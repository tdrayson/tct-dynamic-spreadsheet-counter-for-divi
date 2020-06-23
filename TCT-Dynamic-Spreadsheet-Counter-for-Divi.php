<?php
/**
 * Plugin Name: TCT Dynamic Spreadsheet Counter for Divi
 * Description: Display google sheets data in a number counter
 * Version: 1.4
 * Author: Taylor Drayson
 * Author URI: https://www.thecreativetinker.com/
 **/

add_action( 'admin_menu', 'tct_ddc_add_admin_menu' );
add_action( 'admin_init', 'tct_ddc_settings_init' );

function tct_ddc_add_admin_menu() {
	add_options_page( 'TCT Google Sheets Divi Counter', 'Sheets Divi Counter', 'manage_options', 'tct_ddc', 'tct_ddc_options_page' );
}

function tct_ddc_settings_init() {

	register_setting( 'tct_ddc', 'tct_ddc_settings' );

	add_settings_section(
		'tct_ddc_section',
		__( 'Plugin Settings', 'tct_ddc' ),
		'tct_ddc_settings_section_callback',
		'tct_ddc'
	);

	add_settings_field(
		'tct_ddc_api_key',
		__( 'API Key', 'tct_ddc' ),
		'tct_ddc_api_key_render',
		'tct_ddc',
		'tct_ddc_section'
	);

	add_settings_field(
		'tct_ddc_gsheet',
		__( 'Spreadsheet ID', 'tct_ddc' ),
		'tct_ddc_gsheet_render',
		'tct_ddc',
		'tct_ddc_section'
	);

}

function tct_ddc_api_key_render() {

	$options = get_option( 'tct_ddc_settings' );
	?>
    <input type='text' name='tct_ddc_settings[tct_ddc_api_key]'
           value='<?php echo $options['tct_ddc_api_key']; ?>'>
	<?php

}

function tct_ddc_gsheet_render() {
	$options = get_option( 'tct_ddc_settings' );
	echo '<input type="text" name="tct_ddc_settings[tct_ddc_gsheet]" value="' . $options['tct_ddc_gsheet'] . '">';
}


function tct_ddc_settings_section_callback() {
	echo __( '', 'tct_ddc' );
}

function tct_ddc_options_page() {

	?>
    <form action='options.php' method='post'>

        <h2>TCT Google Sheets Divi Counter</h2>

		<?php
		settings_fields( 'tct_ddc' );
		do_settings_sections( 'tct_ddc' );
		submit_button();
		?>

        <h3>Shortcode - number counter</h3>
        <h4>[custom_number_counter title="[insert title]" location="[insert cell value]"]</h4>

        <h3>Shortcode - circle counter</h3>
        <h4>[custom_number_counter type="circle" title="[insert title]" location="[insert cell value]"]</h4>

				<br>

				<h3>Other additions to the shortcode</h3>
				<ul>
					<li>Change Background Layout Colour Mode (Default dark) = <strong>mode="light"</strong>
					<li>Change Title Text Colour = <strong>text_colour="[insert colour]"</strong>
					<li>Change Counter Number Colour = <strong>number_colour="[insert colour]"</strong>
					<li>Add Percent Symbol = <strong>percent="on"</strong>
				</ul>
    </form>
	<?php

}

// Adding shortcode function

//Add spreadsheet
function tct_ddc_sheet_value_shortcode( $location ) {
	$options   = get_option( 'tct_ddc_settings' );
	$API       = $options['tct_ddc_api_key'];
	$gsheet_id = $options['tct_ddc_gsheet'];
	$api_key   = esc_attr( $API );

	if ( ! $cell_value = get_transient( 'tct_ddc_sheet_data_' . $location ) ) {
		$get_cell = new WP_Http();
		$cell_url = 'https://sheets.googleapis.com/v4/spreadsheets/' . $gsheet_id . '/values/' . $location . '?&key=' . $api_key;

		$cell_response = $get_cell->get( $cell_url );
		$json_body     = json_decode( $cell_response['body'], true );
		$cell_value    = $json_body['values'][0][0];

		set_transient( 'tct_ddc_sheet_data_' . $location, $cell_value, 3600 ); //86400 = 1 day, 3600 = 1 hour
	}

	return $cell_value;
}

//Add number module
function tct_ddc_add_counter_shortcode( $atts ) {
	$et_shortcode = 'et_pb_number_counter';
	$percent = 'off';
	$mode='dark';

	if ( isset( $atts['type'] ) ) {
		if ( $atts['type'] == 'circle' ) {
			$et_shortcode = 'et_pb_circle_counter';
		}
	}
	if ( isset( $atts['mode'] ) ) {
		if ( $atts['mode'] == 'light' ) {
			$mode = 'light';
		}
	}
	if ( isset( $atts['percent'] ) ) {
		if ( $atts['percent'] == 'on' ) {
			$percent = 'on';
		}
	}


	$sheet_val = tct_ddc_sheet_value_shortcode( $atts['location'] );
	$title     = $atts['title'];
	$title_text_colour= $atts['text_colour'];
	$counter_colour=$atts['number_colour'];

	$et_counter = '[' . $et_shortcode . ' admin_label="Number Counter" title="' . $title . '" number="' . $sheet_val . '" background_layout="' . $mode . '" percent_sign="' . $percent . '" counter_color="' . $counter_colour .'" title_text_color="' . $title_text_colour . '" disabled="off"] [/' . $et_shortcode . ']';

	return do_shortcode( $et_counter );
}

add_shortcode( 'custom_number_counter', 'tct_ddc_add_counter_shortcode' );

?>
