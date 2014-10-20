<?php namespace cahnrswp\cahnrs\categories;
/**
 * Custom meta data inputs
 */

class metabox_control {


	public function __construct() {

		\add_action( 'admin_menu', array( $this, 'move_meta_box' ) );
		//\add_action( 'save_post',  array( $this, 'save' )          );

	}


	// Add the meta box containers
	public function move_meta_box() {

		global $wp_taxonomies;

		$post_types = $wp_taxonomies['cahnrs-category']->object_type;

		foreach( $post_types as $post_type ) {
			\remove_meta_box( 'cahnrs-categorydiv', $post_type, 'side' );
			\add_meta_box( 'cahnrs-categorydiv', 'Content Settings', array( $this, 'cahnrs_page_settings_interface' ), $post_type, 'normal', 'high' );
		}

	}


	// Custom interface
	public function cahnrs_page_settings_interface( $args = array(), $post ) {

		// Use same noncename as default box to remove need for save_post hook
		\wp_nonce_field( 'taxonomy_cahnrs-category', 'taxonomy_noncename' );

		echo '<h4>CAHNRS Categories</h4>';

		$all_terms = \wp_get_object_terms( \get_the_ID(), 'cahnrs-category' );
		$selected = ( ! \is_wp_error( $all_terms ) && ! empty( $all_terms ) ) ? (array) \wp_list_pluck( $all_terms, 'term_id' ) : array();

		// Context
		$context = \get_term_by( 'slug', 'context', 'cahnrs-category' );
		$context_terms = \get_terms( 'cahnrs-category', array( 'hide_empty' => false, 'parent' => $context->term_id ) );
		if( $context_terms && ! \is_wp_error( $context_terms ) ) {
			echo '<ul id="cahnrs-context">';
			echo '<li class="label">' . $context->name . '</li>';
			foreach( $context_terms as $term ) {
				$this->checkbox( $term->term_id, $term->name, $selected );
			}
			echo '</ul>';
		}

		echo '<ul id="cahnrs-category-tabs">';

		// Topics
		$topics = \get_term_by( 'slug', 'topics', 'cahnrs-category' );
		echo '<li><span>' . $topics->name . '</span>';
		$this->item_tree( $topics->term_id, $selected );
		echo '</li><!--';

		// Departments
		$depts = \get_term_by( 'slug', 'departments', 'cahnrs-category' );
		echo '--><li><span>' . $depts->name . '</span>';
		$this->item_tree( $depts->term_id, $selected );
		echo '</li><!--';

		// Location
		$locations = \get_term_by( 'slug', 'locations', 'cahnrs-category' );
		echo '--><li><span>' . $locations->name . '</span>';
		$this->item_tree( $locations->term_id, $selected );
		echo '</li><!--';

		// Services and Activities
		$services = \get_term_by( 'slug', 'services-and-activities', 'cahnrs-category' );
		echo '--><li><span>' . $services->name . '</span>';
		$this->item_tree( $services->term_id, $selected );
		echo '</li>';

		echo '</ul>';

		// Update Frequency - I don't think we need this just yet
		/*\wp_nonce_field( 'cahnrs_update_frequency', 'cahnrs_update_frequency_nonce' );
		
		$update_frequency = \get_post_meta( get_the_ID(), '_cahnrs_update_frequency', true );

		if ( \get_post_type() != 'attachment' ) {
			echo '<h4>Update frequency</h4>';
			echo '<label>This content should be updated in:
			<select name="update-frequency">
				<option>N/A</option>
				<option value="monthly" ' . \selected( $update_frequency, 'monthly', false ) . '>one month</option>
				<option value="biannual" ' . \selected( $update_frequency, 'biannual', false ) . '>six months</option>
				<option value="annual" ' . \selected( $update_frequency, 'annual', false ) . '>one year</option>
			</select>
			</label>';
		}*/
		
		/* // Something like this in the content
		$difference = (int) ( current_time( 'Ymd' ) - get_the_modified_date( 'Ymd' ) );
		$update_frequency = get_post_meta( get_the_ID(), '_cahnrs_update_frequency', true );
		if (
			$update_frequency = 'monthly' && ( $difference > 100 ) ||
			$update_frequency = 'biannual' && ( $difference > 600 ) ||
			$update_frequency = 'annual' && ( $difference > 100) )
		) : ?>
		<div class="notice">  
			<p>This information was last updated on <?php the_modified_date( 'm/d/Y' ); ?>, and may be out of date</p>
		</div>
		<?php endif; ?>
		*/

	}


	// Item trees
	public function item_tree( $parent, $selected ) {

		$terms = \get_terms( 'cahnrs-category', array( 'hide_empty' => false, 'parent' => $parent ) );
		if( $terms && ! \is_wp_error( $terms ) ) {
			echo '<ul>';
			foreach( $terms as $term ) {
				echo '<li class="cahnrs-category-toggle"><span>' . $term->name . '</span>';
				$children = \get_terms( 'cahnrs-category', array( 'hide_empty' => false, 'parent' => $term->term_id ) );
				if( $children && ! \is_wp_error( $children ) ) {
					echo '<ul>';
					foreach( $children as $child ) {
						$this->checkbox( $child->term_id, $child->name, $selected );
					}
					echo '</ul>';
				}
				echo '</li>';
			}
			echo '</ul>';
		}

	}


	// Checkboxes
	public function checkbox( $id, $name, $selected ) {

		echo '<li id="cahnrs-category-' . \esc_attr( $id ) . '"><label class="selectit">';
		echo '<input value="' . \esc_attr( $id ) . '" type="checkbox" name="tax_input[cahnrs-category][]" id="in-cahnrs-category-' . \esc_attr( $id ) . '"';
		\checked( ! empty( $selected ) && in_array( $id, $selected ) );
		echo '"/> ' . $name . '</label>';
		echo '</li>';

	}


	// Save the meta when the post is saved
	public function save( $post_id ) {

		// Verify this came from our screen with proper authorization:

		// Check if our nonce is set
		if ( ! isset( $_POST['cahnrs_update_frequency_nonce'] ) )
			return $post_id;

		$nonce = $_POST['cahnrs_update_frequency_nonce'];

		// Verify that the nonce is valid
		if ( ! wp_verify_nonce( $nonce, 'cahnrs_update_frequency' ) )
			return $post_id;

		// If this is an autosave, the form has not been submitted, so don't do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// The user has the capability to edit posts
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return $post_id;

		// Sanitize and save the data:
		if ( isset( $_POST['update-frequency'] ) ) {
			\update_post_meta( $post_id, '_cahnrs_update_frequency', \sanitize_text_field( $_POST['update-frequency'] ) );
    } else {
			\delete_post_meta( $post_id, '_cahnrs_update_frequency' );
    }

	}


}