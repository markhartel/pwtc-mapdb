<?php

class PwtcMapdb_Signup {

	const LOCAL_SIGNUP_ID = 'ride_signup_id';
	const LOCAL_SIGNUP_NAME = 'ride_signup_name';
	const LOCAL_EMER_NAME = 'ride_signup_contact_name';
	const LOCAL_EMER_PHONE = 'ride_signup_contact_phone';

	const TIMESTAMP_OFFSET = 50*365*24*60*60;

    private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	private static function init_hooks() {
		self::$initiated = true;
		
		// Register action callbacks
		add_action('template_redirect', array('PwtcMapdb_Signup', 'download_ride_signup'));

		// Register shortcode callbacks
		add_shortcode('pwtc_mapdb_rider_signup', array('PwtcMapdb_Signup', 'shortcode_rider_signup'));
		add_shortcode('pwtc_mapdb_view_signup', array('PwtcMapdb_Signup', 'shortcode_view_signup'));
		add_shortcode('pwtc_mapdb_nonmember_signup', array('PwtcMapdb_Signup', 'shortcode_nonmember_signup'));
		add_shortcode('pwtc_mapdb_show_userid_signups', array('PwtcMapdb_Signup', 'shortcode_show_userid_signups'));
		add_shortcode('pwtc_mapdb_download_signup', array('PwtcMapdb_Signup', 'shortcode_download_signup'));
		add_shortcode('pwtc_mapdb_reset_signups', array('PwtcMapdb_Signup', 'shortcode_reset_signups'));

		// Register ajax callbacks
		add_action('wp_ajax_pwtc_mapdb_edit_signup', array('PwtcMapdb_Signup', 'edit_signup_callback'));
		add_action('wp_ajax_pwtc_mapdb_edit_nonmember_signup', array('PwtcMapdb_Signup', 'edit_nonmember_signup_callback'));
		add_action('wp_ajax_pwtc_mapdb_log_mileage', array('PwtcMapdb_Signup', 'log_mileage_callback'));
		add_action('wp_ajax_pwtc_mapdb_check_nonmember_signup', array('PwtcMapdb_Signup', 'check_nonmember_signup_callback'));
		add_action('wp_ajax_pwtc_mapdb_accept_nonmember_signup', array('PwtcMapdb_Signup', 'accept_nonmember_signup_callback'));
		add_action('wp_ajax_pwtc_mapdb_cancel_nonmember_signup', array('PwtcMapdb_Signup', 'cancel_nonmember_signup_callback'));

		// Register nopriv ajax callbacks
		add_action('wp_ajax_nopriv_pwtc_mapdb_check_nonmember_signup', array('PwtcMapdb_Signup', 'check_nonmember_signup_callback'));
		add_action('wp_ajax_nopriv_pwtc_mapdb_accept_nonmember_signup', array('PwtcMapdb_Signup', 'accept_nonmember_signup_callback'));
		add_action('wp_ajax_nopriv_pwtc_mapdb_cancel_nonmember_signup', array('PwtcMapdb_Signup', 'cancel_nonmember_signup_callback'));

	}
	
	/******************* Action Functions ******************/

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
				if (get_post_type($post) != PwtcMapdb::POST_TYPE_RIDE) {
					return;
				}
				$current_user = wp_get_current_user();
				if ( 0 == $current_user->ID ) {
					return;
				}
				
				$unused_rows = 0;
				if (isset($_POST['unused_rows'])) {
					$unused_rows = abs(intval($_POST['unused_rows']));
				}

				$list = get_post_meta($rideid, PwtcMapdb::RIDE_SIGNUP_USERID);
				$signup_list = [];
				foreach ($list as $item) {
					$arr = json_decode($item, true);
					$signup_list[] = $arr;
				}
				$list = get_post_meta($rideid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER);
				foreach ($list as $item) {
					$arr = json_decode($item, true);
					$signup_list[] = $arr;
				}

