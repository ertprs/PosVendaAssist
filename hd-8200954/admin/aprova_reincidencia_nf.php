<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';
include "../helpdesk.inc.php";// Funcoes de HelpDesk hd_chamado=2537875

function ultima_interacao($os) {
    global $con, $login_fabrica;

    $select = "SELECT admin, posto FROM tbl_os_interacao WHERE fabrica = {$login_fabrica} AND os = {$os} AND interno IS NOT TRUE ORDER BY data DESC LIMIT 1";
    $result = pg_query($con, $select);

    if (pg_num_rows($result) > 0) {
        $admin = pg_fetch_result($result, 0, "admin");
        $posto = pg_fetch_result($result, 0, "posto");

        if (!empty($admin)) {
            $ultima_interacao = "fabrica";
        } else {
            $ultima_interacao = "posto";
        }
    }

    return $ultima_interacao;
}

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

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj         = trim(pg_fetch_result($res,$i,cnpj));
				$nome         = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);
$status_reinc =($login_fabrica == 30) ? " 67,132,19,190" : (($login_fabrica ==24) ? "67,70,19" : "");

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if(strlen($observacao) > 0){
		$observacao = " Observação: $observacao ";
	}else{
		if($select_acao == 15) {
			$msg_erro = "Informe a justificativa da recusa";
		}else{
			$observacao = " ";
		}
	}

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}


	if (strlen($msg_erro)==0){

		for ($x=0;$x<$qtde_os;$x++){

			$xxos         = trim($_POST["check_".$x]);

			if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

				$res_os = pg_query($con,"BEGIN TRANSACTION");

				$sql = "SELECT status_os
						FROM tbl_os_status
						WHERE status_os IN ($status_reinc)
						AND os = $xxos
						ORDER BY data DESC
						LIMIT 1";
				$res_os = pg_query($con,$sql);
				if (pg_num_rows($res_os)>0){

					$status_da_os = trim(pg_fetch_result($res_os,0,status_os));
					if (in_array($status_da_os,array(132,67,70))){

						/* Aprovar */
						if($select_acao == "19"){
							$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin)
									VALUES ($xxos,19,current_timestamp,'$observacao',$login_admin)";
							$res = pg_query($con,$sql);
							$msg_erro = pg_last_error($con);
							$esmaltec_acao = 'aprovada';
						}

						/* Recusar */
						if($select_acao == "15"){
							$esmaltec_acao = 'reprovada';

							// Regras para auditoria de NS e Reincidência - HD-2539696 (Esmaltec[30])
							if (in_array($login_fabrica, array(30))) {
								$status_reprova = ($status_da_os == 67) ? 104 : 190 ;
								$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin)
									VALUES ($xxos,190,current_timestamp,'$observacao',$login_admin)";
								$res = pg_query($con,$sql);
								$msg_erro .= pg_last_error($con);

								$sql = "UPDATE tbl_os SET
								cancelada  = 't',
								data_fechamento = CURRENT_DATE,
								finalizada = CURRENT_TIMESTAMP
								WHERE os = $xxos
								AND fabrica = $login_fabrica ";
								$res = pg_query($con,$sql);
								$msg_erro = pg_last_error($con);
							} else {
								$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin)
									VALUES ($xxos,15,current_timestamp,'$observacao',$login_admin)";
								$res = pg_query($con,$sql);
								$msg_erro .= pg_last_error($con);

								$sql = "SELECT fn_os_excluida($xxos,$login_fabrica,$login_admin);";
								$res = @pg_query ($con,$sql);
								$msg_erro = pg_last_error($con);
							}
						}
					}
				}
				if (strlen($msg_erro)==0){
					/**
					 * @since HD 261434 - enviar email pro posto
					 */
					if ($login_fabrica == 30 and empty($msg_erro)) {
						$sqlPostoeMail = "SELECT tbl_posto_fabrica.contato_email, tbl_os.sua_os
							FROM tbl_posto_fabrica
							JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE os = $xxos";
						$resPostoeMail = pg_query($con, $sqlPostoeMail);

						if (pg_num_rows($resPostoeMail) == 0) {
							$sqlPostoeMail2 = "SELECT tbl_posto.email, tbl_os.sua_os FROM tbl_posto JOIN tbl_os USING (posto) where os = $xxos";
							$resPostoeMail2 = pg_query($con, $sqlPostoeMail2);

							if (pg_num_rows() == 1) {
								$posto_email  = pg_fetch_result($resPostoeMail2, 0, 'email');
								$sua_os_email = pg_fetch_result($resPostoeMail2, 0, 'sua_os');
							}
						} else {
							$posto_email  = pg_fetch_result($resPostoeMail, 0, 'contato_email');
							$sua_os_email = pg_fetch_result($resPostoeMail, 0, 'sua_os');
						}

						if (!empty($posto_email)) {
							$sqlAdminNome = "select nome_completo from tbl_admin where admin = $login_admin";
							$qryAdminNome = pg_query($con, $sqlAdminNome);
							$nome_admin = pg_fetch_result($qryAdminNome, 0, 'nome_completo');

							if($status_da_os == 67) {
								$assunto = 'O.S. ' . $sua_os_email  . ' ' . $esmaltec_acao . ' da Auditoria de Reincidência de NS';
								$msg = 'A OS ' . $sua_os_email . ' foi ' . $esmaltec_acao . ' da Auditoria de Reincidência de NS por ' . $nome_admin . ' da Esmaltec. ';
							}else{
								$assunto = 'O.S. ' . $sua_os_email  . ' ' . $esmaltec_acao . ' da Auditoria de Reincidência de NF';
								$msg = 'A OS ' . $sua_os_email . ' foi ' . $esmaltec_acao . ' da Auditoria de Reincidência de NF por ' . $nome_admin . ' da Esmaltec. ';
							}
							$msg .= '<br/><br/>';
							$msg .= str_replace("Observação", "Motivo", $observacao);

							$headers  = 'MIME-Version: 1.0' . "\r\n";
							$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
							$headers .= 'From: Esmaltec <auditoria.sae@esmaltec.com.br>' . "\r\n";

							mail($posto_email, utf8_encode($assunto), utf8_encode($msg), $headers);
						}

					}

					$res = pg_query($con,"COMMIT TRANSACTION");
				}else{
					$res = pg_query($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}
}

