<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'anexaNFDevolucao_inc.php';

$msg_erro="";
$msg="";

$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$ok_aceito="nao";

if (filter_input(INPUT_POST,"ajax_nf")) {

    $tDocsNf = new TDocs($con, $login_fabrica);

    $faturamento        = filter_input(INPUT_POST,'faturamento');
    $nova_nota_fiscal   = filter_input(INPUT_POST,'nova_nota_fiscal');
    $arquivo            = $_FILES['anexo_nf_'.$faturamento];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'docx','doc', 'pdf', 'gif'))) {

            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpg, jpeg, docx, doc, pdf ou gif'),"faturamento" => $faturamento);

        } else {
            $anexoID =  $tDocsNf->uploadFileS3($arquivo,$faturamento,true,'lgr');

            if (!$anexoID) {

                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),"faturamento" => $faturamento);

            } else {
                $link = $tDocsNf->getDocumentsByRef($faturamento,'lgr')->url;
	            $href = $tDocsNf->getDocumentsByRef($faturamento,'lgr')->url;

	            if (!strlen($link)) {
	                $retorno = array('error' => utf8_encode(' 2'),"faturamento" => $faturamento);
	            } else {
                    if ($login_fabrica == 3 && !empty($nova_nota_fiscal)) {
                        $sql = "UPDATE tbl_faturamento SET nota_fiscal = '$nova_nota_fiscal' WHERE faturamento = $faturamento AND fabrica = $login_fabrica";
                        $res = pg_query($con,$sql);
                    }

	                $retorno = compact('link', 'href', 'ext','faturamento');
	            }
            }
        }
    }

    exit(json_encode($retorno));
}

if ($_POST["ajax_anexo_upload"] == 't') {

	$tDocs       = new TDocs($con, $login_fabrica);
    $posicao = $_POST["anexo_posicao"];
    $extrato = $_POST["extrato_comprovante_lgr"];
    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'docx','doc', 'pdf', 'gif'))) {

            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpg, jpeg, docx, doc, pdf ou gif'), 'posicao' => $posicao);

        } else {

            $anexoID =  $tDocs->uploadFileS3($arquivo, $extrato, false, 'comprovantelgr', 'extrato');
            if (!$anexoID) {

                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));

            } else {

	            $link = $tDocs->getDocumentsByRef($extrato,'comprovantelgr')->url;
	            $href = $tDocs->getDocumentsByRef($extrato,'comprovantelgr')->url;

	            if (!strlen($link)) {
	                $retorno = array('error' => utf8_encode(' 2'), 'posicao' => $posicao);
	            } else {
	                $retorno = compact('link', 'href', 'ext','posicao');
	            }
            }
        }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}

###### HABILITAR ESTE IF APÒS A EFETIVAÇÃO #######
if ($extrato<144000){
//	header("Location: extrato_posto.php");
//	exit();
}


/*
POSTOS QUE PODEM ACESSAR ESTA TELA

Martello – 2073 - 595
Penha – 80039 - 1537
Janaína – 80330 - 1773
Bertolucci - 80568 - 7080
Tecservi – 80459 - 5037
NL – 80636 - 13951
Telecontrol – 93509 - 4311
A.Carneiro – 1256 - 564
-----Gaslar – 24091 - 1008----- nao mais
Centerservice 80150 - 1623
Visiontec -  80200 - 1664

*/

//header("Location: extrato_posto.php");
//exit();

$postos_permitidos = array(
	0 => 'LIXO',
	'1537',  '1773',  '7080',  '5037',  '13951', '4311',  '564',   '1623',  '1664', '595',
	'2506',  '6458',  '1511',  '1870',  '1266',  '6591',  '5496',  '14296', '6140', '1161',
	'708',   '710',   '14119', '898',   '6379',  '5024',  '388',   '2508',  '1172', '1261',
	'19724', '1523',  '1567',  '1581',  '1713',  '1740',  '1752',  '1754',  '1766', '115',
	'1799',  '1806',  '1814',  '1891',  '6432',  '6916',  '6917',  '7245',  '7256', '13850',
	'4044',  '14182', '14297', '14282', '14260', '18941', '18967', '1962',  '5419'
);

if ($extrato < 185731){# liberado para toda a rede Solicitado por Sergio Mauricio 31/08/2007 - Fabio
	if (array_search($login_posto, $postos_permitidos)===false){ //verifica se o posto tem permissao
		header("Location: extrato_posto.php");
		exit();
	}
}

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0)
	$extrato = trim($_POST['extrato']);

if (strlen($extrato)==0){
	header("Location: extrato_posto.php");
}

$ok_aceito = trim($_POST['ok_aceito']);
if ($ok_aceito=='Concordo')
	$numero_linhas = trim($_POST['qtde_linha']);

$btn_acao = trim($_POST['botao_acao']);

// verificaçao se o posto quer ver a Mao de obra mas ele ainda nï¿½o preencheu as notas
$mao = trim($_GET['mao']);

