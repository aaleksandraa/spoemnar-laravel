# Security Vulnerabilities Fix - Bugfix Design

## Overview

Ova aplikacija za memorijale ima 10 kritičnih sigurnosnih propusta koji moraju biti sistemski riješeni. Design pokriva implementaciju svih sigurnosnih popravki koristeći Laravel best practices i Sanctum autentifikaciju.

Glavni problemi:
- Privatni memorijali dostupni preko javnog API-ja (IDOR vulnerability)
- Email adrese izložene kroz API odgovore (PII leak)
- Nedostatak rate limiting zaštite (brute-force vulnerability)
- Auth tokeni u localStorage (XSS vulnerability)
- Javni tribute API bez anti-spam zaštite
- Nedostajući security headeri (CSP, HSTS, X-Frame-Options)
- Sanctum tokeni bez expiration perioda
- Preširoka CORS politika
- Kontakt forma bez rate limiting i sa PII logiranjem
- Nedostatak HTTPS forsiranja

Strategija popravke: Implementirati defense-in-depth pristup sa multiple layers of security - authorization middleware, rate limiting, secure token storage, security headers, i strict CORS policy.

## Glossary

- **Bug_Condition (C)**: Uslovi koji omogućavaju sigurnosne propuste - neautorizovani pristup privatnim resursima, nedostatak rate limiting, insecure token storage
- **Property (P)**: Željeno sigurnosno ponašanje - access control enforcement, rate limiting, httpOnly cookies, security headers
- **Preservation**: Postojeća funkcionalnost koja mora ostati nepromijenjena - javni memorial pristup, CRUD operacije, search, admin funkcije
- **IDOR**: Insecure Direct Object Reference - pristup resursima bez provjere autorizacije
- **PII**: Personally Identifiable Information - email adrese, imena, kontakt podaci
- **XSS**: Cross-Site Scripting - injection napad koji izvršava maliciozni JavaScript
- **CORS**: Cross-Origin Resource Sharing - kontrola pristupa sa različitih domena
- **CSP**: Content-Security-Policy - header koji kontroliše izvore sadržaja
- **HSTS**: HTTP Strict Transport Security - forsira HTTPS konekcije
- **Sanctum**: Laravel paket za API token autentifikaciju
- **Throttle**: Rate limiting mehanizam u Laravelu
- **Middleware**: Laravel komponenta koja procesira HTTP zahtjeve prije kontrolera
- **Resource**: Laravel klasa za transformaciju modela u API odgovore
- **Policy**: Laravel klasa za autorizaciju pristupa resursima


## Bug Details

### Fault Condition

Sigurnosni propusti se manifestuju u 10 različitih kategorija. Svaka kategorija predstavlja specifičan bug condition:

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type HTTPRequest
  OUTPUT: boolean
  
  RETURN (
    // 1. Privatni memorial pristup bez autorizacije
    (input.endpoint MATCHES '/api/v1/memorials' AND 
     input.memorial.is_public = false AND 
     NOT isAuthorized(input.user, input.memorial))
    
    OR
    
    // 2. Email leak kroz API
    (input.endpoint MATCHES '/api/v1/memorials/{slug}' AND
     response.tributes CONTAINS 'authorEmail')
    
    OR
    
    // 3. Nedostatak rate limiting na auth
    (input.endpoint IN ['/api/v1/login', '/api/v1/register'] AND
     requestCount(input.ip, last_minute) > 5 AND
     NOT hasThrottle(input.endpoint))
    
    OR
    
    // 4. Token u localStorage
    (input.action = 'login_success' AND
     tokenStorage = 'localStorage')
    
    OR
    
    // 5. Tribute spam bez zaštite
    (input.endpoint = '/api/v1/memorials/{memorial}/tributes' AND
     input.method = 'POST' AND
     requestCount(input.ip, last_10_minutes) > 3 AND
     NOT hasRateLimit(input.endpoint))
    
    OR
    
    // 6. Nedostajući security headeri
    (response.headers NOT CONTAINS 'Content-Security-Policy' OR
     response.headers NOT CONTAINS 'Strict-Transport-Security' OR
     response.headers NOT CONTAINS 'X-Frame-Options')
    
    OR
    
    // 7. Sanctum token bez expiration
    (config('sanctum.expiration') = null)
    
    OR
    
    // 8. Preširoka CORS politika
    (config('cors.allowed_methods') = ['*'] OR
     config('cors.allowed_headers') = ['*'])
    
    OR
    
    // 9. Contact form bez zaštite i sa PII logiranjem
    (input.endpoint = '/contact' AND
     (NOT hasRateLimit(input.endpoint) OR
      logs CONTAIN input.email))
    
    OR
    
    // 10. Nedostatak HTTPS forsiranja
    (APP_ENV = 'production' AND
     input.protocol = 'http' AND
     NOT hasHttpsRedirect())
  )
