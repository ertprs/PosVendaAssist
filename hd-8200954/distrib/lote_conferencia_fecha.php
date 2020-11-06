<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "../funcoes.php";

/* foreach ($_POST as $key => $value) {
	echo $key." - ".$value." | ";
} */

?>

<link href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">

<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script type="text/javascript" src="../admin/js//jquery.mask.js"></script>

<script type="text/javascript">
	$('document').ready(function(){

		$('#data_fechamento').mask("99/99/9999");
	});

	$(function() {
		$( "#data_fechamento" ).datepicker({ dateFormat: "dd/mm/yy", dayNamesMin: ["D", "S", "T", "Q", "Q", "S", "S"] });
		$( "#data_fechamento" ).datepicker("option", "monthNames", ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"]);
	});
</script>

<style type="text/css">
	body{
		font: 12px arial;
	}
</style>

<?php

unset($distrib_lote);
unset($distrib_lote_salton);
unset($distrib_lote_cobimex);
unset($distrib_lote_positec);
unset($distrib_lote_wurth);

$distrib_lote 			= $_POST['distrib_lote'];
$distrib_lote_salton 	= $_POST['distrib_lote_salton'];
$distrib_lote_cobimex 	= $_POST['distrib_lote_cobimex'];
$distrib_lote_positec 	= $_POST['distrib_lote_positec'];
$distrib_lote_wurth 	= $_POST['distrib_lote_wurth'];

if(strlen($distrib_lote) == 0){
	if(strlen($distrib_lote_salton) > 0){
		$distrib_lote = $distrib_lote_salton;
		echo "<h1>Fábrica: BestWay</h1>";
	}

	if(strlen($distrib_lote_cobimex) > 0){
		$distrib_lote = $distrib_lote_cobimex;
		echo "<h1>Fábrica: Cobimex </h1>";
	}

	if(strlen($distrib_lote_positec) > 0){
		$distrib_lote = $distrib_lote_positec;
		echo "<h1>Fábrica: Positec</h1>";
	}

	if(strlen($distrib_lote_wurth) > 0){
		$distrib_lote = $distrib_lote_wurth;
		echo "<h1>Fábrica: Wurth</h1>";
	}
}else{
	echo "<h1>Fábrica: Gama Italy e Britânia</h1>";
}

$excluir = $_GET['excluir'];

$fechamento = $_POST['fechamento'];

if($fechamento == 'Fechamento' AND strlen($distrib_lote) > 0){
	$data_fechamento = $_POST['data_fechamento'];
	$data_fechamento = fnc_formata_data_pg ($data_fechamento);
	
	$sql = "UPDATE tbl_distrib_lote SET fechamento = $data_fechamento WHERE distrib_lote = $distrib_lote ; ";
	$res = pg_exec($con,$sql);
}

if (strlen ($distrib_lote) > 0) {
	$sql = "SELECT fabrica, fechamento FROM tbl_distrib_lote WHERE distrib_lote = $distrib_lote";
	$res = pg_exec ($con,$sql);
	$fabrica = pg_result ($res,0,fabrica);
	$fechado = pg_result ($res,0,fechamento);
}


if (strlen($excluir) > 0) {

	$res = pg_exec ($con,"BEGIN;");
	$sql = "DELETE FROM tbl_distrib_lote_posto
			WHERE distrib_lote = $distrib_lote
			AND posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$excluir' AND fabrica=$fabrica)";
	$res = pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		$sql = "DELETE FROM tbl_distrib_lote_os
				WHERE distrib_lote = $distrib_lote
				AND os IN (SELECT os FROM tbl_os WHERE posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$excluir' AND fabrica=$fabrica) 
				AND fabrica=$fabrica)";
		$res = pg_exec ($con,$sql);
		if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
	}
	
	if (strlen($msg_erro) > 0) {
		$res = pg_exec ($con,"ROLLBACK;");
		echo "$msg_erro";
	} else {
		$res = pg_exec ($con,"COMMIT;");
	}
}

/* ---------------------------------------------------------------------------- */

echo "<form method='post' name='frm_lote' action='$PHP_SELF'>";

$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
		FROM tbl_distrib_lote
		WHERE  tbl_distrib_lote.fabrica not in (7,81) 
		ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);
?>
<br>
<table class ='table_line' width='100%' border='0' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<thead>
		<tr bgcolor='#aaaadd'  background='../admin/imagens_admin/azul.gif'>
			<th colspan='100%'> RELATÓRIO DE LOTES</th>
		</tr>
		</thead>

<?
echo "<tr>";
echo "<td nowrap>Conferência por Lote Gama Italy e Britânia<br>";

