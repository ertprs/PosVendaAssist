<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";

include "autentica_admin.php";
include "funcoes.php";

if (strlen($_POST['extrato']) > 0) $extrato = trim($_POST['extrato']);
else                               $extrato = trim($_GET['extrato']);

if (strlen($_POST['posto']) > 0)   $posto   = trim($_POST['posto']);
else                               $posto   = trim($_GET['posto']);

$btn_acao = trim($_POST['btn_acao']);

if ($btn_acao == "gravar") {
	$extrato                   = trim($_POST['extrato']);
	$nf_conferencia            = trim($_POST['nf_conferencia']);
	$caixa_conferencia         = trim($_POST['caixa_conferencia']);
	$obs_fabricante_conferencia= trim($_POST['obs_fabricante_conferencia']);
	$obs_posto_conferencia     = trim($_POST['obs_posto_conferencia']);
	$qtde_item_enviada         = trim($_POST['qtde_item_enviada']);
	$valor_conferencia         = trim($_POST['valor_conferencia']);
	$valor_conferencia_a_pagar = trim($_POST['valor_conferencia_a_pagar']);
	$data_nf_conferencia       = trim($_POST['data_nf_conferencia']);
	$previsao_pagamento_conferencia = trim($_POST['previsao_pagamento_conferencia']);

	if(strlen($nf_conferencia)>0)            $xnf_conferencia = "'".$nf_conferencia."'";
	else                                     $msg_erro        = "Digite o número da Nota Fiscal";

	if(strlen($caixa_conferencia)>0)         $xcaixa_conferencia = "'".$caixa_conferencia."'";
	else                                     $xcaixa_conferencia = "null";
	
	if(strlen($obs_fabricante_conferencia)>0) $xobs_fabricante = "'".$obs_fabricante_conferencia."'";
	else                                      $xobs_fabricante = "null";

	if(strlen($obs_posto_conferencia)>0)     $xobs_posto = "'".$obs_posto_conferencia."'";
	else                                     $xobs_posto = "null";

	if(strlen($valor_conferencia)>0)         $xvalor_conferencia = "'".fnc_limpa_moeda($valor_conferencia)."'";
	else                                     $msg_erro           = "Digite o valor NF. ORIGINAL ";

	if(strlen($valor_conferencia_a_pagar)>0) $xvalor_conferencia_a_pagar = "'".fnc_limpa_moeda($valor_conferencia_a_pagar)."'";
	else                                      $msg_erro                  = "Digite o valor NF. A PAGAR ";

	if(strlen($data_nf_conferencia)>0)       $xdata_nf_conferencia = "'".formata_data($data_nf_conferencia)."'";
	else                                     $xdata_nf_conferencia = "null";

	if(strlen($previsao_pagamento_conferencia)>0) $xprevisao_pagamento = "'".formata_data($previsao_pagamento_conferencia)."'";
	else                                          $xprevisao_pagamento = "null";


	$res = pg_exec ($con,"BEGIN TRANSACTION");

	#HD 47236 - Apaga as conferencias canceladas
	if(strlen($msg_erro)==0){
		#$sql = "DELETE FROM tbl_extrato_conferencia
		#		WHERE extrato = $extrato
		#		AND   cancelada IS TRUE;";
		/*Ao inves de apagar, joga para o extrato do posto de testes. Assim fica armazenado para possíveis verificações */
		#$sql = "UPDATE tbl_extrato_conferencia SET extrato = 367205, justificativa_cancelamento = justificativa_cancelamento || ' ->Conferencia excluida em '||CURRENT_TIMESTAMP||' pelo admin $login_admin . Referencia extrato: $extrato'
		#		WHERE extrato = $extrato
		#		AND   cancelada IS TRUE;";
		#$res = @pg_exec ($con,$sql);
		#HD 52989 - Nao excluir a conferencia cancelada. Deve ser mantida como historico
	}

	if(strlen($msg_erro)==0){
		$sql = "INSERT INTO tbl_extrato_conferencia(
							extrato             ,
							data_conferencia    ,
							nota_fiscal         ,
							data_nf             ,
							valor_nf            ,
							valor_nf_a_pagar    ,
							caixa               ,
							obs_fabricante      ,
							obs_posto           ,
							previsao_pagamento  ,
							admin               
							)VALUES(
							$extrato                   ,
							current_timestamp          ,
							$xnf_conferencia           ,
							$xdata_nf_conferencia      ,
							$xvalor_conferencia        ,
							$xvalor_conferencia_a_pagar,
							$xcaixa_conferencia        ,
							$xobs_fabricante           ,
							$xobs_posto                ,
							$xprevisao_pagamento       ,
							$login_admin
							);";
		#echo nl2br($sql)."<BR>";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen ($msg_erro) == 0 AND strlen($qtde_item_enviada)>0){
			$sql = "select currval('seq_extrato_conferencia') as extrato_conferencia;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$extrato_conferencia = trim(pg_result($res,0,extrato_conferencia));

			if (strlen ($msg_erro) == 0) {
				for($x=0; $x<=$qtde_item_enviada; $x++){
					$linha                = trim($_POST['linha_'.$x]);
					$distrib_posto        = trim($_POST['distrib_posto_'.$x]);
					$mao_de_obra_posto    = trim($_POST['mao_de_obra_posto_'.$x]);
					$mao_de_obra_unitario = trim($_POST['unitario_'.$x]);
					$qtde                 = trim($_POST['qtde_'.$x]);
					$qtde_conferir_os     = trim($_POST['qtde_conferir_os_'.$x]);
					$qtde_conferencia     = trim($_POST['qtde_conferencia_'.$x]);
					$qtde_conferida       = trim($_POST['qtde_conferida_'.$x]);
					
					#echo "Linha $x - $qtde_conferencia<br>";
					if($qtde_conferencia=='t'){
						if(strlen($linha)>0)             $xlinha = "'".$linha."'"; 
						else                             $xlinha = "null";

						if(strlen($distrib_posto)>0)     $xdistrib_posto = "'".$distrib_posto."'"; 
						else                             $xdistrib_posto = "null";
						
						if(strlen($qtde)>0){
							$qtde = str_replace (",","",$qtde);
							$qtde = str_replace (".","",$qtde);
							$qtde = str_replace ("-","",$qtde);
							$qtde = str_replace ("/","",$qtde);
							$qtde = str_replace (" ","",$qtde);
						}

						if(strlen($qtde_conferir_os)>0){
							$qtde_conferir_os = str_replace (",","",$qtde_conferir_os);
							$qtde_conferir_os = str_replace (".","",$qtde_conferir_os);
							$qtde_conferir_os = str_replace ("-","",$qtde_conferir_os);
							$qtde_conferir_os = str_replace ("/","",$qtde_conferir_os);
							$qtde_conferir_os = str_replace (" ","",$qtde_conferir_os);
						}

						if(strlen($qtde_conferida)>0){
							$qtde_conferida = str_replace (",","",$qtde_conferida);
							$qtde_conferida = str_replace (".","",$qtde_conferida);
							$qtde_conferida = str_replace ("-","",$qtde_conferida);
							$qtde_conferida = str_replace ("/","",$qtde_conferida);
							$qtde_conferida = str_replace (" ","",$qtde_conferida);
						}

						if(strlen($qtde_conferir_os)==0){
							$msg_erro = "Digite a quantidade enviada para a M.O $mao_de_obra_posto";
						}
						
						if($qtde_conferir_os > $qtde  ){
							$msg_erro = "Quantidade enviada maior que a quantidade de OSs";
						}

						if($qtde_conferir_os > ($qtde -$qtde_conferida )){
							$msg_erro = "Quantidade enviada maior que a quantidade de OSs";
						}

						if(strlen($mao_de_obra_posto)>0) $xmao_de_obra_posto = "'".fnc_limpa_moeda($mao_de_obra_posto)."'";
						else                             $xmao_de_obra_posto = "null";

						if(strlen($mao_de_obra_unitario)>0) $xmao_de_obra_unitario = "'".fnc_limpa_moeda($mao_de_obra_unitario)."'";
						else                             $xmao_de_obra_unitario= "null";

						#echo "qtde_conferir_os: $qtde_conferir_os";
						if(strlen($qtde_conferir_os)>0 AND strlen($msg_erro)==0 ){
							$sql = "INSERT INTO tbl_extrato_conferencia_item(
											extrato_conferencia  ,
											linha                ,
											mao_de_obra          ,
											mao_de_obra_unitario ,
											distribuidor         ,
											qtde_conferida       
										)VALUES(
											$extrato_conferencia ,
											$xlinha               ,
											$xmao_de_obra_posto   ,
											$xmao_de_obra_unitario,
											$xdistrib_posto       ,
											$qtde_conferir_os
										);";
							#echo nl2br($sql)."<BR>";
							$res = @pg_exec ($con,$sql);
							$msg_erro = pg_errormessage($con);
						}
					}
				}//fim for
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$extrato = str_replace("'","", $extrato);
		header("location: $PHP_SELF?extrato=$extrato&posto=$posto");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

#HD 47236
if($btn_acao=="cancelar"){
	$extrato                    = trim($_POST['extrato']);
	$extrato_conferencia_id        = trim($_POST['extrato_conferencia_id']);
	$justificativa_cancelamento = trim($_POST['justificativa_cancelamento']);

	if(strlen($justificativa_cancelamento)>0) $xjustificativa_cancelamento = "'".$justificativa_cancelamento."'";
	else                                      $msg_erro                    = "Digite a justificativa do cancelamento";

	if(strlen($extrato)==0){
		 $msg_erro = "Extrato não selecionado.";
	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if(strlen($msg_erro)==0){
		$sql = "UPDATE tbl_extrato_conferencia SET 
								cancelada                  = 't',
								admin_cancelou             = $login_admin,
								justificativa_cancelamento = $xjustificativa_cancelamento,
								data_cancelada             = CURRENT_TIMESTAMP
				WHERE extrato = $extrato
				AND   extrato_conferencia = $extrato_conferencia_id
				AND   cancelada IS NOT TRUE ;";
		#echo nl2br($sql)."<BR>";
		#exit;
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$extrato = str_replace("'","", $extrato);
		header("location: $PHP_SELF?extrato=$extrato&posto=$posto");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "financeiro";
$title = "CONSULTA E MANUTENÇÃO DE EXTRATOS DO POSTO";

include "cabecalho.php";
?>


<? include "javascript_calendario.php"; ?>
<script src='js/jquery.alphanumeric.js'></script>
<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$("input[@rel=data]").maskedinput("99/99/9999");
		$("input[name=nf_conferencia]").numeric();
	});
</script>

<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
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
	echo "<DIV class='masg_erro' style='width:700px;'>".$msg_erro."</DIV>";
}

?>
<div style='width:700px;' class='texto_avulso'>
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
				distrib.nome_fantasia AS distrib_nome                                  ,
				distrib.posto    AS distrib_posto                                 
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
echo "<br />";
$codigo_posto2 = pg_result ($resX,0,codigo_posto);

if($login_fabrica == 3){
	include('posto_extrato_ano_britania.php');
}
?>
</div>
<?
if($login_fabrica == 3){
	if(pg_numrows($res) > 0){
	echo "<table width='700'>";
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
echo "<table width='700' align='center' border='0' cellspacing='1' class='tabela'>";
echo "<tr class='titulo_coluna' >";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Recusadas</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "<td align='center' nowrap >Pago via</td>";
echo "<td align='center' nowrap >&nbsp;</td>";
if($login_fabrica==3){
	echo "<td align='center' bgcolor='#FFFFFF' nowrap >&nbsp;</td>";
	echo "<td align='center' nowrap >Conferir OSs</td>";
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
	
	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	echo "<tr bgcolor='$cor'>";

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
			echo "<INPUT TYPE=\"text\" NAME='qtde_conferir_os_$i' id='qtde_conferir_os_$i' value='$qtde' size='10' maxlength='10' style='text-align: right' class='frm'>";
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
				echo "<td align='right' nowrap>";
				echo $qtde_conferir_os;
				echo "<INPUT TYPE=\"hidden\" NAME='qtde_conferir_os_$i' id='qtde_conferir_os_$i' value='$qtde_conferir_os' class='frm'>";
				echo "</td>";

				echo "<td align='right' nowrap>";
				echo $qtde_conferida;
				echo "<INPUT TYPE=\"hidden\" NAME='qtde_conferida_$i' id='qtde_conferida_$i' value='$qtde_conferida' class='frm'>";
				echo "</td>";
			}else{
				$total_conferir_os  = $total_conferir_os + $qtde_conferir_os;
				$total_conferida = $total_conferida + $qtde_conferida;

				echo "<td align='right' nowrap>";
				echo "<INPUT TYPE=\"text\" NAME='qtde_conferir_os_$i' id='qtde_conferir_os_$i' value='$qtde_conferir_os' size='10' maxlength='10' style='text-align: right' class='frm'>";
				echo "<INPUT TYPE='hidden' NAME='qtde_conferencia_$i' value='t'>";
				echo "</td>";

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
			echo "<TD>&nbsp;</TD>";
			echo "</tr>";
			$total_mo_posto = $valor + $total_mo_posto;
		}
	}
}


echo "<tr class='titulo_coluna' >";
echo "<td align='center'>TOTAIS</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_qtde_recusada  ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='center' bgcolor='#FFFFFF'>&nbsp;</td>";
if ($mostra_conferencia != "1"){
	echo "<td align='right'>$total_conferir_os</td>";
	echo "<td align='right'>$total_conferida</td>";
}else{
	echo "<td align='right'>$total_conferida</td>";
}
echo "</tr>";

/******/
if($login_fabrica==3 AND $mostra_conferencia==1){
echo "<tr bgcolor='#F1F4FA'>";
	echo "<td colspan='8' style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: right;'>NF</td>";
	echo "<td align='center' >";
		echo "<INPUT TYPE='text' NAME='nf_conferencia' value='$nf_conferencia' size='10' maxlength='10' class='frm'>";
	echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#F7F5F0'>";
	echo "<td colspan='8' style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: right;'>DATA NF</td>";
	echo "<td align='center' >";
		echo "<INPUT TYPE=\"text\" NAME='data_nf_conferencia' value='$data_nf_conferencia' size='10' maxlength='10' rel='data'   class='frm'>";
	echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#F1F4FA'>";
	echo "<td colspan='8' style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: right;'>VALOR NF. ORIGINAL</td>";
	echo "<td align='center' >";
		echo "<INPUT TYPE=\"text\" NAME='valor_conferencia' value='$valor_conferencia' size='10' maxlength='10' style='text-align: right' class='frm'>";
	echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#F7F5F0'>";
	echo "<td colspan='8' style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: right;'>VALOR NF. A PAGAR</td>";
	echo "<td align='center' >";
	echo "<INPUT TYPE='text' NAME='valor_conferencia_a_pagar' id='valor_conferencia_a_pagar' VALUE='$valor_conferencia_a_pagar' onFocus='calcula_total(); checarNumero(this);' SIZE='10'  style='text-align: right' class='frm'> ";
	echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#F1F4FA'>";
	echo "<td colspan='8' style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: right;'>CAIXA</td>";
	echo "<td align='center' >";
	echo "<INPUT TYPE=\"text\" NAME='caixa_conferencia' value='$caixa_conferencia' size='10' maxlength='10' class='frm'>";
	echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#F7F5F0'>";
	echo "<td colspan='8' style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: right;'>OBS Britânia</td>";
	echo "<td align='center' >";
	echo "<INPUT TYPE=\"text\" NAME='obs_fabricante_conferencia' value='$obs_fabricante_conferencia' size='10' maxlength='225' class='frm'>";
	echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#F1F4FA'>";
	echo "<td colspan='8' style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: right;'>OBS Posto</td>";
	echo "<td align='center' >";
		echo "<INPUT TYPE=\"text\" NAME='obs_posto_conferencia' value='$obs_posto_conferencia' size='10' maxlength='225' class='frm'>";
	echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#F7F5F0'>";
	echo "<td colspan='8' style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: right;'>PREVISÃO PGTO</td>";
	echo "<td align='center' >";
		echo "<INPUT TYPE=\"text\" NAME='previsao_pagamento_conferencia' value='$previsao_pagamento_conferencia' size='10' maxlength='10' rel='data' class='frm' >";
	echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#F1F4FA'>";
	echo "<td colspan='8' style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: center;'>";
		echo "&nbsp;";
	echo "</td>";
	echo "<td style='font-size:12px ; color:#000000 ; font-weight:bold; text-align: center;'>";
		echo "<input type='hidden' name='extrato' value='$extrato'>";
		echo "<input type='hidden' name='posto'   value='$posto'>";
		echo "<input type='hidden' name='btn_acao' value=''>";
		echo "<input type='hidden' name='qtde_item_enviada' value='$qtde_item_enviada'>";
		/*echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: 
				if (confirm('Confirmar a conferência?')){
					if (document.frm_conferencia.btn_acao.value == '' ) { 
						document.frm_conferencia.btn_acao.value='gravar' ; 
						document.frm_conferencia.submit()
					} else { 
						alert ('Aguarde ') 
					}
				}
			\" ALT=\"Gravar\" border='0' style=\"cursor:pointer;\">";*/
	echo "</td>";
echo "</tr>";
}
/******/

echo "</table>";


if ($login_fabrica==3){
	echo "<p><input type='button' value='Download das OS's' onclick=\"window.location='extrato_posto_mao_obra_os_download.php?extrato=$extrato'\"></p>";
}

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
	
			#HD 47236 - Cancelamento das conferencias anteriores
			//59165 - ALTERANDO O CANCELAMENTO PARA CADA EXTRATO_LANÇAMENTO E NÃO O EXTRATO TODO.
			if ($login_fabrica==3 AND $mostra_conferencia != 1){
				echo "<TFOOTY>";
					echo "<TR>";
						echo "<TD height='20' colspan='9' align='center'><a href='javascript: MostraEscondeCancelamento($i)' style='color:#FF0000' class='table_line2'>Cancelar Conferência do Mês</a></TD>";
					echo "</TR>";
				echo "</TFOOTY>";
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

		if ($mostra_conferencia != "1"){
			echo "<TABLE width='650' border='0' align='center' cellspacing='1' cellpadding='0'>";
				echo "<TR colspan='9' bgcolor='#FFFFFF'>";
				echo "&nbsp;";
				echo "</TR>";
				echo "<TR>";
					echo "<TD class='table_line3'>DT. CONF. <BR> &nbsp; <INPUT TYPE='text' NAME='data' SIZE='10' VALUE='$data_atual' readonly> &nbsp; </TD>";
					echo "<TD class='table_line3'>N.F. <BR> &nbsp; <INPUT TYPE='text' NAME='nf_conferencia' VALUE='$nf_conferencia' SIZE='10' MAXLENGTH='10'> &nbsp; </TD>";
					echo "<TD class='table_line3'>DT. NF <BR> &nbsp; <INPUT TYPE='text' NAME='data_nf_conferencia' VALUE='$data_nf_conferencia' SIZE='10' MAXLENGTH='10' rel='data' > &nbsp; </TD>";
					echo "<TD class='table_line3'>VALOR NF. ORIGINAL <BR> &nbsp;<INPUT TYPE='text' NAME='valor_conferencia' VALUE='$valor_conferencia' SIZE='10'> &nbsp; </TD>";
				echo "</TR>";
				echo "<TR>";
					echo "<TD class='table_line3'>VALOR NF. A PAGAR <BR> &nbsp;<INPUT TYPE='text' NAME='valor_conferencia_a_pagar'
					VALUE='$valor_conferencia_a_pagar' id='valor_conferencia_a_pagar' NAME='$valor_conferencia_a_pagar' onFocus='calcula_total();  checarNumero(this);' SIZE='10'> &nbsp; </TD>";
					echo "<TD class='table_line3'>CAIXA <BR> &nbsp; <INPUT TYPE='text' NAME='caixa_conferencia' VALUE='$caixa_conferencia' SIZE='10'> &nbsp; </TD>";
					echo "<TD class='table_line3'>PREV. PGTO <BR>&nbsp; &nbsp; <INPUT TYPE='text' NAME='previsao_pagamento_conferencia' VALUE='$previsao_pagamento_conferencia' SIZE='10' MAXLENGTH='10' rel='data'> &nbsp; </TD>";
					echo "<TD class='table_line3'>ADM<BR> &nbsp; <INPUT TYPE='text' NAME='admin_atual' SIZE='".strlen($admin_atual)."' VALUE='$admin_atual' readonly> &nbsp; </TD>";
				echo "</TR>";
				echo "<TR>";
					echo "<TD class='table_line3' colspan='2'>OBSERVAÇÃO FABRICA <BR> &nbsp; <INPUT TYPE='text' NAME='obs_fabricante_conferencia' 
					 VALUE='$obs_fabricante_conferencia' SIZE='30'> &nbsp; </TD>";
					echo "<TD class='table_line3' colspan='2'>OBSERVAÇÃO POSTO <BR> &nbsp; <INPUT TYPE='text' NAME='obs_posto_conferencia' VALUE='$obs_posto_conferencia' SIZE='30'> &nbsp; </TD>";
				echo "</TR>";
				echo "<TR>";
					echo "<TD class='table_line3' colspan='9'>";
					echo "<input type='hidden' name='extrato' value='$extrato'>";
					echo "<input type='hidden' name='qtde_item_enviada' value='$qtde_item_enviada'>";
					
					echo "<input type='hidden' name='btn_acao' value=''>";
					/*echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_conferencia.btn_acao.value == '' ) { document.frm_conferencia.btn_acao.value='gravar' ; document.frm_conferencia.submit() } else { alert ('Aguarde ') }\" ALT=\"Gravar\" border='0' style=\"cursor:pointer;\">";*/
					echo "</TD>";
				echo "</TR>";
			echo "</TABLE>";
		}
	}
}
echo "</form>";
?>

