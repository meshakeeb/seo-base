<?php
/**
 * The Error404 Document
 *
 * @package    NHG
 * @subpackage NHG\SEO\Document
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Document;

defined( 'ABSPATH' ) || exit;

/**
 * 404 Error.
 */
class Error404 implements IDocument {

	/**
	 * Retrieves the SEO title.
	 *
	 * @return string
	 */
	public function title() {
		return esc_html__( 'Page not found', 'nhg-seo' );
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
		return [ 'index' => 'noindex' ];
	}

	/**
	 * Retrieves the canonical URL.
	 *
	 * @return array
	 */
	public function canonical() {
		return [];
	}
}
