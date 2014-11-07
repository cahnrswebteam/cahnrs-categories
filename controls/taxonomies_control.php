<?php namespace cahnrswp\cahnrs\categories;
/*
 * Register taxonomies
 */

class taxonomies_control {


	public function __construct() {

		\add_action( 'init',               array( $this, 'register_taxonomies' ), 11 );
		\add_action( 'load-edit-tags.php', array( $this, 'compare_categories' ),  10 );
		\add_action( 'load-edit-tags.php', array( $this, 'display_categories' ),  11 );
		
	}


	public function register_taxonomies() {

		$this->cahnrs_categories();

	}


	// Definitions for CAHNRS Categories
	private function cahnrs_categories() {

		$labels = array(
			'name'                       => 'CAHNRS Categories',
			'singular_name'              => 'CAHNRS Category',
			'menu_name'                  => 'CAHNRS Categories',
			'all_items'                  => 'All CAHNRS Categories',
			'parent_item'                => 'Parent CAHNRS Category',
			'parent_item_colon'          => 'Parent CAHNRS Category:',
			'new_item_name'              => 'New CAHNRS Category Name',
			'add_new_item'               => 'Add New CAHNRS Category',
			'edit_item'                  => 'Edit CAHNRS Category',
			'update_item'                => 'Update CAHNRS Category',
			'search_items'               => 'Search CAHNRS Category',
			'add_or_remove_items'        => 'Add or remove CAHNRS Categories',
			'choose_from_most_used'      => 'Choose from the most used CAHNRS Categories',
			'not_found'                  => 'Not Found',
		);

		$args = array(
			'labels'                     => $labels,
			'description'                => 'The central taxonomy for WSU\'s College of Agricultural, Human, and Natural Resource Sciences',
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => false,
			'show_in_menu'               => true,
			'show_tagcloud'              => false,
			//'rewrite'                    => false,
			'query_var'                  => 'cahnrs-category',
		);

		register_taxonomy( 'cahnrs-category', array( 'post', 'attachment' ), $args );

	}


	/**
	 * Clear all cache for a given taxonomy.
	 *
	 * @param string $taxonomy A taxonomy slug.
	 */
	private function clear_taxonomy_cache( $taxonomy ) {

		wp_cache_delete( 'all_ids', $taxonomy );
		wp_cache_delete( 'get',     $taxonomy );
		delete_option( $taxonomy . '_children' );
		_get_term_hierarchy( $taxonomy );

	}


	// Compare the current state of categories and populate anything that is missing.
	public function compare_categories() {

		if( 'cahnrs-category' !== get_current_screen()->taxonomy )
			return;

		$this->clear_taxonomy_cache( 'cahnrs-category' );

		// Get our current master list of categories.
		$master_list = $this->get_cahnrs_categories();

		// Get our current list of top level parents.
		$level1_exist  = get_terms( 'cahnrs-category', array( 'hide_empty' => false, 'parent' => '0' ) );
		$level1_assign = array();
		foreach( $level1_exist as $level1 ) {
			$level1_assign[ $level1->name ] = array( 'term_id' => $level1->term_id );
		}

		$level1_names = array_keys( $master_list );
		/**
		 * Look for mismatches between the master list and the existing parent terms list.
		 *
		 * In this loop:
		 *
		 *     * $level1_names    array of top level parent names.
		 *     * $level1_name     string containing a top level category.
		 *     * $level1_children array containing all of the current parent's child arrays.
		 *     * $level1_assign   array of top level parents that exist in the database with term ids.
		 */
		foreach( $level1_names as $level1_name ) {
			if( ! array_key_exists( $level1_name, $level1_assign ) ) {
				$new_term = wp_insert_term( $level1_name, 'cahnrs-category', array( 'parent' => '0' ) );
				if( ! is_wp_error( $new_term ) ) {
					$level1_assign[ $level1_name ] = array( 'term_id' => $new_term['term_id'] );
				}
			}
		}

		/**
		 * Process the children of each top level parent.
		 *
		 * In this loop:
		 *
		 *     * $level1_names    array of top level parent names.
		 *     * $level1_name     string containing a top level category.
		 *     * $level1_children array containing all of the current parent's child arrays.
		 *     * $level2_assign   array of this parent's second level categories that exist in the database with term ids.
		 */
		foreach( $level1_names as $level1_name ) {
			$level2_exists = get_terms( 'cahnrs-category', array( 'hide_empty' => false, 'parent' => $level1_assign[ $level1_name ]['term_id'] ) );
			$level2_assign = array();

			foreach( $level2_exists as $level2 ) {
				$level2_assign[ $level2->name ] = array( 'term_id' =>  $level2->term_id );
			}

			$level2_names = array_keys( $master_list[ $level1_name ] );
			/**
			 * Look for mismatches between the expected and real children of the current parent.
			 *
			 * In this loop:
			 *
			 *     * $level2_names    array of the current parent's child level names.
			 *     * $level2_name     string containing a second level category.
			 *     * $level2_children array containing the current second level category's children. Unused in this context.
			 *     * $level2_assign   array of this parent's second level categories that exist in the database with term ids.
			 */
			foreach( $level2_names as $level2_name ) {
				if( ! array_key_exists( $level2_name, $level2_assign ) ) {
					$new_term = wp_insert_term( $level2_name, 'cahnrs-category', array( 'parent' => $level1_assign[ $level1_name ]['term_id'] ) );
					if ( ! is_wp_error( $new_term ) ) {
						$level2_assign[ $level2_name ] = array( 'term_id' => $new_term['term_id'] );
					}
				}
			}

			/**
			 * Look for mismatches between second and third level category relationships.
			 */
			foreach( $level2_names as $level2_name ) {
				$level3_exists = get_terms( 'cahnrs-category', array( 'hide_empty' => false, 'parent' => $level2_assign[ $level2_name ]['term_id'] ) );
				$level3_exists = wp_list_pluck( $level3_exists, 'name' );

				$level3_names = $master_list[ $level1_name ][ $level2_name ];
				foreach( $level3_names as $level3_name ) {
					if( ! in_array( $level3_name, $level3_exists ) ) {
						wp_insert_term( $level3_name, 'cahnrs-category', array( 'parent' => $level2_assign[ $level2_name ]['term_id'] ) );
					}
				}
			}
		}

		$this->clear_taxonomy_cache( 'cahnrs-category' );
	}


