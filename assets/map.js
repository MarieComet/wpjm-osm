(function($) {

	/*
	*  new_map
	*
	*  This function will render a Leaflet Map onto the selected jQuery element
	*
	*  @type	function
	*  @date	8/11/2013
	*  @since	4.3.0
	*
	*  @param	$el (jQuery element)
	*  @return	n/a
	*/

	function new_map( $el ) {
		
		// create map	        	
		var map = L.map( $el[0] ).setView([0, 0], 16);

		L.tileLayer('https://a.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
			maxZoom: 18,
			scrollWheelZoom: false,
			attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, ' +
				'<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
				'Imagery Â© <a href="https://www.mapbox.com/">Mapbox</a>',
			id: 'mapbox.streets'
		}).addTo(map);

		var layerGroup = L.layerGroup().addTo(map);
		
		// when WPJM job listing results update
		$( 'div.job_listings' ).on( 'updated_results', function() {

			layerGroup.clearLayers();
		
			// var
			var $markers = $( 'ul.job_listings' ).find( 'li.job_listing' );

			
			// add a markers reference
			map.markers = [];
			
			
			// add markers
			$markers.each( function() {

				// add marker only if latitude and longitude exists
				if ( $(this).attr('data-latitude') && $(this).attr('data-longitude') ) {
		    		add_marker( $(this), map, layerGroup );
		    	}
				
			});

			// center map
			center_map( map );

		});
		
		// return
		return map;
		
	}

	function new_single_map( $el ) {
		
		// create map	        	
		var map = L.map( $el[0] ).setView([0, 0], 16);

		L.tileLayer('https://a.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
			maxZoom: 18,
			scrollWheelZoom: false,
			attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, ' +
				'<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
				'Imagery Â© <a href="https://www.mapbox.com/">Mapbox</a>',
			id: 'mapbox.streets'
		}).addTo(map);

		var layerGroup = L.layerGroup().addTo(map);
		


		layerGroup.clearLayers();
		
		// var
		var $markers = $( $el ).find( '.job-listing-marker' );

			
		// add a markers reference
		map.markers = [];
			
			
		// add markers
		$markers.each( function() {

			// add marker only if latitude and longitude exists
			if ( $(this).attr('data-latitude') && $(this).attr('data-longitude') ) {
		    	add_marker( $(this), map, layerGroup );
		    }
				
		});

		// center map
		center_map( map );
		
		// return
		return map;
		
	}

	/*
	*  add_marker
	*
	*  This function will add a marker to the selected Leaflet Map
	*
	*  @type	function
	*  @date	8/11/2013
	*  @since	4.3.0
	*
	*  @param	$marker (jQuery element)
	*  @param	map (Leaflet Map object)
	*  @return	n/a
	*/

	function add_marker( $marker, map, layerGroup ) {

		var title = $marker.find( 'h3' ).text();

		// job listings list
		if ( title.length ) {
			var location = $marker.find( '.location' ).text();
			var permalink = $marker.find( 'a' ).attr( 'href' );
			var contentPopup = '<a href="' + permalink + '" class="wpjm-osm-title">' + title + '</a><p class="wpjm-osm-location">' + location + '<p>';
		}
		// var
		var marker = L.marker(
			[$marker.attr('data-latitude'), $marker.attr('data-longitude')],
			{
				title: title,
				alt: title,
			}
			)
			.addTo(layerGroup);

		if ( contentPopup ) {
			marker.bindPopup( contentPopup );
		}

		// add to array
		map.markers.push( marker );

	}

	/*
	*  center_map
	*
	*  This function will center the map, showing all markers attached to this map
	*
	*  @type	function
	*  @date	8/11/2013
	*  @since	4.3.0
	*
	*  @param	map (Leaflet Map object)
	*  @return	n/a
	*/

	function center_map( map ) {

		if ( map.markers.length !== 0 ) {

			// vars
			var bounds = L.latLngBounds();

			// loop through all markers and create bounds
			$.each( map.markers, function( i, marker ){

				var latlng = [ marker._latlng.lat, marker._latlng.lng ];

				bounds.extend( latlng );

			});

			if ( bounds ) {

				// only 1 marker?
				if( map.markers.length == 1 )
				{
					// set center of map
				    //map.setCenter( bounds.getCenter() );
				    map.fitBounds( bounds );
				    map.setZoom( 16 );
				}
				else
				{
					// fit to bounds
					map.fitBounds( bounds );
				}

			}
		}
	}


	/*
	*  document ready
	*
	*  This function will render each map when the document is ready (page has loaded)
	*
	*  @type	function
	*  @date	8/11/2013
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	// global var*/
	var map = null;

	$(document).ready(function(){

		if ( $( '#jobsMap').length ) {

			map = new_map( $( '#jobsMap' ) );

			map.scrollWheelZoom.disable();
			
		}

		if ( $( '#jobSingleMap' ).length ) {

			map = new_single_map( $( '#jobSingleMap' ) );

			map.scrollWheelZoom.disable();
		}

	});

})(jQuery);