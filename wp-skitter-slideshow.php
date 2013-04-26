<?php
/*
Plugin Name: Skitter Slideshow
Plugin URI: http://thiagosf.net/projects/jquery/skitter
Description: jQuery Slideshow for Wordpress using Skitter Slideshow
Version: 2.3.1
Author: Thiago Silva Ferreira
Author URI: http://thiagosf.net
License: GPL

Copyright 2011 Thiago Silva Ferreira (thiago@thiagosf.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

register_activation_hook(__FILE__, 'wp_skitter_activate');

add_action('admin_menu', 'wp_skitter_add_menu');
add_action('admin_init', 'wp_skitter_reg_function');
add_action('init', 'init_load');
add_action('wp_ajax_load_more_media', 'load_more_media');

// Load more media
function load_more_media() 
{
	$last_id = isset($_GET['last_id']) ? (int) $_GET['last_id'] : 0;
	$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 1;
	
	// Get media library
	$args = array(
		'post_type'			=> 'attachment', 
		'post_mime_type'	=> array('image/png', 'image/jpeg', 'image/gif'), 
		'numberposts'		=> 50, 
		'offset'			=> $offset * 50, 
		'orderby'			=> 'ID', 
		'order'				=> 'DESC',
	);
	
	$attachments = get_posts( $args );
	
	$options_attachments = get_option('wp_skitter_attachments');
	
	$out = array();
	
	// All images media
	foreach($attachments as $i => $attachment) 
	{
		$id = $attachment->ID;
		if (is_array($options_attachments['image']) && in_array($id, $options_attachments['image'])) continue;
		
		$select = getSelectAnimations(array(
			'name'	=> 'wp_skitter_attachments[animation]['.$id.']',
			'id'	=> 'wp_skitter_attachment_animation_'.$id,
			'class'	=>  'attachments_animation'
		));
		
		$out[] = array(
			'id'		=> $id, 
			'thumb'		=> wp_get_attachment_image( $id, array(50, 50) ), 
			'image'		=> wp_get_attachment_image( $id, array(150, 150) ), 
			'select'	=> $select
		);
	}
	
	echo json_encode($out);
	exit;
}

/** 
 * Load scripts 
 */
function init_load()
{
    wp_enqueue_script('skitter', WP_PLUGIN_URL . '/wp-skitter-slideshow/js/jquery.skitter.min.js', array('jquery'));
    // wp_enqueue_script('jquery.animate-colors', WP_PLUGIN_URL . '/wp-skitter-slideshow/js/jquery.animate-colors-min.js', array('jquery'));
    wp_enqueue_script('jquery.easing', WP_PLUGIN_URL . '/wp-skitter-slideshow/js/jquery.easing.1.3.js', array('jquery'));
    wp_enqueue_style( 'skitter.styles', WP_PLUGIN_URL . '/wp-skitter-slideshow/css/skitter.styles.min.css');
}

/** 
 * Add skitter menu configuration 
 */
function wp_skitter_add_menu() 
{
    $page = add_options_page('Skitter Slideshow', 'Skitter Slideshow', 'administrator', 'wp_skitter_menu', 'wp_skitter_menu_function');
}

/** 
 * Skitter settings 
 */
function getSkitterSettings() 
{
	$wp_skitter_settings = array(
		'wp_skitter_category', 
		'wp_skitter_slides', 
		'wp_skitter_animation', 
		'wp_skitter_velocity', 
		'wp_skitter_interval', 
		'wp_skitter_navigation', 
		'wp_skitter_label', 
		'wp_skitter_type_navigation', 
		'wp_skitter_easing_default', 
		'wp_skitter_animateNumberOut', 
		'wp_skitter_animateNumberOver', 
		'wp_skitter_animateNumberActive', 
		'wp_skitter_hideTools', 
		'wp_skitter_fullscreen', 
		'wp_skitter_xml', 
		'wp_skitter_width_label', 
		'wp_skitter_width', 
		'wp_skitter_height',
		'wp_skitter_show_randomly',
		'wp_skitter_crop',
		'wp_skitter_attachments',
		'wp_skitter_type',
		'wp_skitter_numbers_align',
		'wp_skitter_enable_navigation_keys',
		'wp_skitter_controls',
		'wp_skitter_controls_position',
		'wp_skitter_focus',
		'wp_skitter_focus_position',
		'wp_skitter_preview',
		'wp_skitter_stop_over',
		'wp_skitter_with_animations',
		'wp_skitter_auto_play',
		'wp_skitter_background',
		'wp_skitter_labelAnimation', 
		'wp_skitter_theme', 
	);
	return $wp_skitter_settings;
}

