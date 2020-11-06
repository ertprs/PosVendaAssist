angular.module("Institucional", ["ngRoute",  "Auth", "InstitucionalController", "TreinamentoPostos", "RequestInterceptors"])
.config(function($routeProvider, $httpProvider) {
	$routeProvider
	.when("/statusos/:token?", {
		templateUrl: "partials/statusos.html",
		controller: "StatusOsController"
	})
	.when("/maparede/:token?", {
		templateUrl: "partials/maparede.html",
		controller: "MapaRedeController"

	}).when("/treinamentopostos/:token?",{
        
        templateUrl: "partials/treinamento-postos.html",
        controller:"TreinamentoPostosController"
    });
  // $httpProvider.interceptor.push(Loading); 

});
