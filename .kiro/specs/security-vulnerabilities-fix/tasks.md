# Implementation Plan - Security Vulnerabilities Fix

## Phase 1: Bug Condition Exploration Tests

- [x] 1. Write bug condition exploration tests for all 10 security categories
  - **Property 1: Fault Condition** - Security Vulnerabilities Exploration
  - **CRITICAL**: These tests MUST FAIL on unfixed code - failures confirm the bugs exist
  - **DO NOT attempt to fix the tests or the code when they fail**
  - **NOTE**: These tests encode the expected behavior - they will validate the fixes when they pass after implementation
  - **GOAL**: Surface counterexamples that demonstrate each security vulnerability exists
  
  - [x] 1.1 Test private memorial IDOR vulnerability
    - Test that unauthenticated user can access private memorial via GET /api/v1/memorials/{private-slug}
    - Test that non-owner authenticated user can access other user's private memorial
    - Test that private memorials appear in index for unauthenticated users
    - Run on UNFIXED code - expect SUCCESS (bug exists)
    - Document counterexamples: specific memorial slugs that are accessible without authorization
    - _Requirements: 1.1, 1.2, 1.3_
  
  - [x] 1.2 Test email address exposure vulnerability
    - Test that GET /api/v1/memorials/{slug} returns authorEmail in tributes array
    - Test that GET /api/v1/memorials/{memorial}/tributes exposes author_email to non-owners
    - Run on UNFIXED code - expect email addresses in responses (bug exists)
    - Document counterexamples: specific API responses containing email addresses
    - _Requirements: 2.1, 2.2, 2.3_
  
  - [x] 1.3 Test authentication rate limiting absence
    - Send 100 POST requests to /api/v1/login in 1 minute
    - Send 50 POST requests to /api/v1/register in 1 minute
    - Run on UNFIXED code - expect all requests processed (bug exists)
    - Document counterexamples: number of requests successfully processed without throttling
    - _Requirements: 3.1, 3.2, 3.3_
  
  - [x] 1.4 Test insecure token storage
    - Test that POST /api/v1/login returns token in JSON response body
    - Inspect login.blade.php for localStorage.setItem('auth_token') usage
    - Inspect register.blade.php for localStorage.setItem('auth_token') usage
    - Run on UNFIXED code - expect token in response and localStorage usage (bug exists)
    - Document counterexamples: exact response structure and frontend code lines
    - _Requirements: 4.1, 4.2, 4.3_
  
  - [x] 1.5 Test tribute spam vulnerability
    - Send 1000 POST requests to /api/v1/memorials/{memorial}/tributes in 1 minute
    - Test tribute submission without honeypot validation
    - Test tribute submission without timestamp validation
    - Run on UNFIXED code - expect all requests processed (bug exists)
    - Document counterexamples: number of spam tributes successfully created
    - _Requirements: 5.1, 5.2, 5.3_
  
  - [x] 1.6 Test missing security headers
    - Test that HTTP responses lack Content-Security-Policy header
    - Test that HTTP responses lack Strict-Transport-Security header
    - Test that HTTP responses lack X-Frame-Options header
    - Test that HTTP responses lack X-Content-Type-Options header
    - Run on UNFIXED code - expect missing headers (bug exists)
    - Document counterexamples: actual response headers received
    - _Requirements: 6.1, 6.2, 6.3, 6.4_
  
  - [x] 1.7 Test perpetual token validity
    - Test that config/sanctum.php has 'expiration' => null
    - Create a token and verify it never expires
    - Run on UNFIXED code - expect perpetual validity (bug exists)
    - Document counterexamples: token age and continued validity
    - _Requirements: 7.1, 7.2, 7.3_
  
  - [x] 1.8 Test permissive CORS configuration
    - Test that config/cors.php has 'allowed_methods' => ['*']
    - Test that config/cors.php has 'allowed_headers' => ['*']
    - Test wildcard usage with supports_credentials=true
    - Run on UNFIXED code - expect wildcard configuration (bug exists)
    - Document counterexamples: actual CORS configuration values
    - _Requirements: 8.1, 8.2, 8.3_
  
  - [x] 1.9 Test contact form vulnerabilities
    - Send 500 POST requests to /contact in 1 minute
    - Inspect ContactController.php for PII logging (email, name in Log::info)
    - Check storage/logs/laravel.log for PII data
    - Run on UNFIXED code - expect no rate limiting and PII in logs (bug exists)
    - Document counterexamples: number of requests processed and PII found in logs
    - _Requirements: 9.1, 9.2, 9.3_
  
  - [x] 1.10 Test HTTPS enforcement absence
    - Test HTTP request in production environment (APP_ENV=production)
    - Verify no automatic HTTPS redirect at application level
    - Check config/app.php for force_https setting
    - Run on UNFIXED code - expect HTTP allowed in production (bug exists)
    - Document counterexamples: HTTP requests successfully processed in production mode
    - _Requirements: 10.1, 10.2, 10.3_

