<?php
/*
Plugin Name: WSUWP University Taxonomies
Version: 0.1
Plugin URI: http://web.wsu.edu
Description: Provides Washington State University taxonomies to WordPress
Author: washingtonstateuniversity, jeremyfelt
Author URI: http://web.wsu.edu
*/

class WSUWP_University_Taxonomies {

	/**
	 * @var string Taxonomy slug for the WSU University Category taxonomy.
	 */
	var $university_category = 'wsuwp_university_category';

	/**
	 * @var string Taxonomy slug for the University Location taxonomy.
	 */
	var $university_location = 'wsuwp_university_location';

	/**
	 * Fire necessary hooks when instantiated.
	 */
	function __construct() {
		add_action( 'init',               array( $this, 'modify_default_taxonomy_labels' ), 10 );
		add_action( 'init',               array( $this, 'register_taxonomies'            ), 11 );
		add_action( 'load-edit-tags.php', array( $this, 'compare_locations'              ), 10 );
		add_action( 'load-edit-tags.php', array( $this, 'compare_categories'             ), 10 );
		add_action( 'load-edit-tags.php', array( $this, 'display_locations'              ), 11 );
		add_action( 'load-edit-tags.php', array( $this, 'display_categories'             ), 11 );
	}

	/**
	 * Modify the default labels assigned by WordPress to built in taxonomies.
	 */
	public function modify_default_taxonomy_labels() {
		global $wp_taxonomies;

		$wp_taxonomies['category']->labels->name          = 'Site Categories';
		$wp_taxonomies['category']->labels->singular_name = 'Site Category';
		$wp_taxonomies['category']->labels->menu_name     = 'Site Categories';

		$wp_taxonomies['post_tag']->labels->name          = 'University Tags';
		$wp_taxonomies['post_tag']->labels->singular_name = 'University Tag';
		$wp_taxonomies['post_tag']->labels->menu_name     = 'University Tags';
	}

