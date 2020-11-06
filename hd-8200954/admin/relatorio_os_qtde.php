<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";
include "retorno_os_qtde.php";

$admin_privilegios = "auditoria";
$layout_menu = "auditoria";
$title = "RELATÓRIO DE OS DIGITADAS";

$btn_acao = $_POST['btn_acao'];

if($btn_acao == 'continuar'){
	$ano 		= $_POST['ano'];
	$data_in 	= $_POST['data_in'];
	$data_fl 	= $_POST['data_fl'];
	$estado 	= $_POST['estado'];

	if(empty($data_in) AND empty($data_fl) AND empty($ano)){
		$msg_erro = "Informe a data ou o Ano";
	}

	if(empty($msg_erro)){
		if(!empty($data_in) AND !empty($data_fl)){
			list($di, $mi, $yi) = explode("/", $data_in);
			if (!checkdate($mi,$di,$yi)) {
				$msg_erro = "Data Inválida";
			}
			
			list($df, $mf, $yf) = explode("/", $data_fl);
			if (!checkdate($mf,$df,$yf)){ 
				$msg_erro = "Data Inválida";
			}

			if (strlen($msg_erro) == 0) {

				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";

				if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
					$msg_erro = "Data Inválida";
				}

			}
			$cond .= " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
		}else if(!empty($ano)){
			$cond .= " AND tbl_os.data_abertura BETWEEN '$ano-01-01 00:00:00' and '$ano-12-31 23:59:59'";
		}

		if($estado){
			switch($estado){
				case 'Norte':
					$estado_consulta = "'AC','AP','AM','PA','RO','RR','TO'";
				break;

				case 'Nordeste':
					$estado_consulta = "'AL','BA','CE','MA','PB','PE','PI','RN','SE'";
				break;

				case 'Centro_oeste':
					$estado_consulta = "'DF','GO','MT','MS'";
				break;

				case 'Sudeste':
					$estado_consulta = "'ES','MG','RJ','SP'";
				break;

				case 'Sul':
					$estado_consulta = "'PR','RS','SC'";
				break;

				default: $estado_consulta = "'$estado'";
			}
		}
	}
	
}

include "cabecalho.php";
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */

?>
<script type="text/javascript" charset="utf-8">
	$(function() {
		Shadowbox.init();
		$("input[name^='data']").datepick({startDate:'01/01/2000'});
		$("input[name^='data']").mask("99/99/9999");
	});
</script>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
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

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

</style>
<form name='frm_os_qtde' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="" />
<table width='700' class="formulario" align='center' border='0' cellspacing='2' cellpadding='2'><?php
	if (strlen($msg_erro) > 0) {?>
		<tr class='msg_erro'>
			<td colspan=""><?php echo $msg_erro; ?></td>
		</tr><?php
	}?>
	<tr class='titulo_tabela'>
		<td colspan='3'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td width='120'>&nbsp;</td>
		<td>Data inicial</td>
		<td width='200'>Data final</td>
	</tr>
	<tr>
		<td width='100'>&nbsp;</td>
		<td><input class='frm' type='text' name='data_in' value='<?=$data_in?>' size='12' maxlength='20'></td>
		<td><input class='frm' type='text' name='data_fl' value='<?=$data_fl?>' size='12' maxlength='20'></td>
	</tr>
	<tr>
		<td width='100'>&nbsp;</td>
		<td align="left">Ano *</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td width='100'>&nbsp;</td>
		<td align="left">
			<select name='ano' class='frm'>
				<option value=''></option>
				<?php
					$ano_atual = date('Y');
					for($i = 2011; $i <= $ano_atual; $i++){
						$selected = ($i == $ano) ? "selected" : "";
						echo "<option value='$i' $selected>$i</option>";
					}
				?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td width='100'>&nbsp;</td>
		<td>Estado</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td width='100'>&nbsp;</td>
		<td>
			<select name="estado" id="estado" class="frm">
				<option value="">Selecione</option>
				<?php
				$regioes = array('Norte'=>'Região Norte(AC, AP, AM, PA, RO, RR, TO)','Nordeste'=>'Região Nordeste(AL, BA, CE, MA, PB, PE, PI, RN, SE)','Centro_oeste'=>'Região Centro-Oeste(DF, GO, MT, MS)','Sudeste'=>'Região Sudeste(ES, MG, RJ, SP)','Sul'=>'Região Sul(PR, RS, SC)');

				$estados = array('AC'=>'Acre', 'AL'=>'Alagoas', 'AM'=>'Amazonas', 'AP'=>'Amapá','BA'=>'Bahia','CE'=>'Ceará','DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás','MA'=>'Maranhão','MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'Pará','PB'=>'Paraíba','PR'=>'Paraná','PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte','RO'=>'Rondônia','RS'=>'Rio Grande do Sul','RR'=>'Roraima','SC'=>'Santa Catarina','SE'=>'Sergipe','SP'=>'São Paulo','TO'=>'Tocantins');

				$estados = array_merge($regioes,$estados);


				foreach($estados as $sigla => $nomeEstado) {
					$selected = ($estado == $sigla) ? ' selected="selected"' : null;
					echo '<option value="'.$sigla.'" '.$selected.'>'.$nomeEstado.'</option>'."\n";
				}?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="3" align="center">
			<input type="button" style='background:url(imagens_admin/btn_pesquisar_400.gif); width:400px; cursor:pointer;' value="&nbsp;" onclick="javascript: if (document.frm_os_qtde.btn_acao.value == '' ) { document.frm_os_qtde.btn_acao.value='continuar' ; document.frm_os_qtde.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
		</td>
	</tr>

</table>
</form>
<br />

<?php 

if($btn_acao AND empty($msg_erro)){

	ob_start();
	echo retornaResultado($estado_consulta,$cond);
	$excel = ob_get_contents();

	$arquivo = "xls/relatorio-qtde-os-$login_fabrica-".date('Y-m-d').".xls";
	$fp = fopen($arquivo,"w");
	fwrite($fp, $excel);
	fclose($fp);

	echo "<br> <input type='button' value='Download Excel' onclick=\"window.open('$arquivo');\">";
}

include "rodape.php";
