<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = $_REQUEST['btn_acao'];
$os = $_REQUEST['os'];

if ($btn_acao == "gravar") {

    unset($_REQUEST['os'], $_REQUEST['btn_acao']);
    $qtde_pecas = $_REQUEST['qtde_pecas'];

    for ($i = 0; $i < $qtde_pecas; $i++) {
        $linha = $i + 1;
        if (array_key_exists('os_item_'.$i, $_REQUEST)) {
            if (!empty($_REQUEST['novo_custo_peca_'.$i])) {
                $up_os_item = $_REQUEST['os_item_'.$i];
                $up_custo_peca = $_REQUEST['novo_custo_peca_'.$i];
                $sql = "UPDATE tbl_os_item SET custo_peca = {$up_custo_peca} WHERE os_item = {$up_os_item} AND fabrica_i = {$login_fabrica};";
                $resUpCustoPeca = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro['msg'][] = "Ocorreu um erro na alteração do valor da peça da linha ".$linha.".";
                } else {
                    if (pg_affected_rows($resUpCustoPeca)) {
                        $houve_alteracao = true;
                    }
                }
            }
        }
    }

    if ($houve_alteracao) {
        $msg_sucesso = "Dados Alterados com sucesso";
    }

}

?>

<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<?
$plugins = array(
   "price_format"
);

include __DIR__."/plugin_loader.php";
?>
<script type="text/javascript">
    $(function () {

        $("input[name^=novo_custo_peca_]").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            ca: 2
        });

    });
</script>

<div style="overflow:scroll;height:100%;width:97%;padding-right:3%;">
    <? if (count($msg_erro['msg']) > 0) { ?>
        <div class="alert alert-error" style="margin:10px;">
            <h4><?= implode("<br />", $msg_erro['msg']); ?></h4>
        </div>
    <? } else if (isset($msg_sucesso)) { ?>
        <div class="alert alert-success" style="margin:10px;">
            <h4><?= $msg_sucesso; ?></h4>
        </div>
    <? }

    if (!empty($os)) {

        $sql = "SELECT
                        oi.os_item,
                        oi.qtde,
                        p.referencia as peca_referencia,
                        p.referencia||' - '||p.descricao as peca_descricao,
                        oi.custo_peca
                    FROM tbl_os_item oi
                    JOIN tbl_peca p USING(peca)
                    WHERE oi.fabrica_i = {$login_fabrica}
                    AND oi.os_produto IN (SELECT
                                                            os_produto
                                                        FROM tbl_os_produto
                                                        WHERE os = {$os});
                    ";

        $resPeca = pg_query($con,$sql);

        $count_k = pg_num_rows($resPeca);
        if ($count_k > 0) { ?>
            <h4 style="margin: 10px;">OS: <?=$os?></h4>
            <form name="frm_altera_custo_peca" method="POST" action="<?= $PHP_SELF?>" >
            <input type="hidden" name="os" value="<?=$os?>" />
            <input type="hidden" name="qtde_pecas" value="<?= $count_k; ?>">
                <table id="valor_peca" class='table table-striped table-bordered table-hover table-fixed' style="margin: 0 10px;">
                    <thead>
                        <tr class='titulo_coluna'>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Qtde</th>
                            <th>Valor Atual</th>
                            <th>Novo Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? for ($k = 0; $k < $count_k; $k++) {
                            $os_item                = pg_fetch_result($resPeca, $k, os_item);
                            $peca_referencia    = pg_fetch_result($resPeca, $k, peca_referencia);
                            $peca_descricao     = pg_fetch_result($resPeca, $k, peca_descricao);
                            $qtde                       = pg_fetch_result($resPeca, $k, qtde);
                            $custo_peca             = pg_fetch_result($resPeca, $k, custo_peca); ?>
                            <tr>
                                <input type="hidden" name="os_item_<?= $k; ?>" value="<?= $os_item; ?>" />
                                <td class="tac"><?= $peca_referencia; ?></td>
                                <td class="tac"><?= $peca_descricao; ?></td>
                                <td class="tac"><?= $qtde; ?></td>
                                <td class="tac"><?= $custo_peca; ?></td>
                                <td class="tac"><input type="text" name="novo_custo_peca_<?= $k; ?>" style="width:80px;" /></td>
                            </tr>
                        <? } ?>
                    </tbody>
                </table>
                <br />
                <div class="tac">
                    <input type="hidden" name="btn_acao" id="btn_acao" value="" />
                    <input type="button" class="btn btn-default" value="Gravar" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('gravar'); $(this).parents('form').submit(); } else { alert('Aguarde a gravação dos dados') }" />
                </div>
            </form>
        <? } else { ?>
            <div class="alert">
                <h4>Não foram encontradas peças para essa OS</h4>
            </div>
        <? }
    } else { ?>
        <div class="alert">
            <h4>Não foi contrado OS para edição do valor das peças</h4>
        </div>
    <? } ?>
</div>