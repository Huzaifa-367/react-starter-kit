<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Log an audit event.
     */
    public static function log(
        string $event,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null
    ): void {
        $userId = auth()->id();

        // request() is safe to use in CLI, it returns null for ip and user agent
        $ipAddress = request()?->ip();
        $userAgent = request()?->userAgent();

        $subjectType = $subject ? get_class($subject) : null;
        $subjectId = $subject ? $subject->getKey() : null;

        ActivityLog::create([
            'user_id' => $userId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'event' => $event,
            'description' => $description,
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
        ]);
    }
}
