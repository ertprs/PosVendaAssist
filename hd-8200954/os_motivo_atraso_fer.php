<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

#include 'funcoes.php';

if (strlen($_GET['os']) > 0)   $os = trim ($_GET['os']);
if (strlen($_POST['os']) > 0)  $os = trim ($_POST['os']);

if ($btn_acao == "gravar") {
	if (strlen ($os) > 0) {
		$motivo_atraso = trim($_POST ['motivo_atraso']);
		$justificativa = $_POST ['justificativa'];
		
		//Takashi 02-05-07 trocou o if, pq estava deixando grava com espaco, pois grava-se 'Justificativa Reincidencia:' será necessário reavaliar, acertado de forma paliativa
		//if(strlen($justificativa)>0 AND $msg_erro == 0){
		// hd 1583
		if(strlen($justificativa)>0  AND strlen($motivo_atraso)>0 AND $msg_erro == 0){
/*
			$sql = "SELECT motivo_atraso 
				FROM tbl_os 
				WHERE tbl_os.os    = $os
				AND   tbl_os.fabrica = $login_fabrica";

			$res2 = pg_exec ($con,$sql);
			
			if (pg_numrows ($res2) > 0) {
				$motivo_atraso_cadastrado = trim(pg_result($res2,0,motivo_atraso));
				$teste = explode('Motivo atraso:',$motivo_atraso_cadastrado);
				if(strlen(trim($teste[1]))>0)$motivo_atraso2 .= $motivo_atraso_cadastrado;

			}
*//*
			$motivo_atraso_2 = 'Justificativa Reincidencia: '.$motivo_atraso.' '.$motivo_atraso2;
			$motivo_atraso   = 'Justificativa Reincidencia: '.$motivo_atraso.' ';
*/
			$sql = "UPDATE tbl_os SET 
					obs_reincidencia = '$motivo_atraso'
				WHERE  tbl_os.os    = $os
				AND    tbl_os.fabrica = $login_fabrica
				AND    os_reincidente IS TRUE;";

//			$res = @pg_exec($con,$sql);
			$foi = 'SIM';
			$msg_erro = pg_errormessage($con);
		}else{

			$sql = "UPDATE tbl_os SET motivo_atraso = '$motivo_atraso'
					WHERE  tbl_os.os    = $os
					AND    tbl_os.fabrica = $login_fabrica;";
//			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			$foi = 'SIM';
		}
$msg_erro = $sql;
	}
	if (strlen($msg_erro) == 0) {

/*		$sqlX =	"SELECT tbl_posto.email
				FROM tbl_posto
				JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto.posto = $login_posto;";
		$resX = pg_exec($con,$sqlX);
		if (pg_numrows($resX) == 1) {
			$posto_email = pg_result($resX,0,0);
		}
	
		##### Envia E-mail #####
		$assunto = "Motivo informado pelo posto $login_codigo_posto";
		
		$mensagem  = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
		$mensagem .= "Motivo informado pelo posto <b>" . $login_codigo_posto . " - " . $login_nome . "</b>:";
		$mensagem .= "<br><br>\n";
		$mensagem .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$mensagem .= $motivo_atraso;
		$mensagem .= "</font>";
		
		$cabecalho	.= "MIME-Version: 1.0\n";
		$cabecalho	.= "Content-type: text/html; charset=iso-8859-1\n";
		$cabecalho .= "From: $login_codigo_posto - $login_nome <$posto_email>\n";
		$cabecalho .= "To: Michel Clemente <mclemente@blackedecker.com.br>, Anderson Camilo <acamilo@blackedecker.com.br>, Christoph Schäfer <cschafer@blackedecker.com.br>\n";
//		$cabecalho .= "From: Rodrigo <rodrigo@telecontrol.com.br>\n";
//		$cabecalho .= "To: Suporte <helpdesk@telecontrol.com.br>\n";
		$cabecalho .= "Subject: $assunto\n";
		$cabecalho .= "Return-Path: Suporte <helpdesk@telecontrol.com.br>\n";
		$cabecalho	.= "X-Priority: 1\n";
		$cabecalho	.= "X-MSMail-Priority: High\n";
		$cabecalho	.= "X-Mailer: PHP/" . phpversion();

		if ( !mail("", $assunto, $mensagem, $cabecalho) ) {
			$msg_erro = " NÃO enviou o e-mail. ";
		}else{
			header("Location: os_consulta_lite.php");
			exit;
		}*/
		if($foi == 'SIM'){
			if($justificativa<>'ok' AND $msg_erro == 0){
				
				echo "<script language=\"javascript\">this.close(); </script>";exit;
			}
			header("Location: os_item.php?os=$os");
			exit;
		}else{
			$msg_erro = "Está errado";
		}
	}
}

