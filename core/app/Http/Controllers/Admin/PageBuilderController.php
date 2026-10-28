<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SeoForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Manage Pages (Pages/Admin/Frontend/Pages.tsx, PageSections.tsx and the
 * shared Seo.tsx): the site's pages, the sections each one shows and in what
 * order, and each page's SEO.
 */
class PageBuilderController extends Controller
{
    public function managePages()
    {
        $pageTitle = 'Manage Pages';

        return Inertia::render('Admin/Frontend/Pages', [
            'pages' => Page::where('tempname', activeTemplate())->orderBy('id')->get()->map(fn (Page $p) => [
                'id'      => $p->id,
                'name'    => (string) __($p->name),
                'slug'    => (string) $p->slug,
                'default' => (int) $p->is_default !== Status::NO,
            ]),
            'urls' => [
                'store'     => route('admin.frontend.manage.pages.save'),
                'sections'  => route('admin.frontend.manage.section', ':id'),
                'seo'       => route('admin.frontend.manage.pages.seo', ':id'),
                'delete'    => route('admin.frontend.manage.pages.delete', ':id'),
                'slugCheck' => route('admin.frontend.manage.pages.check.slug'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /**
     * Name and slug rules. A slug must be free on this template and must not
     * be the address of another part of the site (/user, /admin, /login...):
     * such a page could never be opened.
     */
    private function validatePage(Request $request, ?Page $page = null): string
    {
        $request->merge(['slug' => slug((string) $request->slug)]);
        $request->validate([
            'name' => 'required|string|min:3|max:40',
            'slug' => ['required', 'string', 'min:3', 'max:40', Rule::unique('pages', 'slug')->where('tempname', activeTemplate())->ignore($page?->id)],
        ], ['slug.unique' => 'This page already exists on your current template. Please change the slug.']);

        if ($this->slugTaken($request->slug)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['slug' => 'This address is used by another part of the site. Please change the slug.']);
        }

        return $request->slug;
    }

    private function slugTaken(string $slug): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create('/' . $slug));
        } catch (\Throwable) {
            return false;
        }
        return $route->getName() !== 'pages';
    }

    public function managePagesSave(Request $request)
    {
        $slug = $this->validatePage($request);

        $page           = new Page();
        $page->tempname = activeTemplate();
        $page->name     = $request->name;
        $page->slug     = $slug;
        $page->save();

        $notify[] = ['success', 'New page added successfully'];
        return to_route('admin.frontend.manage.section', $page->id)->withNotify($notify);
    }

    /** Rename a page or change its address. The built-in pages keep theirs: the site links to them. */
    public function managePagesUpdate(Request $request, $id)
    {
        $page = Page::where('is_default', Status::NO)->findOrFail($id);
        $slug = $this->validatePage($request, $page);

        $page->name = $request->name;
        $page->slug = $slug;
        $page->save();

        $notify[] = ['success', 'Page updated successfully'];
        return back()->withNotify($notify);
    }

    public function checkSlug($id = null)
    {
        $slug  = slug((string) request('slug'));
        $query = Page::where('tempname', activeTemplate())->where('slug', $slug);
        if ($id) {
            $query->where('id', '!=', $id);
        }
        return response()->json(['slug' => $slug, 'exists' => $slug === '' || $query->exists() || $this->slugTaken($slug)]);
    }

    public function managePagesDelete($id)
    {
        $page = Page::where('id', $id)->where('is_default', Status::NO)->firstOrFail();
        $page->delete();

        $notify[] = ['success', 'Page deleted successfully'];
        return to_route('admin.frontend.manage.pages')->withNotify($notify);
    }

    /** Sections a page can show: the template's sections that are not tied to one place (banned, login, footer...). */
    private function selectable(): array
    {
        return collect(getPageSections(true))->reject(fn ($s) => !empty($s['no_selection']))->all();
    }

    public function manageSection($id)
    {
        $page      = Page::findOrFail($id);
        $pageTitle = 'Manage Section of ' . $page->name;
        $sections  = getPageSections(true);

        return Inertia::render('Admin/Frontend/PageSections', [
            'page' => [
                'id'      => $page->id,
                'name'    => (string) __($page->name),
                'slug'    => (string) $page->slug,
                'default' => (int) $page->is_default !== Status::NO,
                // Saved keys whose section no longer exists are dropped
                'secs'    => collect((array) json_decode((string) $page->secs, true))->filter(fn ($k) => is_string($k) && isset($sections[$k]))->values(),
            ],
            'sections' => collect($this->selectable())->map(fn ($s, $k) => [
                'key'  => (string) $k,
                'name' => (string) __($s['name']),
                'url'  => !empty($s['builder']) ? route('admin.frontend.sections', $k) : null,
            ])->values(),
            'urls' => [
                'save'      => route('admin.frontend.manage.section.update', $page->id),
                'update'    => route('admin.frontend.manage.pages.update', $page->id),
                'seo'       => route('admin.frontend.manage.pages.seo', $page->id),
                'back'      => route('admin.frontend.manage.pages'),
                'slugCheck' => route('admin.frontend.manage.pages.check.slug', $page->id),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /**
     * Save the page's sections in order. Each key must be a section of the
     * template: the site includes a view per key, and any text used to be
     * accepted.
     */
    public function manageSectionUpdate($id, Request $request)
    {
        $request->validate([
            'secs'   => 'nullable|array|max:50',
            'secs.*' => ['required', 'string', Rule::in(array_keys($this->selectable()))],
        ]);

        $page       = Page::findOrFail($id);
        $secs       = array_values(array_unique((array) $request->secs));
        $page->secs = $secs ? json_encode($secs) : null;
        $page->save();

        $notify[] = ['success', 'Page sections updated successfully'];
        return back()->withNotify($notify);
    }

    public function manageSeo($id)
    {
        $page = Page::findOrFail($id);

        return SeoForm::render(
            'SEO Configuration for ' . $page->name . ' Page',
            $page->seo_content,
            getImage(getFilePath('seo') . '/' . @$page->seo_content->image, getFileSize('seo')),
            false,
            route('admin.frontend.manage.pages.seo.update', $page->id),
            route('admin.frontend.manage.pages'),
        );
    }

    public function manageSeoStore(Request $request, $id)
    {
        $page = Page::findOrFail($id);
        SeoForm::validate($request, false);

        $image = @$page->seo_content->image;
        if ($request->hasFile('image')) {
            try {
                $image = fileUploader($request->image, getFilePath('seo'), getFileSize('seo'), $image);
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', 'Couldn\'t upload the image'];
                return back()->withNotify($notify);
            }
        }
        $page->seo_content = SeoForm::values($request, $image);
        $page->save();

        $notify[] = ['success', 'SEO content updated successfully'];
        return back()->withNotify($notify);
    }
}
