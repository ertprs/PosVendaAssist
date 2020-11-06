<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";

$msg_erro="";
$msg="";

$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$ok_aceito="nao";

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0){
	$extrato = trim($_POST['extrato']);
}

$posto = trim($_GET['posto']);
if (strlen($posto)==0){
	$posto = trim($_POST['posto']);
}

$btn_acao = trim($_GET['btn_acao']);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST['btn_acao']);
}

$sql = "SELECT posto
		FROM tbl_extrato
		WHERE extrato = $extrato
		AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res)==0){
	$msg_erro .= "Nenhum posto encontrado para este extrato!";
}else{
	$posto = pg_result ($res,0,posto);
}

$login_posto = $posto;

$postos_permitidos = array(0 => 'LIXO', 1 => '1537', 2 => '1773', 3 => '7080', 4 => '5037', 5 => '13951', 6 => '4311', 7 => '564', 8 => '1623', 9 => '1664',10 => '595',11 => '2506', 12 => '6458', 13 => '1511', 14 => '1870', 15 => '1266', 16 => '6591', 17 => '5496', 18 => '14296', 19 => '6140', 20 => '1161',21 => '708', 22 => '710', 23 => '14119', 24 => '898', 25 => '6379', 26 => '5024', 27 => '388', 28 => '2508', 29 => '1172', 30 => '1261', 31 => '19724', 32 => '1523', 33 => '1567', 34 => '1581', 35 => '1713', 36 => '1740', 37 => '1752', 38 => '1754', 39 => '1766', 40 => '115', 41 => '1799', 42 => '1806', 43 => '1814', 44 => '1891', 45 => '6432', 46 => '6916', 47 => '6917', 48 => '7245', 49 => '7256', 50 => '13850', 51 => '4044', 52 => '14182', 53 => '14297', 54 => '14282', 55 => '14260', 56 => '18941', 57 => '18967', 58 => '1962', 59 => '5419');


if ($extrato < 185731){# liberado para toda a rede Solicitado por Sergio Mauricio 31/08/2007 - Fabio
	if (array_search($login_posto, $postos_permitidos)==0){ //verifica se o posto tem permissao
		header("Location: manutencao_logistica_reversa.php");
		exit();
	}
}

if (strlen($extrato)==0){
	header("Location: manutencao_logistica_reversa.php");
}


