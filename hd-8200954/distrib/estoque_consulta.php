<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){

	$term = $_GET['q'];

	if (!strlen($term)) {
		exit;
	}

	$limit = "LIMIT 21";
		
	$ilike = "tbl_produto.referencia ILIKE '%{$term}%' OR TO_ASCII(tbl_produto.descricao, 'LATIN-9') ILIKE TO_ASCII('%{$term}%', 'LATIN-9')";


	$sql = "SELECT tbl_produto.produto AS id, tbl_produto.referencia AS cod, tbl_produto.descricao AS desc
			FROM tbl_produto
			JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE (tbl_produto.fabrica_i in ( $telecontrol_distrib ) OR tbl_produto.fabrica_i in ( 11,172 ))
			AND (tbl_linha.fabrica in ( $telecontrol_distrib ) OR tbl_linha.fabrica in ( 11,172 ))
			AND ({$ilike})
			{$limit}";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		for ($i = 0; $i < pg_num_rows($res); $i++ ){
			$referencia  = trim(pg_fetch_result($res,$i,'cod'));
			$descricao   = trim(pg_fetch_result($res,$i,'desc'));
			$produto     = trim(pg_fetch_result($res,$i,'id'));

			echo "$referencia|$descricao \n";
		}
	}

	exit;
}

$peca = trim ($_GET['peca']);
$busca      = trim ($_GET['busca']);

