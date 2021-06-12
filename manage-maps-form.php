<script type="text/javascript">
	jQuery(document).ready(function($) { 
	});
</script>			
<div id="pwtc-mapdb-manage-maps-div">
<?php if ($query->have_posts()) { ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Map Title</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
    <?php
    while ($query->have_posts()) {
        $query->the_post();
        $postid = get_the_ID();
        $title = esc_html(get_the_title());
        $status = get_post_status();
        $view_link = esc_url(get_the_permalink());
        $edit_link = self::edit_map_link($postid, $return_uri);
        $delete_link = self::delete_map_link($postid, $return_uri);
    ?>
        <tr>
            <td><span>Map Title</span><?php echo $title; ?></td>
            <td><span>Status</span><?php echo $status; ?></td>
            <td><span>Actions</span>
                <?php if (user_can($current_user,'edit_published_rides')) { ?>
                <a href="<?php echo $view_link; ?>">Preview</a>
                <?php } ?>
                <?php if ($status == 'draft' or $is_road_captain) { ?>
                <a href="<?php echo $edit_link; ?>">Edit</a>
                <?php } ?>
                <?php if ($status == 'draft') { ?>
                <a href="<?php echo $delete_link; ?>">Delete</a>
                <?php } ?>
            </td>	
        </tr>
    <?php
    }
    wp_reset_postdata();
    ?>
        </tbody>
    </table>
    <p class="help-text">These route maps were authored by <?php echo $author_name; ?>.</p>
    <?php } else { ?>
    <div class="callout small"><p>No draft<?php if ($allow_leaders) { ?> or pending<?php } ?> route maps authored by <?php echo $author_name; ?> found.</p></div>
    <?php } ?>
</div>
<?php 