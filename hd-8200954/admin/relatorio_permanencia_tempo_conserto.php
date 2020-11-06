<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Relatório Permanência em conserto";
$layout_menu = "gerencia";

include "cabecalho_new.php";

if ($_POST) {
    $ano              = $_POST["ano"];
    $mes              = $_POST["mes"];
    $cliente_admin    = $_POST["cliente_admin"];
    $posto            = $_POST["posto"];
    $tipo_atendimento = $_POST["tipo_atendimento"];

    if (empty($ano)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "ano";
    }

    if (empty($msg_erro["msg"])) {
        if (!empty($mes)) {
            $whereMes = "AND DATE_PART('MONTH', tbl_os.data_abertura) = {$mes}";
        }

        if (!empty($cliente_admin)) {
            $whereClienteAdmin = "AND tbl_cliente_admin.cliente_admin = {$cliente_admin}";
        }

        if (!empty($posto)) {
            $wherePosto = "AND tbl_posto_fabrica.posto = {$posto}";
        }

        if (!empty($tipo_atendimento)) {
            $whereTipoAtendimento = "AND tbl_os.tipo_atendimento = {$tipo_atendimento}";
        }

        $sql = "
            SELECT
                tbl_posto.nome AS posto_nome,
                tbl_cliente_admin.nome AS cliente_admin_nome,
                (
                    CASE WHEN CURRENT_DATE - tbl_os.data_abertura >= 3 THEN
                        '3 dias ou mais'
                    WHEN CURRENT_DATE - tbl_os.data_abertura = 2 THEN
                        '2 dias'
                    ELSE 
                        '1 dia'
                    END
                ) AS tempo_conserto,
                COUNT(tbl_os.os) AS qtde_os
            FROM tbl_os
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
            INNER JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}
            INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica} AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND DATE_PART('YEAR', tbl_os.data_abertura) = {$ano}
            AND tbl_os.finalizada IS NULL
            AND (tbl_tipo_atendimento.grupo_atendimento != 'P' OR tbl_tipo_atendimento.grupo_atendimento IS NULL)
            {$whereMes}
            {$whereClienteAdmin}
            {$wherePosto}
            {$whereTipoAtendimento}
            GROUP BY posto_nome, cliente_admin_nome, tempo_conserto
        ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][] = "Nenhum resultado encontrado";
        } else {
            $resultado = array();

            array_map(function($row) {
                global $resultado;

                $resultado["posto"][$row["posto_nome"]][$row["tempo_conserto"]] += $row["qtde_os"];
                $resultado["cliente_admin"][$row["cliente_admin_nome"]][$row["tempo_conserto"]] += $row["qtde_os"];
            }, pg_fetch_all($res));
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

<div class="row" >
    <b class="obrigatorio pull-right" >* Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>

    <br />

    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span2" >
            <div class="control-group" >
                <label class="control-label" for="ano" >Ano</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class="asteristico" >*</h5>
                        <select id="ano" name="ano" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "SELECT DATE_PART('YEAR', MIN(data_abertura)) FROM tbl_os WHERE fabrica = {$login_fabrica}";
                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                                $anoIni = pg_fetch_result($res, 0, 0);
                            } 
                            
			    $anoFim = date("Y");
                            
                            for ($i = $anoIni; $i <= $anoFim; $i++) { 
                                $selected = ($i == getValue("ano")) ? "selected" : "";

                                echo "<option value='{$i}' {$selected} >{$i}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2" >
            <div class="control-group" >
                <label class="control-label" for="mes" >Mês</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select id="mes" name="mes" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            foreach ($meses_idioma["pt-br"] as $mes_numero => $mes_nome) {
                                $selected = ($mes_numero == getValue("mes")) ? "selected" : "";

                                echo "<option value='{$mes_numero}' {$selected} >{$mes_nome}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="cliente_admin" >Cliente Admin</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select id="cliente_admin" name="cliente_admin" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "
                                SELECT cliente_admin, nome
                                FROM tbl_cliente_admin
                                WHERE fabrica = {$login_fabrica}
                                ORDER BY nome
                            ";
                            $res = pg_query($con, $sql);

                            while ($row = pg_fetch_object($res)) {
                                $selected = ($row->cliente_admin == getValue("cliente_admin")) ? "selected" : "";

                                echo "<option value='{$row->cliente_admin}' {$selected} >{$row->nome}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span6" >
            <div class="control-group" >
                <label class="control-label" for="posto" >Posto Autorizado</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select id="posto" name="posto" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "
                                SELECT tbl_posto.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                                FROM tbl_posto_fabrica
                                INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                                ORDER BY tbl_posto.nome
                            ";
                            $res = pg_query($con, $sql);

                            while ($row = pg_fetch_object($res)) {
                                $selected = ($row->posto == getValue("posto")) ? "selected" : "";

                                echo "<option value='{$row->posto}' {$selected} >{$row->codigo_posto} - {$row->nome}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span3" >
            <div class="control-group" >
                <label class="control-label" for="tipo_atendimento" >Tipo de Atendimento</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select id="tipo_atendimento" name="tipo_atendimento" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "
                                SELECT tipo_atendimento, descricao
                                FROM tbl_tipo_atendimento
                                WHERE fabrica = {$login_fabrica}
                                AND (grupo_atendimento != 'P' OR grupo_atendimento IS NULL)
                                ORDER BY descricao
                            ";
                            $res = pg_query($con, $sql);

                            while ($row = pg_fetch_object($res)) {
                                $selected = ($row->tipo_atendimento == getValue("tipo_atendimento")) ? "selected" : "";

                                echo "<option value='{$row->tipo_atendimento}' {$selected} >{$row->descricao}</option>";
                            }
                            ?>
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

</div>

<?php
if (count($resultado) > 0) {
    ob_start();
    ?>
    <br />
    <table class="table table-bordered relatorio" >
        <caption class="titulo_tabela" >Tempo em conserto por Cliente Admin</caption>
        <thead>
            <tr class="titulo_coluna" >
                <th>Cliente Admin</th>
                <th>1 dia</th>
                <th>% 1 dia</th>
                <th>2 dias</th>
                <th>% 2 dias</th>
                <th>3 dias ou mais</th>
                <th>% 3 dias ou mais</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_geral           = 0;
            $total_geral_um_dia    = 0;
            $total_geral_dois_dias = 0;
            $total_geral_tres_dias = 0;

            foreach ($resultado["cliente_admin"] as $cliente_admin => $value) {
                $um_dia    = (int) $value["1 dia"];
                $dois_dias = (int) $value["2 dias"];
                $tres_dias = (int) $value["3 dias ou mais"];

                $total = $um_dia + $dois_dias + $tres_dias;

                $total_geral_um_dia    += $um_dia;
                $total_geral_dois_dias += $dois_dias;
                $total_geral_tres_dias += $tres_dias;
                $total_geral           += $total;
                ?>
                <tr>
                    <td nowrap ><?=$cliente_admin?></td>
                    <td class="tac" ><?=$um_dia?></td>
                    <td class="tac" ><?=(int) (100 / ($total / $um_dia))?>%</td>
                    <td class="tac" ><?=$dois_dias?></td>
                    <td class="tac" ><?=(int) (100 / ($total / $dois_dias))?>%</td>
                    <td class="tac" ><?=$tres_dias?></td>
                    <td class="tac" ><?=(int) (100 / ($total / $tres_dias))?>%</td>
                    <td class="tac" ><?=$total?></td>
                </tr>
            <?php
            }
            ?>
            <tr class="titulo_coluna" >
                <th>Total Geral</th>
                <th><?=$total_geral_um_dia?></th>
                <th><?=(int) (100 / ($total_geral / $total_geral_um_dia))?>%</th>
                <th><?=$total_geral_dois_dias?></th>
                <th><?=(int) (100 / ($total_geral / $total_geral_dois_dias))?>%</th>
                <th><?=$total_geral_tres_dias?></th>
                <th><?=(int) (100 / ($total_geral / $total_geral_tres_dias))?>%</th>
                <th><?=$total_geral?></th>
            </tr>
        </tbody>
    </table>
    
    <br />
    <table class="table table-bordered relatorio" >
        <caption class="titulo_tabela" >Tempo em conserto por Posto Autorizado</caption>
        <thead>
            <tr class="titulo_coluna" >
                <th>Posto Autorizado</th>
                <th>1 dia</th>
                <th>% 1 dia</th>
                <th>2 dias</th>
                <th>% 2 dias</th>
                <th>3 dias ou mais</th>
                <th>% 3 dias ou mais</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_geral           = 0;
            $total_geral_um_dia    = 0;
            $total_geral_dois_dias = 0;
            $total_geral_tres_dias = 0;

            foreach ($resultado["posto"] as $posto_nome => $value) {
                $um_dia    = (int) $value["1 dia"];
                $dois_dias = (int) $value["2 dias"];
                $tres_dias = (int) $value["3 dias ou mais"];

                $total = $um_dia + $dois_dias + $tres_dias;

                $total_geral_um_dia    += $um_dia;
                $total_geral_dois_dias += $dois_dias;
                $total_geral_tres_dias += $tres_dias;
                $total_geral           += $total;
                ?>
                <tr>
                    <td nowrap ><?=$posto_nome?></td>
                    <td class="tac" ><?=$um_dia?></td>
                    <td class="tac" ><?=(int) (100 / ($total / $um_dia))?>%</td>
                    <td class="tac" ><?=$dois_dias?></td>
                    <td class="tac" ><?=(int) (100 / ($total / $dois_dias))?>%</td>
                    <td class="tac" ><?=$tres_dias?></td>
                    <td class="tac" ><?=(int) (100 / ($total / $tres_dias))?>%</td>
                    <td class="tac" ><?=$total?></td>
                </tr>
            <?php
            }
            ?>
            <tr class="titulo_coluna" >
                <th>Total Geral</th>
                <th><?=$total_geral_um_dia?></th>
                <th><?=(int) (100 / ($total_geral / $total_geral_um_dia))?>%</th>
                <th><?=$total_geral_dois_dias?></th>
                <th><?=(int) (100 / ($total_geral / $total_geral_dois_dias))?>%</th>
                <th><?=$total_geral_tres_dias?></th>
                <th><?=(int) (100 / ($total_geral / $total_geral_tres_dias))?>%</th>
                <th><?=$total_geral?></th>
            </tr>
        </tbody>
    </table>
    <?php
    $html = ob_get_contents();

    ob_end_flush();
    ob_clean();

    $xls  = "relatorio-permanencia-tempo-conserto-{$login_fabrica}-{$login_admin}-".date("YmdHi").".xls";
    $file = fopen("/tmp/".$xls, "w");
    fwrite($file, $html);
    fclose($file);
    system("mv /tmp/{$xls} xls/{$xls}");
    ?>

    <br />

    <p class="tac" >
        <button type="button" class="btn btn-success download-xls" data-xls="<?=$xls?>" ><i class="icon-download-alt icon-white" ></i> Download XLS</button>
    </p>

<?php
}

$plugins = array(
    "select2"
);

include "plugin_loader.php";
?>

<style>

table.relatorio {
    width: 1200px;
    margin: 0 auto;
}

</style>

<script>

$("select").select2();

$("button.download-xls").on("click", function() {
    var xls = $(this).data("xls");

    window.open("xls/"+xls);
});

</script>

<br />

<?php 
include "rodape.php"; 
?>
