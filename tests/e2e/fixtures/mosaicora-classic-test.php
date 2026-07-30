<?php
/**
 * Test-only helper for exercising the legacy editor in wp-env.
 */

add_filter('use_block_editor_for_post', static function (bool $useBlockEditor): bool {
    return isset($_GET['mosaicora_classic']) ? false : $useBlockEditor;
});
