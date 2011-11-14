<?php
/*
Plugin Name: OS Post Gallery
Description: Automatically lists all the attached images for a particular post in a user-friendly manner.
Version: 0.6
Author: Oli Salisbury
*/

//Config
$hide_featured_image = false;
$pages_in = array();
$posts_in = array(0);

/*
DO NOT EDIT BELOW HERE
*/

//hooks
if (WP_ADMIN) {
	add_action('admin_menu', 'os_postgallery_init');
	add_action('admin_head', 'os_postgallery_css');
	add_action('admin_head', 'os_postgallery_init_js');
	add_action('admin_footer', 'os_postgallery_init_footer');
}

//function to add to posts
function addtoindvpost($thispostid, $array) {
	if (sizeof($array)<0) { $array = array(); }
	$ins = false;
	foreach ($array as $id) {
		if	($id > 0) { $ins = true; break; } 
	}
	//returns
	if ($ins && in_array($thispostid, $array)) { //if there are ins defined and this post is in ins
		return true;
	} else if (in_array("-$thispostid", $array) || in_array(0, $array)) { //if this post is suppressed or all posts are suppressed then hide
		return false;
	} else if (!$ins) { //if ins arent defined show all
		return true;
	} else {
		return false;
	}
}

//function to get all attachments for a post
function get_attached_images($hide_featured_image=true, $post_id=NULL) {
	global $post;
	//set default
	if (!$post_id) { $post_id = $post->ID; }
	//make query
	$q['post_type'] = 'attachment';
	$q['post_mime_type'] = 'image';
	$q['numberposts'] = -1;
	$q['post_parent'] = $post->ID;
	$q['orderby'] = 'menu_order';
	$q['order'] = 'ASC';
	//hide featured image
	if ($hide_featured_image) {
		$q['post__not_in'] = array(get_post_thumbnail_id($post->ID));
	}
	//return query
	return get_posts($q);
}

//Inserts HTML for meta box, including all existing attachments
function os_postgallery_add() { 
	global $post, $hide_featured_image;
	echo '<div id="os_postgallery">';
	echo '<div id="os_postgallery_buttons">';
	echo '<a href="media-upload.php?post_id='.$post->ID.'&TB_iframe=1" class="button button-highlighted thickbox">Upload Image</a>';
	echo ' <a href="#" id="os_postgallery_ajaxclick" class="button">Refresh</a>';
	echo ' <span id="os_postgallery_loading" style="display:none;"><img src="images/loading.gif" alt="Loading..." style="width:auto; height:auto; margin:0; vertical-align:middle" /></span>';
	echo '</div>';
	echo '<div id="os_postgallery_alert"><em>Don\'t forget to click "Refresh" once you\'ve uploaded or edited your images</em></div>';
	echo '<ul id="os_postgallery_ajax">';
	$attachments = get_attached_images($hide_featured_image);
	$i=0;
	foreach ($attachments as $attachment) {
		if ($attachment->ID == get_post_thumbnail_id($post->ID)) { //check if this image is featured
			$featured_image = true; 
		}
		echo '<li class="widget';
		echo $featured_image ? ' featured_image' : '';
		echo '">';
		echo wp_get_attachment_image($attachment->ID, 'thumbnail').'<br />';
		echo '<strong>'.$attachment->post_title.'</strong><br />';
		echo $attachment->post_excerpt ? '<em>'.$attachment->post_excerpt.'</em><br />' : '';
		echo '<a href="media-upload.php?post_id='.$post->ID.'&tab=gallery&TB_iframe=1" class="thickbox edit-post-attachment" rel="'.$post->ID.'">Edit</a>';
		echo $featured_image ? '<b>Featured Image</b>' : '';
		echo '</li>';
		$featured_image = false;
		$i++;
	}
	echo '</ul>';
	echo '<div style="clear:both;"></div>';
	echo '</div>';
}

//Creates meta box on all Posts and Pages
function os_postgallery_metabox() {
	global $_GET, $pages_in, $posts_in;
	//for custom post types
	add_meta_box('os_postgallery_list', 'Image Gallery', 'os_postgallery_add', '');
	//pages
	if (addtoindvpost($_GET['post'], $pages_in)) {
		add_meta_box('os_postgallery_list', 'Image Gallery', 'os_postgallery_add', 'page');
	}
	//posts
	if (addtoindvpost($_GET['post'], $posts_in)) {
		add_meta_box('os_postgallery_list', 'Image Gallery', 'os_postgallery_add', 'post');
	}
}

//javascript in header
function os_postgallery_init_js() {
	echo '
	<script type="text/javascript" charset="utf-8">
	jQuery(document).ready(function(){
		//hide redundant boxes
		jQuery("#gallery-settings").hide();
		jQuery("tr.url").hide();
		jQuery("tr.align").hide();
		jQuery("tr.image-size").hide();
		jQuery("td.savesend input").hide();
		//ajax update
		jQuery("#os_postgallery_ajaxclick").click(function() {
			jQuery("#os_postgallery_alert").hide();
			jQuery("#os_postgallery_ajax").slideUp("fast", function() { jQuery("#os_postgallery_loading").show(); }).load("'.$_SERVER['REQUEST_URI'].' #os_postgallery_ajax", function() { 
				jQuery(this).slideDown("fast");
				jQuery("#os_postgallery_loading").hide();
			});
			return false;
		});
		//alert
		jQuery(".thickbox, #remove-post-thumbnail").live("click", function(){ jQuery("#os_postgallery_alert").show(); });
	});
	</script>';
}

//javascript in footer
function os_postgallery_init_footer() {
	echo '
	<script type="text/javascript">
		//equalise heights of os_postgallery
		//var maxHeight = 0;
		//jQuery("#os_postgallery li").each(function(){ if (jQuery(this).height() > maxHeight) { maxHeight = jQuery(this).height(); } });
		//jQuery("#os_postgallery li").height(maxHeight);
	</script>';
}

//plugin css
function os_postgallery_css() {
	echo '
	<style type="text/css">
	#os_postgallery { padding:10px 10px 0 10px; }
	#os_postgallery_buttons { margin-bottom:20px; }
	#os_postgallery ul { margin:0; padding:0; list-style:none; }
	#os_postgallery li { margin:0 10px 10px 0; padding:10px; width:150px; display:inline-block; overflow:hidden; position:relative; min-height:150px; vertical-align:top; zoom:1; *display:inline; }
	#os_postgallery li.featured_image { border:2px solid red; }
	#os_postgallery img { margin:0 0 5px 0; width:150px; height:auto; }
	#os_postgallery b { display:block; padding:3px; color:#fff; background:red; position:absolute; bottom:0; right:0; font-size:9px; }
	#os_postgallery_alert { display:none; position:absolute; top:5px; left:130px; z-index:100; }
	#os_postgallery_alert em { display:block; background:#fff59b; padding:10px; position:relative; }
	#os_postgallery_alert em:after { 
		border-color: #fff59b transparent;
		border-style: solid;
		border-width: 10px 10px 0;
		bottom: -10px;
		content: "";
		display: block;
		left: 10px;
		position: absolute;
		width: 0;
	}
	</style>';
}

//initialise plugin
function os_postgallery_init() {
	wp_enqueue_script('jquery');
	os_postgallery_metabox();
}
?>