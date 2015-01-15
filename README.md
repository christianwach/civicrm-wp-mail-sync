CiviCRM WordPress Mail Sync
===========================

The *CiviCRM WordPress Mail Sync* plugin creates WordPress posts from CiviCRM Mailings for viewing email in browser. Regular users only have access to the mailings that they have (or should have) received.

*CiviCRM WordPress Mail Sync* intercepts the setup of each CiviMail Mailing and creates a WordPress post (of a Custom Post Type defined by this plugin) from the content of the mailing. It then injects the permalink to the WordPress post into the foot of the message template. Each mail is therefore viewable within the default post template for your theme, though it can be overridden by using standard WordPress template hierarchy techniques. The plugin replaces the tokens in each email with the values for the person viewing it. Lastly, the plugin filters the WordPress archive query for the Mailing Custom Post Type and allows a logged in user to view all emails they have been sent.

#### Notes ####

This plugin has been developed using a minimum of *WordPress 3.9* and *CiviCRM 4.4.5*. For versions of *CiviCRM* prior to 4.6-alpha, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in *CiviCRM* file. Please refer to the each repo for further instructions.

#### Installation ####

There are two ways to install from GitHub:

###### ZIP Download ######

If you have downloaded *CiviCRM WordPress Mail Sync* as a ZIP file from the GitHub repository, do the following to install and activate the plugin and theme:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-wp-mail-sync`
2. Activate the plugin
3. You are done!

###### git clone ######

If you have cloned the code from GitHub, it is assumed that you know what you're doing.
