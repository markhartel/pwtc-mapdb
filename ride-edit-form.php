<style>
    .indicate-error {
        border-color: #900 !important;
        background-color: #FDD !important;
    }
    #pwtc-mapdb-edit-ride-div .maps-div div {
        margin: 10px; 
        padding: 10px; 
        border: 1px solid;
    }
    #pwtc-mapdb-edit-ride-div .maps-div div i {
        cursor: pointer;
    }
    #pwtc-mapdb-edit-ride-div .maps-div input {
        width: 150px;
        margin: 10px; 
        padding: 10px; 
        border: none;
    }
    #pwtc-mapdb-edit-ride-div .map-search-div table tr {
        cursor: pointer;
    }
    #pwtc-mapdb-edit-ride-div .map-search-div table tr[pending] {
        color: red;
    }
    #pwtc-mapdb-edit-ride-div .map-search-div table tr:hover {
        background-color: black !important;
        color: white !important;
    }
    #pwtc-mapdb-edit-ride-div .map-search-div table td {
        padding: 3px;
        vertical-align: top;
    }
    #pwtc-mapdb-edit-ride-div .map-search-div table tr:nth-child(odd) {
        background-color: #f2f2f2;
    }
    #pwtc-mapdb-edit-ride-div .leaders-div div {
        margin: 10px; 
        padding: 10px; 
        border: 1px solid;
    }
    #pwtc-mapdb-edit-ride-div .leaders-div div i {
        cursor: pointer;
    }
    #pwtc-mapdb-edit-ride-div .leaders-div input {
        width: 150px;
        margin: 10px; 
        padding: 10px; 
        border: none;
    }
    #pwtc-mapdb-edit-ride-div .leader-search-div ul {
        list-style-type: none;
    }
    #pwtc-mapdb-edit-ride-div .leader-search-div li {
        cursor: pointer;
    }
    #pwtc-mapdb-edit-ride-div .leader-search-div li:hover {
        font-weight: bold;
    }
    #pwtc-mapdb-edit-ride-div .start-locations-div table tr {
        cursor: pointer;
    }
    #pwtc-mapdb-edit-ride-div .start-locations-div table tr:hover {
        background-color: black !important;
        color: white !important;
    }
    #pwtc-mapdb-edit-ride-div .start-locations-div table td {
        padding: 3px;
        vertical-align: top;
    }
    #pwtc-mapdb-edit-ride-div .start-locations-div table tr:nth-child(odd) {
        background-color: #f2f2f2;
    }
    #pwtc-mapdb-edit-ride-div .find-location-div ul {
        list-style-type: none;
    }
    #pwtc-mapdb-edit-ride-div .find-location-div li {
        cursor: pointer;
    }
    #pwtc-mapdb-edit-ride-div .find-location-div li:hover {
        font-weight: bold;
    }
