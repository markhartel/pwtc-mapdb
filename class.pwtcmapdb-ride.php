<?php

class PwtcMapdb_Ride {
	
	const EDIT_RIDE_URI = '/edit-ride';
	const SUBMIT_RIDE_URI = '/submit-ride';
	const DELETE_RIDE_URI = '/delete-ride';
	
	const EDIT_TEMPLATE_URI = '/edit-ride-template';
	const SUBMIT_TEMPLATE_URI = '/submit-ride-template';
	const DELETE_TEMPLATE_URI = '/delete-ride-template';
	
    	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	private static function init_hooks() {
        self::$initiated = true;

		// Register action callbacks
		add_action('wp_enqueue_scripts', array('PwtcMapdb_Ride', 'load_javascripts'));
		//add_action('transition_post_status', array('PwtcMapdb_Ride', 'status_change_callback'), 10, 3);

		// Register filter callbacks
		add_filter('heartbeat_received', array('PwtcMapdb_Ride', 'refresh_post_lock'), 10, 3);

		// Register shortcode callbacks
		add_shortcode('pwtc_mapdb_ride_breadcrumb', array('PwtcMapdb_Ride', 'shortcode_ride_breadcrumb'));
        	add_shortcode('pwtc_mapdb_edit_ride', array('PwtcMapdb_Ride', 'shortcode_edit_ride'));
		add_shortcode('pwtc_mapdb_leader_edit_ride', array('PwtcMapdb_Ride', 'shortcode_leader_edit_ride'));
		add_shortcode('pwtc_mapdb_manage_rides', array('PwtcMapdb_Ride', 'shortcode_manage_rides'));
		add_shortcode('pwtc_mapdb_delete_ride', array( 'PwtcMapdb_Ride', 'shortcode_delete_ride'));
		add_shortcode('pwtc_mapdb_manage_published_rides', array('PwtcMapdb_Ride', 'shortcode_manage_published_rides'));
		add_shortcode('pwtc_mapdb_manage_ride_templates', array('PwtcMapdb_Ride', 'shortcode_manage_ride_templates'));
		add_shortcode('pwtc_mapdb_manage_pending_rides', array('PwtcMapdb_Ride', 'shortcode_manage_pending_rides'));
		add_shortcode('pwtc_mapdb_new_ride_link', array('PwtcMapdb_Ride', 'shortcode_new_ride_link'));
		add_shortcode('pwtc_mapdb_schedule_template', array('PwtcMapdb_Ride', 'shortcode_schedule_template'));
		
	}
	
	/******************* Action Functions ******************/

	public static function load_javascripts() {
		$link = get_the_permalink();
		if ($link and (strpos($link, self::DELETE_RIDE_URI)!==false or strpos($link, self::EDIT_RIDE_URI)!==false or strpos($link, self::SUBMIT_RIDE_URI)!==false or 
		strpos($link, self::DELETE_TEMPLATE_URI)!==false or strpos($link, self::EDIT_TEMPLATE_URI)!==false or strpos($link, self::SUBMIT_TEMPLATE_URI)!==false)) {
			wp_enqueue_script('heartbeat');
		}
	}
	
	public static function status_change_callback($new, $old, $post) {
		if ($post->post_type == PwtcMapdb::POST_TYPE_RIDE) {
			if (($new == 'publish') && ($old == 'pending')) {
				if ($post->post_author != get_current_user_id()) {
					self::ride_published_email($post);
				}
			}
			else if (($new == 'draft') && ($old == 'pending')) {
				if ($post->post_author != get_current_user_id()) {
					self::ride_rejected_email($post);
				}
			}
		}
	}
	
	/******************* Filter Functions ******************/

	public static function refresh_post_lock($response, $data, $screen_id) {
		if ( array_key_exists( 'pwtc-refresh-post-lock', $data ) ) {
			$received = $data['pwtc-refresh-post-lock'];
			$send     = array();
	
			$post_id = absint( $received['post_id'] );
			if ( ! $post_id ) {
				return $response;
			}
		
			$user_id = self::check_post_lock( $post_id );
			$user    = get_userdata( $user_id );
			if ( $user ) {
				$name = $user->first_name . ' ' . $user->last_name;
				$error = array(
					'text' => sprintf('%s has taken over and is currently editing.', $name ),
				);
				$send['lock_error'] = $error;
			} 
			else {
				$new_lock = self::set_post_lock( $post_id );
				if ( $new_lock ) {
					$send['new_lock'] = implode( ':', $new_lock );
				}
			}
	
			$response['pwtc-refresh-post-lock'] = $send;
		}	

		return $response;
	}

	/******************* Shortcode Functions ******************/
	
	// Generates the [pwtc_mapdb_ride_breadcrumb] shortcode.
	public static function shortcode_ride_breadcrumb($atts) {
		$a = shortcode_atts(array('leaders' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';

		$return = '';
		if (isset($_GET['return'])) {
			$return = $_GET['return'];
		}

		if (empty($return)) {
			return '';
		}

		ob_start();
		include('ride-nav-breadcrumb.php');
		return ob_get_clean();
	}

	// Generates the [pwtc_mapdb_edit_ride] shortcode.
	public static function shortcode_edit_ride($atts, $content) {
		$a = shortcode_atts(array('leaders' => 'no', 'interval' => 'P14D', 'use_return' => 'no', 'email' => 'no', 'captain' => PwtcMapdb::ROAD_CAPTAIN_EMAIL, 'template' => 'no', 'coords' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';
		$use_return = $a['use_return'] == 'yes';
		$allow_email = $a['email'] == 'yes';
		$is_template = $a['template'] == 'yes';
		$set_coords = $a['coords'] == 'yes';
		$captain_email = $a['captain'];
		
		$is_ride_leader = false;
		$is_road_captain = false;
		$current_user = wp_get_current_user();
		$user_info = get_userdata($current_user->ID);
		if ($user_info) {
			$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);
			$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);
		}

		$return = '';
		if (isset($_GET['return'])) {
			$return = $_GET['return'];
		}

		if (isset($_POST['postid']) and isset($_POST['revert'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'ride-edit-form')) {
				wp_nonce_ays('');
			}
			
			if (!$is_road_captain and !$is_ride_leader) {
				wp_die('Authorization failed.', 403);
			}

			$postid = intval($_POST['postid']);
			
			$post_status = '';
			if (isset($_POST['post_status'])) {
				$post_status = $_POST['post_status'];
			}

			$my_post = array(
				'ID' => $postid,
				'post_status' => 'draft'
			);
			$status = wp_update_post($my_post);
			if ($status != $postid) {
				wp_die('Failed to update this post.', 403);
			}
			
			$email = 'no';
			if ($allow_email) {
				if ($post_status == 'pending' and !$is_road_captain) {
					$email = self::ride_unsubmitted_email($postid, $captain_email) ? 'yes': 'failed';
				}
			}

			wp_redirect(add_query_arg(array(
				'post' => $postid,
				'return' => urlencode($return),
				'op' => 'revert_draft',
				'email' => $email
			), get_permalink()), 303);

			if (!$is_template) {
				WordKeeper\System\Purge::purge_all();
			}

			exit;
		}
		else if (isset($_POST['postid']) and isset($_POST['title']) and $current_user->ID != 0) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'ride-edit-form')) {
				wp_nonce_ays('');
			}
			
			if (!$is_road_captain and !$is_ride_leader) {
				wp_die('Authorization failed.', 403);
			}
			
			$operation = '';
			$postid = intval($_POST['postid']);
			$title = trim($_POST['title']);
			$post_status = '';
			if (isset($_POST['post_status'])) {
				$post_status = $_POST['post_status'];
			}

			if ($postid != 0) {
				$my_post = array(
					'ID' => $postid,
					'post_title' => $title
				);
				if (isset($_POST['draft'])) {
					$my_post['post_status'] = 'draft';
					if ($post_status == 'pending') {
						$operation = 'rejected';
					}
					else if ($post_status == 'publish') {
						$operation = 'unpublished';
					}
					else {
						$operation = 'update_draft';
					}
				}
				else if (isset($_POST['pending'])) {
					$my_post['post_status'] = 'pending';
					if ($post_status == 'draft') {
						$operation = 'submit_review';
					}
					else {
						$operation = 'update_pending';
					}
				}
				else if (isset($_POST['publish'])) {
					$my_post['post_status'] = 'publish';
					if ($post_status == 'draft') {
						$operation = 'published_draft';
					}
					else if ($post_status == 'pending') {
						$operation = 'published';
					}
					else {
						$operation = 'update_published';
					}
				}
				//error_log(print_r($my_post, true));
				$status = wp_update_post( $my_post );	
				if ($status != $postid) {
					wp_die('Failed to update this post.', 403);
				}
				update_post_meta($postid, '_edit_last', $current_user->ID);
			}
			else {
				$my_post = array(
					'post_title'    => $title,
					'post_type'     => ($is_template ? PwtcMapdb::POST_TYPE_TEMPLATE : PwtcMapdb::POST_TYPE_RIDE),
                    			'post_status'   => 'draft',
                    			'post_author'   => $current_user->ID
				);
				$operation = 'insert';
				$postid = wp_insert_post( $my_post );
				if ($postid == 0) {
					wp_die('Failed to create a new post.', 403);
				}
			}

			if (isset($_POST['description'])) {
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_DESCRIPTION_KEY, $_POST['description'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_DESCRIPTION_KEY, $_POST['description'], $postid);
				}
			}	
			
