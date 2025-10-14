
# Backend Project

## Overview

This is the **Backend** part of the project, built with **Laravel 12**.

## Technologies Used

- **Laravel 12** - for building the API.
- **MySQL** - for database storage.
- **Laravel Sanctum** - for authentication.
- **Spatie/laravel-permission** - for managing user roles and permissions.

## Prerequisites

- **PHP 8.1 or above**
- **Composer** - Dependency management tool for PHP
- **MySQL** - Database server

## Installation

### 1. Clone the repository:

```bash
git clone <repository-url>
```

### 2. Navigate to the project folder:

```bash
cd <backend-directory>
```

### 3. Install dependencies:

```bash
composer install
```

### 4. Set up the environment variables:

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Then, configure the database and other environment variables in the `.env` file.

### 5. Generate the application key:

```bash
php artisan key:generate
```

### 6. Run database migrations:

```bash
php artisan migrate
```

### 7. Start the Laravel development server:

```bash
php artisan serve
```

This should start the server at [http://localhost:8000](http://localhost:8000).

## Available Commands

- **`php artisan migrate`**: Runs the database migrations.
- **`php artisan db:seed`**: Seeds the database with sample data.
- **`php artisan make:controller <ControllerName>`**: Creates a new controller.
- **`php artisan make:model <ModelName>`**: Creates a new Eloquent model.
- **`php artisan make:migration <MigrationName>`**: Creates a new migration file.

## Authentication

The application uses **Laravel Sanctum** for API authentication. You can use it to authenticate users through **Bearer tokens**.

## Project Structure

```
/app                # Application logic (controllers, models, etc.)
/database           # Database migrations and seeds
/routes             # Web and API routes
/config             # Configuration files
/public             # Public assets (e.g., images, JavaScript)
```

## API Documentation

For detailed API endpoints and usage, refer to the `/docs` folder or the `POSTMAN` collection if provided.
