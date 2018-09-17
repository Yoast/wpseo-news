<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News\XML_Sitemaps
 */

/**
 * The News Sitemap entry.
 */
class WPSEO_News_Sitemap_Item {

	/**
	 * The output which will be returned.
	 *
	 * @var string
	 */
	private $output = '';

	/**
	 * The current item.
	 *
	 * @var object
	 */
	private $item;

	/**
	 * The options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Setting properties and build the item.
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
	 * Return the output, because the object is converted to a string.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->output;
	}

	/**
	 * Determine if item has to be skipped or not.
	 *
	 * @return bool
	 */
	private function skip_build_item() {
		if ( WPSEO_Meta::get_value( 'newssitemap-exclude', $this->item->ID ) === 'on' ) {
			return true;
		}

		$item_noindex = WPSEO_Meta::get_value( 'meta-robots-noindex', $this->item->ID );

		if ( $item_noindex === '1' ) {
			return true;
		}

		if ( $item_noindex === '0' && WPSEO_Options::get( 'noindex-' . $this->item->post_type ) === true ) {
			return true;
		}

		// Check the specific WordPress SEO News no-index value.
		if ( WPSEO_Meta::get_value( 'newssitemap-robots-index', $this->item->ID ) === '1' ) {
			return true;
		}

		if ( $this->exclude_item_terms() ) {
			return true;
		}

		return false;
	}

	/**
	 * Exclude the item when one of his terms is excluded.
	 *
	 * @return bool
	 */
	private function exclude_item_terms() {
		foreach ( $this->get_terms_for_item() as $term ) {
			if ( isset( $this->options[ 'term_exclude_' . $term->taxonomy . '_' . $term->slug ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieve all the Term IDs for all the items.
	 *
	 * @return array
	 */
	private function get_terms_for_item() {
		$terms = array();
		foreach ( get_object_taxonomies( $this->item->post_type ) as $taxonomy ) {
			$extra_terms = get_the_terms( $this->item->ID, $taxonomy );
			if ( is_array( $extra_terms ) && count( $extra_terms ) > 0 ) {
				$terms = array_merge( $terms, $extra_terms );
			}
		}

		return $terms;
	}

	/**
	 * Building each sitemap item.
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
	 * Building the news tag.
	 */
	private function build_news_tag() {

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

		if ( ! empty( $stock_tickers ) ) {
			$this->output .= "\t\t<news:stock_tickers><![CDATA[" . $stock_tickers . ']]></news:stock_tickers>' . "\n";
		}

		$this->output .= "\t</news:news>\n";
	}

	/**
	 * Builds the publication tag.
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
	 * Getting the genre for given $item_id.
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
	 * Getting the publication language.
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
	 * Parses the $item argument into an xml format.
	 *
	 * @param WP_Post $item Object to get data from.
	 *
	 * @return string
	 */
	protected function get_publication_date( $item ) {
		if ( $this->is_valid_datetime( $item->post_date_gmt ) ) {
			return $this->format_date_with_timezone( $item->post_date_gmt, 'UTC' );
		}

		// Fallback: post_date.
		if ( $this->is_valid_datetime( $item->post_date ) ) {
			return $this->format_date_with_timezone( $item->post_date, new WPSEO_News_Sitemap_Timezone() );
		}

		return '';
	}

	/**
	 * Format a date string with a timezone.
	 *
	 * @param string                                $item_date Date to parse.
	 * @param string || WPSEO_News_Sitemap_Timezone $time_zone Timezone to parse.
	 *
	 * @return string The formatted date string.
	 */
	private function format_date_with_timezone( $item_date, $time_zone ) {

		// Create a DateTime object date in the correct timezone.
		$datetime = new DateTime( $item_date, new DateTimeZone( $time_zone ) );

		return $datetime->format( $this->get_date_format() );
	}

	/**
	 * Checks if a DateTimeZone string is usable, either as a known timezone or as a numeric entry.
	 *
	 * @param string $timezone_string DateTimeZone string to check.
	 *
	 * @return bool
	 */
	private function is_timezone_usable( $timezone_string ) {
		if ( $timezone_string === '' ) {
			return false;
		}

		if ( in_array( $timezone_string, DateTimeZone::listIdentifiers(), true ) ) {
			return true;
		}

		// Checks if the string matches a valid numeric DateTimeZone, for example "+0200".
		return ( preg_match( '/[+-][0-9]{4}/', $timezone_string ) === 1 );
	}

	/**
	 * When the timezone string option in WordPress is empty, just return YYYY-MM-DD as format.
	 *
	 * @return string
	 */
	private function get_date_format() {
		static $timezone_format;

		if ( $timezone_format === null ) {
			// Set a default.
			$timezone_format = 'Y-m-d';

			// Get the timezone string.
			$timezone_option = new WPSEO_News_Sitemap_Timezone();
			$timezone_string = $timezone_option->wp_get_timezone_string();

			// Is there a usable timezone string and does it exist in the list of 'valid' timezones.
			if ( $this->is_timezone_usable( $timezone_string ) ) {
				$timezone_format = DateTime::W3C;
			}
		}

		return $timezone_format;
	}

	/**
	 * Getting the stock_tickers for given $item_id.
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
	 * Getting the images for current item.
	 */
	private function get_item_images() {
		$this->output .= new WPSEO_News_Sitemap_Images( $this->item, $this->options );
	}

	/**
	 * Wrapper function to check if we have a valid datetime (Uses a new util in WPSEO).
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
