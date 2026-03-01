# Design Document: Bot Attack Protection and Log Optimization

## Overview

Ovaj design dokument definiše arhitekturu i implementaciju sistema za zaštitu od bot napada i optimizaciju logovanja za Laravel aplikaciju spomenar.com. Sistem će detektovati i blokirati malicious bot zahteve koji traže WordPress i PHP exploit fajlove, rate-limitovati sumnjive IP adrese, i odvojiti security events od application errors u log fajlovima.

Glavni ciljevi sistema su:
- Smanjiti opterećenje aplikacije blokiranjem malicious zahteva prije nego što stignu do routing layer-a
- Očistiti production logove od spam 404 grešaka generisanih od bot-ova
- Omogućiti efikasno praćenje stvarnih application errors
- Pružiti alate za monitoring i upravljanje bot napadima

Sistem se sastoji od četiri glavne komponente:
1. **BotAttackDetector** middleware - detektuje malicious patterns u URL-ovima
2. **IpBlocker** middleware - blokira poznate malicious IP adrese
3. **SecurityLogger** - logira security events odvojeno od application errors
4. **BotManagement** Artisan komande - upravljanje IP block listom i statistikom

## Architecture

### System Architecture

Sistem je dizajniran kao set middleware komponenti koje se izvršavaju rano u Laravel request lifecycle-u, prije nego što zahtev stigne do routing layer-a. Ovo omogućava brzo odbacivanje malicious zahteva sa minimalnim overhead-om.

```
Request Flow:
┌─────────────────┐
│  HTTP Request   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  CORS Middleware│
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│  IpBlocker Middleware   │◄──── Database: blocked_ips table
│  (Check blocked IPs)    │      Cache: blocked_ips_cache (5 min)
└────────┬────────────────┘
         │ (if not blocked)
         ▼
┌──────────────────────────────┐
│  BotAttackDetector Middleware│
│  (Pattern matching)          │
└────────┬─────────────────────┘
         │
         ├─(malicious)──────────┐
         │                      │
         │                      ▼
         │              ┌──────────────────┐
         │              │ Rate Limiter     │◄──── Cache: rate_limit:{ip}
         │              │ (Track attempts) │
         │              └────────┬─────────┘
         │                       │
         │                       ▼
         │              ┌──────────────────┐
         │              │ SecurityLogger   │────► storage/logs/security.log
         │              │ (Log event)      │
         │              └────────┬─────────┘
         │                       │
         │                       ▼
         │              ┌──────────────────┐
         │              │ Return 403/429   │
         │              └──────────────────┘
         │
         │ (legitimate)
         ▼
┌─────────────────┐
│ SecurityHeaders │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ ForceHttps      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ AssignRequestId │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Application     │
│ Routing         │
└─────────────────┘
```

### Middleware Execution Order

Middleware se izvršava u sledećem redosledu:
1. **CORS** - omogućava cross-origin requests
2. **IpBlocker** - blokira poznate malicious IP adrese (najbrža provjera)
3. **BotAttackDetector** - detektuje malicious patterns (pattern matching)
4. **SecurityHeaders** - dodaje security headers
5. **ForceHttps** - forsira HTTPS
6. **EnforceRequestSizeLimit** - provjerava veličinu zahteva
7. **AssignRequestId** - dodeljuje request ID

### Performance Considerations

- **In-memory pattern matching**: Exploit patterns se učitavaju u memoriju pri boot-u
- **Cache-first strategy**: IP block list i rate limit counters se čuvaju u cache-u
- **Redis preferred**: Redis se koristi za cache kada je dostupan, file cache kao fallback
- **Database for persistence**: Blocked IP list se čuva u bazi za persistence
- **Asynchronous logging**: Security events se logiraju asinhrono da ne blokiraju request
- **Target latency**: < 2ms za legitimate requests, < 5ms za malicious detection

## Components and Interfaces

### 1. BotAttackDetector Middleware

Middleware komponenta koja detektuje malicious bot zahteve na osnovu URL pattern-a.

**Responsibilities:**
- Učitava exploit patterns iz konfiguracije
- Provjerava URL protiv poznatih exploit patterns
- Detektuje path traversal pokušaje
- Detektuje nepostojeće .php fajlove
- Poziva RateLimiter za tracking
- Poziva SecurityLogger za logiranje
- Vraća 403 Forbidden za malicious zahteve

