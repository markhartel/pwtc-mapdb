<ul class="breadcrumbs">
<?php if ($allow_leaders) { ?>
    <li><a href="/ride-leader-info">Ride Leader Info</a></li>
    <?php if (strpos($return, 'submit-maps-for-review')!==false) { ?>
    <li><a href="<?php echo $return; ?>">Submit Maps for Review</a></li>
    <?php } else if (strpos($return, 'review-pending-maps')!==false) { ?>
    <li><a href="<?php echo $return; ?>">Review Pending Maps</a></li>
    <?php } ?>
<?php } else { ?>
    <?php if (strpos($return, 'submit-maps-for-library')!==false) { ?>
    <li><a href="<?php echo $return; ?>">Submit Maps for Library</a></li>
    <?php } ?>
<?php } ?>
    <li><?php echo esc_html(get_the_title()); ?></li>
</ul>
<?php 