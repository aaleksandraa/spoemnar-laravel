# Implementation Plan

## Overview

This implementation plan addresses 40 security vulnerabilities across 6 categories using the bug condition methodology. The workflow follows: (1) Exploration tests to surface vulnerabilities BEFORE fixes, (2) Preservation tests to capture baseline behavior, (3) Implementation of all security fixes, and (4) Final validation.

## Phase 1: Bug Condition Exploration Tests (BEFORE Implementation)

These tests MUST be written and run on UNFIXED code. They will FAIL, confirming the vulnerabilities exist.

### Category 1: Input Validation Exploration Tests

- [x] 1.1 Write exploration test for biography length vulnerability
  - **Property 1: Fault Condition** - Biography DoS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples demonstrating biography accepts oversized input
  - **Scoped PBT Approach**: Test biography field with lengths > 5000 characters
  - Test that ProfileUpdateRequest accepts biography with 10,000 characters (from Fault Condition in design)
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (validation passes when it should reject)
  - Document counterexamples found (e.g., "10KB biography accepted without validation error")
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 2.1_

- [x] 1.2 Write exploration test for profile image URL XSS vulnerability
  - **Property 1: Fault Condition** - Profile Image XSS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexamples demonstrating malicious URLs are accepted
  - **Scoped PBT Approach**: Test with javascript:, data:, and file: URL schemes
  - Test that ProfileUpdateRequest accepts profile_image_url with `javascript:alert('XSS')`
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (malicious URL accepted)
  - Document counterexamples found
  - _Requirements: 2.2_

- [x] 1.3 Write exploration test for profile image URL SSRF vulnerability
  - **Property 1: Fault Condition** - Profile Image SSRF Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating internal/external URLs are accepted
  - **Scoped PBT Approach**: Test with internal IPs (169.254.x.x, 10.x.x.x) and non-whitelisted domains
  - Test that ProfileUpdateRequest accepts URLs to internal metadata services or arbitrary domains
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (non-whitelisted URLs accepted)
  - Document counterexamples found
  - _Requirements: 2.2_

- [x] 1.4 Write exploration test for video URL SSRF vulnerability
  - **Property 1: Fault Condition** - Video URL SSRF Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating non-whitelisted video URLs are accepted
  - **Scoped PBT Approach**: Test with non-YouTube/Vimeo domains
  - Test that VideoUpdateRequest accepts video_url from arbitrary domains (not YouTube/Vimeo)
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (non-whitelisted video URLs accepted)
  - Document counterexamples found
  - _Requirements: 2.3_

- [x] 1.5 Write exploration test for caption stored XSS vulnerability
  - **Property 1: Fault Condition** - Caption Stored XSS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating HTML/scripts are stored unsanitized
  - **Scoped PBT Approach**: Test with various XSS payloads (<script>, <img onerror>, etc.)
  - Test that CaptionUpdateRequest stores caption with `<script>alert('XSS')</script>` without sanitization
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (malicious HTML stored)
  - Document counterexamples found
  - _Requirements: 2.4_

- [x] 1.6 Write exploration test for weak password vulnerability
  - **Property 1: Fault Condition** - Weak Password Acceptance
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating weak passwords are accepted
  - **Scoped PBT Approach**: Test with passwords lacking complexity (short, no special chars, etc.)
  - Test that UpdatePasswordRequest accepts password `123` or `password`
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (weak passwords accepted)
  - Document counterexamples found
  - _Requirements: 2.5_

- [x] 1.7 Write exploration test for pagination DoS vulnerability
  - **Property 1: Fault Condition** - Pagination DoS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating excessive per_page values are accepted
  - **Scoped PBT Approach**: Test with per_page values > 100
  - Test that pagination requests accept per_page=10000
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (excessive pagination accepted)
  - Document counterexamples found
  - _Requirements: 2.6_

- [x] 1.8 Write exploration test for search query DoS vulnerability
  - **Property 1: Fault Condition** - Search Query DoS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating oversized search queries are accepted
  - **Scoped PBT Approach**: Test with search queries > 255 characters
  - Test that SearchRequest accepts query with 1000 characters
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (oversized queries accepted)
  - Document counterexamples found
  - _Requirements: 2.7_

### Category 2: Authorization Exploration Tests

- [x] 1.9 Write exploration test for profile IDOR vulnerability
  - **Property 1: Fault Condition** - Profile IDOR Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating unauthorized profile access
  - **Scoped PBT Approach**: Test User A accessing User B's profile routes
  - Test that ProfileController allows User A to view/edit User B's profile without authorization check
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (unauthorized access allowed)
  - Document counterexamples found
  - _Requirements: 2.9_

- [x] 1.10 Write exploration test for image reorder IDOR vulnerability
  - **Property 1: Fault Condition** - Image Reorder IDOR Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating unauthorized image reordering
  - **Scoped PBT Approach**: Test User A reordering User B's images
  - Test that ImageController allows User A to reorder User B's images without ownership check
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (unauthorized operation allowed)
  - Document counterexamples found
  - _Requirements: 2.10_

- [x] 1.11 Write exploration test for video operations IDOR vulnerability
  - **Property 1: Fault Condition** - Video Operations IDOR Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating unauthorized video operations
  - **Scoped PBT Approach**: Test User A updating/deleting User B's videos
  - Test that VideoController allows User A to update/delete User B's videos without ownership check
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (unauthorized operations allowed)
  - Document counterexamples found
  - _Requirements: 2.11_

- [x] 1.12 Write exploration test for location import privilege escalation vulnerability
  - **Property 1: Fault Condition** - Location Import Privilege Escalation
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating non-admin access to import
  - **Scoped PBT Approach**: Test regular user accessing location import endpoint
  - Test that LocationController allows non-admin users to access import functionality
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (non-admin access allowed)
  - Document counterexamples found
  - _Requirements: 2.12_

### Category 3: Data Sanitization Exploration Tests

