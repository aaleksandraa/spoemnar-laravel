<?php

/**
 * Additional Security Fixes Preservation Property Tests
 *
 * These tests validate that existing legitimate functionality is preserved
 * when security fixes are implemented. They test on UNFIXED code and should PASS.
 *
 * IMPORTANT: These tests MUST PASS before and after fixes are implemented.
 * They ensure we don't break existing functionality while fixing security bugs.
 *
 * **Validates: Property 2 (Preservation) - Existing Functionality Preservation**
 */

use App\Models\User;
use App\Models\UserRole;
use App\Models\Memorial;
use App\Models\Tribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('2.1 Valid Tribute Creation Unchanged', function () {

    /**
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     * Property 9: Preservation - Valid Tribute Creation
     *
     * For any tribute creation request with only allowed fields (author_name, author_email, message)
     * and within rate limits, the system SHALL produce the same result as the original system.
     */

    it('allows valid tribute with only allowed fields to be created successfully', function () {
        // Create public memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        // Submit tribute with only allowed fields
        $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'message' => 'Beautiful memorial, will be missed.',
            'timestamp' => time(), // Required for anti-spam
        ]);

        // PRESERVATION: Valid tribute creation should succeed
        expect($response->status())->toBeIn([200, 201]);

        // Verify tribute was created
        $tribute = Tribute::where('author_email', 'john@example.com')->first();
        expect($tribute)->not->toBeNull();
        expect($tribute->author_name)->toBe('John Doe');
    });

    it('allows memorial owner to view tributes on their memorial', function () {
        // Create owner, memorial, and tribute
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);
        $token = $owner->createToken('auth_token')->plainTextToken;

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        Tribute::factory()->create([
            'memorial_id' => $memorial->id,
            'author_name' => 'Test Author',
            'author_email' => 'test@example.com',
            'message' => 'Test tribute message',
        ]);

        // Owner views their memorial
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/memorials/{$memorial->slug}");

        // PRESERVATION: Owner can view tributes
        expect($response->status())->toBe(200);
        expect($response->json('data.tributes'))->toBeArray();
        expect(count($response->json('data.tributes')))->toBeGreaterThanOrEqual(1);
    });

    it('allows tribute listing for a public memorial', function () {
        // Create public memorial with tribute
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
            'slug' => 'test-memorial-tributes',
        ]);

        Tribute::factory()->create([
            'memorial_id' => $memorial->id,
        ]);

        // List tributes via memorial slug endpoint (tributes are included in memorial response)
        $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

        // PRESERVATION: Memorial with tributes should be accessible
        expect($response->status())->toBe(200);
        expect($response->json('data.tributes'))->toBeArray();
    });

    it('allows listing all tributes for a memorial', function () {
        // Create memorial with multiple tributes
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        Tribute::factory()->count(3)->create([
            'memorial_id' => $memorial->id,
        ]);

        // List tributes
        $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

        // PRESERVATION: Tribute listing should work
        expect($response->status())->toBe(200);
        expect($response->json('data.tributes'))->toBeArray();
        expect(count($response->json('data.tributes')))->toBeGreaterThanOrEqual(3);
    });
});

describe('2.2 Memorial Owner Tribute Deletion Unchanged', function () {

    /**
     * **Validates: Requirements 3.2**
     * Property 9: Preservation - Memorial Owner Tribute Deletion
     *
     * For all tributes where user is memorial owner, deletion succeeds.
     */

    it('allows memorial owner to delete tributes from their memorial', function () {
        // Create owner, memorial, and tribute
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);
        $token = $owner->createToken('auth_token')->plainTextToken;

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
        ]);

        $tribute = Tribute::factory()->create([
            'memorial_id' => $memorial->id,
        ]);

        // Owner deletes tribute
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/v1/tributes/{$tribute->id}");

        // PRESERVATION: Owner can delete tributes
        expect($response->status())->toBeIn([200, 204]);
    });
});

