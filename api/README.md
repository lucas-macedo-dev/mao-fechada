<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Architecture

New feature work under `app/Http/Controllers/Api/V1` follows a layered convention:

```
Controller → Input DTO → Service → Repository → Model
                                 ↓
Controller ← Output DTO ← Service
```

- **Controllers** (`app/Http/Controllers/Api/V1`) validate the request, map validated data into an input DTO, call one service method, and shape the HTTP response from the returned output DTO. They never call Eloquent directly.
- **Services** (`app/Services`) hold business rules and orchestration (ownership checks via the `App\Services\Concerns\AuthorizesOwnership` trait, multi-step writes wrapped in `DB::transaction()`, cross-model coordination). They accept and return DTOs, never `Request` objects or raw arrays.
- **Repositories** (`app/Repositories`) hold persistence and query logic for one Eloquent model each, extending `BaseRepository`. They are plain classes (no interfaces) since there's a single Eloquent implementation.
- **DTOs** (`app/DataTransferObjects/Input` and `.../Output`) are readonly, hand-written classes. Input DTOs carry validated request data into services; output DTOs carry data back out and implement `toArray()` for JSON serialization, so services never leak raw Eloquent models to controllers.

Currently applied to the Category, Budget, RecurringTransaction, Transaction, and Dashboard domains. Other controllers (Auth, Billing, Tutorial, Password Reset, Email Verification, MercadoPago webhook) haven't been migrated yet — follow the same pattern when touching them next.

## Local Dev Setup

### Email verification for existing users

After enabling email verification (`MustVerifyEmail` on the `User` model), any existing user without `email_verified_at` set will be redirected to the verify-email page.

The migration `2026_06_25_000001_verify_existing_users.php` auto-verifies all existing users in non-production environments when you run:

```bash
php artisan migrate
```

To manually verify a single user in tinker:

```bash
php artisan tinker
>>> \App\Models\User::where('email', 'your@email.com')->first()->update(['email_verified_at' => now()]);
```

To verify all existing users manually:

```bash
php artisan tinker
>>> \App\Models\User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
