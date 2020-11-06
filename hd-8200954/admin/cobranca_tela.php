<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "Cobrança";
	//include 'cabecalho.php';


include "monitora_cabecalho.php";
function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

function TempoExec($pagina, $sql, $time_start, $time_end){
	if (1 == 1){
		$time = $time_end - $time_start;
		$time = str_replace ('.',',',$time);
		$sql  = str_replace ('\t',' ',$sql);

	}
}

$micro_time_start = getmicrotime();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

<head>

	<title><? echo $title ?></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css.css">
	<link type="text/css" rel="stylesheet" href="css/tooltips.css">

<style> 
.body{
	font-family:Arial, Helvetica, sans-serif;
	font-size:9px;
}
 
 
.formulario {
	font-family:Arial, Helvetica, sans-serif;
	font-size:9px;
	text-align:right;
 
	
}
 
table.bordasimples tr td {
	font-family:Arial, Helvetica, sans-serif;
	font-size:9px;
 
	
}
 
.pgoff {font-family: Verdana, Arial, Helvetica; font-size: 11px; color: #FF0000; text-decoration: none}
a.pg {font-family: Verdana, Arial, Helvetica; font-size: 11px; color: #003366; text-decoration: none}
a:hover.pg {font-family: Verdana, Arial, Helvetica; font-size: 11px; color: #0066cc; text-decoration:underline}
 
 ??????????????????????
</style>

</head>

<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' <?=$body_onload;?> >

<?


//include 'cabecalho.php';

$acao = $_GET["acao"];

$posto = $_GET["posto"];

if ($acao=="excluir"){

	$sql = "update tbl_cobranca_retorno set admin=$login_admin,retorno_status='t' where posto=$posto";
	$res = pg_exec($con,$sql);					
$painel_info="<br>&nbsp;&nbsp;&nbsp;<b>Posto contatado.</b><br><br>";
}

if ($acao=="atualizar"){

$debito = $_POST["debito"];
$data_retorno = $_POST["data_retorno"];
$historico = $_POST["historico"];

$sql = "SELECT descricao_debito from tbl_cobranca_debito_detalhado where id_debito =$debito";
$res = pg_exec($con,$sql);	
			if(pg_numrows($res)> 0){
				$descricao_debito=pg_result($res,0,descricao_debito);
			}
$historico = $descricao_debito." // ".$historico;

	if($data_retorno==""){
		$sql = "update tbl_cobranca_retorno set admin=$login_admin, id_debito=$debito where posto=$posto";
		$res = pg_exec($con,$sql);	

		$timestamp = mktime(date("H")+3, date("i"), date("s"), date("m"), date("d"), date("Y"), 0);
		$data_atual = gmdate("Y/m/d H:i:s", $timestamp);
		$sql = "insert into tbl_cobranca_historico (admin,historico,posto,data_digitacao) values('$login_admin','$historico','$posto','$data_atual')";

		$res = pg_exec($con,$sql);	


	}else{

		$sql = "update tbl_cobranca_retorno set admin=$login_admin, id_debito=$debito, data_retorno='$data_retorno' where posto=$posto";
		$res = pg_exec($con,$sql);	

		$timestamp = mktime(date("H")+3, date("i"), date("s"), date("m"), date("d"), date("Y"), 0);
		$data_atual = gmdate("Y/m/d H:i:s", $timestamp);
		$sql = "insert into tbl_cobranca_historico (admin,historico,posto,data_digitacao) values('$login_admin','$historico','$posto','$data_atual')";

		$res = pg_exec($con,$sql);	
	}
$painel_info="<br>&nbsp;&nbsp;&nbsp;<b>Alterações no sistema de cobrança foram efetuados com sucesso.</b><br><br>";
}

?>
 
<?=$painel_info?>
  <TABLE border=0 class="bordasimples">
  <TR>
	<TD>
<?

$sql = "SELECT 
		tbl_posto.posto as posto ,
		tbl_posto.cnpj as cnpj, 
		tbl_posto.nome as nome, 
		tbl_posto.endereco as endereco, 
		tbl_posto.cidade as cidade, 
		tbl_posto.estado as estado, 
		tbl_posto.cep as cep, 
		tbl_posto.fone as fone, 
		tbl_posto.fax as fax, 
		tbl_posto_fabrica.contato_email as email, 

		tbl_posto_fabrica.codigo_posto as codigo_posto 

		from tbl_posto 

		join tbl_posto_fabrica on tbl_posto.posto=tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=3
		
		where tbl_posto.posto = $posto";

			$res = pg_exec($con,$sql);					

			if(pg_numrows($res)> 0){
				$cnpj=pg_result($res,0,cnpj);
				$codigo_matriz=pg_result($res,0,codigo_posto);
				$posto=pg_result($res,0,posto);
				$razao_social=pg_result($res,0,nome);
				$endereco=pg_result($res,0,endereco);
				$cidade=pg_result($res,0,cidade);
				$estado=pg_result($res,0,estado);
				$cep=pg_result($res,0,cep);
				$telefone=pg_result($res,0,fone);
				$fax=pg_result($res,0,fax);
				$email=pg_result($res,0,email);
			}
?>
		<TABLE border="0" cellpadding="5" cellspacing="2" >
			<TR bgcolor="#D9E2EF">
				<TD><b>Cod. Matriz</b></TD>
				<TD><?=$codigo_matriz?></TD>
				<TD><b>Razão Social</b></TD>
				<TD><?=$razao_social?></TD>
				<TD><b>Telefone</b></TD>
				<TD><?=$telefone?></TD>
				<TD><b>CNPJ</b></TD>
				<TD><?=$cnpj?></TD>
			</TR>

			<TR bgcolor="#D9E2EF">
				<TD><b>Endereço</b></TD>
				<TD colspan="3"><?=$endereco?></TD>

				<TD><b>Cidade</b></TD>
				<TD><?=$cidade?></TD>
				<TD><b>Estado</b></TD>
				<TD><?=$estado?></TD>
			</TR>
			<TR bgcolor="#D9E2EF">
				<TD><b>CEP</b></TD>
				<TD><?=$cep?></TD>
				<TD><b>Fax</b></TD>
				<TD><?=$fax?></TD>
			</TR>		
		</TABLE>
	</TD>
	<TD width="250">		
		<TABLE>
			<TR>
				<TD colspan="4"><a href="#" onclick="document.frm_email.submit();">Enviar e-mail para o posto</a></TD>
			</TR>
			<TR>
				<TD colspan="4"><a href="cobranca_tela.php?acao=excluir&posto=<?=$posto?>">Marcar como posto contatado</a></TD>
			</TR>
		</TABLE>
	</TD>
	</TR>
	<form name="autoSumForm">
	<TR>
	<TD colspan="2">
		<table border=0 style="font-size:10px; font-family:Verdana, Arial, Helvetica, sans-serif">
			<tr bgcolor="#D9E2EF">
				<td align='center'><b>FILIAL</b></td>
				<td align='center'><b>ESPC</b></td>
				<td align='center'><b>SERIE</b></td>
				<td align='center'><b>Nº DA NOTA</b></td>
				<td align='center'><b>BC</b></td>
				<td align='center'><b>NOSSO Nº</b></td>
				<td align='center'><b>PARC.</b></td>
				<td align='center'><b>CART</b></td>
				<td align='center'><b>EMISSÃO</b></td>
				<td align='center'><b>VENC.</b></td>
				<td align='center'><b>VLR ORIG.</b></td>
				<td align='center'><b>VLR SALDO</b></td>
				<td align='center'><b>VLR DESP.</b></td>
				<td align='center'><b>DIAS ATRASO</b></td>
				<td align='center'><b>MULTA R$</b></td>
				<td align='center'><b>JUROS %</b></td>
				<td align='center'><b>VALOR CORR.</b></td>
				<td align='center'><b>INSERIR NO CÁLCULO</b></td>
			</tr>			

<?	//gera corpo do e-mail cabeçalho da tabela
	$corpo_email="&nbsp;<br><br><table border=0 style=font-size:10px;font-family:Verdana,Arial,Helvetica,sans-serif><tr bgcolor=#D9E2EF><td align=center><b>FILIAL</b></td><td align=center><b>ESPC</b></td><td align=center><b>SERIE</b></td><td align=center><b>Nº DA NOTA</b></td><td align=center><b>BC</b></td><td align=center><b>NOSSO Nº</b></td><td align=center><b>PARC.</b></td><td align=center><b>CART</b></td><td align=center><b>EMISSÃO</b></td><td align=center><b>VENC.</b></td><td align=center><b>VLR ORIG.</b></td><td align=center><b>VLR SALDO</b></td><td align=center><b>VLR DESP.</b></td><td align=center><b>DIAS ATRASO</b></td></tr>";
	//fim gera corpo do e-mail cabeçalho da tabela

	// conta nota função UpdateCost
	$nvermelho=0;
	$namarelo=0;
	$nverde=0;
	$total_verde="0.00";
	$total_vermelho="0.00";
	$total_amarelo="0.00";

	// busca informações da nota		
	$sql3 = "select filial,especie,serie,nota,id_cobranca_nota,banco,nosso_numero,parcela,carteira,emissao,vencimento,valor_original,valor_saldo,valor_despesas, visivel, id_cobranca_nota from tbl_cobranca_nota where visivel='t' and posto = $posto order by vencimento";
			
			$res3 = pg_exec($con,$sql3);
			while ($row3 = pg_fetch_array($res3)) {
				$filial = $row3["filial"];
				$especie = $row3["especie"];
				$serie = $row3["serie"];
				$nota = $row3["nota"];
				$banco = $row3["banco"];
				$nosso_numero = $row3["nosso_numero"];
				$parcela = $row3["parcela"];
				$carteira = $row3["carteira"];
				$emissao = $row3["emissao"];
				$vencimento = $row3["vencimento"];
				$valor_original = $row3["valor_original"];
				$valor_saldo = $row3["valor_saldo"];
				$despesas = $row3["despesas"];
				$status = $row3["visivel"];
				$id_cobranca_nota = $row3["id_cobranca_nota"];
				$dias = $row3["dias"];
				
if ($despesas==""){
$despesas="0.00";}
if ($especie=='AN' or $especie=='NC' or $especie=='DT' or $especie=='AT'){
$tr=" bgcolor='#D2FFD2'";
$nverde = $nverde + 1;
$idnota="verde".$nverde;
$onclick = "onclick=\"UpdateCostGreen()\"";
$total_verde=$total_verde+$valor_saldo;
}else if($especie=='JR'){
$tr=" bgcolor='#FFFFD5'";
$namarelo = $namarelo + 1;
$onclick = "onclick=\"UpdateCostYellow()\"";
$idnota="amarelo".$namarelo;
$total_amarelo=$total_amarelo+$valor_saldo;
}else{
$tr=" bgcolor='#FFC6C6'";
$nvermelho = $nvermelho + 1;
$onclick = "onclick=\"UpdateCostRed()\"";
$idnota="vermelho".$nvermelho;
$total_vermelho=$total_vermelho+$valor_saldo;
}

$timestamp = mktime(date("H")+3, date("i"), date("s"), date("m"), date("d"), date("Y"), 0);
$data_hora = gmdate("Y/m/d", $timestamp);
//defino data 1 
$ano1 = substr($data_hora,0,4); 
$mes1 = substr($data_hora,5,2); 
$dia1 = substr($data_hora,8,2); 

//defino data 2 
$ano2 = substr($vencimento,0,4);
$mes2 = substr($vencimento,5,2);
$dia2 = substr($vencimento,8,2); 

//calculo timestam das duas datas 
$timestamp1 = mktime(0,0,0,$mes1,$dia1,$ano1); 
$timestamp2 = mktime(0,0,0,$mes2,$dia2,$ano2); 

//diminuo a uma data a outra 
$segundos_diferenca = $timestamp1 - $timestamp2; 
//echo $segundos_diferenca; 

//converto segundos em dias 
$dias_diferenca = $segundos_diferenca / (60 * 60 * 24); 

//obtenho o valor absoluto dos dias (tiro o possível sinal negativo) 
//$dias_diferenca = abs($dias_diferenca); 

//tiro os decimais aos dias de diferenca 
$dias_diferenca = floor($dias_diferenca); 



$emissao = substr($emissao,8,2) . "/" .substr($emissao,5,2) . "/" . substr($emissao,0,4);
$vencimento = substr($vencimento,8,2) . "/" .substr($vencimento,5,2) . "/" . substr($vencimento,0,4);

?>

<script type="text/javascript">
function startCalc<?=$id_cobranca_nota?>(){
  interval = setInterval("calc<?=$id_cobranca_nota?>()",1);
}
function calc<?=$id_cobranca_nota?>(){
  saldo = document.autoSumForm.saldo<?=$id_cobranca_nota?>.value;
  despesas = document.autoSumForm.despesas<?=$id_cobranca_nota?>.value; 
  dias = document.autoSumForm.dias<?=$id_cobranca_nota?>.value;
  juros = document.autoSumForm.juros<?=$id_cobranca_nota?>.value.replace(',','.'); 
  multa = document.autoSumForm.multa<?=$id_cobranca_nota?>.value.replace(',','.');
  total = ((((juros/30)/100)*dias)*saldo)+(saldo*1)+(despesas*1)+(multa*1); 
  total = ((Math.round(total*100))/100);   
  document.autoSumForm.somatoria<?=$id_cobranca_nota?>.value = total.toFixed(2);
  document.autoSumForm.soma<?=$id_cobranca_nota?>.value = total.toFixed(2);
}
function stopCalc<?=$id_cobranca_nota?>(){
  clearInterval(interval);
}
</script>

			<tr<?=$tr?>>
				<td align="center"><?=$filial?></td>
				<td align="center"><?=$especie?></td>
				<td align="center"><?=$serie?></td>
				<td align="center"><?=$nota?></td>
				<td align="center"><?=$banco?></td>
				<td align="center"><?=$nosso_numero?></td>
				<td align="center"><?=$parcela?></td>
				<td align="center"><?=$carteira?></td>
				<td align="center"><?=$emissao?></td>
				<td align="center"><?=$vencimento?></td>
				<td align="center"><?=number_format($valor_original,2,".","")?></td>
<td align="center"><input type="text" name="saldo<?=$id_cobranca_nota?>" value="<?=number_format($valor_saldo,2,'.','')?>" disabled="disabled" size="7" class="formulario"></td>
<td align="center"><input type="text" name="despesas<?=$id_cobranca_nota?>" value="<?=number_format($despesas,2,'.','')?>" disabled="disabled" size="7" class="formulario"></td>
<td align="center"><? if($dias_diferenca>=0){echo$dias_diferenca;} ?><input type="hidden" name="dias<?=$id_cobranca_nota?>" value="<? if($dias_diferenca>=0){echo$dias_diferenca;} ?>"></td>
<td align="center"><input type="text" name="multa<?=$id_cobranca_nota?>" size="7" value="" onFocus="startCalc<?=$id_cobranca_nota?>();" onBlur="stopCalc<?=$id_cobranca_nota?>();" class="formulario"></td>
<td align="center"><input type="text" name="juros<?=$id_cobranca_nota?>" size="7" value="" onFocus="startCalc<?=$id_cobranca_nota?>();" onBlur="stopCalc<?=$id_cobranca_nota?>();" class="formulario"></td>
<td align="center"><input type="text" name="somatoria<?=$id_cobranca_nota?>" size="7" value="<?=number_format($valor_saldo,2,'.','')?>"  class="formulario"></td>
<td align='center'><input type="checkbox" name="soma<?=$id_cobranca_nota?>" id ="<?=$idnota?>" value="<?=$valor_saldo?>" <?=$onclick?>></td>
			</tr>
<?
			//gera corpo do e-mail corpo da tabela 
			$despesas_dec=number_format($despesas,2,'.','');
			$valor_saldo_dec=number_format($valor_saldo,2,'.','');
			$valor_original_dec=number_format($valor_original,2,".","");

			$corpo_email=$corpo_email."<tr><td align=center>$filial</td><td align=center>$especie</td><td align=center>$serie</td><td align=center>$nota</td><td align=center>$banco</td><td align=center>$nosso_numero</td><td align=center>$parcela</td><td align=center>$carteira</td><td align=center>$emissao</td><td align=center>$vencimento</td><td align=center>$valor_original_dec</td><td align=center>$valor_saldo_dec</td><td align=center>$despesas_dec</td><td align=center>$dias_diferenca</td></tr>";
			//fim gera corpo do e-mail corpo da tabela
			}
?></form>
		</table>		
	</TD>
  </TR> 
  <TR>
	<TD valign="top" align="left" width="730">
	<script type="text/javascript">
	function UpdateCostRed() {
	  var sum = 0;
	  var gn, elem;
	  for (i=1; i<=<?=$nvermelho?>; i++) {
		gn = 'vermelho'+i;
		elem = document.getElementById(gn);
		if (elem.checked == true) { sum += Number(elem.value); }
	  }
	  document.getElementById('totalcostred').value = sum.toFixed(2);
	}

	function UpdateCostGreen() {
	  var sum = 0;
	  var gn, elem;
	  for (i=1; i<=<?=$nverde?>; i++) {
		gn = 'verde'+i;
		elem = document.getElementById(gn);
		if (elem.checked == true) { sum += Number(elem.value); }
	  }
	  document.getElementById('totalcostgreen').value = sum.toFixed(2);
	}

	function UpdateCostYellow() {
	  var sum = 0;
	  var gn, elem;
	  for (i=1; i<=<?=$namarelo?>; i++) {
		gn = 'amarelo'+i;
		elem = document.getElementById(gn);
		if (elem.checked == true) { sum += Number(elem.value); }
	  }
	  document.getElementById('totalcostyellow').value = sum.toFixed(2);
	}

	</script>

	<FORM METHOD=POST ACTION="cobranca_tela.php?acao=atualizar&posto=<?=$posto?>">
	Débito detalhado&nbsp;
		<SELECT NAME="debito">
<?
$sql = "SELECT id_debito, data_retorno, retorno_status from tbl_cobranca_retorno where posto=$posto";

			$res = pg_exec($con,$sql);	
			if(pg_numrows($res)> 0){
				$debito=pg_result($res,0,id_debito);
				$data_retorno=pg_result($res,0,data_retorno);
				$retorno_status=pg_result($res,0,retorno_status);

				if ($data_retorno<>""){
				$data_retorno = substr($data_retorno,8,2) . "/" .substr($data_retorno,5,2) . "/" . substr($data_retorno,0,4);
				}
			}

$sql = "SELECT id_debito, descricao_debito, status_debito from tbl_cobranca_debito_detalhado where status_debito ='t'";

			$res = pg_exec($con,$sql);					

			if(pg_numrows($res)> 0){
					while ($row4 = pg_fetch_array($res)) {
						$id_debito = $row4["id_debito"];
						$descricao_debito = $row4["descricao_debito"];
						$status_debito = $row4["status_debito"]; 
							echo "<OPTION VALUE='$id_debito'";
								if ($id_debito==$debito){
								echo " selected";
								}
							echo ">$descricao_debito</option>";
					}
			}

if ($acao=="calcular"){

$credito_total = $_POST["credito_total"];
$debito_total = $_POST["debito_total"];
$juros_total = $_POST["juros_total"];
$valor_total = (($juros_total+$debito_total)-$credito_total);
$parcelas = $_POST["parcelas"];
$juros = $_POST["juros"];

   if($juros==0){
      $string = 'PARCELA - VALOR <br />';
      for($i=1;$i<($parcelas+1);$i++){
         $string .= $i.'x (Sem Juros) - R$ '.number_format($valor_total/$parcelas, 2, ",", ".").' <br />';

      }
		//$total=$string;
     // return $string;
   }else{
      $string = 'PARCELA - VALOR <br />';
      for($i=1;$i<($parcelas+1);$i++){
         $I =$juros/100.00;
         $valor_parcela = $valor_total*$I*pow((1+$I),$parcelas)/(pow((1+$I),$parcelas)-1);
         $string .= $i.'x (Juros de: '.$juros.'%) - R$ '.number_format($valor_parcela, 2, ",", ".").' <br />';

      }
	//	$total=$string;
      //return $string;
   }

}

?>
		</SELECT>
			<script language="JavaScript" type="text/javascript">
			function mascaraData(campoData){
				var data = campoData.value;              
					if (data.length == 2){
					data = data + '/';
					document.getElementById('data_retorno').value = data;    
					return true;                            
					}  
								
					if (data.length == 5){
					data = data + '/';
					document.getElementById('data_retorno').value = data;
					return true;
																										  
					}         
				}

				function Update_soma_vermelho() {
				  var sum = 0;
				  var gn, elem;
				  for (i=0; i<1; i++) {
					gn = 'soma_vermelho';
					elem = document.getElementById(gn);
					if (elem.checked == true) { sum += Number(elem.value); }
				  }
				  document.getElementById('totalcostred').value = sum.toFixed(2);
				}

				function Update_soma_verde() {
				  var sum = 0;
				    var gn, elem;
				  for (i=0; i<1; i++) {
				 	gn = 'soma_verde';
					elem = document.getElementById(gn);
					if (elem.checked == true) { sum += Number(elem.value); }
				  }
				  document.getElementById('totalcostgreen').value = sum.toFixed(2);
				}

				function Update_soma_amarelo() {
				  var sum = 0;
				  var gn, elem;
				  for (i=0; i<1; i++) {
					gn = 'soma_amarelo';
					elem = document.getElementById(gn);
					if (elem.checked == true) { sum += Number(elem.value); }
				  }
				  document.getElementById('totalcostyellow').value = sum.toFixed(2);
				}


			</script>
		&nbsp;&nbsp;&nbsp;Data de retorno&nbsp;<INPUT TYPE="text" NAME="data_retorno" id="data_retorno" OnKeyUp="mascaraData(this);" value="<?=$data_retorno?>" size="10"  maxlength="10">&nbsp;&nbsp;&nbsp;
		<INPUT TYPE="submit" value="Gravar dados"><br>
		<TEXTAREA NAME="historico" ROWS="3" COLS="78"></TEXTAREA>		
		<TEXTAREA NAME="historico_antigo" ROWS="10" COLS="78"  disabled="disabled">
<?
					// busca histórico da nota
					$sql4 = "select historico,data_digitacao,admin from tbl_cobranca_historico where posto=$posto order by id_historico desc";
					
					$res4 = pg_exec($con,$sql4);
					if(pg_numrows($res4)> 0){
						while ($row4 = pg_fetch_array($res4)) {
							$historico = $row4["historico"];
							$data = $row4["data_digitacao"];
							$usuario = $row4["admin"]; 

							$sql = "SELECT login FROM tbl_admin WHERE admin = $usuario";
							
							$res = pg_exec($con,$sql);					

							if(pg_numrows($res)> 0){
								$nome_usuario=pg_result($res,0,login);
							}

							$data = substr($data,8,2) . "/" .substr($data,5,2) . "/" . substr($data,0,4) ;

							echo "$historico&nbsp;&nbsp;//&nbsp;&nbsp;$nome_usuario&nbsp;&nbsp;$data
";
						}
					}
?>
		</TEXTAREA><br></form>
	</TD>
	<TD align="left">
	<TABLE  cellpadding="5" cellspacing="1">
	<TR bgcolor="#7A96B8">
		<TD colspan="3"><b>Resumo do posto</b></TD>
	</TR>
	<TR bgcolor="#7A96B8">
		<TD align="left"><b>Total de crédito</b>&nbsp;&nbsp;&nbsp;</TD>
		<TD align="right">&nbsp;&nbsp;<b><?=number_format($total_verde,2,'.','')?></b>&nbsp;&nbsp;</TD>
		<TD><input type="checkbox" name="soma_verde" id ="soma_verde" value="<?=$total_verde?>" onclick="Update_soma_verde()"></TD>
	</TR>
	<TR bgcolor="#7A96B8">
		<TD align="left"><b>Total de juros</b>&nbsp;&nbsp;&nbsp;</TD>
		<TD align="right">&nbsp;&nbsp;<b><?=number_format($total_amarelo,2,'.','')?></b>&nbsp;&nbsp;</TD>
		<TD><input type="checkbox" name="soma_amarelo" id ="soma_amarelo" value="<?=$total_amarelo?>" onclick="Update_soma_amarelo()"></TD>
	</TR>
	<TR bgcolor="#7A96B8">
		<TD align="left"><b>Total de débito</b>&nbsp;&nbsp;&nbsp;</TD>
		<TD align="right">&nbsp;&nbsp;<b><?=number_format($total_vermelho,2,'.','')?></b>&nbsp;&nbsp;</TD>
		<TD><input type="checkbox" name="soma_vermelho" id ="soma_vermelho" value="<?=$total_vermelho?>" onclick="Update_soma_vermelho()"></TD>
	</TR>
	</TABLE>
	<br><br>
	<FORM METHOD=POST ACTION="cobranca_tela.php?acao=calcular&posto=<?=$posto?>">
		<table border=0>
			<tr bgcolor="#D9E2EF">
				<td colspan="2"><b>Tabela de Cálculos</b></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td><b>Débitos R$</b></td>
				<td><input type="text" id="totalcostred" name="debito_total" value="<?=$debito_total?>" class="formulario"></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td><b>Juros R$</b></td>
				<td><input type="text" id="totalcostyellow" name="juros_total" value="<?=$juros_total?>" class="formulario"></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td><b>Créditos R$</b></td>
				<td><input type="text" id="totalcostgreen" name="credito_total" value="<?=$credito_total?>" class="formulario"></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td><b>Juros %</b></td>
				<td><input type="text" name="juros" value="<?=$juros?>" class="formulario"></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td><b>N° de parcelas</b></td>
				<td><input type="text" name="parcelas" value="<?=$parcelas?>" class="formulario"></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="2" align='center'><b>Total das Parcelas</b></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="2"><?=$string?></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="2" align='center'><input type="submit" value="Calcular"></td>
			</tr>
		</table>
	</TD>
  </TR></form>
	<FORM METHOD=POST ACTION="cobranca_email.php" name="frm_email">
	<input type="hidden" name="email" value="<?=$email?>">
	<input type="hidden" name="corpo_email" value="<?=$corpo_email?>">
	<input type="hidden" name="posto" value="<?=$posto?>">
	</form>
  </TABLE>
<?
?>
