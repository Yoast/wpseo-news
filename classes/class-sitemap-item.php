<?php
/**
 * @package WPSEO_News\XML_Sitemaps
 */

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

		if ( 'post' === $this->item->post_type && $this->exclude_item_terms() ) {
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
		if ( $timezone_option !== '' && in_array( $timezone_option->wp_get_timezone_string(), DateTimeZone::listIdentifiers(), true ) ) {
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
