<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

if(isset($_GET['gera_excel_pesquisa_opiniao'])){


    $sqlExcel = 'SELECT opr.data_resposta,opr.posto FROM tbl_opiniao_posto_resposta opr JOIN tbl_opiniao_posto_pergunta opp ON opr.opiniao_posto_pergunta = opp.opiniao_posto_pergunta JOIN tbl_opiniao_posto op ON opp.opiniao_posto = op.opiniao_posto JOIN tbl_posto_fabrica pfab ON pfab.posto = opr.posto WHERE op.fabrica = '.$login_fabrica.' AND pfab.fabrica = '.$login_fabrica.' GROUP BY opr.posto, opr.data_resposta ORDER BY opr.data_resposta';

    $resPesquisa = pg_query($con, $sqlExcel);

    $count = pg_num_rows($resPesquisa);
    $postosQueResponderam = pg_fetch_all($resPesquisa);


    if($count == 0){
        echo "NODATA";
        exit;
    }

    $data = date("d-m-Y-H:i");
    $arquivo_nome = "relatorio-pesquisa-postos-$data.xls";
    $path = "xls/";
    $path_tmp = "/tmp/";
    $arquivo_completo = $path.$arquivo_nome;
    $arquivo_completo_tmp = $path_tmp.$arquivo_nome;
    $fp = fopen($arquivo_completo_tmp,"w");


    $thead = "<table border='1'>
            <thead>
            <tr>
                <th>Data da Pesquisa</th>
                <th>Código</th>
                <th>Nome do Posto</th>
                <th>Cidade</th>
                <th>Estado</th>
                <th>Resposta 1</th>
                <th>Resposta 2</th>
                <th>Resposta 3</th>
                <th>Resposta 4</th>
            </tr>
            </thead>
            <tbody>";

    fwrite($fp,$thead);



    foreach($postosQueResponderam as $posto){
        $sql = 'select pf.codigo_posto,p.nome,p.cidade,p.estado from tbl_posto p join tbl_posto_fabrica pf on p.posto = pf.posto where p.posto = '.$posto['posto'].' and pf.fabrica = '.$login_fabrica.';';
        $resPosto = pg_query($con,$sql);
        $dadosPosto = pg_fetch_all($resPosto);


        $linhaExcel = '<tr>';
        $linhaExcel .= '<td>'.$posto['data_resposta'].'</td>';
        $linhaExcel .= '<td>'.$dadosPosto[0]['codigo_posto'].'</td>';
        $linhaExcel .= '<td>'.$dadosPosto[0]['nome'].'</td>';
        $linhaExcel .= '<td>'.$dadosPosto[0]['cidade'].'</td>';
        $linhaExcel .= '<td>'.$dadosPosto[0]['estado'].'</td>';


        $sqlExcel = 'SELECT opr.resposta FROM tbl_opiniao_posto_resposta opr JOIN tbl_opiniao_posto_pergunta opp ON opr.opiniao_posto_pergunta = opp.opiniao_posto_pergunta JOIN tbl_opiniao_posto op ON opp.opiniao_posto = op.opiniao_posto JOIN tbl_posto_fabrica pfab ON pfab     .posto = opr.posto WHERE op.fabrica = '.$login_fabrica.' AND pfab.fabrica = '.$login_fabrica.' AND opr.posto = '.$posto['posto'].' ORDER BY opp.ordem';
        $resExcel = pg_query($con,$sqlExcel);
        $respostas = pg_fetch_all($resExcel);

        $blankResposta = false;
        foreach($respostas as $td){
            if($td['resposta'] == 't'){
                $td['resposta'] = 'Sim';
            }
            if($td['resposta'] == 'f'){
                $td['resposta'] = 'Não';
            }

            if($blankResposta == true){
                $td['resposta'] = "";
            }
            $linhaExcel  .= "<td>".$td['resposta']."</td>";
            if($td['resposta'] == 'Não'){
                $blankResposta = true;
            }
        }
        $linhaExcel .= "</tr>";
        fwrite($fp,$linhaExcel);
    }

    $rodape = "</tbody>
        </table>";
    fwrite($fp,$rodape);

    fclose($fp);
    if (file_exists($arquivo_completo_tmp)) {
        system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
        echo $arquivo_completo;
    }

    exit;
}

if(isset($_GET['gera_excel_pesquisa_cadastral'])){
  $posto_pesquisa = $_GET['posto_pesquisa'];

  if(strlen($posto_pesquisa) > 0){
    $cond_posto = "AND tbl_resposta.posto = $posto_pesquisa";
  }

  $sqlC = "SELECT tbl_pergunta.descricao AS descricao_pergunta
          FROM    tbl_pesquisa_pergunta
          JOIN    tbl_pergunta        USING(pergunta)
          JOIN    tbl_pesquisa        USING(pesquisa)
          LEFT JOIN    tbl_tipo_resposta   ON tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta
          WHERE   tbl_pesquisa.categoria   = 'atualizacao_cadastral'
          AND     tbl_pesquisa.fabrica    = $login_fabrica
          AND     tbl_pergunta.ativo      IS TRUE
          ORDER BY      tbl_pesquisa_pergunta.ordem";
  $resC = pg_query($con, $sqlC);

  if(pg_num_rows($resC) > 0){
    $data = date("d-m-Y-H:i");
    $arquivo_nome = "relatorio-pesquisa-postos-$data.xls";
    $path = "xls/";
    $path_tmp = "/tmp/";
    $arquivo_completo = $path.$arquivo_nome;
    $arquivo_completo_tmp = $path_tmp.$arquivo_nome;
    $fp = fopen($arquivo_completo_tmp,"w");

    $thead = "<table border='1'>
                <thead>
                  <tr>
                  <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código Posto</th>
                  <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
              ";
                    for ($i=0; $i < $resC; $i++) {
                      $descricao_pergunta = pg_fetch_result($resC, $i, 'descricao_pergunta');
                      $thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>$descricao_pergunta</th>";
                    }

        $thead .="</tr>
                </thead>
                <tbody>
              ";
    fwrite($fp,$thead);

    $sqlCR = "SELECT tbl_resposta.txt_resposta,
                    tbl_resposta.pergunta,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_resposta.posto
                FROM    tbl_resposta
                JOIN    tbl_pesquisa using (pesquisa)
                JOIN    tbl_posto ON tbl_posto.posto = tbl_resposta.posto
                JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                WHERE   tbl_pesquisa.categoria = 'atualizacao_cadastral'
                $cond_posto
                ORDER BY      tbl_resposta.posto,tbl_resposta.pergunta";
    $resCR = pg_query($con, $sqlCR);

    if(pg_num_rows($resCR) > 0){

      $contador = 1;
      for ($x=0; $x < pg_num_rows($resCR); $x++) {
        $resposta = pg_fetch_result($resCR, $x, 'txt_resposta');
        $codigo_posto = pg_fetch_result($resCR, $x, 'codigo_posto');
        $posto_nome = pg_fetch_result($resCR, $x, 'nome');
        $posto_id = pg_fetch_result($resCR, $x, 'posto');

        if($contador % 6 == 0){
          $tbody .="</tr>";
          unset($contador);
          $contador = 1;
        }
        if($contador == 1){
          $tbody .= "<tr><td nowrap align='left' valign='top' >$codigo_posto</td>";
          $tbody .= "<td nowrap align='left' valign='top' >$posto_nome</td>";
        }
        $tbody .="<td nowrap align='left' valign='top' >$resposta</td>";
        $contador++;
      }
      $tbody .="</tbody></table>";
      fwrite($fp,$tbody);
    }

  }
  fclose($fp);
    if (file_exists($arquivo_completo_tmp)) {
        system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
        echo $arquivo_completo;
    }
  exit;

}

