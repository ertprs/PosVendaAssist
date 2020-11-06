<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$qry_os = null;
$bonificacao = null;

$posto = (int) $_GET["posto"];

if (!empty($posto) and !empty($_GET["mes"])) {
    $mes = $_GET["mes"];
    $csv = $_GET["csv"];

    if (array_key_exists("bonificacao", $_GET)) {
        $bonificacao = $_GET['bonificacao'];

        if ($bonificacao == 'todas') {
            $bonificacao = null;
        }
    }

    if (null !== $bonificacao and in_array($bonificacao, array(0, 1, 2))) {
        $bonificacao = (int) $bonificacao + 1;
    }

    $ano = date('Y');
    $cond_mes = " AND tbl_os.data_abertura
        BETWEEN '{$ano}-{$mes}-01 00:00:00'
        AND ('{$ano}-{$mes}-01 23:59:59'::timestamp + interval '1 month') - interval '1 day'";

    $sql_os = "SELECT tbl_os.mao_de_obra,
                    tbl_os.os,
                    TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY')  AS data_abertura,
                    TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY')  AS data_fechamento,
                    tbl_produto.referencia AS produto_referencia,
                    tbl_produto.descricao AS produto_descricao,
                    campos_adicionais
                FROM tbl_os
                JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                WHERE JSON_FIELD('bonificacao', campos_adicionais) <> ''
                AND tbl_os.posto = $posto
                $cond_mes";
    $qry_os = pg_query($con, $sql_os);

    if ($csv == "true") {
        $header = "OS;Data Abertura;Data Fechamento;Referência do Produto;Descrição do Produto\n";

        $content = $header;

        while ($fetch = pg_fetch_assoc($qry_os)) {
             if (!empty($bonificacao)) {
                $bonificacao_posto = json_decode($fetch["campos_adicionais"], true);

                if ($bonificacao_posto["bonificacao"]["bonificacao"] <> $bonificacao) {
                    continue;
                }
            }

            $content .= $fetch["os"] . ';';
            $content .= $fetch["data_abertura"] . ';';
            $content .= $fetch["data_fechamento"] . ';';
            $content .= $fetch["produto_referencia"] . ';';
            $content .= $fetch["produto_descricao"] . "\n";
        }

        if ($content <> $header) {
            $csv_name = 'xls/relatorio_bonificacao_posto_os_' . substr(sha1($login_admin), 0, 6) . date('Ymd') . '.csv';
            file_put_contents($csv_name, utf8_encode($content));

            die("$csv_name");
        } else {
            die('');
        }
    }
}

?>

<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>

		<script>
            function gera_csv(posto, mes, bonificacao) {
                var csv_html = $("#csv").html();
                $("#csv").html("<img src='imagens_admin/carregando_callcenter.gif' >Por favor, aguarde...");

                $.ajax({
                    type: 'GET',
                    url: 'relatorio_bonificacao_posto_os.php',
                    data: {
                        csv: true,
                        posto: posto,
                        mes: mes,
                        bonificacao: bonificacao
                    },
                }).done(function(data) {
                    $("#csv").html(csv_html);
                    location = data;
                });
            }

			$(function () {
				 $.dataTableLoad({ table: "#table_os_geral" });
			});
		</script>
	</head>

	<body>
        <div id="container_lupa" style="overflow-y:auto;">
            <div id="topo">
                <img class="espaco" src="imagens/logo_new_telecontrol.png">
            </div>
            <br /><hr />
            <div class="row-fluid">
                <table id="table_os_geral" class='table table-striped table-bordered table-hover table-fixed'>
                    <thead>
                        <tr class='titulo_coluna'>
                            <td>OS</td>
                            <td>Data Abertura</td>
                            <td>Data Fechamento</td>
                            <td>Referência do Produto</td>
                            <td>Descrição do Produto</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (is_resource($qry_os)) {
                            while ($fetch = pg_fetch_assoc($qry_os)) {
                                $os = $fetch["os"];
                                $data_abertura = $fetch["data_abertura"];
                                $data_fechamento = $fetch["data_fechamento"];
                                $produto_referencia = $fetch["produto_referencia"];
                                $produto_descricao = $fetch["produto_descricao"];

                                if (!empty($bonificacao)) {
                                    $bonificacao_posto = json_decode($fetch["campos_adicionais"], true);

                                    if ($bonificacao_posto["bonificacao"]["bonificacao"] <> $bonificacao) {
                                        continue;
                                    }
                                }

                                echo '<tr>';
                                $os_link = '<a href="os_press.php?os=' . $os . '" target="_blank">' . $os . '</a>';
                                echo '<td>' . $os_link . '</td>';
                                echo '<td>' . $data_abertura . '</td>';
                                echo '<td>' . $data_fechamento . '</td>';
                                echo '<td>' . $produto_referencia . '</td>';
                                echo '<td>' . $produto_descricao . '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div id="csv" style="text-align:center">
                <div class="btn_excel"  onClick="gera_csv('<?php echo $posto ?>', '<?php echo $mes ?>', '<?php echo (null === $bonificacao) ? 'todas' : $bonificacao - 1 ?>')">
                    <span><img src='imagens/excel.png' /></span>
                    <span class="txt">Gerar Arquivo Excel</span>
                </div>
            </div>
        </div>
    </body>
</html>
