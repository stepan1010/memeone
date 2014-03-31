<?php
/*
Plugin Name: MemeOne
Plugin URI: http://stepasyuk.com/memeone/
Description: Create memes and advices with MemeOne
Version: 1.1.5
Author: Stepan Stepasyuk
Author URI: http://stepasyuk.com
License: GPLv2
*/

register_uninstall_hook(__FILE__, "memeone_uninstall");

// Let's clean up after ourselves
function memeone_uninstall() 
{

	memeone_delete_all_memes();

	// Delete all our options
	delete_option('memeone_memes_per_page');
	delete_option('memeone_image_width_limit');
	delete_option('memeone_image_height_limit');
	delete_option('memeone_font_size');
	delete_option('memeone_font');
	delete_option('memeone_destination_folder');
	delete_option('memeone_default_upload_path');
	delete_option('memeone_default_upload_url');
	delete_option('memeone_version');

		// Drop our table
	global $wpdb;
    $table_name = $wpdb->prefix . "memeone";
	$wpdb->query("DROP TABLE IF EXISTS $table_name");
}


register_activation_hook(__FILE__, "memeone_activate");

// Handle activation
function memeone_activate() 
{
	global $wpdb;

	// Create a table for our plugin to store a list of all created memes
	$table_name = $wpdb->prefix . "memeone";
	$sql = "CREATE TABLE $table_name (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
	  creation_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  meme_file_name varchar(255) NOT NULL,
	  meme_url varchar(255) NOT NULL,
	  path_to_meme varchar(255) NOT NULL,
	  top_line varchar(50) DEFAULT '' NOT NULL,
	  bottom_line varchar(50) DEFAULT '' NOT NULL,
	  author varchar(55) DEFAULT '' NOT NULL,
	  UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	// Set default values to our settings
	if(!get_option('memeone_memes_per_page')){ // This options specifies how many db records to display per page in settings section
		update_option('memeone_memes_per_page', 10); // Set default value on activation
	}

	// When user uploads an image it is can be resized. Values below set max height and width for resized image
	if(!get_option('memeone_image_height_limit')){
		update_option('memeone_image_height_limit', 480); 
	}

	if(!get_option('memeone_image_width_limit')){ 
		update_option('memeone_image_width_limit', 640); 
	}

	// Set default font size
	if(!get_option('memeone_font_size')){
		update_option('memeone_font_size', 50); 
	}

	// Default path to font
	if(!get_option('memeone_font')){
		update_option('memeone_font', plugin_dir_path( __FILE__ ).'fonts/leaguegothic.ttf'); 
	}

	// Default path to destination foler to store memes.	
	if(!get_option('memeone_destination_folder')){
	
	update_option('memeone_destination_folder', '/memeone-memes/'); 
	}

	$upload_dir = wp_upload_dir();
	update_option('memeone_default_upload_url', $upload_dir['baseurl']);
	update_option('memeone_default_upload_path', $upload_dir['basedir']);
	update_option('memeone_version', 115);
}

// Register stylesheets
add_action('wp_enqueue_scripts', 'register_memeone_styles');
function register_memeone_styles() 
{
	wp_register_style('memeone_style', plugins_url( 'css/meme-one-style.css', __FILE__ ));
	wp_enqueue_style('memeone_style');
}

// Main function of the plugin
add_shortcode('memeone_plugin', 'memeone_load_plugin'); 
function memeone_load_plugin() 
{	
		// Check if this is a submission or not
	if(isset($_POST['memeone_created_meme'])){
		
		// If it is, validate the input first
		$meme = motgen_validate_input($_POST);

		// Then save the poster
		$meme_id = motgen_save_new_meme($meme);

		// And redirect to Thank you page
		memeone_redirect_to_thank_you_page($meme_id);

		return;
	}

	// If this is a redirect to Thank you page then return the Thank you page
	if (isset($_GET['thankyou'])) {
		return memeone_thank_you_page($_GET['poster']);
	} else {
		// Display the generator form if it's neither
		return _mgenerator();
	}
}

// Print submission form
function memeone_print_form($picture_message, $topline_content = '', $bottomline_content = '')
{
	
	$form = "";
	$form .= '<div id="memeone_form" class="widget">';
	$form .= '<form enctype="multipart/form-data" method="POST">';
	$form .= '<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />';
	$form .= '<input type="hidden" name="memeone_file_uploaded" value="Y" />';

	$form .= '<div class="memeone_input">'.$picture_message;
	$form .= '<input name="memeone_picture" type="file" /> </div>';
				
	$form .= '<div class="memeone_input">Enter top line. "\n" are supported ';
	$form .= '<input name="memeone_topline" type="text" maxlength="50" value="'.$topline_content.'"/></div>';
				
	$form .= '<div class="memeone_input">Enter bottom line. "\n" are supported ';
	$form .= '<input name="memeone_bottomline" type="text" maxlength="50" value="'.$bottomline_content.'"/></div>';

	$form .= '<p><input type="submit" class="btn" value="Create"></p>';
			
	$form .= '</form>';
	$form .= '</div>';
	
	return $form;
	die();
}

