<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

require('../class/email/mailer/class.phpmailer.php');
//require_once "../classes/excelwriter/excelwriter.inc.php";

use model\ModelHolder;

include_once "../class/aws/s3_config.php";
include_once S3CLASS;

include "../helpdesk.inc.php";// Funcoes de HelpDesk hd_chamado=2537875

$fabricas_interacao = array(30,126,127,132);

//  Funções próprias...
function iif($condition, $val_true, $val_false = "") {
  if (is_numeric($val_true) and is_null($val_false))
    $val_false = 0;
  if (is_null($val_true) or is_null($val_false) or !is_bool($condition))
    return null;
  return ($condition) ? $val_true : $val_false;
}

function anti_injection($string) {
  $a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
  return strtr(strip_tags(trim($string)), $a_limpa);
}

function getPost($param,$get_first = false) {
  //  Procura o valor do parâmetro $param no $_POST e no $_GET (ou no $_GET e no $_POST, se o segundo parâmetro for 'true')

  if ($get_first) {
    if (isset($_GET[$param]))
      return anti_injection($_GET[$param]);
    if (isset($_POST[$param]))
      return anti_injection($_POST[$param]);
  } else {
    if (isset($_POST[$param]))
      return anti_injection($_POST[$param]);
    if (isset($_GET[$param]))
      return anti_injection($_GET[$param]);
  }
  return null;
}

function converte_data($date){
  list($d, $m, $y) = preg_split('/\D/', $date);
  return (checkdate($m, $d, $y)) ?"$y/$m/$d" : false;
}

$admin_privilegios = 'gerencia,call_center';
include 'autentica_admin.php';

if($login_fabrica == 90) {
  require_once dirname(__FILE__) . '/../class/email/mailer/class.phpmailer.php';
}

if ($login_fabrica == 117) {
    include_once('carrega_macro_familia.php');
}

if ($_GET['ajax'] == 'reprovarOs') {

    $msg = "";
    $msg_erro = array();
    $os  = trim($_GET['os']);

    if (empty($os)) {
       $msg_erro = array("erro" => true, "msg" => utf8_encode("Os não informada"));
    }

    if (count($msg_erro) == 0) {

      $res = pg_query($con,"BEGIN TRANSACTION");
      
      $data_fechamento = date("Y-m-d");
      $finalizada      = date("Y-m-d H:i:s");

      $sqlx = "UPDATE tbl_os SET 
                                data_fechamento = '$data_fechamento', 
                                finalizada = '$finalizada' 
                           WHERE os = $os AND fabrica={$login_fabrica};";
      $resx = pg_query($con, $sqlx);

      if (pg_last_error($con)) {
        $msg = utf8_encode("Erro ao finalizar Os");
      }

      $sql = "INSERT INTO tbl_os_status (
                                          os,
                                          status_os,
                                          data,
                                          observacao,
                                          admin
                                        ) VALUES (
                                          $os,
                                          81,
                                          current_timestamp,
                                          'Os reprovada pelo Fabricante',
                                          $login_admin
                                        )";

      $res  = pg_query($con,$sql);
      if (pg_last_error($con)) {
        $msg =  utf8_encode("Erro ao finalizar Os");
      }

      if (strlen($msg) > 0) {
        $msg_erro = array("erro" => true, "msg" => $msg);
        $res = pg_query ($con,'ROLLBACK TRANSACTION');
      } else {
        $res = pg_query ($con,'COMMIT TRANSACTION');
        $msg_erro = array("erro" => fale, "msg" => "OS reprovada com sucesso");
      }
    }

    exit(json_encode($msg_erro));
}

// HD-962530
if ($_GET['ajax'] == 'cancelar_os') {
  $os = $_GET['os'];
  $motivo = (!empty($_GET['motivo'])) ? $_GET['motivo'] : 'Os cancelada na intervenção técnica';

  $motivo = substr($motivo,0,149);
  if ((in_array($login_fabrica,array(35,86,90,126,131,123,138,140,142,143,145))) and trim($_GET['cancelar']) == 'sim' and strlen($os) > 0) {

    $sua_os = trim($_GET['autorizar']);
    $res    = pg_query($con,"BEGIN TRANSACTION");

    $sql = "INSERT INTO tbl_os_status
      (os,status_os,data,observacao,admin)
      VALUES ($os,15,current_timestamp,'$motivo',$login_admin)";

    $res  = pg_query($con,$sql);
    $msg .= pg_last_error($con);

    if(!in_array($login_fabrica, array(145))){

      $sqlx = "UPDATE tbl_os SET excluida = 't' WHERE os = $os";
      $resx = pg_query($con, $sqlx);
      $msg .= pg_last_error($con);

      #158147 Paulo/Waldir desmarcar se for reincidente
      $sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
      $res = pg_query($con, $sql);

    }

    // Adiciona a OS como excluida
    $sql = "INSERT INTO tbl_os_excluida (
                fabrica,
                admin,
                os,
                sua_os,
                posto,
                codigo_posto,
                produto,
                referencia_produto,
                data_digitacao,
                data_abertura,
                data_fechamento,
                serie,
                nota_fiscal,
                data_nf,
                consumidor_nome,
                data_exclusao,
                consumidor_endereco,
                consumidor_numero,
                consumidor_cidade,
                consumidor_estado,
                defeito_reclamado,
                defeito_reclamado_descricao,
                defeito_constatado,
                revenda_cnpj,
                revenda_nome,
                consumidor_bairro,
                consumidor_fone,
                motivo_exclusao)
            SELECT tbl_os.fabrica,
                   $login_admin,
                   os,
                   tbl_os.sua_os,
                   tbl_posto_fabrica.posto,
                   tbl_posto_fabrica.codigo_posto,
                   tbl_produto.produto,
                   tbl_produto.referencia,
                   data_digitacao,
                   data_abertura,
                   data_fechamento,
                   serie,
                   nota_fiscal,
                   data_nf,
                   consumidor_nome,
                   CURRENT_DATE,
                   consumidor_endereco,
                   consumidor_numero,
                   consumidor_cidade,
                   consumidor_estado,
                   defeito_reclamado,
                   defeito_reclamado_descricao,
                   defeito_constatado,
                   revenda_cnpj,
                   revenda_nome,
                   consumidor_bairro,
                   consumidor_fone,
                   '$motivo'
              FROM tbl_os
              JOIN tbl_produto       ON tbl_produto.produto       = tbl_os.produto
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_os.posto
                                    AND tbl_posto_fabrica.fabrica = $login_fabrica
             WHERE os = $os";
    //$res = pg_query ($con,"ROLLBACK TRANSACTION");
    //die(nl2br($sql)); exit;
    $res  = pg_query($con,$sql);
    $msg .= pg_last_error($con);

    // HD-962530
    $sql = "SELECT tbl_posto_fabrica.contato_email, tbl_os.posto
              FROM tbl_posto_fabrica
              JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto
                         AND tbl_os.os    = $os
             WHERE tbl_posto_fabrica.fabrica = $login_fabrica";

    $res          = pg_query($con,$sql);
    $destinatario = pg_num_rows($res) ? pg_fetch_result($res,0,contato_email) : "";
    $posto        = pg_fetch_result($res,0,'posto');

    if (!empty($destinatario) and $login_fabrica == 90 and !strlen($msg)) {

        $mail = new PHPMailer();
        $mail->IsHTML(true);
        $mail->From     = 'helpdesk@telecontrol.com.br';
        $mail->FromName = 'Telecontrol';

        $assunto   = "A O.S $os foi cancelada pela fabrica.";

        $mensagem  = "A O.S foi cancelada pela fabrica por conter dados divergentes.<br/><br/>";
        $mensagem .= "Para maiores esclarecimentos da O.S, entrar em contato pelo Telefone: (11) 2118-2126.<br/><br/>";
        $mensagem .= "Atenciosamente,<br/><br/>";
        $mensagem .= "<i><b>Nabil Kyriazi Filho</b><br/>";
        $mensagem .= "Supervisor SAC / Garantias / Assistencia Tecnica<br/>";
        $mensagem .= "Industria Brasileira de Bebedouros Ltda</i>";

        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        // Additional headers
        $headers .= "To: $destinatario" . "\r\n";
        $headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n";

        mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
    }

    if ($login_fabrica == 126) {
        $sql = "INSERT INTO tbl_comunicado(
                   mensagem,
                   descricao,
                   tipo,
                   fabrica,
                   obrigatorio_site,
                   posto,
                   pais,
                   ativo
                ) VALUES (
                   '$motivo',
                   'A O.S $os foi cancelada pela fabrica',
                   'Comunicado',
                   $login_fabrica,
                   't',
                   $posto,
                   'BR',
                   't'
                );";
        $res = pg_query($con,$sql);
        $msg = pg_last_error($con);
    }

    if (strlen($msg)) {
      $res = pg_query ($con,'ROLLBACK TRANSACTION');
    }else {
      $res = pg_query ($con,'COMMIT TRANSACTION');
      $msg = 'OS Cancelada com sucesso';
    }

    print $msg;

    exit();
  }
}

// HD-962530
if($_GET['ajax'] == 'confirmar_reparo') {

  $os = $_GET['os'];
  $res = pg_query($con,'BEGIN TRANSACTION');

  $sql = "INSERT INTO tbl_os_status
      (os,status_os,data,observacao,admin)
      VALUES ($os,64,current_timestamp,'OS reparada pela fábrica',$login_admin)";

  $res  = pg_query($con,$sql);
  $msg .= pg_last_error($con);

  if(in_array($login_fabrica,array(90,117,127))) { // Adicionada 127 HD-2264355

    $sql =  "UPDATE tbl_os_retorno
           SET data_nf_retorno = CURRENT_DATE
               WHERE os = $os";

    $res  = pg_query($con,$sql);
    $msg .= pg_last_error($con);
  }

  if (strlen($msg)) {
    $res = pg_query ($con,'ROLLBACK TRANSACTION');
  }else {
    $res = pg_query ($con,'COMMIT TRANSACTION');
    $msg = 'Os liberada!';
  }

  print $msg;

  exit();
}

#HD 14830
#HD 13618
// De volta ao formato tradicional à pedido popular :)
// HD 211825: Liberar intervenção de OS para todas as fábricas >=80

if (!in_array($login_fabrica, array(2,3,6,11,14,19,24,25,30,35,43,45,50,51,52,72,74)) and $login_fabrica < 80) {
    header('Location: menu_callcenter.php');
    exit();
}

/*  29/12/2009 MLG - Define as fábricas que usam a os_intervencao, e assina o serviço realizado e o ajuste. A fábrica é uma 'key' do array, e o serviço_realizado e o ajuste estão nessa ordem, separados por ví­rgula */

$a_usam_intervencao =   array(
                                2   => "7, 67"      ,
                                3   => "20,96"      ,
                                6   => "1,35"       ,
                                11  => "61,498"     ,
                                14  => ""           ,
                                19  => ""           ,
                                25  => ""           ,
                                35  => ""           ,
                                43  => "722,631"    ,
                                45  => "640,639"    ,
                                51  => "673,671"    ,
                                52  => ""           ,
                                85  => "8430,8431"  ,
                                90  => "10064,10060",
                                94  => "10535,10534",
                                104 => "10585,11097",
                                114 => "10660,10659",
                                115 => "10669,10668",
                                116 => "10672,10671",
                                117 => "10676,10675",
                                122 => "10735,10729",
                                123 => "10739,10738",
                                125 => "10741,10743",
                                127 => "10748,10750",
                                131 => "10771,10772",
                                131 => "10773,10774",
                                132 => "10967,10968",
                                139 => "11125,11124",
                                140 => "11104,11113",
                                141 => "11109,11110",
                                144 => "11113,11114",
                                172 => "61,498"     ,
                        );

if (isset($novaTelaOs)) {
  $sqlServicoTrocaPeca = "SELECT servico_realizado
                          FROM tbl_servico_realizado
                          WHERE fabrica = {$login_fabrica}
                          AND gera_pedido IS TRUE
                          AND troca_de_peca IS TRUE
                          AND ativo IS TRUE
                          AND peca_estoque IS NOT TRUE";
  $resServicoTrocaPeca = pg_query($con, $sqlServicoTrocaPeca);

  $servicoTrocaPeca = pg_fetch_result($resServicoTrocaPeca, 0, "servico_realizado");

  $sqlServicoCancelaPeca = "SELECT servico_realizado
                            FROM tbl_servico_realizado
                            WHERE fabrica = {$login_fabrica}
                            AND ativo IS NOT TRUE
                            AND gera_pedido IS NOT TRUE
                            AND troca_de_peca IS NOT TRUE
                            AND troca_produto IS NOT TRUE
                            AND peca_estoque IS NOT TRUE
                            AND UPPER(descricao) = 'CANCELADO'";
  $resServicoCancelaPeca = pg_query($con, $sqlServicoCancelaPeca);

  $servicoCancelaPeca = pg_fetch_result($resServicoCancelaPeca, 0, "servico_realizado");

  $a_usam_intervencao[$login_fabrica] = "{$servicoTrocaPeca},{$servicoCancelaPeca}";
}

//  Configurações por fabricante
$usa_os_orcamento      = in_array($login_fabrica, array(20,96)); // Fábricas que usam OS Fora de Garantia. Ainda não está em uso nesta tela.
$usa_filtro_linha      = in_array($login_fabrica, array(3));     // Fábricas que tem habilitado o filtro por linha
$fabricas_busca_estado = in_array($login_fabrica, array(14,45,142)); // Habilita o filtro por estado/INCLUIDO BUSCA POR ESTADO P/ INTELBRAS HD176677
$fabrica_libera_troca  = in_array($login_fabrica, array(6,14,25,45,51,19,81,72,114,117,123,125,126,139,141)); // Habilita botão de troca de produto
$ordem_tabela_js       = in_array($login_fabrica, array(10,51,81,114)); // Ordena a tabela por qualquer coluna, mas 'perde' a linha do motivo

/*  29/12/2009 MLG - HD 179837 - Resposta AJAX consulta linhas do Posto */
if ($_GET['ajax'] == 'linhas_posto') {
  $info_posto = getPost('info_posto');
  $tipo_info  = getPost('tipo_info');

   //print_r($_GET);
  $linha_oculta_ajax = getPost('linha_oculta');
  $item_r = array("[","]");
  $linha_oculta_ajax = str_replace($item_r, "", $linha_oculta_ajax);
  $linha_oculta_ajax = explode(",", $linha_oculta_ajax);
  //print_r($linha_oculta_ajax);

  // Sem código ou razão social, mostrar todas as linhas
  if ($info_posto == '' or is_null($info_posto)){
    $sql = "SELECT linha,codigo_linha,nome FROM tbl_linha WHERE fabrica=$login_fabrica AND ativo IS TRUE";
  } else {
    if ($info_posto == null or $info_posto == ""){
      die("ko - NO INFO");
    }
    if ($tipo_info =="codigo"){
      $cond = "codigo_posto = '$info_posto'";
    }else{
      $cond = "UPPER(nome) LIKE UPPER('%$info_posto%')";
    }
    $sql= "SELECT posto
             FROM tbl_posto_fabrica
             JOIN tbl_posto USING (posto)
            WHERE fabrica = $login_fabrica AND $cond";
    $res = @pg_query($con,$sql);
    if (!is_resource($res)){
      die("ko - POSTO QUERY ERROR");
    }
    $id_posto = @pg_fetch_result($res, 0, posto);
    if (!is_numeric($id_posto)){
      die("ko - NENHUM POSTO");
    }
    $sql = "SELECT tbl_posto_linha.linha, tbl_linha.codigo_linha, nome
                  FROM tbl_posto_linha
                  JOIN tbl_linha        USING (linha)
                  WHERE posto           =  $id_posto
                  AND tbl_linha.ativo   IS TRUE
                  AND tbl_linha.fabrica =  $login_fabrica";
  }
  $res_linhas = pg_query($con, $sql);
  if (($num_linhas = pg_num_rows($res_linhas)) > 0) {
    echo "<legend>Selecione a(s) linha(s)</legend>\n";
    for ($i = 0; $i < $num_linhas; $i++) {
      list ($linha_id, $codigo_linha, $linha_desc) = pg_fetch_row($res_linhas, $i);

      if(in_array($linha_id, $linha_oculta_ajax)){

        $checked_ajax = "CHECKED";
      }else{

        $checked_ajax = "";
      }
      echo "\t\t\t\t<input type='checkbox' name='linhas[]' $checked_ajax value='$linha_id' title='$linha_desc'>\n".
        "\t\t\t\t<label class='table_line' title='$linha_des'>$codigo_linha</label>\n";
    }
  }
  exit;
}// FIM AJAX atualiza linhas por posto

//  Define o serviço realizado e o ajuste, segundo o fabricante. Se o fabricante não definiu, segue o padrão da Britânia
if ($a_usam_intervencao[$login_fabrica] != '') {
  list($id_servico_realizado,$id_servico_realizado_ajuste) = explode(',', $a_usam_intervencao[$login_fabrica]);
} else { # padrao BRITANIA
  $id_servico_realizado=20;
  $id_servico_realizado_ajuste = 96;
}

$msg = '';
$meses = array(1 => 'Janeiro',  'Fevereiro','Março',  'Abril',  'Maio',   'Junho',
          'Julho',  'Agosto', 'Setembro', 'Outubro',  'Novembro', 'Dezembro');

$styleTag_iframe = <<<STYLE_FOR_IFRAME
  <style>
    body {
      padding: 2px;
      margin: 0;
      margin-bottom: 1em;
      font-size: 12px;
      font-family: Verdana,Tahoma,Arial;
    }
    table, th, td {
      font-size: 12px !important;
      width: 100%;
      border: 1px solid #ccc;
    }
    .frm {
        border: #888888 1px solid;
        font-weight: bold;
        font-size: 8pt;
        background-color: #f0f0f0;
    }
    #frm_form {
      width: 95%;
      margin: 0 auto;
    }

    button,input[type=button],input[type=submit] {
      color: #fff;
      border-radius: 5px;
      background-color: #596D9B;
      border: 0px;
      padding: 5px;
      width: 150px;
      text-align: center;
      cursor: pointer;
    }
    .box-success {
      border: 1px solid green;
      padding: 5px;
      margin: 0 auto;
      color: green;
      background-color: #BEF781;
      margin-top: 15px;
      text-align: center;
    }
    .subtitle_secao {
        background-color:#e1e1e1;
        font-size: 12px;
        color: black;
        text-align: left;
        padding: 2px 1ex;
        margin: 0;
    }
    .subtitle_acao {
        background-color:#e1e1e1;
        font-size: 12px;
        color: white;
        text-align: center;
        padding: 3px;
        margin: 0;
    }
    .box-error {
      border: 1px solid red;
      padding: 5px;
      margin: 0 auto;
      color: red;
      background-color: #F6CECE;
      margin-top: 15px;
      text-align: center;
    }    
    span.label { display: inline-block; width: 18em;}
  </style>
STYLE_FOR_IFRAME;

$os = getPost('os',true);

if (strlen(trim($_GET['retirar_intervencao']))>0) {
  $retirar_intervencao = trim($_GET['retirar_intervencao']);
}

if (isset($_GET['msg_erro']) && strlen(trim($_GET['msg_erro']))>0)
  $msg_erro=trim($_GET['msg_erro']);

if (isset($_GET['msg']) && strlen(trim($_GET['msg']))>0)
  $msg=trim($_GET['msg']);

$str_filtro    = '&btnacao=filtrar';
$ordem         = 'nome';
$laudo_tecnico = '';

if ($_POST['btn_tipo'] == 'cancelar_laudo_tecnico') {
    try {
        $os     = $_POST['os'];
        $observacao = $_POST['observacao'];
        $conclusao  = $_POST['conclusao'];
        $imagem1    = $_FILES['foto1'];
        $imagem2    = $_FILES['foto2'];

        pg_query($con, 'BEGIN');

        $obs = array('observacao' => addslashes(utf8_encode($observacao)), 'conclusao' => addslashes(utf8_encode($conclusao)));
        $obs = addslashes(json_encode($obs));

        $sql = "INSERT INTO tbl_laudo_tecnico_os (os, titulo, observacao, fabrica) values ($os, 'Laudo Tecnico - O.S. $os', '$obs', $login_fabrica)";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception('Erro ao recusar OS');
        }

        $obs = 'OS Reprovada na auditoria de Análise da fábrica';

        $sql = "INSERT INTO tbl_os_status
            (os,status_os,data,observacao,admin) VALUES ($os, 201, current_timestamp, '$obs',$login_admin)";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception('Erro ao recusar OS');
        }

        $sql = "SELECT posto, sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
        $res = pg_query($con, $sql);

        $posto  = pg_fetch_result($res, 0, 'posto');
        $sua_os = pg_fetch_result($res, 0, 'sua_os');

        $sql = "INSERT INTO tbl_comunicado (
            descricao              ,
            mensagem               ,
            tipo                   ,
            fabrica                ,
            obrigatorio_os_produto ,
            obrigatorio_site       ,
            posto                  ,
            ativo
        ) VALUES (
            'OS recusada'           ,
            'A OS $sua_os foi <b>recusada</b> pela fábrica na auditoria de Análise na fábrica. <br />',
            'Com. Unico Posto',
            $login_fabrica,
            'f' ,
            't',
            $posto,
            't'
        );";
        $res = pg_query($con,$sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception('Erro ao recusar OS');
        }

        $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND UPPER(descricao) = 'CANCELADO'";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception('Erro ao recusar OS');
        } else {
            $servico_realizado = pg_fetch_result($res, 0, 'servico_realizado');

            $sql =  "UPDATE tbl_os_item
                        SET servico_realizado          = $servico_realizado,
                            admin                      = $login_admin,
                            liberacao_pedido           = FALSE,
                            liberacao_pedido_analisado = FALSE,
                            data_liberacao_pedido      = NULL
                WHERE os_item IN (
                    SELECT os_item
                      FROM tbl_os
                      JOIN tbl_os_produto USING(os)
                      JOIN tbl_os_item    USING(os_produto)
                      JOIN tbl_peca       USING(peca)
                      JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
                     WHERE tbl_os.os      = $os
                       AND tbl_os.fabrica = $login_fabrica
                       AND tbl_servico_realizado.troca_de_peca IS TRUE
                       AND tbl_servico_realizado.ativo IS TRUE
                       AND tbl_servico_realizado.gera_pedido IS TRUE
                       AND tbl_os_item.pedido IS NULL
                )";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception('Erro ao recusar OS');
            }
        }

        /**
         * Upload S3
         */
        if($imagem1['size'] > 0){

            $s3 = new AmazonTC('inspecao', $login_fabrica);
            $laudo_tecnico = $imagem1;

            $types = array("png", "jpg", "jpeg", "bmp", "pdf");
            $type  = strtolower(preg_replace("/.+\//", "", $laudo_tecnico["type"]));

            if (!in_array($type, $types)) {
                throw new Exception("Anexo 1 com formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf");
            }else{
                $name1 = $os."-laudo-tecnico-1";
                $file1 = $laudo_tecnico;
            }

        }

        if($imagem2['size'] > 0){

            $s3 = new AmazonTC('inspecao', $login_fabrica);
            $laudo_tecnico = $imagem2;

            $types = array("png", "jpg", "jpeg", "bmp", "pdf");
            $type  = strtolower(preg_replace("/.+\//", "", $laudo_tecnico["type"]));

            if (!in_array($type, $types)) {
                throw new Exception("Anexo 2 com formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf");
            }else{
                $name2 = $os."-laudo-tecnico-2";
                $file2 = $laudo_tecnico;
            }

        }

        if (isset($file1)) {
            $s3->upload ($name1, $file1);
        }

        if (isset($file2)) {
            $s3->upload ($name1, $file2);
        }

        pg_query($con, "COMMIT");
        $laudo_tecnico = "form_enviado";

?>
        <script type='text/javascript'>
        parent.document.frm_consulta.btnacao.value='filtrar';
        parent.document.frm_consulta.submit();
        </script>
<?php
        exit;
  } catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    $msg_erro = $e->getMessage();
  }
}

