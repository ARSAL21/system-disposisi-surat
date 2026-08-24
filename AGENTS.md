# Repository Guidelines

## Project Structure & Module Organization

This Laravel 13 application uses Inertia.js and Vue 3. Backend code lives in `app/`; keep controllers thin and validation in `app/Http/Requests`. Routes are split across `routes/`. Vue pages, layouts, components, composables, and types live under `resources/js/`; styles are in `resources/css/`. Put migrations, factories, and seeders in `database/`, and Pest tests in `tests/Feature` or `tests/Unit`. Static assets belong in `public/`; sensitive PDFs must use private storage.

## Build, Test, and Development Commands

- `composer setup` installs dependencies, initializes `.env`, migrates, and builds assets.
- `composer dev` runs the Laravel server, queue listener, and Vite development server together.
- `npm run build` creates the production frontend bundle.
- `composer test` checks PHP formatting and analysis, then runs Pest.
- `composer ci:check` additionally runs frontend linting, formatting, and type checks.
- `npm run lint` and `npm run format` apply ESLint and Prettier fixes.

## Coding Style & Naming Conventions

Use four-space indentation, TypeScript semicolons and single quotes, and Laravel PHP conventions. Run Pint (`composer lint`) for PHP and Prettier/ESLint for frontend code. Use PascalCase for classes and Vue components, camelCase for methods and variables, and `Test.php` suffixes for tests. Prefer domain names such as `CreateDisposition` over generic names. Keep business rules in focused actions/services.

## Testing Guidelines

Write Pest feature tests for HTTP, authorization, workflow, uploads, and database behavior; use unit tests for isolated logic. Cover both allowed and denied access, hierarchy enforcement, branching dispositions, audit records, and private files. Tests use in-memory SQLite. No coverage threshold is configured, but critical paths require automated tests. Run one test with `php artisan test --filter=ProfileUpdateTest`.

## Commit & Pull Request Guidelines

History contains only an initial commit, so no convention is established. Use short, imperative messages, such as `Add disposition hierarchy validation`. Pull requests should describe the change, security or migration impact, tests run, and linked issue. Include screenshots for UI changes and note deployment steps.

## Security & Domain Rules

Follow `instruksi.md` as the authoritative domain guide. Enforce authorization server-side; frontend visibility is not access control. Keep roles, permissions, positions, and assignments distinct, and preserve the disposition hierarchy. Use transactions for multi-record changes, append-only audits, validated server-owned fields, and SHA-256 hashes for original documents. Never commit `.env` or credentials.
# Repository Guidelines

## Project Structure & Module Organization

This Laravel 13 application uses Inertia.js and Vue 3.

Backend code lives in `app/`. Keep controllers thin, validation in `app/Http/Requests`, authorization in Policies, and business logic in focused Actions/Services.

Routes live under `routes/`.

Frontend code lives under `resources/js/`:

* pages;
* layouts;
* components;
* composables;
* types.

Styles live in `resources/css/`.

Database migrations, factories, and seeders live in `database/`.

Tests use Pest and live in:

* `tests/Feature`
* `tests/Unit`

Static public assets belong in `public/`.

Sensitive letter documents must never be stored as publicly accessible files.

---

## Context Router

Do **not** load every project document for every task.

Read only the documents relevant to the current work.

### `instruksi.md`

Read when:

* starting an unfamiliar task;
* making architectural or security-sensitive decisions;
* implementing authentication or authorization;
* handling files or sensitive data;
* creating Actions/Services;
* reviewing code quality;
* deciding whether an implementation violates project rules.

Contains the project's mandatory engineering and security guardrails.

---

### `system-design.md`

Read when working on:

* domain behavior;
* system architecture;
* disposition hierarchy;
* Position and Position Assignment;
* RBAC boundaries;
* visibility rules;
* disposition branching;
* audit architecture;
* document lifecycle;
* reporting architecture;
* TTE/electronic-signature boundaries.

Use this document to understand **how the system is conceptually designed**.

Do not use it as a substitute for database or workflow specifications.

---

### `database-schema.md`

Read when working on:

* migrations;
* Eloquent models;
* relationships;
* foreign keys;
* constraints;
* indexes;
* database transactions;
* database queries;
* document version persistence;
* disposition recipient persistence;
* audit persistence;
* reporting queries.

This document is the source of truth for the persistence model.

Do not invent new columns, relationships, or database abstractions that conflict with it without explicitly revisiting the design.

---

### `workflow-spec.md`

Read when working on:

* letter status;
* disposition status;
* state transitions;
* hierarchy validation;
* routing;
* creating or forwarding dispositions;
* completing branches;
* aggregate letter status;
* transition-related authorization;
* workflow tests.

This document is the source of truth for **valid workflow transitions and invariants**.

Do not introduce new states or transitions without updating the specification first.

---

## Context Selection Examples

### Creating a migration

Read:

```text
instruksi.md
database-schema.md
```

Add `system-design.md` only if the migration involves a domain decision not already clear from the schema.

---

### Implementing CreateDisposition

Read:

```text
instruksi.md
system-design.md
database-schema.md
workflow-spec.md
```

This operation touches domain rules, persistence, hierarchy, state transition, authorization, and audit.

---

### Creating a Vue dashboard component

Usually read:

```text
instruksi.md
```

Add `system-design.md` if domain semantics or visibility rules affect the component.

Do not load `database-schema.md` merely to build presentation components.

---

### Implementing a Policy

Read:

```text
instruksi.md
system-design.md
```

Add `workflow-spec.md` when permission depends on workflow state or transition.

