<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "gerencia";
include "autentica_admin.php";

include "funcoes.php";

$title = "Indicadores de Eficiência";
$layout_menu = "gerencia";

if (isset($_POST['CSV']) && isset($_POST['gerar_excel'])) {
    $data_inicial = $_POST["data_inicial"];
    $data_final = $_POST["data_final"];
    $tipo_atendimento = $_POST["tipo_atendimento"];

    $xdata_inicial = explode('/',$data_inicial);
    $ano_data_inicial = $xdata_inicial[2];
    $xdata_inicial = $xdata_inicial[2]."-".$xdata_inicial[1]."-".$xdata_inicial[0];
    $xdata_final = explode('/',$data_final);
    $xdata_final = $xdata_final[2]."-".$xdata_final[1]."-".$xdata_final[0];

    $sql = "SELECT
                os.os,
                JSON_FIELD('osKof', hdc.dados) AS os_kof,
                f.descricao AS familia,
                (
                    CASE WHEN os.qtde_km <= 25 THEN
                        'Local'
                    WHEN os.qtde_km <= 75 THEN
                        'Foráneo'
                    ELSE
                        'Rural'
                    END
                ) AS regiao,
                TO_CHAR(rsl.create_at, 'DD/MM/YYYY HH24:MI') AS data_integracao,
                TO_CHAR(ose.termino_atendimento, 'DD/MM/YYYY HH24:MI') AS termino_atendimento
            FROM tbl_os os
            INNER JOIN tbl_os_extra ose ON ose.os = os.os
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            WHERE os.fabrica = {$login_fabrica}
            AND ose.termino_atendimento IS NOT NULL
            AND os.hd_chamado IS NOT NULL
            AND os.tipo_atendimento = $tipo_atendimento
            AND ose.termino_atendimento between '{$ano_data_inicial}-01-01 00:00' and '$xdata_final 23:59'";

    $dataArquivo = date("Ymdhis");
    $arquivo_nome = "indicadorEficiencia{$dataArquivo}.csv";
    $file     = "xls/{$arquivo_nome}";
    $fileTemp = "/tmp/{$arquivo_nome}";
    $fp     = fopen($fileTemp,'w');

    $head = "OS;OS KOF;FAMÍLIA;REGIÃO;DATA INTEGRAÇÃO;TÉRMINO ATENDIMENTO\n";
    fwrite($fp, $head);

    $res = pg_query($con, $sql);
    $count = pg_num_rows($res);

    for($i = 0; $i < $count; $i++){
        $os                  = pg_fetch_result($res, $i, "os");
        $os_kof              = pg_fetch_result($res, $i, "os_kof");
        $familia             = pg_fetch_result($res, $i, "familia");
        $regiao              = pg_fetch_result($res, $i, "regiao");
        $data_integracao     = pg_fetch_result($res, $i, "data_integracao");
        $termino_atendimento = pg_fetch_result($res, $i, "termino_atendimento");

        $body = "$os;$os_kof;$familia;$regiao;$data_integracao;$termino_atendimento\n";

        fwrite($fp, $body);
    }

    fclose($fp);
    if(file_exists($fileTemp)){
        system("mv $fileTemp $file");

        if(file_exists($file)){
            echo $file;
        }
    }
    exit;    
}

include "cabecalho_new.php";

function getDates($startDate, $stopDate) {
    $dateArray   = array();
    $currentDate = $startDate;

    while (date($currentDate) <= date($stopDate)) {
        $dateArray[] = date($currentDate);

        $currentDate = date("Y-m-d", strtotime($currentDate." +1 day"));
    }

    return $dateArray;
}

function qtdeDiasUteis($startDate, $stopDate = null) {
    if (is_null($stopDate)) {
        $stopDate = date("Y-m-d", strtotime("today"));
    }

    $dateArray = getDates($startDate, $stopDate);

    $qtde = 0;

    $dateArray = array_filter($dateArray, function($date) {
        if (!in_array(date("w", strtotime($date)), array(0, 6))) {
            return true;
        }

        return false;
    });

    return count($dateArray);
}

