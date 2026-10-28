<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Frontend;
use App\Rules\FileTypeValidate;
use App\Support\FormPresenter;
use App\Support\SeoForm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Frontend content (Pages/Admin/Frontend/*): the texts, pictures and items of
 * each section of the site, as the active template's sections.json describes
 * them, and the site-wide and per-item SEO.
 *
 * A section has "content" (one set of values) and/or "element" (a list of
 * items: FAQ entries, social links, policy pages...). sections.json gives
 * each field a type: text, textarea, textarea-nic (rich text), icon, a
 * select with options, images with their size, and a slug made from a field.
 */
class FrontendController extends Controller
{
    public function index()
    {
        $pageTitle = 'Manage Frontend Content';
        $sections  = collect(getPageSections(true))
            ->filter(fn ($s) => !empty($s['builder']) && empty($s['hide_builder']))
            ->map(fn ($s, $k) => ['key' => (string) $k, 'name' => (string) __($s['name']), 'url' => route('admin.frontend.sections', $k)])
            ->values();

        return Inertia::render('Admin/Frontend/Index', [
            'sections' => $sections,
            'urls'     => ['pages' => route('admin.frontend.manage.pages'), 'seo' => route('admin.seo')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function seoEdit()
    {
        $seo = Frontend::where('data_keys', 'seo.data')->first();

        return SeoForm::render('SEO Configuration', $seo?->data_values, getImage(getFilePath('seo') . '/' . @$seo->data_values->image, getFileSize('seo')), true, route('admin.seo.update'));
    }

    public function seoUpdate(Request $request)
    {
        SeoForm::validate($request, true);

        $seo = Frontend::where('data_keys', 'seo.data')->first();
        if (!$seo) {
            $seo            = new Frontend();
            $seo->data_keys = 'seo.data';
        }
        $image = @$seo->data_values->image;
        if ($request->hasFile('image')) {
            try {
                $image = fileUploader($request->image, getFilePath('seo'), getFileSize('seo'), $image);
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', 'Couldn\'t upload the image'];
                return back()->withNotify($notify);
            }
        }
        $seo->data_values = SeoForm::values($request, $image);
        $seo->save();

        $notify[] = ['success', 'SEO content updated successfully'];
        return back()->withNotify($notify);
    }

    /** A section of sections.json; 404 when it does not exist or cannot be edited. */
    private function section(string $key): object
    {
        $section = @getPageSections()->$key;
        abort_if(!$section || empty($section->builder), 404);
        return $section;
    }

    /**
     * The form fields of a section part ("content" or "element") in a shape
     * the page can render: [name, type, label, options?, from?].
     */
    private function fields(object $part): array
    {
        $fields = [];
        foreach ($part as $k => $type) {
            if (in_array($k, ['images', 'modal', 'seo'], true)) {
                continue;
            }
            if ($k === 'select' && is_object($type)) {
                $fields[] = ['name' => (string) $type->name, 'type' => 'select', 'label' => (string) __(keyToTitle($type->name)), 'options' => collect((array) $type->options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) __($label)])->values()->all()];
            } elseif ($k === 'slug') {
                $fields[] = ['name' => 'slug', 'type' => 'slug', 'label' => (string) __('Slug'), 'from' => (string) $type];
            } else {
                $kind     = ['textarea' => 'textarea', 'textarea-nic' => 'html', 'icon' => 'icon'][$type] ?? 'text';
                $fields[] = ['name' => (string) $k, 'type' => $kind, 'label' => (string) __(keyToTitle($k))];
            }
        }
        return $fields;
    }

    /** The image fields of a section part: key => size ("400x300"). */
    private function images(object $part): array
    {
        return collect((array) ($part->images ?? []))->map(fn ($img) => (string) (@$img->size ?? ''))->all();
    }

    private function imageFields(string $key, object $part, ?Frontend $row): array
    {
        return collect($this->images($part))->map(fn ($size, $img) => [
            'key'   => (string) $img,
            'label' => (string) __(keyToTitle($img)),
            'size'  => $size,
            'url'   => frontendImage($key, @$row->data_values->$img, $size),
        ])->values()->all();
    }

    /** The saved values of a row, as the form edits them. */
    private function formValues(array $fields, ?Frontend $row): array
    {
        $values = [];
        foreach ($fields as $f) {
            $name  = $f['name'];
            $saved = $f['type'] === 'slug' ? @$row->slug : @$row->data_values->$name;
            $saved = is_scalar($saved) ? (string) $saved : '';
            if ($f['type'] === 'icon') {
                $saved = self::iconClass($saved);
            } elseif ($f['type'] === 'select' && $saved === '' && $f['options']) {
                $saved = $f['options'][0]['value'];
            }
            $values[$name] = $saved;
        }
        return $values;
    }

    /** '<i class="las la-home"></i>' → 'las la-home' (icons are saved as HTML the site prints). */
    private static function iconClass(string $html): string
    {
        if (preg_match('/class\s*=\s*["\']([^"\']*)["\']/i', $html, $m)) {
            return trim($m[1]);
        }
        return preg_match('/^[a-z0-9 _-]+$/i', $html) ? trim($html) : '';
    }

    public function frontendSections($key)
    {
        $section   = $this->section($key);
        $pageTitle = (string) $section->name;
        $temp      = activeTemplateName();

        $content = null;
        if (!empty($section->content)) {
            $row     = Frontend::where('data_keys', $key . '.content')->where('tempname', $temp)->orderBy('id', 'desc')->first();
            $fields  = $this->fields($section->content);
            $content = ['fields' => $fields, 'images' => $this->imageFields($key, $section->content, $row), 'values' => $this->formValues($fields, $row)];
        }

        $element  = null;
        $elements = [];
        if (!empty($section->element)) {
            $part    = $section->element;
            $fields  = $this->fields($part);
            $images  = $this->images($part);
            $element = [
                'fields'  => $fields,
                'images'  => $this->imageFields($key, $part, null),
                'modal'   => (bool) @$part->modal,
                'seo'     => (bool) @$part->seo,
                // The list shows the short fields, like the old table
                'columns' => collect($fields)->filter(fn ($f) => in_array($f['type'], ['text', 'icon', 'select'], true))->values()->all(),
            ];
            $firstImage = array_key_first($images);
            $elements   = Frontend::where('data_keys', $key . '.element')->where('tempname', $temp)->orderBy('id', 'desc')->get()->map(fn (Frontend $row) => [
                'id'     => $row->id,
                'image'  => $firstImage !== null ? frontendImage($key, @$row->data_values->$firstImage, $images[$firstImage]) : null,
                'values' => $this->formValues($fields, $row),
                'images' => collect($images)->map(fn ($size, $img) => frontendImage($key, @$row->data_values->$img, $size))->all(),
            ])->all();
        }

        return Inertia::render('Admin/Frontend/Section', [
            'name'     => (string) __($section->name),
            'content'  => $content,
            'element'  => $element,
            'elements' => $elements,
            'urls'     => [
                'save'      => route('admin.frontend.sections.content', $key),
                'create'    => route('admin.frontend.sections.element', $key),
                'edit'      => route('admin.frontend.sections.element', [$key, ':id']),
                'seo'       => route('admin.frontend.sections.element.seo', [$key, ':id']),
                'remove'    => route('admin.frontend.remove', ':id'),
                'slugCheck' => route('admin.frontend.sections.element.slug.check', $key),
                'back'      => empty($section->hide_builder) ? route('admin.frontend.index') : null,
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** One item of a section, on its own page (items with long text: blog posts, policy pages, FAQ). */
    public function frontendElement($key, $id = null)
    {
        $section = $this->section($key);
        abort_if(empty($section->element), 404);
        $part = $section->element;

        $row       = $id ? Frontend::where('data_keys', $key . '.element')->where('tempname', activeTemplateName())->findOrFail($id) : null;
        $fields    = $this->fields($part);
        $pageTitle = $section->name . ' Items';

        return Inertia::render('Admin/Frontend/Element', [
            'name'   => (string) __($section->name),
            'id'     => $row?->id,
            'fields' => $fields,
            'images' => $this->imageFields($key, $part, $row),
            'values' => $this->formValues($fields, $row),
            'urls'   => [
                'save'      => route('admin.frontend.sections.content', $key),
                'back'      => route('admin.frontend.sections', $key),
                'slugCheck' => route('admin.frontend.sections.element.slug.check', [$key, $row?->id]),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** Is a slug free among the items of this section (other than the one being edited)? */
    public function frontendElementSlugCheck($key, $id = null)
    {
        $slug  = slug((string) request('slug'));
        $query = Frontend::where('data_keys', $key . '.element')->where('tempname', activeTemplateName())->where('slug', $slug);
        if ($id) {
            $query->where('id', '!=', $id);
        }
        return response()->json(['slug' => $slug, 'exists' => $slug === '' || $query->exists()]);
    }

    /**
     * Save a section's content or one of its items. Only the fields that
     * sections.json lists are saved: the request used to be stored whole,
     * and an item id from another section (or the site SEO row) could be
     * overwritten through any section.
     */
    public function frontendContent(Request $request, $key)
    {
        $section = $this->section($key);
        $type    = $request->input('type');
        abort_unless(in_array($type, ['content', 'element'], true) && !empty($section->$type), 404);

        $part   = $section->$type;
        $fields = $this->fields($part);
        $images = $this->images($part);
        $temp   = activeTemplateName();

        $row = null;
        if ($type === 'element' && $request->filled('id')) {
            $row = Frontend::where('data_keys', $key . '.element')->where('tempname', $temp)->findOrFail($request->id);
        } elseif ($type === 'content') {
            $row = Frontend::where('data_keys', $key . '.content')->where('tempname', $temp)->orderBy('id', 'desc')->first();
        }

        // Slugs are compared as they will be saved
        if ($request->has('slug')) {
            $request->merge(['slug' => slug((string) $request->slug)]);
        }

        $rules = [];
        $names = [];
        foreach ($fields as $f) {
            $names[$f['name']] = $f['label'];
            $rules[$f['name']] = match ($f['type']) {
                'icon'   => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9 _-]*$/i'],
                'select' => ['required', Rule::in(array_column($f['options'], 'value'))],
                'slug'   => ['required', 'string', 'max:255', Rule::unique('frontends', 'slug')->where(fn ($q) => $q->where('data_keys', $key . '.element')->where('tempname', $temp))->ignore($row?->id)],
                'html'   => ['required', 'string', 'max:65000'],
                default  => ['required', 'string', 'max:' . ($f['type'] === 'textarea' ? 65000 : 2000)],
            };
        }
        foreach ($images as $img => $size) {
            $rules['image_input.' . $img] = ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])];
            $names['image_input.' . $img] = keyToTitle($img);
        }
        $request->validate($rules, ['slug.unique' => 'This slug is already used by another item.'], $names);

        $values = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $value = (string) $request->input($name);
            $values[$name] = match ($f['type']) {
                'slug'  => null,
                // Rich text is printed as HTML on the site: scripts and handlers removed
                'html'  => FormPresenter::safeHtml($value),
                'icon'  => '<i class="' . e(preg_replace('/\s+/', ' ', trim($value))) . '"></i>',
                // Plain text is escaped when printed, so it is saved as typed
                default => $value,
            };
        }
        $values = array_filter($values, fn ($v) => $v !== null);

        $path = 'assets/images/frontend/' . $key;
        foreach ($images as $img => $size) {
            $old  = @$row->data_values->$img;
            $file = $request->file('image_input.' . $img);
            if ($file) {
                try {
                    $values[$img] = fileUploader($file, $path, $size ?: null, $old, @$part->images->$img->thumb);
                } catch (\Exception $e) {
                    report($e);
                    $notify[] = ['error', 'Couldn\'t upload the image'];
                    return back()->withNotify($notify);
                }
            } elseif ($old) {
                $values[$img] = $old;
            }
        }

        $isNew = !$row;
        $row ??= new Frontend();
        $row->data_keys   = $key . '.' . $type;
        $row->tempname    = $temp;
        $row->data_values = $values;
        if (in_array('slug', array_column($fields, 'type'), true)) {
            $row->slug = $request->input('slug');
        }
        $row->save();

        if ($isNew && $type === 'element' && @$part->seo) {
            $notify[] = ['info', 'Configure SEO content for ranking'];
            $notify[] = ['success', 'Content updated successfully'];
            return to_route('admin.frontend.sections.element.seo', [$key, $row->id])->withNotify($notify);
        }
        if ($isNew && $type === 'element' && !@$part->modal) {
            $notify[] = ['success', 'Content updated successfully'];
            return to_route('admin.frontend.sections.element', [$key, $row->id])->withNotify($notify);
        }

        $notify[] = ['success', 'Content updated successfully'];
        return back()->withNotify($notify);
    }

    /** An item of a section that has SEO (blog posts, policy pages). */
    private function seoItem(string $key, $id): Frontend
    {
        abort_unless(@$this->section($key)->element->seo, 404);
        return Frontend::where('data_keys', $key . '.element')->where('tempname', activeTemplateName())->findOrFail($id);
    }

    public function frontendSeo($key, $id)
    {
        $item  = $this->seoItem($key, $id);
        $title = @$item->data_values->title;

        return SeoForm::render(
            'SEO Configuration' . ($title ? ': ' . $title : ''),
            $item->seo_content,
            frontendImage($key, @$item->seo_content->image, getFileSize('seo'), true),
            false,
            route('admin.frontend.sections.element.seo.update', [$key, $item->id]),
            route('admin.frontend.sections', $key),
        );
    }

    public function frontendSeoUpdate(Request $request, $key, $id)
    {
        $item = $this->seoItem($key, $id);
        SeoForm::validate($request, false);

        $image = @$item->seo_content->image;
        if ($request->hasFile('image')) {
            try {
                $image = fileUploader($request->image, 'assets/images/frontend/' . $key . '/seo', getFileSize('seo'), $image);
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', 'Couldn\'t upload the image'];
                return back()->withNotify($notify);
            }
        }
        $item->seo_content = SeoForm::values($request, $image);
        $item->save();

        $notify[] = ['success', 'SEO content updated successfully'];
        return back()->withNotify($notify);
    }

    /** Remove an item of a section, with its pictures. Only items: a section's content or the site's SEO, cookie and maintenance rows stay. */
    public function remove($id)
    {
        $item = Frontend::where('data_keys', 'like', '%.element')->findOrFail($id);
        $key  = explode('.', $item->data_keys)[0];
        $part = @getPageSections()->$key->element;

        $path = 'assets/images/frontend/' . $key;
        foreach ((array) (@$part->images ?? []) as $img => $spec) {
            if ($file = @$item->data_values->$img) {
                fileManager()->removeFile($path . '/' . $file);
                fileManager()->removeFile($path . '/thumb_' . $file);
            }
        }
        if ($seoImage = @$item->seo_content->image) {
            fileManager()->removeFile($path . '/seo/' . $seoImage);
        }
        $item->delete();

        $notify[] = ['success', 'Content removed successfully'];
        return back()->withNotify($notify);
    }
}
