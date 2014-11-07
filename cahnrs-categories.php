<?php namespace cahnrswp\cahnrs\categories;
/**
 * Plugin Name: CAHNRS Categories
 * Plugin URI:  http://cahnrs.wsu.edu/communications/
 * Description: A taxonomy for CAHNRS
 * Version:     0.1
 * Author:      CAHNRS Communications, Phil Cable
 * Author URI:  http://cahnrs.wsu.edu/communications/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

class cahnrs_categories {

	public $init_taxonomies;

	// Fire necessary hooks when instantiated
	public function __construct() {

		$this->define_constants(); // Define constants
		$this->init_autoload(); // Activate custom autoloader for classes

	}


	private function define_constants() {

		define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ )  ); // Plugin base URL
		define( __NAMESPACE__ . '\DIR', plugin_dir_path( __FILE__ ) ); // Directory path

	}


	private function init_autoload() {

		require_once 'controls/autoload_control.php'; // Require autoloader control
		$autoload = new autoload_control(); // Init autoloader to eliminate further dependency on require

	}


	public function plugin() {

		$this->init_taxonomies = new taxonomies_control(); // Register taxonomies
		$init_scripts = new scripts_control(); // Enqueue scripts

		if ( \is_admin() )
			$init_metabox = new metabox_control(); // Custom display of CAHNRS Categories metabox

	}


}

$init_cahnrs_category = new cahnrs_categories();

$init_cahnrs_category->plugin();
/*$init_cahnrs_category->init_taxonomies , 'display_categories'*/
?>