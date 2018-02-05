<?php
/**
 * @package WPSEO_News\XML_Sitemaps
 */

/**
 * Handling the generation of the News Sitemap
 */
class WPSEO_News_Sitemap {

	/** @var array Options */
	private $options;

	/**
	 * @var string The sitemap basename.
	 */
	private $basename;

	/**
	 * Constructor. Set options, basename and add actions.
	 */
	public function __construct() {
		$this->options  = WPSEO_News::get_options();

		add_action( 'init', array( $this, 'init' ), 10 );

		add_action( 'save_post', array( $this, 'invalidate_sitemap' ) );

		add_action( 'wpseo_news_schedule_sitemap_clear', 'yoast_wpseo_news_clear_sitemap_cache' );
	}

	/**
	 * Add the XML News Sitemap to the Sitemap Index.
	 *
	 * @param string $str String with Index sitemap content.
	 *
	 * @return string
	 */
	public function add_to_index( $str ) {

		// Only add when we have items.
		$items = $this->get_items( 1 );
		if ( empty( $items ) ) {
			return $str;
		}

		$date = new DateTime( get_lastpostdate( 'gmt' ), new DateTimeZone( new WPSEO_News_Sitemap_Timezone() ) );

		$str .= '<sitemap>' . "\n";
		$str .= '<loc>' . self::get_sitemap_name() . '</loc>' . "\n";
		$str .= '<lastmod>' . htmlspecialchars( $date->format( 'c' ) ) . '</lastmod>' . "\n";
		$str .= '</sitemap>' . "\n";

		return $str;
	}

	/**
	 * Register the XML News sitemap with the main sitemap class.
	 */
	public function init() {

		$this->basename = WPSEO_News_Sitemap::get_sitemap_name( false );

		// Setting stylesheet for cached sitemap.
		add_action( 'wpseo_sitemap_stylesheet_cache_' . $this->basename, array( $this, 'set_stylesheet_cache' ) );

		if ( isset( $GLOBALS['wpseo_sitemaps'] ) ) {
			add_filter( 'wpseo_sitemap_index', array( $this, 'add_to_index' ) );

			$this->yoast_wpseo_news_schedule_clear();

			// We might consider deprecating/removing this, because we are using a static xsl file.
			$GLOBALS['wpseo_sitemaps']->register_sitemap( $this->basename, array( $this, 'build' ) );
			if ( method_exists( $GLOBALS['wpseo_sitemaps'], 'register_xsl' ) ) {
				$xsl_rewrite_rule = sprintf( '^%s-sitemap.xsl$', $this->basename );

				$GLOBALS['wpseo_sitemaps']->register_xsl( $this->basename, array( $this, 'build_news_sitemap_xsl' ), $xsl_rewrite_rule );
			}
		}
	}

	/**
	 * Method to invalidate the sitemap
	 *
	 * @param integer $post_id Post ID to invalidate for.
	 */
	public function invalidate_sitemap( $post_id ) {
		// If this is just a revision, don't invalidate the sitemap cache yet.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only invalidate when we are in a News Post Type object.
		if ( ! in_array( get_post_type( $post_id ), WPSEO_News::get_included_post_types() ) ) {
			return;
		}

		WPSEO_Sitemaps_Cache::invalidate( $this->basename );
	}

	/**
	 * When sitemap is coming out of the cache there is no stylesheet. Normally it will take the default stylesheet.
	 *
	 * This method is called by a filter that will set the video stylesheet.
	 *
	 * @param object $target_object Target Object to set cache from.
	 *
	 * @return object
	 */
	public function set_stylesheet_cache( $target_object ) {
		$target_object->renderer->set_stylesheet( $this->get_stylesheet_line() );

		return $target_object;
	}

	/**
	 * Build the sitemap and push it to the XML Sitemaps Class instance for display.
	 */
	public function build() {
		$GLOBALS['wpseo_sitemaps']->set_sitemap( $this->build_sitemap() );
		$GLOBALS['wpseo_sitemaps']->renderer->set_stylesheet( $this->get_stylesheet_line() );
	}

	/**
	 * Building the XML for the sitemap
	 *
	 * @return string
	 */
	public function build_sitemap() {
		$output = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

		$items = $this->get_items();

		// Loop through items.
		if ( ! empty( $items ) ) {
			$output .= $this->build_items( $items );
		}

		$output .= '</urlset>';

		return $output;
	}

