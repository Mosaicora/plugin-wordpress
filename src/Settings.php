<?php
declare(strict_types=1);

namespace Mosaicora\WordPress;

final class Settings
{
    public const OPTION_NAME = 'mosaicora_settings';

    /** @return array{enabled: bool, site_id: string, cache_strategy: string, manual_revision: string, cleanup_on_uninstall: bool} */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'site_id' => '',
            'cache_strategy' => 'monthly',
            'manual_revision' => '',
            'cleanup_on_uninstall' => false,
        ];
    }

    /** @return array{enabled: bool, site_id: string, cache_strategy: string, manual_revision: string, cleanup_on_uninstall: bool} */
    public static function get(): array
    {
        $stored = get_option(self::OPTION_NAME, []);

        return self::normalize(is_array($stored) ? $stored : []);
    }

    /** @param mixed $input */
    public static function sanitize($input): array
    {
        $current = self::get();
        if (!is_array($input)) {
            add_settings_error(
                self::OPTION_NAME,
                'mosaicora_invalid_settings',
                __('Mosaicora settings could not be saved. Please review the fields and try again.', 'mosaicora'),
            );

            return $current;
        }

        $siteId = isset($input['site_id'])
            ? trim(sanitize_text_field(wp_unslash((string) $input['site_id'])))
            : '';
        if ($siteId !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,35}$/D', $siteId) !== 1) {
            add_settings_error(
                self::OPTION_NAME,
                'mosaicora_invalid_site_id',
                __('Enter a valid Mosaicora site ID containing up to 36 letters, numbers, dots, underscores, or hyphens.', 'mosaicora'),
            );
            $siteId = $current['site_id'];
        }

        $strategy = isset($input['cache_strategy'])
            ? sanitize_key(wp_unslash((string) $input['cache_strategy']))
            : 'monthly';
        if (!in_array($strategy, ['stable', 'monthly', 'weekly', 'manual'], true)) {
            $strategy = 'monthly';
        }

        $manualRevision = isset($input['manual_revision'])
            ? self::sanitizeRevision((string) wp_unslash($input['manual_revision']))
            : '';
        if ($strategy === 'manual' && $manualRevision === '') {
            add_settings_error(
                self::OPTION_NAME,
                'mosaicora_missing_revision',
                __('Add a manual revision before selecting manual image refresh.', 'mosaicora'),
            );
            $strategy = 'stable';
        }

        return [
            'enabled' => !empty($input['enabled']),
            'site_id' => $siteId,
            'cache_strategy' => $strategy,
            'manual_revision' => $manualRevision,
            'cleanup_on_uninstall' => !empty($input['cleanup_on_uninstall']),
        ];
    }

    public static function sanitizeRevision(string $value): string
    {
        return substr(trim(sanitize_text_field($value)), 0, 100);
    }

    /** @param array<string, mixed> $value */
    private static function normalize(array $value): array
    {
        $defaults = self::defaults();
        $strategy = isset($value['cache_strategy']) && in_array($value['cache_strategy'], ['stable', 'monthly', 'weekly', 'manual'], true)
            ? $value['cache_strategy']
            : $defaults['cache_strategy'];

        return [
            'enabled' => (bool) ($value['enabled'] ?? $defaults['enabled']),
            'site_id' => is_string($value['site_id'] ?? null) ? $value['site_id'] : '',
            'cache_strategy' => $strategy,
            'manual_revision' => is_string($value['manual_revision'] ?? null) ? $value['manual_revision'] : '',
            'cleanup_on_uninstall' => (bool) ($value['cleanup_on_uninstall'] ?? $defaults['cleanup_on_uninstall']),
        ];
    }
}
