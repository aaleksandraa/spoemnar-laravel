# Additional Security Fixes - Bugfix Design

## Overview

During comprehensive security audit follow-up, 8 additional critical security vulnerabilities were identified requiring immediate remediation. These vulnerabilities complement the initial 10 security fixes and address gaps in input validation, authorization, resource management, and session security.

The vulnerabilities span multiple security domains:
- Input validation (mass assignment, XSS via unsanitized content)
- Authorization (admin self-deletion, tribute moderation)
- Resource management (image upload size limits, rate limiting)
- Session management (cookie clearing on logout)

Fix strategy: Implement defense-in-depth with strict input validation, comprehensive authorization checks, resource limits, and proper session cleanup following Laravel security best practices.

## Glossary

- **Bug_Condition (C)**: Conditions enabling security vulnerabilities - mass assignment exploitation, XSS injection, unauthorized admin deletion, resource exhaustion
- **Property (P)**: Desired secure behavior - strict $fillable protection, HTML sanitization, admin self-deletion prevention, size limits, rate limiting, cookie cleanup
- **Preservation**: Existing functionality that must remain unchanged - tribute creation, admin operations, image uploads, authentication, search
- **Mass Assignment**: Laravel vulnerability where unprotected model attributes can be manipulated via request data
- **XSS**: Cross-Site Scripting - injection attack executing malicious JavaScript through unsanitized user input
- **IDOR**: Insecure Direct Object Reference - accessing resources without proper authorization
- **$fillable**: Laravel model property defining which attributes can be mass-assigned
- **$guarded**: Laravel model property defining which attributes cannot be mass-assigned
- **HTML Purifier**: Library for sanitizing HTML while preserving safe formatting
- **Rate Limiting**: Throttling mechanism to prevent abuse by limiting request frequency
- **HttpOnly Cookie**: Cookie flag preventing JavaScript access, mitigating XSS token theft


## Bug Details

### Fault Condition

Security vulnerabilities manifest in 8 distinct categories. Each category represents a specific bug condition:

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type HTTPRequest
  OUTPUT: boolean
  
  RETURN (
    // 1. Mass assignment vulnerability
    (input.endpoint = '/api/v1/memorials/{memorial}/tributes' AND
     input.method = 'POST' AND
     (Tribute.$fillable IS_EMPTY OR Tribute.$guarded = [] OR
      input.data CONTAINS ['is_approved', 'memorial_id', 'created_at']) AND
     unauthorizedFieldsAccepted(input.data))
    
    OR
    
    // 2. Admin self-deletion incomplete prevention
    (input.endpoint MATCHES '/api/v1/admin/users/{user_id}' AND
     input.method = 'DELETE' AND
     input.user_id = auth.user.id AND
     deletionAllowed(input))
    
    OR
    
    // 3. Missing input sanitization for XSS
    (input.endpoint IN ['/api/v1/memorials', '/api/v1/memorials/{memorial}/tributes', '/contact'] AND
     input.method = 'POST' AND
     (input.data.biography CONTAINS '<script>' OR
      input.data.message CONTAINS '<img src=x onerror=' OR
      input.data.subject CONTAINS '<iframe>') AND
     NOT sanitized(input.data))
    
    OR
    
    // 4. Image upload without size limit
    (input.endpoint MATCHES '/api/v1/memorials/{memorial}/images' AND
     input.method = 'POST' AND
     input.file.size > 5 * 1024 * 1024 AND
     NOT sizeValidationFailed(input))
    
    OR
    
    // 5. Tribute deletion missing admin authorization
    (input.endpoint = '/api/v1/tributes/{tribute}' AND
     input.method = 'DELETE' AND
     auth.user.hasRole('admin') AND
     auth.user.id != tribute.memorial.user_id AND
     response.status = 403)
    
    OR
    
    // 6. Undefined rate limiters for password reset
    (input.endpoint IN ['/api/v1/forgot-password', '/api/v1/reset-password'] AND
     middleware CONTAINS 'throttle:password-reset-link' AND
     NOT rateLimiterDefined('password-reset-link'))
    
    OR
    
    // 7. Logout does not clear auth cookie
    (input.endpoint = '/api/v1/logout' AND
     input.method = 'POST' AND
     response.tokenDeletedFromDB = true AND
     response.cookies['auth_token'] STILL_EXISTS)
    
    OR
    
    // 8. Search endpoint without rate limiting
    (input.endpoint = '/api/v1/search' AND
     requestCount(input.ip, last_minute) > 60 AND
     NOT hasRateLimit(input.endpoint))
  )
