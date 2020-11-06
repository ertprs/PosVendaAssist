<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$ajax_explode = $_REQUEST['ajax_explode'];

if ($ajax_explode) {
    $ex_posto = $_REQUEST['posto'];
    $ex_linha = $_REQUEST['linha'];
    $ex_tipo = $_REQUEST['tipo'];
    $ex_data = $_REQUEST['data'];
    $ex_data = explode("/", $ex_data);
    $ex_mes = $ex_data[0];
    $ex_ano = $ex_data[1];

    if (strtoupper($tipo) == "TOT") {
        $whereExPeriodo = "AND tbl_extrato.aprovado BETWEEN TO_DATE('{$ex_ano}-{$ex_mes}-01', 'YYYY-MM-DD') - interval '2 months'
                AND TO_DATE('{$ex_ano}-{$ex_mes}-01', 'YYYY-MM-DD') + interval '1 month - 1 day'";
    } else {
        $whereExPeriodo = "AND tbl_extrato.aprovado BETWEEN TO_DATE('{$ex_ano}-{$ex_mes}-01', 'YYYY-MM-DD')
                AND TO_DATE('{$ex_ano}-{$ex_mes}-01', 'YYYY-MM-DD') + interval '1 month - 1 day'";
    }

    $sqlVistaExplodida = "SELECT DISTINCT(tbl_os.os),
                    tbl_os.sua_os,
                    TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
                    TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
                    tbl_produto.referencia||' - '||tbl_produto.descricao AS produto
                FROM tbl_os
                JOIN tbl_os_produto USING(os)
                JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
                JOIN (SELECT DISTINCT os
                                FROM tbl_os_extra
                                JOIN tbl_extrato USING(extrato)
                                WHERE tbl_extrato.fabrica = $login_fabrica
                                AND tbl_extrato.posto = $ex_posto
                                $whereExPeriodo) oe ON oe.os = tbl_os.os
                WHERE tbl_os.fabrica = $login_fabrica
                AND tbl_produto.linha = $ex_linha
                ORDER BY tbl_os.os;";

    $resVistaExplodida = pg_query($con, $sqlVistaExplodida);

    $vistaExplodida = pg_fetch_all($resVistaExplodida);
    $ex_count = pg_num_rows($resVistaExplodida);

    $sqlExPosto = "SELECT codigo_posto||' - '||nome FROM tbl_posto INNER JOIN tbl_posto_fabrica USING(posto) WHERE fabrica = $login_fabrica AND posto = $ex_posto;";
    $resExPosto = pg_query($con, $sqlExPosto);
    $descPosto = pg_fetch_result($resExPosto, 0, 0);

    $sqlExLinha = "SELECT nome FROM tbl_linha WHERE linha = $ex_linha;";
    $resExLinha = pg_query($con, $sqlExLinha);
    $descLinha = pg_fetch_result($resExLinha, 0, 0);

    if ($_REQUEST['gerar_excel'] && $ex_count > 0) {

        $data = date("d-m-Y-H:i");

        $arquivo_nome       = "relatorio-qtde-os-por-posto-periodo-$data.xls";
        $path                       = "xls/";
        $path_tmp           = "/tmp/";

        $arquivo_completo       = $path.$arquivo_nome;
        $arquivo_completo_tmp   = $path_tmp.$arquivo_nome;

        $fp = fopen($arquivo_completo_tmp,"w");

        $table = "<table border='1'>";
        $table .= "<thead>";
        $table .= "<tr>";
        $table .= "<th>Linha: ".$descLinha."</th>";
        $table .= "<th colspan='3'>Posto: ".$descPosto."</th>";
        $table .= "</tr>";
        $table .= "<tr>";
        $table .= "<th>OS</th>";
        $table .= "<th>Abertura</th>";
        $table .= "<th>Fechamento</th>";
        $table .= "<th>Produto</th>";
        $table .= "</tr>";
        $table .= "</thead>";
        $table .= "<tbody>";

        foreach ($vistaExplodida as $ex_os) {
            $table .= "<tr>";
            $table .= "<td>".$ex_os['sua_os']."</td>";
            $table .= "<td>".$ex_os['data_digitacao']."</td>";
            $table .= "<td>".$ex_os['data_fechamento']."</td>";
            $table .= "<td>".$ex_os['produto']."</td>";
            $table .= "</tr>";
        }

        $table .= "</tbody>";
        $table .= "</table>";

        fwrite($fp, $table);

        fclose($fp);

        if (file_exists($arquivo_completo_tmp)) {
            system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
            echo $arquivo_completo;
        }

        exit;
    }
}
?>
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script>
    $(function () {
        $("#gerar_excel").click(function () {
            if (window.parent.ajaxAction()) {
                var json = $.parseJSON($("#jsonPOST").val());
                json["gerar_excel"] = true;

                $.ajax({
                    url: "<?= $_SERVER['PHP_SELF']; ?>",
                    type: "POST",
                    data: json,
                    beforeSend: function() {
                        window.parent.loading("show");
                    },
                    complete: function(data) {
                        window.open(data.responseText, "_blank");
                        window.parent.loading("hide");
                    }
                });
            }
        });
    });
</script>
<? if ($ex_count > 0) { ?>
    <div style="overflow-y:scroll;height:570px;">
        <table id='resultado_vista_explodida' class='table table-striped table-bordered table-hover table-large'>
            <thead>
                <tr class="titulo_coluna">
                    <th class="tac">Linha: <?= $descLinha; ?></th>
                    <th colspan="3" class="tac">Posto: <?= $descPosto; ?></th>
                </tr>
                <tr class="titulo_coluna">
                    <th class="tac">OS</th>
                    <th class="tal">Abertura</th>
                    <th class="tal">Fechamento</th>
                    <th class="tac">Produto</th>
                </tr>
            </thead>
            <tbody>
                <? foreach ($vistaExplodida as $ex_os) { ?>
                    <tr>
                        <td class="tac"><?= $ex_os['sua_os']; ?></td>
                        <td class="tal"><?= $ex_os['data_digitacao']; ?></td>
                        <td class="tal"><?= $ex_os['data_fechamento']; ?></td>
                        <td class="tac"><?= $ex_os['produto']; ?></td>
                    </tr>
                <? } ?>
            </tbody>
        </table>
        <? $jsonPOST = excelPostToJson($_REQUEST); ?>
        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?= $jsonPOST; ?>' />
            <span><img src="imagens/excel.png" /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>
    </div>
<? } ?>