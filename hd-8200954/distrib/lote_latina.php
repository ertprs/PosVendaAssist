<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "../funcoes.php";

//para fechamento do lote da latina modificado
// $log_fabrica = 15

$log_fabrica = 15;

#------- AJAX para pesquisar nome do Posto --------------#
$codigo_posto = $_GET['codigo_posto'];
if (strlen ($codigo_posto) > 0) {
	include "../ajax_cabecalho.php";

	$sql = "SELECT tbl_posto.posto, tbl_posto.nome FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $log_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "Posto não cadastrado;";
	}else{
		echo pg_result ($res,0,nome);
		echo ";";
		echo pg_result ($res,0,posto);
	}
	exit;
}

#------- AJAX para pesquisar dados da OS --------------#
$sua_os = $_GET['sua_os'];
$posto  = $_GET['posto'];
if (strlen ($sua_os) > 0) {
	include "../ajax_cabecalho.php";

	if (strlen ($posto) == 0) {
		echo "Digite o código do Posto;;;;;";
		exit;
	}

	$sql = "SELECT tbl_os.os , consumidor_nome , nota_fiscal, TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura , TO_CHAR (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento, TO_CHAR (tbl_extrato.data_geracao,'DD/MM/YYYY') AS extrato 
			FROM tbl_os
			JOIN tbl_os_extra USING (os)
			LEFT JOIN tbl_extrato USING (extrato)
			WHERE tbl_os.sua_os = '$sua_os' 
			AND   tbl_os.posto  = $posto
			AND   tbl_os.fabrica = $log_fabrica";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "OS não cadastrada neste posto;";
	}else{
		$os              = pg_result ($res,0,os);
		$consumidor_nome = pg_result ($res,0,consumidor_nome);
		$nota_fiscal     = pg_result ($res,0,nota_fiscal);
		$abertura        = pg_result ($res,0,abertura);
		$fechamento      = pg_result ($res,0,fechamento);
		$extrato         = pg_result ($res,0,extrato);
		

		$sql = "SELECT tbl_distrib_lote.lote FROM tbl_distrib_lote_os JOIN tbl_distrib_lote USING (distrib_lote) WHERE tbl_distrib_lote_os.os = $os";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			$lote = pg_result ($res,0,0);
			echo "OS já foi no lote $lote;;;;;";
		}else{
			echo $consumidor_nome;
			echo ";";
			echo $nota_fiscal;
			echo ";";
			echo $abertura;
			echo ";";
			echo $fechamento;
			echo ";";
			echo $extrato;
			echo ";";
		}
	}
	exit;
}

$ajax = $_GET['ajax'];
if (strlen ($ajax) > 0) exit;



#----------------- Conferir Lote ----------------------------

