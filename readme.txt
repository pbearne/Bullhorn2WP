=== Plugin Name ===
Contributors: pbearne
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=MZTZ5S8MGF75C&lc=CA&item_name=Wordpress%20Development%20%2f%20Paul%20Bearne&item_number=Bullhorn%20Plugin&currency_code=CAD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: Bullhorn, Job Listing, CV upload, Resume upload, Bullhorn Staffing, recruitment crm, staffing, recruiting
Requires at least: 3.6
Tested up to: 4.4.1
Stable tag: 2.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds job listings to your WordPress site from your Bullhorn account for front-end display, also providing a CV uploader.
The plugin automatically synchronizes with Bullhorn.

== Description ==

This plugin is a fork of the code for sale at http://bullhorntowordpress.com and additionally incorporates a resume upload extension.
I was hired by a client to fix the plugin he had bought. By the time I had fixed it, it was effectively a new version with lots of updates and fixes. So I have released my version to WordPress.org so that others can have access to these.

Your need an account at http://www.bullhorn.com/industry/staffing-recruiting/ or http://www.bullhorn.com/products/recruitment-crm/ to use this plugin (please tell them you came from this plugin!)

The plugin adds Bullhorn jobs to a custom post types (Job Listings) for front-end display.
Posts are auto-created by synchronizing with Bullhorn and are auto-deleted if not public or active on Bullhorn.
There is no way to manage the Bullhorn jobs here, the admin menu for Job Listings should be used for viewing only.
Any theme developed on top of this plugin should have archive-job-listing.php and single-job-listing.php template files for special layout.

There are also shortcodes that can be used in sidebar widgets, other pages, etc. Example usages:
Default usage:
[bullhorn]

Shows contract jobs in CA:
[bullhorn state="California" type="Contract"]

Shows 50 jobs with their posting date:
[bullhorn limit=50 show_date=true]

Only shows jobs that have the word "Intern" in the title:
[bullhorn title="Intern"]

You can use the following shortcodes to create a list of categories and states available (no options on either):
[bullhorn_categories]
[bullhorn_states]

To have the jobs display in two, three or four columns add:
columns=X to the shortcode on site. Replace X with the number of desired columns.

The following shortcode generates a search form to search job listings:
[bullhorn_search]

This shortcode will display the resume upload form:
[bullhorn_cv_form]

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `bullhorn` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

You will need to connect to Bullhorn on the options page with your Client API Key.

== Upgrade Notice ==
If you are updating the version from an http://bullhorntowordpress.com, install this plugin, deactive the old version (both plugins) and then active this.
Test and when happy you can remove the old pluigns

== Screenshots ==
1. Options page
2. CV/resume upload form
3. Job list with CV/resume upload form via ShortCode

== Changelog ==

== 2.1.4 ==
fixed typo

== 2.1.2 ==
improved the messages on the settings page
protect the country code

== 2.1.1 ==
Fixed a bug when syncing


= 2.1 =
fixed the country fetch
handle running the plugin without being linked to bullhorn

= 2.0 =
Merged CV upload.
Removed 3rd party file upload code and replaced with native WordPress version.
Added support to CV upload to parse skill list.
Added support to CV upload to link to a joblisting.
Added translations to all strings I could find.
Added filter to allow settings override.
Added option to select to use the CV upload form or just link to a page with an application form.
Added Microdata to the job detail pages to help with SEO ranking.
Lots of code tidy and refactoring to get the code close the WordPress Standards.
Added support for multiple URL's to one Bullhorn Account.


= 1.0 =
As forked


== Frequently asked questions ==

= Integration and Support =
This is code 'as is'. I'm not planning future development on this plugin unless contracted to do something specific.
If you need support please contact me for a quote. via hornbull.sales@bearne.ca

= Pull requests =
I will look at all pull requests. Please submit them here https://github.com/pbearne/Bullhorn2WP

