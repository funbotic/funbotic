<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.funbotic.com
 * @since      1.0.0
 *
 * @package    Funbotic
 * @subpackage Funbotic/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <h1>Camper Enrollment Upload Form</h1>
        <p>Upload a .csv file from a CampMinder report, for the current/upcoming session of campers.  Column headings should be:</p>
        <p>First Name | Last Name | F1P1FirstName | F1P1LastName | F1P1Login/Email | F1P2FirstName | F1P2LastName | F1P2Login/Email | F2P1FirstName | F2P1LastName | F2P1Login/Email | F2P2FirstName | F2P2LastName | F2P2Login/Email</p>
    <form id="enrollment_upload_form" action="/wp-content/plugins/funbotic/includes/funbotic-parse-camper-spreadsheet.php" enctype="multipart/form-data" method="post" target="messages">
        <p><input name="upload" id="upload" type="file"/></p>
        <p><input id="btnSubmit" type="submit" value="Parse Selected Spreadsheet" /></p>
        <iframe name="messages" id="messages" width="1100" height="500"></iframe>
        <p><button id="reset_upload_form" type="reset" value="Reset">Reset</button></p>
    </form>

</div>