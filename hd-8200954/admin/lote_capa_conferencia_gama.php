<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

include "../funcoes.php";



#------- AJAX para pesquisar nome do Posto --------------#
$codigo_posto = $_GET['codigo_posto'];

if (strlen ($codigo_posto) > 0) {
	include "../ajax_cabecalho.php";

	$sql = "SELECT	tbl_posto.posto,
					tbl_posto.nome
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE tbl_posto_fabrica.fabrica = '$login_fabrica'
			AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";

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


#------- AJAX para atualizar os dados da OS --------------#
$extrato_recusa= $_GET['extrato_recusa'];
$sua_os2       = $_GET['sua_os2'];
$posto         = $_GET['posto'];
$nota_fiscal   = $_GET['nota_fiscal'];
$aux_data_nf   = $_GET['data_nf'];
$aux_data_nf   = fnc_formata_data_pg($aux_data_nf);
$recusar_direto = $_GET['recusar_direto'];

$msg_erro = '';
if(strlen($sua_os2) > 0){
	$sql = "SELECT os, posto, nota_fiscal,data_nf FROM tbl_os WHERE sua_os = '$sua_os2' AND posto = $posto";
	$res = pg_exec($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if(pg_numrows($res) > 0 AND strlen($msg_erro) == 0){

		$os              = pg_result($res,0,os);
		$nota_fiscal_ant = pg_result($res,0,nota_fiscal);
		$data_nf_ant     = pg_result($res,0,data_nf);

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_os set nota_fiscal = '$nota_fiscal', data_nf = $aux_data_nf WHERE os = $os";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT fn_valida_os($os,$login_fabrica)";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "INSERT INTO tbl_os_status (os,status_os,observacao,admin) VALUES
					($os,119,'Dados Anteriores: NF $nota_fiscal_ant e Data NF: $data_nf_ant',$login_admin);";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");

		}
		if (strlen($recusar_direto) > 0 OR strlen($msg_erro) > 0) {
			echo "<FONT SIZE='-3' COLOR='#FF0000'>";

			if (strlen($msg_erro) > 0) echo "Não foi possível atualizar a OS. $msg_erro<br><b>";

			echo "Preencha o motivo da recusa<br></b> <INPUT TYPE='text' NAME='obs_recusada' id='obs_recusada_$linha_os' size='60'><br>";
			echo "OU<br>SELECIONE OS MOTIVOS<BR><SELECT name='obs_recusada2' id='obs_recusada2'>";
			echo "<option value=''></option>";
			echo "<option value='1'>Ordem de serviço recusada devido constar número de serie irregular...</option>";
			echo "<option value='2'>Não enviou a ordem de serviço com a documentação  necessária</option>";
			echo "<option value='3'>Não enviou o xerox da nota fiscal do consumidor</option>";
			echo "<option value='4'>Não enviou o certificado de garantia, data da compra do produto superior á 3 meses</option>";
			echo "</select>";
			echo "<span onClick=\"javascript: ajax_recusa_os (document.getElementById('obs_recusada_$linha_os').value,'$os', document.getElementById('extrato').value,'$linha_os')\"><FONT SIZE='-3' COLOR='#330000'><b style='cursor:pointer; cursor:hand;'><div id='recusar_$linha_os'>RECUSAR_OS</div></FONT></span><br><div id='erro_$linha_os'></div>";
		}

	}else{
		echo "<FONT SIZE='-3' COLOR='#FF0000'>OS não encontrada!</FONT>";
	}
	exit;
}


#------- AJAX para pesquisar dados da OS --------------#
$sua_os   = $_GET['sua_os'];
$posto    = $_GET['posto'];
$linha_os = $_GET['linha_os'];


if (strlen ($sua_os) > 0) {
	include "../ajax_cabecalho.php";

	if (strlen ($posto) == 0) {
		echo "Digite o código do Posto";
		exit;
	}

	if (strlen($nota_fiscal) == 0 OR strlen($data_nf) == 0) {
		echo "Verifique Nota Fiscal e Data";
		exit;
	}


	$sql = "SELECT tbl_os.os
			FROM tbl_os
			JOIN tbl_os_extra USING (os)
			JOIN tbl_extrato USING (extrato)
			LEFT JOIN tbl_os_revenda ON tbl_os.revenda = tbl_os_revenda.revenda
			WHERE tbl_os.sua_os = '$sua_os'
			AND   tbl_os.posto  = $posto
			AND   tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "OS não encontrada para este PA ou já recusada!";
	}else{

		$sql = "SELECT DISTINCT tbl_os.os
					FROM tbl_os
					JOIN tbl_os_extra USING (os)
					JOIN tbl_extrato USING (extrato)
					LEFT JOIN tbl_os_revenda ON tbl_os.revenda = tbl_os_revenda.revenda
					WHERE tbl_os.sua_os = '$sua_os'
					AND   tbl_os.posto  = $posto
					AND   to_char(tbl_os.data_nf,'DD/MM/YYYY')::text = '$data_nf'
					AND   tbl_os.nota_fiscal = '$nota_fiscal'
					AND   tbl_os.fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			$lote = pg_result ($res,0,0);
			echo "<FONT SIZE='-3' COLOR='#336600'><b>Conferida com sucesso!</b></FONT>";
		}else{
			echo "<FONT SIZE='-3' COLOR='#FF0000'>Dados não conferem! Verifique atentamente!!</FONT><br><span onClick=\"javascript: ajax_conferencia ('$revendedor', '$sua_os', '$data_nf', '$nota_fiscal', '$obs', '$posto', '$linha_os')\"><FONT SIZE='-3' COLOR='#FF0000'><u><b style='cursor:hand;'>ATUALIZAR a OS com informações corretas<br>$i</b></u></FONT></span>";
		}
	}
	exit;
}


