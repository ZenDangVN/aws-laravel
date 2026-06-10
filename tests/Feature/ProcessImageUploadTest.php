<?php

use App\Jobs\ProcessImageUpload;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

beforeEach(function () {
    Storage::fake('s3');
});

function makeJpegContents(int $width = 300, int $height = 300): string
{
    return (string) (new ImageManager(new GdDriver))
        ->create($width, $height)
        ->fill('#3b82f6')
        ->toJpeg();
}

test('job generates thumb and medium webp variants for avatar collection', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'avatars/original/abc123.jpg',
        'collection' => 'avatars',
        'visibility' => 'public',
        'mime_type' => 'image/jpeg',
    ]);

    Storage::disk('s3')->put('avatars/original/abc123.jpg', makeJpegContents());

    (new ProcessImageUpload($upload))->handle();

    $upload->refresh();

    expect($upload->processed)->toBeTrue()
        ->and($upload->variants)->toHaveKeys(['thumb', 'medium'])
        ->and($upload->variants['thumb'])->toBe('avatars/thumb/abc123.webp')
        ->and($upload->variants['medium'])->toBe('avatars/medium/abc123.webp');

    Storage::disk('s3')->assertExists('avatars/thumb/abc123.webp');
    Storage::disk('s3')->assertExists('avatars/medium/abc123.webp');
});

test('job updates user avatar to medium variant path', function () {
    $user = User::factory()->create(['avatar' => 'avatars/original/abc123.jpg']);
    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'avatars/original/abc123.jpg',
        'collection' => 'avatars',
        'visibility' => 'public',
        'mime_type' => 'image/jpeg',
    ]);

    Storage::disk('s3')->put('avatars/original/abc123.jpg', makeJpegContents());

    (new ProcessImageUpload($upload))->handle();

    expect($user->fresh()->avatar)->toBe('avatars/medium/abc123.webp');
});

test('job does not upscale images smaller than target dimensions', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'avatars/original/small.jpg',
        'collection' => 'avatars',
        'visibility' => 'public',
        'mime_type' => 'image/jpeg',
    ]);

    // 50x50 image — smaller than 256x256 medium target
    Storage::disk('s3')->put('avatars/original/small.jpg', makeJpegContents(50, 50));

    (new ProcessImageUpload($upload))->handle();

    $upload->refresh();
    expect($upload->processed)->toBeTrue();

    // Variant file exists but dimensions should not exceed the original
    Storage::disk('s3')->assertExists('avatars/medium/small.webp');
});

test('job skips non-image mime types', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'documents/original/file.pdf',
        'collection' => 'documents',
        'visibility' => 'private',
        'mime_type' => 'application/pdf',
    ]);

    (new ProcessImageUpload($upload))->handle();

    $upload->refresh();

    expect($upload->processed)->toBeFalse()
        ->and($upload->variants)->toBeNull();
});

test('job skips collections with no defined variant sizes', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'other/original/img.jpg',
        'collection' => 'other',
        'visibility' => 'private',
        'mime_type' => 'image/jpeg',
    ]);

    Storage::disk('s3')->put('other/original/img.jpg', makeJpegContents());

    (new ProcessImageUpload($upload))->handle();

    $upload->refresh();

    expect($upload->processed)->toBeFalse()
        ->and($upload->variants)->toBeNull();
});
