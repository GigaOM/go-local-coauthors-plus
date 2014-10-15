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
	- This plugin hooks into parse_query and does all lookups using a more efficient taxonomy/term lookup.`

Usage Notes
-----------

1. To gain the benfits of the admin panel and query stuff simply install the plugin.
2. If you started using Co-Authors plus after already publishing some content you'll possibly have some posts that are missing the appropriate author terms.
	- You can fix that by hitting the ajax method found here (while logged into an admin account):
		- `/wp-admin/admin-ajax.php?action=go_coauthors_taxonomy_update&post_type=post&batch_size=10`
	- If you've got custom post types you can run the method again with the post_type GET var changed to correct value

Report Issues, Contribute Code, or Fix Stuff
--------------------------------------------

https://github.com/GigaOM/go-local-coauthors-plus/