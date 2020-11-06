<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

if (isset($_POST['gravarDataconserto']) AND isset($_POST['os'])){
	$gravarDataconserto = trim($_POST['gravarDataconserto']);
	$os = trim($_POST['os']);
	if (strlen($os)>0){
		if(strlen($gravarDataconserto ) > 0) {
			$data = $gravarDataconserto.":00 ";
			$aux_ano  = substr ($data,6,4);
			$aux_mes  = substr ($data,3,2);
			$aux_dia  = substr ($data,0,2);
			$aux_hora = substr ($data,11,5).":00";
			$gravarDataconserto ="'". $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora."'";
		} else {
			$gravarDataconserto ='null';
		}

		$erro = "";

		//hd 24714
		if ($gravarDataconserto != 'null'){
			$sql = "SELECT $gravarDataconserto > CURRENT_TIMESTAMP ";
			$res = pg_exec($con,$sql);
			if (pg_result($res,0,0) == 't'){
				$erro = traduz("data.de.conserto.nao.pode.ser.superior.a.data.atual", $con, $cook_idioma);
					#Data de conserto não pode ser superior a data atual.";
			}
		}

		//hd 24714
		if ($gravarDataconserto != 'null'){
			$sql = "SELECT $gravarDataconserto < tbl_os.data_abertura FROM tbl_os where os=$os";
			$res = pg_exec($con,$sql);
			if (pg_result($res,0,0) == 't'){
				$erro = traduz("data.de.conserto.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma);
				#"Data de conserto não pode ser anterior a data de abertura.";
			}
		}

		if (strlen($erro) == 0) {
			$sql = "UPDATE tbl_os
					SET data_conserto = $gravarDataconserto
					WHERE os=$os
					AND fabrica = $login_fabrica
					AND posto = $login_posto";
			$res = pg_exec($con,$sql);
		} else {
			echo $erro;
		}
	}
	exit;
}

$title = traduz("fechamento.de.ordem.de.servico", $con, $cook_idioma); #"Fechamento de Ordem de Serviço";
#if($sistema_lingua == 'ES')$title = "Cierre de órdenes de servicio";
$layout_menu = 'os';
include "cabecalho.php";
include '_traducao_erro.php';



#------------ Fecha Ordem de Servico ------------#
$btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == 'continuar') {

	$data_fechamento = $_POST['data_fechamento'];
	$qtde_os         = $_POST['qtde_os'];

	if (strlen($data_fechamento) == 0){
		/*if($sistema_lingua == "ES") $msg_erro = "Digite la fecha de cierre";
		else                        $msg_erro = "Digite a data de fechamento.";*/
		$msg_erro = traduz("digite.a.data.de.fechamento", $con, $cook_idioma);
	}else{
		$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);

		if($xdata_fechamento > "'".date("Y-m-d")."'"){
			/*if($sistema_lingua == "ES") $msg_erro = "Fecha de cierre mayor que la frcha de hoy.";
 			else                        $msg_erro = "Data fechamento maior que a data de hoje";*/
			$msg_erro = traduz("data.de.fechamento.maior.que.a.data.de.hoje", $con, $cook_idioma);
		}


		//HD 9013, hd 36290 - retirado
		# OBS.: Como esta parte do código não está sendo utilizada não foi traduzida ainda
		#   Quando liberar, favor lembrar de traduzir
	
		if (strlen($msg_erro) == 0){
			// HD  27468

			if($login_fabrica ==14){
				$res = pg_exec ($con,"BEGIN TRANSACTION");
			}

			for ($i = 0 ; $i < $qtde_os ; $i++) {
				$linha_erro[$i]=0;
				$ativo             = trim($_POST['ativo_'. $i]);
				$os                = trim($_POST['os_' . $i]);
				$serie             = trim($_POST['serie_'. $i]);
				$serie_reoperado   = trim($_POST['serie_reoperado_'. $i]);
				$nota_fiscal_saida = trim($_POST['nota_fiscal_saida_'. $i]);
				$data_nf_saida     = trim($_POST['data_nf_saida_'. $i]);
				$motivo_fechamento = trim($_POST['motivo_fechamento_'. $i]);
				if($login_fabrica==1){
					$ativo_revenda             = trim($_POST['ativo_revenda_'. $i]);
				}

				
				//hd 24714
				if($ativo =='t'){
					
					$sqldefeito = "SELECT defeito_constatado from tbl_os where os= $os";

					$resdefeito = pg_exec($con,$sqldefeito);
					$erro .= pg_errormessage ($con);
					if (pg_numrows($resdefeito) > 0) {

						$xdefeito = pg_result($resdefeito,0,0);
						if (strlen($xdefeito)==0) {
							$sqlatudefeito = "UPDATE tbl_os set defeito_constatado = 12816 where os = $os";
							$resatudefeito = pg_exec($con,$sqlatudefeito);
							$erro .= pg_errormessage ($con);
						}
					}
					

					$sqlsolucao = "SELECT solucao_os from tbl_os where os= $os";
					$ressolucao = pg_exec($con,$sqlsolucao);
					$erro .= pg_errormessage ($con);

					if (pg_numrows($ressolucao) > 0) {

						$xsolucao = pg_result($ressolucao,0,0);
						if (strlen($xsolucao)==0) {
							$sqlatusolucao = "UPDATE tbl_os set solucao_os = 771 where os = $os";
							$resatusolucao = pg_exec($con,$sqlatusolucao);
							$erro .= pg_errormessage ($con);
						}
					}


					$sql = "SELECT $xdata_fechamento < tbl_os.data_abertura FROM tbl_os where os=$os";
					$res = pg_exec($con,$sql);
					if (pg_result($res,0,0) == 't'){
						$msg_erro = traduz("data.de.fechamento.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma); /*"Data de fechamento não pode ser anterior a data de abertura.";*/
					}
				}
				
				$xmotivo_fechamento = "null";
			
				if (strlen($data_nf_saida) == 0)
					$xdata_nf_saida = 'null';
				else
					$xdata_nf_saida    = fnc_formata_data_pg ($data_nf_saida) ;

				if (strlen($nota_fiscal_saida) == 0)
					$xnota_fiscal_saida = 'null';
				else
					$xnota_fiscal_saida = "'".$nota_fiscal_saida."'";

				if ($ativo == 't' or $ativo_revenda=='t'){
					$xserie_reoperado = "null";
				
					$xserie= 'null';
			
					//hd 6701 - nao deixar o posto 019876-IVO CARDOSO fechar sem lancar NF
			
					if (strlen ($erro) == 0) {
						// HD 27468
						
						$upd_serie = "";
					
						if (strlen ($erro) == 0) {
							
								$sql = "UPDATE  tbl_os SET
												data_fechamento   = $xdata_fechamento  ,
												$upd_serie
												serie_reoperado   = $xserie_reoperado   ,
												nota_fiscal_saida = $xnota_fiscal_saida,
												data_nf_saida     = $xdata_nf_saida
										WHERE   tbl_os.os         = $os";
							}
							//echo "$sql<BR>";

							$res  = @pg_exec ($con,$sql);
							$erro .= pg_errormessage ($con);
						}

					if (strlen ($erro) == 0) {
							$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
							$res = @pg_exec ($con,$sql);
							$erro = pg_errormessage($con);
						}
					
					if (strlen ($erro) == 0) {
						$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,64,current_timestamp,'Produto com reparo realizado pela fábrica e recebido pelo posto')";

						$res = pg_exec($con,$sql);
						$erro = pg_errormessage($con);

						$sqlretorno = "UPDATE tbl_os_retorno set nota_fiscal_envio = 'N/C', data_nf_envio = current_timestamp, envio_chegada = current_timestamp, nota_fiscal_retorno = 'N/C', data_nf_retorno = current_timestamp, retorno_chegada = current_timestamp where os = $os";

						$resretorno = pg_exec($con,$sqlretorno);
						$erro = pg_errormessage($con);
					}

					if (strlen ($erro) > 0) {
						$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
					}
					else{
						$res = @pg_exec ($con,"COMMIT TRANSACTION");
					}
					
				} else{
					$msg_erro = $erro;
				}
			}//for

		} // if msg_erro
	}//if
}



?>

<script language="JavaScript">
var checkflag = "false";
function SelecionaTodos(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}
</script>
 <link rel="stylesheet" href="js/jquery.tooltip.css" />
 <script src="js/jquery-1.1.2.pack.js"></script>
 <script src="js/jquery.maskedinput.js"></script>
 <script src="js/jquery.tooltip.js"           type="text/javascript"></script>
 <script type="text/javascript" src="js/jquery.corner.js"></script>
 <script type="text/javascript">
 $(document).ready(function(){
   $(".tabela_resultado tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
   //$(".tabela_resultado tr:even").addClass("alt");
   $(".tabela_resultado tr[@rel='sem_defeito']").addClass("sem_defeito");
   $(".tabela_resultado tr[@rel='mais_30']").addClass("mais_30");
   $(".tabela_resultado tr[@rel='erro_post']").addClass("erro_post");
   });

	$(document).ready(function(){
		$(".titulo").corner("round");
		$(".subtitulo").corner("round");
		$(".content").corner("dog 10px");

	});
	function formata_data(campo_data, form, campo){
	var mycnpj = '';
	mycnpj = mycnpj + campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}
function mostraDados(peca){
	if (document.getElementById('dados_'+peca)){
		var style2 = document.getElementById('dados_'+peca);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}

 </script>
 <script type="text/javascript" src="js/niftycube.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$("input[@rel='data_conserto']").maskedinput("99/99/9999 99:99");
		$("input[@name='data_fechamento']").maskedinput("99/99/9999");
	});
