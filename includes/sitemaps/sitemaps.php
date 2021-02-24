<?php
/**
 * Sitemaps: Public functions
 *
 * This file contains a variety of public functions developers can use to interact with
 * the XML Sitemaps API.
 *
 * @package WordPress
 * @subpackage Sitemaps
 * @since 5.5.0
 */

/**
 * Escaping for XML blocks.
 *
 * @param string $text Text to escape.
 * @return string Escaped text.
 */
function esc_xml( $text ) {
	$safe_text = wp_check_invalid_utf8( $text );

	$cdata_regex = '\<\!\[CDATA\[.*?\]\]\>';
	$regex       = <<<EOF
/
	(?=.*?{$cdata_regex})                 # lookahead that will match anything followed by a CDATA Section
	(?<non_cdata_followed_by_cdata>(.*?)) # the "anything" matched by the lookahead
	(?<cdata>({$cdata_regex}))            # the CDATA Section matched by the lookahead

|                                         # alternative

	(?<non_cdata>(.*))                    # non-CDATA Section
/sx
EOF;

	$safe_text = (string) preg_replace_callback(
		$regex,
		static function( $matches ) {
			if ( ! $matches[0] ) {
				return '';
			}

			if ( ! empty( $matches['non_cdata'] ) ) {
				// escape HTML entities in the non-CDATA Section.
				return _wp_specialchars( $matches['non_cdata'], ENT_XML1 );
			}

			// Return the CDATA Section unchanged, escape HTML entities in the rest.
			return _wp_specialchars( $matches['non_cdata_followed_by_cdata'], ENT_XML1 ) . $matches['cdata'];
		},
		$safe_text
	);

	/**
	* Filters a string cleaned and escaped for output in XML.
	*
	* Text passed to esc_xml() is stripped of invalid or special characters
	* before output. HTML named character references are converted to their
	* equivalent code points.
	*
	* @since 5.5.0
	*
	* @param string $safe_text The text after it has been escaped.
	* @param string $text      The text prior to being escaped.
	*/
	return apply_filters( 'esc_xml', $safe_text, $text );
}

/**
 * Retrieves the current Sitemaps server instance.
 *
 * @since 5.5.0
 *
 * @global WP_Sitemaps $wp_sitemaps Global Core Sitemaps instance.
 *
 * @return WP_Sitemaps Sitemaps instance.
 */
function wp_sitemaps_get_server() {
	global $wp_sitemaps;

	// If there isn't a global instance, set and bootstrap the sitemaps system.
	if ( empty( $wp_sitemaps ) ) {
		$wp_sitemaps = new WP_Sitemaps();
		$wp_sitemaps->init();

		/**
		 * Fires when initializing the Sitemaps object.
		 *
		 * Additional sitemaps should be registered on this hook.
		 *
		 * @since 5.5.0
		 *
		 * @param WP_Sitemaps $wp_sitemaps Sitemaps object.
		 */
		do_action( 'wp_sitemaps_init', $wp_sitemaps );
	}

	return $wp_sitemaps;
}

/**
 * Gets an array of sitemap providers.
 *
 * @since 5.5.0
 *
 * @return WP_Sitemaps_Provider[] Array of sitemap providers.
 */
function wp_get_sitemap_providers() {
	$sitemaps = wp_sitemaps_get_server();
	return $sitemaps->registry->get_providers();
}

/**
 * Registers a new sitemap provider.
 *
 * @since 5.5.0
 *
 * @param string               $name     Unique name for the sitemap provider.
 * @param WP_Sitemaps_Provider $provider The `Sitemaps_Provider` instance implementing the sitemap.
 * @return bool Whether the sitemap was added.
 */
function wp_register_sitemap_provider( $name, WP_Sitemaps_Provider $provider ) {
	$sitemaps = wp_sitemaps_get_server();
	return $sitemaps->registry->add_provider( $name, $provider );
}

/**
 * Gets the maximum number of URLs for a sitemap.
 *
 * @since 5.5.0
 *
 * @param string $object_type Object type for sitemap to be filtered (e.g. 'post', 'term', 'user').
 * @return int The maximum number of URLs.
 */
function wp_sitemaps_get_max_urls( $object_type ) {
	/**
	 * Filters the maximum number of URLs displayed on a sitemap.
	 *
	 * @since 5.5.0
	 *
	 * @param int    $max_urls    The maximum number of URLs included in a sitemap. Default 2000.
	 * @param string $object_type Object type for sitemap to be filtered (e.g. 'post', 'term', 'user').
	 */
	return apply_filters( 'wp_sitemaps_max_urls', 2000, $object_type );
}

/**
 * Retrieves the full URL for a sitemap.
 *
 * @since 5.5.1
 *
 * @param string $name         The sitemap name.
 * @param string $subtype_name The sitemap subtype name.  Default empty string.
 * @param int    $page         The page of the sitemap.  Default 1.
 * @return string|false The sitemap URL or false if the sitemap doesn't exist.
 */
function get_sitemap_url( $name, $subtype_name = '', $page = 1 ) {
	$sitemaps = wp_sitemaps_get_server();
	if ( ! $sitemaps ) {
		return false;
	}

	if ( 'index' === $name ) {
		return $sitemaps->index->get_index_url();
	}

	$provider = $sitemaps->registry->get_provider( $name );
	if ( ! $provider ) {
		return false;
	}

	if ( $subtype_name && ! in_array( $subtype_name, array_keys( $provider->get_object_subtypes() ), true ) ) {
		return false;
	}

	$page = absint( $page );
	if ( 0 >= $page ) {
		$page = 1;
	}
	return $provider->get_sitemap_url( $subtype_name, $page );
}
