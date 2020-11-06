<?php
$msg_erro = array();

$path_link    = 'xls/';
$path         = 'xls/';
$path_tmp     = '/tmp/';

$arquivo_nome     = "relatorio_conferencia_".$login_fabrica.".html";
$arquivo_nome_xls = "relatorio_conferencia_".$login_fabrica.".xls";

$arquivo_completo     = $path.$arquivo_nome;
$arquivo_completo_xls = $path.$arquivo_nome_xls;

echo `rm $arquivo_completo `;
echo `rm $arquivo_completo_xls `;

$fp = fopen($arquivo_completo,"w");

fputs($fp,"<html>");
fputs($fp,"<body>");

//DEFINE CABECALHO DO XLS
$xlsTableBegin = "<table border='1'>";
fputs($fp,$xlsTableBegin);

$sql = "SELECT
      tbl_faturamento.faturamento,
      tbl_faturamento.nota_fiscal,
      tbl_faturamento.serie,
      TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS data_emissao,
      tbl_faturamento.total_nota AS valor_total,
      tbl_tipo_pedido.descricao AS tipo_pedido,
      tbl_posto.nome AS posto_autorizado,
      SUM(tbl_faturamento_item.qtde) AS qtde_faturada,
      SUM(tbl_faturamento_item.qtde_quebrada) AS qtde_faltante
  FROM tbl_faturamento
  INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
  INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido AND tbl_pedido.fabrica = $login_fabrica
  INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
  INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
  INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
  WHERE tbl_faturamento.fabrica = {$login_fabrica}
  AND tbl_faturamento.emissao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' 
  {$cond_posto} 
  {$cond_tipo_pedido} 
  GROUP BY
      tbl_faturamento.faturamento,
      tbl_faturamento.nota_fiscal,
      tbl_faturamento.serie,
      tbl_faturamento.emissao,
      tbl_faturamento.total_nota,
      tbl_tipo_pedido.descricao,
      tbl_posto.nome 
  {$cond_faturamento} 
  ORDER BY tbl_faturamento.emissao DESC, tbl_posto.nome ASC";
$resExcel = pg_query($con, $sql);

if (pg_num_rows($resExcel) > 0) {

  $retornoSqlJsonXLS = array();
  foreach (pg_fetch_all($resExcel) as $key) {
    $valor_total = $key['valor_total'];
    $valor_total = number_format($valor_total, 2);

    $xlsContents .= "<tr bgcolor='#DCDCDC'>";
    $xlsContents .= "<td align='center'><b>Nota Fiscal</b></td>";
    $xlsContents .= "<td align='center'><b>Série</b></td>";
    $xlsContents .= "<td align='center'><b>Data Emissão</b></td>";
    $xlsContents .= "<td align='center'><b>Valor Total</b></td>";
    $xlsContents .= "<td align='center'><b>Tipo de Pedido</b></td>";
    $xlsContents .= "<td align='center'><b>Posto</b></td>";
    $xlsContents .= "<td align='center'><b>Quantidade Faturada</b></td>";
    $xlsContents .= "<td align='center'><b>Quantidade Faltante</b></td>";
    $xlsContents .= "</tr>";

    $xlsContents .= "<tr>";
    $xlsContents .= "<td align='center'> ".$key['nota_fiscal']." </td>";
    $xlsContents .= "<td align='center'> ".$key['serie']." </td>";
    $xlsContents .= "<td align='center'> ".$key['data_emissao']." </td>";
    $xlsContents .= "<td align='center'> ".$valor_total." </td>";
    $xlsContents .= "<td align='center'> ".$key['tipo_pedido']." </td>";
    $xlsContents .= "<td> ".$key['posto_autorizado']." </td>";
    $xlsContents .= "<td align='center'> ".$key['qtde_faturada']." </td>";
    $xlsContents .= "<td align='center'> ".$key['qtde_faltante']." </td>";
    $xlsContents .= "</tr>";

    if($key['qtde_faltante'] > 0){
      $sql = "SELECT tbl_faturamento_item.faturamento_item,
                tbl_peca.peca,
                tbl_peca.referencia,
                tbl_peca.descricao,
                tbl_faturamento_item.qtde,
                tbl_faturamento_item.qtde_quebrada,
                tbl_faturamento_item.obs_conferencia
            FROM tbl_faturamento_item
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca 
                AND tbl_peca.fabrica = {$login_fabrica}
            WHERE tbl_faturamento_item.faturamento = ".$key['faturamento'];
      $resPeca = pg_query($con,$sql);

      $rowspan = pg_num_rows($resPeca);

      $xlsContents .= "<tr>";
      $xlsContents .= "<td rowspan='".($rowspan+1)."'></td>";
      $xlsContents .= "<td bgcolor='#DCDCDC' align='center'> <b>Referência da Peça </b></td>";
      $xlsContents .= "<td bgcolor='#DCDCDC' align='center'> <b>Descrição  da Peça </b></td>";
      $xlsContents .= "<td bgcolor='#DCDCDC' align='center'> <b>Quantidade Faltante </b></td>";
      $xlsContents .= "<td colspan='4' rowspan='".($rowspan+1)."'></td>";
      $xlsContents .= "</tr>";
      
      foreach (pg_fetch_all($resPeca) as $value) {
        $xlsContents .= "<tr>";
        $xlsContents .= "<td align='center'> ".$value['referencia']." </td>";
        $xlsContents .= "<td align='center'> ".$value['descricao']." </td>";
        $xlsContents .= "<td align='center'> ".$value['qtde_quebrada']." </td>";
        $xlsContents .= "</tr>";

      }
      $rowspan = "";
    }else{
      $xlsContents .= "<tr>";
      $xlsContents .= "<td></td>";
      $xlsContents .= "<td bgcolor='#DCDCDC' align='center'> <b>Faturamento não é divergente</b> </td>";
      $xlsContents .= "<td colspan='6'></td>";
      $xlsContents .= "</tr>";
    }
    $xlsContents .= "<tr>";
    $xlsContents .= "<td colspan='8'></td>";
    $xlsContents .= "</tr>";
  }
} else {
  $xlsContents .= "<td></td>";
}

fputs($fp,$xlsContents);

$xlsTableEnd = "</table>";
fputs($fp,$xlsTableEnd);

fputs($fp,"</body>");
fputs($fp,"</html>");

fclose($fp);

$arquivo_nome = rename($arquivo_completo, $arquivo_completo_xls);
// $arquivo_link[$tipo_pesquisa_a_gerar[$z]] = $path_link.$arquivo_nome_xls;

?>
<a href="<?=$path_link.$arquivo_nome_xls?>" class="icon_excel" target="_blank" >
  <img src="imagens/excel.gif" alt="Download Excel">
       <?php
       echo "Download Arquivo XLS";
       ?>
</a>
<br>
