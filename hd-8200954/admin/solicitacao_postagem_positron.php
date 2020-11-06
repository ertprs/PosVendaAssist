
  <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
  <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
  <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
  <link href="css/tooltips.css" type="text/css" rel="stylesheet" />
  <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
  <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

  <!--[if lt IE 10]>
        <link href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
      <link rel='stylesheet' type='text/css' href="../admin/bootstrap/css/ajuste_ie.css">
      <![endif]-->

  <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
  <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
  <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
  <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
  <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
  <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<html>
<body>

<?php
  include 'dbconfig.php';
  include 'includes/dbconnect-inc.php';
  include 'autentica_admin.php';

  /* VARIÁVEL PARA DEFINIR QUAL WEBSERVICE E DADOS DE CONTRATO DOS CORREIOS QUE SERÁ UTILIZADO 
    NA SOLICITAÇÃO DE POSTAGEM */
  // $ambiente = "devel";
  $ambiente = "producao";

  $extrato = $_GET['extrato'];
  $tipo       = 'A';

  $sql = "SELECT tbl_posto.posto as posto_id,
      tbl_posto.nome as rementente_nome,
      tbl_posto_fabrica.contato_endereco as remetente_endereco,
      tbl_posto_fabrica.contato_bairro as remetente_bairro,
      tbl_posto_fabrica.contato_numero as remetente_numero,
      tbl_posto_fabrica.contato_cidade as remetente_cidade,
      tbl_posto_fabrica.contato_estado as remetente_estado,
      tbl_posto_fabrica.contato_cep    as remetente_cep,
      tbl_posto_fabrica.contato_complemento as remetente_complemento,
      tbl_posto_fabrica.contato_fone_comercial as remetente_fone,
      tbl_extrato.protocolo,
      tbl_fabrica.nome as destinatario_nome,
      tbl_fabrica.endereco as destinatario_endereco,      
      tbl_fabrica.cep as destinatario_cep,
      tbl_fabrica.cidade as destinatario_cidade
  FROM tbl_extrato
  JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
  JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
  JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_extrato.fabrica and tbl_fabrica.fabrica = $login_fabrica
  JOIN tbl_extrato_extra on tbl_extrato_extra.extrato = tbl_extrato.extrato 
  where tbl_extrato.extrato = $extrato";
  $res = pg_query($sql);

  if(pg_num_rows($res)>0) {
    $posto       = pg_fetch_result($res, 0, 'posto_id');
    $array_dados = pg_fetch_array($res);
  }

  $sql_total = "SELECT sum(preco) as total from tbl_faturamento_item where extrato_devolucao = $extrato and nota_fiscal_origem is not null";
  $res_total = pg_query($sql_total);

  if(pg_num_rows($res_total)>0){
      $total                = pg_fetch_result($res_total, 0, 'total');
      $array_dados['total'] = $total;
  }

  $array_dados['destinatario_numero'] = "420-A";
  $array_dados['destinatario_estado'] = "SP";
  $array_dados['destinatario_bairro'] = "Fragata";

  //echo "<pre>";
    //print_r($array_dados);
  //echo "</pre>";

  $sqlAcesso = "SELECT  usuario,
        senha,
        codigo as codadministrativo,
        contrato,
        cartao,
        id_correio
      FROM tbl_fabrica_correios
      WHERE fabrica = $login_fabrica";
  $resAcesso = pg_query($sqlAcesso);

  if (pg_num_rows($resAcesso)>0) {

    $dados_acesso = pg_fetch_array($resAcesso);
    //echo "<pre>";
    // print_r($dados_acesso);
    //echo "</pre>";
    // die;
  } else {
    die('Fabrica não liberada para este recurso! consulte nosso suporte');
  }

  if($ambiente == "devel"){
    /* HOMOLOGAÇÃO */
    $dados_acesso =  array(
      'codAdministrativo' => "08082650",
      'codigo_servico'    => "41076",
      'cartao'            => "0057018901"
    );
  }else{
    /* PRODUÇÃO */
    $dados_acesso =  array(
      'username'          => $dados_acesso['usuario'],
      'password'          => $dados_acesso['senha'],
      'codAdministrativo' => $dados_acesso['codadministrativo'],
      'codigo_servico'    => "41068",
      'cartao'            => $dados_acesso['cartao'],
      'id_correio'        => $dados_acesso['id_correio']
    );
  }

  if(!empty($array_dados['protocolo'])) {
    /* ESTRUTURA DO ANTIGO WEBSERVICE DOS CORREIOS */
    // $array_request =  (object) array(
    //   'usuario'           =>$dados_acesso['usuario'],
    //   'senha'             => $dados_acesso['senha'],
    //   'codAdministrativo' => $dados_acesso['codadministrativo'],
    //   'tipoBusca'         =>'H',
    //   'numeroPedido'      =>$array_dados['protocolo'],
    //   'tipoSolicitacao'   => $tipo
    // );

    $array_request =  (object) array(
      'codAdministrativo' => $dados_acesso['codAdministrativo'],
      'tipoBusca'         => 'H',
      'numeroPedido'      => $array_dados['protocolo'],
      'tipoSolicitacao'   => $tipo
    );

    $function = 'acompanharPedido';

  } else {

    /* ESTRUTURA DO ANTIGO WEBSERVICE DOS CORREIOS */
    // $dados_acesso =  array(
    //   'usuario'           =>$dados_acesso['usuario'],
    //   'senha'             => $dados_acesso['senha'],
    //   'codAdministrativo' =>$dados_acesso['codadministrativo'],
    //   'contrato'          =>$dados_acesso['contrato'],
    //   'codigo_servico'    =>41068,
    //   'cartao'            =>$dados_acesso['cartao']
    // );

    $array_request = (object)  Array(
      'codAdministrativo' => (int) $dados_acesso['codAdministrativo'],
      'codigo_servico'    => $dados_acesso['codigo_servico'],
      'cartao'            => $dados_acesso['cartao'],
      'destinatario' => (object)  array(
        'nome'       => utf8_encode($array_dados['destinatario_nome']),
        'logradouro' => utf8_decode($array_dados['destinatario_endereco']),
        'numero'     => $array_dados['destinatario_numero'],
        'cidade'     => utf8_decode($array_dados['destinatario_cidade']),
        'uf'         => $array_dados['destinatario_estado'],
        'bairro'     => utf8_decode($array_dados['destinatario_bairro']),
        'cep'        => $array_dados['destinatario_cep']
      ),
      'coletas_solicitadas' =>  (object) array('tipo'=>$tipo,
      'descricao'           => '',
      'id_cliente'          => $extrato,
      'remetente'           => (object)   array(
        'nome'       => $array_dados['rementente_nome'],
        'logradouro' => utf8_encode($array_dados['remetente_endereco']),
        'numero'     => $array_dados['remetente_numero'],
        'bairro'     => utf8_encode($array_dados['remetente_bairro']),
        'cidade'     => utf8_encode($array_dados['remetente_cidade']),
        'uf'         => $array_dados['remetente_estado'],
        'cep'        => $array_dados['remetente_cep'],
      ),
      'valor_declarado' => $array_dados['total'],
      //    'ag' => '15',
      //    'ar'=>'1',
      'obj_col' => (object) array('item'=>1)
      )
    );

    if ($tipo == 'A') {
      $array_request->coletas_solicitadas->ag = 30 ;
    }
    //echo "<pre>";
    // print_r($array_request); die;
    $function = 'solicitarPostagemReversa';
  }

  function validaArray($item,$key) {

    global $array_erro;
    $array_valida = array('nome','logradouro','numero','bairro','cidade','uf','valor_declarado');

    if(in_array($key,$array_valida)) {
      if (empty($item)) {
        $array_erro[]= $key;
      }
    }
//      echo "key=>$key"; echo "<br>";
//      echo "item=>$item"; echo "<br>";
  }

  if ($function=='solicitarPostagemReversa1') {
    //echo "<pre>";
    $array_request =  $array_request;
    //print_r($array_request);
    $return = array_walk_recursive($array_request,'validaArray');
    //int_r($array_erro);

    if (count($array_erro)>0) {
      foreach($array_erro as $value) {
        echo "<div class='alert alert-danger'>preecher o campo $value</div>";
      }
      die;
    }
  }

  /* WEBSERVICES ANTIGOS */
  #$url = "http://webservicescolhomologacao.correios.com.br/ScolWeb/WebServiceScol?wsdl";
  // $url = "http://webservicescol.correios.com.br/ScolWeb/WebServiceScol?wsdl";

  if($ambiente == "devel"){
    /* HOMOLOGAÇÃO */
    $url_novo_webservice = "https://apphom.correios.com.br/logisticaReversaWS/logisticaReversaService/logisticaReversaWS?wsdl";

    $username = "empresacws"; 
    $password = "123456"; 

  }else{
    /* PRODUÇÃO */
    $url_novo_webservice = "https://cws.correios.com.br/logisticaReversaWS/logisticaReversaService/logisticaReversaWS?wsdl";

    if($login_fabrica == 11){
      $password = "aulik";

    }else if($login_fabrica == 151){
      $password = "monitora2016";

    }else if($login_fabrica == 153){
      $password = $dados_acesso["password"];

    }else if($login_fabrica == 156){
      $password = "tele6588";

    }else if($login_fabrica == 162){
      $password = "qbex2016";

    }else{
      $password = "tele6588";
    }

    $username = $dados_acesso["id_correio"]; 
  }