END FUNCTION
```

### Examples

**1. Privatni Memorial IDOR:**
- Zahtjev: `GET /api/v1/memorials/john-doe-private` (is_public=false)
- Trenutno: Vraća potpune podatke bez provjere
- Očekivano: 404 Not Found ili 403 Forbidden

**2. Email Leak:**
- Zahtjev: `GET /api/v1/memorials/jane-smith`
- Trenutno: `{"tributes": [{"authorEmail": "user@example.com"}]}`
- Očekivano: `{"tributes": [{"author": "John", "message": "..."}]}` (bez email-a)

**3. Brute-Force Login:**
- Zahtjev: 100 login pokušaja u minuti na `/api/v1/login`
- Trenutno: Svi zahtjevi se procesiraju
- Očekivano: 429 Too Many Requests nakon 5 pokušaja

**4. XSS Token Theft:**
- Scenario: XSS napad izvršava `localStorage.getItem('auth_token')`
- Trenutno: Token je dostupan JavaScript-u
- Očekivano: Token u httpOnly cookie-u, nedostupan JavaScript-u

**5. Tribute Spam:**
- Zahtjev: 50 tribute POST zahtjeva u minuti
- Trenutno: Svi se procesiraju
- Očekivano: 429 Too Many Requests nakon 3 zahtjeva u 10 minuta

**6. Missing Security Headers:**
- Odgovor: HTTP response bez CSP, HSTS, X-Frame-Options
- Trenutno: Ranjiv na clickjacking, XSS, MITM
- Očekivano: Svi security headeri prisutni

**7. Perpetual Tokens:**
- Scenario: Token kreiran prije 2 godine
- Trenutno: Token i dalje validan
- Očekivano: Token istekao nakon 60 dana

**8. CORS Wildcard:**
- Config: `'allowed_methods' => ['*']`
- Trenutno: Bilo koja metoda dozvoljena
- Očekivano: Samo GET, POST, PUT, DELETE, OPTIONS

**9. Contact Form PII Leak:**
- Log: `Log::info("Contact form: user@example.com")`
- Trenutno: Email u logovima
- Očekivano: Samo hash IP adrese i timestamp

**10. HTTP in Production:**
- Zahtjev: `http://memorials.com/api/v1/memorials`
- Trenutno: Procesira se preko HTTP-a
- Očekivano: Automatski redirect na HTTPS


## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Autentifikacija i registracija validnih korisnika mora nastaviti raditi
- CRUD operacije za memorijale (kreiranje, ažuriranje, brisanje) moraju ostati funkcionalne
- Javni memorijali (is_public=true) moraju ostati dostupni svima
- Tribute funkcionalnost za javne memorijale mora nastaviti raditi
- Image i video upload mora nastaviti raditi
- Search i filtering javnih memorijala mora ostati funkcionalan
- Location API mora nastaviti vraćati države i mjesta
- Password reset flow mora ostati nepromijenjen
- Admin dashboard i privilegije moraju ostati funkcionalne
- Contact form submission mora nastaviti raditi (sa rate limiting-om)
- CORS za legitimne frontend zahtjeve mora nastaviti raditi
- Session i CSRF zaštita na web rutama mora ostati aktivna

**Scope:**
Svi zahtjevi koji NE pokušavaju pristupiti privatnim resursima bez autorizacije, koji NE predstavljaju brute-force napade, i koji dolaze sa legitimnih frontend origina trebaju nastaviti raditi normalno. Ovo uključuje:
- Sve GET zahtjeve za javne memorijale
- Autentifikovane zahtjeve sa validnim tokenima
- Admin operacije sa odgovarajućim privilegijama
- Normalne user interakcije unutar rate limit granica
- Sve web rute sa validnim CSRF tokenima


## Hypothesized Root Cause

Analiza sigurnosnih propusta pokazuje sistemske probleme u arhitekturi:

### 1. Nedostatak Authorization Layer

**Root Cause**: API kontroleri ne provjeravaju vlasništvo resursa prije vraćanja podataka.

**Evidence**:
- `MemorialController::show()` ne poziva `$this->authorize('view', $memorial)`
- `MemorialController::index()` ne filtrira privatne memorijale za neautentifikovane korisnike
- Nedostaju Laravel Policy klase za Memorial i Tribute modele

**Impact**: IDOR vulnerability - bilo ko može pristupiti privatnim memorijalima

### 2. Resource Transformacije Izlažu PII

**Root Cause**: `MemorialResource` i `TributeResource` ne filtriraju osjetljive podatke na osnovu konteksta.

**Evidence**:
- `MemorialResource::toArray()` uključuje `'tributes' => TributeResource::collection($this->tributes)`
- `TributeResource` uvijek vraća `'authorEmail' => $this->author_email`
- Nema conditional logic za skrivanje email-a na osnovu autorizacije

**Impact**: PII leak - email adrese vidljive svima

### 3. Nedostatak Throttle Middleware

**Root Cause**: Auth rute nisu zaštićene sa Laravel throttle middleware-om.

**Evidence**:
- `routes/api.php` ne primjenjuje `throttle:` middleware na `/login` i `/register` rute
- Nema custom rate limiting logike u `AuthController`
- Laravel ima built-in `ThrottleRequests` middleware koji nije iskorišten

**Impact**: Brute-force vulnerability - neograničeni login pokušaji

### 4. Insecure Token Storage Pattern

**Root Cause**: Frontend kod (login.blade.php, register.blade.php) koristi `localStorage.setItem('auth_token')`.

**Evidence**:
- `login.blade.php#L125`: `localStorage.setItem('auth_token', response.token)`
- `register.blade.php#L202`: `localStorage.setItem('auth_token', data.token)`
- Sanctum podržava cookie-based autentifikaciju ali nije konfigurisan

**Impact**: XSS vulnerability - token dostupan malicioznom JavaScript-u

### 5. Tribute Endpoint Bez Rate Limiting

**Root Cause**: Javni tribute endpoint nema anti-spam zaštitu.