if (strlen($mao)>0 AND $mao=='sim'){
	if($extrato > '737905') {
		$cond = " or tbl_peca.produto_acabado       IS TRUE ";
	}

	$sqls = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao
		FROM    tbl_faturamento
		JOIN    tbl_faturamento_item USING (faturamento)
		JOIN    tbl_peca             USING (peca)
		WHERE   tbl_faturamento.extrato_devolucao < $extrato
		AND     tbl_faturamento.fabrica = $login_fabrica
		AND     tbl_faturamento.posto             = $login_posto
		AND     tbl_faturamento.distribuidor IS NULL
		AND     (tbl_faturamento_item.devolucao_obrig IS TRUE $cond)
		AND     tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923')
		ORDER BY  tbl_faturamento.extrato_devolucao DESC ";

		$ress = pg_query ($con,$sqls);
		$res_qtdes = pg_num_rows ($ress);
		$resultados = pg_fetch_all($ress);
		if ($res_qtdes == 0){
			$msg_erro = "";
		}else{
			$extratos = array();
			foreach($resultados as $chave => $valor) {
				$sqlD="SELECT extrato_devolucao
					FROM   tbl_faturamento
					WHERE  distribuidor = $login_posto
					AND    extrato_devolucao in( $valor[extrato_devolucao]);";
				$resD = pg_query($con,$sqlD);
				if(pg_num_rows($resD) == 0){

					if($login_fabrica == 3){
						$data_corte = " AND tbl_extrato.data_geracao > '2017-10-01 00:00:00' ";
					}else{
						$data_corte = " AND tbl_extrato.data_geracao > '2010-01-01 00:00:00' ";
					}


					$sqld = " SELECT to_char(data_geracao,'DD/MM/YYYY') as data_extrato, tbl_extrato_agrupado.aprovado
							FROM tbl_extrato
							LEFT JOIN tbl_extrato_agrupado USING (extrato)
							WHERE tbl_extrato.extrato IN ($valor[extrato_devolucao])
							AND   tbl_extrato.fabrica = $login_fabrica
							AND   tbl_extrato.posto   = $login_posto
							$data_corte
							ORDER BY tbl_extrato.extrato DESC limit 1;";
					$resd = pg_query($con,$sqld);
					if(pg_num_rows($resd) > 0){
						$data_extrato = pg_fetch_result($resd,0,'data_extrato');
						$extr_aprovado = pg_fetch_result($resd, 0, 'aprovado');

						if (empty($extr_aprovado)) {
							$msg_erro="Devem ser preenchidas as Notas Fiscais de devolução de Produtos e peças dos extratos anteriores para liberar a tela de consulta de valores de mão-de-obra - extrato $data_extrato";
						}
					}
				}else{
					$msg_erro = "";
				}
				if(!empty($msg_erro)) {
					break;
				}
			}
		}

		if(empty($msg_erro)) {
			$sqls = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao
			FROM    tbl_faturamento
			JOIN    tbl_faturamento_item USING (faturamento)
			JOIN    tbl_peca             USING (peca)
			WHERE   tbl_faturamento.extrato_devolucao = $extrato
			AND     tbl_faturamento.fabrica = $login_fabrica
			AND     tbl_faturamento.posto             = $login_posto
			AND     tbl_faturamento.distribuidor IS NULL
			AND     (tbl_faturamento_item.devolucao_obrig IS TRUE or tbl_peca.produto_acabado       IS TRUE)
			AND     tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923')
			$desblq
			ORDER BY  tbl_faturamento.extrato_devolucao DESC;";
			$ress = pg_query($con,$sqls);
			if(pg_num_rows($ress) > 0){
				$sql = "SELECT  faturamento,
						extrato_devolucao,
						nota_fiscal,
						distribuidor,
						NULL as produto_acabado,
						NULL as devolucao_obrigatoria
					FROM tbl_faturamento
					WHERE posto IN (13996,4311)
					AND distribuidor=$login_posto
					AND fabrica=$login_fabrica
					AND extrato_devolucao=$extrato
					ORDER BY faturamento ASC;";
				$res = pg_exec ($con,$sql);
				$jah_digitado=pg_numrows ($res);
				if ($jah_digitado>0){
					header("Location: new_extrato_posto_mao_obra.php?extrato=$extrato");
					exit();
				}else{
					$msg_erro="Devem ser preenchidas as Notas Fiscais de devolução de Produtos e peças para liberar a tela de consulta de valores de mão-de-obra - extrato";
				}
			}
		}

	if(strlen($msg_erro) == 0) {
		$sql = "SELECT  faturamento,
				extrato_devolucao,
				nota_fiscal,
				distribuidor,
				NULL as produto_acabado,
				NULL as devolucao_obrigatoria
			FROM tbl_faturamento
			WHERE posto IN (13996,4311)
			AND distribuidor=$login_posto
			AND fabrica=$login_fabrica
			AND extrato_devolucao=$extrato
			ORDER BY faturamento ASC;";
		//echo $sql;
		$res = pg_query ($con,$sql);
		$jah_digitado=pg_num_rows ($res);
		if ($jah_digitado>0){
			header("Location: new_extrato_posto_mao_obra.php?extrato=$extrato");
			exit();
		}else{
			$sql = "SELECT  tbl_faturamento.faturamento,
				tbl_faturamento.extrato_devolucao,
				nota_fiscal,
				distribuidor,
				NULL as produto_acabado,
				NULL as devolucao_obrigatoria
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING(faturamento)
			JOIN tbl_peca USING(peca)
			WHERE posto =$login_posto
			AND	tbl_faturamento.fabrica=$login_fabrica
			AND	tbl_faturamento.extrato_devolucao=$extrato
			AND     tbl_faturamento.distribuidor IS NULL
			AND     (tbl_faturamento_item.devolucao_obrig IS TRUE or tbl_peca.produto_acabado       IS TRUE)
			AND     tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923') ORDER BY  tbl_faturamento.extrato_devolucao DESC LIMIT 1;";
			$res = pg_query ($con,$sql);
			if(pg_num_rows($res) == 0){
				header("Location: new_extrato_posto_mao_obra.php?extrato=$extrato");
				exit;
			}
		}
	}
}

$msg = "";



$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: red
}
.menu_top3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #FA8072
}


.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.btn_anexar_comprovante_lgr {
  color: #ffffff;
  display: block;
font-size: 13px;
  width: 100%;
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
  background-color: #006dcc;
  *background-color: #0044cc;
  background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#0088cc), to(#0044cc));
  background-image: -webkit-linear-gradient(top, #0088cc, #0044cc);
  background-image: -o-linear-gradient(top, #0088cc, #0044cc);
  background-image: linear-gradient(to bottom, #0088cc, #0044cc);
  background-image: -moz-linear-gradient(top, #0088cc, #0044cc);
  background-repeat: repeat-x;
  border-color: #0044cc #0044cc #002a80;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
  filter: progid:dximagetransform.microsoft.gradient(startColorstr='#ff0088cc', endColorstr='#ff0044cc', GradientType=0);
  filter: progid:dximagetransform.microsoft.gradient(enabled=false);
}

.btn_anexar_comprovante_lgr:hover,
.btn_anexar_comprovante_lgr:active,
.btn_anexar_comprovante_lgr.active,
.btn_anexar_comprovante_lgr.disabled,
.btn_anexar_comprovante_lgr[disabled] {
  color: #ffffff;
  background-color: #0044cc;
  *background-color: #003bb3;
}

.btn_anexar_comprovante_lgr:active,
.btn_anexar_comprovante_lgr.active {
  background-color: #003399 \9;
}

</style>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/jquery.form.js"></script>
<script src="plugins/FancyZoom/FancyZoom.js"></script>
<script src="plugins/FancyZoom/FancyZoomHTML.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script language="javascript">
    $(function() {
        /* ANEXO DE COMPROVANTE LGR */
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
            	console.log(data)

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

	                setupZoom();

	                $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
		            $("#div_anexo_"+data.posicao).find("button").hide();
	            }

	            $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
	            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
        	}
    	});

        /* FIM ANEXO COMPROVANTE LGR */

        /**
         * Se a fábrica excluiu a nota
         * o Posto pode refazer o upload
         */
        $("input[name^=anexo_nf_]").change(function() {
            var i = $(this).parent("form").find("input[name=faturamento]").val();
            $("#div_nota_"+i).find("button").hide();
            $("#div_nota_"+i).find("img.anexo_thumb").hide();
            $("#div_nota_"+i).find("img.anexo_loading").show();
            $(this).parent("form").submit();
        });

        $("button[name=anexar_nota]").click(function() {
            var faturamento = $(this).attr("rel");
            var nota_fiscal = $("input[name=nota_fiscal_"+faturamento+"]").val();

            if (nota_fiscal == "") {
                alert("Incluir o número da Nota Fiscal de Devolução.");
            } else {
                $("input[name=nova_nota_fiscal]").val(nota_fiscal);
                $("input[name=anexo_nf_"+faturamento+"]").click();
            }
        });

        $("form[name=form_nf]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);
                console.log(data)

                if (data.error) {

                    alert(data.error);
                    $("#div_nota_"+data.faturamento).find("img.anexo_loading").hide();
                    $("#div_nota_"+data.faturamento).find("button").show();
                    $("#div_nota_"+data.faturamento).find("img.anexo_thumb").show();

                } else {

                    var imagem = $("#div_nota_"+data.faturamento).find("img.anexo_thumb").clone();
                    $(imagem).attr({ src: data.link });

                    $("#div_nota_"+data.faturamento).find("img.anexo_thumb").remove();

                    var link = $("<a></a>", {
                        href: data.href,
                        target: "_blank"
                    });

                    $(link).html(imagem);

                    $("#div_nota_"+data.faturamento).prepend(link);

                    setupZoom();

                    $("#div_nota_"+data.faturamento).find("input[rel=anexo_nf]").val(data.arquivo_nome);
                    $("#div_nota_"+data.faturamento).find("button").hide();
                }

                $("#div_nota_"+data.faturamento).find("img.anexo_loading").hide();
                $("#div_nota_"+data.faturamento).find("img.anexo_thumb").show();
            }
        });
    });



function imprimir(id1, id2, id3, id4){
	var nota_fiscal= document.getElementById(id1).value;
	var frete= document.getElementById(id2).value;
	var volume= document.getElementById(id3).value;
	var transp= document.getElementById(id4).value;
	var erro = '';
	var url = "";

	if (nota_fiscal =='' ) {
		erro = erro +' Nota fiscal está vazia.';
	}
	if (volume =='' ) {
		erro = ' É necessário preecher o volume.';
	}
	if (frete =='' ) {
		erro = erro +' É necessário preecher o frete.';
	}
	if (transp =='' ) {
		erro = erro +' É necessário selecionar a transportadora.';
	}

	if(erro ==''){

		url = "distrib/embarque_nota_fiscal_devolucao.php?nota_fiscal=" + nota_fiscal+"&qtde_volume=" +volume+"&valor_frete=" +frete+"&transportadora=" +transp;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=0, left=0");
	}else{
		alert(erro);
	}
}
</script>