// echo $url_novo_webservice." - ".$username." - ".$password; 
// var_dump($array_request); exit;
  try {
    $client = new SoapClient($url_novo_webservice, array("trace" => 1, "exception" => 0,'authorization' => 'Basic', 'login'   => $username, 'password' => $password));
  } catch (Exception $e) {
    $response[] = array("resultado" => "false", "mensagem" => "ERRO AO CONECTAR SERVIDOR DOS CORREIOS");
    return $response;
  }

  $result = "";
  try {
    $result = $client->__soapCall($function, array($array_request));
  } catch (Exception $e) {
    $response[] = array("resultado" => "false", array($e));
  }
  // var_dump($result); exit;

  if ($function=='solicitarPostagemReversa') {
    if ($result->solicitarPostagemReversa->resultado_solicitacao->codigo_erro == '00') {

      $numero_postagem = $result->solicitarPostagemReversa->resultado_solicitacao->numero_coleta;
      $tipo            = $result->solicitarPostagemReversa->resultado_solicitacao->tipo ;
      $comentario      = $result->solicitarPostagemReversa->resultado_solicitacao;
      //print_r($comentario) ;exit;

      foreach($comentario as  $key => $value) {
        $string .= "<b>$key</b>: $value <br>";
      }

      $string_array        = explode("<br>", $string);
      $tipo                = explode(":", $string_array[0]);
      $atendimento         = explode(":", $string_array[1]);
      $numero_autorizacao  = explode(":", $string_array[2]);
      $numero_etiqueta     = explode(":", $string_array[3]);
      $status              = explode(":", $string_array[4]);
      $prazo_postagem      = explode(":", $string_array[5]);
      $data_solicitacao    = explode(":", $string_array[6]);
      $horario_solicitacao = explode(" ", $string_array[7]);

      $status_solicitacao = "<strong>Tipo Solicitação:</strong> ".trim($tipo[1])."<br />";
      $status_solicitacao .= "<strong>Atendimento:</strong> ".trim($atendimento[1])."<br />";
      $status_solicitacao .= "<strong>Numero Autorização:</strong> ".trim($numero_autorizacao[1])."<br />";
      if($login_fabrica <> 11){
        $status_solicitacao .= "<strong>Numero Etiqueta:</strong> ".trim($numero_etiqueta[1])."<br />";
      }
      $status_solicitacao .= "<strong>Status:</strong> ".trim($status[1])."<br />";
      $status_solicitacao .= "<strong>Prazo de Postagem:</strong> ".trim($prazo_postagem[1])."<br />";
      $status_solicitacao .= "<strong>Data da Solicitação:</strong> ".trim($data_solicitacao[1])."<br />";
      $status_solicitacao .= "<strong>Horário da Solicitação:</strong> ".trim($horario_solicitacao[1])."<br />";

      $sql_extrato_item = "UPDATE tbl_extrato_extra SET obs = 'Postagem feita pelo posto:<br />$status_solicitacao'
        WHERE extrato = $extrato ";
      $res = pg_query($sql_extrato_item);
      //echo nl2br($sql_extrato_item);

      $sql_extrato = "UPDATE tbl_extrato SET protocolo = ".$numero_postagem." WHERE extrato = $extrato ";
      $res_extrato = pg_query($sql_extrato);
      //echo nl2br($sql_extrato)
      ?>

      <div class='container' style="width: 800px;">
        <br/>
        <div class="alert alert-success" style="width: 748px;">
          <h4>Solicitação de Postagem solicitada com Sucesso</h4>
        </div>

        <table class='table table-striped table-bordered table-hover' style="width: 800px;">
          <thead>
            <tr class="titulo_tabela">
              <th colspan="8" >Status da solicitação</th>
            </tr>
            <tr class='titulo_coluna' >
              <th>Tipo</th>
              <th>Atendimento</th>
              <th>Numero Autorização</th>
              <?php if($login_fabrica <> 11){
              ?>
              <th>Numero Etiqueta</th>
              <?php
              } ?>
              <th>Status</th>
              <th>Prazo de Postagem</th>
              <th>Data Solicitação</th>
              <th>Horário Solicitação</th>
            </tr>
          </thead>
          <tbody>
            <tr>
          <?php

            $string_array        = explode("<br>", $string);
            $tipo                = explode(":", $string_array[0]);
            $atendimento         = explode(":", $string_array[1]);
            $numero_autorizacao  = explode(":", $string_array[2]);
            $numero_etiqueta     = explode(":", $string_array[3]);
            $status              = explode(":", $string_array[4]);
            $prazo_postagem      = explode(":", $string_array[5]);
            $data_solicitacao    = explode(":", $string_array[6]);
            $horario_solicitacao = explode(" ", $string_array[7]);

            echo "<td class='tac'>".trim($tipo[1])."</td>";
            echo "<td class='tac'>".trim($atendimento[1])."</td>";
            echo "<td class='tac'>".trim($numero_autorizacao[1])."</td>";
            if($login_fabrica <> 11){
              echo "<td class='tac'>".trim($numero_etiqueta[1])."</td>";
            }
            echo "<td class='tac'>".trim($status[1])."</td>";
            echo "<td class='tac'>".trim($prazo_postagem[1])."</td>";
            echo "<td class='tac'>".trim($data_solicitacao[1])."</td>";
            echo "<td class='tac'>".trim($horario_solicitacao[1])."</td>";

          ?>
            <tr>
          </tbody>
        </table>
      </div>
    <?

    } else {
      // print_r($result->solicitarPostagemReversa->resultado_solicitacao); exit;
      if(isset($result->solicitarPostagemReversa->resultado_solicitacao)){
        foreach ($result->solicitarPostagemReversa->resultado_solicitacao as $key => $value) {
          if($key == "descricao_erro"){
            echo"<div class='container' style='width: 800px;'>
              <div class='alert alert-danger'>";
                echo "<h4>".utf8_decode($value)."</h4>";
         echo"</div>
            </div>";
          }
        }

      }else{
        $value = utf8_decode($result->solicitarPostagemReversa->msg_erro);
        ?>
          <div class='container' style='width: 800px;'>
            <div class='alert alert-danger'>
                <h4><?=$value?></h4>
            </div>
          </div>
        <?php
      }
    }
  } else {

    if ($result->acompanharPedido->coleta) {
      $historico                = $result->acompanharPedido->coleta->historico;
      $historico->numero_pedido = $result->acompanharPedido->coleta->numero_pedido;
      $qtde_his                 = count($historico);

      if ($qtde_his>1) {
        foreach($historico as  $key) {
          foreach($key as $key2 => $value2) {
            $string .= "<b>$key2</b>: $value2 <br>";
          }
        }
      } else {
        foreach($historico as $key2 => $value2) {
          $string .= "<b>$key2</b>: $value2 <br>";
        }
      }
      ?>

      <div class='container' style="width: 800px;">
        <br/>
        <div class="alert alert-success" style="width: 748px;">
          <h4>Solicitação de Postagem já realizada</h4>
        </div>

        <table class='table table-striped table-bordered table-hover' style="width: 800px;">
          <thead>
            <tr class="titulo_tabela">
              <th colspan="6" >Status da solicitação</th>
            </tr>
            <tr class='titulo_coluna' >
              <th>Status</th>
              <th>Descrição do Status</th>
              <th>Número da Autorização </th>
              <th>Data da atualização</th>
              <th>Horário da atualização</th>
              <th>Obs:</th>
            </tr>
          </thead>
          <tbody>
            <tr>
          <?php
            // $string_array = explode("<br>", $string);

            // $status = explode(":", $string_array[0]);
            // $descrica_status = explode(":", $string_array[1]);
            // $data_atualizacao = explode(":", $string_array[2]);
            // $horario_atualizacao = explode(" ", $string_array[3]);
            // $observacao = explode(":", $string_array[4]);

            // echo "<td class='tac'>".trim($status[1])."</td>";
            // echo "<td class='tac'>".trim(utf8_decode($descrica_status[1]))."</td>";
            // echo "<td class='tac'>".trim($data_atualizacao[1])."</td>";
            // echo "<td class='tac'>".trim($horario_atualizacao[1])."</td>";
            // echo "<td class='tac'>".trim($observacao[1])."</td>";
            $qtde_his = count($historico);
            $objeto = $result->solicitarPostagemReversa->coleta->objeto;
            if ($qtde_his>1) {
              foreach($historico as $dados) {
                $dados->data_atualizacao = str_replace("-", "/", $dados->data_atualizacao);

                echo "<tr>";
                  echo "<td class='tac'>".$dados->status."</td>";
                  echo "<td class='tac'>".trim(utf8_decode($dados->descricao_status))."</td>";
                  echo "<td class='tac'>".trim(utf8_decode($dados->numero_pedido))."</td>";
                  echo "<td class='tac'>".trim($dados->data_atualizacao)."</td>";
                  echo "<td class='tac'>".trim($dados->hora_atualizacao)."</td>";
                  echo "<td class='tac'>".trim(utf8_decode($dados->observacao))."</td>";
                echo "</tr>";
              }
            } else {
              $historico->data_atualizacao = str_replace("-", "/", $historico->data_atualizacao);

              echo "<tr>";
                echo "<td class='tac'>".$historico->status."</td>";
                echo "<td class='tac'>".trim(utf8_decode($historico->descricao_status))."</td>";
                echo "<td class='tac'>".trim(utf8_decode($historico->numero_pedido))."</td>";
                echo "<td class='tac'>".trim($historico->data_atualizacao)."</td>";
                echo "<td class='tac'>".trim($historico->hora_atualizacao)."</td>";
                echo "<td class='tac'>".trim(utf8_decode($historico->observacao))."</td>";
              echo "</tr>";
            }
              if(!empty($objeto->ultimo_status)){
                echo "<tr>";
                  echo "<td class='tac'>".$objeto->ultimo_status."</td>";
                  echo "<td class='tac'>".trim(utf8_decode($objeto->descricao_status))."</td>";
                  echo "<td class='tac'>".trim(utf8_decode($historico->numero_pedido))."</td>";
                  echo "<td class='tac'>".trim($objeto->data_ultima_atualizacao)."</td>";
                  echo "<td class='tac'>".trim($objeto->hora_ultima_atualizacao)."</td>";
                  echo "<td class='tac'>".trim($objeto->numero_etiqueta)."</td>";
                echo "</tr>";
              }
          ?>
            <tr>
          </tbody>
        </table>
      </div>
    <?
    }else if($result->acompanharPedido->cod_erro != "00"){
      $value = utf8_decode($result->acompanharPedido->msg_erro);
      ?>
        <div class='container' style='width: 800px;'>
          <div class='alert alert-danger'>
            <h4><?=$value?></h4>
          </div>
        </div>
      <?php
    }
  }
// print_r($client->__last_request);
?>
</body>
</html>