- [x] 1.13 Write exploration test for caption display XSS vulnerability
  - **Property 1: Fault Condition** - Caption Display XSS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating unsanitized caption display
  - **Scoped PBT Approach**: Test caption with various XSS payloads displayed in views
  - Test that caption with `<img src=x onerror=alert('XSS')>` executes when displayed
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (XSS payload executes)
  - Document counterexamples found
  - _Requirements: 2.14_

- [x] 1.14 Write exploration test for profile fields display XSS vulnerability
  - **Property 1: Fault Condition** - Profile Fields Display XSS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating unsanitized profile field display
  - **Scoped PBT Approach**: Test profile fields with HTML/script content
  - Test that profile fields with malicious HTML execute when displayed
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (XSS payload executes)
  - Document counterexamples found
  - _Requirements: 2.15_

- [x] 1.15 Write exploration test for hero settings XSS vulnerability
  - **Property 1: Fault Condition** - Hero Settings XSS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating unsanitized hero settings display
  - **Scoped PBT Approach**: Test hero settings fields with XSS payloads
  - Test that hero settings with malicious content execute when displayed
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (XSS payload executes)
  - Document counterexamples found
  - _Requirements: 2.16_

- [x] 1.16 Write exploration test for contact form XSS vulnerability
  - **Property 1: Fault Condition** - Contact Form XSS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating unsanitized contact form display
  - **Scoped PBT Approach**: Test contact form fields with XSS payloads
  - Test that contact form data with malicious content executes when displayed
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (XSS payload executes)
  - Document counterexamples found
  - _Requirements: 2.17_

### Category 4: Validation Logic Exploration Tests

- [x] 1.17 Write exploration test for reorder structure validation vulnerability
  - **Property 1: Fault Condition** - Reorder Structure Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating invalid reorder structures are accepted
  - **Scoped PBT Approach**: Test with malformed reorder data (missing IDs, invalid types, etc.)
  - Test that ReorderRequest accepts invalid array structures or missing required fields
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (invalid structures accepted)
  - Document counterexamples found
  - _Requirements: 2.18_

- [x] 1.18 Write exploration test for search parameter validation vulnerability
  - **Property 1: Fault Condition** - Search Parameter Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating unvalidated search parameters
  - **Scoped PBT Approach**: Test with invalid search types, missing parameters
  - Test that SearchRequest accepts invalid type values or malformed parameters
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (invalid parameters accepted)
  - Document counterexamples found
  - _Requirements: 2.19_

- [x] 1.19 Write exploration test for hero settings validation vulnerability
  - **Property 1: Fault Condition** - Hero Settings Validation Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating unvalidated hero settings
  - **Scoped PBT Approach**: Test with oversized fields, invalid URLs, malformed data
  - Test that HeroSettingsUpdateRequest accepts invalid or malicious hero settings
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (invalid settings accepted)
  - Document counterexamples found
  - _Requirements: 2.20_

- [x] 1.20 Write exploration test for role privilege escalation vulnerability
  - **Property 1: Fault Condition** - Role Privilege Escalation Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating invalid role assignments
  - **Scoped PBT Approach**: Test with non-whitelisted roles, privilege escalation attempts
  - Test that RoleUpdateRequest accepts role='super_admin' or other invalid roles
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (invalid roles accepted)
  - Document counterexamples found
  - _Requirements: 2.21_

- [x] 1.21 Write exploration test for future tribute timestamp vulnerability
  - **Property 1: Fault Condition** - Future Tribute Timestamp Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating future dates are accepted
  - **Scoped PBT Approach**: Test with dates in the future
  - Test that TributeStoreRequest/TributeUpdateRequest accepts tribute_timestamp in the future
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (future dates accepted)
  - Document counterexamples found
  - _Requirements: 2.22_

- [x] 1.22 Write exploration test for slug format validation vulnerability
  - **Property 1: Fault Condition** - Slug Format Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating invalid slug formats are accepted
  - **Scoped PBT Approach**: Test with special characters, path traversal patterns
  - Test that slug parameter accepts `../../../etc/passwd` or other invalid formats
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (invalid slugs accepted)
  - Document counterexamples found
  - _Requirements: 2.23_

- [x] 1.23 Write exploration test for active status type validation vulnerability
  - **Property 1: Fault Condition** - Active Status Type Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating non-boolean active values are accepted
  - **Scoped PBT Approach**: Test with strings, integers, null values
  - Test that active field accepts 'yes', 1, or other non-boolean values
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (non-boolean values accepted)
  - Document counterexamples found
  - _Requirements: 2.24_

- [x] 1.24 Write exploration test for display order range validation vulnerability
  - **Property 1: Fault Condition** - Display Order Range Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating out-of-range display orders are accepted
  - **Scoped PBT Approach**: Test with negative values, extremely large values
  - Test that display_order accepts 999999 or -1
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (out-of-range values accepted)
  - Document counterexamples found
  - _Requirements: 2.25_

### Category 5: Infrastructure Security Exploration Tests

- [x] 1.25 Write exploration test for failed login logging vulnerability
  - **Property 1: Fault Condition** - Failed Login Not Logged
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating failed logins are not logged
  - **Scoped PBT Approach**: Trigger failed login attempts and check logs
  - Test that failed login attempts do not generate log entries
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (no log entries found)
  - Document counterexamples found
  - _Requirements: 2.26_

- [x] 1.26 Write exploration test for authorization failure logging vulnerability
  - **Property 1: Fault Condition** - Authorization Failure Not Logged
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating authorization failures are not logged
  - **Scoped PBT Approach**: Trigger authorization failures and check logs
  - Test that authorization failures do not generate log entries
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (no log entries found)
  - Document counterexamples found
  - _Requirements: 2.27_

- [x] 1.27 Write exploration test for request size limit vulnerability
  - **Property 1: Fault Condition** - Request Size DoS Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating oversized requests are accepted
  - **Scoped PBT Approach**: Test with requests > 10MB
  - Test that requests with 100MB payloads are processed without rejection
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (oversized requests accepted)
  - Document counterexamples found
  - _Requirements: 2.28_

