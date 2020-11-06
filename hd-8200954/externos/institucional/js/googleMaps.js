var mapsInit = {
        map: null,
        directionsDisplay: null,
        directionsService: null,
        cidadesAtendidasPorPostos:null,
        searchBox: null,
        destination :null,
        origin: null,
        travelMode:null,
        
        getMyOptions: function (){

           var latlng = new google.maps.LatLng(-15, -55);
           return {
                zoom: 5,
                center: latlng,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                panControl:true,
                zoomControl:true,
                mapTypeControl:true,
                scaleControl:true,
                streetViewControl:false,
                overviewMapControl: true
           }

       },
        getMap: function(){
            if(this.map == null){
                this.directionsDisplay = new google.maps.DirectionsRenderer();

                this.directionsService = new google.maps.DirectionsService();

                this.map = new google.maps.Map(document.getElementById('map'),this.getMyOptions());

                this.directionsDisplay.setMap(this.map);
                this.directionsDisplay.setPanel(document.getElementById("directionsPanel"));

                this.initSearchBox();
                this.setCalcRouteEvent();
            }
            return this.map;

        },
        initSearchBox: function(){

             this.searchBox = new google.maps.places.SearchBox(document.getElementById("search_box_origin") );
             var self = this;
             google.maps.event.addListener(this.searchBox, "places_changed", function(a){
                 self.origin = this.getPlaces()[0].geometry.location;
             });
             this.map.controls[google.maps.ControlPosition.RIGHT_TOP].push(document.getElementById("inputs_container"));
        },
        setCalcRouteEvent: function(){
            var self = this;
            $(document).on("click", ".calc", function(){

                var latitude = $(this).parents("ul.list-group").first().find("input[name=latitude]").val();
                var longitude = $(this).parents("ul.list-group").first().find("input[name=longitude]").val();

                var markerLatLng = new google.maps.LatLng(longitude, latitude);

                self.route(null, markerLatLng);
          
            }); 
            $(document).on("click", ".infoWindowCalc", function () {

                self.route(null, null);
                
            });
        },
        route: function(origem, destino){
            if(this.calcRoute(origem, destino)){
                $("#list-group-item").hide()
                $("#directionsPanel").show();
            }
        },
        calcRoute: function (origem, destino) {
            this.origin = (origem==null) ? this.getOrigin() : origem;
            this.destination = (destino==null) ? this.getDestination() : destino; 
            
            if(this.directionsDisplay == null){

                this.directionsDisplay = new google.maps.DirectionsRenderer();
                this.directionsDisplay.setPanel(document.getElementById("directionsPanel"));
            }


            if(this.destination == null || this.origin == null){
                
                alert("Informe seu Endereço de Origem ou Clique em um posto autorizado");
                $("#search_box_origin").focus();
                return false;
            }else{
                var request = {
                    origin:this.origin, 
                    destination:this.destination,
                    travelMode: google.maps.DirectionsTravelMode[this.travelMode]
                };

                var self = this;
                this.directionsService.route(request, function(response, status) {
                    if (status == google.maps.DirectionsStatus.OK) {
                        self.directionsDisplay.setDirections(response);
                        return true;
                    } else {
                        return false;
                        alert("Não foi possível traçar a rota até este local");
                    }

                });
            }
        },
        getOrigin: function(){
            return this.origin;
        },
        getDestination: function(){
            return this.destination;
        },
        estados:[
            {value:'AC',descricao:'Acre'},
            {value:'AL',descricao:'Alagoas'},
            {value:'AM',descricao:'Amazonas'},
            {value:'AP',descricao:'Amapá'},
            {value:'BA',descricao:'Bahia'},
            {value:'CE',descricao:'Ceará'},
            {value:'DF',descricao:'Distrito Federal'},
            {value:'ES',descricao:'Espírito Santo'},
            {value:'GO',descricao:'Goiás'},
            {value:'MA',descricao:'Maranhão'},
            {value:'MG',descricao:'Minas Gerais'},
            {value:'MS',descricao:'Mato Grosso do Sul'},
            {value:'MT',descricao:'Mato Grosso'},
            {value:'PA',descricao:'Pará'},
            {value:'PB',descricao:'Paraíba'},
            {value:'PE',descricao:'Pernambuco'},
            {value:'PI',descricao:'Piauí'},
            {value:'PR',descricao:'Paraná'},
            {value:'RJ',descricao:'Rio de Janeiro'},
            {value:'RN',descricao:'Rio Grande do Norte'},
            {value:'RO',descricao:'Rondônia'},
            {value:'RR',descricao:'Roraima'},
            {value:'RS',descricao:'Rio Grande do Sul'},
            {value:'SC',descricao:'Santa Catarina'},
            {value:'SE',descricao:'Sergipe'},
            {value:'SP',descricao:'São Paulo'},
            {value:'TO',descricao:'Tocantins'}
        ],
        getInfoWindowContent: function(dadosPosto){
            return "<div id='container' >"+
                        "<p><b>Nome Posto:</b> "+dadosPosto.posto+" </p>"+
                        "<p><b>Cidade:</b> "+dadosPosto.cidade+"</p>"+
                        "<p><b>Endereço:</b> "+dadosPosto.endereco+", "+ dadosPosto.numero+"</p>"+
                        "<p><b>Estado:</b> "+dadosPosto.estado+" </p>"+
                        //"<p><b>Atende Cidade de:</b> "+dadosPosto.cidade_atendida+" </p>"+
                        "<p><b>Telefone:</b> "+dadosPosto.telefone+" </p>"+
                        "<p><a class='infoWindowCalc'>[Calcular Rota para este Posto]</a> </p>"+
                
                    "</div>";

        },
        getListContent: function(dadosPosto){
            return "<div class='panel-heading'>"+dadosPosto.posto+"</div>"+
                    "<ul class='list-group'>"+
                        "<input type='hidden' name='latitude' value='"+dadosPosto.localizacao.latitude+"' />"+
                        "<input type='hidden' name='longitude' value='"+dadosPosto.localizacao.longitude+"'/>"+
                        "<li class='list-group-item'><b>Cidade: </b>"+dadosPosto.cidade+"</li>"+
                        "<li class='list-group-item'><b>Endereço: </b>"+dadosPosto.endereco+","+dadosPosto.numero+"</li>"+
                        "<li class='list-group-item'><b>Estado: </b>"+dadosPosto.estado+"</li>"+
                        //"<li class='list-group-item'><b>Atende Cidade de: </b>"+dadosPosto.cidade_atendida+"</li>"+
                        "<li class='list-group-item'><b>Telefone: </b>"+dadosPosto.telefone+"</li>"+
                        "<li class='list-group-item'><a href='#map_container' class='calc'>[Calcular Rota para este Posto]</a></li>"+
                    "</ul> ";
        }
};
