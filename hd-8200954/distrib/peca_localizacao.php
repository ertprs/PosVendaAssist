<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../admin/funcoes.php';
include_once '../class/AuditorLog.php';

$btn_acao = trim(getPost('btn_acao'));

if ($btn_acao == 'ajax') {
	$peca_referencia = mb_strtoupper(getPost('referencia'));
	$xreferencia     = null;
	$filtroPeca      = array('PC.referencia' => $peca_referencia);

	if (strlen ($peca_referencia) < 6) {
		$xreferencia = "000000" . $peca_referencia;
		$xreferencia = substr ($xreferencia,strlen ($xreferencia)-6);
		$filtroPeca  = array(
			'refFilter' => array(
				'PC.referencia'  => $peca_referencia,
				'@PC.referencia' => $xreferencia
			)
		);
	}
	$filtroPeca['LOC.posto'] = $login_posto;

	$sql = sql_cmd(
		array(
			'tbl_peca AS PC',
			'LEFT JOIN tbl_posto_estoque_localizacao AS LOC USING(peca)'
		),
		'LOC.localizacao, PC.peca',
		$filtroPeca
	);
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) == 0) {
		die ("<em style='color:darkred'>Pe&ccedil;a '$peca_referencia' n&atilde;o encontrada!</em>");
	}
	list($loc, $peca) = pg_fetch_row($res, 0);
	if (strlen($loc)) {
		die (pg_fetch_result($res, 0, 0));
	}
	die ("<em>sem localiza&ccedil;&atilde;o</em>");
}

  if ($_POST['ajax_fabrica_peca'] == true) {  
      $referenciaProduto = $_POST["referenciaProduto"];

      $sql = "SELECT tbl_fabrica.fabrica,
              		 tbl_fabrica.nome
              FROM   tbl_peca
              JOIN   tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
              AND    tbl_fabrica.parametros_adicionais::jsonb->>'telecontrol_distrib' is not null
              AND    ativo_fabrica
              WHERE  UPPER(referencia) = UPPER('{$referenciaProduto}')";
 
      $res = pg_query($con, $sql);

 	while ($dados = pg_fetch_object($res)) {
    	   $arrayFabrica[$dados->fabrica] = $dados->nome;
  	}
     	
 	$jsonRetorno = json_encode($arrayFabrica);

 	exit($jsonRetorno);
}

