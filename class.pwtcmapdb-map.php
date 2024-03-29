<?php

class PwtcMapdb_Map {

	const EDIT_MAP_URI = '/edit-map';
	const SUBMIT_MAP_URI = '/submit-map';
	const DELETE_MAP_URI = '/delete-map';
	const USAGE_MAP_URI = '/map-usage';
	
    	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	private static function init_hooks() {
        self::$initiated = true;
		
	// Register action callbacks
	add_action('wp_enqueue_scripts', array('PwtcMapdb_Map', 'load_javascripts'));

	// Register filter callbacks
	add_filter('heartbeat_received', array('PwtcMapdb_Map', 'refresh_post_lock'), 10, 3);

        // Register shortcode callbacks
        add_shortcode('pwtc_search_mapdb', array('PwtcMapdb_Map', 'shortcode_search_mapdb'));
	add_shortcode('pwtc_mapdb_map_breadcrumb', array('PwtcMapdb_Map', 'shortcode_map_breadcrumb'));
	add_shortcode('pwtc_mapdb_edit_map', array('PwtcMapdb_Map', 'shortcode_edit_map'));
	add_shortcode('pwtc_mapdb_delete_map', array( 'PwtcMapdb_Map', 'shortcode_delete_map'));
	add_shortcode('pwtc_mapdb_usage_map', array( 'PwtcMapdb_Map', 'shortcode_usage_map'));
	add_shortcode('pwtc_mapdb_manage_maps', array('PwtcMapdb_Map', 'shortcode_manage_maps'));
	add_shortcode('pwtc_mapdb_manage_pending_maps', array('PwtcMapdb_Map', 'shortcode_manage_pending_maps'));
	add_shortcode('pwtc_mapdb_manage_published_maps', array('PwtcMapdb_Map', 'shortcode_manage_published_maps'));
	add_shortcode('pwtc_mapdb_new_map_link', array('PwtcMapdb_Map', 'shortcode_new_map_link'));

        // Register ajax callbacks
        add_action('wp_ajax_pwtc_mapdb_lookup_maps', array('PwtcMapdb_Map', 'lookup_maps_callback') );
	add_action('wp_ajax_pwtc_mapdb_fetch_maps', array('PwtcMapdb_Map', 'fetch_maps_callback') );

    }
	
	/******************* Action Functions ******************/

	public static function load_javascripts() {
		$link = get_the_permalink();
		if ($link and (strpos($link, self::DELETE_MAP_URI)!==false or strpos($link, self::EDIT_MAP_URI)!==false or strpos($link, self::SUBMIT_MAP_URI)!==false)) {
			wp_enqueue_script('heartbeat');
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

    // Generates the [pwtc_search_mapdb] shortcode.
	public static function shortcode_search_mapdb($atts) {
		$a = shortcode_atts(array('limit' => 0), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to view the map library.</p></div>';
		}
		else {
            ob_start();
            include('map-view-form.php');
            return ob_get_clean();
		}
    }
	
	// Generates the [pwtc_mapdb_map_breadcrumb] shortcode.
	public static function shortcode_map_breadcrumb($atts) {
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
		include('map-nav-breadcrumb.php');
		return ob_get_clean();
	}

	
	// Generates the [pwtc_mapdb_edit_map] shortcode.
	public static function shortcode_edit_map($atts) {
		$a = shortcode_atts(array('leaders' => 'no', 'use_return' => 'no', 'email' => 'no', 'captain' => PwtcMapdb::ROAD_CAPTAIN_EMAIL), $atts);
		$allow_leaders = $a['leaders'] == 'yes';
		$use_return = $a['use_return'] == 'yes';
		$allow_email = $a['email'] == 'yes';
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
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'map-edit-form')) {
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
					$email = self::map_unsubmitted_email($postid, $captain_email) ? 'yes': 'failed';
				}
			}

