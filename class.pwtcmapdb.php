<?php

class PwtcMapdb {

/*
	const MAP_POST_TYPE = 'ride_maps';
	const TERRAIN_FIELD = 'terrain';
	const LENGTH_FIELD = 'length';
	const MAX_LENGTH_FIELD = 'max_length';
	const MAP_FIELD = 'maps';
	const MAP_TYPE_FIELD = 'type';
	const MAP_LINK_FIELD = 'link';
	const MAP_FILE_FIELD = 'file';
*/

	const MAP_POST_TYPE = 'route';
	const TERRAIN_FIELD = 'route_terrain';
	const LENGTH_FIELD = 'route_length';
	const MAX_LENGTH_FIELD = 'max_route_length';
	const MAP_TYPE_FIELD = 'map_type';
	const MAP_LINK_FIELD = 'map_link';
	const MAP_FILE_FIELD = 'map_file';

    private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	// Initializes plugin WordPress hooks.
	private static function init_hooks() {
		self::$initiated = true;

		// Register script and style enqueue callbacks
		add_action( 'wp_enqueue_scripts', 
			array( 'PwtcMapdb', 'load_report_scripts' ) );

		add_action( 'wp_ajax_pwtc_mapdb_lookup_maps', 
			array( 'PwtcMapdb', 'lookup_maps_callback') );

		// Register shortcode callbacks
		add_shortcode('pwtc_search_mapdb', 
			array( 'PwtcMapdb', 'shortcode_search_mapdb'));
	}

	/*************************************************************/
	/* Script and style enqueue callback functions
	/*************************************************************/

	public static function load_report_scripts() {
        wp_enqueue_style('pwtc_mapdb_report_css', 
			PWTC_MAPDB__PLUGIN_URL . 'reports-style.css' );
	}

	/*************************************************************/
	/* Shortcode report table utility functions.
	/*************************************************************/

	public static function get_distance($post_id) {
		$length = get_field(self::LENGTH_FIELD, $post_id);
		$max_length = get_field(self::MAX_LENGTH_FIELD, $post_id);
		//self::write_log($length);
		//self::write_log($max_length);
		if ($max_length == '') {
			return $length . ' miles';
		}
		else {
			return $length . '-' . $max_length . ' miles';
		}
	}

	public static function get_terrain($post_id) {
		$terrain = get_field(self::TERRAIN_FIELD, $post_id);
		//self::write_log($terrain);
		$result = '';
		foreach ($terrain as $item) {
			$result .= $item;
		}
		return $result;
	}

	public static function get_map($post_id) {
		$url = '';

		$type = get_field(self::MAP_TYPE_FIELD, $post_id);
		//self::write_log($type);
		if ($type == 'file') {
			$file = get_field(self::MAP_FILE_FIELD, $post_id);
			//self::write_log($file);
			$url = '<a target="_blank" href="' . $file['url'] . '">File</a>';
		}
		else if ($type == 'link') {
			$link = get_field(self::MAP_LINK_FIELD, $post_id);
			//self::write_log($link);
			$url = '<a target="_blank" href="' . $link . '">Link</a>';
		}
/*		
		while (have_rows(self::MAP_FIELD, $post_id) ): the_row();
			$type = get_sub_field(self::MAP_TYPE_FIELD);
			self::write_log($type);
			if ($type == 'file') {
				$file = get_sub_field(self::MAP_FILE_FIELD);
				self::write_log($file);
				$url = '<a target="_blank" href="' . $file['url'] . '">File</a>';
			}
			else if ($type == 'link') {
				$link = get_sub_field(self::MAP_LINK_FIELD);
				self::write_log($link);
				$url = '<a target="_blank" href="' . $link . '">Link</a>';
			}
		endwhile;
*/
		return $url;
	}

	public static function fetch_maps($title, $startswith) {
		global $wpdb;
		$search_title = $title . '%';
		if ($startswith == 'false') {
			$search_title = '%' . $search_title;
		}
		$sql_stmt = $wpdb->prepare(
			'select ID, post_title' . 
			' from ' . $wpdb->posts .
			' where post_title like %s and post_type = %s and post_status = \'publish\'' . 
			' order by post_title', 
			$search_title, self::MAP_POST_TYPE);
		//self::write_log($sql_stmt);
		$results = $wpdb->get_results($sql_stmt, ARRAY_A);
		return $results;
	}

