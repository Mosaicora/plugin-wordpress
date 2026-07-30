<?php
/**
 * Remove Mosaicora data only when a site administrator opted in.
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean the current site's Mosaicora data if cleanup was enabled.
 */
function mosaicora_uninstall_current_site(): void
{
    $settings = get_option('mosaicora_settings', []);
    if (!is_array($settings) || empty($settings['cleanup_on_uninstall'])) {
        return;
    }

    delete_option('mosaicora_settings');
    delete_post_meta_by_key('_mosaicora_og_disabled');
    delete_post_meta_by_key('_mosaicora_og_cache_version');
    delete_post_meta_by_key('_mosaicora_og_override_v3');
}

if (is_multisite()) {
    $mosaicoraSiteIds = get_sites(['fields' => 'ids', 'number' => 0]);
    foreach ($mosaicoraSiteIds as $mosaicoraSiteId) {
        switch_to_blog((int) $mosaicoraSiteId);
        mosaicora_uninstall_current_site();
        restore_current_blog();
    }
} else {
    mosaicora_uninstall_current_site();
}
