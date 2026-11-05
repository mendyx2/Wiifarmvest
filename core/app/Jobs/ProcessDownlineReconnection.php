<?php

namespace App\Jobs;

use App\Models\Matrix;
use App\Services\MatrixReconnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reconnect one matrix's downline in the background (MatrixReconnectionService).
 * It used to call a service it never imported, so it failed every time.
 */
class ProcessDownlineReconnection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Matrix $matrix)
    {
    }

    public function handle(MatrixReconnectionService $reconnection): void
    {
        $reconnection->reconnectMatrix($this->matrix);
    }
}
