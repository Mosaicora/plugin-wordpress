<?php
declare(strict_types=1);

namespace Mosaicora\WordPress;

use InvalidArgumentException;
use JsonException;
use Mosaicora\PluginCore\MosaicoraOgJsonLd;
use Mosaicora\PluginCore\MosaicoraOgJsonLdOptions;
use Mosaicora\PluginCore\MosaicoraOgOverride;
use Mosaicora\PluginCore\OgImageUrl;
use Mosaicora\PluginCore\OgImageUrlOptions;

final class Renderer
{
    public const IMAGE_WIDTH = 1200;
    public const IMAGE_HEIGHT = 630;
    public const IMAGE_TYPE = 'image/jpeg';

    public function __construct(
        private readonly UrlResolver $urlResolver = new UrlResolver(),
        private readonly OverrideRepository $overrides = new OverrideRepository(),
    ) {
    }

    public function render(): void
    {
        $settings = Settings::get();
        if (!$settings['enabled'] || $settings['site_id'] === '' || !$this->urlResolver->isEligible()) {
            return;
        }

        $postId = is_singular() ? get_queried_object_id() : 0;
        if ($postId > 0 && $this->overrides->isDisabled($postId)) {
            return;
        }

        $shouldRender = (bool) apply_filters('mosaicora_og_should_render', true, $postId);
        if (!$shouldRender) {
            return;
        }

        $pageUrl = $this->urlResolver->resolve();
        if ($pageUrl === null) {
            return;
        }

        $options = $this->buildImageOptions($settings, $pageUrl, $postId);
        /** @var OgImageUrlOptions $options */
        $options = apply_filters('mosaicora_og_image_url_options', $options, $postId);
        if (!$options instanceof OgImageUrlOptions) {
            return;
        }

        try {
            $imageUrl = OgImageUrl::build($options);
        } catch (InvalidArgumentException) {
            return;
        }

        $override = $postId > 0 ? $this->overrides->get($postId) : null;
        $alt = $this->resolveAltText($override, $postId);
        /** @var string $alt */
        $alt = apply_filters('mosaicora_og_image_alt', $alt, $postId, $pageUrl);

        $this->renderMetaTags($imageUrl, is_string($alt) ? $alt : '');
        if ($postId > 0 && $override !== null) {
            $this->renderJsonLd($override, $postId, $pageUrl);
        }
    }

    /**
     * @param array{cache_strategy: string, manual_revision: string, site_id: string} $settings
     */
    private function buildImageOptions(array $settings, string $pageUrl, int $postId): OgImageUrlOptions
    {
        $pageCacheVersion = $postId > 0 ? $this->overrides->getCacheVersion($postId) : null;
        $cacheVersion = $pageCacheVersion;
        $cacheBuster = null;

        if ($cacheVersion === null) {
            if ($settings['cache_strategy'] === 'manual' && $settings['manual_revision'] !== '') {
                $cacheVersion = $settings['manual_revision'];
            } elseif (in_array($settings['cache_strategy'], ['monthly', 'weekly'], true)) {
                $cacheBuster = $settings['cache_strategy'];
            }
        }

        return new OgImageUrlOptions(
            siteId: $settings['site_id'],
            pageHref: $pageUrl,
            cacheBuster: $cacheBuster,
            cacheVersion: $cacheVersion,
        );
    }

    private function renderMetaTags(string $imageUrl, string $alt): void
    {
        echo "\n<!-- Mosaicora Open Graph -->\n";
        printf("<meta property=\"og:image\" content=\"%s\" />\n", esc_url($imageUrl));
        printf("<meta property=\"og:image:secure_url\" content=\"%s\" />\n", esc_url($imageUrl));
        printf("<meta property=\"og:image:width\" content=\"%s\" />\n", esc_attr((string) self::IMAGE_WIDTH));
        printf("<meta property=\"og:image:height\" content=\"%s\" />\n", esc_attr((string) self::IMAGE_HEIGHT));
        printf("<meta property=\"og:image:type\" content=\"%s\" />\n", esc_attr(self::IMAGE_TYPE));
        if ($alt !== '') {
            printf("<meta property=\"og:image:alt\" content=\"%s\" />\n", esc_attr($alt));
        }
        echo "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
        printf("<meta name=\"twitter:image\" content=\"%s\" />\n", esc_url($imageUrl));
        if ($alt !== '') {
            printf("<meta name=\"twitter:image:alt\" content=\"%s\" />\n", esc_attr($alt));
        }
        echo "<!-- /Mosaicora Open Graph -->\n";
    }

    /** @param array{schemaVersion: 3, templateId?: string, semanticValues: array<string, mixed>} $override */
    private function renderJsonLd(array $override, int $postId, string $pageUrl): void
    {
        /** @var array<string, mixed> $filtered */
        $filtered = apply_filters('mosaicora_og_override', $override, $postId, $pageUrl);
        if (!is_array($filtered) || !isset($filtered['semanticValues']) || !is_array($filtered['semanticValues'])) {
            return;
        }

        $templateId = isset($filtered['templateId']) && is_string($filtered['templateId'])
            ? $filtered['templateId']
            : null;
        $jsonLd = MosaicoraOgJsonLd::build(new MosaicoraOgJsonLdOptions(
            schemaType: 'WebPage',
            name: get_the_title($postId),
            description: $this->postDescription($postId),
            url: $pageUrl,
            mosaicoraOg: new MosaicoraOgOverride(
                semanticValues: $filtered['semanticValues'],
                templateId: $templateId,
            ),
        ));

        try {
            $serialized = MosaicoraOgJsonLd::serialize($jsonLd);
        } catch (JsonException) {
            return;
        }

        printf(
            "<script id=\"mosaicora-og-json-ld\" type=\"application/ld+json\">%s</script>\n",
            $serialized, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core serializer escapes HTML script-breaking characters.
        );
    }

    /** @param array{semanticValues: array<string, mixed>}|null $override */
    private function resolveAltText(?array $override, int $postId): string
    {
        $title = $override['semanticValues']['content.title'] ?? null;
        if (is_string($title) && $title !== '') {
            return $title;
        }

        if ($postId > 0) {
            return wp_strip_all_tags(get_the_title($postId));
        }

        return wp_strip_all_tags(wp_get_document_title());
    }

    private function postDescription(int $postId): ?string
    {
        $description = get_the_excerpt($postId);
        if (!is_string($description) || trim($description) === '') {
            return null;
        }

        return wp_strip_all_tags($description);
    }
}
