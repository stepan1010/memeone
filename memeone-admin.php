<?php

// Header for our settings page
$settings_form = "<h2>" . __( 'MemeOne Settings' ) . "</h2><hr />"; 

// Pagination implementation. pn -> Page number
if(!isset($_GET['pn']) || $_GET['pn'] == 0 || !is_numeric($_GET['pn']))
{
	$current_page_number = 1;
}else{
	$current_page_number = $_GET['pn'];
}

// Delete a poster (by id) from settings page. di -> delete item
if(isset($_GET['di']) && is_numeric($_GET['di']))
{
	memeone_delete_meme($_GET['di']);
}

// Delete all posters from the database. da -> delete all
if(isset($_GET['da']) && is_numeric($_GET['da']))
{
	memeone_delete_all_memes();
}

// If this is a form submission, update options
if(isset($_POST['memeone_save_changes']) && $_POST['memeone_save_changes'] == 'Y'){

	if(isset($_POST['memeone_memes_per_page']) && is_numeric(trim($_POST['memeone_memes_per_page']))){
		update_option('memeone_memes_per_page', $_POST['memeone_memes_per_page']);
	}

	if(isset($_POST['memeone_image_width_limit']) && is_numeric(trim($_POST['memeone_image_width_limit']))){
		update_option('memeone_image_width_limit', trim($_POST['memeone_image_width_limit']));
	}

	if(isset($_POST['memeone_image_height_limit']) && is_numeric(trim($_POST['memeone_image_height_limit']))){
		update_option('memeone_image_height_limit', trim($_POST['memeone_image_height_limit']));
	}

	if(isset($_POST['memeone_font_size']) && is_numeric(trim($_POST['memeone_font_size']))){
		update_option('memeone_font_size', trim($_POST['memeone_font_size']));
	}

	if(isset($_POST['memeone_font'])) {
		update_option('memeone_font', $_POST['memeone_font']);
	}

	if(isset($_POST['memeone_destination_folder'])) {
		$destination_folder = $_POST['memeone_destination_folder'][strlen($_POST['memeone_destination_folder'])-1] == '/' ? $_POST['memeone_destination_folder'] : $_POST['memeone_destination_folder'].'/';
		$destination_folder = $destination_folder[0] == '/' ? $destination_folder : '/'.$destination_folder;
		update_option('memeone_destination_folder', $destination_folder);
		update_option('memeone_destination_folder_url', $destination_folder);
	}

	// Say that settings are saved.
	$settings_form .= '<div class="updated"> <p>Changes saved</p></div>';
}

// Prepare the settings form
$settings_form .= '<div class="wrap">';
$settings_form .= '<form name="memeone_form" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';
$settings_form .= '<p>'.__( 'How many memes to display per page in the table below ' );
$settings_form .= '<select name="memeone_memes_per_page">';
$settings_form .= '<option value="10">10</option><option value="50">50</option><option value="200">200</option></select></p>';
$settings_form .= '<br><p>When a user uploads an image to make a meme from the image gets resized. Below you can specify max dimensions for the resized image. Note: if both fields are 0 no limits will be applied and original size will be kept</p>';
$settings_form .= '<p>Enter max height: <input type="text" name="memeone_image_height_limit" value="'.get_option('memeone_image_height_limit').'" /></p>';
$settings_form .= '<p>Enter max width: <input type="text" name="memeone_image_width_limit" value="'.get_option('memeone_image_width_limit').'" /></p>';
$settings_form .= '<p>Font (.ttf only): <input type="text" style="width:400px;" name="memeone_font" value="'.get_option('memeone_font').'" /></p>';
if(!file_exists(get_option('memeone_font'))){
	$settings_form .= '<font color=red>File '.get_option('memeone_font').' not found.</font>';
}else{
	$file_parts = pathinfo(get_option('memeone_font'));
	if ($file_parts['extension'] != 'ttf') {
		$settings_form .= '<font color=red>File '.get_option('memeone_font').' is not a .ttf file.</font>';
	}
}