/** 
 * Options register 
 */
function wp_skitter_reg_function() 
{
	$settings = getSkitterSettings();
	foreach ($settings as $option) {
		register_setting( 'wp_skitter_settings', $option );
	}
}

/** 
 * Skitter active 
 */
function wp_skitter_activate() 
{
	add_option('wp_skitter_category','1');
	add_option('wp_skitter_animation','random');
	add_option('wp_skitter_slides','5');
	add_option('wp_skitter_navigation','true');
	add_option('wp_skitter_label','true');
	add_option('wp_skitter_type_navigation','numbers');
	add_option('wp_skitter_numbers_align','left');
	add_option('wp_skitter_crop','true');
	add_option('wp_skitter_type','posts');
	add_option('wp_skitter_stop_over','false');
	add_option('wp_skitter_auto_play','true');
	add_option('wp_skitter_background','#000');
	add_option('wp_skitter_animation','slideUp');
	add_option('wp_skitter_theme','square');
	
	add_option('wp_skitter_attachments', array(
		'image' => array(),
		'label' => array(),
		'link' => array(),
		'animation' => array()
	));
	
	delete_option('wp_skitter_numbers');
	delete_option('wp_skitter_thumbs');
	delete_option('wp_skitter_dots');
}

/** 
 * Get values skitter formatted 
 */
function filterValueSkitter ($option, $value) 
{
	$booleans = array(
		'wp_skitter_navigation', 
		'wp_skitter_label', 
		'wp_skitter_hideTools', 
		'wp_skitter_fullscreen', 
		'wp_skitter_show_randomly',
		'wp_skitter_enable_navigation_keys',
		'wp_skitter_controls',
		'wp_skitter_focus',
		'wp_skitter_preview',
		'wp_skitter_stop_over',
		'wp_skitter_auto_play',
	);
	
	$strings = array(
		'wp_skitter_animation', 
		'wp_skitter_width', 
		'wp_skitter_height', 
		'wp_skitter_easing_default', 
		'wp_skitter_xml', 
		'wp_skitter_width_label',
		'wp_skitter_numbers_align',
		'wp_skitter_controls_position',
		'wp_skitter_focus_position',
		'wp_skitter_background',
		'wp_skitter_labelAnimation',
		'wp_skitter_theme',
	);
	
	if (in_array($option, $booleans)) {
		$value = $value == 'true' ? 'true' : 'false';
	} 
	else if (in_array($option, $strings) && !empty($value)) {
		$value = '"'.$value.'"';
	}
	return $value;
}

/** 
 * Get animations 
 */
function getAnimations () 
{
	$animations = array(
		'random', 
		'randomSmart', 
		'cube', 
		'cubeRandom', 
		'block', 
		'cubeStop', 
		'cubeHide', 
		'cubeSize', 
		'horizontal', 
		'showBars', 
		'showBarsRandom', 
		'tube',
		'fade',
		'fadeFour',
		'paralell',
		'blind',
		'blindHeight',
		'blindWidth',
		'directionTop',
		'directionBottom',
		'directionRight',
		'directionLeft',
		'cubeStopRandom',
		'cubeSpread',
		'cubeJelly',
		'glassCube',
		'glassBlock',
		'circles',
		'circlesInside',
		'circlesRotate',
		'cubeShow',
		'upBars',
		'downBars',
		'hideBars',
		'swapBars',
		'swapBarsBack',
	);
	return $animations;
}

/**
 * Get themes
 */
function getThemes () 
{
	$themes = array(
		'default', 
		'minimalist', 
		'round', 
		'clean', 
		'square', 
	);

	return $themes;
}

/** 
 * Get select options of animations 
 */
function getSelectAnimations ($options = array()) 
{
	$name = $options['name'];
	$id = $options['id'];
	$class = $options['class'];
	
	$animations = getAnimations();
	$out  = '<select name="'.$name.'" id="'.$id.'" class="'.$class.'">';
	$out .= '<option value="">--</option>';
	foreach($animations as $animation) {
		$selected = ($animation == $options['selected']) ? ' selected="selected"' : '';
		$out .= sprintf('<option value="%s"%s>%s</option>', $animation, $selected, $animation);
	}
	$out .= '</select>';
	return $out;
}

/** 
 * Debug arrays and others 
 */