<br><br>
<?

$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato ";
$res = pg_exec ($con,$sql);
$data    = pg_fetch_result ($res,0, 'data');
$periodo = pg_fetch_result ($res,0, 'periodo');
$nome    = pg_fetch_result ($res,0, 'nome');
$codigo  = pg_fetch_result ($res,0, 'codigo_posto');

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='-1' face='arial'>$codigo - $nome</font>";

?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<?php
	if($login_fabrica == 3) {

		$sql = "SELECT   extrato
					FROM  tbl_extrato
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   tbl_extrato.posto = $login_posto
					ORDER BY  tbl_extrato.extrato DESC LIMIT 1";
		$res = pg_query($con,$sql);

		if(pg_numrows($res) > 0){

			$ultimo_extrato = pg_result($res,0, 'extrato');

			$sqls = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						JOIN    tbl_peca             USING (peca)
						WHERE   tbl_faturamento.extrato_devolucao <= $ultimo_extrato
						AND     tbl_faturamento.fabrica = $login_fabrica
						AND     tbl_faturamento.posto             = $login_posto
						AND     tbl_faturamento.distribuidor IS NULL
						AND     (tbl_faturamento_item.devolucao_obrig IS TRUE or tbl_peca.produto_acabado       IS TRUE)
						AND     tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923')
						AND     tbl_faturamento.extrato_devolucao NOT IN (
									SELECT  distinct
								extrato_devolucao
								FROM tbl_faturamento
								WHERE posto IN (13996,4311)
								AND distribuidor=$login_posto
								AND fabrica=$login_fabrica
								AND extrato_devolucao < $ultimo_extrato
								AND extrato_devolucao <> $extrato
						)
						ORDER BY  tbl_faturamento.extrato_devolucao DESC limit 1";
			$ress = pg_query ($con,$sqls);
			$res_qtdes = pg_num_rows ($ress);

			if ($res_qtdes> 0){

				$extrato_aux = pg_result($ress,0, 'extrato_devolucao');

				$sqlD="SELECT extrato_devolucao
					FROM   tbl_faturamento
					WHERE  distribuidor = $login_posto
					AND    extrato_devolucao = $extrato_aux
					AND    fabrica = $login_fabrica;";
				$resD = pg_query($con,$sqlD);

				if(pg_num_rows($resD) == 0){
					$sqld = " SELECT tbl_extrato.extrato,to_char(data_geracao,'DD/MM/YYYY') as data_extrato, tbl_extrato_agrupado.aprovado
							FROM tbl_extrato
							LEFT JOIN tbl_extrato_agrupado USING (extrato)
							WHERE tbl_extrato.extrato = $extrato_aux
							AND   tbl_extrato.fabrica = $login_fabrica
							AND   tbl_extrato.posto   = $login_posto
							AND   tbl_extrato.data_geracao > '2010-01-01 00:00:00'
							ORDER BY tbl_extrato.extrato DESC limit 1;";
					$resd = pg_query($con,$sqld);
					if(pg_num_rows($resd) > 0){
						$extrato_dev = pg_fetch_result($resd,0,'extrato');
						$aprovado_dev = pg_fetch_result($resd, 0, 'aprovado');

						if (empty($aprovado_dev)) {
							echo "<td><a href='extratos_pendentes_britania.php'>Ver extratos pendentes</a></td>";
						} else {
							echo "<td align='center' width='33%'><a href='$PHP_SELF?mao=sim&extrato=$extrato'>Ver Mão-de-Obra</a></td>";
						}
					} else {
						echo "<td align='center' width='33%'><a href='$PHP_SELF?mao=sim&extrato=$extrato'>Ver Mão-de-Obra</a></td>";
					}

				} else {
					echo "<td align='center' width='33%'><a href='$PHP_SELF?mao=sim&extrato=$extrato'>Ver Mão-de-Obra</a></td>";
				}
			} else {
				echo "<td align='center' width='33%'><a href='$PHP_SELF?mao=sim&extrato=$extrato'>Ver Mão-de-Obra</a></td>";
			}
		}
	} else {
?>
<td align='center' width='33%'><a href='<?php echo $PHP_SELF ?>?mao=sim&extrato=<? echo $extrato ?>'>Ver Mão-de-Obra</a></td>
<?php } ?>
<td align='center' width='33%'><a href='extrato_posto.php'>Ver outro extrato</a></td>
</tr>
</table>

<div id='loading'></div>

<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg_erro ?></td>
	</tr>
</table>
<? } ?>

<center>

<br>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="10" class="menu_top" ><div align="center" style='font-size:16px'><b>ATENÇÃO</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
	As peças ou produtos não devolvidos neste extrato serão apresentadas na tela de <a href='<? echo "extrato_posto_devolucao_lgr.php?extrato=$extrato&pendentes=sim" ?>' target='_blank'>consulta de pendências</a>. Caso não sejam efetivadas as devoluções, os itens serão cobrados do posto autorizado.
<br><br>
<? //HD 15408 ?>
<b style='font-size:14px;font-weight:normal'>Emitir as NF de devolução nos mesmos valores e impostos, referenciando NF de origem, e postagem da NF de acordo com o cabeçalho de cada nota fiscal.</b>

<?php if($login_fabrica ==3 ){?>
<br><br>
<b style='font-size:14px;font-weight:normal'>
"O prazo para anexar o Comprovante de Envio do LGR (Correios ou Transportadora) é de 30 dias, o não cumprimento deste requisito poderá implicar em bloqueio dos próximos extratos, até regularização." </b>
<?php }?>
	</TD>
</TR>
<TR>
	<TD colspan='8' style='padding:10px;' align='center'>
<a href='<? echo "extrato_posto_devolucao_lgr.php?extrato=$extrato&pendentes=sim" ?>' target='_blank'>CONSULTA DE PENDÊNCIAS</a>
	</td>
<TR>
</table>

