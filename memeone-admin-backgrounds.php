<?php

// Header for our settings page
$settings_page = '<div class="wrap">';
$settings_page .= "<h2>" . __( 'Memeone Backgrounds' ) . "</h2><hr />"; 

// The url can recieve a GET parameter to delete the background
if(isset($_GET['delete_id']) && is_numeric($_GET['delete_id']))
{
	memeone_delete_background($_GET['delete_id']);
}

// If this is a form submission for backgrounds priorities, then make corresponding changes
if(isset($_POST['memeone_priority_table_save_changes']) && $_POST['memeone_priority_table_save_changes'] == 'Y'){
	array_pop($_POST);

	foreach ($_POST as $key=>$value){
		$key_parts = explode('_', $key);
		$background_id = intval(array_pop($key_parts));
		$background = memeone_get_backgrounds("", $background_id);

		if ($background->priority != $value && is_numeric($value) && $value > 0) {
			memeone_update_background_prio($background_id, $value);
		}
	}

	memeone_post_redirect_get();
}

// If this is a form submission for a new background, then process it and save to database
if(isset($_POST['memeone_new_background_form_save_changes']) && $_POST['memeone_new_background_form_save_changes'] == 'Y'){
	
	$hasError = false;

	// Check if the new background is or right size and has correct extension
	if($_FILES['memeone_new_background_picture']['tmp_name'] != "" && $_FILES['memeone_new_background_picture']['size'] < 2097152 && ($_FILES['memeone_new_background_picture']['type'] == 'image/jpeg' || $_FILES['memeone_new_background_picture']['type'] == 'image/png')){
			$picture = $_FILES['memeone_new_background_picture']['tmp_name'];
	}else{
		
		$hasError = true;
		$picture_message = '<font color="red"><b>Select a background (max 2mb. .png or .jpg)* </b></font>';
	}

	// Check if there is a name for the background
	if(isset($_POST['memeone_new_background_name']) && $_POST['memeone_new_background_name'] != ''){
		$name = $_POST['memeone_new_background_name'];
	} else {
		
		$hasError = true;
		$name_message = '<font color="red"><b>Enter backgrounds name* </b></font>';
	}

	// Check if backgrounds priority has been set.
	if(isset($_POST['memeone_new_background_prio']) && $_POST['memeone_new_background_prio'] != ''){
		$priority = $_POST['memeone_new_background_prio'];
	} else {		
		$hasError = true;
		$prio_message = '<font color="red"><b>Enter backgrounds priority* </b></font>';
	}

	// If there was at least on error, print the settings page with corresponding messages
	if($hasError){ 
		$settings_page .= memeone_get_backgrounds_settings_page($picture_message, $name_message, $prio_message);
	} else {

	// If there were no errors, then save the background
		memeone_save_new_background($picture, $name, $priority);
		$settings_page .= '<div class="updated"> <p>Changes saved</p></div>';
		$settings_page .= memeone_get_backgrounds_settings_page();		
	}	
} else {

	// Just print the form if this is not a form submission
	$settings_page .= memeone_get_backgrounds_settings_page();
}

$settings_page .= memeone_get_backgrounds_list();
echo $settings_page;

// This function is used to generate new background submission form
function memeone_get_backgrounds_settings_page($picture_msg = "", $name_msg = "", $priority_msg = "")
{
	// Do we have any error messages to print?
	$picture_msg = $picture_msg == "" ? 'Select a background (max 2mb. .png or .jpg)* ' : $picture_msg;
	$name_msg = $name_msg == "" ? 'Enter backgrounds name* ' : $name_msg;
	$priority_msg = $priority_msg == "" ? 'Enter backgrounds priority* ' : $priority_msg;

	$new_background_form = '';
	$new_background_form .= '<form enctype="multipart/form-data" name="memeone_new_background_form" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';
	$new_background_form .= '<div class="memeone_input">'.$picture_msg;
	$new_background_form .= '<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />';
	$new_background_form .= '<input name="memeone_new_background_picture" type="file" /> </div>';

	$new_background_form .= '<div class="memeone_input">'.$name_msg;
	$new_background_form .= '<input name="memeone_new_background_name" type="text" maxlength="250" value=""/></div>';

	$new_background_form .= '<div class="memeone_input">'.$priority_msg;
	$new_background_form .= '<input name="memeone_new_background_prio" type="text" value="1"/></div>';

	$new_background_form .= '<input type="hidden" name="memeone_new_background_form_save_changes" value="Y" />';
	$new_background_form .= '<input type="submit" value="Save Background" />';
	$new_background_form .= '</form><br /><br />';

	return $new_background_form;
}

