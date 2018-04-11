<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.funbotic.com
 * @since      1.0.0
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

/**
 * Create custom fields when uploading/editing media, to allow a piece of media to be associated with an individual camper.
 */

// Advanced Custom Fields load field filter, to allow for spontaneous generation of camper names.
add_filter( 'acf/load_field/name=campers_in_media', 'funbotic_load_campers_in_media' );
// Filter before values are saved in database.
add_filter( 'acf/update_value/name=campers_in_media', 'funbotic_update_value_campers_in_media', 10, 3 );


function funbotic_load_campers_in_media( $field ) {

	$args = array(
		'role' 		=> 'subscriber',
		'orderby' 	=> 'display_name',
		'order'		=> 'ASC',
	);

	$camper_data_array = get_users( $args );
	
	// Clear choices array in case it was previously set.
	$field['choices'] = array();

	foreach ( $camper_data_array as $camper ) {
		$camper_ID = $camper->ID;
		$camper_display_name = $camper->display_name;
		$field['choices'][$camper_ID] = $camper_display_name;
	}

	/*
	// Dump variable for debugging.
	echo '<pre>';
		var_dump( $field );
	echo '</pre>';
	*/

	$id = get_the_ID();
	// This appears to be the only way to properly get the values from the field, as
	// dynamically generated checkboxes don't have a 'values' array, merely a 'choices' array at this stage.
	$previously_associated_campers = get_post_meta( $id, 'campers_in_media' );

	// We need to make sure to save the campers who are associated with this field BEFORE any changes
	// are made to it, otherwise we will not be able to accurately compare changes when updating values.
	if ( empty( $previously_associated_campers ) || is_null( $previously_associated_campers ) ) {
		update_post_meta( $id, 'funbotic_previously_associated_campers', $previously_associated_campers );
	} else {
		$new_meta = funbotic_clean_array( $previously_associated_campers );
		update_post_meta( $id, 'funbotic_previously_associated_campers', $new_meta );
	}

	return $field;
}

function funbotic_update_value_campers_in_media( $value, $field, $post_id ) {

	// Both the previous campers associated with this image as well as the current set of campers need
	// to be loaded, so they can be compared with array_diff.
	$id = get_the_ID();
	$current_post_meta = get_post_meta( $id, 'funbotic_previously_associated_campers' );
	$previously_associated_campers = funbotic_clean_array( $current_post_meta ); // Clean up current_post_meta.
	$current_associated_campers = $value;
	$new_campers = array();
	$campers_to_remove = array();

	// If both $previously_associated campers and $current_associated_campers have no data/are null.
	if ( ( empty( $previously_associated_campers ) || is_null( $previously_associated_campers ) ) && ( empty( $current_associated_campers ) || is_null( $current_associated_campers ) ) ) {
		
		return $value; // Nothing needs to happen and this function can return.

	// If only $previously_associated_campers is null.
	} elseif ( empty( $previously_associated_campers ) || is_null( $previously_associated_campers ) ) {
		
		$new_campers = funbotic_clean_array( $current_associated_campers ); // Then all the currently associated campers are new.

	// If only $current_associated_campers is null.
	} elseif ( empty( $current_associated_campers ) || is_null( $current_associated_campers ) ) {

		$campers_to_remove = funbotic_clean_array( $previously_associated_campers ); // Then all previously associated campers need to be removed.

	// If both arrays have values in them.
	} else {

		$new_campers = funbotic_clean_array( array_diff( $current_associated_campers, $previously_associated_campers ) );
		$campers_to_remove = funbotic_clean_array( array_diff( $previously_associated_campers, $current_associated_campers ) );

	}
	
	/*
	// Test
	update_post_meta( 4406, 'test_previous_campers', $previously_associated_campers );
	update_post_meta( 4406, 'test_current_campers', $current_associated_campers );
	update_post_meta( 4406, 'test_new_campers', $new_campers );
	update_post_meta( 4406, 'test_campers_to_remove', $campers_to_remove );
	*/

	// Process each new camper.  Add the ID of this image to their user_meta.
	foreach( $new_campers as $new_camper ) {
		$current_associated_images = funbotic_clean_array( get_user_meta( $new_camper, 'funbotic_associated_images' ) );
		// If the ID is already in the array, do nothing.  This is a double-check, thanks to array_diff.
		if ( in_array( $id, $current_associated_images ) ) {
			// Do nothing!
		} else {
			array_push( $current_associated_images, $id );
		} // End if/else.
		update_user_meta( $new_camper, 'funbotic_associated_images', $current_associated_images );
	}

	// Process each camper to be removed.  Remove the ID of this image from their user_meta.
	foreach( $campers_to_remove as $camper ) {
		$current_associated_images = get_user_meta( $camper, 'funbotic_associated_images' );
		if ( empty( $current_associated_images ) || is_null( $current_associated_images ) ) {
			update_user_meta( $camper, 'funbotic_associated_images', $id );
		} else {
			$cleaned_images = funbotic_clean_array( $current_associated_images );
			$id_array = array(); // array_diff function requires 2 arrays as parameters.
			array_push( $id_array, $id );
			$new_meta = funbotic_clean_array( array_diff( $cleaned_images, $id_array ) );
			update_user_meta( $camper, 'funbotic_associated_images', $new_meta );
		}
	}

	// Make sure that we save the currently associated campers as the now "previous" set of campers,
	// to be accessed as a reference when this particular post is next edited.
	update_post_meta( $id, 'funbotic_previously_associated_campers', $current_associated_campers );

	return $value;
}


// Function to force all data from the input array into a 1-dimensional array, so that array_diff
// will work properly.
function funbotic_clean_array( array $array_in ) {
	$return = array();
	array_walk_recursive( $array_in, function($a) use (&$return) { $return[] = $a; } );
	return $return;
}