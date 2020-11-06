<?
//criado takashi 29-12-2006 HD - 922.
//regra - Admin entra com um valor, sistema lista todos os extratos menores que esse valor e acumula, desde que este extrato não tenha OS fechada a mais de 30 dias.

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "financeiro";
include 'autentica_admin.php';

$btn_acao = $_POST["btnacao"];
$valor    = $_POST["valor"];

if($btn_acao == "continuar"){
	if(strlen($valor)==0){ 
		$msg_erro = "Preencha o valor";
		$btn_acao = "";
	}
	$valor = str_replace(",",".",$valor);
}

if($btn_acao == "gravar"){

	if(strlen($valor)==0){ $msg_erro = "Preencha o valor";}
	$valor = str_replace(",",".",$valor);

	if(strlen($msg_erro)==0){
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = @pg_exec($con,$sqlX);
		$trinta_dias = pg_result($resX,0,0);

		/*
			$sql = "SELECT distinct	tbl_extrato.extrato, 
							tbl_extrato.total
					FROM tbl_extrato 
					JOIN tbl_os_extra using(extrato)
					JOIN tbl_os using(os)
					WHERE tbl_extrato.fabrica=$login_fabrica 
					AND tbl_extrato.total < $valor 
					AND tbl_extrato.aprovado is null
					AND tbl_os.data_fechamento > '$trinta_dias'";
		*/
		/*HD 922 TAKASHI 11-01-2007*/
		
		/*MARISA alterei hd 237699 reclama que está lento e não usa essas tabelas para nada*/
		/*$sql = "SELECT 	distinct tbl_extrato.extrato,
						tbl_extrato.total 
					FROM tbl_extrato 
					LEFT JOIN tbl_os_extra using(extrato) 
					LEFT JOIN tbl_os using(os)
					WHERE tbl_extrato.fabrica= $login_fabrica 
					AND (
						(tbl_extrato.total < $valor AND tbl_extrato.aprovado is null) 
						OR (tbl_extrato.total < 20 AND tbl_extrato.aprovado is null)
					)";
		*/
		$sql = "SELECT 	distinct tbl_extrato.extrato,
						tbl_extrato.total 
					FROM tbl_extrato 
					WHERE tbl_extrato.fabrica= $login_fabrica 
					AND (
						(tbl_extrato.total < $valor AND tbl_extrato.aprovado is null) 
						OR (tbl_extrato.total < 20 AND tbl_extrato.aprovado is null)
					)";

		$res = pg_exec($con, $sql);
		$msg_erro .= pg_errormessage($con);
		if (pg_numrows($res)>0){

			for($x=0; pg_numrows($res)>$x;$x++){
				$res2 = pg_exec($con,"BEGIN TRANSACTION"); 
				$extrato = pg_result($res, $x, extrato);
				$total = pg_result($res, $x, total);

				$sql = "SELECT os, extrato INTO TEMP TABLE tmp_acumula_extrato_$x
							FROM tbl_os_extra
							JOIN tbl_extrato USING(extrato)
							WHERE extrato = $extrato
							AND fabrica   = $login_fabrica;";
				$res2 = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql2 = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
				$res2 = pg_exec ($con,$sql2);
				$msg_erro .= pg_errormessage($con);
				#echo '1';

				$sql = "UPDATE tbl_os_status SET admin = $login_admin 
					FROM   tmp_acumula_extrato_$x
					WHERE  tbl_os_status.os = tmp_acumula_extrato_$x.os
					AND    tbl_os_status.extrato = tmp_acumula_extrato_$x.extrato;";
				$res2 = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (strlen($msg_erro) == 0) {
					$res2 = pg_exec($con,"COMMIT TRANSACTION");
					$sql = "SELECT protocolo FROM tbl_extrato WHERE extrato = $extrato;";
					$res2 = @pg_exec($con,$sql);
					$extrato_mostra = @pg_result($res2, 0, protocolo);
					$msg.= "<BR>";
					$msg.= "Extrato $extrato_mostra acumulado. O valor era de R$ $total";
					flush();
				}else{
					$res2 = pg_exec($con,"ROLLBACK TRANSACTION"); 
				}
				$msg_erro = "";
			}
		}else{
			$msg_erro = "Nenhum extrato abaixo do valor de ".number_format($valor,2,',','.'). " em um período de 30 dias";
		}
	}
}

$layout_menu = "financeiro";
$title='ACUMULAR EXTRATOS';
include 'cabecalho.php';


?>
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
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

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

10:21 30/07/2010
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

</style>

<form name="frm_acumular_extratos" method="post" action="<? echo $PHP_SELF; ?>">

<? if (strlen($msg_erro) > 0 or strlen($msg) > 0) { ?>
<table width="700px" border="0" cellpadding="2" cellspacing="1" class="msg_erro" align='center'>
	<tr>
		<td><? echo (strlen($msg_erro) > 0) ? $msg_erro : $msg; ?></td>
	</tr>
</table>
<? } ?>

<table width='700px' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr >
 		<td class='titulo_tabela'>Acumular Extratos</td>
	</tr>
	<tr>
		<td bgcolor='#DBE5F5'>
				<table width="500" border="0" cellpadding="2" cellspacing="1" class="titulo" align='center'>
					<tr>
						<? if ($btn_acao=='continuar') {?>
							<td align='center'><BR>
								<p>Todos os extratos com valores menores que <strong>R$ <? echo $valor; ?></strong> serão <strong>ACUMULADOS</strong>.</p>
								<p>Clique no botão abaixo para confirmar</p>
								<input type="hidden" class="frm" name="valor" value="<? echo $valor; ?>">
							</td>
						<?} else {?>
							<td align='center'>
								<p>Desejo acumular todos extratos com valor menor que:
							R$ <input type="text" class="frm" name="valor" value="<? echo $valor; ?>" size="10" maxlength="10" ></p></td>
						<? } ?>
					</tr>
					<tr>
					<td align='center'>
					<? if ($btn_acao=='continuar') {?>
						<input type="submit" style="background:url(imagens/btn_continuar.gif); width:93px; margin-right:5px;border:none; cursor:pointer;" 
						onclick="javascript: if (document.frm_acumular_extratos.btnacao.value == '' ) { document.frm_acumular_extratos.btnacao.value='gravar' ; document.frm_acumular_extratos.submit() } else { alert ('Aguarde submissão') }"
						value="" />
						
						<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:74px;" value="" onclick="window.location='<?php PHP_SELF; ?>'" />
					<?}else{?>
						<input type="submit" onclick="javascript: if (document.frm_acumular_extratos.btnacao.value == '' ) { document.frm_acumular_extratos.btnacao.value='continuar' ; document.frm_acumular_extratos.submit() } else { alert ('Aguarde submissão') }"
						value="Continuar" />
						<!--<img tabindex="2" src="imagens/btn_continuar.gif" onclick="javascript: if (document.frm_acumular_extratos.btnacao.value == '' ) { document.frm_acumular_extratos.btnacao.value='continuar' ; document.frm_acumular_extratos.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">-->
					<? }?>
					</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<input type='hidden' name='btnacao' value=''>

</form>

<? include "rodape.php"; ?>
