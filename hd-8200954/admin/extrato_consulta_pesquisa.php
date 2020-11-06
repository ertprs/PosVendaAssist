<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$admin_privilegios="financeiro";
include "autentica_admin.php";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}


if($ajax=='conta'){
			$sql = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
			$rres = pg_exec($con,$sql);
			if(pg_numrows($rres)>0){
				$qtde_os = pg_result($rres,0,qtde_os);
			}
			echo "ok|$qtde_os";
			exit;
}
// AJAX -> solicita a exportação dos extratos
if (strlen($_GET["exportar"])>0){
	//include "../ajax_cabecalho.php";
	//system("/www/cgi-bin/bosch/exporta-extrato.pl",$ret);
	$dados = "$login_fabrica\t$login_admin\t".date("d-m-Y H:m:s");
	exec ("echo '$dados' > /tmp/bosch/exporta/pronto.txt");
	echo "ok|Exportação concluída com sucesso! Dentro de alguns minutos os arquivos de exportação estarão disponíveis no sistema.";
	exit;
}
// FIM DO AJAX -> solicita a exportação dos extratos


// AJAX -> APROVA O EXTRATO SELECIONADO
if ($_GET["ajax"] == "APROVAR" && strlen($_GET["aprovar"])>0 && strlen($_GET["posto"])>0){
	$posto   = $_GET["posto"];
	$aprovar = $_GET["aprovar"];

	$res = pg_exec($con,"BEGIN TRANSACTION");

	if ($login_fabrica==20) {
		$nf_mao_de_obra = $_GET["nf_mao_de_obra"];
		if (strlen(trim($nf_mao_de_obra))==0) {
			$nf_mao_de_obra = 'null';
		}
	
		$nf_devolucao   = $_GET["nf_devolucao"];
		if (strlen(trim($nf_devolucao))==0) {
			$nf_devolucao = 'null';
		}

		$sql = "UPDATE tbl_extrato_extra 
				SET nota_fiscal_mao_de_obra = '$nf_mao_de_obra',
				nota_fiscal_devolucao       = '$nf_devolucao'
				WHERE extrato = $aprovar";
		#$res = pg_exec($con,$sql); 
		# Estava comentado , entao descomentei. Pq comentaram?  Não tem a explicacao.
		# Estou liberando. HD 4846
	}

	$sql = "SELECT fn_aprova_extrato($posto,$login_fabrica,$aprovar)";
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo "ok;$aprovar";
	}else{
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";
	}
	exit;
}
// FIM DO AJAX -> APROVA O EXTRATO SELECIONADO

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

if (strlen($_GET["liberar"]) > 0) $liberar = $_GET["liberar"];

