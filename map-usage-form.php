<div id="pwtc-mapdb-usage-map-div">
    <?php echo $return_to_map; ?>
    <div class="row column">
<?php
    if ($temmplate_query->have_posts() or $ride_query->have_posts()) {
?>
        <p>The route map "<?php echo $map_title; ?>" is used by the following ride templates and scheduled rides:
<?php
        $count = 0;
        while ($temmplate_query->have_posts()) {
            $temmplate_query->the_post();
            $title = esc_html(get_the_title());
            $view_link = esc_url(get_the_permalink());
?>
            <?php if ($count > 0) { ?>, <?php } ?><a href="<?php echo $view_link; ?>"><?php echo $title; ?></a>
<?php
            $count++;
        }
        wp_reset_postdata();
        while ($ride_query->have_posts()) {
            $ride_query->the_post();
            $title = esc_html(get_the_title());
            $view_link = esc_url(get_the_permalink());
?>
            <?php if ($count > 0) { ?>, <?php } ?><a href="<?php echo $view_link; ?>"><?php echo $title; ?></a>
<?php
            $count++;
        }
        wp_reset_postdata();
?>
        </p>
<?php
    } else {
?> 
        <p>The route map "<?php echo $map_title; ?>" is not used by any ride templates or scheduled rides.</p>
<?php       
    }
?>
    </div>
</div>
<?php 