#------- AJAX para recusa de OS --------------#
$os_recusada       = $_GET['os_recusada'];
$obs_recusada      = trim($_GET['obs_recusada']);
$obs_recusada2     = $_GET['obs_recusada2'];
$extrato_recusa    = $_GET['extrato_recusa'];

if(strlen($os_recusada) > 0){

	$res = pg_exec($con,"BEGIN TRANSACTION");

	
	if (strlen($obs_recusada) == 0 and empty($obs_recusada2)) {
		$msg_erro    = " Informe a observação da recusa. ";
	}

	if(empty($msg_erro) and !empty($obs_recusada2)) {
		switch($obs_recusada2) {
			case 1: $obs_recusada = "Ordem de serviço recusada devido constar número de serie irregular, favor verificar circular enviada pela fábrica dia 05/11";break;
			case 2: $obs_recusada = "Não enviou a ordem de serviço com a documentação  necessária";break;
			case 3: $obs_recusada = "Não enviou o xerox da nota fiscal do consumidor";break;
			case 4: $obs_recusada = "Não enviou o certificado de garantia, data da compra do produto superior á 3 meses";break;
		}
	}

	/* HD 171327 */
	if (strlen($os_recusada)>0) {
		$sqlR = "SELECT tbl_os_status.status_os
				FROM tbl_os
				JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os
				AND  tbl_os_status.fabrica_status      = $login_fabrica
				WHERE tbl_os.os      = $os_recusada
				AND   tbl_os.fabrica = $login_fabrica
				AND  tbl_os_status.extrato = $extrato_recusa
				ORDER BY tbl_os_status.data DESC LIMIT 1";
		$resR = pg_exec($con, $sqlR);

		if(pg_numrows($resR)>0){
			$ultimo_status_os = pg_result($resR,0,status_os);
			if($ultimo_status_os=="13"){
				$msg_erro  = " OS já recusada! ";
			}
		}
	}

	if (strlen($msg_erro) == 0) {

		$sql = "SELECT fn_recusa_os($login_fabrica, $extrato_recusa, $os_recusada, '$obs_recusada');";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

#		$sql = "SELECT fn_estoque_recusa_os($os_recusada,$login_fabrica,$login_admin);";
#		$res = pg_exec($con,$sql);
#$sql_mostra .= "<br>". $sql;
#		$msg_erro = pg_errormessage($con);

	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}

	if(strlen($msg_erro) == 0){
		echo "ok|<FONT SIZE='-3' COLOR='#336600'>OS Recusada! <br> Motivo: <b>$obs_recusada</b></FONT>";
	}else{
		echo "erro|<FONT SIZE='-3' COLOR='#FF0000'>$msg_erro</FONT>";
	}
	exit;
}



$ajax = $_GET['ajax'];
if (strlen ($ajax) > 0) exit;

$extrato = $_GET['extrato'];

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
	$total_sedex     = $_POST['total_sedex'];
	$obs             = $_POST['obs'];
	$continua_lote   = $_POST['continua_lote'];
	$extrato         = $_POST['extrato'];
	$data_recebimento_lote = $_POST['data_recebimento_lote'];
	$identificador_objeto  = $_POST['identificador_objeto'];
	$data_nf_mobra         = $_POST['data_nf_mobra'];


	if(strlen($data_nf_mobra)>0){
		$xdata_nf_mobra         = "'" . substr ($data_nf_mobra,6,4) . "-" . substr ($data_nf_mobra,3,2) . "-" . substr ($data_nf_mobra,0,2) . "'";
	}else{
		$xdata_nf_mobra         = "null";
	}

	if(strlen($data_recebimento_lote)>0){
		$xdata_recebimento_lote = "'" . substr ($data_recebimento_lote,6,4) . "-" . substr ($data_recebimento_lote,3,2) . "-" . substr ($data_recebimento_lote,0,2) . "'";
	}else{
		$xdata_recebimento_lote = "null";
	}

	if(strlen($identificador_objeto)>0){
		$xidentificador_objeto = "'" . $identificador_objeto. "'";
	}else{
		$xidentificador_objeto = "null";
	}

	$nf_mobra = "00000000" . trim ($nf_mobra) ;
	$nf_mobra = substr ($nf_mobra,strlen ($nf_mobra)-8);

	$nf_devolucao = "00000000" . trim ($nf_devolucao) ;
	$nf_devolucao = substr ($nf_mobra,strlen ($nf_devolucao)-8);

	$valor_mobra = str_replace (".","",$valor_mobra);
	$valor_mobra = str_replace (",",".",$valor_mobra);

	$total_sedex = str_replace (",",".",$total_sedex);
 
	if(strlen($icms_devolucao)==0)$icms_devolucao='null';
	if(strlen($valor_devolucao)==0){
            $valor_devolucao='null';
        }else{
	   $valor_devolucao = str_replace (".","", $valor_devolucao);
	   $valor_devolucao = str_replace (",",".",$valor_devolucao);
        }
	if(strlen($total_sedex)==0)$total_sedex='null';

	if(strlen($codigo_posto)==0 AND strlen($extrato) == 0){
		$msg_erro= "Por favor entre com o código do posto!";
	}


