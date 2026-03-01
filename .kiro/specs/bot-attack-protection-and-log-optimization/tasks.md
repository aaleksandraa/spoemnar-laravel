# Implementation Plan: Bot Attack Protection and Log Optimization

## Overview

Ovaj plan implementira sistem za zaštitu od bot napada i optimizaciju logovanja za Laravel aplikaciju spomenar.com. Sistem će detektovati i blokirati malicious bot zahteve koji traže WordPress i PHP exploit fajlove, rate-limitovati sumnjive IP adrese, i odvojiti security events od application errors u log fajlovima.

Implementacija se sastoji od middleware komponenti, service klasa, repository pattern-a, Eloquent modela, Artisan komandi, i modifikacije exception handler-a. Sistem je dizajniran za minimalan performance overhead i graceful degradation.

## Tasks

- [x] 1. Setup database schema and models
  - [x] 1.1 Create blocked_ips migration
    - Create migration file with schema: id, ip_address (unique, 45 chars), reason, blocked_at, expires_at (nullable), is_auto_blocked (boolean), malicious_request_count (integer), timestamps
    - Add indexes: ip_address, expires_at, composite (ip_address, expires_at)
    - _Requirements: 5.1_
  
  - [x] 1.2 Create BlockedIp Eloquent model
    - Define fillable fields: ip_address, reason, blocked_at, expires_at, is_auto_blocked, malicious_request_count
    - Add casts: blocked_at (datetime), expires_at (datetime), is_auto_blocked (boolean)
    - Implement methods: isExpired(), isPermanent()
    - Implement scopes: active(), expired()
    - _Requirements: 5.1_
  
  - [ ]* 1.3 Write property test for BlockedIp model
    - **Property 9: IP Block List Enforcement**
    - **Validates: Requirements 5.4**

- [x] 2. Create configuration files
  - [x] 2.1 Create config/security.php configuration file
    - Define bot_protection array with enabled flag (default: true)
    - Define rate_limiting thresholds: per_minute (10), per_hour (50), auto_block_duration_hours (24)
    - Define logging settings: aggregation_minutes (5), channel ('security')
    - Define exploit_patterns array with all patterns from design (wp-login.php, wp-content, server.php, test1.php, f.php, lock.php, elp.php, buy.php, alfa.php, wp-corn-sample.php, adminer.php, phpmyadmin, .env, config.php, shell.php, c99.php, r57.php, eval-stdin.php)
    - Define whitelist array for trusted IPs
    - _Requirements: 1.4, 8.1, 8.2, 8.3, 8.4, 8.5_
  
  - [x] 2.2 Add security log channel to config/logging.php
    - Add 'security' channel with daily driver
    - Set path to storage/logs/security.log
    - Set level from SECURITY_LOG_LEVEL env variable (default: 'warning')
    - Set days from SECURITY_LOG_DAILY_DAYS env variable (default: 90)
    - _Requirements: 4.1_
  
  - [x] 2.3 Add environment variables to .env.example
    - Add BOT_PROTECTION_ENABLED=true
    - Add BOT_RATE_LIMIT_PER_MINUTE=10
    - Add BOT_RATE_LIMIT_PER_HOUR=50
    - Add BOT_AUTO_BLOCK_DURATION_HOURS=24
    - Add BOT_LOG_AGGREGATION_MINUTES=5
    - Add SECURITY_LOG_LEVEL=warning
    - Add SECURITY_LOG_DAILY_DAYS=90
    - _Requirements: 8.2, 8.3, 8.4_