END FUNCTION
```


### Examples

**1. Mass Assignment Exploitation:**
- Request: `POST /api/v1/memorials/123/tributes` with `{"author_name": "Test", "message": "Test", "is_approved": true, "memorial_id": 999}`
- Current: System accepts `is_approved` and `memorial_id` if model lacks $fillable protection
- Expected: System ignores unauthorized fields, only accepts author_name, author_email, message

**2. Admin Self-Deletion:**
- Request: `DELETE /api/v1/admin/users/5` where user ID 5 is the authenticated admin
- Current: System prevents deletion with 422 error in deleteUser() method
- Expected: System prevents deletion with 403 Forbidden in all deletion paths

**3. XSS via Biography:**
- Request: `POST /api/v1/memorials` with `{"biography": "<script>alert('XSS')</script>"}`
- Current: System stores raw HTML without sanitization
- Expected: System sanitizes to `"alert('XSS')"` or removes script entirely

**4. Large Image Upload:**
- Request: `POST /api/v1/memorials/123/images` with 100MB image file
- Current: ImageService::upload() processes without size validation
- Expected: System returns 422 validation error "Image must not exceed 5MB"

**5. Admin Cannot Delete Tribute:**
- Request: `DELETE /api/v1/tributes/456` as admin (not memorial owner)
- Current: System returns 403 Forbidden
- Expected: System allows deletion with 204 No Content

**6. Undefined Password Reset Rate Limiter:**
- Request: `POST /api/v1/forgot-password` (10 times in 1 minute)
- Current: Middleware references 'throttle:password-reset-link' but limiter undefined
- Expected: System throttles after 3 requests per hour

**7. Auth Cookie Persists After Logout:**
- Request: `POST /api/v1/logout`
- Current: Token deleted from database but cookie remains in browser
- Expected: Cookie cleared with expiration set to past date

**8. Search Endpoint Abuse:**
- Request: 1000 `GET /api/v1/search?q=test` in 1 minute
- Current: All requests processed, database performance degrades
- Expected: System returns 429 Too Many Requests after 60 requests per minute


## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Valid tribute submissions within rate limits must continue to create tributes successfully
- Memorial owner tribute deletion must continue to work without admin privileges
- Image uploads under 5MB must continue to process and store correctly
- Admin deletion of other users (non-self) must continue to work
- Password reset flow for valid requests within limits must continue to function
- Search functionality within rate limits must return correct results
- Content display with safe HTML must continue to render properly
- Authentication and session management must continue to work correctly

**Scope:**
All inputs that do NOT attempt mass assignment exploitation, XSS injection, resource exhaustion, or unauthorized operations should continue to work normally. This includes:
- Valid tribute submissions with only allowed fields
- Safe HTML content without malicious scripts
- Image uploads within size limits
- Admin operations on other users
- Normal search queries within rate limits
- Legitimate password reset requests
- Standard logout operations


## Hypothesized Root Cause

Analysis of security vulnerabilities reveals systematic implementation gaps:

### 1. Mass Assignment Vulnerability

**Root Cause**: Tribute model lacks strict $fillable protection, allowing unauthorized field manipulation.

**Evidence**:
- `app/Models/Tribute.php` may have `$guarded = []` or missing $fillable array
- `TributeController::store()` uses `$request->input()` for individual fields but doesn't prevent mass assignment if model is misconfigured
- Laravel's mass assignment protection only works when $fillable or $guarded is properly configured

**Impact**: Attacker can manipulate is_approved, memorial_id, timestamps, bypassing business logic

### 2. Admin Self-Deletion Prevention Incomplete

**Root Cause**: Self-deletion check only exists in AdminUserController::deleteUser() but not in authorization layer.

**Evidence**:
- Check implemented at controller level: `if ($user->id === auth()->id()) return 422`
- No Policy-level authorization preventing self-deletion
- Alternative deletion paths (direct model manipulation, other routes) may bypass check
- Last admin deletion scenario not handled

**Impact**: Admin could delete own account through alternative paths, losing administrative access

### 3. Missing Input Sanitization

**Root Cause**: Controllers accept user input without HTML sanitization, storing raw content.

**Evidence**:
- `MemorialController::store()` directly saves `$request->biography` without sanitization
- `TributeController::store()` saves `$request->message` without filtering
- `ContactController::store()` processes `$request->subject` without sanitization
- No middleware or service layer for input sanitization

**Impact**: Stored XSS vulnerability - malicious scripts executed when content is displayed

### 4. Image Upload Without Size Limit

**Root Cause**: StoreImageRequest validation rules don't include max file size constraint.

**Evidence**:
- `app/Http/Requests/StoreImageRequest.php` may only validate mime types and dimensions
- Missing `'image' => 'max:5120'` validation rule (5MB = 5120 KB)
- `ImageService::upload()` processes files without size check before storage

**Impact**: Resource exhaustion - large files consume disk space and memory, causing DoS

### 5. Tribute Deletion Missing Admin Authorization

**Root Cause**: TributePolicy::delete() only checks memorial ownership, not admin role.

**Evidence**:
- `app/Policies/TributePolicy.php` likely has: `return $user->id === $tribute->memorial->user_id`
- Missing: `|| $user->hasRole('admin')` condition
- Admin moderation requires separate endpoint instead of standard authorization

**Impact**: Admins cannot moderate inappropriate tributes through standard API, inconsistent design

### 6. Undefined Rate Limiters

**Root Cause**: Routes reference custom rate limiters that aren't defined in RouteServiceProvider.

**Evidence**:
- `routes/api.php` has `middleware('throttle:password-reset-link')` and `middleware('throttle:password-reset-submit')`
- `app/Providers/RouteServiceProvider.php` doesn't define these custom limiters in `configureRateLimiting()`
- Laravel falls back to default behavior when custom limiter is undefined

**Impact**: Password reset endpoints may not be properly throttled, enabling abuse

### 7. Logout Cookie Not Cleared

**Root Cause**: AuthController::logout() deletes token from database but doesn't clear cookie.

**Evidence**:
- `AuthController::logout()` calls `$request->user()->currentAccessToken()->delete()`
- Returns `response()->json(['message' => 'Logged out'])` without cookie manipulation
- Cookie remains in browser with expired token value

**Impact**: Confusion and potential security concern - expired token persists in browser storage

### 8. Search Endpoint Without Rate Limiting

**Root Cause**: Search route doesn't apply throttle middleware.

**Evidence**:
- `routes/api.php` search route: `Route::get('/search', [SearchController::class, 'index'])`
- No `->middleware('throttle:60,1')` applied
- Complex search queries can be expensive database operations

**Impact**: DoS vulnerability - attackers can overwhelm database with search requests


## Correctness Properties

Property 1: Fault Condition - Mass Assignment Protection

_For any_ tribute creation request containing unauthorized fields (is_approved, memorial_id, created_at, updated_at), the fixed Tribute model SHALL ignore these fields and only accept fields in the $fillable array (author_name, author_email, message), preventing mass assignment exploitation.

**Validates: Requirements 2.1, 2.2, 2.3**

Property 2: Fault Condition - Admin Self-Deletion Prevention

_For any_ user deletion request where the target user ID matches the authenticated admin's ID, the fixed system SHALL return 403 Forbidden in all deletion paths (controller, policy, direct model), preventing admin self-deletion and loss of administrative access.

**Validates: Requirements 2.4, 2.5, 2.6**

Property 3: Fault Condition - Input Sanitization for XSS Prevention

_For any_ memorial biography, tribute message, or contact form subject containing HTML tags or JavaScript, the fixed system SHALL sanitize the input using strip_tags or HTML Purifier, removing dangerous content while preserving safe formatting, preventing stored XSS attacks.

**Validates: Requirements 2.7, 2.8, 2.9**

Property 4: Fault Condition - Image Upload Size Limits

_For any_ image upload request where the file size exceeds 5MB (5120 KB), the fixed StoreImageRequest SHALL return 422 validation error before processing, preventing resource exhaustion and denial of service.

**Validates: Requirements 2.10, 2.11, 2.12**

Property 5: Fault Condition - Admin Tribute Deletion Authorization

_For any_ tribute deletion request by an authenticated admin user, the fixed TributePolicy SHALL authorize the deletion regardless of memorial ownership, enabling proper content moderation while maintaining owner deletion rights.

**Validates: Requirements 2.13, 2.14, 2.15**

Property 6: Fault Condition - Password Reset Rate Limiting

_For any_ password reset request (forgot-password or reset-password), the fixed RouteServiceProvider SHALL apply defined rate limiters (3 requests per hour for forgot-password, 5 requests per hour for reset-password), preventing password reset abuse.

**Validates: Requirements 2.16, 2.17, 2.18**

Property 7: Fault Condition - Logout Cookie Clearing

_For any_ logout request, the fixed AuthController SHALL delete the token from the database AND clear the auth_token cookie by setting its expiration to a past date, ensuring complete session cleanup.

**Validates: Requirements 2.19, 2.20, 2.21**

Property 8: Fault Condition - Search Rate Limiting

_For any_ search request sequence exceeding 60 requests per minute from the same IP, the fixed system SHALL return 429 Too Many Requests with Retry-After header, preventing search endpoint abuse and database overload.

**Validates: Requirements 2.22, 2.23, 2.24**

Property 9: Preservation - Valid Tribute Creation

_For any_ tribute creation request with only allowed fields (author_name, author_email, message) and within rate limits, the fixed system SHALL produce the same result as the original system, preserving tribute functionality.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

Property 10: Preservation - Admin Operations on Other Users

_For any_ admin deletion of users other than themselves, the fixed system SHALL produce the same result as the original system, preserving admin user management capabilities.

**Validates: Requirements 3.5, 3.6, 3.7, 3.8**

Property 11: Preservation - Image Upload Functionality

_For any_ valid image upload under 5MB with correct mime type and dimensions, the fixed system SHALL produce the same result as the original system, preserving image upload functionality.

**Validates: Requirements 3.9, 3.10, 3.11, 3.12**

Property 12: Preservation - Search Functionality

_For any_ search request within rate limits, the fixed system SHALL produce the same results as the original system, preserving search and filtering capabilities.

**Validates: Requirements 3.17, 3.18, 3.19, 3.20**

Property 13: Preservation - Content Display

_For any_ content display after sanitization, the fixed system SHALL render formatted text correctly without breaking layout, preserving user experience while removing malicious content.

**Validates: Requirements 3.21, 3.22, 3.23, 3.24**


## Fix Implementation

### Changes Required

Implementation organized into 8 logical units, each with specific files and changes following Laravel best practices.

---

### 1. Mass Assignment Protection

**Files**: 
- `app/Models/Tribute.php` (modify)
- `app/Http/Controllers/Api/V1/TributeController.php` (verify)

**Specific Changes**:

1.1 **Configure Strict $fillable in Tribute Model**:
   ```php
   // app/Models/Tribute.php
   protected $fillable = [
       'memorial_id',
       'author_name',
       'author_email',
       'message',
   ];
   
   // Ensure $guarded is not set to empty array
   // Remove: protected $guarded = [];
   ```

1.2 **Verify Controller Uses Validated Data**:
   ```php
   // app/Http/Controllers/Api/V1/TributeController.php
   public function store(StoreTributeRequest $request, Memorial $memorial)
   {
       // Use validated() to ensure only validated fields are used
       $tribute = $memorial->tributes()->create([
           'memorial_id' => $memorial->id,
           'author_name' => $request->validated('author_name'),
           'author_email' => $request->validated('author_email'),
           'message' => $request->validated('message'),
           // is_approved defaults to false (database default)
       ]);
       
       return new TributeResource($tribute);
   }
   ```

1.3 **Add Test for Mass Assignment Protection**:
   - Verify that sending `is_approved: true` in request doesn't set the field
   - Verify that sending `memorial_id: 999` doesn't override the correct memorial_id

---

### 2. Admin Self-Deletion Prevention

**Files**:
- `app/Policies/UserPolicy.php` (create or modify)
- `app/Http/Controllers/Api/V1/AdminUserController.php` (modify)
- `app/Providers/AuthServiceProvider.php` (verify policy registration)

**Specific Changes**:

2.1 **Create/Modify UserPolicy with Self-Deletion Check**:
   ```php
   // app/Policies/UserPolicy.php
   public function delete(User $authUser, User $targetUser): bool
   {
       // Prevent admin from deleting themselves
       if ($authUser->id === $targetUser->id) {
           return false;
       }
       
       // Only admins can delete users
       return $authUser->hasRole('admin');
   }
   
   public function forceDelete(User $authUser, User $targetUser): bool
   {
       // Same logic for force delete
       if ($authUser->id === $targetUser->id) {
           return false;
       }
       
       return $authUser->hasRole('admin');
   }
   ```

2.2 **Modify AdminUserController to Use Authorization**:
   ```php
   // app/Http/Controllers/Api/V1/AdminUserController.php
   public function deleteUser(User $user)
   {
       // Use policy authorization instead of manual check
       $this->authorize('delete', $user);
       
       $user->delete();
       
       return response()->json([
           'message' => 'User deleted successfully'
       ]);
   }
   ```

2.3 **Register Policy in AuthServiceProvider**:
   ```php
   // app/Providers/AuthServiceProvider.php
   protected $policies = [
       User::class => UserPolicy::class,
       // ... other policies
   ];
   ```

2.4 **Add Custom Error Message**:
   - When authorization fails, Laravel returns 403
   - Optionally customize message in policy or exception handler

---

### 3. Input Sanitization for XSS Prevention

**Files**:
- `app/Services/SanitizationService.php` (create new)
- `app/Http/Controllers/Api/V1/MemorialController.php` (modify)
- `app/Http/Controllers/Api/V1/TributeController.php` (modify)
- `app/Http/Controllers/ContactController.php` (modify)

**Specific Changes**:

3.1 **Create SanitizationService**:
   ```php
   // app/Services/SanitizationService.php
   namespace App\Services;
   
   class SanitizationService
   {
       /**
        * Sanitize HTML content, removing dangerous tags while preserving safe formatting
        */
       public function sanitizeHtml(?string $content): ?string
       {
           if (empty($content)) {
               return $content;
           }
           
           // Allow safe tags: p, br, strong, em, ul, ol, li
           $allowedTags = '<p><br><strong><em><b><i><ul><ol><li>';
           
           // Strip dangerous tags
           $sanitized = strip_tags($content, $allowedTags);
           
           // Remove event handlers and javascript: protocols
           $sanitized = preg_replace('/on\w+\s*=\s*["\'].*?["\']/i', '', $sanitized);
           $sanitized = preg_replace('/javascript:/i', '', $sanitized);
           
           return $sanitized;
       }
       
       /**
        * Sanitize plain text, removing all HTML
        */
       public function sanitizePlainText(?string $content): ?string
       {
           if (empty($content)) {
               return $content;
           }
           
           return strip_tags($content);
       }
   }
   ```

3.2 **Modify MemorialController to Sanitize Biography**:
   ```php
   // app/Http/Controllers/Api/V1/MemorialController.php
   public function __construct(
       private SanitizationService $sanitizationService
   ) {}
   
   public function store(StoreMemorialRequest $request)
   {
       $memorial = Memorial::create([
           'user_id' => auth()->id(),
           'name' => $request->name,
           'biography' => $this->sanitizationService->sanitizeHtml($request->biography),
           'date_of_birth' => $request->date_of_birth,
           'date_of_death' => $request->date_of_death,
           // ... other fields
       ]);
       
       return new MemorialResource($memorial);
   }
   
   public function update(UpdateMemorialRequest $request, Memorial $memorial)
   {
       $this->authorize('update', $memorial);
       
       $memorial->update([
           'name' => $request->name,
           'biography' => $this->sanitizationService->sanitizeHtml($request->biography),
           // ... other fields
       ]);
       
       return new MemorialResource($memorial);
   }
   ```

3.3 **Modify TributeController to Sanitize Message**:
   ```php
   // app/Http/Controllers/Api/V1/TributeController.php
   public function __construct(
       private SanitizationService $sanitizationService
   ) {}
   
   public function store(StoreTributeRequest $request, Memorial $memorial)
   {
       $tribute = $memorial->tributes()->create([
           'memorial_id' => $memorial->id,
           'author_name' => $request->author_name,
           'author_email' => $request->author_email,
           'message' => $this->sanitizationService->sanitizeHtml($request->message),
       ]);
       
       return new TributeResource($tribute);
   }
   ```

3.4 **Modify ContactController to Sanitize Subject**:
   ```php
   // app/Http/Controllers/ContactController.php
   public function __construct(
       private SanitizationService $sanitizationService
   ) {}
   
   public function store(StoreContactRequest $request)
   {
       $sanitizedData = [
           'name' => $this->sanitizationService->sanitizePlainText($request->name),
           'email' => $request->email, // Email already validated
           'subject' => $this->sanitizationService->sanitizePlainText($request->subject),
           'message' => $this->sanitizationService->sanitizeHtml($request->message),
       ];
       
       // Send email with sanitized data
       Mail::to(config('mail.contact_email'))->send(new ContactFormMail($sanitizedData));
       
       return response()->json(['message' => 'Contact form submitted successfully']);
   }
   ```

---

### 4. Image Upload Size Limits

**Files**:
- `app/Http/Requests/StoreImageRequest.php` (modify)

**Specific Changes**:

4.1 **Add Max Size Validation Rule**:
   ```php
   // app/Http/Requests/StoreImageRequest.php
   public function rules(): array
   {
       return [
           'image' => [
               'required',
               'image',
               'mimes:jpeg,jpg,png,gif,webp',
               'max:5120', // 5MB = 5120 KB
               'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
           ],
       ];
   }
   
   public function messages(): array
   {
       return [
           'image.max' => 'The image must not exceed 5MB in size.',
           'image.dimensions' => 'The image dimensions must be between 100x100 and 4000x4000 pixels.',
       ];
   }
   ```

4.2 **Verify ImageService Handles Validated Files**:
   ```php
   // app/Services/ImageService.php
   public function upload(UploadedFile $file, string $directory = 'memorials'): string
   {
       // File is already validated by StoreImageRequest
       // Additional size check as defensive programming
       if ($file->getSize() > 5 * 1024 * 1024) {
           throw new \InvalidArgumentException('Image file size exceeds 5MB limit');
       }
       
       $filename = uniqid() . '.' . $file->getClientOriginalExtension();
       $path = $file->storeAs($directory, $filename, 'public');
       
       return $path;
   }
   ```

---

### 5. Admin Tribute Deletion Authorization

**Files**:
- `app/Policies/TributePolicy.php` (modify)

**Specific Changes**:

5.1 **Update TributePolicy::delete() to Include Admin Check**:
   ```php
   // app/Policies/TributePolicy.php
   public function delete(User $user, Tribute $tribute): bool
   {
       // Allow if user is admin
       if ($user->hasRole('admin')) {
           return true;
       }
       
       // Allow if user is memorial owner
       return $user->id === $tribute->memorial->user_id;
   }
   ```

5.2 **Verify TributeController Uses Authorization**:
   ```php
   // app/Http/Controllers/Api/V1/TributeController.php
   public function destroy(Tribute $tribute)
   {
       $this->authorize('delete', $tribute);
       
       $tribute->delete();
       
       return response()->json(null, 204);
   }
   ```

5.3 **Keep Admin-Specific Endpoint for Consistency** (optional):
   - Existing `/api/v1/admin/tributes/{tribute}` can remain for admin dashboard
   - Standard `/api/v1/tributes/{tribute}` now also works for admins


---

### 6. Password Reset Rate Limiters

**Files**:
- `app/Providers/RouteServiceProvider.php` (modify)
- `routes/api.php` (verify middleware applied)

**Specific Changes**:

6.1 **Define Custom Rate Limiters in RouteServiceProvider**:
   ```php
   // app/Providers/RouteServiceProvider.php
   use Illuminate\Cache\RateLimiting\Limit;
   use Illuminate\Support\Facades\RateLimiter;
   
   protected function configureRateLimiting(): void
   {
       // Existing API rate limiter
       RateLimiter::for('api', function (Request $request) {
           return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
       });
       
       // Password reset link request limiter
       RateLimiter::for('password-reset-link', function (Request $request) {
           return Limit::perHour(3)->by($request->input('email') ?: $request->ip())
               ->response(function () {
                   return response()->json([
                       'message' => 'Too many password reset attempts. Please try again later.'
                   ], 429);
               });
       });
       
       // Password reset submission limiter
       RateLimiter::for('password-reset-submit', function (Request $request) {
           return Limit::perHour(5)->by($request->input('email') ?: $request->ip())
               ->response(function () {
                   return response()->json([
                       'message' => 'Too many password reset submissions. Please try again later.'
                   ], 429);
               });
       });
   }
   ```

6.2 **Verify Routes Apply Correct Middleware**:
   ```php
   // routes/api.php
   Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
       ->middleware('throttle:password-reset-link');
   
   Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
       ->middleware('throttle:password-reset-submit');
   ```

---

### 7. Logout Cookie Clearing

**Files**:
- `app/Http/Controllers/Api/V1/AuthController.php` (modify)

**Specific Changes**:

7.1 **Modify Logout Method to Clear Cookie**:
   ```php
   // app/Http/Controllers/Api/V1/AuthController.php
   public function logout(Request $request)
   {
       // Delete token from database
       $request->user()->currentAccessToken()->delete();
       
       // Clear auth_token cookie by setting expiration to past
       return response()->json([
           'message' => 'Logged out successfully'
       ])->cookie(
           'auth_token',           // name
           '',                     // value (empty)
           -1,                     // minutes (negative = expired)
           '/',                    // path
           null,                   // domain
           true,                   // secure
           true,                   // httpOnly
           false,                  // raw
           'strict'                // sameSite
       );
   }
   ```

7.2 **Alternative: Use Cookie Facade**:
   ```php
   use Illuminate\Support\Facades\Cookie;
   
   public function logout(Request $request)
   {
       $request->user()->currentAccessToken()->delete();
       
       // Forget cookie
       Cookie::queue(Cookie::forget('auth_token'));
       
       return response()->json([
           'message' => 'Logged out successfully'
       ]);
   }
   ```

---

### 8. Search Rate Limiting

**Files**:
- `routes/api.php` (modify)
- `app/Providers/RouteServiceProvider.php` (optional - add custom limiter)

**Specific Changes**:

8.1 **Apply Throttle Middleware to Search Route**:
   ```php
   // routes/api.php
   Route::get('/search', [SearchController::class, 'index'])
       ->middleware('throttle:60,1'); // 60 requests per 1 minute
   ```

8.2 **Optional: Create Custom Search Rate Limiter**:
   ```php
   // app/Providers/RouteServiceProvider.php
   protected function configureRateLimiting(): void
   {
       // ... existing limiters
       
       // Search endpoint limiter
       RateLimiter::for('search', function (Request $request) {
           return Limit::perMinute(60)->by($request->ip())
               ->response(function () {
                   return response()->json([
                       'message' => 'Too many search requests. Please slow down.'
                   ], 429);
               });
       });
   }
   
   // Then in routes/api.php:
   Route::get('/search', [SearchController::class, 'index'])
       ->middleware('throttle:search');
   ```

8.3 **Add Rate Limit Headers to Response** (automatic with throttle middleware):
   - `X-RateLimit-Limit`: Maximum requests allowed
   - `X-RateLimit-Remaining`: Requests remaining
   - `Retry-After`: Seconds until rate limit resets (when 429 returned)


## Testing Strategy

### Validation Approach

Testing strategy follows a three-phase approach for each of the 8 security categories:

1. **Exploratory Fault Condition Checking**: Demonstrate bugs on UNFIXED code to confirm root cause
2. **Fix Checking**: Verify fixes resolve security vulnerabilities for all buggy inputs
3. **Preservation Checking**: Verify existing functionality remains unchanged for valid inputs

Using combination of unit tests, integration tests, and property-based tests following Laravel testing best practices.

---

### Exploratory Fault Condition Checking

**Goal**: Surface counterexamples demonstrating security vulnerabilities BEFORE implementing fixes. Confirm root cause analysis.

#### Test Plan by Category:

**1. Mass Assignment Vulnerability**

Test Cases (will fail on unfixed code):
- `test_tribute_accepts_is_approved_field()`: POST with `{"is_approved": true}` → expects field accepted (bug), should be ignored
- `test_tribute_accepts_memorial_id_override()`: POST with `{"memorial_id": 999}` → expects override accepted (bug), should use route parameter
- `test_tribute_accepts_timestamp_manipulation()`: POST with `{"created_at": "2020-01-01"}` → expects timestamp accepted (bug), should use current time

Expected Counterexamples:
- Unauthorized fields accepted and stored in database
- Confirms hypothesis: Missing $fillable protection

**2. Admin Self-Deletion**

Test Cases (will fail on unfixed code):
- `test_admin_can_delete_self_via_controller()`: DELETE /api/v1/admin/users/{own-id} → expects 422 (current behavior)
- `test_admin_self_deletion_not_prevented_at_policy_level()`: Check if UserPolicy::delete() prevents self-deletion → expects no policy check (bug)
- `test_last_admin_deletion_allowed()`: Delete last admin account → expects deletion succeeds (bug), should prevent

Expected Counterexamples:
- Controller-level check exists but policy-level check missing
- Alternative deletion paths may bypass controller check
- Confirms hypothesis: Incomplete prevention at authorization layer

**3. XSS via Unsanitized Input**

Test Cases (will fail on unfixed code):
- `test_memorial_biography_stores_script_tag()`: POST memorial with `<script>alert('XSS')</script>` in biography → expects stored as-is (bug)
- `test_tribute_message_stores_img_onerror()`: POST tribute with `<img src=x onerror="alert('XSS')">` → expects stored as-is (bug)
- `test_contact_subject_stores_iframe()`: POST contact with `<iframe src="evil.com">` in subject → expects stored as-is (bug)

Expected Counterexamples:
- Malicious HTML/JavaScript stored without sanitization
- Confirms hypothesis: No input sanitization layer

**4. Large Image Upload**

Test Cases (will fail on unfixed code):
- `test_image_upload_accepts_10mb_file()`: POST with 10MB image → expects upload succeeds (bug), should return 422
- `test_image_upload_accepts_100mb_file()`: POST with 100MB image → expects upload succeeds (bug), should return 422
- `test_image_service_processes_large_file()`: Call ImageService::upload() with large file → expects processing (bug), should throw exception

Expected Counterexamples:
- Large files accepted and processed
- No validation error returned
- Confirms hypothesis: Missing max size validation

**5. Admin Cannot Delete Tribute**

Test Cases (will fail on unfixed code):
- `test_admin_cannot_delete_tribute_via_standard_endpoint()`: DELETE /api/v1/tributes/{id} as admin (not owner) → expects 403 (bug), should return 204
- `test_tribute_policy_does_not_check_admin_role()`: Check TributePolicy::delete() → expects no admin check (bug)

Expected Counterexamples:
- Admin deletion returns 403 Forbidden
- Policy only checks memorial ownership
- Confirms hypothesis: Missing admin authorization in policy

**6. Undefined Rate Limiters**

Test Cases (will fail on unfixed code):
- `test_password_reset_link_limiter_undefined()`: Check RouteServiceProvider → expects 'password-reset-link' limiter not defined (bug)
- `test_password_reset_submit_limiter_undefined()`: Check RouteServiceProvider → expects 'password-reset-submit' limiter not defined (bug)
- `test_forgot_password_accepts_unlimited_requests()`: Send 10 requests in 1 minute → expects all processed or default throttling (bug)

Expected Counterexamples:
- Custom rate limiters referenced but not defined
- Throttling may not work as expected
- Confirms hypothesis: Missing rate limiter definitions

**7. Logout Cookie Persistence**

Test Cases (will fail on unfixed code):
- `test_logout_does_not_clear_cookie()`: POST /api/v1/logout → check response cookies → expects auth_token cookie still present (bug)
- `test_cookie_remains_in_browser_after_logout()`: Simulate logout → check browser storage → expects cookie persists (bug)

Expected Counterexamples:
- Token deleted from database but cookie remains
- Confirms hypothesis: Missing cookie clearing in logout response

**8. Search Without Rate Limiting**

Test Cases (will fail on unfixed code):
- `test_search_endpoint_accepts_unlimited_requests()`: Send 1000 GET /api/v1/search in 1 minute → expects all processed (bug), should throttle after 60
- `test_search_route_has_no_throttle_middleware()`: Check routes/api.php → expects no throttle middleware on search route (bug)

Expected Counterexamples:
- All search requests processed without throttling
- Confirms hypothesis: Missing rate limiting on search endpoint

---

### Fix Checking

**Goal**: Verify that for all inputs where bug conditions hold, the fixed system produces expected secure behavior.

#### Pseudocode by Category:

**1. Mass Assignment Protection**
```
FOR ALL tribute_request WITH unauthorized_fields DO
  tribute_data := {
    author_name: "Test",
    message: "Test",
    is_approved: true,        // Unauthorized
    memorial_id: 999,         // Unauthorized
    created_at: "2020-01-01"  // Unauthorized
  }
  
  tribute := POST /api/v1/memorials/{memorial}/tributes WITH tribute_data
  
  ASSERT tribute.is_approved = false  // Default value, not from request
  ASSERT tribute.memorial_id = memorial.id  // From route, not request
  ASSERT tribute.created_at = current_timestamp  // Auto-generated
  ASSERT tribute.author_name = "Test"  // Allowed field
