<script type="text/javascript">
    jQuery(document).ready(function($) { 
        function populate_maps_table(maps, can_edit) {
            var copylink = '<a title="Copy map title to clipboard." class="copy-btn"><i class="fa fa-clipboard"></i></a>';
            var header = '<table class="pwtc-mapdb-rwd-table">' +
                '<thead><tr><th>Title</th><th>Distance</th><th>Terrain</th>';
            if (can_edit) {
                header += '<th>Actions</th>';
            }
            header += '</tr></thead><tbody></tbody></table>';
            $('#pwtc-mapdb-maps-div').append(header);
            maps.forEach(function(item) {
                var data = '<tr postid="' + item.ID + '">' +
                '<td><span>Title</span>' + copylink + ' ' + item.media + item.title + '</a></td>' +
                '<td><span>Distance</span>' + item.distance + '</td>' +
                '<td><span>Terrain</span>' + item.terrain + '</td>';
                if (can_edit) {
                    data += '<td><span>Actions</span>' + item.edit + '</td>'
                }
                data += '</tr>';
                $('#pwtc-mapdb-maps-div table tbody').append(data);    
            });
            $('#pwtc-mapdb-maps-div table .copy-btn').on('click', function(evt) {
                //var title = $(this).parent().parent().find('td').first().find('span').first()[0];
                var title = $(this).parent().find('a').first().next()[0];
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
        }

        function create_paging_form(offset, count) {
            var limit = <?php echo $a['limit'] ?>;
            var pagenum = (offset/limit) + 1;
            var numpages = Math.ceil(count/limit);
            $('#pwtc-mapdb-maps-div').append(
                '<form class="page-frm">' +
                '<input class="prev-btn dark button" style="margin: 0" type="button" value="< Prev"/>' +
                '<span style="margin: 0 10px">Page ' + pagenum + ' of ' + numpages + '</span>' +
                '<input class="next-btn dark button" style="margin: 0" type="button" value="Next >"/>' +
                '<span class="page-msg" style="margin: 0 10px"></span>' +
                '<input name="offset" type="hidden" value="' + offset + '"/>' +
                '<input name="count" type="hidden" value="' + count + '"/>' +
                '</form>'
            );
            $('#pwtc-mapdb-maps-div .page-frm .prev-btn').on('click', function(evt) {
                evt.preventDefault();
                load_maps_table('prev');
            });
            if (pagenum == 1) {
                $('#pwtc-mapdb-maps-div .page-frm .prev-btn').attr("disabled", "disabled");
            }
            else {
                $('#pwtc-mapdb-maps-div .page-frm .prev-btn').removeAttr("disabled");
            }
            $('#pwtc-mapdb-maps-div .page-frm .next-btn').on('click', function(evt) {
                evt.preventDefault();
                load_maps_table('next');
            });
            if (pagenum == numpages) {
                $('#pwtc-mapdb-maps-div .page-frm .next-btn').attr("disabled", "disabled");
            }
            else {
                $('#pwtc-mapdb-maps-div .page-frm .next-btn').removeAttr("disabled");
            }
        }

        function lookup_maps_cb(response) {
            var res = JSON.parse(response);
            $('#pwtc-mapdb-maps-div').empty();
            if (res.error) {
                $('#pwtc-mapdb-maps-div').append(
                    '<div class="callout small alert"><p>' + res.error + '</p></div>');
            }
            else {
                if (res.message !== undefined) {
                    $('#pwtc-mapdb-maps-div').append(
                        '<div class="callout small warning"><p>' + res.message + '</p></div>');
                }
                if (res.maps.length > 0) {
                    populate_maps_table(res.maps, res.can_edit);
                    if (res.offset !== undefined) {
                        create_paging_form(res.offset, res.count);
                    }
                }
                else {
                    $('#pwtc-mapdb-maps-div').append(
                        '<div class="callout small warning"><p>No maps found.</p></div>');
                }
            }
            $('body').removeClass('pwtc-mapdb-waiting');
        }   

        function load_maps_table(mode) {
            var action = $('#pwtc-mapdb-search-div .search-frm').attr('action');
            var data = {
                'action': 'pwtc_mapdb_lookup_maps',
                'limit': <?php echo $a['limit'] ?>
            };
            if (mode != 'search') {
                data.title = $("#pwtc-mapdb-search-div .search-frm input[name='title_sav']").val();
                data.location = '';
                data.terrain = $("#pwtc-mapdb-search-div .search-frm input[name='terrain_sav']").val();
                data.distance = $("#pwtc-mapdb-search-div .search-frm input[name='distance_sav']").val();
                data.media = '0';
                var offset = $("#pwtc-mapdb-maps-div .page-frm input[name='offset']").val();
                var count = $("#pwtc-mapdb-maps-div .page-frm input[name='count']").val();
                data.offset = offset;
                data.count = count;
                if (mode == 'prev') {
                    data.prev = 1;
                }
                else if (mode == 'next') {
                    data.next = 1;						
                }
                $('#pwtc-mapdb-maps-div .page-frm .page-msg').html('<i class="fa fa-spinner fa-pulse"></i> Loading...');
            }
            else {
                data.title = $("#pwtc-mapdb-search-div .search-frm input[name='title']").val().trim();
                data.location = '';
                data.terrain = $('#pwtc-mapdb-search-div .search-frm .terrain').val();
                data.distance = $('#pwtc-mapdb-search-div .search-frm .distance').val();
                data.media = '0';
                $("#pwtc-mapdb-search-div .search-frm input[name='title_sav']").val(data.title);
                $("#pwtc-mapdb-search-div .search-frm input[name='terrain_sav']").val(data.terrain);
                $("#pwtc-mapdb-search-div .search-frm input[name='distance_sav']").val(data.distance);
                $('#pwtc-mapdb-maps-div').html('<i class="fa fa-spinner fa-pulse"></i> Loading...');
            }
            $('body').addClass('pwtc-mapdb-waiting');
            $.post(action, data, lookup_maps_cb); 
        }

        $('#pwtc-mapdb-search-div .search-frm').on('submit', function(evt) {
            evt.preventDefault();
            load_maps_table('search');
        });

        $('#pwtc-mapdb-search-div .search-frm .reset-btn').on('click', function(evt) {
            evt.preventDefault();
            $("#pwtc-mapdb-search-div .search-frm input[type='text']").val(''); 
            $('#pwtc-mapdb-search-div .search-frm select').val('0');
            $('#pwtc-mapdb-maps-div').empty();
            load_maps_table('search');
        });

        load_maps_table('search');
    });
</script>

<div id='pwtc-mapdb-search-div'>
<ul class="accordion" data-accordion data-allow-all-closed="true">
    <li class="accordion-item" data-accordion-item>
        <a href="#" class="accordion-title"><i class="fa fa-search"></i> Click Here To Search</a>
        <div class="accordion-content" data-tab-content>
            <form class="search-frm" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
            <input type="hidden" name="title_sav" value=""/>
            <input type="hidden" name="distance_sav" value=""/>
            <input type="hidden" name="terrain_sav" value=""/>
            <div>
            <div class="row">
                <div class="small-12 medium-4 columns">
                    <label>Title
                        <input type="text" name="title"/>
                    </label>
                </div>
                <div class="small-12 medium-4 columns">
                    <label>Distance
                        <select class="distance">
                            <option value="0" selected>Any</option> 
                            <option value="1">0-25 miles</option>
                            <option value="2">25-50 miles</option>
                            <option value="3">50-75 miles</option>
                            <option value="4">75-100 miles</option>
                            <option value="5">&gt; 100 miles</option>
                        </select>		
                    </label>
                </div>
                <div class="small-12 medium-4 columns">
                    <label>Terrain
                        <select class="terrain">
                            <option value="0" selected>Any</option> 
                            <option value="a">A (flat)</option>
                            <option value="b">B (gently rolling)</option>
                            <option value="c">C (short steep hills)</option>
                            <option value="d">D (longer hills)</option>
                            <option value="e">E (mountainous)</option>
                        </select>
                    </label>
                </div>
            </div>
            <div class="row column">
                <input class="accent button" type="submit" value="Search"/>
                <input class="reset-btn accent button" type="button" value="Reset"/>
            </div>
            </div>
            </form>
        </div>
    </li>
</ul>
</div>
<div id="pwtc-mapdb-maps-div"></div>
<?php 