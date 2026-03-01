<?php

use App\Models\User;
use App\Models\Memorial;
use App\Models\MemorialImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 15: Image upload returns URL
// Validates: Requirements 4.1

it('returns URL for any valid image file upload', function () {
    Storage::fake('public');

    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create memorial
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    $testImages = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.png'),
        UploadedFile::fake()->image('photo3.webp'),
        UploadedFile::fake()->image('photo4.gif'),
        UploadedFile::fake()->image('large-photo.jpg', 1920, 1080),
    ];

    foreach ($testImages as $image) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
            'image' => $image,
        ]);

        // Should return 201 Created
        $response->assertStatus(201);

        // Should return image data with URL
        $response->assertJsonStructure([
            'image' => ['id', 'image_url', 'memorial_id'],
        ]);

        // URL should be a non-empty string
        $imageUrl = $response->json('image.image_url');
        expect($imageUrl)->toBeString();
        expect($imageUrl)->not->toBeEmpty();

        // File should exist in storage
        $filename = basename($imageUrl);
        Storage::disk('public')->assertExists("memorials/{$memorial->id}/{$filename}");
    }
})->repeat(100);

// Feature: laravel-migration, Property 16: Gallery image record creation
// Validates: Requirements 4.2

it('creates memorial_images record for any uploaded gallery image', function () {
    Storage::fake('public');

    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create memorial
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    $testCases = [
        ['image' => UploadedFile::fake()->image('photo1.jpg'), 'caption' => 'Family photo'],
        ['image' => UploadedFile::fake()->image('photo2.png'), 'caption' => 'Birthday celebration'],
        ['image' => UploadedFile::fake()->image('photo3.jpg'), 'caption' => null],
        ['image' => UploadedFile::fake()->image('photo4.webp'), 'caption' => 'Vacation'],
    ];

    foreach ($testCases as $index => $testCase) {
        $requestData = ['image' => $testCase['image']];
        if ($testCase['caption'] !== null) {
            $requestData['caption'] = $testCase['caption'];
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/images", $requestData);

        $response->assertStatus(201);

        // Should have correct memorial_id reference
        $imageId = $response->json('image.id');
        $this->assertDatabaseHas('memorial_images', [
            'id' => $imageId,
            'memorial_id' => $memorial->id,
        ]);

        // Verify the image belongs to the memorial
        $memorialImage = MemorialImage::find($imageId);
        expect($memorialImage->memorial_id)->toBe($memorial->id);
        expect($memorialImage->memorial->id)->toBe($memorial->id);
    }
})->repeat(100);

// Feature: laravel-migration, Property 17: Display order sorting
// Validates: Requirements 4.3

it('sorts images by display_order in ascending order for any memorial gallery', function () {
    Storage::fake('public');

    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create memorial
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Create images with different display orders (intentionally out of order)
    $displayOrders = [5, 1, 3, 2, 4];
    $imageIds = [];

    foreach ($displayOrders as $order) {
        $image = MemorialImage::create([
            'memorial_id' => $memorial->id,
            'image_url' => "https://example.com/image{$order}.jpg",
            'display_order' => $order,
        ]);
        $imageIds[$order] = $image->id;
    }

    // Retrieve memorial with images
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson("/api/v1/memorials/{$memorial->slug}");

    $response->assertStatus(200);

    // Images should be sorted by display_order
    $images = $response->json('memorial.images');
    expect($images)->toBeArray();
    expect(count($images))->toBe(5);

    // Verify sorting
    $previousOrder = -1;
    foreach ($images as $image) {
        expect($image['display_order'])->toBeGreaterThan($previousOrder);
        $previousOrder = $image['display_order'];
    }

    // Verify exact order: 1, 2, 3, 4, 5
    expect($images[0]['display_order'])->toBe(1);
    expect($images[1]['display_order'])->toBe(2);
    expect($images[2]['display_order'])->toBe(3);
    expect($images[3]['display_order'])->toBe(4);
    expect($images[4]['display_order'])->toBe(5);
})->repeat(100);

// Feature: laravel-migration, Property 18: Optional caption support
// Validates: Requirements 4.4

it('allows memorial image creation with or without caption', function () {
    Storage::fake('public');

    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create memorial
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Test with caption
    $imageWithCaption = UploadedFile::fake()->image('photo-with-caption.jpg');
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
        'image' => $imageWithCaption,
        'caption' => 'This is a caption',
    ]);

    $response->assertStatus(201);
    $imageId1 = $response->json('image.id');
    $this->assertDatabaseHas('memorial_images', [
        'id' => $imageId1,
        'caption' => 'This is a caption',
    ]);

    // Test without caption
    $imageWithoutCaption = UploadedFile::fake()->image('photo-without-caption.jpg');
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
        'image' => $imageWithoutCaption,
    ]);

    $response->assertStatus(201);
    $imageId2 = $response->json('image.id');
    $this->assertDatabaseHas('memorial_images', [
        'id' => $imageId2,
        'caption' => null,
    ]);

    // Test with empty caption
    $imageWithEmptyCaption = UploadedFile::fake()->image('photo-empty-caption.jpg');
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
        'image' => $imageWithEmptyCaption,
        'caption' => '',
    ]);

    $response->assertStatus(201);
    $imageId3 = $response->json('image.id');

    // Empty string should be stored as null or empty
    $image = MemorialImage::find($imageId3);
    expect($image->caption === null || $image->caption === '')->toBeTrue();
})->repeat(100);

