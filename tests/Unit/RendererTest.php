<?php
declare(strict_types=1);

namespace Mosaicora\WordPress\Tests\Unit;

use Mosaicora\WordPress\OverrideRepository;
use Mosaicora\WordPress\Renderer;
use Mosaicora\WordPress\Settings;
use PHPUnit\Framework\TestCase;

final class RendererTest extends TestCase
{
    protected function setUp(): void
    {
        mosaicora_test_reset();
        $GLOBALS['mosaicora_test']['options'][Settings::OPTION_NAME] = [
            'enabled' => true,
            'site_id' => 'site-123',
            'cache_strategy' => 'monthly',
            'manual_revision' => '',
            'cleanup_on_uninstall' => false,
        ];
    }

    public function testRendersSocialMetadataAndSafeV3JsonLd(): void
    {
        $GLOBALS['mosaicora_test']['post_meta'][42][OverrideRepository::OVERRIDE_META_KEY] = json_encode([
            'schemaVersion' => 3,
            'semanticValues' => ['content.title' => 'Exact <Title>'],
        ]);

        ob_start();
        (new Renderer())->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('property="og:image"', $html);
        self::assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
        self::assertStringContainsString('/s/site-123/article.jpg?v=', $html);
        self::assertStringContainsString('id="mosaicora-og-json-ld"', $html);
        self::assertStringContainsString('Exact \\u003cTitle>', $html);
    }

    public function testDoesNotRenderWhenPageIsDisabled(): void
    {
        $GLOBALS['mosaicora_test']['post_meta'][42][OverrideRepository::DISABLED_META_KEY] = '1';

        ob_start();
        (new Renderer())->render();

        self::assertSame('', ob_get_clean());
    }

    public function testPageRevisionOverridesScheduledCacheVersion(): void
    {
        $GLOBALS['mosaicora_test']['post_meta'][42][OverrideRepository::CACHE_VERSION_META_KEY] = 'release-9';

        ob_start();
        (new Renderer())->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('.jpg?v=release-9', $html);
    }
}