if (trim($_GET['janela']) == 'sim' or $laudo_tecnico == 'form_enviado') {

  $os   = ($laudo_tecnico == 'form_enviado') ? $os : trim($_GET['os']);
  $tipo = trim($_GET['tipo']);

  if(isset($novaTelaOs)){
    $joinProduto = 'JOIN   tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto';
    $leftJoin = '';
  }else{
    $joinProduto = 'JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ';
    $leftJoin    = ' LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os ';
  }

  // OS não excluída
  $sql =  "SELECT tbl_os.os                                                     ,
        tbl_os.sua_os                                                     ,
        LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
        TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
        TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
        TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
        current_date - tbl_os.data_abertura          AS dias_aberto       ,
        tbl_os.data_abertura                         AS abertura_os       ,
        tbl_os.serie                                                      ,
        tbl_os.consumidor_nome                                            ,
        tbl_os.consumidor_endereco                                        ,
        tbl_os.consumidor_numero                                          ,
        tbl_os.consumidor_bairro                                          ,
        tbl_os.consumidor_cidade                                          ,
        tbl_os.consumidor_estado                                          ,
        tbl_posto_fabrica.codigo_posto                                    ,
        tbl_posto.posto                             AS posto              ,
        tbl_posto.nome                              AS posto_nome         ,
        tbl_posto_fabrica.contato_fone_comercial    AS posto_fone         ,
        tbl_produto.referencia                      AS produto_referencia ,
        tbl_produto.descricao                       AS produto_descricao  ,
        tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
         (
            SELECT status_os
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (67, 68, 70, 199)
                 ORDER BY data DESC LIMIT 1)        AS reincindente, (
            SELECT observacao
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (62, 64, 65, 199)
                 ORDER BY data DESC LIMIT 1)        AS status_descricao, (
            SELECT status_os
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (62, 64, 65, 199)
                 ORDER BY data DESC LIMIT 1)        AS status_os, (
            SELECT data
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (62, 64, 65, 199)
                 ORDER BY data DESC LIMIT 1)        AS status_pedido, (
            SELECT TO_CHAR(data,'DD/MM/YYYY') AS data
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (62, 64, 65, 127, 147, 167, 199)
                 ORDER BY data DESC LIMIT 1)        AS status_data2
      FROM tbl_os
        JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
        $joinProduto
        $leftJoin
      WHERE tbl_os.fabrica = $login_fabrica
        AND tbl_os.os=$os";

  $res   = pg_query($con,$sql);
  $total = pg_num_rows($res);

  if ($total > 0 or $laudo_tecnico == "form_enviado"){
    $os                  = trim(pg_fetch_result($res,0,'os'));
    $sua_os              = trim(pg_fetch_result($res,0,'sua_os'));
    $data_nf             = trim(pg_fetch_result($res,0,'data_nf'));
    $digitacao           = trim(pg_fetch_result($res,0,'digitacao'));
    $abertura            = trim(pg_fetch_result($res,0,'abertura'));
    $serie               = trim(pg_fetch_result($res,0,'serie'));
    $consumidor_nome     = trim(pg_fetch_result($res,0,'consumidor_nome'));
    $consumidor_endereco = trim(pg_fetch_result($res,0,'consumidor_endereco'));
    $consumidor_numero   = trim(pg_fetch_result($res,0,'consumidor_numero'));
    $consumidor_bairro   = trim(pg_fetch_result($res,0,'consumidor_bairro'));
    $consumidor_cidade   = trim(pg_fetch_result($res,0,'consumidor_cidade'));
    $consumidor_estado   = trim(pg_fetch_result($res,0,'consumidor_estado'));
    $codigo_posto        = trim(pg_fetch_result($res,0,'codigo_posto'));
    $posto_nome          = trim(pg_fetch_result($res,0,'posto_nome'));
    $posto_fone          = trim(pg_fetch_result($res,0,'posto_fone'));
    $produto_referencia  = trim(pg_fetch_result($res,0,'produto_referencia'));
    $produto_descricao   = trim(pg_fetch_result($res,0,'produto_descricao'));
    $posto_fone          = substr(trim(pg_fetch_result($res,0,'posto_fone')),0,17);
    $status_os           = trim(pg_fetch_result($res,0,'status_os'));
    $status_descricao    = trim(pg_fetch_result($res,0,'status_descricao'));
    $dias_abertura       = trim(pg_fetch_result($res,0,'dias_aberto'));

    switch ($login_fabrica) {
      case 145: $titulo = "<strong style='font: 16px arial; font-weight: bold;'>Laudo Técnico</strong>"; break;
      default:
        $titulo = "INTERVENÇÃO TÉCNICA"; break;
    }
    ?>
    <html>
    <head>
      <title>Intervenção</title>
      <script src="js/jquery-1.6.1.min.js" type="text/javascript"></script>
      <script src="js/jquery-ui-1.8.14.custom.min.js" type="text/javascript"></script>
      <?=$styleTag_iframe?>
      <script type="text/javascript">
        $(function() {
          $('input[id*=data]').datepicker();
        });



        function valida_laudo(){

          if($("textarea[name=observacao]").val() == ""){
            alert("Por favor insira a Observação");
            $("textarea[name=observacao]").focus();
            return false;
          }

          if($("textarea[name=conclusao]").val() == ""){
            alert("Por favor insira a Conclusão");
            $("textarea[name=conclusao]").focus();
            return false;
          }

          alert("Aguarde o Envio do Laudo Técnico, isso pode demorar alguns segundos!");
        }
      </script>
    </head>
    <body>

      <?php
      if ($login_fabrica == 145 && $tipo == "cancelar_laudo_tecnico") {
      ?>
        <form enctype="multipart/form-data" name='frm_form' id="frm_form" method='post' action="<?=$PHP_SELF?>?janela=sim&tipo=cancelar_laudo_tecnico&os=<?=$os?>&TB_iframe=true" onsubmit="return valida_laudo()">
          <input name='os' value='<?=$os?>' type='hidden'>
          <input type='hidden' name='btn_tipo' value='<?=$tipo?>'>
          <div>
            <div class='subtitle_acao' style='background-color:#596D9B;padding: 5px;'><?=$titulo?></div>

      <?  } else { ?>
        <form enctype="multipart/form-data" name='frm_form' id="frm_form" method='post' action="<?=$PHP_SELF?>" >
          <input name='os' value='<?=$os?>' type='hidden'>
          <input type='hidden' name='btn_tipo' value='<?=$tipo?>'>
          <div>
            <div class='subtitle_acao' style='background-color:#596D9B;padding: 5px;'><?=$titulo?></div>

            <?
            if ($tipo == 'cancelar') {
              echo "<div class='subtitle_acao' style='background-color:#EF4B4B;'>CANCELAR PEDIDO</div>\n";
            } else if ($tipo == 'reparar' && in_array($login_fabrica, array(11,172))) {
                echo "<div class='subtitle_acao' style='background-color:#F7D909;'>REPARAR</div>\n";
            } else if ($tipo == 'autorizar') {
                echo "<div class='subtitle_acao' style='background-color:#34BC3F;'>AUTORIZAR PEDIDO</div>\n";
            }
        }

        if ($login_fabrica == 145 && $tipo == "cancelar_laudo_tecnico") {

            $endereco_cliente = "";
            $endereco_cliente .= (!empty($consumidor_endereco)) ? $consumidor_endereco : "";
            $endereco_cliente .= (!empty($consumidor_nuemro))   ? " , ".$consumidor_numero : "";
            $endereco_cliente .= (!empty($consumidor_bairro))   ? " - ".$consumidor_bairro : "";
            $endereco_cliente .= (!empty($consumidor_cidade))   ? " - ".$consumidor_cidade : "";
            $endereco_cliente .= (!empty($consumidor_estado))   ? " - ".$consumidor_estado : "";

            $msg = "";

            if ($laudo_tecnico == "form_enviado" && empty($msg_erro)) {
              $msg = "<div class='box-success'>Laudo Técnico enviado com Sucesso</div>";
              $conclusao     = "";
              $observacao    = "";
              $laudo_tecnico = "";

              exit;
            } elseif (!empty($msg_erro)) {
              $msg = "<div class='box-error'>".$msg_erro."</div>";
            }

            echo $msg;

            ?>

            <br />

            <strong>O.S <?php echo $sua_os; ?></strong> <br />
            <strong>CLIENTE: <?php echo $consumidor_nome; ?></strong> <br />
            <strong>ENDEREÇO: <?php echo $endereco_cliente; ?></strong> <br />

            <br />

            <table cellpadding="5" cellspacing="0">
              <tr>
                <th>Produto analisado</th>
                <th>QT</th>
                <th>Laudo</th>
              </tr>
              <tr>
                <td><?=$produto_referencia?> - <?=$produto_descricao?></td>
                <td>01</td>
                <td nowrap>NÃO PROCEDENTE</td>
              </tr>
            </table>

            <br /> <br />

            <strong>Observação</strong> <br />
            <textarea name="observacao" cols="60" rows="5"><?php echo $observacao; ?></textarea>

            <br /> <br />

            <strong>Conclusão</strong> <br />
            <textarea name="conclusao" cols="60" rows="2"><?php echo $conclusao; ?></textarea>

            <br /> <br />

            <strong>Imagem 1</strong> <br />
            <input type="file" name="foto1" /> <br />

            <br />

            <strong>Imagem 2</strong> <br />
            <input type="file" name="foto2" /> <br />

            <br />

            <input type="submit" value="Enviar Laudo" />

            <br /> <br />

            <?php
        } else {
            ?>

              <div class='subtitle_secao'>Posto</div>
              <br><span class="label">Código:</span> <?=$codigo_posto?> - <?=$posto_nome?>
              <br><span class="label">Telefone:</span> <?=$posto_fone?>
              <br>
              <br><div class="subtitle_secao">Ordem de Serviço</div>
              <br><span class="label">Número OS:</span> <b><?=$sua_os?></b>
              <br><span class="label">Data Abertura:</span> <b><?=$abertura?></b>
              <br><span class="label">Data da Nota Fiscal:</span> <b><?=$data_nf?></b>
              <br>
              <br><div class="subtitle_secao">Produto</div>
              <br>
              Referência: <?=$produto_referencia?> - <?=$produto_descricao?>
              <?
              $sql_peca = "SELECT tbl_os_item.os_item,
                        tbl_peca.troca_obrigatoria AS troca_obrigatoria,
                        tbl_peca.retorna_conserto AS retorna_conserto,
                        tbl_peca.bloqueada_garantia AS bloqueada_garantia,
                        tbl_peca.referencia AS referencia,
                        tbl_peca.descricao AS descricao,
                        tbl_peca.peca AS peca,
                        tbl_os_item.servico_realizado AS servico_realizado
                      FROM tbl_os_produto
                      JOIN tbl_os_item USING(os_produto)
                      JOIN tbl_peca    USING(peca)
                       WHERE tbl_os_produto.os=$os";
              $res_peca = pg_query($con,$sql_peca);
              $resultado = pg_num_rows($res_peca);
              if ($resultado>0){
                echo "<br>";
                echo "<br><div  class='subtitle_secao'>Peças</div>";
                for($j=0;$j<$resultado;$j++){
                  $peca_id            = trim(pg_fetch_result($res_peca, $j, 'peca'));
                  $peca_referencia    = trim(pg_fetch_result($res_peca, $j, 'referencia'));
                  $peca_descricao     = trim(pg_fetch_result($res_peca, $j, 'descricao'));
                  $bloqueada_garantia = trim(pg_fetch_result($res_peca, $j, 'bloqueada_garantia'));
                  $retorna_conserto   = trim(pg_fetch_result($res_peca, $j, 'retorna_conserto'));
                  $servico_realizado  = trim(pg_fetch_result($res_peca, $j, 'servico_realizado'));
                  if ($bloqueada_garantia=='t')
                    $bloqueada_garantia="(bloqueada p/ garantia)";
                  else
                    $bloqueada_garantia="";
                  if ($retorna_conserto=='t')
                    $retorna_conserto=" <b>*</b> ";
                  else
                    $retorna_conserto="";
                  if ($servico_realizado==$id_servico_realizado){
                      $servico_realizado="<b style='color:gray;font-size:9px;font-weight:normal'>(Troca de Peça)</b>";
                  }else{
                    $servico_realizado="<b style='color:gray;font-size:9px;font-weight:normal'>(não gera pedido)</b>";
                  }

                  echo "<br>";

                  #HD 308972
                  if($login_fabrica == 3){
                    echo '<input type="checkbox" name="pecas_selecionadas[]" class="pecasCancelar" id="peca_'.$peca_id.'" checked="checked" value="'.$peca_id.'" />';
                  }

                  echo "<label for='peca_$peca_id'>$retorna_conserto $peca_referencia - $peca_descricao $servico_realizado $bloqueada_garantia</label> \n";
                }
                echo "<br><b style='color:gray;font-size:9px;font-weight:normal'>* Peças com intervenção da fábrica.";

                #HD 308972
                if($login_fabrica == 3){
                  echo "<br>** As peças marcadas terão seus pedidos cancelados</b>";

                  #Script de validação para selecionar ao menos uma peça
                  $script_validacao = '
                  pecaSelecionada = false;
                  $(\'.pecasCancelar\').each(function(){
                    if($(this).is(\':checked\')){
                      pecaSelecionada = true;
                    }
                  });';
                }
              }
              $sql = "  SELECT status_os, TO_CHAR(data,'DD/MM/YYYY') AS data,
                       observacao,
                       admin
                    FROM tbl_os_status
                     WHERE os             = $os
                     AND status_os     IN (62, 64, 65)
                     AND fabrica_status = $login_fabrica
                     ORDER BY data DESC LIMIT 1";
              $res = pg_query($con,$sql);
              $total=pg_num_rows($res);

              if ($total>0){
                $st_os    = trim(pg_fetch_result($res, 0, 'status_os'));
                $st_data  = trim(pg_fetch_result($res, 0, 'data'));
                $st_obs   = trim(pg_fetch_result($res, 0, 'observacao'));
                $st_admin = trim(pg_fetch_result($res, 0, 'admin'));
              }

              #Para Tectoy Mostra Histórico
              if ($login_fabrica==6){
                $sql = "  SELECT tbl_os_status.status_os,
                         TO_CHAR(tbl_os_status.data,'DD/MM/YYYY HH24:MI') AS data,
                         tbl_os_status.observacao,
                         tbl_admin.admin
                      FROM tbl_os_status
                      LEFT JOIN tbl_admin USING (admin)
                       WHERE os = $os
                       AND status_os IN (62, 64, 65)
                       AND tbl_os_status.fabrica_status = $login_fabrica
                       ORDER BY data ASC";
                $res = pg_query($con,$sql);
                if (pg_num_rows($res)>0){
                  echo "<br><br>";
                  echo "<div class='subtitle_secao'>Histórico</div>";
                  for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                    $hist_stat  = trim(pg_fetch_result($res, $i, 'status_os'));
                    $hist_data  = trim(pg_fetch_result($res, $i, 'data'));
                    $hist_obs   = trim(pg_fetch_result($res, $i, 'observacao'));
                    $hist_admin = trim(pg_fetch_result($res, $i, 'admin'));
                    if ($hist_stat == 62 OR $hist_stat == 65){
                      $origem = "<span style='color:green'>Posto</span>";
                    }else{
                      $origem = "<span style='color:blue'>Fábrica</span>";
                    }
                    $hist_obs = str_replace("Peça da O.S. com intervenção da fábrica","",$hist_obs);
                    echo "<br><b>$hist_data</b> ($hist_admin) - $origem: $hist_obs\n";
                    echo "<br>\n";
                  }
                  echo "<br>\n";
                }
              }

              if ($tipo=="cancelar"){
                $msg_titulo="Justificativa do Cancelamento";
              }
              if ($tipo=="reparar" && in_array($login_fabrica, array(11,172)) ){
                $msg_titulo="Será feito o reparo deste produto na fabrica, informe abaixo a justificativa:";
              }elseif ($tipo == "autorizar"){
                $msg_titulo="Justificativa da Autorização";
              }
              ?>
                <br>
                <p class="subtitle_acao" style='font-weight:bold;background-color:#A9C1E0;color:black'><?=$msg_titulo?></p>
                <br>
                <textarea name='justificativa' style='width:100%' rows='5' class='frm'></textarea>
                <br>
                <input type='hidden' name='btn_acao' value=''>
                <center>
                  <img src='imagens/btn_gravar.gif' alt='gravar' border='0' style='cursor:pointer;'
                   onclick="
                      if (document.frm_form.justificativa.value != '' ){
                        if (document.frm_form.btn_acao.value == '' ) {
                          if (confirm('Deseja continuar?')) {
                            var pecaSelecionada = true;
                            <?=$script_validacao?>
                            if(pecaSelecionada == false){
                              alert('É necessário selecionar ao menos uma peça');
                            }else{
                               document.frm_form.btn_acao.value='gravar' ;
                               document.frm_form.submit();
                               <?php if ($login_fabrica == 30) {?>
                               xos = $('input[name=os]').val();
                               window.parent.removeLinha(xos);
                               <?php }?>
                            }
                          }
                        }else {
                          alert ('Aguarde submissão');
                        }
                      }
                      else{
                        alert('Digite a justificativa!');
                      }">
                </center>

            <?php
          }
          ?>
        </div>
      </form>
    </body>
    </html>
    <?
    exit;
  }
}

if (trim($_GET['janela'])=='sim_reprovar') {
  $os_reprova    = getPost('os');
  $quantidade_os = getPost('quantidade');
  $os   = explode(',', $os_reprova);
  $os   = $os[0];

  $os_reprova_replace = str_replace(',',', ',$os_reprova);

  $tipo = getPost('tipo');
  $tipo_cancelameento = $tipo.'_os';

  // OS não excluída
  $sql =  "SELECT tbl_os.os                                                        ,
                  tbl_os.sua_os                                                    ,
                  LPAD(tbl_os.sua_os,20,'0')                  AS ordem            ,
                  TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao        ,
                  TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS abertura         ,
                  TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')        AS data_nf          ,
                  current_date - tbl_os.data_abertura         AS dias_aberto      ,
                  tbl_os.data_abertura                        AS abertura_os      ,
                  tbl_os.serie                                                     ,
                  tbl_os.consumidor_nome                                           ,
                  tbl_posto_fabrica.codigo_posto                                   ,
                  tbl_posto.nome                              AS posto_nome        ,
                  tbl_posto_fabrica.contato_fone_comercial    AS posto_fone        ,
                  tbl_produto.referencia                      AS produto_referencia,
                  tbl_produto.descricao                       AS produto_descricao ,
                  tbl_produto.troca_obrigatoria               AS troca_obrigatoria ,
                    (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (67,68,70) ORDER BY data DESC LIMIT 1) AS reincindente,
                    (SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65) ORDER BY data DESC LIMIT 1) AS status_descricao,
                    (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65) ORDER BY data DESC LIMIT 1) AS status_os,
                    (SELECT data FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65) ORDER BY data DESC LIMIT 1) AS status_pedido,
                    (SELECT TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,127,147,167) ORDER BY data DESC LIMIT 1) AS status_data2
             FROM tbl_os
             JOIN tbl_posto         ON tbl_posto.posto = tbl_os.posto
             JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
             JOIN tbl_produto       ON tbl_produto.produto = tbl_os.produto
            WHERE tbl_os.fabrica = $login_fabrica
              AND tbl_os.os=$os";

  $res   = pg_query($con,$sql);
  $total = pg_num_rows($res);

  if ($total>0) {
    $os                 = trim(pg_fetch_result($res,0,'os'));
    $sua_os             = trim(pg_fetch_result($res,0,'sua_os'));
    $data_nf            = trim(pg_fetch_result($res,0,'data_nf'));
    $digitacao          = trim(pg_fetch_result($res,0,'digitacao'));
    $abertura           = trim(pg_fetch_result($res,0,'abertura'));
    $serie              = trim(pg_fetch_result($res,0,'serie'));
    $consumidor_nome    = trim(pg_fetch_result($res,0,'consumidor_nome'));
    $codigo_posto       = trim(pg_fetch_result($res,0,'codigo_posto'));
    $posto_nome         = trim(pg_fetch_result($res,0,'posto_nome'));
    $posto_fone         = trim(pg_fetch_result($res,0,'posto_fone'));
    $produto_referencia = trim(pg_fetch_result($res,0,'produto_referencia'));
    $produto_descricao  = trim(pg_fetch_result($res,0,'produto_descricao'));
    $posto_fone         = substr(trim(pg_fetch_result($res,0,'posto_fone')),0,17);
    $status_os          = trim(pg_fetch_result($res,0,'status_os'));
    $status_descricao   = trim(pg_fetch_result($res,0,'status_descricao'));
    $dias_abertura      = trim(pg_fetch_result($res,0,'dias_aberto'));

    echo "<html><head><title>";
    echo "Intervenção";
    echo "</title>";
    echo '<script src="js/jquery-1.8.3.min.js" type="text/javascript" language="JavaScript"></script>';
    echo $styleTag_iframe . "</head><body>";
    echo "<form name='frm_form' method='post' action='$PHP_SELF'>";
    echo "<input type='hidden' name='os' value='$os'>";
    echo "<input type='hidden' name='btn_tipo' value='$tipo_cancelameento'>";
    echo "<input type='hidden' name='quantidade_os' value='$quantidade_os'>";

    echo "<input type='hidden' name='os_reprovar' value='$os_reprova'>";
    echo "<div>";
    echo "<div class='subtitle_acao' style='background-color:#596D9B;'>INTERVENÇÃO TÉCNICA</div>\n";
    if($tipo=='cancelar'){
      echo "<div class='subtitle_acao' style='background-color:#EF4B4B;'>CANCELAR PEDIDO</div>\n";
    }
    if($tipo=='reparar' && in_array($login_fabrica, array(11,172)) ){
      echo "<div class='subtitle_acao' style='background-color:#F7D909;'>REPARAR</div>\n";
    }else{
      if($tipo=='autorizar'){
        echo "<div class='subtitle_acao' style='background-color:#34BC3F;'>AUTORIZAR PEDIDO</div>\n";
      }
    }

    echo "<br>";
    echo "<br><div class='subtitle_secao'>Ordem de Serviço</div>";
    echo "<br>Número OS <b>$os_reprova_replace</b>\n";

    $sql = "SELECT status_os,to_char(data,'DD/MM/YYYY') AS data,observacao,admin
              FROM tbl_os_status
             WHERE os=$os
               AND status_os IN (62,64,65)
               AND tbl_os_status.fabrica_status=$login_fabrica
             ORDER BY data DESC
             LIMIT 1";
    $res = pg_query($con,$sql);
    $total=pg_num_rows($res);

    if ($total>0) {
      $st_os   = trim(pg_fetch_result($res,0,'status_os'));
      $st_data = trim(pg_fetch_result($res,0,'data'));
      $st_obs  = trim(pg_fetch_result($res,0,'observacao'));
      $st_admin= trim(pg_fetch_result($res,0,'admin'));
    }

    if ($tipo=='cancelar'){
      $msg_titulo='Justificativa do Cancelamento';
    }
    if ($tipo=='reparar' && in_array($login_fabrica, array(11,172)) ) {
      $msg_titulo='Será feito o reparo deste produto na fábrica, informe abaixo a justificativa:';
    } elseif ($tipo == 'autorizar') {
      $msg_titulo='Justificativa da Autorização';
    }
?>
      <br>
      <p class='subtitle_secao' style='background-color:#A9C1E0;'><?=$msg_titulo?></p>
      <br>
      <textarea name='justificativa' style='width:100%'rows='5' class='frm' maxlength='200'></textarea>
      <br>
      <input type='hidden' name='btn_acao_reprovar' value=''>
      <center>
        <img src='imagens/btn_gravar.gif' alt='gravar' border='0' style='cursor:pointer;'
         onclick="justificaOS(this);">
      </center>
    </div>
  </form>
</body>
    <script type="text/javascript">
    function justificaOS(el) {
        var frm = document.frm_form;
        if (frm.justificativa.value != '' ){
            if (frm.btn_acao_reprovar.value == '' ) {
                if (confirm('Deseja continuar?')) {
                    var pecaSelecionada = true;
                    <?=$script_validacao?>
                    if(pecaSelecionada == false){
                        alert('É necessário selecionar ao menos uma peça');
                    }else{
                        frm.btn_acao_reprovar.value='gravar_reprovacao' ;
                        frm.submit();
                    }
                }
            } else {
                alert ('Aguarde submissão');
            }
        } else {
            alert('Digite a justificativa!');
        }
    }
    </script>
</html>
<?    exit();
  }
}

  //HD 674410 - Retirar filtro para a Bestway
  if (getPost('btnacao')  == 'filtrar') {

    $ordem = getPost('ordem');
    if (strlen($ordem)>0){
      $sql_ordem = " ORDER BY ";
      switch ($ordem) {
        case "nome":          $sql_ordem .= "tbl_os.os, tbl_posto.$ordem ASC"; break;
        case "data_abertura": $sql_ordem .= "tbl_os.os, tbl_os.$ordem ASC";    break;
        case "data_pedido":   $sql_ordem .= "tbl_os.os, status_pedido ASC ";   break;
      }
      $str_filtro .= "&ordem=$ordem";
    }
  }

  $num_os             = getPost('num_os');
  $posto_codigo       = getPost('posto_codigo');
  $posto_nome         = getPost('posto_nome');
  $referencia         = getPost('referencia');
  $descricao          = getPost('descricao');
  $produto_referencia = getPost('produto_referencia');
  $produto_descricao  = getPost('produto_descricao');
  $peca_referencia    = getPost('peca_referencia');
  $peca_descricao     = getPost('peca_descricao');
  $data_inicial       = getPost('data_inicial');
  $data_final         = getPost('data_final');
  $linha              = getPost('linha');
  $familia            = getPost('familia');
  $macro_linha        = getPost('macro_linha');

  if (strlen($data_inicial) > 0 and strlen($data_final) > 0) {
    list($di, $mi, $yi) = explode("/", $data_inicial);
         if(!checkdate($mi,$di,$yi))
      $msg_erro = "Data Inválida";

    list($df, $mf, $yf) = explode("/", $data_final);
    if(!checkdate($mf,$df,$yf))
      $msg_erro = "Data Inválida";

    if(strlen($msg_erro)==0)
    {
      $aux_data_inicial2 = "$yi-$mi-$di";
      $aux_data_final2 = "$yf-$mf-$df";
    }

    if(strlen($msg_erro)==0)
    {
      if(strtotime($aux_data_final2) < strtotime($aux_data_inicial2)
         or
         strtotime($aux_data_final2) > strtotime('today'))
      {
        $msg_erro = "Data Inválida.";
      }
    }

    if(strlen($msg_erro)==0)
    {
        $aux_data_inicial = $aux_data_inicial2;
        $aux_data_final = $aux_data_final2;
    }
    $str_filtro.="&data_inicial=$data_inicial&data_final=$data_final";
  }

  if ((strlen($data_inicial) > 0 and strlen($data_final) == 0) or (strlen($data_inicial) == 0 and strlen($data_final) > 0))
  {
    $msg_erro = "Data Inválida";
  }

  if (getPost('btnacao') == 'listar_todos')
  {
    $listar_todos = "true";

    $posto_codigo = '';
    $posto_nome   = '';
    $referencia   = '';
    $descricao    = '';
    $linha        = '';
    $familia      = '';
    $macro_linha  = '';
    $data_inicial = '';
    $data_final   = '';
    $produto_referencia = '';
    $produto_descricao  = '';
    $peca_referencia    = '';
    $peca_descricao     = '';
  }

  if (strlen($peca_referencia)>0 OR strlen($peca_descricao)>0){
    if (strlen($peca_referencia)>0)
      $sql_adicional_2 = " AND tbl_peca.referencia = '$peca_referencia' ";
    else
      $sql_adicional_2 = " AND tbl_peca.descricao like '%$peca_descricao%' ";
    $sql = "SELECT tbl_peca.referencia AS ref,
             tbl_peca.descricao  AS desc,
             tbl_peca.peca       AS peca
                  FROM tbl_peca
                 WHERE tbl_peca.fabrica = $login_fabrica
        $sql_adicional_2";
    $res = pg_query ($con,$sql);
    if (pg_num_rows ($res)>0){
      $peca_referencia = pg_fetch_result ($res, 0, 'ref');
      $peca_descricao  = pg_fetch_result ($res, 0, 'desc');
      $peca            = pg_fetch_result ($res, 0, 'peca');
      $sql_adicional_2 = " AND tbl_peca.peca = $peca";
      $str_filtro .= "&peca_referencia=$peca_referencia&peca_descricao=$peca_descricao";
    }
  }
  if (strlen($produto_referencia)>0 OR strlen($produto_descricao)>0){
    if (strlen($produto_referencia)>0){
        $sql_adicional_3 = " AND tbl_produto.referencia = '$produto_referencia' ";
    }else {
        $sql_adicional_3 = " AND tbl_produto.descricao like '%$produto_descricao%' ";
    }
    $sql = "SELECT  tbl_produto.referencia as ref, tbl_produto.descricao as desc, tbl_produto.produto as produto
      FROM tbl_produto
      JOIN tbl_familia USING(familia)
      WHERE tbl_familia.fabrica=$login_fabrica
      $sql_adicional_3";
    $res = pg_query ($con,$sql);
    if (pg_num_rows ($res)>0){
      $produto_referencia = pg_fetch_result ($res, 0, ref);
      $produto_descricao  = pg_fetch_result ($res, 0, desc);
      $produto            = pg_fetch_result ($res, 0, produto);
      $sql_adicional_3 = " AND tbl_produto.produto = $produto";
      $str_filtro .= "&produto_referencia=$produto_referencia&produto_descricao=$produto_descricao";
    }
  }
  // HD 79395 NKS
  $posto_estado = getPost("posto_estado");
  if (strlen($posto_estado)>0){
    $sql_adicional_4 = " AND tbl_posto.estado = '$posto_estado'";
    $str_filtro .= "&posto_estado=$posto_estado";
  }
  if (strlen($posto_codigo)>0 OR strlen($posto_nome)>0){
    if (strlen($posto_codigo)>0)
      $sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
    else
      $sql_adicional = " AND tbl_posto.nome ~* '$posto_nome' ";

    $sql = "SELECT tbl_posto_fabrica.codigo_posto AS cod,
                   tbl_posto.nome                 AS nome,
                   tbl_posto.posto                AS posto
              FROM tbl_posto
              JOIN tbl_posto_fabrica USING(posto)
             WHERE tbl_posto_fabrica.fabrica = $login_fabrica
            $sql_adicional";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res)>0) {
      $posto_codigo  = pg_fetch_result ($res, 0, 'cod');
      $posto_nome    = pg_fetch_result ($res, 0, 'nome');
      $posto         = pg_fetch_result ($res, 0, 'posto');
      $sql_adicional = " AND tbl_posto.posto       = $posto";
      $str_filtro   .= "&posto_codigo=$posto_codigo&posto_nome=$posto_nome";
    }
  }
  // 29/12/2009 MLG - HD 179837 - Adicionar filtro por linha(s)
  if (count($_POST['linhas'])>0) {
    $linhas = implode(",",$_POST['linhas']);
    $sql_adicional  .= " AND tbl_posto_linha.linha IN (".$linhas.")";
    $str_filtro   .= "&linhas=$linhas";
  }

  if (strlen($_POST['linha'])>0) {
    $linha = $_POST['linha'];
    $str_filtro   .= "&linha=$linha";
  }

  if (strlen($_POST['macro_linha'])>0) {
    $macro_linha = $_POST['macro_linha'];
    $str_filtro .= "&macro_linha={$macro_linha}";
  }

  if (strlen($_POST['familia'])>0) {
    $familia = $_POST['familia'];
    $str_filtro   .= "&familia=$familia";
  }
  #Chamado 18406 - Serve para retirar da intervenção quando a OS está em intervenção - reparo na fábrica
  if (trim($retirar_intervencao)=="1" && strlen($os) > 0) {
    $sql = "SELECT sua_os
              FROM tbl_os
             WHERE os      = $os
               AND fabrica = $login_fabrica";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0){
      $sua_os = trim(pg_fetch_result($res,0,sua_os));
      $sql = "  SELECT os_status, status_os
        FROM tbl_os_status
        WHERE os = $os
        AND status_os IN (62, 64, 65)
        AND tbl_os_status.fabrica_status = $login_fabrica
        ORDER BY tbl_os_status.data DESC LIMIT 1";
      $res = pg_query($con,$sql);
      if (pg_num_rows($res) > 0){
        $os_status = trim(pg_fetch_result($res, 0, 'os_status'));
        $st_os     = trim(pg_fetch_result($res, 0, 'status_os'));
        if ($st_os=='65'){
          $msg_erro = "";
          $res = @pg_query($con,"BEGIN TRANSACTION");
          $sql = "INSERT INTO tbl_os_status
            (os,status_os,data,observacao,admin)
            VALUES ($os,64,current_timestamp,'OS Liberada da Intervenção',$login_admin)";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);
        if (strlen($msg_erro)>0){
          $msg = " Não foi possÃ­vel retirar a OS de intervenção. Tente novamente.";
          $res = @pg_query ($con,"ROLLBACK TRANSACTION");
        }else {
          $msg = " OS ".$sua_os." retirada da Intervenção.";
          $res = @pg_query ($con,"COMMIT TRANSACTION");
        }
      }
      if ($st_os == 62 and $login_fabrica == 51) {
        $msg_erro = "";
        $res = @pg_query($con,"BEGIN TRANSACTION");
        $sql = "INSERT INTO tbl_os_status
            (os,status_os,data,observacao,admin)
            VALUES ($os,64,current_timestamp,'OS Liberada da Intervenção',$login_admin)";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);
        $sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
            WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
            AND   tbl_os_produto.os = $os";
        $res = pg_query($con,$sql);
        $erro .= pg_last_error($con);
        $sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
            WHERE tbl_os.os = $os";
        $res = pg_query($con,$sql);
        $erro .= pg_last_error($con);
        if (strlen($msg_erro)>0){
          $msg = " Não foi possÃ­vel retirar a OS de intervenção. Tente novamente.";
          $res = @pg_query ($con,"ROLLBACK TRANSACTION");
        }else {
          $msg = " OS ".$sua_os." retirada da Intervenção.";
          $res = @pg_query ($con,"COMMIT TRANSACTION");
        }
      }
    }
  }
}

