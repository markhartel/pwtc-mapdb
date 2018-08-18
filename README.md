# pwtc-mapdb

This is a Wordpress plugin that provides searchable access to the map library and ride leader list for members of the [Portland Wheelmen Touring Club](http://pwtc.com).

## Installation
Download this distribution as a zip file, login to the pwtc.com Wordpress website as admin and upload this zip file as a new plugin. This plugin will be named **PWTC Map DB**, activate it from the Plugins management page. After activation, this plugin will create shortcodes that allow you to add map library and ride leader list access forms to your pages.

### Plugin Uninstall
Deactivate and then delete the **PWTC Map DB** plugin from the Plugins management page.

## Plugin Shortcodes
These shortcodes allow users to add plugin specific content into Wordpress
pages. For example, if you place the following text string into your page content, it will 
render as a form that allows users to search the map library, limiting the number
of maps returned to 10 per page:

`[pwtc_search_mapdb limit="10"]`

### Map Library Shortcodes
`[pwtc_search_mapdb]` *form that allow search and access of the map library*

Argument|Description|Values|Default
--------|-----------|------|-------
limit|limit the number of maps shown per page|(number)|0 (unlimited)

### Ride Leader List Shortcodes
`[pwtc_ride_leader_dir]` *form that allow search and access of the ride leader list*

Argument|Description|Values|Default
--------|-----------|------|-------
limit|limit the number of members shown per page|(number)|10
mode|set profile editor operating mode|readonly,edit|readonly

## Package Files Used By This Plugin
- `README.md` *this file*
- `pwtc-mapdb.php` *plugin definition file*
- `class.pwtcmapdb.php` *PHP class with server-side logic*
- `reports-style.css` *stylesheet for report shortcodes*
