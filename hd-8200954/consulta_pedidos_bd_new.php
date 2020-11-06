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


/*
TRANPOSTADORA	CONTATO	TEL	E-MAIL
Atlas	CLAUDIO	(34) 3213-5334	COM.UB@ATLASTRANSLOG.COM.BR
Expresso Mercúrio	LAURA 	(34) 3336-4562	UA@MERCURIO.COM
Itapemirim	EUGENIO 	(34) 3336-8176	METATRANSPORTE@TERRA.COM.BR
Real Cargas / Real Encomendas	HELIO	(34) 3314-0699	HELIO.ADM@ASBCARGAS.COM.BR

*/



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

table { 
		font:0.7em Arial, Helvetica, sans-serif; 
	/*background-color:#F2F2F2; */
}
caption { 
/*	background-color:#5A666E; */
	background-color:#596D9B;
	color:#FFF; 
	text-transform:uppercase; 
	font-weight:bold; 
	font-size:1.2em; 
	border:1px solid #000;
}
thead th { 
	background-color:#F5B348; 
	color:#724809; 
	padding:2px; 
	text-transform:uppercase; 
	border-top:1px solid #F4D39E; 
	border-left:1px solid #F4D39E; 
	border-bottom:1px solid #B76E00; 
	border-right:1px solid #B76E00; 
}
tfoot th { 
	background-color:#F29601; 
	color:#724809; 
	padding:2px; 
	text-transform:uppercase; 
	font-size:1.2em; 
}
tfoot td { 
	background-color:#FC0; 
	color:#724809; 
	font-weight:bold; 
	text-transform:uppercase; 
	font-size:1.2em; 
	padding:0px 5px; 
}
.odd {  }
tbody td { 
	/* #F1F4FA" : "#F7F5F0"; */
	/*background-color:#F1F4FA; */
	color:#5A666E; 
/*	padding:2px; 
	text-align:center; 
	border-top:1px solid #FFF; 
	border-left:1px solid #FFF; 
	border-bottom:1px solid #AFB5B8; 
	border-right:1px solid #AFB5B8;  */
}
tbody th { 
/*	background-color:#5A666E; 
	color:#D7DBDD; */
;
	padding:2px; 
	text-align:center; 
	border-top:1px solid #93A1AA; 
	border-left:1px solid #93A1AA; 
	border-bottom:1px solid #2F3B42; 
	border-right:1px solid #2F3B42;
}
tbody td a {  
	color:#724809; 
	text-decoration:none; 
	font-weight:bold;
}
tbody td a:hover { 
/*	background-color:#F5B348; 
	color:#FFF;*/
}
tbody th a {
	color:#FFF; 
	text-decoration:none; 
	font-weight:bold;
}
tbody th a:hover { 
/*	color:#FC0; 
	text-decoration:underline;*/
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
		<td class="titulo_tabela" height='20px'>CONSULTA SITUAÇÃO DE PEDIDOS</td>
	</tr>
</table>
<br>

<?
if (strlen($pedido)>0){
?>
<table width="600" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td align='center' ><a href='consulta_pedidos_bd_new.php'>Clique aqui visualizar todos os pedidos</a></td>
	</tr>
</table>
<br>
<?}?>


<?
$acao="LISTAR";
if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0) {
	
	if (strlen($pedido)>0){
		$sql_adicional_pedido = "AND tbl_pendencia_bd_novo.pedido_banco=$pedido";
		$msg_filtro = " PEDIDO $pedido";
	}
		$sql = "SELECT
					tbl_pedido.pedido as pedido_id,
					tbl_pendencia_bd_novo.pedido,
					tbl_pendencia_bd_novo.referencia_peca,
					tbl_pendencia_bd_novo.pedido_blackedecker,
					to_char(tbl_pendencia_bd_novo.data,'DD/MM/YYYY') as data,
					tbl_pendencia_bd_novo.qtde_solicitada,
					tbl_pendencia_bd_novo.qtde_faturada,
					tbl_pendencia_bd_novo.qtde_pendente,
					tbl_pendencia_bd_novo.posto,
					tbl_pendencia_bd_novo.tipo
				FROM tbl_pendencia_bd_novo
				JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pendencia_bd_novo.pedido_banco
				WHERE tbl_pendencia_bd_novo.posto=$login_posto
				AND tbl_pedido.posto=$login_posto
				AND tipo='OP'
				AND tbl_pendencia_bd_novo.qtde_pendente > 0
				AND tbl_pendencia_bd_novo.qtde_faturada = 0
				$sql_adicional_pedido
				ORDER BY tbl_pendencia_bd_novo.data DESC
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {

		$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));

		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<caption>";
		echo "POSIÇÃO DOS PEDIDOS FATURADOS";
		echo "</caption>";
		echo "<thead>";
		echo "<tr  align='center' height='15'>";
		//echo "<td>PEDIDO FÁBRICA</td>";
		echo "<th scope='col'>ITEM</th>";
		echo "<th scope='col'>REF. PEDIDO</th>";
		echo "<th scope='col'>DATA PEDIDO</th>";
		echo "<th scope='col'>QTDE. SOLICITADA</th>";
		echo "<th scope='col'>QTDE. FATURADA</th>";
		echo "<th scope='col'>PENDÊNCIA</th>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido_id              = trim(pg_result($res,$i,pedido_id));
			$pedido                 = trim(pg_result($res,$i,pedido));
			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));
			$data                   = trim(pg_result($res,$i,data));
			$qtde_solicitada        = trim(pg_result($res,$i,qtde_solicitada));
			$qtde_faturada          = trim(pg_result($res,$i,qtde_faturada));
			$qtde_pendente          = trim(pg_result($res,$i,qtde_pendente));
			$tipo                   = trim(pg_result($res,$i,tipo));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			if ($pedido_blackedecker>100000){
				$pedido_blackedecker -= 100000;
			}
		
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $referencia_peca . "</td>";
//			echo "<td nowrap align='center'>" . $pedido . "</td>";
			echo "<td nowrap align='center'><a href='consulta_pedidos_bd_new.php?pedido=$pedido_id' >" . $pedido_blackedecker . "</a></td>";
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
					tbl_pendencia_bd_novo_nf.pedido,
					tbl_pendencia_bd_novo_nf.referencia_peca,
					to_char(tbl_pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data,
					tbl_pendencia_bd_novo_nf.qtde_embarcada,
					tbl_pendencia_bd_novo_nf.nota_fiscal,
					tbl_pendencia_bd_novo_nf.transportadora_nome,
					tbl_pendencia_bd_novo_nf.conhecimento
				FROM tbl_pendencia_bd_novo_nf
				JOIN tbl_posto USING(posto)
				WHERE tbl_pendencia_bd_novo_nf.posto=$login_posto
				AND tipo='OP'
				ORDER BY pedido,tbl_pendencia_bd_novo_nf.data DESC
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {

		$pedido                 = trim(pg_result($res,0,pedido));
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<caption>";
		echo "<b>EMBARQUES</b>";
		echo "<caption>";
		echo "<thead>";
		echo "<tr>";
//		echo "<th>PEDIDO FÁBRICA</th>";
		echo "<th scope='col'>ITEM</th>";
		echo "<th scope='col'>QTDE EMBARCADA</th>";
		echo "<th scope='col'>DATA</th>";
		echo "<th scope='col'>NOTA FISCAL</th>";
		echo "<th scope='col'>TRANSP / SEDEX</th>";
		echo "<th scope='col'>Nº OBJETO</th>";
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

			if (strlen($transportadora_nome)>0){
				$transportadora_nome = substr($transportadora_nome,0,28);
			}

			if (strlen($conhecimento)>0){
					$conhecimento = strtoupper($conhecimento);
					$conhecimento = str_replace("-","",$conhecimento);
					$conhecimento = "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

			}
		

			if ($qtde_pendente==0)$qtde_pendente="";

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
			echo "<tr bgcolor='$cor'>";
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

if (1==4){
?>
<br><br>
<table width="700" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td class="titulo_tabela" height='20px'>PENDENCIAS X CONSULTA DO EMBARQUE</td>
	</tr>
</table>
<?
}

////////////////////////////////////////////////////////////////////////////////////
if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0 AND 1==4) {
		$sql = "SELECT 
					tbl_pedido.pedido as pedido_id,
					tbl_pendencia_bd_novo.pedido,
					tbl_pendencia_bd_novo.referencia_peca,
					tbl_pendencia_bd_novo.pedido_blackedecker,
					to_char(tbl_pendencia_bd_novo.data,'DD/MM/YYYY') as data,
					tbl_pendencia_bd_novo.qtde_solicitada,
					tbl_pendencia_bd_novo.qtde_faturada,
					tbl_pendencia_bd_novo.qtde_pendente,
					tbl_pendencia_bd_novo.posto,
					tbl_pendencia_bd_novo.tipo,
					to_char(tbl_pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data_nf,
					tbl_pendencia_bd_novo_nf.qtde_embarcada,
					tbl_pendencia_bd_novo_nf.nota_fiscal,
					tbl_pendencia_bd_novo_nf.transportadora_nome,
					tbl_pendencia_bd_novo_nf.conhecimento
				FROM tbl_pendencia_bd_novo
				LEFT JOIN tbl_pendencia_bd_novo_nf ON tbl_pendencia_bd_novo_nf.pedido=tbl_pendencia_bd_novo.pedido 
AND upper(trim(tbl_pendencia_bd_novo_nf.referencia_peca))=upper(trim(tbl_pendencia_bd_novo.referencia_peca))
				JOIN tbl_pedido ON tbl_pedido.pedido_blackedecker = trim(substr(tbl_pendencia_bd_novo.pedido,4,length(tbl_pendencia_bd_novo.pedido)))
				WHERE tbl_pendencia_bd_novo.posto=$login_posto
				AND tbl_pendencia_bd_novo.tipo='GARANTIA'
				ORDER BY pedido_blackedecker,tbl_pendencia_bd_novo.data";

	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<caption>";
		echo "<b>PEDIDO GARANTIA (Gerados através do lançamento de ordem de serviço.)</b>";
		echo "<caption>";
		echo "<thead>";

		echo "<tr>";
		echo "<th scope='col'>ITEM</th>";
		echo "<th scope='col'>REF. PEDIDO</th>";
		echo "<th scope='col'>DATA PEDIDO</th>";
		echo "<th scope='col'>QTDE. SOLICITADA</th>";
		echo "<th scope='col'>QTDE. FATURADA</th>";
		echo "<th scope='col'>PENDÊNCIA</th>";
		//echo "<th scope='col'>QTDE EMBARCADA</th>";
		echo "<th scope='col'>DATA ENVIO</th>";
		echo "<th scope='col'>NOTA FISCAL</th>";
		echo "<th scope='col'>TRANSPORTADORA</th>";
		echo "<th scope='col'>Nr. OBJETO</th>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$pedido_id              = trim(pg_result($res,$i,pedido_id));
			

			$pedido2				= substr($pedido,3);

			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));
			$data                   = trim(pg_result($res,$i,data));
			$qtde_solicitada        = trim(pg_result($res,$i,qtde_solicitada));
			$qtde_faturada          = trim(pg_result($res,$i,qtde_faturada));
			$qtde_pendente          = trim(pg_result($res,$i,qtde_pendente));
			$tipo                   = trim(pg_result($res,$i,tipo));

			$qtde_embarcada         = trim(pg_result($res,$i,qtde_embarcada));
			$data_nf                = trim(pg_result($res,$i,data_nf));
			$nota_fiscal            = trim(pg_result($res,$i,nota_fiscal));
			$transportadora_nome    = trim(pg_result($res,$i,transportadora_nome));
			$conhecimento           = trim(pg_result($res,$i,conhecimento));

			if (strlen($transportadora_nome)>0){
				$transportadora_nome = substr($transportadora_nome,0,28);
			}
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			if ($qtde_pendente==0)$qtde_pendente="";

			if (strlen($conhecimento)>0){
					$conhecimento = strtoupper($conhecimento);
					$conhecimento = str_replace("-","",$conhecimento);
					$conhecimento = "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

			}
		
			echo "<tr  bgcolor='$cor'>";
			echo "<td nowrap>" . $referencia_peca . "</td>";
//			echo "<td nowrap align='center'>" . $pedido . "</td>";
			echo "<td nowrap align='center'><a href='pedido_finalizado.php?pedido=$pedido_id' target='_blank'>" . $pedido2 . "</a></td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $qtde_solicitada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_faturada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_pendente . "</td>";
			//echo "<td nowrap align='center'>" . $qtde_embarcada . "</td>";
			echo "<td nowrap align='center'>" . $data_nf . "</td>";
			echo "<td nowrap align='center'>" . $nota_fiscal . "</td>";
			echo "<td nowrap align='left'>" . $transportadora_nome . "</td>";
			echo "<td nowrap align='center'>" . $conhecimento . "</td>";
			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='10'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}


