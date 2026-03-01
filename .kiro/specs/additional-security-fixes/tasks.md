# Implementation Plan

## Phase 1: Bug Condition Exploration Tests (BEFORE Fix)

- [x] 1. Write bug condition exploration tests for all 8 security vulnerabilities
  - **Property 1: Fault Condition** - Security Vulnerabilities Exploration
  - **CRITICAL**: These tests MUST FAIL on unfixed code - failures confirm the bugs exist
  - **DO NOT attempt to fix the tests or the code when they fail**
  - **NOTE**: These tests encode the expected secure behavior - they will validate the fixes when they pass after implementation
  - **GOAL**: Surface counterexamples demonstrating each security vulnerability exists
  - **Scoped PBT Approach**: For deterministic vulnerabilities, scope properties to concrete failing cases to ensure reproducibility
  - Test implementation details from Fault Condition specifications in design document
  - The test assertions should match the Expected Behavior Properties from design
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests FAIL (this is correct - it proves the bugs exist)
  - Document counterexamples found to understand root causes
  - Mark task complete when tests are written, run, and failures are documented
  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 5.1, 5.2, 5.3, 6.1, 6.2, 6.3, 7.1, 7.2, 7.3, 8.1, 8.2, 8.3_

  - [x] 1.1 Test mass assignment vulnerability
    - Test tribute accepts is_approved field when model lacks $fillable protection
    - Test tribute accepts memorial_id override
    - Test tribute accepts timestamp manipulation
    - Expected: Tests FAIL showing unauthorized fields are accepted (confirms bug)
    - Document: Which fields are accepted, what values are stored

  - [x] 1.2 Test admin self-deletion incomplete prevention
    - Test admin can delete self via controller (expects 422 currently)
    - Test admin self-deletion not prevented at policy level
    - Test last admin deletion allowed
    - Expected: Tests show policy-level check missing (confirms bug)
    - Document: Which deletion paths lack authorization checks

  - [x] 1.3 Test XSS via unsanitized input
    - Test memorial biography stores script tags without sanitization
    - Test tribute message stores img onerror handlers
    - Test contact subject stores iframe tags
    - Expected: Tests FAIL showing malicious HTML stored as-is (confirms bug)
    - Document: Which XSS payloads are stored without filtering

  - [x] 1.4 Test large image upload without size limit
    - Test image upload accepts 10MB file
    - Test image upload accepts 100MB file
    - Test ImageService processes large files without validation
    - Expected: Tests FAIL showing large files accepted (confirms bug)
    - Document: Maximum file size that gets accepted


  - [x] 1.5 Test admin cannot delete tribute via standard endpoint
    - Test admin DELETE /api/v1/tributes/{id} returns 403 (not owner)
    - Test TributePolicy::delete() does not check admin role
    - Expected: Tests FAIL showing admin authorization missing (confirms bug)
    - Document: Current policy logic and missing admin check

  - [x] 1.6 Test undefined password reset rate limiters
    - Test password-reset-link limiter not defined in RouteServiceProvider
    - Test password-reset-submit limiter not defined in RouteServiceProvider
    - Test forgot-password accepts unlimited requests
    - Expected: Tests FAIL showing limiters undefined (confirms bug)
    - Document: Which limiters are missing, default behavior observed

  - [x] 1.7 Test logout does not clear auth cookie
    - Test logout response does not include cookie clearing
    - Test auth_token cookie remains after logout
    - Expected: Tests FAIL showing cookie persists (confirms bug)
    - Document: Cookie state before and after logout

  - [x] 1.8 Test search endpoint without rate limiting
    - Test search route has no throttle middleware
    - Test search accepts unlimited requests (1000+ in 1 minute)
    - Expected: Tests FAIL showing no rate limiting (confirms bug)
    - Document: Number of requests processed without throttling

## Phase 2: Preservation Property Tests (BEFORE Fix)

