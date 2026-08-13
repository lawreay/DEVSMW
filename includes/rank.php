<?php

declare(strict_types=1);

function update_profile_rank(int $profileId, ?int $newRank): void
{
    $pdo = db();
    
    $stmt = $pdo->prepare('SELECT rank_private FROM profiles WHERE id = ?');
    $stmt->execute([$profileId]);
    $currentRank = $stmt->fetchColumn();
    
    if ($currentRank === false || $currentRank === null) {
        throw new RuntimeException('Profile not found.');
    }
    
    if ($newRank === null || $currentRank === $newRank) {
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        if ($newRank < $currentRank) {
            $stmt = $pdo->prepare(
                'UPDATE profiles 
                 SET rank_private = rank_private + 1 
                 WHERE rank_private >= ? AND rank_private < ? AND id != ?'
            );
            $stmt->execute([$newRank, $currentRank, $profileId]);
        } else {
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
    } catch (Exception $e) {
        $pdo->rollBack();
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
