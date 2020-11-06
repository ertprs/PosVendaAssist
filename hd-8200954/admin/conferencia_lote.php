<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "financeiro";

include "autentica_admin.php";
include 'funcoes.php';

if($_POST['btn_acao_gravar']){
	$extratos_campo = $_POST['campo_json_formatado'];
	$data_pg  = $_POST['data_pg'];

	if($data_pg){
		list($d, $m, $y) = explode("/", $data_pg);
		if(!checkdate($m,$d,$y)) {
			$msg_erro = "Data de pagamento inválida";
		}else{
			$xdata_pg = $y.'-'.$m.'-'.$d;
		}
	}else{
		$msg_erro = "Informe data de pagamento";
	}
	
	if(empty($msg_erro)){
		if(empty($extratos_campo)){
			$msg_erro = "Informe pelo menos um extrato para pagamento";
		}else{			
			$extratos = json_decode(str_replace("\\","",$extratos_campo),true);
			foreach ($extratos[0] as $extrato => $total) {
				
				$sql = "SELECT extrato FROM tbl_extrato_pagamento WHERE extrato = $extrato";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) == 0){
					$sql = "INSERT INTO tbl_extrato_pagamento(
																extrato,
																valor_total,
																data_pagamento
															  ) VALUES(
															  	$extrato,
															  	$total,
															  	'$xdata_pg'
															  )";
					$res = pg_query($con,$sql);
					if(pg_last_error($con)){
						$msg_erro = pg_last_error($con);
					}else{
						$msg = "Gravado com sucesso";
					}
				}
			}
		}
	}else{
		if(!empty($extratos_campo)){
			$array_extrato = array();
			$extratos = json_decode($extratos_campo,true);
			foreach ($extratos[0] as $extrato => $total) {
				$array_extrato[] = $extrato;
			}
		}
	}
}

$layout_menu = "financeiro";
$title = "CONFERÊNCIA DE LOTE";
include "cabecalho.php";

include "../js/js_css.php";

?>

<script type='text/javascript'>
	$().ready(function() {

		$('input[name=data_pg]').datepick({startDate:'01/01/2000'});
		$("input[name=data_pg]").mask("99/99/9999");	

		$("input[name^='extrato_check'],#todos").change(function(){
			var json = {};
	        var array = new Array();
	        var grupo1 = {};

			$("input[name^='extrato_check']").each(function(){
				 if( $(this).is(":checked") ){
				 	var extrato = $(this).val();
				 	grupo1[extrato] = $(this).parent("td").parent("tr").find("input[name^=total_extrato]").val(); 

				 }
			});
			array.push(grupo1);

	        json = array;

	        console.log(JSON.stringify(json));
	        $("input[name=campo_json_formatado]").val(JSON.stringify(json));
		});

	});
	
	function selecionaTodos(){
		if( $("#todos").is(":checked") ){
			$("input[id^=extrato_check_]").attr("checked",true);
		}else{
			$("input[id^=extrato_check_]").attr("checked",false);
		}
	}

	function consultar(){
		var btn_acao = $("input[name=btn_acao_consultar]").val();
		if(btn_acao == ""){
			$("input[name=btn_acao_consultar]").val("consultar");
			$("form[name='frm_consulta']").submit();
		}else{
			alert("Aguarde subimissão");
		}
	}

	function gravar(){
		var btn_acao = $("input[name=btn_acao_gravar]").val();
		if(btn_acao == ""){
			$("input[name=btn_acao_gravar]").val("gravar");
			$("form[name='frm_cadastro']").submit();
		}else{
			alert("Aguarde subimissão");
		}
	}
	
</script>

<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

