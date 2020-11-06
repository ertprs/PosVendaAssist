
angular.module("RestClientProvider", ["ngResource"])
    .factory("resourcesRoutes", function($resource){
        var host = "http://api2.telecontrol.com.br/institucional";
        return {
            cidade: $resource(host + "/cidade/cidade/:id",{}, 
                        {
                            getFilteredByUf: {
                                url: host + "/cidade/uf/:uf", 
                                method: "GET",
                                isArray: "true" 
                            },
                            getFilteredByRegiao:{ 
                                url: host + "/cidade/uf/:uf/regiao/:regiao",
                                method: "GET",
                                isArray:true
                            }
                        } 
                            
                    ),
            estado: $resource(host + "/estado/:uf", {},{
                
                getAll: {
                    url: host + "/estado/",
                    method: "GET",
                    isArray:true

                },
                filterByRegion: {
                    url: host + "/estado/regiao/:regiao",
                    method: "GET",
                    isArray:true
                },
                filterByState:{
                    url: host + "estado/:uf",
                    method: "GET",
                    isArray: true

                }
            }),
            regiao: $resource(host + "/regiao/fabrica/:fabrica/id/:id", {fabrica:"117", id:"@id"},
                        {
                            getAll:{ 
                                url: host + "/regiao/fabrica/:fabrica",
                                method: "GET", 
                                params: {fabrica : 117},
                                isArray:true
                            }
                        }
                    ),
            treinamento: $resource(host +"/treinamento/fabrica/:fabrica", {fabrica: "117"},
                    
                    {
                        getFilteredByRegion: {
                            url: host + "/treinamento/fabrica/:fabrica/regiao/:regiao",
                            method: "GET",
                            params: {fabrica: 117, regiao:"@regiao"},
                            isArray: true
                        },
                        getFilteredByState:{
                            url: host + "/treinamento/fabrica/:fabrica/uf/:uf",
                            method: "GET",
                            params: {fabrica: 117, uf:"@uf"},
                            isArray: true


                        },
                        getFilteredByCity:{
                            url: host + "/treinamento/fabrica/:fabrica/cidade/:id",
                            method: "GET",
                            params: {fabrica: 117, id:"@id"},
                            isArray: true


                        }
                    }
            )

        };
     })