**Evidence**:
- `TributeController::store()` ne provjerava rate limit
- Nema honeypot validacije u tribute formi
- Nema timestamp validacije za tribute submission

**Impact**: Spam vulnerability - automatski botovi mogu slati neograničene tribute

### 6. Nedostajući Security Headers Middleware

**Root Cause**: Laravel aplikacija ne koristi middleware za dodavanje security headera.

**Evidence**:
- `app/Http/Kernel.php` ne sadrži custom middleware za security headere
- Nema paketa kao što je `spatie/laravel-csp` ili custom implementacija
- HTTP odgovori nemaju CSP, HSTS, X-Frame-Options, X-Content-Type-Options

**Impact**: Multiple vulnerabilities - clickjacking, XSS, MITM napadi

### 7. Sanctum Config Bez Expiration

**Root Cause**: `config/sanctum.php` ima `'expiration' => null`.

**Evidence**:
- Default Sanctum config ne postavlja expiration
- Tokeni se kreiraju sa `$user->createToken()` bez expiration parametra
- Nema scheduled job-a za čišćenje starih tokena

**Impact**: Perpetual tokens - kompromitovani tokeni ostaju vječno validni

### 8. Permissive CORS Configuration

**Root Cause**: `config/cors.php` koristi wildcard za metode i headere.

**Evidence**:
- `'allowed_methods' => ['*']` dozvoljava sve HTTP metode
- `'allowed_headers' => ['*']` dozvoljava sve headere
- Kombinacija sa `'supports_credentials' => true` je sigurnosni rizik

**Impact**: CORS misconfiguration - potencijalni CSRF i credential leak

### 9. Contact Form Bez Zaštite i Sa PII Logiranjem

**Root Cause**: `ContactController` ne implementira rate limiting i logira PII podatke.

**Evidence**:
- `ContactController::store()` nema throttle middleware
- `ContactController.php#L40`: `Log::info("Contact: {$request->email}")`
- PII podaci ostaju trajno u `storage/logs/laravel.log`

**Impact**: Spam vulnerability i PII leak u logovima

### 10. Nedostatak HTTPS Enforcement Middleware

**Root Cause**: Aplikacija ne forsira HTTPS na aplikacijskom nivou.

**Evidence**:
- `app/Http/Kernel.php` ne sadrži middleware za HTTPS redirect
- `config/app.php` nema `'force_https' => true` opciju
- Zavisnost od infra konfiguracije (nginx/apache) bez app-level fallback

**Impact**: MITM vulnerability - production traffic može ići preko HTTP-a


## Correctness Properties

Property 1: Fault Condition - Private Memorial Access Control

_For any_ API request to `/api/v1/memorials/{slug}` where the memorial has `is_public=false` and the requester is not authenticated or not the owner, the fixed system SHALL return 404 Not Found or 403 Forbidden, preventing unauthorized access to private memorials.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4**

Property 2: Fault Condition - Email Address Protection

_For any_ API request to memorial or tribute endpoints, the fixed system SHALL exclude `authorEmail` from response data unless the requester is the memorial owner, preventing PII leakage.

**Validates: Requirements 2.5, 2.6, 2.7, 2.8**

Property 3: Fault Condition - Authentication Rate Limiting

_For any_ sequence of requests to `/api/v1/login` or `/api/v1/register` exceeding 5 attempts per minute from the same IP, the fixed system SHALL return 429 Too Many Requests, preventing brute-force attacks.

**Validates: Requirements 2.9, 2.10, 2.11**

Property 4: Fault Condition - Secure Token Storage

_For any_ successful authentication, the fixed system SHALL store the auth token in an httpOnly, secure, sameSite cookie instead of localStorage, preventing XSS-based token theft.

**Validates: Requirements 2.12, 2.13, 2.14**

Property 5: Fault Condition - Tribute Anti-Spam Protection

_For any_ sequence of tribute submissions exceeding 3 requests per 10 minutes from the same IP, the fixed system SHALL return 429 Too Many Requests and SHALL validate honeypot and timestamp fields, preventing spam submissions.

**Validates: Requirements 2.15, 2.16, 2.17**

Property 6: Fault Condition - Security Headers Enforcement

_For any_ HTTP response, the fixed system SHALL include Content-Security-Policy, Strict-Transport-Security, X-Frame-Options, and X-Content-Type-Options headers, preventing clickjacking, XSS, and MITM attacks.

**Validates: Requirements 2.18, 2.19, 2.20, 2.21**

Property 7: Fault Condition - Token Expiration

_For any_ Sanctum token created after the fix, the token SHALL expire after 60 days and the system SHALL return 401 Unauthenticated for expired tokens, preventing perpetual token validity.

**Validates: Requirements 2.22, 2.23, 2.24**

Property 8: Fault Condition - Restrictive CORS Policy

_For any_ CORS preflight request, the fixed system SHALL allow only specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS) and specific headers (Content-Type, Authorization, Accept, X-Requested-With), preventing CORS misconfiguration attacks.

**Validates: Requirements 2.25, 2.26, 2.27**

Property 9: Fault Condition - Contact Form Protection

_For any_ contact form submission, the fixed system SHALL enforce rate limiting of 5 requests per hour per IP and SHALL NOT log PII data (email, name), preventing spam and PII leakage in logs.

**Validates: Requirements 2.28, 2.29, 2.30**

Property 10: Fault Condition - HTTPS Enforcement

