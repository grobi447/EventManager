# EventManager

A full-stack event management system built with **Laravel 11** and **Vue 3**, communicating through a RESTful HTTP API.

---

## Features

### Core
- **Event Management** — Create, list, update description, delete events. Public event listing with attendee counts and join/leave functionality.
- **Authentication** — JWT-based login and registration. Password reset via email. Login-only page (no self-registration on login screen).
- **Multi-user** — Each user manages their own events independently.

### Security
- JWT Bearer token authentication (tymon/jwt-auth)
- Rate limiting — 5 req/min on login, 60 req/min on API
- Input sanitization (XSS protection via strip_tags)
- Security headers (X-Frame-Options, X-Content-Type-Options, CSP)
- Bcrypt password hashing
- OWASP Top 10 addressed

### Helpdesk
- AI-powered chat using **Google Gemini 2.5 Flash**
- Free-text question answering about EventManager
- Automatic transfer detection — when user requests a human agent
- **Agent Panel** — dedicated interface for `helpdesk_agent` role users
- Voice input via **Web Speech API** (bonus)

### Bonus
- **MFA** — TOTP-based two-factor authentication (Google Authenticator / Authy)
- QR code setup, 6-digit OTP verification, 8 one-time backup codes
- MFA-aware login flow

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | Vue 3, TypeScript, Vite 7 |
| Auth | JWT (tymon/jwt-auth) |
| AI | Google Gemini 2.5 Flash |
| MFA | pragmarx/google2fa, bacon/bacon-qr-code |
| Database | PostgreSQL 16 |
| Cache | Redis 7 |
| Proxy | Nginx Alpine |
| Mail (dev) | Mailpit |
| UI | shadcn-vue, Tailwind CSS v3 |
| State | Pinia |
| Infra | Docker Compose |

---

## Project Structure

```
EventManager/
├── backend/          # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Http/Middleware/
│   │   ├── Models/
│   │   ├── Services/          # Service layer
│   │   └── Policies/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/api.php
├── frontend/         # Vue 3 SPA
│   └── src/
│       ├── api/               # HTTP client
│       ├── views/             # Feature-based pages
│       ├── stores/            # Pinia stores
│       ├── components/        # Shared components
│       ├── types/             # TypeScript interfaces
│       └── router/
├── docker-compose.yml
└── nginx/
```

---

## Getting Started

### Prerequisites
- Docker Desktop
- Git

### Setup

```bash
git clone <repo-url>
cd EventManager

# Copy environment file
cp backend/.env.example backend/.env

# Add your Gemini API key to backend/.env
GEMINI_API_KEY=your_key_here

# Start all services
docker compose up -d

# Run migrations and seed test users
docker compose exec app php artisan migrate:fresh --seed

# Generate JWT secret
docker compose exec app php artisan jwt:secret
```

### Access

| Service | URL |
|---------|-----|
| Frontend | http://localhost:5173 |
| Backend API | http://localhost/api/v1 |
| Mailpit (emails) | http://localhost:8025 |

### Test Accounts

| Email | Password | Role |
|-------|----------|------|
| user@eventmanager.com | password | Regular user |
| agent@eventmanager.com | password | Helpdesk agent |

---

## API

Base URL: `http://localhost/api/v1`

Authentication: `Authorization: Bearer <token>`

### Quick Reference

```
POST   /auth/register
POST   /auth/login
POST   /auth/login-mfa
POST   /auth/logout            [AUTH]
GET    /auth/me                [AUTH]
POST   /auth/forgot-password
POST   /auth/reset-password
POST   /auth/change-password   [AUTH]

GET    /events                 (public)
GET    /events/my              [AUTH]
GET    /events/joined          [AUTH]
POST   /events                 [AUTH]
PUT    /events/{id}            [AUTH]
DELETE /events/{id}            [AUTH]
POST   /events/{id}/join       [AUTH]
DELETE /events/{id}/leave      [AUTH]

POST   /helpdesk/chats                    [AUTH]
GET    /helpdesk/chats/my                 [AUTH]
GET    /helpdesk/chats                    [AGENT]
POST   /helpdesk/chats/{id}/messages      [AUTH]
GET    /helpdesk/chats/{id}/messages      [AUTH]
POST   /helpdesk/chats/{id}/transfer      [AUTH]
POST   /helpdesk/chats/{id}/respond       [AGENT]
POST   /helpdesk/chats/{id}/close         [AGENT]
DELETE /helpdesk/chats/{id}               [AUTH]

GET    /mfa/setup              [AUTH]
POST   /mfa/enable             [AUTH]
POST   /mfa/disable            [AUTH]
```

Full documentation: see `EventManager_Postman_Collection.json` — import into Postman and the Login request auto-saves the token.

---

## Database

6 tables: `users`, `events`, `event_attendees`, `mfa_secrets`, `chats`, `messages`

```bash
# Fresh migration with seed
docker compose exec app php artisan migrate:fresh --seed

# Migration only
docker compose exec app php artisan migrate
```

---

## Development

```bash
# View logs
docker compose logs -f app
docker compose logs -f frontend

# Run tests
docker compose exec app php artisan test

# Code formatting
docker compose exec app ./vendor/bin/pint

# Rebuild containers
docker compose up -d --build
```

---

