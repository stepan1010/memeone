<?php

	// This file is responsible for generating plugin's Settings Page.

	// Header
	$settings_form = '<div class="wrap"><h2>' . __( 'MemeOne Settings' ) . '</h2><hr />';

	// If this is a form submission, update options
	if(isset($_POST['memeone_save_changes']) && $_POST['memeone_save_changes'] == 'Y'){

		if(isset($_POST['memeone_top_text_font_size']) && is_numeric(trim($_POST['memeone_top_text_font_size']))){
			update_option('memeone_top_text_font_size', $_POST['memeone_top_text_font_size']);
		}

		if(isset($_POST['memeone_bottom_text_font_size']) && is_numeric(trim($_POST['memeone_bottom_text_font_size']))){
			update_option('memeone_bottom_text_font_size', $_POST['memeone_bottom_text_font_size']);
		}

		if(isset($_POST['memeone_thank_you_page'])) {
			update_option('memeone_thank_you_page', $_POST['memeone_thank_you_page']);
		}

		if(isset($_POST['memeone_font'])) {
			update_option('memeone_font', $_POST['memeone_font']);
		}

		update_option('memeone_turn_memes_to_wp_posts', $_POST['memeone_turn_memes_to_wp_posts']);
		update_option('memeone_display_meme_on_thank_you_page', $_POST['memeone_display_meme_on_thank_you_page']);
		

		if(isset($_POST['memeone_destination_folder'])) {
			$destination_folder = $_POST['memeone_destination_folder'][strlen($_POST['memeone_destination_folder'])-1] == '/' ? $_POST['memeone_destination_folder'] : $_POST['memeone_destination_folder'].'/';
			$destination_folder = $destination_folder[0] == '/' ? $destination_folder : '/'.$destination_folder;
			update_option('memeone_destination_folder', $destination_folder);
			update_option('memeone_destination_folder_url', $destination_folder);
		}

		// Say that settings are saved.
		$settings_form .= '<div class="updated"><p>Changes saved</p></div>';
	}

	// Generate settings form
	$settings_form .= '<form name="memeone_form" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';
	$settings_form .= '<p>Save memes to: ' . get_option('memeone_default_upload_path') . '<input type="text" style="width:400px;" name="memeone_destination_folder" value="'.get_option('memeone_destination_folder').'" /></p>';

	$settings_form .= '<p>Font (.ttf only): memeone/<input type="text" style="width:800px;" name="memeone_font" value="'.get_option('memeone_font').'" /></p>';
	
	if(!file_exists( plugin_dir_path( __FILE__ ) . get_option('memeone_font') )) {
		$settings_form .= '<font color=red>File ' . plugin_dir_path( __FILE__ ) . get_option('memeone_font') . ' not found.</font>';
	}else{
		$file_parts = pathinfo( plugin_dir_path( __FILE__ ) . get_option('memeone_font') );
		if ($file_parts['extension'] != 'ttf') {
			$settings_form .= '<font color=red>File ' . plugin_dir_path( __FILE__ ) . get_option('memeone_font') . ' is not a .ttf file.</font>';
		}
	}

	// Check if directory for saving memes exists
	if(!is_dir(get_option('memeone_default_upload_path') . get_option('memeone_destination_folder'))) {
		// Create it if it doesn't
		if (!mkdir(get_option('memeone_default_upload_path').get_option('memeone_destination_folder'))) {
			$settings_form .= '<font color=red>'.get_option('memeone_default_upload_path').get_option('memeone_destination_folder').' Can not be created. Please check your file permissions.</font>';
		}
	}
	// Form inputs
	$settings_form .= '<p>Top text default font size: <input type="text" name="memeone_top_text_font_size" value="'.get_option('memeone_top_text_font_size').'" /></p>';
	$settings_form .= '<p>Bottom text default font size: <input type="text" name="memeone_bottom_text_font_size" value="'.get_option('memeone_bottom_text_font_size').'"></p>';
	$settings_form .= '<p>"Thank you" page:</p> <textarea cols="70" rows="5" name="memeone_thank_you_page" id="memeone_thank_you_page" >' . str_replace("\\", "", get_option('memeone_thank_you_page')) . '</textarea>';

	// Assigning "selected" value to appropriate option in select input below
	$do_turn_to_wp_post_and_publish = get_option('memeone_turn_memes_to_wp_posts') == 2 ? 'selected' : '' ;
	$do_turn_to_wp_post = get_option('memeone_turn_memes_to_wp_posts') == 1 ? 'selected' : '' ;
	$dont_turn_to_wp_post = get_option('memeone_turn_memes_to_wp_posts') == 0 ? 'selected' : '' ;

	// Select input
	$settings_form .= '<p>What to do after meme has been created:</p><p><select name="memeone_turn_memes_to_wp_posts" id="memeone_turn_memes_to_wp_posts">';	
	$settings_form .= '<option value=2 ' . $do_turn_to_wp_post_and_publish . '>Create a WordPress post with meme as content. Post will be marked as "published".</option>';
	$settings_form .= '<option value=1 ' . $do_turn_to_wp_post . '>Create a WordPress post with meme as content. Post will be marked as "pending".</option>';
	$settings_form .= '<option value=0 ' . $dont_turn_to_wp_post . '>Nothing. Just save meme to disk.</option></select></p>';

	// Checkbox for whether or not to display memes on "Thank you" screen
	$display_memes_on_thank_you_page = get_option('memeone_display_meme_on_thank_you_page') == 1 ? 'checked' : '' ;

	$settings_form .= '<p>Display memes on "Thank you" page ';
	$settings_form .= '<input type="checkbox" name="memeone_display_meme_on_thank_you_page" value=1 ' . $display_memes_on_thank_you_page . ' /></p>';

	// "Save changes button"
	$settings_form .= '<input type="hidden" name="memeone_save_changes" value="Y" />';
	$settings_form .= '<input type="submit" value="Save Options" />';
	$settings_form .= '</form>';

	// Donate button
	$settings_form .= '<br /><p>Please donate to this plugin if you like it.';
	$settings_form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">';
	$settings_form .= '<input type="hidden" name="cmd" value="_s-xclick">';
	$settings_form .= '<input type="hidden" name="hosted_button_id" value="9YJ6YFV7EJ8PG">';
	$settings_form .= '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
	$settings_form .= '<img alt="" border="0" src="https://www.paypalobjects.com/ru_RU/i/scr/pixel.gif" width="1" height="1"></form></p>';

	// Pring settings form
	echo $settings_form;
?>
</div>