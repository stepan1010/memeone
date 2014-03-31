<?php
/*
Plugin Name: MemeOne Generator
Plugin URI: http://stepasyuk.com/memeone/
Description: MemeOne is a plugin for creating memes online.
Version: 2.0.0
Author: Stepan Stepasyuk
Author URI: http://stepasyuk.com
License: GPLv2
*/

/*
* This is the main file of Motivation Generator plugin for WordPress.
* The plugin is run via a shortcode [memeone_plugin] which triggers form generator function.
* Once the form is generated it's all javascript from there. All the image processing is handled by memeone.js
* After the meme is done it is submitted to the server.
* meme processing (like writing it to disk) and saving its details to database is handeled by this file. 
*/

/* This hook triggers before any content has been loaded to the page in order to check if 
* the plugin is up to date. */ 
add_action('init', 'memeone_check_version');
function memeone_check_version()
{
	if(!get_option('memeone_version') || get_option('memeone_version') < 200){

		global $wpdb;
		$table_name = $wpdb->prefix . "memeone";
		$wpdb->query("ALTER TABLE $table_name ADD meme_wp_post_id int(4) NOT NULL");

		delete_option('memeone_image_width_limit');
		delete_option('memeone_image_height_limit');
		delete_option('memeone_memes_per_page');				
		delete_option('memeone_font_size');

		update_option('memeone_top_text_font_size', 60);
		update_option('memeone_bottom_text_font_size', 60);
		update_option('memeone_turn_memes_to_wp_posts', 1);
		update_option('memeone_thank_you_page',"<p><h2>Thank you!</h2></p>");
		update_option('memeone_version', 200);
	}
}

// This hook is triggered when the user deletes the plugin 
register_uninstall_hook(__FILE__, "memeone_uninstall");
function memeone_uninstall()
{
 	// Delete all memes first
 	memeone_delete_all_memes();

 	// Delete plugin's database table
 	global $wpdb;
    $table_name = $wpdb->prefix . "memeone";
	$wpdb->query("DROP TABLE IF EXISTS $table_name");

	// Delete all stored options
	delete_option('memeone_top_text_font_size');
	delete_option('memeone_botton_text_font_size');
	delete_option('memeone_version');
	delete_option('memeone_font');
	delete_option('memeone_destination_folder');
	delete_option('memeone_default_upload_url');
	delete_option('memeone_default_upload_path');
	delete_option('memeone_turn_memes_to_wp_posts');
	delete_option('memeone_top_text_font_size');
	delete_option('memeone_bottom_text_font_size');
	delete_option('memeone_thank_you_page');
}

// This hook is triggered when the plugin is activated.
register_activation_hook(__FILE__, "memeone_activate");
function memeone_activate()
{
	// Create a table for our plugin to store info about created memes
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";
	$sql = "CREATE TABLE $table_name (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
	  creation_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  meme_file_name varchar(255) NOT NULL,
	  meme_url varchar(255) NOT NULL,
	  path_to_meme varchar(255) NOT NULL,
	  top_line varchar(25) NOT NULL,
	  bottom_line varchar(48) NOT NULL,
	  author varchar(55) DEFAULT '' NOT NULL,
	  meme_wp_post_id int(4) NOT NULL
	  UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	// Default main text font size
	if(!get_option('memeone_top_text_font_size')){
		update_option('memeone_top_text_font_size', 60); 
	}

	// Default sub text font size
	if(!get_option('memeone_botton_text_font_size')){
		update_option('memeone_botton_text_font_size', 60); 
	}

	$upload_dir = wp_upload_dir();

	// Default path to where to upload memes
	if(!get_option('memeone_default_upload_path') || get_option('memeone_default_upload_path') == ''){
		update_option('memeone_default_upload_path', $upload_dir['basedir']);
	}

	// Same as above but as URL (needed for links)
	if(!get_option('memeone_default_upload_url') || get_option('memeone_default_upload_url') == ''){
		update_option('memeone_default_upload_url', $upload_dir['baseurl']);
	}
	
	// Default folder to upload memes to
	if(!get_option('memeone_destination_folder')){
		update_option('memeone_destination_folder', '/memeone-memes/');
	}

	/* Option to determine what to do with memes after they've been created. Possibe values:
	* 0 - Do nothing, just save meme to disk.
	* 1 - Create WordPress posts with memes as content.
	* 2 - Save meme to disk and display it on "Thank you screen".
	*/
	if(!get_option('memeone_turn_memes_to_wp_posts') || get_option('memeone_turn_memes_to_wp_posts') == ''){
		update_option('memeone_turn_memes_to_wp_posts', 1);
	}

	// Default content of "Thank you" page
	if(!get_option('memeone_thank_you_page')){
		update_option('memeone_thank_you_page',"<p><h2>Thank you!</h2></p>\r\n<p>Your meme will be published shortly</p>");
	}

	update_option('memeone_version', 200);
}

