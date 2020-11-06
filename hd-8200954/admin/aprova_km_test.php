<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";

include "autentica_admin.php";
include 'funcoes.php';

# Pesquisa pelo AutoComplete AJAX
$q = $_GET["q"];
if (isset($_GET["q"])) {

	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 2) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$resultados = pg_fetch_all($res);
			foreach ($resultados as $resultado){
				echo $resultado['cnpj']."|".$resultado['nome']."|".$resultado['codigo_posto'];
				echo "\n";
			}
		}
	}
	exit;
}

if (strlen($os) > 0 AND $ver == 'endereco') {
	$sql = "SELECT  PO.nome                  ,
					PF.contato_endereco      ,
					PF.contato_numero        ,
					PF.contato_complemento   ,
					PF.contato_bairro        ,
					PF.contato_cidade        ,
					PF.contato_estado        ,
					PF.contato_cep           ,
					OS.consumidor_nome       ,
					OS.consumidor_endereco   ,
					OS.consumidor_numero     ,
					OS.consumidor_complemento,
					OS.consumidor_bairro     ,
					OS.consumidor_cidade     ,
					OS.consumidor_estado     ,
					OS.consumidor_cep        ,
					OS.os                    ,
					OS.sua_os                ,
					OS.qtde_km
			FROM tbl_os            OS
			JOIN tbl_posto         PO ON PO.posto = OS.posto
			JOIN tbl_posto_fabrica PF ON PF.posto = OS.posto AND OS.fabrica = PF.fabrica
			WHERE OS.os      = $os
			AND   OS.fabrica = $login_fabrica";

	$res_os = pg_query($con,$sql);

	if (pg_num_rows($res_os) > 0) {
		$nome                   = pg_fetch_result($res_os, 0, 'nome');
		$contato_endereco       = pg_fetch_result($res_os, 0, 'contato_endereco');
		$contato_numero         = pg_fetch_result($res_os, 0, 'contato_numero');
		$contato_complemento    = pg_fetch_result($res_os, 0, 'contato_complemento');
		$contato_bairro         = pg_fetch_result($res_os, 0, 'contato_bairro');
		$contato_cidade         = pg_fetch_result($res_os, 0, 'contato_cidade');
		$contato_estado         = pg_fetch_result($res_os, 0, 'contato_estado');
		$contato_cep            = pg_fetch_result($res_os, 0, 'contato_cep');
		$consumidor_nome        = pg_fetch_result($res_os, 0, 'consumidor_nome');
		$consumidor_endereco    = pg_fetch_result($res_os, 0, 'consumidor_endereco');
		$consumidor_numero      = pg_fetch_result($res_os, 0, 'consumidor_numero');
		$consumidor_complemento = pg_fetch_result($res_os, 0, 'consumidor_complemento');
		$consumidor_bairro      = pg_fetch_result($res_os, 0, 'consumidor_bairro');
		$consumidor_cidade      = pg_fetch_result($res_os, 0, 'consumidor_cidade');
		$consumidor_estado      = pg_fetch_result($res_os, 0, 'consumidor_estado');
		$consumidor_cep         = pg_fetch_result($res_os, 0, 'consumidor_cep');
		$os                     = pg_fetch_result($res_os, 0, 'os');
		$sua_os                 = pg_fetch_result($res_os, 0, 'sua_os');
		$qtde_km                = number_format(pg_fetch_result($res_os, 0, 'qtde_km'),3,',','.');
		if (strlen($sua_os) == 0) $sua_os = $os;

		echo "<table style='font-family:Verdana;font-size:10px;width:500px;'>";
			echo "<caption><h3>OS $sua_os</h3><b>Posto: $nome</b><br>Distância(ida e volta): $qtde_km Km</caption>";
			echo "<tr>";
				echo "<td><b>Endereço</b><br>$contato_endereco, $contato_numero</td>";
				echo "<td><b>Bairro</b><br>$contato_bairro</td>";
				echo "<td><b>Cidade</b><br>$contato_cidade</td>";
				echo "<td><b>Estado</b><br>$contato_estado</td>";
				echo "<td><b>CEP</b><br>$contato_cep</td>";
			echo "</tr>";
		echo "</table>";
		echo "<table style='font-family:Verdana;font-size:10px;width:500px;'>";
			echo "<caption><b>Consumidor: $consumidor_nome</b></caption>";
			echo "<tr>";
				echo "<td><b>Endereço</b><br>$consumidor_endereco, $consumidor_numero</td>";
				echo "<td><b>Bairro</b><br>$consumidor_bairro</td>";
				echo "<td><b>Cidade</b><br>$consumidor_cidade</td>";
				echo "<td><b>Estado</b><br>$consumidor_estado</td>";
				echo "<td><b>CEP</b><br>$consumidor_cep</td>";
			echo "</tr>";
		echo "</table>";

		if ($login_fabrica == 24) {

			//HD 287138
			$contato_cep    = substr($contato_cep,0,5) . '-' . substr($contato_cep,5,3) . ',BR';
			$consumidor_cep = substr($consumidor_cep,0,5) . '-' . substr($consumidor_cep,5,3) . ',BR';

			echo "<br><center>";
			//HD 287138
			//echo "<a href='http://maps.google.com.br/maps?f=d&source=s_d&saddr=$contato_cep&daddr=$consumidor_cep&hl=pt-BR&geocode=Fc3JrP4dWSwG_SkVCAS9qNC_lDHZyf4fzTKaSw%3BFXYxrf4dIpEF_SlX6Ai75Na_lDG1XQS6OnqR-A&mra=ls&sll=-14.179186,-50.449219&sspn=50.412071,112.324219&ie=UTF8&z=13' target='_blank'>ver mapa</a>";
			echo "<a href='http://maps.google.com.br/maps?saddr=$contato_cep&daddr=$consumidor_cep&hl=pt-BR' target='_blank'>ver mapa</a>";

		}

	}

	exit;

}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

