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

Class Funbotic_Custom_Media_Fields {
 
    private $media_fields = array();

    private $has_selected_camper = false;
    private $current_camper_id = -1;
 
    function __construct( $fields ) {
		$this->media_fields = $fields;
 
    	add_filter( 'attachment_fields_to_edit', array( $this, 'funbotic_apply_filter' ), 11, 2 );
    	add_filter( 'attachment_fields_to_save', array( $this, 'funbotic_save_fields' ), 11, 2 );
    }
 
    public function funbotic_apply_filter( $form_fields, $post = null ) {
        // If our fields array is not empty
        if ( ! empty( $this->media_fields ) ) {
            // We browse our set of options
            foreach ( $this->media_fields as $field => $values ) {
                // If the field matches the current attachment mime type
                // and is not one of the exclusions
                if ( preg_match( "/" . $values['application'] . "/", $post->post_mime_type) && ! in_array( $post->post_mime_type, $values['exclusions'] ) ) {
                    // We get the already saved field meta value
                    $meta = get_post_meta( $post->ID, '_' . $field, true );
 
                    // Define the input type to 'text' by default.  Kept in options besides select for potential future use.
                    switch ( $values['input'] ) {
                        default:
                        case 'text':
                            $values['input'] = 'text';
                            break;
                     
                        case 'textarea':
                            $values['input'] = 'textarea';
                            break;
                     
                        case 'select':
                            // Select type doesn't exist, so we will create the html manually
                            // For this, we have to set the input type to 'html'
                            $values['input'] = 'html';
                     
                            // Create the select element with the right name (matches the one that wordpress creates for custom fields)
                            $html = '<select name="attachments[' . $post->ID . '][' . $field . ']">';
                     
                            // If options array is passed
                            if ( isset( $values['options'] ) ) {
                                // Browse and add the options
                                foreach ( $values['options'] as $k => $v ) {
                                    // Set the option selected or not
                                    $string_array = explode('(' , rtrim($v, ')'));
                                    $current_ID = $string_array[1];

                                    if ( $meta == $current_ID )
                                        $selected = ' selected="selected"';
                                    else
                                        $selected = '';
                     
                                    $html .= '<option' . $selected . ' value="' . $k . '">' . $v . '</option>';
                                }
                            }
                     
                            $html .= '</select>';
                     
                            // Set the html content
                            $values['html'] = $html;
                     
                            break;
                     
                        case 'checkbox':
                            // Checkbox type doesn't exist either
                            $values['input'] = 'html';
                     
                            // Set the checkbox checked or not
                            if ( $meta == 'on' )
                                $checked = ' checked="checked"';
                            else
                                $checked = '';
                     
                            $html = '<input' . $checked . ' type="checkbox" name="attachments[' . $post->ID . '][' . $field . ']" id="attachments-' . $post->ID . '-' . $field . '" />';
                     
                            $values['html'] = $html;
                     
                            break;

                        case 'checkbox_group':
                            // Checkbox type doesn't exist either
                            $values['input'] = 'html';

                            // If options array is passed.
                            if ( isset( $values['options'] ) ) {
                                // Browse and add the options
                                $i = 0;

                                foreach ( $values['options'] as $k => $v ) {
                                    // Set each checkbox selected or not
                                    $string_array = explode('(' , rtrim($v, ')'));
                                    $current_ID = $string_array[1];

                                    if ( in_array( $current_ID, $meta ) ) {
                                        $checked = ' checked="checked"';
                                    } else {
                                        $checked = '';
                                    }
                                    $html .= '<input' . $checked . ' type="checkbox" name="attachments[' . $post->ID . '][' . $field . ']" id="attachments-' . $post->ID . '-' . $field . '_' . $i . '" />';
                                    $i++;
                                }
                            }
                            break;
                     
                        case 'radio':
                            // radio type doesn't exist either
                            $values['input'] = 'html';
                     
                            $html = '';
                     
                            if ( ! empty( $values['options'] ) ) {
                                $i = 0;
                     
                                foreach ( $values['options'] as $k => $v ) {
                                    if ( $meta == $k )
                                        $checked = ' checked="checked"';
                                    else
                                        $checked = '';
                     
                                    $html .= '<input' . $checked . ' value="' . $k . '" type="radio" name="attachments[' . $post->ID . '][' . $field . ']" id="' . sanitize_key( $field . '_' . $post->ID . '_' . $i ) . '" /> <label for="' . sanitize_key( $field . '_' . $post->ID . '_' . $i ) . '">' . $v . '</label><br />';
                                    $i++;
                                }
                            }
                     
                            $values['html'] = $html;
                     
                            break;
                    } // End switch ( $values['input'] ).
 
                    // And set it to the field before building it
                    $values['value'] = $meta;
 
                    // We add our field into the $form_fields array
                    $form_fields[$field] = $values;
                } // End if.
            } // End foreach.
        } // End if.
 
        // We return the completed $form_fields array
        return $form_fields;
    } // End function funbotic_apply_filter.
 
    function funbotic_save_fields( $post, $attachment ) {
        // If our fields array is not empty
        if ( ! empty( $this->media_fields ) ) {
            // Browse those fields
            foreach ( $this->media_fields as $field => $values ) {
                // If this field has been submitted (is present in the $attachment variable)
                if ( isset( $attachment[$field] ) ) {
                    // If submitted field is empty
                    // We add errors to the post object with the "error_text" parameter we set in the options
                    if ( strlen( trim( $attachment[$field] ) ) == 0 ) {
                        $post['errors'][$field]['errors'][] = __( $values['error_text'] );
                    // Otherwise we update the custom field
                    } else {
                        if ( $field === 'image_subject' ) {
                            // Use a regular expression to split the currently saved and newly entered values.
                            // Element [0] will be the name (not needed) and [1] will be the ID.

                            // We get the already saved field meta value
                            $current_camper_ID = get_post_meta( $post['ID'], '_' . $field, true );
                            // Now, get the value entered by the user, by taking the string and exploding it into an array.
                            $camper_index = $attachment[$field];
                            $camper_array = funbotic_generate_camper_data_array();
                            $camper_string = $camper_array[$camper_index];
                            $string_array = explode('(' , rtrim($camper_string, ')'));
                            $new_camper_ID = $string_array[1];

                            (int) $current_post_ID = $post['ID'];
 
                            if( $current_camper_ID === $new_camper_ID ) {
                                // Do nothing, as this image is already assigned to the current camper.
                            } elseif ( $current_camper_ID === '-' || $current_camper_ID === null ) {
                                // If the image is not currently assigned to a camper, then no metadata has to be removed, only added.
                                funbotic_add_custom_media_field_data( $new_camper_ID, $current_post_ID );
                            } elseif ( $new_camper_ID === '-' ) {
                                // If the image is being assigned to no new camper, then metadata only needs to be removed.
                                funbotic_remove_custom_media_field_data( $current_camper_ID, $current_post_ID );
                            } else {
                                // Otherwise, the metadata must be removed from the old camper and added to the new camper.
                                funbotic_add_custom_media_field_data( $new_camper_ID, $current_post_ID );
                                funbotic_remove_custom_media_field_data( $current_camper_ID, $current_post_ID );
                            }
                            update_post_meta( $post['ID'], '_image_subject', $new_camper_ID );
                        } else {
                            update_post_meta( $post['ID'], '_' . $field, $attachment[$field] );
                        }
                    } // End if/else.
                // Otherwise, we delete it if it already existed
                } else {
                    delete_post_meta( $post['ID'], $field );
                } // End if/else.
            } // End foreach ( $this->media_fields as $field => $values ).
        } // End if ( isset( $attachment[$field] ) ).
        return $post;
    } // End function funbotic_save_fields.
}

