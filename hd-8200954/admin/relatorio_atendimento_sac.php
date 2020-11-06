<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELAT�RIO DE ATENDIMENTO DO SAC";
$meses = array(01 => "JANEIRO", "FEVEREIRO", "MAR�O", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO");
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
	return date(0,$soma_inicial-1);
}

include "cabecalho.php";

?>

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
<?php include "../js/js_css.php"; ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>
<script>
	function AbreCallcenter(data_inicial,data_final,produto,natureza,reclamado){
		janela = window.open("callcenter_relatorio_defeito_produto_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+"&natureza="+natureza+"&reclamado="+reclamado, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
	}
</script>
<? include "javascript_pesquisas.php" ?>
<br>
<br>

<!-- *** Processo de formata��o de LAY-OUT *** -->
<form name="frm_pesquisa" METHOD="post" ACTION="">
	<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='4' cellspacing='1' align='center'>
		<tr>
			<td width='100%' class='Titulo' background='imagens_admin/azul.gif'>
				Relat�rio dos Atendimentos
			</td>
		</tr>
		<tr>
			<td bgcolor='#DBE5F5' valign='bottom'>
				<table width='100%' border='0' cellspacing='0' cellpadding='0' >
					<td width="100%">
						<font size='4'>
							FILTRO PARA SELE��O DE PER�ODO DE DADOS
						</font>
					</td>
					<table width='100%' border='0' cellspacing='0' cellpadding='0' >
						<tr class="Conteudo" bgcolor="#D9E2EF">
							<td width='25%' align='center' nowrap>
								<font size='2'>
									M�s Inicial
								</font>
							</td>
							<td width='25%' align='center' nowrap>
								<font size='2'>
									Ano Inicial
								</font>
							</td>
							<td width='25%' align='center' nowrap>
								<font size='2'>
									M�s Final
								</font>
							</td>
							<td width='25%' align='center' nowrap>
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
									Obs: O PER�ODO N�O PODE SER SUPERIOR A 12 M�SES
								</font>
							</td>
						</tr>
						<tr class="Conteudo" bgcolor="#D9E2EF">
							<td width='100%' colspan="4" align = 'center' style="text-align: center;">
								<img src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as op��es e clique aqui para pesquisar">
							</td>
						</tr>
					</table>
				</table>
				<br>
			</td>
		</tr>
	</table>
</form>
<p>

<?
// Verifica se o post n�o esta vazio
if (!empty($_POST)) {

	//   ***** CAPTURA DE DADOS *****   \\
	// armazena os dados selecionados nas veri�veis
	if($_POST['mes_inicial'])
		$mes_inicial = str_pad(trim($_POST['mes_inicial']), 2, '0', STR_PAD_LEFT);
	if($_POST['ano_inicial'])
		$ano_inicial = trim($_POST['ano_inicial']);
	if($_POST['mes_final'])
		$mes_final   = str_pad(trim($_POST['mes_final']), 2, '0', STR_PAD_LEFT);
	if($_POST['ano_final'])
		$ano_final   = trim($_POST['ano_final']);

	//   ***** VERIFICA��O DO PER�ODO SELECIONADO *****   \\
	// Verifica se o mes/ano final � maior ou igual ao mes/ano inicial
	if (strtotime($ano_inicial.'-'.$mes_inicial.'-01') <= strtotime($ano_final.'-'.$mes_final.'-01')) {

		// Monta a data inicial 
		$data_inicial = $ano_inicial.'-'.$mes_inicial.'-'.'01';
		$dta_inicial  = $ano_inicial.'-'.$mes_inicial.'-'.'01';
		// Monta a data final (em duas vari�veis)
		$data_final   = $ano_final.'-' .$mes_final.'-'.'01';
		$dta_final    = $ano_final.'-' .$mes_final.'-'.'01';

		// Verifica no banco se o per�do selecionado n�o � maior que 12 m�ses
		$sql_data = "Select '$data_inicial' ::date > '$data_final' :: date - interval '12 month' as data";
		$res_data = pg_exec($con,$sql_data);
		$vet = pg_result($res_data,0,data);
		$vet['data'] ;

		// Verifica se o per�odo � maior que 12 m�ses
		if ($vet == 't'){
			// DETERMINA AS VARI�VEIS A SEREM UTILIZADAS \\

			// Processo que determina a data inicial selecionada pelo usu�rio (YYYY-MM-DD HH:MM:SS)
			$dta_inicial     = substr($dta_inicial, 0,10 )." 00:00:00";
			// Processo que determina a data final selecionada mais um m�s
			$sql_data_final  = "Select '$data_final'::date + interval '1 month' as data_lista";
			$res_data_final  = pg_exec($con,$sql_data_final);
			$vet_data_final  = pg_result($res_data_final,0,data_lista);
			// Processo que determina o �ltimo dia do m�s selecionado
			$sql_data_final  = "Select '$vet_data_final'::date - interval '1 day' as data_lista";
			$res_data_final  = pg_exec($con,$sql_data_final);
			$vet_data_final  = pg_result($res_data_final,0,data_lista);
			$dta_final       = substr($vet_data_final, 0,10 )." 23:59:59";

			// Processo para localiza��o no banco dos MOTIVOS
			$sql_motivo = "
					SELECT categoria
					FROM tbl_hd_chamado 
					WHERE data >= '$dta_inicial' AND data <= '$dta_final'
					AND fabrica = $login_fabrica
					AND fabrica_responsavel = $login_fabrica
					GROUP BY categoria
					ORDER BY categoria";
			$res_motivo   = @pg_exec($con,$sql_motivo);

			// Processo para localiza��o no banco dos MARCAS
			$sql_marca = "
					SELECT tbl_marca.nome
					FROM tbl_hd_chamado 
					JOIN tbl_hd_chamado_extra USING(hd_chamado)
					JOIN tbl_produto USING(produto)
					JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
					WHERE data >= '$dta_inicial' AND data <= '$dta_final'
					AND tbl_hd_chamado.fabrica = $login_fabrica
					AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					GROUP BY tbl_marca.nome
					ORDER BY tbl_marca.nome";
			$res_marca  = @pg_exec($con,$sql_marca);

			// Processo para Formata��o do LABEL de acordo com o BANCO DE DADOS.
			for ($i = 0; $i < pg_num_rows($res_motivo); $i++) {
				$motivo_banco[$i] = @pg_result($res_motivo,$i,categoria);
				switch ($motivo_banco[$i]) {
					case 'informacao'         : $motivo[$i] = 'Informa��o'; 
						break;
					case 'procon'             : $motivo[$i] = 'Procon'; 
						break;
					case 'reclamacao'         : $motivo[$i] = 'Reclama��o'; 
						break;
					case 'reclamacao_produto' : $motivo[$i] = 'Reclama��o do Produto'; 
						break;
					case 'ressarcimento'      : $motivo[$i] = 'Ressarcimento'; 
						break;
					case 'sugestao'           : $motivo[$i] = 'Sugest�o'; 
						break;
					case 'Supote Telefone'    : $motivo[$i] = 'Suporte Telefonico'; 
						break;
					default : $motivo[$i] = ucfirst(str_replace("_", " ", $motivo_banco[$i]));
						break;
				}
			}
			$cont_motivo = $i;

			//  IN�CIO DA PRIMEIRA LINHA T�TULO  \\
			//    **** Processo para montagem do quadro ****    \\
			echo "<table align='center' border='0' cellspacing='1' cellpadding='5'>";
				echo "<tr class='menu_top'>\n";
					// Primeira coluna (DESCRI��O DOS MOTIVOS)
					echo "<td width='105'>Motivo</td>";
					// Coluna por per�odos (MESES SELECIONADO)
					for(;;){
						// Verifica se a data inicial � menor que a data final
						if ($data_inicial <= $data_final){
							// Seleciona o ANO (dois digitos)
							$ano = substr($data_inicial, 2,2 );
							// Seleciona o MES (dois digitos)
							$mes = substr($data_inicial, 5,2 );
							// Troca o m�s de digitos para iniciais (ex. 01 -> JAN)
							$mostra_mes[$mes];
							// Monta a STRING para listar na tela
							$mostra_periodo = $mostra_mes[$mes].'/'.$ano;
							// Lista na tela
							echo "<td width='55'>$mostra_periodo</td>";
							// Adiciona mais um m�s
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
					// Pen�ltima coluna (QTD ACUMULADO)
					echo "<td width='80'>Acumulado</td>";
					// �ltima coluna (Porcentagem)
					echo "<td width='30'>%</td>";
				echo "</tr>";
			//  FIM DA PRIMEIRA LINHA T�TULO  \\

				// Processo para localiza��o no banco das QTD de MOTIVOS
				$sql_geral = "
							SELECT count (hd_chamado)as TOTAL 
								FROM tbl_hd_chamado
							WHERE data >= '$dta_inicial' AND data <= '$dta_final'
								AND fabrica = $login_fabrica
								AND fabrica_responsavel = $login_fabrica";
				$res_geral   = @pg_exec($con,$sql_geral);
				$total_geral = @pg_result($res_geral,0,TOTAL);

				//  IN�CIO DO PREENCHIMENTO DA TABELA COM DADOS  \\
				//  La�o principal feito pelo n�mero de MOTIVOS
				for ($i = 0; $i < $cont_motivo; $i++) {
					// Determina a cor do GRID
					$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F9F9F9";
					// determino a linha da tabela
					echo "<tr class='table_line' bgcolor='$cor'>";
					// Mostra o motivo
					echo "<td>$motivo[$i]</td>";

					// Vari�vel para conta de datas (INICIAL E FINAL)
					$dta_inicio_mes = substr($dta_inicial, 0,10 )." 00:00:00";

					$porcentagem = 0;
					$acumulado   = 0;
					// La�o para somar a qtd m�s a m�s
					for($x=1; $x <= $cont_data; $x++){

						// Verifica se a data inicial � menor que a data final
						$sql_monta     = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista ";
						$res_monta     = pg_exec($con, $sql_monta);
						$vet_monta     = pg_result($res_monta,0,data_lista);
						$sql_monta_fim = "Select '$vet_monta'::date - interval '1 day' as data_lista";
						$res_monta_fim = pg_exec($con,$sql_monta_fim);
						$vet_monta_fim = pg_result($res_monta_fim,0,data_lista);
						$dta_final_mes = substr($vet_monta_fim, 0,10)." 23:59:59";

						// Query para localiza��o do TOTAL POR MOTIVO
						$sql_total = "
								SELECT count (hd_chamado)as TOTAL
								FROM tbl_hd_chamado
								WHERE data >= '$dta_inicio_mes' AND data <= '$dta_final_mes'
									AND fabrica = $login_fabrica
									AND categoria = '{$motivo_banco[$i]}'
									AND fabrica_responsavel = $login_fabrica
								GROUP BY categoria
								ORDER BY categoria";
						$res_total = @pg_exec($con,$sql_total);
						$total = @pg_result($res_total,0,TOTAL);
						echo "<td align='right'>";
							if (strlen($total ) > 0){
								$tot_total += $total;
								echo $total;
							}
						echo "&nbsp;</td>\n";
						// Soma os totais
						$acumulado = $acumulado + $total;
						// Adiciona mais um m�s
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
				//  *** PROCESSO DE TOTALIZA��O DE VALORES ***  \\
				echo "<td width='105'>Total geral</td>";
				$dta_inicio_mes = substr($dta_inicial, 0,10 )." 00:00:00";
				for($x=1; $x <= $cont_data; $x++){
						// Verifica se a data inicial � menor que a data final
						$sql_monta     = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista ";
						$res_monta     = pg_exec($con, $sql_monta);
						$vet_monta     = pg_result($res_monta,0,data_lista);
						$sql_monta_fim = "Select '$vet_monta'::date - interval '1 day' as data_lista";
						$res_monta_fim = pg_exec($con,$sql_monta_fim);
						$vet_monta_fim = pg_result($res_monta_fim,0,data_lista);
						$dta_final_mes = substr($vet_monta_fim, 0,10)." 23:59:59";

						// Query para localiza��o do TOTAL POR MOTIVO
						$sql_total_mes = "
								SELECT count (hd_chamado)as TOTAL
								FROM tbl_hd_chamado
								WHERE data >= '$dta_inicio_mes' AND data <= '$dta_final_mes'
									AND fabrica = $login_fabrica
									AND fabrica_responsavel = $login_fabrica";
						$res_total_mes = @pg_exec($con,$sql_total_mes);
						$total_mes = @pg_result($res_total_mes,0,TOTAL);
						// Adiciona mais um m�s
						$sql_mes = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista";
						$res_mes = pg_exec($con,$sql_mes);
						$vet_mes = pg_result($res_mes,0,data_lista);
						// Pega somente a data sem a hora
						$dta_inicio_mes = substr($vet_mes, 0,10 )." 00:00:00";
						// Vari�vel para acumular o total geral
						$qtd_mes = $qtd_mes + $total_mes;
						echo "<td width='55'>$total_mes</td>";
					}
				echo "<td width='80'>$qtd_mes</td>";
				echo "<td width='30'>100%</td>";
			echo "</tr>";
		echo "</table>";

//*************************************MARCA***************************************************\\

		echo "<br />";
		echo "<br />";
		echo "<br />";


		// Monta a data inicial 
		$data_inicial = $ano_inicial.'-'.$mes_inicial.'-'.'01';
		$dta_inicial  = $ano_inicial.'-'.$mes_inicial.'-'.'01';
		// Monta a data final (em duas vari�veis)
		$data_final   = $ano_final.'-' .$mes_final.'-'.'01';
		$dta_final    = $ano_final.'-' .$mes_final.'-'.'01';

		// Processo que determina a data inicial selecionada pelo usu�rio (YYYY-MM-DD HH:MM:SS)
		$dta_inicial     = substr($dta_inicial, 0,10 )." 00:00:00";
		// Processo que determina a data final selecionada mais um m�s
		$sql_data_final  = "Select '$data_final'::date + interval '1 month' as data_lista";
		$res_data_final  = pg_exec($con,$sql_data_final);
		$vet_data_final  = pg_result($res_data_final,0,data_lista);
		// Processo que determina o �ltimo dia do m�s selecionado
		$sql_data_final  = "Select '$vet_data_final'::date - interval '1 day' as data_lista";
		$res_data_final  = pg_exec($con,$sql_data_final);
		$vet_data_final  = pg_result($res_data_final,0,data_lista);
		$dta_final       = substr($vet_data_final, 0,10 )." 23:59:59";

			// Processo para Formata��o do LABEL de acordo com o BANCO DE DADOS.
			for ($i = 0; $i < pg_num_rows($res_marca); $i++) {
				$marca[$i] = @pg_result($res_marca,$i,nome);
			}
			$cont_marca = $i;

					//  IN�CIO DA PRIMEIRA LINHA T�TULO  \\
			//    **** Processo para montagem do quadro ****    \\
			echo "<table align='center' border='0' cellspacing='1' cellpadding='5'>";
				echo "<tr class='menu_top'>\n";
					// Primeira coluna (DESCRI��O DAS MARCAS)
					echo "<td width='105'>Marcas</td>";
					// Coluna por per�odos (MESES SELECIONADO)
					$cont_data = 0;
					for(;;){
						// Verifica se a data inicial � menor que a data final
						if ($data_inicial <= $data_final){
							// Seleciona o ANO (dois digitos)
							$ano = substr($data_inicial, 2,2 );
							// Seleciona o MES (dois digitos)
							$mes = substr($data_inicial, 5,2 );
							// Troca o m�s de digitos para iniciais (ex. 01 -> JAN)
							$mostra_mes[$mes];
							// Monta a STRING para listar na tela
							$mostra_periodo = $mostra_mes[$mes].'/'.$ano;
							// Lista na tela
							echo "<td width='55'>$mostra_periodo</td>";
							// Adiciona mais um m�s
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
					// Pen�ltima coluna (QTD ACUMULADO)
					echo "<td width='80'>Acumulado</td>";
					// �ltima coluna (Porcentagem)
					echo "<td width='30'>%</td>";
				echo "</tr>";
			//  FIM DA PRIMEIRA LINHA T�TULO  \\

				// Processo para localiza��o no banco das QTD de MARCAS
				$sql_geral = "
					SELECT count (tbl_marca.nome) as TOTAL
					FROM tbl_hd_chamado 
					JOIN tbl_hd_chamado_extra USING(hd_chamado)
					JOIN tbl_produto USING(produto)
					JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
					WHERE data >= '$dta_inicial' AND data <= '$dta_final'
					AND tbl_hd_chamado.fabrica = $login_fabrica
					AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica";
				$res_geral   = @pg_exec($con,$sql_geral);
				$total_geral = @pg_result($res_geral,0,TOTAL);

				//  IN�CIO DO PREENCHIMENTO DA TABELA COM DADOS  \\
				//  La�o principal feito pelo n�mero de MARCAS
				for ($i = 0; $i < $cont_marca; $i++) {
					// Determina a cor do GRID
					$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F9F9F9";
					// determino a linha da tabela
					echo "<tr class='table_line' bgcolor='$cor'>";
					// Mostra o motivo
					echo "<td> <a href='relatorio_atendimento_sac_consulta.php?marca=$marca[$i]&marca2=$mes_inicial&marca3=$ano_inicial&marca4=$mes_final&marca5=$ano_final' target='_blank'
					>$marca[$i]</a></td>";

					// Vari�vel para conta de datas (INICIAL E FINAL)
					$dta_inicio_mes = substr($dta_inicial, 0,10 )." 00:00:00";
					$porcentagem = 0;
					$acumulado   = 0;
					// La�o para somar a qtd m�s a m�s
					for($x=1; $x <= $cont_data; $x++){

						// Verifica se a data inicial � menor que a data final
						$sql_monta     = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista ";
						$res_monta     = pg_exec($con, $sql_monta);
						$vet_monta     = pg_result($res_monta,0,data_lista);
						$sql_monta_fim = "Select '$vet_monta'::date - interval '1 day' as data_lista";
						$res_monta_fim = pg_exec($con,$sql_monta_fim);
						$vet_monta_fim = pg_result($res_monta_fim,0,data_lista);
						$dta_final_mes = substr($vet_monta_fim, 0,10)." 23:59:59";

						// Query para localiza��o do TOTAL POR MARCA
						$sql_total = "
									SELECT count (tbl_marca.nome) as TOTAL
									FROM tbl_hd_chamado 
										JOIN tbl_hd_chamado_extra USING(hd_chamado)
										JOIN tbl_produto USING(produto)
										JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
									WHERE data >= '$dta_inicio_mes' AND data <= '$dta_final_mes'
										AND tbl_hd_chamado.fabrica = $login_fabrica
										AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
										AND tbl_marca.nome = '{$marca[$i]}'
										GROUP BY tbl_marca.nome
										ORDER BY tbl_marca.nome";
						$res_total = @pg_exec($con,$sql_total);
						$total = @pg_result($res_total,0,TOTAL);
						echo "<td align='right'>";
							if (strlen($total ) > 0){
								$tot_total += $total;
								echo $total;
							}
						echo "&nbsp;</td>\n";
						// Soma os totais
						$acumulado = $acumulado + $total;
						// Adiciona mais um m�s
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
				//  *** PROCESSO DE TOTALIZA��O DE VALORES ***  \\
				echo "<td width='105'>Total geral</td>";
				$dta_inicio_mes = substr($dta_inicial, 0,10 )." 00:00:00";
				$qtd_mes = 0;
				$total_mes = 0;
				for($x=1; $x <= $cont_data; $x++){
						// Verifica se a data inicial � menor que a data final
						$sql_monta     = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista ";
						$res_monta     = pg_exec($con, $sql_monta);
						$vet_monta     = pg_result($res_monta,0,data_lista);
						$sql_monta_fim = "Select '$vet_monta'::date - interval '1 day' as data_lista";
						$res_monta_fim = pg_exec($con,$sql_monta_fim);
						$vet_monta_fim = pg_result($res_monta_fim,0,data_lista);
						$dta_final_mes = substr($vet_monta_fim, 0,10)." 23:59:59";
						// Query para localiza��o do TOTAL POR MOTIVO
						$sql_total_mes = "
									SELECT count(tbl_marca.nome) as TOTAL
									FROM tbl_hd_chamado 
										JOIN tbl_hd_chamado_extra USING(hd_chamado)
										JOIN tbl_produto USING(produto)
										JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
									WHERE data >= '$dta_inicio_mes' AND data <= '$dta_final_mes'
										AND tbl_hd_chamado.fabrica = $login_fabrica
										AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica";
						$res_total_mes = @pg_exec($con,$sql_total_mes);
						$total_mes = @pg_result($res_total_mes,0,TOTAL);
						// Adiciona mais um m�s
						$sql_mes = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista";
						$res_mes = pg_exec($con,$sql_mes);
						$vet_mes = pg_result($res_mes,0,data_lista);
						// Pega somente a data sem a hora
						$dta_inicio_mes = substr($vet_mes, 0,10 )." 00:00:00";
						// Vari�vel para acumular o total geral
						echo "<td width='55'>$total_mes</td>";
						$qtd_mes = $qtd_mes + $total_mes;
					}
				echo "<td width='80'>$qtd_mes</td>";
				echo "<td width='30'>100%</td>";
			echo "</tr>";
		echo "</table>";
		}else{
			echo $msg_erro .= "A per�odo n�o pode ser superior a 12 meses";
		}
	}else{
		echo $msg_erro .= "A sel��o do per�odo inicial n�o pode ser maior que a do per�odo final";
	}
}

include "rodape.php" ?>
