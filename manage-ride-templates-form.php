<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function show_warning(msg) {
            $('#pwtc-mapdb-manage-templates-div .search-frm .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function show_waiting() {
            $('#pwtc-mapdb-manage-templates-div .search-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        $('#pwtc-mapdb-manage-templates-div .load-more-frm').on('submit', function(evt) {
            $('#pwtc-mapdb-manage-templates-div .load-more-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
            //$('#pwtc-mapdb-manage-templates-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-manage-templates-div .search-frm').on('submit', function(evt) {
            show_waiting();
            $('#pwtc-mapdb-manage-templates-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-manage-templates-div .search-frm a').on('click', function(evt) {
            $('#pwtc-mapdb-manage-templates-div .search-frm input[name="ride_title"]').val('');
            $('#pwtc-mapdb-manage-templates-div .search-frm select[name="ride_status"]').val('all');
            $('#pwtc-mapdb-manage-templates-div .search-frm select[name="ride_leader"]').val('anyone');
        });
        
        $('#pwtc-mapdb-manage-templates-div .sort-frm input[name="sort_by"]').change(function() {
            $('#pwtc-mapdb-manage-templates-div .sort-frm').submit();
            $('#pwtc-mapdb-manage-templates-div .sort-frm span').html('<i class="fa fa-spinner fa-pulse waiting"></i> please wait...');
        });

    });
</script>			
<div id="pwtc-mapdb-manage-templates-div">
<?php if ($is_road_captain) { ?>
    <div class="row column">
        <form class="sort-frm" method="POST" novalidate>
            <input type="hidden" name="ride_status" value="<?php echo $ride_status; ?>">
            <input type="hidden" name="ride_title" value="<?php echo $ride_title; ?>">
            <input type="hidden" name="ride_leader" value="<?php echo $ride_leader; ?>">
            <input type="hidden" name="offset" value="0">
            <fieldset class="fieldset">
                <legend>Sort ride templates by</legend>
                <input type="radio" name="sort_by" value="date" id="sort-by-date" <?php echo $sort_by == 'date' ? 'checked': ''; ?>><label for="sort-by-date">Post Date</label>
                <input type="radio" name="sort_by" value="title" id="sort-by-title" <?php echo $sort_by == 'title' ? 'checked': ''; ?>><label for="sort-by-title">Title</label>
                <span></span>
            </fieldset>
        </form>
    </div>
<?php } ?>
    <ul class="accordion" data-accordion data-allow-all-closed="true">
        <li class="accordion-item <?php if ($search_open) { ?>is-active<?php } ?>" data-accordion-item>
            <a href="#" class="accordion-title">Search Ride Templates...</a>
            <div class="accordion-content" data-tab-content>
                <form class="search-frm" method="POST">
                    <input type="hidden" name="offset" value="0">
                    <input type="hidden" name="sort_by" value="<?php echo $sort_by; ?>">
                    <div class="row">
                    <?php if ($is_road_captain) { ?>
                        <div class="small-12 medium-2 columns">
                            <label>Post Status
                                <select name="ride_status">
                                    <option value="all" <?php echo $ride_status == 'all' ? 'selected': ''; ?>>All</option>
                                    <option value="mine" <?php echo $ride_status == 'mine' ? 'selected': ''; ?>>Mine</option>
                                    <option value="publish" <?php echo $ride_status == 'publish' ? 'selected': ''; ?>>Published</option>
                                    <option value="pending" <?php echo $ride_status == 'pending' ? 'selected': ''; ?>>Pending Review</option>
                                    <option value="draft" <?php echo $ride_status == 'draft' ? 'selected': ''; ?>>Draft</option>
                                    <option value="trash" <?php echo $ride_status == 'trash' ? 'selected': ''; ?>>Trash</option>
                                </select>
                            </label>
                        </div>
                    <?php } ?>
                        <div class="small-12 medium-7 columns">
                            <label>Ride Title 
                                <input type="text" name="ride_title" value="<?php echo $ride_title; ?>">
                            </label>
                        </div>
                        <div class="small-12 medium-3 columns">
                            <label>Ride Leader
                                <select name="ride_leader">
                                    <option value="anyone" <?php echo $ride_leader == 'anyone' ? 'selected': ''; ?>>Anyone</option>
                                    <?php foreach ( $leaders as $leader ) { ?>
                                    <option value="<?php echo $leader->ID; ?>" <?php echo $ride_leader == $leader->ID ? 'selected': ''; ?>><?php echo $leader->first_name.' '.$leader->last_name; ?></option>
                                    <?php } ?>
                                </select>
                            </label>
                        </div>
                    </div>
                    <div class="row column errmsg"></div>
                    <div class="row column clearfix">
                        <button class="accent button float-left" type="submit">Submit</button>
                        <a class="dark button float-right">Reset</a>
                    </div>
                </form>
            </div>
        </li>
    </ul>
    <?php if ($query->have_posts()) { 
    $total = $query->found_posts;
    $warn = $total > $limit;
    $is_more = ($limit > 0) && ($total > ($offset + $limit));
    $is_prev = ($limit > 0) && ($offset > 0);
    ?>
    <?php if ($warn) { ?>
    <div class="callout small warning">
        <p>There were more templates found than can be shown on the page, use the <em>Search Ride Templates</em> section to narrow your search.</p>
    </div>
    <?php } ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Ride Title</th><th>1st Leader</th><th>Actions</th></tr></thead>
        <tbody>
    <?php
    while ($query->have_posts()) {
        $query->the_post();
        $postid = get_the_ID();
        $status = get_post_status();
        $title = esc_html(get_the_title());
        if ($ride_status == 'all' or $ride_status == 'mine') {
            if ($status == 'pending' or $status == 'draft') {
                $title .= ' <em>(' . $status . ')</em>';
            }
        }
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
        $view_link = esc_url(get_the_permalink());
        $edit_link = self::edit_template_link($postid, $return_uri);
        $copy_link = self::copy_template_link($postid, $return_uri);
        $delete_link = self::delete_template_link($postid, $return_uri);
        $sched_link = self::template_ride_link($postid, $return_uri);
    ?>
        <tr>
            <td><span>Ride Title</span><?php echo $title; ?></td>
            <td><span>1st Leader</span><?php echo $leader; ?></td>
            <td><span>Actions</span>
            <?php if ($status == 'publish') { ?>
                <a href="<?php echo $view_link; ?>">View</a>
            <?php } else if (user_can($current_user,'edit_published_rides') and ($status == 'draft' or $status == 'pending')) { ?> 
                <a href="<?php echo $view_link; ?>">Preview</a>
            <?php } ?>  
            <?php if ($is_road_captain or ($is_ride_leader and $allow_leaders)) { ?>
                <a href="<?php echo $sched_link; ?>">Schedule</a>
            <?php } ?> 
            <?php if ($status != 'trash' and $is_road_captain) { ?>
                <a href="<?php echo $edit_link; ?>">Edit</a>
                <a href="<?php echo $copy_link; ?>">Copy</a>
            <?php } ?>
            <?php if ($status != 'trash' and $is_road_captain) { ?>
                <a href="<?php echo $delete_link; ?>">Delete</a>
            <?php } else if ($status == 'trash' and $is_road_captain) { ?>
                <a href="<?php echo $delete_link; ?>">Restore</a>
            <?php } ?>
            </td>	
        </tr>
    <?php
    }
    wp_reset_postdata();
    ?>
        </tbody>
    </table>
    <?php if ($is_more or $is_prev) { ?>
    <form class="load-more-frm" method="POST">
        <input type="hidden" name="ride_title" value="<?php echo $ride_title; ?>">
        <input type="hidden" name="ride_status" value="<?php echo $ride_status; ?>">
        <input type="hidden" name="ride_leader" value="<?php echo $ride_leader; ?>">
        <input type="hidden" name="sort_by" value="<?php echo $sort_by; ?>">
        <div class="row column errmsg"></div>
        <div class="row column clearfix">
            <div class="button-group float-left">
            <?php if ($is_prev) { ?>
                <button class="dark button" type="submit" name="offset" value="<?php echo $offset - $limit; ?>">Show Previous <?php echo $limit; ?> Templates</button>
            <?php } ?>
            <?php if ($is_more) { ?>
                <button class="dark button" type="submit" name="offset" value="<?php echo $offset + $limit; ?>">Show Next <?php echo $limit; ?> Templates</button>
            <?php } ?>
            </div>
            <?php if ($is_more) { ?>
            <label class="float-right">Remaining templates: <?php echo ($total - ($offset + $limit)); ?></label>
            <?php } ?>
        </div>
    </form>
    <?php } ?>
    <?php } else { ?>
    <div class="callout small"><p>No ride templates found, use the <em>Search Ride Templates</em> section to broaden your search.</p></div>
    <?php } ?>
</div>
<?php 
