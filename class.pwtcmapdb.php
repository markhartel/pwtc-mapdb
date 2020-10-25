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
	const MAP_TYPE_QUERY = 'maps_0_type';
	const COPY_ANCHOR_LABEL = '<i class="fa fa-clipboard"></i>';
	//const FILE_ANCHOR_LABEL = '<i class="fa fa-download"></i>';
	//const LINK_ANCHOR_LABEL = '<i class="fa fa-link"></i>';
	const EDIT_ANCHOR_LABEL = '<i class="fa fa-pencil-square"></i>';
	const EDIT_CAPABILITY = 'edit_others_rides';
	
	const RIDE_CANCELED = 'is_canceled';
	const RIDE_LEADERS = 'ride_leaders';
	const RIDE_DATE = 'date';
	const RIDE_SIGNUP_LOCKED = '_signup_locked';
	const RIDE_SIGNUP_USERID = '_signup_user_id';
	const RIDE_SIGNUP_NONMEMBER = '_signup_nonmember_id';
	const RIDE_SIGNUP_MODE = '_signup_mode';
	const RIDE_SIGNUP_CUTOFF = '_signup_cutoff';
	const RIDE_SIGNUP_LIMIT = '_signup_limit';
	const RIDE_SIGNUP_MEMBERS_ONLY = '_signup_members_only';

	const USER_EMER_PHONE = 'emergency_contact_phone';
	const USER_EMER_NAME = 'emergency_contact_name';
	const USER_SIGNUP_MODE = 'online_ride_signup';
	const USER_SIGNUP_CUTOFF = 'signup_cutoff_time';
	const USER_USE_EMAIL = 'use_contact_email';
	const USER_CONTACT_EMAIL = 'contact_email';
	const USER_CELL_PHONE = 'cell_phone';
	const USER_HOME_PHONE = 'home_phone';
	const USER_RIDER_ID = 'rider_id';
	const USER_RELEASE_ACCEPTED = 'release_accepted';

	const ROLE_CURRENT_MEMBER = 'current_member';
	const ROLE_EXPIRED_MEMBER = 'expired_member';
	const ROLE_RIDE_LEADER = 'ride_leader';

	const LOCAL_SIGNUP_ID = 'ride_signup_id';
	const LOCAL_SIGNUP_NAME = 'ride_signup_name';
	const LOCAL_EMER_NAME = 'ride_signup_contact_name';
	const LOCAL_EMER_PHONE = 'ride_signup_contact_phone';

	const POST_TYPE_RIDE = 'scheduled_rides';
	
	const TIMESTAMP_OFFSET = 50*365*24*60*60;

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
		
		add_action( 'template_redirect', 
			array( 'PwtcMapdb', 'download_ride_signup' ) );

		// Register shortcode callbacks
		add_shortcode('pwtc_search_mapdb', 
			array( 'PwtcMapdb', 'shortcode_search_mapdb'));
		
		add_shortcode('pwtc_mapdb_rider_signup', 
			array( 'PwtcMapdb', 'shortcode_rider_signup'));

		add_shortcode('pwtc_mapdb_view_signup', 
			array( 'PwtcMapdb', 'shortcode_view_signup'));
		
		add_shortcode('pwtc_mapdb_nonmember_signup', 
			array( 'PwtcMapdb', 'shortcode_nonmember_signup'));
		
		add_shortcode('pwtc_mapdb_show_userid_signups', 
			array( 'PwtcMapdb', 'shortcode_show_userid_signups'));
		
		add_shortcode('pwtc_mapdb_download_signup', 
			array( 'PwtcMapdb', 'shortcode_download_signup'));

		add_shortcode('pwtc_mapdb_leader_details', 
			array( 'PwtcMapdb', 'shortcode_leader_details'));
		
		add_shortcode('pwtc_mapdb_edit_ride', 
			array( 'PwtcMapdb', 'shortcode_edit_ride'));
		
		/* Register AJAX request/response callbacks */

		add_action( 'wp_ajax_pwtc_mapdb_edit_signup', 
			array( 'PwtcMapdb', 'edit_signup_callback') );
		
		add_action( 'wp_ajax_pwtc_mapdb_edit_nonmember_signup', 
			array( 'PwtcMapdb', 'edit_nonmember_signup_callback') );
		
		add_action( 'wp_ajax_pwtc_mapdb_log_mileage', 
			array( 'PwtcMapdb', 'log_mileage_callback') );
		
		add_action( 'wp_ajax_pwtc_mapdb_lookup_ride_leaders', 
			array( 'PwtcMapdb', 'lookup_ride_leaders_callback') );
		
		add_action( 'wp_ajax_pwtc_mapdb_check_nonmember_signup', 
			array( 'PwtcMapdb', 'check_nonmember_signup_callback') );

		add_action( 'wp_ajax_pwtc_mapdb_accept_nonmember_signup', 
			array( 'PwtcMapdb', 'accept_nonmember_signup_callback') );

		add_action( 'wp_ajax_pwtc_mapdb_cancel_nonmember_signup', 
			array( 'PwtcMapdb', 'cancel_nonmember_signup_callback') );
		
		add_action( 'wp_ajax_nopriv_pwtc_mapdb_check_nonmember_signup', 
			array( 'PwtcMapdb', 'check_nonmember_signup_callback') );

		add_action( 'wp_ajax_nopriv_pwtc_mapdb_accept_nonmember_signup', 
			array( 'PwtcMapdb', 'accept_nonmember_signup_callback') );

		add_action( 'wp_ajax_nopriv_pwtc_mapdb_cancel_nonmember_signup', 
			array( 'PwtcMapdb', 'cancel_nonmember_signup_callback') );

	}

	/*************************************************************/
	/* Script and style enqueue callback functions
	/*************************************************************/

	public static function load_report_scripts() {
/*
        wp_enqueue_style('pwtc_mapdb_report_css', 
			PWTC_MAPDB__PLUGIN_URL . 'reports-style.css', array(),
			filemtime(PWTC_MAPDB__PLUGIN_DIR . 'reports-style.css'));
*/
		wp_enqueue_style('pwtc_mapdb_report_css', 
			PWTC_MAPDB__PLUGIN_URL . 'reports-style-v2.css', array());

	}

	/*************************************************************/
	/* Shortcode report table utility functions.
	/*************************************************************/

	public static function get_query_args($title, $location, $terrain, $min_dist, $max_dist, $media) {
		$args = array(
			'post_type' => self::MAP_POST_TYPE,
			'post_status' => 'publish',
			'orderby'   => 'title',
			'order'     => 'ASC'
		);
		if (!empty($title)) {
			$args['s'] = $title;	
		}
		if (!empty($location)) {
            $args['meta_query'][] = [
                'key' => self::START_LOCATION_FIELD,
                'value' => $location,
                'compare' => 'LIKE',
            ];
		}
		if (!empty($terrain)) {
            $args['meta_query'][] = [
                'key' => self::TERRAIN_FIELD,
                'value' => '"' . $terrain . '"',
                'compare' => 'LIKE',
            ];
		}
		if ($min_dist >= 0 or $max_dist >= 0) {
			if ($min_dist < 0) {
				$args['meta_query'][] = [
					'key' => self::LENGTH_FIELD,
					'value' => [0, $max_dist],
					'type' => 'NUMERIC',
					'compare' => 'BETWEEN',
				];	
			}
			else if ($max_dist < 0) {
				$args['meta_query'][] = [
					'key' => self::LENGTH_FIELD,
					'value' => $min_dist,
					'type' => 'NUMERIC',
					'compare' => '>',
				];
			}
			else {
				$args['meta_query'][] = [
					'key' => self::LENGTH_FIELD,
					'value' => [$min_dist, $max_dist],
					'type' => 'NUMERIC',
					'compare' => 'BETWEEN',
				];	
			}
		}
		if (!empty($media)) {
			$args['meta_query'][] = [
				'key' => self::MAP_TYPE_QUERY,
				'value' => $media,
				'compare' => 'LIKE',
			];
		}
	return $args;	
	}

	public static function count_maps($title, $location, $terrain, $min_dist, $max_dist, $media) {
		$args = self::get_query_args($title, $location, $terrain, $min_dist, $max_dist, $media);
		$args['posts_per_page'] = -1;
		$args['cache_results'] = false;
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;
		//self::write_log ($args);
		$query = new WP_Query($args);
		$count = $query->found_posts;
		//self::write_log ('Count = ' . $count);
		return $count;
	}

	public static function fetch_maps($title, $location, $terrain, $min_dist, $max_dist, $media, $offset = -1 , $rowcount = -1) {
		$args = self::get_query_args($title, $location, $terrain, $min_dist, $max_dist, $media);
		$args['posts_per_page'] = $rowcount;
		if ($offset >= 0) {
			$args['offset'] = $offset;
		}
		//self::write_log ($args);
		$query = new WP_Query($args);	
		$results = [];	
		while ($query->have_posts()) {
			$query->the_post();

			$terrain = get_field(self::TERRAIN_FIELD);
			$terrain_str = '';
			foreach ($terrain as $item) {
				$terrain_str .= strtoupper($item);
			}

			$length = get_field(self::LENGTH_FIELD);
			$max_length = get_field(self::MAX_LENGTH_FIELD);
			$distance_str = '';
			if ($max_length == '') {
				$distance_str = $length . ' miles';
			}
			else {
				$distance_str = $length . '-' . $max_length . ' miles';
			}

			$url = '';

			while (have_rows(self::MAP_FIELD) ): the_row();
				$type = get_sub_field(self::MAP_TYPE_FIELD);
				if ($type == 'file') {
					$file = get_sub_field(self::MAP_FILE_FIELD);
					//$modtime = get_post_modified_time('M Y', false, $file['id']);
					//self::write_log ($file);
					//$url = '<a title="Download map file." target="_blank" href="' . $file['url'] . '">' . self::FILE_ANCHOR_LABEL . '</a>';
					$url = '<a title="Download map file." target="_blank" href="' . $file['url'] . '">';
				}
				else if ($type == 'link') {
					$link = get_sub_field(self::MAP_LINK_FIELD);
					//$url = '<a title="Open map link." target="_blank" href="' . $link . '">' . self::LINK_ANCHOR_LABEL . '</a>';
					$url = '<a title="Open map link." target="_blank" href="' . $link . '">';
				}
			endwhile;

			$edit_url = '';
			if (current_user_can(self::EDIT_CAPABILITY)) {
				$href = admin_url('post.php?post=' . get_the_ID() . '&action=edit');
				$edit_url = '<a title="Edit map post." target="_blank" href="' . $href . '">' . self::EDIT_ANCHOR_LABEL . '</a>';
			}

			$map = [
				'ID' => get_the_ID(),
				'title' => get_the_title(),
				'terrain' => $terrain_str,
				'distance' => $distance_str,
				'media' => $url,
				'edit' => $edit_url
			];
			$results[] = $map;
		}
		wp_reset_postdata();
		return $results;
	}

	public static function lookup_maps_callback() {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			$response = array(
				'error' => 'Map fetch failed - user access denied.'
			);
			echo wp_json_encode($response);		
		}
		else if (!isset($_POST['title']) or !isset($_POST['location']) or !isset($_POST['terrain']) or !isset($_POST['distance']) or !isset($_POST['media']) or !isset($_POST['limit'])) {
			$response = array(
				'error' => 'Map fetch failed - AJAX arguments missing.'
			);
			echo wp_json_encode($response);
		}
		else {
			$title = sanitize_text_field($_POST['title']);
			$location = sanitize_text_field($_POST['location']);
			$terrain = '';
			if ($_POST['terrain'] != '0') {
				$terrain = $_POST['terrain'];
			}
			$distance = $_POST['distance'];
			$min_dist = -1;
			$max_dist = -1;
			switch ($distance) {
				case 1:
					$max_dist = 25;
					break;
				case 2:
					$min_dist = 25;
					$max_dist = 50;
					break;
				case 3:
					$min_dist = 50;
					$max_dist = 75;
					break;
				case 4:
					$min_dist = 75;
					$max_dist = 100;
					break;
				case 5:
					$min_dist = 100;
					break;
				default:
					break;
			}
			$media = '';
			if ($_POST['media'] != '0') {
				$media = $_POST['media'];
			}
			$limit = intval($_POST['limit']);	
			$nmaps = self::count_maps($title, $location, $terrain, $min_dist, $max_dist, $media);
			$message = '';
			if (isset($_POST['count']) and intval($_POST['count']) != $nmaps) {
				$message = 'Search results have changed, paging context was lost.';
			}
			$can_edit = false;
			if (current_user_can(self::EDIT_CAPABILITY)) {
				$can_edit = true;
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
				$return_maps = self::fetch_maps($title, $location, $terrain, $min_dist, $max_dist, $media, $offset, $limit);
				$response = array(
					'can_edit' => $can_edit,
					'count' => $nmaps,
					'offset' => $offset,
					'maps' => $return_maps);
				if ($message != '') {
					$response['message'] = $message;
				}
				echo wp_json_encode($response);
			}
			else {
				$return_maps = self::fetch_maps($title, $location, $terrain, $min_dist, $max_dist, $media);
				$response = array(
					'can_edit' => $can_edit,
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
			return '<div class="callout small warning"><p>Please log in to view the map library.</p></div>';
		}
		else {
			ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 
			function populate_maps_table(maps, can_edit) {
				var copylink = '<a title="Copy map title to clipboard." class="copy-btn"><?php echo self::COPY_ANCHOR_LABEL ?></a>';
				var header = '<table class="pwtc-mapdb-rwd-table">' +
					'<thead><tr><th>Title</th><th>Distance</th><th>Terrain</th>';
				if (can_edit) {
					header += '<th>Actions</th>';
				}
				header += '</tr></thead><tbody></tbody></table>';
				$('#pwtc-mapdb-maps-div').append(header);
				maps.forEach(function(item) {
					var data = '<tr postid="' + item.ID + '">' +
					'<td><span>Title</span>' + copylink + ' ' + item.media + item.title + '</a></td>' +
					'<td><span>Distance</span>' + item.distance + '</td>' +
					'<td><span>Terrain</span>' + item.terrain + '</td>';
					if (can_edit) {
						data += '<td><span>Actions</span>' + item.edit + '</td>'
					}
					data += '</tr>';
					$('#pwtc-mapdb-maps-div table tbody').append(data);    
				});
				$('#pwtc-mapdb-maps-div table .copy-btn').on('click', function(evt) {
					//var title = $(this).parent().parent().find('td').first().find('span').first()[0];
					var title = $(this).parent().find('a').first().next()[0];
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
				$('#pwtc-mapdb-maps-div').append(
					'<form class="page-frm">' +
                    '<input class="prev-btn dark button" style="margin: 0" type="button" value="< Prev"/>' +
					'<span style="margin: 0 10px">Page ' + pagenum + ' of ' + numpages + '</span>' +
                    '<input class="next-btn dark button" style="margin: 0" type="button" value="Next >"/>' +
					'<span class="page-msg" style="margin: 0 10px"></span>' +
					'<input name="offset" type="hidden" value="' + offset + '"/>' +
					'<input name="count" type="hidden" value="' + count + '"/>' +
					'</form>'
				);
				$('#pwtc-mapdb-maps-div .page-frm .prev-btn').on('click', function(evt) {
					evt.preventDefault();
					load_maps_table('prev');
				});
				if (pagenum == 1) {
					$('#pwtc-mapdb-maps-div .page-frm .prev-btn').attr("disabled", "disabled");
				}
				else {
					$('#pwtc-mapdb-maps-div .page-frm .prev-btn').removeAttr("disabled");
				}
				$('#pwtc-mapdb-maps-div .page-frm .next-btn').on('click', function(evt) {
					evt.preventDefault();
					load_maps_table('next');
				});
				if (pagenum == numpages) {
					$('#pwtc-mapdb-maps-div .page-frm .next-btn').attr("disabled", "disabled");
				}
				else {
					$('#pwtc-mapdb-maps-div .page-frm .next-btn').removeAttr("disabled");
				}
			}

			function lookup_maps_cb(response) {
				var res = JSON.parse(response);
				$('#pwtc-mapdb-maps-div').empty();
				if (res.error) {
					$('#pwtc-mapdb-maps-div').append(
						'<div class="callout small alert"><p>' + res.error + '</p></div>');
				}
				else {
					if (res.message !== undefined) {
						$('#pwtc-mapdb-maps-div').append(
							'<div class="callout small warning"><p>' + res.message + '</p></div>');
					}
					if (res.maps.length > 0) {
						populate_maps_table(res.maps, res.can_edit);
						if (res.offset !== undefined) {
							create_paging_form(res.offset, res.count);
						}
					}
					else {
						$('#pwtc-mapdb-maps-div').append(
							'<div class="callout small warning"><p>No maps found.</p></div>');
					}
				}
				$('body').removeClass('pwtc-mapdb-waiting');
			}   

			function load_maps_table(mode) {
				var action = $('#pwtc-mapdb-search-div .search-frm').attr('action');
				var data = {
					'action': 'pwtc_mapdb_lookup_maps',
					'limit': <?php echo $a['limit'] ?>
				};
				if (mode != 'search') {
					data.title = $("#pwtc-mapdb-search-div .search-frm input[name='title_sav']").val();
					data.location = '';
					data.terrain = $("#pwtc-mapdb-search-div .search-frm input[name='terrain_sav']").val();
					data.distance = $("#pwtc-mapdb-search-div .search-frm input[name='distance_sav']").val();
					data.media = '0';
					var offset = $("#pwtc-mapdb-maps-div .page-frm input[name='offset']").val();
					var count = $("#pwtc-mapdb-maps-div .page-frm input[name='count']").val();
					data.offset = offset;
					data.count = count;
					if (mode == 'prev') {
						data.prev = 1;
					}
					else if (mode == 'next') {
						data.next = 1;						
					}
					$('#pwtc-mapdb-maps-div .page-frm .page-msg').html('<i class="fa fa-spinner fa-pulse"></i> Loading...');
				}
				else {
					data.title = $("#pwtc-mapdb-search-div .search-frm input[name='title']").val().trim();
					data.location = '';
					data.terrain = $('#pwtc-mapdb-search-div .search-frm .terrain').val();
					data.distance = $('#pwtc-mapdb-search-div .search-frm .distance').val();
					data.media = '0';
					$("#pwtc-mapdb-search-div .search-frm input[name='title_sav']").val(data.title);
					$("#pwtc-mapdb-search-div .search-frm input[name='terrain_sav']").val(data.terrain);
					$("#pwtc-mapdb-search-div .search-frm input[name='distance_sav']").val(data.distance);
					$('#pwtc-mapdb-maps-div').html('<i class="fa fa-spinner fa-pulse"></i> Loading...');
				}
				$('body').addClass('pwtc-mapdb-waiting');
				$.post(action, data, lookup_maps_cb); 
			}

			$('#pwtc-mapdb-search-div .search-frm').on('submit', function(evt) {
				evt.preventDefault();
				load_maps_table('search');
			});

			$('#pwtc-mapdb-search-div .search-frm .reset-btn').on('click', function(evt) {
				evt.preventDefault();
				$("#pwtc-mapdb-search-div .search-frm input[type='text']").val(''); 
				$('#pwtc-mapdb-search-div .search-frm select').val('0');
				$('#pwtc-mapdb-maps-div').empty();
				load_maps_table('search');
			});

			load_maps_table('search');
		});
	</script>

	<div id='pwtc-mapdb-search-div'>
	<ul class="accordion" data-accordion data-allow-all-closed="true">
		<li class="accordion-item" data-accordion-item>
            <a href="#" class="accordion-title"><i class="fa fa-search"></i> Click Here To Search</a>
            <div class="accordion-content" data-tab-content>
				<form class="search-frm" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
				<input type="hidden" name="title_sav" value=""/>
				<input type="hidden" name="distance_sav" value=""/>
				<input type="hidden" name="terrain_sav" value=""/>
				<div>
				<div class="row">
                    <div class="small-12 medium-4 columns">
                        <label>Title
							<input type="text" name="title"/>
                        </label>
                    </div>
                    <div class="small-12 medium-4 columns">
                        <label>Distance
							<select class="distance">
            					<option value="0" selected>Any</option> 
           	 					<option value="1">0-25 miles</option>
            					<option value="2">25-50 miles</option>
            					<option value="3">50-75 miles</option>
            					<option value="4">75-100 miles</option>
            					<option value="5">&gt; 100 miles</option>
        					</select>		
                        </label>
                    </div>
                    <div class="small-12 medium-4 columns">
                        <label>Terrain
							<select class="terrain">
            					<option value="0" selected>Any</option> 
            					<option value="a">A (flat)</option>
            					<option value="b">B (gently rolling)</option>
            					<option value="c">C (short steep hills)</option>
            					<option value="d">D (longer hills)</option>
            					<option value="e">E (mountainous)</option>
        					</select>
                        </label>
                    </div>
				</div>
				<div class="row column">
					<input class="accent button" type="submit" value="Search"/>
					<input class="reset-btn accent button" type="button" value="Reset"/>
                </div>
				</div>
				</form>
			</div>
		</li>
	</ul>
	</div>
	<div id="pwtc-mapdb-maps-div"></div>
	<?php
			return ob_get_clean();
		}
	}
	
	// Generates the [pwtc_mapdb_rider_signup] shortcode.
	public static function shortcode_rider_signup($atts) {
		$error = self::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$error = self::check_post_id();
		if (!empty($error)) {
			return $error;
		}
		$postid = intval($_GET['post']);

		$ride_title = esc_html(get_the_title($postid));
		$ride_link = esc_url(get_the_permalink($postid));
		$return_to_ride = 'Click <a href="' . $ride_link . '">here</a> to return to the posted ride.';

		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>You must log in <a href="/wp-login.php">here</a> to sign up for this ride. ' . $return_to_ride . '</p></div>';
		}
		
		if (get_field(self::RIDE_CANCELED, $postid)) {
			return '<div class="callout small warning"><p>The ride "' . $ride_title . '" has been canceled, no sign up allowed. ' . $return_to_ride . '</p></div>';
		}

		if (!in_array(self::ROLE_CURRENT_MEMBER, (array) $current_user->roles) and 
		    !in_array(self::ROLE_EXPIRED_MEMBER, (array) $current_user->roles)) {
			return '<div class="callout small warning"><p>You must be a club member to sign up for rides. ' . $return_to_ride . '</p></div>';
		}
		
		$expired = false;
		if (in_array(self::ROLE_EXPIRED_MEMBER, (array) $current_user->roles)) {
			$expired = true;
		}
		
		//self::init_online_signup($postid);
		
		$ride_signup_mode = get_post_meta($postid, self::RIDE_SIGNUP_MODE, true);
		if (!$ride_signup_mode) {
			$ride_signup_mode = 'no';
		}

		$ride_signup_cutoff = get_post_meta($postid, self::RIDE_SIGNUP_CUTOFF, true);
		if (!$ride_signup_cutoff) {
			$ride_signup_cutoff = 0;
		}
		
		$ride_signup_limit = get_post_meta($postid, self::RIDE_SIGNUP_LIMIT, true);
		if (!$ride_signup_limit) {
			$ride_signup_limit = 0;
		}

		if ($ride_signup_mode == 'paperless') {
			$set_mileage = true;
		}
		else if ($ride_signup_mode == 'hardcopy') {
			$set_mileage = false;
		}
		else {
			return '<div class="callout small warning"><p>The leader for ride "' . $ride_title . '" does not allow online sign up. ' . $return_to_ride . '</p></div>';			
		}

		$error = self::check_ride_start($postid, $ride_signup_mode, $ride_signup_cutoff, $return_to_ride);
		if (!empty($error)) {
			return $error;
		}

		$signup_locked = get_post_meta($postid, self::RIDE_SIGNUP_LOCKED, true);
		if ($signup_locked) {
			return '<div class="callout small warning"><p>You cannot sign up for ride "' . $ride_title . '" because it is closed. ' . $return_to_ride . '</p></div>';	
		}
		
		$mileage = '';
		if (isset($_POST['mileage'])) {
			if (!empty(trim($_POST['mileage']))) {
				$mileage = abs(intval($_POST['mileage']));
			}
		}

		if (isset($_POST['accept_user_signup'])) {
			self::delete_all_signups($postid, $current_user->ID);
			$value = json_encode(array('userid' => $current_user->ID, 'mileage' => ''.$mileage, 'attended' => true));
			add_post_meta($postid, self::RIDE_SIGNUP_USERID, $value);
		}

		if (isset($_POST['cancel_user_signup'])) {
			self::delete_all_signups($postid, $current_user->ID);
		}

		if (isset($_POST['contact_phone'])) {
			if (function_exists('pwtc_members_format_phone_number')) {
				$phone = pwtc_members_format_phone_number($_POST['contact_phone']);
			}
			else {
				$phone = sanitize_text_field($_POST['contact_phone']);
			}
			update_field(self::USER_EMER_PHONE, $phone, 'user_'.$current_user->ID);
		}

		if (isset($_POST['contact_name'])) {
			$name = sanitize_text_field($_POST['contact_name']);
			update_field(self::USER_EMER_NAME, $name, 'user_'.$current_user->ID);
		}
		
		if (isset($_POST['accept_terms'])) {
			if ($_POST['accept_terms'] == 'yes') {
				update_field(self::USER_RELEASE_ACCEPTED, true, 'user_'.$current_user->ID);
			}
		}

		$signup_list = get_post_meta($postid, self::RIDE_SIGNUP_USERID);
		$accept_signup = true;
		foreach($signup_list as $item) {
			$arr = json_decode($item, true);
			if ($arr['userid'] == $current_user->ID) {
				$accept_signup = false;
			}
		}

		if ($accept_signup and $ride_signup_limit > 0) {
			if (count($signup_list)+count(get_post_meta($postid, self::RIDE_SIGNUP_NONMEMBER)) >= $ride_signup_limit) {
				return '<div class="callout small warning"><p>You cannot sign up for ride "' . $ride_title . '" because it is full. <em>A maximum of ' . $ride_signup_limit . ' riders are allowed on this ride.</em> ' . $return_to_ride . '</p></div>';
			}
		}

		$user_info = get_userdata($current_user->ID);
		$rider_name = $user_info->first_name . ' ' . $user_info->last_name;
		$contact_phone = get_field(self::USER_EMER_PHONE, 'user_'.$current_user->ID);
		$contact_name = get_field(self::USER_EMER_NAME, 'user_'.$current_user->ID);
		$release_accepted = get_field(self::USER_RELEASE_ACCEPTED, 'user_'.$current_user->ID);

		ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 

			function show_waiting() {
				$('#pwtc-mapdb-rider-signup-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
			}

			$('#pwtc-mapdb-rider-signup-div form').on('submit', function(evt) {
				<?php if ($accept_signup) { ?>
				$(this).find("select[name='accept_terms'] option:selected").each(function() {
					var accept_terms = $(this).val();
					if (accept_terms == 'no') {
						$('#pwtc-mapdb-rider-signup-div .errmsg').html('<div class="callout small warning"><p>You must accept the Club&#39;s <a href="/terms-and-conditions" target="_blank">terms and conditions</a> to sign up for rides.</p></div>');
						evt.preventDefault();
					}
					else {
						show_waiting();
					}
				});
				<?php } else { ?>
				show_waiting();
				<?php } ?>
     			});

		});
	</script>

	<div id='pwtc-mapdb-rider-signup-div'>
		<?php if ($accept_signup) { ?>
			<div class="callout">
				<p>
				Hello <?php echo $rider_name; ?>, to sign up for the ride "<?php echo $ride_title; ?>," please accept the Club's <a href="/terms-and-conditions" target="_blank">terms and conditions</a>, enter your emergency contact information<?php if ($set_mileage) { ?>, enter the mileage that you intend to ride<?php } ?> and press the accept button.
			<?php if ($set_mileage) { ?> 
				<em>You may ask the leader to change your mileage at ride start if desired. If you don't want your mileage logged, leave the mileage field blank.</em>
			<?php } ?>
				</p>
				<form method="POST">
					<div class="row">
						<div class="small-12 medium-6 columns">
							<label>Accept Terms and Conditions
								<select name="accept_terms">
									<option value="no" <?php echo $release_accepted ? '': 'selected'; ?>>No</option>
									<option value="yes" <?php echo $release_accepted ? 'selected': ''; ?>>Yes</option>
								</select>
							</label>
						</div>
						<div class="small-12 medium-6 columns">
							<label><i class="fa fa-phone"></i> Emergency Contact Phone
								<input type="text" name="contact_phone" value="<?php echo $contact_phone; ?>"/>
							</label>
						</div>
						<div class="small-12 medium-6 columns">
							<label>Emergency Contact Name
								<input type="text" name="contact_name" value="<?php echo $contact_name; ?>"/>
							</label>
						</div>
			<?php if ($set_mileage) { ?>
						<div class="small-12 medium-6 columns">
							<label>Mileage
								<input type="number" name="mileage" value="<?php echo $mileage; ?>" maxlength="3" />
							</label>
						</div>
			<?php } ?>
					</div>
					<div class="row column errmsg">
			<?php if ($expired) { ?>
						<div class="callout small warning"><p>Your club membership has expired, please renew. While expired members may still sign up for rides, your mileage will not be logged.</p></div>
			<?php } ?>
					</div>
					<div class="row column clearfix">
						<input type="hidden" name="accept_user_signup" value="yes"/>
						<button class="dark button float-left" type="submit"><i class="fa fa-user-plus"></i> Accept Sign-up</button>
						<a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
					</div>
				</form>
			</div>
		<?php } else { ?>
			<div class="callout">
				<p>Hello <?php echo $rider_name; ?>, you are currently signed up for the ride "<?php echo $ride_title; ?>." To cancel your sign up, please press the cancel button below.</p>
				<form method="POST">
					<div class="row column errmsg"></div>
					<div class="row column clearfix">
						<input type="hidden" name="cancel_user_signup" value="yes"/>
						<button class="dark button float-left" type="submit"><i class="fa fa-user-times"></i> Cancel Sign-up</button>
						<a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
					</div>
				</form>
			</div>
		<?php } ?>
	</div>
	<?php
		return ob_get_clean();
	}
	
	// Generates the [pwtc_mapdb_view_signup] shortcode.
	public static function shortcode_view_signup($atts) {
		$a = shortcode_atts(array('unused_rows' => 0), $atts);
		$unused_rows = abs(intval($a['unused_rows']));
		
		$error = self::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$error = self::check_post_id();
		if (!empty($error)) {
			return $error;
		}
		$postid = intval($_GET['post']);
		
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to view the ride sign up list.</p></div>';
		}
		
		$ride_title = esc_html(get_the_title($postid));
		$ride_link = esc_url(get_the_permalink($postid));
		$return_to_ride = 'Click <a href="' . $ride_link . '">here</a> to return to the posted ride.';
		
		if (get_field(self::RIDE_CANCELED, $postid)) {
			return '<div class="callout small warning"><p>The ride "' . $ride_title . '" has been canceled, no sign up view allowed. ' . $return_to_ride . '</p></div>';
		}

		if (!user_can($current_user,'edit_published_rides')) {
			$denied = true;
			$leaders = self::get_leader_userids($postid);
			foreach ($leaders as $item) {
				if ($current_user->ID == $item) {
					$denied = false;
					break;
				}
			}
			if ($denied) {
				return '<div class="callout small warning"><p>You must be a leader for ride "' . $ride_title . '" to view sign ups. ' . $return_to_ride . '</p></div>';
			}
		}
		
		if (isset($_POST['ride_signup_mode'])) {
			delete_post_meta($postid, self::RIDE_SIGNUP_MODE);
			add_post_meta($postid, self::RIDE_SIGNUP_MODE, $_POST['ride_signup_mode'], true);
		}

		if (isset($_POST['ride_signup_cutoff'])) {
			delete_post_meta($postid, self::RIDE_SIGNUP_CUTOFF);
			add_post_meta($postid, self::RIDE_SIGNUP_CUTOFF, abs(intval($_POST['ride_signup_cutoff'])), true);
		}
		
		if (isset($_POST['ride_signup_limit'])) {
			delete_post_meta($postid, self::RIDE_SIGNUP_LIMIT);
			add_post_meta($postid, self::RIDE_SIGNUP_LIMIT, abs(intval($_POST['ride_signup_limit'])), true);
		}
		
		if (isset($_POST['signup_members_only'])) {
			delete_post_meta($postid, self::RIDE_SIGNUP_MEMBERS_ONLY);
			add_post_meta($postid, self::RIDE_SIGNUP_MEMBERS_ONLY, $_POST['signup_members_only'] == 'yes', true);
		}
		
		//self::init_online_signup($postid);
		
		$ride_signup_mode = get_post_meta($postid, self::RIDE_SIGNUP_MODE, true);
		if (!$ride_signup_mode) {
			$ride_signup_mode = 'no';
		}

		$ride_signup_cutoff = get_post_meta($postid, self::RIDE_SIGNUP_CUTOFF, true);
		if (!$ride_signup_cutoff) {
			$ride_signup_cutoff = 0;
		}
		
		$ride_signup_limit = get_post_meta($postid, self::RIDE_SIGNUP_LIMIT, true);
		if (!$ride_signup_limit) {
			$ride_signup_limit = 0;
		}
		
		$signup_members_only = get_post_meta($postid, self::RIDE_SIGNUP_MEMBERS_ONLY, true);

		if ($ride_signup_mode != 'no') {
			if ($ride_signup_mode == 'paperless') {
				$paperless = $set_mileage = $take_attendance = true;
				$cutoff_units = '(hours after ride start)';
			}
			else {
				$paperless = $set_mileage = $take_attendance = false;
				$cutoff_units = '(hours before ride start)';
			}

			$now_date = self::get_current_time();
			$cutoff_date = self::get_signup_cutoff_time($postid, $ride_signup_mode, $ride_signup_cutoff);
			$cutoff_date_str = $cutoff_date->format('m/d/Y g:ia');

			$signup_list = get_post_meta($postid, self::RIDE_SIGNUP_USERID);
			$nonmember_signup_list = get_post_meta($postid, self::RIDE_SIGNUP_NONMEMBER);

			if (isset($_POST['lock_signup'])) {
				add_post_meta($postid, self::RIDE_SIGNUP_LOCKED, true, true);
			}
			else if (isset($_POST['unlock_signup'])) {
				delete_post_meta( $postid, self::RIDE_SIGNUP_LOCKED);
			}
			$signup_locked = get_post_meta($postid, self::RIDE_SIGNUP_LOCKED, true);
			if ($signup_locked) {
				$set_mileage = $take_attendance = false;
			}
		}
		else {
			$cutoff_units = '(hours)';
		}
		
		ob_start();
	?>

	<script type="text/javascript">
		jQuery(document).ready(function($) { 

			$.fn.setCursorPosition = function(pos) {
  				this.each(function(index, elem) {
    				if (elem.setSelectionRange) {
      					elem.setSelectionRange(pos, pos);
    				} else if (elem.createTextRange) {
      					var range = elem.createTextRange();
      					range.collapse(true);
      					range.moveEnd('character', pos);
      					range.moveStart('character', pos);
      					range.select();
    				}
  				});
  				return this;
			};

			function show_errmsg(message) {
				$('#pwtc-mapdb-view-signup-div .errmsg').html('<div class="callout small alert">' + message + '</div>');
			}

			function clear_errmsg() {
				$('#pwtc-mapdb-view-signup-div .errmsg').html('');
			}
			
			function show_errmsg2(message) {
				$('#pwtc-mapdb-view-signup-div .errmsg2').html('<div class="callout small alert">' + message + '</div>');
			}

			function show_errmsg2_success(message) {
				$('#pwtc-mapdb-view-signup-div .errmsg2').html('<div class="callout small success">' + message + '</div>');
			}
			
			function show_errmsg2_warning(message) {
				$('#pwtc-mapdb-view-signup-div .errmsg2').html('<div class="callout small warning">' + message + '</div>');
			}

			function show_errmsg2_wait() {
				$('#pwtc-mapdb-view-signup-div .errmsg2').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
			}

			function clear_errmsg2() {
				$('#pwtc-mapdb-view-signup-div .errmsg2').html('');
			}
			
			function show_errmsg3_wait() {
				$('#pwtc-mapdb-view-signup-div .errmsg3').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
			}
		
		<?php if ($ride_signup_mode != 'no') { ?>

			function reset_mileage_cell() {
				$('#pwtc-mapdb-view-signup-div table tbody td[mileage] input').each(function() {
					var cell = $(this).parent();
					cell.html('<span>Mileage</span>' + cell.attr('mileage'));
				});
			}

			function reset_attended_cell() {
				$('#pwtc-mapdb-view-signup-div table tbody td[attended] a').each(function() {
					$(this).remove();
				});				
			}

			function reset_waiting_icon() {
				$('#pwtc-mapdb-view-signup-div table tbody td[mileage] .waiting').each(function() {
					var cell = $(this).parent();
					cell.html('<span>Mileage</span>' + cell.attr('mileage'));
				});

				$('#pwtc-mapdb-view-signup-div table tbody td[attended] .waiting').each(function() {
					var cell = $(this).parent();
					$(this).remove();
					cell.find('i').remove();
					if (cell.attr('attended') == '0') {
						cell.find('span').after('<i class="fa fa-times"></i>');
					}
				});			
			}

			function change_signup_cb(response) {
				var res;
				try {
					res = JSON.parse(response);
				}
				catch (e) {
					show_errmsg(e.message);
					reset_waiting_icon();
					return;
				}
				if (res.error) {
					show_errmsg(res.error);
					reset_waiting_icon();
				}
				else {
					if (res.postid == <?php echo $postid ?>) {
						var row = $('#pwtc-mapdb-view-signup-div table tbody tr[userid="' + res.userid + '"]');
						var mcell = row.find('td[mileage]');
						mcell.attr('mileage', function() {
							return res.mileage;
						});
						mcell.html('<span>Mileage</span>' + res.mileage);
						var acell = row.find('td[attended]');
						acell.attr('attended', function() {
							return res.attended;
						});
						acell.find('i').remove();
						if (res.attended == '0') {
							acell.find('span').after('<i class="fa fa-times"></i>');
						}
					}
					else {
						show_errmsg('Ride post ID does not match post ID returned by server.');
						reset_waiting_icon();
					}
				}
			}
				       
			function change_nonmember_signup_cb(response) {
				var res;
				try {
					res = JSON.parse(response);
				}
				catch (e) {
					show_errmsg(e.message);
					reset_waiting_icon();
					return;
				}
				if (res.error) {
					show_errmsg(res.error);
					reset_waiting_icon();
				}
				else {
					if (res.postid == <?php echo $postid ?>) {
						var row = $('#pwtc-mapdb-view-signup-div table tbody tr[signup_id="' + res.signup_id + '"]');
						var acell = row.find('td[attended]');
						acell.attr('attended', function() {
							return res.attended;
						});
						acell.find('i').remove();
						if (res.attended == '0') {
							acell.find('span').after('<i class="fa fa-times"></i>');
						}
					}
					else {
						show_errmsg('Ride post ID does not match post ID returned by server.');
						reset_waiting_icon();
					}
				}
			}
			
			function log_mileage_cb(response) {
				var res;
				try {
					res = JSON.parse(response);
				}
				catch (e) {
					show_errmsg2(e.message);
					return;
				}
				if (res.error) {
					show_errmsg2(res.error);
				}
				else {
					if (res.postid == <?php echo $postid ?>) {
						var warning = false;
						var msg = 'Mileage was logged successfully, ' + res.num_leaders + ' ride leaders were recorded and ' + res.num_riders + ' riders were recorded.';
						if (res.expired_riders.length > 0) {
							msg += '<br>The following riders were NOT recorded because of expired membership:';
							res.expired_riders.forEach(function(item) {
								msg += ' ' + item;
							});
							msg += '.';
							warning = true;
						}
						if (res.missing_riders.length > 0) {
							msg += '<br>The following riders were NOT recorded because of missing rider IDs:';
							res.missing_riders.forEach(function(item) {
								msg += ' ' + item;
							});
							msg += '.';
							warning = true;
						}
						if (res.missing_leaders.length > 0) {
							msg += '<br>The following ride leaders were NOT recorded because of missing rider IDs:';
							res.missing_leaders.forEach(function(item) {
								msg += ' ' + item;
							});
							msg += '.';
							warning = true;
						}
						if (warning) {
							show_errmsg2_warning(msg);
						}
						else {
							show_errmsg2_success(msg);
						}
					}
					else {
						show_errmsg2('Ride post ID does not match post ID returned by server.');
					}
				}
			}

			function change_signup_setting(userid, oldmileage, mileage, oldattended, attended) {
				var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_mapdb_edit_signup',
					'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_edit_signup'); ?>',
					'postid': '<?php echo $postid ?>',
					'userid': userid,
					'oldmileage': oldmileage,
					'mileage': mileage,
					'oldattended': oldattended,
					'attended': attended
				};
				$.post(action, data, change_signup_cb);
			}
		
			function change_nonmember_signup_setting(signup_id, oldattended, attended) {
				var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_mapdb_edit_nonmember_signup',
					'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_edit_nonmember_signup'); ?>',
					'postid': '<?php echo $postid ?>',
					'signup_id': signup_id,
					'oldattended': oldattended,
					'attended': attended
				};
				$.post(action, data, change_nonmember_signup_cb);
			}

			<?php if ($set_mileage) { ?>
			$('#pwtc-mapdb-view-signup-div table tbody td[mileage]').on('click', function(evt) {
				reset_mileage_cell();
				clear_errmsg();
				reset_attended_cell();
				var cell = $(this);
				if (cell.attr('mileage') != 'XXX') {
					var row = cell.parent();
					cell.html('<span>Mileage</span><input type="number" value="' + cell.attr('mileage') + '" style="width:50%" maxlength="3" />');
					var input = cell.find('input');
					input.on('click', function(e) {
						e.stopPropagation();
					});
					input.on('keypress', function(e) {
    						if (e.which == 13) {
							change_signup_setting(
								row.attr('userid'), 
								cell.attr('mileage'), 
								input.val(), 
								row.find('td[attended]').attr('attended'), 
								row.find('td[attended]').attr('attended'));
							cell.html('<span>Mileage</span><i class="fa fa-spinner fa-pulse waiting"></i> ');
   						}
					});
					input.focus();
					//input.setCursorPosition(3);
				}
			});
			<?php } ?>

			<?php if ($take_attendance) { ?>
			$('#pwtc-mapdb-view-signup-div table tbody td[attended]').on('click', function(evt) {
				reset_mileage_cell();
				clear_errmsg();
				reset_attended_cell();
				var cell = $(this);
				var row = cell.parent();
				if (cell.attr('attended') == '1') {
					cell.append('<a><i class="fa fa-thumbs-down"></i></a>');
					var link = cell.find('a');
					link.on('click', function(e) {
						e.stopPropagation();
						link.remove();
						if (row.attr('userid')) {
							change_signup_setting(
								row.attr('userid'), 
								row.find('td[mileage]').attr('mileage'), 
								row.find('td[mileage]').attr('mileage'), 
								cell.attr('attended'), 
								'0');
						}
						else {
							change_nonmember_signup_setting(
								row.attr('signup_id'), 
								cell.attr('attended'), 
								'0');
						}						
						cell.find('i').remove();
						cell.find('span').after('<i class="fa fa-spinner fa-pulse waiting"></i>');
					});
				}
				else {
					cell.append('<a><i class="fa fa-thumbs-up"></i></a>');
					var link = cell.find('a');
					link.on('click', function(e) {
						e.stopPropagation();
						link.remove();
						if (row.attr('userid')) {
							change_signup_setting(
								row.attr('userid'), 
								row.find('td[mileage]').attr('mileage'), 
								row.find('td[mileage]').attr('mileage'), 
								cell.attr('attended'), 
								'1');
						}
						else {
							change_nonmember_signup_setting(
								row.attr('signup_id'), 
								cell.attr('attended'), 
								'1');
						}
						cell.find('i').remove();
						cell.find('span').after('<i class="fa fa-spinner fa-pulse waiting"></i>');
					});
				}
			});
			<?php } ?>
		
			$('#pwtc-mapdb-view-signup-div .log_mileage').on('click', function(evt) {
				var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_mapdb_log_mileage',
					'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_log_mileage'); ?>',
					'postid': '<?php echo $postid ?>'
				};
				$.post(action, data, log_mileage_cb);
				show_errmsg2_wait();
			});
		
		
			<?php if ($paperless and !$signup_locked) { ?>
			$('#pwtc-mapdb-view-signup-div .show_more').on('click', function(evt) {
				$(this).hide();
				$('#pwtc-mapdb-view-signup-div .more_details').show();
			});

			$('#pwtc-mapdb-view-signup-div .show_less').on('click', function(evt) {
				$('#pwtc-mapdb-view-signup-div .more_details').hide();
				$('#pwtc-mapdb-view-signup-div .show_more').show();
			});
			<?php } ?>
		
		<?php } ?>
		
			$("#pwtc-mapdb-view-signup-div .accordion form select[name='ride_signup_mode']").change(function() {
				$(this).find('option:selected').each(function() {
					var mode = $(this).val();
					var label = '(hours)';
					if (mode == 'paperless') {
						label = '(hours after ride start)';
					} else if (mode == 'hardcopy') {
						label = '(hours before ride start)';
					}
					$('#pwtc-mapdb-view-signup-div .cutoff_units').html(label);
				});
			});

			$('#pwtc-mapdb-view-signup-div .accordion form').on('submit', function(evt) {
				show_errmsg3_wait();
     			});

		});
	</script>
	<div id='pwtc-mapdb-view-signup-div'>
		<ul class="accordion" data-accordion data-allow-all-closed="true">
			<li class="accordion-item" data-accordion-item>
            			<a href="#" class="accordion-title">Click Here For Sign-up Options</a>
            			<div class="accordion-content" data-tab-content>
					<form method="POST">
						<div class="row">
							<div class="small-12 medium-4 columns">
								<label>Online Ride Sign-up
									<select name="ride_signup_mode">
										<option value="no" <?php echo $ride_signup_mode == 'no' ? 'selected': ''; ?>>No</option>
										<option value="hardcopy"  <?php echo $ride_signup_mode == 'hardcopy' ? 'selected': ''; ?>>Hardcopy (requires computer/printer)</option>
										<option value="paperless"  <?php echo $ride_signup_mode == 'paperless' ? 'selected': ''; ?>>Paperless (requires smartphone)</option>
									</select>
								</label>
							</div>
							<div class="small-12 medium-4 columns">
								<label>Sign-up Cutoff <span class="cutoff_units"><?php echo $cutoff_units; ?></span>
									<input type="number" name="ride_signup_cutoff" value="<?php echo $ride_signup_cutoff; ?>"/>
								</label>
							</div>
							<div class="small-12 medium-4 columns">
								<label>Sign-up Count Limit (0 means unlimited)
									<input type="number" name="ride_signup_limit" value="<?php echo $ride_signup_limit; ?>"/>
								</label>
							</div>
							<div class="small-12 medium-4 columns">
								<label>Club Members Only
									<select name="signup_members_only">
										<option value="no" <?php echo $signup_members_only ? '': 'selected'; ?>>No</option>
										<option value="yes"  <?php echo $signup_members_only ? 'selected': ''; ?>>Yes</option>
									</select>
								</label>
							</div>
						</div>
						<div class="row column errmsg3"></div>
						<div class="row column clearfix">
							<input class="accent button float-left" type="submit" value="Submit"/>
						</div>
					</form>
				</div>
			</li>
		</ul>		
		<?php if ($ride_signup_mode == 'no') { ?>
			<div class="callout small"><p>Online sign up is not enabled for ride "<?php echo $ride_title; ?>." <?php echo $return_to_ride; ?></p></div>
		<?php } else { ?>
		<?php if (count($signup_list) > 0 or count($nonmember_signup_list) > 0) { ?>
			<p>The following riders are currently signed up for the ride "<?php echo $ride_title; ?>."
			<?php if ($paperless and !$signup_locked) { ?>
			<a class="show_more">more&gt;</a><span class="more_details" style="display: none"><strong>To mark a rider as NOT present for the ride:</strong> (1) press the rider&#39;s name, (2) press the <i class="fa fa-thumbs-down"></i> icon after it appears and (3) a <i class="fa fa-times"></i> icon will mark the rider as not present. <strong>To modify a rider&#39;s mileage for the ride:</strong> (1) press the rider&#39;s mileage, (2) type the new mileage into the entry field after it appears and (3) press the enter key to accept the change. <a class="show_less">&lt;less</a><span>
			<?php } ?>
			</p> 
			<div class="errmsg"></div>
			<table class="pwtc-mapdb-rwd-table"><thead><tr><th>Name</th><th>Rider ID</th><?php if ($paperless) { ?><th>Mileage</th><?php } ?><th>Emergency Contact</th></tr></thead><tbody>
			<?php foreach($signup_list as $item) { 
				$arr = json_decode($item, true);
				$userid = $arr['userid'];
				$user_info = get_userdata($userid);
				if ($user_info) {
					$name = $user_info->first_name . ' ' . $user_info->last_name;
				}
				else {
					$name = 'Unknown';
				}
				if ($paperless) {
					$mileage = $arr['mileage'];
					$mileage_label = $mileage;
					$attended = $arr['attended'];
					/*
					if (in_array(self::ROLE_EXPIRED_MEMBER, (array) $user_info->roles)) {
						$mileage = 'XXX';
						$mileage_label = 'expired';
					}
					*/
				}
				else {
					$attended = true;
				}
				$rider_id = self::get_rider_id($userid);
				$contact = self::get_emergency_contact($userid, true);
			?>
				<tr userid="<?php echo $userid; ?>">
				<td attended="<?php echo $attended ? '1':'0'; ?>"><span>Name</span><?php echo $attended ? '':'<i class="fa fa-times"></i>'; ?> <?php echo $name; ?> </td>
				<td><span>Rider ID</span><?php echo $rider_id; ?></td>
				<?php if ($paperless) { ?>
				<td mileage="<?php echo $mileage; ?>"><span>Mileage</span><?php echo $mileage_label; ?></td>
				<?php } ?>
				<td><span>Emergency Contact</span><?php echo $contact; ?></td>
				</tr>
			<?php } ?>
			<?php foreach($nonmember_signup_list as $item) { 
				$arr = json_decode($item, true);
				$signup_id = $arr['signup_id'];
				$name = $arr['name'];
				$contact_phone = $arr['contact_phone'];
				$contact_name = $arr['contact_name'];
				$contact = self::get_nonmember_emergency_contact($contact_phone, $contact_name, true);
				if ($paperless) {
					$mileage = 'XXX';
					$mileage_label = 'n/a';
					$attended = $arr['attended'];
				}
				else {
					$attended = true;
				}
			?>
				<tr signup_id="<?php echo $signup_id; ?>">
				<td attended="<?php echo $attended ? '1':'0'; ?>"><span>Name</span><?php echo $attended ? '':'<i class="fa fa-times"></i>'; ?> <?php echo $name; ?> </td>
				<td><span>Rider ID</span>n/a</td>
				<?php if ($paperless) { ?>
				<td mileage="<?php echo $mileage; ?>"><span>Mileage</span><?php echo $mileage_label; ?></td>
				<?php } ?>
				<td><span>Emergency Contact</span><?php echo $contact; ?></td>
				</tr>
			<?php } ?>
			</tbody></table>
		<?php } else { ?>
			<div class="callout small"><p>There are currently no riders signed up for the ride "<?php echo $ride_title; ?>."</p></div>
		<?php } ?>
		<?php if ($signup_locked) { ?>
			<?php if ($paperless) { ?>
				<div class="callout small success"><p>Online sign up is closed, you may now log the rider mileage to the mileage database.</p></div>
			<?php } else { ?>
				<div class="callout small success"><p>Online sign up is closed, you may now download the ride sign-in sheet and print it.</p></div>
			<?php } ?>
		<?php } else { ?>
			<?php if ($now_date < $cutoff_date) { ?>
				<div class="callout small warning"><p>Online sign up is allowed until <?php echo $cutoff_date_str; ?>, you cannot close it until then.</p></div>
			<?php } else { ?>
				<div class="callout small success"><p>The period for online sign up is past, you may now close it.</p></div>
			<?php } ?>
		<?php } ?>
		<div class="errmsg2"></div>
		<div class="row column clearfix">
			<form method="POST">
		<?php if ($signup_locked) { ?>
				<div class="button-group float-left">
			<?php if ($paperless) { ?>
				<a class="log_mileage dark button"><i class="fa fa-bicycle"></i> Log Mileage</a>
			<?php } else { ?>
				<input type="hidden" name="ride_id" value="<?php echo $postid; ?>"/>
				<input type="hidden" name="unused_rows" value="<?php echo $unused_rows; ?>"/>
				<button class="dark button" type="submit" name="pwtc_mapdb_download_signup"><i class="fa fa-download"></i> Sign-in Sheet</button>
			<?php } ?>
				<button class="dark button" type="submit" name="unlock_signup"><i class="fa fa-unlock"></i> Reopen Sign-up</button>
				</div>
		<?php } else { ?>
				<button class="dark button float-left" type="submit" name="lock_signup" <?php echo $now_date < $cutoff_date ? 'disabled': ''; ?>><i class="fa fa-lock"></i> Close Sign-up</button>
		<?php } ?>
				<a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
			</form>
		</div>
		<?php } ?>
	</div>
	<?php
		return ob_get_clean();
	}

	// Generates the [pwtc_mapdb_download_signup] shortcode.
	public static function shortcode_download_signup($atts) {
		$error = self::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}
		
		ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 
		});
	</script>

	<div id='pwtc-mapdb-download-signup-div'>
		<form method="POST">
			<button class="dark button" type="submit" name="pwtc_mapdb_download_signup"><i class="fa fa-download"></i> Ride Sign-in Sheet</button>
		</form>
	</div>
	<?php
		return ob_get_clean();
	}
	
	// Generates the [pwtc_mapdb_nonmember_signup] shortcode.
	public static function shortcode_nonmember_signup($atts) {
		$error = self::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$error = self::check_post_id();
		if (!empty($error)) {
			return $error;
		}
		$postid = intval($_GET['post']);

		/*
		$current_user = wp_get_current_user();
		if ( 0 != $current_user->ID ) {
			return '<div class="callout small alert"><p>This page is only for non-member ride sign up and you must be logged out to use it.</p></div>';
		}
		*/

		$ride_title = esc_html(get_the_title($postid));
		$ride_link = esc_url(get_the_permalink($postid));
		$return_to_ride = 'Click <a href="' . $ride_link . '">here</a> to return to the posted ride.';

		$timestamp = time() - self::TIMESTAMP_OFFSET;
		
		if (get_field(self::RIDE_CANCELED, $postid)) {
			return '<div class="callout small warning"><p>The ride "' . $ride_title . '" has been canceled, no sign up allowed. ' . $return_to_ride . '</p></div>';
		}
		
		//self::init_online_signup($postid);

		$ride_signup_mode = get_post_meta($postid, self::RIDE_SIGNUP_MODE, true);
		if (!$ride_signup_mode) {
			$ride_signup_mode = 'no';
		}

		$ride_signup_cutoff = get_post_meta($postid, self::RIDE_SIGNUP_CUTOFF, true);
		if (!$ride_signup_cutoff) {
			$ride_signup_cutoff = 0;
		}
		
		$ride_signup_limit = get_post_meta($postid, self::RIDE_SIGNUP_LIMIT, true);
		if (!$ride_signup_limit) {
			$ride_signup_limit = 0;
		}
		
		$members_only = get_post_meta($postid, self::RIDE_SIGNUP_MEMBERS_ONLY, true);

		if ($ride_signup_mode == 'no') {
			return '<div class="callout small warning"><p>The leader for ride "' . $ride_title . '" does not allow online sign up. ' . $return_to_ride . '</p></div>';			
		}
		
		if ($members_only) {
			return '<div class="callout small warning"><p>Only club members may attend ride "' . $ride_title . '." ' . $return_to_ride . '</p></div>';
		}

		$error = self::check_ride_start($postid, $ride_signup_mode, $ride_signup_cutoff, $return_to_ride);
		if (!empty($error)) {
			return $error;
		}

		$signup_locked = get_post_meta($postid, self::RIDE_SIGNUP_LOCKED, true);
		if ($signup_locked) {
			return '<div class="callout small warning"><p>You cannot sign up for ride "' . $ride_title . '" because it is closed. ' . $return_to_ride . '</p></div>';	
		}

		ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 

			function clear_warnmsg() {
				$('#pwtc-mapdb-nonmember-signup-div .accept_div .warnmsg').html('');
			}

			function show_warnmsg(message) {
				$('#pwtc-mapdb-nonmember-signup-div .accept_div .warnmsg').html('<div class="callout small warning">' + message + '</div>');
			}

			function clear_errmsg() {
				$('#pwtc-mapdb-nonmember-signup-div .errmsg').html('');
			}

			function show_errmsg(message) {
				$('#pwtc-mapdb-nonmember-signup-div .errmsg').html('<div class="callout small alert">' + message + '</div>');
			}

			function show_waiting() {
				$('#pwtc-mapdb-nonmember-signup-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
			}

			function set_accept_form() {
				var form = $('#pwtc-mapdb-nonmember-signup-div .accept_div form');
				var your_name = window.localStorage.getItem('<?php echo self::LOCAL_SIGNUP_NAME; ?>');
				if (!your_name) {
					your_name = '';
				}
				form.find('input[name="your_name"]').val(your_name);
				var contact_phone = window.localStorage.getItem('<?php echo self::LOCAL_EMER_PHONE; ?>');
				if (!contact_phone) {
					contact_phone = '';
				}
				form.find('input[name="contact_phone"]').val(contact_phone);
				var contact_name = window.localStorage.getItem('<?php echo self::LOCAL_EMER_NAME; ?>');
				if (!contact_name) {
					contact_name = '';	
				}	
				form.find('input[name="contact_name"]').val(contact_name);			
			}

			function check_signup_cb(response) {
				var res;
				try {
					res = JSON.parse(response);
				}
				catch (e) {
					show_errmsg(e.message);
					return;
				}
				if (res.error) {
					show_errmsg(res.error);
				}
				else {
					if (res.postid == <?php echo $postid ?>) {
						clear_errmsg();
						if (res.found) {
							$('#pwtc-mapdb-nonmember-signup-div .accept_div').hide();
							$('#pwtc-mapdb-nonmember-signup-div .cancel_div .your_name').html(res.signup_name);
							$('#pwtc-mapdb-nonmember-signup-div .cancel_div').show();
						}
						else {
							$('#pwtc-mapdb-nonmember-signup-div .cancel_div').hide();
							clear_warnmsg();
							set_accept_form();
							$('#pwtc-mapdb-nonmember-signup-div .accept_div').show();
						}
					}
					else {
						show_errmsg('Ride post ID does not match post ID returned by server.');
					}
				}
			}

			function accept_signup_cb(response) {
				var res;
				try {
					res = JSON.parse(response);
				}
				catch (e) {
					show_errmsg(e.message);
					return;
				}
				if (res.error) {
					show_errmsg(res.error);
				}
				else {
					if (res.postid == <?php echo $postid ?>) {
						//TODO: verify that sign up IDs match!
						clear_errmsg();
						if (res.warning) {
							show_warnmsg(res.warning);
							$('#pwtc-mapdb-nonmember-signup-div .accept_div').show();
						}
						else {
							window.localStorage.setItem('<?php echo self::LOCAL_SIGNUP_NAME; ?>', res.signup_name);
							window.localStorage.setItem('<?php echo self::LOCAL_EMER_NAME; ?>', res.signup_contact_name);
							window.localStorage.setItem('<?php echo self::LOCAL_EMER_PHONE; ?>', res.signup_contact_phone);
							$('#pwtc-mapdb-nonmember-signup-div .accept_div').hide();
							$('#pwtc-mapdb-nonmember-signup-div .cancel_div .your_name').html(res.signup_name);
							$('#pwtc-mapdb-nonmember-signup-div .cancel_div').show();
						}
					}
					else {
						show_errmsg('Ride post ID does not match post ID returned by server.');
					}
				}
			}

			function cancel_signup_cb(response) {
				var res;
				try {
					res = JSON.parse(response);
				}
				catch (e) {
					show_errmsg(e.message);
					return;
				}
				if (res.error) {
					show_errmsg(res.error);
				}
				else {
					if (res.postid == <?php echo $postid ?>) {
						//TODO: verify that sign up IDs match!
						clear_errmsg();
						$('#pwtc-mapdb-nonmember-signup-div .cancel_div').hide();
						clear_warnmsg();
						set_accept_form();
						$('#pwtc-mapdb-nonmember-signup-div .accept_div').show();
					}
					else {
						show_errmsg('Ride post ID does not match post ID returned by server.');
					}
				}
			}

			$('#pwtc-mapdb-nonmember-signup-div .accept_div form').on('submit', function(evt) {
				evt.preventDefault();
				var accept_terms = 'no';
				$(this).find("select[name='accept_terms'] option:selected").each(function() {
					accept_terms = $(this).val();
				});
				var your_name = $(this).find('input[name="your_name"]').val().trim();
				if (accept_terms == 'no') {
					show_warnmsg('You must accept the Club&#39;s <a href="/terms-and-conditions" target="_blank">terms and conditions</a> to sign up for rides.');
				}
				else if (your_name) {
					clear_warnmsg();
					var signup_id = window.localStorage.getItem('<?php echo self::LOCAL_SIGNUP_ID; ?>');
					var contact_phone = $(this).find('input[name="contact_phone"]').val().trim();
					var contact_name = $(this).find('input[name="contact_name"]').val().trim();
					var action = "<?php echo admin_url('admin-ajax.php'); ?>";
					var data = {
						'action': 'pwtc_mapdb_accept_nonmember_signup',
						'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_accept_nonmember_signup'); ?>',
						'postid': '<?php echo $postid ?>',
						'signup_id': signup_id,
						'signup_name': your_name,
						'signup_contact_phone': contact_phone,
						'signup_contact_name': contact_name,
						'signup_limit': <?php echo $ride_signup_limit; ?>
					};
					$.post(action, data, accept_signup_cb);
					$('#pwtc-mapdb-nonmember-signup-div .accept_div').hide();
					show_waiting();
				}
				else {
					show_warnmsg('Your name must be specified.')
				}
			});

			$('#pwtc-mapdb-nonmember-signup-div .cancel_div form').on('submit', function(evt) {
				evt.preventDefault();
				var signup_id = window.localStorage.getItem('<?php echo self::LOCAL_SIGNUP_ID; ?>');
				var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_mapdb_cancel_nonmember_signup',
					'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_cancel_nonmember_signup'); ?>',
					'postid': '<?php echo $postid ?>',
					'signup_id': signup_id
				};
				$.post(action, data, cancel_signup_cb);
				$('#pwtc-mapdb-nonmember-signup-div .cancel_div').hide();
				show_waiting();
			});

			if (window.localStorage) {
				var signup_id = window.localStorage.getItem('<?php echo self::LOCAL_SIGNUP_ID; ?>');
				if (!signup_id) {
					signup_id = '<?php echo $timestamp ?>';
					window.localStorage.setItem('<?php echo self::LOCAL_SIGNUP_ID; ?>', signup_id);
				}

				var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_mapdb_check_nonmember_signup',
					'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_check_nonmember_signup'); ?>',
					'postid': '<?php echo $postid ?>',
					'signup_id': signup_id
				};
				$.post(action, data, check_signup_cb);
				show_waiting();
			}
			else {
				show_errmsg('You cannot sign up because your browser does not support local storage.');
			}
		});
	</script>

	<div id='pwtc-mapdb-nonmember-signup-div'>
		<div class="callout small warning"><p>ONLY non-members should use this page to sign up for rides. If you are a club member, first log in <a href="/wp-login.php">here</a> before signing up for a ride.</p></div>
		<div class="errmsg"></div>
		<div class="accept_div callout" style="display: none">
			<p>To sign up for the ride "<?php echo $ride_title; ?>," please accept the Club's <a href="/terms-and-conditions" target="_blank">terms and conditions</a>, enter your name and emergency contact information and press the accept button.</p>
			<form method="POST">
				<div class="row">
					<div class="small-12 medium-6 columns">
						<label>Accept Terms and Conditions
							<select name="accept_terms">
								<option value="no" selected>No</option>
								<option value="yes">Yes</option>
							</select>
						</label>
					</div>
					<div class="small-12 medium-6 columns">
						<label>Your Name
							<input type="text" name="your_name" value=""/>
						</label>
					</div>
					<div class="small-12 medium-6 columns">
						<label><i class="fa fa-phone"></i> Emergency Contact Phone
							<input type="text" name="contact_phone" value=""/>
						</label>
					</div>
					<div class="small-12 medium-6 columns">
						<label>Emergency Contact Name
							<input type="text" name="contact_name" value=""/>
						</label>
					</div>
				</div>
				<div class="warnmsg"></div>
				<div class="row column clearfix">
					<button class="dark button float-left" type="submit"><i class="fa fa-user-plus"></i> Accept Sign-up</button>
					<a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
				</div>
			</form>
		</div>
		<div class="cancel_div callout" style="display: none">
			<p>Hello <span class="your_name"></span>, you are currently signed up for the ride "<?php echo $ride_title; ?>." To cancel your sign up, please press the cancel button below.</p>
			<form method="POST">
				<div class="row column clearfix">
					<button class="dark button float-left" type="submit"><i class="fa fa-user-times"></i> Cancel Sign-up</button>
					<a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
				</div>
			</form>
		</div>
	</div>

	<?php
		return ob_get_clean();
	}
	
	// Generates the [pwtc_mapdb_show_userid_signups] shortcode.
	public static function shortcode_show_userid_signups($atts) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to view the rides for which you have signed up.</p></div>';
		}

		$user_info = get_userdata($current_user->ID);
		$rider_name = $user_info->first_name . ' ' . $user_info->last_name;

		$now = self::get_current_time();
		$query_args = [
			'posts_per_page' => -1,
			'post_type' => self::POST_TYPE_RIDE,
			'meta_query' => [
				[
					'key' => self::RIDE_DATE,
					'value' => $now->format('Y-m-d 00:00:00'),
					'compare' => '>=',
					'type' => 'DATETIME'
				],
				[
					'key' => self::RIDE_SIGNUP_USERID,
					'value' => '"userid":' . $current_user->ID . ',',
					'compare' => 'LIKE'
				],
			],
			'orderby' => [self::RIDE_DATE => 'ASC'],
		];		
		$query = new WP_Query($query_args);

		if (!$query->have_posts()) {
			return '<div class="callout small"><p>Hello ' . $rider_name . ', you are not signed up for any upcoming rides.</p></div>';
		}

		ob_start();
		?>

	<div id="pwtc-mapdb-show-signups-div">
		<p>Hello <?php echo $rider_name; ?>, you are signed up for the following upcoming rides.</p>
		<table class="pwtc-mapdb-rwd-table">
			<thead><tr><th>Start Time</th><th>Ride Title</th></tr></thead>
			<tbody>
		<?php

		while ($query->have_posts()) {
			$query->the_post();
			$title = esc_html(get_the_title());
            		$link = esc_url(get_the_permalink());
			$start = DateTime::createFromFormat('Y-m-d H:i:s', get_field('date'))->format('m/d/Y g:ia');
		?>
			<tr>
				<td><span>Start Time</span><?php echo $start; ?></td>
				<td><span>Ride Title</span><a href="<?php echo $link; ?>"><?php echo $title; ?></a></td>	
			</tr>
		<?php
		}
		wp_reset_postdata();
		?>
			</tbody>
		</table>
	</div>
		<?php
		return ob_get_clean();
	}
	
	// Generates the [pwtc_mapdb_edit_ride] shortcode.
	public static function shortcode_edit_ride($atts) {
		$error = self::check_post_id();
		if (!empty($error)) {
			return $error;
		}
		$postid = intval($_GET['post']);

		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to edit this ride.</p></div>';
		}

		$ride_title = esc_html(get_the_title($postid));
		$ride_link = esc_url(get_the_permalink($postid));
		$return_to_ride = 'Click <a href="' . $ride_link . '">here</a> to return to the posted ride.';

		if (!user_can($current_user,'edit_published_rides')) {
			$denied = true;
			$leaders = self::get_leader_userids($postid);
			foreach ($leaders as $item) {
				if ($current_user->ID == $item) {
					$denied = false;
					break;
				}
			}
			if ($denied) {
				return '<div class="callout small warning"><p>You must be a leader for ride "' . $ride_title . '" to edit it. ' . $return_to_ride . '</p></div>';
			}
		}

		$now_date = self::get_current_time();
		$ride_date = self::get_ride_start_time($postid);
		if ($ride_date < $now_date) {
			return '<div class="callout small warning"><p>Ride "' . $ride_title . '" has already started so you cannot edit it. ' . $return_to_ride . '</p></div>';
		}

		if (isset($_POST['title'])) {
			$my_post = array(
				'ID' => $postid,
				'post_title' => esc_html(trim($_POST['title']))
			);
			wp_update_post( $my_post );			
		}

		if (isset($_POST['description'])) {
			update_field('description', $_POST['description'], $postid);
		}
		
		if (isset($_POST['leaders'])) {
			$new_leaders = json_decode($_POST['leaders']);
			update_field('ride_leaders', $new_leaders, $postid);
		}
		
		if (isset($_POST['maps'])) {
			$new_maps = json_decode($_POST['maps']);
			update_field('maps', $new_maps, $postid);
		}
		
		if (isset($_POST['distance'])) {
			update_field('length', intval($_POST['distance']), $postid);
		}

		if (isset($_POST['max_distance'])) {
			$d = trim($_POST['max_distance']);
			if (empty($d)) {
				update_field('max_length', null, $postid);
			}
			else {
				update_field('max_length', intval($d), $postid);
			}
		}

		if (isset($_POST['ride_type'])) {
			update_field('type', $_POST['ride_type'], $postid);
		}

		if (isset($_POST['ride_pace'])) {
			update_field('pace', $_POST['ride_pace'], $postid);
		}

		if (isset($_POST['ride_terrain'])) {
			update_field('terrain', $_POST['ride_terrain'], $postid);
		}

		$post = get_post($postid);
		$title = esc_html($post->post_title);
		$description = get_field('description', $postid, false);
		$leaders = self::get_leader_userids($postid);
		$distance = get_field('length', $postid);
		$max_distance = get_field('max_length', $postid);
		$ride_type = get_field('type', $postid);
		$ride_pace = get_field('pace', $postid);
		$ride_terrain = get_field('terrain', $postid);
		$attach_map = get_field('attach_map', $postid);
		$maps_obj = get_field('maps', $postid);
		$maps = [];
		foreach ($maps_obj as $map) {
			$maps[] = $map->ID;
		}
		
		ob_start();
	?>
	<style>
		#pwtc-mapdb-edit-ride-div .maps-div div {
			margin: 10px; 
			padding: 10px; 
			border: 1px solid;
		}
		#pwtc-mapdb-edit-ride-div .maps-div div i {
			cursor: pointer;
		}
		#pwtc-mapdb-edit-ride-div .map-search-div table tr {
			cursor: pointer;
		}
		#pwtc-mapdb-edit-ride-div .map-search-div table tr:hover {
			font-weight: bold;
		}
		#pwtc-mapdb-edit-ride-div .map-search-div table td {
			padding: 3px;
			vertical-align: top;
		}
		#pwtc-mapdb-edit-ride-div .map-search-div table tr:nth-child(odd) {
			background-color: #f2f2f2;
		}
		#pwtc-mapdb-edit-ride-div .leaders-div div {
			margin: 10px; 
			padding: 10px; 
			border: 1px solid;
		}
		#pwtc-mapdb-edit-ride-div .leaders-div div i {
			cursor: pointer;
		}
		#pwtc-mapdb-edit-ride-div .leader-search-div ul {
			list-style-type: none;
		}
		#pwtc-mapdb-edit-ride-div .leader-search-div li {
			cursor: pointer;
		}
		#pwtc-mapdb-edit-ride-div .leader-search-div li:hover {
			font-weight: bold;
		}
	</style>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 

			function show_warning(msg) {
				$('#pwtc-mapdb-edit-ride-div .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
			}

			function show_waiting() {
				$('#pwtc-mapdb-edit-ride-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
			}
			
			function leaders_lookup_cb(response) {
				var res;
				try {
					res = JSON.parse(response);
				}
				catch (e) {
					$('#pwtc-mapdb-edit-ride-div .leader-search-div').html('<div class="callout small alert"><p>' + e.message + '</p></div>');
					return;
				}
				if (res.error) {
					$('#pwtc-mapdb-edit-ride-div .leader-search-div').html('<div class="callout small alert"><p>' + res.error + '</p></div>');
				}
				else {
					$('#pwtc-mapdb-edit-ride-div .leader-search-div').empty();
					$('#pwtc-mapdb-edit-ride-div .leader-search-div').append('<ul></ul>');
					res.users.forEach(function(item) {
            					$('#pwtc-mapdb-edit-ride-div .leader-search-div ul').append(
							'<li userid="' + item.userid + '">' + item.first_name + ' ' + item.last_name + '</li>');    
					});
					$('#pwtc-mapdb-edit-ride-div .leader-search-div li').on('click', function(evt) {
						var userid = $(this).attr('userid');
						var name = $(this).html();
						$('#pwtc-mapdb-edit-ride-div .leaders-div').append('<div userid="' + userid + '"><i class="fa fa-times"></i> ' + name + '</div>').find('i').on('click', function(evt) {
							$(this).parent().remove();
						});
					});
				}
			}
			
			function maps_lookup_cb(response) {
				var res;
				try {
					res = JSON.parse(response);
				}
				catch (e) {
					$('#pwtc-mapdb-edit-ride-div .map-search-div').html('<div class="callout small alert"><p>' + e.message + '</p></div>');
					return;
				}
				if (res.error) {
					$('#pwtc-mapdb-edit-ride-div .map-search-div').html('<div class="callout small alert"><p>' + res.error + '</p></div>');
				}
				else {
					$('#pwtc-mapdb-edit-ride-div .map-search-div').empty();
					$('#pwtc-mapdb-edit-ride-div .map-search-div').append('<table></table>');
					res.maps.forEach(function(item) {
            					$('#pwtc-mapdb-edit-ride-div .map-search-div table').append(
							'<tr mapid="' + item.ID + '"><td>' + item.title + '</td><td>' + item.distance + '</td><td>' + item.terrain + '</td></tr>');    
					});
					$('#pwtc-mapdb-edit-ride-div .map-search-div tr').on('click', function(evt) {
						var mapid = $(this).attr('mapid');
						var title = $(this).find('td').first().html();
						$('#pwtc-mapdb-edit-ride-div .maps-div').append('<div mapid="' + mapid + '"><i class="fa fa-times"></i> ' + title + '</div>').find('i').on('click', function(evt) {
							$(this).parent().remove();
						});
					});
				}
			}
			
			$('#pwtc-mapdb-edit-ride-div .leaders-div i').on('click', function(evt) {
				$(this).parent().remove();
			});
			
			$('#pwtc-mapdb-edit-ride-div .maps-div i').on('click', function(evt) {
				$(this).parent().remove();
			});

			$('#pwtc-mapdb-edit-ride-div input[name="search-leaders"]').on('click', function(evt) {
				var searchstr = $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').val();
				var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_mapdb_lookup_ride_leaders',
					'search': searchstr
				};
				$.post(action, data, leaders_lookup_cb);
				$('#pwtc-mapdb-edit-ride-div .leader-search-div').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');		
			});
			
			$('#pwtc-mapdb-edit-ride-div input[name="search-maps"]').on('click', function(evt) {
				var searchstr = $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').val();
				var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_mapdb_lookup_maps',
					'limit': 10,
					'title': searchstr,
					'location': '',
					'terrain': 0,
					'distance': 0,
					'media': 0
				};
				$.post(action, data, maps_lookup_cb);
				$('#pwtc-mapdb-edit-ride-div .map-search-div').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');		
			});

			$('#pwtc-mapdb-edit-ride-div form').on('submit', function(evt) {
				var new_leaders = [];
				$('#pwtc-mapdb-edit-ride-div .leaders-div div').each(function() {
					var userid = Number($(this).attr('userid'));
					new_leaders.push(userid); 
				});
				var new_maps = [];
				$('#pwtc-mapdb-edit-ride-div .maps-div div').each(function() {
					var mapid = Number($(this).attr('mapid'));
					new_maps.push(mapid); 
				});
				if ($('#pwtc-mapdb-edit-ride-div input[name="title"]').val().trim().length == 0) {
					show_warning('The ride title cannot be blank.');
					evt.preventDefault();
					return;
				}
				if ($('#pwtc-mapdb-edit-ride-div textarea[name="description"]').val().trim().length == 0) {
					show_warning('The ride description cannot be blank.');
					evt.preventDefault();
					return;
				}
				if (new_maps.length == 0) {
					show_warning('You must attach at least one map to this ride.');
					evt.preventDefault();
					return;
				}
				if (new_leaders.length == 0) {
					show_warning('You must assign at least one leader to this ride.');
					evt.preventDefault();
					return;
				}
				$('#pwtc-mapdb-edit-ride-div input[name="leaders"]').val(JSON.stringify(new_leaders));
				$('#pwtc-mapdb-edit-ride-div input[name="maps"]').val(JSON.stringify(new_maps));
				show_waiting();
			});

		});
	</script>
	<div id='pwtc-mapdb-edit-ride-div'>
		<div class="callout">
			<form method="POST">
				<div class="row column">
					<label>Ride Title
						<input type="text" name="title" value="<?php echo $title; ?>"/>
					</label>
				</div>
				<div class="row column">
					<label>Ride Description
						<textarea name="description" rows="10"><?php echo $description; ?></textarea>
					</label>
				</div>
				<div class="row">
					<div class="small-12 medium-6 columns">
						<label>Ride Type
						<fieldset>
    						<input type="radio" name="ride_type" value="nongroup" id="type-nongroup" <?php echo $ride_type == 'nongroup' ? 'checked': ''; ?>><label for="type-nongroup">Non-group</label>
    						<input type="radio" name="ride_type" value="group" id="type-group" <?php echo $ride_type == 'group' ? 'checked': ''; ?>><label for="type-group">Group</label>
    						<input type="radio" name="ride_type" value="regroup" id="type-regroup" <?php echo $ride_type == 'regroup' ? 'checked': ''; ?>><label for="type-regroup">Re-group</label>
  						</fieldset>
						</label>  					
					</div>
					<div class="small-12 medium-6 columns">
						<label>Ride Pace
						<fieldset>
    						<input type="radio" name="ride_pace" value="no" id="pace-na" <?php echo $ride_pace == 'no' ? 'checked': ''; ?>><label for="pace-na">N/A</label>
    						<input type="radio" name="ride_pace" value="slow" id="pace-slow" <?php echo $ride_pace == 'slow' ? 'checked': ''; ?>><label for="pace-slow">Slow</label>
    						<input type="radio" name="ride_pace" value="leisurely" id="pace-leisurely" <?php echo $ride_pace == 'leisurely' ? 'checked': ''; ?>><label for="pace-leisurely">Leisurely</label>
							<input type="radio" name="ride_pace" value="moderate" id="pace-moderate" <?php echo $ride_pace == 'moderate' ? 'checked': ''; ?>><label for="pace-moderate">Moderate</label>
							<input type="radio" name="ride_pace" value="fast" id="pace-fast" <?php echo $ride_pace == 'fast' ? 'checked': ''; ?>><label for="pace-fast">Fast</label>
  						</fieldset>
						</label>  					
					</div>
				</div>
				<div class="row">
					<div class="small-12 medium-4 columns">
						<label>Ride Terrain
						<fieldset>
    						<input type="checkbox" name="ride_terrain[]" value="a" id="terrain-a" <?php echo in_array('a', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-a">A</label>
							<input type="checkbox" name="ride_terrain[]" value="b" id="terrain-b" <?php echo in_array('b', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-b">B</label>
							<input type="checkbox" name="ride_terrain[]" value="c" id="terrain-c" <?php echo in_array('c', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-c">C</label>
							<input type="checkbox" name="ride_terrain[]" value="d" id="terrain-d" <?php echo in_array('d', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-d">D</label>
							<input type="checkbox" name="ride_terrain[]" value="e" id="terrain-e" <?php echo in_array('e', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-e">E</label>
  						</fieldset>
						</label>				
  					</div>
					<div class="small-12 medium-4 columns">
						<label>Ride Distance
							<input type="number" name="distance" value="<?php echo $distance; ?>"/>	
						</label>
					</div>
					<div class="small-12 medium-4 columns">
						<label>Ride Max Distance
							<input type="number" name="max_distance" value="<?php echo $max_distance; ?>"/>	
						</label>
					</div>
				</div>
				<div class="row column">
					<label>Ride Maps
						<input type="hidden" name="maps" value="<?php echo json_encode($maps); ?>"/>	
					</label>
				</div>
				<div class="row column">
					<div class= "maps-div" style="border:1px solid; display:flex; flex-wrap:wrap;">
						<?php foreach ($maps_obj as $map) { ?>
						<div mapid="<?php echo $map->ID; ?>"><i class="fa fa-times"></i> <?php echo $map->post_title; ?></div>
						<?php } ?>
					</div>
				</div>
				<div class="row column">
					<ul class="accordion" data-accordion data-allow-all-closed="true">
						<li class="accordion-item" data-accordion-item>
            						<a href="#" class="accordion-title">Add Ride Map...</a>
            						<div class="accordion-content" data-tab-content>
								<div class="row column">
									<div class="input-group">
										<input class="input-group-field" type="text" name="map-pattern" placeholder="Enter map name">
										<div class="input-group-button">
											<input type="button" class="dark button" name= "search-maps" value="Search">
										</div>
									</div>
								</div>
								<div class="row column">
									<div class="map-search-div" style="border:1px solid; overflow: auto; height: 100px;">
									</div>
								</div>
							</div>
						</li>
					</ul>					
				</div>
				<div class="row column">
					<label>Ride Leaders
						<input type="hidden" name="leaders" value="<?php echo json_encode($leaders); ?>"/>	
					</label>
				</div>
				<div class="row column">
					<div class= "leaders-div" style="border:1px solid; display:flex; flex-wrap:wrap;">
						<?php foreach ($leaders as $leader) {
							$info = get_userdata($leader);
							$name = $info->first_name . ' ' . $info->last_name;
						?>
						<div userid="<?php echo $leader; ?>"><i class="fa fa-times"></i> <?php echo $name; ?></div>
						<?php } ?>
					</div>
				</div>
				<div class="row column">
					<ul class="accordion" data-accordion data-allow-all-closed="true">
						<li class="accordion-item" data-accordion-item>
            						<a href="#" class="accordion-title">Add Ride Leader...</a>
            						<div class="accordion-content" data-tab-content>
								<div class="row column">
									<div class="input-group">
										<input class="input-group-field" type="text" name="leader-pattern" placeholder="Enter leader name">
										<div class="input-group-button">
											<input type="button" class="dark button" name= "search-leaders" value="Search">
										</div>
									</div>
								</div>
								<div class="row column">
									<div class="leader-search-div" style="border:1px solid; overflow: auto; height: 100px;">
									</div>
								</div>
							</div>
						</li>
					</ul>					
				</div>
				<div class="row column errmsg"></div>
				<div class="row column clearfix">
					<button class="dark button float-left" type="submit">Submit</button>
					<a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
				</div>
			</form>
		</div>
	</div>
	<?php
		return ob_get_clean();
	}
	
	public static function check_plugin_dependency() {
		if (!defined('PWTC_MILEAGE__PLUGIN_DIR')) {
			return '<div class="callout small alert"><p>Cannot render shortcode, PWTC Mileage plugin is required.</p></div>';
		}

		if (!defined('PWTC_MEMBERS__PLUGIN_DIR')) {
			return '<div class="callout small alert"><p>Cannot render shortcode, PWTC Members plugin is required.</p></div>';
		}

		return '';
	}

	public static function check_post_id() {
		if (!isset($_GET['post'])) {
			return '<div class="callout small alert"><p>Cannot render shortcode, missing post ID parameter.</p></div>';
		}

		$postid = intval($_GET['post']);
		if ($postid == 0) {
			return '<div class="callout small alert"><p>Cannot render shortcode, post ID is invalid.</p></div>';
		}

		$post = get_post($postid);
		if (!$post) {
			return '<div class="callout small alert"><p>Cannot render shortcode, post does not exist.</p></div>';
		}

		if (get_post_type($post) != self::POST_TYPE_RIDE) {
			return '<div class="callout small alert"><p>Cannot render shortcode, post type is not a scheduled ride.</p></div>';
		}
		
		return '';
	}
	
	public static function get_leader_userids($postid) {
		$leaders = get_field(self::RIDE_LEADERS, $postid);
		$userids = [];
		foreach ($leaders as $leader) {
			$userids[] = $leader['ID'];
		}
		return $userids;		
	}
	
	public static function init_online_signup($postid) {
		$leaders = self::get_leader_userids($postid);
		if (count(get_post_meta($postid, self::RIDE_SIGNUP_MODE)) == 0) {
			if (count($leaders) > 0) {
				$mode = get_field(self::USER_SIGNUP_MODE, 'user_'.$leaders[0]);
				if (!$mode) {
					$mode = 'no';
				}
				add_post_meta($postid, self::RIDE_SIGNUP_MODE, $mode, true);
			}
			else {
				add_post_meta($postid, self::RIDE_SIGNUP_MODE, 'no', true);
			}
		}

		if (count(get_post_meta($postid, self::RIDE_SIGNUP_CUTOFF)) == 0) {
			if (count($leaders) > 0) {
				add_post_meta($postid, self::RIDE_SIGNUP_CUTOFF, abs(intval(get_field(self::USER_SIGNUP_CUTOFF, 'user_'.$leaders[0]))), true);
			}
			else {
				add_post_meta($postid, self::RIDE_SIGNUP_CUTOFF, 0, true);
			}
		}

		if (count(get_post_meta($postid, self::RIDE_SIGNUP_LIMIT)) == 0) {
			add_post_meta($postid, self::RIDE_SIGNUP_LIMIT, 0, true);
		}

		if (count(get_post_meta($postid, self::RIDE_SIGNUP_MEMBERS_ONLY)) == 0) {
			add_post_meta($postid, self::RIDE_SIGNUP_MEMBERS_ONLY, false, true);
		}
	}
	
	public static function get_signup_cutoff_time($postid, $mode, $pad) {
		$ride_date = self::get_ride_start_time($postid);
		if ($pad > 0) {
			if ($mode == 'paperless') {
				$interval = new DateInterval('PT' . $pad . 'H');	
				$ride_date->add($interval);
			}
			else if ($mode == 'hardcopy') {
				$interval = new DateInterval('PT' . $pad . 'H');	
				$ride_date->sub($interval);
			}
		}
		return $ride_date;
	}

	public static function get_ride_start_time($postid) {
		$timezone = new DateTimeZone(pwtc_get_timezone_string());
		$ride_date = DateTime::createFromFormat('Y-m-d H:i:s', get_field(self::RIDE_DATE, $postid), $timezone);
		return $ride_date;
	}

	public static function get_current_time() {
		$timezone = new DateTimeZone(pwtc_get_timezone_string());
		$now_date = new DateTime(null, $timezone);
		return $now_date;
	}
	
	public static function check_ride_start($postid, $mode, $pad, $return_to_ride) {
		$ride_title = esc_html(get_the_title($postid));
		$now_date = self::get_current_time();
		$now_date_str = $now_date->format('m/d/Y g:ia');
		$cutoff_date = self::get_signup_cutoff_time($postid, $mode, $pad);
		$cutoff_date_str = $cutoff_date->format('m/d/Y g:ia');
		if ($now_date > $cutoff_date) {
			return '<div class="callout small warning"><p>You cannot sign up for ride "' . $ride_title . '" because you are past the sign up cutoff time at ' . $cutoff_date_str . '. ' . $return_to_ride . '</p></div>';
		}
		return '';
	}
	
	// Generates the [pwtc_mapdb_leader_details] shortcode.
	public static function shortcode_leader_details($atts) {
		if (!defined('PWTC_MEMBERS__PLUGIN_DIR')) {
			ob_start();
			?>
			<div class="callout alert"><p>Cannot render shortcode, PWTC Members plugin is required.</p></div>
			<?php
			return ob_get_clean();
		}
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			ob_start();
			?>
			<div class="callout warning"><p>You must be logged in to edit your ride leader details information.</p></div>		
			<?php
			return ob_get_clean();
		}
		$userid = $current_user->ID;
		$user_info = get_userdata( $userid );
		if (!in_array(self::ROLE_RIDE_LEADER, $user_info->roles)) {
			ob_start();
			?>
			<div class="callout warning"><p>You must be a ride leader to edit your details information.</p></div>		
			<?php
			return ob_get_clean();
		}
		if (isset($_POST['use_contact_email'])) {
			if ($_POST['use_contact_email'] == 'yes') {
				update_field(self::USER_USE_EMAIL, true, 'user_'.$userid);
			}
			else {
				update_field(self::USER_USE_EMAIL, false, 'user_'.$userid);
			}
		}
		if (isset($_POST['ride_signup_mode'])) {
			update_field(self::USER_SIGNUP_MODE, $_POST['ride_signup_mode'], 'user_'.$userid);
		}
		if (isset($_POST['contact_email'])) {
			update_field(self::USER_CONTACT_EMAIL, sanitize_email($_POST['contact_email']), 'user_'.$userid);
		}
		if (isset($_POST['voice_phone'])) {
			update_field(self::USER_CELL_PHONE, pwtc_members_format_phone_number($_POST['voice_phone']), 'user_'.$userid);
		}
		if (isset($_POST['text_phone'])) {
			update_field(self::USER_HOME_PHONE, pwtc_members_format_phone_number($_POST['text_phone']), 'user_'.$userid);
		}
		if (isset($_POST['signup_cutoff'])) {
			$val = abs(intval($_POST['signup_cutoff']));
			update_field(self::USER_SIGNUP_CUTOFF, $val, 'user_'.$userid);
		}
		$voice_phone = pwtc_members_format_phone_number(get_field(self::USER_CELL_PHONE, 'user_'.$userid));
		$text_phone = pwtc_members_format_phone_number(get_field(self::USER_HOME_PHONE, 'user_'.$userid));
		$contact_email = get_field(self::USER_CONTACT_EMAIL, 'user_'.$userid);
		$use_contact_email = get_field(self::USER_USE_EMAIL, 'user_'.$userid);
		$ride_signup_mode = get_field(self::USER_SIGNUP_MODE, 'user_'.$userid);
		if (!$ride_signup_mode) {
			$ride_signup_mode = 'no';
		}
		
		if ($ride_signup_mode == 'paperless') {
			$cutoff_units = '(hours after ride start)';
		}
		else if ($ride_signup_mode == 'hardcopy') {
			$cutoff_units = '(hours before ride start)';
		}
		else {
			$cutoff_units = '(hours)';
		}

		$signup_cutoff = abs(intval(get_field(self::USER_SIGNUP_CUTOFF, 'user_'.$userid)));
		
		ob_start();
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) { 

				$("#pwtc-mapdb-leader-details-div select[name='ride_signup_mode']").change(function() {
					$(this).find('option:selected').each(function() {
						var mode = $(this).val();
						var label = '(hours)';
						if (mode == 'paperless') {
							label = '(hours after ride start)';
						} else if (mode == 'hardcopy') {
							label = '(hours before ride start)';
						}
						$('#pwtc-mapdb-leader-details-div .cutoff_units').html(label);
					});
				});

			});
		</script>
		<div id="pwtc-mapdb-leader-details-div" class="callout">
			<form method="POST">
				<div class="row">
					<div class="small-12 medium-6 columns">
						<label>Use Contact Email?
							<select name="use_contact_email">
								<option value="no" <?php echo $use_contact_email ? '': 'selected'; ?>>No, use account email instead</option>
								<option value="yes"  <?php echo $use_contact_email ? 'selected': ''; ?>>Yes</option>
							</select>
						</label>
					</div>
					<div class="small-12 medium-6 columns">
						<label><i class="fa fa-envelope"></i> Contact Email
							<input type="text" name="contact_email" value="<?php echo $contact_email; ?>"/>
						</label>
					</div>
					<div class="small-12 medium-6 columns">
						<label><i class="fa fa-phone"></i> Contact Voice Phone
							<input type="text" name="voice_phone" value="<?php echo $voice_phone; ?>"/>
						</label>
					</div>
					<div class="small-12 medium-6 columns">
						<label><i class="fa fa-mobile"></i> Contact Text Phone
							<input type="text" name="text_phone" value="<?php echo $text_phone; ?>"/>
						</label>
					</div>
					<div class="small-12 medium-6 columns">
						<label>Online Ride Sign-up
							<select name="ride_signup_mode">
								<option value="no" <?php echo $ride_signup_mode == 'no' ? 'selected': ''; ?>>No</option>
								<option value="hardcopy"  <?php echo $ride_signup_mode == 'hardcopy' ? 'selected': ''; ?>>Hardcopy</option>
								<option value="paperless"  <?php echo $ride_signup_mode == 'paperless' ? 'selected': ''; ?>>Paperless</option>
							</select>
						</label>
					</div>
					<div class="small-12 medium-6 columns">
						<label>Sign-up Cutoff <span class="cutoff_units"><?php echo $cutoff_units; ?></span>
							<input type="number" name="signup_cutoff" value="<?php echo $signup_cutoff; ?>"/>
						</label>
					</div>
				</div>
				<div class="row column clearfix">
					<input class="dark button float-left" type="submit" value="Submit"/>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
	
	public static function delete_all_signups($postid, $userid) {
		$signup_list = get_post_meta($postid, self::RIDE_SIGNUP_USERID);
		foreach($signup_list as $item) {
			$arr = json_decode($item, true);
			if ($arr['userid'] == $userid) {
				delete_post_meta($postid, self::RIDE_SIGNUP_USERID, $item);
			}
		}
	}
	
	public static function delete_all_nonmember_signups($postid, $signup_id) {
		$signup_list = get_post_meta($postid, self::RIDE_SIGNUP_NONMEMBER);
		foreach($signup_list as $item) {
			$arr = json_decode($item, true);
			if ($arr['signup_id'] == $signup_id) {
				delete_post_meta($postid, self::RIDE_SIGNUP_NONMEMBER, $item);
			}
		}
	}

	public static function download_ride_signup() {
		if (isset($_POST['pwtc_mapdb_download_signup'])) {
			if (!defined('PWTC_MILEAGE__PLUGIN_DIR')) {
				return;
			}
			if (isset($_POST['ride_id'])) {
				$rideid = intval($_POST['ride_id']);
				if ($rideid == 0) {
					return;
				}
				$post = get_post($rideid);
				if (!$post) {
					return;
				}
				if (get_post_type($post) != self::POST_TYPE_RIDE) {
					return;
				}
				$current_user = wp_get_current_user();
				if ( 0 == $current_user->ID ) {
					return;
				}
				
				$unused_rows = 0;
				if (isset($_POST['unused_rows'])) {
					$unused_rows = abs(intval($_POST['unused_rows']));
				}

				$list = get_post_meta($rideid, self::RIDE_SIGNUP_USERID);
				$signup_list = [];
				foreach ($list as $item) {
					$arr = json_decode($item, true);
					$signup_list[] = $arr;
				}
				$list = get_post_meta($rideid, self::RIDE_SIGNUP_NONMEMBER);
				foreach ($list as $item) {
					$arr = json_decode($item, true);
					$signup_list[] = $arr;
				}

				//$ride_title = sanitize_text_field(get_the_title($rideid));
				$post = get_post($rideid);
				$ride_title = $post->post_title;
				$date = DateTime::createFromFormat('Y-m-d H:i:s', get_field(self::RIDE_DATE, $rideid));
				$ride_date = $date->format('m/d/Y g:ia');
			}
			else {
				$unused_rows = 0;
				$signup_list = [];
				$ride_title = '';
				$ride_date = '';
			}
			//$release_waiver = self::get_release_waiver();
			//$safety_waiver = self::get_safety_waiver();
			//$photo_waiver = self::get_photo_waiver();
			
			$waiver_post = get_page_by_title('Terms and Conditions');
			if ($waiver_post) {
				$release_waiver = wp_kses(get_the_content(null, false, $waiver_post), array());
			}
			else {
				$release_waiver = 'Terms and Conditions page not found!';
			}

			header('Content-Description: File Transfer');
			header("Content-type: application/pdf");
			header("Content-Disposition: attachment; filename=ride_signup.pdf");
			require(PWTC_MILEAGE__PLUGIN_DIR . 'fpdf.php');	
			$pdf = new FPDF('L', 'mm', 'Letter');
			$pdf->SetAutoPageBreak(false);
			$x_margin = 10;
			$y_margin = 10;
			$logo_size = 30;
			$font_size = 12;
			$cell_h = 8;
			$max_pages = 2;
			$nrows_1st_page = 13;
			$nrows_next_pages = 16;
			$extra_rows = count($signup_list)+$unused_rows-$nrows_1st_page-$nrows_next_pages;
			if ($extra_rows > 0) {
				$max_pages += (int)ceil((float)$extra_rows/(float)$nrows_next_pages);
			}
			$rider_count = 0;
			for ($i = 1; $i <= $max_pages; $i++) {
				$pdf->AddPage();
				$pdf->SetXY($x_margin+$logo_size+$x_margin, $y_margin);
				$pdf->SetFont('Arial', 'B', $font_size);
				$pdf->Cell(200, $cell_h, 'PORTLAND BICYCLING CLUB WAIVER & RIDE SIGN IN SHEET', 0, 2,'C');
				if ($i == 1) {
					$pdf->SetFont('Arial', '', $font_size);
					$pdf->Cell(220, $cell_h, 'Ride: '.$ride_title, 1, 2,'L');
					$pdf->Cell(60, $cell_h, 'Date: '.$ride_date, 1, 0,'L');
					$pdf->Cell(110, $cell_h, 'Leader:', 1, 0,'L');
					$pdf->Cell(50, $cell_h, 'Distance:', 1, 0,'L');
					$pdf->Image(PWTC_MILEAGE__PLUGIN_DIR . 'pbc_logo.png', $x_margin, $y_margin, $logo_size, $logo_size);
					$waiver_y = $y_margin+35;
					$table_y = $y_margin+75;
					$rows_per_page = $nrows_1st_page;
				}
				else {
					$waiver_y = $y_margin+10;
					$table_y = $y_margin+50;
					$rows_per_page = $nrows_next_pages;		
				}
				$pdf->SetFont('Arial', '', 8);
				$pdf->SetXY($x_margin, $waiver_y);
				$pdf->MultiCell(0, 3, $release_waiver);
				/*
				$pdf->SetXY($x_margin, $waiver_y+15);
				$pdf->MultiCell(0, 3, $safety_waiver);
				$pdf->SetXY($x_margin, $waiver_y+30);
				$pdf->MultiCell(0, 3, $photo_waiver);
				*/

				$pdf->SetXY($x_margin, $table_y);
				$pdf->SetFont('Arial', 'B', $font_size);
				$pdf->Cell(10, $cell_h, 'No.', 1, 0,'C');
				$pdf->Cell(30, $cell_h, 'Member #', 1, 0,'C');
				$pdf->Cell(20, $cell_h, 'Miles', 1, 0,'C');
				$pdf->Cell(70, $cell_h, 'Rider Name', 1, 0,'C');
				$pdf->Cell(50, $cell_h, 'Signature', 1, 0,'C');
				$pdf->Cell(80, $cell_h, 'Emergency Phone', 1, 0,'C');

				$y_offset = $table_y;
				for ($j = 1; $j <= $rows_per_page; $j++) {
					$rider_name = '';
					$rider_id = '';
					$contact = '';
					$mileage = '';
					if ($rider_count < count($signup_list)) {
						$arr = $signup_list[$rider_count];
						if ($arr['userid']) {
							$userid = $arr['userid'];
							$mileage = '';
							$user_info = get_userdata($userid);
							if ($user_info) {
								$rider_name = $user_info->first_name . ' ' . $user_info->last_name;
							}
							else {
								$rider_name = 'Unknown';
							}
							$rider_id = self::get_rider_id($userid);
							$contact = self::get_emergency_contact($userid, false);
						}
						else {
							$signup_id = $arr['signup_id'];
							$rider_name = $arr['name'];
							$contact_phone = $arr['contact_phone'];
							$contact_name = $arr['contact_name'];
							$contact = self::get_nonmember_emergency_contact($contact_phone, $contact_name, false);	
							$mileage = 'n/a';
							$rider_id = 'n/a';						
						}
					}
					$rider_count++;
					$y_offset += $cell_h;
					$pdf->SetXY($x_margin, $y_offset);
					$pdf->SetFont('Arial', '', $font_size);
					$pdf->Cell(10, $cell_h, $rider_count, 1, 0,'C');
					$pdf->Cell(30, $cell_h, $rider_id, 1, 0,'C');
					$pdf->Cell(20, $cell_h, $mileage, 1, 0,'L');
					$pdf->Cell(70, $cell_h, $rider_name, 1, 0,'L');
					$pdf->Cell(50, $cell_h, '', 1, 0,'L');
					$pdf->Cell(80, $cell_h, $contact, 1, 0,'L');
				}
			}
			$pdf->Output('F', 'php://output');
			die;
		}
	}	

	public static function get_rider_id($userid) {
		$rider_id = get_field(self::USER_RIDER_ID, 'user_'.$userid);
		return $rider_id;
	}

	public static function get_emergency_contact($userid, $use_link) {
		$contact_phone = trim(get_field(self::USER_EMER_PHONE, 'user_'.$userid));
		if (!empty($contact_phone)) {
			if (function_exists('pwtc_members_format_phone_number')) {
				if ($use_link) {
					$contact_phone = '<a href="tel:' . 
						pwtc_members_strip_phone_number($contact_phone) . '">' . 
						pwtc_members_format_phone_number($contact_phone) . '</a>';
				}
				else {
					$contact_phone = pwtc_members_format_phone_number($contact_phone);
				}
			}
		}
		$contact_name = trim(get_field(self::USER_EMER_NAME, 'user_'.$userid));
		$contact = $contact_phone;
		if (!empty($contact_name)) {
			$contact .= ' (' . $contact_name . ')';
		}
		return $contact;
	}
	
	public static function get_nonmember_emergency_contact($contact_phone, $contact_name, $use_link) {
		if (!empty($contact_phone)) {
			if (function_exists('pwtc_members_format_phone_number')) {
				if ($use_link) {
					$contact_phone = '<a href="tel:' . 
						pwtc_members_strip_phone_number($contact_phone) . '">' . 
						pwtc_members_format_phone_number($contact_phone) . '</a>';
				}
				else {
					$contact_phone = pwtc_members_format_phone_number($contact_phone);
				}
			}
		}
		$contact = $contact_phone;
		if (!empty($contact_name)) {
			$contact .= ' (' . $contact_name . ')';
		}
		return $contact;
	}

	public static function get_release_waiver() {
		return <<<EOT
Release Of All Claims: In return for being allowed to participate in rides or any other activities sponsored by the Portland Bicycling Club (PBC), the undersigned and his/her heirs, executors, successors and assigns hereby agree that under no circumstances will a claim be asserted for negligence or gross negligence, for any damages for personal injury, property damage or loss, wrongful death or any other injury or loss incurred during or arising out of participation in any PBC ride or activity in which the PBC is involved, against the PBC, its members, ride leader, officers, agents, and sponsors. I acknowledge that cycling is a dangerous sport and fully realize the dangers of participating in this event. I acknowledge that this event is extraordinarily challenging and that I am in sound medical condition capable of participating in the ride without risk to myself or others.
EOT;
	}

	public static function get_safety_waiver() {
		return <<<EOT
I ACKNOWLEDGE AND AGREE THAT FOR MY PERSONAL SAFETY, I AM REQUIRED TO AND WILL WEAR AN ASTM, CPSC OR SNELL-APPROVED BICYCLE HELMET, AND RIDE SAFELY, LEGALLY AND COURTEOUSLY IN ANY PBC RIDE, AND THAT REFUSAL OF ANY OF THESE REQUIREMENTS GIVES PBC THE RIGHT TO ASK ME TO LEAVE THE RIDE. I FURTHER ACKNOWLEDGE AND AGREE THAT USE OF AN ELECTRIC-ASSISTED BICYCLE ("EBIKE") THAT FAILS TO MEET THE DEFINITION OF A LOW-SPEED E-BIKE AS DEFINED BY THE CONSUMER PRODUCTS SAFETY COMMISSION OR USE OF AN E-BIKE THAT DOES NOT PROVIDE BATTERY-POWERED ASSISTANCE WHILE PEDALING (A THROTTLE OR TWIST-ASSIST E-BIKE) WILL BE CONSIDERED AN EXCLUDED ACTIVITY FOR THE PURPOSES OF COVERAGE UNDER PBC'S GENERAL LIABILITY INSURANCE COVERAGE.
EOT;
	}

	public static function get_photo_waiver() {
		return <<<EOT
Photographs and video may be taken during this ride. By signing this ride sheet, you agree that your image or likeness may be used in promotion and marketing of PBC and its events. If the named entrant is a minor, then, to participate in PBC activities, a legal guardian must sign for him/her in the space below, or the minor must be a club member and have a signed Release of Claims on file with the PBC.
EOT;
	}
	
	/*************************************************************/
	/* AJAX request/response callback functions
	/*************************************************************/

	public static function edit_signup_callback() {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			$response = array(
				'error' => 'Server user access denied for sign up edit.'
			);		
		}
		else if (isset($_POST['userid']) and isset($_POST['postid']) and isset($_POST['mileage']) and isset($_POST['oldmileage']) and isset($_POST['attended']) and isset($_POST['oldattended']) and isset($_POST['nonce'])) {
			$userid = intval($_POST['userid']);
			$postid = intval($_POST['postid']);
			$oldmileage = trim($_POST['oldmileage']);
			$mileage = trim($_POST['mileage']);
			$oldattended = trim($_POST['oldattended']);
			$attended = trim($_POST['attended']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_edit_signup')) {
				$response = array(
					'error' => 'Server security check failed for sign up edit.'
				);
			}
			else {
				if (!empty($mileage)) {
					$m = abs(intval($mileage));
					$mileage = '' . $m;
				}
				if ($mileage != $oldmileage or $attended != $oldattended) {
					$oldvalue = json_encode(array('userid' => $userid, 'mileage' => $oldmileage, 'attended' => boolval($oldattended)));
					$value = json_encode(array('userid' => $userid, 'mileage' => $mileage, 'attended' => boolval($attended)));
					if (update_post_meta($postid, self::RIDE_SIGNUP_USERID, $value, $oldvalue)) {
						$response = array(
							'postid' => $postid,
							'userid' => $userid,
							'mileage' => $mileage,
							'attended' => $attended
						);
					}
					else {
						$response = array(
							'error' => 'Server sign up update failed.'
						);	
					}
				}
				else {
					$response = array(
						'postid' => $postid,
						'userid' => $userid,
						'mileage' => $mileage,
						'attended' => $attended
					);
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for sign up edit.'
			);		
		}
		echo wp_json_encode($response);
		wp_die();
	}	
	
	public static function edit_nonmember_signup_callback() {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			$response = array(
				'error' => 'Server user access denied for nonmember signup edit.'
			);		
		}
		else if (isset($_POST['signup_id']) and isset($_POST['postid']) and isset($_POST['attended']) and isset($_POST['oldattended']) and isset($_POST['nonce'])) {
			$signup_id = intval($_POST['signup_id']);
			$postid = intval($_POST['postid']);
			$oldattended = trim($_POST['oldattended']);
			$attended = trim($_POST['attended']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_edit_nonmember_signup')) {
				$response = array(
					'error' => 'Server security check failed for nonmember sign up edit.'
				);
			}
			else {
				if ($attended != $oldattended) {
					$list = get_post_meta($postid, self::RIDE_SIGNUP_NONMEMBER);
					$oldvalue = '';
					foreach($list as $item) {
						$arr = json_decode($item, true);
						if ($arr['signup_id'] == $signup_id) {
							$oldvalue = $item;
							break;
						}
					}
					if (!empty($oldvalue)) {
						$arr = json_decode($oldvalue, true);
						$value = json_encode(array('signup_id' => $arr['signup_id'], 'name' => $arr['name'], 'contact_phone' => $arr['contact_phone'], 'contact_name' => $arr['contact_name'], 'attended' => boolval($attended)));
						if (update_post_meta($postid, self::RIDE_SIGNUP_NONMEMBER, $value, $oldvalue)) {
							$response = array(
								'postid' => $postid,
								'signup_id' => $signup_id,
								'attended' => $attended
							);
						}
						else {
							$response = array(
								'error' => 'Server nonmember sign up update failed.'
							);	
						}
					}
					else {
						$response = array(
							'error' => 'Server nonmember sign up ID not found for edit.'
						);	
					}
				}
				else {
					$response = array(
						'postid' => $postid,
						'signup_id' => $signup_id,
						'attended' => $attended
					);
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for nonmember sign up edit.'
			);		
		}
		echo wp_json_encode($response);
		wp_die();
	}	
	
	public static function check_nonmember_signup_callback() {
		if (isset($_POST['signup_id']) and isset($_POST['postid']) and isset($_POST['nonce'])) {
			$signup_id = intval($_POST['signup_id']);
			$postid = intval($_POST['postid']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_check_nonmember_signup')) {
				$response = array(
					'error' => 'Server security check failed for nonmember sign up check.'
				);
			}
			else {
				$found = false;
				$signup_list = get_post_meta($postid, self::RIDE_SIGNUP_NONMEMBER);
				foreach($signup_list as $item) {
					$arr = json_decode($item, true);
					if ($arr['signup_id'] == $signup_id) {
						$found = true;
						break;
					}
				}
				if ($found) {
					$response = array(
						'postid' => $postid,
						'signup_id' => ''.$signup_id,
						'found' => $found,
						'signup_name' => $arr['name'],
						'signup_contact_phone' => $arr['contact_phone'],
						'signup_contact_name' => $arr['contact_name']
					);
				}
				else {
					$response = array(
						'postid' => $postid,
						'signup_id' => ''.$signup_id,
						'found' => $found
					);					
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for nonmember sign up check.'
			);		
		}
		echo wp_json_encode($response);
		wp_die();
	}	

	public static function accept_nonmember_signup_callback() {
		if (isset($_POST['signup_id']) and isset($_POST['postid']) and isset($_POST['nonce']) and isset($_POST['signup_name']) and isset($_POST['signup_contact_phone']) and isset($_POST['signup_contact_name']) and isset($_POST['signup_limit'])) {
			$signup_id = intval($_POST['signup_id']);
			$postid = intval($_POST['postid']);
			$signup_limit = intval($_POST['signup_limit']);
			$nonce = $_POST['nonce'];
			$signup_name = $_POST['signup_name'];
			$contact_phone = $_POST['signup_contact_phone'];
			$contact_name = $_POST['signup_contact_name'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_accept_nonmember_signup')) {
				$response = array(
					'error' => 'Server security check failed for nonmember sign up accept.'
				);
			}
			else {
				$total_signups = count(get_post_meta($postid, self::RIDE_SIGNUP_USERID)) +
					count(get_post_meta($postid, self::RIDE_SIGNUP_NONMEMBER));
				if ($signup_limit > 0 and $total_signups >= $signup_limit) {
					$response = array(
						'postid' => $postid,
						'signup_id' => ''.$signup_id,
						'warning' => 'You cannot sign up for this ride because it is full; a maximum of ' . $signup_limit . ' riders are allowed.'
					);				
				}
				else {
					if (function_exists('pwtc_members_format_phone_number')) {
						$contact_phone = pwtc_members_format_phone_number($contact_phone);
					}
					self::delete_all_nonmember_signups($postid, $signup_id);
					$value = json_encode(array('signup_id' => $signup_id, 'name' => $signup_name, 'contact_phone' => $contact_phone, 'contact_name' => $contact_name, 'attended' => true));
					add_post_meta($postid, self::RIDE_SIGNUP_NONMEMBER, $value);
					$response = array(
						'postid' => $postid,
						'signup_id' => ''.$signup_id,
						'signup_name' => $signup_name,
						'signup_contact_phone' => $contact_phone,
						'signup_contact_name' => $contact_name
					);
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for nonmember sign up accept.'
			);		
		}
		echo wp_json_encode($response);
		wp_die();
	}	

	public static function cancel_nonmember_signup_callback() {
		if (isset($_POST['signup_id']) and isset($_POST['postid']) and isset($_POST['nonce'])) {
			$signup_id = intval($_POST['signup_id']);
			$postid = intval($_POST['postid']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_cancel_nonmember_signup')) {
				$response = array(
					'error' => 'Server security check failed for nonmember sign up cancel.'
				);
			}
			else {
				self::delete_all_nonmember_signups($postid, $signup_id);
				$response = array(
					'postid' => $postid,
					'signup_id' => ''.$signup_id
				);
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for nonmember sign up cancel.'
			);		
		}		
		echo wp_json_encode($response);
		wp_die();
	}
	
	public static function log_mileage_callback() {
		if (isset($_POST['postid']) and isset($_POST['nonce'])) {
			$postid = intval($_POST['postid']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_log_mileage')) {
				$response = array(
					'error' => 'Server security check failed for log mileage.'
				);
			}
			else {
				$ride_date = self::get_ride_start_time($postid);
				$interval = new DateInterval('P6M');	
				$ride_date->add($interval);
				$now = self::get_current_time();
				if ($ride_date < $now) {
					$response = array(
						'error' => 'This ride must be less than 6 months old to log its mileage.'
					);	
				}
				else {
					$results = PwtcMileage_DB::fetch_ride_by_post_id($postid);
					if (count($results) > 0) {
						$response = array(
							'error' => 'Mileage for this ride has already been logged.'
						);
					}
					else {
						//$title = sanitize_text_field(get_the_title($postid));
						$post = get_post($postid);
						$title = $post->post_title;
						$date = self::get_ride_start_time($postid);
						$startdate = $date->format('Y-m-d');
						$status = PwtcMileage_DB::insert_ride_with_postid($title, $startdate, $postid);
						if (false === $status or 0 === $status) {
							$response = array(
								'error' => 'Could not insert new ridesheet into mileage database.'
							);
						}
						else {
							$ride_id = PwtcMileage_DB::get_new_ride_id();
							$leaders = self::get_leader_userids($postid);
							$nleaders = 0;
							$nriders = 0;
							$expired_ids = [];
							$missing_ids = [];
							$missing_leader_ids = [];
							foreach ($leaders as $item) {
								$memberid = get_field(self::USER_RIDER_ID, 'user_'.$item);
								$result = PwtcMileage_DB::fetch_rider($memberid);
								if (count($result) > 0) {
									PwtcMileage_DB::insert_ride_leader($ride_id, $memberid);
									$nleaders++;
								}
								else {
									$missing_leader_ids[] = $memberid;
								}
							}
							$signup_list = get_post_meta($postid, self::RIDE_SIGNUP_USERID);
							foreach ($signup_list as $item) {
								$arr = json_decode($item, true);
								$userid = $arr['userid'];
								$mileage = $arr['mileage'];
								$attended = $arr['attended'];
								if ($attended and !empty($mileage)) {
									$memberid = get_field(self::USER_RIDER_ID, 'user_'.$userid);
									$user_info = get_userdata($userid);
									if (in_array(self::ROLE_EXPIRED_MEMBER, (array) $user_info->roles)) {
										$expired_ids[] = $memberid;
									}
									else {
										$result = PwtcMileage_DB::fetch_rider($memberid);
										if (count($result) > 0) {
												PwtcMileage_DB::insert_ride_mileage($ride_id, $memberid, intval($mileage));
												$nriders++;
										}
										else {
											$missing_ids[] = $memberid;
										}
									}
								}
							}
							$response = array(
								'postid' => $postid,
								'rideid' => $ride_id,
								'num_leaders' => $nleaders,
								'num_riders' => $nriders,
								'missing_leaders' => $missing_leader_ids,
								'missing_riders' => $missing_ids,
								'expired_riders' => $expired_ids
							);											
						}
					}
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for log mileage.'
			);		
		}		
		echo wp_json_encode($response);
		wp_die();
	}
	
	public static function lookup_ride_leaders_callback() {
		if (isset($_POST['search'])) {
			$search = trim($_POST['search']);
			$query_args = [
				'meta_key' => 'last_name',
				'orderby' => 'meta_value',
				'order' => 'ASC',
				'role' => self::ROLE_RIDE_LEADER,
				'search' => $search
			];
			$user_query = new WP_User_Query($query_args);
			$members = $user_query->get_results();
			$users = array();
			foreach ( $members as $member ) {
				$item = array(
					'userid' => $member->ID,
					'first_name' => $member->first_name,
					'last_name' => $member->last_name
				);
				$users[] = $item;
			}
			$response = array(
				'users' => $users
			);
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for ride leader lookup.'
			);		
		}		
		echo wp_json_encode($response);
		wp_die();
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
