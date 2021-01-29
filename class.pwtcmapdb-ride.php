<?php

class PwtcMapdb_Ride {

    private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	private static function init_hooks() {
		self::$initiated = true;

        	add_shortcode('pwtc_mapdb_edit_ride2', 
        	array( 'PwtcMapdb_Ride', 'shortcode_edit_ride'));
    	}

	// Generates the [pwtc_mapdb_edit_ride] shortcode.
	public static function shortcode_edit_ride($atts) {
		$a = shortcode_atts(array('leaders' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';

		$current_user = wp_get_current_user();

		$return = false;
		if (isset($_GET['return'])) {
			if ($_GET['return'] == 'yes') {
				$return = true;
			}
		}

		if (isset($_POST['postid']) and isset($_POST['title']) and $current_user->ID != 0) {

			$success = '';
			$operation = '';
			$new_post = false;
			$postid = intval($_POST['postid']);

			$title = trim($_POST['title']);
			if ($postid != 0) {
				$my_post = array(
					'ID' => $postid,
					'post_title' => esc_html($title)
				);
				$operation = 'update';
				$status = wp_update_post( $my_post );	
				if ($status != $postid) {
					$success = 'no';
					wp_redirect(add_query_arg(array(
						'post' => $postid,
						'return' => $return ? 'yes':'no',
						'op' => $operation,
						'success' => $success
					), get_permalink()), 303);
					exit;
				}
				else {
					$success = 'yes';
				}
				update_post_meta($postid, '_edit_last', $current_user->ID);
			}
			else {
				$my_post = array(
					'post_title'    => esc_html($title),
					'post_type'     => PwtcMapdb::POST_TYPE_RIDE,
                    			'post_status'   => 'draft',
                    			'post_author'   => $current_user->ID
				);
				$operation = 'insert';
				$postid = wp_insert_post( $my_post );
				if ($postid == 0) {
					$success = 'no';
					if (isset($_GET['post'])) {
						wp_redirect(add_query_arg(array(
							'post' => $_GET['post'],
							'action' => 'copy',
							'return' => $return ? 'yes':'no',
							'op' => $operation,
							'success' => $success
						), get_permalink()), 303);
					}
					else {
						wp_redirect(add_query_arg(array(
							'return' => $return ? 'yes':'no',
							'op' => $operation,
							'success' => $success
						), get_permalink()), 303);
					}
					exit;
				}
				else {
					$success = 'yes';
				}
				//$new_post = true;
			}

			if (isset($_POST['description'])) {
				if ($new_post) {
					update_field(PwtcMapdb::RIDE_DESCRIPTION_KEY, $_POST['description'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_DESCRIPTION, $_POST['description'], $postid);
				}
			}	
			
			if (isset($_POST['leaders'])) {
				$new_leaders = json_decode($_POST['leaders']);
				if ($new_post) {
					update_field(PwtcMapdb::RIDE_LEADERS_KEY, $new_leaders, $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_LEADERS, $new_leaders, $postid);
				}
			}

			if (isset($_POST['ride_date']) and isset($_POST['ride_time'])) {
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
				if ($new_post) {
					update_field(PwtcMapdb::RIDE_START_LOCATION_KEY, $location, $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_START_LOCATION, $location, $postid);
				}
			}

			if (isset($_POST['start_location_comment'])) {
				if ($new_post) {
					update_field(PwtcMapdb::RIDE_START_LOC_COMMENT_KEY, $_POST['start_location_comment'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_START_LOC_COMMENT, $_POST['start_location_comment'], $postid);
				}
			}

			if (isset($_POST['ride_type'])) {
				if ($new_post) {
					update_field(PwtcMapdb::RIDE_TYPE_KEY, $_POST['ride_type'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_TYPE, $_POST['ride_type'], $postid);
				}
			}

			if (isset($_POST['ride_pace'])) {
				if ($new_post) {
					update_field(PwtcMapdb::RIDE_PACE_KEY, $_POST['ride_pace'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_PACE, $_POST['ride_pace'], $postid);
				}
			}

			if (isset($_POST['attach_maps'])) {
				if ($new_post) {
					update_field(PwtcMapdb::RIDE_ATTACH_MAP_KEY, $_POST['attach_maps'], $postid);
				}
				else {
					update_field(PwtcMapdb::RIDE_ATTACH_MAP, $_POST['attach_maps'], $postid);
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
					if ($new_post) {
						update_field(PwtcMapdb::RIDE_MAPS_KEY, $new_maps, $postid);
					}
					else {
						update_field(PwtcMapdb::RIDE_MAPS, $new_maps, $postid);
					}
				}	
			}
			else {
				if (isset($_POST['distance'])) {
					if ($new_post) {
						update_field(PwtcMapdb::RIDE_LENGTH_KEY, intval($_POST['distance']), $postid);
					}
					else {
						update_field(PwtcMapdb::RIDE_LENGTH, intval($_POST['distance']), $postid);
					}
				}
		
				if (isset($_POST['max_distance'])) {
					$d = trim($_POST['max_distance']);
					if (empty($d)) {
						if ($new_post) {
							update_field(PwtcMapdb::RIDE_MAX_LENGTH_KEY, null, $postid);
						}
						else {
							update_field(PwtcMapdb::RIDE_MAX_LENGTH, null, $postid);
						}
					}
					else {
						if ($new_post) {
							update_field(PwtcMapdb::RIDE_MAX_LENGTH_KEY, intval($d), $postid);
						}
						else {
							update_field(PwtcMapdb::RIDE_MAX_LENGTH, intval($d), $postid);
						}
					}
				}
		
				if (isset($_POST['ride_terrain'])) {
					if ($new_post) {
						update_field(PwtcMapdb::RIDE_TERRAIN_KEY, $_POST['ride_terrain'], $postid);
					}
					else {
						update_field(PwtcMapdb::RIDE_TERRAIN, $_POST['ride_terrain'], $postid);
					}
				}
			}

			wp_redirect(add_query_arg(array(
				'post' => $postid,
				'return' => $return ? 'yes':'no',
				'op' => $operation,
				'success' => $success
			), get_permalink()), 303);
			exit;
		}

		$copy_ride = false;
		if (isset($_GET['action'])) {
			if ($_GET['action'] == 'copy') {
				$copy_ride = true;
			}
		}

		if (isset($_GET['post'])) {
			$error = self::check_post_id();
			if (!empty($error)) {
				return PwtcMapdb::create_page_title($copy_ride, -1) . $error;
			}
			$postid = intval($_GET['post']);
		}
		else {
			$postid = 0;
		}

		$now_date = PwtcMapdb::get_current_time();

		$ride_title = '';
		$ride_link = '';
		$return_to_ride = 'You may now close this page.';
		if ($postid != 0) {
			$ride_title = esc_html(get_the_title($postid));
			if ($return) {
				$ride_link = esc_url(get_the_permalink($postid));
				$return_to_ride = PwtcMapdb::create_return_link($ride_link);
			}
		}

		$page_title = PwtcMapdb::create_page_title($copy_ride, $postid);

		if (isset($_GET['op']) and isset($_GET['success'])) {
			if ($_GET['success'] == 'no') {
				if ($_GET['op'] == 'insert') {
					return $page_title . '<div class="callout small alert"><p>Failed to create new scheduled ride. ' . $return_to_ride . '</p></div>';
				}
				else {
					return $page_title . '<div class="callout small alert"><p>Failed to update scheduled ride. ' . $return_to_ride . '</p></div>';
				}
			}
		}

		if ( 0 == $current_user->ID ) {
			return $page_title . '<div class="callout small warning"><p>You must be logged in to edit rides. ' . $return_to_ride . '</p></div>';
        	}
        
        	if ($postid != 0) {
			$post = get_post($postid);
            		$title = $post->post_title;
            		$author = $post->$post_author;
            		$status = $post->$post_status;
		}
		else {
            		$title = '';
            		$author = $current_user->ID;
            		$status = 'draft';
		}

		$user_info = get_userdata($current_user->ID);

		if ($copy_ride and !user_can($current_user,'edit_published_rides')) {
			if ($allow_leaders) {
				if (!in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles)) {
					return $page_title . '<div class="callout small warning"><p>You must be a ride leader to copy rides. ' . $return_to_ride . '</p></div>';
				}
			}
			else {
				return $page_title . '<div class="callout small warning"><p>You are not allowed to copy rides. ' . $return_to_ride . '</p></div>';
			}
		}

		if ($postid == 0 and !user_can($current_user,'edit_published_rides')) {
			if ($allow_leaders) {
				if (!in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles)) {
					return $page_title . '<div class="callout small warning"><p>You must be a ride leader to create new rides. ' . $return_to_ride . '</p></div>';
				}
			}
			else {
				return $page_title . '<div class="callout small warning"><p>You are not allowed to create new rides. ' . $return_to_ride . '</p></div>';
			}
		}

		if ($postid != 0 and !$copy_ride and !user_can($current_user,'edit_published_rides')) {
            		if ($status == 'publish') {
                		return $page_title . '<div class="callout small warning"><p>Ride "' . $ride_title . '" is published so you cannot edit it. ' . $return_to_ride . '</p></div>';
            		}
            		if ($author != $current_user->ID) {
                		return $page_title . '<div class="callout small warning"><p>You must be the author of ride "' . $ride_title . '" to edit it. ' . $return_to_ride . '</p></div>';
            		}
		}

		if ($postid != 0 and !$copy_ride) {
			$ride_datetime = PwtcMapdb::get_ride_start_time($postid);
			if ($ride_datetime < $now_date) {
				return $page_title . '<div class="callout small warning"><p>Ride "' . $ride_title . '" has already finished so you cannot edit it. ' . $return_to_ride . '</p></div>';
			}
		}

		$road_captain = user_can($current_user,'edit_published_rides');

		if ($postid != 0) {
			$description = get_field(PwtcMapdb::RIDE_DESCRIPTION, $postid, false);
		}
		else {
			$description = '';
		}

		if ($postid != 0) {
			$leaders = PwtcMapdb::get_leader_userids($postid);
		}
		else {
			$leaders = [];
		}

		if (user_can($current_user,'edit_published_rides')) {
			$interval = new DateInterval('P1D');
		}
		else {
			$interval = new DateInterval('P14D');
		}
		if ($postid != 0) {
			$min_datetime = PwtcMapdb::get_current_date();
			$min_datetime->add($interval);
			$min_date = $min_datetime->format('Y-m-d');
			$ride_datetime = PwtcMapdb::get_ride_start_time($postid);
			if ($copy_ride) {
				$ride_time = $ride_datetime->format('H:i');
				$ride_date = '';
				$edit_date = true;	
			}
			else {
				$ride_date = $ride_datetime->format('Y-m-d');
				$ride_time = $ride_datetime->format('H:i');	
				if (user_can($current_user,'edit_published_rides')) {
					$edit_date = true;
				}
				else {
					$edit_date = false;
                    			if ($ride_datetime > $min_datetime) {
                        			$edit_date = true;
                    			}
				}
			}
		}
		else {
			$ride_datetime = PwtcMapdb::get_current_time();
			$ride_datetime->add($interval);
			$ride_date = '';
			$ride_time = '';
			$min_date = $ride_datetime->format('Y-m-d');
			$edit_date = true;
		}

		$edit_title = $edit_start_location = $edit_date;

		if ($postid != 0) {
			$start_location = get_field(PwtcMapdb::RIDE_START_LOCATION, $postid);
		}
		else {
			$start_location = array('address' => '', 'lat' => 0.0, 'lng' => 0.0, 'zoom' => 16);
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

		$operation = '';
		$success = '';
		if (isset($_GET['op']) and isset($_GET['success'])) {
			$operation = $_GET['op'];
			$success = $_GET['success'];
		}

		if ($copy_ride) {
			$postid = 0;
		}

		if ($postid != 0) {
			$lock_user = PwtcMapdb::check_post_lock($postid);
		    	if ($lock_user) {
				$info = get_userdata($lock_user);
				$name = $info->first_name . ' ' . $info->last_name;	
				return $page_title . '<div class="callout small warning"><p>Ride "' . $ride_title . '" is currently being edited by ' . $name . '. ' . $return_to_ride . '</p></div>';
			}
			PwtcMapdb::set_post_lock($postid);
		}
		
        	ob_start();
        	include('ride-edit-form.php');
        	return ob_get_clean();
	}
	
	public static function check_post_id() {
		if (!isset($_GET['post'])) {
			return '<div class="callout small alert"><p>Ride post ID parameter is missing.</p></div>';
		}

		$postid = intval($_GET['post']);
		if ($postid == 0) {
			return '<div class="callout small alert"><p>Ride post ID parameter is invalid, it must be an integer number.</p></div>';
		}

		$post = get_post($postid);
		if (!$post) {
			return '<div class="callout small alert"><p>Ride post ' . $postid . ' does not exist, it may have been deleted.</p></div>';
		}

		if (get_post_type($post) != self::POST_TYPE_RIDE) {
			return '<div class="callout small alert"><p>Ride post ' . $postid . ' is not a scheduled ride.</p></div>';
		}

		$post_status = get_post_status($post);
		if ($post_status != 'publish' and $post_status != 'draft') {
			if ($post_status == 'trash') {
				return '<div class="callout small alert"><p>Ride post ' . $postid . ' has been deleted.</p></div>';
			}
			else {
				return '<div class="callout small alert"><p>Ride post ' . $postid . ' is not draft or published. Its current status is "' . $post_status . '"</p></div>';
			}
		}

		return '';
	}
}
