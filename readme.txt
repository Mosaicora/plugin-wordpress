=== Mosaicora ===
Contributors: mosaicora
Tags: open graph, social media, og image, seo, twitter card
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Publish reliable Mosaicora social images and guided page-level Open Graph overrides from WordPress.

== Description ==

Mosaicora adds consistent Open Graph and X image metadata to indexable WordPress
pages. Connect the plugin with the public site ID from your Mosaicora dashboard.
No API token is required.

Features:

* Automatic 1200 by 630 JPEG metadata for the homepage, posts, pages, public custom post types, archives, and taxonomies.
* Monthly image URL refresh by default, plus weekly, stable, manual, and per-page revision options.
* Guided page-level template and semantic-value overrides for the Mosaicora v3 contract.
* A single editor panel that works with the block and classic editors.
* Safe defaults, capability checks, nonces, input validation, escaped output, and no background jobs.
* Individual-site Multisite activation so each subsite keeps its own Mosaicora site ID.

Mosaicora publishes standalone Open Graph metadata. If another SEO plugin also
publishes social-image tags, disable that plugin's social-image output to avoid
duplicates.

= External service =

Generated images are served by the Mosaicora CDN at `cdn.mosaicora.io`. Browsers
and social platforms send the public Mosaicora site ID and public page URL as
part of the image request. Opening the preview on the settings page makes the
same request. The plugin does not send API credentials, private post content, or
analytics events.

Use of Mosaicora is subject to:

* Terms: https://mosaicora.io/terms
* Privacy policy: https://mosaicora.io/privacy

== Installation ==

1. Install and activate Mosaicora.
2. Open Settings > Mosaicora.
3. Paste the site ID from your Mosaicora dashboard.
4. Enable automatic social images and save.
5. Optional: edit a post or page and use the Mosaicora social image panel for exact values.

== Frequently Asked Questions ==

= Does the plugin store an API token? =

No. Version 1 uses only the public Mosaicora site ID.

= Will it replace another SEO plugin's Open Graph tags? =

No. Mosaicora is standalone and does not alter vendor-specific SEO plugin
output. The settings screen warns when a commonly used SEO plugin appears to be
active.

= Which pages are excluded? =

Admin screens, REST responses, feeds, previews, search results, and 404 pages do
not receive Mosaicora metadata.

= Does changing the image URL immediately refresh social previews? =

No. A changed URL helps a platform fetch a newer image when it inspects the page
again, but cannot force an immediate re-scrape.

= What happens when I uninstall the plugin? =

Data is preserved by default. Enable "Remove Mosaicora data on uninstall" before
deleting the plugin if you want its settings and page overrides removed.

== Changelog ==

= 1.0.0 =

* Initial WordPress.org-ready release.