- [x] 2. Write preservation property tests for existing functionality (BEFORE implementing fixes)
  - **Property 2: Preservation** - Existing Functionality Preservation
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for non-buggy inputs
  - Write property-based tests capturing observed behavior patterns from Preservation Requirements
  - Property-based testing generates many test cases for stronger guarantees
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20, 3.21, 3.22, 3.23, 3.24_

  - [x] 2.1 Test valid tribute creation unchanged
    - Observe: Valid tribute with only allowed fields creates successfully
    - Property: For all valid tribute data with only allowed fields, creation succeeds
    - Verify test passes on UNFIXED code
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 2.2 Test memorial owner tribute deletion unchanged
    - Observe: Memorial owner can delete tributes
    - Property: For all tributes where user is memorial owner, deletion succeeds
    - Verify test passes on UNFIXED code
    - _Requirements: 3.2_

  - [x] 2.3 Test admin operations on other users unchanged
    - Observe: Admin can delete users other than themselves
    - Property: For all users where admin.id != user.id, deletion succeeds
    - Verify test passes on UNFIXED code
    - _Requirements: 3.5, 3.6, 3.7, 3.8_

  - [x] 2.4 Test image upload functionality unchanged
    - Observe: Valid images under 5MB upload successfully
    - Property: For all valid images with size <= 5MB, upload succeeds
    - Verify test passes on UNFIXED code
    - _Requirements: 3.9, 3.10, 3.11, 3.12_

  - [x] 2.5 Test authentication functionality unchanged
    - Observe: Login, logout, password reset work correctly
    - Property: For all valid credentials, authentication flow succeeds
    - Verify test passes on UNFIXED code
    - _Requirements: 3.13, 3.14, 3.15, 3.16_

  - [x] 2.6 Test search functionality unchanged
    - Observe: Search within rate limits returns correct results
    - Property: For all search queries within limits, results match expected
    - Verify test passes on UNFIXED code
    - _Requirements: 3.17, 3.18, 3.19, 3.20_

  - [x] 2.7 Test content display unchanged
    - Observe: Safe HTML content displays correctly after sanitization
    - Property: For all safe HTML content, formatting is preserved
    - Verify test passes on UNFIXED code
    - _Requirements: 3.21, 3.22, 3.23, 3.24_


## Phase 3: Implementation of Security Fixes

- [x] 3. Fix 1: Mass Assignment Protection

  - [x] 3.1 Configure strict $fillable in Tribute model
    - Add protected $fillable = ['memorial_id', 'author_name', 'author_email', 'message']
    - Remove $guarded = [] if present
    - Ensure only allowed fields can be mass-assigned
    - _Bug_Condition: isBugCondition(input) where input contains unauthorized fields (is_approved, created_at)_
    - _Expected_Behavior: Only fields in $fillable array are accepted, unauthorized fields ignored_
    - _Preservation: Valid tribute creation with allowed fields continues to work_
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3_

  - [x] 3.2 Verify TributeController uses validated data
    - Ensure controller uses $request->validated() or explicit field assignment
    - Verify no direct mass assignment of unvalidated request data
    - _Requirements: 2.3_

  - [x] 3.3 Verify mass assignment exploration test now passes
    - **Property 1: Expected Behavior** - Mass Assignment Protection
    - **IMPORTANT**: Re-run the SAME test from task 1.1 - do NOT write a new test
    - Run mass assignment exploration test from step 1.1
    - **EXPECTED OUTCOME**: Test PASSES (confirms unauthorized fields are now ignored)
    - _Requirements: 2.1, 2.2, 2.3_

- [x] 4. Fix 2: Admin Self-Deletion Prevention

  - [x] 4.1 Create or modify UserPolicy with self-deletion check
    - Implement delete() method that prevents admin from deleting themselves
    - Return false when $authUser->id === $targetUser->id
    - Ensure admin can delete other users
    - _Bug_Condition: isBugCondition(input) where admin attempts to delete own account_
    - _Expected_Behavior: System returns 403 Forbidden for self-deletion attempts_
    - _Preservation: Admin deletion of other users continues to work_
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [x] 4.2 Modify AdminUserController to use authorization
    - Replace manual check with $this->authorize('delete', $user)
    - Remove if ($user->id === auth()->id()) check from controller
    - Let policy handle authorization logic
    - _Requirements: 2.4, 2.5_

  - [x] 4.3 Register UserPolicy in AuthServiceProvider
    - Add User::class => UserPolicy::class to $policies array
    - Ensure policy is properly registered
    - _Requirements: 2.4_

  - [x] 4.4 Verify admin self-deletion exploration test now passes
    - **Property 1: Expected Behavior** - Admin Self-Deletion Prevention
    - **IMPORTANT**: Re-run the SAME test from task 1.2 - do NOT write a new test
    - Run admin self-deletion exploration test from step 1.2
    - **EXPECTED OUTCOME**: Test PASSES (confirms self-deletion is now prevented at policy level)
    - _Requirements: 2.4, 2.5, 2.6_

