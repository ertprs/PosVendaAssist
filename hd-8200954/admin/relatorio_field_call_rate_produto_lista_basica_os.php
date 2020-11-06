<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$admin_privilegios="gerencia";
include "autentica_admin.php";
include "funcoes.php";


$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS - FIELD CALL-RATE LISTA BÁSICA DO PRODUTO";

include 'cabecalho_new.php';
$plugins = array(
  "dataTable"
);

include("plugin_loader.php");

$peca = $_GET['peca'];
$produto = $_GET['produto'];
$data_inicial = $_GET['data_inicial'];
$data_final = $_GET['data_final'];

$sql = "SELECT tbl_os.os,
        TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
        TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
        tbl_peca.referencia AS peca_referencia,
        tbl_peca.descricao AS peca_descricao,
        tbl_produto.referencia AS produto_referencia,
        tbl_produto.descricao AS produto_descricao
        FROM tbl_os
        JOIN tbl_produto USING (produto)
        JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
        JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
        JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
        LEFT JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito
        WHERE tbl_os.fabrica = $login_fabrica
        AND tbl_produto.produto = $produto
        AND tbl_os_item.peca = $peca
        AND tbl_os.excluida IS FALSE
        AND tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'";
$res = pg_query($con, $sql);
if(pg_num_rows($res) > 0){
  $count = pg_num_rows($res);
?>
  <table id="resultado_os" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
      <tr class='titulo_coluna' >
        <th>OS</th>
        <th>Produto</th>
        <th>Peça</th>
        <th>Data Abertura</th>
        <th>Data Fechamento</th>
      </tr>
    </thead>
    <tbody>


    <?php
    for ($i=0; $i <$count ; $i++) {
      $os = pg_fetch_result($res, $i, 'os');
      $data_abertura = pg_fetch_result($res, $i, 'data_abertura');
      $data_fechamento = pg_fetch_result($res, $i, 'data_fechamento');
      $peca_referencia = pg_fetch_result($res, $i, 'peca_referencia');
      $peca_descricao = pg_fetch_result($res, $i, 'peca_descricao');
      $produto_referencia = pg_fetch_result($res, $i, 'produto_referencia');
      $produto_descricao = pg_fetch_result($res, $i, 'produto_descricao');

      $body = "<tr>
                <td class='tac'><a href='os_press.php?os=$os' target='_blank'>{$os}</a></td>
                <td class='tac'>{$produto_referencia} - {$produto_descricao}</td>
                <td class='tac'>{$peca_referencia} - {$peca_descricao}</td>
                <td class='tac'>{$data_abertura}</td>
                <td class='tac'>{$data_fechamento}</td>
              </tr>";
      echo $body;
    }
    ?>
    </tbody>
  </table>
  <br/>
<?php
}else{
  echo '
    <div class="container">
    <div class="alert">
          <h4>Nenhum resultado encontrado</h4>
    </div>
    </div></div>';
}
include "rodape.php";
?>
