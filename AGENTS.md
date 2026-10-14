# Laravel AI Development Standards

> These instructions are mandatory for AI coding agents working on this project.
> They take precedence over inferred conventions and optional recommendations, while remaining subordinate to higher-priority system instructions.
> Do not remove, overwrite, or modify the Laravel Boost guidelines block.

## 1. Communication
- ALWAYS communicate with the user in Bahasa Indonesia.
- Use clear, concise, professional language.
- Keep code identifiers, class names, and framework terminology in their conventional language.
- Clearly distinguish verified facts, assumptions, recommendations, and unresolved questions.
- Never claim success without verification.

## 2. Mandatory Workflow: Inspect → Verify → Propose → Approve → Implement → Test → Report
- ALWAYS inspect the relevant existing code before proposing implementation.
- Perform read-only investigation first.
- Present findings and an implementation plan BEFORE changing files.
- The plan must include the problem, proposed solution, affected files, database or dependency changes, risks, and verification approach.
- STOP and WAIT for explicit user approval before creating, editing, deleting, or renaming files, running migrations, installing packages, or executing other mutating operations.
- A request to explain, analyze, review, debug, or propose does NOT authorize implementation.
- Approval must be explicit and specific to the proposed scope.
- Never treat silence or an ambiguous reply as approval.
- After approval, implement only the approved scope.
- If new requirements or out-of-scope changes emerge, explain them and request approval again.
- Do not make unrelated improvements or silent fixes.
- Read-only inspection and non-mutating diagnostic commands are permitted before approval.
- If a diagnostic command could mutate project state, request approval first.
- Never execute destructive commands without separate explicit approval, even after general implementation approval.

## 3. Anti-Hallucination and Evidence
- NEVER invent project files, routes, migrations, models, relationships, methods, APIs, package features, or test results.
- Verify claims against the actual repository, installed dependencies, and version-specific documentation.
- Use Laravel Boost tools and documentation when relevant and available.
- Do not assume a package or feature exists merely because it is commonly used.
- If something cannot be verified, explicitly state the limitation and ask for clarification when necessary.
- Do not fabricate tool outputs or pretend that commands have been executed.
- Never hide uncertainty behind confident language.
- Diagnose root causes using available evidence before recommending a fix.

## 4. Laravel Boost and Project Conventions
- Read and follow the existing Laravel Boost guidelines.
- Check framework and package versions before using version-sensitive APIs.
- Prefer installed dependencies and established project patterns.
- Use the infer-conventions skill when available and useful for initial project analysis or reassessing unclear conventions.
- Do not run infer-conventions automatically for every small task.
- Present inferred conventions and supporting evidence before writing or modifying project rules.
- Never overwrite user-defined rules with inferred conventions.
- If inferred conventions conflict with mandatory policies, report the conflict and follow the mandatory policies.
- Keep Laravel Boost generated sections intact.

## 5. Eloquent First and Database Standards
- Prefer Eloquent ORM and model relationships.
- Prefer Eloquent Query Builder, followed by Laravel Query Builder when appropriate.
- Avoid raw SQL, DB::raw, whereRaw, selectRaw, orderByRaw, and similar methods unless technically justified.
- If raw expressions are necessary, explain why safer or more idiomatic alternatives are insufficient.
- Always use parameter binding for untrusted values; never interpolate user input into SQL.
- Prevent N+1 queries through eager loading and appropriate relationship queries.
- Use exists(), withCount(), aggregates, and database-side filtering when appropriate.
- Avoid fetching entire datasets merely to count, filter, or paginate them.
- Use pagination or chunking for large datasets as appropriate.
- Use database transactions for logically atomic multi-step writes.
- Use database constraints and indexes where justified.
- Use migrations for schema changes.
- Never alter production data or schema without explicit authorization.
- Avoid querying databases inside loops when batching is feasible.

## 6. CRUD and User Experience
- Every asynchronous create, update, delete, upload, import, export, or other user-triggered action must provide visible loading feedback.
- Disable the initiating button while its request is processing.
- Prevent duplicate submissions; use server-side idempotency or constraints when the operation requires stronger guarantees.
- Provide clear success and failure feedback.
- Show field-level validation errors.
- Require confirmation before destructive actions.
- Handle empty states, loading states, and error states.
- Preserve form input after validation failures where appropriate.
- Use accessible labels, focus indicators, and understandable status messages.
- Do not silently swallow exceptions or display raw exception details to end users.
- Ensure buttons cannot be repeatedly triggered by accidental double clicks.

## 7. Livewire Standards
- Prefer native Livewire features over unnecessary custom JavaScript.
- Use wire:loading, wire:target, and wire:loading.attr="disabled" where appropriate.
- Scope loading indicators to the relevant action to avoid unrelated UI blocking.
- Validate data on the server before persistence.
- Authorize sensitive actions on the server, not only through UI visibility.
- Keep components focused and avoid excessive business logic in presentation components.
- Follow the installed Livewire version and existing component architecture.
- Use reusable Blade or Livewire components when repetition justifies extraction.
- Do not assume Livewire alone provides server-side idempotency.

