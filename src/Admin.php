<?php
declare(strict_types=1);

namespace Mosaicora\WordPress;

use Mosaicora\PluginCore\OgImageUrl;
use Mosaicora\PluginCore\OgImageUrlOptions;

final class Admin
{
    private const PAGE_SLUG = 'mosaicora';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_notices', [$this, 'renderCompatibilityNotice']);
        add_filter('plugin_action_links_' . plugin_basename(MOSAICORA_WORDPRESS_FILE), [$this, 'addSettingsLink']);
    }

    public function addPage(): void
    {
        add_options_page(
            __('Mosaicora', 'mosaicora'),
            __('Mosaicora', 'mosaicora'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting('mosaicora_settings_group', Settings::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [Settings::class, 'sanitize'],
            'default' => Settings::defaults(),
        ]);

        add_settings_section(
            'mosaicora_connection',
            __('Connect Mosaicora', 'mosaicora'),
            static function (): void {
                echo '<p>' . esc_html__('Add the site ID from your Mosaicora dashboard. No API token is required.', 'mosaicora') . '</p>';
            },
            self::PAGE_SLUG,
        );

        add_settings_field('mosaicora_enabled', __('Automatic social images', 'mosaicora'), [$this, 'renderEnabledField'], self::PAGE_SLUG, 'mosaicora_connection');
        add_settings_field('mosaicora_site_id', __('Mosaicora site ID', 'mosaicora'), [$this, 'renderSiteIdField'], self::PAGE_SLUG, 'mosaicora_connection');
        add_settings_field('mosaicora_cache', __('Image refresh', 'mosaicora'), [$this, 'renderCacheField'], self::PAGE_SLUG, 'mosaicora_connection');
        add_settings_field('mosaicora_cleanup', __('Data cleanup', 'mosaicora'), [$this, 'renderCleanupField'], self::PAGE_SLUG, 'mosaicora_connection');
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = Settings::get();
        echo '<div class="wrap mosaicora-settings">';
        echo '<h1>' . esc_html__('Mosaicora', 'mosaicora') . '</h1>';
        echo '<p class="description">' . esc_html__('Create polished, consistent social previews for every indexable page on your WordPress site.', 'mosaicora') . '</p>';
        settings_errors(Settings::OPTION_NAME);
        echo '<form action="options.php" method="post">';
        settings_fields('mosaicora_settings_group');
        do_settings_sections(self::PAGE_SLUG);
        submit_button(__('Save Mosaicora settings', 'mosaicora'));
        echo '</form>';
        $this->renderStatus($settings);
        echo '</div>';
    }

    public function renderEnabledField(): void
    {
        $settings = Settings::get();
        printf(
            '<label><input type="checkbox" name="%1$s[enabled]" value="1" %2$s /> %3$s</label>',
            esc_attr(Settings::OPTION_NAME),
            checked($settings['enabled'], true, false),
            esc_html__('Publish Mosaicora Open Graph and X image tags.', 'mosaicora'),
        );
    }

    public function renderSiteIdField(): void
    {
        $settings = Settings::get();
        printf(
            '<input class="regular-text" type="text" maxlength="36" autocomplete="off" name="%1$s[site_id]" value="%2$s" aria-describedby="mosaicora-site-id-help" />',
            esc_attr(Settings::OPTION_NAME),
            esc_attr($settings['site_id']),
        );
        echo '<p id="mosaicora-site-id-help" class="description">';
        echo esc_html__('Find this ID on your site overview in the Mosaicora dashboard.', 'mosaicora');
        echo '</p>';
    }

    public function renderCacheField(): void
    {
        $settings = Settings::get();
        $choices = [
            'monthly' => __('Monthly — recommended', 'mosaicora'),
            'weekly' => __('Weekly', 'mosaicora'),
            'stable' => __('Stable URL', 'mosaicora'),
            'manual' => __('Manual revision', 'mosaicora'),
        ];
        printf('<select id="mosaicora-cache-strategy" name="%s[cache_strategy]">', esc_attr(Settings::OPTION_NAME));
        foreach ($choices as $value => $label) {
            printf('<option value="%1$s" %2$s>%3$s</option>', esc_attr($value), selected($settings['cache_strategy'], $value, false), esc_html($label));
        }
        echo '</select>';
        printf(
            ' <input id="mosaicora-manual-revision" type="text" maxlength="100" name="%1$s[manual_revision]" value="%2$s" placeholder="%3$s" />',
            esc_attr(Settings::OPTION_NAME),
            esc_attr($settings['manual_revision']),
            esc_attr__('For example: spring-launch', 'mosaicora'),
        );
        echo '<p class="description">' . esc_html__('Changing an image URL helps social networks retrieve a newer image the next time they inspect the page. It cannot force an immediate re-scrape.', 'mosaicora') . '</p>';
    }

    public function renderCleanupField(): void
    {
        $settings = Settings::get();
        printf(
            '<label><input type="checkbox" name="%1$s[cleanup_on_uninstall]" value="1" %2$s /> %3$s</label>',
            esc_attr(Settings::OPTION_NAME),
            checked($settings['cleanup_on_uninstall'], true, false),
            esc_html__('Remove Mosaicora settings and page overrides when the plugin is deleted.', 'mosaicora'),
        );
        echo '<p class="description">' . esc_html__('Deactivation always keeps your settings.', 'mosaicora') . '</p>';
    }

    /** @param array{enabled: bool, site_id: string, cache_strategy: string, manual_revision: string} $settings */
    private function renderStatus(array $settings): void
    {
        echo '<section class="mosaicora-status" aria-labelledby="mosaicora-status-title">';
        echo '<h2 id="mosaicora-status-title">' . esc_html__('Setup status', 'mosaicora') . '</h2>';
        if (!$settings['enabled'] || $settings['site_id'] === '') {
            echo '<p>' . esc_html__('Add your site ID and enable automatic social images to complete setup.', 'mosaicora') . '</p>';
            echo '</section>';
            return;
        }

        $options = new OgImageUrlOptions(
            siteId: $settings['site_id'],
            pageHref: home_url('/'),
            cacheBuster: in_array($settings['cache_strategy'], ['monthly', 'weekly'], true) ? $settings['cache_strategy'] : null,
            cacheVersion: $settings['cache_strategy'] === 'manual' && $settings['manual_revision'] !== ''
                ? $settings['manual_revision']
                : null,
        );
        $preview = OgImageUrl::build($options);
        echo '<p class="mosaicora-status__success">' . esc_html__('Mosaicora is ready to publish social image metadata.', 'mosaicora') . '</p>';
        printf('<code class="mosaicora-preview-url">%s</code>', esc_html($preview));
        printf(
            '<img class="mosaicora-preview" src="%1$s" width="%2$d" height="%3$d" alt="%4$s" loading="lazy" />',
            esc_url($preview),
            esc_attr((string) Renderer::IMAGE_WIDTH),
            esc_attr((string) Renderer::IMAGE_HEIGHT),
            esc_attr__('Mosaicora homepage social image preview', 'mosaicora'),
        );
        echo '<p class="description">' . esc_html__('This preview loads from cdn.mosaicora.io and sends the public homepage URL as part of the image path.', 'mosaicora') . '</p>';
        echo '</section>';
    }

    public function renderCompatibilityNotice(): void
    {
        if (!$this->isSettingsPage() || !$this->hasKnownSeoPlugin()) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('Another SEO plugin appears to be active. To avoid duplicate Open Graph image tags, disable its social-image output while Mosaicora automatic social images are enabled.', 'mosaicora');
        echo '</p></div>';
    }

    public function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== 'settings_page_' . self::PAGE_SLUG) {
            return;
        }

        wp_enqueue_style('mosaicora-admin', MOSAICORA_WORDPRESS_URL . 'assets/admin.css', [], MOSAICORA_WORDPRESS_VERSION);
        wp_enqueue_script('mosaicora-settings', MOSAICORA_WORDPRESS_URL . 'assets/settings.js', [], MOSAICORA_WORDPRESS_VERSION, true);
    }

    /** @param list<string> $links @return list<string> */
    public function addSettingsLink(array $links): array
    {
        array_unshift(
            $links,
            '<a href="' . esc_url(admin_url('options-general.php?page=' . self::PAGE_SLUG)) . '">' . esc_html__('Settings', 'mosaicora') . '</a>',
        );

        return $links;
    }

    private function isSettingsPage(): bool
    {
        if (!isset($_GET['page'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing.
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing.
        return sanitize_key(wp_unslash((string) $_GET['page'])) === self::PAGE_SLUG;
    }

    private function hasKnownSeoPlugin(): bool
    {
        return defined('WPSEO_VERSION')
            || defined('RANK_MATH_VERSION')
            || defined('AIOSEO_VERSION')
            || function_exists('aioseo');
    }
}
