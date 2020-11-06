<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];
	$status             = $_POST['status'];
	$origem 			= $_POST['origem'];

	$cond_1 = '';
	$cond_2 = '';
	$cond_3 = '';
	$cond_4 = '';

	if ($data_inicial == 'dd/mm/aaaa' or $data_final == 'dd/mm/aaaa')
		$msg_erro = traduz('Data inválida!');

	if(strlen($msg_erro)==0){
		$xdata_inicial = dateFormat($data_inicial, 'dmy', 'y-m-d');
		$xdata_final   = dateFormat($data_final,   'dmy', 'y-m-d');

		if (is_bool($xdata_inicial) or is_bool($xdata_final) or
		    $xdata_inicial > $xdata_final)
			$msg_erro = traduz("Data inválida");
	}

	if($login_fabrica == 52){
		if (strtotime($xdata_inicial.'+1 year') < strtotime($xdata_final) ) {
            $msg_erro = traduz('O intervalo entre as datas não pode ser maior que 1 ano');
        }
    }

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = "AND tbl_hd_chamado_extra.produto = $produto ";
		}
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = "AND tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}
	if(strlen($status)>0){
		if($login_fabrica == 74 AND $status == "nao_resolvido"){
			$cond_3 = "AND lower(tbl_hd_chamado.status) <> 'resolvido'  ";
		}else{
			$cond_3 = "AND tbl_hd_chamado.status = '$status'  ";
		}
	}
	if($login_fabrica==6){
		$cond_4 = "AND tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if(in_array($login_fabrica, array(169,170)) AND strlen(trim($origem)) > 0){
		$cond_origem = " AND tbl_hd_chamado_extra.origem = '$origem' ";
	}

	if($login_fabrica==2){
		$condicoes = $produto . ";" . $natureza_chamado . ";" . $status . ";" . $posto . ";" . $xdata_inicial . ";" .$xdata_final;
	}

	if($login_fabrica == 52){
		$campos = ", to_char(tbl_hd_chamado.data,'YYYY') AS ano,
				 ABS(EXTRACT(MONTH FROM tbl_hd_chamado.data)) AS mes
				 INTO TEMP tmp_atendimento_periodo_fricon ";
		$group_by = ", tbl_hd_chamado.data";
	}
}
$layout_menu = "callcenter";
$title = traduz("RELATÓRIO POR ATENDENTES");

include "cabecalho_new.php";

?>



<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<?
$plugins = array(
	"mask",
	"datepicker",
	"shadowbox"
 );

include "plugin_loader.php";
?>
<script language='javascript' src='../ajax.js'></script>
<script type="text/javascript" charset="utf-8">
	$(function(){

		$("#data_inicial").datepicker().mask("99/99/9999");
		$("#data_final").datepicker().mask("99/99/9999");

		Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

	});

	function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }
</script>


