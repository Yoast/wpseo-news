<?php
/**
 * @package WPSEO_News\XML_Sitemaps
 */

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
	public function wp_get_timezone_string() {

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
