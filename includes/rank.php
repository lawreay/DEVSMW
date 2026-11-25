<?php

declare(strict_types=1);

function update_profile_rank(int $profileId, ?int $newRank): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT rank_private FROM profiles WHERE id = ? FOR UPDATE');
        $stmt->execute([$profileId]);
        $storedRank = $stmt->fetchColumn();

        if ($storedRank === false) {
            throw new RuntimeException('Profile not found.');
        }

        $currentRank = $storedRank === null ? null : (int) $storedRank;

        if ($currentRank === $newRank) {
            $pdo->commit();
            return;
        }

        if ($newRank === null) {
            // Removing a rank closes the gap left by this profile.
            if ($currentRank !== null) {
                $stmt = $pdo->prepare(
                    'UPDATE profiles
                     SET rank_private = rank_private - 1
                     WHERE rank_private > ? AND id != ?'
                );
                $stmt->execute([$currentRank, $profileId]);
            }
        } elseif ($currentRank === null) {
            // Assigning a rank to an unranked profile makes room at that position.
            $stmt = $pdo->prepare(
                'UPDATE profiles
                 SET rank_private = rank_private + 1
                 WHERE rank_private >= ? AND id != ?'
            );
            $stmt->execute([$newRank, $profileId]);
        } elseif ($newRank < $currentRank) {
            // Moving up shifts the profiles between the old and new positions down.
            $stmt = $pdo->prepare(
                'UPDATE profiles
                 SET rank_private = rank_private + 1
                 WHERE rank_private >= ? AND rank_private < ? AND id != ?'
            );
            $stmt->execute([$newRank, $currentRank, $profileId]);
        } else {
            // Moving down shifts the profiles between the old and new positions up.
            $stmt = $pdo->prepare(
                'UPDATE profiles
                 SET rank_private = rank_private - 1
                 WHERE rank_private > ? AND rank_private <= ? AND id != ?'
            );
            $stmt->execute([$currentRank, $newRank, $profileId]);
        }

        $stmt = $pdo->prepare('UPDATE profiles SET rank_private = ? WHERE id = ?');
        $stmt->execute([$newRank, $profileId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function get_rank_suggestions(): array
{
    $stmt = db()->prepare(
        'SELECT
            rank_private,
            github_username,
            name,
            COUNT(*) as count
         FROM profiles
         WHERE rank_private IS NOT NULL
         GROUP BY rank_private
         ORDER BY rank_private ASC'
    );
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
