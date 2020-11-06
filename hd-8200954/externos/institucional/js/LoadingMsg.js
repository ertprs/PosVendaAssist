var loadingProvider = {
            msg: "Carregando...",
            defaultMsg: "Carregando...",
            element: null,
            setMessage: function(message){ 
                            this.msg = '\ <div id="alert-message" class="modal fade"> \n\ <div class="modal-dialog"> \n\ <div class="modal-content"> \n\ <div class="modal-header"> \n\ <h4 class="modal-title">Por favor aguarde</h4>\n\ </div> \n\ <div class="modal-body"> \n\ <p>' + message + '</p> \n\ </div> \n\ <div class="modal-footer"> \n\  </div>\n\ </div>\n\ </div>\n\ </div>';
            },
            show : function(){
                  if(this.element == null){
                      var spanContainer = $("<span>").attr({
                          id: "spanContainer"
                      });
                      var imgLoading = $("<img>").attr({
                         
                          id: "loading-img",
                          src: "../elgin_source/ajax-loader.gif"
                      }); 
                          spanContainer.append(imgLoading).append("Carregando...");
                      
                      this.element = spanContainer
                      $("#loading").append(this.element);
                  }
                  
                  $("#spanContainer").show();

            },
            hide: function(){
                  $("#spanContainer").hide();
            }

        };
