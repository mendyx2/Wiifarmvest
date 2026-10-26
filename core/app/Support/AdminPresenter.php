<?php

namespace App\Support;

use App\Models\User;

/**
 * Small pieces the admin React pages show in many tables, already formatted
 * (Components/Admin/*.tsx read them as they are).
 */
class AdminPresenter
{
    /**
     * The "User" column: full name and @username, linking to the member's
     * admin page. Null when the member was deleted.
     *
     * @return array{fullname: string, username: string, url: string}|null
     */
    public static function user(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'fullname' => (string) $user->fullname,
            'username' => (string) $user->username,
            'url'      => route('admin.users.detail', $user->id),
        ];
    }

    /**
     * A date as the Blade tables showed it: the date and time, and "2 days ago" under it.
     *
     * @return array{date: string, ago: string}|null
     */
    public static function when($date): ?array
    {
        if (!$date) {
            return null;
        }

        return ['date' => showDateTime($date), 'ago' => diffForHumans($date)];
    }

    /** @return array{label: string, class: string} */
    public static function badge(string $label, string $class): array
    {
        return ['label' => __($label), 'class' => $class];
    }

    /**
     * The "Date" filter value, as ISO dates for the page's date inputs. Reads
     * every format Searchable::dateFilter() accepts, so old links such as the
     * dashboard's "2026/10/19 - 2026/10/26" still fill the inputs.
     *
     * @return array{from: string, to: string}
     */
    public static function dateRange(?string $value): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return ['from' => '', 'to' => ''];
        }

        $parts = match (true) {
            str_contains($raw, ' - ')                        => explode(' - ', $raw, 2),
            (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) => [$raw],
            default                                          => explode('-', $raw),
        };

        try {
            $from = \Carbon\Carbon::parse(trim($parts[0]))->format('Y-m-d');
            $to   = isset($parts[1]) && trim($parts[1]) !== '' ? \Carbon\Carbon::parse(trim($parts[1]))->format('Y-m-d') : $from;
        } catch (\Throwable) {
            return ['from' => '', 'to' => ''];
        }

        return ['from' => $from, 'to' => $to];
    }
}
