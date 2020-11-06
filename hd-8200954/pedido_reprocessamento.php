<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
    include __DIR__.'/dbconfig.php';
    include __DIR__.'/includes/dbconnect-inc.php';
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/dbconfig.php';
    include __DIR__.'/includes/dbconnect-inc.php';
    include __DIR__.'/autentica_usuario.php';
}

include __DIR__.'/funcoes.php';

if ($_POST["ajax_aprova_pedido"] == true) {
    $pedido = $_POST["pedido"];

    if (empty($pedido)) {
        $retorno = array("erro" => utf8_encode("Pedido não informado"));
    } else {
        pg_query($con, "BEGIN");

        $sql = "UPDATE tbl_pedido SET status_pedido = 2, recebido_fabrica = CURRENT_DATE, exportado = CURRENT_DATE WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            $retorno = array("erro" => utf8_encode("Erro ao aprovar pedido"));
        } else {
            $sql = "UPDATE tbl_pedido_item SET preco = acrescimo_financeiro, total_item = acrescimo_tabela_base WHERE pedido = {$pedido}";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                $retorno = array("erro" => utf8_encode("Erro ao aprovar pedido"));
            } else {
                $sql = "SELECT SUM(total_item) AS total FROM tbl_pedido_item WHERE pedido = {$pedido}";
                $res = pg_query($con, $sql);

                $total = pg_fetch_result($res, 0, "total");

                $sql = "UPDATE tbl_pedido SET total = {$total} WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    $retorno = array("erro" => utf8_encode("Erro ao aprovar pedido"));
                } else {
                    $retorno = array("ok" => true);
                }
            }
        }

        if (isset($retorno["ok"])) {
            pg_query($con, "COMMIT");
        } else {
            pg_query($con, "ROLLBACK");
        }
    }

    exit(json_encode($retorno));
}

if ($_POST["ajax_cancela_pedido"] == true) {
    $pedido = $_POST["pedido"];

    if (empty($pedido)) {
        $retorno = array("erro" => utf8_encode("Pedido não informado"));
    } else {
        include_once __DIR__."/rotinas/wackerneuson/exporta-pedido-funcao.php";

        pg_query($con, "BEGIN");

        $sql = "SELECT seu_pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
        $res = pg_query($con, $sql);

        $pedido_wacker_neuson = pg_fetch_result($res, 0, "seu_pedido");

        $result = deletaPedidoWackerNeuson($pedido_wacker_neuson);

        if (empty($result->erroExecucao)) {
            $sql = "UPDATE tbl_pedido SET status_pedido = 14, seu_pedido = null WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                $retorno = array("erro" => utf8_encode("Erro ao cancelar pedido"));
            } else {
                $sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde WHERE pedido = {$pedido}";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    $retorno = array("erro" => utf8_encode("Erro ao cancelar pedido"));
                } else {
                    $retorno = array("ok" => true);
                }
            }
        } else {
            $retorno = array("erro" => utf8_encode("Erro ao cancelar pedido, por favor tente novamente dentro de alguns instantes"));
        }

        if (isset($retorno["ok"])) {
            pg_query($con, "COMMIT");
        } else {
            pg_query($con, "ROLLBACK");
        }
    }

    exit(json_encode($retorno));
}