if (isset($_POST)) {

    //RECEBE PARAMETROS PARA PESQUISA
    $data_inicial    = $_POST['data_inicial'];
    $data_final      = $_POST['data_final'];
    $codigo_posto    = trim($_POST['codigo_posto']);
    $posto_nome      = trim($_POST['posto_nome']);
    $posto_local     = trim($_POST['posto_local']);
    $posto_linha     = trim($_POST['posto_linha']);
    $posto_estado    = trim($_POST['posto_estado']);
    //$pesquisa_idioma = trim($_POST['pesquisa_idioma']);
    $pesquisa_os   = trim($_POST['pesquisa_os']);
    $posto           = $_POST['posto'];
    $hd_chamado      = $_POST['hd_chamado'];
    $pesquisa      = ($_POST['pesquisa'] <> 'TODOS') ? $_POST['pesquisa'] : '' ;

    list($di, $mi, $yi) = explode("/", $data_inicial);

    list($df, $mf, $yf) = explode("/", $data_final);

    $aux_data_inicial = "$yi-$mi-$di 00:00:00";
    $aux_data_final   = "$yf-$mf-$df 23:59:59";

  // Monteiro //
    if(!empty($_POST['paises'])) {

        #### PESQUISA POR PAISES ####

        $paises_list = implode(',', $_POST['paises']);

        $conteudo = implode(',', $_POST['paises']);
    }
  // Fim Monteiro //

    if($pesquisa == "america_latina"){
        #### PESQUISA POR PAISES ####
        $pesquisa_grafico = '%pais%';
        $pesquisa_language = implode("','", $_POST['paises']);
//         echo $pesquisa_language;exit;
        $qtde_paises = count($_POST['paises']);

        if($qtde_paises > 1){
            $pesquisa_idioma = "es";
        } else {
            $sql_l = "
                SELECT  tbl_laudo_tecnico_os.os,
                        JSON_FIELD('language',tbl_laudo_tecnico_os.observacao) AS pesquisa_idioma
                FROM    tbl_os
                JOIN    tbl_laudo_tecnico_os    ON  tbl_laudo_tecnico_os.os     = tbl_os.os
                JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                                AND tbl_posto_fabrica.fabrica   = $login_fabrica
                WHERE   tbl_laudo_tecnico_os.data BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                AND     JSON_FIELD('pais',tbl_laudo_tecnico_os.observacao) IN ('$pesquisa_language')
                AND     titulo ILIKE 'Pesquisa de%'
          ORDER BY      tbl_laudo_tecnico_os.data;";


//                      echo nl2br($sql_l);exit;
            $res_l = pg_query($con,$sql_l);

            if(pg_num_rows($res_l) > 0){

                $pesquisa_idioma = pg_fetch_result($res_l, 0, 'pesquisa_idioma');
            }
        }

        #### FIM PESQUISA POR PAISES ####

    } else {
        $pesquisa_grafico = '%"language"%';
        $pesquisa_language = '%"pt"%';
        $pesquisa_idioma = 'pt';
    }

    if(!empty($codigo_posto)) {
        $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica and codigo_posto ='$codigo_posto'";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0) {
            $posto = pg_fetch_result($res,0,posto);
        }
    }

    if(!in_array($login_fabrica,array(1,85,94,129,145))){
        $conditionPesquisa = (!empty($pesquisa)) ? " AND tbl_pesquisa.pesquisa = $pesquisa " : '' ;
        $conditionPosto = (!empty($posto)) ? " AND tbl_hd_chamado_extra.posto = $posto " : '' ;
    } else {
        $conditionPesquisa  = (!empty($pesquisa)) ? " AND tbl_pesquisa.categoria = '$pesquisa' " : '' ;
        $conditionPosto     = (!empty($posto)) ? " AND tbl_os.posto = $posto " : '';
        $conditionLinha     = (!empty($posto_linha)) ? " AND tbl_posto_linha.linha = '$posto_linha' " : '';
        $conditionChamado   = (!empty($hd_chamado)) ? " AND tbl_hd_chamado.hd_chamado = '$hd_chamado' " : '';
        $conditionLocal     = (!empty($posto_local)) ? " AND UPPER(fn_retira_especiais(tbl_posto.cidade)) = UPPER('$posto_local') " : '';

        if($pesquisa == "externo_email"){
            $pesquisa_idioma = "pt";
        }

        if(in_array($pesquisa_idioma, array("pt", "es")) || $pesquisa_idioma == "todos_idiomas"){
            $language['pt']['questao_cinco'] = 'Equipamento';
            $language['pt']['questao_cinco_grafico'] = utf8_encode('Equipamento');

            $language['pt']['martelo'] = 'Martelo';
            $language['pt']['furadeira'] = 'Furadeira/Furadeira sem fio';
            $language['pt']['mecanica'] = 'Metal - Mecânica';
            $language['pt']['madeira'] = 'Madeira';
            $language['pt']['serras'] = 'Serras';
            $language['pt']['jardinagem'] = 'Jardinagem';
            $language['pt']['gasolina'] = 'Gasolina';
            $language['pt']['pneumatica'] = 'Pneumática';
            $language['pt']['outro'] = 'Outro';

            /* FORA DE USO - HD 2479066 */
            $language['pt']['concreto'] = 'Concreto';
            $language['pt']['marmore'] = 'Serras - Marmore';
            $language['pt']['sem_fio'] = 'Sem fio';
            $language['pt']['impacto'] = 'Furadeira de Impacto';
            $language['pt']['parafusadeira'] = 'Parafusadeira';
            /******************************************/

            $language['pt']['questao_um']     = 'Selecione seu país';
            $language['pt']['questao_dois']   = 'Selecione sua cidade';
            $language['pt']['questao_tres']   = 'Assistência Técnica';
            $language['pt']['questao_quatro'] = 'Ordem de Serviço';

            $language['pt']['questao_oito'] = 'Você quer expandir sua pontuação?';

            $language['pt']['questao_marca']   = 'Marca';
            $language['pt']['questao_produto'] = 'Produto';
            $language['pt']['questao_qual']    = 'Qual';

            $language['pt']['questao_seis'] = 'Com base na experiência com o reparo do seu produto você recomendaria a Stanley Black and Decker aos seus colegas, familiares ou amigos?';
            $language['pt']['questao_seis_grafico'] = utf8_encode('Com base na experiência com o reparo do seu produto você recomendaria a Stanley Black and Decker aos seus colegas, familiares ou amigos?');

            $language['pt']['respota_0'] = '0';
            $language['pt']['respota_1'] = '1';
            $language['pt']['respota_2'] = '2';
            $language['pt']['respota_3'] = '3';
            $language['pt']['respota_4'] = '4';
            $language['pt']['respota_5'] = '5';
            $language['pt']['respota_6'] = '6';
            $language['pt']['respota_7'] = '7';
            $language['pt']['respota_8'] = '8';
            $language['pt']['respota_9'] = '9';
            $language['pt']['respota_10'] = '10';

            $language['pt']['questao_sete'] = 'Selecione a principal razão para a sua pontuação';
            $language['pt']['questao_sete_grafico'] = utf8_encode('Selecione a principal razão para a sua pontuação');

            $language['pt']['servico_assistencia'] = 'Serviço da assistência técnica';
            $language['pt']['suporte']             = 'Suporte e atenção';
            $language['pt']['tempo_resposta']      = 'Tempo de resposta';
            $language['pt']['padrao_servico']      = 'Padrões de qualidade e serviço';
            $language['pt']['qualidade_reparo']    = 'Qualidade de reparação';
            $language['pt']['tempo_reparo']        = 'Tempo de reparação';
            $language['pt']['falha_equipamento']   = 'Falha precoce do equipamento';
            $language['pt']['qualidade_produto']   = 'Qualidade do produto';
            $language['pt']['atencao_suporte']     = 'Atenção de Suporte ( Via telefone )';
            $language['pt']['orcamento']           = 'Orçamento de custos e / ou reparação';
            $language['pt']['acompanhamento']      = 'Acompanhamento de reparo';

            // NOVAS RESPOSTAS DA QUESTÃO 7 - DISPONÍVEL A PARTIR DE 2016
            $language['pt']['atencao_suporte_telefonico'] = 'Atenção e suporte (Telefônico)';
            $language['pt']['atencao_suporte_recepcao']   = 'Atenção e suporte (Recepção da assistência técnica)';
            $language['pt']['falha_precoce_ferramenta']   = 'Falha precoce da ferramenta';
            $language['pt']['qualidade_do_produto']       = 'A qualidade do produto';
            $language['pt']['tempo_de_resposta']          = 'Tempo de resposta';
            $language['pt']['custo_orcamento_reparo']     = 'Custo e/ou orçamento do reparo';
            $language['pt']['rastreamento_reparacao']     = 'Rastreamento de reparação';
            $language['pt']['tempo_repado']               = 'Tempo de reparo';
            $language['pt']['qualidade_reparacao']        = 'Qualidade da reparação';
            $language['pt']['servico_prestado_centro']    = 'Serviço prestado pelo centro de serviço';

            $language['pt']['questao_nove']   = 'Tempo de reparo';
            $language['pt']['questao_dez']    = 'Preço do reparo';
            $language['pt']['questao_onze']   = 'Qualidade do reparo';
            $language['pt']['questao_doze']   = 'Atenção do atendente';
            $language['pt']['questao_treze']  = 'Explicação do reparo';
            $language['pt']['questao_quartoze'] = 'Aspecto visual da Assistência';
            $language['pt']['questao_quinze']   = 'Satisfação geral';

            $language['pt']['questao_nove_grafico']   = utf8_encode('Tempo de reparo');
            $language['pt']['questao_dez_grafico']    = utf8_encode('Preço do reparo');
            $language['pt']['questao_onze_grafico']   = utf8_encode('Qualidade do reparo');
            $language['pt']['questao_doze_grafico']   = utf8_encode('Atenção do atendente');
            $language['pt']['questao_treze_grafico']  = utf8_encode('Explicação do reparo');
            $language['pt']['questao_quartoze_grafico'] = utf8_encode('Aspecto visual da Assistência');
            $language['pt']['questao_quinze_grafico']   = utf8_encode('Satisfação geral');

            $language['pt']['plenamente_satisfeito'] = 'Totalmente Satisfeito';
            $language['pt']['muito_satisfeito']      = 'Bastante Satisfeito';
            $language['pt']['satisfeito']        = 'Neutro';
            $language['pt']['pouco_satisfeito']    = 'Pouco Satisfeito';
            $language['pt']['insatisfeito']      = 'Nada Satisfeito';

            $language['pt']['questao_numero'] = 'Numero de dias na assistência';
            $language['pt']['questao_numero_grafico'] = utf8_encode('Numero de dias na assistência');

            $language['pt']['resposta_numero_1'] = '1';
            $language['pt']['resposta_numero_2'] = '2';
            $language['pt']['resposta_numero_3'] = '3';
            $language['pt']['resposta_numero_4'] = '4';
            $language['pt']['resposta_numero_5'] = '5';
            $language['pt']['resposta_numero_6'] = '6';
            $language['pt']['resposta_numero_7'] = '7';
            $language['pt']['resposta_numero_8'] = '8';
            $language['pt']['resposta_numero_9'] = 'mais 8';
        }
        if($pesquisa_idioma == "en" || $pesquisa_idioma == "todos_idiomas"){
            $language['en']['questao_cinco'] = 'Equipment';
            $language['en']['hammer'] = 'Hammer';
            $language['en']['drill_cordless'] = 'Drill / Cordless';
            $language['en']['metalworking'] = 'Metalworking';
            $language['en']['woodworking'] = 'Woodworking';
            $language['en']['saws'] = 'Saws';
            $language['en']['lawn_garden'] = 'Lawn + Garden';
            $language['en']['gasoline_explosion'] = 'Gasoline / Explosion';
            $language['en']['pneumatic'] = 'Pneumatic';
            $language['en']['other'] = 'Other';

            /* FORA DE USO - HD 2479066 */
            $language['en']['machinery'] = 'Machinery';
            $language['en']['cordless'] = 'Cordless';
            $language['en']['drill'] = 'Drill';
            /******************************************/

            $language['en']['questao_um']     = 'Select your country';
            $language['en']['questao_dois']   = 'Select your city';
            $language['en']['questao_tres']   = 'Technical Assistance';
            $language['en']['questao_quatro'] = 'Service Order';

            $language['en']['questao_oito'] = 'Do you want to expand your score?';

            $language['en']['questao_marca']   = 'Brand';
            $language['en']['questao_produto'] = 'Product';
            $language['en']['questao_qual']    = 'Which';

            $language['en']['questao_seis'] = 'Based in your customer service experience, would you recommend Stanley Black & Decker to your family and friends?';

            $language['en']['respota_0'] = '0';
            $language['en']['respota_1'] = '1';
            $language['en']['respota_2'] = '2';
            $language['en']['respota_3'] = '3';
            $language['en']['respota_4'] = '4';
            $language['en']['respota_5'] = '5';
            $language['en']['respota_6'] = '6';
            $language['en']['respota_7'] = '7';
            $language['en']['respota_8'] = '8';
            $language['en']['respota_9'] = '9';
            $language['en']['respota_10'] = '10';

            $language['en']['questao_sete'] = 'Select the main reason for your score';

            $language['en']['servico_assistencia'] = 'Technical assistance service';
            $language['en']['suporte']             = 'Support and attention';
            $language['en']['tempo_resposta']      = 'Response Time';
            $language['en']['padrao_servico']      = 'Quality standards and service';
            $language['en']['qualidade_reparo']    = 'Repair quality';
            $language['en']['tempo_reparo']        = 'Repair time';
            $language['en']['falha_equipamento']   = 'Early equipment failure';
            $language['en']['qualidade_produto']   = 'Product Quality';
            $language['en']['atencao_suporte']     = 'Support attention (via phone)';
            $language['en']['orcamento']           = 'Cost budget and / or repair';
            $language['en']['acompanhamento']      = 'Monitoring repair';

            // NOVAS RESPOSTAS DA QUESTÃO 7 - DISPONÍVEL A PARTIR DE 2016
            $language['en']['atencao_suporte_telefonico'] = 'Support and care (Telephone)';
            $language['en']['atencao_suporte_recepcao']   = 'Support and care (Technical Assistance)';
            $language['en']['falha_precoce_ferramenta']   = 'Premature tool failure';
            $language['en']['qualidade_do_produto']       = 'Product quality';
            $language['en']['tempo_de_resposta']          = 'Lead time';
            $language['en']['custo_orcamento_reparo']     = 'Cost and/or budget repair';
            $language['en']['rastreamento_reparacao']     = 'Repair tracking';
            $language['en']['tempo_repado']               = 'Repair time';
            $language['en']['qualidade_reparacao']        = 'Repair quality';
            $language['en']['servico_prestado_centro']    = 'Technical Assistance Service';

            $language['en']['questao_nove']   = 'Repair time';
            $language['en']['questao_dez']    = 'Repair price';
            $language['en']['questao_onze']   = 'Repair quality';
            $language['en']['questao_doze']   = "Attendant's attention";
            $language['en']['questao_treze']  = "Repair's explanation";
            $language['en']['questao_quartoze'] = 'Visual aspect Assistance';
            $language['en']['questao_quinze']   = 'Overall satisfaction';

            $language['en']['plenamente_satisfeito'] = 'Fully Satisfied';
            $language['en']['muito_satisfeito']      = 'Very Satisfied';
            $language['en']['satisfeito']        = 'Neutral';
            $language['en']['pouco_satisfeito']    = 'Shortly Satisfied';
            $language['en']['insatisfeito']      = 'Unfulfilled';

            $language['en']['questao_numero'] = 'Number of days in attendance';

            $language['en']['resposta_numero_1'] = '1';
            $language['en']['resposta_numero_2'] = '2';
            $language['en']['resposta_numero_3'] = '3';
            $language['en']['resposta_numero_4'] = '4';
            $language['en']['resposta_numero_5'] = '5';
            $language['en']['resposta_numero_6'] = '6';
            $language['en']['resposta_numero_7'] = '7';
            $language['en']['resposta_numero_8'] = '8';
            $language['en']['resposta_numero_9'] = 'more 8';
        }
        if($pesquisa_idioma == "es" || $pesquisa_idioma == "todos_idiomas"){
            $language['es']['questao_cinco'] = 'Equipo que se reparó';
            $language['es']['questao_cinco_grafico'] = utf8_encode('Equipo que se reparó');

            $language['es']['martillos']            = 'Martillos';
            $language['es']['inalambrico']          = utf8_encode('Taladros / H. Inalámbricas');
            $language['es']['metalmecanica']        = utf8_encode('Metalmecánica');
            $language['es']['madera']               = 'Madera';
            $language['es']['estacionaria']         = 'H. Estacionaria';
            $language['es']['jardin']               = utf8_encode('Jardín');
            $language['es']['gasolina_explosion']   = utf8_encode('Gasolina / Explosión');
            $language['es']['neumatica']            = utf8_encode('Neumática');
            $language['es']['other']                = 'Otra';

            $language['es']['questao_um']     = 'Selecciona tu pais';
            $language['es']['questao_dois']   = 'Selecciona ciudad';
            $language['es']['questao_tres']   = 'Centro de Servicio';
            $language['es']['questao_quatro'] = 'Orden de Reparación';

            $language['es']['questao_oito'] = 'Quieres ampliar tu calificación?';

            $language['es']['questao_marca']   = 'Marca';
            $language['es']['questao_produto'] = 'Producto';
            $language['es']['questao_qual']    = 'Cual';

            $language['es']['questao_seis'] = 'Basado en la experiencia con la reparación de tu producto que tan probable es que recomiendes Stanley Black and Decker a tus colegas, familiares o amigos?';
            $language['es']['questao_seis_grafico'] = utf8_encode('Basado en la experiencia con la reparación de tu producto que tan probable es que recomiendes Stanley Black and Decker a tus colegas, familiares o amigos?');

            $language['es']['respota_0'] = '0';
            $language['es']['respota_1'] = '1';
            $language['es']['respota_2'] = '2';
            $language['es']['respota_3'] = '3';
            $language['es']['respota_4'] = '4';
            $language['es']['respota_5'] = '5';
            $language['es']['respota_6'] = '6';
            $language['es']['respota_7'] = '7';
            $language['es']['respota_8'] = '8';
            $language['es']['respota_9'] = '9';
            $language['es']['respota_10'] = '10';

            $language['es']['questao_sete'] = 'Selecciona el principal motivo de tu calificación';
            $language['es']['questao_sete_grafico'] = utf8_encode('Selecciona el principal motivo de tu calificación');

            $language['es']['servico_assistencia'] = utf8_encode('Servicio del centro de raparación');
            $language['es']['suporte']             = utf8_encode('Soporte y atención (Recepción)');
            $language['es']['tempo_resposta']      = utf8_encode('Tiempo de respuesta');
            $language['es']['padrao_servico']      = utf8_encode('Estándares de calidad y servicio');
            $language['es']['qualidade_reparo']    = utf8_encode('Calidade de la reparación');
            $language['es']['tempo_reparo']        = utf8_encode('Tiempo de reparación');
            $language['es']['falha_equipamento']   = utf8_encode('Falla Temprana del equipo');
            $language['es']['qualidade_produto']   = utf8_encode('Calidade del producto');
            $language['es']['atencao_suporte']     = utf8_encode('Atención y soporte');
            $language['es']['orcamento']           = utf8_encode('Costo y/ó presupuesto de reparación');
            $language['es']['acompanhamento']      = utf8_encode('Seguimiento de la reparación');

            // NOVAS RESPOSTAS DA QUESTÃO 7 - DISPONÍVEL A PARTIR DE 2016
            $language['es']['atencao_suporte_telefonico'] = utf8_encode('Atención y soporte (Telefónica)');
            $language['es']['atencao_suporte_recepcao']   = utf8_encode('Atención y soporte (Recepción del centro de servicio)');
            $language['es']['falha_precoce_ferramenta']   = utf8_encode('Falla temprana de la herramienta');
            $language['es']['qualidade_do_produto']       = utf8_encode('Calidad del producto');
            $language['es']['tempo_de_resposta']          = utf8_encode('Tiempo de respuesta');
            $language['es']['custo_orcamento_reparo']     = utf8_encode('Costo y/ó presupuesto de reparación');
            $language['es']['rastreamento_reparacao']     = utf8_encode('Seguimiento de la reparación');
            $language['es']['tempo_repado']               = utf8_encode('Tiempo de reparación');
            $language['es']['qualidade_reparacao']        = utf8_encode('Calidad de la reparación');
            $language['es']['servico_prestado_centro']    = utf8_encode('Servicio prestado por el centro de servicio');

            $language['es']['questao_nove']     = 'Tiempo de la reparación';
            $language['es']['questao_dez']      = 'Precio de la reparación';
            $language['es']['questao_onze']     = 'Calidad de la reparación';
            $language['es']['questao_doze']     = 'Actitud del personal';
            $language['es']['questao_treze']    = 'Explicación de la reparación';
            $language['es']['questao_quartoze'] = 'Aspecto de las instalaciones de servicio';
            $language['es']['questao_quinze']   = 'Satisfacción General';

            $language['es']['questao_nove_grafico']     = utf8_encode('Tiempo de la reparación');
            $language['es']['questao_dez_grafico']      = utf8_encode('Precio de la reparación');
            $language['es']['questao_onze_grafico']     = utf8_encode('Calidad de la reparación');
            $language['es']['questao_doze_grafico']     = utf8_encode('Actitud del personal');
            $language['es']['questao_treze_grafico']    = utf8_encode('Explicación de la reparación');
            $language['es']['questao_quartoze_grafico'] = utf8_encode('Aspecto de las instalaciones de servicio');
            $language['es']['questao_quinze_grafico']   = utf8_encode('Satisfacción General');

            $language['es']['plenamente_satisfeito'] = 'Totalmente satisfecho';
            $language['es']['muito_satisfeito']      = 'Bastante Satisfecho';
            $language['es']['satisfeito']        = 'Neutral';
            $language['es']['pouco_satisfeito']    = 'Poco Satisfecho';
            $language['es']['insatisfeito']      = 'Nada Satisfecho';

            $language['es']['questao_numero'] = 'Numero del dias en lo centro de servicio';
            $language['es']['questao_numero_grafico'] = utf8_encode('Numero del dias en lo centro de servicio');

            $language['es']['resposta_numero_1'] = '1';
            $language['es']['resposta_numero_2'] = '2';
            $language['es']['resposta_numero_3'] = '3';
            $language['es']['resposta_numero_4'] = '4';
            $language['es']['resposta_numero_5'] = '5';
            $language['es']['resposta_numero_6'] = '6';
            $language['es']['resposta_numero_7'] = '7';
            $language['es']['resposta_numero_8'] = '8';
            $language['es']['resposta_numero_9'] = utf8_encode('más 8');
        }
    }
}

