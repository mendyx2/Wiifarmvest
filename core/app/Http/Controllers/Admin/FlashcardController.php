<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flashcard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FlashcardController extends Controller
{
    public function index()
    {
        $pageTitle  = 'Flashcards';
        $flashcards = Flashcard::latest()->paginate(getPaginate());
        return view('admin.flashcard.index', compact('pageTitle', 'flashcards'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:image,video',
            'file'        => 'required|file|max:51200', // 50 MB
        ]);

        $file     = $request->file('file');
        $path     = getFilePath('flashcard');
        $filename = fileUploader($file, $path);

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

    public function toggle($id)
    {
        $flashcard            = Flashcard::findOrFail($id);
        $flashcard->is_active = !$flashcard->is_active;
        $flashcard->save();

        $status  = $flashcard->is_active ? 'activated' : 'deactivated';
        $notify[] = ['success', "Flashcard {$status} successfully."];
        return back()->withNotify($notify);
    }

    public function destroy($id)
    {
        $flashcard = Flashcard::findOrFail($id);

        $filePath = getFilePath('flashcard') . '/' . $flashcard->file;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $flashcard->delete();

        $notify[] = ['success', 'Flashcard deleted successfully.'];
        return back()->withNotify($notify);
    }
}