- [x] 3. Implement core service classes
  - [x] 3.1 Create ExploitPatternMatcher service
    - Create App\Services\Security\ExploitPatternMatcher class
    - Implement constructor that accepts patterns array
    - Implement matches(string $url): bool method with pattern matching logic
    - Implement getMatchedPattern(string $url): ?string method
    - Implement private compilePatterns(): array method for regex compilation
    - Cache compiled patterns in memory
    - _Requirements: 1.1, 1.4_
  
  - [ ]* 3.2 Write property test for ExploitPatternMatcher
    - **Property 1: Malicious Pattern Detection**
    - **Validates: Requirements 1.1, 1.2, 1.3**
  
  - [x] 3.3 Create SecurityLogger service
    - Create App\Services\Security\SecurityLogger class
    - Inject LogManager and Cache dependencies
    - Implement logMaliciousRequest(Request $request, string $pattern): void
    - Implement logIpBlocked(string $ip, string $reason, int $duration): void
    - Implement logRateLimitExceeded(string $ip, int $attempts): void
    - Implement private getRequestFingerprint(Request $request): string using md5(ip|url|pattern)
    - Implement private shouldAggregate(string $fingerprint): bool
    - Implement private incrementAggregateCount(string $fingerprint): void
    - Use cache key format: bot:log_aggregate:{fingerprint}
    - Log to 'security' channel with JSON format
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_
  
  - [ ]* 3.4 Write property tests for SecurityLogger
    - **Property 5: Security Log Channel Separation**
    - **Property 6: Security Event Log Completeness**
    - **Property 7: Log Aggregation**
    - **Property 8: Blocking Action Logging**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**
  
  - [x] 3.5 Create BlockedIpRepository
    - Create App\Repositories\BlockedIpRepository class
    - Inject BlockedIp model dependency
    - Implement isBlocked(string $ip): bool with CIDR support
    - Implement block(string $ip, string $reason, ?int $duration = null): BlockedIp
    - Implement unblock(string $ip): bool
    - Implement getAll(): Collection
    - Implement getActive(): Collection using active() scope
    - Implement removeExpired(): int using expired() scope
    - Implement private matchesCidr(string $ip, string $cidr): bool
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  
  - [ ]* 3.6 Write property tests for BlockedIpRepository
    - **Property 9: IP Block List Enforcement**
    - **Property 10: CIDR Range Blocking**
    - **Validates: Requirements 5.4, 5.5**
  
  - [x] 3.7 Create RateLimiter service
    - Create App\Services\Security\RateLimiter class
    - Inject Cache and BlockedIpRepository dependencies
    - Implement recordMaliciousRequest(string $ip): void
    - Implement isRateLimited(string $ip): bool
    - Implement getRemainingAttempts(string $ip): int
    - Implement private incrementCounter(string $ip, string $window): int
    - Implement private shouldAutoBlock(string $ip): bool (check per_minute and per_hour thresholds)
    - Implement private autoBlockIp(string $ip, int $duration): void
    - Use cache keys: bot:rate_limit:{ip}:minute and bot:rate_limit:{ip}:hour
    - Set TTL: 60 seconds for minute counter, 3600 seconds for hour counter
    - _Requirements: 3.1, 3.2, 3.3, 3.5_
  
  - [ ]* 3.8 Write property test for RateLimiter
    - **Property 4: Rate Limit Response Code**
    - **Validates: Requirements 3.4**

