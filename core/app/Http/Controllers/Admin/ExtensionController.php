<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Extension;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Extensions (Pages/Admin/Extensions.tsx): third-party scripts such as
 * reCAPTCHA, Tawk chat or Google Analytics, with their keys. Secret keys are
 * never sent to the browser; a blank field keeps the saved one.
 */
class ExtensionController extends Controller
{
    private static function isSecret(string $key): bool
    {
        return !str_contains(strtolower($key), 'public') && (bool) preg_match('/secret|private|password|token|api_?key/i', $key);
    }

    public function index()
    {
        $pageTitle = 'Extensions';

        return Inertia::render('Admin/Extensions', [
            'extensions' => Extension::orderBy('name')->get()->map(fn (Extension $e) => [
                'id'          => $e->id,
                'name'        => (string) __($e->name),
                'image'       => getImage(getFilePath('extensions') . '/' . $e->image, getFileSize('extensions')),
                'active'      => (int) $e->status === Status::ENABLE,
                // Seeded help text (HTML) and an optional screenshot
                'description' => (string) __($e->description),
                'support'     => $e->support && $e->support !== 'na' ? asset(getFilePath('extensions') . '/' . $e->support) : null,
                'fields'      => collect((array) ($e->shortcode ?? []))->map(fn ($f, $key) => [
                    'key'    => (string) $key,
                    'title'  => (string) __($f->title ?? keyToTitle($key)),
                    'secret' => self::isSecret((string) $key),
                    'value'  => self::isSecret((string) $key) ? '' : (string) ($f->value ?? ''),
                    'stored' => filled($f->value ?? null),
                ])->values(),
            ]),
            'urls' => ['update' => route('admin.extensions.update', ':id'), 'status' => route('admin.extensions.status', ':id')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function update(Request $request, $id)
    {
        $extension = Extension::findOrFail($id);
        $shortcode = json_decode(json_encode($extension->shortcode), true) ?: [];

        // A blank secret keeps the saved value
        foreach ($shortcode as $key => $item) {
            if (self::isSecret($key) && blank($request->input($key)) && filled($item['value'] ?? null)) {
                $request->merge([$key => $item['value']]);
            }
        }
        $request->validate(collect($shortcode)->map(fn () => 'required|string|max:2000')->all());

        foreach ($shortcode as $key => $value) {
            $shortcode[$key]['value'] = $request->input($key);
        }
        $extension->shortcode = $shortcode;
        $extension->save();

        $notify[] = ['success', $extension->name . ' updated successfully'];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return Extension::changeStatus($id);
    }
}
