<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "financeiro";

include "autentica_admin.php";
include "funcoes.php";

if (strlen($_POST['extrato']) > 0) $extrato = trim($_POST['extrato']);
else                               $extrato = trim($_GET['extrato']);

if (strlen($_POST['posto']) > 0)   $posto   = trim($_POST['posto']);
else                               $posto   = trim($_GET['posto']);

$layout_menu = "financeiro";
$title = "Impressao de Extratos do Posto";?>

<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$("input[@rel=data]").maskedinput("99/99/9999");
	});
</script>

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

.table_line3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #FFFFBB;
}

error{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	background-color: #FF0000;
}
</style>

<script language="JavaScript">

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='0';
	}
}

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO
function calcula_total(){
	var x = parseInt(document.getElementById('qtde_linha').value);
	var y = parseInt(document.getElementById('qtde_avulso').value);

	var somav = 0;
	var somat = 0;
	var mao_de_obra  = 0;
	var qtde_conferir_os = 0;
	var valor_avulso = 0;

	for (f=0; f<x;f++){
		mao_de_obra  = document.getElementById('unitario_'+f).value.replace(',','.');
		qtde_conferir_os = document.getElementById('qtde_conferir_os_'+f).value.replace(',','.');
		somav = parseInt(qtde_conferir_os) * parseFloat(mao_de_obra);
		somat = somat + parseFloat(somav); 
	}

	for (a=0; a<y; a++){
		valor_avulso = document.getElementById('valor_avulso_'+a).value;
		somat += parseFloat(valor_avulso);
	}

	document.getElementById('valor_conferencia_a_pagar').value= somat;
}

function MostraEscondeCancelamento(x){
	$('#cancelamento_conferencia_'+x).toggle();
}

function confimar_cancelamento(x){
	if ( $('#justificativa_cancelamento_aux_'+x).val().length > 0 ){
		if(confirm('Atenção: todas as conferências realizadas deste extrato serão canceladas e uma nova conferênia poderá ser realizada.\n\nDeseja cancelar as conferências realizadas deste extrato?')){
			$('#justificativa_cancelamento').val( $('#justificativa_cancelamento_aux_'+x).val() );
			$('#extrato_conferencia_id').val( $('#extrato_conferencia_aux_'+x).val() );
			document.frm_cancelar_conferencia.submit();
		}
	}else{
		alert('Informe o motivo do cancelamento!');
	}
}
</script>
<p>
<center>
<?
if(strlen($msg_erro)>0){
	echo "<DIV class='error'>".$msg_erro."</DIV>";
}

?>
<font size='+1' face='arial'>Data do Extrato</font>
<?

$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL     THEN 1 ELSE NULL END) AS qtde                      ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NOT NULL THEN 1 ELSE NULL END) AS qtde_recusada             ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto     ,
				tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao ,
				distrib.nome_fantasia AS distrib_nome                            ,
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
	//echo nl2br($sql);
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
		//hd 22096
			echo "<td bgcolor='#339900' width='15'>&nbsp;</td>";
			echo "<td style='font-size: 10px; text-align: left;'>Valores de ajuste de Extrato</td>";
		echo "</tr>";
	echo "</table>";
	}
}

$xsql = "SELECT extrato 
		FROM tbl_extrato_conferencia 
		WHERE extrato   = $extrato
		AND   cancelada IS TRUE";
$xres = pg_exec ($con,$xsql);
if(pg_numrows($xres)>0){
	$conferencia_excluida = 1;
}

$xsql = "SELECT extrato 
		FROM tbl_extrato_conferencia 
		JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
		WHERE extrato = $extrato
		AND   tbl_extrato_conferencia.cancelada IS NOT TRUE";
$xres = pg_exec ($con,$xsql);
if(pg_numrows($xres)==0){
	$mostra_conferencia = 1;
}

echo "<form style='MARGIN: 0px; WORD-SPACING: 0px' name='frm_conferencia' method='post' action='$PHP_SELF?posto=$posto'>";
echo "<table width='400' align='center' border='0' cellspacing='2'>";
echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Recusadas</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "<td align='center' nowrap >Pago via</td>";
if($login_fabrica==3){
	echo "<td align='center' bgcolor='#FFFFFF' nowrap >&nbsp;</td>";
		if ($_GET['consulta']<>'sim') {
	
		echo "<td align='center' nowrap >Conferir OS</td>";

		}
	if($mostra_conferencia!=1){
		echo "<td align='center' nowrap >OSs Enviadas</td>";
	}
}
echo "</tr>";

$total_qtde            = 0 ;
$total_qtde_recusada   = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;

echo "<input type='hidden' name='qtde_linha' id='qtde_linha' value='".pg_numrows($res)."'>";
$qtde_item_enviada = pg_numrows($res);