_For any_ HTTP request in production environment (APP_ENV=production), the fixed system SHALL redirect to HTTPS, preventing MITM attacks on production traffic.

**Validates: Requirements 2.31, 2.32, 2.33**

Property 11: Preservation - Public Memorial Access

_For any_ request to public memorials (is_public=true), the fixed system SHALL produce the same response as the original system, preserving public access functionality.

**Validates: Requirements 3.8, 3.9, 3.10**

Property 12: Preservation - Authenticated Operations

_For any_ authenticated CRUD operation (create, update, delete memorial) by the owner or admin, the fixed system SHALL produce the same result as the original system, preserving core functionality.

**Validates: Requirements 3.4, 3.5, 3.6, 3.7**

Property 13: Preservation - Search and Filtering

_For any_ search or filter operation on public memorials, the fixed system SHALL produce the same results as the original system, preserving search functionality.

**Validates: Requirements 3.17, 3.18, 3.19**

Property 14: Preservation - Admin Functionality

_For any_ admin operation (dashboard, user management, settings), the fixed system SHALL produce the same result as the original system, preserving admin privileges.

**Validates: Requirements 3.25, 3.26, 3.27**


## Fix Implementation

### Changes Required

Implementacija će biti organizovana u 10 logičkih cjelina, svaka sa specifičnim fajlovima i promjenama.

---

### 1. Private Memorial Access Control

**Files**: 
- `app/Policies/MemorialPolicy.php` (create new)
- `app/Http/Controllers/Api/V1/MemorialController.php` (modify)
- `app/Providers/AuthServiceProvider.php` (modify)

**Specific Changes**:

1.1 **Create MemorialPolicy**:
   - Implementirati `view(User $user = null, Memorial $memorial)` metodu
   - Logika: `return $memorial->is_public || ($user && $user->id === $memorial->user_id) || ($user && $user->hasRole('admin'))`
   - Implementirati `viewAny(User $user = null)` za index filtering

1.2 **Modify MemorialController::index()**:
   - Dodati query scope: `$query->where('is_public', true)` za neautentifikovane korisnike
   - Za autentifikovane: `$query->where('is_public', true)->orWhere('user_id', auth()->id())`
   - Dodati `->when(auth()->check() && auth()->user()->hasRole('admin'), fn($q) => $q->withoutGlobalScope('public'))` za admin

1.3 **Modify MemorialController::show()**:
   - Dodati prije return: `$this->authorize('view', $memorial)`
   - Ako authorization fail: Laravel automatski vraća 403 Forbidden
   - Alternativno: `abort_if(!Gate::allows('view', $memorial), 404)` za 404 umjesto 403

1.4 **Register Policy**:
   - U `AuthServiceProvider::boot()`: `Gate::policy(Memorial::class, MemorialPolicy::class)`

---

### 2. Email Address Protection

**Files**:
- `app/Http/Resources/MemorialResource.php` (modify)
- `app/Http/Resources/TributeResource.php` (modify)
- `app/Http/Controllers/Api/V1/TributeController.php` (modify)

**Specific Changes**:

2.1 **Modify TributeResource::toArray()**:
   - Dodati conditional logic:
   ```php
   'authorEmail' => $this->when(
       $request->user() && 
       ($request->user()->id === $this->memorial->user_id || $request->user()->hasRole('admin')),
       $this->author_email
   )
   ```
   - Ovo će uključiti email samo ako je korisnik vlasnik memorijala ili admin

2.2 **Modify MemorialResource::toArray()**:
   - Provjeriti da li `tributes` koristi `TributeResource::collection()`
   - Ako ne, zamijeniti sa: `'tributes' => TributeResource::collection($this->whenLoaded('tributes'))`

2.3 **Modify TributeController::index()**:
   - Dodati authorization check: `$this->authorize('view', $memorial)`
   - Ovo će spriječiti pristup tributima privatnih memorijala

---

### 3. Authentication Rate Limiting

**Files**:
- `routes/api.php` (modify)
- `app/Http/Kernel.php` (verify throttle middleware exists)

**Specific Changes**:

3.1 **Apply Throttle Middleware to Auth Routes**:
   ```php
   Route::post('/login', [AuthController::class, 'login'])
       ->middleware('throttle:5,1'); // 5 requests per 1 minute
   
   Route::post('/register', [AuthController::class, 'register'])
       ->middleware('throttle:3,1'); // 3 requests per 1 minute
   ```

3.2 **Custom Throttle for Failed Logins** (optional enhancement):
   - Kreirati `app/Http/Middleware/ThrottleFailedLogins.php`
   - Implementirati lockout nakon 5 neuspješnih pokušaja
   - Koristiti `RateLimiter::hit()` i `RateLimiter::tooManyAttempts()`
   - Registrovati u `Kernel.php`: `'throttle.login' => \App\Http\Middleware\ThrottleFailedLogins::class`

---

### 4. Secure Token Storage (HttpOnly Cookies)

**Files**:
- `config/sanctum.php` (modify)
- `app/Http/Controllers/Api/V1/AuthController.php` (modify)
- `resources/views/auth/login.blade.php` (modify)
- `resources/views/auth/register.blade.php` (modify)
- `config/cors.php` (modify)

**Specific Changes**:

4.1 **Configure Sanctum for Cookie Authentication**:
   ```php
   // config/sanctum.php
   'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
       '%s%s',
       'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
       env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
   ))),
   ```

