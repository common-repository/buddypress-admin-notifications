=== BuddyPress Admin Notifications ===
Contributors: G.Breant
Donate link: http://dev.benoitgreant.be/2009/11/14/buddypress-admin-notifications
Tags: BuddyPress,notifications,emails,members
Requires at least: 2.8.5
Tested up to: 2.8.5
Stable tag: 0.1

This plugin adds a checkbox in the post/page admin (for the admins and editors) to tell members (notification & email) that an important post has been published.


== Description ==

This plugin adds a checkbox in the post/page admin (for the admins and editors) to tell members (notification & email) that an important post has been published : 

Just check the checbox at the bottom of a page/post when editing it.
(You won't be able to post a notification if the post/page is not published; password protected or if a notification has been sent before.)
(If a notification has been sent before; the checkbox will be disabled. You can eventually re-enable it by deleting the meta key bp_admin_notifications_sent.)

Careful : I don't have tested this plugin with a large amount of members.  I suspect the mail function could not support it.  Please tell me if you try !

== Installation ==

= WordPress 2.8.5 and above = 

* Copy the files in the plugin directory
* Activate sitewide

== Frequently Asked Questions ==

none yet !

== Screenshots ==

1. BuddyPress Admin Notifications Metabox

== Changelog ==

= 0.1 =
* First version
