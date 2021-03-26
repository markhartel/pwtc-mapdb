<script type="text/javascript">
    jQuery(document).ready(function($) { 
    });
</script>			
<div id="pwtc-mapdb-search-riders-div">
<?php if (count($riders) > 0) { ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Emergency Contact</th></tr></thead>
        <tbody>
    <?php
    foreach ($riders as $rider) {
        $id = $rider->ID;
        $name = $rider->first_name . ' ' . $rider->last_name;
        $email = '';
        $phone = '';
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
<?php } else { ?>
<div class="callout small"><p>No riders found.</p></div>
<?php } ?>
</div>
<?php 