</style>
<script type="text/javascript">
    jQuery(document).ready(function($) { 
	    
	var leaderTimeoutID = 0;
        var leaderSentCount = 0;
        var leaderRecvCount = 0;
	    
	var mapTimeoutID = 0;
        var mapSentCount = 0;
        var mapRecvCount = 0;

        function decodeHtml(html) {
            return $('<div/>').html(html).text();
        }

        function show_warning(msg) {
            $('#pwtc-mapdb-edit-ride-div .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function show_waiting() {
            $('#pwtc-mapdb-edit-ride-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
        }
        
        function set_coord_string(lat, lng) {
    <?php if (!$set_coords) { ?>
            $('#pwtc-mapdb-edit-ride-div .coord-span').html('(' + lat + ', ' + lng + ')');          
    <?php } ?>
            $('#pwtc-mapdb-edit-ride-div .goolmap').show();
        }

        function clear_coord_string() {
    <?php if (!$set_coords) { ?>
            $('#pwtc-mapdb-edit-ride-div .coord-span').empty();          
    <?php } ?> 
            $('#pwtc-mapdb-edit-ride-div .goolmap').hide();           
        }

        function has_user_id(id) {
            id = Number(id);
            var found = false;
            $('#pwtc-mapdb-edit-ride-div .leaders-div div').each(function() {
                var userid = Number($(this).attr('userid'));
                if (userid == id) {
                    found = true;
                }
            });
            return found;
        }

        function has_map_id(id) {
            id = Number(id);
            var found = false;
            $('#pwtc-mapdb-edit-ride-div .maps-div div').each(function() {
                var mapid = Number($(this).attr('mapid'));
                if (mapid == id) {
                    found = true;
                }
            });	
            return found;			
        }
	    
	function remove_leader_event(evt) {
            is_dirty = true;
            $(this).parent().remove();
            $('#pwtc-mapdb-edit-ride-div .leader-search-div').hide();
            evt.stopPropagation();
        }

        function add_leader_event(evt) {
            var userid = $(this).attr('userid');
            if (!has_user_id(userid)) {
                var name = $(this).html();
                is_dirty = true;
                $('#pwtc-mapdb-edit-ride-div .leaders-div').removeClass('indicate-error');
                $('#pwtc-mapdb-edit-ride-div .leaders-div input').before('<div userid="' + userid + '"><i class="fa fa-times"></i> ' + name + '</div>');
                $('#pwtc-mapdb-edit-ride-div .leaders-div div[userid="' + userid + '"] .fa-times').on('click', remove_leader_event);
            }
            $('#pwtc-mapdb-edit-ride-div .leader-search-div').hide();
        }

        function leaders_lookup_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').html('<div class="callout small alert"><p>' + e.message + '</p></div>');
                return;
            }
	    if (res.error) {
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').html('<div class="callout small alert"><p>' + res.error + '</p></div>');
                return;
            }
	    if (res.count !== undefined) {
                if (res.count < leaderRecvCount) {
                    //console.log('response ' + res.count + ' discarded!');
                    return;
                }
                leaderRecvCount = res.count;
            }
            $('#pwtc-mapdb-edit-ride-div .leader-search-div').removeAttr('offset');
            if (res.users.length == 0 && res.offset == 0) {
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').empty();
            }
            else {
		if (res.offset == 0) {
                	$('#pwtc-mapdb-edit-ride-div .leader-search-div').empty();
                	$('#pwtc-mapdb-edit-ride-div .leader-search-div').append('<ul></ul>');
		}
		else {
                    $('#pwtc-mapdb-edit-ride-div .leader-search-div li .fa-spinner').parent().remove();
                }
                res.users.forEach(function(item) {
                    $('#pwtc-mapdb-edit-ride-div .leader-search-div ul').append(
                        '<li userid="' + item.userid + '">' + item.display_name + '</li>'); 
		    $('#pwtc-mapdb-edit-ride-div .leader-search-div li[userid="' + item.userid + '"]').on('click', add_leader_event);
                });
		if (res.more !== undefined) {
                    $('#pwtc-mapdb-edit-ride-div .leader-search-div').attr('offset', res.offset+10);
                }
		if (res.select !== undefined) {
                    $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').blur();
                    $('#pwtc-mapdb-edit-ride-div .leader-search-div ul li:first-child').trigger( 'click');
                }
            }
        }
	    
	function remove_map_event(evt) {
            is_dirty = true;
            $(this).parent().remove();
            $('#pwtc-mapdb-edit-ride-div .map-search-div').hide();
            evt.stopPropagation();
        }

        function add_map_event(evt) {
            var mapid = $(this).attr('mapid');
            if (!has_map_id(mapid)) {
                var title = $(this).find('td').first().html();
                is_dirty = true;
                $('#pwtc-mapdb-edit-ride-div .maps-div').removeClass('indicate-error');
                $('#pwtc-mapdb-edit-ride-div .maps-div input').before('<div mapid="' + mapid + '"><i class="fa fa-times"></i> ' + title + '</div>');
                $('#pwtc-mapdb-edit-ride-div .maps-div div[mapid="' + mapid + '"] .fa-times').on('click', remove_map_event);
                $('#pwtc-mapdb-edit-ride-div .maps-div div[mapid="' + mapid + '"] a').on('click', function(evt) {
                    evt.stopPropagation();
                });
            }
            $('#pwtc-mapdb-edit-ride-div .map-search-div').hide();
        }

        function maps_lookup_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                $('#pwtc-mapdb-edit-ride-div .map-search-div').html('<div class="callout small alert"><p>' + e.message + '</p></div>');
                return;
            }
            if (res.error) {
                $('#pwtc-mapdb-edit-ride-div .map-search-div').html('<div class="callout small alert"><p>' + res.error + '</p></div>');
                return;
            }
            if (res.count !== undefined) {
                if (res.count < mapRecvCount) {
                    //console.log('response ' + res.count + ' discarded!');
                    return;
                }
                mapRecvCount = res.count;
            }
            $('#pwtc-mapdb-edit-ride-div .map-search-div').removeAttr('offset');
            if (res.maps.length == 0 && res.offset == 0) {
                $('#pwtc-mapdb-edit-ride-div .map-search-div').empty();
            }
            else {
                if (res.offset == 0) {
                    $('#pwtc-mapdb-edit-ride-div .map-search-div').empty();
                    if (res.pending > 0) {
                        $('#pwtc-mapdb-edit-ride-div .map-search-div').append('<div class="callout small warning">The first ' + res.pending + ' maps (submitted by the ride&#39;s author) are pending review.</div>');
                    }
                    $('#pwtc-mapdb-edit-ride-div .map-search-div').append('<table></table>');
                }
                else {
                    $('#pwtc-mapdb-edit-ride-div .map-search-div tr td .fa-spinner').parent().parent().remove();
                }
                res.maps.forEach(function(item) {
                    var a = '';
                    if (item.type == 'file') {
                        a = ' <a title="Download ride route map file." href="' + item.href + '" target="_blank" download><i class="fa fa-download"></i></a>';
                    }
                    else if (item.type == 'link') {
                        a = ' <a title="Display online ride route map." href="' + item.href + '" target="_blank"><i class="fa fa-link"></i></a>';
                    }
                    else if (item.type == 'both') {
                        a = ' <a title="Download ride route map file." href="' + item.href + '" target="_blank" download><i class="fa fa-download"></i></a> <a title="Display online ride route map." href="' + item.href2 + '" target="_blank"><i class="fa fa-link"></i></a>';
                    }
                    var pending = '';
                    if (item.pending !== undefined) {
                        pending = 'pending';
                    }
                    $('#pwtc-mapdb-edit-ride-div .map-search-div table').append(
                        '<tr mapid="' + item.ID + '" ' + pending + '><td>' + item.title + a + '</td><td>' + item.distance + '</td><td>' + item.terrain + '</td></tr>');  
                    $('#pwtc-mapdb-edit-ride-div .map-search-div table tr[mapid="' + item.ID + '"]').on('click', add_map_event); 
                    $('#pwtc-mapdb-edit-ride-div .map-search-div table tr[mapid="' + item.ID + '"] a').on('click', function(evt) {
                        evt.stopPropagation();
                    }); 
                });
                if (res.more !== undefined) {
                    $('#pwtc-mapdb-edit-ride-div .map-search-div').attr('offset', res.offset+10);
                }
                if (res.select !== undefined) {
                    $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').blur();
                    $('#pwtc-mapdb-edit-ride-div .map-search-div table tr:first-child').trigger( 'click');
                }
            }
        }
        
        function fetch_route_maps(offset, select) {
            mapSentCount++;
            var searchstr = $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').val();
            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_fetch_maps',
                'limit': 10,
                'title': searchstr,
    <?php if ($show_submitted_maps) { ?>
                'author': <?php echo $author; ?>,
    <?php } ?>
                'offset': offset,
                'select': select,
                'count': mapSentCount
            };
            $.post(action, data, maps_lookup_cb);
            if (offset == 0) {
                $('#pwtc-mapdb-edit-ride-div .map-search-div').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> searching...</div>');
            }
            else {
                $('#pwtc-mapdb-edit-ride-div .map-search-div table').append('<tr><td><i class="fa fa-spinner fa-pulse"></i></td><td></td><td></td></tr>');
            }
        }

        function fetch_ride_leaders(offset, select) {
	    leaderSentCount++;
            var searchstr = $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').val();
            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_lookup_ride_leaders',
		'limit': 10,
                'search': searchstr,
                'offset': offset,
                'select': select,
                'count': leaderSentCount
            };
            $.post(action, data, leaders_lookup_cb);
            if (offset == 0) {
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> searching...</div>');
            }
            else {
                $('#pwtc-mapdb-edit-ride-div .leader-search-div ul').append('<li><i class="fa fa-spinner fa-pulse"></i></li>');
            }
        }
        
    <?php if ($edit_leader) { ?>

	$(document).on('focusin', function(evt) {
            //console.log('focusin detected on document');
            if (evt.fromLeaderEdit === undefined) {
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').hide();
            }
        });

        $('#pwtc-mapdb-edit-ride-div .leaders-div').on('focusin', function(evt) {
            //console.log('focusin detected on leaders-div');
            evt.fromLeaderEdit = 1;
        });

        $('#pwtc-mapdb-edit-ride-div .leader-search-div').on('focusin', function(evt) {
            //console.log('focusin detected on leader-search-div');
            evt.fromLeaderEdit = 1;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').on('focus', function() {
            var interval = 500;
            function callback(lastval) {
                var val = $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').val();
                //console.log('callback: val=' + val + ', lastval=' + lastval);
                if (val != lastval) {
                    fetch_ride_leaders(0, 0);
                    $('#pwtc-mapdb-edit-ride-div .leader-search-div').show();
                }
                leaderTimeoutID = setTimeout(callback, interval, val);
            };
            leaderTimeoutID = setTimeout(callback, interval, $(this).val());
        });  

        $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').on('blur', function(evt) {
            clearTimeout(leaderTimeoutID);
            $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').val('');
        });

        $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').on('click', function(evt) {
            if ($('#pwtc-mapdb-edit-ride-div .leader-search-div').is(':hidden')) {
                fetch_ride_leaders(0, 0);
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').show();
            }
            evt.stopPropagation();		
        });

        $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                fetch_ride_leaders(0, 1);
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').show();
            } 
        });	

        $('#pwtc-mapdb-edit-ride-div .leaders-div').on('click', function(evt) { 
            if ($('#pwtc-mapdb-edit-ride-div .leader-search-div').is(':hidden')) {
                fetch_ride_leaders(0, 0);
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').show();
            }
            $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').focus();
        });      

        $('#pwtc-mapdb-edit-ride-div .leaders-div .fa-times').on('click', remove_leader_event);
	    
	$('#pwtc-mapdb-edit-ride-div .leader-search-div').on('scroll', function() {            
            if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
                var offset = $(this).attr('offset');
                if (offset) {
                    fetch_ride_leaders(parseInt(offset, 10), 0);
                    $(this).removeAttr('offset');
                }
            }
        });

    <?php } ?>
        
        $(document).on('focusin', function(evt) {
            //console.log('focusin detected on document');
            if (evt.fromMapEdit === undefined) {
                $('#pwtc-mapdb-edit-ride-div .map-search-div').hide();
            }
        });

        $('#pwtc-mapdb-edit-ride-div .maps-div').on('focusin', function(evt) {
            //console.log('focusin detected on map-div');
            evt.fromMapEdit = 1;
        });

        $('#pwtc-mapdb-edit-ride-div .map-search-div').on('focusin', function(evt) {
            //console.log('focusin detected on map-search-div');
            evt.fromMapEdit = 1;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').on('focus', function() {
            var interval = 500;
            function callback(lastval) {
                var val = $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').val();
                //console.log('callback: val=' + val + ', lastval=' + lastval);
                if (val != lastval) {
                    fetch_route_maps(0, 0);
                    $('#pwtc-mapdb-edit-ride-div .map-search-div').show();
                }
                mapTimeoutID = setTimeout(callback, interval, val);
            };
            mapTimeoutID = setTimeout(callback, interval, $(this).val());
        });  

        $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').on('blur', function(evt) {
            clearTimeout(mapTimeoutID);
            $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').val('');
        });

        $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').on('click', function(evt) {
            if ($('#pwtc-mapdb-edit-ride-div .map-search-div').is(':hidden')) {
                fetch_route_maps(0, 0);
                $('#pwtc-mapdb-edit-ride-div .map-search-div').show();
            }
            evt.stopPropagation();		
        });

        $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                fetch_route_maps(0, 1);
                $('#pwtc-mapdb-edit-ride-div .map-search-div').show();
            } 
        });	

        $('#pwtc-mapdb-edit-ride-div .maps-div').on('click', function(evt) { 
            if ($('#pwtc-mapdb-edit-ride-div .map-search-div').is(':hidden')) {
                fetch_route_maps(0, 0);
                $('#pwtc-mapdb-edit-ride-div .map-search-div').show();
            }
            $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').focus();
        }); 

        $('#pwtc-mapdb-edit-ride-div .maps-div .fa-times').on('click', remove_map_event); 

        $('#pwtc-mapdb-edit-ride-div .maps-div a').on('click', function(evt) {
            evt.stopPropagation();
        }); 

        $('#pwtc-mapdb-edit-ride-div .map-search-div').on('scroll', function() {            
            if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
                var offset = $(this).attr('offset');
                if (offset) {
                    fetch_route_maps(parseInt(offset, 10), 0);
                    $(this).removeAttr('offset');
                }
            }
        });
        
        $('#pwtc-mapdb-edit-ride-div form').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.preventDefault(); 
                return false; 
            } 
        });	

        $('#pwtc-mapdb-edit-ride-div form textarea').on('keypress', function(evt) {
            is_dirty = true;
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.stopPropagation(); 
            } 
        });	

        $('#pwtc-mapdb-edit-ride-div input[name="title"]').on('input', function() {
            is_dirty = true;
            $(this).removeClass('indicate-error');
        });

        $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]').change(function() {
            is_dirty = true;
	    $(this).removeClass('indicate-error');
        });

        $('#pwtc-mapdb-edit-ride-div input[name="ride_time"]').change(function() {
            is_dirty = true;
	    $(this).removeClass('indicate-error');
        });

        $('#pwtc-mapdb-edit-ride-div input[name="distance"]').on('input', function() {
            is_dirty = true;
	    $(this).removeClass('indicate-error');
        });

        $('#pwtc-mapdb-edit-ride-div input[name="max_distance"]').on('input', function() {
            is_dirty = true;
	    $(this).removeClass('indicate-error');
        });

        $('#pwtc-mapdb-edit-ride-div input[name="ride_type"]').change(function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="ride_pace"]').change(function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[type="checkbox"]').change(function() {
            is_dirty = true;
	    $('#pwtc-mapdb-edit-ride-div .terrain-fst').removeClass('indicate-error');
        });

        $('#pwtc-mapdb-edit-ride-div input[name="attach_maps"]').change(function() {
            if (this.value == '0') {
		$('#pwtc-mapdb-edit-ride-div .map-search-div').hide();
                $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').val('');
                $('#pwtc-mapdb-edit-ride-div form .attach-map-yes').hide();
                $('#pwtc-mapdb-edit-ride-div form .attach-map-no').show();
            }
            else if (this.value == '1') {
                $('#pwtc-mapdb-edit-ride-div form .attach-map-no').hide();
                $('#pwtc-mapdb-edit-ride-div form .attach-map-yes').show();
            }
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="start_location_comment"]').on('input', function() {
            is_dirty = true;
        });
        
        $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').on('input', function() {
            is_dirty = true;
	    $(this).removeClass('indicate-error');
        });
        
    <?php if ($set_coords) { ?>

        $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').on('input', function() {
            is_dirty = true;
	    $(this).removeClass('indicate-error');
        });

        $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').on('input', function() {
            is_dirty = true;
	    $(this).removeClass('indicate-error');
        });

    <?php } ?>

        $('#pwtc-mapdb-edit-ride-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-edit-ride-div input').removeClass('indicate-error');
            $('#pwtc-mapdb-edit-ride-div textarea').removeClass('indicate-error');
            $('#pwtc-mapdb-edit-ride-div .maps-div').removeClass('indicate-error');
            $('#pwtc-mapdb-edit-ride-div .leaders-div').removeClass('indicate-error');
            $('#pwtc-mapdb-edit-ride-div .terrain-fst').removeClass('indicate-error');

            if ($('#pwtc-mapdb-edit-ride-div input[name="title"]').val().trim().length == 0) {
                show_warning('The <strong>ride title</strong> cannot be blank.');
                $('#pwtc-mapdb-edit-ride-div input[name="title"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }

            if ($('#pwtc-mapdb-edit-ride-div textarea[name="description"]').val().trim().length == 0) {
                show_warning('The <strong>ride description</strong> cannot be blank.');
                $('#pwtc-mapdb-edit-ride-div textarea[name="description"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }

    <?php if (!$is_template) { ?>
            var date = $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]').val().trim();
            var time = $('#pwtc-mapdb-edit-ride-div input[name="ride_time"]').val().trim();
            if (date.length == 0) {
                show_warning('The <strong>ride date</strong> must be set.');
                $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }
            if (time.length == 0) {
                show_warning('The <strong>departure time</strong> must be set.');
                $('#pwtc-mapdb-edit-ride-div input[name="ride_time"]').addClass('indicate-error');
                evt.preventDefault();
                return;			
            }
            var datergx = /^\d{4}-\d{2}-\d{2}$/;
            var timergx = /^\d{2}:\d{2}$/;
            if (!datergx.test(date)) {
                show_warning('The <strong>ride date</strong> format is invalid. Your browser may not support date entry, try upgrading it to the latest version or use a different browser.');
                $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }
            if (!timergx.test(time)) {
                show_warning('The <strong>departure time</strong> format is invalid. Your browser may not support time entry, try upgrading it to the latest version or use a different browser.');
                $('#pwtc-mapdb-edit-ride-div input[name="ride_time"]').addClass('indicate-error');
                evt.preventDefault();
                return;					
            }

            var date_elem = $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]')[0];
            if (date_elem.validity) {
                if (!date_elem.validity.valid) {
                    show_warning('The <strong>ride date</strong> must be on or after <?php echo $min_date_pretty; ?>.');
                    $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]').addClass('indicate-error');
                    evt.preventDefault();
                    return; 
                }
            }
    <?php } ?>

            var attach_map = $('#pwtc-mapdb-edit-ride-div input[name="attach_maps"]:checked').val() == '1';
            if (attach_map) {
                var new_maps = [];
                $('#pwtc-mapdb-edit-ride-div .maps-div div').each(function() {
                    var mapid = Number($(this).attr('mapid'));
                    new_maps.push(mapid); 
                });
                if (new_maps.length == 0) {
                    show_warning('You must attach at least one <strong>ride map</strong>.');
                    $('#pwtc-mapdb-edit-ride-div .maps-div').addClass('indicate-error');
                    evt.preventDefault();
                    return;
                }
                $('#pwtc-mapdb-edit-ride-div input[name="maps"]').val(JSON.stringify(new_maps));
            }
            else {
                var terrain_empty = $('#pwtc-mapdb-edit-ride-div input[name="ride_terrain[]"]:checked').length == 0;
                if (terrain_empty) {
                    show_warning('You must choose at least one <strong>ride terrain</strong>.');
                    $('#pwtc-mapdb-edit-ride-div .terrain-fst').addClass('indicate-error');
                    evt.preventDefault();
                    return;						
                }
                var dist = $('#pwtc-mapdb-edit-ride-div input[name="distance"]').val().trim();
                if (dist.length == 0) {
                    show_warning('You must enter a <strong>ride distance</strong>.');
                    $('#pwtc-mapdb-edit-ride-div input[name="distance"]').addClass('indicate-error');
                    evt.preventDefault();
                    return;							
                }
                dist = parseInt(dist, 10);
                if (dist == NaN || dist < 0) {
                    show_warning('You must enter a <strong>ride distance</strong> that is a non-negative number.');
                    $('#pwtc-mapdb-edit-ride-div input[name="distance"]').addClass('indicate-error');
                    evt.preventDefault();
                    return;							
                }
                var maxdist = $('#pwtc-mapdb-edit-ride-div input[name="max_distance"]').val().trim();
                if (maxdist.length > 0) {
                    maxdist = parseInt(maxdist, 10);
                    if (maxdist == NaN || maxdist < 0) {
                        show_warning('You must enter a <strong>ride max distance</strong> that is a non-negative number.');
                        $('#pwtc-mapdb-edit-ride-div input[name="max_distance"]').addClass('indicate-error');
                        evt.preventDefault();
                        return;							
                    }
                    if (maxdist <= dist) {
                        show_warning('The <strong>ride max distance</strong> must be greater than the <strong>ride distance</strong>.');
                        $('#pwtc-mapdb-edit-ride-div input[name="max_distance"]').addClass('indicate-error');
                        evt.preventDefault();
                        return;									
                    }					
                }
            }

            if ($('#pwtc-mapdb-edit-ride-div input[name="start_address"]').val().trim().length == 0) {
                show_warning('You must enter a <strong>start location</strong> for this ride.');
                $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }
            
            var lat = $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val().trim();
            if (lat.length == 0 || lat == '0') {
    <?php if ($set_coords) { ?>
                show_warning('You must enter a <strong>latitude</strong> for this ride.');
                $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').addClass('indicate-error');
    <?php } else { ?>
                show_warning('Google map location not found for <strong>start location</strong>.');
                $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').addClass('indicate-error');
    <?php } ?>
                evt.preventDefault();
                return;
            }

            var lng = $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val().trim();
            if (lng.length == 0 || lng == '0') {
    <?php if ($set_coords) { ?>
                show_warning('You must enter a <strong>longitude</strong> for this ride.');
                $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').addClass('indicate-error');
    <?php } else { ?>
                show_warning('Google map location not found for <strong>start location</strong>.');
                $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').addClass('indicate-error');
    <?php } ?>
                evt.preventDefault();
                return;
            }

            var new_leaders = [];
            $('#pwtc-mapdb-edit-ride-div .leaders-div div').each(function() {
                var userid = Number($(this).attr('userid'));
                new_leaders.push(userid); 
            });
            if (new_leaders.length == 0) {
                show_warning('You must assign at least one <strong>ride leader</strong>.');
                $('#pwtc-mapdb-edit-ride-div .leaders-div').addClass('indicate-error');
                evt.preventDefault();
                return;
            }
            $('#pwtc-mapdb-edit-ride-div input[name="leaders"]').val(JSON.stringify(new_leaders));

            is_dirty = false;
            show_waiting();
            $('#pwtc-mapdb-edit-ride-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-edit-ride-div .start_locations table tbody tr').each(function(index) {
            var lat = $(this).data('lat');
            var lng = $(this).data('lng');
            var zoom = $(this).data('zoom');
            if (lat && lng && zoom) {
                var area = $(this).find('td').first().html();
                var title = $(this).find('td').first().next().html();
                $('#pwtc-mapdb-edit-ride-div .start-locations-div table').append(
                    '<tr itemid="' + (index+1) + '"><td>' + title + '</td><td>' + area + '</td><td><a title="Display location in Google Maps."><i class="fa fa-map-marker"></i></a></td></tr>');
            }
        });

        $('#pwtc-mapdb-edit-ride-div .start-locations-div tr').on('click', function(evt) {
            var itemid = $(this).attr('itemid');
            var item = $('#pwtc-mapdb-edit-ride-div .start_locations table tbody tr:nth-child(' + itemid + ')');
            var title = item.find('td').first().next().html();
            var addr = item.find('td').first().next().next().html();
            var comment = item.find('td').first().next().next().next().html();
            var lat = item.data('lat');
            var lng = item.data('lng');
            var zoom = item.data('zoom');
            set_coord_string(lat, lng);
            $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').val(decodeHtml(title+', '+addr));
            $('#pwtc-mapdb-edit-ride-div input[name="start_location_comment"]').val(decodeHtml(comment));
            $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val(lat);
            $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val(lng);
            $('#pwtc-mapdb-edit-ride-div input[name="start_zoom"]').val(zoom);
    <?php if (!$set_coords) { ?>
            load_google_map();
    <?php } ?>
	    $('#pwtc-mapdb-edit-ride-div .accordion').foundation('up', $('#pwtc-mapdb-edit-ride-div .accordion-content'));
	    is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div .start-locations-div tr a').on('click', function(e) {
            e.stopPropagation();
            var itemid = $(this).parent().parent().attr('itemid');
            var item = $('#pwtc-mapdb-edit-ride-div .start_locations table tbody tr:nth-child(' + itemid + ')');
            var lat = item.data('lat');
            var lng = item.data('lng');
            var url = 'https://www.google.com/maps/search/?api=1&query=' + lat + ',' + lng;
            window.open(url, '_blank');
        });
        
    <?php if (!$set_coords) { ?>

        function show_geocode_error(message) {
            google_map = false;
            $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val('0');
            $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val('0');            
            $('#pwtc-mapdb-edit-ride-div .find-location-div').html('<div class="callout small warning"><p>' + message + '</p></div>');
            clear_coord_string();
            is_dirty = true;
        }

	function show_google_map(lat, lng, zoom) {
            google_map = false;
            $('#pwtc-mapdb-edit-ride-div .find-location-div').each(function() {
                $(this).empty();
                var latlng = new google.maps.LatLng(lat, lng);
                var mapArgs = {
                    zoom: zoom,
                    center: latlng,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                };
                google_map = new google.maps.Map($(this)[0], mapArgs);
            });
        }
	    
        function show_google_map_marker(lat, lng, zoom, drag_marker) {
            google_map = false;
            $('#pwtc-mapdb-edit-ride-div .find-location-div').each(function() {
                $(this).empty();
                var latlng = new google.maps.LatLng(lat, lng);
                var mapArgs = {
                    zoom: zoom,
                    center: latlng,
                    marker:	{
                        draggable: drag_marker,
                        raiseOnDrag: drag_marker
                    },
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                };
                google_map = new google.maps.Map($(this)[0], mapArgs);
                var marker = new google.maps.Marker({
                    position: latlng,
                    draggable: drag_marker,
                    raiseOnDrag: drag_marker,
                    map: google_map
                });
                if (drag_marker) {
                    marker.addListener('drag', function(evt) {
                        var lat = evt.latLng.lat();
                        var lng = evt.latLng.lng();
                        $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val(lat);
                        $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val(lng);
                        set_coord_string(lat, lng);
                        is_dirty = true;
                    });
                }
                google_map.marker = marker;
            });
        }

        function load_google_map() {
            $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').each(function() {
                var address = $(this).val();
                if (address) {
                    var lat = $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val();
                    var lng = $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val();
                    var zoom = $('#pwtc-mapdb-edit-ride-div input[name="start_zoom"]').val();
                    if (zoom && !hardcode_zoom) {
                        zoom = parseFloat(zoom);
                    }
                    else {
                        zoom = 16;
                    }
                    lat = parseFloat(lat);
                    lng = parseFloat(lng);
                    show_google_map_marker(lat, lng, zoom, true);
                }
                else {
                    show_google_map(45.5051064, -122.6750261, 16);
                }
            });
        }
        
        function run_geocoder(addrstr) {
            if (addrstr.length > 0) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: addrstr }, function(results, status) {
                    if (status === 'OK') {
                        if (results.length > 1) {
                            google_map = false;
                            $('#pwtc-mapdb-edit-ride-div .find-location-div').empty();
                            $('#pwtc-mapdb-edit-ride-div .find-location-div').append('<ul></ul>');
                            results.forEach(function(item) {
                                $('#pwtc-mapdb-edit-ride-div .find-location-div ul').append(
                                    '<li data-lat="' + item.geometry.location.lat() + '" data-lng="' + item.geometry.location.lng() + '">' + item.formatted_address + '</li>');    
                            });
                            $('#pwtc-mapdb-edit-ride-div .find-location-div li').on('click', function(evt) {
                                var lat = parseFloat($(this).data('lat'));
                                var lng = parseFloat($(this).data('lng'));
                                show_google_map_marker(lat, lng, 16, true);
				$('#pwtc-mapdb-edit-ride-div input[name="start_address"]').val($(this).html());
                                $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val(lat);
                                $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val(lng);
                                $('#pwtc-mapdb-edit-ride-div input[name="start_zoom"]').val(16);
                                set_coord_string(lat, lng);
                                is_dirty = true;
                            });
                        }
                        else {
                            var lat = results[0].geometry.location.lat();
                            var lng = results[0].geometry.location.lng();
                            show_google_map_marker(lat, lng, 16, true);
			    $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').val(results[0].formatted_address);
                            $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val(lat);
                            $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val(lng);
                            $('#pwtc-mapdb-edit-ride-div input[name="start_zoom"]').val(16);
                            set_coord_string(lat, lng);
                            is_dirty = true;
                        }
                    }
                    else if (status === 'ZERO_RESULTS') {
                        show_geocode_error('Google geocoder could not locate address.');
                    }
                    else {
                        show_geocode_error('Error returned from Google geocoder: ' + status);
                    }
                });
                google_map = false;
                $('#pwtc-mapdb-edit-ride-div .find-location-div').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
            }
            else {
                show_geocode_error('Start location not specified.');
            }
        }
        
        $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').each(function() {
	        var autocomplete = new google.maps.places.Autocomplete($(this)[0]);
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) {
                    run_geocoder(place.name);
                }
                else {
                    var lat = place.geometry.location.lat();
                    var lng = place.geometry.location.lng();
                    show_google_map_marker(lat, lng, 16, true);
                    $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val(lat);
                    $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val(lng);
                    $('#pwtc-mapdb-edit-ride-div input[name="start_zoom"]').val(16);
                    set_coord_string(lat, lng);
                    is_dirty = true;
                }
            });
        });

        var hardcode_zoom = true;
        var google_map = false;
        load_google_map();
        
    <?php } ?>
        
        $('#pwtc-mapdb-edit-ride-div a.goolmap').on('click', function(evt) {
            var lat = $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val();
            var lng = $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val();
            if (lat && lng) {
                var url = 'https://www.google.com/maps/search/?api=1&query=' + lat + ',' + lng;
                window.open(url, '_blank');
            }
        });

    <?php if ($attach_maps) { ?>
        $('#pwtc-mapdb-edit-ride-div form .attach-map-no').hide();
        $('#pwtc-mapdb-edit-ride-div form .attach-map-yes').show();
    <?php } else { ?>
        $('#pwtc-mapdb-edit-ride-div form .attach-map-yes').hide();
        $('#pwtc-mapdb-edit-ride-div form .attach-map-no').show();
    <?php } ?>

        window.addEventListener('beforeunload', function(e) {
            if (is_dirty) {
                e.preventDefault();
                e.returnValue = 'If you leave this page, any data you have entered will not be saved.';
            }
            else {
                delete e['returnValue'];
            }
        });

        var is_dirty = false;

    <?php if ($postid != 0) { ?>
        $(document).on( 'heartbeat-send', function( e, data ) {
            var send = {};
            send.post_id = '<?php echo $postid; ?>';
            data['pwtc-refresh-post-lock'] = send;
        });

        $(document).on( 'heartbeat-tick', function( e, data ) {
            if ( data['pwtc-refresh-post-lock'] ) {
                var received = data['pwtc-refresh-post-lock'];
                if ( received.lock_error ) {
                    show_warning('You can no longer edit this post. ' + received.lock_error.text);
                    $('#pwtc-mapdb-edit-ride-div button[type="submit"]').prop('disabled',true);
                } 
                else if ( received.new_lock ) {
                }
            }
        });

        wp.heartbeat.interval( 15 );
    <?php } ?>		

    });