## Phase 2: Preservation Property Tests

- [x] 2. Write preservation property tests (BEFORE implementing fixes)
  - **Property 2: Preservation** - Existing Functionality Preservation
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for non-buggy inputs
  - Write property-based tests capturing observed behavior patterns
  - Property-based testing generates many test cases for stronger guarantees
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  
  - [x] 2.1 Test public memorial access preservation
    - Observe: GET /api/v1/memorials returns public memorials (is_public=true)
    - Observe: GET /api/v1/memorials/{public-slug} returns full data for public memorials
    - Write property: for all public memorials, access is unrestricted
    - Verify test passes on UNFIXED code
    - _Requirements: 3.8, 3.9, 3.10_
  
  - [x] 2.2 Test authenticated owner operations preservation
    - Observe: Owner can view their own private memorial
    - Observe: Owner can update their memorial (PUT /api/v1/memorials/{id})
    - Observe: Owner can delete their memorial (DELETE /api/v1/memorials/{id})
    - Observe: Owner sees email addresses in their memorial's tributes
    - Write property: for all owner operations on owned memorials, full access granted
    - Verify test passes on UNFIXED code
    - _Requirements: 3.4, 3.5, 3.6, 2.8_
  
  - [x] 2.3 Test valid authentication preservation
    - Observe: Valid login credentials return user object and token
    - Observe: Valid registration creates account and sends welcome email
    - Observe: GET /api/v1/me returns user data with profile and roles
    - Write property: for all valid credentials, authentication succeeds
    - Verify test passes on UNFIXED code
    - _Requirements: 3.1, 3.2, 3.3_
  
  - [x] 2.4 Test tribute functionality preservation
    - Observe: Valid tribute submission for public memorial creates tribute record
    - Observe: Owner can delete tributes from their memorial
    - Observe: Admin can delete any tribute
    - Write property: for all valid tribute operations within rate limits, functionality works
    - Verify test passes on UNFIXED code
    - _Requirements: 3.11, 3.12, 3.13_
  
  - [x] 2.5 Test search and filtering preservation
    - Observe: Search query returns filtered public memorials
    - Observe: Country/place filters work correctly
    - Observe: Sort operations apply correctly
    - Write property: for all search/filter operations, results match query
    - Verify test passes on UNFIXED code
    - _Requirements: 3.17, 3.18, 3.19_
  
  - [x] 2.6 Test admin functionality preservation
    - Observe: Admin can access all memorials (public and private)
    - Observe: Admin can manage users (role changes, deletion)
    - Observe: Admin can update settings (hero settings, feature toggles)
    - Write property: for all admin operations, full privileges granted
    - Verify test passes on UNFIXED code
    - _Requirements: 3.7, 3.25, 3.26, 3.27_
  
  - [x] 2.7 Test media upload preservation
    - Observe: Authenticated user can upload images for their memorial
    - Observe: User can add YouTube videos with validation
    - Observe: User can reorder images/videos (display_order)
    - Write property: for all media operations by owners, upload and management works
    - Verify test passes on UNFIXED code
    - _Requirements: 3.14, 3.15, 3.16_
  
  - [x] 2.8 Test location API preservation
    - Observe: GET /api/v1/locations/countries returns country list
    - Observe: GET /api/v1/locations/countries/{country}/places returns places
    - Write property: for all location queries, data is returned
    - Verify test passes on UNFIXED code
    - _Requirements: 3.20, 3.21_
  
  - [x] 2.9 Test password reset preservation
    - Observe: Password reset request sends email with token
    - Observe: Valid reset token allows password change
    - Observe: Password reset revokes old tokens
    - Write property: for all valid reset flows, password reset succeeds
    - Verify test passes on UNFIXED code
    - _Requirements: 3.22, 3.23, 3.24_
  
  - [x] 2.10 Test contact form submission preservation
    - Observe: Valid contact form submission processes successfully
    - Observe: Invalid form data returns validation errors
    - Write property: for all valid contact submissions within rate limits, submission succeeds
    - Verify test passes on UNFIXED code
    - _Requirements: 3.28, 3.29_
  
  - [x] 2.11 Test CORS for legitimate requests preservation
    - Observe: Frontend from FRONTEND_URL origin can make API requests
    - Observe: Authenticated requests with credentials work
    - Write property: for all legitimate origin requests, CORS allows access
    - Verify test passes on UNFIXED code
    - _Requirements: 3.30, 3.31_
  
  - [x] 2.12 Test session and CSRF preservation
    - Observe: Web routes maintain session state
    - Observe: CSRF token validation works on web forms
    - Write property: for all web route operations, session and CSRF protection active
    - Verify test passes on UNFIXED code
    - _Requirements: 3.32, 3.33_

