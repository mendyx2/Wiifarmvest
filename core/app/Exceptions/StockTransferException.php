<?php

namespace App\Exceptions;

/**
 * A stock transfer step that was refused for a reason the stockist can act on
 * (not enough stock, wallet too low, already handled...). The message is safe
 * to show as-is; nothing was changed when it is thrown.
 */
class StockTransferException extends \RuntimeException
{
}
