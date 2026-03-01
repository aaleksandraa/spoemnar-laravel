# Bugfix Requirements Document

## Introduction

During a comprehensive security audit of the memorial application, 8 additional security vulnerabilities were identified that require immediate remediation. These vulnerabilities range from critical issues like mass assignment exploitation and XSS attacks to medium-severity concerns like incomplete rate limiting and cookie management. This bugfix addresses all identified vulnerabilities to ensure the application meets security best practices and protects user data and system integrity.

The vulnerabilities span multiple security domains:
- Input validation and sanitization (mass assignment, XSS)
- Authorization and access control (admin self-deletion, tribute moderation)
- Resource management (image upload limits, rate limiting)
- Session management (cookie clearing on logout)

## Bug Analysis

### Current Behavior (Defect)

#### 1. Mass Assignment Vulnerability

1.1 WHEN attacker sends POST /api/v1/memorials/{memorial}/tributes with additional fields like {"author_name": "Test", "message": "Test", "is_approved": true, "memorial_id": 999} THEN the system may accept unauthorized fields if Tribute model lacks proper $fillable protection

1.2 WHEN tribute is created via TributeController::store() THEN the system uses $request->input() for each field individually but doesn't prevent mass assignment if model is misconfigured

1.3 WHEN Tribute model has $guarded = [] or missing $fillable THEN attacker can manipulate any database column including is_approved, memorial_id, and timestamps

#### 2. Admin Self-Deletion Prevention Incomplete

2.1 WHEN admin calls DELETE /api/v1/admin/users/{own-id} THEN the system prevents deletion with 422 error

2.2 WHEN admin uses different route or direct model manipulation THEN the system may allow self-deletion bypassing the check in deleteUser() method

2.3 WHEN last admin account is deleted through alternative means THEN the system loses all administrative access with no recovery path

#### 3. Missing Input Sanitization for User-Generated Content

3.1 WHEN user submits memorial with biography containing <script>alert('XSS')</script> THEN the system stores raw HTML without sanitization

3.2 WHEN tribute message contains malicious JavaScript like <img src=x onerror="alert('XSS')"> THEN the system stores it without filtering

3.3 WHEN contact form subject contains HTML tags or JavaScript THEN the system processes without sanitization, enabling stored XSS attacks

#### 4. Image Upload Without Size Limit

4.1 WHEN user uploads 100MB image file THEN ImageService::upload() processes it without size validation

4.2 WHEN multiple large images are uploaded consecutively THEN disk space can be exhausted leading to application failure

4.3 WHEN image processing occurs on extremely large files THEN memory overflow can crash the application or cause denial of service

#### 5. Tribute Deletion Missing Admin Authorization

5.1 WHEN admin tries DELETE /api/v1/tributes/{tribute} for inappropriate content THEN the system returns 403 Forbidden if admin is not memorial owner

5.2 WHEN admin needs to moderate tributes THEN must use separate endpoint /api/v1/admin/tributes/{tribute} creating inconsistent API design

5.3 WHEN standard tribute deletion endpoint is used THEN admin privileges are not recognized, limiting content moderation capabilities

#### 6. Undefined Rate Limiters for Password Reset

6.1 WHEN user sends POST /api/v1/forgot-password THEN middleware('throttle:password-reset-link') is applied but limiter is not defined in RouteServiceProvider

6.2 WHEN user sends POST /api/v1/reset-password THEN middleware('throttle:password-reset-submit') is applied but limiter is not defined in RouteServiceProvider

6.3 WHEN custom rate limiters are undefined THEN throttling may fall back to default limits or not work as expected, enabling password reset abuse

#### 7. Logout Does Not Clear Auth Cookie

7.1 WHEN user calls POST /api/v1/logout THEN token is deleted from database but auth_token cookie remains in browser

7.2 WHEN logout response is sent THEN auth_token cookie persists with expired token value accessible to JavaScript

7.3 WHEN user checks browser cookies after logout THEN auth_token cookie is still present, creating confusion and potential security concerns

#### 8. Search Endpoint Without Rate Limiting

8.1 WHEN attacker sends 1000 GET /api/v1/search requests in 1 minute THEN all requests are processed without throttling

8.2 WHEN search queries are complex with multiple filters THEN database performance degrades significantly

8.3 WHEN search endpoint is abused THEN application becomes slow for all users, enabling denial of service attacks

### Expected Behavior (Correct)

#### 1. Mass Assignment Protection

2.1 WHEN tribute is created THEN Tribute model SHALL have strict $fillable array with only ['memorial_id', 'author_name', 'author_email', 'message']

2.2 WHEN attacker sends additional fields like is_approved or created_at THEN Laravel SHALL ignore fields not in $fillable array

2.3 WHEN TributeController creates tribute THEN only validated and explicitly allowed fields SHALL be assigned to the model

#### 2. Admin Self-Deletion Prevention Complete

2.4 WHEN admin attempts to delete own account via any route THEN the system SHALL prevent deletion with 403 Forbidden error

2.5 WHEN checking if user can be deleted THEN the system SHALL verify user is not the requesting admin in all deletion paths

2.6 WHEN last admin account deletion is attempted THEN the system SHALL prevent it and return error message suggesting creating another admin first

