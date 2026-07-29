<?php
declare(strict_types=1);

namespace Mosaicora\WordPress;

final class Plugin
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;
        add_action('wp_head', [new Renderer(), 'render'], 5);

        if (is_admin()) {
            (new Admin())->register();
            (new MetaBox())->register();
        }
    }

    public static function activate(bool $networkWide = false): void
    {
        if (is_multisite() && $networkWide) {
            deactivate_plugins(plugin_basename(MOSAICORA_WORDPRESS_FILE), true, true);
            wp_die(
                esc_html__('Mosaicora must be activated separately on each site so every site can use its own Mosaicora site ID.', 'mosaicora'),
                esc_html__('Per-site activation required', 'mosaicora'),
                ['back_link' => true],
            );
        }

        if (get_option(Settings::OPTION_NAME, null) === null) {
            add_option(Settings::OPTION_NAME, Settings::defaults(), '', false);
        }
    }

    public static function deactivate(): void
    {
        // Settings and page overrides intentionally remain available.
    }
}
