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

    /**
     * Answers to a form built with the admin form builder (KYC, manual
     * deposit gateways, withdraw methods): label, type, the text value and,
     * for a file, its view / download links.
     *
     * @return list<array{name: string, type: string, value: ?string, file: ?array}>
     */
    public static function formFields($data, bool $skipEmpty = false): array
    {
        $file = function (string $name) {
            $hash = encrypt(getFilePath('verify') . '/' . $name);
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            return [
                'view'     => route('admin.view.attachment', $hash),
                'download' => route('admin.download.attachment', $hash),
                'image'    => in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
            ];
        };

        return collect(is_string($data) ? (array) json_decode($data) : (array) ($data ?? []))
            ->map(fn ($f) => (object) $f)
            ->filter(fn ($f) => !$skipEmpty || !empty($f->value))
            ->map(fn ($f) => [
                'name'  => __((string) ($f->name ?? '')),
                'type'  => (string) ($f->type ?? 'text'),
                'value' => ($f->type ?? '') === 'checkbox' ? implode(', ', (array) ($f->value ?? [])) : (($f->type ?? '') === 'file' ? null : __((string) ($f->value ?? ''))),
                'file'  => ($f->type ?? '') === 'file' && !empty($f->value) ? $file((string) $f->value) : null,
            ])->values()->all();
    }

    /**
     * A payment gateway's reply as label / value rows, nested keys indented
     * by depth (admin/deposit/gateway_data.blade.php did this recursively).
     *
     * @return list<array{label: string, value: ?string, depth: int}>
     */
    public static function flatten($data, int $depth = 0): array
    {
        $rows = [];
        foreach ((array) (is_string($data) ? json_decode($data, true) : json_decode(json_encode($data), true)) as $key => $value) {
            if (is_array($value)) {
                $rows[] = ['label' => keyToTitle((string) $key), 'value' => null, 'depth' => $depth];
                array_push($rows, ...self::flatten($value, $depth + 1));
            } else {
                $rows[] = ['label' => keyToTitle((string) $key), 'value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value, 'depth' => $depth];
            }
        }

        return $rows;
    }
}
