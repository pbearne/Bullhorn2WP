=== Plugin Name ===
Contributors: andrewryno
Donate link: http://bullhorntowordpress.com /
Tags: bullhorn
Requires at least: 3.6
Tested up to: 3.7
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds Bullhorn jobs to a custom post types (Job Listings) for front-end display. Posts are auto-created by synchronizing with Bullhorn auto-deleted if not public or active on Bullhorn.

== Description ==

This plugin adds Bullhorn jobs to a custom post types (Job Listings) for front-end display. Posts are auto-created by synchronizing with Bullhorn auto-deleted if not public or active on Bullhorn. There is no way to manage the Bullhorn jobs here, the admin menu for Job Listings should be used for viewing only. Any theme developed on top of this plugin should have archive-job-listing.php and single-job-listing.php template files for special layout.

There is also a shortcode that can be used in sidebar widgets, other pages, etc. Example usages:

Default usage:
[bullhorn]

Shows contract jobs in CA:
[bullhorn state="California" type="Contract"]

Shows 50 jobs with their posting date:
[bullhorn limit=50 show_date=true]

Only shows jobs that have the word "Intern" in the title:
[bullhorn title="Intern"]

There are two other shortcodes to a list of categories and states available (no options on either):

[bullhorn_categories]

[bullhorn_states]

To have the jobs display in two, three or four columns add:
columns=X to the shortcode on site. Replace X with the number of desired columns.

Lastly, there is a shortcode to generate a search form to search job listings:

[bullhorn_search]

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `bullhorn` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Since the redirect URI is optional in the API we omit it. Once you authorize with Bullhorn, it will redirect you to a URL (which may give you a network error). In the URL you should see a "?code=XXXXX" segment. Take that code and append it to your site URL so it looks like:

http://example.com/wp-admin/options-general.php?page=bullhorn&code=XXXX

You will now be connected with Bullhorn.
