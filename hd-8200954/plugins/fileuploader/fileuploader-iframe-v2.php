<?php
    include 'TdocsMirror.php';
    include __DIR__.'/../../controllers/ImageuploaderTiposMirror.php';

    $areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
    $admin_es = preg_match('/\/admin_es\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
	$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

    include __DIR__.'/../../dbconfig.php';
    include __DIR__.'/../../includes/dbconnect-inc.php'; 

    if ($areaAdmin === true) {
        include __DIR__.'/../../admin/autentica_admin.php';
        include_once __DIR__.'/../../fn_traducao.php';
        
        $usuarioInformacoes = array(
            "admin" => $login_admin
        ); 
    } elseif($admin_es){
        include __DIR__.'/../../admin_es/autentica_admin.php';
        include_once __DIR__.'/../../fn_traducao.php';
        
        $usuarioInformacoes = array(
            "admin" => $login_admin
		);
	} elseif($areaAdminCliente){
        include __DIR__.'/../../admin_cliente/autentica_admin.php';
        include_once __DIR__.'/../../fn_traducao.php';
        
        $usuarioInformacoes = array(
            "admin" => $login_admin
        );

    } else {
        include __DIR__.'/../../autentica_usuario.php';

        $usuarioInformacoes = array(
            "posto" => ["posto" => $login_posto]
        );
    }

    if(file_exists(dirname(__FILE__) ."/../../os_cadastro_unico/fabricas/$login_fabrica/regras_box_uploader.php")){
        include dirname(__FILE__) . "/../../os_cadastro_unico/fabricas/$login_fabrica/regras_box_uploader.php";
    }
    
    if ($_GET['ajax'] == 'get_types') {

        $imageuploaderTipos = new ImageuploaderTiposMirror($login_fabrica, $con);
    
        try{
            $comboboxContext = $imageuploaderTipos->get();
        }catch(\Exception $e){    
            $comboboxContext = [];
        }
        
        exit(json_encode($comboboxContext[$_GET['context']]));
    }

	pg_close($con);
    
    $imgExtensions   = array("png","jpg","jpeg","gif","bmp","exif","tiff","ico");
    $videoExtensions = array("mp4", "mov", "wmv", "flv", "avi", "webm", "mpg", "mpeg");
    $audioExtensions = array("mp3", "ogg", "wma", "oga", "wav");
    $arqExtensions   = array("pdf", "PDF");
	if($login_fabrica == 24) {
		$audioExtensions = array("mp3", "ogg", "wma", "oga");
	}
    
    $hash_temp = array_key_exists("hash_temp", $_GET) ? "true" : "false";    
    $descricao = array_key_exists("descricao", $_GET) ? "true" : "false";
	$current_page = $_GET['current_page'];

    if ($descricao == "true"){
        $dados_descricao = $_GET['descricao'];
    }

    // ------------------------------ AJAX---------------------------------
    $referencia = $_GET['reference_id'];

    if(array_key_exists("loadTDocs", $_GET)){

        $tdocsMirror = new TdocsMirror();
        try{
            $response = $tdocsMirror->get($_GET['loadTDocs']);

			$con = pg_connect($parametros);
			
            $sql = "SELECT obs FROM tbl_tdocs WHERE tdocs_id = '{$_GET['loadTDocs']}'";
            $res = pg_query($con, $sql);

            $obs = json_decode(pg_fetch_result($res, 0, 'obs'), true);
            $file = $obs[0]['filename'];
    
            $file = explode(".",$file);
			$file = array_map('strtolower',$file);
            $len = count($file);

            if(in_array($file[$len-1], $imgExtensions)){
                $response['fileType'] = "image";
            }elseif(in_array($file[$len-1], $videoExtensions)){
                $response['fileType'] = "video";
            }elseif(in_array($file[$len-1], $audioExtensions)){
                $response['fileType'] = "audio";
            }elseif(in_array($file[$len-1], $arqExtensions)){
                $response['fileType'] = "pdf";
            }else{
                $response['fileType'] = "file";
        }

        $response['file_name'] = $obs[0]['filename'];
            
            $response['file_name'] = $obs[0]['filename'];
            
            header('Content-Type: application/json');
            echo json_encode($response);
        }catch(\Exception $e){
            header('Content-Type: application/json');
            echo json_encode(array("exception" => utf8_encode(traduz("Imagem nao encontrada"))));
            exit;
        }
        exit;
    }
    if(array_key_exists("removeFile", $_GET)){
        $tdocsId = trim($_POST['id']);
        $reference = trim($_POST['referencia']);
        
		$con = pg_connect($parametros);

        pg_query($con, 'BEGIN');

        if($_POST['hashTemp'] == "true"){
            $sql = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE hash_temp = $1 and tdocs_id = $2";
            $stmt = pg_prepare($con, "update", $sql);
            $result = pg_execute($con, "update", array($reference,$tdocsId));
        
            if (function_exists("cancela_anexos_os_revenda")) {
               cancela_anexos_os_revenda($reference, $tdocsId, true);
            }
        } else if ($_POST["hashTempRef"] == "true") {
            $sql = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE tdocs_id = $1 AND (hash_temp = $2 OR referencia_id = $3::decimal)";
            $stmt = pg_prepare($con, "update", $sql);
            $result = pg_execute($con, "update", array($tdocsId, $reference, $reference));
						
			if (function_exists("cancela_anexos_os_revenda")) { 
               cancela_anexos_os_revenda($reference, $tdocsId, true);
            }
        }else{
            $sql = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE tdocs_id = $1 AND referencia_id = $2";
            $stmt = pg_prepare($con, "update", $sql);
            $result = pg_execute($con, "update", array($tdocsId, $reference));

            if (function_exists("cancela_anexos_os_revenda")) {
               cancela_anexos_os_revenda($reference, $tdocsId, false);
            }
        }
				
		if(pg_last_error($con) == false && pg_affected_rows($result) == 1){
            pg_query($con, 'COMMIT');
            header('Content-Type: application/json');
            echo json_encode(array("remove" => "ok"));
            exit;
        }

        pg_query($con, 'ROLLBACK');
        header('Content-Type: application/json');
        echo json_encode(array("exception" => utf8_encode(traduz("Ocorreu algum erro ao tentar excluir a imagem: ".pg_last_error($con)))));
        exit;
    }

	$con = pg_connect($parametros);

    // ------------------------------ AJAX---------------------------------   
    $contexto = $_GET['context'];
    $referenceId = $_GET['reference_id'];
    $treinamento = null;
    if (in_array($login_fabrica, array(169,170))){
        $treinamento = $_GET['treinamento'];
    }
    
    $params = array($referenceId);
    // Buscando registros existentes
    // if($referenceId < 9223372036854775807){
    if($hash_temp == 'true'){        
        $where = "hash_temp = $1";
    }elseif($hash_temp == 'false'){
        if ($contexto == "help desk") {
            $params[] = $contexto;
            $where = "referencia_id = $1 AND contexto = $2";
        } else {
            $params[] = $contexto;
            $where = "referencia_id = $1 AND referencia = $2";
        }

        if (!empty($treinamento)){
            $params[] = $treinamento;
            $where .= " AND json_field('treinamento',obs) = $3";
        }

    }
        // var_dump($params);

    

    // if(array_key_exists("no_hash", $_GET)){
    //     $params[] = $contexto;
    //     $where = "(referencia = $2 AND referencia_id = $1)";        
    // }else{
    //     $where = "(referencia_id = '0' AND referencia = $1)";
    // }

    $sql = "SELECT tdocs_id, contexto, obs, data_input, obs FROM tbl_tdocs WHERE fabrica = {$login_fabrica} AND situacao = 'ativo' AND  ".$where;
    
    if ($login_fabrica == 203) {
        $sql = "SELECT tdocs_id, contexto, obs, data_input, obs FROM tbl_tdocs WHERE fabrica IN (167,203) AND situacao = 'ativo' AND  ".$where;
    }   

    // var_dump($sql); die();
    
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
			WHERE fabrica = $login_fabrica
			AND ativo
            ";
    $resTipos = pg_query($con, $sqlTipos);

    while ($dados = pg_fetch_object($resTipos)) {

        $tiposAnexoBusca[$dados->value] = $dados->label;

    }

    foreach ($imagensGravadas as $key => $value) {

		$obs = array_pop(json_decode($value['obs'],1));        

		$obs['typeName'] = utf8_encode($tiposAnexoBusca[$obs['typeId']]);
		$obs['typeName'] = (strlen($obs['typeName']) > 0) ? $obs['typeName'] : "Arquivo";
		list ($ano,$mes,$dia) = explode("-", substr($value['data_input'], 0, 10) );  
		$hora = substr($value['data_input'], 10, 6);
		$obs['data'] =  $dia."/".$mes."/".$ano . ' '. $hora; 
		$obs['fabrica'] = $login_fabrica;


		$obs = array($obs);

		$obs = json_encode($obs);

		$value['obs'] = $obs;
		$imagensGravadas[$key] = $value;
    }

    if(array_key_exists("ajax", $_GET)){
        if($_GET['ajax'] == "get_tdocs") {
            header('Content-Type: application/json');            
            echo json_encode($imagensGravadas);exit;
        }
    }

?>
<!DOCTYPE HTML>
<html lang="pt-br">
<head>
    <!-- Force latest IE rendering engine or ChromeFrame if installed -->
    <!--[if IE]>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
    <meta charset="utf8_encode">
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
            height: 150px;
            background: #eff6ff;
            margin: 0 auto;
            border: dashed 5px #549da3;
            margin-top: 10px;
            cursor: pointer;
            text-align: center;
        }

        .env-fileupload:active {
            background: #bbd6f3;
            border: dashed 9px #fef6ff;
            color: #fff;
        }

        .env-fileupload > input {
            height: 150px;
            opacity: 0;
        }

        .env-fileupload > p {
            position: relative;
            font-size: 19px;
            top: -140px;
            color: #55a3ab;
        }

        .env-fileupload > #icon {
            font-size: 48px;
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

        .icon button{
            border-radius: 50px;
            height: 30px;
            width: 30px;
        }

        .rowa{
            font-family:sans-serif;
        }

        .icon-placeholder img{
            border-radius: 50px;
            width: 50px;
            height: 50px;
        }

        .icon-placeholder{
            border-radius: 50px;
            width: 50px;
            height: 50px;
        }

         .icon-placeholder i{
        }

        ./*env-img {
         
            max-width: 150px;
            margin-left: 10px;
            margin-top: 10px;
            display: inline-block;
        }*/}


    </style>
