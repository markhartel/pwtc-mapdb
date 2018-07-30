<?php

class PwtcMapdb {

	const VIEW_LEADERS_CAP = 'pwtc_view_leaders';
	const EDIT_LEADERS_CAP = 'pwtc_edit_leaders';

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
	const EDIT_ANCHOR_LABEL = '<i class="fa fa-pencil-square-o"></i>';
	const EDIT_CAPABILITY = 'edit_others_rides';

/*
	const MAP_POST_TYPE = 'route';
	const START_LOCATION_FIELD = 'start_location';
	const TERRAIN_FIELD = 'route_terrain';
	const LENGTH_FIELD = 'route_length';
	const MAX_LENGTH_FIELD = 'max_route_length';
	const MAP_TYPE_FIELD = 'map_type';
	const MAP_LINK_FIELD = 'map_link';
	const MAP_FILE_FIELD = 'map_file';
	const MAP_TYPE_QUERY = 'map_type';
	const COPY_ANCHOR_LABEL = '[C]';
	//const FILE_ANCHOR_LABEL = 'File';
	//const LINK_ANCHOR_LABEL = 'Link';
	const EDIT_ANCHOR_LABEL = 'Edit';
	const EDIT_CAPABILITY = 'edit_others_posts';
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

		add_shortcode('pwtc_ride_leader_dir', 
			array( 'PwtcMapdb', 'shortcode_ride_leader_dir'));

		add_action( 'wp_ajax_pwtc_ride_leader_lookup', 
			array( 'PwtcMapdb', 'ride_leader_lookup_callback') );
			
		add_action( 'wp_ajax_pwtc_ride_leader_fetch_profile', 
			array( 'PwtcMapdb', 'ride_leader_fetch_profile_callback') );
			
		add_action( 'wp_ajax_pwtc_ride_leader_update_profile', 
			array( 'PwtcMapdb', 'ride_leader_update_profile_callback') );
			
		add_action( 'template_redirect', 
			array( 'PwtcMapdb', 'download_ride_leaders_list' ) );

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

	public static function get_query_args($title, $location, $terrain, $min_dist, $max_dist, $media) {
		$args = array(
			'post_type' => self::MAP_POST_TYPE,
			//'post_status' => 'publish',
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
/*
			$type = get_field(self::MAP_TYPE_FIELD);
			if ($type == 'file') {
				$file = get_field(self::MAP_FILE_FIELD);
				//$modtime = get_post_modified_time('M Y', false, $file['id']);
				//self::write_log ($file);
				//$url = '<a title="Download map file." target="_blank" href="' . $file['url'] . '">' . self::FILE_ANCHOR_LABEL . '</a>';
				$url = '<a title="Download map file." target="_blank" href="' . $file['url'] . '">';
			}
			else if ($type == 'link') {
				$link = get_field(self::MAP_LINK_FIELD);
				//$url = '<a title="Open map link." target="_blank" href="' . $link . '">' . self::LINK_ANCHOR_LABEL . '</a>';
				$url = '<a title="Open map link." target="_blank" href="' . $link . '">';
			}
*/	

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
		if (false) {
			$response = array(
				'error' => 'You are not allowed to search the map library.'
			);
			echo wp_json_encode($response);
		}
		else if (!isset($_POST['title']) or !isset($_POST['location']) or !isset($_POST['terrain']) or !isset($_POST['distance']) or !isset($_POST['media']) or !isset($_POST['limit'])) {
			$response = array(
				'error' => 'Input parameters needed to search map library are missing.'
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
			return 'Please log in to search the map library.';
		}
		else {
			ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 
			function populate_maps_table(maps, can_edit) {
				var copylink = '<a title="Copy map title to clipboard." class="copy-btn"><?php echo self::COPY_ANCHOR_LABEL ?></a>';
				var header = '<table class="pwtc-mapdb-rwd-table">' +
					'<tr><th>Title</th><th>Distance</th><th>Terrain</th>';
				if (can_edit) {
					header += '<th>Actions</th>';
				}
				header += '</tr></table>';
				$('.pwtc-mapdb-maps-div').append(header);
				maps.forEach(function(item) {
					var data = '<tr postid="' + item.ID + '">' +
					'<td data-th="Title">' + copylink + ' ' + item.media + item.title + '</a></td>' +
					'<td data-th="Distance">' + item.distance + '</td>' +
					'<td data-th="Terrain">' + item.terrain + '</td>';
					if (can_edit) {
						data += '<td data-th="Actions">' + item.edit + '</td>'
					}
					data += '</tr>';
					$('.pwtc-mapdb-maps-div table').append(data);    
				});
				$('.pwtc-mapdb-maps-div table .copy-btn').on('click', function(evt) {
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
				$('.pwtc-mapdb-maps-div').append(
					'<form class="page-frm">' +
                    '<input class="prev-btn button" style="margin: 0" type="button" value="< Prev"/>' +
					'<span style="margin: 0 10px">Page ' + pagenum + ' of ' + numpages + '</span>' +
                    '<input class="next-btn button" style="margin: 0" type="button" value="Next >"/>' +
					'<span class="page-msg" style="margin: 0 10px"></span>' +
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
						populate_maps_table(res.maps, res.can_edit);
						if (res.offset !== undefined) {
							create_paging_form(res.offset, res.count);
						}
					}
					else {
						$('.pwtc-mapdb-maps-div').append('<div>No maps found.</div>');					
					}
				}
				$('body').removeClass('pwtc-mapdb-waiting');
			}   

			function load_maps_table(mode) {
				var title = $(".pwtc-mapdb-search-div .search-frm input[name='title']").val().trim();
				//var location = $(".pwtc-mapdb-search-div .search-frm input[name='location']").val().trim();
				var location = '';
				var terrain = $('.pwtc-mapdb-search-div .search-frm .terrain').val();
				var distance = $('.pwtc-mapdb-search-div .search-frm .distance').val();
				//var media = $('.pwtc-mapdb-search-div .search-frm .media').val();
				var media = '0';
				var action = $('.pwtc-mapdb-search-div .search-frm').attr('action');
				var data = {
					'action': 'pwtc_mapdb_lookup_maps',
					'title': title,
					'location': location,
					'terrain': terrain,
					'distance': distance,
					'media': media,
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
					$('.pwtc-mapdb-maps-div .page-frm .page-msg').html('<i class="fa fa-spinner fa-pulse"></i> Loading...');
				}
				else {
					$('.pwtc-mapdb-maps-div').html('<i class="fa fa-spinner fa-pulse"></i> Loading...');
				}
				$('body').addClass('pwtc-mapdb-waiting');
				$.post(action, data, lookup_maps_cb); 
			}

			$('.pwtc-mapdb-search-div .search-frm').on('submit', function(evt) {
				evt.preventDefault();
				load_maps_table('search');
			});

			$('.pwtc-mapdb-search-div .search-frm .reset-btn').on('click', function(evt) {
				evt.preventDefault();
				$(".pwtc-mapdb-search-div .search-frm input[type='text']").val(''); 
				$('.pwtc-mapdb-search-div .search-frm select').val('0');
				$('.pwtc-mapdb-maps-div').empty();
				load_maps_table('search');
			});

			load_maps_table('search');
		});
	</script>

	<div class='pwtc-mapdb-search-div'>
	<ul class="accordion" data-accordion data-allow-all-closed="true">
		<li class="accordion-item" data-accordion-item>
            <a href="#" class="accordion-title"><i class="fa fa-search"></i> Click Here To Search</a>
            <div class="accordion-content" data-tab-content>
				<form class="search-frm" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
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

<!--
	<div class='pwtc-mapdb-search-div pwtc-mapdb-search-sec'>
	<p>To browse the map library, press the <strong>Search</strong> button. 
	To narrow your search, fill out the form below before searching.</p>
	<form class="search-frm pwtc-mapdb-stacked-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
		<span>Title</span>
		<input type="text" name="title"/>
		<span>Terrain</span>
		<select class="terrain">
            <option value="0" selected>Any</option> 
            <option value="a">A (flat)</option>
            <option value="b">B (gently rolling)</option>
            <option value="c">C (short steep hills)</option>
            <option value="d">D (longer hills)</option>
            <option value="e">E (mountainous)</option>
        </select>
		<span>Distance</span>		
		<select class="distance">
            <option value="0" selected>Any</option> 
            <option value="1">0-25 miles</option>
            <option value="2">25-50 miles</option>
            <option value="3">50-75 miles</option>
            <option value="4">75-100 miles</option>
            <option value="5">&gt; 100 miles</option>
        </select>		
		<input class="dark button" type="submit" value="Search"/>
		<input class="reset-btn dark button" type="button" value="Reset"/>
	</form>
	</div>
-->
	<div class="pwtc-mapdb-maps-div"></div>
	<?php
			return ob_get_clean();
		}
	}

	// Generates the [pwtc_ride_leader_dir] shortcode.
	public static function shortcode_ride_leader_dir($atts) {
		$a = shortcode_atts(array('limit' => 10), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return 'Please log in to view the ride leader directory.';
		}
		else {
			$can_view_leaders = current_user_can(self::VIEW_LEADERS_CAP);
			$can_edit_leaders = current_user_can(self::EDIT_LEADERS_CAP);
			ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 

			<?php if ($can_view_leaders or $can_edit_leaders) { ?>
			function display_user_profile_cb(response) {
				$('#pwtc-ride-leader-wait').foundation('close');
				var res = JSON.parse(response);
				if (res.error) {
					$("#pwtc-ride-leader-error .error-msg").html(res.error);
					$('#pwtc-ride-leader-error').foundation('open');
				}
				else {
					$("#pwtc-ride-leader-profile .header-msg").html(res.first_name + ' ' + res.last_name + ' - ' + res.email);
					$("#pwtc-ride-leader-profile .profile-frm input[name='contact_email']").val(res.contact_email);
					$("#pwtc-ride-leader-profile .profile-frm input[name='voice_phone']").val(res.voice_phone);
					$("#pwtc-ride-leader-profile .profile-frm input[name='text_phone']").val(res.text_phone);
					if (res.use_contact_email) {
						$("#pwtc-ride-leader-profile .profile-frm .use_contact_email").val('yes');
					}
					else {
						$("#pwtc-ride-leader-profile .profile-frm .use_contact_email").val('no');
					}
					if (res.is_ride_leader) {
						$("#pwtc-ride-leader-profile .profile-frm .is_ride_leader").val('yes');
					}
					else {
						$("#pwtc-ride-leader-profile .profile-frm .is_ride_leader").val('no');
					}
					$("#pwtc-ride-leader-profile .status_msg").html('');
					$('#pwtc-ride-leader-profile').foundation('open');
				}
			}
			<?php } ?>

			<?php if ($can_edit_leaders) { ?>
			function update_user_profile_cb(response) {
				$('#pwtc-ride-leader-wait').foundation('close');
				var res = JSON.parse(response);
				if (res.error) {
					$("#pwtc-ride-leader-profile .status_msg").html('<div class="callout small alert"><p>' + res.error + '</p></div>');
					$('#pwtc-ride-leader-profile').foundation('open');
				}
				else {
					if (res.refresh) {
						load_members_table('search');
					}
				}
			}
			<?php } ?>

			function populate_members_table(members) {
				var header = '<table class="pwtc-mapdb-rwd-table"><tr><th>Last Name</th><th>First Name</th><th>Email</th><th>Phone</th>' +
				<?php if ($can_view_leaders or $can_edit_leaders) { ?>
				'<th>Actions</th>' +
				<?php } ?>
				'</tr></table>';
				$('#pwtc-ride-leaders-div').append(header);
				members.forEach(function(item) {
					var data = '<tr userid="' + item.ID + '" username="' + item.first_name + ' ' + item.last_name + '">' +
					'<td data-th="Last Name">' + item.last_name + '</td>' + 
					'<td data-th="First Name">' + item.first_name + '</td>' +
					'<td data-th="Email">' + item.email + '</td>' +
					'<td data-th="Phone">' + item.phone + '</td>' +
					<?php if ($can_view_leaders or $can_edit_leaders) { ?>
					'<td data-th="Actions">' +
						<?php if ($can_edit_leaders) { ?>
						'<a title="Edit member profile."><i class="fa fa-pencil-square"></i></a> ' +
						<?php } else { ?>
						'<a title="View member profile."><i class="fa fa-eye"></i></a> ' +
						<?php } ?>
					'</td>' +
					<?php } ?>
					'</tr>';
					$('#pwtc-ride-leaders-div table').append(data);    
				});
				<?php if ($can_view_leaders or $can_edit_leaders) { ?>
				$('#pwtc-ride-leaders-div table a').on('click', function(e) {
					$("#pwtc-ride-leader-profile input[type='text']").val('');
					var userid = $(this).parent().parent().attr('userid');
					$("#pwtc-ride-leader-profile input[name='userid']").val(userid);
					var action = "<?php echo admin_url('admin-ajax.php'); ?>";
					var data = {
						'action': 'pwtc_ride_leader_fetch_profile',
						'userid': userid
					};
					$.post(action, data, display_user_profile_cb);
					$('#pwtc-ride-leader-wait .wait-message').html('Loading ride leader profile.');
					$('#pwtc-ride-leader-wait').foundation('open');
				});
				<?php } ?>
            }

			function create_paging_form(pagenum, numpages) {
				$('#pwtc-ride-leaders-div').append(
					'<form class="page-frm">' +
                    '<input class="prev-btn button" style="margin: 0" type="button" value="< Prev"/>' +
					'<span style="margin: 0 10px">Page ' + pagenum + ' of ' + numpages + '</span>' +
                    '<input class="next-btn button" style="margin: 0" type="button" value="Next >"/>' +
					'<span class="page-msg" style="margin: 0 10px"></span>' +
					'<input name="pagenum" type="hidden" value="' + pagenum + '"/>' +
					'<input name="numpages" type="hidden" value="' + numpages + '"/>' +
					'</form>'
				);
				$('#pwtc-ride-leaders-div .page-frm .prev-btn').on('click', function(evt) {
					evt.preventDefault();
					load_members_table('prev');
				});
				if (pagenum == 1) {
					$('#pwtc-ride-leaders-div .page-frm .prev-btn').attr("disabled", "disabled");
				}
				else {
					$('#pwtc-ride-leaders-div .page-frm .prev-btn').removeAttr("disabled");
				}
				$('#pwtc-ride-leaders-div .page-frm .next-btn').on('click', function(evt) {
					evt.preventDefault();
					load_members_table('next');
				});
				if (pagenum == numpages) {
					$('#pwtc-ride-leaders-div .page-frm .next-btn').attr("disabled", "disabled");
				}
				else {
					$('#pwtc-ride-leaders-div .page-frm .next-btn').removeAttr("disabled");
				}
			}

			function lookup_members_cb(response) {
				var res = JSON.parse(response);
				$('#pwtc-ride-leaders-div').empty();
				if (res.error) {
					$('#pwtc-ride-leaders-div').append(
						'<div><strong>Error:</strong> ' + res.error + '</div>');
				}
				else {
					if (res.message !== undefined) {
						$('#pwtc-ride-leaders-div').append(
							'<div><strong>Warning:</strong> ' + res.message + '</div>');
					}
					if (res.members.length > 0) {
						populate_members_table(res.members);
						if (res.total_pages > 1) {
							create_paging_form(res.page_number, res.total_pages);
						}
					}
					else {
						$('#pwtc-ride-leaders-div').append('<div><i class="fa fa-exclamation-triangle"></i> No members found.</div>');
					}
				}
			}   

			function load_members_table(mode) {
                var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_ride_leader_lookup',
					'limit': <?php echo $a['limit'] ?>
				};
				if (mode != 'search') {
					data.role = $("#pwtc-ride-leader-search-div .search-frm input[name='role_sav']").val();
					data.email = $("#pwtc-ride-leader-search-div .search-frm input[name='email_sav']").val();
					data.last_name = $("#pwtc-ride-leader-search-div .search-frm input[name='last_name_sav']").val();
					data.first_name = $("#pwtc-ride-leader-search-div .search-frm input[name='first_name_sav']").val();
					var pagenum = $("#pwtc-ride-leaders-div .page-frm input[name='pagenum']").val();
					var numpages = $("#pwtc-ride-leaders-div .page-frm input[name='numpages']").val();
					if (mode == 'prev') {
						data.page_number = parseInt(pagenum) - 1;
					}
					else if (mode == 'next') {
						data.page_number = parseInt(pagenum) + 1;
					}
					$('#pwtc-ride-leaders-div .page-frm .page-msg').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
				}
				else {
					data.role = $("#pwtc-ride-leader-search-div .search-frm .role").val();
					data.email = $("#pwtc-ride-leader-search-div .search-frm input[name='email']").val().trim();
					data.last_name = $("#pwtc-ride-leader-search-div .search-frm input[name='last_name']").val().trim();
					data.first_name = $("#pwtc-ride-leader-search-div .search-frm input[name='first_name']").val().trim();
					$("#pwtc-ride-leader-search-div .search-frm input[name='role_sav']").val(data.role);
					$("#pwtc-ride-leader-search-div .search-frm input[name='email_sav']").val(data.email);
					$("#pwtc-ride-leader-search-div .search-frm input[name='last_name_sav']").val(data.last_name);
					$("#pwtc-ride-leader-search-div .search-frm input[name='first_name_sav']").val(data.first_name);	
					$('#pwtc-ride-leaders-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
				}

				$.post(action, data, lookup_members_cb); 
			}

			$('#pwtc-ride-leader-search-div .search-frm').on('submit', function(evt) {
				evt.preventDefault();
				load_members_table('search');
			});

			$('#pwtc-ride-leader-search-div .search-frm .reset-btn').on('click', function(evt) {
				evt.preventDefault();
				$("#pwtc-ride-leader-search-div .search-frm input[type='text']").val('');
				$("#pwtc-ride-leader-search-div .search-frm .role").val('ride_leader'); 
				$('#pwtc-ride-leaders-div').empty();
				load_members_table('search');
			});

			<?php if ($can_edit_leaders) { ?>
			$('#pwtc-ride-leader-profile .profile-frm').on('submit', function(evt) {
				evt.preventDefault();
				var userid = $("#pwtc-ride-leader-profile .profile-frm input[name='userid']").val();
				var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_ride_leader_update_profile',
					'userid': userid,
					'contact_email': $("#pwtc-ride-leader-profile .profile-frm input[name='contact_email']").val().trim(),
					'voice_phone': $("#pwtc-ride-leader-profile .profile-frm input[name='voice_phone']").val().trim(),
					'text_phone': $("#pwtc-ride-leader-profile .profile-frm input[name='text_phone']").val().trim(),
					'use_contact_email': $("#pwtc-ride-leader-profile .profile-frm .use_contact_email").val(),
					'is_ride_leader': $("#pwtc-ride-leader-profile .profile-frm .is_ride_leader").val()
				};
				$.post(action, data, update_user_profile_cb);
				$('#pwtc-ride-leader-wait .wait-message').html('Updating ride leader profile.');
				$('#pwtc-ride-leader-wait').foundation('open');
			});
			<?php } else if ($can_view_leaders) { ?>
			$("#pwtc-ride-leader-profile .profile-frm input[type='text']").attr("disabled", "disabled");
			$("#pwtc-ride-leader-profile .profile-frm select").attr("disabled", "disabled");
			<?php } ?>

			<?php if ($can_view_leaders or $can_edit_leaders) { ?>
			$('#pwtc-ride-leader-download-div a').on('click', function(e) {
				$('#pwtc-ride-leader-download-div .download-frm').submit();
			});
			<?php } ?>

			load_members_table('search');

		});
	</script>
	<?php if ($can_view_leaders or $can_edit_leaders) { ?>
	<div id="pwtc-ride-leader-error" class="reveal" data-close-on-click="false" data-reveal>
		<form class="profile-frm">
		    <div class="row column">
				<div class="callout alert"><p class="error-msg"></p></div>
			</div>
			<div class="row column clearfix">
				<input class="accent button float-left" type="button" value="Close" data-close/>
			</div>
		</form>
	</div>
	<div id="pwtc-ride-leader-wait" class="reveal" data-close-on-click="false" data-reveal>
		<div class="callout warning">
			<p><i class="fa fa-spinner fa-pulse"></i> Please wait...</p>
			<p class="wait-message"></p>
		</div>
	</div>
	<div id="pwtc-ride-leader-profile" class="small reveal" data-close-on-click="false" data-v-offset="100" data-reveal>
		<form class="profile-frm">
			<input type="hidden" name="userid"/>
			<div class="row column">
				<div class="callout primary">
					<p class="header-msg"></p>
				</div>
			</div>
			<div class="row">
				<div class="small-12 medium-6 columns">
					<label>Use Contact Email
						<select class="use_contact_email">
							<option value="no" selected>No, use account email</option>
							<option value="yes">Yes</option>
						</select>
					</label>
				</div>
				<div class="small-12 medium-6 columns">
					<label>Contact Email
						<input type="text" name="contact_email"/>
					</label>
				</div>
				<div class="small-12 medium-6 columns">
					<label>Contact Voice Phone
						<input type="text" name="voice_phone"/>
					</label>
				</div>
				<div class="small-12 medium-6 columns">
					<label>Contact Text Phone
						<input type="text" name="text_phone"/>
					</label>
				</div>
				<div class="small-12 medium-6 columns">
					<label>Is Ride Leader
						<select class="is_ride_leader">
							<option value="no" selected>No</option>
							<option value="yes">Yes</option>
						</select>
					</label>
				</div>
			</div>
			<div class="status_msg row column"></div>
			<div class="row column clearfix">
				<?php if ($can_edit_leaders) { ?>
				<input class="accent button float-left" type="submit" value="Submit"/>
				<input class="accent button float-right" type="button" value="Cancel" data-close/>
				<?php } else {?>
				<input class="accent button float-left" type="button" value="Cancel" data-close/>
				<?php } ?>
			</div>
		</form>
	</div>
	<div id="pwtc-ride-leader-download-div" class="button-group">
  		<a class="button" title="Download ride leaders."><i class="fa fa-download"></i> Ride Leaders</a>
		<form class="download-frm" method="post">
			<input type="hidden" name="pwtc-ride-leaders-download" value="yes"/>
			<input type="hidden" name="role" value="ride_leader"/>
		</form>	
	</div>
	<?php } ?>
	<div id='pwtc-ride-leader-search-div'>
		<ul class="accordion" data-accordion data-allow-all-closed="true">
			<li class="accordion-item" data-accordion-item>
				<a href="#" class="accordion-title"><i class="fa fa-search"></i> Click Here To Search</a>
				<div class="accordion-content" data-tab-content>
					<form class="search-frm">
						<input type="hidden" name="last_name_sav" value=""/>
						<input type="hidden" name="first_name_sav" value=""/>
						<input type="hidden" name="email_sav" value=""/>
						<input type="hidden" name="role_sav" value=""/>
						<?php if (!$can_view_leaders) { ?>
						<input class="role" type="hidden" name="role" value="ride_leader"/>
						<?php } ?>
						<div>
							<div class="row">
								<div class="small-12 medium-3 columns">
                        			<label>Last Name
										<input type="text" name="last_name"/>
                        			</label>
                    			</div>
								<div class="small-12 medium-3 columns">
                        			<label>First Name
										<input type="text" name="first_name"/>
                        			</label>
                    			</div>
								<div class="small-12 medium-3 columns">
                        			<label>Email
										<input type="text" name="email"/>
                        			</label>
                    			</div>
								<?php if ($can_view_leaders) { ?>
								<div class="small-12 medium-3 columns">
                                	<label>Show
							        	<select class="role">
											<option value="ride_leader" selected>Ride Leaders</option>
											<option value="!ride_leader">Others</option>
                                        </select>                                
                                	</label>
                            	</div>
								<?php } ?>
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
	<div id="pwtc-ride-leaders-div"></div>
	<?php
			return ob_get_clean();
		}
	}

    public static function ride_leader_lookup_callback() {
		$query_args = self::get_user_query_args();

		$limit = intval($_POST['limit']);
		$query_args['number'] = $limit;

		$page_number = 1;
		if (isset($_POST['page_number'])) {
			$page_number = intval($_POST['page_number']);
		}
		if ($page_number == 1) {
			$offset = 0;  
		}
		else {
			$offset = ($page_number-1)*$limit;
		}
		$query_args['offset'] = $offset;

        $member_names = [];
        $user_query = new WP_User_Query( $query_args );
        $members = $user_query->get_results();
        if ( !empty($members) ) {
            foreach ( $members as $member ) {
                $member_info = get_userdata( $member->ID );
                $member_names[] = [
                    'ID' => $member->ID,
                    'first_name' => $member_info->first_name,
                    'last_name' => $member_info->last_name,
					'email' => $member_info->user_email,
					'phone' => ''
                ];
            }
		}
		
		$total_users = $user_query->total_users;
		$total_pages = ceil($user_query->total_users/$limit);

        $response = array(
			'members' => $member_names,
			'total_pages' => $total_pages,
			'page_number' => $page_number
		);

        echo wp_json_encode($response);
        wp_die();
	}

	public static function ride_leader_fetch_profile_callback() {
		$userid = intval($_POST['userid']);
		$member_info = get_userdata($userid);
		if ($member_info === false) {
			$response = array(
				'userid' => $userid,
				'error' => 'Fetch of profile for user ID ' . $userid . ' failed.'
			);
		}
		else {
			$is_ride_leader = false;
			if (in_array('ride_leader', $member_info->roles)) {
				$is_ride_leader = true;
			}
			$response = array(
				'userid' => $userid,
				'first_name' => $member_info->first_name,
				'last_name' => $member_info->last_name,
				'email' => $member_info->user_email,
				'voice_phone' => get_field('cell_phone', 'user_'.$userid),
				'text_phone' => get_field('home_phone', 'user_'.$userid),
				'contact_email' => get_field('contact_email', 'user_'.$userid),
				'use_contact_email' => get_field('use_contact_email', 'user_'.$userid),
				'is_ride_leader' => $is_ride_leader
			);
		}
		echo wp_json_encode($response);
        wp_die();
	}

	public static function ride_leader_update_profile_callback() {
		$userid = intval($_POST['userid']);
		$member_info = get_userdata($userid);
		if ($member_info === false) {
			$response = array(
				'userid' => $userid,
				'error' => 'Fetch of profile for user ID ' . $userid . ' failed.'
			);
		}
		else {
			$refresh = false;
			$is_ride_leader = $_POST['is_ride_leader'];
			if (in_array('ride_leader', $member_info->roles)) {
				if ($is_ride_leader == 'no') {
					$member_info->remove_role('ride_leader');
					$refresh = true;
				}
			}
			else {
				if ($is_ride_leader == 'yes') {
					$member_info->add_role('ride_leader');
					$refresh = true;
				}
			}
			$response = array(
				'userid' => $userid,
				'refresh' => $refresh
			);
		}
        echo wp_json_encode($response);
        wp_die();
	}

	public static function download_ride_leaders_list() {
		if (current_user_can(self::VIEW_LEADERS_CAP)) {
			if (isset($_POST['pwtc-ride-leaders-download'])) {
				$query_args = self::get_user_query_args();
				$today = date('Y-m-d', current_time('timestamp'));
				header('Content-Description: File Transfer');
				header("Content-type: text/csv");
				header("Content-Disposition: attachment; filename={$today}_members.csv");
				$fp = fopen('php://output', 'w');
				fputcsv($fp, ['Last Name', 'First Name', 'Email']);
				$user_query = new WP_User_Query( $query_args );
				$members = $user_query->get_results();
				if ( !empty($members) ) {
					foreach ( $members as $member ) {
						$member_info = get_userdata($member->ID);
						fputcsv($fp, [$member_info->last_name, $member_info->first_name, $member_info->user_email]);
					}
				}
				fclose($fp);
				die;
			}
		}
	}

	public static function get_user_query_args() {
        $query_args = [
            'meta_key' => 'last_name',
            'orderby' => 'meta_value',
			'order' => 'ASC',
			'role__in' => ['current_member', 'expired_member']
		];

		if (isset($_POST['first_name'])) {
			if (!isset($query_args['meta_query'])) {
				$query_args['meta_query'] = [];
			}
			$query_args['meta_query'][] = [
				'key'     => 'first_name',
				'value'   => $_POST['first_name'],
				'compare' => 'LIKE'   
			];
		}

		if (isset($_POST['last_name'])) {
			if (!isset($query_args['meta_query'])) {
				$query_args['meta_query'] = [];
			}
			$query_args['meta_query'][] = [
				'key'     => 'last_name',
				'value'   => $_POST['last_name'],
				'compare' => 'LIKE'   
			];
		}

		if (isset($_POST['email'])) {
			$query_args['search'] = '*' . esc_attr($_POST['email']) . '*';
			$query_args['search_columns'] = array( 'user_email' );
		}	

		if (isset($_POST['role'])) {
			$role = $_POST['role'];
			if ($role != 'all') {
				if (substr($role, 0, 1) === "!") {
					$not_role = substr($role, 1, strlen($role)-1);
					if (isset($query_args['role__not_in'])) {
						$query_args['role__not_in'][] = $not_role;
					}
					else {
						$query_args['role__not_in'] = [$not_role];
					}
				}
				else {
					$query_args['role__in'] = [$role];
				}
			}
		}

		return $query_args;
	}
	
	/*************************************************************/
	/* Plugin installation and removal functions.
	/*************************************************************/

	public static function add_caps_admin_role() {
		$admin = get_role('administrator');
		$admin->add_cap(self::VIEW_LEADERS_CAP);
		$admin->add_cap(self::EDIT_LEADERS_CAP);
		self::write_log('PWTC MapDB plugin added capabilities to administrator role');
	}

	public static function remove_caps_admin_role() {
		$admin = get_role('administrator');
		$admin->remove_cap(self::VIEW_LEADERS_CAP);
		$admin->remove_cap(self::EDIT_LEADERS_CAP);
		self::write_log('PWTC MapDB plugin removed capabilities from administrator role');
	}

	public static function add_caps_rc_role() {
		$captain = get_role('ride_captain'); 
		if ($captain !== null) {
			$captain->add_cap(self::VIEW_LEADERS_CAP);
			$captain->add_cap(self::EDIT_LEADERS_CAP);
			pwtc_mileage_write_log('PWTC MapDB plugin added capabilities to ride_captain role');
		} 
	}

	public static function remove_caps_rc_role() {
		$captain = get_role('ride_captain'); 
		if ($captain !== null) {
			$captain->remove_cap(self::VIEW_LEADERS_CAP);
			$captain->remove_cap(self::EDIT_LEADERS_CAP);
			pwtc_mileage_write_log('PWTC MapDB plugin removed capabilities from ride_captain role');
		} 
	}	

	public static function plugin_activation() {
		self::write_log( 'PWTC MapDB plugin activated' );
		if ( version_compare( $GLOBALS['wp_version'], PWTC_MAPDB__MINIMUM_WP_VERSION, '<' ) ) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('PWTC MapDB plugin requires Wordpress version of at least ' . PWTC_MAPDB__MINIMUM_WP_VERSION);
		}
		self::add_caps_admin_role();
		self::add_caps_rc_role();
	}

	public static function plugin_deactivation( ) {
		self::write_log( 'PWTC MapDB plugin deactivated' );
		self::remove_caps_admin_role();
		self::remove_caps_rc_role();
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