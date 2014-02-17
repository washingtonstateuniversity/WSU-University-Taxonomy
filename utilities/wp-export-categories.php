<?php
/**
 * Helper utilities to export university category information into CSV and other formats.
 */

//add_action( 'init', 'wsuwp_dump_categories_csv' );
/**
 * Dump the current categories to a CSV file.
 *
 * This file can be placed in the wp-content/mu-plugins directory. Uncomment the
 * action immediately above this comment to activate.
 */
function wsuwp_dump_categories_csv() {
	$top_level = wsuwp_get_full_category_array();

	$csv_array = array();
	foreach ( $top_level as $cat ) {
		$csv_array[] = array( $cat['name'], $cat['slug'], '', '', '', '' );
		foreach ( $cat['children'] as $lvl2 ) {
			$csv_array[] = array( $cat['name'], $cat['slug'], $lvl2['name'], $lvl2['slug'], '', '' );
			foreach ( $lvl2['children'] as $lvl3 ) {
				$csv_array[] = array( $cat['name'], $cat['slug'], $lvl2['name'], $lvl2['slug'], $lvl3['name'], $lvl3['slug'] );
			}
		}
	}

	$fp = fopen( WP_PLUGIN_DIR . '/university-categories.csv', 'w');

	foreach ($csv_array as $fields) {
		fputcsv($fp, $fields);
	}

	fclose($fp);
}

//add_action( 'init', 'wsuwp_dump_categories_php_array' );
/**
 * Echo current category data as an array assignment to be used in a PHP script.
 *
 * Uncomment the action immediately above this comment to activate.
 */
function wsuwp_dump_categories_php_array() {
	$top_level = wsuwp_get_full_category_array();

	echo '$categories = array(';
	foreach ( $top_level as $cat ) {
		echo "\n\t'" . $cat['name'] . "' => array(";
		foreach ( $cat['children'] as $lvl2 ) {
			echo "\n\t\t'" . $lvl2['name'];
			if ( empty ( $lvl2['children'] ) ) {
				echo "' => array(),";
			} else {
				echo "' => array(";
				foreach ( $lvl2['children'] as $lvl3 ) {
					echo "\n\t\t\t'" . $lvl3['name'] . "',";
				}
				echo "\n\t\t),";
			}

		}
		echo "\n\t),";
	}
	echo "\n);";
}

/**
 * Pull the full list of university categories and arrange in an array of parent
 * child relationships.
 *
 * @return array
 */
function wsuwp_get_full_category_array() {
	$terms = get_terms( 'wsuwp_university_category', array( 'hide_empty' => false, 'hierarchical'  => true, ) );
	$top_level = array();
	foreach ( $terms as $key => $term ) {
		if ( '0' === $term->parent ) {
			$top_level[ $term->term_id ] = array(
				'name' => $term->name,
				'slug' => $term->slug,
				'children' => array(),
			);
			unset( $terms[ $key ] );
		}
	}

	foreach( $terms as $key => $term ) {
		if ( array_key_exists( intval( $term->parent ), $top_level ) ) {
			$top_level[ intval( $term->parent ) ]['children'][ $term->term_id] = array(
				'name' => $term->name,
				'slug' => $term->slug,
				'children' => array(),
			);
			unset( $terms[ $key ] );
		}
	}

	foreach ( $terms as $key => $term ) {
		foreach( $top_level as $k => $parent ) {
			if ( array_key_exists( intval( $term->parent ), $parent['children'] ) ) {
				$top_level[ $k ]['children'][ intval( $term->parent ) ]['children'][ $term->term_id ] = array(
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
			unset ( $terms[ $key ] );
		}
	}
	return $top_level;
}