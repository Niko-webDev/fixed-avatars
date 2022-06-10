=== Fixed Avatars ===
Contributors: nikowebtweaks
Tags: gravatar, avatar, wordpress
Requires at least: Unknown
Tested up to: 6.0
Requires PHP: Untested, use at least 7.4 to be safe
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


== Description ==

This bare bone plugin disables gravatar.com and allows you to host locally your own default avatar and let users pick their profile picture among a defined set of images.
It works with Buddypress version 10.2.0, with an ajax function to let members change their avatar from the front-end


== Image Files Locations ==

The default avatar must be uploaded to the "base" sub-directory. The first file in alphabetical order will be used as default.
The avatars to choose from must be uploaded to the "selection" sub-directory.


== Avatar Names ==

The file names will be displayed as avatar names. For example,
"my-avatar.png" or "my_avatar.jpg" will show as "my avatar" across the site.
The base avatar name will be followed by "(default)".


== File Extension and Separator Removal ==
The file extensions are defined by the "FA_IMG_EXT" constant. Current extensions are: '.png', '.jpg', '.jpeg', '.svg', '.avi', '.webp'.
The file separators are defined by the "FA_IMG_SEPARATOR" constant. Current separators are '-' and '_'.


== Image Files Sizes ==

There are no cropping utilities provided yet. You should use a 1:1 ratio.


== Changelog ==

1.1 – Jun 10, 2022

* Improve - Change from purely static to instantiated classes
* Fix - White space in img file names are url encoded

1.0 – Jun 02, 2022

* New - First version
