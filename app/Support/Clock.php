<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Clock
{
    public static function apply(?string $time = null): void
    {
        $demo = config('cantine.demo_now');
        if (! config('cantine.demo') || ! $demo) {
            return;
        }
        $base = Carbon::parse($demo);
        if ($time && preg_match('/^\d{2}:\d{2}$/', $time)) {
            $base = $base->copy()->setTimeFromTimeString($time);
        }
        Carbon::setTestNow($base);
        \Carbon\CarbonImmutable::setTestNow($base);
    }

    public static function today(): Carbon
    {
        return Carbon::today();
    }
}
