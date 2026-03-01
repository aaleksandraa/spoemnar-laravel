# Requirements Document

## Introduction

Ova funkcionalnost štiti Laravel aplikaciju od automatskih bot napada koji traže ranjive PHP datoteke (WordPress exploit skeneri, generički PHP exploit skeneri) i optimizuje logiranje kako bi se odvojili stvarni application errors od security events. Cilj je smanjiti opterećenje aplikacije, očistiti logove od spam grešaka, i omogućiti efikasno praćenje stvarnih problema u produkciji.

## Glossary

- **Bot_Attack_Detector**: Middleware komponenta koja identifikuje malicious bot zahteve na osnovu URL pattern-a
- **Security_Logger**: Logging komponenta koja logira security events odvojeno od application errors
- **IP_Blocker**: Komponenta koja blokira IP adrese koje šalju malicious zahteve
- **Rate_Limiter**: Komponenta koja ograničava broj zahteva sa određene IP adrese
- **Malicious_Request**: HTTP zahtev koji traži poznate exploit datoteke ili pokazuje bot attack pattern
- **Exploit_Pattern**: URL pattern koji odgovara poznatim exploit pokušajima (wp-login.php, server.php, itd.)
- **Application_Error**: Greška koja nastaje u normalnom radu aplikacije i zahteva developer attention
- **Security_Event**: Događaj koji ukazuje na security threat ali nije application error
- **Request_Fingerprint**: Kombinacija IP adrese, User-Agent-a i request pattern-a koja identifikuje zahtev

## Requirements

### Requirement 1: Detect Malicious Bot Requests

**User Story:** Kao system administrator, želim da aplikacija automatski detektuje malicious bot zahteve, kako bi se smanjilo opterećenje i očistili logovi od spam grešaka.

#### Acceptance Criteria

1. WHEN a request URL contains known exploit patterns (wp-login.php, wp-content, server.php, test1.php, f.php, lock.php, elp.php, buy.php, alfa.php, wp-corn-sample.php), THE Bot_Attack_Detector SHALL identify it as a Malicious_Request
2. WHEN a request URL contains .php extension AND the file does not exist in the Laravel application, THE Bot_Attack_Detector SHALL identify it as a Malicious_Request
3. WHEN a request URL contains multiple consecutive slashes or path traversal patterns (../, ..\), THE Bot_Attack_Detector SHALL identify it as a Malicious_Request
4. THE Bot_Attack_Detector SHALL maintain a configurable list of Exploit_Patterns that can be updated without code changes
5. WHEN a Malicious_Request is detected, THE Bot_Attack_Detector SHALL prevent the request from reaching the application routing layer

### Requirement 2: Block Malicious Requests Early

**User Story:** Kao developer, želim da malicious zahtevi budu blokirani prije nego što prođu kroz kompletan middleware stack, kako bi se smanjilo opterećenje aplikacije.

#### Acceptance Criteria

1. THE Bot_Attack_Detector SHALL execute before SecurityHeaders, ForceHttps, AssignRequestId, and other application middleware
2. WHEN a Malicious_Request is detected, THE Bot_Attack_Detector SHALL return HTTP 403 Forbidden response without executing subsequent middleware
3. WHEN a Malicious_Request is blocked, THE Bot_Attack_Detector SHALL NOT trigger Laravel's exception handling system
4. THE Bot_Attack_Detector SHALL complete execution within 5 milliseconds for typical Malicious_Request detection

### Requirement 3: Rate Limit Suspicious IP Addresses

**User Story:** Kao system administrator, želim da aplikacija automatski rate-limituje IP adrese koje šalju sumnjive zahteve, kako bi se spriječili masovni napadi.

#### Acceptance Criteria

1. WHEN an IP address sends more than 10 Malicious_Requests within 1 minute, THE Rate_Limiter SHALL block all subsequent requests from that IP for 15 minutes
2. WHEN an IP address sends more than 50 Malicious_Requests within 1 hour, THE IP_Blocker SHALL block that IP address for 24 hours
3. THE Rate_Limiter SHALL use cache storage for tracking request counts per IP address
4. WHEN a rate limit is exceeded, THE Rate_Limiter SHALL return HTTP 429 Too Many Requests response
5. THE Rate_Limiter SHALL allow configuration of thresholds (requests per minute, requests per hour) via environment variables

### Requirement 4: Separate Security Logging from Application Logging

**User Story:** Kao developer, želim da security events budu logirani odvojeno od application errors, kako bih mogao lako pratiti stvarne probleme u aplikaciji.

#### Acceptance Criteria

1. WHEN a Malicious_Request is detected, THE Security_Logger SHALL log the event to the security log channel
2. WHEN a Malicious_Request is detected, THE Security_Logger SHALL NOT log the event to the default application log channel
3. THE Security_Logger SHALL log IP address, User-Agent, requested URL, timestamp, and Request_Fingerprint for each Security_Event
4. THE Security_Logger SHALL aggregate multiple identical Malicious_Requests from the same IP within 5 minutes into a single log entry with count
5. WHEN an IP address is rate-limited or blocked, THE Security_Logger SHALL log the blocking action with reason and duration