if ($_POST["ajax_reprocessa_pedido"] == true) {
    $pedido = trim($_POST["pedido"]);

    if (empty($pedido)) {
        exit(json_encode(array("erro_webservice" => utf8_encode("Pedido não informado"))));
    } else {
        include_once __DIR__."/rotinas/wackerneuson/exporta-pedido-funcao.php";

        $result = exportaPedidoVendaWackerNeuson($pedido);

        if (!empty($result->erroExecucao)) {
            $retorno = array("erro_webservice" => utf8_encode(utf8_decode($result->erroExecucao)));
        } else if ($result->respostaPedido->retorno != "OK") {
            $retorno = array("erro" => utf8_encode(utf8_decode($result->respostaPedido->retorno)));
        } else {
            pg_query($con, "BEGIN");

            $pedido_wacker_neuson = $result->respostaPedido->gridPro[0]->numPed;

            if (empty($pedido_wacker_neuson)) {
                $retorno = array("erro_webservice" => true);
            } else {
                $sql = "UPDATE tbl_pedido SET seu_pedido = '{$pedido_wacker_neuson}' WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    deletaPedidoWackerNeuson($pedido_wacker_neuson);
                    $retorno = array("erro_webservice" => utf8_encode(utf8_decode($result->erroExecucao)));
                } else {
                    $itensPedido = consultaPedidoWackerNeuson($pedido_wacker_neuson);

                    if (!empty($itensPedido->erroExecucao)) {
                        deletaPedidoWackerNeuson($pedido_wacker_neuson);
                        $retorno = array("erro_webservice" => utf8_encode(utf8_decode($result->erroExecucao)));
                    } else {
                        $retorno = array("ok" => true, "itens" => array());

                        foreach ($itensPedido->retornos->dadosGerais->itens as $key => $item) {
                            $update = "UPDATE tbl_pedido_item SET 
                                            acrescimo_financeiro = {$item->preUni},
                                            acrescimo_tabela_base = {$item->vlrLiq}
                                       FROM tbl_peca
                                       WHERE tbl_pedido_item.pedido = {$pedido}
                                       AND tbl_pedido_item.peca = tbl_peca.peca
                                       AND tbl_peca.fabrica = {$login_fabrica}
                                       AND tbl_peca.referencia = '{$item->codPro}'";
                            $res = pg_query($con, $update);

                            if (strlen(pg_last_error()) > 0) {
                                deletaPedidoWackerNeuson($pedido_wacker_neuson);
                                $retorno = array("erro_webservice" => utf8_encode(utf8_decode($result->erroExecucao)));
                                break;
                            }

                            $retorno["itens"]["{$item->codPro}"] = array(
                                "preUni" => $item->preUni,
                                "vlrLiq" => $item->vlrLiq
                            );
                        }
                    }
                }
            }

            if (isset($retorno["erro"]) || isset($retorno["erro_webservice"])) {
                pg_query($con, "ROLLBACK");
            } else {
                pg_query($con, "COMMIT");
            }
        }

        exit(json_encode($retorno));
    }
}
?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>

<style>

#reprocessamento_concluido, #reprocessamento_erro, #cancelar_pedido, #aprovar_pedido {
    display: none;
}

td.novo_preco {
    font-weight: bold;
    font-size: 16px;
    color: #C09853;
}

</style>

<script>

$(function() {
    $("#aprovar_pedido").click(function() {
        $.ajax({
            url: "pedido_reprocessamento.php",
            type: "post",
            data: { ajax_aprova_pedido: true, pedido: <?=$pedido?> },
            beforeSend: function() {
                $("#aprovar_pedido").button("loading");
                $("#cancelar_pedido").hide();
            }
        }).always(function(data) {
            data = JSON.parse(data);

            if (data.erro) {
                alert(data.erro);

                $("#aprovar_pedido").button("reset");
                $("#cancelar_pedido").show();
            } else {
                <?php
                if ($areaAdmin === true) {
                ?>
                    window.location = "pedido_admin_consulta.php?pedido=<?=$pedido?>";
                <?php
                } else {
                ?>
                    window.location = "pedido_finalizado.php?pedido=<?=$pedido?>";
                <?php
                }
                ?>
            }
        });
    });

    $("#cancelar_pedido").click(function() {
        $.ajax({
            url: "pedido_reprocessamento.php",
            type: "post",
            data: { ajax_cancela_pedido: true, pedido: <?=$pedido?> },
            beforeSend: function() {
                $("#cancelar_pedido").button("loading");
                $("#aprovar_pedido").hide();
            }
        }).always(function(data) {
            data = JSON.parse(data);

            if (data.erro) {
                alert(data.erro);

                $("#cancelar_pedido").button("reset");
                $("#aprovar_pedido").show();
            } else {
                alert("Pedido cancelado");
                window.location = "cadastro_pedido.php";
            }
        });
    });
})

</script>

<?php
$pedido = $_GET["pedido"];

