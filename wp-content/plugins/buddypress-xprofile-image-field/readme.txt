=== BuddyPress XProfile Custom Image Field ===
Contributors: kalengi
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KWZPYPL527WVN
Tags: BuddyPress, XProfile, Extended Profile, User Profile, Custom Profile Field, Image Field, Field, Image
Requires at least: WordPress 3.2.1 with BuddyPress 1.5
Tested up to: WordPress 4.7.3 with BuddyPress 2.8.0
Stable tag: 2.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to add custom fields of type Image to user profile screens without having to write any additional code. No configuration required.

== Description ==

The BuddyPress Extended Profile add-on lacks support for Image type fields. The BuddyPress XProfile Custom Image Field plugin allows you to add fields of type Image to user profile screens without having to write any additional code. 

The images are stored by default into the /wp-content/uploads/profiles/[USER_ID] directory, where [USER_ID] is the ID of logged in user. You can override this default by hooking into the *bpxp_image_field_upload_dir* filter. 

The BuddyPress XProfile Custom Image Field plugin has a number of additional hooks that allow theme and plugin writers to modify its behavior.

== Installation ==

1. Upload `bp-xprofile-image-field` to the `/wp-content/plugins/` directory or use the automatic installation in the WordPress plugin panel.
2. Activate the plugin through the WordPress 'Plugins' menu


== Translations ==

* English - default
* Spanish translation by [Andrew Kurtis - WebHostingHub](http://www.webhostinghub.com/)


== Changelog ==

= 2.0.3 =
* Updated plugin description

= 2.0.2 =
* Fixed a bug that was blocking image display on the member list page

= 2.0.1 =
* Added ability to upload images on admin backend profile edit

= 2.0.0 =
* Added support for BuddyPress 2.3.3

= 1.4.0 =
* Added support for saving profile images during user sign-up

= 1.3.3 =
* Minor bug fix

= 1.3.2 =
* Added Spanish translation

= 1.3.1 =
* Added language l10n support

= 1.3.0 =
* Added support for BuddyPress 2.0.1

= 1.2.0 =
* Added capability to delete an image 
* Add front end image display 

= 1.1.0 =
* fixed to prevent crashing the profile edit page on sites not using BuddyPress Default Theme 

= 1.0.0 =
* Initial release
