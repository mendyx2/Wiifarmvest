<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Flashcard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FlashcardController extends Controller
{
    /**
     * Mark a flashcard as viewed by the authenticated user.
     * Called via AJAX when user closes the flashcard.
     */
    public function dismiss(Request $request, $id)
    {
        $flashcard = Flashcard::active()->findOrFail($id);

        DB::table('flashcard_views')->updateOrInsert(
            ['user_id' => Auth::id(), 'flashcard_id' => $flashcard->id],
            ['viewed_at' => now()]
        );

        return response()->json(['success' => true]);
    }
}