// Function for meme creation.
function memeone_create_meme($topline, $bottomline, $picture)
{
	// First, get our image properties
	$imageproperties = getimagesize($picture);
	$srcW = $imageproperties[0];
	$srcH = $imageproperties[1];

	// Prepare meme text
	$topline = mb_strtoupper(stripslashes($topline), "utf-8");
	$bottomline = mb_strtoupper(stripslashes($bottomline), "utf-8");

	// Get all neccessary settings
	$font_size = get_option('memeone_font_size');
	$font_face = get_option('memeone_font');
	$destination_folder = get_option('memeone_default_upload_path').get_option('memeone_destination_folder');

	// Open image to work with
	switch($imageproperties[2]){
	case IMAGETYPE_JPEG:
		$image = imagecreatefromjpeg($picture);
		break;
	
	case IMAGETYPE_PNG:
	
		$image = imagecreatefromPNG($picture);
		break;
	default:
		return "";
			
	}

	// Get the colors we need for drawing text
	$text_color_white = imagecolorallocate($image, 255, 255, 255);
	$text_color_black = imagecolorallocate($image, 0, 0, 0);

	// Get our dimension limits
	$new_width = get_option('memeone_image_width_limit') != '' ? get_option('memeone_image_width_limit') : 545;
	$new_height = get_option('memeone_image_height_limit') != '' ? get_option('memeone_image_height_limit') : 450;

	// Check if the picture needs to be resized.
	if (($new_width != 0 || $new_height != 0) && ($srcW > $new_width || $srcH > $new_height)){
		
		// Calculate new dimension with respect to limits and picture's aspect ratio.
		if ($srcW > $srcH) {

			if ($srcW > $new_width) {

				$cut_ratio = $srcW / $new_width;

				$new_height = $srcH / $cut_ratio; 
			}

		}else{

			if ($srcH > $new_height) {

				$cut_ratio = $srcH / $new_height;

				$new_width = $srcW / $cut_ratio;
			}
		}

		// Create new resized image
		$resized_image = imagecreatetruecolor($new_width, $new_height);

		// Resize original picture 
		imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $srcW, $srcH);

		// Update values for text coordinates calculations
		$srcW = $new_width;
		$srcH = $new_height;
		$image = $resized_image;
	}

	// Calculate our text dimensions
	$bbox = imagettfbbox($font_size,0 , $font_face, $topline);
	$bbox2 = imagettfbbox($font_size,0 , $font_face, $bottomline);

	// The following block will calculate where to draw our text

	// We start with the top line
	$toplines = array();

	// Check if the user specified new line signs himself, if so, respect them
	if (strstr($topline, "\n")){

		// Break our string to lines and put it in array
		$words = explode("\n", $topline);

		foreach ($words as $word) 
		{	
			// Push this arrays elementes (lines) to an array of toplines. Since php will take care of \n charecters we don't really have to worry about anything
			array_push($toplines, $word);
		}

	// If there are no \n signs we will have calculate everything by ourselves	
	}else{

		// Check if the original text is wider than the picture
		if (abs($bbox[4]) > $srcW-70) {
			
			// If so, break it into words
		 	$words = explode(" ", $topline);

		 	// Then we calculate how many words can one line fit
		 	$wordsonaline = '';
		 	
		 	// 70 is a sum of left and right margins for text
		 	$currentlinelength = 70;
		 	for ($i = 0; $i <= count($words)+1; $i++) {

		 		// Take length of each word
		 		$wordbox = imagettfbbox($font_size,0 , $font_face, $words[$i]." ");
		 		
		 		// And add it to each other until we will run out of space on one line
		 		$currentlinelength += abs($wordbox[4]);

		 		if($currentlinelength < $srcW)
		 		{	
		 			// If a word fits on a line, leave it there
		 			$wordsonaline .= $words[$i]." ";

		 			// If this was the last word of a text, push it to the final array.
		 			if ($i == count ($words)) array_push($toplines, $wordsonaline);
		 		}else{

		 			// If a word doesn't fit on a line, push the current line to result array
		 			array_push($toplines,$wordsonaline);

		 			// Start a new line by adding that word to it
		 			$currentlinelength = $wordbox[4]+70;
		 			$wordsonaline = '';
		 			$wordsonaline .= $words[$i]." ";

		 			// If this was the last word of a text, push it to the final array.
		 			if ($i == count ($words)) array_push($toplines, $wordsonaline);
		 		}
		 	}
		 }else{

		 	// If our original text fits all on one line then add it to the final array
		 	array_push($toplines, $topline);
		 }
	}

		// Draw topline on the picture
		$counter = 1;
		foreach ($toplines as $line) {

			// Get width of a line
			$bbox = imagettfbbox($font_size,0 , $font_face, $line);
		
			// Calculate x and y coordinates for it 
			$x = ($srcW - abs($bbox[4])) / 2;
			$y = (abs($bbox[7]) + 12) * $counter;

			// Since there is no stroke in GD library, we make a workaround by drawing the same line several times in black color
			imagettftext($image, $font_size, 0, $x, $y-2, $text_color_black, $font_face, stripcslashes($line));
			imagettftext($image, $font_size, 0, $x, $y+2, $text_color_black, $font_face, stripcslashes($line));
			imagettftext($image, $font_size, 0, $x-2, $y, $text_color_black, $font_face, stripcslashes($line));
			imagettftext($image, $font_size, 0, $x+2, $y, $text_color_black, $font_face, stripcslashes($line));
			imagettftext($image, $font_size, 0, $x+2, $y-2, $text_color_black, $font_face, stripcslashes($line));
			imagettftext($image, $font_size, 0, $x+2, $y+2, $text_color_black, $font_face, stripcslashes($line));
			imagettftext($image, $font_size, 0, $x-2, $y-2, $text_color_black, $font_face, stripcslashes($line));
			imagettftext($image, $font_size, 0, $x-2, $y+2, $text_color_black, $font_face, stripcslashes($line));

			// And finally add the line in white color on top of our previous drawings
			imagettftext($image, $font_size, 0, $x,$y,$text_color_white, $font_face, stripcslashes($line));	

			$counter++;
		}

	// Calculation for bottom text is the same
	$bottomlines = array();

	if (strstr($bottomline, "\n")){

		$words = explode("\n", $bottomline);

		foreach ($words as $word) 
		{
			array_push($bottomlines, $word);
		}
	}else{	
	
		if (abs($bbox2[4]) > $srcW-70) {

		 	$words = explode(" ", $bottomline);
		 	$wordsonaline = '';
		 	
		 	$currentlinelength = 70;
		 	for ($i = 0; $i <= count($words)+1; $i++) {

		 		$wordbox = imagettfbbox($font_size,0 , $font_face, $words[$i]." ");
		 		
		 		$currentlinelength += abs($wordbox[4]);
		 		if($currentlinelength < $srcW)
		 		{
		 			$wordsonaline .= $words[$i]." ";
		 			if ($i == count ($words)) array_push($bottomlines, $wordsonaline);
		 		}else{
		 			array_push($bottomlines,$wordsonaline);
		 			$currentlinelength = $wordbox[4]+70;
		 			$wordsonaline = '';
		 			$wordsonaline .= $words[$i]." ";
		 			if ($i == count ($words)) array_push($bottomlines, $wordsonaline);
		 		}
		 	}
		}else{
		 	array_push($bottomlines, $bottomline);
		}
	}

	$counter = count($bottomlines);
	
	foreach ($bottomlines as $line) {
		$bbox2 = imagettfbbox($font_size,0 , $font_face, $line);
	
		$y = count($bottomlines) > 1 ? ($srcH  - 12 - (abs($bbox2[7])+12) * ($counter-1)) : ($srcH - 12) * $counter;
		$x = ($srcW - abs($bbox2[4])) / 2;

		imagettftext($image, $font_size, 0, $x, $y-2, $text_color_black, $font_face, stripcslashes($line));
		imagettftext($image, $font_size, 0, $x, $y+2, $text_color_black, $font_face, stripcslashes($line));
		imagettftext($image, $font_size, 0, $x-2, $y, $text_color_black, $font_face, stripcslashes($line));
		imagettftext($image, $font_size, 0, $x+2, $y, $text_color_black, $font_face, stripcslashes($line));
		imagettftext($image, $font_size, 0, $x+2, $y-2, $text_color_black, $font_face, stripcslashes($line));
		imagettftext($image, $font_size, 0, $x+2, $y+2, $text_color_black, $font_face, stripcslashes($line));
		imagettftext($image, $font_size, 0, $x-2, $y-2, $text_color_black, $font_face, stripcslashes($line));
		imagettftext($image, $font_size, 0, $x-2, $y+2, $text_color_black, $font_face, stripcslashes($line));

		// And finally add the line in white color on top of our previous drawings
		imagettftext($image, $font_size, 0, $x,$y,$text_color_white, $font_face, stripcslashes($line));	

		$counter--;
	}
	
	// Generate a file name for our meme
	$new_picture_name = uniqid();

	// Write image to disk
	imagejpeg($image,  $destination_folder.$new_picture_name.".jpg", 100) or die ('Error writing poster to file. Please check if directory exists and its permissions.');

	// Free up memory
	imagedestroy($image);

	// Return file name for further display
	return $new_picture_name;
}

