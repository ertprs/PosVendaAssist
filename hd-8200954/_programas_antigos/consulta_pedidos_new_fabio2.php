<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

$msg = "";

if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);

if (strlen($_GET["pedido"]) > 0) $pedido = $_GET["pedido"];

if (strlen($_GET["nota"]) > 0) $nota = $_GET["nota"];

$layout_menu = "pedido";
$title = "Consulta NF´s emitidas pelo Fabricante";
include "cabecalho.php";

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if (strlen(trim($_GET["numero_pedido"])) > 0) $numero_pedido = trim($_GET["numero_pedido"]);
	if (strlen(trim($_GET["numero_nf"])) > 0)     $numero_nf = trim($_GET["numero_nf"]);

	if (strlen($numero_pedido) == 0 && strlen($numero_nf) == 0) {
		$msg .= "Preencha um campo para realizar a pesquisa. ";
	}
}
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.titulo_tabela {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 16px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}


</style>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="600" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td class="error"><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>



<table width="700" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td class="titulo_tabela" height='20px'>CONSULTA DA PENDÊNCIA</td>
	</tr>
</table>
<br>

<?
$acao="LISTAR";
if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {

				
		$sql = "SELECT 
					pendencia_bd_novo.pedido,
					pendencia_bd_novo.referencia_peca,
					pendencia_bd_novo.pedido_blackedecker,
					to_char(pendencia_bd_novo.data,'DD/MM/YYYY') as data,
					pendencia_bd_novo.qtde_solicitada,
					pendencia_bd_novo.qtde_faturada,
					pendencia_bd_novo.qtde_pendente,
					pendencia_bd_novo.posto,
					pendencia_bd_novo.tipo
				FROM pendencia_bd_novo
				JOIN tbl_posto USING(posto)
				WHERE pendencia_bd_novo.posto=$login_posto
				AND pendencia_bd_novo.qtde_pendente>0
				AND tipo='OP'
				ORDER BY pedido_blackedecker,pendencia_bd_novo.data
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {

		$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));

		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<thead>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='6'align='left' style='font-size:14px'><b>NÚMERO DO PEDIDO FATURADO NA FÁBRICA:  $pedido_blackedecker</b></td>";
		echo "</tr>";
		echo "<tr class='Titulo' align='center' height='15'>";
		//echo "<td>PEDIDO FÁBRICA</td>";
		echo "<td>ITEM</td>";
		echo "<td>REF. PEDIDO</td>";
		echo "<td>DATA PEDIDO</td>";
		echo "<td>QTDE. SOLICITADA</td>";
		echo "<td>QTDE. FATURADA</td>";
		echo "<td>PENDÊNCIA</td>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));
			$data                   = trim(pg_result($res,$i,data));
			$qtde_solicitada        = trim(pg_result($res,$i,qtde_solicitada));
			$qtde_faturada          = trim(pg_result($res,$i,qtde_faturada));
			$qtde_pendente          = trim(pg_result($res,$i,qtde_pendente));
			$tipo                   = trim(pg_result($res,$i,tipo));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
			//echo "<td nowrap align='center'><a href='$PHP_SELF?pedido=$pedido'>" . $pedido_blackedecker . "</a></td>";
			echo "<td nowrap>" . $referencia_peca . "</td>";
			echo "<td nowrap align='center'>" . $pedido . "</td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $qtde_solicitada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_faturada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_pendente . "</td>";
			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='6'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {
		$sql = "SELECT 
					pendencia_bd_novo.pedido,
					pendencia_bd_novo.referencia_peca,
					pendencia_bd_novo.pedido_blackedecker,
					to_char(pendencia_bd_novo.data,'DD/MM/YYYY') as data,
					pendencia_bd_novo.qtde_solicitada,
					pendencia_bd_novo.qtde_faturada,
					pendencia_bd_novo.qtde_pendente,
					pendencia_bd_novo.posto,
					pendencia_bd_novo.tipo
				FROM pendencia_bd_novo
				JOIN tbl_posto USING(posto)
				WHERE pendencia_bd_novo.posto=$login_posto
				AND pendencia_bd_novo.qtde_pendente>0
				AND tipo='GARANTIA'
				ORDER BY pedido_blackedecker,pendencia_bd_novo.data
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<thead>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='6'align='left' style='font-size:14px'><b>PEDIDO GARANTIA (Gerados através do lançamento de ordem de serviço.)</b></td>";
		echo "</tr>";
		echo "<tr class='Titulo' align='center' height='15'>";
		echo "<td>ITEM</td>";
		echo "<td>REF. PEDIDO</td>";
		echo "<td>DATA PEDIDO</td>";
		echo "<td>QTDE. SOLICITADA</td>";
		echo "<td>QTDE. FATURADA</td>";
		echo "<td>PENDÊNCIA</td>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));
			$data                   = trim(pg_result($res,$i,data));
			$qtde_solicitada        = trim(pg_result($res,$i,qtde_solicitada));
			$qtde_faturada          = trim(pg_result($res,$i,qtde_faturada));
			$qtde_pendente          = trim(pg_result($res,$i,qtde_pendente));
			$tipo                   = trim(pg_result($res,$i,tipo));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $referencia_peca . "</td>";
			echo "<td nowrap align='center'>" . $pedido . "</td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $qtde_solicitada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_faturada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_pendente . "</td>";
			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='6'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}


