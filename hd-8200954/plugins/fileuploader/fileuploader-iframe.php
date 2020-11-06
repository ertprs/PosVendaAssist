<?php
    include 'TdocsMirror.php';
    include __DIR__.'/../../controllers/ImageuploaderTiposMirror.php';

    $areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
    $admin_es = preg_match('/\/admin_es\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

    include __DIR__.'/../../dbconfig.php';
    include __DIR__.'/../../includes/dbconnect-inc.php'; 

    if ($areaAdmin === true) {
        include __DIR__.'/../../admin/autentica_admin.php';
        include_once __DIR__.'/../../fn_traducao.php';
        
        $usuarioInformacoes = array(
            "admin" => $login_admin
        );
    } elseif($admin_es){
        $admin_privilegios = "call_center, info_tecnica";
        include __DIR__.'/../../admin_es/autentica_admin.php';
        include_once __DIR__.'/../../fn_traducao.php';
        
        $usuarioInformacoes = array(
            "admin" => $login_admin
        );
    } else {
        include __DIR__.'/../../autentica_usuario.php';

        $usuarioInformacoes = array(
            "posto" => ["posto" => $login_admin]
        );
    }
    
    $imgExtensions = array("png","jpg","jpeg","gif","bmp","exif","tiff","ico","PNG","JPG","JPEG","GIF");
    
    $hash_temp = array_key_exists("hash_temp", $_GET) ? "true" : "false";    

    
    $descricao = array_key_exists("descricao", $_GET) ? "true" : "false";

    if ($descricao == "true"){
        $dados_descricao = $_GET['descricao'];
    }

    // ------------------------------ AJAX---------------------------------
    $referencia = $_GET['reference_id'];

    if(array_key_exists("loadTDocs", $_GET)){

        $tdocsMirror = new TdocsMirror();
        try{
            $response = $tdocsMirror->get($_GET['loadTDocs']);
        $sql = "SELECT obs FROM tbl_tdocs WHERE tdocs_id = '{$_GET['loadTDocs']}'";
        $res = pg_query($con, $sql);

        $obs = json_decode(pg_fetch_result($res, 0, 'obs'), true);
            $file = $obs[0]['filename'];
    
            $file = explode(".",$file);
            $len = count($file);
        
            if(in_array($file[$len-1], $imgExtensions)){
                $response['fileType'] = "image";
            }else{
                $response['fileType'] = "file";
        }

        $response['file_name'] = $obs[0]['filename'];
            
            header('Content-Type: application/json');
            echo json_encode($response);
        }catch(\Exception $e){
            header('Content-Type: application/json');
            echo json_encode(array("exception" => utf8_encode(traduz("Imagem não encontrada"))));
            exit;
        }
        exit;
    }
    if(array_key_exists("removeFile", $_GET)){
        $tdocsId = $_POST['id'];
        $reference = $_POST['referencia'];

        if($_POST['hashTemp'] == "true"){
            $sql = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE hash_temp = $1 and tdocs_id = $2";
            $stmt = pg_prepare($con, "update", $sql);
            $result = pg_execute($con, "update", array($reference,$tdocsId));
        }else{
            $sql = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE tdocs_id = $1 AND referencia_id = $2";
            $stmt = pg_prepare($con, "update", $sql);
            $result = pg_execute($con, "update", array($tdocsId, $reference));
        }

        

        if(pg_last_error($con) == false){
            header('Content-Type: application/json');
            echo json_encode(array("remove" => "ok"));
            exit;
        }
        header('Content-Type: application/json');
        echo json_encode(array("exception" => utf8_encode(traduz("Ocorreu algum erro ao tentar excluir a imagem: ".pg_last_error($con)))));
        exit;
    }
    // ------------------------------ AJAX---------------------------------   
    $contexto = $_GET['context'];
    $referenceId = $_GET['reference_id'];
    $treinamento = null;
    if (in_array($login_fabrica, array(169,170))){
         $treinamento = $_GET['treinamento'];
    }
    /**
     * Ao adicionar um novo tipo, alterar os seguintes arquivos:
     * backend2:/var/www/callcenter/src/Tc/Callcenter/Controllers/ImageUploaderCallback.php
     * posvenda:/assist/controllers/QrCode.php
     * posvenda:/assist/plugins/fileuploader/fileuploader-iframe.php
     */

    if (in_array($login_fabrica, array(177))){
        $imageuploaderTipos = new ImageuploaderTiposMirror($login_fabrica);
    }else{
        $imageuploaderTipos = new ImageuploaderTiposMirror();
    }
    
    try{
        $comboboxContext = $imageuploaderTipos->get();
    }catch(\Exception $e){    
        $comboboxContext = [];
    }
    
    foreach ($comboboxContext as $key => $value) {
        foreach ($comboboxContext[$key] as $idx => $value) {
            $value['label'] = traduz(utf8_decode($value['label']));
            $value['value'] = utf8_decode($value['value']);
            $comboboxContext[$key][$idx] = $value;
        }
    }    
    
    $comboboxContextJson = [];
    $comboboxContextOptionsAux = [];
    foreach ($comboboxContext as $context => $options) {
        foreach ($options as $value) {
            $comboboxContextOptionsAux[$value['value']] = $value['label'];
            $comboboxContextJson[$context][] = $value["value"];
        }
    }

    if($contexto != ""){
        $contextOptions = $comboboxContext[$contexto];
        foreach ($contextOptions as $key => $value) {
            $value['label'] = utf8_encode($value['label']);
            $contextOptionsJson[$key] = $value;
        }
    }

    $params = array($referenceId);
    // Buscando registros existentes
    // if($referenceId < 9223372036854775807){
    if($hash_temp == 'true'){        
        $where = "hash_temp = $1";
    }elseif($hash_temp == 'false'){        
        $params[] = $contexto;
        $where = "referencia_id = $1 AND referencia = $2";

        if (!empty($treinamento)){
            $params[] = $treinamento;
            $where .= " AND json_field('treinamento',obs) = $3";
        }
    }

    

    // if(array_key_exists("no_hash", $_GET)){
    //     $params[] = $contexto;
    //     $where = "(referencia = $2 AND referencia_id = $1)";        
    // }else{
    //     $where = "(referencia_id = '0' AND referencia = $1)";
    // }

    $sql = "SELECT tdocs_id, contexto, obs, data_input FROM tbl_tdocs WHERE fabrica = {$login_fabrica} AND situacao = 'ativo' AND  ".$where;
    
    $stmt = pg_prepare($con,"select",$sql);
    $imagensGravadas = pg_execute($con,"select",$params);

    if(pg_num_rows($imagensGravadas) > 0){
        $imagensGravadas = pg_fetch_all($imagensGravadas);
    }else{
        $imagensGravadas = array();
    }

    $sqlTipos = "SELECT trim(tbl_anexo_contexto.nome)            as contexto,
                       trim(tbl_anexo_tipo.nome)            as label,
                       trim(tbl_anexo_tipo.codigo)              as value
                FROM tbl_anexo_tipo
                JOIN tbl_anexo_contexto          ON tbl_anexo_tipo.anexo_contexto            = tbl_anexo_contexto.anexo_contexto
            ";
    $resTipos = pg_query($con, $sqlTipos);

    while ($dados = pg_fetch_object($resTipos)) {

        $tiposAnexoBusca[$dados->value] = $dados->label;

    }

    foreach ($imagensGravadas as $key => $value) {
        
        $obs = array_pop(json_decode($value['obs'],1));        

    $obs['typeName'] = utf8_encode($tiposAnexoBusca[$obs['typeId']]);
    $obs['typeName'] = (strlen($obs['typeName']) > 0) ? $obs['typeName'] : "Arquivo";
        
        $obs = array($obs);

        $obs = json_encode($obs);

        $value['obs'] = $obs;
        $imagensGravadas[$key] = $value;
    }

    if(array_key_exists("ajax", $_GET)){
        if($_GET['ajax'] == "get_tdocs"){
            header('Content-Type: application/json');            
            echo json_encode($imagensGravadas);exit;
        }
    }
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <!-- Force latest IE rendering engine or ChromeFrame if installed -->
    <!--[if IE]>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
    <meta charset="iso-8851">
    <title>Telecontrol File Uploader</title>    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap styles -->
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <!-- Generic page styles -->
    <link rel="stylesheet" href="jquery-file-upload/css/jquery-ui-demo-ie8.css">
    <link rel="stylesheet" href="../../plugins/font_awesome/css/font-awesome.css">

    <link rel="stylesheet" href="../toastrjs/build/toastr.min.css">
    <!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
    <!-- <link rel="stylesheet" href="jquery-file-upload/css/jquery-ui-demo-ie8.css"> -->
    <style>
        .div_cursor {
            cursor: pointer;    
        }

        .env-fileupload {
            width: 500px;
            height: 150px;
            background: #eff6ff;
            margin: 0 auto;
            border: dashed 9px #549da3;
            margin-top: 10px;
            cursor: pointer;
        }

        .env-fileupload:active {
            background: #bbd6f3;
            border: dashed 9px #fef6ff;
            color: #fff;
        }

        .env-fileupload > input {
            width: 500px;
            height: 150px;
            opacity: 0;
        }

        .env-fileupload > p {
            position: relative;
            top: -140px;
            right: -78px;
            font-size: 19px;
            color: #55a3ab;
        }

        .env-fileupload > #icon {
            font-size: 48px;
            right: -217px;
            top: -123px;
        }

        #files > div {
            float: left;
            width: 100%;
            height: 60px;
            margin-top: 5px;
            border-bottom: 1px solid #e2e2e2;            
            padding: 5px;
        }

        #files > div:hover{
            background-color: #f7f7f7;
        }

        #files > div > p {
            float: left;
            text-align: center;
        }

        #files > div > select {
            float: right;
            width: 50%;
            margin-top: 65px;
        }

        #files > .row > .col-md-3 > p > canvas, #files > .row > .col-md-3 > p > img{
            float: left;
            border: 1px solid #e2e2e2;
        }

        #files > .row > .col-md-3 > p > span{
            float: left;
            margin-top: 10px;
            margin-left: 5px;
        }


        #files > div > span {
            float: right;
            margin-top: 93px;
            margin-right: 9px;
            color: #d9534f;
        }

        .backgroud-error{
            background-color: #ffdad9;
        }

        .error-message{
            color: #d9534f;
            margin-left: 10px;
            font-size: 10px;
        }

        .hidden{
            display: none;
        }

        .mobile:hover {
          background: #5b5c8d;
        }
        .mobile:active{
          background: #373865;
        }
        .mobile{
          display: inline-flex;
          height: 45px;
          width: 190px;
          background: #373865;
          /*padding: 5px;*/
          border-radius: 10px;
          cursor: pointer;
        }
        .google_play{
          margin-left: 10px;
          display: inline-flex;
          height: 57px;
          padding: 5px;
          cursor: pointer;

        }
        .google_play > a >span{
          color: #373865;
        }
        .google_play:hover{
          background: #f3f3f3;
          border-radius: 10px;
        }
        .mobile > span{
          font-size: 14px;
          float: right;
          margin-top: 13px;
          margin-right: 14px;
          color: #fac814;
        }

        .env-code{          
          /*border-radius: 7px;
          margin-top: 1px;   */       
        }

        .env-code > img{
            border: solid 3px;
            border-color: #373866;
        }

        .item-img{
            height: auto !important;
            min-height: 110px;
            overflow: hidden;
        }

        ./*env-img {
         
            max-width: 150px;
            margin-left: 10px;
            margin-top: 10px;
            display: inline-block;
        }*/}


    </style>
