<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro="";
$msg="";

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0)
	$extrato = trim($_POST['extrato']);

if (strlen($extrato)==0){
	header("Location: extrato_posto.php");
}

$msg = "";

$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";


/* HD 46741 */
$sql = "SELECT  CASE WHEN data_geracao > '2008-10-30'::date THEN '1' ELSE '0' END
		FROM tbl_extrato
		WHERE extrato = $extrato ";
//echo "<br>sql_1: $sql";
$res2 = pg_exec ($con,$sql);
	
$verificacao = pg_result ($res2,0,0);
#2008-06-01 - HD 16362

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
.table_line3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #FE918D
}

.menu_top4 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #CC3333;
}

</style>

<br><br>

<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center' width='33%'><a href='new_extrato_posto_mao_obra.php?extrato=<? echo $extrato ?>'>Ver Mão-de-Obra</a></td>
<td align='center' width='33%'><a href='os_extrato.php'>Ver outro extrato</a></td>
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
<?
/*<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="10" class="menu_top" ><div align="center" style='font-size:16px'><b>ATENÇÃO</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
	As peças ou produtos não devolvidos neste extrato serão apresentadas na tela de consulta de pendências. Caso não sejam efetivadas as devoluções, os itens serão cobrados do posto autorizado.
<br><br>
<b style='font-size:14px;font-weight:normal'>Emitir as NF de devolução nos mesmos valores e impostos, referenciando NF de origem Telecontrol, e postagem da NF para a Telecontrol</b>
	</TD>
</TR>
</table>*/

if ($login_fabrica==51) {//HD 28111 15/8/2008
	if($login_fabrica==51) $class = "menu_top4";
	else                   $class = "menu_top";?>
	<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="0">
	<TR>
		<TD colspan="10" class="<? echo $class; ?>" ><div align="center" style='font-size:16px'>
		<b>
		<?
			if ($pecas_pendentes=="sim") echo "DEVOLUÇÃO PENDENTE";
			else                         echo "ATENÇÃO!";
		?>
		</b></div>
		</TD>
	</TR>
	</table>
<?
	echo "<TABLE width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
	/*echo "<tr class='table_line3'>\n";
	echo "<td align=\"center\"><B>EMITIR NOTA FISCAL DE MÃO DE OBRA PARA:</B><BR>
	BRASVINCI COMÉRCIO DE ACESSÓRIOS E EQUIPAMENTOS DE BELEZA LTDA.<br>
	Rua Bogaert, 152 - Vila Vermelha, SP, CEP 04.298-020<br>
	CNPJ: 07.881.054/0001-52</td>\n";
	echo "</tr>\n";
	echo "<tr class='table_line3'>\n";
	echo "<td align=\"center\"><B>ENVIAR AS OS's JUNTO COM A NF DE MÃO DE OBRA PARA:</b><BR>
	TELECONTROL NETWORKING LTDA.<br>
	AV. Carlos Artêncio, 420 B - Fragata C<br>
	Marília, SP, CEP 17519-255 <br>
	CNPJ: 04.716.427/0001-41 </td>\n";
	echo "</tr>\n";*/
	echo "<tr class='table_line3'>\n";
	echo "<td align=\"center\"><B>EMITIR E ENVIAR A NOTA FISCAL DE DEVOLUÇÃO PARA:</B><BR>
	TELECONTROL NETWORKING LTDA.<br>
	AV. Carlos Artêncio, 420 B - Fragata C<br>
	Marília, SP, CEP 17519-255 <br>
	CNPJ: 04.716.427/0001-41 </td>\n";
	echo "</tr>\n";


	if ($verificacao == '1'){
		echo "<tr class='table_line3'>\n";
		echo "<td align=\"center\"><B>VISTORIA DE PEÇAS</B><BR>
		<a href='lgr_vistoria_itens.php'>Clique aqui</a> para consultar as peças que  devem ser armazenadas no seu posto autorizado para vistoria.</td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
}
?>

<?
	$array_nf_canceladas = array();

	#Posto da Lenoxx
	if($login_fabrica==11){
	$posto_da_fabrica = "20321";
	}

		#Posto da hbtech
	if($login_fabrica==25 OR $login_fabrica==51){
	$posto_da_fabrica = "4311";
	}



	$sql="SELECT faturamento,nota_fiscal
			FROM tbl_faturamento
			WHERE fabrica             = $login_fabrica
			AND distribuidor          = $login_posto
			AND extrato_devolucao     = $extrato
			AND posto                 = $posto_da_fabrica
			AND cancelada IS NOT NULL";
	$res_nota = pg_exec ($con,$sql);
	//echo "sql1: $sql";
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
		TO_CHAR(cancelada,'DD/MM/YYYY') AS cancelada,
		movimento
	FROM tbl_faturamento
	WHERE posto in ($posto_da_fabrica)
	AND distribuidor      = $login_posto
	AND fabrica           = $login_fabrica
	AND extrato_devolucao = $extrato
	ORDER BY faturamento ASC";
//echo "<br>sql_2: $sql";
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
		$movimento           = trim (pg_result ($res,$i,movimento));
		
		
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
//echo "<br>sql3: $sql";
		$res_topo              = pg_exec   ($con,$sql_topo);
		$produto_acabado       = pg_result ($res_topo,0,produto_acabado);
		$devolucao_obrigatoria = pg_result ($res_topo,0,devolucao_obrigatoria);

		$pecas_produtos = "PEÇAS";
		$devolucao = " RETORNO OBRIGATÓRIO ";

		#if ($devolucao_obrigatoria=='f') $devolucao = " NÃO RETORNÁVEIS ";		
		#if ($devolucao_obrigatoria=='f') $pecas_produtos = "PEÇAS";

		#HD 17436
		if ($movimento!='RETORNAVEL') $devolucao = " NÃO RETORNÁVEIS ";
		if ($movimento!='RETORNAVEL') $pecas_produtos = "PEÇAS";

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
		if ($verificacao!=='1' or 1==1){
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
					AND   tbl_faturamento.extrato_devolucao = $extrato
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
//echo "<br>sql4: $sql";
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
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   tbl_faturamento.faturamento=$faturamento_nota
					ORDER BY tbl_faturamento.nota_fiscal";
			$resNF = pg_exec ($con,$sql_nf);
			//echo "<br>sql7: $sql";
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

			if ($verificacao=='1' and 1==2){
				if ($aliq_ipi>0){
					$peca_preco = $peca_preco + ($peca_preco * $aliq_ipi/100);
				}
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
			if ($verificacao!=='1' or 1==1){
				echo "<td align='right'>$aliq_ipi</td>\n";
			}

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

		if ($verificacao=='1' and 1==2){
			echo "<td>Total de Peças <br> <b> " . number_format ($tota_pecas,2,",",".") . " </b> </td>\n";
		}else{
			echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
			echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
		}
		if ($verificacao=='1' and 1==2){
			$tota_geral = $total_nota;
		}else{
			$tota_geral = $total_nota + $total_valor_ipi;
		}
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
?>

<p><p>

<? include "rodape.php"; ?>
