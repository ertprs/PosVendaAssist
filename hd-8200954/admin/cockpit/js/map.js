var map = {
    leaflet: null,
    markers: null,
    router: null,
    technical_selected: null,
    distance: null,
    returning_distance: null,
    technical_maximum_amount: 0,
    technical_internal: false,
    geocoder: new Geocoder(),
    address: null,
    address_json: null,
    keep_technical: false,
    latitude_view: null,
    longitude_view: null,
    destiny: {
        latitude: null,
        longitude: null,
        zip_code: null
    },
    diferencial: 0.000000,
    fnc: 0,
    init: function() {
        $("#form-wizard-tabs").append("<li class='form-wizard-tab-map' rel='form-wizard-map' ><a href='#form-wizard-map' data-toggle='tab' >Mapa de Técnicos</a></li>");
        $("#form-wizard-tabs-content").append("\
            <div class='tab-pane' id='form-wizard-map' >\
                <div id='map' style='height: 300px; width: 100%;' ></div>\
                <div style='overflow-y: auto; height: 40%;' >\
                    <table id='map-technical-table' class='table table-condensed' >\
                        <thead>\
                            <tr class='titulo_coluna' >\
                                <th>Nome</th>\
                                <th>Tipo</th>\
                                <th>Endereço</th>\
                                <th title='Atendimentos de Hoje/Máximo de Atendimentos' >Atend./Max.</th>\
                                <th>Distância</th>\
                            </tr>\
                        </thead>\
                        <tbody></tbody>\
                    </table>\
                </div>\
                <div class='module_actions text-right' style='position: fixed; bottom: 0px; margin-bottom: 20px; right: 20px;' >\
                    <button type='button' class='btn btn-primary next-tab disabled' style='width: 150px;' >Próximo</button>\
                </div>\
            </div>\
        ");

        map.trigger();
    },
    trigger: function() {
        $("li.form-wizard-tab-map a").on("click", function() {
            if ($(this).parent().hasClass("disabled")) {
                return false;
            }

            form_wizard.current_tab = $(this).parent().attr("rel");

            window.delay(function() {
                if (map.address_json == null) {
                    var address = ticket_conference.get_cliente_address();
                } else {
                    var address = map.address_json;
                }

                var full_address = address.address+", "+address.neighborhood+", "+address.city+", "+address.state+", "+address.country;

                map.destiny.zip_code = address.zip_code;

                map.load_map(map.geocode_latitude_longitude, { full_address: full_address, address: address });
            });
        });

        $(document).on("click", "#map-technical-table > tbody > tr", function() {
            var latitude  = $(this).data("latitude");
            var longitude = $(this).data("longitude");

            if (typeof latitude == "undefined" || typeof longitude == "undefined") {
                return false;
            }

            map.leaflet.setView(latitude, longitude, 13);
        });
    },
    load_map: function(callback, options_callback) {
        try{
            if (map.leaflet == null) {
                map.leaflet = new Map("map");
                map.leaflet.load();

                map.markers = new Markers(map.leaflet);
                map.router = new Router(map.leaflet);
            }
        }catch(e){
            console.log(e.message);
        }

        if (options_callback.full_address != map.address) {
            map.keep_technical = false;
            map.address        = options_callback.full_address;
        } else {
            map.keep_technical = true;
        }

        if (callback && map.keep_technical != true) {
            window.delay(function() {
                callback(options_callback.address);
            });
        } else if (map.keep_technical == true && map.technical_selected != null) {
            form_wizard.activeNextTab();
        }
    },
    set_client_address: function(address) {
        map.address_json = {
            address: address.address,
            neighborhood: address.neighborhood,
            city: address.city,
            state: address.state,
            country: address.country,
            zip_code: address.zip_code
        };

        map.destiny.zip_code = address.zip_code;
    },
    set_current_technical: function() {
        $.ajax({
            async: false,
            url: "cockpit/ajax/map.php",
            type: "get",
            data: { ajax_set_current_technical: true, ticket: form_wizard.ticket, lat: map.destiny.latitude, lng: map.destiny.longitude },
            contentType: "application/json",
            dataType: "json"
        }).always(function(response) {
            if (response.error) {
                alert(response.error);
            } else if(response.success == true) {
                map.technical_selected       = response.technical;
                map.technical_maximum_amount = response.maximum_amount;
                map.technical_internal       = response.internal;
                map.distance                 = response.distance;
                map.keep_technical           = true;
            }
        });
    },
    reload_map: function(fit) {
        if (typeof fit == "undefined") {
            fit = false;
        }

	if (typeof map.marker_array == "object" && map.marker_array.length > 0) {
        	map.markers.addJSON(map.marker_array);
        	map.markers.clear();
        	map.markers.render();
        	map.marker_events();

	        if (map.technical_selected != null || fit == true) {
        	    window.delay(function() {
                	map.markers.focus();
            	    });
	        } else {
        	    window.delay(function() {
                	map.leaflet.setView(map.latitude_view, map.longitude_view, 13);
	            });
        	}
	} else {
		map.markers.remove();
		map.markers.clear();
		$("#map-technical-table > tbody").html("");
		form_wizard.disableNextTab();
	}
    },
    technical_nearest: function() {
        $.ajax({
            async: false,
            url: "cockpit/ajax/map.php",
            type: "get",
            data: { 
                ajax_technical_nearest: true, 
                lat: map.destiny.latitude, 
                lng: map.destiny.longitude,
                zip_code: map.destiny.zip_code,
                call_type: form_wizard.call_type,
                call_type_warranty: form_wizard.call_type_warranty,
                client_id: form_wizard.client_id,
                product: form_wizard.product,
                distribution_center: form_wizard.distribution_center
            },
            contentType: "application/json",
            dataType: "json",
            beforeSend: function() {
                map.markers.remove();
                map.markers.clear();
                map.marker_array = [];
            }
        }).always(function(response) {
            if (response.error) {
                map.technical_maximum_amount = 0;
                map.technical_internal       = false;
                map.distance                 = null;
                map.returning_distance       = null;

                if (response.technical_not_found == true) {
                    if (form_wizard.technical == null) {
                        alert(response.error);
                    }

                    map.add_destiny_marker();
                    map.markers.addJSON(map.marker_array);
                    map.markers.clear();
                    map.markers.render();
                    map.markers.focus();
                    map.marker_events();
                    map.create_table(null, map.check_manual_technical);
                } else {
                    alert(response.error);
                }
            } else if (response.success == true) {
                map.add_destiny_marker();

                var selected = map.select_nearest_technical(response.result, true);

                [].forEach.call(response.result, function(technical) {
                    map.add_technical_marker(technical);
                });

                if (selected) {
                    form_wizard.activeNextTab();
                } else {
                    form_wizard.disableNextTab();
                }

                map.markers.addJSON(map.marker_array);
                map.markers.clear();
                map.markers.render();
                map.marker_events();

                window.delay(function() {
                    if (map.technical_selected != null && map.latitude_view != null && map.longitude_view != null) {
                        map.leaflet.setView(map.latitude_view, map.longitude_view, 13);
                    } else {
                        map.markers.focus();
                    }

                    map.create_table(response.result, map.check_manual_technical);
                });
            } else {
                alert("Não foi possível carregar as informações, tente novamente");
                map.technical_selected       = null;
                map.technical_maximum_amount = 0;
                map.technical_internal       = false;
                map.distance                 = null;
                map.returning_distance       = null;
            }
        });
    },
    marker_events: function() {
        map.markers.onClick(function(e) {
            if (!e.layer.feature.properties["destiny"]) {
                map.marker_layer_reset_color();

                e.layer.feature.properties["marker-color"] = map.markers.color("yellow");

                map.markers.clear();
                map.markers.render();

                var id                 = e.layer.feature.properties["technical-id"];
                var maximum_amount     = e.layer.feature.properties["technical-maximum-amount"];
                var internal           = e.layer.feature.properties["technical-internal"];
                var distance           = e.layer.feature.properties["distance"];
                var returning_distance = e.layer.feature.properties["returning_distance"];

                $("#technical-"+id).addClass("technical-selected");

                map.technical_selected                      = id;
                map.technical_maximum_amount                = maximum_amount;
                map.technical_internal                      = internal;
                map.distance                                = distance;
                map.returning_distance                      = returning_distance;
                technical_schedule.datepicker.selected_date = null;

                map.latitude_view  = e.latlng.lat;
                map.longitude_view = e.latlng.lng;

                map.leaflet.setView(e.latlng.lat, e.latlng.lng, 13);

                form_wizard.activeNextTab();
                map.marker_events();
            }

            return false;
        });

        map.markers.onMouseover(function(e) {
            e.layer.openPopup();
        });

        map.markers.onMouseout(function(e) {
            e.layer.closePopup();
        });
    },
    geocode_latitude_longitude: function(address) {
	map.marker_array = [];

	address.country = (address.country) ? address.country : "Brasil"

        map.geocoder.setEndereco({
            endereco: address.address,
            bairro: address.neighborhood,
            cidade: address.city,
            estado: address.state,
            pais: address.country,
			cep: address.zip_code
        });

        var request = map.geocoder.getLatLon();

        request.then(
            function(response) {
		if (response.latitude.length == 0 || response.longitude.length == 0) {
			map.reload_map();
			alert("Não foi possível buscar a localização do cliente, tente retirar o número do endereço e efetue uma nova busca.");
		} else {
                	map.destiny.latitude  = response.latitude;
                	map.destiny.longitude = response.longitude;

                	if (map.technical_selected == null) {
                    		map.set_current_technical();
                	}

                	map.technical_nearest();
		}
            },
            function(error) {
		map.reload_map();
                alert("Não foi possível buscar a localização do cliente");
            }
        );
    },
    marker_layer_reset_color: function() {
        map.marker_array.forEach(function(marker, i) {
            if (!map.marker_array[i].properties["destiny"]) {
                map.marker_array[i].properties['marker-color'] = map.markers.color("blue");

                var id = map.marker_array[i].properties["technical-id"];
                $("#technical-"+id).removeClass("technical-selected");
            }
        });

        map.markers.remove();
        map.markers.clear();
        map.markers.addJSON(map.marker_array);
        map.markers.render();
    },
    marker_array: [],
    add_technical_marker: function(data, manual) {
        if (typeof manual == "undefined") {
            manual = false;
        }

        if (data.id == map.technical_selected) {
            var selected       = true;

            map.latitude_view  = data.latitude;
            map.longitude_view = data.longitude;
        } else {
            var selected = false;
        }

        var latitude = data.latitude;
        var longitude = data.longitude;

        if (manual == true) {
            longitude -= 0.000010;
        } else if ((map.diferencial / 2) >= 0.000005) {
            var diferencial = 0.000005;

            switch (map.fnc) {
                case 0:
                    latitude  -= diferencial;
                    longitude -= diferencial;
                    break;

                case 1:
                    latitude  += diferencial;
                    longitude += diferencial;
                    break;

                case 2:
                    latitude  -= diferencial;
                    longitude += diferencial;
                    break;

                case 3:
                    latitude  += diferencial;
                    longitude -= diferencial;
                    break;
            }
        } else if (map.diferencial == 0.000005) {
            latitude  += 0.000005;
            longitude += 0.000005;
        }

        var marker = {
            type: "Feature",
            geometry: {
                type: "Point",
                coordinates: [longitude, latitude]
            },
            properties: {
                title: ((data["internal-technical"] == true) ? "Técnico" : "Terceiro"),
                description: data.name,
                "marker-color": (selected) ? map.markers.color("yellow") : map.markers.color("blue") ,
                "technical-id": data.id,
                "technical-maximum-amount": data.maximum_amount,
                "technical-internal": data["internal-technical"],
                "distance": data.distance,
                "returning_distance": data.returning_distance
            }
        };

        map.marker_array.push(marker);

        if (manual == false) {
            map.diferencial += 0.000005;

            switch (map.fnc) {
                case 0:
                    map.fnc = 1;
                    break;

                case 1:
                    map.fnc = 2;
                    break;

                case 2:
                    map.fnc = 3;
                    break;

                case 3:
                    map.fnc = 0;
                    break;
            }
        }
    },
    remove_technical_marker: function(id) {
        var new_array = [];

        map.marker_array.forEach(function(data, i) {
            if (data.properties["technical-id"] != id) {
                new_array.push(data);
            }
        });

        map.marker_array = new_array;
    },
    add_destiny_marker: function() {
        var marker = {
            type: "Feature",
            geometry: {
                type: "Point",
                coordinates: [map.destiny.longitude, map.destiny.latitude]
            },
            properties: {
                title: "Cliente",
                description: form_wizard.client_name,
                "marker-color": map.markers.color("green"),
                destiny: true
            }
        };

        map.marker_array.push(marker);
    },
    select_nearest_technical: function(array_technical, internal_technical) {
        var min_distance   = null;
        
        [].forEach.call(array_technical, function(technical, i) {
            if (map.keep_technical == true && map.technical_selected == technical.id) {
                map.technical_selected       = technical.id;
                map.technical_maximum_amount = technical.maximum_amount;
                map.technical_internal       = technical["internal-technical"];
                map.distance                 = technical.distance;
                map.returning_distance       = technical.returning_distance;

                return false;
            } else if (map.keep_technical == false && ((min_distance == null || technical.distance < min_distance) && technical["internal-technical"] == internal_technical)) {
                if (internal_technical == true && technical.calls >= technical.maximum_amount) {
                    return;
                }

                min_distance                 = technical.distance;
                map.technical_selected       = technical.id;
                map.technical_maximum_amount = technical.maximum_amount;
                map.technical_internal       = technical["internal-technical"];
                map.distance                 = technical.distance;
                map.returning_distance       = technical.returning_distance;
                technical_schedule.datepicker.selected_date = null;
            }
        });

        if (map.technical_selected != null) {
            return true;
        } else {
            if (internal_technical == false) {
                return false;
            }

            technical_schedule.datepicker.selected_date = null;

            return map.select_nearest_technical(array_technical, false);
        }
    },
    create_table: function(array_technical, callback) {
        var t = $("#map-technical-table > tbody");

        $(t).html("");

        if (array_technical != null) {
            [].forEach.call(array_technical, function(technical) {
                var selected = (technical.id == map.technical_selected) ? true : false;

                var address = technical.address;

                if (technical.number.length > 0) {
                    address += ", "+technical.number;
                }

                if (technical.neighborhood.length > 0) {
                    address += ", "+technical.neighborhood;
                }

                address += ", "+technical.city+", "+technical.state;

                $(t).append("\
                    <tr id='technical-"+technical.id+"' data-id='"+technical.id+"' "+((selected) ? "class='technical-selected'" : "")+" data-latitude='"+technical.latitude+"' data-longitude='"+technical.longitude+"' >\
                        <td>"+technical.name+"</td>\
                        <td>"+((technical["internal-technical"] == true) ? "Técnico" : "Terceiro")+"</td>\
                        <td>"+address+"</td>\
                        <td class='text-center' title='Atendimentos de Hoje/Máximo de Atendimentos' >"+technical.calls+"/"+technical.maximum_amount+"</td>\
                        <td>"+parseFloat(technical.distance).toFixed(2)+" Km</td>\
                    </tr>\
                ");
            });
        }

        $(t).append("\
            <tr class='manual-selection info' >\
                <td id='technical-assistance-search' colspan='5' >\
                    <input type='text' id='search-input' class='form-control input-xs' style='display: inline-block; width: 30%;' placeholder='Pesquisa: Nome ou CPF/CNPJ (somente números)' />\
                </td>\
                <td class='name' style='display: none;' ></td>\
                <td class='technical-type' style='display: none;' ></td>\
                <td class='address' style='display: none;' ></td>\
                <td class='calls-limit text-center' title='Atendimentos de Hoje/Máximo de Atendimentos' style='display: none;' ></td>\
                <td class='distance' style='display: none;' ></td>\
            </tr>\
        ");

        window.delay(function() {
            $("#search-input").autocomplete({
                source: "cockpit/ajax/map.php",
                cache: false,
                extraParams: { 
                    search: "autocomplete", 
                    ajax_technical_nearest: true, 
                    not_in: function() {
                        var array = [];

                        $("#map-technical-table > tbody > tr").each(function() {
                            if ($(this).hasClass("manual-selection")) {
                                return;
                            }

                            array.push($(this).data("id"));
                        });

                        return array;
                    },
                    lat: function() {
                        return map.destiny.latitude;
                    },
                    lng: function() {
                        return map.destiny.longitude;
                    },
                    product: form_wizard.product,
                    call_type: form_wizard.call_type,
                    call_type_warranty: form_wizard.call_type_warranty,
                    distribution_center: form_wizard.distribution_center
                },
                select: function (event, ui) {
                    if (ui.item.error) {
                        return false;
                    }

                    map.manual_technical_selected(ui.item);
                }
            }).data("uiAutocomplete")._renderItem = function (ul, item) {
                if (item.id) {
                    var text = "<a>"+item.cnpj+" - "+item.name+"</a>";
                } else {
                    var text = "<a style='background-color: #F2DEDE !important; color: #a94442 !important;' >"+item.error+"</a>";
                }

                return $("<li></li>").data("item.autocomplete", item).append(text).appendTo(ul);
            };

            if (callback) {
                callback();
            }
        });
    },
    check_manual_technical: function() {
        if (map.technical_selected != null && $("#technical-"+map.technical_selected).length == 0) {
            $.ajax({
                async: false,
                url: "cockpit/ajax/map.php",
                type: "get",
                data: { ajax_technical_nearest: true, lat: map.destiny.latitude, lng: map.destiny.longitude, technical_id: map.technical_selected },
                contentType: "application/json",
                dataType: "json"
            }).done(function(response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    window.delay(function() {
                        map.manual_technical_selected(response.result[0]);
                    });
                }
            });
        }
    },
    manual_technical_selected: function(data) {
        var tr = $("tr.manual-selection");

        if (data.id == map.technical_selected) {
            $(tr).addClass("technical-selected");
        }

        $(tr).attr({
          id: "technical-"+data.id
        }).data({
            id: data.id,
            latitude: data.latitude,
            longitude: data.longitude
        });

        $(tr).find("td.name").html("\
            <button type='button' class='remove-manual-technical-selected btn btn-xs btn-danger' style='margin-right: 10px;' >\
                <span class='glyphicon glyphicon-remove-sign' ></span>\
            </button>\
            "+data.name+"\
        ");
        $(tr).find("td.technical-type").text((data["internal-technical"] == true) ? "Técnico" : "Terceiro");

        var address = data.address;

        if (data.number.length > 0) {
            address += ", "+data.number;
        }

        if (data.neighborhood.length > 0) {
            address += ", "+data.neighborhood;
        }

        address += ", "+data.city+", "+data.state;
        $(tr).find("td.address").text(address);
        $(tr).find("td.calls-limit").text(data.calls+"/"+data.maximum_amount);
        $(tr).find("td.distance").text("calculando...");
        $(tr).find("#technical-assistance-search").hide().nextAll("td").show();

        var technical_lat = data.latitude;
        var technical_lng = data.longitude;

        $("button.remove-manual-technical-selected").on("click", function() {
            if (confirm("Deseja remover o técnico ?")) {
                var tr = $("tr.manual-selection");
                var id = $(tr).data("id");

                $(tr).removeAttr("id").data({ id: null, latitude: null, longitude: null });
                $(tr).find("#technical-assistance-search").nextAll("td").text("").hide();
                $(tr).find("#technical-assistance-search").show();

                if (map.technical_selected == id) {
                    map.technical_selected       = null;
                    map.technical_maximum_amount = 0;
                    map.technical_internal       = false;
                    map.distance                 = null;
                    map.returning_distance       = null;

                    $(tr).removeClass("technical-selected");
                    form_wizard.disableNextTab();
                }

                map.remove_technical_marker(id);
                map.reload_map(true);
            }
        });

        window.delay(function() {
            $.ajax({
                url: "cockpit/ajax/map.php",
                type: "GET",
                data: { 
                    ajax_technical_distance: true,
                    destiny_lat: map.destiny.latitude,
                    destiny_lng: map.destiny.longitude,
                    technical_lat: technical_lat,
                    technical_lng: technical_lng
                },
                async: true,
                contentType: "application/json",
                dataType: "json",
                beforeSend: function() {
                    form_wizard.disableNextTab();
                    $("button.remove-manual-technical-selected").prop({ disabled: true });
                },
                timeout: 10000
            }).fail(function(response) {
                alert("Não foi possível calcular a distância, tempo limite esgotado");
                $("tr.manual-selection > td.distance").text("erro");
                $("button.remove-manual-technical-selected").prop({ disabled: false });
            }).done(function(response) {
                if (response.error) {
                    alert("Não foi possível calcular a distância");
                    $("tr.manual-selection > td.distance").text("erro");
                } else {
                    $("tr.manual-selection > td.distance").text(parseFloat(response.distance).toFixed(2)+" Km");

                    data.distance           = response.distance;
                    data.returning_distance = response.returning_distance;

                    map.add_technical_marker(data, true);
                    map.reload_map();

                    if (map.technical_selected != null) {
                        form_wizard.activeNextTab();
                    }
                }

                $("button.remove-manual-technical-selected").prop({ disabled: false });
            });
        });
    }
};
