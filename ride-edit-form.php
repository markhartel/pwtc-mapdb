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
    #pwtc-mapdb-edit-ride-div .map-search-div table tr {
        cursor: pointer;
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
    #pwtc-mapdb-edit-ride-div .pending-maps-div table tr {
        cursor: pointer;
    }
    #pwtc-mapdb-edit-ride-div .pending-maps-div table tr:hover {
        background-color: black !important;
        color: white !important;
    }
    #pwtc-mapdb-edit-ride-div .pending-maps-div table td {
        padding: 3px;
        vertical-align: top;
    }
    #pwtc-mapdb-edit-ride-div .pending-maps-div table tr:nth-child(odd) {
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
            $('#pwtc-mapdb-edit-ride-div .coord-span').html('(' + lat + ', ' + lng + ')');
            $('#pwtc-mapdb-edit-ride-div .goolmap').show();
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
            }
            else {
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').empty();
                $('#pwtc-mapdb-edit-ride-div .leader-search-div').append('<ul></ul>');
                res.users.forEach(function(item) {
                    $('#pwtc-mapdb-edit-ride-div .leader-search-div ul').append(
                        '<li userid="' + item.userid + '">' + item.first_name + ' ' + item.last_name + '</li>');    
                });
                $('#pwtc-mapdb-edit-ride-div .leader-search-div li').on('click', function(evt) {
                    var userid = $(this).attr('userid');
                    if (!has_user_id(userid)) {
                        var name = $(this).html();
                        is_dirty = true;
                        $('#pwtc-mapdb-edit-ride-div .leaders-div').append('<div userid="' + userid + '"><i class="fa fa-times"></i> ' + name + '</div>').find('i').on('click', function(evt) {
                            $(this).parent().remove();
                        });
                    }
                });
            }
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
            }
            else {
                $('#pwtc-mapdb-edit-ride-div .map-search-div').empty();
                $('#pwtc-mapdb-edit-ride-div .map-search-div').append('<table></table>');
                res.maps.forEach(function(item) {
                    var a = '';
                    if (item.type == 'file') {
                        a = '<a title="Download ride route map file." href="' + item.href + '" target="_blank" download><i class="fa fa-download"></i></a>';
                    }
                    else if (item.type == 'link') {
                        a = '<a title="Display online ride route map." href="' + item.href + '" target="_blank"><i class="fa fa-link"></i></a>';
                    }
                    else if (item.type == 'both') {
                        a = '<a title="Download ride route map file." href="' + item.href + '" target="_blank" download><i class="fa fa-download"></i></a> <a title="Display online ride route map." href="' + item.href2 + '" target="_blank"><i class="fa fa-link"></i></a>';
                    }
                    $('#pwtc-mapdb-edit-ride-div .map-search-div table').append(
                        '<tr mapid="' + item.ID + '"><td>' + item.title + '</td><td>' + item.distance + '</td><td>' + item.terrain + '</td><td>' + a + '</td></tr>');  
                });
                if (res.offset !== undefined) {
                    $('#pwtc-mapdb-edit-ride-div .map-search-div').append('<a class="dark button fetch" offset="' + res.offset + '" count="' + res.count + '">Show Next 10 Maps</a>');
                }
                $('#pwtc-mapdb-edit-ride-div .map-search-div tr a').on('click', function(evt) {
                    evt.stopPropagation();
                });
                $('#pwtc-mapdb-edit-ride-div .map-search-div tr').on('click', function(evt) {
                    var mapid = $(this).attr('mapid');
                    if (!has_map_id(mapid)) {
                        var title = $(this).find('td').first().html();
                        var link = $(this).find('td').last().html();
                        is_dirty = true;
                        $('#pwtc-mapdb-edit-ride-div .maps-div').append('<div mapid="' + mapid + '"><i class="fa fa-times"></i> ' + title + ' ' + link + '</div>').find('.fa-times').on('click', function(evt) {
                            $(this).parent().remove();
                        });
                    }
                });
                $('#pwtc-mapdb-edit-ride-div .map-search-div a.fetch').on('click', function(evt) {
                    var offset = $(this).attr('offset');
                    var count = $(this).attr('count');
                    var searchstr = $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').val();
                    var action = "<?php echo admin_url('admin-ajax.php'); ?>";
                    var data = {
                        'action': 'pwtc_mapdb_lookup_maps',
                        'limit': 10,
                        'title': searchstr,
                        'location': '',
                        'terrain': 0,
                        'distance': 0,
                        'media': 0,
                        'offset': offset,
                        'count': count,
                        'next': 1
                    };
                    $.post(action, data, maps_lookup_cb);
                    $('#pwtc-mapdb-edit-ride-div .map-search-div').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
                });
            }
        }
        
        $('#pwtc-mapdb-edit-ride-div .pending-maps-div tr a').on('click', function(evt) {
            evt.stopPropagation();
        });
        
        $('#pwtc-mapdb-edit-ride-div .pending-maps-div tr').on('click', function(evt) {
            var mapid = $(this).attr('mapid');
            if (!has_map_id(mapid)) {
                var title = $(this).find('td').first().html();
                var link = $(this).find('td').last().html();
                is_dirty = true;
                $('#pwtc-mapdb-edit-ride-div .maps-div').append('<div mapid="' + mapid + '"><i class="fa fa-times"></i> ' + title + ' (pending) ' + link + '</div>').find('.fa-times').on('click', function(evt) {
                    $(this).parent().remove();
                });
            }
        });        

        $('#pwtc-mapdb-edit-ride-div .leaders-div i').on('click', function(evt) {
            is_dirty = true;
            $(this).parent().remove();
        });

        $('#pwtc-mapdb-edit-ride-div .maps-div .fa-times').on('click', function(evt) {
            is_dirty = true;
            $(this).parent().remove();
        });

        $('#pwtc-mapdb-edit-ride-div input[name="search-leaders"]').on('click', function(evt) {
            var searchstr = $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').val();
            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_lookup_ride_leaders',
                'search': searchstr
            };
            $.post(action, data, leaders_lookup_cb);
            $('#pwtc-mapdb-edit-ride-div .leader-search-div').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');		
        });

        $('#pwtc-mapdb-edit-ride-div input[name="search-maps"]').on('click', function(evt) {
            var searchstr = $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').val();
            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_lookup_maps',
                'limit': 10,
                'title': searchstr,
                'location': '',
                'terrain': 0,
                'distance': 0,
                'media': 0
            };
            $.post(action, data, maps_lookup_cb);
            $('#pwtc-mapdb-edit-ride-div .map-search-div').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');		
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

        $('#pwtc-mapdb-edit-ride-div input[name="leader-pattern"]').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                $('#pwtc-mapdb-edit-ride-div input[name="search-leaders"]').trigger( 'click');
            } 
        });	

        $('#pwtc-mapdb-edit-ride-div input[name="map-pattern"]').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                $('#pwtc-mapdb-edit-ride-div input[name="search-maps"]').trigger( 'click');
            } 
        });	

        $('#pwtc-mapdb-edit-ride-div input[name="title"]').on('input', function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]').change(function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="ride_time"]').change(function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="distance"]').on('input', function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="max_distance"]').on('input', function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="ride_type"]').change(function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="ride_pace"]').change(function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[type="checkbox"]').change(function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-ride-div input[name="attach_maps"]').change(function() {
            if (this.value == '0') {
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
                show_warning('The <strong>ride date</strong> format is invalid.');
                $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }
            if (!timergx.test(time)) {
                show_warning('The <strong>departure time</strong> format is invalid.');
                $('#pwtc-mapdb-edit-ride-div input[name="ride_time"]').addClass('indicate-error');
                evt.preventDefault();
                return;					
            }

            var date_elem = $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]')[0];
            if (date_elem.validity) {
                if (!date_elem.validity.valid) {
                    show_warning('The <strong>ride date</strong> must be no earlier than <?php echo $min_date_pretty; ?>.');
                    $('#pwtc-mapdb-edit-ride-div input[name="ride_date"]').addClass('indicate-error');
                    evt.preventDefault();
                    return; 
                }
            }

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
                show_warning('You must choose a <strong>start location</strong> for this ride.');
                $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').addClass('indicate-error');
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

    <?php if ($edit_start_location) { ?>
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
            var zoom = '16';
            if (!hardcode_zoom) {
                zoom = item.data('zoom');
            }
            set_coord_string(lat, lng);
            $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').val(decodeHtml(title+', '+addr));
            $('#pwtc-mapdb-edit-ride-div input[name="start_location_comment"]').val(decodeHtml(comment));
            $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val(lat);
            $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val(lng);
            $('#pwtc-mapdb-edit-ride-div input[name="start_zoom"]').val(zoom);
            load_google_map();
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

        function show_geocode_error(message) {
            google_map = false;
            $('#pwtc-mapdb-edit-ride-div .find-location-div').html('<div class="callout small warning"><p>' + message + '</p></div>');
        }

        function show_google_map(lat, lng, zoom, drag_marker) {
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
                google_map.marker = marker;
            });
        }

        function load_google_map() {
            $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').each(function() {
                var address = $(this).val();
                if (address) {
                    $('#pwtc-mapdb-edit-ride-div input[name="location-address"]').val(address);
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
                    show_google_map(lat, lng, zoom, false);
                }
                else {
                    $('#pwtc-mapdb-edit-ride-div input[name="location-address"]').val('');
                    google_map = false;
                    $('#pwtc-mapdb-edit-ride-div .find-location-div').empty();
                }
            });
            $('#pwtc-mapdb-edit-ride-div .accept-location-div').hide();
        }

        $('#pwtc-mapdb-edit-ride-div input[name="find-location"]').on('click', function(evt) {
            $('#pwtc-mapdb-edit-ride-div .accept-location-div').hide();
            var addrstr = $('#pwtc-mapdb-edit-ride-div input[name="location-address"]').val().trim();
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
                                show_google_map(lat, lng, 16, true);
                                google_map.marker_address = $(this).html();
                                $('#pwtc-mapdb-edit-ride-div input[name="location-address"]').val(google_map.marker_address);
                                $('#pwtc-mapdb-edit-ride-div .accept-location-div').show();
                            });
                        }
                        else {
                            var lat = results[0].geometry.location.lat();
                            var lng = results[0].geometry.location.lng();
                            show_google_map(lat, lng, 16, true);
                            google_map.marker_address = results[0].formatted_address;
                            $('#pwtc-mapdb-edit-ride-div input[name="location-address"]').val(google_map.marker_address);
                            $('#pwtc-mapdb-edit-ride-div .accept-location-div').show();
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
                show_geocode_error('You must enter a street address.');
            }
        });

        $('#pwtc-mapdb-edit-ride-div input[name="location-address"]').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                $('#pwtc-mapdb-edit-ride-div input[name="find-location"]').trigger( 'click');
            } 
        });

        $('#pwtc-mapdb-edit-ride-div .accept-location-btn').on('click', function(evt) {
            if (google_map) {
                var position = google_map.marker.getPosition();
                if (position) {
                    var lat = position.lat();
                    var lng = position.lng();
                    var zoom = 16;
                    if (!hardcode_zoom) {
                        zoom = google_map.getZoom();
                    }
                    set_coord_string(lat, lng);
                    var addr = google_map.marker_address;
                    $('#pwtc-mapdb-edit-ride-div input[name="start_address"]').val(addr);
                    $('#pwtc-mapdb-edit-ride-div input[name="start_lat"]').val(lat);
                    $('#pwtc-mapdb-edit-ride-div input[name="start_lng"]').val(lng);
                    $('#pwtc-mapdb-edit-ride-div input[name="start_zoom"]').val(zoom);
                    is_dirty = true;
                    load_google_map();
                }
            }
            $('#pwtc-mapdb-edit-ride-div .accept-location-div').hide();
        });

        $('#pwtc-mapdb-edit-ride-div .cancel-location-btn').on('click', function(evt) {
            load_google_map();
            $('#pwtc-mapdb-edit-ride-div .accept-location-div').hide();
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
                    show_warning('You can no longer edit this ride. ' + received.lock_error.text);
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
        The draft ride was updated.
        <?php } else if ($operation == 'submit_review') { ?>
        The draft ride was submitted for review.
        <?php } else if ($operation == 'update_pending') { ?>
        The pending ride was updated.
        <?php } else if ($operation == 'published_draft') { ?>
        The draft ride was published.
        <?php } else if ($operation == 'published') { ?>
        The pending ride was published
        <?php if ($email_status == 'yes') { ?> and the author notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify author by email<?php } ?>.
        <?php } else if ($operation == 'rejected') { ?>
        The pending ride was rejected
        <?php if ($email_status == 'yes') { ?> and the author notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify author by email<?php } ?>.
        <?php } else if ($operation == 'update_published') { ?>
        The published ride was updated.
        <?php } else if ($operation == 'unpublished') { ?>
        The published ride was unpublished.
        <?php } else if ($operation == 'insert') { ?>
        The first draft of your ride was saved.
        <?php } else if ($operation == 'revert_draft') { ?>
        The ride was reverted back to draft
        <?php if ($email_status == 'yes') { ?> and the road captain notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify road captain by email<?php } ?>.
        <?php } ?>
        </p>
    </div>
    <?php } ?>
    <div>
        <p>
    <?php if ($postid != 0) { ?>
        This ride was authored by 
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
        published and on the ride calendar. It can be updated or unpublished using the buttons at the bottom of the form.
        <?php } ?>
    <?php } else { ?>
        <?php if ($template) { ?>
        This is a new ride created from a template, set the <em>ride date</em> and <em>departure time</em> in the form below (and modify any other desired fields) and press the save button at the bottom of the form.
        <?php } else if ($copy_ride) { ?>
        This is a new ride copied from an existing ride, set the <em>ride date</em> in the form below (and modify any other desired fields) and press the save button at the bottom of the form.
        <?php } else { ?>
        This is a new ride, fill out the form below and press the save button at the bottom of the form.
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
                <?php } ?>
            </div>
            <div class="row">
                <div class="small-12 medium-6 columns">
                    <label>Ride Date
                        <input type="date" name="ride_date" value="<?php echo $ride_date; ?>" <?php echo empty($min_date) ? '': 'min="'.$min_date.'"'; ?> <?php echo $edit_date ? '': 'readonly'; ?>/>
                    </label>
                    <?php if (!$edit_date) { ?>
                    <p class="help-text">You are not allowed to edit the ride date.</p>
                     <?php } else if (!empty($min_date_pretty)) { ?>
                    <p class="help-text">The ride date cannot be earlier than <?php echo $min_date_pretty; ?>.</p>
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
            <div class="row column">
                <label>Ride Description
                    <textarea name="description" rows="10"><?php echo $description; ?></textarea>
                </label>
            </div>
            <div class="row">
                <div class="small-12 medium-6 columns">
                    <fieldset>
                        <legend>Ride Type</legend>
                        <input type="radio" name="ride_type" value="nongroup" id="type-nongroup" <?php echo $ride_type == 'nongroup' ? 'checked': ''; ?>><label for="type-nongroup">Non-group</label>
                        <input type="radio" name="ride_type" value="group" id="type-group" <?php echo $ride_type == 'group' ? 'checked': ''; ?>><label for="type-group">Group</label>
                        <input type="radio" name="ride_type" value="regroup" id="type-regroup" <?php echo $ride_type == 'regroup' ? 'checked': ''; ?>><label for="type-regroup">Re-group</label>
                    </fieldset>
                </div>
                <div class="small-12 medium-6 columns">
                    <fieldset>
                        <legend>Ride Pace</legend>
                        <input type="radio" name="ride_pace" value="no" id="pace-na" <?php echo $ride_pace == 'no' ? 'checked': ''; ?>><label for="pace-na">N/A</label>
                        <input type="radio" name="ride_pace" value="slow" id="pace-slow" <?php echo $ride_pace == 'slow' ? 'checked': ''; ?>><label for="pace-slow">Slow</label>
                        <input type="radio" name="ride_pace" value="leisurely" id="pace-leisurely" <?php echo $ride_pace == 'leisurely' ? 'checked': ''; ?>><label for="pace-leisurely">Leisurely</label>
                        <input type="radio" name="ride_pace" value="moderate" id="pace-moderate" <?php echo $ride_pace == 'moderate' ? 'checked': ''; ?>><label for="pace-moderate">Moderate</label>
                        <input type="radio" name="ride_pace" value="fast" id="pace-fast" <?php echo $ride_pace == 'fast' ? 'checked': ''; ?>><label for="pace-fast">Fast</label>
                    </fieldset>
                </div>
                <div class="small-12 medium-6 columns">
                    <fieldset>
                        <legend>Attach Maps</legend>
                        <input type="radio" name="attach_maps" value="0" id="attach-no" <?php echo $attach_maps == false ? 'checked': ''; ?>><label for="attach-no">No</label>
                        <input type="radio" name="attach_maps" value="1" id="attach-yes" <?php echo $attach_maps == true ? 'checked': ''; ?>><label for="attach-yes">Yes</label>
                    </fieldset>
                </div>
                <div class="small-12 medium-6 columns attach-map-no">
                    <fieldset class="terrain-fst">
                        <legend>Ride Terrain</legend>
                        <input type="checkbox" name="ride_terrain[]" value="a" id="terrain-a" <?php echo in_array('a', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-a">A</label>
                        <input type="checkbox" name="ride_terrain[]" value="b" id="terrain-b" <?php echo in_array('b', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-b">B</label>
                        <input type="checkbox" name="ride_terrain[]" value="c" id="terrain-c" <?php echo in_array('c', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-c">C</label>
                        <input type="checkbox" name="ride_terrain[]" value="d" id="terrain-d" <?php echo in_array('d', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-d">D</label>
                        <input type="checkbox" name="ride_terrain[]" value="e" id="terrain-e" <?php echo in_array('e', $ride_terrain) ? 'checked': ''; ?>><label for="terrain-e">E</label>
                    </fieldset>
                </div>
                <div class="small-12 medium-6 columns attach-map-no">
                    <label>Ride Distance
                        <input type="number" name="distance" value="<?php echo $distance; ?>"/>	
                    </label>
                </div>
                <div class="small-12 medium-6 columns attach-map-no">
                    <label>Ride Max Distance
                        <input type="number" name="max_distance" value="<?php echo $max_distance; ?>"/>	
                    </label>
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
                </div>
            </div>
            <div class="row column attach-map-yes">
                <ul class="accordion" data-accordion data-allow-all-closed="true">
                    <li class="accordion-item" data-accordion-item>
                        <a href="#" class="accordion-title">Add Map from Library...</a>
                        <div class="accordion-content" data-tab-content>
                            <div class="row column">
                                <p class="help-text">Find route maps in the library by entering a title and pressing search, then choose the desired map from the resulting list. When choosing a map, make certain that the start location on the route map matches the start location of the ride. To inspect the map, press the download or link icon.</p>
                                <div class="input-group">
                                    <input class="input-group-field" type="text" name="map-pattern" placeholder="Enter map title">
                                    <div class="input-group-button">
                                        <input type="button" class="dark button" name= "search-maps" value="Search">
                                    </div>
                                </div>
                            </div>
                            <div class="row column">
                                <div class="map-search-div" style="border:1px solid; overflow: auto; height: 100px;">
                                </div>
                            </div>
                        </div>
                    </li>
    <?php if ($author == $current_user->ID and ($postid == 0 or $status == 'draft')) { ?>
                    <li class="accordion-item" data-accordion-item>
                        <a href="#" class="accordion-title">Add Submitted Map...</a>
                        <div class="accordion-content" data-tab-content>
                            <div class="row column">
                                <p class="help-text">Below is a list of route maps that you have submitted for review. Scroll through the list and choose the desired map. When choosing a map, make certain that the start location on the route map matches the start location of the ride. To inspect the map, press the download or link icon.</p>
                            </div>
                            <div class="row column">
                                <div class="pending-maps-div" style="border:1px solid; overflow: auto; height: 100px;">
                                <?php
                                $query_args = [
                                    'posts_per_page' => -1,
                                    'post_status' => 'pending',
                                    'post_type' => PwtcMapdb::MAP_POST_TYPE,
                                    'author' => $current_user->ID,
                                    'orderby' => 'date',
                                    'order' => 'DESC',
                                ];
                                $query = new WP_Query($query_args);
                                if ($query->have_posts()) {
                                ?>
                                <table>
                                    <?php
                                    while ($query->have_posts()) {
                                        $query->the_post();
                                        $pid = get_the_ID();
                                        $d = get_field(PwtcMapdb::LENGTH_FIELD, $pid);
                                        $max_d = get_field(PwtcMapdb::MAX_LENGTH_FIELD, $pid);
                                        $t = get_field(PwtcMapdb::TERRAIN_FIELD, $pid);
                                    ?>
                                    <tr mapid="<?php echo $pid; ?>">
                                        <td><?php echo esc_html(get_the_title()); ?></td>
                                        <td><?php echo PwtcMapdb::build_distance_str($d, $max_d); ?></td>
                                        <td><?php echo PwtcMapdb::build_terrain_str($t); ?></td>
                                        <td><?php echo PwtcMapdb::get_map_link($pid); ?></td>
                                    </tr>
                                    <?php } ?>
                                </table>
                                <?php } ?>
                                </div>
                            </div>
                        </div>
                    </li>
    <?php } ?>
                </ul>					
            </div>
            <div class="row column">
                <label>Start Location
                    <span class="coord-span"><?php echo $start_coords; ?></span>
                    <a class="goolmap" <?php if (empty($start_coords)) { ?>style="display:none"<?php } ?> title="Display start location in Google Maps."><i class="fa fa-map-marker"></i></a>
                    <input type="text" name="start_address" value="<?php echo esc_attr($start_location['address']); ?>" readonly/>
                </label>
    <?php if ($edit_start_location) { ?>
                <p class="help-text">You cannot edit the start location directly, instead press the find or choose start location buttons below.</p>
    <?php } else { ?>
                <p class="help-text">You are not allowed to edit the start location.</p>
    <?php } ?>
                <label>Start Location Comment
                    <input type="text" name="start_location_comment" value="<?php echo esc_attr($start_location_comment); ?>"/>
                </label>
                <input type="hidden" name="start_lat" value="<?php echo esc_attr($start_location['lat']); ?>"/>
                <input type="hidden" name="start_lng" value="<?php echo esc_attr($start_location['lng']); ?>"/>
                <input type="hidden" name="start_zoom" value="<?php echo esc_attr(isset($start_location['zoom']) ? $start_location['zoom'] : ''); ?>"/>
            </div>
    <?php if ($edit_start_location) { ?>
            <div class="row column">
                <ul class="accordion" data-accordion data-allow-all-closed="true">
                    <li class="accordion-item" data-accordion-item>
                        <a href="#" class="accordion-title">Find New Start Location...</a>
                        <div class="accordion-content" data-tab-content>
                            <div class="row column">
                                <p class="help-text">Find a start location by entering a street address and pressing search. A Google map with the location will display, press accept to use it as the start location.</p>
                                <div class="input-group">
                                    <input class="input-group-field" type="text" name="location-address" placeholder="Enter street address">
                                    <div class="input-group-button">
                                        <input type="button" class="dark button" name= "find-location" value="Search">
                                    </div>
                                </div>
                            </div>
                            <div class="row column">
                                <div class="find-location-div" style="border:1px solid; overflow: auto; height: 200px;">
                                </div>
                            </div>
                            <div class="accept-location-div row column" style="display:none">
                                <div class="button-group">
                                    <a class="accept-location-btn dark button">Accept</a>
                                    <a class="cancel-location-btn dark button">Cancel</a>
                                </dev>
                            </div>
                        </div>
                    </li>
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
    <?php } ?>
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
                    <div userid="<?php echo $leader; ?>"><?php if ($edit_leader) { ?><i class="fa fa-times"></i> <?php } ?><?php echo $name; ?></div>
                    <?php } } ?>
                </div>
            </div>
    <?php if ($edit_leader) { ?>
            <div class="row column">
                <ul class="accordion" data-accordion data-allow-all-closed="true">
                    <li class="accordion-item" data-accordion-item>
                        <a href="#" class="accordion-title">Add Ride Leader...</a>
                        <div class="accordion-content" data-tab-content>
                            <div class="row column">
                                <p class="help-text">Find ride leaders by entering a name and pressing search, then choose the desired leader from the resulting list.</p>
                                <div class="input-group">
                                    <input class="input-group-field" type="text" name="leader-pattern" placeholder="Enter leader name">
                                    <div class="input-group-button">
                                        <input type="button" class="dark button" name= "search-leaders" value="Search">
                                    </div>
                                </div>
                            </div>
                            <div class="row column">
                                <div class="leader-search-div" style="border:1px solid; overflow: auto; height: 100px;">
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>					
            </div>
    <?php } else { ?>
            <div class="row column" style="min-height:20px;"></div>
    <?php } ?>
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
    <?php if ($edit_start_location) { ?>
    <div class="start_locations" style="display:none">
        <?php
        $content = get_page_by_title('Ride Start Locations');
        if ($content) {
            echo get_the_content(null, false, $content);
        }
        ?>
    </div>
    <?php } ?>
</div>
<?php 
