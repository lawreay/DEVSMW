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

Phase 3 introduces trusted external data and search capabilities while preserving site quality and privacy.

### 3.1 GitHub API integration
* Add support for an authenticated GitHub token to avoid public rate limits.
* Fetch and refresh profile metadata from GitHub, including repositories, language data, stars, followers, and repo links.
* Store GitHub source records separately for audit and reconciliation.
* Add sync status indicators to the admin dashboard and profile records.

### 3.2 Search and discovery integration
* Add an approved search API such as Google Programmable Search or Bing Web Search for broader discoverability.
* Surface relevant public resources for developers, including GitHub repos, portfolio pages, and community content.
* Keep results focused on Malawi developers and avoid exposing private or sensitive data.
* Add a fallback to site search when external search is unavailable.

### 3.3 External profile links
* Support LinkedIn URLs as an approved external profile field.
* Require manual review or approved partner/API access before publishing LinkedIn links.
* Add optional fields for portfolio and social links that can be reviewed before display.
* Keep all external links under admin review to prevent spam and abuse.

### 3.4 Admin audit and review
* Log every admin edit, profile refresh, and integration sync event in audit logs.
* Add an admin review workflow for imported or updated external data.
* Surface integration-related warnings when data is stale or unverifiable.

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
