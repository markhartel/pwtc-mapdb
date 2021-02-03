<script type="text/javascript">
	jQuery(document).ready(function($) { 
	});
</script>			
<div id="pwtc-mapdb-manage-pending-rides-div">
    <h3>Pending Rides - Ready for Review</h3>
    <?php if ($query->have_posts()) { ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Ride Title</th><th>Author</th><th>Actions</th></tr></thead>
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
        }
        else {
            $author_name = 'Unknown';
        }
        $edit_link = esc_url('/ride-edit-fields/?post='.$postid);
    ?>
        <tr>
            <td><span>Ride Title</span><?php echo $title; ?></td>
            <td><span>Author</span><?php echo $author_name; ?></td>
            <td><span>Actions</span>
                <a href="<?php echo $edit_link; ?>" target="_blank" rel="opener">Edit</a>
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