if (strlen($liberar) > 0){

	if($login_fabrica ==11){
		$sql="SELECT recalculo_pendente 
				from tbl_extrato
				where extrato=$liberar
				and fabrica=$login_fabrica";
		$res = @pg_exec($con,$sql);
		$recalculo_pendente=pg_result($res,0,recalculo_pendente);
		if($recalculo_pendente=='t'){
			$msg_erro="Este extrato será recalculado de noite e poderá ser liberado amanhã";	
		}
	}
	if (strlen($msg_erro)==0){

		$res = pg_exec($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_extrato SET liberado = current_date";
				if($login_fabrica == 6 OR $login_fabrica == 11 OR $login_fabrica == 24 OR $login_fabrica == 14 OR $login_fabrica == 35 OR $login_fabrica == 45 OR $login_fabrica == 15){ $sql .= ", aprovado = current_date";}
					$sql .= " WHERE extrato = $liberar";
		$res = @pg_exec($con,$sql);
		$msg_erro = @pg_errormessage($con);

		//Wellington 14/12/2006 - ENVIA EMAIL PARA O POSTO QDO O EXTRATO É LIBERADO
		if (strlen($msg_erro)==0 and $login_fabrica==11) {
			$sql = "SELECT email, posto FROM tbl_posto 
					JOIN tbl_extrato USING(posto) 
					WHERE extrato = $liberar";
			$res = @pg_exec($con,$sql);

			$xemail = trim(pg_result($res,0,email));
			$xposto = trim(pg_result($res,0,posto));
			if (strlen($xemail) > 0) {
				if (strlen($_GET["msg_aviso"])  > 0) $msg_aviso = "AVISO: ".$_GET["msg_aviso"]."<BR><BR><BR>";
				elseif (strlen($_POST["msg_aviso"])  > 0) $msg_aviso = "AVISO: ".$_POST["msg_aviso"]."<BR><BR><BR>";
				$remetente    = "LENOXXSOUND FINANCEIRO <luiz@lenoxxsound.com.br>"; 
				$destinatario = $xemail; 
				$assunto      = "SEU EXTRATO FOI LIBERADO"; 
				$mensagem     =  "* O EXTRATO Nº".$liberar." ESTÁ LIBERADO NO SITE: www.telecontrol.com.br *<br><br>".$msg_aviso ; 
				$headers="Return-Path: <luiz@lenoxxsound.com.br>\nFrom:".$remetente."\nBcc:luiz@lenoxxsound.com.br \nContent-type: text/html\n"; 
				
				if ( @mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers) ) {
				}else{
					$remetente    = "MERCURIO FINANCEIRO <wellington@telecontrol.com.br>"; 
					$destinatario = "wellington@telecontrol.com.br"; 
					$assunto      = "EMAIL NÃO ENVIADO (SEU EXTRATO FOI LIBERADO)"; 
					$mensagem     = "* NÃO ENVIADO PARA O POSTO ".$xemail." *"; 
					$headers="Return-Path: <wellington@telecontrol.com.br>\nFrom: ".$remetente."\nContent-type: text/html\n"; 
					
					@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
				}
			}
		}

		//Samuel 02/01/2007 - ENVIA EMAIL PARA O POSTO QDO O EXTRATO É LIBERADO
		if (strlen($msg_erro)==0 and $login_fabrica==24) {
			$sql = "SELECT email, posto FROM tbl_posto 
					JOIN tbl_extrato USING(posto) 
					WHERE extrato = $liberar";
			$res = @pg_exec($con,$sql);

			$xemail = trim(pg_result($res,0,email));
	//		$xposto = trim(pg_resulst($res,0,posto));
			$xposto = trim(pg_result($res,0,posto));
			if (strlen($xemail) > 0) {
				$remetente    = "SUGGAR FINANCEIRO <suggat@suggar.com.br>"; 
				$destinatario = $xemail; 
				$assunto      = "SEU EXTRATO FOI LIBERADO"; 
				$mensagem     = "* O EXTRATO Nº".$liberar." ESTÁ LIBERADO NO SITE: www.telecontrol.com.br *"; 
				$headers="Return-Path: <suggat@suggar.com.br>\nFrom:".$remetente."\nBcc:marilene@suggar.com.br,helpdesk@telecontrol.com.br \nContent-type: text/html\n"; 
				
				if ( @mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers) ) {
				}else{
					$remetente    = "MERCURIO FINANCEIRO <helpdesk@telecontrol.com.br>"; 
					$destinatario = "helpdesk@telecontrol.com.br"; 
					$assunto      = "EMAIL NÃO ENVIADO (SEU EXTRATO FOI LIBERADO)"; 
					$mensagem     = "* NÃO ENVIADO PARA O POSTO ".$xemail." *"; 
					$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom: ".$remetente."\nContent-type: text/html\n"; 
					
					@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
				}
			}
		}

		//wellington liberar
		// Fabio 02/10/2007
		// Alterado por Fabio -> tbl_faturamento.emissao <  '2007-10-21' // HD 600
		// Depois da liberação, alterar para tbl_faturamento.emissao < current_date - interval'15 day'
		/* LENOXX - SETA EXTRATO DE DEVOLUÇÃO PARA OS FATURAMENTOS */
		if (strlen($liberar) > 0 and strlen($msg_erro)==0 and $login_fabrica==11) {

			$sql = "SELECT TO_CHAR(data_geracao-interval '1 month','YYYY-MM-21') AS data_limite
					FROM tbl_extrato 
					WHERE extrato = $extrato;";
			# HD 281159 (60 dias da data de geração do extrato)
			$sql = "SELECT TO_CHAR(data_geracao-interval '2 month','YYYY-MM-DD') AS data_limite
					FROM tbl_extrato 
					WHERE extrato = $extrato;";
			$res = pg_exec($con,$sql);
			$data_limite_nf = trim(pg_result($res,0,data_limite));

			$sql = "UPDATE tbl_faturamento SET extrato_devolucao = $liberar
					WHERE  tbl_faturamento.fabrica = $login_fabrica
					AND    tbl_faturamento.posto   = $xposto
					AND    tbl_faturamento.extrato_devolucao IS NULL
					AND    tbl_faturamento.emissao > '2007-08-30'
					AND    tbl_faturamento.emissao < '$data_limite_nf'
					AND    (tbl_faturamento.cfop ILIKE '%59%' OR tbl_faturamento.cfop ILIKE '%69%')
					";
			// AND    tbl_faturamento.emissao <  current_date - interval'15 day'
			$res = pg_exec($con,$sql);

			$sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = $liberar";
			$res = pg_exec($con,$sql);

			$sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde) (
				SELECT tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde) 
				FROM tbl_extrato 
				JOIN tbl_faturamento      ON tbl_extrato.extrato         = tbl_faturamento.extrato_devolucao
				JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.extrato = $liberar
				GROUP BY tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca 
				) ;";
			$res = pg_exec($con,$sql);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

