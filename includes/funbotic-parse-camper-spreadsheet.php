<?php
/**
 * Fired from: funbotic-admin-display.php
 * Fired when uploading .csv file via Funbotic options, for camper/parent account creation.
 * Modified from: https://www.htmlgoodies.com/beyond/cms/create-a-file-uploader-in-wordpress.html
 *
 * @link       https://www.funbotic.com
 * @since      1.1.6
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

//provides access to WP environment
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
 
/* import
you get the following information for each file:
$_FILES['field_name']['name']
$_FILES['field_name']['size']
$_FILES['field_name']['type']
$_FILES['field_name']['tmp_name']
*/

if ( $_FILES['upload']['name'] ) {
    if ( ! $_FILES['upload']['error'] ) {
        // Sanitize the file name.
        $new_file_name = sanitize_title( $_FILES['upload']['name'] );

        // Needs to be of .csv format.
        $filetype = wp_check_filetype( $_FILES['upload']['name'] );
        if ( $filetype['ext'] != 'csv' ) {
            wp_die( 'Please upload a .csv file.' );
        }

        // Can't be larger than 10 MB.
        if ( $_FILES['upload']['size'] > (10000000) ) {
            // wp_die generates a visually appealing message element.
            wp_die( 'Your file size is too large.  Please limit your csv to 10MB or less.');
        // File needs to exist, duh.  This check should never be reached.
        } else if ( $_FILES['upload']['tmp_name'] == '' ){
            wp_die( 'Please choose a .csv file to upload.' );
        // The file has been validated, we can proceed with processing.
        } else {

            $row = 1;
            $csv_data = '';
            // Open file for reading only.
            if ( ($handle = fopen( $_FILES['upload']['tmp_name'], "r" ) ) !== FALSE) {
                while ( ( $data = fgetcsv( $handle, 1000, "," ) ) !== FALSE) {
                    $num = count( $data );
                    $csv_data .= $num . ' fields in line ' . $row . "\n\n";
                    $row++;

                    for ( $c = 0; $c < $num; $c++ ) {
                        $csv_data .= $data[$c] . " | ";
                    }
                    $csv_data .= "\n";
                }
                fclose( $handle );
            }
            wp_die ( "Processed .csv data:\n\n" . $csv_data );

            /*
            //These files need to be included as dependencies when on the front end.
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
     
            // Let WordPress handle the upload.
            // Remember, 'upload' is the name of our file input in our form above.
            $file_id = media_handle_upload( 'upload', 0 );

            if ( is_wp_error( $file_id ) ) {
                wp_die( 'Error loading file!' );
            } else {
                wp_die( 'Your menu was successfully imported.' );
            }
            */
        }
    } else {
        //set that to be the returned message
        wp_die( 'Error: ' . $_FILES['upload']['error'] );
    }
} 