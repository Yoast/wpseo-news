<?php
/**
 * Yoast SEO: News.
 *
 * @package WPSEO_News
 *
 * @wordpress-plugin
 * Plugin Name: Yoast SEO: News
 * Version:     8.3-RC2
 * Plugin URI:  https://yoast.com/wordpress/plugins/news-seo/#utm_source=wpadmin&utm_medium=plugin&utm_campaign=wpseonewsplugin
 * Description: Google News plugin for the Yoast SEO plugin
 * Author:      Team Yoast
 * Author URI:  http://yoast.com/
 * Text Domain: wpseo_news
 * Domain Path: /languages/
 * Depends:     Yoast SEO
 * License:     GPL v3
 *
 * Yoast SEO Plugin
 * Copyright (C) 2008-2018, Team Yoast
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

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload_52.php' ) ) {
	require dirname( __FILE__ ) . '/vendor/autoload_52.php';
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
 */
// @codingStandardsIgnoreLine
function __wpseo_news_main() {
	new WPSEO_News();
}
add_action( 'plugins_loaded', '__wpseo_news_main' );

/**
 * Clear the news sitemap.
 */
function yoast_wpseo_news_clear_sitemap_cache() {
	if ( class_exists( 'WPSEO_Sitemaps_Cache' ) && method_exists( 'WPSEO_Sitemaps_Cache', 'clear' ) ) {
		WPSEO_Sitemaps_Cache::clear( array( WPSEO_News_Sitemap::get_sitemap_name() ) );
	}
}

/**
 * Clear the news sitemap when we activate the plugin.
 */
function yoast_wpseo_news_activate() {
	yoast_wpseo_news_clear_sitemap_cache();
}

/**
 * Clear the news sitemap when we activate the plugin.
 */
function yoast_wpseo_news_deactivate() {
	yoast_wpseo_news_clear_sitemap_cache();
}

/**
 * Activate the license automatically.
 */
function wpseo_news_activate_license() {
	if ( ! class_exists( 'Yoast_Plugin_License_Manager' ) ) {
		return;
	}

	$license_manager = new Yoast_Plugin_License_Manager( new WPSEO_News_Product() );
	$license_manager->activate_license();
}

register_activation_hook( WPSEO_NEWS_FILE, 'yoast_wpseo_news_activate' );

register_deactivation_hook( WPSEO_NEWS_FILE, 'yoast_wpseo_news_deactivate' );

/*
 * When the plugin is deactivated and activated again, the license has to be activated. This is mostly the case
 * during an update of the plugin. To solve this, we hook into the activation process by calling a method that will
 * activate the license.
 */
register_activation_hook( WPSEO_NEWS_FILE, 'wpseo_news_activate_license' );