$layout_menu = "auditoria";

$title =  "Aprovação de Reincidência";

include "cabecalho.php";

if ($login_fabrica == 30) {
	$plugins = array(
    	"shadowbox"
	);
}

?>

<style type="text/css">

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
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

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
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

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

.env-interagir{
    width: 800px;
    min-height: 400px;
    border: 3px solid #e2e2e2;
    background: #fff;
    position: fixed;
    top: 50px;
    left: 20%;

    display: none;
}

.env-interagir textarea{
    margin-top: 40px;
    width: 500px;
    height: 100px;

}

#env-interacoes{
    height: 181px;
    overflow-y: scroll;
}

#env-interacoes table{
    width: 100%;
}

.env-buttons{
    margin-top: 15px;
    width: 100%;
}

#interacao-msg{
    min-height: 0px;
    background: #e2e2e2;
    position: absolute;
    width: 100%;
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
}
</script>


<script language="JavaScript">
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

<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007
	include_once '../js/js_css.php';
?>

<script language="JavaScript">
$().ready(function() {
	<?php if ($login_fabrica == 30) { ?>
			Shadowbox.init();
	<?php } ?>
	$('#data_inicial').datepick({startdate:'01/01/2000'});
	$('#data_final').datepick({startDate:'01/01/2000'});
	$("#data_inicial").mask("99/99/9999");
	$("#data_final").mask("99/99/9999");

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

	$(".alterar").click(function(){
		var num_os = $(this).data("num_os");
		Shadowbox.open({
	        content:    "alterar_dados_os_reincidente_esmaltec.php?os="+num_os,
	        player: "iframe",
	        title:      "Dados O.S Reincidente",
	        width:  800,
	        height: 230
	    });
	});

});

function exibeOS(os){
	if($("tr[id^="+os+"_]").css('display') == "none"){
		$("tr[id^="+os+"_]").show();
		$('#img_'+os).attr('src','imagens/menos.bmp');
	}else{
		$("tr[id^="+os+"_]").hide();
		$('#img_'+os).attr('src','imagens/mais.bmp');
	}
}

