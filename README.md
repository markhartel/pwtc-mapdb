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

### Route Map Related Shortcodes
`[pwtc_search_mapdb]` *renders a page that allow search and access of the route map library - deprecated, use `[pwtc_mapdb_manage_published_maps]` instead*

Argument|Description|Values|Default
--------|-----------|------|-------
limit|limit the number of maps shown per page|(number)|0 (unlimited)

`[pwtc_mapdb_map_breadcrumb]` *TBD*

`[pwtc_mapdb_edit_map]` *renders a form that allows a user to create/copy/edit a route map*

`[pwtc_mapdb_delete_map]` *renders a form that allows a user to delete a route map*

`[pwtc_mapdb_usage_map]` *TBD*

`[pwtc_mapdb_manage_maps]` *renders a table that allows a user to view and manage (view/create/copy/edit) their draft or pending route maps*

`[pwtc_mapdb_manage_pending_maps]` *TBD*

`[pwtc_mapdb_manage_published_maps]` *renders a table that allows a user to view and manage (view/create/copy/edit) published route maps*

`[pwtc_mapdb_new_map_link]` *TBD*

### Online Signup Related Shortcodes

`[pwtc_mapdb_rider_signup]` *renders a form that allows a user to signup for a ride*

`[pwtc_mapdb_view_signup]` *renders a page that displays/manages the attendees signed up for a ride*

Argument|Description|Values|Default
--------|-----------|------|-------
unused_rows|the number of unused rows added to the end of a downloaded ride sign-in sheet|(number)|0

`[pwtc_mapdb_nonmember_signup]` *renders a form that allows a club non-member to signup for a ride*

`[pwtc_mapdb_show_userid_signups]` *renders a page that displays the upcoming rides for which a user is signed up*

`[pwtc_mapdb_download_signup]` *renders a button that allows a user to download a blank ride sign-in sheet*

`[pwtc_mapdb_reset_signups]` *renders a button that allows a user to remove all riders signed-up for a scheduled ride*

`[pwtc_mapdb_view_signup_rides]` *TBD*

### Scheduled Ride Related Shortcodes

`[pwtc_mapdb_ride_breadcrumb]` *TBD*

`[pwtc_mapdb_edit_ride]` *renders a form that allows a user to create/copy/edit a scheduled ride*

`[pwtc_mapdb_leader_edit_ride]` *TBD*

`[pwtc_mapdb_manage_rides]` *renders a table that allows a user to view and manage (view/create/copy/edit) their draft or pending scheduled rides*

`[pwtc_mapdb_delete_ride]` *renders a form that allows a user to delete a scheduled ride*

`[pwtc_mapdb_manage_published_rides]` *renders a table that allows a user to view and manage (view/create/copy/edit) published scheduled rides*

`[pwtc_mapdb_manage_ride_templates]` *TBD*

`[pwtc_mapdb_manage_pending_rides]` *TBD*

`[pwtc_mapdb_new_ride_link]` *TBD*

`[pwtc_mapdb_schedule_template]` *TBD*

### Attached File Related Shortcodes

`[pwtc_mapdb_upload_file]` *TBD*

`[pwtc_mapdb_delete_file]` *TBD*

`[pwtc_mapdb_manage_files]` *TBD*

`[pwtc_mapdb_new_file_link]` *TBD*

### Other Shortcodes

`[pwtc_mapdb_logged_in_content]` *TBD*

`[pwtc_mapdb_not_logged_in_content]` *TBD*

`[pwtc_mapdb_role_content]` *TBD*

`[pwtc_mapdb_leader_contact]` *TBD*

`[pwtc_mapdb_alert_contact]` *TBD*

`[pwtc_mapdb_search_riders]` *TBD*

## Plugin Function Hooks

`pwtc_mapdb_get_signup` *TBD*

`pwtc_mapdb_get_map_metadata` *TBD*

`pwtc_mapdb_get_template_metadata` *TBD*

## Package Files Used By This Plugin
- `README.md` *this file*
- `pwtc-mapdb-hooks.php` *plugin hooks file*
- `pwtc-mapdb.php` *plugin definition file*
- `class.pwtcmapdb.php` *PHP class with server-side logic*
- `reports-style.css` *stylesheet for report shortcodes*