if ($btn_acao=="cancelar_notas" AND strlen($extrato)>0){

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT count(*) as qtde
			FROM tbl_faturamento
			WHERE extrato_devolucao = $extrato
			AND distribuidor = $posto
			AND posto IN (13996,4311)
			AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$qtde_notas = pg_result ($res,0,qtde);

	$sql = "SELECT count(*) as qtde_digitadas
			FROM tbl_faturamento
			WHERE extrato_devolucao = $extrato
			AND distribuidor = $posto
			AND posto IN (13996,4311)
			AND fabrica = $login_fabrica
			AND emissao = CURRENT_DATE";
	$res = pg_exec ($con,$sql);
	$qtde_digitadas = pg_result ($res,0,qtde_digitadas);

	if ($qtde_digitadas == 0){
		$msg_erro .= "Só é permitido cancelar notas digitadas hoje!";
	}else{
		if ($qtde_digitadas <> $qtde_notas){
			$msg_erro .= "Não é possível cancelar notas de pendência!";
		}
	}

	$sql = "SELECT posto
			FROM tbl_extrato
			WHERE extrato = $extrato
			AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res)==0){
		$msg_erro .= "Nenhum posto encontrado para este extrato!";
	}else{
		$posto = pg_result ($res,0,posto);
	}

	if (strlen($msg_erro)==0){
		$sql = "SELECT faturamento
				FROM tbl_faturamento
				WHERE extrato_devolucao = $extrato
				AND distribuidor = $posto
				AND posto IN (13996,4311)";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)==0){
			$msg_erro .= "Nenhum posto encontrado para este extrato!";
		}
	}

	if (strlen($msg_erro)==0){
		$sql = "UPDATE tbl_faturamento
				SET cancelada = current_timestamp,
					baixa = current_date
				WHERE extrato_devolucao = $extrato
				AND distribuidor = $posto
				AND posto IN (13996,4311)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro)==0){
		$sql = "INSERT INTO tbl_lgr_cancelado
				(posto,nota_fiscal,data_cancelamento,usuario,fabrica,foi_cancelado,data_nf)
				SELECT distribuidor,nota_fiscal,current_date,$login_admin,fabrica,'t',emissao
				FROM tbl_faturamento
				WHERE extrato_devolucao = $extrato
				AND cancelada IS NOT NULL
				AND distribuidor = $posto
				AND posto IN (13996,4311)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro)==0){
		$sql = "UPDATE tbl_extrato_lgr
				SET qtde_nf = NULL
				WHERE extrato = $extrato
				AND posto = $posto";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Notas canceladas com sucesso!";
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "financeiro";
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

</style>

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
$data = pg_result ($res,0,data);
$periodo = pg_result ($res,0,periodo);
$nome = pg_result ($res,0,nome);
$codigo = pg_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='-1' face='arial'>$codigo - $nome</font>";

?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center' width='100%'><a href='manutencao_logistica_reversa.php'>Ver outro extrato</a></td>
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

<? if (strlen($msg) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="menu_top">
	<tr>
		<td><?echo $msg ?></td>
	</tr>
</table>
<? } ?>

<center>

<!--
<br>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="10" class="menu_top" ><div align="center" style='font-size:16px'><b>ATENÇÃO</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
	As peças ou produtos não devolvidos neste extrato serão apresentadas na tela de <a href='<? echo "$PHP_SELF?extrato=$extrato&pendentes=sim" ?>' target='_blank'>consulta de pendências</a>. Caso não sejam efetivadas as devoluções, os itens serão cobrados do posto autorizado.
<br><br>
<b style='font-size:14px;font-weight:normal'>Emitir as NF de devolução nos mesmos valores e impostos, referenciando NF de origem Britânia, e postagem da NF para Britânia Joinville-SC</b>
	</TD>
</TR>
</table>
-->
<?

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
			$nf_cancelada = pg_result ($res_nf_canceladas,$i,nota_fiscal);
			$data_nf      = pg_result ($res_nf_canceladas,$i,data_nf);
			
			$sql2="SELECT faturamento
					FROM tbl_faturamento
					WHERE fabrica             = $login_fabrica
					AND distribuidor           = $login_posto
					AND extrato_devolucao      = $extrato
					AND posto                  = 13996
					AND LPAD(nota_fiscal::text,7,'0')  = LPAD(trim('$nf_cancelada'),7,'0')
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
			echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>As notas:</b><br>".implode(",<br>",$array_nf_canceladas)." <br>foram <b>canceladas</b> e deverão ser preenchidas novamente! <br></h3>";
		}else{
			echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>A nota</b> ".implode(", ",$array_nf_canceladas)." foi <b>cancelada</b> e deverá ser preenchida novamente! <br></h3>";
		}
	}

?>

<form name='frm_nota_fiscal' method='POST' action='<? echo $PHP_SELF ?>?'>
<input type='hidden' name='btn_acao' value='cancelar_notas'>
<input type='hidden' name='extrato' value='<? echo $extrato; ?>'>
<input type='hidden' name='posto' value='<? echo $posto; ?>'>
<? 

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

