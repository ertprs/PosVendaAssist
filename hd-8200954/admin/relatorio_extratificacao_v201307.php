<?php
/**
 *
 * relatorio_extratificacao.php
 *
 * @author  Francisco Ambrozio
 * @version 2013.07
 *
 */

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";
$layout_menu = "gerencia";
$title = "RELATÓRIO DE ESTRATIFICAÇÃO";
include "cabecalho.php";

include 'relatorio_extratificacao.class.php';
/**
 *
 * REGRAS:
 *
 *   - o relatório é extraído de 24 meses retroativo ao mês atual
 *   - o admin seleciona familia, meses e índice
 *   - de acordo com o número de meses selecionado conta as OSs de cada mês
 *   - taxa de falha: total de OSs (meses) / total produção
 *   - população: soma de N meses anteriores
 *
 *
 *
 */
	$relatorio = new relatorioExtratificacao;
	$relatorio->run();
	$resultado = $relatorio->getResultView();

	$data_inicial = $relatorio->getDataInicial();
	if (!empty($data_inicial)) {
		$arr_data = explode('-', $data_inicial);
		$ano_pesquisa = $arr_data[0];
		$mes_pesquisa = $arr_data[1];
	}

	$familia = $relatorio->getFamilia();
	$qtde_meses = $relatorio->getMeses();
	$irc = $relatorio->getIndexIRC();

	if (empty($qtde_meses)) {
		$qtde_meses = 15;
	}

	if (empty($irc)) {
		$irc = 1;
	}

	$msg_erro = $relatorio->getMsgErro();
?>

<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete-tkirda.min.js'></script>
<script type='text/javascript' src='../js/jquery.numeric.js'></script>