- [x] 1.28 Write exploration test for error information disclosure vulnerability
  - **Property 1: Fault Condition** - Error Information Disclosure
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating sensitive error information is exposed
  - **Scoped PBT Approach**: Trigger various errors and check responses
  - Test that database errors or exceptions expose stack traces or sensitive information to users
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (sensitive information exposed)
  - Document counterexamples found
  - _Requirements: 2.29_

- [x] 1.29 Write exploration test for locale parameter validation vulnerability
  - **Property 1: Fault Condition** - Locale Parameter Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating non-whitelisted locales are accepted
  - **Scoped PBT Approach**: Test with invalid locale codes, path traversal attempts
  - Test that locale parameter accepts arbitrary values like `../../etc/passwd`
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (invalid locales accepted)
  - Document counterexamples found
  - _Requirements: 2.30_

### Category 6: Best Practice Exploration Tests

- [x] 1.30 Write exploration test for missing request ID vulnerability
  - **Property 1: Fault Condition** - Missing Request ID
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating requests lack unique IDs
  - **Scoped PBT Approach**: Make requests and check for request ID in headers/logs
  - Test that requests do not have X-Request-ID header or request ID in logs
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (no request IDs found)
  - Document counterexamples found
  - _Requirements: 2.31_

- [x] 1.31 Write exploration test for page number validation vulnerability
  - **Property 1: Fault Condition** - Page Number Validation Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating invalid page numbers are accepted
  - **Scoped PBT Approach**: Test with negative, zero, or non-integer page values
  - Test that page parameter accepts -5, 0, or 'abc'
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (invalid page numbers accepted)
  - Document counterexamples found
  - _Requirements: 2.32_

- [x] 1.32 Write exploration test for date range logical validation vulnerability
  - **Property 1: Fault Condition** - Date Range Logic Attack
  - **CRITICAL**: This test MUST FAIL on unfixed code
  - **GOAL**: Surface counterexamples demonstrating illogical date ranges are accepted
  - **Scoped PBT Approach**: Test with start_date > end_date
  - Test that date range requests accept start_date='2024-12-31' and end_date='2024-01-01'
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (illogical ranges accepted)
  - Document counterexamples found
  - _Requirements: 2.33_

## Phase 2: Preservation Property Tests (BEFORE Implementation)

These tests MUST be written and run on UNFIXED code to capture baseline behavior. They will PASS, confirming existing functionality to preserve.

- [x] 2.1 Write preservation tests for valid input processing
  - **Property 2: Preservation** - Valid Input Processing
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for valid inputs:
    - Valid biography (≤5000 chars) is accepted and stored
    - Valid profile image URLs from whitelisted domains are processed
    - Valid video URLs from YouTube/Vimeo embed correctly
    - Valid captions without HTML are stored and displayed correctly
    - Valid passwords meeting complexity are accepted
    - Valid pagination (per_page ≤100, page ≥1) works correctly
    - Valid search queries (≤255 chars) return results
  - Write property-based tests capturing these behaviors across input domain
  - Property: For all valid inputs (where isBugCondition returns false), system processes successfully
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_

- [x] 2.2 Write preservation tests for authorized user operations
  - **Property 2: Preservation** - Authorized Operations
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for authorized operations:
    - Users accessing their own profiles can view/edit successfully
    - Users managing their own images/videos can reorder/update/delete
    - Admin users can access administrative functions
  - Write property-based tests capturing these authorization patterns
  - Property: For all authorized users performing operations on their own resources, system allows access
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (confirms baseline authorization behavior)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.8, 3.9, 3.10_

- [x] 2.3 Write preservation tests for valid data display
  - **Property 2: Preservation** - Valid Data Display
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for valid data display:
    - Valid captions without malicious content display correctly
    - Valid profile fields display correctly
    - Valid hero settings apply correctly
    - Valid contact form submissions are processed
  - Write property-based tests capturing these display behaviors
  - Property: For all valid data without malicious content, system displays correctly
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (confirms baseline display behavior)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.11, 3.12, 3.13, 3.14_

- [x] 2.4 Write preservation tests for valid business logic operations
  - **Property 2: Preservation** - Valid Business Logic
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for valid operations:
    - Valid reorder requests update display order correctly
    - Valid search queries return relevant results
    - Valid role updates by authorized admins work
    - Valid tribute timestamps (past/current) are accepted
    - Valid slug parameters route correctly
  - Write property-based tests capturing these business logic behaviors
  - Property: For all valid business operations, system processes correctly
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (confirms baseline business logic)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.15, 3.16, 3.17, 3.18, 3.19_

- [x] 2.5 Write preservation tests for valid system operations
  - **Property 2: Preservation** - Valid System Operations
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for valid system operations:
    - Requests within size limits process normally
    - Valid locale parameters apply localization
    - Valid display order values apply correctly
    - Valid date ranges filter results correctly
  - Write property-based tests capturing these system behaviors
  - Property: For all valid system parameters, system operates correctly
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (confirms baseline system behavior)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.20_

## Phase 3: Implementation of Security Fixes

### Phase 3.1: Critical Priority - Input Validation Fixes (Week 1)