//--== INICIA PROCEDIMENTO =======================================--
	$res = pg_exec ($con,"BEGIN");

	if(strlen($msg_erro)==0){
		$sql = "SELECT distrib_lote FROM tbl_distrib_lote WHERE distribuidor = 4311 AND fechamento IS NULL AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		//caso nao tenha o lote, cria um lote novo
		if (pg_numrows ($res) == 0) {
			$sql = "SELECT lote FROM tbl_distrib_lote WHERE fabrica = $login_fabrica AND fechamento IS NOT NULL ORDER BY lote DESC LIMIT 1 ";
			$res = pg_exec($con,$sql);
			if(pg_num_rows($res) > 0) {
				$lote = pg_result($res,0,lote);
			}else{
				$lote = 0;
			}
			$lote++;


			$sql = "INSERT INTO tbl_distrib_lote (distribuidor, lote, fabrica) VALUES ('4311','$lote',$login_fabrica )";
			$res = pg_exec ($con,$sql);

			$sql = "SELECT distrib_lote FROM tbl_distrib_lote WHERE distribuidor = 4311 AND fechamento IS NULL AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
//			$res = pg_exec ($con,"SELECT CURRVAL ('seq_distrib_lote')");
		}
		$distrib_lote = pg_result ($res,0,0);
                if(strlen($extrato) > 0){
		    $sql = "SELECT posto FROM tbl_extrato WHERE extrato = $extrato; ";
	  	    $res = pg_exec ($con,$sql);
                    #echo "$sql - $posto";
		    $posto = pg_result ($res,0,0);
                }

               
		#----------- Gravando Notas do Posto -------------
		if($continua_lote<>'t'){
			$sql = "INSERT INTO tbl_distrib_lote_posto (
						distrib_lote       ,
						posto              ,
						nf_mobra           ,
						valor_mobra        ,
						nf_devolucao       ,
						valor_devolucao    ,
						total_sedex        ,
						obs                ,
						icms_devolucao     ,
						data_nf_mobra        ,
						data_recebimento_lote,
						identificador_objeto
					) VALUES (
						$distrib_lote      ,
						$posto             ,
						'$nf_mobra'        ,
						$valor_mobra       ,
						'$nf_devolucao'    ,
						$valor_devolucao   ,
						$total_sedex       ,
						'$obs'             ,
						$icms_devolucao    ,
						$xdata_nf_mobra       ,
						$xdata_recebimento_lote,
						$xidentificador_objeto

					)";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(strlen($msg_erro)>0) $msg_erro .="<br><br>Este lote já foi cadastrado! ";
			$msg_erro = substr($msg_erro,6);
		}

		if(strlen($msg_erro)==0){
#	echo $sql;
#	echo 'Quantidade Item: '.$qtde_item.'<br><br>';
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$sua_os      = $_POST['sua_os_' . $i];
				$data_nf     = $_POST['data_nf_' . $i];
				$nota_fiscal = $_POST['nota_fiscal_' . $i];
				$os          = $_POST['os_' . $i];
				$xdata_nf    = fnc_formata_data_pg($data_nf);

#	echo '<br>Sua OS'.$i.' - '.$sua_os.'<br>';
				if(strlen($os) > 0){
					$sql3 = "SELECT os FROM tbl_os_status WHERE os = $os AND extrato = $extrato AND status_os = 13;";
					$res3 = pg_exec($con,$sql3);
				}
#	echo "<br>1 - $sql3 <br>";
				if(strlen($sua_os)>0 AND pg_numrows($res3) == 0){
					$sql = "SELECT * FROM tbl_os WHERE fabrica = $login_fabrica AND posto = $posto AND sua_os = '$sua_os' AND data_nf = $xdata_nf AND nota_fiscal = '$nota_fiscal'";
					$res = pg_exec ($con,$sql);
#	echo "<br>1 - $sql <br>";

					if (pg_numrows($res) == 0) {
						$msg_erro = "OS $sua_os não encontrada";
					}
					if(pg_numrows($res) > 0){
						$os              = pg_result($res,0,os);
						$data_fechamento = pg_result($res,0,data_fechamento);

						if($login_fabrica <> 3){ //HD 20200
							$sqlnf = "SELECT data_nf FROM tbl_os WHERE os = $os";
							$resnf = pg_exec ($con,$sqlnf);
							if(pg_numrows($resnf) > 0){
								$xxdata_nf = pg_result($res,0,data_nf);
								$xdata_nf = str_replace("'","",$xdata_nf);
								if($xdata_nf <> $xxdata_nf){
									$msg_erro = "Data da Nota Fiscal vazia ou não confere na os $sua_os";
								}
							}
						}
						if(strlen($msg_erro)==0){
							if (strlen ($data_fechamento) == 0) { //atualiza a data de fechamento e finaliza a OS
								$res = pg_exec ($con,"UPDATE tbl_os SET data_fechamento = CURRENT_DATE WHERE os = $os");

								$msg_erro .= pg_errormessage ($con);

								$res = pg_exec ($con,"SELECT fn_finaliza_os ($os,$login_fabrica)");
								$msg_erro .= pg_errormessage ($con);
							}//FIM DO FINALIZA OS

							$sql = "SELECT * FROM tbl_distrib_lote_os WHERE os = $os";
						//echo '2 -  Verifica Lote :  '.$sql;
							$res = pg_exec ($con,$sql);
							if (pg_numrows ($res) > 0) {
								$msg_erro .= "  &nbsp; OS $sua_os já está em outro lote";
						//echo $msg_erro;
							}

							if (strlen ($msg_erro) == 0) {
								$sql = "INSERT INTO tbl_distrib_lote_os (distrib_lote, os, nota_fiscal_mo, admin) VALUES ($distrib_lote, $os, '$nf_mobra', $login_admin)";
						//echo '<br>3 -  Insere Lote :  '.$sql;
								$res = pg_exec ($con,$sql);
								$msg_erro .= pg_errormessage ($con);
							}
						}//FIM MSG ERRO
					}//FIM da parte com OS
				}// FIM DO FOR
			}//FIM da verificação de erro
			//if (strlen ($msg_erro) > 0) break;

			$sql = "UPDATE tbl_extrato_lancamento set distrib_lote = $distrib_lote, nota_fiscal_mo = '$nf_mobra'
					WHERE extrato = $extrato and fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage ($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT");
			$extrato = '';
		}else{
			echo "<h1>$msg_erro</h1>";
			$res = pg_exec ($con,"ROLLBACK");
		}
	}//fim de se nao tiver todos os campos
}

