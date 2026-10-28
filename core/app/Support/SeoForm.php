<?php

namespace App\Support;

use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The one SEO form (Pages/Admin/Frontend/Seo.tsx) behind the three SEO pages
 * that were copies of each other: the site-wide SEO, a page's SEO and an
 * item's SEO (blog posts, policy pages).
 */
class SeoForm
{
    /**
     * @param object|null $seo      the saved values (keywords, description, social_title, social_description)
     * @param bool        $required the site-wide SEO needs every field; a page's or item's is optional
     */
    public static function render(string $pageTitle, ?object $seo, string $image, bool $required, string $submit, ?string $back = null)
    {
        return Inertia::render('Admin/Frontend/Seo', [
            'title'    => $pageTitle,
            'required' => $required,
            'image'    => $image,
            'size'     => getFileSize('seo'),
            'form'     => [
                'keywords'           => array_values(array_map('strval', array_filter((array) ($seo->keywords ?? []), 'is_scalar'))),
                'description'        => (string) ($seo->description ?? ''),
                'social_title'       => (string) ($seo->social_title ?? ''),
                'social_description' => (string) ($seo->social_description ?? ''),
            ],
            'urls'     => ['submit' => $submit, 'back' => $back, 'global' => $required ? null : route('admin.seo')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public static function validate(Request $request, bool $required): void
    {
        $need = $required ? 'required' : 'nullable';
        $request->validate([
            'image'              => ['nullable', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'keywords'           => [$need, 'array', 'max:50'],
            'keywords.*'         => ['required', 'string', 'max:100'],
            'description'        => [$need, 'string', 'max:1000'],
            'social_title'       => [$need, 'string', 'max:255'],
            'social_description' => [$need, 'string', 'max:1000'],
        ]);
    }

    /** The values to save, with the image file name already decided. */
    public static function values(Request $request, ?string $image): array
    {
        $keywords = collect((array) $request->input('keywords', []))->map(fn ($k) => trim((string) $k))->filter()->unique()->values()->all();

        return [
            'image'              => $image,
            'description'        => (string) $request->input('description', ''),
            'social_title'       => (string) $request->input('social_title', ''),
            'social_description' => (string) $request->input('social_description', ''),
            'keywords'           => $keywords,
        ];
    }
}