function changeColorLine(os){

	$(".btn-interagir").each(function(idx,elem){
		if($(elem).attr("data-os") == os){
			var tr = $(elem).parents("tr");
			$(tr).attr("style","background: #FFDC4C");
		}
	});
}


var timeHelper;
function clearMessage(){
    window.clearTimeout(timeHelper);

    timeHelper =  setTimeout(function(){
        $("#interacao-msg").html("");
    },5000);

}

/* Comentado no chamado hd_chamado=2538024
function getInterations (os) {
    $("#tr-coments").html("");

    $.get("ajax_interagir_os.php",{os:os},function(response){
    	response = JSON.parse(response);

		$.each(response,function(idx,elem){
            var tr = $("<tr>");

            $(tr).append($("<td>").html(elem.comentario));
            if(elem.nome_completo == ""){
                $(tr).append($("<td>").html(elem.nome_fantasia ));
            }else{
                $(tr).append($("<td>").html(elem.nome_completo));
            }

            $(tr).append($("<td>").html(elem.data));

            $("#tr-coments").append(tr);
        });
    });
}

$(function(){
  $(".btn-interagir").click(function(){
    var os = $(this).attr("data-os");
		$("#os-number-env").html(os);

		interations = getInterations(os);

    $("#btn-grava-interacao").data("os",$(this).attr("data-os"));
    $("#txt-interacao").val("");

    $(".env-interagir").fadeIn("500");
  });


  $("#btn-grava-interacao").click(function(){
    var os = $(this).data("os");
    var text = $("#txt-interacao").val();
    if(text == ""){
        $("#interacao-msg").html("Digite uma interação");
        clearMessage();

        return false;
    }
    $("#btn-grava-interacao").html("Gravando...");
    $.post("ajax_interagir_os.php",{os: os, interacao: text},function(response){
			response = JSON.parse(response);
    	$("#btn-grava-interacao").html("Gravar");
      if(response.exception == undefined && response.msg == "ok"){
        $("#interacao-msg").html("Interação Gravada!!!");
        clearMessage();

        changeColorLine(os);
        setTimeout(function(){
            $(".env-interagir").fadeOut("500");
            $("#interacao-msg").html("");
        },1500);
      }else{
        $("#interacao-msg").html(response.exception);
        clearMessage();
  		}
		});
	});

	$("#btn-cancela-interacao").click(function(){
	  $(".env-interagir").fadeOut("500");
	});
});
*/
//Adicionado no chamado hd_chamado=2538024
function abreInteracao(linha,os,tipo) {
  $.get(
    'ajax_grava_interacao.php',
    {
      linha:linha,
      os:os,
      tipo:tipo
    },
    function (resposta){
        resposta_array = resposta.split("|");
        resposta = resposta_array [0];
        linha = resposta_array [1];
        $('#interacao_'+linha).html(resposta);
        $('#comentario_'+linha).focus;

    }
  )
}
function box_interagir(os) {
  	Shadowbox.open({
	    content: "relatorio_interacao_os.php?interagir=true&os="+os,
	    player: "iframe",
	    width: 850,
	    height: 600,
	    title: "Ordem de Serviço "+os
	});
}
function refreshInteracoes(linha, os) {
  $.ajax({
      url: "ajax_refresh_interacao.php",
      type: "POST",
      data: {
          linha: linha,
          os: os
      },
      complete: function (data) {
          $("#interacao_"+linha).find("td[rel=interacoes]").html(data.responseText);
      }
  })
}
function div_detalhe_carrega (campos) {
  campos_array = campos.split("|");
  resposta = campos_array [1];
  linha = campos_array [2];
  os = campos_array [3];

  if (resposta == 'ok') {
    document.getElementById('interacao_' + linha).innerHTML = "Gravado Com sucesso!!!";
    document.getElementById('btn_interacao_' + linha).innerHTML = "<font color='red'><a href='#' onclick='abreInteracao("+linha+","+os+",\"Mostrar\")'><img src='imagens/btn_interagir_amarelo.gif' title='Aguardando Resposta do Posto'></a></font>";
    var table = document.getElementById('linha_'+linha);
    table.style.background = "#FFCC00";
  }
}
// Fim Adicionado no chamado hd_chamado=2538024
</script>


