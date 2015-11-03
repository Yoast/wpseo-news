<?php

class WPSEO_News_Meta_Box extends WPSEO_Metabox {

	/**
	 * @var int    The maximum number of standout tags allowed.
	 */
	private $max_standouts = 7;

	/**
	 * Constructing the object.
	 */
	public function __construct() {
		$this->options = WPSEO_News::get_options();

		add_filter( 'wpseo_save_metaboxes', array( $this, 'save' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'add_tab_hooks' ) );
		add_filter( 'add_extra_wpseo_meta_fields', array( $this, 'add_meta_fields_to_wpseo_meta' ) );
	}

	/**
	 * The metaboxes to display and save for the tab
	 *
	 * @param string $post_type
	 *
	 * @return array $mbs
	 */
	public function get_meta_boxes( $post_type = 'post' ) {
		$mbs = array(
			'newssitemap-exclude'      => array(
				'name'  => 'newssitemap-exclude',
				'type'  => 'checkbox',
				'std'   => 'on',
				'title' => __( 'Exclude from News Sitemap', 'wordpress-seo-news' ),
			),
			'newssitemap-keywords'     => array(
				'name'        => 'newssitemap-keywords',
				'type'        => 'text',
				'std'        => '',
				'title'       => __( 'Meta News Keywords', 'wordpress-seo-news' ),
				'description' => __( 'Comma separated list of the keywords this article aims at, use a maximum of 10 keywords.', 'wordpress-seo-news' ),
			),
			'newssitemap-genre'        => array(
				'name'        => 'newssitemap-genre',
				'type'        => 'multiselect',
				'std'         => ( ( isset( $this->options['default_genre'] ) ) ? $this->options['default_genre'] : 'blog' ),
				'title'       => __( 'Google News Genre', 'wordpress-seo-news' ),
				'description' => __( 'Genre to show in Google News Sitemap.', 'wordpress-seo-news' ),
				'options'     => WPSEO_News::list_genres(),
				'serialized'  => true,
			),
			'newssitemap-original'     => array(
				'name'        => 'newssitemap-original',
				'std'         => '',
				'type'        => 'text',
				'title'       => __( 'Original Source', 'wordpress-seo-news' ),
				'description' => __( 'Is this article the original source of this news? If not, please enter the URL of the original source here. If there are multiple sources, please separate them by a pipe symbol: | .', 'wordpress-seo-news' ),
			),
			'newssitemap-stocktickers' => array(
				'name'        => 'newssitemap-stocktickers',
				'std'         => '',
				'type'        => 'text',
				'title'       => __( 'Stock Tickers', 'wordpress-seo-news' ),
				'description' => __( 'A comma-separated list of up to 5 stock tickers of the companies, mutual funds, or other financial entities that are the main subject of the article. Each ticker must be prefixed by the name of its stock exchange, and must match its entry in Google Finance. For example, "NASDAQ:AMAT" (but not "NASD:AMAT"), or "BOM:500325" (but not "BOM:RIL").', 'wordpress-seo-news' ),
			),
			'newssitemap-standout'     => array(
				'name'        => 'newssitemap-standout',
				'std'         => '',
				'type'        => 'checkbox',
				'title'       => __( 'Standout', 'wordpress-seo-news' ),
				'description' => $this->standout_description(),
			),
			'newssitemap-editors-pick' => array(
				'name'        => 'newssitemap-editors-pick',
				'std'         => '',
				'type'        => 'checkbox',
				'title'       => __( "Editors' Pick", 'wordpress-seo-news' ),
				'description' => __( "Editors' Picks enables you to provide up to five links to original news content you believe represents your organization’s best journalistic work at any given moment, and potentially have it displayed on the Google News homepage or select section pages.", 'wordpress-seo-news' ),
			),
		);

		return $mbs;
	}

	/**
	 * Add the meta boxes to meta box array so they get saved
	 *
	 * @param $meta_boxes
	 *
	 * @return array
	 */
	public function save( $meta_boxes ) {
		$meta_boxes = array_merge( $meta_boxes, $this->get_meta_boxes() );

		return $meta_boxes;
	}


