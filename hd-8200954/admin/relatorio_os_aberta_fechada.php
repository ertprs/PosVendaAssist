<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico == 'automatico'){
	$login_fabrica = trim($_GET["login_fabrica"]);
	$login_admin   = trim($_GET["login_admin"]);
}else{
	include "autentica_admin.php";
}

include "funcoes.php";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$msg_erro = "";

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);

if (strlen(trim($_POST["agendar"])) > 0) $agendar = trim($_POST["agendar"]);
if (strlen(trim($_GET["agendar"])) > 0)  $agendar = trim($_GET["agendar"]);

if (strlen($acao) > 0 && $acao == "PESQUISAR" ) {
	if($agendar!='SIM'){

		if (!in_array($login_fabrica, array(11,172))){
			if (strlen($mes) > 0 AND strlen($ano) > 0){
				$sql = "SELECT fn_dias_mes('$ano-$mes-01',0)";
				$res3 = pg_exec($con,$sql);
				$data_inicial = pg_result($res3,0,0);

				$sql = "SELECT fn_dias_mes('$ano-$mes-01',1)";
				$res3 = pg_exec($con,$sql);
				$data_final = pg_result($res3,0,0);
			}else{
				$msg_erro.="SELECIONE MÊS E ANO PARA FAZER PESQUISA.";
			}
		}


	}

if (in_array($login_fabrica, array(11,172))){
	if ($data_inicial_2<>'' and $data_final_2<>''){
		$data_inicial=$data_inicial_2;
		$data_final=$data_final_2;


	}else{
		$msg_erro.="Preenche a data inicial e data final para a pesquisa";
	}

}


	if($agendar=='SIM' and !in_array($login_fabrica, array(11,172))){
		

		if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
		$data_final   = $_POST['data_final'];
		if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
		

			if(strlen($data_inicial) ==0 or strlen($data_final) ==0 ){
				$msg_erro.="Preenche a data inicial e data final para a pesquisa";
			}else{
				if (strlen ($data_inicial) >0) 
					$data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
					if (strlen ($data_final) >0) 
						$data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

						$data_inicial=$data_inicial." 00:00:00";
						$data_final=$data_final." 23:59:59";
					}
				}	

				if (strlen(trim($_POST["cidade"])) > 0) $cidade = trim($_POST["cidade"]);
				if (strlen(trim($_GET["cidade"])) > 0)  $cidade = trim($_GET["cidade"]);

				if (strlen(trim($_POST["estado"])) > 0) $estado = trim($_POST["estado"]);
				if (strlen(trim($_GET["estado"])) > 0)  $estado = trim($_GET["estado"]);
				
			if (!in_array($login_fabrica, array(11,172))){
				if(strlen($estado) ==0 ){
					$msg_erro.="SELECIONE O ESTADO PARA FAZER PESQUISA";
				}
			}

		if (strlen(trim($_POST["revenda_nome"])) > 0) $revenda_nome = trim($_POST["revenda_nome"]);
		if (strlen(trim($_GET["revenda_nome"])) > 0)  $revenda_nome = trim($_GET["revenda_nome"]);

		if (strlen(trim($_POST["revenda_cnpj"])) > 0) $revenda_cnpj = trim($_POST["revenda_cnpj"]);
		if (strlen(trim($_GET["revenda_cnpj"])) > 0)  $revenda_cnpj = trim($_GET["revenda_cnpj"]);

		if (strlen(trim($_POST["status"])) > 0) $status = trim($_POST["status"]);
		if (strlen(trim($_GET["status"])) > 0)  $status = trim($_GET["status"]);

		if (strlen(trim($_POST["formato_arquivo"])) > 0) $formato_arquivo = trim($_POST["formato_arquivo"]);
		if (strlen(trim($_GET["formato_arquivo"])) > 0)  $formato_arquivo = trim($_GET["formato_arquivo"]);
	}


$layout_menu = "auditoria";
$title = "Relatorio de OS Abertas e Fechadas";
include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.divisao{
	width:600px;
	text-align:center;
	margin:0 auto;
	font-size:10px;
	background-color:#FEFCCF;
	border:1px solid #928A03;
	padding:5px;
}
.sucesso{
	width:500px;
	text-align:left;
	margin:0 auto;
	font-size:10px;
	background-color:#E3FBE4;
	border:1px solid #0F6A13;
	color:#07340A;
	padding:5px;
	font-size:13px;
}
.menu_ajuda{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#332D00;
	background-color: #FFF9CA;
}
</style>
<script language='javascript' src='../ajax.js'></script>

