<?php
declare(strict_types=1);

namespace Mosaicora\WordPress;

use Mosaicora\PluginCore\MosaicoraOgSemanticRoles;
use WP_Post;

final class MetaBox
{
    private const NONCE_ACTION = 'mosaicora_save_page_settings';
    private const NONCE_NAME = 'mosaicora_meta_box_nonce';

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMetaBoxes(): void
    {
        $postTypes = get_post_types(['public' => true, 'show_ui' => true], 'names');
        unset($postTypes['attachment']);

        foreach ($postTypes as $postType) {
            add_meta_box(
                'mosaicora-page-settings',
                __('Mosaicora social image', 'mosaicora'),
                [$this, 'render'],
                $postType,
                'normal',
                'default',
                ['__back_compat_meta_box' => false],
            );
        }
    }

    public function render(WP_Post $post): void
    {
        $repository = new OverrideRepository();
        $override = $repository->get($post->ID);
        $values = $override['semanticValues'] ?? [];
        $templateId = $override['templateId'] ?? '';

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        echo '<div class="mosaicora-meta-box">';
        printf(
            '<label><input type="checkbox" name="mosaicora_og_disabled" value="1" %1$s /> %2$s</label>',
            checked($repository->isDisabled($post->ID), true, false),
            esc_html__('Do not publish Mosaicora metadata for this page.', 'mosaicora'),
        );

        echo '<div class="mosaicora-meta-box__grid">';
        echo '<p><label for="mosaicora-cache-version"><strong>' . esc_html__('Page revision', 'mosaicora') . '</strong></label><br />';
        printf(
            '<input id="mosaicora-cache-version" class="widefat" type="text" maxlength="100" name="mosaicora_cache_version" value="%1$s" placeholder="%2$s" />',
            esc_attr($repository->getCacheVersion($post->ID) ?? ''),
            esc_attr__('Optional, for example: product-update-2', 'mosaicora'),
        );
        echo '<span class="description">' . esc_html__('Overrides the site-wide image refresh value for this page.', 'mosaicora') . '</span></p>';

        echo '<p><label for="mosaicora-template-id"><strong>' . esc_html__('Template ID', 'mosaicora') . '</strong></label><br />';
        printf(
            '<input id="mosaicora-template-id" class="widefat" type="text" maxlength="36" name="mosaicora_template_id" value="%1$s" placeholder="%2$s" />',
            esc_attr($templateId),
            esc_attr__('Optional Mosaicora template ID', 'mosaicora'),
        );
        echo '<span class="description">' . esc_html__('Use a saved Mosaicora template for this page.', 'mosaicora') . '</span></p>';
        echo '</div>';

        echo '<hr />';
        echo '<div class="mosaicora-role-builder">';
        echo '<div><h4>' . esc_html__('Exact image values', 'mosaicora') . '</h4>';
        echo '<p class="description">' . esc_html__('Add only values that Mosaicora should use exactly. Values you leave out continue to use automatic page content.', 'mosaicora') . '</p></div>';
        echo '<div id="mosaicora-role-rows" class="mosaicora-role-rows">';
        foreach ($values as $role => $value) {
            $this->renderRoleRow($role, $value);
        }
        echo '</div>';
        echo '<button type="button" class="button" id="mosaicora-add-role">' . esc_html__('Add exact value', 'mosaicora') . '</button>';
        echo '</div>';
        echo '</div>';
    }

    public function enqueueAssets(string $hookSuffix): void
    {
        if (!in_array($hookSuffix, ['post.php', 'post-new.php'], true)) {
            return;
        }

        wp_enqueue_style('mosaicora-admin', MOSAICORA_WORDPRESS_URL . 'assets/admin.css', [], MOSAICORA_WORDPRESS_VERSION);
        wp_enqueue_script('mosaicora-editor', MOSAICORA_WORDPRESS_URL . 'assets/editor.js', [], MOSAICORA_WORDPRESS_VERSION, true);

        $roles = [];
        foreach (MosaicoraOgSemanticRoles::definitions() as $role => $type) {
            $roles[] = [
                'role' => $role,
                'type' => $type,
                'group' => self::groupLabel($role),
                'label' => self::roleLabel($role),
            ];
        }
        wp_localize_script('mosaicora-editor', 'MosaicoraEditor', [
            'roles' => $roles,
            'labels' => [
                'chooseRole' => __('Choose a value', 'mosaicora'),
                'remove' => __('Remove', 'mosaicora'),
                'listHelp' => __('Enter one item per line.', 'mosaicora'),
                'metricsHelp' => __('Enter one metric per line as: ID | Label | Value', 'mosaicora'),
                'yes' => __('Yes', 'mosaicora'),
                'no' => __('No', 'mosaicora'),
            ],
        ]);
    }