- [x] 5. Fix 3: Input Sanitization for XSS Prevention

  - [x] 5.1 Create SanitizationService
    - Implement sanitizeHtml() method using strip_tags with allowed safe tags
    - Implement sanitizePlainText() method removing all HTML
    - Remove event handlers and javascript: protocols
    - _Bug_Condition: isBugCondition(input) where input contains malicious HTML/JavaScript_
    - _Expected_Behavior: Dangerous content removed, safe formatting preserved_
    - _Preservation: Safe HTML content continues to display correctly_
    - _Requirements: 3.1, 3.2, 3.3, 2.7, 2.8, 2.9_

  - [x] 5.2 Modify MemorialController to sanitize biography
    - Inject SanitizationService in constructor
    - Sanitize biography in store() and update() methods
    - Use sanitizeHtml() to preserve safe formatting
    - _Requirements: 3.1, 2.7_

  - [x] 5.3 Modify TributeController to sanitize message
    - Inject SanitizationService in constructor
    - Sanitize message in store() method
    - Use sanitizeHtml() to preserve safe formatting
    - _Requirements: 3.2, 2.8_

  - [x] 5.4 Modify ContactController to sanitize subject
    - Inject SanitizationService in constructor
    - Sanitize subject and message in store() method
    - Use sanitizePlainText() for subject, sanitizeHtml() for message
    - _Requirements: 3.3, 2.9_

  - [x] 5.5 Verify XSS exploration test now passes
    - **Property 1: Expected Behavior** - XSS Prevention
    - **IMPORTANT**: Re-run the SAME test from task 1.3 - do NOT write a new test
    - Run XSS exploration test from step 1.3
    - **EXPECTED OUTCOME**: Test PASSES (confirms malicious content is now sanitized)
    - _Requirements: 2.7, 2.8, 2.9_


- [x] 6. Fix 4: Image Upload Size Limits

  - [x] 6.1 Add max size validation to StoreImageRequest
    - Add 'max:5120' validation rule (5MB = 5120 KB)
    - Add custom error message for size validation
    - Ensure validation happens before file processing
    - _Bug_Condition: isBugCondition(input) where image file size > 5MB_
    - _Expected_Behavior: System returns 422 validation error for oversized files_
    - _Preservation: Valid images under 5MB continue to upload successfully_
    - _Requirements: 4.1, 4.2, 4.3, 2.10, 2.11, 2.12_

  - [x] 6.2 Add defensive size check in ImageService
    - Add size validation in upload() method as defensive programming
    - Throw exception if file exceeds 5MB
    - Ensure validation happens before expensive processing
    - _Requirements: 4.3, 2.12_

  - [x] 6.3 Verify image upload exploration test now passes
    - **Property 1: Expected Behavior** - Image Size Limits
    - **IMPORTANT**: Re-run the SAME test from task 1.4 - do NOT write a new test
    - Run image upload exploration test from step 1.4
    - **EXPECTED OUTCOME**: Test PASSES (confirms oversized files are now rejected)
    - _Requirements: 2.10, 2.11, 2.12_

- [x] 7. Fix 5: Admin Tribute Deletion Authorization

  - [x] 7.1 Update TributePolicy to include admin check
    - Modify delete() method to check if user hasRole('admin')
    - Return true if user is admin OR memorial owner
    - Maintain existing owner authorization
    - _Bug_Condition: isBugCondition(input) where admin attempts to delete tribute (not owner)_
    - _Expected_Behavior: System allows admin deletion regardless of ownership_
    - _Preservation: Memorial owner deletion continues to work_
    - _Requirements: 5.1, 5.2, 5.3, 2.13, 2.14, 2.15_

  - [x] 7.2 Verify TributeController uses authorization
    - Ensure destroy() method calls $this->authorize('delete', $tribute)
    - Confirm policy is properly applied
    - _Requirements: 2.13_

  - [x] 7.3 Verify admin tribute deletion exploration test now passes
    - **Property 1: Expected Behavior** - Admin Tribute Deletion
    - **IMPORTANT**: Re-run the SAME test from task 1.5 - do NOT write a new test
    - Run admin tribute deletion exploration test from step 1.5
    - **EXPECTED OUTCOME**: Test PASSES (confirms admin can now delete tributes)
    - _Requirements: 2.13, 2.14, 2.15_

