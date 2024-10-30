=== Smart External Link Click Monitor [Link Log] ===
Contributors:  petersplugins
Tags: log, click, click counting, link analytics, tracking, visitor tracking, external links, classicpress
Requires at least: 4.0
Tested up to: 6.3
Stable tag: 5.0.2
Requires PHP: 5.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Find out where your visitors leave to by tracking clicks on external links

== Description ==

The Smart External Link Click Monitor Plugin allows you to track which external links your visitors click on.

Data are stored on your server. No external service needed.

== Retired Plugin ==

Development, maintenance and support of this plugin has been retired in october 2023. You can use this plugin as long as is works for you. 

There will be no more updates and I won't answer any support questions. Thanks for your understanding.

Feel free to fork this plugin.

== Usage ==

The Link Log Plugin changes all your links to external sites. For example `https://www.google.com` is changed to something like `https://www.example.com/?goto=HUESQ2ifGipAlHg4OTUzfihAUgfz1La8`. The link change takes place when a post or page is displayed. Internal links to pages on your domain are not changed, also URLs not starting with `http` or `https` are not changed (tracking of `tel` URLs can be activated optionally). Also attributes (like class or target) are not touched. There is no need to change anything. All links in all posts and pages are changed automatically in front end. When editing a post or page in back end all links appear unchanged.

== Settings (optionally) ==

In 'Settings' -> 'Link Log' you can change several settings.

== Plugin Privacy Information ==

* This plugin does not set cookies
* This plugin **stores encrypted IP-addresses of visitors in your database**
* This plugin does not send any data to external servers

== For Theme Developers ==

There are two functions you can use in your theme files:

**`get_linklog_url( $url )`** to get the tracking URL, 
e.g. `<?php $google = get_linklog_url( 'http://www.google.com' ); ?>`

**`the_linklog_url( $url )`** to echo the tracking URL, 
e.g. `<a href="<?php the_linklog_url( 'http://www.google.com' ); ?>" target"=_blank">Google</a>`

== Changelog ==

= 5.0.2 (2022-10-01) FINAL VERSION =
* removed all links to webiste
* removed request for rating
* removed manual

= 5.0.1 (2022-10-19) =
* Settings interface adapted to my other plugins

= 5 (2019-03-10) =
* plugin renamed from link-log to Link Log
* UI improvements
* code improvement

= 4 (2018-04-30) =
* incorrect GDPR compliance alert fixed

= 3 (2018-04-14) =
* encrypt IP-addresses for data protection reasons
* use 303 redirect to avoid browser caching
* priority of filter the_content changed
* minor code- & UI-improvements

= 2.4 (2017-11-16) =
* faulty display in WP 4.9 fixed

= 2.3 (2017-07-03) =
* redesigned admin interface
* code improvement
* 12 new bots added to detection list

= 2.2 (2016-10-21) =
* made plugin ready for translation
* removal of needless characters from encrypted URLs
* faster encryption and decryption
* optional tracking of telephone links

= 2.1 (2015-12-11) =
* Closed SQL Injection vulnerability

= 2.0 (2015-12-10) =
* Closed HTTP Response Splitting vulnerability
* Closed Open Redirect vulnerability
* Menu item title for Link Click Analysis page is now customizable
* Page title for Link Click Analysis page is now customizable
* Customizable link descriptions to show on Link Click Analysis page instead of URLs

= 1.4 (2015-04-28) =
* Option to add rel="nofollow" to links
* Option to track only specific posts/pages
* Complete documentation accessible from back end
* Click Analysis now accessible also for Editors, not only for Admins
* Click Analysis now uses standard WP table
* Filtering of results

= 1.3 (2014-10-26) = 
* Works now with WPML  
The [WPML Plugin](http://wpml.org) changes the Home URL by adding the language to it - Link Log now can handle that to work with WPML and other Plugins that change the Home URL (thanks to [GREIFF](http://greiff.de/en/) for testing)
* Performance Improvement  
The browser is now forced to redirect to the target URL **before** the data is stored to the databse
* remove trailing slashes  
To avoid duplicate entries for e.g. example.com and example.com/ all trailing slashes are removed now  
**Update Notice**: when updating to version 1.3 all trailing slashes from all existing entries in the database are removed automatically

= 1.2 (2014-09-19) =
* Omit search engines and other bots

= 1.1 (2014-06-25) =
* Omit multiple clicks from same IP

= 1.0 (2014-02-20) =
* Initial Release

== Upgrade Notice ==

= 5.0.1 =
Settings interface adapted to my other plugins

= 5 =
some improvements, no functional changes

= 4 =
incorrect GDPR compliance alert fixed

= 3 =
encrypt IP-addresses for data protection reasons, use 303 redirect to avoid browser caching

= 2.4 =
faulty display in WP 4.9 fixed

= 2.3 =
unified admin interface, 12 new bots added to detection list

= 2.2 =
Version 2.2 is now ready for translation. Plus improved URL encoding and optional telephone link tracking.

= 2.1 =
Version 2.1 closes an Security Exploit. Upgrade immediately to secure your WordPress installation.

= 2.0 =
Version 2.0 comes with a lot of changes. Upgrade immediately. See [this article](http://petersplugins.com/blog/2015/12/10/link-log-version-2-0-is-out/) for details.