// Register plugin's css
add_action('wp_enqueue_scripts', 'register_memeone_styles');
function register_memeone_styles()
{
	wp_register_style('memeone_style', plugins_url( 'css/memeone-style.css', __FILE__ ));
	wp_enqueue_style('memeone_style');
}

// Register plugin's css for Settings page
add_action( 'admin_enqueue_scripts', 'memeone_admin_style' ); 
function memeone_admin_style($hook) // Link our already registered script only to settings page of our plugin
{ 	
	if( 'toplevel_page_memeone' == $hook || 'memeone_page_memeone-memes' == $hook){
     	wp_register_style( 'memeone_admin_style', plugins_url( 'css/memeone-style-admin.css', __FILE__ ) );
    	wp_enqueue_style( 'memeone_admin_style' );
    }else{
    	return;
   }
}

// Shortcode handler
add_shortcode('memeone_plugin', 'memeone_load_plugin'); 
function memeone_load_plugin()
{	
	// Check if this is a submission or not
	if(isset($_POST['memeone_created_meme'])){
		
		// If it is, validate the input first
		$meme = memeone_validate_input($_POST);

		// Then save the meme
		$meme_id = memeone_save_new_meme($meme);

		// And redirect to Thank you page
		memeone_redirect_to_thank_you_page($meme_id);

		return;
	}

	// If this is a redirect to Thank you page then return the Thank you page
	if (isset($_GET['thankyou'])) {
		return memeone_thank_you_page($_GET['meme']);
	} else {
		// Display the generator form if it's neither
		return memeone_generator();
	}
}

// Create generator form
function memeone_generator()
{
	$generator = '<div id="memeone-plugin" class="widget">';
	
	// File input
    $generator .= '<p> Please select a picture you would like to turn into a meme and click "Upload". <input type="file" id="memeone_imgfile" />';
    // Upload button and Loading icon
    $generator .= '<input type="button" id="memeone_loadimgfile" value="Upload" onclick="memeone_load_image();" /><div id="memeone_loading_icon_placeholder"><img id="memeone_loading_icon" src="' . plugins_url( 'images/loader.gif' , __FILE__ ) . '"></div></p>';
	
	$generator .= '<div id="memeone_meme_placeholder"></div>';
	$generator .= '<div id="memeone_canvas_placeholder"><p><canvas id="memeone_canvas"></canvas></p></div>';
	$generator .= '<div id="memeone_error_message_area"></div>';

	// Input form (for top_text and botton_text)
	$generator .= '<form id="memeone_generator_form" name="memeone_generator_form" accept-charset="UTF-8" enctype="multipart/form-data" action='.$_SERVER['REQUEST_URI'].' method="POST">';
	$generator .= '<div id="memeone_form_wrapper"><p>Enter main text:* <input type="text" id="memeone_meme_top_text" name="memeone_meme_top_text" tabindex=2 required="required" onkeyup="memeone_type_text();">';
	$generator .= ' Font size: <input type="text" id="memeone_top_text_font_size" size=3 value="'. get_option('memeone_top_text_font_size') .'" tabindex=4 onkeyup="memeone_load_image();">&nbsp px</p>';
	$generator .= '<p>Enter sub text: &nbsp&nbsp<input type="text" id="memeone_meme_bottom_text" name="memeone_meme_bottom_text" tabindex=3 onkeyup="memeone_type_text();">';
	$generator .= ' Font size: <input type="text" id="memeone_bottom_text_font_size" size=3 value="'. get_option('memeone_botton_text_font_size') .'" tabindex=5 onkeyup="memeone_load_image();">&nbsp px</p></div>';
    
    // Hidden input for created meme (For more info see memeone.js) 
    $generator .= '<input type="hidden" id="memeone_created_meme" name="memeone_created_meme" value="">';

    // Submit button
    $generator .= '<input type="button" id="memeone_submit" tabindex=6 onclick="memeone_submit_meme()" value="Create"/>';
    $generator .= '</form>';
    
    // Loading our memeone.js which is responsible for all the image processing
    $generator .= '<script type="text/javascript" src="'.plugins_url( 'memeone/js/memeone-generator.js').'"></script>';
    $generator .= '</div>';

	return $generator;
}