if ($_POST) {
    $data_inicial = $_POST["data_inicial"];
    $data_final = $_POST["data_final"];
	$tipo_atendimento = $_POST["tipo_atendimento"];

	$xdata_inicial = explode('/',$data_inicial);
    $ano_data_inicial = $xdata_inicial[2];
    $xdata_inicial = $xdata_inicial[2]."-".$xdata_inicial[1]."-".$xdata_inicial[0];

    $xdata_final = explode('/',$data_final);
    $ano_data_final = $xdata_final[2];
    $xdata_final = $xdata_final[2]."-".$xdata_final[1]."-".$xdata_final[0];

	$i_xdata_inicial = new DateTime($xdata_inicial);
	$i_xdata_final = new DateTime($xdata_final);
	$intervalo = $i_xdata_inicial->diff($i_xdata_final);
	$dias = $intervalo->days;

	if($dias > 180) {
		$msg_erro["msg"][] = " Intervalo de data não pode ser maior que 180 dias";
	}

    if (empty($data_inicial) || empty($data_final)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "data_inicial";
        $msg_erro["campos"][]   = "data_final";
    }

    if (empty($msg_erro["msg"])) {
        $tabela = "tmp_os_indicadores_eficiencia_produtividade_{$login_admin}_{$login_fabrica}";        

        $sql = "
            SELECT
                os.os,
                f.descricao AS familia,
                (
                    CASE WHEN os.qtde_km <= 25 THEN
                        'Local'
                    WHEN os.qtde_km <= 75 THEN
                        'Foráneo'
                    ELSE
                        'Rural'
                    END
                ) AS regiao,
                ose.termino_atendimento AS data_os,
				EXTRACT(EPOCH FROM ose.termino_atendimento - rsl.create_at) AS intervalo,
				ose.termino_atendimento::date - rsl.create_at::date AS intervalo_dia
            INTO TEMP TABLE {$tabela}
            FROM tbl_os os
            INNER JOIN tbl_os_extra ose ON ose.os = os.os
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            WHERE os.fabrica = {$login_fabrica}
            AND ose.termino_atendimento IS NOT NULL
            AND os.hd_chamado IS NOT NULL
			and os.tipo_atendimento = $tipo_atendimento
            AND ose.termino_atendimento between '{$ano_data_inicial}-01-01 00:00' and '$xdata_final 23:59';

            SELECT COUNT(*) FROM {$tabela};
        ";
        $res = pg_query($con, $sql);

        if (!pg_fetch_result($res, 0, 0)) {
            $msg_erro["msg"][] = "Nenhum resultado encontrado";
        } else {
            $resultado = true;
        }
    }
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error" >
        <strong><?=implode("<br />", $msg_erro["msg"])?></strong>
    </div>
<?php
}
?>

<div class="row no-print" >
    <b class="obrigatorio pull-right" >* Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario no-print" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>

    <br />

    <div class="row-fluid" >
        <div class="span1" ></div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span6'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span6'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group'>
					<label class="control-label" for="tipo_atendimento">Tipo Atendimento</label>
					<div class="controls controls-row">
						<div class="span7">
							<select name='tipo_atendimento' class='frm' readonly>
								<?php
									$sql = "SELECT tipo_atendimento,descricao
									          FROM tbl_tipo_atendimento
									         WHERE fabrica = $login_fabrica
									           AND ativo AND LOWER(descricao) = 'corretiva'";
									$res   = pg_exec($con,$sql);
									$total = pg_numrows($res);

									for($i = 0; $i < $total; $i++)
									{
										$tipo_atendimento_id = pg_result($res,$i,tipo_atendimento);
										$descricao   = pg_result($res,$i,descricao);
									?>
										<option value="<?php echo $tipo_atendimento_id; ?>" <?php if($tipo_atendimento_id == $tipo_atendimento) echo 'selected'; ?>><?php echo $descricao; ?></option>
								<?php }?>
							</select>
						</div>
					</div>
				</div>
			</div>
    </div>

    <br />

    <p class="tac" >
        <button type="submit" name="pesquisa" class="btn" >Pesquisar</button>
    </p>

    <br />