### Requirement 5: Provide IP Blocking Management

**User Story:** Kao system administrator, želim da mogu ručno blokirati ili odblokirati IP adrese, kako bih imao kontrolu nad pristupom aplikaciji.

#### Acceptance Criteria

1. THE IP_Blocker SHALL maintain a persistent list of blocked IP addresses in database storage
2. THE IP_Blocker SHALL support manual addition of IP addresses to the block list via Artisan command
3. THE IP_Blocker SHALL support manual removal of IP addresses from the block list via Artisan command
4. WHEN an IP address is on the block list, THE IP_Blocker SHALL block all requests from that IP with HTTP 403 Forbidden response
5. THE IP_Blocker SHALL support CIDR notation for blocking IP ranges (e.g., 20.63.80.0/24)
6. THE IP_Blocker SHALL allow whitelisting of trusted IP addresses that bypass all bot detection and rate limiting

### Requirement 6: Monitor and Report Bot Attack Statistics

**User Story:** Kao system administrator, želim da vidim statistiku bot napada, kako bih mogao pratiti security threats i prilagoditi zaštitu.

#### Acceptance Criteria

1. THE Security_Logger SHALL track daily statistics of blocked Malicious_Requests per IP address
2. THE Security_Logger SHALL track daily statistics of detected Exploit_Patterns
3. THE Security_Logger SHALL provide an Artisan command to display bot attack statistics for the last 7 days
4. THE Security_Logger SHALL provide an Artisan command to display top 10 attacking IP addresses by request count
5. WHEN bot attack statistics are requested, THE Security_Logger SHALL include total blocked requests, unique IP addresses, and most common Exploit_Patterns

### Requirement 7: Optimize Exception Logging for 404 Errors

**User Story:** Kao developer, želim da 404 greške za Malicious_Requests ne budu logirane kao exceptions, kako bi application log bio čist i fokusiran na stvarne probleme.

#### Acceptance Criteria

1. WHEN a NotFoundHttpException is thrown for a URL that matches Exploit_Pattern, THE Application SHALL NOT log it as an application error
2. WHEN a NotFoundHttpException is thrown for a legitimate application route, THE Application SHALL log it as an application error
3. THE Application SHALL distinguish between bot-generated 404 errors and legitimate user-generated 404 errors based on Request_Fingerprint
4. WHEN a 404 error is suppressed from application logs, THE Security_Logger SHALL still record it as a Security_Event if it matches Exploit_Pattern

### Requirement 8: Provide Configuration and Customization

**User Story:** Kao developer, želim da mogu konfigurirati bot protection behaviour bez mijenjanja koda, kako bih mogao prilagoditi zaštitu specifičnim potrebama aplikacije.

#### Acceptance Criteria

1. THE Bot_Attack_Detector SHALL read Exploit_Patterns from a configuration file that can be updated without deployment
2. THE Rate_Limiter SHALL read rate limit thresholds from environment variables (BOT_RATE_LIMIT_PER_MINUTE, BOT_RATE_LIMIT_PER_HOUR)
3. THE IP_Blocker SHALL read automatic blocking duration from environment variable (BOT_AUTO_BLOCK_DURATION_HOURS)
4. THE Security_Logger SHALL read log aggregation window from environment variable (BOT_LOG_AGGREGATION_MINUTES)
5. THE Bot_Attack_Detector SHALL support enabling/disabling bot protection via environment variable (BOT_PROTECTION_ENABLED)
6. WHERE bot protection is disabled, THE Application SHALL process all requests normally and log all errors to application log

### Requirement 9: Ensure Performance and Scalability

**User Story:** Kao developer, želim da bot protection ne utiče negativno na performance aplikacije za legitimne korisnike, kako bi user experience ostao optimalan.

#### Acceptance Criteria

1. THE Bot_Attack_Detector SHALL use in-memory pattern matching for Exploit_Pattern detection
2. THE Rate_Limiter SHALL use Redis cache for storing IP request counts when Redis is available
3. WHILE Redis is not available, THE Rate_Limiter SHALL use file-based cache as fallback
4. THE IP_Blocker SHALL cache the blocked IP list in memory for 5 minutes to avoid repeated database queries
5. THE Bot_Attack_Detector SHALL add less than 2 milliseconds of latency to legitimate requests
6. THE Security_Logger SHALL use asynchronous logging to avoid blocking request processing

### Requirement 10: Integrate with Existing Security Infrastructure

**User Story:** Kao developer, želim da bot protection radi zajedno sa postojećim security middleware, kako bi se održala konzistentnost security layer-a.

#### Acceptance Criteria

1. THE Bot_Attack_Detector SHALL execute after CORS middleware but before SecurityHeaders middleware
2. THE Bot_Attack_Detector SHALL preserve existing AssignRequestId functionality for Security_Events
3. WHEN a Malicious_Request is blocked, THE Bot_Attack_Detector SHALL still apply SecurityHeaders to the response
4. THE Bot_Attack_Detector SHALL respect existing rate limiters for password reset and other application features
5. THE IP_Blocker SHALL NOT interfere with Laravel's built-in throttle middleware for API routes
