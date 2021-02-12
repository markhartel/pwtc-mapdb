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
            $('#pwtc-mapdb-manage-templates-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-manage-templates-div .search-frm').on('submit', function(evt) {
            show_waiting();
            $('#pwtc-mapdb-manage-templates-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-manage-templates-div .search-frm a').on('click', function(evt) {
            $('#pwtc-mapdb-manage-templates-div .search-frm input[name="ride_title"]').val('');
            $('#pwtc-mapdb-manage-templates-div .search-frm select[name="ride_leader"]').val('anyone');
        });

    });
</script>			
<div id="pwtc-mapdb-manage-templates-div">
    <ul class="accordion" data-accordion data-allow-all-closed="true">
        <li class="accordion-item" data-accordion-item>
            <a href="#" class="accordion-title">Search Ride Templates...</a>
            <div class="accordion-content" data-tab-content>
                <form class="search-frm" method="POST">
                    <input type="hidden" name="offset" value="0">
                    <div class="row">
                        <div class="small-12 medium-10 columns">
                            <label>Ride Title 
                                <input type="text" name="ride_title" value="<?php echo $ride_title; ?>">
                            </label>
                        </div>
                        <div class="small-12 medium-2 columns">
                            <label>Ride Leader
                                <select name="ride_leader">
                                    <option value="anyone" <?php echo $ride_leader == 'anyone' ? 'selected': ''; ?>>Anyone</option>
                                    <option value="me"  <?php echo $ride_leader == 'me' ? 'selected': ''; ?>>Me Only</option>
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
    <?php if ($query->have_posts()) { ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Ride Title</th><th>1st Leader</th><th>Actions</th></tr></thead>
        <tbody>
    <?php
    $is_more = ($limit > 0) && ($query->found_posts > ($offset + $limit));
    //error_log('found_posts='.$query->found_posts);
    //error_log('limit='.$limit);
    //error_log('offset='.$offset);
    //error_log('is_more='.$is_more);
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
        $copy_link = esc_url('/ride-edit-fields/?post='.$postid.'&action=template&return='.$return_uri);
    ?>
        <tr>
            <td><span>Ride Title</span><?php echo $title; ?></td>
            <td><span>1st Leader</span><?php echo $leader; ?></td>
            <td><span>Actions</span>
                <?php if ($is_captain or ($is_leader and $allow_leaders)) { ?>
                <a href="<?php echo $copy_link; ?>">Copy</a>
                <?php } ?>
            </td>	
        </tr>
    <?php
    }
    wp_reset_postdata();
    ?>
        </tbody>
    </table>
    <?php if ($is_more) { ?>
    <form class="load-more-frm" method="POST">
        <input type="hidden" name="offset" value="<?php echo $offset + $limit; ?>">
        <input type="hidden" name="ride_title" value="<?php echo $ride_title; ?>">
        <input type="hidden" name="ride_leader" value="<?php echo $ride_leader; ?>">
        <div class="row column errmsg"></div>
        <div class="row column clearfix">
            <button class="dark button float-left" type="submit">Load more templates...</button>
            <label class="float-right">Remaining templates: <?php echo ($query->found_posts - ($offset + $limit)); ?></label>
        </div>
    </form>
    <?php } ?>
    <?php } else { ?>
    <div class="callout small"><p>No ride templates found.</p></div>
    <?php } ?>
</div>
<?php 