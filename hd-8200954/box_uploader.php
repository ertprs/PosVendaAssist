<?php
if (empty($login_fabrica) || (empty($login_admin) && empty($login_posto))) {
    die;
}

/* 02/04/2020 - kiq
    caso seja necessário mais de um boxuploader 
    no mesmo arquivo, chama as dependencias do plugin
    apenas uma vez, evitando erros
*/
$totalIncludes += 1;

include_once __DIR__.'/fn_traducao.php';

$boxUploader["div_class"]                = (!empty($boxUploader["div_class"])) ? $boxUploader["div_class"] : "tc_formulario";
$boxUploader["titulo_tabela"]            = (!empty($boxUploader["titulo_tabela"])) ? $boxUploader["titulo_tabela"] : traduz("Anexo(s)");
$boxUploader["label_botao"]              = (!empty($boxUploader["label_botao"])) ? $boxUploader["label_botao"] : traduz("Anexar arquivos");
$boxUploader["tamanho_botao"]              = (!empty($boxUploader["tamanho_botao"])) ? $boxUploader["tamanho_botao"] : "";
$areaAdminCliente                        = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'');
$pagina_atual                            = str_replace('/assist/','',$_SERVER['PHP_SELF']);

if (empty($boxUploader['root'])) {
    $boxUploader['root'] = 'box-uploader-app';
}

?>
<link rel='stylesheet' href='<?=ADMCLI_BACK?>box_uploader.css' />
<div id='<?=$boxUploader['root']?>' class="div_boxuploader_principal" style="display: none;">
    <div id="<?=$boxUploader['div_id']?>" class="<?=$boxUploader['div_class']?>" style="padding-bottom: 1px;" >
        <?php
        if ($boxUploader['hidden_title'] !== true) {
        ?>
            <div class="titulo_tabela" style="margin-bottom: 15px">
                <?=$boxUploader["titulo_tabela"]?>
            </div>
        <?php
        }
        ?>
        <div class='box-uploader-component'>
            <?php
            if (!empty($boxUploader['prepend'])) {
            ?>
                <div style='text-align: center;'>
                    <?=$boxUploader['prepend']?>
                </div>
            <?php
            }

            if ($mensagem_anexo_obrigatorio != "") {
            ?>
                <div class="alert alert-info" style="width:40%;margin:20px auto 0 auto;text-align:center;">
                    <h6><?=$mensagem_anexo_obrigatorio?></h6>
                </div>
            <?php
            }

            if ($boxUploader["hidden_button"] !== true){
            ?>
                <div class="row-fluid danexo"> 
                    <div class="span2" ></div>
                    <div class="span8 tac" >
                        <button type="button" class="btn btn-primary <?php echo $boxUploader["tamanho_botao"];?> btn-call-fileuploader" rel="2"><i class="fa fa-upload"></i> <?=$boxUploader["label_botao"];?></button>
            			<input type='hidden' name='anexo_chave' value='<?=$boxUploader['unique_id']?>' />
            			<input type='hidden' id='openBoxUploader' value='' />
                    </div>
                </div>
            <?php
            }
            ?>
            
            <?=$boxUploader["append"]?>
            
            <div class="row-fluid danexo" >
                <div class="box-uploader-anexos span10" style="width: 600px !important;" ></div>
            </div>
        </div>
        <div class='box-uploader-loading' style='text-align: center;' >
            <i class='fa fa-spinner fa-pulse fa-5x'></i>
        </div>
    </div>

    <div class='modal-view-file modal hide fade'>
        <div class='modal-header'>
            <button type='button' class='close pull-left' data-dismiss='modal' aria-hidden='true' style="display: block; float: left;"><i class='fa fa-arrow-left'></i> <?=traduz('Voltar')?></button>
            <h3 style='text-align: right;'></h3>
        </div>
        <div class='modal-body tac'>
            <div class='modal-image'>
                <img src='' class='img-rounded' style="max-width: 82%;" />
            </div>
            <div class='modal-video'></div>
            <div class='modal-audio' style='text-align: center;'></div>
            <div class='modal-file' style='text-align: center;'>
                <a href='' target='_blank'>
                    <i class='fa fa-download fa-5x'></i>
                    <br />
                    <span></span>
                </a>
            </div>
        </div>
    </div>
</div>
    <?php 
    if ($totalIncludes <= 1) { ?>
        <script src='<?=ADMCLI_BACK?>box_uploader.js'></script>
        <script src='<?=ADMCLI_BACK?>box_uploader_file.js?v=2'></script>
    <?php
    }
    ?>
<script>

$(function(){
    $("#<?= $boxUploader['root'] ?>").show();
});

<?php
	if(!empty($login_fabrica)) {
		$cond = " AND (tbl_anexo_tipo.fabrica = $login_fabrica or tbl_anexo_tipo.fabrica isnull) ";
	}

    $sqlTipos = "SELECT 
            trim(tbl_anexo_contexto.nome) as contexto,
            trim(tbl_anexo_tipo.nome) as label,
            trim(tbl_anexo_tipo.codigo) as value
        FROM tbl_anexo_tipo
            JOIN tbl_anexo_contexto ON tbl_anexo_tipo.anexo_contexto = tbl_anexo_contexto.anexo_contexto
        WHERE UPPER(tbl_anexo_contexto.nome) = UPPER('".$boxUploader['context']."')
            $cond";
    $resTipos = pg_query($con, $sqlTipos);

    while ($dadosTipos = pg_fetch_object($resTipos)) {
        $json_objetos[$dadosTipos->value] = utf8_encode($dadosTipos->label);
    }
?>
   
    if (objDadosBoxuploader == undefined) {
        var objDadosBoxuploader = {};
    }
    
    var boxUploader;
    $(function() {
        /*
            alteração realizada para possibilitar
            mais de um contexto na mesma página
        */
        objDadosBoxuploader['<?= $boxUploader['context'] ?>'] = {
            context: '<?=$boxUploader['context']?>',
            referenceId: '<?=$boxUploader['unique_id']?>',
            hashTemp: '<?=$boxUploader['hash_temp']?>',
            edit: <?=($boxUploader["hidden_button"] == true) ? 'false' : 'true'?>,
            root: '<?=$boxUploader['root']?>',
            currentPage: '<?=$pagina_atual ?>',
            typesExibe: <?= json_encode($json_objetos) ?>
        };

        boxUploader = new BoxUploader(objDadosBoxuploader['<?= $boxUploader['context'] ?>']);

        boxUploader.init();
    	<? if(count($msg_erro["msg"]) > 0) { ?>
    		boxUploader.showFile();
    	<? } ?>
    });


</script>