?>

<html>
<body>

<?

$title = "Geração de Lotes";
include 'cabecalho.php'

?>


<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>

<script type="text/javascript" src="js/firebug.js"></script>
<?php include "../js/js_css.php";?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>


<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$('input[rel=data]').datepick({startDate:'01/01/2000'});
		$("input[rel=data]").mask("99/99/9999");
	});
</script>


<!--     Nome do Posto        -->
<script language='javascript'>
function retornaPosto (http , posto_nome , posto ) {
	var posto_nome2 = document.getElementById(posto_nome);
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') {
				posto_nome2.innerHTML = results[0];
				posto.value = results[1];
				posto_nome2.color = '#ff0000';
			}
		}
	}
}
function ajax_posto (codigo_posto , posto_nome , posto, fabrica ) {
	url = "<?= $PHP_SELF ?>?ajax=1&codigo_posto=" + escape(codigo_posto) + "&fabrica=" + escape(fabrica) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPosto (http , posto_nome , posto, fabrica ) ; } ;
	http.send(null);
}
</script>




<!--     Dados da OS        -->
<script language='javascript'>
function retornaSua_OS (http , obs) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText;
			obs.innerHTML = results;
		}
	}
}

function retornaSua_OS2 (http , obs, linha_os) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText;
			document.getElementById('obs_' + linha_os).innerHTML = results;
		}
	}
}

function ajax_sua_os (revendedor, sua_os, data_nf, nota_fiscal, obs, posto, linha_os) {
	if (sua_os.length>0){
		url = "<?= $PHP_SELF ?>?ajax=1&sua_os=" + escape(sua_os) + "&posto=" + escape(posto) + "&revendedor=" + escape(revendedor) + "&data_nf=" + escape(data_nf) + "&nota_fiscal=" + escape(nota_fiscal) + "&linha_os=" + escape(linha_os) ;
		http.open("GET", url , true);
		http.onreadystatechange = function () { retornaSua_OS (http, obs) ; } ;
		http.send(null);
	}
}

function ajax_conferencia (revendedor, sua_os, data_nf, nota_fiscal, obs, posto, linha_os, recusar_direto) {
	if (sua_os.length>0){
		url = "<?= $PHP_SELF ?>?ajax=1&sua_os2=" + escape(sua_os) + "&posto=" + escape(posto) + "&revendedor=" + escape(revendedor) + "&data_nf=" + escape(data_nf) + "&nota_fiscal=" + escape(nota_fiscal) + "&linha_os=" + escape(linha_os) + "&recusar_direto=" + escape(recusar_direto);
		http.open("GET", url , true);
		http.onreadystatechange = function () { retornaSua_OS2 (http, obs, linha_os) ; } ;
		http.send(null);
	}
}