/*
//alterado takashi 06/07/2006 a pedido da angelica
if ($btnacao == 'liberar_tudo'){
	if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];
	
	for ($i=0; $i < $total_postos; $i++) {
		$extrato = $_POST["liberar_".$i];
		if (strlen($extrato) > 0) {
			$sql = "UPDATE tbl_extrato SET liberado = current_date
					WHERE  tbl_extrato.extrato = $extrato
					and    tbl_extrato.fabrica = $login_fabrica";
			$res = @pg_exec($con,$sql);
			$msg_erro = @pg_errormessage($con);
		}
	}
}

*/
//takashi 06/07/2006
//angelica e tectoy com problemas de liberação de extratos, quer que qdo extrato liberado, já seja aprovado
//coloquei um if fabrica 6 para setar aprovado com a data tambem
if ($btnacao == 'liberar_tudo'){
	if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];
	
	$sql = "begin";
	$res = @pg_exec($con,$sql);

	for ($i=0; $i < $total_postos; $i++) {
		$extrato    = $_POST["liberar_".$i];
		$imprime_os = $_POST["imprime_os_".$i];
		if (strlen($extrato) > 0 AND strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_extrato SET liberado = current_date ";
			if($login_fabrica == 6 OR $login_fabrica == 11 OR $login_fabrica == 24 OR $login_fabrica == 14 OR $login_fabrica == 35 OR $login_fabrica == 45 OR $login_fabrica == 15){ 
				$sql .= ", aprovado = current_date ";
			}
			
			$sql .= "WHERE  tbl_extrato.extrato = $extrato
					 and    tbl_extrato.fabrica = $login_fabrica";
					 //echo $sql;
			$res = pg_exec($con,$sql);
			$msg_erro = @pg_errormessage($con);

			//Wellington 14/12/2006 - ENVIA EMAIL PARA O POSTO QDO O EXTRATO É LIBERADO
			if (strlen($msg_erro)==0 and $login_fabrica==11) {
				$sql = "SELECT email, posto FROM tbl_posto 
						JOIN tbl_extrato USING(posto) 
						WHERE extrato = $extrato";
				$res = @pg_exec($con,$sql);

				$xemail = trim(pg_result($res,0,email));
				$xposto = trim(pg_result($res,0,posto));
				if (strlen($xemail) > 0) {
					if (strlen($_GET["msg_aviso"])  > 0) $msg_aviso = "AVISO: ".$_GET["msg_aviso"]."<BR>";
					elseif (strlen($_POST["msg_aviso"])  > 0) $msg_aviso = "AVISO: ".$_POST["msg_aviso"]."<BR><BR><BR>";
					$remetente    = "LENOXXSOUND FINANCEIRO <luiz@lenoxxsound.com.br>"; 
					$destinatario = $xemail; 
					$assunto      = "SEU EXTRATO FOI LIBERADO"; 
					$mensagem     = "* O EXTRATO Nº".$extrato." ESTÁ LIBERADO NO SITE: www.telecontrol.com.br * <br><br>".$msg_aviso ; 
					$headers="Return-Path: <luiz@lenoxxsound.com.br>\nFrom:".$remetente."\nBcc:luiz@lenoxxsound.com.br\nContent-type: text/html\n"; 
					
					if ( @mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers) ) {
					}else{
						$remetente    = "MERCURIO FINANCEIRO <wellington@telecontrol.com.br>"; 
						$destinatario = "wellington@telecontrol.com.br"; 
						$assunto      = "EMAIL NÃO ENVIADO (SEU EXTRATO FOI LIBERADO)"; 
						$mensagem     = "* NÃO ENVIADO PARA O POSTO ".$xemail." *"; 
						$headers="Return-Path: <wellington@telecontrol.com.br>\nFrom: ".$remetente."\nContent-type: text/html\n"; 
						
						@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
					}
				}
			}
		}

		//wellington liberar
		/* LENOXX - SETA EXTRATO DE DEVOLUÇÃO PARA OS FATURAMENTOS */
		if (strlen($extrato) > 0 and strlen($msg_erro)==0 and $login_fabrica==11) {

			$sql = "SELECT TO_CHAR(data_geracao-interval '1 month','YYYY-MM-21') AS data_limite
					FROM tbl_extrato 
					WHERE extrato = $extrato;";
			$res = pg_exec($con,$sql);
			$data_limite_nf = trim(pg_result($res,0,data_limite));

			$sql = "UPDATE tbl_faturamento SET extrato_devolucao = $extrato
					WHERE  tbl_faturamento.fabrica = $login_fabrica
					AND    tbl_faturamento.posto   = $xposto
					AND    tbl_faturamento.extrato_devolucao IS NULL
					AND    tbl_faturamento.emissao >  '2007-08-30'
					AND    tbl_faturamento.emissao < '$data_limite_nf'
					AND    (tbl_faturamento.cfop ILIKE '%59%' OR tbl_faturamento.cfop ILIKE '%69%')
					";
			$res = pg_exec($con,$sql);

			$sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde) (
				SELECT tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde) 
				FROM tbl_extrato 
				JOIN tbl_faturamento      ON tbl_extrato.extrato         = tbl_faturamento.extrato_devolucao
				JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.extrato = $extrato
				GROUP BY tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca 
				) ;";
			$res = pg_exec($con,$sql);
		}
	}
	//HD 12104
	if($login_fabrica==14 and strlen($imprime_os) > 0){
		$sql =" UPDATE tbl_posto_fabrica set imprime_os ='t'
					FROM tbl_extrato
					WHERE tbl_extrato.posto=tbl_posto_fabrica.posto
					AND extrato=$imprime_os
					AND tbl_posto_fabrica.fabrica=$login_fabrica ";
		$res=pg_exec($con,$sql);
	}

	if (strlen($msg_erro) == 0)
		$sql = "commit";
	else
		$sql = "rollback";
	$res = @pg_exec($con,$sql);

}
//takashi 06/07/2006


