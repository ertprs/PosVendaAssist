<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

/* Área do Admin    */
//Opções: 'auditoria', 'cadastros', 'call_center', 'financeiro', 'gerencia'  'info_tecnica'
$admin_privilegios = "gerencia";
include 'autentica_admin.php';

/*------------------*/
include 'funcoes.php';
// Opcional
include '../helpdesk/mlg_funciones.php'; //Admin

if (count(array_filter($_POST)) > 1 and $_POST['btn_acao'] == 'download') {
	$link    = $_POST['arquivo'];
	//$arquivo = '/var/www/assist/www/admin/xls/' . basename($link);
	$arquivo = "xls/$link";
	if (!preg_match("/atualizacao_posto_(?:des)?credenciado_\d{4}(?:-\d\d){2}_\d{2,7}\.xls/", $link)) {$msg_erro = 'Nome do arquivo inválido!';}
	if (!file_exists($arquivo)) {$msg_erro = 'Arquivo não localizado! Gere novamente.';}
	if (!$msg_erro) {
		header('Content-type: application/msexcel');
		header("Content-Disposition: attachment; filename=" . basename($arquivo));
		readfile($arquivo);
		exit();
	}
}

/*if (count(array_filter($_POST)) > 1 and $_POST['btn_acao'] == 'todos') {
	exec("/usr/bin/perl /var/www/cgi-bin/bosch/atualizacao-posto_415083.pl", $resposta, $error_code);
	if ($error_code != 0) {
		$msg_erro = 'Erro ao gerar e enviar os arquivos por e-mail. Por favor, tente novamente em alguns segundos.<br>'.
					'Se o erro persisteir, contate com a Telecontrol.';
	} else {$msg = 'E-mail enviado para <b>Robson Gastão</b> com a atualização dos postos!';}
} // Não foi solicitado fazer on-line... */

//echo nl2br(print_r($_POST, true));
if (/*count(array_filter($_POST)) > 1 and */$_POST['btn_acao'] == 'consultar') {
	$posto_codigo = anti_injection($_POST['posto_codigo']);
	if ($posto_codigo) { // Produra o ID do posto
		$sql = "SELECT posto, credenciamento FROM tbl_posto_fabrica WHERE codigo_posto = '$posto_codigo' AND fabrica = $login_fabrica";
		$res = @pg_query($con, $sql);
		if (is_resource($res) and @pg_num_rows($res) > 0) {
			$posto = pg_fetch_result($res, 0, 0);
			$status= substr(pg_fetch_result($res, 0, 'credenciamento'), 0, 1);
			$comando = "";
			$link_arquivo = exec("php ../rotinas/bosch/atualizacao-posto.php $posto $status");
			//system("php ../rotinas/bosch/atualizacao-posto.php $posto $status");						
		} else {
			$msg_erro = "Posto $posto_codigo não encontrado!";
		}
	} else { // Erro!
		$msg_erro = 'Por favor, selecione o posto a consultar.';
	}
}

/* Include cabeçalho Admin */
	$title = "RELATÓRIO DE CREDENCIAMENTO DE POSTOS AUTORIZADOS";
	//Opções: 'cadastro', 'callcenter', 'financeiro', 'gerencia', 'tecnica'
	$layout_menu = 'gerencia';

include "cabecalho.php";

// Style para relatórios (formulário + tabela de resultados) para  aárea do admin
?>
<style type="text/css">

.menu_top {
	text-align: center;
	font: normal bold 10px Verdana, Geneva, Arial, Helvetica, sans-serif;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef;
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font: normal normal 10px Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	border: 0px solid;
	background-color: white;
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font: normal bold 10px Verdana, Geneva, Arial, Helvetica, sans-serif;
	color:#596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: white;
}

caption,.titulo_tabela {
	background-color:#596d9b;
	font: bold 14px "Arial";
	color: white;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color: white;
	text-align:center;
}

.formulario {
	background-color:#D9E2EF;
	font: normal normal 11px Arial;
	table-layout: fixed;
}

.msg,.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color: white;
	text-align:center;
}

.msg{
	background-color:#51AE51;
	color: white;
}

