<?php if (!empty($return)) { ?>
<ul class="breadcrumbs">
<?php if (strpos($return, 'create-ride-from-template')!==false) { ?>
    <li><a href="/submit-rides-for-review">Submit Rides for Review</a></li>
    <li><a href="<?php echo $return; ?>">Create Ride from Template</a></li>
<?php } else if (strpos($return, 'create-ride-from-calendar')!==false) { ?>
    <li><a href="/submit-rides-for-review">Submit Rides for Review</a></li>
    <li><a href="<?php echo $return; ?>">Create Ride from Calendar</a></li>
<?php } else if (strpos($return, 'submit-rides-for-review')!==false) { ?>
    <li><a href="<?php echo $return; ?>">Submit Rides for Review</a></li>
<?php } else if (strpos($return, 'review-pending-rides')!==false) { ?>
    <li><a href="<?php echo $return; ?>">Review Pending Rides</a></li>
<?php } ?>
    <li><?php echo esc_html(get_the_title()); ?></li>
</ul>
<?php } ?>
<?php 