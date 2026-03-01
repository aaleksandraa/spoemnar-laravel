<?php

use App\Models\User;
use App\Models\Memorial;
use App\Models\MemorialVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 21: YouTube URL validation
// Validates: Requirements 5.1

it('validates YouTube URL format for any video creation request', function () {
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

    // Test valid YouTube URLs
    $validUrls = [
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'https://youtube.com/watch?v=dQw4w9WgXcQ',
        'https://youtu.be/dQw4w9WgXcQ',
        'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'http://www.youtube.com/watch?v=abc123DEF',
        'http://youtu.be/xyz789ABC',
    ];

    foreach ($validUrls as $url) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/videos", [
            'youtube_url' => $url,
            'title' => 'Test Video',
        ]);

        // Should return 201 Created for valid URLs
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'video' => ['id', 'youtube_url', 'memorial_id'],
        ]);
    }

    // Test invalid URLs
    $invalidUrls = [
        'https://vimeo.com/123456',
        'https://www.dailymotion.com/video/x123456',
        'https://example.com/video',
        'not-a-url',
        'ftp://youtube.com/watch?v=123',
    ];

    foreach ($invalidUrls as $url) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/videos", [
            'youtube_url' => $url,
            'title' => 'Test Video',
        ]);

        // Should return 422 Unprocessable Entity for invalid URLs
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['youtube_url']);
    }
})->repeat(100);

// Feature: laravel-migration, Property 22: Video record fields
// Validates: Requirements 5.2

it('stores youtube_url, title, and display_order for any memorial video', function () {
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
        [
            'youtube_url' => 'https://www.youtube.com/watch?v=video1',
            'title' => 'Memorial Video 1',
            'display_order' => 0,
        ],
        [
            'youtube_url' => 'https://youtu.be/video2',
            'title' => 'Memorial Video 2',
            'display_order' => 1,
        ],
        [
            'youtube_url' => 'https://www.youtube.com/watch?v=video3',
            'title' => null, // nullable title
            'display_order' => 2,
        ],
        [
            'youtube_url' => 'https://www.youtube.com/embed/video4',
            'title' => 'Video with Embed URL',
            'display_order' => null, // auto-assigned display_order
        ],
    ];

    foreach ($testCases as $testCase) {
        $requestData = ['youtube_url' => $testCase['youtube_url']];

        if ($testCase['title'] !== null) {
            $requestData['title'] = $testCase['title'];
        }

        if ($testCase['display_order'] !== null) {
            $requestData['display_order'] = $testCase['display_order'];
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/videos", $requestData);

        $response->assertStatus(201);

        $videoId = $response->json('video.id');
        $video = MemorialVideo::find($videoId);

        // Verify all fields are stored
        expect($video->youtube_url)->toBe($testCase['youtube_url']);
        expect($video->title)->toBe($testCase['title']);
        expect($video->display_order)->toBeInt();
        expect($video->memorial_id)->toBe($memorial->id);
    }
})->repeat(100);

// Feature: laravel-migration, Property 23: Video deletion
// Validates: Requirements 5.3

it('removes video record for any deletion request by memorial owner', function () {
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

    // Create video
    $video = MemorialVideo::create([
        'memorial_id' => $memorial->id,
        'youtube_url' => 'https://www.youtube.com/watch?v=testVideo',
        'title' => 'Video to Delete',
        'display_order' => 0,
    ]);

    // Verify video exists
    $this->assertDatabaseHas('memorial_videos', ['id' => $video->id]);

    // Delete video
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->deleteJson("/api/v1/videos/{$video->id}");

    $response->assertStatus(204);

    // Verify video is deleted from database
    $this->assertDatabaseMissing('memorial_videos', ['id' => $video->id]);
})->repeat(100);

// Feature: laravel-migration, Property 24: Video display order sorting
// Validates: Requirements 5.4

it('sorts videos by display_order in ascending order for any memorial', function () {
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

    // Create videos with different display orders (intentionally out of order)
    $displayOrders = [5, 1, 3, 2, 4];
    $videoIds = [];

    foreach ($displayOrders as $order) {
        $video = MemorialVideo::create([
            'memorial_id' => $memorial->id,
            'youtube_url' => "https://www.youtube.com/watch?v=video{$order}",
            'title' => "Video {$order}",
            'display_order' => $order,
        ]);
        $videoIds[$order] = $video->id;
    }

    // Retrieve memorial with videos
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson("/api/v1/memorials/{$memorial->slug}");

    $response->assertStatus(200);

    // Videos should be sorted by display_order
    $videos = $response->json('memorial.videos');
    expect($videos)->toBeArray();
    expect(count($videos))->toBe(5);

    // Verify sorting
    $previousOrder = -1;
    foreach ($videos as $video) {
        expect($video['display_order'])->toBeGreaterThan($previousOrder);
        $previousOrder = $video['display_order'];
    }

    // Verify exact order: 1, 2, 3, 4, 5
    expect($videos[0]['display_order'])->toBe(1);
    expect($videos[1]['display_order'])->toBe(2);
    expect($videos[2]['display_order'])->toBe(3);
    expect($videos[3]['display_order'])->toBe(4);
    expect($videos[4]['display_order'])->toBe(5);
})->repeat(100);

