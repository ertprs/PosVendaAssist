<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include "autentica_admin.php";
include 'funcoes.php';

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

include "../helpdesk.inc.php";// Funcoes de HelpDesk hd_chamado=2537875

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
				$cnpj         = trim(pg_result($res,$i,cnpj));
				$nome         = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
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

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if($select_acao == "107" AND strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo da reprovação da OS.<br>";
	}

	if(strlen($observacao) > 0){
		$observacao = " Observação: $observacao ";
	}else{
		$observacao = "Observação:";
	}

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	if (strlen($msg_erro)==0){

		for ($x=0;$x<$qtde_os;$x++){

			$xxos         = trim($_POST["check_".$x]);

			if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

				$res_os = pg_exec($con,"BEGIN TRANSACTION");

				$sql = "SELECT status_os
						FROM tbl_os_status
						WHERE status_os IN (105,106,107)
						AND os = $xxos
						ORDER BY data DESC
						LIMIT 1";
				$res_os = pg_exec($con,$sql);
				if (pg_numrows($res_os)>0){

					$status_da_os = trim(pg_result($res_os,0,status_os));
					if ($status_da_os == 105){

						/* Aprovar */
						if($select_acao == "106"){
							$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin)
									VALUES ($xxos,106,current_timestamp,'$observacao',$login_admin)";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$esmaltec_acao = 'aprovada';

						}

						/* Recusar */
						if($select_acao == "107"){
							$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin)
									VALUES ($xxos,107,current_timestamp,'$observacao',$login_admin)";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);

							/* Se recusado, excluir a OS - HD 26244 */
							$sql = "SELECT fn_os_excluida($xxos,$login_fabrica,$login_admin);";
							$res = @pg_exec ($con,$sql);
							$msg_erro = pg_errormessage($con);

							$esmaltec_acao = 'reprovada';
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

							$assunto = 'O.S. ' . $sua_os_email  . ' ' . $esmaltec_acao . ' da Auditoria garantia estendida - LGI';
							$msg = 'A OS ' . $sua_os_email . ' foi ' . $esmaltec_acao . ' da Auditoria garantia estendida - LGI por ' . $nome_admin . ' da Esmaltec.';
							$msg .= '<br/><br/>';
							$msg .= str_replace("Observação", "Motivo", $observacao);

							$headers  = 'MIME-Version: 1.0' . "\r\n";
							$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
							$headers .= 'From: Esmaltec <auditoria.sae@esmaltec.com.br>' . "\r\n";

							mail($posto_email, utf8_encode($assunto), utf8_encode($msg), $headers);
						}

					}

					$res = pg_exec($con,"COMMIT TRANSACTION");
				}else{
					$res = pg_exec($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "auditoria";

$title = "Aprovação de Garantia Estendida.";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>

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
function ver(os) {
	var url = "<? echo $PHP_SELF ?>?ver=endereco&os="+os;
	janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=550, height=300, top=18, left=0");
	janela_aut.focus();
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

function changeColorLine(os){

	$(".btn-interagir").each(function(idx,elem){
		if($(elem).data("os") == os){
			var tr = $(elem).parents("tr").first();
			$(tr).attr("style","background: #FFDC4C");
		}
	});
}

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


function gravarInteracao(linha,os,tipo) {
    var comentario = $.trim($("#comentario_"+linha).val());

    if (comentario.length == 0) {
        alert("Insira uma mensagem para interagir");
    } else {
        $.ajax({
            url: "ajax_grava_interacao_new.php",
            type: "GET",
            data: {
                linha: linha,
                os: os,
                tipo: tipo,
                comentario: comentario
            },
            beforeSend: function () {
                $("#interacao_"+linha).hide();
                $("#loading_"+linha).show();
            },
            complete: function(data){
                data = data.responseText;

                if (data == "erro") {
                    alert("Ocorreu um erro ao gravar interação");
                } else {
                    $("#loading_"+linha).hide();
                    $("#gravado_"+linha).show();

                    setTimeout(function () {
                        $("#gravado_"+linha).hide();
                    }, 3000);

                    $("#linha_"+linha).css({
                        "background-color": "#FFCC00"
                    });
                }

                $("#comentario_"+linha).val("");
                refreshInteracoes(linha, os);
            }
        });
    }
}

function box_interacao(os) {
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
//      var linha = new Number(linha+1);
        var table = document.getElementById('linha_'+linha);
//      alert(document.getElementById('linha_'+linha).innerHTML);
        table.style.background = "#FFCC00";
    }
}

function abreObs(os,codigo_posto,sua_os){
    janela = window.open("obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,"formularios",'resizable=1,scrollbars=yes,width=400,height=250,top=0,left=0');
    janela.focus();
}

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

</script>



<script type="text/javascript" charset="utf-8">
	$(function(){
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		//$("input[@rel='data_nf']").maskedinput("99/99/9999");
	});

</script>

<?

if($btn_acao == 'Pesquisar'){

	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$aprova       = trim($_POST['aprova']);
	$os           = trim($_POST['os']);

	if (empty($os) && empty($data_inicial) && empty($data_final) && empty($aprova)) {
		$msg_erro = "Preencha algum campo para pesquisa";
	}

	if (strlen($os)>0){
		$Xos = " AND os = $os ";
	}

	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		$aprovacao = "105";
	}elseif($aprova=="aprovacao"){
		$aprovacao = "105";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "106";
	}elseif($aprova=="reprovadas"){
		$aprovacao = "107";
	}

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_inicial) > 0 && strlen($data_final) == 0) {
		$msg_erro = "Data incorreta";
	}

	if (strlen($data_final) > 0 && strlen($data_inicial) == 0) {
		$msg_erro = "Data incorreta";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
}

if(strlen($msg_erro) > 0){
	echo "<div class='alert alert-danger'><h4>$msg_erro</h4></div>";
}

?>

<form name="frm_pesquisa" class='form-search form-inline tc_formulario' method="post" action="<?echo $PHP_SELF?>">

	<div class="titulo_tabela">Aprovação de Garantia Estendida</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='peca_referencia'>Número da OS</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $os ?>" class="frm">
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=($msg_erro == "Data incorreta") ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="span12">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Data incorreta") ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="span12">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>	
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="posto_codigo" id="codigo_posto" size="15"  value="<? echo $posto_codigo ?>" class="frm">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="posto_nome" id="descricao_posto" size="40"  value="<? echo $posto_nome ?>" class="frm">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>


<?php if($login_fabrica == 30){ $aAtendentes = hdBuscarAtendentes(); //hd_chamado=2537875 ?>
<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Inspetor:</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<select class='frm' name="admin_sap" id="admin_sap">
	      						<option value=""></option>
						      <?php foreach($aAtendentes as $aAtendente): ?>
					            <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo empty($aAtendente['nome_completo']) ? $aAtendente['login'] : $aAtendente['nome_completo'] ; ?></option>
					         <?php endforeach; ?>
					   		</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

<?php } ?>
		<br />
		<p>
			<b>Separar por OS:</b>
		</p>
		<div class='row-fluid'>
			<div class='span3'></div>
			<div class='span2'>
				 <label class="radio">
			        <INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>Em aprovação 
			    </label>
			</div>
			<div class='span2'>
			    <label class="radio">
			        <INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas
			    </label>
			</div>
			<div class='span2'>
			    <label class="radio">
			        <INPUT TYPE="radio" NAME="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas
			    </label>
			</div>
		</div>
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<IMG class='btn' onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Pesquisar'>
		<br /><br />

</form>


<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {

	$posto_codigo= trim($_POST["posto_codigo"]);


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

	$sql =  "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN (105,106,107) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN (105,106,107) ) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos
			;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			/* select os from  tmp_interv_$login_admin; */

			SELECT	tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.consumidor_nome                                      ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_estado                            ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					tbl_os.certificado_garantia                                 ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (105,106,107) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT admin FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (105,106,107) ORDER BY data DESC LIMIT 1) AS admin         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (105,106,107) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (105,106,107) ORDER BY data DESC LIMIT 1) AS status_descricao
				FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				$sql_add
				$cond_admin_sap";
				if($login_fabrica == 30 and $aprovacao == 107){
					$sql .= " WHERE tbl_os.fabrica = 0 ";
				}else{
					$sql .= " WHERE tbl_os.fabrica = $login_fabrica ";
				}
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
	}

	#if ($ip == "187.39.213.156"){
		#echo nl2br($sql);
		#exit;
	#}

	$res = pg_exec($con,$sql);

            if(pg_numrows($res)>0){
                        if(in_array($login_fabrica, array(30))) { ?>
                            <table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>
                                <tr>
                                    <td bgcolor='#FFCC00' width='30' style="border-radius: 3px;">&nbsp;</td>
                                    <td align='left'>Fábrica interagiu</td>
                                </tr>

                                <tr>
                                    <td bgcolor='#669900' width='30' style="border-radius: 3px;">&nbsp;</td>
                                    <td align='left'>Posto interagiu</td>
                                </tr>
                            </table>                    
                        <? } ?>
                        	</div>
                        <?

		echo "<br /><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";

		echo "<table class='table table-bordered table-large'>";
		echo "<thead><tr class='titulo_coluna'>";
		echo "<th bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></th>";
		echo "<th bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></th>";
		echo "<th bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>DIGITAÇÃO</B></font></th>";
		echo "<th bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></th>";
		echo "<th bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></th>";
		echo "<th bgcolor='#485989'><font color='#FFFFFF'><B>LGI</B></font></th>";
		echo "<th bgcolor='#485989'><font color='#FFFFFF'><B>STATUS</B></font></th>";
		echo "<th bgcolor='#485989'><font color='#FFFFFF'><B>ADMIN</B></font></th>";
		echo "<th bgcolor='#485989'><font color='#FFFFFF'><B>OBSERVAÇÃO</B></font></th>";
		if(in_array($login_fabrica, array(30))) {
			echo "<th bgcolor='#485989'><font color='#FFFFFF'><B>INTERAGIR</B></font></th>";
		}
		echo "</tr></thead>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x=0; $x<pg_numrows($res);$x++){

			$os						= pg_result($res, $x, os);
			$sua_os					= pg_result($res, $x, sua_os);
			$data_digitacao			= pg_result($res, $x, data_digitacao);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$consumidor_nome		= pg_result($res, $x, consumidor_nome);
			$posto_nome				= pg_result($res, $x, posto_nome);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_result($res, $x, voltagem);
			$status_os				= pg_result($res, $x, status_os);
			$certificado_garantia	= pg_result($res, $x, certificado_garantia);
			$status_observacao		= pg_result($res, $x, status_observacao);
			$status_descricao		= pg_result($res, $x, status_descricao);
			$admin            		= pg_result($res, $x, admin);

			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

			if(strlen($sua_os)==o){
				$sua_os=$os;
			}

			if(in_array($login_fabrica, array(30))){
                                            $ultima_interacao = ultima_interacao($sua_os);
                                            switch ($ultima_interacao) {
                                                case "fabrica":
                                                    $cor = "#FFDC4C";
                                                    break;

                                                case "posto":
                                                    $cor = "#A6D941";
                                                    break;

                                            }

				echo "<input type=\"hidden\" value=\"$posto_codigo\" name=\"posto_codigo\" />"; //hd_chamado=2537875
				echo "<input type=\"hidden\" value=\"$nome_posto\" name=\"posto_nome\" />"; //hd_chamado=2537875
				echo "<input type=\"hidden\" value=\"$admin_sap\" name=\"admin_sap\" />"; //hd_chamado=2537875
			}

			echo "<tr bgcolor='$cor' id='linha_$x'>";
				echo "<td align='center' width='0'>";
					if($status_os==105){
						echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
						if (strlen($msg_erro)>0){
							if (strlen($_POST["check_".$x])>0){
								echo " CHECKED ";
							}
						}
						echo ">";
					}
				echo "</td>";
				if($status_descricao=='OS LGI Reprovada'){
					echo "<td style='font-size: 9px; font-family: verdana' nowrap >".$sua_os."</td>";
				}else{
					echo "<td style='font-size: 9px; font-family: verdana' nowrap ><a href='os_press.php?os=$os'  target='_blank'>".$sua_os."</a></td>";
				}
				echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
				echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
				echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ." - ". $produto_descricao ."</acronym></td>";
				echo "<td style='font-size: 9px; font-family: verdana'>".$certificado_garantia. "</td>";
				//if(strlen($status_descricao)==0){
				//	$status_descricao = 'sem status';
				//}
				echo "<td style='font-size: 9px; font-family: verdana' nowrap>".$status_descricao. "</td>";
				if(strlen($admin)==0){
					echo "<td style='font-size: 9px; font-family: verdana' nowrap>&nbsp;</td>";
				}else{
					$sql_login = "select login from tbl_admin where admin = $admin";
					$res_login = @pg_exec($con,$sql_login);
					$login_status = @pg_result($res_login,0,login);
					echo "<td style='font-size: 9px; font-family: verdana' nowrap>$login_status</td>";
				}
				echo "<td style='font-size: 9px; font-family: verdana' nowrap>".$status_observacao."</td>";
				if(in_array($login_fabrica, array(30))){
                    $sqlint = "SELECT os_interacao, admin
                                        FROM tbl_os_interacao
                                        WHERE os = {$os}
                                        AND interno IS NOT TRUE
                                        ORDER BY os_interacao DESC
                                        LIMIT 1";
                    $resint = pg_query($con, $sqlint);

                    if (pg_num_rows($resint) == 0) {
                        $botao = "Interagir <img title='Enviar Interação com Posto' />";
                    } else {
                        $admin = pg_fetch_result($resint, 0, "admin");

                        if (strlen($admin) > 0) {
                            $botao = "Interagir <img title='Aguardando Resposta do Posto' />";
                        } else {
                            $botao = "Visualizar<img title='Posto Respondeu, clique aqui para visualizar' />";
                        }
                    } 

                    if ($login_fabrica == 30) { ?>
                    	<td>
                            <div class='btn btn-primary' style='cursor: pointer;' onclick="box_interacao(<?=$os?>)">
                                <?= $botao; ?>
                            </div>
                        </td>
            <?      } else { ?>
                        <td>
                            <div class='btn btn-primary' id="btn_interacao_<?=$x?>" style='cursor: pointer;' onclick='if ($("#interacao_<?=$x?>").is(":visible")) { $("#interacao_<?=$x?>").hide(); } else { $("#interacao_<?=$x?>").show(); }'>
                                <?= $botao; ?>
                            </div>
                        </td>
			<?  	} 
				} ?>
			</tr>
	<?  } ?>
		<input type='hidden' name='qtde_os' value='<?= $x; ?>'>
		  <tr>
		      <td height='20' bgcolor='#485989' colspan='100%' align='left'>
			<? if(trim($aprova) == 'aprovacao') { ?>
                                            &nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><b>COM MARCADOS:</b></font> &nbsp;
                                            <select name='select_acao' size='1' class='frm' >
                                                <option value=''></option>
                                                <option value='106'<?= ($_POST["select_acao"] == "106") ? " selected" : ""; ?>>APROVADO</option>
                                                <option value='107'<?= ($_POST["select_acao"] == "107") ? " selected" : ""; ?>>RECUSADO</option>
                                            </select>
                                            &nbsp;&nbsp;<font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' />
                                            &nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0' />
	                           <? } ?>
                               </td>
                                <input type='hidden' name='btn_acao' value='Pesquisar'>
                            </table>
                        </form>
            <? } else { ?>
               		<div class="alert alert-warning"><h4>Nenhum OS encontrada</h4></div>
            <? }
                $msg_erro = '';
}

include "rodape.php" ?>