if (strlen($peca)>0 AND strlen($busca)>0){

	$sql = "SELECT peca,referencia,descricao FROM tbl_peca WHERE peca = $peca";
	$res = pg_query($con,$sql);
	$peca      = pg_result ($res,0,peca);
	$referencia= pg_result ($res,0,referencia);
	$descricao = pg_result ($res,0,descricao);

	echo "<html>";
	echo "<head>";
	echo "<title>Estoque de Peças</title>";
	echo '<link type="text/css" rel="stylesheet" href="css/css.css">';
	echo "</head>";
	echo "<body>";

	echo "<span style='align:center'><h2>$referencia - $descricao</h2></span>";

	if ($busca == 'pedido_fabrica'){

		#echo "<p>Somente pedidos sem recebimento</p>"; # HD 13939
		#Comentado: HD 41813

		$sql = "SELECT tbl_pedido.pedido,
						TO_CHAR (tbl_pedido.data,'DD/MM/YYYY') AS data    ,
						tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
						tbl_pedido.status_pedido                          ,
						tbl_pedido_item.qtde                              ,
						tbl_pedido_item.qtde_faturada                     ,
						tbl_pedido_item.qtde_cancelada
			FROM tbl_pedido_item
			JOIN tbl_pedido        USING (pedido)
			JOIN tbl_tipo_pedido   USING (tipo_pedido)
			WHERE (
					(
						tbl_pedido.posto  in (4311,20682,376542)
						/*AND ( tbl_pedido.tipo_pedido = 2 OR tbl_pedido.tipo_pedido = 131 )
					)

					OR

					(
						tbl_pedido.distribuidor  in (4311,20682,376542)
						AND ( tbl_pedido.tipo_pedido = 3 OR tbl_pedido.tipo_pedido = 132 )*/
					)
			)
			/* HD 43268 NOT IN (10) */
			AND   tbl_pedido.fabrica NOT IN (0,10)
			AND   tbl_pedido_item.peca = $peca
			AND   tbl_pedido.data > '2010-01-08 00:00:00'
			AND   tbl_pedido.data > CURRENT_DATE - INTERVAL '600 days'
			AND   (tbl_pedido.status_pedido NOT IN (3,4,6,13) OR tbl_pedido.status_pedido IS NULL)
			AND     tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)
			ORDER BY tbl_pedido.pedido DESC";
			//hd 36986
			//LIMIT 50 ";

		 # HD 13939
		 /*
		$sql = "SELECT DISTINCT
						tbl_pedido.pedido,
						TO_CHAR (tbl_pedido.data,'DD/MM/YYYY')  AS data,
						tbl_tipo_pedido.descricao               AS tipo_pedido_descricao,
						tbl_pedido.status_pedido,
						tbl_pedido_item.qtde,
						tbl_pedido_item.qtde_faturada,
						tbl_pedido_item.qtde_cancelada
			FROM tbl_pedido_item
			JOIN tbl_pedido USING (pedido)
			JOIN tbl_tipo_pedido USING (tipo_pedido)
			LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido AND tbl_faturamento_item.peca = tbl_pedido_item.peca
			LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			WHERE (
				(tbl_pedido.posto  in (4311,20682,376542) AND tbl_pedido.tipo_pedido = 2) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3)OR
				(tbl_pedido.posto  in (4311,20682,376542) AND tbl_pedido.tipo_pedido = 131) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 132)OR
				(tbl_pedido.fabrica = 10 AND (tbl_pedido.pedido_loja_virtual IS TRUE OR tbl_pedido.posto = 14076))
				)
			AND   tbl_pedido_item.peca = $peca
			AND   tbl_pedido.data > CURRENT_DATE - INTERVAL '600 days'
			AND   (tbl_faturamento_item.faturamento_item IS NULL OR (tbl_faturamento.posto  in (4311,20682,376542) AND  tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.conferencia IS NULL ))
			ORDER BY tbl_pedido.pedido DESC
			LIMIT 50 ";
*/

		#echo $sql ;
		#exit;

		$res = pg_query($con,$sql);

		if(pg_numrows ($res)==0){
			echo "<br><center><b>PEDIDOS REALIZADOS PARA A FÁBRICA</center></b>";
			echo "<center><span class='vermelho'>Não foi efetuado pedido para fábrica</span></center><br>";
		}else{
			echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td colspan='6' align='center'>Pedidos realizados para a Fábrica</td>";
			echo "</tr>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Pedido</td>";
			echo "<td>Data</td>";
			echo "<td>Tipo</td>";
			echo "<td>Qtde</td>";
			echo "<td>Cancelado</td>";
			echo "<td>Faturado</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$cor = "#cccccc";
				if ($i % 2 == 0) $cor = '#eeeeee';

				echo "<tr bgcolor='$cor'>";

				echo "<td title='Número do pedido'>";
				echo pg_result ($res,$i,pedido);
				echo "</td>";

				echo "<td title='Data'>";
				echo pg_result ($res,$i,data);
				echo "</td>";

				echo "<td title='Tipo'>";
				echo pg_result ($res,$i,tipo_pedido_descricao);
				echo "</td>";

				echo "<td align='right' title='Quantidade'>";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				echo "<td align='right' title='Cancelado'>";
				echo pg_result ($res,$i,qtde_cancelada);
				echo "</td>";

				echo "<td align='right' title='Faturado'>";
				echo pg_result ($res,$i,qtde_faturada);
				echo "</td>";

				echo "</tr>";
			}

			echo "</table>";
		}
		exit();
	}

	if ($busca == 'pedido_transportadora'){

		$sql = "
			SELECT tbl_faturamento_item.pedido,fat.nota_fiscal,fat.emissao,peca, SUM (qtde) AS qtde_transp
			INTO TEMP tmp_transp_$login_posto
			FROM tbl_faturamento_item
			JOIN (
				SELECT faturamento,nota_fiscal,emissao
				FROM tbl_faturamento
				WHERE tbl_faturamento.posto    in (4311,20682,376542)
				AND   tbl_faturamento.conferencia  IS NULL
				AND   tbl_faturamento.cancelada    IS NULL
				AND   tbl_faturamento.distribuidor IS NULL
			) fat ON tbl_faturamento_item.faturamento = fat.faturamento
			WHERE tbl_faturamento_item.peca = $peca
			GROUP BY tbl_faturamento_item.pedido,fat.nota_fiscal,fat.emissao,tbl_faturamento_item.peca;

			CREATE INDEX tmp_transp_peca_$login_posto ON tmp_transp_$login_posto(peca);

			SELECT	transp.nota_fiscal,
					TO_CHAR (transp.emissao,'DD/MM/YYYY')  AS emissao,
					transp.pedido,
					transp.qtde_transp,
					TO_CHAR (tbl_pedido.data,'DD/MM/YYYY') AS data    ,
					tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
					tbl_pedido.status_pedido                          ,
					tbl_peca.peca                                     ,
					tbl_peca.referencia                               ,
					tbl_peca.descricao                                ,
					tbl_posto_estoque.qtde                            ,
					para.referencia                AS para_referencia ,
					para.descricao                 AS para_descricao  ,
					tbl_posto_estoque_localizacao.localizacao         ,
					(
							SELECT (
									SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela FROM tbl_posto_linha WHERE posto  in (4311,20682,376542)) ORDER BY preco DESC LIMIT 1
									)
							UNION
									(SELECT tbl_tabela_item.preco
									FROM tbl_tabela_item
									WHERE tbl_peca.peca = tbl_tabela_item.peca AND tbl_peca.fabrica = 10
									ORDER BY preco DESC
									LIMIT 1
									)
							LIMIT 1
						) AS preco
			FROM tmp_transp_$login_posto transp
			JOIN tbl_peca                 USING (peca)
			LEFT JOIN tbl_pedido          USING (pedido)
			LEFT JOIN tbl_tipo_pedido     USING (tipo_pedido)
			LEFT JOIN tbl_posto_estoque               ON tbl_peca.peca        = tbl_posto_estoque.peca AND tbl_posto_estoque.posto  in ($login_posto)
			LEFT JOIN tbl_posto_estoque_localizacao   ON tbl_peca.peca        = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto  in ($login_posto)
			LEFT JOIN tbl_depara                      ON tbl_peca.peca        = tbl_depara.peca_de
			LEFT JOIN tbl_peca para                   ON tbl_depara.peca_para = para.peca
			WHERE (tbl_peca.peca = $peca OR para.peca = $peca)
			ORDER BY tbl_peca.descricao";

		$sql = "
			SELECT tbl_faturamento_item.pedido,fat.nota_fiscal,fat.emissao,peca, SUM (qtde) AS qtde_transp
			INTO TEMP tmp_transp_$login_posto
			FROM tbl_faturamento_item
			JOIN (
				SELECT faturamento,nota_fiscal,emissao
				FROM tbl_faturamento
				WHERE tbl_faturamento.posto    in (4311,20682,376542)
				AND   tbl_faturamento.fabrica <> 0
				AND   tbl_faturamento.conferencia  IS NULL
				AND   tbl_faturamento.cancelada    IS NULL
				AND   tbl_faturamento.distribuidor IS NULL
			) fat ON tbl_faturamento_item.faturamento = fat.faturamento
			WHERE tbl_faturamento_item.peca = $peca
			GROUP BY tbl_faturamento_item.pedido,fat.nota_fiscal,fat.emissao,tbl_faturamento_item.peca;

			CREATE INDEX tmp_transp_peca_$login_posto ON tmp_transp_$login_posto(peca);

			SELECT	transp.nota_fiscal,
					TO_CHAR (transp.emissao,'DD/MM/YYYY')  AS emissao,
					transp.pedido,
					transp.qtde_transp,
					TO_CHAR (tbl_pedido.data,'DD/MM/YYYY') AS data    ,
					tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
					tbl_pedido.status_pedido                          ,
					tbl_peca.peca                                     ,
					tbl_peca.referencia                               ,
					tbl_peca.descricao                                ,
					tbl_posto_estoque.qtde                            ,
					para.referencia                AS para_referencia ,
					para.descricao                 AS para_descricao  ,
					tbl_posto_estoque_localizacao.localizacao         ,
					(
							SELECT (
									SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela FROM tbl_posto_linha WHERE posto  in (4311,20682,376542)) ORDER BY preco DESC LIMIT 1
									)
							UNION
									(SELECT tbl_tabela_item.preco
									FROM tbl_tabela_item
									WHERE tbl_peca.peca = tbl_tabela_item.peca AND tbl_peca.fabrica = 10
									ORDER BY preco DESC
									LIMIT 1
									)
							LIMIT 1
						) AS preco
			FROM tmp_transp_$login_posto transp
			JOIN tbl_peca                 USING (peca)
			LEFT JOIN tbl_pedido          USING (pedido)
			LEFT JOIN tbl_tipo_pedido     USING (tipo_pedido)
			LEFT JOIN tbl_posto_estoque               ON tbl_peca.peca        = tbl_posto_estoque.peca AND tbl_posto_estoque.posto  in ($login_posto)
			LEFT JOIN tbl_posto_estoque_localizacao   ON tbl_peca.peca        = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto  in ($login_posto)
			LEFT JOIN tbl_depara                      ON tbl_peca.peca        = tbl_depara.peca_de
			LEFT JOIN tbl_peca para                   ON tbl_depara.peca_para = para.peca
			WHERE (tbl_peca.peca = $peca OR para.peca = $peca)
			ORDER BY tbl_peca.descricao";
			#echo nl2br($sql);
			#exit;
		$res = pg_query($con,$sql);

		if(pg_numrows ($res)==0){
			echo "<br><center><b>PEDIDOS NA TRANSPORTADORA</center></b>";
			echo "<center><span class='vermelho'>Não registro encontrado!</span></center><br>";
		}else{
			echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td colspan='6' align='center'>Pedidos realizados para a Fábrica</td>";
			echo "</tr>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>NF</td>";
			echo "<td>Data NF</td>";
			echo "<td>Pedido</td>";
			echo "<td>Data</td>";
			echo "<td>Tipo</td>";
			echo "<td>Qtde Transp</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$cor = "#cccccc";
				if ($i % 2 == 0) $cor = '#eeeeee';

				echo "<tr bgcolor='$cor'>";

				echo "<td title='Nota Fiscal'>";
				echo pg_result ($res,$i,nota_fiscal);
				echo "</td>";

				echo "<td title='Data da Nota Fiscal'>";
				echo pg_result ($res,$i,emissao);
				echo "</td>";

				echo "<td title='Número do pedido'>";
				echo pg_result ($res,$i,pedido);
				echo "</td>";

				echo "<td title='Data'>";
				echo pg_result ($res,$i,data);
				echo "</td>";

				echo "<td title='Tipo'>";
				echo pg_result ($res,$i,tipo_pedido_descricao);
				echo "</td>";

				echo "<td align='right' title='Quantidade'>";
				echo pg_result ($res,$i,qtde_transp);
				echo "</td>";

				echo "</tr>";
			}

			echo "</table>";
		}
		exit();
	}

	if ($busca=='pedido_postos'){

		echo "<p>Somente pedidos pendentes</p>";

		#---------- Postos que fizeram pedido desta peça e que está pendente (não atendida e nem embarcada) ------------#
		//HD 272650: Alterei a SQL, estava filtrando os pedidos pendentes depois de somar, usando HAVING. Além de ficar bem mais lento, está errado, pois incluia todos os pedidos atendidos daquela peça caso um único não estivesse atendido. Acrescentei a condição status_pedido<>14
		$sql = "
		SELECT
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_pedido.pedido,
		TO_CHAR (tbl_pedido.data,'DD/MM/YYYY') AS data,
		tbl_tipo_pedido.descricao              AS tipo_pedido_descricao,
		tbl_pedido.status_pedido,
		sum (tbl_pedido_item.qtde) as qtde,
		sum(tbl_pedido_item.qtde_faturada_distribuidor) as qtde_faturada_distribuidor,
		sum(tbl_pedido_item.qtde_cancelada) as qtde_cancelada

		FROM
		tbl_pedido_item
		JOIN tbl_pedido        USING (pedido)
		JOIN tbl_peca          USING (peca)
		JOIN tbl_tipo_pedido   USING (tipo_pedido)
		JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_pedido.fabrica = tbl_posto_fabrica.fabrica
		JOIN tbl_posto         ON tbl_pedido.posto = tbl_posto.posto

		WHERE
		tbl_pedido.distribuidor  in (4311,20682,376542)
		AND tbl_pedido.fabrica <> 0
		AND tbl_peca.peca = $peca
		AND tbl_pedido.status_pedido <> 14
		AND qtde > qtde_cancelada + qtde_faturada_distribuidor

		GROUP BY
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_pedido.pedido,
		TO_CHAR(tbl_pedido.data,'DD/MM/YYYY'),
		tbl_tipo_pedido.descricao,
		tbl_pedido.status_pedido

		ORDER BY
		tbl_pedido.pedido DESC
		";
		//ADICIONEI: AND qtde > qtde_cancelada + qtde_faturada_distribuidor - HD  13939
//		echo nl2br($sql);die;
		$res = pg_query($con,$sql);
		if(pg_numrows ($res)==0){
			echo "<br><center><b>PEDIDOS REALIZADOS PELOS POSTOS</center></b>";
			echo "<center><span class='vermelho'>Não existe pedido pendente (Verifique se não existe pedido no embarque!)</span></center><br>";
		}else{

			echo "<br><table align='center' border='0' cellspacing='1' cellpadding='1'>";
			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td colspan='8' align='center'>Pedidos realizados pelos Postos</td>";
			echo "</tr>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Código</td>";
			echo "<td>Posto</td>";
			echo "<td>Pedido</td>";
			echo "<td>Data</td>";
			echo "<td>Tipo</td>";
			echo "<td>Qtde</td>";
			echo "<td>Cancelado</td>";
			echo "<td>Faturado</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$cor = "cccccc";
				if ($i % 2 == 0) $cor = '#eeeeee';

				echo "<tr bgcolor='$cor' style='font-size:11px'>";

				echo "<td title='Código do posto'>";
				echo pg_result ($res,$i,codigo_posto);
				echo "</td>";

				echo "<td title='Nome do posto'>";
				echo pg_result ($res,$i,nome);
				echo "</td>";

				echo "<td title='Número do pedido'>";
				echo pg_result ($res,$i,pedido);
				echo "</td>";

				echo "<td title='Data'>";
				echo pg_result ($res,$i,data);
				echo "</td>";

				echo "<td title='Tipo'>";
				echo pg_result ($res,$i,tipo_pedido_descricao);
				echo "</td>";

				echo "<td align='right' title='Quantidade'>";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				echo "<td align='right' title='Cancelado'>";
				echo pg_result ($res,$i,qtde_cancelada);
				echo "</td>";

				echo "<td align='right' title='Faturado'>";
				echo pg_result ($res,$i,qtde_faturada_distribuidor);
				echo "</td>";

				echo "</tr>";
			}

			echo "</table>";
		}
		exit();
	}
	exit();
}



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Estoque de Peças</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script src='../admin/plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='../admin/plugins/shadowbox_lupa/shadowbox.css' />

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">
$().ready(function() {
	Shadowbox.init();

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}


	$("#descricao").autocomplete("<?echo 'peca_consulta_ajax.php?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0]; return row[1];}
	});

	$("#descricao").result(function(event, data, formatted) {
		$("#referencia").val(data[1]) ;
		$("#descricao").val(data[2]) ;
	});

	$("#referencia_produto").autocomplete("<?= $_SERVER['PHP_SELF'].'?busca=referencia' ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0]; return row[1];}
	});

	$("#referencia_produto").result(function(event, data, formatted) {
		$("#referencia_produto").val(data[0]) ;
		$("#descricao_produto").val(data[1]) ;
	});

	$("#descricao_produto").autocomplete("<?= $_SERVER['PHP_SELF'].'?busca=descricao' ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0]; return row[1];}
	});

	$("#descricao_produto").result(function(event, data, formatted) {
		$("#referencia_produto").val(data[0]) ;
		$("#descricao_produto").val(data[1]) ;
	});

});