<? include "javascript_calendario.php"; //adicionado por suporte 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

		$('#data_inicial_2').datePicker({startDate:'01/01/2000'});
		$('#data_final_2').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_2").maskedinput("99/99/9999");
		$("#data_final_2").maskedinput("99/99/9999");
	});
</script>

<script language="JavaScript">
function toogleProd(radio){

	var obj = document.getElementsByName('agendar');
	/*for(var x=0 ; x<obj.length ; x++){*/

	if (obj[0].checked){
		$("#data_inicial").attr('disabled',false); 
		$("#data_final").attr('disabled',false); 
		$("#mes").attr('disabled',true); 
		$("#ano").attr('disabled',true); 
		$("div[@rel='id_sem_agenda']").hide("slow");
		$("div[@rel='id_com_agenda']").show("slow");
		$("#label_1").html("Data Inicial");
		$("#label_2").html("Data Final");
	}
	if (obj[1].checked){
		$("#data_inicial").attr('disabled',true); 
		$("#data_final").attr('disabled',true);
		$("#mes").attr('disabled',false); 
		$("#ano").attr('disabled',false); 
		$("div[@rel='id_com_agenda']").hide("slow");
		$("div[@rel='id_sem_agenda']").show("slow");
		$("#label_1").html("Mês");
		$("#label_2").html("Ano");
	}

}
</script>

<script>

// ========= Fun??o PESQUISA DE REVENDA POR NOME OU CNPJ ========= //

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_relatorio.revenda_nome;
			janela.cnpj			= document.frm_relatorio.revenda_cnpj;
			janela.fone			= document.frm_relatorio.revenda_fone;
			janela.cidade		= document.frm_relatorio.revenda_cidade;
			janela.estado		= document.frm_relatorio.revenda_estado;
			janela.endereco		= document.frm_relatorio.revenda_endereco;
			janela.numero		= document.frm_relatorio.revenda_numero;
			janela.complemento	= document.frm_relatorio.revenda_complemento;
			janela.bairro		= document.frm_relatorio.revenda_bairro;
			janela.cep			= document.frm_relatorio.revenda_cep;
			janela.email		= document.frm_relatorio.revenda_email;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}
function AgendaData(form,agenda_data){

	if (agenda_data == 'N'){
		form.mes.disabled=false;
		form.ano.disabled=false;
	}else{
		form.mes.disabled=true;
		form.ano.disabled=true;
	}
}
</script>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<br>
<?


if (in_array($login_fabrica, array(11,172))){
	
	if ($agendar=='NAO'){

		$inicio=$data_inicial;
		$fim=$data_final;
		$intervalo=$fim-$inicio;

		//defino data 1 
		$ano1 = substr($inicio, 6, 10);

		$mes1 = substr($inicio, 3, 2);

		$dia1 = substr($inicio, 0, 2);

		//defino data 2 
		$ano2 = substr($fim, 6, 10); 
		$mes2 = substr($fim, 3, 2); 
		$dia2 = substr($fim, 0, 2);  

		//calculo timestam das duas datas 
		$timestamp1 = mktime(0,0,0,$mes1,$dia1,$ano1); 
		$timestamp2 = mktime(4,12,0,$mes2,$dia2,$ano2); 

		//diminuo a uma data a outra 
		$segundos_diferenca = $timestamp1 - $timestamp2; 
		//echo $segundos_diferenca; 

		//converto segundos em dias 
		$dias_diferenca = $segundos_diferenca / (60 * 60 * 24); 

		//obtenho o valor absoluto dos dias (tiro o possível sinal negativo) 
		$dias_diferenca = abs($dias_diferenca); 

		//tiro os decimais aos dias de diferenca 
		$dias_diferenca = floor($dias_diferenca); 

		if ($dias_diferenca>30){
			$msg_erro = "<B>Você selecionou o intervalo de $dias_diferenca dias.</B><br>Se a opção agendamento estiver selecionado não o intervalo entre as datas não podem exceder a 30 dias.";
		} 

	}
}