if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {
		$sql = "SELECT 
					pendencia_bd_novo.pedido,
					pendencia_bd_novo.referencia_peca,
					pendencia_bd_novo.pedido_blackedecker,
					to_char(pendencia_bd_novo.data,'DD/MM/YYYY') as data,
					pendencia_bd_novo.qtde_solicitada,
					pendencia_bd_novo.qtde_faturada,
					pendencia_bd_novo.qtde_pendente,
					pendencia_bd_novo.posto,
					pendencia_bd_novo.tipo
				FROM pendencia_bd_novo
				JOIN tbl_posto USING(posto)
				WHERE pendencia_bd_novo.posto=$login_posto
				AND tipo='SEDEX'
				ORDER BY pedido_blackedecker,pendencia_bd_novo.data
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<thead>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='6'align='left' style='font-size:14px'><b>PEDIDO DE SEDEX - Solicitações avulsas para o fabricante.</td>";
		echo "</tr>";
		echo "<tr class='Titulo' align='center' height='15'>";
		echo "<td>ITEM</td>";
		echo "<td>REF. PEDIDO</td>";
		echo "<td>DATA PEDIDO</td>";
		echo "<td>QTDE. SOLICITADA</td>";
		echo "<td>QTDE. FATURADA</td>";
		echo "<td>PENDÊNCIA</td>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));
			$data                   = trim(pg_result($res,$i,data));
			$qtde_solicitada        = trim(pg_result($res,$i,qtde_solicitada));
			$qtde_faturada          = trim(pg_result($res,$i,qtde_faturada));
			$qtde_pendente          = trim(pg_result($res,$i,qtde_pendente));
			$tipo                   = trim(pg_result($res,$i,tipo));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $referencia_peca . "</td>";
			echo "<td nowrap align='center'>" . $pedido . "</td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $qtde_solicitada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_faturada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_pendente . "</td>";
			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='6'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}
if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {
		$sql = "SELECT 
					pendencia_bd_novo.pedido,
					pendencia_bd_novo.referencia_peca,
					pendencia_bd_novo.pedido_blackedecker,
					to_char(pendencia_bd_novo.data,'DD/MM/YYYY') as data,
					pendencia_bd_novo.qtde_solicitada,
					pendencia_bd_novo.qtde_faturada,
					pendencia_bd_novo.qtde_pendente,
					pendencia_bd_novo.posto,
					pendencia_bd_novo.tipo
				FROM pendencia_bd_novo
				JOIN tbl_posto USING(posto)
				WHERE pendencia_bd_novo.posto=$login_posto
				AND tipo='ACESSORIO'
				ORDER BY pedido_blackedecker,pendencia_bd_novo.data
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<thead>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='6'align='left' style='font-size:14px'><b>PEDIDO DE ACESSÓRIOS</b></td>";
		echo "</tr>";
		echo "<tr class='Titulo' align='center' height='15'>";
		echo "<td>ITEM</td>";
		echo "<td>REF. PEDIDO</td>";
		echo "<td>DATA PEDIDO</td>";
		echo "<td>QTDE. SOLICITADA</td>";
		echo "<td>QTDE. FATURADA</td>";
		echo "<td>PENDÊNCIA</td>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));
			$data                   = trim(pg_result($res,$i,data));
			$qtde_solicitada        = trim(pg_result($res,$i,qtde_solicitada));
			$qtde_faturada          = trim(pg_result($res,$i,qtde_faturada));
			$qtde_pendente          = trim(pg_result($res,$i,qtde_pendente));
			$tipo                   = trim(pg_result($res,$i,tipo));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $referencia_peca . "</td>";
			echo "<td nowrap align='center'>" . $pedido . "</td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $qtde_solicitada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_faturada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_pendente . "</td>";
			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='6'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}