<script>
	var mes = null;
	function pareto(fabricacao, abertura, meses, familia, peca01, peca02) {

		if (!peca01) {
			peca01 = 0;
		}

		if (!peca02) {
			peca02 = 0;
		}

		var url="relatorio_extratificacao_pareto.php?fb=" + fabricacao + "&ab=" + abertura + "&fm=" + meses + "&fa=" + familia + "&p1=" + peca01 + "&p2=" + peca02;
		window.open (url, "pareto", "height=640,width=1020,scrollbars=1");
	}

	function mostraRelatorio(relatorio) {
		var tx_os = document.getElementById('taxa_falha');
		var tx_os_comp = document.getElementById('tx_falha_comparativo_0');
		var tx_os_comp_15 = document.getElementById('tx_falha_comparativo_1');
		var irc = document.getElementById('irc_0');
		var irc_15 = document.getElementById('irc_1');
		var irc_15_mes = document.getElementById('irc_15_mes');
		var cfe_parq = document.getElementById('gr_cfe_0');
		var cfe_prod = document.getElementById('gr_cfe_1');
		var cfe_fat = document.getElementById('gr_cfe_2');

		if (relatorio == 'tx_os') {
			tx_os.style.display = "block";
			tx_os_comp.style.display = "none";
			tx_os_comp_15.style.display = "none";
			irc.style.display = "none";
			irc_15.style.display = "none";
			irc_15_mes.style.display = "none";
			cfe_parq.style.display = "none";
			cfe_prod.style.display = "none";
			cfe_fat.style.display = "none";
		}
		else if (relatorio == 'tx_os_comp') {
			tx_os.style.display = "none";
			tx_os_comp.style.display = "block";
			tx_os_comp_15.style.display = "none";
			irc.style.display = "none";
			irc_15.style.display = "none";
			irc_15_mes.style.display = "none";
			cfe_parq.style.display = "none";
			cfe_prod.style.display = "none";
			cfe_fat.style.display = "none";
		}
		else if (relatorio == 'tx_os_comp_15') {
			tx_os.style.display = "none";
			tx_os_comp.style.display = "none";
			tx_os_comp_15.style.display = "block";
			irc.style.display = "none";
			irc_15.style.display = "none";
			irc_15_mes.style.display = "none";
			cfe_parq.style.display = "none";
			cfe_prod.style.display = "none";
			cfe_fat.style.display = "none";
		}
		else if (relatorio == 'irc') {
			tx_os.style.display = "none";
			tx_os_comp.style.display = "none";
			tx_os_comp_15.style.display = "none";
			irc.style.display = "block";
			irc_15.style.display = "none";
			irc_15_mes.style.display = "none";
			cfe_parq.style.display = "none";
			cfe_prod.style.display = "none";
			cfe_fat.style.display = "none";
		}
		else if (relatorio == 'irc_15') {
			tx_os.style.display = "none";
			tx_os_comp.style.display = "none";
			tx_os_comp_15.style.display = "none";
			irc.style.display = "none";
			irc_15.style.display = "block";
			irc_15_mes.style.display = "none";
			cfe_parq.style.display = "none";
			cfe_prod.style.display = "none";
			cfe_fat.style.display = "none";
		}
		else if (relatorio == 'irc_15_mes') {
			tx_os.style.display = "none";
			tx_os_comp.style.display = "none";
			tx_os_comp_15.style.display = "none";
			irc.style.display = "none";
			irc_15.style.display = "none";
			irc_15_mes.style.display = "block";
			cfe_parq.style.display = "none";
			cfe_prod.style.display = "none";
			cfe_fat.style.display = "none";
		}
		else if (relatorio == 'cfe_parq') {
			tx_os.style.display = "none";
			tx_os_comp.style.display = "none";
			tx_os_comp_15.style.display = "none";
			irc.style.display = "none";
			irc_15.style.display = "none";
			irc_15_mes.style.display = "none";
			cfe_parq.style.display = "block";
			cfe_prod.style.display = "none";
			cfe_fat.style.display = "none";
		}
		else if (relatorio == 'cfe_prod') {
			tx_os.style.display = "none";
			tx_os_comp.style.display = "none";
			tx_os_comp_15.style.display = "none";
			irc.style.display = "none";
			irc_15.style.display = "none";
			irc_15_mes.style.display = "none";
			cfe_parq.style.display = "none";
			cfe_prod.style.display = "block";
			cfe_fat.style.display = "none";
		}
		else if (relatorio == 'cfe_fat') {
			tx_os.style.display = "none";
			tx_os_comp.style.display = "none";
			tx_os_comp_15.style.display = "none";
			irc.style.display = "none";
			irc_15.style.display = "none";
			irc_15_mes.style.display = "none";
			cfe_parq.style.display = "none";
			cfe_prod.style.display = "none";
			cfe_fat.style.display = "block";
		}
	}

	function download(link) {
		window.location=link;
	}

	function getPecasByFamilia(familia, apagaVal) {
		$.ajax({
			url: "pecas_lb_familia.php?familia=" + familia, 
			dataType: "text",
			success: function(data) {
				if (apagaVal) {
					$('#peca01').val('');
					$('#peca02').val('');
				}
				autocomplete(data);
			}
		});
	}

	function autocomplete (data) {
		var pecas = $.parseJSON(data);

		if (!pecas) {
			return false;
		}

		$('#peca01').autocomplete({
			lookup: pecas,
			minChars: 3
		});

		$('#peca02').autocomplete({
			lookup: pecas,
			minChars: 3
		});
	}

	$(document).ready(function() {
		var familia = $('#familia').val();

		if (familia) { getPecasByFamilia(familia, false); };
	});
</script>