- [x] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implement middleware components
  - [x] 5.1 Create IpBlocker middleware
    - Create App\Http\Middleware\IpBlocker class
    - Inject BlockedIpRepository and Cache dependencies
    - Implement handle(Request $request, Closure $next): Response
    - Implement private isIpBlocked(string $ip): bool
    - Implement private isIpWhitelisted(string $ip): bool
    - Implement private matchesCidrRange(string $ip, string $cidr): bool
    - Implement private getBlockedIpList(): Collection with 5-minute cache
    - Use cache key: bot:blocked_ips
    - Return 403 Forbidden for blocked IPs
    - Check whitelist before blocking
    - _Requirements: 5.4, 5.5, 5.6_
  
  - [ ]* 5.2 Write property tests for IpBlocker middleware
    - **Property 9: IP Block List Enforcement**
    - **Property 10: CIDR Range Blocking**
    - **Property 11: Whitelist Bypass**
    - **Validates: Requirements 5.4, 5.5, 5.6**
  
  - [x] 5.3 Create BotAttackDetector middleware
    - Create App\Http\Middleware\BotAttackDetector class
    - Inject ExploitPatternMatcher, RateLimiter, and SecurityLogger dependencies
    - Implement handle(Request $request, Closure $next): Response
    - Implement private isMaliciousRequest(Request $request): bool
    - Implement private matchesExploitPattern(string $url): bool
    - Implement private isNonExistentPhpFile(string $url): bool
    - Implement private hasPathTraversal(string $url): bool (check for ../ and ..\)
    - Check BOT_PROTECTION_ENABLED config flag
    - Call RateLimiter->recordMaliciousRequest() for malicious requests
    - Call SecurityLogger->logMaliciousRequest() for malicious requests
    - Return 403 Forbidden for malicious requests
    - Return 429 Too Many Requests if rate limited
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.2, 2.3, 8.5, 8.6_
  
  - [ ]* 5.4 Write property tests for BotAttackDetector middleware
    - **Property 1: Malicious Pattern Detection**
    - **Property 2: Early Request Blocking**
    - **Property 3: No Exception Throwing for Malicious Requests**
    - **Property 4: Rate Limit Response Code**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.5, 2.2, 2.3, 3.4**
  
  - [x] 5.5 Register middleware in bootstrap/app.php
    - Add IpBlocker to global middleware stack after CORS
    - Add BotAttackDetector to global middleware stack after IpBlocker
    - Ensure execution order: CORS → IpBlocker → BotAttackDetector → SecurityHeaders → ForceHttps → AssignRequestId
    - _Requirements: 2.1, 10.1_
  
  - [ ]* 5.6 Write integration test for middleware execution order
    - **Property 2: Early Request Blocking**
    - **Property 17: Security Headers on Blocked Requests**
    - **Validates: Requirements 2.1, 2.2, 10.3**

- [x] 6. Implement Artisan commands
  - [x] 6.1 Create bot:block command
    - Create App\Console\Commands\BlockIpCommand class
    - Define signature: 'bot:block {ip} {--reason=} {--duration=}'
    - Define description: 'Block an IP address'
    - Inject BlockedIpRepository dependency
    - Implement handle() method to block IP with provided reason and duration
    - Validate IP address format (IPv4 and IPv6)
    - Support CIDR notation
    - Display success/error message
    - _Requirements: 5.2_
  
  - [x] 6.2 Create bot:unblock command
    - Create App\Console\Commands\UnblockIpCommand class
    - Define signature: 'bot:unblock {ip}'
    - Define description: 'Unblock an IP address'
    - Inject BlockedIpRepository dependency
    - Implement handle() method to unblock IP
    - Display success/error message
    - _Requirements: 5.3_
  
  - [x] 6.3 Create bot:list command
    - Create App\Console\Commands\ListBlockedIpsCommand class
    - Define signature: 'bot:list {--active}'
    - Define description: 'List blocked IP addresses'
    - Inject BlockedIpRepository dependency
    - Implement handle() method to display blocked IPs in table format
    - Show columns: IP Address, Reason, Blocked At, Expires At, Auto-Blocked, Request Count
    - Filter by active flag if provided
    - _Requirements: 5.1_
  
  - [x] 6.4 Create bot:cleanup command
    - Create App\Console\Commands\CleanupBlockedIpsCommand class
    - Define signature: 'bot:cleanup'
    - Define description: 'Remove expired IP blocks'
    - Inject BlockedIpRepository dependency
    - Implement handle() method to call removeExpired()
    - Display count of removed entries
    - _Requirements: 5.1_
  
  - [x] 6.5 Create BotStatisticsService
    - Create App\Services\Security\BotStatisticsService class
    - Inject SecurityLogger dependency
    - Implement getStatistics(int $days = 7): array
    - Implement getTopAttackingIps(int $limit = 10): Collection
    - Implement getMostCommonPatterns(int $limit = 10): Collection
    - Implement getDailyStats(int $days = 7): Collection
    - Parse security log file to extract statistics
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_
  
  - [x] 6.6 Create bot:stats command
    - Create App\Console\Commands\BotStatsCommand class
    - Define signature: 'bot:stats {--days=7}'
    - Define description: 'Display bot attack statistics'
    - Inject BotStatisticsService dependency
    - Implement handle() method to display statistics
    - Show: total blocked requests, unique IP addresses, most common patterns, top attacking IPs
    - Display daily breakdown in table format
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_
  
  - [ ]* 6.7 Write property tests for statistics
    - **Property 12: Statistics Aggregation Accuracy**
    - **Property 13: Statistics Completeness**
    - **Validates: Requirements 6.1, 6.2, 6.5**
  
  - [ ]* 6.8 Write unit tests for Artisan commands
    - Test bot:block command with valid/invalid IPs
    - Test bot:unblock command
    - Test bot:list command with/without --active flag
    - Test bot:cleanup command
    - Test bot:stats command with different --days values

