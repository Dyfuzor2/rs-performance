<?php

declare(strict_types=1);

return [
    'webhook_key' => env('BLOG_DRAFT_WEBHOOK_KEY', ''),
    'pipeline_key' => env('BLOG_PIPELINE_WEBHOOK_KEY', env('BLOG_DRAFT_WEBHOOK_KEY', '')),
    'telegram_bot_token' => env('BLOG_TELEGRAM_BOT_TOKEN', ''),
    'telegram_chat_id' => env('BLOG_TELEGRAM_CHAT_ID', ''),
    'telegram_webhook_secret' => env('BLOG_TELEGRAM_WEBHOOK_SECRET', ''),
    /** Multimodal Telegram intake — Flash is sufficient and far cheaper than 3.1 Pro. */
    'telegram_vision_model' => env('BLOG_TELEGRAM_VISION_MODEL', 'gemini-2.5-flash'),
    'default_related_service_slug' => env('BLOG_DEFAULT_SERVICE_SLUG', 'diagnostyka-komputerowa'),
    'default_category' => env('BLOG_DEFAULT_CATEGORY', 'Aktualnosci motoryzacyjne'),
    'auto_generate_image' => (bool) env('BLOG_AUTO_GENERATE_IMAGE', true),
    'source_first_hero_enabled' => (bool) env('BLOG_SOURCE_FIRST_HERO_ENABLED', true),
    'source_first_hero_slots' => array_values(array_filter(array_map(
        static fn (string $slot): string => trim(strtolower($slot)),
        explode(',', (string) env('BLOG_SOURCE_FIRST_HERO_SLOTS', 'premiera,news'))
    ))),
    'source_first_hero_min_width' => (int) env('BLOG_SOURCE_FIRST_HERO_MIN_WIDTH', 960),
    'source_first_hero_min_height' => (int) env('BLOG_SOURCE_FIRST_HERO_MIN_HEIGHT', 540),
    'source_first_hero_min_bytes' => (int) env('BLOG_SOURCE_FIRST_HERO_MIN_BYTES', 65536),
    /**
     * When true and image_fallback is an imagen-* id, try Imagen before Gemini native image for all editorial modes.
     */
    'prefer_imagen_hero_first' => (bool) env('BLOG_PREFER_IMAGEN_HERO_FIRST', true),
    'premium_review_score_threshold' => (int) env('BLOG_PREMIUM_REVIEW_SCORE_THRESHOLD', 101),
    'rss_sources' => [
        'google_news_moto' => 'https://news.google.com/rss/search?q=motoryzacja%20OR%20samochody%20OR%20warsztat%20when%3A2d&hl=pl&gl=PL&ceid=PL:pl',
        'google_news_ev' => 'https://news.google.com/rss/search?q=elektryki%20OR%20hybrydy%20OR%20EV%20when%3A2d&hl=pl&gl=PL&ceid=PL:pl',
    ],
    /*
     * April 2026+ cost / quality routing (override via .env per stage):
     * - Flash / Flash-Lite: cheap classification, SEO, fallbacks.
     * - 2.5 Pro: long-form writer — strong quality vs 3.1 Pro preview cost.
     * - Claude Sonnet: premium editorial review — Opus only via env for rare cases.
     */
    'vertex_models' => [
        'researcher' => env('BLOG_VERTEX_RESEARCHER_MODEL', 'gemini-2.5-flash'),
        'researcher_fallback' => env('BLOG_VERTEX_RESEARCHER_FALLBACK_MODEL', 'gemini-2.5-flash-lite'),
        'selector' => env('BLOG_VERTEX_SELECTOR_MODEL', 'gemini-2.5-flash-lite'),
        'writer' => env('BLOG_VERTEX_WRITER_MODEL', 'gemini-2.5-pro'),
        'writer_fallback' => env('BLOG_VERTEX_WRITER_FALLBACK_MODEL', 'gemini-2.5-flash-lite'),
        'premium_reviewer' => env('BLOG_VERTEX_PREMIUM_REVIEWER_MODEL', 'claude-sonnet-4-6'),
        'premium_reviewer_fallback' => env('BLOG_VERTEX_PREMIUM_REVIEWER_FALLBACK_MODEL', env('BLOG_VERTEX_WRITER_FALLBACK_MODEL', 'gemini-2.5-flash-lite')),
        'seo' => env('BLOG_VERTEX_SEO_MODEL', 'gemini-2.5-flash-lite'),
        'seo_fallback' => env('BLOG_VERTEX_SEO_FALLBACK_MODEL', env('BLOG_VERTEX_WRITER_FALLBACK_MODEL', 'gemini-2.5-flash-lite')),
        // Gemini native image (fallback when Imagen fails). Primary hero path = Imagen 4 when prefer_imagen_hero_first is true — see generateHeroImage().
        'image' => env('BLOG_VERTEX_IMAGE_MODEL', 'gemini-3.1-flash-image-preview'),
        'image_fallback' => env('BLOG_VERTEX_IMAGE_FALLBACK_MODEL', 'imagen-4.0-generate-001'),
    ],
];