</script>
<div id='pwtc-mapdb-edit-ride-div'>
    <?php echo $return_to_ride; ?>
    <?php if (!empty($operation)) { ?>
    <div class="callout small success">
        <p>
        <?php if ($operation == 'update_draft') { ?>
        The draft ride<?php echo $is_template ? ' template': ''; ?> was updated.
        <?php } else if ($operation == 'submit_review') { ?>
        The draft ride<?php echo $is_template ? ' template': ''; ?> was submitted for review.
        <?php } else if ($operation == 'update_pending') { ?>
        The pending ride<?php echo $is_template ? ' template': ''; ?> was updated.
        <?php } else if ($operation == 'published_draft') { ?>
        The draft ride<?php echo $is_template ? ' template': ''; ?> was published.
        <?php } else if ($operation == 'published') { ?>
        The pending ride<?php echo $is_template ? ' template': ''; ?> was published
        <?php if ($email_status == 'yes') { ?> and the author notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify author by email<?php } ?>.
        <?php } else if ($operation == 'rejected') { ?>
        The pending ride<?php echo $is_template ? ' template': ''; ?> was rejected
        <?php if ($email_status == 'yes') { ?> and the author notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify author by email<?php } ?>.
        <?php } else if ($operation == 'update_published') { ?>
        The published ride<?php echo $is_template ? ' template': ''; ?> was updated.
        <?php } else if ($operation == 'unpublished') { ?>
        The published ride<?php echo $is_template ? ' template': ''; ?> was unpublished.
        <?php } else if ($operation == 'insert') { ?>
        The first draft of your ride<?php echo $is_template ? ' template': ''; ?> was saved.
        <?php } else if ($operation == 'revert_draft') { ?>
        The ride<?php echo $is_template ? ' template': ''; ?> was reverted back to draft
        <?php if ($email_status == 'yes') { ?> and the road captain notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify road captain by email<?php } ?>.
        <?php } ?>
        </p>
    </div>
    <?php } ?>
    <div>
        <p>
    <?php if ($postid != 0) { ?>
        This ride<?php echo $is_template ? ' template': ''; ?> was authored by 
        <?php if ($author != $current_user->ID) { 
            echo '<a href="' . esc_url('mailto:' . $author_email) . '">' . $author_name . '</a>';
        } else { 
            echo $author_name;
        } ?> and is 
        <?php if ($status == 'draft') { ?>
        a draft. It can be updated or <?php if ($allow_leaders and !$is_road_captain) { ?>submitted for review<?php } else { ?>published<?php } ?> using the buttons at the bottom of the form.
        <?php } else if ($status == 'pending') { ?>
        pending review by a road captain. It can be updated, published or rejected using the buttons at the bottom of the form.
        <?php } else if ($status == 'publish') { ?>
        published<?php if (!$is_template) { ?> and on the ride calendar<?php } ?>. It can be updated or unpublished using the buttons at the bottom of the form.
        <?php } ?>
    <?php } else { ?>
        <?php if ($template) { ?>
        This is a new ride created from a template, set the <em>ride date</em> and <em>departure time</em> in the form below (and modify any other desired fields) and press the save button at the bottom of the form.
        <?php } else if ($copy_ride) { ?>
        This is a new ride<?php echo $is_template ? ' template': ''; ?> copied from an existing ride<?php echo $is_template ? ' template': ''; ?>, 
        <?php if ($is_template) { ?>
        modify any fields that you desire 
        <?php } else { ?>
        set the <em>ride date</em> in the form below (and modify any other desired fields) 
        <?php } ?> 
        and press the save button at the bottom of the form.
        <?php } else { ?>
        This is a new ride<?php echo $is_template ? ' template': ''; ?>, fill out the form below and press the save button at the bottom of the form.
        <?php } ?>
    <?php } ?>
        </p>
    </div>
    <div class="callout">
        <form method="POST" novalidate>
            <?php wp_nonce_field('ride-edit-form', 'nonce_field'); ?>
            <div class="row column">
                <label>Ride Title
                    <input type="text" name="title" value="<?php echo esc_attr($title); ?>" <?php echo $edit_title ? '': 'readonly'; ?>/>
                    <input type="hidden" name="postid" value="<?php echo $postid; ?>"/>
                    <input type="hidden" name="post_status" value="<?php echo $status; ?>"/>
                </label>
                <?php if (!$edit_title) { ?>
                    <p class="help-text">You are not allowed to edit the ride title.</p>
		<?php } else { ?>
                    <p class="help-text">Use creative names as they are more enticing to riders.  Weekly repeating rides can start the title with the day of the week but this isn’t required.  All ride names must start with a letter or number in order to be entered in the Mileage Database. </p>
                <?php } ?>
            </div>
    <?php if (!$is_template) { ?>
            <div class="row">
                <div class="small-12 medium-6 columns">
                    <label>Ride Date
                        <input type="date" name="ride_date" value="<?php echo $ride_date; ?>" <?php echo empty($min_date) ? '': 'min="'.$min_date.'"'; ?> <?php echo $edit_date ? '': 'readonly'; ?>/>
                    </label>
                    <?php if (!$edit_date) { ?>
                    <p class="help-text">You are not allowed to edit the ride date.</p>
                     <?php } else if (!empty($min_date_pretty)) { ?>
                    <p class="help-text">The ride date must be on or after <?php echo $min_date_pretty; ?>.</p>
                    <?php } ?>
                </div>
                <div class="small-12 medium-6 columns">
                    <label>Departure Time
                        <input type="time" name="ride_time" value="<?php echo $ride_time; ?>" <?php echo $edit_date ? '': 'readonly'; ?>/>	
                    </label>
                    <?php if ($edit_date) { ?>
                    <p class="help-text">To set the time, enter hours then minutes then AM or PM. For example, "10:00 AM".</p>
                    <?php } else { ?>
                    <p class="help-text">You are not allowed to edit the departure time.</p>
                    <?php } ?>
                </div>
            </div>
    <?php } ?>
            <div class="row column">
                <label>Ride Description
                    <textarea name="description" rows="10"><?php echo $description; ?></textarea>
                </label>
		<p class="help-text">Describe the ride in a way that helps people find rides that are appropriate for them, and provide any details that help manage riders&#39; expectations.  Provide information about planned stops for refreshments. Do not include any information that appears elsewhere on the ride page to avoid conflicting information and keep the description shorter and &#34;scannable&#34;.</p>
            </div>
            <div class="row">
                <div class="small-12 medium-6 columns">
                    <fieldset>
                        <legend>Ride Type</legend>
                        <input type="radio" name="ride_type" value="nongroup" id="type-nongroup" <?php echo $ride_type == 'nongroup' ? 'checked': ''; ?>><label for="type-nongroup">Non-group</label>
                        <input type="radio" name="ride_type" value="group" id="type-group" <?php echo $ride_type == 'group' ? 'checked': ''; ?>><label for="type-group">Group</label>
                        <input type="radio" name="ride_type" value="regroup" id="type-regroup" <?php echo $ride_type == 'regroup' ? 'checked': ''; ?>><label for="type-regroup">Re-group</label>
                    </fieldset>
		    <p class="help-text">See <a href="/home/ride-ratings-requirements-and-tips" target="_blank">ride ratings, requirements, and tips</a> for more information.</p>
                </div>
                <div class="small-12 medium-6 columns">
                    <fieldset>
                        <legend>Ride Pace</legend>
                        <div><input type="radio" name="ride_pace" value="no" id="pace-na" <?php echo $ride_pace == 'no' ? 'checked': ''; ?>><label for="pace-na">N/A</label></div>
                        <div><input type="radio" name="ride_pace" value="slow" id="pace-slow" <?php echo $ride_pace == 'slow' ? 'checked': ''; ?>><label for="pace-slow">Slow (8-13 mph)</label></div>
                        <div><input type="radio" name="ride_pace" value="leisurely" id="pace-leisurely" <?php echo $ride_pace == 'leisurely' ? 'checked': ''; ?>><label for="pace-leisurely">Intermediate (13-15 mph)</label></div>
                        <div><input type="radio" name="ride_pace" value="moderate" id="pace-moderate" <?php echo $ride_pace == 'moderate' ? 'checked': ''; ?>><label for="pace-moderate">Brisk (15-17 mph)</label></div>
                        <div><input type="radio" name="ride_pace" value="fast" id="pace-fast" <?php echo $ride_pace == 'fast' ? 'checked': ''; ?>><label for="pace-fast">Fast (18+ mph)</label></div>
                    </fieldset>
		    <p class="help-text">Speed indicated is while on a flat or gentle hill terrain—not an average speed, but a cruising speed. On a non-group ride, this is the pace of the ride leader.</p>
                </div>
                <div class="small-12 medium-6 columns">
                    <fieldset>
                        <legend>Attach Maps</legend>
                        <input type="radio" name="attach_maps" value="0" id="attach-no" <?php echo $attach_maps == false ? 'checked': ''; ?>><label for="attach-no">No</label>
                        <input type="radio" name="attach_maps" value="1" id="attach-yes" <?php echo $attach_maps == true ? 'checked': ''; ?>><label for="attach-yes">Yes</label>
                    </fieldset>
	            <p class="help-text">If possible, maps should be attached for re-group and non-group rides.</p>
                </div>
                <div class="small-12 medium-6 columns attach-map-no">
                    <fieldset class="terrain-fst">
                        <legend>Ride Terrain</legend>
                        <div><input type="checkbox" name="ride_terrain[]" value="a" id="terrain-a" <?php echo in_array('a', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-a">(A) Flat</label></div>
                        <div><input type="checkbox" name="ride_terrain[]" value="b" id="terrain-b" <?php echo in_array('b', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-b">(B) Mostly flat</label></div>
                        <div><input type="checkbox" name="ride_terrain[]" value="c" id="terrain-c" <?php echo in_array('c', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-c">(C) Small hills</label></div>
                        <div><input type="checkbox" name="ride_terrain[]" value="d" id="terrain-d" <?php echo in_array('d', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-d">(D) Large hills</label></div>
                        <div><input type="checkbox" name="ride_terrain[]" value="e" id="terrain-e" <?php echo in_array('e', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-e">(E) Mountainous</label></div>
                    </fieldset>
		    <p class="help-text">See <a href="/home/ride-ratings-requirements-and-tips" target="_blank">ride ratings, requirements, and tips</a> for more information.</p>
                </div>
                <div class="small-12 medium-6 columns attach-map-no">
                    <label>Ride Distance
                        <input type="number" name="distance" value="<?php echo $distance; ?>"/>	
                    </label>
		    <p class="help-text">All distances should be listed in miles.</p>
                </div>
                <div class="small-12 medium-6 columns attach-map-no">
                    <label>Ride Max Distance
                        <input type="number" name="max_distance" value="<?php echo $max_distance; ?>"/>	
                    </label>
		    <p class="help-text">Use to indicate a distance range if several route choices will be detailed in the ride description.</p>
                </div>
            </div>
            <div class="row column attach-map-yes">
                <label>Ride Maps
                    <input type="hidden" name="maps" value="<?php echo json_encode($maps); ?>"/>	
                </label>
            </div>
            <div class="row column attach-map-yes">
                <div class= "maps-div" style="min-height:40px; border:1px solid; display:flex; flex-wrap:wrap;">
    <?php 
        foreach ($maps_obj as $map) {
            $append = '';
            if ($map->post_status != 'publish') {
                $append = ' (' . $map->post_status . ')';
            }
    ?>
                    <div mapid="<?php echo $map->ID; ?>"><i class="fa fa-times"></i> <?php echo esc_html($map->post_title); ?><?php echo $append; ?> <?php echo PwtcMapdb::get_map_link($map->ID); ?></div>
    <?php } ?>
                    <input type="text" name="map-pattern" placeholder="Select map">
                </div>
            </div>
            <div class="row column attach-map-yes">
                <div class="map-search-div" style="border:1px solid; border-top-width: 0 !important; overflow: auto; height: 200px; display:none;">
                </div>
            </div>
            <div class="row column attach-map-yes" style="margin-top:15px;">
                <p class="help-text">When selecting a map, make certain that the start location on the route map matches the start location of the ride. To inspect the map, press the download or link icon.</p>
            </div>
            <div class="row column">
                <ul class="accordion" data-accordion data-allow-all-closed="true">
                    <li class="accordion-item" data-accordion-item>
                        <a href="#" class="accordion-title">Choose Popular Start Location...</a>
                        <div class="accordion-content" data-tab-content>
                            <div class="row column">
                                <p class="help-text">Below is a list of <a href="/ride-start-locations" target="_blank" rel="noopener noreferrer">popular club ride start locations.</a> Scroll through the list and choose your desired start location. To see the location on a Google Map, press the map marker icon.</p>
                                <div class="start-locations-div" style="border:1px solid; overflow: auto; height: 200px;">
                                    <table></table>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="row column">
                <label>Start Location
    <?php if ($set_coords) { ?>
                    <a class="goolmap" title="Display start location in Google Maps."><i class="fa fa-map-marker"></i></a>
    <?php } else { ?>
                    <span class="coord-span"><?php echo $start_coords; ?></span>
                    <a class="goolmap" <?php if (empty($start_coords)) { ?>style="display:none"<?php } ?> title="Display start location in Google Maps."><i class="fa fa-map-marker"></i></a>
    <?php } ?>                
                    <input type="text" name="start_address" value="<?php echo esc_attr($start_location['address']); ?>" placeholder="Enter a street address">
                </label>
    <?php if ($set_coords) { ?>
                <p class="help-text">Enter the string to be shown as the ride start location. The actual Google map location will be determined by the latitude and longitude coordinates entered below.</p>
    <?php } else { ?>
                <p class="help-text">If possible, the name of a Park or Church AND a street intersection is best for riders. They can easily find the general location and know exactly where to look for the group. After an address is entered, you may drag the map pointer to adjust the location on the map.</p>
    <?php } ?>
            </div>
    <?php if ($set_coords) { ?>
            <div class="row">
                <div class="small-12 medium-6 columns">
                    <label>Latitude Coordinate
                        <input type="text" name="start_lat" value="<?php echo esc_attr($start_location['lat']); ?>"/>
                    </label>
                    <p class="help-text">Enter a positive number for the northern hemisphere and a negative number for the southern hemisphere.</p>
                </div>
                <div class="small-12 medium-6 columns">
                    <label>Longitude Coordinate
                        <input type="text" name="start_lng" value="<?php echo esc_attr($start_location['lng']); ?>"/>
                    </label>
                    <p class="help-text">Enter a positive number for the eastern hemisphere and a negative number for the western hemisphere.</p>
                </div>
            </div>
    <?php } else { ?>
            <input type="hidden" name="start_lat" value="<?php echo esc_attr($start_location['lat']); ?>"/>
            <input type="hidden" name="start_lng" value="<?php echo esc_attr($start_location['lng']); ?>"/>
    <?php } ?>
            <input type="hidden" name="start_zoom" value="<?php echo esc_attr(isset($start_location['zoom']) ? $start_location['zoom'] : ''); ?>"/>
    <?php if (!$set_coords) { ?>
            <div class="row column">
                <div class="find-location-div" style="border:1px solid; overflow: auto; height: 200px;">
                </div>
            </div>
    <?php } ?>
            <div class="row column" <?php if (!$set_coords) { ?>style="margin-top:15px;"<?php } ?>>
                <label>Start Location Comment
                    <input type="text" name="start_location_comment" value="<?php echo esc_attr($start_location_comment); ?>"/>
                </label>
		<p class="help-text">Use this comment to provide additional instruction about the start location.</p>
            </div>
            <div class="row column">
                <label>Ride Leaders
                    <input type="hidden" name="leaders" value="<?php echo json_encode($leaders); ?>"/>	
                </label>
            </div>
            <div class="row column">
                <div class= "leaders-div" style="min-height:40px; border:1px solid; display:flex; flex-wrap:wrap;">
    <?php foreach ($leaders as $leader) {
        $info = get_userdata($leader);
        if ($info) {
            $name = $info->first_name . ' ' . $info->last_name;
    ?>
                    <div userid="<?php echo $leader; ?>"><?php if ($edit_leader and ($is_road_captain or $leader != $current_user->ID)) { ?><i class="fa fa-times"></i> <?php } ?><?php echo $name; ?></div>
    <?php } } ?>
    <?php if ($edit_leader) { ?>
                    <input type="text" name="leader-pattern" placeholder="Enter leader">
    <?php } ?>
                </div>
            </div>
    <?php if ($edit_leader) { ?>
            <div class="row column">
                <div class="leader-search-div" style="border:1px solid; border-top-width: 0 !important; overflow: auto; height: 100px; display:none;">
                </div>
            </div>
    <?php } ?>
            <div class="row column" style="margin-top:15px;">
    <?php if ($edit_leader) { ?>
                <p class="help-text">Riders must have ride leader access on the website to be assigned as leader.</p>
    <?php } ?>   
            </div>
	    <div class="row column errmsg"></div>
            <div class="row column clearfix">
            <?php if ($postid == 0) { ?>
                <button class="dark button float-left" type="submit">Save Draft</button>
            <?php } else if ($status == 'draft') { ?>
                <div class="button-group float-left">
                    <input class="dark button" name="draft" value="Update" type="submit"/>
                    <?php if ($allow_leaders and !$is_road_captain) { ?>
                    <input class="dark button" name="pending" value="Submit for Review" type="submit"/>
                    <?php } else { ?>
                    <input class="dark button" name="publish" value="Publish" type="submit"/>
                    <?php } ?>
                </div>
            <?php } else if ($status == 'pending') { ?>
                <div class="button-group float-left">
                    <input class="dark button" name="pending" value="Update" type="submit"/>
                    <input class="dark button" name="publish" value="Publish" type="submit"/>
                    <input class="dark button" name="draft" value="Reject" type="submit"/>
                </div>
            <?php } else { ?>
                <div class="button-group float-left">
                    <input class="dark button" name="publish" value="Update" type="submit"/>
                    <input class="dark button" name="draft" value="Unpublish" type="submit"/>
                </div>
            <?php } ?>
            </div>
        </form>
    </div>
    <div class="start_locations" style="display:none">
        <?php
        $content = get_page_by_title('Ride Start Locations');
        if ($content) {
            echo get_the_content(null, false, $content);
        }
        ?>
    </div>
</div>
<?php 