	/**
	 * Outputs the XSL file
	 */
	public function build_news_sitemap_xsl() {
		$protocol = 'HTTP/1.1';
		if ( filter_input( INPUT_SERVER, 'SERVER_PROTOCOL' ) !== '' ) {
			$protocol = sanitize_text_field( filter_input( INPUT_SERVER, 'SERVER_PROTOCOL' ) );
		}
		// Force a 200 header and replace other status codes.
		header( $protocol . ' 200 OK', true, 200 );
		// Set the right content / mime type.
		header( 'Content-Type: text/xml' );
		// Prevent the search engines from indexing the XML Sitemap.
		header( 'X-Robots-Tag: noindex, follow', true );
		// Make the browser cache this file properly.
		header( 'Pragma: public' );
		header( 'Cache-Control: maxage=' . YEAR_IN_SECONDS );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', ( time() + YEAR_IN_SECONDS ) ) . ' GMT' );

		readfile( dirname( WPSEO_NEWS_FILE ) . '/assets/xml-news-sitemap.xsl' );
		die();
	}

	/**
	 * Clear the sitemap  and sitemap index every hour to make sure the sitemap is hidden or shown when it needs to be.
	 */
	private function yoast_wpseo_news_schedule_clear() {
		$schedule = wp_get_schedule( 'wpseo_news_schedule_sitemap_clear' );

		if ( empty( $schedule ) ) {
			wp_schedule_event( time(), 'hourly', 'wpseo_news_schedule_sitemap_clear' );
		}
	}

	/**
	 * Getter for stylesheet url
	 *
	 * @return string
	 */
	private function get_stylesheet_line() {
		$stylesheet_url = "\n" . '<?xml-stylesheet type="text/xsl" href="' . esc_url( $this->get_xsl_url() ) . '"?>';

		return $stylesheet_url;
	}

	/**
	 * Getting all the items for the sitemap
	 *
	 * @param int $limit the limit for the query, default is 1000 items.
	 *
	 * @return array|null|object
	 */
	private function get_items( $limit = 1000 ) {
		global $wpdb;

		$limit = max( 1, min( 1000, $limit ) );

		$post_types = $this->get_post_types();
		if ( empty( $post_types ) ) {
			return array();
		}

		// Get posts for the last two days only, credit to Alex Moss for this code.
		// @codingStandardsIgnoreStart
		$sql_query = "
			 SELECT ID, post_content, post_name, post_author, post_parent, post_date_gmt, post_date, post_date_gmt, post_title, post_type
			 FROM {$wpdb->posts}
			 WHERE post_status=%s
			 AND (DATEDIFF(CURDATE(), post_date_gmt)<=2)
			 AND post_type IN ({$post_types})
			 ORDER BY post_date_gmt DESC
			 LIMIT 0, {$limit}
		 ";

		$items = $wpdb->get_results( $wpdb->prepare( $sql_query, 'publish' ) );

		// @codingStandardsIgnoreEnd

		return $items;
	}

	/**
	 * Loop through all $items and build each one of it
	 *
	 * @param array $items Items to convert to sitemap output.
	 *
	 * @return string $output
	 */
	private function build_items( $items ) {
		$output = '';
		foreach ( $items as $item ) {
			$output .= new WPSEO_News_Sitemap_Item( $item, $this->options );
		}

		return $output;
	}

	/**
	 * Getting the post_types which will be displayed in the sitemap
	 *
	 * @return array|string
	 */
	private function get_post_types() {
		// Get supported post types.
		$post_types = WPSEO_News::get_included_post_types();

		if ( ! empty( $post_types ) ) {
			$post_types = "'" . implode( "','", $post_types ) . "'";
		}

		return $post_types;
	}

	/**
	 * Getting the name for the sitemap, if $full_path is true, it will return the full path
	 *
	 * @param bool $full_path Generate a full path.
	 *
	 * @return string mixed
	 */
	public static function get_sitemap_name( $full_path = true ) {
		// This filter is documented in classes/class-sitemap.php.
		$sitemap_name = apply_filters( 'wpseo_news_sitemap_name', self::news_sitemap_basename() );

		// When $full_path is true, it will generate a full path.
		if ( $full_path ) {
			return WPSEO_Sitemaps_Router::get_base_url( $sitemap_name . '-sitemap.xml' );
		}

		return $sitemap_name;
	}

