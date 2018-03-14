# pwtc-mapdb

This is a Wordpress plugin that provides searchable access to the map library database for members of the [Portland Wheelmen Touring Club](http://pwtc.com).

## Installation
Download this distribution as a zip file, login to the pwtc.com Wordpress website as admin and upload this zip file as a new plugin. This plugin will be named **PWTC Map DB**, activate it from the Plugins management page. After activation, this plugin will create a shortcode that allow you to add the route map database search form to your pages.

### Plugin Uninstall
Deactivate and then delete the **PWTC Map DB** plugin from the Plugins management page.

## Route Map DB Shortcodes
These shortcodes allow users to insert map library database related content into Wordpress
pages. For example, if you place the following text string into your page content, it will 
render as a form that allows users to search the map library database and limits the number
of maps returned to 10 per page:

`[pwtc_search_mapdb limit="10"]`

### Route Map DB Shortcodes
`[pwtc_search_mapdb]` *form that allow search of the map library database*

Argument|Description|Values|Default
--------|-----------|------|-------
limit|limit the number of maps shown per page in search results|number|0 (unlimited)

## Package Files Used By This Plugin
- `README.md` *this file*
- `pwtc-mapdb.php` *plugin definition file*
- `class.pwtcmapdb.php` *PHP class with server-side logic*
- `reports-style.css` *stylesheet for report shortcodes*
