<?php
/*
Plugin Name: W3 Total Cache
Description: The fastest and most complete WordPress performance plugin. Dramatically improve the speed and user experience of your blog by adding: page caching, database caching, minify, content delivery network (CDN) functionality and more...
Version: 0.8.5.2
Plugin URI: http://www.w3-edge.com/wordpress-plugins/w3-total-cache/
Author: Frederick Townes
Author URI: http://www.linkedin.com/in/w3edge
*/

/*  Copyright 2009 Frederick Townes <ftownes@w3-edge.com>
	Portions of this distribution are copyrighted by:
		Copyright (c) 2008 Ryan Grove <ryan@wonko.com>
		Copyright (c) 2008 Steve Clay <steve@mrclay.org>
	All rights reserved.

	W3 Total Cache is distributed under the GNU General Public License, Version 2,
	June 1991. Copyright (C) 1989, 1991 Free Software Foundation, Inc., 51 Franklin
	St, Fifth Floor, Boston, MA 02110, USA

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
	ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

if (! defined('W3TC_IN_MINIFY')) {
    /**
     * Require plugin configuration
     */
    require_once dirname(__FILE__) . '/inc/define.php';
    
    /**
     * Load plugins
     */
    w3_load_plugins();
    
    /**
     * Run plugin
     */
    require_once W3TC_DIR . '/lib/W3/Plugin/TotalCache.php';
    $w3_plugin_totalcache = & W3_Plugin_TotalCache::instance();
    $w3_plugin_totalcache->run();
}