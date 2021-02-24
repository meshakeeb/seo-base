<?php
/**
 * The Metabox.
 *
 * @package    NHG
 * @subpackage NHG\SEO\Admin
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Admin;

use NHG\SEO\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Metabox.
 */
class Metabox {

	/**
	 * Allowed post types.
	 *
	 * @var array
	 */
	private $post_allowed = [
		'post',
		'page',
		'product',
	];

	/**
	 * Allowed taxonomies.
	 *
	 * @var array
	 */
	private $taxonomy_allowed = [
		'product_cat',
	];

	/**
	 * Registers functionality through WordPress hooks.
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', [ $this, 'add_boxes' ] );
		add_action( 'save_post', [ $this, 'save' ] );

		foreach ( $this->taxonomy_allowed as $taxonomy ) {
			add_action( "{$taxonomy}_edit_form_fields", [ $this, 'display_taxonomy_boxes' ], 99 );
			add_action( "edited_{$taxonomy}", [ $this, 'save_taxonomy' ] );
		}
	}

	/**
	 * Add boxes
	 *
	 * @param  string $post_type Post type.
	 */
	public function add_boxes( $post_type ) {
		if ( ! in_array( $post_type, $this->post_allowed, true ) ) {
			return;
		}

		add_meta_box(
			'nhg_seo_metabox',
			esc_html__( 'SEO metadata', 'nhg-seo' ),
			[ $this, 'display' ],
			$post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Display Metabox.
	 *
	 * @param  WP_Post $post Post instance.
	 */
	public function display( $post ) {
		wp_nonce_field( 'nhg_seo_metadata', 'nhg_seo_security' );

		$title       = get_post_meta( $post->ID, '_nhg_seo_title', true );
		$robots      = get_post_meta( $post->ID, '_nhg_seo_robots', true );
		$description = get_post_meta( $post->ID, '_nhg_seo_description', true );
		?>
		<p class="form-field">
			<label for="_nhg_seo_title"><strong>SEO Title</strong></label>
			<br>
			<input type="text" name="_nhg_seo_title" id="_nhg_seo_title" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p class="form-field">
			<label for="_nhg_seo_description"><strong>SEO Description</strong></label>
			<br>
			<textarea name="_nhg_seo_description" id="_nhg_seo_description"><?php echo esc_textarea( $description ); ?></textarea>
		</p>

		<p class="form-field">
			<label for="_nhg_seo_robots"><strong>SEO Robots</strong></label>
			<br>
			<select name="_nhg_seo_robots" id="_nhg_seo_robots" class="select short">
				<option value="index"<?php selected( 'index', $robots ); ?>>Index</option>
				<option value="noindex"<?php selected( 'noindex', $robots ); ?>>No-Index</option>
			</select>
		</p>

		<?php
		if ( 'product' === get_post_type( $post ) ) :
			wp_enqueue_script(
				'nhg-seo-term-selector',
				Plugin::get()->url . 'assets/js/term-selector.js',
				[ 'jquery' ],
				'1.0.0',
				true
			);
			$term_id = get_post_meta( $post->ID, '_nhg_seo_primary_term', true );
			$term_id = $term_id ? $term_id : 0;
			?>
		<p class="form-field">
			<label for="_nhg_seo_primary_term"><strong>Primary Category</strong></label>
			<br>
			<select name="_nhg_seo_primary_term" id="_nhg_seo_primary_term" class="select short js-primary-selector" data-selected="<?php echo esc_attr( $term_id ); ?>" data-taxonomy="product_cat">
			</select>
		</p>
			<?php
		endif;
	}

	/**
	 * Display Metabox.
	 *
	 * @param  WP_Term $term Term instance.
	 */
	public function display_taxonomy_boxes( $term ) {
		wp_nonce_field( 'nhg_seo_metadata', 'nhg_seo_security' );

		$title       = get_term_meta( $term->term_id, '_nhg_seo_title', true );
		$robots      = get_term_meta( $term->term_id, '_nhg_seo_robots', true );
		$description = get_term_meta( $term->term_id, '_nhg_seo_description', true );
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="_nhg_seo_title"><strong>SEO Title</strong></label>
			</th>
			<td>
				<input type="text" name="_nhg_seo_title" id="_nhg_seo_title" value="<?php echo esc_attr( $title ); ?>">
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="_nhg_seo_description"><strong>SEO Description</strong></label>
			</th>
			<td>
				<textarea name="_nhg_seo_description" id="_nhg_seo_description"><?php echo esc_textarea( $description ); ?></textarea>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="_nhg_seo_robots"><strong>SEO Robots</strong></label>
			</th>
			<td>
				<select name="_nhg_seo_robots" id="_nhg_seo_robots" class="select short">
					<option value="index"<?php selected( 'index', $robots ); ?>>Index</option>
					<option value="noindex"<?php selected( 'noindex', $robots ); ?>>No-Index</option>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['nhg_seo_security'] ) ) {
			return $post_id;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['nhg_seo_security'], 'nhg_seo_metadata' ) ) {
			return $post_id;
		}

