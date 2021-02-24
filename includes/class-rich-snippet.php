<?php
/**
 * The Rich Snippet.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Rich Snippet.
 */
class Rich_Snippet {

	/**
	 * Stores the structured data.
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Registers functionality through WordPress hooks.
	 */
	public function hooks() {
		add_action( 'nhg_seo_head', [ $this, 'add_structured_data' ], 99 );
	}

	/**
	 * Sets data.
	 *
	 * @param  array $data Structured data.
	 * @return bool
	 */
	public function set_data( $data ) {
		if ( ! isset( $data['@type'] ) ) {
			return false;
		}

		$this->data[] = $data;

		return true;
	}

	/**
	 * Add breadcrumn as JSON only.
	 */
	public function add_structured_data() {

		do_action( 'nhg_seo_ld_json', $this );

		if ( is_array( $this->data ) && ! empty( $this->data ) ) {
			$json = [
				'@context' => 'https://schema.org/',
				'@graph'   => $this->data,
			];

			echo '<script type="application/ld+json">' . wc_esc_json( wp_json_encode( $json ), true ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
