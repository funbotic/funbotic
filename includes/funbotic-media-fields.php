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
 * Registers bulk action to tag multiple camper's status in regards to attendance of current camp session.
 */

// Add capability to upload and edit media files to the Group Leader role, as this will be necessary for
// group leaders to do their own photo tagging of campers.
add_action( 'init', 'funbotic_add_group_leader_media_permissions');
// Filter to add custom rule to value to allow a field to only show up when the attachment to a post is an image.
add_filter( 'acf/location/rule_values/ef_media', 'acf_location_rule_values_ef_media' );
// Rule match for funbotic_acf_rule_values_attachment_image.
// add_filter( 'acf/location/rule_match/ef_media', 'acf_location_rule_match_ef_media', 10, 3 );
// Advanced Custom Fields load field filter, to allow for spontaneous generation of camper names.
add_filter( 'acf/load_field/name=campers_in_media', 'funbotic_load_campers_in_media' );
// Filter before values are saved in database.
add_filter( 'acf/update_value/name=campers_in_media', 'funbotic_update_value_campers_in_media', 10, 3 );

// Bulk action filters.
add_filter( 'bulk_actions-users', 'funbotic_register_bulk_action_enable_camper_current_session_status' );
add_filter( 'bulk_actions-users', 'funbotic_register_bulk_action_disable_camper_current_session_status' );
add_filter( 'handle_bulk_actions-users', 'funbotic_users_bulk_action_handler', 10, 3 );
// Action for notice after using bulk action for users.
add_action( 'admin_notices', 'funbotic_bulk_action_alter_camper_current_session_status' );

// Filters to display current camper enrollment status on the edit users admin screen/table.
add_filter( 'manage_users_columns', 'funbotic_user_table_camper_current_session_status' );
add_filter( 'manage_users_custom_column', 'funbotic_modify_user_table_media_fields', 10, 3 );

function funbotic_add_group_leader_media_permissions() {
	// Use of static variable to ensure function only runs once.
	static $has_run = false;

	if ( $has_run === true ) {
		// Do nothing.
	} else {
		$role = get_role( 'group_leader' );
		$role->add_cap( 'upload_files' );
		$role->add_cap( 'edit_posts' );
		$role->add_cap( 'edit_published_posts' );
		$role->add_cap( 'delete_posts' );
		$role->add_cap( 'edit_others_posts' );
		$has_run = true;
	}
}


function acf_location_rule_values_ef_media ( $choices ) {
	$choices['image'] = 'Image';

	return $choices;
}


// Apparently this is impossible?
// https://support.advancedcustomfields.com/forums/topic/custom-location-rules-for-attachment-modals/
// This was an attempt to make it so that the option to tag campers in images would only be available for an actual image, and not any other type of media.
/*
function acf_location_rule_match_ef_media ( $match, $rule, $options ) {
	$id = get_the_ID();
	
	if ( $rule['param'] = 'post_type' && $rule['value'] = 'attachment' ) {
		if( $rule['operator'] === "==" ) {

			$match = wp_attachment_is_image( $id );

    	} elseif ( $rule['operator'] === "!=" ) {

			$match = !wp_attachment_is_image( $id );

		}
	} else {
		
		$match = false;
	}

	return $match;
}
*/


