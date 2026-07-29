<?php
declare(strict_types=1);

namespace Mosaicora\WordPress;

final class UrlResolver
{
    public function isEligible(): bool
    {
        if (
            is_admin()
            || is_feed()
            || is_preview()
            || is_search()
            || is_404()
            || (function_exists('wp_is_json_request') && wp_is_json_request())
        ) {
            return false;
        }

        return is_front_page()
            || is_home()
            || is_singular()
            || is_archive()
            || is_category()
            || is_tag()
            || is_tax()
            || is_author()
            || is_date();
    }

    public function resolve(): ?string
    {
        $url = null;
        if (is_singular()) {
            $canonical = wp_get_canonical_url();
            $url = is_string($canonical) && $canonical !== '' ? $canonical : get_permalink();
        } else {
            $page = max(1, (int) get_query_var('paged', 1));
            $url = get_pagenum_link($page);
        }

        if (!is_string($url) || !wp_http_validate_url($url)) {
            return null;
        }

        /** @var string|null $filtered */
        $filtered = apply_filters('mosaicora_og_page_url', $url);

        return is_string($filtered) && wp_http_validate_url($filtered) ? $filtered : null;
    }
}