- [x] 3.1 Fix biography length validation vulnerability

  - [x] 3.1.1 Implement biography length validation
    - Update `app/Http/Requests/ProfileUpdateRequest.php`
    - Add validation rule: `'biography' => ['nullable', 'string', 'max:5000']`
    - _Bug_Condition: input.field == 'biography' AND length(input.value) > 5000 AND NOT validated_
    - _Expected_Behavior: Reject biography > 5000 chars with validation error_
    - _Preservation: Valid biography ≤5000 chars continues to be accepted_
    - _Requirements: 2.1_

  - [x] 3.1.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Biography Length Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.1 - do NOT write a new test
    - Run biography length exploration test from step 1.1
    - **EXPECTED OUTCOME**: Test PASSES (confirms validation works)
    - _Requirements: 2.1_

  - [x] 3.1.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Biography Processing
    - **IMPORTANT**: Re-run the SAME tests from task 2.1 - do NOT write new tests
    - Run preservation tests for valid biography input
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.2 Fix profile image URL validation vulnerabilities

  - [x] 3.2.1 Implement URL whitelist validation
    - Create `app/Rules/WhitelistedUrlRule.php`
    - Implement whitelist checking for allowed domains
    - Validate URL scheme (https only)
    - Reject suspicious patterns (javascript:, data:, file:)
    - Update `app/Http/Requests/ProfileUpdateRequest.php`
    - Add validation: `'profile_image_url' => ['nullable', 'url', 'max:255', new WhitelistedUrlRule()]`
    - _Bug_Condition: input.field == 'profile_image_url' AND NOT whitelistValidated(input.value)_
    - _Expected_Behavior: Reject non-whitelisted URLs with validation error_
    - _Preservation: Valid URLs from whitelisted domains continue to be processed_
    - _Requirements: 2.2_

  - [x] 3.2.2 Verify exploration tests now pass
    - **Property 1: Expected Behavior** - URL Whitelist Validation
    - **IMPORTANT**: Re-run the SAME tests from tasks 1.2 and 1.3
    - Run profile image URL exploration tests
    - **EXPECTED OUTCOME**: Tests PASS (confirms XSS/SSRF prevention)
    - _Requirements: 2.2_

  - [x] 3.2.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid URL Processing
    - Run preservation tests for valid profile image URLs
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.3 Fix video URL validation vulnerability

  - [x] 3.3.1 Implement video URL platform validation
    - Create `app/Rules/VideoUrlRule.php`
    - Validate against YouTube and Vimeo URL patterns
    - Extract and validate video IDs
    - Reject non-whitelisted domains
    - Update `app/Http/Requests/VideoUpdateRequest.php`
    - Add validation: `'video_url' => ['required', 'url', new VideoUrlRule()]`
    - _Bug_Condition: input.field == 'video_url' AND NOT platformValidated(input.value)_
    - _Expected_Behavior: Reject non-YouTube/Vimeo URLs with validation error_
    - _Preservation: Valid YouTube/Vimeo URLs continue to embed correctly_
    - _Requirements: 2.3_

  - [x] 3.3.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Video URL Platform Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.4
    - Run video URL exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms SSRF prevention)
    - _Requirements: 2.3_

  - [x] 3.3.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Video URL Processing
    - Run preservation tests for valid video URLs
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.4 Fix caption stored XSS vulnerability

  - [x] 3.4.1 Implement caption sanitization
    - Create `app/Services/SanitizationService.php`
    - Implement `sanitizeHtml()` method using HTMLPurifier
    - Configure very restrictive allowed tags and attributes
    - Add HTMLPurifier dependency to composer.json: `"ezyang/htmlpurifier": "^4.16"`
    - Update `app/Http/Requests/CaptionUpdateRequest.php`
    - Add `prepareForValidation()` method to sanitize caption before validation
    - Add validation: `'caption' => ['nullable', 'string', 'max:1000']`
    - _Bug_Condition: input.field == 'caption' AND containsHTML(input.value) AND NOT sanitized_
    - _Expected_Behavior: Sanitize HTML before storage, removing malicious scripts_
    - _Preservation: Valid captions without malicious content continue to display correctly_
    - _Requirements: 2.4_

  - [x] 3.4.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Caption Sanitization
    - **IMPORTANT**: Re-run the SAME test from task 1.5
    - Run caption XSS exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms XSS prevention)
    - _Requirements: 2.4_

  - [x] 3.4.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Caption Processing
    - Run preservation tests for valid captions
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.5 Fix weak password vulnerability

  - [x] 3.5.1 Implement password complexity validation
    - Update `app/Http/Requests/UpdatePasswordRequest.php`
    - Add validation: `'password' => ['required', 'string', 'min:12', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/']`
    - Add confirmation: `'password_confirmation' => ['required', 'same:password']`
    - _Bug_Condition: input.field == 'password' AND NOT complexityValidated(input.value)_
    - _Expected_Behavior: Reject weak passwords with validation error_
    - _Preservation: Valid complex passwords continue to be accepted_
    - _Requirements: 2.5_

  - [x] 3.5.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Password Complexity Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.6
    - Run weak password exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms complexity enforcement)
    - _Requirements: 2.5_

  - [x] 3.5.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Password Processing
    - Run preservation tests for valid passwords
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.6 Fix pagination DoS vulnerability

  - [x] 3.6.1 Implement pagination limits
    - Create `app/Http/Requests/PaginationRequest.php`
    - Add validation: `'per_page' => ['nullable', 'integer', 'min:1', 'max:100']`
    - Add validation: `'page' => ['nullable', 'integer', 'min:1']`
    - Update controllers to extend PaginationRequest where applicable
    - _Bug_Condition: input.param == 'per_page' AND input.value > 100 AND NOT validated_
    - _Expected_Behavior: Reject per_page > 100 with validation error_
    - _Preservation: Valid pagination (per_page ≤100) continues to work_
    - _Requirements: 2.6_

  - [x] 3.6.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Pagination Limit Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.7
    - Run pagination DoS exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms DoS prevention)
    - _Requirements: 2.6_

  - [x] 3.6.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Pagination Processing
    - Run preservation tests for valid pagination
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.7 Fix search query DoS vulnerability

  - [x] 3.7.1 Implement search query length validation
    - Update `app/Http/Requests/SearchRequest.php`
    - Add validation: `'q' => ['required', 'string', 'max:255']`
    - Add validation: `'type' => ['nullable', 'in:profiles,tributes,locations']`
    - _Bug_Condition: input.param == 'search' AND length(input.value) > 255 AND NOT validated_
    - _Expected_Behavior: Reject search queries > 255 chars with validation error_
    - _Preservation: Valid search queries ≤255 chars continue to return results_
    - _Requirements: 2.7_

  - [x] 3.7.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Search Query Length Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.8
    - Run search DoS exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms DoS prevention)
    - _Requirements: 2.7_

  - [x] 3.7.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Search Processing
    - Run preservation tests for valid search queries
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

### Phase 3.2: Critical Priority - Authorization Fixes (Week 1)