	/**
	 * Returns the basename of the news-sitemap, the first portion of the name of the sitemap "file".
	 *
	 * Defaults to news, but it's possible to override it by using the YOAST_VIDEO_SITEMAP_BASENAME constant.
	 *
	 * @since 3.1
	 *
	 * @return string $basename
	 */
	public static function news_sitemap_basename() {
		$basename = 'news';

		if ( post_type_exists( 'news' ) ) {
			$basename = 'yoast-news';
		}

		if ( defined( 'YOAST_NEWS_SITEMAP_BASENAME' ) ) {
			$basename = YOAST_NEWS_SITEMAP_BASENAME;
		}

		return $basename;
	}

	/**
	 * Retrieves the XSL URL that should be used in the current environment
	 *
	 * When home_url and site_url are not the same, the home_url should be used.
	 * This is because the XSL needs to be served from the same domain, protocol and port
	 * as the XML file that is loading it.
	 *
	 * @return string The XSL URL that needs to be used.
	 */
	protected function get_xsl_url() {
		if ( home_url() !== site_url() ) {
			return home_url( $this->basename . '-sitemap.xsl' );
		}

		return plugin_dir_url( WPSEO_NEWS_FILE ) . 'assets/xml-news-sitemap.xsl';
	}
}

/**
 * Convert the sitemap dates to the correct timezone
 */
class WPSEO_News_Sitemap_Timezone {

	/**
	 * Returns the timezone string for a site, even if it's set to a UTC offset
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->wp_get_timezone_string();
	}

	/**
	 * Returns the timezone string for a site, even if it's set to a UTC offset
	 *
	 * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
	 *
	 * @return string valid PHP timezone string
	 */
	private function wp_get_timezone_string() {

		// If site timezone string exists, return it.
		if ( $timezone = get_option( 'timezone_string' ) ) {
			return $timezone;
		}

		// Get UTC offset, if it isn't set then return UTC.
		if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) ) {
			return 'UTC';
		}

		// Adjust UTC offset from hours to seconds.
		$utc_offset *= HOUR_IN_SECONDS;

		// Attempt to guess the timezone string from the UTC offset.
		$timezone = timezone_name_from_abbr( '', $utc_offset );

		if ( false !== $timezone ) {
			return $timezone;
		}

		// Last try, guess timezone string manually.
		$timezone_id = $this->get_timezone_id( $utc_offset );
		if ( $timezone_id ) {
			return $timezone_id;
		}

		// Fallback to UTC.
		return 'UTC';
	}


	/**
	 * Getting the timezone id
	 *
	 * @param string $utc_offset Offset to use.
	 *
	 * @return mixed
	 */
	private function get_timezone_id( $utc_offset ) {
		$is_dst = date( 'I' );

		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] === $is_dst && $city['offset'] === $utc_offset ) {
					return $city['timezone_id'];
				}
			}
		}

		return false;
	}
}

/**
 * The News Sitemap entry
 */
class WPSEO_News_Sitemap_Item {

	/** @var string The output which will be returned */
	private $output = '';

	/** @var object The current item */
	private $item;

	/** @var array The options */
	private $options;

	/**
	 * Setting properties and build the item
	 *
	 * @param object $item    The post.
	 * @param array  $options The options.
	 */
	public function __construct( $item, $options ) {
		$this->item    = $item;
		$this->options = $options;


		// Check if item should be skipped.
		if ( ! $this->skip_build_item() ) {
			$this->build_item();
		}
	}

	/**
	 * Return the output, because the object is converted to a string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->output;
	}

	/**
	 * Determine if item has to be skipped or not
	 *
	 * @return bool
	 */
	private function skip_build_item() {
		if ( WPSEO_Meta::get_value( 'newssitemap-exclude', $this->item->ID ) === 'on' ) {
			return true;
		}

		if ( false !== WPSEO_Meta::get_value( 'meta-robots', $this->item->ID ) && strpos( WPSEO_Meta::get_value( 'meta-robots', $this->item->ID ), 'noindex' ) !== false ) {
			return true;
		}

		$exclude_item = $this->exclude_item_terms() || apply_filters( 'wpseo_news_sitemap_exclude_item', false, $this->item->ID );
		if ( 'post' === $this->item->post_type && $exclude_item ) {
			return true;
		}

		return false;
	}

