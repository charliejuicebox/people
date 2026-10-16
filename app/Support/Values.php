<?php

namespace App\Support;

/**
 * Company values from the Brand Platform, used as praise tags and review sections.
 */
class Values
{
    public const ALL = [
        'make_it_matter' => ['en' => 'Make it Matter', 'id' => 'Wujudkan yang Bermakna'],
        'work_as_one' => ['en' => 'Work as One', 'id' => 'Bekerja Sebagai Satu'],
        'embrace_change' => ['en' => 'Embrace Change', 'id' => 'Merangkul Perubahan'],
    ];

    public const DESCRIPTIONS = [
        'make_it_matter' => 'Own the work. Honour the craft. Pursue excellence. Strive for impact.',
        'work_as_one' => 'Build trust. Share with clarity. Move forward together.',
        'embrace_change' => 'Be open minded. Learn with intent. Celebrate curiosity.',
    ];

    public const EMOJI = [
        'make_it_matter' => ':star2:',
        'work_as_one' => ':handshake:',
        'embrace_change' => ':seedling:',
    ];

    public static function keys(): array
    {
        return array_keys(self::ALL);
    }

    public static function label(string $key, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return self::ALL[$key][$locale] ?? self::ALL[$key]['en'] ?? $key;
    }

    /** "Make it Matter / Wujudkan yang Bermakna" for formal, bilingual documents. */
    public static function bilingual(string $key): string
    {
        return self::ALL[$key]['en'].' / '.self::ALL[$key]['id'];
    }
}
