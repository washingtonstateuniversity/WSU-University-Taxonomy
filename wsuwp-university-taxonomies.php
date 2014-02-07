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
	 * Fire necessary hooks when instantiated.
	 */
	function __construct() {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Register the taxonomies provided by the plugin.
	 */
	public function register_taxonomies() {
		$labels = array(
			'name'              => 'University Category',
			'search_items'      => 'Search University Categories',
			'all_items'         => 'All University Categories',
			'edit_item'         => 'Edit University Category',
			'update_item'       => 'Update University Category',
			'add_new_item'      => 'Add New University Category',
			'new_item_name'     => 'New University Category Name',
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'subject' ),
		);
		register_taxonomy( $this->university_category, array( 'post', 'page', 'attachment' ), $args );
	}
}
new WSUWP_University_Taxonomies();