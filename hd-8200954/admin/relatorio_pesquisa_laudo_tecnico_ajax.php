<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include "autentica_admin.php";

// header('Content-Type: application/json');

$msg_erro = array();

if ($_GET['ajax']){

  //VALIDAÇÃO DOS CAMPOS PARA EFETUAR A PESQUISA
  if (isset($_GET['validar'])){

    //VALIDAÇÃO DOS CAMPOS DE DATA
    $data_inicial = $_GET["data_inicial"];
        $data_final   = $_GET["data_final"];
    $pesquisa     = $_GET["pesquisa"];

      if(empty($data_inicial)){
          $msg_erro[] = "Informe a Data Inicial";
      }

      if (empty($data_final)) {
        $msg_erro[] = "Informe a Data Final";
      }

      if ($data_inicial && $data_final){

          list($di, $mi, $yi) = explode("/", $data_inicial);
          if(!checkdate($mi,$di,$yi)){
              $msg_erro[] = "Data Inicial Inválida";
          }

          list($df, $mf, $yf) = explode("/", $data_final);
          if(!checkdate($mf,$df,$yf)) {
              $msg_erro[] = "Data Final Inválida";
          }

      $aux_data_inicial = "$yi-$mi-$di";
      $aux_data_final   = "$yf-$mf-$df";

            if (strtotime($aux_data_inicial.'+3 month') < strtotime($aux_data_final)) {
                $msg_erro[] = 'O intervalo entre as datas não pode ser maior do que 3 meses';
            }

            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
              $msg_erro[] = "Data Final menor que Data Inicial";
          }

        if (strtotime($aux_data_final) > strtotime('today') and $aux_data_final){
          $msg_erro[] = "Data Final maior que a data atual";
        }

        if (strtotime($aux_data_inicial) > strtotime('today')){
          $msg_erro[] = "Data Final maior que a data atual";
        }

      }

      //FIM VALIDAÇÃO DE DATAS

      //VALIDA SE O POSTO DIGITADO EXISTE

    if (isset($_GET['codigo_posto']) || isset($_GET['posto_nome'])){

      $codigo_posto = (isset($_GET['codigo_posto'])) ? utf8_decode(trim($_GET['codigo_posto'])) : '';
      $posto_nome   = (isset($_GET['posto_nome'])) ? utf8_decode(trim($_GET['posto_nome'])) : '';

        $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
              FROM tbl_posto
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
              WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

      if ($posto_nome){
        $sql .= "AND UPPER(tbl_posto.nome) like UPPER('%$posto_nome%')";
      }

      if ($codigo_posto){
        $sql .= "AND UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('$codigo_posto')";
      }

      $res = pg_query($con,$sql);

      if (pg_num_rows ($res) == 0) {

        $msg_erro[] = "Posto não existe";

      }
    }
    //FIM VALIDACAO SE POSTO EXISTE

        if($pesquisa == ""){
            if(isset($_GET['enviado_email']) && $_GET['enviado_email'] == "true"){
                $msg_erro[] = "Selecione um tipo de pesquisa";
            }else{
                $msg_erro[] = "Selecione um idioma";
            }
        }

        if (count($msg_erro)>0) {
            $msg_erro = implode('<br>', $msg_erro);
            $msg_erro = $msg_erro;
            echo "1|$msg_erro";
        } else {
            echo "0|Sem Erros";
        }

  }

  if(isset($_GET['ver_respostas'])){
        $os          = $_GET['os'];
        $pesquisa    = $_GET['pesquisa'];
        $pesquisa_os = $_GET['pesquisa_os'];
        $tipo_pesquisa = $_GET['tipo_pesquisa'];
        $data_inicio = $_GET['data_inicio'];
        $data_final  = $_GET['data_final'];

        $data = "";

        if($pesquisa == ""){
            $pesquisa = "pt";
        }

        switch ($pesquisa) {
            case 'pt': $language['pt']['questao_cinco'] = 'Equipamento';
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

                $language['pt']['servico_assistencia'] = 'Serviço da assistência técnica';
                $language['pt']['suporte']     = 'Suporte e atenção';
                $language['pt']['tempo_resposta']      = 'Tempo de resposta';
                $language['pt']['padrao_servico']   = 'Padrões de qualidade e serviço';
                $language['pt']['qualidade_reparo'] = 'Qualidade de reparação';
                $language['pt']['tempo_reparo']     = 'Tempo de reparação';
                $language['pt']['falha_equipamento']       = 'Falha precoce do equipamento';
                $language['pt']['qualidade_produto']   = 'Qualidade do produto';
                $language['pt']['atencao_suporte']     = 'Atenção de Suporte ( Via telefone )';
                $language['pt']['orcamento']     = 'Orçamento de custos e / ou reparação';
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

                $language['pt']['questao_nove']     = 'Tempo de reparo';
                $language['pt']['questao_dez']      = 'Preço do reparo';
                $language['pt']['questao_onze']     = 'Qualidade do reparo';
                $language['pt']['questao_doze']     = 'Atenção do atendente';
                $language['pt']['questao_treze']    = 'Explicação do reparo';
                $language['pt']['questao_quartoze'] = 'Aspecto visual da Assistência';
                $language['pt']['questao_quinze']   = 'Satisfação geral';

                $language['pt']['plenamente_satisfeito'] = 'Totalmente Satisfeito';
                $language['pt']['muito_satisfeito']      = 'Bastante Satisfeito';
                $language['pt']['satisfeito']            = 'Neutro';
                $language['pt']['pouco_satisfeito']      = 'Pouco Satisfeito';
                $language['pt']['insatisfeito']          = 'Nada Satisfeito';

                $language['pt']['questao_numero'] = 'Numero de dias na assistência';

                $language['pt']['resposta_numero_1'] = '1';
                $language['pt']['resposta_numero_2'] = '2';
                $language['pt']['resposta_numero_3'] = '3';
                $language['pt']['resposta_numero_4'] = '4';
                $language['pt']['resposta_numero_5'] = '5';
                $language['pt']['resposta_numero_6'] = '6';
                $language['pt']['resposta_numero_7'] = '7';
                $language['pt']['resposta_numero_8'] = '8';
                $language['pt']['resposta_numero_9'] = 'mais 8';
                break;

            case 'en':$language['en']['questao_cinco'] = 'Equipment';
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
                $language['en']['suporte']     = 'Support and attention';
                $language['en']['tempo_resposta']      = 'Response Time';
                $language['en']['padrao_servico']   = 'Quality standards and service';
                $language['en']['qualidade_reparo'] = 'Repair quality';
                $language['en']['tempo_reparo']     = 'Repair time';
                $language['en']['falha_equipamento']       = 'Early equipment failure';
                $language['en']['qualidade_produto']   = 'Product Quality';
                $language['en']['atencao_suporte']     = 'Support attention (via phone)';
                $language['en']['orcamento']     = 'Cost budget and / or repair';
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

                $language['en']['questao_nove']     = 'Repair time';
                $language['en']['questao_dez']      = 'Repair price';
                $language['en']['questao_onze']     = 'Repair quality';
                $language['en']['questao_doze']     = "Attendant's attention";
                $language['en']['questao_treze']    = "Repair's explanation";
                $language['en']['questao_quartoze'] = 'Visual aspect Assistance';
                $language['en']['questao_quinze']   = 'Overall satisfaction';

                $language['en']['plenamente_satisfeito'] = 'Fully Satisfied';
                $language['en']['muito_satisfeito']      = 'Very Satisfied';
                $language['en']['satisfeito']            = 'Neutral';
                $language['en']['pouco_satisfeito']      = 'Shortly Satisfied';
                $language['en']['insatisfeito']          = 'Unfulfilled';

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
                break;

            case 'es':$language['es']['questao_cinco'] = 'Equipo que se reparó';
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

                $language['es']['servico_assistencia'] = 'Servicio del centro de raparación';
                $language['es']['suporte']     = 'Soporte y atención (Recepción)';
                $language['es']['tempo_resposta']      = 'Tiempo de respuesta';
                $language['es']['padrao_servico']   = 'Estándares de calidad y servicio';
                $language['es']['qualidade_reparo'] = 'Calidade de la reparación';
                $language['es']['tempo_reparo']     = 'Tiempo de reparación';
                $language['es']['falha_equipamento']       = 'Falla Temprana del equipo';
                $language['es']['qualidade_produto']   = 'Calidade del producto';
                $language['es']['atencao_suporte']     = 'Atención y soporte';
                $language['es']['orcamento']     = 'Costo y/ó presupuesto de reparación';
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

                $language['es']['questao_nove']     = 'Tiempo de la reparación';
                $language['es']['questao_dez']      = 'Precio de la reparación';
                $language['es']['questao_onze']     = 'Calidad de la reparación';
                $language['es']['questao_doze']     = 'Actitud del personal';
                $language['es']['questao_treze']    = 'Explicación de la reparación';
                $language['es']['questao_quartoze'] = 'Aspecto de las instalaciones de servicio';
                $language['es']['questao_quinze']   = 'Satisfacción General';

                $language['es']['plenamente_satisfeito'] = 'Totalmente satisfecho';
                $language['es']['muito_satisfeito']      = 'Bastante Satisfecho';
                $language['es']['satisfeito']            = 'Neutral';
                $language['es']['pouco_satisfeito']      = 'Poco Satisfecho';
                $language['es']['insatisfeito']          = 'Nada Satisfecho';

                $language['es']['questao_numero'] = 'Numero del dias en lo centro de servicio';

                $language['es']['resposta_numero_1'] = '1';
                $language['es']['resposta_numero_2'] = '2';
                $language['es']['resposta_numero_3'] = '3';
                $language['es']['resposta_numero_4'] = '4';
                $language['es']['resposta_numero_5'] = '5';
                $language['es']['resposta_numero_6'] = '6';
                $language['es']['resposta_numero_7'] = '7';
                $language['es']['resposta_numero_8'] = '8';
                $language['es']['resposta_numero_9'] = 'más 8';
                break;
        }

        $i = 0;
        $respostasPergunta = array();
        //percorre o array da consulta principal 1ª vez para jogar as respostas em um array
        switch ($pesquisa) {
            case 'pt': $pesquisa_grafico = '%"language"%'; $pesquisa_language = '%"pt"%'; break;
            case 'es': $pesquisa_grafico = '%"language"%'; $pesquisa_language = '%"es"%'; break;
            case 'en': $pesquisa_grafico = '%"language"%'; $pesquisa_language = '%"en"%'; break;
            default: $pesquisa_grafico = '%"language"%'; $pesquisa_language = '%"pt"%'; break;
        }

        $sql = "SELECT tbl_laudo_tecnico_os.os,
                tbl_os.sua_os,
                tbl_posto_fabrica.codigo_posto,
                tbl_laudo_tecnico_os.observacao,
                tbl_laudo_tecnico_os.os AS pais,
                tbl_laudo_tecnico_os.os AS cidade,
                to_char(tbl_laudo_tecnico_os.data,'DD/MM/YYYY') AS data
            FROM tbl_os
                JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os ";

        if($tipo_pesquisa == "externo_email"){
            $sql .= "JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.enviar_email = 't'";
            //
            // CONCATENAÇÃO DOS CAMPOS CODIGO_POSTO COM SUA_OS PARA BUSCA DE OS DA FABRICA 1 -> HD_2293350
            //
//          JOIN (SELECT tbl_posto_fabrica.codigo_posto || tbl_os.sua_os AS sua_os_codigo_posto,
            //      tbl_posto_fabrica.posto,
            //      tbl_posto_fabrica.codigo_posto
            //  FROM tbl_os
            //  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            // ) AS os_codigo_posto ON os_codigo_posto.posto = tbl_os.posto  ";
        }
        $sql .= "JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_laudo_tecnico_os.data BETWEEN '$data_inicio' AND '$data_final'
                AND tbl_laudo_tecnico_os.observacao LIKE '$pesquisa_grafico'
                AND tbl_laudo_tecnico_os.observacao LIKE '$pesquisa_language' AND tbl_os.os = $os
                 AND titulo ILIKE 'Pesquisa de%' ORDER BY tbl_laudo_tecnico_os.data;";

        $resTabela = pg_query($con,$sql);
        // echo $sql; exit;
        if (pg_num_rows($resTabela)>0) {
            $os                 = pg_fetch_result($resTabela,0,os);
            $data_os            = pg_fetch_result($resTabela,0,data);
            $resultado_resposta = pg_fetch_result($resTabela,0,observacao);
            $resultado_resposta = json_decode(utf8_encode(stripslashes($resultado_resposta)));
            $cor = ($i % 2) ? "#E4E9FF" : "#F3F3F3";

            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 1 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_um']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$resultado_resposta->pais."
                        </td>";
            $data .= "</tr>";

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

            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 2 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_dois']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$cidade."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 3 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_tres']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".str_replace("+", " ", $resultado_resposta->posto)."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 4 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_quatro']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$resultado_resposta->os."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 5 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_cinco']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$language[$pesquisa][$resultado_resposta->equipamento]."
                        </td>";
            $data .= "</tr>";

            $sql = "SELECT marca, nome FROM tbl_marca WHERE fabrica = 1 AND marca = ".$resultado_resposta->marca;
            $resMarca = pg_query($con,$sql);
            // echo $sql; exit;
            if (pg_num_rows($resMarca)>0) {
                $resMarca = pg_fetch_array($resMarca);
                $resultado_resposta->marca = $resMarca['nome'];
            }

            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label >  </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_marca']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$resultado_resposta->marca."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label >  </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_produto']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$resultado_resposta->produto."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label >  </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_qual']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$resultado_resposta->outro_qual."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 6 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_seis']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$resultado_resposta->recomendacao."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 7 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_sete']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$language[$pesquisa][$resultado_resposta->razao_pontuacao]."
                        </td>";
            $data .= "</tr>";
            if(utf8_decode($resultado_resposta->complemento_classificacao) != ""){
                $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 8 </label>
                        </td>
                        <td colspan='2' style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_oito']." </label>
                        </td>
                    </tr><tr>
                        <td  colspan='3' align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px; width: 10px' >
                            ".(utf8_decode($resultado_resposta->complemento_classificacao))."
                        </td>";
            }else{
                $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 8 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_oito']." </label>
                        </td>
                        <td align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px; width: 10px' >
                            ".utf8_decode($resultado_resposta->complemento_classificacao)."
                        </td>";
            }

            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 9 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_nove']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$language[$pesquisa][$resultado_resposta->nota_tempo_reparo]."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 10 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_dez']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$language[$pesquisa][$resultado_resposta->nota_preco_reparo]."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 11 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_onze']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$language[$pesquisa][$resultado_resposta->nota_qualidade_reparo]."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 12 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_doze']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$language[$pesquisa][$resultado_resposta->nota_atencao]."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 13 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_treze']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$language[$pesquisa][$resultado_resposta->nota_explicacao]."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 14 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_quartoze']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$language[$pesquisa][$resultado_resposta->nota_aspecto]."
                        </td>";
            $data .= "</tr>";
            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > 15 </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_quinze']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$language[$pesquisa][$resultado_resposta->nota_geral]."
                        </td>";
            $data .= "</tr>";

            if($resultado_resposta->numero_dias == "mais"){
              $resultado_resposta->numero_dias = "Mais de 8 dias";
            }

            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label >  </label>
                        </td>
                        <td style='text-align:left;padding: 0px 10px 0px 10px' >
                            <label > ".$language[$pesquisa]['questao_numero']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$resultado_resposta->numero_dias."
                        </td>";
            $data .= "</tr>";
            $i++;
        }
        echo $data;
    }

  if (isset($_GET['getChartContents'])) {

    $pesquisa       = $_GET['pesquisa'];
        $posto_nome     = $_GET['posto_nome'];
        $posto_linha    = $_GET['posto_linha'];
        $posto_local    = $_GET['posto_local'];
      $data_inicial   = $_GET['data_inicial'];
        $data_final     = $_GET['data_final'];

        $conditionPosto = (!empty($posto_nome)) ? " AND tbl_posto.nome = '$posto_nome' " : '';
        $conditionLinha = (!empty($posto_linha)) ? " AND tbl_posto_linha.linha = '$posto_linha' " : '';
        $conditionLocal = (!empty($posto_local)) ? " AND UPPER(fn_retira_especiais(tbl_posto.cidade)) = UPPER('$posto_local') " : '';
    $conditionData  = (!empty($data_inicial)) ? " AND tbl_resposta.data_input BETWEEN '$data_inicial' AND '$data_final'" : "";

    $sql = "SELECT  DISTINCT
                        to_ascii(tbl_tipo_resposta_item.descricao, 'LATIN1') AS descricao
                FROM    tbl_pesquisa
                JOIN    tbl_pesquisa_pergunta   USING (pesquisa)
                JOIN    tbl_pergunta            USING (pergunta)
                JOIN    tbl_tipo_resposta_item  USING (tipo_resposta)
                WHERE   tbl_pesquisa.pesquisa   = $pesquisa
                AND     tbl_pergunta.ativo      IS TRUE
        ";

    $res = pg_query($con,$sql);
    for ($i=0; $i < pg_num_rows($res); $i++) {

      $txt_resposta = pg_fetch_result($res, $i, 'descricao');
      $respostas_geral[] = $txt_resposta;

      $tipo_de_respostas[$txt_resposta]['name'] = $txt_resposta;

    }

    // var_dump($tipo_de_respostas);

    $sql = "SELECT  to_ascii(tbl_pergunta.descricao, 'LATIN1') AS descricao ,
                        tbl_pesquisa_pergunta.ordem                             ,
                        tbl_pergunta.pergunta
                FROM    tbl_pesquisa
                JOIN    tbl_pesquisa_pergunta   USING (pesquisa)
                JOIN    tbl_pergunta            USING (pergunta)
                JOIN    tbl_tipo_resposta       USING (tipo_resposta)
                WHERE   tbl_pesquisa.fabrica                = $login_fabrica
                AND     tbl_pesquisa.ativo                  IS TRUE
                AND     tbl_tipo_resposta.tipo_descricao    IN ('radio','checkbox')
                AND     tbl_pesquisa.pesquisa               = $pesquisa
          ORDER BY      tbl_pesquisa_pergunta.ordem
      ";

    $resPerguntas = pg_query($con,$sql);

    if (pg_num_rows($resPerguntas)>0) {
      for ($x=0; $x < pg_num_rows($resPerguntas); $x++) {
        $perguntas[$x] = pg_fetch_result($resPerguntas, $x, 'pergunta');
      }
    }

    $perguntasJson = json_encode($perguntas);
    $respostas_restantes = array();
    foreach ($perguntas as $key => $value) {

      $respostas = $respostas_geral;
      $sql = "SELECT  COUNT(tbl_resposta.tipo_resposta_item)                  AS qtde_respostas,
                            to_ascii(tbl_tipo_resposta_item.descricao, 'LATIN1')    AS descricao
                    FROM    tbl_tipo_resposta_item
         LEFT JOIN    tbl_pergunta        USING (tipo_resposta)
         LEFT JOIN    tbl_resposta        ON  tbl_resposta.tipo_resposta_item = tbl_tipo_resposta_item.tipo_resposta_item
                                                AND tbl_resposta.pergunta           = tbl_pergunta.pergunta
                                                AND tbl_resposta.pesquisa           = $pesquisa";
            if(!empty($posto_nome) || !empty($posto_local) || !empty($posto_linha)){
                $sql .= "
               LEFT JOIN    tbl_posto           ON  tbl_posto.posto                 = tbl_resposta.posto
                    JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto         = tbl_posto.posto
                                                AND tbl_posto_fabrica.fabrica       = $login_fabrica
                    JOIN    tbl_posto_linha     ON  tbl_posto_linha.posto           = tbl_posto.posto
                    JOIN    tbl_linha           ON  tbl_linha.linha                 = tbl_posto_linha.linha
                                                AND tbl_linha.fabrica               = $login_fabrica
                ";
            }
            $sql .= "
                    WHERE   tbl_pergunta.pergunta = $value
                    $conditionPosto
                    $conditionLinha
          $conditionLocal
          $conditionData
              GROUP BY      tbl_tipo_resposta_item.descricao";

      $resRespostas = pg_query($con,$sql);

      for ($i=0; $i < pg_num_rows($resRespostas); $i++) {

        $descricao_tipo_resposta_item = pg_fetch_result($resRespostas, $i, 'descricao');
        $qtde_respostas = pg_fetch_result($resRespostas, $i, 'qtde_respostas');

        if ($tipo_de_respostas[$descricao_tipo_resposta_item]['name'] == $descricao_tipo_resposta_item) {

          $tipo_de_respostas[$descricao_tipo_resposta_item]['data'][] = (int)$qtde_respostas;

        }

        $descricoes_respostas_usadas[] = trim($descricao_tipo_resposta_item);

      }

      $total_respostas = count($respostas);
      for ($x=0; $x <$total_respostas; $x++) {

        for ($z=0; $z < count($descricoes_respostas_usadas); $z++) {

          if ($respostas[$x] === $descricoes_respostas_usadas[$z]) {
            unset($respostas[$x]);
            $respostas_restantes = $respostas;

          }

        }

      }

      foreach ($respostas_restantes as $key => $value) {
        $tipo_de_respostas[$value]['data'][] =(int)0;
      }

      unset($descricoes_respostas_usadas);

    }

    foreach ($tipo_de_respostas as $key => $value) {
      $arraySeries[] = $value;
    }
    echo $respostaJson = json_encode($arraySeries);

  }

  if (isset($_GET['getChartCategories'])) {

    $pesquisa       = $_GET['pesquisa'];
        $posto_nome     = $_GET['posto_nome'];
        $posto_linha    = $_GET['posto_linha'];
        $posto_local    = $_GET['posto_local'];
        $data_inicial   = $_GET['data_inicial'];
        $data_final     = $_GET['data_final'];

        $conditionPosto = (!empty($posto_nome)) ? " AND tbl_posto.nome = '$posto_nome' " : '';
        $conditionLinha = (!empty($posto_linha)) ? " AND tbl_posto_linha.linha = '$posto_linha' " : '';
        $conditionLocal = (!empty($posto_local)) ? " AND UPPER(fn_retira_especiais(tbl_posto.cidade)) = UPPER('$posto_local') " : '';
        $conditionData  = (!empty($data_inicial)) ? " AND tbl_resposta.data_input BETWEEN '$data_inicial' AND '$data_final'" : "";

    $sql = "SELECT  to_ascii(tbl_pergunta.descricao, 'LATIN1') as descricao ,
                        tbl_pesquisa_pergunta.ordem                             ,
                        tbl_pergunta.pergunta
                FROM    tbl_pesquisa
                JOIN    tbl_pesquisa_pergunta   USING (pesquisa)
                JOIN    tbl_pergunta            USING (pergunta)
                JOIN    tbl_tipo_resposta       USING (tipo_resposta)
                WHERE   tbl_pesquisa.fabrica                = $login_fabrica
                AND     tbl_pesquisa.ativo                  IS TRUE
                AND     tbl_tipo_resposta.tipo_descricao    IN ('radio','checkbox')
                AND     tbl_pesquisa.pesquisa               = $pesquisa
                AND     tbl_pergunta.pergunta IN (
                            SELECT  DISTINCT
                                    pergunta
                            FROM    tbl_resposta
                            WHERE   pesquisa = $pesquisa
                        )
          ORDER BY      tbl_pesquisa_pergunta.ordem
      ";

    $resPerguntas = pg_query($con,$sql);

    if (pg_num_rows($resPerguntas)>0) {
      for ($x=0; $x < pg_num_rows($resPerguntas); $x++) {
        $perguntas[$x] = pg_fetch_result($resPerguntas, $x, 'descricao');
      }
    }

    echo json_encode($perguntas);

  }
  exit;

}
