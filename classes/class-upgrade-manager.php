<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Represents the update routine when a newer version has been installed.
 */
class WPSEO_News_Upgrade_Manager {

	/**
	 * Check if there's a plugin update.
	 */
	public function check_update() {
		// Get options.
		$options = WPSEO_News::get_options();

		// Check if update is required.
		if ( version_compare( WPSEO_News::VERSION, $options['version'], '>' ) ) {
			// Do update.
			$this->do_update( $options['version'] );

			// Update version code.
			$this->update_current_version_code();
		}
	}

	/**
	 * An update is required, do it.
	 *
	 * @param string $current_version The current version.
	 */
	private function do_update( $current_version ) {
		// Update to version 2.0.
		if ( version_compare( $current_version, '2.0', '<' ) ) {
			$this->upgrade_20();
		}

		// Upgrade to version 2.0.4.
		if ( version_compare( $current_version, '2.0.4', '<' ) ) {
			$this->upgrade_204();
		}

		// Upgrade to version 7.8.
		if ( version_compare( $current_version, '7.8', '<' ) ) {
			$this->upgrade_78();
		}

		// Upgrade to version 8.3
		if ( version_compare( $current_version, '8.3', '<' ) ) {
			$this->upgrade_83();
		}
	}

	/**
	 * Update the current version code.
	 */
	private function update_current_version_code() {
		$options            = WPSEO_News::get_options();
		$options['version'] = WPSEO_News::VERSION;
		update_option( 'wpseo_news', $options );
	}

	/**
	 * Perform the upgrade to 2.0.
	 */
	private function upgrade_20() {
		// Get current options.
		$current_options = get_option( 'wpseo_news' );

		// Set new options.
		$new_options = array(
			'name'          => ( ( isset( $current_options['newssitemapname'] ) ) ? $current_options['newssitemapname'] : '' ),
			'default_genre' => ( ( isset( $current_options['newssitemap_default_genre'] ) ) ? $current_options['newssitemap_default_genre'] : '' ),
		);

		// Save new options.
		update_option( 'wpseo_news', $new_options );
	}

	/**
	 * Perform the upgrade to 2.0.4.
	 */
	private function upgrade_204() {
		// Remove unused option.
		$news_options = WPSEO_News::get_options();
		unset( $news_options['ep_image_title'] );

		// Update options.
		update_option( 'wpseo_news', $news_options );

		// Reset variable.
		$news_options = null;
	}

	/**
	 * Perform the upgrade to 7.8.
	 */
	private function upgrade_78() {
		// Delete all standout tags. Functionality was deleted in 7.7, data only deleted in 7.8.
		$this->delete_meta_by_key( '_yoast_wpseo_newssitemap-standout' );

		// Delete all editors picks settings.
		$this->delete_meta_by_key( '_yoast_wpseo_newssitemap-editors-pick' );

		// Delete all original source references.
		$this->delete_meta_by_key( '_yoast_wpseo_newssitemap-original' );
	}

	/**
	 * Perform the upgrade to 8.3.
	 */
	private function upgrade_83() {
		// Get current options.
		$options = get_option( 'wpseo_news' );
		foreach( $options as $key => $value ) {
			if ( strpos( $key, 'catexclude_' ) === 0 ) {
				$slug                                        = str_replace( 'catexclude_', '', $key );
				$options[ 'term_exclude_category_' . $slug ] = $value;
				unset( $options[ $key ] );
			}
		}

		// Update options.
		update_option( 'wpseo_news', $options );
	}

	/**
	 * Deletes post meta fields by key.
	 *
	 * @param string $key The key to delete post meta fields for.
	 *
	 * @link https://codex.wordpress.org/Class_Reference/wpdb#DELETE_Rows
	 */
	private function delete_meta_by_key( $key ) {
		global $wpdb;
		$wpdb->delete(
			$wpdb->postmeta,
			array(
				'meta_key' => $key,
			),
			array( '%s' )
		);
	}
}
