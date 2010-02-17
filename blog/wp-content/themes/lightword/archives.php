<?php
/*
Template Name: Blog archive
*/
?>
<?php get_header(); ?>
<div id="content-body">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<h2><a title="<?php the_title(); ?>" href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>
<?php the_content(__('Continue reading','lightword'));?>
<?php
// echo archives start
$lastpost = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_date <'" . current_time('mysql') . "' AND post_status='publish' AND post_type='post' AND post_password='' ORDER BY post_date DESC LIMIT 1");
//$output = get_option('hfy_archives_'.$lastpost);
if(empty($output)){
	$output = '';
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'hfy_archives_%'");
	// Get all of the months that have posts
	$monthquery = "SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month, count(ID) as posts FROM " . $wpdb->posts . " WHERE post_date <'" . current_time('mysql') . "' AND post_status='publish' AND post_type='post' AND post_password='' GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC";
	$monthresults = $wpdb->get_results($monthquery);

	if ($monthresults) {
		// Loop through each month
		foreach ($monthresults as $monthresult) {
			$thismonth	= zeroise($monthresult->month, 2);
			$thisyear	= $monthresult->year;

			// Get all of the posts for the current month
			$postquery = "SELECT ID, post_date, post_title, comment_count FROM " . $wpdb->posts . " WHERE post_date LIKE '$thisyear-$thismonth-%' AND post_date AND post_status='publish' AND post_type='post' AND post_password='' ORDER BY post_date DESC";
			$postresults = $wpdb->get_results($postquery);

			if ($postresults) {
				// The month year title things
				$text = sprintf('%s %d', $month[zeroise($monthresult->month,2)], $monthresult->year);
                $text_id = strtolower(str_replace(" ","",$text));
				$postcount = count($postresults);
                if($postcount=="1") $postcount_text = "post"; else $postcount_text = "posts";
				$output .= "<h2 class=\"archive_h2\"><a onclick=\"jQuery('#$text_id').toggle();\">" . $text . "<span> (" . count($postresults) . " ".$postcount_text." )</span></a></h2>";
                $output .= "<ul id='$text_id' class='hide'>\n";

				foreach ($postresults as $postresult) {
					if ($postresult->post_date != '0000-00-00 00:00:00') {
						$url = get_permalink($postresult->ID);
						$arc_title	= $postresult->post_title;
						if ($arc_title)
							$text = wptexturize(strip_tags($arc_title));
						else
							$text = $postresult->ID;
						$title_text = wp_specialchars($text, 1);
						$output .= '	<li>' . mysql2date('m/d', $postresult->post_date) . ':&nbsp;' . "<a href='$url' title='$title_text'>$text</a>";
					  	$output .= '&nbsp;(' . $postresult->comment_count . ' ';
                        if($postresult->comment_count=="1") $output.= "comment"; else $output.= "comments";
						$output .= ")</li>\n";
					}
				}
				$output .= "</ul>\n\n";
			}
		}
		update_option('hfy_archives_'.$lastpost,$output);
	}else{
		$output = '<strong>'. __('Not Found','lightword') .'</strong> '. __("Sorry, but you are looking for something that isn't here.","lightword") .'';
	}

}
echo $output;
//echo archives end
?>

<?php endwhile; else: ?>

<h2><?php _e('Not Found','lightword'); ?></h2>
<p><?php  _e("Sorry, but you are looking for something that isn't here.","lightword"); ?></p>

<?php endif; ?>

</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>