if (strlen($acao) > 0 && strlen($msg_erro) == 0) {

	if ($agendar=='SIM' AND $gera_automatico != 'automatico'){

		$parametros = "";
		foreach ($_POST as $key => $value){
			$parametros .= $key."=".$value."&";
		}
		$sql = "SELECT email 
				FROM tbl_admin
				WHERE admin   = $login_admin";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			$email = trim(pg_result($res,0,email));
			if (strlen($email)==0){
				$msg_erro = "Verifique seu email antes de agendar um relatório.";
			}else{
				$sql = "SELECT relatorio_agendamento 
						FROM tbl_relatorio_agendamento
						WHERE admin   = $login_admin
						AND programa  = '$PHP_SELF'
						AND executado IS NULL";
				$res = pg_exec($con,$sql);
				if (pg_numrows($res) > 0) {
					$relatorio_agendamento = trim(pg_result($res,0,relatorio_agendamento));
					$sql = "UPDATE tbl_relatorio_agendamento SET parametros = '$parametros'
							WHERE relatorio_agendamento = $relatorio_agendamento";
					$res = pg_exec($con,$sql);
				}else{
					$sql = "INSERT INTO tbl_relatorio_agendamento (admin,programa,parametros,titulo) VALUES ($login_admin,'$PHP_SELF','$parametros','$title')";
					$res = pg_exec($con,$sql);
				}
				//echo "<p>".$sql."</p>";
				echo "<p class='sucesso'>O agendamento do relatório foi feito com sucesso! Este relatório está agendado para executar nesta noite e será enviado para seu email assim que for concluído.";
				echo "<br><b>Atenção:</b> <u>é necessário que o cadastro de seu email esteja correto no sistema. Caso contrário, você não receberá o relatório.</u>";
				if (strlen($relatorio_agendamento)>0){
					echo "<br><b>Importante:</b> só é permitido 1 agendamento de relatório por usuário. A última atualização é a que será válida.";
				}
				echo "</p>";
				echo "<p></p>";
			}
		}
	}
}
?>

<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>


<div class='divisao'>
<p>
<img src='imagens/info.png' align='absmiddle'> &nbsp;Para relatório com períodos longos, é necessário agendamento:
	<ol style='padding-left:50px; text-align:left'>
		<li>Preencha os parâmetros</li>
		<li>Em AGENDAMENTO, marque SIM</li>
		<li>Clique em PESQUISAR</li>
		<li>O relatório será processado durante a noite e será enviado para seu email.</li>
	</ol>
