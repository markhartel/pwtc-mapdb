<script type="text/javascript">
    jQuery(document).ready(function($) { 
        
        $('#pwtc-mapdb-search-riders-div .load-more-frm').on('submit', function(evt) {
            $('#pwtc-mapdb-search-riders-div .load-more-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
            //$('#pwtc-mapdb-search-riders-div button[type="submit"]').prop('disabled',true);
        });
        
        $('#pwtc-mapdb-search-riders-div .search-frm').on('submit', function(evt) {
            $('#pwtc-mapdb-search-riders-div .search-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
            $('#pwtc-mapdb-search-riders-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-search-riders-div .search-frm a').on('click', function(evt) {
            $('#pwtc-mapdb-search-riders-div .search-frm input[name="rider_name"]').val('');
            $('#pwtc-mapdb-search-riders-div .search-frm input[name="rider_id"]').val('');
        });

    });
</script>			
<div id="pwtc-mapdb-search-riders-div">
    <ul class="accordion" data-accordion data-allow-all-closed="true">
        <li class="accordion-item" data-accordion-item>
            <a href="#" class="accordion-title">Search Riders...</a>
            <div class="accordion-content" data-tab-content>
                <form class="search-frm" method="POST" novalidate>
                    <input type="hidden" name="offset" value="0">
                    <div class="row">
                        <div class="small-12 medium-6 columns">
                            <label>Rider Name 
                                <input type="text" name="rider_name" value="<?php echo stripslashes($rider_name); ?>">
                            </label>
                        </div>
                        <div class="small-12 medium-6 columns">
                            <label>Rider ID 
                                <input type="text" name="rider_id" value="<?php echo $rider_id; ?>">
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
<?php if (count($riders) > 0) { 
    $total = $user_query->get_total();
    $warn = $total > $limit;
?>
    <?php if ($warn) { ?>
    <div class="callout small warning">
        <p>There were more riders found than can be shown on the page, use the <em>Search Riders</em> section to narrow your search.</p>
    </div>
    <?php } ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Name</th><th>Rider ID</th><th>Email</th><th>Phone</th><th>Emergency Contact</th></tr></thead>
        <tbody>
    <?php
    foreach ($riders as $rider) {
        $id = $rider->ID;
        //$hidden = get_field('directory_excluded', 'user_'.$id);
        $hidden = false;
        $name = $rider->first_name . ' ' . $rider->last_name;
        $riderID = get_field('rider_number', 'user_'.$id);
        $email = '';
        if (!empty($rider->user_email)) {
            if ($hidden) {
                $email = '*****';
            }
            else {
                $email = '<a href="mailto:' . $rider->user_email . '">' . $rider->user_email . '</a>';
            }
        }
        $phone = '';
        if (!empty($rider->billing_phone)) {
            if ($hidden) {
                $phone = '*****';
            }
            else {
                $phone = '<a href="tel:' . pwtc_members_strip_phone_number($rider->billing_phone) . '">' . pwtc_members_format_phone_number($rider->billing_phone) . '</a>';
            }
        }
        $contact = PwtcMapdb_Signup::get_emergency_contact($id, true);
    ?>
            <tr>
                <td><span>Name</span><?php echo $name; ?></td>
                <td><span>Rider ID</span><?php echo $riderID; ?></td>
                <td><span>Email</span><?php echo $email; ?></td>
                <td><span>Phone</span><?php echo $phone; ?></td>
                <td><span>Emergency Contact</span><?php echo $contact; ?></td>
            </tr>
    <?php } ?>
        </tbody>
    </table>
    <?php if ($warn) { ?>
    <form class="load-more-frm" method="POST">
        <input type="hidden" name="rider_name" value="<?php echo stripslashes($rider_name); ?>">
        <input type="hidden" name="rider_id" value="<?php echo $rider_id; ?>">
        <div class="row column errmsg"></div>
        <?php echo PwtcMapdb::output_pagination_html($limit, $offset, $total); ?>
    </form>
    <?php } ?>
<?php } else { ?>
<div class="callout small"><p>No riders found, use the <em>Search Riders</em> section to broaden your search.</p></div>
<?php } ?>
</div>
<?php 