echo "<select name='distrib_lote' size='1'>\n";
echo "<option></option>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	echo "<option value='" . pg_result ($res,$i,distrib_lote) . "' >" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>\n";
} 
 

echo "</select>\n";
echo "<input type='submit' name='btn_acao' value='Fechar Lote'>\n";
echo "</td>";
echo "<td nowrap>Conferência por Lote BestWay<br>";
$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
		FROM tbl_distrib_lote
		WHERE  tbl_distrib_lote.fabrica =81
		ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);


echo "<select name='distrib_lote_salton' size='1'>\n";
echo "<option></option>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>\n";
}
echo "</select>\n";
echo "<input type='submit' name='btn_acao' value='Fechar Lote'>\n";
echo "</td>";
##HD 1115756 
echo "<td nowrap>Conferência por Lote Cobimex<br>";
$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
                FROM tbl_distrib_lote
                WHERE  tbl_distrib_lote.fabrica =114
                ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);


echo "<select name='distrib_lote_cobimex' size='1'>\n";
echo "<option></option>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

        echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>\n";
}
echo "</select>\n";
echo "<input type='submit' name='btn_acao' value='Fechar Lote'>\n";
echo "</td>";
#### HD 1115756
echo "<td nowrap>Conferência por Lote Positec<br>";
$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
                FROM tbl_distrib_lote
                WHERE  tbl_distrib_lote.fabrica =123
                ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);


echo "<select name='distrib_lote_positec' size='1'>\n";
echo "<option></option>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

        echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>\n";
}
echo "</select>\n";
echo "<input type='submit' name='btn_acao' value='Fechar Lote'>\n";
echo "</td>";

echo "<td nowrap>Conferência por Lote Wurth<br>";
$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
                FROM tbl_distrib_lote
                WHERE  tbl_distrib_lote.fabrica =122
                ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);


echo "<select name='distrib_lote_wurth' size='1'>\n";
echo "<option></option>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

        echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>\n";
}
echo "</select>\n";
echo "<input type='submit' name='btn_acao' value='Fechar Lote'>\n";
echo "</td>";

echo "</tr></table>";

echo "</form>";

/* ---------------------------------------------------------------------------- */

/* echo "<form method='post' name='frm_lote' action='$PHP_SELF'>";
$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
		FROM tbl_distrib_lote
		ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);


echo "<select name='distrib_lote' size='1'>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>";
}
echo "</select>";

echo "<input type='submit' name='btn_acao' value='Fechar Lote'>"; */