// This function is used to build a table of currently saved backgrounds
function memeone_get_backgrounds_list()
{
	$backgroundlist = '';
	$backgroundlist .= "<h3>" . __( 'Backgrounds' ) . "</h3><hr />";
	$backgrounds = memeone_get_backgrounds();

	if( empty($backgrounds) ){ // If no posters are found, say so

		$backgroundlist .= '<div class="memeone_admin_error">No backgrounds</div>';
		$backgroundlist .= '</div>';
		return $backgroundlist;

	}else{

		$backgroundlist .= '<form enctype="multipart/form-data" name="memeone_priority_table" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';

 		$backgroundlist .= '<table><tr>';
 		$backgroundlist .= '<td><b>Id</b></td>';
 		$backgroundlist .= '<td><b>Name</b></td>';
 		$backgroundlist .= '<td><b>Priority</b>&nbsp<input type="submit" style="text-align:right;" value="Save" /></td>';
 		$backgroundlist .= '<td><b>Delete</b></td></tr>';

		foreach ( $backgrounds as $background ) // Print info about posters
			{					
				$backgroundlist .= '<tr>';
				$backgroundlist .= '<td>' . $background->id . '</td>';
		
				$backgroundlist .= '<td><a href="' . $background->background_url . $background->background_file_name . '.jpg' . '" target="_blank">' . str_replace("\\", "", $background->name) . '</a></td>';

				$backgroundlist .= '<td><input type="text" class="priority_index_input" name="inline_index_' . $background->id . '" value="' . $background->priority . '" /></td>';

				$backgroundlist .= '<td><a href="' . $_SERVER['PHP_SELF'].'?page=memeone-backgrounds&delete_id=' . $background->id . '">';
				$backgroundlist .= '<img class="memeone_delete_meme_image" src="' . plugins_url( 'images/delete_button.png' , __FILE__ ) . '" >';
				$backgroundlist .= '</a></td>';
				
				$backgroundlist .= '</tr>';
			}

		$backgroundlist .= '</table>';
		$backgroundlist .= '<input type="hidden" name="memeone_priority_table_save_changes" value="Y" />';
		$backgroundlist .= '</form><br /><br />';

		$backgroundlist .= '</div>';
		
		return $backgroundlist;
	}
}

// This function is used to delete the background by id
function memeone_delete_background($background_id)
{
	// Delete corresponding record from db
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone_backgrounds";
	$background = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $background_id");
	
	// Delete the file if it exists
	if(unlink($background->path_to_background.$background->background_file_name . '.jpg')){
		$wpdb->delete($table_name, array( 'id' => $background_id));
	}else{
		die('Couldnt delete a file. Please check your file permissions and try again.');
	}

	memeone_post_redirect_get();
}

// This function is used for saving newly submitted background
function memeone_save_new_background($background, $name, $priority)
{
	// Where do we save the background?
	$destination_folder = get_option('memeone_default_upload_path').get_option('memeone_backgrounds_destination_folder');
	$destination_url = get_option('memeone_default_upload_url').get_option('memeone_backgrounds_destination_folder');

	// Create directory if it doesn't exist
	if (!file_exists($destination_folder)) {
    	mkdir($destination_folder, 0777, true);
	}	
	
	$imageproperties = getimagesize($background);

	switch($imageproperties[2]){
	case IMAGETYPE_JPEG:
		$background = imagecreatefromjpeg($background);
		break;
	
	case IMAGETYPE_PNG:
		$background = imagecreatefromPNG($background);
		break;
	default:
		return "";
			
	}

	if (!is_numeric($priority) || $priority < 0){
		$priority = 1;
	}

	// Generate a new name for a background file (based on the name given during submission)
	$new_picture_name = str_replace(" ", "_", (strtolower($name)));
	$new_picture_name = preg_replace("/[^a-zA-Z0-9_-]+/", "", $new_picture_name);

	// Get image dimensions
	$width_orig = $imageproperties[0];
	$height_orig = $imageproperties[1];

	// Resize image if needed
	if ($width_orig > 600 || $height_orig > 600) {

		$width = 600;
		$height = 600;

		$ratio_orig = $width_orig / $height_orig;

		if ($width / $height > $ratio_orig) {
		   $width = $height * $ratio_orig;
		} else {
		   $height = $width / $ratio_orig;
		}

		$new_background_image = imagecreatetruecolor($width, $height);

		imagecopyresampled($new_background_image, $background, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

		// Write the background to disk
		imagejpeg($new_background_image,  $destination_folder . $new_picture_name.".jpg", 100) or die ('Error writing poster to file. Please check if directory exists and its permissions.');
	} else {

		// If resizing is not needed, just write the background to disk
		imagejpeg($background,  $destination_folder . $new_picture_name.".jpg", 100) or die ('Error writing poster to file. Please check if directory exists and its permissions.');
	}
	
	// Add a record to database
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone_backgrounds";
	$wpdb->insert($table_name, array( 
		'path_to_background' => mysql_real_escape_string($destination_folder), 
		'name' => mysql_real_escape_string($name),
		'background_file_name' => mysql_real_escape_string($new_picture_name),
		'background_url' => mysql_real_escape_string($destination_url),
		'priority' => mysql_real_escape_string($priority)
	));

	memeone_post_redirect_get();
}

// This function updates priority for a given background
function memeone_update_background_prio($background_id, $new_prio)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "memeone_backgrounds";
	$wpdb->query($wpdb->prepare("UPDATE $table_name SET priority = $new_prio WHERE id = '$background_id'"));
}

?>