function ajax_recusa_os(obs_recusada,os_recusada, extrato_recusa,linha_os){
	var obs2 = $('#obs_recusada2 option:selected').val();
	if(os_recusada.length>0){
		url = "<?= $PHP_SELF ?>?ajax=1&os_recusada=" + escape(os_recusada) + "&extrato_recusa=" + escape(extrato_recusa) + "&obs_recusada=" + escape(obs_recusada)+ "&obs_recusada2=" + escape(obs2);
		
		$.ajax({
		type: "GET",
		url: "<?= $PHP_SELF ?>",
		data: "ajax=1&os_recusada=" + escape(os_recusada)+ "&extrato_recusa=" + escape(extrato_recusa) + "&obs_recusada=" + escape(obs_recusada)+ "&obs_recusada2=" + escape(obs2),
		cache: false,
		beforeSend: function() {
			// enquanto a função esta sendo processada, você
			var div = document.getElementById('erro_'+linha_os);
			div.innerHTML = "<img src='imagens/ajax-loader.gif'>";
			// msg de carregando
		},
		success: function(txt) {
//	$('#observacao').val(txt);
			campo = txt.split('|');
			if (campo[0]=='erro'){
				var div = document.getElementById('erro_'+linha_os);
				div.innerHTML = campo[1];
			}

			if (campo[0]=='ok'){
				var div = document.getElementById('recusar_'+linha_os);
				div.innerHTML = '';
				var div2 = document.getElementById('recusar2_'+linha_os);
				div2.innerHTML = 'os já recusada';
				var div = document.getElementById('erro_'+linha_os);
				div.innerHTML = campo[1];
				document.getElementById('obs_' + linha_os).innerHTML = campo[1];

			}
		},
		error: function(txt) {
			alert(txt);
		}
	});
	
	}
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

function procurarOS(){
	var os = document.frm_lote.os_procurar.value;
	var achou=0;
	alert('Procurando por '+os);
	if (os.length>0){
		var formulatio=document.frm_lote;
		for( var i = 0 ; i < formulatio.length; i++ ){
			if (formulatio.elements[i].type=='text' && formulatio.elements[i].name!='os_procurar'){
				if (os==formulatio.elements[i].value){
					alert('Achou a OS '+os);
					formulatio.elements[i].focus();
					achou++;
					break;
				}
			}
		}
	}else{
		alert('Digite a OS');
	}
	if (achou==0){
		alert('OS não econtrada');
	}
}


function trim(cp) {
   var txt = new String(cp.value);
   while((txt.charAt(0)==" ")||(txt.charAt(txt.length-1)==" "))
      txt = txt.replace(/^ /,"").replace(/ $/,"");
   return cp.value = txt;
}

function importarLote(){
	var oeses = document.frm_lote.lista_os.value;
	var array_os = new Array();
	array_os = oeses.split("\n");
	for (i=0;i<array_os.length;i++){
		document.frm_lote.lista_os.value;
		var campo = document.getElementById("sua_os_"+i);
		if (campo.value==""){
			if (array_os[i]!="")
				campo.value=array_os[i];
		}
	}
}

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
</script>

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

<?
if(strlen($extrato) > 0){
	$sql = "SELECT tbl_posto.posto                ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					to_char(tbl_extrato.data_geracao,'DD/MM/YYY') AS data_geracao
				FROM tbl_extrato
				JOIN tbl_posto USING(posto)
				JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_extrato.extrato = $extrato";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		$posto        = pg_result($res,0,posto);
		$codigo_posto = pg_result($res,0,codigo_posto);
		$posto_nome   = pg_result($res,0,nome);
		$data_geracao = pg_result($res,0,data_geracao);
	}
	echo "<b>EXTRATO $extrato - $data_geracao</b>";
}

if(strlen($extrato) > 0){
	$sql = "SELECT tbl_distrib_lote_os.os,tbl_distrib_lote_posto.obs
				FROM tbl_distrib_lote_os
				JOIN tbl_os_extra USING(os)
				JOIN tbl_distrib_lote_posto ON nf_mobra = nota_fiscal_mo and tbl_distrib_lote_os.distrib_lote = tbl_distrib_lote_posto.distrib_lote
			WHERE tbl_os_extra.extrato = $extrato
			AND tbl_distrib_lote_posto.posto = {$posto}";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		echo "<br><br><b>EXTRATO JÁ CONFERIDO</b><br><br>";
		echo "Obs: ". pg_fetch_result($res,0,'obs');
		include "rodape.php";
		exit;
	}else{
		$sql = "SELECT extrato_lancamento
				FROM   tbl_extrato_lancamento
				WHERE  extrato = $extrato and distrib_lote notnull";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0){
			echo "<br><br><b>EXTRATO JÁ CONFERIDO</b><br><br>";
			include "rodape.php";
			exit;
		}
	}
}