var ok = false;
function checkaTodos() {
	f = document.frm_estoque_lista;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
			}
		}
	}
}

function abrePopup(url,largura,log){
	data = new Date();

	if (log != "log" && log != "imagens") {
		window.open(url, 'Consulta'+data.getSeconds(), 'width='+largura+',height=500,toolbar=0,resizable=1,scrollbars=1');
	} else if (log == "log") {

		Shadowbox.open({
			content:    url,
			player: "iframe",
			title:   "Log de alterações na peça",
			width:  1300,
			height: 700
		});

	} else {

		Shadowbox.open({
			content:    url,
			player: "iframe",
			title:   "Informações da peça",
			width:  1200,
			height: 700
		});

	}
}

</script>

<center><h1>Estoque de Peças</h1></center>

<p>
<?php
	$referencia   		 = trim ($_POST['referencia']);
	$descricao    		 = trim ($_POST['descricao']);
	$localizacao  		 = trim ($_POST['localizacao']);
	$referencia_produto  = trim ($_POST['referencia_produto']);
	$descricao_produto   = trim ($_POST['descricao_produto']);
?>

<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

<table>
<!--
	<tr>
		<td >Fábrica</td>
		<td align='left' colspan='6'>
		<?
		echo "<select style='width:200px;' name='fabrica' id='fabrica' ";
		$fabrica = $_GET["fabrica"];
