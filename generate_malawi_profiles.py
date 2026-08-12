import json
import os
import re
import time
import urllib.request
from html import unescape
from pathlib import Path
from urllib.parse import quote

INPUT_FILE = Path('malawi_rank.json')
OUTPUT_DIR = Path('malawi_profiles')
USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
SOURCE_LIST = 'user_private'


def safe_filename(name: str) -> str:
    return re.sub(r'[^A-Za-z0-9_.-]+', '_', name)


def extract(pattern: str, text: str, group: int = 1, flags=0) -> str | None:
    match = re.search(pattern, text, flags)
    if match:
        return clean_text(match.group(group))
    return None


def clean_text(value: str) -> str:
    value = re.sub(r'<[^>]+>', ' ', value)
    value = unescape(value)
    return re.sub(r'\s+', ' ', value).strip()


def clean_attr(value: str) -> str:
    return unescape(value.strip())


def extract_pinned_repos(username: str, text: str) -> list[dict[str, str]]:
    pattern = fr'<a[^>]*href="/{re.escape(username)}/([^"/]+)"[^>]*>\s*<span[^>]*class="repo"[^>]*>([^<]+)</span>'
    matches = re.findall(pattern, text, re.S | re.I)
    repos = []
    for repo_name, repo_label in matches:
        name = clean_text(repo_label) or clean_text(repo_name)
        if name and not any(repo['name'] == name for repo in repos):
            repos.append({'name': name, 'url': f'https://github.com/{username}/{name}'})
    return repos


def fetch_url(url: str) -> str:
    req = urllib.request.Request(url, headers={'User-Agent': USER_AGENT})
    with urllib.request.urlopen(req, timeout=30) as resp:
        return resp.read().decode('utf-8', errors='ignore')


def fetch_profile(username: str) -> tuple[str, str]:
    url = f'https://github.com/{quote(username)}'
    return url, fetch_url(url)


def normalize_link(link: str) -> str:
    link = clean_attr(link)
    if link.startswith('//'):
        return 'https:' + link
    return link


def extract_repo_rows(username: str, html: str) -> list[dict[str, str]]:
    items = re.findall(r'<li class="[^"]*public[^"]*"[^>]*itemprop="owns".*?</li>', html, re.S | re.I)
    repos = []
    for item in items:
        link = re.search(
            fr'<a href="/{re.escape(username)}/([^"/]+)"[^>]*itemprop="name codeRepository"[^>]*>(.*?)</a>',
            item,
            re.S | re.I,
        )
        if not link:
            continue
        name = clean_text(link.group(2)) or clean_text(link.group(1))
        description = extract(r'<p[^>]*itemprop="description"[^>]*>(.*?)</p>', item, flags=re.S | re.I) or ''
        language = extract(r'<span itemprop="programmingLanguage">(.*?)</span>', item, flags=re.S | re.I) or ''
        star_match = re.search(fr'href="/{re.escape(username)}/{re.escape(link.group(1))}/stargazers"[^>]*>.*?</svg>\s*([^<]+)</a>', item, re.S | re.I)
        stars = clean_text(star_match.group(1)) if star_match else '0'
        repos.append({
            'name': name,
            'url': f'https://github.com/{username}/{clean_attr(link.group(1))}',
            'description': description,
            'language': language,
            'stars': stars,
        })
    return repos


def infer_strengths(bio: str, repos: list[dict[str, str]]) -> list[str]:
    languages = []
    for repo in repos:
        language = repo.get('language', '')
        if language and language not in languages:
            languages.append(language)

    strengths = []
    if bio:
        strengths.append(bio)
    if languages:
        strengths.append('Public repository languages: ' + ', '.join(languages[:6]) + '.')

    project_text = ' '.join((repo.get('description') or repo['name']) for repo in repos[:5]).lower()
    domains = []
    keywords = {
        'health / medical systems': ['health', 'medical', 'patient', 'openmrs', 'clinic', 'hospital', 'emr'],
        'web application development': ['web', 'dashboard', 'portal', 'react', 'next', 'laravel', 'django'],
        'mobile / Android development': ['android', 'kotlin', 'mobile', 'flutter'],
        'data / AI tooling': ['data', 'machine', 'ai', 'ml', 'notebook', 'analytics'],
        'backend and APIs': ['api', 'server', 'backend', 'go', 'node', 'spring'],
    }
    for label, words in keywords.items():
        if any(word in project_text for word in words):
            domains.append(label)
    if domains:
        strengths.append('Likely focus areas from public repo names/descriptions: ' + ', '.join(domains) + '.')

    return strengths or ['No public bio or repository language signals available.']