// Generate Thank you page ($meme_id is needed in case the meme should be displayed on Thank you page)
function memeone_thank_you_page($meme_id)
{
	// Get Thank you page content
	$page_content = str_replace("\\", "", get_option('memeone_thank_you_page'));

	// Display meme if it's needed.
	if (get_option('memeone_turn_memes_to_wp_posts') == 2){

		$page_content .= memeone_display_meme($meme_id);
	}
	
	return $page_content;
}

// Function for displaying the meme
function memeone_display_meme($meme_id)
{	
	// Just in case
	$meme_id = mysql_real_escape_string($meme_id);

	// Get info about meme
	$meme = memeone_get_meme_by_id($meme_id);

	// Generate html which will be embedded into Thank you page
	$meme_html = '<div><img class="memeone_meme" src="' . $meme->meme_url . $meme->meme_file_name . '.jpg" /></div>';

	return $meme_html;
}

// Function for validating input before writing it to database when user submits a meme
function memeone_validate_input($POST)
{
	// Array to hold info about the meme
	$meme = array();

	// Decode image
	$encoded_image = substr_replace($POST['memeone_created_meme'], '', 0, strlen('data:image/jpeg;base64,'));
	$meme[0] = imagecreatefromstring(base64_decode($encoded_image)) or die ('Error processing image. Please try again.');

	// Check if there is a top_text
	if (strlen(trim($POST['memeone_meme_top_text'])) == 0 ){
		die("Please type in the top_text.");
	}

	$meme[1] = mysql_real_escape_string($POST['memeone_meme_top_text']);
	$meme[2] = mysql_real_escape_string($POST['memeone_meme_bottom_text']);

	return $meme;
}

// This function is responsible for saving new meme to disk and to database
function memeone_save_new_meme($meme)
{
	// Get path where to save the meme
	$destination_folder = get_option('memeone_default_upload_path') . get_option('memeone_destination_folder');

	// Generate memes' file name
	$meme_filename = memeone_generate_meme_filename($destination_folder);

	// Write meme to disk
	imagejpeg($meme[0], $destination_folder . $meme_filename . '.jpg', 100) or die ('Error creating meme. Please try again.');

	// Write meme's info to db
	$meme_id = memeone_write_meme_to_db($meme, $meme_filename);

	// Create a WordPress post if needed
	if (get_option('memeone_turn_memes_to_wp_posts') == 1){
		memeone_turn_meme_to_wp_post($meme_id);
	}

	return $meme_id;
}

// This function generates a unique filename for our meme
function memeone_generate_meme_filename($destination_folder)
{	
	// Generate the name
	$meme_filename = uniqid();

	// Check if directory exists
	if (!file_exists($destination_folder)) {
    	mkdir($destination_folder, 0777, true);
	}

	// If (by chance) there is already a file with such name modify the name of our file to avoid collisions
	if (file_exists($destination_folder . $meme_filename.".jpg")){
		$file_exists = true;
		$counter = 0;
		while($file_exists){
			if(!file_exists($destination_folder . $meme_filename . "_" . $counter. ".jpg")){
				$meme_filename = $meme_filename . "_" . $counter;
				$file_exists = false; 
			} else {
				$counter++;
			}
		}
	}

	return $meme_filename;
}

// Add a record about meme to the db
function memeone_write_meme_to_db($meme, $meme_filename)
{
	$current_user = wp_get_current_user();

	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";
	$wpdb->insert($table_name, array( 
		'creation_date' => current_time('mysql'),
		'meme_file_name' => mysql_real_escape_string($meme_filename),
		'meme_url' => mysql_real_escape_string(get_option('memeone_default_upload_url') . get_option('memeone_destination_folder')),
		'path_to_meme' => mysql_real_escape_string(get_option('memeone_default_upload_path') . get_option('memeone_destination_folder')), 
		'top_line' => mysql_real_escape_string($meme[1]),
		'bottom_line' => mysql_real_escape_string($meme[2]),
		'author' => mysql_real_escape_string($current_user->user_login),
		'meme_wp_post_id' => 0
	));

	return $wpdb->insert_id;
}