</head>
<body style="overflow-x: hidden; height: 100%;" >

<script type="text/javascript">
var comboboxContextOptions = window.parent.BoxUploader.types;
</script>

<div class="container-fluid">    
    <!-- The fileinput-button span is used to style the file input field as button -->

    <!-- The file input field used as target for the file upload widget -->
    <div class="row">
        <div class="col-md-12" width="100%">
            <div class="env-fileupload">
                <input id="fileupload" type="file" name="files[]" multiple>
                <p id="icon"><span class="glyphicon glyphicon-upload" aria-hidden="true"></span></p>
                <p><?=traduz("Clique ou arraste um arquivo nessa area")?></p>
            </div>
        </div>
    </div>
    
    
    <div class="row" style="margin-top: 40px;">
        <div class="col-md-12 text-center">
        <div class='env-code' style="display: none">
            <img style="width: 200px;" src="">
            <p style="margin-top: 4px;"><?=traduz("Visualize esse QR Code atraves do aplicativo")?> <a class="g_play" target="_BLANK" href="https://play.google.com/store/apps/details?id=br.com.telecontrol.imageuploader">Image Uploader</a></p>
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

   <div class="row" id="env-send-all" style="margin-top:30px; display: none">
        <div class="col-md-12" style="text-align: center">
            <button id="" class="btn btn-primary btn-send-all"><?=traduz("Enviar Todos")?></button>
        </div>
    </div>
        <div class="upload-bar" style="margin-top: 20px"></div>