	public static function lookup_maps_callback() {
		if (false) {
			$response = array(
				'error' => 'You are not allowed to lookup a map.'
			);
			echo wp_json_encode($response);
		}
		else if (!isset($_POST['title']) or !isset($_POST['startswith']) or !isset($_POST['limit'])) {
			$response = array(
				'error' => 'Input parameters needed to lookup a map are missing.'
			);
			echo wp_json_encode($response);
		}
		else {
			$title = sanitize_text_field($_POST['title']);
			$limit = intval($_POST['limit']);	
			$startswith = 'false';
			if (isset($_POST['startswith'])) {
				$startswith = trim($_POST['startswith']);
			}
			$return_maps = array();
			$maps = self::fetch_maps($title, $startswith);
			$nmaps = count($maps);
			$count = 0;
			$message = '';
			foreach ($maps as $map) {
				$count++;
				if ($limit > 0 and $count > $limit) {
					$message = '' . $nmaps . ' maps returned, but only displaying the first ' . $limit . '.';
					break;
				}
				$post_id = intval($map['ID']);
				$distance = self::get_distance($post_id);
				$terrain = self::get_terrain($post_id);
				$link = self::get_map($post_id);
				array_push($return_maps, array(
					'ID' => $map['ID'],
					'title' => $map['post_title'],
					'distance' => $distance,
					'terrain' => $terrain,
					'media' => $link
				));
			}
			if ($message == '') {
				$response = array(
					'maps' => $return_maps);
				echo wp_json_encode($response);
			}
			else {
				$response = array(
					'message' => $message,
					'maps' => $return_maps);
				echo wp_json_encode($response);
			}
		}
		wp_die();
	}

	/*************************************************************/
	/* Shortcode report generation functions
	/*************************************************************/
 
	// Generates the [pwtc_search_mapdb] shortcode.
	public static function shortcode_search_mapdb($atts) {
		$a = shortcode_atts(array('limit' => 0), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return 'Please log in to search the map database.';
		}
		else {
			ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 
			function populate_maps_table(maps) {
				$('.pwtc-mapdb-maps-div').append('<table class="pwtc-mapdb-rwd-table">' +
					'<tr><th>Title</th><th>Distance</th><th>Terrain</th><th>Media</th></tr>' +
					'</table>');
				maps.forEach(function(item) {
					$('.pwtc-mapdb-maps-div table').append(
						'<tr postid="' + item.ID + '">' + 
						'<td data-th="Title">' + item.title + '</td>' +
						'<td data-th="Distance">' + item.distance + '</td>' +
						'<td data-th="Terrain">' + item.terrain + '</td>' +
						'<td data-th="Media">' + item.media + '</td></tr>');    
				});
			}

			function lookup_maps_cb(response) {
				var res = JSON.parse(response);
				$('.pwtc-mapdb-maps-div').empty();
				if (res.error) {
					$('.pwtc-mapdb-maps-div').append(
						'<span><strong>Error:</strong> ' + res.error + '</span>');
				}
				else {
					if (res.maps.length > 0) {
						if (res.message) {
							$('.pwtc-mapdb-maps-div').append(
								'<span><strong>Warning:</strong> ' + res.message + '</span>');
						}
						populate_maps_table(res.maps);
					}
					else {
						$('.pwtc-mapdb-maps-div').append('<span>No maps found.</span>');					
					}
				}
			}   

			function load_maps_table() {
				var title = $(".pwtc-mapdb-search-sec .search-frm input[name='title']").val().trim();
				var startswith = false;
				if ($(".pwtc-mapdb-search-sec .search-frm input[name='startswith']").is(':checked')) {
					startswith = true;
				}
				if (true) {
					var action = $('.pwtc-mapdb-search-sec .search-frm').attr('action');
					var data = {
						'action': 'pwtc_mapdb_lookup_maps',
						'title': title,
						'startswith': startswith,
						'limit': <?php echo $a['limit'] ?>
					};
					$.post(action, data, lookup_maps_cb); 
				}
				else {
					$('.pwtc-mapdb-maps-div').empty();  
				}  
			}

			$('.pwtc-mapdb-search-sec .search-frm').on('submit', function(evt) {
				evt.preventDefault();
				load_maps_table();
			});

			$('.pwtc-mapdb-search-sec .search-frm .reset-btn').on('click', function(evt) {
				evt.preventDefault();
				$(".pwtc-mapdb-search-sec .search-frm input[type='text']").val(''); 
				$('.pwtc-mapdb-maps-div').empty();
			});
		});
	</script>
	<div class='pwtc-mapdb-search-sec'>
	<p>To search the map database, press the <strong>Search</strong> button. 
	To narrow your search, first enter a string into the <strong>Title</strong> field before searching.
	<form class="search-frm pwtc-mapdb-stacked-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
		<span>Title</span>
		<input type="text" name="title"/>
		<span>Title Begins With</span>
		<span class="pwtc-mapdb-checkbox-wrap">
			<input type="checkbox" name="startswith"/>
		</span>
		<input type="submit" value="Search"/>
		<input class="reset-btn" type="button" value="Reset"/>
	</form></p>	
	</div>
	<div class="pwtc-mapdb-maps-div"></div>
	<?php
			return ob_get_clean();
		}
	}

	/*************************************************************/
	/* Plugin installation and removal functions.
	/*************************************************************/

	public static function plugin_activation() {
		self::write_log( 'PWTC MapDB plugin activated' );
		if ( version_compare( $GLOBALS['wp_version'], PWTC_MAPDB__MINIMUM_WP_VERSION, '<' ) ) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('PWTC MapDB plugin requires Wordpress version of at least ' . PWTC_MAPDB__MINIMUM_WP_VERSION);
		}
	}

	public static function plugin_deactivation( ) {
		self::write_log( 'PWTC MapDB plugin deactivated' );
	}

	public static function plugin_uninstall() {
		self::write_log( 'PWTC MapDB plugin uninstall' );	
	}

    public static function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }

}