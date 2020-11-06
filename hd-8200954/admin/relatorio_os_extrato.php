<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

/* Área do Admin    */
//Opções: 'auditoria', 'cadastros', 'call_center', 'financeiro', 'gerencia'  'info_tecnica'
$admin_privilegios = "financeiro";
include 'autentica_admin.php';

/*------------------*/
include 'funcoes.php';
// Opcional
include '../helpdesk/mlg_funciones.php'; //Admin

/* Include cabeçalho Admin */
	$title = "RELATÓRIO ANALÍTICO DE DEFEITO";
	//Opções: 'cadastro', 'callcenter', 'financeiro', 'gerencia', 'tecnica'
	$layout_menu = 'gerencia';
	include "cabecalho.php";
	extract(array_filter($_POST, 'anti_injection')); // cria as variáveis com os campos do formulário

// Style para relatórios (formulário + tabela de resultados) para a área do admin
include "../js/js_css.php";

if(empty($tipo_data))
    $tipo_data = 'geracao';
?>
<script type="text/javascript" charset="utf-8">
    $(function(){
        $('#data_inicial').datepick({startDate:'01/01/2000'})
						  .mask("99/99/9999");
        $('#data_final').datepick({startDate:'01/01/2000'})
						.mask("99/99/9999");
		$('button[type=reset]').click(function() {
			$('input[name^=data]').val('');
			return false;
		});
    });

function geraRelatorio (){
	$("#erro").toggle();
	var data_inicial = $('#data_inicial').val();
	var data_final   = $('#data_final').val();
	var status_os    = $('#status_os').val();
	var tipo_data    = $('input[name=tipo_data]:checked').val();

	$.ajax({
		url:"gerador_relatorio_os_extrato.php",
		type:"POST",
		dataType:"JSON",
		data: {
			"data_inicial":data_inicial,
		    "data_final":data_final,
		    "status_os":status_os,
		    "tipo_data":tipo_data
		},
		beforeSend: function(){
			$("#msg_ajax").show();
		},
		complete:function(data){

			data = data.responseText;
			console.log(data)
			data = $.parseJSON(data)

			if(data.erro == 'true'){
				alert(data.msg);
			}else{
				window.open(data.msg,"_blank");
			}
			$("#msg_ajax").hide();
		}

	});

}

 </script>