if (getPost('btn_tipo') == 'cancelar_os' && strlen($os) > 0) {
  $justificativa  = getPost('justificativa');

  $quantidade_os  = getPost('quantidade_os');
  $os_reprovar  = getPost('os_reprovar');
  $numero_os    = str_replace(',',' | ',$os_reprovar);

  $numero_os    = explode(' | ',$numero_os);

  for($a=0;$a < $quantidade_os;$a++) {
    $os = "";
    $os = $numero_os[$a];

    if($os <> ''){
      if (strlen($justificativa)>0){
        $justificativa = "Justificativa: $justificativa";
      }
      $sql = "SELECT sua_os,
              posto
            FROM tbl_os
            WHERE os=$os";
      $res = pg_query($con,$sql);
      if (pg_num_rows($res)>0){
        $sua_os = trim(pg_fetch_result($res,0,sua_os));
        $posto = trim(pg_fetch_result($res,0,posto));
      }
      $sql = "SELECT status_os,
            to_char(data,'DD/MM/YYYY') as data,
            observacao,admin
            FROM tbl_os_status
            WHERE os=$os
            AND status_os IN (62,64,65)
            AND tbl_os_status.fabrica_status=$login_fabrica
            ORDER BY tbl_os_status.data
            DESC LIMIT 1";
      $res = pg_query($con,$sql);
      $total=pg_num_rows($res);
      if ($total>0){
        $st_os   = trim(pg_fetch_result($res,0,'status_os'));
        $st_data = trim(pg_fetch_result($res,0,'data'));
        $st_obs  = trim(pg_fetch_result($res,0,'observacao'));
        $st_admin= trim(pg_fetch_result($res,0,'admin'));

        if ($st_os=='64'){ echo "<html><head><title='Intervenção'>$styleTag_iframe</head><body>";
          echo "OS LIBERADA!!!<br><br>id OS: $os<br>EM: $st_data<br>Observacao: $st_obs<br>Admin: $st_admin";
          echo "<script language='javascript'>";
          echo "opener.document.location = '$PHP_SELF';";
          echo "setTimeout('window.close()',5000);";
          echo "</script>";
          echo "</body>";
          echo "</html>";
          exit();
        }
      }
      $res = @pg_query($con,"BEGIN TRANSACTION");

      $sql = "INSERT INTO tbl_os_status
          (os,status_os,data,observacao,admin)
          VALUES ($os,64,current_timestamp,'Pedido de Peças Cancelado. $justificativa',$login_admin)";
      $res = pg_query($con,$sql);
      $msg_erro = pg_last_error($con);

      if (strlen($msg_erro)==0) {
          $mensagem_insert = ($login_fabrica != 94) ? "O pedido das peças referente a OS $sua_os foi <b>cancelado</b> pela fábrica. <br><br>$justificativa" : "Seu pedido de envio de produto para conserto / troca, referente à OS $sua_os foi <b>cancelado</b> pela fábrica.<br><br>$justificativa";

        $sql = "INSERT INTO tbl_comunicado (
              descricao              ,
              mensagem               ,
              tipo                   ,
              fabrica                ,
              obrigatorio_os_produto ,
              obrigatorio_site       ,
              posto                  ,
              ativo
            ) VALUES (
              'Pedido de Peças CANCELADO',
              '$mensagem_insert',
              'Pedido de Peças',
              $login_fabrica,
              'f' ,
              't',
              $posto,
              't'
            );";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);

        //HD 416877 -  Envia e-mail também após reprovar a OS
        if ($login_fabrica == 86){

          $sql = "SELECT contato_email
                    FROM tbl_posto_fabrica
                   WHERE posto=$posto
                     AND fabrica=$login_fabrica";
          $res = pg_query($con,$sql);

          $destinatario = (pg_num_rows($res)>0) ? pg_fetch_result($res,0,0) : "";

          if (!empty($destinatario)) {

            $assunto   = " OS RECUSADA PELO FABRICANTE ";
            $mensagem  = "<center>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</center><br><br>";
            $mensagem .= "At. Responsável,<br><br>A OS $sua_os foi recusada pelo seguinte motivo: <br> $justificativa. <br>";
            $mensagem .= "Qualquer duvida contatar a sua atendente regional.<br>";
            $mensagem .= "<b><font color='red'>FAMASTIL</font></b>";

            // To send HTML mail, the Content-type header must be set
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // Additional headers
            $headers .= "To: $destinatario" . "\r\n";
            $headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n";

            $username = 'tc.sac.famastil@gmail.com';
            $senha = 'tcfamastil';

            $mailer = new PhpMailer(true);

            $mailer->IsSMTP();
            $mailer->Mailer = "smtp";

            $mailer->Host = 'ssl://smtp.gmail.com';
            $mailer->Port = '465';
            $mailer->SMTPAuth = true;

            $mailer->Username = $username;
            $mailer->Password = $senha;
            $mailer->SetFrom($username, $username);
            $mailer->AddAddress($destinatario,$destinatario );
            $mailer->Subject = utf8_encode($assunto);
            $mailer->Body = utf8_encode($mensagem);

            try{
              $mailer->Send();
            }catch(Exception $e){
              $msg_erro = "Mensagem não enviada";
            }
          }
        }
      }

      if (strlen($msg_erro)==0){
        //HD 255530: Alterei pois não faz sentido para a Nova, ninguém pediu esta condição e sem ela as peças vão ser pedidas
        if ($login_fabrica <> 43) {
          $retorno_conserto_sql = "AND   tbl_peca.retorna_conserto IS TRUE";
        }
        //HD 255530 FIM

        #HD 308972
        if ($login_fabrica == 3) {

          $pecas_atualizar = is_array($_POST['pecas_selecionadas']) ? implode(',',$_POST['pecas_selecionadas']) : array();
          $sql_pecas_atualizar = ($pecas_atualizar) ? ' AND tbl_peca.peca IN ('.$pecas_atualizar.') ' : ' AND tbl_peca.peca IN (0) ';

        }else{
          $sql_pecas_atualizar = null;
        }

        $sql =  "UPDATE tbl_os_item SET
                servico_realizado          = $id_servico_realizado_ajuste,
                admin                      = $login_admin                ,
                liberacao_pedido           = FALSE                       ,
                liberacao_pedido_analisado = FALSE                       ,
                data_liberacao_pedido      = NULL
              WHERE os_item IN (
               SELECT os_item
                 FROM tbl_os
                 JOIN tbl_os_produto USING(os)
                 JOIN tbl_os_item    USING(os_produto)
                 JOIN tbl_peca       USING(peca)
                WHERE tbl_os.os                     = $os
                AND tbl_os.fabrica                = $login_fabrica
                AND tbl_os_item.servico_realizado = $id_servico_realizado
                AND tbl_os_item.pedido IS NULL
                /* HD 255530 - Somente a linha $retorna_conserto_sql */
                $retorna_conserto_sql
                $sql_pecas_atualizar
          )";

        $res = pg_query($con,$sql);
        $msg_erro = pg_last_error($con);
        if (strlen($msg_erro)>0){
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
        }else {
          $res = @pg_query ($con,"COMMIT TRANSACTION");
          $msg = "Pedido de peças da OS $sua_os foi cancelado! A OS foi liberada para o posto";
        }
      }
    }
  }
    echo "<html><head><title='Intervenção'></head><body>";
    echo "<script language='javascript'>";
    echo "parent.document.frm_consulta.btnacao.value='filtrar';";
    echo "parent.document.frm_consulta.submit();";

    if (strlen($msg_erro)>0){
      echo "Erro: $msg_erro<br>Faça o processo novamente.";
    }else{
      if ($login_fabrica != 3) echo 'tb_remove();';
    }
    echo "</script>";
    echo "</body>";
    echo "</html>";
  exit();
}

if (getPost('btn_tipo') == 'cancelar' && strlen($os) > 0) {
  $os            = getPost('os');
  
  if ((($login_fabrica >= 131 || in_array($login_fabrica, [6,86])) && tem_pedido_os($os)) || ($login_fabrica < 131 && !in_array($login_fabrica, [6,86]))) {
    
    $justificativa = getPost('justificativa');

    if (strlen($justificativa)>0){

      if ($login_fabrica == 131) {
        $justificativa = "Intervenção da fábrica, motivo: $justificativa";          
      } else {
        $justificativa = "Justificativa: $justificativa";
      }
    }

    $sql = "SELECT sua_os, posto, data_fechamento, finalizada
              FROM tbl_os
             WHERE os=$os";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res)>0){
      $sua_os = trim(pg_fetch_result($res, 0, 'sua_os'));
      $posto  = trim(pg_fetch_result($res, 0, 'posto'));
      $data_fechamento  = pg_fetch_result($res, 0, 'data_fechamento');
      $finalizada  = pg_fetch_result($res, 0, 'finalizada');
    }

    if($login_fabrica == 3){
      $status_verificacao = ",67,174,175";
    }

    $sql = "SELECT status_os,
                   TO_CHAR(data,'DD/MM/YYYY') AS data,
                   observacao, admin
              FROM tbl_os_status
             WHERE os = $os
               AND status_os IN (62, 64, 65, 81{$status_verificacao})
               AND tbl_os_status.fabrica_status = $login_fabrica
          ORDER BY tbl_os_status.data DESC
             LIMIT 1";

    $res   = pg_query($con,$sql);
    $total = pg_num_rows($res);

    if ($total>0){
      $st_os    = trim(pg_fetch_result($res, 0, 'status_os'));
      $st_data  = trim(pg_fetch_result($res, 0, 'data'));
      $st_obs   = trim(pg_fetch_result($res, 0, 'observacao'));
      $st_admin = trim(pg_fetch_result($res, 0, 'admin'));

      if ($st_os=='64'){ ?>
        <html>
        <head>
          <title>Intervenção</title>
          <?=$styleTag_iframe?>
        </head>
        <body>
          <table class="table">
              <caption>OS LIBERADA!!!</caption>
              <tbody>
                  <tr class="table_line">
                      <td>id OS:</td>
                      <td><?=$os?></td>
                  </tr>
                  <tr class="table_line">
                      <td>EM:</td>
                      <td><?=$st_data?></td>
                  </tr>
                  <tr class="table_line">
                      <td>Observacao:</td>
                      <td><?=$st_obs?></td>
                  </tr>
                  <tr class="table_line">
                      <td>Admin:</td>
                      <td><?=$st_admin?></td>
                  </tr>
              </tbody>
          </table>
        </body>
        </html>
        <?
      }
    }

    $res = @pg_query($con,"BEGIN TRANSACTION");

    // HD-962530
    if($login_fabrica != 90) {
      $status_os = (in_array($login_fabrica,array(30,35,74,104,114,117,115))) ? 81 : 64;

      $justificativa = str_replace("<br>", "", $justificativa);

      $sql = "INSERT INTO tbl_os_status
          (os,status_os,data,observacao,admin)
          VALUES ($os,$status_os,CURRENT_TIMESTAMP,'$justificativa',$login_admin)";
      $res      = pg_query($con,$sql);
      $msg_erro = pg_last_error($con);
    }

    if ($login_fabrica == 30) {
          $sqlOsFinalizada = "UPDATE tbl_os SET data_fechamento = null, finalizada = null WHERE os = $os and finalizada notnull;";
          $res = pg_query($con, $sqlOsFinalizada);
          $msg_erro = pg_last_error($con);
    }

    if (strlen($msg_erro)==0){
        $msg_observacao = "Seu pedido de envio de produto para conserto/troca, referente a O.S $sua_os foi cancelada pela fábrica. <br><br> $justificativa ";
        if ($login_fabrica == 30) {
          $msg_observacao = "A(s) Peça(s) da OS $os foi/foram Recusada(s) na Auditoria. <br><br> $justificativa ";
        }
      $sql = "INSERT INTO tbl_comunicado (
                descricao              ,
                mensagem               ,
                tipo                   ,
                fabrica                ,
                obrigatorio_os_produto ,
                obrigatorio_site       ,
                posto                  ,
                ativo
              ) VALUES (
                'Pedido de Peças CANCELADO',
                '$msg_observacao',
                'Pedido de Peças',
                $login_fabrica,
                'f' ,
                't',
                $posto,
                't'
          );";
      $res       = pg_query($con,$sql);
      $msg_erro .= pg_last_error($con);

      if($login_fabrica == 30 AND strlen($msg_erro) == 0){

        $sqlServ = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND descricao = 'Cancelado'";
        $resServ = pg_query($con,$sqlServ);

        if(pg_num_rows($resServ) > 0){

          $servico_realizado_cancelado = pg_fetch_result($resServ, 0, 'servico_realizado');

          $sqlItem = "UPDATE tbl_os_item SET servico_realizado = {$servico_realizado_cancelado}
                  FROM tbl_os_produto
                  WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto
                  AND tbl_os_produto.os = {$os}
                  AND tbl_os_item.pedido IS NULL";
          $resItem = pg_query($con,$sqlItem);
        }

      }

      if($login_fabrica >= 131 && !in_array($login_fabrica, array(145))){

        if(pg_query($con, "SELECT fn_os_excluida($os, $login_fabrica, $login_admin)")){
  		    $justificativa = substr($justificativa,0, 150);
          $sql_motivo = "UPDATE tbl_os_excluida SET motivo_exclusao = '{$justificativa}' WHERE os = {$os} AND fabrica = {$login_fabrica}";
          $res_motivo = pg_query($con, $sql_motivo);

        }

      }

      //HD 416877 -  Envia e-mail também após reprovar a OS
      if ($login_fabrica == 86){
        $sql = "INSERT INTO tbl_os_status
            (os,status_os,data,observacao,admin)
            VALUES ($os,15,current_timestamp,'$justificativa',$login_admin)";

        $res  = pg_query($con,$sql);
        $msg .= pg_last_error($con);

        $sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin)";
        $res = pg_query($con, $sql);

        $sql ="UPDATE tbl_os_excluida SET motivo_exclusao = '$justificativa' WHERE os = $os ";
        $res = pg_query($con,$sql);

        $sql = "SELECT contato_email
                  FROM tbl_posto_fabrica
                 WHERE posto   = $posto
                   AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);

        $destinatario = (pg_num_rows($res)>0) ? pg_fetch_result($res,0,0) : "";

        if (!empty($destinatario)){

            $assunto   = " OS RECUSADA E EXCLUIDA PELO FABRICANTE ";
            $mensagem  = "<center>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</center><br><br>";
            $mensagem .= "At. Responsável,<br><br>A OS $sua_os foi recusada e excluida pelo seguinte motivo: <br> $justificativa. <br>";
            $mensagem .= "Qualquer duvida contatar a sua atendente regional.<br>";
            $mensagem .= "<b><font color='red'>FAMASTIL</font></b>";
            // To send HTML mail, the Content-type header must be set
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // Additional headers
            $headers .= "To: $destinatario" . "\r\n";
            $headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n";


            $username = 'tc.sac.famastil@gmail.com';
            $senha = 'tcfamastil';


            $mailer = new PhpMailer(true);

            $mailer->IsSMTP();
            $mailer->Mailer = "smtp";

            $mailer->Host = 'ssl://smtp.gmail.com';
            $mailer->Port = '465';
            $mailer->SMTPAuth = true;

            $mailer->Username = $username;
            $mailer->Password = $senha;
            $mailer->SetFrom($username, $username);
            $mailer->AddAddress($destinatario,$destinatario );
            $mailer->Subject = utf8_encode($assunto);
            $mailer->Body = utf8_encode($mensagem);

            try{
                $mailer->Send();
            }catch(Exception $e){
                $msg_erro = "Mensagem não enviada";
            }

            //mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
        }
      }
    }

    if (strlen($msg_erro)==0){
      //HD 255530: Alterei pois não faz sentido para a Nova, ninguém pediu esta condição e sem ela as peças vão ser pedidas
      if ($login_fabrica <> 43) {
        $retorno_conserto_sql = "AND   tbl_peca.retorna_conserto IS TRUE";
      }
      //HD 255530 FIM

      #HD 308972
      $sql_pecas_atualizar= '';
      if ($login_fabrica == 3 and is_array($_POST['pecas_selecionadas'])) {
          $pecas_post  = implode(',', array_map('anti_injection', $_POST['pecas_selecionadas']));
          $sql_pecas_atualizar= " AND tbl_peca.peca IN($pecas_post) ";
      }

      if($login_fabrica == 131){
          $sql = "UPDATE tbl_os_item
                     SET servico_realizado = 10776
                    FROM tbl_os_produto
                   WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto
                     AND tbl_os_produto.os = $os";
          } else {

            if (isset($novaTelaOs)) {
                $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND UPPER(descricao) = 'CANCELADO'";
                $res = pg_query($con, $sql);

                if (!pg_num_rows($res)) {
                    $msg_erro = "Erro ao cancelar pedido de peças";
                } else {
                    $servico_realizado = pg_fetch_result($res, 0, "servico_realizado");

                    $sql =  "UPDATE tbl_os_item SET
                        servico_realizado          = $servico_realizado,
                        admin                      = $login_admin,
                        liberacao_pedido           = FALSE,
                        liberacao_pedido_analisado = FALSE,
                        data_liberacao_pedido      = NULL
                        WHERE os_item IN (
                            SELECT os_item
                            FROM tbl_os
                            JOIN tbl_os_produto USING(os)
                            JOIN tbl_os_item    USING(os_produto)
                            JOIN tbl_peca       USING(peca)
                            JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
                            WHERE tbl_os.os                   = $os
                            AND tbl_os.fabrica                = $login_fabrica
                            AND tbl_servico_realizado.troca_de_peca IS TRUE
                            AND tbl_servico_realizado.ativo IS TRUE
                            AND tbl_servico_realizado.gera_pedido IS TRUE
                            AND tbl_os_item.pedido IS NULL
                        )";
                }
            } else {
                $sql =  "UPDATE tbl_os_item SET
                    servico_realizado          = $id_servico_realizado_ajuste,
                    admin                      = $login_admin                ,
                    liberacao_pedido           = FALSE                       ,
                    liberacao_pedido_analisado = FALSE                       ,
                    data_liberacao_pedido      = NULL
                    WHERE os_item IN (
                        SELECT os_item
                        FROM tbl_os
                        JOIN tbl_os_produto USING(os)
                        JOIN tbl_os_item    USING(os_produto)
                        JOIN tbl_peca       USING(peca)
                        WHERE tbl_os.os                     = $os
                        AND tbl_os.fabrica                = $login_fabrica
                        AND tbl_os_item.servico_realizado = $id_servico_realizado
                        AND tbl_os_item.pedido IS NULL
                        /* HD 255530 - Somente a linha $retorna_conserto_sql */
                        $retorna_conserto_sql
                        $sql_pecas_atualizar
                    )";
            }
      }

      $res = pg_query($con,$sql);
      $msg_erro = pg_last_error($con);

      if (empty($msg_erro) && in_array($login_fabrica,array(6,131,138,140,142,143))) {
        $result = pg_query_params(
  	      $con,
  	      'SELECT fn_os_excluida($1, $2, $3)',
  	      array($os, $login_fabrica, $login_admin)
        );

        if(!$result)
          $msg_erro = pg_last_error($con);
      }

      if($login_fabrica == 74){
        $sql = "UPDATE tbl_os SET cancelada = TRUE WHERE os = $os AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
      }

        if ($login_fabrica == 30 and !empty($data_fechamento)) {
              $sqlOsFinalizada = "UPDATE tbl_os SET data_fechamento = '$data_fechamento', finalizada = '$finalizada' WHERE os = $os;";
              $res = pg_query($con, $sqlOsFinalizada);
              $msg_erro = pg_last_error($con);
        }

      if (strlen($msg_erro)>0){
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
      }else {
        $res = @pg_query ($con,"COMMIT TRANSACTION");
         $msg = "<div class='subtitle_acao' style='background-color:#EF4B4B;'>O pedido de peças da OS $sua_os foi cancelado! A OS foi liberada para o posto</div>\n";
      }
    } ?>
  <html>
  <head>
    <title>Intervenção</title>
    <?=$styleTag_iframe?>
  </head>
  <body>
  <?
    if (strlen($msg_erro)>0){
      echo "Erro: $msg_erro<br>Faça o processo novamente.";
    }else{
      if ($login_fabrica != 3) echo '<script>window.parent.tb_remove();</script>';
      if ($login_fabrica == 117) echo '<script>parent.$("#linha_'.$os.'").remove();parent.$("#justif_'.$os.'").remove();</script>';
    ?>
          <p><?=$msg?></p>
          <p /><p>
            <button class="closeThickBox" type='button' onClick='window.parent.cancelar_os(<?=$os?>); window.parent.tb_remove();'>Voltar</button>
            <!--
            <button type='button' onclick='parent.document.frm_consulta.value="filtrar";parent.document.frm_consulta.submit();'>Voltar</button>
            -->
          </p>
  <?  } ?>
  </body>
  </html>
  <?  exit();
  } else {
  ?>
    <html>
      <head>
        <title>Intervenção</title>
        <?=$styleTag_iframe?>
      </head>
      <body>
  <?php
        echo "<div style='text-align: center; background-color: #ff0000;'><h4 style='color:#ffffff;'><b>OS possui pedido e não pode ser excluida</b></h4></div>";
        echo '<script> 
                setTimeout(function(){
                  window.parent.tb_remove();
                }, 5000);    
              </script>';
  ?>
      </body>
    </html>
  <?php
    exit();
  }
}

if (($_POST['btn_tipo'] == 'reparar' or getPost("reparar",true) or getPost("ajax",'fazer_reparo')) and strlen($os) > 0) {

    $data_inicial = getPost('data_inicial');
    $data_final   = getPost('data_final');
    $str_filtro  .= "&data_inicial=$data_inicial&data_final=$data_final";
    $linha        = getPost('linha');
    $str_filtro  .= "&linha=$linha";
    $familia      = getPost('familia');
    $str_filtro  .= "&familia=$familia";
    $os           = getPost('os');

  if($login_fabrica == 138){
    $model = ModelHolder::init('OS');
    try{
      $model->repairInFactory($os);
    }
    catch(Exception $ex){
      $msg_erro = $ex->getMessage();
    }

  } else if (strlen($a_usam_intervencao[$login_fabrica]) > 0) {

    $sua_os        = trim(getPost("reparar",true));
    $justificativa = getPost("justificativa");

    if (strlen($justificativa)>0){
      $justificativa = "Justificativa: $justificativa";
    }

    $res = @pg_query($con,"BEGIN TRANSACTION");

    try{
      $mensagem = 'O produto da OS '.$os.' será reparado na fábrica. Favor entrar em contato com a fábrica.';
      $descricao = 'Reparo em Fábrica';

      $sql = "SELECT posto FROM tbl_os WHERE os = $os";
      $res = pg_query($con, $sql);

      if(pg_num_rows($res) > 0){
        $posto = pg_fetch_result($res, 0, 'posto');
      }

      $sql = "INSERT INTO tbl_comunicado(
                mensagem ,
                descricao ,
                tipo ,
                fabrica ,
                obrigatorio_site ,
                posto ,
                pais ,
                ativo
              ) VALUES (
                '$mensagem',
                '$descricao',
                'Comunicado' ,
                $login_fabrica ,
                't' ,
                $posto ,
                'BR' ,
                't'
              );";
      $result = pg_query($con,$sql);

      if(!$result)
        throw new Exception(pg_last_error($con));
    }
    catch(Exception $ex){
      $msg_erro = $ex->getMessage();
    }

    $sql = "INSERT INTO tbl_os_status
        (os,status_os,data,observacao,admin)
        VALUES ($os,65,current_timestamp,'Reparo do produto deve ser feito pela fábrica $justificativa',$login_admin)";
    $res      = @pg_query($con,$sql);
    $msg_erro = pg_last_error($con);

    $sql      = "INSERT INTO tbl_os_retorno (os) VALUES ($os)";
    $res      = @pg_query($con,$sql);
    $msg_erro = pg_last_error($con);

    if (!strlen($msg_erro)){

      if($login_fabrica == 127){
        $sqlUpdate = "UPDATE tbl_os SET solucao_os = 4832 WHERE os = $os AND fabrica = $login_fabrica";
        $resUpdate = pg_query($con,$sqlUpdate);
        $msg_erro .= pg_last_error($con);
      }
      $sql = "UPDATE tbl_os_item
             SET servico_realizado      = $id_servico_realizado_ajuste,
             liberacao_pedido           = FALSE,
             liberacao_pedido_analisado = FALSE,
             data_liberacao_pedido      = NULL,
             admin                      = $login_admin
           WHERE os_item IN (
              SELECT os_item
                FROM tbl_os
                JOIN tbl_os_produto USING(os)
                JOIN tbl_os_item    USING(os_produto)
                JOIN tbl_peca       USING(peca)
               WHERE tbl_os.os                     = $os
                 AND tbl_os.fabrica                = $login_fabrica
                 AND tbl_os_item.servico_realizado = $id_servico_realizado
                 AND tbl_os_item.pedido IS NULL)";
      $res      = pg_query($con,$sql);
      if(!strlen($msg_erro) and $login_fabrica == 90) {

        $sql = "UPDATE tbl_os_produto SET
              os = 4836000
            FROM   tbl_os_item
            WHERE  tbl_os_produto.os            = $os AND
                 tbl_os_produto.os_produto    = tbl_os_item.os_produto AND
                 tbl_os_item.pedido           IS NULL AND
                 tbl_os_item.liberacao_pedido IS false";

        $res       = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);
      }

      // HD-962530
      if(!strlen($msg_erro) and ($login_fabrica == 90 OR $login_fabrica == 117)) {

        $sql = "SELECT  tbl_posto_fabrica.contato_email
                  FROM  tbl_posto_fabrica
                  JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto AND
                         tbl_os.os  = $os
                 WHERE   tbl_posto_fabrica.fabrica = $login_fabrica";

        $res = pg_query($con,$sql);

        $destinatario = pg_num_rows($res) ? pg_fetch_result($res,0,'contato_email') : "";

        if(!empty($destinatario)) {
          /*$mail = new PHPMailer();
          $mail->IsHTML(true);
          $mail->From     = 'helpdesk@telecontrol.com.br';
          $mail->FromName = 'Telecontrol';*/

          $assunto   = "A O.S $os em intervencao foi solicitado o reparo do produto na fabrica.";

          if($login_fabrica == 90){
            $mensagem  = "Solicitamos que o produto seja enviado para reparo na fabrica conforme orientacoes abaixo:<br/>";
            $mensagem .= "Emitir a N.F de Remessa para Conserto e enviar uma copia da nota para o e-mail:  garantias@ibbl.com.br  que solicitara a coleta;<br/>";
            $mensagem .= "A O.S ficara aberta e em intervencao no sistema ate o retorno do produto a assistencia, que sera liberada a O.S para finalizacao;<br/>";
            $mensagem .= "As pecas solicitadas na O.S serao excluidas automaticamente.<br/><br/>";
            $mensagem .= "Para maiores esclarecimentos da O.S, entrar em contato pelo Telefone: (11) 2118-2126.<br/><br/>";
            $mensagem .= "Atenciosamente,<br/><br/>";
            $mensagem .= "<i><b>Nabil Kyriazi Filho</b><br/>";
            $mensagem .= "Supervisor SAC / Garantias / Assistencia Tecnica<br/>";
            $mensagem .= "Industria Brasileira de Bebedouros Ltda</i>";
          }else{
            $mensagem  = "Solicitamos procederem com a sistem&aacute;tica de CAPTA&Ccedil;&Atilde;O DE PRODUTO, conforme instru&ccedil;&atilde;o dispon&iacute;vel na aba Info. T&eacute;cnica â€“ Procedimentos.<br/><br/>";
            $mensagem .= "Emitir a N.F de Remessa para Conserto para:<br/>";
            $mensagem .= "ELGIN S/A<br/>";
            $mensagem .= "Rua Bar&atilde;o de Campinas, 305 â€“ Campos El&iacute;seos<br/>";
            $mensagem .= "CNPJ: 52.556.578/0008-07 INSC EST: 103.634.429.115<br/>";
            $mensagem .= "01201-901 â€“ S&atilde;o Paulo â€“ Capital<br/>";
            $mensagem .= "A/C SESERV<br/><br/>";
            $mensagem .= "Solicitar coleta somente pela transportadora previamente autorizada pela ELGIN.<br/>";
            $mensagem .= "No caso de d&uacute;vidas, contate o departamento de suporte t&eacute;cnico.<br/><br>";
            $mensagem .= "Atenciosamente,<br/>";
            $mensagem .= "Suporte t&eacute;cnico<br/>";
            $mensagem .= " Elgin S/A.";
          }

          $headers  = 'MIME-Version: 1.0' . "\r\n";
          $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

          // Additional headers
          $headers .= "To: $destinatario" . "\r\n";
          $headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n";

          mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
        }
      }
      if (strlen($msg_erro)>0){
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
      }
      else {
        $res = @pg_query ($con,"COMMIT TRANSACTION");
      }
    }
    if( in_array($login_fabrica, array(11,172)) ){?>

      <html>
      <head>
        <title>Intervenção</title>
      </head>
      <body>
        <script language='javascript'>
          opener.document.location = '<?=$PHP_SELF?>';
          setTimeout('window.close()',5000);
        </script>
      </body>
      </html><?
          exit();
    }else{
      if($login_fabrica == 117){//hd_chamado=2768184
        echo "Reparook|$os";exit;
      }else{
        header("Location: $PHP_SELF?msg=$msg$str_filtro");
      }

    }
  }
  else
  {
    $msg_erro = "Ocorreu um erro no reparo. Entre em contato com o suporte da Telecontrol para resolver o erro.";
  }
  }

