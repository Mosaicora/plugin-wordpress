<?php
declare(strict_types=1);

namespace Mosaicora\WordPress\Tests\Unit;

use Mosaicora\WordPress\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        mosaicora_test_reset();
    }

    public function testDefaultsToSafeMonthlyDisabledConfiguration(): void
    {
        self::assertSame([
            'enabled' => false,
            'site_id' => '',
            'cache_strategy' => 'monthly',
            'manual_revision' => '',
            'cleanup_on_uninstall' => false,
        ], Settings::get());
    }

    public function testSanitizesValidSettings(): void
    {
        self::assertSame([
            'enabled' => true,
            'site_id' => 'site_123',
            'cache_strategy' => 'manual',
            'manual_revision' => 'spring-launch',
            'cleanup_on_uninstall' => true,
        ], Settings::sanitize([
            'enabled' => '1',
            'site_id' => ' site_123 ',
            'cache_strategy' => 'manual',
            'manual_revision' => ' spring-launch ',
            'cleanup_on_uninstall' => '1',
        ]));
    }

    public function testRejectsInvalidSiteIdWithoutDestroyingCurrentValue(): void
    {
        $GLOBALS['mosaicora_test']['options'][Settings::OPTION_NAME] = [
            'site_id' => 'existing-site',
            'cache_strategy' => 'monthly',
        ];

        $saved = Settings::sanitize(['site_id' => 'invalid/site', 'cache_strategy' => 'monthly']);

        self::assertSame('existing-site', $saved['site_id']);
        self::assertSame('mosaicora_invalid_site_id', $GLOBALS['mosaicora_test']['errors'][0]['code']);
    }
}