<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,defeito_reclamado,adm){

	var url = "callcenter_relatorio_atendente_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&defeito_reclamado="+defeito_reclamado+"&adm="+adm;
	if (navigator.userAgent.match(/Chrome/gi)) {
		url = unescape(encodeURIComponent(url));
	}
	janela = window.open(url, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}

/* POP-UP IMPRIMIR */
	function abrir(URL) {
		var width = 700;
		var height = 600;
		var left = 90;
		var top = 90;

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
	}

</script>

<?
	$xstatus             = $_POST['status'];
?>
	<? if(strlen($msg_erro)>0){ ?>
		<div class='alert alert-danger'><? echo $msg_erro; ?></div>
	<? } ?>
<div class="row">
   	<b class="obrigatorio pull-right">  * <?=traduz("Campos obrigatórios ")?></b>
</div>
<FORM class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

		<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span4">
					<div class="control-group">
						<label class="control-label" for=''><?=traduz('Data Inicial')?></label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<input class="span4" type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial;  ?>">
							<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
						</div>
					</div>
				</div>
				<div class="span4">
					<div class="control-group">
						<label class="control-label" for=''><?=traduz('Data Final')?></label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<input class="span4" type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; ?>">
							<!--<img border="0" src="imagens/lupa.png" align="absmiddle" 	onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
						</div>
					</div>
				</div>
				<div class="span2"></div>
			</div>
			<div class="row-fluid">
			<div class="span2"></div>
				<div class="span4">
					<div class="control-group">
						<label class="control-label" for='produto_referencia'><?=traduz('Ref. Produto')?></label>
						<div class='controls-row input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" size="12" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        	<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
				<div class="span4">
					<div class="control-group">
						<label class="control-label" for='produto_descricao'><?=traduz('Descrição')?></label>
						<div class='controls-row input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" size="30" class='frm' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        	<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class="row-fluid">
			<div class="span2"></div>
				<div class="span4">
				<label class="control-label" for=''><?=traduz('Natureza')?></label>
					<div class='controls controls-row'>
						<select name='natureza_chamado' class='frm'>
						<option value=''></option>

						<?PHP
							//HD39566
							$sqlx = "SELECT nome            ,
											descricao
									FROM tbl_natureza
									WHERE fabrica=$login_fabrica
									AND ativo = 't'
									ORDER BY nome";

							$resx = pg_exec($con,$sqlx);
								if(pg_numrows($resx)>0){
									for($y=0;pg_numrows($resx)>$y;$y++){
										$nome     = trim(pg_result($resx,$y,nome));
										$descricao     = trim(pg_result($resx,$y,descricao));
										echo $nome;
										echo "<option value='$nome'";
											if($natureza_chamado == $nome) {
												echo "selected";
											}
										echo ">$descricao</option>";
									}

								}
						?>

						</select>
					</div>
				</div>
				<div class="span4">
					<label class="control-label" for=''><?=traduz('Status')?></label>
					<div class='controls controls-row'>
						<select name="status" class='frm'>
						<option value=''></option>
						<?
						if($login_fabrica == 74){
							$selected = ($xstatus == "nao_resolvido") ? "selected" : "";
							echo "<option value='nao_resolvido' $selected>".traduz("Não resolvido")."</option>";
						}
							$sql = "select distinct status from tbl_hd_status where fabrica = $login_fabrica order by status";
							$res = pg_exec($con,$sql);
							if(pg_numrows($res)>0){
								for($x=0;pg_numrows($res)>$x;$x++){
									$status = pg_result($res,$x,status);
									echo "<option value='$status'";
									if($xstatus == $status){
										echo " selected";
									}
									echo ">$status</option>";
								}

							}
						?>
						</select>
					</div>
				</div>
			<div class="span2"></div>
			</div>

			<?php if(in_array($login_fabrica, array(169,170))){ ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class="span4">
					<label class="control-label" for=''><?=traduz('Origem')?></label>
					<div class='controls controls-row'>
						<select name='origem'>
						<option value=''></option>

						<?PHP
							$sql = "SELECT hd_chamado_origem,
											descricao
									FROM tbl_hd_chamado_origem
									WHERE fabrica = {$login_fabrica}
									ORDER BY descricao";
							$resOrigem = pg_query($con,$sql);

							if(pg_numrows($resOrigem)>0){
								for($y=0;pg_numrows($resOrigem)>$y;$y++){
									$hd_chamado_origem 	= trim(pg_result($resOrigem,$y,hd_chamado_origem));
									$descricao     		= trim(pg_result($resOrigem,$y,descricao));
									echo "<option value='$descricao'";
										if($origem == $descricao) {
											echo "selected";
										}
									echo ">$descricao</option>";
								}

							}
						?>

						</select>
					</div>
				</div>
				<div class='span6'></div>
			</div>
			<?php } ?>
			<br />
			<div class="row-fluid">
				<div class="span5"></div>
				<div class="span2">
					<input class="btn" type='submit' style="cursor:pointer" name='btn_acao' value='<?=traduz("Consultar")?>'>
				</div>
				<div class="span5"></div>
			</div>
</FORM>
<br />

<?

if(strlen($btn_acao)>0){


	if(strlen($msg_erro)==0){
		if ($login_fabrica != 2) {

	        if($login_fabrica == 74){

				$tipo = "producao"; // teste - producao

				$admin_fale_conosco = ($tipo == "producao") ? 6409 : 6437;

				$cond_admin_fale_conosco = " AND tbl_admin.admin NOT IN ($admin_fale_conosco) ";

			}

			$sql = "SELECT tbl_admin.admin                          ,
							tbl_admin.login                         ,
							count(tbl_hd_chamado.hd_chamado) as qtde
							$campos
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
						$cond_1
						$cond_2
						$cond_3
						$cond_4
						$cond_origem
						$cond_admin_fale_conosco
						GROUP by tbl_admin.admin, tbl_admin.login $group_by
						ORDER BY qtde DESC;
				";
				if($login_fabrica==52){
					$sql .= "select * from tmp_atendimento_periodo_fricon;";
				}
		} else {
			$sql = "SELECT tbl_admin.admin                               ,
							tbl_admin.login                              ,
							count(tbl_hd_chamado_item.hd_chamado) as qtde
					FROM tbl_hd_chamado_item
					JOIN tbl_hd_chamado_extra on tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado
					JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
					JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					AND tbl_hd_chamado_item.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
					$cond_1
					$cond_2
					$cond_3
					$cond_4
					GROUP by tbl_admin.admin, tbl_admin.login
					ORDER BY qtde DESC;
					";
		}
		$res = pg_exec($con,$sql);
		//echo nl2br($sql); echo "<br><br>";
		//if($ip=="189.47.76.179")echo $sql;
		if(pg_numrows($res)>0){
			if($login_fabrica == 52){
				$mes_extenso = array(" ","janeiro", "fevereiro", "março", "abril", "maio", "junho", "julho", "agosto", "setembro", "outubro", "novembro", "dezembro");

				$sql = "SELECT  tmp_atendimento_periodo_fricon.admin,
								tmp_atendimento_periodo_fricon.login,
								SUM(tmp_atendimento_periodo_fricon.qtde) AS qtde,
								tmp_atendimento_periodo_fricon.mes,
								tmp_atendimento_periodo_fricon.ano
								INTO TEMP tmp_atendimento_admin_fricon
							FROM tmp_atendimento_periodo_fricon
							GROUP BY tmp_atendimento_periodo_fricon.mes,
								tmp_atendimento_periodo_fricon.ano,
								tmp_atendimento_periodo_fricon.admin,
								tmp_atendimento_periodo_fricon.login
							ORDER BY ano DESC ,mes DESC";
				$res = pg_exec($con,$sql);

				$sqlT = "SELECT SUM(qtde) FROM tmp_atendimento_periodo_fricon";
				$resT = pg_query($con,$sqlT);
				$total_periodo = pg_result($resT,0,0);

				$sqlQ = "SELECT ano,mes,SUM(qtde) as qtde_mes
							FROM tmp_atendimento_admin_fricon
							GROUP BY mes, ano
							ORDER BY ano,mes";
				$resQ = pg_query($con,$sqlQ);
				$qtdeMes = pg_fetch_all($resQ);
				foreach($qtdeMes as $tMes) {
					$totalMes[$tMes['mes']] = $tMes['qtde_mes'];
				}
				//echo nl2br(print_r($qtdeMes, true));



				$sqlM = "SELECT DISTINCT (mes),ano FROM tmp_atendimento_admin_fricon ORDER BY ano , mes";
				$resM = pg_query($con,$sqlM);
				?>
				<table width="700" align="center" class='table table-striped table-bordered table-fixed'>
					<thead>
						<caption class="titulo_tabela">Atendentes</caption>
						<tr class="titulo_coluna">
							<th>Atendente</th>
						<?php
							for($x = 0;$x < pg_numrows($resM); $x++){
								$mes = pg_result($resM,$x,mes);
								$ano = pg_result($resM,$x,ano);
								echo "<th>$mes_extenso[$mes]/$ano</th>";
							}
							echo "<th>".traduz("Total Período")."</th>";
						?>
						</tr>
						<tr class="titulo_coluna">
							<td>&nbsp;</td>
							<?php
								for($x = 0;$x <= pg_numrows($resM); $x++){
									echo "<td>
											<table width='100%'>
												<tr>
													<td style='border-left: none; width='50%'><font color='black'>".traduz("Qtde")."</font></td>
													<td width='50%'><font color='black'>%</font></td>
												</tr>
											</table>
										  </td>";
								}
							?>
						</tr>
					</thead>
				<?php


				$sqlT = "SELECT DISTINCT admin,login FROM tmp_atendimento_admin_fricon";
				$resT = pg_query($con,$sqlT);
				for($i = 0; $i < pg_numrows($resT) ; $i++){
					$admin = pg_result($resT,$i,admin);
					$login = pg_result($resT,$i,login);

					echo "<tr>";
					echo "<td align='left'>$login</td>";
					$sqlS = " SELECT qtde, ABS(mes) AS mes, ano
							  FROM tmp_atendimento_admin_fricon
							  WHERE admin = $admin
							  ORDER BY ano,mes";
					$resS = pg_query($con,$sqlS);

					$valsS = pg_fetch_all($resS);
					//echo array2table($valsS, $login);

					unset($mesS);
					foreach($valsS as $infoS) {
						$i_mes = $infoS['mes'];
						$i_ano = $infoS['ano'];
						$i_qtde= $infoS['qtde'];
						$mesS[$i_mes] = $i_qtde;
					}

					$mes1 = pg_fetch_result($resM,0,mes);
					$mesMax = ($mes1 > 1) ? $mes1 + pg_num_rows($resM) : pg_num_rows($resM);
					if ($mesMax > 12) {
						$mesMax = $mesMax - 12;
						$mes1 = (int) "-$mes1";
					}

					$mesMax = ($mes1 == 1) ? $mesMax : $mesMax - 1;

					for($j = $mes1; $j <= $mesMax ; $j++){
						echo "<td nowrap>";
						$imes = abs($j);
						$porcentagem = ($mesS[$imes] * 100) / $totalMes[$imes];
						$total_periodo_atendente += $mesS[$imes];
						echo "<table width='100%'>
											<tr>
												<td width='50%'>";
													echo (isset($mesS[$imes])) ? $mesS[$imes] : "S/A";
												echo "</td>
												<td width='50%'>".number_format($porcentagem,2,',','.')."</td>
											</tr>
										</table>";
						echo "</td>";
					}

					$total_porcentagem_atendente = ($total_periodo_atendente * 100) / $total_periodo;
					echo "<td nowrap> <table width='100%'>
											<tr>
												<td width='50%'>";
													echo $total_periodo_atendente;
												echo "</td>
												<td width='50%'>".number_format($total_porcentagem_atendente,2,',','.')."</td>
											</tr>
										</table>";
						echo "</td>";

					echo "</tr>";
					$total_porcentagem += $total_porcentagem_atendente;
					$total_periodo_atendente = 0;
					$total_porcentagem_atendente = 0;
				}
				echo "<tr style='color: black;font-weight: bolder;'>";
						echo "<td>".traduz("Total")."</td>";
					for($y = 0; $y < count($qtdeMes); $y++){
						echo "<td> <table width='100%'><tr>";
						echo "<td>".$qtdeMes[$y]['qtde_mes']."</td>";
						echo "<td>100&nbsp;&nbsp;</td>";
						echo "</tr></table>";
					}
					echo "<td> <table width='100%'><tr>";
					echo "<td align='50%'>".$total_periodo."</td>";
					echo "<td align='50%'>100</td>";
					echo "</tr></table>";
				echo "</table>";
			} else{
				echo "<table width='700' border='0' align='center' class='table table-striped table-bordered table-fixed'>";
				echo "<thead><TR class='titulo_coluna'>\n";
				echo "<td align='left'>".traduz("Atendente")."</TD>\n";
				echo "<TD>Qtde</TD>\n";
				echo "</TR ></thead>\n";

				$grafico_atendente = [];

				for($y=0;pg_numrows($res)>$y;$y++){
					$adm              = pg_result($res,$y,admin);
					$login_admin      = pg_result($res,$y,login);
					$qtde             = pg_result($res,$y,qtde);
	#				if(strlen($descricao)==0){$descricao = "Sem defeito reclamado";}
					$grafico_atendente[$y]["name"] = utf8_encode($login_admin);
					$grafico_atendente[$y]["y"]    = (int) $qtde;

					$total_qtde += $qtde;

					if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
					echo "<TR bgcolor='$cor'>\n";
					echo "<TD align='left' nowrap><a href=\"javascript: AbreCallcenter('$xdata_inicial','$xdata_final','$produto','$natureza_chamado','$xstatus','$xperiodo','$defeito_reclamado','$adm')\">$login_admin</a></TD>\n";
					echo "<TD class='tac' nowrap>$qtde</TD>\n";
					echo "</TR >\n";
				} 
				echo "<TR class='titulo_coluna'>\n";
					echo "<th align='center' nowrap>Total</th>\n";
					echo "<th align='center' nowrap>$total_qtde</th>\n";
					echo "</TR >\n";
				echo "</table>";

				$json_grafico = json_encode($grafico_atendente);
				?>
				<script src="https://code.highcharts.com/highcharts.js"></script>
				<script src="https://code.highcharts.com/modules/exporting.js"></script>
				<script src="https://code.highcharts.com/modules/export-data.js"></script>

				<div id="container" style="min-width: 310px; height: 400px; max-width: 600px; margin: 0 auto"></div>
				<script>
					Highcharts.chart('container', {
					    chart: {
					        plotBackgroundColor: null,
					        plotBorderWidth: null,
					        plotShadow: false,
					        type: 'pie'
					    },
					    title: {
					        text: 'Relatório por atendente <?= $data_inicial ?> - <?= $data_final ?>'
					    },
					    tooltip: {
					        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
					    },
					    plotOptions: {
					        pie: {
					            allowPointSelect: true,
					            cursor: 'pointer',
					            dataLabels: {
					                enabled: true,
					                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
					                style: {
					                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
					                }
					            }
					        }
					    },
					    series: [{
					        name: 'Brands',
					        colorByPoint: true,
					        data: <?= $json_grafico ?>
					    }]
					});
				</script>
				<?php

			}
		}else{
			echo "<center>".traduz("Nenhum Resultado Encontrado")."</center>";
		}

	}
}

?>

<p>

<? include "rodape.php" ?>