//		if(strlen($fabrica)>0) echo " disabled ";
//		else echo "onChange='window.location=\"$PHP_SELF?fabrica=\"+this.value'";
		echo ">";
		echo "<option value=''>Selecionar</option>";
			$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN (3,25,51) ORDER BY nome";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				for($x = 0; $x < pg_numrows($res);$x++) {
					$aux_fabrica = pg_result($res,$x,fabrica);
					$aux_nome    = pg_result($res,$x,nome);
					echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
				}
			}
		echo "</select>";
		?>
		</td>
	</tr>
	<tr>
-->
		<td>Referência da Peça</td>
		<td><input type='text' size='10' name='referencia' id='referencia' class="frm" value="<?= $referencia ?>"></td>
		<td>Descrição da Peça</td>
		<td><input type='text' size='20' name='descricao'   id='descricao' class="frm" value="<?= $descricao ?>"></td>
		<td>Localização</td>
		<td colspan='3'><input type='text' size='10' name='localizacao' class="frm" value="<?= $localizacao ?>"></td>
	</tr>
	<tr>
		<td>Referência do Produto</td>
		<td><input type='text' size='10' name='referencia_produto' id='referencia_produto' class="frm" value="<?= $referencia_produto ?>"></td>
		<td>Descrição do Produto</td>
		<td><input type='text' size='20' name='descricao_produto'  id='descricao_produto' class="frm" value="<?= $descricao_produto ?>"></td>
	</tr>
	<tr>
		<td align='center' colspan='4'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
	</tr>
</table>
<br>



</form>
</center>


<?

// FAZ A CONSULTA COM A PELA DESCRICAO, SOMENTE SE ELA TIVER + Q 2 STRING
/*
if (strlen ($descricao) > 2) {
	$sql = "SELECT	tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_peca.ipi,
			tbl_posto_estoque.qtde,
			0 AS qtde_fabrica,
			0 AS qtde_transp,
			0 AS qtde_embarcada,
			para.referencia AS para_referencia,
			para.descricao AS para_descricao,
			tbl_posto_estoque_localizacao.localizacao,
			(
				SELECT tbl_tabela_item.preco
				FROM   tbl_tabela_item
				WHERE  tbl_tabela_item.peca = tbl_peca.peca
				AND    tbl_tabela_item.tabela IN (SELECT tabela_posto FROM tbl_posto_linha WHERE posto  in (4311,20682,376542))
				 ORDER BY preco DESC LIMIT 1
			) AS preco
		FROM       tbl_peca PE
		LEFT JOIN tbl_posto_estoque             ON PE.peca = tbl_posto_estoque.peca             AND tbl_posto_estoque.posto  in (4311,20682,376542)
		LEFT JOIN tbl_posto_estoque_localizacao ON PE.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto  in (4311,20682,376542)
		LEFT JOIN tbl_depara                    ON PE.peca = tbl_depara.peca_de
		LEFT JOIN tbl_peca           para       ON tbl_depara.peca_para = para.peca
		WHERE ( PE.descricao LIKE '%$descricao%' OR para.descricao LIKE '%$descricao%' )
		AND     PE.fabrica = $login_fabrica
		ORDER BY tbl_peca.descricao";

	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res)==0){
		echo "<center><b><span class='vermelho'>$descricao </span>- NENHUM PRODUTO COM ESSA DESCRIÇÃO FOI ENCONTRADO</center></b><br>";
	}
}
*/
//$fabrica = $_POST["fabrica"];
//if(strlen($fabrica)==0) $fabrica = $login_fabrica;

if ($_GET['eti']==1) {
	echo "<br><center>";
	echo "<form name='frm_lista' action='estoque_consulta_imprimir.php' method='POST' target='_blank'>";
	echo "<input type='hidden' name='lista' value='sim'>";
	echo "<br><label id='lista_referencias'>Digite as Etiquetas a Serem Impressas</label><br><textarea name='lista_referencias' cols='10' rows='10'></textarea>";
	echo "<br /><br /><label>
					<input type='radio' name='ativo' value='tela' checked>
					Visualizar em Tela
				</label>";

	echo "<label>
					<input type='radio' name='ativo' value='txt'>
					Gerar TXT
				</label><br /><br />";


	echo "<br><img border='0' src='../imagens/btn_continuar.gif' align='absmiddle' onclick=\"javascript: document.frm_lista.submit();\" style='cursor:pointer' alt='Clique aqui p/ localizar o número de série'>";
	echo "</form></center>";
} else {
	echo "<br><center><b><a href='$PHP_SELF?eti=1' >IMPRIMIR ETIQUETAS INDIVIDUAIS</a></b></center><br>";
}