END FOR
```

**2. Admin Self-Deletion Prevention**
```
FOR ALL admin_user DO
  response := DELETE /api/v1/admin/users/{admin_user.id} AS admin_user
  ASSERT response.status = 403
  ASSERT admin_user.exists() = true  // User not deleted
END FOR

// Test via policy directly
FOR ALL admin_user DO
  can_delete := UserPolicy::delete(admin_user, admin_user)
  ASSERT can_delete = false
END FOR
```

**3. Input Sanitization**
```
FOR ALL malicious_input IN [
  '<script>alert("XSS")</script>',
  '<img src=x onerror="alert(1)">',
  '<iframe src="evil.com"></iframe>',
  'javascript:alert(1)'
] DO
  memorial := POST /api/v1/memorials WITH {biography: malicious_input}
  ASSERT memorial.biography NOT CONTAINS '<script>'
  ASSERT memorial.biography NOT CONTAINS 'onerror='
  ASSERT memorial.biography NOT CONTAINS '<iframe>'
  ASSERT memorial.biography NOT CONTAINS 'javascript:'
  
  tribute := POST /api/v1/memorials/{memorial}/tributes WITH {message: malicious_input}
  ASSERT tribute.message NOT CONTAINS malicious patterns
END FOR
```

**4. Image Size Limits**
```
FOR ALL image_size IN [6MB, 10MB, 50MB, 100MB] DO
  image_file := generateImageFile(image_size)
  response := POST /api/v1/memorials/{memorial}/images WITH {image: image_file}
  
  ASSERT response.status = 422
  ASSERT response.errors.image CONTAINS 'must not exceed 5MB'