4.2 **Modify AuthController::login()**:
   - Umjesto vraćanja tokena u JSON-u: `return response()->json(['token' => $token])`
   - Koristiti cookie response:
   ```php
   $token = $user->createToken('auth_token')->plainTextToken;
   
   return response()->json(['user' => $user])
       ->cookie('auth_token', $token, 60 * 24 * 60, '/', null, true, true, false, 'strict');
       // params: name, value, minutes, path, domain, secure, httpOnly, raw, sameSite
   ```

4.3 **Modify Frontend (login.blade.php)**:
   - Ukloniti: `localStorage.setItem('auth_token', response.token)`
   - Token će automatski biti u cookie-u
   - Axios će automatski slati cookie sa `withCredentials: true`

4.4 **Modify Frontend (register.blade.php)**:
   - Ista promjena kao za login

4.5 **Configure CORS for Credentials**:
   ```php
   // config/cors.php
   'supports_credentials' => true,
   ```

4.6 **Update Axios Configuration**:
   - U frontend kodu dodati: `axios.defaults.withCredentials = true`

---

### 5. Tribute Anti-Spam Protection

**Files**:
- `routes/api.php` (modify)
- `app/Http/Requests/StoreTributeRequest.php` (create new or modify)
- `resources/views/memorials/show.blade.php` (modify - add honeypot)

**Specific Changes**:

5.1 **Apply Rate Limiting to Tribute Route**:
   ```php
   Route::post('/memorials/{memorial}/tributes', [TributeController::class, 'store'])
       ->middleware('throttle:3,10'); // 3 requests per 10 minutes
   ```

5.2 **Create StoreTributeRequest with Honeypot Validation**:
   ```php
   public function rules()
   {
       return [
           'author_name' => 'required|string|max:255',
           'message' => 'required|string|max:1000',
           'author_email' => 'nullable|email',
           'honeypot' => 'size:0', // Must be empty (bot trap)
           'timestamp' => 'required|integer|min:' . (time() - 3600), // Max 1 hour old
       ];
   }
   ```

5.3 **Add Honeypot Field to Frontend Form**:
   ```html
   <input type="text" name="honeypot" style="display:none" tabindex="-1" autocomplete="off">
   <input type="hidden" name="timestamp" :value="Date.now()">
   ```

5.4 **Modify TributeController::store()**:
   - Koristiti `StoreTributeRequest` umjesto `Request`
   - Validacija će automatski odbiti spam zahtjeve

---

### 6. Security Headers Middleware

**Files**:
- `app/Http/Middleware/SecurityHeaders.php` (create new)
- `app/Http/Kernel.php` (modify)

**Specific Changes**:

6.1 **Create SecurityHeaders Middleware**:
   ```php
   public function handle($request, Closure $next)
   {
       $response = $next($request);
       
       $response->headers->set('Content-Security-Policy', 
           "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'");
       
       $response->headers->set('Strict-Transport-Security', 
           'max-age=31536000; includeSubDomains');
       
       $response->headers->set('X-Frame-Options', 'DENY');
       
       $response->headers->set('X-Content-Type-Options', 'nosniff');
       
       $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
       
       $response->headers->set('Permissions-Policy', 
           'geolocation=(), microphone=(), camera=()');
       
       return $response;
   }
   ```

6.2 **Register Middleware Globally**:
   ```php
   // app/Http/Kernel.php
   protected $middleware = [
       // ... existing middleware
       \App\Http\Middleware\SecurityHeaders::class,
   ];
   ```

---

### 7. Sanctum Token Expiration

**Files**:
- `config/sanctum.php` (modify)
- `app/Http/Controllers/Api/V1/AuthController.php` (modify - add refresh endpoint)
- `routes/api.php` (modify - add refresh route)

**Specific Changes**:

7.1 **Configure Token Expiration**:
   ```php
   // config/sanctum.php
   'expiration' => 60 * 24 * 60, // 60 days in minutes
   ```

7.2 **Create Token Refresh Endpoint**:
   ```php
   // AuthController::refresh()
   public function refresh(Request $request)
   {
       $user = $request->user();
       
       // Revoke current token
       $user->currentAccessToken()->delete();
       
       // Create new token
       $token = $user->createToken('auth_token')->plainTextToken;
       
       return response()->json(['user' => $user])
           ->cookie('auth_token', $token, 60 * 24 * 60, '/', null, true, true, false, 'strict');
   }
   ```

7.3 **Add Refresh Route**:
   ```php
   Route::post('/refresh', [AuthController::class, 'refresh'])
       ->middleware('auth:sanctum');
   ```

7.4 **Create Scheduled Job for Token Cleanup** (optional):
   ```php
   // app/Console/Kernel.php
   protected function schedule(Schedule $schedule)
   {
       $schedule->command('sanctum:prune-expired --hours=24')->daily();
   }
   ```

---

### 8. Restrictive CORS Policy

**Files**:
- `config/cors.php` (modify)

**Specific Changes**:

8.1 **Replace Wildcard with Explicit Values**:
   ```php
   'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
   
   'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'X-CSRF-TOKEN'],
   
   'allowed_origins' => [
       env('FRONTEND_URL', 'http://localhost:3000'),
       env('APP_URL', 'http://localhost:8000'),
   ],
   
   'allowed_origins_patterns' => [],
   
   'supports_credentials' => true,
   ```

