# Timetable Generation System – REST API

Pure PHP (OOP, no frameworks) REST API for generating and managing school timetables. Uses PDO, MySQL, and a central router with token-based auth.

## Requirements

- PHP 7.4+ (8.x recommended)
- MySQL 5.7+ / MariaDB
- Apache with mod_rewrite (XAMPP)

## Installation

1. **Database**
   - Create database and tables by running `database/migrations.sql` in MySQL.
   - Default admin user: `admin@school.local` / `Admin@123` (change in production).

2. **Configuration**
   - Copy `config/.env.example.php` to `config/.env.php`.
   - Set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` as needed.

3. **Apache**
   - Ensure `mod_rewrite` is enabled.
   - If the API lives in a subfolder (e.g. `timetable-api`), set `RewriteBase` in `.htaccess` if needed.

## Project structure

```
timetable-api/
├── index.php              # Front controller
├── .htaccess
├── config/
│   ├── Database.php       # PDO singleton
│   └── .env.example.php
├── routes/
│   ├── api.php            # Route definitions
│   └── Router.php         # Request router
├── controllers/           # HTTP layer
├── models/                # Database access
├── services/              # Business logic (incl. TimetableGenerator)
├── middleware/            # Auth, RequestContext
├── utils/
│   ├── Response.php       # JSON responses
│   └── Validator.php      # Input validation
└── database/
    └── migrations.sql
```

## API overview

- **Auth:** `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`
- **Academic:** academic-years, terms, classes (CRUD)
- **Resources:** teachers (with subjects & availability), subjects, rooms (CRUD)
- **Time:** school-days, time-slots, break-periods
- **Allocations:** `POST/GET /api/allocations`, `GET /api/allocations/class/{id}`
- **Timetable:** `POST /api/timetable/generate`, `GET /api/timetable`, by class/teacher, swap, move, conflicts

All responses are JSON: `{ "success": true|false, "message": "...", "data": {} }`.  
Protected routes require header: `Authorization: Bearer <token>`.

See **EXAMPLE_API_CALLS.md** for request/response examples.

## Security

- Passwords hashed with `password_hash()` (bcrypt).
- PDO prepared statements only (no raw SQL with user input).
- Token auth via middleware; tokens stored in `user_tokens` with expiry.
- Validation and error handling; avoid exposing stack traces in production (`APP_DEBUG=0`).

## Timetable generation

- **Service:** `Services\TimetableGenerator`
- **Constraints:** one teacher per slot, one class per slot, one room per slot, teacher availability respected.
- **Algorithm:** backtracking over allocations and slots; break periods are excluded from placement.