**Interface:**
```php
namespace App\Http\Middleware;

class BotAttackDetector
{
    public function __construct(
        private ExploitPatternMatcher $patternMatcher,
        private RateLimiter $rateLimiter,
        private SecurityLogger $securityLogger
    ) {}
    
    public function handle(Request $request, Closure $next): Response;
    
    private function isMaliciousRequest(Request $request): bool;
    private function matchesExploitPattern(string $url): bool;
    private function isNonExistentPhpFile(string $url): bool;
    private function hasPathTraversal(string $url): bool;
}
```

**Configuration:**
- `BOT_PROTECTION_ENABLED` - enable/disable bot protection (default: true)
- `config/security.php` - exploit patterns list

### 2. IpBlocker Middleware

Middleware komponenta koja blokira zahteve sa poznato malicious IP adresa.

**Responsibilities:**
- Učitava blocked IP list iz cache-a/baze
- Provjerava da li je request IP na block listi
- Podržava CIDR notation za IP ranges
- Podržava whitelist za trusted IP adrese
- Vraća 403 Forbidden za blokirane IP adrese

**Interface:**
```php
namespace App\Http\Middleware;

class IpBlocker
{
    public function __construct(
        private BlockedIpRepository $blockedIpRepository,
        private Cache $cache
    ) {}
    
    public function handle(Request $request, Closure $next): Response;
    
    private function isIpBlocked(string $ip): bool;
    private function isIpWhitelisted(string $ip): bool;
    private function matchesCidrRange(string $ip, string $cidr): bool;
    private function getBlockedIpList(): Collection;
}
```

**Configuration:**
- `config/security.php` - whitelist IP addresses

### 3. ExploitPatternMatcher Service

Service klasa koja provjerava URL-ove protiv poznatih exploit patterns.

**Responsibilities:**
- Učitava patterns iz konfiguracije
- Kompajlira regex patterns za brzo matching
- Provjerava URL protiv svih patterns
- Cache-uje kompajlirane patterns

**Interface:**
```php
namespace App\Services\Security;

class ExploitPatternMatcher
{
    public function __construct(private array $patterns) {}
    
    public function matches(string $url): bool;
    public function getMatchedPattern(string $url): ?string;
    
    private function compilePatterns(): array;
}
```

**Exploit Patterns:**
```php
[
    'wp-login.php',
    'wp-content',
    'wp-admin',
    'wp-includes',
    'xmlrpc.php',
    'server.php',
    'test1.php',
    'f.php',
    'lock.php',
    'elp.php',
    'buy.php',
    'alfa.php',
    'wp-corn-sample.php',
    'adminer.php',
    'phpmyadmin',
    '.env',
    'config.php',
    'shell.php',
]
```

### 4. RateLimiter Service

Service klasa koja prati broj zahteva po IP adresi i primjenjuje rate limiting.

**Responsibilities:**
- Prati broj malicious zahteva po IP adresi
- Primjenjuje rate limiting thresholds
- Automatski blokira IP adrese koje prekorače threshold
- Koristi cache za tracking counters

**Interface:**
```php
namespace App\Services\Security;

class RateLimiter
{
    public function __construct(
        private Cache $cache,
        private BlockedIpRepository $blockedIpRepository
    ) {}
    
    public function recordMaliciousRequest(string $ip): void;
    public function isRateLimited(string $ip): bool;
    public function getRemainingAttempts(string $ip): int;
    
    private function incrementCounter(string $ip, string $window): int;
    private function shouldAutoBlock(string $ip): bool;
    private function autoBlockIp(string $ip, int $duration): void;
}
```

**Rate Limiting Rules:**
- 10 malicious requests per minute → block for 15 minutes
- 50 malicious requests per hour → block for 24 hours

**Configuration:**
- `BOT_RATE_LIMIT_PER_MINUTE` - requests per minute threshold (default: 10)
- `BOT_RATE_LIMIT_PER_HOUR` - requests per hour threshold (default: 50)
- `BOT_AUTO_BLOCK_DURATION_HOURS` - auto-block duration (default: 24)

### 5. SecurityLogger Service

