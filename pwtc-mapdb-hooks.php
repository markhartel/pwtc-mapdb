<?php

function pwtc_mapdb_get_signup_mode() {
    $postid = get_the_ID();
    $signup_mode = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE, true);
    if (!$signup_mode) {
        PwtcMapdb::init_online_signup($postid);
        $signup_mode = get_post_meta($postid, PwtcMapdb::RIDE_SIGNUP_MODE, true);
        if (!$signup_mode) {
            $signup_mode = 'no';
        }
    }
    $result = ['mode' => $signup_mode];

    if ($signup_mode == 'paperless') {
        $result['message'] = 'Online signup is required to attend this ride:';
    }
    else if ($signup_mode == 'hardcopy') {
        $result['message'] = 'Online signup is available for this ride:';
    }
    else {
        $result['message'] = '';
    }

    if (wp_get_current_user()->ID != 0) {
        $result['signup_url'] = '/ride-online-signup/?post='.$postid;
    }
    else {
        $result['signup_url'] = '/ride-online-nonmember-signup/?post='.$postid;
    }

    $result['view_url'] = '/ride-view-signups/?post='.$postid;

    return $result;
}
