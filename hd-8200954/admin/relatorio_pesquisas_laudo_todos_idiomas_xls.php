<?php

$msg_erro = array();

$style = "

.titulo_tabela{
   background-color:#596d9b; font: bold 14px \"Arial\"; color:#FFFFFF; text-align:center;
}

.titulo_coluna{
   background-color:#596d9b !important; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;
}

table.tabela{
  width:700px;
  margin:auto;
  background-color: #F7F5F0;
}

table.tabela tr td{
   font-family: verdana;
   font-size: 11px;
   border-collapse: collapse;
   border:1px solid #596d9b;
}

";

$path_link    = 'xls/';
$path         = 'xls/';
$path_tmp     = '/tmp/';
$arquivo_link = array();

$arquivo_nome     = "relatorio_pesquisas_laudo_fabrica_".$login_fabrica."_todos_idiomas.html";
$arquivo_nome_xls = "relatorio_pesquisas_laudo_fabrica_".$login_fabrica."_todos_idiomas.xls";

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

// if ($pesquisa == "ordem_de_servico") {
//   $xlsHeader = "
//   <tr bgcolor=\"#CFA533\" >
//       <th nowrap>OS</th>
//       <th nowrap>CNPJ</th>
//       <th nowrap>Posto</th>
//       <th nowrap>Data Resposta</th>
//       <th nowrap>Pesquisa</th>";
// }

#$idioma = "";

#$idioma = array(0 => '%"es"%', 1 => '%"en"%', 2 => '%"pt"%');