Service klasa koja logira security events odvojeno od application errors.

**Responsibilities:**
- Logira malicious requests u security log channel
- Agregira identične zahteve u jednu log entry
- Logira IP blocking actions
- Logira rate limiting events
- Prati statistiku bot napada

**Interface:**
```php
namespace App\Services\Security;

class SecurityLogger
{
    public function __construct(
        private LogManager $log,
        private Cache $cache
    ) {}
    
    public function logMaliciousRequest(Request $request, string $pattern): void;
    public function logIpBlocked(string $ip, string $reason, int $duration): void;
    public function logRateLimitExceeded(string $ip, int $attempts): void;
    
    private function shouldAggregate(string $fingerprint): bool;
    private function getRequestFingerprint(Request $request): string;
    private function incrementAggregateCount(string $fingerprint): void;
}
```

**Log Format:**
```json
{
    "timestamp": "2024-01-15 10:30:45",
    "event": "malicious_request",
    "ip": "20.63.80.123",
    "user_agent": "Mozilla/5.0...",
    "url": "/wp-login.php",
    "pattern": "wp-login.php",
    "request_id": "uuid",
    "count": 5
}
```

**Configuration:**
- `BOT_LOG_AGGREGATION_MINUTES` - aggregation window (default: 5)

### 6. BlockedIpRepository

Repository klasa za upravljanje blocked IP listom u bazi.

**Responsibilities:**
- CRUD operacije za blocked IPs
- Podržava CIDR notation
- Prati razlog i trajanje blokade
- Automatski uklanja expired blokade

**Interface:**
```php
namespace App\Repositories;

class BlockedIpRepository
{
    public function __construct(private BlockedIp $model) {}
    
    public function isBlocked(string $ip): bool;
    public function block(string $ip, string $reason, ?int $duration = null): BlockedIp;
    public function unblock(string $ip): bool;
    public function getAll(): Collection;
    public function getActive(): Collection;
    public function removeExpired(): int;
    
    private function matchesCidr(string $ip, string $cidr): bool;
}
```

### 7. BotManagement Artisan Commands

Set Artisan komandi za upravljanje bot protection sistemom.

**Commands:**

**a) bot:block**
```bash
php artisan bot:block {ip} {--reason=} {--duration=}
```
Ručno blokira IP adresu.

**b) bot:unblock**
```bash
php artisan bot:unblock {ip}
```
Uklanja IP adresu sa block liste.

**c) bot:list**
```bash
php artisan bot:list {--active}
```
Prikazuje listu blokiranih IP adresa.

**d) bot:stats**
```bash
php artisan bot:stats {--days=7}
```
Prikazuje statistiku bot napada.

**e) bot:cleanup**
```bash
php artisan bot:cleanup
```
Uklanja expired IP blokade.

**Interface:**
```php
namespace App\Console\Commands;

class BlockIpCommand extends Command
{
    protected $signature = 'bot:block {ip} {--reason=} {--duration=}';
    protected $description = 'Block an IP address';
    
    public function handle(BlockedIpRepository $repository): int;
}

class UnblockIpCommand extends Command
{
    protected $signature = 'bot:unblock {ip}';
    protected $description = 'Unblock an IP address';
    
    public function handle(BlockedIpRepository $repository): int;
}

class ListBlockedIpsCommand extends Command
{
    protected $signature = 'bot:list {--active}';
    protected $description = 'List blocked IP addresses';
    
    public function handle(BlockedIpRepository $repository): int;
}

class BotStatsCommand extends Command
{
    protected $signature = 'bot:stats {--days=7}';
    protected $description = 'Display bot attack statistics';
    
    public function handle(BotStatisticsService $stats): int;
}

class CleanupBlockedIpsCommand extends Command
{
    protected $signature = 'bot:cleanup';
    protected $description = 'Remove expired IP blocks';
    
    public function handle(BlockedIpRepository $repository): int;
}
```

### 8. BotStatisticsService

Service klasa za prikupljanje i analizu statistike bot napada.

**Responsibilities:**
- Prikuplja statistiku iz security logova
- Agregira podatke po IP adresi, pattern-u, datumu
- Identifikuje top attacking IP adrese
- Identifikuje najčešće exploit patterns

