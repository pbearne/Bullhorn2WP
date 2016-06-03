=== Bullhorn Staffing and Recruitment Job Listing and CV/Resume Uploader for WordPress ===
Contributors: pbearne
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=MZTZ5S8MGF75C&lc=CA&item_name=Wordpress%20Development%20%2f%20Paul%20Bearne&item_number=Bullhorn%20Plugin&currency_code=CAD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: Bullhorn, Job Listing, CV upload, Resume upload, Bullhorn Staffing, recruitment crm, staffing, recruiting
Requires at least: 3.6
Tested up to: 4.5.2
Stable tag: 2.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds job listings to your WordPress site from your Bullhorn account for front-end display, also providing a CV uploader.

== Description ==

This plugin is a fork of the code for sale at http://bullhorntowordpress.com and additionally incorporates a resume upload extension. It automatically synchronizes with Bullhorn.
I was hired by a client to fix the plugin he had bought. By the time I had fixed it, it was effectively a new version with lots of updates and fixes. So I have released my version to WordPress.org so that others can have access to these.

Your need an account at http://www.bullhorn.com/industry/staffing-recruiting/ or http://www.bullhorn.com/products/recruitment-crm/ to use this plugin (please tell them you came from this plugin!)

This plugin adds Bullhorn jobs to a custom post (Job Listings) for front-end display.
New and deleted jobs are synchronized every hour with Bullhorn. This is a 'pull' process only - any job created locally is not pushed to Bullhorn!
Any theme developed on top of this plugin should have archive-job-listing.php and single-job-listing.php template files if a special layout is needed.

There are also shortcodes that can be used in sidebar widgets, other pages, etc. 
Example usages:
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
columns=X to the shortcode on site. Replace X with the desired number of columns.

The following shortcode generates a search form to search job listings:
[bullhorn_search]

This shortcode will display the resume upload form:
[bullhorn_cv_form]

There are lots of hooks and filters but I haven't documented them all yet so you will have to read the code

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `bh-staffing-job-listing-and-cv-upload-for-wp` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress.

You need to ask Bullhorn support to activate the API for your URL - this is not turned on by default.

You will need to connect to Bullhorn on the options page with your Client API Key.

Enter your Bullhorn client ID and key, then save them.

Now try to connect to Bullhorn - this may fail!

If you received an error "Invalid Client Id" you need to recheck your Bullhorn Client ID.

If the error is "Invalid Redirect URI" then you need to have your website URL added to the allowed sites for your account by Bullhorn support. 

When the Client ID and your site URL have been added you will be asked to login (this may be a special account for just the API).

If the Client Secret is wrong the login will fail.

Once you connect to Bullhorn, run a manual sync to test that all is working. You will be able to see all your jobs with  'public' locations set 'true' in the Job Listings section in your WordPress site. 

The 'public' setting is under 'locations' and is not visible by default. There is an option in the settings to ignore this setting and pull 'private' locations as well should you wish.

Good luck! If you have suggestions to improve these instructions, please send them to me.





== Upgrade Notice ==
If you are updating the version from an http://bullhorntowordpress.com, install this plugin, deactivate the old version (both plugins) and then activate this.
Test and, when happy, you can remove the old plugins.

== Screenshots ==
1. Options page
2. CV/resume upload form
3. Job list with CV/resume upload form via ShortCode

== Changelog == 

= 2.4.0 =
Added retries to the CV upload to re-attempt CV parsing failures (a known Bullhorn issue - "Convert failed, resume mirror status: -1 - Rex has not been initialized").
Increased the timeout for CV parsing to 2 mins
Added error email option
Added lots of extra checks for bad data back to Bullhorn API
Enabled a copy of the CV to be inserted into the description as HTML.
Numerous other bug-fixs and extra filters.

= 2.3.0 =
Added option to disable auto syncing.
Added option to not filter the jobs by isPublic location setting.
Other small fixes.
More short code support.

= 2.2.2 =
Some fixes for PHP errors.
Added more content to job post meta.
Added option select which field to show in CV form.
More short code support.

= 2.2.0 =
Fixed an error in the options name that was breaking the CV upload redirect to the thank you page.
Fixed calls to non static functions.
Added shortcodes for "b2wp_resume_form", "b2wp_application", "b2wp_shortapp" for compatibility.

= 2.1.4 =
Fixed typo.

= 2.1.2 =
Improved the messages on the settings page.
Protected the country code.

= 2.1.1 =
Fixed a bug when syncing.


= 2.1 =
Fixed the country fetch.
Handle running the plugin without being linked to bullhorn.

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