if (!function_exists('pr'))
{
	function pr ($array) 
	{
		echo '<pre>';
		print_r($array);
		echo '</pre>';
	}
}

/** 
 * Show skitter 
 */
function show_skitter() 
{
	$width_skitter = get_option('wp_skitter_width');
	$height_skitter = get_option('wp_skitter_height');
	$background_skitter = get_option('wp_skitter_background');
	
	$category = get_option('wp_skitter_category');
	$wp_skitter_slides = get_option('wp_skitter_slides');
	
?>

<style type="text/css">
	.box_skitter { 
		width:<?php echo $width_skitter; ?>px;
		height:<?php echo $height_skitter; ?>px; 
		background: <?php echo $background_skitter; ?>; 
	}
	.box_skitter img { 
		width: auto !important;
		max-width: none !important;
	}
</style>

<?php
	
	$skitter_images = array();
	
	switch (get_option('wp_skitter_type')) 
	{	
		case 'library' : 
			$attachments = get_option('wp_skitter_attachments');
			
			if (!empty($attachments)) {
				foreach($attachments['image'] as $id_post) {
					$post = get_post($id_post);
					$image = wp_get_attachment_image_src( $id_post, 'original');
					$skitter_images[] = array(
						'image' => $image[0], 
						'link' => $attachments['link'][$id_post], 
						'label' => $attachments['label'][$id_post], 
						'animation' => $attachments['animation'][$id_post], 
					);
				}
				wp_reset_query();
			}
			
			break; 
			
		case 'xml' : 
			$skitter_xml = true;
			
			break;
			
		case 'posts' : default : 
			$query_posts = 'cat='.$category.'&posts_per_page='.$wp_skitter_slides;
			query_posts( $query_posts ); 
			
			if ( have_posts() ) : 
				while ( have_posts() ) : the_post(); if (has_post_thumbnail()) :
					$content = strip_tags(get_the_content());
					if (preg_match('/^Link:(http:\/\/.*)/i', $content, $matches)) {
						$link = $matches[1];
					}
					else {
						$link = get_permalink();
					}
					$thumbnail = get_the_post_thumbnail($post->ID, 'large');
					preg_match('/src="([^"]*)"/i', $thumbnail, $matches);
					$image = (isset($matches[1])? $matches[1] : null);
					
					$skitter_images[] = array(
						'image' => $image, 
						'link' => $link,  
						'label' => get_the_title(), 
						'animation' => null, 
					);
				endif; endwhile;
			endif;
			
			wp_reset_query();
			
			break; 
	}
	
	if (!empty($skitter_images) || isset($skitter_xml)) {
	
?>
<div id="wp_skitter" class="box_skitter">
	<?php
	
		if (!isset($skitter_xml)) {
	
	?>
	<ul>
		<?php
		
		$remove_animation_option = false;
		$crop = get_option('wp_skitter_crop');
		
		foreach($skitter_images as $skitter) {
				
			$class_animation = (!empty($skitter['animation']) ? 'class="'.$skitter['animation'].'"' : '');
			
			if (!empty($class_animation)) $remove_animation_option = true;
			
			if ($crop) {
				$image_slider  = WP_PLUGIN_URL.'/wp-skitter-slideshow/image.php?image='.$skitter['image'];
				$image_slider .= '&width='.$width_skitter.'&height='.$height_skitter;
				$image_slider = '<img src="'.$image_slider.'" '.$class_animation.' />';
			}
			else {
				$image_slider = '<img src="'.$skitter['image'].'" '.$class_animation.' />';
			}
			
		?>
			<li>
				<?php
				if (!empty($skitter['link'])) {
				?>
				<a href="<?php echo $skitter['link']; ?>" title="<?php echo $skitter['label']; ?>"><?php echo $image_slider ?></a>
				<?php
				}
				else {
					echo $image_slider;
				}
				?>
				<?php
				if (!empty($skitter['label'])) {
				?>
				<div class="label_text">
					<p><?php echo $skitter['label']; ?></p>
				</div>
				<?php
				}
				?>
			</li>
		<?php
			
		}
		
		?>
	</ul>
	<?php
	
		}
	
	?>
</div>
<?php

		$options = array();
		$settings = getSkitterSettings();
		$block = array('wp_skitter_category', 'wp_skitter_slides', 'wp_skitter_width', 'wp_skitter_height', 'wp_skitter_type', 'wp_skitter_attachments');

		foreach ($settings as $option) {
			$get_option = get_option($option);
			$get_option = filterValueSkitter($option, $get_option);
			if (!empty($get_option) && !in_array($option, $block)) {
				if ($option == 'wp_skitter_type_navigation') {
					if ($get_option != 'none') {
						$options[] = $get_option.': true';
					}
					else {
						$options[] = 'numbers: false';
					}
				}
				else {
					if ($option == 'wp_skitter_xml' && get_option('wp_skitter_type') != 'xml') continue;
					if ($option == 'wp_skitter_animation' && !empty($remove_animation_option)) continue;
					if ($option == 'wp_skitter_background') continue;
					$options[] = str_replace('wp_skitter_', '', $option).': '.$get_option;
				}
			}
		}

		$options = implode(", \n\t\t", $options);

?>

<script type="text/javascript">
jQuery(window).load(function() {
	jQuery('#wp_skitter').skitter({
		<?php echo $options;?>
	});
});
</script>
<?php 
	
	} // end if (!empty($skitter_images))
	
} // end function show_skitter()

