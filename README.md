Gigaom Local Co-Authors Plus
============================

* Tags: wordpress, authors, coauthors, coauthors-plus
* Requires at least: 3.6.1, [coauthors-plus](https://wordpress.org/plugins/co-authors-plus/)
* Tested up to: 4.0
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Description
-----------

This plugin extends Co-Authors Plus by adding some utility and helper functions that have proved useful for us in our extensive use of Co-Authors Plus.

Why Does This Exist?
--------------------

We use Co-Authors Plus quite extensively and over time we've run into a variety of issues were we've either had to work around or improve upon default Co-Authors Plus behavior:

1. The admin interface for adding coauthors to a post becomes unresponsive when there are a large number of potential authors.
	- This plugin caches the list of possible authors and has custom Javascript that works around this issue.
2. Author post queries can get unewieldy when Co-Authors Plus is running which causes performance issues.
	- This plugin hooks into parse_query and does all lookups using a more efficient taxonomy/term lookup.
3. The default Co-Authors Plus coauthors_posts_links template function wasn't flexible enough for us so this plugin adds an alternative:
	- `coauthors_posts_links`

Usage Notes
-----------

1. To gain the benfits of the admin panel and query stuff simply install the plugin.
2. If you started using Co-Authors plus after already publishing some content you'll possibly have some posts that are missing the appropriate author terms.
	- You can fix that by hitting the ajax method found here (while logged into an admin account):
		- `/wp-admin/admin-ajax.php?action=go_coauthors_taxonomy_update&post_type=post&batch_size=10`
	- If you've got custom post types you can run the method again with the post_type GET var changed to correct value
3. `coauthors_posts_links` helper function for displaying the authors of a post:

	```php
	go_coauthors()->coauthors_posts_links( $between, $betweenLast, $before, $after, $echo );
	```

	Parameters
	----------

	**$between**

	_(string) (optional)_ String to put between authors

	Default: null

	**$betweenLast**

	_(string) (optional)_ String between last two authors (ex. "and")

	Default: null

	**$before**

	_(string) (optional)_ String to put before the authors

	Default: null

	**$after**

	_(string) (optional)_ String to put after the authors

	Default: null

	**$echo**

	_(boolean) (optional)_ If true outputs string

	Default: true

	Example
	-------

	```php
	go_coauthors()->coauthors_posts_links( ',', 'and' );
	```

	```html
	<span class="vcard"><a itemprop="author" class="url fn" href="http://gigaom.com/author/borkweb/" title="Posts by Matthew Batchelder" rel="author">Matthew Batchelder</a></span>, <span class="vcard"><a itemprop="author" class="url fn" href="http://research.gigaom.com/author/methnen/" title="Posts by Jamie Poitra" rel="author">Jamie Poitra</a></span> and <span class="vcard"><a itemprop="author" class="url fn" href="http://research.gigaom.com/analyst/wluo/" title="Posts by Will Luo" rel="author">Will Luo</a></span>
	```

Report Issues, Contribute Code, or Fix Stuff
--------------------------------------------

https://github.com/GigaOM/go-local-coauthors-plus/