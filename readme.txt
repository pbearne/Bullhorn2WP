=== Plugin Name ===
Contributors: pbearne
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=MZTZ5S8MGF75C&lc=CA&item_name=Wordpress%20Development%20%2f%20Paul%20Bearne&item_number=Bullhorn%20Plugin&currency_code=CAD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: bullhorn, Job Listing, CE upload, Resume upload, bullhorn staffing, recruitment crm, staffing, recruiting
Requires at least: 3.6
Tested up to: 4.4.1
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds Bullhorn job listing with CV uploader to a custom post types (Job Listings) for front-end display.
Posts synchronizing with Bullhorn.

== Description ==

This plugin is fork of the code for sale at http://bullhorntowordpress.com and includes the resume upload extension.
I was hired by a client to fix the plugin he had bought by the time I had fixed it was a new version with lots of updates and fixes to I have released to the WP.org so others can have the working version

Your need an account at http://www.bullhorn.com/industry/staffing-recruiting/ or http://www.bullhorn.com/products/recruitment-crm/ to use this plugin (tell them you came from this plugin)

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

== Upgrade Notice ==
If you are updating the version from http://bullhorntowordpress.com install this deactive the old version (both plugins) and then active this.
Test and when happy you can remove the old pluigns

== Screenshots ==
1. Options page
2. CV/resume upload form
3. Job list with CV/resume upload form via ShortCode

== Changelog ==
= 2.0 =
Merged CV upload.
Removed 3rd part file upload code and replaced with WP version.
Added support to CV upload to parse skill list
Added support to CV upload to link to a joblisting
Added translations to all strings I could find.
Added filter to allow setting override
Added option to select to use the CV upload form or just link to a page with an application form.
Added Microdata to the job detail pages to help with SEO ranking
Lots of code tidy and refracting to get the code close the WordPress Standards
Added support for multiple URL's to one Bullhorn Account


= 1.0 =
As forked


== Frequently asked questions ==

= Support =
If you need support please contact me for a quote. via sales@bearne.ca

= Pull requests =
I will look at all pull requests. Please submit them here https://github.com/pbearne/Bullhorn2WP