$settings_form .= '<p>Save memes to: '.get_option('memeone_default_upload_path').'<input type="text" style="width:400px;" name="memeone_destination_folder" value="'.get_option('memeone_destination_folder').'" /></p>';
if(!is_dir(get_option('memeone_default_upload_path').get_option('memeone_destination_folder'))) {

	if (!mkdir(get_option('memeone_default_upload_path').get_option('memeone_destination_folder'))) {
	$settings_form .= '<font color=red>'.get_option('memeone_default_upload_path').get_option('memeone_destination_folder').' Can not be created. Please check your file permissions.</font>';

	}
}

$settings_form .= '<br><p>Enter meme font size: <input type="text" name="memeone_font_size" value="'.get_option('memeone_font_size').'" /></p>';
$settings_form .= '<input type="hidden" name="memeone_save_changes" value="Y" />';
$settings_form .= '<input type="submit" value="Save Options" />';
$settings_form .= '</form><br /><br />';

// Code below is responsible for pagination. 

// Get the total amount of posters we have
$total_rows_count = memeone_meme_count();

	if($total_rows_count == 0){ // If no posters are found, say so
		echo $settings_form.'</div>';
		echo "<h3>" . __( 'Memes' ) . "</h3><hr />";
		echo '<div class="memeone_admin_error">No memes found</div>';
		return;
	}else{
		$rows_per_page = get_option('memeone_memes_per_page'); 
		$total_page_count = ceil($total_rows_count / $rows_per_page); // Calculate how many posters per page should be displayed
		$meme_list = memeone_get_memes($current_page_number, $total_rows_count); // Get a specific number of posters
	}

	echo $settings_form; // Pring settings form

  echo '<div><span class="memeone_meme_table_caption">' . __( 'Memes' ) . '</span>';
  echo '<a href="'.$_SERVER['PHP_SELF'].'?page=meme-one&da=1"><span class="memeone_delete_all"><img src="' . plugins_url( 'images/delete_button.jpg' , __FILE__ ) . '">Delete All</span></a></div>';   
 ?>

	<table>
		<tr>
			<td><?php echo "<b>" . __( 'Id' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Creation Date' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Author' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Meme File Name' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Topline' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Bottomline' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Delete' ) . "</b>"; ?></td>
		</tr>
			<?php
				foreach ( $meme_list as $meme ) // Print info about posters
					{					
						$meme_info = '<tr>';
						$meme_info .= '<td>'.$meme->id.'</td>';
						$meme_info .= '<td>'.$meme->creation_date.'</td>';
						$meme_info .= '<td>'.$meme->author.'</td>';
						$meme_info .= '<td><a href="'. $meme->meme_url.$meme->meme_file_name.'.jpg' . '" target="_blank">'.$meme->meme_file_name.'</a></td>';
						$meme_info .= '<td>'.$meme->top_line.'</td>';
						$meme_info .= '<td>'.$meme->bottom_line.'</td>';
						$meme_info .= '<td><a href="'.$_SERVER['PHP_SELF'].'?page=meme-one&pn='.$current_page_number.'&di='.$meme->id.'">';
						$meme_info .= '<img class="memeone_delete_meme_image" src="' . plugins_url( 'images/delete_button.jpg' , __FILE__ ) . '" >';
						$meme_info .= '</a></td>';
						$meme_info .= '</tr>';

						echo $meme_info;
					}

			?>
		</tr>
	</table>   

<?php
	
	$pagination = '<div class="memeone_pagination">'; // Pring page numbers
	for($i = 1;$i<=$total_page_count;$i++){

		$pagination .= '<a class="memeone_admin_link" href="'.$_SERVER['PHP_SELF'].'?page=meme-one&pn='.$i.'">'.$i.' &nbsp </a>';
	}
	$pagination .= '</div>';
	echo $pagination;

?>
</div> 