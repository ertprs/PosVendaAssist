<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$sqlPrincipal = "SELECT tbl_posto_fabrica.posto, 
                        tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto_nome 
                   FROM tbl_posto_fabrica
                   JOIN tbl_posto USING(posto)
                   JOIN tbl_credenciamento ON tbl_posto_fabrica.posto=tbl_credenciamento.posto AND tbl_posto_fabrica.fabrica=tbl_credenciamento.fabrica
                  WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                    AND tbl_posto_fabrica.credenciamento='EM CREDENCIAMENTO'
                    AND tbl_credenciamento.status='EM CREDENCIAMENTO'";
$resPrincipal = pg_query($con, $sqlPrincipal);

if (pg_num_rows($resPrincipal) > 0) {
    $dadosPostos = pg_fetch_all($resPrincipal);
}

$layout_menu = "cadastro";
$title = "Postos em Credenciamento";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th class="tal">Posto Autorizado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dadosPostos as $k => $rows) {?>
        <tr>
            <td class='tal'><a href="posto_cadastro.php?posto=<?php echo $rows["posto"];?>" target="_blank"><?php echo $rows["posto_nome"];?></a></td>
        </tr>
        <?php }?>
        </tbody>
    </table>
</div> 
<?php include 'rodape.php';?>