// Add a record about poster to the db
function memeone_add_db_record($topline, $bottomline, $memename)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";
	$current_user = wp_get_current_user();
	$wpdb->insert($table_name, array( 
		'creation_date' => current_time('mysql'),
		'meme_file_name' => mysql_real_escape_string($memename),
		'meme_url' => mysql_real_escape_string(get_option('memeone_default_upload_url').get_option('memeone_destination_folder')),
		'path_to_meme' => mysql_real_escape_string(get_option('memeone_default_upload_path').get_option('memeone_destination_folder')), 
		'top_line' => mysql_real_escape_string($topline),
		'bottom_line' => mysql_real_escape_string($bottomline),
		'author' => mysql_real_escape_string($current_user->user_login)
	));
}

/*
* Code below is responsible for creating a link to the settings page
*/
add_action('admin_menu', 'memeone_admin_actions'); // Displays link to our settings page in the admin menu
function memeone_admin_actions()
{
    add_options_page("meme-one", "MemeOne", 1, "meme-one", "memeone_admin");    
}

add_action( 'admin_enqueue_scripts', 'memeone_admin_style' ); 
function memeone_admin_style($hook) // Link our already registered script only to settings page of our plugin
{ 
	if( 'settings_page_meme-one' != $hook ){
    	return;
    }else{
    	wp_register_style( 'memeone_admin_style', plugins_url( 'css/meme-one-style-admin.css', __FILE__ ) );
    	wp_enqueue_style( 'memeone_admin_style' );
   }
}

