<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$title       = "MANUTENÇÃO GERAÇÃO EXTRATO";
$layout_menu = 'cadastro';

if (filter_input(INPUT_POST,'btn_acao')) {
    $semana         = filter_input(INPUT_POST,'semana');
    $estados_regiao = filter_input(INPUT_POST,'estados_regiao');

    if (empty($semana) || empty($estados_regiao)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'semana';
        $msg_erro['campos'][]   = 'estados_regiao';
    }

    if (count($msg_erro) == 0) {
        if (strpos($estados_regiao,"|")) {
            $campos_regiao = explode("|",$estados_regiao);

            if (count(array_diff(array("NE","NO","CO"),$campos_regiao)) == 0) {
                $sqlEstados = "
                    SELECT  estado
                    FROM    tbl_estado
                    WHERE   regiao IN ('NORDESTE','NORTE','CENTRO-OESTE')
              ORDER BY      estado
                ";
                $resEstados = pg_query($con,$sqlEstados);

                $allEstados = pg_fetch_all_columns($resEstados);

            } else if (count(array_diff($campos_regiao,array("ES","RJ","MG"))) == 0) {
                $allEstados = $campos_regiao;
            }
        } else {
            if ($estados_regiao == "SUL") {

                $sqlEstados = "
                    SELECT  estado
                    FROM    tbl_estado
                    WHERE   regiao = '$estados_regiao'
                ";
                $resEstados = pg_query($con,$sqlEstados);

                $allEstados = pg_fetch_all_columns($resEstados);
            } else {
                $allEstados[] = $estados_regiao;
            }
        }

        foreach ($allEstados as $uf) {
            /*
             * - Procura se há um intervalo
             * cadastrado para o Estado
             */
            $sqlProcuraUf = "
                SELECT  tbl_intervalo_extrato.intervalo_extrato
                FROM    tbl_intervalo_extrato
                WHERE   fabrica = $login_fabrica
                AND     estado = '$uf'
            ";
            $resProcura = pg_query($con,$sqlProcuraUf);
            $intervalo_extrato = pg_fetch_result($resProcura,0,intervalo_extrato);

            pg_query($con,"BEGIN TRANSACTION");
            if (empty($intervalo_extrato)) {
                $sqlInsUf = "
                    INSERT INTO tbl_intervalo_extrato (
                        fabrica,
                        descricao,
                        dia_semana,
                        semana,
                        estado,
                        observacao,
                        periodicidade
                    ) VALUES (
                        $login_fabrica,
                        'Mensal',
                        'Mon',
                        $semana,
                        '$uf',
                        'Os extratos do estado de $uf serão gerados uma vez por mes, toda $semana ª segunda feira.',
                        30
                    ) RETURNING intervalo_extrato
                ";
            } else {
                $sqlInsUf = "
                    UPDATE  tbl_intervalo_extrato
                    SET     semana = $semana,
                            observacao = 'Os extratos do estado de $uf serão gerados uma vez por mes, toda $semana ª segunda feira.'
                    WHERE   fabrica = $login_fabrica
                    AND     estado = '$uf'
                    AND     intervalo_extrato = $intervalo_extrato
                ";
            }

            $resInsUf = pg_query($con,$sqlInsUf);

            if (pg_last_error($con)) {
                $msg_erro['msg'][] = "Problemas ao gravar a informação".pg_last_error($con);
                pg_query($con,"ROLLBACK TRANSACTION");
                break;
            }

            if (empty($intervalo_extrato)) {
                $intervalo_extrato = pg_fetch_result($resInsUf,0,0);
            }

            /*
             * - Gravação do tipo de geração
             * de extrato escolhido
             */

            $sqlBuscaPostos = "
                SELECT  tbl_posto_fabrica.posto
                FROM    tbl_posto_fabrica
                JOIN    tbl_posto USING (posto)
                WHERE   tbl_posto_fabrica.fabrica           = $login_fabrica
                AND     tbl_posto.estado                    = '$uf'
                AND     tbl_posto_fabrica.credenciamento    IN('CREDENCIADO','EM DESCREDENCIAMENTO')
            ";
            $resBuscaPostos = pg_query($con,$sqlBuscaPostos);

            while ($resPostos = pg_fetch_object($resBuscaPostos)) {
                /*
                 * - Verificar se o posto já tem cadastro
                 * na geração de extrato por estado
                 */
                $sqlVer = "
                    SELECT  tbl_tipo_gera_extrato.tipo_gera_extrato AS gera_extrato_uf
                    FROM    tbl_tipo_gera_extrato
                    WHERE   posto = ".$resPostos->posto."
                ";
                $resVer = pg_query($con,$sqlVer);
                $gera_extrato_uf = pg_fetch_result($resVer,0,gera_extrato_uf);

                if (empty($gera_extrato_uf)) {
                    $sqlIns = "
                        INSERT INTO tbl_tipo_gera_extrato (
                            descricao,
                            fabrica,
                            posto,
                            intervalo_extrato
                        ) VALUES (
                            'Opção Extrato',
                            $login_fabrica,
                            ".$resPostos->posto.",
                            $intervalo_extrato
                        )
                    ";
                } else {
                    $sqlIns = "
                        UPDATE  tbl_tipo_gera_extrato
                        SET     intervalo_extrato = $intervalo_extrato
                        WHERE   fabrica = $login_fabrica
                        AND     posto = ".$resPostos->posto."
                        AND     tipo_gera_extrato = $gera_extrato_uf
                    ";
                }
                $resIns = pg_query($con,$sqlIns);

                if (pg_last_error($con)) {
                    pg_query($con,"ROLLBACK TRANSACTION");
                    $msg_erro['msg'][] = "Problemas ao gravar a informação";
                    break 2;
                }
            }
            pg_query($con,"COMMIT TRANSACTION");
            $msg[] = "Postos do estado de $uf gravados com sucesso.";
        }
    }
}

