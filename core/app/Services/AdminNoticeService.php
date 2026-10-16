<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\NotificationTemplate;
use App\Models\User;

class AdminNoticeService
{
    /**
     * @return array{0: bool, 1: string} [success, message]
     */
    public function send(User $user, string $via, ?string $subject, string $message, ?string $imageUrl, int $adminId): array
    {
        $enabled = NotificationTemplate::where('act', 'DEFAULT')
            ->where($via . '_status', Status::ENABLE)
            ->exists();

        if (!$enabled) {
            return [false, 'Default notification template is not enabled for ' . $via . '.'];
        }

        notify($user, 'DEFAULT', [
            'subject' => $subject,
            'message' => $message,
        ], [$via], pushImage: $imageUrl, adminId: $adminId);

        return [true, 'Notice sent to @' . $user->username . '.'];
    }
}