/** 
 * Admin 
 */
function wp_skitter_menu_function() 
{
	// Get media library
	$args = array(
		'post_type' => 'attachment', 
		'post_mime_type' => array('image/png', 'image/jpeg', 'image/gif'), 
		'numberposts' => 50, 
		'offset' => 0, 
		'orderby' => 'ID', 
		'order' => 'DESC',
	);
	
	$attachments = get_posts( $args );
	
	$wp_skitter_type = get_option('wp_skitter_type');
	
?>

<style type="text/css" rel="stylesheet" media="all">

.box_image_sk {background:#eee;padding:5px;border:1px solid #000;margin:10px;display:none;position:relative;}
.item_image_sk {width:100px;overflow:hidden;float:left;margin:0 0px 0 0;}
.item_image_sk img {margin-bottom:0px;}
.settings_slide {margin-left:110px;}
.settings_slide label:first-child {margin-top:0px;}
.settings_slide label {display:block;margin-top:5px;}
.settings_slide input {width:99%;}
.remove_slide_sk {padding:1px 5px;background:#cc0000;color:#fff;font-size:12px;font-weight:bold;position:absolute;bottom:10px;right:10px; z-index:10;text-decoration:none;text-transform:uppercase;border:1px solid #990000;}
.remove_slide_sk:hover {background:#cc3333;color:#fff;}

/* Images selecteds */
#box_selected_images {float:left;width:50%;background:#555;height:400px;overflow:auto;}

/* List images */
#box_list_images {float:left;width:50%;background:#eee;height:400px;overflow:auto;position:relative;}
#box_list_images .item_list_sk {width:50px;height:50px;overflow:hidden;float:left;margin:5px 0 0 5px;background:#fff;}
#box_list_images .item_list_sk a {float:left;}

#box_more_media {text-align:center;float:left;width:100%;margin:20px 0;}
#box_more_media a {padding:5px 20px;background:#ddd;border:1px solid #999;color:#333;text-shadow:#fff 1px 1px 0;text-decoration:none;border-radius:4px;}

#loading_list_sk {position:absolute;top:10px;left:10px;background:#333;color:#fff;font-size:16px;font-weight:bold;border:1px solid #000;padding:5px;display:none;}

.clear {clear:both;}

#tabs_sk {background:#ccc;margin-bottom:10px;float:left;width:100%;}
#tabs_sk a {background:#fff;padding:5px 10px;float:left;margin:5px 0 5px 5px;text-decoration:none;font-size:18px;}
#tabs_sk a.tab_selected_sk {background:#333;color:#fff;}

.tab_item_sk {display:none;float:left;width:100%;margin-bottom:20px;background:#eee;}
.tab_item_sk table {margin:0;}
.tab_item_selected_sk {display:block;}

#setting_advanced {}
#setting_advanced h3 {margin:0;}

</style>

<script>

var offset_sk = 1;
var request_sk = false;

jQuery.noConflict();
jQuery(document).ready(function() {
	
	jQuery('#form_skitter').submit(function() {
		jQuery('.box_image_sk').each(function() {
			if (jQuery(this).css('display') != 'block') jQuery(this).remove();
		});
	});
	
	jQuery('#box_more_media a').click(function() {
		if (request_sk) return false;
		request_sk = true;
		
		var last_id = jQuery('.item_list_sk:last').find('a').attr('href').replace('#', '');
		var scroll_top = jQuery('#box_list_images').scrollTop() + 10;
		
		jQuery('#loading_list_sk').css({'top': scroll_top}).fadeTo(300, 0.9);
		
		jQuery.getJSON('admin-ajax.php?action=load_more_media&offset='+offset_sk+'&last_id='+last_id, function(json) {
			var list  = '';
			var item  = '';
			
			jQuery.each(json, function(key, val) {
				// List
				list += '<div class="item_list_sk">';
				list += '<a href="#'+val.id+'" title="Add">'+val.thumb+'</a>';
				list += '</div>';
				
				// Item
				item += '<div class="box_image_sk" id="box_image_sk_'+val.id+'">';
				item += '<div class="item_image_sk">';
				item += val.image;
				item += '<input class="attachments_image" type="checkbox" value="'+val.id+'" name="wp_skitter_attachments[image][]" id="wp_skitter_attachment_'+val.id+'" checked="checked" style="display:none;" />';
				item += '</div>';
				item += '<div class="settings_slide">';
				item += '<label for="wp_skitter_attachment_label_'+val.id+'">Label</label>';
				item += '<input class="attachments_label" type="text" name="wp_skitter_attachments[label]['+val.id+']" id="wp_skitter_attachment_label_'+val.id+'" size="50" />';
				item += '<label for="wp_skitter_attachment_link_'+val.id+'">Link</label>';
				item += '<input class="attachments_link" type="text" name="wp_skitter_attachments[link]['+val.id+']" id="wp_skitter_attachment_link_'+val.id+'" size="50" />';
				item += '<label for="wp_skitter_attachment_animation_'+val.id+'">Animation</label>';
				item += val.select;
				item += '</div>';
				item += '<div class="clear"></div>';
				item += '<a href="#" class="remove_slide_sk" title="Remove">x</a>';
				item += '</div>';
			});
			
			jQuery('#box_more_media').before(list);
			jQuery('#box_selected_images').append(item);
			
			jQuery('#loading_list_sk').fadeOut(300);
			
			request_sk = false;
			offset_sk++;
		});
		return false;
	});
	
	jQuery('.item_list_sk a').live('click', function() {
		var id = jQuery(this).attr('href').replace('#', '');
		if (jQuery('#box_image_sk_'+id).css('display') != 'block') {
			jQuery(this).fadeTo(300,0.3);
			jQuery('#box_image_sk_'+id)
				.appendTo('#box_selected_images')
				.slideDown(300);
		}
		else {
			jQuery(this).fadeTo(300,1.0);
			jQuery('#box_image_sk_'+id).slideUp(300);
		}
		return false;
	});
	
	jQuery('.remove_slide_sk').live('click', function() {
		jQuery(this).parents('.box_image_sk').slideUp(300);
		return false;
	});
	
	jQuery('#tabs_sk a').click(function() {
		var rel = jQuery(this).attr('rel');
		var wp_skitter_type = jQuery(this).attr('href').replace('#', '');
		
		jQuery('.tab_selected_sk').removeClass('tab_selected_sk');
		jQuery('.tab_item_selected_sk').removeClass('tab_item_selected_sk');
		
		jQuery(this).addClass('tab_selected_sk');
		jQuery('#'+rel).addClass('tab_item_selected_sk');
		
		jQuery('#wp_skitter_type').val(wp_skitter_type);
		
		return false;
	});
	
});
</script>

<div class="wrap">
	<h2>Skitter Slideshow</h2>
	<form method="post" action="options.php" id="form_skitter">
		<?php settings_fields( 'wp_skitter_settings' ); ?>
		<input type="hidden" value="<?php echo $wp_skitter_type;?>" name="wp_skitter_type" id="wp_skitter_type" />
		
		<?php
		
		$selected_library = ($wp_skitter_type == 'library') ? 'class="tab_selected_sk"' : '';
		$selected_posts = ($wp_skitter_type == 'posts') ? 'class="tab_selected_sk"' : '';
		$selected_xml = ($wp_skitter_type == 'xml') ? 'class="tab_selected_sk"' : '';
		
		$tab_selected_library = ($wp_skitter_type == 'library') ? ' tab_item_selected_sk' : '';
		$tab_selected_posts = ($wp_skitter_type == 'posts') ? ' tab_item_selected_sk' : '';
		$tab_selected_xml = ($wp_skitter_type == 'xml') ? ' tab_item_selected_sk' : '';
		
		?>
		<div id="tabs_sk">
			<a href="#library" rel="tab_media_library_sk" <?php echo $selected_library;?>>Media Library</a>
			<a href="#posts" rel="tab_posts_sk" <?php echo $selected_posts;?>>Posts</a>
			<a href="#xml" rel="tab_xml_sk" <?php echo $selected_xml;?>>XML</a>
		</div>
		
		<div id="tab_posts_sk" class="tab_item_sk<?php echo $tab_selected_posts;?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Category</th>
					<td>
						<select name="wp_skitter_category" id="wp_skitter_category"> 
							<option value="">Select a Category</option> 
							<?php 
								
							$category = get_option('wp_skitter_category');
							$categories=  get_categories(); 
							
							foreach ($categories as $cat) 
							{
								$option = '<option value="'.$cat->term_id.'"';
								if ($category == $cat->term_id) $option .= ' selected="selected">';
								else { $option .= '>'; }
								$option .= $cat->cat_name;
								$option .= ' ('.$cat->category_count.')';
								$option .= '</option>';
								echo $option;
							}
								
							?>
						</select>
					</td>
				</tr>
				<tr valign="top" style="border-top:1px solid #ccc;">
					<th scope="row">Number of slides</th>
					<td><input type="text" name="wp_skitter_slides" id="wp_skitter_slides" size="7" value="<?php echo get_option('wp_skitter_slides'); ?>" /></td>
				</tr>
			</table>
		</div>
		
		<div id="tab_xml_sk" class="tab_item_sk<?php echo $tab_selected_xml;?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">XML Path</th>
					<td>
						<input type="text" name="wp_skitter_xml" id="wp_skitter_xml" size="100" value="<?php echo get_option('wp_skitter_xml'); ?>" />
					</td>
				</tr>
			</table>
		</div>
		
		<div id="tab_media_library_sk" class="tab_item_sk<?php echo $tab_selected_library;?>">
			<div id="box_list_images">
				<div id="loading_list_sk">Loading...</div>
				<?php
				
				// Media library
				$options_attachments = get_option('wp_skitter_attachments');
				
				foreach($attachments as $i => $attachment) 
				{
					$id = $attachment->ID;
					$style = (is_array($options_attachments['image']) && in_array($id, $options_attachments['image'])) ? ' style="opacity:0.3;"' : '';
					
				?>
					<div class="item_list_sk">
						<a href="#<?php echo $id;?>" title="Add"<?php echo $style;?>><?php echo wp_get_attachment_image( $id, array(50, 50) );?></a>
					</div>
				<?php
					
				}
				
				?>
				<div id="box_more_media">
					<a href="#">More</a>
				</div>
			</div>
			
			<div id="box_selected_images">
			
				<?php
				
				if (!empty($options_attachments)) 
				{
					// Loop images selecteds
					foreach($options_attachments['image'] as $id) 
					{
						$attachment = get_post($id);
						
						if (empty($attachment)) continue;
						
						$label = $options_attachments['label'][$id];
						$link = $options_attachments['link'][$id];
						$animation = $options_attachments['animation'][$id];
						
				?>
					<div class="box_image_sk" id="box_image_sk_<?php echo $id;?>" style="display:block;">
						<div class="item_image_sk">
							<?php echo wp_get_attachment_image( $id, array(150, 150) );?>
							<input class="attachments_image" type="checkbox" value="<?php echo $id;?>" name="wp_skitter_attachments[image][]" id="wp_skitter_attachment_<?php echo $id;?>" checked="checked" style="display:none;" />
						</div>
						<div class="settings_slide">
							<label for="wp_skitter_attachment_label_<?php echo $id;?>">Label</label>
							<input class="attachments_label" type="text" name="wp_skitter_attachments[label][<?php echo $id;?>]" id="wp_skitter_attachment_label_<?php echo $id;?>" size="50" value="<?php echo $label;?>" />
							<label for="wp_skitter_attachment_link_<?php echo $id;?>">Link</label>
							<input class="attachments_link" type="text" name="wp_skitter_attachments[link][<?php echo $id;?>]" id="wp_skitter_attachment_link_<?php echo $id;?>" size="50" value="<?php echo $link;?>" />
							<label for="wp_skitter_attachment_animation_<?php echo $id;?>">Animation</label>
							<?php echo getSelectAnimations(array(
								'name' => 'wp_skitter_attachments[animation]['.$id.']',
								'id' => 'wp_skitter_attachment_animation_'.$id,
								'selected' => $animation,
								'class' =>  'attachments_animation'
							));?>
						</div>
						<a href="#" class="remove_slide_sk" title="Remove">x</a>
						<div class="clear"></div>
					</div>
				<?php
				
					}
				}
				
				// All images media
				foreach($attachments as $i => $attachment) 
				{
					$id = $attachment->ID;
					if (is_array($options_attachments['image']) && in_array($id, $options_attachments['image'])) continue;
					
				?>
					<div class="box_image_sk" id="box_image_sk_<?php echo $id;?>">
						<div class="item_image_sk">
							<?php echo wp_get_attachment_image( $id, array(150, 150) );?>
							<input class="attachments_image" type="checkbox" value="<?php echo $id;?>" name="wp_skitter_attachments[image][]" id="wp_skitter_attachment_<?php echo $id;?>" checked="checked" style="display:none;" />
						</div>
						<div class="settings_slide">
							<label for="wp_skitter_attachment_label_<?php echo $id;?>">Label</label>
							<input class="attachments_label" type="text" name="wp_skitter_attachments[label][<?php echo $id;?>]" id="wp_skitter_attachment_label_<?php echo $id;?>" size="50" />
							<label for="wp_skitter_attachment_link_<?php echo $id;?>">Link</label>
							<input class="attachments_link" type="text" name="wp_skitter_attachments[link][<?php echo $id;?>]" id="wp_skitter_attachment_link_<?php echo $id;?>" size="50" />
							<label for="wp_skitter_attachment_animation_<?php echo $id;?>">Animation</label>
							<?php echo getSelectAnimations(array(
								'name' => 'wp_skitter_attachments[animation]['.$id.']',
								'id' => 'wp_skitter_attachment_animation_'.$id,
								'class' =>  'attachments_animation'
							));?>
						</div>
						<a href="#" class="remove_slide_sk" title="Remove">x</a>
						<div class="clear"></div>
					</div>
				<?php
					
				}
				
				?>
			</div>
			
			<div class="clear"></div>
		</div>
		
		<div id="setting_advanced">
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row" colspan="2"><h3>Customization</h3></th>
				</tr>
				<tr valign="top">
					<th scope="row">Skitter Theme</th>
					<td>
						<?php $wp_skitter_theme = get_option('wp_skitter_theme'); ?>
						<select name="wp_skitter_theme" id="wp_skitter_theme">
							<?php
							
							$themes = getThemes();
							
							foreach ($themes as $theme) 
							{
								$selected = ($theme == $wp_skitter_theme) ? ' selected="selected"' : '';
								$value = $theme != 'all' ? $theme : '';
								echo sprintf('<option value="%s"%s>%s</option>', $value, $selected, $theme);
							}
							
							?>
						</select>
					</td>
				</tr>
				<tr valign="top" style="border-top:1px solid #ccc;">
					<th scope="row">Animation type</th>
					<td>
						<?php $wp_skitter_animation = get_option('wp_skitter_animation'); ?>
						<select name="wp_skitter_animation" id="wp_skitter_animation">
							<option value="">--</option>
							<?php
							
							$animations = getAnimations();
							
							foreach ($animations as $animation) 
							{
								$selected = ($animation == $wp_skitter_animation) ? ' selected="selected"' : '';
								$value = $animation != 'all' ? $animation : '';
								echo sprintf('<option value="%s"%s>%s</option>', $value, $selected, $animation);
							}
							
							?>
						</select>
					</td>
				</tr>
				
				<tr valign="top" style="border-top:1px solid #ccc;">
					<th scope="row">Navigation type</th>
					<td>
						<?php $wp_skitter_type_navigation = get_option('wp_skitter_type_navigation'); ?>
						<select name="wp_skitter_type_navigation" id="wp_skitter_type_navigation">
							<?php
							
							$types_navigation = array(
								'numbers', 
								'thumbs', 
								'dots', 
								'none', 
							);
							
							foreach ($types_navigation as $type_navigation) 
							{
								$selected = ($type_navigation == $wp_skitter_type_navigation) ? ' selected="selected"' : '';
								$value = $type_navigation != 'all' ? $type_navigation : '';
								echo sprintf('<option value="%s"%s>%s</option>', $value, $selected, $type_navigation);
							}
							
							?>
						</select>
					</td>
				</tr>
				
				<tr valign="top" style="border-top:1px solid #ccc;">
					<th scope="row">width</th>
					<td><input type="text" name="wp_skitter_width" id="wp_skitter_width" size="20" value="<?php echo get_option('wp_skitter_width'); ?>" />px</td>
				</tr>
				
				<tr valign="top" style="border-top:1px solid #ccc;">
					<th scope="row">height</th>
					<td><input type="text" name="wp_skitter_height" id="wp_skitter_height" size="20" value="<?php echo get_option('wp_skitter_height'); ?>" />px</td>
				</tr>
				
				<tr valign="top" style="border-top:1px solid #ccc;">
					<th scope="row">background</th>
					<td><input type="text" name="wp_skitter_background" id="wp_skitter_background" size="20" value="<?php echo get_option('wp_skitter_background'); ?>" /></td>
				</tr>
				
				<tr valign="top" style="border-top:1px solid #ccc;">
					<th scope="row">crop image</th>
					<td><input type="checkbox" value="true" name="wp_skitter_crop" id="wp_skitter_crop" <?php echo (get_option('wp_skitter_crop') == 'true' ? ' checked="checked"' : ''); ?> /></td>
				</tr>
				
				<?php
				
				$data = array(
					array('velocity', 'Velocity of animation', '1', "2"),
					array('interval', 'Interval between transitions', '2500', "3000"),
					array('navigation', 'Navigation display', 'true', "false"),
					array('numbers_align', 'Alignment of numbers/dots/thumbs', "left", "center"),
					array('label', 'Label display', 'true', "false"),
					array('labelAnimation', 'Label animation', 'slideUp', "slideUp, left, right, fixed"),
					array('width_label', 'Width label', "null", "300px"),
					array('easing_default', 'Easing default', 'null', "easeOutBack"),
					array('animateNumberOut', 'Animation/style number', "null", "{backgroundColor:'#000', color:'#ccc'}"),
					array('animateNumberOver', 'Animation/style hover number', "null", "{backgroundColor:'#000', color:'#ccc'}"),
					array('animateNumberActive', 'Animation/style active number', "null", "{backgroundColor:'#000', color:'#ccc'}"),
					array('hideTools', 'Hide numbers and navigation', "false", "true"),
					array('fullscreen', 'Fullscreen mode', "false", "true"),
					array('show_randomly', 'Randomly slides', "false", "true"),
					array('enable_navigation_keys', 'Enable navigation keys', "false", "true"),
					array('controls', 'Option play/pause manually', "false", "true"),
					array('controls_position', 'Position of button controls', "center", "center, leftTop, rightTop, leftBottom, rightBottom"),
					array('focus', 'Focus slideshow', "false", "true"),
					array('focus_position', 'Position of button focus slideshow', "center", "center, leftTop, rightTop, leftBottom, rightBottom"),
					array('preview', 'Preview with dots', "false", "true"),
					array('stop_over', 'Stop animation to move mouse over it.', "false", "true"),
					array('with_animations', 'Specific animations', "[]", "['paralell', 'glassCube', 'swapBars']"),
					array('auto_play', 'Sets whether the slideshow will start automatically', "true", "false"),
				);
				
				foreach($data as $linha) 
				{
				
				?>
				
				<tr valign="top" style="border-top:1px solid #ccc;">
					<th scope="row"><?php echo $linha[0];?></th>
					<td>
						<?php
						
						if ($linha[3] == 'true' || $linha[3] == 'false') {
							
							$selected = (get_option('wp_skitter_'.$linha[0]) == 'true' ? ' checked="checked"' : '');
							
						?>
						<input type="checkbox" value="true" name="wp_skitter_<?php echo $linha[0];?>" <?php echo $selected;?> />
						<?php
							
						}
						else {
						
						?>
						<input type="text" name="wp_skitter_<?php echo $linha[0];?>" id="wp_skitter_<?php echo $linha[0];?>" size="50" value="<?php echo get_option('wp_skitter_'.$linha[0]); ?>" />
						<?php
						
						}
						
						?>
					</td>
				</tr>
		
				<tr valign="top" style="background-color:#eee;border-bottom:1px solid #ccc;">
					<td scope="row" style="padding-left:20px;">Default: <strong><?php echo $linha[2];?></strong></td>
					<td>Example: <strong><?php echo $linha[3];?></strong></td>
				</tr>
				
				<?php
				
				}
				
				?>
			
			</table>
		</div>
	 
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>

<?php 

} 

/**
 * Shortcode
 */
function skitter_shortcode( $atts ) 
{
	return show_skitter( $atts );
}
add_shortcode( 'skitter', 'skitter_shortcode' );

?>