if ($btnacao == "acumular_tudo") {
	if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];

	$res = pg_exec($con,"BEGIN TRANSACTION");

	for ($i = 0 ; $i < $total_postos ; $i++) {
		$extrato = $_POST["acumular_" . $i];

		if (strlen($extrato) > 0) {
			$sql = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
		if (strlen($msg_erro) > 0) break;
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($_GET["aprovar"]) > 0) $aprovar = $_GET["aprovar"]; // é o numero do extrato

if (strlen($aprovar) > 0){
	//if ($login_fabrica == 1){

	//atualiza campos de notas fiscais
	if ($login_fabrica==20) {
		$nf_mao_de_obra = $_GET["nf_mao_de_obra"];
		if (strlen(trim($nf_mao_de_obra))==0) {
			$nf_mao_de_obra = 'null';
		}
	
		$nf_devolucao   = $_GET["nf_devolucao"];
		if (strlen(trim($nf_devolucao))==0) {
			$nf_devolucao = 'null';
		}

		$sql = "UPDATE tbl_extrato_extra 
				SET nota_fiscal_mao_de_obra = '$nf_mao_de_obra',
				nota_fiscal_devolucao       = '$nf_devolucao'
				WHERE extrato = $aprovar";
		$res = pg_exec($con,$sql); 
		#  HD 4846 - Colocado!

	}

		$sql = "SELECT fn_aprova_extrato($posto,$login_fabrica,$aprovar)";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		//Raphael HD-1260 Retirar liberação
		/*if($login_fabrica==20){
			//A OS QUANDO É APROVADA PARA A BOSCH ELA É AUTOMATICAMENTE LIBERADA TAMBÉM
			$sql = "UPDATE tbl_extrato set liberado = aprovado 
					WHERE  posto   = $posto 
					AND    fabrica = $login_fabrica
					AND    extrato = $aprovar";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}*/
	//}
}
$layout_menu = "financeiro";
$title = "Consulta de Extratos";

include "cabecalho.php";



//hd 15622
//if ($login_fabrica==11) {
//	echo "<BR><BR><BR><BR><BR><CENTER>Programa em manutenção, aguarde alguns instantes.</CENTER>";
//	exit;
//}
?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
;
	background-color: #D9E2EF
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.quadro{
	border: 1px solid #596D9B;
	width:450px;
	height:50px;
	padding:10px;
	
}

.botao {
		border-top: 1px solid #333;
	        border-left: 1px solid #333;
	        border-bottom: 1px solid #333;
	        border-right: 1px solid #333;
	        font-size: 13px;
	        margin-bottom: 10px;
	        color: #0E0659;
		font-weight: bolder;
}

.texto_padrao {
	        font-size: 12px;
}
</style>

<script language='javascript' src='../ajax.js'></script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[2];
	}
	
	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>
<script language="JavaScript">

/* ============= Função PESQUISA DE POSTOS ====================
Nome da Função : fnc_pesquisa_posto (cnpj,nome)
		Abre janela com resultado da pesquisa de Postos pela
		Código ou CNPJ (cnpj) ou Razão Social (nome).
=================================================================*/

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}
	
	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}
}




var checkflag = "false";
function check(field) {
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

function AbrirJanelaObs (extrato) {
	var largura  = 400;
	var tamanho  = 250;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
function gerarExportacao(but){
	 if (but.value == 'Exportar Extratos' ) {
		if (confirm('Deseja realmente prosseguir com a exportação?\n\nSerá exportado somente os extratos aprovados e liberados.')){
			but.value='Exportando...';
			exportar();
		}
	} else {
		 alert ('Aguarde submissão');
	}

}

function retornaExporta(http) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					alert(results[1]);
				}else{
					alert (results[1]);
				}
			}else{
				alert ("Não existe extratos a serem exportados.");
			}
		}
	}
}

function exportar() {
	url = "<?= $PHP_SELF ?>?exportar=sim";
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaExporta(http) ; } ;
	http.send(null);
}
</script>


