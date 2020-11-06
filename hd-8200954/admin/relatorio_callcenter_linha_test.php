<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "Relatório de Atendimento por Atendente";
$meses = array(01 => "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO");
$mostra_mes = array('01'=>"JAN",'02'=>"FEV",'03'=>"MAR",'04'=>"ABR",'05'=>"MAI",'06'=>"JUN",'07'=>"JUL",'08'=>"AGO",'09'=>"SET",'10'=>"OUT",'11'=>"NOV",'12'=>"DEZ");

$msg_erro = '';

function ultimodiames($soma_inicial=""){
	if (!$soma_inicial){
		$ano = date("Y");
		$mes = date("m");
		$dia = date("d");
	}else{
		$ano = date("Y",$soma_inicial);
		$mes = date("m",$soma_inicial);
		$dia = date("d",$soma_inicial);
	}
	$soma_inicial = mktime(0, 0, 0, $ano, $mes, 1);
	//$soma_inicial = mktime(0, 0, 0, $mes, 1, $ano);
	return date(0,$soma_inicial-1);
}

include "cabecalho.php";

include "javascript_calendario.php";?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<style>
	.menu_top {
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 11px;
		font-weight: bold;
		color:#ffffff;
		background-color: #445AA8;
	}
	.Titulo {
		text-align: center;
		font-family: Arial;
		font-size: 10px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #485989;
	}
	.Conteudo {
		font-family: Arial;
		font-size: 9px;
		font-weight: normal;
	}
	.ConteudoBranco {
		font-family: Arial;
		font-size: 9px;
		color:#FFFFFF;
		font-weight: normal;
	}
	.Mes{
		font-size: 9px;
	}
	.Caixa{
		BORDER-RIGHT: #6699CC 1px solid; 
		BORDER-TOP: #6699CC 1px solid; 
		FONT: 8pt Arial ;
		BORDER-LEFT: #6699CC 1px solid; 
		BORDER-BOTTOM: #6699CC 1px solid; 
		BACKGROUND-COLOR: #FFFFFF;
	}
	.Exibe{
		font-family: Arial, Helvetica, sans-serif;
		font-size: 8 px;
		font-weight: none;
		color: #000000;
		text-align: center;
	}
	.Erro{
		BORDER-RIGHT: #990000 1px solid; 
		BORDER-TOP: #990000 1px solid; 
		FONT: 10pt Arial ;
		COLOR: #ffffff;
		BORDER-LEFT: #990000 1px solid; 
		BORDER-BOTTOM: #990000 1px solid; 
		BACKGROUND-COLOR: #FF0000;
	}
	.Carregando{
		TEXT-ALIGN: center;
		BORDER-RIGHT: #aaa 1px solid; 
		BORDER-TOP: #aaa 1px solid; 
		FONT: 10pt Arial ;
		COLOR: #000000;
		BORDER-LEFT: #aaa 1px solid; 
		BORDER-BOTTOM: #aaa 1px solid; 
		BACKGROUND-COLOR: #FFFFFF;
		margin-left:20px;
		margin-right:20px;
	}
</style>
<? include "javascript_pesquisas.php" ?>
<br>
<br>
<?
if($btn_acao=="Consultar"){
	if(strlen($mes_inicial) == 0){
		$msg_erro = "ENTRE COM O MÊS INICIAL";
	}elseif (strlen($ano_inicial) == 0){
		$msg_erro = "ENTRE COM O ANO INICIAL";
	}elseif(strlen($mes_final) == 0){
		$msg_erro = "ENTRE COM O MÊS FINAL";
	}elseif(strlen($ano_final) == 0){
		$msg_erro = "ENTRE COM O ANO FINAL";
	}
}

if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='5' cellspacing='1' align='center'>";
		echo "<tr>";
			echo "<td class='Erro'>$msg_erro</td>";
		echo "</tr>";
	echo "</table>";
}
?>