	/**
	 * Display a dashboard for CAHNRS Categories. This offers a view of the existing
	 * categories and removes the ability to add/edit terms of the taxonomy.
	 */
	public function display_categories() {

		if( 'cahnrs-category' !== get_current_screen()->taxonomy )
			return;

		// Setup the page.
		global $title;
		$tax = get_taxonomy( 'cahnrs-category' );
		$title = $tax->labels->name;
		require_once( ABSPATH . 'wp-admin/admin-header.php' );
		echo '<div class="wrap nosubsub""><h2>CAHNRS and Extension Categories</h2>';
		echo '<p><em>' . wp_count_terms( 'cahnrs-category' ) . ' items</em></p>';

		$parent_terms = get_terms( 'cahnrs-category', array( 'hide_empty' => false, 'parent' => '0' ) );

		foreach( $parent_terms as $term ) {
			
			$child_terms = get_terms( 'cahnrs-category', array( 'hide_empty' => false, 'parent' => $term->term_id ) );

			echo '<h3>' . esc_html( $term->name ) . '</h3>';

			foreach( $child_terms as $child ) {

				$child = sanitize_term( $child, 'cahnrs-category' );
    		$child_link = get_term_link( $child, 'cahnrs-category' );
				$grandchild_terms = get_terms( 'cahnrs-category', array( 'hide_empty' => false, 'parent' => $child->term_id ) );

				echo '<h4><a href="' . esc_url( $child_link ) . '">' . esc_html( $child->name ) . '</a> (' . $child->count . ')</h4>';

				if( ! empty( $grandchild_terms ) ) {

					echo '<ul>';
	
					foreach ( $grandchild_terms as $grandchild ) {
						
						$grandchild = sanitize_term( $grandchild, 'cahnrs-category' );
    				$grandchild_link = get_term_link( $grandchild, 'cahnrs-category' );
						
						echo '<li><a href="' . esc_url( $grandchild_link ) . '">' . esc_html( $grandchild->name ) . '</a> (' . $grandchild->count . ')</li>';
					}

					echo '</ul>';
					
				}
			}

		}

		// Close the page.
		echo '</div>';

		include( ABSPATH . 'wp-admin/admin-footer.php' );

		die();

	}


