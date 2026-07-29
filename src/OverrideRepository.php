<?php
declare(strict_types=1);

namespace Mosaicora\WordPress;

use Mosaicora\PluginCore\MosaicoraOgSemanticRoles;

final class OverrideRepository
{
    public const DISABLED_META_KEY = '_mosaicora_og_disabled';
    public const CACHE_VERSION_META_KEY = '_mosaicora_og_cache_version';
    public const OVERRIDE_META_KEY = '_mosaicora_og_override_v3';

    /** @return array{schemaVersion: 3, templateId?: string, semanticValues: array<string, mixed>}|null */
    public function get(int $postId): ?array
    {
        $raw = get_post_meta($postId, self::OVERRIDE_META_KEY, true);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || ($decoded['schemaVersion'] ?? null) !== 3) {
            return null;
        }

        $values = [];
        foreach (($decoded['semanticValues'] ?? []) as $role => $value) {
            if (is_string($role) && MosaicoraOgSemanticRoles::acceptsValue($role, $value)) {
                $values[$role] = $value;
            }
        }

        $result = ['schemaVersion' => 3, 'semanticValues' => $values];
        $templateId = $decoded['templateId'] ?? null;
        if (is_string($templateId) && self::isValidIdentifier($templateId)) {
            $result['templateId'] = $templateId;
        }

        return $result;
    }

    public function isDisabled(int $postId): bool
    {
        return get_post_meta($postId, self::DISABLED_META_KEY, true) === '1';
    }

    public function getCacheVersion(int $postId): ?string
    {
        $value = get_post_meta($postId, self::CACHE_VERSION_META_KEY, true);

        return is_string($value) && $value !== '' ? Settings::sanitizeRevision($value) : null;
    }

    public static function isValidIdentifier(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,35}$/D', $value) === 1;
    }
}
