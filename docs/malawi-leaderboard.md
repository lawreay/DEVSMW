# Malawi GitHub Contributions Leaderboard

## What it measures

The leaderboard ranks discoverable GitHub personal accounts by the number of contributions GitHub reports for the rolling 365-day period shown on the page. It is a separate public-data ranking and does not replace the manually assigned profile ranks stored in DEVSMW.

The collector reads GitHub's public contribution calendar. It does not request the `read:user` scope, which can expose private contribution counts. It does not collect private repository names, private commit details, email addresses, or other non-public activity.

## Discovery method and limitations

1. Search GitHub for accounts whose public profile location matches `location:Malawi`.
2. Keep up to 1,000 personal user accounts from the follower-sorted search results.
3. Fetch each candidate's contribution calendar for the same rolling 365-day period.
4. Sort the discovered accounts by contribution count, highest first. Usernames break ties consistently.
5. Publish the first 256 ranked accounts as a dated JSON snapshot.

This is a reproducible discovery sample, not a complete census of developers in Malawi. GitHub Search has a result ceiling, the follower-first candidate pool favours accounts with more followers, and developers whose profile location is blank, different, or not indexed as Malawi may be missed. The count represents GitHub's public contribution calendar, not a count of commits alone and not a measure of developer quality.

## Update schedule

GitHub Actions refreshes the snapshot every Monday at 04:17 UTC and supports manual runs from the Actions tab. The collector uses the workflow's short-lived `GITHUB_TOKEN`; no personal access token or additional secret is required. The workflow needs repository contents write permission to commit the generated JSON snapshot.

If GitHub's API is unavailable, rate-limits the workflow, or returns incomplete data, the collector fails without replacing the previous valid snapshot.

## Corrections and opt-out

A leaderboard entry is derived from a public GitHub account and its public location field. If an account is listed incorrectly, the profile owner can update their GitHub location or contact DEVSMW through the project's published contact channel to request a review. Opt-out requests must be handled consistently with the DEVSMW privacy and data-correction policy.