$idioma_pesquisa = "";
#$idioma_pesquisa = array(0 => 'es', 1 => 'en', 2 => 'pt');
$idioma_pesquisa = array(0 => 'es');
$languge_excel = '%"language"%';

  $sql = "";
  $sql = "SELECT tbl_laudo_tecnico_os.os,
              tbl_os.sua_os,
              tbl_posto_fabrica.codigo_posto,
              tbl_laudo_tecnico_os.observacao,
              tbl_laudo_tecnico_os.os AS pais,
              tbl_laudo_tecnico_os.os AS cidade,
              to_char(tbl_laudo_tecnico_os.data,'DD/MM/YYYY') AS data
          FROM tbl_os
              JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
                AND tbl_posto_fabrica.fabrica = $login_fabrica
          WHERE tbl_laudo_tecnico_os.data BETWEEN '$aux_data_inicial' AND '$aux_data_final'
          AND tbl_laudo_tecnico_os.observacao LIKE '$languge_excel'
          AND tbl_laudo_tecnico_os.observacao LIKE ANY ($sql_idiomas) AND tbl_os.posto = 6359 AND titulo ILIKE 'Pesquisa de%' ORDER BY tbl_laudo_tecnico_os.data;";
  $resPerguntasTipo = "";
  $resPerguntasTipo = pg_query($con,$sql);

  if(pg_num_rows($resPerguntasTipo) > 0){
    $xlsHeader = "";
    $xlsHeader .= "<tr>";
    $xlsHeader .= "</tr>";

    $xlsHeader .= "<tr>
      <th nowrap>1 - ".$language[$idioma_pesquisa[0]]['questao_um']." </th>";
    $xlsHeader .= "<th nowrap>2 - ".$language[$idioma_pesquisa[0]]['questao_dois']." </th>";
    $xlsHeader .= "<th nowrap>3 - ".$language[$idioma_pesquisa[0]]['questao_tres']." </th>";
    $xlsHeader .= "<th nowrap>4 - ".$language[$idioma_pesquisa[0]]['questao_quatro']." </th>";
    $xlsHeader .= "<th nowrap>5 - ".$language[$idioma_pesquisa[0]]['questao_cinco']." </th>";
    $xlsHeader .= "<th nowrap>".$language[$idioma_pesquisa[0]]['questao_marca']."</th>";
    $xlsHeader .= "<th nowrap>".$language[$idioma_pesquisa[0]]['questao_produto']."</th>";
    $xlsHeader .= "<th nowrap>".$language[$idioma_pesquisa[0]]['questao_qual']."</th>";
    $xlsHeader .= "<th nowrap>6 - ".$language[$idioma_pesquisa[0]]['questao_seis']." </th>";
    $xlsHeader .= "<th nowrap>7 - ".$language[$idioma_pesquisa[0]]['questao_sete']." </th>";
    $xlsHeader .= "<th nowrap>8 - ".$language[$idioma_pesquisa[0]]['questao_oito']." </th>";
    $xlsHeader .= "<th nowrap>9 - ".$language[$idioma_pesquisa[0]]['questao_nove']." </th>";
    $xlsHeader .= "<th nowrap>10 - ".$language[$idioma_pesquisa[0]]['questao_dez']." </th>";
    $xlsHeader .= "<th nowrap>11 - ".$language[$idioma_pesquisa[0]]['questao_onze']." </th>";
    $xlsHeader .= "<th nowrap>12 - ".$language[$idioma_pesquisa[0]]['questao_doze']." </th>";
    $xlsHeader .= "<th nowrap>13 - ".$language[$idioma_pesquisa[0]]['questao_treze']." </th>";
    $xlsHeader .= "<th nowrap>14 - ".$language[$idioma_pesquisa[0]]['questao_quartoze']." </th>";
    $xlsHeader .= "<th nowrap>15 - ".$language[$idioma_pesquisa[0]]['questao_quinze']." </th>";
    $xlsHeader .= "<th nowrap> ".$language[$idioma_pesquisa[0]]['questao_numero']." </th>";
    $xlsHeader .= "
    </tr>
    ";
    fputs($fp,$xlsHeader);
    $xlsContents = "";

    for ($j=0; $j < pg_num_rows($resPerguntasTipo); $j++) {
      $resultado_resposta = pg_fetch_result($resPerguntasTipo,$j,observacao);
      $resultado_resposta = json_decode(utf8_encode(stripslashes($resultado_resposta)));
      $xlsContents .= "<tr bgcolor='$cor'>";

      $cidade = "";
      if($resultado_resposta->cidade != ""){
          $a=-1;
          while(substr($resultado_resposta->cidade, $a, 1) != "+"){
              $a--;
              if(($a*(-1)) == strlen($resultado_resposta->cidade)){
                  break;
              }
          }
          if(substr($resultado_resposta->cidade, $a, 1) == "+"){
              $a++;
          }
          $cidade = substr($resultado_resposta->cidade, $a);
      }else{
          $cidade = $resultado_resposta->cidade;
      }

      if($resultado_resposta->pais == ""){ $resultado_resposta->pais = " "; }
      if($cidade == ""){ $cidade = " "; }

      if ($resultado_resposta->posto == ""){
        $resultado_resposta->posto = " ";
      }else{
        $resultado_resposta->posto = str_replace("+", " ", $resultado_resposta->posto);
      }

      if($resultado_resposta->os == ""){ $resultado_resposta->os = " "; }

      if($resultado_resposta->equipamento == ""){
        $resultado_resposta->equipamento = " ";
      }else{
        $resultado_resposta->equipamento = $language[$idioma_pesquisa[0]][$resultado_resposta->equipamento];
      }

      if($resultado_resposta->marca == ""){
        $resultado_resposta->marca = " ";
      }else{
        $sql = "SELECT marca, nome FROM tbl_marca WHERE fabrica = 1 AND marca = ".$resultado_resposta->marca;
        $resMarca = pg_query($con,$sql);
        // echo $sql; exit;
        if (pg_num_rows($resMarca)>0) {
            $resMarca = pg_fetch_array($resMarca);
            $resultado_resposta->marca = $resMarca['nome'];
        }
      }

      if($resultado_resposta->produto == ""){ $resultado_resposta->produto = " "; }
      if($resultado_resposta->outro_qual == ""){ $resultado_resposta->outro_qual = " "; }

      if($resultado_resposta->recomendacao == ""){
        $resultado_resposta->recomendacao = " ";
      }

      if($resultado_resposta->razao_pontuacao == ""){
        $resultado_resposta->razao_pontuacao = " ";
      }else{
        $resultado_resposta->razao_pontuacao = $language[$idioma_pesquisa[0]][$resultado_resposta->razao_pontuacao];
      }

      if($resultado_resposta->complemento_classificacao == ""){
        $resultado_resposta->complemento_classificacao = " ";
      }else{
        $resultado_resposta->complemento_classificacao = utf8_decode($resultado_resposta->complemento_classificacao);
      }

      if($resultado_resposta->nota_tempo_reparo == ""){
        $resultado_resposta->nota_tempo_reparo = " ";
      }else{
        $resultado_resposta->nota_tempo_reparo = $language[$idioma_pesquisa[0]][$resultado_resposta->nota_tempo_reparo];
      }

      if($resultado_resposta->nota_preco_reparo == ""){
        $resultado_resposta->nota_preco_reparo = " ";
      }else{
        $resultado_resposta->nota_preco_reparo = $language[$idioma_pesquisa[0]][$resultado_resposta->nota_preco_reparo];
      }

      if($resultado_resposta->nota_qualidade_reparo == ""){
        $resultado_resposta->nota_qualidade_reparo = " ";
      }else{
        $resultado_resposta->nota_qualidade_reparo = $language[$idioma_pesquisa[0]][$resultado_resposta->nota_qualidade_reparo];
      }

      if($resultado_resposta->nota_atencao == ""){
        $resultado_resposta->nota_atencao = " ";
      }else{
        $resultado_resposta->nota_atencao = $language[$idioma_pesquisa[0]][$resultado_resposta->nota_atencao];
      }

      if($resultado_resposta->nota_explicacao == ""){
        $resultado_resposta->nota_explicacao = " ";
      }else{
        $resultado_resposta->nota_explicacao = $language[$idioma_pesquisa[0]][$resultado_resposta->nota_explicacao];
      }

      if($resultado_resposta->nota_aspecto == ""){
        $resultado_resposta->nota_aspecto = " ";
      }else{
        $resultado_resposta->nota_aspecto = $language[$idioma_pesquisa[0]][$resultado_resposta->nota_aspecto];
      }

      if($resultado_resposta->nota_geral == ""){
        $resultado_resposta->nota_geral = " ";
      }else{
        $resultado_resposta->nota_geral = $language[$idioma_pesquisa[0]][$resultado_resposta->nota_geral];
      }

      if($resultado_resposta->numero_dias == ""){
        $resultado_resposta->numero_dias = " ";
      }else if($resultado_resposta->numero_dias == "mais"){
        $resultado_resposta->numero_dias = "Mais de 8";
      }

      $cidade = "";
      $cidade = str_replace("+", " ", $resultado_resposta->cidade);

      $xlsContents .= "<td> ".$resultado_resposta->pais." </td>";
      $xlsContents .= "<td> ".$cidade." </td>";
      $xlsContents .= "<td> ".str_replace("+", " ", $resultado_resposta->posto)." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->os." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->equipamento." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->marca." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->produto." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->outro_qual." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->recomendacao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->razao_pontuacao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->complemento_classificacao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_tempo_reparo." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_preco_reparo." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_qualidade_reparo." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_atencao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_explicacao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_aspecto." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_geral." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->numero_dias." </td>";

      // if($idioma_pesquisa[$i] == 'es' || $idioma_pesquisa[$i] == 'en'){
        $resultado_resposta->recomendacao = (int)$resultado_resposta->recomendacao;

        if($resultado_resposta->recomendacao < 7){
          $xlsContents .= "<td> Detractor </td>";

        }else if($resultado_resposta->recomendacao < 9){
          $xlsContents .= "<td> Neutro </td>";
        }else{
          $xlsContents .= "<td> Promotor </td>";
        }
      // }
      $xlsContents .= "</tr>";
    }

  }else{
    $xlsHeader = "";
    $xlsHeader .= "<tr>";
    $xlsHeader .= "</tr>";
    $xlsContents = "";
    $xlsContents .= "<td></td>";
  }

  fputs($fp,$xlsContents);


