<?php
/*
Plugin Name: Andy's Crumbs
Plugin URI: http://www.andrew-bellamy.co.uk/index.php/2009/08/andys-crumbs/
Description: This allows users to use breadcrumbs in their Wordpress Install
Author: Andrew Bellamy
Version: 1.0
Author URI: http://www.andrew-bellamy.co.uk/
*/

/*
Copyright 2009 Andrew Bellamy  (bellamy.aj@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function crumbs()
{
	global $wpdb;
	global $post;

	$temp = explode(' ', get_gmt_from_date($post->post_date));
	$date = explode('-', $temp[0]);

	$year = $date[0];
	$monthnum = $date[1];
	$day = $date[2];
	$search = $_REQUEST['s'];

	$seperator = '<span>&gt;</span>';
	$title = '';
	$title = '<a href="'.get_bloginfo('url').'/">Home</a>'.$seperator;

	$category_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE taxonomy = 'category'");
	$rand = rand(1, 2); 
	$randCat = rand(1, $category_count); 
	$categories = get_categories(); 
	if ($rand == 1) {
		$catname = $categories[$randCat]->cat_name; 
		$catid = $categories[$randCat]->cat_ID;
	}

	if(is_single()) {
		$sql = "SELECT name, slug FROM wp_terms LEFT JOIN wp_term_relationships ON ";
		$sql .= "(wp_terms.term_id = wp_term_relationships.term_taxonomy_id) WHERE ";
		$sql .= "wp_term_relationships.object_id=".$post->ID;
		$result = mysql_query($sql);
		while($row = mysql_fetch_assoc($result)) {
			if(get_cat_ID($row['name']) > 0){
				$title .= '<a href="'.get_bloginfo('url').'/index.php/category/'.$row['name'].'/">'.$row['name'].'</a>';
			}
		}	
		$title .= $seperator.apply_filters('the_title', get_the_title());
	}

	if(is_home()) {
		$title = '<a href="'.get_bloginfo('url').'/">Home</a>' ;
	}

	if(is_page()) {
		$title .= '<a href="'.get_bloginfo('url').'/index.php/'.apply_filters('the_title', get_the_title()).'/">'.apply_filters('the_title', get_the_title()).'</a>' ;
	}

	if(is_category()) {
		$title .= 'Category: <a href="'.get_bloginfo('url').'/index.php/category/'.get_query_var('category_name').'/">'.ucfirst(get_query_var('category_name')).'</a>';
	}

	if(is_archive()) {
		$datetime = new DateTime(get_gmt_from_date($post->post_date));
		//$title .= 'Archive';
		$y = '<a href="'.get_year_link($datetime->format('Y')).'">'.$datetime->format('Y').'</a>';
		$m = '<a href="'.get_month_link($datetime->format('Y'), $datetime->format('m')).'">'.$datetime->format('F').'</a>';
		$d = '<a href="'.get_day_link($datetime->format('Y'), $datetime->format('m'), $datetime->format('d')).'">'.$datetime->format('d').'</a>';
		if(is_year()) {
			$title .= 'Archive'.$seperator.$y;
		} elseif(is_month()) {
			$title .= 'Archive'.$seperator.$y.$seperator.$m;
		} elseif(is_day()) {
			$title .= 'Archive'.$seperator.$y.$seperator.$m.$seperator.$d;
		}
	}

	if(is_404()) {
		$title .= '404 Error';
	}

	if(is_tag()) {
		$title .= 'Tag: '.single_tag_title('', false);
	}

	if(!empty($search)) {
		$title .= 'Search Results for '.$search;
	}

	if(empty($search) && isset($_REQUEST['s'])) {
		$title .= 'Search Results';
	}

	if(is_author()) {
        $curauth = get_userdata(intval(get_query_var('author')));
		$title .= 'Author'.$seperator.$curauth->nickname;
	}

	echo '<div id="andy-crumbs">'.$title.'</div>';
}
?>