		/*
		* If this is an autosave, our form has not been submitted,
		* so we don't want to do anything.
		*/
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		$post_type = get_post_type( $post_id );
		// This metabox is to be displayed for a certain post types only.
		if ( ! in_array( $post_type, $this->post_allowed, true ) ) {
			return $post_id;
		}

		// Check the user's permissions.
		$post_type_object = get_post_type_object( $post_type );
		if ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		$title = sanitize_text_field( $_POST['_nhg_seo_title'] );
		if ( empty( $title ) ) {
			delete_post_meta( $post_id, '_nhg_seo_title' );
		} else {
			update_post_meta( $post_id, '_nhg_seo_title', $title );
		}

		$description = sanitize_textarea_field( $_POST['_nhg_seo_description'] );
		if ( empty( $description ) ) {
			delete_post_meta( $post_id, '_nhg_seo_description' );
		} else {
			update_post_meta( $post_id, '_nhg_seo_description', $description );
		}

		$robots = sanitize_text_field( $_POST['_nhg_seo_robots'] );
		if ( empty( $robots ) ) {
			delete_post_meta( $post_id, '_nhg_seo_robots' );
		} else {
			update_post_meta( $post_id, '_nhg_seo_robots', $robots );
		}

		if ( 'product' === $post_type ) {
			$term_id = sanitize_text_field( $_POST['_nhg_seo_primary_term'] );
			if ( empty( $term_id ) ) {
				delete_post_meta( $post_id, '_nhg_seo_primary_term' );
			} else {
				update_post_meta( $post_id, '_nhg_seo_primary_term', $term_id );
			}
		}
	}

	/**
	 * Save the meta when the term is saved.
	 *
	 * @param int $term_id The ID of the post being saved.
	 */
	public function save_taxonomy( $term_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['nhg_seo_security'] ) ) {
			return $term_id;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['nhg_seo_security'], 'nhg_seo_metadata' ) ) {
			return $term_id;
		}

		$title = sanitize_text_field( $_POST['_nhg_seo_title'] );
		if ( empty( $title ) ) {
			delete_term_meta( $term_id, '_nhg_seo_title' );
		} else {
			update_term_meta( $term_id, '_nhg_seo_title', $title );
		}

		$description = sanitize_textarea_field( $_POST['_nhg_seo_description'] );
		if ( empty( $description ) ) {
			delete_term_meta( $term_id, '_nhg_seo_description' );
		} else {
			update_term_meta( $term_id, '_nhg_seo_description', $description );
		}

		$robots = sanitize_text_field( $_POST['_nhg_seo_robots'] );
		if ( empty( $robots ) ) {
			delete_term_meta( $term_id, '_nhg_seo_robots' );
		} else {
			update_term_meta( $term_id, '_nhg_seo_robots', $robots );
		}
	}
}
