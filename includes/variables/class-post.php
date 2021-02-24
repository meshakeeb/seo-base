<?php
/**
 * The Post.
 *
 * @package    NHG
 * @subpackage NHG\SEO\Variables
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Variables;

defined( 'ABSPATH' ) || exit;

/**
 * Post.
 */
class Post extends Term {

	/**
	 * Get the title of the post to use as a replacement.
	 *
	 * @return string|null
	 */
	public function get_title() {
		return '' !== $this->args->post_title ? stripslashes( $this->args->post_title ) : null;
	}

	/**
	 * Get the post excerpt to use as a replacement. It will be auto-generated if it does not exist.
	 *
	 * @return string|null
	 */
	public function get_excerpt() {
		$object = $this->args;

		// Early Bail!
		if ( empty( $object ) || empty( $object->post_excerpt ) ) {
			return '';
		}

		return $object->post_excerpt;
	}
}
