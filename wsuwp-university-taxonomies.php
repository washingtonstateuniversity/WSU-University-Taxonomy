<?php
/*
Plugin Name: WSUWP University Taxonomies
Version: 1.0.5
Plugin URI: https://web.wsu.edu/
Description: Provides Washington State University taxonomies to WordPress
Author: washingtonstateuniversity, jeremyfelt, philcable
Author URI: https://web.wsu.edu/
*/

class WSUWP_University_Taxonomies {

	/**
	 * Maintain a record of the taxonomy schema. This should be changed whenever
	 * a schema change should be initiated on any site using the taxonomy.
	 *
	 * @var string Current version of the taxonomy schema.
	 */
	var $taxonomy_schema_version = '20200518-001';

	/**
	 * @var string Taxonomy slug for the WSU University Category taxonomy.
	 */
	var $university_category = 'wsuwp_university_category';

	/**
	 * @var string Taxonomy slug for the University Location taxonomy.
	 */
	var $university_location = 'wsuwp_university_location';

	/**
	 * @var string Taxonomy slug for the University Organization taxonomy.
	 */
	var $university_organization = 'wsuwp_university_org';

	/**
	 * Fire necessary hooks when instantiated.
	 */
	function __construct() {
		add_action( 'wpmu_new_blog', array( $this, 'pre_load_taxonomies' ), 10 );
		add_action( 'admin_init', array( $this, 'check_schema' ), 10 );
		add_action( 'wsu_taxonomy_update_schema', array( $this, 'update_schema' ) );
		add_action( 'init', array( $this, 'modify_default_taxonomy_labels' ), 10 );
		add_action( 'init', array( $this, 'register_taxonomies' ), 11 );
		add_action( 'load-edit-tags.php', array( $this, 'compare_schema' ), 10 );
		add_action( 'load-edit-tags.php', array( $this, 'display_terms' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 9 );
		add_filter( 'pre_insert_term', array( $this, 'prevent_term_creation' ), 10, 2 );
		add_filter( 'parent_file', array( $this, 'parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'submenu_file' ), 10, 2 );

		add_action( 'do_meta_boxes', array( $this, 'taxonomy_meta_boxes' ), 10, 2 );
		add_action( 'wp_ajax_add_term', array( $this, 'ajax_add_term' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
	}

	/**
	 * Pre-load University wide taxonomies whenever a new site is created on the network.
	 *
	 * @param int $site_id The ID of the new site.
	 */
	public function pre_load_taxonomies( $site_id ) {
		switch_to_blog( $site_id );
		$this->update_schema();
		restore_current_blog();
	}

	/**
	 * Check the current version of the taxonomy schema on every admin page load. If it is
	 * out of date, fire a single wp-cron event to process the changes.
	 */
	public function check_schema() {
		if ( get_option( 'wsu_taxonomy_schema', false ) !== $this->taxonomy_schema_version ) {
			// Don't schedule a duplicate event if one has already been scheduled.
			$next = wp_next_scheduled( 'wsu_taxonomy_update_schema', array() );
			if ( $next ) {
				return;
			}

			wp_schedule_single_event( time() + 60, 'wsu_taxonomy_update_schema' );
		}
	}

	/**
	 * Update the taxonomy schema and version.
	 */
	public function update_schema() {
		$this->load_terms( $this->university_category );
		$this->load_terms( $this->university_location );
		$this->load_terms( $this->university_organization );
		update_option( 'wsu_taxonomy_schema', $this->taxonomy_schema_version );
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
	 * In normal term entry situations, we prevent new terms being created for the
	 * taxonomies that we statically maintain.
	 *
	 * @param string $term     Term being added.
	 * @param string $taxonomy Taxonomy of the term being added.
	 *
	 * @return string|WP_Error Pass on the term untouched if not one of our taxonomies. WP_Error otherwise.
	 */
	public function prevent_term_creation( $term, $taxonomy ) {
		if ( in_array( $taxonomy, array( $this->university_location, $this->university_organization, $this->university_category ), true ) ) {
			$term = new WP_Error( 'invalid_term', 'These terms cannot be modified.' );
		}

		return $term;
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

		$labels = array(
			'name' => 'University Organization',
			'search_items'  => 'Search Organizations',
			'all_items'     => 'All Organizations',
			'edit_item'     => 'Edit Organization',
			'update_item'   => 'Update Organization',
			'add_new_item'  => 'Add New Organization',
			'new_item_name' => 'New Organization Name',
			'menu_name'     => 'University Organizations',
		);
		$args = array(
			'labels'            => $labels,
			'description'       => 'The central organization taxonomy for Washington State University',
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'rewrite'           => false,
			'query_var'         => $this->university_organization,
		);
		register_taxonomy( $this->university_organization, array( 'post', 'page' ), $args );
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
	 * Compare the existing schema version on taxonomy page loads and run update
	 * process if a mismatch is present.
	 */
	public function compare_schema() {
		if ( ! in_array( get_current_screen()->taxonomy, array( $this->university_location, $this->university_organization, $this->university_category ), true ) ) {
			return;
		}

		if ( get_option( 'wsu_taxonomy_schema', false ) !== $this->taxonomy_schema_version ) {
			$this->update_schema();
		}
	}

	/**
	 * Process term removals or name changes.
	 *
	 * @param strong $taxonomy Taxonomy for which to process term changes.
	 */
	public function process_term_updates( $taxonomy ) {
		$existing_terms = get_terms( array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
			'fields' => 'id=>name',
		) );

		// Bail if there are no existing terms.
		if ( empty( $existing_terms ) ) {
			return;
		}

		// Flip the array of terms so we have IDs keyed by name.
		$existing_terms = array_flip( $existing_terms );

		// Get the array of terms that have been updated.
		$updated_names = $this->get_university_term_updates( $taxonomy );

		foreach ( $updated_names as $previous_name => $new_name ) {

			// Confirm an existing match before processing any change.
			if ( ! array_key_exists( $previous_name, $existing_terms ) ) {
				continue;
			}

			$existing_term_id = $existing_terms[ $previous_name ];

			if ( '' === $new_name ) {
				wp_delete_term( $existing_term_id, $taxonomy );
			} else {
				wp_update_term( $existing_term_id, $taxonomy, array(
					'name' => $new_name,
				) );
			}
		}
	}

	/**
	 * Ensure all of the pre-configured terms for a given taxonomy are loaded with
	 * the proper parent -> child relationships.
	 *
	 * @param string $taxonomy Taxonomy being loaded.
	 */
	public function load_terms( $taxonomy ) {
		$this->clear_taxonomy_cache( $taxonomy );

		// Get a master list of terms used to populate this taxonomy.
		if ( $this->university_category === $taxonomy ) {
			$master_list = $this->get_university_categories();
		} elseif ( $this->university_location === $taxonomy ) {
			$master_list = $this->get_university_locations();
		} elseif ( $this->university_organization === $taxonomy ) {
			$master_list = $this->get_university_organizations();
		} else {
			return;
		}

		// Process term removals or name changes.
		$this->process_term_updates( $taxonomy );

		// Get our current list of top level parents.
		$level1_exist  = get_terms( $taxonomy, array(
			'hide_empty' => false,
			'parent' => '0',
		) );
		$level1_assign = array();
		foreach ( $level1_exist as $level1 ) {
			$level1_assign[ $level1->name ] = array(
				'term_id' => $level1->term_id,
			);
		}

		remove_filter( 'pre_insert_term', array( $this, 'prevent_term_creation' ), 10 );

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
		foreach ( $level1_names as $level1_name ) {
			if ( ! array_key_exists( $level1_name, $level1_assign ) ) {
				$new_term = wp_insert_term( $level1_name, $taxonomy, array(
					'parent' => '0',
				) );
				if ( ! is_wp_error( $new_term ) ) {
					$level1_assign[ $level1_name ] = array(
						'term_id' => $new_term['term_id'],
					);
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
		foreach ( $level1_names as $level1_name ) {
			$level2_exists = get_terms( $taxonomy, array(
				'hide_empty' => false,
				'parent' => $level1_assign[ $level1_name ]['term_id'],
			) );
			$level2_assign = array();

			foreach ( $level2_exists as $level2 ) {
				$level2_assign[ $level2->name ] = array(
					'term_id' => $level2->term_id,
				);
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
			foreach ( $level2_names as $level2_name ) {
				if ( ! array_key_exists( $level2_name, $level2_assign ) ) {
					$new_term = wp_insert_term( $level2_name, $taxonomy, array(
						'parent' => $level1_assign[ $level1_name ]['term_id'],
					) );
					if ( ! is_wp_error( $new_term ) ) {
						$level2_assign[ $level2_name ] = array(
							'term_id' => $new_term['term_id'],
						);
					}
				}
			}

			/**
			 * Look for mismatches between second and third level category relationships.
			 */
			foreach ( $level2_names as $level2_name ) {
				$level3_exists = get_terms( $taxonomy, array(
					'hide_empty' => false,
					'parent' => $level2_assign[ $level2_name ]['term_id'],
				) );
				$level3_exists = wp_list_pluck( $level3_exists, 'name' );

				$level3_names = $master_list[ $level1_name ][ $level2_name ];
				foreach ( $level3_names as $level3_name ) {
					if ( ! in_array( $level3_name, $level3_exists, true ) ) {
						wp_insert_term( $level3_name, $taxonomy, array(
							'parent' => $level2_assign[ $level2_name ]['term_id'],
						) );
					}
				}
			}
		}

		add_filter( 'pre_insert_term', array( $this, 'prevent_term_creation' ), 10 );

		$this->clear_taxonomy_cache( $taxonomy );
	}

	/**
	 * Enqueue styles to be used for the display of taxonomy terms.
	 *
	 * @param string $hook Hook indicating the current admin page.
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( false === apply_filters( 'wsu_taxonomy_select2_interface', true ) ) {
			return;
		}

		// Register scripts and styles so they can be easily enqueued by other plugins if needed.
		wp_register_style( 'select2', plugins_url( 'assets/select2.min.css', __FILE__ ) );
		wp_register_script( 'select2', plugins_url( 'assets/select2.min.js', __FILE__ ), array( 'jquery' ) );
		wp_register_style( 'wsuwp-select2', plugins_url( 'css/wsuwp-select2.css', __FILE__ ), array( 'select2' ) );
		wp_register_script( 'wsuwp-select2', plugins_url( 'js/wsuwp-select2.js', __FILE__ ), array( 'select2' ), null, true );

		if ( 'edit-tags.php' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		if ( in_array( get_current_screen()->taxonomy, array( $this->university_organization, $this->university_category, $this->university_location ), true ) ) {
			wp_enqueue_style( 'wsuwp-taxonomy-admin', plugins_url( 'css/edit-tags-style.css', __FILE__ ) );
		}

		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			if ( in_array( get_current_screen()->post_type, array_keys( $this->get_default_metabox_post_types() ), true ) ) {
				wp_enqueue_style( 'select2' );
				wp_enqueue_style( 'wsuwp-select2' );
				wp_enqueue_script( 'select2' );
				wp_enqueue_script( 'wsuwp-select2' );

				wp_localize_script( 'wsuwp-select2', 'wsuwp_taxonomies', array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'wsuwp-add-term' ),
				) );
			} else {
				wp_enqueue_style( 'wsuwp-edit-post', plugins_url( 'css/edit-post.css', __FILE__ ) );
			}
		}

	}

	/**
	 * Display a dashboard for a custom taxonomy rather than the default term
	 * management screen provided by WordPress core.
	 */
	public function display_terms() {
		if ( ! in_array( get_current_screen()->taxonomy, array( $this->university_organization, $this->university_category, $this->university_location ), true ) ) {
			return;
		}

		$taxonomy = get_current_screen()->taxonomy;

		// Setup the page.
		$tax = get_taxonomy( $taxonomy );
		require_once( ABSPATH . 'wp-admin/admin-header.php' );
		echo '<div class="wrap nosubsub"><h2>' . esc_html( $tax->labels->name ) . '</h2>';

		$parent_terms = get_terms( $taxonomy, array(
			'hide_empty' => false,
			'parent' => '0',
		) );

		foreach ( $parent_terms as $term ) {
			echo '<h3>' . esc_html( $term->name ) . '</h3>';
			$child_terms = get_terms( $taxonomy, array(
				'hide_empty' => false,
				'parent' => $term->term_id,
			) );

			foreach ( $child_terms as $child ) {
				echo '<h4>' . esc_html( $child->name ) . '</h4>';
				$grandchild_terms = get_terms( $taxonomy, array(
					'hide_empty' => false,
					'parent' => $child->term_id,
				) );

				echo '<ul>';

				if ( empty( $grandchild_terms ) ) {
					echo '<li><em>No level 3 categories for this term.</em></li>';
				}
				foreach ( $grandchild_terms as $grandchild ) {
					echo '<li>' . esc_html( $grandchild->name ) . '</li>';
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
	 * Sets the active parent menu item for a taxonomy dashboard page.
	 * Using the `load-edit-tags.php` hook prevents this from being set by default.
	 *
	 * @param string $parent_file The parent file.
	 *
	 * @return string
	 */
	public function parent_file( $parent_file ) {
		if ( ! isset( $_GET['taxonomy'] ) || ! in_array( $_GET['taxonomy'], array( $this->university_category, $this->university_location, $this->university_organization ), true ) ) { // WPCS: CSRF ok.
			return $parent_file;
		}

		if ( ! isset( $_GET['post_type'] ) ) { // WPCS: CSRF ok.
			$parent_file = 'edit.php';

			return $parent_file;
		}

		$post_type = sanitize_text_field( $_GET['post_type'] );
		$parent_file .= "edit.php?post_type=$post_type";

		return $parent_file;
	}

	/**
	 * Sets the active menu item for a taxonomy dashboard page.
	 * Using the `load-edit-tags.php` hook prevents this from being set by default.
	 *
	 * @param string $submenu_file The submenu file.
	 * @param string $parent_file  The parent file.
	 *
	 * @return string
	 */
	public function submenu_file( $submenu_file, $parent_file ) {
		if ( ! isset( $_GET['taxonomy'] ) || ! in_array( $_GET['taxonomy'], array( $this->university_category, $this->university_location, $this->university_organization ), true ) ) { // WPCS: CSRF ok.
			return $submenu_file;
		}

		$taxonomy = sanitize_text_field( $_GET['taxonomy'] );
		$submenu_file = "edit-tags.php?taxonomy=$taxonomy";

		if ( isset( $_GET['post_type'] ) ) { // WPCS: CSRF ok.
			$args = array(
				'public' => true,
			);
			$post_types = get_post_types( $args, 'names' );

			if ( in_array( $_GET['post_type'], $post_types, true ) ) { // WPCS: CSRF ok.
				$post_type = sanitize_text_field( $_GET['post_type'] );
				$submenu_file .= "&amp;post_type=$post_type";
			}
		}

		return $submenu_file;
	}

	/**
	 * Maintain an array of current university organizations.
	 *
	 * @return array University Organizations
	 */
	public function get_university_organizations() {
		$organizations = array(
			'Office' => array(
				'Academic Outreach and Innovation' => array(),
				'Alumni Association' => array(),
				'Budget Office' => array(),
				'Cougar Health Services' => array(),
				'Office of Emergency Management' => array(),
				'Enrollment Management' => array(
					'Office of Admissions and Recruitment',
					'New Student Programs',
					'Student Financial Services',
					'Office of the Registrar',
					'Enrollment Information Technology',
				),
				'Extension' => array(
					'Agriculture and Natural Resources',
					'Community and Economic Development',
					'Youth and Family',
				),
				'External Affairs and Government Relations' => array(
					'Small Business Development Center',
				),
				'Facilities Services' => array(),
				'Faculty Senate' => array(),
				'Finance and Administration' => array(),
				'Foundation' => array(),
				'Graduate School' => array(
					'Molecular Plant Sciences',
				),
				'Human Resource Services' => array(),
				'Information Technology Services' => array(),
				'Intercollegiate Athletics' => array(),
				'Office of Internal Audit' => array(),
				'Office of International Programs' => array(
					'International Student and Scholar Services',
				),
				'Libraries' => array(),
				'Office of the President' => array(),
				'Office of the Provost' => array(
					'Jordan Schnitzer Museum of Art WSU',
					'Association for Faculty Women',
				),
				'Public Safety' => array(),
				'Office of Research' => array(
					'Office of Commercialization',
					'Office of Research Advancement and Partnerships',
				),
				'Sleep and Performance Research Center' => array(),
				'Division of Student Affairs' => array(),
				'Transportation Services' => array(),
				'Office of Undergraduate Education' => array(),
				'University Marketing and Communications' => array(
					'Administration - University Marketing and Communications',
					'Finance and Administrative Support Team - University Marketing and Communications',
					'Finance and Administration',
					'Financial Services - University Marketing and Communications',
					'IT Support - University Marketing and Communications',
					'Strategic Communications - University Marketing and Communications',
					'EM Marketing - University Marketing and Communications',
					'University Events - University Marketing and Communications',
					'News and Media Relations - University Marketing and Communications',
					'Presidential Communications - University Marketing and Communications',
					'Visual Design - University Marketing and Communications',
					'Photo Services - University Marketing and Communications',
					'Video Services - University Marketing and Communications',
					'WA State Magazine - University Marketing and Communications',
					'Web Communications - University Marketing and Communications',
					'University Publishing - University Marketing and Communications',
					'Coug Prints Plus - University Marketing and Communications',
					'Coug Copies - University Marketing and Communications',
					'Graphic Design - University Marketing and Communications',
					'Mailing Services - University Marketing and Communications',
					'Printing Services - University Marketing and Communications',
					'Production Coordination - University Marketing and Communications',
					'WSU Press - University Marketing and Communications',
				),
				'Office of Veterans Affairs' => array(),
			),
			'College' => array(
				'Carson College of Business' => array(
					'Accounting',
					'Finance and Management Science',
					'School of Hospitality Business Management',
					'Management, Information Systems and Entrepreneurship',
					'Marketing and International Business',
				),
				'College of Agricultural, Human, and Natural Resource Sciences' => array(
					'Animal Science',
					'Apparel, Merchandising, Design, and Textiles',
					'Biological Systems Engineering',
					'Crop and Soil Sciences',
					'School of Economic Sciences',
					'Entomology',
					'School of the Environment', // Shared with College of Arts and Sciences.
					'School of Food Science',
					'Horticulture',
					'Human Development',
					'Institute of Biological Chemistry',
					'International Research and Agricultural Development',
					'Plant Pathology',
				),
				'College of Arts and Sciences' => array(
					'Anthropology',
					'Asian Program',
					'School of Biological Sciences',
					'Chemistry',
					'Creative Media and Digital Culture Program',
					'Criminal Justice and Criminology',
					'Digital Technology and Culture Program',
					'English',
					'School of the Environment', // Shared with CAHNRS.
					'Fine Arts',
					'History',
					'School of Languages, Cultures, and Race',
					'Mathematics and Statistics',
					'School of Music',
					'Physics and Astronomy',
					'School of Politics, Philosophy, and Public Affairs',
					'Psychology',
					'Sociology',
					'Women\'s Studies Program',
				),
				'College of Education' => array(
					'Teaching and Learning',
					'Kinesiology and Educational Psychology',
				),
				'Elson S. Floyd College of Medicine' => array(
					'Biomedical Sciences',
					'Medical Education and Clinical Sciences',
					'Nutrition and Exercise Physiology',
					'Speech and Hearing Sciences',
					'Admissions, Recruiting and inclusion',
					'Behavioral Health Innovations',
					'Health Policy and Administration',
					'Sleep and Performance Research Center',
					'Virtual Care Clinic',
					'WSU Health Sciences Spokane',
				),
				'College of Nursing' => array(),
				'College of Pharmacy and Pharmaceutical Sciences' => array(
					'Experimental and Systems Pharmacology',
					'Pharmaceutical Sciences',
					'Pharmacotherapy',
				),
				'College of Veterinary Medicine' => array(
					'School for Global Animal Health',
					'Integrative Physiology and Neuroscience',
					'School of Molecular Biosciences',
					'Veterinary Clinical Sciences',
					'Veterinary Microbiology and Pathology',
				),
				'Edward R. Murrow College of Communication' => array(
					'Communication and Society',
					'Journalism and Media Production',
					'Strategic Communication',
				),
				'Honors College' => array(),
				'Voiland College of Engineering and Architecture' => array(
					'School of Chemical Engineering and Bioengineering',
					'Civil and Environmental Engineering',
					'School of Design and Construction',
					'School of Electrical Engineering and Computer Science',
					'Engineering and Computer Science (Tri-Cities)',
					'School of Engineering and Computer Science (Vancouver)',
					'School of Mechanical and Materials Engineering',
				),
			),
		);

		return $organizations;
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
			'WSU Health Sciences Spokane'      => array(),
			'WSU Tri-Cities'                   => array(),
			'WSU Vancouver'                    => array(),
			'WSU Global Campus'                => array(),
			'WSU Extension'                    => array(
				'Adams County' => array(),
				'Asotin County' => array(),
				'Benton County' => array(),
				'Chelan County' => array(),
				'Clallam County' => array(),
				'Clark County' => array(),
				'Columbia County' => array(),
				'Colville Reservation' => array(),
				'Cowlitz County' => array(),
				'Douglas County' => array(),
				'Ferry County' => array(),
				'Franklin County' => array(),
				'Garfield County' => array(),
				'Grant County' => array(),
				'Grays Harbor County' => array(),
				'Island County' => array(),
				'Jefferson County' => array(),
				'King County' => array(),
				'Kitsap County' => array(),
				'Kittitas County' => array(),
				'Klickitat County' => array(),
				'Lewis County' => array(),
				'Lincoln County' => array(),
				'Mason County' => array(),
				'Okanogan County' => array(),
				'Pacific County' => array(),
				'Pend Oreille County' => array(),
				'Pierce County' => array(),
				'San Juan County' => array(),
				'Skagit County' => array(),
				'Skamania County' => array(),
				'Snohomish County' => array(),
				'Spokane County' => array(),
				'Stevens County' => array(),
				'Thurston County' => array(),
				'Wahkiakum County' => array(),
				'Walla Walla County' => array(),
				'Whatcom County' => array(),
				'Whitman County' => array(),
				'Yakima County' => array(),
			),
			'WSU Seattle'                      => array(),
			'WSU Everett'                      => array(),
			'WSU Research Centers'             => array(
				'Lind' => array(),
				'Long Beach' => array(),
				'Mount Vernon' => array(),
				'Othello' => array(),
				'Prosser' => array(),
				'Puyallup' => array(),
				'Wenatchee' => array(),
			),
			'WSU Bremerton'                    => array(
				'Olympic College' => array(),
			),
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
					'Equipment / Mechanization',
					'Fodder / Silage',
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
					'Viticulture / Enology / Wine',
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
				'Communication, Academic' => array(
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
				'Design, Construction' => array(
					'Architecture',
					'Interior Design',
					'Landscape Architecture',
				),
				'Earth Sciences' => array(
					'Environmental Studies',
					'Geology',
					'Natural Resources',
				),
				'Education, Academic' => array(
					'Administration',
					'Special Education',
					'Teaching',
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
					'Physical Performance / Recreation',
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
					'Criminology / Criminal Justice',
					'Cultural and Ethnic Studies',
					'Gender and Sexuality Studies',
					'Geography',
					'Military',
					'Political Science',
					'Psychology',
					'Religion',
					'Sociology',
				),
				'Space Sciences' => array(
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
					'Pharmacology, Animal',
					'Zoonoses',
				),
			),
			'Alumni' => array(
				'Alumni Association' => array(
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
					'Other Notable Alumni',
					'Philanthropists',
					'Scientists',
				),
			),
			'Community and Economic Development' => array(
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
				'Dedication and Naming' => array(
					'Building',
					'College / School / Program',
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
				'Recreation / Wellness' => array(),
				'Seminar' => array(),
				'Student Event' => array(),
				'Workshop' => array(),
			),
			'Faculty, Staff' => array(
				'Awards, Employee' => array(),
				'Faculty' => array(
					'Faculty Senate',
				),
				'Obituaries' => array(),
				'Retirement' => array(),
				'Staff' => array(),
			),
			'Philanthropy' => array(
				'Fundraising News' => array(
					'Foundation and Fundraising Events',
					'Fundraising Updates',
					'Gift Announcements',
					'Volunteer News',
				),
				'Impact (Private Support, Volunteers)' => array(
					'Alumni Giving',
					'Faculty and Staff Giving',
					'Friends and Organizations Giving',
					'Gifts for Faculty and Research',
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
			'Resources and Offices' => array(
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
				'Health and Wellness Services' => array(),
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
					'Students and Parents',
				),
				'Libraries' => array(
					'Archives / Special Collections',
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
					'Campus Police',
					'Emergency Management',
					'Environmental Safety',
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
					'Track and Field',
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
				'Awards / Honors' => array(),
				'Bookstore' => array(),
				'Career services' => array(),
				'Civic Engagement / Community Outreach' => array(),
				'Clubs / Organizations' => array(),
				'Counseling' => array(),
				'Dining Services' => array(),
				'Diversity' => array(),
				'Enrollment' => array(),
				'Fellowships' => array(),
				'Financial Aid' => array(),
				'Health Wellness' => array(
					'Health and Wellness Services',
					'University Recreation',
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
				'Registrar' => array(),
				'Student Ambassadors' => array(),
				'Student Government' => array(),
			),
		);

		return $categories;
	}

	/**
	 * Maintain an array of terms that have been changed.
	 *
	 * The array should follow the 'existing name' => 'new name' format,
	 * with the 'new name' value being empty if the term is being deleted.
	 *
	 * @return array Terms that have been changed.
	 */
	public function get_university_term_updates( $taxonomy ) {
		$updated_terms = array();

		if ( 'wsuwp_university_org' === $taxonomy ) {
			$updated_terms = array(
				'College of Medical Sciences' => 'Elson S. Floyd College of Medicine',
				'Critical Culture, Gender, and Race Studies' => 'School of Languages, Cultures, and Race',
				'Foreign Languages and Cultures' => '',
				'Budget' => 'Budget Office',
				'Emergency Management' => 'Office of Emergency Management',
				'Health and Wellness' => 'Cougar Health Services',
				'Human Resources' => 'Human Resource Services',
				'Internal Audit' => 'Office of Internal Audit',
				'International Programs' => 'Office of International Programs',
				'President' => 'Office of the President',
				'Provost' => 'Office of the Provost',
				'Research' => 'Office of Research',
				'Commercialization' => 'Office of Commercialization',
				'Student Affairs' => 'Division of Student Affairs',
				'University Communications' => 'University Marketing and Communications',
				'CAHNRS' => 'College of Agricultural, Human, and Natural Resource Sciences',
				'College of Pharmacy' => 'College of Pharmacy and Pharmaceutical Sciences',
				'Educational Leadership, Sports Studies, and Educational / Counseling Psychology' => 'Educational Leadership and Sport Management',
				'Administration' => 'Administration - University Marketing and Communications',
				'Finance and Administrative Support Team' => 'Finance and Administrative Support Team - University Marketing and Communications',
				'IT Support' => 'IT Support - University Marketing and Communications',
				'Strategic Communications' => 'Strategic Communications - University Marketing and Communications',
				'EM Marketing' => 'EM Marketing - University Marketing and Communications',
				'University Events' => 'University Events - University Marketing and Communications',
				'News and Media Relations' => 'News and Media Relations - University Marketing and Communications',
				'Presidential Communications' => 'Presidential Communications - University Marketing and Communications',
				'Visual Design' => 'Visual Design - University Marketing and Communications',
				'Photo Services' => 'Photo Services - University Marketing and Communications',
				'Video Services' => 'Video Services - University Marketing and Communications',
				'WA State Magazine' => 'WA State Magazine and Content Development - University Marketing and Communications',
				'Web Communications' => 'Web Communications - University Marketing and Communications',
				'University Publishing' => 'University Publishing - University Marketing and Communications',
				'Coug Prints Plus' => 'Coug Prints Plus - University Marketing and Communications',
				'Graphic Design' => 'Graphic Design - University Marketing and Communications',
				'Mailing Services' => 'Mailing Services - University Marketing and Communications',
				'Printing Services' => 'Printing Services - University Marketing and Communications',
				'Production Coordination' => 'Production Coordination - University Marketing and Communications',
				'WSU Press' => 'WSU Press - University Marketing and Communications',
			);
		}

		if ( 'wsuwp_university_location' === $taxonomy ) {
			$updated_terms = array(
				'WSU North Puget Sound at Everett' => 'WSU Everett',
				'WSU Spokane' => 'WSU Health Sciences Spokane',
			);
		}

		return $updated_terms;
	}

	/**
	 * Returns default taxonomies.
	 *
	 * @return array Taxonomies to include in the custom metabox.
	 */
	public function get_default_metabox_taxonomies() {
		$taxonomies = array(
			'wsuwp_university_org',
			'wsuwp_university_location',
			'wsuwp_university_category',
			'category',
			'post_tag',
		);

		return apply_filters( 'wsuwp_taxonomy_metabox_taxonomies', $taxonomies );
	}

	/**
	 * Returns default post types.
	 *
	 * @return array Post types for which to display the custom taxonomy metabox.
	 */
	public function get_default_metabox_post_types() {
		$taxonomies = $this->get_default_metabox_taxonomies();

		$post_types = array(
			'page' => $taxonomies,
			'post' => $taxonomies,
		);

		return apply_filters( 'wsuwp_taxonomy_metabox_post_types', $post_types );
	}

	/**
	 * Replace the default taxonomy metaboxes with our own.
	 *
	 * @param $string $post_type
	 * @param string $context
	 */
	public function taxonomy_meta_boxes( $post_type, $context ) {
		if ( 'side' !== $context ) {
			return;
		}

		if ( false === apply_filters( 'wsu_taxonomy_select2_interface', true ) ) {
			return;
		}

		$post_types = $this->get_default_metabox_post_types();

		if ( ! in_array( $post_type, array_keys( $post_types ), true ) ) {
			return;
		}

		foreach ( $post_types[ $post_type ] as $taxonomy ) {
			if ( get_taxonomy( $taxonomy )->hierarchical ) {
				remove_meta_box( $taxonomy . 'div', $post_type, 'side' );
			} else {
				remove_meta_box( 'tagsdiv-' . $taxonomy, $post_type, 'side' );
			}
		}

		add_meta_box(
			'wsuwp-university-taxonomies',
			'Taxonomies',
			array( $this, 'display_university_taxonomies_meta_box' ),
			array_keys( $post_types ),
			'side',
			'low'
		);
	}

	/**
	 * Provides a filter for easily disabling the term adding interface.
	 *
	 * This is only for the Edit/Add New {Post Type} view,
	 * and has no impact on the taxonomy's dashboard page.
	 *
	 * @return array Taxonomies for which to disable the term adding interface.
	 */
	public function disable_term_adding_interface() {
		$taxonomies = array(
			'wsuwp_university_org',
			'wsuwp_university_location',
			'wsuwp_university_category',
		);

		return apply_filters( 'wsuwp_taxonomy_metabox_disable_new_term_adding', $taxonomies );
	}

	/**
	 * Provides the term adding interface for heirarchical taxonomies.
	 *
	 * @param object $taxonomy Taxonomy settings.
	 */
	public function term_adding_interface( $taxonomy ) {
		$name = $taxonomy->name;
		$label = $taxonomy->labels->singular_name;
		?>
		<div id="<?php echo esc_attr( $name ); ?>-adder"
			 class="wp-hidden-children">

			<a id="<?php echo esc_attr( $name ); ?>-add-toggle"
			   href="#<?php echo esc_attr( $name ); ?>-add"
			   class="hide-if-no-js taxonomy-add-new">+ Add New <?php echo esc_html( $label ); ?></a>

			<p id="<?php echo esc_attr( $name ); ?>-add"
			   class="<?php echo esc_attr( $name ); ?>-add wp-hidden-child">

				<label class="screen-reader-text"
					   for="new<?php echo esc_attr( $name ); ?>">Add New <?php echo esc_html( $label ); ?></label>

				<input type="text"
					   name="new<?php echo esc_attr( $name ); ?>"
					   id="new<?php echo esc_attr( $name ); ?>"
					   class="form-required"
					   value=""
					   aria-required="true"
					   autocomplete="off">

				<label class="screen-reader-text"
					   for="new<?php echo esc_attr( $name ); ?>_parent">Parent <?php echo esc_html( $label ); ?>:</label>

				<?php
				wp_dropdown_categories( array(
					'class' => 'postform',
					'hide_empty' => false,
					'hierarchical' => true,
					'id' => 'new' . $name . '_parent',
					'name' => 'new' . $name . '_parent',
					'show_option_none' => '— Parent ' . $label . ' —',
					'option_none_value'  => '0',
					'taxonomy' => $name,
				) );
				?>

				<input type="button"
					   id="<?php echo esc_attr( $name ); ?>-add-submit"
					   class="button term-add-submit"
					   value="Add New <?php echo esc_attr( $label ); ?>">

			</p>

		</div>
		<?php
	}

	/**
	 * Display the metabox for selecting taxonomy terms.
	 */
	public function display_university_taxonomies_meta_box( $post ) {
		wp_nonce_field( 'wsuwp_select2_interface', 'wsuwp_select2_nonce' );

		// Ensure that only the appropriate taxonomies are displayed for the current post type.
		$post_types = $this->get_default_metabox_post_types();
		$post_type_taxonomies = ( isset( $post_types[ get_post_type() ] ) ) ? $post_types[ get_post_type() ] : $this->get_default_metabox_taxonomies();
		$taxonomies = array_intersect( $post_type_taxonomies, get_object_taxonomies( $post ) );

		foreach ( $taxonomies as $taxonomy ) {
			$taxonomy_settings = get_taxonomy( $taxonomy );
			$id = $taxonomy . '-select';
			?>

			<p class="post-attributes-label-wrapper">
				<label class="post-attributes-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( get_taxonomy( $taxonomy )->labels->name ); ?></label>
			</p>

			<?php
			$dropdown_args = array(
				'class' => 'taxonomy-select2',
				'echo' => false,
				'hide_empty' => false,
				'id' => $id,
				'name' => 'tax_input[' . $taxonomy . '][]',
				'taxonomy' => $taxonomy,
			);

			if ( $taxonomy_settings->hierarchical ) {
				$dropdown_args['hierarchical'] = true;
			} else {
				$dropdown_args['value_field'] = 'name';
			}

			$dropdown = wp_dropdown_categories( $dropdown_args );

			$additional_attributes = 'multiple="multiple" style="width: 100%"';

			if ( ! $taxonomy_settings->hierarchical ) {
				$additional_attributes = $additional_attributes . ' data-tags="true" data-token-separators=","';
			}

			$dropdown = str_replace( '<select', '<select ' . $additional_attributes, $dropdown );

			$dropdown = str_replace( '&nbsp;', '', $dropdown );

			$selected_terms = get_the_terms( $post->ID, $taxonomy );

			if ( $selected_terms && ! is_wp_error( $selected_terms ) ) {
				foreach ( $selected_terms as $term ) {
					$value = ( $taxonomy_settings->hierarchical ) ? $term->term_id : $term->name;
					$dropdown = str_replace( 'value="' . $value . '"', 'value="' . $value . '" selected="selected"', $dropdown );
				}
			}

			$allowed = array(
				'select' => array(
					'class' => array(),
					'name' => array(),
					'id' => array(),
					'multiple' => array(),
					'style' => array(),
					'data-tags' => array(),
					'data-token-separators' => array(),
				),
				'option' => array(
					'class' => array(),
					'value' => array(),
					'selected' => array(),
				),
			);

			echo wp_kses( $dropdown, $allowed );

			if ( $taxonomy_settings->hierarchical && ! in_array( $taxonomy, $this->disable_term_adding_interface(), true ) ) {
				$this->term_adding_interface( $taxonomy_settings );
			}
		}
	}

	/**
	 * Adds a term to a hierarchical taxonomy.
	 */
	public function ajax_add_term() {
		check_admin_referer( 'wsuwp-add-term', 'nonce' );

		$taxonomy = sanitize_text_field( $_POST['taxonomy'] );
		$parent = absint( $_POST['parent'] );
		$term = sanitize_text_field( $_POST['term'] );
		$term_slug = sanitize_title( $term );

		$inserted_term = wp_insert_term(
			$term,
			$taxonomy,
			array(
				'parent' => $parent,
				'slug' => $term_slug,
			)
		);

		// Bail if something has gone wrong.
		if ( is_wp_error( $inserted_term ) ) {
			wp_send_json_error();
		}

		// Get the complete term object.
		$new_term = get_term( $inserted_term['term_id'], $taxonomy );

		// Determine a logical insertion point for the new term.
		$all_terms = get_terms( array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
			'fields' => 'ids',
			'parent' => $parent,
		) );

		$new_term_index = array_search( $inserted_term['term_id'], $all_terms, true );

		if ( 0 !== $new_term_index ) {
			$insert_after = $all_terms[ $new_term_index - 1 ];
		} else {
			$insert_after = $parent;
		}

		// Pass along the ID of the existing term that the new term should be inserted after.
		$new_term->wsuwp_insert_after = $insert_after;

		echo wp_json_encode( $new_term );

		exit();
	}

	/**
	 * Ensures that a post's terms are properly updated
	 * when all terms for a given taxonomy are removed.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( ! isset( $_POST['wsuwp_select2_nonce'] ) || ! wp_verify_nonce( $_POST['wsuwp_select2_nonce'], 'wsuwp_select2_interface' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		// The nonce check should prevent us from getting this far,
		// but just for good measure, we'll check if the post type
		// is in the whitelist before proceeding.
		$post_types = $this->get_default_metabox_post_types();

		if ( ! in_array( $post->post_type, array_keys( $post_types ), true ) ) {
			return;
		}

		$taxonomies = get_post_taxonomies( $post_id );

		foreach ( $taxonomies as $taxonomy ) {
			// Skip over any taxonomies that aren't included in the Select2 interface.
			if ( ! in_array( $taxonomy, $post_types[ $post->post_type ], true ) ) {
				continue;
			}

			if ( ! isset( $_POST['tax_input'][ $taxonomy ] ) ) {
				wp_set_object_terms( $post_id, '', $taxonomy );
			}
		}
	}
}
new WSUWP_University_Taxonomies();
