<?php

namespace App\Support;

class Ratings
{
    public const OVERALL = [
        'exceeds' => ['en' => 'Exceeds expectations', 'id' => 'Melampaui ekspektasi'],
        'meets' => ['en' => 'Meets expectations', 'id' => 'Memenuhi ekspektasi'],
        'developing' => ['en' => 'Developing', 'id' => 'Berkembang'],
        'below' => ['en' => 'Below expectations', 'id' => 'Di bawah ekspektasi'],
    ];

    public static function keys(): array
    {
        return array_keys(self::OVERALL);
    }

    public static function bilingual(?string $key): string
    {
        if (! $key || ! isset(self::OVERALL[$key])) {
            return '—';
        }

        return self::OVERALL[$key]['en'].' / '.self::OVERALL[$key]['id'];
    }
}