?>
<br><br>
<table width="700" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td class="titulo_tabela" height='20px'>CONSULTA DO EMBARQUE</td>
	</tr>
</table>
<?

if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {
		$sql = "SELECT 
					pendencia_bd_novo_nf.pedido,
					pendencia_bd_novo_nf.referencia_peca,
					to_char(pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data,
					pendencia_bd_novo_nf.qtde_embarcada,
					pendencia_bd_novo_nf.nota_fiscal,
					pendencia_bd_novo_nf.transportadora_nome,
					pendencia_bd_novo_nf.conhecimento
				FROM pendencia_bd_novo_nf
				JOIN tbl_posto USING(posto)
				WHERE pendencia_bd_novo_nf.posto=$login_posto
				AND tipo='OP'
				ORDER BY pedido,pendencia_bd_novo_nf.data
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {

		$pedido                 = trim(pg_result($res,0,pedido));
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<thead>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='6'align='left' style='font-size:14px'><b>PEDIDO NA FÁBRICA: $pedido</b></td>";
		echo "</tr>";
		echo "<tr class='Titulo' align='center' height='15'>";
//		echo "<td>PEDIDO FÁBRICA</td>";
		echo "<td>ITEM</td>";
		echo "<td>QTDE EMBARCADA</td>";
		echo "<td>DATA</td>";
		echo "<td>NOTA FISCAL</td>";
		echo "<td>TRANSP / SEDEX</td>";
		echo "<td>Nº OBJETO</td>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$qtde_embarcada         = trim(pg_result($res,$i,qtde_embarcada));
			$data                   = trim(pg_result($res,$i,data));
			$nota_fiscal            = trim(pg_result($res,$i,nota_fiscal));
			$transportadora_nome    = trim(pg_result($res,$i,transportadora_nome));
			$conhecimento           = trim(pg_result($res,$i,conhecimento));

			$conhecimento = strtoupper($conhecimento);

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
//			echo "<td nowrap align='center'>" . $pedido . "</td>";
			echo "<td nowrap align='left'>" . $referencia_peca . "</td>";
			echo "<td nowrap align='center'>" . $qtde_embarcada . "</td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $nota_fiscal . "</td>";
			echo "<td nowrap align='left'>" . $transportadora_nome . "</td>";
			echo "<td nowrap align='center'>" . $conhecimento . "</td>";
			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='6'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {
		$sql = "SELECT 
					pendencia_bd_novo_nf.pedido,
					pendencia_bd_novo_nf.referencia_peca,
					to_char(pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data,
					pendencia_bd_novo_nf.qtde_embarcada,
					pendencia_bd_novo_nf.nota_fiscal,
					pendencia_bd_novo_nf.transportadora_nome,
					pendencia_bd_novo_nf.conhecimento
				FROM pendencia_bd_novo_nf
				JOIN tbl_posto USING(posto)
				WHERE pendencia_bd_novo_nf.posto=$login_posto
				AND tipo='GARANTIA'
				ORDER BY pedido,pendencia_bd_novo_nf.data
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<thead>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='7'align='left' style='font-size:14px'><b>PEDIDO DE GARANTIA</b></td>";
		echo "</tr>";
		echo "<tr class='Titulo' align='center' height='15'>";
		echo "<td>ITEM</td>";
		echo "<td>QTDE EMBARCADA</td>";
		echo "<td>DATA</td>";
		echo "<td>NOTA FISCAL</td>";
		echo "<td>TRANSP / SEDEX</td>";
		echo "<td>Nº OBJETO</td>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$qtde_embarcada         = trim(pg_result($res,$i,qtde_embarcada));
			$data                   = trim(pg_result($res,$i,data));
			$nota_fiscal            = trim(pg_result($res,$i,nota_fiscal));
			$transportadora_nome    = trim(pg_result($res,$i,transportadora_nome));
			$conhecimento           = trim(pg_result($res,$i,conhecimento));

			$conhecimento = strtoupper($conhecimento);

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='left'>" . $referencia_peca . "</td>";
			echo "<td nowrap align='center'>" . $qtde_embarcada . "</td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $nota_fiscal . "</td>";
			echo "<td nowrap align='left'>" . $transportadora_nome . "</td>";
			echo "<td nowrap align='center'>" . $conhecimento . "</td>";
			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='6'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {
		$sql = "SELECT 
					pendencia_bd_novo_nf.pedido,
					pendencia_bd_novo_nf.referencia_peca,
					to_char(pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data,
					pendencia_bd_novo_nf.qtde_embarcada,
					pendencia_bd_novo_nf.nota_fiscal,
					pendencia_bd_novo_nf.transportadora_nome,
					pendencia_bd_novo_nf.conhecimento
				FROM pendencia_bd_novo_nf
				JOIN tbl_posto USING(posto)
				WHERE pendencia_bd_novo_nf.posto=$login_posto
				AND tipo='SEDEX'
				ORDER BY pedido,pendencia_bd_novo_nf.data
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<thead>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='7'align='left' style='font-size:14px'><b>PEDIDO DE SEDEX</b></td>";
		echo "</tr>";
		echo "<tr class='Titulo' align='center' height='15'>";
		echo "<td>ITEM</td>";
		echo "<td>QTDE EMBARCADA</td>";
		echo "<td>DATA</td>";
		echo "<td>NOTA FISCAL</td>";
		echo "<td>TRANSP / SEDEX</td>";
		echo "<td>Nº OBJETO</td>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$qtde_embarcada         = trim(pg_result($res,$i,qtde_embarcada));
			$data                   = trim(pg_result($res,$i,data));
			$nota_fiscal            = trim(pg_result($res,$i,nota_fiscal));
			$transportadora_nome    = trim(pg_result($res,$i,transportadora_nome));
			$conhecimento           = trim(pg_result($res,$i,conhecimento));

			$conhecimento = strtoupper($conhecimento);

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='left'>" . $referencia_peca . "</td>";
			echo "<td nowrap align='center'>" . $qtde_embarcada . "</td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $nota_fiscal . "</td>";
			echo "<td nowrap align='left'>" . $transportadora_nome . "</td>";
			echo "<td nowrap align='center'>" . $conhecimento . "</td>";
			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='6'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {
		$sql = "SELECT 
					pendencia_bd_novo_nf.pedido,
					pendencia_bd_novo_nf.referencia_peca,
					to_char(pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data,
					pendencia_bd_novo_nf.qtde_embarcada,
					pendencia_bd_novo_nf.nota_fiscal,
					pendencia_bd_novo_nf.transportadora_nome,
					pendencia_bd_novo_nf.conhecimento
				FROM pendencia_bd_novo_nf
				JOIN tbl_posto USING(posto)
				WHERE pendencia_bd_novo_nf.posto=$login_posto
				AND tipo='ACESSORIO'
				ORDER BY pedido,pendencia_bd_novo_nf.data
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<thead>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='7'align='left' style='font-size:14px'><b>PEDIDO DE ACESSÓRIO</b></td>";
		echo "</tr>";
		echo "<tr class='Titulo' align='center' height='15'>";
		echo "<td>ITEM</td>";
		echo "<td>QTDE EMBARCADA</td>";
		echo "<td>DATA</td>";
		echo "<td>NOTA FISCAL</td>";
		echo "<td>TRANSP / SEDEX</td>";
		echo "<td>Nº OBJETO</td>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$qtde_embarcada         = trim(pg_result($res,$i,qtde_embarcada));
			$data                   = trim(pg_result($res,$i,data));
			$nota_fiscal            = trim(pg_result($res,$i,nota_fiscal));
			$transportadora_nome    = trim(pg_result($res,$i,transportadora_nome));
			$conhecimento           = trim(pg_result($res,$i,conhecimento));

			$conhecimento = strtoupper($conhecimento);

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='left'>" . $referencia_peca . "</td>";
			echo "<td nowrap align='center'>" . $qtde_embarcada . "</td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $nota_fiscal . "</td>";
			echo "<td nowrap align='left'>" . $transportadora_nome . "</td>";
			echo "<td nowrap align='center'>" . $conhecimento . "</td>";
			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='6'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

$pedido="";
if (strlen($pedido) > 0 && strlen($acao) == 0) {
	$sql =	"SELECT DISTINCT tbl_faturamento.pedido                                                     ,
					tbl_faturamento.pedido_fabricante                                          ,
					tbl_faturamento.nota_fiscal                                                ,
					TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY')          AS emissao          ,
					TO_CHAR(tbl_faturamento.previsao_chegada,'DD/MM/YYYY') AS previsao_chegada ,
					tbl_faturamento.total_nota                                                 ,
					tbl_faturamento.transp                                 AS transportadora
			FROM      tbl_faturamento
			LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_faturamento.transportadora
			WHERE tbl_faturamento.posto   = $login_posto
			AND   tbl_faturamento.fabrica = $login_fabrica
			AND   tbl_faturamento.pedido  = $pedido
			AND   tbl_faturamento.pedido NOTNULL;";
	$res = pg_exec($con,$sql);
	
	
	$resultado = pg_numrows($res);
	
	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido            = trim(pg_result($res,$i,pedido));
			$pedido_fabricante = trim(pg_result($res,$i,pedido_fabricante));
			$nota_fiscal       = trim(pg_result($res,$i,nota_fiscal));
			$emissao           = trim(pg_result($res,$i,emissao));
			$total_nota        = trim(pg_result($res,$i,total_nota));
			$previsao_chegada  = trim(pg_result($res,$i,previsao_chegada));
			$transportadora    = trim(pg_result($res,$i,transportadora));
			
			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td colspan='5'>DESDOBRAMENTO DO PEDIDO $pedido_fabricante</td>";
				echo "</tr>";
				echo "<tr class='Titulo' height='15'>";
				echo "<td>Nº NOTA</td>";
				echo "<td>EMISSÃO</td>";
				echo "<td>VALOR</td>";
				echo "<td>PREVISÃO</td>";
				echo "<td>TRANSPORTADORA</td>";
				echo "</tr>";
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='center'><a href='$PHP_SELF?pedido=$pedido&nota=$nota_fiscal'>" . $nota_fiscal . "</a></td>";
			echo "<td nowrap align='center'>" . $emissao . "</td>";
			echo "<td nowrap align='right'>R$ " . number_format($total_nota,2,",",".") . "</td>";
			echo "<td nowrap align='center'>" . $previsao_chegada . "</td>";
			echo "<td nowrap>" . $transportadora . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

if (strlen($pedido) > 0 && strlen($nota) > 0 && strlen($acao) == 0) {
	$sql =	"SELECT DISTINCT tbl_peca.referencia       AS peca_referencia ,
					tbl_peca.descricao        AS peca_descricao  ,
					tbl_peca.ipi              AS peca_ipi        ,
					tbl_faturamento_item.qtde AS peca_qtde       ,
					tbl_faturamento_item.preco
			FROM tbl_faturamento
			JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
									 AND tbl_faturamento_item.pedido      = tbl_faturamento.pedido
			JOIN tbl_peca             ON tbl_peca.peca                    = tbl_faturamento_item.peca
			WHERE tbl_faturamento.posto       = $login_posto
			AND   tbl_faturamento.fabrica     = $login_fabrica
			AND   tbl_faturamento.pedido      = $pedido
			AND   tbl_faturamento.nota_fiscal = $nota
			AND   tbl_faturamento.pedido NOTNULL;";
	$res = pg_exec($con,$sql);

#	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);
	
	$resultado = pg_numrows($res);
	
	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$peca_referencia = trim(pg_result($res,$i,peca_referencia));
			$peca_descricao  = trim(pg_result($res,$i,peca_descricao));
			$peca_qtde       = trim(pg_result($res,$i,peca_qtde));
			$peca_ipi        = trim(pg_result($res,$i,peca_ipi));
			$preco           = trim(pg_result($res,$i,preco));

			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td colspan='7'>DESDOBRAMENTO DA NOTA FISCAL $nota</td>";
				echo "</tr>";
				echo "<tr class='Titulo' height='15'>";
				echo "<td>PEÇA REFERÊNCIA</td>";
				echo "<td>PEÇA DESCRIÇÃO</td>";
				echo "<td>QTDE</td>";
				echo "<td>IPI</td>";
				echo "<td>UNITÁRIO</td>";
				echo "<td NOWRAP>TOTAL COM IPI</td>";
				echo "</tr>";
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap align='center'>" . $peca_referencia . "</td>";
			echo "<td nowrap> " . $peca_descricao . " </td>";
			echo "<td nowrap align='center'>" . $peca_qtde . "</td>";
			echo "<td nowrap align='center'>" . $peca_ipi . "</td>";
			echo "<td nowrap align='center'>" . number_format ($preco,2,",",".") . "</td>";
			
			if (strlen ($peca_ipi) == 0) $peca_ipi = 0 ;
			$total = $preco * (1 + ($peca_ipi / 100) ) * $peca_qtde ;
			echo "<td nowrap align='center'>" . number_format ($total,2,",",".") . "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}
	echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}
echo "<br><br>";
echo "<a href='javascript: history.back();'>VOLTAR</a>";
echo "<br>";

include "rodape.php";
?>
