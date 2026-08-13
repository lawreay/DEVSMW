<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/visit.php';
require __DIR__ . '/includes/search.php';
set_page_cache_control('public');  // 5 min cache for search engines to see fresh metadata

$username = trim($_GET['u'] ?? '');
$stmt = db()->prepare('SELECT * FROM profiles WHERE github_username = ? AND visibility = "published"');
$stmt->execute([$username]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    exit('Profile not found.');
}

record_profile_visit((int) $profile['id']);
$visitCount = get_profile_visit_count((int) $profile['id']);
$lastVisit = get_profile_last_visit((int) $profile['id']);

$projects = db()->prepare('SELECT * FROM projects WHERE profile_id = ? ORDER BY stars DESC, name LIMIT 20');
$projects->execute([$profile['id']]);
$projects = $projects->fetchAll();

$externalSearchResults = [];
if (search_is_external()) {
    $searchQuery = $profile['name'] ?: $profile['github_username'];
    $externalSearchResults = search_results($searchQuery);
}
$searchEngine = config('search.engine');
$displayName = $profile['name'] ?: $profile['github_username'];
$profileSummary = trim((string) ($profile['bio'] ?: $profile['strengths'] ?: 'No public profile summary is available yet.'));
$strengthItems = array_values(array_filter(array_map(static function (string $item): string {
    return trim($item);
}, preg_split('/[,;\n]+/', (string) ($profile['strengths'] ?? '')) ?: [])));
$projectLanguages = [];
foreach ($projects as $project) {
    $language = trim((string) ($project['language'] ?? ''));
    if ($language !== '') {
        $projectLanguages[$language] = true;
    }
}
$projectLanguages = array_slice(array_keys($projectLanguages), 0, 8);
$initialsSource = preg_split('/\s+/', trim($displayName));
$initials = '';
foreach ($initialsSource as $part) {
    if ($part !== '') {
        $initials .= mb_substr($part, 0, 1);
    }
    if (mb_strlen($initials) >= 2) {
        break;
    }
}
$initials = mb_strtoupper($initials ?: mb_substr($profile['github_username'], 0, 2));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($displayName) ?> - Developer Profile | <?= e(config('app_name')) ?></title>
    <?= meta_description($profileSummary) . "\n    " ?>
    <?= og_tags([
        'title' => $displayName . ' - Developer | DEVSMW',
        'description' => $profileSummary,
        'type' => 'profile',
        'url' => site_url('profile.php?u=' . urlencode($profile['github_username'])),
        'image' => 'https://github.com/' . htmlspecialchars($profile['github_username']) . '.png',
    ]) . "\n    " ?>
    <?= twitter_card([
        'title' => $displayName . ' - Developer Profile',
        'description' => $profileSummary,
        'image' => 'https://github.com/' . htmlspecialchars($profile['github_username']) . '.png',
    ]) . "\n    " ?>
    <?= canonical_url(site_url('profile.php?u=' . urlencode($profile['github_username']))) . "\n    " ?>
    <meta name="keywords" content="<?= e(implode(', ', array_slice(array_merge($strengthItems, $projectLanguages), 0, 8))) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body class="profile-view">
<?= schema_person($profile) . "\n"?>
<header class="topbar">
    <a class="brand" href="<?= e(site_url('index.php')) ?>">
        <span class="brand-mark">D</span>
        <span>DEVSMW Profiles</span>
    </a>
    <nav>
        <a href="<?= e(site_url('index.php')) ?>">Home</a>
        <a href="<?= e(site_url('profiles.php')) ?>">Profiles</a>
        <a class="active" href="<?= e(site_url('profile.php?u=' . urlencode($profile['github_username']))) ?>">Profile</a>
    </nav>
</header>

