<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News
 */

use Yoast\WP\SEO\Presenters\Abstract_Indexable_Presenter;

/**
 * Represents the news extension for Yoast SEO.
 */
class WPSEO_News {

	/**
	 * Version number of the plugin.
	 *
	 * @var string
	 */
	const VERSION = WPSEO_NEWS_VERSION;

	/**
	 * Initializes the plugin.
	 */
	public function __construct() {
		// Check if module can work.
		global $wp_version;
		if ( $this->check_dependencies( $wp_version ) === false ) {
			return;
		}

		$this->set_hooks();

		// Meta box.
		new WPSEO_News_Meta_Box();

		// Sitemap.
		new WPSEO_News_Sitemap();

		// Schema.
		new WPSEO_News_Schema();

		if ( is_admin() ) {
			$this->init_admin();
		}
	}

	/**
	 * Loading the hooks, which will be lead to methods withing this class.
	 */
	private function set_hooks() {
		add_filter( 'plugin_action_links', [ $this, 'plugin_links' ], 10, 2 );
		add_filter( 'wpseo_submenu_pages', [ $this, 'add_submenu_pages' ] );
		add_action( 'admin_init', [ $this, 'init_helpscout_beacon' ] );
		add_action( 'init', [ 'WPSEO_News_Option', 'register_option' ] );

		// Enable Yoast usage tracking.
		add_filter( 'wpseo_enable_tracking', '__return_true' );

		add_filter( 'wpseo_frontend_presenters', [ $this, 'add_frontend_presenter' ] );

		add_action( 'admin_init', [ 'WPSEO_News_Updates_Notification', 'add_notification' ], 15 );
	}

	/**
	 * Adds the Google Bot News presenter.
	 *
	 * @param Abstract_Indexable_Presenter[] $presenters The presenter instances.
	 *
	 * @return Abstract_Indexable_Presenter[] The extended presenters.
	 */
	public function add_frontend_presenter( $presenters ) {
		if ( ! is_array( $presenters ) ) {
			return $presenters;
		}

		$presenters[] = new WPSEO_News_Googlebot_News_Presenter();

		return $presenters;
	}

