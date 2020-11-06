<?php
/**
 * 2018.07.13
 * @author  Lucas Bicalleto
 * @version 1.0
*/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST['ajax_finaliza'])) {

    $treinamento = $_POST['treinamento'];

    if (!empty($treinamento)) {
        pg_query($con, "UPDATE tbl_treinamento SET
                               data_finalizado = CURRENT_TIMESTAMP
                        WHERE  treinamento     = {$treinamento}
                        AND fabrica            = {$login_fabrica}");
        exit(json_encode(array("ok" => "Treinamento Finalizado com sucesso")));
    } else {
        exit(json_encode(array("erro" => "ERRO!")));
    }

}

$layout_menu = "info_tecnica";
$title = "Avaliação dos Técnicos";
include 'cabecalho_new.php';

/************************ LISTANDO OS TREINAMENTOS  PRESENCIAIS ************************/
$sql_presencial =   "SELECT DISTINCT 
                        tbl_treinamento.treinamento,
                        tbl_treinamento.titulo,
                        tbl_treinamento_tipo.nome AS treinamento_tipo,
                        tbl_treinamento.ativo,
                        tbl_treinamento.linha,
                        TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
                        TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
                        ARRAY_TO_STRING(array_agg(DISTINCT(tbl_linha.nome)), ', ', null) AS linhas,
                        ARRAY_TO_STRING(array_agg(DISTINCT(tbl_produto.descricao)), ', ', null) AS produtos,
                        (
                            SELECT COUNT(*)
                            FROM tbl_treinamento_posto
                            WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                            AND   tbl_treinamento_posto.ativo IS TRUE
                        )                                                     AS inscritos
                    FROM    tbl_treinamento
                        LEFT JOIN tbl_treinamento_produto ON tbl_treinamento_produto.treinamento = tbl_treinamento.treinamento
                        LEFT JOIN tbl_linha   ON tbl_linha.linha     = tbl_treinamento_produto.linha
                        LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_treinamento_produto.produto
                        INNER JOIN tbl_treinamento_tipo ON tbl_treinamento_tipo.treinamento_tipo = tbl_treinamento.treinamento_tipo
                    WHERE   tbl_treinamento_tipo.nome = 'Presencial'
                        AND tbl_treinamento.ativo IS TRUE
                        AND tbl_treinamento.data_finalizado IS NULL
                        AND tbl_treinamento.fabrica = {$login_fabrica}
                    GROUP BY tbl_treinamento.treinamento,
                             tbl_treinamento_tipo.nome,
                             tbl_treinamento.ativo,
                             tbl_treinamento.linha";
$res_presencial       = pg_query($con,$sql_presencial);
$msg_erro_presencial  = pg_last_error($con);


/************************ LISTANDO OS TREINAMENTOS ONLINE ************************/
$sql_online     =   "SELECT DISTINCT 
                        tbl_treinamento.treinamento,
                        tbl_treinamento.titulo,
                        tbl_treinamento_tipo.nome AS treinamento_tipo,
                        tbl_treinamento.ativo,
                        tbl_treinamento.linha,
                        ARRAY_TO_STRING(array_agg(DISTINCT(tbl_linha.nome)), ', ', null) AS linhas,
                        ARRAY_TO_STRING(array_agg(DISTINCT(tbl_produto.descricao)), ', ', null) AS produtos,
                        (
                            SELECT COUNT(*)
                            FROM tbl_treinamento_posto
                            WHERE tbl_treinamento_posto.participou IS TRUE
                            AND   tbl_treinamento_posto.data_avaliacao IS NULL
                            AND   tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                        )                                                     AS avaliacoes
                    FROM    tbl_treinamento
                        LEFT JOIN tbl_treinamento_produto ON tbl_treinamento_produto.treinamento = tbl_treinamento.treinamento
                        LEFT JOIN tbl_linha               ON tbl_linha.linha                     = tbl_treinamento_produto.linha
                        LEFT JOIN tbl_produto             ON tbl_produto.produto                 = tbl_treinamento_produto.produto
                        LEFT JOIN tbl_treinamento_posto   ON tbl_treinamento_posto.treinamento   = tbl_treinamento.treinamento
                        INNER JOIN tbl_treinamento_tipo ON tbl_treinamento_tipo.treinamento_tipo = tbl_treinamento.treinamento_tipo
                    WHERE   tbl_treinamento_tipo.nome = 'Online'
                        AND tbl_treinamento.ativo IS TRUE
                        AND tbl_treinamento.fabrica = {$login_fabrica}
                        AND tbl_treinamento.data_finalizado IS NULL
                    GROUP BY tbl_treinamento.treinamento,
                             tbl_treinamento_tipo.nome,
                             tbl_treinamento.ativo,
                             tbl_treinamento.linha";
$res_online      = pg_query($con,$sql_online);
$msg_erro_online = pg_last_error($con);

$plugins = array(
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);
include("plugin_loader.php");
?>

<script type="text/javascript">
    $(function() {
        Shadowbox.init();
        $(document).on('click', 'a.detalhes_treinamento', function(){
            if (!$(this).hasClass("disabled")) {
                var url = $(this).data('url');
                Shadowbox.open({
                    content: url,
                    player: 'iframe',
                    width: 1224,
                    height: 600
                });
            }
        });

        $(".finalizar-treinamento").click(function(){

            let treinamento = $(this).data("treinamento");

             $.ajax({
                async: true,
                type: 'POST',
                dataType:"JSON",
                url: window.location.href,
                data: {
                    ajax_finaliza:true,
                    treinamento:treinamento
                },
            }).done(function(data) {
                if (data.ok !== undefined) {
                    if (!alert(data.ok)){
                        window.location.reload();
                    }
                }else{
                    if (!alert(data.erro)){
                        window.location.reload();
                    }
                }
            });

        });

        var tabela       = $("#tblTreinamento");
        var tabelaOnline = $("#tblTreinamentoOnline");

        $.dataTableLoad({ table: tabela});
    });    
</script>

<!-- -->
<div class='row-fluid'>
    <div class='span12'>
        <table id='tblTreinamento' class='table table-striped table-bordered table-fixed'>
            <thead>
                <tr class='titulo_tabela'>
                    <th colspan='7'>Treinamentos Presenciais</th>
                </tr>
                <tr class='titulo_coluna'>
                    <th>Titulo</th>
                    <th class='date_column'>Data In&iacute;cio</th>
                    <th class='date_column'>Data Fim</th>
                    <?php if (!in_array($login_fabrica, [175])) { ?>
                        <th>Linhas</th>
                    <?php } ?>
                    <th>Produtos</th>
                    <th>Inscritos</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    if (!strlen($msg_erro_presencial) > 0){
                        for ($i=0; $i<pg_num_rows($res_presencial); $i++)
                        {
                            $treinamento = pg_fetch_result($res_presencial,$i,'treinamento');
                            $titulo      = pg_fetch_result($res_presencial,$i,'titulo');
                            $data_inicio = pg_fetch_result($res_presencial,$i,'data_inicio');
                            $data_fim    = pg_fetch_result($res_presencial,$i,'data_fim');
                            $produtos    = pg_fetch_result($res_presencial,$i,'produtos');
                            $inscritos   = pg_fetch_result($res_presencial,$i,'inscritos');

                            if (!in_array($login_fabrica, [175])) {
                                $linhas  = pg_fetch_result($res_presencial,$i,'linhas');
                            }

                            echo "<TR>";
                                echo  "<td>".$titulo."</td>";
                                echo "<td>".$data_inicio."</td>";
                                echo "<td>".$data_fim."</td>";
                                if (!in_array($login_fabrica, [175])) {
                                    echo "<td>".$linhas."</td>";
                                }
                                echo "<td>".$produtos."</td>";
                                echo "<td class='tac'>".$inscritos."</td>";
                                echo "<td class='tac'><a class='btn btn-success btn-small detalhes_treinamento' data-url='detalhes_treinamento_avaliacao.php?treinamento=$treinamento' style='cursor: pointer;'>Avaliar/Finalizar</a></td>";
                            echo "</TR>";
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>
</div> <br />
    
<div class='row-fluid'>
    <div class='span12'>
        <table id='tblTreinamentoOnline' class='table table-striped table-bordered table-fixed'>
            <thead>
                <tr class='titulo_tabela'>
                    <th colspan='7'>Treinamentos Online</th>
                </tr>
                <tr class='titulo_coluna'>
                    <th>Titulo</th>
                    <?php if (!in_array($login_fabrica, [175])) { ?>
                        <th>Linhas</th>
                    <?php } ?>
                    <th>Produtos</th>
                    <th>Avaliações Pendentes</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    if (!strlen($msg_erro_online) > 0){
                        for ($i2=0; $i2<pg_num_rows($res_online); $i2++)
                        {
                            $treinamento = pg_fetch_result($res_online,$i2,'treinamento');
                            $titulo      = pg_fetch_result($res_online,$i2,'titulo');
                            $produtos    = pg_fetch_result($res_online,$i2,'produtos');
                            $avaliacoes  = pg_fetch_result($res_online,$i2,'avaliacoes');

                            if (!in_array($login_fabrica, [175])) {
                                $linhas  = pg_fetch_result($res_online,$i2,'linhas');
                            }

                            ?>

                            <TR>
                                <td><?= $titulo ?></td>
                                <?php
                                if (!in_array($login_fabrica, [175])) { ?>
                                    <td><?= $linhas ?></td>
                                <?php
                                } ?>
                                <td><?= $produtos ?></td>
                                <td class="tac"><?= $avaliacoes ?></td>
                                <td class="tac">
                                    <a <?= ($avaliacoes == 0) ? "disabled" : "" ?> class='btn btn-primary btn-small detalhes_treinamento <?= ($avaliacoes == 0) ? "disabled" : "" ?>' data-url='detalhes_treinamento_avaliacao.php?treinamento=<?= $treinamento ?>&avaliar_finalizar=avaliar' style='cursor: pointer;width: 105px;margin-bottom: 3px;'>Avaliar Técnicos</a>
                                    <a class='btn btn-success btn-small finalizar-treinamento' data-treinamento="<?= $treinamento ?>" style='cursor: pointer;width: 105px;'>Finalizar</a>
                                </td>
                            </TR>
                        <?php
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include 'rodape.php';
?>