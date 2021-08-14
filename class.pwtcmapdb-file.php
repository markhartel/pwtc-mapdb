<?php

class PwtcMapdb_File {
	
	const UPLOAD_FILE_URI = '/upload-file';
	const DELETE_FILE_URI = '/delete-file';

    	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

    	private static function init_hooks() {
        	self::$initiated = true;

        	// Register shortcode callbacks
        	add_shortcode('pwtc_mapdb_upload_file', array('PwtcMapdb_File', 'shortcode_upload_file'));
		add_shortcode('pwtc_mapdb_delete_file', array( 'PwtcMapdb_File', 'shortcode_delete_file'));
		add_shortcode('pwtc_mapdb_manage_files', array('PwtcMapdb_File', 'shortcode_manage_files'));
       		add_shortcode('pwtc_mapdb_new_file_link', array('PwtcMapdb_File', 'shortcode_new_file_link'));
    	}

    	/******************* Shortcode Functions ******************/

    	// Generates the [pwtc_mapdb_upload_file] shortcode.
	public static function shortcode_upload_file($atts, $content) {
		$a = shortcode_atts(array('use_return' => 'no'), $atts);
        	$use_return = $a['use_return'] == 'yes';

        	$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small alert"><p>You must be logged in to upload files.</p></div>';
		}
		$user_info = get_userdata($current_user->ID);
		$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);
		$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);

        	$return = '';
		if (isset($_GET['return'])) {
			$return = $_GET['return'];
		}

        	if (isset($_POST['attach_id']) and isset($_POST['title'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'file-upload-form')) {
				wp_nonce_ays('');
			}

			$attach_id = intval($_POST['attach_id']); 
            		$title = trim($_POST['title']);

            		if (isset($_FILES['file_upload']) and $_FILES['file_upload']['size'] > 0) {
                		if ($_FILES['file_upload']['error'] != UPLOAD_ERR_OK) {
                    			wp_die('File upload failed.', 403);
                		}
                		$filetype = wp_check_filetype($_FILES['file_upload']['name']);
                		$tmpname = $_FILES['file_upload']['tmp_name'];
                		if ($attach_id > 0) {
                    			$status = wp_delete_attachment($attach_id, true);
                    			if ($status === false) {
                        			wp_die('Could not delete previous file attachment.', 403);
                    			}	
                		}
                		$filename = sanitize_file_name($_FILES['file_upload']['name']);
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
                            				wp_die('Too many iterations when renaming uploaded file.', 403);
                        			}
                    			}
                		}
                		$status = move_uploaded_file($tmpname, $movefile);
                		if ($status === false) {
                    			wp_die('Could not move uploaded file.', 403);
                		}
				if ($attach_id != 0) {
					$operation = 'update_upload';
				}
				else {
					$operation = 'insert';
				}
               			$attachment = array(
                    			'guid'           => $upload_dir['url'] . '/' . $filename, 
                    			'post_mime_type' => $filetype['type'],
                    			'post_title'     => esc_html($title),
                    			'post_content'   => '',
                    			'post_status'    => 'inherit'
                		);
                		$attach_id = wp_insert_attachment($attachment, $movefile);
                		if ($attach_id == 0) {
                    			wp_die('Could not create new attachment for uploaded file.', 403);
                		}
                		add_post_meta($attach_id, '_road_captain', $current_user->ID, true);
            		}
            		else if ($attach_id > 0) {
				$operation = 'update';
                		$my_post = array(
					'ID' => $attach_id,
					'post_title' => esc_html($title)
				);
				$status = wp_update_post($my_post);	
				if ($status != $attach_id) {
					wp_die('Failed to update this attachment.', 403);
				}
            		}

            		if ($attach_id > 0) {
               			 wp_redirect(add_query_arg(array(
                    			'post' => $attach_id,
					'op' => $operation,
                    			'return' => urlencode($return)
                		), get_permalink()), 303);
            		}
            		else {
                		wp_redirect(add_query_arg(array(
                    			'return' => urlencode($return)
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

		if (isset($_GET['post'])) {
			$error = self::check_post_id();
			if (!empty($error)) {
				return $return_to_page . $error;
			}
			$attach_id = intval($_GET['post']);
		}
		else {
			$attach_id = 0;
		}

        	if (!$is_road_captain) {
			return $return_to_page . '<div class="callout small warning"><p>You are not allowed to upload files.</p></div>';
		}

        	if ($attach_id != 0){
            		$post = get_post($attach_id);
            		$title = $post->post_title;
            		$attachment_url = wp_get_attachment_url($attach_id);
        	}
        	else {
            		$title = '';
            		$attachment_url = '';
        	}
		
		$operation = '';
		if (isset($_GET['op'])) {
			$operation = $_GET['op'];
		}

        	ob_start();
        	include('file-upload-form.php');
        	return ob_get_clean();
    	}
	
	// Generates the [pwtc_mapdb_delete_file] shortcode.
	public static function shortcode_delete_file($atts) {
		$a = shortcode_atts(array('use_return' => 'no'), $atts);
		$use_return = $a['use_return'] == 'yes';

		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small alert"><p>You must be logged in to delete upload files.</p></div>';
		}
		$user_info = get_userdata($current_user->ID);
		if ($allow_leaders) {
			$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);
		}
		else {
			$is_road_captain = user_can($current_user,'edit_published_rides');
		}
		$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);

		$return = '';
		if (isset($_GET['return'])) {
			$return = $_GET['return'];
		}

		if (isset($_POST['attach_id'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'file-delete-form')) {
				wp_nonce_ays('');
			}

            		$attach_id = intval($_POST['attach_id']);
            		$status = wp_delete_attachment($attach_id, true);
            		if ($status === false) {
                		wp_die('Failed to delete the upload file', 403);
            		}	

            		wp_redirect(add_query_arg(array(
                		'post' => $attach_id,
                		'return' => urlencode($return)
            		), get_permalink()), 303);
            		exit;
        	}

        	$return_link = '';
		$return_to_page = '';
		if (!empty($return) and $use_return) {
			$return_link = esc_url($return);
			$return_to_page = self::create_return_link($return_link);
		}

        	$error = self::check_post_id();
        	if (!empty($error)) {
            		return $return_to_page . $error;
        	}
        	$attach_id = intval($_GET['post']);

        	if (!$is_road_captain) {
			return $return_to_page . '<div class="callout small warning"><p>You are not allowed to delete upload files.</p></div>';
		}

        	$post = get_post($attach_id);
        	$title = $post->post_title;

		ob_start();
		include('file-delete-form.php');
		return ob_get_clean();
	}
	
        // Generates the [pwtc_mapdb_manage_files] shortcode.
	public static function shortcode_manage_files($atts) {
		$a = shortcode_atts(array('limit' => '10', 'search' => 'close'), $atts);
		$limit = intval($a['limit']);
		$search_open = $a['search'] == 'open';

		$current_user = wp_get_current_user();

		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please <a href="/wp-login.php">log in</a> to view the uploaded files.</p></div>';
		}

		if (isset($_POST['file_title']) and isset($_POST['offset'])) {
			wp_redirect(add_query_arg(array(
				'title' => urlencode(trim($_POST['file_title'])),
				'offset' => intval($_POST['offset'])
			), get_permalink()), 303);
			exit;
		}

		$user_info = get_userdata($current_user->ID);
		if ($allow_leaders) {
			$is_road_captain = in_array(PwtcMapdb::ROLE_ROAD_CAPTAIN, $user_info->roles);
		}
		else {
			$is_road_captain = user_can($current_user,'edit_published_rides');
		}
		$is_ride_leader = in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles);

        	if (!$is_road_captain) {
			return '<div class="callout small warning"><p>You are not allowed to manage upload files.</p></div>';
		}

		if (isset($_GET['title'])) {
			$file_title = $_GET['title'];
		}
		else {
			$file_title = '';
		}

		if (isset($_GET['offset'])) {
			$offset = intval($_GET['offset']);
		}
		else {
			$offset = 0;
		}

		$query_args = [
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'post_status' => 'any',
			'post_type' => 'attachment',
			'orderby'   => 'title',
			'order'     => 'ASC',
		];
        	$query_args['meta_query'][] = [
            		'key' => '_road_captain',
            		'compare' => 'EXISTS',
        	];

		if (!empty($file_title)) {
			$query_args['s'] = $file_title;	
		}
		
		if ($limit > 0)	{
			$query_args['offset'] = $offset;
		}

		$query = new WP_Query($query_args);

		$return_uri = $_SERVER['REQUEST_URI'];

		ob_start();
		include('manage-files-form.php');
		return ob_get_clean();
	}	

    	// Generates the [pwtc_mapdb_new_file_link] shortcode.
	public static function shortcode_new_file_link($atts, $content) {
		$a = shortcode_atts(array('class' => ''), $atts);
		$return_uri = $_SERVER['REQUEST_URI'];
		if (empty($content)) {
			$content = 'new file upload';
		}
		$new_link = self::new_file_link($return_uri);
		return '<a class="' . $a['class'] . '" href="' . $new_link . '">' . $content . '</a>';
	}

    	/******************* Utility Functions ******************/

    	public static function create_return_link($return_url) {
		return '<ul class="breadcrumbs"><li><a href="' . $return_url . '">Back to Previous Page</a></li></ul>';
	}

    	public static function check_post_id($ignore_trash = false) {
		if (!isset($_GET['post'])) {
			return '<div class="callout small alert"><p>Attachment post ID parameter is missing.</p></div>';
		}

		$postid = intval($_GET['post']);
		if ($postid == 0) {
			return '<div class="callout small alert"><p>Attachment post ID parameter is invalid, it must be an integer number.</p></div>';
		}

        	$post = get_post($postid);
		if (!$post) {
			return '<div class="callout small alert"><p>Attachment post ' . $postid . ' does not exist, it may have been deleted.</p></div>';
		}

		if (get_post_type($post) != 'attachment') {
			return '<div class="callout small alert"><p>Attachment post ' . $postid . ' is not a attachment.</p></div>';
		}

        	return '';
	}
	
	public static function new_file_link($return=false) {
		$uri = self::UPLOAD_FILE_URI;
		if ($return) {
			$uri .= '?return=' . urlencode($return);
		}
		return esc_url($uri);
	}

    	public static function delete_file_link($postid, $return=false) {
		$uri = self::DELETE_FILE_URI;
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}

    	public static function edit_file_link($postid, $return=false) {
		$uri = self::UPLOAD_FILE_URI;
		$uri .= '?post=' . $postid;
		if ($return) {
			$uri .= '&return=' . urlencode($return);
		}
		return esc_url($uri);
	}

}
