<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

if ($areaAdmin === true) {
	$admin_privilegios = "call_center";
	include __DIR__.'/autentica_admin.php';
} else {
	 include __DIR__.'/../autentica_usuario.php';
}

include 'funcoes.php';

$produto = $_GET["produto"];

$sql = "SELECT  tbl_produto_troca_opcao.produto, 
                produto_opcao, 
                voltagem,
                referencia,
                descricao,
                tbl_produto_troca_opcao.kit 
        FROM tbl_produto_troca_opcao
        JOIN tbl_produto ON tbl_produto.produto = tbl_produto_troca_opcao.produto_opcao
        AND tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.ativo IS TRUE 
        WHERE tbl_produto_troca_opcao.produto = {$produto}
        ORDER BY kit ASC";
$res = pg_query($con, $sql);
?>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<script>
    $(function(){
        $("#exibir_produtos").click(function(){
            $(".table").toggle();
        });

        $("#fechar").click(function(){
            window.parent.Shadowbox.close();
        });
    });
</script>
<?
if (pg_num_rows($res) > 0) {
	?>
    <button class="btn btn-danger btn-small" id="fechar" style="position: fixed;border-radius: 0px 0px 0px 5px;right:0;"><?= traduz("fechar") ?></button>
    <div class="alert" style="background-color: #ffd20a;color: black;">
        <h5><?= traduz("o.modelo.de.origem.esta.indisponivel") ?>. <?= traduz("verifique.se.o.cliente.aceita.o.s.substituto.s.cadastrado.s") ?>. <?= traduz("se.sim.continue.o.lancamento.da.ordem.de.servico.se.nao.entre.em.contato.com.a.fabrica") ?></h5>
    </div>
    <center>
        <button class="btn btn-info btn-large" id="exibir_produtos"> 
            <?= traduz("exibir.produtos.substitutos") ?>
        </button>
    </center>
    <br />
	<table class="table table-striped table-bordered" style="display: none;">
		<thead>
            <tr class="titulo_coluna">
                <th><?= traduz("referencia") ?></th>
                <th><?= traduz("descricao") ?></th>
                <th><?= traduz("voltagem") ?></th>
            </tr>
        </thead>
	<?
    $kit_anterior = 0;

	for ($i=0;$i < pg_num_rows($res);$i++) {
		$referencia = pg_fetch_result($res, $i, 'referencia');
        $descricao  = pg_fetch_result($res, $i, 'descricao');
        $voltagem   = pg_fetch_result($res, $i, 'voltagem');
        $kit        = pg_fetch_result($res, $i, 'kit');

        if ($kit != 0) {
            if ($kit_anterior != $kit) { 
                ?>
                <tr>
                    <td colspan="3"></td>
                </tr>
                <?php
                if ($kit_anterior == 0) {
                ?>  
                <tr>
                    <td colspan=3 align=center>
                        <b>KITs:</b> <?= traduz("podera.ser.selecionado.um.kit.para.trocar.o.produto.atual.por.varios.outros") ?>.
                    </td>
                </tr>
                <?php
                }
                ?>
                <tr class="titulo_coluna">
                    <th style="text-align: center;" colspan="3">KIT <?= $kit ?></th>
                </tr>
            <?php
            }
            ?>
            <tr>
                <td class="tac"><?= $referencia ?></td>
                <td><?= $descricao  ?></a></td>
                <td class="tac"><?= $voltagem ?></td>
            </tr>
            <?php
            $kit_anterior = $kit;
        } else {
        ?>

                <tr>
                    <td class="tac"><?= $referencia ?></td>
                    <td><?= $descricao  ?></a></td>
                    <td class="tac"><?= $voltagem ?></td>
                </tr>
    	<?
        }
    }
    ?>
	</table>
	<?
} else {
?>
<div class="alert alert-warning"><h4><?= traduz("erro.na.consulta") ?></h4></div>
<?
}
