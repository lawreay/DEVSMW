import sys
import unittest
from datetime import datetime, timedelta, timezone
from pathlib import Path
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
import update_malawi_leaderboard as collector


class LeaderboardCollectorTests(unittest.TestCase):
    def test_contribution_period_is_exactly_365_days(self):
        end_at = datetime(2026, 11, 26, 11, 12, 13, tzinfo=timezone.utc)
        start_at, actual_end = collector.contribution_period(end_at)

        self.assertEqual(actual_end, end_at)
        self.assertEqual(actual_end - start_at, timedelta(days=365))
        self.assertEqual(start_at.isoformat(), "2025-11-26T11:12:13+00:00")

    def test_discovery_keeps_personal_accounts_only(self):
        response = {
            "total_count": 2,
            "items": [
                {"login": "dev-one", "type": "User", "avatar_url": "https://avatars.githubusercontent.com/u/1", "html_url": "https://github.com/dev-one"},
                {"login": "malawi-org", "type": "Organization", "avatar_url": "", "html_url": "https://github.com/malawi-org"},
            ],
        }
        with patch.object(collector, "request_json", return_value=response) as request:
            users, total = collector.discover_candidates("token")

        self.assertEqual(total, 2)
        self.assertEqual([user["login"] for user in users], ["dev-one"])
        self.assertEqual(request.call_count, 1)

    def test_discovery_excludes_opted_out_accounts_case_insensitively(self):
        response = {
            "total_count": 2,
            "items": [
                {"login": "keep-me", "type": "User", "avatar_url": "", "html_url": "https://github.com/keep-me"},
                {"login": "DoNotList", "type": "User", "avatar_url": "", "html_url": "https://github.com/DoNotList"},
            ],
        }
        with patch.object(collector, "request_json", return_value=response):
            users, total = collector.discover_candidates("token", {"donotlist"})

        self.assertEqual(total, 2)
        self.assertEqual([user["login"] for user in users], ["keep-me"])

    def test_contribution_results_are_sorted_highest_first(self):
        candidates = [
            {"login": "developer-a", "avatar_url": "", "profile_url": "https://github.com/developer-a"},
            {"login": "developer-b", "avatar_url": "", "profile_url": "https://github.com/developer-b"},
        ]
        response = {
            "data": {
                "u0": {
                    "login": "developer-a", "name": "Developer A", "location": "Malawi",
                    "avatarUrl": "", "url": "https://github.com/developer-a",
                    "contributionsCollection": {"contributionCalendar": {"totalContributions": 12}},
                },
                "u1": {
                    "login": "developer-b", "name": "Developer B", "location": "Lilongwe, Malawi",
                    "avatarUrl": "", "url": "https://github.com/developer-b",
                    "contributionsCollection": {"contributionCalendar": {"totalContributions": 37}},
                },
            }
        }
        with patch.object(collector, "request_json", return_value=response):
            ranked = collector.fetch_contributions("token", candidates, "2025-01-01T00:00:00Z", "2026-01-01T23:59:59Z")

        self.assertEqual([user["login"] for user in ranked], ["developer-b", "developer-a"])
        self.assertEqual([user["contributions"] for user in ranked], [37, 12])

    def test_invalid_login_is_rejected_before_query(self):
        with self.assertRaises(collector.GitHubApiError):
            collector.contribution_query(
                [{"login": 'bad"login'}],
                "2025-01-01T00:00:00Z",
                "2026-01-01T23:59:59Z",
            )

    def test_incomplete_search_is_rejected(self):
        with patch.object(
            collector,
            "request_json",
            return_value={"total_count": 1, "incomplete_results": True, "items": []},
        ):
            with self.assertRaises(collector.GitHubApiError):
                collector.discover_candidates("token")

    def test_graphql_rate_limit_is_retried(self):
        candidate = [{"login": "developer-a", "avatar_url": "", "profile_url": "https://github.com/developer-a"}]
        rate_limited = {"errors": [{"message": "API rate limit exceeded"}]}
        successful = {
            "data": {
                "u0": {
                    "login": "developer-a", "name": "Developer A", "location": "Malawi",
                    "avatarUrl": "", "url": "https://github.com/developer-a",
                    "contributionsCollection": {"contributionCalendar": {"totalContributions": 8}},
                }
            }
        }
        with patch.object(collector, "request_json", side_effect=[rate_limited, successful]) as request:
            with patch.object(collector.time, "sleep"):
                ranked = collector.fetch_contributions("token", candidate, "2025-01-01T00:00:00Z", "2026-01-01T23:59:59Z")

        self.assertEqual(request.call_count, 2)
        self.assertEqual(ranked[0]["contributions"], 8)

    def test_empty_search_does_not_create_a_fake_ranking(self):
        with patch.object(collector, "request_json", return_value={"total_count": 0, "items": []}):
            users, total = collector.discover_candidates("token")

        self.assertEqual(users, [])
        self.assertEqual(total, 0)


if __name__ == "__main__":
    unittest.main()
