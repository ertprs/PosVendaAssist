<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0


include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include 'autentica_admin.php';

include "funcoes.php";

$distrib_lote = $_POST['distrib_lote'];
if (strlen($distrib_lote) == 0) $distrib_lote = $_GET['distrib_lote'];

$excluir = $_GET['excluir'];

if (strlen ($distrib_lote) > 0) {
	$sql = "SELECT fabrica FROM tbl_distrib_lote WHERE distrib_lote = $distrib_lote";
	$res = pg_exec ($con,$sql);
	$login_fabrica = pg_result ($res,0,0);
}

if (strlen($excluir) > 0) {

	$res = pg_exec ($con,"BEGIN;");
	$sql = "DELETE FROM tbl_distrib_lote_posto
			WHERE distrib_lote = $distrib_lote
			AND posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$excluir' AND fabrica=$login_fabrica)";
	$res = pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		$sql = "DELETE FROM tbl_distrib_lote_os
				WHERE distrib_lote = $distrib_lote
				AND os IN (SELECT os FROM tbl_os WHERE posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$excluir' AND fabrica=$login_fabrica) 
				AND fabrica=$login_fabrica)";
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

$layout_menu = 'callcenter';

?>
<? include 'cabecalho.php' ?>
<body>

<script type="text/javascript" src="../javascripts/ajax_busca.js"></script>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

//FUNÇAO USADA PARA CARREGAR UMA CONTA_PAGAR DA LISTA DE PENDENTES
function retornaPosto(http) {
	var f= document.getElementById('f1');
	f.style.display='inline';
	if (http.readyState == 1) {
		f.innerHTML = "<CENTER><BR><BR><BR><BR><BR>&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' ></CENTER>";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					f.innerHTML = results[1];
				}else{
					f.innerHTML = "<h4>Ocorreu um erro</h4>"+results[1] +"teste -"+results[0] ;
				}
			}else{
				alert ('Posto nao processado');
			}
		}
	}
}

function exibirPosto() {
	var codigo_posto= document.getElementById('codigo_posto').value;
	url = "lote_conferencia_retorna_posto_ajax.php?ajax=sim&codigo_posto="+escape(codigo_posto) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPosto(http) ; } ;
	http.send(null);
}

</script>

<style type="text/css">
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
}
</style>

<?

echo "<form method='post' name='frm_lote' action='$PHP_SELF'>";

$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
		FROM tbl_distrib_lote
		WHERE  tbl_distrib_lote.fabrica = $login_fabrica 
		ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);

echo "<table class ='table_line' width='100%' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<thead>
		<tr bgcolor='#aaaadd'  background='../admin/imagens_admin/azul.gif'>
			<td > RELATÓRIO DE LOTES</td>
		</tr>
		</thead>
";
echo "<tr>";
echo "<td nowrap>Conferência por Lote<br>";

echo "<select name='distrib_lote' size='1'>\n";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>\n";
}
echo "</select>\n";
echo "<input type='submit' name='btn_acao' value='Imprimir Lote'>\n";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td nowrap>Conferência por Posto:\n";
echo "<br>";
echo "<input type='text' name='codigo_posto' id='codigo_posto' size='20' maxlength='20' value=''>\n";
echo "<INPUT TYPE='button' name='bt_posto' value='Buscar' onClick='exibirPosto();'>\n";
echo "<INPUT TYPE='button' name='bt_posto' value='Fechar' onClick=\"document.getElementById('f1').style.display='none';\">\n";
echo "</td>\n";
echo "</tr>\n";
echo "<div name='blabla' id='f1' style='padding:10px; background-color:#ffffff; filter:alpha(opacity=90); opacity: .90 border-color:#cccccc; border:1px solid #bbbbbb; display:none; width:400px; height:350px; margin-left:-100px; margin-top:30px; position:absolute;'></div>\n";
echo "</form>";

if (strlen ($distrib_lote) > 0) {

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto ,
					tbl_posto.nome                 ,
					qtde.med_qtde_os               ,
					media.med_qtde_pecas           ,
					custo.med_custo                ,
					lote.qtde_os                   ,
					lote.mao_de_obra               ,
					lote.mobra_total
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
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

	$sql= "
	
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
	JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
	JOIN   tmp_tab1  as lote ON tbl_posto.posto = lote.posto
	LEFT JOIN   tmp_tab2  as qtde  ON tbl_posto.posto = qtde.posto
	LEFT JOIN   tmp_tab3  as media ON tbl_posto.posto = media.posto
	LEFT JOIN   tmp_tab4  as custo ON tbl_posto.posto = custo.posto
	ORDER BY tbl_posto.nome;
	";
//echo "sql: $sql";exit;
//exit;
$res = pg_exec ($con,$sql);
//echo "sql: $sql";


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
			FROM (
				SELECT tbl_os.produto FROM tbl_os JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os 
				WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
			) xprod 
			JOIN tbl_produto ON tbl_produto.produto = xprod.produto ";



	$resX = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($resX) ; $i++) {
		echo "<td nowrap><b>" . number_format (pg_result ($resX,$i,mao_de_obra),2,",",".") . "</b></td>";
		$array_mo[$i][1] = pg_result ($resX,$i,mao_de_obra) ;
		$array_mo[$i][2] = 0 ;
		$array_mo[$i][3] = 0 ;
	}
	$qtde_cab = $i;

	echo "<td nowrap><b>TOTAL</b></td>";
	echo "<td nowrap>&nbsp;</td>";
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

	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";


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
	echo "<td>&nbsp;</td>";
	echo "</tr>";

	echo "</table>";

}

?>
<br><br>
</body>

</html>
<? include "rodape.php"; ?>