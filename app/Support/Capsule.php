<?php

namespace App\Support;

use Carbon\Carbon;

class Capsule
{
    public const OPENING = '2050-01-01';

    public const TOTAL_CAP = 10_000_000;

    public const FOUNDING_CAP = 10_000;

    public const SEAL_PRICE_CENTS = 500;

    public const MIN_SEAL_PRICE_CENTS = 300;

    public const DEFAULT_SEAL_PRICE_CENTS = 500;

    public const PLATFORM_FEE_PERCENT = 20;

    public const CREATOR_CUT_CENTS = 400; // 80% of default $5.00

    public static function calculatePlatformCut(int $amountCents): int
    {
        return (int) round($amountCents * (self::PLATFORM_FEE_PERCENT / 100));
    }

    public static function calculateCreatorCut(int $amountCents): int
    {
        return max(0, $amountCents - self::calculatePlatformCut($amountCents));
    }

    public const MAX_LETTER = 600;

    public const MAX_TEASER = 72;

    public static function opening(): Carbon
    {
        return Carbon::parse(self::OPENING.' 00:00:00', 'UTC');
    }

    public static function clampAddressDate(?string $value): string
    {
        $v = substr((string) $value, 0, 10);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return self::OPENING;
        }
        if ($v > self::OPENING) {
            return self::OPENING;
        }
        if ($v < '2026-01-01') {
            return '2026-01-01';
        }

        return $v;
    }

    public static function clampDraftDate(?string $value): string
    {
        $v = self::clampAddressDate($value);
        $min = now()->toDateString();

        return $v < $min ? $min : $v;
    }

    public static function makeTeaser(string $text): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($clean === '') {
            return '';
        }
        if (preg_match('/^[^.!?]+[.!?]?/u', $clean, $m)) {
            $sentence = trim($m[0]);
            if (mb_strlen($sentence) <= self::MAX_TEASER) {
                return $sentence;
            }
        }

        return mb_substr($clean, 0, self::MAX_TEASER - 1).'…';
    }

    public static function formatNumber(int $n): string
    {
        return str_pad((string) max(0, $n), 8, '0', STR_PAD_LEFT);
    }

    public static function formatDay(string $iso): string
    {
        $iso = self::clampAddressDate($iso);

        return Carbon::parse($iso, 'UTC')->format('j F Y');
    }

    public static function formatBoardDay(string $iso): string
    {
        $iso = self::clampAddressDate($iso);

        return strtoupper(Carbon::parse($iso, 'UTC')->format('d M Y'));
    }

    public static function daysUntil(string $iso): int
    {
        $day = self::clampAddressDate($iso);
        $target = Carbon::parse($day, 'UTC')->startOfDay();
        $today = now('UTC')->startOfDay();

        return (int) $today->diffInDays($target, false);
    }

    public static function untilLabel(string $iso): string
    {
        $n = self::daysUntil($iso);
        if ($n < 0) {
            return 'This morning has passed. The letter still waits until 2050.';
        }
        if ($n === 0) {
            return 'This is the morning.';
        }
        if ($n === 1) {
            return 'Arrives tomorrow.';
        }
        if ($n < 45) {
            return "Arrives in {$n} days.";
        }
        $years = intdiv($n, 365);
        $rest = $n % 365;
        if ($years === 0) {
            return "Arrives in {$n} days.";
        }
        if ($rest < 21) {
            return 'Arrives in '.$years.' '.($years === 1 ? 'year' : 'years').'.';
        }

        return 'Arrives in '.$years.' '.($years === 1 ? 'year' : 'years').", {$rest} days.";
    }

    public static function untilBoard(string $iso): string
    {
        $n = self::daysUntil($iso);
        if ($n < 0) {
            return 'PASSED';
        }
        if ($n === 0) {
            return 'TODAY';
        }
        if ($n === 1) {
            return 'TOMORROW';
        }
        if ($n < 45) {
            return "{$n} DAYS";
        }
        $years = intdiv($n, 365);
        $rest = $n % 365;

        return $years === 0 ? "{$n} DAYS" : "{$years} YR {$rest} D";
    }

    public static function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return substr($slug, 0, 48);
    }

    public static function remaining(): array
    {
        $now = now('UTC');
        $opening = self::opening();
        $years = $opening->year - $now->year;
        $months = $opening->month - $now->month;
        $days = $opening->day - $now->day;
        if ($days < 0) {
            $months -= 1;
            $days += $now->copy()->startOfMonth()->subDay()->day;
        }
        if ($months < 0) {
            $years -= 1;
            $months += 12;
        }
        $ms = max(0, $opening->getTimestampMs() - $now->getTimestampMs());
        $hours = (int) floor(($ms / 3_600_000) % 24);

        return compact('years', 'months', 'days', 'hours');
    }
}
