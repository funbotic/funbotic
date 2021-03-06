<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.funbotic.com
 * @since      1.1.4
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

/**
 * Creates relationships between user accounts to denote parent/child relationships, giving the ability for parents to monitor their children's progress.
 */

// Advanced Custom Fields load field filter, to allow for spontaneous generation of potential parent names.
add_filter( 'acf/load_field/name=funbotic_parents', 'funbotic_load_parents', 20 );
// Advanced Custom Fields load field filter, to allow for spontaneous generation of potential child names.
add_filter( 'acf/load_field/name=funbotic_children', 'funbotic_load_children' );
// Filter before values are saved in database.
add_filter( 'acf/update_value/name=funbotic_children', 'funbotic_update_value_funbotic_children', 20, 3 );
// Needed to save user profile ID being edited.
add_action( 'edit_user_profile', 'funbotic_save_profile_ID', 10, 1 );


// This function executes at high priority, so it can save the ID of the user profile currently being edited before funbotic_load_parents() fires.
function funbotic_save_profile_ID( $profileuser ) {
	update_field( 'profile_user_id', $profileuser->ID );
}


// A camper's parents will be displayed as an uneditable text field.  Any parent/child relationships should
// only be established when editing the parent's profile.
function funbotic_load_parents( $field ) {
	/* 
	 * This line supposed to make the field read only, though this is not currently working for an unknown reason.  Current attempted fixes:
	 * Disabling all plugins but ACF and this plugin.
	 * Reverting to 2017 theme.
	 * Testing out just this plugin and ACF on a clean site.
	 * See: https://support.advancedcustomfields.com/forums/topic/set-textarea-to-uneditable/#post-60729
	 */
	$field['readonly'] = 1;
	return $field;
}


// Same idea as funbotic_load_campers_in_media, from funbotic-media-fields.php.
function funbotic_load_children( $field ) {

	$user_id = (int) get_field( 'profile_user_id' );

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

	
	// This appears to be the only way to properly get the values from the field, as
	// dynamically generated checkboxes don't have a 'values' array, merely a 'choices' array at this stage.
	$previously_associated_children = get_the_author_meta( 'funbotic_children', $user_id);
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

	// Both the previous children associated with this profile as well as the current set of children need
	// to be loaded, so they can be compared with array_diff.
	$user_id = (int) get_field( 'profile_user_id' );
	$current_user_meta = get_the_author_meta( 'funbotic_previously_associated_children', $user_id );
	
	if ( ( empty( $current_user_meta ) || is_null( $current_user_meta ) ) ) {
		$previously_associated_children = array();
	} else {
		$previously_associated_children = funbotic_clean_array( $current_user_meta ); // Clean up current_user_meta.
	}

	if ( ( empty( $value ) || is_null( $value ) ) ) {
		$current_associated_children = array();
	} else {
		$current_associated_children = funbotic_clean_array( $value ); // Clean up value.
	}

	$temp_new = array_diff( $current_associated_children, $previously_associated_children );
	$new_children = funbotic_clean_array( $temp_new );

	$temp_remove = array_diff( $previously_associated_children, $current_associated_children );
	$children_to_remove = funbotic_clean_array( $temp_remove );


	// TEST
	/*
	update_user_meta( $user_id, 'funbotic_test_previously_associated_children', $previously_associated_children );
	update_user_meta( $user_id, 'funbotic_test_current_associated_children', $current_associated_children );
	update_user_meta( $user_id, 'funbotic_test_value', $value );
	update_user_meta( $user_id, 'funbotic_test_new_children', $new_children );
	update_user_meta( $user_id, 'funbotic_test_children_to_remove', $children_to_remove );
	update_user_meta( $user_id, 'funbotic_test_user_id', $user_id );
	*/


	// NOTE: funbotic_associated_parents only exists as a custom user_meta field.  It does not and should not exist as an ACF field.
	// funbotic_associated_parents should merely be an internal listing of the IDs of all users who have a parent relationship to a given camper's user profile.

	// Process each new child.  Add the ID of this parent to their user_meta.
	foreach( $new_children as $new_child ) {
		$user_meta = get_the_author_meta( 'funbotic_associated_parents', $new_child );

		if ( empty( $user_meta ) || is_null( $user_meta ) ) {
			$current_associated_parents = array();
		} else {
			$current_associated_parents = funbotic_clean_array( $user_meta );
		}

		// If the ID is already in the array, do nothing.  This is a double-check, thanks to array_diff.
		if ( in_array( $user_id, $current_associated_parents ) ) {
			// Do nothing!
		} else {
			array_push( $current_associated_parents, $user_id );
			update_user_meta( $new_child, 'funbotic_associated_parents', $current_associated_parents );
			funbotic_generate_acf_parent_textarea( $new_child );
		} // End if/else.
	}
	

	// Process each child to be removed.  Remove the ID of this user from their user_meta.
	foreach( $children_to_remove as $child ) {
		$current_associated_parents = get_the_author_meta( 'funbotic_associated_parents', $child );
		if ( empty( $current_associated_parents ) || is_null( $current_associated_parents ) ) {
			// Nothing else needs to be done besides regenerate the textarea.
			//funbotic_generate_acf_parent_textarea( $child );
			update_user_meta( $child, 'funbotic_parents', '' );
		} else {
			$cleaned_array = funbotic_clean_array( $current_associated_parents );
			$id_array = array(); // array_diff function requires 2 arrays as parameters.
			array_push( $id_array, $user_id );
			$array_diff = array_diff( $id_array, $cleaned_array );
			$new_meta = funbotic_clean_array( $array_diff );
			// TEST
			/*
			update_user_meta( $child, 'funbotic_test_id_array', $id_array );
			update_user_meta( $child, 'funbotic_test_array_diff', $array_diff );
			update_user_meta( $child, 'funbotic_test_new_meta', $new_meta );
			*/

			update_user_meta( $child, 'funbotic_associated_parents', $new_meta );
			funbotic_generate_acf_parent_textarea( $child );
		}
	}

	// Make sure that we save the currently associated children as the now "previous" set of children,
	// to be accessed as a reference when this particular post is next edited.
	update_user_meta( $user_id, 'funbotic_previously_associated_children', $current_associated_children );

	return $value;
}


// This is a helper function that generates a formatted text string displaying all of the users who are registered as parents
// of the profile whose ID is entered as a parameter.  The text string is saved in the user's funbotic_parents ACF field metadata.
function funbotic_generate_acf_parent_textarea( $user_id_in ) {
	$parent_IDs = get_the_author_meta( 'funbotic_associated_parents', $user_id_in );

	$textarea_string = '';

	if ( ( ! empty( $parent_IDs ) || ! is_null( $parent_IDs ) ) ) {

		foreach ( $parent_IDs as $parent ) {
			$last_name = get_the_author_meta( 'last_name', $parent );
			$first_name = get_the_author_meta( 'first_name', $parent );

			$textarea_string .= $last_name . ', ' . $first_name . '
'; // This line NEEDS to be formatted this way in order for ACF to register a line break properly.  Yeah, it's annoying and looks ugly.  But it works.
		}
	}

	// TEST
	// $textarea_string .= '|GOT TO END OF funbotic_generate_acf_parent_textarea|';

	update_user_meta( $user_id_in, 'funbotic_parents', $textarea_string );
}