    public function save(int $postId, WP_Post $post): void
    {
        if (
            !isset($_POST[self::NONCE_NAME])
            || !wp_verify_nonce(
                sanitize_text_field(wp_unslash((string) $_POST[self::NONCE_NAME])),
                self::NONCE_ACTION,
            )
            || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || wp_is_post_revision($postId)
            || !current_user_can('edit_post', $postId)
            || $post->post_status === 'auto-draft'
        ) {
            return;
        }

        if (isset($_POST['mosaicora_og_disabled'])) {
            update_post_meta($postId, OverrideRepository::DISABLED_META_KEY, '1');
        } else {
            delete_post_meta($postId, OverrideRepository::DISABLED_META_KEY);
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by Settings::sanitizeRevision immediately below.
        $cacheVersion = isset($_POST['mosaicora_cache_version'])
            ? Settings::sanitizeRevision((string) wp_unslash($_POST['mosaicora_cache_version'])) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : '';
        $this->saveOrDelete($postId, OverrideRepository::CACHE_VERSION_META_KEY, $cacheVersion);

        $templateId = isset($_POST['mosaicora_template_id'])
            ? trim(sanitize_text_field(wp_unslash((string) $_POST['mosaicora_template_id'])))
            : '';
        if ($templateId !== '' && !OverrideRepository::isValidIdentifier($templateId)) {
            $templateId = '';
        }

        // Each role and value is un-slashed, type-checked, and sanitized in the loop below.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $roles = isset($_POST['mosaicora_semantic_role']) && is_array($_POST['mosaicora_semantic_role'])
            ? wp_unslash($_POST['mosaicora_semantic_role']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $postedValues = isset($_POST['mosaicora_semantic_value']) && is_array($_POST['mosaicora_semantic_value'])
            ? wp_unslash($_POST['mosaicora_semantic_value']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];
        $semanticValues = [];
        foreach ($roles as $index => $rawRole) {
            if (!is_string($rawRole) || !array_key_exists($index, $postedValues) || !is_string($postedValues[$index])) {
                continue;
            }

            $role = sanitize_text_field($rawRole);
            $value = $this->sanitizeRoleValue($role, $postedValues[$index]);
            if ($value !== null && MosaicoraOgSemanticRoles::acceptsValue($role, $value)) {
                $semanticValues[$role] = $value;
            }
        }

        if ($templateId === '' && $semanticValues === []) {
            delete_post_meta($postId, OverrideRepository::OVERRIDE_META_KEY);
            return;
        }

        $override = ['schemaVersion' => 3, 'semanticValues' => $semanticValues];
        if ($templateId !== '') {
            $override['templateId'] = $templateId;
        }
        update_post_meta(
            $postId,
            OverrideRepository::OVERRIDE_META_KEY,
            wp_json_encode($override, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    private function renderRoleRow(string $role, mixed $value): void
    {
        $type = MosaicoraOgSemanticRoles::typeFor($role);
        if ($type === null) {
            return;
        }

        echo '<div class="mosaicora-role-row">';
        echo '<div class="mosaicora-role-row__heading">';
        echo '<select class="mosaicora-role-select" name="mosaicora_semantic_role[]">';
        $this->renderRoleOptions($role);
        echo '</select>';
        printf('<span class="mosaicora-role-type">%s</span>', esc_html($type));
        echo '<button type="button" class="button-link-delete mosaicora-remove-role">' . esc_html__('Remove', 'mosaicora') . '</button>';
        echo '</div>';
        echo '<div class="mosaicora-role-control">';
        $this->renderValueControl($type, $value);
        echo '</div></div>';
    }

    private function renderRoleOptions(string $selectedRole = ''): void
    {
        $group = null;
        foreach (MosaicoraOgSemanticRoles::definitions() as $role => $type) {
            unset($type);
            $nextGroup = self::groupLabel($role);
            if ($nextGroup !== $group) {
                if ($group !== null) {
                    echo '</optgroup>';
                }
                printf('<optgroup label="%s">', esc_attr($nextGroup));
                $group = $nextGroup;
            }
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($role),
                selected($selectedRole, $role, false),
                esc_html(self::roleLabel($role)),
            );
        }
        if ($group !== null) {
            echo '</optgroup>';
        }
    }

    private function renderValueControl(string $type, mixed $value): void
    {
        if ($type === MosaicoraOgSemanticRoles::TYPE_BOOLEAN) {
            echo '<select class="widefat" name="mosaicora_semantic_value[]">';
            printf('<option value="1" %s>%s</option>', selected($value, true, false), esc_html__('Yes', 'mosaicora'));
            printf('<option value="0" %s>%s</option>', selected($value, false, false), esc_html__('No', 'mosaicora'));
            echo '</select>';
            return;
        }

        $formatted = $this->formatValue($type, $value);
        printf(
            '<textarea class="widefat" rows="%1$d" name="mosaicora_semantic_value[]">%2$s</textarea>',
            $type === MosaicoraOgSemanticRoles::TYPE_TEXT ? 2 : 4,
            esc_textarea($formatted),
        );
        if ($type === MosaicoraOgSemanticRoles::TYPE_LIST) {
            echo '<span class="description">' . esc_html__('Enter one item per line.', 'mosaicora') . '</span>';
        } elseif ($type === MosaicoraOgSemanticRoles::TYPE_METRICS) {
            echo '<span class="description">' . esc_html__('Enter one metric per line as: ID | Label | Value', 'mosaicora') . '</span>';
        }
    }

    private function formatValue(string $type, mixed $value): string
    {
        if ($type === MosaicoraOgSemanticRoles::TYPE_LIST && is_array($value)) {
            return implode("\n", array_filter($value, 'is_string'));
        }
        if ($type === MosaicoraOgSemanticRoles::TYPE_METRICS && is_array($value)) {
            return implode("\n", array_map(
                static fn (array $metric): string => implode(' | ', [$metric['id'], $metric['label'], $metric['value']]),
                $value,
            ));
        }

        return is_string($value) ? $value : '';
    }

    private function sanitizeRoleValue(string $role, string $rawValue): mixed
    {
        $type = MosaicoraOgSemanticRoles::typeFor($role);
        if ($type === MosaicoraOgSemanticRoles::TYPE_BOOLEAN) {
            return $rawValue === '1';
        }

        if ($type === MosaicoraOgSemanticRoles::TYPE_LIST) {
            $values = array_values(array_filter(array_map(
                fn (string $entry): string => $this->sanitizeTextOrUrl($role, $entry),
                preg_split('/\R/', $rawValue) ?: [],
            ), static fn (string $entry): bool => $entry !== ''));

            return $values === [] ? null : $values;
        }

        if ($type === MosaicoraOgSemanticRoles::TYPE_METRICS) {
            $metrics = [];
            foreach (preg_split('/\R/', $rawValue) ?: [] as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) !== 3) {
                    continue;
                }
                [$id, $label, $value] = array_map('sanitize_text_field', $parts);
                if ($id !== '' && $label !== '' && $value !== '') {
                    $metrics[] = ['id' => $id, 'label' => $label, 'value' => $value];
                }
            }

            return $metrics === [] ? null : $metrics;
        }

        if ($type === MosaicoraOgSemanticRoles::TYPE_TEXT) {
            $value = $this->sanitizeTextOrUrl($role, $rawValue);

            return $value === '' ? null : $value;
        }

        return null;
    }

    private function sanitizeTextOrUrl(string $role, string $value): string
    {
        $value = trim($value);
        $urlRoles = ['content.url', 'person.image', 'image.primary', 'image.secondary'];
        if (in_array($role, $urlRoles, true)) {
            $url = esc_url_raw($value, ['http', 'https']);

            return wp_http_validate_url($url) ? $url : '';
        }

        return substr(sanitize_text_field($value), 0, 2000);
    }

    private function saveOrDelete(int $postId, string $key, string $value): void
    {
        if ($value === '') {
            delete_post_meta($postId, $key);
        } else {
            update_post_meta($postId, $key, $value);
        }
    }

    private static function groupLabel(string $role): string
    {
        $group = explode('.', $role, 2)[0];

        return ucwords((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $group));
    }

    private static function roleLabel(string $role): string
    {
        $name = explode('.', $role, 2)[1] ?? $role;

        return ucwords((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $name));
    }
}
