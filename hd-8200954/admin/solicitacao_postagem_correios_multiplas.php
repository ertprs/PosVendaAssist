<?php
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    $admin_privilegios="call_center";
    include 'autentica_admin.php';
    include_once '../class/communicator.class.php';
    include_once 'funcoes.php';

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

	$checklist = "";
    $numero_documento = "";


    /*echo "<pre>";
    print_r($dados_acesso);
    exit;*/

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
                tbl_produto.descricao,
                tbl_produto.marca,
                tbl_hd_chamado_extra.cpf
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

    $res = pg_query($sql);

    if(pg_num_rows($res)>0) {
        $array_adicionais = pg_fetch_result($res, 0, 'array_campos_adicionais'); //HD-3224475
        $posto       = pg_fetch_result($res, 0, 'posto_id');
        $produto     = pg_fetch_result($res, 0, 'produto');
        $marca       = pg_fetch_result($res, 0, 'marca');
        $fabrica     = pg_fetch_result($res, 0, 'fabrica');
        $email_posto = pg_fetch_result($res, 0, 'posto_email');
        $fabrica_nome= pg_fetch_result($res, 0, 'fabrica_nome');
        $array_dados = pg_fetch_array($res);

    }

    $sqlAcesso = "SELECT usuario,
	    senha,
	    codigo as codadministrativo,
	    contrato,
	    cartao,
	    id_correio, marca
	FROM tbl_fabrica_correios
	WHERE fabrica = $login_fabrica 
	AND (tipo_contrato = 'solicitar_postagem' OR tipo_contrato IS NULL) {$cond}";
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

        if($login_fabrica == 160 or $replica_einhell){
          $password = $dados_acesso["password"];
        } else if($telecontrol_distrib){
          $password = "tele6588";
        }

		if($login_fabrica == 11 or $login_fabrica == 172){
          $password = "aulik";
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
) );
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

    if (empty($array_dados['numero_postagem']) || (strlen($function_cancelar_ped) > 0 && $postagem_cancelada == true) || isset($_GET['nova_postagem'])) {

            //$id_cliente_obs = $hd_chamado . ";" . $obs;
            //$obs = "";

            if (isset($_GET['nova_postagem'])) {

                $sqlQtdColetas = "SELECT COUNT(*) as qtd_postagem
                                  FROM tbl_hd_chamado_postagem 
                                  WHERE hd_chamado = {$hd_chamado}";
                $resQtdColetas = pg_query($con, $sqlQtdColetas);

                $qtd_coletas = pg_fetch_result($resQtdColetas, 0, 'qtd_postagem');

                $codigo_postagem = $hd_chamado."-".$qtd_coletas;
            } else {
                $codigo_postagem = $hd_chamado;
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
                'id_cliente' => $codigo_postagem,
                'cklist'  => $checklist, ////HD-3224475
                'documento' => $numero_documento, ////HD-3224475
                'remetente'  => (object)   array(
                    'nome'       => $array_dados['rementente_nome'],
                    'logradouro' => utf8_encode($array_dados['remetente_endereco']),
                    'numero'     => $array_dados['remetente_numero'],
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
                    'id'   => $codigo_postagem.";".(($obs == "undefined") ? "" : utf8_encode($obs)),
                    'desc' => utf8_encode($array_dados['descricao'])
                )
            )
        );
        $dias = 30;

        if (in_array($login_fabrica, [186])) {
            $dias = 15;
        }

        if ($tipo == 'A') {
            $array_request->coletas_solicitadas->ag = $dias;
        }

        $msg = "Postagem";

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
					)
));
        } catch (Exception $e) {
            $response[] = array("resultado" => "false", "mensagem" => "ERRO AO CONECTAR SERVIDOR DOS CORREIOS");

            throw new \Exception($response);
        }

	    $result = "";
        try {
            $result = $client->__soapCall($function, array($array_request));


        } catch (Exception $e) {
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
                $msg_erro = pg_last_error($con);

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

		$local  = "Logradouro: ".utf8_encode($array_dados['remetente_endereco']);
		$local .= " Número: ". $array_dados['remetente_numero'];
		$local .= " Complemento: ".utf8_encode($array_dados['remetente_complemento']);
		$local .= " Bairro: ".utf8_encode($array_dados['remetente_bairro']);
		$local .= " Cidade: ".utf8_encode($array_dados['remetente_cidade']);
		$local .= " UF: ".$array_dados['remetente_estado'];
		$local .= " CEP: ".$array_dados['remetente_cep'];

		$sql_grava_rastreio = "                                                                                                                                                                   
			INSERT INTO tbl_faturamento_correio (
				fabrica,
				local,
				situacao,
				data,
				numero_postagem
			) VALUES (
				$login_fabrica,
				'$local',
				'Aguard. Postagem',
				current_timestamp,
				'$numero_postagem'
			)";
		$res_grava_rastreio = pg_query($con, $sql_grava_rastreio);

                }else{
                    $msg_erro = "Este Fabricante não possui o serviço solicitado!";
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
                $status_solicitacao .= "<strong>Número Etiqueta:</strong> ".trim($numero_etiqueta[1])."<br />";
                $status_solicitacao .= "<strong>Modo de Envio:</strong> ".trim($modo_envio)."<br />";
                $status_solicitacao .= "<strong>Status:</strong> ".trim($status[1])."<br />";
                $status_solicitacao .= "<strong>Prazo de Postagem:</strong> ".trim($prazo_postagem[1])."<br />";
                $status_solicitacao .= "<strong>Data da Solicitação:</strong> ".trim($data_solicitacao[1])."<br />";
                $status_solicitacao .= "<strong>Horário da Solicitação:</strong> ".trim($horario_solicitacao[1])."<br />";

                $sql = "INSERT INTO tbl_hd_chamado_item(
                                hd_chamado   ,
                                data         ,
                                comentario   ,
                                admin        ,
                                interno      , 
                                status_item
                        ) values (
                                $hd_chamado       ,
                                current_timestamp ,
                                '$status_solicitacao',
                                $login_admin      ,
                                't'               ,
                                'Aguard. Postagem'
                        )";
                $res = pg_query($sql);

                // HD-2357100
                if (!empty($hd_chamado)) {
                    $sql_consumidor = "SELECT tbl_hd_chamado_extra.nome, tbl_hd_chamado_extra.email FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado";
                    $res_consumidor = pg_query($con, $sql_consumidor);
                }

                if(pg_num_rows($res_consumidor) > 0){
                    $consumidor = pg_fetch_result($res_consumidor, 0, 'nome');
                    $email_consumidor = pg_fetch_result($res_consumidor, 0, 'email');
                }

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
                      <th colspan="12" >Status da solicitação</th>
                    </tr>
                    <tr class='titulo_coluna' >
                      <th>Tipo</th>
                      <th>Atendimento</th>
                      <th>Número Autorização</th>
                      <th>Número Etiqueta</th>
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
                    echo "<td class='tac'>".trim($numero_etiqueta[1])."</td>";
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
            } else {
             //print_r($result->solicitarPostagemReversa->resultado_solicitacao); exit;
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
          <th>Etiqueta</th>
          <th>Obs(Correios):</th>
        </tr>
      </thead>
      <tbody>
      <?php

            $sqlSolicitacoes = "SELECT numero_postagem,tipo_postagem
                                FROM tbl_hd_chamado_postagem
                                WHERE hd_chamado = {$hd_chamado}";
            $resSolicitacoes = pg_query($con, $sqlSolicitacoes);
            
            while ($linha = pg_fetch_array($resSolicitacoes)) {

                $array_request =  (object) array(
                    'codAdministrativo'=> $dados_acesso['codAdministrativo'],
                    'tipoBusca'=>'H',
                    'numeroPedido'=>$linha['numero_postagem'],
                    'tipoSolicitacao'=> $linha['tipo_postagem']
                );
                
                $result = $client->__soapCall('acompanharPedido', array($array_request));
		$dados_historico = $result->acompanharPedido->coleta->historico;

		if(!is_array($dados_historico)){
			$dados_historico = array(0 => $dados_historico);
		}

		foreach($dados_historico AS $key => $historico){
			echo "<tr>";
			    echo "<td class='tac'>".trim(str_replace("-","/",$historico->data_atualizacao))."</td>";
			    echo "<td class='tac'>".trim($historico->hora_atualizacao)."</td>";
			    echo "<td class='tac'>".$result->acompanharPedido->coleta->numero_pedido."</td>";
			    //echo "<td class='tac'>".$historico->status."</td>"; /* HD-3853415 07/11/2017 */
			    echo "<td class='tac'>".$result->acompanharPedido->tipo_solicitacao."</td>";
			    echo "<td class='tac'>".trim(utf8_decode($historico->descricao_status))."</td>";
			    echo "<td class='tac'>".$result->acompanharPedido->coleta->objeto->numero_etiqueta."</td>";
			    echo "<td class='tac'>".trim(utf8_decode($historico->observacao))."</td>";
			 echo "</tr>";
		}
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

        } ?>
        <center>
            <form method="GET" action"<?= $_SERVER['PHP_SELF'] ?>">

                <input type="hidden" name="hd_chamado" value="<?= $hd_chamado ?>" />
                <input type="hidden" name="solicitacao" value="<?= $solicitacao ?>" />
                <input type="hidden" name="tipo" value="<?= $tipo ?>" />
                <input type="hidden" name="sedex_pac" value="<?= $sedex_pac ?>" />
                <input type="hidden" name="obs" value="<?= $obs ?>" />
                <input type="hidden" name="valor_nf" value="<?= $valor_nf ?>" />
                <input type="hidden" name="login_admin" value="<?= $id_admin ?>" />
                <button id="nova_postagem" name="nova_postagem" class="btn btn-large btn-primary">
                    Nova Solicitação
                </button>
            </form>
        </center>
    <?php
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