# antigo
if (trim($_POST['btn_tipo'])=="autorizar" && strlen($os) > 0) {

  $os       =trim($_POST['os']);
  $justificativa  =trim($_POST['justificativa']);
  if (strlen($justificativa)>0){
    $justificativa = "Justificativa: $justificativa";
  }else{
    $msg_erro = "Informe a justificativa!";
  }
  $sql = "SELECT status_os,
           TO_CHAR(data,'DD/MM/YYYY') AS data,
           observacao,
           admin
              FROM tbl_os_status
             WHERE os        = $os
               AND status_os IN (62, 64, 65)
               AND tbl_os_status.fabrica_status = $login_fabrica
       ORDER BY tbl_os_status.data DESC LIMIT 1";
  $res = pg_query($con,$sql);
  $total=pg_num_rows($res);
  if ($total>0){
    $st_os    = trim(pg_fetch_result($res, 0, 'status_os'));
    $st_data  = trim(pg_fetch_result($res, 0, 'data'));
    $st_obs   = trim(pg_fetch_result($res, 0, 'observacao'));
    $st_admin = trim(pg_fetch_result($res, 0, 'admin'));
    if ($st_os=='64'){
      echo "<html><head><title='Intervenção'></head><body>";
      echo "OS LIBERADA!!!<br><br>id OS: $os<br>EM: $st_data<br>Observação: $st_obs<br>Admin: $st_admin";
      echo "<script language='javascript'>";
      echo "opener.document.location = '$PHP_SELF';";
      echo "setTimeout('window.close()',3000);";
      echo "</script>";
      echo "</body>";
      echo "</html>";
      exit();
    }
  }
  $res = @pg_query($con,"BEGIN TRANSACTION");
  if($login_fabrica==19) {
    $obs_lib = "OSs Aprovada da Intervensão Pela Fábrica";
  } else {
    $obs_lib = "Pedido de Peças Autorizado Pela Fábrica ".$justificativa;
  }

  $sql = "INSERT INTO tbl_os_status
      (os,status_os,data,observacao,admin)
      VALUES
        ($os,64,CURRENT_TIMESTAMP,'$obs_lib',$login_admin)";
  $res = pg_query($con,$sql);



  $msg_erro = pg_last_error($con);

  if (strlen($msg_erro)>0){
    $res = @pg_query ($con,"ROLLBACK TRANSACTION");
  }else {

    $res = @pg_query ($con,"COMMIT TRANSACTION");


    $msg = "Pedido de peças da OS $sua_os foi autorizado. A OS foi liberada para o posto";
  }?>
  <html>
    <head>
      <title>Intervenção</title>
    </head>
    <body>
      <script language='javascript'>
        opener.document.frm_consulta.btnacao.value='filtrar';
        opener.document.frm_consulta.submit();
        window.close();
      </script>
      <br>
    </body>
  </html>
  <?
  exit();
}

// HD 21341
$justific     = $_POST['justific'];

if(strlen($justific)==0){
  $justific="Pedido de Peças Autorizado Pela Fábrica";
}
$autorizar    = $_POST['autorizar'];
$autorizar_os = $_POST['autorizar_os'];

if(strlen($os) == 0) {
  $autorizar_os = $_GET['autorizar_os'];
}

if(($login_fabrica==3 or $login_fabrica == 14) and strlen($justific) >0 and strlen($autorizar) >0) {
  $res = @pg_query($con,"BEGIN TRANSACTION");
  $sql = "INSERT INTO tbl_os_status
             (os,status_os,data,observacao,admin)
      VALUES ($autorizar_os,64,current_timestamp,'$justific',$login_admin)";
  $res = pg_query($con,$sql);
  $msg_erro .= pg_last_error($con);

  $sql_aprovar = "SELECT tbl_os.os
                  FROM tbl_os 
                  JOIN tbl_os_status USING(os)
                  WHERE tbl_os.os = $autorizar_os
                  AND tbl_os.fabrica = $login_fabrica
                  AND tbl_os.data_abertura < CURRENT_DATE - INTERVAL '20 days'
                  AND tbl_os_status.status_os IN(72,73)
                  AND (SELECT status_os FROM tbl_os_status os_status WHERE tbl_os.os = os_status.os AND os_status.status_os IN (72,73) AND os_status.fabrica_status = $login_fabrica ORDER BY data DESC LIMIT 1) = 72";

  $res_aprovar = pg_query($con,$sql_aprovar);                  
  $msg_erro .= pg_last_error($con); 

  if($login_fabrica == 3 and pg_num_rows($res_aprovar) > 0){  
    $res_aprovar = @pg_query($con,"BEGIN TRANSACTION");  
    $sql_i_autorizar = "INSERT INTO tbl_os_status
                          (os,status_os,data,observacao,admin)
                          VALUES ($autorizar_os,73,current_timestamp,'$justific',$login_admin)";
    $res_i_aprovar = pg_query($con,$sql_i_aprovar);                  
    $msg_erro .= pg_last_error($con); 
  }

  if (strlen($msg_erro)>0){
    $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    $res_aprovar = @pg_query ($con,"ROLLBACK TRANSACTION");

    #HD 308972
    if($login_fabrica==3){
      echo 'NO|Ocorreu um erro e a OS não pode ser autorizada';
    }

  }else {
    $res = @pg_query ($con,"COMMIT TRANSACTION");
    $res_aprovar = @pg_query ($con,"COMMIT TRANSACTION");
    $msg = "Pedido de peças da OS $autorizar foi autorizado. A OS foi liberada para o posto";

    #HD 308972
    if($login_fabrica==3){
      $ident_os = $_POST['ident_os'];
      echo 'OK|'.$ident_os;
    }
  }

  if($login_fabrica!=3){
    header("Location: $PHP_SELF?msg=$msg$str_filtro");
  }
  exit();
}

if (strlen(trim($_GET['os_autorizar'])) > 0) {

  $valor_os =trim($_GET['os']);
  $quantidade_os = trim($_GET['quantidade']);

  $numero_os = explode(',',$valor_os);

  for($c=0;$c < $quantidade_os;$c++) {

    $os = $numero_os[$c];

    if($os <> '') {
      $res = @pg_query($con,"BEGIN TRANSACTION");

      $sql = "INSERT INTO tbl_os_status
          (os,status_os,data,observacao,admin) VALUES ($os,64,current_timestamp,'Pedido de Peças Autorizado Pela Fábrica',$login_admin)";
      $res = pg_query($con,$sql);
      $msg_erro .= pg_last_error($con);

      if (!$msg_erro){

        $sql_updt = "
              UPDATE tbl_os_item
                 SET data_liberacao_pedido = NOW(),
                     liberacao_pedido = TRUE
               WHERE os_item IN (
                  SELECT os_item
                    FROM tbl_os
                    JOIN tbl_os_produto USING(os)
                    JOIN tbl_os_item    USING(os_produto)
                    JOIN tbl_peca       USING(peca)
                   WHERE tbl_os.os=$os
                     AND tbl_os.fabrica=$login_fabrica
              )";

        $res_updt= pg_query($con,$sql_updt);
        $msg_erro = pg_last_error($con);

        if ($login_fabrica == 86 and !$msg_erro){

                      $sqlM = "SELECT laudo_tecnico FROM tbl_os_extra WHERE os = $os AND laudo_tecnico NOTNULL";
                      $resM = pg_query($con,$sqlM);
                      if(pg_num_rows($resM) > 0){
                        $sql_updt = "UPDATE tbl_os
                                        SET finalizada = current_timestamp,
                                            data_fechamento = current_date
                                      WHERE os  = $os
                                        AND fabrica = $login_fabrica";
                              $res_updt= pg_query($con,$sql_updt);
                              $msg_erro = pg_last_error($con);
                      }

              }

      }



      if (strlen($msg_erro)>0){
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
      }else {
        $res = @pg_query ($con,"COMMIT TRANSACTION");
      }
    }
  }

  $msg = "Pedido de peças da OS $sua_os foi autorizado. A OS foi liberada para o posto";
  header("Location: $PHP_SELF?msg=$msg$str_filtro");
  exit();
}

if (strlen(trim($_GET['autorizar'])) > 0 && strlen($os) > 0  ) {

  $res = @pg_query($con,"BEGIN TRANSACTION");

  if($login_fabrica == 145){

    $sql = "SELECT status_os
              FROM tbl_os_status
             WHERE os = $os
               AND status_os IN(62, 64, 65, 199)
               AND tbl_os_status.fabrica_status = $login_fabrica
          ORDER BY tbl_os_status.data DESC LIMIT 1";

    $res = pg_query($con,$sql);
    $msg_erro .= pg_last_error($con);

    $st_os = pg_fetch_result($res, 0, 'status_os');

    $status_aprova = ($st_os == 199) ? 200 : 64;
    $obs = ($st_os == 199) ? "OS Aprovada de auditoria de Analise da fábrica" : "Pedido de Peças Autorizado Pela Fábrica";

    $sql = "INSERT INTO tbl_os_status
        (os,status_os,data,observacao,admin) VALUES ($os, $status_aprova, current_timestamp, '$obs',$login_admin)";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_last_error($con);

  }else{
    $sua_os=trim($_GET['autorizar']);

    if($login_fabrica == 114){ //hd_chamado=2634503
      $sql_inter = "SELECT status_os
                FROM tbl_os_status
               WHERE os = $os
                 AND status_os IN(62,64)
                 AND tbl_os_status.fabrica_status = $login_fabrica
            ORDER BY tbl_os_status.data DESC LIMIT 1";
      $res_inter = pg_query($con, $sql_inter);

      $st_os_inter_auditoria = pg_fetch_result($res_inter, 0, 'status_os');

      $sql_analise = "SELECT status_os
                FROM tbl_os_status
               WHERE os = $os
                 AND status_os IN(19,20)
                 AND tbl_os_status.fabrica_status = $login_fabrica
            ORDER BY tbl_os_status.data DESC LIMIT 1";
      $res_analise = pg_query($con, $sql_analise);

      $st_os_inter = pg_fetch_result($res_analise, 0, 'status_os');

	}else{
		$cond_status = ($login_fabrica == 3) ? '62,64,65,127,147,167,175,199,200,201' : '62,64,65,127,147,167,199,200,201';

		if ($login_fabrica == 138) {
			$cond_status = "62,64,65,127,147,199,200,201";
		}

		if ($login_fabrica == 35 or $login_fabrica == 52) {
			$cond_status = '62,64,65,127,19';
		}

		if($login_fabrica == 94){
			$cond_status = "62,64,65,127,147,167,176,199,200,201";
		}

      $sql = "SELECT  status_os
              FROM    tbl_os_status
              WHERE   os = $os
			  AND     tbl_os_status.fabrica_status = $login_fabrica
			  AND		status_os in ($cond_status)
        ORDER BY      tbl_os_status.data DESC
              LIMIT   1";
      $res = pg_query($con,$sql);
      $st_os = pg_fetch_result($res, 0, 'status_os');
    }

    if($login_fabrica == 114){
      if($st_os_inter_auditoria == 62){
        $sql = "INSERT INTO tbl_os_status
          (os,status_os,data,observacao,admin)
            VALUES ($os,64,CURRENT_TIMESTAMP,'Liberada de auditoria de Peça Crítica',$login_admin)";
      }
      if($st_os_inter == 20){
        $sql = "INSERT INTO tbl_os_status
          (os,status_os,data,observacao,admin)
            VALUES ($os,19,CURRENT_TIMESTAMP,'Liberada da intervenção',$login_admin)";
      }
    }else{
		//mesmo status, mas com observação diferente
		if ($login_fabrica == 52 ) {
		  $sql = "INSERT INTO tbl_os_status
			  (os,status_os,data,observacao,admin)
			  VALUES ($os,19,CURRENT_TIMESTAMP,'OSs Aprovada da Intervensão Pela Fábrica',$login_admin)";
		}
		if ($login_fabrica == 35) {
		  $sql = "INSERT INTO tbl_os_status
			  (os,status_os,data,observacao,admin)
			  VALUES ($os,19,CURRENT_TIMESTAMP,'Pedido de Peças Autorizado Pela Fábrica',$login_admin)";
		}
		if($login_fabrica == 94 && $st_os == 176){
			$sql = "INSERT INTO tbl_os_status
			  (os,status_os,data,observacao,admin)
			  VALUES ($os,177,CURRENT_TIMESTAMP,'Aprovação de OS Revenda',$login_admin)";
		}else{
			 $sql = "INSERT INTO tbl_os_status
			  (os,status_os,data,observacao,admin)
				VALUES ($os,64,CURRENT_TIMESTAMP,'Liberada da auditoria de Peça Crítica',$login_admin)";
		}
    }


    $res = pg_query($con,$sql);
    $msg_erro .= pg_last_error($con);

  }

  if ($login_fabrica == 6){
    # Peças com serviço ENVIO PARA A FABRICA, QUANDO AUTORIZA TROCA O SERVIÇO PARA TROCA DE PEÇA
        $sql = "
        UPDATE tbl_os_item
         SET servico_realizado = 1
         WHERE servico_realizado = 485
         AND os_produto IN (
            SELECT os_produto
            FROM tbl_os_produto
             WHERE os = $os
        )";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_last_error($con);
  }

  if($login_fabrica == 85) {
    $sql = " UPDATE tbl_os_item SET liberacao_pedido = 't'
         FROM tbl_os_produto
         WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
         AND   os = $os";
    $res = @pg_query($con,$sql);
    $msg_erro = pg_last_error($con);
  }

  #HD 416877 - INICIO
  if ($login_fabrica == 86 and !$msg_erro){

    $sql_updt = "
                    UPDATE tbl_os_item
             SET data_liberacao_pedido = NOW(),
            liberacao_pedido = TRUE
          WHERE os_item IN (
          SELECT os_item
          FROM tbl_os
          JOIN tbl_os_produto USING(os)
          JOIN tbl_os_item USING(os_produto)
          JOIN tbl_peca USING(peca)
               WHERE tbl_os.os      = $os
                 AND tbl_os.fabrica = $login_fabrica
          )";

    $res_updt= pg_query($con,$sql_updt);
    $msg_erro = pg_last_error($con);

    $sqlM = "SELECT laudo_tecnico FROM tbl_os_extra WHERE os = $os AND laudo_tecnico notnull";
    $resM = pg_query($con,$sqlM);
    if(pg_num_rows($resM) > 0){
      $sql_updt = "UPDATE tbl_os SET
            finalizada = current_timestamp,
            data_fechamento = current_date
          WHERE os  =$os
          AND fabrica = $login_fabrica";
      $res_updt= pg_query($con,$sql_updt);
                  $msg_erro = pg_last_error($con);
    }

  }

  if (in_array($login_fabrica, array(141,144))) {
    $sqlStatus = "SELECT fn_os_status_checkpoint_os({$os}) AS status;";
    $resStatus = pg_query($con, $sqlStatus);

    $statusCheckpoint = pg_fetch_result($resStatus, 0, "status");

    $updateStatus = "UPDATE tbl_os SET status_checkpoint = {$statusCheckpoint} WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $resStatus = pg_query($con, $updateStatus);

    if (strlen(pg_last_error()) > 0) {
      $msg_erro = "Erro ao autorizar Ordem de Serviço";
    }
  }

  #HD 416877 - FIM

  /**
   * HD-2226476 - Regra para abrir OS para
   * posto interno, igual os troca
   * para autorização de intervenção de
   * os revenda
   *
   * @author William Ap. Brandino
   * @fabrica EVEREST
   */
    if ($login_fabrica == 94 && $st_os == 176) {
        $tipo_atendimento = $_GET['tipo_atendimento'];

        $sql = "
            SELECT  tbl_posto.posto,
                    tbl_posto.cnpj,
                    tbl_posto.nome
            FROM    tbl_os
            JOIN    tbl_posto USING(posto)
            WHERE   os = $os
        ";

        $res = pg_query($con,$sql);
        $posto      = pg_fetch_result($res,0,posto);
        $posto_cnpj = pg_fetch_result($res,0,cnpj);
        $posto_nome = pg_fetch_result($res,0,nome);
        $posto_nome    = substr($posto_nome,0,50);
        $sqlUpOs = "
            UPDATE  tbl_os
            SET     tipo_atendimento = $tipo_atendimento
            WHERE   os      = $os
            AND     fabrica = $login_fabrica
        ";

        $resUpOs = pg_query($con,$sqlUpOs);

        $sql = "INSERT INTO tbl_os(
                    fabrica,
                    posto,
                    admin,
                    produto,
                    serie,
                    nota_fiscal,
                    data_digitacao,
                    data_abertura,
                    data_nf,
                    defeito_constatado,
                    defeito_reclamado_descricao,
                    marca,
                    revenda_cnpj,
                    revenda_nome,
                    consumidor_nome,
                    consumidor_cpf,
                    consumidor_endereco,
                    consumidor_cidade,
                    consumidor_bairro,
                    consumidor_numero,
                    consumidor_complemento,
                    consumidor_estado,
                    consumidor_cep,
                    consumidor_email,
                    consumidor_fone,
                    consumidor_celular,
                    consumidor_fone_comercial,
                    tipo_atendimento,
                    acessorios,
                    aparencia_produto,
                    mao_de_obra
                )
                (
                    SELECT  fabrica,
                            114768,
                            $login_admin,
                            produto,
                            serie,
                            nota_fiscal,
                            data_digitacao,
                            data_abertura,
                            data_nf,
                            defeito_constatado,
                            defeito_reclamado_descricao,
                            marca,
                            '$posto_cnpj',
                            '$posto_nome',
                            '$posto_nome',
                            '$posto_cnpj',
                            consumidor_endereco,
                            consumidor_cidade,
                            consumidor_bairro,
                            consumidor_numero,
                            consumidor_complemento,
                            consumidor_estado,
                            consumidor_cep,
                            consumidor_email,
                            consumidor_fone,
                            consumidor_celular,
                            consumidor_fone_comercial,
                            tipo_atendimento,
                            acessorios,
                            aparencia_produto,
                            mao_de_obra
                    FROM    tbl_os
                    WHERE   fabrica = $login_fabrica
					AND     os = $os
					AND     posto not in (114768,6359)
                ) RETURNING os
        ";

        $res = pg_query($con,$sql);
        $os_interno = pg_fetch_result($res,0,0);

        if(strlen($os_interno) > 0){ //hd_chamado=2530201
          $sql = "SELECT fn_valida_os($os_interno, $login_fabrica)";
          $res = pg_query($con, $sql);

          $sqlInsert = "INSERT INTO tbl_os_campo_extra(os,fabrica,os_troca_origem)
                       VALUES($os_interno,$login_fabrica,$os)";
          $resInsert = pg_query($con, $sqlInsert);
        }

        $sqlUpdate = "UPDATE  tbl_os
                      SET     data_fechamento = CURRENT_DATE,
                              finalizada      = CURRENT_TIMESTAMP
                      WHERE   os = $os;";
        $resUpdate = pg_query($con, $sqlUpdate);

        $vai_frete = ($tipo_atendimento == 209) ? strtoupper($login_fabrica_nome) : "REVENDA";

        if($tipo_atendimento == 209) {
            $texto = "Sua solicitação de envio de produtos para conserto na $login_fabrica_nome foi autorizada<br />";
        }else if($tipo_atendimento == 211){
            $texto = "De acordo com o número de série deste produto, o mesmo já se encontra fora de garantia. Portanto, receberemos o mesmo na condição de <u>ORÇAMENTO</u><br />";
            $texto .= "Após o recebimento do mesmo, enviaremos um orçamento por e-mail para sua prévia aprovação.<br />";
            $texto .= "Após a aprovação, realizaremos o conserto e geraremos uma nota fiscal de venda das peças substituídas neste conserto, assim como uma nota fiscal de serviço referente ao valor da mão-de-obra.<br />";
        }

        switch($tipo_atendimento){
            case 209:
            case 211:
                $mensagem ="<p>";
                $mensagem .= $texto;
                $mensagem .= "Dados para a emissão da NFe.:<br />";
//                 $mensagem .= "<span style='color:#F00;'>";
                $mensagem .= "<ul style='color:#F00;'>";
                $mensagem .= "<li>Natureza de operação: REMESSA PARA CONSERTO</li>";
                $mensagem .= "<li>CFOP:6915 (fora RJ) / 5915 (RJ)</li>";
                $mensagem .= "<li>Impostos: Esta operação é isenta de impostos</li>";
                $mensagem .= "<li>Prazo de liberação do produto consertado: até 15 dias</li>";
                $mensagem .= "<li style='font-weight:bold;'>FRETE POR CONTA DA $vai_frete</li>";
                $mensagem .= "</ul>";
//                 $mensagem .= "</span>";
                $mensagem .= "</p>";
            break;
            case 210:
                $mensagem ="<p>";
                $mensagem .= "Sua solicitação de envio de produtos para troca na $login_fabrica_nome foi autorizada<br />";
                $mensagem .= "Dados para a emissão da NFe.:<br />";
//                 $mensagem .= "<span style='color:#F00;'>";
                $mensagem .= "<ul style='color:#F00;'>";
                $mensagem .= "<li>Natureza de operação: SUBSTITUIÇÃO EM GARANTIA (OUTRAS SAÍDAS)</li>";
                $mensagem .= "<li>CFOP:6949 (fora RJ) / 5949 (RJ)</li>";
                $mensagem .= "<li>Impostos: Terá incidência do ICMS, inclusive do ICMS ST, quando a revenda for localizada em UF signatário de protocolo com o Rio de Janeiro (MG, SP, RS, SC, PR, MT e AP), mesmo para as revendas cadastradas no Simples Nacional</li>";
                $mensagem .= "<li>Prazo de liberação do novo produto: até 5 dias</li>";
                $mensagem .= "<li style='font-weight:bold;'>FRETE POR CONTA DA ".strtoupper($login_fabrica_nome)."</li>";
                $mensagem .= "</ul>";
//                 $mensagem .= "</span>";
                $mensagem .= "</p>";
            break;
        }

        $mensagem = addslashes($mensagem);
        $os_msg = explode('&',$sua_os);
        $sua_os_msg = $os_msg[0];
        $sqlCom = "
            INSERT INTO tbl_comunicado(
                mensagem,
                descricao,
                tipo,
                fabrica,
                obrigatorio_site,
                posto,
                pais,
                ativo
            ) VALUES (
                (E'$mensagem'),
                'OS $sua_os_msg liberada de interação de REVENDA',
                'Comunicado',
                $login_fabrica,
                't',
                $posto,
                'BR',
                't'
            );
        ";
//         echo $sqlCom;exit;
        $resCom = pg_query($con,$sqlCom);
        $msg_erro = pg_last_error($con);

//         echo pg_last_error($con);exit;
    }
  if (strlen($msg_erro)){
    $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    if($login_fabrica == 90 or $login_fabrica == 35) {
      exit(utf8_encode("Erro ao autorizar o posto.\nErro: $msg_erro."));
    }
  }else {
    $res = @pg_query ($con,"COMMIT TRANSACTION");

      if ($telecontrol_distrib && !isset($novaTelaOs)) {
        if (!os_em_intervencao($os)) {

          $descricao_status_anterior = get_ultimo_status_os($os);
          
          atualiza_status_checkpoint($os, $descricao_status_anterior);

        }
      }

    if($login_fabrica == 145 && $st_os == 199){
        $msg = "A OS foi liberada para o posto da auditoria de Analise da fabrica.@@@ok";
    }else if($login_fabrica == 94 && $st_os == 176){
        $msg = "A OS foi liberada para o posto.@@@ok";
    }else{
        $msg = "Pedido de peças da OS $sua_os foi autorizado. A OS foi liberada para o posto.@@@ok";
    }
    echo (utf8_encode($msg));
    exit;
  }

}

if (strlen($_GET['trocar']) > 0 && strlen($os) > 0  ) {

  $sua_os=trim($_GET['trocar']);
  if (strlen($sua_os)>0){
    if (isset($novaTelaOs) || $login_fabrica == 141) {
      header("Location: os_troca_subconjunto.php?os={$os}");
    } else {
      header("Location: os_cadastro.php?os=$os$str_filtro&osacao=trocar");
    }
    exit();
  }
  ## ao inves de colocar o status novo, redireciona e o status novo vai ser colocado na os_cadastro.php
  $res = @pg_query($con,"BEGIN TRANSACTION");
  $sql = "INSERT INTO tbl_os_status
      (os,status_os,data,observacao,admin)
      VALUES ($os,64,current_timestamp,'Troca do Produto',$login_admin)";
  $res = pg_query($con,$sql);
  $msg_erro = pg_last_error($con);

  if (strlen($msg_erro)>0){
    $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    header("Location: $PHP_SELF?msg_erro=$msg_erro");
    exit();
  } else {
    $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    header("Location: os_cadastro.php?os=$os$str_filtro");
    exit();
  }
}

if (strlen($_POST['confirmar_chegada']) > 0 && strlen(trim($_POST['txt_data_envio_chegada']))>0) {
  $os = trim($_GET['autorizar_os']);
  $data_envio_chegada=trim($_POST['txt_data_envio_chegada']);
  $data_envio_chegada= @converte_data($data_envio_chegada);

  if ($data_envio_chegada==false)
    $msg_erro.="Data de chegada à fábrica inválida!";
  $data_envio_chegada_x = $data_envio_chegada." ".date("H:i:s");
  if (strlen($msg_erro)==0){
    $res = @pg_query($con,"BEGIN TRANSACTION");
    $sql =  "UPDATE tbl_os_retorno
        SET envio_chegada='$data_envio_chegada_x',
          admin_recebeu=$login_admin
        WHERE os=$os";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_last_error($con);
    if (strlen($msg_erro)>0){
      $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    }
    else {
      $res = @pg_query ($con,"COMMIT TRANSACTION");
    }
  }
  header("Location: $PHP_SELF?msg_erro=$msg_erro$str_filtro");
}

if (strlen($_POST['confirmar_retorno']) > 0 && strlen(trim($_POST['txt_nota_fiscal_retorno']))>0) {
  $os = trim($_GET['autorizar_os']);
  $nota_fiscal_retorno  = trim($_POST['txt_nota_fiscal_retorno']);
  $rastreio_retorno   = trim($_POST['txt_rastreio_retorno']);
  $data_envio_retorno   = trim($_POST['txt_data_envio_retorno']);

  if (strlen($nota_fiscal_retorno)==0 OR strlen($nota_fiscal_retorno)>6 OR (strlen($rastreio_retorno)==0 AND $login_fabrica<>6) OR strlen($data_envio_retorno)!=10){
    $msg_erro.="Dados do Envio à Fábrica incorretos";
  } else {

    $data_envio_retorno= @converte_data($data_envio_retorno);
    if ($data_envio_retorno==false) $msg_erro .="Data de envio do produto ao posto inválido!";
  }
  if (strlen($msg_erro)==0){
    $res = @pg_query($con,"BEGIN TRANSACTION");
    $sql =  "UPDATE tbl_os_retorno
           SET nota_fiscal_retorno       = '$nota_fiscal_retorno',
             data_nf_retorno             = '$data_envio_retorno' ,
             numero_rastreamento_retorno = '$rastreio_retorno'   ,
             admin_enviou                = $login_admin
                 WHERE os = $os";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_last_error($con);
    if (strlen($msg_erro)>0){
      $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    } else {
      $res = @pg_query ($con,"COMMIT TRANSACTION");
    }
  }
  header("Location: $PHP_SELF?msg_erro=$msg_erro$str_filtro");
}
$layout_menu = (in_array($login_fabrica, array(108,111))) ? 'gerencia' : 'auditoria';
$title = "OS's com intervenção da Fábrica";