END FOR

// Valid size should work
image_file := generateImageFile(4MB)
response := POST /api/v1/memorials/{memorial}/images WITH {image: image_file}
ASSERT response.status = 201
```

**5. Admin Tribute Deletion**
```
FOR ALL tribute WHERE admin.id != tribute.memorial.user_id DO
  response := DELETE /api/v1/tributes/{tribute.id} AS admin
  ASSERT response.status = 204
  ASSERT tribute.exists() = false
END FOR

// Test via policy
FOR ALL tribute DO
  can_delete := TributePolicy::delete(admin, tribute)
  ASSERT can_delete = true
END FOR
```

**6. Password Reset Rate Limiting**
```
// Forgot password limiter
FOR attempt IN 1..5 DO
  response := POST /api/v1/forgot-password WITH {email: "test@example.com"}
  IF attempt <= 3 THEN
    ASSERT response.status = 200
  ELSE
    ASSERT response.status = 429
    ASSERT response.message CONTAINS 'Too many'
  END IF
END FOR

// Reset password limiter
FOR attempt IN 1..7 DO
  response := POST /api/v1/reset-password WITH {email: "test@example.com", token: "...", password: "..."}
  IF attempt <= 5 THEN
    ASSERT response.status IN [200, 422]  // 422 if invalid token
  ELSE
    ASSERT response.status = 429
  END IF
