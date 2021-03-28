<script type="text/javascript">
    jQuery(document).ready(function($) { 
        
        $('#pwtc-mapdb-search-riders-div .load-more-frm').on('submit', function(evt) {
            $('#pwtc-mapdb-search-riders-div .load-more-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
            $('#pwtc-mapdb-search-riders-div button[type="submit"]').prop('disabled',true);
        });

    });
</script>			
<div id="pwtc-mapdb-search-riders-div">
<?php if (count($riders) > 0) { ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Emergency Contact</th></tr></thead>
        <tbody>
    <?php
    $is_more = ($limit > 0) && ($user_query->get_total() > ($offset + $limit));
    foreach ($riders as $rider) {
        $id = $rider->ID;
        $hidden = get_field('directory_excluded', 'user_'.$id);
        $name = $rider->first_name . ' ' . $rider->last_name;
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
                <td><span>Email</span><?php echo $email; ?></td>
                <td><span>Phone</span><?php echo $phone; ?></td>
                <td><span>Emergency Contact</span><?php echo $contact; ?></td>
            </tr>
    <?php } ?>
        </tbody>
    </table>
    <?php if ($is_more) { ?>
    <form class="load-more-frm" method="POST">
        <input type="hidden" name="offset" value="<?php echo $offset + $limit; ?>">
        <div class="row column errmsg"></div>
        <div class="row column clearfix">
            <button class="dark button float-left" type="submit">Load more riders...</button>
            <label class="float-right">Remaining riders: <?php echo ($user_query->get_total() - ($offset + $limit)); ?></label>
        </div>
    </form>
    <?php } ?>
<?php } else { ?>
<div class="callout small"><p>No riders found.</p></div>
<?php } ?>
</div>
<?php 
