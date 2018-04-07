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
 * 
 * Modified from: https://code.tutsplus.com/articles/how-to-add-custom-fields-to-attachments--wp-31100
 */

add_filter( 'attachment_fields_to_edit', 'funbotic_apply_media_fields_filter', 11, 2 );
add_filter( 'attachment_fields_to_save', 'funbotic_save_media_fields', 11, 2 );


function funbotic_apply_media_fields_filter( $form_fields, $post = null ) {
	// This is used to ensure each camper's checkbox has a unique ID associated with it.
	$camper_counter = 1;

    // If the post in question is an image.
    if ( preg_match( "/" . 'image' . "/", $post->post_mime_type ) ) {
		// Get saved metadata of currently associated images.
		$current_associated_images = get_post_meta( $post->ID, 'funbotic_associated_images', true );
		
		// Generate current list of all campers.
		$camper_list = funbotic_generate_camper_data_array();

		?>
		<form action="/funbotic-media-fields.php" method="post">
		<div><label for="camper_selection">Please select all campers present in this image.  This image will show up in the camper\'s private profile, so please ensure they are clearly visible.</label></div>
		<?php
		// For each camper, determine if they are already associated with this image, then generate html to create a checkbox that is checked or unchecked, as appropriate.
		foreach ( $camper_list as $camper ) {
			$string_array = explode('(' , rtrim($camper, ')'));
			(int) $current_ID = $string_array[1];
			
			if ( in_array($current_ID, $current_associated_images) ) {
				$checked = ' checked="checked"';
			} else {
				$checked = '';
			} // End if/else.
			
			// Create array of checkboxes for ease of processing when saving data, hence the usage of camper_selection[] as name.
			?>
			<div><input<?php echo esc_attr( $checked )?> type="checkbox" name="camper_selection[]" value="<?php echo esc_attr( $camper )?>">
			<label><?php echo esc_attr( $camper )?></label></div>
			<?php

			$camper_counter++;
		}
		?>
		</form>
		<?php
	
    // If the post in question is a video.
    } elseif ( preg_match( "/" . 'video' . "/", $post->post_mime_type ) ) {
        $meta = get_post_meta( $post->ID, 'funbotic_associated_videos', true );

	} // End elseif.
} // End function funbotic_apply_filter.


function funbotic_save_media_fields( $post, $attachment ) {
	// If the post in question is an image.
    //if ( preg_match( "/" . 'image' . "/", $post->post_mime_type ) ) { <-- PROBLEM LINE
		//update_post_meta( $post['ID'], 'funbotic_associated_images', 'test2 succesful' );
		// Get saved metadata of currently associated images.
		$current_associated_images = get_post_meta( $post->ID, 'funbotic_associated_images', true );

		if ( !empty( $_POST['camper_selection'] ) ) {
			foreach ( $_POST['camper_selection'] as $selected ) {
				update_post_meta( $post['ID'], '_image_subject', $selected->value );
				$string_array = explode('(' , rtrim($selected, ')'));
				(int) $current_ID = $string_array[1];

				if ( in_array( $current_ID, $current_associated_images ) ) {
					// Do nothing, as the current ID is already saved as a currently associated image.
				} else {
					array_push( $current_associated_images, $current_ID );
				} // End if/else.
			} // End foreach.
		//} // End if.
		update_post_meta( $post['ID'], 'funbotic_associated_images', $current_associated_images );

	// If the post in question is a video.
    } elseif ( preg_match( "/" . 'video' . "/", $post->post_mime_type ) ) {
        $meta = get_post_meta( $post->ID, 'funbotic_associated_videos', true );

	} // End elseif.
} // End function funbotic_save_media_fields.


// Generates an array consisting of the display name of a camper and their ID, to be used as a list of options when
// selecting which camper is featured in a piece of media.  ID can be extracted from the string when needed.
function funbotic_generate_camper_data_array() {
	$args = array(
		'role' 		=> 'subscriber',
		'orderby' 	=> 'display_name',
		'order'		=> 'ASC',
	);

	$camper_data_array = get_users( $args );

	$return_array = array();

	foreach( $camper_data_array as $camper ) {
		$camper_ID = $camper->ID;
		$camper_display_name = $camper->display_name;
		$camper_string = $camper_display_name . " (" . $camper_ID . ")";
		array_push( $return_array, $camper_string );
	}
	return $return_array;
}