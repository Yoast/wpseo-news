<?php
/**
 * Yoast SEO: News.
 *
 * @package WPSEO_News
 *
 * @wordpress-plugin
 * Plugin Name: Yoast SEO: News
 * Version:     12.6
 * Plugin URI:  https://yoast.com/wordpress/plugins/news-seo/#utm_source=wpadmin&utm_medium=plugin&utm_campaign=wpseonewsplugin
 * Description: Google News plugin for the Yoast SEO plugin
 * Author:      Team Yoast
 * Author URI:  http://yoast.com/
 * Text Domain: wordpress-seo-news
 * Domain Path: /languages/
 * Depends:     Yoast SEO
 * License:     GPL v3
 *
 * Yoast SEO Plugin
 * Copyright (C) 2008-2019, Team Yoast
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'WPSEO_NEWS_FILE' ) ) {
	define( 'WPSEO_NEWS_FILE', __FILE__ );
}

define( 'WPSEO_NEWS_VERSION', '12.6' );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}


/**
 * Load text domain.
 */
function wpseo_news_load_textdomain() {
	load_plugin_textdomain( 'wordpress-seo-news', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'wpseo_news_load_textdomain' );

/**
 * Load Yoast SEO: News.
 *
 * @phpcs:disable PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore,WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function name change would be BC-break.
 */
function __wpseo_news_main() {
	new WPSEO_News();
}
// phpcs:enable
add_action( 'plugins_loaded', '__wpseo_news_main' );

/**
 * Clear the news sitemap.
 */
function yoast_wpseo_news_clear_sitemap_cache() {
	if ( class_exists( 'WPSEO_Sitemaps_Cache' ) && method_exists( 'WPSEO_Sitemaps_Cache', 'clear' ) ) {
		WPSEO_Sitemaps_Cache::clear( [ WPSEO_News_Sitemap::get_sitemap_name() ] );
	}
}

/**
 * Removes the News SEO notifications.
 *
 * @return void
 */
function yoast_wpseo_news_remove_notifications() {
	if (
		class_exists( 'Yoast_Notification_Center' ) &&
		method_exists( 'Yoast_Notification_Center', 'get' ) &&
		method_exists( 'Yoast_Notification_Center', 'remove_notification_by_id' )
	) {
		WPSEO_News_Updates_Notification::remove_notification();
	}
}

/**
 * Clear the news sitemap when we activate the plugin.
 */
function yoast_wpseo_news_activate() {
	// Enable tracking.
	if ( class_exists( 'WPSEO_Options' ) && method_exists( 'WPSEO_Options', 'set' ) ) {
		WPSEO_Options::set( 'tracking', true );
	}

	yoast_wpseo_news_clear_sitemap_cache();
}

/**
 * Clear the news sitemap when we activate the plugin.
 */
function yoast_wpseo_news_deactivate() {
	yoast_wpseo_news_clear_sitemap_cache();
	yoast_wpseo_news_remove_notifications();
}

/**
 * Activate the license automatically.
 *
 * @deprecated 10.1
 * @codeCoverageIgnore
 */
function wpseo_news_activate_license() {
	_deprecated_function( __FUNCTION__, '10.1' );
}

register_activation_hook( WPSEO_NEWS_FILE, 'yoast_wpseo_news_activate' );

register_deactivation_hook( WPSEO_NEWS_FILE, 'yoast_wpseo_news_deactivate' );