	/**
	 * Initialize the admin page.
	 */
	private function init_admin() {
		// Upgrade Manager.
		$upgrade_manager = new WPSEO_News_Upgrade_Manager();
		$upgrade_manager->check_update();

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
		if ( version_compare( $wp_version, '5.3', '<' ) ) {
			add_action( 'all_admin_notices', [ $this, 'error_upgrade_wp' ] );

			return false;
		}

		$wordpress_seo_version = $this->get_wordpress_seo_version();

		// When WPSEO_VERSION isn't defined.
		if ( $wordpress_seo_version === false ) {
			add_action( 'all_admin_notices', [ $this, 'error_missing_wpseo' ] );

			return false;
		}

		// At least 14.0, in which we implemented the Indexables.
		if ( version_compare( $wordpress_seo_version, '14.0-RC0', '<' ) ) {
			add_action( 'all_admin_notices', [ $this, 'error_upgrade_wpseo' ] );

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
	 * Add plugin links.
	 *
	 * @param string[] $links The plugin links.
	 * @param string   $file  The file name.
	 *
	 * @return string[]
	 */
	public function plugin_links( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = plugin_basename( WPSEO_NEWS_FILE );
		}
		if ( $file === $this_plugin ) {
			$settings_link = sprintf(
				'<a href="%1$s">%2$s</a>',
				admin_url( 'admin.php?page=wpseo_news' ),
				__( 'Settings', 'wordpress-seo-news' )
			);
			array_unshift( $links, $settings_link );
		}

		return $links;
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

		$submenu_pages[] = [
			'wpseo_dashboard',
			'Yoast SEO: News SEO',
			'News SEO',
			'wpseo_manage_options',
			'wpseo_news',
			[ $admin_page, 'display' ],
			[ [ $this, 'enqueue_admin_page' ] ],
		];

		return $submenu_pages;
	}

	/**
	 * Enqueue admin page JS.
	 */
	public function enqueue_admin_page() {

		wp_enqueue_media(); // Enqueue files needed for upload functionality.

		wp_enqueue_script(
			'wpseo-news-admin-page',
			plugins_url( 'assets/admin-page.min.js', WPSEO_NEWS_FILE ),
			[ 'jquery' ],
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
		if ( ! class_exists( 'WPSEO_HelpScout' ) ) {
			return;
		}

		$helpscout = new WPSEO_HelpScout(
			'161a6b32-9360-4613-bd04-d8098b283a0f',
			[ 'wpseo_news' ],
			[ WPSEO_Addon_Manager::NEWS_SLUG ]
		);

		$helpscout->register_hooks();
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
			// Get supported post types.
			$post_types          = [];
			$included_post_types = (array) WPSEO_Options::get( 'news_sitemap_include_post_types', [] );
			foreach ( get_post_types( [ 'public' => true ], 'names' ) as $post_type ) {
				if ( array_key_exists( $post_type, $included_post_types ) && $included_post_types[ $post_type ] === 'on' ) {
					$post_types[] = $post_type;
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
	 * Determines whether the post is excluded in the news sitemap (and therefore schema) output.
	 *
	 * @param int $post_id The ID of the post to check for.
	 *
	 * @return bool Whether or not the post is excluded.
	 */
	public static function is_excluded_through_sitemap( $post_id ) {
		return WPSEO_Meta::get_value( 'newssitemap-exclude', $post_id ) === 'on';
	}

	/**
	 * Determines if the post is excluded in through a term that is excluded.
	 *
	 * @param int    $post_id   The ID of the post.
	 * @param string $post_type The type of the post.
	 *
	 * @return bool True if the post is excluded.
	 */
	public static function is_excluded_through_terms( $post_id, $post_type ) {
		$terms          = self::get_terms_for_post( $post_id, $post_type );
		$excluded_terms = (array) WPSEO_Options::get( 'news_sitemap_exclude_terms', [] );
		foreach ( $terms as $term ) {
			$option_key = $term->taxonomy . '_' . $term->slug . '_for_' . $post_type;
			if ( array_key_exists( $option_key, $excluded_terms ) && $excluded_terms[ $option_key ] === 'on' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieves all the term IDs for the post.
	 *
	 * @param int    $post_id   The ID of the post.
	 * @param string $post_type The type of the post.
	 *
	 * @return array The terms for the item.
	 */
	public static function get_terms_for_post( $post_id, $post_type ) {
		$terms                 = [];
		$excludable_taxonomies = new WPSEO_News_Excludable_Taxonomies( $post_type );

		foreach ( $excludable_taxonomies->get() as $taxonomy ) {
			$extra_terms = get_the_terms( $post_id, $taxonomy->name );

			if ( ! is_array( $extra_terms ) || count( $extra_terms ) === 0 ) {
				continue;
			}

			$terms = array_merge( $terms, $extra_terms );
		}

		return $terms;
	}

	/**
	 * Listing the genres.
	 *
	 * @deprecated 12.7 News genres are deprecated.
	 * @codeCoverageIgnore
	 *
	 * @return array
	 */
	public static function list_genres() {
		_deprecated_function( __METHOD__, 'WPSEO News 12.7' );

		return [
			'none'          => __( 'None', 'wordpress-seo-news' ),
			'pressrelease'  => __( 'Press Release', 'wordpress-seo-news' ),
			'satire'        => __( 'Satire', 'wordpress-seo-news' ),
			'blog'          => __( 'Blog', 'wordpress-seo-news' ),
			'oped'          => __( 'Op-Ed', 'wordpress-seo-news' ),
			'opinion'       => __( 'Opinion', 'wordpress-seo-news' ),
			'usergenerated' => __( 'User Generated', 'wordpress-seo-news' ),
		];
	}
}
