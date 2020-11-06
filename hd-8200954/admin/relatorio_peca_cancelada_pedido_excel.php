<?php

$path_link    = 'xls/';
$path         = 'xls/';
$path_tmp     = '/tmp/';
$arquivo_link = array();
$data         = date("Y-m-d");

$arquivo_nome     = "relatorio_peca_cancelada_pedido-{$data}.html";
$arquivo_nome_xls = "relatorio_peca_cancelada_pedido-{$data}.xls";

$arquivo_completo     = $path.$arquivo_nome;
$arquivo_completo_xls = $path.$arquivo_nome_xls;

echo `rm $arquivo_completo `;
echo `rm $arquivo_completo_xls `;

$fp = fopen($arquivo_completo,"w");

fputs($fp,"<html>");
fputs($fp,"<body>");

$xlsTableBegin = "<table border='1'>";
fputs($fp,$xlsTableBegin);

$xlsHeader = "";
$xlsHeader .= "<tr>";

$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Pedido</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Data do Pedido</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Código Posto</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Nome Posto</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Código Peça</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Descrição Peça</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Quantidade Cancelada</th>";

$xlsHeader .= "</tr>";

fputs($fp,$xlsHeader);
$xlsContents = "";

while($objeto_relatorio = pg_fetch_object($resPedido)){

  $xlsContents .= "<tr>";
  $xlsContents .= "<td> ".$objeto_relatorio->pedido." </td>";
  $xlsContents .= "<td> ".$objeto_relatorio->data_pedido." </td>";
  $xlsContents .= "<td> ".$objeto_relatorio->codigo_posto." </td>";
  $xlsContents .= "<td> ".$objeto_relatorio->nome_posto." </td>";
  $xlsContents .= "<td> ".$objeto_relatorio->peca_referencia." </td>";
  $xlsContents .= "<td> ".$objeto_relatorio->peca_descricao." </td>";
  $xlsContents .= "<td> ".$objeto_relatorio->qtde_cancelada." </td>";
  $xlsContents .= "</tr>";

}

fputs($fp,$xlsContents);

$xlsTableEnd = "</table>";
fputs($fp,$xlsTableEnd);

fputs($fp,"</body>");
fputs($fp,"</body>");
fputs($fp,"</html>");

fclose($fp);

$arquivo_nome                             = rename($arquivo_completo, $arquivo_completo_xls);
$arquivo_link[$tipo_pesquisa_a_gerar[$z]] = $path_link.$arquivo_nome_xls;

?>
<div id='btn_excel' class="btn_excel">
  <a href="<?=$path_link.$arquivo_nome_xls?>" target="_blank" >
    <img src="imagens/excel.gif" alt="Download Excel">
    Gerar Arquivo Excel
  </a>
  <br/>
</div>
<br>