for($i=0; $i<pg_numrows($res); $i++){
	$linha             = pg_result ($res,$i,linha);
	$linha_nome        = pg_result ($res,$i,linha_nome);
	$unitario          = number_format(pg_result ($res,$i,unitario),2,',','.');
	$qtde_recusada     = number_format(pg_result ($res,$i,qtde_recusada),0,',','.');
	$qtde              = number_format(pg_result ($res,$i,qtde),0,',','.');
	$qtde_recusada     = number_format(pg_result ($res,$i,qtde_recusada),0,',','.');
	$mao_de_obra_posto = number_format(pg_result ($res,$i,mao_de_obra_posto),2,',','.');
	$mao_de_obra_posto_unitaria = number_format(pg_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');
	$distrib_nome      = pg_result ($res,$i,distrib_nome) ;
	$distrib_posto     = pg_result ($res,$i,distrib_posto) ;

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

	if($login_fabrica==3 AND $mostra_conferencia==1){
		echo "<td align='right' nowrap width='40'>&nbsp</td>";
		echo "<td align='right' nowrap>";
			echo "<INPUT TYPE=\"text\" NAME='qtde_conferir_os_$i' id='qtde_conferir_os_$i' value='$qtde' size='10' maxlength='10' style='text-align: right'>";
			echo "<INPUT TYPE='hidden' NAME='qtde_conferencia_$i' value='t'>";
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
				AND    tbl_extrato_conferencia.cancelada IS NOT TRUE
				";

		$resm = pg_exec($con, $sqlm);
		if(pg_numrows($resm)>0){
			$qtde_conferida = number_format(pg_result ($resm,0,qtde_conferida),0,',','.');
			echo "<td align='right' nowrap width='40'>&nbsp</td>";

			$qtde_conferir_os= $qtde - $qtde_conferida;
			if($qtde==$qtde_conferida){
				$total_conferir_os  = $total_conferir_os + $qtde_conferir_os;
				$total_conferida = $total_conferida + $qtde_conferida;
				if ($_GET['consulta']<>'sim') {
					echo "<td align='right' nowrap>";
					echo $qtde_conferir_os;
					echo "<INPUT TYPE=\"hidden\" NAME='qtde_conferir_os_$i' id='qtde_conferir_os_$i' value='$qtde_conferir_os'>";
					echo "</td>";
				}
				echo "<td align='right' nowrap>";
				echo $qtde_conferida;
				echo "<INPUT TYPE=\"hidden\" NAME='qtde_conferida_$i' id='qtde_conferida_$i' value='$qtde_conferida'>";
				echo "</td>";
			}else{
				$total_conferir_os  = $total_conferir_os + $qtde_conferir_os;
				$total_conferida = $total_conferida + $qtde_conferida;
				if ($_GET['consulta']<>'sim') {
					echo "<td align='right' nowrap>";
					echo "$qtde_conferir_os";
					echo "<INPUT TYPE='hidden' NAME='qtde_conferencia_$i' value='t'>";
					echo "</td>";
				}

				echo "<td align='right' nowrap>";
				echo $qtde_conferida;
				echo "<INPUT TYPE=\"hidden\" NAME='qtde_conferida_$i' id='qtde_conferida_$i' value='$qtde_conferida'>";
				echo "</td>";
			}


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
		/* hd 22096 */ 
		AND (admin IS NOT NULL OR lancamento in (103,104))";
	//echo $sql;
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
				if ($lancamento == 78 AND $valor>0){
					$valor = $valor * -1;
				}
			}else{ 
				$bgcolor= "bgcolor='#0000FF'";
				$color = " color: #FFFFFF; ";
			}

			//hd 22096 - lançamentos e Valores de ajuste de Extrato
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

echo "<td align='center' bgcolor='#FFFFFF'>&nbsp;</td>";
if ($mostra_conferencia != "1"){
	if ($_GET['consulta']<>'sim') {
		echo "<td align='right'>$total_conferir_os</td>";
	}
	echo "<td align='right'>$total_conferida</td>";
}else{
	echo "<td align='right'>123$total_conferida</td>";
}
echo "</tr>";

/******/

echo "</table><br><br>";

//hd 22389
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
			
			if ($i%2==0){
				$class     = 'table_line2';
				$class_obs = 'table_obs2';
			}else{
				$class     = 'table_line2';
				$class_obs = 'table_obs2';
			}

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
			echo "<TD colspan='9' height='1'>";

				echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='2' id='cancelamento_conferencia_$i' style='display:none; background-color:#FFDF7D' >";
				echo "<TR class='table_line2' height='20'>";
					echo "<TD>";
					echo "<strong>Informe a justificativa do cancelamento da conferência</strong><br>";
					echo "<input type='hidden' name='extrato_conferencia_aux_$i' id='extrato_conferencia_aux_$i' value='$extrato_conferencia'>";
					echo "<textarea name='justificativa_cancelamento_aux_$i' id='justificativa_cancelamento_aux_$i' cols='60' rows='3'></textarea>";
					echo "</TD>";
				echo "</TR>";
				echo "<TR class='table_line2' height='20'>";
					echo "<TD>";
					echo "<input type='button' value='Confirmar Cancelamento' onClick='confimar_cancelamento($i)'>";
					echo "</TD>";
				echo "</TR>";
				echo "</TABLE>";


			echo "</TD>";
			echo "</TR>";
	
			echo "<TR height='1'>";
				echo "<TD colspan='9' height='1'><hr style='height:1px;padding:none;margin:none'></TD>";
			echo "</TR>";
		}

		echo "</TABLE>";


		$sql = "SELECT tbl_admin.login,
						TO_CHAR(current_date,'DD/MM/YYYY') as data_Atual
				FROM tbl_admin where admin = $login_admin";
		$res = pg_exec($con, $sql);
		$admin_atual = pg_result($res,0,login);
		$data_atual = pg_result($res,0,data_atual);
		/*ZERAR A QTDE PARA OBRIGAR A GRAVAR A INFORMAÇÃO NOVAMENTE*/
		if(strlen($msg_erro)>0){
			$valor_conferencia_a_pagar = "";
		}

		
	}
}
echo "</form>";
?>


<?
echo "<p align='center'>";
#echo "<font style='font-size:12px'>Enviar Nota Fiscal de Prestação de Serviços para o fabricante<br>no valor de <b>R$ " . trim (number_format ($total_mo_posto + $total_mo_adicional + $total_adicional_pecas,2,",",".")) . "</b> descontados os tributos na forma da Lei.";


#echo "<p>";
#echo "<a href='new_extrato_posto_retornaveis.php?extrato=$extrato'>Peças Retornáveis</a>";

echo "<script>
	window.print();
</script>";


?>

<p><p>

