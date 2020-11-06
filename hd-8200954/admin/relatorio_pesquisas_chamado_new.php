<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if(isset($_POST["carregaComboPesquisa"])){

    $valor      = $_POST["valor"];
    $pesquisa   = $_POST["pesquisa"];


  $sql = "SELECT pesquisa, descricao, categoria FROM tbl_pesquisa WHERE fabrica = $login_fabrica AND categoria = '$valor' ";
  $res = pg_query($con, $sql);
  echo "<option value=''>".traduz("Selecione uma Pesquisa")."</option>";
  if(pg_num_rows($res) > 0 ){
    for($i = 0; $i<pg_num_rows($res); $i++){
      $descricao = pg_fetch_result($res, $i, 'descricao');
      $pesquisa = pg_fetch_result($res, $i, 'pesquisa');

      echo "<option value='$pesquisa'>$descricao</option>";
    }
  }
  exit;
}



if (isset($_POST)){

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
    $nome_pesquisa = $_POST["nome_pesquisa"];

    if (strstr($pesquisa,"|")) {
        list($pesquisa,$categoria) = explode("|",$pesquisa);
    }

    if(strlen(trim($nome_pesquisa))==0 and $login_fabrica == 129){
        $msg_error .= traduz("Por favor selecione uma pesquisa.<br>");
    }


    if($login_fabrica == 129 and strlen(trim($pesquisa)) > 0 ){
        $sql = "SELECT pesquisa, descricao, categoria FROM tbl_pesquisa WHERE fabrica = $login_fabrica AND categoria = '$pesquisa' ";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0 ){
            for($i = 0; $i<pg_num_rows($res); $i++){
                $descricao = pg_fetch_result($res, $i, 'descricao');
                $pesquisa_id = pg_fetch_result($res, $i, 'pesquisa');

                if($pesquisa_id == $nome_pesquisa){
                    $selected = " selected ";
                }else{
                    $selected = " ";
                }

                $option .= "<option value='$pesquisa_id' $selected >$descricao</option>";
            }
        }
    }


  //hd_chamado=2414398
  if($login_fabrica == 94){
    $pesquisa_selecionada = $_POST['tipo_pesquisa_callcenter'];
    if(strlen($hd_chamado) == "" && strlen($pesquisa_selecionada) == ""){
      $msg_error = traduz("Digite um Atendimento ou Selecione uma Pesquisa");
    }

    if(strlen($hd_chamado) > 0){
      $sqlPesq = "SELECT DISTINCT tbl_resposta.pesquisa
                    FROM tbl_resposta
                    JOIN tbl_pesquisa ON tbl_resposta.pesquisa = tbl_pesquisa.pesquisa
                    WHERE tbl_resposta.hd_chamado = $hd_chamado
                    AND tbl_pesquisa.fabrica = $login_fabrica";
      $resPesq = pg_query($con, $sqlPesq);
      if(pg_num_rows($resPesq) > 0){
        $pesquisa_selecionada = pg_fetch_result($resPesq, 0, 'pesquisa');
      }
    }


  }
  //hd_chamado=2414398 fim

  // Monteiro //
  if(!empty($_POST['pesquisa_idioma'])) {
    $pesquisa_idiomas = implode(',', $_POST['pesquisa_idioma']);
  }
  // Fim Monteiro //

  if($pesquisa == "america_latina"){
    // Monteiro //
    $pesquisa_grafico = '%language%';

    switch ($pesquisa_idiomas) {
      case 'es':
        $pesquisa_language = "array['%\"es\"%']";
        $pesquisa_idioma = "es";
        break;
      case 'en':
        $pesquisa_language = "array['%\"en\"%']";
        $pesquisa_idioma = "en";
        break;
      case 'pt':
        $pesquisa_language = "array['%\"pt\"%']";
        $pesquisa_idioma = "pt";
        break;
      case 'es,pt':
        $pesquisa_language = "array['%\"es\"%','%\"pt\"%']";
        $pesquisa_idioma = "todos_idiomas";
        $sql_idiomas = "array['%\"es\"%','%\"pt\"%']";
        break;
      case 'es,en':
        $pesquisa_language = "array['%\"es\"%','%\"en\"%']";
        $pesquisa_idioma = "todos_idiomas";
        $sql_idiomas = "array['%\"es\"%','%\"en\"%']";
        break;
      case 'en,pt':
        $pesquisa_language = "array['%\"en\"%','%\"pt\"%']";
        $pesquisa_idioma = "todos_idiomas";
        $sql_idiomas = "array['%\"en\"%','%\"pt\"%']";
        break;
      case 'es,en,pt':
        $pesquisa_language = "array['%\"es\"%','%\"en\"%','%\"pt\"%']";
        $pesquisa_idioma = "todos_idiomas";
        $sql_idiomas = "array['%\"es\"%','%\"en\"%','%\"pt\"%']";
        break;
    }
    // Fim Monteiro//

    // switch ($pesquisa_idioma) {
    //   case 'pt': $pesquisa_grafico = '%"language"%'; $pesquisa_language = '%"pt"%'; break;
    //   case 'es': $pesquisa_grafico = '%"language"%'; $pesquisa_language = '%"es"%'; break;
    //   case 'en': $pesquisa_grafico = '%"language"%'; $pesquisa_language = '%"en"%'; break;
    // }
  }else{
    $pesquisa_grafico = '%"language"%';
    $pesquisa_language = '%"pt"%';

    // $pesquisa_grafico = '%"language":"pt"%';
    $pesquisa_idioma = 'pt';
  }

  list($di, $mi, $yi) = explode("/", $data_inicial);

  list($df, $mf, $yf) = explode("/", $data_final);

  $aux_data_inicial = "$yi-$mi-$di 00:00:00";
  $aux_data_final   = "$yf-$mf-$df 23:59:59";

  if (!in_array($login_fabrica,array(1,35,85,94,129,138,145,151,160,161)) and !$replica_einhell) {
    $conditionPesquisa = (!empty($pesquisa)) ? " AND tbl_pesquisa.pesquisa = $pesquisa " : '' ;
    $conditionPosto = (!empty($posto)) ? " AND tbl_hd_chamado_extra.posto = $posto " : '' ;
  } else {
    $conditionPesquisa  = (!empty($pesquisa)) ? " AND tbl_pesquisa.categoria = '$pesquisa' " : '' ;
    $conditionPosto     = (!empty($posto_nome)) ? " AND tbl_posto.nome = '$posto_nome' " : '';
    $conditionLinha     = (!empty($posto_linha)) ? " AND tbl_posto_linha.linha = '$posto_linha' " : '';
    $conditionChamado   = (!empty($hd_chamado)) ? " AND tbl_hd_chamado.hd_chamado = '$hd_chamado' " : '';

    if($login_fabrica == 94){
      $conditionChamado2   = (!empty($pesquisa_selecionada)) ? " AND pesquisa.pesquisa = '$pesquisa_selecionada' " : ''; //hd_chamado=2414398
      $conditionPesquisaSelecionada   = (!empty($pesquisa_selecionada)) ? " AND tbl_pesquisa.pesquisa = '$pesquisa_selecionada' " : ''; //hd_chamado=2414398
    }

    $conditionLocal     = (!empty($posto_local)) ? " AND UPPER(fn_retira_especiais(tbl_posto.cidade)) = UPPER('$posto_local') " : '';

    // switch ($pesquisa_idioma) {
    //  case 'pt':
    if($pesquisa == "externo_email"){
      $pesquisa_idioma = "pt";
    }

    if($pesquisa_idioma == "pt" || $pesquisa_idioma == "todos_idiomas"){
      $language['pt']['questao_cinco'] = 'Equipamento';
      $language['pt']['questao_cinco_grafico'] = utf8_encode('Equipamento');

      $language['pt']['sem_fio'] = 'Sem fio';
      $language['pt']['furadeira'] = 'Furadeira';
      $language['pt']['impacto'] = 'Furadeira de Impacto';
      $language['pt']['parafusadeira'] = 'Parafusadeira';
      $language['pt']['concreto'] = 'Concreto';
      $language['pt']['serras'] = 'Serras';
      $language['pt']['marmore'] = 'Serras - Marmore';
      $language['pt']['mecanica'] = 'Metal - Mecânica';
      $language['pt']['madeira'] = 'Madeira';
      $language['pt']['jardinagem'] = 'Jardinagem';
      $language['pt']['outro'] = 'Outro';

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
      // break;

    // case 'en':
    if($pesquisa_idioma == "en" || $pesquisa_idioma == "todos_idiomas"){
      $language['en']['questao_cinco'] = 'Equipment';
      $language['en']['cordless'] = 'Cordless';
      $language['en']['hammer'] = 'Hammer';
      $language['en']['drill'] = 'Drill';
      $language['en']['metalworking'] = 'Metalworking';
      $language['en']['woodworking'] = 'Woodworking';
      $language['en']['machinery'] = 'Machinery';
      $language['en']['lawn_garden'] = 'Lawn + Garden';
      $language['en']['gasoline_explosion'] = 'Gasoline / Explosion';
      $language['en']['pneumatic'] = 'Pneumatic';
      $language['en']['other'] = 'Other';

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
      // break;

    // case 'es':
    if($pesquisa_idioma == "es" || $pesquisa_idioma == "todos_idiomas"){
      $language['es']['questao_cinco'] = 'Equipo que se reparó';
      $language['es']['questao_cinco_grafico'] = utf8_encode('Equipo que se reparó');

      $language['es']['martillos'] = 'Martillos';
      $language['es']['inalambrico'] = 'Taladros / H. Inalámbricas';
      $language['es']['metalmecanica'] = 'Metalmecánica';
      $language['es']['madera'] = 'Madera';
      $language['es']['estacionaria'] = 'H. Estacionaria';
      $language['es']['jardin'] = 'Jardín';
      $language['es']['gasolina_explosion'] = 'Gasolina / Explosión';
      $language['es']['neumatica'] = 'Neumática';
      $language['es']['other'] = 'Otra';

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

      $language['es']['servico_assistencia'] = 'Servicio del centro de raparación';
      $language['es']['suporte']             = 'Soporte y atención (Recepción)';
      $language['es']['tempo_resposta']      = 'Tiempo de respuesta';
      $language['es']['padrao_servico']      = 'Estándares de calidad y servicio';
      $language['es']['qualidade_reparo']    = 'Calidade de la reparación';
      $language['es']['tempo_reparo']        = 'Tiempo de reparación';
      $language['es']['falha_equipamento']   = 'Falla Temprana del equipo';
      $language['es']['qualidade_produto']   = 'Calidade del producto';
      $language['es']['atencao_suporte']     = 'Atención y soporte';
      $language['es']['orcamento']           = 'Costo y/ó presupuesto de reparación';
      $language['es']['acompanhamento']      = 'Seguimiento de la reparación';

      // NOVAS RESPOSTAS DA QUESTÃO 7 - DISPONÍVEL A PARTIR DE 2016
      $language['es']['atencao_suporte_telefonico'] = 'Atención y soporte (Telefónica)';
      $language['es']['atencao_suporte_recepcao']   = 'Atención y soporte (Recepción del centro de servicio)';
      $language['es']['falha_precoce_ferramenta']   = 'Falla temprana de la herramienta';
      $language['es']['qualidade_do_produto']       = 'Calidad del producto';
      $language['es']['tempo_de_resposta']          = 'Tiempo de respuesta';
      $language['es']['custo_orcamento_reparo']     = 'Costo y/ó presupuesto de reparación';
      $language['es']['rastreamento_reparacao']     = 'Seguimiento de la reparación';
      $language['es']['tempo_repado']               = 'Tiempo de reparación';
      $language['es']['qualidade_reparacao']        = 'Calidad de la reparación';
      $language['es']['servico_prestado_centro']    = 'Servicio prestado por el centro de servicio';

      $language['es']['questao_nove']   = 'Tiempo de la reparación';
      $language['es']['questao_dez']    = 'Precio de la reparación';
      $language['es']['questao_onze']   = 'Calidad de la reparación';
      $language['es']['questao_doze']   = 'Actitud del personal';
      $language['es']['questao_treze']  = 'Explicación de la reparación';
      $language['es']['questao_quartoze'] = 'Aspecto de las instalaciones de servicio';
      $language['es']['questao_quinze']   = 'Satisfacción General';

      $language['es']['questao_nove_grafico']   = utf8_encode('Tiempo de la reparación');
      $language['es']['questao_dez_grafico']    = utf8_encode('Precio de la reparación');
      $language['es']['questao_onze_grafico']   = utf8_encode('Calidad de la reparación');
      $language['es']['questao_doze_grafico']   = utf8_encode('Actitud del personal');
      $language['es']['questao_treze_grafico']  = utf8_encode('Explicación de la reparación');
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
      $language['es']['resposta_numero_9'] = 'más 8';
    }
      // break;
  }
}

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

</style>

<script src="js/jquery-1.8.3.min.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src="js/highcharts_4.0.3.js"></script>
<script src="js/exporting.js"></script>
<script type="text/javascript">

  function carregaComboPesquisa(valor, pesquisa){
      $.ajax({
          type: "POST",
          url: "relatorio_pesquisas_chamado_new.php",
          data: {carregaComboPesquisa:true, valor: valor, pesquisa:pesquisa},
          success: function(msg) {
            $("#nome_pesquisa").html(msg);
          }
        });

    }


  $(function() {

    $('#data_inicial').datepick({startDate:'01/01/2000'});
    $('#data_final').datepick({startDate:'01/01/2000'});
    $("#data_inicial").maskedinput("99/99/9999");
    $("#data_final").maskedinput("99/99/9999");

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

    //EXIBE RESPOSTA
    $('.btn_ver_resposta').click(function(){
        relBtn      = $(this).attr('rel');
        var btn     = $(this);
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
                $(btn).html(' Ver Respostas <img src="imagens/barrow_up.png"> ');
                $('tr#'+quebraRel[0]+' td table').html(data);
            });
        }else{
            $('tr#'+quebraRel[0]).toggle('slow');
            $('tr#'+quebraRel[0]).addClass('hideTr');
            $(btn).html(' Ver Respostas <img src="imagens/barrow_down.png"> ');
        }
    });


    //EXIBE RESPOSTA
    $('.btn_ver_resposta_enviado_email').click(function(){
        relBtn      = $(this).attr('rel');
        quebraRel   = relBtn.split("|");
        if ($('tr#'+quebraRel[0]).hasClass('hideTr')){
            $.ajax({
                type:"GET",
                url:"relatorio_pesquisa_laudo_tecnico_ajax.php",
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
                $('tr#'+quebraRel[0]+' td table').html(data);
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

      var pesquisa_valor = $(".radio_pesquisa:checked").val();
      carregaComboPesquisa(pesquisa_valor);

      if($(this).val() == "america_latina"){
        var check_idioma = "";
        // radio_idioma = '<fieldset><legend>Pesquisa:</legend><input type="radio" name="pesquisa_idioma" id="pesquisa_idioma" value="todos_idiomas"> <label for="pesquisa_idioma">Todos</label><br>';
        // radio_idioma += '<input type="radio" name="pesquisa_idioma" id="pesquisa_idioma" value="es"> <label for="pesquisa_idioma">Espanhol</label><br>';
        // radio_idioma += '<input type="radio" name="pesquisa_idioma" id="pesquisa_idioma" value="en"> <label for="pesquisa_idioma">Inglês</label><br>';
        // radio_idioma += '<input type="radio" name="pesquisa_idioma" id="pesquisa_idioma" value="pt"> <label for="pesquisa_idioma">Português</label><br></fieldset>';
        // Monteiro //
        check_idioma = '<fieldset><legend>Pesquisa:</legend>';
        check_idioma += '<input type="checkbox" name="pesquisa_idioma[]" id="pesquisa_idioma" value="es"> <label for="pesquisa_idioma">Espanhol</label><br>';
        check_idioma += '<input type="checkbox" name="pesquisa_idioma[]" id="pesquisa_idioma" value="en"> <label for="pesquisa_idioma">Inglês</label><br>';
        check_idioma += '<input type="checkbox" name="pesquisa_idioma[]" id="pesquisa_idioma" value="pt"> <label for="pesquisa_idioma">Português</label><br></fieldset>';
        // Fim Monteiro //
        $('#pesquisa_idioma > td[rel=idioma]').html(check_idioma);

      // }else if($(this).val() == "externo_email"){
      //  $('#pesquisa_idioma > td[rel=idioma]').html('<label for="posto_local">Buscar O.S.</label><br><input type="text" name="pesquisa_os" id="pesquisa_os" class="frm" value="">');

      }else{
        $('#pesquisa_idioma > td[rel=idioma]').html("");
      }
    });

  });


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

function createChartPesquisa(textoChart,perguntas,respostas){

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

    if(data != null){
      var data = json.responseText;
    }
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
      <th colspan='6'><?=traduz('Parâmetros de Pesquisa')?></th>
    </tr>

    <tr>
      <td colspan='6'>&nbsp;</td>
    </tr>

    <tr>
      <td>&nbsp;</td>
      <td>
        <label for="data_inicial"><?=traduz('Data Inicial:')?></label><br>
        <input type="text" name="data_inicial" id="data_inicial" class='frm' size="12" value="<?=$data_inicial?>">
      </td>
      <td>
        <label for="data_final"><?=traduz('Data Final:')?></label><br>
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
        <label for="codigo_posto"><?=traduz('Posto Código:')?></label><br>
        <input type="text" name="codigo_posto" id="codigo_posto" class='frm' value="<?=$codigo_posto?>">
      </td>
      <td>
        <label for="posto_nome"><?=traduz('Posto Nome:')?></label><br>
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
          <label for="posto_linha"><?=traduz('Linha')?></label><br>
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
          <label for="posto_local"><?=traduz('Local')?></label><br>
          <input type="text" name="posto_local" id="posto_local" class='frm' value="<?=$posto_local?>">
          <input type="text" name="posto_estado" id="posto_estado" readonly="readonly" size="2" class='frm' value="<?=$posto_estado?>">
        </td>
        <td>&nbsp;</td>
      </tr>
      <tr class="dados_pesquisa_hd" <?=$display_hd?>>
        <td>&nbsp;</td>
        <td colspan="">
          <label for="hd_chamado"><?=traduz('Atendimento')?></label><br />
          <input type="text" name="hd_chamado" id="hd_chamado" class='frm' value="<?=$hd_chamado?>">
        </td>

        <?php
          if($login_fabrica == 94){ //hd_chamado=2414398
            $sqlPesquisa = "SELECT  tbl_pesquisa.pesquisa,
                            tbl_pesquisa.descricao,
                            tbl_pesquisa.ativo
                        FROM tbl_pesquisa
                        WHERE tbl_pesquisa.fabrica = $login_fabrica
                        AND tbl_pesquisa.ativo IS TRUE
                        AND tbl_pesquisa.categoria LIKE 'callcenter%'";
            $resPesquisa = pg_query($con, $sqlPesquisa);
        ?>
            <td>
              <label for="data_inicial"><?=traduz('Tipo Pesquisa:')?></label><br>
              <select class='frm' name="tipo_pesquisa_callcenter" id="tipo_pesquisa_callcenter">
                <option value=""><?=traduz('Selecione')?></option>
                <?php
                  for ($i = 0; $i < pg_num_rows($resPesquisa);$i++){
                    $pesquisaID   = pg_result($resPesquisa,$i,'pesquisa');
                    $pesquisaDesc   = pg_result($resPesquisa,$i,'descricao');
                    $selected_pesquisa = ($pesquisaID == $pesquisa_selecionada) ? "SELECTED" : null;
                ?>
                      <option value="<?=$pesquisaID?>" <?=$selected_pesquisa?> ><?=$pesquisaDesc?></option>
                <?php
                  }
                ?>
              </select>
            </td>
            <td>&nbsp;</td>
        <?php
          } else {
        ?>
          <td>&nbsp;</td>
        <?php } ?>
      </tr>
      <?
    }
    ?>

    <tr>
      <td>&nbsp;</td>
      <td colspan="2">
        <fieldset>
          <legend><?=traduz('Tipo Pesquisa:')?></legend>
          <?php

          if(!in_array($login_fabrica,array(1,35,85,94,129,138,145,151,152,160,161,180,181,182)) and !$replica_einhell){
            $sql = "SELECT  tbl_pesquisa.descricao,
            tbl_pesquisa.pesquisa,
            tbl_pesquisa.categoria
            FROM    tbl_pesquisa
            WHERE   tbl_pesquisa.fabrica    = $login_fabrica
            ";
          } else {
            $sql = "SELECT  DISTINCT
            tbl_pesquisa.categoria
            FROM    tbl_pesquisa
            WHERE   tbl_pesquisa.fabrica = $login_fabrica
            ";
          }
          $res = pg_query($con,$sql);

        if (pg_num_rows($res)>0) {
            if ($_POST['pesquisa'] == 'TODOS'){
              $checked = "CHECKED";
            }

            if (!in_array($login_fabrica,array(1,35,85,94,129,138,145,151,152,160,161,180,181,182)) and !$replica_einhell) {
?>
              <input type="radio" name="pesquisa" id="PesquisaTodos" <?=$checked?> value="TODOS" > <label for="PesquisaTodos">TODOS</label>
              <br>
<?
                for ($i=0; $i < pg_num_rows($res); $i++) {

                $pesquisa_id        = pg_fetch_result($res, $i, 'pesquisa');
                $descricao_pesquisa = pg_fetch_result($res, $i, 'descricao');
                $categoria          = pg_fetch_result($res, $i, 'categoria');

                $checked = ($pesquisa == $pesquisa_id."_".$categoria) ? "CHECKED" : '' ;
                ?>
                <input type="radio" name="pesquisa" id="<?=$pesquisa_id?>" <?=$checked?> value='<?=$pesquisa_id."|".$categoria?>'>
                <label for="<?=$pesquisa_id?>"><?=$descricao_pesquisa?></label>
                <br>

<?
                }
            } else {
                for ($i=0; $i < pg_num_rows($res); $i++) {
                    $categoria = pg_fetch_result($res,$i,categoria);

                    $checked = ($pesquisa == $categoria) ? "checked" : '' ;

                    if (in_array($login_fabrica,array(1,129,151,152,180,181,182))) {
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

                            default:
                            $categoria_desc = $categoria;
                            break;
                        }
                    }else{
                        if ($categoria == "ordem_de_servico") {
                            $categoria_desc = "Ordem de Serviço";
                        }elseif ($categoria == "externo") {
                            $categoria_desc = "E-mail Call-Center";
                        }elseif($categoria == "ordem_de_servico_email"){
				$categoria_desc = "Ordem de Serviço - E-mail";
			}elseif($categoria == "externo_outros"){
				$categoria_desc = "E-mail Consumidor - Pós Venda";
                        }elseif ($login_fabrica == 151) {
                            $categoria_desc = pg_fetch_result($res,$i,'descricao');
                        }else {
                            $categoria_desc = $categoria;
                        }
                    }
                ?>
                <input type="radio" class="radio_pesquisa" name="pesquisa" id="<?=$categoria?>" <?=$checked?> value='<?=$categoria?>'>
                <label for="<?=$categoria?>"><?=ucfirst($categoria_desc)?></label>
                <br>

                <?
                }
              if($login_fabrica == 1){
                ?>
                <input type="radio" class="radio_pesquisa" name="pesquisa" id="externo_email" <?php echo $pesquisa == 'externo_email' ? 'checked' : '' ; ?> value='externo_email'>
                <label for="externo_email">Brasil</label>
                <br>
                <input type="radio" class="radio_pesquisa" name="pesquisa" id="america_latina" <?php echo $pesquisa == 'america_latina' ? 'checked' : '' ; ?> value='america_latina'>
                <label for="america_latina">América Latina</label>
                <br>
                <?
              }

            }
          }
          ?>
        </fieldset>
      </td>
	</tr>
	<? if ($login_fabrica == 129) { ?>
    <tr>
      <td>&nbsp;</td>
      <td>Pesquisas</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>
        <select name="nome_pesquisa" id="nome_pesquisa">
            <option value="" >Selecione uma pesquisa</option>
            <?=$option?>
        </select>


      </td>
    </tr>
<? } ?>

    <tr id="pesquisa_idioma">
      <td>&nbsp;</td>
      <td rel="idioma" colspan="2">
        <?php if($pesquisa == "america_latina"){

          $idiomas = $_POST['pesquisa_idioma'];
          foreach ($idiomas as $key => $value) {
            switch ($value) {
              case 'es':
                $pesquisa_idioma_es = "es";
                break;
              case 'en':
                $pesquisa_idioma_en = "en";
                break;
              case 'pt':
                $pesquisa_idioma_pt = "pt";
                break;
            }
          }
          ?>
          <fieldset>
            <legend>Pesquisa:</legend>
            <!-- <input type="radio" name="pesquisa_idioma" id="pesquisa_idioma" <?php echo $pesquisa_idioma == "todos_idiomas" ? 'checked' : ''; ?> value="todos_idiomas" > <label for="pesquisa_idioma">Todos</label>
            <br>
            <input type="radio" name="pesquisa_idioma" id="pesquisa_idioma" <?php echo $pesquisa_idioma == "es" ? 'checked' : ''; ?> value="es" > <label for="pesquisa_idioma">Espanhol</label>
            <br>
            <input type="radio" name="pesquisa_idioma" id="pesquisa_idioma" <?php echo $pesquisa_idioma == "en" ? 'checked' : ''; ?> value="en" > <label for="pesquisa_idioma">Inglês</label>
            <br>
            <input type="radio" name="pesquisa_idioma" id="pesquisa_idioma" <?php echo $pesquisa_idioma == "pt" ? 'checked' : ''; ?> value="pt" > <label for="pesquisa_idioma">Português</label>
            <br> -->

            <input type="checkbox" name="pesquisa_idioma[]" id="pesquisa_idioma" <?php echo $pesquisa_idioma_es == "es" ? 'checked' : ''; ?> value="es" > <label for="pesquisa_idioma">Espanhol</label>
            <br>
            <input type="checkbox" name="pesquisa_idioma[]" id="pesquisa_idioma" <?php echo $pesquisa_idioma_en == "en" ? 'checked' : ''; ?> value="en" > <label for="pesquisa_idioma">Inglês</label>
            <br>
            <input type="checkbox" name="pesquisa_idioma[]" id="pesquisa_idioma" <?php echo $pesquisa_idioma_pt == "pt" ? 'checked' : ''; ?> value="pt" > <label for="pesquisa_idioma">Português</label>
            <br>
          </fieldset>
          <?
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
        <input type="button" value='<?=traduz("Pesquisar")?>' id="btn_pesquisa">
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
        $msg_error = traduz("Favor, escolha um tipo de pesquisa");
    }
    $pesquisa_categoria = (isset($pesquisa)) ? $pesquisa : $categoria;