- [x] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. Modify exception handler for 404 filtering
  - [ ] 8.1 Update bootstrap/app.php exception handling
    - Add withExceptions callback
    - Register render callback for NotFoundHttpException
    - Check if URL matches exploit pattern using ExploitPatternMatcher
    - If matches: log to security channel via SecurityLogger, return 404 without application log
    - If not matches: return null to let Laravel handle normally
    - _Requirements: 7.1, 7.2, 7.3, 7.4_
  
  - [ ]* 8.2 Write property tests for 404 filtering
    - **Property 14: Bot-Generated 404 Suppression**
    - **Property 15: Legitimate 404 Logging**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4**

- [x] 9. Integration and wiring
  - [x] 9.1 Register service providers and bindings
    - Bind ExploitPatternMatcher in AppServiceProvider with config patterns
    - Bind SecurityLogger as singleton
    - Bind RateLimiter as singleton
    - Bind BlockedIpRepository
    - Bind BotStatisticsService
    - _Requirements: All_
  
  - [x] 9.2 Add service provider boot logic
    - Load config/security.php configuration
    - Ensure security log channel is available
    - Cache exploit patterns on boot for performance
    - _Requirements: 1.4, 9.1_
  
  - [ ]* 9.3 Write integration tests for complete flow
    - Test malicious request → detection → rate limiting → logging → blocking
    - Test legitimate request → pass through normally
    - Test whitelisted IP → bypass all checks
    - Test rate limit exceeded → auto-blocking
    - Test 404 for exploit pattern → security log only
    - Test 404 for legitimate route → application log
    - **Property 16: Request ID Preservation**
    - **Property 17: Security Headers on Blocked Requests**
    - **Property 18: Application Rate Limiter Independence**
    - **Property 19: API Throttle Middleware Independence**
    - **Validates: Requirements 10.2, 10.3, 10.4, 10.5**

- [x] 10. Performance optimization and caching
  - [x] 10.1 Implement in-memory pattern caching
    - Cache compiled exploit patterns in ExploitPatternMatcher
    - Ensure patterns are loaded once per request lifecycle
    - _Requirements: 9.1_
  
  - [x] 10.2 Implement blocked IP list caching
    - Cache blocked IP list in IpBlocker with 5-minute TTL
    - Use cache key: bot:blocked_ips
    - Invalidate cache when IP is blocked/unblocked
    - _Requirements: 9.4_
  
  - [x] 10.3 Optimize rate limiter cache usage
    - Use Redis for rate limit counters when available
    - Fallback to file cache if Redis unavailable
    - Set appropriate TTLs: 60s for minute counter, 3600s for hour counter
    - _Requirements: 9.2, 9.3_
  
  - [ ]* 10.4 Write performance tests
    - Test middleware latency < 2ms for legitimate requests
    - Test middleware latency < 5ms for malicious detection
    - Test graceful degradation when cache unavailable
    - Test graceful degradation when database unavailable
    - **Validates: Requirements 2.4, 9.5**

- [x] 11. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties (19 properties total)
- Unit tests validate specific examples and edge cases
- Integration tests validate end-to-end flows
- System is designed for graceful degradation (fail open, not fail closed)
- Performance targets: < 2ms latency for legitimate requests, < 5ms for malicious detection
- Cache strategy: Redis preferred, file cache as fallback
- Middleware execution order is critical: CORS → IpBlocker → BotAttackDetector → SecurityHeaders → ForceHttps → AssignRequestId