- [x] 3.8 Fix profile IDOR vulnerability

  - [x] 3.8.1 Implement profile authorization policy
    - Create/update `app/Policies/ProfilePolicy.php`
    - Add `view()` method: Check if user owns profile or has admin role
    - Add `update()` method: Check if user owns profile or has admin role
    - Add `delete()` method: Check if user owns profile or has admin role
    - Update `app/Http/Controllers/ProfileController.php`
    - Add authorization checks: `$this->authorize('view', $profile)` before showing
    - Add authorization checks: `$this->authorize('update', $profile)` before updating
    - Add authorization checks: `$this->authorize('delete', $profile)` before deleting
    - Register policy in `app/Providers/AuthServiceProvider.php`
    - _Bug_Condition: input.route IN profileRoutes AND NOT ownershipVerified(input.user, input.resource)_
    - _Expected_Behavior: Deny unauthorized access with 403 Forbidden_
    - _Preservation: Authorized users accessing their own profiles continue to have full access_
    - _Requirements: 2.9_

  - [x] 3.8.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Profile Authorization
    - **IMPORTANT**: Re-run the SAME test from task 1.9
    - Run profile IDOR exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms IDOR prevention)
    - _Requirements: 2.9_

  - [x] 3.8.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Authorized Profile Access
    - Run preservation tests for authorized profile operations
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.9 Fix image operations IDOR vulnerability

  - [x] 3.9.1 Implement image authorization policy
    - Create `app/Policies/ImagePolicy.php`
    - Add `reorder()` method: Verify user owns parent profile
    - Add `update()` method: Verify user owns parent profile
    - Add `delete()` method: Verify user owns parent profile
    - Update `app/Http/Controllers/ImageController.php`
    - Add authorization check: `$this->authorize('reorder', $image)` in reorder method
    - Add authorization check: `$this->authorize('update', $image)` in update method
    - Add authorization check: `$this->authorize('delete', $image)` in destroy method
    - Register policy in `app/Providers/AuthServiceProvider.php`
    - _Bug_Condition: input.action IN ['reorder', 'delete', 'update'] AND resourceType == 'image' AND NOT ownershipVerified_
    - _Expected_Behavior: Deny unauthorized operations with 403 Forbidden_
    - _Preservation: Authorized users managing their own images continue to perform operations successfully_
    - _Requirements: 2.10_

  - [x] 3.9.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Image Authorization
    - **IMPORTANT**: Re-run the SAME test from task 1.10
    - Run image reorder IDOR exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms IDOR prevention)
    - _Requirements: 2.10_

  - [x] 3.9.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Authorized Image Operations
    - Run preservation tests for authorized image operations
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.10 Fix video operations IDOR vulnerability

  - [x] 3.10.1 Implement video authorization policy
    - Create `app/Policies/VideoPolicy.php`
    - Add `reorder()` method: Verify user owns parent profile
    - Add `update()` method: Verify user owns parent profile
    - Add `delete()` method: Verify user owns parent profile
    - Update `app/Http/Controllers/VideoController.php`
    - Add authorization check: `$this->authorize('reorder', $video)` in reorder method
    - Add authorization check: `$this->authorize('update', $video)` in update method
    - Add authorization check: `$this->authorize('delete', $video)` in destroy method
    - Register policy in `app/Providers/AuthServiceProvider.php`
    - _Bug_Condition: input.action IN ['reorder', 'delete', 'update'] AND resourceType == 'video' AND NOT ownershipVerified_
    - _Expected_Behavior: Deny unauthorized operations with 403 Forbidden_
    - _Preservation: Authorized users managing their own videos continue to perform operations successfully_
    - _Requirements: 2.11_

  - [x] 3.10.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Video Authorization
    - **IMPORTANT**: Re-run the SAME test from task 1.11
    - Run video operations IDOR exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms IDOR prevention)
    - _Requirements: 2.11_

  - [x] 3.10.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Authorized Video Operations
    - Run preservation tests for authorized video operations
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.11 Fix location import privilege escalation vulnerability

  - [x] 3.11.1 Implement location import authorization
    - Create/update `app/Policies/LocationPolicy.php`
    - Add `import()` method: Check for admin role
    - Update `app/Http/Controllers/LocationController.php`
    - Add authorization check: `$this->authorize('import', Location::class)` in import method
    - Register policy in `app/Providers/AuthServiceProvider.php`
    - Ensure `Gate::before()` checks for admin users
    - _Bug_Condition: input.route == 'location.import' AND NOT isAdmin(input.user)_
    - _Expected_Behavior: Deny non-admin access with 403 Forbidden_
    - _Preservation: Admin users performing legitimate administrative tasks continue to have access_
    - _Requirements: 2.12, 2.13_

  - [x] 3.11.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Location Import Authorization
    - **IMPORTANT**: Re-run the SAME test from task 1.12
    - Run location import privilege escalation exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms privilege escalation prevention)
    - _Requirements: 2.12, 2.13_

  - [x] 3.11.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Admin Access
    - Run preservation tests for admin operations
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

### Phase 3.3: High Priority - Data Sanitization Fixes (Week 2)

- [x] 3.12 Fix data sanitization vulnerabilities

  - [x] 3.12.1 Implement comprehensive sanitization service
    - Ensure `app/Services/SanitizationService.php` exists (created in 3.4.1)
    - Implement `sanitizeText()` method for plain text fields
    - Configure HTMLPurifier with very restrictive settings
    - Update `app/Http/Requests/ProfileUpdateRequest.php`
    - Add `prepareForValidation()` to sanitize all text fields
    - Update `app/Http/Requests/HeroSettingsUpdateRequest.php`
    - Add `prepareForValidation()` to sanitize all text fields
    - Update `app/Http/Requests/ContactFormRequest.php`
    - Add `prepareForValidation()` to sanitize message and name fields
    - _Bug_Condition: input.field IN ['caption', 'profile_fields', 'hero_settings', 'contact_form'] AND containsHTML(input.value) AND NOT sanitizedOnDisplay_
    - _Expected_Behavior: Sanitize all user input before storage and display_
    - _Preservation: Valid content without malicious HTML continues to display correctly_
    - _Requirements: 2.14, 2.15, 2.16, 2.17_

  - [x] 3.12.2 Verify exploration tests now pass
    - **Property 1: Expected Behavior** - Data Sanitization
    - **IMPORTANT**: Re-run the SAME tests from tasks 1.13, 1.14, 1.15, 1.16
    - Run all data sanitization exploration tests
    - **EXPECTED OUTCOME**: Tests PASS (confirms XSS prevention)
    - _Requirements: 2.14, 2.15, 2.16, 2.17_

  - [x] 3.12.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Data Display
    - Run preservation tests for valid data display
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