// echo "<pre>";
// print_r($language['es']);exit;

$layout_menu = 'callcenter';
$title = "PESQUISA DE PERGUNTAS DO CALLCENTER";
include 'cabecalho.php';
?>

<style type="text/css">
  @import "../plugins/jquery/datepick/telecontrol.datepick.css";
  .formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
    margin:auto;
    width:700px;
  }

  .msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    width:700px;
    margin:auto;
    text-align:center;
  }

  .sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    width:700px;
    margin:auto;
    text-align:center;
  }

  .titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
  }

  .titulo_coluna{
    background-color:#596d9b !important;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
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

  .hideTr{
    display:none;
  }

  .ms-parent{
    width: 200px !important;
  }

  #ms{
    border-radius: 0px !important;
    height: 15px !important;
  }

  .ms-choice {
      border-radius: 0px !important;
      border-color: #888 !important;
      border-style: solid;
      border-width: 1px !important;
      background-color:#F0F0F0 !important;
      height: 18px !important;
  }
</style>

<script src="js/jquery-1.8.3.min.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src="js/highcharts_4.2.3.js"></script>
<script src="js/exporting.js"></script>

<?php include("plugin_loader.php"); ?>
<link rel="stylesheet" href="css/multiple-select.css" />
<script src="js/jquery.multiple.select.js"></script>
<script type="text/javascript">

  $(function() {
    $('#data_inicial').datepick({startDate:'01/01/2000'});
    $('#data_final').datepick({startDate:'01/01/2000'});
    $("#data_inicial").maskedinput("99/99/9999");
    $("#data_final").maskedinput("99/99/9999");




    <?php if($login_fabrica == 1){ ?>
        $('#pesquisa_idioma').hide();

        var america_latina = $("#combo_pais").val();
        if(america_latina == "america_latina"){
          $('#pesquisa_idioma').show();
        }

        // $('.radio_pesquisa').click(function() {
        //     var tipo_pesquisa = $(this).val();
        //     if(tipo_pesquisa == 'pesquisa'){
        //       $("#btn_pesquisa").hide();
        //       $("#btn_gerar_excel_pesquisa").val('Pesquisar');
        //       $("#btn_gerar_excel_pesquisa").show();
        //     }else{
        //       $("#btn_pesquisa").show();
        //       $("#btn_gerar_excel_pesquisa").hide();
        //     }
        // });

    <?php } ?>

    <?
    if(in_array($login_fabrica,array(85,94))){
      ?>
      $("input:radio").on("click",function(){
        var valor = $(this).val();
        if(valor == "posto"){
          $(".dados_pesquisa_posto").css("display","table-row");
          $(".dados_pesquisa_hd").css("display","none");
          $("input[id=hd_chamado]").val("");
        }else if(valor == "callcenter" || valor == "externo"){
          $(".dados_pesquisa_posto").css("display","none");
          $(".dados_pesquisa_hd").css("display","table-row");
          $("input[id=codigo_posto]").val("");
          $("input[id=posto_nome]").val("");
          $("input[id=posto_linha]").val("");
          $("input[id=posto_local]").val("");
          $("input[id=posto_estado]").val("");
        }else{
          $(".dados_pesquisa_posto").css("display","none");
          $(".dados_pesquisa_hd").css("display","none");
          $("input[id=hd_chamado]").val("");
          $("input[id=codigo_posto]").val("");
          $("input[id=posto_nome]").val("");
          $("input[id=posto_linha]").val("");
          $("input[id=posto_local]").val("");
          $("input[id=posto_estado]").val("");
        }
      });
      <?
    }
    ?>
    /* Busca AutoComplete pelo Código */
    $("#codigo_posto").autocomplete("relatorio_pesquisas_chamado_ajax.php?ajax=true&tipo_busca=posto&busca=codigo", {
      minChars: 3,
      delay: 150,
      width: 350,
      matchContains: true,
      formatItem: formatItem,
      formatResult: function(row) {return row[2];}
    });

    $("#codigo_posto").result(function(event, data, formatted) {
      $("#posto_nome").val(data[1]) ;
      $("#posto").val(data[3]) ;
    });

    /* Busca AutoComplete pelo Nome */
    $("#posto_nome").autocomplete("relatorio_pesquisas_chamado_ajax.php?ajax=true&tipo_busca=posto&busca=nome", {
      minChars: 3,
      delay: 150,
      width: 350,
      matchContains: true,
      formatItem: formatItem,
      formatResult: function(row) {return row[1];}
    });

    $("#posto_nome").result(function(event, data, formatted) {
      $("#codigo_posto").val(data[2]) ;
      $("#posto").val(data[3]) ;
    });

    /*BUSCA POR AUTOCOMPLETE DAS CIDADES COM POSTOS ATENDENTES*/
    $("#posto_local").autocomplete("relatorio_pesquisas_chamado_ajax.php?ajax=true&tipo_busca=cidade_posto&busca=cidade", {
      minChars: 3,
      delay: 150,
      width: 350,
      matchContains: true,
      formatItem: formatItemLocal,
      formatResult: function(row) {return row[1];}
    });

    $("#posto_local").result(function(event, data, formatted) {
      $("#posto_local").val(data[0]) ;
      $("#posto_estado").val(data[1]) ;
    });

    //ENVIA PARA O PROGRAMA DO AJAX VALIDAR O FORM
    $('#btn_pesquisa').click(function(){

      $.ajax({

        type: "GET",
        url: "relatorio_pesquisas_chamado_ajax.php",
        data: "ajax=true&validar=true&"+$('form[name=frm_pesquisa]').find('input').serialize(),
        complete: function(http) {

          results = http.responseText;
          results = results.split('|');
          if (results[0] == 1){

            $('div.msg_erro').html(results[1]);

          }else{
            $('form[name=frm_pesquisa]').submit();
          }
        }

      });

    });

<?php
if($login_fabrica == 1){
    ?>
    $("#btn_gerar_excel_pesquisa").click(function(){
        $.ajax({
            type: "GET",
            url: "relatorio_pesquisas_chamado_new_black.php",
            data: {
                gera_excel_pesquisa_opiniao: true
            }
        }).done(function(data){
            if(data == 'NODATA'){
                alert("Nenhuma pesquisa foi respondida ainda");
            }else{
                window.location = "./"+data;
            }
        });
    });

    $("#btn_gerar_excel_pesquisa_cadastral").click(function(){
        var posto_pesquisa = $("#posto").val();
        $.ajax({
            type: "GET",
            url: "relatorio_pesquisas_chamado_new_black.php",
            data: {
                gera_excel_pesquisa_cadastral: true,
                posto_pesquisa: posto_pesquisa
            }
        }).done(function(data){
            if(data == 'NODATA'){
                alert("Nenhuma pesquisa foi respondida ainda");
            }else{
                window.location = "./"+data;
            }
        });
    });


<?php
}
?>

    //EXIBE RESPOSTA
    $('.btn_ver_resposta').click(function(){
      relBtn      = $(this).attr('rel');
      quebraRel   = relBtn.split("|");

      if ($('tr#'+quebraRel[0]).hasClass('hideTr')){
        $.ajax({
          type:"GET",
          url:"relatorio_pesquisas_chamado_ajax.php",
          dataType:"html",
          data:{
            ajax:true,
            ver_respostas:true,
            local:quebraRel[0],
            pesquisa:quebraRel[1],
            categoria:quebraRel[2]
          },
          beforeSend:function(){
            $('tr#'+quebraRel[0]).toggle('slow');
            $('tr#'+quebraRel[0]).removeClass('hideTr');
          }
        })
        .done(function(data){
          $(this).html(' Ver Respostas <img src="imagens/barrow_up.png"> ');
          $('tr#'+quebraRel[0]+' td table').html(data);
        });
      }else{
        $('tr#'+quebraRel[0]).toggle('slow');
        $('tr#'+quebraRel[0]).addClass('hideTr');
        $(this).html(' Ver Respostas <img src="imagens/barrow_down.png"> ');
      }
    });

    $('.btn_ver_resposta_posto_sms').click(function(){
      relBtn      = $(this).attr('rel');
      quebraRel   = relBtn.split("|");

      if ($('tr#'+quebraRel[0]).hasClass('hideTr')){
        $.ajax({
          type:"GET",
          url:"relatorio_pesquisas_chamado_ajax.php",
          dataType:"html",
          data:{
            ajax:true,
            ver_respostas:true,
            local:quebraRel[3],
            pesquisa:quebraRel[1],
            categoria:quebraRel[2]
          },
          beforeSend:function(){
            $('tr#'+quebraRel[0]).toggle('slow');
            $('tr#'+quebraRel[0]).removeClass('hideTr');
          }
        })
        .done(function(data){
          $(this).html(' Ver Respostas <img src="imagens/barrow_up.png"> ');
          $('tr#'+quebraRel[0]+' td table').html(data);
        });
      }else{
        $('tr#'+quebraRel[0]).toggle('slow');
        $('tr#'+quebraRel[0]).addClass('hideTr');
        $(this).html(' Ver Respostas <img src="imagens/barrow_down.png"> ');
      }
    });


    //EXIBE RESPOSTA
    $('.btn_ver_resposta_enviado_email').click(function(){
      relBtn      = $(this).attr('rel');
      quebraRel   = relBtn.split("|");
      if ($('tr#'+quebraRel[0]).hasClass('hideTr')){
        $.ajax({
          type:"GET",
          url:"relatorio_pesquisa_laudo_tecnico_ajax_new.php",
          dataType:"html",
          data:{
            ajax:true,
            ver_respostas:true,
            os:quebraRel[0],
            pesquisa:quebraRel[1],
            tipo_pesquisa:quebraRel[2],
            data_inicio:quebraRel[3],
            data_final:quebraRel[4]
          },
          beforeSend:function(){
            $('tr#'+quebraRel[0]).toggle('slow');
            $('tr#'+quebraRel[0]).removeClass('hideTr');
          }
        })
        .done(function(data){
          $(this).html(' Ver Respostas <img src="imagens/barrow_up.png"> ');
          $('tr#'+quebraRel[0]+' td table').html(decodeURIComponent(data));
        });
      }else{
        $('tr#'+quebraRel[0]).toggle('slow');
        $('tr#'+quebraRel[0]).addClass('hideTr');
        $(this).html(' Ver Respostas <img src="imagens/barrow_down.png"> ');
      }
    });

    //ZERA o hidden posto se quando der blur no codigo e o valor estiver vazio
    $("#codigo_posto").blur(function() {
      if ($(this).val().length == 0){
        $("#posto").val('');
      }
    });

    //ZERA o hidden "posto" se quando der blur no nome do posto e o valor estiver vazio
    $("posto_nome").blur(function() {
      if ($(this).val().length == 0){
        $("#posto").val('');
      }
    });

    $(".divShowChart").click(function(){
      pesquisa = $(this).attr('rel');
      $("#div_"+pesquisa).toggle('slow');
    });

    $('.radio_pesquisa').click(function(){
      if($(this).val() == "america_latina"){
        $('#pesquisa_idioma').show();
      }else{
        $('#pesquisa_idioma').hide();
      }
    });
  });

<?php if($login_fabrica == 1){?>
  $(function() {
    $('#paises').change(function() {
        console.log($(this).val());
    }).multipleSelect({
        width: '100%'
    });
  });
<?php } ?>
function createChart(textoChart,perguntas,respostas,pesquisa){

  chart = new Highcharts.Chart({
    chart: {
      renderTo: 'div_'+pesquisa,
      type: 'bar',
      height: 600,
      width: 1000
    },
    title: {
      text: textoChart
    },
    xAxis: {
      categories: $.parseJSON(perguntas)
    },
    yAxis: {
      min: 0,
      title: {
        text: 'Quantidade de respostas'
      }
    },
    legend: {
      backgroundColor: '#FFFFFF',
      reversed: true
    },
    tooltip: {
      formatter: function() {
        return ''+
        this.series.name +'= '+ this.y +'';
      }
    },
    plotOptions: {
      series: {
        stacking: 'normal'
      }
    },
    series: $.parseJSON(respostas)
  });

}

function createChartPesquisa (textoChart,perguntas,respostas)
{

  chart = new Highcharts.Chart({
    chart: {
      renderTo: 'div_26',
      type: 'bar',
      height: 600,
      width: 1000
    },
    title: {
      text: "Resposta da Pesquisa e Satisfação"
    },
    xAxis: {
      categories: perguntas
      // categories: ["5 - Equipamento","6 - Com base na experiência com o reparo do seu produto você recomendaria a Stanley Black and Decker aos seus colegas, familiares ou amigos?",
      // "7 - Selecione a principal razão para a sua pontuação","9 - Tempo de reparo",
      // "10 - Preço do reparo","11 - Qualidade do reparo","12 - Atenção do atendente",
      // "13 - Explicação do reparo","14 - Aspecto visual da Assistência",
      // "15 - Satisfação geral","Numero de dias na assistência"]
    },
    yAxis: {
      min: 0,
      title: {
        text: 'Quantidade de respostas'
      }
    },
    legend: {
      backgroundColor: '#FFFFFF',
      reversed: true
    },
    tooltip: {
      formatter: function() {
        return ''+
        this.series.name +'= '+ this.y +'';
      }
    },
    plotOptions: {
      series: {
        stacking: 'normal'
      }
    },
    series: respostas
    // series: [{
  //           name: 'John',
  //           data: [5, 3, 4, 7, 2, 1, 3, 4, 5, 6, 2]
  //       }]
  });
}

