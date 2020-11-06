<?php
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    $admin_privilegios="call_center";
    include 'autentica_admin.php';
    include_once '../class/communicator.class.php';

    if ($_environment == 'development')
        ini_set('display_errors','on');

    /*
    VARIÁVEL PARA DEFINIR QUAL WEBSERVICE E DADOS DE CONTRATO DOS CORREIOS QUE SERÁ UTILIZADO
    NA SOLICITAÇÃO DE POSTAGEM
    */

    $host = $_SERVER['HTTP_HOST'];

    if(strstr($host, "devel.telecontrol") || strstr($host, "homologacao.telecontrol") || strstr($host, "localhost") || strstr($host, "127.0.0.1")){
        $ambiente = "devel";
    }else{
        $ambiente = "producao";
    }


?>
<!DOCTYPE html>
<html lang="en">
<html>
<head>
    <meta charset="iso-8859-1">
    <title>Solicitação de Postagem</title>
    <link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/extra.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="css/tc_css.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/ajuste.css" />
    <link rel="stylesheet" type="text/css" href="css/tooltips.css" />

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
</head>
<body>
<?php
    $hd_chamado     = filter_input(INPUT_GET,'hd_chamado');
    $solicitacao    = filter_input(INPUT_GET,'solicitacao');
    $tipo           = filter_input(INPUT_GET,'tipo');
    $sedex_pac      = filter_input(INPUT_GET,'sedex_pac');
    $obs            = filter_input(INPUT_GET,'obs');
    $valor_nf       = filter_input(INPUT_GET,'valor_nf');
    $id_admin       = filter_input(INPUT_GET,'login_admin');
    $volumes        = filter_input(INPUT_GET,'volumes');

    if(in_array($login_fabrica, array(162,164))){

        $xvalor_nf = str_replace('.','',$valor_nf);
        $xvalor_nf = str_replace(',','.',$xvalor_nf);

        $sqlatualiza = "UPDATE tbl_hd_chamado_extra SET valor_nf = '$xvalor_nf' WHERE hd_chamado = $hd_chamado";

        if(in_array($login_fabrica, array(162,164))){

            $checklist = $_GET['checklist']; //HD-3224475
            $numero_documento = $_GET['numero_documento']; //HD-3224475

        }

        $resatualiza = pg_query($sqlatualiza);

	}else{
		$checklist = "";
        $numero_documento = "";

	}


    if (in_array($login_fabrica,array(11,104,151,169,170,172))) {
        if ($_GET['sedex_pac'] == '40517') {
          $modo_envio = "SEDEX";
		  $servico_adicional = '019';
        }else{
          $modo_envio = "PAC";
		  $servico_adicional = '064';
        }
    }

    if (in_array($login_fabrica,array(81,114,122,123,125))){
        if ($_GET['sedex_pac'] == '40398') {
          $modo_envio = "SEDEX";
		  $servico_adicional = '019';
        }else{
          $modo_envio = "PAC";
		  $servico_adicional = '064';
        }
    }
    if ($login_fabrica == 1) {
        $modo_envio = "PAC";
        $servico_adicional = '064';
    }

    if ($login_fabrica == 80 or $login_fabrica == 174) {
        if ($_GET['sedex_pac'] == '04677') {
            $modo_envio = "PAC REVERSO";
            $servico_adicional = '064';
        } else {
            $modo_envio = "SEDEX";
            $servico_adicional = '019';
        } 
    }

    /*echo "<pre>";
    print_r($dados_acesso);
    exit;*/

    if ($login_fabrica != 1) {
        $sql = "SELECT tbl_hd_chamado_extra.array_campos_adicionais AS array_campos_adicionais,
                    tbl_posto.posto as posto_id,
                    tbl_fabrica.nome as fabrica,
                    tbl_produto.referencia || ' - ' ||tbl_produto.descricao AS produto,
                    tbl_posto.nome as destinatario_nome,
                    tbl_posto_fabrica.contato_endereco as destinatario_endereco,
                    tbl_posto_fabrica.contato_bairro as destinatario_bairro,
                    tbl_posto_fabrica.contato_numero as destinatario_numero,
                    tbl_posto_fabrica.contato_cidade as destinatario_cidade,
                    tbl_posto_fabrica.contato_estado as destinatario_estado,
                    tbl_posto_fabrica.contato_cep    as destinatario_cep,
                    tbl_posto.email as destinatario_email,
                    tbl_posto_fabrica.contato_complemento as destinatario_complemento,
                    tbl_posto_fabrica.contato_fone_comercial as destinatario_fone,
                    tbl_hd_chamado_extra.valor_nf,
                    case when tbl_hd_chamado_extra.numero_postagem notnull then tbl_hd_chamado_extra.numero_postagem else tbl_hd_chamado_postagem.numero_postagem end as numero_postagem,
                    tbl_hd_chamado_extra.nome as rementente_nome,
                    tbl_hd_chamado_extra.endereco as remetente_endereco,
                    tbl_hd_chamado_extra.numero as remetente_numero,
                    tbl_hd_chamado_extra.complemento as remetente_complemento,
                    tbl_hd_chamado_extra.bairro as remetente_bairro,
                    tbl_hd_chamado_extra.cep as remetente_cep,
                    tbl_cidade.nome as remetente_cidade,
		    tbl_cidade.estado as remetente_estado,
		    tbl_produto.referencia,
                    tbl_produto.descricao,
                    tbl_produto.marca,
                    tbl_hd_chamado_extra.cpf,
                    tbl_hd_chamado.hd_classificacao
                FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_extra    ON tbl_hd_chamado.hd_chamado   = tbl_hd_chamado_extra.hd_chamado
           LEFT JOIN tbl_produto             ON tbl_produto.produto         = tbl_hd_chamado_extra.produto
                JOIN tbl_posto               ON tbl_posto.posto             = tbl_hd_chamado_extra.posto
                JOIN tbl_posto_fabrica       ON tbl_posto.posto             = tbl_posto_fabrica.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
                JOIN tbl_fabrica             ON tbl_posto_fabrica.fabrica   = tbl_fabrica.fabrica
                JOIN tbl_cidade              ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
           LEFT JOIN tbl_hd_chamado_postagem ON tbl_hd_chamado_postagem.hd_chamado = tbl_hd_chamado.hd_chamado
                WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";
    } else {
         $sql = "
            SELECT  tbl_os.sua_os,
                    tbl_posto.posto                                 AS posto_id,
                    tbl_posto.nome                                  AS rementente_nome,
                    tbl_posto_fabrica.codigo_posto                  AS codigo_posto,
                    tbl_posto_fabrica.contato_email                 AS posto_email,
                    tbl_posto_fabrica.contato_endereco              AS remetente_endereco,
                    tbl_posto_fabrica.contato_bairro                AS remetente_bairro,
                    tbl_posto_fabrica.contato_numero                AS remetente_numero,
                    tbl_posto_fabrica.contato_cidade                AS remetente_cidade,
                    tbl_posto_fabrica.contato_estado                AS remetente_estado,
                    tbl_posto_fabrica.contato_cep                   AS remetente_cep,
                    tbl_posto_fabrica.contato_complemento           AS remetente_complemento,
                    tbl_posto_fabrica.contato_fone_comercial        AS remetente_fone,
                    SUM(tbl_os_item.custo_peca * qtde )             AS valor_nf,
                    tbl_os.autorizacao_domicilio,
                    fabrica_dest.nome                               AS destinatario_nome,
                    fabrica_dest.endereco                           AS destinatario_endereco,
                    fabrica_dest.numero                             AS destinatario_numero,
                    fabrica_dest.complemento                        AS destinatario_complemento,
                    fabrica_dest.bairro                             AS destinatario_bairro,
                    fabrica_dest.cep                                AS destinatario_cep,
                    fabrica_dest.cidade                             AS destinatario_cidade,
                    fabrica_dest.estado                             AS destinatario_estado,
                    tbl_fabrica.nome                                AS fabrica_nome
            FROM    tbl_os
            JOIN    tbl_os_produto  USING(os)
            JOIN    tbl_os_item     USING(os_produto)
            JOIN    tbl_posto               ON  tbl_posto.posto             = tbl_os.posto
            JOIN    tbl_posto_fabrica       ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
            JOIN    tbl_fabrica             ON  tbl_fabrica.fabrica         = tbl_os.fabrica
            JOIN    tbl_posto fabrica_dest  ON  fabrica_dest.posto          = tbl_fabrica.posto_fabrica
            LEFT JOIN tbl_hd_chamado_extra  ON  tbl_hd_chamado_extra.os     = tbl_os.os
            WHERE   tbl_os.os = $solicitacao
      GROUP BY      tbl_os.sua_os                              ,
                    tbl_posto.posto                            ,
                    tbl_posto.nome                             ,
                    tbl_posto_fabrica.codigo_posto             ,
                    tbl_posto_fabrica.contato_endereco         ,
                    tbl_posto_fabrica.contato_bairro           ,
                    tbl_posto_fabrica.contato_numero           ,
                    tbl_posto_fabrica.contato_cidade           ,
                    tbl_posto_fabrica.contato_estado           ,
                    tbl_posto_fabrica.contato_cep              ,
                    tbl_posto_fabrica.contato_complemento      ,
                    tbl_posto_fabrica.contato_fone_comercial   ,
                    tbl_os.autorizacao_domicilio               ,
                    fabrica_dest.nome                          ,
                    fabrica_dest.endereco                      ,
                    fabrica_dest.numero                        ,
                    fabrica_dest.complemento                   ,
                    fabrica_dest.bairro                        ,
                    fabrica_dest.cep                           ,
                    fabrica_dest.cidade                        ,
                    fabrica_dest.estado,
                    tbl_posto_fabrica.contato_email,
                    tbl_fabrica.nome
        ";
    }
    $res = pg_query($sql);

    if(pg_num_rows($res)>0) {
        $array_adicionais = json_decode(pg_fetch_result($res, 0, 'array_campos_adicionais'),true); //HD-3224475
        $posto       = pg_fetch_result($res, 0, 'posto_id');
	$produto     = pg_fetch_result($res, 0, 'produto');
	$ref_produto = pg_fetch_result($res, 0, 'referencia');
        $marca       = pg_fetch_result($res, 0, 'marca');
        $fabrica     = pg_fetch_result($res, 0, 'fabrica');
        $email_posto = pg_fetch_result($res, 0, 'posto_email');
        $fabrica_nome= pg_fetch_result($res, 0, 'fabrica_nome');
        $array_dados = pg_fetch_array($res);
        $classificacaoAtendimento = pg_fetch_result($res, 0, 'hd_classificacao');
    }

    $cond = "";
    if (in_array($login_fabrica, array(81,164))) {
        if (empty($marca) || $marca == 0 || $marca == null) {
            $cond = " AND marca is null";
        } else {
            $cond = " AND marca={$marca}";
        }
    }

    if($login_fabrica == 151){
        $pgResource = pg_query("SELECT descricao FROM tbl_hd_classificacao WHERE hd_classificacao = {$classificacaoAtendimento}");
        $descricaoClassificacao = pg_fetch_assoc($pgResource)['descricao'];

        switch($descricaoClassificacao){
            case 'E-COMMERCE': 
                $cond = " AND tbl_fabrica_correios.tipo_contrato = 'e-commerce'";
                break;
            case 'MKT PLACE':
                $cond = " AND tbl_fabrica_correios.tipo_contrato = 'mktplace'";
                break;
            default:
                $cond = " AND tbl_fabrica_correios.tipo_contrato is null";
        }
    }

    $sqlAcesso = "SELECT usuario,
	    senha,
	    codigo as codadministrativo,
	    contrato,
	    cartao,
	    id_correio, marca
	FROM tbl_fabrica_correios
    WHERE fabrica = $login_fabrica {$cond}";
    $resAcesso = pg_query($sqlAcesso);
    if (pg_num_rows($resAcesso)>0) {
       $dados_acesso = pg_fetch_array($resAcesso);
	} else {
		die('Fabrica não liberada para este recurso! consulte nosso suporte');
    }

    if($ambiente == "devel"){

        /* HOMOLOGAÇÃO */
         $dados_acesso =  array(
             'codAdministrativo' => "17000190",//"08082650",
             'codigo_servico'    => "41076",
             'cartao'            => "0067599079"//"0057018901"

         );

    } else {

        $sqlAcesso = "SELECT usuario,
                senha,
                codigo as codadministrativo,
                contrato,
                cartao,
                id_correio, marca
            FROM tbl_fabrica_correios
            WHERE fabrica = $login_fabrica {$cond}";
        $resAcesso = pg_query($sqlAcesso);
        if (pg_num_rows($resAcesso)>0) {

            $dados_acesso = pg_fetch_array($resAcesso);

            $sedex_pac = ($login_fabrica == 1) ? "PAC" : $sedex_pac;
            /* PRODUÇÃO */
            $dados_acesso =  array(
                'username'          => $dados_acesso['usuario'],
                'password'          => $dados_acesso['senha'],
                'codAdministrativo' => $dados_acesso['codadministrativo'],
                'codigo_servico'    => $sedex_pac,
                'cartao'            => $dados_acesso['cartao'],
                'id_correio'        => $dados_acesso['id_correio']
            );

        } else {

            die('Fabrica não liberada para este recurso! consulte nosso suporte');

        }
    }

    function validaArray($item,$key) {

        global $array_erro;
        $array_valida = array('nome','logradouro','numero','bairro','cidade','uf','valor_declarado');

        if(in_array($key,$array_valida)) {
            if (empty($item)) {
                $array_erro[]= $key;
            }
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

        if($login_fabrica == 11 or $login_fabrica == 172){
          $password = "aulik";

        }else if($login_fabrica == 104){
          $password = "ovd76635689";

        }else if($login_fabrica == 160 or $replica_einhell){
          $password = $dados_acesso["password"];

       } else if (in_array($login_fabrica,array(1,153))) {
          $password = $dados_acesso["password"];

        }else if($login_fabrica == 156){
          $password = "tele6588";

        }else if($login_fabrica == 162){
          $password = "qbex2016";

    }else if($login_fabrica == 164){
     $password = "gama2017nova";
    }elseif($telecontrol_distrib){
          $password = "tele6588";
        }

		if($login_fabrica == 11 or $login_fabrica == 172){
          $password = "aulik";
		  if($array_dados['valor_nf'] ==0 ) {
			  unset($array_dados['valor_nf']);
		  }
		}

        $username = $dados_acesso["id_correio"];
        if(empty($password)) $password = $dados_acesso['password'];
    }
    // echo $url_novo_webservice." - ".$username." - ".$password; exit();
    // var_dump($array_request); exit;

    /*      HD-3853415 31/10/2017
        Caso já exista um número de postagem para o atendimento e seja solicitado uma nova postagem, porém de um tipo diferente, primeiro é necessário cancelar o número de postagem anterior e então inserir um novo.
    */
    if(!empty($array_dados['numero_postagem'])) {
        $function_cancelar_ped = "";
        $postagem_cancelada = false;
        $numero_postagem_cancelada = "";
        $aux_numero_postagem = $array_dados['numero_postagem'];

        $aux_sql = "
            SELECT tipo_postagem
            FROM tbl_hd_chamado_postagem
            WHERE numero_postagem = '{$aux_numero_postagem}' AND fabrica = {$login_fabrica}
        ";

        $aux_res = pg_query($con, $aux_sql);
        $aux_tipo_postagem = pg_fetch_result($aux_res, 0, 'tipo_postagem');

        if($aux_tipo_postagem != $tipo) {

            $array_request =  (object) array(
                'codAdministrativo' => $dados_acesso['codAdministrativo'],
                'numeroPedido'      => $array_dados['numero_postagem'],
                'tipo'   => $tipo
            );

            $function_cancelar_ped = 'cancelarPedido';
            try {
                $client = new SoapClient($url_novo_webservice, array("trace" => 1, "exception" => 0,'authorization' => 'Basic', 'login'   => $username, 'password' => $password,'stream_context'=>stream_context_create(
						array('http'=>
						array(
							'protocol_version'=>'1.0',
							'header' => 'Connection: Close'
							)
						)
					)
                    ));
            } catch (Exception $e) {
                $response[] = array("resultado" => "false", "mensagem" => "ERRO AO CONECTAR SERVIDOR DOS CORREIOS");

                return $response;
            }

           $result = "";
            try {
                $result = $client->__soapCall($function_cancelar_ped, array($array_request));
            } catch (Exception $e) {
                $response[] = array("resultado" => "false", array($e));
            }

            if (strlen($result->cancelarPedidoResponse->cod_erro) == '00') {
                $postagem_cancelada = true;

                $aux_sql = "
                    UPDATE tbl_hd_chamado_postagem
                    SET motivo = 'Cancelado pela fábrica.'
                    WHERE fabrica = {$login_fabrica}
                    AND hd_chamado = {$hd_chamado}
                ";
                $aux_res = pg_query($con, $aux_sql);
                if(!pg_num_rows($aux_res) > 0) {
                    $msg_erro = pg_last_error($con);
                }
            } else {
                $msg_erro = utf8_decode($result->cancelarPedidoResponse->msg_erro);
            }
        } else {

            /* ESTRUTURA DO ANTIGO WEBSERVICE DOS CORREIOS */
            // $array_request =  (object) array(
            //     'usuario'           => $dados_acesso['usuario'],
            //     'senha'             => $dados_acesso['senha'],
            //     'codAdministrativo' => $dados_acesso['codadministrativo'],
            //     'tipoBusca'         => 'H',
            //     'numeroPedido'      => $array_dados['numero_postagem'],
            //     'tipoSolicitacao'   => $tipo
            // );

            $array_request =  (object) array(
                'codAdministrativo' => $dados_acesso['codAdministrativo'],
                'tipoBusca'         => 'H',
                'numeroPedido'      => $array_dados['numero_postagem'],
                'tipoSolicitacao'   => $tipo
            );

            $function = 'acompanharPedido';
        }
    }

    if (empty($array_dados['numero_postagem']) || (strlen($function_cancelar_ped) > 0 && $postagem_cancelada == true)) {
        if ($obs == 'undefined') {
            $id_cliente_obs = ($login_fabrica == 1) ? $array_dados['codigo_posto'].$array_dados['sua_os'] : $hd_chamado;
        }else{
            //$id_cliente_obs = $hd_chamado . ";" . $obs;
            //$obs = "";
            $id_cliente_obs = $hd_chamado;
        }

        /* ESTRUTURA DO ANTIGO WEBSERVICE DOS CORREIOS */
        // $dados_acesso =  array(
        //     'usuario'           => $dados_acesso['usuario'],
        //     'senha'             => $dados_acesso['senha'],
        //     'codAdministrativo' => $dados_acesso['codadministrativo'],
        //     'contrato'          => $dados_acesso['contrato'],
        //     'codigo_servico'    => $sedex_pac,
        //     'cartao'            => $dados_acesso['cartao']
        // );

        $array_request = (object)  Array(
            'codAdministrativo' => (int) $dados_acesso['codAdministrativo'],
            'codigo_servico'    => $dados_acesso['codigo_servico'],
            'cartao'            => $dados_acesso['cartao'],
            'destinatario'      => (object)  array(
                'nome'       => utf8_encode($array_dados['destinatario_nome']),
                'logradouro' => utf8_encode($array_dados['destinatario_endereco']),
                'numero'     => $array_dados['destinatario_numero'],
                'complemento'     => utf8_encode($array_dados['destinatario_complemento']),
                'cidade'     => utf8_encode($array_dados['destinatario_cidade']),
                'uf'         => $array_dados['destinatario_estado'],
                'bairro'     => utf8_encode($array_dados['destinatario_bairro']),
                'cep'        => $array_dados['destinatario_cep']
            ),
            'coletas_solicitadas' =>  (object) array(
                'tipo'       => $tipo,
                'descricao'  => utf8_encode($array_dados['descricao']),
                'id_cliente' => $id_cliente_obs,
                'cklist'  => $checklist, ////HD-3224475
                'documento' => $numero_documento, ////HD-3224475
                'remetente'  => (object)   array(
                    'nome'       => $array_dados['rementente_nome'],
                    'logradouro' => utf8_encode($array_dados['remetente_endereco']),
                    'numero'     => utf8_encode($array_dados['remetente_numero']),
                    'complemento'     => utf8_encode($array_dados['remetente_complemento']),
                    'bairro'     => utf8_encode($array_dados['remetente_bairro']),
                    'cidade'     => utf8_encode($array_dados['remetente_cidade']),
                    'uf'         => $array_dados['remetente_estado'],
                    'cep'        => $array_dados['remetente_cep'],
                ),
                'valor_declarado' => $array_dados['valor_nf'],
                'servico_adicional' => $servico_adicional,//correios obrigou a passar esse parametro
                // 'ag' => '15',
                // 'ar'=>'1',
                'obj_col' => (object) array(
                    'item' => 1,
                    'id'   => (in_array($login_fabrica, array(1))) ? $array_dados['codigo_posto'].$array_dados['sua_os'] : $hd_chamado.";".(($obs == "undefined") ? "" : utf8_encode($obs)),
                    'desc' => utf8_encode($array_dados['descricao'])
                )
            )
        );
        if (in_array($login_fabrica, array(162,164))) {
            $array_request->coletas_solicitadas->remetente->identificacao = $array_dados['cpf'];
        }

        $dias = 30;

	$dias = (in_array($login_fabrica,array(80))) ? 7 : $dias;

        if ($tipo == 'A') {
            $array_request->coletas_solicitadas->ag = $dias;
        }

        if(($login_fabrica == 151 AND $tipo == 'A')){
            $array_request->coletas_solicitadas->ag = 30;
        }

        if((in_array($login_fabrica, [164,186]) AND $tipo == 'A') or $login_fabrica == 162){
            $array_request->coletas_solicitadas->ag = 15;
        }

        $msg = "Postagem";

        if (in_array($login_fabrica,array(1,151)) AND $tipo == 'C') {
            $array_request->coletas_solicitadas->ag = date('d/m/Y', strtotime(date("Y-m-d"). ' + 15 days'));
            $msg = "Coleta";
	}

	if(!empty($volumes) AND 1==2){
		$array_request->coletas_solicitadas->produto->codigo = $ref_produto;
		$array_request->coletas_solicitadas->produto->qtd = $volumes;
		$array_request->coletas_solicitadas->produto->tipo = 0;

	}

        $function = 'solicitarPostagemReversa';
    }

    if ($function=='SolicitarPostagemReversa1') {
        //echo "<pre>";
        $array_request =  $array_request;

        $return = array_walk_recursive($array_request,'validaArray');

        //int_r($array_erro);

        if (count($array_erro)>0) {

            foreach($array_erro as $value) {
                echo "<div class='alert alert-danger'>preecher o campo $value</div>";
            }

            die;
        }

    }

    try {

        try {
            $client = new SoapClient($url_novo_webservice, array("trace" => 1, "exception" => 0,'authorization' => 'Basic', 'login'   => $username, 'password' => $password,'stream_context'=>stream_context_create(
						array('http'=>
						array(
							'protocol_version'=>'1.0',
							'header' => 'Connection: Close'
							)
						)
                    )));
        } catch (Exception $e) {
            $response[] = array("resultado" => "false", "mensagem" => "ERRO AO CONECTAR SERVIDOR DOS CORREIOS");
            throw new \Exception($response);
        }

	    $result = "";
        try {
            $result = $client->__soapCall($function, array($array_request));
        } catch (Exception $e) {
		print_r($e->getMessage());
            $response[] = array("resultado" => "false", array($e));
        }


        if ($function=='solicitarPostagemReversa') {

            if ($result->solicitarPostagemReversa->resultado_solicitacao->codigo_erro == '00') {
                $numero_postagem = $result->solicitarPostagemReversa->resultado_solicitacao->numero_coleta;
                $tipo            = $result->solicitarPostagemReversa->resultado_solicitacao->tipo ;
                $comentario      = $result->solicitarPostagemReversa->resultado_solicitacao;


                foreach($comentario as  $key => $value) {
                    $string .= "<b>$key</b>: $value <br>";
                }

                /**
                *   Inserindo ou atualizando a postagem
                **/
                $sql = "BEGIN TRANSACTION";
                $res = pg_query($con, $sql);
                if ($login_fabrica == 1) {
                    $sqlatualiza = "UPDATE tbl_os set autorizacao_domicilio = '$numero_postagem' where os = $solicitacao";
                    $resatualiza = pg_query($sqlatualiza);
                }
                $msg_erro = pg_last_error($con);


                if ($login_fabrica != 1) {

                    $sql_consulta = "SELECT hd_chamado
                                            FROM tbl_hd_chamado_postagem
                                            WHERE fabrica = $login_fabrica
                                            AND hd_chamado = $hd_chamado
                                            AND motivo NOTNULL";
                    $res_consulta = pg_query($con,$sql_consulta);
                    //echo nl2br($sql_consulta);

                    $sql_servico_correio =
                        "SELECT servico_correio
                        FROM tbl_servico_correio
                        WHERE tbl_servico_correio.codigo = '$sedex_pac'";
                    $res_servico_correio = pg_query($con,$sql_servico_correio);
                    //echo nl2br($sql_servico_correio);

                    if (pg_num_rows($res_servico_correio) > 0 ) {
                        $servico_correio = pg_fetch_result($res_servico_correio, 0, servico_correio);
                    }
                    if ($obs == 'undefined') {
                        $obs = "";
                    }

                    if (pg_num_rows($res_servico_correio) > 0) {
                        $sqlatualiza = " INSERT INTO tbl_hd_chamado_postagem (
                                            hd_chamado,
                                            fabrica,
                                            numero_postagem,
                                            tipo_postagem,
                                            servico_correio,
                                            admin,
                                            obs
                                        ) VALUES(
                                            $hd_chamado,
                                            $login_fabrica,
                                            '$numero_postagem',
                                            '$tipo',
                                            $servico_correio,
                                            $id_admin,
                                            '$obs'
                                        ); ";
                        $resatualiza = pg_query($con, $sqlatualiza);

                        if(in_array($login_fabrica, array(162,164))){ //HD-3224475
                            $dados_adicionais = array();

                            if(strlen(trim($checklist)) > 0){
                                $dados_adicionais['checklist'] = $checklist;
                            }
                            if(strlen(trim($numero_documento)) > 0){
                                $dados_adicionais['numero_documento'] = $numero_documento;
                            }
                            $valor_update = array_merge($array_adicionais,$dados_adicionais);
                            $campos_adicioanais = str_replace("\\", "\\\\", json_encode($valor_update));

                            $sqlatualiza_adicionais = "UPDATE tbl_hd_chamado_extra
                                        SET array_campos_adicionais = '$campos_adicioanais'
                                        WHERE hd_chamado = $hd_chamado";
                            $res_atualiza_adicionais = pg_query($sqlatualiza_adicionais);
                        }

                    }else{
                        $msg_erro = "Este Fabricante não possui o serviço solicitado!";
                    }
                }

                /*FIM */

                $string_array = explode("<br>", $string);

                $tipo                = explode(":", $string_array[0]);
                $atendimento         = explode(":", $string_array[1]);
                $numero_autorizacao  = explode(":", $string_array[2]);
                $numero_etiqueta     = explode(":", $string_array[3]);
                $status              = explode(":", $string_array[5]);
                $prazo_postagem      = explode(":", $string_array[6]);
                $data_solicitacao    = explode(":", $string_array[7]);
                $horario_solicitacao = explode(" ", $string_array[8]);


                $status_solicitacao = "<strong>Tipo Solicitação:</strong> ".trim($tipo[1])."<br />";
                $status_solicitacao .= "<strong>Atendimento:</strong> ".trim($atendimento[1])."<br />";
                $status_solicitacao .= "<strong>Número Autorização:</strong> ".trim($numero_autorizacao[1])."<br />";

                if (in_array($login_fabrica, array(1))) {
                    $local = "Logradouro: {$array_dados['destinatario_endereco']} Número: {$array_dados['destinatario_numero']} Complemento: {$array_dados['destinatario_complemento']}  Bairro: {$array_dados['destinatario_bairro']} Cidade: {$array_dados['destinatario_cidade']} UF: {$array_dados['destinatario_estado']} CEP: {$array_dados['destinatario_cep']}";

                    $sql_grava_rastreio = "
                        INSERT INTO tbl_faturamento_correio (
                            fabrica,
                            local,
                            conhecimento,
                            situacao,
                            data,
                            numero_postagem
                        ) VALUES (
                            $login_fabrica,
                            '$local',
                            '".trim($numero_etiqueta[1])."',
                            '".trim($status[1])."',
                            current_timestamp,
                            '$numero_postagem'
                        )";
                    $res_grava_rastreio = pg_query($con, $sql_grava_rastreio);
                }

                if(in_array($login_fabrica, array(162,164))){//HD-3224475
                    $sql_adicionais = "SELECT JSON_FIELD('checklist', tbl_hd_chamado_extra.array_campos_adicionais) AS checklist,
                                            JSON_FIELD('numero_documento', tbl_hd_chamado_extra.array_campos_adicionais) AS numero_documento
                                        FROM tbl_hd_chamado_extra
                                        WHERE hd_chamado = $hd_chamado ";
                    $res_adicionas = pg_query($con, $sql_adicionais);
                    if(pg_num_rows($res_adicionas) > 0){
                        $n_checklist = pg_fetch_result($res_adicionas, 0, 'checklist');
                        $n_documento = pg_fetch_result($res_adicionas, 0, 'numero_documento');
                        switch ($n_checklist) {
                            case '2':
                                $descricao_check = "Checklist Celular";
                                break;
                            case '4':
                                $descricao_check = "Checklist Eletrônico";
                                break;
                            case '5':
                                $descricao_check = "Checklist Documento";
                                break;
                            case '7':
                                $descricao_check = "Checklist Conteúdo";
                                break;
                        }
                    }
                    $status_solicitacao .= "<strong>Check List:</strong> ".$descricao_check."<br />";
                    $status_solicitacao .= "<strong>Número Documento:</strong> ".$n_documento."<br />";
                }


                if( !in_array($login_fabrica, array(11,172)) ){
                    $status_solicitacao .= "<strong>Número Etiqueta:</strong> ".trim($numero_etiqueta[1])."<br />";
                }
                $status_solicitacao .= "<strong>Modo de Envio:</strong> ".trim($modo_envio)."<br />";
                $status_solicitacao .= "<strong>Status:</strong> ".trim($status[1])."<br />";
                $status_solicitacao .= "<strong>Prazo de Postagem:</strong> ".trim($prazo_postagem[1])."<br />";
                $status_solicitacao .= "<strong>Data da Solicitação:</strong> ".trim($data_solicitacao[1])."<br />";
                $status_solicitacao .= "<strong>Horário da Solicitação:</strong> ".trim($horario_solicitacao[1])."<br />";
                if (strlen($volumes) > 0) {
                    $status_solicitacao .= "<strong>Volumes:</strong> ".trim($volumes)."<br />";
                }
                if ($login_fabrica != 1) {

                    if ($telecontrol_distrib && !isset($novaTelaOs)) {
                        $colunaStatus = ", status_item";
                        $valorStatus  = ",'Aguard. Postagem'";
                    }

                    $sql = "INSERT INTO tbl_hd_chamado_item(
                                    hd_chamado   ,
                                    data         ,
                                    comentario   ,
                                    admin        ,
                                    interno
                                    $colunaStatus
                            ) values (
                                    $hd_chamado       ,
                                    current_timestamp ,
                                    '$status_solicitacao',
                                    $login_admin      ,
                                    't'
                                    $valorStatus
                            )";
                    $res = pg_query($sql);

                    if (in_array($login_fabrica,array(35,104,162)) || ($telecontrol_distrib && !isset($novaTelaOs))) {

                        list($dia,$mes,$ano) = explode("/",$data_solicitacao[1]);
                        $dataSoliticacaoGravar = trim($ano).'-'.trim($mes).'-'.trim($dia);

                        $local  = "Logradouro: ".utf8_encode($array_dados['remetente_endereco']);
                        $local .= " Número: ". $array_dados['remetente_numero'];
                        $local .= " Complemento: ".utf8_encode($array_dados['remetente_complemento']);
                        $local .= " Bairro: ".utf8_encode($array_dados['remetente_bairro']);
                        $local .= " Cidade: ".utf8_encode($array_dados['remetente_cidade']);
                        $local .= " UF: ".$array_dados['remetente_estado'];
                        $local .= " CEP: ".$array_dados['remetente_cep'];

                        $sqlCorreios = "
                            INSERT INTO tbl_faturamento_correio(
                                fabrica,
                                local,
                                situacao,
                                data,
                                numero_postagem
                            ) VALUES(
                                $login_fabrica,
                                '$local',
                                '{$status[1]}',
                                '$dataSoliticacaoGravar',
                                '$numero_postagem'
                            )
                        ";
                        //                     exit(nl2br($sqlCorreios));
                        $resCorreios = pg_query($con,$sqlCorreios);
                    }

                } else {
                    $response = json_encode($comentario);

                    $sqlGravaOs = "UPDATE tbl_os_campo_extra SET campos_adicionais = E'$response' WHERE os = $solicitacao";
                    $resGravaOs = pg_query($con,$sqlGravaOs);

                }

                // HD-2357100
                if (!empty($hd_chamado)) {
                    $sql_consumidor = "SELECT tbl_hd_chamado_extra.nome, tbl_hd_chamado_extra.email FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado";
                    $res_consumidor = pg_query($con, $sql_consumidor);

                }

                if(pg_num_rows($res_consumidor) > 0){
                    $consumidor = pg_fetch_result($res_consumidor, 0, 'nome');
                    $email_consumidor = pg_fetch_result($res_consumidor, 0, 'email');
                }

                if ($login_fabrica != 162) {

                    $insert = "INSERT INTO tbl_comunicado (
                            fabrica,
                            posto,
                            obrigatorio_site,
                            tipo,
                            ativo,
                            descricao,
                            mensagem
                        ) VALUES (
                            {$login_fabrica},
                            {$posto},
                            true,
                            'Com. Unico Posto',
                            true,
                            'Postagem de produto',
                            'Será enviado produto do Sr./Sra. $consumidor para conserto via correios'
                        )";
                    $result = pg_query($con, $insert);

                }

                if ($login_fabrica == 1 && empty($msg_erro)) {
                    
                    $remetente = "noreply@telecontrol.com.br";
                    // $email_envio = $email_consumidor;

                    $assunto = $fabrica_nome." - Solicitação de {$msg} Ordem de serviço ".$array_dados['codigo_posto'].$array_dados['sua_os'];
                    $mensagem = "Prezada autorizada, <br/><br/> Foi realizada uma solicitação de {$msg} para a Ordem de Serviço ".$array_dados['codigo_posto'].$array_dados['sua_os'];

                    $mailer = new TcComm($externalId);

                    if (!$mailer->sendMail($email_posto, $assunto, $mensagem, $remetente)) {
                        $msg_erro = "Erro ao enviar email para $email_consumidor";
                        //echo $mailer->ErrorInfo;
                    }
                }

                if ($login_fabrica == 80 && empty($msg_erro)) {
                    
                    $remetente = "noreply@telecontrol.com.br";
                    $assunto = "Amvox - Autorização de postagem";
                    $mensagem = "
                    Olá ".$consumidor.", seu cadastro foi realizado com sucesso e o protocolo de seu atendimento Nº <b>".$hd_chamado."</b><br />
                    Sua autorização de postagem Nº<b> ".trim($numero_autorizacao[1])."</b><br />
                    A autorização é válida após 24h e tem duração de 7 dias corridos.<br />
                    Não esqueça de nos enviar uma cópia da nota fiscal e o comprovante de endereço dentro da embalagem.<br /><br />
                    A Amvox agradece seu contato.<br />
                    www.amvox.com.br<br /><br />
                    <em>
                    Desejando demais esclarecimentos, por favor, não deixe de nos contatar. Nossa Central de relacionamento, ao qual está totalmente à  sua disposição através dos canais:
                    Tel. e fax: (71) 3369-2859 Salvador e região metropolitana - Segunda a sexta - 9 ás 16h;
                    Tel. e fax: 0800 284 5032 Demais localidades. - Segunda a sexta - 9 ás 16h;
                    Facebook, Twitter, Chat online em nosso site - Segunda a sexta - 9 ás 17h;
                    <br /><br />
                    WHATSAPP: (71) 99267.0131 - Segunda a sexta - 9 ás 16h;

                    </em>";

                    $mailer = new TcComm($externalId);

                    if (!$mailer->sendMail($email_consumidor, $assunto, $mensagem, $remetente)) {
                        $msg_erro = "Erro ao enviar email para $email_consumidor";
                    }
                }

                if (strlen($msg_erro) > 0  or strlen(pg_last_error()) > 0 ) {
                    $sql = "ROLLBACK TRANSACTION";
                    $res = pg_query($con, $sql);
                } else {
                    $sql = "COMMIT TRANSACTION";
                    $res = pg_query($con, $sql);

                }
?>

                <div class='container' style="width: 800px;">
                <br/>
                <div class="alert alert-success" style="width: 748px;">
		            <h4>Solicitação de <?=$msg?> solicitada com Sucesso</h4>
                </div>

                <table class='table table-striped table-bordered table-hover' style="width: 800px;">
                  <thead>
                    <tr class="titulo_tabela">
                      <th colspan="<?=(!in_array($login_fabrica, array(11,172))) ? 12 : 9?>" >Status da solicitação</th>
                    </tr>
                    <tr class='titulo_coluna' >
                      <th>Tipo</th>
                      <th>Atendimento</th>
                      <th>Número Autorização</th>
                      <?php if(in_array($login_fabrica, array(162,164))){ ?>
                      <th>Check List</th>
                      <th>Número Documento</th>
                      <?php } ?>
                      <?php if( !in_array($login_fabrica, array(11,172)) ){
                      ?>
                      <th>Número Etiqueta</th>
                      <?php
                        } ?>
                      <th>Modo de Envio</th>
                      <th>Status</th>
                      <th>Prazo de Postagem</th>
                      <th>Data da Solicitação</th>
                      <th>Horário da Solicitação</th>
                      <th>OBS(CallCenter):</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                  <?php
//                         print_r($string_array);
                    $string_array = explode("<br>", $string);

                    $tipo = explode(":", $string_array[0]);
                    $atendimento = explode(":", $string_array[1]);
                    $numero_autorizacao = explode(":", $string_array[2]);
                    $numero_etiqueta = explode(":", $string_array[3]);
                    $status = explode(":", $string_array[5]);
                    $prazo_postagem = explode(":", $string_array[6]);
                    $data_solicitacao = explode(":", $string_array[7]);
                    $horario_solicitacao = explode(" ", $string_array[8]);

                    echo "<td class='tac'>".trim($tipo[1])."</td>";
                    echo "<td class='tac'>".trim($atendimento[1])."</td>";
                    echo "<td class='tac'>".trim($numero_autorizacao[1])."</td>";
                    if(in_array($login_fabrica, array(162,164))){
                        echo "<td class='tac'>".$descricao_check."</td>";
                        echo "<td class='tac'>".$n_documento."</td>";
                    }
                    if( !in_array($login_fabrica, array(11,172)) ){
                        echo "<td class='tac'>".trim($numero_etiqueta[1])."</td>";
                    }
                    echo "<td class='tac'>".trim($modo_envio)."</td>";
                    echo "<td class='tac'>".trim($status[1])."</td>";
                    echo "<td class='tac'>".trim($prazo_postagem[1])."</td>";
                    echo "<td class='tac'>".trim($data_solicitacao[1])."</td>";
                    echo "<td class='tac'>".trim($horario_solicitacao[1])."</td>";
                    echo "<td class='tac'>".$obs."</td>";

                  ?>
                    <tr>
                  </tbody>
                </table>
            </div>
            <?
            if (in_array($login_fabrica,array(11,151,172))) {
                if (trim(strlen($email_consumidor)) > 0) {

                    $remetente = ( in_array($login_fabrica, array(11,172)) )
                        ? 'sac@lenoxx.com.br'
                        : (($login_fabrica == 151)
                            ? 'sac@mondialline.com.br'
                            : $externalEmail
                    );
                    // $email_envio = $email_consumidor;

                    $assunto = "Fábrica: ".$fabrica." - Informações sobre sua solicitação";
                    $mensagem = "Sr./Sra. $consumidor informações sobre sua solicitação de postagem.<br/>
                    Protocolo de Atendimento : ".$hd_chamado."<br />
                    Produto: ".$produto."<br />
                    Número da Autorização: ".$numero_autorizacao[1]."<br />
                    Modo de Envio: ".$modo_envio."<br />
                    Data da Solicitação: ".$data_solicitacao[1]."<br />
                    Prazo para Postagem: ".$prazo_postagem[1];

                    $mailer = new TcComm($externalId);

                    if (!$mailer->sendMail($email_consumidor, $assunto, $mensagem, $remetente)) {
                        $msg_erro = "Erro ao enviar email para $email_consumidor";
                        //echo $mailer->ErrorInfo;
                    }
                }
            }
        } else {

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
            $aux_sql = "
                SELECT numero_postagem
                FROM tbl_hd_chamado_postagem
                WHERE fabrica = $login_fabrica
                AND hd_chamado = $hd_chamado;
                AND motivo NOTNULL
            ";
            $aux_res = pg_query($con, $aux_sql);
            $aux_numero_postagem = "";

            if(pg_num_rows($aux_sql) > 0) {
                $aux_numero_postagem = pg_fetch_result($aux_sql, 0, 'numero_postagem');
            }

            if ($result->acompanharPedido->coleta) {
                $historico = $result->acompanharPedido->coleta->historico;
                $tipo_solicitacao = $result->acompanharPedido->tipo_solicitacao;
                $qtde_his = count($historico);

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
      <h4>Solicitação de Postagem já Realizada</h4>
    </div>

    <table class='table table-striped table-bordered table-hover' style="width: 800px;">
      <thead>
        <tr class="titulo_tabela">
          <th colspan="9" >Dados da Solicitação</th>
        </tr>
        <tr class='titulo_coluna' >
          <th>Data</th>
          <th>Hora</th>
          <th>Autorização</th>
          <!-- <th>Status</th> --> <!-- HD-3853415 07/11/2017 -->
          <th>Tipo de Postagem</th>
          <th>Descrição do Status</th>
          <?php if(in_array($login_fabrica, array(162,164))){ ?>
              <th>Check List</th>
              <th>Número Documento</th>
          <?php } ?>
          <th>Etiqueta</th>
          <th>Obs(Correios):</th>
        </tr>
      </thead>
      <tbody>
      <?php

            $qtde_his           = count($historico);
            $numero_etiqueta    = $result->acompanharPedido->coleta->objeto->numero_etiqueta;
            $objeto             = $result->acompanharPedido->coleta->objeto;
            $numero_autorizacao = $result->acompanharPedido->coleta->numero_pedido;

            if(in_array($login_fabrica, array(162,164))){//HD-3224475
                $sql_adicionais = "SELECT JSON_FIELD('checklist', tbl_hd_chamado_extra.array_campos_adicionais) AS checklist,
                                        JSON_FIELD('numero_documento', tbl_hd_chamado_extra.array_campos_adicionais) AS numero_documento
                                    FROM tbl_hd_chamado_extra
                                    WHERE hd_chamado = $hd_chamado ";
                $res_adicionas = pg_query($con, $sql_adicionais);
                if(pg_num_rows($res_adicionas) > 0){
                    $n_checklist = pg_fetch_result($res_adicionas, 0, 'checklist');
                    $n_documento = pg_fetch_result($res_adicionas, 0, 'numero_documento');

                    switch ($n_checklist) {
                        case '2':
                            $descricao_check = "Checklist Celular";
                            break;
                        case '4':
                            $descricao_check = "Checklist Eletrônico";
                            break;
                        case '5':
                            $descricao_check = "Checklist Documento";
                            break;
                        case '7':
                            $descricao_check = "Checklist Conteúdo";
                            break;
                    }
                }
            }



            if ($qtde_his>1) {
                foreach($historico as $dados) {
                    $dados->data_atualizacao = str_replace("-", "/", $dados->data_atualizacao);

                    echo "<tr>";
                    echo "<td class='tac'>".$dados->status."</td>";
                    echo "<td class='tac'>".trim(utf8_decode($dados->descricao_status))."</td>";
                    echo "<td class='tac'>".trim($dados->data_atualizacao)."</td>";
                    echo "<td class='tac'>".trim($dados->hora_atualizacao)."</td>";
                    echo "<td class='tac'>".$numero_autorizacao."</td>";
                    if(in_array($login_fabrica, array(162,164))){//HD-3224475
                        echo "<td class='tac'>".$descricao_check."</td>";
                        echo "<td class='tac'>".$n_documento."</td>";
                    }
                    echo "<td class='tac'>".$numero_etiqueta."</td>";
                    echo "<td class='tac'>".trim(utf8_decode($dados->observacao))."</td>";
                    echo "</tr>";
                }
            } else {
                $historico->data_atualizacao = str_replace("-", "/", $historico->data_atualizacao);
                echo "<tr>";
                echo "<td class='tac'>".trim($historico->data_atualizacao)."</td>";
                echo "<td class='tac'>".trim($historico->hora_atualizacao)."</td>";
                echo "<td class='tac'>".$numero_autorizacao."</td>";
                //echo "<td class='tac'>".$historico->status."</td>"; /* HD-3853415 07/11/2017 */
                echo "<td class='tac'>".$tipo_solicitacao."</td>";
                echo "<td class='tac'>".trim(utf8_decode($historico->descricao_status))."</td>";
                echo "<td class='tac'>".$numero_etiqueta."</td>";
                if(in_array($login_fabrica, array(162,164))){//HD-3224475
                    echo "<td class='tac'>".$descricao_check."</td>";
                    echo "<td class='tac'>".$n_documento."</td>";
                }

                echo "<td class='tac'>".trim(utf8_decode($historico->observacao))."</td>";
                echo "</tr>";
            }
    ?>
          </tbody>
        </table>
      </div>

      <?php
        $aux_sql = "
            SELECT
                tbl_hd_chamado_postagem.data,
                tbl_hd_chamado_postagem.tipo_postagem,
                tbl_hd_chamado_postagem.numero_postagem,
                tbl_hd_chamado_postagem.motivo,
                tbl_hd_chamado_item.comentario
            FROM tbl_hd_chamado_postagem
            JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_postagem.hd_chamado
            WHERE tbl_hd_chamado_postagem.fabrica = {$login_fabrica}
            AND tbl_hd_chamado_postagem.hd_chamado = {$hd_chamado}
            AND tbl_hd_chamado_postagem.motivo NOTNULL
            AND tbl_hd_chamado_item.comentario LIKE '%Tipo Solicitação%'
            ORDER BY
            tbl_hd_chamado_postagem.data,
            tbl_hd_chamado_postagem.tipo_postagem,
            tbl_hd_chamado_postagem.numero_postagem,
            tbl_hd_chamado_postagem.motivo,
            tbl_hd_chamado_item.comentario
            ASC
            LIMIT 1
        ";
        $aux_res = pg_query($con, $aux_sql);

        if(pg_num_rows($aux_res) > 0) { ?>
            <div class="alert alert-info" style="width: 748px;">
                <h4>
                    Solicitação de Postagem Anterior
                </h4>
            </div>

            <table class='table table-striped table-bordered table-hover' style="width: 800px;">
                <thead>
                    <tr class="titulo_tabela">
                      <th colspan="6" >Dados da Solicitação</th>
                    </tr>
                    <tr class='titulo_coluna' >
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Autorização</th>
                        <th>Tipo de Postagem</th>
                        <th>Descrição do Status</th>
                        <th>Informações Adicionais</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i=0; $i < pg_num_rows($aux_res); $i++) {
                            $data_hora           = pg_fetch_result($aux_res, $i, 'data');
                            $data                = explode(" ", $data_hora);
                            $hora                = explode(".", $data[1]);
                            $tipo_postagem       = pg_fetch_result($aux_res, $i, 'tipo_postagem');
                            $aux_numero_postagem = pg_fetch_result($aux_res, $i, 'numero_postagem');
                            $aux_motivo          = utf8_decode(pg_fetch_result($aux_res, $i, 'motivo'));
                            $aux_comentario      = pg_fetch_result($aux_res, $i, 'comentario');
                    ?>
                        <tr>
                            <td class="tac"><?=date('d/m/Y', strtotime($data[0]));?></td>
                            <td class="tac"><?=$hora[0];?></td>
                            <td class="tac"><?=$aux_numero_postagem;?></td>
                            <td class="tac"><?=$tipo_postagem;?></td>
                            <td><?=$aux_motivo;?></td>
                            <td><?=$aux_comentario;?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
            <?
            }else if($result->acompanharPedido->cod_erro != "00"){
                $value = utf8_decode($result->acompanharPedido->msg_erro);

                /*HD-4140018*/
                if (strlen($value) == 0) {
                    $value = "Detectado um erro não identificado com o serviço dos Correios";
                }

                ?>
                  <div class='container' style='width: 800px;'>
                    <div class='alert alert-danger'>
                        <h4><?=$value?></h4>
                    </div>
                  </div>
                <?php
            }

        }
    } catch (Exception $e) {
        if ($_environment == 'development')
            var_dump($e);

        /*HD-4140018*/
        if (strlen($msg_erro) == 0) {
            $msg_erro = "Detectado um erro não identificado com o serviço dos Correios";
        }

        echo $msg_erro;
    }
?>
</body>
</html>