END FOR
```

**7. Logout Cookie Clearing**
```
FOR ALL authenticated_user DO
  response := POST /api/v1/logout AS authenticated_user
  
  ASSERT response.status = 200
  ASSERT authenticated_user.tokens.count() = 0  // Token deleted from DB
  
  // Check cookie in response
  cookie := response.cookies['auth_token']
  ASSERT cookie.value = ''  // Empty value
  ASSERT cookie.expires < now()  // Expired
END FOR
```

**8. Search Rate Limiting**
```
FOR attempt IN 1..70 DO
  response := GET /api/v1/search?q=test
  IF attempt <= 60 THEN
    ASSERT response.status = 200
    ASSERT response.headers['X-RateLimit-Remaining'] = 60 - attempt
  ELSE
    ASSERT response.status = 429
    ASSERT response.headers['Retry-After'] EXISTS
  END IF
END FOR
```


---

### Preservation Checking

**Goal**: Verify that for all inputs where bug conditions do NOT hold, the fixed system produces the same result as the original system.

#### Pseudocode:

**Valid Tribute Creation (Unchanged)**
```
FOR ALL valid_tribute_data WHERE only_allowed_fields(valid_tribute_data) DO
  tribute_original := POST_ORIGINAL /api/v1/memorials/{memorial}/tributes WITH valid_tribute_data
  tribute_fixed := POST_FIXED /api/v1/memorials/{memorial}/tributes WITH valid_tribute_data
  
  ASSERT tribute_original.author_name = tribute_fixed.author_name
  ASSERT tribute_original.message = tribute_fixed.message
  ASSERT tribute_original.memorial_id = tribute_fixed.memorial_id
  // Only difference: fixed version has strict $fillable, but result is same for valid input