def extract_phone(html: str, bio: str) -> str:
    tel = extract(r'href="tel:([^"]+)"', html)
    if tel:
        return tel
    text = clean_text(bio)
    match = re.search(r'(?:phone|tel|whatsapp|contact)[:\s]+(\+?\d[\d\s().-]{6,}\d)', text, re.I)
    return match.group(1).strip() if match else ''


def build_markdown(username: str, rank: int, contributions: int | None, html: str, repos_html: str, profile_url: str) -> str:
    full_name = extract(r'<span[^>]*class="[^"<>]*p-name[^"<>]*"[^>]*>(.*?)</span>', html, flags=re.S) or ''
    if full_name and full_name.lower() == username.lower():
        full_name = ''
    company = extract(r'<span[^>]*class="[^"<>]*p-org[^"<>]*"[^>]*>(.*?)</span>', html, flags=re.S) or ''
    location = extract(r'<li[^>]*itemprop="homeLocation"[^>]*>.*?<span[^>]*class="[^"<>]*p-label[^"<>]*"[^>]*>(.*?)</span>', html, flags=re.S) 
    if not location:
        location = extract(r'<span[^>]*class="[^"<>]*p-label[^"<>]*"[^>]*>(.*?)</span>', html, flags=re.S) or ''
    email = extract(r'href="mailto:([^"]+)"', html) or ''
    blog = extract(r'<a[^>]*class="[^"]*u-url[^"]*"[^>]*href="([^"]+)"', html) or ''
    website = normalize_link(blog) if blog else ''
    bio = extract(r'<div[^>]*class="[^"<>]*p-note user-profile-bio[^"<>]*"[^>]*>(.*?)</div>', html, flags=re.S) or ''
    phone = extract_phone(html, bio)
    pinned_repos = extract_pinned_repos(username, html)
    top_repos = extract_repo_rows(username, repos_html)
    repos = top_repos or pinned_repos

    profile_title = full_name or username
    md = [f'# {profile_title} ({username})', '']
    md.append(f'- **Rank:** #{rank} on committers.top Malawi private-contributions list')
    if contributions is not None:
        md.append(f'- **Contributions:** {contributions}')
    md.append(f'- **Location:** {location or "Not publicly available"}')
    md.append(f'- **Work:** {company or "Not publicly available"}')
    md.append(f'- **Phone:** {phone or "Not publicly available"}')
    md.append(f'- **Email:** {email or "Not publicly available"}')
    if website:
        md.append(f'- **Website:** {website}')
    md.append(f'- **GitHub:** {profile_url}')
    md.append(f'- **Source:** https://committers.top/malawi_private (data as of {DATA_ASOF})')
    md.append('')

    if repos:
        md.append('## Top Projects')
        for repo in repos[:8]:
            details = []
            if repo.get('language'):
                details.append(repo['language'])
            if repo.get('stars') and repo['stars'] != '0':
                details.append(f"{repo['stars']} stars")
            suffix = f" ({', '.join(details)})" if details else ''
            description = f" - {repo['description']}" if repo.get('description') else ''
            md.append(f"- [{repo['name']}]({repo['url']}){suffix}{description}")
        md.append('')
    else:
        md.append('## Top Projects')
        md.append('- No public repository data available')
        md.append('')

    md.append('## What They Are Good At')
    for strength in infer_strengths(bio, repos):
        md.append(f'- {strength}')
    md.append('')

    return '\n'.join(md)


def main(limit: int | None = None) -> None:
    with INPUT_FILE.open('r', encoding='utf-8-sig') as f:
        data = json.load(f)
    users = data.get(SOURCE_LIST, [])
    contributions = {name: index for index, name in enumerate(users, start=1)}
    raw_private = data.get('user_private', [])
    OUTPUT_DIR.mkdir(exist_ok=True)
    if limit is not None:
        users = users[:limit]
    for count, username in enumerate(users, start=1):
        try:
            profile_url, html = fetch_profile(username)
            repos_url = f'https://github.com/{quote(username)}?tab=repositories&sort=stargazers'
            repos_html = fetch_url(repos_url)
            rank = raw_private.index(username) + 1 if username in raw_private else contributions[username]
            body = build_markdown(username, rank, None, html, repos_html, profile_url)
        except Exception as exc:
            body = f"# {username}\n\n- **GitHub:** https://github.com/{username}\n- **Error:** {exc}\n"
        filename = OUTPUT_DIR / f'{safe_filename(username)}.md'
        with filename.open('w', encoding='utf-8') as f:
            f.write(body)
        print(f'Written {count}/{len(users)} {filename.name}')
        time.sleep(0.25)


if __name__ == '__main__':
    with INPUT_FILE.open('r', encoding='utf-8-sig') as f:
        DATA_ASOF = json.load(f).get('data_asof', 'unknown')
    main()
