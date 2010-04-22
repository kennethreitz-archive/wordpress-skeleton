<?php

/**
 * Combine only minifier
 */
class Minify_CombineOnly
{
    /**
     * Minifies content
     * @param string $content
     * @param array $options
     * @return string
     */
    public static function minify($content, $options = array())
    {
        if (isset($options['currentDir'])) {
            require_once W3TC_LIB_MINIFY_DIR . '/Minify/CSS/UriRewriter.php';
            
            $content = Minify_CSS_UriRewriter::rewrite($content, $options['currentDir'], isset($options['docRoot']) ? $options['docRoot'] : $_SERVER['DOCUMENT_ROOT'], isset($options['symlinks']) ? $options['symlinks'] : array());
        } elseif (isset($options['prependRelativePath'])) {
            require_once W3TC_LIB_MINIFY_DIR . '/Minify/CSS/UriRewriter.php';
            
            $content = Minify_CSS_UriRewriter::prepend($content, $options['prependRelativePath']);
        }
        
        return $content;
    }
}
