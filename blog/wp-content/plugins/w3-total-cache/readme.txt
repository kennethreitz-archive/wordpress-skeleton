=== Plugin Name ===
Contributors: fredericktownes
Tags: user experience, cache, caching, page cache, css cache, js cache, db cache, disk cache, disk caching, database cache, http compression, gzip, deflate, minify, CDN, content delivery network, media library, performance, speed, multiple hosts, CSS, merge, combine, unobtrusive javascript, compress, optimize, optimizer, JavaScript, JS, cascading style sheet, plugin, yslow, YUI, google, google rank, google page speed, S3, CloudFront, AWS, Amazon Web Services, batcache, wp cache, wp super cache, w3 total cache, buddypress
Requires at least: 2.6
Tested up to: 2.9.1
Stable tag: 0.8.5.2

Improve the speed and user experience of your site. Add page caching, database caching, minify and content delivery network and more to WordPress.

== Description ==

The fastest and most complete WordPress performance optimization plugin. Trusted by many popular sites like: mashable.com, smashingmagazine.com, makeuseof.com, kiss925.com, lockergnome.com, tutsplus.com, johnchow.com, ilovetypography.com, webdesignerdepot.com, pearsonified.com, css-tricks.com, yoast.com and others &mdash; W3 Total Cache improves the user experience of your blog by improving your server performance, caching every aspect of your site, reducing the download time of your theme and providing transparent content delivery network (CDN) integration.

Benefits:

