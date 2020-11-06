<?
//Callcenter _relatorio_atendimento 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE REMESSA À ECT PARA FECHAMENTO DE ORDENS DE SERVIÇO";

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


<script type="text/javascript">

	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

</script>

<? //include "javascript_pesquisas.php" ?>

<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];

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
			$sql = "
				SELECT 	o.os,
					d.codigo as defeito,
					pc.referencia,pc.descricao,
				       	pc.unidade,
					p.posto,
					oi.peca,
					oi.qtde ,
					oi.custo_peca,
					c.data,
					c.resolvido,
					e.array_campos_adicionais,
					e.serie,
					pr.codigo_barra,
					pr.descricao,
					pf.codigo_posto,
				       	s.descricao as descricao_servico
				FROM tbl_hd_chamado c
				JOIN tbl_hd_chamado_extra e on c.hd_chamado = e.hd_chamado
				JOIN tbl_os o on c.hd_chamado = o.hd_chamado
				JOIN tbl_posto p on o.posto = p.posto
				LEFT JOIN tbl_os_produto op on op.os = o.os
				LEFT JOIN tbl_os_item oi on oi.os_produto = op.os_produto
				JOIN tbl_produto pr on pr.produto = o.produto
				LEFT JOIN tbl_defeito_constatado d on d.defeito_constatado = o.defeito_constatado
				LEFT JOIN tbl_peca pc on pc.peca = oi.peca
				JOIN tbl_posto_fabrica pf on o.posto = pf.posto
				LEFT JOIN tbl_servico_realizado s on s.servico_realizado = oi.servico_realizado
				where c.fabrica = $login_fabrica
				AND   o.fabrica = $login_fabrica
				AND   pf.fabrica = $login_fabrica
				and c.data is not null
				and c.resolvido is not null
				and c.data between '".date('Y-m-d',$timestamp_data1)." 00:00:00' and '".date('Y-m-d',$timestamp_data2)." 23:59:00'
				and pf.fabrica = $login_fabrica
				order by c.hd_chamado
			";


			$res = pg_exec($con,$sql);
			if(pg_num_rows($res)>0){
				$linhas = pg_num_rows($res);
				for($i=0;$i<$linhas;$i++){
					$array_campos_adicionais = json_decode(pg_result($res,$i,array_campos_adicionais));
					if(count($array_campos_adicionais)>0){
						$numero_contrato = $array_campos_adicionais->numero_contrato;
						$numero_patrimonio_pib = $array_campos_adicionais->numero_patrimonio_pib;
						$erp_produto = $array_campos_adicionais->erp_produto;
					}else{
						$numero_contrato = "";
						$numero_patrimonio_pib = "";
						$erp_produto = "";
					}
					$table_lines .= "
									<tr>
										<td>2</td>
										<td>H</td>
										<td>WO</td>
										<td>T</td>
										<td>MO</td>
										<td>".pg_result($res,$i,os)."</td>
										<td>".pg_result($res,$i,codigo_posto)."</td>
										<td>".pg_result($res,$i,defeito)."</td>
										<td>".pg_result($res,$i,referencia)."</td>
										<td>".pg_result($res,$i,descricao)."</td>
										<td>".pg_result($res,$i,unidade)."</td>
										<td>".pg_result($res,$i,qtde)."</td>
										<td>".pg_result($res,$i,custo_peca)."</td>
										<td>".date('d-m-Y',strtotime(pg_result($res,$i,data)))."</td>
										<td>&nbsp;".date('his',strtotime(pg_result($res,$i,data)))."&nbsp;</td>
										<td>".date('d-m-Y',strtotime(pg_result($res,$i,resolvido)))."</td>
										<td>&nbsp;".date('his',strtotime(pg_result($res,$i,resolvido)))."&nbsp;</td>
										<td>".pg_result($res,$i,descricao_servico)."</td>
										<td>$numero_contrato</td>
										<td>$numero_patrimonio_pib</td>
										<td>$erp_produto</td>
										<td></td>
										<td></td>
										<td></td>
									</tr>";
				}
			}

			if(strlen($table_lines) == 0){
				$table_lines = "";
			}
		}
	}
}

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
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
					</td>
					<td align='right' nowrap><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
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
	if(strlen($table_lines) > 0){
		?>
	    <?php // <div id="env_tabela" style="border:1px solid #e6e6e6;margin-bottom:10px">
	    	$write = <<<EOF
			<table width="700" border="0" align="center" cellpadding="1" cellspacing="1" class="tabela">
				<tbody>
					<tr class="titulo_coluna">
						<td>Tipo de Registro</td>
						<td>Tipo de Registro (Header)</td>
						<td>Tipo de Documento</td>
						<td>Tipo de Linha</td>
						<td>Status da OS</td>
						<td>Número da OS</td>
						<td>Matrícula Contratada</td>
						<td>Código do Defeito / Serviço</td>
						<td>Código do Material Terceiro</td>
						<td>Descrição do Material</td>
						<td>Unidade de Medida</td>
						<td>Qtd do Material Utilizado</td>
						<td>Custo Unitário</td>
						<td>Data de Chegada</td>
						<td>Hora de Chegada</td>
						<td>Data de Conclusão</td>
						<td>Hora de Conclusão</td>
						<td>Observação / Descrição da Solução</td>
						<td>Protocolo da Contratada / Nº do Contrato</td>
						<td>PIB do Equipamento</td>
						<td>Número de Série do Equipamento</td>
						<td>Código de Barras do Equipamento</td>
						<td>Deixou Backup</td>
						<td>Data Solicitação</td>
					</tr>
					$table_lines
				</tbody>
			</table>
EOF;

		date_default_timezone_set('America/Sao_Paulo');
		$filename = 'xls/relatorio_remessa_ect-' . $login_admin . date('Ymd') . '.xls';
		$handle = fopen($filename, 'w');
		fwrite($handle, $write);
		fclose($handle);

		echo '<center><a href="' . $filename . '" target="_blank">Download do Resultado</a></center>';
		// </div>

	}else{
		if($btn_acao != ""){
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
	?>

</div>

	<?php
?>

<? include "rodape.php" ?>
