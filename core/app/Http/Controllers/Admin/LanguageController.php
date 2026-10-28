<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Frontend;
use Illuminate\Http\Request;
use App\Models\Language;
use App\Rules\FileTypeValidate;
use Illuminate\Support\Facades\File;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

/**
 * Language Manager (Pages/Admin/Language/*): the languages members can pick,
 * and each language's keywords (resources/lang/<code>.json).
 */
class LanguageController extends Controller
{
    /**
     * A language code becomes a file name (resources/lang/<code>.json). It
     * used to be taken as typed, so "../../composer" overwrote
     * core/composer.json. Now: letters, optionally a region (en, pt-br, zh_cn).
     */
    private const CODE = '/^[a-z]{2,3}([_-][a-z0-9]{2,8})?$/i';

    private function file(string $code): string
    {
        abort_unless(preg_match(self::CODE, $code), 404);
        return resource_path('lang/') . strtolower($code) . '.json';
    }

    /** @return array<string, string> */
    private function keywords(Language $lang): array
    {
        $data = json_decode((string) @file_get_contents($this->file($lang->code)), true);
        return is_array($data) ? $data : [];
    }

    private function saveKeywords(Language $lang, array $keywords): void
    {
        // Written whole, then moved into place, so a crash cannot leave half a file
        $file = $this->file($lang->code);
        File::put($file . '.tmp', json_encode($keywords, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::move($file . '.tmp', $file);
    }

    public function langManage()
    {
        $pageTitle = 'Language Manager';

        return Inertia::render('Admin/Language/Index', [
            'languages' => Language::orderBy('is_default', 'desc')->orderBy('name')->get()->map(fn (Language $l) => [
                'id'        => $l->id,
                'name'      => (string) $l->name,
                'code'      => (string) $l->code,
                'default'   => (int) $l->is_default === Status::YES,
                'image'     => getImage(getFilePath('language') . '/' . $l->image, getFileSize('language')),
                // English is the base every new language is copied from
                'removable' => (int) $l->is_default !== Status::YES && strtolower((string) $l->code) !== 'en',
            ]),
            'size' => getFileSize('language'),
            'urls' => [
                'store'     => route('admin.language.manage.store'),
                'update'    => route('admin.language.manage.update', ':id'),
                'delete'    => route('admin.language.manage.delete', ':id'),
                'translate' => route('admin.language.key', ':id'),
                'keys'      => route('admin.language.get.key'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function langStore(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:40',
            'code'       => ['required', 'string', 'max:40', 'regex:' . self::CODE, 'unique:languages'],
            'image'      => ['required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'is_default' => 'nullable|boolean',
        ], ['code.regex' => 'Use a language code such as "fr", "pt-br" or "zh_cn".']);

        $code = strtolower($request->code);
        if (is_file($this->file($code))) {
            return back()->withErrors(['code' => 'A keyword file for this code already exists.']);
        }

        $language = new Language();
        try {
            $language->image = fileUploader($request->image, getFilePath('language'), getFileSize('language'));
        } catch (\Exception $e) {
            report($e);
            $notify[] = ['error', 'Couldn\'t upload language image'];
            return back()->withNotify($notify);
        }

        // A new language starts as a copy of English
        File::copy(resource_path('lang/en.json'), $this->file($code));

        if ($request->boolean('is_default')) {
            Language::where('is_default', Status::YES)->update(['is_default' => Status::NO]);
        }
        $language->name       = $request->name;
        $language->code       = $code;
        $language->is_default = $request->boolean('is_default') ? Status::YES : Status::NO;
        $language->save();

        $notify[] = ['success', 'Language added successfully'];
        return back()->withNotify($notify);
    }

    public function langUpdate(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:40',
            'image'      => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'is_default' => 'nullable|boolean',
        ]);

        $language = Language::findOrFail($id);

        if (!$request->boolean('is_default') && !Language::where('is_default', Status::YES)->where('id', '!=', $id)->exists()) {
            $notify[] = ['error', 'You\'ve to set another language as default before unset this'];
            return back()->withNotify($notify);
        }

        if ($request->hasFile('image')) {
            try {
                $language->image = fileUploader($request->image, getFilePath('language'), getFileSize('language'), $language->image);
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', 'Couldn\'t upload language image'];
                return back()->withNotify($notify);
            }
        }
        $language->name       = $request->name;
        $language->is_default = $request->boolean('is_default') ? Status::YES : Status::NO;
        $language->save();

        if ($request->boolean('is_default')) {
            Language::where('is_default', Status::YES)->where('id', '!=', $language->id)->update(['is_default' => Status::NO]);
        }

        $notify[] = ['success', 'Language updated successfully'];
        return back()->withNotify($notify);
    }

    /** Not the default language (members would have none), and not English (the base for new ones). */
    public function langDelete($id)
    {
        $lang = Language::findOrFail($id);

        if ((int) $lang->is_default === Status::YES || strtolower((string) $lang->code) === 'en') {
            $notify[] = ['error', (int) $lang->is_default === Status::YES ? 'Set another language as default before removing this one.' : 'English is the base of every language and cannot be removed.'];
            return back()->withNotify($notify);
        }

        if (preg_match(self::CODE, (string) $lang->code)) {
            fileManager()->removeFile($this->file($lang->code));
        }
        fileManager()->removeFile(getFilePath('language') . '/' . $lang->image);
        $lang->delete();

        $notify[] = ['success', 'Language deleted successfully'];
        return back()->withNotify($notify);
    }

    public function langEdit($id)
    {
        $lang      = Language::findOrFail($id);
        $pageTitle = 'Update ' . $lang->name . ' Keywords';

        if (!is_file($this->file($lang->code))) {
            $notify[] = ['error', 'File not found'];
            return back()->withNotify($notify);
        }

        $json   = $this->keywords($lang);
        $search = trim((string) request()->search);
        if ($search !== '') {
            $json = array_filter($json, fn ($value, $key) => stripos((string) $key, $search) !== false || stripos((string) $value, $search) !== false, ARRAY_FILTER_USE_BOTH);
        }

        $perPage = getPaginate(20);
        $page    = max(1, (int) request()->get('page', 1));
        $items   = array_slice($json, ($page - 1) * $perPage, $perPage, true);
        $keys    = (new LengthAwarePaginator(
            collect($items)->map(fn ($value, $key) => ['key' => (string) $key, 'value' => (string) $value])->values(),
            count($json),
            $perPage,
            $page,
            ['path' => url()->current()]
        ))->withQueryString();

        return Inertia::render('Admin/Language/Keywords', [
            'language' => ['id' => $lang->id, 'name' => (string) $lang->name, 'code' => (string) $lang->code],
            'keys'     => $keys,
            'search'   => $search,
            'others'   => Language::where('id', '!=', $lang->id)->orderBy('name')->get(['id', 'name'])->map(fn ($l) => ['id' => $l->id, 'name' => (string) $l->name]),
            'urls'     => [
                'store'  => route('admin.language.store.key', $lang->id),
                'update' => route('admin.language.update.key', $lang->id),
                'delete' => route('admin.language.delete.key', $lang->id),
                'import' => route('admin.language.import.lang'),
                'back'   => route('admin.language.manage'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /**
     * Add the keywords this language is missing, from another language or
     * from every keyword the system uses ("999"). Existing translations stay.
     */
    public function langImport(Request $request)
    {
        $request->validate(['toLangid' => 'required|integer', 'id' => 'required|integer']);

        $to = Language::findOrFail($request->toLangid);
        if ((int) $request->id === 999) {
            $incoming = array_map('trim', explode("\n", $this->getKeys()));
        } else {
            $incoming = array_keys($this->keywords(Language::findOrFail($request->id)));
        }

        $current = $this->keywords($to);
        $added   = 0;
        foreach ($incoming as $keyword) {
            $keyword = trim((string) $keyword);
            if ($keyword !== '' && !array_key_exists($keyword, $current)) {
                $current[$keyword] = $keyword;
                $added++;
            }
        }
        if ($added) {
            $this->saveKeywords($to, $current);
        }

        $notify[] = ['success', $added ? "Imported {$added} new keyword(s)" : 'Nothing to import: every keyword is already there'];
        return back()->withNotify($notify);
    }

    public function storeLanguageJson(Request $request, $id)
    {
        $lang = Language::findOrFail($id);
        $request->validate(['key' => 'required|string|max:1000', 'value' => 'required|string|max:5000']);

        $keywords = $this->keywords($lang);
        $key      = trim($request->key);
        if (array_key_exists($key, $keywords)) {
            return back()->withErrors(['key' => 'Key already exist']);
        }

        $keywords[$key] = trim($request->value);
        $this->saveKeywords($lang, $keywords);

        $notify[] = ['success', 'Language key added successfully'];
        return back()->withNotify($notify);
    }

    public function deleteLanguageJson(Request $request, $id)
    {
        $request->validate(['key' => 'required|string']);
        $lang     = Language::findOrFail($id);
        $keywords = $this->keywords($lang);

        if (!array_key_exists($request->key, $keywords)) {
            $notify[] = ['error', 'This key is not in the file (already removed?)'];
            return back()->withNotify($notify);
        }

        unset($keywords[$request->key]);
        $this->saveKeywords($lang, $keywords);

        $notify[] = ['success', 'Language key deleted successfully'];
        return back()->withNotify($notify);
    }

    public function updateLanguageJson(Request $request, $id)
    {
        $request->validate(['key' => 'required|string', 'value' => 'required|string|max:5000']);
        $lang     = Language::findOrFail($id);
        $keywords = $this->keywords($lang);

        // Only an existing key: "update" used to add any key it was given
        if (!array_key_exists($request->key, $keywords)) {
            $notify[] = ['error', 'This key is not in the file (it may have been removed)'];
            return back()->withNotify($notify);
        }

        $keywords[$request->key] = $request->value;
        $this->saveKeywords($lang, $keywords);

        $notify[] = ['success', 'Language key updated successfully'];
        return back()->withNotify($notify);
    }

    public function getKeys()
    {
        $langKeys = [];
        $dirname = resource_path('views');
        foreach ($this->getAllFiles($dirname) as $file) {
            $langKeys = array_merge($langKeys, $this->getLangKeys($file));
        }
        // React (Inertia) pages translate with t('...') instead of @lang('...').
        $langKeys = array_merge($langKeys, $this->getReactLangKeys(resource_path('js')));
        $frontendData = Frontend::where('data_keys', '!=', 'seo.data')->get();
        foreach ($frontendData as $frontend) {
            foreach ($frontend->data_values as $key => $frontendValue) {
                if ($key != 'has_image' && !isImage($frontendValue) && !isHtml($frontendValue)) {
                    $langKeys[] = $frontendValue;
                }
            }
        }
        $langKeys = array_unique($langKeys);
        $keyText = '';
        foreach ($langKeys as $langKey) {
            $keyText .= "$langKey \n";
        }
        return rtrim($keyText, "\n");
    }


    private function getAllFiles($dir)
    {
        $root = $dir;

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($iter as $path => $dir) {
            if (!$dir->isDir() && substr($dir, -4) == '.php') {
                $files[] = $path;
            }
        }

        return $files;
    }

    private function getLangKeys($path)
    {
        $code = file_get_contents($path);
        $exp = explode("')", $code);
        $finalcode = '';
        foreach ($exp as $dd) {
            $finalcode .= $dd . "')

            ";
        }
        preg_match_all("/@lang\(\\'(.*)\\'\)/",  $finalcode, $keys);
        return $this->fixMultiIssue($keys[1]);
    }

    /**
     * Collect the string literals passed to t('...') / t("...") in the React
     * pages under resources/js (*.ts, *.tsx).
     */
    private function getReactLangKeys($dir)
    {
        if (!is_dir($dir)) {
            return [];
        }

        $keys = [];
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iter as $path => $file) {
            if (!preg_match('/\.tsx?$/', $path)) {
                continue;
            }
            preg_match_all('/\bt\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/', file_get_contents($path), $matches);
            foreach ($matches[2] as $key) {
                $keys[] = stripslashes($key);
            }
        }

        return $keys;
    }

    private function fixMultiIssue($arr)
    {
        $res = array();
        foreach ($arr as $keys) {
            $exp = explode("')", $keys);
            foreach ($exp as $child) {
                if (!strpos($child, '@lang') && !strpos($child, '}') && !strpos($child, '<') && !strpos($child, '{') && !strpos($child, '>')) {
                    $res[] =  $child;
                }
            }
        }
        return $res;
    }
}