</form>

<?php
if ($resultado == true) {
    $sql = "
        SELECT
            familia,
            regiao,
            '{$data_inicial} - {$data_final}' AS data,
            AVG(intervalo) AS intervalo,
            COUNT(os) AS qtde_os,
            CASE WHEN familia = 'REFRIGERADOR' OR familia = 'VENDING MACHINE' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.regiao = {$tabela}.regiao
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 24
                )
            WHEN familia = 'POST MIX' OR familia = 'CHOPEIRA' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.regiao = {$tabela}.regiao
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 3
                )
            END AS eficiencia
        FROM {$tabela}
        WHERE data_os between '$data_inicial 00:00' and '$data_final 23:59'
        AND regiao = 'Local'
        GROUP BY familia, regiao, data

        UNION
        SELECT
            familia,
            'Geral' AS regiao,
            '{$data_inicial} - {$data_final}' AS data,
            AVG(intervalo) AS intervalo,
            COUNT(os) AS qtde_os,
            CASE WHEN familia = 'REFRIGERADOR' OR familia = 'VENDING MACHINE' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 24
                )
            WHEN familia = 'POST MIX' OR familia = 'CHOPEIRA' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 3
                )
            END AS eficiencia
        FROM {$tabela}
        WHERE data_os between '$data_inicial 00:00' and '$data_final 23:59'
        GROUP BY familia, data

        UNION
        SELECT
            familia,
            regiao,
            '{$ano_data_inicial}' AS data,
            AVG(intervalo) AS intervalo,
            COUNT(os) AS qtde_os,
            CASE WHEN familia = 'REFRIGERADOR' OR familia = 'VENDING MACHINE' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.regiao = {$tabela}.regiao
                    AND x.data_os between '{$ano_data_inicial}-01-01 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 24
                )
            WHEN familia = 'POST MIX' OR familia = 'CHOPEIRA' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.regiao = {$tabela}.regiao
                    AND x.data_os between '{$ano_data_inicial}-01-01 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 3
                )
            END AS eficiencia
        FROM {$tabela}
        WHERE data_os between '{$ano_data_inicial}-01-01 00:00' and '$data_final 23:59'
        AND regiao = 'Local'
        GROUP BY familia, regiao

        UNION
        SELECT
            familia,
            'Geral' AS regiao,
            '{$ano_data_inicial}' AS data,
            AVG(intervalo) AS intervalo,
            COUNT(os) AS qtde_os,
            CASE WHEN familia = 'REFRIGERADOR' OR familia = 'VENDING MACHINE' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.data_os between '{$ano_data_inicial}-01-01 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 24
                )
            WHEN familia = 'POST MIX' OR familia = 'CHOPEIRA' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.data_os between '{$ano_data_inicial}-01-01 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 3
                )
            END AS eficiencia
        FROM {$tabela}
        WHERE data_os between '{$ano_data_inicial}-01-01 00:00' and '$data_final 23:59'
        GROUP BY familia

        ORDER BY familia ASC, regiao ASC, data ASC
    ";
    $res = pg_query($con, $sql);

    $resultado     = array();

    array_map(function($r) {
        global $resultado;

        if (!isset($resultado[$r["familia"]][$r["data"]])) {
            $resultado[$r["familia"]][$r["data"]] = array(
                "Local"   => array(),
                "Geral"   => array()
            );
        }

        $resultado[$r["familia"]][$r["data"]][$r["regiao"]] = array(
            "intervalo"  => $r["intervalo"],
            "eficiencia" => $r["eficiencia"],
            "qtde_os"    => $r["qtde_os"]
        );
    }, pg_fetch_all($res));

    $qtd_meses = 0;

    foreach ($resultado as $familia => $f) {
    ?>
        <table class="table table-bordered" >
            <thead>
                <tr class="titulo_coluna" >
                    <th>Família</th>
                    <th>Indicadores</th>
                    <th colspan="6" >BRASIL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td rowspan="5" style="vertical-align: middle; text-align: center;" nowrap ><?=$familia?></td>
                    <td>&nbsp;</td>
                    <th class="titulo_coluna" colspan="2" ><?=$data_inicial." - ".$data_final?></th>
                    <th class="titulo_coluna" colspan="2" ><?=($ano_data_inicial != $ano_data_final) ? "{$ano_data_inicial}/{$ano_data_final}" : $ano_data_inicial?></th>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <th class="titulo_coluna" >Local</th>
                    <th class="titulo_coluna" >Geral</th>
                    <th class="titulo_coluna" >Local</th>
                    <th class="titulo_coluna" >Geral</th>
                </tr>
                <tr>
                    <td nowrap >Tempo de Resposta</td>
                    <?php
                    foreach ($f as $data => $d) {
                        foreach ($d as $regiao => $r) {
                        ?>
                            <td class="tac" ><?=secondsToTimeString($r["intervalo"])?></td>
                        <?php
                        }
                    }
                    ?>
                </tr>
                <tr>
                    <?php
                    switch ($familia) {
                        case "REFRIGERADOR":
                        case "VENDING MACHINE":
                            $label_eficiencia = "Eficiência D+1 (%)";
                            $label_volume = "Eficiência D+1 (Volume de Serviços)";
                            break;

                        case "POST MIX":
                        case "CHOPEIRA":
                            $label_eficiencia = "Eficiência 3h (%)";
                            $label_volume = "Eficiência 3h (Volume de Serviços)";
                            break;
                    }
                    ?>

                    <td nowrap ><?=$label_eficiencia?></td>

                    <?php
                    foreach ($f as $data => $d) {
                        foreach ($d as $regiao => $r) {
                        ?>
                            <td class="tac" ><?=(!strlen($r["qtde_os"])) ? 0 : number_format(($r["eficiencia"] * 100) / $r["qtde_os"], 2, ".", "")?>%</td>
                        <?php
                        }
                    }
                    ?>
                </tr>
                <tr>
                    <td nowrap ><?=$label_volume?></td>
                    <?php
                    foreach ($f as $data => $d) {
                        foreach ($d as $regiao => $r) {
                        ?>
                            <td class="tac" ><?=(!strlen($r["qtde_os"])) ? 0 : $r["qtde_os"]?></td>
                        <?php
                        }
                    }
                    ?>                    
                </tr>
            </tbody>
        </table>

        <br />
    <?php
    }
}

if ($resultado == true) {
    $parametros = json_encode(
                            array(
                                'CSV' => '1', 
                                'data_inicial' => $data_inicial, 
                                'data_final' => $data_final, 
                                'tipo_atendimento' => $tipo_atendimento
                            )
                    );

    $button  = "<center><div id='gerar_excel' class='btn_excel'>";
    $button .= "<input type='hidden' id='jsonPOST' value='{$parametros}'/>";
    $button .= "<span><img src='imagens/excel.png'/></span>";
    $button .= "<span class='txt'>Gerar Arquivo Excel</span></div></center>";

    echo $button;
}

$plugins = array(
	"select2",
	"mask"
);

include "plugin_loader.php";
?>
<div id="graficos" ></div>

<style>

@media print {
    .no-print {
        display: none;
    }

    table, table tr, table td, table th {
        border: 1px solid;
        border-collapse: collapse;
    }

    table td, table th {
        padding: 10px;
    }

    table {
        width: 100%;
    }
}

</style>

<script>
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
	});

</script>

<br />

<?php
include "rodape.php";
?>