<?
if($login_fabrica ==3 ){
?>
	<br>
	<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">

	<TR>
		<TD colspan='8' class="table_line" style='padding:10px'>
	<b style='font-size:14px;font-weight:normal'>
	Prezados Postos.<br>
	A Britânia reduziu o número de peças de devolução obrigatória.<br>
	Favor realizar as devoluções de peças de acordo com o mostrado nas telas de acesso ao extrato financeiro.<br>
	Não haverá devolução de Nota Fiscal sem o envio físico das peças.<br>
	Atenção para o prazo de armazenamento de peças para vistoria, conforme informações do Telecontrol.

	<br>
	<a href='lgr_vistoria_itens.php'>VISTORIA</a>
	<br>
	Todos os produtos trocados devem ser enviados à Britânia no físico e Nota Fiscal, de acordo com o apresentado pelo Telecontrol.
	<br>

	</b>
		</TD>
	</TR>
	</table>
<br>

<?
}

	$array_nf_canceladas = array();
	$sql="SELECT	trim(nota_fiscal) as nota_fiscal,
					to_char(data_nf,'DD/MM/YYYY') as data_nf
			FROM tbl_lgr_cancelado
			WHERE	fabrica = $login_fabrica
			AND     posto   = $login_posto
			AND foi_cancelado IS TRUE";
	$res_nf_canceladas = pg_exec ($con,$sql);
	$qtde_notas_canceladas = pg_numrows ($res_nf_canceladas);
	if ($qtde_notas_canceladas>0){
		for($i=0;$i<$qtde_notas_canceladas;$i++) {
			$nf_cancelada = pg_fetch_result ($res_nf_canceladas,$i, 'nota_fiscal');
			$data_nf      = pg_fetch_result ($res_nf_canceladas,$i, 'data_nf');

			$sql2="SELECT faturamento
					FROM tbl_faturamento
					WHERE fabrica             = $login_fabrica
					AND distribuidor           = $login_posto
					AND extrato_devolucao      = $extrato
					AND posto                  = 13996
					AND LPAD(nota_fiscal::text,7,'0')  = LPAD(trim('$nf_cancelada')::text,7,'0')
					AND cancelada IS NOT NULL";
			$res_nota = pg_exec ($con,$sql2);
			$notasss = pg_numrows ($res_nota);
			if ($notasss>0){
				array_push($array_nf_canceladas,$nf_cancelada);
			}else{
				if ($extrato==156369){
					if ($nf_cancelada=="0027373" OR $nf_cancelada=="0027374"){
						continue;
					}
				}
				if ($extrato==165591){
					if ($nf_cancelada=="0027155"){
						continue;
					}
				}
				if ($login_posto==595 AND ($extrato == 165591 OR $extrato==156369)){
					array_push($array_nf_canceladas,"$nf_cancelada");
				}
				if ($login_posto==13951 AND $extrato==147564){
					array_push($array_nf_canceladas,"$nf_cancelada");
				}
				if ($login_posto==1537 AND $extrato==156705){
					array_push($array_nf_canceladas,"$nf_cancelada");
				}
			}
		}
	}
	if (count($array_nf_canceladas)>0){
		if (count($array_nf_canceladas)>1){
			echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>As notas:</b><br>".implode(",<br>",$array_nf_canceladas)." <br>foram <b>canceladas</b> e deverão ser preenchidas novamente! <br> <a href='extrato_posto_devolucao_lgr.php?extrato=$extrato&pendentes=sim'>Clique aqui</a> para o preenchimento das notas.</h3>";
		}else{
			echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>A nota</b> ".implode(", ",$array_nf_canceladas)." foi <b>cancelada</b> e deverá ser preenchida novamente! <br> <a href='extrato_posto_devolucao_lgr.php?extrato=$extrato&pendentes=sim'>Clique aqui</a> para o preenchimento da nota.</h3>";
		}
	}

?>
<br>
<?php if ($numero_linhas==0){ ?>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<td style='padding-left:280px;padding-right:60px'>
	<IMG SRC="imagens/setona.gif" WIDTH="31" HEIGHT="52" BORDER="0" ALT="" align='right'>
	Preencha esta coluna com as quantidades de peças que serão devolvidas
	</TD>
</TR>
</table>
<? } ?>


<?

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_fetch_result ($resX,0, 'estado');


$sql = "SELECT  faturamento,
		extrato_devolucao,
		nota_fiscal,
		TO_CHAR(emissao,'DD/MM/YYYY') AS emissao,
		distribuidor,
		cfop,
		natureza,
		posto,
		obs
	FROM tbl_faturamento
	WHERE posto in (13996,4311)
	AND distribuidor      = $login_posto
	AND fabrica           = $login_fabrica
	AND extrato_devolucao = $extrato
	AND cancelada IS NULL
	ORDER BY faturamento ASC";
$res = pg_exec ($con,$sql);
$qtde_for=pg_numrows ($res);

