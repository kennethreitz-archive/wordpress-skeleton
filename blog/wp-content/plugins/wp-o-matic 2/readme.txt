=== WP-o-Matic ===
Contributors: Appletalk
Donate link: http://devthought.com/wp-o-matic-the-wordpress-rss-agreggator/#donate
Tags: autoblogging, feeds, rss, atom, aggregation, content, syndication, posts, post, writing, bot, admin
Requires at least: 2.2.1
Tested up to: 2.6
Stable tag: trunk

WP-o-Matic makes autoblogging a snap by automatically creating posts from the RSS/Atom feeds you choose, which are organized into campaigns. 

== Description ==

WP-o-Matic makes autoblogging a snap by automatically creating posts from the RSS/Atom feeds you choose, which are organized into campaigns. 

Supported features:

* Campaigs Feeds and all settings and options are now organized into campaigns for the perfect organization and comfort.
* Multiple feeds / categories: it’s possible to add as many feeds as you want, and add them to as many categories as you want. It’s not obstrusive, so you can also write your own posts to any of those categories.
* Every form of XML syndication supported. This includes RSS 0.91 and RSS 1.0 formats, the popular RSS 2.0 format, and the emerging Atom.
* Feed autodiscovery, which lets you add feeds without even knowing the exact URL. (Thanks Simplepie!)
* Unix cron and Wordpress cron jobs For maximum performance, you can make the RSS fetching process be called by a Unix cron job, or simply let Wordpress handle it.
* Comfortable interface. The admin is powered by a state of the art ajaxy interface that lets you handle your feeds with ease. Updated!
* Images caching for extreme performance and hotlinking bypassing.
* Words Rewriting. Regular expressions supported.
* Words Relinking. Define custom links for words you specify.
* Post templating. Define a global header and footer for the posts, or even for a specific feed. Add ads
* Campaigns import/export using OPML files. Easily import hundreds of feeds into new or existing campaigns! 

Upcoming features:

* Improved export (to include campaign options, with an extended OPML)
* Image thumbnailing
* Improved importing with drag and drop
* Advanced templates
* Advanced filters

== Installation ==

1. Extract wpomatic.zip in the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Follow the on-screen instructions.s

== Frequently Asked Questions ==

= WP-o-Matic is not fetching plugins automatically =

Make sure the cron is set up properly. Enable logging and check for 'Running cron' entries.

= I get an error 'Maximum execution time of 30 seconds exceeded' when fetching =

WP-o-Matic attempts to override the maximum execution time setting by its own. In some setups, however, this is not allowed, which causes WP-o-Matic to exit unexpectedly when fetching large feeds. You can try changing this setting via the .htaccess file or by contacting your hosting provider.

= How do I include a link back to the original article? = 

Use the post template feature -located in campaign settings-.
For example: {content} <p><a href={permalink}>Link to the original site</a></p>

= Why isn’t the full article showing? = 

Because the feed you're using is only syndicating excerpts, not full content.

= Why does the cron task run every twenty minutes and not an hour ? =

This is a limitation that will soon go away. This, however, does not create extra overhead, and you feeds will be fetched in the timeframe you specify. We just make some extra unnecessary queries to the db. 

= What's the benefit of campaigns ? =

Campaigns give you the ability to group feeds and select common options for them. So, if you ever decide to change a setting, all the feeds in that logical group will be affected. 

The best way to organize your feeds is up to you.

== Screenshots ==

1. Dashboard
2. Campaigns list
3. Add campaign main tab
4. Add campaign feeds tab
5. Add campaign categories tab
6. Add campaign rewrite tab
7. Add campaign options tab
8. Campaign tools
9. Campaign import