**Interface:**
```php
namespace App\Services\Security;

class BotStatisticsService
{
    public function __construct(private SecurityLogger $logger) {}
    
    public function getStatistics(int $days = 7): array;
    public function getTopAttackingIps(int $limit = 10): Collection;
    public function getMostCommonPatterns(int $limit = 10): Collection;
    public function getDailyStats(int $days = 7): Collection;
}
```

### 9. Custom Exception Handler

Modifikacija postojećeg exception handler-a da filtrira bot-generated 404 errors.

**Responsibilities:**
- Detektuje da li je 404 error generisan od bot-a
- Sprečava logiranje bot-generated 404 errors u application log
- Prosljeđuje bot-generated 404 errors SecurityLogger-u

**Interface:**
```php
// U bootstrap/app.php - withExceptions callback

$exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
    // Check if this is a bot-generated 404
    if (app(ExploitPatternMatcher::class)->matches($request->path())) {
        // Log to security channel instead of application log
        app(SecurityLogger::class)->logMaliciousRequest($request, 'not_found');
        
        // Return 404 without logging to application log
        return response()->json(['message' => 'Not Found'], 404);
    }
    
    // For legitimate 404s, let Laravel handle normally
    return null;
});
```

## Data Models

### 1. BlockedIp Model

Eloquent model za blocked_ips tabelu.

**Schema:**
```php
Schema::create('blocked_ips', function (Blueprint $table) {
    $table->id();
    $table->string('ip_address', 45)->unique(); // Supports IPv4 and IPv6
    $table->string('reason')->nullable();
    $table->timestamp('blocked_at');
    $table->timestamp('expires_at')->nullable(); // NULL = permanent block
    $table->boolean('is_auto_blocked')->default(false);
    $table->integer('malicious_request_count')->default(0);
    $table->timestamps();
    
    $table->index('ip_address');
    $table->index('expires_at');
    $table->index(['ip_address', 'expires_at']);
});
```

**Model:**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_at',
        'expires_at',
        'is_auto_blocked',
        'malicious_request_count',
    ];
    
    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_auto_blocked' => 'boolean',
    ];
    
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
    
    public function isPermanent(): bool
    {
        return $this->expires_at === null;
    }
    
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
    
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }
}
```

### 2. BotAttackLog Model (Optional)

Opcioni model za strukturirano čuvanje bot attack logova u bazi (alternativa log fajlovima).

**Schema:**
```php
Schema::create('bot_attack_logs', function (Blueprint $table) {
    $table->id();
    $table->string('ip_address', 45);
    $table->text('user_agent')->nullable();
    $table->string('url', 500);
    $table->string('method', 10);
    $table->string('pattern_matched')->nullable();
    $table->string('request_id', 36)->nullable();
    $table->integer('aggregate_count')->default(1);
    $table->timestamp('first_seen_at');
    $table->timestamp('last_seen_at');
    $table->timestamps();
    
    $table->index('ip_address');
    $table->index('created_at');
    $table->index(['ip_address', 'created_at']);
});
```

**Model:**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotAttackLog extends Model
{
    protected $fillable = [
        'ip_address',
        'user_agent',
        'url',
        'method',
        'pattern_matched',
        'request_id',
        'aggregate_count',
        'first_seen_at',
        'last_seen_at',
    ];
    
    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
    
    public function incrementCount(): void
    {
        $this->increment('aggregate_count');
        $this->update(['last_seen_at' => now()]);
    }
}
```

### 3. Configuration Files