### Phase 3.4: High Priority - Infrastructure Security (Logging & Error Handling) (Week 2)

- [x] 3.13 Fix security event logging vulnerabilities

  - [x] 3.13.1 Implement failed login logging
    - Create `app/Listeners/LogFailedLogin.php`
    - Listen for `Illuminate\Auth\Events\Failed` event
    - Log failed login attempts with: timestamp, IP address, username/email, user agent
    - Use dedicated security log channel
    - Update `config/logging.php`
    - Add security log channel: `storage/logs/security.log`
    - Update `app/Providers/EventServiceProvider.php`
    - Register `LogFailedLogin` listener
    - _Bug_Condition: event.type == 'failed_login' AND NOT logged(event)_
    - _Expected_Behavior: Log all failed login attempts with relevant details_
    - _Preservation: No impact on existing functionality_
    - _Requirements: 2.26_

  - [x] 3.13.2 Implement authorization failure logging
    - Create `app/Listeners/LogAuthorizationFailure.php`
    - Hook into `Illuminate\Auth\Access\Events\GateEvaluated` event
    - Log when authorization is denied: user ID, resource type, resource ID, action, timestamp
    - Use dedicated security log channel
    - Update `app/Providers/EventServiceProvider.php`
    - Register `LogAuthorizationFailure` listener
    - _Bug_Condition: event.type == 'authorization_failure' AND NOT logged(event)_
    - _Expected_Behavior: Log all authorization failures with relevant details_
    - _Preservation: No impact on existing functionality_
    - _Requirements: 2.27_

  - [x] 3.13.3 Verify exploration tests now pass
    - **Property 1: Expected Behavior** - Security Event Logging
    - **IMPORTANT**: Re-run the SAME tests from tasks 1.25, 1.26
    - Run security logging exploration tests
    - **EXPECTED OUTCOME**: Tests PASS (confirms logging works)
    - _Requirements: 2.26, 2.27_

  - [x] 3.13.4 Verify preservation tests still pass
    - **Property 2: Preservation** - System Operations
    - Run preservation tests for system operations
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.14 Fix error handling information disclosure vulnerability

  - [x] 3.14.1 Implement secure error handling
    - Update `app/Exceptions/Handler.php`
    - Override `render()` method to handle exceptions securely
    - Return generic error messages to users (avoid stack traces in production)
    - Log full error details internally
    - Implement different handling for production vs development environments
    - Handle specific exceptions (ValidationException, AuthorizationException, etc.) appropriately
    - _Bug_Condition: error.occurred AND sensitiveInfoExposed(error.message)_
    - _Expected_Behavior: Return generic error messages, log details internally_
    - _Preservation: No impact on existing functionality_
    - _Requirements: 2.29_

  - [x] 3.14.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Secure Error Handling
    - **IMPORTANT**: Re-run the SAME test from task 1.28
    - Run error information disclosure exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms information disclosure prevention)
    - _Requirements: 2.29_

  - [x] 3.14.3 Verify preservation tests still pass
    - **Property 2: Preservation** - System Operations
    - Run preservation tests for system operations
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

### Phase 3.5: Medium Priority - Validation Logic Fixes (Week 3)

