=== Semantic Notifications ===
Contributors: dshanske
Tags: indieweb, interaction, posts, webmention
Stable tag: 0.0.1
Requires at least: 4.2
Tested up to: 4.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Notification Enhancements for Semantic Linkbacks

== Description == 

Makes improvements to the comment presentation of comments/linkbacks enhanced by Semantic Linkbacks
and supports Push Notifications with the Pushover for WordPress plugin.

== Screenshots ==


== Other Notes == 

Uses the pluggable functions of WordPress to replace the Comment Notification functions with ones
easier to adjust. The implementation is a patch currently under consideration for WordPress Core
under Ticket #33735(https://core.trac.wordpress.org/ticket/33735).

It then adds to the filter to make the display aware contextual.


== Changelog ==
	= Version 1.0.0 = 
		* Add push notifications support
		* Separate out email and push functions
		* Add linkback moderation whitelist previously in Extras
	= Version 0.01 = 
		* Initial Release
