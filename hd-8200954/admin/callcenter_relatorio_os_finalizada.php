<?php

include 'dbconfig.php'; 
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "Relação de OS's Finalizadas";

include "cabecalho.php";

?>
<style>
	.msg_erro{
		background-color: #ff0000;
		color: #fff;
		font-weight: bold;
	}
</style>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" src="js/jquery_1.js"></script>
<script type="text/javascript" src="js/grafico/highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>



<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

/* POP-UP IMPRIMIR */
	function abrir(URL) {
		var width = 700;
		var height = 500;
		var left = 90;
		var top = 90;

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
	}

</script>

<?php
$btn_acao = $_POST['btn_acao'];
$xls = '';

if(strlen($btn_acao)>0){
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];	
	$cliente_admin 		= $_POST['cliente_admin'];

	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		if(strlen($msg_erro) == 0)
			$msg_erro = "Data Inválida, Data Inicial sem valor";
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{		
		if(strlen($msg_erro) == 0)
			$msg_erro = "Data Inválida, Data Final sem valor";
	}	

	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		$timestamp_data1 = mktime(0,0,0,$m,$d,$y);
		if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		$timestamp_data2 = mktime(0,0,0,$m,$d,$y);
		if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}

	if($xdata_inicial > $xdata_final and strlen($msg_erro) == 0)
		$msg_erro = "Data Inválida, Data Inicial maior do que Data Final";

	if(strlen($msg_erro) == 0){
		$segundos_diferenca = $timestamp_data2 - $timestamp_data1;
		$dias_diferenca = $segundos_diferenca / (24*60*60);		
		if($dias_diferenca > 31){
			$msg_erro = "Período inválido, Escolha um período de 31 dias";
		}
	}

	if(strlen($msg_erro) == 0){
		if (strlen($btn_acao)>0 and strlen($msg_erro) == 0) {

			if(strlen($cliente_admin) > 0){
				$and_aux = " AND tbl_hd_chamado.cliente_admin = $cliente_admin ";
			}else{
				$and_aux = "";
			}


			$sql = "
			SELECT tbl_hd_chamado.hd_chamado,
				TO_CHAR(tbl_hd_chamado.data, 'MM/YYYY') as mes,
				tbl_cidade.estado as estado,
				TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY HH:MM:SS') as data_abertura,
				TO_CHAR(tbl_hd_chamado.resolvido, 'DD/MM/YYYY HH:MM:SS') as data_solucao,
				tbl_defeito_constatado.descricao as defeito_constatado,
				tbl_os.os,
				tbl_cidade_sla.hora as prazo,
				tbl_hd_chamado.data as data
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
			JOIN tbl_os on tbl_hd_chamado_extra.os = tbl_os.os
			JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_cidade_sla ON tbl_cidade.nome = tbl_cidade_sla.cidade
			WHERE tbl_hd_chamado.fabrica = $login_fabrica
			AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:00' $and_aux
			";			
			$res = pg_exec($con,$sql);

			if (pg_num_rows($res) > 0) {
				$xls.= '
					<table border="1">
						<tr>
							<td>#</td>
							<td>Chamado</td>
							<td>Mês</td>
							<td>Estado</td>
							<td>Data Abertura</td>
							<td>Data Solução Limite</td>
							<td>Data Solução Finalizado</td>
							<td>Defeito Constatado</td>
							<td colspan="__COLSPAN_PECAS__">Peças</td>
						</tr>
				';
				$statmt = 'select tbl_peca.referencia, tbl_peca.descricao, tbl_os_item.qtde
							from tbl_os_item
							join tbl_peca on tbl_peca.peca = tbl_os_item.peca
							join tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
							join tbl_os on tbl_os.os = tbl_os_produto.os
							where tbl_os.os = $1';
				$prepare = pg_prepare($con, "pecas", $statmt);

				$line_count = 0;
				while ($fetch = pg_fetch_assoc($res)) {
					$hd_chamado = $fetch['hd_chamado'];
					$mes = $fetch['mes'];
					$estado = $fetch['estado'];
					$data_abertura = $fetch['data_abertura'];					
					$data_solucao = $fetch['data_solucao'];
					$defeito_constatado = $fetch['defeito_constatado'];
					$prazo = (!empty($fetch['prazo'])) ? (int) $fetch['prazo'] : 20;
					$os = $fetch['os'];

					date_default_timezone_set('America/Sao_Paulo');
					$date = new DateTime($fetch['data']);
					
					$data_limite = 0;
					$horas_diff  = 0;					
					while ($data_limite == 0) {
						/**
						 * N - Representação numérica ISO-8601 do dia da semana (adicionado no PHP 5.1.0) 
						 *	1 (para Segunda) até 7 (para Domingo)
						 */
						$dia_semana = (int) $date->format('N');

						if ($dia_semana < 6) {
							$hora_final_dia = new DateTime($date->format('Y-m-d 20:00:00'));
							$diferenca = $date->diff($hora_final_dia);				
							$horas_diff+= (int) $diferenca->format('%h');

							if ($prazo <= $horas_diff) {
								$data_limite = $date->format('d/m/Y h:i:s');
							} else {
								$date->add(new DateInterval('P1D'));
								$date = new DateTime($date->format('Y-m-d 07:30:00'));
							}
						}
						elseif ($dia_semana == 6) {
							$hora_final_dia = new DateTime($date->format('Y-m-d 16:00:00'));
							$diferenca = $date->diff($hora_final_dia);				
							$horas_diff+= (int) $diferenca->format('%h');

							if ($prazo <= $horas_diff) {
								$data_limite = $date->format('d/m/Y h:i:s');
							} else {
								$date->add(new DateInterval('P2D'));
								$date = new DateTime($date->format('Y-m-d 07:30:00'));
							}
						} else {
							$date->add(new DateInterval('P1D'));
							$date = new DateTime($date->format('Y-m-d 07:30:00'));
						}
					}

					$xls.= "
						<tr>
							<td>$line_count</td>
							<td>$hd_chamado</td>
							<td>$mes</td>
							<td>$estado</td>
							<td>$data_abertura</td>
							<td>$data_limite</td>
							<td>$data_solucao</td>
							<td>$defeito_constatado</td>
					";
					$line_count += 1;

					$execute = pg_execute($con, "pecas", array($os));
					$rows = pg_num_rows($execute);
					$pecas = '';

					if ($rows > 0) {
						$colspan = $rows * 3;						
						$xls = str_replace('__COLSPAN_PECAS__', $colspan, $xls);

						while ($fetch_pecas = pg_fetch_assoc($execute)) {
							$pecas.= '<td>' . $fetch_pecas['qtde'] . '</td>';
							$pecas.= '<td>' . $fetch_pecas['referencia'] . '</td>';
							$pecas.= '<td>' . $fetch_pecas['descricao'] . '</td>';
						}
					} else {						
						$pecas.= '<td colspan="__COLSPAN_PECAS__">&nbsp;</td>';
					}

					$xls.= $pecas;

				}

				$xls = str_replace('__COLSPAN_PECAS__', '1', $xls);
				$xls.= '</table>';
			} else {
				$xls = 'Nenhum Resultado Encontrado';
			}
		}
	}
}