if (($login_fabrica ==81 or $login_fabrica == 51) AND strlen($extrato) > 0){

	$msg_notas = "";
	$msg_mes_anterior = "";

	/* HD 46741 */
	$sql = "SELECT  CASE WHEN data_geracao > '2008-10-30'::date THEN '1' ELSE '0' END
			FROM tbl_extrato
			WHERE extrato = $extrato ";
	$res2 = pg_exec ($con,$sql);
	$verificacao = pg_result ($res2,0,0);

	## Verificação do Mês Anterior
	## Verifica se tem extrato no mês anterior e se foi digitado as notas de devolução
	## Válido apartir de data_geracao > '2007-12-01'
	$sqlConf = "
				SELECT extrato,admin_lgr
				FROM tbl_extrato
				WHERE fabrica    = $login_fabrica
				AND posto        = $posto
				AND extrato      < $extrato
				AND data_geracao > '2007-11-01'
				AND liberado    IS NOT NULL
				ORDER BY data_geracao DESC
				LIMIT 1";
	$resConf = pg_exec ($con,$sqlConf);
	if (pg_numrows($resConf)>0){
		$admin_lgr   = trim(pg_result($resConf,0,admin_lgr));
		$lgr_extrato = trim(pg_result($resConf,0,extrato));
		# Verifica se as notas de devolução do Mes anterior foi recebido pela Fabrica
		$sqlConf = "SELECT faturamento,
							nota_fiscal,
							emissao - CURRENT_DATE AS dias_emitido,
							conferencia,
							movimento,
							devolucao_concluida
					FROM tbl_faturamento
					WHERE fabrica         = $login_fabrica
					AND distribuidor      = $posto
					AND extrato_devolucao = $lgr_extrato
					AND posto             IS NOT NULL
					";
		$resConf = pg_exec ($con,$sqlConf);
		//if($ip=="201.76.85.4") echo $sql;
		$notas_array = array();
		$msg_notas = "";
		if (pg_numrows($resConf)>0){
			for ( $w=0; $w < pg_numrows($resConf); $w++ ){
				$fat_faturamento  = trim(pg_result($resConf,$w,faturamento));
				$fat_nota_fiscal  = trim(pg_result($resConf,$w,nota_fiscal));
				$fat_dias_emitido = trim(pg_result($resConf,$w,dias_emitido));
				$fat_conferencia  = trim(pg_result($resConf,$w,conferencia));
				$fat_movimento    = trim(pg_result($resConf,$w,movimento));
				$fat_concluido    = trim(pg_result($resConf,$w,devolucao_concluida));

				// $admin_lgr -> se a Fábrica liberou o mes anterior, deixa digitar este mes
				// $fat_movimento != 'NAO_RETOR.' -> nao exige conferencia caso nao for conferida NF de peças nao retornaveis - HD 13450
				if (strlen($admin_lgr)==0 AND strlen($fat_conferencia)==0 AND $fat_concluido!='t' AND $fat_movimento != 'NAO_RETOR.'){
					array_push($notas_array,$fat_nota_fiscal);
				}
			}
		}

		#Dynacom nao tem conferencia de NF - HD12684
		#Gmaa nao faz também!!!!
		if ($login_fabrica ==81 or $login_fabrica==2 or $login_fabrica == 51){
			$notas_array = array();
		}

		if (count($notas_array)>0 OR pg_numrows($resConf)==0){
			if (count($notas_array)>0 ){
				$msg_mes_anterior = "<a href=\"$PHP_SELF?ajax=true&extrato=$lgr_extrato&status=confirmada&nf=".implode(",",$notas_array)."&height=240&width=320\"  title=\"NF não confirmada\" class=\"thickbox\">Extrato Bloqueado</a>";
			}else{

				$sqlConf = "SELECT faturamento
							FROM tbl_faturamento
							JOIN tbl_faturamento_item USING(faturamento)
							JOIN tbl_peca             USING(peca)
							WHERE tbl_faturamento.fabrica = $login_fabrica
							AND   tbl_faturamento.posto   = $posto
							AND   tbl_faturamento.extrato_devolucao = $lgr_extrato
							";
				if ($verificacao=='1'){
					$sqlConf .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE OR tbl_peca.produto_acabado IS TRUE) ";
				}
				$resConf = pg_exec ($con,$sqlConf);
				if (pg_numrows($resConf)> 0){
					$msg_mes_anterior = "<a href=\"$PHP_SELF?ajax=true&extrato=$lgr_extrato&status=anterior&height=240&width=320\"  title=\"NF não confirmada\" class=\"thickbox\">Extrato Bloqueado</a>";
				}
			}
		}
	}


	/*PARA A HBTECH É FATURADO PELA TELECONTROL - DISTRIB*/
	if($login_fabrica ==  25){
		#verifica se tem peças de devolução em garantia
		$sqlLgr = "SELECT count(*)
				FROM   tbl_faturamento
				WHERE  fabrica           = $login_fabrica
				AND    extrato_devolucao = $extrato";

		$resLGR = pg_exec ($con,$sqlLgr);
		$qtde_devolucao = trim(pg_result($resLGR,0,0));
	}else{
		#verifica se tem peças de devolução em garantia
		$sqlLgr = "SELECT count(*)
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				JOIN tbl_peca             USING(peca)
				WHERE  tbl_faturamento.fabrica           = $login_fabrica
				AND    tbl_faturamento.extrato_devolucao = $extrato
				AND    tbl_faturamento.distribuidor IS NULL
				AND     (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '69%')
				";
		if ($verificacao=='1'){
			$sqlLgr .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE OR tbl_peca.produto_acabado IS TRUE) ";
		}
		$resLGR = pg_exec ($con,$sqlLgr);
		$qtde_devolucao = trim(pg_result($resLGR,0,0));
	}

	$devolveu_pecas = "nao";

	if ( $login_fabrica ==81 or $login_fabrica == 51) {
		$posto_da_fabrica = "4311";
	}


	# Verifica se já foi digitada
	$sqlLgr = "SELECT	extrato_devolucao,
						emissao,
						nota_fiscal
			FROM tbl_faturamento
			WHERE distribuidor      = $posto
			AND   posto             in ($posto_da_fabrica)
			AND   extrato_devolucao = $extrato
			AND   fabrica           = $login_fabrica
			AND  cancelada          IS NULL";
	$resLGR = pg_exec ($con,$sqlLgr);
	if (pg_numrows($resLGR)>0){
		$devolveu_pecas = "sim";
	}
}
if ($qtde_devolucao >0 AND $devolveu_pecas == "nao" AND strlen($msg_mes_anterior)==0){
	echo "<br><br><b>Nota Fiscal não conferida.</b><br><br>";
	include "rodape.php";
	exit;
}


if(strlen($extrato) > 0){
	$sql = "SELECT tbl_distrib_lote.lote
				FROM tbl_distrib_lote_posto
				JOIN tbl_distrib_lote USING(distrib_lote)
			WHERE tbl_distrib_lote_posto.posto = $posto
			AND   tbl_distrib_lote.fabrica = $login_fabrica
			AND   tbl_distrib_lote.fechamento IS NULL";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		$lote_pa = "00".pg_result($res,0,lote);
		$cor_lote_ant = "style='color:#FF0000;'";
		echo "<br><br><FONT SIZE='3' COLOR='#FF0000'><B>JÁ EXISTE UM EXTRATO DESTE PA NO LOTE $lote_pa.<BR>SELECIONE A OPÇÃO <U>\"Continua a digitação de OS de lote anterior\" OU FINALIZE LOTE ATUAL.</U></B></FONT></b><br><br>";
	}
}