Add `database-schema.md` only when relationship/query details are needed.

---

### Writing workflow tests

Read:

```text
instruksi.md
workflow-spec.md
```

Add:

```text
database-schema.md
```

when database relationships or constraints are involved.

---

### Refactoring frontend UI only

Read:

```text
instruksi.md
```

Do not load backend design documents unless the refactor changes domain behavior.

---

## Conflict Resolution

When documents appear to conflict, use this priority:

```text
instruksi.md
    ↓
system-design.md
    ↓
workflow-spec.md / database-schema.md
    ↓
implementation
```

`instruksi.md` contains project-wide constraints and must not be silently violated.

`system-design.md` defines the architecture and domain model.

`workflow-spec.md` and `database-schema.md` define specialized details within that architecture.

If implementation conflicts with documentation, do not silently modify behavior to fit existing code.

Determine whether:

1. the implementation is wrong; or
2. the requirement/design has intentionally changed.

If the design must change, update the relevant documentation deliberately.

---

## Build, Test, and Development Commands

* `composer setup` installs dependencies, initializes `.env`, migrates, and builds assets.
* `composer dev` runs the Laravel server, queue listener, and Vite development server together.
* `npm run build` creates the production frontend bundle.
* `composer test` checks PHP formatting and analysis, then runs Pest.
* `composer ci:check` additionally runs frontend linting, formatting, and type checks.
* `npm run lint` runs frontend linting.
* `npm run format` applies frontend formatting.
* `composer lint` runs Laravel Pint.

Before considering a task complete, run the smallest relevant test set.

Run the full test suite when the change affects shared domain behavior, authorization, workflow, or database structure.

---

## Coding Style & Naming Conventions

Use Laravel conventions for PHP.

Use:

* PascalCase for classes and Vue components;
* camelCase for PHP methods, TypeScript functions, and variables;
* descriptive domain terminology.

Prefer:

```text
CreateDisposition
ForwardDisposition
CompleteDisposition
RegisterIncomingLetter
activePositionAssignment
dispositionRecipient
```

Avoid vague names such as:

```text
Manager
Helper
process()
handleData()
doAction()
data
temp
item
```

unless the generic meaning is genuinely appropriate.

Keep functions focused on one responsibility.

Prefer early returns over deeply nested conditions.

Do not introduce abstraction merely to reduce line count.

---

## Backend Rules

Controllers coordinate HTTP concerns only.

Typical flow:

```text
Form Request
    ↓
Controller
    ↓
Authorization
    ↓
Action / Service
    ↓
Domain / Persistence
```

Use Form Requests for external input validation.

Use Policies or equivalent server-side authorization for protected resources.

Business rules belong in focused Actions/Services.

Multi-record operations that represent one business action must use database transactions.

Do not trust actor IDs, status, permission, Position, timestamps, or other server-owned fields from the frontend.

Never use uncontrolled mass assignment such as:

```php
Model::create($request->all());
```

---

## Frontend Rules

Vue handles presentation and interaction.

Frontend checks are UX conveniences, not authorization.

The backend must independently verify every protected action.

Split meaningful sections of a complex page into components.

Avoid giant components, but do not create components without a real responsibility.

Do not duplicate backend business rules in Vue.

---

## Testing Guidelines

Use Pest.

Prefer Feature tests for:

* HTTP behavior;
* authentication;
* authorization;
* workflow;
* database interaction;
* uploads;
* private document access.

Use Unit tests for isolated domain logic where appropriate.

Critical behavior must test both success and rejection paths.

Examples:

```text
authorized Kabag can access assigned letter
```

and:

```text
unrelated Kabag cannot access the same letter even when knowing its ID
```

Priority areas:

* RBAC;
* Position-based authorization;
* hierarchy enforcement;
* multiple recipients;
* branching dispositions;
* branch completion;
* aggregate letter status;
* audit records;
* document access;
* privilege escalation prevention.

When tests use SQLite, be aware that database constraints and behavior may differ from the production database. Database-specific behavior must be verified against the target database when relevant.

---

## Security Rules

Security is a primary project requirement.

Always enforce:

* server-side authorization;
* least privilege;
* private document storage;
* validated uploads;
* secure session handling;
* MFA requirements;
* controlled mass assignment;
* database consistency;
* auditability.

Original letter documents use SHA-256 fingerprints for integrity verification.

Hashing does not replace authorization, encryption, or TTE.

Audit records are application-level append-only and must not have normal application workflows for editing or deletion.

Never commit:

* `.env`;
* credentials;
* private keys;
* access tokens;
* MFA secrets;
* production secrets.

Assume the application may eventually be exposed to a public network.

---

## Commit & Pull Request Guidelines

Use short, imperative commit messages.

Example:

```text
Add disposition hierarchy validation
```

Pull requests should describe:

* what changed;
* why;
* security impact;
* database/migration impact;
* tests executed;
* deployment implications when relevant.

Include screenshots for meaningful UI changes.

Do not combine unrelated refactors with domain changes in the same pull request.

---

## Agent Working Rules

Before changing code:

1. classify the task;
2. load only the relevant project documents using the Context Router;
3. inspect the existing implementation;
4. identify affected domain invariants;
5. implement the smallest correct solution;
6. run relevant tests;
7. verify that no security or workflow rule was weakened.

Do not modify unrelated code.

Do not invent new architecture when an existing project pattern already solves the problem.

Do not silently change project requirements to accommodate existing code.

If a task conflicts with a documented invariant, surface the conflict before implementing a weaker design.
