<div class="wrap">
<h2>Memes</h2><hr />
<?php

//This file is responsible for dislaying information about all the memes as a table.

// First, we check if any parameters we passed in
// Delete a meme (by id)
if(isset($_GET['delete_meme']) && is_numeric($_GET['delete_meme']))
{
	memeone_delete_meme($_GET['delete_meme']);
	memeone_post_redirect_get();
}

// Delete all memes from the database
if(isset($_GET['delete_all_memes']) && is_numeric($_GET['delete_all_memes']))
{
	memeone_delete_all_memes();
	memeone_post_redirect_get();
}

// Create a WordPress post with existing meme as a content
if(isset($_GET['create_wp_post']))
{
	memeone_turn_meme_to_wp_post($_GET['create_wp_post']);
	memeone_post_redirect_get();
}

//Now we get info about all the memes we have and start formatting it
$memes_list = memeone_get_memes();

// Display appropriate message if we don't have any memes
if (empty($memes_list)) {
	echo '<p><h2> No memes :( </h2></p>';
	exit;
}

// "Delete all" button
echo '<a href="'.$_SERVER['PHP_SELF'].'?page=memeone-memes&delete_all_memes=1"><span class="memeone_delete_all"><img src="' . plugins_url( 'images/delete_button.png' , __FILE__ ) . '">Delete All</span></a></div>';
?>
<table>
	<tr>
		<td><?php echo "<b>" . __( 'Id' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Creation Date' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Author' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Meme File Name' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Background' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Top text' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Bottom text' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'WP Post ID' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Delete' ) . "</b>"; ?></td>
	</tr>
		<?php
		$admin_url = admin_url();
			foreach ( $memes_list as $meme ) // Print info about memes
				{					
					$meme_info = '<tr>';
					$meme_info .= '<td>' . $meme->id . '</td>';
					$meme_info .= '<td>' . $meme->creation_date . '</td>';
					$meme_info .= '<td>' . $meme->author . '</td>';
					$meme_info .= '<td><a href="' . $meme->meme_url.$meme->meme_file_name . '.jpg' . '" target="_blank">' . $meme->meme_file_name . '</a></td>';
					$meme_info .= '<td>' . str_replace("\\", "", $meme->background_name) . '</td>';
					$meme_info .= '<td>' . str_replace("\\", "", $meme->top_line) . '</td>';
					$meme_info .= '<td>' . str_replace("\\", "", $meme->bottom_line) . '</td>';
					
					// If there is no WP post with this meme, create a button to make one
					if ($meme->meme_wp_post_id == 0)
					{
						$meme_info .= '<td>N/A <a href="' . $_SERVER['PHP_SELF'] . '?page=memeone-memes&create_wp_post=' . $meme->id . '"> Publish </a></td>';
					} else {

						// Otherwise, get meme status
						$post_status = get_post_status($meme->meme_wp_post_id);

						// Empty status means that the meme was deleted from Edit Post screen, so we should make appropriate changes to our db table
						if ($post_status == ''){
							$meme_info .= '<td>N/A <a href="' . $_SERVER['PHP_SELF'] . '?page=memeone-memes&create_wp_post=' . $meme->id . '"> Publish </a></td>';
							memeone_reset_meme_wp_post_id($meme->id);
						} else {
							// If status is not empty, display it alongside with id of corresponding WP post
							$meme_info .= '<td><a href="' . $admin_url . 'post.php?post=' . $meme->meme_wp_post_id . '&action=edit">' . $meme->meme_wp_post_id . '</a> - ' . $post_status . ' </td>';		
						}		
					}

					// Delete meme button 
					$meme_info .= '<td><a href="' . $_SERVER['PHP_SELF'] . '?page=memeone-memes&delete_meme=' . $meme->id . '">';
					$meme_info .= '<img class="memeone_delete_meme_image" src="' . plugins_url( 'images/delete_button.png' , __FILE__ ) . '" >';
					$meme_info .= '</a></td>';
					$meme_info .= '</tr>';

					echo $meme_info;
				}
		?>
	</tr>
</table>