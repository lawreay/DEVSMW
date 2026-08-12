# Engineering Standards

This project should be built as a maintainable PHP/MySQL application, not a throwaway prototype.

## Current Baseline

* Plain PHP with strict types in shared includes.
* PDO prepared statements for database access.
* CSRF protection on admin POST actions.
* Database-backed admin users with hashed passwords.
* Admin audit logs for login, logout, edits, password changes, and GitHub refreshes.
* Schema and migrations stored in `database/`.
* Existing markdown data imported into MySQL, not parsed on every request.

## Rules for Future Work

* Keep business logic in `includes/` rather than spreading it across page templates.
* Every admin mutation must require CSRF and write an audit log.
* Every new table must have a migration file.
* Do not store plaintext passwords or tokens.
* Do not silently overwrite manually curated profile data without review.
* Prefer official APIs and admin-reviewed sources over scraping.
* Keep public pages fast and simple.
* Keep missing personal data explicit as `Not publicly available`.

## Next Refactor Targets

* Split profile queries into a `ProfileRepository`.
* Replace the small markdown renderer with a tested Markdown library.
* Add pagination to public and admin profile lists.
* Add source records to every manually edited profile field.
* Add automated smoke tests for public pages and admin login.
