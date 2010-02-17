<?php

include("code.php");

function test_lang($lang, $language = null, $line = null, $escaped = null)
{
  global $code;
  if (!isset($language)) $language = $lang;
  else $as = "as $language";

  if (isset($escaped)) $c = htmlspecialchars($code[$lang]);
  else { $c = $code[$lang]; $escaped = "false"; }

  $snippet = <<<EOF
<h2>$lang $as</h2>
<p>This *is* what some <code>$lang</code> code looks like (escaped:$escaped):</p>
<pre lang='$language' line="$line" escaped="$escaped"> \t \r
$c
</pre>
EOF;

  return $snippet;
}

function gather_content()
{
  $content = '';
  $content .= test_lang('php');
  $content .= test_lang('lisp', null, 1);
  $content .= test_lang('java', null, 1);
  $content .= test_lang('xml');
  $content .= test_lang('xml', null, null, "true");
  $content .= test_lang('html', 'html4strict');
  $content .= test_lang('html', 'xml', 18);
  $content .= test_lang('html', 'xml', 18, "true");
  $content .= test_lang('ocaml');
  $content .= test_lang('python');
  $content .= test_lang('ruby', null, 18);
  $content .= test_lang('ruby');
  $content .= test_lang('rails');
  $content .= test_lang('c');
  return $content;
}

function test_head()
{
  echo apply_filters("wp_head", "");
}

function test_all()
{
  echo apply_filters("the_content", gather_content());
}

function test_all_with_other_filters()
{
  add_filter('the_content', 'pre_killer');   // bad if run before GeSHi
  add_filter('the_content', 'amp_exposer');  // bad if run after GeSHi

  if (file_exists("filters/filters.php"))
  {
    include("filters/filters.php");
  }

  echo apply_filters("the_content", gather_content());
}

include("../wp-syntax.php");
?>

<html>
<head>
<title>WP-Syntax Test Page</title>
<link rel="stylesheet" href="../wp-syntax.css" type="text/css" media="screen" />
<?php
test_head();
define("TEMPLATEPATH", "../");
test_head();
?>
<style type="text/css" media="screen">
.wp_syntax td div, .wp_syntax div div {
  padding: 0;
}
</style>
</head>

<body>
<div style="width:50%;">
  <h1>Vanilla, without other filters</h1>
<?php
test_all();
?>

  <h1>Modified, with other filters</h1>
<?php
test_all_with_other_filters();
?>
</div>
</body>
</html>


<?php

function amp_exposer($content)
{
    return str_replace("&", "&amp;", $content);
}

function pre_killer($content)
{
    return preg_replace("/<(\/)?pre([^>]*)>/", "[$1pre$2]", $content);
}

/*
 * === WORDPRESS STUBS ===
 */
function get_bloginfo($arg) {
    return "http://yourblog.com/blog";
}

function get_option($arg) {
    return "http://yourblog.com/blog";
}

function remove_filter($tag, $function_to_remove, $priority = 10)
{
    return true;
}

function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1)
{
    global $test_filter;

    $test_filter[$tag][$priority][] = $function_to_add;
    $test_filter[$tag][$priority] = array_unique($test_filter[$tag][$priority]);

    return true;
}

function apply_filters($tag, $string)
{
    global $test_filter;

    if (!isset($test_filter[$tag])) return $string;

    uksort($test_filter[$tag], "strnatcasecmp");

    foreach ($test_filter[$tag] as $priority => $functions)
    {
        if (is_null($functions)) continue;

        foreach($functions as $function)
        {
            $string = call_user_func_array($function, array($string));
        }
    }
    return $string;
}

function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1)
{
    add_filter($tag, $function_to_add, $priority, $accepted_args);
}

function do_action($tag, $arg = '') {
    global $test_filter;

    if (!isset($test_filter[$tag])) return;

    uksort($test_filter[$tag], "strnatcasecmp");

    foreach ($test_filter[$tag] as $priority => $functions)
    {
        if (is_null($functions)) continue;

        foreach($functions as $function)
        {
            call_user_func_array($function, array($arg));
        }
    }
}

function do_action_ref_array($tag, $args) {
    global $test_filter;

    if (!isset($test_filter[$tag])) return;

    uksort($test_filter[$tag], "strnatcasecmp");

    foreach ($test_filter[$tag] as $priority => $functions)
    {
        if (is_null($functions)) continue;

        foreach($functions as $function)
        {
            call_user_func_array($function, $args);
        }
    }
}

?>