function funbotic_load_campers_in_media( $field ) {

	$args = array(
		'role' 		=> 'subscriber',
		'orderby' 	=> 'display_name',
		'order'		=> 'ASC',
		'meta_key'	=> 'funbotic_camper_current_session_status',
		'meta_value'=> 1,
	);

	$camper_data_array = get_users( $args );
	
	// Clear choices array in case it was previously set.
	$field['choices'] = array();

	foreach ( $camper_data_array as $camper ) {

		$camper_ID = $camper->ID;
		$camper_display_name = $camper->display_name;
		$field['choices'][$camper_ID] = $camper_display_name;
	}

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


// Registering the "Enable Current Camp Activity Status" bulk action.
function funbotic_register_bulk_action_enable_camper_current_session_status( $bulk_actions ) {
	$bulk_actions['enable_camper_current_session_status'] = __( 'Enable Current Camp Activity Status', 'enable_camper_current_session_status' );
	return $bulk_actions;
}


// Registering the "Disable Current Camp Activity Status" bulk action.
function funbotic_register_bulk_action_disable_camper_current_session_status( $bulk_actions ) {
	$bulk_actions['disable_camper_current_session_status'] = __( 'Disable Current Camp Activity Status', 'disable_camper_current_session_status' );
	return $bulk_actions;
}


// Bulk action handler for all actions occuring on the 'users' admin edit page.
function funbotic_users_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
	if ( $doaction === 'enable_camper_current_session_status' ) {

		foreach ( $post_ids as $post_id ) {
		
			$user_meta = get_userdata( $post_id );
			$user_roles = $user_meta->roles;

			if ( in_array( 'subscriber', $user_roles ) ) {
				update_user_meta( $post_id, 'funbotic_camper_current_session_status', 1 );
			}	
			
		}
		$redirect_to = add_query_arg( 'bulk_enabled_camper_current_session_status', count( $post_ids ), $redirect_to );
		return $redirect_to;

	} elseif ( $doaction === 'disable_camper_current_session_status' ) {

		foreach ( $post_ids as $post_id ) {

			$user_meta = get_userdata( $post_id );
			$user_roles = $user_meta->roles;

			if ( in_array( 'subscriber', $user_roles ) ) {
				update_user_meta( $post_id, 'funbotic_camper_current_session_status', 0 );
			}

		}
		$redirect_to = add_query_arg( 'bulk_disabled_camper_current_session_status', count( $post_ids ), $redirect_to );
		return $redirect_to;

	} else {

		return $redirect_to;

	}
}


// Display notices after bulk actions for enabling or disabling camper current session status.
function funbotic_bulk_action_alter_camper_current_session_status() {
	if ( ! empty( $_REQUEST['bulk_enabled_camper_current_session_status'] ) ) {

		$enabled_count = intval( $_REQUEST['bulk_enabled_camper_current_session_status'] );

		printf( '<div id="message" class="updated fade">' . _n( 'Enabled %s camper\'s current session status.',  'Enabled %s campers\' current session status.',
		$enabled_count, 'enable_camper_current_session_status' ) . '</div>', $enabled_count );

	} elseif ( ! empty( $_REQUEST['bulk_disabled_camper_current_session_status'] ) ) {

		$disabled_count = intval( $_REQUEST['bulk_disabled_camper_current_session_status'] );

		printf( '<div id="message" class="updated fade">' . _n( 'Disabled %s camper\'s current session status.',  'Disabled %s campers\' current session status.',
		$disabled_count, 'disable_camper_current_session_status' ) . '</div>', $disabled_count );

	}
}


// Function to create column for camper current session status.
function funbotic_user_table_camper_current_session_status( $column ) {
	$column['current_session_status'] = 'Currently At Camp?';
    return $column;
}


// Function to add all data to admin user table row, that should be displayed from this file (funbotic-media-fields.php)
function funbotic_modify_user_table_media_fields( $val, $column_name, $user_id ) {
	switch ( $column_name ) {

		case 'current_session_status' :
			$user_meta = get_the_author_meta( 'funbotic_camper_current_session_status', $user_id );

			if ( $user_meta === '1' ) {
				return 'Yes';
				break;
			} else {
				return 'No' ;
				break;
			}

		default:
			break;
    }
    return $val;
}


// Function to force all data from the input array into a 1-dimensional array, so that array_diff
// will work properly.
function funbotic_clean_array( array $array_in ) {
	$return = array();
	array_walk_recursive( $array_in, function($a) use (&$return) { $return[] = $a; } );
	return $return;
}