<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function show_warning(msg) {
            $('#pwtc-mapdb-manage-published-maps-div .search-frm .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function show_waiting() {
            $('#pwtc-mapdb-manage-published-maps-div .search-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        $('#pwtc-mapdb-manage-published-maps-div .load-more-frm').on('submit', function(evt) {
            $('#pwtc-mapdb-manage-published-maps-div .load-more-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
            //$('#pwtc-mapdb-manage-published-maps-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-manage-published-maps-div .search-frm').on('submit', function(evt) {
            show_waiting();
            $('#pwtc-mapdb-manage-published-maps-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-manage-published-maps-div .search-frm a').on('click', function(evt) {
            $('#pwtc-mapdb-manage-published-maps-div .search-frm input[name="map_title"]').val('');
        });

    });
</script>			
<div id="pwtc-mapdb-manage-published-maps-div">
    <ul class="accordion" data-accordion data-allow-all-closed="true">
        <li class="accordion-item" data-accordion-item>
            <a href="#" class="accordion-title">Search Map Library...</a>
            <div class="accordion-content" data-tab-content>
                <form class="search-frm" method="POST" novalidate>
                    <input type="hidden" name="offset" value="0">
                    <div class="row">
                        <div class="small-12 medium-6 columns">
                            <label>Map Title 
                                <input type="text" name="map_title" value="<?php echo $map_title; ?>">
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
        <p>There were more maps found than can be shown on the page, use the <em>Search Map Library</em> section to narrow your search.</p>
    </div>
    <?php } ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Map Title</th><th>Type</th><th>Distance</th><th>Terrain</th><th>Actions</th></tr></thead>
        <tbody>
    <?php
    while ($query->have_posts()) {
        $query->the_post();
        $postid = get_the_ID();
        $title = esc_html(get_the_title());
        $type = PwtcMapdb::get_map_link($postid);
        $d = get_field(PwtcMapdb::LENGTH_FIELD, $postid);
        $max_d = get_field(PwtcMapdb::MAX_LENGTH_FIELD, $postid);
        $distance = PwtcMapdb::build_distance_str($d, $max_d);
        $terrain = PwtcMapdb::build_terrain_str(get_field(PwtcMapdb::TERRAIN_FIELD, $postid));
        $view_link = esc_url(get_the_permalink());
        $edit_link = self::edit_map_link($postid, $return_uri);
        $delete_link = self::delete_map_link($postid, $return_uri);
    ?>
        <tr>
            <td><span>Map Title</span><?php echo $title; ?></td>
            <td><span>Type</span><?php echo $type; ?></td>
            <td><span>Distance</span><?php echo $distance; ?></td>
            <td><span>Terrain</span><?php echo $terrain; ?></td>
            <td><span>Actions</span>
                <a href="<?php echo $view_link; ?>">View</a>
                <?php if ($is_road_captain) { ?>
                <a href="<?php echo $edit_link; ?>">Edit</a>
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
    <?php if ($is_more or $is_prev) { ?>
    <form class="load-more-frm" method="POST">
        <input type="hidden" name="map_title" value="<?php echo $map_title; ?>">
        <div class="row column errmsg"></div>
        <div class="row column clearfix">
            <div class="button-group float-left">
            <?php if ($is_prev) { ?>
                <button class="dark button" type="submit" name="offset" value="<?php echo $offset - $limit; ?>">Show Previous <?php echo $limit; ?> Maps</button>
            <?php } ?>
            <?php if ($is_more) { ?>
                <button class="dark button" type="submit" name="offset" value="<?php echo $offset + $limit; ?>">Show Next <?php echo $limit; ?> Maps</button>
            <?php } ?>
            </div>
            <?php if ($is_more) { ?>
            <label class="float-right">Remaining maps: <?php echo ($total - ($offset + $limit)); ?></label>
            <?php } ?>
        </div>
    </form>
    <?php } ?>
    <?php } else { ?>
    <div class="callout small"><p>No published maps found, use the <em>Search Map Library</em> section to broaden your search.</p></div>
    <?php } ?>
</div>
<?php 