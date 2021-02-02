<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function show_warning(msg) {
            $('#pwtc-mapdb-manage-templates-div .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function show_waiting() {
            $('#pwtc-mapdb-manage-templates-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        $('#pwtc-mapdb-manage-templates-div form').on('submit', function(evt) {
            show_waiting();
            $('#pwtc-mapdb-manage-rides-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-manage-templates-div form a').on('click', function(evt) {
            $('#pwtc-mapdb-manage-templates-div input[name="ride_title"]').val('');
            $('#pwtc-mapdb-manage-templates-div form').submit();
        });

    });
</script>			
<div id="pwtc-mapdb-manage-templates-div">
    <h3>Ride Templates</h3>		
    <ul class="accordion" data-accordion data-allow-all-closed="true">
        <li class="accordion-item" data-accordion-item>
            <a href="#" class="accordion-title">Click Here to Search</a>
            <div class="accordion-content" data-tab-content>
                <form method="POST">
                    <div class="row">
                        <div class="small-12 medium-6 columns">
                            <label>Ride Title 
                                <input type="text" name="ride_title" value="<?php echo $ride_title; ?>">
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
        $copy_link = esc_url('/ride-edit-fields/?post='.$postid.'&action=template');
    ?>
        <tr>
            <td><span>Ride Title</span><?php echo $title; ?></td>
            <td><span>1st Leader</span><?php echo $leader; ?></td>
            <td><span>Actions</span>
                <?php if ($is_captain or ($is_leader and $allow_leaders)) { ?>
                <a href="<?php echo $copy_link; ?>" target="_blank" rel="opener">Schedule</a>
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
    <div class="callout small"><p>No ride templates found.</p></div>
    <?php } ?>
</div>
<?php 
