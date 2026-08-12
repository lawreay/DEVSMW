# Project Phases

## Phase 1: Local MVP - Complete

Completed on 12 August 2026.

* [x] Create MySQL schema.
* [x] Import existing markdown profiles.
* [x] Build public search page.
* [x] Build public profile page.
* [x] Build admin login.
* [x] Build admin profile editor.
* [x] Add one-click GitHub refresh per profile.
* [x] Add one-click GitHub refresh for all profiles.
* [x] Add legal, privacy, and data handling docs.
* [x] Verify PHP syntax.
* [x] Verify app routes through WAMP/Apache.
* [x] Verify admin login and dashboard access.

Phase 1 database status:

* Profiles imported: 256
* Projects imported: 1,316
* Missing private-contribution ranks: 0

Post-MVP hardening added:

* Database-backed admin users.
* Hashed admin passwords.
* Admin password-change page.
* Admin audit logs.
* Migration file for auth/audit tables.
* Engineering standards document.

## Phase 2: Data Quality - Next

* Add source records for every non-GitHub data point.
* Add profile claim workflow.
* Add correction and opt-out form.
* Add admin review status for risky records.
* Add duplicate detection.
* Add better markdown rendering.

## Phase 3: External Integrations

* Use GitHub API with an authenticated token.
* Add approved search API integration such as Google Programmable Search or Bing Web Search.
* Add LinkedIn URLs manually or through approved partner/API access only.
* Add audit logs for every admin edit and refresh.

## Phase 4: Public Launch

* Replace default admin password.
* Add HTTPS.
* Add backups.
* Add abuse reporting.
* Add legal review for Terms, Privacy Policy, and data processing notices.
* Add monitoring and rate limits.

## Phase 5: Community Features

* Profile owner accounts.
* Verified badges.
* Portfolio/project submissions.
* Organisation pages.
* Developer ecosystem reports and dashboards.