if (strlen($os) > 0) {
	#----------------- Le dados da OS --------------
	$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
	$res2 = @pg_exec ($con,$sql);

	$sql = "SELECT  tbl_os.*                                                 ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura2   ,
			tbl_produto.produto                                              ,
			tbl_produto.referencia                                           ,
			tbl_produto.descricao                                            ,
			tbl_produto.linha                                                ,
			tbl_linha.nome AS linha_nome                                     ,
			tbl_posto_fabrica.codigo_posto                                   ,
			tbl_defeito_constatado.descricao AS defeito_constatado_descricao ,
			tbl_causa_defeito.descricao      AS causa_defeito_descricao      ,
			tbl_os.motivo_atraso                                             ,
			tbl_os.obs_reincidencia                                          ,
			tbl_os_extra.os_reincidente      AS reincidente_os
		FROM    tbl_os
		JOIN    tbl_os_extra USING (os)
		JOIN    tbl_posto USING (posto)
		JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
							  AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN    tbl_produto USING (produto)
		LEFT JOIN    tbl_linha   ON tbl_produto.linha = tbl_linha.linha
		LEFT JOIN    tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
		LEFT JOIN    tbl_causa_defeito      ON tbl_causa_defeito.causa_defeito           = tbl_os.causa_defeito
		WHERE   tbl_os.os = $os";
//echo $sql;
//	if ($ip=="201.13.179.45") {echo $sql; exit;}
	$res = pg_exec ($con,$sql) ;
	
	$defeito_constatado = pg_result ($res,0,defeito_constatado);
	$data_abertura      = pg_result ($res,0,data_abertura2);
	$nota_fiscal        = pg_result ($res,0,nota_fiscal);
	$causa_defeito      = pg_result ($res,0,causa_defeito);
	$linha              = pg_result ($res,0,linha);
	$linha_nome         = pg_result ($res,0,linha_nome);
	$consumidor_nome    = pg_result ($res,0,consumidor_nome);
	$consumidor_fone    = pg_result ($res,0,consumidor_fone);
	$sua_os             = pg_result ($res,0,sua_os);
	$produto_os         = pg_result ($res,0,produto);
	$produto_referencia = pg_result ($res,0,referencia);
	$produto_descricao  = pg_result ($res,0,descricao);
	$produto_serie      = pg_result ($res,0,serie);
	$motivo_atraso      = pg_result ($res,0,motivo_atraso);
	$obs                = pg_result ($res,0,obs);
	$codigo_posto       = pg_result ($res,0,codigo_posto);
	$duplicada          = pg_result ($res,0,os_reincidente);
	$os_reincidente     = pg_result ($res,0,reincidente_os);
	$obs_reincidente    = pg_result ($res,0,obs_reincidencia);
	$defeito_constatado_descricao = pg_result ($res,0,defeito_constatado_descricao);
	$causa_defeito_descricao = pg_result ($res,0,causa_defeito_descricao);

	//--=== Tradução para outras linguas ============================= Raphael HD:1212
	$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto_os AND upper(idioma) = '$sistema_lingua'";

	$res_idioma = @pg_exec($con,$sql_idioma);
	if (@pg_numrows($res_idioma) >0) {
		$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
	}

	if ($sistema_lingua=='ES' and strlen(trim($defeito_constatado))>0) {
		$sql = "SELECT descricao
				FROM tbl_defeito_constatado_idioma
				WHERE idioma             = 'ES'
				AND   defeito_constatado = $defeito_constatado";
		
		$res = @pg_exec ($con, $sql);

		if (pg_numrows($res) > 0) $defeito_constatado_descricao = trim(pg_result($res,0,0));
	}
	//--=== Tradução para outras linguas ================================================


	$justificativa = $_GET["justificativa"];

	if (strlen($os_reincidente) > 0) {
		$sql = "SELECT tbl_os.sua_os
				FROM   tbl_os
				WHERE  tbl_os.os      = $os_reincidente
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = @pg_exec ($con,$sql) ;
		
		if (pg_numrows($res) > 0) $sua_os_reincidente = trim(pg_result($res,0,sua_os));
	}
}

