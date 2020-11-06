<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
else                            $acao = strtoupper($_GET["acao"]);

if(strlen($_POST["codigo_posto"])>0) $codigo_posto = trim($_POST["codigo_posto"]);
else                                 $codigo_posto = trim($_GET["codigo_posto"]);

if(strlen($_POST["periodo"])>0) $periodo = trim($_POST["periodo"]);
else                            $periodo = trim($_GET["periodo"]);

if(strlen($_POST["ultimo_rel"])>0) $ultimo_rel = trim($_POST["ultimo_rel"]);
else                               $ultimo_rel = trim($_GET["ultimo_rel"]);

if($acao=="PESQUISAR"){

	if(strlen($codigo_posto)>0){
		$sql = "
		SELECT posto
		FROM tbl_posto_fabrica
		WHERE codigo_posto = '$codigo_posto'
		AND fabrica = $login_fabrica
		";
		$res = pg_exec($con, $sql);
		$posto = pg_result($res,0,posto);

		if(pg_numrows($res)==0){
			$msg_erro = 'Posto não Encontrado';
		}
		else{
			$cond = ' AND tbl_posto_media_nova_computadores.posto ='. $posto;
		}
	}
	
	if(!empty($periodo)){
		if($periodo=='ultimo'){
			$cond .= " AND data_geracao = '$ultimo_rel'";
		}
		else{
			$cond .= " AND data_geracao = '$periodo-01'";
		}
	}
}
$title = 'RANKING DE AUTORIZADAS';
include "cabecalho.php";
?>
<style type='text/css'>
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
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

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	
	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.espaco{padding-left:100px;};
</style>


<SCRIPT LANGUAGE="JavaScript">
<!--
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
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
	else{
		alert('Preencha toda ou parte da informação para realizar a pesquisa');
	}
}
//-->
</SCRIPT>
	<?
		if(strlen($msg_erro) > 0){ ?>
			<table align='center' width='705'>
				<tr class='msg_erro'>
					<td><? echo $msg_erro; ?></td>
				</tr>
			</table>
	<?
		}
	?>
	<FORM METHOD="POST" NAME="frm_relatorio" ACTION="<?php echo $PHP_SELF; ?>" >
		<TABLE WIDTH='700' BORDER='0' CELLPADDING='0' CELLSPACING='0' ALIGN='CENTER' CLASS='formulario'>
			<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>
			<tr>
				<td colspan="4">&nbsp;</td>
			</tr>
			<tr>
				<td class='espaco'>Posto</td>
				<td>Razão Social</td>
			</tr>
			<tr>
				<td class='espaco'><input class="frm" type="text" name="codigo_posto" size="13" value="<?php echo $codigo_posto ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></td>
				<td><input class="frm" type="text" name="posto_nome" size="50" value="<?php echo $posto_nome ?>">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"><br></td>
			</tr>
			<tr>
				<td colspan='2' class='espaco'>
					Período <br />
					<select name='periodo' class='frm'> 
					<?
						$sql = "SELECT MAX(data_geracao) AS data FROM tbl_posto_media_nova_computadores";
						$res = pg_exec($con,$sql);
							$data = pg_result($res,0,data);
							if(strlen($data) > 0){
							list($y, $m, $d) = explode("-", $data);
							$data_aux = $d.'/'.$m.'/'.$y;
							echo '<option value="ultimo">'.$data_aux.'</option>';
							$ano = date('Y');
							$meses = date('m');
							for($i = 1 ; $i <=12; $i++){
								if($meses == 0){
									$meses = 12;
									$ano--;
								}
								if($ano==2011 and $meses == 2){
									$i = 13;
								}

								switch ($meses) {
									case "01":    $mes = Janeiro;   $mes_seguinte = '02';  break;
									case "02":    $mes = Fevereiro; $mes_seguinte = '03';  break;
									case "03":    $mes = Março;     $mes_seguinte = '04';  break;
									case "04":    $mes = Abril;     $mes_seguinte = '05';  break;
									case "05":    $mes = Maio;      $mes_seguinte = '06';  break;
									case "06":    $mes = Junho;     $mes_seguinte = '07';  break;
									case "07":    $mes = Julho;     $mes_seguinte = '08';  break;
									case "08":    $mes = Agosto;    $mes_seguinte = '09';  break;
									case "09":    $mes = Setembro;  $mes_seguinte = '10';  break;
									case "10":    $mes = Outubro;   $mes_seguinte = '11';  break;
									case "11":    $mes = Novembro;  $mes_seguinte = '12';  break;
									case "12":    $mes = Dezembro;  $mes_seguinte = '01';  break; 
								}
								echo "<option value='$ano-$mes_seguinte'>".$mes.' - '.$ano."</option>";
								$meses--;
							}							
						}
						else{
								echo '<option value="">Nenhum Relatório Gerado</option>';
							}
					?>
					</select>
					<input type='hidden' name='ultimo_rel' value='<?= $data; ?>'>
				</td>
			</tr>
			<tr>
				<td colspan="4">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="4" align='center'>
				<INPUT TYPE="hidden" NAME="acao" ID="acao" VALUE="">
				<input type='button' value='Pesquisar'onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: pointer;" alt="Clique AQUI para pesquisar"></td>
			</tr>
			<tr>
				<td colspan="4">&nbsp;</td>
			</tr>
		</TABLE>
	</FORM>
	<br />