</p>
<p>Se não for feito o agendamento, o período máximo é de 1 mês para a pesquisa. Caso seja feito o agendamento, o relatório pode ser feito um período maior.</p>
<p><b>IMPORTANTE:</b> verifique se o cadastro de seu email está correto. Caso contrário o relatório não será enviado corretamente.</p>
</div>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>
		Preeche os parâmetros para pesquisa.<br>Os campos com esta marcação (*) não poder ser nulos.  </td>
	</tr>
	
	<tr>
		<td bgcolor='#DBE5F5'>
				<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>
					<tr>
					<td align='right'>Cidade:&nbsp;</td>
					<td align='left'>
					<input type='text' name=cidade size='20' maxlength='30' value='<? echo $cidade; ?>'>
					</td>
					</tr>
					<tr>
					<td align='right'>Estado:(*)&nbsp;</td>
					<td align='left'>
						<select name="estado" size="1">
							<option value=""><? if( in_array($login_fabrica, array(11,172)) ) { echo "Todos";
							}?></option>
							<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
							<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
							<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
							<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
							<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
							<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
							<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
							<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
							<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
							<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
							<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
							<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
							<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
							<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
							<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
							<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
							<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
							<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
							<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
							<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
							<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
							<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
							<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
							<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
							<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
							<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
							<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<td align='right'>CNPJ Revenda</td> 
					<td align='left'> 
						<input class="Caixa" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_relatorio.revenda_cnpj, "cnpj")' style='cursor: pointer'>
						<input type ='hidden' name='revenda_fone' value=' '>
						<input type ='hidden' name='revenda_cidade' value=' '>
						<input type ='hidden' name='revenda_estado' value=' '>
						<input type ='hidden' name='revenda_endereco' value=' '>
						<input type ='hidden' name='revenda_numero' value=' '>
						<input type ='hidden' name='revenda_complemento' value=' '>
						<input type ='hidden' name='revenda_bairro' value=' '>
						<input type ='hidden' name='revenda_cep' value=' '>
						<input type ='hidden' name='revenda_email' value=' '>
					</td>
				</tr>
				<tr>
					<td align='right'>Nome Revenda</td> 
					<td align='left'> 
						<input class="Caixa" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" >&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_relatorio.revenda_nome, "nome")' style='cursor: pointer'>
					</td>
				</tr>
				<TR>

				<? if (in_array($login_fabrica, array(11,172))){?>
					<Td align='right'><span id='label_1'>Data Inicial</span></Td>
				<?}else{?>
					<Td align='right'><span id='label_1'><?if ($agendar=="SIM") echo "Data Inicial"; else echo "Mês";?></span></Td>
				<?}?>

					<TD align='left'>
						<div rel='id_sem_agenda' style='<?if ($agendar=="SIM") echo "display:none";?>'>
							
				<?if (in_array($login_fabrica, array(11,172))){?>			
						<INPUT size='8' maxlength='10' TYPE='text' NAME='data_inicial_2' id='data_inicial_2' value='<? echo $data_inicial; ?>' >
				<?}else{?>
					<select name="mes" id="mes" size="1" >
					<option value=''></option>
					<?
					for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
					}
					?>
					</select>
				<?}?>


						</div>
						<div rel='id_com_agenda' style='<?if ($agendar!=="SIM") echo "display:none";?>'>
							<INPUT size='8' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' value='<? echo $dt_inicial; ?>' >
						</div>
					</TD>
				</tr>
				<tr>
					
				<?if (in_array($login_fabrica, array(11,172))){?>	
					<Td align='right'><span id='label_2'>Data Final</span></Td>
				<?}else{?>
					<Td align='right'><span id='label_2'><?if ($agendar=="SIM") echo "Data Final"; else echo "Ano";?></span></Td>
				<?}?>

					<TD align='left'>
						<div rel='id_sem_agenda' style='<?if ($agendar=="SIM") echo "display:none";?>'>
							
						<?if (in_array($login_fabrica, array(11,172))){?>		
							<INPUT size='8' maxlength='10' TYPE='text' NAME='data_final_2' id='data_final_2' value='<? echo $data_final; ?>' >
						<?}else{?>
							<select name="ano" id="ano" size="1" >
								<option value=''></option>
								<?
								for ($i = 2003 ; $i <= date("Y") ; $i++) {
									echo "<option value='$i'";
									if ($ano == $i) echo " selected";
									echo ">$i</option>";
								}
								?>
							</select>
						<?}?>


						</div>
						<div rel='id_com_agenda' style='<?if ($agendar!=="SIM") echo "display:none";?>'>
							<INPUT size='8' maxlength='10' TYPE='text' NAME='data_final' id='data_final' value='<? echo $dt_final; ?>' >
						</div>
						
				</TR>
				<tr>
					<td align='right'>Somente</td> 
					<td align='left'>
					<input type='radio' name='status' value='ABERTAS' <?if($status=='ABERTAS')echo "checked";?>> OS's Abertas
					&nbsp;&nbsp;&nbsp;
					<input type='radio' name='status' value='FECHADAS' <?if($status=='FECHADAS')echo "checked";?>> OS's Fechadas
					&nbsp;&nbsp;&nbsp;
					<? if (in_array($login_fabrica, array(11,172))){ // HD 88963?>
					<BR>
					<input type='radio' name='status' value='CONSERTADAS' <?if($status=='CONSERTADAS')echo "checked";?>> OS's Consertadas
					&nbsp;&nbsp;&nbsp;
					<?}?>
					<input type='radio' name='status' value='' <?if($status=='')echo "checked";?>> Todas
					
					</td>
				</tr>
				<tr>
					<td colspan='2'><hr></td> 
				</tr>
				<tr>
					<td align='right'>Tipo Arquivo para Download</td> 
					<td align='left'>
					
					<input type='radio' name='formato_arquivo' value='XLS' <?if($formato_arquivo=='XLS')echo "checked";?>> XLS
					&nbsp;&nbsp;&nbsp;
					<input type='radio' name='formato_arquivo' value='CSV' <?if($formato_arquivo!='XLS')echo "checked";?>> CSV
					</td>
				</tr>
				<tr>
					<td align='right'>Agendamento</td> 
					<td align='left'>
					
					<? if (in_array($login_fabrica, array(11,172))){?>
						<input type='radio' name='agendar' value='SIM'> SIM
					<?}else{?>
						<input type='radio' name='agendar' value='SIM'<?if($agendar=='SIM')echo "checked";?> onClick='javascript:toogleProd(this)' > SIM
					<?}?>

					&nbsp;&nbsp;&nbsp;
					
					<? if (in_array($login_fabrica, array(11,172))){?>
						<input type='radio' name='agendar' value='NAO' checked>Não
						</td>
					<?}else{?>
						<input type='radio' name='agendar' value='NAO'<?if($agendar!='SIM')echo "checked";?> onClick='javascript:toogleProd(this)' > Não
						</td>
					<?}?>


				</tr>
				<tr bgcolor="#D9E2EF">
					<td colspan="2" align="center" ><img border="0" src="imagens/btn_pesquisar_400.gif" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
				</tr>

			</table>
		</td>
	</tr>
