<?php
/**
 * Plugin Name:       Mosaicora
 * Plugin URI:        https://mosaicora.io/integrations/wordpress
 * Description:       Add reliable Mosaicora social images and guided page-level Open Graph overrides to WordPress.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Author:            Mosaicora
 * Author URI:        https://mosaicora.io
 * License:           Apache-2.0
 * License URI:       https://www.apache.org/licenses/LICENSE-2.0
 * Text Domain:       mosaicora
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('MOSAICORA_WORDPRESS_VERSION', '1.0.0');
define('MOSAICORA_WORDPRESS_FILE', __FILE__);
define('MOSAICORA_WORDPRESS_DIR', plugin_dir_path(__FILE__));
define('MOSAICORA_WORDPRESS_URL', plugin_dir_url(__FILE__));

$mosaicoraAutoloader = MOSAICORA_WORDPRESS_DIR . 'vendor/autoload.php';
if (!is_readable($mosaicoraAutoloader)) {
    add_action('admin_notices', static function (): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Mosaicora could not start because its packaged dependencies are missing. Please reinstall the official plugin ZIP.', 'mosaicora');
        echo '</p></div>';
    });

    return;
}

require $mosaicoraAutoloader;

register_activation_hook(__FILE__, [\Mosaicora\WordPress\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [\Mosaicora\WordPress\Plugin::class, 'deactivate']);

\Mosaicora\WordPress\Plugin::boot();