**config/security.php**
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bot Protection
    |--------------------------------------------------------------------------
    */
    
    'bot_protection' => [
        'enabled' => env('BOT_PROTECTION_ENABLED', true),
        
        'rate_limiting' => [
            'per_minute' => env('BOT_RATE_LIMIT_PER_MINUTE', 10),
            'per_hour' => env('BOT_RATE_LIMIT_PER_HOUR', 50),
            'auto_block_duration_hours' => env('BOT_AUTO_BLOCK_DURATION_HOURS', 24),
        ],
        
        'logging' => [
            'aggregation_minutes' => env('BOT_LOG_AGGREGATION_MINUTES', 5),
            'channel' => 'security',
        ],
        
        'exploit_patterns' => [
            'wp-login.php',
            'wp-content',
            'wp-admin',
            'wp-includes',
            'xmlrpc.php',
            'server.php',
            'test1.php',
            'f.php',
            'lock.php',
            'elp.php',
            'buy.php',
            'alfa.php',
            'wp-corn-sample.php',
            'adminer.php',
            'phpmyadmin',
            '.env',
            'config.php',
            'shell.php',
            'c99.php',
            'r57.php',
            'eval-stdin.php',
        ],
        
        'whitelist' => [
            // Trusted IP addresses that bypass all bot detection
            // '127.0.0.1',
            // '::1',
        ],
    ],
];
```

**Environment Variables (.env)**
```env
# Bot Protection
BOT_PROTECTION_ENABLED=true
BOT_RATE_LIMIT_PER_MINUTE=10
BOT_RATE_LIMIT_PER_HOUR=50
BOT_AUTO_BLOCK_DURATION_HOURS=24
BOT_LOG_AGGREGATION_MINUTES=5

# Security Logging
SECURITY_LOG_LEVEL=warning
SECURITY_LOG_DAILY_DAYS=90
```

### 4. Cache Keys Structure

**Rate Limiting:**
```
bot:rate_limit:{ip}:minute - Counter za requests per minute
bot:rate_limit:{ip}:hour - Counter za requests per hour
```

**IP Blocking:**
```
bot:blocked_ips - Cached list of blocked IPs (5 minutes TTL)
```

**Log Aggregation:**
```
bot:log_aggregate:{fingerprint} - Counter za log aggregation (5 minutes TTL)
```

**Request Fingerprint:**
```php
$fingerprint = md5($ip . '|' . $url . '|' . $pattern);
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, I identified the following redundancies and consolidations:

**Redundancy Analysis:**
- Properties 1.1, 1.2, 1.3 can be consolidated into a single comprehensive "malicious pattern detection" property
- Properties 1.5 and 2.2 both test that malicious requests are blocked early - these can be combined
- Properties 4.1 and 4.2 both test logging channel separation - these can be combined into one property
- Properties 7.1 and 7.4 both test that exploit pattern 404s go to security log - these overlap and can be combined
- Properties 7.2 and 7.3 both test legitimate 404 handling - these can be combined

**Consolidated Properties:**
The following properties provide unique validation value without redundancy:

### Property 1: Malicious Pattern Detection

*For any* HTTP request URL, if it contains known exploit patterns (wp-login.php, wp-content, etc.), non-existent .php files, or path traversal patterns (../, ..\), then the Bot_Attack_Detector should identify it as a malicious request.

**Validates: Requirements 1.1, 1.2, 1.3**

### Property 2: Early Request Blocking

*For any* malicious request, the Bot_Attack_Detector should return HTTP 403 Forbidden response before the request reaches the application routing layer and without executing subsequent middleware.

**Validates: Requirements 1.5, 2.2**

### Property 3: No Exception Throwing for Malicious Requests

*For any* malicious request that is blocked, the system should not trigger Laravel's exception handling system.

**Validates: Requirements 2.3**

### Property 4: Rate Limit Response Code

*For any* IP address that exceeds rate limiting thresholds, all subsequent requests should receive HTTP 429 Too Many Requests response.

**Validates: Requirements 3.4**

### Property 5: Security Log Channel Separation

*For any* malicious request, the Security_Logger should log the event to the security log channel and not to the default application log channel.

**Validates: Requirements 4.1, 4.2**

### Property 6: Security Event Log Completeness

*For any* security event, the logged entry should contain IP address, User-Agent, requested URL, timestamp, and Request_Fingerprint.

**Validates: Requirements 4.3**

### Property 7: Log Aggregation

*For any* sequence of identical malicious requests from the same IP within the aggregation window, the Security_Logger should aggregate them into a single log entry with an accurate count.

**Validates: Requirements 4.4**

### Property 8: Blocking Action Logging

*For any* IP address that is rate-limited or blocked, the Security_Logger should log the blocking action with reason and duration.

**Validates: Requirements 4.5**

### Property 9: IP Block List Enforcement

*For any* IP address on the block list, all requests from that IP should receive HTTP 403 Forbidden response.

**Validates: Requirements 5.4**

