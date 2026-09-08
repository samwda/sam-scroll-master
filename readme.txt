=== Sam Scroll Master ===
Contributors: samwda, smahjoob
Donate link: https://samwda.ir
Tags: smooth scroll, scroll, JavaScript, simple plugin, cache
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern, secure, and highly configurable smooth scroll plugin for WordPress.

== Description ==

**Sam Scroll Master (SSM)** is a lightweight and modern plugin to enable smooth scrolling across WordPress sites.

**Features include:**
* Smooth scroll on links within the site.
* Enable/disable for frontend and admin.
* Exclude posts, pages, custom post types, or taxonomies.
* Control per user role or guest users.
* Responsive support for desktop, tablet, and mobile.
* Automatic cache purge on settings save (WP Rocket, LiteSpeed Cache, W3 Total Cache, and more).
* Accessibility ready

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to `Settings > Sam Scroll Master` to configure options.
4. Configure frontend activation, user roles, devices, and exclusions as needed.

== Usage ==

The plugin works automatically on selected pages/posts and devices based on the settings:

- Enable smooth scrolling for frontend or admin panel.
- Exclude specific pages, post types, custom post types, or taxonomy terms.
- Filter by user roles or device types (desktop, tablet, mobile).
- Whenever you save the settings, all supported cache plugins are purged automatically.

== Frequently Asked Questions ==

= Can I enable smooth scroll only for certain users? =
Yes. Use the "User Roles" section in the settings panel to control who sees smooth scroll.

= Can I exclude custom post types or taxonomies? =
Absolutely. SSM supports exclusions for any post type or taxonomy, including custom ones.

= Does it work in WordPress admin? =
Yes. You can enable it optionally for WordPress admin panel.

= Does it clear the cache automatically? =
Yes. On every settings save, SSM purges all caches of supported plugins: WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache, WP Fastest Cache, WP Optimize, SG Optimizer, Autoptimize, Hummingbird, Cache Enabler, Breeze, and Swift Performance – so your changes take effect immediately.

== Changelog ==

= 1.2 =
* New: Automatic cache purge on settings save (WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache, WP Fastest Cache, WP Optimize, SG Optimizer, Autoptimize, Hummingbird, Cache Enabler, Breeze, Swift Performance).
* New: `samsm_after_purge_caches` action hook for third-party cache integrations.
* New: uninstall.php – all plugin options are now removed on uninstall.
* Security: AJAX search endpoint is now admin-only (capability check added, nopriv access removed).
* Security: Whitelist validation for user roles, device types, and post types before saving.
* Fix: Smooth scroll now correctly loads for guest visitors when the "Guest" role is selected.
* Fix: Prevented errors when taxonomy term queries return WP_Error.
* Fix: Taxonomy term exclusions now also apply to pages and all single post types.
* Fix: Smooth scroll no longer attaches to empty "#" links or links pointing to other pages.
* Fix: Valid HTML structure in the settings page.
* Performance: Settings page loads only saved selections; remaining posts are fetched via AJAX search.
* Performance: Taxonomy term lists are capped to keep the settings page fast on large sites.
* Dev: Post/Redirect/Get pattern after saving (prevents duplicate submissions on page refresh).
* Dev: All strings are now translatable (text domain: sam-scroll-master).
* Dev: Forced RTL direction removed from admin CSS; layout follows the WordPress admin locale.

= 1.1 =
Accessibility: Respect user's prefers-reduced-motion setting – smooth scroll automatically disabled when reduced motion is requested.
Code review: Full code audit and compatibility testing performed on WordPress 7.

= 1.0 =
Initial public release with settings page.

== Credits ==

Developed by SAM Web Design Agency – https://samwda.ir
This plugin uses SmoothScroll for websites by Balazs Galambosi.

== License ==

This plugin is licensed under the GPLv2 or later.
See https://www.gnu.org/licenses/gpl-2.0.html for details.