<main class="page profile-page">
    <aside class="profile-aside">
        <div class="profile-identity">
            <div class="avatar-mark"><?= e($initials) ?></div>
            <span class="rank"><?= $profile['rank_private'] ? '#' . e((string) $profile['rank_private']) : 'Profile' ?></span>
        </div>
        <h1><?= e($displayName) ?></h1>
        <p class="muted">@<?= e($profile['github_username']) ?></p>

        <div class="profile-kpis">
            <div title="Views tracked on this site">
                <strong><?= (int) $visitCount ?></strong>
                <span>Views</span>
            </div>
            <div title="Featured repositories saved locally">
                <strong id="repoCount"><?= count($projects) ?></strong>
                <span>Repos</span>
            </div>
            <div title="Fetched from GitHub when available">
                <strong id="followerCount">-</strong>
                <span>Followers</span>
            </div>
        </div>

        <h2>Contact</h2>
        <dl>
            <div><dt>Location</dt><dd><?= e($profile['location'] ?: 'Not publicly available') ?></dd></div>
            <div><dt>Work</dt><dd><?= e($profile['work'] ?: 'Not publicly available') ?></dd></div>
            <div><dt>Email</dt><dd><?= $profile['email'] ? '<a href="mailto:' . e($profile['email']) . '">' . e($profile['email']) . '</a>' : 'Not publicly available' ?></dd></div>
            <div><dt>Phone</dt><dd><?= e($profile['phone'] ?: 'Not publicly available') ?></dd></div>
        </dl>
        <div class="profile-meta">
            <?php if ($lastVisit): ?>
                <div class="muted">Last visited <?= e(date('M j, Y H:i', strtotime($lastVisit))) ?></div>
            <?php endif; ?>
        </div>
        <?php if ($profile['github_url'] || $profile['website'] || $profile['linkedin_url']): ?>
            <h2>Links</h2>
        <?php endif; ?>
        <div class="link-stack">
            <?php if ($profile['github_url']): ?><a href="<?= e($profile['github_url']) ?>" target="_blank" rel="noopener">GitHub</a><?php endif; ?>
            <?php if ($profile['website']): ?><a href="<?= e($profile['website']) ?>" target="_blank" rel="noopener">Website</a><?php endif; ?>
            <?php if ($profile['linkedin_url']): ?><a href="<?= e($profile['linkedin_url']) ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
        </div>
        <div class="gh-live">
            
            <span id="ghStatus">Checking GitHub profile</span>
        </div>
    </aside>

    <section class="profile-main">
        <div class="profile-titlebar">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Developer profile</span>
                    <h2><?= e($displayName) ?></h2>
                </div>
            </div>
            <nav class="tabs" aria-label="Profile sections">
                <button class="tab-btn active" type="button" data-tab="overview">Overview</button>
                <button class="tab-btn" type="button" data-tab="projects">Projects <span class="n"><?= count($projects) ?></span></button>
                <?php if (!empty($externalSearchResults)): ?>
                    <button class="tab-btn" type="button" data-tab="external">External <span class="n"><?= count($externalSearchResults) ?></span></button>
                <?php endif; ?>
            </nav>
        </div>

        <div class="tab-panel active" id="panel-overview">
            <section class="profile-section">
                <div class="markdown-card">
                    <h2>Snapshot</h2>
                    <div class="fact-grid">
                        <div><span class="k">Rank</span><span><?= $profile['rank_private'] ? '#' . e((string) $profile['rank_private']) : 'Unranked' ?></span></div>
                        <div><span class="k">Location</span><span><?= e($profile['location'] ?: 'Not public') ?></span></div>
                        <div><span class="k">Work</span><span><?= e($profile['work'] ?: 'Not public') ?></span></div>
                        <div><span class="k">GitHub</span><a href="<?= e($profile['github_url'] ?: 'https://github.com/' . $profile['github_username']) ?>" target="_blank" rel="noopener">@<?= e($profile['github_username']) ?></a></div>
                    </div>

                    <h2>About</h2>
                    <?php if ($profile['markdown']): ?>
                        <div class="markdown"><?= markdown_to_html($profile['markdown']) ?></div>
                    <?php else: ?>
                        <p><?= e($profileSummary) ?></p>
                    <?php endif; ?>

                    <h2>Skills and languages</h2>
                    <?php if (count($strengthItems) > 0): ?>
                        <div class="chip-row">
                            <?php foreach (array_slice($strengthItems, 0, 10) as $strength): ?>
                                <span class="chip"><?= e($strength) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (count($projectLanguages) > 0): ?>
                        <div class="chip-row">
                            <?php foreach ($projectLanguages as $language): ?>
                                <span class="chip subdued"><?= e($language) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h2>Top project notes</h2>
                    <?php if (count($projects) === 0): ?>
                        <p class="muted">No public projects are available for this profile yet.</p>
                    <?php else: ?>
                        <ul class="bullet-list">
                            <?php foreach (array_slice($projects, 0, 8) as $project): ?>
                                <li>
                                    <?php if ($project['url']): ?>
                                        <a href="<?= e($project['url']) ?>" target="_blank" rel="noopener"><?= e($project['name']) ?></a>
                                    <?php else: ?>
                                        <?= e($project['name']) ?>
                                    <?php endif; ?>
                                    <span><?= (int) $project['stars'] ?> stars<?= $project['language'] ? ' - ' . e($project['language']) : '' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="tab-panel" id="panel-projects">
            <section class="profile-section">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Portfolio</span>
                        <h2>Top projects</h2>
                    </div>
                    <span class="count-pill"><?= count($projects) ?> featured</span>
                </div>
                <?php if (count($projects) === 0): ?>
                    <p class="muted">No public projects are available for this profile yet.</p>
                <?php else: ?>
                    <div class="project-list">
                        <?php foreach ($projects as $project): ?>
                            <article class="project-row">
                                <h3>
                                    <?php if ($project['url']): ?>
                                        <a href="<?= e($project['url']) ?>" target="_blank" rel="noopener"><?= e($project['name']) ?></a>
                                    <?php else: ?>
                                        <?= e($project['name']) ?>
                                    <?php endif; ?>
                                </h3>
                                <div class="project-description"><?= markdown_to_html($project['description'] ?: 'No description available.') ?></div>
                                <div class="project-tags">
                                    <span><?= e($project['language'] ?: 'Unknown') ?></span>
                                    <span><?= (int) $project['stars'] ?> stars</span>
                                    <span><?= e($project['source']) ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <?php if (!empty($externalSearchResults)): ?>
            <div class="tab-panel" id="panel-external">
                <section class="profile-section external-results-inline">
                    <div class="section-head">
                        <div>
                            <span class="eyebrow">Related</span>
                            <h2>External information</h2>
                            <p class="muted">Powered by <?= e(ucfirst($searchEngine === 'duckduckgo' ? 'DuckDuckGo' : $searchEngine)) ?> (free external search)</p>
                        </div>
                    </div>
                    <div class="news-list">
                        <?php foreach ($externalSearchResults as $result): ?>
                            <li>
                                <a href="<?= e($result['url']) ?>" target="_blank" rel="noopener noreferrer"><strong><?= e($result['name']) ?></strong></a>
                                <p><?= e($result['snippet']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
<script>
    document.querySelectorAll('.profile-view .tab-btn').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.profile-view .tab-btn').forEach((item) => item.classList.remove('active'));
            document.querySelectorAll('.profile-view .tab-panel').forEach((panel) => panel.classList.remove('active'));
            button.classList.add('active');
            document.getElementById('panel-' + button.dataset.tab)?.classList.add('active');
        });
    });

    (() => {
        const username = <?= json_encode($profile['github_username'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const dot = document.getElementById('ghDot');
        const status = document.getElementById('ghStatus');
        const avatar = document.querySelector('.profile-view .avatar-mark');
        const followers = document.getElementById('followerCount');
        const repoCount = document.getElementById('repoCount');

        fetch('https://api.github.com/users/' + encodeURIComponent(username))
            .then((response) => {
                if (!response.ok) {
                    throw new Error('GitHub API unavailable');
                }
                return response.json();
            })
            .then((data) => {
                dot?.classList.remove('loading');
                if (status) status.textContent = 'Synced with GitHub';
                if (followers && typeof data.followers === 'number') followers.textContent = data.followers;
                if (repoCount && typeof data.public_repos === 'number') repoCount.textContent = data.public_repos;
                if (avatar && data.avatar_url) {
                    avatar.innerHTML = '';
                    const image = document.createElement('img');
                    image.src = data.avatar_url;
                    image.alt = username + ' avatar';
                    avatar.appendChild(image);
                }
            })
            .catch(() => {
                dot?.classList.remove('loading');
                dot?.classList.add('error');
                if (status) status.textContent = 'GitHub sync unavailable';
            });
    })();
</script>
</body>
</html>