END FOR
```

**Memorial Owner Tribute Deletion (Unchanged)**
```
FOR ALL tribute WHERE user.id = tribute.memorial.user_id DO
  response_original := DELETE_ORIGINAL /api/v1/tributes/{tribute.id} AS owner
  response_fixed := DELETE_FIXED /api/v1/tributes/{tribute.id} AS owner
  
  ASSERT response_original.status = response_fixed.status = 204
  ASSERT tribute.exists() = false
END FOR
```

**Admin Deletion of Other Users (Unchanged)**
```
FOR ALL user WHERE admin.id != user.id DO
  response_original := DELETE_ORIGINAL /api/v1/admin/users/{user.id} AS admin
  response_fixed := DELETE_FIXED /api/v1/admin/users/{user.id} AS admin
  
  ASSERT response_original.status = response_fixed.status = 200
  ASSERT user.exists() = false
END FOR
```

**Safe Content Display (Unchanged)**
```
FOR ALL safe_content IN [
  'This is a <strong>bold</strong> statement',
  'Line 1<br>Line 2',
  '<p>Paragraph with <em>emphasis</em></p>'
] DO
  memorial := POST /api/v1/memorials WITH {biography: safe_content}
  
  // Safe HTML preserved after sanitization
  ASSERT memorial.biography CONTAINS '<strong>'
  ASSERT memorial.biography CONTAINS '<br>'
  ASSERT memorial.biography CONTAINS '<em>'
  
  // Layout not broken
  response := GET /memorials/{memorial.slug}
  ASSERT response.status = 200
  ASSERT response.body CONTAINS safe_content formatting
