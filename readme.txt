=== CiviCRM WordPress Mail Sync ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.me/interactivist
Tags: civicrm, user, mailing, mail, newsletter, sync
Requires at least: 4.9
Tested up to: 5.5
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CiviCRM WordPress Mail Sync creates WordPress Posts from CiviCRM Mailings for viewing email in a browser.



== Description ==

The CiviCRM WordPress Mail Sync plugin creates WordPress Posts from CiviCRM Mailings for viewing email in a browser. Regular users only have access to the mailings that they have (or should have) received.

CiviCRM WordPress Mail Sync intercepts the setup of each CiviMail Mailing and creates a WordPress Post (of a Custom Post Type defined by this plugin) from the content of the Mailing. It then injects the permalink to the WordPress Post into the foot of the message template. Each mail is therefore viewable within the default post template for your theme, though it can be overridden by using standard WordPress template hierarchy techniques. The plugin replaces the tokens in each email with the values for the person viewing it. Lastly, the plugin filters the WordPress archive query for the Mailing Custom Post Type and allows a logged in user to view all emails they have been sent.

### Requirements

This plugin requires a minimum of *WordPress 4.9* and *CiviCRM 4.7*.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-wp-mail-sync).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

This plugin requires a minimum of WordPress 4.9 and CiviCRM 4.7.



== Changelog ==

= 0.1 =

Initial release