	/**
	 * Add WordPress SEO meta fields to WPSEO meta class
	 *
	 * @param $meta_fields
	 *
	 * @return mixed
	 */
	public function add_meta_fields_to_wpseo_meta( $meta_fields ) {

		$meta_fields['news'] = $this->get_meta_boxes();

		return $meta_fields;
	}

	/**
	 * Only add the tab header and content actions when the post is supported
	 */
	public function add_tab_hooks() {
		if ( $this->is_post_type_supported() ) {
			add_action( 'wpseo_tab_header', array( $this, 'header' ) );
			add_action( 'wpseo_tab_content', array( $this, 'content' ) );
		}
	}

	/**
	 * The tab header
	 */
	public function header() {
		echo '<li class="news"><a class="wpseo_tablink" href="#wpseo_news">' . __( 'Google News', 'wordpress-seo-news' ) . '</a></li>';
	}

	/**
	 * The tab content
	 */
	public function content() {
		// Build tab content
		$content = '';
		foreach ( $this->get_meta_boxes() as $meta_key => $meta_box ) {
			$content .= $this->do_meta_box( $meta_box, $meta_key );
		}
		$this->do_tab( 'news', __( 'Google News', 'wordpress-seo-news' ), $content );
	}

	/**
	 * Check if current post_type is supported
	 *
	 * @return bool
	 */
	private function is_post_type_supported() {
		static $is_supported;

		if ( $is_supported === null ) {
			$post = $this->get_metabox_post();

			// Default is false
			$is_supported = false;

			// Get supported post types
			$post_types = WPSEO_News::get_included_post_types();

			// Display content if post type is supported
			if ( ! empty( $post_types ) && in_array( $post->post_type, $post_types ) ) {
				$is_supported = true;
			}
		}

		return $is_supported;
	}

	/**
	 * Count the total number of used standouts
	 *
	 * @return mixed
	 */
	private function standouts_used() {
		// Count standout tags
		$standout_query = new WP_Query(
			array(
				'post_type'   => 'any',
				'post_status' => 'publish',
				'meta_query'  => array(
					array(
						'key'   => '_yoast_wpseo_newssitemap-standout',
						'value' => 'on',
					),
				),
				'date_query'  => array(
					'after' => '-7 days',
				),
			)
		);

		return $standout_query->found_posts;
	}

	/**
	 * Generates the standout description
	 *
	 * @return string
	 */
	private function standout_description() {

		$used_standouts = $this->standouts_used();

		// Default standout description
		$standout_desc = __( 'If your news organization breaks a big story, or publishes an extraordinary work of journalism, you can indicate this by using the standout tag.', 'wordpress-seo-news' );
		$standout_desc .= '<br />';

		$standout_desc .= sprintf(
			__( 'Note: Google has a limit of %d stand out tags per seven days. Using more tags can cause removal from Google news. See for more information <a href="https://support.google.com/news/publisher/answer/191283?hl=en">this Google page</a>.', 'wordpress-seo-news' ),
			$this->max_standouts
		);

		$standout_desc .= '<br />';

		$standout_desc .= '<span style="font-weight:bold;';
		if ( $used_standouts > $this->max_standouts ) {
			$standout_desc .= 'color:#ff0000';
		}
		$standout_desc .= '">';
		$standout_desc .= sprintf(
			__( "You've used %s/%s standout tags in the last 7 days.", 'wordpress-seo-news' ),
			$used_standouts,
			$this->max_standouts
		);

		$standout_desc .= '</span>';

		return $standout_desc;
	}

	/**
	 * Returns post in metabox context - fallback for Yoast SEO < 3.0 and News SEO > 2.2.5
	 *
	 * @returns WP_Post
	 */
	protected function get_metabox_post() {
		if ( is_callable( 'parent:get_metabox_post' ) ) {
			return parent::get_metabox_post();
		}

		if ( $post = filter_input( INPUT_GET, 'post' ) ) {
			$post_id = (int) WPSEO_Utils::validate_int( $post );
			return get_post( $post_id );
		}

		if ( isset( $GLOBALS['post'] ) ) {
			return $GLOBALS['post'];
		}

		return array();
	}
}
