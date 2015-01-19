=== CiviCRM WordPress Mail Sync ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PZSKM8T5ZP3SC
Tags: civicrm, user, mailing, mail, newsletter, sync
Requires at least: 3.9
Tested up to: 4.1
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CiviCRM WordPress Mail Sync creates WordPress posts from CiviCRM Mailings for viewing email in browser.



== Description ==

The CiviCRM WordPress Mail Sync plugin creates WordPress posts from CiviCRM Mailings for viewing email in browser. Regular users only have access to the mailings that they have (or should have) received.

CiviCRM WordPress Mail Sync intercepts the setup of each CiviMail Mailing and creates a WordPress post (of a Custom Post Type defined by this plugin) from the content of the mailing. It then injects the permalink to the WordPress post into the foot of the message template. Each mail is therefore viewable within the default post template for your theme, though it can be overridden by using standard WordPress template hierarchy techniques. The plugin replaces the tokens in each email with the values for the person viewing it. Lastly, the plugin filters the WordPress archive query for the Mailing Custom Post Type and allows a logged in user to view all emails they have been sent.

### Requirements

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6-alpha1*. Please refer to the installation page for how to use this plugin with versions of CiviCRM prior to 4.6-alpha1.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-wp-mail-sync).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

This plugin requires a minimum of WordPress 3.9 and CiviCRM 4.6-alpha1. For versions of CiviCRM prior to 4.6-alpha1, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in CiviCRM file. Please refer to the each repo for further instructions.



== Changelog ==

= 0.1 =

Initial release