END FOR
```

**Valid Image Uploads (Unchanged)**
```
FOR ALL image_size IN [100KB, 500KB, 1MB, 3MB, 4.9MB] DO
  image_file := generateValidImage(image_size)
  
  response_original := POST_ORIGINAL /api/v1/memorials/{memorial}/images WITH {image: image_file}
  response_fixed := POST_FIXED /api/v1/memorials/{memorial}/images WITH {image: image_file}
  
  ASSERT response_original.status = response_fixed.status = 201
  ASSERT response_original.data.path = response_fixed.data.path
  ASSERT file_exists(response_fixed.data.path) = true
END FOR
```

**Password Reset Within Limits (Unchanged)**
```
FOR attempt IN 1..3 DO
  response_original := POST_ORIGINAL /api/v1/forgot-password WITH {email: "test@example.com"}
  response_fixed := POST_FIXED /api/v1/forgot-password WITH {email: "test@example.com"}
  
  ASSERT response_original.status = response_fixed.status = 200
  ASSERT response_original.message = response_fixed.message
END FOR
```

**Search Within Limits (Unchanged)**
```
FOR attempt IN 1..60 DO
  query := generateRandomSearchQuery()
  
  results_original := GET_ORIGINAL /api/v1/search?q={query}
  results_fixed := GET_FIXED /api/v1/search?q={query}
  
  ASSERT results_original.data = results_fixed.data
  ASSERT results_original.pagination = results_fixed.pagination
