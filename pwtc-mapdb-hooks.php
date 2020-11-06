<?php

function pwtc_mapdb_get_signup() {
    $postid = get_the_ID();
    $signup_mode = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE, true);
    if (!$signup_mode) {
        PwtcMapdb::init_online_signup($postid);
        $signup_mode = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE, true);
        if (!$signup_mode) {
            $signup_mode = 'no';
        }
    }
    $members_only = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MEMBERS_ONLY, true);
    $signup_locked = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_LOCKED, true);

    $result['view_signup_url'] = '/ride-view-signups/?post='.$postid;
    $result['edit_ride_url'] = '/ride-edit-fields/?post='.$postid;
    $result['copy_ride_url'] = '/ride-edit-fields/?post='.$postid.'&action=copy';

    if ($signup_mode == 'no' or $signup_locked) {
        $result['ride_signup_msg'] = false;
        $result['ride_signup_url'] = false;
    }
    else {
        if (wp_get_current_user()->ID != 0) {
            $result['ride_signup_url'] = '/ride-online-signup/?post='.$postid;
            if ($signup_mode == 'paperless') {
                $result['ride_signup_msg'] = 'You <em>must</em> sign up online to attend this ride.';
            }
            else {
                $result['ride_signup_msg'] = 'Online sign up is available for this ride.';
            }
        }
        else if (!$members_only) {
            $result['ride_signup_url'] = '/ride-nonmember-signup/?post='.$postid;
            if ($signup_mode == 'paperless') {
                $result['ride_signup_msg'] = 'You <em>must</em> sign up online to attend this ride.';
            }
            else {
                $result['ride_signup_msg'] = 'Online sign up is available for this ride.';
            }
        }
        else {
            $result['ride_signup_url'] = false;
            $result['ride_signup_msg'] = 'Only club members may attend this ride. Members must first log in <a href="/wp-login.php">here</a> to sign up.';
        }
    }

    return $result;
}
