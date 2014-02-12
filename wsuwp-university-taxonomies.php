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
	 * @var array Contains current dataset for University Locations.
	 */
	var $locations = array();

	/**
	 * Fire necessary hooks when instantiated.
	 */
	function __construct() {
		$this->locations = $this->get_university_locations();

		add_action( 'init',               array( $this, 'modify_default_taxonomy_labels' ) );
		add_action( 'init',               array( $this, 'register_taxonomies'            ) );
		add_action( 'load-edit-tags.php', array( $this, 'compare_locations'              ) );
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
	 * Register the taxonomies provided by the plugin.
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
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'query_var'         => true,
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
			'hierarchical' => true,
			'labels'       => $labels,
			'show_ui'      => true,
		);
		register_taxonomy( $this->university_location, array( 'post', 'page', 'attachment' ), $args );
	}

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
	 * Compare the current state of locations and populate anything that is missing.
	 */
	public function compare_locations() {
		if ( $this->university_location !== get_current_screen()->taxonomy ) {
			return;
		}

		$current_locations = get_terms( $this->university_location, array( 'hide_empty' => false ) );
		$current_locations = wp_list_pluck( $current_locations, 'name' );

		foreach ( $this->locations as $location => $child_locations ) {
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
				'Chemistry',
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
					'Construction Management',
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
					'Nutrition',
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
				'Mathematics',
				'Music' => array(
					'Instrumental',
					'Vocal',
				),
				'Physics',
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
					'Benefits',
					'Events',
					'Membership',
					'Recognition',
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
				'4-H',
				'Economic Development' => array(
					'Entrepreneurship',
				),
				'Gardening' => array(
					'Master Gardeners',
				),
				'Small Business',
				'Technology Transfer',
				'WeatherNet',
			),
			'Events' => array(
				'Anniversary',
				'Athletic',
				'Camp',
				'Concert',
				'Conference',
				'Cultural',
				'Deadline',
				'Dedication &amp; Naming' => array(
					'Building',
					'College/School/Program',
				),
				'Exhibit',
				'Fair and Festival',
				'Field Day',
				'Film',
				'Groundbreaking',
				'Guest Speaker',
				'Lecture',
				'Meeting',
				'Performance',
				'Reception',
				'Recognition',
				'Recreation/Wellness',
				'Seminar',
				'Student event',
				'Workshop',
			),
			'Faculty, Staff' => array(
				'Awards, employee',
				'Faculty' => array(
					'Faculty Senate',
				),
				'Obituaries',
				'Retirement',
				'Staff',
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
				'Graduate Research',
				'Grants' => array(
					'Corporate Grants',
					'Federal Grants',
					'State Grants',
				),
				'Intellectual Property',
				'Postdoctoral Research',
				'Research Fellowships',
				'Undergraduate Research',
			),
			'Resources &amp; Offices' => array(
				'Attorney General',
				'Board of Regents',
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
					'Public Relations',
					'Publishing',
					'Social Media',
					'Web Communication',
				),
				'Community Engagement',
				'Deans and Executives',
				'Government Relations',
				'Health &amp; Wellness Services',
				'History of University',
				'Human Resources' => array(
					'Benefits',
					'Employment',
					'Equal Employment Opportunities',
					'Payroll',
					'Professional Development',
					'Retirement',
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
					'Entomology',
					'Geology',
					'Herbarium',
					'Veterinary',
				),
				'President',
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
					'Enrollment',
					'Funding',
					'Graduation',
					'Private Support',
				),
			),
			'Sports' => array(
				'Administration',
				'Club',
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
				'Admissions',
				'Advising' => array(
					'Academic',
					'Career',
				),
				'Awards, honors',
				'Bookstore',
				'Career services',
				'Civic engagement, community outreach',
				'Clubs, organizations',
				'Counseling',
				'Dining Services',
				'Diversity',
				'Enrollment',
				'Fellowships',
				'Financial aid',
				'Health wellness' => array(
					'Health &amp; wellness services',
					'University recreation',
				),
				'Honors College',
				'International',
				'Internships',
				'Living Communities' => array(
					'Fraternities',
					'Independent Living',
					'Residence Halls',
					'Sororities',
				),
				'Recruitment / Retention',
				'Registar',
				'Student Ambassadors',
				'Student government',
			),
		);

		return $categories;
	}
}
new WSUWP_University_Taxonomies();