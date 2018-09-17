<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News
 */

/**
 * Represents the news extension for Yoast SEO.
 */
class WPSEO_News {

	const VERSION = '8.3-RC2';

	/**
	 * Get WPSEO News options.
	 *
	 * @return array
	 */
	public static function get_options() {
		/**
		 * Filter: 'wpseo_news_options' - Allow modifying of Yoast News SEO options.
		 *
		 * @api array $wpseo_news_options The Yoast News SEO options.
		 */
		return apply_filters( 'wpseo_news_options', wp_parse_args( get_option( 'wpseo_news', array() ), array(
			'name'                     => '',
			'default_genre'            => array(),
			'ep_image_src'             => '',
			'version'                  => '0',
		) ) );
	}

	/**
	 * Initializes the plugin.
	 */
	public function __construct() {
		// Check if module can work.
		global $wp_version;
		if ( false === $this->check_dependencies( $wp_version ) ) {
			return;
		}

		$this->set_hooks();

		// Meta box.
		new WPSEO_News_Meta_Box();

		// Sitemap.
		new WPSEO_News_Sitemap();

		// Head.
		new WPSEO_News_Head();

		if ( is_admin() ) {
			$this->init_admin();
		}
	}

	/**
	 * Loading the hooks, which will be lead to methods withing this class.
	 */
	private function set_hooks() {
		// Add plugin links.
		add_filter( 'plugin_action_links', array( $this, 'plugin_links' ), 10, 2 );

		// Add subitem to menu.
		add_filter( 'wpseo_submenu_pages', array( $this, 'add_submenu_pages' ) );

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Only initialize Helpscout Beacon when the License Manager is present.
		if ( class_exists( 'Yoast_Plugin_License_Manager' ) ) {
			add_action( 'admin_init', array( $this, 'init_helpscout_beacon' ) );
		}
	}

	/**
	 * Initialize the admin page.
	 */
	private function init_admin() {
		// Upgrade Manager.
		$upgrade_manager = new WPSEO_News_Upgrade_Manager();
		$upgrade_manager->check_update();

		// License Manager.
		$license_manager = $this->get_license_manager();
		if ( $license_manager ) {
			add_action( 'wpseo_licenses_forms', array( $license_manager, 'show_license_form' ) );
		}

		// Setting action for removing the transient on update options.
		if ( class_exists( 'WPSEO_Sitemaps_Cache' )
			&& method_exists( 'WPSEO_Sitemaps_Cache', 'register_clear_on_option_update' )
		) {
			WPSEO_Sitemaps_Cache::register_clear_on_option_update(
				'wpseo_news',
				WPSEO_News_Sitemap::get_sitemap_name( false )
			);
		}
	}

	/**
	 * Check the dependencies.
	 *
	 * @param string $wp_version The current version of WordPress.
	 *
	 * @return bool True whether the dependencies are okay.
	 */
	protected function check_dependencies( $wp_version ) {
		// When WordPress function is too low.
		if ( version_compare( $wp_version, '4.8', '<' ) ) {
			add_action( 'all_admin_notices', array( $this, 'error_upgrade_wp' ) );

			return false;
		}

		$wordpress_seo_version = $this->get_wordpress_seo_version();

		// When WPSEO_VERSION isn't defined.
		if ( $wordpress_seo_version === false ) {
			add_action( 'all_admin_notices', array( $this, 'error_missing_wpseo' ) );

			return false;
		}

		// Make sure Yoast SEO is installed on version 7.0 or an RC candidate of that version.
		if ( version_compare( $wordpress_seo_version, '6.9', '<' ) ) {
			add_action( 'all_admin_notices', array( $this, 'error_upgrade_wpseo' ) );

			return false;
		}

		return true;
	}

	/**
	 * Returns the WordPress SEO version when set.
	 *
	 * @return bool|string The version whether it is set.
	 */
	protected function get_wordpress_seo_version() {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return false;
		}