/* select dr's */

$sql = "select cliente_admin,nome from tbl_cliente_admin where fabrica = $login_fabrica";
$res_cliente_admin = pg_exec($sql);	
if(pg_num_rows($res_cliente_admin)>0){
	$linhas = pg_num_rows($res_cliente_admin);	
	for($i=0;$i<$linhas;$i++){
		$xcliente_admin = pg_result($res_cliente_admin,$i,cliente_admin);
		$xnome = pg_result($res_cliente_admin,$i,nome);	
		if($cliente_admin == $xcliente_admin){
			$selected = "selected";
		}else{
			$selected = "";
		}
		$option_drs .= "<option $selected value='$xcliente_admin'>$xnome</option>";
	}
}else{
	$option_drs = "";
}

/*-------------*/

?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if(strlen($msg_erro)>0){ ?>
		<tr class='msg_erro'><td><? echo $msg_erro ?></td></tr>
	<? } ?>
	<tr class='titulo_tabela'>
		<td>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>

				<tr>					
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Data Inicial</td>
					<td align='left' nowrap>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
					</td>
					<td align='right' nowrap><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">						
					</td>					
					<td align='right' nowrap><font size='2'>DR's</td>
					<td align='left' nowrap>
						<select style='width:180px' name="cliente_admin">
							<option></option>
							<?php
							echo $option_drs;
							?>
						</select>
					</td>
					<td width="10">&nbsp;</td>
				</tr>			
			</table><br>
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
		</td>
	</tr>
</table>
</FORM>

<br />

<div id="container" style="width: 700px; margin: 0 auto">
	<?php
	if (!empty($xls)) {
		if ($xls == "Nenhum Resultado Encontrado") {
			echo '<center>' . $xls . '</center>';
		} else {
			date_default_timezone_set('America/Sao_Paulo');
			$filename = 'xls/relatorio_os_finalizada-' . $login_admin . date('Ymd') . '.xls';
			$handle = fopen($filename, 'w');
			fwrite($handle, $xls);
			fclose($handle);

			echo '<center><a href="' . $filename . '" target="_blank">Download do Resultado</a></center>';
		}
	}
	?>
</div>

<?php include "rodape.php" ?>
