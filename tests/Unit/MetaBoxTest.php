<?php
declare(strict_types=1);

namespace Mosaicora\WordPress\Tests\Unit;

use Mosaicora\WordPress\MetaBox;
use Mosaicora\WordPress\OverrideRepository;
use PHPUnit\Framework\TestCase;
use WP_Post;

final class MetaBoxTest extends TestCase
{
    protected function setUp(): void
    {
        mosaicora_test_reset();
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
    }

    public function testSavesValidatedGuidedOverrides(): void
    {
        $_POST = [
            'mosaicora_meta_box_nonce' => 'valid',
            'mosaicora_cache_version' => ' release-2 ',
            'mosaicora_template_id' => 'template-123',
            'mosaicora_semantic_role' => [
                'content.title',
                'product.features',
                'social.verified',
                'analytics.metrics',
                'unknown.value',
            ],
            'mosaicora_semantic_value' => [
                ' Exact title ',
                "Fast\nReliable",
                '1',
                "sales | Sales | 120\ninvalid",
                'discarded',
            ],
        ];

        (new MetaBox())->save(42, new WP_Post(42));
        $saved = json_decode(
            $GLOBALS['mosaicora_test']['post_meta'][42][OverrideRepository::OVERRIDE_META_KEY],
            true,
        );

        self::assertSame('release-2', $GLOBALS['mosaicora_test']['post_meta'][42][OverrideRepository::CACHE_VERSION_META_KEY]);
        self::assertSame('template-123', $saved['templateId']);
        self::assertSame([
            'content.title' => 'Exact title',
            'product.features' => ['Fast', 'Reliable'],
            'social.verified' => true,
            'analytics.metrics' => [
                ['id' => 'sales', 'label' => 'Sales', 'value' => '120'],
            ],
        ], $saved['semanticValues']);
    }

    public function testRejectsSaveWithoutCapability(): void
    {
        $GLOBALS['mosaicora_test']['can_edit'] = false;
        $_POST = [
            'mosaicora_meta_box_nonce' => 'valid',
            'mosaicora_og_disabled' => '1',
        ];

        (new MetaBox())->save(42, new WP_Post(42));

        self::assertSame([], $GLOBALS['mosaicora_test']['post_meta']);
    }
}
