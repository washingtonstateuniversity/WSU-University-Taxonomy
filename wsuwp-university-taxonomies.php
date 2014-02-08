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

		add_action( 'init',       array( $this, 'modify_default_taxonomy_labels' ) );
		add_action( 'init',       array( $this, 'register_taxonomies'            ) );
		add_action( 'admin_init', array( $this, 'compare_locations'              ) );
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
}
new WSUWP_University_Taxonomies();