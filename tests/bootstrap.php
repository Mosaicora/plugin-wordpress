<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (!class_exists('WP_Post')) {
    final class WP_Post
    {
        public function __construct(
            public int $ID,
            public string $post_status = 'publish',
        ) {
        }
    }
}

/** @param array<string, mixed> $overrides */
function mosaicora_test_reset(array $overrides = []): void
{
    $GLOBALS['mosaicora_test'] = array_merge([
        'options' => [],
        'post_meta' => [],
        'errors' => [],
        'filters' => [],
        'conditionals' => [
            'admin' => false,
            'feed' => false,
            'preview' => false,
            'search' => false,
            '404' => false,
            'json' => false,
            'front' => false,
            'home' => false,
            'singular' => true,
            'archive' => false,
            'category' => false,
            'tag' => false,
            'tax' => false,
            'author' => false,
            'date' => false,
        ],
        'post_id' => 42,
        'canonical_url' => 'https://example.com/article',
        'paged_url' => 'https://example.com/archive/',
        'title' => 'Example article',
        'description' => 'A useful summary.',
        'nonce_valid' => true,
        'can_edit' => true,
    ], $overrides);
}

mosaicora_test_reset();

function get_option(string $key, mixed $default = false): mixed
{
    return $GLOBALS['mosaicora_test']['options'][$key] ?? $default;
}

function add_option(string $key, mixed $value, string $deprecated = '', bool $autoload = true): bool
{
    unset($deprecated, $autoload);
    $GLOBALS['mosaicora_test']['options'][$key] = $value;
    return true;
}

function add_settings_error(string $setting, string $code, string $message): void
{
    $GLOBALS['mosaicora_test']['errors'][] = compact('setting', 'code', 'message');
}

function sanitize_text_field(string $value): string
{
    return trim(strip_tags($value));
}

function sanitize_key(string $value): string
{
    return preg_replace('/[^a-z0-9_\\-]/', '', strtolower($value)) ?? '';
}

function wp_unslash(mixed $value): mixed
{
    if (is_array($value)) {
        return array_map('wp_unslash', $value);
    }
    return is_string($value) ? stripslashes($value) : $value;
}

function __(string $text, string $domain = 'default'): string
{
    unset($domain);
    return $text;
}

function get_post_meta(int $postId, string $key, bool $single = false): mixed
{
    unset($single);
    return $GLOBALS['mosaicora_test']['post_meta'][$postId][$key] ?? '';
}

function update_post_meta(int $postId, string $key, mixed $value): bool
{
    $GLOBALS['mosaicora_test']['post_meta'][$postId][$key] = $value;
    return true;
}

function delete_post_meta(int $postId, string $key): bool
{
    unset($GLOBALS['mosaicora_test']['post_meta'][$postId][$key]);
    return true;
}

function wp_json_encode(mixed $value, int $flags = 0): string|false
{
    return json_encode($value, $flags);
}

function wp_http_validate_url(string $url): string|false
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
        ? $url
        : false;
}

function esc_url_raw(string $url, ?array $protocols = null): string
{
    unset($protocols);
    return wp_http_validate_url($url) ? $url : '';
}

function is_admin(): bool { return $GLOBALS['mosaicora_test']['conditionals']['admin']; }
function is_feed(): bool { return $GLOBALS['mosaicora_test']['conditionals']['feed']; }
function is_preview(): bool { return $GLOBALS['mosaicora_test']['conditionals']['preview']; }
function is_search(): bool { return $GLOBALS['mosaicora_test']['conditionals']['search']; }
function is_404(): bool { return $GLOBALS['mosaicora_test']['conditionals']['404']; }
function wp_is_json_request(): bool { return $GLOBALS['mosaicora_test']['conditionals']['json']; }
function is_front_page(): bool { return $GLOBALS['mosaicora_test']['conditionals']['front']; }
function is_home(): bool { return $GLOBALS['mosaicora_test']['conditionals']['home']; }
function is_singular(): bool { return $GLOBALS['mosaicora_test']['conditionals']['singular']; }
function is_archive(): bool { return $GLOBALS['mosaicora_test']['conditionals']['archive']; }
function is_category(): bool { return $GLOBALS['mosaicora_test']['conditionals']['category']; }
function is_tag(): bool { return $GLOBALS['mosaicora_test']['conditionals']['tag']; }
function is_tax(): bool { return $GLOBALS['mosaicora_test']['conditionals']['tax']; }
function is_author(): bool { return $GLOBALS['mosaicora_test']['conditionals']['author']; }
function is_date(): bool { return $GLOBALS['mosaicora_test']['conditionals']['date']; }

function get_queried_object_id(): int { return $GLOBALS['mosaicora_test']['post_id']; }
function wp_get_canonical_url(): string|false { return $GLOBALS['mosaicora_test']['canonical_url']; }
function get_permalink(): string { return $GLOBALS['mosaicora_test']['canonical_url']; }
function get_query_var(string $key, mixed $default = ''): mixed
{
    return $key === 'paged' ? 1 : $default;
}
function get_pagenum_link(int $page): string
{
    unset($page);
    return $GLOBALS['mosaicora_test']['paged_url'];
}

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    $filter = $GLOBALS['mosaicora_test']['filters'][$hook] ?? null;
    return is_callable($filter) ? $filter($value, ...$args) : $value;
}

function get_the_title(int $postId = 0): string
{
    unset($postId);
    return $GLOBALS['mosaicora_test']['title'];
}

function wp_get_document_title(): string { return $GLOBALS['mosaicora_test']['title']; }
function get_the_excerpt(int $postId = 0): string
{
    unset($postId);
    return $GLOBALS['mosaicora_test']['description'];
}
function wp_strip_all_tags(string $value): string { return strip_tags($value); }
function esc_url(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_attr(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function wp_verify_nonce(string $nonce, string $action): bool
{
    unset($nonce, $action);
    return $GLOBALS['mosaicora_test']['nonce_valid'];
}
function wp_is_post_revision(int $postId): bool
{
    unset($postId);
    return false;
}
function current_user_can(string $capability, mixed ...$args): bool
{
    unset($capability, $args);
    return $GLOBALS['mosaicora_test']['can_edit'];
}