	/**
	 * Register the central University taxonomies provided.
	 *
	 * Taxonomies are registered to core post types by default. To take advantage of these
	 * custom taxonomies in your custom post types, use register_taxonomy_for_object_type().
	 */
	public function register_taxonomies() {
		$labels = array(
			'name'          => 'University Categories',
			'singular_name' => 'University Category',
			'search_items'  => 'Search Categories',
			'all_items'     => 'All Categories',
			'edit_item'     => 'Edit Category',
			'update_item'   => 'Update Category',
			'add_new_item'  => 'Add New Category',
			'new_item_name' => 'New Category Name',
			'menu_name'     => 'University Categories',
		);
		$args = array(
			'labels'            => $labels,
			'description'       => 'The central taxonomy for Washington State University',
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'rewrite'           => false,
			'query_var'         => $this->university_category,
		);
		register_taxonomy( $this->university_category, array( 'post', 'page', 'attachment' ), $args );

		$labels = array(
			'name'          => 'University Location',
			'search_items'  => 'Search Locations',
			'all_items'     => 'All Locations',
			'edit_item'     => 'Edit Location',
			'update_item'   => 'Update Location',
			'add_new_item'  => 'Add New Location',
			'new_item_name' => 'New Location Name',
			'menu_name'     => 'University Locations',
		);
		$args = array(
			'labels'            => $labels,
			'description'       => 'The central location taxonomy for Washington State University',
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'rewrite'           => false,
			'query_var'         => $this->university_location,
		);
		register_taxonomy( $this->university_location, array( 'post', 'page', 'attachment' ), $args );
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

	/**
	 * Compare the current state of locations and populate anything that is missing.
	 */
	public function compare_locations() {
		if ( $this->university_location !== get_current_screen()->taxonomy ) {
			return;
		}

		$this->clear_taxonomy_cache( $this->university_location );

		// Get our current master list of locations.
		$master_locations = $this->get_university_locations();

		// Get our current list of top level locations.
		$current_locations = get_terms( $this->university_location, array( 'hide_empty' => false ) );
		$current_locations = wp_list_pluck( $current_locations, 'name' );

		foreach ( $master_locations as $location => $child_locations ) {
			$parent_id = false;

			// If the parent location is not a term yet, insert it.
			if ( ! in_array( $location, $current_locations ) ) {
				$new_term    = wp_insert_term( $location, $this->university_location, array( 'parent' => 0 ) );
				$parent_id = $new_term['term_id'];
			}

			// Loop through the parent's children to check term existence.
			foreach( $child_locations as $child_location ) {
				if ( ! in_array( $child_location, $current_locations ) ) {
					if ( ! $parent_id ) {
						$parent = get_term_by( 'name', $location, $this->university_location );
						if ( isset( $parent->id ) ) {
							$parent_id = $parent->id;
						} else {
							$parent_id = 0;
						}
					}
					wp_insert_term( $child_location, $this->university_location, array( 'parent' => $parent_id ) );
				}
			}
		}

		$this->clear_taxonomy_cache( $this->university_location );
	}

	/**
	 * Compare the current state of categories and populate anything that is missing.
	 */
	public function compare_categories() {
		if ( $this->university_category !== get_current_screen()->taxonomy ) {
			return;
		}

		$this->clear_taxonomy_cache( $this->university_category );

		// Get our current master list of categories.
		$master_list = $this->get_university_categories();

		// Get our current list of top level parents.
		$level1_exist  = get_terms( $this->university_category, array( 'hide_empty' => false, 'parent' => '0' ) );
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
			if ( ! array_key_exists( $level1_name, $level1_assign ) ) {
				$new_term = wp_insert_term( $level1_name, $this->university_category, array( 'parent' => '0' ) );
				if ( ! is_wp_error( $new_term ) ) {
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
			$level2_exists = get_terms( $this->university_category, array( 'hide_empty' => false, 'parent' => $level1_assign[ $level1_name ]['term_id'] ) );
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
				if ( ! array_key_exists( $level2_name, $level2_assign ) ) {
					$new_term = wp_insert_term( $level2_name, $this->university_category, array( 'parent' => $level1_assign[ $level1_name ]['term_id'] ) );
					if ( ! is_wp_error( $new_term ) ) {
						$level2_assign[ $level2_name ] = array( 'term_id' => $new_term['term_id'] );
					}
				}
			}

			/**
			 * Look for mismatches between second and third level category relationships.
			 */
			foreach( $level2_names as $level2_name ) {
				$level3_exists = get_terms( $this->university_category, array( 'hide_empty' => false, 'parent' => $level2_assign[ $level2_name ]['term_id'] ) );
				$level3_exists = wp_list_pluck( $level3_exists, 'name' );

				$level3_names = $master_list[ $level1_name ][ $level2_name ];
				foreach( $level3_names as $level3_name ) {
					if ( ! in_array( $level3_name, $level3_exists ) ) {
						wp_insert_term( $level3_name, $this->university_category, array( 'parent' => $level2_assign[ $level2_name ]['term_id'] ) );
					}
				}
			}
		}

		$this->clear_taxonomy_cache( $this->university_category );
	}

	/**
	 * Display a dashboard for University Locations. This offers a view of the existing
	 * locations and removes the ability to add/edit terms of the taxonomy.
	 */
	public function display_locations() {
		if ( $this->university_location !== get_current_screen()->taxonomy ) {
			return;
		}

		// Setup the page.
		global $title;
		$tax = get_taxonomy( $this->university_location );
		$title = $tax->labels->name;
		require_once( ABSPATH . 'wp-admin/admin-header.php' );
		echo '<div class="wrap nosubsub"><h2>University Locations</h2>';

		$parent_terms = get_terms( $this->university_location, array( 'hide_empty' => false, 'parent' => '0' ) );

		foreach( $parent_terms as $term ) {
			echo '<h3>' . esc_html( $term->name ) . '</h3>';
			$child_terms = get_terms( $this->university_location, array( 'hide_empty' => false, 'parent' => $term->term_id ) );
			echo '<ul>';

			if ( 7 < count( $child_terms ) ) {
				$style = 'style="width:200px;display:inline-block;"';
			} else {
				$style = '';
			}

			foreach ( $child_terms as $child ) {
				echo '<li ' . $style . '>' . esc_html( $child->name ) . '</li>';
			}
			echo '</ul>';
		}

		// Close the page.
		echo '</div>';
		include( ABSPATH . 'wp-admin/admin-footer.php' );
		die();
	}

	/**
	 * Display a dashboard for University Categories. This offers a view of the existing
	 * categories and removes the ability to add/edit terms of the taxonomy.
	 */
	public function display_categories() {
		if ( $this->university_category !== get_current_screen()->taxonomy ) {
			return;
		}

		// Setup the page.
		global $title;
		$tax = get_taxonomy( $this->university_category );
		$title = $tax->labels->name;
		require_once( ABSPATH . 'wp-admin/admin-header.php' );
		echo '<div class="wrap nosubsub"><h2>University Categories</h2>';

		$parent_terms = get_terms( $this->university_category, array( 'hide_empty' => false, 'parent' => '0' ) );

		foreach( $parent_terms as $term ) {
			echo '<h3>' . esc_html( $term->name ) . '</h3>';
			$child_terms = get_terms( $this->university_category, array( 'hide_empty' => false, 'parent' => $term->term_id ) );

			foreach( $child_terms as $child ) {
				echo '<h4>' . esc_html( $child->name ) . '</h4>';
				$grandchild_terms = get_terms( $this->university_category, array( 'hide_empty' => false, 'parent' => $child->term_id ) );

				echo '<ul>';

				if ( 7 < count( $grandchild_terms ) ) {
					$style = 'style="width:200px;display:inline-block;"';
				} else {
					$style = '';
				}

				foreach ( $grandchild_terms as $grandchild ) {
					echo '<li ' . $style . '>' . esc_html( $grandchild->name ) . '</li>';
				}
				echo '</ul>';
			}

		}

		// Close the page.
		echo '</div>';
		include( ABSPATH . 'wp-admin/admin-footer.php' );
		die();
	}

	/**
	 * Maintain an array of current university locations.
	 *
	 * @return array Current university locations.
	 */
	public function get_university_locations() {
		$locations = array(
			'WSU Pullman'                      => array(),
			'WSU West/Downtown Seattle'        => array(),
			'WSU Spokane'                      => array(),
			'WSU Tri-Cities'                   => array(),
			'WSU Vancouver'                    => array(),
			'WSU Global Campus'                => array(),
			'WSU Extension'                    => array(
				'Asotin County',
				'Benton County',
				'Chelan County',
				'Clallam County',
				'Clark County',
				'Columbia County',
				'Cowlitz County',
				'Douglas County',
				'Ferry County',
				'Franklin County',
				'Garfield County',
				'Grant County',
				'Grays Harbor County',
				'Island County',
				'Jefferson County',
				'King County',
				'Kitsap County',
				'Kittitas County',
				'Klickitat County',
				'Lewis County',
				'Lincoln County',
				'Mason County',
				'Okanogan County',
				'Pacific County',
				'Pend Oreille County',
				'Pierce County',
				'San Juan County',
				'Skagit County',
				'Skamania County',
				'Spokane County',
				'Stevens County',
				'Thurston County',
				'Wahkiakum County',
				'Walla Walla County',
				'Whatcom County',
				'Whitman County',
				'Yakima County',
			),
			'WSU Seattle'                      => array(),
			'WSU North Puget Sound at Everett' => array(),
			'WSU Research Centers'             => array(
				'Lind',
				'Long Beach',
				'Mount Vernon',
				'Othello',
				'Prosser',
				'Puyallup',
				'Wenatchee',
			)
		);

		return $locations;
	}

	/**
	 * Maintain an array of current university categories.
	 *
	 * @return array Current university categories.
	 */
	public function get_university_categories() {
		$categories = array(
			'Academic Subjects' => array(
				'Agriculture' => array(
					'Agriculture Business',
					'Agriculture Economics',
					'Agriculture Engineering',
					'Animal Sciences',
					'Berries',
					'Crop Sciences',
					'Equipment/Mechanization',
					'Fodder/Silage',
					'Food Science',
					'Forestry',
					'Fruit Trees',
					'Fungus',
					'Horticulture',
					'Irrigation / Water Management',
					'Legumes, Pulse',
					'Mint',
					'Oil Seed',
					'Organic Farming',
					'Pests and Weeds',
					'Plant Pathology',
					'Small Grains',
					'Soil Sciences',
					'Tubers',
					'Vegetables',
					'Viticulture, Enology, Wine',
					'Weather, Climate',
				),
				'Arts' => array(
					'Digital Media',
					'Fine Arts',
					'Performing Arts',
				),
				'Biology' => array(
					'Botany',
					'Entomology',
					'Genomics and Bioinformatics',
					'Molecular Biology',
					'Neuroscience',
					'Zoology',
				),
				'Business' => array(
					'Accounting',
					'Construction Management',
					'Economics',
					'Finance',
					'Hospitality',
					'Information Systems',
					'Investment',
					'Management',
					'Sports Management',
				),
				'Chemistry' => array(),
				'Communication (academic)' => array(
					'Advertising',
					'Broadcasting',
					'Electronic',
					'Journalism',
					'Public Relations',
				),
				'Computer Sciences' => array(
					'Computer Engineering',
					'Computer Science',
					'Power Systems',
					'Smart Environments',
				),
				'Design (Construction)' => array(
					'Architecture',
					'Interior Design',
					'Landscape Architecture',
				),
				'Earth Sciences' => array(
					'Environmental Studies',
					'Geology',
					'Natural Resources',
				),
				'Education (Academics)' => array(
					'Administration',
					'Special Education',
					'Teaching (Education)',
				),
				'Engineering' => array(
					'Atmospheric Research',
					'Catalysis',
					'Energy Conversion',
					'Infrastructure',
					'Structures',
				),
				'Family and Consumer Science' => array(
					'Apparel and Textile Design',
					'Food and Sensory Science',
					'Home Economics',
					'Human Development',
					'Nutrition',
				),
				'Health Sciences' => array(
					'Addictions',
					'Cancer',
					'Childhood Trauma',
					'Chronic Illness',
					'Exercise Physiology',
					'Health Administration',
					'Health Policy',
					'Medical Health',
					'Metabolic Disorders',
					'Nursing',
					'Nutrition, Health',
					'Pharmacy',
					'Physical performance, recreation',
					'Sleep',
					'Speech and Hearing',
				),
				'Humanities' => array(
					'English',
					'History',
					'Languages',
					'Literature',
					'Philosophy',
				),
				'Mathematics' => array(),
				'Music' => array(
					'Instrumental',
					'Vocal',
				),
				'Physics' => array(),
				'Social Sciences' => array(
					'Anthropology',
					'Archaeology',
					'Criminology/Criminal Justice',
					'Cultural and ethnic studies',
					'Gender and sexuality studies',
					'Geography',
					'Military',
					'Political science',
					'Psychology',
					'Religion',
					'Sociology',
				),
				'Space sciences' => array(
					'Astronomy',
				),
				'Veterinary Medicine' => array(
					'Companion Animals',
					'Emerging Diseases',
					'Equine',
					'Exotic / Pocket Pets',
					'Food Animal',
					'Foreign Animal Diseases',
					'Pathology',
					'Pharmacology, animal',
					'Zoonoses',
				),
			),
			'Alumni' => array(
				'Alumni Assoc.' => array(
					'Alumni Centre',
					'Awards',
					'Alumni Benefits',
					'Alumni Events',
					'Membership',
				),
				'Notable Alumni' => array(
					'Athletes',
					'Business Leaders',
					'Government Leaders',
					'Other',
					'Philanthropists',
					'Scientists',
				),
			),
			'Community &amp; Economic Development' => array(
				'4-H' => array(),
				'Economic Development' => array(
					'Entrepreneurship',
				),
				'Gardening' => array(
					'Master Gardeners',
				),
				'Small Business' => array(),
				'Technology Transfer' => array(),
				'WeatherNet' => array(),
			),
			'Events' => array(
				'Anniversary' => array(),
				'Athletic' => array(),
				'Camp' => array(),
				'Concert' => array(),
				'Conference' => array(),
				'Cultural' => array(),
				'Deadline' => array(),
				'Dedication &amp; Naming' => array(
					'Building',
					'College/School/Program',
				),
				'Exhibit' => array(),
				'Fair and Festival' => array(),
				'Field Day' => array(),
				'Film' => array(),
				'Groundbreaking' => array(),
				'Guest Speaker' => array(),
				'Lecture' => array(),
				'Meeting' => array(),
				'Performance' => array(),
				'Reception' => array(),
				'Recognition' => array(),
				'Recreation/Wellness' => array(),
				'Seminar' => array(),
				'Student event' => array(),
				'Workshop' => array(),
			),
			'Faculty, Staff' => array(
				'Awards, employee' => array(),
				'Faculty' => array(
					'Faculty Senate',
				),
				'Obituaries' => array(),
				'Retirement' => array(),
				'Staff' => array(),
			),
			'Philanthropy' => array(
				'Fundraising News' => array(
					'Foundation &amp; Fundraising Events',
					'Fundraising Updates',
					'Gift Announcements',
					'Volunteer News',
				),
				'Impact (Private Support, Volunteers)' => array(
					'Alumni Giving',
					'Faculty &amp; Staff Giving',
					'Friends &amp; Organizations Giving',
					'Gifts for Faculty &amp; Research',
					'Meet Our Donors',
					'Scholarships in Action',
					'Student Philanthropy',
				),
			),
			'Research' => array(
				'Graduate Research' => array(),
				'Grants' => array(
					'Corporate Grants',
					'Federal Grants',
					'State Grants',
				),
				'Intellectual Property' => array(),
				'Postdoctoral Research' => array(),
				'Research Fellowships' => array(),
				'Undergraduate Research' => array(),
			),
			'Resources &amp; Offices' => array(
				'Attorney General' => array(),
				'Board of Regents' => array(),
				'Buildings and Grounds' => array(
					'Campus Planning',
					'Construction',
					'Facilities Management',
				),
				'Business and Finances' => array(
					'Budget',
					'Real Estate',
					'Travel',
				),
				'Communication, University' => array(
					'Marketing',
					'Media Relations',
					'Publishing',
					'Social Media',
					'Web Communication',
				),
				'Community Engagement' => array(),
				'Deans and Executives' => array(),
				'Government Relations' => array(),
				'Health &amp; Wellness Services' => array(),
				'History of University' => array(),
				'Human Resources' => array(
					'Employee Benefits',
					'Jobs',
					'Equal Employment Opportunities',
					'Payroll',
					'Professional Development',
					'Retirees',
					'Training',
				),
				'Information Technology' => array(
					'Faculty and Staff',
					'Help Desk',
					'Maintenance',
					'Network',
					'Security',
					'Services',
					'Students &amp; Parents',
				),
				'Libraries' => array(
					'Archives/Special Collections',
					'Books',
					'Collections',
					'Reference',
				),
				'Museums' => array(
					'Anthropology Museum',
					'Art Museum',
					'Conner Museum',
					'Entomology Museum',
					'Geology Museum',
					'Herbarium Museum',
					'Veterinary Museum',
				),
				'President' => array(),
				'Safety' => array(
					'Campus police',
					'Emergency management',
					'Environmental safety',
				),
				'Transportation' => array(
					'Bicycle',
					'Bus',
					'Parking',
				),
				'University Statistics' => array(
					'Employment',
					'Enrollment Statistics',
					'Funding',
					'Graduation',
					'Private Support',
				),
			),
			'Sports' => array(
				'Sports Administration' => array(),
				'Club' => array(),
				'Intercollegiate' => array(
					'Baseball',
					'Basketball',
					'Cross Country',
					'Football',
					'Golf',
					'Rowing',
					'Soccer',
					'Swimming',
					'Tennis',
					'Track &amp; Field',
					'Volleyball',
				),
				'Intramural' => array(
					'Outdoor Recreation',
				),
			),
			'Students' => array(
				'Admissions' => array(),
				'Advising' => array(
					'Academic',
					'Career',
				),
				'Awards, honors' => array(),
				'Bookstore' => array(),
				'Career services' => array(),
				'Civic engagement, community outreach' => array(),
				'Clubs, organizations' => array(),
				'Counseling' => array(),
				'Dining Services' => array(),
				'Diversity' => array(),
				'Enrollment' => array(),
				'Fellowships' => array(),
				'Financial aid' => array(),
				'Health wellness' => array(
					'Health &amp; wellness services',
					'University recreation',
				),
				'Honors College' => array(),
				'International' => array(),
				'Internships' => array(),
				'Living Communities' => array(
					'Fraternities',
					'Independent Living',
					'Residence Halls',
					'Sororities',
				),
				'Recruitment / Retention' => array(),
				'Registar' => array(),
				'Student Ambassadors' => array(),
				'Student government' => array(),
			),
		);

		return $categories;
	}
}
new WSUWP_University_Taxonomies();