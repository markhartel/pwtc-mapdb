<?php

class PwtcMapdb_Map {

    private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	private static function init_hooks() {
        self::$initiated = true;

        // Register shortcode callbacks
        add_shortcode('pwtc_search_mapdb', array('PwtcMapdb_Map', 'shortcode_search_mapdb'));
	add_shortcode('pwtc_mapdb_edit_map', array('PwtcMapdb_Map', 'shortcode_edit_map'));

        // Register ajax callbacks
        add_action('wp_ajax_pwtc_mapdb_lookup_maps', array('PwtcMapdb_Map', 'lookup_maps_callback') );

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
	
	// Generates the [pwtc_mapdb_edit_map] shortcode.
	public static function shortcode_edit_map($atts) {
		if (isset($_POST['upload_file'])) {
			if (!isset($_FILES['map_file'])) {
				error_log('ERROR: missing input parameter');
			}
			else if ($_FILES['map_file']['size'] == 0) {
				error_log('ERROR: uploaded file is empty or not selected');
			}
			else if ($_FILES['map_file']['error'] != UPLOAD_ERR_OK) {
				error_log('ERROR: file upload error code ' . $_FILES['map_file']['error']);
			}
			else {
				$filename = $_FILES['map_file']['name'];
				error_log('Uploaded file name: ' . $filename);
				$tmpname = $_FILES['map_file']['tmp_name'];
				error_log('Uploaded tmp file location: ' . $tmpname);

				$upload_dir = wp_upload_dir();
				$movefile = $upload_dir['path'] . '/' . $filename;
				error_log('Uploaded file move location: ' . $movefile);

				$status = move_uploaded_file($tmpname, $movefile);
				if ($status === false) {
					error_log('ERROR: file move failed');
				}
			}

			wp_redirect(get_permalink(), 303);
			exit;
		}

		ob_start();
        	include('map-edit-form.php');
        	return ob_get_clean();
	}
    
    /******* AJAX request/response callback functions *******/

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
			$href_type = '';
			while (have_rows(PwtcMapdb::MAP_FIELD) ): the_row();
				$href_type = get_sub_field(PwtcMapdb::MAP_TYPE_FIELD);
				if ($href_type == 'file') {
					$file = get_sub_field(PwtcMapdb::MAP_FILE_FIELD);
					$href = esc_url($file['url']);
					$url = '<a title="Download ride route map file." target="_blank" href="' . $href . '">';
				}
				else if ($href_type == 'link') {
					$link = get_sub_field(PwtcMapdb::MAP_LINK_FIELD);
					$href = esc_url($link);
					$url = '<a title="Display online ride route map." target="_blank" href="' . $href . '">';
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
				'type' => $href_type,
				'href' => $href,
				'edit' => $edit_url
			];
			$results[] = $map;
		}
		wp_reset_postdata();
		return $results;
	}
}