$btn_acao = $_POST['btn_acao'];
if ($btn_acao == "Conferir Lote") {
	
	$msg_erro = "";

	$codigo_posto    = $_POST['codigo_posto'];
	$nf_mobra        = $_POST['nf_mobra'];
	$valor_mobra     = $_POST['valor_mobra'];
	$nf_devolucao    = $_POST['nf_devolucao'];
	$valor_devolucao = $_POST['valor_devolucao'];
	$icms_devolucao  = $_POST['icms_devolucao'];
	$obs             = $_POST['obs'];
	$continua_lote   = $_POST['continua_lote'];

	$nf_mobra = "000000" . trim ($nf_mobra) ;
	$nf_mobra = substr ($nf_mobra,strlen ($nf_mobra)-6);

	$nf_devolucao = "000000" . trim ($nf_devolucao) ;
	$nf_devolucao = substr ($nf_mobra,strlen ($nf_devolucao)-6);

	$valor_mobra = number_format($valor_mobra, 2, '.', '');//retira a , dos numeros e coloca .
	
	if(strlen($icms_devolucao)==0)$icms_devolucao='null';
	if(strlen($valor_devolucao)==0)$valor_devolucao='null';
	
	if(strlen($codigo_posto)==0){
		$msg_erro= "Por favor entre com o código do posto!";
	}

/*	if($continua_lote==1){
		$sql = "SELECT * 
				FROM tbl_distrib_lote_ 
				WHERE nf_devolucao = $nf_devolucao
				AND   posto        = $codigo_posto";
		$res = pg_exec ($con,$sql);
		if(pg_numrows($res) > 0){
			$msg_erro="O posto $codigo_posto já lançou a Nota Fiscal de Devolução n°$nf_devolucao";
		}
	}
*/


//--== INICIA PROCEDIMENTO =======================================--	
	$res = pg_exec ($con,"BEGIN");

	if(strlen($msg_erro)==0){
		$sql = "SELECT distrib_lote FROM tbl_distrib_lote WHERE distribuidor = $login_posto AND fechamento IS NULL";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 0) {
			$sql = "INSERT INTO tbl_distrib_lote (distribuidor) VALUES ($login_posto)";
			$res = pg_exec ($con,$sql);
			$res = pg_exec ($con,"SELECT CURRVAL ('seq_distrib_lote')");
		}
		$distrib_lote = pg_result ($res,0,0);
		//para latina digitar sempre no lote 60. lote numero 15.
		$distrib_lote = 60 ;


		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $log_fabrica";
		$res = pg_exec ($con,$sql);
		$posto = pg_result ($res,0,0);
			

		#----------- Gravando Notas do Posto -------------
		if($continua_lote<>'t'){
			$sql = "INSERT INTO tbl_distrib_lote_posto (
						distrib_lote, 
						posto, 
						nf_mobra, 
						valor_mobra, 
						nf_devolucao, 
						valor_devolucao,
						obs,
						icms_devolucao
					) VALUES (
						$distrib_lote      ,
						$posto             ,
						'$nf_mobra'        ,
						$valor_mobra       ,
						'$nf_devolucao'    ,
						$valor_devolucao   ,
						'$obs'             ,
						$icms_devolucao
					)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro)>0) $msg_erro .="<br><br>Este lote já foi cadastrado! ";
			$msg_erro = substr($msg_erro,6);
		}


		if(strlen($msg_erro)==0){
	//echo $sql;
	//echo 'Quantidade Item: '.$qtde_item.'<br><br>';
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$sua_os = $_POST['sua_os_' . $i];
				
//echo '<br>Sua OS'.$i.' - '.$sua_os.'<br>';
				
				if(strlen($sua_os)>0){
					$sql = "SELECT * FROM tbl_os WHERE fabrica = $log_fabrica AND posto = $posto AND sua_os = '$sua_os'";
					$res = pg_exec ($con,$sql);
	//echo "<br>1 - $sql <br>";
				
					if (pg_numrows($res) == 0) {
						$msg_erro = "OS $sua_os não encontrada";
					}
					if(pg_numrows($res) > 0){
						$os              = pg_result($res,0,os);
						$data_fechamento = pg_result($res,0,data_fechamento);

						if (strlen ($data_fechamento) == 0) { //atualiza a data de fechamento e finaliza a OS
							$res = pg_exec ($con,"UPDATE tbl_os SET data_fechamento = CURRENT_DATE WHERE os = $os");

							$msg_erro = pg_errormessage ($con);

							$res = pg_exec ($con,"SELECT fn_finaliza_os ($os,$log_fabrica)"); 
							$msg_erro = pg_errormessage ($con);
						}//FIM DO FINALIZA OS

						$sql = "SELECT * FROM tbl_distrib_lote_os WHERE os = $os";
	//echo '2 -  Verifica Lote :  '.$sql;
						$res = pg_exec ($con,$sql);
						if (pg_numrows ($res) > 0) {
							$msg_erro = "  &nbsp; OS $sua_os já está em outro lote";
	//echo $msg_erro;
						}

						if (strlen ($msg_erro) == 0) {
							$sql = "INSERT INTO tbl_distrib_lote_os (distrib_lote, os, nota_fiscal_mo) VALUES ($distrib_lote, $os, '$nf_mobra')";
	//echo '<br>3 -  Insere Lote :  '.$sql;
							$res = pg_exec ($con,$sql);
							$msg_erro = pg_errormessage ($con);
						}

					}//FIM da parte com OS
				}// FIM DO FOR
			}//FIM da verificação de erro
			//if (strlen ($msg_erro) > 0) break;
		}



		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT");
		}else{
			echo "<h1>$msg_erro</h1>";
			$res = pg_exec ($con,"ROLLBACK");
		}
	}//fim de se nao tiver todos os campos
}



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Conferência de Extratos dos Postos</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>

<script language='javascript' src='../ajax.js'></script>



<!--     Nome do Posto        -->
<script language='javascript'>
function retornaPosto (http , posto_nome , posto ) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') {
				posto_nome.innerText = results[0];
				posto.value = results[1];
				posto_nome.color = '#ff0000';
			}
		}
	}
}
function ajax_posto (codigo_posto , posto_nome , posto ) {
	url = "<?= $PHP_SELF ?>?ajax=1&codigo_posto=" + escape(codigo_posto) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPosto (http , posto_nome , posto ) ; } ;
	http.send(null);
}
</script>




<!--     Dados da OS        -->
<script language='javascript'>
function retornaSua_OS (http , consumidor, nota_fiscal, abertura, fechamento, extrato) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (http.responseText.length > 0) {
				if (typeof (results[0]) != 'undefined') {
					consumidor.innerText = results[0];
					nota_fiscal.innerText = results[1];
					abertura.innerText = results[2];
					fechamento.innerText = results[3];
					extrato.innerText = results[4];
				}else{
					consumidor.innerText = "OS não encontrada neste posto";
					consumidor.color = '#ff0000';
				}
			}
		}
	}
}
function ajax_sua_os (sua_os, posto, consumidor, nota_fiscal, abertura, fechamento, extrato ) {
	url = "<?= $PHP_SELF ?>?ajax=1&sua_os=" + escape(sua_os) + "&posto=" + escape(posto) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaSua_OS (http , consumidor, nota_fiscal, abertura, fechamento, extrato) ; } ;
	http.send(null);
}
</script>