<? include "javascript_pesquisas.php";

if($btn_acao == 'Pesquisar'){

	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$aprova       = trim($_POST['aprova']);
	$os           = trim($_POST['os']);

	if (strlen($os)>0){
		$Xos = " AND os = $os ";
	}

	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		$aprovacao = ($login_fabrica == 30) ? "67,132" : (($login_fabrica == 24) ? "67,70" : "");
	}elseif($aprova=="aprovacao"){
		$aprovacao = ($login_fabrica == 30) ? "67,132" : (($login_fabrica == 24) ? "67,70" : "");
	}elseif($aprova=="aprovadas"){
		$aprovacao = "19";
	} elseif($aprova=="reprovadas"){
		$aprovacao = ($login_fabrica == 30) ? "190" : "15";
	}

	if ($login_fabrica == 30 && $aprovacao == "190") {
		$status_reinc = "15,19,67,132,190";
		$status_reinc2 = "67,132";
		$join_status= "JOIN tbl_os_status st2 ON st2.os = tbl_os_status.os and st2.os_status < tbl_os_status.os_status and st2.status_os in ($status_reinc2)" ;
	}

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
}

if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}

?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">



<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption><?=$title?></caption>

<TBODY>
<TR>

	<TD>Número da OS<br><input type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $os ?>" class="frm"></TD>
	<TD></TD>
</TR>
<TR>

	<TD>Data Inicial<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm"></TD>
	<TD>Data Final<br><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm"></TD>
</TR>
<TR>

	<TD>Código Posto<br><input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm"></TD>
	<TD>Nome do Posto<br><input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm"></TD>
</TR>
<TR>
	<td colspan='100%' align='left '>
		Estado<br><select name="estado" id='estado'  class="frm">
					<? $ArrayEstados = array('','AC','AL','AM','AP',
												'BA','CE','DF','ES',
												'GO','MA','MG','MS',
												'MT','PA','PB','PE',
												'PI','PR','RJ','RN',
												'RO','RR','RS','SC',
												'SE','SP','TO'
											);

					for ($i=0; $i<=27; $i++){
						echo"<option value='".$ArrayEstados[$i]."'";
						if ($estado == $ArrayEstados[$i]) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";

						if($login_fabrica == 30 AND $i == 0){
						  ?>
						    <option value='1' <? if ($estado == 1) echo " SELECTED "; ?>>Sul (SC, RS e PR)</option>
						    <option value='2' <? if ($estado == 2) echo " SELECTED "; ?>>Sudeste (SP, RJ, ES e MG)</option>
						    <option value='3' <? if ($estado == 3) echo " SELECTED "; ?>>Centro Oeste (GO, MS, MT e DF)</option>
						    <option value='4' <? if ($estado == 4) echo " SELECTED "; ?>>Nordeste (SE, AL, RN, MA, PE, PB, CE, PI e BA)</option>
						    <option value='5' <? if ($estado == 5) echo " SELECTED "; ?>>Norte (TO, PA, AP, RR, AM, AC E RO)</option>
						  <?
						}
					}?>
		</select>
	</td>
</tr>

<?php if($login_fabrica == 30){ $aAtendentes = hdBuscarAtendentes(); //hd_chamado=2537875 ?>

<TR>
	<td colspan='100%' align='left '>Inspetor<br>
		<select class='frm' name="admin_sap" id="admin_sap">
	      <option value=""></option>
	      <?php foreach($aAtendentes as $aAtendente): ?>
            <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo empty($aAtendente['nome_completo']) ? $aAtendente['login'] : $aAtendente['nome_completo'] ; ?></option>
         <?php endforeach; ?>
	   </select>
	</td>
</TR>

<?php } ?>


<tr>
	<td colspan='2'>
		<b>Mostrar as OS:</b><br>

			<INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>Em aprovação &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas  &nbsp;&nbsp;&nbsp;<INPUT TYPE="radio" NAME="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas  &nbsp;&nbsp;&nbsp;
	</td>
</tr>
</tbody>
<TR>
	<TD colspan="2">
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
	</TD>
</TR>
</table>
</form>