?>

<form name='frm_lote' action='<? echo $PHP_SELF ?>' method='post'>
<input type='hidden' name='posto' value='<? echo "$posto"; ?>'>
<input type='hidden' name='fabrica' value='<? echo "$login_fabrica"; ?>'>
<input type='hidden' name='extrato' id='extrato' value='<? echo "$extrato"; ?>'>
<table width="700" align="center" border="0" cellspacing="0" cellpadding="2">
<tr><td>
<p>Localizar OS <input type='text' name='os_procurar' value=''> <input type='button' value='Procurar' onclick='javascript:procurarOS();'></p>


<table border='0' width='100%' cellpadding='3'>
<tr>
	<td nowrap>
		<? if(strlen($extrato) == 0){ ?>
			Código Posto<input type='text' name='codigo_posto' size='10' onblur="javascript: ajax_posto (this.value, 'posto_nome' , document.frm_lote.posto, document.frm_lote.fabrica.value) " onfocus="javascript: nextfield='nf_mobra' " ><br>&nbsp;
		<? }else{
			echo "<b>$codigo_posto - $posto_nome</b>";
			echo "<input type='hidden' name='codigo_posto' value='$codigo_posto'>";
		} ?>
	</td>

	<td width='100%' style='font-size: 12px'>
		<span id='posto_nome' valign='top'></span>
	</td>
</tr>
<tr>
	<td colspan='2' <? echo $cor_lote_ant ?>>
		Continua a digitação de OS de lote anterior <INPUT TYPE="checkbox" NAME="continua_lote" value="t">
		<BR>
		<FONT size='1' color='#919191'>(os dados da NF não serão salvos, utilize esta opção quando for continuar a digitação de uma mesma NF)</FONT>
	</td>
</tr>
</table>



<table border='0' width='100%' cellpadding='3'>
<tr bgcolor='#CCCCFF'>
	<td class="frm-title" nowrap>
		NF Mão de Obra
	</td>
	<td class="frm-title" nowrap>
		Data NF Mão de Obra
	</td>
	<td class="frm-title" nowrap>
		Valor Mão de Obra
	</td>
	<td class="frm-title" nowrap>
		Data Receb. Lote
	</td>
	<td class="frm-title" nowrap>
		Número Objeto
	</td>
</tr>

<tr>
	<td align='center' nowrap>
		<input type='text' name='nf_mobra' size='8' maxlength='8' onfocus="javascript: nextfield='valor_mobra' ">
	</td>
	<td align='center' nowrap>
		<input type='text' name='data_nf_mobra' id='data_nf_mobra' rel='data' size='10' maxlength='6' onfocus="javascript: nextfield='data_nf_mobra' ">
	</td>
	<td align='center' nowrap>
		<input type='text' name='valor_mobra' size='10' maxlength='10' onfocus="javascript: nextfield='nf_devolucao' ">
	</td>
	<td align='center' nowrap>
		<input type='text' name='data_recebimento_lote' id='data_recebimento_lote' rel='data' size='10' maxlength='6' onfocus="javascript: nextfield='data_recebimento_lote' ">
	</td>
	<td align='center' nowrap>
		<input type='text' name='identificador_objeto' id='identificador_objeto'  size='10' maxlength='13' >
	</td>
</tr>
</table>


<table border='0' width='700' cellpadding='3'>
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
	<td nowrap>
		Total Sedex
	</td>
</tr>

<tr>
	<td align='center' nowrap>
		<input type='text' name='nf_devolucao' size='8' maxlength='8' onfocus="javascript: nextfield='valor_devolucao' ">
	</td>
	<td align='center' nowrap>
		<input type='text' name='valor_devolucao' size='10' maxlength='10' onfocus="javascript: nextfield='icms_devolucao' ">
	</td>
	<td align='center' nowrap>
		<input type='text' name='icms_devolucao' size='10' maxlength='10' onfocus="javascript: nextfield='total_sedex' ">
	</td>
	<td align='center' nowrap>
		<input type='text' name='total_sedex' size='10' maxlength='10' onfocus="javascript: nextfield='sua_os_0' ">
	</td>
</tr>
<tr>
	<td colspan='4' align='center'bgcolor='#CCCCFF'>
		Observação
	</td>
</tr>

<tr>
	<td align='center' colspan='4'>
		<TEXTAREA NAME="obs" ROWS="5" COLS="50" onfocus="javascript: nextfield='' "></TEXTAREA>
	</td>
</tr>
</table>


<table width='700' border='1' cellpadding='1' cellspacing='0'>
<tr bgcolor='#CCCCFF'>
	<td align='center' colspan='2'><b>O.S.</b></td>
	<td align='center' nowrap><b>Nota Fiscal</b></td>
	<td align='center'><b>Data Digitação</b></td>
	<td align='center'><b>Data NF</b></td>
	<td align='center'><b>Certificado Garantia</b></td>
	<td align='center' width='200'><b>SÉRIE</b></td>
	<td align='center' width='400'><b>OBS</b></td>
</tr>

<?
$getLinhas = $_GET['linhas'];

if ($getLinhas==""){
	$getLinhas = 20;
}

