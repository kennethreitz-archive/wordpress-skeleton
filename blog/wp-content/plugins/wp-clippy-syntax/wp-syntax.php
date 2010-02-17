<?php
/*
Plugin Name: Syntax Hilight w/ Clippy!
Plugin URI: http://github.com/kennethreitz/wp-clippy-syntax-plugin
Description: Syntax highlighting using <a href="http://qbnz.com/highlighter/">GeSHi</a> supporting a wide range of popular languages.  One-click copy and paste. Awesome.
Author: Kenneth Reitz
Version: 0.5.2
Author URI: http://kennethreitz.com
*/

#  WP-Syntax Copyright (c) 2007-2009 Ryan McGeary
#  Kenneth Reitz extended it. Take a look:


// Override allowed attributes for pre tags in order to use <pre lang=""> in
// comments. For more info see wp-includes/kses.php
if (!CUSTOM_TAGS) {
  $allowedposttags['pre'] = array(
    'lang' => array(),
    'line' => array(),
    'escaped' => array(),
    'style' => array(),
    'width' => array(),
  );
  //Allow plugin use in comments
  $allowedtags['pre'] = array(
    'lang' => array(),
    'line' => array(),
    'escaped' => array(),
  );
}

include_once("geshi/geshi.php");

if (!defined("WP_CONTENT_URL")) define("WP_CONTENT_URL", get_option("siteurl") . "/wp-content");
if (!defined("WP_PLUGIN_URL"))  define("WP_PLUGIN_URL",  WP_CONTENT_URL        . "/plugins");

function clip($text='copy-me') {
$dir = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
return '
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="110" height="14" id="clippy" >
<param name="movie" value="'.$dir.'lib/clippy.swf"/>
<param name="allowScriptAccess" value="always" />
<param name="quality" value="high" />
<param name="scale" value="noscale" />
<param NAME="FlashVars" value="text='.$text.' ?>">
<param name="bgcolor" value="#FFFFFF">
<embed src="'.$dir.'lib/clippy.swf"
width="110"
height="14"
name="clippy"
quality="high"
allowScriptAccess="always"
type="application/x-shockwave-flash"
pluginspage="http://www.macromedia.com/go/getflashplayer"
FlashVars="text='.$text.'"
bgcolor="#FFFFFF"
/>
</object>';
}

function str_chop_lines($str, $lines = 4) {
    return implode("\n", array_slice(explode("\n", $str), $lines));
}

$str = str_chop_lines($str);

function wp_syntax_head()
{
  $css_url = WP_PLUGIN_URL . "/wp-syntax/wp-syntax.css";
  if (file_exists(TEMPLATEPATH . "/wp-syntax.css"))
  {
    $css_url = get_bloginfo("template_url") . "/wp-syntax.css";
  }
  echo "\n".'<link rel="stylesheet" href="' . $css_url . '" type="text/css" media="screen" />'."\n";
}

function wp_syntax_code_trim($code)
{
    // special ltrim b/c leading whitespace matters on 1st line of content
    $code = preg_replace("/^\s*\n/siU", "", $code);
    $code = rtrim($code);
    return $code;
}

function wp_syntax_substitute(&$match)
{
    global $wp_syntax_token, $wp_syntax_matches;

    $i = count($wp_syntax_matches);
    $wp_syntax_matches[$i] = $match;

    return "\n\n<p>" . $wp_syntax_token . sprintf("%03d", $i) . "</p>\n\n";
}

function wp_syntax_line_numbers($code, $start)
{
    $line_count = count(explode("\n", $code));
    $output = "<pre>";
    for ($i = 0; $i < $line_count; $i++)
    {
        $output .= ($start + $i) . "\n";
    }
    $output .= "</pre>";
    return $output;
}

function wp_syntax_highlight($match)
{
    global $wp_syntax_matches;

	// print_r($wp_syntax_matches[0][0]);
	
	$c = $wp_syntax_matches[0][0];
	
	 // $c = implode('\n',$wp_syntax_matches);
    $i = intval($match[1]);
    $match = $wp_syntax_matches[$i];

    $language = strtolower(trim($match[1]));
    // $line = trim($match[2]);
	 $line = trim(1);
    $escaped = trim($match[3]);
    $code = wp_syntax_code_trim($match[4]);
	 
    if ($escaped == "true") $code = htmlspecialchars_decode($code);

    $geshi = new GeSHi($code, $language);
    $geshi->enable_keyword_links(false);
    do_action_ref_array('wp_syntax_init_geshi', array(&$geshi));

    $output = "\n<div class=\"wp_syntax\">";

    if ($line)
    {
        $output .= "<table><tr><td class=\"line_numbers\">";
        $output .= wp_syntax_line_numbers($code, $line);
        $output .= "</td><td class=\"code\">";
        $output .= $geshi->parse_code();

		$clean = $wp_syntax_matches[0][0];	// Suck out Entire Pre tag

		$clean = preg_replace('/^[ \t]*[\r\n]+/m', '', $clean);	// Remove empty lines
		$clean = implode("\n", array_slice(explode("\n", $clean), 1));	// chop off top line (pre tag)
		$clean = urlencode($clean);
		$clean = str_replace(array('%3C%2Fpre%3E','%0D%0A%0D%0A%0D%0A'), "", $clean); // remote end pre tag
		$output .= "</td></tr></table>".clip($clean);


$string = preg_replace("/<img[^>]+\>/i", "", $string); 
    }
    else
    {
        $output .= "<div class=\"code\">";
        $output .= $geshi->parse_code();
        $output .= "</div>";
    }
    return

    $output .= "</div>\n";

    return $output;
}

function wp_syntax_before_filter($content)
{
    return preg_replace_callback(
        "/\s*<pre(?:lang=[\"']([\w-]+)[\"']|line=[\"'](\d*)[\"']|escaped=[\"'](true|false)?[\"']|\s)+>(.*)<\/pre>\s*/siU",
        "wp_syntax_substitute",
        $content
    );
}

function wp_syntax_after_filter($content)
{
    global $wp_syntax_token;

     $content = preg_replace_callback(
         "/<p>\s*".$wp_syntax_token."(\d{3})\s*<\/p>/si", 
         "wp_syntax_highlight", 
         $content
     );

    return $content;
}

$wp_syntax_token = md5(uniqid(rand()));

// Add styling
add_action('wp_head', 'wp_syntax_head');

// We want to run before other filters; hence, a priority of 0 was chosen.
// The lower the number, the higher the priority.  10 is the default and
// several formatting filters run at or around 6.
add_filter('the_content', 'wp_syntax_before_filter', 1);
add_filter('the_excerpt', 'wp_syntax_before_filter', 1);
add_filter('comment_text', 'wp_syntax_before_filter', 1);

// We want to run after other filters; hence, a priority of 99.
add_filter('the_content', 'wp_syntax_after_filter', 99);
add_filter('the_excerpt', 'wp_syntax_after_filter', 99);
add_filter('comment_text', 'wp_syntax_after_filter', 99);

?>
