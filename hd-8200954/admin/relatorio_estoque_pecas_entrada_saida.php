<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO ESTOQUE DE PEÇAS - ENTRADA E SAÍDA";

if ($_POST["btn_acao"] == "submit") {

	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$opcao        = $_POST['opcao'];

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if(!strlen($opcao)){
        $msg_erro["msg"][]    = "Favor, escolher o tipo de pesquisa";
        $msg_erro["campos"][] = "opcao";
	}


	// Entrada de Peças
	$sqlEntradaSaida = "SELECT  x.referencia,
                                x.peca      ,
                                x.descricao ,
                                (
                                    SELECT  SUM (tbl_faturamento_item.qtde_estoque) AS qtde
                                    FROM    tbl_faturamento
                                    JOIN    tbl_faturamento_item using (faturamento)
                                    JOIN    tbl_peca    ON  tbl_peca.peca    = tbl_faturamento_item.peca
                                                        AND tbl_peca.fabrica = $login_fabrica
                                                        AND tbl_peca.peca    = x.peca
                                    WHERE   tbl_faturamento.fabrica     IN (10)
                                    AND     tbl_faturamento.cfop        IN ('5106','5102','5403','5405','6106','6102','6403','6405')
                                    AND     tbl_faturamento.posto       = 4311
                                    AND     tbl_faturamento.cancelada   IS NULL
                                    AND     tbl_faturamento.emissao     BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final] 23:59:59'
                              GROUP BY      tbl_peca.peca
                                )   AS qtde_entrada,
                                (
                                    SELECT  SUM (tbl_faturamento_item.qtde) as qtde
                                    FROM    tbl_faturamento
                                    JOIN    tbl_faturamento_item    USING (faturamento)
                                    JOIN    tbl_peca                ON  tbl_peca.peca       = tbl_faturamento_item.peca
                                                                    AND tbl_peca.fabrica    = $login_fabrica
                                                                    AND tbl_peca.peca       = x.peca
                                    WHERE   tbl_faturamento.fabrica         IN (10)
                                    AND     tbl_faturamento.distribuidor    = 4311
				    AND     tbl_faturamento.cfop            IN ('5949','6949')
				    AND     (tbl_faturamento_item.os notnull or tbl_faturamento_item.pedido notnull)
                                    AND     tbl_faturamento.emissao         BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                              GROUP BY      tbl_peca.peca
                                )   AS qtde_garantia,
                                (
                                    SELECT  SUM (tbl_faturamento_item.qtde) as qtde
                                    FROM    tbl_faturamento
                                    JOIN    tbl_faturamento_item    USING (faturamento)
                                    JOIN    tbl_peca                ON  tbl_peca.peca       = tbl_faturamento_item.peca
                                                                    AND tbl_peca.fabrica    = $login_fabrica
                                                                    AND tbl_peca.peca       = x.peca
                                    WHERE   tbl_faturamento.fabrica         IN (10)
                                    AND     tbl_faturamento.distribuidor    = 4311
                                    AND     tbl_faturamento.cfop            IN ('5106','5102','5403','5405','6106','6102','6403','6405')
                                    AND     tbl_faturamento.emissao         BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                              GROUP BY      tbl_peca.peca
                                )   AS qtde_venda,
                                (
                                    SELECT SUM (tbl_posto_estoque_acerto.qtde) AS qtde_acerto
                                    FROM   tbl_posto_estoque_acerto
                                    WHERE  tbl_posto_estoque_acerto.peca = x.peca
                                )   AS qtde_acerto,
                                (
                                    SELECT  qtde
                                    FROM    tbl_posto_estoque
                                    WHERE   peca = x.peca
                                )   AS estoque_atual
                        FROM    tbl_faturamento
                        JOIN    tbl_faturamento_item    USING (faturamento)
                        JOIN    tbl_peca x              ON  x.peca       = tbl_faturamento_item.peca
                                                        AND x.fabrica    = $login_fabrica
                        WHERE   tbl_faturamento.fabrica IN (10)
                        AND     tbl_faturamento.cfop    IN ('5949','6949','5106','5102','5403','5405','6106','6102','6403','6405')
                        AND     tbl_faturamento.emissao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                  GROUP BY      x.peca      ,
                                x.referencia,
                                x.descricao
			";

			$resEntradaSaida = pg_query($con, $sqlEntradaSaida);
            $cont = pg_num_rows($resEntradaSaida);

	if($cont > 0){

		/* Gerar Excel */
		if ($_POST["gerar_excel"]) {

			if ($cont > 0) {
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_estoque_pecas_entrada_saida-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");

				fwrite($file, "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='7' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									RELATÓRIO ESTOQUE DE PEÇAS - ENTRADA E SAIDA
								</th>
							</tr>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estoque Atual</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Entrada</th>
                                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Saída Garantia</th>
                                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Saída Venda</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Acerto</th>
							</tr>
						</thead>
						<tbody>
				");

				for ($i = 0; $i <= $cont; $i++){

					$referencia 	= pg_fetch_result($resEntradaSaida,$i,referencia);
					$peca 			= pg_fetch_result($resEntradaSaida,$i,peca);
					$descricao 		= pg_fetch_result($resEntradaSaida,$i,descricao);
					$entrada 		= pg_fetch_result($resEntradaSaida,$i,qtde_entrada);
					$saida_garantia = pg_fetch_result($resEntradaSaida,$i,qtde_garantia);
					$saida_venda    = pg_fetch_result($resEntradaSaida,$i,qtde_venda);
					$acerto         = pg_fetch_result($resEntradaSaida,$i,qtde_acerto);
					$estoque        = pg_fetch_result($resEntradaSaida,$i,estoque_atual);

					if(!isset($entrada)){
						$entrada = 0;
					}
					if(!isset($saida_garantia)){
						$saida_garantia = 0;
					}
					if(!isset($saida_venda)){
						$saida_venda = 0;
					}
					if(!isset($acerto)){
						$acerto = 0;
					}

					fwrite($file, "
						<tr>
							<td nowrap align='center' valign='top'>{$referencia}</td>
							<td nowrap align='center' valign='top'>{$descricao}</td>
							<td nowrap align='center' valign='top'>{$estoque}</td>
							<td nowrap align='center' valign='top'>{$entrada}</td>
							    <td nowrap align='center' valign='top'>{$saida_garantia}</td>
						    <td nowrap align='center' valign='top'>{$saida_venda}</td>
							<td nowrap align='center' valign='top'>{$acerto}</td>
						</tr>"
					);
					if($opcao == 2){
                        /*
                        * - Se a opção for DETALHADA:
                        * - Será feita uma tabela interna para mostrar as entradas e saídas, com seus acertos
                        *
                        * - PRIMEIRO: ENTRADA
                        */
                    fwrite($file, "
                    <tr>
                        <td colspan='7'>
                            <table >
                                <thead>
                                    <tr>
                                        <th colspan='3' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                                            Entrada
                                        </th>
                                    </tr>
                                    <tr>
                                        <th bgcolor='#596D9B'>Emissão</th>
                                        <th bgcolor='#596D9B'>Nota</th>
                                        <th bgcolor='#596D9B'>Qtde</th>
                                    </tr>
                                </thead>
                                <tbody>
                    ");
                        $sqlPecaEntrada = "
                                SELECT  to_char(tbl_faturamento.emissao,'DD/MM/YYYY')   AS emissao  ,
                                        tbl_faturamento.nota_fiscal                                 ,
                                        SUM (tbl_faturamento_item.qtde_estoque)                 AS qtde
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item using (faturamento)
                                JOIN    tbl_peca    ON  tbl_peca.peca    = tbl_faturamento_item.peca
                                                    AND tbl_peca.fabrica = $login_fabrica
                                                    AND tbl_peca.peca    = $peca
                                WHERE   tbl_faturamento.fabrica     IN (10)
                                AND     tbl_faturamento.cfop        IN ('5106','5102','5403','5405','6106','6102','6403','6405')
                                AND     tbl_faturamento.posto       = 4311
                                AND     tbl_faturamento.cancelada   IS NULL
                                AND     tbl_faturamento.emissao     BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final] 23:59:59'
                          GROUP BY      tbl_faturamento.emissao,
                                        tbl_faturamento.nota_fiscal
                        ";
                        $resPecaEntrada = pg_query($con,$sqlPecaEntrada);

                        for($e = 0;$e < pg_num_rows($resPecaEntrada); $e++){
                            fwrite($file, "
                                    <tr>
                                        <td nowrap align='center' valign='top'>". pg_fetch_result($resPecaEntrada,$e,emissao)."</td>
                                        <td nowrap align='center' valign='top'>". pg_fetch_result($resPecaEntrada,$e,nota_fiscal)."</td>
                                        <td nowrap align='center' valign='top'>". pg_fetch_result($resPecaEntrada,$e,qtde)."</td>
                                    </tr>
                            ");
                        }
                        fwrite($file, "
                                </tbody>
                            </table>
                        </td>
                    </tr>
                        ");
                    /*
                    * - SEGUNDO: SAÍDAS DE PEÇAS EM GARANTIA
                    */
                        fwrite($file, "
                    <tr>
                        <td colspan='7'>
                            <table >
                                <thead>
                                    <tr>
                                        <th colspan='3' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                                            Saída
                                        </th>
                                    </tr>
                                    <tr>
                                        <th bgcolor='#596D9B'>OS</th>
                                        <th bgcolor='#596D9B'>Nota Fiscal</th>
                                        <th bgcolor='#596D9B'>Qtde</th>
                                    </tr>
                                </thead>
                                <tbody>
                        ");

                        $sqlPecaSaidaGarantia = "
                                SELECT  tbl_os.sua_os                                                   ,
                                        tbl_faturamento.nota_fiscal    ,
                                        SUM (tbl_faturamento_item.qtde)             AS qtde
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item    USING (faturamento)
                                JOIN    tbl_peca                ON  tbl_peca.peca       = tbl_faturamento_item.peca
                                                                AND tbl_peca.fabrica    = $login_fabrica
                                                                AND tbl_peca.peca       = $peca
                                JOIN    tbl_os                  ON  tbl_os.os           = tbl_faturamento_item.os
                                WHERE   tbl_faturamento.fabrica         IN (10)
                                AND     tbl_faturamento.distribuidor    = 4311
                                AND     tbl_faturamento.cfop            IN ('5949','6949')
                                AND     tbl_faturamento.emissao         BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                          GROUP BY      tbl_os.sua_os,
			  		tbl_faturamento.nota_fiscal
				UNION
			  SELECT  	null as sua_os                                       ,
                                        tbl_faturamento.nota_fiscal    ,
                                        SUM (tbl_faturamento_item.qtde)         AS qtde
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item    USING (faturamento)
                                JOIN    tbl_peca                ON  tbl_peca.peca       = tbl_faturamento_item.peca
                                                                AND tbl_peca.fabrica    = $login_fabrica
                                                                AND tbl_peca.peca       = $peca
                                JOIN    tbl_pedido              ON  tbl_pedido.pedido   = tbl_faturamento_item.pedido
                                WHERE   tbl_faturamento.fabrica         IN (10)
                                AND     tbl_faturamento.distribuidor    = 4311
                                AND     tbl_faturamento.cfop            IN ('5106','5102','5403','5405','6106','6102','6403','6405')
                                AND     tbl_faturamento.emissao         BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                          GROUP BY      tbl_faturamento.nota_fiscal
                        ";
                        $resSaidaGarantia = pg_query($con,$sqlPecaSaidaGarantia);

                        $total = 0;
                        for($g = 0;$g < pg_num_rows($resSaidaGarantia); $g++){
                            $total += pg_fetch_result($resSaidaGarantia,$g,qtde);

                            fwrite($file, "
                                    <tr>
                                        <td nowrap align='center' valign='top'>".pg_fetch_result($resSaidaGarantia,$g,sua_os)."</td>
                                        <td nowrap align='center' valign='top'>".pg_fetch_result($resSaidaGarantia,$g,nota_fiscal)."</td>
                                        <td nowrap align='center' valign='top'>".pg_fetch_result($resSaidaGarantia,$g,qtde)."</td>
                                    </tr>
                            ");
                        }
                        fwrite($file, "
                                    <tr style='font: bold 11px 'Arial';color:#FFF;background-color:#596D9B'>
                                        <td colspan='2' style='text-align:center;'>Total:</td>
                                        <td style='text-align:right'>$total</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                        ");


                    }
				}

				fwrite($file, "
							<tr>
								<th colspan='5' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de {$cont} registros</th>
							</tr>
						</tbody>
					</table>
				");

				fclose($file);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}
			}

			exit;
		}

	}

}

include "cabecalho_new.php";

$plugins = array(
	"datepicker",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>
<style type="text/css">
caption {
    color: #000;
    font: bold 11px "Arial";
    text-align: center;
}
.titulo_coluna_detalhada {
    background-color: #9370DB !important;
    color: #FFFFFF;
    font: bold 11px "Arial";
    padding: 5px 0 0;
    text-align: center;
}
</style>
	<script language="javascript">
		$(function() {
			$.datepickerLoad(["data_inicial", "data_final"]);
		});
<?
if($opcao == 1){
?>
		 $(function() {
            var table = new Object();
            table['table'] = '#resultado_os_atendimento';
            table['type'] = 'full';
            $.dataTableLoad(table);
        });
<?
}
?>
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

	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>

	<form name='frm-relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

		<div class='titulo_tabela '>Filtro de Peças</div>

		<br />

		<div class='row-fluid'>

			<div class='span2'></div>

			<div class='span2'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_inicial']?>">
						</div>
					</div>
				</div>
			</div>

			<div class='span2'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_final']?>">
						</div>
					</div>
				</div>
			</div>

			<div class='span4'>
				<div class='control-group'>
				<label class='control-label'>&nbsp;</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<label class="radio">
								<input type="radio" name="opcao" value="1" <?=($_POST["opcao"] == "1") ? "CHECKED" : ""?> />
					        	Simplificada
							</label>
							&nbsp;
							<label class="radio">
								<input type="radio" name="opcao" value="2" <?=($_POST["opcao"] == "2") ? "CHECKED" : ""?> />
					        	Detalhada
							</label>
				        </div>
			        </div>
		        </div>
			</div>

			<div class='span2'></div>

		</div>

		<br />

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>

	</form>

<?php

if (isset($_POST['btn_acao']) && count($msg_erro["msg"]) == 0) {

	if ($cont > 0){
		?>

		<table id="resultado_os_atendimento" class='table table-bordered table-fixed' >
			<thead>
				<tr class='titulo_coluna' >
					<th>Referência</th>
					<!-- <th>Peça</th> -->
					<th>Descrição</th>
					<th>Estoque Atual</th>
					<th>Entrada</th>
                    <th>Saída Garantia</th>
                    <th>Saída Venda</th>
					<th>Acerto</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $cont; $i++){

					$referencia     = pg_fetch_result($resEntradaSaida,$i,referencia);
					$peca           = pg_fetch_result($resEntradaSaida,$i,peca);
					$descricao      = pg_fetch_result($resEntradaSaida,$i,descricao);
					$entrada        = pg_fetch_result($resEntradaSaida,$i,qtde_entrada);
					$saida_garantia = pg_fetch_result($resEntradaSaida,$i,qtde_garantia);
					$saida_venda    = pg_fetch_result($resEntradaSaida,$i,qtde_venda);
					$acerto         = pg_fetch_result($resEntradaSaida,$i,qtde_acerto);
					$estoque        = pg_fetch_result($resEntradaSaida,$i,estoque_atual);

					if(!isset($entrada)){
						$entrada = 0;
					}
					if(!isset($saida_garantia)){
						$saida_garantia = 0;
					}
					if(!isset($saida_venda)){
						$saida_venda = 0;
					}
					if(!isset($acerto)){
						$acerto = 0;
					}

					echo "<tr ";
					if($opcao == 2){
                        echo "style='background-color:#D9E2EF !important;'";
					}
                    echo  ">
						<td class='tac'>
							{$referencia}
						</td>
						<!-- <td class='tac'>
							{$peca}
						</td> -->
						<td class='tac' style='text-align: justify;'>
							{$descricao}
						</td>
                        <td class='tac'>
                            {$estoque}
                        </td>
						<td class='tac' name='status_confirmado'>
							{$entrada}
						</td>
                        <td class='tac'>
                            {$saida_garantia}
                        </td>
                        <td class='tac'>
                            {$saida_venda}
                        </td>
						<td class='tac'>
							{$acerto}
						</td>

					</tr>";

					if($opcao == 2){
                        /*
                        * - Se a opção for DETALHADA:
                        * - Será feita uma tabela interna para mostrar as entradas e saídas, com seus acertos
                        *
                        * - PRIMEIRO: ENTRADA
                        */

                        $sqlPecaEntrada = "
                                SELECT  to_char(tbl_faturamento.emissao,'DD/MM/YYYY')   AS emissao  ,
                                        tbl_faturamento.nota_fiscal                                 ,
                                        SUM (tbl_faturamento_item.qtde_estoque)                 AS qtde
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item using (faturamento)
                                JOIN    tbl_peca    ON  tbl_peca.peca    = tbl_faturamento_item.peca
                                                    AND tbl_peca.fabrica = $login_fabrica
                                                    AND tbl_peca.peca    = $peca
                                WHERE   tbl_faturamento.fabrica     IN (10)
                                AND     tbl_faturamento.cfop        IN ('5106','5102','5403','5405','6106','6102','6403','6405')
                                AND     tbl_faturamento.posto       = 4311
                                AND     tbl_faturamento.cancelada   IS NULL
                                AND     tbl_faturamento.emissao     BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final] 23:59:59'
                          GROUP BY      tbl_faturamento.emissao,
                                        tbl_faturamento.nota_fiscal
                        ";
                        $resPecaEntrada = pg_query($con,$sqlPecaEntrada);
                        $contaEntrada   = pg_num_rows($resPecaEntrada);

                        if($contaEntrada > 0){
?>
                    <tr>
                        <td colspan="50%">
                            <table id="resultado_peca_entrada" style='width:450px' class="table table-bordered table-hover">
                                <caption>Entrada</caption>
                                <thead>
                                    <tr class="titulo_coluna_detalhada">
                                        <th>Emissão</th>
                                        <th>Nota</th>
                                        <th>Qtde</th>
                                    </tr>
                                </thead>
                                <tbody>
<?
                            for($e = 0;$e < $contaEntrada; $e++){
?>
                                    <tr>
                                        <td class="tac"><? echo pg_fetch_result($resPecaEntrada,$e,emissao);?></td>
                                        <td class="tar"><? echo pg_fetch_result($resPecaEntrada,$e,nota_fiscal);?></td>
                                        <td class="tar"><? echo pg_fetch_result($resPecaEntrada,$e,qtde);?></td>
                                    </tr>
<?
                            }
?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
<?
                        }
                        /*
                         * - SEGUNDO: SAÍDAS DE PEÇAS EM GARANTIA
                         */
                        $sqlPecaSaidaGarantia = "
                                SELECT  tbl_os.sua_os                                                   ,
                                        tbl_faturamento.nota_fiscal    ,
                                        SUM (tbl_faturamento_item.qtde)             AS qtde
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item    USING (faturamento)
                                JOIN    tbl_peca                ON  tbl_peca.peca       = tbl_faturamento_item.peca
                                                                AND tbl_peca.fabrica    = $login_fabrica
                                                                AND tbl_peca.peca       = $peca
                                JOIN    tbl_os                  ON  tbl_os.os           = tbl_faturamento_item.os
                                WHERE   tbl_faturamento.fabrica         IN (10)
                                AND     tbl_faturamento.distribuidor    = 4311
                                AND     tbl_faturamento.cfop            IN ('5949','6949')
                                AND     tbl_faturamento.emissao         BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                          GROUP BY      tbl_os.sua_os,
			  		tbl_faturamento.nota_fiscal
				UNION
			  SELECT  	null as sua_os                                       ,
                                        tbl_faturamento.nota_fiscal    ,
                                        SUM (tbl_faturamento_item.qtde)         AS qtde
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item    USING (faturamento)
                                JOIN    tbl_peca                ON  tbl_peca.peca       = tbl_faturamento_item.peca
                                                                AND tbl_peca.fabrica    = $login_fabrica
                                                                AND tbl_peca.peca       = $peca
                                JOIN    tbl_pedido              ON  tbl_pedido.pedido   = tbl_faturamento_item.pedido
                                WHERE   tbl_faturamento.fabrica         IN (10)
                                AND     tbl_faturamento.distribuidor    = 4311
                                AND     tbl_faturamento.cfop            IN ('5106','5102','5403','5405','6106','6102','6403','6405')
                                AND     tbl_faturamento.emissao         BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                          GROUP BY      tbl_faturamento.nota_fiscal
			  ";
                        $resSaidaGarantia = pg_query($con,$sqlPecaSaidaGarantia);
                        $contaGarantia = pg_num_rows($resSaidaGarantia);

                        if($contaGarantia > 0){
?>
                    <tr>
                        <td colspan="50%">
                            <table id="resultado_peca_entrada" style='width: 450px' class="table table-bordered">
                                <caption>Saída em Garantia e Faturado </caption>
                                <thead>
                                    <tr class="titulo_coluna_detalhada">
                                        <th>OS</th>
                                        <th>Nota</th>
                                        <th>Qtde</th>
                                    </tr>
                                </thead>
                                <tbody>
<?
                            for($g = 0;$g < $contaGarantia; $g++){
?>
                                    <tr>
                                        <td class="tac"><? echo pg_fetch_result($resSaidaGarantia,$g,sua_os);?></td>
                                        <td class="tar"><? echo pg_fetch_result($resSaidaGarantia,$g,nota_fiscal);?></td>
                                        <td class="tar"><? echo pg_fetch_result($resSaidaGarantia,$g,qtde);?></td>
                                    </tr>
<?
                            }
?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
<?
                        }
					}
				}
?>
			</tbody>
		</table>

		<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>

			</div>

	<?php

	} else {
		echo '
		<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>';
	}

}

echo "<br />";

include 'rodape.php';

?>