</head>
<body>

<script type="text/javascript">
var comboboxContextOptions = null;
<?php
if($contextOptions != ""){
?>
    comboboxContextOptions = <?=json_encode($contextOptionsJson)?>;
<?php
}
?>    

</script>

<div class="container-fluid">    
    <!-- The fileinput-button span is used to style the file input field as button -->

    <!-- The file input field used as target for the file upload widget -->
    <div class="row">
        <div class="col-md-12">
            <div class="env-fileupload">
                <input id="fileupload" type="file" name="files[]" multiple>
                <p id="icon"><span class="glyphicon glyphicon-upload" aria-hidden="true"></span></p>
                <p><?=traduz("Clique ou Arraste um arquivo nessa área")?></p>
            </div>
        </div>
    </div>
    
    
    <div class="row" style="margin-top: 40px;">
        <div class="col-md-12 text-center">
            <div class='env-code' style="display: none">
              <img style="width: 200px;" src="">
              <p style="margin-top: 4px;"><?=traduz("Visualize esse QR Code através do aplicativo")?> <a class="g_play" target="_BLANK" href="https://play.google.com/store/apps/details?id=br.com.telecontrol.imageuploader">Image Uploader</a></p>
            </div>
        </div>
        <div class="col-md-12">                
                <div style="width:100%;text-align:center">
                <span class="mobile" id="btn-qrcode-request" onclick="getQrCode()">
                <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="../../imagens/icone_mobile.png">
                <span><?=traduz("Anexar via Mobile")?></span>
                </span>
                <span class="google_play" id="btn-google-play">
                  <a class="g_play" target="_BLANK" href="https://play.google.com/store/apps/details?id=br.com.telecontrol.imageuploader">
                    <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="../../imagens/icone_google_play.png">
                    <span style="margin-top: 17px;float: left;font-size: 12px; color: #373865;"><?=traduz("Baixar Aplicativo Image Uploader")?></span>
                  </a>
                </span>
            </div>
        </div>
    </div>

    <div class="row"  style="margin-top: 60px">
        <div class="col-md-12" style="text-align: center">
            <button class="btn btn-primary btn-send-all"> <?=traduz("Enviar Todos")?></button>            
        </div>        
    </div>
    <!-- The global progress bar -->
    <div class="row" style="margin-top:40px">
        <div class="col-md-12">
            <small class="upload-info"></small>
        </div>
        <div class="col-md-12">            
            <div id="progress" class="progress">
                <div id="progress-item" class="progress-bar progress-bar-info"></div>
            </div>    
        </div>
    </div>

    
    <div class="row">
        <div class="col-md-12 text-center">
            <button id="refresh" class="btn btn-info btn-send-all"><i class="glyphicon glyphicon-refresh"></i> <?=traduz("Atualizar Imagens")?></button>            
        </div>
    </div>
    <hr>
    <!-- The container for the uploaded files -->
    <div class="row">
        <div class="col-md-12">
            <div id="files" class="files">
                <?php                  
                if(count($imagensGravadas)){
                    foreach ($imagensGravadas as $item) {
                        $dados = json_decode($item['obs'],1);

                        
                        $dados = $dados[0];                        

                        $file = explode(".",$dados['filename']);
                        $len = count($file);
                        $isImg = "";
                        if(in_array($file[$len-1], $imgExtensions)){
                            $isImg = 'is-image';
                        }
                       ?>
                        <div class='row-fluid item-img' id="<?=$item['tdocs_id']?>">                          
                            <div class='col-md-4 col-sm-4 row-img'> 
                                <div class="col-md-3 col-sm-3 div_cursor">
                                    <img class="tdocs-load box-uploader-download-arquivo <?=$isImg?>" data-arquivo="<?=$dados['filename']?>" data-uniqueid="<?=$item['tdocs_id']?>" width="100" src="placeholder.png" style="cursor: pointer;">
                                </div>                                
                           </div>
                           <?php if (!empty($dados["descricao"])){ ?>
                            <div class='col-md-2 col-sm-2' > 
                                <p><b><?=$dados["descricao"]?></b></p>   
                           </div>
                           <?php } ?>
                           <div class='col-md-2 col-sm-2' style="text-align: center;"> 
                                <button data-tdocsid="<?=$item['tdocs_id']?>" data-reference="<?= $referencia ?>" class="btn btn-danger btn-remove-upload"><?=traduz("Excluir")?></button>
                           </div>
                           <?php
                           if($contextOptions){
                            ?>
                            <div class='col-md-4 col-sm-4'>                                 
                                <select class="form-control input-xs">
                                   <option value="" disabled="" ><?=traduz("Defina o tipo do anexo")?></option>
                                   <?php
                                   foreach ($contextOptions as $key => $value) {                                    
                                       ?><option <?= $dados['typeId'] == $value['value']?"selected" : ""?> value="<?=$value['value']?>"><?=$value['label']?></option><?php
                                   }
                                   
                                   ?>
                                </select>                            
                            </div>                            
                            <?php
                           }
                           ?>                           
                           <div class="col-md-12 col-sm-12 div_cursor" style="overflow: hidden;">
                               <span style="margin-top: 5px !important;margin-left:15px !important; float: left;"><?=$dados['filename']?></span>                                                               
                            </div>                           
                       </div>
                       <?php 
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <div class="row" id="env-send-all" style="display:none; margin-top: 60px">
        <div class="col-md-12" style="text-align: center">
            <button id="" class="btn btn-primary btn-send-all"><?=traduz("Enviar Todos")?></button>
        </div>
    </div>
</div>
<script src="assets/jquery.min.js"></script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="jquery-file-upload/js/vendor/jquery.ui.widget.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="assets/load-image.all.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="assets/canvas-to-blob.min.js"></script>
<!-- Bootstrap JS is not required, but included for the responsive demo navigation -->
<script src="assets/bootstrap.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="jquery-file-upload/js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="jquery-file-upload/js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="jquery-file-upload/js/jquery.fileupload-process.js"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="jquery-file-upload/js/jquery.fileupload-image.js"></script>
<!-- The File Upload audio preview plugin -->
<script src="jquery-file-upload/js/jquery.fileupload-audio.js"></script>
<!-- The File Upload video preview plugin -->
<!--<script src="jquery-file-upload/js/jquery.fileupload-video.js?dhush"></script>-->
<!-- The File Upload validation plugin -->
<script src="jquery-file-upload/js/jquery.fileupload-validate.js"></script>

<script src="../toastrjs/build/toastr.min.js"></script>

<script id="item-template" type="text/html">
    <div class="row-fluid item-img" id="">        
        <div class="col-md-4 col-sm-4 row-img"> 
            <div class="col-md-3 col-sm-3 div_cursor">
                <img class="tdocs-load box-uploader-download-arquivo is-image" data-arquivo="" data-uniqueid="" width="100" src="" style="cursor: pointer;" data-link="">
            </div>                                
       </div>
        <div class="col-md-2 col-sm-2" style="text-align: center;"> 
            <button data-tdocsid="" data-reference="" class="btn btn-danger btn-remove-upload">Excluir</button>
       </div>
        <div class="col-md-4 col-sm-4">                                 
            <select class="form-control input-xs">
               <option value="" disabled="">Defina o tipo do anexo</option>
        </div>                            
        <div class="col-md-12 col-sm-12 div_cursor" style="overflow: hidden;">
           <span class='img-title' style="margin-top: 5px !important;float: left;"></span>                                                               
        </div>                                                                                  
    </div>
</script>

<script>

    var debug = false;

    /*jslint unparam: true, regexp: true */
    /*global window, $ */
    var removeButtonAction = function(){
        var tdocsId = $(this).data("tdocsid");
        var referencia = $(this).data("reference");
        var div = $(this).parents(".row-fluid")[0];
        
        $.ajax("fileuploader-iframe.php?removeFile=file",{
            method: "POST",
            data: {
                id: tdocsId, 
                referencia: referencia,
                hashTemp: "<?=$hash_temp?>"
            }
        }).done(function(response){
            if (debug) {
                console.log(response);
            }
            if(response.remove == 'ok'){
                $(div).fadeOut(1000,function(){
                    $(div).remove();
                });

                if (typeof window.parent.BoxUploaderExcluirCallback == 'function') {
                    window.parent['BoxUploaderExcluirCallback'](tdocsId);
                }

                toastr.info("<?=traduz('Arquivo Removido com sucesso')?>");
            }
        });
    }

    $(function(){
        $("#refresh").click(function(){
            verifyObjectId($("#objectid").val());
        });

        $(".env-fileupload").find("p,span").click(function(){
            $("#fileupload").click();
    });

    $(document).on("click", ".box-uploader-download-arquivo", function() {
            var link = $(this).data("link");
            var arquivo = $(this).data("arquivo");
        var id = $(this).data("uniqueid");
        console.log(link);
            window.open("fileuploader-download-arquivo.php?hash="+id+"&l="+btoa(link)+"&a="+arquivo);
        });

    });

    $(document).ready(function() {
        ocultar();
    });

    $(".row").change(function() { 
        ocultar();
    });

    // hd-6089156 Ocultar opções de definição do anexo
    function ocultar() {
        let contexto_revenda = "<?=$_SERVER['HTTP_REFERER']?>";
        let fab = "<?=$login_fabrica?>";  
        let posicao = contexto_revenda.indexOf("revenda_cadastro.php");

        if (fab == 117 && posicao != -1) {
            $('.form-control').hide();
        } else {
            $('.form-control').show();
        }
    }

    $(function () {
        'use strict';

        var select = undefined;

        $(".btn-send-all").click(function () {
            $(".btn-make-upload").each(function (idx, elem) {
                if (debug) {
                    console.log(elem);
                }
                $(elem).trigger("click");
            });
        });

        var descricao = "<?=$descricao?>";

        if (descricao == "true"){
            var dados_descricao = "<?=$dados_descricao?>";
        }

        $(".tdocs-load").each(function(idx, elem){
            var img = $(this);
            var id = img.attr("data-uniqueid");
            loadImage($(elem).data("uniqueid"),function(response){
                if (debug) {
                    console.log('.tdocs-load');
                    console.log(response);
                }

                img.attr("data-link",response.link);
                
                if(response.fileType == "image" || $(elem).hasClass('is-image')){
                    $(img).attr("src",response.link);
                    $("[data-auniqueid="+id+"]").attr("href",response.link);
                }else{
                    var image = $(img).attr("src","file-placeholder.png");
                }
            });
        });
        

        // Change this to the location of your server-side upload handler:        
        var uploadButton = $('<button/>')
            .addClass('btn btn-primary btn-make-upload')
            .prop('disabled', true)
            .text('Processing...')
            .on('click', function () {
                var $this = $(this),
                    data = $this.data();

                if (debug) {
                    console.log($this);
                }
                
                var div = $this.parents(".row")[0];                
                var select = $(div).find("select");    
                var fab = "<?=$login_fabrica?>"; 
                var contexto_revenda = "<?=$_SERVER['HTTP_REFERER']?>";
                var posicao = contexto_revenda.indexOf("revenda_cadastro.php");  
                
                // hd-6089156 Gravar sem classificar imagem
                if (fab != 117 && posicao == -1) {   
                    if(select.length > 0){

                        if($(select).val() == null){

                            if($(div).find(".error-message").length == 0){
                                div = $(this).parent("div");

                                var span = $("<span class='error-message'><?=traduz('Classifique essa imagem por favor')?></span>");
                                
                                toastr.warning("<?=traduz('Por favor classifique as imagens antes de efetuar o upload')?>");

                                $(div).append(span);

                                setTimeout(function(){
                                    $(div).find(".error-message").fadeOut(1000,function(){
                                        $(this).remove();                                                                        
                                    });
                                },5000);

                                if (debug) {
                                    console.log("Choose");
                                }
                            }
                            
                            return false;
                        }
                    }
                } 

                $this
                    .off('click')
                    .text('Abort')
                    .on('click', function () {
                        $this.remove();
                        data.abort();
                    });
                data.submit().always(function () {
                    $this.remove();
                });
            });

        

        $(".btn-remove-upload").click(removeButtonAction);

        $('#fileupload').fileupload({
            url: "uploader.php?context=<?=$contexto?>&reference_id=<?=$referenceId?>&hash_temp=<?=$hash_temp?>&descricao=<?=$dados_descricao?>",
            dataType: 'json',
            autoUpload: false,
            // acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
            maxFileSize: 999999999,
            // Enable image resizing, except for Android and Opera,
            // which actually support image resizing, but fail to
            // send Blob objects via XHR requests:
            //disableImageResize: /Android(?!.*Chrome)|Opera/
             //   .test(window.navigator.userAgent),
            previewMaxWidth: 100,
            previewMaxHeight: 50,
            previewCrop: true
        }).on('fileuploadadd', function (e, data) {            
            if (debug) {
                console.log("add");
                console.log(data);
            }

            $("#env-send-all").fadeIn(1000);

             // data.context = $('<div/>').appendTo('#files');

            var div = $("<div class='row-fluid item-img'>");


            data.context = $(div).appendTo("#files");
           
            if (debug) {
                console.log(data.context);
            }
            var divString = $("<div class='col-md-12 col-sm-12' style='overflow: hidden;'>");

            var div1 = $("<div class='col-md-4 col-sm-4 row-img'>");

            var div2 = $("<div class='env-buttons col-md-2 col-sm-2' style='text-align: center'>");

            if (descricao == "true"){
                var campo_descricao = $("<div class='col-md-2 col-sm-2'><p><b>"+dados_descricao+"</b></p></div>");
            }

            var button = uploadButton.clone(true);
            $(button).data(data);
            

            $(div2).append(button);
            
            if(comboboxContextOptions != null){
                var div3 = $("<div class='col-md-4 col-sm-4'>");
                var select = $("<select  class='form-control input-xs'>");
                var option = $('<option value="" disabled selected><?=traduz("Defina o tipo do anexo")?></option>');
                $(select).append(option);                

                $(comboboxContextOptions).each(function(idx,elem){
                    if (debug) {
                        console.log(elem);
                    }
                    var option = $("<option>");
                    $(option).html(elem.label);
                    $(option).val(elem.value);

                    $(select).append(option);                
                });                    
                select.appendTo(div3);    

                if (debug) {
                    console.log("select");
                }
            }

            var divCursor = $('<div class="col-md-12 col-sm-12 div_cursor" style="overflow: hidden;">');

            $(div).append(divString);
            $(div).append(div1);
            if (descricao == "true"){                
                $(div).append(campo_descricao);
            }
            $(div).append(div2);
            $(div).append(div3);
            $(div).append(divCursor);
            

            // data.context = $(div).appendTo("#files");

            $(div).appendTo(data.context); 

             $.each(data.files, function (index, file) {
                if (debug) {
                    console.log(index);
                    console.log(file);
                }
             });
        }).on('fileuploadsubmit', function (e, data) {
            if (debug) {
                console.log("fileuploadsubmit");
                // The example input, doesn't have to be part of the upload form:            
                console.log(data);
            }
            var div = data.context[0];
            var select = $(div).find("select");    
            if(select.length > 0){
                data.formData = {classificacao: $(select).val()};                
            }            
        }).on('fileuploadprocessalways', function (e, data) {
            if (debug) {
                console.log("fileuploadprocessalways");
                console.log(data);
            }
            var index = data.index,
                file = data.files[index],
                node = $(data.context.children()[index]),
                divs = data.context.children();
            if (debug) {
                console.log(index);
                console.log(file);
                console.log(node);
            }

            var string = $("<span class='tdocs-load is-image' style='margin-top: 5px !important;margin-left: 15px !important;float: left;'>" + file.name + "</span>");
            divs[4].append(string[0]);

            if (file.preview) { 
                divs[1].append(file.preview);                    
            }else{ 
                //var img = $("<img width='100' height='50' src='file-placeholder.png'>");
                //divs[1].append(img); 
            }
            if (file.error) {
                node.append($('<span class="text-danger"/>').text(file.error));
            }
            if (index + 1 === data.files.length) {
                data.context.find('button')
                    .text('Upload')
                    .prop('disabled', !!data.files.error);
            }
        }).on('fileuploadprogress', function (e, data) {
            if (debug) {
                console.log("..");
                console.log(data);
                console.log(data);
            }
        }).on('fileuploadprogressall', function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            // 701.04 kbit/s | 00:00:00 | 100.00 % | 1.14 MB / 1.14 MB            
            // $(".upload-info").html(progress+"% - Taxa Transferência: "+(data.bitrate/10000).toFixed(2)+" kbit/s - Uploaded: "+(data.loaded /1000)+"kb /"+(data.total/1000)+"kb");

            var progress = parseInt(data.loaded / data.total * 100, 10);
            var secondsRemaining = (data.total - data.loaded) * 8 / data.bitrate;

            $(".upload-info").html(progress+"% - <?=traduz('Taxa Transferência')?>: "+(data.bitrate/10000).toFixed(2)+" kbit/s - Uploaded: "+(data.loaded /1000)+"kb /"+(data.total/1000)+"kb - <?=traduz('Tempo Restante')?>: "+secondsRemaining.toFixed(2)+"s");
            $('#progress-item').css(
                'width',
                progress + '%'
            );
        }).on('fileuploaddone', function (e, data) {
            if (debug) {
                console.log("fileuploaddone");
                console.log(data);
            }

            $(data.result).each(function (idx, elem) {
                if (debug) {
                    console.log(idx);
                    console.log(elem);
                }

                console.log('asd');
                var buttonRemove = $('<button class="btn btn-danger btn-remove-upload"><?=traduz("Excluir")?></button>');


                $(buttonRemove).data("tdocsid",elem.unique_id);
                $(buttonRemove).data("reference",'<?=$referenceId?>');
                $(buttonRemove).click(removeButtonAction);

                $(data.context).find(".env-buttons").append(buttonRemove);
                console.log(data.context);
                console.log(elem);
                $(data.context).attr("id",elem.unique_id);

                 if (typeof window.parent.BoxUploaderCallback == 'function') {
                    window.parent['BoxUploaderCallback'](data);
                }

                toastr.success("<?=traduz('Upload feito com sucesso!')?>");

            });
            //
            // $.each(data.result.files, function (index, file) {
            //     if (file.url) {
            //         var link = $('<a>')
            //             .attr('target', '_blank')
            //             .prop('href', "http://google.com");
            //         $(data.context.children()[index])
            //             .wrap(link);
            //     } else if (file.error) {
            //         var error = $('<span class="text-danger"/>').text(file.error);
            //         $(data.context.children()[index])
            //             .append('<br>')
            //             .append(error);
            //     }
            // });
        }).on('fileuploadfail', function (e, data) {
            if (debug) {
                console.log("fileuploadfail");
            }
            $.each(data.files, function (index) {
                var error = $('<span class="text-danger"/>').text('<?=traduz("Upload do arquivo falhou.")?>');
                $(data.context.children()[index])
                    .append('<br>')
                    .append(error);
            });
        }).prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');
    });

    function getQrCode(){
        var data_ajax = {
            "ajax": "requireQrCode",
            "options": <?=json_encode($comboboxContextJson[$contexto])?>,
            "title": "<?=traduz('Upload de Arquivos')?>",
            "objectId": $("#objectid").val(),
            "contexto": "<?=$contexto?>",
            "fabrica": <?=$login_fabrica?>,
            "hashTemp": "<?=$hash_temp?>"
        };

        <?php
        if ($hash_temp == "true") {
        ?>
            data_ajax.hash_temp = true;
        <?php
        }
        ?>

        $("#btn-qrcode-request").fadeOut(1000);
        $("#btn-google-play").fadeOut(1000);
        $.ajax("../../controllers/QrCodeImageUploader.php",{
            method: "POST",
            data: data_ajax
        }).done(function(response){
          // response = JSON.parse(response);
          if (debug) {
            console.log(response);
          }

          $(".env-code").find("img").attr("src",response.qrcode)          
          $(".env-code").fadeIn(1000);

          if(setIntervalRunning==false){
            setIntervalHandler = setInterval(function(){
              verifyObjectId($("#objectid").val());
            },15000);
          }
        });
    }

    setIntervalRunning = false;
    setIntervalHandler = null;

    function verifyObjectId(objectId){

        $.ajax("../../controllers/TDocs.php",{
            method: "POST",
            data:{
                "ajax": "verifyObjectIdOnly",
                "objectId": objectId,
                "hashTemp": "<?=$hash_temp?>",
                "contexto": "<?=$contexto?>",
                "fabrica": <?=$login_fabrica?>
            }
          }).done(function(response){                        
            if(response != null){
                response = JSON.parse(response);

                if(response.exception == undefined){
                  $(response).each(function(idx,elem){

                    if($("#"+elem.tdocs_id).length == 0){

                      //var img = $("<div class='env-img'><img id='"+elem.tdocs_id+"' style='width: 150px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                      //##var img = $("<div class='env-img'><a href='http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                      //$(img).find("img").attr("src","http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id);

                      /**
                        
                      */


                        if (debug) {
                            console.log(elem);
                        }

                        loadImage(elem.tdocs_id, function(response){
                            console.log(elem);
                            var item = $("#item-template").html();
                            item = $(item);

                            var data = JSON.parse(elem.obs);
                            
                            data = data[0];
                            if (debug) {
                                console.log(data);
                            }                            
                            $(item).find("img").attr("src",response.link);
                            console.log(item);
                            $(item).find(".img-title").html(data.filename);                            
                            $(item).attr("id",data.tdocsId);                            

                            $(item).find(".btn-remove-upload").data("tdocsid", data.tdocsId);                            
                            $(item).find(".btn-remove-upload").data("reference", data.objectId);
                            $(item).find(".btn-remove-upload").click(removeButtonAction);

                            $(item).find('.box-uploader-download-arquivo').data({ link: response.link, arquivo: data.filename, uniqueid: data.tdocsId });
                            
                            if(comboboxContextOptions != null){                    
                                var option = $('<option value="" disabled><?=traduz("Defina o tipo do anexo")?></option>');                                

                                $(comboboxContextOptions).each(function(idx,elem){                                
                                    var option = $("<option>");
                                    $(option).html(elem.label);
                                    $(option).val(elem.value);

                                    
                                    if(elem.value == data.typeId){
                                        if (debug) {
                                            console.log("selected");
                                        }
                                        $(option).attr("selected","selected");
                                    }                                    
                                    $(item).find("select").append(option);
                                });                                                    
                            }

                            $("#files").prepend(item);
                        }, true);                    
                    }
                  });
                }
            }
          });
    }    

    function loadImage(uniqueId, callback, link){
    if (typeof link != 'undefined' && link === true) {
            $.ajax("fileuploader-iframe.php?loadTDocs="+uniqueId+"&link=true").done(callback);
    } else {
        $.ajax("fileuploader-iframe.php?loadTDocs="+uniqueId).done(callback);
    }
    }
</script>

<input type="hidden" id="objectid" value="<?=$reference_id?>">
</body>
</html>