//HD 237498: Coloquei para pegar a ação por GET, para que possa filtrar uma OS por get
//			 Desta forma filtra por GET e tem rotina que ja manda para que fique filtrado (extrato_consulta_os.php)
//			 Não retirar este filtro, pois é essencial para o funcionamento
if ($_POST["btn_acao"]) {
	$btn_acao    = trim($_POST["btn_acao"]);
}
else {
	$btn_acao = $_GET["btn_acao"];
}

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if($select_acao == "101" AND strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo da reprovação da OS.<br>";
	}

	$observacao = (strlen($observacao) > 0) ? " Observação: $observacao " : "Observação:";

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	for ($x=0;$x<$qtde_os;$x++){

		$xxos         = trim($_POST["check_".$x]);
		$xxqtde_km_os = trim($_POST["qtde_km_os_".$x]);
		$xxqtde_km    = trim($_POST["qtde_km_".$x]);
		$xxqtde_km    = str_replace (",",".",$xxqtde_km);
		$xxqtde_km_os = str_replace (",",".",$xxqtde_km_os);

		$xxqtde_km    = number_format($xxqtde_km ,3,'.',',');
		$xxqtde_km_os = number_format($xxqtde_km_os,3,'.',',');

		if($select_acao == "99" AND ($xxqtde_km_os <> $xxqtde_km) AND $observacao == "Observação:" ){
			$msg_erro .= "Informe o motivo da alteração do km da OS: $xxos.";
		}else{
			// ALTERARA O STATUS DE APROVADA, PARA APROVADA COM ALTERAÇÃO
			if($select_acao == "99" AND ($xxqtde_km_os <> $xxqtde_km) ){
				$select_acao = "100" ;
			}
		}

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

			$res_os = pg_query($con,"BEGIN TRANSACTION");

			$sql = "SELECT contato_email,tbl_os.sua_os
					FROM tbl_posto_fabrica
					JOIN tbl_os            ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.os      = $xxos
					AND   tbl_os.fabrica = $login_fabrica";
			$res_x = pg_query($con,$sql);
			$posto_email = pg_fetch_result($res_x,0,contato_email);
			$sua_os      = pg_fetch_result($res_x,0,sua_os);

			$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
			$res_x = pg_query($con,$sql);
			$promotor = pg_fetch_result($res_x,0,nome_completo);

			$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (98,99,100,101)
					AND os = $xxos
					ORDER BY data DESC
					LIMIT 1";
			$res_os = pg_query($con,$sql);
			if (pg_num_rows($res_os)>0){
				$status_da_os = trim(pg_fetch_result($res_os, 0, status_os));
				if ($status_da_os == 98){
					if($select_acao == "99"){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,99,current_timestamp,'$observacao',$login_admin)";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

					/*
						$email_origem  = "pt.garantia@br.bosch.com";
						$email_destino = $posto_email;
						$assunto       = "Troca Aprovada";

						$corpo.="<br>A OS n°$sua_os foi aprovada.\n\n";
						$corpo.="<br>Promotor que concedeu a aprovação: $promotor\n\n";
						$corpo.="<br>_______________________________________________\n";
						$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";
						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
						}*/
					}

					//ALTERADO O KM
					if($select_acao == "100"){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,100,current_timestamp,'$observacao',$login_admin)";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if($login_fabrica<>3){ // A Britânia quer um historico de Km - Gustavo 26/6/2008
							$sql = "UPDATE tbl_os SET qtde_km = $xxqtde_km
									WHERE os      = $xxos
									AND   fabrica = $login_fabrica";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);
							
							// HD 149799
							$sql = " SELECT fn_calcula_extrato($login_fabrica,extrato)
									FROM tbl_os_extra
									WHERE os = $xxos
									AND   extrato IS NOT NULL ";
							$res = @pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}

						/*
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $posto_email;
						$assunto       = "Troca Reprovada";

						$corpo.="<br>A OS n°$sua_os foi reprovada.\n\n";
						$corpo.="<br>Promotor que reprovou: $promotor\n\n";
						$corpo.="<br>_______________________________________________\n";
						$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){

						}*/
					}

					//RECUSADA
					if($select_acao == "101"){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,101,current_timestamp,'$observacao',$login_admin)";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if($login_fabrica<>3){ // A Britânia quer um historico de Km - Gustavo 26/6/2008
							$sql = "UPDATE tbl_os SET qtde_km = 0, qtde_km_calculada = 0
									WHERE os      = $xxos
									AND   fabrica = $login_fabrica;";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

							# HD 149799
							$sql = " SELECT fn_calcula_extrato($login_fabrica,extrato)
									FROM tbl_os_extra
									WHERE os = $xxos
									AND   extrato IS NOT NULL ";
							$res = @pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}

						/*
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $posto_email;
						$assunto       = "Troca Reprovada";

						$corpo.="<br>A OS n°$sua_os foi reprovada.\n\n";
						$corpo.="<br>Promotor que reprovou: $promotor\n\n";
						$corpo.="<br>_______________________________________________\n";
						$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){

						} */
					}
				}
			}

			//HD 237498: Para algumas fábricas que trabalham com auditoria de KM a OS irá entrar no extrato mesmo em auditoria. No entanto não é possível liberar tais extratos antes que se audite os KM. Ao auditar o KM de uma OS que esteja em um extrato o mesmo deve ser recalculado.
			//Na rotina de extrato existe um redirecionamento para este programa para que o usuário audite o KM da OS que ainda não foi auditado. Antes de modificar este código, verificar as rotinas
			$sql = "SELECT extrato
					FROM tbl_os_extra
					WHERE os=$xxos";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)>0) {
				$extrato_recalcular = pg_result($res, 0, extrato);

				if(strlen($extrato_recalcular)>0){ #HD 249679
					$sql = "SELECT fn_calcula_extrato($login_fabrica, $extrato_recalcular)";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
			//HD 237498: FIM

			if (strlen($msg_erro)==0){
				$res = pg_query($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

# HD 35771 - Francisco Ambrozio
#   Foi movido de CALLCENTER para AUDITORIA
$layout_menu = ($login_fabrica == 50) ? "auditoria" : "callcenter";
$title = "APROVAÇÃO DE DESLOCAMENTO DE KM.";

include "cabecalho.php";

?>

<style type="text/css">

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
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
}
function ver(os) {
	var url = "<? echo $PHP_SELF ?>?ver=endereco&os="+os;
	janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=550, height=300, top=18, left=0");
	janela_aut.focus();
}
</script>


<script language="JavaScript">
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_pesquisa2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}
}

