<?php

declare(strict_types=1);

test('blog vertex defaults balance cost and quality (apr 2026 routing)', function (): void {
    expect(config('blog.php_cli_binary'))->toBe('php85');
    expect(config('blog.prefer_imagen_hero_first'))->toBeTrue();
    expect(config('blog.telegram_vision_model'))->toBe('gemini-2.5-flash');
    expect(config('blog.vertex_models.researcher'))->toBe('gemini-2.5-flash');
    expect(config('blog.vertex_models.researcher_fallback'))->toBe('gemini-2.5-flash-lite');
    expect(config('blog.vertex_models.selector'))->toBe('gemini-2.5-flash-lite');
    expect(config('blog.vertex_models.writer'))->toBe('gemini-2.5-pro');
    expect(config('blog.vertex_models.writer_fallback'))->toBe('gemini-2.5-flash-lite');
    expect(config('blog.vertex_models.premium_reviewer'))->toBe('claude-sonnet-4-6');
    expect(config('blog.vertex_models.seo'))->toBe('gemini-2.5-flash-lite');
    expect(config('blog.vertex_models.seo_fallback'))->toBe('gemini-2.5-flash-lite');
    expect(config('blog.vertex_models.image'))->toBe('gemini-3.1-flash-image-preview');
    expect(config('blog.vertex_models.image_fallback'))->toBe('imagen-4.0-generate-001');
});
