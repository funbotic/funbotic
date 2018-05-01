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
//add_filter( 'acf/load_field/name=funbotic_parents', 'funbotic_load_parents' );
// Filter before values are saved in database.
//add_filter( 'acf/update_value/name=funbotic_parents', 'funbotic_update_value_funbotic_parents', 10, 3 );

// Advanced Custom Fields load field filter, to allow for spontaneous generation of potential child names.
add_filter( 'acf/load_field/name=funbotic_children', 'funbotic_load_children' );
// Filter before values are saved in database.
add_filter( 'acf/update_value/name=funbotic_children', 'funbotic_update_value_funbotic_children', 10, 3 );

add_action( 'personal_options_update', 'funbotic_load_children_prepare', 10, 1 );
add_action( 'edit_user_profile_update', 'funbotic_load_children_prepare', 10, 1 );


// Super janky function to save meta value for ID of profile being currently edited.  I'm so sorry.  Couldn't figure out another way to do this.
function funbotic_load_children_prepare( $profileuser ) {
	update_user_meta( get_current_user_id(), 'funbotic_user_being_edited', $profileuser->ID );
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

	$user_id = get_user_meta( get_current_user_id(), 'funbotic_user_being_edited' );
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

	return $field;

}


function funbotic_update_value_funbotic_parents( $value, $field, $post_id ) {

	// Both the previous parents associated with this image as well as the current set of parents need
	// to be loaded, so they can be compared with array_diff.
	$user_id = get_user_meta( get_current_user_id(), 'funbotic_user_being_edited' );
	$current_user_meta = get_user_meta( $user_id, 'funbotic_previously_associated_parents' );
	$previously_associated_parents = funbotic_clean_array( $current_user_meta ); // Clean up current_user_meta.
	$current_associated_parents = $value;
	$new_parents = array();
	$parents_to_remove = array();

	// If both $previously_associated_parents and $current_associated_parents have no data/are null.
	if ( ( empty( $previously_associated_parents ) || is_null( $previously_associated_parents ) ) && ( empty( $current_associated_parents ) || is_null( $current_associated_parents ) ) ) {
		
		return $value; // Nothing needs to happen and this function can return.

	// If only $previously_associated_parents is null.
	} elseif ( empty( $previously_associated_parents ) || is_null( $previously_associated_parents ) ) {
		
		$new_parents = funbotic_clean_array( $current_associated_parents ); // Then all the currently associated parents are new.

	// If only $current_associated_parents is null.
	} elseif ( empty( $current_associated_parents ) || is_null( $current_associated_parents ) ) {

		$parents_to_remove = funbotic_clean_array( $previously_associated_parents ); // Then all previously associated parents need to be removed.

	// If both arrays have values in them.
	} else {

		$new_parents = funbotic_clean_array( array_diff( $current_associated_parents, $previously_associated_parents ) );
		$parents_to_remove = funbotic_clean_array( array_diff( $previously_associated_parents, $current_associated_parents ) );

	}

	// Process each new parent.  Add the ID of this parent to their user_meta.
	foreach( $new_parents as $new_parent ) {
		$current_associated_children = funbotic_clean_array( get_user_meta( $new_parent, 'funbotic_associated_children' ) );
		// If the ID is already in the array, do nothing.  This is a double-check, thanks to array_diff.
		if ( in_array( $user_id, $current_associated_children ) ) {
			// Do nothing!
		} else {
			array_push( $current_associated_children, $user_id );
		} // End if/else.
		update_user_meta( $new_parent, 'funbotic_associated_children', $current_associated_parents );
	}

	// Process each parent to be removed.  Remove the ID of this image from their user_meta.
	foreach( $parents_to_remove as $parent ) {
		$current_associated_children = get_user_meta( $parent, 'funbotic_associated_children' );
		if ( empty( $current_associated_children ) || is_null( $current_associated_children ) ) {
			update_user_meta( $parent, 'funbotic_associated_children', $user_id );
		} else {
			$cleaned_array = funbotic_clean_array( $current_associated_children );
			$id_array = array(); // array_diff function requires 2 arrays as parameters.
			array_push( $id_array, $user_id );
			$new_meta = funbotic_clean_array( array_diff( $cleaned_array, $id_array ) );
			update_user_meta( $parent, 'funbotic_associated_children', $new_meta );
		}
	}

	// Make sure that we save the currently associated parents as the now "previous" set of parents,
	// to be accessed as a reference when this particular post is next edited.
	update_user_meta( $user_id, 'funbotic_previously_associated_parents', $current_associated_parents );

	return $value;
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

	$user_id = (int) get_user_meta( get_current_user_id(), 'funbotic_user_being_edited', true );
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

	// Dump variable for debugging.
	$current_post = get_post();
	echo '<pre>';
		echo 'Vardump: ';
		var_dump( $user_id );
	echo '</pre>';

	return $field;
}