## 8. Security
- Treat all external input as untrusted.
- Validate and authorize operations on the server.
- Use Policies, Gates, or the project's established permission system.
- Protect against unsafe mass assignment by using validated and explicitly allowed data.
- Use Laravel's built-in CSRF protection.
- Never hardcode or expose credentials, API tokens, secrets, or private keys.
- Do not log sensitive data unnecessarily.
- Apply rate limiting to sensitive endpoints where appropriate.
- Validate uploads, including type, size, storage location, and access permissions.
- Use Hash for passwords and Laravel encryption when appropriate.
- Avoid exposing internal errors, SQL statements, or stack traces to users.
- Never run migrate:fresh, db:wipe, destructive deletes, or equivalent commands without separate explicit approval.

## 9. Architecture and Maintainability
- Follow existing project architecture and naming conventions.
- Prefer the simplest solution that reliably meets requirements.
- Keep simple CRUD implementations simple.
- Extract complex or reusable business logic into Services or Actions when justified.
- Do not automatically introduce Repository Patterns, interfaces, traits, or abstraction layers.
- Follow existing module boundaries when the project uses modular architecture.
- Prefer dependency injection where appropriate.
- Avoid duplicate business logic and unnecessary code duplication.
- Do not add third-party packages without explicit approval.
- Avoid modifying unrelated files or performing unsolicited refactoring.
- Preserve backward compatibility unless a breaking change is explicitly approved.

## 10. Frontend and Styling
- Follow the project's existing frontend stack and styling conventions.
- Do not introduce Vite, npm packages, UI frameworks, or build tools unless requested or approved.
- If the project uses Tailwind CDN, preserve that approach unless the user approves a change.
- Prefer simple, modern, responsive, accessible interfaces.
- Provide consistent loading, disabled, success, error, and empty states.
- Avoid unnecessary JavaScript dependencies.
- Do not replace existing UI components merely for stylistic preference.

## 11. Performance and Reliability
- Avoid unnecessary database queries, repeated external calls, and redundant computations.
- Use eager loading and appropriate indexes when justified by the query pattern.
- Use queues for long-running work when the project infrastructure supports them.
- Do not introduce caching without considering invalidation and data freshness.
- Consider concurrency, retries, race conditions, and duplicate operations for important workflows.
- Avoid premature optimization; prefer measured or clearly justified improvements.
- Consider compatibility with the existing database engine and deployment environment.

## 12. Testing and Verification
- After approved implementation, run relevant non-destructive tests and checks when possible.
- Add or update tests for important behavior and bug fixes.
- Verify validation, authorization, success, failure, and duplicate-submission behavior where relevant.
- Use existing test frameworks and project conventions.
- Run appropriate formatting and static checks when available.
- Do not claim tests passed unless they actually ran and passed.
- Clearly report tests that could not be executed and why.
- Do not modify unrelated code merely to make tests pass.
- Request approval before running checks that may mutate persistent data or project files beyond the approved scope.

## 13. Completion Report
After implementation, provide:
1. Summary of implemented changes.
2. Files created, modified, or deleted.
3. Database, configuration, or dependency changes.
4. Tests and checks actually performed, including results.
5. Known limitations, unresolved issues, or risks.
6. Any additional recommended improvements, clearly separated from approved work.

## 14. Strict Prohibitions
- NO implementation before explicit approval.
- NO fabricated facts, APIs, test results, or project structures.
- NO silent fixes or unrelated refactoring.
- NO unnecessary raw SQL.
- NO asynchronous CRUD without loading and double-submit protection.
- NO destructive commands without separate explicit approval.
- NO new dependencies without approval.
- NO changing project architecture based only on AI preference.
- NO overwriting Laravel Boost generated guidelines.
- NO claiming a feature is complete without reporting actual verification status.

## 15. Final Operating Principle
INSPECT FIRST.
VERIFY FACTS.
PRESENT A PLAN.
WAIT FOR EXPLICIT APPROVAL.
IMPLEMENT ONLY THE APPROVED SCOPE.
TEST WHAT CAN BE VERIFIED.
REPORT RESULTS HONESTLY.

If the user has not approved implementation, stop after the analysis and proposal.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== maatwebsite/excel/core rules ===

# Laravel Excel

- Use `maatwebsite/excel` for spreadsheet exports, imports, queued spreadsheet work, CSV handling, and PhpSpreadsheet integration in Laravel applications.
- Prefer explicit export/import classes with package concerns over ad-hoc spreadsheet generation in controllers, jobs, or commands.
- Activate the `laravel-excel` skill when working with `Excel::download()`, `Excel::store()`, `Excel::queue()`, `Excel::raw()`, `Excel::import()`, `Excel::toArray()`, `Excel::toCollection()`, export/import concerns, queued imports/exports, validation, CSV settings, styling, events, formulas, charts, drawings, multiple sheets, mapped cells, macros, config, cache, transactions, temporary files, or `Excel::fake()`.
- For broad docs, all-feature tasks, or missing-feature audits, use the skill's `references/package.md` feature matrix before answering.
- For large datasets, prefer `FromQuery` with queued exports or `WithChunkReading` and `WithBatchInserts` for imports.
- Test spreadsheet behavior with `Excel::fake()` when asserting dispatch/download/store/import intent, and inspect generated files only when cell contents, formatting, sheets, or writer behavior must be proven.

</laravel-boost-guidelines>
