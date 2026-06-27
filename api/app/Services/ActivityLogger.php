<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    private const BLOCKLIST = [
        'password', 'ip', 'ip_address', 'email', 'name',
        'token', 'secret', 'cpf', 'card', 'cvv', 'remember_token',
    ];

    public function log(string $event, ?int $userId, array $metadata = []): void
    {
        try {
            ActivityLog::create([
                'event_type' => $event,
                'user_id' => $userId,
                'metadata' => $this->sanitize($metadata),
            ]);
        } catch (\Throwable $e) {
            Log::error("ActivityLogger failed for event [{$event}]: {$e->getMessage()}");
        }
    }

    private function sanitize(array $metadata): array
    {
        $result = [];
        foreach ($metadata as $key => $value) {
            if (in_array($key, self::BLOCKLIST, true)) {
                continue;
            }
            $result[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }
        return $result;
    }
}