$title = "Telecontrol - Assistência Técnica - Motivo Atraso da Ordem de Serviço";
if ($sistema_lingua == 'ES')  $title = "Servicio Técnico - Razón de atrazo de la orden de servicio";
$layout_menu = 'os';
include "cabecalho.php";


if (strlen ($msg_erro) > 0){
?>

<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
<? 
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro; 
?>
		</font></b>
	</td>
</tr>
</table>

<? } ?>


<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="os" value="<?echo $os?>">
		<input type="hidden" name="justificativa" value="<?echo $justificativa?>">
		<p>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Abertura</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $data_abertura?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua=='ES') echo "Usuário"; else echo "Consumidor";?></font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_nome ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua == 'ES') echo "Teléfono"; else echo "Telefone"?></font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_fone ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua == 'ES') echo "Factura comercial"; else echo "Nota Fiscal"?></font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<B><? echo $nota_fiscal; ?></B>
				</font>
			</td>


		</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua == 'ES') echo "Producto"; else echo "Produto"?></font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_referencia . " - " . $produto_descricao?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua == 'ES') echo "N. serie"; else echo "N. Série"?></font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
				</font>
			</td>
			<? if ($login_fabrica <> 5) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua=='ES') echo "Defecto constatado"; else echo "Defeito Constatado";?></font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<B><? echo $defeito_constatado_descricao; ?></B>
				</font>
			</td>
			<? } ?>
			
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<? 
		if ($duplicada=='t'){ 
			echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' width='700'><tr><td align='center'><b>OS REINCIDENTE</b>";
			if($login_fabrica == 1) echo "<br><font size='2'>Gentileza justificar abaixo se esse atendimento tem procedência, pois foi localizado num período menor ou igual a 90 dias outra(s) OS(s) concluída(s) pelo seu posto com os mesmos dados de nota fiscal e produto. Se o lançamento estiver incorreto, solicitamos não fazer a justificativa. Nesse caso, entre em consulta de ordem de serviço de consumidor e faça a exclusão da OS.</font>";
			echo "</td></tr></table>";

			if (strlen($os_reincidente) > 0 OR $reincidencia =='t') {
				
				$sql = "SELECT  tbl_os_status.status_os,tbl_os_status.observacao 
					FROM tbl_os_extra JOIN tbl_os_status USING(os)
					WHERE tbl_os_extra.os = $os
					AND tbl_os_status.status_os IN (67,68,70)";
				$res1 = pg_exec ($con,$sql);
				
				if (pg_numrows ($res1) > 0) {
					$status_os  = trim(pg_result($res1,0,status_os));
					$observacao  = trim(pg_result($res1,0,observacao));
				}
			
				echo "<br><table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
				echo "<tr>";
				if ($sistema_lingua=='ES') 
					echo "<td align='center'><b><font size='1'>ATENCIÓN</font></b></td>";
				else 
					echo "<td align='center'><b><font size='1'>ATENÇÃO</font></b></td>";
				
				echo "</tr>";
				echo "<tr>";
				echo "<td align='center'><font size='1'>";
			
				if(strlen($os_reincidente)>0 ){
					$sql = "SELECT  tbl_os.sua_os,
							tbl_os.serie
							FROM    tbl_os
							WHERE   tbl_os.os = $os_reincidente;";
					$res1 = pg_exec ($con,$sql);
					
					$sos   = trim(pg_result($res1,0,sua_os));
					$serie_r = trim(pg_result($res1,0,serie));
					if($login_fabrica==1)$sos=$codigo_posto.$sos;
				}else{
					//CASO NÃO TENHA A REINCIDENCIA NÃO TENHA SIDO APONTADA, PROCURA PELA REINCIDENCIA NA SERIE
					$sql = "SELECT os,sua_os,posto
						FROM tbl_os
						JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
						WHERE   serie   = '$produto_serie'
						AND     os     <> $os
						AND     fabrica = $login_fabrica
						AND     tbl_produto.numero_serie_obrigatorio IS TRUE ";
					if($login_fabrica == 3) $sql .= " AND tbl_produto.linha = 3";
			
					$res2 = pg_exec ($con,$sql);
if ($sistema_lingua == 'ES') echo "ORDEN DE SERVICO CON NUMERO DE SERIE: <u>$produto_serie</u> REINCIDENTE. ORDEN DE SERVICIO ANTERIOR:<BR>";
					else echo "ORDEM DE SERVIÇO COM NÚMERO DE SÉRIE: <u>$produto_serie</u> REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR:<br>";
			
					if (pg_numrows ($res2) > 0) {
						for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
							$sos_reinc  = trim(pg_result($res2,$i,sua_os));
							$os_reinc   = trim(pg_result($res2,$i,os));
							$posto_reinc   = trim(pg_result($res2,$i,posto));
							if($posto_reinc == $login_posto){
								echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
							}else{
								echo "» $sos_reinc<br>";
							}
							
						}
					}
			
				}
			
				if($status_os==67){
			
					echo "ORDEM DE SERVIÇO COM NÚMERO DE SÉRIE: <u>$produto_serie</u> REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR:<br>";
					
					if ($login_fabrica == 11) {
						$sql = "SELECT os_reincidente
								FROM tbl_os_extra 
								WHERE os= $os";
						$res2 = pg_exec($con,$sql);

						$osrein = pg_result($res2,0,os_reincidente);

						if (pg_numrows($res2) > 0) {
							$sql = "SELECT os,sua_os
									FROM tbl_os
									WHERE   serie   = '$produto_serie'
									AND     os      = $osrein
									AND     fabrica = $login_fabrica";
						}
						$res2 = pg_exec($con,$sql);

						if (pg_numrows($res2) > 0) {
							$sua_osrein = pg_result($res2,0,sua_os);
							echo "<a href='os_press.php?os=$osrein' target='_blank'>» $sua_osrein</a>";
						}
					} else {
						$sql = "SELECT os,sua_os,posto
							FROM tbl_os
							JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
							WHERE   serie   = '$produto_serie'
							AND     os     <> $os
							AND     fabrica = $login_fabrica
							AND     tbl_produto.numero_serie_obrigatorio IS TRUE ";
						if($login_fabrica == 3) $sql .= " AND tbl_produto.linha = 3";
				
						$res2 = pg_exec ($con,$sql);
				
						if (pg_numrows ($res2) > 0) {
							for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
								$sos_reinc  = trim(pg_result($res2,$i,sua_os));
								$os_reinc   = trim(pg_result($res2,$i,os));
								$posto_reinc   = trim(pg_result($res2,$i,posto));
								if($posto_reinc == $login_posto){
									echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
								}else{
									echo "» $sos_reinc<br>";
								}
							}
						}
					}
				}elseif($status_os==68){

if ($sistema_lingua == 'ES') echo "ORDEN DE SERVICIO COM  MISMO DISTRIBUIDOR Y FACTURA COMERCIAL REINCIDENTE. ORDEN DE SERVICIO ANTERIOR:";
	else echo "ORDEM DE SERVIÇO COM MESMA REVENDA E NOTA FISCAL REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR: ";
echo "<a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
				}elseif($status_os==70){
if ($sistema_lingua == 'ES') echo "ORDEN DE SERVICIO CON MISMO DISTRIBUIDOR, FACTURA COMERCIAL Y PRODUCTO REINCIDENTE. ORDEN DE SERVICIO ANTERIOR";
				else	echo "ORDEM DE SERVIÇO COM MESMA REVENDA, NOTA FISCAL E PRODUTO REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR: ";
				echo "<a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
				}else{
					echo "OS Reincidente:<a href='os_press.php?os=$os_reincidente' target = '_blank'>$sos</a>";
				
				}
				echo "";
				echo "</font></td>";
				echo "</tr>";
				echo "</table>";
			}
		}
		?>
		<br>
		<? 
		if($justificativa=='ok'){
			
			$teste = explode('Motivo atraso:',$motivo_atraso);
			if(strlen(trim($teste[1]))>0)echo  "<font color='FF0000'>Motivo Atraso:".$teste[1].'</font><br>';
			$motivo_atraso = "";
		}
		?>

		<FONT SIZE="2"><B><? if($justificativa=='ok')echo "Justificativa:";else 
		if ($sistema_lingua=='ES') echo "Razón del atraso:"; else echo "Motivo do atraso:";?></B></FONT>
		<br>

		<textarea NAME="motivo_atraso" cols="70" rows="5" class="frm" maxlength='40'><? echo $motivo_atraso; ?></textarea>
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<?if ($sistema_lingua == 'ES') {?>
			<img src='imagens/btn_guardar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Guardar" border='0' style="cursor:pointer;">
		<?} else {?>
			<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar" border='0' style="cursor:pointer;">
		<?}?>
	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php";?>