<script language='javascript'>
nextfield="x";
netscape = "";
ver = navigator.appVersion; 
len = ver.length;
for(iln = 0; iln < len; iln++) if (ver.charAt(iln) == "(") break;
netscape = (ver.charAt(iln+1).toUpperCase() != "C");
	
function keyDown(DnEvents) {
	// ve quando e o netscape ou IE
	k = (netscape) ? DnEvents.which : window.event.keyCode;
	if (k == 13) { // preciona tecla enter
		if (nextfield == 'done') {
			alert("viu como funciona?");
			return false;
		} else {
			// se existem mais campos vai para o proximo
			eval('document.frm_lote.' + nextfield + '.focus()');
			return false;
		}
	}
}

document.onkeydown = keyDown; // work together to analyze keystrokes
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP);
</script>





<center><h1>Geração de Lotes</h1></center>

<p>
<?if (strlen($msg_erro) > 0) {?>
<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<?}?>
<br>




<form name='frm_lote' action='<? echo $PHP_SELF ?>' method='post'>
<input type='hidden' name='posto'>

<table border='0' width='500' cellpadding='3'>
<tr>
	<td nowrap>
		Código Posto <input type='text' name='codigo_posto' size='10' onblur="javascript: ajax_posto (this.value, document.all('posto_nome') , document.frm_lote.posto ) " onfocus="javascript: nextfield='nf_mobra' " >
	</td>

	<td width='100%'>
		<span id='posto_nome' valign='top'></span>
	</td>
</tr>
<tr>
	<td colspan='2'>
		Continua a digitação de OS de lote anterior<INPUT TYPE="checkbox" NAME="continua_lote" value="t">
	</td>
</tr>
</table>


<table border='0' width='500' cellpadding='3'>
<tr bgcolor='#CCCCFF'>
	<td nowrap>
		NF Mão de Obra 
	</td>
	<td nowrap>
		Valor Mão de Obra
	</td>
</tr>

<tr>
	<td nowrap>
		<input type='text' name='nf_mobra' size='6' maxlength='6' onfocus="javascript: nextfield='valor_mobra' ">
	</td>
	<td nowrap>
		<input type='text' name='valor_mobra' size='10' maxlength='10' onfocus="javascript: nextfield='nf_devolucao' ">
	</td>
</tr>
</table>

 
<table border='0' width='500' cellpadding='3'>
<tr bgcolor='#CCCCFF'>
	<td nowrap>
		NF Devolução 
	</td>
	<td nowrap>
		Valor Devolução
	</td>
	<td nowrap>
		ICMS Devolução
	</td>
</tr>

<tr>
	<td nowrap>
		<input type='text' name='nf_devolucao' size='6' maxlength='6' onfocus="javascript: nextfield='valor_devolucao' ">
	</td>
	<td nowrap>
		<input type='text' name='valor_devolucao' size='10' maxlength='10' onfocus="javascript: nextfield='icms_devolucao' ">
	</td>
	<td nowrap>
		<input type='text' name='icms_devolucao' size='10' maxlength='10' onfocus="javascript: nextfield='sua_os_0' ">
	</td>
</tr>
<tr>
	<td colspan='3' align='center'bgcolor='#CCCCFF'>
		Observação
	</td>
</tr>

<tr>
	<td colspan='3'>
		<TEXTAREA NAME="obs" ROWS="5" COLS="50" onfocus="javascript: nextfield='' "></TEXTAREA>
	</td>
</tr>
</table>

 
<table width='650' border='1' cellpadding='3' cellspacing='0'>
<tr bgcolor='#CCCCFF'> 
	<td align='center'><b>O.S.</b></td>
	<td align='center' width='200'><b>Consumidor</b></td>
	<td align='center'><b>Nota Fiscal</b></td>
	<td align='center'><b>Abertura</b></td>
	<td align='center'><b>Fechamento</b></td>
	<td align='center'><b>Extrato</b></td>
</tr>

<?
for ($i = 0 ; $i < 250 ; $i++) {
	$proximo = "sua_os_" . ($i + 1) ;

	echo "<tr>";
	
	echo "<td>";
	echo "<input type='text' name='sua_os_$i' size='10' onfocus=\"javascript: nextfield='$proximo' \" onblur=\"javascript: ajax_sua_os (this.value, document.frm_lote.posto.value, document.all('consumidor_$i') , document.all('nota_fiscal_$i') , document.all  ('abertura_$i') , document.all ('fechamento_$i') , document.all('extrato_$i') ) \" >";
	echo "</td>";

	echo "<td><span id='consumidor_$i'></span></td>";
	echo "<td><span id='nota_fiscal_$i'></span></td>";
	echo "<td><span id='abertura_$i'></span></td>";
	echo "<td><span id='fechamento_$i'></span></td>";
	echo "<td><span id='extrato_$i'></span></td>";

	echo "</tr>";
}

echo "</table>";

?>

<input type='hidden' name='qtde_item' value='<?= $i ?>'>
<input type='submit' name='btn_acao' value='Conferir Lote'>

</form>



<? #include "rodape.php"; ?>

</body>
</html>
