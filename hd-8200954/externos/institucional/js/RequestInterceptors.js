angular.module("RequestInterceptors", ["ModalLoading"])
    .factory("Loading", function(ModelLoadingProvider){
        return{

                request: function(config){
                    console.log(config);
                    ModelLoadingProvider.setMessage("Carregando...");
                    ModelLoadingProvider.showModal();
                }
        };
    })
    .factory("Loaded",  function(ModelLoadingProvider){
        
       return {
           response: function(response){
                console.log(response);
               ModelLoadingProvider.hideModal();
           } 
       };
    });