	/**
	 * Exclude the item when one of his terms is excluded
	 *
	 * @return bool
	 */
	private function exclude_item_terms() {
		$cats    = get_the_terms( $this->item->ID, 'category' );
		$exclude = 0;

		if ( is_array( $cats ) ) {
			foreach ( $cats as $cat ) {
				if ( isset( $this->options[ 'catexclude_' . $cat->slug ] ) ) {
					$exclude ++;
				}
			}
		}

		if ( $exclude >= count( $cats ) ) {
			return true;
		}
	}

	/**
	 * Building each sitemap item
	 */
	private function build_item() {
		$this->item->post_status = 'publish';

		$this->output .= '<url>' . "\n";
		$this->output .= "\t<loc>" . get_permalink( $this->item ) . '</loc>' . "\n";

		// Building the news_tag.
		$this->build_news_tag();

		// Getting the images for this item.
		$this->get_item_images();

		$this->output .= '</url>' . "\n";
	}

	/**
	 * Building the news tag
	 */
	private function build_news_tag() {

		$keywords      = new WPSEO_News_Meta_Keywords( $this->item->ID );
		$genre         = $this->get_item_genre();
		$stock_tickers = $this->get_item_stock_tickers( $this->item->ID );

		$this->output .= "\t<news:news>\n";

		// Build the publication tag.
		$this->build_publication_tag();

		if ( ! empty( $genre ) ) {
			$this->output .= "\t\t<news:genres><![CDATA[" . $genre . ']]></news:genres>' . "\n";
		}

		$this->output .= "\t\t<news:publication_date>" . $this->get_publication_date( $this->item ) . '</news:publication_date>' . "\n";
		$this->output .= "\t\t<news:title><![CDATA[" . $this->item->post_title . ']]></news:title>' . "\n";

		if ( ! empty( $keywords ) ) {
			$this->output .= "\t\t<news:keywords><![CDATA[" . $keywords . ']]></news:keywords>' . "\n";
		}

		if ( ! empty( $stock_tickers ) ) {
			$this->output .= "\t\t<news:stock_tickers><![CDATA[" . $stock_tickers . ']]></news:stock_tickers>' . "\n";
		}

		$this->output .= "\t</news:news>\n";
	}

	/**
	 * Builds the publication tag
	 */
	private function build_publication_tag() {
		$publication_name = ! empty( $this->options['name'] ) ? $this->options['name'] : get_bloginfo( 'name' );
		$publication_lang = $this->get_publication_lang();

		$this->output .= "\t\t<news:publication>\n";
		$this->output .= "\t\t\t<news:name>" . $publication_name . '</news:name>' . "\n";
		$this->output .= "\t\t\t<news:language>" . htmlspecialchars( $publication_lang ) . '</news:language>' . "\n";
		$this->output .= "\t\t</news:publication>\n";
	}

	/**
	 * Getting the genre for given $item_id
	 *
	 * @return string
	 */
	private function get_item_genre() {
		$genre = WPSEO_Meta::get_value( 'newssitemap-genre', $this->item->ID );
		if ( is_array( $genre ) ) {
			$genre = implode( ',', $genre );
		}

		if ( $genre === '' && isset( $this->options['default_genre'] ) && $this->options['default_genre'] !== '' ) {
			$genre = is_array( $this->options['default_genre'] ) ? implode( ',', $this->options['default_genre'] ) : $this->options['default_genre'];
		}

		$genre = trim( preg_replace( '/^none,?/', '', $genre ) );

		return $genre;
	}

	/**
	 * Getting the publication language
	 *
	 * @return string
	 */
	private function get_publication_lang() {
		$locale = apply_filters( 'wpseo_locale', get_locale() );

		// Fallback to 'en', if the length of the locale is less than 2 characters.
		if ( strlen( $locale ) < 2 ) {
			$locale = 'en';
		}

		$publication_lang = substr( $locale, 0, 2 );

		return $publication_lang;
	}