</script>

<script type="text/javascript">

function filtro(bolinha){
	if (bolinha!=''){
		$('.tabela_resultado tbody tr[@rel!='+bolinha+']').css({'display':'none'});
		$('.tabela_resultado tbody tr[@rel='+bolinha+']').css({'display':''});
	//	$('.tabela_resultado tr[@rel='+bolinha+']').style.display = "none";
	}else{
		$('.tabela_resultado tbody tr').css({'display':''});
	}

}

$().ready(function() {
	$("input[@rel='data_conserto']").blur(function(){
		var campo = $(this);


			$.post('<? echo $PHP_SELF; ?>',
				{
					gravarDataconserto : campo.val(),
					os: campo.attr("alt")
				},

				//24714
				function(resposta){
					if (resposta.length > 0){
						alert(resposta);
						campo.val('');
					}
				}
			);

	});
});

</script>


<script type="text/javascript">
	window.onload=function(){
		Nifty("ul#split h3","top");
		Nifty("ul#split div","none same-height");
	}
</script>
<style type="text/css">

	table.sample {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
	}

	table.sample th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}

	table.sample td {
		padding: 1px 11px;
		border-bottom: 1px solid #95bce2;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.sample tr.alt td {
		background: #ecf6fc;
	}

	table.sample tr.over td {
		background: #bcd4ec;
	}
	table.sample tr.clicado td {
		background: #FF9933;
	}
	table.sample tr.sem_defeito td {
		background: #FFCC66;
	}
	table.sample tr.mais_30 td {
		background: #FF0000;
	}
	table.sample tr.erro_post td {
		background: #99FFFF;
	}

.titulo {
	background:#7392BF;
	width: 650px;
	text-align: center;
	padding: 4px 4px; /* padding greater than corner height|width */
/*	margin: 1em 0.25em;*/
	font-size:12px;
	color:#FFFFFF;
}
.titulo h1 {
	color:white;
	font-size: 120%;
}

.subtitulo {
	background:#FCF0D8;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 10px auto;
	color:#392804;
}
.subtitulo h1 {
	color:black;
	font-size: 120%;
}

.content {
	background:#CDDBF1;
	width: 600px;
	text-align: center;
	padding: 5px 30px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#000000;
	text-align:left;
}
.content h1 {
	color:black;
	font-size: 120%;
}

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
.fechamento{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #C50A0A;
}
.fechamento_content{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	color: #FFFFFF;
	background-color: #F9DBD0;
}




	.Relatorio {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
	}

	.Relatorio th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}

	.Relatorio td {
		padding: 1px 11px;
		border-bottom: 1px solid #95bce2;
	}







</style>

<?


	if($sistema_lingua ) $msg_erro = traducao_erro($msg_erro,$sistema_lingua);
if (strlen ($msg_erro) > 0) {
	//echo $msg_erro;
	if (strpos ($msg_erro,"Bad date external ") > 0) $msg_erro = traduz("data.de.fechamento.invalida", $con, $cook_idioma); /*"Data de fechamento inválida";*/
	if (strpos ($msg_erro,'"tbl_os" violates check constraint "data_fechamento"') > 0) $msg_erro = traduz("data.de.fechamento.invalida", $con, $cook_idioma); /*"Data de fechamento inválida";*/
	if (strpos ($msg_erro,"É necessário informar a solução na OS") > 0) $msg_solucao = 1;
	if (strpos ($msg_erro,"Para esta solução é necessário informar as peças trocadas") > 0) $msg_solucao = 1;
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = traduz("foi.detectado.o.seguinte.erro", $con, $cook_idioma); /*"Foi detectado o seguinte erro:*/
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

?>
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center" class='error'>
		<?
		echo $msg_erro; echo "<br>";
		echo $erro;
		?>
	</td>
</tr>
</table>
<br>
<? } ?>

<? if (strlen ($msg_ok) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#FFCC66">
<tr>
	<td height="27" valign="middle" align="center">
	<?	//if ($sistema_lingua=='ES') {
			echo "<font size='2'><b>";
			fecho ("os.fechada.com.sucesso", $con, $cook_idioma);
			/*OS(s) cerrada(s) con exito!!!*/
			echo "</b></font>";
		/*} else {
			echo "<font size='2'><b>OS(s) fechada(s) com sucesso!!!</b></font>";
		} */?>
	</td>
</tr>
</table>
<? } ?>

<br>

<?
if(strlen($msg_erro) > 0){

	echo "<BR>";
	echo "<div align='left' style='position: relative; left: 10'>";
	echo "<table width='700' height=15 border='0' cellspacing='0' cellpadding='0' align='center'>";
	echo "<tr>";
	echo "<td align='center' width='15' bgcolor='#FF0000'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp;";
	fecho ("erro.na.os", $con, $cook_idioma);
	/*ERRO NA OS*/
	echo "</b></font></td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br>";
}

$sua_os       = trim($_POST['sua_os']);
$codigo_posto = $_POST['codigo_posto'];
if(strlen($sua_os ) == 0 AND $login_fabrica == 15){
	$sua_os       = trim($_GET['sua_os']);
}

?>

<table width="700" border="0" cellpadding="2" cellspacing="0" align="center">
<form name='frm_os_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<table width="400" align="center" border="0" cellspacing="2" cellpadding="2" bgcolor='#D9E2EF'>
	<tr class="Titulo" height="30">
		<td align="center"'>
		<?
		fecho ("selecione.os.parametros.para.a.pesquisa", $con, $cook_idioma);
		?>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='center'><b><? fecho ("numero.da.os", $con, $cook_idioma); ?></b>
		<input type='text' name='sua_os' size='10' value='<? echo $sua_os ?>'></td>
	</tr>
	<? if($login_fabrica == '11' and $login_posto == '14301' or ($login_fabrica == '11' and  $login_posto == '6359')){?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='center'><b><? fecho ("box", $con, $cook_idioma);
		echo "/";
		fecho ("prateleira", $con, $cook_idioma);  ?></b>
		<SELECT NAME="prateleira_box">
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='CONSERTO'><? fecho ("conserto.maiu", $con, $cook_idioma); ?></OPTION>
				<OPTION VALUE='TROCA'><? fecho ("troca.maiu", $con, $cook_idioma); ?></OPTION>
				<OPTION VALUE='REEMBOLSO'><? fecho ("reembolso.maiu", $con, $cook_idioma); ?></OPTION>
		</SELECT>
	</tr>
	<? } ?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='center'><a href='<? echo $PHP_SELF."?listar=todas"; ?>'>
		<? fecho ("listar.todas.as.suas.oss", $con, $cook_idioma); ?></a>
		</td>
	</tr>
	<? if ($login_e_distribuidor == 't') { ?>

	<tr height="22" bgcolor="#bbbbbb">
		<TD>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? fecho ("listar.todas.as.os.do.posto", $con, $cook_idioma);?> </b>
			</font>
			<input type='text' name='codigo_posto' size='8' value='<? echo $codigo_posto ?>'>
			<input type='submit' value='Listar' name='btn_listar_posto'>
		</TD>
	</tr>

	<? } ?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='center'><img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os_pesquisa.btn_acao_pesquisa.value == '' ) { document.frm_os_pesquisa.btn_acao_pesquisa.value='continuar' ; document.frm_os_pesquisa.submit() } else { alert ('Aguarde submissão') }"  border='0' style='cursor: pointer'></td>
	</tr>
</table>
<input type='hidden' name='btn_acao_pesquisa' value=''>


</form>
</table>

<?
$btn_acao_pesquisa = trim($_POST['btn_acao_pesquisa']);
$listar            = trim($_POST['listar']);
$sua_os            = trim($_POST['sua_os']);
$codigo_posto      = trim($_POST['codigo_posto']);

if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = trim($_GET['btn_acao_pesquisa']);
if (strlen($_GET['listar']) > 0)            $listar            = trim($_GET['listar'])           ;
if (strlen($_GET['sua_os']) > 0)            $sua_os            = trim($_GET['sua_os'])           ;
if (strlen($_GET['codigo_posto']) > 0)      $codigo_posto      = trim($_GET['codigo_posto'])     ;

$posto = $login_posto;