if ($qtde_for > 0 OR 1==1) {

	if ($login_fabrica == 3) {

        $tDocs    = new TDocs($con, $login_fabrica);
	    $temAnexo = $tDocs->getDocumentsByRef($extrato,'comprovantelgr')->attachListInfo;
	    $pos      = 1;

		if (count($temAnexo) > 0) {
	        foreach ($temAnexo as $k => $vAnexo) {
	            $temAnexo[$k]["posicao"] = $pos++;
	        }
	    }

	    echo '<div align="center" style="width:620px;font-size:16px;background:#596D9B;color:#ffffff;padding:10px;">
	    	<b>Anexo(s) Comprovante(s) LGR</b>
	    	</div>';

		for ($i=1; $i <= 5; $i++) {

			$imagemAnexo = "imagens/imagem_upload.png";
			$linkAnexo   = "#";

            if (count($temAnexo) > 0) {

                foreach ($temAnexo as $k => $vAnexo) {

                    if ($vAnexo["posicao"] != $i) {
                        continue;
                    }

                    $linkAnexo   = $vAnexo["link"];
                    $imagemAnexo = $vAnexo["link"];

                }
            }

			echo '<div id="div_anexo_'.$i.'" class="tac" style="display: inline-block;margin: 30px 30px 0px 0px; vertical-align: top">';

		        if ($linkAnexo != "#") {
		            echo '<a href="'.$linkAnexo.'" target="_blank" >';
		        }
		        echo '<img src="'.$imagemAnexo.'" class="anexo_thumb" style="width: 100px; height: 90px;" />';
		        if ($linkAnexo != "#") {
		        	echo '</a> <script>setupZoom();</script>';
		        }
	        	if ($linkAnexo == "#") {
	        	echo '<button type="button" class="btn_anexar_comprovante_lgr" name="anexar" rel="'.$i.'" >Anexar</button>';
	        	}
	        	echo '
	            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
	            <input type="hidden" rel="anexo" name="anexo['.$i.']" value="'.$anexo[$i].'" />
	        </div>';

		}

		for ($i=1; $i <= 5; $i++) {

			echo '
			 <form name="form_anexo" id="form_anexo" method="post" action="extrato_posto_devolucao_lgr_itens.php" enctype="multipart/form-data" style="display: none !important;" >
			    <input type="file" name="anexo_upload_'.$i.'" value="" />
			    <input type="hidden" name="ajax_anexo_upload" value="t" />
			    <input type="hidden" name="extrato_comprovante_lgr" value="'.$extrato.'" />
            	<input type="hidden" name="anexo_posicao" value="'.$i.'" />
			</form>';
		}
	}
?>
<?php
	$contador=0;
	for ($i=0; $i < $qtde_for; $i++) {

		$faturamento_nota    = trim (pg_fetch_result ($res,$i, 'faturamento'));
		$distribuidor        = trim (pg_fetch_result ($res,$i, 'distribuidor'));
		$cfop                = trim (pg_fetch_result ($res,$i, 'cfop'));
		$natureza            = trim (pg_fetch_result ($res,$i, 'natureza'));
		$posto               = trim (pg_fetch_result ($res,$i, 'posto'));
		$nota_fiscal         = trim (pg_fetch_result ($res,$i, 'nota_fiscal'));
		$data_nf             = trim (pg_fetch_result ($res,$i, 'emissao'));
		$extrato_devolucao	 = trim (pg_fetch_result ($res,$i, 'extrato_devolucao'));
		$obs                 = trim (pg_fetch_result ($res,$i, 'obs'));
		$distribuidor        = "";
		$produto_acabado     = "";

		$sql_topo = "SELECT
					CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
					tbl_faturamento_item.devolucao_obrig as devolucao_obrigatoria,
					tbl_peca.data_atualizacao
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				JOIN tbl_peca USING(peca)
				WHERE tbl_faturamento.posto           = $posto
				AND tbl_faturamento.distribuidor      = $login_posto
				AND tbl_faturamento.fabrica           = $login_fabrica
				AND tbl_faturamento.extrato_devolucao = $extrato_devolucao
				AND tbl_faturamento.faturamento       = $faturamento_nota
				AND tbl_faturamento_item.peca         <> 738213
				ORDER BY tbl_peca.data_atualizacao DESC
				LIMIT 1";
		$res_topo = pg_exec ($con,$sql_topo);

		if (pg_numrows ($res_topo)==0){
			$sql_topo = "SELECT
						CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
						tbl_faturamento_item.devolucao_obrig as devolucao_obrigatoria,
						tbl_peca.data_atualizacao
					FROM tbl_faturamento
					JOIN tbl_faturamento_item USING(faturamento)
					JOIN tbl_peca USING(peca)
					WHERE tbl_faturamento.posto           = $posto
					AND tbl_faturamento.distribuidor      = $login_posto
					AND tbl_faturamento.fabrica           = $login_fabrica
					AND tbl_faturamento.extrato_devolucao = $extrato_devolucao
					AND tbl_faturamento.faturamento       = $faturamento_nota
					ORDER BY tbl_peca.data_atualizacao DESC
					LIMIT 1";

			$res_topo = pg_exec ($con,$sql_topo);
		}

		if (pg_numrows ($res_topo)>0){
			$produto_acabado = pg_fetch_result ($res_topo,0, 'produto_acabado');
			$devolucao_obrigatoria = pg_fetch_result ($res_topo,0, 'devolucao_obrigatoria');

			$pecas_produtos = "PEÇAS";
			$devolucao = " RETORNO OBRIGATÓRIO ";

			if ($posto=='4311'){
				$posto_desc = "Devolução para a TELECONTROL - ";
			}else{
				$posto_desc="";
			}

			if ($devolucao_obrigatoria=='f') $devolucao = " NÃO RETORNÁVEIS ";
			if ($devolucao_obrigatoria=='f') $pecas_produtos = "$posto_desc PEÇAS";

			if ($produto_acabado == "TRUE"){
				$pecas_produtos = "$posto_desc PRODUTOS";
				$devolucao = " RETORNO OBRIGATÓRIO";
			}

			if ($obs=='Devolução de peças do posto para à Fábrica - Ressarcimento'){
				$devolucao = "RESSARCIMENTO FINANCEIRO";
			}

			if ($obs=='Devolução de produtos do posto para à Fábrica'){
				$nota_envio_consumidor = "SIM";
			}

			//HD43448
			if ($posto=='13996'){ #BRITANIA

				$sql_data_geracao_extrato = "SELECT data_geracao::date FROM tbl_extrato WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
				$res_data_geracao_extrato = pg_query($con, $sql_data_geracao_extrato);

				$data_geracao_extrato = pg_fetch_result($res_data_geracao_extrato, 0, "data_geracao");

				if(strtotime($data_geracao_extrato) >= strtotime("2017-03-01")){

					$razao    = "BRITANIA ELETRONICOS SA";
					$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
					$cidade   = "Joinville";
					$estado   = "SC";
					$cep      = "89239-270";
					$fone     = "(41) 2102-7700";
					$cnpj     = "07019308000128";
					$ie       = "254.861.660";

				}else{

					$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
					$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
					$cidade   = "Joinville";
					$estado   = "SC";
					$cep      = "89239270";
					$fone     = "(41) 2102-7700";
					$cnpj     = "76492701000742";
					$ie       = "254.861.652";

				}

			}
			if ($posto=='4311'){ #TELECONTROL
					$razao    = "TELECONTROL NETWORKING LTDA";
					$endereco = "AV. CARLOS ARTENCIO 420 ";
					$cidade   = "Marília";
					$estado   = "SP";
					$cep      = "17519255 ";
					$fone     = "(14) 3433-6588";
					$cnpj     = "04716427000141 ";
					$ie       = "438.200.748-116";
			}

			$cabecalho  = "";
			$cabecalho  = "<br><br>\n";
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

			$cabecalho .= "<tr align='left'  height='16'>\n";
			$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			$cabecalho .= "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
			$cabecalho .= "</td>\n";
			$cabecalho .= "</tr>\n";

			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Natureza <br> <b>$natureza</b> </td>\n";
			$cabecalho .= "<td>CFOP <br> <b>".substr($cfop,0,4)."</b> </td>\n";
			$cabecalho .= "<td>Emissao <br> <b>$data_nf</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
			$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
			$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
			$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
			$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
			$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$topo ="";
			$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
			$topo .=  "<thead>\n";
			if ($numero_linhas==5000 AND  $jah_digitado==0){
	//			$topo .=  "<tr align='left'>\n";
	//			$topo .=  "<td bgcolor='#E3E4E6' colspan='4' style='font-size:18px'>\n";
	//			$topo .=  "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
	//			$topo .=  "</td>\n";
	//			$topo .=  "</tr>\n";
			}
			$topo .=  "<tr align='center'>\n";
			$topo .=  "<td><b>Código</b></td>\n";
			$topo .=  "<td><b>Descrição</b></td>\n";
			$topo .=  "<td><b>Qtde.</b></td>\n";

			$topo .=  "<td><b>Preço</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			$topo .=  "<td><b>% IPI</b></td>\n";

			if ( $devolucao == "RESSARCIMENTO FINANCEIRO" OR $nota_envio_consumidor=='SIM'){
				$topo .=  "<td><b>OS</b></td>\n";
			}

			$topo .=  "</tr>\n";
			$topo .=  "</thead>\n";

			$sql = "SELECT
					tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
					tbl_faturamento_item.devolucao_obrig as devolucao_obrigatoria,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco,
					tbl_faturamento_item.os,
					SUM (tbl_faturamento_item.qtde) as qtde,
					SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco) as total,
					SUM (tbl_faturamento_item.base_icms) AS base_icms,
					SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
					SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
					SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi,
					SUM (tbl_faturamento_item.base_subs_trib) AS base_subs_trib,
					SUM (tbl_faturamento_item.valor_subs_trib) AS valor_subs_trib
					FROM tbl_faturamento
					JOIN tbl_faturamento_item USING (faturamento)
					JOIN tbl_peca             USING (peca)
					WHERE tbl_faturamento.fabrica = $login_fabrica
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND   tbl_faturamento.faturamento=$faturamento_nota
						AND   tbl_faturamento.posto=$posto
						AND   tbl_faturamento.distribuidor=$login_posto
					GROUP BY
						tbl_peca.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_faturamento_item.devolucao_obrig,
						tbl_peca.produto_acabado,
						tbl_peca.ipi,
						tbl_faturamento_item.aliq_icms,
						tbl_faturamento_item.aliq_ipi,
						tbl_faturamento_item.preco,
						tbl_faturamento_item.os
					ORDER BY tbl_peca.referencia";

			$resX = pg_exec ($con,$sql);

			$notas_fiscais=array();
			$qtde_peca=0;

			if (pg_numrows ($resX)==0) continue;

			echo $cabecalho;
			echo $topo;

			$total_base_icms  = 0;
			$total_valor_icms = 0;
			$total_base_ipi   = 0;
			$total_valor_ipi  = 0;
			$total_valor_subs_trib  = 0;
			$total_base_subs_trib  = 0;
			$total_nota       = 0;
			$aliq_final       = 0;

			for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

				$peca                = pg_fetch_result ($resX,$x, 'peca');
				$peca_referencia     = pg_fetch_result ($resX,$x, 'referencia');
				$peca_descricao      = pg_fetch_result ($resX,$x, 'descricao');
				$ipi                 = pg_fetch_result ($resX,$x, 'ipi');
				$peca_produto_acabado= pg_fetch_result ($resX,$x, 'produto_acabado');
				$peca_devolucao_obrigatoria = pg_fetch_result ($resX,$x, 'devolucao_obrigatoria');
				$aliq_icms           = pg_fetch_result ($resX,$x, 'aliq_icms');
				$aliq_ipi            = pg_fetch_result ($resX,$x, 'aliq_ipi');
				$peca_preco          = pg_fetch_result ($resX,$x, 'preco');
				$os                  = pg_fetch_result ($resX,$x, 'os');

				$base_icms           = pg_fetch_result ($resX,$x, 'base_icms');
				$valor_icms          = pg_fetch_result ($resX,$x, 'valor_icms');
				$base_ipi            = pg_fetch_result ($resX,$x, 'base_ipi');
				$valor_ipi           = pg_fetch_result ($resX,$x, 'valor_ipi');

				$base_subs_trib      = pg_fetch_result ($resX,$x,'base_subs_trib');
				$valor_subs_trib     = pg_fetch_result ($resX,$x,'valor_subs_trib');

				if(empty($base_subs_trib)) {
					$base_subs_trib = 0;
				}

				if(empty($valor_subs_trib)) {
					$valor_subs_trib = 0;
				}
				$total               = pg_fetch_result ($resX,$x, 'total');
				$qtde                = pg_fetch_result ($resX,$x, 'qtde');

				$sua_os="";
				if (strlen($os)>0 AND ($devolucao == "RESSARCIMENTO FINANCEIRO"  OR $nota_envio_consumidor=='SIM')) {
					$sql_os = "SELECT sua_os
								FROM tbl_os
								WHERE os  = $os
								AND posto = $login_posto ";
					$resOS = pg_exec ($con,$sql_os);
					if (pg_numrows ($resOS)>0){
						$sua_os = pg_fetch_result ($resOS,0, 'sua_os');
					}
				}

				$sql_nf = "SELECT tbl_faturamento_item.nota_fiscal_origem
						FROM tbl_faturamento_item
						JOIN tbl_faturamento      USING (faturamento)
						WHERE tbl_faturamento.fabrica = $login_fabrica
						AND   tbl_faturamento.distribuidor   = $login_posto
						AND   tbl_faturamento.posto   = $posto
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND   tbl_faturamento.faturamento=$faturamento_nota
						ORDER BY tbl_faturamento.nota_fiscal";
				$resNF = pg_exec ($con,$sql_nf);
				for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
					array_push($notas_fiscais,pg_fetch_result ($resNF,$y, 'nota_fiscal_origem'));
				}
				$notas_fiscais = array_unique($notas_fiscais);
				asort($notas_fiscais);

				if ($qtde==0) {
					$peca_preco       =  $peca_preco;
				} else {
					$peca_preco       =  $total / $qtde;
				}

				$total_item  = $peca_preco * $qtde;

				if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

				if ($aliq_icms==0){
					$base_icms=0;
					$valor_icms=0;
				} else {
					$base_icms  = $total_item;
					$valor_icms = $total_item * $aliq_icms / 100;
				}

				if (strlen($aliq_ipi)==0) $aliq_ipi=0;

				if ($aliq_ipi==0) 	{
					$base_ipi=0;
					$valor_ipi=0;
				} else {
					$base_ipi=$total_item;
					$valor_ipi = $total_item*$aliq_ipi/100;
				}

				$total_base_icms  += $base_icms;
				$total_valor_icms += $valor_icms;
				$total_base_ipi   += $base_ipi;
				$total_valor_ipi  += $valor_ipi;
				$total_base_subs_trib  += $base_subs_trib;
				$total_valor_subs_trib  += $valor_subs_trib;
				$total_nota       += $total_item;

				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
				echo "<td align='left'>";
				echo "$peca_referencia";
				echo "<input type='hidden' name='id_item_LGR_$item_nota-$numero_nota' value='$extrato_lgr'>\n";
				echo "<input type='hidden' name='id_item_peca_$item_nota-$numero_nota' value='$peca'>\n";
				echo "<input type='hidden' name='id_item_preco_$item_nota-$numero_nota' value='$preco'>\n";
				echo "<input type='hidden' name='id_item_qtde_$item_nota-$numero_nota' value='$qtde'>\n";
				echo "<input type='hidden' name='id_item_icms_$item_nota-$numero_nota' value='$aliq_icms'>\n";
				echo "<input type='hidden' name='id_item_ipi_$item_nota-$numero_nota' value='$aliq_ipi'>\n";
				echo "<input type='hidden' name='id_item_total_$item_nota-$numero_nota' value='$total_item'>\n";
				echo "</td>\n";
				echo "<td align='left'>$peca_descricao</td>\n";

				echo "<td align='center'>$qtde</td>\n";
				echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
				echo "<td align='right'>$aliq_icms</td>\n";
				echo "<td align='right'>$aliq_ipi</td>\n";
				if (strlen($sua_os)>0){
					echo "<td align='center'>$sua_os</td>\n";
				}

				echo "</tr>\n";
				flush();
			}
			if (count($notas_fiscais)>0){
				echo "<tfoot>";
				echo "<tr>";
				echo "<td colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
				echo "</tr>";
				echo "</tfoot>";
			}

			echo "</table>\n";


			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
			echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
			echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
			echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
			echo "<td>Base Subst. Trib. <br> <b> " . number_format ($total_base_subs_trib,2,",",".") . " </b> </td>";
			echo "<td>Valor Subst. Trib.<br> <b> " . number_format ($total_valor_subs_trib,2,",",".") . " </b> </td>";
			echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi+$total_valor_subs_trib,2,",",".") . " </b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

			if ( $devolucao == "RESSARCIMENTO FINANCEIRO" OR $nota_envio_consumidor=='SIM'){
				echo "<tr>\n";
				echo "<td colspan ='4'><br><div style='margin:0 auto; text-align:center;color:#D90000;font-weight:bold'>* Para o preenchimento da Nota Fiscal de Simples Remessa, utilizar o mesmo valor da Nota Fiscal de compra do consumidor</div><br></td>\n";
				echo "</tr>";
			}

			echo "<tr>\n";
			echo (empty($nota_fiscal))
                ? "<td colspan ='4'><h1><center>Cadastrar nota: <input type='text' name='nota_fiscal_$faturamento_nota' />"
                : "<td colspan ='4'><h1><center><a href='". $NFDevolucao($extrato, $faturamento_nota) . "' target='_blank'> Nota de Devolução $nota_fiscal</center></a></h1></td>\n";
			echo "</tr>";
            if ($login_fabrica == 3 && empty($NFDevolucao($extrato, $faturamento_nota))) {
                echo "<tr><td style='margin:auto;'>";
                echo '<div id="div_nota_'.$faturamento_nota.'" class="tac" style="display: block;margin: 30px 30px 0px 0px; vertical-align: top">';

		        echo '<img src="'.$imagemAnexo.'" class="anexo_thumb" style="width: 100px; height: 90px;" />';
                echo '</a> <script>setupZoom();</script>';
	        	if ($linkAnexo == "#") {
	        	echo '<button type="button" class="btn_anexar_nota" name="anexar_nota" rel="'.$faturamento_nota.'" >Anexar</button>';
	        	}
	        	echo '
	            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
	            <input type="hidden" rel="anexo_nf" name="anexo['.$faturamento_nota.']" value="'.$anexo[$faturamento_nota].'" />
                </div>';

?>
<form name="form_nf" id="form_nf" method="post" action="extrato_posto_devolucao_lgr_itens.php" enctype="multipart/form-data" style="display: none !important;" >
    <input type="file" name="anexo_nf_<?=$faturamento_nota?>" value="" />
    <input type="hidden" name="ajax_nf" value="t" />
    <input type="hidden" name="nova_nota_fiscal" value="" />
    <input type="hidden" name="faturamento" value="<?=$faturamento_nota?>" />
</form>
<?php
                echo "</td></tr>";
            }
			/*APENAS PARA O POSTO TELECONTROL (4311 - DISTRIB TELECONTROL)*/

			if($login_posto == 4311){

				$sql_transp = "SELECT qtde_volume,
									valor_frete,
									transportadora,
									conferencia,
									total_nota
							FROM tbl_faturamento
							WHERE faturamento = $faturamento_nota";
						$resTransp = pg_exec ($con,$sql_transp);
						//echo "sql: ". $sql_transp;
						if(pg_numrows($resTransp)>0){
							$qtde_volume    = trim (pg_fetch_result ($resTransp,0, 'qtde_volume'));
							$valor_frete    = trim (pg_fetch_result ($resTransp,0, 'valor_frete'));
							$transportadora = trim (pg_fetch_result ($resTransp,0, 'transportadora'));
							$conferencia    = trim (pg_fetch_result ($resTransp,0, 'conferencia'));
							$total_nota     = trim (pg_fetch_result ($resTransp,0, 'total_nota'));
						}



				echo "<form name='frm_imprimir_$i' method='post'  action='#'>";
				echo "<tr>\n";
				echo "<td>";
				echo "Volumes: <input type='text' size='10' name='qtde_volume_$i' id='qtde_volume_$i' value='$qtde_volume'>
					<input type='hidden' name='nota_fiscal_$i' id='nota_fiscal_$i' value='$nota_fiscal'>
				<br>";
				echo "</td>";

				echo "<td>";
				echo "Frete: <input type='text' size='5' name='valor_frete_$i' id='valor_frete_$i' value='$valor_frete'>";
				echo "</td>";

				echo "<td>Transportadora: ";
				echo "<select name='transportadora_$i' id='transportadora_$i' size='1'>";
		#		echo "<option value='1055' SELECTED>VARIG-LOG</option>";
				$selecionado ="";
				if($transportadora == "1058") $selecionado = "SELECTED";
				echo "<option value='1058' $selecionado >PAC</option>";
				$selecionado ="";
				if($transportadora == "1061") $selecionado = "SELECTED";
				echo "<option value='1061' $selecionado >PAC (TC)</option>";
				$selecionado ="";
				if($transportadora == "1062") $selecionado = "SELECTED";
				echo "<option value='1062' $selecionado >PAC (AK)</option>";
				$selecionado ="";
				if($transportadora == "1056") $selecionado = "SELECTED";
				echo "<option value='1056' $selecionado >SEDEX</option>";
				$selecionado ="";
				if($transportadora == "1060") $selecionado = "SELECTED";
				echo "<option value='1060' $selecionado >E-SEDEX</option>";
				$selecionado ="";
				if($transportadora == "1057") $selecionado = "SELECTED";
				echo "<option value='1057' $selecionado >PROPRIO</option>";
				$selecionado ="";
				if($transportadora == "497") $selecionado = "SELECTED";
				echo "<option value='497' $selecionado >BRASPRESS</option>";
				$selecionado ="";
				if($transportadora == "703") $selecionado = "SELECTED";
				echo "<option value='703' $selecionado >MERCURIO</option>";
				echo "</select>";
				echo "</td>\n";
				echo "<td>";

				if(strlen($total_nota)==0 OR $total_nota==0 OR $total_nota=="0,00"){
					echo "<a href='distrib/nf_saida.php?faturamento=$faturamento_nota&alterar_faturamento=sim'>Atualizar Nota</a>";
				}else{
					echo "<input type='BUTTON' size='10' name='imprimir_$i' value='Imprimir' onclick='javascript:imprimir(\"nota_fiscal_$i\",\"valor_frete_$i\",\"qtde_volume_$i\",\"transportadora_$i\")' ><br>";
				}

				echo "</td>";
				echo "</tr>";
				echo "</form>";
			}
			echo "</table>";

			$total_base_icms  = 0;
			$total_valor_icms = 0;
			$total_base_ipi   = 0;
			$total_valor_ipi  = 0;
			$total_nota       = 0;
		}

	}

