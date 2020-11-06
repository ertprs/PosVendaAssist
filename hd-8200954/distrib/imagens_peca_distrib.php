<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once "../class/tdocs.class.php";

include_once '../class/AuditorLog.php';

$peca = $_REQUEST["peca"];

$sql = "SELECT fabrica,peca 
		FROM tbl_peca 
		WHERE peca = $peca";
$res = pg_query($con, $sql);

$fabrica_peca = pg_fetch_result($res, 0, "fabrica");

$tDocs       = new TDocs($con, $fabrica_peca);

if ($_POST["ajax_anexo_upload"] == true) {

	$posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"] ? : $peca;

    $arquivo = $_FILES["anexo_upload_{$posicao}"];


    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'gif'))) {

            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, gif'),'posicao' => $posicao);

        } else {

            if ($_FILES["anexo_upload_{$posicao}"]["tmp_name"]) {

            	$tDocs->setContext('peca', 'distrib');

            	$anexoID = $tDocs->uploadFileS3($arquivo, $chave, $peca != $chave);
                $arquivo_nome = $tDocs->sentData;

                if (!$anexoID) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
                } 

            }

            if (empty($anexoID)) {
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
            }

            $link = '//api2.telecontrol.com.br/tdocs/document/id/'.$arquivo_nome['tdocs_id'].'/file/'.$arquivo_nome['filename'];
            $href = $link;
            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode(' 2'),'posicao' => $posicao);
            } else {

                $retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao');
            }
        }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}

?>
<html>
<head>
	<title>Cadastro informações adicionais</title>

    <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	<link type="text/css" rel="stylesheet" href="css/css.css">
	<link type="text/css" rel="stylesheet" media="screen" href="../admin/bootstrap/css/bootstrap.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="../admin/bootstrap/css/extra.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="../admin/bootstrap/css/bootstrap.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="../admin/css/tc_css.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="../admin/css/tooltips.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
	<link type="text/css" rel="stylesheet" media="screen" href="../admin/bootstrap/css/ajuste.css" />
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <script src='../admin/plugins/jquery.form.js'></script>
	<script src="../admin/plugins/FancyZoom/FancyZoom.js"></script>
	<script src="../admin/plugins/FancyZoom/FancyZoomHTML.js"></script>
	

	<script>
	$(function(){

			/* ANEXO DE FOTOS */
        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

            $("#div_anexo_"+i).find("button").hide();
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
                console.log(data);
            if (data.error) {
                alert(data.error);
                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                $("#div_anexo_"+data.posicao).find("button").show();
                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
            } else {
                var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                $(imagem).attr({ src: data.link });

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
            $("#div_anexo_"+data.posicao).find("button").show();
            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
        }
        /* FIM ANEXO DE FOTOS */
    	});

    });    
	</script>	
</head>
<body>

<?
$sql = "SELECT tbl_peca.fabrica,tbl_peca.peca,tbl_peca.referencia,tbl_peca.descricao,tbl_fabrica.nome,tbl_peca.parametros_adicionais ,tbl_peca.peso,tbl_peca.informacoes
		FROM tbl_peca
        JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica 
		WHERE peca = $peca";
$res = pg_query($con, $sql);

$fabrica_peca = pg_fetch_result($res, 0, "fabrica");
$fabrica_nome = pg_fetch_result($res, 0, "nome");
$referencia   = pg_fetch_result($res, 0, "referencia");
$descricao    = pg_fetch_result($res, 0, "descricao");
$peso         = pg_fetch_result($res, 0, "peso");
$informacoes  = pg_fetch_result($res, 0, "informacoes");

$informacoes_adicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"));	

?>
<br />
    <table class="table table-fixed table-bordered" style="width: 90%;position: relative;left: 5%;">
            <tr>
                <th colspan="4" class="titulo_tabela">Peça</th>
            </tr>
            <tr>
                <th class="titulo_coluna">Referência</th>
                <td><?= $referencia ?></td>
                <th class="titulo_coluna">Descrição</th>
                <td><?= $descricao ?></td>
            </tr>
            <tr>
                <th class="titulo_coluna">Fábrica</th>
                <td class="tal" colspan="3" style="border-top: 1px solid #dddddd;"><?= $fabrica_nome ?></td>
            </tr>
    </table> 
    <table class="table table-fixed table-bordered" style="width: 90%;position: relative;left: 5%;">       
            <tr>
                <th colspan="4" class="titulo_tabela">Informações Adicionais</th>
            </tr>
            <tr>
                <th class="titulo_coluna">Peso</th>
                <td class="tac"><?= $peso ?></td>
                <th class="titulo_coluna">Altura</th>
                <td class="tac"><?= $informacoes_adicionais->altura ?></td>
            </tr>
            <tr>
                <th class="titulo_coluna">Largura</th>
                <td class="tac"><?= $informacoes_adicionais->largura ?></td>
                <th class="titulo_coluna">Comprimento</th>
                <td class="tac"><?= $informacoes_adicionais->comprimento ?></td>
            </tr>
            <tr>
                <th class="titulo_coluna">Informações</th>
                <td colspan="3"><?= $informacoes ?></td>
            </tr>
    </table>
    <div class="tc_formulario" style="width: 75%;position: relative;left: 12.5%;">
    <div class="titulo_tabela">Anexos da peça</div>
    <br />
    <?php 
        $tDocs->setContext('peca','distrib');
        $info = $tDocs->getDocumentsByRef($peca)->attachListInfo;
        $pos  = 1;

        if (count($info) > 0) {

            foreach ($info as $k => $vAnexo) {
                $info[$k]["posicao"] = $pos++;
            }

        } 

        for ($i=1; $i <= 3; $i++) { 

            $imagemAnexo = "../imagens/imagem_upload.png";
            $linkAnexo   = "#";

                if (count($info) > 0) {
                	$vAnexo = array_shift($info);

                    if ($vAnexo["posicao"] != $i) {
                        continue;
                    }

                    $linkAnexo   = $vAnexo["link"];
                    $imagemAnexo = $vAnexo["link"];
                    $tdocs_id[$i] = $vAnexo["tdocs_id"];

                }
    ?>
    <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
        <?php if ($linkAnexo != "#") { ?>
        <a href="<?=$linkAnexo?>" target="_blank" >
        <?php } ?>
            <img src="<?=$imagemAnexo?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
        <?php if ($linkAnexo != "#") { ?>
        </a>
        <?php } ?>
        <button type="button" class="btn btn-mini btn-primary btn-block" name="anexar" rel="<?=$i?>" >Anexar</button>
        <img src="../imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
        <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo[$i]?>" />
    </div>
    	<?php } 
    	for ($i = 1; $i <=  3; $i++) {?>
            <form name="form_anexo" method="post" action="relatorio_log_info_peca.php" enctype="multipart/form-data" style="display: none !important;" >
                <input type="file" name="anexo_upload_<?=$i?>" value="" />
                <input type="hidden" name="ajax_anexo_upload" value="t" />
                <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
                <input type="hidden" name="peca" value="<?=$peca?>" />
                <input type="hidden" name="anexo_chave" value="<?=$tdocs_id[$i]?>" />
            </form>
    	<?php }?>
        <br /><br />
    </div>
<br />
</body>
</html>