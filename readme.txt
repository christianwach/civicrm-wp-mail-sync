=== CiviCRM WordPress Mail Sync ===
Contributors: needle
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PZSKM8T5ZP3SC
Tags: civicrm, user, mailing, mail, newsletter, sync
Requires at least: 3.5
Tested up to: 3.9
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CiviCRM WordPress Mail Sync creates WordPress posts from CiviCRM Mailings for viewing email in browser. Regular users only have access to the mailings that they have (or should have) received.


== Description ==

The CiviCRM WordPress Mail Sync plugin creates WordPress posts from CiviCRM Mailings for viewing email in browser. Regular users only have access to the mailings that they have (or should have) received.

CiviCRM WordPress Mail Sync intercepts the setup of each CiviMail Mailing and creates a WordPress post (of a Custom Post Type defined by this plugin) from the content of the mailing. It then injects the permalink to the WordPress post into the foot of the message template. Each mail is therefore viewable within the default post template for your theme, though it can be overridden by using standard WordPress template hierarchy techniques. The plugin replaces the tokens in each email with the values for the person viewing it. Lastly, the plugin filters the WordPress archive query for the Mailing Custom Post Type and allows a logged in user to view all emails they have been sent.

This plugin has been developed using *WordPress 3.9* and *CiviCRM 4.4.5*. It requires the master branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in *CiviCRM* file.


== Installation ==

1. Extract the plugin archive 
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Changelog ==

= 0.1 =

Initial release
