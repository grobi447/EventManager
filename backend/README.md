# EventManager — Backend

Laravel 11 RESTful API with JWT authentication, AI helpdesk, and MFA support.

## Architecture

**Service Layer Pattern** — Controllers are thin, business logic lives in Services.

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php       # Login, register, password reset
│   │   ├── EventController.php      # Events CRUD + join/leave
│   │   ├── HelpdeskController.php   # Chat management
│   │   └── MfaController.php        # MFA setup/enable/disable
│   ├── Middleware/
│   │   ├── SanitizeInput.php        # XSS protection
│   │   └── SecurityHeaders.php      # OWASP headers
│   └── Requests/                    # Form validation
├── Models/
│   ├── User.php
│   ├── Event.php
│   ├── Chat.php
│   ├── Message.php
│   └── MfaSecret.php
├── Services/
│   └── HelpdeskService.php          # Gemini AI integration
└── Policies/
    └── EventPolicy.php              # Authorization rules
```

## Key Packages

| Package | Purpose |
|---------|---------|
| tymon/jwt-auth | JWT token authentication |
| pragmarx/google2fa-laravel | TOTP MFA |
| bacon/bacon-qr-code | QR code generation |
| google-gemini-php/laravel | Gemini AI client |
| laravel/pint | Code formatting |

## Environment Variables

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=eventmanager
DB_USERNAME=eventmanager
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# JWT
JWT_SECRET=                    # php artisan jwt:secret
JWT_TTL=60

# Gemini AI
GEMINI_API_KEY=                # https://ai.google.dev

# Mail (Mailpit dev)
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@eventmanager.com

# Frontend URL (for reset links)
FRONTEND_URL=http://localhost:5173
```

## API Response Format

All responses follow this structure:

```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

Error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": { "field": ["validation error"] }
}
```

## Security

- **Passwords** — bcrypt hashed via Laravel's Hash facade
- **JWT** — stored in Redis blacklist on logout, auto-expire after 60 min
- **Rate limiting** — `AppServiceProvider`: 5/min login, 60/min API
- **Input** — `SanitizeInput` middleware runs `strip_tags` on all input
- **Headers** — `SecurityHeaders` middleware adds OWASP-recommended headers
- **SQL injection** — Eloquent ORM with parameter binding throughout
- **MFA** — TOTP with 30-second window, 8 hex backup codes

## Running Locally

```bash
# Inside Docker
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear

# Logs
docker compose logs -f app
```

## Test Accounts (seeded)

| Email | Password | Role |
|-------|----------|------|
| user@eventmanager.com | password | user |
| agent@eventmanager.com | password | helpdesk_agent |
