<?php
/**
 * The Search Document
 *
 * @package    NHG
 * @subpackage NHG\SEO\Document
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Search results.
 */
class Search implements IDocument {

	/**
	 * Retrieves the SEO title.
	 *
	 * @return string
	 */
	public function title() {
		return Strategy::get_others( 'title', 'search' );
	}

	/**
	 * Retrieves the SEO description.
	 *
	 * @return string
	 */
	public function description() {
		return '';
	}

	/**
	 * Retrieves the robots.
	 *
	 * @return string
	 */
	public function robots() {
		return [];
	}

	/**
	 * Retrieves the canonical URL.
	 *
	 * @return array
	 */
	public function canonical() {
		$search_query = get_search_query();
		return [ 'canonical' => ! empty( $search_query ) && ! preg_match( '|^page/\d+$|', $search_query ) ? get_search_link() : '' ];
	}
}