<script language='javascript'>

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
			
var http_data = new Array();
var semafaro=0;

function aprovaExtrato (extrato , posto, aprovar, novo,adicionar,acumular,resposta ) {

	if (semafaro == 1){
		alert('Aguarde alguns instantes antes de aprovar outro extrato.');
		return;
	}

	if (confirm('Deseja aprovar este extrato?')==false){
		return;
	}

	var curDateTime = new Date();
	semafaro = 1;
	url = "<?= $PHP_SELF ?>?ajax=APROVAR&aprovar=" + escape(extrato)+ "&posto=" + escape(posto)+"&data="+curDateTime;

	aprovar   = document.getElementById(aprovar);
	novo      = document.getElementById(novo);
	adicionar = document.getElementById(adicionar);
	acumular  = document.getElementById(acumular);
	resposta  = document.getElementById(resposta);

	http_data[curDateTime] = createRequestObject();
	http_data[curDateTime].open('POST',url,true);
	
	http_data[curDateTime].onreadystatechange = function(){
		if (http_data[curDateTime].readyState == 4){
			if (http_data[curDateTime].status == 200 || http_data[curDateTime].status == 304){

			var response = http_data[curDateTime].responseText.split(";");
				
				if (response[0]=="ok"){
					if (aprovar)   aprovar.src   = '/assist/imagens/pixel.gif';
					if (novo)      novo.src      = '/assist/imagens/pixel.gif';
					if (adicionar) adicionar.src = '/assist/imagens/pixel.gif';
					if (acumular)  {acumular.disabled = true; acumular.style.visibility = "hidden";}
					if (resposta)  resposta.innerHTML = "Aprovado";
				}else{
					alert('Extrato não foi aprovado. Tente novamente.');
				}
				semafaro = 0;
			}
		}
	}
	http_data[curDateTime].setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=iso-8859-1");
	http_data[curDateTime].setRequestHeader("Cache-Control", "no-store, no-cache, must-revalidate");
	http_data[curDateTime].setRequestHeader("Cache-Control", "post-check=0, pre-check=0");
	http_data[curDateTime].setRequestHeader("Pragma", "no-cache");
	http_data[curDateTime].send('');
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
			
var http_forn = new Array();

function conta_os(extrato,div) {
	var ref = document.getElementById(div);
	ref.innerHTML = "Espere...";
	url = "<?=$PHP_SELF?>?ajax=conta&extrato="+extrato;
	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
						ref.innerHTML = response[1];
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}

</script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<?
if (strlen($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' class='error'>\n";
	echo "<tr>";
	echo "<td>$msg_erro</td>";
	echo "</tr>";
	echo "</table>\n";
}


$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];

$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];


if (strlen($_GET['extrato']) > 0) $extrato = $_GET['extrato'];
if (strlen($_POST['extrato']) > 0) $extrato = $_POST['extrato'];


echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<TABLE width='600' align='center' border='0' cellspacing='3' cellpadding='2'>\n";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "		Digite o Número do extrato";
echo "	</TD>";
echo "	<TR>\n";

echo "<TR>\n";
echo "	<TD COLSPAN= '4' ALIGN='center'>";
echo "	Nº de extrato ";
echo "	<input type='text' name='extrato' size='12' value='$extrato' class='frm'>&nbsp;";
echo "	</TD>\n";
echo "</TR>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "		Consultar postos com extratos fechados entre";
echo "	</TD>";
echo "<TR>\n";

echo "<TR>\n";
echo "	<TD width='25%'>";
echo "	</TD>";
echo "	<TD ALIGN='left'>";
echo "	Data Inicial ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' value='$data_inicial' class='frm'>\n";
echo "	</TD>\n";

echo "	<TD ALIGN='left'>";
echo "	Data Final ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' id='data_final' value='$data_final' class='frm'>\n";
echo "</TD>\n";
echo "	<TD width='25%'>";
echo "	</TD>";
echo "</TR>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "		Somente extratos do posto";
echo "	</TD>";
echo "<TR>\n";

echo "<TR >\n";
echo "	<TD COLSPAN='4' ALIGN='center' nowrap>";
echo "Código";
echo "		<input type='text' name='posto_codigo' id='posto_codigo' size='10' value='$posto_codigo' class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'cnpj')\">";

echo "&nbsp;&nbsp;Razão Social ";
echo "		<input type='text' name='posto_nome' id='posto_nome' size='45' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "<TR>\n";

if($login_fabrica == 20){
// MLG 2009-08-04 HD 136625
    $sql = 'SELECT pais,nome FROM tbl_pais';
    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
    	$sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
?>
	<tr class="Conteudo" bgcolor="#D9E2EF" >
		<td colspan='4' align='center'>País
			<select name='pais' size='1' class='frm'>
			 <option></option>
            <?echo $sel_paises;?>
			</select>
		</td>
	</tr>
<?}

