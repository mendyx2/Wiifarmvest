<?php
// app/Console/Commands/CreateSampleFlashcards.php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserFlashcardPreference;
use Illuminate\Console\Command;

class CreateSampleFlashcards extends Command
{
    protected $signature = 'flashcards:create-sample';
    protected $description = 'Create sample flashcards for users';
 
    public function handle()
    {
        $users = User::all();
        
        foreach ($users as $user) {
            UserFlashcardPreference::create([
                'user_id' => $user->id,
                'type' => 'video',
                'content_url' => 'https://example.com/welcome-video.mp4',
                'title' => 'Welcome Video',
                'description' => 'Watch this quick welcome video to get started!',
                'is_active' => true,
            ]);
            
            UserFlashcardPreference::create([
                'user_id' => $user->id,
                'type' => 'image',
                'content_url' => 'https://example.com/feature-image.jpg',
                'title' => 'New Feature',
                'description' => 'Check out our latest feature!',
                'is_active' => true,
            ]);
        }
        
        $this->info('Sample flashcards created for all users.');
    }
}