// Function that includes the actual settings page
function memeone_admin() 
{ 
	include('memeone-admin.php');
}

// Delete a meme from the database and disk
function memeone_delete_meme($meme_id)
{
	// Delete corresponding record from db
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";
	$meme = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $meme_id");

	// Delete the file if it exists
	if(unlink($meme->path_to_meme.$meme->meme_file_name.'.jpg')){
		$wpdb->delete($table_name, array( 'id' => $meme_id));
	}else{
		die('Couldnt delete a file. Please check your file permissions and try again.');
	}

}

// Clear plugins db table
function memeone_delete_all_memes()
{
	global $wpdb;

	$table_name = $wpdb->prefix . "memeone";
	$list_of_memes = $wpdb->get_results( "SELECT meme_file_name, path_to_meme FROM " . $table_name);
	
	foreach($list_of_memes as $meme){ // Iterate files
		if (unlink($meme->path_to_meme.$meme->meme_file_name.'.jpg')){ // Delete file
			$wpdb->delete($table_name, array( 'meme_file_name' => $meme->meme_file_name));
		}
	}
}

// Count all the posters in the database (for pagination on the settings page)
function memeone_meme_count()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";

	$wpdb->get_results("SELECT * FROM $table_name");
	return $wpdb->num_rows;
}

// Get specific amout of posters (also for pagination on the settings page)
function memeone_get_memes($current_page_number, $total_rows_count)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone";
	$rows_per_page = get_option('memeone_memes_per_page');

	$sql_limit = ($current_page_number == 1) ? 0 : (($current_page_number - 1) * $rows_per_page);
	return $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT $sql_limit, $rows_per_page");
}

function memeone_post_redirect_get($to)
{
	// JS workaround for 'post redirect get'
	$args = strpos($_SERVER['REQUEST_URI'],'?') !== false ? '&' : '?';
	$string = '<script type="text/javascript">';
	$string .= 'window.location = "' . $_SERVER['REQUEST_URI'] .$args.$to.'"';
	$string .= '</script>';

	echo $string;
	exit;
}

function wrestlememes_gen_save_new_background($background, $name)
{
	$destination_folder = get_option('memeone_default_upload_path').get_option('memeone_destination_folder');
	imagejpeg($image,  $destination_folder.$new_picture_name.".jpg", 100) or die ('Error writing poster to file. Please check if directory exists and its permissions.');
}

?>