system("rm /tmp/assist/relatorio-consulta-estoque-distrib.csv");
$fp = fopen ("/tmp/assist/relatorio-consulta-estoque-distrib.csv","w");

//system("rm /home/kaique/public_html/PosVenda/admin/xls/assist/relatorio-consulta-estoque-distrib.csv");
//$fp = fopen ("/home/kaique/public_html/PosVenda/admin/xls/relatorio-consulta-estoque-distrib.csv","w");

if (strlen ($referencia) > 2 || !empty($referencia_produto)) {
	$telecontrol_distrib = $telecontrol_distrib.",11,172";
	$fabricas = array($telecontrol_distrib); // A variável nem estava definida!

	fputs ($fp,"Relatório de Estoque\n");

	if (!empty($referencia)) {
		$condPeca = " AND (referencia ILIKE '%$referencia%' AND fabrica IN (".implode(",", $fabricas)."))
			OR
			(referencia_pesquisa ILIKE '%$referencia%' AND fabrica IN (".implode(",", $fabricas)."))";
	} else {

		$join_lb = "JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca
					AND tbl_lista_basica.produto = (
						SELECT tbl_produto.produto
					    FROM tbl_produto
					    WHERE UPPER(tbl_produto.referencia) = UPPER('{$referencia_produto}')
					    AND tbl_produto.fabrica_i IN (".implode(",", $fabricas).")
					    LIMIT 1
					)";
	}

	$sqlx = "SELECT tbl_peca.peca,tbl_peca.fabrica
			FROM tbl_peca
			{$join_lb}
			WHERE tbl_peca.fabrica IN (".implode(",", $fabricas).")
			{$condPeca}
			ORDER BY fabrica";
	$resx = pg_query($con,$sqlx);

	if (pg_num_rows($resx)==0) {
		echo "Peça com a referência $referencia não encontrada";
		exit;
	}

	$primeiro_item = "";

	for ($x = 0; $x < pg_num_rows($resx); $x++) {

		$peca    = pg_fetch_result($resx,$x,peca);
		$fabrica = pg_fetch_result($resx,$x,fabrica);

		//hd 36986 - comentei condições de posto
		$sql = "

			/*CREATE INDEX tmp_ce1_peca_$login_posto_$x ON tmp_ce1_$login_posto_$x(peca);*/

			SELECT peca, SUM (qtde) AS qtde_transp
			INTO TEMP tmp_ce2_$login_posto_$x
			FROM tbl_faturamento_item
			JOIN (
				SELECT faturamento
				FROM tbl_faturamento
				WHERE tbl_faturamento.posto    in (4311,20682,376542)
				AND   tbl_faturamento.fabrica = $fabrica
				AND   tbl_faturamento.conferencia  IS NULL
				AND   tbl_faturamento.cancelada    IS NULL
				AND   tbl_faturamento.distribuidor IS NULL
			) fat ON tbl_faturamento_item.faturamento = fat.faturamento
			WHERE tbl_faturamento_item.peca = $peca
			GROUP BY tbl_faturamento_item.peca;

			CREATE INDEX tmp_ce2_peca_$login_posto_$x ON tmp_ce2_$login_posto_$x(peca);

			SELECT peca, SUM (qtde) AS qtde_embarcada
			INTO TEMP tmp_ce3_$login_posto_$x
			FROM tbl_embarque_item
			JOIN tbl_embarque USING (embarque)
			WHERE tbl_embarque.faturar   IS NULL
			AND   tbl_embarque_item.peca = $peca
			GROUP BY tbl_embarque_item.peca;

			CREATE INDEX tmp_ce3_peca_$login_posto_$x ON tmp_ce3_$login_posto_$x(peca);

			SELECT	tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.ipi,
				tbl_posto_estoque.qtde,
				transp.qtde_transp,
				embarque.qtde_embarcada,
				para.referencia AS para_referencia,
				para.descricao AS para_descricao,
				tbl_posto_estoque_localizacao.localizacao,
				tbl_fabrica.nome,
				tbl_peca.peca,
				(
				SELECT (
					SELECT tbl_tabela_item.preco
					  FROM tbl_tabela_item
					  JOIN tbl_tabela USING(tabela)
					 WHERE tbl_tabela_item.peca = tbl_peca.peca
					   AND tbl_tabela_item.tabela IN (
							SELECT tabela
							  FROM tbl_posto_linha
							 WHERE posto IN(4311,20682,376542)
						)
					   AND tbl_tabela.tabela_garantia
					 ORDER BY preco DESC
					 LIMIT 1
					)
					UNION
					(SELECT tbl_tabela_item.preco
					FROM tbl_tabela_item
					JOIN tbl_tabela USING(tabela)
					WHERE tbl_peca.peca = tbl_tabela_item.peca AND tbl_peca.fabrica = 10
					AND tbl_tabela.tabela_garantia
					ORDER BY preco DESC
					LIMIT 1
					)
					LIMIT 1
				) AS preco,
				(
				SELECT (
					SELECT tbl_tabela_item.preco
					  FROM tbl_tabela_item
					  JOIN tbl_tabela USING(tabela)
					 WHERE tbl_tabela_item.peca = tbl_peca.peca
					   AND tbl_tabela_item.tabela IN (
							SELECT tabela
							  FROM tbl_posto_linha
							 WHERE posto IN(4311,20682,376542)
						)
					   AND tbl_tabela.tabela_garantia IS NOT TRUE
					 ORDER BY preco DESC
					 LIMIT 1
					)
					UNION
					(SELECT tbl_tabela_item.preco
					FROM tbl_tabela_item
					JOIN tbl_tabela USING(tabela)
					WHERE tbl_peca.peca = tbl_tabela_item.peca
					AND tbl_tabela_item.peca = $peca
					AND tbl_tabela.tabela_garantia IS NOT TRUE
					ORDER BY preco DESC
					LIMIT 1
					)
					LIMIT 1
				) AS preco_venda
			FROM   tbl_peca
			LEFT JOIN tbl_posto_estoque                 ON tbl_peca.peca        = tbl_posto_estoque.peca
			                                           AND tbl_posto_estoque.posto IN ($login_distrib_postos)
			LEFT JOIN tbl_posto_estoque_localizacao     ON tbl_peca.peca        = tbl_posto_estoque_localizacao.peca
			                                           AND tbl_posto_estoque_localizacao.posto IN ($login_distrib_postos)
			LEFT JOIN tbl_depara                        ON tbl_peca.peca        = tbl_depara.peca_de
			LEFT JOIN tbl_peca para                     ON tbl_depara.peca_para = para.peca
			/*LEFT JOIN tmp_ce1_$login_posto_$x fabrica ON tbl_peca.peca        = fabrica.peca*/
			LEFT JOIN tmp_ce2_$login_posto_$x transp    ON tbl_peca.peca        = transp.peca
			LEFT JOIN tmp_ce3_$login_posto_$x embarque  ON tbl_peca.peca        = embarque.peca
			JOIN tbl_fabrica                            ON tbl_peca.fabrica     = tbl_fabrica.fabrica
			WHERE (tbl_peca.peca    = $peca OR para.peca = $peca)
			  AND  tbl_peca.fabrica = $fabrica
			ORDER BY tbl_peca.descricao";	
		$res = pg_query($con,$sql);
		//echo "<br /><br />".$sql;
		//echo "<br />".pg_num_rows($res);
		if(pg_num_rows($res)==0){
			echo "<center><b><span class='vermelho'>$referencia </span>- CÓDIGO DE PEÇA NÃO CADASTRADO</center></b><br>";
			exit;
		} else {
			if ($primeiro_item == '') {

				$cabecalho = [];

				echo "<br><table align='center' border='0' cellspacing='1' cellpadding='5'>";
				echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
				echo "<td>Fabrica</td>";
				$cabecalho[] = "Fábrica";

				echo "<td>Referência</td>";
				$cabecalho[] = "Referência";

				echo "<td>Descrição</td>";
				$cabecalho[] = "Descrição";

				echo "<td>Informações</td>";

				echo "<td>Disponível</td>";
				$cabecalho[] = "Disponível";

				echo "<td>Embarcado</td>";
				$cabecalho[] = "Embarcado";

				echo "<td>Fábrica</td>";

				echo "<td>Transp.</td>";

				echo "<td>Pedido/Postos</td>";

				echo "<td>Localização</td>";
				$cabecalho[] = "Localização";

				echo "<td>Preço Garantia</td>";
				$cabecalho[] = "Preço Garantia";

				echo "<td>Preço Faturado</td>";
				$cabecalho[] = "Preço Faturado";

				echo "</tr>";

				fputs ($fp, implode(";", $cabecalho)."\n");

				$primeiro_item = 't';
			}

			for ($i = 0; $i < pg_numrows($res); $i++) {

				$linha = [];

				$cor = "cccccc";
				if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $cor = '#eeeeee';

				echo "<tr bgcolor='$cor'>";

				if (strlen ($localizacao) > 2){
					echo "<td align='center'><input type='checkbox' name='pecas_$i' value='".trim (pg_result ($res,$i,referencia))."'></td>";
				}

				echo "<td>";

				echo pg_result ($res,$i,nome);
				$linha[] = pg_result ($res,$i,nome);

				echo "</td>";

				echo "<td>";
				echo pg_result ($res,$i,referencia);
				$refExcel = pg_result ($res,$i,referencia);

				if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) {
					echo "<br>" . pg_result ($res,$i,para_referencia);
					$refExcel .= "PARA: ".pg_result ($res,$i,para_referencia);
				} 
				$linha[] = $refExcel;

				echo "</td>";

				echo "<td>";
				echo pg_result ($res,$i,descricao);
				$descExcel = pg_result($res,$i,descricao);

				if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) {

					echo "<br>" . pg_result ($res,$i,para_descricao);
					$descExcel .= " PARA: ".pg_result ($res,$i,para_descricao);

				} 
					
				$linha[] = $descExcel;

				echo "</td>";

				echo "<td align='center'>";
				echo "<button onclick=\"javascript:abrePopup('imagens_peca_distrib.php?peca=$peca&parametro=tbl_peca_adicionais&id=$fabrica*$peca',800,'imagens')\"'>Informações</button>";
				echo "</td>";

				echo "<td align='center'>";
				echo pg_result ($res,$i,qtde);
				$linha[] = pg_result ($res,$i,qtde);
				echo "</td>";

				echo "<td align='center'>";
				echo pg_result ($res,$i,qtde_embarcada);
				$linha[] = pg_result ($res,$i,qtde_embarcada);

				echo "</td>";

				$sql = "
				SELECT
				peca,
				SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica,
				tbl_tipo_pedido.codigo AS tipo_pedido_codigo
				FROM
				tbl_pedido_item
				JOIN tbl_pedido ON tbl_pedido_item.pedido=tbl_pedido.pedido
				JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido=tbl_tipo_pedido.tipo_pedido

				WHERE
				tbl_pedido.fabrica NOT IN (0,10) /* HD 43268 NOT IN (10) */
				AND tbl_pedido.posto  in (4311,20682,376542)
				AND (tbl_pedido.distribuidor not in (4311,20682,376542) or tbl_pedido.distribuidor isnull)
				AND tbl_pedido.fabrica   = $fabrica
				AND tbl_pedido_item.peca = $peca
				AND tbl_pedido.data > '2010-01-08 00:00:00'
				AND (tbl_pedido.status_pedido NOT IN (3,4,6,13) OR tbl_pedido.status_pedido IS NULL)
				AND tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)

				GROUP BY
				tbl_pedido_item.peca,
				tbl_tipo_pedido.codigo
				";
				$res_qtde_fabrica = pg_query($con, $sql);

				echo "<td style='padding: 0px;' align='center'>";
				echo "<table cellpadding='3' cellspacint='0' border='0' style='display: inline; text-align: center; cursor:pointer;' onclick=\"javascript:abrePopup('$PHP_SELF?peca=$peca&busca=pedido_fabrica',600)\">";
				echo "<tr style='background-color: #555555; color:#FFFFFF; font-weight: bold;'>";
				for ($q = 0; $q < pg_num_rows($res_qtde_fabrica); $q++) {
					$tipo_pedido_codigo = pg_result($res_qtde_fabrica, $q, tipo_pedido_codigo);
					echo "<td>$tipo_pedido_codigo</td>";

					if ($tipo_pedido_codigo == "FAT") {
						$qtde_pedido_fabrica_faturado = pg_result($res_qtde_fabrica, $q, qtde_fabrica);
						if (strlen($qtde_pedido_fabrica_faturado) == 0) {
							$qtde_pedido_fabrica_faturado = 0;
						}
					}
					elseif ($tipo_pedido_codigo == "GAR") {
						$qtde_pedido_fabrica_garantia = pg_result($res_qtde_fabrica, $q, qtde_fabrica);
						if (strlen($qtde_pedido_fabrica_garantia) == 0) {
							$qtde_pedido_fabrica_garantia = 0;
						}
					}
				}
				echo "</tr>";

				echo "<tr style='background-color: #999999; color:#000000; font-weight: bold;'>";
				for ($q = 0; $q < pg_num_rows($res_qtde_fabrica); $q++) {
					$qtde_fabrica = pg_result($res_qtde_fabrica, $q, qtde_fabrica);
					echo "<td>$qtde_fabrica</td>";
				}
				echo "</tr>";
				echo "</table>";
				echo "</td>";

				echo "<td align='center'>";
				echo "<a  href=\"javascript:abrePopup('$PHP_SELF?peca=$peca&busca=pedido_transportadora',600)\" targe='_blank'>";
				echo "</td>";

				$sql = "
				SELECT
				peca,
				SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_pedido,
				tbl_tipo_pedido.codigo AS tipo_pedido_codigo

				FROM
				tbl_pedido_item
				JOIN tbl_pedido USING (pedido)
				JOIN tbl_peca USING (peca)
				JOIN tbl_tipo_pedido USING (tipo_pedido)
				JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_pedido.fabrica = tbl_posto_fabrica.fabrica
				JOIN tbl_posto         ON tbl_pedido.posto = tbl_posto.posto

				WHERE
				tbl_pedido.distribuidor  in (4311,20682,376542)
				AND tbl_pedido.fabrica <> 0
				AND tbl_peca.peca = $peca
				AND tbl_pedido.status_pedido <> 14
				AND qtde > qtde_cancelada + qtde_faturada_distribuidor

				GROUP BY
				tbl_pedido_item.peca,
				tbl_tipo_pedido.codigo
				";

				$res_qtde_pedido = pg_query($con, $sql);

				echo "<td style='padding: 0px;' align='center'>";
				echo "<table onclick=\"javascript:abrePopup('$PHP_SELF?peca=$peca&busca=pedido_postos',750)\" cellpadding='3' cellspacint='0' border='0' style='display: inline; text-align: center; cursor:pointer;'>";
				echo "<tr style='background-color: #555555; color:#FFFFFF; font-weight: bold;'>";
				for ($q = 0; $q < pg_num_rows($res_qtde_pedido); $q++) {
					$tipo_pedido_codigo = pg_result($res_qtde_pedido, $q, tipo_pedido_codigo);
					echo "<td>$tipo_pedido_codigo</td>";

					if ($tipo_pedido_codigo == "FAT") {
						$qtde_pedido_faturado = pg_result($res_qtde_pedido, $q, qtde_pedido);
						if (strlen($qtde_pedido_faturado) == 0) {
							$qtde_pedido_faturado = 0;
						}
					}
					elseif ($tipo_pedido_codigo == "GAR") {
						$qtde_pedido_garantia = pg_result($res_qtde_pedido, $q, qtde_pedido);
						if (strlen($qtde_pedido_garantia) == 0) {
							$qtde_pedido_garantia = 0;
						}
					}
				}
				echo "</tr>";

				echo "<tr style='background-color: #999999; color:#000000; font-weight: bold;'>";
				for ($q = 0; $q < pg_num_rows($res_qtde_pedido); $q++) {
					$qtde_pedido = pg_result($res_qtde_pedido, $q, qtde_pedido);
					echo "<td>$qtde_pedido</td>";
				}
				echo "</tr>";
				echo "</table>";
				echo "</td>";

				echo "<td align='center'>";
				$locPc = pg_fetch_result($res,$i,localizacao);
				echo ($locPc) ? : 'SL';
				$linha[] = ($locPc) ? : 'SL';

				echo "&nbsp; <input type='button' "
					. "alt=\"../admin/relatorio_log_alteracao_new.php?parametro=tbl_posto_estoque_localizacao&id=$login_posto*$peca\" "
					. "class='thickbox' title='Consultar LOG de Localização' "
					. "data-value=\"$login_posto*'$locPc'\"i value='LOG' />";
				echo "</td>";

				$preco1 = pg_result ($res,$i,preco);
				echo "<td align='center'>&nbsp;";
				echo number_format ($preco1,4,",",".");

				$linha[] = number_format ($preco1,4,",",".");

				echo "</td>";

				$preco = pg_result ($res,$i,preco_venda) ;
				echo "<td align='center'>&nbsp;";
				echo number_format ($preco,4,",",".");

				$linha[] = number_format ($preco,4,",",".");

				echo "</td>";

				echo "</tr>";

				fputs($fp, implode(";", $linha)."\n");

			}
		}
	}

	if ($primeiro_item == 't') {
		echo "</table>";

		#Fabio: fiz esta separação para
		//echo "<br><br><center><span><a  href=\"javascript:abrePopup('$PHP_SELF?peca=$peca&busca=pedido_fabrica',600)\" targe='_blank'>Pedidos realizados para a Fábrica</a></span></center><br>";

		//echo "<br><center><span><a  href=\"javascript:abrePopup('$PHP_SELF?peca=$peca&busca=pedido_transportadora',600)\" targe='_blank'>Pedidos na Transportadora</a></span></center><br>";

		//echo "<br><center><span><a href=\"javascript:abrePopup('$PHP_SELF?peca=$peca&busca=pedido_postos',750)\" >Postos que fizeram pedido desta peça, e que não estão atendida e nem embarcada</a></span></center><br><br>";
	}

	fclose ($fp);

	$datacsv = date("Y-m-d").".".date("H-i-s");

	//rename("/home/kaique/public_html/PosVenda/admin/xls/relatorio-consulta-estoque-distrib.csv", "../admin/xls/relatorio-consulta-estoque.$datacsv.csv");
	rename("/tmp/assist/relatorio-consulta-estoque-distrib.csv", "../admin/xls/relatorio-consulta-estoque.$datacsv.csv");

	echo "<table width='300' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>
                <tr>
                    <td align='left' valign='absmiddle'>
                        <a href='../admin/xls/relatorio-consulta-estoque.$datacsv.csv' target='_blank'>
                            <img src='../admin/imagens/excel.png' height='20px' width='20px' align='absmiddle'>    Download do Arquivo CSV
                        </a>
                    </td>
                </tr>
            </table>";

}

