Marketing Press Bullhorn Integration Resume/Candidate Extension
===============================================================

- Contributors: michaelcurry / pbearne
- Donate link: http://bullhorntowordpress.com
- Tags: bullhorn
- Requires at least: 3.6
- Tested up to: 3.7
- Stable tag: 2.0
- License: GPLv2 or later
- License URI: http://www.gnu.org/licenses/gpl-2.0.html

### Description

This plugin is an extension for the Marketing Press Bullhorn Integration plugin that allows for resume uploads and candidate creation.

### Installation

This section describes how to install the plugin and get it working.

1. Upload the `bullhorn-extension` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create a page with the form below on it.
```
<form action="/api/bullhorn/resume" method="post" enctype="multipart/form-data">
    <input type="file" name="resume" id="fileToUpload">
    <input type="submit" value="Upload Resume" name="submit">
</form>
```
3. In the Marketing Press Bullhorn Integration plugin settings: set 'Form Page' to the page you created above.
4. In the Marketing Press Bullhorn Integration Resume/Candidate Extension plugin settings: set 'Thanks Page' to the page you want the previous form to redirect to.