<script type="text/javascript">
	jQuery(document).ready(function($) { 
	});
</script>			
<div id="pwtc-mapdb-manage-rides-div">
    <p>You, <?php echo $author_name; ?>, are the author of the following draft and pending rides:</p>
<?php if ($query->have_posts()) { ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Start Time</th><th>Ride Title</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
    <?php
    while ($query->have_posts()) {
        $query->the_post();
        $postid = get_the_ID();
        $title = esc_html(get_the_title());
        $status = get_post_status();
        $view_link = esc_url(get_the_permalink());
        $edit_link = esc_url('/ride-edit-fields/?post='.$postid.'&return='.$return_uri);
        $delete_link = esc_url('/ride-delete-page/?post='.$postid.'&return='.$return_uri);
	$subject = 'Ride Submitted for Review';
        $body = 'Ride URL: ' . urlencode(get_the_permalink());
        $notify_link = esc_url('mailto:roadcaptain@portlandbicyclingclub.com?subject='.$subject.'&body='.$body);
        $start = PwtcMapdb::get_ride_start_time($postid);
        $start_date = '';
        if ($start) {
            $start_date = $start->format('m/d/Y g:ia');
        }
    ?>
        <tr>
            <td><span>Start Time</span><?php echo $start_date; ?></td>
            <td><span>Ride Title</span><?php echo $title; ?></td>
            <td><span>Status</span><?php echo $status; ?></td>
            <td><span>Actions</span>
                <?php if (user_can($current_user,'edit_published_rides')) { ?>
                <a href="<?php echo $view_link; ?>">Preview</a>
                <?php } ?>
                <?php if ($status == 'draft' or user_can($current_user,'edit_published_rides')) { ?>
                <a href="<?php echo $edit_link; ?>">Edit</a>
                <?php } ?>
                <?php if ($status == 'draft') { ?>
                <a href="<?php echo $delete_link; ?>">Delete</a>
                <?php } ?>
		<?php if ($status == 'pending') { ?>
                <a href="<?php echo $notify_link; ?>">Notify</a>
                <?php } ?>
            </td>	
        </tr>
    <?php
    }
    wp_reset_postdata();
    ?>
        </tbody>
    </table>
    <?php } else { ?>
    <div class="callout small"><p>No draft or pending rides found.</p></div>
    <?php } ?>
</div>
<?php 