<!-- *** Processo de formatação de LAY-OUT *** -->
<form name="frm_pesquisa" METHOD="post" ACTION='<?=$PHP_SELF?>' align='center'>
	<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='4' cellspacing='1' align='center'>
		<tr>
			<td width='100%' class='Titulo' background='imagens_admin/azul.gif'>
			<span style="font-size:13px">Relatório de Atendimento por Atendente</span>
			</td>
		</tr>
		<tr>
			<td bgcolor='#DBE5F5' valign='bottom'>
				<table width='100%' border='0' cellspacing='0' cellpadding='0' >
					<td width="100%">
						<font size='4'>
						<span style="font-size:13px">FILTRO PARA SELEÇÃO DE PERÍODO DE DADOS</span>
						</font>
					</td>
					<table width='100%' border='0' cellspacing='0' cellpadding='0' >
						<tr class="Conteudo" bgcolor="#D9E2EF">
							<td width='25%' align='center' nowrap>
							<br />
								<font size='2'>
									Mês Inicial
								</font>
							</td>
							<td width='25%' align='center' nowrap>
							<br />
								<font size='2'>
									Ano Inicial
								</font>
							</td>
							<td width='25%' align='center' nowrap>
							<br />
								<font size='2'>
									Mês Final
								</font>
							</td>
							<td width='25%' align='center' nowrap>
							<br />
								<font size='2'>
									Ano Final
								</font>
							</td>
						</tr>
						<tr class="Conteudo" bgcolor="#D9E2EF">
							<td width='25%' align='center' nowrap>
								<select align ='center' name="mes_inicial" size="1" class="frm">
									<option value=''>
									</option>
									<?
										for ($i = 1 ; $i <= count($meses);$i++){
											echo "<option value='$i'";
											if ($_POST['mes_inicial'] == $i) 
												echo " selected";
											echo ">" . $meses[$i] . "</option>";
										}
									?>
								</select>
							</td>
							<td width='25%' align='center' nowrap>
								<select align ='center' name="ano_inicial" size="1" class="frm">
									<option value=''>
									</option>
									<?
										for ($i = date("Y") ; $i >= 2003 ; $i--) {
											echo "<option value='$i'";
											if ($_POST['ano_inicial'] == $i)
												echo " selected";
											echo ">$i</option>";
										}
									?>
								</select>
							</td>
							<td width='25%' align='center' nowrap>
								<select align ='center' name="mes_final" size="1" class="frm">
									<option value=''>
									</option>
									<?
										for ($i = 1 ; $i <= count($meses);$i++){
											echo "<option value='$i'";
											if ($_POST['mes_final'] == $i) 
												echo " selected";
											echo ">" . $meses[$i] . "</option>";
										}
									?>
								</select>
							</td>
							<td width='25%' align='center' nowrap>
								<select align ='center' name="ano_final" size="1" class="frm">
									<option value=''>
									</option>
									<?
										for ($i = date("Y") ; $i >= 2003 ; $i--) {
											echo "<option value='$i'";
											if ($_POST['ano_final'] == $i)
												echo " selected";
											echo ">$i</option>";
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td width='100%' align='center' nowrap colspan="4">
								&nbsp;
							</td>
						</tr>
						<tr >
							<td colspan="4" width="100%">
								<font size='1'>
									Obs: O PERÍODO NÃO PODE SER SUPERIOR A 12 MÊSES
								</font>
							</td>
						</tr>
							<tr class="Conteudo" bgcolor="#D9E2EF">
								<td width='100%' colspan="4" align = 'center' style="text-align: center;">
									<img border="0" src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.btn_acao.value='Consultar'; document.frm_pesquisa.submit();"style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar">
									<input type='hidden' name='btn_acao' value='<?=$acao?>'>
								</td>
							</tr>
						</tr>
					</table>
				</table>
				<br>
			</td>
		</tr>
	</table>
	<? if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){
		//   ***** CAPTURA DE DADOS *****   \\
		// armazena os dados selecionados nas veriáveis
		if($_POST['mes_inicial'])
			$mes_inicial = str_pad(trim($_POST['mes_inicial']), 2, '0', STR_PAD_LEFT);
		if($_POST['ano_inicial'])
			$ano_inicial = trim($_POST['ano_inicial']);
		if($_POST['mes_final'])
			$mes_final   = str_pad(trim($_POST['mes_final']), 2, '0', STR_PAD_LEFT);
		if($_POST['ano_final'])
			$ano_final   = trim($_POST['ano_final']);
		//   ***** VERIFICAÇÃO DO PERÍODO SELECIONADO *****   \\
		// Verifica se o mes/ano final é maior ou igual ao mes/ano inicial
		if (strtotime($ano_inicial.'-'.$mes_inicial.'-01') <= strtotime($ano_final.'-'.$mes_final.'-01')) {
			// Monta a data inicial 
			$data_inicial = $ano_inicial.'-'.$mes_inicial.'-'.'01';
			$dta_inicial  = $ano_inicial.'-'.$mes_inicial.'-'.'01';
			// Monta a data final (em duas variáveis)
			$data_final   = $ano_final.'-' .$mes_final.'-'.'01';
			$dta_final    = $ano_final.'-' .$mes_final.'-'.'01';
			// Verifica no banco se o perído selecionado não é maior que 12 mêses
			$sql_data = "Select '$data_inicial' ::date > '$data_final' :: date - interval '12 month' as data";
			$res_data = pg_exec($con,$sql_data);
			$vet = pg_result($res_data,0,data);
			$vet['data'] ;
			// Verifica se o período é maior que 12 mêses
			if ($vet == 't'){
				// Processo que determina a data inicial selecionada pelo usuário (YYYY-MM-DD HH:MM:SS)
				$dta_inicial     = substr($dta_inicial, 0,10 )." 00:00:00";
				// Processo que determina a data final selecionada mais um mês
				$sql_data_final  = "Select '$data_final'::date + interval '1 month' as data_lista";
				$res_data_final  = pg_exec($con,$sql_data_final);
				$vet_data_final  = pg_result($res_data_final,0,data_lista);
				// Processo que determina o último dia do mês selecionado
				$sql_data_final  = "Select '$vet_data_final'::date - interval '1 day' as data_lista";
				$res_data_final  = pg_exec($con,$sql_data_final);
				$vet_data_final  = pg_result($res_data_final,0,data_lista);
				$dta_final       = substr($vet_data_final, 0,10 )." 23:59:59";
				$sql_atendente = "
						SELECT DISTINCT
							tbl_admin.admin          AS admin,
							tbl_admin.login          AS usuario,
							tbl_admin.nome_completo  AS nome,
							COUNT(hd_chamado_item)   AS interacoes
						FROM tbl_hd_chamado
							INNER JOIN tbl_hd_chamado_extra USING (hd_chamado)
							LEFT  JOIN tbl_hd_chamado_item  USING (hd_chamado)
							INNER JOIN tbl_admin            ON ( tbl_hd_chamado.admin = tbl_admin.admin )
						WHERE tbl_admin.fabrica = $login_fabrica
							AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							AND (tbl_hd_chamado.data BETWEEN '$dta_inicial' AND '$dta_final')
						GROUP BY tbl_admin.admin, tbl_admin.login, tbl_admin.nome_completo
						ORDER BY tbl_admin.nome_completo";
				echo nl2br($sql_atendente);
				// $res_atendente   = @pg_exec($con,$sql_atendente);

				//  INÍCIO DA PRIMEIRA LINHA TÍTULO  \\
				if (pg_numrows($res_atendente) > 0) {
					//    **** Processo para montagem do quadro ****    \\
					echo "<table align='center' border='0' cellspacing='1' cellpadding='5'>";
						echo "<tr class='menu_top'>\n";
							// Primeira coluna (Nome dos Atendentes)
							echo "<td width='105'>Atendente</td>";
							// Coluna por períodos (MESES SELECIONADO)
							for(;;){
								// Verifica se a data inicial é menor que a data final
								if ($data_inicial <= $data_final){
									// Seleciona o ANO (dois digitos)
									$ano = substr($data_inicial, 2,2 );
									// Seleciona o MES (dois digitos)
									$mes = substr($data_inicial, 5,2 );
									// Troca o mês de digitos para iniciais (ex. 01 -> JAN)
									$mostra_mes[$mes];
									// Monta a STRING para listar na tela
									$mostra_periodo = $mostra_mes[$mes].'/'.$ano;
									// Lista na tela
									echo "<td width='55'>$mostra_periodo</td>";
									// Adiciona mais um mês
									// Última coluna (Porcentagem)
									echo "<td width='30'>%</td>";
									$sql_mes = "Select '$data_inicial'::date + interval '1 month' as data_lista";
									$res_mes = pg_exec($con,$sql_mes);
									$vet_mes = pg_result($res_mes,0,data_lista);
									// Pega somente a data sem a hora
									$data_inicial = substr($vet_mes, 0,10 );
									$cont_data = $cont_data + 1;
								}else{
									break;
								}
							}
						echo "</tr>";
						//  FIM DA PRIMEIRA LINHA TÍTULO  \\
						// Processo para localização no banco das QTD por Atendente
						$sql_geral = "
									SELECT count (hd_chamado)as TOTAL 
										FROM tbl_hd_chamado
									WHERE data >= '$dta_inicial' AND data <= '$dta_final'
										AND fabrica = $login_fabrica
										AND fabrica_responsavel = $login_fabrica";
						$res_geral   = @pg_exec($con,$sql_geral);
						$total_geral = @pg_result($res_geral,0,TOTAL);
						//  INÍCIO DO PREENCHIMENTO DA TABELA COM DADOS  \\
						//  Laço principal feito pelo número de MOTIVOS
						for ($i = 0; $i < $cont_atendente; $i++) {
							$atendente       = pg_result($res_atendente,$i,nome);
							// Determina a cor do GRID
							$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F9F9F9";
							// determino a linha da tabela
							echo "<tr class='table_line' bgcolor='$cor'>";
							// Mostra o motivo
							echo "<td nowrap>$atendente</td>";
							// Variável para conta de datas (INICIAL E FINAL)
							$dta_inicio_mes = substr($dta_inicial, 0,10 )." 00:00:00";
							$porcentagem = 0;
							$acumulado   = 0;
							// Laço para somar a qtd mês a mês
							for($x=1; $x <= $cont_data; $x++){
								// Verifica se a data inicial é menor que a data final
								$sql_monta     = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista ";
								$res_monta     = pg_exec($con, $sql_monta);
								$vet_monta     = pg_result($res_monta,0,data_lista);
								$sql_monta_fim = "Select '$vet_monta'::date - interval '1 day' as data_lista";
								$res_monta_fim = pg_exec($con,$sql_monta_fim);
								$vet_monta_fim = pg_result($res_monta_fim,0,data_lista);
								$dta_final_mes = substr($vet_monta_fim, 0,10)." 23:59:59";
								// Query para localização do TOTAL POR MOTIVO
								$sql_total = "
										SELECT DISTINCT
											COUNT(hd_chamado_item)   AS TOTAL
										FROM tbl_hd_chamado
											INNER JOIN tbl_hd_chamado_extra USING (hd_chamado)
											LEFT  JOIN tbl_hd_chamado_item  USING (hd_chamado)
											INNER JOIN tbl_admin            ON ( tbl_hd_chamado.admin = tbl_admin.admin )
										WHERE tbl_admin.fabrica         = $login_fabrica
											AND tbl_admin.nome_completo = '{$atendente[$i]}'
											AND (tbl_hd_chamado.data BETWEEN '$dta_inicial' AND '$dta_final')
										GROUP BY tbl_admin.nome_completo
										ORDER BY tbl_admin.nome_completo";
								$res_total = @pg_exec($con,$sql_total);
								$total     = @pg_result($res_total,0,TOTAL);
								echo "<td align='right'>";
								if (strlen($total ) > 0){
									$tot_total += $total;
									echo $total;
								}
								echo "&nbsp;</td>\n";
								// Soma os totais
								$acumulado = $acumulado + $total;
								// Adiciona mais um mês
								$sql_mes = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista";
								$res_mes = pg_exec($con,$sql_mes);
								$vet_mes = pg_result($res_mes,0,data_lista);
								// Pega somente a data sem a hora
								$dta_inicio_mes = substr($vet_mes, 0,10 )." 00:00:00";
							}
							// Mostra o total acumulado e a porcentagem
							echo "<td align='right'>$acumulado</td>";
							// Faz a conta da Porcentagem
							if ($total_geral > 0) {
								$porcentagem = $acumulado/$total_geral*100;
								$porcentagem = number_format($porcentagem,2);
							}else{
								$porcentagem = 0;
							}
							echo "<td align='right'>$porcentagem</td>\n";
						}
						//  FINAL DO PREENCHIMENTO DA TABELA COM DADOS  \\
						echo "<tr class='menu_top'>\n";
							//  *** PROCESSO DE TOTALIZAÇÃO DE VALORES ***  \\
							echo "<td width='105'>Total geral</td>";
							$dta_inicio_mes = substr($dta_inicial, 0,10 )." 00:00:00";
							for($x=1; $x <= $cont_data; $x++){
								// Verifica se a data inicial é menor que a data final
								$sql_monta     = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista ";
								$res_monta     = pg_exec($con, $sql_monta);
								$vet_monta     = pg_result($res_monta,0,data_lista);
								$sql_monta_fim = "Select '$vet_monta'::date - interval '1 day' as data_lista";
								$res_monta_fim = pg_exec($con,$sql_monta_fim);
								$vet_monta_fim = pg_result($res_monta_fim,0,data_lista);
								$dta_final_mes = substr($vet_monta_fim, 0,10)." 23:59:59";

								// Query para localização do TOTAL POR Atendente
								$sql_total_mes = "
										SELECT count (hd_chamado)as TOTAL
										FROM tbl_hd_chamado
										WHERE data >= '$dta_inicio_mes' AND data <= '$dta_final_mes'
											AND fabrica = $login_fabrica
											AND fabrica_responsavel = $login_fabrica";
								$res_total_mes = @pg_exec($con,$sql_total_mes);
								$total_mes = @pg_result($res_total_mes,0,TOTAL);
								// Adiciona mais um mês
								$sql_mes = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista";
								$res_mes = pg_exec($con,$sql_mes);
								$vet_mes = pg_result($res_mes,0,data_lista);
								// Pega somente a data sem a hora
								$dta_inicio_mes = substr($vet_mes, 0,10 )." 00:00:00";
								// Variável para acumular o total geral
								$qtd_mes = $qtd_mes + $total_mes;
								echo "<td width='55'>$total_mes</td>";
								echo "<td width='55'>%</td>";
							}
						echo "</tr>";
					echo "</table>";
				} else {
					echo "A período não pode ser superior a 12 meses";
				}
			} else {
				echo "A selção do período inicial não pode ser maior que a do período final";
			}
		}
	}
include "rodape.php"?>