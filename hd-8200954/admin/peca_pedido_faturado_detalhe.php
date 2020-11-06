<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';


/* ---------------------- */

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PEÇA POR GARANTIA/FATURADO";
include 'cabecalho_new.php';

$plugins = array(
  "autocomplete",
  "datepicker",
  "shadowbox",
  "mask",
  "dataTable",
  "informacaoCompleta"
);

include("plugin_loader.php");

$data_inicial = $_GET['data_inicial'];
$data_final = $_GET['data_final'];
$peca = $_GET['peca'];
$pedidos_faturados_no_periodo= $_GET['pedidos_faturados_no_periodo'];
if($pedidos_faturados_no_periodo == 't') {
		$cond_data = " AND tbl_faturamento.emissao BETWEEN '{$data_inicial}' AND '{$data_final}' ";
}else{
		$cond_data = " AND tbl_pedido.data BETWEEN '{$data_inicial}' AND '{$data_final}' ";
}


$sql = "SELECT
            tbl_pedido.pedido,
            to_char(tbl_faturamento.emissao, 'DD/MM/YYYY') as data_nf,
            to_char(tbl_pedido.data, 'DD/MM/YYYY') as data_pedido,
            tbl_faturamento.nota_fiscal,
            tbl_posto.nome,
            tbl_peca.descricao,
        case when tbl_condicao.descricao ~* 'garantia' then 'Garantia' else 'Faturado' end as condicao
        FROM tbl_pedido
        JOIN tbl_posto_fabrica USING(posto,fabrica)
        JOIN tbl_posto USING(posto)
        JOIN tbl_condicao ON tbl_pedido.condicao = tbl_condicao.condicao
        JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
        JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
        JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido_item.pedido AND tbl_faturamento_item.peca = tbl_pedido_item.peca
        JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
        LEFT JOIN tbl_os_item ON tbl_pedido_item.pedido = tbl_os_item.pedido and tbl_pedido_item.peca = tbl_os_item.peca
		WHERE tbl_pedido.fabrica = $login_fabrica
		$cond_data
		AND tbl_peca.peca = $peca
	GROUP BY 
	tbl_pedido.pedido,
	data_nf,
	data_pedido,
	tbl_faturamento.nota_fiscal,
	tbl_posto.nome,
	tbl_peca.descricao,
	tbl_condicao.descricao";
#echo nl2br($sql);exit;
$res = pg_query($con, $sql);
?>
<table id="resultado_detalhado" class='table table-striped table-bordered table-hover table-fixed' >
  <thead>
    <tr class='titulo_coluna' >
      <th>Nome Posto</th>
      <th>Peça</th>
      <th>Pedido</th>
      <th>Data Pedido</th>
      <th>Nota Fiscal</th>
      <th>Data Nota</th>
      <th>Condição</th>
    </tr>
  </thead>
  <tbody>
  <?php
    if(pg_num_rows($res) > 0){

      $count = pg_num_rows($res);

      for ($i=0; $i < $count; $i++) {
        $posto = pg_fetch_result($res, $i, 'nome');
        $descricao = pg_fetch_result($res, $i, 'descricao');
        $nota_fiscal = pg_fetch_result($res, $i, 'nota_fiscal');
        $data_nf = pg_fetch_result($res, $i, 'data_nf');
        $pedido = pg_fetch_result($res, $i, 'pedido');
        $data_pedido = pg_fetch_result($res, $i, 'data_pedido');
        $condicao = pg_fetch_result($res, $i, 'condicao');

        $body = "<tr>
                  <td>{$posto}</td>
                  <td>{$descricao}</td>
                  <td class='tac'><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'> {$pedido} </a></td>
                  <td class='tac'>{$data_pedido}</td>
                  <td class='tac'>{$nota_fiscal}</td>
                  <td class='tac'>{$data_nf}</td>
                  <td class='tac'>{$condicao}</td>
                </tr>";
        echo $body;
      }

  ?>
  </tbody>
</table>
<?php
}
include 'rodape.php';
?>





