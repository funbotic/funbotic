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
 */

add_action('init', 'funbotic_user_gallery_init');


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

            $content .= rtrim($string, ',');
            $content .= '"]';

            return '<span class="dynamic_user_gallery">' . $content . '</span>';
        } // End if/else.
    } // End function funbotic_generate_dynamic_user_gallery.
    add_shortcode( 'funbotic_user_gallery', 'funbotic_generate_dynamic_user_gallery' );
} // End function funbotic_user_gallery_init.