if (strlen ($distrib_lote) > 0) {

	echo "<form method='post' name='frm_lote' action='$PHP_SELF'>";

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto ,
					tbl_posto.nome                 ,
					qtde.med_qtde_os               ,
					media.med_qtde_pecas           ,
					custo.med_custo                ,
					lote.qtde_os                   ,
					lote.mao_de_obra               ,
					lote.mobra_total
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
			JOIN   (SELECT tbl_os.posto, tbl_produto.mao_de_obra, SUM (tbl_produto.mao_de_obra) AS mobra_total, COUNT (tbl_os.os) AS qtde_os
					FROM tbl_os
					JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
					GROUP BY tbl_os.posto , tbl_produto.mao_de_obra
			) lote ON tbl_posto.posto = lote.posto
			LEFT JOIN   (SELECT tbl_os.posto, COUNT (tbl_os.os) AS med_qtde_os
					FROM tbl_os
					JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
					WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
					GROUP BY tbl_os.posto
			) qtde  ON tbl_posto.posto = qtde.posto
			LEFT JOIN   (SELECT tbl_os.posto, SUM (tbl_os_item.qtde) AS med_qtde_pecas
					FROM tbl_os
					JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
					JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
					GROUP BY tbl_os.posto
			) media ON tbl_posto.posto = media.posto
			LEFT JOIN   (SELECT tbl_os.posto, SUM (tbl_os_item.qtde * tbl_tabela_item.preco) AS med_custo
					FROM tbl_os
					JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
					JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto
					JOIN tbl_posto_linha ON tbl_os.posto = tbl_posto_linha.posto AND tbl_produto.linha = tbl_posto_linha.linha
					JOIN tbl_tabela_item ON tbl_posto_linha.tabela = tbl_tabela_item.tabela AND tbl_os_item.peca = tbl_tabela_item.peca
					WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
					GROUP BY tbl_os.posto
			) custo ON tbl_posto.posto = custo.posto
			ORDER BY tbl_posto.nome	";

	$sql = "
	SELECT tbl_os.posto, 
		tbl_os.os,
		tbl_os.produto
	into temp table t_1
	FROM tbl_os
	JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
	WHERE tbl_distrib_lote_os.distrib_lote =$distrib_lote;

	CREATE INDEX t_1_posto_index ON t_1(posto);
	CREATE INDEX t_1_os_index ON t_1(os);
	CREATE INDEX t_1_produto_index ON t_1(produto);

	SELECT t_1.posto, 
		tbl_produto.mao_de_obra, 
		SUM (tbl_produto.mao_de_obra) AS mobra_total, 
		COUNT (t_1.os) AS qtde_os
	INTO TEMP TABLE tmp_tab1
	FROM t_1
	JOIN tbl_produto ON tbl_produto.produto = t_1.produto 
	GROUP BY t_1.posto , tbl_produto.mao_de_obra;

	CREATE INDEX tmp_tab1_posto_index ON tmp_tab1(posto);



	SELECT t_1.posto, COUNT (t_1.os) AS med_qtde_os
	into temp table tmp_tab2
	FROM t_1
	GROUP BY t_1.posto;

	CREATE INDEX tmp_tab2_posto_index ON tmp_tab2(posto);


	SELECT t_1.posto, SUM (tbl_os_item.qtde) AS med_qtde_pecas
	into temp table tmp_tab3
	FROM t_1
	JOIN tbl_os_produto ON t_1.os = tbl_os_produto.os
	JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
	GROUP BY t_1.posto;

	CREATE INDEX tmp_tab3_posto_index ON tmp_tab3(posto);

	SELECT t_1.posto, SUM (tbl_os_item.qtde * tbl_tabela_item.preco) AS med_custo
	into temp table tmp_tab4
	FROM t_1
	JOIN tbl_os_produto ON t_1.os = tbl_os_produto.os
	JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
	JOIN tbl_produto    ON t_1.produto = tbl_produto.produto
	JOIN tbl_posto_linha ON t_1.posto = tbl_posto_linha.posto AND tbl_produto.linha = tbl_posto_linha.linha
	JOIN tbl_tabela_item ON tbl_posto_linha.tabela = tbl_tabela_item.tabela AND tbl_os_item.peca = tbl_tabela_item.peca
	GROUP BY t_1.posto;

	CREATE INDEX tmp_tab4_posto_index ON tmp_tab4(posto);



	SELECT  tbl_posto_fabrica.codigo_posto ,
			tbl_posto.nome                 ,
			qtde.med_qtde_os               ,
			media.med_qtde_pecas           ,
			custo.med_custo                ,
			lote.qtde_os                   ,
			lote.mao_de_obra               ,
			lote.mobra_total
	FROM    tbl_posto
	JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
	JOIN   tmp_tab1  as lote ON tbl_posto.posto = lote.posto
	LEFT JOIN   tmp_tab2  as qtde  ON tbl_posto.posto = qtde.posto
	LEFT JOIN   tmp_tab3  as media ON tbl_posto.posto = media.posto
	LEFT JOIN   tmp_tab4  as custo ON tbl_posto.posto = custo.posto
	ORDER BY tbl_posto.nome;
	";

	$res = pg_exec ($con,$sql);

	$sql = "SELECT LPAD (lote::text,6,'0') AS lote , TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento FROM tbl_distrib_lote WHERE distrib_lote = $distrib_lote";
	$resX = pg_exec ($con,$sql);

	echo "<center><h1>Lote " . pg_result ($resX,0,lote) . " de " . pg_result ($resX,0,fechamento) . "</h1></center>";

	echo "<table border='1' cellspacing='0' cellpadding='2'>";
	echo "<tr align='center' bgcolor='#eeeeee'>";
	echo "<td nowrap><b>Código</b></td>";
	echo "<td nowrap><b>Nome</b></td>";
	echo "<td nowrap><b>Peças</b></td>";
	echo "<td nowrap><b>Custo</b></td>";

	$sql = "SELECT DISTINCT tbl_produto.mao_de_obra
			FROM tbl_produto
			JOIN (
				SELECT tbl_os.produto FROM tbl_os JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os 
				WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
			) xprod ON tbl_produto.produto = xprod.produto ";


	$resX = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($resX) ; $i++) {
		echo "<td nowrap><b>" . number_format (pg_result ($resX,$i,mao_de_obra),2,",",".") . "</b></td>";
		$array_mo[$i][1] = pg_result ($resX,$i,mao_de_obra) ;
		$array_mo[$i][2] = 0 ;
		$array_mo[$i][3] = 0 ;
	}
	$qtde_cab = $i;

	echo "<td nowrap><b>TOTAL</b></td>";
	echo "</tr>";


	$qtde_total_os = 0 ;
	$mobra_total   = 0 ;
	$mobra_posto   = 0 ;
	$total_total   = 0 ;


	$codigo_posto_ant = pg_result ($res,0,codigo_posto);
	$nome_ant         = pg_result ($res,0,nome);
	if (pg_result ($res,0,med_qtde_os) > 0) {
		$media_pecas_ant = pg_result ($res,0,med_qtde_pecas) / pg_result ($res,0,med_qtde_os);
		$custo_ant       = pg_result ($res,0,med_custo)      / pg_result ($res,0,med_qtde_os);
	}else{
		$media_pecas_ant = 0;
		$custo_ant       = 0;
	}


	for ($i = 0 ; $i < pg_numrows ($res) +1; $i++) {
		if ($i == pg_numrows ($res) ) $codigo_posto = "*";
		
		if ($codigo_posto <> "*") {
			$codigo_posto = pg_result ($res,$i,codigo_posto);
			$nome         = pg_result ($res,$i,nome);
			if (pg_result ($res,$i,med_qtde_os) > 0) {
				$media_pecas = pg_result ($res,$i,med_qtde_pecas) / pg_result ($res,$i,med_qtde_os);
				$custo       = pg_result ($res,$i,med_custo)      / pg_result ($res,$i,med_qtde_os);
			}else{
				$media_pecas = 0;
				$custo       = 0;
			}
		}
		

		if ($codigo_posto_ant <> $codigo_posto) {
			echo "<tr style='font-size:10px'>";

			echo "<td nowrap>";
			echo $codigo_posto_ant;
			echo "</td>";

			echo "<td nowrap>";
			echo $nome_ant;
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo number_format ($media_pecas_ant,1,",",".");
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo number_format ($custo_ant,2,",",".");
			echo "</td>";

			for ($x = 0 ; $x < $qtde_cab ; $x++) {
				echo "<td align='right'>";
				$qtde_os = $array_mo [$x][2];
				if ($qtde_os > 0) {
					echo $qtde_os ;
				}else{
					echo "&nbsp;";
				}
				echo "</td>";
				
				$array_mo[$x][3] = $array_mo[$x][3] + $array_mo[$x][2];

				$array_mo[$x][2] = 0;
			}
			
			echo "<td align='right'><b>";
			echo number_format ($mobra_posto,2,",",".");
			echo "</b></td>";

			echo "<td>";
			echo "<a href=\"javascript: if (confirm('Deseja realmente excluir do lote o posto $codigo_posto_ant - $nome_ant?') == true) { window.location='$PHP_SELF?excluir=$codigo_posto_ant&distrib_lote=$distrib_lote'; } \">Excluir</A>";
			echo "</td>";


			$total_total += $mobra_posto ;
			$mobra_posto = 0 ;
			
			if ($codigo_posto == "*") break ;
			
			$codigo_posto_ant = $codigo_posto ;
			$nome_ant         = $nome ;
			$media_pecas_ant  = $media_pecas ;
			$custo_ant        = $custo ;
		}

		$mao_de_obra = pg_result ($res,$i,mao_de_obra);
		$qtde_os     = pg_result ($res,$i,qtde_os);
		
		$mobra_posto = $mobra_posto + ($qtde_os * $mao_de_obra) ;
		
		for ($x = 0 ; $x < $qtde_cab ; $x++) {
			if ($mao_de_obra == $array_mo [$x][1]) {
				$array_mo [$x][2] = $qtde_os ;
			}
		}
	}

	echo "<tr align='center' bgcolor='#eeeeee'>";
	echo "<td colspan='2'><b>Qtde Total de OS</b></td>";

	echo "<td></td>";
	echo "<td></td>";


	for ($x = 0 ; $x < $qtde_cab ; $x++) {
		echo "<td align='right'>";
		$qtde_os = $array_mo [$x][3];
		if ($qtde_os > 0) {
			echo $qtde_os ;
		}else{
			echo "&nbsp;";
		}
		echo "</td>";
	}

	echo "<td align='right'><b>" . number_format ($total_total,2,",",".") . "</b></td>";
	echo "</tr>";

	echo "</table>";
	echo "<INPUT TYPE='hidden' name='distrib_lote' value='$distrib_lote'>";
	if(strlen($fechado) == 0) echo "<p align='center'>Data Fechamento<br><INPUT TYPE='text' NAME='data_fechamento' id='data_fechamento'><br><INPUT TYPE='submit' name='fechamento' value='Fechamento'></p>";

	echo "</form>";

}

?>

<? #include "rodape.php"; ?>

</body>
</html>
