# PWTC Map DB

This is a Wordpress plugin that provides searchable access to the route map library for the [Portland Bicycling Club](https://portlandbicyclingclub.com).

## Installation
Download this distribution as a zip file, login to the pwtc.com Wordpress website as admin and upload this zip file as a new plugin. This plugin will be named **PWTC Map DB**, activate it from the Plugins management page.

### Plugin Uninstall
Deactivate and then delete the **PWTC Map DB** plugin from the Plugins management page.

## Plugin Shortcodes
These shortcodes allow users to add plugin specific content into Wordpress
pages. For example, if you place the following text string into your page content, it will 
render as a form that allows users to search the map library, limiting the number
of maps returned to 10 per page:

`[pwtc_search_mapdb limit="10"]`

### Map Library Shortcodes
`[pwtc_search_mapdb]` *renders a page that allow search and access of the route map library*

Argument|Description|Values|Default
--------|-----------|------|-------
limit|limit the number of maps shown per page|(number)|0 (unlimited)

`[pwtc_mapdb_rider_signup]` *renders a form that allows a user to signup for a ride*

`[pwtc_mapdb_view_signup]` *renders a page that displays/manages the attendees signed up for a ride*

Argument|Description|Values|Default
--------|-----------|------|-------
unused_rows|the number of unused rows added to the end of a downloaded ride sign-in sheet|(number)|0

`[pwtc_mapdb_nonmember_signup]` *renders a form that allows a club non-member to signup for a ride*

`[pwtc_mapdb_show_userid_signups]` *renders a page that displays the upcoming rides for which a user is signed up*

`[pwtc_mapdb_edit_ride]` *renders a form that allows a user to edit the title and description of a ride*

`[pwtc_mapdb_leader_details]` *renders a form that allows a user to edit their ride leader preferences settings*

`[pwtc_mapdb_download_signup]` *renders a button that allows a user to download a blank ride sign-in sheet*

## Package Files Used By This Plugin
- `README.md` *this file*
- `pwtc-mapdb-hooks.php` *plugin hooks file*
- `pwtc-mapdb.php` *plugin definition file*
- `class.pwtcmapdb.php` *PHP class with server-side logic*
- `reports-style.css` *stylesheet for report shortcodes*
