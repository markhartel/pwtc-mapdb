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

		// Register shortcode callbacks
		add_shortcode('pwtc_search_mapdb', 
			array( 'PwtcMapdb', 'shortcode_search_mapdb'));
		
		add_shortcode('pwtc_mapdb_rider_signup', 
			array( 'PwtcMapdb', 'shortcode_rider_signup'));

		add_shortcode('pwtc_mapdb_view_signup', 
			array( 'PwtcMapdb', 'shortcode_view_signup'));

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
                    '<input class="prev-btn button" style="margin: 0" type="button" value="< Prev"/>' +
					'<span style="margin: 0 10px">Page ' + pagenum + ' of ' + numpages + '</span>' +
                    '<input class="next-btn button" style="margin: 0" type="button" value="Next >"/>' +
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
			return '<div class="callout small warning"><p>Page missing post ID.</p></div>';
		}

		$postid = intval($_GET['post']);
		if ($postid == 0) {
			return '<div class="callout small warning"><p>Page post ID is invalid.</p></div>';
		}

		$post = get_post($postid);
		if (!$post) {
			return '<div class="callout small warning"><p>Page post does not exist.</p></div>';
		}

		/*
		if (get_post_type($post) != 'scheduled_rides') {
			return '<div class="callout small warning"><p>Page post type is not a scheduled ride.</p></div>';
		}
		*/

		$ride_title = get_the_title($postid);

		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to signup for a ride.</p></div>';
		}

		/*
		$allow_signup = false;
		$leaders = get_field('ride_leaders', $postid);
		foreach($leaders as $leader) {
			$allow_signup = get_field('allow_ride_signup', 'user_'.$leader['ID']);
		}
		if ( !$allow_signup ) {
			return '<div class="callout small warning"><p>The leader for ride "' . $ride_title . '" does not allow online signup.</p></div>';
		}
		*/

		/*
		if (!in_array('current_member', (array) $current_user->roles)) {
			return '<div class="callout small warning"><p>You must be a current member to signup for rides.</p></div>';
		}
		*/

		if ($time_limit >= 0) {
			$ride_date = DateTime::createFromFormat('Y-m-d H:i:s', get_field('date', $postid))->getTimestamp();
			$now_date = new DateTime();
			$now_date = $now_date->getTimestamp();
			$now_date = $now_data - ($time_limit*60*60);
			if ($now_date > $ride_date) {
				return '<div class="callout small warning"><p>Cannot signup for ride "' . $ride_title . '" because it is too close to the start time.</p></div>';
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
			//update_field('emergency_contact_phone', pwtc_members_format_phone_number($_POST['contact_phone']), 'user_'.$current_user->ID);
		}

		if (isset($_POST['contact_name'])) {
			//update_field('emergency_contact_name', trim($_POST['contact_name']), 'user_'.$current_user->ID);
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
		$contact_phone = '';
		//$contact_phone = pwtc_members_format_phone_number(get_field('emergency_contact_phone', 'user_'.$current_user->ID));
		$contact_name = '';
		//$contact_name = trim(get_field('emergency_contact_name', 'user_'.$current_user->ID));

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
						<input class="button float-left" type="submit" value="Accept Signup"/>
					</div>
				</form>
			</div>
		<?php } else { ?>
			<div class="callout">
				<p>Hello <?php echo $rider_name; ?>, you are currently signed up for the ride "<?php echo $ride_title; ?>." To cancel your sign up, please press the cancel button below.</p>
				<form method="POST">
					<div class="row column clearfix">
						<input type="hidden" name="cancel_user_signup" value="yes"/>
						<input class="button float-left" type="submit" value="Cancel Signup"/>
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
			return '<div class="callout small warning"><p>Page missing post ID.</p></div>';
		}

		$postid = intval($_GET['post']);
		if ($postid == 0) {
			return '<div class="callout small warning"><p>Page post ID is invalid.</p></div>';
		}

		$post = get_post($postid);
		if (!$post) {
			return '<div class="callout small warning"><p>Page post does not exist.</p></div>';
		}

		/*
		if (get_post_type($post) != 'scheduled_rides') {
			return '<div class="callout small warning"><p>Page post type is not a scheduled ride.</p></div>';
		}
		*/

		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to view the rider signup list.</p></div>';
		}

		/*
		if (!in_array('ride_leader', (array) $current_user->roles)) {
			return '<div class="callout small warning"><p>You must be a ride leader to view the rider signup list.</p></div>';
		}
		*/

		$signup_list = get_post_meta($postid, '_signup_user_id');
		$ride_title = get_the_title($postid);

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
				$name = $user_info->first_name . ' ' . $user_info->last_name;
				$rider_id = '';
				//$rider_id = get_field('rider_id', 'user_'.$item);
				$contact_phone = '';
				//$contact_phone = trim(get_field('emergency_contact_phone', 'user_'.$item));
				if (!empty($contact_phone)) {
					$contact_phone = '<a href="tel:' . 
						pwtc_members_strip_phone_number($contact_phone) . '">' . 
						pwtc_members_format_phone_number($contact_phone) . '</a>';
				}
				$contact_name = '';
				//$contact_name = trim(get_field('emergency_contact_name', 'user_'.$item));
				$contact = $contact_phone;
				if (!empty($contact_name)) {
					$contact .= '(' . $contact_name . ')';
				}
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
	</div>
	<?php
		return ob_get_clean();
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
