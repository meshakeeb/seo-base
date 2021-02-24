<?php
/**
 * The Misc.
 *
 * @package    NHG
 * @subpackage NHG\SEO\Variables
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Variables;

defined( 'ABSPATH' ) || exit;

/**
 * Misc.
 */
class Misc {

	/**
	 * Get the separator to use as a replacement.
	 *
	 * @return string
	 */
	public function get_sep() {
		return '-';
	}

	/**
	 * Get the site name to use as a replacement.
	 *
	 * @return string|null
	 */
	public function get_sitename() {
		return wp_strip_all_tags( get_bloginfo( 'name' ), true );
	}

	/**
	 * Get the current page number (i.e. "page 2 of 4") to use as a replacement.
	 *
	 * @return string
	 */
	public function get_page() {
		$sep  = $this->get_sep();
		$max  = $this->determine_max_pages();
		$page = $this->determine_page_number();

		if ( $max > 1 && $page > 1 ) {
			/* translators: 1: current page number, 2: total number of pages. */
			return sprintf( $sep . ' ' . __( 'Page %1$d of %2$d', 'nhg-seo' ), $page, $max );
		}

		return null;
	}

	/**
	 * Get search query.
	 *
	 * @return string
	 */
	public function get_searchphrase() {
		return get_search_query();
	}

	/**
	 * Determine the page number of the current post/page/CPT.
	 *
	 * @return int|null
	 */
	protected function determine_page_number() {
		$page_number = is_singular() ? get_query_var( 'page' ) : get_query_var( 'paged' );
		if ( 0 === $page_number || '' === $page_number ) {
			return 1;
		}

		return $page_number;
	}

	/**
	 * Determine the max num of pages of the current post/page/CPT.
	 *
	 * @return int|null
	 */
	protected function determine_max_pages() {
		global $wp_query, $post;
		if ( is_singular() && isset( $post->post_content ) ) {
			return ( substr_count( $post->post_content, '<!--nextpage-->' ) + 1 );
		}

		return empty( $wp_query->max_num_pages ) ? 1 : $wp_query->max_num_pages;
	}
}
