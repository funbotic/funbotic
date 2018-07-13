<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.funbotic.com
 * @since      1.0.1
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

/**
 * Generate shortcode to allow display of a dynamic gallery populated only with images
 * expressly associated with the current user.  Added as part of an init action due to
 * plugins being loaded very early.
 * 
 * Generates shortcode to display LearnDash content as well as dynamic galleries for each
 * child of a parent user.
 */

add_action('init', 'funbotic_user_gallery_init');
add_action('init', 'funbotic_parent_display_init');


function funbotic_user_gallery_init() {
    function funbotic_generate_dynamic_user_gallery( $content = null ) {
        $id = get_current_user_id();
        $current_meta = get_user_meta( $id, 'funbotic_associated_images', false );
        // Just to be safe, we will clean the array to ensure it is in a single dimension.
        $current_associated_images = funbotic_clean_array( $current_meta );

        if ( empty( $current_associated_images ) || is_null( $current_associated_images ) ) {

            return 'No pictures yet!';

        } else {

            $content = '[gallery ids="';

            foreach ( $current_associated_images as $image_id ) {
                $content .= $image_id . ',';
            }

            $content = rtrim($content, ',');
            $content .= '"]';

            return '<span class="dynamic_user_gallery">' . $content . '</span>';
        } // End if/else.
    } // End function funbotic_generate_dynamic_user_gallery.
    add_shortcode( 'funbotic_user_gallery', 'funbotic_generate_dynamic_user_gallery' );
} // End function funbotic_user_gallery_init.


function funbotic_parent_display_init() {
    function funbotic_generate_parent_display( $content = null ) {
        $id = get_current_user_id();
        
        $current_children = get_the_author_meta( 'funbotic_children', $id );

        if ( empty( $current_children ) || is_null( $current_children ) ) {

            $content = 'According to our records, you do not have any children registered for Fun Bot Lab camp at this time.  If this is an error, please contact us at 703-831-7747.';

        } else {

            $content = '';
            $counter = 1;

            foreach ( $current_children as $child ) {

                $child_name = get_the_author_meta( 'display_name', $child );

                $content .= '<div><h5>Begin Child ' . $counter . '</h5></div>';

                $content .= '<div><h3>' . $child_name . '</h3></div></br></br>';

                $current_meta = get_the_author_meta( 'funbotic_associated_images', $child );
                // Just to be safe, we will clean the array to ensure it is in a single dimension.
                if ( empty( $current_meta ) || is_null( $current_meta ) ) {
                    $current_associated_images = array();
                } else {
                    $current_associated_images = funbotic_clean_array( $current_meta );
                }

                if ( empty( $current_associated_images ) || is_null( $current_associated_images ) ) {
                    
                    $content .= '<div>No pictures yet!</div></br>';

                } else {

                    $content .= '<div><span class="dynamic_user_gallery">[gallery ids="';

                    foreach ( $current_associated_images as $image_id ) {
                        $content .= $image_id . ',';
                    }

                    $content = rtrim($content, ',');
                    $content .= '"]</span></div></br>';

                    $content .= '<div><h5>End Child ' . $counter . '</h5></div>';
                    $counter++;
                } // End if/else check for associated images.

            } // End foreach ( $current_children as $child ).

        } // End if/else check for children.

        return '<span class="parent_display">' . $content . '</span>';
    } // End function funbotic_generate_parent_display.
    add_shortcode( 'funbotic_parent_display', 'funbotic_generate_parent_display' );
} // End function funbotic_parent_display_init.