8.2 **Verify CORS Middleware is Applied**:
   ```php
   // app/Http/Kernel.php - verify this exists
   protected $middlewareGroups = [
       'api' => [
           \Illuminate\Routing\Middleware\SubstituteBindings::class,
           \Fruitcake\Cors\HandleCors::class, // Should be present
       ],
   ];
   ```

---

### 9. Contact Form Protection and PII Removal

**Files**:
- `app/Http/Controllers/ContactController.php` (modify)
- `routes/web.php` (modify)

**Specific Changes**:

9.1 **Apply Rate Limiting to Contact Route**:
   ```php
   Route::post('/contact', [ContactController::class, 'store'])
       ->middleware('throttle:5,60'); // 5 requests per 60 minutes
   ```

9.2 **Remove PII from Logging**:
   ```php
   // ContactController::store()
   // BEFORE:
   // Log::info("Contact form submitted: {$request->email}, {$request->name}");
   
   // AFTER:
   Log::info('Contact form submitted', [
       'ip_hash' => hash('sha256', $request->ip()),
       'timestamp' => now()->toIso8601String(),
       'success' => true,
   ]);
   ```

9.3 **Keep Email Sending Functionality**:
   - Email sending (Mail::to()) ostaje nepromijenjen
   - Samo logging se mijenja

---

### 10. HTTPS Enforcement

**Files**:
- `app/Http/Middleware/ForceHttps.php` (create new)
- `app/Http/Kernel.php` (modify)
- `app/Providers/AppServiceProvider.php` (modify)

**Specific Changes**:

10.1 **Create ForceHttps Middleware**:
   ```php
   public function handle($request, Closure $next)
   {
       if (!$request->secure() && app()->environment('production')) {
           return redirect()->secure($request->getRequestUri(), 301);
       }
       
       return $next($request);
   }
   ```

10.2 **Register Middleware**:
   ```php
   // app/Http/Kernel.php
   protected $middleware = [
       // ... existing middleware
       \App\Http\Middleware\ForceHttps::class,
   ];
   ```

10.3 **Force HTTPS URLs in Production**:
   ```php
   // app/Providers/AppServiceProvider.php
   public function boot()
   {
       if ($this->app->environment('production')) {
           \URL::forceScheme('https');
       }
   }
   ```


## Testing Strategy

### Validation Approach

Testing strategija prati two-phase pristup za svaku od 10 sigurnosnih kategorija:

1. **Exploratory Fault Condition Checking**: Demonstrirati bug na UNFIXED kodu
2. **Fix Checking**: Verificirati da fix rješava problem
3. **Preservation Checking**: Verificirati da postojeća funkcionalnost ostaje nepromijenjena

Koristimo kombinaciju unit testova, integration testova, i property-based testova.

---

### Exploratory Fault Condition Checking

**Goal**: Surface counterexamples that demonstrate security vulnerabilities BEFORE implementing fixes. Confirm root cause analysis.

#### Test Plan by Category:

**1. Private Memorial Access (IDOR)**

Test Cases (will fail on unfixed code):
- `test_unauthenticated_user_can_access_private_memorial()`: GET /api/v1/memorials/{private-slug} → expects 200 (bug), should be 404
- `test_authenticated_non_owner_can_access_private_memorial()`: User A accesses User B's private memorial → expects 200 (bug), should be 403
- `test_index_returns_private_memorials_to_unauthenticated()`: GET /api/v1/memorials → expects private memorials in response (bug)

Expected Counterexamples:
- Private memorials returned in API responses without authorization check
- Confirms hypothesis: Missing authorization layer

**2. Email Leak**

Test Cases (will fail on unfixed code):
- `test_memorial_resource_exposes_author_email()`: GET /api/v1/memorials/{slug} → expects `authorEmail` in tributes array (bug)
- `test_tribute_index_exposes_emails_to_non_owner()`: GET /api/v1/memorials/{memorial}/tributes → expects email in response (bug)

Expected Counterexamples:
- Email addresses visible in JSON responses
- Confirms hypothesis: Resource transformations don't filter PII

**3. Rate Limiting on Auth**

Test Cases (will fail on unfixed code):
- `test_login_accepts_unlimited_requests()`: Send 100 POST /api/v1/login in 1 minute → expects all processed (bug), should throttle after 5
- `test_register_accepts_unlimited_requests()`: Send 50 POST /api/v1/register in 1 minute → expects all processed (bug)

Expected Counterexamples:
- All requests processed without throttling
- Confirms hypothesis: Missing throttle middleware

**4. Token in localStorage**

Test Cases (will fail on unfixed code):
- `test_login_returns_token_in_json()`: POST /api/v1/login → expects `{"token": "..."}` in response body (bug)
- `test_frontend_stores_token_in_localstorage()`: Check login.blade.php → expects `localStorage.setItem('auth_token')` (bug)

Expected Counterexamples:
- Token returned in JSON response body
- Frontend code uses localStorage
- Confirms hypothesis: Insecure token storage pattern

**5. Tribute Spam**

Test Cases (will fail on unfixed code):
- `test_tribute_endpoint_accepts_unlimited_requests()`: Send 50 POST /api/v1/memorials/{memorial}/tributes in 1 minute → expects all processed (bug)
- `test_tribute_accepts_submission_without_honeypot()`: POST without honeypot field → expects success (bug)

Expected Counterexamples:
- All tribute requests processed
- No honeypot or timestamp validation
- Confirms hypothesis: No anti-spam protection

**6. Missing Security Headers**