- [x] 3.15 Fix reorder structure validation vulnerability

  - [x] 3.15.1 Implement reorder structure validation
    - Update `app/Http/Requests/ReorderRequest.php`
    - Add validation: `'items' => ['required', 'array', 'min:1']`
    - Add validation: `'items.*.id' => ['required', 'integer', 'exists:images,id']` (or videos table)
    - Add validation: `'items.*.display_order' => ['required', 'integer', 'min:0', 'max:9999']`
    - _Bug_Condition: input.action == 'reorder' AND NOT structureValidated(input.data)_
    - _Expected_Behavior: Reject invalid reorder structures with validation error_
    - _Preservation: Valid reorder requests continue to update display order_
    - _Requirements: 2.18_

  - [x] 3.15.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Reorder Structure Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.17
    - Run reorder structure exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms structure validation)
    - _Requirements: 2.18_

  - [x] 3.15.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Reorder Operations
    - Run preservation tests for valid reorder operations
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.16 Fix search parameter validation vulnerability

  - [x] 3.16.1 Implement comprehensive search parameter validation
    - Update `app/Http/Requests/SearchRequest.php` (already updated in 3.7.1)
    - Ensure validation: `'q' => ['required', 'string', 'max:255']`
    - Ensure validation: `'type' => ['nullable', 'in:profiles,tributes,locations']`
    - Ensure validation: `'per_page' => ['nullable', 'integer', 'min:1', 'max:100']`
    - _Bug_Condition: input.endpoint == 'search' AND NOT parametersValidated(input.params)_
    - _Expected_Behavior: Reject invalid search parameters with validation error_
    - _Preservation: Valid search queries continue to return relevant results_
    - _Requirements: 2.19_

  - [x] 3.16.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Search Parameter Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.18
    - Run search parameter exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms parameter validation)
    - _Requirements: 2.19_

  - [x] 3.16.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Search Operations
    - Run preservation tests for valid search operations
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.17 Fix hero settings validation vulnerability

  - [x] 3.17.1 Implement hero settings validation
    - Update `app/Http/Requests/HeroSettingsUpdateRequest.php`
    - Add validation: `'hero_name' => ['required', 'string', 'max:255']`
    - Add validation: `'hero_title' => ['nullable', 'string', 'max:255']`
    - Add validation: `'hero_image' => ['nullable', 'url', 'max:255', new WhitelistedUrlRule()]`
    - Add validation for all other hero settings fields
    - _Bug_Condition: input.field == 'hero_settings' AND NOT validated(input.value)_
    - _Expected_Behavior: Reject invalid hero settings with validation error_
    - _Preservation: Valid hero settings continue to apply correctly_
    - _Requirements: 2.20_

  - [x] 3.17.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Hero Settings Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.19
    - Run hero settings exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms validation works)
    - _Requirements: 2.20_

  - [x] 3.17.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Hero Settings
    - Run preservation tests for valid hero settings
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [-] 3.18 Fix role privilege escalation vulnerability

  - [x] 3.18.1 Implement role whitelist validation
    - Update `app/Http/Requests/RoleUpdateRequest.php`
    - Add validation: `'role' => ['required', 'string', 'in:user,editor,admin']`
    - Implement `withValidator()` method for complex role validation logic
    - Add custom validation to prevent privilege escalation (users can't assign roles higher than their own)
    - _Bug_Condition: input.field == 'role' AND NOT roleWhitelistValidated(input.value)_
    - _Expected_Behavior: Reject invalid roles and privilege escalation attempts with validation error_
    - _Preservation: Valid role updates by authorized admins continue to work_
    - _Requirements: 2.21_

  - [-] 3.18.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Role Whitelist Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.20
    - Run role privilege escalation exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms privilege escalation prevention)
    - _Requirements: 2.21_

  - [ ] 3.18.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Role Updates
    - Run preservation tests for valid role updates
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.19 Fix future tribute timestamp vulnerability

  - [x] 3.19.1 Implement tribute timestamp validation
    - Update `app/Http/Requests/TributeStoreRequest.php`
    - Add validation: `'tribute_timestamp' => ['required', 'date', 'before_or_equal:now']`
    - Update `app/Http/Requests/TributeUpdateRequest.php`
    - Add validation: `'tribute_timestamp' => ['required', 'date', 'before_or_equal:now']`
    - _Bug_Condition: input.field == 'tribute_timestamp' AND isFutureDate(input.value)_
    - _Expected_Behavior: Reject future dates with validation error_
    - _Preservation: Valid tribute timestamps (past/current dates) continue to be accepted_
    - _Requirements: 2.22_

  - [x] 3.19.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Tribute Timestamp Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.21
    - Run future tribute timestamp exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms future date rejection)
    - _Requirements: 2.22_

  - [x] 3.19.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Tribute Timestamps
    - Run preservation tests for valid tribute timestamps
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.20 Fix slug format validation vulnerability

  - [x] 3.20.1 Implement slug format validation
    - Create `app/Http/Requests/SlugRequest.php` or add to existing request classes
    - Add validation: `'slug' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:255']`
    - Apply to all routes that accept slug parameters
    - _Bug_Condition: input.param == 'slug' AND NOT formatValidated(input.value)_
    - _Expected_Behavior: Reject invalid slug formats with validation error_
    - _Preservation: Valid slug parameters continue to route correctly_
    - _Requirements: 2.23_

  - [x] 3.20.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Slug Format Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.22
    - Run slug format exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms format validation)
    - _Requirements: 2.23_

  - [x] 3.20.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Slug Routing
    - Run preservation tests for valid slug parameters
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.21 Fix active status type validation vulnerability

  - [x] 3.21.1 Implement active status boolean validation
    - Create `app/Http/Requests/ActiveStatusRequest.php` or add to existing request classes
    - Add validation: `'active' => ['required', 'boolean']`
    - Apply to all routes that accept active status parameter
    - _Bug_Condition: input.field == 'active' AND NOT booleanValidated(input.value)_
    - _Expected_Behavior: Reject non-boolean values with validation error_
    - _Preservation: Valid boolean active values continue to work_
    - _Requirements: 2.24_

  - [x] 3.21.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Active Status Type Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.23
    - Run active status type exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms type validation)
    - _Requirements: 2.24_

  - [x] 3.21.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Active Status
    - Run preservation tests for valid active status values
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.22 Fix display order range validation vulnerability

  - [x] 3.22.1 Implement display order range validation
    - Create `app/Http/Requests/DisplayOrderRequest.php` or add to existing request classes
    - Add validation: `'display_order' => ['required', 'integer', 'min:0', 'max:9999']`
    - Apply to all routes that accept display_order parameter
    - _Bug_Condition: input.field == 'display_order' AND NOT rangeValidated(input.value)_
    - _Expected_Behavior: Reject out-of-range values with validation error_
    - _Preservation: Valid display order values continue to apply correctly_
    - _Requirements: 2.25_

  - [x] 3.22.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Display Order Range Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.24
    - Run display order range exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms range validation)
    - _Requirements: 2.25_

  - [x] 3.22.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Display Order
    - Run preservation tests for valid display order values
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

### Phase 3.6: Medium Priority - Infrastructure Security (Request Limits & Locale) (Week 3)

- [x] 3.23 Fix request size limit vulnerability

  - [x] 3.23.1 Implement global request size limit middleware
    - Create `app/Http/Middleware/EnforceRequestSizeLimit.php`
    - Check `Content-Length` header
    - Reject requests exceeding 10MB with 413 Payload Too Large response
    - Register in `app/Http/Kernel.php` as global middleware
    - _Bug_Condition: input.size > globalLimit AND NOT rejected_
    - _Expected_Behavior: Reject oversized requests with 413 error_
    - _Preservation: Requests within size limits continue to process normally_
    - _Requirements: 2.28_

  - [x] 3.23.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Request Size Limit
    - **IMPORTANT**: Re-run the SAME test from task 1.27
    - Run request size limit exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms DoS prevention)
    - _Requirements: 2.28_

  - [x] 3.23.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Request Processing
    - Run preservation tests for valid-sized requests
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.24 Fix locale parameter validation vulnerability

  - [x] 3.24.1 Implement locale whitelist validation
    - Create `app/Http/Requests/LocaleRequest.php` or add to existing request classes
    - Add validation: `'locale' => ['required', 'string', 'in:en,es,fr,de']` (adjust based on supported locales)
    - Apply to all routes that accept locale parameter
    - _Bug_Condition: input.param == 'locale' AND NOT whitelistValidated(input.value)_
    - _Expected_Behavior: Reject non-whitelisted locales with validation error_
    - _Preservation: Valid locale parameters continue to apply localization_
    - _Requirements: 2.30_

  - [x] 3.24.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Locale Whitelist Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.29
    - Run locale parameter exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms whitelist validation)
    - _Requirements: 2.30_

  - [x] 3.24.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Locale Processing
    - Run preservation tests for valid locale parameters
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

