<?php
declare(strict_types=1);

namespace Mosaicora\WordPress\Tests\Unit;

use Mosaicora\WordPress\OverrideRepository;
use PHPUnit\Framework\TestCase;

final class OverrideRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        mosaicora_test_reset();
    }

    public function testReadsOnlySupportedTypedValues(): void
    {
        $GLOBALS['mosaicora_test']['post_meta'][42][OverrideRepository::OVERRIDE_META_KEY] = json_encode([
            'schemaVersion' => 3,
            'templateId' => 'template-123',
            'semanticValues' => [
                'content.title' => 'Exact title',
                'social.verified' => true,
                'unknown.secret' => 'discarded',
                'product.features' => 'wrong type',
            ],
        ]);

        self::assertSame([
            'schemaVersion' => 3,
            'semanticValues' => [
                'content.title' => 'Exact title',
                'social.verified' => true,
            ],
            'templateId' => 'template-123',
        ], (new OverrideRepository())->get(42));
    }

    public function testRejectsLegacySchemaVersion(): void
    {
        $GLOBALS['mosaicora_test']['post_meta'][42][OverrideRepository::OVERRIDE_META_KEY] = '{"schemaVersion":2,"semanticValues":{}}';

        self::assertNull((new OverrideRepository())->get(42));
    }
}
