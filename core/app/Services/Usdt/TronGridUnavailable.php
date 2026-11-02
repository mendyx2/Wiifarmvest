<?php

namespace App\Services\Usdt;

/** TronGrid could not be asked, or gave an answer we cannot trust. Nothing is credited or recorded. */
class TronGridUnavailable extends \RuntimeException
{
}
