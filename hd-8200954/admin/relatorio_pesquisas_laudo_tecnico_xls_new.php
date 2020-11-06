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

$arquivo_nome     = "relatorio_pesquisas_laudo_fabrica_$login_fabrica".$pesquisa_idioma.".html";
$arquivo_nome_xls = "relatorio_pesquisas_laudo_fabrica_$login_fabrica".$pesquisa_idioma.".xls";

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

$sql = "SELECT tbl_laudo_tecnico_os.os,
            tbl_os.sua_os,
            tbl_posto_fabrica.codigo_posto,
            tbl_laudo_tecnico_os.observacao,
            JSON_FIELD('pais',tbl_laudo_tecnico_os.observacao) AS pais,
            JSON_FIELD('cidade',tbl_laudo_tecnico_os.observacao) AS cidade,
            to_char(tbl_laudo_tecnico_os.data,'DD/MM/YYYY') AS data
        FROM tbl_os
            JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os ";
    if($pesquisa == "externo_email"){
      $sql .= "JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.enviar_email = 't'";
    }
    $sql .= "JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
        WHERE tbl_laudo_tecnico_os.data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";

    if($pesquisa == "america_latina"){
        $sql .= " AND JSON_FIELD('pais',tbl_laudo_tecnico_os.observacao) IN ('$pesquisa_language') ";

        //$sql .= " AND tbl_laudo_tecnico_os.observacao LIKE ANY ($pesquisa_language) AND tbl_os.posto = 6359"; era assim - retirado " AND tbl_os.posto = 6359 " - para liberar para aparecer brasil no excel

                //AND tbl_laudo_tecnico_os.observacao LIKE '$pesquisa_language' AND tbl_os.posto = 6359";
    }
    $sql .= " AND titulo ILIKE 'Pesquisa de%' ORDER BY tbl_laudo_tecnico_os.data;";
//     exit(nl2br($sql));

$resPerguntasTipo = pg_query($con,$sql);