	/**
	 * Maintain an array of current CAHNRS categories.
	 *
	 * @return array current CAHNRS categories.
	 */
	public function get_cahnrs_categories() {

		$categories = array(
			'Context' => array(
				'Extension' => array(),
				'Research'  => array(),
				'Teaching'  => array(),
			),
			'Departments' => array(
				'Academic' => array(
					'Department of Animal Science',
					'Department of Apparel, Merchandising, Design and Textiles',
					'Department of Biological Systems Engineering',
					'Department of Crop and Soil Sciences',
					'Department of Entomology',
					'Department of Horticulture',
					'Department of Human Development',
					'Department of Plant Pathology',
					'Institute of Biological Chemistry',
					'School of Design and Construction',
					'School of Economic Sciences',
					'School of the Environment',
					'WSU/UI School of Food Sciences',
				),
				'Administrative/Support' => array(
					'Academic Programs',
					'Alumni and Friends',
					'Business and Finance Office',
					'CAHNRS Office of Research',
					'CAHNRS Communications',
					'Computing and Web Resources',
					'Dean\'s Office',
					'Extension',
					'Food Science/Clark Hall Business Center',
					'Johnson Hall Business Center',
				),
			),
			'Units' => array(
				'Partnerships/Centers' => array(
					'Ag WeatherNet',
					'Center for Precision, Automated Agricultural Systems',
					'Center for Sustaining Agriculture and Natural Resources',
					'Center for Transformational Learning and Leadership',
					'Clean Plant Network',
					'Composite Materials and Engineering Center',
					'IMPACT Center',
					'International Research and Agricultural Development',
					'Western Center for Risk Management Education',
					'William D. Ruckelshaus Center',
				),
				'Extension County Offices' => array(
					'Adams',
					'Asotin',
					'Benton',
					'Benton/Franklin',
					'Chelan',
					'Chelan/Douglas/Okanogan',
					'Clallam',
					'Clark',
					'Columbia',
					'Cowlitz ',
					'Douglas',
					'Ferry',
					'Franklin',
					'Garfield',
					'Grant/Adams',
					'Grays Harbor',
					'Island',
					'Jefferson',
					'King',
					'Kitsap',
					'Kittitas',
					'Klickitat',
					'Lewis',
					'Lincoln/Adams',
					'Mason',
					'Okanogan',
					'Pacific',
					'Pend Oreille',
					'Pierce',
					'San Juan',
					'Skagit',
					'Skamania',
					'Snohomish',
					'Spokane',
					'Stevens',
					'Thurston',
					'Wahkiakum',
					'Walla Walla',
					'Whatcom',
					'Whitman',
					'Yakima',
				),
				'Extension Program Units' => array(
					'Agriculture and Natural Resources',
					'Community and Economic Development',
					'Youth and Family',
				),
				'Research and Extension' => array(
					'Lind Dryland Research Station',
					'Long Beach Research and Extension Unit',
					'Mount Vernon Research and Extension Center',
					'Prosser Irrigated Agricultural Research and Extension Center',
					'Puyallup Research and Extension Center',
					'Wentachee Tree Fruit Research and Extension Center',
					'Yakima Agricultural Research Laboratory',
				),
			),
			'Programs' => array(
				'Agriculture and Food Systems' => array(),
				'Integrated Plant Sciences' => array(),
				'Interior Design' => array(),
				'Landscape Architecture' => array(),
			),
			'Services and Activities' => array(
				'Events' => array(
					'Career/Networking Opportunity',
					'Conferences',
					'Field Day',
					'Field Tour',
					'Fundraising/Development',
					'Professional Development/Continuing Education',
					'Service',
					'Student Recruitment',
					'Workshops',
				),
				'External' => array(
					'Books, Manuals, and Learning modules ',
					'Commercial Feeds',
					'Commercial Seeds',
					'Edible Products',
					'Pesticide Recommendations',
					'Training and Certifications',
					'Variety Licensing',
					'Weather Services',
				),
				'Internal' => array(
					'Grants Management and Processing',
					'Hiring',
					'New Employee Orientation',
					'Payroll',
					'Publishing',
					'Purchasing',
					'Travel',
				),
				'Student Services' => array(
					'Advising',
					'Internship/Job Opportunity',
					'Research Opportunity',
				),
			),
			'Topics' => array (
				'Agriculture' => array(
					'Agronomy',
					'Animal Science',
					'Aquaculture',
					'Biological Systems Engineering',
					'Enology',
					'Entomology',
					'Food Systems',
					'Gardening',
					'Genomics',
					'Horticulture',
					'Organics',
					'Plant Science',
					'Small Farms',
					'Viticulture',
				),
				'Architecture and Design' => array(
					'Interior Design',
					'Landscape Architecture',
				),
				'Crops' => array(
					'Berries and Small fruit',
					'Forages and Hay',
					'Grapes and Wine',
					'Greenhouse and Nursery products',
					'Hops',
					'Legumes',
					'Mint',
					'Potatoes',
					'Small Grains',
					'Tree Fruit',
					'Vegetables',
				),
				'Earth Sciences' => array(
					'Environmental Studies and Forestry',
					'Soil Science',
					'Turf Management',
				),
				'Education' => array(
					'Agricultural Education',
				),
				'Family and Consumer Sciences' => array(
					'Economics',
					'Food Science',
					'Nutrition',
					'Textiles',
				),
				'Industry Types' => array(
					'Energy',
					'Farming',
					'Foresty',
					'Gardening',
					'Health Services',
					'Manufacturing',
					'Technology',
					'Tourism',
				),
				'Life Sciences' => array(
					'Bioinformatics',
					'Developmental Biology',
				),
				'Livestock' => array(
					'Cattle and Calves',
					'Dairy',
					'Poultry',
					'Small Ruminants',
					'Swine',
				),
				'Natural Resources' => array(
					'Forestry',
					'Range',
					'Water',
				),
			),	
		);

		return $categories;

	}


}
?>