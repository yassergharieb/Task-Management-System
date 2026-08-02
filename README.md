# Task Management System API

Laravel API for user authentication, project management, task management, attachments, dashboard counters, Redis-backed cache/queues, and overdue task notifications.

## Tech Stack

- PHP 8.4
- Laravel 13
- Laravel Sanctum token authentication
- MySQL 8.4
- Redis 8
- Nginx
- Mailpit
- Pest, Pint, and Larastan

## Architecture

The application uses a layered Laravel API structure:

```text
HTTP route
  -> Controller
  -> Form Request validation
  -> Service contract
  -> Service
  -> Repository contract
  -> Repository / Query Builder
  -> Eloquent model
  -> API Resource response
```

Important directories:

- `routes/api.php` - versioned API routes under `/api/v1`.
- `app/Http/Controllers/Api/V1` - API controllers.
- `app/Http/Requests` - request validation.
- `app/Http/Resources` - response transformers.
- `app/Services` and `app/Contracts/Services` - business logic and service contracts.
- `app/Repositories` and `app/Contracts/Repositories` - persistence layer.
- `app/QueryBuilders` - reusable project/task filtering.
- `app/Enums` - project status, task status, and task priority values.
- `app/Jobs` and `app/Notifications` - async attachment cleanup and overdue task notification workflow.
- `database/migrations` - schema definitions.
- `tests` - Pest tests.

Core domain notes:

- Users own projects.
- Projects have many tasks.
- Projects and tasks can have polymorphic attachments.
- Ownership checks are enforced in the service layer and return not found responses for resources owned by another user.
- API authentication uses Sanctum bearer tokens.
- Dashboard counters are cached through the cache service.

## Docker Setup

Requirements:

- Docker
- Docker Compose

Create the environment file:

```bash
cp .env.example .env
```

Build and start the containers:

```bash
docker compose up -d --build
```

Install PHP dependencies inside the app container:

```bash
docker compose exec app composer install
```

Generate the Laravel app key:

```bash
docker compose exec app php artisan key:generate
```

Run migrations:

```bash
docker compose exec app php artisan migrate
```

The API is available at:

```text
http://localhost:8080/api/v1
```

Included services:

- API via Nginx: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`
- Mailpit dashboard: `http://localhost:8025`
- MySQL forwarded port: `3306`
- Redis forwarded port: `6379`

Optional queue worker:

```bash
docker compose --profile worker up -d queue
```

Useful Docker commands:

```bash
docker compose ps
docker compose logs -f app
docker compose exec app php artisan test
docker compose exec app composer check
docker compose down
```

To reset Docker database volumes:

```bash
docker compose down -v
docker compose up -d --build
docker compose exec app php artisan migrate
```

## Local Setup

Requirements:

- PHP 8.4 with required Laravel extensions
- Composer 2
- MySQL
- Redis

Create the environment file:

```bash
cp .env.example .env
```

For native local development, update `.env` so the app connects to local services instead of Docker service names:

```dotenv
APP_URL=http://localhost:8000
APP_PORT=8000
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_poc
DB_USERNAME=laravel
DB_PASSWORD=password
REDIS_HOST=127.0.0.1
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

Install dependencies and prepare the app:

```bash
composer install
php artisan key:generate
php artisan migrate
```

Run the API locally:

```bash
php artisan serve
```

The local API is available at:

```text
http://localhost:8000/api/v1
```

Run the queue worker locally when testing queued jobs:

```bash
php artisan queue:listen --tries=1
```

## Artisan Commands

Common commands:

```bash
php artisan migrate
php artisan migrate:fresh
php artisan route:list --path=api/v1
php artisan queue:work
php artisan queue:listen --tries=1
php artisan storage:link
php artisan optimize:clear
php artisan test
```

Docker equivalents:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh
docker compose exec app php artisan route:list --path=api/v1
docker compose exec app php artisan queue:work
docker compose exec app php artisan storage:link
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan test
```

Code quality:

```bash
composer format
composer format:check
composer analyse
composer test
composer check
```

## API Documentation

Swagger/OpenAPI documentation is available at:

```text
docs/openapi.yaml
```

Base paths:

- Docker: `http://localhost:8080/api/v1`
- Local artisan server: `http://localhost:8000/api/v1`

Headers:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>
```

Use `Authorization` only for protected endpoints.

Standard success response:

```json
{
  "message": "Operation successful",
  "data": {},
  "token": "optional-sanctum-token"
}
```

Validation errors use Laravel's standard `422 Unprocessable Entity` JSON response.

### Authentication

#### Register

```http
POST /register
```

Body:

```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