function formatItem(row) {
  return row[2] + " - " + row[1];
}

function formatItemLocal(row) {
  return row[0] + " - " + row[1];
}

function getChartContents(pesquisa,posto_nome,posto_linha,posto_local,text,data_inicial,data_final){

  var series = $.ajax({

    type: "GET",
    dataType: "json",
    async: false,
    url: "relatorio_pesquisas_chamado_ajax.php",
    data: {
      ajax:true,
      getChartContents:true,
      pesquisa:pesquisa,
      posto_nome:posto_nome,
      posto_linha:posto_linha,
      posto_local:posto_local,
      data_inicial:data_inicial,
      data_final:data_final
    }
  })
  .done(function(json) {
    data = json.responseText;
  });


  var dados = getChartCategories(pesquisa,posto_nome,posto_linha,posto_local,data_inicial,data_final);
  dados = dados.responseText;
  series = series.responseText;
  return createChart(text,dados,series,pesquisa);

}

function getChartCategories(pesquisa,posto_nome,posto_linha,posto_local,data_inicial,data_final){

  var dados = $.ajax({

    type: "GET",
    async: false,
    dataType: "json",
    url: "relatorio_pesquisas_chamado_ajax.php",
    data: {
      ajax:true,
      getChartCategories:true,
      pesquisa:pesquisa,
      posto_nome:posto_nome,
      posto_linha:posto_linha,
            posto_local:posto_local,
            data_inicial:data_inicial,
            data_final:data_final
    }
  })
  .done(function(json) {
    var data = json.responseText;
  });
  return dados;
}

</script>


<div class="msg_erro"></div>
<div class="sucesso"></div>

<form action="<?=$PHP_SELF?>" method="post" name="frm_pesquisa">
  <input type="hidden" name="posto" id="posto" value="<?=$posto?>">
  <table class="formulario">
    <tr class="titulo_tabela">
      <th colspan='6'>Parâmetros de Pesquisa</th>
    </tr>

    <tr>
      <td colspan='6'>&nbsp;</td>
    </tr>

    <tr>
      <td>&nbsp;</td>
      <td>
        <label for="data_inicial">Data Inicial:</label><br>
        <input type="text" name="data_inicial" id="data_inicial" class='frm' size="12" value="<?=$data_inicial?>">
      </td>
      <td>
        <label for="data_final">Data Final:</label><br>
        <input type="text" name="data_final" id="data_final" class='frm' size="12" value="<?=$data_final?>">
      </td>
      <td>&nbsp;</td>
    </tr>
    <?
    if(in_array($login_fabrica,array(85,94))){
      if($_POST['pesquisa'] == "posto"){
        $display_hd = "style='display:none;'";
        $display    = "style='display:table-row;'";
      }else if(in_array($_POST['pesquisa'],array('callcenter','externo'))){
        $display    = "style='display:none;'";
        $display_hd = "style='display:table-row;'";
      }else{
        $display    = "style='display:none;'";
        $display_hd = "style='display:none;'";
      }
    }else{
      $display = "";
    }
    ?>
    <tr class="dados_pesquisa_posto" <?=$display?>>
      <td>&nbsp;</td>
      <td>
        <label for="codigo_posto">Posto Código:</label><br>
        <input type="text" name="codigo_posto" id="codigo_posto" class='frm' value="<?=$codigo_posto?>">
      </td>
      <td>
        <label for="posto_nome">Posto Nome:</label><br>
        <input type="text" name="posto_nome" id="posto_nome" class='frm' value="<?=$posto_nome?>">
      </td>
      <td>&nbsp;</td>
    </tr>
    <?
    if(in_array($login_fabrica,array(85,94))){
      $sqlLinha = "   SELECT  tbl_linha.linha,
      tbl_linha.nome
      FROM    tbl_linha
      WHERE   tbl_linha.fabrica = $login_fabrica
      ";
      $resLinha = pg_query($con,$sqlLinha);
      ?>
      <tr class="dados_pesquisa_posto" <?=$display?>>
        <td>&nbsp;</td>
        <td>
          <label for="posto_linha">Linha</label><br>
          <select name="posto_linha">
            <option value="">&nbsp;</option>
            <?
            for($c=0;$c<pg_num_rows($resLinha);$c++){
              $linha      = pg_fetch_result($resLinha,$c,linha);
              $linhaNome  = pg_fetch_result($resLinha,$c,nome);
              ?>
              <option value="<?=$linha?>" <? if($linha == $_POST['posto_linha']){ echo "selected"; }?>><?=$linhaNome?></option>
              <?
            }
            ?>
          </select>
        </td>
        <td>
          <label for="posto_local">Local</label><br>
          <input type="text" name="posto_local" id="posto_local" class='frm' value="<?=$posto_local?>">
          <input type="text" name="posto_estado" id="posto_estado" readonly="readonly" size="2" class='frm' value="<?=$posto_estado?>">
        </td>
        <td>&nbsp;</td>
      </tr>
      <tr class="dados_pesquisa_hd" <?=$display_hd?>>
        <td>&nbsp;</td>
        <td colspan="2">
          <label for="hd_chamado">Atendimento</label><br />
          <input type="text" name="hd_chamado" id="hd_chamado" class='frm' value="<?=$hd_chamado?>">
        </td>
        <td>&nbsp;</td>
      </tr>
      <?
    }
    ?>

    <tr>
      <td>&nbsp;</td>
      <td colspan="2">
        <fieldset>
          <legend>Pesquisa:</legend>
          <?php
          if(!in_array($login_fabrica,array(1,85,94,129,145))){
            $sql = "SELECT  tbl_pesquisa.descricao,
            tbl_pesquisa.pesquisa
            FROM    tbl_pesquisa
            WHERE   tbl_pesquisa.fabrica    = $login_fabrica
            AND     tbl_pesquisa.ativo IS TRUE
            ";
          }else{
            $sql = "SELECT  DISTINCT
            tbl_pesquisa.categoria
            FROM    tbl_pesquisa
            WHERE   tbl_pesquisa.fabrica = $login_fabrica
            AND     tbl_pesquisa.ativo IS TRUE
            ";
          }

          $res = pg_query($con,$sql);

          if (pg_num_rows($res)>0) {
            if ($_POST['pesquisa'] == 'TODOS'){
              $checked = "CHECKED";
            }
            if(!in_array($login_fabrica,array(1,85,94,129,145))){
              ?>
              <input type="radio" name="pesquisa" id="PesquisaTodos" <?=$checked?> value="TODOS" > <label for="PesquisaTodos">TODOS</label>
              <br>
              <?
              for ($i=0; $i < pg_num_rows($res); $i++) {

                $pesquisa_id        = pg_fetch_result($res, $i, 'pesquisa');
                $descricao_pesquisa = pg_fetch_result($res, $i, 'descricao');

                $checked = ($pesquisa == $pesquisa_id) ? "CHECKED" : '' ;
                ?>
                <input type="radio" name="pesquisa" id="<?=$pesquisa_id?>" <?=$checked?> value='<?=$pesquisa_id?>'>
                <label for="<?=$pesquisa_id?>"><?=$descricao_pesquisa?></label>
                <br>

                <?
              }
            }else{
                for ($i=0; $i < pg_num_rows($res); $i++) {
                    $categoria = pg_fetch_result($res,$i,categoria);

                    $checked = ($pesquisa == $categoria) ? "checked" : '' ;

                    if(in_array($login_fabrica,array(1,129))){
                        switch ($categoria) {
                            case 'callcenter':
                                $categoria_desc = "Callcenter";
                                break;
                            case 'externo':
                                $categoria_desc = "Callcenter - E-mail";
                                break;

                            case 'posto':
                                $categoria_desc = "Posto Autorizado";
                                break;

                            case 'ordem_de_servico':
                                $categoria_desc = "Ordem de Serviço";
                                break;

                            case 'ordem_de_servico_email':
                                $categoria_desc = "Ordem de Serviço - E-mail";
                                break;

                            // case 'atualizacao_cadastral':
                                //   $categoria_desc = "Atualização Cadastral"; //HD-2987225
                                //   break;

                            // case 'pesquisa':
                                //   $categoria_desc = "Pesquisa Pneumática"; //HD-2987225
                                //   break;
                            default:
                                $categoria_desc = $categoria;
                                break;
                        }
                    }else{
                        if ($categoria == "ordem_de_servico") {
                            $categoria_desc = "Ordem de Serviço";
                        } else {
                            $categoria_desc = $categoria;
                        }
                    }
                    
                    if (!in_array($categoria, array("atualizacao_cadastral","pesquisa","posto_sms") )) {
                    //if($categoria != "atualizacao_cadastral" AND $categoria != "pesquisa"){ 
                        ?>
                        <input type="radio" class="radio_pesquisa" name="pesquisa" id="<?=$categoria?>" <?=$checked?> value='<?=$categoria?>'>
                        <label for="<?=$categoria?>"><?=ucfirst($categoria_desc)?></label>
                        <br>

                    <?php
                    }
                }

                if($login_fabrica == 1){ ?>

                    <input type="radio" class="radio_pesquisa" name="pesquisa" id="externo_email" <?php echo $pesquisa == 'externo_email' ? 'checked' : '' ; ?> value='externo_email'>
                    <label for="externo_email">Brasil</label>
                    <br>
                    <input type="radio" class="radio_pesquisa" name="pesquisa" id="america_latina" <?php echo $pesquisa == 'america_latina' ? 'checked' : '' ; ?> value='america_latina'>
                    <label for="america_latina">América Latina</label>
                    <br>
                    <input type="radio" class="radio_pesquisa" name="pesquisa" id="pesquisa" <?php echo $pesquisa == 'pesquisa' ? 'checked' : '' ; ?> value='pesquisa'>
                    <label for="america_latina">Pesquisa Pneumática</label>
                    <br>
                    <input type="radio" class="radio_pesquisa" name="pesquisa" id="atualizacao_cadastral" <?php echo $pesquisa == 'atualizacao_cadastral' ? 'checked' : '' ; ?> value='atualizacao_cadastral'>
                    <label for="externo_email">Atualização Cadastral</label>
                    <br>
                    <input type="radio" class="radio_pesquisa" name="pesquisa" id="posto_sms" <?php echo $pesquisa == 'posto_sms' ? 'checked' : '' ; ?> value='posto_sms'>
                    <label for="externo_sms">Pesquisa Treinamento</label>
                    <br>
                <?php
                }
            }
          }
          ?>
        </fieldset>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr id="pesquisa_idioma">
      <td>&nbsp;</td>
      <td rel="idioma" colspan="2">

      <fieldset>
        <legend>Pesquisa:</legend>
        <label>Países:</label>
        <select name='paises[]' id='paises' class='frm' multiple='multiple'>
          <?php
            $sql_pais = "SELECT  DISTINCT
                      tbl_pais.nome,
                      tbl_pais.pais
                    FROM tbl_pais
                    JOIN tbl_posto ON tbl_posto.pais = tbl_pais.pais
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 1
                    ORDER BY tbl_pais.nome";
            $res_pais = pg_query($con,$sql_pais);

            $paises = pg_fetch_all($res_pais);
            foreach($paises as $pais){
          ?>
              <option value="<?=$pais['pais']?>"><?=$pais['nome']?></option>
          <?php
            }
          ?>
        </select>
      </fieldset>
        <?php
        if($pesquisa == "america_latina"){
        ?>
          <input type="hidden" id='combo_pais' value='<?=$pesquisa?>'>
        <?php
        }else if($pesquisa == "externo_email"){
          ?>
          <!-- <label for="posto_local">Buscar O.S.</label><br> -->
          <!-- <input type="text" name="pesquisa_os" id="pesquisa_os" class="frm" value="<?=$pesquisa_os?>"> -->
          <?
        }
        ?>
      </td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td colspan='6'>&nbsp;</td>
    </tr>
    <tr>
      <td colspan='6' align="center">
        <input type="button" value="Pesquisar" id="btn_pesquisa">
        <?php if($login_fabrica == 1 AND $pesquisa == "pesquisa"){?>
          <br/><br/>
          <input type="button" value="Gerar Excel Pesquisa de Postos" id="btn_gerar_excel_pesquisa">
          <?php }
          if($pesquisa == "atualizacao_cadastral"){
        ?>
            <br/><br/>
            <input type="button" value="Gerar Excel Atualização Cadastral" id="btn_gerar_excel_pesquisa_cadastral">
        <?php } ?>
      </td>
    </tr>
    <tr>
      <td colspan='6'>&nbsp;</td>
    </tr>
  </table>

</form>

<br>

<div id="container" class="container"></div>

