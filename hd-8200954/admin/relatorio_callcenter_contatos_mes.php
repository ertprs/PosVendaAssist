<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include "autentica_admin.php";
header("Cache-Control: no-cache, must-revalidate");
header('Pragma: no-cache');
$layout_menu = "gerencia";
$title = "RELATÓRIO CONTATO CALLCENTER NO MÊS";
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

		$sqlHD = "	SELECT COUNT(tbl_hd_chamado.hd_chamado) AS total_hd,	
						   tbl_hd_chamado_origem.descricao AS origem,
						    CASE WHEN 
						            tbl_hd_chamado_extra.consumidor_revenda = 'C' THEN 'Consumidor1'
						        WHEN 
						            tbl_hd_chamado_extra.consumidor_revenda = 'R' THEN 'Revenda'
						        WHEN 
						            tbl_hd_chamado_extra.consumidor_revenda = 'S' THEN 'Construtora'
						    END AS tipo_cliente
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra USING (hd_chamado)
					JOIN tbl_hd_chamado_origem USING (hd_chamado_origem)
					WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_hd_chamado.posto IS NULL
					GROUP BY tbl_hd_chamado_origem.descricao, tbl_hd_chamado_extra.consumidor_revenda
					ORDER BY tipo_cliente";
		$resHD = pg_query($con, $sqlHD);
		if (pg_num_rows($resHD) > 0) {
			$resultHD = pg_fetch_all($resHD);
		}

		$sqlClassificacao = "	SELECT COUNT(tbl_hd_chamado.hd_chamado) AS total_hd,	
									   tbl_hd_chamado_origem.descricao AS origem,
									   tbl_hd_classificacao.descricao AS classificacao
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra USING (hd_chamado)
								JOIN tbl_hd_chamado_origem USING (hd_chamado_origem)
								JOIN tbl_hd_classificacao USING (hd_classificacao)
								WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
								AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
								AND tbl_hd_chamado.posto IS NULL
								GROUP BY tbl_hd_chamado_origem.descricao, tbl_hd_classificacao.descricao
								ORDER BY classificacao";
		$resClassificacao = pg_query($con, $sqlClassificacao);
		if (pg_num_rows($resClassificacao) > 0) {
			$resultClassificacao = pg_fetch_all($resClassificacao);
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
		min-width: 70%;
		width: auto !important;
		margin: 0 auto !important;
	}

	.cor_total {
		background-color: #e8e5e5 !important;
	}

	.cor_tt {
		background-color: #c3b3c0 !important;
	}

</style>

<script type="text/javascript">