include 'cabecalho_new.php';
$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);
include("plugin_loader.php");



if (count($msg) > 0) {
?>
    <div class="alert alert-success">
        <h4><?=implode("<br />", $msg)?></h4>
    </div>
<?php
}
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

<form name='frm_intervalo_extrato' method='POST' action='<?=$PHP_SELF?>' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Informações para Cadastro</div>
    <br />

    <div class='row-fluid'>
        <div class="span2"></div>
        <div class="span4">
            <div class='control-group <?=(in_array("semana", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='semana'>Semana</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <select name="semana">
                            <option value="">SELECIONE</option>
                            <option value="1">1ª Segunda Feira</option>
                            <option value="2">2ª Segunda Feira</option>
                            <option value="3">3ª Segunda Feira</option>
                            <option value="4">4ª Segunda Feira</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group <?=(in_array("estados_regiao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='estados_regiao'>Região</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <select name="estados_regiao">
                            <option value="">SELECIONE</option>
                            <optgroup label="REGIÕES">
                                <option value="ES|MG|RJ">ES,MG,RJ</option>
                                <option value="SUL">Sul</option>
                                <option value="NE|NO|CO">Norte, Nordeste e Centro-Oeste</option>
                            </optgroup>
                            <optgroup label="ESTADOS">
<?php
foreach ($array_estados() as $uf=>$estado) {
?>
                                <option value="<?=$uf?>"><?=$estado?></option>
<?php
}
?>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
        <p>
        <br/>
        <button class="btn" id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
        <input type="hidden" id="btn_click" name="btn_acao" value="" />
        </p>
        <br/>
</form>

<br />

<table id="semana_estados" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class="titulo_coluna" >
            <th>Semana</th>
            <th>UF</th>
        </tr>
    </thead>
    <tbody>
<?php
$sql = "SELECT  tbl_estado.nome AS estado,
                CASE WHEN semana = 1
                     THEN '1ª Segunda Feira'
                     WHEN semana = 2
                     THEN '2ª Segunda Feira'
                     WHEN semana = 3
                     THEN '3ª Segunda Feira'
                     WHEN semana = 4
                     THEN '4ª Segunda Feira'
                END AS semana
        FROM    tbl_intervalo_extrato
        JOIN    tbl_estado USING(estado)
        WHERE   fabrica = $login_fabrica
        AND     tbl_intervalo_extrato.estado IS NOT NULL
  ORDER BY      semana,
                tbl_intervalo_extrato.estado
";
$res = pg_query($con,$sql);

while ($result = pg_fetch_object($res)) {
?>
        <tr>
            <td style="text-align:center;"><?=$result->semana?></td>
            <td style="text-align:center;"><?=$result->estado?></td>
        </tr>
<?php
}
?>
    </tbody>
</table>
<?php
include 'rodape.php';
?>