if (strlen ($btn_acao) > 0) {

	#-------------- Confirma conferência atual ----------#
	$qtde_item = $_POST['qtde_item'];

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$referencia  = mb_strtoupper(trim($_POST['referencia_' . $i]));
		$localizacao = mb_strtoupper(trim($_POST['localizacao_' . $i]));
		$fabrica_combo = mb_strtoupper(trim($_POST['fabrica_' . $i]));

		if (strlen ($referencia) < 6) {
			$xreferencia = "000000" . $referencia;
			$xreferencia = substr ($xreferencia,strlen ($xreferencia)-6);
		}

		if (!empty($referencia) and !empty($localizacao)) {

			if(!valida_mascara_localizacao($localizacao)){
				$nao_gravou[$referencia] = "$referencia - Erro de Localização inválida. ";			
				continue;
			}

			if (strlen ($localizacao) == 0) {
				$localizacao = "NULL";
			}else{
				$localizacao = "'" . $localizacao . "'";
			}
			$auditor = new AuditorLog;
			//Auditor-Anterior
			$cond_fab = (!empty($fabrica_combo)) ? " AND tbl_peca.fabrica = $fabrica_combo" : "";

            $sqlA = "SELECT posto, fabrica, peca, referencia, descricao,
					        nome AS fabrica, LOC.localizacao, PEQ.qtde
					   FROM tbl_posto_estoque_localizacao AS LOC
					   JOIN tbl_posto_estoque AS PEQ USING(posto, peca)
					   JOIN tbl_peca    USING (peca)
					   JOIN tbl_fabrica USING (fabrica)
					  WHERE LOC.posto            = $login_posto
						AND (tbl_peca.referencia = '$referencia'
						 OR tbl_peca.referencia = '$xreferencia')
						AND tbl_peca.ativo 
						$cond_fab 
						LIMIT 1";
						
			$auditor->retornaDadosSelect($sqlA);

			$res = pg_query($con, $sqlA);

            $pecaID = pg_fetch_result($res, 0,  'peca');
			$locAnt = pg_fetch_result($res, 0, 'localizacao');
			$action = $locAnt ? 'update' : 'insert';

			pg_begin();
			// $action = $action == 'insert' and !strlen($localizacao) ?
			//     'delete' : 'insert';

			

			$sql_fab_peca = "SELECT fabrica, peca FROM tbl_peca WHERE peca = $pecaID";
			$res_fab_peca = pg_query($con, $sql_fab_peca);
			$fab_peca = pg_fetch_result($res_fab_peca, 0, 'fabrica');
			$ref_peca = pg_fetch_result($res_fab_peca, 0, 'referencia');

			if (in_array($fab_peca, [11,172])) {
				include_once '../funcoes.php';

				atualiza_localizacao_lenoxx($pecaID, $localizacao, $login_posto);
			} else {
				$sql = "UPDATE tbl_posto_estoque_localizacao
						   SET localizacao = $localizacao,
						       posto       = $login_posto
						  FROM tbl_peca
						  JOIN tbl_fabrica USING(fabrica)
						 WHERE tbl_posto_estoque_localizacao.peca = tbl_peca.peca
						   AND tbl_fabrica.parametros_adicionais ~* 'telecontrol_distrib'
						   AND (tbl_peca.referencia = '$referencia'
						     OR tbl_peca.referencia = '$xreferencia')
						   AND tbl_peca.ativo
						   AND tbl_peca.fabrica = $fabrica_combo";
				$res = pg_query($con, $sql);
				 
				if (pg_last_error($con)) {
					$nao_gravou[$referencia] = pg_last_error($con);
					pg_rollBack();
					continue;
				}
			}

			pg_commit();

			//Auditor-Depois
			$auditor->retornaDadosSelect();
			$auditor->enviarLog('update', 'tbl_posto_estoque_localizacao', "$login_posto*$pecaID");

			if ($auditor->OK == false)
				$msg_erro_log = "<h3> Erro ao gravar o Log de Alteração</h3>";
		}
	}
	if (count($nao_gravou)) {

			foreach ($nao_gravou as $ref => $erro) {
				$msg_erro .= "<br />\n".$erro;
			}
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
<title>Mudar Localização de Peça</title>
<link type="text/css" rel="stylesheet" href="css./css.css">
<script type="text/javascript" src="../js/jquery-1.8.3.min.js"></script>

	<script type="text/javascript">
		function alteraMaiusculo(valor){
			var novoTexto = valor.value.toUpperCase();
			valor.value = novoTexto;
		}		

		$(document).ready(function (){
			$(".localizacao").attr('pattern',"[A-Z][A-Z]-[A-Z]\\\d\\\d-[A-Z]\\\d\\\d|[A-Z]\\\d\\\d-[A-Z]\\\d\\\d|[A-Z]{3}-[A-Z]\\\d\\\d\\\d|[A-Z]\\\d\\\d\\\d-[A-Z]\\\d\\\d");

			//HD-6835584
		    $("input[name^=referencia_]").blur(function(){
                 
		    	var referencia = $(this).val();
		    	var that = $(this);
		    	if (referencia != "") {

		    		$.ajax({
			            url : window.location,
			            type: "POST",
			            data: {
			            	ajax_fabrica_peca: true, 
			            	referenciaProduto: referencia
			            },
			            dataType: "json",
			            complete: function(data){ 

			          
			            	data = $.parseJSON(data.responseText);

		                    $(that).closest("tr").find("#fabricas").html("");

		                    $.each(data, function(key, value) {

	                            var option = $("<option></option>", { value: key, text: value});

	                            $(that).closest("tr").find("#fabricas").append(option);
		                    });

			            }
			        });

		    	}

            });
	

		});
	</script>

</head>

<body>

<? include 'menu.php' ?>

<center><h1>Mudar Localização de Peça</h1></center>
<div> SÓ É ACEITO CONFORME SEGUINTES MÁSCARAS:
1.  LL-LNN-LNN   2. LNN-LNN    3. LLL-LNNNN  4. LNNN-LNN
</div>
<?php
if (count($_POST)) {
	if (empty($msg_erro)) {
	echo "<center><h2>Mudanças processadas corretamente</h2>$msg_erro_log</center>";
	} else {
		echo "<center><h2>Erro ao Gravar: $msg_erro </h2>$msg_erro_log</center>";
	}
}
?>
<p>
<form method='POST' name='frm_localizacao' id="frm_localizacao">
<table width="800" align="center" style="table-layout: fixed">
	<thead align="center" style="background: #F93;color:white; font-weight: bold">
		<tr>
			<th rowspan="2" width="250">Peça</th>
			<th colspan="3" width="600">Localização</th>

		</tr>
		<tr>
			<th width="250">Fábrica</th>
			<th width="250">Atual</th>
			<th width="250">Nova</th>

		</tr>
	</thead>
	<tbody>
<?
pg_prepare(
	$con, 'LocPeca',
	"SELECT tbl_posto_estoque_localizacao.localizacao " .
	"FROM tbl_posto_estoque_localizacao " .
	"RIGHT JOIN tbl_peca USING (peca) " .
	"WHERE tbl_posto_estoque_localizacao.posto = $1" .
	"AND tbl_peca.referencia = $2"
);

$cor = "#FFFBF0";

for ($i = 0 ; $i < 20 ; $i++) {
	$localizacao = '';
	$referencia  = ''; // limpa se não deu erro

	$locPeca     = '';
	$cor = ($i % 2 == 0) ? "#FFEECC" : "#FFFBF0";

	if (array_key_exists($_POST["referencia_$i"], $nao_gravou)) {
		$referencia  = $_POST['referencia_'  . $i];
		$localizacao = $_POST['localizacao_' . $i];
		$cor = '#faa';
	}

	if (strlen ($referencia) > 0) {
		$res = pg_execute($con, 'LocPeca', array($login_posto, $referencia));
		$locPeca = @pg_fetch_result($res,0,0); // Localização atual

		if (pg_num_rows($res) == 0) {
			$locPeca = "<em style='color:darkred'>Pe&ccedil;a '$referencia' n&atilde;o encontrada!</em>";
		} else if (is_null($loc)) {
			$locPeca = "<em>sem localiza&ccedil;&atilde;o</em>";
		} else {
			list($locPeca, $pecaID) = pg_fetch_row($res, 0);
		}
	}

	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";

	// Separei em linhas para que a montagem do grupo fique mais clara
	// O teste ternário coloca o atributo de autofoco apenas no primeiro campo
	echo "<td align='center' nowrap>" .
		"<input style='font-family:VT323,Courier New,monospace' " .
		"type='text' class='ref' data-pos='$i' name='referencia_$i' " .
		"value='$referencia' " .
		"size='20' " .
		($i == 0 ? "autofocus='true'" : "") .
		"maxlength='20'>" .
		"</td>\n";
	echo "<td><select id='fabricas' name='fabrica_{$i}'></select></td>";
	echo "<td align='center' class='loc-ant-$i' nowrap>$locPeca</td>\n";
	echo "<td align='center' nowrap><input style='font-family:Courier New' class='localizacao' onkeyup='alteraMaiusculo(this)' type='text' name='localizacao_$i' title='Formato Válido: LL-LNN-LNN, LNN-LNN, LLL-LNNN, LNNN-LNN' value='$localizacao' size='20' maxlength='20'></td>\n";
	echo "</tr>\n";
}
?>
  
    	</tr>
		<tr>
			<td colspan="3">
				<input type="hidden" name="qtde_item" value="<?=$i?>">
				<button name="btn_acao" style="margin-left: 55px;" value="Mudar!">Mudar!</button>
			</td>
		</tr>
	</tbody>
</table>
</form>
<script>
<?php
/**
 * Este código usa as novas APIs do ES2015 Fetch, FormData, Request e Response,
 * NÃO funciona no IE. Com sorte, ninguèm no Distrib está mais usando o IE.
 * Precisando, refatorar usando jQuery
 **/
?>
var pecas = document.getElementsByClassName('ref');
for (var idx in pecas) {
	if (pecas[idx].classList.contains('ref')) { // Em tese este 'if' sobra...
		pecas[idx].addEventListener('change', function(el) {
			var peca = this;
			var pos = peca.dataset.pos;
			var loc = document.querySelector('.loc-ant-'+pos);

			var params = new FormData();
			params.append('btn_acao', 'ajax');
			params.append('referencia', peca.value.trim());

			fetch(
				document.location.pathname, {
				method: 'POST',
				cache: 'default',
				credentials: 'include', // send some cookies
				body: params
			}).then(function(res) {
				return res.text();
			}).then(function(resText) {
				loc.innerHTML = resText;
			});
		});
	}
}
</script>
<?
include "rodape.php";
?>
</body>