if (strlen ($codigo_posto) > 0) {
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$posto = pg_result ($res,0,0);
}
if($login_fabrica == 3 or $login_posto == '4311' or (($login_fabrica <> 11 and $login_fabrica<>1) and $login_posto== '6359') or $login_fabrica==15 or $login_fabrica==45 or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50 or ($login_fabrica ==20)){
	$sql_data_conserto=", to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' )	as data_conserto ";
}
if($login_fabrica==11 and $login_posto==14301){
	$sql_obs=" , tbl_os.consumidor_email ";
}
if ((strlen($sua_os) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0 OR strlen ($codigo_posto) > 0 OR (strlen($prateleira_box) > 0 AND $btn_acao_pesquisa == 'continuar' ) ){




		/*HD 18229 Retirado MELHORIA DE PERFORMANCE
		//takashi comentou dia 18-05-07
		//if ($login_fabrica == 1) $sua_os = substr($sua_os, strlen($login_codigo_posto), strlen($sua_os));
		if (strlen($sua_os) > 0 and $login_fabrica<>1) $sql_adiciona .= "AND (tbl_os.sua_os like '$sua_os%' OR tbl_os.sua_os LIKE '0$sua_os%' OR tbl_os.sua_os LIKE '00$sua_os%' OR tbl_os.sua_os LIKE '000$sua_os%' OR tbl_os.sua_os LIKE '0000$sua_os%' OR tbl_os.sua_os LIKE '00000$sua_os%' OR tbl_os.sua_os LIKE '000000$sua_os%' OR tbl_os.sua_os LIKE '0000000$sua_os%' OR tbl_os.sua_os LIKE '00000000$sua_os%') ";
		//takashi comentou dia 18-05-07
		//takashi colocou dia 18-05-07

		if ($login_fabrica == 1){
				$pos = strpos($sua_os, "-");
				if ($pos === false) {
					$pos = strlen($sua_os) - 5;
				}else{
					$pos = $pos - 5;
				}
				$sua_os = substr($sua_os, $pos,strlen($sua_os));
		}
		/*
		if(strlen($sua_os)>0 and $login_fabrica==1){
			$sql_adiciona .= "AND (
					tbl_os.sua_os = '$sua_os' OR tbl_os.sua_os = '0$sua_os' OR tbl_os.sua_os = '00$sua_os' OR tbl_os.sua_os = '000$sua_os' OR tbl_os.sua_os = '0000$sua_os' OR tbl_os.sua_os = '00000$sua_os' OR tbl_os.sua_os = '000000$sua_os' OR tbl_os.sua_os = '0000000$sua_os' OR tbl_os.sua_os = '00000000$sua_os' OR ";
			$sql_adiciona .= "tbl_os.sua_os = '$sua_os-1' OR
			tbl_os.sua_os = '$sua_os-2' OR
			tbl_os.sua_os = '$sua_os-3' OR
			tbl_os.sua_os = '$sua_os-4' OR
			tbl_os.sua_os = '$sua_os-5' OR
			tbl_os.sua_os = '$sua_os-6' OR
			tbl_os.sua_os = '$sua_os-7' OR
			tbl_os.sua_os = '$sua_os-8' OR
			tbl_os.sua_os = '$sua_os-9' OR 1=2 ";
			//HD 9013
			$xsua_os=substr($sua_os,0,6);
			$sql_adiciona .=" OR tbl_os.sua_os like '$xsua_os%')";
		}*/

		if($login_fabrica == 3 or $login_posto == '4311'){
			$sql_add1 = "
				,(
					SELECT   OI.os_item
					FROM      tbl_os_produto        OP
					JOIN      tbl_os_item           OI ON OP.os_produto        = OI.os_produto
					JOIN      tbl_servico_realizado SR ON OI.servico_realizado = SR.servico_realizado
					LEFT JOIN tbl_faturamento_item  FI ON OI.peca              = FI.peca              AND OI.pedido = FI.pedido
					WHERE OP.os = tbl_os.os
					AND   SR.gera_pedido      IS TRUE
					AND   FI.faturamento_item IS NULL
					LIMIT 1
				) as os_item ";
		}
		if($login_posto == '4311'  or $login_posto == '6359' or $login_posto == '14301') {
			$sql_add2 =", tbl_os.prateleira_box ";
		}

		if($login_fabrica == 19) $sql_adiciona .= " AND tbl_os.consumidor_revenda = 'C' ";

		if($login_fabrica == '11' and $login_posto == '14301' or $login_posto == '6359'){
			if (strlen ($prateleira_box) > 0) {
				$sql_adiciona .= " AND tbl_os.prateleira_box = '$prateleira_box'";
			}
		}
		if ( strlen ($codigo_posto) == 0) {
			$sql_adiciona .= " AND tbl_os.posto = $login_posto ";
		} else {
			$sql_adiciona .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' AND (tbl_os.posto = $login_posto OR tbl_os.posto IN (SELECT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto))";
		}

		//hd 45142
		if ($login_fabrica==1){
			$fazer_paginacao = 'nao';
		}

/*		//hd 46941
		if ($login_fabrica == 11 and ($login_posto = 6940 or $login_posto = 4567 or $login_posto = 14236 or $login_posto = 1809 or $login_posto = 27401 or $login_posto = 6945 or $login_posto = 17674 or $login_posto = 5993 or $login_posto = 14254)) {
			$fazer_paginacao = 'nao';
		}
# encontrei este erro quando estava fazendo o HD 98262 */

		if ($login_fabrica == 11 and ($login_posto == 6940 or $login_posto == 4567 or $login_posto == 14236 or $login_posto == 1809 or $login_posto == 27401 or $login_posto == 6945 or $login_posto == 17674 or $login_posto == 5993 or $login_posto == 14254)) {
			$fazer_paginacao = 'nao';
		}

		//HD 18229
		if (strlen($sua_os) > 0) {
			$fazer_paginacao = 'nao';
			if ($login_fabrica == 1) {
				$pos = strpos($sua_os, "-");
				if ($pos === false) {
					$pos = strlen($sua_os) - 5;
				}else{
					$pos = $pos - 5;
				}
				$sua_os = substr($sua_os, $pos,strlen($sua_os));
			}
			$sua_os = strtoupper ($sua_os);

			$pos = strpos($sua_os, "-");
			if ($pos === false) {
				if(!ctype_digit($sua_os)){
					$sql_adiciona .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					$sql_adiciona .= " AND tbl_os.os_numero = '$sua_os' ";
				}
			}else{
				$conteudo = explode("-", $sua_os);
				$os_numero    = $conteudo[0];
				$os_sequencia = $conteudo[1];
				if(!ctype_digit($os_sequencia)){
					$sql_adiciona .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					if($login_fabrica <>1 and $login_fabrica <>7){
						$sql_adiciona .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
					}else{
						//HD 9013 24484
						$sql_adiciona .= " AND tbl_os.os_numero = '$os_numero' ";
					}

				}
			}
		}
		if($login_fabrica==11 and ($login_posto==6359 or $login_posto==14301)){
			$sql_order .= "ORDER BY tbl_os.data_abertura ASC ";
		}else if($login_fabrica==1 and $login_posto==6359){
			$sql_order .= "ORDER BY tbl_os.consumidor_revenda asc,lpad(tbl_os.sua_os::text,20,'0') DESC, lpad(tbl_os.os::text,20,'0') DESC ";
		}else{
			$sql_order .= "ORDER BY tbl_os.os";
		}

		$sql_linha = " AND tbl_produto.linha = 549 ";

		$sql = "SELECT  tbl_os.os                                                  ,
						tbl_os.sua_os                                              ,
						tbl_os.serie                                               ,
						tbl_produto.referencia                                     ,
						tbl_produto.produto                                        ,
						tbl_produto.descricao                                      ,
						tbl_produto.nome_comercial                                 ,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						tbl_os.consumidor_nome                                     ,
						tbl_os.consumidor_revenda                                  ,
						tbl_os.defeito_constatado                                  ,
						tbl_os.admin                                               ,
						tbl_os.tipo_atendimento
						$sql_add1
						$sql_add2
						$sql_data_conserto
						$sql_obs
				FROM    tbl_os
				JOIN    tbl_produto            USING (produto)
				JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     tbl_os.data_fechamento IS NULL
				AND    (tbl_os.excluida        IS NULL OR tbl_os.excluida IS FALSE )
				AND    tbl_posto_fabrica.codigo_posto = '3030'
				$sql_linha
				$sql_order";
		
//		echo nl2br($sql);
		$res = pg_exec ($con,$sql);

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		/* Alterado HD 44973 - Colocado número da Página */
		// definicoes de variaveis
		$max_links = 15;				// máximo de links à serem exibidos
		$max_res   = 200;				// máximo de resultados à serem exibidos por tela ou pagina
		/* Nos casos de busca por OS, mostrar paginacao longa, pois a Black precisa mostrar todas as OS na mesma tela  */
		if ($fazer_paginacao == 'nao'){
			$max_res   = 3000;
		}
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		if (pg_numrows($res) > 0){

			echo "<div id='layout'>";
			echo "<div class='subtitulo'>";
			fecho ("com.o.fechamento.da.os.voce.se.habilita.ao.recebimento.dos.valores.que.serao.pagos.no.proximo.extrato", $con, $cook_idioma);
			echo "</div>";
			echo "</div>";

			echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center'>";
			echo "<tr>";
			echo "<td><img height='1' width='20' src='imagens/spacer.gif'></td>";
			echo "<td valign='top' align='center'>";

			// Não traduzi ainda
			if ($login_fabrica == 1){
				echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
				echo "<tr>";
				echo "<td align='center' width='18' height='18' bgcolor='#FF0000'>&nbsp;</td>";
				echo "<td align='left'><font size=1>&nbsp;";
				fecho ("oss.que.excederam.o.prazo.limite.de.30.dias.para.fechamento,.informar.motivo", $con, $cook_idioma);
				echo "</font></td>";
				echo "</tr>";
				echo "<tr height='4'><td colspan='2'></td></tr>";
				echo "<tr>";
				echo "<td align='center' width='18' height='18' bgcolor='#FFCC66'>&nbsp;</td>";
				echo "<td align='left'><font size=1>&nbsp;";
				fecho ("oss.sem.defeito.constatado", $con, $cook_idioma);
				echo "</font></td>";
				echo "</tr>";
				if (strlen($msg_solucao) > 0){
					echo "<tr height='4'><td colspan='2'></td></tr>";
					echo "<tr>";
					echo "<td align='center' width='18' height='18' bgcolor='#99FFFF'>&nbsp;</td>";
					echo "<td align='left'><font size=1>&nbsp;";
					fecho ("oss.sem.solucao.e.sem.itens.lancados", $con, $cook_idioma);
					echo "</font></td>";
					echo "</tr>";
				}
				echo "</table>";
			}
			//


//HD 4291 PAULO  HD 14121
	if($login_posto=='4311' or $login_posto=='6359' or $login_fabrica == 15 or $login_fabrica ==45 or $login_fabrica == 7 or $login_fabrica == 43 or $login_fabrica >50  or ($login_fabrica ==20 AND $login_pais=='BR')){
		##### LEGENDAS - INÍCIO #####
			echo "<br>";
			echo "<div align='left' style='position: relative; left: 10'>";
			echo "<table border='0' cellspacing='0' cellpadding='0'>";
			echo "<tr height='18'>";
			echo "<td width='18' ><img src='imagens/status_vermelho' width='10' align='absmiddle'/></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  <a href=\"javascript: filtro('vermelho')\">";
			fecho ("os.aguardando.analise", $con, $cook_idioma); /*OS Aguardando Análise*/
			echo "</a></b></font></td><BR>";
			echo "</tr>";
			echo "<tr height='18'>";
			echo "<td width='18'><img src='imagens/status_amarelo' width='10' align='absmiddle'/></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  <a href=\"javascript: filtro('amarelo')\">";
			fecho ("os.aguardando.peca", $con, $cook_idioma); /*OS Aguardando Peça*/
			echo "</a></b></font></td>";
			echo "</tr>";
			echo "<tr height='18'>";
			echo "<td width='18'><img src='imagens/status_rosa' width='10' align='absmiddle'/></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  <a href=\"javascript: filtro('rosa')\">";
			fecho ("os.aguardando.conserto", $con, $cook_idioma); /*OS Aguardando Conserto*/
			echo "</a></b></font></td>";
			echo "</tr>";
			echo "<tr height='18'>";
			echo "<td width='18'><img src='imagens/status_azul' width='10' align='absmiddle'/></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  <a href=\"javascript: filtro('azul')\">";
			fecho ("os.consertada", $con, $cook_idioma); /*OS Consertada*/
			echo "</a></b></font></td>";
			echo "</tr>";
			echo "<tr height='18'>";
			echo "<td width='18'></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  <a href=\"javascript: filtro('')\">";
			fecho ("todas", $con, $cook_idioma); /*Todas*/
			echo "</a></b></font></td>";
			echo "</tr>";
			echo "</table>";
			echo "</div>";
			echo "<BR>";
		##### LEGENDAS - FIM  ######

		}
		?>
		<?if($login_fabrica==11){
			//HD 13239
			$data_fechamento=date("d/m/Y");
		}

		?>

		<!-- ------------- Formulário ----------------- -->

		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type='hidden' name='qtde_os' value='<? echo pg_numrows ($res); ?>'>
		<input type='hidden' name='sua_os' value='<? echo $sua_os; ?>'>
		<input type='hidden' name='btn_acao_pesquisa' value='<? echo $btn_acao_pesquisa ?>'>
		<input type='hidden' name='listar' value='<? echo $listar ?>'>
		<TABLE width="650" border="0" cellpadding="2" cellspacing="0" align="center">
		<tr>
		<TD width='120' class="fechamento"><b><?/*if($sistema_lingua == 'ES') echo "Cerrar Cierre";else echo "Data de Fechamento";*/ fecho ("data.de.fechamento", $con, $cook_idioma); ?></TD>
		<TD nowrap  width='530' class="fechamento_content">&nbsp;&nbsp;&nbsp;&nbsp;
		<input class="frm" type='text' name='data_fechamento' size='12' maxlength='10' value='<? echo $data_fechamento ?>' <?if($login_fabrica==11){
			echo "readonly='readonly'";
		}?> >
		</TD>
		</TR>
		</TABLE>
		<table width="650" border="0" cellspacing="1" cellpadding="4" align="center" style='font-family: verdana; font-size: 10px' class='tabela_resultado Relatorio'>
		<!-- class='tabela_resultado sample'-->
		<?		//HD 9013
			if($login_fabrica==1 or $login_fabrica ==7){?>
		<caption colspan='100%' style='font-family: verdana; font-size: 20px'><? fecho ("os.de.consumidor", $con, $cook_idioma); /*OS de Consumidor*/?></font><caption>
		<?}?>
		<thead>
		<tr height="20">
			<th nowrap>
			<?// if($login_fabrica<>20){ ?>
				<input type='checkbox' class='frm' name='marcar' value='tudo' title='<? fecho ("selecione.ou.desmarque.todos", $con, $cook_idioma); /*Selecione ou desmarque todos*/?>' onClick='SelecionaTodos(this.form.ativo);' style='cursor: hand;'>
			<?// } ?>
			</th>
			<th nowrap><b><? fecho ("os", $con, $cook_idioma); /*OS*/
				if($login_fabrica<>20){ fecho ("fabricante", $con, $cook_idioma); /*Fabricante*/ } ?></b></th>
			<? //HD 23623 ?>
			<? if ($login_fabrica == 11 and $login_posto==14301){ ?><th nowrap><b><? fecho ("box", $con, $cook_idioma);
			echo "/";
			fecho ("prateleira", $con, $cook_idioma);
			/*Box/Prateleira*/ ?></b></th><?}?>
			<th nowrap><b><? fecho ("data.abertura", $con, $cook_idioma); /*if($sistema_lingua == 'ES') echo "Fecha Abertura";else echo "Data Abertura";*/?></b></th>
			<th nowrap><b><? fecho ("consumidor", $con, $cook_idioma);/*if($sistema_lingua == 'ES') echo "Usuário";else echo "Consumidor";*/?></b></th>
			<th nowrap><b><? fecho ("produto", $con, $cook_idioma); /*if($sistema_lingua == 'ES') echo "Producto";else echo "Produto";*/?></b></th>
			
		</tr>
</thead>
<tbody>
<?
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			
			$flag_cor = "";
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			 $consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
			 $referencia         = trim(pg_result ($res,$i,referencia));
			 //HD 9013
			 if(($consumidor_revenda=='C' and ($login_fabrica==1 or $login_fabrica==7)) or ($login_fabrica<>1 and $login_fabrica <>7)){
			$os               = trim(pg_result ($res,$i,os));
			$sua_os           = trim(pg_result ($res,$i,sua_os));
			$admin            = trim(pg_result ($res,$i,admin));
			$tipo_atendimento = trim(pg_result ($res,$i,tipo_atendimento));
			$produto          = trim(pg_result ($res,$i,produto));
			//HD 12521


			if($login_fabrica == 3 or $login_posto == '4311'){
				$os_item          = trim(pg_result ($res,$i,os_item));

			}
			//HD 13239
			if($login_fabrica == 3 or $login_posto == '4311' or (($login_fabrica <> 11 and $login_fabrica <>1) and $login_posto== '6359') or $login_fabrica == 15  or $login_fabrica == 45 or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50 or ($login_fabrica ==20)){
				$data_conserto           = trim(pg_result ($res,$i,data_conserto));
			}
			//HD 4291 Paulo --- HD 23623 - acrescentado 14301
			if($login_posto=='4311' or $login_posto == 6359 or $login_posto==14301) {
				$prateleira_box          = trim(pg_result ($res,$i,prateleira_box));
			}
			if($login_fabrica==11 and $login_posto==14301){
				$consumidor_email        = trim(pg_result ($res,$i,consumidor_email));

			}
//			$leftpad = trim(pg_result ($res,$i,leftpad));
//if ($ip == '201.0.9.216') { echo $leftpad; }
			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_result ($res,$i,descricao) ;


			 $defeito_constatado = trim(pg_result ($res,$i,defeito_constatado));

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================


			if ($login_fabrica == 1) {
				$sql = "SELECT os
						FROM   tbl_os
						WHERE  os = $os
						AND    motivo_atraso IS NULL
						AND    consumidor_revenda = 'C'
						AND    (data_abertura + INTERVAL '30 days')::date < current_date;";
				$resY = pg_exec($con, $sql);
				if (pg_numrows($resY) > 0) {
					$cor = "#FF0000";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}

#				$resX = pg_exec($con,"SELECT to_char (current_date , 'YYYY-MM-DD')");
#				$data_atual = pg_result($resX,0,0);

				if (strlen($defeito_constatado) == 0) {
					$cor = "#FFCC66";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}elseif ($flag_bloqueio == "t" AND strlen($defeito_constatado) <> 0){
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}
			}
			//HD 4291 Paulo verificar a peça pendente da os e mudar cor
			// HD 14121
			if(($login_posto=='4311' or $login_posto=='6359' or $login_fabrica==15 or $login_fabrica ==45 or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50) and $os <> $os_anterior) {
				$bolinha="";

				$sqlcor="SELECT *
							FROM tbl_os
							WHERE defeito_constatado is null
							AND	  solucao_os is null
							AND	  os=$os";
				$rescor=pg_exec($con,$sqlcor);
				if(pg_numrows($rescor) > 0) {
					$bolinha="vermelho";
				} else {

					$sqlcor2 = "SELECT	DISTINCT tbl_os_item.pedido   ,
										tbl_os_item.peca                      ,
										tbl_pedido.distribuidor             ,
										tbl_os_item.faturamento_item       ";
					if(strlen($os_item)==0){
						$sqlcor2 .=", tbl_os_item.os_item ";
					}
					$sqlcor2 .=	"FROM    tbl_os_produto
								JOIN    tbl_os_item USING (os_produto)
								JOIN    tbl_produto USING (produto)
								JOIN    tbl_peca    USING (peca)
								LEFT JOIN tbl_defeito USING (defeito)
								LEFT JOIN tbl_servico_realizado USING (servico_realizado)
								LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
								LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
								LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
								LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
								WHERE   tbl_os_produto.os = $os";

					$rescor2 = pg_exec($con,$sqlcor2);
					if(pg_numrows($rescor2) > 0) {
						for ($j = 0 ; $j < pg_numrows ($rescor2) ; $j++) {
							$pedido               = trim(pg_result($rescor2,$j,pedido));
							$peca                 = trim(pg_result($rescor2,$j,peca));
							$distribuidor         = trim(pg_result($rescor2,$j,distribuidor));
							$faturamento_item     = trim(pg_result($rescor2,$j,faturamento_item));
							if(strlen($os_item) ==0)$os_item              = trim(pg_result($rescor2,$j,os_item));
							$bolinha="";
								if ($login_fabrica == 3) {
									if (strlen($pedido) > 0 and (($peca <> $peca_anterior and $pedido<>$pedido_anterior) or ($peca <> $peca_anterior and $pedido == $pedido_anterior))) {
											$sql  = "SELECT *
													FROM    tbl_faturamento
													JOIN    tbl_faturamento_item USING (faturamento)
													WHERE   tbl_faturamento_item.pedido  = $pedido
													AND     tbl_faturamento_item.peca    = $peca";
											if($distribuidor=='4311'){
												$sql .=" AND     tbl_faturamento_item.os_item = $os_item
														 AND     tbl_faturamento.posto        = $login_posto
														 AND     tbl_faturamento.distribuidor = 4311";
											}elseif(strlen($distribuidor)>0 ){
												$sql .=" tbl_faturamento.posto = $distribuidor ";
											}else{
												$sql .=" AND     (length(tbl_faturamento_item.os::text) = 0 OR tbl_faturamento_item.os = $os)
														 AND     tbl_faturamento.posto       = $login_posto";
												//hd 22576
												if($login_posto =='4311'){
													$sql .=" AND tbl_faturamento.nota_fiscal IS NOT NULL
															 AND tbl_faturamento.emissao IS NOT NULL ";
												}
											}
											$resx = pg_exec ($con,$sql);
											if (pg_numrows ($resx) == 0) {
												$bolinha="amarelo";
											}elseif ($login_posto =='4311' and pg_numrows($resx) >0) {
												//hd 22576
												$bolinha="amarelo";
											}


										$sql="SELECT count(os_item) as conta_item,
													 os as conta_os
												FROM tbl_os_produto
												JOIN tbl_os_item using(os_produto)
												WHERE os=$os
												GROUP BY os";
										$resX = pg_exec ($con,$sql);
										if (pg_numrows ($resX) > 0) {
											$conta_item=pg_result($resX,0,conta_item);
											$conta_os  =pg_result($resX,0,conta_os);
											if(strlen($conta_item) > 0){
												$sql = "SELECT	count(embarcado) as embarcado
														FROM tbl_embarque_item
														JOIN tbl_os_item ON tbl_os_item.os_item = tbl_embarque_item.os_item
														JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
														WHERE tbl_os_item.os_item in (SELECT os_item FROM tbl_os_produto JOIN tbl_os_item using(os_produto) WHERE os=$conta_os)	";
												$resX = pg_exec ($con,$sql);
												if (pg_numrows ($resX) > 0) {
													$embarcado      = trim(pg_result($resX,0,embarcado));
												}
												if($embarcado==$conta_item ){
													$bolinha="rosa";
												}
											}
										}
									}else {
										$bolinha="amarelo";
									}
								}elseif($login_fabrica==11){
									if (strlen($faturamento_item)>0 ){
										$sql  = "SELECT *
													FROM    tbl_faturamento
													JOIN    tbl_faturamento_item USING (faturamento)
													WHERE   tbl_faturamento.fabrica=$login_fabrica
													AND     tbl_faturamento_item.faturamento_item = $faturamento_item";

										$resx = pg_exec ($con,$sql);

										if (pg_numrows ($resx) == 0) {
											$bolinha="amarelo";
										}else {
											$nota_fiscal=pg_result($resx,0,nota_fiscal);
											if(strlen($nota_fiscal) > 0){
												$bolinha="rosa";
											}
										}
									}else{
										if (strlen($pedido) > 0 and $pedido <> $pedido_anterior) {
											$bolinha="amarelo";
										}
									}
								} else {
									if (strlen ($nota_fiscal) == 0) {
										if (strlen($pedido) > 0 and (($peca <> $peca_anterior and $pedido<>$pedido_anterior) or ($peca <> $peca_anterior and $pedido == $pedido_anterior))) {
											$sqlx  = "SELECT *
													FROM    tbl_faturamento
													JOIN    tbl_faturamento_item USING (faturamento)
													WHERE   tbl_faturamento.pedido    = $pedido
													AND     tbl_faturamento_item.peca = $peca;";
											$resx = pg_exec ($con,$sqlx);
											if (pg_numrows ($resx) == 0) {
												$condicao_01 = " 1=1 ";
												if (strlen ($distribuidor) > 0) {
													$condicao_01 = " tbl_faturamento.distribuidor = $distribuidor ";
												}
												$sqlxx  = "SELECT *
														FROM    tbl_faturamento
														JOIN    tbl_faturamento_item USING (faturamento)
														WHERE   tbl_faturamento_item.pedido = $pedido
														AND     tbl_faturamento_item.peca   = $peca
														AND     $condicao_01 ";
												$resxx = pg_exec ($con,$sqlxx);

												if (pg_numrows ($resxx) == 0) {
													if ($login_fabrica==1){
														$sqlxxx  = "SELECT *
																	FROM    tbl_pendencia_bd_novo_nf
																	WHERE   posto        = $login_posto
																	AND     pedido_banco = $pedido
																	AND     peca         = $peca";

														$resxxx = pg_exec ($con,$sqlxxx);

														if (pg_numrows ($resxxx) > 0) {
															$bolinha="amarelo";
														}
													}else{
														$bolinha="amarelo";
													}
												}else{
														$bolinha="rosa";
												}
											}
										}
									}
								}
							$os_anterior     = $os;
							$peca_anterior   = $peca;
							$pedido_anterior = $pedido;
							$faturamento_anterior = $faturamento;
						}
					}
				}

				if(strlen($data_conserto) > 0) {
					$bolinha="azul";
				}
			}
//HD 4291 Fim
			if (strlen($linha_erro[$i]) > 0) $cor = "#FF0000";

?>

		<tr bgcolor=<?
			echo $cor;
			echo " rel='$bolinha' ";

			/*
				if($cor == "#FF0000"){
					echo "rel='mais_30'";
				}
				if($cor == "#FFCC66"){
					echo "rel='sem_defeito'";
				}
				if($cor == "#99FFFF"){
					echo "rel='erro_post'";
				}
			*/

			?> <? if ($linha_erro == $i and strlen ($msg_erro) > 0 )?>>
			<input type='hidden' name='os_<? echo $i ?>' value='<? echo pg_result ($res,$i,os) ?>' >
			<?if($login_fabrica==1){?>
			<input type='hidden' name='conta_<? echo $i ?>' value='<? echo $i;?>'>
			<input type='hidden' name='consumidor_revenda_<? echo $i ?>' value='<? echo pg_result ($res,$i,consumidor_revenda)?>'>
			<?}?>
			<td align="center">
<? if($login_fabrica == 3 and strlen($os_item)>0){?>
			<input type='hidden' name='os_item_<? echo $i ?>' value='<? echo "$os_item"; ?>'>
<?}

			?>
			<? if (strlen($flag_bloqueio) == 0) { ?>

					<input type="checkbox" class="frm" name="ativo_<?echo $i?>" id="ativo" value="t" <?
							if($login_fabrica==3){	?> onClick='javascript:mostraDados(<?echo $i; ?>);'
							<? } ?>>

			 <?  }     ?></td>
			<? //Alterado por Wellington 06/12/2006 a pedido de Luiz Antonio, posto Jundservice (Lenoxx) deve abrir os_item ao clicar na OS ?>

			<?
			//HD 4291 Paulo
			if (($login_posto == '4311' or $login_posto == '6359' or $login_fabrica == 15 or $login_fabrica == 45 or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50) and strlen($bolinha) > 0) {
				$bolinha = "<img src='imagens/status_$bolinha' width='10' align='absmiddle'>";
			} else {
				$bolinha="";
			}
			//Fim
			?>

	<td>
	<?if (($login_posto=='4311' or $login_posto=='6359' or $login_fabrica ==15 or $login_fabrica ==45 or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50) and strlen($bolinha) > 0) { echo $bolinha; } ?><a href='<? if ($cor == "#FFCC66" or ($login_fabrica==11 and $login_posto==14254)) echo "os_item"; else echo "os_press"; ?>.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1)echo $login_codigo_posto; echo $sua_os; ?></a></td>
			<? //HD 23623 ?>
			<? if($login_fabrica == 11 and $login_posto == 14301) echo "<td>$prateleira_box</td>"; ?>
			<td><? echo pg_result ($res,$i,data_abertura); ?></td>
			<td NOWRAP ><? echo substr (pg_result ($res,$i,consumidor_nome),0,10); ?></td>
			<? if($login_fabrica == 30){
			 $serie = pg_result ($res,$i,serie);
			?>
			<td NOWRAP><? echo substr ($descricao,0,15); ?></td>
			<? }else{
				if($login_fabrica == 11){ ?>
					<td NOWRAP><? echo pg_result ($res,$i,serie)." - ".substr($referencia,0,15); ?></td>
				<?}else{?>
					<td NOWRAP><? echo pg_result ($res,$i,serie) . " - " . substr ($descricao,0,15); ?></td>
				<?}?>
			<? } ?>
<? if ($login_fabrica <> 2 AND $login_fabrica <> 1 AND $login_fabrica<>20){ ?>
			<?
			# Lorenzetti - Quando OS aberta pelo SAC para atendimento em Domicilio, obrigatorio NF de Devolucao
			if ($consumidor_revenda == 'R' and $login_fabrica <> 14){
				if($login_fabrica == 15){
					echo "<td><input class='frm' type='text' name='serie_reoperado_$i' size='15' maxlength='20' value='$serie_reoperado'></td>";
				}
				if($login_fabrica == 30 and strlen($serie)==0){
					echo "<td><input class='frm' type='text' name='serie_$i' size='15' maxlength='20' value='$serie'></td>";
				}else if(strlen($serie)>0){
						echo "<td>$serie";
						echo "<input type='hidden' name='serie_$i' value='$serie'>";
						echo "</td>";
				}
				echo "<td><input class='frm' type='text' name='nota_fiscal_saida_$i' size='8' maxlength='10' value='$nota_fiscal_saida'></td>";
				echo "<td><input class='frm' type='text' name='data_nf_saida_$i' size='12'  onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf_saida_$i')\"  maxlength='10' value='$data_nf_saida'></td>";
			}else{
				if($login_fabrica == 30 and strlen($serie)==0){
					echo "<td><input class='frm' type='text' name='serie_$i' size='15' maxlength='20' value='$serie'></td>";
				}else if(strlen($serie)>0){
					echo "<td>$serie";
					echo "<input type='hidden' name='serie_$i' value='$serie'>";
					echo "</td>";
				}

				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
			}
			?>
<? } ?>
<?
if ($login_fabrica == "20") {

    $pecas              = 0;
    $mao_de_obra        = 0;
    $tabela             = 0;
    $desconto           = 0;
    $desconto_acessorio = 0;

    $ysql = "SELECT mao_de_obra FROM tbl_produto_defeito_constatado WHERE produto = (SELECT produto FROM tbl_os WHERE os = $os) AND defeito_constatado = (SELECT defeito_constatado FROM tbl_os WHERE os = $os)";
    $yres = pg_exec ($con,$ysql);
    if (pg_numrows ($yres) == 1) {
        $mao_de_obra = pg_result ($yres,0,mao_de_obra);
    }

    $ysql = "SELECT tabela,desconto,desconto_acessorio FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $yres = pg_exec ($con,$ysql);

    if (pg_numrows ($yres) == 1) {

        $tabela             = pg_result ($yres,0,tabela)            ;
        $desconto           = pg_result ($yres,0,desconto)          ;
        $desconto_acessorio = pg_result ($yres,0,desconto_acessorio);

    }
    if (strlen ($desconto) == 0) $desconto = "0";

    if (strlen ($tabela) > 0) {

        $ysql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
                FROM tbl_os
                JOIN tbl_os_produto USING (os)
                JOIN tbl_os_item    USING (os_produto)
                JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
                WHERE tbl_os.os = $os";
        $yres = pg_exec ($con,$ysql);

        if (pg_numrows ($yres) == 1) {
            $pecas = pg_result ($yres,0,0);
        }
    }else{
        $pecas = "0";
    }

    $valor_liquido = 0;

    if ($desconto > 0 and $pecas <> 0) {

        $ysql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
        $yres = pg_exec ($con,$ysql);
        if (pg_numrows ($res) == 1) {
            $produto = pg_result ($yres,0,0);
        }
        //echo 'peca'.$pecas;
        if( $produto == '20567' ){
            $desconto_acessorio = '0.2238';
            $valor_desconto = round ( (round ($pecas,2) * $desconto_acessorio ) ,2);

        }else{
            $valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
        }

        $valor_liquido = $pecas - $valor_desconto ;

    }
    $acrescimo = 0;
	if($login_pais<>"BR"){
		$ysql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$yres = pg_exec ($con,$ysql);

		if (pg_numrows ($yres) == 1) {
			$valor_liquido = pg_result ($yres,0,pecas);
			$mao_de_obra   = pg_result ($yres,0,mao_de_obra);
		}
		$ysql = "select imposto_al  from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica";
		$yres = pg_exec ($con,$ysql);

		if (pg_numrows ($yres) == 1) {
			$imposto_al   = pg_result ($yres,0,imposto_al);
			$imposto_al   = $imposto_al / 100;
			$acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
		}
	}

	//HD 9469 - Alteração no cálculo da BOSCH do Brasil HD 48439
	if($login_pais=="BR") {
		$sqlxx = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$resxx = pg_exec ($con,$sqlxx);

		if (pg_numrows ($resxx) == 1) {
			$valor_liquido = pg_result ($resxx,0,pecas);
			$mao_de_obra   = pg_result ($resxx,0,mao_de_obra);
		}
	}


    $total = $valor_liquido + $mao_de_obra + $acrescimo;

    $total          = number_format ($total,2,",",".")         ;
    $mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
    $acrescimo      = number_format ($acrescimo ,2,",",".")    ;
    $valor_desconto = number_format ($valor_desconto,2,",",".");
    $valor_liquido  = number_format ($valor_liquido ,2,",",".");

//	$data_conserto = "";

    echo "<td align='center'>" ;
    echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$valor_liquido</font>" ;
    echo "</td>";
    echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$mao_de_obra</font></td>";

}

if($login_posto=='4311' or $login_posto == '6359') {
	echo "<td align='center'>$prateleira_box</td>";
}
//HD 12521 //HD13239 hd 14121 HD 48989(bosch)
if($login_fabrica == 3 or $login_posto == '4311' or (($login_fabrica <> 11 and $login_fabrica <>1) and $login_posto== '6359') or $login_fabrica == 15 or $login_fabrica == 45 or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50 or ($login_fabrica ==20)){
	echo "<td align='center'>";
		if($login_fabrica == 3 AND strlen($data_conserto)>0){
			echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto' disabled>";
		}
	echo "</td>";
	}


?>
		</tr>
<? if($login_fabrica == 3 and strlen($os_item)>0){?>
		<?  //HD 6477
		$sqlp = "SELECT peca, pedido, qtde FROM tbl_os_item WHERE os_item = $os_item;";

		#HD 51236 - gera_pedido IS TRUE
		$sqlp = "	SELECT tbl_os_item.peca, tbl_os_item.pedido, tbl_os_item.qtde
					FROM tbl_os_item
					JOIN tbl_servico_realizado USING(servico_realizado)
					WHERE tbl_servico_realizado.gera_pedido IS TRUE
					AND   tbl_os_item.os_item = $os_item;";
		$resp = pg_exec($con, $sqlp);

		if (pg_numrows($resp) > 0) {
			$pendente = "f";

			$pedido = pg_result($resp,0,pedido);
			$peca   = pg_result($resp,0,peca);
			$qtde   = pg_result($resp,0,qtde);

			if (strlen($pedido) > 0) {
				$sqlp = "SELECT os
						FROM tbl_pedido_cancelado
						WHERE pedido  = $pedido
						AND   peca    = $peca
						AND   qtde    = $qtde
						AND   os      = $os
						AND   fabrica = $login_fabrica";
				$resp = pg_exec($con, $sqlp);

				if (pg_numrows($resp) == 0) $pendente = "t";
			} else {
				$pendente = "t";
			}


			if ($pentende = "t") {?>
				<TR>
					<td colspan='7'>
						<div id='dados_<? echo $i; ?>' style='position:relative; display:none; border: 1px solid #FF6666;background-color: #FFCC99;width:100%; font-size:9px'><? fecho ("esta.os.que.voce.esta.fechando.tem.pecas", $con, $cook_idioma); /*Esta OS que você está fechando tem peças*/?> <strong><? fecho ("pendentes", $con, $cook_idioma); /*pendentes*/?></strong>! <? fecho ("motivo.do.fechamento", $con, $cook_idioma); /*Motivo do Fechamento: */?>
							<input class='frm' type='text' name='motivo_fechamento_<?echo$i;?>' size='30' maxlength='100' value=''>
						</div>
					</td>
				</tr>
			<?}
		}
}?>
<?if($login_fabrica==11 and $login_posto==14301 and strlen($consumidor_email) >0){ ?>
<TR bgcolor="<?echo $cor;?>"><td colspan="100%"><? fecho ("observacao", $con, $cook_idioma); /*Observação*/?>: <input type="text" name="observacao_<?echo $i;?>" size="100" maxlength="200" value="" title="<? fecho ("esta.informacao.sera.inserido.na.interacao.da.os.mandado.junto.com.o.email", $con, $cook_idioma); /*Esta informação será inserido na interação da OS e mandado junto com o email*/?>"></td></TR>
<?
}
			 }
	$os_anterior = $os;
}?>

</tbody>
		</table>
<br><br>

<?//HD 9013
	if($login_fabrica=='1' or $login_fabrica ==7){ ?>
		<table width="650" border="0" cellspacing="1" cellpadding="4" align="center" style='font-family: verdana; font-size: 10px' class='tabela_resultado sample'>
		<caption colspan='100%' style='font-family: verdana; font-size: 20px'>
		<?	if($login_fabrica ==7) fecho ("os.de.manutencao", $con, $cook_idioma); /*"OS de Manutenção"*/
			else                   fecho ("os.de.revenda", $con, $cook_idioma); /*"OS de Revenda"*/
		?>
		</font><caption>
		<thead>
		<tr height="20">
			<th>
			<input type='checkbox' class='frm' name='marcar' value='tudo' title='<? fecho ("selecione.ou.desmarque.todos", $con, $cook_idioma) /*Selecione ou desmarque todos*/;?>' onClick='SelecionaTodos(this.form.ativo_revenda);' style='cursor: hand;'>
			</th>
			<th nowrap><b><? fecho ("os.fabricante", $con, $cook_idioma); /*OS Fabricante*/ ?></b></th>
			<th nowrap><b><? fecho ("data.abertura", $con, $cook_idioma); /*Data Abertura*/ ?></b></th>
			<th nowrap><b><? fecho ("consumidor", $con, $cook_idioma); /*Consumidor*/ ?></b></th>
			<th nowrap><b><? fecho ("produto", $con, $cook_idioma); /*Produto*/ ?></b></th>
			<?
			if($login_fabrica <> 1){
				echo "<th nowrap><strong>";
				fecho ("nf.saida", $con, $cook_idioma); /*NF de Saída*/
				echo "</strong></th>";
				echo "<th nowrap><strong>";
				fecho ("data.nf.saida", $con, $cook_idioma); /*Data NF de Saída*/
				echo "</strong></th>";
			}
			if($login_posto=='4311' or $login_posto == '6359' ) {
				echo "<th nowrap><strong>";
				fecho ("box", $con, $cook_idioma); /*Box*/
				echo "</strong></th>";
			}
			//HD 12521       HD 13239    HD 14121
			if(($login_fabrica <> 11 and $login_fabrica <>1) and $login_posto== 6359) {
				echo "<th nowrap><strong>";
				fecho ("data.de.conserto", $con, $cook_idioma); /*Data de conserto*/
				echo "</strong></th>";
			} ?>
		</tr>
		</thead>
		<tbody>
<?
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$flag_cor = "";
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			 $consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
			 if($consumidor_revenda=='R' and ($login_fabrica==1 or $login_fabrica ==7)){
			$os               = trim(pg_result ($res,$i,os));
			$sua_os           = trim(pg_result ($res,$i,sua_os));
			$admin            = trim(pg_result ($res,$i,admin));
			$tipo_atendimento = trim(pg_result ($res,$i,tipo_atendimento));
			$produto          = trim(pg_result ($res,$i,produto));
			//HD 13239
			if((($login_fabrica <> 11 and $login_fabrica<>1) and $login_posto== '6359') or $login_fabrica ==7 or $login_fabrica==43 or $login_fabrica >50){
				$data_conserto           = trim(pg_result ($res,$i,data_conserto));
			}
			//HD 4291 Paulo
			if($login_posto=='4311'or $login_posto == 6359) {
				$prateleira_box          = trim(pg_result ($res,$i,prateleira_box));
			}
			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_result ($res,$i,descricao) ;

			 $consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
			 $defeito_constatado = trim(pg_result ($res,$i,defeito_constatado));
			if($login_fabrica ==1){
				$sql = "SELECT os
						FROM   tbl_os
						WHERE  os = $os
						AND    motivo_atraso IS NULL
						AND    consumidor_revenda = 'C'
						AND    (data_abertura + INTERVAL '30 days')::date < current_date;";
				$resY = pg_exec($con, $sql);
				if (pg_numrows($resY) > 0) {
					$cor = "#FF0000";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}

				if (strlen($defeito_constatado) == 0) {
					$cor = "#FFCC66";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}elseif ($flag_bloqueio == "t" AND strlen($defeito_constatado) <> 0){
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}
			}
			//HD 4291 Paulo verificar a peça pendente da os e mudar cor
			// HD 14121
			if($login_posto=='4311' or $login_posto=='6359' or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50 ) {
				$bolinha="";

				$sqlcor="SELECT *
							FROM tbl_os
							WHERE defeito_constatado is null
							AND	  solucao_os is null
							AND	  os=$os";
				$rescor=pg_exec($con,$sqlcor);
				if(pg_numrows($rescor) > 0) {
					$bolinha="vermelho";
				} else {

					$sqlcor2 = "SELECT	tbl_os_item.pedido   ,
										tbl_os_item.peca                      ,
										tbl_pedido.distribuidor             ,
										tbl_os_item.faturamento_item       ";
					if(strlen($os_item)==0){
						$sqlcor2 .=", tbl_os_item.os_item ";
					}
					$sqlcor2 .=	"FROM    tbl_os_produto
								JOIN    tbl_os_item USING (os_produto)
								JOIN    tbl_produto USING (produto)
								JOIN    tbl_peca    USING (peca)
								LEFT JOIN tbl_defeito USING (defeito)
								LEFT JOIN tbl_servico_realizado USING (servico_realizado)
								LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
								LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
								LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
								LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
								WHERE   tbl_os_produto.os = $os";

					$rescor2 = pg_exec($con,$sqlcor2);

					if(pg_numrows($rescor2) > 0) {

						for ($j = 0 ; $j < pg_numrows ($rescor2) ; $j++) {
							$pedido               = trim(pg_result($rescor2,$j,pedido));
							$peca                 = trim(pg_result($rescor2,$j,peca));
							$distribuidor         = trim(pg_result($rescor2,$j,distribuidor));
							$faturamento_item     = trim(pg_result($rescor2,$j,faturamento_item));
							if(strlen($os_item) ==0)$os_item              = trim(pg_result($rescor2,$j,os_item));
							$bolinha="";
							if ($login_fabrica == 3) {
								if (strlen($pedido) > 0) {
										$sql  = "SELECT *
												FROM    tbl_faturamento
												JOIN    tbl_faturamento_item USING (faturamento)
												WHERE   tbl_faturamento_item.pedido  = $pedido
												AND     tbl_faturamento_item.peca    = $peca";
										if($distribuidor=='4311'){
											$sql .=" AND     tbl_faturamento_item.os_item = $os_item
													 AND     tbl_faturamento.posto        = $login_posto
													 AND     tbl_faturamento.distribuidor = 4311";
										}elseif(strlen($distribuidor)>0 ){
											$sql .=" tbl_faturamento.posto = $distribuidor ";
										}else{
											$sql .=" AND     (length(tbl_faturamento_item.os) = 0 OR tbl_faturamento_item.os = $os)
													 AND     tbl_faturamento.posto       = $login_posto";
										}
										$resx = pg_exec ($con,$sql);
										if (pg_numrows ($resx) == 0) {
											$bolinha="amarelo";
										}
									$sql="SELECT count(os_item) as conta_item,
												 os as conta_os
											FROM tbl_os_produto
											JOIN tbl_os_item using(os_produto)
											WHERE os=$os
											GROUP BY os";
									$resX = pg_exec ($con,$sql);
									if (pg_numrows ($resX) > 0) {
										$conta_item=pg_result($resX,0,conta_item);
										$conta_os  =pg_result($resX,0,conta_os);
										if(strlen($conta_item) > 0){
											$sql = "SELECT	count(embarcado) as embarcado
													FROM tbl_embarque_item
													JOIN tbl_os_item ON tbl_os_item.os_item = tbl_embarque_item.os_item
													JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
													WHERE tbl_os_item.os_item in (SELECT os_item FROM tbl_os_produto JOIN tbl_os_item using(os_produto) WHERE os=$conta_os)	";
											$resX = pg_exec ($con,$sql);
											if (pg_numrows ($resX) > 0) {
												$embarcado      = trim(pg_result($resX,0,embarcado));
											}
											if($embarcado==$conta_item ){
												$bolinha="rosa";
											}
										}
									}
								}else {
									$bolinha="amarelo";
								}
							}elseif($login_fabrica==11){
								if (strlen($faturamento_item)>0){
									$sql  = "SELECT *
												FROM    tbl_faturamento
												JOIN    tbl_faturamento_item USING (faturamento)
												WHERE   tbl_faturamento.fabrica=$login_fabrica
												AND     tbl_faturamento_item.faturamento_item = $faturamento_item";

									$resx = pg_exec ($con,$sql);

									if (pg_numrows ($resx) == 0) {
										$bolinha="amarelo";
									}else {
										$nota_fiscal=pg_result($resx,0,nota_fiscal);
										if(strlen($nota_fiscal) > 0){
											$bolinha="rosa";
										}
									}
								}else{
									if (strlen($pedido) > 0) {
										$bolinha="amarelo";
									}
								}
							} else {
								if (strlen ($nota_fiscal) == 0) {
									if (strlen($pedido) > 0) {
										$sql  = "SELECT *
												FROM    tbl_faturamento
												JOIN    tbl_faturamento_item USING (faturamento)
												WHERE   tbl_faturamento.pedido    = $pedido
												AND     tbl_faturamento_item.peca = $peca;";
										$resx = pg_exec ($con,$sql);

										if (pg_numrows ($resx) == 0) {
											$condicao_01 = " 1=1 ";
											if (strlen ($distribuidor) > 0) {
												$condicao_01 = " tbl_faturamento.distribuidor = $distribuidor ";
											}
											$sql  = "SELECT *
													FROM    tbl_faturamento
													JOIN    tbl_faturamento_item USING (faturamento)
													WHERE   tbl_faturamento_item.pedido = $pedido
													AND     tbl_faturamento_item.peca   = $peca
													AND     $condicao_01 ";
											$resx = pg_exec ($con,$sql);

											if (pg_numrows ($resx) == 0) {
												if ($login_fabrica==1){
													$sql  = "SELECT *
																FROM    tbl_pendencia_bd_novo_nf
																WHERE   posto        = $login_posto
																AND     pedido_banco = $pedido
																AND     peca         = $peca";
													$resx = pg_exec ($con,$sql);

													if (pg_numrows ($resx) > 0) {
														$bolinha="amarelo";
													}
												}else{
													$bolinha="amarelo";
												}
											}
										}
									}
								}
							}
						}
					}
				}

				if(strlen($data_conserto) > 0) {
					$bolinha="azul";
				}
			}
//HD 4291 Fim
			if (strlen($linha_erro[$i]) > 0) $cor = "#99FFFF";

?>

		<tr bgcolor=<?
			echo $cor;
			echo " rel='$bolinha' ";

			?> <? if ($linha_erro == $i and strlen ($msg_erro) > 0 )?>>
			<input type='hidden' name='os_<? echo $i ?>' value='<? echo pg_result ($res,$i,os) ?>' >
			<input type='hidden' name='conta_<? echo $i ?>' value='<? echo $i;?>'>
			<input type='hidden' name='consumidor_revenda_<? echo $i ?>' value='<? echo pg_result ($res,$i,consumidor_revenda)?>'>
			<td align="center">
			<? if (strlen($flag_bloqueio) == 0) { ?><input type="checkbox" class="frm" name="ativo_revenda_<?echo $i?>" id="ativo_revenda" value="t" ><? } ?></td>

			<?
			//HD 4291 Paulo
			if (($login_posto == '4311' or $login_posto == '6359' or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50) and strlen($bolinha) > 0) {
				$bolinha = "<img src='imagens/status_$bolinha' width='10' align='absmiddle'>";
			} else {
				$bolinha="";
			}
			//Fim
			?>

	<td><? if (($login_posto=='4311' or $login_posto=='6359' or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50) and strlen($bolinha) > 0) { echo $bolinha; } ?><a href='<? if ($cor == "#FFCC66" or ($login_fabrica==11 and $login_posto==14254)) echo "os_item"; else echo "os_press"; ?>.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1)echo $login_codigo_posto; echo $sua_os; ?></a></td>
			<td><? echo pg_result ($res,$i,data_abertura) ?></td>
			<td NOWRAP ><? echo substr (pg_result ($res,$i,consumidor_nome),0,10) ?></td>
			<td NOWRAP><? echo pg_result ($res,$i,serie) . " - " . substr ($descricao,0,15) ?></td>
<?
	if($login_fabrica <>1){
		echo "<td><input class='frm' type='text' name='nota_fiscal_saida_$i' size='8' maxlength='10' value='$nota_fiscal_saida'></td>";
		echo "<td><input class='frm' type='text' name='data_nf_saida_$i' size='12'  onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf_saida_$i')\"  maxlength='10' value='$data_nf_saida'></td>";
	}

if($login_posto=='4311' or $login_posto == '6359') {
	echo "<td align='center'>$prateleira_box</td>";
}
//HD 12521 //HD13239 hd 14121
if((($login_fabrica <> 11 and $login_fabrica<>1) and $login_posto== '6359') or $login_fabrica ==7 or $login_fabrica ==43 or $login_fabrica >50){
	echo "<td align='center'>";
		if(strlen($data_conserto)>0){
			echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto' disabled>";
		}else{
			echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto'>";
		}
	echo "</td>";
	}

?>
	</tr>

<?}
			}?>
</tbody>
		</table>

<?}?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" background="" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">

<?		if($sistema_lingua == "ES"){?>
		<img src='imagens/btn_cerrar_maior.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar con orden de servicio" border='0' style='cursor: pointer'>

		<? }else{ ?>
		<img src='imagens/btn_fechar_azul.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
		<? }?>
		</td>
</tr>

</form>

</table>
<?

		// ##### PAGINACAO ##### //
		// links da paginacao
		echo "<br>";

		echo "<div>";

		if($pagina < $max_links) {
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("todos", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}

		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array("<b>$resultado_inicial</b>","<b>$resultado_final</b>","<b>$registros</b>"));
			echo "<font color='#cccccc' size='1'>";
			fecho("pagina.%.de.%",$con,$cook_idioma,array("<b>$valor_pagina</b>","<b>$numero_paginas</b>"));
			echo "</font>";
			echo "</div>";
		}
		// ##### PAGINACAO ##### //

		}else{
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td valign="top" align="center">
		<h4>
		<?
		/*if($sistema_lingua == "ES") echo "No fue(ran) encuentrada(s) OS(s) cerrada(s)";
		else                        echo "Não foi(ram) encontrada(s) OS(s) não finalizada(s).";*/
		fecho ("nao.foi.encontrada.os.nao.finalizada", $con, $cook_idioma);
		?>

		</h4>
	</td>
</tr>
</table>
<?
		}

	}
?>
<p>

<? include "rodape.php"; ?>