/*
for($i=0;isset($idioma[$i]); $i++){

  $sql = "";
  $sql = "SELECT tbl_laudo_tecnico_os.os,
              tbl_os.sua_os,
              tbl_posto_fabrica.codigo_posto,
              tbl_laudo_tecnico_os.observacao,
              tbl_laudo_tecnico_os.os AS pais,
              tbl_laudo_tecnico_os.os AS cidade,
              to_char(tbl_laudo_tecnico_os.data,'DD/MM/YYYY') AS data
          FROM tbl_os
              JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
                AND tbl_posto_fabrica.fabrica = $login_fabrica
          WHERE tbl_laudo_tecnico_os.data BETWEEN '$aux_data_inicial' AND '$aux_data_final'
          AND tbl_laudo_tecnico_os.observacao LIKE '$languge_excel'
          AND tbl_laudo_tecnico_os.observacao LIKE '$idioma[$i]' AND tbl_os.posto = 6359 AND titulo ILIKE 'Pesquisa de%' ORDER BY tbl_laudo_tecnico_os.data;";

  // $sql = "SELECT tbl_laudo_tecnico_os.os,
  //       tbl_os.sua_os,
  //       tbl_laudo_tecnico_os.observacao,
  //       to_char(tbl_laudo_tecnico_os.data,'DD/MM/YYYY') AS data
  //   FROM tbl_os
  //       JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os
  //   WHERE tbl_laudo_tecnico_os.data BETWEEN '$yi-$mi-$di' AND '$yf-$mf-$df'
  //       AND tbl_laudo_tecnico_os.observacao LIKE '$pesquisa_grafico'
  //       AND titulo ILIKE 'Pesquisa de%' ORDER BY tbl_os.data_fechamento";
  $resPerguntasTipo = "";
  $resPerguntasTipo = pg_query($con,$sql);

  if(pg_num_rows($resPerguntasTipo) > 0){
    $xlsHeader = "";
    $xlsHeader .= "<tr>";
    $xlsHeader .= "</tr>";

    $xlsHeader .= "<tr>
      <th nowrap>1 - ".$language[$idioma_pesquisa[$i]]['questao_um']." </th>";
    $xlsHeader .= "<th nowrap>2 - ".$language[$idioma_pesquisa[$i]]['questao_dois']." </th>";
    $xlsHeader .= "<th nowrap>3 - ".$language[$idioma_pesquisa[$i]]['questao_tres']." </th>";
    $xlsHeader .= "<th nowrap>4 - ".$language[$idioma_pesquisa[$i]]['questao_quatro']." </th>";
    $xlsHeader .= "<th nowrap>5 - ".$language[$idioma_pesquisa[$i]]['questao_cinco']." </th>";
    $xlsHeader .= "<th nowrap>".$language[$idioma_pesquisa[$i]]['questao_marca']."</th>";
    $xlsHeader .= "<th nowrap>".$language[$idioma_pesquisa[$i]]['questao_produto']."</th>";
    $xlsHeader .= "<th nowrap>".$language[$idioma_pesquisa[$i]]['questao_qual']."</th>";
    $xlsHeader .= "<th nowrap>6 - ".$language[$idioma_pesquisa[$i]]['questao_seis']." </th>";
    $xlsHeader .= "<th nowrap>7 - ".$language[$idioma_pesquisa[$i]]['questao_sete']." </th>";
    $xlsHeader .= "<th nowrap>8 - ".$language[$idioma_pesquisa[$i]]['questao_oito']." </th>";
    $xlsHeader .= "<th nowrap>9 - ".$language[$idioma_pesquisa[$i]]['questao_nove']." </th>";
    $xlsHeader .= "<th nowrap>10 - ".$language[$idioma_pesquisa[$i]]['questao_dez']." </th>";
    $xlsHeader .= "<th nowrap>11 - ".$language[$idioma_pesquisa[$i]]['questao_onze']." </th>";
    $xlsHeader .= "<th nowrap>12 - ".$language[$idioma_pesquisa[$i]]['questao_doze']." </th>";
    $xlsHeader .= "<th nowrap>13 - ".$language[$idioma_pesquisa[$i]]['questao_treze']." </th>";
    $xlsHeader .= "<th nowrap>14 - ".$language[$idioma_pesquisa[$i]]['questao_quartoze']." </th>";
    $xlsHeader .= "<th nowrap>15 - ".$language[$idioma_pesquisa[$i]]['questao_quinze']." </th>";
    $xlsHeader .= "<th nowrap> ".$language[$idioma_pesquisa[$i]]['questao_numero']." </th>";
    $xlsHeader .= "
    </tr>
    ";
    fputs($fp,$xlsHeader);
    $xlsContents = "";

    for ($j=0; $j < pg_num_rows($resPerguntasTipo); $j++) {
      $resultado_resposta = pg_fetch_result($resPerguntasTipo,$j,observacao);
      $resultado_resposta = json_decode(utf8_encode(stripslashes($resultado_resposta)));
      $xlsContents .= "<tr bgcolor='$cor'>";

      $cidade = "";
      if($resultado_resposta->cidade != ""){
          $a=-1;
          while(substr($resultado_resposta->cidade, $a, 1) != "+"){
              $a--;
              if(($a*(-1)) == strlen($resultado_resposta->cidade)){
                  break;
              }
          }
          if(substr($resultado_resposta->cidade, $a, 1) == "+"){
              $a++;
          }
          $cidade = substr($resultado_resposta->cidade, $a);
      }else{
          $cidade = $resultado_resposta->cidade;
      }

      if($resultado_resposta->pais == ""){ $resultado_resposta->pais = " "; }
      if($cidade == ""){ $cidade = " "; }

      if ($resultado_resposta->posto == ""){
        $resultado_resposta->posto = " ";
      }else{
        $resultado_resposta->posto = str_replace("+", " ", $resultado_resposta->posto);
      }

      if($resultado_resposta->os == ""){ $resultado_resposta->os = " "; }

      if($resultado_resposta->equipamento == ""){
        $resultado_resposta->equipamento = " ";
      }else{
        $resultado_resposta->equipamento = $language[$idioma_pesquisa[$i]][$resultado_resposta->equipamento];
      }

      if($resultado_resposta->marca == ""){
        $resultado_resposta->marca = " ";
      }else{
        $sql = "SELECT marca, nome FROM tbl_marca WHERE fabrica = 1 AND marca = ".$resultado_resposta->marca;
        $resMarca = pg_query($con,$sql);
        // echo $sql; exit;
        if (pg_num_rows($resMarca)>0) {
            $resMarca = pg_fetch_array($resMarca);
            $resultado_resposta->marca = $resMarca['nome'];
        }
      }

      if($resultado_resposta->produto == ""){ $resultado_resposta->produto = " "; }
      if($resultado_resposta->outro_qual == ""){ $resultado_resposta->outro_qual = " "; }

      if($resultado_resposta->recomendacao == ""){
        $resultado_resposta->recomendacao = " ";
      }

      if($resultado_resposta->razao_pontuacao == ""){
        $resultado_resposta->razao_pontuacao = " ";
      }else{
        $resultado_resposta->razao_pontuacao = $language[$idioma_pesquisa[$i]][$resultado_resposta->razao_pontuacao];
      }

      if($resultado_resposta->complemento_classificacao == ""){
        $resultado_resposta->complemento_classificacao = " ";
      }else{
        $resultado_resposta->complemento_classificacao = utf8_decode($resultado_resposta->complemento_classificacao);
      }

      if($resultado_resposta->nota_tempo_reparo == ""){
        $resultado_resposta->nota_tempo_reparo = " ";
      }else{
        $resultado_resposta->nota_tempo_reparo = $language[$idioma_pesquisa[$i]][$resultado_resposta->nota_tempo_reparo];
      }

      if($resultado_resposta->nota_preco_reparo == ""){
        $resultado_resposta->nota_preco_reparo = " ";
      }else{
        $resultado_resposta->nota_preco_reparo = $language[$idioma_pesquisa[$i]][$resultado_resposta->nota_preco_reparo];
      }

      if($resultado_resposta->nota_qualidade_reparo == ""){
        $resultado_resposta->nota_qualidade_reparo = " ";
      }else{
        $resultado_resposta->nota_qualidade_reparo = $language[$idioma_pesquisa[$i]][$resultado_resposta->nota_qualidade_reparo];
      }

      if($resultado_resposta->nota_atencao == ""){
        $resultado_resposta->nota_atencao = " ";
      }else{
        $resultado_resposta->nota_atencao = $language[$idioma_pesquisa[$i]][$resultado_resposta->nota_atencao];
      }

      if($resultado_resposta->nota_explicacao == ""){
        $resultado_resposta->nota_explicacao = " ";
      }else{
        $resultado_resposta->nota_explicacao = $language[$idioma_pesquisa[$i]][$resultado_resposta->nota_explicacao];
      }

      if($resultado_resposta->nota_aspecto == ""){
        $resultado_resposta->nota_aspecto = " ";
      }else{
        $resultado_resposta->nota_aspecto = $language[$idioma_pesquisa[$i]][$resultado_resposta->nota_aspecto];
      }

      if($resultado_resposta->nota_geral == ""){
        $resultado_resposta->nota_geral = " ";
      }else{
        $resultado_resposta->nota_geral = $language[$idioma_pesquisa[$i]][$resultado_resposta->nota_geral];
      }

      if($resultado_resposta->numero_dias == ""){
        $resultado_resposta->numero_dias = " ";
      }else if($resultado_resposta->numero_dias == "mais"){
        $resultado_resposta->numero_dias = "Mais de 8";
      }

      $cidade = "";
      $cidade = str_replace("+", " ", $resultado_resposta->cidade);
      // if($resultado_resposta->cidade != ""){
      //     $a=-1;
      //     while(substr($resultado_resposta->cidade, $a, 1) != "+"){
      //         $a--;
      //         if(($a*(-1)) == strlen($resultado_resposta->cidade)){
      //             break;
      //         }
      //     }
      //     if(substr($resultado_resposta->cidade, $a, 1) == "+"){
      //         $a++;
      //     }
      //     $cidade = substr($resultado_resposta->cidade, $a);
      // }else{
      //     $cidade = $resultado_resposta->cidade;
      // }

      // $xlsContents .= "<td> ".$resultado_resposta->pais." </td>";
      // $xlsContents .= "<td> ".$cidade." </td>";
      // $xlsContents .= "<td> ".str_replace("+", " ", $resultado_resposta->posto)." </td>";
      // $xlsContents .= "<td> ".$resultado_resposta->os." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->equipamento]." </td>";

      // $sql = "SELECT marca, nome FROM tbl_marca WHERE fabrica = 1 AND marca = ".$resultado_resposta->marca;
      // $resMarca = pg_query($con,$sql);
      // // echo $sql; exit;
      // if (pg_num_rows($resMarca)>0) {
      //     $resMarca = pg_fetch_array($resMarca);
      //     $resultado_resposta->marca = $resMarca['nome'];
      // }

      // $xlsContents .= "<td> ".$resultado_resposta->marca." </td>";
      // $xlsContents .= "<td> ".$resultado_resposta->produto." </td>";
      // $xlsContents .= "<td> ".$resultado_resposta->outro_qual." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->recomendacao]." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->razao_pontuacao]." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->complemento_classificacao]." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->nota_tempo_reparo]." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->nota_preco_reparo]." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->nota_qualidade_reparo]." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->nota_atencao]." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->nota_explicacao]." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->nota_aspecto]." </td>";
      // $xlsContents .= "<td> ".$language[$idioma_pesquisa[$i]][$resultado_resposta->nota_geral]." </td>";
      // $xlsContents .= "<td> ".$resultado_resposta->numero_dias." </td>";

      $xlsContents .= "<td> ".$resultado_resposta->pais." </td>";
      $xlsContents .= "<td> ".$cidade." </td>";
      $xlsContents .= "<td> ".str_replace("+", " ", $resultado_resposta->posto)." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->os." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->equipamento." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->marca." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->produto." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->outro_qual." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->recomendacao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->razao_pontuacao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->complemento_classificacao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_tempo_reparo." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_preco_reparo." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_qualidade_reparo." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_atencao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_explicacao." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_aspecto." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->nota_geral." </td>";
      $xlsContents .= "<td> ".$resultado_resposta->numero_dias." </td>";

      // if($idioma_pesquisa[$i] == 'es' || $idioma_pesquisa[$i] == 'en'){
        $resultado_resposta->recomendacao = (int)$resultado_resposta->recomendacao;

        if($resultado_resposta->recomendacao < 7){
          $xlsContents .= "<td> Detractor </td>";

        }else if($resultado_resposta->recomendacao < 9){
          $xlsContents .= "<td> Neutro </td>";
        }else{
          $xlsContents .= "<td> Promotor </td>";
        }
      // }
      $xlsContents .= "</tr>";
    }

  }else{
    $xlsHeader = "";
    $xlsHeader .= "<tr>";
    $xlsHeader .= "</tr>";
    $xlsContents = "";
    $xlsContents .= "<td></td>";
  }

  fputs($fp,$xlsContents);
}
*/
$xlsTableEnd = "</table>";
fputs($fp,$xlsTableEnd);

fputs($fp,"</body>");
fputs($fp,"</body>");
fputs($fp,"</html>");

fclose($fp);

$arquivo_nome = rename($arquivo_completo, $arquivo_completo_xls);
$arquivo_link[$tipo_pesquisa_a_gerar[$z]] = $path_link.$arquivo_nome_xls;

?>
<a href="<?=$path_link.$arquivo_nome_xls?>" target="_blank" >
  <img src="imagens/excel.gif" alt="Download Excel">
       <?php
       echo "Download Arquivo XLS";
       ?>
</a>
<br>