<?
if($acao=="PESQUISAR" and strlen($msg_erro)==0){

	$sql = "SELECT tbl_posto.nome, tbl_posto_fabrica.posto,tbl_posto_media_nova_computadores.*,								    tbl_posto_fabrica.codigo_posto
			   FROM tbl_posto_media_nova_computadores
			   JOIN tbl_posto USING(posto)
			   JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			   WHERE tbl_posto_media_nova_computadores.fabrica = $login_fabrica
			   $cond
			   AND tbl_posto_media_nova_computadores.posto <> 6359
			   ORDER BY ranking, media_atend_mes DESC,reincidencia DESC
			   ";
	//echo nl2br($sql); exit;
	$res = pg_query($con,$sql);
	$total = pg_numrows($res);

	$desc_coluna3 = 'Quantidade de OS Abertas pelo Posto nos últimos 30 dias';
	$desc_coluna4 = 'Quantidade de OS Finalizadas pelo Posto nos últimos 30 dias';
	$desc_coluna5 = 'Porcentagem de OS Finalizadas nos últimos 20 dias considerando as OS abertas nos últimos 30 dias';
	$desc_coluna6 = 'Média de dias em que uma OS ficou aberta, levando-se em consideração todas as OS Finalizadas nos últimos 30 dias';
	$desc_coluna7 = 'Total de OS que ainda estão abertas';
	$desc_coluna8 = 'Total de OS que ainda estão abertas a mais de 20 dias';
	$desc_coluna9 = 'Soma dos dias de todas as OS Abertas nos ultimos 30 dias dividio pela quantidade de OS';
	$desc_coluna10 = 'Total de OS Abertas nos últimos 30 dias e que possuem uma Reincidência de até 90 dias';
	$desc_coluna11 = 'Porcentagem de OS Abertas nos últimos 30 dias e que possuem uma Reincidência de até 90 dias em relação as OS abertas nos últimos 30 dias';
	$desc_coluna12 = 'Total de OS abertas a mais de 48 horas e que não tiveram pedido de Peça';
	$desc_coluna13 = 'Porcentagem de de OS abertas a mais de 48 horas e que não tiveram pedido de Peça em relação as OS abertas nos últimos 30 dias';
	$desc_coluna14 = 'Soma de todas as Peças das OS abertas nos últimos 30 dias dias dividido pela quantidade de OS';
	$desc_coluna15 = 'Total de OS em Intervenção nos últimos 30 dias';
	$desc_coluna16 = 'Porcentagem de OS em Intervenção nos últimos 30 dias';
	$desc_coluna17 = 'Porcentagem de Comunicados do tipo "Comunicado de não conformidade" recebidos pelo Posto em relação as OS abertas nos últimos 30 dias';
	$desc_coluna18 = 'Nota relativa ao Indicador (Prazo médio de atendimento (1 mês)) utilizando o seguinte cálculo : NOTA = 100 - (INDICADOR_POSTO - META) * (100 / (CORTE - META + 1))';
	$desc_coluna19 = 'Nota relativa ao Indicador Reincidência 90 dias utilizando o seguinte cálculo : NOTA = 100 - (INDICADOR_POSTO - META) * (100 / (CORTE - META + 1))';
	$desc_coluna20 = 'Nota relativa ao Indicador Comunicados utilizando o seguinte cálculo : NOTA = 100 - (INDICADOR_POSTO - META) * (100 / (CORTE - META + 1))';
	$desc_coluna21 = 'Média cálculada entre a Nota1, Nota2 e Nota3 utilizando o seguinte cálculo : NOTA FINAL = (NOTA_1 * PESO_1 + NOTA_2 * PESO_2 + NOTA_3 * PESO_3) / 3';
	$desc_coluna22 = 'Posição do Posto de acordo com a Nota Final e Critérios de Desempate';
	if($total > 0){ ?>
		
		<table align='center' class='tabela' width='2000' cellspacing='1'>
			<caption class='titulo_tabela'>Ranking de Autorizadas</caption>
			<tr class='titulo_coluna'>
				<th>Cod Posto</th>
				<th>Nome Posto</th>
				<th><span title='<?= $desc_coluna3;?>'>Total OS Lançadas Posto (30 dias)</span></th>
				<th><span title='<?= $desc_coluna4;?>'>Total OS Finalizadas Posto (30 dias)</span></th>
				<th><span title='<?= $desc_coluna5;?>'>% OS Finalizadas até 20 dias</span></th>
				<th><span title='<?= $desc_coluna6;?>'>Média de dias Finalização da OS</span></th>
				<th><span title='<?= $desc_coluna7;?>'>Total OS abertas</span></th>
				<th><span title='<?= $desc_coluna8;?>'>Total OS abertas mais de 20 dias</span></th>
				<th><span title='<?= $desc_coluna9;?>'>Média de dias das OS abertas</span></th>
				<th><span title='<?= $desc_coluna10;?>'>Total OS Reincidente 90 dias</span></th>
				<th><span title='<?= $desc_coluna11;?>'>% OS Reincidente 90 dias</span></th>
				<th><span title='<?= $desc_coluna12;?>'>OS abertas a mais de 48 horas sem peça</span></th>
				<th><span title='<?= $desc_coluna13;?>'>% OS abertas a mais de 48 horas sem peça</span></th>
				<th><span title='<?= $desc_coluna14;?>'>Médias de peças por OS</span></th>
				<th><span title='<?= $desc_coluna15;?>'>Total OS em intervenção 30 dias</span></th>
				<th><span title='<?= $desc_coluna16;?>'>% OS em intervenção 30 dias</span></th>
				<th><span title='<?= $desc_coluna17;?>'>% Comunicado de não conformidade</span></th>
				<th><span title='<?= $desc_coluna18;?>'>Nota 1</span></th>
				<th><span title='<?= $desc_coluna19;?>'>Nota 2</span></th>
				<th><span title='<?= $desc_coluna20;?>'>Nota 3</span></th>
				<th><span title='<?= $desc_coluna21;?>'>Nota Final</span></th>
				<th><span title='<?= $desc_coluna22;?>'>Ranking</span></th>
			</tr>
	<?php

		for($i = 0; $i<$total; $i++){
			//$posto_codigo               = pg_result($res,$i,posto);
			$posto_codigo               = pg_result($res,$i,codigo_posto);
			$posto_nome                 = pg_result($res,$i,nome);
			$qtde_aberta_30             = pg_result($res,$i,qtde_aberta_30);
			$qtde_finalizadas_30        = pg_result($res,$i,qtde_finalizadas_30);
			$porc_finalizada_20         = pg_result($res,$i,porc_finalizada_20);
			$qtde_media_dias_finalizada = pg_result($res,$i,qtde_media_dias_finalizada);
			$qtde_aberta                = pg_result($res,$i,qtde_aberta);
			$qtde_aberta_20             = pg_result($res,$i,qtde_aberta_20);
			$media_atend_mes            = pg_result($res,$i,media_atend_mes);
			$qtde_os_reincidente_90     = pg_result($res,$i,qtde_os_reincidente_90);
			$reincidencia               = pg_result($res,$i,reincidencia);
			$qtde_os_sem_peca_2         = pg_result($res,$i,qtde_os_sem_peca_2);
			$porc_os_sem_peca_2         = pg_result($res,$i,porc_os_sem_peca_2);
			$media_peca_os_30           = pg_result($res,$i,media_peca_os_30);
			$qtde_os_interv_30          = pg_result($res,$i,qtde_os_interv_30);
			$porc_os_interv_30          = pg_result($res,$i,porc_os_interv_30);
			$reclamacoes                = pg_result($res,$i,reclamacoes);
			$nota1                      = pg_result($res,$i,nota1);
			$nota2                      = pg_result($res,$i,nota2);
			$nota3                      = pg_result($res,$i,nota3);
			$nota_final                    = pg_result($res,$i,nota_final);
			$data_geracao               = pg_result($res,$i,data_geracao);
			$ranking               = pg_result($res,$i,ranking);

			 $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	?>

			<tr bgcolor='<?php echo $cor; ?>'>
				<td align='center'><?php echo $posto_codigo; ?></td>
				<td align='center'><?php echo $posto_nome; ?></td>
				<td align='center'><?php echo $qtde_aberta_30; ?></td>
				<td align='center'><?php echo $qtde_finalizadas_30; ?></td>
				<td align='center'><?php echo number_format($porc_finalizada_20,2,',','.'); ?></td>
				<td align='center'><?php echo $qtde_media_dias_finalizada; ?></td>
				<td align='center'><?php echo $qtde_aberta; ?></td>
				<td align='center'><?php echo $qtde_aberta_20; ?></td>
				<td align='center'><?php echo $media_atend_mes; ?></td>
				<td align='center'><?php echo $qtde_os_reincidente_90; ?></td>
				<td align='center'><?php echo number_format($reincidencia,2,',','.'); ?></td>
				<td align='center'><?php echo $qtde_os_sem_peca_2; ?></td>
				<td align='center'><?php echo number_format($porc_os_sem_peca_2,2,',','.'); ?></td>
				<td align='center'><?php echo $media_peca_os_30; ?></td>
				<td align='center'><?php echo $qtde_os_interv_30; ?></td>
				<td align='center'><?php echo number_format($porc_os_interv_30,2,',','.'); ?></td>
				<td align='center'><?php echo number_format($reclamacoes,2,',','.'); ?></td>
				<td align='center'><?php echo number_format($nota1,2,',','.'); ?></td>
				<td align='center'><?php echo number_format($nota2,2,',','.'); ?></td>
				<td align='center'><?php echo number_format($nota3,2,',','.'); ?></td>
				<td align='center'><?php echo number_format($nota_final,2,',','.'); ?></td>
				<td align='center'><?php echo $ranking; ?>º</td>
			</tr>
	<?php
		}
		echo '</table>';
	}
	else{
		echo '<center>Nenhum Resultado Encontrado</center>';
	}
}
include 'rodape.php';
?>