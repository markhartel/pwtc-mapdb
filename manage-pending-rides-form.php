<script type="text/javascript">
	jQuery(document).ready(function($) { 
	});
</script>			
<div id="pwtc-mapdb-manage-pending-rides-div">
    <?php if ($query->have_posts()) { ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Start Time</th><th>Ride Title</th><th>Author</th><th>Actions</th></tr></thead>
        <tbody>
    <?php
    while ($query->have_posts()) {
        $query->the_post();
        $postid = get_the_ID();
        $title = esc_html(get_the_title());
        $author = get_the_author_meta('ID');
        $user_info = get_userdata($author);
        if ($user_info) {
            $author_name = $user_info->first_name . ' ' . $user_info->last_name;
	    $author_email = $user_info->user_email;
        }
        else {
            $author_name = 'Unknown';
	    $author_email = '';
        }
        $view_link = esc_url(get_the_permalink());
        $edit_link = self::edit_ride_link($postid, $return_uri);
        $start = PwtcMapdb::get_ride_start_time($postid);
        $start_date = $start->format('m/d/Y g:ia');
    ?>
        <tr>
            <td><span>Start Time</span><?php echo $start_date; ?></td>
            <td><span>Ride Title</span><?php echo $title; ?></td>
            <td><span>Author</span><?php echo $author_name; ?></td>
            <td><span>Actions</span>
                <a href="<?php echo $view_link; ?>">Preview</a>
                <a href="<?php echo $edit_link; ?>">Edit</a>
            </td>	
        </tr>
    <?php
    }
    wp_reset_postdata();
    ?>
        </tbody>
    </table>
    <?php } else { ?>
    <div class="callout small"><p>No pending rides found.</p></div>
    <?php } ?>
</div>
<?php 