### Phase 3.7: Low Priority - Best Practice Fixes (Week 4)

- [x] 3.25 Fix missing request ID vulnerability

  - [x] 3.25.1 Implement request ID middleware
    - Create `app/Http/Middleware/AssignRequestId.php`
    - Use `Str::uuid()` to generate unique request ID
    - Add to request attributes: `$request->attributes->set('request_id', $requestId)`
    - Add to response headers: `X-Request-ID`
    - Add to log context for all subsequent log entries
    - Register in `app/Http/Kernel.php` as global middleware
    - _Bug_Condition: request.processed AND NOT hasRequestID(request)_
    - _Expected_Behavior: Generate and track request ID for all requests_
    - _Preservation: No impact on existing functionality_
    - _Requirements: 2.31_

  - [x] 3.25.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Request ID Generation
    - **IMPORTANT**: Re-run the SAME test from task 1.30
    - Run missing request ID exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms request ID generation)
    - _Requirements: 2.31_

  - [x] 3.25.3 Verify preservation tests still pass
    - **Property 2: Preservation** - System Operations
    - Run preservation tests for system operations
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.26 Fix page number validation vulnerability

  - [x] 3.26.1 Implement page number positive integer validation
    - Update `app/Http/Requests/PaginationRequest.php` (already created in 3.6.1)
    - Ensure validation: `'page' => ['nullable', 'integer', 'min:1']`
    - Apply to all routes that accept page parameter
    - _Bug_Condition: input.param == 'page' AND NOT positiveIntegerValidated(input.value)_
    - _Expected_Behavior: Reject non-positive page numbers with validation error_
    - _Preservation: Valid pagination parameters continue to paginate results_
    - _Requirements: 2.32_

  - [x] 3.26.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Page Number Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.31
    - Run page number validation exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms positive integer validation)
    - _Requirements: 2.32_

  - [x] 3.26.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Pagination
    - Run preservation tests for valid pagination
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [x] 3.27 Fix date range logical validation vulnerability

  - [x] 3.27.1 Implement date range logical validation
    - Create `app/Http/Requests/DateRangeRequest.php` or add to existing request classes
    - Add validation: `'start_date' => ['required', 'date']`
    - Add validation: `'end_date' => ['required', 'date', 'after_or_equal:start_date']`
    - Implement `withValidator()` for logical validation
    - Apply to all routes that accept date range parameters
    - _Bug_Condition: input.dateRange AND NOT logicallyValidated(input.start, input.end)_
    - _Expected_Behavior: Reject illogical date ranges with validation error_
    - _Preservation: Valid date ranges continue to filter results correctly_
    - _Requirements: 2.33_

  - [x] 3.27.2 Verify exploration test now passes
    - **Property 1: Expected Behavior** - Date Range Logical Validation
    - **IMPORTANT**: Re-run the SAME test from task 1.32
    - Run date range logical validation exploration test
    - **EXPECTED OUTCOME**: Test PASSES (confirms logical validation)
    - _Requirements: 2.33_

  - [x] 3.27.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Valid Date Ranges
    - Run preservation tests for valid date ranges
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

## Phase 4: Final Validation and Comprehensive Testing

- [x] 4.1 Run comprehensive test suite
  - Run all unit tests
  - Run all property-based tests
  - Run all integration tests
  - Verify all 32 exploration tests pass (confirming all vulnerabilities fixed)
  - Verify all 5 preservation test groups pass (confirming no regressions)
  - _Requirements: All requirements 2.1-2.33, 3.1-3.20_

- [x] 4.2 Perform security validation
  - Verify all 40 security vulnerabilities are addressed
  - Verify all validation rules are enforced
  - Verify all authorization checks are in place
  - Verify all sanitization is applied
  - Verify all security events are logged
  - Verify error handling is secure
  - _Requirements: All requirements 2.1-2.33_

- [x] 4.3 Verify preservation of existing functionality
  - Test valid biography content within limits
  - Test valid URLs from whitelisted domains
  - Test authorized user operations
  - Test valid data display
  - Test valid business logic operations
  - Test valid system operations
  - Confirm no breaking changes for legitimate users
  - _Requirements: All requirements 3.1-3.20_

- [x] 4.4 Review security logging and monitoring
  - Verify failed login attempts are logged
  - Verify authorization failures are logged
  - Verify security log format is consistent
  - Verify log entries contain all required information
  - Test log analysis for security monitoring
  - _Requirements: 2.26, 2.27_

- [x] 4.5 Checkpoint - Ensure all tests pass
  - Confirm all 40 vulnerabilities are fixed
  - Confirm all tests pass
  - Confirm no regressions in existing functionality
  - Ask the user if questions arise

## Summary

This implementation plan systematically addresses all 40 security vulnerabilities using the bug condition methodology:

- **32 Exploration Tests** (Phase 1): Surface all vulnerabilities on unfixed code
- **5 Preservation Test Groups** (Phase 2): Capture baseline behavior to preserve
- **27 Implementation Tasks** (Phase 3): Fix all vulnerabilities with proper validation, authorization, sanitization, and logging
- **5 Final Validation Tasks** (Phase 4): Comprehensive testing and verification

The plan follows a phased approach prioritizing critical vulnerabilities first (input validation and authorization), followed by high-priority issues (sanitization and logging), medium-priority validation logic, and finally best practice improvements.