#echo "->>".$pesquisa_categoria." :: ".$pesquisa."<br>";
  if(strlen($msg_error) > 0){ //hd_chamado=2414398
  ?>
    <div class="msg_erro"><?=$msg_error?></div>
  <?
    require_once "rodape.php";
    exit;
  }

  if(in_array($login_fabrica,[138,161])){
    $cond_categoria = " AND pesquisa.categoria = '$pesquisa_categoria' ";
  }

  //PESQUISA OS CHAMADOS DE ACORDO COM OS PARÂMETROS PASSADOS
    if (!in_array($pesquisa_categoria,array("posto","ordem_de_servico","ordem_de_servico_email","externo_email","externo_outros","america_latina", "recadastramento"))) {

        if ($pesquisa_categoria == "externo") {
            $joinAdmin = "JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente";
        } else {
            $joinAdmin = "LEFT JOIN    tbl_admin               ON tbl_admin.admin              = tbl_resposta.admin";
        }

        $sql = "
        SELECT  DISTINCT
                tbl_hd_chamado.hd_chamado                                           ,
                TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data   ,
                pesquisa.descricao                                                  ,
                pesquisa.pesquisa                                                   ,
                tbl_admin.nome_completo
        FROM    tbl_hd_chamado
        JOIN    tbl_hd_chamado_extra    ON tbl_hd_chamado.hd_chamado    = tbl_hd_chamado_extra.hd_chamado
        JOIN    tbl_resposta            ON tbl_resposta.hd_chamado      = tbl_hd_chamado.hd_chamado
        $joinAdmin
        JOIN    tbl_pergunta            ON tbl_resposta.pergunta        = tbl_pergunta.pergunta
        LEFT JOIN    tbl_pesquisa_pergunta   ON tbl_pergunta.pergunta        = tbl_pesquisa_pergunta.pergunta
        JOIN    tbl_pesquisa pesquisa   ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
                                        AND pesquisa.pesquisa           = tbl_resposta.pesquisa
                                        AND pesquisa.fabrica            = $login_fabrica
        WHERE   tbl_hd_chamado.status   = 'Resolvido'
        AND     tbl_hd_chamado.fabrica  = $login_fabrica
        AND     tbl_resposta.pesquisa   IS NOT NULL
        $conditionChamado
        $conditionChamado2
        $cond_categoria
        AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'

        ";
    } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email","externo_email","externo_outros","america_latina"))) {
        $sql = "
        SELECT  DISTINCT
                TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data   ,
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
        JOIN    tbl_pesquisa pesquisa   ON  pesquisa.pesquisa           = tbl_pesquisa_pergunta.pesquisa
                                        AND pesquisa.pesquisa           = tbl_resposta.pesquisa
                                        AND pesquisa.fabrica            = $login_fabrica
        WHERE   tbl_posto_fabrica.credenciamento    IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
        AND     tbl_resposta.pesquisa               IS NOT NULL
        AND     tbl_resposta.data_input             BETWEEN '$aux_data_inicial' AND '$aux_data_final'
        $conditionPosto
        $conditionLinha
        $conditionLocal
        $conditionChamado2
        $cond_categoria
        ";
    } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
        $sql = "
        SELECT  DISTINCT
                tbl_os.os,
                tbl_os.sua_os,
                TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data,
                tbl_posto.nome AS posto_nome,
                tbl_posto.cnpj,
                tbl_posto.posto,
                pesquisa.descricao,
                pesquisa.pesquisa
        FROM    tbl_resposta
        JOIN    tbl_os                  ON  tbl_os.os               = tbl_resposta.os
                                        AND tbl_os.fabrica          = {$login_fabrica}
        JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        JOIN    tbl_posto               ON  tbl_posto.posto         = tbl_posto_fabrica.posto
        JOIN    tbl_pergunta            ON  tbl_resposta.pergunta   = tbl_pergunta.pergunta
        JOIN    tbl_pesquisa_pergunta   ON  tbl_pergunta.pergunta   = tbl_pesquisa_pergunta.pergunta
        JOIN    tbl_pesquisa pesquisa   ON  pesquisa.pesquisa       = tbl_pesquisa_pergunta.pesquisa
                                        AND pesquisa.pesquisa       = tbl_resposta.pesquisa
                                        AND pesquisa.fabrica        = $login_fabrica
        WHERE   tbl_posto_fabrica.credenciamento    IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
        AND     tbl_resposta.pesquisa               IS NOT NULL
        AND     tbl_resposta.data_input             BETWEEN '$aux_data_inicial' AND '$aux_data_final'
        $conditionPosto
        $conditionLocal
        $conditionChamado2
        $cond_categoria
        ";
    } else if (in_array($pesquisa_categoria,array("externo_email","america_latina")) && $pesquisa_idioma != "todos_idiomas") {

        $sql = "SELECT tbl_os.os,
                tbl_os.sua_os,
                tbl_posto_fabrica.codigo_posto,
                tbl_laudo_tecnico_os.observacao,
                tbl_laudo_tecnico_os.os AS pais,
                tbl_laudo_tecnico_os.os AS cidade,
                to_char(tbl_laudo_tecnico_os.data,'DD/MM/YYYY') AS data
            FROM tbl_os
                JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os ";


        if(in_array($pesquisa_categoria,array("externo_email"))){
            $sql .= "JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.enviar_email = 't'";
        }
        $sql .= "JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_laudo_tecnico_os.data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";

        // if($pesquisa == "externo_email" AND $pesquisa_os != ""){
          // $sql .= "AND tbl_os.sua_os = '$pesquisa_os'";

        // }else
        if(in_array($pesquisa_categoria,array("america_latina"))){
            $sql .= " AND tbl_laudo_tecnico_os.observacao LIKE '$pesquisa_grafico'";
            $sql .= " AND tbl_laudo_tecnico_os.observacao LIKE ANY ($pesquisa_language) AND tbl_os.posto = 6359";

                //AND tbl_laudo_tecnico_os.observacao LIKE '$pesquisa_language' AND tbl_os.posto = 6359";

        }
        $sql .= " AND titulo ILIKE 'Pesquisa de%' ORDER BY tbl_laudo_tecnico_os.data ";

    } else if (in_array($pesquisa_categoria,array("externo_outros"))) {
        $sql = "
            SELECT  DISTINCT
                    TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data   ,
                    pesquisa.descricao                                                  ,
                    pesquisa.pesquisa                                                   ,
                    TO_CHAR(tbl_venda.data_nf, 'DD/MM/YYYY') AS data_compra   ,
                    tbl_venda.venda                                                     ,
                    tbl_cliente.nome AS cliente
            FROM    tbl_resposta
            JOIN    tbl_pesquisa pesquisa   USING(pesquisa)
            JOIN    tbl_venda               USING(fabrica,venda)
            JOIN    tbl_cliente             USING(cliente)
            JOIN    tbl_pergunta            USING(pergunta)
            JOIN    tbl_pesquisa_pergunta   USING(pergunta)
            WHERE   pesquisa.fabrica        = $login_fabrica
            AND     tbl_resposta.data_input BETWEEN '$aux_data_inicial' AND '$aux_data_final'
            $cond_categoria
        ";
    }
    if($login_fabrica == 129 and strlen(trim($nome_pesquisa))> 0 ){
        $sql .= " AND pesquisa.pesquisa = $nome_pesquisa ";
        $condPesquisa = " AND tbl_pesquisa.pesquisa = $nome_pesquisa ";
    }
    $sql .= "ORDER BY pesquisa.pesquisa ";

    $resTabela = pg_query($con,$sql);

    if (pg_num_rows($resTabela) > 0) {

        if (!in_array($pesquisa_categoria,array("externo_email","america_latina"))) {
                //PROGRAMA QUE VAI GERAR O XLS
        if ($login_fabrica == 94) {
            include_once 'relatorio_pesquisas_chamado_xls_eve.php';
        } else {
            include_once 'relatorio_pesquisas_chamado_xls_new.php';
        }

        $sqlGrafico = "
        SELECT    distinct pesquisa    ,
                    tbl_pesquisa.descricao
		FROM      tbl_pesquisa
		join tbl_resposta using(pesquisa)
        WHERE     fabrica = $login_fabrica
        AND     tbl_resposta.data_input             BETWEEN '$aux_data_inicial' AND '$aux_data_final'
        $conditionPesquisa
        $condPesquisa
        $conditionPesquisaSelecionada";

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
    <?php
            if (!in_array($pesquisa_categoria,array('posto',"ordem_de_servico","ordem_de_servico_email",'externo_outros', "recadastramento"))) {
    ?>
                <th><?=traduz('Atendimento')?></th>
    <?php
            } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email",'externo_outros'))) {
    ?>
                <th><?=traduz('CNPJ')?></th>
    <?php
            } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
    ?>
                <th><?=traduz('OS')?></th>
                <th><?=traduz('CNPJ')?></th>
                <th><?=traduz('Posto')?></th>
    <?php
            } else if (in_array($pesquisa_categoria,array('externo_outros'))) {
    ?>
                <th><?=traduz('Cliente')?></th>
                <th><?=traduz('Data Compra')?></th>
    <?php
            }
    ?>
            <th><?=traduz('Data Resposta')?></th>
            <?
            if (!in_array($pesquisa_categoria,array('posto',"ordem_de_servico","ordem_de_servico_email","externo_outros","recadastramento"))) {
                ?>
                <th><?=traduz('Atendente')?></th>
                <?
            } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email","externo_outros"))) {
                ?>
                <th><?=traduz('Posto')?></th>
                <?
            }
            ?>
            <th><?=traduz('Pesquisa')?></th>
            <th><?=traduz('Ação')?></th>
            </tr>

            <?

            $i = 0;
            $respostasPergunta = array();
            //percorre o array da consulta principal 1ª vez para jogar as respostas em um array
    //         print_r(pg_fetch_all($resTabela));exit;
            foreach (pg_fetch_all($resTabela) as $key) {
                if (in_array($login_fabrica, [138,161])) {
                    $condPesquisaMedia = " AND tbl_pesquisa.pesquisa = ".$key['pesquisa']." ";
                }
                if(!in_array($pesquisa_categoria,array('posto',"ordem_de_servico","ordem_de_servico_email","externo_outros", "recadastramento"))){
                    $sql = "SELECT  pergunta,
                    txt_resposta,
                    tipo_resposta_item,
                    hd_chamado
                    FROM    tbl_resposta
                    JOIN    tbl_pesquisa USING (pesquisa)
                    WHERE   hd_chamado = ".$key['hd_chamado']."
                    $conditionPesquisa
                    $condPesquisaMedia
                    ORDER BY      pergunta";
                } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email","externo_outros"))) {
                    $sql = "SELECT  pergunta,
                    txt_resposta,
                    tipo_resposta_item,
                    posto
                    FROM    tbl_resposta
                    JOIN    tbl_pesquisa using (pesquisa)
                    WHERE   posto = ".$key['posto']."
                    $conditionPesquisa
                    $condPesquisaMedia
                    ORDER BY      pergunta";

                } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
                    $sql = "SELECT  pergunta,
                    txt_resposta,
                    tipo_resposta_item,
                    os
                    FROM    tbl_resposta
                    JOIN    tbl_pesquisa using (pesquisa)
                    WHERE   os = ".$key['os']."
                    $conditionPesquisa
                    $condPesquisaMedia
                    ORDER BY      pergunta";
                } else if (in_array($pesquisa_categoria,array("externo_outros"))) {
                    $sql = "SELECT  pergunta,
                                    txt_resposta,
                                    tipo_resposta_item,
                                    venda
                            FROM    tbl_resposta
                            JOIN    tbl_pesquisa using (pesquisa)
                            WHERE   os IS NULL
                            AND     hd_chamado IS NULL
                            AND     venda = ".$key['venda'];
                }

                $resRespostas = pg_query($con,$sql);

                if (pg_num_rows($resRespostas)>0) {
                    foreach (pg_fetch_all($resRespostas) as $keyRespostas) {

                        if (!in_array($pesquisa_categoria,array('posto',"ordem_de_servico","ordem_de_servico_email", "recadastramento"))) {
                            $local = $keyRespostas['hd_chamado'];
                        } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
                            $local = $keyRespostas['posto'];
                        } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
                            $local = $keyRespostas["os"];
                        } else if (in_array($pesquisa_categoria,array("externo_outros"))) {
                            $local = $keyRespostas["venda"];
                        }

                        if (!empty($keyRespostas['tipo_resposta_item'])) {

                            $respostasPergunta[$key['pesquisa']][$local][$keyRespostas['pergunta']]['respostas'][] = $keyRespostas['tipo_resposta_item'];

                        } else {
                            $respostasPergunta[$key['pesquisa']][$local][$keyRespostas['pergunta']]['respostas'][] = $keyRespostas['txt_resposta'];
                            $mediaPesquisa[$key['pesquisa']][] = $keyRespostas['txt_resposta'];
                        }
                    }
                }
            }

            //echo "<pre>";print_r($respostasPergunta);echo "</pre>";
            $pesquisa_anterior = null;
            $totalPesquisas = pg_fetch_all($resTabela);
            $countTotalPesquisas =count($totalPesquisas);
            $contador = 0;

            foreach (pg_fetch_all($resTabela) as $key) {
            //echo "<pre>";print_r($key);echo "</pre>";
                if (in_array($login_fabrica, [161]) && $pesquisa_anterior != $key['pesquisa'] && $pesquisa_anterior != null) { ?>
            <tr>
                <td colspan="100%">
                    Média da Satisfação nesse período Consultado: <?=number_format( (array_sum($mediaPesquisa[$key['pesquisa']])/ count($mediaPesquisa[$key['pesquisa']]) ), 2, ",", ".")?>
                </td>
            </tr>
    <?php
                }
                if (!in_array($pesquisa_categoria,array('posto',"ordem_de_servico","ordem_de_servico_email","externo_outros", "recadastramento"))) {
                    $local = $key['hd_chamado'];
                } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email","externo_outros"))) {
                    $local = $key['posto'];
                } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
                    $local = $key["os"];
                } else if (in_array($pesquisa_categoria,array("externo_outros"))) {
                    $local = $key["venda"];
                }
    ?>
            <tr>

    <?php
                if (!in_array($pesquisa_categoria,array('posto',"ordem_de_servico","ordem_de_servico_email","externo_outros", "recadastramento"))) {
    ?>
                <td> <a href="callcenter_interativo_new.php?callcenter=<?=$local?>" target='_blank'> <?=$local?></a></td>
    <?php
                } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email","externo_outros"))) {
    ?>
                <td><?=$key['cnpj']?></td>
    <?php
                } else if (in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email"))) {
    ?>
                <td> <a href="os_press.php?os=<?=$local?>" target='_blank'> <?=$key["sua_os"]?></a></td>
                <td><?=$key['cnpj']?></td>
                <td><?echo $key['posto_nome']?></td>
    <?php
                } else if (in_array($pesquisa_categoria,array('externo_outros'))) {
    ?>
                <td><?=$key['cliente']?></td>
                <td><?=$key['data_compra']?></td>
    <?php
                }
    ?>
                <td><?echo $key['data']?></td>
    <?php
                if (!in_array($pesquisa_categoria,array('posto',"ordem_de_servico","ordem_de_servico_email","externo_outros", "recadastramento"))){
    ?>
                <td><?echo $key['nome_completo']?></td>
    <?php
                } else if (!in_array($pesquisa_categoria,array("ordem_de_servico","ordem_de_servico_email","externo_outros"))) {
    ?>
                <td><?echo $key['posto_nome']?></td>
    <?php
                }
    ?>
                <td><?echo $key['descricao']?></td>
                <td>
                <button class="btn_ver_resposta" rel="<?=$local?>|<?=$key['pesquisa']?>|<?=$pesquisa_categoria?>"> <?=traduz('Ver Respostas')?> <img src="imagens/barrow_down.png"> </button>
                </td>
            </tr>
            <tr class='hideTr' id='<?=$local?>' >
                <td colspan="100%">
                <table style="width:100%">
                </table>
                </td>
            </tr>
    <?
                $contador++;
                if ($contador == $countTotalPesquisas) {
                    if (in_array($login_fabrica, [161])) {
    ?>
            <tr>
                <td colspan="100%">
                Média da Satisfação nesse período Consultado: <?=number_format( (array_sum($mediaPesquisa[$key['pesquisa']])/ count($mediaPesquisa[$key['pesquisa']]) ), 2, ",", ".")?>
                </td>
            </tr>
    <?php
                    }
                }
                $pesquisa_anterior = $key['pesquisa']; 
            }
            ?>
        </table>