<?php
if (count($_POST)>0){

  if($pesquisa == ""){
    ?>
    <div class="msg_erro">Favor, escolha um tipo de pesquisa</div>
    <?
    require_once "rodape.php";
    exit;
  }
  //PESQUISA OS CHAMADOS DE ACORDO COM OS PARÂMETROS PASSADOS
    if($pesquisa != "posto" && $pesquisa != "posto_sms" && $pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != "externo_email" && $pesquisa != "america_latina" && $pesquisa != 'atualizacao_cadastral'){

        $sql = "
        SELECT  DISTINCT
        tbl_hd_chamado.hd_chamado                                           ,
        TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY HH24:MI:SS') AS data   ,
        pesquisa.descricao                                                  ,
        pesquisa.pesquisa                                                   ,
        tbl_admin.nome_completo
        FROM    tbl_hd_chamado
        JOIN    tbl_hd_chamado_extra    ON tbl_hd_chamado.hd_chamado    = tbl_hd_chamado_extra.hd_chamado
        JOIN    tbl_admin               ON tbl_hd_chamado.atendente     = tbl_admin.admin
        JOIN    tbl_resposta            ON tbl_hd_chamado.hd_chamado    = tbl_resposta.hd_chamado
        JOIN    tbl_pergunta            ON tbl_resposta.pergunta        = tbl_pergunta.pergunta
        JOIN    tbl_pesquisa_pergunta   ON tbl_pergunta.pergunta        = tbl_pesquisa_pergunta.pergunta
        JOIN    (
        SELECT  tbl_pesquisa.pesquisa,
        tbl_pesquisa.descricao
        FROM    tbl_pesquisa
        WHERE   tbl_pesquisa.ativo IS TRUE
        AND     tbl_pesquisa.fabrica    = $login_fabrica
        $conditionPesquisa
        ORDER BY      tbl_pesquisa.pesquisa DESC
        LIMIT   1
        ) pesquisa              ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
        AND pesquisa.pesquisa           = tbl_resposta.pesquisa
        WHERE   tbl_hd_chamado.status   = 'Resolvido'
        AND     tbl_hd_chamado.fabrica  = $login_fabrica
        AND     tbl_resposta.pesquisa   IS NOT NULL
        $conditionChamado
        AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'
        ;
        ";
    } else if($pesquisa == "posto_sms") {

        $sql = "SELECT 
                    tbl_treinamento.treinamento, 
                    tbl_treinamento.data_inicio, 
                    tbl_treinamento.data_fim, 
                    tbl_treinamento.titulo, 
                    tbl_treinamento.local, 
                    tbl_treinamento.data_finalizado                    
                    FROM tbl_treinamento 
                    WHERE tbl_treinamento.data_finalizado  BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                    and tbl_treinamento.fabrica = $login_fabrica ";


        /*$sql = "SELECT  DISTINCT
                    TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY HH24:MI:SS') AS data   ,
                    TO_CHAR(tbl_resposta.data_input, 'YYYY/MM/DD HH24:MI:SS') AS data_input  ,
                    pesquisa.descricao                                                  ,
                    pesquisa.pesquisa
            FROM tbl_resposta
                JOIN tbl_pergunta ON tbl_resposta.pergunta = tbl_pergunta.pergunta
                JOIN tbl_pesquisa_pergunta ON tbl_pergunta.pergunta = tbl_pesquisa_pergunta.pergunta
            JOIN (
              SELECT  tbl_pesquisa.pesquisa,
              tbl_pesquisa.descricao
              FROM tbl_pesquisa
              WHERE tbl_pesquisa.ativo IS TRUE
              AND tbl_pesquisa.fabrica = $login_fabrica
              $conditionPesquisa
              ORDER BY      tbl_pesquisa.pesquisa DESC
              LIMIT   1
            ) pesquisa              ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
            AND pesquisa.pesquisa           = tbl_resposta.pesquisa
            WHERE   tbl_resposta.pesquisa   IS NOT NULL
            AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'";*/



    } else if($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != "externo_email" && $pesquisa != "america_latina" && $pesquisa != "atualizacao_cadastral") {
        $sql = "
        SELECT  DISTINCT
        TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY HH24:MI:SS') AS data   ,
        pesquisa.descricao                                                  ,
        pesquisa.pesquisa                                                   ,
        tbl_posto.nome AS posto_nome                                        ,
        tbl_posto.cnpj                                                      ,
        tbl_posto.posto
        FROM    tbl_posto
        JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_posto.posto
        AND tbl_posto_fabrica.fabrica   = $login_fabrica
        LEFT JOIN    tbl_posto_linha         ON  tbl_posto_linha.posto       = tbl_posto.posto
        JOIN    tbl_resposta            ON  tbl_resposta.posto          = tbl_posto.posto
        JOIN    tbl_pergunta            ON  tbl_resposta.pergunta       = tbl_pergunta.pergunta
        JOIN    tbl_pesquisa_pergunta   ON  tbl_pergunta.pergunta       = tbl_pesquisa_pergunta.pergunta
        JOIN    (
        SELECT  tbl_pesquisa.pesquisa,
        tbl_pesquisa.descricao
        FROM    tbl_pesquisa
        WHERE   tbl_pesquisa.ativo IS TRUE
        AND     tbl_pesquisa.fabrica    = $login_fabrica
        $conditionPesquisa
        ORDER BY      tbl_pesquisa.pesquisa DESC
        LIMIT   1
        ) pesquisa              ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
        AND pesquisa.pesquisa           = tbl_resposta.pesquisa
        WHERE   tbl_posto_fabrica.credenciamento    IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
        AND     tbl_resposta.pesquisa               IS NOT NULL
        AND     tbl_resposta.data_input             BETWEEN '$aux_data_inicial' AND '$aux_data_final'
        $conditionPosto
        $conditionLinha
        $conditionLocal
        ";

    } else if ($pesquisa == "ordem_de_servico" OR $pesquisa == "ordem_de_servico_email") {
        $sql = "
        SELECT  DISTINCT
        tbl_os.os,
        tbl_os.sua_os,
        TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY HH24:MI:SS') AS data,
        tbl_posto.nome AS posto_nome,
        tbl_posto.cnpj,
        tbl_posto.posto,
        pesquisa.descricao,
        pesquisa.pesquisa
        FROM tbl_resposta
        JOIN tbl_os ON tbl_os.os = tbl_resposta.os AND tbl_os.fabrica = {$login_fabrica}
        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
        JOIN    tbl_pergunta            ON  tbl_resposta.pergunta       = tbl_pergunta.pergunta
        JOIN    tbl_pesquisa_pergunta   ON  tbl_pergunta.pergunta       = tbl_pesquisa_pergunta.pergunta
        JOIN    (
        SELECT  tbl_pesquisa.pesquisa,
        tbl_pesquisa.descricao
        FROM    tbl_pesquisa
        WHERE   tbl_pesquisa.ativo IS TRUE
        AND     tbl_pesquisa.fabrica    = $login_fabrica
        $conditionPesquisa
        ORDER BY      tbl_pesquisa.pesquisa DESC
        LIMIT   1
        ) pesquisa              ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
        AND pesquisa.pesquisa           = tbl_resposta.pesquisa
        WHERE   tbl_posto_fabrica.credenciamento    IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
        AND     tbl_resposta.pesquisa               IS NOT NULL
        AND     tbl_resposta.data_input             BETWEEN '$aux_data_inicial' AND '$aux_data_final'
        $conditionPosto
        $conditionLocal
        ";
    } else if(($pesquisa == "externo_email" || $pesquisa == "america_latina") && $pesquisa_idioma != "todos_idiomas") {
        $sql = "SELECT tbl_os.os,
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



        // if($pesquisa == "externo_email" AND $pesquisa_os != ""){
          // $sql .= "AND tbl_os.sua_os = '$pesquisa_os'";

        // }else
        if($pesquisa == "america_latina"){
            $sql .= " AND JSON_FIELD('pais',tbl_laudo_tecnico_os.observacao) IN ('$pesquisa_language')";
            //AND tbl_laudo_tecnico_os.observacao LIKE '$pesquisa_language' AND tbl_os.posto = 6359";
            $sql .= " $conditionPosto AND titulo ILIKE 'Pesquisa de%' ORDER BY pais, cidade,tbl_laudo_tecnico_os.data ;";
        }else{
          $sql .= " $conditionPosto AND titulo ILIKE 'Pesquisa de%' ORDER BY tbl_laudo_tecnico_os.data ;";
        }
    } else if ($pesquisa == "atualizacao_cadastral") { //HD-2987225
        $sql = "SELECT  DISTINCT
              TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY HH24:MI:SS') AS data   ,
              pesquisa.descricao                                                  ,
              pesquisa.pesquisa,
              tbl_posto.nome,
              tbl_posto.posto
            FROM tbl_resposta
            JOIN tbl_pergunta ON tbl_resposta.pergunta = tbl_pergunta.pergunta
            JOIN tbl_pesquisa_pergunta ON tbl_pergunta.pergunta = tbl_pesquisa_pergunta.pergunta
            JOIN tbl_posto ON tbl_posto.posto = tbl_resposta.posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN (
              SELECT  tbl_pesquisa.pesquisa,
              tbl_pesquisa.descricao
              FROM tbl_pesquisa
              WHERE tbl_pesquisa.ativo IS TRUE
              AND tbl_pesquisa.fabrica = $login_fabrica
              ORDER BY      tbl_pesquisa.pesquisa DESC
              LIMIT   1
            ) pesquisa              ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
          AND pesquisa.pesquisa           = tbl_resposta.pesquisa
          WHERE   tbl_resposta.pesquisa   IS NOT NULL
         /* AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'*/";
    }

    $resTabela = pg_query($con,$sql);

    if ($pesquisa == "posto_sms" and pg_num_rows($resTabela)>0) {
        $dadosTreinamento = pg_fetch_all($resTabela);
        $sql = "SELECT data_input, descricao, categoria as pesquisa, pesquisa as pesquisaId data_input as data from tbl_pesquisa where categoria = 'posto_sms' and fabrica = $login_fabrica";
        $resPesquisa = pg_query($con, $sql);
        if(pg_num_rows($resPesquisa)>0){
            $pesquisa = pg_fetch_result($resPesquisa, 0, pesquisa);
        }
    }

    if (pg_num_rows($resTabela)>0) {
        if($pesquisa != "externo_email" && $pesquisa != "america_latina" && $pesquisa != "atualizacao_cadastral"){
      //PROGRAMA QUE VAI GERAR O XLS
      if ($login_fabrica == 94) {
        include_once 'relatorio_pesquisas_chamado_xls_eve.php';
      }else{
        if($login_fabrica == 1 and $pesquisa == 'posto_sms'){
            include_once 'relatorio_pesquisa_posto_sms.php';        
        }else{
            
        }
      }

      $sqlGrafico = " SELECT  pesquisa    ,
      descricao
      FROM    tbl_pesquisa
      WHERE   fabrica = $login_fabrica
      AND     ativo IS TRUE
      $conditionPesquisa";
      $resGrafico = pg_query($con,$sqlGrafico);

      if (pg_num_rows($resGrafico)>0) {
        for ($x=0; $x < pg_num_rows($resGrafico); $x++) {
          $pesquisaChart = pg_fetch_result($resGrafico, $x, 0);
          $pesquisaDescChart = pg_fetch_result($resGrafico, $x, 1);
          echo "<div id='showChart_$pesquisaChart' rel='$pesquisaChart' class='divShowChart' style='cursor:pointer;margin:auto;text-align:center;width:1000px'>
          <p class='titulo_tabela'>Gráfico: $pesquisaDescChart</p><div style='margin:auto;display:none' id='div_$pesquisaChart' class='div_$pesquisaChart'></div></div>";
          echo '<script>getChartContents('.$pesquisaChart.',"'.$_POST['posto_nome'].'","'.$_POST['posto_linha'].'","'.$_POST['posto_local'].'", "'.$pesquisaDescChart.'","'.$aux_data_inicial.'","'.$aux_data_final.'");</script>';
        }
      }
      ?>

      <table class="tabela">
        <tr class="titulo_coluna">
          <?
          if($pesquisa != 'posto' && $pesquisa != "posto_sms" && $pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email"){
            ?>
            <th>Atendimento</th>
            <?
          }else if ($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != "posto_sms") {
            ?>
            <th>CNPJ</th>
            <?
          } else if ($pesquisa == "ordem_de_servico" OR $pesquisa == "ordem_de_servico_email") {
          ?>
            <th>OS</th>
            <th>CNPJ</th>
            <th>Posto</th>
          <?php
          } ?>
          <th><?=($pesquisa == 'posto_sms')? "Data Treinamento" :"Data Resposta"; ?></th>
          <?
          if($pesquisa != 'posto' && $pesquisa != "posto_sms" && $pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email"){
            ?>
            <th>Atendente</th>
            <?
          }else if ($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != "posto_sms"){
            ?>
            <th>Posto</th>
            <?
          }
          ?>
          <th><?=($pesquisa == "posto_sms")? "Tema": "Pesquisa"; ?></th>
          <th>Ação</th>
        </tr>

        <?

        $i = 0;
        $respostasPergunta = array();
        //percorre o array da consulta principal 1ª vez para jogar as respostas em um array
        foreach (pg_fetch_all($resTabela) as $key) {
          if($pesquisa != "posto" && $pesquisa != "posto_sms" && $pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email"){
            $sql = "SELECT  pergunta,
            txt_resposta,
            tipo_resposta_item,
            hd_chamado
            FROM    tbl_resposta
            JOIN    tbl_pesquisa USING (pesquisa)
            WHERE   hd_chamado = ".$key['hd_chamado']."
            $conditionPesquisa
            ORDER BY      pergunta";
          }else if ($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != "posto_sms") {
            $sql = "SELECT  pergunta,
            txt_resposta,
            tipo_resposta_item,
            posto
            FROM    tbl_resposta
            JOIN    tbl_pesquisa using (pesquisa)
            WHERE   posto = ".$key['posto']."
            $conditionPesquisa
            ORDER BY      pergunta";
          } else if ($pesquisa == "ordem_de_servico" OR $pesquisa == "ordem_de_servico_email") {
            $sql = "SELECT  pergunta,
            txt_resposta,
            tipo_resposta_item,
            os
            FROM    tbl_resposta
            JOIN    tbl_pesquisa using (pesquisa)
            WHERE   os = ".$key['os']."
            $conditionPesquisa
            ORDER BY      pergunta";
          } elseif ($pesquisa == "posto_sms") {

            $id_data_input = $key['data_input'];
            $key_pesquisa = $key['pesquisa'];

            $sql = "SELECT  pergunta            ,
                            txt_resposta        ,
                            data_input          ,
                            tipo_resposta_item
                    FROM    tbl_resposta
                    WHERE   pesquisa    = {$key_pesquisa}
                    AND     os isnull
                    AND     hd_chamado isnull
                    AND     pergunta is not null
                    AND     data_input BETWEEN (timestamp'{$id_data_input}' - INTERVAL '5 second' ) AND (timestamp'{$id_data_input}' + INTERVAL '5 second')
                ORDER BY pergunta";

          }
          //select filtrando por Treinamento
          $resRespostas = pg_query($con,$sql);

          if (pg_num_rows($resRespostas)>0) {
            foreach (pg_fetch_all($resRespostas) as $keyRespostas) {
              if($pesquisa != "posto" && $pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != "posto_sms"){
                $local = $keyRespostas['hd_chamado'];
              }else if ($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != "posto_sms") {
                $local = $keyRespostas['posto'];
              } else if ($pesquisa == "ordem_de_servico" OR $pesquisa == "ordem_de_servico_email" && $pesquisa != "posto_sms") {
                $local = $keyRespostas["os"];
              } elseif ($pesquisa == "posto_sms") {
                  $local = $keyRespostas["data_input"];
              }

              if (!empty($keyRespostas['tipo_resposta_item'])) {

                $respostasPergunta[$key['pesquisa']][$local][$keyRespostas['pergunta']]['respostas'][] = $keyRespostas['tipo_resposta_item'];

              }else{
                $respostasPergunta[$key['pesquisa']][$local][$keyRespostas['pergunta']]['respostas'][] = $keyRespostas['txt_resposta'];
              }
            }
          }
        }
        //echo "<pre>";print_r($respostasPergunta);echo "</pre>";
        $xpto = 1;
        foreach (pg_fetch_all($resTabela) as $key) {
          if($pesquisa != "posto" && $pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != 'posto_sms'){
            $local = $key['hd_chamado'];
          }else if ($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != 'posto_sms') {
            $local = $key['posto'];
          } else if($pesquisa == "ordem_de_servico" OR $pesquisa == "ordem_de_servico_email" && $pesquisa != 'posto_sms') {
            $local = $key["os"];
          } elseif ($pesquisa == 'posto_sms') {
            /*$local = date("c",strtotime($key["data_input"]));
            $local = $key["data_input"];*/
            $local = $xpto;
            $xpto++;
          }
          ?>
          <tr>
            <?
            if($pesquisa != 'posto' && $pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email"  && $pesquisa != 'posto_sms'){
              ?>
              <td> <a href="callcenter_interativo_new.php?callcenter=<?=$local?>" target='_blank'> <?=$local?></a></td>
              <?
            }else if ($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email"  && $pesquisa != 'posto_sms') {
              ?>
              <td><?=$key['cnpj']?></td>
              <?
            } else if ($pesquisa == "ordem_de_servico" OR $pesquisa == "ordem_de_servico_email" && $pesquisa != 'posto_sms') {
            ?>
              <td> <a href="os_press.php?os=<?=$local?>" target='_blank'> <?=$key["sua_os"]?></a></td>
              <td><?=$key['cnpj']?></td>
              <td><?echo $key['posto_nome']?></td>
            <?php
            }
            ?>
            <td><?= ($pesquisa == "posto_sms")? substr(mostra_data($key['data_inicio']),0,10): $key['data']?></td>
            <?
            if($pesquisa != 'posto' && $pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != 'posto_sms'){
              ?>
              <td><?echo $key['nome_completo']?></td>
              <?
            }else if ($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != 'posto_sms') {
              ?>
              <td><?echo $key['posto_nome']?></td>
              <?
            }
            
            if ($login_fabrica == 1) { ?>
                <td><?= ($pesquisa == "posto_sms")? utf8_decode($key['titulo']): $key['descricao']; ?></td>
      <?php } else { ?>
                <td><?= ($pesquisa == "posto_sms")? $key['titulo']: $key['descricao']; ?></td>
      <?php } ?>
            <td>
                <?php
                if (($pesquisa == 'posto_sms')) { ?>
                    <button class="btn_ver_resposta_posto_sms" rel="<?=$local?>|<?=$key['treinamento']?>|<?=$pesquisa?>|<?=$key["data_input"]?>"> Ver Respostas <img src="imagens/barrow_down.png"> </button>
                <?php
                } else { ?>
                    <button class="btn_ver_resposta" rel="<?=$local?>|<?=$key['pesquisa']?>|<?=$pesquisa?>"> Ver Respostas <img src="imagens/barrow_down.png"> </button>
                <?php
                } ?>
              
            </td>
          </tr>
          <tr class='hideTr' id='<?=$local?>' >
            <td colspan="100%">
              <table style="width:100%">
              </table>
            </td>
          </tr>
          <?
        }
        ?>
      </table>
      <?
    }elseif($pesquisa == 'atualizacao_cadastral'){//HD-2987225
    }else{
      $os     = pg_fetch_result($resTabela,0,os);
      $data     = pg_fetch_result($resTabela,0,data);
      $observacao = pg_fetch_result($resTabela,0,observacao);
      if($pesquisa_idioma == "todos_idiomas" && $pesquisa == "america_latina"){
            //PROGRAMA QUE VAI GERAR O XLS
        include_once 'relatorio_pesquisas_laudo_todos_idiomas_xls.php';
      }else{
        include_once 'relatorio_pesquisas_laudo_tecnico_xls_new.php';
        switch ($pesquisa_idioma) {
          // Alterado radios da pesquisa HD2479066
          case 'pt':$resposta['equipamento']['sem_fio'] = 0;
            $resposta['equipamento']['martelo'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['furadeira'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['impacto'] = 0;
            $resposta['equipamento']['parafusadeira'] = 0;
            $resposta['equipamento']['concreto'] = 0;
            $resposta['equipamento']['serras'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['marmore'] = 0;
            $resposta['equipamento']['mecanica'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['madeira'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['jardinagem'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['gasolina'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['pneumatica'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['outro'] = 0; //Ativo - HD2479066
            break;

          case 'es':$resposta['equipamento']['martillos'] = 0;
            $resposta['equipamento']['inalambrico'] = 0;
            $resposta['equipamento']['metalmecanica'] = 0;
            $resposta['equipamento']['madera'] = 0;
            $resposta['equipamento']['estacionaria'] = 0;
            $resposta['equipamento']['jardin'] = 0;
            $resposta['equipamento']['gasolina_explosion'] = 0;
            $resposta['equipamento']['neumatica'] = 0;
            $resposta['equipamento']['other'] = 0;
            break;

          case 'en':$resposta['equipamento']['cordless'] = 0;
            $resposta['equipamento']['hammer'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['drill_cordless'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['drill'] = 0;
            $resposta['equipamento']['metalworking'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['woodworking'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['machinery'] = 0;
            $resposta['equipamento']['saws'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['lawn_garden'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['gasoline_explosion'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['pneumatic'] = 0; //Ativo - HD2479066
            $resposta['equipamento']['other'] = 0; //Ativo - HD2479066
            break;
        }

        $resposta['recomendacao']['resposta_0']  = 0;
        $resposta['recomendacao']['resposta_1']  = 0;
        $resposta['recomendacao']['resposta_2']  = 0;
        $resposta['recomendacao']['resposta_3']  = 0;
        $resposta['recomendacao']['resposta_4']  = 0;
        $resposta['recomendacao']['resposta_5']  = 0;
        $resposta['recomendacao']['resposta_6']  = 0;
        $resposta['recomendacao']['resposta_7']  = 0;
        $resposta['recomendacao']['resposta_8']  = 0;
        $resposta['recomendacao']['resposta_9']  = 0;
        $resposta['recomendacao']['resposta_10'] = 0;

        $resposta['razao_pontuacao']['servico_assistencia'] = 0;
        $resposta['razao_pontuacao']['suporte']             = 0;
        $resposta['razao_pontuacao']['tempo_resposta']      = 0;
        $resposta['razao_pontuacao']['padrao_servico']      = 0;
        $resposta['razao_pontuacao']['qualidade_reparo']    = 0;
        $resposta['razao_pontuacao']['tempo_reparo']        = 0;
        $resposta['razao_pontuacao']['falha_equipamento']   = 0;
        $resposta['razao_pontuacao']['qualidade_produto']   = 0;
        $resposta['razao_pontuacao']['atencao_suporte']     = 0;
        $resposta['razao_pontuacao']['orcamento']           = 0;
        $resposta['razao_pontuacao']['acompanhamento']      = 0;

        // NOVAS RESPOSTAS DA QUESTÃO 7 - DISPONÍVEL A PARTIR DE 2016
        $resposta['razao_pontuacao']['atencao_suporte_telefonico'] = 0;
        $resposta['razao_pontuacao']['atencao_suporte_recepcao']   = 0;
        $resposta['razao_pontuacao']['falha_precoce_ferramenta']   = 0;
        $resposta['razao_pontuacao']['qualidade_do_produto']       = 0;
        $resposta['razao_pontuacao']['tempo_de_resposta']          = 0;
        $resposta['razao_pontuacao']['custo_orcamento_reparo']     = 0;
        $resposta['razao_pontuacao']['rastreamento_reparacao']     = 0;
        $resposta['razao_pontuacao']['tempo_repado']               = 0;
        $resposta['razao_pontuacao']['qualidade_reparacao']        = 0;
        $resposta['razao_pontuacao']['servico_prestado_centro']    = 0;

        $resposta['nota_tempo_reparo']['plenamente_satisfeito'] = 0;
        $resposta['nota_tempo_reparo']['muito_satisfeito']      = 0;
        $resposta['nota_tempo_reparo']['satisfeito']            = 0;
        $resposta['nota_tempo_reparo']['pouco_satisfeito']      = 0;
        $resposta['nota_tempo_reparo']['insatisfeito']          = 0;

        $resposta['nota_preco_reparo']['plenamente_satisfeito'] = 0;
        $resposta['nota_preco_reparo']['muito_satisfeito']      = 0;
        $resposta['nota_preco_reparo']['satisfeito']            = 0;
        $resposta['nota_preco_reparo']['pouco_satisfeito']      = 0;
        $resposta['nota_preco_reparo']['insatisfeito']          = 0;

        $resposta['nota_qualidade_reparo']['plenamente_satisfeito'] = 0;
        $resposta['nota_qualidade_reparo']['muito_satisfeito']      = 0;
        $resposta['nota_qualidade_reparo']['satisfeito']            = 0;
        $resposta['nota_qualidade_reparo']['pouco_satisfeito']      = 0;
        $resposta['nota_qualidade_reparo']['insatisfeito']          = 0;

        $resposta['nota_atencao']['plenamente_satisfeito'] = 0;
        $resposta['nota_atencao']['muito_satisfeito']      = 0;
        $resposta['nota_atencao']['satisfeito']            = 0;
        $resposta['nota_atencao']['pouco_satisfeito']      = 0;
        $resposta['nota_atencao']['insatisfeito']          = 0;

        $resposta['nota_explicacao']['plenamente_satisfeito'] = 0;
        $resposta['nota_explicacao']['muito_satisfeito']      = 0;
        $resposta['nota_explicacao']['satisfeito']            = 0;
        $resposta['nota_explicacao']['pouco_satisfeito']      = 0;
        $resposta['nota_explicacao']['insatisfeito']          = 0;

        $resposta['nota_aspecto']['plenamente_satisfeito'] = 0;
        $resposta['nota_aspecto']['muito_satisfeito']      = 0;
        $resposta['nota_aspecto']['satisfeito']            = 0;
        $resposta['nota_aspecto']['pouco_satisfeito']      = 0;
        $resposta['nota_aspecto']['insatisfeito']          = 0;

        $resposta['nota_geral']['plenamente_satisfeito'] = 0;
        $resposta['nota_geral']['muito_satisfeito']      = 0;
        $resposta['nota_geral']['satisfeito']            = 0;
        $resposta['nota_geral']['pouco_satisfeito']      = 0;
        $resposta['nota_geral']['insatisfeito']          = 0;

        $resposta['resposta_numero']['1'] = 0;
        $resposta['resposta_numero']['2'] = 0;
        $resposta['resposta_numero']['3'] = 0;
        $resposta['resposta_numero']['4'] = 0;
        $resposta['resposta_numero']['5'] = 0;
        $resposta['resposta_numero']['6'] = 0;
        $resposta['resposta_numero']['7'] = 0;
        $resposta['resposta_numero']['8'] = 0;
        $resposta['resposta_numero']['9'] = 0;

        $resultado_pesquisa = pg_fetch_array($resTabela);
        for ($x=0; $x<pg_num_rows($resTabela); $x++) {
            $resultado_resposta = pg_fetch_result($resTabela,$x,observacao);
            $resultado_resposta = json_decode(utf8_encode(stripslashes($resultado_resposta)));

            if ($pesquisa_idioma == 'pt') {

                switch ($resultado_resposta->equipamento) {
                    case 'martelo':     $resposta['equipamento']['martelo']++;  break;
                    case 'furadeira':   $resposta['equipamento']['furadeira']++;  break;
                    case 'mecanica':      $resposta['equipamento']['mecanica']++;  break;
                    case 'madeira':       $resposta['equipamento']['madeira']++;  break;
                    case 'serras':        $resposta['equipamento']['serras']++;  break;
                    case 'jardinagem':    $resposta['equipamento']['jardinagem']++;  break;
                    case 'gasolina':    $resposta['equipamento']['gasolina']++;  break;
                    case 'pneumatica': $resposta['equipamento']['pneumatica']++;  break;
                    case 'outro':       $resposta['equipamento']['outro']++; break;

                    case 'sem_fio':     $resposta['equipamento']['sem_fio']++;  break;
                    case 'impacto':     $resposta['equipamento']['impacto']++;  break;
                    case 'parafusadeira': $resposta['equipamento']['parafusadeira']++;  break;
                    case 'concreto':      $resposta['equipamento']['concreto']++;  break;
                    case 'marmore':       $resposta['equipamento']['marmore']++;  break;
                }
            } else if($pesquisa_idioma == 'es') {
                switch ($resultado_resposta->equipamento) {
                    case 'martillos'       :  $resposta['equipamento']['martillos']++;  break;
                    case 'inalambrico'     :  $resposta['equipamento']['inalambrico']++;  break;
                    case 'metalmecanica'   :  $resposta['equipamento']['metalmecanica']++;  break;
                    case 'madera'          :  $resposta['equipamento']['madera']++;  break;
                    case 'estacionaria'    :  $resposta['equipamento']['estacionaria']++;  break;
                    case 'jardin'          :  $resposta['equipamento']['jardin']++;  break;
                    case 'gasolina_explosion':  $resposta['equipamento']['gasolina_explosion']++;  break;
                    case 'neumatica'       :  $resposta['equipamento']['neumatica']++;  break;
                    case 'other'       :  $resposta['equipamento']['other']++;  break;
                }
            } else if($pesquisa_idioma == 'en') {
                switch ($resultado_resposta->equipamento) {
                    case 'hammer':  $resposta['equipamento']['hammer']++;  break;
                    case 'drill_cordless':  $resposta['equipamento']['drill_cordless']++;  break;
                    case 'metalworking':  $resposta['equipamento']['metalworking']++;  break;
                    case 'woodworking':  $resposta['equipamento']['woodworking']++;  break;
                    case 'saws':  $resposta['equipamento']['saws']++;  break;
                    case 'lawn_garden':  $resposta['equipamento']['lawn_garden']++;  break;
                    case 'gasoline_explosion':  $resposta['equipamento']['gasoline_explosion']++;  break;
                    case 'pneumatic':  $resposta['equipamento']['pneumatic']++;  break;
                    case 'other':  $resposta['equipamento']['other']++;  break;

                    case 'cordless':  $resposta['equipamento']['cordless']++;  break;
                    case 'drill':  $resposta['equipamento']['drill']++;  break;
                    case 'machinery':  $resposta['equipamento']['machinery']++;  break;
                }
            }
          // >>> 6
            switch ($resultado_resposta->recomendacao) {
                case '0':  $resposta['recomendacao']['resposta_0']++;  break;
                case '1':  $resposta['recomendacao']['resposta_1']++;  break;
                case '2':  $resposta['recomendacao']['resposta_2']++;  break;
                case '3':  $resposta['recomendacao']['resposta_3']++;  break;
                case '4':  $resposta['recomendacao']['resposta_4']++;  break;
                case '5':  $resposta['recomendacao']['resposta_5']++;  break;
                case '6':  $resposta['recomendacao']['resposta_6']++;  break;
                case '7':  $resposta['recomendacao']['resposta_7']++;  break;
                case '8':  $resposta['recomendacao']['resposta_8']++;  break;
                case '9':  $resposta['recomendacao']['resposta_9']++;  break;
                case '10': $resposta['recomendacao']['resposta_10']++; break;
            }

          // >>> 7
            switch ($resultado_resposta->razao_pontuacao) {
                case 'servico_assistencia': $resposta['razao_pontuacao']['servico_assistencia']++; break;
                case 'suporte'            : $resposta['razao_pontuacao']['suporte']++;             break;
                case 'tempo_resposta'     : $resposta['razao_pontuacao']['tempo_resposta']++;      break;
                case 'padrao_servico'     : $resposta['razao_pontuacao']['padrao_servico']++;      break;
                case 'qualidade_reparo'   : $resposta['razao_pontuacao']['qualidade_reparo']++;    break;
                case 'tempo_reparo'       : $resposta['razao_pontuacao']['tempo_reparo']++;        break;
                case 'falha_equipamento'  : $resposta['razao_pontuacao']['falha_equipamento']++;   break;
                case 'qualidade_produto'  : $resposta['razao_pontuacao']['qualidade_produto']++;   break;
                case 'atencao_suporte'    : $resposta['razao_pontuacao']['atencao_suporte']++;     break;
                case 'orcamento'          : $resposta['razao_pontuacao']['orcamento']++;           break;
                case 'acompanhamento'     : $resposta['razao_pontuacao']['acompanhamento']++;      break;

                // NOVAS RESPOSTAS DA QUESTÃO 7 - DISPONÍVEL A PARTIR DE 2016
                case 'atencao_suporte_telefonico': $resposta['razao_pontuacao']['atencao_suporte_telefonico']++; break;
                case 'atencao_suporte_recepcao'  : $resposta['razao_pontuacao']['atencao_suporte_recepcao']++;   break;
                case 'falha_precoce_ferramenta'  : $resposta['razao_pontuacao']['falha_precoce_ferramenta']++;   break;
                case 'qualidade_do_produto'      : $resposta['razao_pontuacao']['qualidade_do_produto']++;       break;
                case 'tempo_de_resposta'         : $resposta['razao_pontuacao']['tempo_de_resposta']++;          break;
                case 'custo_orcamento_reparo'    : $resposta['razao_pontuacao']['custo_orcamento_reparo']++;     break;
                case 'rastreamento_reparacao'    : $resposta['razao_pontuacao']['rastreamento_reparacao']++;     break;
                case 'tempo_repado'              : $resposta['razao_pontuacao']['tempo_repado']++;               break;
                case 'qualidade_reparacao'       : $resposta['razao_pontuacao']['qualidade_reparacao']++;        break;
                case 'servico_prestado_centro'   : $resposta['razao_pontuacao']['servico_prestado_centro']++;    break;
            }

          // >>> 9
            switch ($resultado_resposta->nota_tempo_reparo) {
                case 'plenamente_satisfeito': $resposta['nota_tempo_reparo']['plenamente_satisfeito']++; break;
                case 'muito_satisfeito'   : $resposta['nota_tempo_reparo']['muito_satisfeito']++;    break;
                case 'satisfeito'     : $resposta['nota_tempo_reparo']['satisfeito']++;        break;
                case 'pouco_satisfeito'   : $resposta['nota_tempo_reparo']['pouco_satisfeito']++;    break;
                case 'insatisfeito'     : $resposta['nota_tempo_reparo']['insatisfeito']++;      break;
            }

          // >>> 10
            switch ($resultado_resposta->nota_preco_reparo) {
                case 'plenamente_satisfeito': $resposta['nota_preco_reparo']['plenamente_satisfeito']++; break;
                case 'muito_satisfeito'   : $resposta['nota_preco_reparo']['muito_satisfeito']++;    break;
                case 'satisfeito'     : $resposta['nota_preco_reparo']['satisfeito']++;        break;
                case 'pouco_satisfeito'   : $resposta['nota_preco_reparo']['pouco_satisfeito']++;    break;
                case 'insatisfeito'     : $resposta['nota_preco_reparo']['insatisfeito']++;      break;
            }

          // >>> 11
            switch ($resultado_resposta->nota_qualidade_reparo) {
                case 'plenamente_satisfeito': $resposta['nota_qualidade_reparo']['plenamente_satisfeito']++; break;
                case 'muito_satisfeito'   : $resposta['nota_qualidade_reparo']['muito_satisfeito']++;    break;
                case 'satisfeito'     : $resposta['nota_qualidade_reparo']['satisfeito']++;        break;
                case 'pouco_satisfeito'   : $resposta['nota_qualidade_reparo']['pouco_satisfeito']++;    break;
                case 'insatisfeito'     : $resposta['nota_qualidade_reparo']['insatisfeito']++;      break;
            }

          // >>> 12
            switch ($resultado_resposta->nota_atencao) {
                case 'plenamente_satisfeito': $resposta['nota_atencao']['plenamente_satisfeito']++; break;
                case 'muito_satisfeito'   : $resposta['nota_atencao']['muito_satisfeito']++;   break;
                case 'satisfeito'     : $resposta['nota_atencao']['satisfeito']++;       break;
                case 'pouco_satisfeito'   : $resposta['nota_atencao']['pouco_satisfeito']++;   break;
                case 'insatisfeito'     : $resposta['nota_atencao']['insatisfeito']++;     break;
            }

          // >>> 13
            switch ($resultado_resposta->nota_explicacao) {
                case 'plenamente_satisfeito': $resposta['nota_explicacao']['plenamente_satisfeito']++; break;
                case 'muito_satisfeito'   : $resposta['nota_explicacao']['muito_satisfeito']++;    break;
                case 'satisfeito'     : $resposta['nota_explicacao']['satisfeito']++;        break;
                case 'pouco_satisfeito'   : $resposta['nota_explicacao']['pouco_satisfeito']++;    break;
                case 'insatisfeito'     : $resposta['nota_explicacao']['insatisfeito']++;      break;
            }

          // >>> 14
            switch ($resultado_resposta->nota_aspecto) {
                case 'plenamente_satisfeito': $resposta['nota_aspecto']['plenamente_satisfeito']++; break;
                case 'muito_satisfeito'   : $resposta['nota_aspecto']['muito_satisfeito']++;   break;
                case 'satisfeito'     : $resposta['nota_aspecto']['satisfeito']++;       break;
                case 'pouco_satisfeito'   : $resposta['nota_aspecto']['pouco_satisfeito']++;   break;
                case 'insatisfeito'     : $resposta['nota_aspecto']['insatisfeito']++;     break;
            }

          // >>> 15
            switch ($resultado_resposta->nota_geral) {
                case 'plenamente_satisfeito': $resposta['nota_geral']['plenamente_satisfeito']++; break;
                case 'muito_satisfeito'   : $resposta['nota_geral']['muito_satisfeito']++;   break;
                case 'satisfeito'     : $resposta['nota_geral']['satisfeito']++;       break;
                case 'pouco_satisfeito'   : $resposta['nota_geral']['pouco_satisfeito']++;   break;
                case 'insatisfeito'     : $resposta['nota_geral']['insatisfeito']++;     break;
            }

            switch ($resultado_resposta->numero_dias) {
                case '1' : $resposta['resposta_numero']['1']++; break;
                case '2' : $resposta['resposta_numero']['2']++; break;
                case '3' : $resposta['resposta_numero']['3']++; break;
                case '4' : $resposta['resposta_numero']['4']++; break;
                case '5' : $resposta['resposta_numero']['5']++; break;
                case '6' : $resposta['resposta_numero']['6']++; break;
                case '7' : $resposta['resposta_numero']['7']++; break;
                case '8' : $resposta['resposta_numero']['8']++; break;
                case '9' : $resposta['resposta_numero']['9']++; break;
            }
        }

        if ($pesquisa_idioma == "en") {
            $pergunta[] = "5 - ".$language[$pesquisa_idioma]['questao_cinco'];
            $pergunta[] = "6 - ".$language[$pesquisa_idioma]['questao_seis'];
            $pergunta[] = "7 - ".$language[$pesquisa_idioma]['questao_sete'];
            $pergunta[] = "9 - ".$language[$pesquisa_idioma]['questao_nove'];
            $pergunta[] = "10 - ".$language[$pesquisa_idioma]['questao_dez'];
            $pergunta[] = "11 - ".$language[$pesquisa_idioma]['questao_onze'];
            $pergunta[] = "12 - ".$language[$pesquisa_idioma]['questao_doze'];
            $pergunta[] = "13 - ".$language[$pesquisa_idioma]['questao_treze'];
            $pergunta[] = "14 - ".$language[$pesquisa_idioma]['questao_quartoze'];
            $pergunta[] = "15 - ".$language[$pesquisa_idioma]['questao_quinze'];
            $pergunta[] = $language[$pesquisa_idioma]['questao_numero'];
        } else {
            $pergunta[] = "5 - ".$language[$pesquisa_idioma]['questao_cinco_grafico'];
            $pergunta[] = "6 - ".$language[$pesquisa_idioma]['questao_seis_grafico'];
            $pergunta[] = "7 - ".$language[$pesquisa_idioma]['questao_sete_grafico'];
            $pergunta[] = "9 - ".$language[$pesquisa_idioma]['questao_nove_grafico'];
            $pergunta[] = "10 - ".$language[$pesquisa_idioma]['questao_dez_grafico'];
            $pergunta[] = "11 - ".$language[$pesquisa_idioma]['questao_onze_grafico'];
            $pergunta[] = "12 - ".$language[$pesquisa_idioma]['questao_doze_grafico'];
            $pergunta[] = "13 - ".$language[$pesquisa_idioma]['questao_treze_grafico'];
            $pergunta[] = "14 - ".$language[$pesquisa_idioma]['questao_quartoze_grafico'];
            $pergunta[] = "15 - ".$language[$pesquisa_idioma]['questao_quinze_grafico'];
            $pergunta[] = $language[$pesquisa_idioma]['questao_numero_grafico'];
        }

        switch ($pesquisa_idioma) {
            case 'pt':
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['martelo'], 'data' =>     array($resposta['equipamento']['martelo'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['furadeira'], 'data' =>     array($resposta['equipamento']['furadeira'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['mecanica'], 'data' =>     array($resposta['equipamento']['mecanica'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['madeira'], 'data' =>      array($resposta['equipamento']['madeira'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['serras'], 'data' =>     array($resposta['equipamento']['serras'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['jardinagem'], 'data' =>    array($resposta['equipamento']['jardinagem'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['gasolina'], 'data' =>    array($resposta['equipamento']['gasolina'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pneumatica'], 'data' =>    array($resposta['equipamento']['pneumatica'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['outro'], 'data' =>        array($resposta['equipamento']['outro'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));

                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['sem_fio'], 'data' =>     array($resposta['equipamento']['sem_fio'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['impacto'], 'data' =>      array($resposta['equipamento']['impacto'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['parafusadeira'], 'data' => array($resposta['equipamento']['parafusadeira'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['concreto'], 'data' =>     array($resposta['equipamento']['concreto'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['marmore'], 'data' =>      array($resposta['equipamento']['marmore'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                break;

            case 'es':
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['martillos'], 'data'   => array($resposta['equipamento']['martillos'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['inalambrico'], 'data'       => array($resposta['equipamento']['inalambrico'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['metalmecanica'], 'data'     => array($resposta['equipamento']['metalmecanica'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['madera'], 'data'          => array($resposta['equipamento']['madera'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['estacionaria'], 'data'      => array($resposta['equipamento']['estacionaria'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['jardin'], 'data'          => array($resposta['equipamento']['jardin'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['gasolina_explosion'], 'data' => array($resposta['equipamento']['gasolina_explosion'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['neumatica'], 'data'       => array($resposta['equipamento']['neumatica'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['other'], 'data'       => array($resposta['equipamento']['other'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                break;

            case 'en':
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['hammer'], 'data' =>     array($resposta['equipamento']['hammer'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['drill_cordless'], 'data' =>     array($resposta['equipamento']['drill_cordless'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['metalworking'], 'data' =>     array($resposta['equipamento']['metalworking'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['woodworking'], 'data' =>      array($resposta['equipamento']['woodworking'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['saws'], 'data' => array($resposta['equipamento']['saws'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['lawn_garden'], 'data' =>      array($resposta['equipamento']['lawn_garden'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['gasoline_explosion'], 'data' =>     array($resposta['equipamento']['gasoline_explosion'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pneumatic'], 'data' =>    array($resposta['equipamento']['pneumatic'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['other'], 'data' =>        array($resposta['equipamento']['other'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));

                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['cordless'], 'data' =>     array($resposta['equipamento']['cordless'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['drill'], 'data' => array($resposta['equipamento']['drill'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['machinery'], 'data' =>      array($resposta['equipamento']['machinery'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                break;
        }

        $resposta_grafico[] = array('name' => '0', 'data' =>  array(0, $resposta['recomendacao']['resposta_0'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '1', 'data' =>  array(0, $resposta['recomendacao']['resposta_1'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '2', 'data' =>  array(0, $resposta['recomendacao']['resposta_2'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '3', 'data' =>  array(0, $resposta['recomendacao']['resposta_3'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '4', 'data' =>  array(0, $resposta['recomendacao']['resposta_4'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '5', 'data' =>  array(0, $resposta['recomendacao']['resposta_5'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '6', 'data' =>  array(0, $resposta['recomendacao']['resposta_6'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '7', 'data' =>  array(0, $resposta['recomendacao']['resposta_7'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '8', 'data' =>  array(0, $resposta['recomendacao']['resposta_8'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '9', 'data' =>  array(0, $resposta['recomendacao']['resposta_9'], 0, 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => '10', 'data' => array(0, $resposta['recomendacao']['resposta_10'], 0, 0, 0, 0, 0, 0, 0, 0, 0));

        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['servico_assistencia'], 'data' => array(0, 0, $resposta['razao_pontuacao']['servico_assistencia'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['suporte']       , 'data' => array(0, 0, $resposta['razao_pontuacao']['suporte'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['tempo_resposta']    , 'data' => array(0, 0, $resposta['razao_pontuacao']['tempo_resposta'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['padrao_servico']    , 'data' => array(0, 0, $resposta['razao_pontuacao']['padrao_servico'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['qualidade_reparo']    , 'data' => array(0, 0, $resposta['razao_pontuacao']['qualidade_reparo'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['tempo_reparo']      , 'data' => array(0, 0, $resposta['razao_pontuacao']['tempo_reparo'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['falha_equipamento']  , 'data' => array(0, 0, $resposta['razao_pontuacao']['falha_equipamento'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['qualidade_produto']  , 'data' => array(0, 0, $resposta['razao_pontuacao']['qualidade_produto'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['atencao_suporte']   , 'data' => array(0, 0, $resposta['razao_pontuacao']['atencao_suporte'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['orcamento']     , 'data' => array(0, 0, $resposta['razao_pontuacao']['orcamento'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['acompanhamento']     , 'data' => array(0, 0, $resposta['razao_pontuacao']['acompanhamento'], 0, 0, 0, 0, 0, 0, 0, 0));

        // NOVAS RESPOSTAS DA QUESTÃO 7 - DISPONÍVEL A PARTIR DE 2016
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['atencao_suporte_telefonico'], 'data' => array(0, 0, $resposta['razao_pontuacao']['atencao_suporte_telefonico'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['atencao_suporte_recepcao'],   'data' => array(0, 0, $resposta['razao_pontuacao']['atencao_suporte_recepcao'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['falha_precoce_ferramenta'],   'data' => array(0, 0, $resposta['razao_pontuacao']['falha_precoce_ferramenta'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['qualidade_do_produto'],       'data' => array(0, 0, $resposta['razao_pontuacao']['qualidade_do_produto'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['tempo_de_resposta'],          'data' => array(0, 0, $resposta['razao_pontuacao']['tempo_de_resposta'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['custo_orcamento_reparo'],     'data' => array(0, 0, $resposta['razao_pontuacao']['custo_orcamento_reparo'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['rastreamento_reparacao'],     'data' => array(0, 0, $resposta['razao_pontuacao']['rastreamento_reparacao'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['tempo_repado'],               'data' => array(0, 0, $resposta['razao_pontuacao']['tempo_repado'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['qualidade_reparacao'],        'data' => array(0, 0, $resposta['razao_pontuacao']['qualidade_reparacao'], 0, 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['servico_prestado_centro'],    'data' => array(0, 0, $resposta['razao_pontuacao']['servico_prestado_centro'], 0, 0, 0, 0, 0, 0, 0, 0));

        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['plenamente_satisfeito'] , 'data' => array(0, 0, 0, $resposta['nota_tempo_reparo']['plenamente_satisfeito'], 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['muito_satisfeito']     , 'data' => array(0, 0, 0, $resposta['nota_tempo_reparo']['muito_satisfeito'    ], 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['satisfeito']         , 'data' => array(0, 0, 0, $resposta['nota_tempo_reparo']['satisfeito'        ], 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pouco_satisfeito']       , 'data' => array(0, 0, 0, $resposta['nota_tempo_reparo']['pouco_satisfeito'    ], 0, 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['insatisfeito']         , 'data' => array(0, 0, 0, $resposta['nota_tempo_reparo']['insatisfeito'      ], 0, 0, 0, 0, 0, 0, 0));

        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['plenamente_satisfeito'] , 'data' => array(0, 0, 0, 0, $resposta['nota_preco_reparo']['plenamente_satisfeito'], 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['muito_satisfeito']     , 'data' => array(0, 0, 0, 0, $resposta['nota_preco_reparo']['muito_satisfeito'   ], 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['satisfeito']         , 'data' => array(0, 0, 0, 0, $resposta['nota_preco_reparo']['satisfeito'       ], 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pouco_satisfeito']       , 'data' => array(0, 0, 0, 0, $resposta['nota_preco_reparo']['pouco_satisfeito'   ], 0, 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['insatisfeito']         , 'data' => array(0, 0, 0, 0, $resposta['nota_preco_reparo']['insatisfeito'     ], 0, 0, 0, 0, 0, 0));

        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['plenamente_satisfeito'] , 'data' => array(0, 0, 0, 0, 0, $resposta['nota_qualidade_reparo']['plenamente_satisfeito'], 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['muito_satisfeito']     , 'data' => array(0, 0, 0, 0, 0, $resposta['nota_qualidade_reparo']['muito_satisfeito'    ], 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['satisfeito']         , 'data' => array(0, 0, 0, 0, 0, $resposta['nota_qualidade_reparo']['satisfeito'        ], 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pouco_satisfeito']       , 'data' => array(0, 0, 0, 0, 0, $resposta['nota_qualidade_reparo']['pouco_satisfeito'    ], 0, 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['insatisfeito']         , 'data' => array(0, 0, 0, 0, 0, $resposta['nota_qualidade_reparo']['insatisfeito'      ], 0, 0, 0, 0, 0));

        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['plenamente_satisfeito'] , 'data' => array(0, 0, 0, 0, 0, 0, $resposta['nota_atencao']['plenamente_satisfeito'], 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['muito_satisfeito']     , 'data' => array(0, 0, 0, 0, 0, 0, $resposta['nota_atencao']['muito_satisfeito'    ], 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['satisfeito']         , 'data' => array(0, 0, 0, 0, 0, 0, $resposta['nota_atencao']['satisfeito'      ], 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pouco_satisfeito']       , 'data' => array(0, 0, 0, 0, 0, 0, $resposta['nota_atencao']['pouco_satisfeito'    ], 0, 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['insatisfeito']         , 'data' => array(0, 0, 0, 0, 0, 0, $resposta['nota_atencao']['insatisfeito'      ], 0, 0, 0, 0));

        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['plenamente_satisfeito'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, $resposta['nota_explicacao']['plenamente_satisfeito'], 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['muito_satisfeito']     , 'data' => array(0, 0, 0, 0, 0, 0, 0, $resposta['nota_explicacao']['muito_satisfeito'    ], 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['satisfeito']         , 'data' => array(0, 0, 0, 0, 0, 0, 0, $resposta['nota_explicacao']['satisfeito'        ], 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pouco_satisfeito']       , 'data' => array(0, 0, 0, 0, 0, 0, 0, $resposta['nota_explicacao']['pouco_satisfeito'    ], 0, 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['insatisfeito']         , 'data' => array(0, 0, 0, 0, 0, 0, 0, $resposta['nota_explicacao']['insatisfeito'      ], 0, 0, 0));

        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['plenamente_satisfeito'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_aspecto']['plenamente_satisfeito'], 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['muito_satisfeito']     , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_aspecto']['muito_satisfeito'   ], 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['satisfeito']         , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_aspecto']['satisfeito'       ], 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pouco_satisfeito']       , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_aspecto']['pouco_satisfeito'   ], 0, 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['insatisfeito']         , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_aspecto']['insatisfeito'     ], 0, 0));

        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['plenamente_satisfeito'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_geral']['plenamente_satisfeito'], 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['muito_satisfeito']     , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_geral']['muito_satisfeito'    ], 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['satisfeito']         , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_geral']['satisfeito'      ], 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pouco_satisfeito']       , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_geral']['pouco_satisfeito'    ], 0));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['insatisfeito']         , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['nota_geral']['insatisfeito'      ], 0));

        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['resposta_numero_1'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['resposta_numero']['1']));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['resposta_numero_2'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['resposta_numero']['2']));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['resposta_numero_3'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['resposta_numero']['3']));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['resposta_numero_4'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['resposta_numero']['4']));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['resposta_numero_5'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['resposta_numero']['5']));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['resposta_numero_6'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['resposta_numero']['6']));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['resposta_numero_7'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['resposta_numero']['7']));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['resposta_numero_8'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['resposta_numero']['8']));
        $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['resposta_numero_9'] , 'data' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $resposta['resposta_numero']['9']));
// echo "<pre>";
// print_r($resposta_grafico);exit;
        $pesquisaChart = "26";
        $pesquisaDescChart = "Pesquisa de Satisfação";
        echo "<div id='showChart_$pesquisaChart' rel='$pesquisaChart' class='divShowChart' style='cursor:pointer;margin:auto;text-align:center;width:1000px'>
        <p class='titulo_tabela'>Gráfico: $pesquisaDescChart</p><div style='margin:auto;display:none' id='div_$pesquisaChart' class='div_$pesquisaChart'></div></div>";
        echo '<script>createChartPesquisa('.$pesquisaChart.','.json_encode($pergunta).','.json_encode($resposta_grafico).');</script>';

        if ($pesquisa != "america_latina") {
?>
        <table class="tabela">
          <tr class="titulo_coluna">
            <th>País</th>
            <th>Cidade</th>
            <?php if($pesquisa == "externo_email"){
              echo "<th>OS</th>";
            }
            ?>
            <th>Data Resposta</th>
            <th>Ação</th>
          </tr>
          <?
          $i = 0;
          foreach (pg_fetch_all($resTabela) as $key) {
            $local = $key["os"];
            $resultado_resposta = json_decode(urldecode(utf8_encode(stripslashes($key['observacao']))));
//             $cidade = "";
//             $cidade = str_replace("+", " ", $resultado_resposta->cidade);
// 			$cidade = utf8_encode($cidade);
            if ($login_fabrica == 1) {
                $cidade = str_replace('+', ' ', $key['cidade']);
            }
            ?>
            <tr>
              <td><?echo $resultado_resposta->pais?></td>
              <td><?echo $cidade?></td>
              <?php if($pesquisa == "externo_email"){
                echo "<td> <a href='os_press.php?os=$local' target='_blank'>".$key['codigo_posto']."".$key['sua_os']."</a></td>";
              }?>
              <td><?echo $key['data']?></td>
              <td>
                <?php if($pesquisa == "externo_email"){ ?>
                  <button class="btn_ver_resposta_enviado_email" rel="<?=$local?>|<?=$pesquisa_idioma?>|<?=$pesquisa?>|<?=$aux_data_inicial?>|<?=$aux_data_final?>"> Ver Respostas <img src="imagens/barrow_down.png"> </button>
                <?php }else{ ?>
                  <button class="btn_ver_resposta_enviado_email" rel='<?=$local?>|<?=$paises_list?>|<?=$pesquisa?>|<?=$aux_data_inicial?>|<?=$aux_data_final?>'> Ver Respostas <img src="imagens/barrow_down.png"> </button>
                <?php } ?>
                <!--<button class="btn_ver_resposta_enviado_email" rel="<?=$local?>|<?=$pesquisa_idioma?>|<?=$pesquisa?>|<?=$aux_data_inicial?>|<?=$aux_data_final?>"> Ver Respostas <img src="imagens/barrow_down.png"> </button>-->
              </td>
            </tr>
            <tr class='hideTr' id='<?=$local?>' >
              <td colspan="100%">
                <table style="width:100%">
                </table>
              </td>
            </tr>
            <?
          }
          ?>
        </table>
        <?
      } else {
        //Cria um array com retorno do sql e do json para ser organizado por  pais, cidade - HD-2479066
        $retornoSqlJson[] = array();
        $arrayPorPais = array();
        $arrayPorCidade = array();
        $resOrdenado = array();
        foreach (pg_fetch_all($resTabela) as $key) {
            $local = $key["os"];
            $resultado_resposta = json_decode(urldecode(utf8_encode(stripslashes($key['observacao']))));
            $pais = "";
            $pais = $resultado_resposta->pais;
            $cidade = "";
			$cidade = str_replace("+", " ", $resultado_resposta->cidade);
			if(!empty($resultado_resposta)) {
				$retornoSqlJson[] = array('Pais' =>  $pais, 'Cidade' => $cidade, 'Data' => $key['data'], 'OS' => $local);
			}
        }
        foreach ($retornoSqlJson as $k => $v) {
            foreach ($v as $k2 => $v2) {
              if ($k2 == 'Pais') {
                $arrayPorPais[$k] = $v2;
              }
              if ($k2 == 'Cidade') {
                $arrayPorCidade[$k] = $v2;
              }
          }
        }
        foreach ($arrayPorPais as $k => $v) {
          $resOrdenado[$k] = $retornoSqlJson[$k];
        }
        array_multisort($arrayPorPais, SORT_ASC, $arrayPorCidade, SORT_ASC, $resOrdenado);
        /*echo "<pre>";
        print_r($resOrdenado);
        echo "</pre>";*/
        ?>
        <table class="tabela">
          <tr class="titulo_coluna">
            <th>País</th>
            <th>Cidade</th>
            <th>Data Resposta</th>
            <th>Ação</th>
          </tr>
        <?
        foreach ($resOrdenado as $linha) {
            ?>
            <tr>
              <td><?= $linha['Pais']?></td>
              <td><?= utf8_decode($linha['Cidade'])?></td>
              <td><?= $linha['Data']?></td>
              <td>
                  <button class="btn_ver_resposta_enviado_email" rel='<?=$linha['OS']?>|<?=$paises_list?>|<?=$pesquisa?>|<?=$aux_data_inicial?>|<?=$aux_data_final?>'> Ver Respostas <img src="imagens/barrow_down.png"> </button>
              </td>
            </tr>
            <tr class='hideTr' id='<?=$linha['OS']?>' >
              <td colspan="100%">
                <table style="width:100%">
                </table>
              </td>
            </tr>
            <?
        }
        ?>
        </table>
        <?
      }
    }
    }
    } else {
        if($pesquisa_idioma == "todos_idiomas" && $pesquisa == "america_latina"){
            //PROGRAMA QUE VAI GERAR O XLS
            include_once 'relatorio_pesquisas_laudo_todos_idiomas_xls.php';
        } else {
            if($pesquisa != 'pesquisa'){ ?>
                <div class="msg_erro">Nenhum Resultado Encontrado</div>
            <?php
            }
        }
    }
}
require_once 'rodape.php';
?>

