<script type="text/javascript">
    jQuery(document).ready(function($) { 

        $('#pwtc-mapdb-signup-rides-view-div .load-more-frm').on('submit', function(evt) {
            $('#pwtc-mapdb-signup-rides-view-div .load-more-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
            $('#pwtc-mapdb-signup-rides-view-div button[type="submit"]').prop('disabled',true);
        });

    });
</script>			
<div id="pwtc-mapdb-signup-rides-view-div">
    <?php if ($query->have_posts()) { 
    $total = $query->found_posts;
    $is_more = ($limit > 0) && ($total > ($offset + $limit));
    ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Start Time</th><th>Ride Title</th><th>1st Leader</th><th>Sign-up Mode</th><th>Status</th></tr></thead>
        <tbody>
    <?php
    while ($query->have_posts()) {
        $query->the_post();
        $postid = get_the_ID();
        $title = esc_html(get_the_title());
        $leaders = PwtcMapdb::get_leader_userids($postid);
        if (count($leaders)) {
            $user_info = get_userdata($leaders[0]);
            if ($user_info) {
                $leader = $user_info->first_name . ' ' . $user_info->last_name;	
            }
            else {
                $leader = 'Unknown';
            }
        }
        else {
            $leader = '';
        }
        $mode = self::get_signup_mode($postid);
        $cutoff = self::get_signup_cutoff($postid);
        $cutoff_date = self::get_signup_cutoff_time($postid, $mode, $cutoff);
        if (self::get_signup_locked($postid)) {
            $status = 'closed';
            $ride_start = PwtcMapdb::get_ride_start_time($postid);
            $limit_date = self::get_log_mileage_lookback_limit();
            if ($ride_start < $limit_date) {
                $status = 'archived';
            }
            else {
                $results = PwtcMileage_DB::fetch_ride_by_post_id($postid);
                if (count($results) > 0) {
                    $status = 'mileage logged';
                }
            }
        }
        else if ($now > $cutoff_date) {
            $status = 'ready to close';
        }
        else {
            $status = 'open';
        }
        $view_link = esc_url(get_the_permalink());
        $start = PwtcMapdb::get_ride_start_time($postid);
        $start_date = $start->format('m/d/Y g:ia');
    ?>
        <tr>
            <td><span>Start Time</span><?php echo $start_date; ?></td>
            <td><span>Ride Title</span><a href="<?php echo $view_link; ?>"><?php echo $title; ?></a></td>
            <td><span>1st Leader</span><?php echo $leader; ?></td>
            <td><span>Sign-up Mode</span><?php echo $mode; ?></td>
            <td><span>Status</span><?php echo $status; ?></td>
        </tr>
    <?php
    }
    wp_reset_postdata();
    ?>
        </tbody>
    </table>
    <p class="help-text">These scheduled rides <?php echo $addendum; ?> have online sign-up enabled.</p>
    <?php if ($is_more) { ?>
    <form class="load-more-frm" method="POST">
        <input type="hidden" name="offset" value="<?php echo $offset + $limit; ?>">
        <div class="row column errmsg"></div>
        <div class="row column clearfix">
            <button class="dark button float-left" type="submit">Show Next <?php echo $limit; ?> Rides</button>
            <label class="float-right">Remaining rides: <?php echo ($total - ($offset + $limit)); ?></label>
        </div>
    </form>
    <?php } ?>
    <?php } else { ?>
    <div class="callout small"><p>No scheduled rides <?php echo $addendum; ?> with online sign-up enabled were found.</p></div>
    <?php } ?>
</div>
<?php 