table.tabela tr td {
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.texto_avulso {
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width: 700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<!-- ARQUIVOS PARA CARRREGAR JANELA MODAL ------>
    <script type='text/javascript' src='js/modal/ajax.js'></script>
    <script type='text/javascript' src='js/modal/modal-message.js'></script>
    <script type='text/javascript' src='js/modal/ajax-dynamic-contentt.js'></script>
    <script type='text/javascript' src='js/modal/main.js'></script>
    <link rel='stylesheet' href='css/modal/modal-message.css' type='text/css'>
    <!-- -------------------------------------------->

    <!-- ARQUIVOS PARA MONTAR TABELA DE PAGINAÇÃO --->
    <script src='js/jquery.js' type='text/javascript'></script>
    <script src='js/table/jquery.dataTables.js' type='text/javascript'></script>
    <script src='js/table/demo_page.js' type='text/javascript'></script>
    <script src='js/table/jquery-ui-1.7.2.custom.js' type='text/javascript'></script>
    <!-- ---------------------------------------- -->


    <!--- CSS DA TABELA DE PAGINAÇÃO ---------------->
    <link rel='stylesheet' href='css/table/demo_table_jui.css' type='text/css' />
    <link rel='stylesheet' href='css/table/jquery-ui-1.7.2.custom.css' type='text/css' />

<script type="text/javascript">

   try{
        xmlhttp = new XMLHttpRequest();
    }catch(ee){
        try{
            xmlhttp = new ActiveXObject('Msxml2.XMLHTTP');
        }catch(e){
            try{
                xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
            }catch(E){
                xmlhttp = false;
            }
        }
    }

    //FUNÇÃO DA PAGINAÇÃO
    function fnFeaturesInit () {
        $('ul.limit_length>li').each( function(i) {
            if ( i > 10 ) {
                this.style.display = 'none';
            }
        } );

        $('ul.limit_length').append( '<li class="css_link">Mais<\/li>' );
        $('ul.limit_length li.css_link').click(function () {
            $('ul.limit_length li').each(function(i) {
                if ( i > 5 ) {
                    this.style.display = 'list-item';
                }
            });
            $('ul.limit_length li.css_link').css( 'display', 'none' );
        });
    }


    function closeMessage_1(){
        messageObj.close();//FECHA A JANELA MODAL
    }

	function preenche_campo(campo, valor) {
        //VERIFICA SE CAMPO EXISTE NO FORMULARIO
        var objnome1 = document.getElementsByName(campo).length;
        if(valor != '' && objnome1  == '1'){
            //LIMPA CAMPO
            document.getElementById(campo).value = '';
            //ADICIONA CONTEUDO
            document.getElementById(campo).value = valor;
        }
	}

	function Fechar_popup() { // Fecha depois de 2 seg.
		setTimeout('closeMessage_1()',2500);
	}

    function busca_dados_1(tipo, param) {
		var valor = document.getElementById(param).value;
		if (valor.replace(/(^\s+|\s+$)/g, '').length < 3) {
			alert("Digite pelo menos três caracteres para iniciar a pesquisa");
			return false;
		}
        //MONTA A JANELA MODAL
		displayMessage('pesquisa_posto_codigo.php?tipo='+tipo+'&posto='+valor,'800','500');
            $(document).mousemove( function() {
            //---TABLE DE PAGINAÇÃO---
            fnFeaturesInit();
            $(document).mousemove(function() {
                oTable = $('#example').dataTable({
                    'bJQueryUI': true,
                    'sPaginationType': 'full_numbers',
                    'bPaginate': true,
                    'iDisplayLength': 10,
                    //RETIRA EVENTO MOUSEMOVE DA JANELA MODAL
                    fnInitComplete:function() {
                        $(document).unbind('mousemove');
                    }
                });
            })
        });

    }

    function retorno(info){
		var vars = info.split('|');
		preenche_campo('posto_codigo', vars[0]);
		preenche_campo('posto_nome',   vars[1]);
		//alert("Info: " + vars[0] + " dado: " + vars[1]);
        messageObj.close();//FECHA A JANELA MODAL
    }
</script>
<script>
	$().ready(function() {
		$('#btn_limpar').click(function() {
			$('.formulario input').val('');
			$('.msg,.msg_erro').parent().parent().hide('fast');
			return false;
		});
		$('#download').click(function() {
			$(this).attr('disabled','disabled');
			$('#resultado').fadeOut('fast');
			$('form[name=xls]').submit();
			return false;
		});
		$('#consultar').click(function() {
			$('input#acao').val('consultar');
			$('form[name=frm_posto_atualiza]').submit();
		});
/*		$('#btn_todos').click(function() {
			$('input#acao').val('todos');
			$('form[name=frm_posto_atualiza]').submit();
		});*/
	});
</script>

<table width='700' align='center' border='0' bgcolor='#d9e2ef'>
<? if (strlen ($msg_erro) > 0) { ?>
	<tr class="msg_erro">
		<td> <? echo $msg_erro; ?></td>
	</tr>
<? } ?>

<? if (strlen ($msg) > 0) { ?>
	<tr class="msg">
		<td> <? echo $msg; ?></td>
	</tr>
<? } ?>
</table>
<form action="<?=$PHP_SELF?>" name="frm_posto_atualiza" style='margin:auto;text-align:center;' method="post">
	<table align='center' class="formulario" style='table-layout:fixed;width:700px'>
		<caption border='1'>Parâmetros de Pesquisa</caption>
		<thead>
			<tr>
				<th style='width:120px'>&nbsp;</th>
				<th style='width:135px'>&nbsp;</th>
				<th style='width:110px'>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody style='text-align:left;'>
			<tr>
				<td>&nbsp;</td>
				<td>
					<label for="data_inicial">&nbsp;Código do Posto *</label>
				</td>
				<td>&nbsp;</td>
				<td>
					<label for="data_final">&nbsp;Nome do Posto</label>
				</td>
			</tr>
			<tr>
				<td>&nbsp</td>
				<td>
					<input type="text" maxlength="20" size='15' class="frm" name="posto_codigo" id="posto_codigo" value="<?=$posto_codigo?>" />
					<img src="../imagens/lupa.png" border="0" style="cursor:pointer" onclick='busca_dados_1("codigo","posto_codigo");' align="absmiddle">
				</td>
				<td>&nbsp;</td>
				<td>
					<input type="text" maxlength="50" size='30' class='frm' name="posto_nome" id="posto_nome" value="<?=$posto_nome?>" />
					<img src="../imagens/lupa.png" border="0" style="cursor:pointer" onclick='busca_dados_1("nome","posto_nome");' align="absmiddle">
				</td>
			</tr>
			<tr><td colspan='4'>&nbsp;</td></tr>
			<tr>
				<td colspan="4" align='center' style='text-align:center!important'>
					<input type='hidden' id='acao' name='btn_acao' value='' />
					<button type='button' id='consultar' value='Consultar'>Consultar</button>
					&nbsp;&nbsp;&nbsp;
					<button name='btl_limpar' type='reset'  id='btn_limpar'>Limpar</button>
				</td>
			</tr>
<?/*			<tr><td colspan='4'>&nbsp;</td></tr>
			<tr>
				<td colspan="4" align='center' style='text-align:center!important'>
					<p>Para gerar e enviar os arquivos de atualização das <acronym title='Assistências Técnicas'>AT</acronym> por e-mail, clique no botão 'Gerar Arquivo'</p>
					<button  type='button' id='btn_todos' value='todos'
							title='Gera os arquivos de atualização e os envia por e-mail'>Gerar arquivos</button>
				</td>
			</tr>	*/?>
			<tr><td colspan='4'>&nbsp;</td></tr>
		</tbody>
	</table>
</form>
<p>&nbsp;</p>
<?if (isset($link_arquivo)) { ?>
<form action="<?=$PHP_SELF?>" method='post' target='_blank' name='xls'>
	<div id='resultado' align='center' style='text-align:center'>
			<input  type='hidden' name='arquivo'  value='<?=$link_arquivo?>'>
			<input  type='hidden' name='btn_acao' value='download'>
			<button type='submit' id='download' name='baixar'>Baixar Arquivo</button>
	</div>
</form>
<?}?>
<? include 'rodape.php'; ?>
