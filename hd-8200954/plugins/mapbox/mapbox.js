/**
 * Required
 * jQuery >= 1.9.1
 *     js: plugins/posvenda_jquery_ui/js/jquery-1.9.1.js
 * Mapbox == 2.2.2
 *     js: plugins/mapbox/map.js
 *     css: plugins/mapbox/map.css
 */

function Map(element_id) {

    if (typeof $ == "undefined") {
        throw new Error("jQuery undefined");
    }

    if (typeof L == "undefined") {
        throw new Error("Mapbox undefined");
    }

    if (typeof element_id == "undefined" || $("#"+element_id).length == 0) {
        throw new Error("Element undefined or not found");
    }

    var element_id  = element_id;
    //var accessToken = "pk.eyJ1Ijoid2FsZGlycGltZW50ZWwiLCJhIjoiY2l6anZ2a2c4MDRqZzM4cWs5bmd2c21qaCJ9.yC-n1ri9huu7DEGWZp2b0A";
    //var accessToken = "pk.eyJ1IjoiYW5kZXJzb250ZWxlY29udHJvbCIsImEiOiJjajA4NXc5NTkwMDd4MndueWhtbWp3c3JlIn0.SI2BGGzVpQfwoq9Y78YqAQ";
    var accessToken = "pk.eyJ1IjoiaW1iZXJhdGNsIiwiYSI6ImNqNXV6dWZ2OTBiNGQzMnF3OGQydGc1aW4ifQ.u2Y5PB8gT7klfSZuxtySSw";
    this.mapbox     = null;

    /**
     * Load map in div element
     */
    this.load = function() {
        L.mapbox.accessToken = accessToken;

        try {
            this.mapbox = L.mapbox.map(element_id, "mapbox.streets").setView([-13.3481418, -52.6068917], 3);
        } catch(e) {
            console.log(e.message);
        }
    };

    /**
     * Set view on map
     * @param {float}   latitude
     * @param {float}   longitude
     * @param {integer} zoom
     */
    this.setView = function(latitude, longitude, zoom) {
        this.mapbox.setView([latitude, longitude], zoom);
        this.scrollToMap();
    };

    /**
     * Scroll document to map div element
     */
    this.scrollToMap = function() {
        $("html").scrollTop($("#"+element_id).offset().top);
    };

};

function Markers(map_class) {

    if (typeof map_class == "undefined") {
        throw new Error("Map Class undefined");
    }

    var map          = map_class;
    var marker_array = [];
    var layer        = null;

    var colors = {
        blue: "#337ab7",
        red: "#d9534f",
        yellow: "#f0ad4e",
        lightblue: "#5bc0de",
        green: "#5cb85c"
    };

    /**
     * Clear markers
     */
    this.clear = function() {
        if (layer != null) {
            layer.clearLayers();
        }
    };

    /**
     * Remove markers
     * @param  {integer} i
     */
    this.remove = function(i) {
        if (typeof i == "undefined" || i == null) {
            marker_array = [];
        } else {
            delete marker_array[i];
        }
    }

    /**
     * Get Markers
     */
    this.get = function() {
        return marker_array;
    }

    /**
     * Add new marker
     * @param {float}      latitude
     * @param {float}      longitude
     * @param {hexdecimal} color
     * @param {string}     title
     * @param {html}       description
     * @param {object}     extra_properties merge with default marker properties
     */
    this.add = function(latitude, longitude, color, title, description, extra_properties) {
        if (typeof color == "undefined" || color == null) {
            color = "blue";
        }

        var properties = {
            "marker-size": "large",
            "marker-color": colors[color]
        };

        if (typeof title != "undefined" && title != null && title.length > 0) {
            properties.title = title;
        }

        if (typeof description != "undefined" && description != null && description.length > 0) {
            properties.description = description;
        }

        if (extra_properties) {
            $.extend(properties, extra_properties);
        }

        marker_array.push({
            type: "Feature",
            geometry: {
                type: "Point",
                coordinates: [longitude, latitude]
            },
            properties: properties
        });
    };

    /**
     * Load markers on map
     */
    this.render = function() {
        layer = L.mapbox.featureLayer().addTo(map.mapbox);
        layer.setGeoJSON(marker_array);

        //Marker events to show popup on mouseover and close popup on mouseout
        layer.on("mouseover", function(e) {
            e.layer.openPopup();
        });

        layer.on("mouseout", function(e) {
            e.layer.closePopup();
        });
    };

    /**
     * Show all markers on map
     */
    this.focus = function(scrollToMap) {
        map.mapbox.fitBounds(layer.getBounds());

        if (scrollToMap) {
            map.scrollToMap();
        }
    };

    /**
     * Focus marker on mouse click
     * @param  {Function} callback return marker object to function callback
     */
    this.onClick = function(callback) {
        layer.on("click", function(e) {
            if (callback) {
                callback(e);
            }
        });
    };

};

function Router(map_class) {

    if (typeof map_class == "undefined") {
        throw new Error("Map Class undefined");
    }

    var map       = map_class;
    var polylines = [];
    var layers    = [];

    /**
     * Add new route
     * @param {array}  coordinates array with longitude (float) and latitude (float)
     */
    this.add = function(coordinates) {
        polylines.push({
            polyline: {
                type: "LineString",
                coordinates: coordinates
            },
            style: {
                color: "#337ab7",
                weight: 8,
                opacity: 1
            }
        });
    };

    /**
     * Remove all routes
     */
    this.remove = function() {
        polylines = [];
    };

    /**
     * Clear route layer on map
     */
    this.clear = function() {
        layers.forEach(function(layer, i) {
            map.mapbox.removeLayer(layer);
        });
    };

    /**
     * Load route on map
     */
    this.render = function() {
        polylines.forEach(function(polyline, i) {
            var layer = L.geoJson(polyline.polyline, { style: polyline.style }).addTo(map.mapbox);
            layers.push(layer);
            map.mapbox.fitBounds(layer);
        });
    };

};
