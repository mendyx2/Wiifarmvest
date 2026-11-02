<?php

namespace App\Services\Usdt;

/** A USDT payment cannot be started (address not set, or no free amount). The message is for the member. */
class UsdtNotReady extends \RuntimeException
{
}