## Phase 3: Implementation

- [x] 3. Fix 1: Private Memorial Access Control
  
  - [x] 3.1 Create MemorialPolicy
    - Create app/Policies/MemorialPolicy.php
    - Implement view(User $user = null, Memorial $memorial) method
    - Logic: return $memorial->is_public || ($user && $user->id === $memorial->user_id) || ($user && $user->hasRole('admin'))
    - Implement viewAny(User $user = null) for index filtering
    - _Bug_Condition: isBugCondition where memorial.is_public=false AND NOT isAuthorized(user, memorial)_
    - _Expected_Behavior: Return 404 or 403 for unauthorized access to private memorials_
    - _Preservation: Public memorials remain accessible, owner access unchanged, admin privileges maintained_
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  
  - [x] 3.2 Modify MemorialController for authorization
    - Update MemorialController::index() to filter private memorials for unauthenticated users
    - Add query scope: $query->where('is_public', true) for guests
    - For authenticated: $query->where('is_public', true)->orWhere('user_id', auth()->id())
    - Add admin scope: ->when(auth()->check() && auth()->user()->hasRole('admin'), fn($q) => $q->withoutGlobalScope('public'))
    - Update MemorialController::show() to add $this->authorize('view', $memorial)
    - _Bug_Condition: Requests to private memorials without authorization_
    - _Expected_Behavior: Authorization check before returning data_
    - _Preservation: Public memorial access unchanged, owner operations unchanged_
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  
  - [x] 3.3 Register MemorialPolicy
    - Update app/Providers/AuthServiceProvider.php
    - Add Gate::policy(Memorial::class, MemorialPolicy::class) in boot() method
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  
  - [x] 3.4 Verify private memorial access control tests now pass
    - **Property 1: Expected Behavior** - Private Memorial Access Control
    - **IMPORTANT**: Re-run the SAME tests from task 1.1 - do NOT write new tests
    - Run tests: unauthenticated access returns 404/403, non-owner returns 403, owner access works
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  
  - [x] 3.5 Verify preservation tests still pass
    - **Property 2: Preservation** - Public Memorial Access
    - **IMPORTANT**: Re-run preservation tests from task 2.1, 2.2, 2.6
    - Confirm public memorial access unchanged
    - Confirm owner operations unchanged
    - Confirm admin privileges maintained
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 4. Fix 2: Email Address Protection
  
  - [x] 4.1 Modify TributeResource to hide emails
    - Update app/Http/Resources/TributeResource.php
    - Modify toArray() method to conditionally include authorEmail
    - Add: 'authorEmail' => $this->when($request->user() && ($request->user()->id === $this->memorial->user_id || $request->user()->hasRole('admin')), $this->author_email)
    - _Bug_Condition: API responses expose authorEmail to non-owners_
    - _Expected_Behavior: Email only visible to memorial owner and admin_
    - _Preservation: Owner sees emails, admin sees emails, tribute functionality unchanged_
    - _Requirements: 2.5, 2.6, 2.7, 2.8_
  
  - [x] 4.2 Verify MemorialResource uses TributeResource
    - Check app/Http/Resources/MemorialResource.php
    - Ensure tributes use: 'tributes' => TributeResource::collection($this->whenLoaded('tributes'))
    - _Requirements: 2.5, 2.6_
  
  - [x] 4.3 Add authorization to TributeController
    - Update app/Http/Controllers/Api/V1/TributeController.php
    - Add $this->authorize('view', $memorial) in index() method
    - Prevents access to tributes of private memorials
    - _Bug_Condition: Tribute API accessible for private memorials_
    - _Expected_Behavior: Authorization check before returning tributes_
    - _Requirements: 2.6, 2.7_
  
  - [x] 4.4 Verify email protection tests now pass
    - **Property 1: Expected Behavior** - Email Address Protection
    - **IMPORTANT**: Re-run the SAME tests from task 1.2
    - Run tests: email not in responses for non-owners, email visible to owners/admins
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.5, 2.6, 2.7, 2.8_
  
  - [x] 4.5 Verify preservation tests still pass
    - **Property 2: Preservation** - Owner Email Visibility
    - **IMPORTANT**: Re-run preservation tests from task 2.2, 2.4
    - Confirm owner sees emails in their memorial's tributes
    - Confirm tribute functionality unchanged
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 5. Fix 3: Authentication Rate Limiting
  
  - [x] 5.1 Apply throttle middleware to auth routes
    - Update routes/api.php
    - Add ->middleware('throttle:5,1') to POST /login route
    - Add ->middleware('throttle:3,1') to POST /register route
    - _Bug_Condition: Unlimited login/register attempts from same IP_
    - _Expected_Behavior: 429 Too Many Requests after threshold_
    - _Preservation: Valid authentication attempts within limits work normally_
    - _Requirements: 2.9, 2.10, 2.11_
  
  - [x] 5.2 Verify rate limiting tests now pass
    - **Property 1: Expected Behavior** - Authentication Rate Limiting
    - **IMPORTANT**: Re-run the SAME tests from task 1.3
    - Run tests: 6th login attempt returns 429, 4th register attempt returns 429
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.9, 2.10, 2.11_
  
  - [x] 5.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Authentication
    - **IMPORTANT**: Re-run preservation tests from task 2.3
    - Confirm valid login/register within limits works
    - Confirm /api/v1/me endpoint works
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 6. Fix 4: Secure Token Storage (HttpOnly Cookies)
  
  - [x] 6.1 Configure Sanctum for cookie authentication
    - Update config/sanctum.php
    - Set 'stateful' domains to include localhost, 127.0.0.1, and APP_URL host
    - _Requirements: 2.12, 2.13, 2.14_
  
  - [x] 6.2 Modify AuthController to use cookies
    - Update app/Http/Controllers/Api/V1/AuthController.php
    - Modify login() method to return token in httpOnly cookie instead of JSON
    - Use: return response()->json(['user' => $user])->cookie('auth_token', $token, 60*24*60, '/', null, true, true, false, 'strict')
    - Modify register() method with same cookie approach
    - _Bug_Condition: Token returned in JSON response body, stored in localStorage_
    - _Expected_Behavior: Token in httpOnly, secure, sameSite=strict cookie_
    - _Preservation: Authentication flow works, user data returned_
    - _Requirements: 2.12, 2.13, 2.14_
  
  - [x] 6.3 Update frontend to remove localStorage usage
    - Update resources/views/auth/login.blade.php
    - Remove localStorage.setItem('auth_token', response.token)
    - Token will be in cookie automatically
    - Update resources/views/auth/register.blade.php with same change
    - Add axios.defaults.withCredentials = true for cookie sending
    - _Bug_Condition: Frontend stores token in localStorage_
    - _Expected_Behavior: Token in httpOnly cookie, inaccessible to JavaScript_
    - _Requirements: 2.12, 2.13, 2.14_
  
  - [x] 6.4 Configure CORS for credentials
    - Update config/cors.php
    - Set 'supports_credentials' => true
    - _Requirements: 2.12, 2.14_
  
  - [x] 6.5 Verify secure token storage tests now pass
    - **Property 1: Expected Behavior** - Secure Token Storage
    - **IMPORTANT**: Re-run the SAME tests from task 1.4
    - Run tests: token not in JSON response, token in httpOnly cookie, localStorage not used
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.12, 2.13, 2.14_
  
  - [x] 6.6 Verify preservation tests still pass
    - **Property 2: Preservation** - Authentication Flow
    - **IMPORTANT**: Re-run preservation tests from task 2.3, 2.11
    - Confirm login/register still works with cookie-based auth
    - Confirm CORS for legitimate requests works
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 7. Fix 5: Tribute Anti-Spam Protection
  
  - [x] 7.1 Apply rate limiting to tribute route
    - Update routes/api.php
    - Add ->middleware('throttle:3,10') to POST /memorials/{memorial}/tributes route
    - _Bug_Condition: Unlimited tribute submissions from same IP_
    - _Expected_Behavior: 429 Too Many Requests after 3 requests in 10 minutes_
    - _Requirements: 2.15_
  
  - [x] 7.2 Create StoreTributeRequest with honeypot validation
    - Create app/Http/Requests/StoreTributeRequest.php
    - Add validation rules: 'honeypot' => 'size:0', 'timestamp' => 'required|integer|min:'.(time()-3600)
    - Add standard rules: 'author_name' => 'required|string|max:255', 'message' => 'required|string|max:1000'
    - _Bug_Condition: No honeypot or timestamp validation_
    - _Expected_Behavior: Reject submissions with filled honeypot or old timestamp_
    - _Requirements: 2.16, 2.17_
  
  - [x] 7.3 Add honeypot field to frontend form
    - Update resources/views/memorials/show.blade.php (or relevant tribute form)
    - Add: <input type="text" name="honeypot" style="display:none" tabindex="-1" autocomplete="off">
    - Add: <input type="hidden" name="timestamp" :value="Date.now()">
    - _Requirements: 2.16, 2.17_
  
  - [x] 7.4 Update TributeController to use StoreTributeRequest
    - Update app/Http/Controllers/Api/V1/TributeController.php
    - Change store(Request $request) to store(StoreTributeRequest $request)
    - Validation will automatically reject spam
    - _Bug_Condition: No spam validation in controller_
    - _Expected_Behavior: Automatic validation via form request_
    - _Preservation: Valid tribute submissions within limits work_
    - _Requirements: 2.15, 2.16, 2.17_
  
  - [x] 7.5 Verify tribute anti-spam tests now pass
    - **Property 1: Expected Behavior** - Tribute Anti-Spam Protection
    - **IMPORTANT**: Re-run the SAME tests from task 1.5
    - Run tests: 4th tribute returns 429, filled honeypot rejected, old timestamp rejected
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.15, 2.16, 2.17_
  
  - [x] 7.6 Verify preservation tests still pass
    - **Property 2: Preservation** - Tribute Functionality
    - **IMPORTANT**: Re-run preservation tests from task 2.4
    - Confirm valid tribute submissions within limits work
    - Confirm owner/admin can delete tributes
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 8. Fix 6: Security Headers Middleware
  
  - [x] 8.1 Create SecurityHeaders middleware
    - Create app/Http/Middleware/SecurityHeaders.php
    - Implement handle() method to add headers:
      - Content-Security-Policy: "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'"
      - Strict-Transport-Security: 'max-age=31536000; includeSubDomains'
      - X-Frame-Options: 'DENY'
      - X-Content-Type-Options: 'nosniff'
      - Referrer-Policy: 'strict-origin-when-cross-origin'
      - Permissions-Policy: 'geolocation=(), microphone=(), camera=()'
    - _Bug_Condition: HTTP responses lack security headers_
    - _Expected_Behavior: All responses include security headers_
    - _Preservation: All existing functionality works with headers_
    - _Requirements: 2.18, 2.19, 2.20, 2.21_
  
  - [x] 8.2 Register SecurityHeaders middleware globally
    - Update app/Http/Kernel.php
    - Add \App\Http\Middleware\SecurityHeaders::class to $middleware array
    - _Requirements: 2.18, 2.19, 2.20, 2.21_
  
  - [x] 8.3 Verify security headers tests now pass
    - **Property 1: Expected Behavior** - Security Headers Enforcement
    - **IMPORTANT**: Re-run the SAME tests from task 1.6
    - Run tests: verify CSP, HSTS, X-Frame-Options, X-Content-Type-Options present
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.18, 2.19, 2.20, 2.21_
  
  - [x] 8.4 Verify preservation tests still pass
    - **Property 2: Preservation** - All Functionality
    - **IMPORTANT**: Re-run ALL preservation tests from task 2
    - Confirm all features work with security headers
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 9. Fix 7: Sanctum Token Expiration
  
  - [x] 9.1 Configure token expiration
    - Update config/sanctum.php
    - Set 'expiration' => 60 * 24 * 60 (60 days in minutes)
    - _Bug_Condition: Tokens never expire (expiration = null)_
    - _Expected_Behavior: Tokens expire after 60 days_
    - _Requirements: 2.22, 2.23_
  
  - [x] 9.2 Create token refresh endpoint
    - Update app/Http/Controllers/Api/V1/AuthController.php
    - Add refresh() method: revoke current token, create new token, return in cookie
    - _Bug_Condition: No mechanism to refresh expiring tokens_
    - _Expected_Behavior: Users can refresh tokens before expiration_
    - _Requirements: 2.24_
  
  - [x] 9.3 Add refresh route
    - Update routes/api.php
    - Add: Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum')
    - _Requirements: 2.24_
  
  - [x] 9.4 Verify token expiration tests now pass
    - **Property 1: Expected Behavior** - Token Expiration
    - **IMPORTANT**: Re-run the SAME tests from task 1.7
    - Run tests: verify config has expiration set, expired tokens return 401
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.22, 2.23, 2.24_
  
  - [x] 9.5 Verify preservation tests still pass
    - **Property 2: Preservation** - Authentication
    - **IMPORTANT**: Re-run preservation tests from task 2.3
    - Confirm authentication works with expiring tokens
    - Confirm refresh mechanism works
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 10. Fix 8: Restrictive CORS Policy
  
  - [x] 10.1 Replace wildcard CORS configuration
    - Update config/cors.php
    - Set 'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
    - Set 'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'X-CSRF-TOKEN']
    - Set 'allowed_origins' => [env('FRONTEND_URL'), env('APP_URL')]
    - Set 'allowed_origins_patterns' => []
    - Keep 'supports_credentials' => true
    - _Bug_Condition: Wildcard methods and headers with credentials_
    - _Expected_Behavior: Explicit whitelist of methods and headers_
    - _Preservation: Legitimate frontend requests work_
    - _Requirements: 2.25, 2.26, 2.27_
  
  - [x] 10.2 Verify CORS middleware is applied
    - Check app/Http/Kernel.php
    - Confirm \Fruitcake\Cors\HandleCors::class in 'api' middleware group
    - _Requirements: 2.25, 2.26, 2.27_
  
  - [x] 10.3 Verify CORS restriction tests now pass
    - **Property 1: Expected Behavior** - Restrictive CORS Policy
    - **IMPORTANT**: Re-run the SAME tests from task 1.8
    - Run tests: verify no wildcards, verify explicit method/header lists
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.25, 2.26, 2.27_
  
  - [x] 10.4 Verify preservation tests still pass
    - **Property 2: Preservation** - CORS for Legitimate Requests
    - **IMPORTANT**: Re-run preservation tests from task 2.11
    - Confirm frontend from FRONTEND_URL can make requests
    - Confirm authenticated requests with credentials work
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 11. Fix 9: Contact Form Protection and PII Removal
  
  - [x] 11.1 Apply rate limiting to contact route
    - Update routes/web.php
    - Add ->middleware('throttle:5,60') to POST /contact route
    - _Bug_Condition: Unlimited contact form submissions_
    - _Expected_Behavior: 429 after 5 requests per hour per IP_
    - _Requirements: 2.28_
  
  - [x] 11.2 Remove PII from logging
    - Update app/Http/Controllers/ContactController.php
    - Replace Log::info with PII to: Log::info('Contact form submitted', ['ip_hash' => hash('sha256', $request->ip()), 'timestamp' => now()->toIso8601String(), 'success' => true])
    - Remove email and name from all log statements
    - _Bug_Condition: Email and name logged in plain text_
    - _Expected_Behavior: Only non-PII data logged (IP hash, timestamp)_
    - _Preservation: Email sending functionality unchanged_
    - _Requirements: 2.29, 2.30_
  
  - [x] 11.3 Verify contact form protection tests now pass
    - **Property 1: Expected Behavior** - Contact Form Protection
    - **IMPORTANT**: Re-run the SAME tests from task 1.9
    - Run tests: 6th submission returns 429, logs contain no PII
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.28, 2.29, 2.30_
  
  - [x] 11.4 Verify preservation tests still pass
    - **Property 2: Preservation** - Contact Form Submission
    - **IMPORTANT**: Re-run preservation tests from task 2.10
    - Confirm valid submissions within limits work
    - Confirm validation errors returned correctly
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 12. Fix 10: HTTPS Enforcement
  
  - [x] 12.1 Create ForceHttps middleware
    - Create app/Http/Middleware/ForceHttps.php
    - Implement handle() method: if (!$request->secure() && app()->environment('production')) return redirect()->secure($request->getRequestUri(), 301)
    - _Bug_Condition: HTTP allowed in production environment_
    - _Expected_Behavior: Automatic HTTPS redirect in production_
    - _Requirements: 2.31, 2.32, 2.33_
  
  - [x] 12.2 Register ForceHttps middleware
    - Update app/Http/Kernel.php
    - Add \App\Http\Middleware\ForceHttps::class to $middleware array
    - _Requirements: 2.31, 2.32_
  
  - [x] 12.3 Force HTTPS URLs in production
    - Update app/Providers/AppServiceProvider.php
    - Add in boot(): if ($this->app->environment('production')) { \URL::forceScheme('https'); }
    - _Requirements: 2.31, 2.32_
  
  - [x] 12.4 Verify HTTPS enforcement tests now pass
    - **Property 1: Expected Behavior** - HTTPS Enforcement
    - **IMPORTANT**: Re-run the SAME tests from task 1.10
    - Run tests: HTTP redirects to HTTPS in production, HTTP allowed in local
    - **EXPECTED OUTCOME**: Tests PASS (confirms bug is fixed)
    - _Requirements: 2.31, 2.32, 2.33_
  
  - [x] 12.5 Verify preservation tests still pass
    - **Property 2: Preservation** - All Functionality
    - **IMPORTANT**: Re-run ALL preservation tests from task 2
    - Confirm all features work with HTTPS enforcement
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

## Phase 4: Final Validation

- [x] 13. Checkpoint - Ensure all tests pass
  - Run complete test suite for all 10 security fixes
  - Verify all exploration tests now pass (bugs fixed)
  - Verify all preservation tests still pass (no regressions)
  - Confirm all 10 security vulnerabilities are resolved
  - Ask user if any questions or issues arise
