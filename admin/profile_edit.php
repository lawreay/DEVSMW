<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM profiles WHERE id = ?');
$stmt->execute([$id]);
$profile = $stmt->fetch();
if (!$profile) {
    http_response_code(404);
    exit('Profile not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $before = $profile;
    $allowedConsent = ['public_data', 'claimed', 'opted_out', 'needs_review'];
    $allowedVisibility = ['published', 'draft', 'hidden'];
    $consentStatus = in_array($_POST['consent_status'] ?? '', $allowedConsent, true) ? $_POST['consent_status'] : 'needs_review';
    $visibility = in_array($_POST['visibility'] ?? '', $allowedVisibility, true) ? $_POST['visibility'] : 'draft';

    $update = db()->prepare(
        'UPDATE profiles SET
            name = ?, title = ?, location = ?, work = ?, phone = ?, email = ?, website = ?,
            linkedin_url = ?, bio = ?, strengths = ?, markdown = ?, consent_status = ?,
            visibility = ?, updated_at = NOW()
         WHERE id = ?'
    );
    $update->execute([
        nullable_field($_POST['name'] ?? ''),
        nullable_field($_POST['title'] ?? ''),
        nullable_field($_POST['location'] ?? ''),
        nullable_field($_POST['work'] ?? ''),
        nullable_field($_POST['phone'] ?? ''),
        nullable_field($_POST['email'] ?? ''),
        nullable_field($_POST['website'] ?? ''),
        nullable_field($_POST['linkedin_url'] ?? ''),
        nullable_field($_POST['bio'] ?? ''),
        nullable_field($_POST['strengths'] ?? ''),
        nullable_field($_POST['markdown'] ?? ''),
        $consentStatus,
        $visibility,
        $id,
    ]);
    $stmt->execute([$id]);
    $after = $stmt->fetch() ?: [];
    audit_log('update_profile', 'profile', $id, $before, $after);
    flash('Profile saved.');
    redirect('profile_edit.php?id=' . $id);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit <?= e($profile['github_username']) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(site_url('admin/dashboard.php')) ?>">DEVSMW Admin</a>
    <nav><a href="<?= e(site_url('profile.php?u=' . urlencode($profile['github_username']))) ?>">View public</a></nav>
</header>
<main class="page">
    <section class="admin-head">
        <div>
            <h1>Edit @<?= e($profile['github_username']) ?></h1>
            <p>Keep public data accurate, minimal, and sourced.</p>
        </div>
    </section>

    <form method="post" class="edit-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $profile['id'] ?>">
        <label>Name <input name="name" value="<?= e($profile['name']) ?>"></label>
        <label>Title <input name="title" value="<?= e($profile['title']) ?>"></label>
        <label>Location <input name="location" value="<?= e($profile['location']) ?>"></label>
        <label>Work <input name="work" value="<?= e($profile['work']) ?>"></label>
        <label>Phone <input name="phone" value="<?= e($profile['phone']) ?>"></label>
        <label>Email <input name="email" type="email" value="<?= e($profile['email']) ?>"></label>
        <label>Website <input name="website" value="<?= e($profile['website']) ?>"></label>
        <label>LinkedIn URL <input name="linkedin_url" value="<?= e($profile['linkedin_url']) ?>"></label>
        <label>Bio <textarea name="bio" rows="5"><?= e($profile['bio']) ?></textarea></label>
        <label>Strengths <textarea name="strengths" rows="5"><?= e($profile['strengths']) ?></textarea></label>
        <label>Full Markdown Profile <textarea name="markdown" rows="18"><?= e($profile['markdown']) ?></textarea></label>
        <label>Consent Status
            <select name="consent_status">
                <?php foreach (['public_data', 'claimed', 'opted_out', 'needs_review'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= $profile['consent_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Visibility
            <select name="visibility">
                <?php foreach (['published', 'draft', 'hidden'] as $visibility): ?>
                    <option value="<?= e($visibility) ?>" <?= $profile['visibility'] === $visibility ? 'selected' : '' ?>><?= e($visibility) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Save profile</button>
    </form>
</main>
</body>
</html>
