<?php

use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
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
        ->and($upload->user_id)->toBe($user->id);

    Storage::disk('s3')->assertExists($upload->path);
});

test('store with public visibility returns 8-hour signed url', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('uploads.store'), [
        'file' => UploadedFile::fake()->image('avatar.png'),
        'collection' => 'public-images',
        'visibility' => 'public',
    ]);

    $response->assertOk();

    $url = $response->json('url');
    expect($url)->toContain('expiration');

    $expiration = (int) parse_url($url, PHP_URL_QUERY);
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    expect((int) $params['expiration'])->toBeGreaterThan(now()->addHours(7)->timestamp);
});

test('store with private visibility returns 15-minute signed url', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('doc.pdf', 512, 'application/pdf'),
        'visibility' => 'private',
    ]);

    $response->assertOk();

    $url = $response->json('url');
    expect($url)->toContain('expiration');

    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    expect((int) $params['expiration'])->toBeLessThan(now()->addHours(1)->timestamp);
});

test('store with avatars collection updates user avatar column', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson(route('uploads.store'), [
        'file' => UploadedFile::fake()->image('me.jpg'),
        'collection' => 'avatars',
        'visibility' => 'public',
    ]);

    $response->assertOk();

    $user->refresh();
    expect($user->avatar)->toBe($response->json('path'));
});

test('show returns url for own upload', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'documents/test.pdf',
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
        'path' => 'documents/secret.pdf',
        'visibility' => 'private',
    ]);

    $this->getJson(route('uploads.show', $upload))->assertForbidden();
});

test('destroy deletes file from s3 and database', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $upload = Upload::factory()->for($user)->create([
        'disk' => 's3',
        'path' => 'documents/deleteme.pdf',
        'visibility' => 'private',
    ]);

    Storage::disk('s3')->put($upload->path, 'content');

    $this->deleteJson(route('uploads.destroy', $upload))
        ->assertNoContent();

    expect(Upload::find($upload->id))->toBeNull();
    Storage::disk('s3')->assertMissing($upload->path);
});
