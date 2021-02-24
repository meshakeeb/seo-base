<?php
/**
 * The Helper.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Helper.
 */
class Helper {

	/**
	 * Checks if the current page is the WooCommerce "Shop" page.
	 *
	 * @return bool Whether the current page is the shop page.
	 */
	public static function is_shop_page() {
		if ( function_exists( 'is_shop' ) && function_exists( 'wc_get_page_id' ) ) {
			return is_shop() && ! is_search();
		}

		return false;
	}

	/**
	 * Returns the ID of the selected WooCommerce shop page.
	 *
	 * @return int The ID of the Shop page.
	 */
	public static function get_shop_page_id() {
		static $shop_page_id;
		if ( ! $shop_page_id ) {
			$shop_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : -1;
		}

		return $shop_page_id;
	}

	/**
	 * Check whether this is the static frontpage.
	 *
	 * @return bool
	 */
	public static function is_home_static_page() {
		return ( is_front_page() && 'page' === get_option( 'show_on_front' ) && is_page( get_option( 'page_on_front' ) ) );
	}

	/**
	 * Check if this is the posts page and that it's not the frontpage.
	 *
	 * @return bool
	 */
	public static function is_posts_page() {
		return ( is_home() && 'page' === get_option( 'show_on_front' ) );
	}

	/**
	 * Checks if the current page is a simple page.
	 *
	 * @return bool Whether the current page is a simple page.
	 */
	public static function is_simple_page() {
		return self::get_simple_page_id() > 0;
	}

	/**
	 * Get the ID of the current page.
	 *
	 * @return int The ID of the page.
	 */
	public static function get_simple_page_id() {
		if ( is_singular() ) {
			return get_the_ID();
		}

		if ( self::is_posts_page() ) {
			return get_option( 'page_for_posts' );
		}

		if ( self::is_shop_page() ) {
			return self::get_shop_page_id();
		}

		return 0;
	}

	/**
	 * Get taxonomies attached to a post type.
	 *
	 * @param  string  $post_type Post type to get taxonomy data for.
	 * @param  string  $output    (Optional) Output type can be `names`, `objects`.
	 * @param  boolean $filter    (Optional) Whether to filter taxonomies.
	 * @return boolean|array
	 */
	public static function get_object_taxonomies( $post_type, $output = 'objects', $filter = true ) {
		if ( 'names' === $output ) {
			return get_object_taxonomies( $post_type );
		}

		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		$taxonomies = self::filter_exclude_taxonomies( $taxonomies, $filter );

		return $taxonomies;
	}

	/**
	 * Filter taxonomies using
	 *        `is_taxonomy_viewable` function
	 *
	 * @param  array|object $taxonomies Collection of taxonomies to filter.
	 * @param  boolean      $filter     (Optional) Whether to filter taxonomies.
	 * @return array|object
	 */
	public static function filter_exclude_taxonomies( $taxonomies, $filter = true ) {
		$taxonomies = $filter ? array_filter( $taxonomies, [ __CLASS__, 'is_taxonomy_viewable' ] ) : $taxonomies;

		return $taxonomies;
	}

	/**
	 * Determine whether a taxonomy is viewable.
	 *
	 * @param  string|WP_Taxonomy $taxonomy Taxonomy name or object.
	 * @return bool
	 */
	public static function is_taxonomy_viewable( $taxonomy ) {
		if ( is_scalar( $taxonomy ) ) {
			$taxonomy = get_taxonomy( $taxonomy );
			if ( ! $taxonomy ) {
				return false;
			}
		}

		/*
		 * For categories and tags, we check for the 'public' parameter.
		 * For others, we use the 'publicly_queryable' parameter.
		 */
		return $taxonomy->publicly_queryable || ( $taxonomy->_builtin && $taxonomy->public );
	}

	/**
	 * Check whether a URL is relative.
	 *
	 * @param  string $url URL string to check.
	 * @return bool
	 */
	public static function is_relative( $url ) {
		return ( 0 !== strpos( $url, 'http' ) && 0 !== strpos( $url, '//' ) );
	}

