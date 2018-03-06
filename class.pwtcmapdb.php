<?php

class PwtcMapdb {

	const MAP_POST_TYPE = 'ride_maps';
	const START_LOCATION_FIELD = 'start_location';
	const TERRAIN_FIELD = 'terrain';
	const LENGTH_FIELD = 'length';
	const MAX_LENGTH_FIELD = 'max_length';
	const MAP_FIELD = 'maps';
	const MAP_TYPE_FIELD = 'type';
	const MAP_LINK_FIELD = 'link';
	const MAP_FILE_FIELD = 'file';
	const COPY_ANCHOR_LABEL = '<i class="fa fa-files-o"></i>';
	const FILE_ANCHOR_LABEL = '<i class="fa fa-download"></i>';
	const LINK_ANCHOR_LABEL = '<i class="fa fa-link"></i>';

/*
	const MAP_POST_TYPE = 'route';
	const START_LOCATION_FIELD = 'start_location';
	const TERRAIN_FIELD = 'route_terrain';
	const LENGTH_FIELD = 'route_length';
	const MAX_LENGTH_FIELD = 'max_route_length';
	const MAP_TYPE_FIELD = 'map_type';
	const MAP_LINK_FIELD = 'map_link';
	const MAP_FILE_FIELD = 'map_file';
	const COPY_ANCHOR_LABEL = 'Copy';
	const FILE_ANCHOR_LABEL = 'File';
	const LINK_ANCHOR_LABEL = 'Link';
*/
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
			PWTC_MAPDB__PLUGIN_URL . 'reports-style.css', array(),
			filemtime(PWTC_MAPDB__PLUGIN_DIR . 'reports-style.css'));
	}

	/*************************************************************/
	/* Shortcode report table utility functions.
	/*************************************************************/

	public static function get_distance($post_id) {
		$length = get_field(self::LENGTH_FIELD, $post_id);
		$max_length = get_field(self::MAX_LENGTH_FIELD, $post_id);
		if ($max_length == '') {
			return $length . ' miles';
		}
		else {
			return $length . '-' . $max_length . ' miles';
		}
	}

	public static function get_terrain($post_id) {
		$terrain = get_field(self::TERRAIN_FIELD, $post_id);
		$result = '';
		foreach ($terrain as $item) {
			$result .= strtoupper($item);
		}
		return $result;
	}

	public static function get_map($post_id) {
		$url = '';
/*
		$type = get_field(self::MAP_TYPE_FIELD, $post_id);
		if ($type == 'file') {
			$file = get_field(self::MAP_FILE_FIELD, $post_id);
			$url = '<a title="Download map file." target="_blank" href="' . $file['url'] . '">' . self::FILE_ANCHOR_LABEL . '</a>';
		}
		else if ($type == 'link') {
			$link = get_field(self::MAP_LINK_FIELD, $post_id);
			$url = '<a title="Go to map link." target="_blank" href="' . $link . '">' . self::LINK_ANCHOR_LABEL . '</a>';
		}
*/

		while (have_rows(self::MAP_FIELD, $post_id) ): the_row();
			$type = get_sub_field(self::MAP_TYPE_FIELD);
			if ($type == 'file') {
				$file = get_sub_field(self::MAP_FILE_FIELD);
				$url = '<a target="_blank" href="' . $file['url'] . '">' . self::FILE_ANCHOR_LABEL . '</a>';
			}
			else if ($type == 'link') {
				$link = get_sub_field(self::MAP_LINK_FIELD);
				$url = '<a target="_blank" href="' . $link . '">' . self::LINK_ANCHOR_LABEL . '</a>';
			}
		endwhile;
	
		return $url;
	}

	public static function count_maps($title, $startswith, $bylocation) {
		global $wpdb;
		$search_title = $title . '%';
		if ($startswith == 'false') {
			$search_title = '%' . $search_title;
		}
		if ($bylocation == 'true') {
			$sql_stmt = $wpdb->prepare(
				'select count(p.ID)' . 
				' from ' . $wpdb->posts . ' as p inner join ' . $wpdb->postmeta . 
				' as m on p.ID = m.post_id where p.post_type = %s and p.post_status = \'publish\'' . 
				' and m.meta_key = %s and m.meta_value like %s', 
				self::MAP_POST_TYPE, self::START_LOCATION_FIELD, $search_title);	
		}
		else {
			$sql_stmt = $wpdb->prepare(
				'select count(ID)' . 
				' from ' . $wpdb->posts .
				' where post_title like %s and post_type = %s and post_status = \'publish\'', 
				$search_title, self::MAP_POST_TYPE);
		}
		$results = $wpdb->get_var($sql_stmt);
		return $results;
	}

	public static function fetch_maps($title, $startswith, $bylocation, $offset = -1 , $rowcount = -1) {
		global $wpdb;
		$search_title = $title . '%';
		if ($startswith == 'false') {
			$search_title = '%' . $search_title;
		}
		if ($bylocation == 'true') {
			$sql_stmt = $wpdb->prepare(
				'select p.ID, p.post_title' . 
				' from ' . $wpdb->posts . ' as p inner join ' . $wpdb->postmeta . 
				' as m on p.ID = m.post_id where p.post_type = %s and p.post_status = \'publish\'' . 
				' and m.meta_key = %s and m.meta_value like %s order by p.post_title', 
				self::MAP_POST_TYPE, self::START_LOCATION_FIELD, $search_title);	
		}
		else {
			$sql_stmt = $wpdb->prepare(
				'select ID, post_title' . 
				' from ' . $wpdb->posts .
				' where post_title like %s and post_type = %s and post_status = \'publish\'' . 
				' order by post_title', 
				$search_title, self::MAP_POST_TYPE);
		}
		if ($offset >= 0 and $rowcount >= 0) {
			$sql_stmt .= ' limit ' . $offset . ',' . $rowcount;
		}
		$results = $wpdb->get_results($sql_stmt, ARRAY_A);
		return $results;
	}

	public static function build_map_array($maps) {
		$return_maps = array();
		foreach ($maps as $map) {
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
		return $return_maps;		
	}

	public static function lookup_maps_callback() {
		if (false) {
			$response = array(
				'error' => 'You are not allowed to search the map library.'
			);
			echo wp_json_encode($response);
		}
		else if (!isset($_POST['title']) or !isset($_POST['startswith']) or !isset($_POST['bylocation']) or !isset($_POST['limit'])) {
			$response = array(
				'error' => 'Input parameters needed to search map library are missing.'
			);
			echo wp_json_encode($response);
		}
		else {
			$title = sanitize_text_field($_POST['title']);
			$bylocation = $_POST['bylocation'];
			$limit = intval($_POST['limit']);	
			$startswith = 'false';
			if (isset($_POST['startswith'])) {
				$startswith = trim($_POST['startswith']);
			}
			if (empty($title) and $bylocation == 'true') {
				$bylocation = 'false';
			}
			$nmaps = intval(self::count_maps($title, $startswith, $bylocation));
			$message = '';
			if (isset($_POST['count']) and intval($_POST['count']) != $nmaps) {
				$message = 'Search results have changed, paging context was lost.';
			}
			if ($limit > 0 and $nmaps > $limit) {
				$offset = 0;
				if (isset($_POST['count']) and intval($_POST['count']) == $nmaps) {
					if (isset($_POST['prev'])) {
						$offset = intval($_POST['offset']) - $limit;
					}
					else if (isset($_POST['next'])) {
						$offset = intval($_POST['offset']) + $limit;
					}
				}
				$maps = self::fetch_maps($title, $startswith, $bylocation, $offset, $limit);
				$return_maps = self::build_map_array($maps);
				$response = array(
					'count' => $nmaps,
					'offset' => $offset,
					'maps' => $return_maps);
				if ($message != '') {
					$response['message'] = $message;
				}
				echo wp_json_encode($response);
			}
			else {
				$maps = self::fetch_maps($title, $startswith, $bylocation);
				$return_maps = self::build_map_array($maps);
				$response = array(
					'maps' => $return_maps);
				if ($message != '') {
					$response['message'] = $message;
				}
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
			return 'Please log in to search the map library.';
		}
		else {
			ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 
			function populate_maps_table(maps) {
				var copylink = '<a title="Copy ride title to clipboard." class="copy-btn"><?php echo self::COPY_ANCHOR_LABEL ?></a>';
				$('.pwtc-mapdb-maps-div').append('<table class="pwtc-mapdb-rwd-table">' +
					'<tr><th>Title</th><th>Distance</th><th>Terrain</th><th>Actions</th></tr>' +
					'</table>');
				maps.forEach(function(item) {
					$('.pwtc-mapdb-maps-div table').append(
						'<tr postid="' + item.ID + '">' + 
						'<td data-th="Title"><span>' + item.title + '</span></td>' +
						'<td data-th="Distance">' + item.distance + '</td>' +
						'<td data-th="Terrain">' + item.terrain + '</td>' +
						'<td data-th="Actions">' + copylink + ' ' + item.media + '</td></tr>');    
				});
				$('.pwtc-mapdb-maps-div table .copy-btn').on('click', function(evt) {
					var title = $(this).parent().parent().find('td').first().find('span').first()[0];
					if (window.getSelection().rangeCount > 0) window.getSelection().removeAllRanges();
					var range = document.createRange();  
  					range.selectNode(title);  
  					window.getSelection().addRange(range);  
					try {  
    					var successful = document.execCommand('copy');  
    					var msg = successful ? 'successful' : 'unsuccessful';  
    					console.log('Copy title command was ' + msg);  
  					} catch(err) {  
    					console.log('Oops, unable to copy');  
  					}  
					window.getSelection().removeAllRanges();  
				});
			}

			function create_paging_form(offset, count) {
				var limit = <?php echo $a['limit'] ?>;
				var pagenum = (offset/limit) + 1;
				var numpages = Math.ceil(count/limit);
				$('.pwtc-mapdb-maps-div').append(
					'<form class="page-frm">' +
                    '<input class="prev-btn dark button" style="margin: 0" type="button" value="< Prev"/>' +
					'<span style="margin: 0 10px">Page ' + pagenum + ' of ' + numpages + '</span>' +
                    '<input class="next-btn dark button" style="margin: 0" type="button" value="Next >"/>' +
					'<input name="offset" type="hidden" value="' + offset + '"/>' +
					'<input name="count" type="hidden" value="' + count + '"/>' +
					'</form>'
				);
				$('.pwtc-mapdb-maps-div .page-frm .prev-btn').on('click', function(evt) {
					evt.preventDefault();
					load_maps_table('prev');
				});
				if (pagenum == 1) {
					$('.pwtc-mapdb-maps-div .page-frm .prev-btn').attr("disabled", "disabled");
				}
				else {
					$('.pwtc-mapdb-maps-div .page-frm .prev-btn').removeAttr("disabled");
				}
				$('.pwtc-mapdb-maps-div .page-frm .next-btn').on('click', function(evt) {
					evt.preventDefault();
					load_maps_table('next');
				});
				if (pagenum == numpages) {
					$('.pwtc-mapdb-maps-div .page-frm .next-btn').attr("disabled", "disabled");
				}
				else {
					$('.pwtc-mapdb-maps-div .page-frm .next-btn').removeAttr("disabled");
				}
			}

			function lookup_maps_cb(response) {
				var res = JSON.parse(response);
				$('.pwtc-mapdb-maps-div').empty();
				if (res.error) {
					$('.pwtc-mapdb-maps-div').append(
						'<div><strong>Error:</strong> ' + res.error + '</div>');
				}
				else {
					if (res.message !== undefined) {
						$('.pwtc-mapdb-maps-div').append(
							'<div><strong>Warning:</strong> ' + res.message + '</div>');
					}
					if (res.maps.length > 0) {
						if (res.offset !== undefined) {
							create_paging_form(res.offset, res.count);
						}
						populate_maps_table(res.maps);
					}
					else {
						$('.pwtc-mapdb-maps-div').append('<div>No maps found.</div>');					
					}
				}
				$('body').removeClass('pwtc-mapdb-waiting');
			}   

			function load_maps_table(mode) {
				var title = $(".pwtc-mapdb-search-sec .search-frm input[name='title']").val().trim();
				var startswith = false;
				if ($('.pwtc-mapdb-search-sec .search-frm .searchfrom').val() == 'begin') {
					startswith = true;
				}
				var bylocation = false;
				if ($('.pwtc-mapdb-search-sec .search-frm .searchby').val() == 'location') {
					bylocation = true;
				}
				var action = $('.pwtc-mapdb-search-sec .search-frm').attr('action');
				var data = {
					'action': 'pwtc_mapdb_lookup_maps',
					'title': title,
					'startswith': startswith,
					'bylocation': bylocation,
					'limit': <?php echo $a['limit'] ?>
				};
				if (mode != 'search') {
					var offset = $(".pwtc-mapdb-maps-div .page-frm input[name='offset']").val();
					var count = $(".pwtc-mapdb-maps-div .page-frm input[name='count']").val();
					data.offset = offset;
					data.count = count;
					if (mode == 'prev') {
						data.prev = 1;
					}
					else if (mode == 'next') {
						data.next = 1;						
					}
				}
				$('body').addClass('pwtc-mapdb-waiting');
				$.post(action, data, lookup_maps_cb); 
			}

			$('.pwtc-mapdb-search-sec .search-frm').on('submit', function(evt) {
				evt.preventDefault();
				load_maps_table('search');
			});

			$('.pwtc-mapdb-search-sec .search-frm .reset-btn').on('click', function(evt) {
				evt.preventDefault();
				$(".pwtc-mapdb-search-sec .search-frm input[type='text']").val(''); 
				$('.pwtc-mapdb-maps-div').empty();
			});
		});
	</script>
	<div class='pwtc-mapdb-search-sec'>
	<div>To browse the map library, press the <strong>Search</strong> button. 
	To narrow your search, enter a string into the <strong>Search For</strong> field before searching.</div>
	<form class="search-frm pwtc-mapdb-stacked-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
		<span>Search By</span>
		<select class="searchby">
            <option value="title" selected>Ride Title</option>
			<!--<option value="location">Start Location</option>-->
        </select>		
		<span>Search For</span>
		<input type="text" name="title"/>
		<span>Search From</span>
		<select class="searchfrom">
            <option value="any" selected>Anywhere in Line</option> 
            <option value="begin">Beginning of Line</option>
        </select>		
		<input class="dark button" type="submit" value="Search"/>
		<input class="reset-btn dark button" type="button" value="Reset"/>
	</form>
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