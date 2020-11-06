<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include "autentica_admin.php";
header("Cache-Control: no-cache, must-revalidate");
header('Pragma: no-cache');
$layout_menu = "gerencia";
$title = "RELATÓRIO OSs ABERTAS E FECHADAS NO MÊS";
include "cabecalho_new.php";

include_once 'funcoes.php';

$dataAtual = new DateTime('now');
$anoAtual = $dataAtual->format("Y");
$quantidadeAnosAnteriores = 1;

$mesesDoAno = [
    '01' => traduz('Janeiro'),
    '02' => traduz('Fevereiro'),
    '03' => traduz('Março'),
    '04' => traduz('Abril'),
    '05' => traduz('Maio'),
    '06' => traduz('Junho'),
    '07' => traduz('Julho'),
    '08' => traduz('Agosto'),
    '09' => traduz('Setembro'),
    '10' => traduz('Outubro'),
    '11' => traduz('Novembro'),
    '12' => traduz('Dezembro')
];

if (isset($_POST['consultar'])) {

	$mes = $_POST["mes"];
	$mes_nome = $mesesDoAno[$mes];
	$ano = $_POST["ano"];
	$ultimo_dia = date("t", mktime(0,0,0,$mes,'01',$ano));

	$dt_atual = date("Y-m").'-01';
	$aux_data_inicial = "$ano-$mes-01";
	$aux_data_final   = "$ano-$mes-$ultimo_dia";

	if (empty($mes) || empty($ano)) {
		$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = "mes";
		$msg_erro["campos"][] = "ano";
	} else if (strtotime($aux_data_inicial) > strtotime($dt_atual)) {
		$msg_erro["msg"][]    =traduz("Data inválida");
		$msg_erro["campos"][] = "mes";
		$msg_erro["campos"][] = "ano";
	}

	if (count($msg_erro) == 0) {

		$sqlOS = "	SELECT  COUNT(os_revenda) FILTER(WHERE data_fechamento IS NULL) AS os_em_processo,
					        COUNT(os_revenda) FILTER(WHERE data_fechamento IS NULL AND data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59') AS os_abertas,
					        COUNT(os_revenda) FILTER(WHERE data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59') AS os_fechadas
					FROM tbl_os_revenda
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND tbl_posto_fabrica.posto <> 6359
					AND tbl_os_revenda.excluida IS NOT TRUE";
		$resOS = pg_query($con, $sqlOS);
		if (pg_num_rows($resOS) > 0) {
			$resultOS = pg_fetch_all($resOS);
		}

		$sqlEncerradas = "	SELECT  COUNT(tbl_os_revenda.os_revenda) AS qtde,
								    CASE WHEN 
								            EXTRACT(DAYS FROM (tbl_os_revenda.data_fechamento - tbl_os_revenda.data_abertura::timestamp)) <= 10 THEN 10
								        WHEN 
								            EXTRACT(DAYS FROM (tbl_os_revenda.data_fechamento - tbl_os_revenda.data_abertura::timestamp)) <= 30 THEN 30
								        WHEN 
								            EXTRACT(DAYS FROM (tbl_os_revenda.data_fechamento - tbl_os_revenda.data_abertura::timestamp)) > 30 THEN 31
								    END AS tempo
							FROM tbl_os_revenda
							JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE tbl_os_revenda.fabrica = $login_fabrica
							AND tbl_os_revenda.data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
							AND tbl_os_revenda.excluida IS NOT TRUE
							AND tbl_os_revenda.data_fechamento NOTNULL
							AND tbl_posto_fabrica.posto <> 6359
							GROUP BY tempo
							ORDER BY tempo ASC";

		$resEncerradas = pg_query($con, $sqlEncerradas);
		if (pg_num_rows($resEncerradas) > 0) {
			$resultEncerradas = pg_fetch_all($resEncerradas);
		}

		$sqlMaiorData = "	WITH total_fechada AS   (
							                            SELECT
							                                COUNT(tbl_os_revenda.os_revenda) AS qtde_total,
							                                data_fechamento - data_abertura AS maior_data,
							                                tbl_os_revenda.fabrica
							                            FROM tbl_os_revenda
							                            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os_revenda.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							                            WHERE tbl_os_revenda.fabrica = $login_fabrica
							                            AND tbl_os_revenda.data_fechamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
							                            AND tbl_os_revenda.excluida IS NOT TRUE
							                            AND tbl_os_revenda.data_fechamento NOTNULL
							                            AND tbl_posto_fabrica.posto <> 6359
							                            GROUP BY data_fechamento, data_abertura, tbl_os_revenda.fabrica
							                            ORDER BY maior_data DESC
							                        ),

							        qtde_total AS   (
							                            SELECT SUM(qtde_total) AS total_qtde,
							                            fabrica
							                            FROM total_fechada
							                            GROUP BY fabrica
							                        )
							        SELECT total_qtde, maior_data 
							        FROM total_fechada 
							        LEFT JOIN qtde_total ON total_fechada.fabrica = qtde_total.fabrica 
							        GROUP BY maior_data, total_qtde 
							        ORDER BY maior_data DESC 
							        LIMIT 1";

		$resMaiorData = pg_query($con, $sqlMaiorData);
		if (pg_num_rows($resMaiorData) > 0) {
			$resultMaiorData = pg_fetch_all($resMaiorData);
		}
	}
}

$plugins = array(
	"shadowbox"
);

include 'plugin_loader.php';
?>

<style type="text/css">
	.sub {
		background-color: #596d9b !important;
	    font: bold 11px "Arial";
	    color: #FFFFFF;
	    text-align: center;
	    padding: 5px 0 0 0;
	}

	.hov:hover {
		background-color: #f3eded !important;
	}

	.posicao {
		width: 50% !important;
		margin: 0 auto !important;
	}
</style>

<script type="text/javascript">

$(function() {
	Shadowbox.init();

	$("#os_ab").click(function () {
		let dt_ini = $(this).data("ini");
		let dt_fin = $(this).data("fim");
		let url = "detalhe_relatorio_os_aberta_fechada_mes.php?opcao=aberta&dt_ini="+dt_ini+"&dt_fin="+dt_fin;
        Shadowbox.open({
            content:url,
            player: "iframe",
            title:  "Ordens de Serviço",
            width:  900,
            height: 600
        });
	});
	
	$("#os_fc").click(function () {
		let dt_ini = $(this).data("ini");
		let dt_fin = $(this).data("fim");
		let url = "detalhe_relatorio_os_aberta_fechada_mes.php?opcao=fechada&dt_ini="+dt_ini+"&dt_fin="+dt_fin;
        Shadowbox.open({
            content:url,
            player: "iframe",
            title:  "Ordens de Serviço",
            width:  900,
            height: 600
        });
	});

	$("#os_p").click(function () {
		let url = "detalhe_relatorio_os_aberta_fechada_mes.php?opcao=processada";
        Shadowbox.open({
            content:url,
            player: "iframe",
            title:  "Ordens de Serviço",
            width:  900,
            height: 600
        });
	});

	$("#fc_10").click(function () {
		let dt_ini = $(this).data("ini");
		let dt_fin = $(this).data("fim");
		let url = "detalhe_relatorio_os_aberta_fechada_mes.php?opcao=10dias&dt_ini="+dt_ini+"&dt_fin="+dt_fin;
        Shadowbox.open({
            content:url,
            player: "iframe",
            title:  "<?=traduz('Ordens de Serviço')?>",
            width:  900,
            height: 600
        });
	});

	$("#fc_30").click(function () {
		let dt_ini = $(this).data("ini");
		let dt_fin = $(this).data("fim");
		let url = "detalhe_relatorio_os_aberta_fechada_mes.php?opcao=30dias&dt_ini="+dt_ini+"&dt_fin="+dt_fin;
        Shadowbox.open({
            content:url,
            player: "iframe",
            title:  "<?=traduz('Ordens de Serviço')?>",
            width:  900,
            height: 600
        });
	});

	$("#fc_31").click(function () {
		let dt_ini = $(this).data("ini");
		let dt_fin = $(this).data("fim");
		let url = "detalhe_relatorio_os_aberta_fechada_mes.php?opcao=31dias&dt_ini="+dt_ini+"&dt_fin="+dt_fin;
        Shadowbox.open({
            content:url,
            player: "iframe",
            title:  "<?=traduz('Ordens de Serviço')?>",
            width:  900,
            height: 600
        });
	});

	$("#fc_maior").click(function () {
		let dt_ini = $(this).data("ini");
		let dt_fin = $(this).data("fim");
		let url = "detalhe_relatorio_os_aberta_fechada_mes.php?opcao=maior&dt_ini="+dt_ini+"&dt_fin="+dt_fin;
        Shadowbox.open({
            content:url,
            player: "iframe",
            title:  "<?=traduz('Ordens de Serviço')?>",
            width:  900,
            height: 200
        });
	});
});

</script>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<form  method='POST' action='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">	
	<div class="titulo_tabela"><?=traduz("Parâmetros de Pesquisa")?></div>
	<br />
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span3'>
        	<div class='control-group <?=(in_array("mes", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicio'><?=traduz("Mês")?></label>
                <div class='controls controls-row'>
                	<div class="span12">
                		<h5 class='asteristico'>*</h5>
        				<select name="mes">
            				<?php foreach($mesesDoAno as $numeroDoMes => $nomeDoMes) { ?>
                					<option value="<?=$numeroDoMes?>" <?= ($numeroDoMes == $mes) ? 'selected' : null ?> > <?=$nomeDoMes?> </option>
            				<?php } ?>
        				</select>
            		</div>
            	</div>
            </div>
        </div>
        <div class='span2'></div>
        <div class='span3'>
            <div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicio'><?=traduz('Ano')?></label>
                <div class='controls controls-row'>
                	<div class="span12">
                		<h5 class='asteristico'>*</h5>
		                <select name="ano">
		                    <option value="<?=$anoAtual?>"> <?=$anoAtual?> </option>
		                    <?php for ($i=1; $i<=$quantidadeAnosAnteriores; $i++) { ?>
		                        	<option value="<?=($anoAtual-$i)?>" <?= (($anoAtual-$i) == $ano) ? 'selected' : null ?> > <?= ($anoAtual-$i) ?> </option>
		                    <?php } ?>
		                </select>
                	</div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
	<br />
    <div class='row-fluid'>
		<div class="span12 tac">
			<input type="submit" class="btn" name="consultar" value="Consultar" />
		</div>
    </div>
</form>

<?php
	if (count($resultOS) > 0) {
?>
		<table class='table table-striped table-bordered table-fixed posicao'>
			<thead>
				<tr class="titulo_tabela">
					<th colspan="2"><?=traduz("Abertas e Fechadas no Mês")?></th>
				</tr>
				<tr class='titulo_coluna'>
					<th></th>
					<th><?=$mes_nome?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="sub tac"><?=traduz("OSs Abertas")?></td>
					<td class="hov tac" id="os_ab" data-ini="<?=$aux_data_inicial?>" data-fim="<?=$aux_data_final?>"><?=$resultOS[0]["os_abertas"]?></td>
				</tr>
				<tr>
					<td class="sub tac"><?=traduz("OSs Fechadas")?></td>
					<td class="hov tac" id="os_fc" data-ini="<?=$aux_data_inicial?>" data-fim="<?=$aux_data_final?>"><?=$resultOS[0]["os_fechadas"]?></td>
				</tr>
				<tr>
					<td class="sub tac"><?=traduz("OSs Em Processo")?></td>
					<td class="hov tac" id="os_p"><?=$resultOS[0]["os_em_processo"]?></td>
				</tr>
			</tbody>
		</table>
<br />
<br />
<?php
		if (count($resultEncerradas) > 0 && count($resultMaiorData) > 0) {
			
			$ate10       = 0;
			$ate30       = 0;
			$mais30      = 0;
			$maximoTempo = 0;

			foreach ($resultEncerradas as $k => $v) {				
				if ($v["tempo"] == 10) {
					$ate10 = round(($v["qtde"] / $resultMaiorData[0]["total_qtde"]) * 100);
				}

				if ($v["tempo"] == 30) {
					$ate30 = round(($v["qtde"] / $resultMaiorData[0]["total_qtde"]) * 100);
				}

				if ($v["tempo"] == 31) {
					$mais30 = round(($v["qtde"] / $resultMaiorData[0]["total_qtde"]) * 100);
				}

				$maximoTempo = $resultMaiorData[0]["maior_data"];
			}
?>
			<table class='table table-striped table-bordered table-fixed posicao'>
				<thead>
					<tr class="titulo_tabela">
						<th colspan="2"><?=traduz("% Das OSs Fechadas Dentro do Mês")?></th>
					</tr>
					<tr class='titulo_coluna'>
						<th></th>
						<th><?=$mes_nome?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="sub tac"><?=traduz("Até 10 Dias")?></td>
						<td class="hov tac" id="fc_10" data-ini="<?=$aux_data_inicial?>" data-fim="<?=$aux_data_final?>"><?=$ate10?>%</td>
					</tr>
					<tr>
						<td class="sub tac"><?=traduz("De 11 - 30 Dias")?></td>
						<td class="hov tac" id="fc_30" data-ini="<?=$aux_data_inicial?>" data-fim="<?=$aux_data_final?>"><?=$ate30?>%</td>
					</tr>
					<tr>
						<td class="sub tac"><?=traduz("Mais de 30 Dias")?></td>
						<td class="hov tac" id="fc_31" data-ini="<?=$aux_data_inicial?>" data-fim="<?=$aux_data_final?>"><?=$mais30?>%</td>
					</tr>
					<tr>
						<td class="sub tac"><?=traduz("Máximo")?></td>
						<td class="hov tac" id="fc_maior" data-ini="<?=$aux_data_inicial?>" data-fim="<?=$aux_data_final?>"><?=$maximoTempo?> Dias</td>
					</tr>
				</tbody>
			</table>
<?php 
		}
	
	} else {
		if (count($msg_erro) == 0 && $_POST) {
?>
			<div class="container">
				<div class="row-fluid">
					<div class="alert alert-warning">
						<h4><?=traduz("Nenhum resultado encontrado")?></h4>
					</div>
				</div>
			</div>
<?php
		}
	}
?>

<?php include 'rodape.php'; ?>