$(function() {
	Shadowbox.init();

	$(".detalhe_hd").click(function () {		
		let dt_ini = $(this).data("ini");
		let dt_fin = $(this).data("fim");
		let origem = $(this).data("origem");
		let tipo   = $(this).data("tipo");
		let url = "detalhe_relatorio_callcenter_contatos_mes.php?opcao=hd&dt_ini="+dt_ini+"&dt_fin="+dt_fin+"&origem="+origem+"&tipo="+tipo;
        Shadowbox.open({
            content:url,
            player: "iframe",
            title:  "Atendimentos Callcenter",
            width:  1200,
            height: 600
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
	if (count($resultHD) > 0) {

		$legendas = [];
		$resultNew = [];
		$legendaCliente = [];
		$resultadoCliente = [];
		$totalHD = 0;

		foreach ($resultHD as $key => $value) {
			$totalHD += $value["total_hd"];
		}

		foreach ($resultHD as $key => $value) {
			$legendas[] = $value["origem"];
		}

		$legendas = array_unique($legendas);
		array_unshift($legendas, "Total");

		foreach ($resultHD as $key => $value) {
			$legendaCliente[] = $value["tipo_cliente"];
		}

		$legendaCliente = array_unique($legendaCliente);
		array_push($legendaCliente, 'Total Protocolos'); 

		foreach ($legendas as $k => $v) {
			foreach ($resultHD as $key => $value) {
				if ($v == "Total") {
					$resultNew[$v][$value["tipo_cliente"]] += $value["total_hd"]; 
				} else if ($v == $value["origem"]) {
					$resultNew[$v][$value["tipo_cliente"]] = $value["total_hd"]; 
				} 
			}
		}

		foreach ($legendaCliente as $key => $value) {
			foreach ($resultNew as $k => $v) {
				$resultadoCliente[$k][$value] = (!isset($v[$value])) ? 0 : $v[$value];
			}
		}

		foreach ($resultadoCliente as $key => $value) {
			$tt = 0;
			foreach ($value as $k => $v) {
				$tt +=  $v;
			}
			$resultadoCliente[$key]["Total Protocolos"] = $tt;
		}

		$colspanP = count($legendas) + count($legendas) + 1;
?>
		<table class='table table-striped table-bordered table-fixed posicao'>
			<thead>
				<tr class="titulo_tabela">
					<th colspan="<?=$colspanP?>"><?=traduz("Contatos Realizados Por Público")?></th>
				</tr>
				<tr class='titulo_coluna'>
					<th></th>
<?php 
				foreach ($legendas as $desc => $v) {
?>	
					<th colspan="2"><?=traduz($v)?></th>
<?php			
				}
?>
				</tr>
			</thead>
			<tbody>
<?php 
				foreach ($legendaCliente as $posicao => $valor) {
					$bk_color = "cor_total";
					$corTt = ("Total Protocolos" == $valor) ? "cor_tt" : "";
?>	
					<tr>
						<td class="sub tac"><?=traduz($valor)?></td>
<?php
							foreach ($resultadoCliente as $key => $value) {
?>
								<td class="hov tac detalhe_hd <?=$bk_color?> <?=$corTt?>" data-origem="<?=$key?>" data-tipo="<?=$valor?>" data-ini="<?=$aux_data_inicial?>" data-fim="<?=$aux_data_final?>"><?=$value[$valor]?></td>
								<td class="hov tac <?=$bk_color?> <?=$corTt?>"><?=round(($value[$valor]/$value["Total Protocolos"])*100)?>%</td>
<?php
								$bk_color = "";
							}
?>
					</tr>
<?php
				}
?>
			</tbody>
		</table>
		<br />
		<br />
<?php
		if (count($resultClassificacao) > 0 && count($resultHD) > 0) {

			$legendasCla = [];
			$resultNewCla = [];
			$legendaClassificacao = [];
			$resultadoClassificacao = [];
			$totalHDCla = 0;

			foreach ($resultClassificacao as $key => $value) {
				$totalHDCla += $value["total_hd"];
			}

			foreach ($resultClassificacao as $key => $value) {
				$legendasCla[] = $value["origem"];
			}

			$legendasCla = array_unique($legendasCla);
			array_push($legendasCla, "Total");

			foreach ($resultClassificacao as $key => $value) {
				$legendaClassificacao[] = $value["classificacao"];
			}

			$legendaClassificacao = array_unique($legendaClassificacao);
			array_push($legendaClassificacao, 'total classificacao'); 

			foreach ($legendasCla as $k => $v) {
				foreach ($resultClassificacao as $key => $value) {
					if ($v == "Total") {
						$resultNewCla[$v][$value["classificacao"]] += $value["total_hd"]; 
					} else if ($v == $value["origem"]) {
						$resultNewCla[$v][$value["classificacao"]] = $value["total_hd"]; 
					} 
				}
			}

			foreach ($legendaClassificacao as $key => $value) {
				foreach ($resultNewCla as $k => $v) {
					$resultadoClassificacao[$k][$value] = (!isset($v[$value])) ? 0 : $v[$value];
				}
			}

			foreach ($resultadoClassificacao as $key => $value) {
				$tt = 0;
				foreach ($value as $k => $v) {
					$tt +=  $v;
				}
				$resultadoClassificacao[$key]["total classificacao"] = $tt;
			}

			$colspanCla = count($legendasCla) + 1;
?>
			<table class='table table-striped table-bordered table-fixed posicao'>
				<thead>
					<tr class="titulo_tabela">
						<th colspan="<?=$colspanCla?>"><?=traduz("Contatos Realizados Por Classificação")?></th>
					</tr>
					<tr class='titulo_coluna'>
						<th></th>
<?php 
						foreach ($legendasCla as $desc => $v) {
?>	
							<th><?=traduz($v)?></th>
<?php			
						}
?>
					</tr>
				</thead>
				<tbody>
<?php 
					foreach ($legendaClassificacao as $posicao => $valor) {
						$i = 1;
						$countC = count($legendasCla);
						$corTt = ("total classificacao" == $valor) ? "cor_tt" : "";
?>	
						<tr>							
							<td class="sub tac"><?=traduz($valor)?></td>
<?php
								foreach ($resultadoClassificacao as $key => $value) {
									$bk_color = ($i == $countC) ? "cor_total" : "";
?>
									<td class="hov tac <?=$bk_color?> <?=$corTt?>"><?=round(($value[$valor]/$totalHDCla)*100)?>%</td>
<?php
									$bk_color = "";
									$i++;
								}
?>
						</tr>
<?php
					}
?>
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