// FAZ A CONSULTA COM A PELA LOCALIZACAO, SOMENTE SE ELA TIVER + QUE 2 STRING
// colocado por Fabio 22/11/2006
if (strlen ($localizacao) > 2) {
	if (strlen($localizacao)==4 AND strtoupper($localizacao{0})=='P' AND strtoupper($localizacao{1})=='T'){
		$localizacao = '%'.$localizacao{2}.$localizacao{3};
	}
	else{
		$localizacao = "%$localizacao%";
	}
	$sql = "SELECT	tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					tbl_posto_estoque.qtde,
					0 AS qtde_fabrica,
					0 AS qtde_transp,
					0 AS qtde_embarcada,
					para.referencia AS para_referencia,
					para.descricao AS para_descricao,
					tbl_posto_estoque_localizacao.localizacao,
					tbl_fabrica.nome,
					(
							SELECT (
									SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela FROM tbl_posto_linha WHERE posto  in (4311,20682,376542)) ORDER BY preco DESC LIMIT 1
									)
							UNION
									(SELECT tbl_tabela_item.preco
									FROM tbl_tabela_item
									WHERE tbl_peca.peca = tbl_tabela_item.peca AND tbl_peca.fabrica = 10
									ORDER BY preco DESC
									LIMIT 1
									)
							LIMIT 1
						) AS preco
		FROM   tbl_peca
		LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto  in ($login_distrib_postos)
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto  in ($login_distrib_postos)
		LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
		LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
		JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica
		WHERE  (tbl_posto_estoque_localizacao.localizacao ILIKE '$localizacao')
		/*AND    tbl_peca.fabrica = $fabrica*/
		ORDER BY tbl_peca.descricao";
	//echo $sql;
	$res = pg_query($con,$sql);
	if(pg_numrows ($res)==0){
		echo "<center><b><span class='vermelho'>$localizacao </span>- NENHUM PRODUTO COM ESSA DESCRIÇÃO FOI ENCONTRADO</center></b><br>";
	} else {
		$localizacao = strtoupper($localizacao);
		echo "<center><b><span class='vermelho'>$localizacao</span> - <a href='javascript:document.frm_estoque_lista.submit()' >CLIQUE AQUI PARA ABRIR A TELA DE IMPRESSÃO</a></b></center><br>";
		echo "<form name='frm_estoque_lista' action='estoque_consulta_imprimir.php' method='post' target='_blank'>";
		echo "<br><table align='center' border='0' cellspacing='1' cellpadding='5'>";
		echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
		echo "<td align='center'><a href='javascript:checkaTodos()'>Todos</td>";

		echo "<td>Fabrica</td>";
		echo "<td>Referência</td>";
		echo "<td>Descrição</td>";
		echo "<td>Disponível</td>";
		echo "<td>Embarcado</td>";
		echo "<td>Fábrica</td>";
		echo "<td>Transp.</td>";
		echo "<td>Localização</td>";
		echo "<td>Preço</td>";
		echo "</tr>";

		for ($i = 0; $i < pg_numrows($res); $i++) {
			$cor = "cccccc";
			if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $cor = '#eeeeee';

			echo "<tr bgcolor='$cor'>";

			if (strlen ($localizacao) > 2){
				echo "<td align='center'><input type='checkbox' name='pecas_$i' value='".trim (pg_result ($res,$i,referencia))."'></td>";
			}

			echo "<td>";
			echo pg_result ($res,$i,nome);
			echo "</td>";

			echo "<td>";
			echo pg_result ($res,$i,referencia);
			if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
			echo "</td>";

			echo "<td>";
			echo pg_result ($res,$i,descricao);
			if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
			echo "</td>";

			echo "<td align='right'>&nbsp;";
			echo pg_result ($res,$i,qtde);
			echo "</td>";

			echo "<td align='right'>&nbsp;";
			echo pg_result ($res,$i,qtde_embarcada);
			echo "</td>";

			$qtde_fabrica = pg_result ($res,$i,qtde_fabrica);
			if ($qtde_fabrica < 0) $qtde_fabrica = 0;

			echo "<td align='right'>&nbsp;";
			echo $qtde_fabrica;
			echo "</td>";

			echo "<td align='right'>&nbsp;";
			echo pg_result ($res,$i,qtde_transp);
			echo "</td>";

			echo "<td align='left'>&nbsp;";
			$locPc = pg_result ($res,$i,localizacao);
				echo ($locPc) ? : 'SL';
				echo "&nbsp; <input type='button' "
					. "alt=\"../admin/relatorio_log_alteracao_new.php?parametro=tbl_posto_estoque_localizacao&id=$login_posto*$peca\" "
					. "class='thickbox' title='Consultar LOG de Localização' "
					. "data-value=\"$login_posto*'$locPc'\"i value='LOG' />";
			echo "</td>";

			$preco = pg_result ($res,$i,preco) * (1 + (pg_result ($res,$i,ipi) / 100)) ;
			echo "<td align='left'>&nbsp;";
			echo number_format ($preco,4,",",".");
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";

		echo "<input type='hidden' name='qtde' value='$i'>";
		echo "</form>";
	}
}

#---------- Pedidos desta peça para a Fábrica ------------#

if (isset($_POST['btn_acao']) AND (strlen ($descricao) < 3 AND strlen ($referencia) < 3 AND strlen ($localizacao) < 3)) {
	echo "<br><br><center><b class='vermelho'>DIGITE NO MÍNIMO 3 CARACTERES PARA A BUSCA!</center></b>";
}
?>
<? #include "rodape.php"; ?>
</body>
</html>

