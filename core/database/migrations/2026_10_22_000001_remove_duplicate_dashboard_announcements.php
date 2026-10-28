<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UserController@home used to insert this exact "announcement" into
 * user_flashcard_preferences on every dashboard visit, so the table grew by
 * one row per member per page view. Nothing ever displayed these rows (the
 * dashboard shows admin-created flashcards from the `flashcards` table).
 *
 * Only rows with that exact machine-written signature are removed; anything
 * else in the table is left alone. Deleted in batches to keep locks short.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_flashcard_preferences')) {
            return;
        }

        do {
            $ids = DB::table('user_flashcard_preferences')
                ->where('title', 'Announcement: New Feature')
                ->where('description', "We've added a new dashboard feature!")
                ->where('content_url', 'like', '%storage/announcements/new-feature.jpg')
                ->orderBy('id')
                ->limit(5000)
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                DB::table('user_flashcard_preferences')->whereIn('id', $ids)->delete();
            }
        } while ($ids->count() === 5000);
    }

    public function down(): void
    {
        // Nothing to restore: the deleted rows were duplicates nobody could see.
    }
};
