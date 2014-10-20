<?php namespace cahnrswp\cahnrs\categories;
/**
 * Enqueue CSS and Javascript
 */

class scripts_control {


	public function __construct() {

		\add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

	}


	/**
	 * Enqueue styles to be used for the display of taxonomy terms.
	 *
	 * @param string $hook Hook indicating the current admin page.
	 */
	public function admin_enqueue_scripts( $hook ) {

		if( 'edit-tags.php' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook )
			return;

		if( 'cahnrs-category' === get_current_screen()->taxonomy )
			\wp_enqueue_style( 'news-release-style', URL . 'css/edit-tags-style.css', array() );

		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			\wp_enqueue_style( 'news-release-style',   URL . 'css/edit-post.css', array() );
			\wp_enqueue_script( 'news-release-script', URL . 'js/edit-post.js',   array() );
		}

	}


}