// Function to create a WordPress post with a given meme as content
function memeone_turn_meme_to_wp_post($meme_id)
{
	// Get info about a given meme
	$meme = memeone_get_meme_by_id($meme_id);

	// Do nothing if there is no such meme
	if(empty($meme))
	{
		return;
	}

	$new_wp_post= array(
	  'post_title'     => str_replace("\\", "", $meme->top_line),
	  'post_content'   => '<img src="'. $meme->meme_url . $meme->meme_file_name.'.jpg' . '" />',
	  'post_status'    => 'pending',
	  'post_author'    => 1
	);

	$wp_post_id = wp_insert_post($new_wp_post);

	// Update meme info. Add WordPress post id with this meme as content
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";
	$wpdb->query($wpdb->prepare("UPDATE $table_name SET meme_wp_post_id = $wp_post_id WHERE id = $meme_id"));
}

// This function is a javascript workaround for Post - Redirect - Get pattern
function memeone_redirect_to_thank_you_page($meme_id)
{
	$string = '<script type="text/javascript">';
	$string .= 'window.location = "' . $_SERVER['REQUEST_URI'] . '?thankyou=&meme=' . $meme_id . '"';
	$string .= '</script>';

	echo $string;
	exit;
}

// Function to reset WordPress post id back to 0 (in case meme was deleted from Edit Post screen)
function memeone_reset_meme_wp_post_id($meme_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";
	$wpdb->query($wpdb->prepare("UPDATE $table_name SET meme_wp_post_id = 0 WHERE id = $meme_id"));
}

// Function to gen info about meme by id
function memeone_get_meme_by_id($meme_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";

	return $wpdb->get_row("SELECT * FROM $table_name WHERE id = $meme_id");
}

/*
* Code below is responsible for creating a link to the settings page
*/
add_action('admin_menu', 'memeone_admin_actions');
function memeone_admin_actions()
{
	add_menu_page("MemeOne", "MemeOne", "edit_others_posts", "memeone", "memeone_include_admin_settings_page");
}

function memeone_include_admin_settings_page() 
{ 
	include('memeone-admin-settings.php');
}

add_action('admin_menu', 'memeone_init_admin_settings_page');
function memeone_init_admin_settings_page()
{
	add_submenu_page( 'memeone', 'MemeOne Settings', 'Settings', 'edit_others_posts', 'memeone', 'memeone_include_admin_settings_page' ); 
}

add_action('admin_menu', 'memeone_init_admin_memes_page');
function memeone_init_admin_memes_page()
{
	add_submenu_page( 'memeone', 'MemeOne Memes', 'Memes', 'edit_others_posts', 'memeone-memes', 'memeone_include_admin_memes_page' ); 
}

function memeone_include_admin_memes_page()
{	
	include('memeone-admin-memes.php');
}

// Delete the meme from database and from server
function memeone_delete_meme($meme_id)
{
	// Get the info about it first (so we now where is the file which we want to delete)
	global $wpdb;

	$table_name = $wpdb->prefix . "memeone";
	$meme = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $meme_id");

	// Delete the file
	if(unlink($meme->path_to_meme . $meme->meme_file_name . '.jpg')){

		// If file was deleted successfully, delete info about the meme
		$wpdb->delete($table_name, array('id' => $meme_id));

		// If there was a WordPress post with this meme, delete it as well
		if ($meme->meme_wp_post_id != 0){
			wp_delete_post($meme->meme_wp_post_id, true);
		}

	}else{
		die('Couldnt delete a file. Please check your file permissions and try again.');
	}
}

// Function to delete all memes
function memeone_delete_all_memes()
{	
	// Get info about all the memes we have
	global $wpdb;

	$table_name = $wpdb->prefix . "memeone";
	$list_of_memes = $wpdb->get_results("SELECT * FROM $table_name");
	
	// Loop through every meme and delete the file, info and WP post
	foreach($list_of_memes as $meme){ 
		if (unlink($meme->path_to_meme . $meme->meme_file_name . '.jpg')){
			
			$wpdb->delete($table_name, array('id' => $meme->id));

			if ($meme->meme_wp_post_id != 0) {
				wp_delete_post($meme->meme_wp_post_id, true);
			}
		}
	}
}

// Get information about all memes that we have (from newest to oldest)
function memeone_get_memes()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";

	return $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
}

// JS workaround for 'post redirect get'
function memeone_post_redirect_get()
{
	
	$uri = $_SERVER['REQUEST_URI'];
	$string = '<script type="text/javascript">';
	$string .= 'window.location = "' . substr($uri, 0, strpos($uri, '&')) . '"';
	$string .= '</script>';

	echo $string;
	exit;
}

?>