			if (isset($_POST['leaders'])) {
				$new_leaders = json_decode($_POST['leaders']);
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_LEADERS_KEY, $new_leaders, $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_LEADERS_KEY, $new_leaders, $postid);
				}
			}

			if (!$is_template and isset($_POST['ride_date']) and isset($_POST['ride_time'])) {
				$date_str = trim($_POST['ride_date']) . ' ' . trim($_POST['ride_time']) . ':00';
				$timezone = new DateTimeZone(pwtc_get_timezone_string());
				$date = DateTime::createFromFormat('Y-m-d H:i:s', $date_str, $timezone);
				if ($date) {
					$date_str = $date->format('Y-m-d H:i:s');
					if ($new_post) {
						update_field(PwtcMapdb::RIDE_DATE_KEY, $date_str, $postid);
					}
					else {
						update_field(PwtcMapdb::RIDE_DATE, $date_str, $postid);
					}
				}
			}

			if (isset($_POST['start_address']) and isset($_POST['start_lat']) and isset($_POST['start_lng']) and isset($_POST['start_zoom'])) {
				$location = array('address' => $_POST['start_address'], 'lat' => floatval($_POST['start_lat']), 'lng' => floatval($_POST['start_lng']));
				if (!empty($_POST['start_zoom'])) {
					$location['zoom'] = intval($_POST['start_zoom']);
				}
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_START_LOCATION_KEY, $location, $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_START_LOCATION_KEY, $location, $postid);
				}
			}
			
			if (isset($_POST['start_location_comment'])) {
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_START_LOC_COMMENT_KEY, $_POST['start_location_comment'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_START_LOC_COMMENT_KEY, $_POST['start_location_comment'], $postid);
				}
			}

			if (isset($_POST['ride_type'])) {
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_TYPE_KEY, $_POST['ride_type'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_TYPE_KEY, $_POST['ride_type'], $postid);
				}
			}

			if (isset($_POST['ride_pace'])) {
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_PACE_KEY, $_POST['ride_pace'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_PACE_KEY, $_POST['ride_pace'], $postid);
				}
			}

			if (isset($_POST['attach_maps'])) {
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_ATTACH_MAP_KEY, $_POST['attach_maps'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_ATTACH_MAP_KEY, $_POST['attach_maps'], $postid);
				}
			}

			if ($postid != 0) {
				$attach_maps = get_field(PwtcMapdb::RIDE_ATTACH_MAP, $postid);
			}
			else {
				$attach_maps = false;
			}
	
			if ($attach_maps) {
				if (isset($_POST['maps'])) {
					$new_maps = json_decode($_POST['maps']);
					if ($is_template) {
						update_field(PwtcMapdb::TEMPLATE_MAPS_KEY, $new_maps, $postid);
					}
					else {
						update_field(PwtcMapdb::RIDE_MAPS_KEY, $new_maps, $postid);
					}
				}
				
				$terrain = get_actual_ride_terrain($postid);
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_TERRAIN_KEY, $terrain, $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_TERRAIN_KEY, $terrain, $postid);
				}
				
				$length = get_actual_ride_length($postid);
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_LENGTH_KEY, $length, $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_LENGTH_KEY, $length, $postid);
				}
				
				$max_length = get_actual_ride_maxlength($postid);
				if ($length == $max_length) {
					$max_length = null;
				}
				if ($is_template) {
					update_field(PwtcMapdb::TEMPLATE_MAX_LENGTH_KEY, $max_length, $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_MAX_LENGTH_KEY, $max_length, $postid);
				}
			}
			else {
				if (isset($_POST['distance'])) {
					if ($is_template) {
						update_field(PwtcMapdb::TEMPLATE_LENGTH_KEY, intval($_POST['distance']), $postid);
					}
					else {
						update_field(PwtcMapdb::RIDE_LENGTH_KEY, intval($_POST['distance']), $postid);
					}
				}
		
				if (isset($_POST['max_distance'])) {
					$d = trim($_POST['max_distance']);
					if (empty($d)) {
						if ($is_template) {
							update_field(PwtcMapdb::TEMPLATE_MAX_LENGTH_KEY, null, $postid);
						}
						else {
							update_field(PwtcMapdb::RIDE_MAX_LENGTH_KEY, null, $postid);
						}
					}
					else {
						if ($is_template) {
							update_field(PwtcMapdb::TEMPLATE_MAX_LENGTH_KEY, intval($d), $postid);
						}
						else {
							update_field(PwtcMapdb::RIDE_MAX_LENGTH_KEY, intval($d), $postid);
						}
					}
				}
		
				if (isset($_POST['ride_terrain'])) {
					if ($is_template) {
						update_field(PwtcMapdb::TEMPLATE_TERRAIN_KEY, $_POST['ride_terrain'], $postid);
					}
					else {
						update_field(PwtcMapdb::RIDE_TERRAIN_KEY, $_POST['ride_terrain'], $postid);
					}
				}
			}
			
			if (!$is_template) {
				if (isset($_POST['online_signup'])) {
					$online_signup = $_POST['online_signup'];
					delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE);
					add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE, $online_signup, true);
					if ($online_signup != 'no') {
						if (isset($_POST['members_only'])) {
							delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MEMBERS_ONLY);
							add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MEMBERS_ONLY, $_POST['members_only'] == '1', true);
						}
						if (isset($_POST['signup_cutoff'])) {
							delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_CUTOFF);
							add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_CUTOFF, abs(intval($_POST['signup_cutoff'])), true);
						}
						if (isset($_POST['signup_limit'])) {
							delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LIMIT);
							add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LIMIT, abs(intval($_POST['signup_limit'])), true);
						}
					}	
				}
			}

			$email = 'no';
			if ($allow_email) {
				if ($operation == 'submit_review' and !$is_road_captain) {
					$email = self::ride_submitted_email($postid, $captain_email) ? 'yes': 'failed';
				}
				/*
				else if ($operation == 'published') {
					$post = get_post($postid);
					if ($post->post_author != $current_user->ID) {
						$email = self::ride_published_email($post) ? 'yes': 'failed';
					}
				}
				else if ($operation == 'rejected') {
					$post = get_post($postid);
					if ($post->post_author != $current_user->ID) {
						$email = self::ride_rejected_email($post) ? 'yes': 'failed';
					}
				}
				*/
			}
			
			wp_redirect(add_query_arg(array(
				'post' => $postid,
				'return' => urlencode($return),
				'op' => $operation,
				'email' => $email
			), get_permalink()), 303);

			if (!$is_template) {
				WordKeeper\System\Purge::purge_all();
			}

			exit;
		}
		
		$email_status = 'no';
		if (isset($_GET['email'])) {
			$email_status = $_GET['email'];
		}

		$copy_ride = false;
		$template = false;
		if (isset($_GET['action'])) {
			if ($_GET['action'] == 'copy') {
				$copy_ride = true;
			}
			else if (!$is_template and $_GET['action'] == 'template') {
				$copy_ride = true;
				$template = true;
			}
		}
		
		$ride_link = '';
		$return_to_ride = '';
		if (!empty($return) and $use_return) {
			$ride_link = esc_url($return);
			$return_to_ride = self::create_return_link($ride_link);
		}

		if (isset($_GET['post'])) {
			$error = self::check_post_id(($template || $is_template));
			if (!empty($error)) {
				return $return_to_ride . $error;
			}
			$postid = intval($_GET['post']);
		}
		else {
			$postid = 0;
		}
		
		if (0 == $current_user->ID) {
			return $return_to_ride . '<div class="callout small alert"><p>You must be logged in to submit rides or ride templates.</p></div>';
		}

		$now_date = PwtcMapdb::get_current_time();

        	if ($postid != 0) {
			$post = get_post($postid);
            		$title = $post->post_title;
            		$author = $post->post_author;
            		$status = $post->post_status;
		}
		else {
            		$title = '';
            		$author = $current_user->ID;
            		$status = 'draft';
		}

		$author_name = '';
		$author_email = '';
		if ($author != 0) {
			$info = get_userdata($author);
			if ($info) {
				$author_name = $info->first_name . ' ' . $info->last_name;
				$author_email = $info->user_email;
			}
			else {
				$author_name = 'Unknown';
				$author_email = '';
			}
		}

		$ride_title = '';
		if ($postid != 0) {
			$ride_title = esc_html(get_the_title($postid));
		}
		
		if ($postid != 0 and !$copy_ride) {
			$lock_user = self::check_post_lock($postid);
		    if ($lock_user) {
				$info = get_userdata($lock_user);
				$name = $info->first_name . ' ' . $info->last_name;	
				return $return_to_ride . '<div class="callout small warning"><p>Post "' . $ride_title . '" is currently being edited by ' . $name . '. </p></div>';
			}
		}
		
		if ($is_template) {

			if ($postid == 0 and !$is_road_captain) {
				return $return_to_ride . '<div class="callout small warning"><p>You must be a road captain to create new ride templates.</p></div>';
			}

			if ($copy_ride and !$is_road_captain) {
				return $return_to_ride . '<div class="callout small warning"><p>You must be a road captain to copy ride templates.</p></div>';
			}

			if ($postid != 0 and !$copy_ride and !$is_road_captain) {
				return $return_to_ride . '<div class="callout small warning"><p>You must be a road captain to edit ride templates.</p></div>';
			}

		}
		else {
		
			if (!$allow_leaders and !$is_road_captain) {
				return $return_to_ride . '<div class="callout small warning"><p>You are not allowed to submit rides.</p></div>';
			}

			if ($copy_ride and !$is_road_captain) {
				if (!$is_ride_leader) {
					return $return_to_ride . '<div class="callout small warning"><p>You must be a ride leader to copy rides.</p></div>';
				}
			}

			if ($postid == 0 and !$is_road_captain) {
				if (!$is_ride_leader) {
					return $return_to_ride . '<div class="callout small warning"><p>You must be a ride leader to create new rides.</p></div>';
				}
			}

			if ($postid != 0 and !$copy_ride and !$is_road_captain) {
				if (!$is_ride_leader) {
					return $return_to_ride . '<div class="callout small warning"><p>You must be a ride leader to edit rides.</p></div>';
				}
				
				if ($author != $current_user->ID) {
					return $return_to_ride . '<div class="callout small warning"><p>You must be the author of ride "' . $ride_title . '" to edit it.</p></div>';
				}
				
				if ($status == 'publish') {
					return $return_to_ride . '<div class="callout small warning"><p>Ride "' . $ride_title . '" is published so you cannot edit it.</p></div>';
				}
				else if ($status == 'pending') {
					if (!empty($return) and $use_return) {
						$create_map_link = PwtcMapdb_Map::new_map_link($return);
						$create_ride_link = self::new_ride_link($return);
						$copy_ride_link = self::copy_ride_link($postid, $return);
					}
					else {
						$create_map_link = PwtcMapdb_Map::new_map_link();
						$create_ride_link = self::new_ride_link();
						$copy_ride_link = self::copy_ride_link($postid);
					}
					ob_start();
					include('ride-pending-form.php');
					return ob_get_clean();
				}
			}

			if ($postid != 0 and !$copy_ride and $status == 'publish') {
				$ride_datetime = PwtcMapdb::get_ride_start_time($postid);
				if ($ride_datetime < $now_date) {
					if ($is_road_captain) {
						if (function_exists('pwtc_mileage_ridesheet_exists')) {
							if (pwtc_mileage_ridesheet_exists($postid)) {
								return $return_to_ride . '<div class="callout small warning"><p>Ride "' . $ride_title . '" has already finished and has associated mileage so you cannot edit it.</p></div>';
							}
						}
						ob_start();
						include('ride-published-form.php');
						return ob_get_clean();
					}
					else {
						return $return_to_ride . '<div class="callout small warning"><p>Ride "' . $ride_title . '" has already finished so you cannot edit it.</p></div>';
					}
				}
			}
			
		}

		if ($postid != 0) {
			$description = get_field(PwtcMapdb::RIDE_DESCRIPTION, $postid, false);
		}
		else {
			$description = '';
			if (!empty($content)) {
				$description = wp_kses($content, array()).'(insert ride description here...)';
			}
		}

		$edit_leader = true;
		if ($postid != 0) {
			if ($is_road_captain) {
				$leaders = PwtcMapdb::get_leader_userids($postid);
			}
			else if ($copy_ride) {
				$leaders = [$current_user->ID];
			}
			else {
				$leaders = PwtcMapdb::get_leader_userids($postid);
			}
		}
		else {
			if ($is_road_captain) {
				$leaders = [];
			}
			else {
				$leaders = [$current_user->ID];
			}
		}

		$edit_date = true;
		if (!$is_template) {
			if ($postid != 0) {
				if ($template) {
					$ride_date = '';
					$ride_time = '';
				}
				else if ($copy_ride) {
					$ride_datetime = PwtcMapdb::get_ride_start_time($postid);
					$ride_time = $ride_datetime->format('H:i');
					$ride_date = '';
				}
				else {
					$ride_datetime = PwtcMapdb::get_ride_start_time($postid);
					$ride_date = $ride_datetime->format('Y-m-d');
					$ride_time = $ride_datetime->format('H:i');	
				}
			}
			else {
				$ride_date = '';
				$ride_time = '';
			}

			if ($postid == 0 or $copy_ride or !$is_road_captain) {
				if ($is_road_captain) {
					$interval = new DateInterval('P1D');
				}
				else {
					$interval = new DateInterval($a['interval']);
				}
				$min_datetime = PwtcMapdb::get_current_date();
				$min_datetime->add($interval);
				$min_date = $min_datetime->format('Y-m-d');
				$min_date_pretty = $min_datetime->format('m/d/Y');
			}
			else {
				$min_date = $min_date_pretty = '';
			}
		}
		
		$edit_title = $edit_date;

		if ($postid != 0) {
			$start_location = get_field(PwtcMapdb::RIDE_START_LOCATION, $postid);
			if (empty($start_location)) {
				$start_location = array('address' => '', 'lat' => 0, 'lng' => 0, 'zoom' => 16);
				$start_coords = '';
			}
			else {
				$start_coords = '('.$start_location['lat'].', '.$start_location['lng'].')';
			}
		}
		else {
			$start_location = array('address' => '', 'lat' => 0, 'lng' => 0, 'zoom' => 16);
			$start_coords = '';
		}

		if ($postid != 0) {
			$start_location_comment = get_field(PwtcMapdb::RIDE_START_LOC_COMMENT, $postid);
		}
		else {
			$start_location_comment = '';
		}

		if ($postid != 0) {
			$ride_type = get_field(PwtcMapdb::RIDE_TYPE, $postid);
		}
		else {
			$ride_type = 'nongroup';
		}

		if ($postid != 0) {
			$ride_pace = get_field(PwtcMapdb::RIDE_PACE, $postid);
		}
		else {
			$ride_pace = 'no';
		}

		if ($postid != 0) {
			$attach_maps = get_field(PwtcMapdb::RIDE_ATTACH_MAP, $postid);
		}
		else {
			$attach_maps = false;
		}

		if ($postid != 0) {
			$distance = get_field(PwtcMapdb::RIDE_LENGTH, $postid);
			$max_distance = get_field(PwtcMapdb::RIDE_MAX_LENGTH, $postid);
			$ride_terrain = get_field(PwtcMapdb::RIDE_TERRAIN, $postid);
			$maps_obj = get_field(PwtcMapdb::RIDE_MAPS, $postid);
			$maps = [];
			foreach ($maps_obj as $map) {
				$maps[] = $map->ID;
			}
			//TODO: discard any map IDs that have been deleted!
		}
		else {
			$distance = 0;
			$max_distance = '';
			$ride_terrain = [];
			$maps_obj = [];
			$maps = [];
		}
		
		if (!$is_template) {
			if ($postid != 0) {
				$online_signup = PwtcMapdb_Signup::get_signup_mode($postid);
				$members_only = PwtcMapdb_Signup::get_signup_members_only($postid);
				$signup_limit = PwtcMapdb_Signup::get_signup_limit($postid);
				$signup_cutoff = PwtcMapdb_Signup::get_signup_cutoff($postid);
			}
			else {
				$online_signup = 'no';
				$members_only = false;
				$signup_limit = 0;
				$signup_cutoff = 0;
			}
		}

		$operation = '';
		if (isset($_GET['op'])) {
			$operation = $_GET['op'];
		}

		if ($copy_ride) {
			$postid = 0;
		}

		if ($postid != 0) {
			self::set_post_lock($postid);
		}
		
		$edit_link = '';
		$view_link = '';
		if ($postid != 0) {
			$edit_link = add_query_arg(array(
				'post' => $postid
			), get_permalink());
			$view_link = get_permalink($postid);
		}
		
		if ($is_template) {
			$show_submitted_maps = false;
		}
		else {
			$show_submitted_maps = ($postid == 0 || $status == 'draft' || $status == 'pending');
		}
		
        	ob_start();
        	include('ride-edit-form.php');
        	return ob_get_clean();
	}

	// Generates the [pwtc_mapdb_leader_edit_ride] shortcode.
	public static function shortcode_leader_edit_ride($atts, $content) {
		$a = shortcode_atts(array('use_return' => 'no', 'email' => 'no', 'captain' => PwtcMapdb::ROAD_CAPTAIN_EMAIL), $atts);
		$use_return = $a['use_return'] == 'yes';
		$allow_email = $a['email'] == 'yes';
		$captain_email = $a['captain'];
		
		$current_user = wp_get_current_user();
		$user_info = get_userdata($current_user->ID);
		$return = '';
		if (isset($_GET['return'])) {
			$return = $_GET['return'];
		}

		if (isset($_POST['postid']) and isset($_POST['title']) and $current_user->ID != 0) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'ride-leader-edit-form')) {
				wp_nonce_ays('');
			}
			$postid = intval($_POST['postid']);
			$title = trim($_POST['title']);
			$my_post = array(
				'ID' => $postid,
				'post_title' => $title
			);
			$status = wp_update_post( $my_post );	
			if ($status != $postid) {
				wp_die('Failed to update this post.', 403);
			}
			update_post_meta($postid, '_edit_last', $current_user->ID);
			if (isset($_POST['description'])) {
				update_field(PwtcMapdb::RIDE_DESCRIPTION_KEY, $_POST['description'], $postid);
			}
			if (isset($_POST['start_location_comment'])) {
				update_field(PwtcMapdb::RIDE_START_LOC_COMMENT_KEY, $_POST['start_location_comment'], $postid);
			}
			if (isset($_POST['ride_time'])) {
				$ride_datetime = PwtcMapdb::get_ride_start_time($postid);
				$ride_date = $ride_datetime->format('Y-m-d');
				$date_str = $ride_date . ' ' . trim($_POST['ride_time']) . ':00';
				$timezone = new DateTimeZone(pwtc_get_timezone_string());
				$date = DateTime::createFromFormat('Y-m-d H:i:s', $date_str, $timezone);
				if ($date) {
					$date_str = $date->format('Y-m-d H:i:s');
					update_field(PwtcMapdb::RIDE_DATE, $date_str, $postid);
				}
			}
			
			wp_redirect(add_query_arg(array(
				'post' => $postid,
				'return' => urlencode($return)
			), get_permalink()), 303);
			exit;
		}

		$ride_link = '';
		$return_to_ride = '';
		if (!empty($return) and $use_return) {
			$ride_link = esc_url($return);
			$return_to_ride = self::create_return_link($ride_link);
		}

		$error = self::check_post_id();
		if (!empty($error)) {
			return $return_to_ride . $error;
		}
		$postid = intval($_GET['post']);
		
		if (0 == $current_user->ID) {
			return $return_to_ride . '<div class="callout small alert"><p>You must be logged in to edit rides.</p></div>';
		}

		$now_date = PwtcMapdb::get_current_time();

		$post = get_post($postid);
            	$title = $post->post_title;
		$status = $post->post_status;
		$ride_title = esc_html(get_the_title($postid));
		$description = get_field(PwtcMapdb::RIDE_DESCRIPTION, $postid, false);
		$start_location_comment = get_field(PwtcMapdb::RIDE_START_LOC_COMMENT, $postid);
		$ride_datetime = PwtcMapdb::get_ride_start_time($postid);
		$ride_date = $ride_datetime->format('Y-m-d');
		$ride_time = $ride_datetime->format('H:i');

		$lock_user = self::check_post_lock($postid);
		if ($lock_user) {
			$info = get_userdata($lock_user);
			$name = $info->first_name . ' ' . $info->last_name;	
			return $return_to_ride . '<div class="callout small warning"><p>Post "' . $ride_title . '" is currently being edited by ' . $name . '. </p></div>';
		}

		if ($status != 'publish') {
			return $return_to_ride . '<div class="callout small warning"><p>Ride "' . $ride_title . '" is not published so you cannot edit it.</p></div>';
		}

		$ride_datetime = PwtcMapdb::get_ride_start_time($postid);
		if ($ride_datetime < $now_date) {
			return $return_to_ride . '<div class="callout small warning"><p>Ride "' . $ride_title . '" has already finished so you cannot edit it.</p></div>';
		}

		$denied = true;
		$leaders = PwtcMapdb::get_leader_userids($postid);
		foreach ($leaders as $item) {
			if ($current_user->ID == $item) {
				$denied = false;
				break;
			}
		}
		if ($denied) {
			return $return_to_ride . '<div class="callout small warning"><p>You must be a leader of ride "' . $ride_title . '" to edit it.</p></div>';
		}
		
	        ob_start();
        	include('ride-leader-edit-form.php');
        	return ob_get_clean();
	}

	// Generates the [pwtc_mapdb_delete_ride] shortcode.
	public static function shortcode_delete_ride($atts) {
		$a = shortcode_atts(array('leaders' => 'no', 'use_return' => 'no', 'template' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';
		$use_return = $a['use_return'] == 'yes';
		$is_template = $a['template'] == 'yes';

		$is_ride_leader = false;
		$is_road_captain = false;
		$current_user = wp_get_current_user();
		$user_info = get_userdata($current_user->ID);
		if ($user_info) {
			$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);
			$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);
		}
		
		$return = '';
		if (isset($_GET['return'])) {
			$return = $_GET['return'];
		}

		if (isset($_POST['postid'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'ride-delete-form')) {
				wp_nonce_ays('');
			}
			
			if (!$is_road_captain and !$is_ride_leader) {
				wp_die('Authorization failed.', 403);
			}
			
			$postid = intval($_POST['postid']);
			if (isset($_POST['delete_ride'])) {
				if (wp_trash_post($postid)) {
					wp_redirect(add_query_arg(array(
						'post' => $postid,
						'return' => urlencode($return)
					), get_permalink()), 303);
				}
				else {
					wp_die('Failed to delete this post.', 403);
				}
			}
			else if (isset($_POST['undo_delete'])) {
				if (wp_untrash_post($postid)) {
					wp_redirect(add_query_arg(array(
						'post' => $postid,
						'return' => urlencode($return)
					), get_permalink()), 303);
				}
				else {
					wp_die('Failed to undo the delete of this post.', 403);
				}
			}
			exit;
		}

		$ride_link = '';
		$return_to_ride = '';
		if (!empty($return) and $use_return) {
			$ride_link = esc_url($return);
			$return_to_ride = self::create_return_link($ride_link);
		}
		
		$error = self::check_post_id($is_template, true);
		if (!empty($error)) {
			return $return_to_ride . $error;
		}
		$postid = intval($_GET['post']);
		
		if (0 == $current_user->ID) {
			return $return_to_ride . '<div class="callout small alert"><p>You must be logged in to delete rides or ride templates.</p></div>';
		}

		$ride_title = esc_html(get_the_title($postid));

		$post = get_post($postid);
		$author = $post->post_author;
		$status = $post->post_status;

		if ($is_template) {

			if (!$is_road_captain) {
				return $return_to_ride . '<div class="callout small warning"><p>You must be a road captain to delete ride templates.</p></div>';
			}

		}
		else {

			if (!$allow_leaders and !$is_road_captain) {
				return $return_to_ride . '<div class="callout small warning"><p>You are not allowed to delete rides.</p></div>';
			}

			if ($status == 'publish') {
				if ($is_road_captain) {
					$now_date = PwtcMapdb::get_current_time();
					$ride_datetime = PwtcMapdb::get_ride_start_time($postid);
					if ($ride_datetime < $now_date) {
						return $return_to_ride . '<div class="callout small warning"><p>Ride "' . $ride_title . '" has already finished so you cannot delete it.</p></div>';
					}
				}
				else {
					return $return_to_ride . '<div class="callout small warning"><p>Ride "' . $ride_title . '" is published so you cannot delete it.</p></div>';
				}
			}
			else if ($status == 'pending') {
				return $return_to_ride . '<div class="callout small warning"><p>Ride "' . $ride_title . '" is pending review so you cannot delete it.</p></div>';
			}

			if ($author != $current_user->ID and !$is_road_captain) {
				return $return_to_ride . '<div class="callout small warning"><p>You must be the author of ride "' . $ride_title . '" to delete it.</p></div>';
			}

		}

		$deleted = false;
		if ($status != 'trash') {
			$lock_user = self::check_post_lock($postid);
			if ($lock_user) {
				$info = get_userdata($lock_user);
				$name = $info->first_name . ' ' . $info->last_name;	
				return $return_to_ride . '<div class="callout small warning"><p>Post "' . $ride_title . '" is currently being edited by ' . $name . '.</p></div>';
			}
			self::set_post_lock($postid);
		}
		else {
			$deleted = true;
		}

        	ob_start();
        	include('ride-delete-form.php');
        	return ob_get_clean();
	}

	// Generates the [pwtc_mapdb_manage_rides] shortcode.
	public static function shortcode_manage_rides($atts) {
		$a = shortcode_atts(array('leaders' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';
		
		$current_user = wp_get_current_user();

		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to view the rides that you have created.</p></div>';
		}

		$user_info = get_userdata($current_user->ID);
		
		$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);

		$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);

		if (!$is_road_captain) {
			if (!$allow_leaders) {
				return '<div class="callout small warning"><p>You are not allowed to view the rides that you have created.</p></div>';
			}
			if (!$is_ride_leader) {
				return '<div class="callout small warning"><p>You must be a ride leader to view the rides that you have created.</p></div>';
			}
		}

		$author_name = $user_info->first_name . ' ' . $user_info->last_name;

		$status = array('draft');
		if ($allow_leaders) {
			$status[] = 'pending';
		}
		$query_args = [
			'posts_per_page' => -1,
			'post_status' => $status,
			'author' => $current_user->ID,
			'post_type' => PwtcMapdb::POST_TYPE_RIDE,
			'meta_key'  => PwtcMapdb::RIDE_DATE,
			'meta_type' => 'DATETIME',
			'orderby' => ['meta_value' => 'DESC'],
		];
		$query = new WP_Query($query_args);

		$return_uri = $_SERVER['REQUEST_URI'];

		ob_start();
		include('manage-rides-form.php');
		return ob_get_clean();
	}	

	// Generates the [pwtc_mapdb_manage_pending_rides] shortcode.
	public static function shortcode_manage_pending_rides($atts) {
		$a = shortcode_atts(array('leaders' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';

		$current_user = wp_get_current_user();

		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to review the pending rides.</p></div>';
		}
		
		$user_info = get_userdata($current_user->ID);
		
		$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);

		if (!$is_road_captain) {
			return '<div class="callout small warning"><p>You must be a road captain to review the pending rides.</p></div>';
		}

		$query_args = [
			'posts_per_page' => -1,
			'post_status' => 'pending',
			'post_type' => PwtcMapdb::POST_TYPE_RIDE,
			'meta_key'  => PwtcMapdb::RIDE_DATE,
			'meta_type' => 'DATETIME',
			'orderby' => ['meta_value' => 'DESC'],
		];
		$query = new WP_Query($query_args);

		$return_uri = $_SERVER['REQUEST_URI'];

		ob_start();
		include('manage-pending-rides-form.php');
		return ob_get_clean();
	}	

	// Generates the [pwtc_mapdb_manage_published_rides] shortcode.
	public static function shortcode_manage_published_rides($atts) {
		$a = shortcode_atts(array('leaders' => 'no', 'limit' => '10', 'status' => 'all', 'search' => 'close', 'sort' => 'start', 'monthonly' => 'no', 'viewonly' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';
		$limit = intval($a['limit']);
		$search_open = $a['search'] == 'open';
		$monthonly = $a['monthonly'] == 'yes';
		$viewonly = $a['viewonly'] == 'yes';

		$current_user = wp_get_current_user();

		if (isset($_POST['ride_title']) and isset($_POST['ride_leader']) and isset($_POST['ride_month']) and isset($_POST['offset'])) {
			/*
			if (0 == $current_user->ID) {
				wp_die('Authorization failed.', 403);
			}
			*/

			$ride_status = 'publish';
			if (isset($_POST['ride_status'])) {
				$ride_status = $_POST['ride_status'];
			}
			$sort_by = 'start';
			if (isset($_POST['sort_by'])) {
				$sort_by = $_POST['sort_by'];
			}
			$ride_month = $_POST['ride_month'];
			if (empty($ride_month) and $monthonly) {
				$now = PwtcMapdb::get_current_time();
				$ride_month = $now->format('Y-m');
			}
			wp_redirect(add_query_arg(array(
				'month' => $ride_month,
				'leader' => $_POST['ride_leader'],
				'title' => urlencode(stripslashes(trim($_POST['ride_title']))),
				'status' => $ride_status,
				'sort' => $sort_by,
				'offset' => intval($_POST['offset'])
			), get_permalink()), 303);
			exit;
		}
		
		if (0 == $current_user->ID or $viewonly) {
			//return '<div class="callout small warning"><p>You must be logged in to view the published rides.</p></div>';
			$is_road_captain = false;
			$is_ride_leader = false;
		}
		else {
			$user_info = get_userdata($current_user->ID);
			$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);
			$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);
		}

		if (isset($_GET['title'])) {
			$ride_title = $_GET['title'];
		}
		else {
			$ride_title = '';
		}
		
		if (isset($_GET['status']) and $is_road_captain) {
			$ride_status = $_GET['status'];
			if ($ride_status == 'all' or $ride_status == 'mine') {
				$post_status = ['publish', 'pending', 'draft'];
			}
			else {
				$post_status = $ride_status;
			}
		}
		else if ($is_road_captain) {
			$ride_status = $a['status'];
			if ($ride_status == 'all' or $ride_status == 'mine') {
				$post_status = ['publish', 'pending', 'draft'];
			}
			else {
				$post_status = $ride_status;
			}
		}
		else {
			$ride_status = 'publish';
			$post_status = $ride_status;
		}

		if (isset($_GET['leader'])) {
			$ride_leader = $_GET['leader'];
		}
		else {
			if ($is_road_captain or !$is_ride_leader or !$allow_leaders) {
				$ride_leader = 'anyone';
			}
			else {
				$ride_leader = $current_user->ID;
			}
		}
		
		$now = PwtcMapdb::get_current_time();

		if (isset($_GET['month'])) {
			$ride_month = $_GET['month'];
		}
		else {
			if ($monthonly) {
				$ride_month = $now->format('Y-m');
			}
			else {
				$ride_month = '';
			}
		}
		
		if (!empty($ride_month)) {
			$timezone = new DateTimeZone(pwtc_get_timezone_string());
			$interval = new DateInterval('P1M');	
			$this_month = DateTime::createFromFormat('Y-m-d H:i:s', $ride_month.'-01 00:00:00', $timezone);
			$next_month = DateTime::createFromFormat('Y-m-d H:i:s', $ride_month.'-01 00:00:00', $timezone);
			$next_month->add($interval);
			$ride_month = $this_month->format('Y-m');
		}

		if (isset($_GET['offset'])) {
			$offset = intval($_GET['offset']);
		}
		else {
			$offset = 0;
		}
		
		if (isset($_GET['sort']) and $is_road_captain) {
			$sort_by = $_GET['sort'];
		}
		else if ($is_road_captain) {
			$sort_by = $a['sort'];
		}
		else {
			$sort_by = 'start';
		}

		$query_args = [
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'post_status' => $post_status,
			'post_type' => PwtcMapdb::POST_TYPE_RIDE,
			'meta_query' => [],
		];
		
		if ($sort_by == 'date') {
			$query_args['orderby'] = 'date';
			$query_args['order'] = 'DESC';
		}
		else if ($sort_by == 'title') {
			$query_args['orderby'] = 'title';
			$query_args['order'] = 'ASC';
		}
		else if ($sort_by == 'start') {
			$query_args['meta_key'] = PwtcMapdb::RIDE_DATE;
			$query_args['meta_type'] = 'DATETIME';
			if (!empty($ride_month)) {
				$query_args['orderby'] = ['meta_value' => 'ASC'];
			}
			else {
				$query_args['orderby'] = ['meta_value' => 'DESC'];
			}
		}
		
		if ($ride_status == 'mine') {
			$query_args['author'] = $current_user->ID;
		}
		if (!empty($ride_month)) {
			$query_args['meta_query'][] = [
				'key' => PwtcMapdb::RIDE_DATE,
				'value' => [$this_month->format('Y-m-01 00:00:00'), $next_month->format('Y-m-01 00:00:00')],
				'compare' => 'BETWEEN',
				'type' => 'DATETIME'
			];
		}
		if (!empty($ride_title)) {
			$query_args['s'] = $ride_title;	
		}	
		if ($ride_leader != 'anyone') {
			if ($ride_leader == 'me') {
				$userid = $current_user->ID;
			}
			else {
				$userid = intval($ride_leader);
			}
			$query_args['meta_query'][] = [
				'key' => PwtcMapdb::RIDE_LEADERS,
				'value' => '"' . $userid . '"',
				'compare' => 'LIKE'
			];
		}
		if ($limit > 0)	{
			$query_args['offset'] = $offset;
		}	 
		$query = new WP_Query($query_args);
		
		$leaders = PwtcMapdb::fetch_ride_leaders();

		$return_uri = $_SERVER['REQUEST_URI'];

		ob_start();
		include('manage-published-rides-form.php');
		return ob_get_clean();
	}	

	// Generates the [pwtc_mapdb_manage_ride_templates] shortcode.
	public static function shortcode_manage_ride_templates($atts) {
		$a = shortcode_atts(array('leaders' => 'no', 'limit' => '10', 'status' => 'all', 'search' => 'close', 'sort' => 'date'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';
		$limit = intval($a['limit']);
		$search_open = $a['search'] == 'open';
		
		$current_user = wp_get_current_user();

		if (isset($_POST['ride_title']) and isset($_POST['ride_leader']) and isset($_POST['offset'])) {
			if (0 == $current_user->ID) {
				wp_die('Authorization failed.', 403);
			}
			
			$ride_status = 'publish';
			if (isset($_POST['ride_status'])) {
				$ride_status = $_POST['ride_status'];
			}
			$sort_by = 'date';
			if (isset($_POST['sort_by'])) {
				$sort_by = $_POST['sort_by'];
			}
			wp_redirect(add_query_arg(array(
				'leader' => $_POST['ride_leader'],
				'title' => urlencode(stripslashes(trim($_POST['ride_title']))),
				'status' => $ride_status,
				'sort' => $sort_by,
				'offset' => intval($_POST['offset'])
			), get_permalink()), 303);
			exit;
		}
		
		if (0 == $current_user->ID) {
			return '<div class="callout small warning"><p>You must be logged in to view the ride templates.</p></div>';
		}

		$user_info = get_userdata($current_user->ID);
		
		$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);

		$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);

		if (isset($_GET['title'])) {
			$ride_title = $_GET['title'];
		}
		else {
			$ride_title = '';
		}
		
		if (isset($_GET['status']) and $is_road_captain) {
			$ride_status = $_GET['status'];
			if ($ride_status == 'all' or $ride_status == 'mine') {
				$post_status = ['publish', 'pending', 'draft'];
			}
			else {
				$post_status = $ride_status;
			}
		}
		else if ($is_road_captain) {
			$ride_status = $a['status'];
			if ($ride_status == 'all' or $ride_status == 'mine') {
				$post_status = ['publish', 'pending', 'draft'];
			}
			else {
				$post_status = $ride_status;
			}
		}
		else {
			$ride_status = 'publish';
			$post_status = $ride_status;
		}

		if (isset($_GET['leader'])) {
			$ride_leader = $_GET['leader'];
		}
		else {
			if ($is_road_captain or !$is_ride_leader) {
				$ride_leader = 'anyone';
			}
			else {
				$ride_leader = $current_user->ID;
			}
		}

		if (isset($_GET['offset'])) {
			$offset = intval($_GET['offset']);
		}
		else {
			$offset = 0;
		}
		
		if (isset($_GET['sort']) and $is_road_captain) {
			$sort_by = $_GET['sort'];
		}
		else if ($is_road_captain) {
			$sort_by = $a['sort'];
		}
		else {
			$sort_by = 'date';
		}

		$query_args = [
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'post_status' => $post_status,
			'post_type' => 'ride_template',
		];
		
		if ($sort_by == 'date') {
			$query_args['orderby'] = 'date';
			$query_args['order'] = 'DESC';
		}
		else if ($sort_by == 'title') {
			$query_args['orderby'] = 'title';
			$query_args['order'] = 'ASC';
		}

		if ($ride_status == 'mine') {
			$query_args['author'] = $current_user->ID;
		}

		if (!empty($ride_title)) {
			$query_args['s'] = $ride_title;	
		}	
		if ($ride_leader != 'anyone') {
			if ($ride_leader == 'me') {
				$userid = $current_user->ID;
			}
			else {
				$userid = intval($ride_leader);
			}
			$query_args['meta_query'][] = [
				'key' => PwtcMapdb::RIDE_LEADERS,
				'value' => '"' . $userid . '"',
				'compare' => 'LIKE'
			];
		}
		if ($limit > 0)	{
			$query_args['offset'] = $offset;
		}	
		$query = new WP_Query($query_args);
		
		$leaders = PwtcMapdb::fetch_ride_leaders();

		$return_uri = $_SERVER['REQUEST_URI'];

		ob_start();
		include('manage-ride-templates-form.php');
		return ob_get_clean();
	}	

	// Generates the [pwtc_mapdb_new_ride_link] shortcode.
	public static function shortcode_new_ride_link($atts, $content) {
		$a = shortcode_atts(array('class' => '', 'template' => 'no'), $atts);
		$is_template = $a['template'] == 'yes';
		
		$return_uri = $_SERVER['REQUEST_URI'];

		if ($is_template) {
			if (empty($content)) {
				$content = 'new ride template';
			}
			$new_link = self::new_template_link($return_uri);
		}
		else {
			if (empty($content)) {
				$content = 'new ride';
			}
			$new_link = self::new_ride_link($return_uri);
		}
		
		return '<a class="' . $a['class'] . '" href="' . $new_link . '">' . $content . '</a>';
	}
	
	// Generates the [pwtc_mapdb_schedule_template] shortcode.
	public static function shortcode_schedule_template($atts) {
		$a = shortcode_atts(array('use_return' => 'no'), $atts);
		$use_return = $a['use_return'] == 'yes';

		$is_road_captain = false;
		$current_user = wp_get_current_user();
		$user_info = get_userdata($current_user->ID);
		if ($user_info) {
			$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);
		}

		$postid = 0;
		$post_check_error = self::check_post_id(true);
		if (empty($post_check_error)) {
			$postid = intval($_GET['post']);
		}

		$return = '';
		if (isset($_GET['return'])) {
			$return = $_GET['return'];
		}

		if (isset($_POST['ride_time']) and isset($_POST['schedule_dates']) and isset($_POST['create_as']) and isset($_POST['return_to'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'schedule-template-form')) {
				wp_nonce_ays('');
			}

			if ($postid == 0) {
				wp_die('Invalid ride template post ID.', 403);
			}

			if (!$is_road_captain) {
				wp_die('Authorization failed.', 403);
			}

			$post = get_post($postid);
            		$title = $post->post_title;
			$type = get_field(PwtcMapdb::RIDE_TYPE, $postid);
			$pace = get_field(PwtcMapdb::RIDE_PACE, $postid);
			$desc = get_field(PwtcMapdb::RIDE_DESCRIPTION, $postid, false);
			$location = get_field(PwtcMapdb::RIDE_START_LOCATION, $postid, false);
			$loc_comment = get_field(PwtcMapdb::RIDE_START_LOC_COMMENT, $postid);
			$leaders = [];
			foreach (get_field(PwtcMapdb::RIDE_LEADERS, $postid) as $leader) {
				$leaders[] = $leader['ID'];
			}
			$attach = get_field(PwtcMapdb::RIDE_ATTACH_MAP, $postid);
			$maps = get_field(PwtcMapdb::RIDE_MAPS, $postid, false);
			//$terrain = get_field(PwtcMapdb::RIDE_TERRAIN, $postid);
			//$length = get_field(PwtcMapdb::RIDE_LENGTH, $postid);
			//$max_length = get_field(PwtcMapdb::RIDE_MAX_LENGTH, $postid);
			$terrain = get_actual_ride_terrain($postid);
			$length = get_actual_ride_length($postid);
			$max_length = get_actual_ride_maxlength($postid);
			if ($length == $max_length) {
				$max_length = null;
			}

			if ($_POST['create_as'] == 'draft') {
				$newstatus = 'draft';
			}
			else {
				$newstatus = 'publish';
			}
			$timezone = new DateTimeZone(pwtc_get_timezone_string());
			foreach ($_POST['schedule_dates'] as $event) {
				$date_str = trim($event) . ' ' . trim($_POST['ride_time']) . ':00';
				$date = DateTime::createFromFormat('Y-m-d H:i:s', $date_str, $timezone);
				if ($date === false) {
					wp_die('Invalid date format.', 403);
				}
				$my_post = array(
					'post_title'    => $title,
					'post_type'     => PwtcMapdb::POST_TYPE_RIDE,
                    			'post_status'   => $newstatus,
                   	 		'post_author'   => $current_user->ID
				);
				$newpostid = wp_insert_post($my_post);
				if ($newpostid == 0) {
					wp_die('Failed to create a new post.', 403);
				}
				update_field(PwtcMapdb::RIDE_DATE_KEY, $date->format('Y-m-d H:i:s'), $newpostid);
				update_field(PwtcMapdb::RIDE_TYPE_KEY, $type, $newpostid);
				update_field(PwtcMapdb::RIDE_PACE_KEY, $pace, $newpostid);
				update_field(PwtcMapdb::RIDE_DESCRIPTION_KEY, $desc, $newpostid);
				update_field(PwtcMapdb::RIDE_START_LOCATION_KEY, $location, $newpostid);
				update_field(PwtcMapdb::RIDE_START_LOC_COMMENT_KEY, $loc_comment, $newpostid);
				update_field(PwtcMapdb::RIDE_LEADERS_KEY, $leaders, $newpostid);
				update_field(PwtcMapdb::RIDE_ATTACH_MAP_KEY, $attach, $newpostid);
				update_field(PwtcMapdb::RIDE_MAPS_KEY, $maps, $newpostid);
				update_field(PwtcMapdb::RIDE_TERRAIN_KEY, $terrain, $newpostid);
				update_field(PwtcMapdb::RIDE_LENGTH_KEY, $length, $newpostid);
				update_field(PwtcMapdb::RIDE_MAX_LENGTH_KEY, $max_length, $newpostid);
			}

			if ($_POST['return_to'] == 'rides') {
				wp_redirect(add_query_arg(array(
					'sort' => 'date',
					'status' => 'mine'
				), '/manage-scheduled-rides'), 303);
			}
			else if (!empty($return)) {
				wp_redirect($return, 303);
			}
			else {
				wp_redirect(add_query_arg(array(
					'post' => $postid
				), get_permalink()), 303);				
			}
			exit;
		}
		
		$return_link = '';
		$return_to_page = '';
		if (!empty($return) and $use_return) {
			$return_link = esc_url($return);
			$return_to_page = self::create_return_link($return_link);
		}

		if (!empty($post_check_error)) {
			return $return_to_page . $post_check_error;
		}
		
		if ( 0 == $current_user->ID ) {
			return $return_to_page . '<div class="callout small alert"><p>You must be logged in to schedule ride templates.</p></div>';
		}

		if (!$is_road_captain) {
			return $return_to_page . '<div class="callout small warning"><p>You must be a road captain to schedule ride templates.</p></div>';
		}

		$template_title = esc_html(get_the_title($postid));

		$now_date = PwtcMapdb::get_current_time();
		$min_date = $now_date->format('Y-m-d');
		
		ob_start();
		include('schedule-ride-template-form.php');
		return ob_get_clean();
	}

	/******************* Utility Functions ******************/
    
    	public static function check_post_id($template = false, $ignore_trash = false) {
		if (!isset($_GET['post'])) {
			return '<div class="callout small alert"><p>Post ID parameter is missing.</p></div>';
		}

		$postid = intval($_GET['post']);
		if ($postid == 0) {
			return '<div class="callout small alert"><p>Post ID parameter is invalid, it must be an integer number.</p></div>';
		}

		$post = get_post($postid);
		if (!$post) {
			return '<div class="callout small alert"><p>Post ' . $postid . ' does not exist, it may have been deleted.</p></div>';
		}

		if ($template) {
			if (get_post_type($post) != 'ride_template') {
				return '<div class="callout small alert"><p>Post ' . $postid . ' is not a ride template.</p></div>';
			}
		}
		else {
			if (get_post_type($post) != PwtcMapdb::POST_TYPE_RIDE) {
				return '<div class="callout small alert"><p>Post ' . $postid . ' is not a scheduled ride.</p></div>';
			}
		}

		$post_status = get_post_status($post);
		if ($template) {
			if ($post_status != 'publish' and $post_status != 'draft' and $post_status != 'pending') {
				if ($post_status == 'trash') {
					if (!$ignore_trash) {
						return '<div class="callout small alert"><p>Ride template post ' . $postid . ' has been deleted.</p></div>';
					}
				}
				else {
					return '<div class="callout small alert"><p>Ride template post ' . $postid . ' is not published. Its current status is "' . $post_status . '"</p></div>';
				}
			}
		}
		else {
			if ($post_status != 'publish' and $post_status != 'draft' and $post_status != 'pending') {
				if ($post_status == 'trash') {
					if (!$ignore_trash) {
						return '<div class="callout small alert"><p>Ride post ' . $postid . ' has been deleted.</p></div>';
					}
				}
				else {
					return '<div class="callout small alert"><p>Ride post ' . $postid . ' is not draft, pending or published. Its current status is "' . $post_status . '"</p></div>';
				}
			}
		}

		return '';
	}

	public static function get_refresh_script() {
		return <<<EOT
<script type="text/javascript">
	jQuery(document).ready(function($) { 
		var opener_win = window.opener;
		if (opener_win) {
			opener_win.location.reload();
		}
	});
</script>	
EOT;
	}

	public static function create_return_link($ride_url) {
		return '<ul class="breadcrumbs"><li><a href="' . $ride_url . '">Back to Previous Page</a></li></ul>';
	}

	public static function set_post_lock( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}
	 
		$user_id = get_current_user_id();
		if ( 0 == $user_id ) {
			return false;
		}
	 
		$now  = time();
		$lock = "$now:$user_id";
	 
		update_post_meta( $post->ID, '_edit_lock', $lock );
	 
		return array( $now, $user_id );
	}

	public static function check_post_lock( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}
	 
		$lock = get_post_meta( $post->ID, '_edit_lock', true );
		if ( ! $lock ) {
			return false;
		}
	 
		$lock = explode( ':', $lock );
		$time = $lock[0];
		$user = isset( $lock[1] ) ? $lock[1] : get_post_meta( $post->ID, '_edit_last', true );
	 
		if ( ! get_userdata( $user ) ) {
			return false;
		}
	 
		$time_window = 150;
		if ( $time && $time > time() - $time_window && get_current_user_id() != $user ) {
			return $user;
		}
	 
		return false;
	}

	public static function delete_ride_link($postid, $return=false) {
		$uri = self::DELETE_RIDE_URI;
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}

	public static function new_ride_link($return=false) {
		$uri = self::SUBMIT_RIDE_URI;
		if ($return) {
			$uri .= '?return=' . urlencode($return);
		}
		return esc_url($uri);
	}

	public static function copy_ride_link($postid, $return=false) {
		$uri = self::SUBMIT_RIDE_URI;
		$uri .= '?post=' . $postid;
		$uri .= '&action=copy';
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}

	public static function edit_ride_link($postid, $return=false) {
		$uri = self::EDIT_RIDE_URI;
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}
	
	public static function schedule_template_link($postid, $return=false) {
		$uri = '/schedule-ride-template';
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}

	public static function template_ride_link($postid, $return=false) {
		$uri = self::SUBMIT_RIDE_URI;
		$uri .= '?post=' . $postid;
		$uri .= '&action=template';
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}
	
	public static function delete_template_link($postid, $return=false) {
		$uri = self::DELETE_TEMPLATE_URI;
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}

	public static function new_template_link($return=false) {
		$uri = self::SUBMIT_TEMPLATE_URI;
		if ($return) {
			$uri .= '?return=' . urlencode($return);
		}
		return esc_url($uri);
	}

	public static function copy_template_link($postid, $return=false) {
		$uri = self::SUBMIT_TEMPLATE_URI;
		$uri .= '?post=' . $postid;
		$uri .= '&action=copy';
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}

	public static function edit_template_link($postid, $return=false) {
		$uri = self::EDIT_TEMPLATE_URI;
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}
	
	public static function ride_submitted_email($postid, $captain_email) {
		$ride_title = esc_html(get_the_title($postid));
		$ride_date = PwtcMapdb::get_ride_start_time($postid)->format('m/d/Y g:ia');
		$ride_url = get_permalink($postid);
		$ride_link = '<a href="' . $ride_url . '">' . $ride_title . '</a>';
		$subject = 'Ride Submitted for Review';
		$message = <<<EOT
The following ride has been submitted for road captain review:<br>
$ride_link on $ride_date.<br>
To review this ride, use a browser to log in to your club account (you must be a road captain) and open the ride by clicking its link. Make any changes that you see fit and publish the ride or reject (return it to draft.)<br>
Do not reply to this email!<br>
EOT;
		$headers = ['Content-type: text/html'];
		return wp_mail($captain_email, $subject , $message, $headers);
	}
	
	public static function ride_unsubmitted_email($postid, $captain_email) {
		$ride_title = esc_html(get_the_title($postid));
		$ride_date = PwtcMapdb::get_ride_start_time($postid)->format('m/d/Y g:ia');
		$subject = 'Ride Unsubmitted';
		$message = <<<EOT
The author has reverted the following ride back to draft:<br>
$ride_title on $ride_date.<br>
Ignore the previous review request email and do not review this ride.<br>
Do not reply to this email!<br>
EOT;
		$headers = ['Content-type: text/html'];
		return wp_mail($captain_email, $subject , $message, $headers);
	}

	public static function ride_published_email($post) {
		$author_email = get_the_author_meta('user_email', $post->post_author);
		$ride_title = esc_html($post->post_title);
		$ride_url = get_permalink($post);
		$ride_link = '<a href="' . $ride_url . '">' . $ride_title . '</a>';
		$subject = 'Published Your Submitted Ride';
		$message = <<<EOT
Your submitted ride has been published and is now on the ride calendar:<br>
$ride_link.<br>
To view this ride as it appears on the calendar, click its link.<br>
Do not reply to this email!<br>
EOT;
		$headers = ['Content-type: text/html'];
		return wp_mail($author_email, $subject , $message, $headers);
	}

	public static function ride_rejected_email($post) {
		$author_email = get_the_author_meta('user_email', $post->post_author);		
		$ride_title = esc_html($post->post_title);
		$ride_url = "https://".$_SERVER['HTTP_HOST'].self::edit_ride_link($post->ID);
		$ride_link = '<a href="' . $ride_url . '">' . $ride_title . '</a>';
		$subject = 'Rejected Your Submitted Ride';
		$message = <<<EOT
Your submitted ride has been rejected and returned to you:<br>
$ride_link.<br>
To make changes to this ride and re-submit, use a browser to log in to your club account and open the ride by clicking its link.<br>
Do not reply to this email!<br>
EOT;
		$headers = ['Content-type: text/html'];
		return wp_mail($author_email, $subject , $message, $headers);
	}
	
	public static function ride_question_email($postid) {
		$post = get_post($postid);
		$current_user = wp_get_current_user();
		if ( $post->post_author != $current_user->ID ) {
			$author_email = get_the_author_meta('user_email', $post->post_author);		
			$ride_title = esc_html(get_the_title($postid));
			$ride_date = PwtcMapdb::get_ride_start_time($postid)->format('m/d/Y g:ia');
			$subject = 'Question About Your Submitted Ride';
			$body = 'I have questions about your submitted ride'.urlencode("\r\n").$ride_title.' on '.$ride_date.urlencode("\r\n").'(insert questions here...)'.urlencode("\r\n").urlencode("\r\n");
			return esc_url('mailto:'.$author_email.'?subject='.$subject.'&body='.$body); 
		}
		return '';
	}

}
