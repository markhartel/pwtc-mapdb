<?php

class PwtcMapdb_Signup {

    private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	private static function init_hooks() {
        self::$initiated = true;
    }

	/******************* Shortcode Functions ******************/
	
	// Generates the [pwtc_mapdb_rider_signup] shortcode.
	public static function shortcode_rider_signup($atts) {
		$error = PwtcMapdb::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$error = PwtcMapdb::check_post_id();
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
		
		if (get_field(PwtcMapdb::RIDE_CANCELED, $postid)) {
			return '<div class="callout small warning"><p>The ride "' . $ride_title . '" has been canceled, no sign up allowed. ' . $return_to_ride . '</p></div>';
		}

		if (!in_array(PwtcMapdb::ROLE_CURRENT_MEMBER, (array) $current_user->roles) and 
		    !in_array(PwtcMapdb::ROLE_EXPIRED_MEMBER, (array) $current_user->roles)) {
			return '<div class="callout small warning"><p>You must be a club member to sign up for rides. ' . $return_to_ride . '</p></div>';
		}
		
		$expired = false;
		if (in_array(PwtcMapdb::ROLE_EXPIRED_MEMBER, (array) $current_user->roles)) {
			$expired = true;
		}
				
		$ride_signup_mode = PwtcMapdb::get_signup_mode($postid);

		$ride_signup_cutoff = PwtcMapdb::get_signup_cutoff($postid);
		
		$ride_signup_limit = PwtcMapdb::get_signup_limit($postid);

		if ($ride_signup_mode == 'paperless') {
			$set_mileage = true;
		}
		else if ($ride_signup_mode == 'hardcopy') {
			$set_mileage = false;
		}
		else {
			return '<div class="callout small warning"><p>The leader for ride "' . $ride_title . '" does not allow online sign up. ' . $return_to_ride . '</p></div>';			
		}

		$error = PwtcMapdb::check_ride_start($postid, $ride_signup_mode, $ride_signup_cutoff, $return_to_ride);
		if (!empty($error)) {
			return $error;
		}

		$signup_locked = PwtcMapdb::get_signup_locked($postid);
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
			if ($_POST['accept_user_signup'] == 'yes') {
				PwtcMapdb::delete_all_signups($postid, $current_user->ID);
				$value = json_encode(array('userid' => $current_user->ID, 'mileage' => ''.$mileage, 'attended' => true));
				add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID, $value);
			}
			else {
				PwtcMapdb::delete_all_signups($postid, $current_user->ID);
			}
		}

		if (isset($_POST['contact_phone'])) {
			if (function_exists('pwtc_members_format_phone_number')) {
				$phone = pwtc_members_format_phone_number($_POST['contact_phone']);
			}
			else {
				$phone = sanitize_text_field($_POST['contact_phone']);
			}
			update_field(PwtcMapdb::USER_EMER_PHONE, $phone, 'user_'.$current_user->ID);
		}

		if (isset($_POST['contact_name'])) {
			$name = sanitize_text_field($_POST['contact_name']);
			update_field(PwtcMapdb::USER_EMER_NAME, $name, 'user_'.$current_user->ID);
		}
		
		if (isset($_POST['accept_terms'])) {
			if ($_POST['accept_terms'] == 'yes') {
				update_field(PwtcMapdb::USER_RELEASE_ACCEPTED, true, 'user_'.$current_user->ID);
			}
		}

		$signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID);
		$accept_signup = true;
		foreach($signup_list as $item) {
			$arr = json_decode($item, true);
			if ($arr['userid'] == $current_user->ID) {
				$accept_signup = false;
			}
		}

		if ($accept_signup and $ride_signup_limit > 0) {
			if (count($signup_list)+count(get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER)) >= $ride_signup_limit) {
				return '<div class="callout small warning"><p>You cannot sign up for ride "' . $ride_title . '" because it is full. <em>A maximum of ' . $ride_signup_limit . ' riders are allowed on this ride.</em> ' . $return_to_ride . '</p></div>';
			}
		}

		$user_info = get_userdata($current_user->ID);
		$rider_name = $user_info->first_name . ' ' . $user_info->last_name;
		$contact_phone = get_field(PwtcMapdb::USER_EMER_PHONE, 'user_'.$current_user->ID);
		$contact_name = get_field(PwtcMapdb::USER_EMER_NAME, 'user_'.$current_user->ID);
		$release_accepted = get_field(PwtcMapdb::USER_RELEASE_ACCEPTED, 'user_'.$current_user->ID);

		if (isset($_POST['accept_user_signup'])) {
			wp_redirect(add_query_arg(array(
				'post' => $postid
			), get_permalink()), 303);
			exit;
		}

		ob_start();
		include('signup-member-form.php');
		return ob_get_clean();
	}

	// Generates the [pwtc_mapdb_view_signup] shortcode.
	public static function shortcode_view_signup($atts) {
		$a = shortcode_atts(array('unused_rows' => 0), $atts);
		$unused_rows = abs(intval($a['unused_rows']));
		
		$error = PwtcMapdb::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$error = PwtcMapdb::check_post_id();
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
		
		if (get_field(PwtcMapdb::RIDE_CANCELED, $postid)) {
			return '<div class="callout small warning"><p>The ride "' . $ride_title . '" has been canceled, no sign up view allowed. ' . $return_to_ride . '</p></div>';
		}

		if (!user_can($current_user,'edit_published_rides')) {
			$denied = true;
			$leaders = PwtcMapdb::get_leader_userids($postid);
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
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE);
			add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE, $_POST['ride_signup_mode'], true);
		}

		if (isset($_POST['ride_signup_cutoff'])) {
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_CUTOFF);
			add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_CUTOFF, abs(intval($_POST['ride_signup_cutoff'])), true);
		}
		
		if (isset($_POST['ride_signup_limit'])) {
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LIMIT);
			add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LIMIT, abs(intval($_POST['ride_signup_limit'])), true);
		}
		
		if (isset($_POST['signup_members_only'])) {
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MEMBERS_ONLY);
			add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MEMBERS_ONLY, $_POST['signup_members_only'] == 'yes', true);
		}
		
		if (isset($_POST['signup_userid'])) {
			$userid = intval($_POST['signup_userid']);
			if ($userid != 0) {
				$mileage = false;
				if (isset($_POST['signup_rider_mileage'])) {
					if (!empty(trim($_POST['signup_rider_mileage']))) {
						$mileage = $_POST['signup_rider_mileage'];
					}
				}
				$signup = PwtcMapdb::fetch_user_signup($postid, $userid);
				if ($signup) {
					if ($mileage) {
						$signup['mileage'] = ''.abs(intval($mileage));
					}
					PwtcMapdb::delete_all_signups($postid, $userid);
					$value = json_encode($signup);
					add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID, $value);
				}
				else {
					if ($mileage) {
						$value = json_encode(array('userid' => $userid, 'mileage' => ''.abs(intval($mileage)), 'attended' => true));
					}
					else {
						$value = json_encode(array('userid' => $userid, 'mileage' => '', 'attended' => true));
					}
					add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID, $value);	
				}
			}
		}
				
		$ride_signup_mode = PwtcMapdb::get_signup_mode($postid);

		$ride_signup_cutoff = PwtcMapdb::get_signup_cutoff($postid);
		
		$ride_signup_limit = PwtcMapdb::get_signup_limit($postid);
		
		$signup_members_only = PwtcMapdb::get_signup_members_only($postid);

		if ($ride_signup_mode != 'no') {
			if ($ride_signup_mode == 'paperless') {
				$paperless = $set_mileage = $take_attendance = true;
				$cutoff_units = '(hours after ride start)';
			}
			else {
				$paperless = $set_mileage = $take_attendance = false;
				$cutoff_units = '(hours before ride start)';
			}

			$now_date = PwtcMapdb::get_current_time();
			$cutoff_date = PwtcMapdb::get_signup_cutoff_time($postid, $ride_signup_mode, $ride_signup_cutoff);
			$cutoff_date_str = $cutoff_date->format('m/d/Y g:ia');

			$signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID);
			$nonmember_signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER);

			if (isset($_POST['lock_signup'])) {
				if ($_POST['lock_signup'] == 'yes') {
					add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LOCKED, true, true);
				}
				else {
					delete_post_meta( $postid, PwtcMapdb::RIDE_SIGNUP_LOCKED);
				}
			}
			$signup_locked = PwtcMapdb::get_signup_locked($postid);
			if ($signup_locked) {
				$set_mileage = $take_attendance = false;
			}
		}
		else {
			$cutoff_units = '(hours)';
		}
		
		if (isset($_POST['lock_signup']) or isset($_POST['ride_signup_mode']) or isset($_POST['signup_userid'])) {
			wp_redirect(add_query_arg(array(
				'post' => $postid
			), get_permalink()), 303);
			exit;
		}

		ob_start();
		include('signup-view-form.php');
		return ob_get_clean();
	}

	// Generates the [pwtc_mapdb_download_signup] shortcode.
	public static function shortcode_download_signup($atts) {
		$error = PwtcMapdb::check_plugin_dependency();
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
		$error = PwtcMapdb::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$error = PwtcMapdb::check_post_id();
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

		$timestamp = time() - PwtcMapdb::TIMESTAMP_OFFSET;
		
		if (get_field(PwtcMapdb::RIDE_CANCELED, $postid)) {
			return '<div class="callout small warning"><p>The ride "' . $ride_title . '" has been canceled, no sign up allowed. ' . $return_to_ride . '</p></div>';
		}
		
		//PwtcMapdb::init_online_signup($postid);

		$ride_signup_mode = PwtcMapdb::get_signup_mode($postid);

		$ride_signup_cutoff = PwtcMapdb::get_signup_cutoff($postid);
		
		$ride_signup_limit = PwtcMapdb::get_signup_limit($postid);
		
		$members_only = PwtcMapdb::get_signup_members_only($postid);

		if ($ride_signup_mode == 'no') {
			return '<div class="callout small warning"><p>The leader for ride "' . $ride_title . '" does not allow online sign up. ' . $return_to_ride . '</p></div>';			
		}
		
		if ($members_only) {
			return '<div class="callout small warning"><p>Only club members may attend ride "' . $ride_title . '." ' . $return_to_ride . '</p></div>';
		}

		$error = PwtcMapdb::check_ride_start($postid, $ride_signup_mode, $ride_signup_cutoff, $return_to_ride);
		if (!empty($error)) {
			return $error;
		}

		$signup_locked = PwtcMapdb::get_signup_locked($postid);
		if ($signup_locked) {
			return '<div class="callout small warning"><p>You cannot sign up for ride "' . $ride_title . '" because it is closed. ' . $return_to_ride . '</p></div>';	
		}

		ob_start();
		include('signup-nonmember-form.php');
		return ob_get_clean();
	}

	// Generates the [pwtc_mapdb_reset_signups] shortcode.
	public static function shortcode_reset_signups($atts) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '';
		}

		if (!user_can($current_user,'manage_options')) {
			return '';
		}

		$error = PwtcMapdb::check_post_id();
		if (!empty($error)) {
			return $error;
		}
		$postid = intval($_GET['post']);

		if (isset($_POST['reset_ride_signups'])) {
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LOCKED);
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID);
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER);
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE);
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_CUTOFF);
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LIMIT);
			delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MEMBERS_ONLY);
			
			wp_redirect(add_query_arg(array(
				'post' => $postid
			), get_permalink()), 303);
			exit;
		}

		ob_start();
		include('signup-reset-form.php');
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

		$now = PwtcMapdb::get_current_time();
		$query_args = [
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'post_type' => PwtcMapdb::POST_TYPE_RIDE,
			'meta_query' => [
				[
					'key' => PwtcMapdb::RIDE_DATE,
					'value' => $now->format('Y-m-d 00:00:00'),
					'compare' => '>=',
					'type' => 'DATETIME'
				],
				[
					'key' => PwtcMapdb::RIDE_SIGNUP_USERID,
					'value' => '"userid":' . $current_user->ID . ',',
					'compare' => 'LIKE'
				],
			],
			'orderby' => [PwtcMapdb::RIDE_DATE => 'ASC'],
		];		
		$query = new WP_Query($query_args);

		if (!$query->have_posts()) {
			return '<div class="callout small"><p>Hello ' . $rider_name . ', you are not signed up for any upcoming rides.</p></div>';
		}

		ob_start();
		include('signup-your-form.php');
		return ob_get_clean();
	}
    
    /******************* Utility Functions ******************/

}