echo "</TABLE>\n";

echo "<br><img src=\"imagens_admin/btn_filtrar.gif\" onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' style=\"cursor:pointer;\">\n";

echo "</form>";


// INICIO DA SQL
$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];


$data_inicial = str_replace (" " , "" , $data_inicial);
$data_inicial = str_replace ("-" , "" , $data_inicial);
$data_inicial = str_replace ("/" , "" , $data_inicial);
$data_inicial = str_replace ("." , "" , $data_inicial);


$data_final = str_replace (" " , "" , $data_final);
$data_final = str_replace ("-" , "" , $data_final);
$data_final = str_replace ("/" , "" , $data_final);
$data_final = str_replace ("." , "" , $data_final);


if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

if (strlen ($data_inicial) > 0) $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
if (strlen ($data_final)   > 0) $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);


$pais = $_POST['pais'];
if (strlen($_GET['pais']) > 0) $pais = $_GET['pais'];

$cond_extrato="";
if(strlen($extrato)>0){
	if($login_fabrica <> 1 AND $login_fabrica <> 19 ){
		$cond_extrato=" AND EX.extrato = $extrato";
	}else{
		$cond_extrato=" AND EX.protocolo = $extrato";
	}
}

if (strlen ($posto_codigo) > 0 OR (strlen ($data_inicial) > 0 and strlen ($data_final) > 0) OR strlen($extrato) > 0 ) {

	if ($login_fabrica == 1) $add_1 = " AND       EX.aprovado IS NULL ";

	//--== INICIO - Consulta por data ===============================================
	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
	$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
	$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0 AND strlen($extrato) == 0) 
		$add_2 = " AND      EX.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	//--== FIM - Consulta por data ==================================================

	//--== INICIO - Consulta por data ===============================================
	$xposto_codigo = str_replace (" " , "" , $posto_codigo);
	$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("." , "" , $xposto_codigo);

	if (strlen ($posto_codigo) > 0 OR strlen ($posto_nome) > 0 ){
		$sql = "SELECT posto 
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE fabrica = $login_fabrica ";
		if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto_fabrica.codigo_posto = '$xposto_codigo' ";
		if (strlen ($posto_nome) > 0 )   $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";
//		echo $sql;
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			$posto = pg_result($res,0,0);
			$add_3 = " AND EX.posto = $posto " ;
		}else{
			$add_3 = " AND 1=2 " ;
		}
	}
	//--== FIM - Consulta por Posto ==============================================

	if($login_fabrica == 20) $add_4 = " AND PO.pais = '$pais' ";

	$sql = "SELECT DISTINCT
					PO.posto                                                 ,
					PO.nome                                                  ,
					PO.cnpj                                                  ,
					PO.email                                                 ,
					PF.codigo_posto                                          ,
					PF.distribuidor                                          ,
					PF.imprime_os                                            ,
					TP.descricao                             AS tipo_posto   ,
					EX.extrato                                               ,
					EX.bloqueado                                             ,
					EX.liberado                                              ,
					EX.estoque_menor_20                                      ,
					TO_CHAR (EX.aprovado,'dd/mm/yyyy')       AS aprovado     ,
					LPAD (EX.protocolo,6,'0')                AS protocolo    ,
					TO_CHAR (EX.data_geracao,'dd/mm/yyyy')   AS data_geracao ,
					EX.data_geracao                          AS xdata_geracao,
					EX.total                                                 ,
					EX.pecas                                                 ,
					EX.mao_de_obra                                           ,
					EX.avulso                                                ,
					EX.recalculo_pendente                                    ,
					TO_CHAR (EP.data_pagamento,'dd/mm/yyyy') AS baixado      ,
					EP.valor_liquido                                         ,
					EE.nota_fiscal_devolucao                                 ,
					EE.nota_fiscal_mao_de_obra
			FROM      tbl_extrato           EX
			JOIN      tbl_posto             PO USING (posto)
			JOIN      tbl_posto_fabrica     PF ON EX.posto      = PF.posto      AND PF.fabrica = $login_fabrica
			JOIN      tbl_tipo_posto        TP ON TP.tipo_posto = PF.tipo_posto AND TP.fabrica = $login_fabrica
			LEFT JOIN tbl_os_extra          OE ON OE.extrato    = EX.extrato
			LEFT JOIN tbl_extrato_pagamento EP ON EX.extrato    = EP.extrato
			LEFT JOIN tbl_extrato_extra     EE ON EX.extrato    = EE.extrato
			WHERE     EX.fabrica = $login_fabrica
			AND       PF.distribuidor IS NULL 
			$cond_extrato
			$add_1 
			$add_2 
			$add_3
			$add_4";
	if ($login_fabrica <> 1) $sql .= " ORDER BY PO.nome, EX.data_geracao";
	else                     $sql .= " ORDER BY PF.codigo_posto, EX.data_geracao";
