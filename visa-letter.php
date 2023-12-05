<?php
/*
Plugin Name: Visa Letter
Plugin URI: https://github.com/vachan/visa-letter
Description: This plugin adds a settings page to the admin menu and creates a shortcode for visa letter.
Version: 1.0.0
Author: Vachan Kudmule
Author URI: https://dezine.ninja/
Text Domain: wcorg-visa-letter
License: GPLv2 or later
*/

// Add a menu item in the admin menu
function visa_letter_add_menu_item() {
    add_menu_page(
        'Visa Letter Settings',
        'Visa Letter',
        'manage_options',
        'visa-letter-settings',
        'visa_letter_render_settings_page',
        'dashicons-media-document',
        80
    );
}
add_action('admin_menu', 'visa_letter_add_menu_item');

// Render the settings page
function visa_letter_render_settings_page() {
    ?>
    <div class="visa_letter_settings_form">
        <form method="post" action="options.php">
            <?php
            settings_fields('visa_letter_settings');
            do_settings_sections('visa-letter-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings and fields
function visa_letter_register_settings() {
    // Register a settings group
    register_setting('visa_letter_settings', 'visa_letter_options');

    // Add fields to the settings group
    add_settings_section(
        'visa_letter_section',
        'WordCamp Information',
        'visa_letter_section_callback',
        'visa-letter-settings'
    );

    add_settings_field(
        'wordcamp_name',
        'WordCamp Name',
        'visa_letter_field_callback',
        'visa-letter-settings',
        'visa_letter_section',
        ['field_type' => 'text', 'field_name' => 'wordcamp_name']
    );

    add_settings_field(
        'wordcamp_location',
        'WordCamp Location',
        'visa_letter_field_callback',
        'visa-letter-settings',
        'visa_letter_section',
        ['field_type' => 'text', 'field_name' => 'wordcamp_location']
    );

    add_settings_field(
        'wordcamp_date_start',
        'WordCamp Date Start',
        'visa_letter_field_callback',
        'visa-letter-settings',
        'visa_letter_section',
        ['field_type' => 'date', 'field_name' => 'wordcamp_date_start']
    );

    add_settings_field(
        'wordcamp_date_end',
        'WordCamp Date End',
        'visa_letter_field_callback',
        'visa-letter-settings',
        'visa_letter_section',
        ['field_type' => 'date', 'field_name' => 'wordcamp_date_end']
    );

    add_settings_field(
        'organizer_name',
        'Organizer Name',
        'visa_letter_field_callback',
        'visa-letter-settings',
        'visa_letter_section',
        ['field_type' => 'text', 'field_name' => 'organizer_name']
    );

    add_settings_field(
        'organizer_contacts',
        'Organizer Contacts',
        'visa_letter_field_callback',
        'visa-letter-settings',
        'visa_letter_section',
        ['field_type' => 'textarea', 'field_name' => 'organizer_contacts']
    );

}
add_action('admin_init', 'visa_letter_register_settings');

// Render section callback
function visa_letter_section_callback() {
    echo '<p>Enter event information below:</p>';
}

// Render field callback
function visa_letter_field_callback( $args ) {
    $data = get_option( 'visa_letter_options' );
    $value = isset( $data[$args['field_name']] ) ? $data[$args['field_name']] : '';
    $date_min = ( 'date' == $args['field_type'] ) ? ' min="'. date( 'Y-m-d', time() ) . '"' : ''; 
    if( 'textarea' == esc_attr( $args['field_type'] ) ) {
        echo '<textarea name="visa_letter_options[' . esc_attr($args['field_name']) . ']">' . esc_textarea( $value ) . '</textarea>';
    } else {
        echo '<input type="' . esc_attr( $args['field_type'] ) . '" name="visa_letter_options[' . esc_attr( $args['field_name'] ) . ']" value="' . esc_attr( $value ) . '"' . $date_min .' />';
    }
}

// Shortcode callback
function visa_letter_shortcode( $atts ) {
    $atts = shortcode_atts(array(
        // Add attributes if needed
    ), $atts);
 
    ob_start();
    // Output the visa letter content
    if ( isset( $_POST['visa_letter_nonce'] ) && wp_verify_nonce( $_POST['visa_letter_nonce'], 'visa_letter' ) ) {

        $data = get_option('visa_letter_options');
        $email_address = isset( $_POST['attendee_email'] ) ? sanitize_email( wp_unslash( $_POST['attendee_email'] ) ) : '';
        $first_name = isset( $_POST['attendee_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['attendee_first_name'] ) ) : '';
        $last_name = isset( $_POST['attendee_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['attendee_last_name'] ) ) : '';
        $full_name = $first_name . ' ' . $last_name;
        $country_of_residency = isset( $_POST['country_of_residency'] ) ? sanitize_text_field( wp_unslash( $_POST['country_of_residency'] ) ) : '';
        $passport_number = isset( $_POST['passport_number'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_number'] ) ) : '';
        $passport_date_end = isset( $_POST['passport_date_end'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_date_end'] ) ) : '';

        require_once( plugin_dir_path( __FILE__ ) . 'libs/fpdf/fpdf.php' );

        $pdf = new FPDF();
        $pdf->AddPage( 'P', 'A4', 0 );
        $pdf->SetFont( 'Arial', '', 12 );
        $pdf->Image( plugin_dir_path( __FILE__ ) . '/assets/img/wpcs-logo.png', null, null, 80, 0, 'PNG' );
        $pdf->Ln( 10 );
        $pdf->Cell( 190, 6, date( 'Y-m-d' ) );
        $pdf->Ln( 15 );
        $pdf->MultiCell( 190, 6, 'To Whom It May Concern', 0, 'L' );
        $pdf->Ln( 5 );
        $pdf->MultiCell( 190, 6, 'This letter issued states that ' . $full_name . ' from ' . $country_of_residency . ' with the passport number ' . $passport_number . ', requested a visa invitation letter, and confirmed that they purchased a ticket to attend ' . esc_html( $data['wordcamp_name'] ) . ', a community-organised event focusing on WordPress development and technology.', 0, 'L' );
        $pdf->Ln( 5 );
        $pdf->MultiCell( 190, 6, 'WordPress is a web software you can use to create a beautiful website or blog. The core software is built by hundreds of community volunteers. The mission of the WordPress open-source project is to democratize publishing through Open Source, GPL software.', 0, 'L' );
        $pdf->Ln( 5 );
        $pdf->MultiCell( 190, 6, 'Attending ' . esc_html( $data['wordcamp_name'] ) . ' will require ' . $full_name . ' to be in ' . esc_html( $data['wordcamp_location'] ) . ' from ' . esc_html( $data['wordcamp_date_start'] ) .' through ' . esc_html( $data['wordcamp_date_end'] ) . '.', 0, 'L' );
        $pdf->Ln( 5 );
        $pdf->MultiCell( 190, 6, 'I would be happy to provide any further information you may require.', 0, 'L' );
        $pdf->Ln( 5 );
        $pdf->MultiCell( 190, 6, 'Sincerely,', 0, 'L' );
        $pdf->Ln( 5 );
        $pdf->MultiCell( 190, 6, esc_html( $data['organizer_name'] ), 0, 'L' );
        $pdf->MultiCell( 190, 6, 'Organiser', 0, 'L' );
        $pdf->MultiCell( 190, 6, esc_html( $data['wordcamp_name'] ), 0, 'L' );
        $pdf->Ln( 5 );
        $pdf->MultiCell( 190, 6, esc_html( $data['organizer_contacts'] ), 0, 'L' );
        $pdf->Ln( 20 );
        $pdf->MultiCell( 190, 6, 'PLEASE NOTE: This visa invitation letter is only valid in combination with a ticket to ' . esc_html( $data['wordcamp_name'] ) . '.', 0, 'L' );
        $pdf->Output( 'D', str_replace( ' ', '-', sanitize_text_field( esc_html( $data['wordcamp_name'] ) ) . '_Visa-Letter_' . $full_name ) . '.pdf', TRUE );
        exit();
        
    } else {
    ?>
    <style>
        .visa-letter-form label {
            display: block;
            float: none;
            font-weight: 700;
            margin-bottom: 0.25em;
        }
        .visa-letter-form label em {
            font-weight: normal;
            font-size: 0.85em;
        }
        .visa-letter-form input, 
        .visa-letter-form button {
            border: 1px solid #8c8f94;
            border-radius: 0;
            box-sizing: border-box;
            font: inherit;
            padding: 0.50em;
            width: 50%;
            margin-bottom: 1.25em;
        } 
        .visa-letter-form input[type=checkbox], 
        .visa-letter-form .form-check-label {
            display: inline-block;
            width: auto;
        }
        .visa-letter-form button {
            cursor: pointer;
        }
    </style>
    <div id="form" class="visa-letter">
        <form id="visa-form" name="visa-form" method="post" action="<?php the_permalink(); ?>" class="visa-letter-form">
            <!--
            <div class="grunion-field-email-wrap grunion-field-wrap">
                <label for="attendee_email" class="grunion-field-label email"><?php _e( 'Email Address:', 'wcorg-visa-letter' ); ?> <span></span> <em><?php _e( 'Used to purchase ticket', 'wcorg-visa-letter' ); ?></em></label>
                <input type="email" name="attendee_email" class="email  grunion-field" aria-required="false" />
            </div>
            -->
            <div class="grunion-field-name-wrap grunion-field-wrap">
                <label for="attendee_first_name" class="grunion-field-label name"><?php _e( 'First Name:', 'wcorg-visa-letter' ); ?> <span>*</span> <em><?php _e( 'As mentioned on passport', 'wcorg-visa-letter' ); ?></em></label>
			    <input value="Vachan" type="text" name="attendee_first_name" class="text  grunion-field" required aria-required="true" />
            </div>

            <div class="grunion-field-name-wrap grunion-field-wrap">
			    <label for="attendee_last_name" class="grunion-field-label name"><?php _e( 'Last Name:', 'wcorg-visa-letter' ); ?> <span>*</span> <em><?php _e( 'As mentioned on passport', 'wcorg-visa-letter' ); ?></em></label>
			    <input value="Kudmule" type="text" name="attendee_last_name" class="text  grunion-field" required aria-required="true" />
            </div>

            <div class="grunion-field-text-wrap grunion-field-wrap">
			    <label for="country_of_residency" class="grunion-field-label text"><?php _e( 'Password Issuing Country:', 'wcorg-visa-letter' ); ?> <span>*</span></label>
			    <input value="India" type="text" name="country_of_residency" class="text  grunion-field" required aria-required="true" />
            </div>

            <div class="grunion-field-text-wrap grunion-field-wrap">
			    <label for="passport_number" class="grunion-field-label text"><?php _e( 'Passport Number:', 'wcorg-visa-letter' ); ?> <span>*</span></label>
			    <input value="PASS1234" type="text" name="passport_number" class="text  grunion-field" required aria-required="true" />
            </div>

            <div class="grunion-field-date-wrap grunion-field-wrap">
                <label for="passport_date_end" class="grunion-field-label date"><?php _e( 'Passport Date of Exipry:', 'wcorg-visa-letter' ); ?></label>
                <input type="date" name="passport_date_end" min="<?php echo esc_attr( date( 'Y-m-d', strtotime( "+6 months", strtotime( get_option('visa_letter_options')['wordcamp_date_end'] ) ) ) ); ?>" class="date grunion-field hasDatepicker" />
            </div>

            <div class="form-check">
				<input type="checkbox" class="form-check-input" id="validTicket" required="" oninvalid="this.setCustomValidity('You cannot request an invitation letter without a valid event ticket.')" oninput="this.setCustomValidity('')" required aria-required="true" />
				<label class="form-check-label" for="validTicket"><small>I confirm that I have a valid event ticket.</small></label>
			</div>

            <!-- Nonce Field for Security -->
            <?php wp_nonce_field('visa_letter', 'visa_letter_nonce'); ?>

            <button type="submit" name="visa_letter_submit" class="button btn pushbutton-wide">Generate Visa Letter</button>

            <div class="notice warning notice-warning">
                <p><strong>Please note that the invitation letter is only valid in combination with a valid event ticket.</strong></p>
                <p>Providing the invitation letter to the authorities without holding a valid event ticket can be considered forgery of documents.</p>
            </div>
        </form>
    </div>
    <?php
    }

    return ob_get_clean(); // Return the shortcode output

}
add_shortcode('visa-letter', 'visa_letter_shortcode');