function setCheck(theCheckbox,mudarcor,cor){
	if (document.getElementById(theCheckbox)) {
//		document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}

</script>

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
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script language="JavaScript">
$().ready(function() {

	$("#os").keypress(function(e) {   
		var c = String.fromCharCode(e.which);   
		var allowed = '1234567890-';
		if ((e.keyCode != 9 && e.keyCode != 8) && allowed.indexOf(c) < 0) return false;
	});


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
		matchCase: true,
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

function abreInteracao(linha,os,tipo) {

	var div = document.getElementById('interacao_'+linha);
	var os = os;
	var tipo = tipo;

	//alert('ajax_grava_interacao.php?linha='+linha+'&os='+os+'&tipo='+tipo);
	
	requisicaoHTTP('GET','ajax_grava_interacao.php?linha='+linha+'&os='+os+'&tipo='+tipo, true , 'div_detalhe_carrega2');
	
}

function div_detalhe_carrega2 (campos) {
	campos_array = campos.split("|");
	resposta = campos_array [0];
	linha = campos_array [1];
	var div = document.getElementById('interacao_'+linha);
	div.innerHTML = resposta;
	var comentario = document.getElementById('comentario_'+linha);
	comentario.focus();
}

function gravarInteracao(linha,os,tipo) {
	var linha = linha;
	var os = os;
	var tipo = tipo;
	var comentario = document.getElementById('comentario_'+linha).value;
	//alert('ajax_grava_interacao.php?linha='+linha+'&os='+os+'&comentario='+comentario+'&tipo='+tipo);

	requisicaoHTTP('GET','ajax_grava_interacao.php?linha='+linha+'&os='+os+'&comentario='+comentario+'&tipo='+tipo, true , 'div_detalhe_carrega');
}

function div_detalhe_carrega (campos) {
	campos_array = campos.split("|");
	resposta = campos_array [1];
	linha = campos_array [2];
	os = campos_array [3];

	if (resposta == 'ok') {
		document.getElementById('interacao_' + linha).innerHTML = "Gravado Com sucesso!!!";
		document.getElementById('btn_interacao_' + linha).innerHTML = "<font color='red'><a href='#' onclick='abreInteracao("+linha+","+os+",\"Mostrar\")'><img src='imagens/btn_interagir_amarelo.gif' title='Aguardando Resposta do Posto'></a></font>";
//		var linha = new Number(linha+1);
		var table = document.getElementById('linha_'+linha);
//		alert(document.getElementById('linha_'+linha).innerHTML);
		table.style.background = "#FFCC00";
	}
}

function abreObs(os,codigo_posto,sua_os){
	janela = window.open("obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,"formularios",'resizable=1,scrollbars=yes,width=400,height=250,top=0,left=0');
	janela.focus();
}

</script>

<style type="text/css">

body {
	margin: 0px;
}

body {
	margin: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}

.titulo3 {
	font-family: Arial;
	font-size: 7pt;
	text-align: left;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}

.conteudo2 {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #FFDCDC;
}

.Tabela{
	border:1px solid #d2e4fc;
	background-color:#485989;
	}
.subtitulo {
	font-family: Verdana;
	FONT-SIZE: 9px;
	text-align: left;
	background: #F4F7FB;
	padding-left:5px
}
.justificativa{
	font-family: Arial;
	FONT-SIZE: 10px;
	background: #F4F7FB;
}
.inpu{
	border:1px solid #666;
}
.conteudo_sac {
	font-family: Arial;
	FONT-SIZE: 10pt;
	text-align: left;
	background: #F4F7FB;
}

table.bordasimples {
	border-collapse: collapse;
}

table.bordasimples tr td {
	border:1px solid #000000;
}

</style>
<? include "javascript_pesquisas.php";

if($btn_acao == 'Pesquisar'){
	$data_inicial		= trim($_POST['data_inicial']);
	$data_final			= trim($_POST['data_final']);
	$aprova				= trim($_POST['aprova']);
	$regiao_comercial	= trim($_POST['regiao_comercial']);
	$posto_codigo		= trim($_POST["posto_codigo"]);
	
	
	if ($_POST["os"]) {
		$os = trim($_POST['os']);
	}
	else {
		$os = trim($_GET['os']);
	}

	if (strlen($os)>0){
		$Xos = " AND os = $os ";
	}

	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		$aprovacao = "98";
	}elseif($aprova=="aprovacao"){
		$aprovacao = "98";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "99, 100";
	}elseif($aprova=="reprovadas"){
		$aprovacao = "101";
	}

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}

	if(!empty($data_inicial) and !empty($data_final)) {
		$sqlX = "SELECT ('$xdata_final'::date - '$xdata_inicial'::date)";
		$resX = @pg_query($con,$sqlX);
		$msg_erro = pg_last_error($con);
		if(strpos($msg_erro,"date/time field value out of range") !==false) {
			$msg_erro = "Data Inválida.";
		}
		if(strlen($msg_erro)==0){
			if(pg_num_rows($resX) > 0){
				$periodo = pg_fetch_result($resX,0,0);
				if($periodo < 0) {
					$msg_erro = "Data Inválida.";
				}elseif($periodo > 90){
					$msg_erro = "Período entre datas não pode ser maior que 90 dias";
				}
			}
		}
	}

	if ($login_fabrica == 50 ) { //hd 71341 waldir
		$cond_excluidas = "AND tbl_os.excluida is not true "; 
	}

	if(strlen($posto_codigo)>0){
		$sql = " SELECT posto
				FROM tbl_posto_fabrica
				WHERE fabrica = $login_fabrica
				AND   codigo_posto = '$posto_codigo' ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$sql_posto .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
		}else{
			$msg_erro = "Código do posto $posto_codigo incorreto";
		}
		
	}
}




?>
<br>
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='formulario'>
<? if(strlen($msg_erro) > 0){ ?>
		<tr class="msg_erro">
			<td colspan='2' align='center'> <? echo $msg_erro; ?>
		</tr>
<? } ?>
<tr class="titulo_tabela"><td colspan='3' height="20px">Parâmetros de Pesquisa</td></tr>
<TBODY>
<TR>
	<td width="100">&nbsp;</td>
	<TD>Número da OS<br><input type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $os ?>" class="frm" tabindex='1'></TD>
	<TD></TD>
</TR>
<TR>
	<td width="100">&nbsp;</td>
	<TD>Data Inicial<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm" tabindex='2'></TD>
	<TD>Data Final<br><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm" tabindex='3'></TD>
</TR>
<TR>
	<td width="100">&nbsp;</td>
	<TD>Código Posto<br><input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm" tabindex='4'></TD>
	<TD>Nome do Posto<br><input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm" tabindex='5'></TD>
</TR>
<?if ($login_fabrica==50){?>
<TR>
	<td width="100">&nbsp;</td>
	<TD colspan='2'>Região Comercial<br>
			<select name='regiao_comercial' class="frm">
				<option value=''></option>
				<option value='1' <? if($regiao_comercial=='1') echo "selected"?>>Região Comercial 1 (SP)<option>
				<option value='2' <? if($regiao_comercial=='2') echo "selected"?>>Região Comercial 2 (PR SC RS RO AC MS e MT)<option>
				<option value='3' <? if($regiao_comercial=='3') echo "selected"?>>Região Comercial 3 (ES BA PE PB SE AL CE AM TO PI MA PA RN RR AP)<option>
				<option value='4' <? if($regiao_comercial=='4') echo "selected"?>>Região Comercial 4 (MG GO DF RJ)<option>
			</select>
	</TD>
</TR>
<?}?>
<tr>
	<td width="100">&nbsp;</td>
	<td colspan='2'>
		<b>Mostrar as OS:</b><br>
			<INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?> tabindex='6'>Em aprovação &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas  &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas &nbsp;&nbsp;&nbsp;
	</td>
</tr>
</tbody>
<TR>
	<TD colspan="3" align='center'>
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" alt='Clique AQUI para pesquisar' tabindex='7'>
	</TD>
</TR>
</table>
</form>

<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {
	

	if(strlen($promotor_treinamento)>0) $sql_add = " AND tbl_promotor_treinamento.admin = $promotor_treinamento ";
	else                                $sql_add = " ";
	
	//HD 169362
	if ($regiao_comercial){
		/*
		1-SP
		2-PR SC RS RO AC MS MT
		3-ES BA PE PB SE AL CE AM TO PI MA PA RN RR AP
		4-MG GO DF RJ
		*/
		$array_regioes = array(
								'1' => "SP",
								'2' => "PR SC RS RO AC MS MT",
								'3' => "ES BA PE PB SE AL CE AM TO PI MA PA RN RR AP",
								'4' => "MG GO DF RJ",
							);
	
		if (isset($array_regioes[$regiao_comercial])) {
			$estados = $array_regioes[$regiao_comercial];
			$estados = str_replace(" ","','",$estados);
			$estados = "'".$estados."'";
			$sql_add .= " AND tbl_posto.estado IN ($estados) ";
		}
	}

	$sql =  "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE status_os IN (98,99,100, 101) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (98,99,100,101) ) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos
			;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			/* select os from  tmp_interv_$login_admin; */

			SELECT	tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.qtde_km                                              ,
					tbl_os.autorizacao_domicilio                                ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.nota_fiscal_saida                                    ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_estado                            ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (98,99,100,101) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (98,99,100,101) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (98,99,100,101) ORDER BY data DESC LIMIT 1) AS status_descricao
				FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				$sql_add
				$sql_posto ";
	if($login_fabrica==20) $sql .= " AND tipo_atendimento=13 ";
	if($login_fabrica==3)  $sql .= " AND tipo_atendimento=37 ";
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				$cond_excluidas
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
	}
	if($ip=='187.117.22.167') nl2br($sql);
	#exit;

	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";

		echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";
		echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
		if ($login_fabrica == 50 ) {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>KM</B></font><a href='regra_km_colormaq.html' target='_blank' title='Ver Regra'> <font color='#0099FF'>?</font></a></td>";
		} else {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>KM</B></font></td>";
		}

		if($login_fabrica==3){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Nº Autorização</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Justificativa</B></font></td>";
		}

		if($login_fabrica==30){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OBSERVAÇÃO</B></font></td>";
		}

		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>DIGITAÇÃO</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Descrição</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>STATUS</B></font></td>";

		if ($login_fabrica == 50) {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Interação</B></font></td>";
		}
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;
		$total_os = pg_num_rows($res);
		for ($x=0; $x<pg_num_rows($res);$x++){

			$os						= pg_fetch_result($res, $x, os);
			$sua_os					= pg_fetch_result($res, $x, sua_os);
			$codigo_posto			= pg_fetch_result($res, $x, codigo_posto);
			$posto_nome				= pg_fetch_result($res, $x, posto_nome);
			$qtde_km				= pg_fetch_result($res, $x, qtde_km);
			$autorizacao_domicilio	= pg_fetch_result($res, $x, autorizacao_domicilio);
			$consumidor_nome		= pg_fetch_result($res, $x, consumidor_nome);
			$produto_referencia		= pg_fetch_result($res, $x, produto_referencia);
			$produto_descricao		= pg_fetch_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_fetch_result($res, $x, voltagem);
			$data_digitacao			= pg_fetch_result($res, $x, data_digitacao);
			$data_abertura			= pg_fetch_result($res, $x, data_abertura);
			$status_os				= pg_fetch_result($res, $x, status_os);
			$status_observacao		= pg_fetch_result($res, $x, status_observacao);
			$status_descricao		= pg_fetch_result($res, $x, status_descricao);
			$qtde_kmx = number_format($qtde_km,3,',','.');
			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
			
			if ($login_fabrica == 50) {
				$sqlint = " SELECT os_interacao,admin from tbl_os_interacao
							WHERE os = $os
							ORDER BY os_interacao DESC limit 1";

				$resint = pg_query($con,$sqlint);

				if(pg_num_rows($resint)>0) {
					$cor = (strlen(pg_fetch_result($resint,0,'admin'))>0) ? "#FFCC00" : "#669900";
				}
			}

			if(strlen($sua_os)==o)$sua_os=$os;
			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
			if($status_os==98){
				echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
				echo (strlen($msg_erro)>0 and strlen($_POST["check_".$x])>0) ? " CHECKED " : "";
				echo ">";
			}
			echo "</td>";

			$sql_extrato = "SELECT extrato FROM tbl_os_extra WHERE os = $os;";
			$res_extrato = pg_query($con,$sql_extrato);
			if(pg_num_rows($res_extrato)>0 and strlen(pg_fetch_result($res_extrato, 0, extrato))>0){
				$title_extrato  = "<br>".pg_fetch_result($res_extrato, 0, extrato);
				$title_extrato2 = "Esta Ordem de Serviço já consta em um extrato e vai ser recalculado! Se você não tem certeza da alteração, não faça! Este extrato já pode ter sido impresso pelo Posto ou por outro setor da administração!" ;
			}else{
				$title_extrato  = "";
				$title_extrato2 = ""; 
			}

			echo "<td style='font-size: 9px; font-family: verdana' nowrap ><a href='os_press.php?os=$os' title='$title_extrato2' target='_blank'>$sua_os $title_extrato</a></td>";
			if($login_fabrica==3){
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$qtde_kmx &nbsp;";
				echo "<a href='javascript:ver($os);'>Ver Endereços</a>";
				echo "</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>".$autorizacao_domicilio. "</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>".$status_observacao. "</td>";
			}else{
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "<input type='hidden' size='5' name='qtde_km_os_$x' value='$qtde_km'>";
				echo "<input type='text' size='5' name='qtde_km_$x' value='$qtde_kmx'>";
				echo "<a href='javascript:ver($os);'>Ver Endereços</a>";
				echo "</td>";
			}
			if($login_fabrica==30) {
				// HD 47644
				echo "<td style='font-size: 9px; font-family: verdana'>".$status_observacao. "</td>";
			}
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação do Promotor: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
			#echo "<td style='font-size: 9px; font-family: verdana'><a href=\"javascript: abreObs('$os','$codigo_posto','$sua_os')\">VER</a></td>";

			if ($login_fabrica == 50) {
				$sqlint = "SELECT os_interacao,admin from tbl_os_interacao
							WHERE os = $os
						ORDER BY os_interacao DESC limit 1";
				$resint = pg_query($con,$sqlint);

				if(pg_num_rows($resint)==0) {
					$botao = "<img src='imagens/btn_interagir_azul.gif' title='Enviar Interação com Posto'>";
				} else {
					$admin = pg_fetch_result($resint,0,admin);
					if (strlen($admin)>0) {
						$botao = "<img src='imagens/btn_interagir_amarelo.gif' title='Aguardando Resposta do Posto'>";
					} else {
						$botao = "<img src='imagens/btn_interagir_verde.gif' title='Posto Respondeu, clique aqui para visualizar'>";
					}
				}
				echo "<td><font color='#FFFFFF'><div id=btn_interacao_".$x."><B><font color='blue'><a href='#' onclick='abreInteracao($x,$os,\"Mostrar\")'>$botao</a></font></B></font></div></td>";
			}
			echo "</tr>";
			if ($login_fabrica == 50 ) {
				echo "<tr>";
				echo "<td colspan=9><div id='interacao_".$x."'></div></td>";
				echo "</tr>";
			}
		}
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";
		if(trim($aprova) == 'aprovacao'){
			echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
			echo "<select name='select_acao' size='1' class='frm' >";
			echo "<option value=''></option>";
			echo "<option value='99'";  if ($_POST["select_acao"] == "99")  echo " selected"; echo ">APROVADO</option>";
			echo "<option value='101'";  if ($_POST["select_acao"] == "101")  echo " selected"; echo ">RECUSADO</option>";
			echo "</select>";
			echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value=''  "; if ($_POST["select_acao"] == "19") echo " DISABLED "; echo ">";
			echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
		}
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "<p>TOTAL OS: $total_os</p>";
		echo "</form>";
	}else{
		echo "<center>Nenhuma OS encontrada.</center>";
	}
	$msg_erro = '';
}
echo "<br>";
include "rodape.php" ?>