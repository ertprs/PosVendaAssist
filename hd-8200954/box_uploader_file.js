function File(tdocs, BoxUploader) {
    'use strict';
    
    const id          = tdocs.tdocs_id;
    const obs         = (JSON.parse(tdocs.obs))[0];
    let thumbnail     = null;
    let loadError     = false;
    let loading       = false;
    
    const createDiv = () => (
        $('<div></div>', {
            id: id,
            class: 'box-uploader-anexo box-uploader-carregando'
        })
    )
    
    const createTypeDiv = () => (
        $('<div></div>', {
            class: 'box-uploader-arquivo-titulo',

            html:  (obs.fabrica == 152 && BoxUploader.context == 'extrato') ?  obs.data : BoxUploader.types[obs.typeId] || 'Arquivo'            
        })
    )
    
    const createThumbnail = (res) => {
        let src       = null;
        let elem      = null;
        let html      = null;
        let elemClass = 'box-uploader-arquivo-anexo';
        
        if(res.fileType == 'image'){
            src = res.link;
            elem = '<img />';
            elemClass += ' img-rounded';
        }else{
            elem = '<span></span>';
            
            switch (res.fileType) {
                case 'audio':
                case 'video':
                    html = '<i class="fa fa-play" ></i>';
                    break;
                case 'pdf':
                    html = '<img src="imagens/pdf_transparente.jpg" width="84px">';
                    break;
                default:
                    html = '<i class="fa fa-file-image"></i>';
                    break;
            }
        }
        
        thumbnail = $(elem, {
            class: elemClass,
            src: src,
            html: html,
            data: {
                fileType: res.fileType,
                link: res.link,
                fileName: res.file_name,
                id: id
            },
            alt: res.file_name,
            title: res.file_name
        });
    }
    
    this.id = () => id;
    
    this.render = () => {
        const e = createDiv();
        
        if (thumbnail === null) {
            e.append('<i class="fa fa-spinner fa-pulse fa-2x"></i>');
        } else if (loadError === true) {
            e.append('<span class="label label-important">Erro</span>');
        } else {
            $(e).prepend(createTypeDiv());
            
            const text = $(thumbnail).attr('alt');
            let description = null;
            
            if (typeof obs.descricao != 'undefined' && obs.descricao.length > 0) {
                description = (obs.descricao.length > 15) ? `${obs.descricao.substr(0, 15)}...` : obs.descricao;
                $(thumbnail).attr({ title: text+'\n'+obs.descricao });
            }
                        
            $(e).append(thumbnail);
            
            if (BoxUploader.edit === true) {
                const deleteButton = $('<button></button>', {
                    type: 'button',
                    class: 'btn btn-block btn-mini btn-danger btn-delete-file',
                    html: $('<i></i>', { class: 'fa fa-trash' }),
                    title: 'Deletar arquivo'
                });
            
                $(deleteButton).on('click', () => {
                    if (confirm('Deseja realmente deletar o arquivo?')) {
                        this.delete();
                    }
                });
                
                $(e).append(deleteButton);
            }
            
            $(e).append($('<span></span>', {
                class: 'box-uploader-arquivo-nome',
                text: (text.length > 15) ? `${text.substr(0, 15)}...` : text
            }));
            
            if (description !== null) {
                $(e).find('.box-uploader-arquivo-nome').append(`<br />${description}`);
            }
        }
        
        $(thumbnail).on('click', (e) => {
            let data = $(e.target).data();
            let abre_modal = true;
            
            if (Object.keys(data).length == 0) {
                data = $(e.target).parent().data();
            }
            
            $(BoxUploader.root).find('.modal-video, .modal-audio, .modal-image, .modal-file').hide();
            
            const type = BoxUploader.types[obs.typeId] || 'Arquivo';
            
            let source, video, audio, link;

            //caso tenha mais que um box uploader na página
            if ($(".div_boxuploader_principal").length > 1) {
                data.fileType = "file";
            }
            
            switch (data.fileType) {
                case 'image':
                    $(BoxUploader.root).find('.modal-image').find('img').attr({ src: data.link });
                    $(BoxUploader.root).find('.modal-image').show();
                    break;
                    
                case 'video':

                    // Extensão não suportada pelo Browser, só libera o Download 
                    let extensao = data.fileName.split(".")

                    if ($.inArray("wmv", extensao) != -1) {
                       link = `plugins/fileuploader/fileuploader-download-arquivo.php?hash=${data.id}&l=${btoa(data.link)}&a=${data.fileName}`;
                        $(BoxUploader.root).find('.modal-file').find('a').attr({ href: link }).find('span').text(data.fileName);
                        $(BoxUploader.root).find('.modal-file').show();
                        break; 
                    }

                    source = $('<source></source>', {
                        src: data.link
                    });
                    video = $('<video></video>', {
                        controls: true,
                        css: {
                            height: `${window.innerHeight - 120}px`,
                            width: '100%'
                        }
                    });
                    $(video).html(source);
                    $(BoxUploader.root).find('.modal-video').html(video);
                    $(BoxUploader.root).find('.modal-video').show();
                    break;
                    
                case 'audio':
                    source = $('<source></source>', {
                        src: data.link
                    });
                    audio = $('<audio></audio>', {
                        controls: true
                    });
                    $(audio).html(source);
                    $(BoxUploader.root).find('.modal-audio').html(audio);
                    $(BoxUploader.root).find('.modal-audio').show();

                    var text = 'text/plain';
                    const a = document.createElement("a");
                    a.style.display = "none";

                    // Set the HREF to a Blob representation of the data to be downloaded
                    a.href = window.open(
                        data.link,"_blank"
                    );

                    // Use download attribute to set set desired file name
                    a.setAttribute("download", data.fileName);

                    // Trigger the download by simulating click
                    a.click();

                    // Cleanup

                    break;
            
                default:
                    link = `plugins/fileuploader/fileuploader-download-arquivo.php?hash=${data.id}&l=${btoa(data.link)}&a=${data.fileName}`;
                    abre_modal = false;
                    window.open(link)
                    //HD-7186754
                    /*$(BoxUploader.root).find('.modal-file').find('a').attr({ href: link }).find('span').text(data.fileName);
                    $(BoxUploader.root).find('.modal-file').show();*/
                    break;
            }
            
            if (typeof this.open === 'function' && abre_modal) {
                this.open(BoxUploader, data, type);
            }
        });
        
        return e;
    }
    
    this.open = null;
    
    this.load = async () => {
        if ((thumbnail === null || loadError === true) && loading === false) {
            loading = true;
            
            $.ajax({
                async: true,
                url: `plugins/fileuploader/fileuploader-iframe-v2.php?loadTDocs=${id}`,
                type: 'get',
                timeout: 60000
            })
            .fail(() => {
                loadError = true;
                loading = false;
                refresh()
            })
            .done((res, req) => {
                if (req == 'success') {
                    if (typeof res.link == 'undefined') {
                        this.remove();
                        refresh()
                    } else {
                        createThumbnail(res);
                        refresh()
                    }
                } else {
                    loadError = true;
                    refresh()
                }
                
                loading = false;
            })
        }
    }
    
    const refresh = () => BoxUploader.refreshFile(this)
    
    this.remove = () => $(BoxUploader.root).find(`#${id}`).remove();
    
    this.delete = () => (
        new Promise((resolve, reject) => {
            $.ajax('plugins/fileuploader/fileuploader-iframe-v2.php?removeFile=file', {
                async: true,
                timeout: 60000,
                type: 'POST',
                data: {
                    id: id, 
                    referencia: BoxUploader.referenceId,
                    hashTemp: BoxUploader.hashTemp
                },
                beforeSend: () => {
                    $(BoxUploader.root).find(`#${id}`).find('.btn-delete-file').prop({ disabled: true }).html('<i class="fa fa-spinner fa-pulse"></i>');
                }
            })
            .fail((res) => {
                $(BoxUploader.root).find(`#${id}`).find('.btn-delete-file').prop({ disabled: false }).html('<i class="fa fa-trash"></i>');
                reject(res);
            })
            .done((res, req) => {
                if (req == 'success') {
                    this.remove();
                    
                    if (typeof BoxUploader.callback != 'undefined' && BoxUploader.callback.delete !== null) {
                        BoxUploader.callback.delete(res, this)
                        .then(() => resolve(res));
                    } else {
                        resolve(res);
                    }
                } else {
                    $(BoxUploader.root).find(`#${id}`).find('.btn-delete-file').prop({ disabled: false }).html('<i class="fa fa-trash"></i>');
                    reject(res);
                }
            });
        })
    )
}