if (empty($pedido)) {
    header("Location: cadastro_pedido.php");
    exit;
} else {
    if ($areaAdmin === false) {
        $whereLoginPosto = "AND tbl_pedido.posto = {$login_posto}";
    }

    $sql = "SELECT
                tbl_pedido.pedido,
                tbl_pedido.pedido_cliente,
                tbl_pedido.seu_pedido AS pedido_fabricante,
                tbl_condicao.descricao AS condicao,
                tbl_tipo_pedido.descricao AS tipo_pedido,
                tbl_transportadora.cnpj AS transportadora_cnpj,
                tbl_transportadora.nome AS transportadora_nome,
                tbl_pedido.obs AS observacao,
                tbl_posto.nome AS posto_nome,
                tbl_posto_fabrica.codigo_posto AS posto_codigo,
                tbl_pedido.desconto
            FROM tbl_pedido
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao AND tbl_condicao.fabrica = {$login_fabrica}
            INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
            LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_pedido.transportadora
            LEFT JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora AND tbl_transportadora_fabrica.fabrica = {$login_fabrica}
            WHERE tbl_pedido.pedido = $pedido
            {$whereLoginPosto}
            AND tbl_pedido.fabrica = $login_fabrica
            AND tbl_pedido.status_pedido = 19";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $result = pg_fetch_all($res);
        extract($result[0]);

        if (empty($pedido_fabricante)) {
        ?>
            <script>
            $(function() {
                $.ajax({
                    url: "pedido_reprocessamento.php",
                    type: "post",
                    data: { ajax_reprocessa_pedido: true, pedido: <?=$pedido?> }
                }).always(function(data) {
                    data = JSON.parse(data);

                    if (typeof data.erro_webservice == "string") {
                        $("#reprocessamento_erro").html("<h5>Ocorreu um erro ao calcular o pedido, por favor tente novamente mais tarde.<br /> Caso queira voltar para a tela inicial <a href='menu_inicial.php' >clique aqui</a>, a proxima vez que você entrar na tela de pedido essa tela irá aparecer novamente</h5>").show();
                    } else if (data.erro) {
                        $("#reprocessamento_erro").html("<h5>Erro ao calcular o pedido: "+data.erro+"<br />Caso queira voltar para a tela inicial <a href='menu_inicial.php' >clique aqui</a>, a proxima vez que você entrar na tela de pedido essa tela irá aparecer novamente, ou se preferir vá para o final da tela para cancelar o pedido</h5>").show();
                        $("#cancelar_pedido").show();
                    } else {
                        var total = 0;

                        $.each(data.itens, function(key, item) {
                            $("#"+key).find("td.novo_preco_unitario").text(number_format(item.preUni, 2, ",", "."));
                            $("#"+key).find("td.novo_preco_total").text(number_format(item.vlrLiq, 2, ",", "."));

                            total += parseFloat(item.vlrLiq);
                        });

                        total = total.toFixed(2);

                        $("td.novo_total_pedido").text(number_format(total, 2, ",", "."));

                        $("#reprocessamento_concluido").show();
                        $("#cancelar_pedido, #aprovar_pedido").show();
                    }

                    $("#reprocessamento_carregando").hide();
                });
            })
            </script>
        <?php
        }

        $sqlItens = "SELECT
                        tbl_peca.referencia AS peca_referencia,
                        tbl_peca.descricao AS peca_descricao,
                        tbl_pedido_item.qtde AS qtde_pedida,
                        tbl_pedido_item.preco AS preco_unitario,
                        tbl_pedido_item.total_item AS preco_total,
                        tbl_pedido_item.acrescimo_financeiro AS novo_preco_unitario,
                        tbl_pedido_item.acrescimo_tabela_base AS novo_preco_total
                    FROM tbl_pedido_item
                    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                    WHERE tbl_pedido_item.pedido = {$pedido}";
        $resItens = pg_query($con, $sqlItens);
        ?>
        <div class="container">

        <br />

        <?php
        if (empty($pedido_fabricante)) {
        ?>
            <div id="reprocessamento_carregando" class="alert alert-warning" ><h5>O SEU PEDIDO ESTÁ SENDO CALCULADO POR FAVOR NÃO FECHE A JANELA ATÉ O TÉRMINO DA OPERAÇÃO</h5></div>
        <?php
        }
        ?>

        <div id="reprocessamento_concluido" class="alert alert-success" <?=(!empty($pedido_fabricante)) ? "style='display: block;'" : ""?> ><h5>PEDIDO CALCULADO POR FAVOR VÁ ATÉ O FINAL DA PÁGINA PARA APROVAR OU CANCELAR</h5></div>

        <div id="reprocessamento_erro" class="alert alert-danger" ><h5></h5></div>

        <table class="table table-bordered" style="table-layout: fixed; margin: 0 auto;" >
            <thead>
                <tr class="titulo_coluna">
                    <th colspan="6">Informações do Pedido: <?=$pedido?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th class="titulo_coluna" nowrap>Pedido do Cliente</th>
                    <td><?=$pedido_cliente?></td>
                    <th class="titulo_coluna" nowrap>Condição de Pagamento</th>
                    <td><?=$condicao?></td>
                    <th class="titulo_coluna" nowrap>Tipo de Pedido</th>
                    <td><?=$tipo_pedido?></td>
                </tr>
                <tr>
                    <th class="titulo_coluna" nowrap>Transportadora CNPJ</th>
                    <td><?=$transportadora_cnpj?></td>
                    <th class="titulo_coluna" nowrap>Transportadora Nome</th>
                    <td colspan="3"><?=$transportadora_nome?></td>
                </tr>
                <tr>
                    <th class="titulo_coluna">Observação</th>
                    <td colspan="5"><?=$observacao?></td>
                </tr>
            </tbody>
        </table>

        <table class="table table-bordered" >
            <thead>
                <tr class="titulo_coluna" >
                    <th colspan="6">Itens do Pedido</th>
                </tr>
                <tr class="titulo_coluna">
                    <th>Item</th>
                    <th>Qtde</th>
                    <th>Preço Unitário</th>
                    <th>Preço Total</th>
                    <th>Novo Preço Unitário</th>
                    <th>Novo Preço Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_pedido = 0;

                if (!empty($pedido_fabricante)) {
                    $novo_total_pedido = 0;
                }

                while ($item = pg_fetch_object($resItens)) {
                    $total_pedido += $item->preco_total;

                    if (!empty($pedido_fabricante)) {
                        $novo_total_pedido += $item->novo_preco_total;
                    }
                    ?>

                    <tr id="<?=$item->peca_referencia?>" >
                        <td><?=$item->peca_referencia?> - <?=$item->peca_descricao?></td>
                        <td class='tar'><?=$item->qtde_pedida?></td>
                        <td class='tar'><?=number_format($item->preco_unitario, 2, ",", ".")?></td>
                        <td class='tar'><?=number_format($item->preco_total, 2, ",", ".")?></td>

                        <?php
                        if (!empty($pedido_fabricante)) {
                        ?>
                            <td class='tar novo_preco novo_preco_unitario'><?=$item->novo_preco_unitario?></td>
                            <td class='tar novo_preco novo_preco_total'><?=$item->novo_preco_total?></td>
                        <?php
                        } else {
                        ?>
                            <td class='tar novo_preco novo_preco_unitario'></td>
                            <td class='tar novo_preco novo_preco_total'></td>
                        <?php
                        }
                        ?>
                    </tr>
                <?php
                }

                if (!empty($desconto)) {
                    $total_pedido -= ($total_pedido / 100) * $desconto;

                    if (!empty($pedido_fabricante)) {
                        $novo_total_pedido -= ($novo_total_pedido / 100) * $desconto;
                    }
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th class="titulo_coluna tar" colspan="3">Desconto</th>
                    <td class="tar"><?=number_format($desconto, 2, ",", ".")?>%</td>
                </tr>
                <tr>
                    <th class="titulo_coluna tar" colspan="3">Total do Pedido</th>
                    <td class="tar"><?=number_format($total_pedido, 2, ",", ".")?></td>
                    <th class="titulo_coluna tar">Novo Total do Pedido</th>
                    <?php
                    if (!empty($pedido_fabricante)) {
                    ?>
                        <td class="tar novo_preco novo_total_pedido"><?=number_format($novo_total_pedido, 2, ",", ".")?></td>
                    <?php
                    } else {
                    ?>
                        <td class="tar novo_preco novo_total_pedido"></td>
                    <?php
                    }
                    ?>
                </tr>
            </tfoot>
        </table>

        <p class="tac">
            <button type="button" id="aprovar_pedido" class="btn btn-success" data-loading-text="Aprovando Pedido..." <?=(!empty($pedido_fabricante)) ? "style='display: inline;'" : ""?> >Aprovar Pedido</button>
            <button type="button" id="cancelar_pedido" class="btn btn-danger" data-loading-text="Cancelando Pedido..." <?=(!empty($pedido_fabricante)) ? "style='display: inline;'" : ""?> >Cancelar Pedido</button>
        </p>

        </div>
    <?php
    } else {
        header("Location: cadastro_pedido.php");
        exit;
    }
}
?>