</table>

<br>
<?

if (strlen($acao) > 0 && strlen($msg_erro) == 0 && ($agendar!='SIM' OR $gera_automatico == 'automatico')) {

	echo "<span id='msg_carregando'><img src='imagens/carregar2.gif'><font face='Verdana' size='1' color=#FF0000>
	<BR>Aguarde carregamento.
	<BR>Devido a grande quantidade de Ordem de Serviço o resultado pode levar alguns minuto para ser exibido.
	<BR>Aguarde até o término do carregamento.</font></span>";
	flush();

	$cond_revenda    = " 1=1 ";
	$cond_cidade     = " 1=1 ";
	$cond_status     = " 1=1 ";
	$cond_estado     = " 1=1 ";

	if (strlen($revenda_cnpj)>0){
		$sql_temp = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$revenda_cnpj';";
		$res = pg_exec($con,$sql_temp);
		$revenda = trim(pg_result($res,0,0));
		$cond_revenda = "tbl_os.revenda = $revenda";
	}elseif (strlen($revenda_nome)>0){
		$sql_temp = "SELECT revenda FROM tbl_revenda WHERE nome LIKE '%$revenda_nome%'";
		$cond_revenda = "tbl_os.revenda IN ( $sql_temp )";
	}

	if(strlen($cidade)> 0){
		$cond_cidade=" tbl_os.consumidor_cidade = '$cidade' ";
	}
	

	if(strlen($status)> 0){
		if ($status=='ABERTAS'){
			$cond_status = " tbl_os.finalizada IS NULL ";
		}elseif($status=='FECHADAS'){
			$cond_status = " tbl_os.finalizada IS NOT NULL ";
		}else{
			$cond_status = " tbl_os.data_conserto IS NOT NULL";
		}
	}

	if (strlen(trim($estado)) > 0){
		$cond_estado =" tbl_os.consumidor_estado ='$estado' ";
	}		
	$sql = "SELECT  
				tbl_os.os                                                     ,
				tbl_os.sua_os                                                 ,
				to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as data_digitacao ,
				to_char(tbl_os.data_conserto,'DD/MM/YYYY') as data_conserto   ,
				tbl_os.finalizada                                             ,
				tbl_posto_fabrica.codigo_posto                                ,
				tbl_posto.nome as posto_nome                                  ,
				tbl_os.revenda_nome                                           ,
				tbl_os.revenda_cnpj                                           ,
				tbl_os.consumidor_cidade                                      ,
				tbl_os.consumidor_estado
	FROM tbl_os
	JOIN tbl_posto         on tbl_posto.posto=tbl_os.posto
	JOIn tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica= $login_fabrica
	WHERE tbl_os.fabrica = $login_fabrica";
	$sql .=" AND $cond_revenda
		AND $cond_cidade
		AND $cond_status
		AND $cond_estado
		AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
		AND tbl_os.posto <> 6359
		AND tbl_os.excluida IS NOT TRUE
		";


	# echo nl2br($sql);
	#exit;
	$res = pg_exec($con,$sql);
	$numero_registros = pg_numrows($res);
	$conteudo = "";


	$data = date("Y-m-d").".".date("H-i-s");

	$arquivo_nome     = "relatorio_os_aberta_fechada-$login_fabrica.$login_admin.".$formato_arquivo;
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	$fp = fopen ($arquivo_completo_tmp,"w");

	if ($formato_arquivo!='CSV'){
		fputs ($fp,"<html>");
		fputs ($fp,"<body>");
	}


	echo "<p id='id_download' style='display:none'><a href='xls/$arquivo_nome'>Fazer download do arquivo em  ".strtoupper($formato_arquivo)." </a></p>";

	if ($numero_registros > 0) {
		$conteudo = "<table width='95%' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";

		$conteudo .= "<tr class='Titulo'>";
		$conteudo .= "<td colspan='12'>RELAÇÃO DE OS</td>";
		$conteudo .= "</tr>";
		$conteudo .= "<tr class='Titulo'>";

		$conteudo .= "<td align='center'>OS</td>";
		$conteudo .= "<td align='center'>DATA DE DIGITAÇÃO</td>";
		if( in_array($login_fabrica, array(11,172)) ) { // HD 88963
			$conteudo .= "<td align='center'>DATA DO CONSERTO</td>";
		}
		$conteudo .= "<td>STATUS</td>";
		$conteudo .= "<td>CÓDIGO POSTO</td>";
		$conteudo .= "<td>NOME POSTO</td>";
		$conteudo .= "<td>CIDADE</td>";
		$conteudo .= "<td>ESTADO</td>";
		$conteudo .= "<td>CNPJ DA REVENDA</td>";
		$conteudo .= "<td>NOME DA REVENDA</td>";
		$conteudo .= "</tr>";

		echo $conteudo;

		if ($formato_arquivo=='CSV'){
			$conteudo = "";
			$conteudo .= "OS;DATA DE DIGITAÇÃO;STATUS;CÓDIGO POSTO;NOME POSTO;CIDADE; ESTADO ;CNPJ DA REVENDA;NOME DA REVENDA \n";
		}
		fputs ($fp,$conteudo);

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os                = trim(pg_result($res,$i,os));
			$sua_os            = trim(pg_result($res,$i,sua_os));
			$data_digitacao    = trim(pg_result($res,$i,data_digitacao));
			$data_conserto    = trim(pg_result($res,$i,data_conserto));
			$finalizada        = trim(pg_result($res,$i,finalizada));
			$codigo_posto      = trim(pg_result($res,$i,codigo_posto));
			$posto_nome        = trim(pg_result($res,$i,posto_nome));
			$revenda_nome      = trim(pg_result($res,$i,revenda_nome));
			$revenda_cnpj      = trim(pg_result($res,$i,revenda_cnpj));
			$consumidor_cidade = trim(pg_result($res,$i,consumidor_cidade));
			$consumidor_estado = trim(pg_result($res,$i,consumidor_estado));
			
			if(strlen($finalizada) ==0){
				$status="ABERTA";
			}else{
				$status="FECHADA";
			}
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			$conteudo  = "<tr class='Conteudo' bgcolor='$cor'>";
			$conteudo .= "<td><a href='os_press.php?os=$os' target='_blank'>".$sua_os."</a></td>";
			$conteudo .= "<td>".$data_digitacao."</td>";
			if(in_array($login_fabrica, array(11,172))) { // HD 88963
				$conteudo .= "<td>".$data_conserto."</td>";
			}
			$conteudo .= "<td>".$status      ."</td>";
			$conteudo .= "<td>".$codigo_posto."</td>";
			$conteudo .= "<td>".$posto_nome  ."</td>";
			$conteudo .= "<td>".$consumidor_cidade."</td>";
			$conteudo .= "<td>".$consumidor_estado."</td>";
			$conteudo .= "<td>".$revenda_cnpj."</td>";
			$conteudo .= "<td>".$revenda_nome."</td>";
			$conteudo .= "</tr>";

			echo $conteudo;
			if ($formato_arquivo=='CSV'){
				$conteudo = "";
				if(in_array($login_fabrica, array(11,172))) {
					$conteudo.= $sua_os.";".$data_digitacao.";".$data_conserto.";".$status.";".$codigo_posto.";".$posto_nome.";".$consumidor_cidade.";".$consumidor_estado.";".$revenda_cnpj.";" .$revenda_nome.";\n";
				}else{
					$conteudo.= $sua_os.";".$data_digitacao.";".$status.";".$codigo_posto.";".$posto_nome.";".$consumidor_cidade.";".$consumidor_estado.";".$revenda_cnpj.";" .$revenda_nome.";\n";
				}
			}
			fputs ($fp,$conteudo);
		}
		$conteudo  = "</table>";
		$conteudo .= "<BR><CENTER>".pg_numrows($res)." Registros encontrados</CENTER>";

		echo $conteudo;
		if ($formato_arquivo=='CSV'){
			$conteudo = "";
		}

		fputs ($fp,$conteudo);

		if ($formato_arquivo!='CSV'){
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
		}
		fclose ($fp);
		flush();

		echo ` cp $arquivo_completo_tmp $path `;

		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block';";
		echo "</script>";
		echo "<br>";

	} else {
		echo "<table border='0' cellpadding='2' cellspacing='0'>";
		echo "<tr height='50'>";
		echo "<td valign='middle' align='center'><img src='imagens/atencao.gif' border='0'>
			<font size=\"2\"><b>Não foram encontrados registros com os parâmetros informados/digitados.</b></font>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	echo "<script language='javascript'>";
	echo "document.getElementById('msg_carregando').style.visibility='hidden';";
	echo "</script>";

	if ($gera_automatico == 'automatico'){

		$email_para = 'helpdesk@telecontrol.com.br';
		if (strlen($login_admin)>0){
			$sql = "SELECT email FROM tbl_admin WHERE admin = $login_admin AND fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res)>0){
				$email_para = trim(pg_result($res,0,email));
			}
		}

		$from    = 'Suporte Telecontrol <helpdesk@telecontrol.com.br>';
		$to      = $email_para;

		if (strlen($to)==0){
			$to  = "helpdesk@telecontrol.com.br";
		}
		#$to  = "helpdesk@telecontrol.com.br";
		
		$cc      = "helpdesk@telecontrol.com.br";
		$bcc     = "helpdesk@telecontrol.com.br";
		$subject = "Relatorio: ".$title." - (Data: $data)";

		if ($numero_registros>0){
			//$body    = " Em anexo relatório $title.";
			$body = "O relatório '$title' foi processado. Para fazer o download, <a href='http://posvenda.telecontrol.com.br/assist/admin/xls/$arquivo_nome'>clique aqui</a>. ";
			$body .= "<br>Caso não está consiga fazer o download, copie e cole no seu navegador o link: http://posvenda.telecontrol.com.br/assist/admin/xls/$arquivo_nome <br>";
			$body .= "<br>Qualquer dúvida entre em contato com o Suporte Telecontrol helpdesk@telecontrol.com.br";
		}else{
			$body    = " Relatório $title foi processado mas não foi encontrado nenhum registro com os parâmetros informados.";
		}

		$mailheaders = "From: $from\n"; 
		$mailheaders .= "Reply-To: $from\n"; 
		$mailheaders .= "Cc: $cc\n"; 
		#$mailheaders .= "Bcc: $bcc\n"; 
		$mailheaders .= "X-Mailer: ".$title." - AUTOMATICO \n"; 
		$mailheaders .= "Content-type: text/html; charset=iso-8859-1\r\n";

		$msg_body = stripslashes($body); 
		$msg_body = $body_top . $msg_body; 

		if (mail($to, stripslashes(utf8_encode($subject)), utf8_encode($msg_body), $mailheaders)){
			$sql = "SELECT relatorio_agendamento 
					FROM tbl_relatorio_agendamento
					WHERE admin   = $login_admin
					AND programa  = '$PHP_SELF'
					AND executado IS NULL";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				$relatorio_agendamento = trim(pg_result($res,0,relatorio_agendamento));
				$sql = "UPDATE tbl_relatorio_agendamento SET executado = CURRENT_TIMESTAMP
						WHERE relatorio_agendamento = $relatorio_agendamento";
				$res = pg_exec($con,$sql);
			}
		}else{
			mail('helpdesk@telecontrol.com.br', stripslashes('RELATORIO AGENDADO NAO ENVIOU EMAIL'), 'NAO ENVIOU O EMAIL PARA'.$to.' - Fabrica: '.$login_fabrica.' - Admin: '.$login_admin, '');
		}
		exit;
	}
}

include "rodape.php";
?>