<style type="text/css">
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
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	button.download { margin-top : 15px; }
	table.form tr td{
		padding:10px 30px 0 0;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
		padding: 0 10px;
	}
	.texto_avulso{
	    font: 14px Arial; color: rgb(89, 109, 155);
	    background-color: #d9e2ef;
	    text-align: center;
	    width:700px;
	    margin: 10px auto;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}
	div.formulario table.form{
		padding:10px 0 10px 60px;
		text-align:left;
	}
	.subtitulo{
		background-color: #7092BE;
		font:bold 14px Arial;
		color: #FFFFFF;
		text-align:center;
	}
	tr th a {color:white !important;}
	tr th a:hover {color:blue !important;}

	div.formulario form p{ margin:0; padding:0; }

	.autocomplete-suggestions { text-align: left; border: 1px solid #999; background: #FFF; cursor: default; overflow: auto; -webkit-box-shadow: 1px 4px 3px rgba(50, 50, 50, 0.64); -moz-box-shadow: 1px 4px 3px rgba(50, 50, 50, 0.64); box-shadow: 1px 4px 3px rgba(50, 50, 50, 0.64); }
	.autocomplete-suggestion { padding: 2px 5px; white-space: nowrap; overflow: hidden; }
	.autocomplete-selected { background: #F0F0F0; }

</style>


<div class="formulario" style="width:700px; margin:auto;">
	<div id="msg"></div>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm">
		<table cellspacing="1" align="center" class="form">
			<tr>
				<td style="min-width:120px;">
					<label for="ano">Ano</label>
					<select name="ano_pesquisa">
						<?php
						date_default_timezone_set('America/Sao_Paulo');
						$curr_year = date('Y');
						$curr_month = date('m');
						$anos = range(2006, ($curr_year + 3));

						if (empty($ano_pesquisa)) {
							$ano_pesquisa = $curr_year;
						}

						foreach ($anos as $ano) {
							echo '<option value="' , $ano , '"';
							if ($ano == $ano_pesquisa) {
								echo ' selected="SELECTED"';
							}
							echo '>' , $ano , '</option>';
						}
						?>
					</select>
				</td>
				<td style="min-width:120px;">
					<label for="mes">Mês</label>
					<select name="mes_pesquisa">
						<?php
						$meses = array("01" => "Janeiro",
										"02" => "Fevereiro",
										"03" => "Março",
										"04" => "Abril",
										"05" => "Maio",
										"06" => "Junho",
										"07" => "Julho",
										"08" => "Agosto",
										"09" => "Setembro",
										"10" => "Outubro",
										"11" => "Novembro",
										"12" => "Dezembro"
										);

						if (empty($mes_pesquisa)) {
							$mes_pesquisa = $curr_month;
						}

						foreach ($meses as $idx => $mes) {
							echo '<option value="' , $idx , '"';
							if ($idx == $mes_pesquisa) {
								echo ' selected="SELECTED"';
							}
							echo '>' , $mes , '</option>';
						}

						?>
					</select>
				</td>
			</tr>

			<tr>
				<td>
					<label for="familia">Família</label>
					<select name="familia" id="familia" onChange="getPecasByFamilia(this.value, true)">
						<option value=""></option>
						<?php
						$qry_familias = pg_query($con, "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo = 't' ORDER BY descricao");
						if (pg_num_rows($qry_familias) > 0) {
							while ($fetch = pg_fetch_assoc($qry_familias)) {
								echo '<option value="' , $fetch['familia'] , '"';
								if ($familia == $fetch['familia']) {
									echo ' SELECTED="SELECTED"';
								}
								echo '>' , $fetch['descricao'] , '</option>';
							}
						}
						?>
					</select>
				</td>
			</tr>

			<tr>
				<td>
					<label for="meses">Qtde Meses</label>
					<input type="text" name="meses" id="meses" value="<?php echo $qtde_meses;?>" class="frm" style="width: 40px;" maxlength="2">
				</td>
				<td>
					<label for="index_irc">Índice</label>
					<input type="text" name="index_irc" id="index_irc" value="<?php echo $irc;?>" class="frm" style="width: 40px;">
				</td>
			</tr>

			<tr>
				<td colspan="2">
					<label for="peca01">Peça 1</label>
					<input type="text" name="peca01" id="peca01" value="<?php echo $peca01;?>" class="frm" style="width: 380px;">
				</td>
			</tr>

			<tr>
				<td colspan="2">
					<label for="peca02">Peça 2</label>
					<input type="text" name="peca02" id="peca02" value="<?php echo $peca02;?>" class="frm" style="width: 380px;">
				</td>
			</tr>

			<tr>
				<td colspan="2" style="padding-top:15px;" align="center">
					<input type="submit" name="btn_acao" value="Consultar" />
				</td>
			</tr>
		</table>
	</form>
</div><br/>

<?php
if (!empty($resultado)) {
	echo $resultado;
}
?>

<script type="text/javascript">
    $("#meses").numeric();
    $("#irc").numeric();
</script>

<?php echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>'; ?>

<script type="text/javascript">
	<?php if ( !empty($msg_erro) ){ ?>
			$("#erro").appendTo("#msg").fadeIn("slow");
	<?php } ?>
</script>

<?php include 'rodape.php'; ?>
