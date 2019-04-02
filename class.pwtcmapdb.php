<?php

class PwtcMapdb {

	const VIEW_ADDRESS_CAP = 'pwtc_view_address';
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

		add_shortcode('pwtc_membership_directory', 
			array( 'PwtcMapdb', 'shortcode_membership_dir'));

		add_shortcode('pwtc_membership_statistics', 
			array( 'PwtcMapdb', 'shortcode_membership_statistics'));

		add_shortcode('pwtc_membership_families', 
			array( 'PwtcMapdb', 'shortcode_membership_families'));

		add_shortcode('pwtc_membership_new_members', 
			array( 'PwtcMapdb', 'shortcode_membership_new_members'));

		add_shortcode('pwtc_membership_renew_nag', 
			array( 'PwtcMapdb', 'shortcode_membership_renew_nag'));

		add_action( 'wp_ajax_pwtc_ride_leader_lookup', 
			array( 'PwtcMapdb', 'ride_leader_lookup_callback') );

		add_action( 'wp_ajax_pwtc_member_fetch_address', 
			array( 'PwtcMapdb', 'member_fetch_address_callback') );

		add_action( 'wp_ajax_pwtc_ride_leader_fetch_profile', 
			array( 'PwtcMapdb', 'ride_leader_fetch_profile_callback') );
			
		add_action( 'wp_ajax_pwtc_ride_leader_update_profile', 
			array( 'PwtcMapdb', 'ride_leader_update_profile_callback') );
			
		add_action( 'template_redirect', 
			array( 'PwtcMapdb', 'download_ride_leaders_list' ) );

		/*
		add_filter( 'wc_memberships_for_teams_new_team_data', 		
			array( 'PwtcMapdb', 'rename_new_team_to_owner' ) );
		*/
		
		/*
		add_filter( 'wc_memberships_members_area_my-memberships_actions', 		
			array( 'PwtcMapdb', 'edit_my_memberships_actions' ) );
		add_filter( 'wc_memberships_members_area_my-membership-details_actions', 
			array( 'PwtcMapdb', 'edit_my_memberships_actions' ) );
		*/	
		
