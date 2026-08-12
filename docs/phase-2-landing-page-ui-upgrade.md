# Phase 2: Landing Page UI / UX Upgrade

This document describes the Phase 2 update for the DEVSMW profile site. The focus is on a better landing page experience, clearer profile discovery, and a dedicated full profile list.

---

## 1. Landing page hero redesign

- The homepage now opens with a stronger hero section.
- It includes a clear headline, supporting copy, and a visible search field.
- The new hero highlights the site purpose: finding Malawi developer talent and technology trends.

**Why this matters**
- Visitors get immediate context.
- Search is front and center.
- The page feels more modern and welcoming.

---

## 2. CTA and action buttons

- Added a primary CTA button for browsing all profiles.
- Added a secondary button for direct access to the dedicated profile list page.
- The top navigation no longer exposes the admin login link.

**Why this matters**
- Reduces clutter on the public landing page.
- Helps users focus on discovery first.
- Keeps admin entry private unless accessed via URL.

---

## 3. Top 10 ranked profiles showcase

- The homepage now displays the top 10 published profiles ranked by `rank_private`.
- Each profile card includes name, GitHub handle, summary, location, work, and rank.
- This section is designed as a spotlight area for high-value profiles.

**Why this matters**
- Highlights the best profiles automatically.
- Provides quick value to users without requiring a search.
- Encourages exploration of profiles with higher visibility.

---

## 4. Technology usage chart

- Introduced a simple technology usage chart based on project languages.
- The chart displays the top technologies used by published profiles.
- It was built from aggregated `projects.language` data.

**Why this matters**
- Offers a quick community insight.
- Helps users understand what technologies are popular.
- Adds a visual analytics element to the page.

---

## 5. News and jobs section

- Added a tech news card with community updates.
- Added a jobs card labeled "Coming soon".
- This section presents future value and roadmap context.

**Why this matters**
- Reinforces the site as a living developer hub.
- Signals that job opportunities and news content are planned.
- Creates expectation for future updates.

---

## 6. Dedicated full profile list page

- Created `profiles.php` to show the entire published profile directory.
- The page supports search and displays up to 2,000 profiles.
- It uses the same card design as the homepage for consistency.

**Why this matters**
- Provides a one-click view of all profiles.
- Reduces reliance on the homepage to show full listings.
- Improves navigation and discoverability.

---

## 7. CSS and responsive design improvements

- Updated `assets/app.css` with new hero, card, chart, and section styles.
- Added responsive grid layouts for desktop and mobile.
- Improved spacing, typography, button visuals, and hover states.

**Why this matters**
- Makes the site feel cohesive and polished.
- Ensures the new layout works across screen sizes.
- Improves readability and usability.

---

## 8. Files changed

- `assets/app.css`
- `index.php`
- `profiles.php`

These files contain the design, homepage structure, and the new full list page.

---

## 9. Next recommended phase items

- Add pagination for the full profile list page.
- Replace static news items with dynamic content or external feeds.
- Add a proper jobs board or newsletter signup.
- Add featured skills or technology filters.
- Add profile category chips for faster browsing.

---

## Summary

Phase 2 focused on landing page user experience by making discovery clearer, highlighting top profiles, introducing community data, and creating a dedicated full profile list page. The site now feels more like a developer showcase and less like a plain directory.