Test Cases (will fail on unfixed code):
- `test_response_missing_csp_header()`: GET any endpoint → expects no `Content-Security-Policy` header (bug)
- `test_response_missing_hsts_header()`: GET any endpoint → expects no `Strict-Transport-Security` header (bug)
- `test_response_missing_xframe_header()`: GET any endpoint → expects no `X-Frame-Options` header (bug)

Expected Counterexamples:
- HTTP responses without security headers
- Confirms hypothesis: No security headers middleware

**7. Perpetual Tokens**

Test Cases (will fail on unfixed code):
- `test_sanctum_config_has_null_expiration()`: Check config/sanctum.php → expects `'expiration' => null` (bug)
- `test_old_token_still_valid()`: Create token, advance time 2 years, use token → expects success (bug)

Expected Counterexamples:
- Tokens never expire
- Confirms hypothesis: Sanctum config without expiration

**8. Permissive CORS**

Test Cases (will fail on unfixed code):
- `test_cors_config_allows_all_methods()`: Check config/cors.php → expects `'allowed_methods' => ['*']` (bug)
- `test_cors_config_allows_all_headers()`: Check config/cors.php → expects `'allowed_headers' => ['*']` (bug)

Expected Counterexamples:
- Wildcard CORS configuration
- Confirms hypothesis: Permissive CORS policy

**9. Contact Form Issues**

Test Cases (will fail on unfixed code):
- `test_contact_form_accepts_unlimited_requests()`: Send 100 POST /contact in 1 minute → expects all processed (bug)
- `test_contact_controller_logs_pii()`: POST /contact → check logs for email address (bug)

Expected Counterexamples:
- No rate limiting on contact form
- PII in log files
- Confirms hypothesis: No protection and PII logging

**10. HTTP in Production**

Test Cases (will fail on unfixed code):
- `test_http_request_not_redirected_in_production()`: Send HTTP request with APP_ENV=production → expects HTTP response (bug), should redirect to HTTPS
- `test_no_https_enforcement_middleware()`: Check Kernel.php → expects no ForceHttps middleware (bug)

Expected Counterexamples:
- HTTP requests processed in production
- Confirms hypothesis: No HTTPS enforcement

---

### Fix Checking

**Goal**: Verify that for all inputs where bug conditions hold, the fixed system produces expected secure behavior.

#### Pseudocode by Category:

**1. Private Memorial Access Control**
```
FOR ALL memorial WHERE memorial.is_public = false DO
  FOR ALL user WHERE user.id != memorial.user_id AND NOT user.isAdmin() DO
    response := GET /api/v1/memorials/{memorial.slug} AS user
    ASSERT response.status IN [403, 404]
  END FOR
END FOR
```

**2. Email Protection**
```
FOR ALL memorial DO
  FOR ALL user WHERE user.id != memorial.user_id DO
    response := GET /api/v1/memorials/{memorial.slug} AS user
    ASSERT response.tributes[*].authorEmail = undefined
  END FOR
END FOR
```

**3. Rate Limiting**
```
FOR endpoint IN ['/api/v1/login', '/api/v1/register'] DO
  FOR attempt IN 1..10 DO
    response := POST endpoint
    IF attempt > 5 THEN
      ASSERT response.status = 429
    END IF
  END FOR
END FOR
```

**4. Secure Token Storage**
```
FOR ALL successful_login DO
  response := POST /api/v1/login
  ASSERT response.body.token = undefined
  ASSERT response.cookies['auth_token'] EXISTS
  ASSERT response.cookies['auth_token'].httpOnly = true
  ASSERT response.cookies['auth_token'].secure = true
END FOR
```

**5. Tribute Anti-Spam**
```
FOR tribute_submission IN 1..10 DO
  response := POST /api/v1/memorials/{memorial}/tributes
  IF tribute_submission > 3 THEN
    ASSERT response.status = 429
  END IF
END FOR

tribute_with_honeypot := {honeypot: 'bot-value'}
response := POST /api/v1/memorials/{memorial}/tributes WITH tribute_with_honeypot
ASSERT response.status = 422
```

**6. Security Headers**
```
FOR ALL endpoint IN application_routes DO
  response := GET endpoint
  ASSERT response.headers['Content-Security-Policy'] EXISTS
  ASSERT response.headers['Strict-Transport-Security'] EXISTS
  ASSERT response.headers['X-Frame-Options'] = 'DENY'
  ASSERT response.headers['X-Content-Type-Options'] = 'nosniff'
END FOR
```

**7. Token Expiration**
```
token := createToken()
advanceTime(61 days)
response := GET /api/v1/me WITH token
ASSERT response.status = 401
```

**8. CORS Restrictions**
```
ASSERT config('cors.allowed_methods') = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
ASSERT config('cors.allowed_headers') = ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With']
ASSERT '*' NOT IN config('cors.allowed_methods')
```

**9. Contact Form Protection**
```
FOR submission IN 1..10 DO
  response := POST /contact
  IF submission > 5 THEN
    ASSERT response.status = 429
  END IF
END FOR

POST /contact
ASSERT logs NOT CONTAIN email_address
ASSERT logs CONTAIN ip_hash
```

**10. HTTPS Enforcement**
```
WITH APP_ENV = 'production' DO
  response := GET http://memorials.com/api/v1/memorials
  ASSERT response.status = 301
  ASSERT response.headers['Location'] STARTS_WITH 'https://'
END WITH
```

