function BoxUploader(options) {
    'use strict';
    
    this.constructor.tdocs       = [];
    this.constructor.types       = {};
    this.constructor.typesExibe  = options.typesExibe;
    this.constructor.bootstrap   = false;
    this.constructor.context     = options.context; 
    this.constructor.currentPage = options.currentPage; 
    this.constructor.referenceId = options.referenceId;
    this.constructor.hashTemp    = Boolean(options.hashTemp);
    this.constructor.callback    = { delete: null, upload: null };
    this.constructor.edit        = (typeof options.edit != 'undefined') ? options.edit : true;
    this.constructor.root        = $(`#${options.root}`);
    
    const root             = this.constructor.root;
    const context          = this.constructor.context;
    const referenceId      = this.constructor.referenceId; 
    const currentPage      = this.constructor.currentPage; 
    const hashTemp         = this.constructor.hashTemp ? '&hash_temp=true' : '';
    const urlFileuploader  = `plugins/fileuploader/fileuploader-iframe-v2.php?context=${context}&reference_id=${referenceId}${hashTemp}&current_page=${currentPage}`;
    const filesDiv         = $(root).find(".box-uploader-anexos");
    const btnFileuploader  = $(root).find('.btn-call-fileuploader');
    const descriptionField = $(root).find('#campo_descricao');
    
    this.init = async () => {
        if (!$) {
            throw new Error('jQuery is undefined')    
        }
        
        if (!Shadowbox) {
            throw new Error('Shadowbox is undefined');
        } else {
            Shadowbox.init();
        }
        
        if (typeof $().modal == 'function') {
            BoxUploader.bootstrap = true;
        } else {
            const link = $('<link />', {
                href: 'box_uploader_shadowbox.css',
                rel: 'stylesheet'
            });
            $('body').append(link);
        }
        
        await loadTypes().catch(() => {
            $(root).find('.box-uploader-loading').hide();
            $(root).find('.box-uploader-component').html('\
                <div class="alert alert-danger" style="margin-bottom: 13px;">\
                    <strong>Erro ao carregar componente</strong>\
                </div>\
            ').fadeIn(300);
        });
        await events();
        
        $(root).find('.box-uploader-loading').hide();
        $(root).find('.box-uploader-component').fadeIn(300);
    }
   
	this.showFile = async() => {
		await refresh();
	}

    const loadTypes = () => new Promise((resolve, reject) => {
        $.ajax({
            async: true,
            type: 'get',
            url: urlFileuploader+'&ajax=get_types',
            timeout: 60000
        }).fail(() => {
            reject();
        }).done((res, req) => {
            if (req == 'success') {
                res = JSON.parse(res);
                
                if (res == null || res.length == 0) {
                    reject();
                } else {
                    res.forEach((type, i) => BoxUploader.types[type.value] = type.label);
                    resolve();
                }
            } else {
                reject();
            }
        })
    })
    
    const events = () => new Promise((resolve, reject) => {
        $(btnFileuploader).on('click', (e) => {
            let url = urlFileuploader;
            
            if ($(descriptionField).length > 0) {
                const description = $(descriptionField).val().trim();
                
                if (description !== null && description.length > 0) {
                    url += `&descricao=${description}`;
                }
            }
           
	    $("#openBoxUploader").val("t");
	    refresh();

            Shadowbox.open({
                content: url,
                player: 'iframe',
                height: 600,
                width: 950,
		options:{
			onClose: function(){
				$("#openBoxUploader").val("");
				refresh();
			}
		}
            });
        });
        
        if (!BoxUploader.bootstrap) {
            $(document).on('click', '#sb-container .modal-header .close', () => Shadowbox.close());
            $(document).on('click', '#sb-container .modal-footer .close', () => Shadowbox.close());
        }
       
	let openBoxUploader = $("#openBoxUploader").val();

	if((openBoxUploader == "t" && this.constructor.hashTemp == true) || this.constructor.hashTemp == "" ){
        	refresh();
	}
        resolve();
    })
    
    this.constructor.refreshFile = (file) => {
        $(BoxUploader.root).find(`#${file.id()}`).replaceWith(file.render());
    }
    
    const refresh = () => new Promise((resolve, reject) => {

        $.ajax({
            async: true,
            type: 'get',
            url: urlFileuploader+'&ajax=get_tdocs',
            timeout: 60000
        }).fail((res, req) => {
            setTimeout(() => refresh(), 30000);
            reject();
        }).done((res, req) => {
            if (req == 'success' && typeof res == 'object') {
                res.forEach((item, k) => {
                    if (BoxUploader.tdocs.map((file) => file.id()).indexOf(item.tdocs_id) == -1)  {
                        const file = new File(item, BoxUploader);
                        
                        file.open = (BoxUploader, data, type) => {
                            if (data.fileType == 'file') {
                                $(BoxUploader.root).find('.modal-view-file').find('.modal-header').find('h3').text(`${type}`);
                            } else {
                                $(BoxUploader.root).find('.modal-view-file').find('.modal-header').find('h3').text(`${type} - ${data.fileName}`);
                            }
                            
                            $(BoxUploader.root).find('.modal-view-file').removeClass('modal-full-screen');
                            
                            switch (data.fileType) {
                                case 'image':
                                case 'video':
                                    $(BoxUploader.root).find('.modal-view-file').addClass('modal-full-screen');
                                    break;
                            }
                            
                            if (BoxUploader.bootstrap) {
                                $(BoxUploader.root).find('.modal-view-file').modal();
                            } else {
                                if (['image', 'video'].indexOf(data.fileType) != -1) {
                                    $('#sb-wrapper').removeClass('box-uploader-sb-wrapper-s');
                                    $('#sb-wrapper-inner').removeClass('box-uploader-sb-wrapper-inner-s');
                                    $('#sb-wrapper').addClass('box-uploader-sb-wrapper-fs');
                                    $('#sb-wrapper-inner').addClass('box-uploader-sb-wrapper-inner-fs');
                                    $(BoxUploader.root).find('.modal-view-file').find('.close').show();
                                } else {
                                    $('#sb-wrapper').removeClass('box-uploader-sb-wrapper-fs');
                                    $('#sb-wrapper-inner').removeClass('box-uploader-sb-wrapper-inner-fs');
                                    $('#sb-wrapper').addClass('box-uploader-sb-wrapper-s');
                                    $('#sb-wrapper-inner').addClass('box-uploader-sb-wrapper-inner-s');
                                    $(BoxUploader.root).find('.modal-view-file').find('.close').hide();
                                }
                                
                                Shadowbox.open({
                                    content: $(BoxUploader.root).find('.modal-view-file').html(),
                                    player: 'html',
                                    options: {
                                        onClose: function() {
                                            $('#sb-wrapper').removeClass('box-uploader-sb-wrapper-s');
                                            $('#sb-wrapper-inner').removeClass('box-uploader-sb-wrapper-inner-s');
                                            $('#sb-wrapper').removeClass('box-uploader-sb-wrapper-fs');
                                            $('#sb-wrapper-inner').removeClass('box-uploader-sb-wrapper-inner-fs');
                                        }
                                    }
                                });
                            }
                        }
                        
                        BoxUploader.tdocs.push(file);
                        $(filesDiv).append(file.render());
                    }
                });
                
                res = res.map((item) => item.tdocs_id);
                BoxUploader.tdocs = BoxUploader.tdocs.filter((file) => {
                    if (res.indexOf(file.id()) == -1) {
                        file.remove();
                        return false;
                    }
                    
                    return true;
                });
                
                (async () => {
                    BoxUploader.tdocs.forEach((file) => file.load())
                    //setTimeout(() => refresh(), 30000);
                    resolve();
                })();
            } else {
                setTimeout(() => refresh(), 30000);
                reject();
            }
        });
    });

    this.constructor.deleteFile = (tdocs_id) => {
        const file = BoxUploader.tdocs.filter((file) => {
            if(file.id() == tdocs_id){
                return true;
            } else {
                return false;
            } 
        });
        
        return file[0].delete();
    }
    
    this.constructor.registerCallback = (action, callback) => {
        if (typeof BoxUploader.callback[action] == 'undefined') {
            throw new Error('Invalid action');
        }
        
        BoxUploader.callback[action] = callback;
    }
}