* At least 10x improvement in overall site performance (when fully configured: Grade A in [YSlow](http://developer.yahoo.com/yslow/) or significant [Google Page Speed](http://code.google.com/speed/page-speed/) improvements)
* Improves "[site performance](http://googlewebmastercentral.blogspot.com/2009/12/your-sites-performance-in-webmaster.html)" which [affect your site's rank](http://searchengineland.com/site-speed-googles-next-ranking-factor-29793) on Google.com
* "Instant" second page views (browser caching after first page view)
* Reduced page load time: increased visitor time on site (visitors view more pages)
* Optimized progressive render (pages appear to render immediately)
* Improved web server performance (sustain high traffic spikes)
* Up to 80% bandwidth savings via minify and HTTP compression of HTML, CSS, JavaScript and feeds

Features:

* Compatible with shared hosting, virtual private / dedicated servers and dedicated servers / clusters
* Transparent content delivery network (CDN) integration with Media Library, theme files and WordPress itself
* Caching of (minified and compressed) pages and posts in memory or on disk
* Caching of (minified and compressed) CSS and JavaScript in memory, on disk or on CDN
* Caching of feeds (site, categories, tags, comments) in memory or on disk
* Caching of search results pages (i.e. URIs with query string variables) in memory or on disk
* Caching of database objects in memory or disk
* Minification of posts and pages and feeds
* Minification (combine and remove comments / white space) of inline, embedded or 3rd party JavaScript (with automated updates)
* Minification (combine and remove comments / white space) of inline, embedded or 3rd party CSS (with automated updates)
* Browser caching of CSS, JavaScript and HTML using future expire headers and entity tags (ETag)
* JavaScript grouping by template (home page, post page etc) with embed location management
* Non-blocking JavaScript embedding
* Import post attachments directly into the Media Library (and CDN)

Improve the user experience for your readers without having to change WordPress, your theme, your plugins or how you produce your content.

== Frequently Asked Questions ==

= Why does speed matter? =

Speed is among the most significant success factors web sites face. In fact, your blog's speed directly affects your income (revenue) &mdash; it's a fact. Some high traffic sites conducted research and uncovered the following:

* Google.com: **+500 ms** (speed decrease) -> **-20% traffic loss** [[1](http://home.blarg.net/~glinden/StanfordDataMining.2006-11-29.ppt)]
* Yahoo.com: **+400 ms** (speed decrease) -> **-5-9% full-page traffic loss** (visitor left before the page finished loading) [[2](http://www.slideshare.net/stoyan/yslow-20-presentation)]
* Amazon.com: **+100 ms** (speed decrease) -> **-1% sales loss** [[1](http://home.blarg.net/~glinden/StanfordDataMining.2006-11-29.ppt)]

A thousandth of a second is not a long time, yet the impact is quite significant. Even if you're not a large company (or just hope to become one), a loss is still a loss. However, there is a solution to this problem, take advantage.

In the near future, search engines will weigh the performance of web sites as factors in their ranking algorithms. Search engine's have the goal of providing users with the best user experience, so speed is definitely a concern.

= Why is W3 Total Cache better than other cache plugins? =

**It's complete.** Most of the popular cache plugins available do a great job and serve their purpose very well. Our plugin remedies numerous performance reducing aspects of any web site going far beyond merely reducing CPU usage (load) and bandwidth consumption for HTML pages alone. Equally important, the plugin requires no theme modifications, modifications to your .htaccess (mod_rewrite rules) or programming compromises to reap the benefits. Most importantly, it's the only plugin designed to optimize all practical hosting environments. The options are many and setup is easy.

= I've never heard of any of this stuff; my blog is fine, no one complains about the speed. Why should I install this? =

Rarely do readers take the time to complain. They typically just stop browsing earlier than you'd prefer and may not return altogether. This is the only plugin specifically designed to make sure that all aspects of your site are as fast as possible. Google is placing more emphasis on the [speed of a site as a factor in rankings](http://searchengineland.com/site-speed-googles-next-ranking-factor-29793); this plugin helps with that too.

It's in every web site owner's best interest is to make sure that the performance of your blog is not hindering its success.

= And how many years of university do I need to use this thing? =

-4 - That's right; a youngster in junior high school can get started with this plugin. Seriously, if you did your own WordPress install or have ever installed a plugin before you're in good shape. If you need help, let us know or perhaps we'll make some videos or the like.

= Which WordPress versions are supported? =

To use all features in the suite, a minimum of version WordPress 2.6 with PHP 5 is required. Earlier versions will benefit from our Media Library Importer to get them back on the upgrade path and into a CDN of their choosing.

= What about comments? Does the plugin slow down the rate at which comments appear? =

On the contrary, as with any other action a user can perform on a site, faster performance will encourage more of it. The cache is so quickly rebuilt in memory that it's no trouble to show visitors the most current version of a post that's experiencing Digg, Slashdot, Drudge Report, Yahoo Buzz or Twitter effect.

= Will the plugin interfere with other plugins or widgets? =

No, on the contrary if you use the minify settings you will improve their performance by several times.

= Does this plugin work with WordPress MU? =

Indeed it does.

= Does this plugin work with BuddyPress (bbPress)? =

Yes.

= Will this plugin speed up WP Admin? =

Yes, indirectly - if you have a lot of bloggers working with you, you will find that it feels like you have a server dedicated only to WP Admin once this plugin is enabled; the result, increased productivity.

= Which web servers do you support? =

We are aware of no incompatibilities with [apache](http://httpd.apache.org/) 1.3+, [IIS](http://www.iis.net/) 5+ or [litespeed](http://litespeedtech.com/products/webserver/overview/) 4.0.2+. If there's a web server you feel we should be actively testing (e.g. [lighttpd](http://www.lighttpd.net/)), we're [interested in hearing](http://www.w3-edge.com/contact/).

= Is this plugin server cluster and load balancer friendly? =

Yes, built from the ground up with scale and current hosting paradigms in mind.

= What is the purpose of the "Media Library Import" tool and how do I use it? =

The media library import tool is for old or "messy" WordPress installations that have attachments (images etc in posts or pages) scattered about the web server or "hot linked" to 3rd party sites instead of properly using the media library.

The tool will scan your posts and pages for the cases above and copy them to your media library, update your posts to use the link addresses and produce a .htaccess file containing the list of of permanent redirects, so search engines can find the files in their new location.

You should backup your database before performing this operation.

= How do I find the JS and CSS to optimize (minify) them with this plugin? =

View your page source in your browser and search for any `<style>`, `<link>` or `<script>` tags that contain external CSS or JS files and one by one add them to the minify settings page. Do not include any CSS in conditional statements (unless you know what you are doing) like:

`<!--[if lte IE 8]><link rel="stylesheet" type="text/css" href="/wp-content/themes/default/lte.css" media="screen,projection" /><![endif]-->`

The plugin will combine, minify, HTTP compress and check for updates to these files automatically from now on. If you have any CSS or JS that are inline consider making them external files so that you can use them with minify.

= Who do you recommend as a CDN (Content Delivery Network) provider? =

That depends on how you use your blog and where most of your readers read your blog (regionally). Here's a short list:

* [MaxCDN](http://bit.ly/acaXXt)
* [Rackspace Cloud Files](http://bit.ly/9hpX8T)
* [Cotendo](http://bit.ly/bnVs0a)
* [VPS NET](http://bit.ly/d5hfFt)
* [Amazon Cloudfront](http://bit.ly/ao1sGt)
* [EdgeCast](http://bit.ly/bIjSWC)
* [Akamai](http://bit.ly/a5GBLV)
* [Limelight Networks](http://bit.ly/aCW04j)
* [SimpleCDN](http://bit.ly/93EAKK)

= I don't understand what a CDN has to do with caching, that's completely different, no? =

Technically no, a CDN is a high performance cache that stores static assets (your theme files, media library etc) in various locations throughout the world in order to provide low latency access to them by readers in those regions. So indeed a CDN is a high performance cache, many of which actually store your frequently requested assets in memory for fastest possible response.

= What if I don't want to work with a CDN right now, is there any other use for this feature? =

Yes! You can take advantage of the [pipelining](http://www.mozilla.org/projects/netlib/http/pipelining-faq.html) support in some browsers by creating a sub-domain for the static content for your site. So you could select the "Origin Push / Self-hosted" method of the General Settings tab. Create static.domain.com on your server (and update your DNS zone) and then specify the FTP details for it in the plugin configuration panel and you're done. If you disable the scripting options on your server you'll find that your server will actually respond slightly faster from that sub-domain because it's just sending files and not processing them.

= How do I configure Amazon Simple Storage Service (Amazon S3) or Amazon CloudFront as my CDN? =

First [create an S3 account](http://aws.amazon.com/). Next, you need to obtain your "Access key" and "Secret key" from the "Access Credentials" section of the "[Security Credentials](http://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key)" page of "My Account." Make sure the status is "active." Next, make sure that "Amazon Simple Storage Service (Amazon S3)" is the selected type of CDN on the General Settings tab (if not change the setting and save the changes). Now on the CDN Settings tab enter your "Access key," "Secret key" and create a bucket. Click the "Test S3 Upload" button and make sure that the test is successful, if not check your settings and try again.

Save your settings. Make sure that you export your media library, upload your wp-includes (WordPress core files) theme files, and custom files if you wish to host each of those with AWS. Unless you wish to use CloudFront, you're almost done, skip to the next paragraph if you're using CloudFront. Just go to the General Settings tab and click the checkbox and save the settings to enable CDN functionality and empty the cache for the changes to take effect.

To use CloudFront, perform all of the steps above, except select the "Amazon CloudFront" CDN type in the CDN section of the General Settings tab. Proceed to the [AWS Management Console](https://console.aws.amazon.com/cloudfront/) and create a new distribution: select the S3 Bucket you created earlier as the "Origin," enter a [CNAME](http://docs.amazonwebservices.com/AmazonCloudFront/latest/DeveloperGuide/index.html?CNAMEs.html) if you wish to add one to your DNS Zone. Make sure that "Distribution Status" is enabled and "State" is deployed. Now on CDN Settings tab of the plugin, copy the subdomain found in the AWS Management Console and enter the CNAME used for the distribution in the "CNAME" field.

Now go to the General Settings tab and click the checkbox and save the settings to enable CDN functionality and empty the cache for the changes to take effect.

= How do I use an Origin Pull (Mirror) CDN? =
Login to your CDN providers control panel or account management area. Following any set up steps they provide, create a new "pull zone" or "bucket" for your site's domain name. If there's a set up wizard or any troubleshooting tips your provider offers, be sure to review them. In the CDN Settings tab of the plugin, enter the hostname your CDN provider provided in the "replace site's hostname with" field. You should always do a quick check by opening a test file from the CDN hostname, e.g. http://cdn.domain.com/favicon.ico. Troubleshoot with yoru CDN provider until this test is successful.

Now go to the General Settings tab and click the checkbox and save the settings to enable CDN functionality and empty the cache for the changes to take effect.

= My YSlow score is low because it doesn't recognize my CDN, what can I do? =
Rule 2 says to use a content delivery network (CDN). The score for this rule is computed by checking the hostname of each component against the list of known CDNs. Unfortunately, the list of "known CDNs" are the ones used by Yahoo!. Most likely these are not relevant to your web site, except for potentially yui.yahooapis.com. If you want an accurate score for your web site, you can add your CDN hostnames to YSlow using Firefox's preferences. Here are the steps to follow:

* Go to about:config in Firefox. You'll see the current list of preferences.
* Right-click in the window and choose New and String to create a new string preference.
* Enter extensions.yslow.cdnHostnames for the preference name.
* For the string value, enter the hostname of your CDN, for example, mycdn.com. Do not use quotes. If you have multiple CDN hostnames, separate them with commas.

If you specify CDN hostnames in your preferences, they'll be shown under the details for Rule 2 in the Performance view.

= What is the purpose of the "modify attachment URLs" button? =

If the domain name of your site has changed, this tool is useful in updating your posts and pages to use the current addresses. For example, if your site used to be www.domain.com, and you decided to change it to domain.com, the result would either be many "broken" images or many unncessary redirects (which slow down the visitor's browsing experience). You can use this tool to correct this and similar cases. Correcting the URLs of your images also allows the plugin to do a better job of determining which images are actually hosted with the CDN.

As always, it never hurts to back up your database first.

= Is this plugin comptatible with TDO Mini Forms? =

Captcha and recaptcha will work fine, however you will need to prevent any pages with forms from being cached. Add the page's URI to the "Never cache the following pages" box on the Page Cache Settings tab.

= Is this plugin comptatible with GD Star Rating? =

Yes. Follow these steps:

1. Enable dynamic loading of ratings by checking GD Star Rating -> Settings -> Features "Cache support option"
1. If Database cache enabled in W3 Total Cache add `wp_gdsr` to "Ignored query stems" field in the Database Cache settings tab, otherwise ratings will not updated after voting
1. Empty all caches

= I see garbage characters instead of the normal web site, what's going on here? =

If a theme or it's files use the call `php_flush()` or function `flush()` that will interfere with the plugins normal operation; making the plugin send cached files before essential operations have finished. The `flush()` call is no longer necessary and should be removed.

= How do I cache only the home page? =

Add `/.+` to page cache "Never cache the following pages" option on the page cache settings tab.

= I'm getting blank pages or 500 error codes when trying to upgrade on WordPress MU =

First, make sure the plugin is not active (disabled) site-wide. Then make sure it's deactivated site-wide. Now you should be able to successful upgrade without breaking your site.

= This is too good to be true, how can I test the results? =
You will be able to see it instantly on each page load, but for tangible metrics, consider the following tools:

* [Mozilla Firefox](http://www.mozilla.com/firefox/) + [Firebug](http://getfirebug.com/) + [Yahoo! YSlow](http://developer.yahoo.com/yslow/)
* [Mozilla Firefox](http://www.mozilla.com/firefox/) + [Firebug](http://getfirebug.com/) + [Google Page Speed](http://code.google.com/speed/page-speed/)
* [Mozilla Firefox](http://www.mozilla.com/firefox/) + [Firebug](http://getfirebug.com/) + [Hammerhead](http://stevesouders.com/hammerhead/)
* [Google Chrome](http://www.google.com/chrome) + [Google Speed Tracer](http://code.google.com/webtoolkit/speedtracer/)
* [Pingdom](http://tools.pingdom.com/)
* [WebPagetest](http://www.webpagetest.org/test)
* [Gomez Instant Test Pro](http://www.gomez.com/instant-test-pro/)
* [Resource Expert Droid](http://redbot.org/)
* [Web Caching Tests](http://www.procata.com/cachetest/)
* [Port80 Compression Check (minify requires MSIE6 support to be enabled)](http://www.port80software.com/tools/compresscheck.asp)
* [A simple online web page compression / deflate / gzip test tool](http://www.gidnetwork.com/tools/gzip-test.php)

= I don't have time to deal with this, but I know I need it. Will you help me? =

Yes! Please [reach out to us](http://www.w3-edge.com/contact/) and we'll get you acclimated so you can "set it and forget it."

Install the plugin to read the full FAQ.

== Other Notes ==

= Who do I thank for all of this? =

It's quite difficult to recall all of the innovators that have shared their thoughts, code and experiences in the blogosphere over the years, but here are some names to get you started:

* [Steve Souders](http://stevesouders.com/)
* [Steve Clay](http://mrclay.org/)
* [Ryan Grove](http://wonko.com/)
* [Nicholas Zakas](http://www.nczonline.net/blog/2009/06/23/loading-javascript-without-blocking/)
* [Ryan Dean](http://rtdean.livejournal.com/)
* [Andrei Zmievski](http://gravitonic.com/)
* George Schlossnagle
* Daniel Cowgill
* [Rasmus Lerdorf](http://toys.lerdorf.com/)
* [Gopal Vijayaraghavan](http://t3.dotgnu.info/)
* [Bart Vanbraban](http://eaccelerator.net/)
* [mOo](http://xcache.lighttpd.net/)

Please reach out to all of these people and support their projects if you're so inclined.

== Installation ==

1. Deactivate and delete any other caching plugin you may be using. Make sure wp-content/ (temporarily) has 777 permissions before proceeding, e.g.: `# chmod 777 /var/www/vhosts/domain.com/httpdocs/wp-content/` using your web hosting control panel or your SSH account.
1. Login as an administrator to your WordPress Admin account. Using the "Add New" menu option under the "Plugins" section of the navigation, you can either search for: w3 total cache or if you've downloaded the plugin already, click the "Upload" link, find the .zip file you download and then click "Install Now". Or you can unzip and FTP upload the plugin to your plugins directory (wp-content/plugins/). In either case, when done wp-content/plugins/w3-total-cache/ should exist.
1. Locate and activate the plugin on the "Plugins" page. Page caching will **automatically be running** in basic mode. Set the permissions of wp-content back to 755, e.g.: `# chmod 755 /var/www/vhosts/domain.com/httpdocs/wp-content/`.
1. Now click the "Settings" link to proceed to the "General Settings" tab and select your caching methods for page, database and minify. In most cases, "disk enhanced" mode for page cache, "disk" mode for minify and "disk" mode for database caching are "good" settings.
1. *Optional:* If you already have a content delivery network (CDN) provider, proceed to the "CDN Settings" tab and populate the fields and set your preferences. If you do not use the Media Library, you will need to import your images etc into the default locations. Use the Media Library Import Tool on the CDN Setting tab to perform this task. If you do not have a CDN provider, you can still improve your site's performance using the "Self-hosted" method. On your own server, create a subdomain and matching DNS Zone record; e.g. static.domain.com and configure FTP options on the CDN tab accordingly. Be sure to FTP upload the appropriate files, using the available upload buttons.
1. *Optional:* On the "Minify Settings" tab all of the recommended settings are preset. View your site's HTML source and search for .css and .js files and then specify any CSS and JS files in the respective section. In the case of JS files you can (optionally) specify the type and location of the embedding using the drop down menu. See the plugin's FAQ for more information on usage.
1. *Optional:* On the "Database Cache Settings" tab the recommended settings are preset. If you're using a shared hosting account use the "disk" method with caution; in either of these cases the response time of the disk may not be fast enough, so this option is disabled by default.
1. You're done! Get back to blogging!

== Changelog ==

= 0.8.5.2 =
* Added support for Bad Behavior plugin
* Added support for eAccelerator and XCache opcode caches
* Added support for rewriting href attribute of anchors for images that use the CDN
* Added deflate http compression support to minify
* Added setting of file modification time after FTP upload
* Added check of file modification time and file size before FTP upload
* Added check of file hash before uploading to Amazon S3
* Added option to force replacement of uploaded attachments on CDN
* Added option to toggle feed minification
* Added combine only option for CSS minification to overcome invalid CSS files
* Database caching now disabled by default to improve compatibility with some shared hosting environments
* Default page caching method now disk enhanced
* Improved HTTP Compression inter-operability
* Improved compatibility with Multi Site Manager
* Improved apache compatibility
* Improved .htaccess directives
* Improved header management for posts / pages
* Improved notifications
* Improved minify handling of external files
* Improved minify memory limit to avoid blank pages when document size is large
* Improved minify reliability by reducing comment removal options
* Improved Media Library Import versatility
* Improved reliability of plugin activation in WordPress MU
* Improved security handling for some operations
* Improved reliability of handling file names containing spaces in CDN functionality
* Improved non-blocking embedding reliability
* Improved memcached detection by supporting only PECL memcache
* Fixed bug with disk enhanced method of page cache in WordPress MU
* Fixed false match of page cache files in disk enhanced method of page cache
* Fixed a bug with cron anomalies caused by caching of wp_options table
* Fixed missing trailing slash issue when using disk enhanced page caching
* Fixed auto-embed bug CSS and JS when already manually embedded

= 0.8.5.1 =
* Added option to CDN Settings to skip specified directories
* Added option to allow for full control of HTTP compression options for page cache (some WordPress installations have issues with deflate)
* Added sql_calc_found_rows to default auto reject SQL list
* Added more notification cases identified and configured
* Added new mobile user agents for Japanese market
* Page cache performance improvements for disk enhanced mode
* Improved FAQ and option descriptions
* Improved apache directives for minify headers
* Improved handling of redirects
* Improved name space to avoid issues with other plugins
* Improved handling of incomplete installations, caching runs with default options if custom settings file does not exist
* Fixed anomalies with memcached-client.php in some environments
* Fixed another interface bug with management of minify files
* Fixed minor bug with table column length for some MySQL versions
* Fixed minify bug with CRLF
* Fixed minify bug with handling of zlib compression enabled
* Fixed handling of pages with CDN Media Library import

= 0.8.5 =
* Added "enhanced" disk caching mode for page cache, a 160% performance improvement over basic mode
* Added disk caching as an option for Database Cache
* Added CDN support for Amazon S3 and CloudFront
* Added mobile user agent rejection and redirect fields to page cache for handling mobile user agents
* Added Submit Bug Report tab
* Added support for detection of custom templates for minify groups
* Added separate controls expiration time field for minify and page cache settings
* Added PHP4 Support Notification to handle fatal errors on activation
* Improved database caching by 45%
* Improved handling of cache-control HTML headers
* Improved handing of 3rd Party CSS file minification
* Improved media library import reliability
* Improved handling of `DOCUMENT_ROOT` on some servers
* Improved garbage collection routine
* Improved handling of `<pre>` and `<textarea>` minification
* Improved handling of regular expressions in custom file list in CDN settings
* Improved handling of media library attachments in RSS feeds
* Improved handing of subdomains for CDN settings
* Improved various notifications and error messages
* Improved optional .htaccess directives (located in /ini/_htaccess)
* Fixed bug with JS minifcation saving group settings
* Fixed bug with false positives for duplicate CSS or JS in minify settings
* Fixed bug causing settings to be lost on upgrade
* Fixed bug with attachment URI when CDN mode enabled
* Fixed small bug with FTP upload when CDN Method is Mirror (Origin Pull)
* Fixed bug with the URI for wlwmanfiest.xml when CDN enabled 
* Fixed bug with handling of HTTPS objects according to options
* Fixed bug with emptying disk cache under various obscure permutations
* Fixed bug with handling of obscure open_basedir restrictions
* Fixed various bugs with emptying cache under various obscure permutations
* Fixed bug with installations deeper than document root

= 0.8 =
* Added disk as method for page caching
* Added support for mirror (origin pull) content delivery networks
* Added options to specify minify group policies per template
* Added options for toggling inline CSS and JS minification to improve minify reliability
* Added option to update Media Library attachment hostnames (when migrating domains etc)
* Added "Empty Cache" buttons to respective tabs
* Added additional file download fallback methods for minify
* Improved cookie handling
* Improved header handling
* Improved reliability of Media Library import
* "Don't cache pages for logged in users" is now the default page cache setting
* Fixed minify bug with RSS feeds
* Fixed minify bug with rewriting of url() URI in CSS
* Addressed more page cache invalidity cases
* Addressed rare occurrence of PHP fatal errors when saving post or comments
* Addressed CSS bug on wp-login.php
* Addressed rare MySQL error when uploading attachments to Media Library
* Modified plugin file/directory structure
* Confirmed compatibility with varnish and squid

= 0.7.5.2 =
* Added warning dialog to minify tab about removal of query strings locally hosted object URIs
* Added empty (memcached) cache button to each tab
* Improved reliability of memcache flush
* Minified files now (optionally) upload automatically according to update interval (expiry time)
* Changed directory of minify working files to wp-content/w3tc-cache/
* Fixed bug with parsing memcached server strings
* Fixed bug with minify sometimes not creating files as it should
* Addressed WordPress MU site-wide activation/deactivation issues
* Provided memcache.ini directives updated to improve network throughput

= 0.7.5.1 =
* Added memcached test button for convenience
* Added option to concatenate any script to header or footer with non-blocking options for scripts that cannot be minified (e.g. obfuscated scripts)
* Added options to concatenate JS files only in header or footer (for use with obfuscated scripts)
* Improved notification handling
* Improved compatibility with suPHP
* Improved reliability of Media Library Export
* Fixed bug with database cache that caused comment counts to become out of date
* Fixed minor issue with URI with CDN functionality enabled
* Removed unnecessary minify options
* Minification error dialogs now disabled when JS or CSS minify settings disabled
* Normalized line endings with /n as per minify author's direction
* Resolved a bug in the minify library preventing proper permission notification messages

= 0.7.5 =
* Added handling for magic_quotes set to on
* Fixed issue with auto-download/upgrade and additional error checking
* Fixed a bug preventing minify working properly if either CSS or JS minification was disabled
* Improved handling of inline comments and JavaScript in HTML documents
* Improved handing of @import CSS embedding
* Addressed privilege control issue
* Resolved warnings thrown in various versions of WordPress
* Memcached engine logic modified to better support clustering and multiple memcached instances
* Eliminated false negatives in a number of gzip/deflate compression analysis tools
* Total plugin file size reduced

= 0.7 =
* Added minify support for URIs starting with /
* WordPress MU support bug fixes
* Minor CDN uploader fixes
* Minor error message improvements

= 0.6 =
* Added "Debug Mode" listing all settings and queries with statistics
* Improved error message notifications
* Improved cache stability for large objects
* FAQ and installation instructions corrections/adjustments
* Support for multiple wordpress installations added
* Resolved bug in minification of feeds

= 0.5 =
* Initial release