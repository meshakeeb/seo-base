<?php
/**
 * The Document Interface
 *
 * @package    NHG
 * @subpackage NHG\SEO\Document
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Document interface.
 */
interface IDocument {

	/**
	 * Retrieves the SEO title.
	 *
	 * @return string
	 */
	public function title();

	/**
	 * Retrieves the SEO description.
	 *
	 * @return string
	 */
	public function description();

	/**
	 * Retrieves the robots.
	 *
	 * @return string
	 */
	public function robots();

	/**
	 * Retrieves the canonical URL.
	 *
	 * @return array
	 */
	public function canonical();
}