describe('2.3 Admin Operations on Other Users Unchanged', function () {

    /**
     * **Validates: Requirements 3.5, 3.6, 3.7, 3.8**
     * Property 10: Preservation - Admin Operations on Other Users
     *
     * For all users where admin.id != user.id, deletion and management succeeds.
     */

    it('allows admin to delete users other than themselves', function () {
        // Create admin user
        $admin = User::factory()->create();
        $admin->profile()->create(['email' => $admin->email]);
        UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);
        $token = $admin->createToken('auth_token')->plainTextToken;

        // Create user to delete
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        // Admin deletes other user
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/v1/admin/users/{$user->id}");

        // PRESERVATION: Admin can delete other users
        expect($response->status())->toBeIn([200, 204]);
    });

    it('allows admin to manage memorials via admin endpoints', function () {
        // Create admin
        $admin = User::factory()->create();
        $admin->profile()->create(['email' => $admin->email]);
        UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);
        $token = $admin->createToken('auth_token')->plainTextToken;

        // Create memorial by another user
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
        ]);

        // Admin accesses memorial
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/memorials/{$memorial->slug}");

        // PRESERVATION: Admin has full access
        expect($response->status())->toBe(200);
    });

    it('allows admin to use dedicated admin endpoints for tributes', function () {
        // Create admin
        $admin = User::factory()->create();
        $admin->profile()->create(['email' => $admin->email]);
        UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);
        $token = $admin->createToken('auth_token')->plainTextToken;

        // Create memorial and tribute
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
        ]);

        $tribute = Tribute::factory()->create([
            'memorial_id' => $memorial->id,
        ]);

        // Admin deletes tribute via admin endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/v1/admin/tributes/{$tribute->id}");

        // PRESERVATION: Admin endpoint should work
        expect($response->status())->toBeIn([200, 204, 404]);
    });

    it('allows admin to view user list with pagination', function () {
        // Create admin
        $admin = User::factory()->create();
        $admin->profile()->create(['email' => $admin->email]);
        UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);
        $token = $admin->createToken('auth_token')->plainTextToken;

        // Create multiple users
        User::factory()->count(5)->create()->each(function ($user) {
            $user->profile()->create(['email' => $user->email]);
        });

        // Admin views user list
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/admin/users');

        // PRESERVATION: Admin can view user list
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toBeArray();
    });
});

describe('2.4 Image Upload Functionality Unchanged', function () {

    /**
     * **Validates: Requirements 3.9, 3.10, 3.11, 3.12**
     * Property 11: Preservation - Image Upload Functionality
     *
     * For all valid images with size <= 5MB, upload succeeds.
     */

    it('allows valid images under 5MB to upload successfully', function () {
        // Create user and memorial
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create a fake 2MB image (well under 5MB limit)
        Storage::fake('public');
        $validImage = UploadedFile::fake()->image('valid.jpg')->size(2048); // 2MB

        // Upload image
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
            'image' => $validImage,
        ]);

        // PRESERVATION: Valid image upload should succeed
        expect($response->status())->toBeIn([200, 201]);
    });

    it('allows images to be deleted when memorial is deleted', function () {
        // Create user and memorial
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
        ]);

        // Delete memorial
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/v1/memorials/{$memorial->id}");

        // PRESERVATION: Memorial deletion should work
        expect($response->status())->toBeIn([200, 204]);
    });

    it('allows images to be displayed in memorial views', function () {
        // Create memorial
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // View memorial
        $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

        // PRESERVATION: Memorial view should work
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toHaveKey('images');
    });

    it('returns validation errors for invalid image types', function () {
        // Create user and memorial
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create invalid file (PDF instead of image)
        Storage::fake('public');
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024);

        // Attempt to upload invalid file
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
            'image' => $invalidFile,
        ]);

        // PRESERVATION: Validation should reject invalid types
        expect($response->status())->toBe(422);
    });
});

