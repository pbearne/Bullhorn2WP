=== Plugin Name ===
Contributors: andrewryno
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=MZTZ5S8MGF75C&lc=CA&item_name=Wordpress%20Development%20%2f%20Paul%20Bearne&item_number=Bullhorn%20Plugin&currency_code=CAD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: bullhorn, Job Listing, CE upload, Resume upload
Requires at least: 3.6
Tested up to: 4.4.1
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds Bullhorn jobs to a custom post types (Job Listings) for front-end display.
Posts are auto-created by synchronizing with Bullhorn auto-deleted if not public or active on Bullhorn.

== Description ==

This plugin is fork of the code for sale at http://bullhorntowordpress.com and includes the resume upload


This plugin adds Bullhorn jobs to a custom post types (Job Listings) for front-end display. Posts are auto-created by synchronizing with Bullhorn auto-deleted if not public or active on Bullhorn. There is no way to manage the Bullhorn jobs here, the admin menu for Job Listings should be used for viewing only. Any theme developed on top of this plugin should have archive-job-listing.php and single-job-listing.php template files for special layout.

There is also a shortcodes that can be used in sidebar widgets, other pages, etc. Example usages:

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

This a shortcode to generates a search form to search job listings:

[bullhorn_search]

This shortcode will display the resume upload form

[bullhorn_cv_form]

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `bullhorn` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

You will now be connected with Bullhorn.

== Screenshots ==

== Changelog ==
= 2.0 =
Merged CV upload.
Removed 3rd part file upload code and replaced with WP version.
Added support to CV upload to to paurse skill list and to link to a joblisting
Added translations to all strings I could find.
Added filter to allow seting over right
Added option to select to use the CV upload form or just link to a page with an application form.
Add Microdata to the job detail pages to help with SEO ranking
Lots of code tidy and refacting to get the coding standard to WordPress


= 1.0 =
as forked


== Frequently asked questions ==

= Support =
If you need support please contact me for a quote

= Pull requests =
I will look at all pull requests.
http