include "cabecalho.php";

if ($login_fabrica == 30) {
  $plugins = array(
      "shadowbox"
  );
}

?>
<script type='text/javascript' src="js/jquery-1.6.1.min.js"></script>
<?
if ($ordem_tabela_js) { // hd-674410
?>
  <script type="text/javascript" src="../plugins/jquery/jquery.tablesorter.js"></script>
  <link rel="stylesheet" href="../plugins/jquery/tablesorter/themes/telecontrol/style.css" type="text/css" media="all" />
  <center>
<?php
}
if ($usa_filtro_linha) {?>
  <script type="text/javascript" language="JavaScript">
    $().ready(function() {
      $('input[name^=posto_]').change(function () {
        var info_posto = $(this).val();
        var tipo_info  = $(this).attr('name').substr(6);
        var caixa_linhas = $('fieldset#linhas');
        var fs_content = caixa_linhas.html();
        var linha_oculta = $('#linhas_ocultas').val();
        //console.log(linha_oculta);

        caixa_linhas.html("<p>Atualizando...</p>");

        $.get('<?=$PHP_SELF?>',{
          'ajax':'linhas_posto',
          'info_posto':info_posto,
          'tipo_info':tipo_info,
          'linha_oculta':linha_oculta,
        },function(data){
          if (data.substr(0,2) != 'ko' && data.indexOf("label>") > 0) {
            caixa_linhas.html(data);
          } else {
            caixa_linhas.html(fs_content);
          }
        });
      });
      $('input[name=posto_codigo]').change();
    });
  </script>
<?}?>
<style type="text/css">
  .status_checkpoint{width:15px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
  .status_checkpoint_sem{width:15px;height:15px;margin:2px 5px;padding:0 5px;}
  .peca {
    border-top: 1px solid black;
  }
  .Tabela{
    border:1px solid #596D9B;
    background-color:#596D9B;
  }
  .Erro{
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 12px;
    color:#CC3300;
    font-weight: bold;
    background-color:#FFFFFF;
  }
  .Titulo {
    text-align: center;
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
  }
  .Conteudo {
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
  }
  .inpu{
    border:1px solid #666;
    font-size:9px;
    height:12px;
  }
  .botao2{
    border:1px solid #666;
    font-size:9px;
  }
  .butt{
    border:1px solid #666;
    background-color:#ccc;
    font-size:9px;
    height:16px;
  }
  .menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: bold;
    border: 1px solid;
    color:#ffffff;
    background-color: #596D9B
  }
  .table_line {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    border: 0px solid;
    background-color: #D9E2EF
  }
  label {padding-top:0;position:relative;top:-3px}
  .table_line2 {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
  }
  .justificativa{
    font-size: 10px;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
  }
  .frm {
    background-color:#F0F0F0;
    border:1px solid #888888;
    font-family:Verdana;
    font-size:8pt;
    font-weight:bold;
  }
  .titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
  }
  .titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
  }


  .msg_erro{
    background-color:#FF0000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
  }

  .sucesso{
    background-color:green;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
  }

  .formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
  }

  .subtitulo{

    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
    text-align:center;
  }

  table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
  }

  .texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
  }

  .informacao{
    font: 14px Arial; color:rgb(89, 109, 155);
    background-color: #C7FBB5;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
  }

  .os_motivo_interv {
    color:#5B5B5B;
    text-align: left;
    font-style: italic;
  }

  #tablesorter thead tr th {
    cursor: pointer;
  }
  .espaco{padding-left:30px; }
  table tr.Conteudo>td img {
    margin-left: 0.5ex;
  }

  .tabela_resultado tr td
  {
    padding-left: 6px;
    padding-right: 6px;
    padding-top: 0px;
    padding-bottom: 0px;
  }
</style>
<style type="text/css">
  @import "../plugins/jquery/datepick/telecontrol.datepick.css";
</style>

<?php include "javascript_pesquisas.php"; ?>

<script type='text/javascript' src="js/assist.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type='text/javascript'>
function removeLinha(linha){
  $('#linha_'+linha).remove();
}
$(function() {
  <?php if ($login_fabrica == 30) { ?>
          Shadowbox.init();
  <?php } ?>
  $("input[rel=data]").datepick({startDate:"01/01/2000"});
  $("input[rel=data], input[name=data_fabricacao]").maskedinput("99/99/9999");

  $("input[name=data_fabricacao]").change(function () {
    var input   = $(this);
    var data    = $(this).val();
    var os_item = $(this).attr("rel");

    if (data.length > 0) {
      $.ajax({
        url: "ajax_data_fabricacao.php",
        dataType: "JSON",
        data: { data: data, os_item: os_item },
        type: "POST",
        success: function (data) {
          if (data["erro"]) {
            alert(data["erro"]);
            $(input).val("");
          }
        }
      });
    }
  });

    $("img[name=btn_autorizar]").click(function(){
        var login_fabrica = <?=$login_fabrica?>;
        var obj = $(this).parent("td").parent("tr");

        var dados = this.id.split('|');
        var tipo_atendimento = obj.find("#atendimento_"+dados[0]).val();
		    var status_os = dados[2];



        if(confirm('Autorizar esta OS para liberação da intervenção?')) {

				  var dados = this.id.split('|');
				  if(login_fabrica == 94 && dados[2] == 176){
					  var tipo_atendimento = obj.find("#atendimento_"+dados[0]).val();
					  if (tipo_atendimento == "" || tipo_atendimento =='undefined') {
						  alert("Selecione o tipo de atendimento") ;
						  return false;
					  }
				  }else{
					  var tipo_atendimento = null;
				  }
			$('#load_'+dados[0]).show();
			$('.bt_autorizar').hide();
				  $.ajax({
					  url: '<?="$PHP_SELF"?>',
					  type:"GET",
					  data:{
						  os: dados[0],
						  autorizar:dados[1],
						  tipo_atendimento:tipo_atendimento
					  }
				  })
				  .done(function(data) {
            $('#load_'+dados[0]).hide();
            $('.bt_autorizar').show();
					  var msg = data.split('@@@');

					  if(msg[1] == "ok") {
						  if(obj.next().attr('class') == 'justificativa') {
							  obj.next().remove();
						  }
						  var tbody = obj.parents("tbody");
						  obj.remove();

						  if(tbody.html() == '') {
							  $("tr[name=nenhum_os_intervencao]").show();
							  $("p[name=quantidade_os_intervencao]").hide();
						  }
					  }
				  });
			  }
        //location.href = '<?="$PHP_SELF?os=' + dados[0] + '&autorizar=' + dados[1] + '"?>';
    });

    $("img[name=btn_cancelar]").click(function() {
      var obj = $(this).parent("td").parent("tr");

      if(confirm('Cancelar troca do produto? Esta OS será cancelada.')){
        $.ajax({
          url: '<?="$PHP_SELF?os='+this.id+'&cancelar=sim&ajax=cancelar_os" ?>',
          success: function(result) {
            if(result == "Os Cancelada com sucesso") {
              if(obj.next().attr('class') == 'justificativa') {
                obj.next().remove();
              }

              var tbody = obj.parents("tbody");

              obj.remove();

              if(tbody.html() == '') {
                $("tr[name=nenhum_os_intervencao]").show();
                $("p[name=quantidade_os_intervencao]").hide();
              }

            }
          }
        });
      }
    });
  <?
  if(in_array($login_fabrica,array(6,90,104,117,127,139)) or $novaTelaOs) { ?>

    $("button[name=confirmar_reparo]").click(function(){
      var obj = $(this).parent("td").parent("tr");

      if(confirm('Confirma reparo do produto da O.S. '+this.id+'?')){
        $.ajax({
          url: '<?="$PHP_SELF?os='+this.id+'&cancelar=sim&ajax=confirmar_reparo" ?>',
          success: function(result) {
            if(result == "Os liberada!") {
              if(obj.next().attr('class') == 'justificativa') {
                obj.next().remove();
              }

              var tbody = obj.parents("tbody");

              obj.remove();

              if(tbody.html() == '') {
                $("tr[name=nenhum_os_intervencao]").show();
                $("p[name=quantidade_os_intervencao]").hide();
              }


            }
          }
        });
      }
    });

  <? } ?>

  $("div[name=mostrar_pecas]").live('click', function(){
    $(this).parent('td').parent('tr').parent('tbody').find('tr[name=peca]').show();
    $("*[name=qtde_pecas]").hide();
    $(this).attr('name', 'esconder_pecas');
    $(this).html('Esconder peças');

  });

  $("div[name=esconder_pecas]").live('click', function(){
    $(this).parent('td').parent('tr').parent('tbody').find('tr[name=peca]').hide();
    $("*[name=qtde_pecas]").show();
    $(this).attr('name', 'mostrar_pecas');
    $(this).html('Mostrar peças');
  });
})

<?php if($login_fabrica == 117){ ##HD-hd_chamado=2768184 ?>
  function reparar_produto(os) {
    if(confirm('Reparar este produto na fábrica? O pedido de peça será cancelado.')){
      $.ajax({
        url: '<?="$PHP_SELF?os='+os+'&ajax=fazer_reparo" ?>',
        beforeSend: function () {
          $(".td_"+os).parent().find('img').hide();
          $('#load_'+os).show();
        },
        success: function(result) {
          var retorno = result.split("|");
          $('#load_'+os).hide();
          if(retorno[0] == "Reparook") {
            $(".td_"+retorno[1]).parent().find('img').remove();
            $(".td_"+retorno[1]).html('<button name="confirmar_reparo" id="'+os+'" onClick="javascript: confirm_reparo('+os+')">Confirmar reparo</button>');
          }else{
            alert("Erro ao Reparar");
          }
        }
      });
    }
  }

  function confirm_reparo(os){
    if(confirm('Confirma reparo do produto da O.S. '+os+'?')){
      $.ajax({
        url: '<?="$PHP_SELF?os='+os+'&ajax=confirmar_reparo" ?>',
        success: function(result) {
          if(result == "Os liberada!") {
            $("#linha_"+os).remove();
            $('#justif_'+os).remove();
          }
        }
      });
    }
  }

<?php } ## FIM HD ##?>

  var wOpParams = "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, top=18, left=0, width=300, height=500";
  function fnc_pesquisa_peca_lista(peca_referencia, peca_descricao, tipo) {
    var url = "";
    if (tipo == "referencia") {
      url = "peca_pesquisa_lista.php?peca=" + peca_referencia.value + "&tipo=" + tipo + "&exibe=/assist/admin/os_intervencao_fabio.php";
    }
    if (tipo == "descricao") {
      url = "peca_pesquisa_lista.php?descricao=" + peca_descricao.value + "&tipo=" + tipo + "&exibe=/assist/admin/os_intervencao_fabio.php";
    }
    if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
      janela = window.open(url, "janela", wOpParams.replace('500','400').replace('300','501'));
      janela.referencia = peca_referencia;
      janela.descricao  = peca_descricao;
      janela.preco    = document.frm_consulta.preco_null;
      janela.focus();
    }else{
      alert("Digite pelo menos 3 caracteres!");
    }
  }
  function fnc_pesquisa_peca_2 (campo, campo2, tipo) {
    if (tipo == "referencia" ) {
      var xcampo = campo;
    }
    if (tipo == "descricao" ) {
      var xcampo = campo2;
    }
    if (xcampo.value != "") {
      var url = "";
      url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
      janela = window.open(url, "janela", wOpParams.replace('500','400').replace('300','500'));
      janela.retorno = "<?php echo $PHP_SELF ?>";
      janela.referencia= campo;
      janela.descricao= campo2;
      janela.focus();
    }
  }
  function MostraEsconde(dados){
    if (document.getElementById){
      var style2 = document.getElementById(dados);
      if (style2==false)
        return;
      if (style2.style.display=="block"){
        style2.style.display = "none";
      }else{
        style2.style.display = "block";
      }
    }
  }
  function fnc_pesquisa_posto(campo, campo2, tipo) {
    if (tipo == "codigo" ) {
      var xcampo = campo;
    }
    if (tipo == "nome" ) {
      var xcampo = campo2;
    }
    if (xcampo.value != "") {
      var url = "";
      url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
      janela = window.open(url, "janela", wOpParams.replace('500','400').replace('300','600'));
      janela.codigo  = campo;
      janela.nome    = campo2;
      janela.focus();
    }
  }
  function fnc_pesquisa_produto2 (campo, campo2, tipo) {
    if (tipo == "referencia" ) {
      var xcampo = campo;
    }
    if (tipo == "descricao" ) {
      var xcampo = campo2;
    }
    if (xcampo.value != "") {
      var url = "";
      url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1" ;
      janela = window.open(url, "janela", wOpParams.replace('500','400').replace('300','500'));
      janela.referencia = campo;
      janela.descricao  = campo2;
      janela.produto    = document.frm_consulta.produto;
      janela.focus();
    }
  }
  function fnc_pesquisa_produto (campo, campo2, tipo) {
    if (tipo == "referencia" ) {
      var xcampo = campo;
    }
    if (tipo == "descricao" ) {
      var xcampo = campo2;
    }
    if (xcampo.value != "") {
      var url = "";
      url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
      janela = window.open(url, "janela", wOpParams.replace('500','400').replace('300','500'));
      janela.referencia = campo;
      janela.descricao  = campo2;
      janela.focus();
    }
  }
  function fnc_reparar(os) {
    var url = "<?php echo $PHP_SELF ?>?janela=sim&tipo=reparar&os="+os;
    janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=300, height=500, top=18, left=0");
    janela_aut.focus();
  }
  function fnc_cancelar(os) {
    var url = "<?php echo $PHP_SELF ?>?janela=sim&tipo=cancelar&os="+os;
    janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=300, height=500, top=18, left=0");
    janela_aut.focus();
  }
  function fnc_autorizar(os) {
    var url = "<? echo $PHP_SELF ?>?janela=sim&tipo=autorizar&os="+os;
    janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=300, height=500, top=18, left=0");
    janela_aut.focus();
  }
  function autorizar_os(os,formulario,sua_os){
    eval("var form = document."+formulario);

    // var frm;

    // frm = document.forms[formulario];

    // alert(frm.autorizar.value);

    form.autorizar.value=sua_os;

    <?php if($login_fabrica != 3){?>
      var just = prompt('Informe a justificativa da autorização (opcional)','');
      if ( just==null){
        return false;
      }
      form.justific.value=just;

      if (confirm('Deseja continuar?\n\nOS: '+sua_os)){
        form.submit();
      }
    <?php }else{?>

      if (confirm('Confirma a Autorização para a OS: '+sua_os+'?')){
        $.ajax({
          type: 'POST',
          url: $("#"+formulario).attr('action'),
          data: $("#"+formulario).serialize(),
          success: function(ret){
            var retorno = ret.split("|");
            if(retorno[0] == 'OK'){
              $("#linha_"+retorno[1]).css("background-color","#93FACC");
              $(".botoes_"+retorno[1]).hide();
            }else{
              alert(retorno[1]);
            }
          }
        });
      }
    <?php }?>

  }

function cancelar_os(formulario){
  $('#linha_'+formulario).css("background-color","#EF4B4B");
  $(".botoes_"+formulario).hide();

}

var filtro_status = -1  ;
function filtrar(status){

  if(status > 0){
    $("tr[name=linha_os]").hide();
    $("tr[rel=status_"+status+"]").show();

  }else{

    $("tr[name=linha_os]").show();
  }

  filtro_status = status;

}
</script>

<?if ($ordem_tabela_js) { ?>
<script type='text/javascript'>
$(function() {
  $("#tablesorter").tablesorter({
    dateFormat:'uk',
    headers: {
       5: {sorter: false},
       9: {sorter: false},
      10: {sorter: false}
      }
  });
});
</script>
<?}?>
<script type='text/javascript'>

function abreInteracao(linha, os, tipo, posto) {

  var div  = document.getElementById('interacao_'+linha);
  var os   = os;
  var tipo = tipo;

  if($("#interacao_"+linha).is(":visible")){
    $("#interacao_"+linha).hide();
  }else{
    $("#interacao_"+linha).show();
  }

  $.ajax({

    url: 'ajax_grava_interacao.php',
    type: 'POST',
    data: {linha:linha,os:os,tipo:tipo,posto:posto},
    success: function(campos) {

      campos_array   = campos.split("|");
      resposta       = campos_array[0];
      linha          = campos_array[1];

      var div        = document.getElementById('interacao_'+linha);
      div.innerHTML  = resposta;

      var comentario = document.getElementById('comentario_'+linha);
      comentario.focus();

    }

  });

}

function gravarInteracao(linha, os, tipo, posto, email) {
    var comentario = $.trim($("#comentario_"+os).val());

    if (comentario.length == 0) {
      alert("Insira uma mensagem para interagir");
    } else {
      $.ajax({
        url: "ajax_grava_interacao.php",
        type: "GET",
        data: {
          linha: os,
          os: os,
          tipo: tipo,
          comentario: comentario,
          posto:posto,
                email:email
        },
        beforeSend: function () {
          $("#interacao_"+os).hide();
          $("#loading_"+os).show();
        },
        complete: function(data){
          data = data.responseText;

          if (data == "erro") {
            alert("Ocorreu um erro ao gravar interação");
          } else {
            $("#loading_"+os).hide();
            $("#gravado_"+os).show();

            setTimeout(function () {
              $("#gravado_"+os).hide();
            }, 3000);

            $("#linha_"+os).css({
              "background-color": "#FFCC00"
            });
          }

          $("#comentario_"+os).val("");
          refreshInteracoes(os, os);
        }
      });
    }
  }

  function box_interacao(os) {
    Shadowbox.open({
      content: "relatorio_interacao_os.php?interagir=true&os="+os,
      player: "iframe",
      width: 850,
      height: 600,
      title: "Ordem de Serviço "+os
    });
  }

$(function() {
  $('#marcar_todos').click(function(){
    var marcar = $(this).is(':checked');

    if(marcar == true){
      $(".aprovar_os").attr("checked","checked");
    }else{
      $(".aprovar_os").removeAttr('checked');
    }

  });

    $('div[name=interagir]').click(function(){
        var valor = this.id.split("|");
        var tipo = "Mostrar";
        var os    = valor[0];
        var posto = valor[1];

        <?php if ($login_fabrica == 30) { ?>
                box_interacao(os);
        <?php } else { ?>
                abreInteracao(os,os,tipo,posto);
        <?php } ?>
    });

  $('.btn-reprova-os').click(function(){
    var os = $(this).data("os");
    if (confirm('Deseja reprovar esta Os?')) {
      if (os != "") {
          $.ajax({
                  url: '<?=$PHP_SELF?>?os='+os+'&ajax=reprovarOs',
                  success: function(result) {
                    data = JSON.parse(result);
                    console.log(data.msg)
                    if (data.erro == true) {
                      alert(data.msg);
                      return false;
                    } else {
                      setTimeout(function(){
                        $("#linha_"+os).remove();
                      },500);
                      alert(data.msg);
                    }
                  }
          });
      } else {
        alert("Os não informada");
        return false;
      }
    } else {
      return false;
    }

  });




  $('#status_acao').click(function(){

    var opcao = $("#status_acao").val();
    //alert(opcao);
    var qtd_os = $("#quantidade_os").val();
    var conteudo;
    if(opcao == 1) {
      conteudo = "<input type='button' value='Autorizar' id='reprovar_os_aprovar' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' style='cursor:pointer;font: 12px Arial;padding:3px' rel='<?php  echo $PHP_SELF;?>?os= OS_AUTORIZAR &quantidade= QUANTIDADE &os_autorizar=2<?php echo $str_filtro;?>'>";
    }
    if(opcao == 2) {

      conteudo ='<a href="#" id="reprovar_os" rel="<?php  echo $PHP_SELF;?>?janela=sim_reprovar&os= OS_REPROVAR &tipo=cancelar&quantidade= NUMERO_OS &TB_iframe=true" class="reprova_os"><input type="button" value="Reprovar" ALT="Cancelar Troca de Peça" style="cursor:pointer;font: 12px Arial;padding:3px;"></a>';

    }

    if(opcao == '') {
      conteudo = '';
    }

    $("#executar_acao").html(conteudo);

    $('#reprovar_os').click(function(){

      var os_array;
      var array_os;
      var qtd_os = $("#quantidade_os").val();
      var conteudo_os;

      for(i=0;i < qtd_os;i++) {
        var array_os = $('#aprovar_os'+i).is(':checked');
        //alert(array_os);
        if(array_os == true) {
          conteudo_os = '';
          var conteudo_os = $('#aprovar_os'+i).val();
          os_array = os_array+','+conteudo_os;
        }
      }

      if(os_array) {
        var os = os_array.replace("undefined,", "");
        var cont = os.length;

        if(cont > 0) {
          var cod_linha = $(this).attr('rel');
          var cod_linha = cod_linha.replace(" OS_REPROVAR ",os);
          var cod_linha = cod_linha.replace(" NUMERO_OS ",qtd_os);

          var url = cod_linha;

          tb_show ("", url);
        }
      }else{
        alert("Selecione uma OS");
      }
    });

    $('#reprovar_os_aprovar').click(function(){

      var os_array;
      var array_os;
      var qtd_os = $("#quantidade_os").val();
      var conteudo_os;

      for(i=0;i < qtd_os;i++) {
        var array_os = $('#aprovar_os'+i).is(':checked');
        //alert(array_os);
        if(array_os == true) {
          conteudo_os = '';
          var conteudo_os = $('#aprovar_os'+i).val();
          os_array = os_array+','+conteudo_os;
        }
      }

      if(os_array) {
        var os = os_array.replace("undefined,", "");
        var cont = os.length;
        if(cont > 0) {
          var cod_linha = $(this).attr('rel');
          var cod_linha = cod_linha.replace(" OS_AUTORIZAR ",os);
          var cod_linha = cod_linha.replace(" QUANTIDADE ",qtd_os);

          var url = cod_linha;

          document.location= url;
        }
      }
    });

  });

});

function mostraMotivo(os){
  var linha = $("#ln_motivo_"+os);

  if($(linha).is(":visible")){
    $(linha).hide();
  }else{
    $(linha).show();
  }
}

function cancelarOS(os){
  var motivo = $("#motivo_"+os).val();

  if(motivo == ""){
    alert("Informe o motivo");
  }else{
   if(confirm('Cancelar troca do produto? Esta OS será cancelada.')){
          $.ajax({
                  url: '<?="$PHP_SELF?os='+os+'&motivo='+motivo+'&cancelar=sim&ajax=cancelar_os" ?>',
                        success: function(result) {
                          if(result == "Os Cancelada com sucesso") {
          $("#ln_motivo_"+os+" > td").html(result);
          setTimeout(function(){
            $("#ln_motivo_"+os).remove();
            $("$linha_"+os).remove();
          },500);
                          }
      }
          });
        }
  }

}
</script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" />
<!--[if lt IE 9]>
<link rel="stylesheet" href="js/thickbox_ie.css" type="text/css" />
<![endif]-->
<?php
$sql = "SELECT qtde_dias_uteis_intervencao FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query($con,$sql);
$dias = pg_fetch_result($res,0,0);
if(empty($dias)) {
  switch($login_fabrica) {
    case  3: $dias = 1; break;
    case 19: $dias = 2; break;
  //  case 14: $dias = 30;// HD 204735 não haverá mais tempo para a OS sair automaticamente de INTERVENÇÃO... só saira quando a fabrica confirmar
    default: 5;
  }
}
// HD 674410 - Bestway (81) - HD907550 + Cobimex (114)
if(!in_array($login_fabrica, array(51, 81,90,114,115,116,117,120,201,121,122,123,125))){
  #HD 14331
  echo "<p class='texto_avulso'>";
  echo "<b>ATENÇÃO: </b>As OSs em intervenção serão desconsideradas da INTERVENÇÃO automaticamente pelo sistema se não forem analisadas no prazo de $dias dias! O objetivo desta rotina é que o fabricante ajude o posto autorizado, e se isto não acontecer a OS sai da intervenção.</p>";
  echo "<p style='text-align:left'><br></p>";//TELECONTROL<br><br>
}?>

<?php
if(strlen($msg_erro)>0){
  echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' style='margin: 0 auto;'>";
  echo "<tr >";
  echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' valign='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg_erro</td>";
  echo "</tr>";
  echo "</table><br>";
}