if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0 AND 1==4) {
		$sql = "SELECT 
					tbl_pedido.pedido as pedido_id,
					tbl_pendencia_bd_novo.pedido,
					tbl_pendencia_bd_novo.referencia_peca,
					tbl_pendencia_bd_novo.pedido_blackedecker,
					to_char(tbl_pendencia_bd_novo.data,'DD/MM/YYYY') as data,
					tbl_pendencia_bd_novo.qtde_solicitada,
					tbl_pendencia_bd_novo.qtde_faturada,
					tbl_pendencia_bd_novo.qtde_pendente,
					tbl_pendencia_bd_novo.posto,
					tbl_pendencia_bd_novo.tipo,
					to_char(tbl_pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data_nf,
					tbl_pendencia_bd_novo_nf.qtde_embarcada,
					tbl_pendencia_bd_novo_nf.nota_fiscal,
					tbl_pendencia_bd_novo_nf.transportadora_nome,
					tbl_pendencia_bd_novo_nf.conhecimento
				FROM tbl_pendencia_bd_novo
				LEFT JOIN tbl_pendencia_bd_novo_nf ON tbl_pendencia_bd_novo_nf.pedido=tbl_pendencia_bd_novo.pedido 
AND upper(trim(tbl_pendencia_bd_novo_nf.referencia_peca))=upper(trim(tbl_pendencia_bd_novo.referencia_peca))
				JOIN tbl_pedido ON tbl_pedido.pedido_blackedecker = trim(substr(tbl_pendencia_bd_novo.pedido,4,length(tbl_pendencia_bd_novo.pedido)))
				WHERE tbl_pendencia_bd_novo.posto=$login_posto
				AND tbl_pendencia_bd_novo.tipo='SEDEX'
				ORDER BY pedido_blackedecker,tbl_pendencia_bd_novo.data";

	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<caption>";
		echo "<b>PEDIDO DE SEDEX - Solicitações avulsas para o fabricante</b>";
		echo "<caption>";
		echo "<thead>";

		echo "<tr>";
		echo "<th scope='col'>ITEM</th>";
		echo "<th scope='col'>REF. PEDIDO</th>";
		echo "<th scope='col'>DATA PEDIDO</th>";
		echo "<th scope='col'>QTDE. SOLICITADA</th>";
		echo "<th scope='col'>QTDE. FATURADA</th>";
		echo "<th scope='col'>PENDÊNCIA</th>";
		//echo "<th scope='col'>QTDE EMBARCADA</th>";
		echo "<th scope='col'>DATA ENVIO</th>";
		echo "<th scope='col'>NOTA FISCAL</th>";
		echo "<th scope='col'>TRANSPORTADORA</th>";
		echo "<th scope='col'>Nr. OBJETO</th>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$pedido_id              = trim(pg_result($res,$i,pedido_id));

			$pedido2 = substr($pedido,2);

			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));
			$data                   = trim(pg_result($res,$i,data));
			$qtde_solicitada        = trim(pg_result($res,$i,qtde_solicitada));
			$qtde_faturada          = trim(pg_result($res,$i,qtde_faturada));
			$qtde_pendente          = trim(pg_result($res,$i,qtde_pendente));
			$tipo                   = trim(pg_result($res,$i,tipo));

			$qtde_embarcada         = trim(pg_result($res,$i,qtde_embarcada));
			$data_nf                = trim(pg_result($res,$i,data_nf));
			$nota_fiscal            = trim(pg_result($res,$i,nota_fiscal));
			$transportadora_nome    = trim(pg_result($res,$i,transportadora_nome));
			$conhecimento           = trim(pg_result($res,$i,conhecimento));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			if (strlen($transportadora_nome)>0){
				$transportadora_nome = substr($transportadora_nome,0,28);
			}

			if ($qtde_pendente==0)$qtde_pendente="";

			if (strlen($conhecimento)>0){
					$conhecimento = strtoupper($conhecimento);
					$conhecimento = str_replace("-","",$conhecimento);
					$conhecimento = "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

			}
				
			echo "<tr bgcolor='$cor'>";
			echo "<td nowrap>" . $referencia_peca . "</td>";
			//echo "<td nowrap align='center'>" . $pedido . "</td>";
			echo "<td nowrap align='center'><a href='pedido_finalizado.php?pedido=$pedido_id' target='_blank'>" . $pedido2 . "</a></td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $qtde_solicitada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_faturada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_pendente . "</td>";
			//echo "<td nowrap align='center'>" . $qtde_embarcada . "</td>";
			echo "<td nowrap align='center'>" . $data_nf . "</td>";
			echo "<td nowrap align='center'>" . $nota_fiscal . "</td>";
			echo "<td nowrap align='left'>" . $transportadora_nome . "</td>";
			echo "<td nowrap align='center'>" . $conhecimento . "</td>";

			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='10'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}