<?php
        } else {
            $os     = pg_fetch_result($resTabela,0,os);
            $data     = pg_fetch_result($resTabela,0,data);
            $observacao = pg_fetch_result($resTabela,0,observacao);

            if($pesquisa_idioma == "todos_idiomas" && $pesqusia = "america_latina"){
                    //PROGRAMA QUE VAI GERAR O XLS
                include_once 'relatorio_pesquisas_laudo_todos_idiomas_xls.php';
            }else{
                include_once 'relatorio_pesquisas_laudo_tecnico_xls.php';
                switch ($pesquisa_idioma) {
                case 'pt':$resposta['equipamento']['sem_fio'] = 0;
                    $resposta['equipamento']['furadeira'] = 0;
                    $resposta['equipamento']['impacto'] = 0;
                    $resposta['equipamento']['parafusadeira'] = 0;
                    $resposta['equipamento']['concreto'] = 0;
                    $resposta['equipamento']['serras'] = 0;
                    $resposta['equipamento']['marmore'] = 0;
                    $resposta['equipamento']['mecanica'] = 0;
                    $resposta['equipamento']['madeira'] = 0;
                    $resposta['equipamento']['jardinagem'] = 0;
                    $resposta['equipamento']['outro'] = 0;
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
                    $resposta['equipamento']['hammer'] = 0;
                    $resposta['equipamento']['drill'] = 0;
                    $resposta['equipamento']['metalworking'] = 0;
                    $resposta['equipamento']['woodworking'] = 0;
                    $resposta['equipamento']['machinery'] = 0;
                    $resposta['equipamento']['lawn_garden'] = 0;
                    $resposta['equipamento']['gasoline_explosion'] = 0;
                    $resposta['equipamento']['pneumatic'] = 0;
                    $resposta['equipamento']['other'] = 0;
                    break;
                }

                $resposta['recomendacao']['resposta_0'] = 0;
                $resposta['recomendacao']['resposta_1'] = 0;
                $resposta['recomendacao']['resposta_2'] = 0;
                $resposta['recomendacao']['resposta_3'] = 0;
                $resposta['recomendacao']['resposta_4'] = 0;
                $resposta['recomendacao']['resposta_5'] = 0;
                $resposta['recomendacao']['resposta_6'] = 0;
                $resposta['recomendacao']['resposta_7'] = 0;
                $resposta['recomendacao']['resposta_8'] = 0;
                $resposta['recomendacao']['resposta_9'] = 0;
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
                $resposta['nota_tempo_reparo']['muito_satisfeito'] = 0;
                $resposta['nota_tempo_reparo']['satisfeito'] = 0;
                $resposta['nota_tempo_reparo']['pouco_satisfeito'] = 0;
                $resposta['nota_tempo_reparo']['insatisfeito'] = 0;

                $resposta['nota_preco_reparo']['plenamente_satisfeito'] = 0;
                $resposta['nota_preco_reparo']['muito_satisfeito'] = 0;
                $resposta['nota_preco_reparo']['satisfeito'] = 0;
                $resposta['nota_preco_reparo']['pouco_satisfeito'] = 0;
                $resposta['nota_preco_reparo']['insatisfeito'] = 0;

                $resposta['nota_qualidade_reparo']['plenamente_satisfeito'] = 0;
                $resposta['nota_qualidade_reparo']['muito_satisfeito'] = 0;
                $resposta['nota_qualidade_reparo']['satisfeito'] = 0;
                $resposta['nota_qualidade_reparo']['pouco_satisfeito'] = 0;
                $resposta['nota_qualidade_reparo']['insatisfeito'] = 0;

                $resposta['nota_atencao']['plenamente_satisfeito'] = 0;
                $resposta['nota_atencao']['muito_satisfeito'] = 0;
                $resposta['nota_atencao']['satisfeito'] = 0;
                $resposta['nota_atencao']['pouco_satisfeito'] = 0;
                $resposta['nota_atencao']['insatisfeito'] = 0;

                $resposta['nota_explicacao']['plenamente_satisfeito'] = 0;
                $resposta['nota_explicacao']['muito_satisfeito'] = 0;
                $resposta['nota_explicacao']['satisfeito'] = 0;
                $resposta['nota_explicacao']['pouco_satisfeito'] = 0;
                $resposta['nota_explicacao']['insatisfeito'] = 0;

                $resposta['nota_aspecto']['plenamente_satisfeito'] = 0;
                $resposta['nota_aspecto']['muito_satisfeito'] = 0;
                $resposta['nota_aspecto']['satisfeito'] = 0;
                $resposta['nota_aspecto']['pouco_satisfeito'] = 0;
                $resposta['nota_aspecto']['insatisfeito'] = 0;

                $resposta['nota_geral']['plenamente_satisfeito'] = 0;
                $resposta['nota_geral']['muito_satisfeito'] = 0;
                $resposta['nota_geral']['satisfeito'] = 0;
                $resposta['nota_geral']['pouco_satisfeito'] = 0;
                $resposta['nota_geral']['insatisfeito'] = 0;

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
                for($x=0; $x<pg_num_rows($resTabela); $x++){
                $resultado_resposta = pg_fetch_result($resTabela,$x,observacao);
                            // $resultado_resposta = str_replace("\&#039;","", htmlspecialchars_decode($resultado_resposta));
                            $resultado_resposta = json_decode(utf8_encode(stripslashes($resultado_resposta)));

                if ($pesquisa_idioma == 'pt') {
                    switch ($resultado_resposta->equipamento) {
                    case 'sem_fio':     $resposta['equipamento']['sem_fio']++;  break;
                    case 'furadeira':   $resposta['equipamento']['furadeira']++;  break;
                    case 'impacto':     $resposta['equipamento']['impacto']++;  break;
                    case 'parafusadeira': $resposta['equipamento']['parafusadeira']++;  break;
                    case 'concreto':      $resposta['equipamento']['concreto']++;  break;
                    case 'serras':        $resposta['equipamento']['serras']++;  break;
                    case 'marmore':       $resposta['equipamento']['marmore']++;  break;
                    case 'mecanica':      $resposta['equipamento']['mecanica']++;  break;
                    case 'madeira':       $resposta['equipamento']['madeira']++;  break;
                    case 'jardinagem':    $resposta['equipamento']['jardinagem']++;  break;
                    case 'outro':       $resposta['equipamento']['outro']++; break;
                    }
                }else if($pesquisa_idioma == 'es'){
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
                }else if($pesquisa_idioma == 'en'){
                    switch ($resultado_resposta->equipamento) {
                    case 'cordless'      :  $resposta['equipamento']['cordless']++;  break;
                    case 'hammer'    :  $resposta['equipamento']['hammer']++;  break;
                    case 'drill'   :  $resposta['equipamento']['drill']++;  break;
                    case 'metalworking'          :  $resposta['equipamento']['metalworking']++;  break;
                    case 'woodworking'   :  $resposta['equipamento']['woodworking']++;  break;
                    case 'machinery'         :  $resposta['equipamento']['machinery']++;  break;
                    case 'lawn_garden':  $resposta['equipamento']['lawn_garden']++;  break;
                    case 'gasoline_explosion'      :  $resposta['equipamento']['gasoline_explosion']++;  break;
                    case 'pneumatic'       :  $resposta['equipamento']['pneumatic']++;  break;
                    case 'other'       :  $resposta['equipamento']['other']++;  break;
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

                if($pesquisa_idioma == "en"){
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
                }else{
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
                    case 'pt': $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['sem_fio'], 'data' =>     array($resposta['equipamento']['sem_fio'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['furadeira'], 'data' =>     array($resposta['equipamento']['furadeira'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['impacto'], 'data' =>      array($resposta['equipamento']['impacto'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['parafusadeira'], 'data' => array($resposta['equipamento']['parafusadeira'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['concreto'], 'data' =>     array($resposta['equipamento']['concreto'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['serras'], 'data' =>     array($resposta['equipamento']['serras'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['marmore'], 'data' =>      array($resposta['equipamento']['marmore'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['mecanica'], 'data' =>     array($resposta['equipamento']['mecanica'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['madeira'], 'data' =>      array($resposta['equipamento']['madeira'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['jardinagem'], 'data' =>    array($resposta['equipamento']['jardinagem'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['outro'], 'data' =>        array($resposta['equipamento']['outro'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                        break;

                case 'es':$resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['martillos'], 'data'   => array($resposta['equipamento']['martillos'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['inalambrico'], 'data'       => array($resposta['equipamento']['inalambrico'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['metalmecanica'], 'data'     => array($resposta['equipamento']['metalmecanica'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['madera'], 'data'          => array($resposta['equipamento']['madera'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['estacionaria'], 'data'      => array($resposta['equipamento']['estacionaria'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['jardin'], 'data'          => array($resposta['equipamento']['jardin'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['gasolina_explosion'], 'data' => array($resposta['equipamento']['gasolina_explosion'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['neumatica'], 'data'       => array($resposta['equipamento']['neumatica'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['other'], 'data'       => array($resposta['equipamento']['other'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                        break;

                case 'en':$resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['cordless'], 'data' =>     array($resposta['equipamento']['cordless'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['hammer'], 'data' =>     array($resposta['equipamento']['hammer'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['drill'], 'data' => array($resposta['equipamento']['drill'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['metalworking'], 'data' =>     array($resposta['equipamento']['metalworking'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['woodworking'], 'data' =>      array($resposta['equipamento']['woodworking'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['machinery'], 'data' =>      array($resposta['equipamento']['machinery'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['lawn_garden'], 'data' =>      array($resposta['equipamento']['lawn_garden'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['gasoline_explosion'], 'data' =>     array($resposta['equipamento']['gasoline_explosion'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['pneumatic'], 'data' =>    array($resposta['equipamento']['pneumatic'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
                    $resposta_grafico[] = array('name' => $language[$pesquisa_idioma]['other'], 'data' =>        array($resposta['equipamento']['other'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0));
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

                $pesquisaChart = "26";
                $pesquisaDescChart = "Pesquisa de Satisfação";

                echo "<div id='showChart_$pesquisaChart' rel='$pesquisaChart' class='divShowChart' style='cursor:pointer;margin:auto;text-align:center;width:1000px'>
                <p class='titulo_tabela'>Gráfico: $pesquisaDescChart</p><div style='margin:auto;display:none' id='div_$pesquisaChart' class='div_$pesquisaChart'></div></div>";
                echo '<script>createChartPesquisa('.$pesquisaChart.','.json_encode($pergunta).','.json_encode($resposta_grafico).');</script>';

                ?>

                <table class="tabela">
                <tr class="titulo_coluna">
                    <th><?=traduz('País')?></th>
                    <th><?=traduz('Cidade')?></th>
                    <?php if($pesquisa == "externo_email"){
                    echo "<th>OS</th>";
                    }
                    ?>
                    <th><?=traduz('Data Resposta')?></th>
                    <th><?=traduz('Ação')?></th>
                </tr>
                <?
                $i = 0;
                foreach (pg_fetch_all($resTabela) as $key) {
                    $local = $key["os"];
                                // $resultado_resposta = json_decode(str_replace("\&#039;","", htmlspecialchars_decode($key['observacao'])));
                                $resultado_resposta = json_decode(utf8_encode(stripslashes($key['observacao'])));
                                $cidade = "";
                                $cidade = str_replace("+", " ", $resultado_resposta->cidade);
                                // if($resultado_resposta->cidade != "" && strripos("+",$resultado_resposta->cidade) == true){
                            // $a=-1;
                            // while(substr($resultado_resposta->cidade, $a, 1) != "+"){
                            //     $a--;
                            //     if(($a*(-1)) == strlen($resultado_resposta->cidade)){
                            //         break;
                            //     }
                            // }
                            // if(substr($resultado_resposta->cidade, $a, 1) == "+"){
                            //     $a++;
                            // }
                            // $cidade = substr($resultado_resposta->cidade, $a);
                        // }else{
                        //     $cidade = $resultado_resposta->cidade;
                        // }
                    ?>
                    <tr>
                    <td><?echo $resultado_resposta->pais?></td>
                    <td><?echo $cidade?></td>
                    <?php if($pesquisa == "externo_email"){
                        echo "<td> <a href='os_press.php?os=$local' target='_blank'>".$key['codigo_posto']."".$key['sua_os']."</a></td>";
                    }?>
                    <td><?echo $key['data']?></td>
                    <td>
                        <button class="btn_ver_resposta_enviado_email" rel="<?=$local?>|<?=$pesquisa_idioma?>|<?=$pesquisa?>|<?=$aux_data_inicial?>|<?=$aux_data_final?>"> <?=traduz('Ver Respostas')?> <img src="imagens/barrow_down.png"> </button>
                    </td>
                    </tr>
                    <tr class='hideTr' id='<?=$local?>' >
                    <td colspan="100%">
                        <table style="width:100%">
                        </table>
                    </td>
                    </tr>
<?php
                }
?>
                </table>
<?php
            }
        }
    } else {
        if($pesquisa_idioma == "todos_idiomas" && $pesqusia = "america_latina"){
          //PROGRAMA QUE VAI GERAR O XLS
        include_once 'relatorio_pesquisas_laudo_todos_idiomas_xls.php';
        } else {
?>
    <div class="msg_erro"><?=traduz('Nenhum Resultado Encontrado')?></div>
<?php
        }
    }
}
require_once 'rodape.php';
?>