		/*
		add_action('woocommerce_before_cart', 
			array( 'PwtcMapdb', 'validate_checkout_callback' ));
		add_action('woocommerce_checkout_process', 
			array( 'PwtcMapdb', 'validate_checkout_callback' ));
		*/
	}

	/*
	public static function rename_new_team_to_owner( $team_post_data ) {
		$user_data = get_userdata($team_post_data['post_author']);
		if (!$user_data) {
			$team_post_data['post_title'] = 'Unknown'; 
		}
		else {
			$team_post_data['post_title'] = $user_data->last_name . ', ' . $user_data->first_name;
		}
		return $team_post_data;
	}	
	*/

	/*
	public static function edit_my_memberships_actions( $actions ) {
		// remove the "Cancel" action for members
		unset( $actions['cancel'] );
		return $actions;
	}	
	*/

	/*
	public static function validate_checkout_callback() {
		$membership_cnt = 0;
		if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$product = $values['data'];
				if (has_term('Memberships', 'product_cat', $product->get_id())) {
					$membership_cnt++;
				}
				//$categories = $product->get_category_ids();
				//if (in_array('memberships', $categories)) {
				//	$membership_cnt++;
				//}
			}
		}
		if ($membership_cnt > 1) {
			$msg = 'You may not purchase more than one membership product at a time';
			if (is_cart()) {
				wc_print_notice($msg, 'error');
			} 
			else {
				wc_add_notice($msg, 'error');
			}
		}
	}
	*/

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
					'<tr><th>Title</th><th>Distance</th><th>Terrain</th>';
				if (can_edit) {
					header += '<th>Actions</th>';
				}
				header += '</tr></table>';
				$('#pwtc-mapdb-maps-div').append(header);
				maps.forEach(function(item) {
					var data = '<tr postid="' + item.ID + '">' +
					'<td data-th="Title">' + copylink + ' ' + item.media + item.title + '</a></td>' +
					'<td data-th="Distance">' + item.distance + '</td>' +
					'<td data-th="Terrain">' + item.terrain + '</td>';
					if (can_edit) {
						data += '<td data-th="Actions">' + item.edit + '</td>'
					}
					data += '</tr>';
					$('#pwtc-mapdb-maps-div table').append(data);    
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

	// Generates the [pwtc_membership_directory] shortcode.
	public static function shortcode_membership_dir($atts) {
		$a = shortcode_atts(array('limit' => 10, 'mode' => 'readonly', 'privacy' => 'off'), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to view the member list.</p></div>';
		}
		else {
			if ($a['mode'] == 'edit') {
				$can_view_address = current_user_can(self::VIEW_ADDRESS_CAP);
				$can_view_leaders = current_user_can(self::VIEW_LEADERS_CAP);
				$can_edit_leaders = current_user_can(self::EDIT_LEADERS_CAP);
			}
			else {
				$can_view_address = current_user_can(self::VIEW_ADDRESS_CAP);
				$can_view_leaders = false;
				$can_edit_leaders = false;
			}
			ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 

			<?php if ($can_view_address) { ?>
			function display_user_address_cb(response) {
				$('#pwtc-ride-leader-wait').foundation('close');
				var res = JSON.parse(response);
				if (res.error) {
					$("#pwtc-ride-leader-error .error-msg").html(res.error);
					$('#pwtc-ride-leader-error').foundation('open');
				}
				else {
					$('#pwtc-member-address .address-data').empty();
					$('#pwtc-member-address .contact-data').empty();
					$('#pwtc-member-address .address-data').append(
						'<div>' + res.first_name + ' ' + res.last_name + '</div>');
					$('#pwtc-member-address .address-data').append(
						'<div>' + res.street1 + '</div>');
					$('#pwtc-member-address .address-data').append(
						'<div>' + res.street2 + '</div>');
					$('#pwtc-member-address .address-data').append(
						'<div>' + res.city + ' ' + res.state + ' ' + res.zipcode + '</div>');
					$('#pwtc-member-address .contact-data').append(
						'<div>' + res.email + '</div>');
					$('#pwtc-member-address .contact-data').append(
						'<div>' + res.phone + '</div>');
					$('#pwtc-member-address .contact-data').append(
						'<div>Rider ID: ' + res.riderid + '</div>');
					$('#pwtc-member-address .contact-data').append(
						'<div>Family:' + res.family + '</div>');
					$('#pwtc-member-address').foundation('open');
				}
			}
			<?php } ?>

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
						load_members_table('refresh');
					}
				}
			}
			<?php } ?>

			function populate_members_table(members) {
				var header = '<table class="pwtc-mapdb-rwd-table"><tr><th>Member Name</th><th>Account Email</th><th>Account Phone</th>' +
				<?php if ($can_view_leaders or $can_edit_leaders or $can_view_address) { ?>
				'<th>Actions</th>' +
				<?php } ?>
				'</tr></table>';
				$('#pwtc-ride-leaders-div').append(header);
				members.forEach(function(item) {
					var data = '<tr userid="' + item.ID + '">' +
					'<td data-th="Name">' + item.first_name + ' ' + item.last_name + 
					(item.is_expired ? ' <i class="fa fa-exclamation-triangle" title="Membership Expired"></i>' : '') +
					(item.is_ride_leader ? ' <i class="fa fa-bicycle" title="Ride Leader"></i>' : '') + '</td>' + 
					'<td data-th="Email">' + item.email + '</td>' +
					'<td data-th="Phone">' + item.phone + '</td>' +
					<?php if ($can_view_leaders or $can_edit_leaders or $can_view_address) { ?>
					'<td data-th="Actions">' +
						<?php if ($can_view_address) { ?>
						'<a class="view_address" title="View member contact information."><i class="fa fa-home"></i></a> ' +	
						<?php } ?>
						<?php if ($can_edit_leaders) { ?>
						'<a class="edit_leaders" title="Edit ride leader profile information."><i class="fa fa-pencil-square"></i></a> ' +
						<?php } else if ($can_view_leaders) { ?>
						'<a class="edit_leaders" title="View ride leader profile information."><i class="fa fa-eye"></i></a> ' +
						<?php } ?>
					'</td>' +
					<?php } ?>
					'</tr>';
					$('#pwtc-ride-leaders-div table').append(data);    
				});
				<?php if ($can_view_address) { ?>
				$('#pwtc-ride-leaders-div table .view_address').on('click', function(e) {
					var userid = $(this).parent().parent().attr('userid');
					var action = "<?php echo admin_url('admin-ajax.php'); ?>";
					var data = {
						'action': 'pwtc_member_fetch_address',
						'userid': userid
					};
					$.post(action, data, display_user_address_cb);
					$('#pwtc-ride-leader-wait .wait-message').html('Loading member address information.');
					$('#pwtc-ride-leader-wait').foundation('open');
				});
				<?php } ?>
				<?php if ($can_view_leaders or $can_edit_leaders) { ?>
				$('#pwtc-ride-leaders-div table .edit_leaders').on('click', function(e) {
					$("#pwtc-ride-leader-profile input[type='text']").val('');
					var userid = $(this).parent().parent().attr('userid');
					$("#pwtc-ride-leader-profile input[name='userid']").val(userid);
					var action = "<?php echo admin_url('admin-ajax.php'); ?>";
					var data = {
						'action': 'pwtc_ride_leader_fetch_profile',
						'userid': userid
					};
					$.post(action, data, display_user_profile_cb);
					$('#pwtc-ride-leader-wait .wait-message').html('Loading ride leader profile information.');
					$('#pwtc-ride-leader-wait').foundation('open');
				});
				<?php } ?>
            }

			function create_paging_form(pagenum, numpages, totalusers) {
				$('#pwtc-ride-leaders-div').append(
					'<form class="page-frm">' +
                    '<input class="prev-btn button" style="margin: 0" type="button" value="< Prev"/>' +
					'<span style="margin: 0 10px">Page ' + pagenum + ' of ' + numpages + 
					' (' + totalusers + ' records)</span>' +
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
						'<div class="callout small alert"><p>' + res.error + '</p></div>');
				}
				else {
					if (res.members.length > 0) {
						populate_members_table(res.members);
						if (res.total_pages > 1) {
							create_paging_form(res.page_number, res.total_pages, res.total_users);
						}
					}
					else {
						$('#pwtc-ride-leaders-div').append(
							'<div class="callout small warning"><p>No members found.</p></div>');
					}
				}
			}   

			function load_members_table(mode) {
                var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_ride_leader_lookup',
					'privacy': '<?php echo $a['privacy'] ?>',
					'limit': <?php echo $a['limit'] ?>
				};
				if (mode != 'search') {
					data.role = $("#pwtc-ride-leader-search-div .search-frm input[name='role_sav']").val();
					data.email = $("#pwtc-ride-leader-search-div .search-frm input[name='email_sav']").val();
					data.last_name = $("#pwtc-ride-leader-search-div .search-frm input[name='last_name_sav']").val();
					data.first_name = $("#pwtc-ride-leader-search-div .search-frm input[name='first_name_sav']").val();
					if (mode == 'refresh') {
						if ($("#pwtc-ride-leaders-div .page-frm").length != 0) {
							var pagenum = $("#pwtc-ride-leaders-div .page-frm input[name='pagenum']").val();
							data.page_number = parseInt(pagenum);
							$('#pwtc-ride-leaders-div .page-frm .page-msg').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');	
						}
						else {
							$('#pwtc-ride-leaders-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
						}
					}
					else {
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
				$("#pwtc-ride-leader-search-div .search-frm .role").val('all'); 
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
					'nonce': '<?php echo wp_create_nonce('pwtc_ride_leader_update_profile'); ?>',
					'contact_email': $("#pwtc-ride-leader-profile .profile-frm input[name='contact_email']").val().trim(),
					'voice_phone': $("#pwtc-ride-leader-profile .profile-frm input[name='voice_phone']").val().trim(),
					'text_phone': $("#pwtc-ride-leader-profile .profile-frm input[name='text_phone']").val().trim(),
					'use_contact_email': $("#pwtc-ride-leader-profile .profile-frm .use_contact_email").val(),
					'is_ride_leader': $("#pwtc-ride-leader-profile .profile-frm .is_ride_leader").val()
				};
				$.post(action, data, update_user_profile_cb);
				$('#pwtc-ride-leader-wait .wait-message').html('Updating ride leader profile information.');
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
	<div id="pwtc-ride-leader-error" class="small reveal" data-close-on-click="false" data-v-offset="100" data-reveal>
		<form class="profile-frm">
		    <div class="row column">
				<div class="callout alert"><p class="error-msg"></p></div>
			</div>
			<div class="row column clearfix">
				<input class="accent button float-left" type="button" value="Close" data-close/>
			</div>
		</form>
	</div>
	<div id="pwtc-ride-leader-wait" class="small reveal" data-close-on-click="false" data-v-offset="100" data-reveal>
		<div class="callout warning">
			<p><i class="fa fa-spinner fa-pulse"></i> Please wait...</p>
			<p class="wait-message"></p>
		</div>
	</div>
	<?php if ($can_view_address) { ?>
	<div id="pwtc-member-address" class="small reveal" data-close-on-click="false" data-v-offset="100" data-reveal>
		<form class="profile-frm">
			<div class="row column">
				<div class="callout primary">
					<p class="address-data"></p>
					<p class="contact-data"></p>
				</div>
			</div>
			<div class="row column clearfix">
				<input class="accent button float-left" type="button" value="Close" data-close/>
			</div>
		</form>
	</div>
	<?php } ?>
	<?php if ($can_view_leaders or $can_edit_leaders) { ?>
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
					<label>Is Ride Leader?
						<select class="is_ride_leader">
							<option value="no" selected>No</option>
							<option value="yes">Yes</option>
						</select>
					</label>
				</div>
			</div>
			<div class="row column">
				<div class="callout small secondary">
					<p><i>These entries are displayed in the ride calendar for use by riders to contact the ride leader for additional information. Set an entry blank to not display.</i></p>
				</div>
			</div>
			<div class="row">
				<div class="small-12 medium-6 columns">
					<label>Display Contact Email?
						<select class="use_contact_email">
							<option value="no" selected>No, display account email</option>
							<option value="yes">Yes</option>
						</select>
					</label>
				</div>
				<div class="small-12 medium-6 columns">
					<label><i class="fa fa-envelope"></i> Contact Email
						<input type="text" name="contact_email"/>
					</label>
				</div>
				<div class="small-12 medium-6 columns">
					<label><i class="fa fa-phone"></i> Contact Voice Phone
						<input type="text" name="voice_phone"/>
					</label>
				</div>
				<div class="small-12 medium-6 columns">
					<label><i class="fa fa-mobile"></i> Contact Text Phone
						<input type="text" name="text_phone"/>
					</label>
				</div>
			</div>
			<div class="status_msg row column"></div>
			<div class="row column clearfix">
				<?php if ($can_edit_leaders) { ?>
				<input class="accent button float-left" type="submit" value="Submit"/>
				<input class="accent button float-right" type="button" value="Cancel" data-close/>
				<?php } else {?>
				<input class="accent button float-left" type="button" value="Close" data-close/>
				<?php } ?>
			</div>
		</form>
	</div>
	<div id="pwtc-ride-leader-download-div" class="button-group">
  		<a class="button" title="Download ride leader CSV file."><i class="fa fa-download"></i> Ride Leaders</a>
		<form class="download-frm" method="post">
			<input type="hidden" name="pwtc-ride-leaders-download" value="yes"/>
			<input type="hidden" name="role" value="ride_leader"/>
			<input type="hidden" name="privacy" value="<?php echo $a['privacy'] ?>"/>
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
						<input class="role" type="hidden" name="role" value="all"/>
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
											<option value="all" selected>All Members</option>
											<option value="ride_leader">Ride Leaders Only</option>
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
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			$response = array(
				'error' => 'Member fetch failed - user access denied.'
			);		
		}
		else if (isset($_POST['limit'])) {
			$exclude = false;
			$hide = false;
			if (isset($_POST['privacy'])) {
				if ($_POST['privacy'] == 'exclude') {
					$exclude = true;
				}
				else if ($_POST['privacy'] == 'hide') {
					$hide = true;
				}
			}
			$query_args = self::get_user_query_args($exclude);

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
					if ($hide and get_field('directory_excluded', 'user_'.$member->ID)) {
						$email = '*****';
						$phone = '*****';
					}
					else {
						if (!empty($member_info->user_email)) {
							$email = '<a href="mailto:' . $member_info->user_email . '">' . $member_info->user_email . '</a>';
						}
						else {
							$email = '';
						}
						if (!empty($member_info->billing_phone)) {
							$phone = '<a href="tel:' . $member_info->billing_phone . '">' . $member_info->billing_phone . '</a>';
						}
						else {
							$phone = '';
						}
					}
					$member_names[] = [
						'ID' => $member->ID,
						'first_name' => $member_info->first_name,
						'last_name' => $member_info->last_name,
						'email' => $email,
						'phone' => $phone,
						'is_expired' => in_array('expired_member', $member_info->roles),
						'is_ride_leader' => in_array('ride_leader', $member_info->roles)
					];
				}
			}
			
			$total_users = $user_query->total_users;
			$total_pages = ceil($user_query->total_users/$limit);

			$response = array(
				'members' => $member_names,
				'total_pages' => $total_pages,
				'page_number' => $page_number,
				'total_users' => $total_users
			);
		}
		else {
			$response = array(
				'error' => 'Member fetch failed - AJAX arguments missing.'
			);		
		}
        echo wp_json_encode($response);
        wp_die();
	}

	public static function ride_leader_fetch_profile_callback() {
		if (!current_user_can(PwtcMapdb::VIEW_LEADERS_CAP)) {
			$response = array(
				'error' => 'Profile fetch failed - user access denied.'
			);		
		}
		else if (isset($_POST['userid'])) {
			$userid = intval($_POST['userid']);
			$member_info = get_userdata($userid);
			if ($member_info === false) {
				$response = array(
					'userid' => $userid,
					'error' => 'Profile fetch failed - user ID ' . $userid . ' not valid.'
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
		}
		else {
			$response = array(
				'error' => 'Profile fetch failed - AJAX arguments missing.'
			);		
		}
		echo wp_json_encode($response);
        wp_die();
	}

	public static function member_fetch_address_callback() {
		if (!current_user_can(PwtcMapdb::VIEW_ADDRESS_CAP)) {
			$response = array(
				'error' => 'Address fetch failed - user access denied.'
			);		
		}
		else if (isset($_POST['userid'])) {
			$userid = intval($_POST['userid']);
			$member_info = get_userdata($userid);
			if ($member_info === false) {
				$response = array(
					'userid' => $userid,
					'error' => 'Address fetch failed - user ID ' . $userid . ' not valid.'
				);
			}
			else {
				$family = '';
				if (function_exists('wc_memberships_for_teams_get_teams')) {
					$teams = wc_memberships_for_teams_get_teams($userid);
					if ($teams && !empty($teams)) {
						foreach ( $teams as $team ) {
							$family .= ' ' . $team->get_name();
							if ($team->is_user_owner($userid)) {
								$family .= ' (owner)';
							}
						}
					}
				}
				$riderid = get_field('rider_id', 'user_'.$userid);
				if (!$riderid) {
					$riderid = '';
				}
				$response = array(
					'userid' => $userid,
					'first_name' => $member_info->first_name,
					'last_name' => $member_info->last_name,
					'email' => $member_info->user_email,
					'riderid' => $riderid,
					'street1' => get_user_meta($userid, 'billing_address_1', true),
					'street2' => get_user_meta($userid, 'billing_address_2', true), 
					'city' => get_user_meta($userid, 'billing_city', true), 
					'state' => get_user_meta($userid, 'billing_state', true), 
					'country' => get_user_meta($userid, 'billing_country', true), 
					'zipcode' => get_user_meta($userid, 'billing_postcode', true), 
					'phone' => get_user_meta($userid, 'billing_phone', true),
					'family' => $family
				);
			}
		}
		else {
			$response = array(
				'error' => 'Address fetch failed - AJAX arguments missing.'
			);		
		}
		echo wp_json_encode($response);
        wp_die();
	}

	public static function ride_leader_update_profile_callback() {
		if (!current_user_can(PwtcMapdb::EDIT_LEADERS_CAP)) {
			$response = array(
				'error' => 'Profile update failed - user access denied.'
			);		
		}
		else if (isset($_POST['userid']) and isset($_POST['nonce']) and
			isset($_POST['use_contact_email']) and isset($_POST['voice_phone']) and
			isset($_POST['text_phone']) and isset($_POST['contact_email']) and
			isset($_POST['is_ride_leader'])) {
			$phone_regexp = '/^\d{3}-\d{3}-\d{4}$/';
			if (!wp_verify_nonce($_POST['nonce'], 'pwtc_ride_leader_update_profile')) {
				$response = array(
					'error' => 'Profile update failed - access security check failed.'
				);
			}
			else if ($_POST['contact_email'] != '' and 
				!filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL)) {
				$response = array(
					'error' => 'Profile update failed - contact email is not a valid email address.'
				);				
			}
			else if ($_POST['voice_phone'] != '' and 
				preg_match($phone_regexp, $_POST['voice_phone']) !== 1) {
				$response = array(
					'error' => 'Profile update failed - voice phone is not a valid phone number (000-000-0000).'
				);				
			}
			else if ($_POST['text_phone'] != '' and 
				preg_match($phone_regexp, $_POST['text_phone']) !== 1) {
				$response = array(
					'error' => 'Profile update failed - text phone is not a valid phone number (000-000-0000).'
				);				
			}
			else {
				$userid = intval($_POST['userid']);
				$member_info = get_userdata($userid);
				if ($member_info === false) {
					$response = array(
						'userid' => $userid,
						'error' => 'Profile update failed - user ID ' . $userid . ' is not valid.'
					);
				}
				else {
					$refresh = false;
					$use_contact_email = get_field('use_contact_email', 'user_'.$userid);
					if ($use_contact_email and $_POST['use_contact_email'] == 'no') {
						update_field('use_contact_email', false, 'user_'.$userid);
					}
					else if (!$use_contact_email and $_POST['use_contact_email'] == 'yes') {
						update_field('use_contact_email', true, 'user_'.$userid);
					}
					$voice_phone = get_field('cell_phone', 'user_'.$userid);
					if ($voice_phone != $_POST['voice_phone']) {
						update_field('cell_phone', $_POST['voice_phone'], 'user_'.$userid);
					}
					$text_phone = get_field('home_phone', 'user_'.$userid);
					if ($text_phone != $_POST['text_phone']) {
						update_field('home_phone', $_POST['text_phone'], 'user_'.$userid);
					}
					$contact_email = get_field('contact_email', 'user_'.$userid);
					if ($contact_email != $_POST['contact_email']) {
						update_field('contact_email', $_POST['contact_email'], 'user_'.$userid);
					}
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
			}
		}
		else {
			$response = array(
				'error' => 'Profile update failed - AJAX arguments are missing.'
			);		
		}
        echo wp_json_encode($response);
        wp_die();
	}

	public static function download_ride_leaders_list() {
		if (current_user_can(self::VIEW_LEADERS_CAP)) {
			if (isset($_POST['pwtc-ride-leaders-download']) and isset($_POST['role'])) {
				$query_args = self::get_user_query_args();
				$today = date('Y-m-d', current_time('timestamp'));
				header('Content-Description: File Transfer');
				header("Content-type: text/csv");
				header("Content-Disposition: attachment; filename={$today}_ride_leaders.csv");
				$fp = fopen('php://output', 'w');
				fputcsv($fp, ['Email Address', 'First Name', 'Last Name']);
				$user_query = new WP_User_Query( $query_args );
				$members = $user_query->get_results();
				if ( !empty($members) ) {
					foreach ( $members as $member ) {
						$member_info = get_userdata($member->ID);
						fputcsv($fp, [$member_info->user_email, $member_info->first_name, $member_info->last_name]);
					}
				}
				fclose($fp);
				die;
			}
		}
	}

	public static function get_user_query_args($exclude = false) {
        $query_args = [
            'meta_key' => 'last_name',
            'orderby' => 'meta_value',
			'order' => 'ASC',
			'role__in' => ['current_member', 'expired_member']
		];

		if ($exclude) {
			if (!isset($query_args['meta_query'])) {
				$query_args['meta_query'] = [];
			}
			$query_args['meta_query'][] = [
				'relation' => 'OR',
				[
					'key'     => 'directory_excluded',
					'value'   => '0',
					'compare' => '='   	
				],
				[
					'key'     => 'directory_excluded',
					'compare' => 'NOT EXISTS'   	
				]
			];
		}

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

	// Generates the [pwtc_membership_statistics] shortcode.
	public static function shortcode_membership_statistics($atts) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to view the membership statistics.</p></div>';
		}
		else {
			$today = date('F j Y', current_time('timestamp'));
			$active = self::count_membership('active');
			$expired = self::count_membership('expired');
			$delayed = self::count_membership('delayed');
			$complimentary = self::count_membership('complimentary');
			$paused = self::count_membership('paused');
			$cancelled = self::count_membership('cancelled');
			$total = $active + $expired + $delayed + $complimentary + $paused + $cancelled;
			ob_start();
			?>
			<div>Membership statistics as of <?php echo $today; ?>:<ul>
			<li><?php echo $active; ?> active members</li>
			<li><?php echo $expired; ?> expired members</li>
			<?php if ($complimentary > 0) { ?>
			<li><?php echo $complimentary; ?> complimentary members</li>
			<?php } ?>
			<?php if ($delayed > 0) { ?>
			<li><?php echo $delayed; ?> delayed members</li>
			<?php } ?>
			<?php if ($paused > 0) { ?>
			<li><?php echo $paused; ?> paused members</li>
			<?php } ?>
			<?php if ($cancelled > 0) { ?>
			<li><?php echo $cancelled; ?> cancelled members</li>
			<?php } ?>
			</ul></div>
			<?php
			return ob_get_clean();
		}
	}

	public static function count_membership($status) {
		$query_args = [
			'nopaging'    => true,
			'post_status' => 'wcm-'.$status,
			'post_type' => 'wc_user_membership',
		];			
		$the_query = new WP_Query($query_args);
		return $the_query->found_posts;
	}

	// Generates the [pwtc_membership_families] shortcode.
	public static function shortcode_membership_families($atts) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to view the family member statistics.</p></div>';
		}
		else {
			$today = date('F j Y', current_time('timestamp'));
			$families = self::count_family_memberships();
			$family_members = self::count_family_members();
			ob_start();
			?>
			<div>Family member statistics as of <?php echo $today; ?>:<ul>
			<li><?php echo $families; ?> family memberships</li>
			<li><?php echo $family_members; ?> family members</li>
			</ul></div>
			<?php
			return ob_get_clean();
		}
	}

	public static function count_family_memberships() {
		$query_args = [
			'nopaging'    => true,
			'post_status' => 'any',
			'post_type' => 'wc_memberships_team',
		];			
		$the_query = new WP_Query($query_args);
		return $the_query->found_posts;
	}

	public static function count_family_members() {
		$count = 0;
		$query_args = [
			'nopaging'    => true,
			'post_status' => 'any',
			'post_type' => 'wc_memberships_team',
		];			
		$the_query = new WP_Query($query_args);
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$members = get_post_meta(get_the_ID(), '_member_id', false);
				$count += count($members);
			}
			wp_reset_postdata();
		}
		return $count;
	}

	// Generates the [pwtc_membership_new_members] shortcode.
	public static function shortcode_membership_new_members($atts) {
		$a = shortcode_atts(array('lookback' => 0), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to view the new members.</p></div>';
		}
		else {
			$timezone = new DateTimeZone(pwtc_get_timezone_string());
			$month = new DateTime(date('Y-m-01', current_time('timestamp')), $timezone);
			$lookback = $a['lookback'];
			if ($lookback > 0) {
				$month->sub(new DateInterval('P' . $lookback . 'M'));
			}
			$query_args = [
				'nopaging'    => true,
				'post_status' => 'any',
				'post_type'   => 'wc_user_membership',
				'meta_key'    => '_start_date',
				'orderby'     => 'meta_value',
				'order'       => 'DESC',
			];			
			$the_query = new WP_Query($query_args);
			if ( $the_query->have_posts() ) {
				ob_start();
				?>
				<div>New members since <?php echo $month->format('F Y'); ?>:<ul>
				<?php
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$start = new DateTime(get_post_meta(get_the_ID(), '_start_date', true), $timezone);
					if ($start->getTimestamp() < $month->getTimestamp()) {
						break;
					}
					?>
					<li><?php echo get_the_author(); ?> (<?php echo $start->format('M j'); ?>)</li>
					<?php						
				}
				?>
				</ul></div>
				<?php						
				wp_reset_postdata();
				return ob_get_clean();
			} 
			else {
				return '<div class="callout small warning"><p>No new members found.</p></div>';
			}
		}
	}

	// Generates the [pwtc_membership_renew_nag] shortcode.
	public static function shortcode_membership_renew_nag($atts) {
		$a = shortcode_atts(array('renewonly' => 'no'), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '';
		}
		if (!function_exists('wc_memberships_get_user_memberships')) {
			return '';
		}
		$memberships = wc_memberships_get_user_memberships($current_user->ID);
		if (empty($memberships)) {
			return '';
		}
		if (count($memberships) > 1) {
			ob_start();
			?>
			<div class="callout alert"><p>You have multiple memberships, please notify website admin to resolve</p></div>		
			<?php
			return ob_get_clean();
		}
		$membership = $memberships[0];
		if ($a['renewonly'] == 'yes') {
			if (!$membership->is_expired()) {
				return '';
			}
		}
		$team = false;
		if (function_exists('wc_memberships_for_teams_get_user_membership_team')) {
			$team = wc_memberships_for_teams_get_user_membership_team($membership->get_id());
		}
		if ($team) {
			if ($team->is_user_owner($current_user->ID)) {
				if ($membership->is_expired()) {
					ob_start();
					?>
					<div class="callout warning"><p>Your family membership "<?php echo $team->get_name(); ?>" has expired. <a href="<?php echo $team->get_renew_membership_url(); ?>">Click here to renew</a></p></div>		
					<?php
					return ob_get_clean();
				}
				else {
					ob_start();
					?>
					<div class="callout success"><p>Your family membership "<?php echo $team->get_name(); ?>" expires on <?php echo date('F j, Y', $team->get_local_membership_end_date('timestamp')); ?></p></div>		
					<?php
					return ob_get_clean();
				}
			}
			else {
				if ($membership->is_expired()) {
					ob_start();
					?>
					<div class="callout warning"><p>The family membership "<?php echo $team->get_name(); ?>" has expired, please ask the membership owner to renew</p></div>		
					<?php
					return ob_get_clean();	
				}
				else {
					ob_start();
					?>
					<div class="callout success"><p>The family membership "<?php echo $team->get_name(); ?>" expires on <?php echo date('F j, Y',$team->get_local_membership_end_date('timestamp')); ?></p></div>		
					<?php
					return ob_get_clean();
				}
			}
		}
		else {
			if ($membership->is_expired()) {
				ob_start();
				?>
				<div class="callout warning"><p>Your individual membership has expired. <a href="<?php echo $membership->get_renew_membership_url(); ?>">Click here to renew</a></p></div>		
				<?php
				return ob_get_clean();
			}
			else {
				ob_start();
				?>
				<div class="callout success"><p>Your individual membership expires on <?php echo date('F j, Y', $membership->get_local_end_date('timestamp')); ?></p></div>		
				<?php
				return ob_get_clean();
			}
		}
	}	
	
	/*************************************************************/
	/* Plugin installation and removal functions.
	/*************************************************************/

	public static function add_caps_admin_role() {
		$admin = get_role('administrator');
		$admin->add_cap(self::VIEW_ADDRESS_CAP);
		$admin->add_cap(self::VIEW_LEADERS_CAP);
		$admin->add_cap(self::EDIT_LEADERS_CAP);
		self::write_log('PWTC MapDB plugin added capabilities to administrator role');
	}

	public static function remove_caps_admin_role() {
		$admin = get_role('administrator');
		$admin->remove_cap(self::VIEW_ADDRESS_CAP);
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