<?php
if (empty($login_fabrica) || (empty($login_admin) && empty($login_posto))) {
    die;
}

$plugins = [
    'font_awesome'
];

include 'plugin_loader.php';

include_once __DIR__.'/fn_traducao.php';

$boxUploader["titulo"] = (!empty($boxUploader["titulo"])) ? $boxUploader["titulo"] : traduz("Anexo(s)");
?>

<link rel='stylesheet' href='box_uploader.css' />
<style>
#modal-<?=$boxUploader['div_id']?> .modal-open-file {
    display: none;
}

#modal-<?=$boxUploader['div_id']?> .box-uploader-file-list {
    display: none;
    color: #3a87ad !important;
    opacity: unset;
    float: left;
}

#modal-<?=$boxUploader['div_id']?> .close {
    color: #b94a48;
    opacity: unset;
}

#modal-<?=$boxUploader['div_id']?> .modal-full-screen {
    display: none;
}

#modal-<?=$boxUploader['div_id']?> .modal-body {
    padding: 30px 0;
}

#modal-<?=$boxUploader['div_id']?> .box-uploader-loading {
    text-align: center;
}

#modal-<?=$boxUploader['div_id']?> .modal-open-file {
    display: none;
}

#modal-<?=$boxUploader['div_id']?> .modal-image,
#modal-<?=$boxUploader['div_id']?> .modal-audio,
#modal-<?=$boxUploader['div_id']?> .modal-video,
#modal-<?=$boxUploader['div_id']?> .modal-file {
    display: none;
}

#modal-<?=$boxUploader['div_id']?> .modal-file,
#modal-<?=$boxUploader['div_id']?> .modal-audio {
    text-align: center;
}
</style>

<div id='modal-<?=$boxUploader['div_id']?>'>
    <button type="button" class="btn btn-primary btn-box-uploader-viewer">
        <i class="icon-picture icon-white"></i> <?=traduz("Ver Anexo(s)")?>
    </button>

    <div class="modal hide fade modal-full-screen">
        <div class="modal-header">
            <button type='button' class='close pull-left' data-dismiss='modal' aria-hidden='true'><i class='fa fa-arrow-left'></i> <?=traduz('Voltar')?></button>
            <h3 style='text-align: right;'><?=$boxUploader["titulo"]?></h3>
        </div>
        <div class="modal-body tac">
            <div class='box-uploader-component'>
                <div class="row-fluid danexo" >
                    <div class="span1" ></div>
                    <div class="box-uploader-anexos span10" ></div>
                </div>
            </div>
            <div class='box-uploader-loading'>
                <i class='fa fa-spinner fa-pulse fa-5x'></i>
            </div>
            <div class='modal-open-file'>                
                <div class='modal-image'>
                    <img src='' class='img-rounded' />
                </div>
                <div class='modal-video'></div>
                <div class='modal-audio'></div>
                <div class='modal-file'>
                    <a href='' target='_blank'>
                        <i class='fa fa-download fa-5x'></i>
                        <br />
                        <span></span>
                    </a>
                </div>
            </div>
        </div>
        <div class="modal-footer" >
            <button type='button' class='close box-uploader-file-list'>
                <i class='fa fa-arrow-left'></i> <?=traduz('Voltar a lista de arquivos')?>
            </button>
        </div>
    </div>
</div>

<script src='box_uploader_file.js'></script>
<script>
function BoxUploaderViewer(options) {
    'use strict';
    
    this.root        = $(`#${options.root}`);
    this.referenceId = options.referenceId;
    this.hashTemp    = false;
    this.types       = [];
    this.edit        = false;
    this.bootstrap   = true;
    
    var loaded = false;
    
    const root            = this.root;
    const context         = options.context;
    const tdocs           = [];
    const button          = $(root).find('.btn-box-uploader-viewer');
    const urlFileuploader = `plugins/fileuploader/fileuploader-iframe-v2.php?context=${context}&reference_id=${this.referenceId}`;
    const title           = '<?=$boxUploader["titulo"]?>';
    
    this.init = () => {
        $(button).on('click', async () => {
            const modal = $(root).find('.modal');     
            $(modal).modal();
            
            if (loaded === false) {
                await loadTypes().catch(() => {
                    $(root).find('.box-uploader-loading').hide();
                    $(root).find('.box-uploader-component').html('\
                        <div class="alert alert-danger" style="margin-bottom: 13px;">\
                            <strong>Erro ao carregar componente</strong>\
                        </div>\
                    ').fadeIn(300);
                });
                
                refresh().then(() => loaded = true)
                .catch(() => {
                    $(root).find('.box-uploader-component').html('\
                        <div class="alert alert-danger" style="margin-bottom: 13px;">\
                            <strong>Erro ao carregar componente</strong>\
                        </div>\
                    ').fadeIn(300);
                });
                
                $(root).find('.box-uploader-file-list').on('click', () => {
                    $(root).find('.modal-open-file').hide();
                    $(root).find('.box-uploader-file-list').fadeOut(300);
                    $(root).find('.box-uploader-component').fadeIn(300);
                    $(root).find('.modal-header h3').text(title);
                });
                
                $(root).find('.box-uploader-loading').hide();
                $(root).find('.box-uploader-component').fadeIn(300);
            }
        });
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
                    res.forEach((type, i) => this.types[type.value] = type.label);
                    resolve();
                }
            } else {
                reject();
            }
        })
    });
    
    const refresh = () => new Promise((resolve, reject) => {
        $.ajax({
            async: true,
            type: 'get',
            url: urlFileuploader+'&ajax=get_tdocs',
            timeout: 60000
        }).fail((res, req) => {
            reject();
        }).done((res, req) => {
            if (req == 'success' && typeof res == 'object') {
                res.forEach((item, k) => {
                    const file = new File(item, this);
                    
                    file.open = (BoxUploader, data, type) => {
                        if (data.fileType == 'file') {
                            $(BoxUploader.root).find('.modal-header h3').text(`${type}`);
                        } else {
                            $(BoxUploader.root).find('.modal-header h3').text(`${type} - ${data.fileName}`);
                        }
                        
                        $(BoxUploader.root).find('.box-uploader-file-list').fadeIn(300);
                        $(BoxUploader.root).find('.box-uploader-component').hide();
                        $(BoxUploader.root).find('.modal-open-file').fadeIn(300);
                    }
                    
                    tdocs.push(file);
                    $(root).find('.box-uploader-anexos').append(file.render());
                });
                
                (async () => {
                    tdocs.forEach((file) => file.load())
                    resolve();
                })();
                resolve();
            } else {
                reject();
            }
        });
    });
    
    this.refreshFile = (file) => {
        $(root).find(`#${file.id()}`).replaceWith(file.render());
    }
}

$(function() {
    const <?='boxuploader'.$boxUploader['div_id']?> = new BoxUploaderViewer({
        root: 'modal-<?=$boxUploader['div_id']?>',
        referenceId: '<?=$boxUploader['unique_id']?>',
        context: '<?=$boxUploader['context']?>'
    });
    <?='boxuploader'.$boxUploader['div_id']?>.init();
});
</script>