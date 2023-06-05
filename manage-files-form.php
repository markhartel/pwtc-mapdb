<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function show_warning(msg) {
            $('#pwtc-mapdb-manage-files-div .search-frm .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function show_waiting() {
            $('#pwtc-mapdb-manage-files-div .search-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        $('#pwtc-mapdb-manage-files-div .load-more-frm').on('submit', function(evt) {
            $('#pwtc-mapdb-manage-files-div .load-more-frm .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
            //$('#pwtc-mapdb-manage-files-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-manage-files-div .search-frm').on('submit', function(evt) {
            show_waiting();
            $('#pwtc-mapdb-manage-files-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-manage-files-div .search-frm a').on('click', function(evt) {
            $('#pwtc-mapdb-manage-files-div .search-frm input[name="file_title"]').val('');
        });
        
        $('#pwtc-mapdb-manage-files-div table .clipboard-btn').on('click', function(evt) {
            var title = $(this).parent()[0];
            if (window.getSelection().rangeCount > 0) window.getSelection().removeAllRanges();
            var range = document.createRange();  
            range.selectNode(title);  
            window.getSelection().addRange(range);  
            try {  
                var successful = document.execCommand('copy');  
                var msg = successful ? 'successful' : 'unsuccessful';  
                console.log('Copy title command was ' + msg);  
            } catch(err) {  
                console.log('Oops, unable to copy');  
            }  
            window.getSelection().removeAllRanges();  
        });
        
        $('#pwtc-mapdb-manage-files-div .sort-frm input[name="sort_by"]').change(function() {
            $('#pwtc-mapdb-manage-files-div .sort-frm').submit();
            $('#pwtc-mapdb-manage-files-div .sort-frm span').html('<i class="fa fa-spinner fa-pulse waiting"></i> please wait...');
        });

    });
</script>			
<div id="pwtc-mapdb-manage-files-div">
    <div class="row column">
        <form class="sort-frm" method="POST" novalidate>
            <input type="hidden" name="file_title" value="<?php echo stripslashes($file_title); ?>">
            <input type="hidden" name="offset" value="0">
            <fieldset class="fieldset">
                <legend>Sort file attachments by</legend>
                <input type="radio" name="sort_by" value="title" id="sort-by-title" <?php echo $sort_by == 'title' ? 'checked': ''; ?>><label for="sort-by-title">Title</label>
                <input type="radio" name="sort_by" value="date" id="sort-by-date" <?php echo $sort_by == 'date' ? 'checked': ''; ?>><label for="sort-by-date">Post Date</label>
                <span></span>
            </fieldset>
        </form>
    </div>
    <ul class="accordion" data-accordion data-allow-all-closed="true">
        <li class="accordion-item <?php if ($search_open) { ?>is-active<?php } ?>" data-accordion-item>
            <a href="#" class="accordion-title">Search File Attachments...</a>
            <div class="accordion-content" data-tab-content>
                <form class="search-frm" method="POST" novalidate>
                    <input type="hidden" name="offset" value="0">
                    <input type="hidden" name="sort_by" value="<?php echo $sort_by; ?>">
                    <div class="row">
                        <div class="small-12 medium-12 columns">
                            <label>Attachment Title 
                                <input type="text" name="file_title" value="<?php echo stripslashes($file_title); ?>">
                            </label>
                        </div>
                    </div>
                    <div class="row column errmsg"></div>
                    <div class="row column clearfix">
                        <button class="dark button float-left" type="submit">Search</button>
                        <a class="dark button float-right">Reset</a>
                    </div>
                </form>
            </div>
        </li>
    </ul>
    <?php if ($query->have_posts()) { 
    $total = $query->found_posts;
    $warn = $total > $limit;
    ?>
    <?php if ($warn) { ?>
    <div class="callout small warning">
        <p>There were more attachments found than can be shown on the page, use the <em>Search File Attachments</em> section to narrow your search.</p>
    </div>
    <?php } ?>
    <table class="pwtc-mapdb-rwd-table">
        <thead><tr><th>Attachment Title</th><th>File URL</th><th>Actions</th></tr></thead>
        <tbody>
    <?php
    while ($query->have_posts()) {
        $query->the_post();
        $postid = get_the_ID();
        $title = esc_html(get_the_title());
        $url = wp_get_attachment_url($postid);
        $view_link = esc_url($url);
        $edit_link = self::edit_file_link($postid, $return_uri);
        $delete_link = self::delete_file_link($postid, $return_uri);
    ?>
        <tr>
            <td><span>Attachment Title</span><?php echo $title; ?></td>
            <td><span>File URL</span><?php echo $url; ?> <a class="clipboard-btn" title="Copy URL to clipboard."><i class="fa fa-clipboard"></i></a></td>
            <td><span>Actions</span>
                <a href="<?php echo $view_link; ?>">View</a>
                <a href="<?php echo $edit_link; ?>">Edit</a>
                <a href="<?php echo $delete_link; ?>">Delete</a>
            </td>
        </tr>
    <?php
    }
    wp_reset_postdata();
    ?>
        </tbody>
    </table>
    <?php if ($warn) { ?>
    <form class="load-more-frm" method="POST">
        <input type="hidden" name="file_title" value="<?php echo stripslashes($file_title); ?>">
        <input type="hidden" name="sort_by" value="<?php echo $sort_by; ?>">
        <div class="row column errmsg"></div>
        <?php echo PwtcMapdb::output_pagination_html($limit, $offset, $total); ?>
    </form>
    <?php } ?>
    <?php } else { ?>
    <div class="callout small"><p>No attachments found, use the <em>Search File Attachments</em> section to broaden your search.</p></div>
    <?php } ?>
</div>
<?php 