### Property 10: CIDR Range Blocking

*For any* IP address that falls within a blocked CIDR range, all requests from that IP should be blocked.

**Validates: Requirements 5.5**

### Property 11: Whitelist Bypass

*For any* IP address on the whitelist, all requests should bypass bot detection and rate limiting regardless of request content.

**Validates: Requirements 5.6**

### Property 12: Statistics Aggregation Accuracy

*For any* set of blocked malicious requests, the daily statistics should accurately reflect the count per IP address and per exploit pattern.

**Validates: Requirements 6.1, 6.2**

### Property 13: Statistics Completeness

*For any* statistics request, the output should include total blocked requests, unique IP addresses, and most common exploit patterns.

**Validates: Requirements 6.5**

### Property 14: Bot-Generated 404 Suppression

*For any* NotFoundHttpException thrown for a URL matching an exploit pattern, the application should not log it to the application log channel but should log it to the security log channel.

**Validates: Requirements 7.1, 7.4**

### Property 15: Legitimate 404 Logging

*For any* NotFoundHttpException thrown for a legitimate application route (not matching exploit patterns), the application should log it to the application log channel.

**Validates: Requirements 7.2, 7.3**

### Property 16: Request ID Preservation

*For any* security event, the logged entry should contain the request ID assigned by AssignRequestId middleware.

**Validates: Requirements 10.2**

### Property 17: Security Headers on Blocked Requests

*For any* malicious request that is blocked, the 403 response should still include security headers.

**Validates: Requirements 10.3**

### Property 18: Application Rate Limiter Independence

*For any* request to password reset or other application features with existing rate limiters, the bot protection system should not interfere with those rate limiters.

**Validates: Requirements 10.4**

### Property 19: API Throttle Middleware Independence

*For any* API route with Laravel's built-in throttle middleware, the IP_Blocker should not interfere with that throttling behavior.

**Validates: Requirements 10.5**

## Error Handling

### Error Scenarios

**1. Malicious Request Detection**
- **Scenario**: Request matches exploit pattern
- **Handling**: Return 403 Forbidden immediately, log to security channel
- **User Experience**: Generic "Forbidden" message, no detailed error information

**2. Rate Limit Exceeded**
- **Scenario**: IP exceeds rate limit threshold
- **Handling**: Return 429 Too Many Requests with Retry-After header
- **User Experience**: Clear message indicating rate limit exceeded

**3. IP Blocked**
- **Scenario**: Request from blocked IP address
- **Handling**: Return 403 Forbidden immediately, no further processing
- **User Experience**: Generic "Forbidden" message

**4. Cache Unavailable**
- **Scenario**: Redis cache is down
- **Handling**: Fallback to file-based cache automatically
- **User Experience**: No impact, transparent fallback

**5. Database Unavailable**
- **Scenario**: Cannot read blocked IP list from database
- **Handling**: Use cached list if available, otherwise allow request and log error
- **User Experience**: Minimal impact, system degrades gracefully

**6. Configuration Error**
- **Scenario**: Invalid exploit pattern regex
- **Handling**: Log error, skip invalid pattern, continue with valid patterns
- **User Experience**: No impact on legitimate requests

### Error Response Format

**403 Forbidden Response:**
```json
{
    "message": "Forbidden"
}
```

**429 Too Many Requests Response:**
```json
{
    "message": "Too many requests. Please try again later.",
    "retry_after": 900
}
```

### Graceful Degradation

The system is designed to fail open rather than fail closed:
- If bot protection is disabled via config, all requests pass through normally
- If cache is unavailable, system falls back to file cache
- If database is unavailable, system uses cached data
- If all storage fails, system allows requests and logs errors

This ensures that legitimate users are never blocked due to system failures.

## Testing Strategy

### Dual Testing Approach

This feature requires both unit tests and property-based tests for comprehensive coverage:

**Unit Tests** focus on:
- Specific examples of exploit patterns
- Edge cases (empty URLs, special characters, IPv6 addresses)
- Integration points between middleware components
- Artisan command functionality
- Error conditions and graceful degradation

**Property-Based Tests** focus on:
- Universal properties that hold for all inputs
- Comprehensive input coverage through randomization
- Malicious pattern detection across all possible URLs
- Rate limiting behavior across all IP addresses
- Log aggregation correctness across all request sequences

