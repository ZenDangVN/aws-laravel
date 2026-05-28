<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUploadRequest;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class UploadController extends Controller
{
    public function store(StoreUploadRequest $request): JsonResponse|RedirectResponse
    {
        $file = $request->file('file');
        $collection = $request->input('collection', 'default');
        $visibility = $request->input('visibility', 'private');

        $extension = $file->getClientOriginalExtension();
        $path = $collection.'/'.Str::uuid().'.'.$extension;

        Storage::disk('s3')->putFileAs(
            $collection,
            $file,
            basename($path),
        );

        $upload = $request->user()->uploads()->create([
            'disk' => 's3',
            'path' => $path,
            'visibility' => $visibility,
            'collection' => $collection,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        if ($collection === 'avatars') {
            $existingAvatar = $request->user()->uploads()
                ->inCollection('avatars')
                ->where('id', '!=', $upload->id)
                ->first();

            if ($existingAvatar) {
                Storage::disk($existingAvatar->disk)->delete($existingAvatar->path);
                $existingAvatar->delete();
            }

            $request->user()->update(['avatar' => $path]);
        }

        if ($request->inertia()) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('File uploaded successfully.')]);

            return to_route('profile.edit');
        }

        return response()->json([
            'id' => $upload->id,
            'path' => $upload->path,
            'url' => $upload->url(),
        ]);
    }

    public function show(Request $request, Upload $upload): JsonResponse
    {
        abort_unless($upload->user_id === $request->user()->id, Response::HTTP_FORBIDDEN);

        return response()->json(['url' => $upload->url()]);
    }

    public function destroy(Request $request, Upload $upload): Response
    {
        abort_unless($upload->user_id === $request->user()->id, Response::HTTP_FORBIDDEN);

        Storage::disk($upload->disk)->delete($upload->path);

        if ($request->user()->avatar === $upload->path) {
            $request->user()->update(['avatar' => null]);
        }

        $upload->delete();

        return response()->noContent();
    }
}
