/**
 * Required
 * jQuery >= 1.9.1
 *     js: plugins/posvenda_jquery_ui/js/jquery-1.9.1.js
 * LeafLet == 1.1.0 with VectorMarkers
 *     js: plugins/leaflet/leaflet.js
 *     css: plugins/leaflet/leaflet.css
 */ 

function MapInstance () {
return function (element_id) {
    if (typeof $ == "undefined") {
        throw new Error("jQuery undefined");
    }

    if (typeof L == "undefined") {
        throw new Error("LeafLet undefined");
    }

    if (typeof element_id == "undefined" || $("#"+element_id).length == 0) {
        throw new Error("Element undefined or not found");
    }

    var element_id  = element_id;
    this.map     = null;

    /**
     * Load map in div element
     */
    this.load = function() {
        try {
            this.map = L.map(element_id).setView([-13.3481418, -52.6068917], 3);
            L.tileLayer('https://maps.telecontrol.com.br/tile/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(this.map);
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
        this.map.setView([latitude, longitude], zoom);
        this.scrollToMap();
    };

    /**
     * Fly to location
     * @param {float}   latitude
     * @param {float}   longitude
     * @param {integer} zoom
     */
    this.flyTo = function(latitude, longitude, zoom) {
        this.map.flyTo([latitude, longitude], zoom);
        this.scrollToMap();
    };

    /**
     * Scroll document to map div element
     */
    this.scrollToMap = function() {
        $(window).scrollTop($("#"+element_id).offset().top);
    };
}};

var Map = MapInstance();

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
        blue_midea: "#0099DA",
        yellow: "#f0ad4e",
        lightblue: "#5bc0de",
        green: "#5cb85c",
        deepblue: "#092f87"
    };

    /**
     * Return color
     * @param {string} color 
     */
    this.color = function(color) {
        return colors[color];
    }

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
     * Add new markers, clear all old markers
     * @param {object} json 
        [
            {
                type: "Feature",
                geometry: {
                    type: "Point",
                    coordinates: [longitude, latitude]
                },
                properties: {
                    title: "Title, show on mouseover",
                    description: "Description, show on info window",
                    "marker-color": "blue",
                    etc...
                }
            },
            etc...
        ]
     */
    this.addJSON = function(json) {
        if (typeof json != "object" || json.length == 0) {
            throw new Error("invalid markers json");
        }

        this.remove();

        json.forEach(function(marker, i) {
            marker_array.push(marker);
        });
    }

    /**
     * Load markers on map
     */
    this.render = function() {
        layer = new L.GeoJSON(marker_array.filter(function(n) { return n != undefined; }), {
            pointToLayer: function(feature, latlng) {
                
                
                if(feature.hasOwnProperty("properties") == true && feature.properties.hasOwnProperty("icon") == true && feature.properties.icon != null && feature.properties.icon != undefined){
                    
                    var marker = L.marker(latlng, {icon: feature.properties.icon});   
                }else{
                    var marker = L.marker(latlng, {icon: L.VectorMarkers.icon({ markerColor: feature.properties["marker-color"] }) });   
                }
                
                var popup = "";

                
                if (feature.properties["title"]) {
                    popup += feature.properties["title"];
                }
                
                if (feature.properties["description"]) {
                    popup += "<br />"+feature.properties["description"];
                }
                
                if (popup.length > 0) {
                    marker.bindPopup(popup);
                }
                
                return marker;
            }
        }).addTo(map.map);
    };

    /**
     * Show all markers on map
     */
    this.focus = function(scrollToMap) {
        map.map.fitBounds(layer.getBounds());

        if (scrollToMap) {
            map.scrollToMap();
        }
    };

    /**
     * Marker click event
     * @param  {Function} callback return marker object to function callback
     */
    this.onClick = function(callback) {
        layer.on("click", function(e) {
            if (callback) {
                callback(e);
            }
        });
    };

    /**
     * Markers mouseover event
     * @param {Function} callback return marker object to function callback
     */
    this.onMouseover = function(callback) {
        layer.on("mouseover", function(e) {
            if (callback) {
                callback(e);
            }
        });
    }

    /**
     * Markers mouseout event
     * @param {Function} callback return marker object to function callback
     */
    this.onMouseout = function(callback) {
        layer.on("mouseout", function(e) {
            if (callback) {
                callback(e);
            }
        });
    }
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
        polylines.push(coordinates);
        /*polylines.push({
            polyline: {
                type: "LineString",
                coordinates: coordinates
            },
            style: {
                color: "#337ab7",
                weight: 8,
                opacity: 1
            }
        });*/
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
            map.map.removeLayer(layer);
        });
    };

    /**
     * Load route on map
     */
    this.render = function() {
        polylines.forEach(function(polyline, i) {
            //var layer = new L.GeoJSON(polyline.polyline, { style: polyline.style }).addTo(map.map);
            var layer = new L.polyline(polyline).addTo(map.map);
            layers.push(layer);
            map.map.fitBounds(layer.getBounds());
        });
    };
};