#relatorio thead tr th {

	cursor: pointer;

}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela{
	margin: auto;
	width: 700px;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.espaco{
	padding: 0 0 0 100px;
}

caption{
	height:25px; 
	vertical-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<?php
	if(!empty($msg_erro)){
?>
		<table align='center' width='700'>
			<tr class='msg_erro'>
				<td><?=$msg_erro?></td>
			</tr>
		</table>
<?php
	}
?>

<?php
	if(!empty($msg)){
?>
		<table align='center' width='700'>
			<tr class='sucesso'>
				<td><?=$msg?></td>
			</tr>
		</table>
<?php
	}
?>

<form name='frm_consulta' method='post'>
	<table width='700' align='center' class='formulario'>
		<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td align='center'>
				LOTE :
				<select name='lote' class='frm'</select>
					<option value=''>Selecioe um lote</option>
					<?php
						$sql = "SELECT distrib_lote,lote FROM tbl_distrib_lote WHERE fabrica = $login_fabrica AND fechamento notnull ORDER BY lote DESC";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							for($i = 0; $i < pg_num_rows($res); $i++){
								$distrib_lote = pg_fetch_result($res, $i, 'distrib_lote');
								$lote = pg_fetch_result($res, $i, 'lote');

								$selected = ($distrib_lote == $_POST['lote']) ? "selected" : "";

								echo "<option value='$distrib_lote' $selected>$lote - $login_fabrica_nome</option>";
							}
						}
					?>
				</select>
			</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td align='center'>
				<input type='hidden' name='btn_acao_consultar'>
				<input type='button' value='Pesquisar' onclick="javascript:consultar();">
			</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
	</table>
</form>
<br />
<?php
	if($_POST['lote']){
		$lote = $_POST['lote'];

		$sql = "SELECT distinct tbl_os_extra.extrato,
							tbl_posto_fabrica.codigo_posto, 
							tbl_posto.nome,
							tbl_posto.posto,
							tbl_distrib_lote_posto.total_sedex,
							tbl_extrato.total,
							to_char(tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY') AS data_pagamento
				FROM tbl_distrib_lote
				JOIN tbl_distrib_lote_os ON tbl_distrib_lote.distrib_lote = tbl_distrib_lote_os.distrib_lote
				JOIN tbl_distrib_lote_posto ON tbl_distrib_lote.distrib_lote = tbl_distrib_lote_posto.distrib_lote
				AND tbl_distrib_lote_posto.nf_mobra = tbl_distrib_lote_os.nota_fiscal_mo
				JOIN tbl_posto ON tbl_distrib_lote_posto.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_os_extra ON tbl_distrib_lote_os.os = tbl_os_extra.os
				JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato 
				AND tbl_extrato.fabrica = $login_fabrica AND tbl_extrato.posto = tbl_distrib_lote_posto.posto
				LEFT JOIN tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
				WHERE tbl_distrib_lote.fabrica = $login_fabrica
				AND tbl_distrib_lote.distrib_lote = $lote
				AND tbl_os_extra.extrato notnull
				AND tbl_os_extra.extrato > 0
				ORDER BY posto,extrato";
		#echo nl2br($sql);
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
?>
			<table class='tabela'>
				<tr class='titulo_tabela'>
					<td colspan='4' align='center'> 
						<form name='frm_cadastro' method='post'>
							Data Pagamento : 
							<input type='text' name='data_pg' id='data_pg' class='frm' size='10' value='<?=$data_pg?>'>
							<input type='hidden' name='campo_json_formatado' value='<?=$extratos_campo?>'>
							<input type='hidden' name='btn_acao_gravar'>
							<input type='hidden' name='lote' value='<?=$lote?>'>
							<input type='button' value='Gravar' onclick="javascript:gravar();"> 
						</form>
					</tr>
				</tr>

				<tr class='titulo_coluna'>
					<td align='center'>
						Todos <br /> 
						<input type='checkbox' name='todos' id='todos' onchange='javascript: selecionaTodos();' class='frm'>
					</td>
					<td align='left'>Posto</td>
					<td>Extrato</td>
				</tr>
<?php
				for($j = 0; $j < pg_num_rows($res); $j++){
					$posto 		 = pg_fetch_result($res, $j, 'posto');
					$extrato 	 = pg_fetch_result($res, $j, 'extrato');
					$posto_nome  = pg_fetch_result($res, $j, 'codigo_posto').' - '.pg_fetch_result($res, $j, 'nome');
					$total_sedex = pg_fetch_result($res, $j, 'total_sedex');
					$total 		 = pg_fetch_result($res, $j, 'total');
					$data_pagamento = pg_fetch_result($res, $j, 'data_pagamento');

					$total_extrato = ($posto != $posto_ant) ? ($total + $total_sedex) : $total;

					$cor = ($j % 2) ? "#F7F5F0" : "#F1F4FA";

					if(empty($data_pagamento)){
						$checked = (in_array($extrato,$array_extrato)) ? "checked" : "";
?>
						<tr bgcolor='<?=$cor?>'>
							<td align='center'>
								<input type='checkbox' <?=$checked?> rel='<?=$posto?>' name="extrato_check[]" id="extrato_check_<?=$extrato?>" value="<?=$extrato?>" class="frm">
							</td>
							<td align='left'>
								<?=$posto_nome?>
							</td>
							<td>
								<?=$extrato?>
								<input type='hidden' name='total_extrato[]' value="<?=$total_extrato?>">
							</td>				
						</tr>
<?php
					}
					$posto_ant = $posto;
				}
?>
			</table>		
<?php
		}
	}

	include "rodape.php";
?>