//echo $sql;exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}
	if (pg_numrows ($res) > 0) {

		$legenda_avulso="";
		echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b>$legenda_avulso</font></td>";
		echo "</tr>";
		echo "</table>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto                   = trim(pg_result($res,$i,posto));
			$codigo_posto            = trim(pg_result($res,$i,codigo_posto));
			$nome                    = trim(pg_result($res,$i,nome));
			$email                   = trim(pg_result($res,$i,email));
			$tipo_posto              = trim(pg_result($res,$i,tipo_posto));
			$extrato                 = trim(pg_result($res,$i,extrato));
			$data_geracao            = trim(pg_result($res,$i,data_geracao));
			//$qtde_os                 = trim(pg_result($res,$i,qtde_os));
			$total                   = trim(pg_result($res,$i,total));
			$baixado                 = trim(pg_result($res,$i,baixado));
			$extrato                 = trim(pg_result($res,$i,extrato));
			$distribuidor            = trim(pg_result($res,$i,distribuidor));
			$xtotal                  = round($total);
			$total	                 = number_format ($total,2,',','.');
			$liberado                = trim(pg_result($res,$i,liberado));
			$aprovado                = trim(pg_result($res,$i,aprovado));
			$estoque_menor_20        = trim(pg_result($res,$i,estoque_menor_20));
			$protocolo               = trim(pg_result($res,$i,protocolo));
			$nota_fiscal_devolucao   = trim(pg_result($res,$i,nota_fiscal_devolucao));
			$nota_fiscal_mao_de_obra = trim(pg_result($res,$i,nota_fiscal_mao_de_obra));
			$xdata_geracao           = trim(pg_result($res,$i,xdata_geracao));
			$bloqueado               = trim(pg_result($res,$i,bloqueado));
			$recalculo_pendente      = trim(pg_result($res,$i,recalculo_pendente));
			$pecas                   = trim(pg_result($res,$i,pecas));
			$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra));
			$avulso                  = trim(pg_result($res,$i,avulso));

			$pecas       = number_format($pecas,2,',','.');
			$mao_de_obra = number_format($mao_de_obra,2,',','.');
			$avulso      = number_format($avulso,2,',','.');

			if (trim(pg_result($res,$i,valor_liquido)) <> '') {
				$valor_liquido = number_format (trim(pg_result($res,$i,valor_liquido)),2,',','.');
			}else{
				$valor_liquido = number_format (trim(pg_result($res,$i,total)),2,',','.');
			}

			if ($i == 0) {
				echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
				echo "<input type='hidden' name='btnacao' value=''>";
				echo "<table width='700' align='center' border='0' cellspacing='2'>\n";
				echo "<tr class = 'menu_top'>\n";
				echo "<td align='center'>Código</td>\n";
				echo "<td align='center' nowrap>Nome do Posto</td>\n";
				if ($login_fabrica == 1 OR $login_fabrica == 19) {
					echo "<td align='center'>Protocolo</td>\n";
				} else {
					echo "<td align='center'>Extrato</td>\n";
				}
				echo "<td align='center'>Data</td>\n";
				echo "<td align='center' nowrap>Qtde. OS</td>\n";
				echo "<td align='center'>Total</td>\n";
				if ($login_fabrica == 6) {//hd 3471
					echo "<td align='center'><acronym title='Média de valor pago nos últimos 6 meses' style='cursor: help;'>Média</td>\n";
				}
				// SONO - 04/09/206 exibir valor_liquido para intelbras //
				if ($login_fabrica == 14) {
					echo "<td align='center' nowrap>Total Líquido</td>\n";
				}
				echo "</tr>\n";
			}

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
			if (strlen($extrato) > 0) {
				$sql = "SELECT count(*) as existe
						FROM   tbl_extrato_lancamento
						WHERE  extrato = $extrato
						and    fabrica = $login_fabrica";
				$res_avulso = pg_exec($con,$sql);

				if (@pg_numrows($res_avulso) > 0) {
					if (@pg_result($res_avulso, 0, existe) > 0 AND $login_fabrica<>3){
						$cor = "#FFE1E1";
					}
				}
			}
			##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

			echo "<tr bgcolor='$cor'>\n";

			echo "<td align='left'>$codigo_posto</td>\n";
			echo "<td align='left' nowrap>".substr($nome,0,35)."</td>\n";
			echo "<td align='center'><a href='extrato_consulta_pesquisa_os.php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome' target='_blank'>";
			if ($login_fabrica == 1 OR $login_fabrica == 19 ) echo $protocolo;
			else                                              echo $extrato;
			echo "</a></td>\n";
			echo "<td align='left' $cor_estoque_menor>$data_geracao</td>\n";
			echo "<td align='center' title='Clique aqui para ver a quantidade de OS'><div id='qtde_os_$i'><a href=\"javascript:conta_os('$extrato','qtde_os_$i');\">VER</div></td>\n";
			//--== FIM - QTDE de OS no extrato =========================================================
		
			echo "<td align='right' nowrap> $total</td>\n";
			echo "</tr>\n";
			flush();
		}
		echo "<tr>\n";
		echo "<td colspan='7'>&nbsp;<INPUT size='60' TYPE='hidden' NAME='msg_aviso' value=''></td>\n";
		echo "<td colspan='2'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</form>\n";
	}

	if ($login_fabrica == 3 AND 1==2) {
		############################## DISTRIBUIDORES
	
		echo "<br><br>";
	
		$sql = "SELECT  tbl_posto.posto               ,
						tbl_posto.nome                ,
						tbl_posto.cnpj                ,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto_fabrica.distribuidor,
						EX.extrato           ,
						to_char (EX.data_geracao,'dd/mm/yyyy') as data_geracao,
						EX.total,
						(SELECT count (tbl_os.os) FROM tbl_os JOIN tbl_os_extra USING (os) WHERE tbl_os_extra.extrato = EX.extrato) AS qtde_os,
						to_char (tbl_extrato_pagamento.data_pagamento,'dd/mm/yyyy') as baixado
				FROM    tbl_extrato EX
				JOIN    tbl_posto USING (posto)
				JOIN    tbl_posto_fabrica ON EX.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				left JOIN    tbl_extrato_pagamento ON EX.extrato = tbl_extrato_pagamento.extrato
				WHERE   EX.fabrica = $login_fabrica
				AND     tbl_posto_fabrica.distribuidor NOTNULL 
				$cond_extrato";

		if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	
		if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
			$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	
		if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
		$sql .= " AND      EX.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	
		$xposto_codigo = str_replace (" " , "" , $posto_codigo);
		$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
		$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
		$xposto_codigo = str_replace ("." , "" , $xposto_codigo);
	
		if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto_fabrica.codigo_posto = '$xposto_codigo' ";
		if (strlen ($posto_nome) > 0 ) $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";
	
		$sql .= " GROUP BY tbl_posto.posto ,
						tbl_posto.nome ,
						tbl_posto.cnpj ,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto_fabrica.distribuidor,
						EX.extrato ,
						EX.liberado ,
						EX.total,
						EX.data_geracao,
						tbl_extrato_pagamento.data_pagamento
					ORDER BY tbl_posto.nome, EX.data_geracao";

		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 0) {
			echo "<center><h2>Nenhum extrato encontrado</h2></center>";
		}
	
		if (pg_numrows ($res) > 0) {
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$posto   = trim(pg_result($res,$i,posto));
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$nome           = trim(pg_result($res,$i,nome));
				$extrato        = trim(pg_result($res,$i,extrato));
				$data_geracao   = trim(pg_result($res,$i,data_geracao));
				$qtde_os        = trim(pg_result($res,$i,qtde_os));
				$total          = trim(pg_result($res,$i,total));
				$baixado        = trim(pg_result($res,$i,baixado));
				$extrato        = trim(pg_result($res,$i,extrato));
				$distribuidor   = trim(pg_result($res,$i,distribuidor));
				$total	        = number_format ($total,2,',','.');
	
				if (strlen($distribuidor) > 0) {
					$sql = "SELECT  tbl_posto.nome                ,
									tbl_posto_fabrica.codigo_posto
							FROM    tbl_posto_fabrica
							JOIN    tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
							WHERE   tbl_posto_fabrica.posto   = $distribuidor
							AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
					$resx = pg_exec ($con,$sql);
	
					if (pg_numrows($resx) > 0) {
						$distribuidor_codigo = trim(pg_result($resx,0,codigo_posto));
						$distribuidor_nome   = trim(pg_result($resx,0,nome));
					}
				}
	
				if ($i == 0) {
					echo "<table width='700' align='center' border='1' cellspacing='2'>";
					echo "<tr class = 'menu_top'>";
					echo "<td align='center'>Código</td>";
					echo "<td align='center' nowrap>Nome do Posto</td>";
					echo "<td align='center'>Extrato</td>";
					echo "<td align='center'>Data</td>";
					echo "<td align='center' nowrap>Qtde. OS</td>";
					echo "<td align='center'>Total</td>";
					echo "<td align='center' colspan='2'>Extrato Vinculado a um Distribuidor</td>";
					echo "</tr>";
				}
	
				echo "<tr>";
	
				echo "<td align='left'>$codigo_posto</td>";
				echo "<td align='left' nowrap>$nome</td>";
				echo "<td align='center'>$extrato</td>";
	
				echo "<td align='left'>$data_geracao</td>";
				echo "<td align='center'>$qtde_os</td>";
				echo "<td align='right' nowrap>R$ $total</td>";
				echo "<td align='left' nowrap><font face='verdana' color='#FF0000' size='-2'>$distribuidor_codigo - $distribuidor_nome</font></td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
}
?>

<br>

<? include "rodape.php"; ?>
