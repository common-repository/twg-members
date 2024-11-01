=== TheWebGears Members Plugin ===
Contributors: beatmasta (mrbeatmasta@gmail.com)
Plugin Name: TheWebGears Members
Tags: the, web, gears, members, users, user, member, directory, profile, thewebgears, module
Author URI: http://cs16.us
Author: beatmasta [Alex Vanyan], The Web Gears
Requires at least: 2.5.2
Tested up to: 3.1
Stable tag: 1.1

A plugin developed by The Web Gears (http://www.thewebgears.com) to have a fully functional members module under WordPress.

== Description ==

This plugin allows you to have members and profiles in WordPress independent from wp built-in users functionality.

The administrator is able to customize registration fields from the plugin settings panel __Settings__ *-&gt;* __TWG Members__.
Several types of fields can be added for the registration form.
User-defined values and messages can be added for different cases and elements.

Many features not implemented yet will be developed soon... New feature requests and bug tracks are accepted by e-mail &lt;mrbeatmasta@gmail.com&gt;.

# About 'The Web Gears'

[The Web Gears](http://www.thewebgears.com/) is a company/community of web-developers who are busy with developing new features and implement them in web environment to make the new web more comfortable and suitable for you.

== Installation ==

1. Upload the plugin to the "/wp-content/plugins/" folder
2. Activate the plugin through the "Plugins" menu in WordPress
3. Customize the registration fields, success/failure messages and element values through Settings -&gt; TWG Members settings panel

= Usage =

* Create a page for registration form and use __[twg-register-form]__ shortcode on it, or if you have a custom template for that page - use &lt;?php __twg_register_form()__; ?&gt; PHP code to get the registration form
* Create a page for login form and use __[twg-login-form]__ shortcode on it, or if you have a custom template for that page - use &lt;?php __twg_login_form()__; ?&gt; PHP code to get the login form
* Use __[twg-login-errors]__ on the login page in place where you want login errors to be displayed, or &lt;?php __twg_login_errors()__; ?&gt; code - for custom template
* You can use __twg_logged_in()__ PHP function to check whether there is a currently logged-in user (the function returns boolean *true* or *false*)
* Use __[twg-get-username]__ shortcode or __twg_get_username()__ function to get username of currently logged-in user (returns 'Guest' if user is not logged in)
* Place on the page of user own profile the shortcode __[twg-my-profile]__ or a function __twg_my_profile()__ (user can edit his profile from the page where you place this code)
* __[twg-profile-page]__ shortcode or __twg_profile_page()__ PHP function to be placed on users public profile page (user profile can be seen calling url like this [assuming that your page name/slug is 'users' http://my-wordpress-site.com/users/?uid=15 - where uid=15 is ID of the user whose profile is to be displayed)
* To log out the user you either redirect user to login page's /?logout location (like this: http://www.my-wordpress-site.com/my_login_page/?logout) or add a new page for logging out and put there __[twg-logout]__ short code or &lt;?php __twg_logout()__; ?&gt;.
* To display someones public profile by his user ID, you either need to proceed by the link from the user list or if you want to preview the public profile of the current user, you should get user ID by using [twg-my-id] short code or twg_my_id() PHP function and append it to the public profile page link like this: &lt;a href="http://www.my-wordpress-site.com/publicprofile?uid=__[twg-my-id]__"&gt;preview my profile&lt;/a&gt; or like this: &lt;a href="http://www.my-wordpress-site.com/publicprofile?uid=&lt;?php echo __twg_my_id()__; ?&gt;"&gt;preview my profile&lt;/a&gt;
* To get list of all members you can just call short code like this __[twg-member-list profile_page="my_public_profiles_page"]__ or a function like this twg_member_list('profile_page=my_public_profiles_page') from PHP. If you don't use profile_page parameter (both in short tag and in PHP function) it will take "*profiles*" as a default value. This option points to your profile page (it must be the slug of the page where you have used *[twg-profile-page]* or *twg_profile_page()* function). *profile_page* is an optional parameter, so if not passed no profile links will be displayed for individual users.

== Frequently Asked Questions ==

= In case of any questions/misunderstandings =

write an email to plugin development chief ;) [MrBeatMasta](mailto:mrbeatmasta@gmail.com)

== Screenshots ==

1. Registration fields configuration view
2. Login errors configuration view
3. Registration fields customization looks like this

== Changelog ==

= 1.1 =
* Added user logout separate function and shortcode
* Get own user ID functionality added
* Getting member list functionality added

= 1.0 =
Initial release of __TheWebGears Members__ plugin
