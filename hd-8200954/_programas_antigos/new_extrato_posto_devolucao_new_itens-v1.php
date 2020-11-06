<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro="";
$msg="";

$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$ok_aceito="nao";


###### HABILITAR ESTE IF APÒS A EFETIVAÇÃO #######
if ($extrato<144000){
//	header("Location: new_extrato_posto.php");
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

//header("Location: new_extrato_posto.php");
//exit();

$postos_permitidos = array(0 => '595', 1 => '1537', 2 => '1773', 3 => '7080', 4 => '5037', 5 => '13951', 6 => '4311', 7 => '564', 8 => '1623', 9 => '1664');

if (array_search($login_posto, $postos_permitidos)===false){ //verifica se o posto tem permissao
	header("Location: new_extrato_posto.php");
	exit();
}

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0)
	$extrato = trim($_POST['extrato']);

if (strlen($extrato)==0){
	header("Location: new_extrato_posto.php");
}

$ok_aceito = trim($_POST['ok_aceito']);
if ($ok_aceito=='Concordo') 
	$numero_linhas = trim($_POST['qtde_linha']);

$btn_acao = trim($_POST['botao_acao']);



// verificaçao se o posto quer ver a Mao de obra mas ele ainda nï¿½o preencheu as notas
$mao = trim($_GET['mao']);
if (strlen($mao)>0 AND $mao=='sim'){
	$sql = "SELECT  faturamento,
			extrato_devolucao,
			nota_fiscal,
			distribuidor,
			NULL as produto_acabado,
			NULL as devolucao_obrigatoria
		FROM tbl_faturamento
		WHERE posto=13996
		AND distribuidor=$login_posto
		AND fabrica=$login_fabrica
		AND extrato_devolucao=$extrato
		ORDER BY faturamento ASC";
	$res = pg_exec ($con,$sql);
	$jah_digitado=pg_numrows ($res);
	if ($jah_digitado>0){
		header("Location: new_extrato_posto_mao_obra.php?extrato=$extrato");
		exit();
	}
	else{
		$msg_erro="Devem ser preenchidas as Notas Fiscais de devolução de Produtos e peças para liberar a tela de consulta de valores de mão-de-obra - extrato";
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
<td align='center' width='33%'><a href='<?php echo $PHP_SELF ?>?mao=sim&extrato=<? echo $extrato ?>'>Ver Mão-de-Obra</a></td>
<td align='center' width='33%'><a href='new_extrato_posto.php'>Ver outro extrato</a></td>
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
	As peças ou produtos não devolvidos neste extrato serão apresentadas na tela de <a href='<? echo "$PHP_SELF?extrato=$extrato&pendentes=sim" ?>' target='_blank'>consulta de pendências</a>. Caso não sejam efetivadas as devoluções, os itens serão cobrados do posto autorizado.
<br><br>
<b style='font-size:14px;font-weight:normal'>Emitir as NF de devolução nos mesmos valores e impostos, referenciando NF de origem Britânia, e postagem da NF para Britânia Joinville-SC</b>
	</TD>
</TR>
<TR>
	<TD colspan='8' style='padding:10px;' align='center'>
<a href='<? echo "new_extrato_posto_devolucao_new.php?extrato=$extrato&pendentes=sim" ?>' target='_blank'>CONSULTA DE PENDÊNCIAS</a>
	</td>
<TR>
</table>

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
$estado_origem = pg_result ($resX,0,estado);


$sql = "SELECT  faturamento,
		extrato_devolucao,
		nota_fiscal,
		distribuidor
	FROM tbl_faturamento
	WHERE posto=13996
	AND distribuidor      = $login_posto
	AND fabrica           = $login_fabrica
	AND extrato_devolucao = $extrato
	ORDER BY faturamento ASC";
$res = pg_exec ($con,$sql);
$qtde_for=pg_numrows ($res);

if ($qtde_for > 0) {

	$contador=0;
	for ($i=0; $i < $qtde_for; $i++) {

		$faturamento_nota    = trim (pg_result ($res,$i,faturamento));
		$distribuidor        = trim (pg_result ($res,$i,distribuidor));
		$nota_fiscal         = trim (pg_result ($res,$i,nota_fiscal));
		$extrato_devolucao	 = trim (pg_result ($res,$i,extrato_devolucao));
		$distribuidor        = "";
		$produto_acabado     = "";

		$sql_topo = "SELECT  
					CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
					tbl_peca.devolucao_obrigatoria
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				JOIN tbl_peca USING(peca)
				WHERE tbl_faturamento.posto           = 13996
				AND tbl_faturamento.distribuidor      = $login_posto
				AND tbl_faturamento.fabrica           = $login_fabrica
				AND tbl_faturamento.extrato_devolucao = $extrato_devolucao
				AND tbl_faturamento.faturamento       = $faturamento_nota 
				LIMIT 1";
		$res_topo = pg_exec ($con,$sql_topo);
		$produto_acabado = pg_result ($res_topo,0,produto_acabado);
		$devolucao_obrigatoria = pg_result ($res_topo,0,devolucao_obrigatoria);

		$pecas_produtos = "PEÇAS";
		$devolucao = " RETORNO OBRIGATÓRIO ";

		if ($devolucao_obrigatoria=='f') $devolucao = " NÃO RETORNÁVEIS ";		
		if ($devolucao_obrigatoria=='f') $pecas_produtos = "PEÇAS";

		if ($produto_acabado == "TRUE"){
			$pecas_produtos = "PRODUTOS";
			 $devolucao = " RETORNO OBRIGATÓRIO ";
		}

		$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
		$endereco = "Rua Dona Francisca, 8300 - Mod.4 e 5 - Bloco A";
		$cidade   = "Joinville";
		$estado   = "SC";
		$cep      = "89239270";
		$fone     = "(41) 2102-7700";
		$cnpj     = "76492701000742";
		$ie       = "254.861.652";


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
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   tbl_faturamento.faturamento=$faturamento_nota
					AND   tbl_faturamento.posto=13996
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
					AND   tbl_faturamento.posto   = 13996
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
			}
			else{
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
		echo "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
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