$sql = "SELECT  faturamento,
		extrato_devolucao,
		nota_fiscal,
		distribuidor,
		posto
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

	$contador=0;
	for ($i=0; $i < $qtde_for; $i++) {
		
		$contador++;
		$faturamento_nota    = trim (pg_result ($res,$i,faturamento));
		$distribuidor        = trim (pg_result ($res,$i,distribuidor));
		$posto               = trim (pg_result ($res,$i,posto));
		$nota_fiscal         = trim (pg_result ($res,$i,nota_fiscal));
		$extrato_devolucao	 = trim (pg_result ($res,$i,extrato_devolucao));
		$distribuidor        = "";
		$produto_acabado     = "";

		$sql_topo = "SELECT  
					tbl_faturamento_item.devolucao_obrigatoria_lgr
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				JOIN tbl_peca USING(peca)
				WHERE tbl_faturamento.posto           = $posto
				AND tbl_faturamento.distribuidor      = $login_posto
				AND tbl_faturamento.fabrica           = $login_fabrica
				AND tbl_faturamento.extrato_devolucao = $extrato_devolucao
				AND tbl_faturamento.faturamento       = $faturamento_nota 
				LIMIT 1";
		$res_topo = pg_exec ($con,$sql_topo);
		//$produto_acabado = pg_result ($res_topo,0,produto_acabado);
		$devolucao_obrigatoria_lgr = pg_result ($res_topo,0,devolucao_obrigatoria_lgr);

		$pecas_produtos = "PEÇAS";
		$devolucao = " RETORNO OBRIGATÓRIO ";

		if ($posto=='4311'){
			$posto_desc = "Devolução para a TELECONTROL - ";
		}else{
			$posto_desc="";
		}

		if ( $devolucao_obrigatoria_lgr<>2 and $devolucao_obrigatoria_lgr<>3) $devolucao = " NÃO RETORNÁVEIS ";		
		if ($devolucao_obrigatoria_lgr<>2 and $devolucao_obrigatoria_lgr<>3) $pecas_produtos = "$posto_desc PEÇAS";

		if ($devolucao_obrigatoria_lgr==1 ){
			$pecas_produtos = "$posto_desc PRODUTOS";
			 $devolucao = " RETORNO OBRIGATÓRIO ";
		}

		if ($posto=='13996'){ #BRITANIA
				$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
				$endereco = "Rua Dona Francisca, 8300 - Mod.4 e 5 - Bloco A";
				$cidade   = "Joinville";
				$estado   = "SC";
				$cep      = "89239270";
				$fone     = "(41) 2102-7700";
				$cnpj     = "76492701000742";
				$ie       = "254.861.652";
		}
		if ($posto=='4311'){ #TELECONTROL
				$razao    = "TELECONTROL NETWORKING LTDA";
				$endereco = "AV. CARLOS ARTENCIO 420 ";
				$cidade   = "Marília";
				$estado   = "SP";
				$cep      = "17.519-255 ";
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
		$cabecalho .= "<td>Natureza <br> <b>Devolução de Garantia</b> </td>\n";
		$cabecalho .= "<td>CFOP <br> <b>$cfop</b> </td>\n";
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

		$topo .=  "</tr>\n";
		$topo .=  "</thead>\n";

		$sql = "SELECT  
				tbl_peca.peca, 
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.ipi, 
				tbl_faturamento_item.devolucao_obrigatoria_lgr,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.preco,
				SUM (tbl_faturamento_item.qtde) as qtde,
				SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco) as total,
				SUM (tbl_faturamento_item.base_icms) AS base_icms, 
				SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
				SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
				SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
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
					tbl_faturamento_item.devolucao_obrigatoria_lgr,
					tbl_peca.ipi,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco
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
		$total_nota       = 0;
		$aliq_final       = 0;

		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
			
			$peca                = pg_result ($resX,$x,peca);
			$peca_referencia     = pg_result ($resX,$x,referencia);
			$peca_descricao      = pg_result ($resX,$x,descricao);
			$ipi                 = pg_result ($resX,$x,ipi);
			$devolucao_obrigatoria_lgr = pg_result ($resX,$x,devolucao_obrigatoria_lgr);
			$aliq_icms           = pg_result ($resX,$x,aliq_icms);
			$aliq_ipi            = pg_result ($resX,$x,aliq_ipi);
			$peca_preco          = pg_result ($resX,$x,preco);

			$base_icms           = pg_result ($resX,$x,base_icms);
			$valor_icms          = pg_result ($resX,$x,valor_icms);
			$base_ipi            = pg_result ($resX,$x,base_ipi);
			$valor_ipi           = pg_result ($resX,$x,valor_ipi);

			$total               = pg_result ($resX,$x,total);
			$qtde                = pg_result ($resX,$x,qtde);

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
				array_push($notas_fiscais,pg_result ($resNF,$y,nota_fiscal_origem));
			}
			$notas_fiscais = array_unique($notas_fiscais);
			asort($notas_fiscais);

			if ($qtde==0)
				$peca_preco       =  $peca_preco;
			else
				$peca_preco       =  $total / $qtde;
			
			$total_item  = $peca_preco * $qtde;

