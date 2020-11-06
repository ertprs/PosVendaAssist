<?php
$path_link    = 'xls/';
$path         = 'xls/';
$path_tmp     = '/tmp/';
$arquivo_link = array();
$data         = date("Y-m-d");

$arquivo_nome     = "relatorio_helpdesk_posto_autorizado-{$data}.html";
$arquivo_nome_xls = "relatorio_helpdesk_posto_autorizado-{$data}.xls";

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

$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Protocolo</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Interacao</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Status</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Data</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Atendente</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Tipo de Solicitacao</th>";
$xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>O.S.</th>";
if($login_fabrica == 151){
  $xlsHeader .= "<th nowrap bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Centro Distribuicao</th>";
}

$xlsHeader .= "</tr>";

fputs($fp,$xlsHeader);
$xlsContents    = "";

$i              = 1;
$hd_chamado_ant = null;

while($objeto_relatorio = pg_fetch_object($resRelatorio)){
  $xlsContents .= "<tr>";

  if ($hd_chamado_ant != $objeto_relatorio->hd_chamado) {
    $hd_chamado_ant = $objeto_relatorio->hd_chamado;
    $i              = 1;
  } else {
    $i++;
  }

  $xlsContents .= "<td> ".$objeto_relatorio->hd_chamado." </td>";
  $xlsContents .= "<td> ".$i." </td>";
  $xlsContents .= "<td> ".$objeto_relatorio->status." </td>";
  $xlsContents .= "<td> ".$objeto_relatorio->data_temp." </td>";
  $xlsContents .= "<td> ".retira_acentos($objeto_relatorio->atendente_nome)." </td>";
  $xlsContents .= "<td> ".retira_acentos($objeto_relatorio->tipo_solicitacao_chamado)." </td>";
  $xlsContents .= "<td> ".$objeto_relatorio->os." </td>";
  if($login_fabrica == 151){        
    if($objeto_relatorio->centro_distribuicao == "mk_nordeste"){
      $xlsContents .= "<td>MK Nordeste</td>";  
    }else if($objeto_relatorio->centro_distribuicao == "mk_sul") {
      $xlsContents .= "<td>MK Sul</td>"; 
    } else{
      $xlsContents .= "<td>&nbsp;</td>"; 
    }
  }

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