// Feature: laravel-migration, Property 19: Image deletion cleanup
// Validates: Requirements 4.5

it('removes both file and database record for any image deletion', function () {
    Storage::fake('public');

    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create memorial
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Upload image
    $image = UploadedFile::fake()->image('photo-to-delete.jpg');
    $uploadResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
        'image' => $image,
    ]);

    $uploadResponse->assertStatus(201);
    $imageId = $uploadResponse->json('image.id');
    $imageUrl = $uploadResponse->json('image.image_url');
    $filename = basename($imageUrl);

    // Verify file exists
    Storage::disk('public')->assertExists("memorials/{$memorial->id}/{$filename}");

    // Verify database record exists
    $this->assertDatabaseHas('memorial_images', ['id' => $imageId]);

    // Delete image
    $deleteResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->deleteJson("/api/v1/images/{$imageId}");

    $deleteResponse->assertStatus(204);

    // Verify file is deleted from storage
    Storage::disk('public')->assertMissing("memorials/{$memorial->id}/{$filename}");

    // Verify database record is deleted
    $this->assertDatabaseMissing('memorial_images', ['id' => $imageId]);
})->repeat(100);

// Feature: laravel-migration, Property 20: Image format validation
// Validates: Requirements 4.6

it('rejects files that are not valid image formats', function () {
    Storage::fake('public');

    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create memorial
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Test invalid file formats
    $invalidFiles = [
        UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('document.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        UploadedFile::fake()->create('video.mp4', 100, 'video/mp4'),
        UploadedFile::fake()->create('audio.mp3', 100, 'audio/mpeg'),
        UploadedFile::fake()->create('text.txt', 100, 'text/plain'),
    ];

    foreach ($invalidFiles as $file) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
            'image' => $file,
        ]);

        // Should return 422 Unprocessable Entity
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    // Test valid image formats (jpg, png, webp, gif)
    $validImages = [
        UploadedFile::fake()->image('photo.jpg'),
        UploadedFile::fake()->image('photo.jpeg'),
        UploadedFile::fake()->image('photo.png'),
        UploadedFile::fake()->image('photo.gif'),
        UploadedFile::fake()->image('photo.webp'),
    ];

    foreach ($validImages as $image) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
            'image' => $image,
        ]);

        // Should return 201 Created
        $response->assertStatus(201);
    }
})->repeat(100);
