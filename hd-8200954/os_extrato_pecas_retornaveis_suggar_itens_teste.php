<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro="";
$msg="";

$periodo = trim($_GET['periodo']);
if (strlen($periodo)==0){
	$periodo = trim($_POST['periodo']);
}

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0){
	$extrato = trim($_POST['extrato']);
}

$posto_da_fabrica = "27253";

$extratos = array();


if (strlen($periodo)>0){
	$periodo_array = explode("-",$periodo);

	$mes = $periodo_array[0];
	if (strlen($mes)==1){
		$mes = "0".$mes;
	}
	if ($mes>12 or $mes<0){
		$mes = "01";
	}
	$mes_proximo = $mes +1;
	if ($mes_proximo>12){
		$mes_proximo = "01";
	}

	$ano         = $periodo_array[1];
	$ano_proximo = $ano;

	if ($mes_proximo=="01"){
		$ano_proximo++;
	}
}else{
	header("Location: os_extrato.php");
	exit;
}


if (strlen($msg_erro)==0){
	$sql = "SELECT DISTINCT extrato
			FROM tbl_extrato_lgr
			JOIN tbl_extrato           USING(extrato)
			WHERE tbl_extrato.data_geracao BETWEEN '$ano-$mes-01 00:00:01' AND '$ano_proximo-$mes_proximo-01 00:00:01'
			AND tbl_extrato.fabrica                   = $login_fabrica
			AND tbl_extrato.posto                     = $login_posto
			";
	$sql = "SELECT DISTINCT extrato
			FROM tbl_extrato_lgr
			JOIN tbl_extrato           USING(extrato)
			WHERE TO_CHAR(tbl_extrato.data_geracao,'MM-YYYY') = '$mes-$ano'
			AND tbl_extrato.fabrica                   = $login_fabrica
			AND tbl_extrato.posto                     = $login_posto
			";
	$resNF = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($resNF) ; $i++) {
		array_push($extratos,pg_result ($resNF,$i,extrato));
	}
}

if (strlen($extrato)>0){
	$extratos = array();
	array_push($extratos,$extrato);
}


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

</style>

<br><br>

<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center'><a href='os_extrato.php'>Ver outro extrato</a></td>
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
	<b style='font-size:14px;font-weight:normal'>Emitir as NF de devolução nos mesmos valores e impostos, referenciando NF de origem Suggar, e postagem da NF para a Suggar</b>
	</TD>
</TR>
</table>


<?
	$array_nf_canceladas = array();

	$sql="SELECT faturamento,nota_fiscal
			FROM tbl_faturamento
			WHERE fabrica             = $login_fabrica
			AND distribuidor          = $login_posto
			AND extrato_devolucao     IN (".implode(",", $extratos).")
			AND posto                 = $posto_da_fabrica
			AND cancelada IS NOT NULL";
	$res_nota = pg_exec ($con,$sql);
	$notasss = pg_numrows ($res_nota);
	for ($i=0; $i<$notasss; $i++){
		$nf_cancelada = pg_result ($res_nota,$i,nota_fiscal);
		array_push($array_nf_canceladas,$nf_cancelada);
	}

	if (count($array_nf_canceladas)>0){
		if (count($array_nf_canceladas)>1){
			echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>As notas:</b><br>".implode(",<br>",$array_nf_canceladas)." <br>foram <b>canceladas</b></h3>";
			// e deverão ser preenchidas novamente! <br> <a href='extrato_posto_devolucao_lenoxx.php?extrato=$extrato&pendentes=sim'>Clique aqui</a> para o preenchimento das notas.
		}else{
			echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>A nota</b> ".implode(", ",$array_nf_canceladas)." foi <b>cancelada</b></h3>";
			// e deverá ser preenchida novamente! <br> <a href='extrato_posto_devolucao_lenoxx.php?extrato=$extrato&pendentes=sim'>Clique aqui</a> para o preenchimento da nota.
		}
	}

?>

<?
$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

$sql = "SELECT  faturamento,
		extrato_devolucao,
		nota_fiscal,
		TO_CHAR(emissao,'DD/MM/YYYY') AS emissao,
		distribuidor,
		posto,
		cfop,
		TO_CHAR(cancelada,'DD/MM/YYYY') AS cancelada
	FROM tbl_faturamento
	WHERE posto in ($posto_da_fabrica)
	AND distribuidor      = $login_posto
	AND fabrica           = $login_fabrica
	AND extrato_devolucao IN (".implode(",", $extratos).")
	ORDER BY faturamento ASC";
$res = pg_exec ($con,$sql);
$qtde_for=pg_numrows ($res);