<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {

	$posto_codigo= trim($_POST["posto_codigo"]);
	$estado      = trim($_POST["estado"]);

	if($login_fabrica == 30){ //hd_chamado=2537875
		if(strlen($admin_sap) > 0){
			$admin_sap = (int) $_POST['admin_sap'];
			$cond_admin_sap = " AND tbl_posto_fabrica.admin_sap = $admin_sap";
		}

		if(strlen($posto_codigo)>0){ //hd_chamado=2537875
			$sql = " SELECT tbl_posto_fabrica.posto,
								tbl_posto.nome AS nome_posto
					FROM tbl_posto_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$nome_posto = pg_fetch_result($res, 0, 'nome_posto');
			}
		}
	}

	if(strlen($posto_codigo)>0)         $sql_add .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
	if(strlen($estado)>0){
	  switch($estado){
	    case 1: $sql_add2 .= " AND tbl_posto_fabrica.contato_estado IN ('SC', 'RS', 'PR')"; break;
	    case 2: $sql_add2 .= " AND tbl_posto_fabrica.contato_estado IN ('SP', 'RJ', 'ES', 'MG')"; break;
	    case 3: $sql_add2 .= " AND tbl_posto_fabrica.contato_estado IN ('GO', 'MS', 'MT', 'DF')"; break;
	    case 4: $sql_add2 .= " AND tbl_posto_fabrica.contato_estado IN ('SE','AL', 'RN', 'MA', 'PE', 'PB', 'CE', 'PI', 'BA')"; break;
	    case 5: $sql_add2 .= " AND tbl_posto_fabrica.contato_estado IN ('TO', 'PA', 'AP', 'RR', 'AM', 'AC', 'RO')"; break;
	    default : $sql_add2 .= " AND tbl_posto_fabrica.contato_estado = '$estado' ";
	  }
	}

	if ($aprovacao == '15' || (in_array($login_fabrica, array(30)) && $aprovacao == '190')) {
		$cond_fabrica = " (tbl_os.fabrica = $login_fabrica or tbl_os.fabrica = 0) ";
	}
	else
	{
		$cond_fabrica = " tbl_os.fabrica = $login_fabrica ";
	}

	#HD 407384 - INICIO

	$sql_data_a = ( $xdata_inicial or ( $xdata_inicial and $xdata_final ) and empty($msg_erro) ) ? " AND tbl_os_status.data >= '$xdata_inicial' " : null;

	// Acresentado pela mudança do SQL, foi necessário criar um Alias para a consulta HD 2539696
	$sqlDataAlias = ( $xdata_inicial or ( $xdata_inicial and $xdata_final ) and empty($msg_erro) ) ? " AND ost.data >= '$xdata_inicial' " : null;

	$sql_os_a   = ($_POST['os']) ? " AND ost.os = $os " : null ;

	$sql =  "	SELECT ost.os,
				ost.os_status

			INTO TEMP tmp_inverv_os_$login_admin

			FROM tbl_os_status ost
			JOIN tbl_os USING (os)

			WHERE ost.fabrica_status = $login_fabrica
			AND ost.os_status IN (SELECT tbl_os_status.os_status
						FROM tbl_os_status
						$join_status
						WHERE tbl_os_status.os = ost.os
						AND tbl_os_status.status_os IN ($status_reinc)
						$sql_data_a
						ORDER BY tbl_os_status.data DESC
						LIMIT 1)
			AND ost.status_os IN ($status_reinc)
			$sqlDataAlias
			$sql_os_a
			ORDER BY tbl_os.data_digitacao DESC;

			CREATE INDEX tmp_inverv_os_".$login_admin."_os ON tmp_inverv_os_".$login_admin."(os);
			CREATE INDEX tmp_inverv_os_".$login_admin."_os_status ON tmp_inverv_os_".$login_admin."(os_status);

			SELECT
			tbl_os.os                                                     		,
			tbl_os.sua_os                                                 		,
			TO_CHAR(tbl_os_status.data,'DD/MM/YYYY')    AS data_status    		,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura 		,
			TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao		,
			tbl_os.consumidor_nome                                        		,
			tbl_os.obs_reincidencia                                       		,
			tbl_os.pecas AS total_pecas							,
			tbl_os.qtde_km_calculada AS valor_km						,
			tbl_os.mao_de_obra								,
			tbl_defeito_constatado.codigo							,
			tbl_defeito_constatado.descricao AS defeito_constatado_desc			,
			tbl_defeito_constatado.defeito_constatado					,
			(SELECT tbl_defeito_constatado_interno.descricao FROM tbl_defeito_constatado AS tbl_defeito_constatado_interno WHERE tbl_defeito_constatado_interno.fabrica = $login_fabrica AND codigo = RPAD(SUBSTR(tbl_defeito_constatado.codigo,1,4),6,'0') LIMIT 1) AS DES_GRUP_DEFEITO,
			tbl_posto.nome AS posto_nome 								  		,
			tbl_posto_fabrica.codigo_posto 								  		,
			tbl_admin.nome_completo 									  		,
			tbl_posto_fabrica.contato_estado 							  		,
			tbl_produto.referencia 						AS produto_referencia 	,
			tbl_produto.descricao 						AS produto_descricao	,
			tbl_produto.voltagem 												,
			tbl_os_status.status_os												,
			tbl_os_status.observacao 					AS status_observacao    ,
			tbl_status_os.descricao 					AS status_descricao

			FROM
			tmp_inverv_os_".$login_admin."

			JOIN tbl_os_status ON tmp_inverv_os_".$login_admin.".os_status=tbl_os_status.os_status AND tbl_os_status.fabrica_status=$login_fabrica
			JOIN tbl_status_os ON tbl_os_status.status_os=tbl_status_os.status_os
			JOIN tbl_os ON tbl_os_status.os = tbl_os.os
			LEFT JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			LEFT JOIN tbl_defeito_constatado              ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado

			WHERE
			$cond_fabrica
			$cond_admin_sap
			AND tbl_os_status.status_os IN (".$aprovacao.")
			$sql_add
			$sql_add2  ";

	if($login_fabrica == 24) { # HD 158379
			$sql.=" AND tbl_os.data_digitacao > '2009-10-14 00:00:00'";
	}else if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final' ";
	}

	//echo "<br />". nl2br($sql) .  "<br />";

	$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";
		if($login_fabrica == 30){
				echo '<div class="legenda-interacao" style="width: 98%;margin: 0 auto;">
				    <table>
				        <tr>
				            <td style="background: #A6D941; height:20px; width:45px;">&nbsp</td>
				            <td align="left">Posto interagiu</td>
				        </tr>
				        <tr>
				            <td style="background: #FFDC4C; height:20px; width:45px;">&nbsp;</td>
				            <td align="left">Fábrica interagiu</td>
				        </tr>
				    </table>
				</div>';

 		}
		echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF' id='resultado'>";
		echo "<theader>";
		echo "<tr>";
		echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>DIGITAÇÃO</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		if ($aprovacao =='15' or $aprovacao == '19') {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>ADMIN</B></font></td>";
		}
		if ($login_fabrica == 30 and $aprovacao != '15,190' and ($aprovacao == '19' or $aprovacao = '67' or $aprovacao = '132')){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Aprovação</B></font></td>";
		}
		if ($login_fabrica == 30 and $aprovacao == '15,190'){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>Recusada</B></font></td>";
		}
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>STATUS</B></font></td>";
		if ($login_fabrica == 30) {
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OBSERVAÇÃO</B></font></td>";
		}
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Justificativa</B></font></td>";

		if($login_fabrica == 30){
		  echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Defeito Constatado</B></font></td>";
		  echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Grupo de Defeito</B></font></td>";
		  echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Valor das Peças</B></font></td>";
		  echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Mão de Obra</B></font></td>";
		  echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Valor do KM</B></font></td>";
		  echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Valor da OS</B></font></td>";
		  echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Ação</B></font></td>";
		  echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Interação</B></font></td>";
		}
		echo "</tr>";
		echo "</theader>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x=0; $x<pg_num_rows($res);$x++){

			$os						= pg_fetch_result($res, $x, os);
			$sua_os					= pg_fetch_result($res, $x, sua_os);
			$data_digitacao			= pg_fetch_result($res, $x, data_digitacao);
			$data_status			= pg_fetch_result($res, $x, data_status);
			$data_abertura			= pg_fetch_result($res, $x, data_abertura);
			$consumidor_nome		= pg_fetch_result($res, $x, consumidor_nome);
			$posto_nome				= pg_fetch_result($res, $x, posto_nome);
			$codigo_posto			= pg_fetch_result($res, $x, codigo_posto);
			$produto_referencia		= pg_fetch_result($res, $x, produto_referencia);
			$produto_descricao		= pg_fetch_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_fetch_result($res, $x, voltagem);
			if (($aprovacao == '15' || (in_array($login_fabrica, array(30)) && $aprovacao == '190')) or $aprovacao == '19') {
				$nome_completo			= pg_fetch_result($res, $x, nome_completo);
			}

			$status_os			= pg_fetch_result($res, $x, status_os);
			$status_observacao		= str_replace("'","",pg_fetch_result($res, $x, status_observacao));
			$status_descricao		= pg_fetch_result($res, $x, status_descricao);
			$obs_reincidencia		= pg_fetch_result($res, $x, obs_reincidencia);
			$total_pecas			= pg_fetch_result($res, $x, total_pecas);
			$valor_km			= pg_fetch_result($res, $x, valor_km);
			$mao_de_obra			= pg_fetch_result($res, $x, mao_de_obra);
			$defeito_constatado		= pg_fetch_result($res, $x, defeito_constatado);
			$defeito_constatado_desc	= pg_fetch_result($res, $x, defeito_constatado_desc);
			$defeito_constatado_codigo	= pg_fetch_result($res, $x, codigo);
			$grupo_defeito_constatado	= pg_fetch_result($res, $x, DES_GRUP_DEFEITO);
			$total_os = $total_pecas + $valor_km + $mao_de_obra;

			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';


			if($login_fabrica == 30){
                $ultima_interacao = ultima_interacao($os);
                switch ($ultima_interacao) {
                    case "fabrica":
                        $cor = "#FFDC4C";
                        break;

                    case "posto":
                        $cor = "#A6D941";
                        break;
                }
            }

			if(strlen($sua_os)==0){
				$sua_os=$os;
			}

			if($login_fabrica == 30){
				if (strlen($defeito_constatado) > 0) {
				   $sqlVerificaOS = "SELECT tbl_defeito_constatado .descricao
						      FROM tbl_os_defeito_reclamado_constatado
						      JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
						      AND tbl_defeito_constatado.fabrica = $login_fabrica
						      WHERE tbl_os_defeito_reclamado_constatado.os = $os
						      AND tbl_os_defeito_reclamado_constatado.defeito_constatado <> $defeito_constatado";
			    		$resVerificaOS = pg_query($con,$sqlVerificaOS);
					$temOS = (pg_num_rows($resVerificaOS) > 0) ? "sim" : "";
				}

				echo "<input type=\"hidden\" value=\"$posto_codigo\" name=\"posto_codigo\" />"; //hd_chamado=2537875
				echo "<input type=\"hidden\" value=\"$nome_posto\" name=\"posto_nome\" />"; //hd_chamado=2537875
				echo "<input type=\"hidden\" value=\"$admin_sap\" name=\"admin_sap\" />"; //hd_chamado=2537875

			}
			echo "<tbody>";
			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
				if(in_array($status_os,array(132,67,70))){
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
					if (strlen($msg_erro)>0){
						if (strlen($_POST["check_".$x])>0){
							echo " CHECKED ";
						}
					}
					echo ">";
				}
			echo "</td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap >";
			if($login_fabrica == 30 AND $temOS == "sim"){
			  echo "<img src='imagens/mais.bmp' id='img_$os' onclick=\"exibeOS('$os');\" style='cursor:pointer;'> &nbsp;";
			}
			echo "<a href='os_press.php?os=$os'  target='_blank'>".$sua_os."</a></td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_referencia ." - ". substr($produto_descricao,0,30) ."</acronym></td>";
			if ($aprovacao == '15' or $aprovacao == '19') {
				echo "<td>$nome_completo</td>";
			}
			if ($login_fabrica == 30){
				echo "<td style='font-size: 9px; font-family: verdana'>".$data_status. "</td>";
			}
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
			if ($login_fabrica == 30) {
				echo "<td style='font-size: 9px; font-family: verdana' nowrap>".$status_observacao. "</td>";
			}
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$obs_reincidencia."'>".substr($obs_reincidencia,0,30). "</acronym></td>";
			if ($login_fabrica == 30) {
				echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$defeito_constatado_codigo ." - ".$defeito_constatado_desc. "</td>";
				echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$grupo_defeito_constatado. "</td>";
				echo "<td style='font-size: 9px; font-family: verdana' nowrap>".number_format($total_pecas,2,',','.'). "</td>";
				echo "<td style='font-size: 9px; font-family: verdana' nowrap>".number_format($mao_de_obra,2,',','.'). "</td>";
				echo "<td style='font-size: 9px; font-family: verdana' nowrap>".number_format($valor_km,2,',','.'). "</td>";
				echo "<td style='font-size: 9px; font-family: verdana' nowrap>".number_format($total_os,2,',','.'). "</td>";

				echo "<td>";
				if($status_observacao == 'OS EM REINCIDÊNCIA DE NÚMERO DE SÉRIE'){
					echo "<button type='button' class='alterar' data-num_os='$os' >Alterar</button>";
				}
				echo "</td>";

				echo '<td>';

				?>
				<!-- Adicionado no chamado hd_chamado=2538024 -->
					<div>
						<button type="button" onClick="box_interagir(<?=$os?>)" class="btn-interagir">Interagir</button>
					</div>
				</td>

				
      	</tr>
      	<!-- FIM chamado hd_chamado=2538024 -->
			<?php
			}
			echo "</tr>";

			if($login_fabrica == 30){

			  if(pg_num_rows($resVerificaOS) > 0){

			    for($j = 0; $j < pg_num_rows($resVerificaOS); $j++){
				  echo "<tr id='{$os}_{$j}' style='display: none;' bgcolor='$cor'><td >";
				  $defeito_constatado_adicional = pg_result($resVerificaOS,$j,'descricao');

				  echo "<td style='font-size: 9px; font-family: verdana' width='50' nowrap ><a href='os_press.php?os=$os'  target='_blank'>".$sua_os."</a></td>";
				  echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
				  echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
				  echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_referencia ." - ". substr($produto_descricao,0,30) ."</acronym></td>";
				  if ($aprovacao == '15' or $aprovacao == '19') {
					  echo "<td>$nome_completo</td>";
				  }

				  echo "<td style='font-size: 9px; font-family: verdana'>".$data_status. "</td>";
				  echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
				  echo "<td style='font-size: 9px; font-family: verdana' nowrap>".$status_observacao. "</td>";
				  echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$obs_reincidencia."'>".substr($obs_reincidencia,0,30). "</acronym></td>";
				  echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$defeito_constatado_codigo ." - ".$defeito_constatado_adicional. "</td>";
				  echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$grupo_defeito_constatado. "</td>";
				  echo "<td style='font-size: 9px; font-family: verdana' nowrap>".number_format($total_pecas,2,',','.'). "</td>";
				  echo "<td style='font-size: 9px; font-family: verdana' nowrap>".number_format($mao_de_obra,2,',','.'). "</td>";
				  echo "<td style='font-size: 9px; font-family: verdana' nowrap>".number_format($valor_km,2,',','.'). "</td>";
				  echo "<td style='font-size: 9px; font-family: verdana' nowrap>".number_format($total_os,2,',','.'). "</td>";
				  echo "</tr>";


			    }

			  }

			}

		}
		echo "</tbody>";
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";
		if(trim($aprova) == 'aprovacao'){
			echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
			echo "<select name='select_acao' size='1' class='frm' >";
			echo "<option value=''></option>";
			echo "<option value='19'";  if ($_POST["select_acao"] == "19")  echo " selected"; echo ">APROVADO</option>";
			echo "<option value='15'";  if ($_POST["select_acao"] == "15")  echo " selected"; echo ">RECUSADO</option>";
			echo "</select>";
			echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' >";
			echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'>";
			echo "</td>";
		}
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "</form>";
	}else{
		echo "<center>Nenhuma OS encontrada.</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>
