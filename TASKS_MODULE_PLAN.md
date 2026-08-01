# Tasks Module Implementation Plan

## Repository Architecture Notes

- Laravel API app using versioned routes under `routes/api.php`.
- Current projects module is layered as controller -> form requests/resources -> service contract -> service -> repository contract -> repository -> custom query builder -> Eloquent model.
- Attachments are polymorphic through `attachments.attachable_type` and `attachments.attachable_id`.
- Projects own attachments through `Project::attachments()`, and attachment responses use `AttachmentResource`.
- Ownership is enforced in the service layer by checking the authenticated user's id.
- Dependency bindings live in `AppServiceProvider`.
- Existing tests use Pest with in-memory SQLite.

## Task Module Steps

1. Add task enums for priority and status.
2. Add `tasks` migration with `project_id`, `title`, `description`, `priority`, `status`, and `due_date`.
3. Add `Task` model with `project`, `attachments`, and enum/date casts.
4. Add `Project::tasks()` relation.
5. Add task repository and service contracts.
6. Add task repository using a custom `TaskQueryBuilder`.
7. Add task service with project ownership checks and task attachment handling.
8. Add task form requests for create, update, and list filters.
9. Add `TaskResource` including project id, fields, and attachments.
10. Add `TaskController` APIs for create, update, delete, list, and show.
11. Register routes under authenticated `v1` API routes.
12. Register service container bindings and task morph map.
13. Add focused feature/unit tests for CRUD, filters, search, ownership, attachments, and query builder behavior.
14. Run formatting/static checks/tests as available.