				$post = get_post($rideid);
				$ride_title = $post->post_title;
				$date = DateTime::createFromFormat('Y-m-d H:i:s', get_field(PwtcMapdb::RIDE_DATE, $rideid));
				$ride_date = $date->format('m/d/Y g:ia');
				$leaders = PwtcMapdb::get_leader_userids($rideid);
				$ride_leader = '';
				if (count($leaders) > 0) {
					$user_info = get_userdata($leaders[0]);
					if ($user_info) {
						$ride_leader = $user_info->first_name . ' ' . $user_info->last_name;
					}
					else {
						$ride_leader = 'Unknown';
					}
				}
			}
			else {
				$unused_rows = 0;
				$signup_list = [];
				$ride_title = '';
				$ride_date = '';
				$ride_leader = '';
			}			
			$waiver_post = get_page_by_title('Terms and Conditions');
			if ($waiver_post) {
				$release_waiver = wp_kses(get_the_content(null, false, $waiver_post), array());
			}
			else {
				$release_waiver = 'Terms and Conditions page not found!';
			}

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
			$nrows_1st_page = 13;
			$nrows_next_pages = 16;
			$extra_rows = count($signup_list)+$unused_rows-$nrows_1st_page-$nrows_next_pages;
			if ($extra_rows > 0) {
				$max_pages += (int)ceil((float)$extra_rows/(float)$nrows_next_pages);
			}
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
					$pdf->Cell(110, $cell_h, 'Leader: '.$ride_leader, 1, 0,'L');
					$pdf->Cell(50, $cell_h, 'Distance:', 1, 0,'L');
					$pdf->Image(PWTC_MILEAGE__PLUGIN_DIR . 'pbc_logo.png', $x_margin, $y_margin, $logo_size, $logo_size);
					$waiver_y = $y_margin+35;
					$table_y = $y_margin+75;
					$rows_per_page = $nrows_1st_page;
				}
				else {
					$waiver_y = $y_margin+10;
					$table_y = $y_margin+50;
					$rows_per_page = $nrows_next_pages;		
				}
				$pdf->SetFont('Arial', '', 8);
				$pdf->SetXY($x_margin, $waiver_y);
				$pdf->MultiCell(0, 3, $release_waiver);
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
					$mileage = '';
					if ($rider_count < count($signup_list)) {
						$arr = $signup_list[$rider_count];
						if ($arr['userid']) {
							$userid = $arr['userid'];
							$mileage = '';
							$user_info = get_userdata($userid);
							if ($user_info) {
								$rider_name = $user_info->first_name . ' ' . $user_info->last_name;
							}
							else {
								$rider_name = 'Unknown';
							}
							$rider_id = PwtcMapdb::get_rider_id($userid);
							$contact = self::get_emergency_contact($userid, false);
						}
						else {
							$signup_id = $arr['signup_id'];
							$rider_name = $arr['name'];
							$contact_phone = $arr['contact_phone'];
							$contact_name = $arr['contact_name'];
							$contact = self::get_nonmember_emergency_contact($contact_phone, $contact_name, false);	
							$mileage = 'n/a';
							$rider_id = 'n/a';						
						}
					}
					$rider_count++;
					$y_offset += $cell_h;
					$pdf->SetXY($x_margin, $y_offset);
					$pdf->SetFont('Arial', '', $font_size);
					$pdf->Cell(10, $cell_h, $rider_count, 1, 0,'C');
					$pdf->Cell(30, $cell_h, $rider_id, 1, 0,'C');
					$pdf->Cell(20, $cell_h, $mileage, 1, 0,'C');
					$pdf->Cell(70, $cell_h, $rider_name, 1, 0,'L');
					$pdf->Cell(50, $cell_h, '', 1, 0,'L');
					$pdf->Cell(80, $cell_h, $contact, 1, 0,'L');
				}
			}
			$pdf->Output('F', 'php://output');
			die;
		}
	}	

	/******************* Shortcode Functions ******************/
	
	// Generates the [pwtc_mapdb_rider_signup] shortcode.
	public static function shortcode_rider_signup($atts) {
		$error = PwtcMapdb::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$error = self::check_post_id();
		if (!empty($error)) {
			return $error;
		}
		$postid = intval($_GET['post']);

		$current_user = wp_get_current_user();

		if (isset($_POST['accept_user_signup'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'signup-member-form')) {
				wp_nonce_ays('');
			}

			$mileage = '';
			if (isset($_POST['mileage'])) {
				if (!empty(trim($_POST['mileage']))) {
					$mileage = abs(intval($_POST['mileage']));
				}
			}
	
			if (isset($_POST['accept_user_signup'])) {
				if ($_POST['accept_user_signup'] != 'no' ) {
					self::delete_all_signups($postid, $current_user->ID);
					$value = json_encode(array('userid' => $current_user->ID, 'mileage' => ''.$mileage, 'attended' => true));
					add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID, $value);
				}
				else {
					self::delete_all_signups($postid, $current_user->ID);
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

			wp_redirect(add_query_arg(array(
				'post' => $postid
			), get_permalink()), 303);
			exit;
		}
		
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small alert"><p>You must be logged in to sign up for rides.</p></div>';
		}
		
		$ride_title = esc_html(get_the_title($postid));
		$ride_link = esc_url(get_the_permalink($postid));
		$return_to_ride = 'Click <a href="' . $ride_link . '">here</a> to return to the posted ride.';
		
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
				
		$ride_signup_mode = self::get_signup_mode($postid);

		$ride_signup_cutoff = self::get_signup_cutoff($postid);
		
		$ride_signup_limit = self::get_signup_limit($postid);

		if ($ride_signup_mode == 'paperless') {
			$set_mileage = true;
		}
		else if ($ride_signup_mode == 'hardcopy') {
			$set_mileage = false;
		}
		else {
			return '<div class="callout small warning"><p>The leader for ride "' . $ride_title . '" does not allow online sign up. ' . $return_to_ride . '</p></div>';			
		}

		$error = self::check_ride_start($postid, $ride_signup_mode, $ride_signup_cutoff, $return_to_ride);
		if (!empty($error)) {
			return $error;
		}

		$signup_locked = self::get_signup_locked($postid);
		if ($signup_locked) {
			return '<div class="callout small warning"><p>You cannot sign up for ride "' . $ride_title . '" because it is closed. ' . $return_to_ride . '</p></div>';	
		}

		$signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID);
		$nonmember_signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER);
		$ride_signup_count = count($signup_list) + count($nonmember_signup_list);
		
		$accept_signup = true;
		$mileage = '';
		foreach($signup_list as $item) {
			$arr = json_decode($item, true);
			if ($arr['userid'] == $current_user->ID) {
				$accept_signup = false;
				$mileage = $arr['mileage'];
			}
		}

		if ($accept_signup and $ride_signup_limit > 0) {
			if ($ride_signup_count >= $ride_signup_limit) {
				return '<div class="callout small warning"><p>You cannot sign up for ride "' . $ride_title . '" because it is full. <em>A maximum of ' . $ride_signup_limit . ' riders are allowed on this ride.</em> ' . $return_to_ride . '</p></div>';
			}
		}

		$user_info = get_userdata($current_user->ID);
		$rider_name = $user_info->first_name . ' ' . $user_info->last_name;
		$contact_phone = get_field(PwtcMapdb::USER_EMER_PHONE, 'user_'.$current_user->ID);
		$contact_name = get_field(PwtcMapdb::USER_EMER_NAME, 'user_'.$current_user->ID);
		$release_accepted = get_field(PwtcMapdb::USER_RELEASE_ACCEPTED, 'user_'.$current_user->ID);

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

		$error = self::check_post_id();
		if (!empty($error)) {
			return $error;
		}
		$postid = intval($_GET['post']);
		
		$current_user = wp_get_current_user();

		if (isset($_POST['lock_signup']) or isset($_POST['ride_signup_mode']) or isset($_POST['signup_userid'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'signup-view-form')) {
				wp_nonce_ays('');
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
					$signup = self::fetch_user_signup($postid, $userid);
					if ($signup) {
						if ($mileage) {
							$signup['mileage'] = ''.abs(intval($mileage));
						}
						self::delete_all_signups($postid, $userid);
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

			if (isset($_POST['lock_signup'])) {
				if ($_POST['lock_signup'] == 'yes') {
					add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LOCKED, true, true);
				}
				else {
					delete_post_meta( $postid, PwtcMapdb::RIDE_SIGNUP_LOCKED);
				}
			}

			wp_redirect(add_query_arg(array(
				'post' => $postid
			), get_permalink()), 303);
			exit;
		}
		
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small alert"><p>You must be logged in to view sign up list for rides.</p></div>';
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
				
		$ride_signup_mode = self::get_signup_mode($postid);

		$ride_signup_cutoff = self::get_signup_cutoff($postid);
		
		$ride_signup_limit = self::get_signup_limit($postid);
		
		$signup_members_only = self::get_signup_members_only($postid);

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
			$cutoff_date = self::get_signup_cutoff_time($postid, $ride_signup_mode, $ride_signup_cutoff);
			$cutoff_date_str = $cutoff_date->format('m/d/Y g:ia');

			$signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID);
			$nonmember_signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER);

			$signup_locked = self::get_signup_locked($postid);
			if ($signup_locked) {
				$set_mileage = $take_attendance = false;
			}
		}
		else {
			$cutoff_units = '(hours)';
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

		$error = self::check_post_id();
		if (!empty($error)) {
			return $error;
		}
		$postid = intval($_GET['post']);

		$current_user = wp_get_current_user();
		if ( 0 != $current_user->ID ) {
			return '<div class="callout small alert"><p>This page is ONLY for non-member ride sign up and you must be logged out to use it.</p></div>';
		}

		$ride_title = esc_html(get_the_title($postid));
		$ride_link = esc_url(get_the_permalink($postid));
		$return_to_ride = 'Click <a href="' . $ride_link . '">here</a> to return to the posted ride.';

		$timestamp = time() - self::TIMESTAMP_OFFSET;
		
		if (get_field(PwtcMapdb::RIDE_CANCELED, $postid)) {
			return '<div class="callout small warning"><p>The ride "' . $ride_title . '" has been canceled, no sign up allowed. ' . $return_to_ride . '</p></div>';
		}
		
		$ride_signup_mode = self::get_signup_mode($postid);

		$ride_signup_cutoff = self::get_signup_cutoff($postid);
		
		$ride_signup_limit = self::get_signup_limit($postid);
		
		$members_only = self::get_signup_members_only($postid);

		if ($ride_signup_mode == 'no') {
			return '<div class="callout small warning"><p>The leader for ride "' . $ride_title . '" does not allow online sign up. ' . $return_to_ride . '</p></div>';			
		}
		
		if ($members_only) {
			return '<div class="callout small warning"><p>Only club members may attend ride "' . $ride_title . '." ' . $return_to_ride . '</p></div>';
		}

		$error = self::check_ride_start($postid, $ride_signup_mode, $ride_signup_cutoff, $return_to_ride);
		if (!empty($error)) {
			return $error;
		}

		$signup_locked = self::get_signup_locked($postid);
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

		$error = self::check_post_id();
		if (!empty($error)) {
			return $error;
		}
		$postid = intval($_GET['post']);

		if (isset($_POST['reset_ride_signups'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'signup-reset-form')) {
				wp_nonce_ays('');
			}
			
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

	/******* AJAX request/response callback functions *******/

	public static function edit_signup_callback() {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			$response = array(
				'error' => 'Server user access denied for sign up edit.'
			);		
		}
		else if (isset($_POST['userid']) and isset($_POST['postid']) and isset($_POST['mileage']) and isset($_POST['oldmileage']) and isset($_POST['attended']) and isset($_POST['oldattended']) and isset($_POST['nonce'])) {
			$userid = intval($_POST['userid']);
			$postid = intval($_POST['postid']);
			$oldmileage = trim($_POST['oldmileage']);
			$mileage = trim($_POST['mileage']);
			$oldattended = trim($_POST['oldattended']);
			$attended = trim($_POST['attended']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_edit_signup')) {
				$response = array(
					'error' => 'Server security check failed for sign up edit.'
				);
			}
			else {
				if (!empty($mileage)) {
					$m = abs(intval($mileage));
					$mileage = '' . $m;
				}
				if ($mileage != $oldmileage or $attended != $oldattended) {
					$oldvalue = json_encode(array('userid' => $userid, 'mileage' => $oldmileage, 'attended' => boolval($oldattended)));
					$value = json_encode(array('userid' => $userid, 'mileage' => $mileage, 'attended' => boolval($attended)));
					if (update_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID, $value, $oldvalue)) {
						$response = array(
							'postid' => $postid,
							'userid' => $userid,
							'mileage' => $mileage,
							'attended' => $attended
						);
					}
					else {
						$response = array(
							'error' => 'Server sign up update failed.'
						);	
					}
				}
				else {
					$response = array(
						'postid' => $postid,
						'userid' => $userid,
						'mileage' => $mileage,
						'attended' => $attended
					);
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for sign up edit.'
			);		
		}
		echo wp_json_encode($response);
		wp_die();
	}	
	
	public static function edit_nonmember_signup_callback() {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			$response = array(
				'error' => 'Server user access denied for nonmember signup edit.'
			);		
		}
		else if (isset($_POST['signup_id']) and isset($_POST['postid']) and isset($_POST['attended']) and isset($_POST['oldattended']) and isset($_POST['nonce'])) {
			$signup_id = intval($_POST['signup_id']);
			$postid = intval($_POST['postid']);
			$oldattended = trim($_POST['oldattended']);
			$attended = trim($_POST['attended']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_edit_nonmember_signup')) {
				$response = array(
					'error' => 'Server security check failed for nonmember sign up edit.'
				);
			}
			else {
				if ($attended != $oldattended) {
					$list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER);
					$oldvalue = '';
					foreach($list as $item) {
						$arr = json_decode($item, true);
						if ($arr['signup_id'] == $signup_id) {
							$oldvalue = $item;
							break;
						}
					}
					if (!empty($oldvalue)) {
						$arr = json_decode($oldvalue, true);
						$value = json_encode(array('signup_id' => $arr['signup_id'], 'name' => $arr['name'], 'contact_phone' => $arr['contact_phone'], 'contact_name' => $arr['contact_name'], 'attended' => boolval($attended)));
						if (update_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER, $value, $oldvalue)) {
							$response = array(
								'postid' => $postid,
								'signup_id' => $signup_id,
								'attended' => $attended
							);
						}
						else {
							$response = array(
								'error' => 'Server nonmember sign up update failed.'
							);	
						}
					}
					else {
						$response = array(
							'error' => 'Server nonmember sign up ID not found for edit.'
						);	
					}
				}
				else {
					$response = array(
						'postid' => $postid,
						'signup_id' => $signup_id,
						'attended' => $attended
					);
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for nonmember sign up edit.'
			);		
		}
		echo wp_json_encode($response);
		wp_die();
	}	
	
	public static function check_nonmember_signup_callback() {
		if (isset($_POST['signup_id']) and isset($_POST['postid']) and isset($_POST['nonce'])) {
			$signup_id = intval($_POST['signup_id']);
			$postid = intval($_POST['postid']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_check_nonmember_signup')) {
				$response = array(
					'error' => 'Server security check failed for nonmember sign up check.'
				);
			}
			else {
				$found = false;
				$signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER);
				foreach($signup_list as $item) {
					$arr = json_decode($item, true);
					if ($arr['signup_id'] == $signup_id) {
						$found = true;
						break;
					}
				}
				if ($found) {
					$response = array(
						'postid' => $postid,
						'signup_id' => ''.$signup_id,
						'found' => $found,
						'signup_name' => $arr['name'],
						'signup_contact_phone' => $arr['contact_phone'],
						'signup_contact_name' => $arr['contact_name']
					);
				}
				else {
					$response = array(
						'postid' => $postid,
						'signup_id' => ''.$signup_id,
						'found' => $found
					);					
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for nonmember sign up check.'
			);		
		}
		echo wp_json_encode($response);
		wp_die();
	}	

	public static function accept_nonmember_signup_callback() {
		if (isset($_POST['signup_id']) and isset($_POST['postid']) and isset($_POST['nonce']) and isset($_POST['signup_name']) and isset($_POST['signup_contact_phone']) and isset($_POST['signup_contact_name']) and isset($_POST['signup_limit'])) {
			$signup_id = intval($_POST['signup_id']);
			$postid = intval($_POST['postid']);
			$signup_limit = intval($_POST['signup_limit']);
			$nonce = $_POST['nonce'];
			$signup_name = $_POST['signup_name'];
			$contact_phone = $_POST['signup_contact_phone'];
			$contact_name = $_POST['signup_contact_name'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_accept_nonmember_signup')) {
				$response = array(
					'error' => 'Server security check failed for nonmember sign up accept.'
				);
			}
			else {
				$total_signups = count(get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID)) +
					count(get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER));
				if ($signup_limit > 0 and $total_signups >= $signup_limit) {
					$response = array(
						'postid' => $postid,
						'signup_id' => ''.$signup_id,
						'warning' => 'You cannot sign up for this ride because it is full; a maximum of ' . $signup_limit . ' riders are allowed.'
					);				
				}
				else {
					if (function_exists('pwtc_members_format_phone_number')) {
						$contact_phone = pwtc_members_format_phone_number($contact_phone);
					}
					self::delete_all_nonmember_signups($postid, $signup_id);
					$value = json_encode(array('signup_id' => $signup_id, 'name' => $signup_name, 'contact_phone' => $contact_phone, 'contact_name' => $contact_name, 'attended' => true));
					add_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER, $value);
					$response = array(
						'postid' => $postid,
						'signup_id' => ''.$signup_id,
						'signup_name' => $signup_name,
						'signup_contact_phone' => $contact_phone,
						'signup_contact_name' => $contact_name
					);
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for nonmember sign up accept.'
			);		
		}
		echo wp_json_encode($response);
		wp_die();
	}	

	public static function cancel_nonmember_signup_callback() {
		if (isset($_POST['signup_id']) and isset($_POST['postid']) and isset($_POST['nonce'])) {
			$signup_id = intval($_POST['signup_id']);
			$postid = intval($_POST['postid']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_cancel_nonmember_signup')) {
				$response = array(
					'error' => 'Server security check failed for nonmember sign up cancel.'
				);
			}
			else {
				self::delete_all_nonmember_signups($postid, $signup_id);
				$response = array(
					'postid' => $postid,
					'signup_id' => ''.$signup_id
				);
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for nonmember sign up cancel.'
			);		
		}		
		echo wp_json_encode($response);
		wp_die();
	}
	
	public static function log_mileage_callback() {
		if (isset($_POST['postid']) and isset($_POST['nonce'])) {
			$postid = intval($_POST['postid']);
			$nonce = $_POST['nonce'];
			if (!wp_verify_nonce($nonce, 'pwtc_mapdb_log_mileage')) {
				$response = array(
					'error' => 'Server security check failed for log mileage.'
				);
			}
			else {
				$ride_date = PwtcMapdb::get_ride_start_time($postid);
				$interval = new DateInterval('P6M');	
				$ride_date->add($interval);
				$now = PwtcMapdb::get_current_time();
				if ($ride_date < $now) {
					$response = array(
						'error' => 'This ride must be less than 6 months old to log its mileage.'
					);	
				}
				else {
					$results = PwtcMileage_DB::fetch_ride_by_post_id($postid);
					if (count($results) > 0) {
						$response = array(
							'error' => 'Mileage for this ride has already been logged.'
						);
					}
					else {
						//$title = sanitize_text_field(get_the_title($postid));
						$post = get_post($postid);
						$title = $post->post_title;
						$date = PwtcMapdb::get_ride_start_time($postid);
						$startdate = $date->format('Y-m-d');
						$status = PwtcMileage_DB::insert_ride_with_postid($title, $startdate, $postid);
						if (false === $status or 0 === $status) {
							$response = array(
								'error' => 'Could not insert new ridesheet into mileage database.'
							);
						}
						else {
							$ride_id = PwtcMileage_DB::get_new_ride_id();
							$leaders = PwtcMapdb::get_leader_userids($postid);
							$nleaders = 0;
							$nriders = 0;
							$expired_ids = [];
							$missing_ids = [];
							$missing_leader_ids = [];
							foreach ($leaders as $item) {
								$memberid = get_field(PwtcMapdb::USER_RIDER_ID, 'user_'.$item);
								$result = PwtcMileage_DB::fetch_rider($memberid);
								if (count($result) > 0) {
									PwtcMileage_DB::insert_ride_leader($ride_id, $memberid);
									$nleaders++;
								}
								else {
									$missing_leader_ids[] = $memberid;
								}
							}
							$signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID);
							foreach ($signup_list as $item) {
								$arr = json_decode($item, true);
								$userid = $arr['userid'];
								$mileage = $arr['mileage'];
								$attended = $arr['attended'];
								if ($attended and !empty($mileage)) {
									$memberid = get_field(PwtcMapdb::USER_RIDER_ID, 'user_'.$userid);
									$user_info = get_userdata($userid);
									if (in_array(PwtcMapdb::ROLE_EXPIRED_MEMBER, (array) $user_info->roles)) {
										$expired_ids[] = $memberid;
									}
									else {
										$result = PwtcMileage_DB::fetch_rider($memberid);
										if (count($result) > 0) {
												PwtcMileage_DB::insert_ride_mileage($ride_id, $memberid, intval($mileage));
												$nriders++;
										}
										else {
											$missing_ids[] = $memberid;
										}
									}
								}
							}
							$response = array(
								'postid' => $postid,
								'rideid' => $ride_id,
								'num_leaders' => $nleaders,
								'num_riders' => $nriders,
								'missing_leaders' => $missing_leader_ids,
								'missing_riders' => $missing_ids,
								'expired_riders' => $expired_ids
							);											
						}
					}
				}
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for log mileage.'
			);		
		}		
		echo wp_json_encode($response);
		wp_die();
	}
    
	/******************* Utility Functions ******************/
	
	public static function get_signup_mode($postid) {
		$ride_signup_mode = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE, true);
		if (!$ride_signup_mode) {
			$ride_signup_mode = 'no';
		}
		return $ride_signup_mode;
	}

	public static function get_signup_cutoff($postid) {
		$ride_signup_cutoff = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_CUTOFF, true);
		if (!$ride_signup_cutoff) {
			$ride_signup_cutoff = 0;
		}
		return $ride_signup_cutoff;	
	}

	public static function get_signup_limit($postid) {
		$ride_signup_limit = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LIMIT, true);
		if (!$ride_signup_limit) {
			$ride_signup_limit = 0;
		}
		return $ride_signup_limit;
	}

	public static function get_signup_members_only($postid) {
		return get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MEMBERS_ONLY, true);
	}

	public static function get_signup_locked($postid) {
		return get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LOCKED, true);
	}

	public static function create_return_link($ride_url) {
		return 'Click <a href="' . $ride_url . '">here</a> to return to the posted ride.';
	}

	public static function get_signup_cutoff_time($postid, $mode, $pad) {
		$ride_date = PwtcMapdb::get_ride_start_time($postid);
		if ($pad > 0) {
			if ($mode == 'paperless') {
				$interval = new DateInterval('PT' . $pad . 'H');	
				$ride_date->add($interval);
			}
			else if ($mode == 'hardcopy') {
				$interval = new DateInterval('PT' . $pad . 'H');	
				$ride_date->sub($interval);
			}
		}
		return $ride_date;
	}

	public static function check_ride_start($postid, $mode, $pad, $return_to_ride) {
		$ride_title = esc_html(get_the_title($postid));
		$now_date = PwtcMapdb::get_current_time();
		$now_date_str = $now_date->format('m/d/Y g:ia');
		$cutoff_date = self::get_signup_cutoff_time($postid, $mode, $pad);
		$cutoff_date_str = $cutoff_date->format('m/d/Y g:ia');
		if ($now_date > $cutoff_date) {
			return '<div class="callout small warning"><p>You cannot sign up for ride "' . $ride_title . '" because you are past the sign up cutoff time at ' . $cutoff_date_str . '. ' . $return_to_ride . '</p></div>';
		}
		return '';
	}

	public static function fetch_user_signup($postid, $userid) {
		$signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID);
		foreach($signup_list as $item) {
			$arr = json_decode($item, true);
			if ($arr['userid'] == $userid) {
				return $arr;
			}
		}
		return false;
	}
	
	public static function delete_all_signups($postid, $userid) {
		$signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID);
		foreach($signup_list as $item) {
			$arr = json_decode($item, true);
			if ($arr['userid'] == $userid) {
				delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_USERID, $item);
			}
		}
	}
	
	public static function delete_all_nonmember_signups($postid, $signup_id) {
		$signup_list = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER);
		foreach($signup_list as $item) {
			$arr = json_decode($item, true);
			if ($arr['signup_id'] == $signup_id) {
				delete_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_NONMEMBER, $item);
			}
		}
	}

	public static function get_emergency_contact($userid, $use_link) {
		$contact_phone = trim(get_field(PwtcMapdb::USER_EMER_PHONE, 'user_'.$userid));
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
		$contact_name = trim(get_field(PwtcMapdb::USER_EMER_NAME, 'user_'.$userid));
		$contact = $contact_phone;
		if (!empty($contact_name)) {
			$contact .= ' (' . $contact_name . ')';
		}
		return $contact;
	}
	
	public static function get_nonmember_emergency_contact($contact_phone, $contact_name, $use_link) {
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
		$contact = $contact_phone;
		if (!empty($contact_name)) {
			$contact .= ' (' . $contact_name . ')';
		}
		return $contact;
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

		if (get_post_type($post) != PwtcMapdb::POST_TYPE_RIDE) {
			return '<div class="callout small alert"><p>Ride post ' . $postid . ' is not a scheduled ride.</p></div>';
		}

		$post_status = get_post_status($post);
		if ($post_status != 'publish') {
			if ($post_status == 'trash') {
				return '<div class="callout small alert"><p>Ride post ' . $postid . ' has been deleted.</p></div>';
			}
			else {
				return '<div class="callout small alert"><p>Ride post ' . $postid . ' is not published. Its current status is "' . $post_status . '"</p></div>';
			}
		}

		return '';
	}

}