---

### Preservation Checking

**Goal**: Verify that for all inputs where bug conditions do NOT hold, the fixed system produces the same result as the original system.

#### Pseudocode:

**Public Memorial Access (Unchanged)**
```
FOR ALL memorial WHERE memorial.is_public = true DO
  response_original := GET_ORIGINAL /api/v1/memorials/{memorial.slug}
  response_fixed := GET_FIXED /api/v1/memorials/{memorial.slug}
  ASSERT response_original.data = response_fixed.data
END FOR
```

**Authenticated Owner Operations (Unchanged)**
```
FOR ALL memorial WHERE user.id = memorial.user_id DO
  // Owner can still access their private memorial
  response := GET /api/v1/memorials/{memorial.slug} AS owner
  ASSERT response.status = 200
  ASSERT response.data.tributes[*].authorEmail EXISTS // Owner sees emails
  
  // Owner can still update
  response := PUT /api/v1/memorials/{memorial.id} AS owner
  ASSERT response.status = 200
  
  // Owner can still delete
  response := DELETE /api/v1/memorials/{memorial.id} AS owner
  ASSERT response.status = 204
END FOR
```

**Valid Login Attempts (Unchanged)**
```
FOR ALL valid_credentials DO
  response_original := POST_ORIGINAL /api/v1/login WITH valid_credentials
  response_fixed := POST_FIXED /api/v1/login WITH valid_credentials
  ASSERT response_original.user = response_fixed.user
  // Token delivery method changed (cookie vs JSON), but user data same
END FOR
```

**Search and Filtering (Unchanged)**
```
FOR ALL search_query DO
  results_original := GET_ORIGINAL /api/v1/memorials?search={query}
  results_fixed := GET_FIXED /api/v1/memorials?search={query}
  ASSERT results_original.data = results_fixed.data
END FOR
```

**Admin Privileges (Unchanged)**
```
FOR ALL admin_user DO
  // Admin can access all memorials
  response := GET /api/v1/memorials AS admin
  ASSERT response includes private memorials
  
  // Admin can manage all memorials
  response := DELETE /api/v1/memorials/{any_memorial} AS admin
  ASSERT response.status = 204
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- Generates many test cases automatically across input domain
- Catches edge cases that manual tests might miss
- Provides strong guarantees that behavior is unchanged for non-buggy inputs

---

### Unit Tests

**Memorial Access Control**:
- `test_unauthenticated_cannot_view_private_memorial()`
- `test_non_owner_cannot_view_private_memorial()`
- `test_owner_can_view_own_private_memorial()`
- `test_admin_can_view_any_private_memorial()`
- `test_public_memorials_visible_to_all()`

**Email Protection**:
- `test_memorial_resource_hides_email_from_non_owner()`
- `test_memorial_resource_shows_email_to_owner()`
- `test_tribute_index_requires_authorization_for_private_memorial()`

**Rate Limiting**:
- `test_login_throttled_after_5_attempts()`
- `test_register_throttled_after_3_attempts()`
- `test_tribute_throttled_after_3_attempts_in_10_minutes()`
- `test_contact_form_throttled_after_5_attempts_per_hour()`

**Token Security**:
- `test_login_returns_httponly_cookie()`
- `test_token_not_in_response_body()`
- `test_cookie_has_secure_flag()`
- `test_cookie_has_samesite_strict()`

**Anti-Spam**:
- `test_tribute_rejects_filled_honeypot()`
- `test_tribute_rejects_old_timestamp()`
- `test_tribute_accepts_valid_submission()`

**Security Headers**:
- `test_response_includes_csp_header()`
- `test_response_includes_hsts_header()`
- `test_response_includes_xframe_options()`
- `test_response_includes_content_type_options()`

**Token Expiration**:
- `test_token_expires_after_60_days()`
- `test_expired_token_returns_401()`
- `test_refresh_endpoint_creates_new_token()`

**CORS**:
- `test_cors_allows_only_specific_methods()`
- `test_cors_allows_only_specific_headers()`
- `test_cors_rejects_wildcard_with_credentials()`

**Contact Form**:
- `test_contact_form_does_not_log_email()`
- `test_contact_form_logs_ip_hash()`

**HTTPS**:
- `test_http_redirects_to_https_in_production()`
- `test_http_allowed_in_local_environment()`

---

### Property-Based Tests

**Memorial Access Properties**:
- Generate random memorials with varying `is_public` values
- Generate random users with varying ownership and roles
- Verify access control rules hold for all combinations

**Email Protection Properties**:
- Generate random memorial responses
- Verify email never appears unless requester is owner/admin

**Rate Limiting Properties**:
- Generate random request sequences
- Verify throttling activates at correct thresholds

**Preservation Properties**:
- Generate random public memorial operations
- Verify results identical between original and fixed code
- Generate random authenticated owner operations
- Verify CRUD operations unchanged

---

### Integration Tests

**End-to-End Security Flow**:
- Test complete user journey: register → login → create private memorial → verify access control
- Test admin flow: login as admin → access all memorials → verify email visibility
- Test public flow: browse public memorials → submit tribute → verify rate limiting

**Cross-Feature Integration**:
- Test CORS + Cookie authentication together
- Test rate limiting + security headers together
- Test HTTPS enforcement + cookie security flags together

**Regression Testing**:
- Run full test suite on public memorial operations
- Run full test suite on authenticated operations
- Verify no existing functionality broken

