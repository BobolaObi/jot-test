var GoogleMap = {
    setMap: function(dataIndex, address){
        
        // The map canvas
        var mapCanvasId = 'mapCanvas'+ dataIndex;
        // The map container
        var mapContainerId = 'mapContainer'+ dataIndex;
        // The map that will be initializes
        var map;
        // The coordinates of the address
        var latlng;
        // The marker for the center
        var marker;
        
        // Get the latitude longitude from the address
        var geocoder = new google.maps.Geocoder();
        if (geocoder){
            geocoder.geocode( {'address': address}, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {

                    // take the first result (because it is the best match)
                    latlng = results[0].geometry.location;
                    var myOptions = {
                        zoom: 11,
                        center: latlng,
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    };
                    
                    // Create the map and set the center of the map
                    map = new google.maps.Map($(mapCanvasId), myOptions);
                    marker = new google.maps.Marker({
                        position: latlng,
                        map: map
                    });
                    
                    var largerMapLink = '<small><a target="_blank" href="http://maps.google.com/maps?q='+latlng.Ra+'+'+latlng.Sa+'" style="color:#0000FF;text-align:left">View Larger Map</a></small>';
                    $(mapContainerId).insert(largerMapLink);
                    $(mapContainerId).show();
                }
            });
        }
    }
};