
var TreinamentoPostosModule = angular.module("TreinamentoPostos",["RestClientProvider", "ModalLoading"])

TreinamentoPostosModule.controller("TreinamentoPostosController", function($scope,token, resourcesRoutes, ModalLoadingProvider){
    $("#msgErro").hide();


    ModalLoadingProvider.showModal();
    $scope.cidade="";
    $scope.regiao =null;
    $scope.estado=null;
    
    resourcesRoutes.regiao.getAll()
        .$promise.then(
            function(result){
                $scope.regioes = result; 
                ModalLoadingProvider.hideModal();
            });


    resourcesRoutes.estado.getAll()
        .$promise.then(
            function(result){
                $scope.estados = result;
                ModalLoadingProvider.hideModal();
            });

    $scope.getEstados = function(){
        $scope.completing = false;
        $scope.cidade = null;
        $scope.estado = null;
        ModalLoadingProvider.showModal();
        resourcesRoutes.estado.filterByRegion({regiao:$scope.regiao.regiao})
            .$promise.then(
                function(result){
                    $scope.estados = result;
                    ModalLoadingProvider.hideModal();
                });

    }
    $scope.getCidades = function(){

        $scope.completing = false;
        $scope.cidade = null;
        ModalLoadingProvider.showModal()
        resourcesRoutes.cidade.getFilteredByUf({uf:$scope.estado.estado})
            .$promise.then(
                    function(result){
                        $scope.cidades = result;
                        ModalLoadingProvider.hideModal();   
                    });
    }
    $scope.completing = false;
    $scope.complete = function(cidade){
       if(cidade.length == 0){
           $scope.completing = false;
       }else{
           $scope.completing = true;
       }
    }
    $scope.selectedCity = function(cidade){
        $scope.cidade = cidade; 
        $scope.completing = false
    }
    $scope.getTreinamentos = function(){
        ModalLoadingProvider.showModal();
        $scope.treinamentos = [];
        if($scope.cidade != null){
            $scope.treinamentos = resourcesRoutes.treinamento.getFilteredByCity({id: $scope.cidade.id});
        }else if($scope.estado != null){
            $scope.treinamentos = resourcesRoutes.treinamento.getFilteredByState({uf: $scope.estado.estado});
        }else if($scope.regiao != null ){

            $scope.treinamentos = resourcesRoutes.treinamento.getFilteredByRegion({regiao: $scope.regiao.regiao});
        } 

        $scope.treinamentos.$promise.then(function(result){
            ModalLoadingProvider.hideModal();
            $scope.treinamentos = result;
            if($scope.treinamentos.length > 0){
                
                $scope.loaded = true;
            }else{

                alert("Nenhum registro encontrado");
            }
        });
        
    }
});