#### 3. Input Sanitization Implementation

2.7 WHEN user submits biography with HTML THEN the system SHALL sanitize using strip_tags or HTML Purifier allowing only safe tags

2.8 WHEN tribute message is submitted THEN the system SHALL remove potentially dangerous HTML/JavaScript while preserving safe formatting

2.9 WHEN contact form is submitted THEN the system SHALL sanitize all text inputs removing script tags and dangerous attributes

#### 4. Image Upload Size Limits

2.10 WHEN image is uploaded THEN StoreImageRequest SHALL validate max file size of 5MB (5120 KB)

2.11 WHEN file exceeds size limit THEN the system SHALL return 422 validation error with clear message

2.12 WHEN ImageService::upload() is called THEN file size SHALL be verified before processing to prevent resource exhaustion

#### 5. Tribute Deletion Admin Authorization

2.13 WHEN admin calls DELETE /api/v1/tributes/{tribute} THEN the system SHALL allow deletion regardless of memorial ownership

2.14 WHEN checking authorization in TributePolicy THEN the system SHALL recognize admin role and grant deletion permission

2.15 WHEN non-admin non-owner attempts deletion THEN the system SHALL return 403 Forbidden as before

#### 6. Password Reset Rate Limiters Defined

2.16 WHEN forgot-password endpoint is called THEN the system SHALL apply rate limit of 3 requests per hour per email

2.17 WHEN reset-password endpoint is called THEN the system SHALL apply rate limit of 5 requests per hour per email

2.18 WHEN rate limiters are defined in RouteServiceProvider::configureRateLimiting() THEN throttling SHALL work correctly preventing abuse

#### 7. Logout Cookie Clearing

2.19 WHEN user logs out THEN the system SHALL delete token from database

2.20 WHEN logout response is sent THEN the system SHALL clear auth_token cookie by setting expiration to past date

2.21 WHEN user checks browser after logout THEN auth_token cookie SHALL be removed completely

#### 8. Search Rate Limiting

2.22 WHEN user searches THEN the system SHALL apply rate limit of 60 requests per minute per IP

2.23 WHEN rate limit is exceeded THEN the system SHALL return 429 Too Many Requests with Retry-After header

2.24 WHEN legitimate users search within limits THEN performance SHALL remain acceptable

### Unchanged Behavior (Regression Prevention)

#### Tribute Functionality

3.1 WHEN valid tribute is submitted within rate limits THEN the system SHALL CONTINUE TO create tribute successfully with all fields

3.2 WHEN memorial owner deletes tribute THEN the system SHALL CONTINUE TO allow deletion without admin privileges

3.3 WHEN tribute data is retrieved via GET /api/v1/tributes/{tribute} THEN the system SHALL CONTINUE TO return correct information

3.4 WHEN tributes are listed for a memorial THEN the system SHALL CONTINUE TO return all approved tributes

#### Admin Functionality

3.5 WHEN admin deletes other users (non-self) THEN the system SHALL CONTINUE TO allow deletion successfully

3.6 WHEN admin manages memorials via admin endpoints THEN the system SHALL CONTINUE TO have full access

3.7 WHEN admin uses dedicated admin endpoints like /api/v1/admin/tributes/{tribute} THEN the system SHALL CONTINUE TO work correctly

3.8 WHEN admin views user list THEN the system SHALL CONTINUE TO return all users with proper pagination

#### Image Upload Functionality

3.9 WHEN valid images under 5MB are uploaded THEN the system SHALL CONTINUE TO process successfully and store in correct location

3.10 WHEN images are deleted via memorial or tribute deletion THEN the system SHALL CONTINUE TO remove files correctly from storage

3.11 WHEN images are displayed in memorial or tribute views THEN the system SHALL CONTINUE TO serve correct URLs

3.12 WHEN image validation fails for type or dimensions THEN the system SHALL CONTINUE TO return appropriate validation errors

#### Authentication Functionality

3.13 WHEN user logs in with valid credentials THEN the system SHALL CONTINUE TO create session and return auth token

3.14 WHEN user logs out THEN the system SHALL CONTINUE TO invalidate token in database

3.15 WHEN password reset is requested with valid email THEN the system SHALL CONTINUE TO send reset email

3.16 WHEN password reset token is valid THEN the system SHALL CONTINUE TO allow password change

#### Search Functionality

3.17 WHEN user searches for memorials with query parameter THEN the system SHALL CONTINUE TO return filtered results

3.18 WHEN search query is empty THEN the system SHALL CONTINUE TO return all public memorials

3.19 WHEN search filters (date_from, date_to) are applied THEN the system SHALL CONTINUE TO apply correctly

3.20 WHEN search results are paginated THEN the system SHALL CONTINUE TO return correct page data

#### Content Display

3.21 WHEN biography is displayed after sanitization THEN the system SHALL CONTINUE TO show formatted text without breaking layout

3.22 WHEN tribute messages are shown THEN the system SHALL CONTINUE TO display correctly with proper formatting

3.23 WHEN HTML entities are used in content THEN the system SHALL CONTINUE TO render properly without double-encoding

3.24 WHEN line breaks and basic formatting exist THEN the system SHALL CONTINUE TO preserve them after sanitization
