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
		
		add_shortcode('pwtc_mapdb_download_signup', 
			array( 'PwtcMapdb', 'shortcode_download_signup'));

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
		$a = shortcode_atts(array('time_limit' => -1), $atts);
		$time_limit = $a['time_limit'];

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

		if (get_post_type($post) != 'scheduled_rides') {
			return '<div class="callout small alert"><p>Cannot render shortcode, post type is not a scheduled ride.</p></div>';
		}

		$ride_title = get_the_title($postid);
		$ride_link = get_the_permalink($postid);

		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to signup for this ride.</p></div>';
		}

		$allow_signup = false;
		$leaders = get_field('ride_leaders', $postid);
		foreach($leaders as $leader) {
			$allow_signup = get_field('allow_ride_signup', 'user_'.$leader['ID']);
			if ($allow_signup) {
				break;
			}
		}
		if ( !$allow_signup ) {
			return '<div class="callout small warning"><p>The leader for ride "' . $ride_title . '" does not allow online signup.</p></div>';
		}

		if (get_field('is_canceled', $postid)) {
			return '<div class="callout small warning"><p>The ride "' . $ride_title . '" has been canceled, no signup allowed.</p></div>';
		}

		if (!in_array('current_member', (array) $current_user->roles)) {
			return '<div class="callout small warning"><p>You must be a current member to signup for rides.</p></div>';
		}

		if ($time_limit >= 0) {
			$timezone = new DateTimeZone(pwtc_get_timezone_string());
			$ride_date = DateTime::createFromFormat('Y-m-d H:i:s', get_field('date', $postid), $timezone);
			$ride_date_str = $ride_date->format('m/d/Y g:ia');
			//$ride_time = $ride_date->getTimestamp();
			$now_date = new DateTime(null, $timezone);
			$now_date_str = $now_date->format('m/d/Y g:ia');
			//$now_time = $now_date->getTimestamp();
			//if ($now_time > $ride_time) {
			if ($now_date > $ride_date) {
				return '<div class="callout small warning"><p>You cannot signup for ride "' . $ride_title . '" because it has already started. <em>The start time of the ride is ' . $ride_date_str . ' and the current time is ' . $now_date_str . '</em></p></div>';
			}
			//$now_time = $now_time - ($time_limit*60*60);
			//if ($now_time > $ride_time) {
			if ($time_limit > 0) {
				$interval = new DateInterval('PT' . $time_limit . 'H');	
				$ride_date->sub($interval);
				if ($now_date > $ride_date) {
					return '<div class="callout small warning"><p>You cannot signup for ride "' . $ride_title . '" because it is within ' . $time_limit . ' hours of the start time. <em>The start time of the ride is ' . $ride_date_str . ' and the current time is ' . $now_date_str . '</em></p></div>';
				}
			}
		}

		if (isset($_POST['accept_user_signup'])) {
			delete_post_meta($postid, '_signup_user_id', $current_user->ID);
			add_post_meta($postid, '_signup_user_id', $current_user->ID);
		}

		if (isset($_POST['cancel_user_signup'])) {
			delete_post_meta($postid, '_signup_user_id', $current_user->ID);
		}

		if (isset($_POST['contact_phone'])) {
			if (function_exists('pwtc_members_format_phone_number')) {
				$phone = pwtc_members_format_phone_number($_POST['contact_phone']);
			}
			else {
				$phone = sanitize_text_field($_POST['contact_phone']);
			}
			update_field('emergency_contact_phone', $phone, 'user_'.$current_user->ID);
		}

		if (isset($_POST['contact_name'])) {
			$name = sanitize_text_field($_POST['contact_name']);
			update_field('emergency_contact_name', $name, 'user_'.$current_user->ID);
		}

		$signup_list = get_post_meta($postid, '_signup_user_id');
		$accept_signup = true;
		foreach($signup_list as $item) {
			if ($item == $current_user->ID) {
				$accept_signup = false;
			}
		}

		$user_info = get_userdata($current_user->ID);
		$rider_name = $user_info->first_name . ' ' . $user_info->last_name;
		$contact_phone = get_field('emergency_contact_phone', 'user_'.$current_user->ID);
		$contact_name = get_field('emergency_contact_name', 'user_'.$current_user->ID);

		ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 
		});
	</script>

	<div id='pwtc-mapdb-rider-signup-div'>
		<?php if ($accept_signup) { ?>
			<div class="callout">
				<p>Hello <?php echo $rider_name; ?>, to sign up for the ride "<?php echo $ride_title; ?>," please enter your emergency contact information below and press the accept button. Doing so will indicate your acceptance of the Club's <a href="/terms-and-conditions" target="_blank">terms and conditions</a>.</p>
				<form method="POST">
					<div class="row">
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
					</div>
					<div class="row column clearfix">
						<input type="hidden" name="accept_user_signup" value="yes"/>
						<button class="dark button float-left" type="submit"><i class="fa fa-user-plus"></i> Accept Signup</button>
						<a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
					</div>
				</form>
			</div>
		<?php } else { ?>
			<div class="callout">
				<p>Hello <?php echo $rider_name; ?>, you are currently signed up for the ride "<?php echo $ride_title; ?>." To cancel your sign up, please press the cancel button below.</p>
				<form method="POST">
					<div class="row column clearfix">
						<input type="hidden" name="cancel_user_signup" value="yes"/>
						<button class="dark button float-left" type="submit"><i class="fa fa-user-times"></i> Cancel Signup</button>
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

		if (get_post_type($post) != 'scheduled_rides') {
			return '<div class="callout small alert"><p>Cannot render shortcode, post type is not a scheduled ride.</p></div>';
		}

		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to view the ride signup list.</p></div>';
		}

		if (!user_can($current_user,'edit_published_rides')) {
			if (!in_array('ride_leader', (array) $current_user->roles)) {
				return '<div class="callout small warning"><p>You must be a ride leader to view the ride signup list.</p></div>';
			}
		}

		$signup_list = get_post_meta($postid, '_signup_user_id');
		$ride_title = get_the_title($postid);
		$ride_link = get_the_permalink($postid);

		ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 
		});
	</script>

	<div id='pwtc-mapdb-view-signup-div'>
		<?php if (count($signup_list) > 0) { ?>
			<p>The following riders are currently signed up for the ride "<?php echo $ride_title; ?>."</p>
			<table class="pwtc-mapdb-rwd-table"><thead><tr><th>Name</th><th>Rider ID</th><th>Emergency Contact</th></tr></thead><tbody>
			<?php foreach($signup_list as $item) { 
				$user_info = get_userdata($item);
				if ($user_info) {
					$name = $user_info->first_name . ' ' . $user_info->last_name;
				}
				else {
					$name = 'Unknown';
				}
				$rider_id = self::get_rider_id($item);
				$contact = self::get_emergency_contact($item, true);
			?>
				<tr>
				<td><span>Name</span><?php echo $name; ?></td>
				<td><span>Rider ID</span><?php echo $rider_id; ?></td>
				<td><span>Emergency Contact</span><?php echo $contact; ?></td>
				</tr>
			<?php } ?>
			</tbody></table>
		<?php } else { ?>
			<div class="callout small"><p>There are currently no riders signed up for the ride "<?php echo $ride_title; ?>."</p></div>
		<?php } ?>
		<div class="row column clearfix">
			<form method="POST">
				<input type="hidden" name="ride_id" value="<?php echo $postid; ?>"/>
				<button class="dark button float-left" type="submit" name="pwtc_mapdb_download_signup"><i class="fa fa-download"></i> Signup Sheet</button>
				<a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
			</form>
		</div>
	</div>
	<?php
		return ob_get_clean();
	}

	// Generates the [pwtc_mapdb_download_signup] shortcode.
	public static function shortcode_download_signup($atts) {
		ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 
		});
	</script>

	<div id='pwtc-mapdb-download-signup-div'>
		<form method="POST">
			<button class="dark button" type="submit" name="pwtc_mapdb_download_signup"><i class="fa fa-download"></i> Ride Signup Sheet</button>
		</form>
	</div>
	<?php
		return ob_get_clean();
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
				if (get_post_type($post) != 'scheduled_rides') {
					return;
				}
				$current_user = wp_get_current_user();
				if ( 0 == $current_user->ID ) {
					return;
				}
				$signup_list = get_post_meta($rideid, '_signup_user_id');
				$ride_title = get_the_title($rideid);
				$date = DateTime::createFromFormat('Y-m-d H:i:s', get_field('date', $rideid));
				$ride_date = $date->format('m/d/Y g:ia');
			}
			else {
				$signup_list = [];
				$ride_title = '';
				$ride_date = '';
			}
			$release_waiver = self::get_release_waiver();
			$safety_waiver = self::get_safety_waiver();
			$photo_waiver = self::get_photo_waiver();

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
					$rows_per_page = 13;
				}
				else {
					$waiver_y = $y_margin+10;
					$table_y = $y_margin+50;
					$rows_per_page = 16;		
				}
				$pdf->SetFont('Arial', '', 6);
				$pdf->SetXY($x_margin, $waiver_y);
				$pdf->MultiCell(0, 3, $release_waiver);
				$pdf->SetXY($x_margin, $waiver_y+15);
				$pdf->MultiCell(0, 3, $safety_waiver);
				$pdf->SetXY($x_margin, $waiver_y+30);
				$pdf->MultiCell(0, 3, $photo_waiver);

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
					if ($rider_count < count($signup_list)) {
						$userid = $signup_list[$rider_count];
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
					$rider_count++;
					$y_offset += $cell_h;
					$pdf->SetXY($x_margin, $y_offset);
					$pdf->SetFont('Arial', '', $font_size);
					$pdf->Cell(10, $cell_h, $rider_count, 1, 0,'C');
					$pdf->Cell(30, $cell_h, $rider_id, 1, 0,'C');
					$pdf->Cell(20, $cell_h, '', 1, 0,'L');
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
		$rider_id = get_field('rider_id', 'user_'.$userid);
		return $rider_id;
	}

	public static function get_emergency_contact($userid, $use_link) {
		$contact_phone = trim(get_field('emergency_contact_phone', 'user_'.$userid));
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
		$contact_name = trim(get_field('emergency_contact_name', 'user_'.$userid));
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
