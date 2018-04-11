# Funbotic
Contributors: Alexander LaBrie

Link: https://www.funbotic.com

Tags: proprietary

Requires at least: 3.0.1

Tested up to: 3.4

Stable tag: 4.3

License: GPLv2 or later

License URI: http://www.gnu.org/licenses/gpl-2.0.html

# Description
This is a custom plugin for WordPress, providing a variety of functionality for the Funbotic website.

Current functionality:
- Logged In/Logged Out Menu Change
- Conditional Shortcodes

See each section below for a description of the functionality provided by different elements of this plugin.

## Functionality: Logged In/Logged Out Menu Change
All credit to: http://www.wpbeginner.com/wp-themes/how-to-show-different-menus-to-logged-in-users-in-wordpress/

This allows one menu to be displayed to users who are logged into the site, and another to be displayed to users who are logged out of the site.  The menus must have the following names:
- logged-in
- logged-out

## Functionality: Conditional Shortcodes
Originally developed by geomagas.  https://wordpress.org/plugins/if-shortcode/

This plugin provides an "if" shortcode to conditionally render content. The syntax is the following:

	[if condition1 condition2=false condition3]{content}[/if]

Conditions are passed as attribute names. Multiple conditions evaluate to the result of ORing all of them.  In other words, if at least one condition evaluates to the desired boolean result, {content} is rendered, otherwise it is discarded.  Attribute values determine if we want the condition to be true or false. A value of '0', 'false', '' (the empty string), 'null' or 'no' means we expect the condition to be false.  Anything else, including the absense of a value, is interpreted as true.

For example, suppose that we want to include a sentence in a post, but only for anonymous visitors:

	[if is_user_logged_in=no]The Sentence.[/if]
	
It also provides an [else] shortcode and an [eitherway] one for use inside [if] blocks.  [else] will render its content if the condition evaluates to false, and [eitherway] will render its content regardless of the evaluation result.  When used outside an [if] block, these shortcodes behave as if the whole content is surrounded by an [if] shortcode whose condition evaluates to true.  In other words, an [else] shortcode would not render any content, while a [eitherway] one would.  You can use as many of these shortcodes as you like in a single [if] block, which gives you the ability to do things like:

	- Am I logged in?
	[if is_user_logged_in]- Yes you are.
	[else]- No you are not.
	[/else][eitherway]- I'm sorry, what?
	[/eitherway]- I said YOU A-R-E LOGGED IN!!!
	[else]- YOU ARE NOT LOGGED IN!!! What's the matter with you?[/else][/if]

A multitude of conditions are supported out-of-the-box.  The following evaluate to the result of the corresponding WordPress Conditional Tag, using the no-parameter syntax:

	comments_open
	has_tag
	has_term
	in_category
	is_404
	is_admin
	is_archive
	is_attachment
	is_author
	is_category
	is_child_theme
	is_comments_popup
	is_customize_preview
	is_date
	is_day
	is_feed
	is_front_page
	is_home
	is_month
	is_multi_author
	is_multisite
	is_main_site
	is_page
	is_page_template
	is_paged
	is_preview
	is_rtl
	is_search
	is_single
	is_singular
	is_sticky
	is_super_admin
	is_tag
	is_tax
	is_time
	is_trackback
	is_year
	pings_open

And the following conditions have been added as custom conditions:

	is_user_role_subscriber - Checks if the currently logged in user has the role "Subscriber", from the LearnDash plugin.

	is_user_role_group_leader - Checks if the currently logged in user has the role "Group Leader", from the LearnDash plugin.

	is_user_role_customer - Checks if the currently logged in user has the role "Customer", from the WooCommerce plugin.

For example, the evaluation of the is_page condition is equivalent to calling is_page() with no parameter.

The functionality of the plugin can be extended by other plugins, by means of adding custom conditions through filters.  To add a custom condition, a filter hook must be defined in the following manner:

	add_filter( $if_shortcode_filter_prefix . 'my_condition', 'my_condition_evaluator' );

	function my_condition_evaluator( $value ) {
		$evaluate = .... /* add your evaluation code here */
		return $evaluate;
	}
	
A big thanks to M Miller for the `normalize_empty_atts()` function found here: http://wordpress.stackexchange.com/a/123073/39275


# Installation

1. Upload `funbotic.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

# Frequently Asked Questions

Hopefully nothing yet...

# Screenshots

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

# Changelog

## 1.0.1
- Data saving functionality for funbotic-media-fields.php fully implemented.

## 1.0
- Initial menu functionality.
- Initial conditional shortcode functionality.