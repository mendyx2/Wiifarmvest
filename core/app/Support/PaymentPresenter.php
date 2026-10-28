<?php

namespace App\Support;

use App\Constants\Status;

/**
 * Turns deposits / withdrawals into the small arrays the React history pages
 * show. Keeps the wording and rules of the old Blade views and model
 * badges, but as data instead of HTML.
 */
class PaymentPresenter
{
    /**
     * Status for the deposit history (mirrors Deposit::statusBadge).
     *
     * @return array{label: string, tone: string, when: ?string}
     */
    public static function depositStatus($deposit): array
    {
        $manual = self::isManual($deposit);

        return match ((int) $deposit->status) {
            Status::PAYMENT_PENDING => ['label' => 'Pending', 'tone' => 'warning', 'when' => null],
            Status::PAYMENT_SUCCESS => $manual
                ? ['label' => 'Approved', 'tone' => 'success', 'when' => diffForHumans($deposit->updated_at)]
                : ['label' => 'Succeed', 'tone' => 'success', 'when' => null],
            Status::PAYMENT_REJECT  => ['label' => 'Rejected', 'tone' => 'danger', 'when' => diffForHumans($deposit->updated_at)],
            default                 => ['label' => 'Initiated', 'tone' => 'dark', 'when' => null],
        };
    }

    /**
     * Status for the withdrawal log (mirrors the withdraw/log.blade.php badge map).
     *
     * @return array{label: string, tone: string}
     */
    public static function withdrawStatus($withdraw): array
    {
        return match ((int) $withdraw->status) {
            Status::PAYMENT_SUCCESS => ['label' => 'Approved', 'tone' => 'green'],
            Status::PAYMENT_PENDING => ['label' => 'Pending', 'tone' => 'amber'],
            Status::PAYMENT_REJECT  => ['label' => 'Rejected', 'tone' => 'rose'],
            default                 => ['label' => 'Unknown', 'tone' => 'grey'],
        };
    }

    /** Manual gateways (method codes 1000–5000) are reviewed by an admin. */
    public static function isManual($deposit): bool
    {
        return $deposit->method_code >= 1000 && $deposit->method_code <= 5000;
    }

    /**
     * The form a user filled in for a manual payment. Uploaded files become a
     * download link (encrypted path, as before) instead of a raw file name.
     *
     * @return list<array{name: string, value: ?string, isFile: bool}>
     */
    public static function details($information): array
    {
        $rows = [];
        foreach ($information ?? [] as $info) {
            $isFile = @$info->type == 'file';
            $value  = @$info->value;
            if ($isFile && $value) {
                $value = route('user.download.attachment', encrypt(getFilePath('verify') . '/' . $value));
            }
            $rows[] = [
                'name'   => (string) @$info->name,
                'value'  => is_scalar($value) || $value === null ? $value : json_encode($value),
                'isFile' => $isFile,
            ];
        }

        return $rows;
    }
}