<!-- Form utilizado para cancelamento de conferencia -->
<form name='frm_cancelar_conferencia' action='<?=$PHP_SELF?>' method='POST'>
<input type='hidden' name='extrato'                    value='<?=$extrato?>'>
<input type='hidden' name='extrato_conferencia_id'    value=''                                 id='extrato_conferencia_id'>
<input type='hidden' name='posto'                      value='<?=$posto?>'>
<input type='hidden' name='btn_acao'                   value='cancelar'>
<input type='hidden' name='justificativa_cancelamento' value='<?=$justificativa_cancelamento?>' id='justificativa_cancelamento'>
</form>

<?
echo "<p align='center'>";
#echo "<font style='font-size:12px'>Enviar Nota Fiscal de Prestação de Serviços para o fabricante<br>no valor de <b>R$ " . trim (number_format ($total_mo_posto + $total_mo_adicional + $total_adicional_pecas,2,",",".")) . "</b> descontados os tributos na forma da Lei.";

echo "<BR><p>";

echo "<input type='button' value='Imprimir' onclick=\"window.location='extrato_posto_mao_obra_impressao.php?extrato=$extrato&posto=$posto'\">";

echo "<BR><p>";

echo "<a href='extrato_posto_britania.php'>Outro extrato</a>";


#echo "<p>";
#echo "<a href='new_extrato_posto_retornaveis.php?extrato=$extrato'>Peças Retornáveis</a>";

?>

<p><p>

<? include "rodape.php"; ?>
