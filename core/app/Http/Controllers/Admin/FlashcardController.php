<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flashcard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Flashcards (Pages/Admin/Flashcards.tsx): pictures and videos shown once to
 * each member on their dashboard, until they close them.
 */
class FlashcardController extends Controller
{
    /** File types per card type. The upload took any file, and kept its extension in a public folder. */
    private const TYPES = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'webm', 'ogg', 'mov'],
    ];

    public function index()
    {
        $pageTitle = 'Flashcards';

        return Inertia::render('Admin/Flashcards', [
            'flashcards' => Flashcard::withCount('viewers')->latest('id')->paginate(getPaginate())->through(fn (Flashcard $f) => [
                'id'          => $f->id,
                'title'       => (string) $f->title,
                'description' => (string) $f->description,
                'type'        => $f->type === 'video' ? 'video' : 'image',
                'url'         => $f->file_url,
                'active'      => (bool) $f->is_active,
                'seen'        => (int) $f->viewers_count,
                'date'        => showDateTime($f->created_at, 'd M Y'),
            ]),
            'types' => self::TYPES,
            'urls'  => [
                'store'   => route('admin.flashcard.store'),
                'toggle'  => route('admin.flashcard.toggle', ':id'),
                'destroy' => route('admin.flashcard.destroy', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type'        => ['required', Rule::in(array_keys(self::TYPES))],
            'file'        => ['required', 'file', 'max:51200', 'mimes:' . implode(',', self::TYPES[$request->type] ?? ['none']), 'extensions:' . implode(',', self::TYPES[$request->type] ?? ['none'])],
        ], ['file.mimes' => 'This file is not a supported ' . ($request->type ?: 'image or video') . '.', 'file.extensions' => 'Allowed: ' . implode(', ', self::TYPES[$request->type] ?? [])]);

        try {
            $filename = fileUploader($request->file('file'), getFilePath('flashcard'));
        } catch (\Exception $e) {
            report($e);
            $notify[] = ['error', 'The file could not be uploaded'];
            return back()->withNotify($notify);
        }

        Flashcard::create([
            'title'       => $request->title,
            'description' => $request->description,
            'type'        => $request->type,
            'file'        => $filename,
            'is_active'   => true,
        ]);

        $notify[] = ['success', 'Flashcard uploaded successfully.'];
        return back()->withNotify($notify);
    }

    /** Show (state=enable) or hide (state=disable) a flashcard. */
    public function toggle(Request $request, $id)
    {
        $request->validate(['state' => 'required|in:enable,disable']);
        $flashcard = Flashcard::findOrFail($id);
        $active    = $request->state === 'enable';
        if ($flashcard->is_active === $active) {
            $notify[] = ['info', 'Flashcard is already ' . ($active ? 'active' : 'hidden') . '.'];
            return back()->withNotify($notify);
        }
        $flashcard->is_active = $active;
        $flashcard->save();

        $notify[] = ['success', 'Flashcard ' . ($active ? 'activated' : 'deactivated') . ' successfully.'];
        return back()->withNotify($notify);
    }

    public function destroy($id)
    {
        $flashcard = Flashcard::findOrFail($id);
        fileManager()->removeFile(getFilePath('flashcard') . '/' . $flashcard->file);
        $flashcard->viewers()->detach();
        $flashcard->delete();

        $notify[] = ['success', 'Flashcard deleted successfully.'];
        return back()->withNotify($notify);
    }
}