<style type="text/css">
.menu_top{text-align:center;font:normal bold 10px Verdana,Geneva,Arial,Helvetica,sans-serif;border:1px solid;color:#596d9b;background-color:#d9e2ef;}
.border{border:1px solid #ced7e7;}
.table_line{text-align:center;font:normal normal 10px Verdana,Geneva,Arial,Helvetica,sans-serif;font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;border:0px solid;background-color:white;}
input{font-size:10px;}
.top_list{text-align:center;font:normal bold 10px Verdana,Geneva,Arial,Helvetica,sans-serif;color:#596d9b;background-color:#d9e2ef;}
.line_list{text-align:left;font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;font-size:x-small;font-weight:normal;color:#596d9b;background-color:white;}
caption, .titulo_tabela {background-color:#596d9b;font:bold 14px "Arial";color:white;text-align:center;}
thead, .titulo_coluna {background-color:#596d9b;font:bold 11px "Arial";color:white;text-align:center;}
.formulario{background-color:#D9E2EF;font:normal normal 11px Arial;width:700px;margin:auto;text-align:left;}
.msg, .msg_erro{background-color:#FF0000;font:bold 16px "Arial";color:white;text-align:center;}
.formulario caption{padding: 3px;}
.msg{background-color:#51AE51;color:white;}
table.tabela tr td{font-family:verdana;font-size:11px;border-collapse:collapse;border:1px solid #596d9b;}
.texto_avulso{font:14px Arial;color:rgb(89,109,155);background-color:#d9e2ef;text-align:center;width:700px;margin:0 auto;border-collapse:collapse;border:1px solid #596d9b;}
</style>


<table id = "erro" width='700' align='center' border='0' bgcolor='#d9e2ef'>
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

<form method="post" name="frm_os_aprovada" action="gerador_relatorio_os_extrato.php">
	<table class="formulario" border='0' cellpadding='5' cellspacing='2'>
		<caption>PARÂMETROS DA CONSULTA - ANALÍTICO DE DEFEITO</caption>
		<tr><td colspan='3'>&nbsp;</td></tr>
		<tr>
			<td style='width:135px'>&nbsp;</td>
			<td style='width:165px'>
				<label for="data_inicial">Data Inicial *</label><br>
                <input id="data_inicial" maxlength="10" name="data_inicial" size='12' type="text" class="frm" value="<?=$data_inicial?>">
			</td>
			<td>
				<label for="data_final">Data Final *</label><br>
                <input id="data_final" maxlength="10" name="data_final" size='12' type="text" class="frm" value="<?=$data_final?>">
			</td>
		</tr>
        <?php if(in_array($login_fabrica, array(30))){?>
        <tr name="status_os" <? if($tipo_data!="analitico_defeito" && $tipo_data!="analitico_defeito_pecas" ) echo "style='display: none;'" ?> >
        	<td style='width:135px'>&nbsp;</td>
			<td >Status da OS</td>
        </tr>
        <tr name="status_os" <? if($tipo_data!="analitico_defeito" && $tipo_data!="analitico_defeito_pecas" ) echo "style='display: none;'" ?> >
        	<td style='width:135px'>&nbsp;</td>
			<td style='width:200px' colspan='1'>
				<select id="status_os" name="status_os">
					<option value="">Selecione o status da OS</option>
					<option value="os_aberto">Apenas OS em Aberto</option>
					<option value="os_fechada">Apenas OS Fechadas</option>
				</select>
			</td>
        </tr>

		<tr>
			<td style='width:135px'>&nbsp;</td>
			<td style='width:200px' colspan='1'>
				<fieldset>
                    <legend> Tipo de Data </legend>
                    <input type='radio' name='tipo_data' value='geracao'	onclick="javascript:$('tr[name=status_os]').hide();"		id='geracao' <?php if($tipo_data == 'geracao') echo ' checked="checked" '?> /><label for='geracao'>Geração do Extrato</label><br />
                    <input type='radio' name='tipo_data' value='analitico_defeito' 		 onclick="javascript:$('tr[name=status_os]').show();"	id='analitico_defeito'  <?php if($tipo_data == 'analitico_defeito') echo ' checked="checked" '?> /><label for='analitico_defeito'>Analítico de Defeito</label><br />
                    <input type='radio' name='tipo_data' value='analitico_defeito_pecas' onclick="javascript:$('tr[name=status_os]').show();"	id='analitico_defeito_pecas'  <?php if($tipo_data == 'analitico_defeito_pecas') echo ' checked="checked" '?> /><label for='analitico_defeito_pecas'>Analítico de Defeito com Peças</label><br />
                </fieldset>
			</td>
            <td>&nbsp;</td>
		</tr>
        <?php }else{?>
            <input type='hidden' name='tipo_data'  value='geracao' />
        <?php }?>
		<tr style='text-align:center!important; margin: 30px !important;'>
			<td colspan="3"><br />
				<input name="btn_acao" type="hidden" value='t' />
				<button value="" type='button' onclick="geraRelatorio()">Filtrar</button>
				&nbsp;&nbsp;&nbsp;
				<button value="" type='reset'>Limpar</button>
                <br /><br />
			</td>
		</tr>
	</table>
</form> <br/>
<div id="msg_ajax" style="display: none;"><img valign="absmiddle" src="imagens/loading.gif" /> <span style="display: inline-block; margin-left: 5px; top:-2px; position:relative; ">Carregando</span></div>
<? include 'rodape.php'; ?>