if (($acao == "LISTAR" || $acao == "PESQUISAR") && strlen($msg) == 0 AND 1==4) {
		$sql = "SELECT 
					tbl_pedido.pedido as pedido_id,
					tbl_pendencia_bd_novo.pedido,
					tbl_pendencia_bd_novo.referencia_peca,
					tbl_pendencia_bd_novo.pedido_blackedecker,
					to_char(tbl_pendencia_bd_novo.data,'DD/MM/YYYY') as data,
					tbl_pendencia_bd_novo.qtde_solicitada,
					tbl_pendencia_bd_novo.qtde_faturada,
					tbl_pendencia_bd_novo.qtde_pendente,
					tbl_pendencia_bd_novo.posto,
					tbl_pendencia_bd_novo.tipo,
					to_char(tbl_pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data_nf,
					tbl_pendencia_bd_novo_nf.qtde_embarcada,
					tbl_pendencia_bd_novo_nf.nota_fiscal,
					tbl_pendencia_bd_novo_nf.transportadora_nome,
					tbl_pendencia_bd_novo_nf.conhecimento
				FROM tbl_pendencia_bd_novo
				LEFT JOIN tbl_pendencia_bd_novo_nf ON tbl_pendencia_bd_novo_nf.pedido=tbl_pendencia_bd_novo.pedido 
AND upper(trim(tbl_pendencia_bd_novo_nf.referencia_peca))=upper(trim(tbl_pendencia_bd_novo.referencia_peca))
				JOIN tbl_pedido ON tbl_pedido.pedido_blackedecker = trim(substr(tbl_pendencia_bd_novo.pedido,4,length(tbl_pendencia_bd_novo.pedido)))
				WHERE tbl_pendencia_bd_novo.posto=$login_posto
				AND tbl_pendencia_bd_novo.tipo='ACESSORIO'
				ORDER BY pedido_blackedecker,tbl_pendencia_bd_novo.data
			";
	$res = pg_exec($con,$sql);
	$resultado = pg_numrows($res);
	if (pg_numrows($res) > 0) {
		echo "<br><br>";
		echo "<table width='600' align='center'  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<caption>";
		echo "<b>PEDIDO DE ACESSÓRIOS</b>";
		echo "<caption>";
		
		echo "<thead>";
		echo "<tr>";
		echo "<th scope='col'>ITEM</th>";
		echo "<th scope='col'>REF. PEDIDO</th>";
		echo "<th scope='col'>DATA PEDIDO</th>";
		echo "<th scope='col'>QTDE. SOLICITADA</th>";
		echo "<th scope='col'>QTDE. FATURADA</th>";
		echo "<th scope='col'>PENDÊNCIA</th>";
		//echo "<th scope='col'>QTDE EMBARCADA</th>";
		echo "<th scope='col'>DATA ENVIO</th>";
		echo "<th scope='col'>NOTA FISCAL</th>";
		echo "<th scope='col'>TRANSPORTADORA</th>";
		echo "<th scope='col'>Nr. OBJETO</th>";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$pedido                 = trim(pg_result($res,$i,pedido));
			$pedido_id               = trim(pg_result($res,$i,pedido_id));

			$pedido2 = substr($pedido,3);

			$referencia_peca        = trim(pg_result($res,$i,referencia_peca));
			$pedido_blackedecker    = trim(pg_result($res,$i,pedido_blackedecker));
			$data                   = trim(pg_result($res,$i,data));
			$qtde_solicitada        = trim(pg_result($res,$i,qtde_solicitada));
			$qtde_faturada          = trim(pg_result($res,$i,qtde_faturada));
			$qtde_pendente          = trim(pg_result($res,$i,qtde_pendente));
			$tipo                   = trim(pg_result($res,$i,tipo));

			$qtde_embarcada         = trim(pg_result($res,$i,qtde_embarcada));
			$data_nf                = trim(pg_result($res,$i,data_nf));
			$nota_fiscal            = trim(pg_result($res,$i,nota_fiscal));
			$transportadora_nome    = trim(pg_result($res,$i,transportadora_nome));
			$conhecimento           = trim(pg_result($res,$i,conhecimento));

			if (strlen($transportadora_nome)>0){
				$transportadora_nome = substr($transportadora_nome,0,28);
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			if ($qtde_pendente==0)$qtde_pendente="";

			if (strlen($conhecimento)>0){
					$conhecimento = strtoupper($conhecimento);
					$conhecimento = str_replace("-","",$conhecimento);
					$conhecimento = "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

			}
				
			echo "<tr class='Conteudo' align='center' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $referencia_peca . "</td>";
			//echo "<td nowrap align='center'>" . $pedido . "</td>";
			echo "<td nowrap align='center'><a href='pedido_finalizado.php?pedido=$pedido_id' target='_blank'>" . $pedido2 . "</a></td>";
			echo "<td nowrap align='center'>" . $data . "</td>";
			echo "<td nowrap align='center'>" . $qtde_solicitada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_faturada . "</td>";
			echo "<td nowrap align='center'>" . $qtde_pendente . "</td>";
			//echo "<td nowrap align='center'>" . $qtde_embarcada . "</td>";
			echo "<td nowrap align='center'>" . $data_nf . "</td>";
			echo "<td nowrap align='center'>" . $nota_fiscal . "</td>";
			echo "<td nowrap align='left'>" . $transportadora_nome . "</td>";
			echo "<td nowrap align='center'>" . $conhecimento . "</td>";

			echo "</tr>";
		}
		echo "<t/body>";
		echo "<tfoot>\n";
		echo "<tr>";
		echo "<td colspan='10'align='left'></td>";
		echo "</tr>";
		echo "</tfoot>\n";
		echo "</table>";
	}
	//echo "<h3><b><center>Resultado: $resultado registro(s).</center></b></h3>";
}

echo "<br><br>";
echo "<a href='javascript: history.back();'>VOLTAR</a>";
echo "<br>";

include "rodape.php";
?>