$fcmf_attachment_options = funbotic_generate_attachment_options();
$fcmf = new Funbotic_Custom_Media_Fields( $fcmf_attachment_options );

// Generates an array consisting of the display name of a camper and their ID, to be used as a list of options when
// selecting which camper is featured in a piece of media.  ID can be extracted from the string when needed.
function funbotic_generate_camper_data_array() {
	$args = array(
		'role' 		=> 'subscriber',
		'orderby' 	=> 'display_name',
		'order'		=> 'ASC',
	);

	$camper_data_array = get_users( $args );

	$return_array = array( 'None (-)', );

	foreach( $camper_data_array as $camper ) {
		$camper_ID = $camper->ID;
		$camper_display_name = $camper->display_name;
		$camper_string = $camper_display_name . " (" . $camper_ID . ")";
		array_push( $return_array, $camper_string );
	}
	return $return_array;
}

// Generates the list of options the uploader has when adding a new piece of media.
function funbotic_generate_attachment_options() {
	$camper_data_array = funbotic_generate_camper_data_array();

	$attachment_options = array(
		'image_subject' => array(
        	'label' 		=> 'Select all campers present in this image.  This image will then be displayed on that camper\'s profile page, so please make sure they are prominently visible.',
        	'input' 		=> 'checkbox_group',
        	'options' 		=> $camper_data_array,
        	'application' 	=> 'image',
        	'exclusions'   	=> array( 'audio', 'video' )
		),
		'video_subject' => array(
        	'label' 		=> 'Select all campers present in this video.  This video will then be displayed on that camper\'s profile page, so please make sure they are prominently visible.',
        	'input' 		=> 'checkbox_group',
        	'options' 		=> $camper_data_array,
        	'application' 	=> 'video',
        	'exclusions'   	=> array( 'audio', 'image' )
		),
	);
	return $attachment_options;
}

// Adds the post id for custom media field to a user's metadata.
function funbotic_add_custom_media_field_data( $user_id_in, $post_id_in) {
    if ( empty( get_user_meta( $user_id_in, 'funbotic_associated_images', false ) ) ) {
        (int) $id_to_push = $post_id_in;
        update_user_meta( $user_id_in, 'funbotic_associated_images', (int) $id_to_push );
    } else {
        $current_associated_images = get_user_meta( $user_id_in, 'funbotic_associated_images', false );
        (int) $id_to_push = $post_id_in;
        array_push( $current_associated_images, $id_to_push );
        // Sanitize and cast full array to ints.
        $int_array = array_map( 'intval', $current_associated_images );

        update_user_meta( $user_id_in, 'funbotic_associated_images', $int_array );
    } // End if/else.
}

// Removes an entry for custom media field in a user's metadata.
function funbotic_remove_custom_media_field_data( $user_id_in, $post_id_in) {
    $current_associated_images = get_user_meta( $user_id_in, 'funbotic_associated_images', false );
    $int_array = array_map( 'intval', $current_associated_images );
    // TEST
    update_user_meta( $user_id_in, '_test_current_associated_images', $int_array );

    /*
    // Sanitize and cast full array to ints.
    $int_array = array_map( 'intval', $current_associated_images );
    if ( ( $key = array_search( (int) $post_id_in, $int_array ) ) !== false ) {
        unset($int_array[$key]);
    }
    update_user_meta( $user_id_in, 'funbotic_associated_images', $int_array );
    */
}

// Add a function to ensure any time a new user is created, their metadata will be updated with the funbotic_associated_images field.
add_action( 'user_register', 'funbotic_create_custom_media_fields_user_meta' );

function funbotic_create_custom_media_fields_user_meta( $user_id ) {
	update_user_meta( $user_id, 'funbotic_associated_images', array() );
}