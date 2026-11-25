#!/usr/bin/env python3
"""Build a public-only, rolling 365-day GitHub contributions snapshot."""

from __future__ import annotations

import json
import os
import re
import sys
import time
from datetime import date, datetime, timedelta, timezone
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import Request, urlopen

API_ROOT = "https://api.github.com"
GRAPHQL_URL = "https://api.github.com/graphql"
REPOSITORY_ROOT = Path(__file__).resolve().parents[1]
OUTPUT_FILE = REPOSITORY_ROOT / "data" / "leaderboards" / "malawi.json"
CANDIDATE_LIMIT = 1000
LEADERBOARD_LIMIT = 256
BATCH_SIZE = 15
USERNAME_PATTERN = re.compile(r"^[A-Za-z0-9](?:[A-Za-z0-9-]{0,37}[A-Za-z0-9])?$")


class GitHubApiError(RuntimeError):
    pass


def request_json(url: str, token: str, payload: dict | None = None) -> dict:
    body = None if payload is None else json.dumps(payload).encode("utf-8")
    headers = {
        "Accept": "application/vnd.github+json",
        "Authorization": f"Bearer {token}",
        "User-Agent": "DEVSMW-Malawi-Leaderboard",
        "X-GitHub-Api-Version": "2022-11-28",
    }
    if body is not None:
        headers["Content-Type"] = "application/json"

    for attempt in range(4):
        request = Request(url, data=body, headers=headers, method="POST" if body else "GET")
        try:
            with urlopen(request, timeout=45) as response:
                return json.loads(response.read().decode("utf-8"))
        except HTTPError as error:
            response_body = error.read().decode("utf-8", errors="replace")
            retryable = error.code in (429, 500, 502, 503, 504) or (
                error.code == 403 and "rate limit" in response_body.lower()
            )
            if not retryable or attempt == 3:
                raise GitHubApiError(
                    f"GitHub API returned HTTP {error.code}: {response_body[:500]}"
                ) from error
            retry_after = error.headers.get("Retry-After")
            reset_at = error.headers.get("X-RateLimit-Reset")
            delay = int(retry_after) if retry_after and retry_after.isdigit() else 2 ** (attempt + 1)
            if reset_at and reset_at.isdigit():
                delay = max(delay, min(60, int(reset_at) - int(time.time()) + 1))
            time.sleep(max(1, min(delay, 60)))
        except (URLError, TimeoutError) as error:
            if attempt == 3:
                raise GitHubApiError(f"Could not reach GitHub API: {error}") from error
            time.sleep(2 ** (attempt + 1))

    raise GitHubApiError("GitHub API request failed after retries.")


def discover_candidates(token: str) -> tuple[list[dict], int]:
    candidates: list[dict] = []
    total_count = 0

    for page in range(1, 11):
        query = urlencode({
            "q": "location:Malawi type:user",
            "sort": "followers",
            "order": "desc",
            "per_page": 100,
            "page": page,
        })
        result = request_json(f"{API_ROOT}/search/users?{query}", token)
        if result.get("incomplete_results"):
            raise GitHubApiError("GitHub marked the user search results as incomplete; refusing to publish a partial ranking.")
        total_count = int(result.get("total_count", 0))
        items = result.get("items", [])
        if not isinstance(items, list):
            raise GitHubApiError("GitHub user search returned an invalid response.")

        candidates.extend(
            {
                "login": item.get("login", ""),
                "avatar_url": item.get("avatar_url", ""),
                "profile_url": item.get("html_url", ""),
            }
            for item in items
            if item.get("type") == "User" and item.get("login")
        )
        print(
            f"Discovered page {page}/10: {len(candidates)} user accounts "
            f"(GitHub search reports {total_count} matches).",
            flush=True,
        )
        if len(items) < 100 or len(candidates) >= CANDIDATE_LIMIT:
            break

    # Keep the first 1,000 follower-sorted candidates, matching the published
    # committers.top discovery approach as closely as GitHub Search permits.
    return candidates[:CANDIDATE_LIMIT], total_count


def contribution_query(users: list[dict], start: str, end: str) -> str:
    fields = []
    for index, user in enumerate(users):
        login = user["login"]
        if not USERNAME_PATTERN.fullmatch(login):
            raise GitHubApiError(f"GitHub returned an invalid username: {login!r}")
        fields.append(
            f'u{index}: user(login: "{login}") {{ '
            "login name location avatarUrl url "
            f'contributionsCollection(from: "{start}", to: "{end}") {{ '
            "contributionCalendar { totalContributions } } }"
        )
    return "query { " + " ".join(fields) + " }"