if (pg_num_rows($resPerguntasTipo) > 0) {
  $xlsHeader .= "<tr>
    <th nowrap>1 - ".$language[$pesquisa_idioma]['questao_um']." </th>";

  $xlsHeader .= "<th nowrap>2 - ".$language[$pesquisa_idioma]['questao_dois']." </th>";
  $xlsHeader .= "<th nowrap>3 - ".$language[$pesquisa_idioma]['questao_tres']." </th>";
  $xlsHeader .= "<th nowrap>4 - ".$language[$pesquisa_idioma]['questao_quatro']." </th>";
  $xlsHeader .= "<th nowrap>5 - ".$language[$pesquisa_idioma]['questao_cinco']." </th>";

  if($pesquisa_idioma == "pt"){
    $xlsHeader .= "<th nowrap>Marca </th>";
  }else if($pesquisa_idioma == "es"){
    $xlsHeader .= "<th nowrap>Marca </th>";
  }else{
    $xlsHeader .= "<th nowrap>Brand </th>";
  }

  if($pesquisa_idioma == "pt"){
    $xlsHeader .= "<th nowrap>Produto </th>";
  }else if($pesquisa_idioma == "es"){
    $xlsHeader .= "<th nowrap>Producto </th>";
  }else{
    $xlsHeader .= "<th nowrap>Product </th>";
  }

  if($pesquisa_idioma == "pt"){
    $xlsHeader .= "<th nowrap>Qual </th>";
  }else if($pesquisa_idioma == "es"){
    $xlsHeader .= "<th nowrap>Cual </th>";
  }else{
    $xlsHeader .= "<th nowrap>Which </th>";
  }

  $xlsHeader .= "<th nowrap>6 - ".$language[$pesquisa_idioma]['questao_seis']." </th>";
  $xlsHeader .= "<th nowrap>7 - ".$language[$pesquisa_idioma]['questao_sete']." </th>";
  $xlsHeader .= "<th nowrap>8 - ".$language[$pesquisa_idioma]['questao_oito']." </th>";
  $xlsHeader .= "<th nowrap>9 - ".$language[$pesquisa_idioma]['questao_nove']." </th>";
  $xlsHeader .= "<th nowrap>10 - ".$language[$pesquisa_idioma]['questao_dez']." </th>";
  $xlsHeader .= "<th nowrap>11 - ".$language[$pesquisa_idioma]['questao_onze']." </th>";
  $xlsHeader .= "<th nowrap>12 - ".$language[$pesquisa_idioma]['questao_doze']." </th>";
  $xlsHeader .= "<th nowrap>13 - ".$language[$pesquisa_idioma]['questao_treze']." </th>";
  $xlsHeader .= "<th nowrap>14 - ".$language[$pesquisa_idioma]['questao_quartoze']." </th>";
  $xlsHeader .= "<th nowrap>15 - ".$language[$pesquisa_idioma]['questao_quinze']." </th>";
  $xlsHeader .= "<th nowrap> ".$language[$pesquisa_idioma]['questao_numero']." </th>";
  $xlsHeader .= "
  </tr>
  ";
  fputs($fp,$xlsHeader);

  /*echo "<pre>";
  var_dump(pg_fetch_all($resPerguntasTipo));
  echo "</pre>";*/

  $retornoSqlJsonXLS = array();
  foreach (pg_fetch_all($resPerguntasTipo) as $key) {
    #$resultado_resposta = json_decode(urldecode(utf8_encode(stripslashes($key['observacao']))));
    $resultado_resposta = $key['observacao'];
    $resultado_resposta = json_decode($resultado_resposta);

    //Retorno Pais
    if($resultado_resposta->pais == ""){ $resultado_resposta->pais = " "; }
    //Retorno Cidade
    $cidade = "";
    ($resultado_resposta->cidade != ""  ? $cidade = str_replace("+", " ", $resultado_resposta->cidade) : $cidade = " ");
    //Retorno Posto
    ($resultado_resposta->posto != ""  ? $posto = utf8_decode(str_replace("+", " ", rawurldecode($resultado_resposta->posto))) : $posto = " ");
    //Retorno OS
    ($resultado_resposta->os == "" ? $resultado_resposta->os = " " : $resultado_resposta->os = $resultado_resposta->os);
    //Retorno Equipamento
    ($resultado_resposta->equipamento == "" ? $resultado_resposta->equipamento = " " : $resultado_resposta->equipamento = utf8_decode($language[$resultado_resposta->language][$resultado_resposta->equipamento]));
    //Retorno Marca
    if ($resultado_resposta->marca == "") {
      $resultado_resposta->marca = " ";
    } else {
      $sql = "SELECT marca, nome FROM tbl_marca WHERE fabrica = 1 AND marca = ".$resultado_resposta->marca;
      $resMarca = pg_query($con,$sql);
      if (pg_num_rows($resMarca)>0) {
          $resMarca = pg_fetch_array($resMarca);
          $resultado_resposta->marca = $resMarca['nome'];
      }
    }
    //Retorno Produto
    if ($resultado_resposta->produto == "") { $resultado_resposta->produto = " "; }
    //Retorno Outro Qual
    if ($resultado_resposta->outro_qual == "") { $resultado_resposta->outro_qual = " "; }
    //Retorno Recomendação
    if ($resultado_resposta->recomendacao == "") { $resultado_resposta->recomendacao = " "; }
    //Retorno Razao Pontuação
    ($resultado_resposta->razao_pontuacao == "" ? $resultado_resposta->razao_pontuacao = " " : $resultado_resposta->razao_pontuacao = utf8_decode($language[$pesquisa_idioma][$resultado_resposta->razao_pontuacao]));
    //Retorno Complemento Classificação
    ($resultado_resposta->complemento_classificacao == "" ? $resultado_resposta->complemento_classificacao = " " : $resultado_resposta->complemento_classificacao = utf8_decode($resultado_resposta->complemento_classificacao));
    //Retorno Nota Tempo Reparo
    ($resultado_resposta->nota_tempo_reparo == "" ? $resultado_resposta->nota_tempo_reparo = " " : $resultado_resposta->nota_tempo_reparo = utf8_decode($language[$pesquisa_idioma][$resultado_resposta->nota_tempo_reparo]));
    ($resultado_resposta->nota_preco_reparo == "" ? $resultado_resposta->nota_preco_reparo = " " : $resultado_resposta->nota_preco_reparo = $language[$pesquisa_idioma][$resultado_resposta->nota_preco_reparo]);
    ($resultado_resposta->nota_qualidade_reparo == "" ? $resultado_resposta->nota_qualidade_reparo = " " : $resultado_resposta->nota_qualidade_reparo = $language[$pesquisa_idioma][$resultado_resposta->nota_qualidade_reparo]);
    ($resultado_resposta->nota_atencao == "" ? $resultado_resposta->nota_atencao = " " : $resultado_resposta->nota_atencao = $language[$pesquisa_idioma][$resultado_resposta->nota_atencao]);
    ($resultado_resposta->nota_explicacao == "" ? $resultado_resposta->nota_explicacao = " " : $resultado_resposta->nota_explicacao = $language[$pesquisa_idioma][$resultado_resposta->nota_explicacao]);
    ($resultado_resposta->nota_aspecto == "" ? $resultado_resposta->nota_aspecto = " " : $resultado_resposta->nota_aspecto = $language[$pesquisa_idioma][$resultado_resposta->nota_aspecto]);
    ($resultado_resposta->nota_geral == "" ? $resultado_resposta->nota_geral = " " : $resultado_resposta->nota_geral = $language[$pesquisa_idioma][$resultado_resposta->nota_geral]);

    if($resultado_resposta->numero_dias == ""){
      $resultado_resposta->numero_dias = " ";
    }else if($resultado_resposta->numero_dias == "mais"){
      $resultado_resposta->numero_dias = "Mais de 8";
    }

    $recomendacao = "";
    $resultado_resposta->recomendacao = (int)$resultado_resposta->recomendacao;
    if ($resultado_resposta->recomendacao < 7) {
      $recomendacao = "Detractor";
    } else if ($resultado_resposta->recomendacao < 9) {
      $recomendacao = "Neutro";
    } else {
      $recomendacao = "Promotor ";
    }

	if(strlen($resultado_resposta->pais) == 2) {
		$retornoSqlJsonXLS[] = array('Pais' => $resultado_resposta->pais,
			'Cidade' => $cidade,
			'Posto' => $posto,
			'OS' => $resultado_resposta->os,
			'Equip' => $resultado_resposta->equipamento,
			'Marca' => $resultado_resposta->marca,
			'Produto' => $resultado_resposta->produto,
			'OutroQual' => $resultado_resposta->outro_qual,
			'RecomendNum' => $resultado_resposta->recomendacao,
			'RazaoPont' => $resultado_resposta->razao_pontuacao,
			'ComplClass' => $resultado_resposta->complemento_classificacao,
			'TpReparo' => $resultado_resposta->nota_tempo_reparo,
			'PrecoReparo' => $resultado_resposta->nota_preco_reparo,
			'QualReparo' => $resultado_resposta->nota_qualidade_reparo,
			'Atencao' => $resultado_resposta->nota_atencao,
			'Explicacao' => $resultado_resposta->nota_explicacao,
			'Aspecto' => $resultado_resposta->nota_aspecto,
			'Geral' => $resultado_resposta->nota_geral,
			'NumDias' => $resultado_resposta->numero_dias,
			'Recomend' => $recomendacao);
	}

  }

  if ($pesquisa == 'america_latina') {
    $arrayPorPaisXLS = array();
    $arrayPorCidadeXLS = array();
    $resOrdenadoXLS = array();
    foreach ($retornoSqlJsonXLS as $k => $v) {
      foreach ($v as $k2 => $v2) {
        if ($k2 == 'Pais') {
          $arrayPorPaisXLS[$k] = $v2;
        }
        if ($k2 == 'Cidade') {
          $arrayPorCidadeXLS[$k] = $v2;
        }
    }
  }
  foreach ($arrayPorPaisXLS as $k => $v) {
    $resOrdenadoXLS[$k] = $retornoSqlJsonXLS[$k];
  }
  array_multisort($arrayPorPaisXLS, SORT_ASC, $arrayPorCidadeXLS, SORT_ASC, $resOrdenadoXLS);
  foreach ($resOrdenadoXLS as $key) {
    $xlsContents .= "<tr bgcolor='$cor'>";
    $xlsContents .= "<td> ".$key['Pais']." </td>";
    $xlsContents .= "<td> ".$key['Cidade']." </td>";
    $xlsContents .= "<td> ".$key['Posto']." </td>";
    $xlsContents .= "<td> ".$key['OS']." </td>";
    $xlsContents .= "<td> ".$key['Equip']." </td>";
    $xlsContents .= "<td> ".$key['Marca']." </td>";
    $xlsContents .= "<td> ".$key['Produto']." </td>";
    $xlsContents .= "<td> ".$key['OutroQual']." </td>";
    $xlsContents .= "<td> ".$key['RecomendNum']." </td>";
    $xlsContents .= "<td> ".$key['RazaoPont']." </td>";
    $xlsContents .= "<td> ".$key['ComplClass']." </td>";
    $xlsContents .= "<td> ".$key['TpReparo']." </td>";
    $xlsContents .= "<td> ".$key['PrecoReparo']." </td>";
    $xlsContents .= "<td> ".$key['QualReparo']." </td>";
    $xlsContents .= "<td> ".$key['Atencao']." </td>";
    $xlsContents .= "<td> ".$key['Explicacao']." </td>";
    $xlsContents .= "<td> ".$key['Aspecto']." </td>";
    $xlsContents .= "<td> ".$key['Geral']." </td>";
    $xlsContents .= "<td> ".$key['NumDias']." </td>";
    $xlsContents .= "<td> ".$key['Recomend']." </td>";
    $xlsContents .= "</tr>";
  }
} else {
  foreach ($retornoSqlJsonXLS as $key) {
    $os_formatada =  str_replace(",", "",  number_format($key['OS']));

    $xlsContents .= "<tr bgcolor='$cor'>";
    $xlsContents .= "<td> ".$key['Pais']." </td>";
    $xlsContents .= "<td> ".$key['Cidade']." </td>";
    $xlsContents .= "<td> ".$key['Posto']." </td>";
    $xlsContents .= "<td> $os_formatada </td>";
    $xlsContents .= "<td> ".$key['Equip']." </td>";
    $xlsContents .= "<td> ".$key['Marca']." </td>";
    $xlsContents .= "<td> ".$key['Produto']." </td>";
    $xlsContents .= "<td> ".$key['OutroQual']." </td>";
    $xlsContents .= "<td> ".$key['RecomendNum']." </td>";
    $xlsContents .= "<td> ".$key['RazaoPont']." </td>";
    $xlsContents .= "<td> ".$key['ComplClass']." </td>";
    $xlsContents .= "<td> ".$key['TpReparo']." </td>";
    $xlsContents .= "<td> ".$key['PrecoReparo']." </td>";
    $xlsContents .= "<td> ".$key['QualReparo']." </td>";
    $xlsContents .= "<td> ".$key['Atencao']." </td>";
    $xlsContents .= "<td> ".$key['Explicacao']." </td>";
    $xlsContents .= "<td> ".$key['Aspecto']." </td>";
    $xlsContents .= "<td> ".$key['Geral']." </td>";
    $xlsContents .= "<td> ".$key['NumDias']." </td>";
    $xlsContents .= "<td> ".$key['Recomend']." </td>";
    $xlsContents .= "</tr>";
  }
}

  /*for ($i=0; $i < pg_num_rows($resPerguntasTipo); $i++) {
    $resultado_resposta = pg_fetch_result($resPerguntasTipo,$i,observacao);
    $resultado_resposta = json_decode(utf8_encode(stripslashes($resultado_resposta)));
    $xlsContents .= "<tr bgcolor='$cor'>";

    /*$cidade = "";
    $cidade = str_replace("+", " ", $resultado_resposta->cidade);
    if ($cidade == ""){ $cidade = " "; }*/

    /*if ($resultado_resposta->pais == "") { $resultado_resposta->pais = " "; }*/

   /* if ($resultado_resposta->posto == "") {
      $resultado_resposta->posto = " ";
    } else {
      $resultado_resposta->posto = str_replace("+", " ", $resultado_resposta->posto);
    }*/

    /*if ($resultado_resposta->os == "") {
      $resultado_resposta->os = " ";
    }*/

    /*if ($resultado_resposta->equipamento == "") {
      $resultado_resposta->equipamento = " ";
    } else {
      $resultado_resposta->equipamento = $language[$pesquisa_idioma][$resultado_resposta->equipamento];
    }*/

    /*if ($resultado_resposta->marca == "") {
      $resultado_resposta->marca = " ";
    } else {
      $sql = "SELECT marca, nome FROM tbl_marca WHERE fabrica = 1 AND marca = ".$resultado_resposta->marca;
      $resMarca = pg_query($con,$sql);
      if (pg_num_rows($resMarca)>0) {
          $resMarca = pg_fetch_array($resMarca);
          $resultado_resposta->marca = $resMarca['nome'];
      }
    }*/

    /*if ($resultado_resposta->produto == ""){
      $resultado_resposta->produto = " ";
    }

    if ($resultado_resposta->outro_qual == "") {
      $resultado_resposta->outro_qual = " ";
    }

    if ($resultado_resposta->recomendacao == "") {
      $resultado_resposta->recomendacao = " ";
    }

    if ($resultado_resposta->razao_pontuacao == "") {
      $resultado_resposta->razao_pontuacao = " ";
    } else {
      $resultado_resposta->razao_pontuacao = $language[$pesquisa_idioma][$resultado_resposta->razao_pontuacao];
    }

    if ($resultado_resposta->complemento_classificacao == "") {
      $resultado_resposta->complemento_classificacao = " ";
    } else {
      $resultado_resposta->complemento_classificacao = utf8_decode($resultado_resposta->complemento_classificacao);
    }

    if($resultado_resposta->nota_tempo_reparo == ""){
      $resultado_resposta->nota_tempo_reparo = " ";
    }else{
      $resultado_resposta->nota_tempo_reparo = $language[$pesquisa_idioma][$resultado_resposta->nota_tempo_reparo];
    }

    if($resultado_resposta->nota_preco_reparo == ""){
      $resultado_resposta->nota_preco_reparo = " ";
    }else{
      $resultado_resposta->nota_preco_reparo = $language[$pesquisa_idioma][$resultado_resposta->nota_preco_reparo];
    }

    if($resultado_resposta->nota_qualidade_reparo == ""){
      $resultado_resposta->nota_qualidade_reparo = " ";
    }else{
      $resultado_resposta->nota_qualidade_reparo = $language[$pesquisa_idioma][$resultado_resposta->nota_qualidade_reparo];
    }

    if($resultado_resposta->nota_atencao == ""){
      $resultado_resposta->nota_atencao = " ";
    }else{
      $resultado_resposta->nota_atencao = $language[$pesquisa_idioma][$resultado_resposta->nota_atencao];
    }

    if($resultado_resposta->nota_explicacao == ""){
      $resultado_resposta->nota_explicacao = " ";
    }else{
      $resultado_resposta->nota_explicacao = $language[$pesquisa_idioma][$resultado_resposta->nota_explicacao];
    }

    if($resultado_resposta->nota_aspecto == ""){
      $resultado_resposta->nota_aspecto = " ";
    }else{
      $resultado_resposta->nota_aspecto = $language[$pesquisa_idioma][$resultado_resposta->nota_aspecto];
    }

    if($resultado_resposta->nota_geral == ""){
      $resultado_resposta->nota_geral = " ";
    }else{
      $resultado_resposta->nota_geral = $language[$pesquisa_idioma][$resultado_resposta->nota_geral];
    }

    if($resultado_resposta->numero_dias == ""){
      $resultado_resposta->numero_dias = " ";
    }else if($resultado_resposta->numero_dias == "mais"){
      $resultado_resposta->numero_dias = "Mais de 8";
    }

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

    $resultado_resposta->recomendacao = (int)$resultado_resposta->recomendacao;

    if ($resultado_resposta->recomendacao < 7) {
      $xlsContents .= "<td> Detractor </td>";
    } else if ($resultado_resposta->recomendacao < 9) {
      $xlsContents .= "<td> Neutro </td>";
    } else {
      $xlsContents .= "<td> Promotor </td>";
    }
    $xlsContents .= "</tr>";
  }*/
} else {
  $xlsContents .= "<td></td>";
}

fputs($fp,$xlsContents);

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
