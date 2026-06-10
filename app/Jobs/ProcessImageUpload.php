<?php

namespace App\Jobs;

use App\Models\Upload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class ProcessImageUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Upload $upload) {}

    public function handle(): void
    {
        if (! str_starts_with($this->upload->mime_type, 'image/')) {
            return;
        }

        $sizes = config('image.variants.'.$this->upload->collection, []);

        if (empty($sizes)) {
            return;
        }

        $contents = Storage::disk($this->upload->disk)->get($this->upload->path);
        $manager = new ImageManager(new GdDriver);
        $baseDir = $this->upload->collection;
        $uuid = pathinfo(basename($this->upload->path), PATHINFO_FILENAME);
        $variants = [];

        foreach ($sizes as $name => ['width' => $w, 'height' => $h]) {
            $encoded = $manager->read($contents)
                ->scaleDown($w, $h)
                ->toWebp(quality: 85, strip: true);

            $variantPath = $baseDir.'/'.$name.'/'.$uuid.'.webp';

            Storage::disk($this->upload->disk)->put($variantPath, (string) $encoded, [
                'visibility' => $this->upload->visibility,
                'CacheControl' => 'public, max-age=31536000, immutable',
                'ContentType' => 'image/webp',
            ]);

            $variants[$name] = $variantPath;
        }

        $this->upload->update(['variants' => $variants, 'processed' => true]);

        if ($this->upload->collection === 'avatars' && isset($variants['medium'])) {
            $this->upload->user->update(['avatar' => $variants['medium']]);
        }
    }
}