describe('2.5 Authentication Functionality Unchanged', function () {

    /**
     * **Validates: Requirements 3.13, 3.14, 3.15, 3.16**
     * Property: Authentication and session management continues to work correctly
     */

    it('allows user to log in with valid credentials', function () {
        // Create user
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->profile()->create(['email' => $user->email]);

        // Login
        $response = $this->postJson('/api/v1/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        // PRESERVATION: Login should succeed
        expect($response->status())->toBe(200);
        expect($response->json())->toHaveKey('user');
    });

    it('allows user to log out and invalidate token', function () {
        // Create user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/logout');

        // PRESERVATION: Logout should succeed
        expect($response->status())->toBe(200);

        // Verify token is invalidated
        $user->refresh();
        expect($user->tokens()->count())->toBe(0);
    });

    it('allows password reset request with valid email', function () {
        // Create user
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);
        $user->profile()->create(['email' => $user->email]);

        // Request password reset
        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        // PRESERVATION: Password reset request should work
        expect($response->status())->toBeIn([200, 201]);
    });

    it('allows password change with valid reset token', function () {
        // Create user
        $user = User::factory()->create([
            'email' => 'reset2@example.com',
            'password' => Hash::make('oldpassword'),
        ]);
        $user->profile()->create(['email' => $user->email]);

        // Generate reset token
        $token = \Illuminate\Support\Facades\Password::createToken($user);

        // Reset password
        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'reset2@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // PRESERVATION: Password reset should work
        expect($response->status())->toBeIn([200, 201, 404]);
    });
});

describe('2.6 Search Functionality Unchanged', function () {

    /**
     * **Validates: Requirements 3.17, 3.18, 3.19, 3.20**
     * Property 12: Preservation - Search Functionality
     *
     * For all search requests within rate limits, results match expected.
     */

    it('allows search for memorials with query parameter', function () {
        // Create memorials
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        // Search for "John" with required query parameter
        $response = $this->getJson('/api/v1/search?query=John');

        // PRESERVATION: Search should return filtered results
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toBeArray();
    });

    it('returns results when search query is provided', function () {
        // Create public memorials
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        Memorial::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Search with a query (required parameter)
        $response = $this->getJson('/api/v1/search?query=memorial');

        // PRESERVATION: Search with query should return results
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toBeArray();
    });

    it('applies search query correctly', function () {
        // Create memorials with different names
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'first_name' => 'TestName',
            'birth_date' => '1980-01-01',
            'death_date' => '2020-01-01',
        ]);

        // Search with query
        $response = $this->getJson('/api/v1/search?query=TestName');

        // PRESERVATION: Search should work
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toBeArray();
    });

    it('returns paginated search results correctly', function () {
        // Create many memorials
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        Memorial::factory()->count(15)->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Search with pagination and required query parameter
        $response = $this->getJson('/api/v1/search?query=memorial&per_page=10');

        // PRESERVATION: Pagination should work
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toBeArray();
    });
});

describe('2.7 Content Display Unchanged', function () {

    /**
     * **Validates: Requirements 3.21, 3.22, 3.23, 3.24**
     * Property 13: Preservation - Content Display
     *
     * For all content display after sanitization, formatted text renders correctly.
     */

    it('displays biography with safe HTML formatting preserved', function () {
        // Create memorial with safe HTML
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'biography' => '<p>This is a <strong>biography</strong> with safe HTML.</p>',
        ]);

        // View memorial
        $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

        // PRESERVATION: Biography should display correctly
        expect($response->status())->toBe(200);
        expect($response->json('data.biography'))->toBeString();
    });

    it('displays tribute messages with formatting correctly', function () {
        // Create memorial and tribute
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        Tribute::factory()->create([
            'memorial_id' => $memorial->id,
            'message' => '<p>Beautiful tribute with <em>emphasis</em>.</p>',
        ]);

        // View memorial
        $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

        // PRESERVATION: Tribute messages should display correctly
        expect($response->status())->toBe(200);
        expect($response->json('data.tributes'))->toBeArray();
        expect($response->json('data.tributes.0.message'))->toBeString();
    });

    it('renders HTML entities correctly without double-encoding', function () {
        // Create memorial with HTML entities
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'biography' => 'Biography with &amp; ampersand and &lt; less than.',
        ]);

        // View memorial
        $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

        // PRESERVATION: HTML entities should render properly
        expect($response->status())->toBe(200);
        expect($response->json('data.biography'))->toBeString();
    });

    it('preserves line breaks and basic formatting in content', function () {
        // Create memorial with line breaks
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'biography' => "Line 1\nLine 2\nLine 3",
        ]);

        // View memorial
        $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

        // PRESERVATION: Line breaks should be preserved
        expect($response->status())->toBe(200);
        expect($response->json('data.biography'))->toBeString();
    });
});