END FOR
```

**Authentication Flow (Unchanged)**
```
// Login
response_login := POST /api/v1/login WITH valid_credentials
ASSERT response_login.status = 200
ASSERT response_login.user EXISTS

// Logout
response_logout := POST /api/v1/logout
ASSERT response_logout.status = 200
// Only difference: cookie now properly cleared, but message same
ASSERT response_logout.message = 'Logged out successfully'
```

**Testing Approach**: Property-based testing recommended for preservation checking because:
- Generates many test cases automatically across input domain
- Catches edge cases that manual tests might miss
- Provides strong guarantees that behavior unchanged for valid inputs
- Particularly useful for testing sanitization (safe HTML preserved, malicious removed)

---

### Unit Tests

**Mass Assignment Protection**:
- `test_tribute_model_has_strict_fillable()`
- `test_tribute_ignores_is_approved_field()`
- `test_tribute_ignores_memorial_id_override()`
- `test_tribute_ignores_timestamp_manipulation()`
- `test_tribute_accepts_only_allowed_fields()`

**Admin Self-Deletion**:
- `test_user_policy_prevents_self_deletion()`
- `test_admin_cannot_delete_own_account()`
- `test_admin_can_delete_other_users()`
- `test_last_admin_cannot_be_deleted()`
- `test_non_admin_cannot_delete_users()`

**Input Sanitization**:
- `test_sanitization_service_removes_script_tags()`
- `test_sanitization_service_removes_event_handlers()`
- `test_sanitization_service_preserves_safe_tags()`
- `test_memorial_biography_sanitized_on_create()`
- `test_memorial_biography_sanitized_on_update()`
- `test_tribute_message_sanitized()`
- `test_contact_subject_sanitized()`

**Image Size Limits**:
- `test_image_request_validates_max_size()`
- `test_image_upload_rejects_6mb_file()`
- `test_image_upload_accepts_5mb_file()`
- `test_image_service_throws_on_oversized_file()`
- `test_validation_error_message_clear()`

**Admin Tribute Deletion**:
- `test_tribute_policy_allows_admin_deletion()`
- `test_admin_can_delete_any_tribute()`
- `test_owner_can_still_delete_tribute()`
- `test_non_owner_non_admin_cannot_delete()`

**Password Reset Rate Limiting**:
- `test_forgot_password_limiter_defined()`
- `test_reset_password_limiter_defined()`
- `test_forgot_password_throttled_after_3_attempts()`
- `test_reset_password_throttled_after_5_attempts()`
- `test_rate_limit_resets_after_hour()`

**Logout Cookie Clearing**:
- `test_logout_deletes_token_from_database()`
- `test_logout_clears_auth_cookie()`
- `test_logout_cookie_has_past_expiration()`
- `test_logout_response_includes_success_message()`

**Search Rate Limiting**:
- `test_search_route_has_throttle_middleware()`
- `test_search_throttled_after_60_requests()`
- `test_search_returns_rate_limit_headers()`
- `test_search_returns_retry_after_on_429()`

---

### Property-Based Tests

**Mass Assignment Properties**:
- Generate random tribute data with mix of allowed and unauthorized fields
- Verify only allowed fields stored in database
- Verify unauthorized fields always ignored regardless of values

**Sanitization Properties**:
- Generate random HTML content with varying malicious patterns
- Verify all dangerous patterns removed
- Verify safe formatting preserved
- Test with various XSS payloads from OWASP XSS Filter Evasion Cheat Sheet

**Image Upload Properties**:
- Generate images of random sizes from 1KB to 200MB
- Verify all images > 5MB rejected
- Verify all images ≤ 5MB accepted (if valid format)

**Authorization Properties**:
- Generate random user-resource combinations
- Verify admin self-deletion always prevented
- Verify admin can delete all other users
- Verify admin can delete all tributes

**Rate Limiting Properties**:
- Generate random request sequences with varying frequencies
- Verify throttling activates at correct thresholds
- Verify rate limits reset after time window

**Preservation Properties**:
- Generate random valid inputs
- Verify results identical between original and fixed code
- Test across all CRUD operations

---

### Integration Tests

**End-to-End Security Flow**:
- Test complete memorial creation with biography sanitization
- Test tribute submission with message sanitization and mass assignment protection
- Test admin moderation flow: login → view inappropriate tribute → delete via standard endpoint
- Test image upload flow with size validation

**Cross-Feature Integration**:
- Test sanitization + mass assignment protection together
- Test admin authorization + rate limiting together
- Test logout cookie clearing + authentication flow

**Regression Testing**:
- Run full test suite on valid operations
- Verify no existing functionality broken
- Test all API endpoints return expected responses
- Verify database integrity maintained

**Performance Testing**:
- Verify sanitization doesn't significantly impact response times
- Test rate limiting doesn't affect legitimate users
- Verify image size validation happens before expensive processing