if(strlen($msg)>0){
  echo "<center><b class='sucesso'>$msg</b></center><br>";
}
?>
<center>
<form method='POST' name='frm_consulta' action="<?=$PHP_SELF?>">
  <input type="hidden" name="preco_null" value="">
  <input type='hidden' name='btnacao'>

  <table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario" style='margin: "margin: 10px auto;"'>
    <caption class="titulo_tabela">Parâmetros de Pesquisa</caption>
  <tr>
      <td colspan="6">&nbsp;</td>
  </tr>
    <tr>
    <td>&nbsp;</td>
    <? if (!$usa_filtro_linha)
    //echo "<td class='table_line' rowspan='2'>&nbsp;</td>\n";  ?>
    <td rowspan="2" class="table_line">O.S</td>
    <td class="table_line">Número da O.S</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
      <td class="table_line" style="text-align: center;">
         &nbsp;
      </td>
     <td class="table_line" align="left">
      <input type="text" name="num_os" size="10" maxlength="20" value="<? echo $num_os ?>" class="frm">
    </td>
  </tr>
  <tr><td colspan="6">&nbsp;</td></tr>
  <tr>
    <td>&nbsp;</td>
    <? if (!$usa_filtro_linha)
    //echo "<td class='table_line' rowspan='2'>&nbsp;</td>\n";  ?>
    <td rowspan="2" class="table_line">Posto</td>
    <td class="table_line">Código do Posto</td>
    <td class="table_line">Nome do Posto</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td class="table_line" style="text-align: center;">
      &nbsp;
    </td>

    <td class="table_line" align="left">
      <input type="text" name="posto_codigo" size="10" maxlength="20" value="<? echo $posto_codigo ?>" class="frm">
      <img border="0" src="../imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'codigo')">
    </td>
    <td class="table_line" style="text-align: left;">
      <input type="text" name="posto_nome" size="25" maxlength="50"  value="<?echo $posto_nome?>" class="frm">
      <img border="0" src="../imagens/lupa.png" style="cursor:pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pela razão social" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'nome')">
    </td>
    <td class="table_line" style="text-align: center;">
      &nbsp;
    </td>
  </tr>
  <? if(!in_array($login_fabrica, array(51,81,114)) && !isset($novaTelaOs)){ ?>
    <tr>
      <td class="table_line" style="text-align: center;color:gray" colspan='5'>
        * Busca por produto e peça desabilitado temporariamente
      </td>
    </tr>
    <tr><td colspan="5">&nbsp;</td></tr>
  <? }
  //  29/12/2009 MLG - HD 179837 - Adicionar filtro por linha(s)
  if ($usa_filtro_linha) {
    $sql_linhas ="SELECT linha,codigo_linha,nome FROM tbl_linha WHERE fabrica=$login_fabrica AND ativo IS TRUE";
    $res_linhas = pg_query($con, $sql_linhas);
    if (($num_linhas = pg_num_rows($res_linhas)) > 0) { ?>
      <tr>
        <td>&nbsp;</td>
        <td class="table_line" title="Selecione a(s) linha(s)" colspan='3'>
          <fieldset size="4" style='text-align:center;padding:10px;margin:10px 0;' id='linhas'>
            <legend>
              Selecione a(s) linha(s) (<?=$num_linhas?>)
            </legend>

            <? for ($i = 0; $i < $num_linhas; $i++) {
              list ($linha_id, $codigo_linha, $linha_desc) = pg_fetch_row($res_linhas, $i);
              if (isset($_POST['linhas'])) {
                $checked = iif(in_array($linha_id,$_POST['linhas'])," CHECKED");
              } else {    //  HD 195612 - Por padrão, deixar todas as linhas selecionadas.
                $checked = " CHECKED";
              }
              echo "<input type='checkbox' name='linhas[]' value='$linha_id' title='$linha_desc'$checked>\n".
               "<label class='table_line' title='$linha_des'>$codigo_linha</label>\n";
            }?>
          </fieldset>
        </td>
        <td>&nbsp;</td>
      </tr><?
    }
  }
  if ($login_fabrica == 3) {
    $linhas_ocultas = $_POST['linhas'];
    // print_r($linhas_ocultas);
    // $linhas_ocultas = implode(",", $linhas_ocultas);
    $linhas_ocultas = json_encode($linhas_ocultas);
     //echo $linhas_ocultas;
    ?>
    <input type='hidden' id="linhas_ocultas" name='linhas_ocultas' value='<?=$linhas_ocultas?>'>
    <?php
  }
  if ($fabricas_busca_estado) { ?>
    <tr>
      <td colspan="5" class="table_line">
        <hr color='#eeeeee'>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td colspan="3" class="table_line" style="text-align: center;">
        Busca por Estado
        <select name="posto_estado" id='posto_estado' size="1" class="frm">
          <option value="" selected></option>
          <option value="AC">AC</option>
          <option value="AL">AL</option>
          <option value="AM">AM</option>
          <option value="AP">AP</option>
          <option value="BA">BA</option>
          <option value="CE">CE</option>
          <option value="DF">DF</option>
          <option value="ES">ES</option>
          <option value="GO">GO</option>
          <option value="MA">MA</option>
          <option value="MG">MG</option>
          <option value="MS">MS</option>
          <option value="MT">MT</option>
          <option value="PA">PA</option>
          <option value="PB">PB</option>
          <option value="PE">PE</option>
          <option value="PI">PI</option>
          <option value="PR">PR</option>
          <option value="RJ">RJ</option>
          <option value="RN">RN</option>
          <option value="RO">RO</option>
          <option value="RR">RR</option>
          <option value="RS">RS</option>
          <option value="SC">SC</option>
          <option value="SE">SE</option>
          <option value="SP">SP</option>
          <option value="TO">TO</option>
        </select>
      </td>
      <td>&nbsp;</td>
  <?php } ?>
    <tr>
      <td colspan="5">&nbsp;</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td rowspan="2" class="table_line">Data Abertura OS</td>
      <td class="table_line">Data Inicial</td>
      <td class="table_line">Data Final</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td class="table_line" style="text-align: center;">
        &nbsp;
      </td>
      <td class="table_line" align="left">
        <input type="text" name="data_inicial" rel="data" value="<? echo $data_inicial ?>" style="width: 90px;" maxlength="10" class='frm' />
      </td>
      <td class="table_line" style="text-align: left;">
        <input type="text" name="data_final" rel="data" value="<? echo $data_final ?>" style="width: 90px;" maxlength="10" class='frm' />
      </td>
      <td class="table_line" style="text-align: center;">
        &nbsp;
      </td>
    </tr>
    <tr>
      <td colspan="5">&nbsp;</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td rowspan="2" class="table_line">Produto</td>
      <td class="table_line">Referência</td>
      <td class="table_line">Descrição</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td class="table_line" style="text-align: center;">
        &nbsp;
      </td>
      <td class="table_line" align="left">
        <input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="10" maxlength="20" class='frm'>
        <img src='../imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_descricao,'referencia')" />
      </td>
      <td class="table_line" style="text-align: left;">
        <input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="25" maxlength="50" class='frm'>
        <img src='../imagens/lupa.png' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_descricao,'descricao')" />
      </td>
      <td class="table_line" style="text-align: center;">
        &nbsp;
      </td>
    </tr>
    <tr>
      <td colspan="5">&nbsp;</td>
    </tr>
    <tr>
      <td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
      <td rowspan="2" class="table_line">Peça</td>
      <td class="table_line">Referência</td>
      <td class="table_line">Descrição</td>
      <td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
    </tr>
    <tr>
      <td class="table_line" style="text-align: center;">
        &nbsp;
      </td>
      <td class="table_line" align="left">
        <input class='frm' type="text" name="peca_referencia" value="<? echo $peca_referencia ?>"size="10" maxlength="20">
          <a href="javascript: fnc_pesquisa_peca_lista (document.frm_consulta.peca_referencia,document.frm_consulta.peca_descricao,'referencia')">
            <img SRC="../imagens/lupa.png" align="absmiddle">
          </a>
      </td>
      <td class="table_line" style="text-align: left;">
        <input class='frm' type="text" name="peca_descricao" value="<? echo $peca_descricao ?>" size="25" maxlength="50">
          <a href="javascript: fnc_pesquisa_peca_lista (document.frm_consulta.peca_referencia,document.frm_consulta.peca_descricao,'descricao')">
            <img SRC="../imagens/lupa.png" align="absmiddle" >
          </a>
      </td>
      <td class="table_line" style="text-align: center;">&nbsp;</td>
    </tr>

    <?php if(in_array($login_fabrica,array(151))) { ?>
    <tr>
      <td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
      <td rowspan="2" class="table_line">Estado</td>
      <td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
    </tr>
    <tr>
      <td class="table_line" style="text-align: center;">
        &nbsp;
      </td>
       <td class="table_line" style="text-align: center;">
          <select id="estado" name="estado" class="span12" >
            <option value="" ></option>

            <?php

            foreach ($array_estados() as $sigla => $estado_nome) {
              $selected = ($estado == $sigla) ? "selected" : "";
              echo "<option value='{$sigla}' {$selected} >{$estado_nome}</option>";
            }

            ?>
          </select>
          </td>
       <td colspan='100%' align='left '></td>
      <td class="table_line" style="text-align: center;">&nbsp;</td>
    </tr>
    <?php } ?>

    <?php if($login_fabrica == 30){ $aAtendentes = hdBuscarAtendentes(); //hd_chamado=2537875 ?>
      <TR>
        <td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
        <td rowspan="2" class="table_line">Inspetor</td>
      </TR>
      <tr>
        <td class="table_line" style="text-align: center;">
          &nbsp;
        </td>
        <td colspan='100%' align='left '>
          <select class='frm' name="admin_sap" id="admin_sap">
              <option value=""></option>
              <?php foreach($aAtendentes as $aAtendente): ?>
                  <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo empty($aAtendente['nome_completo']) ? $aAtendente['login'] : $aAtendente['nome_completo'] ; ?></option>
               <?php endforeach; ?>
           </select>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td class="table_line">Gerar Excel</td>
        <td>
              <input type='checkbox' name='gerar_excel' value='t'> &nbsp;Gerar Excel
        </td>
      </tr>
    <?php }
      if($login_fabrica == 11) {
        echo "<tr>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td class='table_line'>&nbsp;</td>
        <td>
              <input type='hidden' name='gerar_excel' value='t'> 
        </td>
      </tr>";
      }

     ?>

  <!--Chamado 2033471 -->
  <? if($login_fabrica == 117){ ?>
    <tr>
      <td colspan="5">&nbsp;</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td rowspan="2" class="table_line">Escolha</td>
      <td class="table_line">Linha</td>
      <td class="table_line">Macro - Família</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td class="table_line" style="text-align: center;">
        &nbsp;
      </td>
      <td class="table_line" style="text-align: left;">
      <?
        $sql = "SELECT distinct
                    tbl_macro_linha.descricao,
                    tbl_macro_linha.macro_linha
            FROM tbl_macro_linha
                JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
            WHERE tbl_macro_linha.ativo IS TRUE
                AND fabrica = {$login_fabrica}
                AND ativo = 't'
            ORDER BY tbl_macro_linha.descricao";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
          echo "<select name='macro_linha' id='macro_linha' class='frm'>\n";
          echo "<option value=''>ESCOLHA</option>\n";
          for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
            $aux_macro_linha   = trim(pg_fetch_result($res,$x,macro_linha));
            $aux_descricao = trim(pg_fetch_result($res,$x,descricao));
            echo "<option value='$aux_macro_linha'";
            if ($macro_linha == $aux_macro_linha){
              echo " SELECTED ";
              $mostraMsgLinha = "<br> da FAMÍLIA $aux_descricao";
            }
            echo ">$aux_descricao</option>\n";
          }
          echo "</select>\n&nbsp;";
        }
      ?>
      </td>      
      <td class="table_line" align="left">
        <input type="hidden" name="linha_aux" id="linha_aux" value="<?=$linha; ?>">
        <select name='linha' id='linha' class="frm" style="max-width: 170px;">
        <option value=''>ESCOLHA</option>
        </select>
      </td>
      <td class="table_line" style="text-align: center;">
        &nbsp;
      </td>
    </tr>
  <?
  }
  ?>
    <!-- -->

  <?php if(!$ordem_tabela_js){ ?>
  <tr>
    <td>&nbsp;</td>
    <td class="table_line" colspan='3' style='text-align:center;vertical-align:middle'>
      <fieldset style='text-align:center;padding:10px;margin:10px 0;' >
        <legend>Ordenar por:</legend>
          <input type="radio" name="ordem" id="ordem_da" value="data_abertura" <? if ($ordem=='data_abertura') echo 'checked'; ?> >
        <label for='ordem_da'>Data da Abertura&nbsp;&nbsp;&nbsp;</label>
          <input type="radio" name="ordem" id="ordem_nome" value="nome" <?php if ($ordem=='nome') echo 'checked'; ?> >
        <label for='ordem_nome'>Nome do Posto&nbsp;&nbsp;&nbsp;</label>
          <input type="radio" name="ordem" id="ordem_dp" value="data_pedido" <?php if ($ordem=='data_pedido') echo 'checked'; ?> >
        <label for='ordem_dp'>Data Pedido</label>
      </fieldset>
    </td>
    <td>&nbsp;</td>
  </tr>
  <? } ?>

  <?php if(in_array($login_fabrica, array(115,116))){ ?>
  <tr>
    <td>&nbsp;</td>
    <td colspan="3"> 
      <fieldset style="text-align: center; padding: 10px; margin: 10px 0;">
        <legend> Mostrar as OS: </legend> 
        <input type="radio" name="filtro_de_os" value="apenas_os_aprovadas" <?php echo ($_POST["filtro_de_os"] == "apenas_os_aprovadas") ? "checked" : ""; ?> > Aprovadas &nbsp; &nbsp;  
        <input type="radio" name="filtro_de_os" value="apenas_os_reprovadas" <?php echo ($_POST["filtro_de_os"] == "apenas_os_reprovadas") ? "checked" : ""; ?> > Reprovadas  
      </fieldset>
      
    </td>
  </tr>
  <?php } ?>

  <tr>
    <td colspan="5" class="table_line" style="text-align: center;">
      <br>
      <input type="button" value="Pesquisar" onclick="document.frm_consulta.btnacao.value='filtrar' ; document.frm_consulta.submit() " ALT="Filtrar extratos" border='0' style="cursor:pointer;">
    </td>
  </tr>
  <tr>
    <td colspan="5">&nbsp;</td>
  </tr>
  <tr>
  <td colspan="5" class="table_line" style="text-align: center;">
    <br>
    <input type="button" value="Listar  Todas as OS's em intervenção" onclick="document.frm_consulta.btnacao.value='listar_todos' ; document.frm_consulta.submit() " border='0' style="cursor:pointer;">
  </td>
  </tr>
  <tr>
    <td colspan="5">&nbsp;</td>
  </tr>
</table>
</form>
</center>
<div style="width: 700px; margin: 0 auto; display: block;">