Rules:

- `name` is required, string, max 255.
- `email` is required, lowercase email, unique, max 255.
- `password` is required and confirmed.

Response: `201 Created`

#### Login

```http
POST /login
```

Body:

```json
{
  "email": "test@example.com",
  "password": "password"
}
```

Response: `200 OK`

#### Logout

```http
POST /logout
```

Protected: yes

Response: `200 OK`

### Dashboard

#### Get Dashboard Statistics

```http
GET /dashboard
```

Protected: yes

Response data:

```json
{
  "total_projects": 0,
  "active_projects": 0,
  "total_tasks": 0,
  "completed_tasks": 0,
  "pending_tasks": 0,
  "overdue_tasks": 0
}
```

### Projects

Project statuses:

- `active`
- `completed`
- `archived`

#### List Projects

```http
GET /projects
```

Protected: yes

Query parameters:

- `status` - optional, one of `active`, `completed`, `archived`.
- `search` - optional string, max 255.
- `per_page` - optional integer, 1 to 100.

#### Create Project

```http
POST /projects
```

Protected: yes

Body:

```json
{
  "name": "Project Alpha",
  "description": "First project in the workspace.",
  "status": "active"
}
```

For attachments, submit `multipart/form-data` with `attachments[]` files. Allowed file types are `pdf`, `doc`, `txt`, and `md`; max file size is 10 MB each.

Response: `201 Created`

#### View Project

```http
GET /projects/{project}
```

Protected: yes

#### Update Project

```http
PATCH /projects/{project}
```

Protected: yes

Body fields are optional:

```json
{
  "name": "Project Alpha Updated",
  "description": "Updated project details.",
  "status": "completed"
}
```

#### Delete Project

```http
DELETE /projects/{project}
```

Protected: yes

Deleting a project also dispatches attachment cleanup for the project and its tasks.

### Tasks

Task statuses:

- `todo`
- `in_progress`
- `done`

Task priorities:

- `low`
- `medium`
- `high`

#### List Project Tasks

```http
GET /projects/{project}/tasks
```

Protected: yes

Query parameters:

- `status` - optional, one of `todo`, `in_progress`, `done`.
- `priority` - optional, one of `low`, `medium`, `high`.
- `search` - optional string, max 255.
- `per_page` - optional integer, 1 to 100.

#### Create Task

```http
POST /projects/{project}/tasks
```

Protected: yes

Body:

```json
{
  "title": "Prepare launch checklist",
  "description": "Write rollout tasks.",
  "priority": "high",
  "status": "todo",
  "due_date": "2026-08-15"
}
```

For attachments, submit `multipart/form-data` with `attachments[]` files. Allowed file types are `pdf`, `doc`, `txt`, and `md`; max file size is 10 MB each.

Response: `201 Created`

#### View Task

```http
GET /tasks/{task}
```

Protected: yes

#### Update Task

```http
PATCH /tasks/{task}
```

Protected: yes

Body fields are optional:

```json
{
  "title": "Prepare launch checklist updated",
  "description": "Updated rollout tasks.",
  "priority": "medium",
  "status": "in_progress",
  "due_date": "2026-08-20"
}
```

#### Delete Task

```http
DELETE /tasks/{task}
```

Protected: yes

Deleting a task dispatches attachment cleanup for that task.

## Postman Collection

The Postman collection is stored at:

```text
collection.json
```

Default collection variables:

- `base_url`: `http://localhost:8080`
- `name`: `Test User`
- `email`: generated during the register request pre-script
- `password`: `password`
- `token`: saved automatically after register or login
- `project_id`: saved after creating a project
- `task_id`: saved after creating a task

Recommended run order:

1. Authentication / Register
2. Dashboard / Get Dashboard Statistics
3. Projects / Create Project
4. Projects / List Projects
5. Projects / View Project
6. Projects / Update Project
7. Tasks / Create Task
8. Tasks / List Tasks
9. Tasks / View Task
10. Tasks / Update Task
11. Tasks / Delete Task
12. Cleanup / Delete Project
13. Cleanup / Logout

For local artisan server usage, change `base_url` in Postman to:

```text
http://localhost:8000
```

## Testing

Run tests:

```bash
php artisan test
```

Run the full quality gate:

```bash
composer check
```

With Docker:

```bash
docker compose exec app php artisan test
docker compose exec app composer check
```