	/**
	 * Truncate text for given length.
	 *
	 * @param  string $str    Text to truncate.
	 * @param  int    $length Length to truncate for.
	 * @return string Truncated text.
	 */
	public static function truncate( $str, $length = 110 ) {
		$str     = wp_strip_all_tags( $str, true );
		$excerpt = mb_substr( $str, 0, $length );

		// Remove part of an entity at the end.
		$excerpt = preg_replace( '/&[^;\s]{0,6}$/', '', $excerpt );
		if ( $str !== $excerpt ) {
			$strrpos = function_exists( 'mb_strrpos' ) ? 'mb_strrpos' : 'strrpos';
			$excerpt = mb_substr( $str, 0, $strrpos( trim( $excerpt ), ' ' ) );
		}

		return $excerpt;
	}

	/**
	 * Check if the string contains the given value.
	 *
	 * @param  string $needle   The sub-string to search for.
	 * @param  string $haystack The string to search.
	 * @return bool
	 */
	public static function str_contains( $needle, $haystack ) {
		return '' !== $needle ? strpos( $haystack, $needle ) !== false : false;
	}

	/**
	 * Check if the string begins with the given value.
	 *
	 * @param  string $needle   The sub-string to search for.
	 * @param  string $haystack The string to search.
	 * @return bool
	 */
	public static function str_starts_with( $needle, $haystack ) {
		return '' === $needle || substr( $haystack, 0, strlen( $needle ) ) === (string) $needle;
	}

	/**
	 * Is WooCommerce product
	 *
	 * @return bool
	 */
	public static function is_product() {
		return function_exists( 'is_woocommerce' ) && is_product();
	}

	/**
	 * Find an attachment ID for a given URL.
	 *
	 * @param  string $url The URL to find the attachment for.
	 * @return int The found attachment ID, or 0 if none was found.
	 */
	public static function get_attachment_by_url( $url ) {
		// Because get_by_url won't work on resized versions of images, we strip out the size part of an image URL.
		$url = preg_replace( '/(.*)-\d+x\d+\.(jpg|png|gif)$/', '$1.$2', $url );

		$id = function_exists( 'wpcom_vip_attachment_url_to_postid' ) ? wpcom_vip_attachment_url_to_postid( $url ) : self::url_to_postid( $url );

		return absint( $id );
	}

	/**
	 * Implements the attachment_url_to_postid with use of WP Cache.
	 *
	 * @link https://dotlayer.com/20-wordpress-core-functions-that-dont-scale-and-how-to-work-around-it/
	 *
	 * @param  string $url The attachment URL for which we want to know the Post ID.
	 * @return int The Post ID belonging to the attachment, 0 if not found.
	 */
	private static function url_to_postid( $url ) {
		$cache_key = sprintf( 'nhg_attachment_url_post_id_%s', md5( $url ) );

		// Set the ID based on the hashed url in the cache.
		$id = wp_cache_get( $cache_key );

		if ( 'not_found' === $id ) {
			return 0;
		}

		// ID is found in cache, return.
		if ( false !== $id ) {
			return $id;
		}

		// phpcs:ignore WordPress.VIP.RestrictedFunctions -- We use the WP COM version if we can, see above.
		$id = attachment_url_to_postid( $url );

		if ( empty( $id ) ) {
			wp_cache_set( $cache_key, 'not_found', 'default', ( 12 * HOUR_IN_SECONDS + wp_rand( 0, ( 4 * HOUR_IN_SECONDS ) ) ) );
			return 0;
		}

		// We have the Post ID, but it's not in the cache yet. We do that here and return.
		wp_cache_set( $cache_key, $id, 'default', ( 24 * HOUR_IN_SECONDS + wp_rand( 0, ( 12 * HOUR_IN_SECONDS ) ) ) );

		return $id;
	}
}