</div>

<!-- The global progress bar -->
    <div class="row" id="progress-info" style="margin-top:20px">
        <div class="col-md-12">
            <small class="upload-info"></small>
        </div>
        <div class="col-md-12">            
            <div id="progress" class="progress">
                <div id="progress-item" class="progress-bar progress-bar-info"></div>
            </div>    
        </div>
    </div>
<div class="row" style="margin-top: 10px">
        <div class="col-md-12 text-center">
            <button id="refresh" class="btn btn-info btn-update-all"><i class="fas fa-mobile-alt"></i> <?=traduz("Atualizar Imagens Mobile")?></button>            
        </div>
    </div>

    <hr>
    <!-- The container for the uploaded files -->
    <div class="row">
        <div class="col-md-12">
            <div id="files" class="files">
                <table id="filetable" class="table listview dataTable no-footer" role="grid" aria-describedby="filetable_info" style="width: 100%" cellspacing="0">
                        <tr class="rowa one" role="row">
                            <td class="icon itemicon text-center sorting_disabled" rowspan="1" colspan="1" style="width: 75px;" aria-label=" ">
                            </td>
                            <td class="mini h-filename name sorting_asc" tabindex="0" aria-controls="filetable" rowspan="1" colspan="1" style="width: 400px;" aria-sort="ascending">
                                <span class="hidden-xs sorta nowrap">Nome do arquivo</span>
                            </td> 
                            <?php if (in_array($contexto, ['revenda'])) { ?>
                            <td class="mini h-description type" tabindex="0" aria-controls="filetable" rowspan="1" colspan="1" style="width: 500px;" aria-sort="ascending">
                                <span class="hidden-xs sorta nowrap"><?=traduz('Descricao')?></span>
                            </td>
                            <?php } ?>
                            <td class="mini h-filetype type" tabindex="0" aria-controls="filetable" rowspan="1" colspan="1" style="width: 547px;" aria-sort="ascending">
                                <span class="hidden-xs sorta nowrap">Tipo de arquivo</span>
                            </td>
                            <td class="mini h-inputdate date" tabindex="0" aria-controls="filetable" rowspan="1" colspan="1" style="width: 547px;" aria-sort="ascending">
                                <span class="hidden-xs sortal nowrap">Data do Envio</span>
                            </td>
                            <td class="mini text-center gridview-hidden sorting_disabled" rowspan="1" colspan="1" style="width: 39px;" aria-label=" ">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </td>
                        </tr>
                    </thead>
                    <tbody class="gridbody">
                        <?php foreach ($imagensGravadas as $imgs) { ?>
                            <?php foreach (json_decode($imgs['obs']) as $img) { ?>
                                <?php $imgInfo = new TdocsMirror();
                                $info = $imgInfo->get($imgs['tdocs_id']);
                                if (!pathinfo($info['file_name'])['extension']) {
                                    pathinfo($info['file_name'])['extension'] = 'fpg'; 
                                    $info['file_name'] .= '.jpg';
                                }

                                $url = "../../plugins/fileuploader/fileuploader-download-arquivo.php?hash=".$imgs['tdocs_id']."&l=".base64_encode($info['link'])."&a=".$info['file_name']."";
                                ?>
                                <tr id="gall-0" class="rowa gallindex odd" role="row">
                                    <td class="icon itemicon text-center">
                                        <div class="icon-placeholder">
                                        <?php $ext = pathinfo($info['file_name'])['extension']; $ext = strtolower($ext); ?>
                                            <?php if (in_array($ext, $imgExtensions)) { ?>
                                            <img src="<?php echo $info['link']; ?>" alt="<?php echo $img->filename?>">
                                            <?php } else if (in_array($ext, $videoExtensions)) { ?>
                                                <i class="fa fa-file-video fa-2x"></i>
                                        <?php } else if (in_array($ext, $audioExtensions)) { ?>
                                            <i class="fa fa-file-audio fa-2x"></i>
                                        <?php } else { ?>
                                                <i class="fa fa-file-alt fa-2x"></i>
                                            <?php } ?>
                                        </div>
                                    </td>
                                    <td class="name_sorting_1">
                                        <div class="relative">
                                            <a class="php-table item file full-lenght thumb vfm-gall" href="<?php echo $url ?>" target="_blank"><?php echo utf8_decode($img->filename) ?></a>
                                            <span class="hover">
                                                    <i class="fa fa-search-plus fa-fw"></i>
                                            </span>
                                        </div>
                                    </td>
                                    <?php if (in_array($contexto, ['revenda'])) { ?>
                                    <td class="name_sorting_1">
                                        <div class="relative">
                                            <p class="item file full-lenght thumb vfm-gall"><?= $img->descricao ?>
                                            </p>
                                        </div>
                                    </td>
                                    <?php } ?>
                                    <td class="name_sorting_1">
                                        <div class="relative">
                                            <p class="item file full-lenght thumb vfm-gall"><?php echo $img->typeId != 'null' ? ucwords($img->typeId) : ' '; ?>
                                            </p
>                                        </div>
                                    </td>
                                    <td class="name_sorting_1">
                                        <div class="relative">
                                        <p class="item file full-lenght thumb vfm-gall"><?php echo $img->data ? $img->data : date('d/m/Y H:i', strtotime($imgs['data_input'])); ?>
                                            </p>
                                        </div>
                                    </td>
                                    <?php if (isset($_POST['ajax_tdocs_id'])) {
                                        $sql = "SELECT * FROM tbl_tdocs
                                                WHERE tdocs_id = {$_POST['tdocs_hash']};";

                                        if (pg_row_count(pg_query($con, $sql)) > 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                    ?>
                                    <td class="icon text-center hidden-xs">
                                        <div class="btn-delete-file" row="row" data-reference="<?php echo $referencia; ?>" data-tdocsid="<?php echo $imgs['tdocs_id']; ?>" data-thisname="<?php echo $img->filename; ?>" onclick="deleteAction($(this));">
                                            <i class="fas fa-times"></i>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>                     
            </div>
        </div>
    </div>
<script src="assets/jquery.min.js"></script>
<script src="jquery-file-upload/js/vendor/jquery.ui.widget.js"></script>
<script src="assets/load-image.all.min.js"></script>
<script src="assets/canvas-to-blob.min.js"></script>
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

<script>
    var debug = false;
    var fileInfo = ['fileData', 'fileId'];
    fileInfo['fileType'] = [];
    var row = 1;
    var visible = 0;
    var imgLink;
    var index = 1;

    /*jslint unparam: true, regexp: true */
    /*global window, $ */
    var removeButtonAction = function(){
        var tdocsId = $(this).data("tdocsid");
        var referencia = $(this).data("reference");
        var div = $(this).parents(".row-fluid")[0];

        $.ajax("fileuploader-iframe-v2.php?removeFile=file",{
            method: "POST",
            data: {
                id: tdocsId, 
                referencia: "<?=$hash_temp?>",
                hashTemp: "true"
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
        $('.input-type').each(function() {
            $(this).val(comboboxContextOptions[$(this).val()]);
        });
        
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
            window.open("fileuploader-download-arquivo.php?hash="+id+"&l="+btoa(link)+"&a="+arquivo);
        });

    });

    $(document).ready(function() {
        ocultar();
    });

    $(".row").change(function() { 
        ocultar();
    });

    // hd-6089156 Ocultar opçoes de definicao do anexo
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

    $(function () {''
        'use strict';

        var select = undefined;

        $(".btn-send-all").click(function () {
            $(".row-fluid").each(function (idx, elem) {
                if (debug) {
                    console.log(elem);
                }
            $(elem).find('.env-button').find('button').click();
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
            .attr('data-row', row)
            .prop('disabled', true)
            .on('click', function (e) {
                var $this = $(this),
                    data = $this.data();

                if (debug) {
                    console.log($this);
                }
                
                var div = $this.parents(".row-fluid");                
                var select = $(div).find('.form-control.input-xs');    
                var fab = "<?=$login_fabrica?>"; 
                var contexto_revenda = "<?=$_SERVER['HTTP_REFERER']?>";
                var posicao = contexto_revenda.indexOf("revenda_cadastro.php");

                if($(select).val() == "") {
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
                
                $this
                    .off('click')
                    .text('Cancelar')
                    .addClass('btn btn-warning ')
                    .removeClass('fa fa-upload')
                    .on('click', function () {
                        $this.remove();
                        data.abort();
                    });
                data.submit().always(function () {
                    $this.remove();
                });

                var ths = $(this).parent();
                ths.parent().closest('div').fadeOut(1000);
            });

        var cancelButton = $('<button/>')
        .addClass('btn btn-danger btn-cancel-upload')
        .html('<i class="fas fa-ban"></i> Remover')
        .on('click', function () {
                var ths = $(this).parent();
                ths.parent().closest('div').fadeOut(1000);
            });
        ;

        $(".btn-remove-upload").click(removeButtonAction);

        $(".btn-update-all").click(function(){
            $.ajax({
                async: true,
                type: 'get',
                url: '../../plugins/fileuploader/fileuploader-iframe-v2.php?context=<?=$contexto?>&reference_id=<?=$referenceId?>&hash_temp=<?=$hash_temp?>&ajax=get_tdocs',
                timeout: 200000
            }).done(function(res){
                var tdocsId = res[0].tdocs_id;
                var url = '../../plugins/fileuploader/fileuploader-iframe-v2.php?loadTDocs=';
                var fileType; 

                $(JSON.parse(res[0].obs)).each(function(i, elem) {
                    fileType = elem.typeId;
                });
                
                $.ajax({
                    async: true,
                    type: 'get',
                    url: url.concat(tdocsId)
                }).done(function (response) {
                    $(updateMobile(response, tdocsId, fileType));
                }).fail(function (res) {
                    console.log(res);
                });
            }).fail(function(response) {
                console.log('fail');
                console.log(response);
            });
        });

        $('.btn-update-all').click(function() {
            $.ajax({
            <?php if ($contexto == 'os') { ?> 
                async: true,
                type: 'get',
                url: '../../plugins/fileuploader/fileuploader-iframe-v2.php?context=<?=$contexto?>&reference_id=<?=$reference_id?>&hash_temp=<?=$hash_temp?>&ajax=get_tdocs',
                timeout: 200000
            <?php } else { ?>
                async: true,
                type: 'get',
                url: '../../plugins/fileuploader/fileuploader-iframe-v2.php?context=<?=$contexto?>&reference_id=<?=$reference_id?>&ajax=get_tdocs',
                timeout: 200000
            <?php } ?>
            }).done((res, req) => {
                var tdocsId = res[0].tdocs_id; 
                var obs = JSON.parse(res[0].obs);
                $.ajax({
                    async: true,
                    type: 'get',
                    url: '../../plugins/fileuploader/fileuploader-iframe-v2.php?loadTDocs=' + tdocsId
                }).done((data) => {
                    updateMobile(data.file_name, data.link, tdocsId , obs[0].typeId, res[0].data_input);

                });
            }).always((data) => {
                document.location.reload();
            });
        });

        var idUpload, lastPart = 0, part = 1, metadata, warning = 0;
        var objUpload = [];
        $('#fileupload').fileupload({
		url: "uploader.php?context=<?=$contexto?>&reference_id=<?=$referenceId?>&hash_temp=<?=$hash_temp?>&descricao=<?=$dados_descricao?>&current_page=<?=$current_page?>",    
            dataType: 'json',
            autoUpload: false,
            maxFileSize: 999999999,
            maxChunkSize: 5242880,
            maxRetries: 100,
            retryTimeout: 500,
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                $("#progress .bar").css("width", `${progress}%`);
            },
            chunkbeforesend: function (e, data) {

                var arquivoUpload = data.files[0].name;

                if (objUpload[arquivoUpload] == undefined) {

                    objUpload[arquivoUpload] = {
                        part: 1,
                        lastPart: 0,
                        metadata: arquivoUpload,
                        classificacao: null,
                        uploadId: undefined
                    };

                }

                if (data.total > 50000 && !warning) {
                    $('#progress-info').prepend('<p class="text-warning" style=margin-left:30px>Devido ao tamanho do arquivo, o upload pode levar um certo tempo para ser completado.'+ 
                    '<br>Fechar essa janela vai cancelar o upload.</p>'+
                    '</div>');
                    warning = 1
                }

                if (data.uploadedBytes + data.chunkSize == data.total) {
                    objUpload[arquivoUpload].lastPart = 1;
                }

                objUpload[arquivoUpload].classificacao = ($('select[arquivo="'+arquivoUpload+'"]').val() == "") ? $(".typeid").val() : $('select[arquivo="'+arquivoUpload+'"]').val();

                data.formData = objUpload[arquivoUpload];

                objUpload[arquivoUpload].part++;
            },
            chunksend: function (e, data) {
            },
            chunkfail: function (e, data) {
                console.log("teste fahou");
                var error = $('<span class="text-danger" style=margin-left:30px/>').text('<?=traduz("Upload do arquivo falhou.")?>');
                $('#progress-info')
                    .prepend('<br>')
                    .prepend(error);
            },
            chunkdone: function (e, data) {

                if (typeof(data.formData.uploadId) == 'undefined') {
                    objUpload[data.files[0].name].uploadId  = data.result.upload_id;
                }


            }
        }).on('fileuploadadd', function (e, data) {            
            if (debug) {
                console.log("add");
                console.log(data);
            }

            var div = $("<div class='row-fluid' row='"+ row +"' >");
            row++;

            if (visible = 1) {
                data.context = $(div).appendTo(".upload-bar")
                $('.upload-bar').fadeIn();
                $('#env-send-all').css('display', 'block');
            } else {
                data.context = $(div).appendTo(".upload-bar");
            }

            visible = 1;

            if (debug) {
                console.log(data.context);
            }

            var divString = $("<div class='upload-file'><div class='col-md-12 col-sm-12' style='overflow: hidden;'>");

            var div1 = $("<div class='col-md-4 col-sm-4 '>");

            var div2 = $("<div class='env-button col-md-2 col-sm-2' style='text-align: center'></div>");

            var cdiv = $("<div class='cancel-button col-md-2 col-sm-2' style='text-align: center'></div>");

            if (descricao == "true"){
                var campo_descricao = $("<div class='col-md-2 col-sm-2'><p><b>"+dados_descricao+"</b></p></div>");
            }

            var cancelBtn = cancelButton.clone(true);
            var uploadBtn = uploadButton.clone(true);
            
            $(uploadBtn).data(data);
            
            $(div2).append(uploadBtn);
            $(cdiv).append(cancelBtn);

            if(comboboxContextOptions != null){
                var div3 = $("<div class='col-md-4 col-sm-4'>");
                var select = $("<select  class='typeid form-control input-xs' arquivo='"+data.files[0].name+"'>");
                var option = $('<option value=""><?=traduz("Defina o tipo do anexo")?></option>');
                $(select).append(option);   

                $.each(comboboxContextOptions, function(value,label){
                    if (debug) {
                        console.log(value, label);
                    }
                    var option = $("<option>");
                    $(option).html(label.replace(/^\w/, c => c.toUpperCase()));
                    $(option).val(value);
                    
                    $(select).append(option);
                });                    
                select.appendTo(div3);    

                if (debug) {
                    console.log("select");
                }

                $(option).on('change', function() {
                    $(option).prop('selected');
                });
            }

            var divCursor = $('<div class="col-md-12 col-sm-12 div_cursor" style="overflow: hidden;">');

            $(div).append(divString);
            $(div).append(div1);
            if (descricao == "true"){                
                $(div).append(campo_descricao);
            }
            $(div).append(div2);
            $(div).append(cdiv);
            $(div).append(div3);
            $(div).append(divCursor);
            
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
                console.log(data);
            }
            fileInfo['fileType'] = data;

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
            divs[5].append(string[0]);

            if (file.preview) { 
                divs[1].append(file.preview);                    
            }
            if (file.error) {
                node.append($('<span class="text-danger"/>').text(file.error));
            }
            if (index + 1 === data.files.length) {
                data.context.find('.btn-make-upload')
                    .html('<i class="fas fa-upload"></i> Upload')
                    .prop('disabled', !!data.files.error);
            }
        }).on('fileuploadprogress', function (e, data) {
            if (debug) {
                console.log('data');
                console.log(data);
            }

        }).on('fileuploadprogressall', function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            // 701.04 kbit/s | 00:00:00 | 100.00 % | 1.14 MB / 1.14 MB            
            // $(".upload-info").html(progress+"% - Taxa Transfer�ncia: "+(data.bitrate/10000).toFixed(2)+" kbit/s - Uploaded: "+(data.loaded /1000)+"kb /"+(data.total/1000)+"kb");

            var progress = parseInt(data.loaded / data.total * 100, 10);
            var secondsRemaining = (data.total - data.loaded) * 8 / data.bitrate;
            var hours, minutes, seconds;

            hours = Math.floor(secondsRemaining / 3600);
            secondsRemaining %= 3600;
            minutes = Math.floor(secondsRemaining / 60);
            seconds = (secondsRemaining % 60).toFixed(0);

            var timeRemaining;
            if (hours > 0) {
                timeRemaining = hours+' h '+minutes+' min '+seconds+' s';
            } else {
                timeRemaining = minutes+' min '+seconds+' s';
            }

            $(".upload-info").html(progress+"% - <?=traduz('Taxa Transferencia')?>: "+(data.bitrate/10000).toFixed(2)+" kbit/s - Uploaded: "+(data.loaded /1000)+"kb /"+(data.total/1000)+"kb - <?=traduz('Tempo Restante')?>: " + timeRemaining);
            $('#progress-item').css(
                'width',
                progress + '%'
            );
        }).on('fileuploaddone', function (e, data) {
            // if (debug) {
                console.log("fileuploaddone");
                console.log(data);
            // }
            
            if (data.result.exception != undefined) {

                toastr.error(data.result.exception);

            } else {

                var mimeType = data.files[0].type;

                $(data.result).each(function (idx, elem) {
                    if (debug) {
                        console.log(idx);
                        console.log(elem);
                    }

                    fileInfo['fileId'] = elem;

                    // const fileUpload = {'file_name':elem.file_name, 'link':elem.link, 'unique_id':unique_id}
                    tableAdd(fileInfo, idx, data.formData.classificacao, index, mimeType);
                    index++;


                    var buttonRemove = $('<button class="btn btn-danger btn-remove-upload"><?=traduz("Excluir")?></button>');

                    $(buttonRemove).data("tdocsid",elem.unique_id);
                    $(buttonRemove).data("reference",'<?=$referenceId?>');
                    $(buttonRemove).click(removeButtonAction);

                     <?php if($login_fabrica == 30){ ?>
                        window.parent.RetornoAnexoEsmaltec(elem.unique_id);
                    <?php } ?>

                    $(data.context).attr("id",elem.unique_id);

                    if (window.parent.BoxUploader.callback.upload !== null) {
                        window.parent.BoxUploader.callback.upload(fileInfo);
                    }

                    toastr.success("<?=traduz('Upload feito com sucesso!')?>");

                });

            }
            //
            // $.each(data.result.files, function (index, file) {
            //     if (file.url) {
            //         var link = $('<a>')
            //             .attr('target', '_blank')
            //             .prop('href', "http://google.com");_
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
            "options": JSON.stringify(comboboxContextOptions),
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
                        if (debug) {
                            console.log(elem);
                        }

                        loadImage(elem.tdocs_id, function(response){
                            var item = $("#item-template").html();
                            item = $(item);

                            var data = JSON.parse(elem.obs);
                            
                            data = data[0];
                            if (debug) {
                                console.log(data);
                            }                            
                            $(item).find("img").attr("src",response.link);
                            $(item).find(".img-title").html(data.filename);                            
                            $(item).attr("id",data.tdocsId);                            

                            $(item).find(".btn-remove-upload").data("tdocsid", data.tdocsId);                            
                            $(item).find(".btn-remove-upload").data("reference", data.objectId);
                            $(item).find(".btn-remove-upload").click(removeButtonAction);

                            $(item).find('.box-uploader-download-arquivo').data({ link: response.link, arquivo: data.filename, uniqueid: data.tdocsId });
                            
                            $(item).find('.input-type').val(comboboxContextOptions[data.typeId]);

                            $("#files").prepend(item);
                        }, true);                    
                    }
                });
                }
            }
        });
    }    

    function removeRow(field)
    {
        var tdocsId     = field.context.dataset.tdocsid; 
        var referenceId = field.context.dataset.reference;
        var row         = field.parents(".gallindex");
        
        new Promise((resolve, reject) => {
            $.ajax('fileuploader-iframe-v2.php?removeFile=file', {
                async: true,
                timeout: 60000,
                type: 'POST',
                data: {
                    id: tdocsId, 
                    referencia: referenceId,
                    hashTempRef: true
                }
            })
            .fail((res) => {
                $(field).find('.btn-delete-file').prop({ disabled: false }).html('<i class="fa fa-trash"></i>');
                reject(res);
            })
            .done((res, req) => {
                if (req == 'success') {
                    (row).remove();
                    toastr.info("<?=traduz('Arquivo removido com sucesso')?>");
                    resolve(res);
                } else {
                    $(field).find('.btn-delete-file').prop({ disabled: false }).html('<i class="fa fa-trash"></i>');
                    toastr.error("<?=traduz('Erro ao remover o arquivo')?>");
                    reject(res);
                }
            });
        })
    
        // window.parent.BoxUploader.deleteFile(tdocsId)
        // .then((res) => {
        //     $(row).fadeOut(1000,function(){
        //         $(row).remove();
        //     });
        //     toastr.info("<?=traduz('Arquivo removido com sucesso')?>");
        // })
        // .catch((res) => {
        //     toastr.error("<?=traduz('Erro ao remover o arquivo')?>");
        // })
    }

    function deleteAction(field)
    {
        field.prop('disabled', true);
        field.html('<i class="fas fa-spinner fa-pulse fa-sm"></i>');
        removeRow(field);
    }

    function getFileImage()
    {
        $('.icon-placeholder').on('load', function() {
            $.ajax({
                url: 'fileuploader-iframe-v2.php',
                type: 'POST',
                data: {
                    ajax_img_link: true,
                    link: imgLink
                },
            }).done(function(data) {
                if(true) {
                    return imgLink;
                }
            })
        })
    }


    function updateMobile(data, tdocsId, classificacao) 
    {
        var img = '<img class="icon-placeholder" src="'+ data.link + '" alt="icon-placeholder">';
        var date = new Date().toLocaleDateString('pt-BR', {hour: "numeric", minute: "numeric"});

        var fileType = ("<?=traduz('')?>");
        $.each(comboboxContextOptions, function(key, value) {
            if (classificacao && key === classificacao) {
                fileType = value;
            }
        });

        $('.gridbody').append(
            '<tr id="gall-0" class="rowa gallindex odd" role="row">'+
                '<td class="icon itemicon text-center" style="width: 50px; height: 50px;">'+
                    img +
                '</td>'+
                '<td class="name_sorting_1">'+
                    '<div class="relative">'+

                    '<a class="item file full-lenght thumb vfm-gall" href="'+ data.link +'" target="_blank">'+ data.file_name + '</a>'+
                        '<span class="hover">'+
                            '<i class="fa fa-search-plus fa-fw"></i>'+
                        '</span>'+
                    '</div>'+
                '</td>'+
                <?php if ($dados_descricao) { ?>
                '<td class="name_sorting_1">'+
                    '<div class="relative">'+
                        '<p class="item file full-lenght thumb vfm-gall"><?php echo $dados_descricao; ?>'+
                        '</p>'+
                    '</div>'+
                '</td>'+
                <?php } ?>
                '<td class="name_sorting_1">'+
                    '<div class="relative">'+
                        '<p class="item file full-lenght thumb vfm-gall">'+ fileType +'</p>'+
                    '</div>'+
                '</td>'+
                '<td class="name_sorting_1">'+
                    '<div class="relative">'+
                        '<p class="item file full-lenght thumb vfm-gall">' + date + '</p>'+
                    '</div>'+
                '</td>'+
                '<td class="icon text-center hidden-xs">'+

                    '<div class="btn-delete-file" data-reference="<?php echo "$referencia"; ?>" data-tdocsid="'+ tdocsId + '" data-thisname="'+ data.file_name + '"onclick="deleteAction($(this));">'+
                        '<i class="fas fa-times"></i>'+
                    '</div>'+
                '</td>'+
            '</tr>');    
    }

    function tableAdd(file, row, classificacao, index, mimeType = null)
    {   
        var check = 1;

        var date = new Date().toLocaleDateString('pt-BR', {hour: "numeric", minute: "numeric"});

        var tdocsId = file['fileId'].unique_id;

        var fileType = ("<?=traduz('')?>");
        $.each(comboboxContextOptions, function(key, value) {
            if (classificacao && key === classificacao) {
                fileType = value;
            }
        });

        //var mimeType = file['fileId']['document'] !== undefined ? file['fileId']['document']['mime_type'] : file['fileType'].originalFiles[0]['type'];
        var fileName = file['fileId']['document'] !== undefined ? file['fileId']['document']['file_name'] : file['fileId'].file_name;

    if (index == 1 && $('#filetable > thead > tr').html() == undefined) {
        $('.gridhead').append(
           '<tr class="rowa one" role="row">'+
               '<td class="icon itemicon text-center sorting_disabled" rowspan="1" colspan="1" style="width: 75px;" aria-label=" ">'+
               '</td>'+
               '<td class="mini h-filename name sorting_asc" tabindex="0" aria-controls="filetable" rowspan="1" colspan="1" style="width: 400px;" aria-sort="ascending">'+
                   '<span class="hidden-xs sorta nowrap">Nome do arquivo</span>'+
               '</td>'+
               '<?php if ($dados_descricao) { ?>'+
               '<td class="mini h-description type" tabindex="0" aria-controls="filetable" rowspan="1" colspan="1" style="width: 500px;" aria-sort="ascending">'+
                   '<span class="hidden-xs sorta nowrap"><?=traduz('Descricao')?></span>'+
               '</td>'+
               '<?php } ?>'+
               '<td class="mini h-filetype type" tabindex="0" aria-controls="filetable" rowspan="1" colspan="1" style="width: 547px;" aria-sort="ascending">'+
                   '<span class="hidden-xs sorta nowrap">Tipo de arquivo</span>'+
               '</td>'+
               '<td class="mini h-inputdate date" tabindex="0" aria-controls="filetable" rowspan="1" colspan="1" style="width: 547px;" aria-sort="ascending">'+
                   '<span class="hidden-xs sortal nowrap">Data do Envio</span>'+
               '</td>'+
               '<td class="mini text-center gridview-hidden sorting_disabled" rowspan="1" colspan="1" style="width: 39px;" aria-label=" ">'+
                       '<i class="fas fa-trash" aria-hidden="true"></i>'+
               '</td>'+
           '</tr>'
       );
    }


        if (mimeType.includes('image')) {
            var img = '<img class="icon-placeholder" src="'+ file['fileId'].link + '" alt="icon-placeholder">';
        } else if (mimeType.includes('application')) {
            var img = '<i id="icon-placeholder" class="fas fa-file-alt fa-2x"></i>'
        } else if (mimeType.includes('video')) {
            var img = '<i id="icon-placeholder" class="fas fa-video fa-2x"></i>'
        } else if (mimeType.includes('audio')) {
            var img = '<i id="icon-placeholder" class="fas fa-file-audio fa-2x"></i>'
        } else if (mimeType.includes('text')) {
            var img = '<i id="icon-placeholder" class="fas fa-file-text fa-2x"></i>'
        } 
        
        $('.gridbody').append(
            '<tr id="gall-0" class="rowa gallindex odd" role="row">'+
                '<td class="icon itemicon text-center" style="width: 50px; height: 50px;">'+
                    img +
                '</td>'+
                '<td class="name_sorting_1">'+
                    '<div class="relative">'+
                    '<a class="table-add item file full-lenght thumb vfm-gall" href="'+ updateUrl(tdocsId, file['fileId'].link, fileName) +'" target="_blank">'+ fileName + '</a>'+
                        '<span class="hover">'+
                            '<i class="fa fa-search-plus fa-fw"></i>'+
                        '</span>'+
                    '</div>'+
                '</td>'+
                <?php if ($dados_descricao) { ?>
                '<td class="name_sorting_1">'+
                    '<div class="relative">'+
                        '<p class="item file full-lenght thumb vfm-gall"><?php echo $dados_descricao; ?>'+
                        '</p>'+
                    '</div>'+
                '</td>'+
                <?php } ?>
                '<td class="name_sorting_1">'+
                    '<div class="relative">'+
                        '<p class="item file full-lenght thumb vfm-gall">'+ fileType +'</p>'+
                    '</div>'+
                '</td>'+
                '<td class="name_sorting_1">'+
                    '<div class="relative">'+
                        '<p class="item file full-lenght thumb vfm-gall">' + date + '</p>'+
                    '</div>'+
                '</td>'+
                '<td class="icon text-center hidden-xs">'+
                    '<div class="btn-delete-file" row='+ row +' data-reference="<?php echo $referencia;?> " data-tdocsid="'+ tdocsId + '" data-thisname="'+ file['fileId'].file_name + '"onclick="deleteAction($(this));">'+
                        '<i class="fas fa-times"></i>'+
                    '</div>'+
                '</td>'+
            '</tr>');    
    }

    function updateUrl(id, link, name)
    {
        return "../../plugins/fileuploader/fileuploader-download-arquivo.php?hash="+id+"&l="+btoa(link)+"&a="+name+""; 
    }

    function hideInfo()
    {
        $('.upload-bar').fadeOut(1000);
        $('#progress-info').fadeOut(1000);
        $('#env-send-all').fadeOut(1000);
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