	/**
	 * Parses the $item argument into an xml format
	 *
	 * @param WP_Post $item Object to get data from.
	 *
	 * @return string
	 */
	private function get_publication_date( $item ) {
		if ( $this->is_valid_datetime( $item->post_date_gmt ) ) {
			// Create a DateTime object date in the correct timezone.
			return $this->format_date_with_timezone( $item->post_date_gmt );
		}
		if ( $this->is_valid_datetime( $item->post_modified_gmt ) ) {
			// Fallback 1: post_modified_gmt.
			return $this->format_date_with_timezone( $item->post_modified_gmt );
		}
		if ( $this->is_valid_datetime( $item->post_modified ) ) {
			// Fallback 2: post_modified.
			return $this->format_date_with_timezone( $item->post_modified );
		}
		if ( $this->is_valid_datetime( $item->post_date ) ) {
			// Fallback 3: post_date.
			return $this->format_date_with_timezone( $item->post_date );
		}

		return '';
	}

	/**
	 * Format a datestring with a timezone
	 *
	 * @param string $item_date Date to parse.
	 *
	 * @return string
	 */
	private function format_date_with_timezone( $item_date ) {
		static $timezone_string;

		if ( $timezone_string === null ) {
			// Get the timezone string.
			$timezone_string = new WPSEO_News_Sitemap_Timezone();
		}

		// Create a DateTime object date in the correct timezone.
		$datetime = new DateTime( $item_date, new DateTimeZone( $timezone_string ) );

		return $datetime->format( $this->get_date_format() );
	}

	/**
	 * When the timezone string option in WordPress is empty, just return YYYY-MM-DD as format.
	 *
	 * @return string
	 */
	private function get_date_format() {
		static $timezone_option;

		if ( $timezone_option === null ) {

			// Get the timezone string.
			$timezone_option = new WPSEO_News_Sitemap_Timezone();
		}

		// Is there a timezone option and does it exists in the list of 'valid' timezone.
		if ( $timezone_option !== '' && in_array( $timezone_option, DateTimeZone::listIdentifiers() ) ) {
			return DateTime::W3C;
		}

		return 'Y-m-d';
	}

	/**
	 * Getting the stock_tickers for given $item_id
	 *
	 * @param integer $item_id Item to get ticker from.
	 *
	 * @return string
	 */
	private function get_item_stock_tickers( $item_id ) {
		$stock_tickers = explode( ',', trim( WPSEO_Meta::get_value( 'newssitemap-stocktickers', $item_id ) ) );
		$stock_tickers = trim( implode( ', ', $stock_tickers ), ', ' );

		return $stock_tickers;
	}

	/**
	 * Getting the images for current item
	 */
	private function get_item_images() {
		$this->output .= new WPSEO_News_Sitemap_Images( $this->item, $this->options );
	}

	/**
	 * Wrapper function to check if we have a valid datetime (Uses a new util in WPSEO)
	 *
	 * @param string $datetime Datetime to check.
	 *
	 * @return bool
	 */
	private function is_valid_datetime( $datetime ) {
		if ( method_exists( 'WPSEO_Utils', 'is_valid_datetime' ) ) {
			return WPSEO_Utils::is_valid_datetime( $datetime );
		}

		return true;
	}
}

/**
 * Handle images used in News
 */
class WPSEO_News_Sitemap_Images {

	/** @var object The current item */
	private $item;

	/** @var string The out that will be returned */
	private $output = '';

	/** @var array The options */
	private $options;

	/** @var array Storage for the images */
	private $images;

	/**
	 * Setting properties and build the item
	 *
	 * @param object $item    News post object.
	 * @param array  $options The options.
	 */
	public function __construct( $item, $options ) {
		$this->item    = $item;
		$this->options = $options;

		$this->parse_item_images();
	}

	/**
	 * Return the output, because the object is converted to a string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->output;
	}

	/**
	 * Parsing the images from the item
	 */
	private function parse_item_images() {
		$this->get_item_images();

		if ( ! empty( $this->images ) ) {
			foreach ( $this->images as $src => $img ) {
				$this->parse_item_image( $src, $img );
			}
		}
	}

	/**
	 * Getting the images for the given $item
	 */
	private function get_item_images() {
		$restrict_sitemap_featured_img = isset( $this->options['restrict_sitemap_featured_img'] ) ? $this->options['restrict_sitemap_featured_img'] : false;
		if ( ! $restrict_sitemap_featured_img && preg_match_all( '/<img [^>]+>/', $this->item->post_content, $matches ) ) {
			$this->get_images_from_content( $matches );
		}

		// Also check if the featured image value is set.
		$post_thumbnail_id = get_post_thumbnail_id( $this->item->ID );
		if ( '' !== $post_thumbnail_id ) {
			$this->get_item_featured_image( $post_thumbnail_id );
		}
	}

