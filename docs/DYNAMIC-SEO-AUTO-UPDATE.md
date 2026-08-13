# Dynamic SEO - How DEVSMW Auto-Updates SEO When Data Changes

## Overview
DEVSMW automatically updates all SEO metadata (meta tags, structured data, sitemap) whenever profile data is changed. This ensures search engines always see current information.

## How It Works

### Data Update Flow

```
Admin edits profile in /admin/profile_edit.php
    ↓
Database UPDATE with updated_at = NOW()
    ↓
Profile page (/profile.php) fetches fresh data
    ↓
SEO meta tags generated from fresh data
    ↓
Search engine crawler receives current metadata
```

### Real Example: Bio Update

**Step 1: Admin Updates Bio**
```
User: Click admin/profile_edit.php?id=5
Edit: Change bio from "Python developer" to "Full-stack Python & JavaScript expert"
Click: Save profile
Database: UPDATE profiles SET bio = ?, updated_at = NOW() WHERE id = 5
```

**Step 2: Meta Tags Auto-Update**
- Database updated_at field: `2026-08-13 14:32:00`
- Next time profile is viewed: `profile.php?u=username` fetches fresh data
- Meta description auto-generated from bio: `"Full-stack Python & JavaScript expert"`
- OG tags updated with new bio
- Person schema updated with new description

**Step 3: Search Engine Sees Fresh Data**
- Sitemap shows profile lastmod: `2026-08-13` (from updated_at)
- Google Search Console crawls at priority (sees new lastmod)
- Profile page meta tags reflect new bio
- Rich snippets updated with new data

---

## SEO Components That Auto-Update

### 1. **Meta Description** ✓ Dynamic
- **Source:** Profile bio or strengths field
- **Updates:** Every time bio/strengths is changed
- **Code:** `profile.php` fetches fresh data, calls `meta_description($profileSummary)`
- **Result:** Google snippets show current bio

### 2. **Open Graph Tags** ✓ Dynamic
- **Source:** Profile name, bio, GitHub username
- **Updates:** Every change to name/bio
- **Code:** `og_tags()` pulls fresh profile data
- **Result:** Facebook/LinkedIn preview shows current info

### 3. **Twitter Cards** ✓ Dynamic
- **Source:** Profile name, bio, GitHub avatar
- **Updates:** Every change to profile
- **Code:** `twitter_card()` generates from current data
- **Result:** Twitter share preview is current

### 4. **JSON-LD Person Schema** ✓ Dynamic
- **Source:** All profile fields (name, title, email, website, location, bio, skills)
- **Updates:** Every profile field change
- **Code:** `schema_person($profile)` uses fresh database data
- **Result:** Google's rich snippet shows current details

### 5. **XML Sitemap** ✓ Dynamic
- **Source:** Database profiles table with updated_at dates
- **Updates:** Generates fresh on every request
- **Code:** `sitemap.xml.php` queries database, shows current lastmod dates
- **Result:** Google knows exactly when profile was last updated

### 6. **Page Title** ✓ Dynamic
- **Source:** Profile name field
- **Updates:** When name is changed
- **Code:** `profile.php` uses current `$displayName` from database
- **Result:** Search results show current name

### 7. **Keywords Meta Tag** ✓ Dynamic
- **Source:** Profile strengths and project languages
- **Updates:** When strengths or projects change
- **Code:** Extracted from fresh database data in `profile.php`
- **Result:** Keywords reflect current skills

---

## Technical Implementation

### 1. Database Timestamps
Every profile update sets `updated_at = NOW()`:

```php
// In admin/profile_edit.php
$update = db()->prepare(
    'UPDATE profiles SET
        name = ?, bio = ?, ...,
        updated_at = NOW()  // ← Auto-updates timestamp
     WHERE id = ?'
);
```

### 2. Fresh Data Fetching
Profile pages always fetch fresh data from database:

```php
// In profile.php
$stmt = db()->prepare('SELECT * FROM profiles WHERE github_username = ? AND visibility = "published"');
$stmt->execute([$username]);
$profile = $stmt->fetch();  // ← Fresh data every time
```

### 3. Dynamic Meta Tag Generation
SEO helpers generate tags from live data:

```php
// These functions use $profile (from database) to generate tags
<?= meta_description($profileSummary) ?>        // From fresh bio
<?= og_tags([...profile fields...]) ?>          // From fresh data
<?= schema_person($profile) ?>                  // From fresh profile
```

### 4. Cache Control Strategy
Cache times optimized for SEO freshness:

**Public Pages (index, profile, profiles):**
- `Cache-Control: public, max-age=300` (5 minutes)
- Allows fast response for users
- Search engines see fresh metadata within 5 min

**Sitemap:**
- `Cache-Control: public, max-age=86400` (24 hours)
- Reduces database load
- Still highly responsive to updates

**Admin Pages:**
- `Cache-Control: no-cache, no-store, must-revalidate`
- Never cached
- Always fresh

**Static Assets (CSS, JS, images):**
- `Cache-Control: public, max-age=2592000` (30 days)
- No need to change frequently

### 5. Search Engine Crawling
Google/Bing crawl updated pages based on:
- Sitemap lastmod dates
- robots.txt crawl frequency hints
- Link popularity
- Previous crawl frequency

**Result:** Updated profile pages crawled within hours/days

---

## What Updates When

### Profile Name Changed
✓ Page title updates  
✓ Meta description updates  
✓ OG title updates  
✓ Schema Person name updates  
✓ Sitemap lastmod updates  

### Profile Bio Changed
✓ Meta description updates (auto-generated from bio)  
✓ OG description updates  
✓ Twitter Card description updates  
✓ Schema Person description updates  
✓ Sitemap lastmod updates  

### Profile Skills/Strengths Changed
✓ Keywords meta tag updates  
✓ Schema Person skills section updates  
✓ OG description updates (if includes skills)  
✓ Sitemap lastmod updates  

### Profile Email/Website/LinkedIn Changed
✓ Schema Person contact info updates  
✓ OG tags update (if shared)  
✓ Sitemap lastmod updates  

### Profile Rank Changed
✓ Sitemap lastmod updates  
✓ Rank affects sort order in index.php (for top 10)  
✓ JSON-LD aggregateRating could update (future feature)  

### GitHub Projects Changed
✓ Keywords meta tag updates (if languages change)  
✓ Schema Person skills section updates  
✓ OG description might update  
✓ Sitemap lastmod updates  

### Profile Visibility Changed
✓ Profile appears/disappears from sitemap  
✓ Page becomes indexed/deindexed  
✓ Robots.txt rules apply  

---

## Verification Checklist

### Test 1: Profile Bio Update
```
1. Admin: Edit profile_edit.php?id=5
2. Change: Bio field to new text
3. Click: Save profile
4. Check: 
   - Database: SELECT updated_at FROM profiles WHERE id = 5;
   - Should show current timestamp ✓
5. View: Visit profile.php?u=username in browser
6. View source: Check meta description contains new bio ✓
7. Sitemap: Check sitemap.xml for updated lastmod date ✓
```

### Test 2: Profile Name Update
```
1. Admin: Edit name field
2. Save: Click save profile
3. Check page title: Browser tab should show new name ✓
4. View source: <title> should contain new name ✓
5. Sitemap: lastmod should be today's date ✓
```

### Test 3: Schema Freshness
```
1. Edit: Change profile title field
2. View source: profile.php page source
3. Search: Find "jobTitle" in JSON-LD schema
4. Verify: Schema contains new job title ✓
```

### Test 4: Cache Validation
```
1. View: profile.php page
2. Browser dev tools: Network tab
3. Look at Response headers:
   - Cache-Control: public, max-age=300 ✓
   - For admin pages: no-cache, no-store ✓
```

---

## How Search Engines Use This

### Google Workflow
```
1. Crawls sitemap.xml
2. Sees lastmod="2026-08-13" for profile.php?u=username
3. Compares with last crawl date
4. If newer: Re-crawls the page
5. Extracts: Meta tags, schema markup, content
6. Updates: Search index with fresh data
7. Result: Updated snippets appear in search results
```

**Timeline:** Usually 1-7 days for pages with high authority

### Bing Workflow
```
1. Similar to Google
2. Also checks robots.txt and sitemap
3. Uses lastmod dates to prioritize
4. Re-indexes changed pages
```

**Timeline:** Usually 1-14 days