### Property-Based Testing Configuration

**Library**: Use `pestphp/pest` with `pest-plugin-faker` for property-based testing in PHP.

**Configuration**:
- Minimum 100 iterations per property test
- Each test must reference its design document property
- Tag format: `Feature: bot-attack-protection-and-log-optimization, Property {number}: {property_text}`

**Example Property Test Structure:**
```php
<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Feature: bot-attack-protection-and-log-optimization
 * Property 1: Malicious Pattern Detection
 * 
 * For any HTTP request URL, if it contains known exploit patterns,
 * non-existent .php files, or path traversal patterns, then the
 * Bot_Attack_Detector should identify it as a malicious request.
 */
test('malicious pattern detection property', function () {
    $exploitPatterns = config('security.bot_protection.exploit_patterns');
    
    // Test 100 random URLs with exploit patterns
    for ($i = 0; $i < 100; $i++) {
        $pattern = fake()->randomElement($exploitPatterns);
        $url = '/' . fake()->word() . '/' . $pattern;
        
        $response = $this->get($url);
        
        expect($response->status())->toBe(403);
    }
    
    // Test 100 random non-existent .php files
    for ($i = 0; $i < 100; $i++) {
        $url = '/' . fake()->word() . '/' . fake()->word() . '.php';
        
        $response = $this->get($url);
        
        expect($response->status())->toBe(403);
    }
    
    // Test 100 random path traversal attempts
    for ($i = 0; $i < 100; $i++) {
        $traversal = fake()->randomElement(['../', '..\\']);
        $url = '/' . $traversal . fake()->word();
        
        $response = $this->get($url);
        
        expect($response->status())->toBe(403);
    }
})->group('property-based', 'bot-protection');
```

### Unit Test Coverage

**Middleware Tests:**
- `BotAttackDetectorTest.php` - Test specific exploit patterns, edge cases
- `IpBlockerTest.php` - Test IP blocking, CIDR matching, whitelist
- `RateLimiterTest.php` - Test rate limit thresholds, auto-blocking

**Service Tests:**
- `ExploitPatternMatcherTest.php` - Test pattern compilation, matching
- `SecurityLoggerTest.php` - Test log formatting, aggregation
- `BotStatisticsServiceTest.php` - Test statistics calculation

**Repository Tests:**
- `BlockedIpRepositoryTest.php` - Test CRUD operations, CIDR matching

**Command Tests:**
- `BlockIpCommandTest.php` - Test manual IP blocking
- `UnblockIpCommandTest.php` - Test manual IP unblocking
- `ListBlockedIpsCommandTest.php` - Test listing blocked IPs
- `BotStatsCommandTest.php` - Test statistics display
- `CleanupBlockedIpsCommandTest.php` - Test expired IP cleanup

**Integration Tests:**
- `BotProtectionIntegrationTest.php` - Test full request flow
- `ExceptionHandlerIntegrationTest.php` - Test 404 filtering
- `MiddlewareOrderingTest.php` - Test middleware execution order

### Example-Based Tests

The following acceptance criteria are best tested with specific examples:

**3.1 - Rate Limit Per Minute Threshold:**
```php
test('blocks IP after 10 malicious requests per minute', function () {
    $ip = '192.168.1.100';
    
    // Send 10 malicious requests - should all return 403
    for ($i = 0; $i < 10; $i++) {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/wp-login.php');
        expect($response->status())->toBe(403);
    }
    
    // 11th request should return 429 (rate limited)
    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->get('/wp-login.php');
    expect($response->status())->toBe(429);
});
```

**3.2 - Rate Limit Per Hour Threshold:**
```php
test('auto-blocks IP after 50 malicious requests per hour', function () {
    $ip = '192.168.1.101';
    
    // Send 51 malicious requests
    for ($i = 0; $i < 51; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/wp-login.php');
    }
    
    // Verify IP is now in blocked list
    expect(BlockedIp::where('ip_address', $ip)->exists())->toBeTrue();
    
    // Verify block duration is 24 hours
    $blockedIp = BlockedIp::where('ip_address', $ip)->first();
    expect($blockedIp->expires_at->diffInHours(now()))->toBe(24);
});
```