//			$nota_fiscal_item = pg_result ($resX,$x,nota_fiscal);
//			$faturamento = pg_result ($resX,$x,faturamento);

			if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

			if ($aliq_icms==0){
				$base_icms=0;
				$valor_icms=0;
			}else{
				$base_icms  = $total_item;
				$valor_icms = $total_item * $aliq_icms / 100;
			}

			if (strlen($aliq_ipi)==0) $aliq_ipi=0;

			if ($aliq_ipi==0) 	{
				$base_ipi=0;
				$valor_ipi=0;
			}
			else {
				$base_ipi=$total_item;
				$valor_ipi = $total_item*$aliq_ipi/100;
			}

//			if ($base_icms > $total_item) $base_icms = $total_item;
//			if ($aliq_final == 0) $aliq_final = $aliq_icms;
//			if ($aliq_final <> $aliq_icms) $aliq_final = -1;

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi   += $base_ipi;
			$total_valor_ipi  += $valor_ipi;
			$total_nota       += $total_item;

			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
			echo "<td align='left'>";
			echo "$peca_referencia";
			echo "</td>\n";
			echo "<td align='left'>$peca_descricao</td>\n";

			echo "<td align='center'>$qtde</td>\n";
			echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
			echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
			echo "<td align='right'>$aliq_icms</td>\n";
			echo "<td align='right'>$aliq_ipi</td>\n";

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
		echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi,2,",",".") . " </b> </td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		echo "<tr>\n";
		echo "<td><h1><center>Nota de Devolução $nota_fiscal</center></h1></td>\n";
		echo "</tr>";
		echo "</table>";
	
		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;

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

	$resX = pg_exec ($con,$sql);
	if(pg_numrows($resX)>0 AND strlen($nota_fiscal)>0){

		echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";

		echo "<tr align='left'  height='16'>\n";
		echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
		echo "<b>&nbsp;<b>PEÇAS COM RESSARCIMENTO - DEVOLUÇÃO OBRIGATÓRIA </b><br>\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
		echo "<td>CFOP <br> <b>$cfop</b> </td>";
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
	
			$sua_os             = pg_result ($resX,$x,sua_os);
			$produto_referencia = pg_result ($resX,$x,produto_referencia);
			$produto_descricao  = pg_result ($resX,$x,produto_descricao);
			$data_ressarcimento = pg_result ($resX,$x,data_ressarcimento);
			$quem_trocou        = pg_result ($resX,$x,login);
	
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

	echo "<input type='hidden' name='qtde_notas' value='$contador'>";
	echo "<br><br><center><input type='button' value='Cancelar Todas as Notas' onclick=\"javascript: if ('Deseja cancelar todas as notas deste extrato? Após este procedimento, as notas canceladas não poderão ser recuperadas!')this.form.submit();\"></center>";
	echo "</form>";
}else{

	echo "<h1>Posto autorizado ainda não preencheu as notas de devolução.<br>Para consultar as notas, logue como Este Posto e acesse seu extrato.</h1>";
	//$res = pg_exec ($con,$sql);

}
?>

<p><p>

<? include "rodape.php"; ?>