if(strlen($extrato) > 0){
	$sql2 = "SELECT sua_os, os FROM tbl_os_extra
				JOIN tbl_os USING(os) WHERE extrato = $extrato";
	$sql2 = "SELECT tbl_os.sua_os, tbl_os.os, to_char(tbl_os.data_nf,'DD/MM/YYYY') as data_nf,
				to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as data_digitacao,
					tbl_os.nota_fiscal,tbl_os.serie,tbl_os.certificado_garantia
				FROM tbl_os_extra
				JOIN tbl_os USING(os) WHERE extrato = $extrato
				ORDER BY    tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
							replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC ";
	$res2 = pg_exec($con,$sql2);
	$total_extrato = pg_numrows($res2);
}

if($total_extrato > 0)
    $getLinhas = $total_extrato;

for ($i = 0 ; $i < $getLinhas ; $i++) {
	$proximo = "sua_os_" . ($i + 1) ;

	if (strlen($msg_erro) > 0) {
		$sua_os          = $_POST['sua_os_' . $i];
		$revendedor      = $_POST['revendedor_' . $i];
		$data_nf         = $_POST['data_nf_' . $i];
		$nota_fiscal     = $_POST['nota_fiscal_' . $i];
	}



	if($total_extrato-1 >= $i ){
		$sua_os					= pg_fetch_result($res2,$i,'sua_os');
		$os						= pg_fetch_result($res2,$i,'os');
		$data_nf				= pg_fetch_result($res2,$i,'data_nf');
		$certificado_garantia	= pg_fetch_result($res2,$i,'certificado_garantia');
		$nota_fiscal			= pg_fetch_result($res2,$i,'nota_fiscal');
		$serie					= pg_fetch_result($res2,$i,'serie');
		$data_digitacao			= pg_fetch_result($res2,$i,'data_digitacao');
	}


	echo "<tr align='center'>";

	echo "<td><SPAN style='width:1px;color:white;white-space:nowrap;overflow:hidden;'>$sua_os</SPAN><INPUT TYPE='text' name='sua_os_$i' id='sua_os_$i' size='12' value='$sua_os' READONLY><INPUT TYPE='hidden' NAME='os_$i' value='$os'>
	</td>
	<td>
	<div id='recusar2_$i'><span onClick=\"javascript: ajax_conferencia ('$revendedor', '$sua_os', '$data_nf', '$nota_fiscal', '$obs', '$posto', '$i', '$os')\"><FONT SIZE='-3' COLOR='#330000'><b style='cursor:pointer; cursor:hand;'>recusar</div></span></td>";
	$sua_os = '';
	echo "<td><INPUT TYPE='text' NAME='nota_fiscal_$i' VALUE='$nota_fiscal' size='10' maxlength='10'></td>";
	echo "<td><INPUT TYPE='text' rel='data' NAME='data_digitacao_$i' VALUE='$data_digitacao' size='10' maxlength='10' onKeyUp=\"formata_data(this.value,'frm_lote', 'data_digitacao_$i')\" ></td>";
	echo "<td><INPUT TYPE='text' rel='data' NAME='data_nf_$i' VALUE='$data_nf' size='10' maxlength='10' onKeyUp=\"formata_data(this.value,'frm_lote', 'data_nf_$i')\" ></td>";
	//echo "<td><input type='text' name='certificado_garantia_$i' value='$certificado_garantia' size='6' maxlength='6'></td>";
	echo "<td>$certificado_garantia</td>";
	echo "<td><INPUT TYPE='text' NAME='serie_$i' VALUE='$serie' size='10' maxlength='10'  onblur=\"javascript: ajax_sua_os (this.value, document.frm_lote.sua_os_$i.value , document.frm_lote.data_nf_$i.value , document.frm_lote.nota_fiscal_$i.value, document.getElementById('obs_$i'), document.frm_lote.posto.value, '$i')\"></td>";
/*	echo "<!--<td><INPUT TYPE='text' NAME='revendedor_$i' VALUE='$revendedor' size='20' maxlength='14' onfocus=\"javascript: nextfield='$proximo' \" onblur=\"javascript: ajax_sua_os (this.value, document.frm_lote.sua_os_$i.value , document.frm_lote.data_nf_$i.value , document.frm_lote.nota_fiscal_$i.value, document.getElementById('obs_$i'), document.frm_lote.posto.value, '$i') \"></td>-->"; */
	echo "<td><span id='obs_$i'></span></td>";
	echo "</tr>";

	unset($sua_os, $os, $data_nf, $nota_fiscal, $certificado_garantia, $serie, $data_digitacao);


}

echo "</table>";

?>
<!--
<br><label id='lista_referencias'>OS EM LOTE</label><br><textarea name='lista_os' cols='10' rows='10'></textarea>
<br><input type='button' name='btn_lote' value='Importar Lote' onclick='importarLote()'>
<br>
-->
<br>
<input type='hidden' name='qtde_item' value='<?= $i ?>'>
<input type="hidden" name="btn_acao" value="">
<p align="CENTER">
<img src='imagens/btn_gravar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_lote.btn_acao.value == '' ) { document.frm_lote.btn_acao.value='Conferir Lote' ; document.frm_lote.submit(); } else { document.frm_lote.submit(); }" ALT="Conferir Lote" border='0'>
</p>
</form>

</td></tr>
</table>
<? include "rodape.php"; ?>

</body>
</html>
