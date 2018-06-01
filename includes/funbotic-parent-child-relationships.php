<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.funbotic.com
 * @since      1.1.1
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

/**
 * Creates relationships between user accounts to denote parent/child relationships, giving the ability for parents to monitor their children's progress.
 */

// Advanced Custom Fields load field filter, to allow for spontaneous generation of potential parent names.
add_filter( 'acf/load_field/name=funbotic_parents', 'funbotic_load_parents' );
// Advanced Custom Fields load field filter, to allow for spontaneous generation of potential child names.
add_filter( 'acf/load_field/name=funbotic_children', 'funbotic_load_children' );
// Filter before values are saved in database.
add_filter( 'acf/update_value/name=funbotic_children', 'funbotic_update_value_funbotic_children', 10, 3 );
// Needed to save user profile ID being edited.
add_action( 'edit_user_profile', 'funbotic_save_profile_ID', 10, 1 );


// This function executes at high priority, so it can save the ID of the user profile currently being edited before funbotic_load_parents() fires.
function funbotic_save_profile_ID( $profileuser ) {
	update_field( 'profile_user_id', $profileuser->ID );
}


// Same idea as funbotic_load_campers_in_media, from funbotic-media-fields.php.
function funbotic_load_parents( $field ) {

	$args = array(
		'role' 		=> 'customer',
		'orderby' 	=> 'display_name',
		'order'		=> 'ASC',
	);

	$parent_data_array = get_users( $args );
	
	// Clear choices array in case it was previously set.
	$field['choices'] = array();

	foreach ( $parent_data_array as $parent ) {
		$parent_ID = $parent->ID;
		$parent_display_name = $parent->display_name;
		$field['choices'][$parent_ID] = $parent_display_name;
	}

	$user_id = (int) get_field( 'profile_user_id' );
	// This appears to be the only way to properly get the values from the field, as
	// dynamically generated checkboxes don't have a 'values' array, merely a 'choices' array at this stage.
	$previously_associated_parents = get_user_meta( $user_id, 'funbotic_parents' );
	// We need to make sure to save the parents who are associated with this user BEFORE any changes
	// are made to it, otherwise we will not be able to accurately compare changes when updating values.

	if ( empty( $previously_associated_parents ) || is_null( $previously_associated_parents ) ) {
		update_user_meta( $user_id, 'funbotic_previously_associated_parents', $previously_associated_parents );
	} else {
		$new_meta = funbotic_clean_array( $previously_associated_parents );
		update_user_meta( $user_id, 'funbotic_previously_associated_parents', $new_meta );
	}

	//TEST
	var_dump( $user_id );

	return $field;

}


// Same idea as funbotic_load_campers_in_media, from funbotic-media-fields.php.
function funbotic_load_children( $field ) {

	$args = array(
		'role' 		=> 'subscriber',
		'orderby' 	=> 'display_name',
		'order'		=> 'ASC',
	);

	$child_data_array = get_users( $args );
	
	// Clear choices array in case it was previously set.
	$field['choices'] = array();

	foreach ( $child_data_array as $child ) {
		$child_ID = $child->ID;
		$child_display_name = $child->display_name;
		$field['choices'][$child_ID] = $child_display_name;
	}

	$user_id = (int) $user_id;
	// This appears to be the only way to properly get the values from the field, as
	// dynamically generated checkboxes don't have a 'values' array, merely a 'choices' array at this stage.
	$previously_associated_children = get_user_meta( $user_id, 'funbotic_children' );
	// We need to make sure to save the children who are associated with this user BEFORE any changes
	// are made to it, otherwise we will not be able to accurately compare changes when updating values.

	if ( empty( $previously_associated_children ) || is_null( $previously_associated_children ) ) {
		update_user_meta( $user_id, 'funbotic_previously_associated_children', $previously_associated_children );
	} else {
		$new_meta = funbotic_clean_array( $previously_associated_children );
		update_user_meta( $user_id, 'funbotic_previously_associated_children', $new_meta );
	}

	return $field;
}


function funbotic_update_value_funbotic_children( $value, $field, $post_id ) {

	// Both the previous children associated with this image as well as the current set of children need
	// to be loaded, so they can be compared with array_diff.
	$user_id = (int) $user_id;
	$current_user_meta = get_user_meta( $user_id, 'funbotic_previously_associated_children' );
	$previously_associated_children = funbotic_clean_array( $current_user_meta ); // Clean up current_user_meta.
	$current_associated_children = $value;
	$new_children = array();
	$children_to_remove = array();

	// If both $previously_associated_children and $current_associated_children have no data/are null.
	if ( ( empty( $previously_associated_children ) || is_null( $previously_associated_children ) ) && ( empty( $current_associated_children ) || is_null( $current_associated_children ) ) ) {
		
		return $value; // Nothing needs to happen and this function can return.

	// If only $previously_associated_children is null.
	} elseif ( empty( $previously_associated_children ) || is_null( $previously_associated_children ) ) {
		
		$new_children = funbotic_clean_array( $current_associated_children ); // Then all the currently associated children are new.

	// If only $current_associated_children is null.
	} elseif ( empty( $current_associated_children ) || is_null( $current_associated_children ) ) {

		$children_to_remove = funbotic_clean_array( $previously_associated_children ); // Then all previously associated children need to be removed.

	// If both arrays have values in them.
	} else {

		$new_children = funbotic_clean_array( array_diff( $current_associated_children, $previously_associated_children ) );
		$children_to_remove = funbotic_clean_array( array_diff( $previously_associated_children, $current_associated_children ) );

	}

	// Process each new child.  Add the ID of this parent to their user_meta.
	foreach( $new_children as $new_child ) {
		$current_associated_parents = funbotic_clean_array( get_user_meta( $new_child, 'funbotic_associated_parents' ) );
		// If the ID is already in the array, do nothing.  This is a double-check, thanks to array_diff.
		if ( in_array( $user_id, $current_associated_parents ) ) {
			// Do nothing!
		} else {
			array_push( $current_associated_parents, $user_id );
		} // End if/else.
		update_user_meta( $new_child, 'funbotic_associated_parents', $current_associated_parents );
	}

	// Process each child to be removed.  Remove the ID of this image from their user_meta.
	foreach( $children_to_remove as $child ) {
		$current_associated_children = get_user_meta( $child, 'funbotic_associated_children' );
		if ( empty( $current_associated_children ) || is_null( $current_associated_children ) ) {
			update_user_meta( $child, 'funbotic_associated_children', $user_id );
		} else {
			$cleaned_array = funbotic_clean_array( $current_associated_children );
			$id_array = array(); // array_diff function requires 2 arrays as parameters.
			array_push( $id_array, $user_id );
			$new_meta = funbotic_clean_array( array_diff( $cleaned_array, $id_array ) );
			update_user_meta( $child, 'funbotic_associated_children', $new_meta );
		}
	}

	// Make sure that we save the currently associated children as the now "previous" set of children,
	// to be accessed as a reference when this particular post is next edited.
	update_user_meta( $user_id, 'funbotic_previously_associated_children', $current_associated_children );

	return $value;
}