<?php if ($btnacao=='filtrar' or $btnacao == "listar_todos"){

  $estado               = trim($_POST["estado"]);
  if(strlen($estado) > 0){
    if(in_array($login_fabrica, array(151)) && isset($array_estado[$estado])){
      $condicao_estado .= " AND tbl_posto.estado = '{$estado}' ";
    }
  }

  if($login_fabrica == 30){ //hd_chamado=2537875
    if(strlen($admin_sap) > 0){
      $admin_sap = (int) $_POST['admin_sap'];
      $cond_admin_sap = " AND tbl_posto_fabrica.admin_sap = $admin_sap";
    }
  }

  ##### LEGENDAS - INÍCIO #####
  echo "<div name='leg' style='position: relative; padding-left:10px; width: 300px; margin-left: 0px; top: 0px; display: block; text-align: left; float: left;'>";

  if($login_fabrica < 138 || in_array($login_fabrica, array(172))){

    echo "<table width='100%'>";

    if ($login_fabrica <> 104)
    {
      if (!in_array($login_fabrica, array(81,114)))
      {
        echo "<tr height='3'>
            <td width='55' style='border:1px solid #666666;background-color:#F1F4FA;'>&nbsp;</td>
            <td nowrap><b> Intervenção da Assistência Técnica da Fábrica</td>
            </tr>";
      }
      echo "<tr height='3'>
          <td width='55' style='border:1px solid #666666;background-color:#FFFF99'>&nbsp;</td>
          <td><b> Reparo na Fábrica</td>
          </tr>";
    }
    echo "<tr height='3'>
        <td width='55' style='border:1px solid #666666;background-color:#D7FFE1'>&nbsp;</td>
        <td><b>OS Reincidente</td>
        </tr>";
    echo "<tr height='3'>
        <td width='55' style='border:1px solid #666666;background-color:#91C8FF'>&nbsp;</td>
        <td nowrap><b>OS Aberta a mais de 25 dias</td>
        </tr>";
    if($login_fabrica == 3){
      echo "<tr height='3'>
        <td width='55' style='border:1px solid #666666;background-color:#A4A4A4'>&nbsp;</td>
        <td nowrap><b>OS com intervenção de display</td>
        </tr>
        <tr height='3'>
        <td width='55' style='border:1px solid #666666;background-color:#EF4B4B'>&nbsp;</td>
        <td nowrap><b>OS Cancelada</td>
        </tr>";

    }
    echo "</table>";

  }

  echo "</div>";

  $cond_status = ($login_fabrica == 3) ? '62,64,65,127,147,167,175,199,200,201' : '62,64,65,127,147,167,199,200,201';

  if ($login_fabrica == 138) {
    $cond_status = "62,64,65,127,147,199,200,201";
  }

  if ($login_fabrica == 147) {
    $cond_status = "20,62,64,65,127,147,199,200,201";
  }

  if ($login_fabrica == 35 or $login_fabrica == 52) {
    $cond_status = '62,64,65,127,19';
  }

  if($login_fabrica == 94){
    $cond_status = "62,64,65,127,147,167,176,199,200,201";
  }

  ##### LEGENDAS - INÍCIO - HD 416877 #####
      /*
       0 | Aberta Call-Center               | #D6D6D6
             1 | Aguardando Analise               | #FF8282
             2 | Aguardando Peças                 | #FAFF73
             3 | Aguardando Conserto              | #EF5CFF
             4 | Aguardando Retirada              | #9E8FFF
             9 | Finalizada                       | #8DFF70
      */


    $sql_status   = "SELECT status_checkpoint,descricao,cor FROm tbl_status_checkpoint WHERE status_checkpoint > 0 AND (fabricas[1] isnull or $login_fabrica = any(fabricas))";

    if($login_fabrica > 103 and !in_array($login_fabrica,array(114,131,141,144,172)) ){
        $sql_status .= " AND status_checkpoint NOT IN(5,6,7,9,10,11,12,13,14)";
    } else if(in_array($login_fabrica,array(81,114,50))) {
        $sql_status .= " AND (status_checkpoint NOT IN(5,6,7,8,9,10,11,12,13,14) and status_checkpoint < 14)";
    } else if(($login_fabrica < 103 && $login_fabrica != 101) || in_array($login_fabrica, array(172))) {
        $sql_status .= " AND status_checkpoint NOT IN(5,6,7,8,10,11,12,13,14)";
    } else if($login_fabrica == 131) {
        $sql_status .= " AND status_checkpoint NOT IN(5,6,7,8,9,10,11,12,14)";
    } else if(in_array($login_fabrica,array(141,144))) {
        if ($login_fabrica == 141) {
            $sql_status .= ' AND status_checkpoint IN(0,1,14,2,8,11,3,12,4) ';
        }

        if ($login_fabrica == 144) {
            $sql_status .= ' AND status_checkpoint IN(0,1,14,2,8,11,3,4) ';
        }
    } else if ($login_fabrica == 101){
        $sql_status .= ' AND status_checkpoint IN(1,2,3,4,9) ';
	}

    $res_status = pg_query($con,$sql_status);
    $total_status = pg_num_rows($res_status);

    if (!in_array($login_fabrica,$fabricas_interacao) OR $login_fabrica >= 134) {

        if(in_array($login_fabrica, array(172))){
          $float = "right";
          $width = "200px";
        }else{
          $float = ($login_fabrica >= 138) ? "left" : "right";
          $width = ($login_fabrica >= 138) ? "700px" : "200px";
        }

      ?>
      <div style='position: relative; width: <?php echo $width; ?>; margin-right: 0px; top: 0xp; display: block; text-align: center; float: <?php echo $float; ?>; table-layout: fixed;'>
        <br>
        <table border='0' cellspacing='5' cellpadding='1' style='text-align: left; width: <?php echo $width; ?>;'>
          <tr height='24' style='line-height:20px;font-size:12px'>
            <td>&nbsp;</td><td height='24'><h4>Status das OS</h4><br /></td>
          </tr>
        <?php


        for($i=0;$i<$total_status;$i++){

          $id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
          $cor_status = pg_fetch_result($res_status,$i,'cor');
          $descricao_status = pg_fetch_result($res_status,$i,'descricao');

          #Array utilizado posteriormente para definir as cores dos status
          $array_cor_status[$id_status] = $cor_status;

            ?>

            <tr height='18'>
              <td width='18' >
                <span class="status_checkpoint" style="background-color:<?php echo $cor_status;?>">&nbsp;</span>
              </td>
              <td align='left'>
                <font size='1'>
                  <b>
                    <a href="javascript:void(0)" onclick="filtrar(<?php echo $id_status;?>);">
                      <?php echo $descricao_status;?>
                    </a>
                  </b>
                </font>
              </td>
            </tr>

            <?php

        }

        ?>
          <tr height='18'>
            <td width='18' >
              <span class="status_checkpoint">&nbsp;</span>
            </td>
            <td align='left'>
              <font size='1'>
                <b>
                  <a href="javascript:void(0)" onclick="filtrar(-1);">
                    Listar Todos
                  </a>
                </b>
              </font>
            </td>
          </tr>

        </table>
      </div>
      <? } ?>
</div>
  <?
  //if (in_array($login_fabrica,$fabricas_interacao) OR $login_fabrica >= 134) { ?>
  <!-- <table align="center" style="width: 700px; margin: 0 auto;">
    <tr>
      <td>
        <div style="width:700px;margin: 0 auto; margin-top: 20px;">
          <div class="status_checkpoint" style="background-color:<?php echo '#FFCC00';?>;float:left"></div>
          <div style="float:left"><b>Admin Interagiu</b></div>
          <div style="clear:both"></div>
          <div class="status_checkpoint" style="background-color:<?php echo '#669900';?>;float:left"></div>
          <div style="float:left"><b>Posto Interagiu</b></div>
        </div>
      </td>
    </tr>
  </table> -->
  <?//}

  if(in_array($login_fabrica, array(115,116)) && $_POST["filtro_de_os"] == "apenas_os_reprovadas"){
    $cond_status = $cond_status_temp = " 13, 101 ";
  }else if(in_array($login_fabrica, array(115,116)) && $_POST["filtro_de_os"] == "apenas_os_aprovadas"){
    $cond_status = $cond_status_temp = " 64, 155 ";
  }else{
    $cond_status_temp = "20,62,65,127,147,167,175,199,176";
  }

  if($login_fabrica == 114){ //hd_chamado=2634503
    // INTERVENCAO OS
    $sql_drop = "DROP TABLE IF EXISTS tmp_aud_intervencao";
    $res_drop = pg_query($con, $sql_drop);
    $sql_aud = "SELECT interv_os.os
          INTO TEMP tmp_aud_intervencao
          FROM (
            SELECT
            ultima_status.os,
            (
              SELECT status_os
              FROM tbl_os_status
              WHERE tbl_os_status.os = ultima_status.os
              AND   tbl_os_status.fabrica_status= $login_fabrica
              AND   status_os IN (19,20,65,127,147,167,199,200,201)
              ORDER BY os_status DESC LIMIT 1
            ) AS ultimo_os_status

            FROM (
              SELECT DISTINCT os
              FROM tbl_os_status
              WHERE tbl_os_status.fabrica_status= $login_fabrica
              AND   status_os IN (19,20,65,127,147,167,199,200,201)
            ) ultima_status
          ) interv_os
          WHERE interv_os.ultimo_os_status IN (20);
      ";

    $res_aud = pg_query($con,$sql_aud);

    //62,64,65,127,147,167,199,200,201
    // FIM INTERVENÇÃO OS

    // PEÇA CRITICA
    $sql_drop = "DROP TABLE IF EXISTS tmp_aud_peca_critica";
    $res_drop = pg_query($con, $sql_drop);

    $sql_aud_peca = "SELECT interv_os.os
          INTO TEMP tmp_aud_peca_critica
          FROM (
            SELECT
            ultima_status.os,
            (
              SELECT status_os
              FROM tbl_os_status
              WHERE tbl_os_status.os = ultima_status.os
              AND   tbl_os_status.fabrica_status= $login_fabrica
              AND   status_os IN (62,64,65,127,147,167,199,200,201)
              ORDER BY os_status DESC LIMIT 1
            ) AS ultimo_os_status

            FROM (
              SELECT DISTINCT os
              FROM tbl_os_status
              WHERE tbl_os_status.fabrica_status= $login_fabrica
              AND   status_os IN (62,64,65,127,147,167,199,200,201)
            ) ultima_status
          ) interv_os
          WHERE interv_os.ultimo_os_status IN (62);
      ";
    $res_aud_peca = pg_query($con,$sql_aud_peca);
    // FIM PEÇA CRITICA

    $sql = "SELECT DISTINCT tbl_os_status.os INTO TEMP tmp_interv_$login_admin
            FROM tbl_os
            join tbl_os_status ON tbl_os_status.os = tbl_os.os and tbl_os_status.fabrica_status = $login_fabrica
            WHERE tbl_os.fabrica = $login_fabrica
            AND tbl_os.os IN (SELECT os FROM tmp_aud_intervencao UNION SELECT os FROM tmp_aud_peca_critica);

            CREATE INDEX tmp_interv_OS_$login_admin
                ON tmp_interv_$login_admin(os);";
  }else{
    $today = new DateTime('now');
    $dataFim = $today->format('Y-m-d H:i:s');
    $dataInicio = $today->sub(new DateInterval('P1Y'));
    $dataInicio = $dataInicio->format('Y-m-d 00:00:00');
    $sql =  "SELECT distinct tbl_os_status.os INTO temp tmp_interv_$login_admin
      FROM  tbl_os_status
	  join (select os, max(os_status) as os_status
			 from tbl_os_status
			 where fabrica_status = $login_fabrica
			 and status_os in ($cond_status)
			 AND tbl_os_status.data BETWEEN '$dataInicio' AND '$dataFim'
			 group by os
			) os on os.os = tbl_os_status.os
      join tbl_os_status ex on ex.os_status = os.os_status and ex.fabrica_status = $login_fabrica
      where tbl_os_status.status_os in ($cond_status)
	  and ex.status_os in ({$cond_status_temp})
	  and tbl_os_status.fabrica_status = $login_fabrica
      AND tbl_os_status.data BETWEEN '$dataInicio' AND '$dataFim';

        CREATE INDEX tmp_interv_OS_$login_admin
                  ON tmp_interv_$login_admin(os); /*
              SELECT os
                FROM tmp_interv_$login_admin */;";
  }

  $res_status = pg_query($con,$sql);
  $total=pg_num_rows($res_status);

  if(strlen($produto_referencia)>0){
    $sql_adicional_3 = " AND tbl_produto.referencia = '$produto_referencia' ";
  }else{
    $sql_adicional_3 = " AND 1=1 ";
  }

  if (strlen($peca_referencia)>0) {
    $sql_adicional_2 = " AND tbl_peca.referencia = '$peca_referencia' ";
  }else{
    $sql_adicional_2 = " AND 1=1 ";
  }

  if (strlen($aux_data_inicial) > 0 and strlen($aux_data_final) > 0)
  {
    $sql_adicional_data = " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
  }

    //HD 195242: Adicionando a consulta de OS na SQL. Acrescentei como uma subselect na cláusula FROM, pois para a Fricon é diferente
    if ($login_fabrica == 52) {
      $select_hd_chamado_os = "SELECT hd_chamado FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.os=tbl_os.os";
    }
    else {
      $select_hd_chamado_os = " SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE  tbl_hd_chamado_extra.os=tbl_os.os ORDER BY hd_chamado DESC LIMIT 1 ";
    }

    if(isset($novaTelaOs)){
      $joinProduto = "JOIN   tbl_os_produto ON tbl_os_produto.os = tbl_os.os
              JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto";
      $leftJoin = "";

      $campoProduto = "tbl_produto.referencia                      AS produto_referencia ,
              tbl_produto.descricao                       AS produto_descricao  ,
              tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
              tbl_produto.produto_critico               AS produto_critico  , ";
    }else{
      $joinProduto = "JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ";
      $leftJoin    = " LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os ";

      $campoProduto = "tbl_produto.referencia                      AS produto_referencia ,
              tbl_produto.descricao                       AS produto_descricao  ,
              tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
              tbl_produto.produto_critico               AS produto_critico  , ";
    }

    /*HD 15731 - Habilitado p/ Otimização*/
    // OS não excluÃ­da
    //echo $linha;
    //echo "<br>";
    //echo $familia;

    if(isset($_POST["gerar_excel"])){
        $gerar_excel = true;
        $data = date("d-m-Y-h-i");
        $arquivo_completo = "xls/os_intervencao_$login_fabrica"."_$data.csv";
        $excel = fopen ($arquivo_completo,"w+");
    }else{
        $gerar_excel = false;
    }

    $sql =  "SELECT DISTINCT ON(tbl_os.os) tbl_os.os,
          tbl_os.sua_os                                                     ,
          tbl_os.status_checkpoint                                          ,
          LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
          TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
          TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
          current_date - tbl_os.data_abertura as dias_aberto,
          tbl_os.data_abertura   AS abertura_os       ,
          tbl_os.serie                                                      ,
          tbl_os.consumidor_nome                                            ,
          tbl_os.admin                                                      ,
          tbl_posto_fabrica.codigo_posto                                    ,
          tbl_posto.nome                              AS posto_nome         ,
          tbl_posto_fabrica.contato_fone_comercial    AS posto_fone         ,
          $campoProduto
          tbl_os_retorno.nota_fiscal_envio,
          TO_CHAR(tbl_os_retorno.data_nf_envio,'DD/MM/YYYY')      AS data_nf_envio        ,
          tbl_os_retorno.numero_rastreamento_envio,
          TO_CHAR(tbl_os_retorno.envio_chegada,'DD/MM/YYYY HH24:mm')      AS envio_chegada      ,
          tbl_os_retorno.nota_fiscal_retorno,
          TO_CHAR(tbl_os_retorno.data_nf_retorno,'DD/MM/YYYY')      AS data_nf_retorno        ,
          tbl_os_retorno.numero_rastreamento_retorno,
          TO_CHAR(tbl_os_retorno.retorno_chegada,'DD/MM/YYYY HH24:mm')      AS retorno_chegada,
          tbl_os_retorno.admin_recebeu AS admin_recebeu,
          tbl_os_retorno.admin_enviou AS admin_enviou,
          (select TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') from tbl_os_status where os = tbl_os.os order by data asc limit 1) AS data_auditoria,
          (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (67,68,70, 81,175) ORDER BY data DESC LIMIT 1) AS reincindente,
          (SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY data DESC LIMIT 1) AS status_descricao,
          (SELECT status_os_troca FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY data DESC LIMIT 1) AS status_os_troca,
          (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY os_status DESC LIMIT 1) AS status_os,
          (SELECT data FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY data DESC LIMIT 1) AS status_pedido,
          /* HD 195242: Colocar coluna do Chamado para Fricon, Salton e novas */
          ($select_hd_chamado_os) AS hd_chamado
        FROM  tmp_interv_$login_admin X
          JOIN  tbl_os ON tbl_os.os = X.os
          $joinProduto
          JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
          JOIN tbl_posto_fabrica   ON tbl_posto_fabrica.posto   = tbl_posto.posto
          AND tbl_posto_fabrica.fabrica = $login_fabrica
          LEFT JOIN tbl_os_retorno ON tbl_os_retorno.os     = tbl_os.os ";
          //chamado
      if($login_fabrica != 117){
      $sql .= " $leftJoin
          LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
          LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica ";
      }else{

        $sql .= " LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
          LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
          LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica ";

        if($linha > 0){
          $sql .= " JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.linha = $linha";
        }else{
            if ($macro_linha > 0) {
                $sql_macro_linha = "SELECT linha FROM tbl_macro_linha_fabrica WHERE fabrica = {$login_fabrica} AND macro_linha = {$macro_linha}";
                $res_macro_linha = pg_query($con, $sql_macro_linha);

                $macro_linha_list = array();
                for ($count = 0; $count < pg_num_rows($res_macro_linha); $count++) {
                    $macro_linha_list[] = pg_fetch_result($res_macro_linha, $count, "linha");
                }

                $sql .= " JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.linha IN(".implode(',', $macro_linha_list).")";
            }
        }


        if($familia > 0){
          $sql .= " JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.familia = $familia";
        }
      }
      // $sql .= " LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
      //    LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
      //    LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica ";
          //fim chamado
    if (isset($linhas))
      $sql.= "  JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
        AND tbl_posto_linha.linha = tbl_produto.linha";
      $sql .= " WHERE tbl_os.fabrica = $login_fabrica
        AND tbl_os.excluida IS NOT TRUE";

      if($login_fabrica <> 114 and $login_fabrica <> 101){ //hd_chamado=2634503
        
        if(in_array($login_fabrica, array(115,116)) && $_POST["filtro_de_os"] == "apenas_os_reprovadas"){
          $sql .= " AND (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (13, 101) ORDER BY data DESC LIMIT 1) IN (13, 101) ";
        }else if(in_array($login_fabrica, array(115,116)) && $_POST["filtro_de_os"] == "apenas_os_aprovadas"){
          $sql .= " AND (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (64, 155) ORDER BY data DESC LIMIT 1) IN (64, 155) ";
        }else{
          $sql .= " AND (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (20,62,64,65,81,127,147,167,174,175,176,199) ORDER BY data DESC LIMIT 1) IN (20,62,65,127,147,167,175,176,199) ";
        }
        
      }

      if(!empty($num_os)) {
        $sql .= " AND tbl_os.sua_os = '$num_os' ";
      }

      if((in_array($login_fabrica, array(51, 81, 114)) or $login_fabrica > 85) AND $login_fabrica != 138){ #HD 269024
        $sql.= " AND tbl_os.finalizada IS NULL
             AND tbl_os.os NOT IN(SELECT tbl_os_troca.os FROM tbl_os_troca WHERE tbl_os_troca.os = tbl_os.os) ";
      }
      $sql .= " $sql_adicional_4
        $sql_adicional_3
        $sql_adicional_2
        $sql_adicional
        $cond_admin_sap
        $condicao_estado
        $sql_adicional_data
        $sql_ordem ";

      $res = pg_query($con,$sql);
      $total=pg_num_rows($res);

      $achou=0;

      // echo nl2br($sql)."<br /> <br />"; exit;

      if ($listar_todos == "true")
      {

        $sql =  "SELECT DISTINCT ON(tbl_os.os) tbl_os.os,
          tbl_os.sua_os                                                     ,
          tbl_os.status_checkpoint                                          ,
          LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
          TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
          TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
          current_date - tbl_os.data_abertura as dias_aberto,
          tbl_os.data_abertura   AS abertura_os       ,
          tbl_os.serie                                                      ,
          tbl_os.consumidor_nome                                            ,
          tbl_os.admin                                                      ,
          tbl_posto_fabrica.codigo_posto                                    ,
          tbl_posto.posto                              AS posto         ,
          tbl_posto.nome                              AS posto_nome         ,
          tbl_posto_fabrica.contato_fone_comercial    AS posto_fone         ,
          tbl_os_retorno.nota_fiscal_envio,
          $campoProduto
          TO_CHAR(tbl_os_retorno.data_nf_envio,'DD/MM/YYYY')      AS data_nf_envio        ,
          tbl_os_retorno.numero_rastreamento_envio,
          TO_CHAR(tbl_os_retorno.envio_chegada,'DD/MM/YYYY HH24:mm')      AS envio_chegada      ,
          tbl_os_retorno.nota_fiscal_retorno,
          TO_CHAR(tbl_os_retorno.data_nf_retorno,'DD/MM/YYYY')      AS data_nf_retorno        ,
          tbl_os_retorno.numero_rastreamento_retorno,
          TO_CHAR(tbl_os_retorno.retorno_chegada,'DD/MM/YYYY HH24:mm')      AS retorno_chegada,
          tbl_os_retorno.admin_recebeu AS admin_recebeu,
          tbl_os_retorno.admin_enviou AS admin_enviou,
          (select TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') from tbl_os_status where os = tbl_os.os order by data asc limit 1) AS data_auditoria,
          (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (67,68,70,81,175,199) ORDER BY data DESC LIMIT 1) AS reincindente,
          (SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY data DESC LIMIT 1) AS status_descricao,
          (SELECT status_os_troca FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY data DESC LIMIT 1) AS status_os_troca,
          (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY os_status DESC LIMIT 1) AS status_os,
          (SELECT data FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY data DESC LIMIT 1) AS status_pedido,
          /* HD 195242: Colocar coluna do Chamado para Fricon, Salton e novas */
          ($select_hd_chamado_os) AS hd_chamado
        FROM  tmp_interv_$login_admin X
          JOIN  tbl_os ON tbl_os.os = X.os
          $joinProduto
          JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
        JOIN tbl_posto_fabrica   ON tbl_posto_fabrica.posto   = tbl_posto.posto
                    AND tbl_posto_fabrica.fabrica = $login_fabrica
        LEFT JOIN tbl_os_retorno ON tbl_os_retorno.os     = tbl_os.os ";
      $sql .= " $leftJoin
          LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
          LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica ";

      if (isset($linhas))
        $sql.= "  JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
          AND tbl_posto_linha.linha = tbl_produto.linha";
        $sql .= " WHERE tbl_os.fabrica = $login_fabrica
          AND tbl_os.excluida IS NOT TRUE";
        if($login_fabrica <> 114 and $login_fabrica <> 101){
          $sql .=" AND (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (20,62,64,65,81,127,147,167,174,175,176,199) ORDER BY data DESC LIMIT 1) IN (20,62,65,127,147,167,175,176,199,200) ";
        }

        if(in_array($login_fabrica, array(51, 81, 114)) or $login_fabrica > 85){ #HD 269024
          $sql.= " AND tbl_os.finalizada IS NULL
               AND tbl_os.os NOT IN(SELECT tbl_os_troca.os FROM tbl_os_troca WHERE tbl_os_troca.os = tbl_os.os) ";
        }
        $sql .= "ORDER BY tbl_os.os, abertura DESC";
        if ($debug) pre_echo ($sql,"Consulta por linha");
        $res = pg_query($con,$sql);

        $total=pg_num_rows($res);
        $achou=0;
      }

      if (in_array($login_fabrica,$fabricas_interacao) || ($login_fabrica >= 134 & !in_array($login_fabrica, array(172)))) {
?>
        <table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px; padding-left:15px;' bgcolor='#FFFFFF'>

            <tr>
                <td bgcolor='#FFCC00' width='45' height="20" style="border-radius: 3px;">&nbsp;</td>
                <td align='left'>Fábrica interagiu</td>
            </tr>

            <tr>
                <td bgcolor='#669900' width='45' height="20" style="border-radius: 3px;">&nbsp;</td>
                <td align='left'>Posto interagiu</td>
            </tr>
        </table>
<?php
    }
      echo "<br>";
      echo "<table border='1' cellpadding='3' class='tabela_resultado' id='tablesorter'
        style='border-collapse: collapse; border-color: #485989; margin: 0 auto; width: 98%;'>";
      //INICIO DO XLS
      $data_xls = date('dmy');
      echo `rm /tmp/assist/relatorio-os-intervencao-$login_fabrica.xls`;
      //$fp = fopen ("/tmp/assist/relatorio-os-intervencao-$login_fabrica.html","w");
      //fputs ($fp,"<html>");
      //fputs ($fp,"<head>");
      //fputs ($fp,"<title>OS INTERVENCAO - $data_xls");
      //fputs ($fp,"</title>");
      //fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
      //fputs ($fp,"</head>");
      //fputs ($fp,"<body>");
      //fputs($fp,"<TABLE align='center' border='0' cellspacing='1' cellpadding='1'>\n");
      echo "<thead>";
      //fputs ($fp,"<thead>");
      echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
      //fputs($fp,"<tr>\n");
      echo "<th width='70'>OS</th>";
      //fputs($fp,"<th width='70'>OS</th>\n");
      //HD 195242: Colocar coluna do Chamado para Fricon, Salton e novas
      if ($login_fabrica == 52 || $login_fabrica >= 81) {
        echo "<th width='70'>Chamado</th>";
        //fputs($fp,"<th width='70'>Chamado</th>\n");
      }
      //alteração HD 8112 3/12/2007 Gustavo
      if($login_fabrica == 6 OR $login_fabrica == 94){
        echo "<th width='70'>Nº SÉRIE</th>";
      }
      echo "<th>AB</th>";
      if($login_fabrica == 30){
        echo "<th>Data Auditoria</th>";
      }
      //fputs($fp,"<th>AB</th>\n");
      echo "<th>POSTO</th>";
      //fputs($fp,"<th>POSTO</th>\n");
      //alteração HD 8112 3/12/2007 Gustavo
      if($login_fabrica <> 6){
        echo "<th>FONE POSTO</th>";
      }
      //echo "<th>CONSUMIDOR</th>"; retirado a pedido do Fabio Britania
      if(!in_array($login_fabrica, array(138,142))){
        echo "<th>PRODUTO</th>";
      }
      //fputs($fp,"<th>PRODUTO</th>\n");
      if($login_fabrica == 51){
        echo "<th>PEÇA CRÍTICA</th>";
      }else{
        echo "<th>PEÇA</th>";
        //fputs($fp,"<th>PEÇA</th>\n");
      }

            if(in_array($login_fabrica, array_merge(array(11,131,172), $fabricas_interacao)) || $login_fabrica >= 134){
?>
                <th>INTERAÇÃO</th>
<?
            }

      if ($login_fabrica == 3) {
        echo "<th>Serial LCD</th>";
        echo "<th>Anexos</th>";
      }

      if( in_array($login_fabrica, array(11,172)) ){
                echo "<th>ADMIN</th>";
            }else{
                echo "<th name='qtde_pecas'>QTDE<br>PEÇAS</th>";
            }
      if ($ordem_tabela_js) echo "<th title='Justificativa para a Intervenção'>Just.</th>";
      if($login_fabrica == 94){
?>
            <th>Tipo Atendimento</th>
<?
      }
      echo "<th colspan='7'>AÇÕES</th>";
      echo "</tr>\n";
      echo "</thead>\n";
      echo "<tbody name='oss'>";

      if($gerar_excel){
          if($login_fabrica == 11) {
            $thead  = "OS;".utf8_encode('Data Digitação')."; Posto; Fone Posto; Produto;". utf8_encode('Peças')."\r\n";  
            $escreve = fwrite($excel, $thead);
          } else {
          $thead  = "OS;AB;Data Auditoria; Posto; Fone Posto; Produto;". utf8_encode('Peças').";".utf8_encode('Qtde.Peças').";".utf8_encode('Data Digitação').";".utf8_encode('Total Peças')."\r\n";
          $escreve = fwrite($excel, $thead);
        }
      }

      $tbody = "";

      for ($i = 0 ; $i < $total ; $i++) {
        $os                          = trim(pg_fetch_result($res, $i, 'os'));
        $sua_os                      = trim(pg_fetch_result($res, $i, 'sua_os'));
        $status_checkpoint           = trim(pg_fetch_result($res, $i, 'status_checkpoint'));
        $digitacao                   = trim(pg_fetch_result($res, $i, 'digitacao'));
        $abertura                    = trim(pg_fetch_result($res, $i, 'abertura'));
        $data_auditoria              = trim(pg_fetch_result($res, $i, 'data_auditoria'));
        $serie                       = trim(pg_fetch_result($res, $i, 'serie'));
        $consumidor_nome             = trim(pg_fetch_result($res, $i, 'consumidor_nome'));
        $codigo_posto                = trim(pg_fetch_result($res, $i, 'codigo_posto'));
        $posto                  = trim(pg_fetch_result($res, $i, 'posto'));
        $posto_nome                  = trim(pg_fetch_result($res, $i, 'posto_nome'));

        if(!in_array($login_fabrica, array(138,142))){
          $produto_referencia          = trim(pg_fetch_result($res, $i, 'produto_referencia'));
          $produto_descricao           = trim(pg_fetch_result($res, $i, 'produto_descricao'));
          $produto_troca_obrigatoria   = trim(pg_fetch_result($res, $i, 'troca_obrigatoria'));
          $produto_critico             = trim(pg_fetch_result($res, $i, 'produto_critico'));
        }

        $status_os                   = trim(pg_fetch_result($res, $i, 'status_os'));
        $status_descricao            = trim(pg_fetch_result($res, $i, 'status_descricao'));
        $status_os_troca             = trim(pg_fetch_result($res, $i, 'status_os_troca'));
        $admin_recebeu               = trim(pg_fetch_result($res, $i, 'admin_recebeu'));
        $admin_enviou                = trim(pg_fetch_result($res, $i, 'admin_enviou'));
        $os_reincidente              = trim(pg_fetch_result($res, $i, 'reincindente'));
        $dias_abertura               = trim(pg_fetch_result($res, $i, 'dias_aberto'));
        $nota_fiscal_envio           = trim(pg_fetch_result($res, $i, 'nota_fiscal_envio'));
        $data_nf_envio               = trim(pg_fetch_result($res, $i, 'data_nf_envio'));
        $numero_rastreamento_envio   = trim(pg_fetch_result($res, $i, 'numero_rastreamento_envio'));
        $envio_chegada               = trim(pg_fetch_result($res, $i, 'envio_chegada'));
        $nota_fiscal_retorno         = trim(pg_fetch_result($res, $i, 'nota_fiscal_retorno'));
        $data_nf_retorno             = trim(pg_fetch_result($res, $i, 'data_nf_retorno'));
        $numero_rastreamento_retorno = trim(pg_fetch_result($res, $i, 'numero_rastreamento_retorno'));
        $retorno_chegada             = trim(pg_fetch_result($res, $i, 'retorno_chegada'));
        $posto_fone           = substr(trim(pg_fetch_result($res, $i, 'posto_fone')),0,17);

        if($gerar_excel){
          if($login_fabrica == 11) {
            $tbody .= "$os;$abertura;$codigo_posto-$posto_nome;$posto_fone;$produto_referencia-$produto_descricao;";
          } else {
            $tbody .= "$os;$abertura;$data_auditoria;$codigo_posto-$posto_nome;$posto_fone;$produto_referencia-$produto_descricao;";
          }
        }

        //HD 195242: Colocar coluna do Chamado para Fricon, Salton e novas
        if ($login_fabrica == 52 || $login_fabrica >= 81) {
          $hd_chamado          = trim(pg_fetch_result($res,$i,'hd_chamado'));
        }
        if ( in_array($login_fabrica, array(11,172)) ){
          $produto_troca_obrigatoria='f';
        }
        if($produto_critico == 't' and $login_fabrica == 35){
          continue;
        }
        $sql_status  = "SELECT TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_os_status WHERE tbl_os_status.os= $os AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY tbl_os_status.data DESC LIMIT 1";
        $res_status = pg_query($con,$sql_status);
        $data_pedido = trim(pg_fetch_result($res_status,0,0));
        //Para Gama Italy não pegar a data da intervenção como data do pedido, ela pode entrar em intervenção pelo Ditrib, ações do Ronaldo
        if($login_fabrica == 51){
          $sql_canc = "SELECT  TO_CHAR(tbl_os_item.digitacao_item,'DD/MM/YYYY') as data_pedido
              FROM tbl_os_produto
                JOIN tbl_os_item USING(os_produto)
                JOIN tbl_peca USING(peca)
              WHERE tbl_os_produto.os=$os;";
          $res_canc = pg_query($con, $sql_canc);
          if( pg_num_rows($res_canc) > 0){
            $data_pedido = trim(pg_fetch_result($res_canc,0,0));
          }
        }
        if ($status_os=="64" or empty($status_os)) {
          if($login_fabrica == 114){ //hd_chamado=2634503
            $sqlAud = "SELECT status_os
                FROM tbl_os_status
                WHERE   tbl_os_status.fabrica_status = $login_fabrica
                AND   status_os IN (20,19)
                AND tbl_os_status.os = $os
                ORDER BY os_status DESC LIMIT 1";
            $resAud = pg_query($con, $sqlAud);

            if(pg_num_rows($resAud) > 0){
              $statusAud = pg_fetch_result($resAud, 0, 'status_os');
              if($statusAud == 20){
                $auditoriaCob = true;
              }else{
                $auditoriaCob = false;
                continue;
              }
            }
          }elseif(!in_array($login_fabrica,[115,116])){
            continue;
		  }
		}
          //continue; //volta ao laço "for"
		$achou=1;
        if ($i % 2 == 0)
          $cor   = "#F1F4FA";
        else
          $cor   = "#F7F5F0";
        if ($dias_abertura>24){
          $cor = "#91C8FF";
        }
        if ($status_os == "65")
          $cor = "#FFFF99";
        if ($os_reincidente==67 || $os_reincidente==68 || $os_reincidente==70){
          $cor = "#D7FFE1";
        }
        if ($status_os == "64")
          $cor = "#D7FFE1";

        if($status_os == "175"){
          $cor = "#A4A4A4";
        }

        $sqlint = "SELECT os_interacao, admin from tbl_os_interacao WHERE os = $os AND interno IS NOT TRUE ORDER BY os_interacao DESC limit 1";
                $resint = pg_query($con, $sqlint);

        $cor_interacao = "";
                if (pg_num_rows($resint) > 0) {

                    $admin = pg_fetch_result($resint, 0, 'admin');
                    if (in_array($login_fabrica,$fabricas_interacao) OR $login_fabrica >= 134) {
                        if (strlen($admin) > 0) {
                            $cor_interacao = "#FFCC00";
                        } else {
                            $cor_interacao = "#669900";
                        }
                    }
                }


        $pecas = "";
        $peca  = "";
        $sql_peca = "SELECT  tbl_os_item.os_item,
                tbl_peca.troca_obrigatoria AS troca_obrigatoria,
                tbl_peca.retorna_conserto AS retorna_conserto,
                tbl_peca.referencia AS referencia,
                tbl_peca.descricao AS descricao,
                tbl_peca.peca AS peca,
                tbl_os_item.peca_serie AS serial_lcd,
		tbl_os_item.parametros_adicionais AS os_item_parametros_adicionais,
		to_char(tbl_os_item.digitacao_item,'DD/MM/YYYY') AS data_digitacao
              FROM tbl_os_produto
                JOIN tbl_os_item USING (os_produto)
                JOIN tbl_peca    USING (peca)
              WHERE tbl_os_produto.os=$os ";
        if ($login_fabrica == 6){
          $sql_peca .= " AND tbl_peca.retorna_conserto IS TRUE ";
        }
        if ($login_fabrica == 51){
          //HD 52047 - retirado, caso venha a dar problema retornar.
          //25/11/2008 - Ronaldo pediu para voltar como era antes..
          $sql_peca .= " AND tbl_peca.troca_obrigatoria IS TRUE ";
        }
        $res_peca = pg_query($con,$sql_peca);
        $resultado = pg_num_rows($res_peca);
        $quantas_pecas = $resultado;

        // HD 674410 - INICIO - MLG - Movi de lugar este trecho
        $justificativa = trim(str_replace("Reparo do produto deve ser feito pela fábrica","",$status_descricao));
        if($login_fabrica == 51){
          $justificativa = $justificativa;
        }else{
          $justificativa = trim(str_replace("Peça da O.S. com intervenção da fábrica.","",$justificativa));
        }
        // HD 674410 - FIM

        if ($resultado>0){
          $peca_troca_obrigatoria   = trim(pg_fetch_result($res_peca, 0, 'troca_obrigatoria'));
          $peca_intervencao_fabrica = trim(pg_fetch_result($res_peca, 0, 'retorna_conserto'));
          $peca                     = trim(pg_fetch_result($res_peca, 0, 'peca'));

          if ($login_fabrica == 3) {
            $serial_lcd_obg = false;
          }

          for($j=0;$j<$resultado;$j++){
            $peca_referencia = trim(pg_fetch_result($res_peca, $j, 'referencia'));
            $peca_descricao  = trim(pg_fetch_result($res_peca, $j, 'descricao'));
            $data_digitacao  = trim(pg_fetch_result($res_peca, $j, 'data_digitacao'));

            $pecas[$peca_referencia]->desc = $peca_descricao;
            $pecas[$peca_referencia]->id   = trim(pg_fetch_result($res_peca, $j, 'peca'));
            $pecas[$peca_referencia]->cont++;
            $pecas[$peca_referencia]->data_digitacao = $data_digitacao;

            if ($login_fabrica == 3 && $serial_lcd_obg == false) {
              $sqlSerialLCD = "SELECT peca
                        FROM tbl_peca
                        WHERE peca = {$pecas[$peca_referencia]->id}
                        AND fabrica = {$login_fabrica}
                        AND parametros_adicionais ILIKE '%\"serial_lcd\":\"t\"%'";
              $resSerialLCD = pg_query($con, $sqlSerialLCD);

              if (pg_num_rows($resSerialLCD) > 0) {
                $serial_lcd_obg = true;
              }
            }

            if ($login_fabrica == 3) {
              $pecas[$peca_referencia]->os_item      = pg_fetch_result($res_peca, $j, "os_item");

              $os_item_parametros_adicionais            = pg_fetch_result($res_peca, $j, "os_item_parametros_adicionais");
              $os_item_parametros_adicionais            = json_decode($os_item_parametros_adicionais, true);
              list($ydf, $mdf, $ddf)                    = explode("-", $os_item_parametros_adicionais["data_fabricacao"]);
              $pecas[$peca_referencia]->data_fabricacao = "$ddf/$mdf/$ydf";
              $pecas[$peca_referencia]->serial_lcd = pg_fetch_result($res_peca, $j, "serial_lcd");
            }
          }
        }

        if (strlen($admin_recebeu)>0){
          $query = "SELECT login FROM tbl_admin WHERE admin=$admin_recebeu";
          $res_query = pg_query($con,$query);
          $resultado = pg_num_rows($res_query);
          if ($resultado>0){
            $admin_recebeu = trim(pg_fetch_result($res_query, 0, 'login'));
          }
        }
        if (strlen($admin_enviou)>0){
          $query = "SELECT login FROM tbl_admin WHERE admin=$admin_enviou";
          $res_query = pg_query($con,$query);
          $resultado = pg_num_rows($res_query);
          if ($resultado>0){
            $admin_enviou = trim(pg_fetch_result($res_query, 0, 'login'));
          }
        }
        if (strlen($sua_os) == 0)
          $sua_os = $os;
        if($login_fabrica==3 or $login_fabrica ==14){
          // HD 21341
          echo "<form name='frm_$os' id='frm_$os' method='post' ACTION=\"$PHP_SELF?autorizar_os=$os\">";
        }

        if ($login_fabrica == 3) {
          unset($pendenciaFoto);

          $sqlPF = "SELECT
                tbl_os_item.os_item
              FROM tbl_os_item
              JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
              JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
              JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
              WHERE tbl_os.os = {$os}
              AND tbl_os_item.parametros_adicionais ILIKE '%\"upload\":\"t\"%'
              LIMIT 1";
          $resPF = pg_query($con, $sqlPF);

          if (pg_num_rows($resPF) > 0) {
            $pendenciaFoto = true;
          }
        }

        echo "<tr class='Conteudo' rel='status_$status_checkpoint' name='linha_os' style='height:20px' bgcolor='$cor_interacao' align='left' id='linha_$os' >";
        //fputs($fp,"<tr align='left'>\n");
        echo "<td nowrap>
        <input type='hidden' name='justific' value=''>
        <input type='hidden' name='autorizar' value=''>
        <input type='hidden' name='ident_os' value='$os'>";
        //HD 416877 - INICIO
        if(strlen($status_checkpoint)> 0 AND (!in_array($login_fabrica,$fabricas_interacao OR $login_fabrica >= 134))) {
          $cor_status_os = '<span class="status_checkpoint" style="background-color:'.$array_cor_status[$status_checkpoint].'">&nbsp;</span>';
        } else if(in_array($login_fabrica,$fabricas_interacao) OR $login_fabrica >= 134){
          $cor_status_os = '<span class="status_checkpoint" style="background-color:'.$cor.'">&nbsp;</span>';
        }else {
          $cor_status_os = '<span class="status_checkpoint_sem">&nbsp;</span>';
        }

        //16877 - FIM
        if($login_fabrica == 86) {
          echo "&nbsp;<input type='checkbox' name='aprovar_os$i' id='aprovar_os$i' value='$os' alt='$os' rel='$os' class='aprovar_os'>";
        }
        echo "
        $cor_status_os

        <a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
        //fputs($fp,"<td nowrap>$sua_os</td>\n");
        //HD 195242: Colocar coluna do Chamado para Fricon, Salton e novas
        if ($login_fabrica == 52 || $login_fabrica >= 81) {
          echo "<td nowrap><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>$hd_chamado</a></td>";
          //fputs($fp,"<td nowrap>$hd_chamado</td>\n");
        }
        //alteração HD 8112 3/12/2007 Gustavo
        if($login_fabrica == 6 OR $login_fabrica == 94){
          echo "<td nowrap >$serie</td>";
        }
        echo "<td nowrap >$abertura</td>";
        if($login_fabrica == 30){
          echo "<td nowrap >$data_auditoria</td>";
        }
        //fputs($fp,"<td nowrap >$abertura</td>\n");
        echo "<td title='$codigo_posto $posto_nome' nowrap>$codigo_posto - ".substr($posto_nome,0,30)."</td>";
        //fputs($fp,"<td nowrap>$codigo_posto ".$posto_nome."</td>\n");
        //alteração HD 8112 3/12/2007 Gustavo
        if($login_fabrica <> 6){
          echo "<td nowrap>$posto_fone</td>";
        }

        if(!in_array($login_fabrica, array(138,142))){
        $produto = $produto_referencia . " - " . $produto_descricao;
        echo "<td nowrap title='Referência: $produto_referencia \nDescrição: $produto_descricao'>".$produto."</td>";
        //fputs($fp,"<td nowrap>".$produto."</td>\n");
        }

        echo "<td nowrap title='$peca_id' name='peca' align='left' style='padding: 5px;'>";
        if(!empty($pecas)) {
          echo   "<table cellspacing='0' style='border: 1px solid black; width: 100%;'>
              <tr>
                <td colspan='".(($login_fabrica == 3) ? "4" : "3" )."' style='background-color: #596D9B;' >
                  <div name='mostrar_pecas' style='width: 100%; text-align: center; color: white; cursor: pointer;'>Mostrar peças</div>
                </td>
              </tr>
              <tr style='display: none; text-align: center' name='peca'>
                <td style='background-color: #CED7E7; border-right: 1px solid black;' class='peca'>
                  Nome
                </td>
                <td style='background-color: #CED7E7; border-right: 1px solid black;' class='peca'>
                  Qtde. Peças
                </td>";

                if ($login_fabrica == 3) {
                  echo "<td style='background-color: #CED7E7;' class='peca'>
                    Serial LCD
                  </td>
                  <td style='background-color: #CED7E7;' class='peca'>
                    Data Fabricação
                  </td>";
                }

		if($login_fabrica == 30){
			echo "<td style='background-color: #CED7E7; border-right: 1px solid black;' class='peca'>
				Data Digitação
			      </td>";
		}

              echo "</tr>";

              $qtd_excel_pecas = 0;              
          foreach ($pecas as $peca_id => $peca) {

            echo "  <tr style='display: none; background-color: #fff' name='peca'>
                  <td class='peca' style='border-right: 1px solid black;'><a href='peca_cadastro.php?peca=$peca->id' target='_blank'>$peca->desc</a></td>
                  <td class='peca' style='text-align: center; border-right: 1px solid black;'> $peca->cont</td>";
                  if ($login_fabrica == 3) {
                    echo "<td class='peca'>
                      $peca->serial_lcd
                    </td>
                    <td class='peca'>
                      <input type='text' name='data_fabricacao' value='$peca->data_fabricacao' rel='$peca->os_item' style='width: 90px;' />
                    </td>";
		  }
            if($gerar_excel){
                if($login_fabrica == 11) {
                  $tbody .= !$qtd_excel_pecas ? "$peca->desc\r\n " : ";;;;;$peca->desc\r\n";
                }elseif ($qtd_excel_pecas == 0){
                    $tbody .= "$peca->desc;$peca->cont;$peca->data_digitacao;$quantas_pecas\r\n ";
                }else{
                    $tbody .= " ; ; ; ; ; ;$peca->desc;$peca->cont;$peca->data_digitacao;;\r\n";
                }
            }
		  if($login_fabrica == 30){
			echo "<td class='peca' style='text-align: center; border-right: 1px solid black;'> $peca->data_digitacao</td>";
		  }

                echo "</tr>";
                $qtd_excel_pecas++;
          }
        }
        else {
          echo "<table><tr><td>-</td></tr>";
          echo "  <tr style='display: none' name='peca'>
                  <td class='peca' style='border-right: 1px solid black;'> - </td>
                  <td class='peca' style='text-align: center'> 0 </td>
                </tr>";
        }
        echo "</table></td>";

        if ($login_fabrica == 3) {
          echo "<td style='text-align: center;'>";
            if ($serial_lcd_obg == true) {
              echo "<img src='imagens/img_ok.gif' alt='Obrigatório' title='OS possui peças com Serial LCD obrigatório' />";
            } else {
              echo "<img src='imagens/inativo.png' alt='Não Obrigatório' title='OS não possui peças com Serial LCD obrigatório' />";
            }
          echo "</td>";

          echo "<td style='text-align: center;'>";
            if (isset($pendenciaFoto)) {
              echo "<span style='color: #63798D; font-weight: bold; text-decoration: none; cursor: pointer;' onclick='window.open(\"mostra_upload.php?os={$os}\", \"_blank\");' title='OS possui peças com Upload de fotos obrigatório'>Ver anexos</span>";
            }
          echo "</td>";
        }

      //var_dump($pecas); exit;
                if(in_array($login_fabrica, array_merge(array(11,131,172), $fabricas_interacao)) || $login_fabrica >= 134){
?>
                    <td nowrap title='interacao_<?=$os?>' name='peca' align='left' style='padding: 5px;'>
                        <table cellspacing='0' style='border: 1px solid black; width: 100%;'>
                            <tr>
                                <td style='background-color: #596D9B;' >
                                    <div name='interagir' id='<?=$os?>|<?=$posto?>' style='width: 100%; text-align: center; color: white; cursor: pointer;'>Interagir</div>
                                </td>
                            </tr>
                        </table>
                    </td>
<?
                }
                if( in_array($login_fabrica, array(11,172)) ){
                    $sqlint2 = "SELECT admin from tbl_os_interacao WHERE os = $os ORDER BY os_interacao DESC limit 1";
                    $resint2 = pg_query($con, $sqlint2);
                    $admin_ultimo = pg_fetch_result($resint2,0,admin);
                    if(strlen($admin_ultimo) > 0){
                        $sqlAdmin = "
                                    SELECT  nome_completo
                                    FROM    tbl_admin
                                    WHERE   admin = $admin_ultimo
                        ";
                        $resAdmin = pg_query($con,$sqlAdmin);
                        echo "<td>".pg_fetch_result($resAdmin,0,nome_completo)."</td>";
                    }else{
                        echo "<td>&nbsp;</td>";
                    }
                }else{
                    echo "<td nowrap title='Quantidade de peças: $quantas_pecas' align='center' name='qtde_pecas'>$quantas_pecas</td>";
                }
        //fputs($fp,"<td nowrap align='center'>$quantas_pecas</td>\n");

        if ($ordem_tabela_js) {
          if ($justificativa) {
            echo "<td class='os_motivo_interv'>
              <img  src='imagens/setinha_linha4.gif'
              title='$justificativa' />&nbsp;</td>";
          } else echo "<td>&nbsp;</td>";
        }
        if($login_fabrica == 94 && $status_os == 176){
            $sqlBusca = "
                SELECT  tbl_tipo_atendimento.tipo_atendimento,
                        tbl_tipo_atendimento.descricao
                FROM    tbl_tipo_atendimento
                WHERE   tbl_tipo_atendimento.fabrica = $login_fabrica
          ORDER BY      tbl_tipo_atendimento.codigo
            ";
            $resBusca = pg_query($con,$sqlBusca);
            $tipo = pg_fetch_all($resBusca);
?>
            <td id="tipo_atendimento">
                <select name="tipo_atendimento" id="atendimento_<?=$os?>">
                    <option value="">&nbsp;</option>
<?
            foreach($tipo as $valor){
?>
                    <option value="<?=$valor['tipo_atendimento']?>"><?=$valor['descricao']?></option>
<?
            }
?>
                </select>
            </td>
<?
        }else{
?>
            <td id="tipo_atendimento">&nbsp;</td>
<?
        }
        if (in_array($status_os, array(20,62,127,147,167,175,176,199)) OR $auditoriaCob == true){ //hd_chamado=2634503 adicionado auditoriaCob
          /*$colspan="";
          if ($produto_troca_obrigatoria=='t' || $peca_troca_obrigatoria=='t')
            $colspan="colspan='4'";
          if ($login_fabrica == 51) {
            $colspan="colspan='3'";
          }*/
          //if ( $produto_troca_obrigatoria=='t' || $peca_troca_obrigatoria=='t' || 1==1){
            echo "<td align='center' class='td_$os' $colspan style='font-size:9px' nowrap>";
                if ($login_fabrica == 147 && $status_os == 20) {
                    echo " <img class='btn-reprova-os' data-os='{$os}' src='imagens_admin/btn_reprovar.png' border='0' style='cursor:pointer;' > ";
                }
          if ($fabrica_libera_troca || ($login_fabrica==35 && $produto_troca_obrigatoria=='t')) {
              if($login_fabrica != 117 || $status_os_troca == "t"){
                echo "<img class='botoes_$os' src='imagens/btn_trocar.gif' ALT='Efetuar a troca do Produto' border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Deseja realizar a troca deste produto pela Fábrica? Esta OS será liberada'))  document.location='$PHP_SELF?os=$os&trocar=$sua_os$str_filtro';\">";
              }
          }
          //}
          // Comentei o Ãºltimo TD, porque 'Autorizar' também é uma ação..
          //echo "</td>\n";
          //echo "<td align='center' style='font-size:9px' nowrap>";
          if ( in_array($login_fabrica, array(11,172)) ){
            echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça' border='0' style='cursor:pointer;'
            onClick=\"javascript: fnc_autorizar($os);\">";
          }elseif($login_fabrica == 3 or $login_fabrica==14){
          echo "<img class='botoes_$os' src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' i border='0' style='cursor:pointer;' onClick=\"javascript:autorizar_os($os,'frm_$os',$sua_os);\" >";
          }else{
            if($login_fabrica ==51){
              $sql_canc = "SELECT motivo
                       FROM tbl_pedido_cancelado
                                          WHERE os = $os
                                            AND peca IN (
                         SELECT tbl_os_item.peca
                       FROM tbl_os_produto
                           JOIN tbl_os_item USING (os_produto)
                           JOIN tbl_peca    USING (peca)
                                          WHERE tbl_os_produto.os = $os);";
              $res_canc = pg_query($con, $sql_canc);
              if( pg_num_rows($res_canc) > 0){
                echo "Peça cancelada";
              }else{
                echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' i border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Autorizar pedido de peça? Esta OS será liberada e a solicitação de peça para esta OS será autorizada'))  document.location='$PHP_SELF?os=$os&autorizar=$sua_os$str_filtro';\">";

              }
            }else if ($login_fabrica == 52) {
              echo "<img src='imagens/btn_autorizar.gif' ALT='Liberar Os de Intervenção Por falta da dados na OS' i border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Autorizar liberação de OS? Esta OS será liberada e poderá entrar no proxÃ­mo extrato'))  document.location='$PHP_SELF?os=$os&autorizar=$sua_os$str_filtro';\">";
            }else{
              if ($login_fabrica <> 35 or $produto_troca_obrigatoria<>'t') {
                if ($login_fabrica==86){
                  echo "<input type='button' value='Autorizar' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' style='cursor:pointer;font: 12px Arial;padding:3px' onClick=\"javascript: if(confirm('Autorizar pedido de peça? Esta OS será liberada e a solicitação de peça para esta OS será autorizada'))  document.location='$PHP_SELF?os=$os&autorizar=$sua_os$str_filtro';\">";
                }else{
                  echo "<img id='load_$os' style='display: none; vertical-align: middle; height: 20px; width: 20px; margin-left: 10px;' src='imagens/loading_indicator_big.gif' />";
                  echo "<img src='imagens/btn_autorizar.gif' id='$os|$sua_os$str_filtro|$status_os' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' border='0' style='cursor:pointer;' name='btn_autorizar' class='bt_autorizar' >";
                }
              }
            }
          }
          //echo "</td>\n";
          if (($produto_troca_obrigatoria != 't' && $peca_troca_obrigatoria != 't' && $login_fabrica <> 35 && $login_fabrica <> 52) or $login_fabrica == 14 or $login_fabrica == 86 or $login_fabrica == 117 or $login_fabrica == 6 or $login_fabrica == 104){
            //echo "<td align='center' style='font-size:9px' nowrap>";
            if($status_os_troca != "t" && $login_fabrica != 145) {
              echo '<a href="'.$PHP_SELF.'?janela=sim&tipo=cancelar&os='.$os.'&TB_iframe=true" class="thickbox" >';
                if ($login_fabrica == 86){
                  echo "<input type='button' value='Reprovar' ALT='Cancelar Troca de Peça' style='cursor:pointer;font: 12px Arial;padding:3px'>";
                }else if($login_fabrica != 90 && $login_fabrica != 126){
                  if ($login_fabrica == 30) {
                    echo "<img class='botoes_$os' src='imagens_admin/btn_reprovar.png' border='0' style='cursor:pointer;'>";
                  } else {
                    echo "<img class='botoes_$os' src='imagens/btn_cancelar.gif' border='0' style='cursor:pointer;'>";
                  }
                }
              echo '</a>';
            }elseif($status_os_troca != "t" && $login_fabrica == 145){
              if ($status_os == 199) {
                echo '<a href="'.$PHP_SELF.'?janela=sim&tipo=cancelar_laudo_tecnico&os='.$os.'&TB_iframe=true" class="thickbox" >';
                    echo "<img class='botoes_$os' src='imagens/btn_cancelar.gif' border='0' style='cursor:pointer;'>";
                echo '</a>';
              } else {
                echo '<a href="'.$PHP_SELF.'?janela=sim&tipo=cancelar&os='.$os.'&TB_iframe=true" class="thickbox" >';
                    echo "<img class='botoes_$os' src='imagens/btn_cancelar.gif' border='0' style='cursor:pointer;'>";
                echo '</a>';
              }

            }
            //echo "</td>\n";
            if($login_fabrica == 131 or isset($novaTelaOs)) {
                if($login_fabrica == 147){
                  $select_produto = "SELECT produto from tbl_os where os = {$os} and fabrica = {$login_fabrica}";
                  $res_produto    = pg_query($con, $select_produto);
                  if ((pg_num_rows($res_produto) > 0 && !in_array(pg_fetch_result($res_produto, 0, "produto"), array(234103)))) {
                    echo "<img src='imagens/btn_trocar.gif' ALT='Efetuar a troca do Produto' border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Deseja realizar a troca deste produto pela Fábrica? Esta OS será liberada'))  document.location='$PHP_SELF?os=$os&trocar=$sua_os$str_filtro';\">";
                  }
                }else{
                  echo "<img src='imagens/btn_trocar.gif' ALT='Efetuar a troca do Produto' border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Deseja realizar a troca deste produto pela Fábrica? Esta OS será liberada'))  document.location='$PHP_SELF?os=$os&trocar=$sua_os$str_filtro';\">";
                }
            }elseif (!in_array($login_fabrica,array(25,43,94,115,116,122,123,125,126))){
              //echo "<td align='center' style='font-size:9px' nowrap>";
              if( in_array($login_fabrica, array(11,172)) ){
                echo "<img src='imagens/btn_reparar.gif' ALT='Reparar Produto' border='0' style='cursor:pointer;' onClick=\"javascript: fnc_reparar($os);\">";
              }else{
                if (!in_array($login_fabrica,array(3,30,35,86))) {
                  if($login_fabrica == 117){ //hd_chamado=2768184
                    echo "<img src='imagens/btn_reparar.gif' ALT='Reparar Produto' border='0' style='cursor:pointer;'  onClick=\"javascript: reparar_produto($os);\">";
                    echo "<img id='load_$os' style='display: none; vertical-align: middle; height: 20px; width: 20px; margin-left: 10px;' src='imagens/loading_indicator_big.gif' />";
                  }else{
                    echo "<img src='imagens/btn_reparar.gif' ALT='Reparar Produto' border='0' style='cursor:pointer;'  onClick=\"javascript: if(confirm('Reparar este produto na fábrica? O pedido de peça será cancelado.'))  document.location='$PHP_SELF?os=$os&reparar=$sua_os$str_filtro';\">";
                  }
                }
              }
            }
            if ($login_fabrica == 30) {
              echo "<img src='imagens/btn_alterar_amarelo.gif' ALT='Alterar Peça' border='0' style='cursor:pointer;'  onClick=\"javascript: if(confirm('Será direcionado para a tela de lançamento de peças.'))  window.open('os_item.php?os=$os');\">";
            }

            if(isset($novaTelaOs)){
              echo "<img src='imagens/btn_reparar.gif' ALT='Reparar Produto' border='0' style='cursor:pointer;'  onClick=\"javascript: if(confirm('Reparar este produto na fábrica? O pedido de peça será cancelado.'))  document.location='$PHP_SELF?os=$os&reparar=$sua_os$str_filtro';\">";
            }
            //echo "</td>\n";
          }

          if ($login_fabrica == 52) {
            //echo "<td align='center' style='font-size:9px' nowrap>";
            echo '<a href="'.$PHP_SELF.'?janela=sim&tipo=cancelar&os='.$os.'&TB_iframe=true" class="thickbox" >';
              echo "<img src='imagens/btn_cancelar.gif' ALT='Reprovar OS' border='0' style='cursor:pointer;'>";
            echo '</a>';
            //echo "</td>\n";
          }
          if ($login_fabrica == 35 and $quantas_pecas >= 4) {
            //echo "<td align='center' style='font-size:9px' nowrap>";
            echo '<a href="'.$PHP_SELF.'?janela=sim&tipo=cancelar&os='.$os.'&TB_iframe=true" class="thickbox" >';
              echo "<img src='imagens/btn_cancelar.gif' ALT='Cancelar Troca de Peça' border='0' style='cursor:pointer;'>";
            echo '</a>';
            echo "<a href='os_item.php?os=$os' target='blank'><img src='imagens/btn_alterar_cinza.gif' ALT='Alterar Peça'></a>";
            //echo "</td>\n";
          }else if (in_array($login_fabrica,array(35,90,126)) ) {

            if(in_array($login_fabrica,array(126))){
              echo "<img src='imagens/btn_cancelar.gif' name='btn_cancela' id='$os' onclick='mostraMotivo($os);' border='0' style='cursor:pointer;' >";
            }else{
              echo "<img src='imagens/btn_cancelar.gif' name='btn_cancelar' id='$os' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' i border='0' style='cursor:pointer;' >";
              echo "<a href='os_item.php?os=$os' target='blank'><img src='imagens/btn_alterar_cinza.gif' ALT='Alterar Peça'></a>";
            }
            //echo "</td>\n";
            if ($produto_critico == 't') {
            //echo "<td>";
            echo "<img src='imagens/btn_trocar.gif' ALT='Efetuar a troca do Produto' border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Deseja realizar a troca deste produto pela Fábrica? Esta OS será liberada'))  document.location='$PHP_SELF?os=$os&trocar=$sua_os$str_filtro';\">";
            //echo "</td>";
            }
          }
          echo "</td>";
        }

        if($login_fabrica == 114){ //hd_chamado=2634503
          if ($status_os=="64" && strlen($nota_fiscal_envio)==0){
            if($auditoriaCob == false){
              echo "<td align='center' nowrap>";
              echo "OS LIBERADA AAA";
              echo "</td>";
            }
          }
        }else{
          if ($status_os=="64" && strlen($nota_fiscal_envio)==0){
            echo "<td align='center' nowrap>";
            echo "OS LIBERADA AAA";
            echo "</td>";
            //fputs($fp,"<td align='center' nowrap>OS LIBERADA</td>\n");
          }
        }
        $mostrar="none";
        $btn_retirar_intervencao = "<br /><a href=\"javascript: if(confirm('Deseja retirar esta OS da intervenção?'))  document.location='$PHP_SELF?os=$os&retirar_intervencao=1';\" alt='Força retirada da OS da intervenção. Só utilize em casos exttremos.'>RETIRAR OS DA INTERVENÇÃO</a>";

        if ($status_os=="62" AND $login_fabrica == 51){ // HD 59408
          echo "<td align='center' style='font-size:9px' nowrap>";
          echo "<a href=\"javascript: if(confirm('Deseja retirar esta OS da intervenção já que posto conseguiu consertar o produto?'))  document.location='$PHP_SELF?os=$os&retirar_intervencao=1';\"><img src='imagens/btn_consertado.gif' ALT='Produto Consertado pelo Posto' border='0' style='cursor:pointer;' ></a>";
          echo "</td>\n";
        }

        if ($status_os=="65" OR ($status_os=="64" && strlen($nota_fiscal_envio)>0)){
          echo   "<td align='center' style='font-family:arial;font-size:9px' nowrap>";

          if ($nota_fiscal_envio==""){
            if(in_array($login_fabrica,array(3,11,14,172))){
              echo "POSTO NÃO ENVIOU O PRODUTO";
              $xls_acoes = "POSTO NÃO ENVIOU O PRODUTO";
              if (in_array($login_fabrica,array(11,172))){
                echo $btn_retirar_intervencao;
              }
            }
            else{

              if(in_array($login_fabrica,array(6,90,104,117,127,139)) or $novaTelaOs){ // Adicionada 127 HD-2264355
                echo "<button name='confirmar_reparo' id='$os'>Confirmar reparo</button>";
              } else {
                echo "O PRODUTO SERÁ REPARADO PELA FÁBRICA";
              }

              $xls_acoes = "O PRODUTO SERÁ REPARADO PELA FÁBRICA";
            }
          }
          elseif ($envio_chegada==""){
            $mostrar="block";
            echo "PRODUTO ENVIADO PELO POSTO";
            $xls_acoes = "PRODUTO ENVIADO PELO POSTO";
            if (in_array($login_fabrica,array(11,172)))
              echo $btn_retirar_intervencao;
          }
          elseif ($nota_fiscal_retorno==""){
            $mostrar="block";
            echo "RETORNO DO PRODUTO AO POSTO PENDENTE";
            $xls_acoes = "RETORNO DO PRODUTO AO POSTO PENDENTE";
            if (in_array($login_fabrica,array(11,172)))
              echo $btn_retirar_intervencao;
          }
          elseif($retorno_chegada==""){
            $mostrar="block";
            echo "CONFIRMAÇÃO DO POSTO PENDENTE";
            $xls_acoes = "CONFIRMAÇÃO DO POSTO PENDENTE";
            if (in_array($login_fabrica,array(11,172)))
              echo $btn_retirar_intervencao;
          }
          else{
            if ($status_os=="65")
              echo "<a href=\"javascript:MostraEsconde('dados_$i');\" >REPARO CONCLUÍDO</a>";
            else
              echo "<a href=\"javascript:MostraEsconde('dados_$i');\" >OS LIBERADA COM REPARO</a>";
          }
          echo "</td>";
          //fputs($fp,"<td>$xls_acoes</td>\n");

          // Se não for fabrica 14 não mostra o botão de CONFIRMAR

            if ($login_fabrica == 14 ){ //or ($nota_fiscal_envio && $data_nf_envio)){

              $acao=(strlen($envio_chegada)>0)?"confirmar_retorno":"confirmar_chegada";
              echo "</tr>";
              echo "<tr class='Conteudo' bgcolor='#FFFFCC' height='0px' align='right' style='height: 20px'>";
              echo "<form name='frm_confim' method='post' action='$PHP_SELF?$acao=$os' onSubmit='javascript:if (confirm(\"Deseja continuar?\")) return true; else return false;'>";
              echo "<td colspan='12'>";
              //fputs($fp,"<td>$nota_fiscal_envio</td>\n");
              //fputs($fp,"<td>$data_nf_envio</td>\n");
              echo "<div style='display:$mostrar' id='dados_$i'>";
              echo "<b style='color:#3366CC'>ENVIO Ã€ FÁBRICA:&nbsp;&nbsp;&nbsp;&nbsp;</b>";
              echo "<b style='padding-left:13px'>Nota Fiscal:</b> $nota_fiscal_envio ";
              echo "<b style='padding-left:13px'>Data Nota Fiscal:</b> $data_nf_envio ";
              if ($login_fabrica<>6){
                echo "<b style='padding-left:13px'>NÃºmero do Objeto/PAC:</b> <a href='http://www.websro.com.br/correios.php?P_COD_UNI=$numero_rastreamento_envio' target='_blank'>$numero_rastreamento_envio</a>";
                //fputs($fp,"<td>$numero_rastreamento_envio</td>\n");
              } else {
                //fputs($fp,"<td></td>\n");
              }

//              if ($envio_chegada){

                echo "<b style='padding-left:13px'>Chegada: </b>$envio_chegada ";
                //fputs($fp,"<td>$envio_chegada</td>\n");
                echo "<b style='padding-left:13px'>Admin: </b>$admin_recebeu ";
                //fputs($fp,"<td>$admin_recebeu</td>\n");
//              }else {
                //fputs($fp,"<td></td>\n");
                //fputs($fp,"<td></td>\n");
                echo "<b style='padding-left:13px'>Data Chegada: </b><input type='text' value='' id='data_envio_chegada' name='txt_data_envio_chegada' class='inpu' size='15' maxlength='10'><input name='confirmar_chegada' type='submit' value='Confirmar' class='butt'>";
//              }

              if ($envio_chegada){
                echo "<br><b style='color:#3366CC'>RETORNO AO POSTO: </b>";
                if ($nota_fiscal_retorno){
                  echo "<b style='padding-left:13px'>Nota Fiscal: </b>$nota_fiscal_retorno ";
                  //fputs($fp,"<td>$nota_fiscal_retorno</td>\n");
                  echo "<b style='padding-left:13px'>Data Nota Fiscal: </b> $data_nf_retorno ";
                  //fputs($fp,"<td>$data_nf_retorno </td>\n");
                  if ($login_fabrica<>6){
                    echo "<b style='padding-left:13px'>NÃºmero do Objeto/PAC: </b> <a href='http://www.websro.com.br/correios.php?P_COD_UNI=$numero_rastreamento_retorno"."BR' target='_blank'>$numero_rastreamento_retorno</a>";
                    //fputs($fp,"<td>$numero_rastreamento_retorno</td>\n");
                  } else {
                    //fputs($fp,"<td></td>\n");
                  }
                  echo "<b style='padding-left:13px'>Admin: </b>$admin_enviou";
                  //fputs($fp,"<td>$admin_enviou</td>\n");
                  if (strlen($retorno_chegada)==0)
                    $retorno_chegada = " NÃO CONSTA ";
                  echo "<b style='padding-left:13px'> Chegada: </b>$retorno_chegada ";
                  //fputs($fp,"<td>$retorno_chegada</td>\n");
                }else {
                  //fputs($fp,"<td></td>\n");
                  //fputs($fp,"<td></td>\n");
                  //fputs($fp,"<td></td>\n");
                  //fputs($fp,"<td></td>\n");
                  echo " <b style='padding-left:13px'>Nota Fiscal: </b><input type='text' value='' name='txt_nota_fiscal_retorno' class='inpu' size='10' maxlength='6'>";
                  echo "<b style='padding-left:13px'>Data da Nota Fiscal: </b><input type='text' value='' id='data_envio_retorno' name='txt_data_envio_retorno' class='inpu' size='15' maxlength='10'>";
                  if ($login_fabrica<>6){
                    echo "<b style='padding-left:13px'>NÃºmero do Objeto/PAC: </b><input type='text' value='' name='txt_rastreio_retorno' class='inpu' size='15' maxlength='13'>";
                    //fputs($fp,"<td></td>\n");
                  }
                  echo"<input type='submit' name='confirmar_retorno' value='Confirmar' class='butt'>";
                }
              } else {
                //fputs($fp,"<td></td>\n");
                //fputs($fp,"<td></td>\n");
                //fputs($fp,"<td></td>\n");
                //fputs($fp,"<td></td>\n");
                //fputs($fp,"<td></td>\n");
              }
              echo "</div>";
              echo "</td>";
              echo "</form>";
            } else {
              //fputs($fp,"<td></td>\n");
              //fputs($fp,"<td></td>\n");
              //fputs($fp,"<td></td>\n");
              //fputs($fp,"<td></td>\n");
              //fputs($fp,"<td></td>\n");
              //fputs($fp,"<td></td>\n");
              //fputs($fp,"<td></td>\n");
              //fputs($fp,"<td></td>\n");
              //fputs($fp,"<td></td>\n");
              //fputs($fp,"<td></td>\n");
            }


        }

        if($login_fabrica==3 or $login_fabrica==14){
          // HD 21341
          echo "</form>";
        }
        if (!$ordem_tabela_js and  strlen($justificativa)>0){
          echo "<tr class='justificativa' rel='status_$status_checkpoint' bgcolor='$cor' id='justif_{$os}'>";
          echo "<td class='os_motivo_interv' colspan='100%'>";
          echo "<img src='imagens/setinha_linha4.gif'>&nbsp;&nbsp;$justificativa&nbsp;";
          //fputs($fp,"<td>$justificativa</td>\n");
          echo "</td>";
          echo "</tr>";
        } else {
          //fputs($fp,"<td></td>\n");
        }
        echo "</tr>";
                  if(in_array($login_fabrica, array_merge(array(11,131,172), $fabricas_interacao)) || $login_fabrica >= 134){
?>
                    <tr bgcolor='#FFF'>
                        <td colspan='100%'>
                          <div id="loading_<?=$os?>" style="display: none;"><img src="imagens/ajax-loader.gif" /></div>
              <div id="gravado_<?=$os?>" style="font-size: 14px; background-color: #669900; color: #FFFFFF; font-weight: bold; display: none;">Interação gravada</div>
                          <div id='interacao_<?=$os?>' style="width: 700px; margin: 0 auto;"></div>
                        </td>
                    </tr>
<?
                }
    if(in_array($login_fabrica,array(126))){ // Campo para inserir motivo quando for cancelar a OS
?>
      <tr bgcolor='#FFF' id='ln_motivo_<?=$os?>' style='display:none;'>
                          <td colspan='100%'>
          Motivo: <input type='text' size='60' name='motivo_<?=$os?>'  id='motivo_<?=$os?>' />
          <input type='button' onclick='cancelarOS(<?=$os?>);' value='Cancelar OS' />
        </td>
                      </tr>
<?
    }


      }


      if($gerar_excel){
          fwrite($excel, $tbody);
          fclose($excel);
      }




      if($login_fabrica == 86){
        echo "<tr class='Conteudo' rel='' border='0' height='30' align='left' style='margin-top:10px;'>
            <td colspan='2'>
              &nbsp;<input type='checkbox' name='marcar_todos' id='marcar_todos' alt='' rel='' class='marcar_todos'>&nbsp;Selecionar Todos&nbsp;&nbsp;
            </td>
            <td colspan='2'>
              &nbsp;
              <select name='status_acao' id='status_acao' class='status_acao'>
                <option value=''>Selecione uma Opção</option>
                <option value='1'>Aprovar</option>
                <option value='2'>Reprovar</option>
              </select>
              &nbsp;
            </td>
            <td colspan='7'>";
            echo "<div name='executar_acao' id='executar_acao' class='executar_acao'></div>
            </td>
          </tr>";
      }


      echo "</tbody>";
      echo "<tr class='Conteudo' name='nenhum_os_intervencao' style='". ($achou ? "display: none" : '') . "' height='20' bgcolor='#FFFFCC' align='left'>
          <td colspan='100%' style='padding:10px'>NENHUMA OS COM INTERVENÇÃO DA FÁBRICA OU COM REPARO</td>
          </tr>";
      echo "</table>";
      //fputs($fp,"</tr></table>");
      if ($achou>0 AND $i>0){
        echo "<input type='hidden' name='quantidade_os' id='quantidade_os' value='$i'>";
        echo "<p name='quantidade_os_intervencao' style='text-align:center;'>$i OS(s) em Intervenção</p>";
        if($login_fabrica == 30 and $gerar_excel){
          echo "<br /> <a href='$arquivo_completo' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;Gerar Arquivo Excel</a> <br />";
        }
        if($login_fabrica == 11 and $gerar_excel){
          rename("/tmp/assist/relatorio-os-intervencao-$login_fabrica.html", dirname(__FILE__) . "/xls/relatorio-os-intervencao-$login_fabrica-$data_xls.xls");
          echo "<br /> <a href='$arquivo_completo' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;Gerar Arquivo Excel</a> <br />";
        }
        if ($login_fabrica == 14) {
          rename("/tmp/assist/relatorio-os-intervencao-$login_fabrica.html", dirname(__FILE__) . "/xls/relatorio-os-intervencao-$login_fabrica-$data_xls.xls");
          //echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-os-intervencao-$login_fabrica-$data_xls.xls /tmp/assist/relatorio-os-intervencao-$login_fabrica.html`;
          echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
          echo"<tr>";
          echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>RELATÓRIO DE OS INTERVENÇÃO<BR>Clique aqui para fazer o </font><a href='xls/relatorio-os-intervencao-$login_fabrica-$data_xls.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
          echo "</tr>";
          echo "</table>";
        }
      }
}?>
<br>
<br>
<br>
</center>
<?php include "rodape.php"?>