		return WPSEO_VERSION;
	}

	/**
	 * Check whether we can include the minified version or not.
	 *
	 * @param string $ext The file extension.
	 *
	 * @return string
	 */
	private function file_ext( $ext ) {
		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {
			$ext = '.min' . $ext;
		}

		return $ext;
	}

	/**
	 * Add plugin links.
	 *
	 * @param array  $links The plugin links.
	 * @param string $file  The file name.
	 *
	 * @return mixed
	 */
	public function plugin_links( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = plugin_basename( WPSEO_NEWS_FILE );
		}
		if ( $file === $this_plugin ) {
			$settings_link = '<a href="' . admin_url( 'admin.php?page=wpseo_news' ) . '">'
				. __( 'Settings', 'wordpress-seo-news' )
				. '</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	/**
	 * Register the premium settings.
	 */
	public function register_settings() {
		register_setting( 'yoast_wpseo_news_options', 'wpseo_news', array( $this, 'sanitize_options' ) );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $options The options to sanitize.
	 *
	 * @return mixed
	 */
	public function sanitize_options( $options ) {
		$options['version'] = self::VERSION;

		return $options;
	}

	/**
	 * Add submenu item.
	 *
	 * @param array $submenu_pages Array with the sub menu pages.
	 *
	 * @return array
	 */
	public function add_submenu_pages( $submenu_pages ) {

		$admin_page = new WPSEO_News_Admin_Page();

		$submenu_pages[] = array(
			'wpseo_dashboard',
			'Yoast SEO: News SEO',
			'News SEO',
			'wpseo_manage_options',
			'wpseo_news',
			array( $admin_page, 'display' ),
			array( array( $this, 'enqueue_admin_page' ) ),
		);

		return $submenu_pages;
	}


	/**
	 * Enqueue admin page JS.
	 */
	public function enqueue_admin_page() {

		wp_enqueue_media(); // Enqueue files needed for upload functionality.

		wp_enqueue_script(
			'wpseo-news-admin-page',
			plugins_url( 'assets/admin-page' . $this->file_ext( '.js' ), WPSEO_NEWS_FILE ), array(
				'jquery',
			),
			self::VERSION,
			true
		);

		wp_localize_script( 'wpseo-news-admin-page', 'wpseonews', WPSEO_News_Javascript_Strings::strings() );
	}

	/**
	 * Throw an error if Yoast SEO is not installed.
	 *
	 * @since 2.0.0
	 */
	public function error_missing_wpseo() {
		echo '<div class="error"><p>';
		printf(
			/* translators: %1$s resolves to the link to search for Yoast SEO, %2$s resolves to the closing tag for this link, %3$s resolves to Yoast SEO, %4$s resolves to News SEO */
			esc_html__(
				'Please %1$sinstall &amp; activate %3$s%2$s and then enable its XML sitemap functionality to allow the %4$s module to work.',
				'wordpress-seo-news'
			),
			'<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&type=term&s=yoast+seo&plugin-search-input=Search+Plugins' ) ) . '">',
			'</a>',
			'Yoast SEO',
			'News SEO'
		);
		echo '</p></div>';
	}

	/**
	 * Throw an error if WordPress is out of date.
	 *
	 * @since 2.0.0
	 */
	public function error_upgrade_wp() {
		echo '<div class="error"><p>';
		printf(
			/* translators: %1$s resolves to News SEO */
			esc_html__(
				'Please upgrade WordPress to the latest version to allow WordPress and the %1$s module to work properly.',
				'wordpress-seo-news'
			),
			'News SEO'
		);
		echo '</p></div>';
	}

	/**
	 * Throw an error if Yoast SEO is out of date.
	 *
	 * @since 2.0.0
	 */
	public function error_upgrade_wpseo() {
		echo '<div class="error"><p>';
		printf(
			/* translators: %1$s resolves to Yoast SEO, %2$s resolves to News SEO */
			esc_html__(
				'Please upgrade the %1$s plugin to the latest version to allow the %2$s module to work.',
				'wordpress-seo-news'
			),
			'Yoast SEO',
			'News SEO'
		);
		echo '</p></div>';
	}

	/**
	 * Initializes the helpscout beacon.
	 */
	public function init_helpscout_beacon() {
		$query_var = ( $page = filter_input( INPUT_GET, 'page' ) ) ? $page : '';

		// Only add the helpscout beacon on Yoast SEO pages.
		if ( $query_var === 'wpseo_news' ) {
			$beacon = yoast_get_helpscout_beacon( $query_var );
			$beacon->add_setting( new WPSEO_News_Beacon_Setting() );
			$beacon->register_hooks();
		}
	}

	/**
	 * Getting the post_types based on the included post_types option.
	 *
	 * The variable $post_types is static, because it won't change during pageload, but the method may be called
	 * multiple times. First time it will set the value, second time it will return this value.
	 *
	 * @return array
	 */
	public static function get_included_post_types() {
		static $post_types;

		if ( $post_types === null ) {
			$options = self::get_options();

			// Get supported post types.
			$post_types = array();
			foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) {
				if ( isset( $options[ 'newssitemap_include_' . $post_type->name ] ) && ( 'on' === $options[ 'newssitemap_include_' . $post_type->name ] ) ) {
					$post_types[] = $post_type->name;
				}
			}

			// Support post if no post types are supported.
			if ( empty( $post_types ) ) {
				$post_types[] = 'post';
			}
		}

		return $post_types;
	}

	/**
	 * Listing the genres.
	 *
	 * @return array
	 */
	public static function list_genres() {
		return array(
			'none'          => __( 'None', 'wordpress-seo-news' ),
			'pressrelease'  => __( 'Press Release', 'wordpress-seo-news' ),
			'satire'        => __( 'Satire', 'wordpress-seo-news' ),
			'blog'          => __( 'Blog', 'wordpress-seo-news' ),
			'oped'          => __( 'Op-Ed', 'wordpress-seo-news' ),
			'opinion'       => __( 'Opinion', 'wordpress-seo-news' ),
			'usergenerated' => __( 'User Generated', 'wordpress-seo-news' ),
		);
	}

	/**
	 * Get the newest License Manager available.
	 *
	 * @return Yoast_Plugin_License_Manager
	 */
	private function get_license_manager() {

		if ( ! class_exists( 'Yoast_Plugin_License_Manager' ) ) {
			return null;
		}

		$license_manager = new Yoast_Plugin_License_Manager( new WPSEO_News_Product() );
		$license_manager->setup_hooks();

		return $license_manager;
	}

	/**
	 * Getting the name for the sitemap, if $full_path is true, it will return the full path
	 *
	 * @deprecated 5.2
	 *
	 * @param bool $full_path Generate a full path.
	 *
	 * @return string
	 */
	public static function get_sitemap_name( $full_path = true ) {
		_deprecated_function( 'WPSEO_News::get_sitemap_name', '5.2', 'WPSEO_News_Sitemap::get_sitemap_name' );

		return WPSEO_News_Sitemap::get_sitemap_name( $full_path );
	}
}
