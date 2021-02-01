<div id="pwtc-mapdb-show-signups-div">
    <p>Hello <?php echo $rider_name; ?>, you are signed up for the following upcoming rides.</p>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Start Time</th><th>Ride Title</th></tr></thead>
        <tbody>
    <?php

    while ($query->have_posts()) {
        $query->the_post();
        $title = esc_html(get_the_title());
                $link = esc_url(get_the_permalink());
        $start = DateTime::createFromFormat('Y-m-d H:i:s', get_field('date'))->format('m/d/Y g:ia');
    ?>
        <tr>
            <td><span>Start Time</span><?php echo $start; ?></td>
            <td><span>Ride Title</span><a href="<?php echo $link; ?>"><?php echo $title; ?></a></td>	
        </tr>
    <?php
    }
    wp_reset_postdata();
    ?>
        </tbody>
    </table>
</div>
<?php 