### Other Search Engines
- DuckDuckGo: Uses Bing's index
- Yandex: Crawls like Google/Bing
- Baidu (China): Similar process

---

## Best Practices

### ✓ DO
- Update profile bio/name/skills regularly (keeps SEO fresh)
- Use meaningful, keyword-rich bios (helps search visibility)
- Keep GitHub projects updated (languages are keywords)
- Use correct job titles (included in schema)
- Maintain unique profiles (different bios/skills per person)
- Monitor search rankings (see if changes help)

### ✗ DON'T
- Don't change profiles frequently (too often = crawl waste)
- Don't stuff keywords unnaturally (looks spammy)
- Don't duplicate bios across profiles (duplicate content)
- Don't hide information in markup (misleading SEO)
- Don't block profile pages in robots.txt (need to be crawlable)

---

## Monitoring Updates

### Use Google Search Console
```
1. Go: search.google.com/search-console
2. Select: DEVSMW property
3. Menu: Coverage report
4. Filter: By last crawl date
5. See: When profiles were last crawled
6. Update: Submit URL to re-crawl (manual re-index)
```

### Check Sitemap in Console
```
1. Console: Sitemaps section
2. URL: Add /DEVSMW/sitemap.xml
3. Monitor: "Entries in sitemap" (should = profile count)
4. Check: "Discovered URLs" (compared to submitted)
```

### Monitor Rankings
Use tool of choice:
- SEMrush
- Ahrefs
- Moz
- Rank Tracker

Track keywords like:
- "[Developer Name] Malawi"
- "[Developer Name] developer"
- "[Skills] developer Malawi"

---

## Troubleshooting

### SEO Not Updating After Profile Edit

**Problem:** Changed profile bio but Google snippet hasn't updated

**Check:** 
1. Admin panel shows saved? ✓
2. Profile page shows new bio? ✓ (view source, check meta description)
3. Sitemap shows updated lastmod? ✓ (check sitemap.xml)

**Cause:** Google cache, not your site  
**Solution:** 
1. Wait 5-7 days for automatic re-crawl
2. OR manual re-index in Search Console
3. OR run "URL inspection" tool in Search Console

### Sitemap Not Updating

**Problem:** New profile added but doesn't appear in sitemap

**Check:**
1. Profile visibility = "published"? ✓
2. Profile added to database? ✓
3. Visit sitemap.xml directly: /DEVSMW/sitemap.xml
4. Search for profile username in sitemap ✓

**Cause:** Database issue or visibility filter  
**Solution:**
1. Ensure profile visibility is "published"
2. Check database: SELECT * FROM profiles WHERE visibility = "published"
3. Re-visit sitemap.xml (should include profile now)

### Metadata Not Fresh in Browser

**Problem:** View profile page, see old metadata

**Check:**
1. Is browser caching? (Hard refresh: Ctrl+Shift+R)
2. Browser: Network tab → check response headers
3. Should see: Cache-Control header

**Solution:**
1. Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
2. Clear browser cache
3. Check in incognito window

---

## Future Enhancements

Potential improvements to SEO automation:

1. **Automatic schema image URLs**
   - Use profile picture URL in schema
   - Update in real-time

2. **Aggregate Ratings**
   - Add community voting/ratings
   - Include in schema markup
   - Update dynamically

3. **Structured project data**
   - Add schema for projects
   - Include stars, languages, links
   - Crawlable project pages

4. **Search preview tool**
   - Admin tool to preview how profile looks in Google
   - Show meta tags, rich snippet preview
   - Update live as you edit

5. **Crawl simulation**
   - Simulate Google crawl on demand
   - See what Google sees
   - Debug metadata issues

6. **SEO audit dashboard**
   - Monitor all profiles for SEO issues
   - Check for missing meta tags
   - Alert on outdated data

---

## Summary

**DEVSMW's SEO is fully automated:**
- ✓ Database updates set timestamps
- ✓ Fresh data fetched on every page load
- ✓ Meta tags generated from live data
- ✓ Sitemap reflects current state
- ✓ Cache times optimized for search engines
- ✓ No manual SEO updates needed

**When you update profile data, search engines automatically receive fresh metadata within 5 minutes, and full re-indexing typically occurs within 1-7 days.**