################################################
## PEÇAS RETORNAVEIS DA TELECONTROL
################################################

if ($posto<>"4311" AND 1==2){
			$sql = "SELECT  tbl_faturamento.faturamento,
							tbl_peca.peca,
							tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_peca.ipi,
							tbl_faturamento_item.aliq_icms,
							tbl_faturamento_item.aliq_ipi,
							SUM (tbl_faturamento_item.qtde) AS qtde,
							SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco ) AS total_item,
							SUM (tbl_faturamento_item.base_icms) AS base_icms,
							SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
							SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi,
							SUM (tbl_faturamento_item.base_ipi) AS base_ipi
					FROM tbl_peca
					JOIN tbl_faturamento_item USING (peca)
					JOIN tbl_faturamento      USING (faturamento)
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.posto   = $login_posto
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND   tbl_faturamento.distribuidor=4311
					AND   tbl_faturamento_item.aliq_icms > 0
					AND   tbl_faturamento.emissao > '2005-10-01'
					GROUP BY tbl_faturamento.faturamento, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento_item.aliq_ipi
					ORDER BY tbl_peca.referencia ";

			$resX = pg_exec ($con,$sql);
			$total_base_icms  = 0;
			$total_valor_icms = 0;
			$total_base_ipi   = 0;
			$total_valor_ipi  = 0;
			$total_nota       = 0;
			$aliq_final       = 0;

			$distribuidor=4311;
			$notas_fiscais=0;

			if ( pg_numrows ($resX)>0){

				if (strlen ($distribuidor) > 0) {
					$sql_2  = "SELECT * FROM tbl_posto WHERE posto = $distribuidor";
					$resY = pg_exec ($con,$sql_2);

					$estado   = pg_fetch_result ($resY,0, 'estado');
					$razao    = pg_fetch_result ($resY,0, 'nome');
					$endereco = trim (pg_fetch_result ($resY,0, 'endereco')) . " " . trim (pg_fetch_result ($resY,0, 'numero'));
					$cidade   = pg_fetch_result ($resY,0, 'cidade');
					$estado   = pg_fetch_result ($resY,0, 'estado');
					$cep      = pg_fetch_result ($resY,0, 'cep');
					$fone     = pg_fetch_result ($resY,0, 'fone');
					$cnpj     = pg_fetch_result ($resY,0, 'cnpj');
					$ie       = pg_fetch_result ($resY,0, 'ie');

					$condicao_1 = " tbl_faturamento.distribuidor = $distribuidor ";
					$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
				}

				$cabecalho  = "<br><br>\n";
				$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

				$cabecalho .= "<tr align='left'  height='16'>\n";
				$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
				$cabecalho .= "<b>DEVOLUÇÃO TELECONTROL&nbsp;</b><br>\n";
				$cabecalho .= "</td>\n";
				$cabecalho .= "</tr>\n";

				$cabecalho .= "<tr>\n";
				$cabecalho .= "<td>Natureza <br> <b>Devolução de Garantia</b> </td>\n";
				$cabecalho .= "<td>CFOP <br> <b>".substr($cfop,0,4)."</b> </td>\n";
				$cabecalho .= "<td>Emissao <br> <b>$data</b> </td>\n";
				$cabecalho .= "</tr>\n";
				$cabecalho .= "</table>\n";

				$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
				$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
				$cabecalho .= "<tr>\n";
				$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
				$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
				$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
				$cabecalho .= "</tr>\n";
				$cabecalho .= "</table>\n";

				$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
				$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
				$cabecalho .= "<tr>\n";
				$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
				$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
				$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
				$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
				$cabecalho .= "</tr>\n";
				$cabecalho .= "</table>\n";

				$topo ="";
				$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
				$topo .=  "<thead>\n";

				$topo .=  "<tr align='center'>\n";
				$topo .=  "<td><b>Código</b></td>\n";
				$topo .=  "<td><b>Descrição</b></td>\n";
				$topo .=  "<td><b>Qtde.</b></td>\n";
				$topo .=  "<td><b>Preço</b></td>\n";
				$topo .=  "<td><b>Total</b></td>\n";
				$topo .=  "<td><b>% ICMS</b></td>\n";
				$topo .=  "<td><b>% IPI</b></td>\n";
				$topo .=  "</tr>\n";
				$topo .=  "</thead>\n";

				echo $cabecalho;
				echo $topo;

				for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
					$peca        = pg_fetch_result ($resX,$x, 'peca');
					$qtde        = pg_fetch_result ($resX,$x, 'qtde');
					$total_item  = pg_fetch_result ($resX,$x, 'total_item');
					$base_icms   = pg_fetch_result ($resX,$x, 'base_icms');
					$valor_icms  = pg_fetch_result ($resX,$x, 'valor_icms');
					$aliq_icms   = pg_fetch_result ($resX,$x, 'aliq_icms');
					$base_ipi   = pg_fetch_result ($resX,$x, 'base_ipi');
					$aliq_ipi   = pg_fetch_result ($resX,$x, 'aliq_ipi');
					$valor_ipi   = pg_fetch_result ($resX,$x, 'valor_ipi');
					$ipi = pg_fetch_result ($resX,$x, 'ipi');
					$preco       = round ($total_item / $qtde, '2');
					$total_item  = $preco * $qtde;
					$faturamento = pg_fetch_result ($resX,$x, 'faturamento');

					if (strlen ($base_icms)  == 0) $base_icms = $total_item ;
					if (strlen ($valor_icms) == 0) $valor_icms = round ($total_item * $aliq_icms / 100,2);


					if (strlen($aliq_ipi)==0) $aliq_ipi=0;
					if ($aliq_ipi==0) 	{
						$base_ipi=0;
						$valor_ipi=0;
					}
					else {
						$base_ipi=$total_item;
						$valor_ipi = $total_item*$aliq_ipi/100;
					}

					if ($base_icms > $total_item) $base_icms = $total_item;
					if ($aliq_final == 0) $aliq_final = $aliq_icms;
					if ($aliq_final <> $aliq_icms) $aliq_final = -1;

					echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
					echo "<td align='left'>" . pg_fetch_result ($resX,$x, 'referencia') . "</td>";
					echo "<td align='left'>" . pg_fetch_result ($resX,$x, 'descricao') . "</td>";
					echo "<td align='right'>" . pg_fetch_result ($resX,$x, 'qtde') . "</td>";
					echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>";
					echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>";
					echo "<td align='right'>" . $aliq_icms . "</td>";
					echo "<td align='right'>" . $aliq_ipi. "</td>";
					echo "</tr>";

					$total_base_icms  += $base_icms;
					$total_valor_icms += $valor_icms;
					$total_base_ipi  += $base_ipi;
					$total_valor_ipi += $valor_ipi;
					$total_nota       += $total_item;
				}

				$sql_nf = "SELECT DISTINCT tbl_faturamento.nota_fiscal
						FROM tbl_faturamento_item
						JOIN tbl_faturamento      USING (faturamento)
						JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
						WHERE tbl_faturamento.fabrica = $login_fabrica
						AND   tbl_faturamento.posto   = $login_posto
						AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND   tbl_faturamento.distribuidor=4311
						AND   tbl_faturamento_item.aliq_icms > 0
						AND   tbl_faturamento.emissao > '2005-10-01'
						ORDER BY tbl_faturamento.nota_fiscal ";
				$resZ = pg_exec ($con,$sql_nf);
				$notas_fiscais    = array();
				for ($x = 0 ; $x < pg_numrows ($resZ) ; $x++) {
					array_push($notas_fiscais,pg_fetch_result ($resZ,$x, 'nota_fiscal'));
				}
				if (count($notas_fiscais)>0){
					echo "<tfoot>";
					echo "<tr>";
					echo "<td colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
					echo "</tr>";
					echo "</tfoot>";
				}
				//$total_valor_icms = $total_base_icms * $aliq_final / 100;
				$total_geral=$total_nota+$total_valor_ipi;
				echo "</table>\n";
				echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
				echo "<tr>\n";
				echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>\n";
				echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";
				echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>\n";
				echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>\n";
				echo "<td>Total da Nota <br> <b> " . number_format ($total_geral,2,",",".") . " </b> </td>\n";
				echo "</tr>\n";

				echo "</table>\n";
			}
}
########################################################
### PEÇAS COM RESSARCIMENTO
########################################################

	$sql = "SELECT  tbl_os.os                                                         ,
			tbl_os.sua_os                                                     ,
			TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_ressarcimento,
			tbl_produto.referencia                       AS produto_referencia,
			tbl_produto.descricao                        AS produto_descricao ,
			tbl_admin.login
		FROM tbl_os
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING(os_produto)
		JOIN tbl_os_extra   USING(os)
		LEFT JOIN tbl_admin      ON tbl_os.troca_garantia_admin   = tbl_admin.admin
		LEFT JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_os_extra. extrato = $extrato
		AND  tbl_os.ressarcimento  IS TRUE
		AND  tbl_os.troca_garantia IS TRUE";

	//Sono em 25/10/2007 - não encontrei motivo para dar JOIn em os_produto e os_item (estava causando duplicidade na tela ex: extrato 197739)
	$sql = "SELECT  tbl_os.os                                                         ,
			tbl_os.sua_os                                                     ,
			TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_ressarcimento,
			tbl_produto.referencia                       AS produto_referencia,
			tbl_produto.descricao                        AS produto_descricao ,
			tbl_admin.login
		FROM tbl_os
		JOIN tbl_os_extra   USING(os)
		LEFT JOIN tbl_admin      ON tbl_os.troca_garantia_admin   = tbl_admin.admin
		LEFT JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_os_extra. extrato = $extrato
		AND  tbl_os.ressarcimento  IS TRUE
		AND  tbl_os.troca_garantia IS TRUE";

	if (1==2){ # não preicsa pois as notas de ressarcimento sao inseridas no faturamento
		$resX = pg_exec ($con,$sql);
		if(pg_numrows($resX)>0 AND strlen($nota_fiscal)>0 ){

			echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";

			echo "<tr align='left'  height='16'>\n";
			echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			echo "<b>&nbsp;<b>PEÇAS COM RESSARCIMENTO - DEVOLUÇÃO OBRIGATÓRIA </b><br>\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>";
			echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
			echo "<td>CFOP <br> <b>".substr($cfop,0,4)."</b> </td>";
			echo "<td>Emissao <br> <b>$data</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Razão Social <br> <b>$razao</b> </td>";
			echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
			echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
			echo "</tr>";
			echo "</table>";


			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Endereço <br> <b>$endereco </b> </td>";
			echo "<td>Cidade <br> <b>$cidade</b> </td>";
			echo "<td>Estado <br> <b>$estado</b> </td>";
			echo "<td>CEP <br> <b>$cep</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr align='center'>";
			echo "<td><b>Código</b></td>";
			echo "<td><b>Descrição</b></td>";
			echo "<td><b>Ressarcimento</b></td>";
			echo "<td><b>Responsavel</b></td>";
			echo "<td><b>OS</b></td>";
			echo "</tr>";

			for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

				$sua_os             = pg_fetch_result ($resX,$x, 'sua_os');
				$produto_referencia = pg_fetch_result ($resX,$x, 'produto_referencia');
				$produto_descricao  = pg_fetch_result ($resX,$x, 'produto_descricao');
				$data_ressarcimento = pg_fetch_result ($resX,$x, 'data_ressarcimento');
				$quem_trocou        = pg_fetch_result ($resX,$x, 'login');

				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
				echo "<td align='left'>$produto_referencia</td>";
				echo "<td align='left'>$produto_descricao</td>";
				echo "<td align='left'>$data_ressarcimento</td>";
				echo "<td align='right'>$quem_trocou</td>";
				echo "<td align='right'>$sua_os</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}

	if ($login_fabrica == 3) {
		echo '<br /><a href="helpdesk_cadastrar.php" class="btn"><b>Agendar Coleta</b></a>';
	}
}else{

	echo "<h1><center> Extrato de Mão-de-obra Liberado. Recarregue a página. </center></h1>";
	$sql =	"UPDATE tbl_extrato_extra SET
				nota_fiscal_devolucao              = '000000' ,
				valor_total_devolucao              = 0        ,
				base_icms_devolucao                = 0        ,
				valor_icms_devolucao               = 0        ,
				nota_fiscal_devolucao_distribuidor = '000000' ,
				valor_total_devolucao_distribuidor = 0        ,
				base_icms_devolucao_distribuidor   = 0        ,
				valor_icms_devolucao_distribuidor  = 0
			WHERE extrato = $extrato;";
	//$res = pg_exec ($con,$sql);

}

?>

<p><p>

<? include "rodape.php"; ?>
