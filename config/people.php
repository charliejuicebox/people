<?php

return [
    // Perth (AWST) and Bali (WITA) are both UTC+8, so one schedule covers the group.
    'timezone' => env('PEOPLE_TIMEZONE', 'Australia/Perth'),

    // Reminders only go out in business hours (right to disconnect).
    'business_hours' => ['start' => 8, 'end' => 17],

    // Days before quarter end when a missing review starts being nudged.
    'review_reminder_days_before_quarter_end' => (int) env('PEOPLE_REVIEW_REMINDER_DAYS', 21),

    'slack' => [
        'bot_token' => env('SLACK_BOT_TOKEN'),
        'praise_channel' => env('SLACK_PRAISE_CHANNEL', '#juicebox'),
    ],

    // Retention (decided 16 Oct 2026): formal records = employment + 7 years, feedback notes = 2 years.
    'retention' => [
        'feedback_years' => 2,
        'formal_years_after_leaving' => 7,
    ],

    // Local-only sign-in for demos with dummy data. Never enabled outside APP_ENV=local.
    'dev_login' => env('PEOPLE_DEV_LOGIN', false),
];
