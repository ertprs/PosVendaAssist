<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$layout_menu = 'pedido';
$title       = 'Pedidos aguardando aprovação';

include 'cabecalho_new.php';

$plugins = array(
    'dataTable',
    'font_awesome'
);
include 'plugin_loader.php';
?>

<?php
$sql = "
    SELECT p.pedido, p.data, p.total, COUNT(pi.pedido_item) AS qtde_item
    FROM tbl_pedido p
    INNER JOIN tbl_pedido_item pi ON pi.pedido = p.pedido
    WHERE p.fabrica = {$login_fabrica}
    AND p.posto = {$login_posto}
    AND p.status_pedido = 18
    GROUP BY p.pedido, p.data, p.total
";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
?>
    <table id='resultado-pedidos-aguardando-aprovacao' class='table table-striped table-bordered table-hover' style='width: 100%;' >
        <thead>
            <tr class='titulo_coluna' >
                <th colspan='5' >Pedidos aguardando aprovação</th>
            </tr>
            <tr class='titulo_coluna' >
                <th>Pedido</th>
                <th>Data</th>
                <th>Quantidade de Itens</th>
                <th>Total</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = pg_fetch_object($res)) {
            ?>
                <tr>
                    <td class='tac'><?=$row->pedido?></td>
                    <td class='tac'><?=date('d/m/Y', strtotime($row->data))?></td>
                    <td class='tac'><?=$row->qtde_item?></td>
                    <td class='tac'><?=number_format($row->total, 2, ',', '.')?></td>
                    <td class='tac'>
                        <button type="button" class="btn btn-info btn-small btn-visualizar" data-pedido="<?=$row->pedido?>"><i class='fa fa-eye'></i> Visualizar</button>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php
} else {
?>
    
    <div class="alert alert-warning">
        <strong>Não há pedidos aguardando aprovação!</strong>
    </div>
    
<?php
}
?>

<script>
    
$.dataTableLoad({
    table: '#resultado-pedidos-aguardando-aprovacao',
    type: 'custom',
    config: ['pesquisa', 'info'],
    aoColumns: [
        { sType: 'numeric' },
        { sType: 'date' },
        { sType: 'numeric' },
        { sType: 'numeric' },
        null
    ]
});

$(document).on('click', '.btn-visualizar', function() {
    let pedido = $(this).data('pedido');
    
    window.open('pedido_finalizado.php?pedido='+pedido);
});

window.pedidoAprovado = function(pedido) {
    let btn = $('[data-pedido='+pedido+']');
    $(btn).after('<span class="label label-success">Aprovado</span>');
    $(btn).remove();
}

window.pedidoCancelado = function(pedido) {
    let btn = $('[data-pedido='+pedido+']');
    $(btn).after('<span class="label label-important">Cancelado</span>');
    $(btn).remove();
}
    
</script>

<?php
include 'rodape.php';
?>