			wp_redirect(add_query_arg(array(
				'post' => $postid,
				'return' => urlencode($return),
				'op' => 'revert_draft',
				'email' => $email
			), get_permalink()), 303);
			exit;
		}
		else if (isset($_POST['postid']) and isset($_POST['title']) and $current_user->ID != 0) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'map-edit-form')) {
				wp_nonce_ays('');
			}
			
			if (!$is_road_captain and !$is_ride_leader) {
				wp_die('Authorization failed.', 403);
			}

			$operation = '';
			$new_post = true;
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
					'post_type'     => PwtcMapdb::MAP_POST_TYPE,
                    			'post_status'   => 'draft',
                    			'post_author'   => $current_user->ID
				);
				$operation = 'insert';
				$postid = wp_insert_post( $my_post );
				if ($postid == 0) {
					wp_die('Failed to create a new post.', 403);
				}
			}
			
			if (isset($_POST['distance'])) {
				if ($new_post) {
					update_field(PwtcMapdb::LENGTH_FIELD_KEY, intval($_POST['distance']), $postid);
				}
				else {
					update_field(PwtcMapdb::LENGTH_FIELD, intval($_POST['distance']), $postid);
				}
			}
	
			if (isset($_POST['max_distance'])) {
				$d = trim($_POST['max_distance']);
				if (empty($d)) {
					if ($new_post) {
						update_field(PwtcMapdb::MAX_LENGTH_FIELD_KEY, null, $postid);
					}
					else {
						update_field(PwtcMapdb::MAX_LENGTH_FIELD, null, $postid);
					}
				}
				else {
					if ($new_post) {
						update_field(PwtcMapdb::MAX_LENGTH_FIELD_KEY, intval($d), $postid);
					}
					else {
						update_field(PwtcMapdb::MAX_LENGTH_FIELD, intval($d), $postid);
					}
				}
			}
	
			if (isset($_POST['terrain'])) {
				if ($new_post) {
					update_field(PwtcMapdb::TERRAIN_FIELD_KEY, $_POST['terrain'], $postid);
				}
				else {
					update_field(PwtcMapdb::TERRAIN_FIELD, $_POST['terrain'], $postid);
				}
			}
			
			if (isset($_POST['map_type'])) {
				$map_type = $_POST['map_type'];
				
				$update = false;
				$row = array(
					PwtcMapdb::MAP_TYPE_FIELD_KEY => $map_type
				);

				if (isset($_POST['map_file_id'])) {
					$map_file_id = intval($_POST['map_file_id']);
					if (isset($_FILES['map_file_upload']) and $_FILES['map_file_upload']['size'] > 0) {
						if ($_FILES['map_file_upload']['error'] != UPLOAD_ERR_OK) {
							wp_die('Route map file upload failed.', 403);
						}
						$filetype = wp_check_filetype($_FILES['map_file_upload']['name']);
						$tmpname = $_FILES['map_file_upload']['tmp_name'];
						if ($map_file_id > 0) {
							$status = wp_delete_attachment($map_file_id, true);
							if ($status === false) {
								wp_die('Could not delete old route map file attachment.', 403);
							}	
						}
						$filename = sanitize_file_name($_FILES['map_file_upload']['name']);
						$upload_dir = wp_upload_dir();
						$movefile = $upload_dir['path'] . '/' . $filename;
						if (file_exists($movefile)) {
							$file_count = 0;
							$split = strrpos($filename, ".");
							if ($split === false) {
								$firstpart = $filename;
								$lastpart = '';
							}
							else {
								$firstpart = substr($filename, 0, $split);
								$lastpart = substr($filename, $split);
							}
							while (true) {
								$file_count++;
								$testfile = $firstpart . '-' . $file_count . $lastpart;
								$testpath = $upload_dir['path'] . '/' . $testfile;
								if (!file_exists($testpath)) {
									$filename = $testfile;
									$movefile = $testpath;
									break;
								}
								if ($file_count > 100) {
									wp_die('Too many iterations when renaming uploaded route map file.', 403);
								}
							}
						}
						$status = move_uploaded_file($tmpname, $movefile);
						if ($status === false) {
							wp_die('Could not move uploaded route map file.', 403);
						}	
						$attachment = array(
							'guid'           => $upload_dir['url'] . '/' . $filename, 
							'post_mime_type' => $filetype['type'],
							'post_title'     => $title,
							'post_content'   => '',
							'post_status'    => 'inherit'
						);
						$map_file_id = wp_insert_attachment($attachment, $movefile, $postid);
						if ($map_file_id == 0) {
							wp_die('Could not create new attachment for uploaded route map file.', 403);
						}
						add_post_meta($map_file_id, '_rml_folder', 3, true);
					}
					$update = true;
					if ($new_post) {
						if ($map_file_id > 0) {
							$row[PwtcMapdb::MAP_FILE_FIELD_KEY] = $map_file_id;
						}
					}
					else {
						if ($map_file_id > 0) {
							$row[PwtcMapdb::MAP_FILE_FIELD] = $map_file_id;
						}
					}
				}

				if (isset($_POST['map_link'])) {
					$update = true;
					$map_link = trim($_POST['map_link']);
					if ($new_post) {
						$row[PwtcMapdb::MAP_LINK_FIELD_KEY] = $map_link;			
					}
					else {
						$row[PwtcMapdb::MAP_LINK_FIELD] = $map_link;
					}
				}
				
				if ($update) {
					if ($new_post) {
						if (have_rows(PwtcMapdb::MAP_FIELD_KEY, $postid)) {
							update_row(PwtcMapdb::MAP_FIELD_KEY, 1, $row, $postid);
						}
						else {
							add_row(PwtcMapdb::MAP_FIELD_KEY, $row, $postid);
						}	
					}
					else {
						if (have_rows(PwtcMapdb::MAP_FIELD, $postid)) {
							update_row(PwtcMapdb::MAP_FIELD, 1, $row, $postid);
						}
						else {
							add_row(PwtcMapdb::MAP_FIELD, $row, $postid);
						}
					}
				}
			}

			$email = 'no';
			if ($allow_email) {
				if ($operation == 'submit_review' and !$is_road_captain) {
					$email = self::map_submitted_email($postid, $captain_email) ? 'yes': 'failed';
				}
			}
			
			wp_redirect(add_query_arg(array(
				'post' => $postid,
				'return' => urlencode($return),
				'op' => $operation,
				'email' => $email
			), get_permalink()), 303);
			exit;			
		}
		
		$email_status = 'no';
		if (isset($_GET['email'])) {
			$email_status = $_GET['email'];
		}

		$copy_map = false;
		if (isset($_GET['action'])) {
			if ($_GET['action'] == 'copy') {
				$copy_map = true;
			}
		}
		
		$map_link = '';
		$return_to_map = '';
		if (!empty($return) and $use_return) {
			$map_link = esc_url($return);
			$return_to_map = self::create_return_link($map_link);
		}

		if (isset($_GET['post'])) {
			$error = self::check_post_id();
			if (!empty($error)) {
				return $return_to_map . $error;
			}
			$postid = intval($_GET['post']);
		}
		else {
			$postid = 0;
		}
		
		if (0 == $current_user->ID) {
			return $return_to_map . '<div class="callout small alert"><p>You must be logged in to submit route maps.</p></div>';
		}

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

		$map_title = '';
		if ($postid != 0) {
			$map_title = esc_html(get_the_title($postid));
		}

		if (!$allow_leaders and !$is_road_captain) {
			return $return_to_map . '<div class="callout small warning"><p>You are not allowed to submit route maps.</p></div>';
		}

		if ($copy_map and !$is_road_captain) {
            		if (!$is_ride_leader) {
                		return $return_to_map . '<div class="callout small warning"><p>You must be a ride leader to copy route maps.</p></div>';
            		}
		}

		if ($postid == 0 and !$is_road_captain) {
            		if (!$is_ride_leader) {
                		return $return_to_map . '<div class="callout small warning"><p>You must be a ride leader to create new route maps.</p></div>';
            		}
		}

		if ($postid != 0 and !$copy_map) {
			$lock_user = self::check_post_lock($postid);
		    	if ($lock_user) {
				$info = get_userdata($lock_user);
				$name = $info->first_name . ' ' . $info->last_name;	
				return $return_to_map . '<div class="callout small warning"><p>Post "' . $map_title . '" is currently being edited by ' . $name . '. </p></div>';
			}
		}

		if ($postid != 0 and !$copy_map and !$is_road_captain) {
			if (!$is_ride_leader) {
                return $return_to_map . '<div class="callout small warning"><p>You must be a ride leader to edit route maps.</p></div>';
			}
			
			if ($author != $current_user->ID) {
                return $return_to_map . '<div class="callout small warning"><p>You must be the author of route map "' . $map_title . '" to edit it.</p></div>';
			}
			
            if ($status == 'publish') {
                return $return_to_map . '<div class="callout small warning"><p>Route map "' . $map_title . '" is published so you cannot edit it.</p></div>';
			}
			else if ($status == 'pending') {
				if (!empty($return) and $use_return) {
					$create_map_link = self::new_map_link($return);
					$create_ride_link = PwtcMapdb_Ride::new_ride_link($return);
				}
				else {
					$create_map_link = self::new_map_link();
					$create_ride_link = PwtcMapdb_Ride::new_ride_link();
				}
				ob_start();
				include('map-pending-form.php');
				return ob_get_clean();
            		}
		}
		
		if ($postid != 0) {
			$distance = get_field(PwtcMapdb::LENGTH_FIELD, $postid);
			$max_distance = get_field(PwtcMapdb::MAX_LENGTH_FIELD, $postid);
			$terrain = get_field(PwtcMapdb::TERRAIN_FIELD, $postid);
			$map_type = 'file';
			$map_link = '';
			$map_file_id = 0;
			$map_file_url = '';
			$map_file_name = '';
			while (have_rows(PwtcMapdb::MAP_FIELD, $postid) ): the_row();
				$map_type = get_sub_field(PwtcMapdb::MAP_TYPE_FIELD);
				$map_file = get_sub_field(PwtcMapdb::MAP_FILE_FIELD);
				if (!empty($map_file)) {
					$map_file_id = $map_file['id'];
					$map_file_url = $map_file['url'];
					$map_file_name = $map_file['filename'];
				}
				$map_link = get_sub_field(PwtcMapdb::MAP_LINK_FIELD);
			endwhile;
		}
		else {
			$distance = 0;
			$max_distance = '';
			$terrain = [];
			$map_type = 'file';
			$map_link = '';
			$map_file_id = 0;
			$map_file_url = '';
			$map_file_name = '';
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

		ob_start();
        	include('map-edit-form.php');
        	return ob_get_clean();
	}
	
	// Generates the [pwtc_mapdb_delete_map] shortcode.
	public static function shortcode_delete_map($atts) {
		$a = shortcode_atts(array('leaders' => 'no', 'use_return' => 'no', 'check_usage' => 'yes'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';
		$use_return = $a['use_return'] == 'yes';
		$check_usage = $a['check_usage'] == 'yes';

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
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'map-delete-form')) {
				wp_nonce_ays('');
			}
			
			if (!$is_road_captain and !$is_ride_leader) {
				wp_die('Authorization failed.', 403);
			}

			$postid = intval($_POST['postid']);
			if (isset($_POST['trash_map'])) {
				if (wp_trash_post($postid)) {
					wp_redirect(add_query_arg(array(
						'post' => $postid,
						'return' => urlencode($return)
					), get_permalink()), 303);
				}
				else {
					wp_die('Failed to trash this post.', 403);
				}
			}
			else if (isset($_POST['delete_map'])) {
				if (isset($_POST['map_file_id'])) {
					$map_file_id = intval($_POST['map_file_id']);
					if ($map_file_id > 0) {
						$status = wp_delete_attachment($map_file_id, true);
						if ($status === false) {
							wp_die('Failed to delete the file attached to this route map', 403);
						}	
					}
				}
				if (wp_delete_post($postid, true)) {
					if (!empty($return)) {
						wp_redirect($return, 303);
					}
					else {
						wp_redirect(add_query_arg(array(
							'post' => $postid
						), get_permalink()), 303);
					}
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

		$map_link = '';
		$return_to_map = '';
		if (!empty($return) and $use_return) {
			$map_link = esc_url($return);
			$return_to_map = self::create_return_link($map_link);
		}
		
		$error = self::check_post_id(true);
		if (!empty($error)) {
			return $return_to_map . $error;
		}
		$postid = intval($_GET['post']);
		
		if (0 == $current_user->ID) {
			return $return_to_map . '<div class="callout small alert"><p>You must be logged in to delete route maps.</p></div>';
		}

		$map_title = esc_html(get_the_title($postid));

		$post = get_post($postid);
		$author = $post->post_author;
		$status = $post->post_status;

		if (!$allow_leaders and !$is_road_captain) {
			return $return_to_map . '<div class="callout small warning"><p>You are not allowed to delete route maps.</p></div>';
		}

		if ($status == 'publish' and !$is_road_captain) {
			return $return_to_map . '<div class="callout small warning"><p>Route map "' . $map_title . '" is published so you cannot delete it.</p></div>';
		}
		else if ($status == 'pending') {
			return $return_to_map . '<div class="callout small warning"><p>Route map "' . $map_title . '" is pending review so you cannot delete it.</p></div>';
		}

		if ($author != $current_user->ID and !$is_road_captain) {
			return $return_to_map . '<div class="callout small warning"><p>You must be the author of route map "' . $map_title . '" to delete it.</p></div>';
		}

		$deleted = false;
		if ($status == 'trash') {
			$deleted = true;
		}
		else {
			$used_in_ride = false;
			$ride_query = self::ride_usage_query($postid, PwtcMapdb::POST_TYPE_RIDE);
			$template_query = self::ride_usage_query($postid, PwtcMapdb::POST_TYPE_TEMPLATE);
			if ($template_query->have_posts() or $ride_query->have_posts()) { 
				$used_in_ride = true;
			}
		
			if ($check_usage and $used_in_ride) {
				return $return_to_map . '<div class="callout small warning"><p>Cannot delete map "' . $map_title . '" because it is used by ride templates or scheduled rides.</p></div>';
			}
			
			$lock_user = self::check_post_lock($postid);
			if ($lock_user) {
				$info = get_userdata($lock_user);
				$name = $info->first_name . ' ' . $info->last_name;	
				return $return_to_map . '<div class="callout small warning"><p>Post "' . $map_title . '" is currently being edited by ' . $name . '.</p></div>';
			}
			self::set_post_lock($postid);
			
			$attached_file = false;
			$delete_file = false;
			while (have_rows(PwtcMapdb::MAP_FIELD, $postid) ): the_row();
				$map_file = get_sub_field(PwtcMapdb::MAP_FILE_FIELD);
				if (!empty($map_file)) {
					$map_file_id = $map_file['id'];
					if ($map_file_id > 0) {
						$attached_file = true;
						$delete_file = true;
						$attachment_parent = wp_get_post_parent_id($map_file_id);
						if ($attachment_parent and $attachment_parent != $postid) {
							$delete_file = false;
						}
						else {
							$query = new WP_Query([
								'post_type'    => PwtcMapdb::MAP_POST_TYPE,
								'post__not_in' => [$postid],
								'post_status'  => ['pending', 'draft', 'publish'],
								'meta_query'   => [[
									'key'   => 'maps_0_file',
									'value' => $map_file_id,
									'type'  => 'numeric',
								]],
							]);
							if ($query->have_posts()) { 
								$delete_file = false;
							}
						}
					}
				}
			endwhile;
		}
		
		$return_uri = $_SERVER['REQUEST_URI'];
		$usage_link = self::usage_map_link($postid, $return_uri);

		ob_start();
		include('map-delete-form.php');
		return ob_get_clean();
	}
	
	// Generates the [pwtc_mapdb_usage_map] shortcode.
	public static function shortcode_usage_map($atts) {
		$a = shortcode_atts(array('leaders' => 'no', 'use_return' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';
		$use_return = $a['use_return'] == 'yes';

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

		$map_link = '';
		$return_to_map = '';
		if (!empty($return) and $use_return) {
			$map_link = esc_url($return);
			$return_to_map = self::create_return_link($map_link);
		}

		$error = self::check_post_id();
		if (!empty($error)) {
			return $return_to_map . $error;
		}
		$postid = intval($_GET['post']);

		if (0 == $current_user->ID) {
			return $return_to_map . '<div class="callout small alert"><p>You must be logged in to view route map usage.</p></div>';
		}

		$map_title = esc_html(get_the_title($postid));

		if (!$allow_leaders and !$is_road_captain) {
			return $return_to_map . '<div class="callout small warning"><p>You are not allowed to view route map usage.</p></div>';
		}

		$ride_query = self::ride_usage_query($postid, PwtcMapdb::POST_TYPE_RIDE);
		$template_query = self::ride_usage_query($postid, PwtcMapdb::POST_TYPE_TEMPLATE);

		ob_start();
		include('map-usage-form.php');
		return ob_get_clean();
	}
	
	// Generates the [pwtc_mapdb_manage_maps] shortcode.
	public static function shortcode_manage_maps($atts) {
		$a = shortcode_atts(array('leaders' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';

		$current_user = wp_get_current_user();

		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to view the route maps that you have created.</p></div>';
		}

		$user_info = get_userdata($current_user->ID);
		
		$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);

		$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);

		if (!$is_road_captain) {
			if (!$allow_leaders) {
				return '<div class="callout small warning"><p>You are not allowed to view the route maps that you have created.</p></div>';
			}
			if (!$is_ride_leader) {
				return '<div class="callout small warning"><p>You must be a ride leader to view the route maps that you have created.</p></div>';
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
			'post_type' => PwtcMapdb::MAP_POST_TYPE,
			'orderby' => 'date',
			'order' => 'DESC',
		];
		$query = new WP_Query($query_args);

		$return_uri = $_SERVER['REQUEST_URI'];

		ob_start();
		include('manage-maps-form.php');
		return ob_get_clean();
	}	
	
	// Generates the [pwtc_mapdb_manage_pending_maps] shortcode.
	public static function shortcode_manage_pending_maps($atts) {
		$a = shortcode_atts(array('leaders' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';

		$current_user = wp_get_current_user();

		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to review the pending route maps.</p></div>';
		}

		$user_info = get_userdata($current_user->ID);
		
		$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);

		if (!$is_road_captain) {
			return '<div class="callout small warning"><p>You must be a road captain to review the pending route maps.</p></div>';
		}

		$query_args = [
			'posts_per_page' => -1,
			'post_status' => 'pending',
			'post_type' => PwtcMapdb::MAP_POST_TYPE,
			'orderby' => 'date',
			'order' => 'DESC',
		];
		$query = new WP_Query($query_args);

		$return_uri = $_SERVER['REQUEST_URI'];

		ob_start();
		include('manage-pending-maps-form.php');
		return ob_get_clean();
	}	
	
	// Generates the [pwtc_mapdb_manage_published_maps] shortcode.
	public static function shortcode_manage_published_maps($atts) {
		$a = shortcode_atts(array('leaders' => 'no', 'limit' => '10', 'status' => 'all', 'search' => 'close', 'sort' => 'title', 'viewonly' => 'no'), $atts);
		$allow_leaders = $a['leaders'] == 'yes';
		$limit = intval($a['limit']);
		$search_open = $a['search'] == 'open';
		$viewonly = $a['viewonly'] == 'yes';

		$current_user = wp_get_current_user();

		if (isset($_POST['map_title']) and isset($_POST['map_type']) and isset($_POST['map_distance']) and isset($_POST['map_terrain']) and isset($_POST['offset'])) {
			if (0 == $current_user->ID) {
				wp_die('Authorization failed.', 403);
			}
			
			$map_status = 'publish';
			if (isset($_POST['map_status'])) {
				$map_status = $_POST['map_status'];
			}
			$sort_by = 'title';
			if (isset($_POST['sort_by'])) {
				$sort_by = $_POST['sort_by'];
			}
			wp_redirect(add_query_arg(array(
				'title' => urlencode(stripslashes(trim($_POST['map_title']))),
				'type' => $_POST['map_type'],
				'distance' => $_POST['map_distance'],
				'terrain' => $_POST['map_terrain'],
				'status' => $map_status,
				'sort' => $sort_by,
				'offset' => intval($_POST['offset'])
			), get_permalink()), 303);
			exit;
		}
		
		if (0 == $current_user->ID) {
			return '<div class="callout small warning"><p>You must be logged in to view the published route maps.</p></div>';
		}

		$user_info = get_userdata($current_user->ID);
		
		if ($viewonly) {
			$is_road_captain = false;
			$is_ride_leader = false;
		}
		else {
			$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);
			$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);
		}

		if (isset($_GET['title'])) {
			$map_title = $_GET['title'];
		}
		else {
			$map_title = '';
		}
		
		if (isset($_GET['status']) and $is_road_captain) {
			$map_status = $_GET['status'];
			if ($map_status == 'all' or $map_status == 'mine') {
				$post_status = ['publish', 'pending', 'draft'];
			}
			else {
				$post_status = $map_status;
			}
		}
		else if ($is_road_captain) {
			$map_status = $a['status'];
			if ($map_status == 'all' or $map_status == 'mine') {
				$post_status = ['publish', 'pending', 'draft'];
			}
			else {
				$post_status = $map_status;
			}
		}
		else {
			$map_status = 'publish';
			$post_status = $map_status;
		}
		
		if (isset($_GET['distance'])) {
			$map_distance = $_GET['distance'];
		}
		else {
			$map_distance = '0';
		}

		if (isset($_GET['terrain'])) {
			$map_terrain = $_GET['terrain'];
		}
		else {
			$map_terrain = '0';
		}
		
		if (isset($_GET['type'])) {
			$map_type = $_GET['type'];
		}
		else {
			$map_type = '0';
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
			$sort_by = 'title';
		}

		$query_args = [
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'post_status' => $post_status,
			'post_type' => PwtcMapdb::MAP_POST_TYPE,
		];
		
		if ($sort_by == 'date') {
			$query_args['orderby'] = 'date';
			$query_args['order'] = 'DESC';
		}
		else if ($sort_by == 'title') {
			$query_args['orderby'] = 'title';
			$query_args['order'] = 'ASC';
		}
		
		if ($map_status == 'mine') {
			$query_args['author'] = $current_user->ID;
		}

		if (!empty($map_title)) {
			$query_args['s'] = $map_title;	
		}
		
		if ($map_distance != '0') {
			$min_dist = -1;
			$max_dist = -1;
			switch ($map_distance) {
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
			if ($min_dist >= 0 or $max_dist >= 0) {
				if ($min_dist < 0) {
					$query_args['meta_query'][] = [
						'key' => PwtcMapdb::LENGTH_FIELD,
						'value' => [0, $max_dist],
						'type' => 'NUMERIC',
						'compare' => 'BETWEEN',
					];	
				}
				else if ($max_dist < 0) {
					$query_args['meta_query'][] = [
						'key' => PwtcMapdb::LENGTH_FIELD,
						'value' => $min_dist,
						'type' => 'NUMERIC',
						'compare' => '>',
					];
				}
				else {
					$query_args['meta_query'][] = [
						'key' => PwtcMapdb::LENGTH_FIELD,
						'value' => [$min_dist, $max_dist],
						'type' => 'NUMERIC',
						'compare' => 'BETWEEN',
					];	
				}
			}
		}

		if ($map_terrain != '0') {
			$query_args['meta_query'][] = [
                		'key' => PwtcMapdb::TERRAIN_FIELD,
                		'value' => '"' . $map_terrain . '"',
                		'compare' => 'LIKE',
            		];
		}
		
		if ($map_type != '0') {
			$query_args['meta_query'][] = [
				'key' => PwtcMapdb::MAP_TYPE_QUERY,
				'value' => $map_type,
				'compare' => 'LIKE',
			];
		}
		
		if ($limit > 0)	{
			$query_args['offset'] = $offset;
		}

		$query = new WP_Query($query_args);

		$return_uri = $_SERVER['REQUEST_URI'];

		ob_start();
		include('manage-published-maps-form.php');
		return ob_get_clean();
	}	

	// Generates the [pwtc_mapdb_new_map_link] shortcode.
	public static function shortcode_new_map_link($atts, $content) {
		$a = shortcode_atts(array('class' => ''), $atts);
		$return_uri = $_SERVER['REQUEST_URI'];
		if (empty($content)) {
			$content = 'new route map';
		}
		$new_link = self::new_map_link($return_uri);
		return '<a class="' . $a['class'] . '" href="' . $new_link . '">' . $content . '</a>';
	}
    
    /******* AJAX request/response callback functions *******/

	public static function fetch_maps_callback() {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			$response = array(
				'error' => 'Map fetch failed - user access denied.'
			);
			echo wp_json_encode($response);		
		}
		else if (!isset($_POST['title']) or !isset($_POST['limit'])) {
			$response = array(
				'error' => 'Map fetch failed - AJAX arguments missing.'
			);
			echo wp_json_encode($response);
		}
		else {
			$title = sanitize_text_field($_POST['title']);
			$limit = intval($_POST['limit']);
			if (isset($_POST['offset'])) {
				$offset = intval($_POST['offset']);
			}
			else {
				$offset = 0;
			}
			$select = 0;
			if (isset($_POST['select'])) {
				$select = intval($_POST['select']);
			}
			
			if ($offset == 0 and isset($_POST['author'])) {
				$maps = self::fetch_pending_maps(intval($_POST['author']));
			}
			else {
				$maps = [];
			}
			$num_pending = count($maps);

			$query_args = [
				'posts_per_page' => $limit > 0 ? $limit : -1,
				'post_type' => PwtcMapdb::MAP_POST_TYPE,
				'post_status' => 'publish',
				'orderby'   => 'title',
				'order'     => 'ASC',
			];
			if (!empty($title)) {
				$query_args['s'] = $title;	
			}	
			if ($limit > 0)	{
				$query_args['offset'] = $offset;
			}

			$query = new WP_Query($query_args);	

			$is_more = false;
			if ($query->have_posts()) {
				$total = $query->found_posts;
				$is_more = ($limit > 0) && ($total > ($offset + $limit));
				while ($query->have_posts()) {
					$query->the_post();
					$map = self::build_map_array_from_post();
					$maps[] = $map;
				}
				wp_reset_postdata();
			}

			$response = array(
				'limit' => $limit,
				'offset' => $offset,
				'pending' => $num_pending,
				'maps' => $maps
			);
			if ($is_more) {
				$response['more'] = 1;
			}
			if ($select > 0 and $offset == 0 and $num_pending == 0 and count($maps) == 1) {
				$response['select'] = 1;
			}
			if (isset($_POST['count'])) {
				$response['count'] = intval($_POST['count']);
			}
			echo wp_json_encode($response);
		}
		wp_die();
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
			if (current_user_can(PwtcMapdb::EDIT_CAPABILITY)) {
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

    /******************* Utility Functions ******************/
	
    	public static function ride_usage_query($map_postid, $type) {
		$query_args = [
			'post_type'    => $type,
			'post_status'  => ['pending', 'draft', 'publish'],
			'meta_query'   => [
				'relation' => 'AND',
				[
					'key'     => PwtcMapdb::RIDE_MAPS,
					'value'   => '"'.$map_postid.'"',
					'compare' => 'LIKE',
				],
				[
					'key'   => PwtcMapdb::RIDE_ATTACH_MAP,
					'value' => 1,
					'type'  => 'numeric',
				],
			],
		];
		if ($type == PwtcMapdb::POST_TYPE_RIDE) {
			$query_args['meta_key'] = PwtcMapdb::RIDE_DATE;
			$query_args['meta_type'] = 'DATETIME';
			$query_args['orderby'] = ['meta_value' => 'DESC'];
		}
		else {
			$query_args['orderby'] = 'date';
			$query_args['order'] = 'DESC';			
		}
		$query = new WP_Query($query_args);
		return $query;
	}

    	public static function get_query_args($title, $location, $terrain, $min_dist, $max_dist, $media) {
		$args = array(
			'post_type' => PwtcMapdb::MAP_POST_TYPE,
			'post_status' => 'publish',
			'orderby'   => 'title',
			'order'     => 'ASC'
		);
		if (!empty($title)) {
			$args['s'] = $title;	
		}
		if (!empty($location)) {
            $args['meta_query'][] = [
                'key' => PwtcMapdb::START_LOCATION_FIELD,
                'value' => $location,
                'compare' => 'LIKE',
            ];
		}
		if (!empty($terrain)) {
            $args['meta_query'][] = [
                'key' => PwtcMapdb::TERRAIN_FIELD,
                'value' => '"' . $terrain . '"',
                'compare' => 'LIKE',
            ];
		}
		if ($min_dist >= 0 or $max_dist >= 0) {
			if ($min_dist < 0) {
				$args['meta_query'][] = [
					'key' => PwtcMapdb::LENGTH_FIELD,
					'value' => [0, $max_dist],
					'type' => 'NUMERIC',
					'compare' => 'BETWEEN',
				];	
			}
			else if ($max_dist < 0) {
				$args['meta_query'][] = [
					'key' => PwtcMapdb::LENGTH_FIELD,
					'value' => $min_dist,
					'type' => 'NUMERIC',
					'compare' => '>',
				];
			}
			else {
				$args['meta_query'][] = [
					'key' => PwtcMapdb::LENGTH_FIELD,
					'value' => [$min_dist, $max_dist],
					'type' => 'NUMERIC',
					'compare' => 'BETWEEN',
				];	
			}
		}
		if (!empty($media)) {
			$args['meta_query'][] = [
				'key' => PwtcMapdb::MAP_TYPE_QUERY,
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
		$query = new WP_Query($args);
		$count = $query->found_posts;
		return $count;
	}

	public static function fetch_maps($title, $location, $terrain, $min_dist, $max_dist, $media, $offset = -1 , $rowcount = -1) {
		$args = self::get_query_args($title, $location, $terrain, $min_dist, $max_dist, $media);
		$args['posts_per_page'] = $rowcount;
		if ($offset >= 0) {
			$args['offset'] = $offset;
		}
		$query = new WP_Query($args);	
		$results = [];	
		while ($query->have_posts()) {
			$query->the_post();

			$terrain = get_field(PwtcMapdb::TERRAIN_FIELD);
			$terrain_str = '';
			foreach ($terrain as $item) {
				$terrain_str .= strtoupper($item);
			}

			$length = get_field(PwtcMapdb::LENGTH_FIELD);
			$max_length = get_field(PwtcMapdb::MAX_LENGTH_FIELD);
			$distance_str = '';
			if ($max_length == '') {
				$distance_str = $length . ' miles';
			}
			else {
				$distance_str = $length . '-' . $max_length . ' miles';
			}

			$url = '';
			$href = '';
			$url2 = '';
			$href2 = '';
			$href_type = '';
			while (have_rows(PwtcMapdb::MAP_FIELD) ): the_row();
				$href_type = get_sub_field(PwtcMapdb::MAP_TYPE_FIELD);
				if ($href_type == 'file') {
					$file = get_sub_field(PwtcMapdb::MAP_FILE_FIELD);
					$href = esc_url($file['url']);
					$url = '<a title="Download ride route map file." target="_blank" href="' . $href . '" download>';
				}
				else if ($href_type == 'link') {
					$link = get_sub_field(PwtcMapdb::MAP_LINK_FIELD);
					$href = esc_url($link);
					$url = '<a title="Display online ride route map." target="_blank" href="' . $href . '">';
				}
				else if ($href_type == 'both') {
					$file = get_sub_field(PwtcMapdb::MAP_FILE_FIELD);
					$href = esc_url($file['url']);
					$url = '<a title="Download ride route map file." target="_blank" href="' . $href . '" download>';
					$link = get_sub_field(PwtcMapdb::MAP_LINK_FIELD);
					$href2 = esc_url($link);
					$url2 = '<a title="Display online ride route map." target="_blank" href="' . $href2 . '">';
				}
			endwhile;

			$edit_url = '';
			if (current_user_can(PwtcMapdb::EDIT_CAPABILITY)) {
				$edit_href = admin_url('post.php?post=' . get_the_ID() . '&action=edit');
				$edit_url = '<a title="Edit map post." target="_blank" href="' . $edit_href . '">Edit</a>';
			}

			$map = [
				'ID' => get_the_ID(),
				'title' => esc_html(get_the_title()),
				'terrain' => $terrain_str,
				'distance' => $distance_str,
				'media' => $url,
				'media2' => $url2,
				'type' => $href_type,
				'href' => $href,
				'href2' => $href2,
				'edit' => $edit_url
			];
			$results[] = $map;
		}
		wp_reset_postdata();
		return $results;
	}
	
	public static function fetch_pending_maps($author) {
		$query_args = [
			'posts_per_page' => -1,
			'post_status' => 'pending',
			'post_type' => PwtcMapdb::MAP_POST_TYPE,
			'author' => $author,
			'orderby' => 'date',
			'order' => 'DESC',
		];
		$query = new WP_Query($query_args);
		$maps = [];
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$map = self::build_map_array_from_post();
				$maps[] = $map;
			}
			wp_reset_postdata();
		}
		return $maps;
	}

	public static function build_map_array_from_post() {
		$status = get_post_status();
		$title = esc_html(get_the_title());
		if ($status == 'pending') {
			$title .= ' (' . $status . ')';
			$is_pending = true;
		}
		else {
			$is_pending = false;
		}

		$terrain = get_field(PwtcMapdb::TERRAIN_FIELD);
		$terrain_str = '';
		foreach ($terrain as $item) {
			$terrain_str .= strtoupper($item);
		}

		$length = get_field(PwtcMapdb::LENGTH_FIELD);
		$max_length = get_field(PwtcMapdb::MAX_LENGTH_FIELD);
		$distance_str = '';
		if ($max_length == '') {
			$distance_str = $length . ' miles';
		}
		else {
			$distance_str = $length . '-' . $max_length . ' miles';
		}

		$href = '';
		$href2 = '';
		$type = '';
		while (have_rows(PwtcMapdb::MAP_FIELD) ): the_row();
			$type = get_sub_field(PwtcMapdb::MAP_TYPE_FIELD);
			if ($type == 'file') {
				$file = get_sub_field(PwtcMapdb::MAP_FILE_FIELD);
				$href = esc_url($file['url']);
			}
			else if ($type == 'link') {
				$link = get_sub_field(PwtcMapdb::MAP_LINK_FIELD);
				$href = esc_url($link);
			}
			else if ($type == 'both') {
				$file = get_sub_field(PwtcMapdb::MAP_FILE_FIELD);
				$href = esc_url($file['url']);
				$link = get_sub_field(PwtcMapdb::MAP_LINK_FIELD);
				$href2 = esc_url($link);
			}
		endwhile;

		$map = [
			'ID' => get_the_ID(),
			'title' => $title,
			'terrain' => $terrain_str,
			'distance' => $distance_str,
			'type' => $type,
			'href' => $href,
			'href2' => $href2
		];
		if ($is_pending) {
			$map['pending'] = 1;
		}
		
		return $map;
	}
	
	public static function check_post_id($ignore_trash = false) {
		if (!isset($_GET['post'])) {
			return '<div class="callout small alert"><p>Route map post ID parameter is missing.</p></div>';
		}

		$postid = intval($_GET['post']);
		if ($postid == 0) {
			return '<div class="callout small alert"><p>Route map post ID parameter is invalid, it must be an integer number.</p></div>';
		}

		$post = get_post($postid);
		if (!$post) {
			return '<div class="callout small alert"><p>Route map post ' . $postid . ' does not exist, it may have been deleted.</p></div>';
		}


		if (get_post_type($post) != PwtcMapdb::MAP_POST_TYPE) {
			return '<div class="callout small alert"><p>Route map post ' . $postid . ' is not a route map.</p></div>';
		}

		$post_status = get_post_status($post);

		if ($post_status != 'publish' and $post_status != 'draft' and $post_status != 'pending') {
			if ($post_status == 'trash') {
				if (!$ignore_trash) {
					return '<div class="callout small alert"><p>Route map post ' . $postid . ' has been deleted.</p></div>';
				}
			}
			else {
				return '<div class="callout small alert"><p>Route map post ' . $postid . ' is not draft, pending or published. Its current status is "' . $post_status . '"</p></div>';
			}
		}
	
		return '';
	}

	public static function create_return_link($map_url) {
		return '<ul class="breadcrumbs"><li><a href="' . $map_url . '">Back to Previous Page</a></li></ul>';
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
	
	public static function map_submitted_email($postid, $captain_email) {
		$map_title = esc_html(get_the_title($postid));
		$map_url = get_permalink($postid);
		$map_link = '<a href="' . $map_url . '">' . $map_title . '</a>';
		$subject = 'Route Map Submitted for Review';
		$message = <<<EOT
The following route map has been submitted for road captain review:<br>
$map_link.<br>
To review this route map, use a browser to log in to your club account (you must be a road captain) and open the route map by clicking its link. Make any changes that you see fit and publish the route map or reject (return it to draft.)<br>
Do not reply to this email!<br>
EOT;
		$headers = ['Content-type: text/html'];
		return wp_mail($captain_email, $subject , $message, $headers);
	}
	
	public static function map_unsubmitted_email($postid, $captain_email) {
		$map_title = esc_html(get_the_title($postid));
		$subject = 'Route Map Unsubmitted';
		$message = <<<EOT
The author has reverted the following route map back to draft:<br>
$map_title.<br>
Ignore the previous review request email and do not review this route map.<br>
Do not reply to this email!<br>
EOT;
		$headers = ['Content-type: text/html'];
		return wp_mail($captain_email, $subject , $message, $headers);
	}


	public static function delete_map_link($postid, $return=false) {
		$uri = self::DELETE_MAP_URI;
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}

	public static function new_map_link($return=false) {
		$uri = self::SUBMIT_MAP_URI;
		if ($return) {
			$uri .= '?return=' . urlencode($return);
		}
		return esc_url($uri);
	}

	public static function edit_map_link($postid, $return=false) {
		$uri = self::EDIT_MAP_URI;
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}
	
	public static function usage_map_link($postid, $return=false) {
		$uri = self::USAGE_MAP_URI;
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}
	
}
