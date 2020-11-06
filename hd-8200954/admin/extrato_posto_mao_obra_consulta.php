<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "financeiro";

include "autentica_admin.php";
include "funcoes.php";

$extrato = (strlen(($_POST['extrato'])>0)) ? trim($_POST['extrato']) : trim($_GET['extrato']);
$posto   = (strlen(($_POST['posto'])>0)) ? trim($_POST['posto']) : trim($_GET['posto']);

$btn_acao = trim($_POST['btn_acao']);

$layout_menu = "financeiro";
$title = "Consulta de Extratos do Posto";

include "cabecalho.php";
?>

<style type="text/css">
.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B
}

.table_line2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_obs2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>
<p>
<center>

<font size='+1' face='arial'>Data do Extrato</font>
<?

$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL     THEN 1 ELSE NULL END) AS qtde                      ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NOT NULL THEN 1 ELSE NULL END) AS qtde_recusada             ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto     ,
				tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.nome_fantasia AS distrib_nome                             ,
				distrib.posto         AS distrib_posto
		FROM
			(SELECT tbl_os_extra.os 
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra
		ORDER BY tbl_linha.nome";
$res = pg_exec ($con,$sql);

echo @pg_result ($res,0,data_geracao);

echo "<br>";

$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome
		FROM tbl_posto_fabrica
		JOIN tbl_posto USING (posto)
		JOIN tbl_extrato ON tbl_extrato.fabrica = tbl_posto_fabrica.fabrica AND tbl_extrato.posto = tbl_posto_fabrica.posto
		WHERE tbl_extrato.extrato = $extrato";
$resX = pg_exec ($con,$sql);

echo @pg_result ($resX,0,codigo_posto) . " - " . @pg_result ($resX,0,nome);

$codigo_posto2 = pg_result ($resX,0,codigo_posto);

if($login_fabrica == 3){
	include('posto_extrato_ano_britania.php');
}

if($login_fabrica == 3){
	if(pg_numrows($res) > 0){
	echo "<table width='450'>";
		echo "<tr>";
			echo "<td><BR></td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td bgcolor='#FF0000' width='15'>&nbsp;</td>";
			echo "<td style='font-size: 10px; text-align: left;'>Débito</td>";
		
			echo "<td bgcolor='#0000FF' width='15'>&nbsp;</td>";
			echo "<td style='font-size: 10px; text-align: left;'>Crédito</td>";

			echo "<td bgcolor='#339900' width='15'>&nbsp;</td>";
			echo "<td style='font-size: 10px; text-align: left;'>Valores de ajuste de Extrato</td>";
		echo "</tr>";
	echo "</table>";
	echo "<br>";
	}
}

$xsql = "SELECT extrato 
		FROM tbl_extrato_conferencia 
		JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
		WHERE extrato = $extrato";
$xres = pg_exec ($con,$xsql);
if(pg_numrows($xres)==0){
	$mostra_conferencia = 1;
}

echo "<table width='400' align='center' border='0' cellspacing='2'>";
echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Recusadas</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "<td align='center' nowrap >Pago via</td>";
echo "<td align='center' nowrap >&nbsp;</td>";
if($login_fabrica==3){
echo "<td align='center' bgcolor='#FFFFFF' nowrap >&nbsp;</td>";
echo "<td align='center' nowrap >OSs Enviadas</td>";
}
echo "</tr>";

$total_qtde            = 0 ;
$total_qtde_recusada   = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;

echo "<input type='hidden' name='qtde_linha' id='qtde_linha' value='".pg_numrows($res)."'>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	
	$linha             = pg_result ($res,$i,linha);
	$linha_nome        = pg_result ($res,$i,linha_nome);
	$unitario          = number_format (pg_result ($res,$i,unitario),2,',','.');
	$qtde_recusada     = number_format (pg_result ($res,$i,qtde_recusada),0,',','.');
	$qtde              = number_format (pg_result ($res,$i,qtde),0,',','.');
	$qtde_recusada     = number_format (pg_result ($res,$i,qtde_recusada),0,',','.');
	$mao_de_obra_posto = number_format (pg_result ($res,$i,mao_de_obra_posto),2,',','.');
	$distrib_nome      = pg_result ($res,$i,distrib_nome) ;
	$distrib_posto     = pg_result ($res,$i,distrib_posto) ;
	$mao_de_obra_posto_unitaria = number_format(pg_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');

	echo "<tr style='font-size: 10px'>";

	echo "<td nowrap >";
	echo $linha_nome;
	echo "<input type='hidden' name='linha_$i' value='$linha'>";
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $unitario;
	echo "<input type='hidden' name='unitario_$i' id='unitario_$i' value='$unitario'>";
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde;
	echo "<input type='hidden' name='qtde_$i' id='qtde_$i' value='$qtde'>";
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde_recusada;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $mao_de_obra_posto;
	echo "<input type='hidden' name='mao_de_obra_posto_$i' id='mao_de_obra_posto' value='$mao_de_obra_posto'>";
	echo "</td>";

	echo "<td  nowrap align='center'>";
	if (strlen ($distrib_nome) == 0) $distrib_nome = "<b>FABR.</b>";
	echo $distrib_nome;
	echo "<input type='hidden' name='distrib_posto_$i' value='$distrib_posto'>";
	echo "</td>";

	$linha = pg_result ($res,$i,linha) ;
	$mounit = pg_result ($res,$i,unitario) ;

	echo "<td align='right' nowrap>";
	echo "<a href='extrato_posto_detalhe.php?extrato=$extrato&posto=$posto&linha=$linha&mounit=$mounit'>ver O.S.</a>";
	echo "</td>";

	if($login_fabrica==3 AND $mostra_conferencia==1){
		echo "<td align='right' nowrap width='40'>&nbsp</td>";
		echo "<td align='right' nowrap>";
			echo "<INPUT TYPE=\"text\" NAME='qtde_enviada_$i' id='qtde_enviada_$i' value='$qtde' size='10' maxlength='10' style='text-align: right'>";
			echo "<INPUT TYPE=\"hidden\" NAME='qtde_item_enviada' value='$i' size='10' maxlength='10' style='text-align: right'>";
		echo "</td>";
	}else{
		$mao_de_obra_posto = fnc_limpa_moeda($mao_de_obra_posto);

		$mao_de_obra_posto_unitaria = fnc_limpa_moeda($mao_de_obra_posto_unitaria);

		$sqlm = "SELECT SUM(tbl_extrato_conferencia_item.qtde_conferida) as qtde_conferida
				FROM   tbl_extrato_conferencia 
				JOIN   tbl_extrato_conferencia_item USING(extrato_conferencia) 
				WHERE  tbl_extrato_conferencia.extrato = $extrato
				AND    tbl_extrato_conferencia_item.mao_de_obra_unitario = '$mao_de_obra_posto_unitaria'
				AND    tbl_extrato_conferencia_item.linha       = '$linha'
				AND    tbl_extrato_conferencia.cancelada IS NOT TRUE";
		$resm = pg_exec($con, $sqlm);
		if(pg_numrows($resm)>0){
			$qtde_conferida = number_format(pg_result ($resm,0,qtde_conferida),0,',','.');
			echo "<td align='right' nowrap width='40'>&nbsp</td>";
			echo "<td align='right' nowrap>";
				if($qtde==$qtde_conferida){
					$total_conferida = $total_conferida + $qtde_conferida;
					echo $qtde_conferida;
					$qtde_item_enviada = "";
					echo "<INPUT TYPE=\"hidden\" NAME='qtde_enviada_$i' id='qtde_enviada_$i' value='$aqtde_conferida'>";
				}else{
					$total_conferida = $total_conferida + $qtde_conferida;
					echo "<INPUT TYPE=\"text\" NAME='qtde_enviada_$i' id='qtde_enviada_$i' value='$qtde_conferida' size='10' maxlength='10' style='text-align: right'>";
					$qtde_item_enviada = $i;
				}
			echo "</td>";
		}else{
			$qtde_item_enviada = "";
		}
	}

	echo "</tr>";

	$total_qtde            += pg_result ($res,$i,qtde) ;
	$total_qtde_recusada   += pg_result ($res,$i,qtde_recusada) ;
	$total_mo_posto        += pg_result ($res,$i,mao_de_obra_posto) ;
	$total_mo_adicional    += pg_result ($res,$i,mao_de_obra_adicional) ;
	$total_adicional_pecas += pg_result ($res,$i,adicional_pecas) ;
}

if($login_fabrica == 3){
	$sql = " SELECT
			extrato,
			historico,
			valor,
			admin,
			debito_credito,
			lancamento
		FROM tbl_extrato_lancamento
		WHERE extrato = $extrato
		AND fabrica = $login_fabrica
		AND (admin IS NOT NULL OR lancamento in (103,104))";
	$res = pg_exec ($con,$sql);

	echo "<INPUT TYPE='hidden' NAME='qtde_avulso' id='qtde_avulso' value=". pg_numrows($res) .">";
	
	if(pg_numrows($res) > 0){
		for($i=0; $i < pg_numrows($res); $i++){
			$extrato         = trim(pg_result($res, $i, extrato));
			$historico       = trim(pg_result($res, $i, historico));
			$valor           = trim(pg_result($res, $i, valor));
			$debito_credito  = trim(pg_result($res, $i, debito_credito));
			$lancamento      = trim(pg_result($res, $i, lancamento));
			
			if($debito_credito == 'D'){ 
				$bgcolor= "bgcolor='#FF0000'"; 
				$color = " color: #000000; ";
				if ( $valor>0){
					$valor = $valor * -1;
				}
			}else{ 
				$bgcolor= "bgcolor='#0000FF'";
				$color = " color: #FFFFFF; ";
			}

			if ($lancamento==103 or $lancamento==104) {
				$bgcolor= "bgcolor='#339900'";
			}

			echo "<tr style='font-size: 10px; $color' $bgcolor>";
			echo "<TD><b>Avulso</b></TD>";
			echo "<TD colspan='3'><b>$historico</b></TD>";
			echo "<TD align='right'><b>".number_format ($valor ,2,",",".")."</b>
			<INPUT TYPE='hidden' NAME='valor_avulso_$i' id='valor_avulso_$i' value='$valor'>
			</TD>";
			echo "<TD>&nbsp;</TD>";
			echo "<TD>&nbsp;</TD>";
			echo "</tr>";
			$total_mo_posto = $valor + $total_mo_posto;
		}
	}
}


echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center'>TOTAIS</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_qtde_recusada  ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='center' bgcolor='#FFFFFF'>&nbsp;</td>";
echo "<td align='right'>$total_conferida</td>";
echo "</tr>";

echo "</table>";

if ($login_fabrica==3){
	echo "<p><a href='extrato_posto_mao_obra_os_download.php?extrato=$extrato' target='_blank'>Clique aqui para fazer o download das Ordens de Serviços</a></p>";
}

if ($login_fabrica == 3) {
	$sql = "SELECT  tbl_extrato_conferencia.extrato_conferencia                        AS extrato_conferencia,
					tbl_extrato_conferencia.data_conferencia                           AS data_conferencia,
					to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY')     AS data,
					tbl_extrato_conferencia.nota_fiscal                                AS nota_fiscal,
					to_char(tbl_extrato_conferencia.data_nf,'DD/MM/YYYY')              AS data_nf,
					tbl_extrato_conferencia.valor_nf                                   AS valor_nf,
					tbl_extrato_conferencia.valor_nf_a_pagar                           AS valor_nf_a_pagar,
					tbl_extrato_conferencia.caixa                                      AS caixa,
					tbl_extrato_conferencia.obs_fabricante                             AS obs_fabricante,
					tbl_extrato_conferencia.obs_posto                                  AS obs_posto,
					to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY')   AS previsao_pagamento,
					tbl_admin.login                                                    AS login,
					tbl_extrato_conferencia.cancelada                                  AS cancelada,
					to_char(tbl_extrato_conferencia.data_cancelada,'DD/MM/YYYY')       AS data_cancelada,
					tbl_extrato_conferencia.justificativa_cancelamento                 AS justificativa_cancelamento,
					ADM.login                                                          AS admin_cancelou
			FROM tbl_extrato_conferencia
			JOIN tbl_admin   USING(admin)
			LEFT JOIN tbl_admin ADM ON ADM.admin = tbl_extrato_conferencia.admin_cancelou
			WHERE tbl_extrato_conferencia.extrato = $extrato
			ORDER BY tbl_extrato_conferencia.data_conferencia";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='4'>";
			echo "<TR>";
				echo "<TD height='20' class='menu_top2' colspan='9'>CONFERÊNCIA </TD>";
			echo "</TR>";
			echo "<TR class='menu_top2' height='20'>";
				echo "<TD>#</TD>";
				echo "<TD>DATA<br>CONFERÊNCIA</TD>";
				echo "<TD>NF</TD>";
				echo "<TD>DATA NF</TD>";
				echo "<TD>VALOR NF<BR>ORIGINAL</TD>";
				echo "<TD>VALOR NF<BR>A PAGAR</TD>";
				echo "<TD>CAIXA</TD>";
				echo "<TD>PREVISÃO<BR>PAGAMENTO</TD>";
				echo "<TD>ADMIN</TD>";
			echo "</TR>";
		
		for ($i=0; $i<pg_numrows($res); $i++) {
			$extrato_conferencia= pg_result($res,$i,extrato_conferencia);
			$data               = pg_result($res,$i,data);
			$nota_fiscal        = pg_result($res,$i,nota_fiscal);
			$data_nf            = pg_result($res,$i,data_nf);
			$valor_nf           = pg_result($res,$i,valor_nf);
			$valor_nf_a_pagar   = pg_result($res,$i,valor_nf_a_pagar);
			$caixa              = pg_result($res,$i,caixa);
			$obs_fabricante     = pg_result($res,$i,obs_fabricante);
			$obs_posto          = pg_result($res,$i,obs_posto);
			$previsao_pagamento = pg_result($res,$i,previsao_pagamento);
			$admin              = pg_result($res,$i,login);

			$cancelada                  = pg_result($res,$i,cancelada);
			$data_cancelada             = pg_result($res,$i,data_cancelada);
			$justificativa_cancelamento = pg_result($res,$i,justificativa_cancelamento);
			$admin_cancelou             = pg_result($res,$i,admin_cancelou);

			$valor_nf         = number_format($valor_nf,2,",",".");
			$valor_nf_a_pagar = number_format($valor_nf_a_pagar,2,",",".");

			$class     =  'table_line2';
			$class_obs =  'table_obs2';

			echo "<TR class='$class'>";
				echo "<TD><span style='font-size:14px;font-weight:bold'>".($i+1)."</span></TD>";
				echo "<TD>$data</TD>";
				echo "<TD>$nota_fiscal </TD>";
				echo "<TD>$data_nf </TD>";
				echo "<TD align='right'>$valor_nf </TD>";
				echo "<TD align='right'>$valor_nf_a_pagar </TD>";
				echo "<TD>$caixa </TD>";
				echo "<TD>$previsao_pagamento </TD>";
				echo "<TD>$admin </TD>";
			echo "</TR>";
			echo "<TR>";
				echo "<TD></TD>";
				echo "<TD class='$class'>";
				if(strlen($obs_fabricante)>0) echo "OBS FABRICA:";
				echo "</TD>";
				echo "<TD class='$class_obs' colspan='4'>$obs_fabricante</TD>";
			
				echo "<TD class='$class'>";
				if(strlen($obs_posto)>0) echo "OBS POSTO:"; 
				echo "</TD>";
				echo "<TD class='$class_obs' colspan='3'>$obs_posto</TD>";
			echo "</TR>";
			if($cancelada == 't'){
				echo "<TR bgcolor='#FFDBBB' class='table_line2'>";
					echo "<TD colspan='2' align='left'><img src='imagens/seta_checkbox.gif' valign='absmiddle'> &nbsp;<strong><font color='#FF2222'>CANCELADA</font></strong></TD>";
					echo "<TD             align='left'>$data_cancelada</TD>";
					echo "<TD             align='right'>Admin: </TD>";
					echo "<TD             align='left'>$admin_cancelou</TD>";
					echo "<TD colspan='6' align='left'>Justificativa: $justificativa_cancelamento</TD>";
				echo "</TR>";
			}

			echo "<TR height='1'>";
				echo "<TD colspan='9' height='1'><hr style='height:1px;padding:none;margin:none'></TD>";
			echo "</TR>";
		}
		echo "</TABLE>";
	}
}
echo "<BR><p>";

echo "<a href='extrato_posto_mao_obra_impressao.php?extrato=$extrato&posto=$posto&consulta=sim' target='_blank'><img src='imagens_admin/btn_imprimir_azul.gif'></a>";

echo "<BR><p><a href='extrato_posto_britania.php?somente_consulta=sim'>Outro extrato</a>";

?>

<p><p>

<? include "rodape.php"; ?>
