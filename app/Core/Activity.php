<?php
declare(strict_types=1);

namespace App\Core;

final class Activity
{
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null, ?int $userId = null): void
    {
        try {
            Database::insert('wb_activity_log', [
                'user_id' => $userId ?? Auth::id(),
                'action' => substr($action, 0, 60),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details !== null ? mb_substr($details, 0, 2000) : null,
                'ip' => (new Request())->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Logger::error('Activity log failed: ' . $e->getMessage());
        }
    }
}