function funbotic_update_value_funbotic_children( $value, $field, $post_id ) {

	// Both the previous children associated with this user as well as the current set of children need
	// to be loaded, so they can be compared with array_diff.
	$user_id = (int) get_user_meta( get_current_user_id(), 'funbotic_user_being_edited', true );

	echo '<pre>';
		echo 'Update Value Vardump: ';
		var_dump( $user_id );
	echo '</pre>';

	$raw_meta = get_user_meta( $user_id, 'funbotic_previously_associated_children' );
	$current_associated_children = $value;
	$new_children = array();
	$children_to_remove = array();

	if ( empty( $raw_meta ) || is_null( $raw_meta ) ) {
		$previously_associated_children = array();
	} else {
		$previously_associated_children = funbotic_clean_array( $current_user_meta ); // Clean up current_user_meta.
	}

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

	// Test
	update_user_meta( 116, 'test_id', $user_id );
	update_user_meta( 116, 'test_current_associated_children', $current_associated_children );
	update_user_meta( 116, 'test_new_children', $new_children );
	update_user_meta( 116, 'test_children_to_remove', $children_to_remove );

	// Process each new child.  Add the ID of this parent to their user_meta.
	foreach( $new_children as $new_child ) {
		$raw_meta = get_user_meta( $new_child, 'funbotic_associated_parents' );

		if ( empty( $raw_meta ) || is_null( $raw_meta ) ) {
			$current_associated_parents = array();
		} else {
			$current_associated_parents = funbotic_clean_array( $raw_meta );
		}
		
		// If the ID is already in the array, do nothing.  This is a double-check, thanks to array_diff.
		if ( in_array( $user_id, $current_associated_parents ) ) {
			// Do nothing!
		} else {
			array_push( $current_associated_parents, $user_id );
		} // End if/else.
		update_user_meta( $new_child, 'funbotic_associated_parents', $current_associated_parents );
	}

	// Process each child to be removed.  Remove this parent's ID from their user_meta.
	foreach( $children_to_remove as $child ) {
		$current_associated_parents = get_user_meta( $child, 'funbotic_associated_parents' );

		if ( empty( $current_associated_parents ) || is_null( $current_associated_parents ) ) {
			update_user_meta( $child, 'funbotic_associated_parents', $user_id );
		} else {
			$cleaned_array = funbotic_clean_array( $current_associated_parents );
			$id_array = array(); // array_diff function requires 2 arrays as parameters.
			array_push( $id_array, $user_id );
			$new_meta = funbotic_clean_array( array_diff( $cleaned_array, $id_array ) );
			update_user_meta( $child, 'funbotic_associated_parents', $new_meta );
		}
	}

	// Make sure that we save the currently associated children as the now "previous" set of children,
	// to be accessed as a reference when this particular post is next edited.
	update_user_meta( $user_id, 'funbotic_previously_associated_children', $current_associated_children );

	return $value;
}