if ($qtde_for > 0) {

	$contador=0;
	for ($i=0; $i < $qtde_for; $i++) {

		$faturamento_nota    = trim (pg_result ($res,$i,faturamento));
		$distribuidor        = trim (pg_result ($res,$i,distribuidor));
		$posto               = trim (pg_result ($res,$i,posto));
		$nota_fiscal         = trim (pg_result ($res,$i,nota_fiscal));
		$emissao             = trim (pg_result ($res,$i,emissao));
		$extrato_devolucao	 = trim (pg_result ($res,$i,extrato_devolucao));
		$cfop                = trim (pg_result ($res,$i,cfop));
		$cancelada           = trim (pg_result ($res,$i,cancelada));

		$distribuidor        = "";
		$produto_acabado     = "";

		$sql_topo = "SELECT
					CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
					tbl_peca.devolucao_obrigatoria
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				JOIN tbl_peca USING(peca)
				WHERE tbl_faturamento.posto           = $posto
				AND tbl_faturamento.distribuidor      = $login_posto
				AND tbl_faturamento.fabrica           = $login_fabrica
				AND tbl_faturamento.extrato_devolucao = $extrato_devolucao
				AND tbl_faturamento.faturamento       = $faturamento_nota
				LIMIT 1";

		$res_topo              = pg_exec   ($con,$sql_topo);
		$produto_acabado       = pg_result ($res_topo,0,produto_acabado);
		$devolucao_obrigatoria = pg_result ($res_topo,0,devolucao_obrigatoria);

		$pecas_produtos = "PEÇAS";
		$devolucao = " RETORNO OBRIGATÓRIO ";

		if ($devolucao_obrigatoria=='f') $devolucao = " NÃO RETORNÁVEIS ";
		if ($devolucao_obrigatoria=='f') $pecas_produtos = "PEÇAS";

		if ($produto_acabado == "TRUE"){
			$pecas_produtos = "$posto_desc PRODUTOS";
			$devolucao      = " RETORNO OBRIGATÓRIO ";
		}

		if (strlen ($posto) > 0) {
			$sql  = "SELECT * FROM tbl_posto WHERE posto = $posto";
			$resX = pg_exec ($con,$sql);

			$estado   = pg_result ($resX,0,estado);
			$razao    = pg_result ($resX,0,nome);
			$endereco = trim (pg_result ($resX,0,endereco)) . " " . trim (pg_result ($resX,0,numero));
			$cidade   = pg_result ($resX,0,cidade);
			$estado   = pg_result ($resX,0,estado);
			$cep      = pg_result ($resX,0,cep);
			$fone     = pg_result ($resX,0,fone);
			$cnpj     = pg_result ($resX,0,cnpj);
			$ie       = pg_result ($resX,0,ie);
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
		$cabecalho .= "<td>Emissao <br> <b>$emissao</b> </td>\n";
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
		if ($verificacao!=='1'){
			$topo .=  "<td><b>% IPI</b></td>\n";
		}
		$topo .=  "</tr>\n";
		$topo .=  "</thead>\n";

		$sql = "SELECT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.ipi,
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
				tbl_peca.devolucao_obrigatoria,
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
					AND   tbl_faturamento.extrato_devolucao IN (".implode(",",$extratos).")
					AND   tbl_faturamento.faturamento=$faturamento_nota
					AND   tbl_faturamento.posto = $posto
					AND   tbl_faturamento.distribuidor=$login_posto
				GROUP BY
					tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.devolucao_obrigatoria,
					tbl_peca.produto_acabado,
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
		$tota_pecas       = 0;

		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

			$peca                = pg_result ($resX,$x,peca);
			$peca_referencia     = pg_result ($resX,$x,referencia);
			$peca_descricao      = pg_result ($resX,$x,descricao);
			$ipi                 = pg_result ($resX,$x,ipi);
			$peca_produto_acabado= pg_result ($resX,$x,produto_acabado);
			$peca_devolucao_obrigatoria = pg_result ($resX,$x,devolucao_obrigatoria);
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
					AND   tbl_faturamento.extrato_devolucao IN (".implode(",",$extratos).")
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


//			$nota_fiscal_item = pg_result ($resX,$x,nota_fiscal);
//			$faturamento = pg_result ($resX,$x,faturamento);

			if (strlen ($aliq_icms)  == 0) {
				$aliq_icms = 0;
			}

			if (strlen($aliq_ipi)==0) {
				$aliq_ipi=0;
			}

			$total_item  = $peca_preco * $qtde;
			$tota_pecas += $total_item;

			if ($aliq_icms==0){
				$base_icms=0;
				$valor_icms=0;
			}
			else{
				$base_icms  = $total_item;
				$valor_icms = $total_item * $aliq_icms / 100;
			}


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
			echo "<input type='hidden' name='id_item_LGR_$item_nota-$numero_nota' value='$extrato_lgr'>\n";
			echo "<input type='hidden' name='id_item_peca_$item_nota-$numero_nota'  value='$peca'>\n";
			echo "<input type='hidden' name='id_item_preco_$item_nota-$numero_nota' value='$preco'>\n";
			echo "<input type='hidden' name='id_item_qtde_$item_nota-$numero_nota'  value='$qtde'>\n";
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
			echo "</tr>\n";
			flush();
		}
		if (count($notas_fiscais)>0){
			echo "<tfoot>";
			echo "<tr>";
			echo "<td colspan='8'> Referente as NFs. " . implode(", ",$notas_fiscais) . "</td>";
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
		$tota_geral = $total_nota + $total_valor_ipi;
		echo "<td>Total da Nota <br> <b> " . number_format ($tota_geral,2,",",".") . " </b> </td>";
		echo "</tr>";
		echo "</table>";

		if (strlen($cancelada)==0){
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			echo "<tr>\n";
			echo "<td><h1><center>Nota de Devolução $nota_fiscal</center></h1></td>\n";
			echo "</tr>";
			echo "</table>";
		}else{
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			echo "<tr>\n";
			echo "<td><h1><center><strike>Nota de Devolução $nota_fiscal</strike></center></h1><br>\n";
			echo "<h4 style='color:red'><center>ESTA NOTA FOI CANCELADA EM $cancelada</center></h4></td>\n";
			echo "</tr>";
			echo "</table>";
		}

		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$tota_pecas       = 0;

	}
}else{
	echo "<h1><center> Sem peças para Devolução </center></h1>";
}


/*###########################################################
	PEÇAS RETORNAVEIS - HD 92975
  ###########################################################*/
if(1==1){
	echo "<br><br>";
	$sql = "SELECT tbl_peca.peca
			INTO TEMP tmp_suggar_fat_$login_posto
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING (faturamento)
			JOIN tbl_peca             USING (peca)
			WHERE tbl_faturamento.fabrica = $login_fabrica
			AND   tbl_faturamento.extrato_devolucao IN (".implode(",",$extratos).")
			AND   tbl_faturamento.faturamento=$faturamento_nota
			AND   tbl_faturamento.posto = $posto
			AND   tbl_faturamento.distribuidor=$login_posto;

			SELECT tbl_os.os                                 ,
					tbl_os.sua_os                            ,
					tbl_os.consumidor_nome                   ,
					tbl_peca.referencia as peca_referencia   ,
					tbl_peca.descricao     as peca_nome      ,
					tbl_os_item.qtde                         ,
					tbl_os_item.preco                        ,
					tbl_os_item.custo_peca                   ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
			FROM tbl_os
			JOIN tbl_os_extra using(os)
			JOIN tbl_os_produto using(os)
			JOIN tbl_os_item using(os_produto)
			JOIN tbl_peca using(peca)
			JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			WHERE tbl_os_extra.extrato = $extrato
			AND tbl_extrato.fabrica    = $login_fabrica
			AND tbl_os_item.servico_realizado IN(504)
			AND tbl_peca.devolucao_obrigatoria IS TRUE
			AND tbl_servico_realizado.troca_de_peca IS TRUE
			AND tbl_peca.peca NOT IN(SELECT peca FROM tmp_suggar_fat_$login_posto)";
	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);

	echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	if ($totalRegistros > 0){
		echo "<TR class='menu_top'>\n";
			echo "<TD colspan='4' align = 'center'>";
			echo "EXTRATO $extrato GERADO EM " . pg_result ($res,0,data_geracao) . " - RETORNO OBRIGATÓRIO" ;
			echo "</TD>";
		echo "</TR>\n";
		echo "<TR class='menu_top'>\n";
			echo "<TD align='center' >OS</TD>\n";
			echo "<TD align='center' >CLIENTE</TD>\n";
			echo "<TD align='center' >PEÇA</TD>\n";
			echo "<TD align='center' >QTDE</TD>\n";
		echo "</TR>\n";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			$os					= trim(pg_result ($res,$i,os));
			$sua_os				= trim(pg_result ($res,$i,sua_os));
			$consumidor			= trim(pg_result ($res,$i,consumidor_nome));
			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$peca_nome			= trim(pg_result ($res,$i,peca_nome));
			$preco				= trim(pg_result ($res,$i,preco));
			$qtde				= trim(pg_result ($res,$i,qtde));
			$preco				= number_format($preco,2,",",".");

			$cor = "#d9e2ef";
			$btn = 'amarelo';

			if ($i % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			if (strstr($matriz, ";" . $i . ";")) {
				$cor = '#E49494';
			}

			if (strlen ($sua_os) == 0) $sua_os = $os;

			echo "<TR class='table_line' style='background-color: $cor;'>\n";
				echo "<TD align='center' nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>\n";
				echo "<TD align='left' nowrap>$consumidor</TD>\n";
				echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
			echo "</TR>\n";
		}
	}
	echo "</TABLE>\n";
}
/*######################################################*/

?>

<p><p>

<? include "rodape.php"; ?>