	/**
	 * Getting the images from the content
	 *
	 * @param array $matches Images found in the content.
	 */
	private function get_images_from_content( $matches ) {
		foreach ( $matches[0] as $img ) {
			if ( ! preg_match( '/src=("|\')([^"|\']+)("|\')/', $img, $match ) ) {
				continue;
			}

			$src = $this->parse_image_source( $match[2] );
			if ( ! empty( $src ) && ! isset( $this->images[ $src ] ) ) {
				$this->images[ $src ] = $this->parse_image( $img );
			}
		}
	}

	/**
	 * Parsing the image source
	 *
	 * @param string $src Image Source.
	 *
	 * @return string|void
	 */
	private function parse_image_source( $src ) {

		static $home_url;

		if ( is_null( $home_url ) ) {
			$home_url = home_url();
		}

		if ( strpos( $src, 'http' ) !== 0 ) {
			if ( $src[0] !== '/' ) {
				return null;
			}

			$src = $home_url . $src;
		}

		if ( $src !== esc_url( $src ) ) {
			return null;
		}

		return $src;
	}

	/**
	 * Setting title and alt for image and returns them in an array
	 *
	 * @param string $img Image HTML.
	 *
	 * @return array
	 */
	private function parse_image( $img ) {
		$image = array();
		if ( preg_match( '/title=("|\')([^"\']+)("|\')/', $img, $match ) ) {
			$image['title'] = str_replace( array( '-', '_' ), ' ', $match[2] );
		}

		if ( preg_match( '/alt=("|\')([^"\']+)("|\')/', $img, $match ) ) {
			$image['alt'] = str_replace( array( '-', '_' ), ' ', $match[2] );
		}

		return $image;
	}

	/**
	 * Parse the XML for given image
	 *
	 * @param string $src Image source.
	 * @param array  $img Image array.
	 *
	 * @return void
	 */
	private function parse_item_image( $src, $img ) {
		/**
		 * Filter: 'wpseo_xml_sitemap_img_src' - Allow changing of sitemap image src
		 *
		 * @api string $src The image source
		 *
		 * @param object $item The post item
		 */
		$src = apply_filters( 'wpseo_xml_sitemap_img_src', $src, $this->item );

		$this->output .= "\t<image:image>\n";
		$this->output .= "\t\t<image:loc>" . htmlspecialchars( $src ) . "</image:loc>\n";

		if ( ! empty( $img['title'] ) ) {
			$this->output .= "\t\t<image:title>" . htmlspecialchars( $img['title'] ) . "</image:title>\n";
		}

		if ( ! empty( $img['alt'] ) ) {
			$this->output .= "\t\t<image:caption>" . htmlspecialchars( $img['alt'] ) . "</image:caption>\n";
		}

		$this->output .= "\t</image:image>\n";
	}

	/**
	 * Getting the featured image
	 *
	 * @param integer $post_thumbnail_id Thumbnail ID.
	 *
	 * @return void
	 */
	private function get_item_featured_image( $post_thumbnail_id ) {

		$attachment = $this->get_attachment( $post_thumbnail_id );

		if ( empty( $attachment ) ) {
			return;
		}

		$image = array();

		if ( ! empty( $attachment['title'] ) ) {
			$image['title'] = $attachment['title'];
		}

		if ( ! empty( $attachment['alt'] ) ) {
			$image['alt'] = $attachment['alt'];
		}

		if ( ! empty( $attachment['src'] ) ) {
			$this->images[ $attachment['src'] ] = $image;
		}
		elseif ( ! empty( $attachment['href'] ) ) {
			$this->images[ $attachment['href'] ] = $image;
		}
	}

	/**
	 * Get attachment
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array
	 */
	private function get_attachment( $attachment_id ) {
		// Get attachment.
		$attachment = get_post( $attachment_id );

		// Check if we've found an attachment.
		if ( is_null( $attachment ) ) {
			return array();
		}

		// Return properties.
		return array(
			'title'       => $attachment->post_title,
			'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'href'        => get_permalink( $attachment->ID ),
			'src'         => $attachment->guid,
		);
	}
}
