<?php

namespace App\Jobs;

use App\Models\Matrix;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FixMatrixPositionChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public array $matrixIds) {}

    public function handle(): void
    {
        Matrix::whereIn('id', $this->matrixIds)
            ->get()
            ->each(function (Matrix $matrix) {
                if (!$matrix->parent_id) {
                    return;
                }

                $parent = Matrix::where('user_id', $matrix->parent_id)
                                ->where('stage_id', 1)
                                ->first();

                if (!$parent) {
                    return;
                }

                if ((int) $parent->left === (int) $matrix->user_id) {
                    $matrix->position = 'left';
                    $matrix->save();
                } elseif ((int) $parent->right === (int) $matrix->user_id) {
                    $matrix->position = 'right';
                    $matrix->save();
                }
            });
    }
}