def fetch_contributions(
    token: str, candidates: list[dict], start: str, end: str
) -> list[dict]:
    ranked: list[dict] = []
    total_batches = (len(candidates) + BATCH_SIZE - 1) // BATCH_SIZE

    for offset in range(0, len(candidates), BATCH_SIZE):
        batch = candidates[offset:offset + BATCH_SIZE]
        query = contribution_query(batch, start, end)
        result = None
        for attempt in range(4):
            result = request_json(GRAPHQL_URL, token, {"query": query})
            errors = result.get("errors", [])
            if not errors:
                break
            message = "; ".join(
                str(error.get("message", "GraphQL error"))
                for error in errors
            )
            if "rate limit" not in message.lower() or attempt == 3:
                raise GitHubApiError(f"GitHub GraphQL query failed: {message}")
            time.sleep(2 ** (attempt + 1))

        data = result.get("data")
        if not isinstance(data, dict):
            raise GitHubApiError("GitHub GraphQL returned no data.")

        for index, candidate in enumerate(batch):
            user = data.get(f"u{index}")
            if user is None:
                # The account may have been deleted or changed between search
                # and lookup. It is safer to omit it than invent a count.
                continue
            collection = user.get("contributionsCollection") or {}
            calendar = collection.get("contributionCalendar") or {}
            count = calendar.get("totalContributions")
            if not isinstance(count, int) or count < 0:
                raise GitHubApiError(
                    f"GitHub returned an invalid contribution count for {candidate['login']}."
                )

            ranked.append({
                "login": user.get("login") or candidate["login"],
                "name": user.get("name") or "",
                "location": user.get("location") or "",
                "avatar_url": user.get("avatarUrl") or candidate["avatar_url"],
                "profile_url": user.get("url") or candidate["profile_url"],
                "contributions": count,
            })

        completed = min(offset + len(batch), len(candidates))
        print(
            f"Fetched contribution totals for {completed}/{len(candidates)} "
            f"candidate accounts (batch {offset // BATCH_SIZE + 1}/{total_batches}).",
            flush=True,
        )

    ranked.sort(key=lambda user: (-user["contributions"], user["login"].casefold()))
    return ranked[:LEADERBOARD_LIMIT]


def main() -> int:
    token = os.environ.get("GITHUB_TOKEN", "").strip()
    if not token:
        print("GITHUB_TOKEN is required. In GitHub Actions, use the built-in GITHUB_TOKEN.", file=sys.stderr)
        return 1

    today = datetime.now(timezone.utc).date()
    period_start = today - timedelta(days=365)
    start = f"{period_start.isoformat()}T00:00:00Z"
    end = f"{today.isoformat()}T23:59:59Z"

    print("Discovering Malawi-located GitHub accounts, sorted by followers...", flush=True)
    candidates, search_total = discover_candidates(token)
    if not candidates:
        raise GitHubApiError(
            "GitHub returned no user accounts for location:Malawi. "
            "The existing leaderboard snapshot was not changed."
        )

    print(
        f"Collecting public contribution totals for {len(candidates)} candidates "
        f"over {period_start.isoformat()} to {today.isoformat()}...",
        flush=True,
    )
    ranked_users = fetch_contributions(token, candidates, start, end)
    if not ranked_users:
        raise GitHubApiError("No contribution records were returned; snapshot was not changed.")

    snapshot = {
        "generated_at": datetime.now(timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z"),
        "period_start": period_start.isoformat(),
        "period_end": today.isoformat(),
        "candidate_limit": CANDIDATE_LIMIT,
        "candidate_count": len(candidates),
        "github_search_total": search_total,
        "ranked_count": len(ranked_users),
        "methodology": "Discover up to 1,000 GitHub user accounts returned by location:Malawi, sorted by followers; rank the discovered users by public contributions in the rolling 365-day period. The list is a discovery sample, not a complete census of Malawi developers.",
        "contribution_visibility": "Public contributions only. The collector does not request the read:user scope, which can expose private contribution counts.",
        "ranked_users": ranked_users,
    }

    OUTPUT_FILE.parent.mkdir(parents=True, exist_ok=True)
    temporary_file = OUTPUT_FILE.with_suffix(".json.tmp")
    temporary_file.write_text(
        json.dumps(snapshot, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    temporary_file.replace(OUTPUT_FILE)

    print(
        f"Saved {len(ranked_users)} ranked accounts to {OUTPUT_FILE.relative_to(REPOSITORY_ROOT)}.",
        flush=True,
    )
    if ranked_users:
        print(
            f"Current snapshot leader: @{ranked_users[0]['login']} "
            f"with {ranked_users[0]['contributions']} public contributions.",
            flush=True,
        )
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except GitHubApiError as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