- [x] 8. Fix 6: Password Reset Rate Limiters

  - [x] 8.1 Define custom rate limiters in RouteServiceProvider
    - Add password-reset-link limiter (3 requests per hour)
    - Add password-reset-submit limiter (5 requests per hour)
    - Configure custom error responses for rate limit exceeded
    - _Bug_Condition: isBugCondition(input) where rate limiters are undefined_
    - _Expected_Behavior: Custom rate limiters properly throttle password reset requests_
    - _Preservation: Valid password reset requests within limits continue to work_
    - _Requirements: 6.1, 6.2, 6.3, 2.16, 2.17, 2.18_

  - [x] 8.2 Verify routes apply correct middleware
    - Confirm /forgot-password uses throttle:password-reset-link
    - Confirm /reset-password uses throttle:password-reset-submit
    - Ensure middleware is properly applied
    - _Requirements: 2.16, 2.17_

  - [x] 8.3 Verify password reset rate limiter exploration test now passes
    - **Property 1: Expected Behavior** - Password Reset Rate Limiting
    - **IMPORTANT**: Re-run the SAME test from task 1.6 - do NOT write a new test
    - Run password reset rate limiter exploration test from step 1.6
    - **EXPECTED OUTCOME**: Test PASSES (confirms rate limiters are now defined and working)
    - _Requirements: 2.16, 2.17, 2.18_

- [x] 9. Fix 7: Logout Cookie Clearing

  - [x] 9.1 Modify AuthController logout to clear cookie
    - Add cookie clearing to logout response
    - Set auth_token cookie with empty value and past expiration
    - Configure secure, httpOnly, and sameSite attributes
    - _Bug_Condition: isBugCondition(input) where logout does not clear cookie_
    - _Expected_Behavior: Logout clears auth_token cookie from browser_
    - _Preservation: Token deletion from database continues to work_
    - _Requirements: 7.1, 7.2, 7.3, 2.19, 2.20, 2.21_

  - [x] 9.2 Verify logout cookie clearing exploration test now passes
    - **Property 1: Expected Behavior** - Logout Cookie Clearing
    - **IMPORTANT**: Re-run the SAME test from task 1.7 - do NOT write a new test
    - Run logout cookie clearing exploration test from step 1.7
    - **EXPECTED OUTCOME**: Test PASSES (confirms cookie is now cleared on logout)
    - _Requirements: 2.19, 2.20, 2.21_


- [x] 10. Fix 8: Search Rate Limiting

  - [x] 10.1 Apply throttle middleware to search route
    - Add throttle:60,1 middleware to /search route (60 requests per minute)
    - Alternatively create custom search rate limiter in RouteServiceProvider
    - Configure rate limit headers in response
    - _Bug_Condition: isBugCondition(input) where search endpoint has no rate limiting_
    - _Expected_Behavior: Search endpoint throttles after 60 requests per minute_
    - _Preservation: Search within rate limits continues to return correct results_
    - _Requirements: 8.1, 8.2, 8.3, 2.22, 2.23, 2.24_

  - [x] 10.2 Optional: Create custom search rate limiter
    - Define search limiter in RouteServiceProvider with custom response
    - Configure 60 requests per minute by IP
    - Add Retry-After header to 429 responses
    - _Requirements: 2.22, 2.23_

  - [x] 10.3 Verify search rate limiting exploration test now passes
    - **Property 1: Expected Behavior** - Search Rate Limiting
    - **IMPORTANT**: Re-run the SAME test from task 1.8 - do NOT write a new test
    - Run search rate limiting exploration test from step 1.8
    - **EXPECTED OUTCOME**: Test PASSES (confirms search is now rate limited)
    - _Requirements: 2.22, 2.23, 2.24_

## Phase 4: Final Validation

- [x] 11. Verify all preservation tests still pass
  - **Property 2: Preservation** - Final Preservation Validation
  - **IMPORTANT**: Re-run ALL preservation tests from Phase 2 - do NOT write new tests
  - Run all preservation property tests from step 2
  - **EXPECTED OUTCOME**: All tests PASS (confirms no regressions)
  - Verify valid tribute creation unchanged (2.1)
  - Verify memorial owner tribute deletion unchanged (2.2)
  - Verify admin operations on other users unchanged (2.3)
  - Verify image upload functionality unchanged (2.4)
  - Verify authentication functionality unchanged (2.5)
  - Verify search functionality unchanged (2.6)
  - Verify content display unchanged (2.7)
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20, 3.21, 3.22, 3.23, 3.24_

- [x] 12. Checkpoint - Ensure all tests pass and security vulnerabilities are fixed
  - Verify all 8 bug condition exploration tests now pass (fixes work correctly)
  - Verify all 7 preservation property tests still pass (no regressions)
  - Run full test suite to ensure no unexpected failures
  - Verify all security vulnerabilities are resolved:
    - Mass assignment protection in place
    - Admin self-deletion prevented at policy level
    - Input sanitization working for all user content
    - Image upload size limits enforced
    - Admin can delete tributes for moderation
    - Password reset rate limiters defined and working
    - Logout clears auth cookie properly
    - Search endpoint rate limited
  - Ask user if any questions or issues arise
  - _Requirements: All requirements 1.1-3.24_

