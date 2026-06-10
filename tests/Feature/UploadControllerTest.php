<?php

use App\Jobs\ProcessImageUpload;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
    Queue::fake();
});

test('unauthenticated users cannot upload files', function () {
    $response = $this->post(route('uploads.store'), [
        'file' => UploadedFile::fake()->image('photo.jpg'),
    ]);

    $response->assertRedirect(route('login'));
});

test('store creates upload record and puts file on s3', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $response = $this->postJson(route('uploads.store'), [
        'file' => $file,
        'collection' => 'documents',
        'visibility' => 'private',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['id', 'path', 'url']);

    $upload = Upload::find($response->json('id'));
    expect($upload)->not->toBeNull()
        ->and($upload->collection)->toBe('documents')
        ->and($upload->visibility)->toBe('private')
        ->and($upload->user_id)->toBe($user->id)
        ->and($upload->path)->toContain('documents/original/');

    Storage::disk('s3')->assertExists($upload->path);
    Queue::assertPushed(ProcessImageUpload::class);
});

test('store rejects non-image files', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('doc.pdf', 512, 'application/pdf'),
        'visibility' => 'private',
    ])->assertUnprocessable();
});

test('store with public visibility returns permanent url when cloudfront is configured', function () {
    Config::set('filesystems.disks.s3.url', 'https://cdn.example.com');

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('uploads.store'), [
        'file' => UploadedFile::fake()->image('avatar.png'),
        'collection' => 'public-images',
        'visibility' => 'public',
    ]);

    $response->assertOk();

    $url = $response->json('url');
    expect($url)
        ->toBeString()
        ->not->toBeEmpty()
        ->not->toContain('expiration')
        ->not->toContain('X-Amz-Signature');
});

test('store with public visibility returns signed url when cloudfront is not configured', function () {
    Config::set('filesystems.disks.s3.url', null);

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('uploads.store'), [
        'file' => UploadedFile::fake()->image('photo.png'),
        'collection' => 'images',
        'visibility' => 'public',
    ]);

    $response->assertOk();

    $url = $response->json('url');
    expect($url)->toContain('expiration');

    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    expect((int) $params['expiration'])->toBeGreaterThan(now()->addHours(7)->timestamp);
});

test('store with private visibility returns 15-minute signed url', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('uploads.store'), [
        'file' => UploadedFile::fake()->image('photo.png', 100, 100),
        'visibility' => 'private',
    ]);

    $response->assertOk();

    $url = $response->json('url');
    expect($url)->toContain('expiration');

    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    expect((int) $params['expiration'])->toBeLessThan(now()->addHours(1)->timestamp);
});

test('store with avatars collection sets initial avatar path and dispatches job', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('uploads.store'), [
        'file' => UploadedFile::fake()->image('me.jpg'),
        'collection' => 'avatars',
        'visibility' => 'public',
    ]);

    $response->assertOk();

    $user->refresh();
    expect($user->avatar)->toBe($response->json('path'))
        ->and($user->avatar)->toContain('avatars/original/');

    Queue::assertPushed(ProcessImageUpload::class);
});

test('show returns url for own upload', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'documents/original/test-uuid.jpg',
        'visibility' => 'private',
    ]);

    Storage::disk('s3')->put($upload->path, 'content');

    $response = $this->getJson(route('uploads.show', $upload));

    $response->assertOk()->assertJsonStructure(['url']);
});

test('show returns 403 for another users upload', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($other);

    $upload = Upload::factory()->for($owner)->create([
        'disk' => 's3',
        'path' => 'documents/original/secret-uuid.jpg',
        'visibility' => 'private',
    ]);

    $this->getJson(route('uploads.show', $upload))->assertForbidden();
});

test('destroy deletes original and variant files from s3', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'avatars/original/uuid.jpg',
        'visibility' => 'public',
        'collection' => 'avatars',
        'processed' => true,
        'variants' => ['thumb' => 'avatars/thumb/uuid.webp', 'medium' => 'avatars/medium/uuid.webp'],
    ]);

    Storage::disk('s3')->put($upload->path, 'content');
    Storage::disk('s3')->put('avatars/thumb/uuid.webp', 'content');
    Storage::disk('s3')->put('avatars/medium/uuid.webp', 'content');

    $this->deleteJson(route('uploads.destroy', $upload))
        ->assertNoContent();

    expect(Upload::find($upload->id))->toBeNull();
    Storage::disk('s3')->assertMissing($upload->path);
    Storage::disk('s3')->assertMissing('avatars/thumb/uuid.webp');
    Storage::disk('s3')->assertMissing('avatars/medium/uuid.webp');
});

test('destroy clears user avatar when any variant matches', function () {
    $user = User::factory()->create(['avatar' => 'avatars/medium/uuid.webp']);
    $this->actingAs($user);

    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'avatars/original/uuid.jpg',
        'collection' => 'avatars',
        'processed' => true,
        'variants' => ['medium' => 'avatars/medium/uuid.webp'],
    ]);

    Storage::disk('s3')->put($upload->path, 'content');
    Storage::disk('s3')->put('avatars/medium/uuid.webp', 'content');

    $this->deleteJson(route('uploads.destroy', $upload))->assertNoContent();

    expect($user->fresh()->avatar)->toBeNull();
});