**5.2 - Manual IP Blocking Command:**
```php
test('bot:block command adds IP to block list', function () {
    $ip = '192.168.1.102';
    
    $this->artisan('bot:block', ['ip' => $ip, '--reason' => 'Manual block'])
        ->assertSuccessful();
    
    expect(BlockedIp::where('ip_address', $ip)->exists())->toBeTrue();
});
```

**5.3 - Manual IP Unblocking Command:**
```php
test('bot:unblock command removes IP from block list', function () {
    $ip = '192.168.1.103';
    BlockedIp::create(['ip_address' => $ip, 'blocked_at' => now()]);
    
    $this->artisan('bot:unblock', ['ip' => $ip])
        ->assertSuccessful();
    
    expect(BlockedIp::where('ip_address', $ip)->exists())->toBeFalse();
});
```

**6.3 - Statistics Command:**
```php
test('bot:stats command displays statistics', function () {
    // Create test data
    BlockedIp::factory()->count(5)->create();
    
    $this->artisan('bot:stats', ['--days' => 7])
        ->expectsOutput('Bot Attack Statistics (Last 7 Days)')
        ->assertSuccessful();
});
```

**6.4 - Top Attacking IPs Command:**
```php
test('bot:stats command displays top attacking IPs', function () {
    // Create test data with different request counts
    BlockedIp::factory()->create(['ip_address' => '1.1.1.1', 'malicious_request_count' => 100]);
    BlockedIp::factory()->create(['ip_address' => '2.2.2.2', 'malicious_request_count' => 50]);
    
    $this->artisan('bot:stats')
        ->expectsOutput('Top 10 Attacking IP Addresses:')
        ->expectsOutput('1.1.1.1')
        ->assertSuccessful();
});
```

**8.6 - Bot Protection Disabled:**
```php
test('allows all requests when bot protection is disabled', function () {
    config(['security.bot_protection.enabled' => false]);
    
    $response = $this->get('/wp-login.php');
    
    // Should return 404 (not found) instead of 403 (forbidden)
    expect($response->status())->toBe(404);
});
```

### Test Data Generators

For property-based tests, create custom generators:

```php
<?php

namespace Tests\Generators;

class BotAttackGenerators
{
    public static function maliciousUrl(): string
    {
        $patterns = config('security.bot_protection.exploit_patterns');
        $pattern = fake()->randomElement($patterns);
        
        return '/' . fake()->optional()->word() . '/' . $pattern;
    }
    
    public static function legitimateUrl(): string
    {
        $routes = ['/', '/about', '/contact', '/memorials', '/tributes'];
        return fake()->randomElement($routes);
    }
    
    public static function pathTraversalUrl(): string
    {
        $traversal = fake()->randomElement(['../', '..\\']);
        return '/' . str_repeat($traversal, fake()->numberBetween(1, 5)) . fake()->word();
    }
    
    public static function nonExistentPhpFile(): string
    {
        return '/' . fake()->word() . '/' . fake()->word() . '.php';
    }
    
    public static function ipAddress(): string
    {
        return fake()->ipv4();
    }
    
    public static function cidrRange(): string
    {
        return fake()->ipv4() . '/' . fake()->numberBetween(16, 30);
    }
}
```

### Performance Testing

While not part of property-based testing, performance should be validated:

**Benchmarks:**
- Middleware execution time for legitimate requests: < 2ms
- Middleware execution time for malicious requests: < 5ms
- Pattern matching performance: < 1ms for 20+ patterns
- Cache lookup performance: < 1ms
- Database query performance: < 10ms

**Load Testing:**
- 1000 concurrent legitimate requests: no degradation
- 1000 concurrent malicious requests: all blocked within 5ms
- Mixed load (50% legitimate, 50% malicious): legitimate requests unaffected

### Continuous Integration

All tests should run in CI pipeline:
```bash
# Run all tests
php artisan test

# Run only property-based tests
php artisan test --group=property-based

# Run only bot protection tests
php artisan test --group=bot-protection

# Run with coverage
php artisan test --coverage --min=80
```

### Test Database Setup

For tests, use in-memory SQLite database:
```php
// phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
```

This ensures fast test execution and isolation between tests.

