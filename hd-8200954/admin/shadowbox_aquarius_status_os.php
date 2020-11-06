<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    include_once 'autentica_admin.php';
    include_once '../class/tdocs.class.php';
    include_once '../helpdesk/mlg_funciones.php';

} else {
    include_once 'autentica_usuario.php';
    include_once 'class/tdocs.class.php';
    include_once 'helpdesk/mlg_funciones.php';
    $caminhoImagens = "admin/";
}

include_once 'funcoes.php';
$tDocs       = new TDocs($con, $login_fabrica, "os");

if ($_POST["ajax_anexo_upload"] == true) {

    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

            if ($_FILES["anexo_upload_{$posicao}"]["tmp_name"]) {

                $anexoID      = $tDocs->sendFile($_FILES["anexo_upload_{$posicao}"]);
                $arquivo_nome = json_encode($tDocs->sentData);

                if (!$anexoID) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
                } 

            }

            if (empty($anexoID)) {
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
            }

            $link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $href = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $tdocs_id = $anexoID;
            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode(' 2'),'posicao' => $posicao);
            } else {
                $retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao','tdocs_id');
            }

    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}

if ($_POST["ajax_remove_anexo"] == true) {

    $posicao    = $_POST["posicao"];
    $tdocs_id   = $_POST["tdocsid"];

    $tDocs->setContext('os');

    $anexoID = $tDocs->deleteFileById($tdocs_id);

    if (!$anexoID) {
        $retorno = array('erro' => true, 'msg' => utf8_encode('Erro ao remover arquivo'),'posicao' => $posicao);
    }  else {

        $retorno = array('sucesso' => true, 'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}

$acao = $_REQUEST['acao'];
$os = $_REQUEST['os'];
if ($_POST) {

	$anexo      = $_POST['anexo'];

	foreach ($anexo as $key => $value) {
        if (empty($value)) {
            unset($anexo[$key]);
        }
    }

	$campos_adicionais = '';
	$sqlConsultaOs = "SELECT campos_adicionais, status_checkpoint FROM tbl_os_campo_extra JOIN tbl_os USING (os) WHERE tbl_os.fabrica = {$login_fabrica} AND os = {$os};";
	$resConsultaOs = pg_query($con, $sqlConsultaOs);
	if (pg_num_rows($resConsultaOs) > 0) {
		$campos_adicionais = json_decode(pg_fetch_result($resConsultaOs, 0, 'campos_adicionais'));
	}
	if (isset($_POST['num_nf_entrada'])) {
		$campos_adicionais->nf_entrada = $_POST['num_nf_entrada'];
		$tipo_anexo = 'nf_entrada';
	} else if (isset($_POST['num_nf_saida'])) {
		$campos_adicionais->nf_saida = $_POST['num_nf_saida'];
		$tipo_anexo = 'nf_saida';
	} else if (isset($_POST['rastreio'])) {
		$campos_adicionais->rastreio = $_POST['rastreio'];
	}
	$jsonCampos = json_encode($campos_adicionais);
    if (pg_num_rows($resConsultaOs) > 0) {    	
		$tbl_campo_exra = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$jsonCampos}' WHERE fabrica = {$login_fabrica} AND os = {$os}";
    } else {
    	$tbl_campo_exra = "INSERT INTO tbl_os_campo_extra(campos_adicionais, fabrica, os) VALUES ('{$jsonCampos}', {$login_fabrica}, {$os})";
    }

    $status_atual = pg_fetch_result($resConsultaOs, 0, 'status_checkpoint');
    $proximoStatus = "";

    switch ($status_atual) {
    	case 40:
    		$proximoStatus = 1;
    		break;
    	case 41:
    	case 39:
    		$proximoStatus = 42;
    		break;
    	case 42:
    		$proximoStatus = 43;
    		break;
    	default:
    		$msg_erro = 'status invalido';
    		break;
    }
    $tbl_os = "UPDATE tbl_os SET status_checkpoint = '{$proximoStatus}' WHERE fabrica = {$login_fabrica} AND os = {$os}";

    pg_query($con,"BEGIN TRANSACTION");
	pg_query($con, $tbl_campo_exra);
	pg_query($con, $tbl_os);

	if (!empty($tipo_anexo)) {
		foreach ($anexo as $vAnexo) {
	        if (empty($vAnexo)) {
	            continue;
	        }
	        $dadosAnexo = json_decode($vAnexo, 1);
	        $anexoID = $tDocs->setDocumentReference($dadosAnexo, $os, "anexar", false, "os");
	        
	        $tdocs_id = $_POST['tdocs_id'];

	        $sqlTdocs = "UPDATE tbl_tdocs SET referencia_id = $os, referencia = '{$tipo_anexo}' WHERE tdocs_id = '{$tdocs_id}'";
	        $resTdocs = pg_query($con, $sqlTdocs);

	        if (!$anexoID) {
	            $msg_erro = 'Erro ao fazer upload do anexo';
	        }
	    }
	}

	if (pg_last_error()) {
    	$msg_erro = 'erro';
        pg_query($con,"ROLLBACK TRANSACTION");
    } else {
        pg_query($con,"COMMIT TRANSACTION");
    }
    
	if (strlen(pg_last_error()) == 0) {
		$msg = "Gravado com sucesso!";
	}

}
?>
<head>
	<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
	<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
	<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
	<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
	<script src="bootstrap/js/bootstrap.js"></script>
	<script src='plugins/jquery.alphanumeric.js'></script>
	<script src='plugins/jquery.form.js'></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<style type="text/css">
		h4 {
			font-weight: bold;
		}
		input[type=number]::-webkit-inner-spin-button { 
		    -webkit-appearance: none;
		    cursor:pointer;
		    display:block;
		    width:8px;
		    color: #333;
		    text-align:center;
		    position:relative;
		}
		input[type=number] { 
		   -moz-appearance: textfield;
		   appearance: textfield;
		   margin: 0; 
		}
	</style>
	<script>
		$(function(){
			$("div[id^=div_anexo_]").each(function(i) {
	            var tdocs_id = $("#div_anexo_"+i).find(".btn-remover-anexo").data("tdocsid");
	            if (tdocs_id != '' && tdocs_id != null && tdocs_id != undefined) {
	                $("#div_anexo_"+i).find("button[name=anexar]").hide();
	                $("#div_anexo_"+i).find(".btn-remover-anexo").show();
	            } else {
	                $("#div_anexo_"+i).find(".btn-remover-anexo").hide();
	            }
	        });

	        /* REMOVE DE FOTOS */
	        $(document).on("click", ".btn-remover-anexo", function () {
	            var tdocsid = $(this).data("tdocsid");
	            var posicao = $(this).data("posicao");

	            if (tdocsid != '' && tdocsid != null && tdocsid != undefined) {

	                $.ajax({
	                    url: 'shadowbox_aquarius_status_os.php',
	                    type: "POST",
	                    dataType:"JSON",
	                    data: { 
	                        ajax_remove_anexo: true,
	                        tdocsid: tdocsid,
	                        posicao: posicao
	                    }
	                }).done(function(data) {
	                    if (data.erro == true) {
	                        alert(data.msg);
	                        return false;
	                    } else {
	                        alert("Removido com sucesso.");
	                        $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
	                        $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
	                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").hide();
	                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", "");
	                        $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val("");
	                        $("#div_anexo_"+data.posicao).find("img.anexo_thumb").attr("src", "imagens/imagem_upload.png");
	                    }
	                });

	            }

	        });

	        /* ANEXO DE FOTOS */
	        $("input[name^=anexo_upload_]").change(function() {
	            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

	            $("#div_anexo_"+i).find("button[name=anexar]").hide();
	            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
	            $("#div_anexo_"+i).find("img.anexo_loading").show();

	            $(this).parent("form").submit();
	        });

	        $("button[name=anexar]").click(function() {
	            var posicao = $(this).attr("rel");
	            $("input[name=anexo_upload_"+posicao+"]").click();
	        });

	        $("form[name=form_anexo]").ajaxForm({
	            complete: function(data) {
	                data = $.parseJSON(data.responseText);
		            if (data.error) {
		                alert(data.error);
		                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
		                $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
		                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
		            } else {
		                var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
		               
		                if (data.ext == 'pdf') {
		                	$(imagem).attr({ src: "imagens/pdf_icone.png" });
		                } else if (data.ext == "doc" || data.ext == "docx") {
		                	$(imagem).attr({ src: "imagens/docx_icone.png" });
		                } else {
		                	$(imagem).attr({ src: data.link });
		                }

		                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

		                var link = $("<a></a>", {
		                    href: data.href,
		                    target: "_blank"
		                });

		                $(link).html(imagem);

		                $("#div_anexo_"+data.posicao).prepend(link);

		                $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
		            }

		            $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
		            $("#div_anexo_"+data.posicao).find("button[name=anexar]").hide();
		            $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").show();
		            $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", data.tdocs_id);
		            $("#div_anexo_"+data.posicao).find("#tdocs_id").val(data.tdocs_id);
		            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
		        }
	        /* FIM ANEXO DE FOTOS */
	    	});

	    	$("#gravar").click(function(){
	    		if (($("input[name=num_nf_entrada]").val() == "" || $("input[rel=anexo]").val() == "") && ($("#acao").val() == 'nf_entrada' || $("#acao").val() == 'nf_saida')) {
	    			alert("Número e anexo da nota fiscal obrigatórios")
	    		} else {
	    			$("#formulario_nf").submit();
	    		}

	    	});
		});
	</script>
</head>
<form method="POST" id="formulario_nf">
<input type="hidden" name="os" value="<?=$os?>">
<input type="hidden" name="acao" id="acao" value="<?=$acao?>">
<div style="text-align: center;" class="form-group">
<?php
	if ($msg_erro != NULL && $msg_erro != 'NULL' && $msg_erro == '') {
		?>
		<div class="alert alert-danger">
		 	<strong>Erro!</strong> Problema ao salvar!
		</div>
		<?php
		exit;
	} else if ($os == null || $os == '') {
		?>
		<div class="alert alert-danger">
		 	<strong>Erro!</strong> OS Obrigatoria.
		</div>
		<?php
		exit;
	} else if (strlen($msg)) {
		?>
		<div class="alert alert-success">
		 	<strong> Cadastrado com Sucesso!</strong>
		</div>
		<button type="button" onclick="window.parent.location.reload();" id="fechar_shadowbox" class="btn btn-default">Fechar</button>
		<?php
		exit;
	}

	if ($acao == 'nf_entrada') { ?>
		<h4>Número da Nota Fiscal de Entrada</h4>
		<input type="number" class="form-control" style="width: 50%;display: inline-grid;" name="num_nf_entrada">
	<?php } else if ($acao == 'nf_saida') { ?> 
		<h4>Número da Nota Fiscal de Saída</h4>
		<input type="number" class="form-control" style="width: 50%;display: inline-grid;" name="num_nf_saida">
	<?php } else if ($acao == 'rastreio') { ?>
		<h4>Rastreio</h4>
		<input type="text" class="form-control" style="width: 90%;display: inline-grid;" name="rastreio">
	<?php } ?>
</div>
	<center>
	<?php
		if ($acao == 'nf_entrada' || $acao == 'nf_saida') {
	        $tDocs->setContext('os');

	        for ($i=1; $i <= 1 ; $i++) {

	            $imagemAnexo = "imagens/imagem_upload.png";
	            $linkAnexo   = "#";
	            $tdocs_id   = "";

	            ?>
	            <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
	                <?php if ($linkAnexo != "#") { ?>
	                <a href="<?=$linkAnexo?>" target="_blank" >
	                <?php } ?>
	                    <img src="<?=$imagemAnexo?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
	                <?php if ($linkAnexo != "#") { ?>
	                </a>

	                <?php } ?>
	                <button type="button" style="display: none;" class="btn btn-mini btn-remover-anexo btn-danger btn-block" data-tdocsid="<?=$tdocs_id?>" data-posicao="<?=$i?>" >Remover</button>
	                <button type="button" class="btn btn-mini btn-primary btn-block" name="anexar" rel="<?=$i?>" >Anexar</button>
	                <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
	                <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo[$i]?>" />
	                <input type="hidden" name="tdocs_id" value="" id="tdocs_id" />
	            </div>
	            <br />
	        <?php
	    	} 
    	}?>
    </center>
    <br />
<div style="text-align: center;">
	<button type="button" class="btn btn-success" id="gravar">Gravar</button>
	<button type="button" onclick="window.parent.Shadowbox.close();" class="btn btn-danger">Cancelar</button>
</div>
</form>
<?php 
if ($acao == 'nf_entrada' || $acao = 'nf_saida') {
	for ($i = 1; $i <=  1; $i++) { ?>
	    <form name="form_anexo" method="post" action="shadowbox_aquarius_status_os.php" enctype="multipart/form-data" style="display: none !important;" >
	        <input type="file" name="anexo_upload_<?=$i?>" value="" />
	        <input type="hidden" name="ajax_anexo_upload" value="t" />
	        <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
	        <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
	    </form>
	<?php 
	}
}
?>