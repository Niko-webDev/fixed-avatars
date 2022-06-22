=== Fixed Avatars ===
Contributors: nikowebtweaks
Tags: gravatar, avatar, wordpress
Requires at least: Unknown
Tested up to: 6.0
Requires PHP: Untested, use at least 7.4 to be safe
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


== Description ==

This bare bone plugin disables gravatar.com in WordPress and allows you to host locally your own default avatars and let users pick their profile picture among a defined set of images.
BuddyPress (up to version 10.2.0) and Directorist (up to version 7.2.2) registered members can change their avatars from the frontend.


== Image Files Locations ==

The default avatar must be uploaded to the "assets/images/default" sub-directory. The first file in alphabetical order will be used as default.
The selection of avatars must be uploaded to the "assets/images/default" sub-directory.


== Avatar Names ==

The file names will be displayed as avatar names. For example,
"my-avatar.png" or "my_avatar.jpg" will show as "my avatar" across the site.
The base avatar name will be followed by "(default)".


== Image Files Sizes ==

There are no cropping utilities provided. You should use a 1:1 ratio.


== Changelog ==

1.2 – Jun 21, 2022

* Add - Directorist integration
* Fix - All outputs are properly escaped

1.1 – Jun 10, 2022

* Improve - Change from purely static to instantiated classes
* Fix - White space in img file names are url encoded

1.0 – Jun 02, 2022

* New - First version
