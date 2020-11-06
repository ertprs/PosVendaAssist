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
    include 'autentica_usuario.php';

    $solicitacao  = filter_input(INPUT_GET,'solicitacao');
    $tipo         = filter_input(INPUT_GET,'tipo');
    $os         = filter_input(INPUT_GET,'os');

    /* VARIÁVEL PARA DEFINIR QUAL WEBSERVICE E DADOS DE CONTRATO DOS CORREIOS QUE SERÁ UTILIZADO
        NA SOLICITAÇÃO DE POSTAGEM */
    //$ambiente = "devel";
    $ambiente = ($_serverEnvironment == "development") ? "devel" : "producao";

	$tbl_posto_join = 'tbl_hd_chamado_extra';

	if (in_array($login_fabrica, [11, 172])) {
		$sql_posto = "SELECT posto FROM tbl_hd_chamado_extra WHERE hd_chamado = $solicitacao AND posto IS NOT NULL";
		$qry_posto = pg_query($con, $sql_posto);

		if (pg_num_rows($qry_posto) == 0) {
			$tbl_posto_join = 'tbl_os';
		}
	}

        $sql = "
            SELECT  tbl_posto.posto as posto_id,
                    tbl_posto.nome as rementente_nome,
                    tbl_posto_fabrica.contato_endereco          AS remetente_endereco,
                    tbl_posto_fabrica.contato_bairro            AS remetente_bairro,
                    tbl_posto_fabrica.contato_numero            AS remetente_numero,
                    tbl_posto_fabrica.contato_cidade            AS remetente_cidade,
                    tbl_posto_fabrica.contato_estado            AS remetente_estado,
                    tbl_posto_fabrica.contato_cep               AS remetente_cep,
                    tbl_posto_fabrica.contato_complemento       AS remetente_complemento,
                    tbl_posto_fabrica.contato_fone_comercial    AS remetente_fone,
                    tbl_hd_chamado_extra.valor_nf,
                    tbl_os.autorizacao_domicilio,
                    tbl_hd_chamado_extra.nome                   AS destinatario_nome,
                    tbl_hd_chamado_extra.endereco               AS destinatario_endereco,
                    tbl_hd_chamado_extra.numero                 AS destinatario_numero,
                    tbl_hd_chamado_extra.complemento            AS destinatario_complemento,
                    tbl_hd_chamado_extra.bairro                 AS destinatario_bairro,
                    tbl_hd_chamado_extra.cep                    AS destinatario_cep,
                    tbl_cidade.nome                             AS destinatario_cidade,
					tbl_cidade.estado                           AS destinatario_estado,
					tbl_os.os
            FROM    tbl_hd_chamado
            JOIN    tbl_hd_chamado_extra    ON  tbl_hd_chamado.hd_chamado   = tbl_hd_chamado_extra.hd_chamado
            JOIN    tbl_cidade              ON  tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
            JOIN    tbl_os                  ON  (tbl_hd_chamado_extra.os     = tbl_os.os or tbl_os.hd_chamado = tbl_hd_chamado.hd_chamado)
            JOIN    tbl_posto               ON  tbl_posto.posto             = {$tbl_posto_join}.posto
            JOIN    tbl_posto_fabrica       ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
            WHERE   tbl_hd_chamado.hd_chamado = $solicitacao";
//     echo nl2br($sql);exit;
    $res = pg_query($sql);

    if (pg_num_rows($res) > 0) {
        $posto       = pg_fetch_result($res, 0, 'posto_id');
        $array_dados = pg_fetch_array($res);
        if ($array_dados['remetente_estado'] == $array_dados['destinatario_estado']) {
            $servico_correio = "04170";
            $id_servico_correio = "10";
        } else {
            $servico_correio = "04677";
            $id_servico_correio = "11";
        }

		if(empty($array_dados['valor_nf'])) {
			$os = $array_dados['os'];
			$sqln = "SELECT sum(tbl_faturamento_item.preco) as total FROM tbl_os_item join tbl_os_produto using(os_produto) join tbl_faturamento_item using(pedido, peca) WHERE tbl_os_produto.os = $os";
			$resn = pg_query($con, $sqln);
			if(pg_num_rows($resn) > 0) {
				$array_dados['valor_nf'] = pg_fetch_result($resn, 0 , 'total');
			}
		}
    }

    $id_cliente = ($login_fabrica == 1) ? $array_dados['codigo_posto'].$array_dados['sua_os'] : $os;

    if($ambiente == "devel"){

        /* HOMOLOGAÇÃO */
        $dados_acesso =  array(
            'codAdministrativo' => "17000190",
            'codigo_servico'    => "41076",
            'cartao'            => "0067599079"
        );

    } else {

        $sqlAcesso = "SELECT  usuario,
        senha,
        codigo as codadministrativo,
        contrato,
        cartao,
        id_correio
        FROM tbl_fabrica_correios
        WHERE fabrica = $login_fabrica";
        $resAcesso = pg_query($sqlAcesso);

        if (pg_num_rows($resAcesso) > 0) {

            $dados_acesso = pg_fetch_array($resAcesso);

            /* PRODUÇÃO */
            $dados_acesso =  array(
                'username'          => $dados_acesso['usuario'],
                'password'          => $dados_acesso['senha'],
                'codAdministrativo' => $dados_acesso['codadministrativo'],
                'codigo_servico'    => $servico_correio,
                'cartao'            => $dados_acesso['cartao'],
                'id_correio'        => $dados_acesso['id_correio']
            );

        } else {

            die('Fabrica não liberada para este recurso! consulte nosso suporte');

        }
    }

    if(!empty($array_dados['autorizacao_domicilio'])) {

        $array_request =  (object) array(
            'codAdministrativo' => $dados_acesso['codAdministrativo'],
            'tipoBusca'         => 'H',
            'numeroPedido'      => $array_dados['autorizacao_domicilio'],
            'tipoSolicitacao'   => $tipo
        );

        $function = 'acompanharPedido';

    } else {

        $array_request = (object)  Array(
            'codAdministrativo' => (int) $dados_acesso['codAdministrativo'],
            'codigo_servico'    => $dados_acesso['codigo_servico'],
            'cartao'            => $dados_acesso['cartao'],

            'destinatario' => (object)  array(
                'nome'       => utf8_encode($array_dados['destinatario_nome']),
                'logradouro' => utf8_decode($array_dados['destinatario_endereco']),
                'numero'     => utf8_encode($array_dados['destinatario_numero']),
                'complemento'     => utf8_encode($array_dados['destinatario_complemento']),
                'cidade'     => utf8_decode($array_dados['destinatario_cidade']),
                'uf'         => $array_dados['destinatario_estado'],
                'bairro'     => utf8_decode($array_dados['destinatario_bairro']),
                'cep'        => $array_dados['destinatario_cep']
            ),
            'coletas_solicitadas' =>  (object) array(
                'tipo'       => $tipo,
                'descricao'  => '',
                'id_cliente' => $id_cliente,

                'remetente'  => (object) array(
                'nome'       => utf8_encode($array_dados['rementente_nome']),
                'logradouro' => utf8_encode($array_dados['remetente_endereco']),
                'numero'     => utf8_encode($array_dados['remetente_numero']),
                'complemento'     => utf8_encode($array_dados['remetente_complemento']),
                'bairro'     => utf8_encode($array_dados['remetente_bairro']),
                'cidade'     => utf8_encode($array_dados['remetente_cidade']),
                'uf'         => $array_dados['remetente_estado'],
                'cep'        => $array_dados['remetente_cep']
                ),
                'valor_declarado' => $array_dados['valor_nf'],
                //    'ag' => '15',
                //    'ar'=>'1',
                'obj_col' => (object) array(
                    'item'=>1
                )
            )
        );
        if ($tipo == 'A') {
            $array_request->coletas_solicitadas->ag = 30 ;
        }

		if ((float) $array_dados['valor_nf'] < 18.5) {
			unset($array_request->coletas_solicitadas->valor_declarado);
		}

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
    }

    if ($function=='solicitarPostagemReversa') {
        $array_request =  $array_request;

        $return = array_walk_recursive($array_request,'validaArray');

        if (count($array_erro)>0) {

            foreach($array_erro as $value) {
                echo "<div class='alert alert-danger'>preecher o campo $value</div>";
            }

            die;
        }
    }

    if ($ambiente == "devel") {
        /* HOMOLOGAÇÃO */
        $url_novo_webservice = "https://apphom.correios.com.br/logisticaReversaWS/logisticaReversaService/logisticaReversaWS?wsdl";

        $username = "empresacws";
        $password = "123456";

    } else {
        /* PRODUÇÃO */
        $url_novo_webservice = "https://cws.correios.com.br/logisticaReversaWS/logisticaReversaService/logisticaReversaWS?wsdl";

        if ( in_array($login_fabrica, array(11,172))) {
            $password = "aulik";

        } else if ($login_fabrica == 104) {
            $password = "ovd76635689";

        } else if ($login_fabrica == 151) {
            $password = "monitora2016";

        } else if (in_array($login_fabrica,array(35,153))) {
            $password = $dados_acesso["password"];

        } else if ($login_fabrica == 156) {
            $password = "tele6588";

        } else if ($login_fabrica == 162) {
            $password = "qbex2016";

        }  else {
            $password = "tele6588";
        }

        $username = $dados_acesso["id_correio"];
    }

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
    if ($function=='solicitarPostagemReversa') {
        if ($result->solicitarPostagemReversa->resultado_solicitacao->codigo_erro == '00') {

            $numero_postagem = $result->solicitarPostagemReversa->resultado_solicitacao->numero_coleta;
            $tipo            = $result->solicitarPostagemReversa->resultado_solicitacao->tipo ;
            $comentario      = $result->solicitarPostagemReversa->resultado_solicitacao;


            foreach($comentario as $key => $value) {
                $string .= "<b>$key</b>: $value <br>";
            }

            $sqlatualiza = "UPDATE tbl_os set autorizacao_domicilio = '$numero_postagem' where hd_chamado = $solicitacao";
            $resatualiza = pg_query($sqlatualiza);

            $string_array = explode("<br>", $string);

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

            if( !in_array($login_fabrica, array(11,172)) ){
                $status_solicitacao .= "<strong>Numero Etiqueta:</strong> ".trim($numero_etiqueta[1])."<br />";
            }

            $status_solicitacao .= "<strong>Status:</strong> ".trim($status[1])."<br />";
            $status_solicitacao .= "<strong>Prazo de Postagem:</strong> ".trim($prazo_postagem[1])."<br />";
            $status_solicitacao .= "<strong>Data da Solicitação:</strong> ".trim($data_solicitacao[1])."<br />";
            $status_solicitacao .= "<strong>Horário da Solicitação:</strong> ".trim($horario_solicitacao[1])."<br />";
            
            $sql = "INSERT INTO tbl_hd_chamado_postagem (
                    hd_chamado,
                    fabrica,
                    os,
                    numero_postagem,
                    tipo_postagem,
                    servico_correio
                    ) VALUES(
                    $solicitacao,
                    $login_fabrica,
                    $os,
                    '$numero_postagem',
                    '{$tipo[1]}',
                    $id_servico_correio
                    )";                    

            $res = pg_query($sql);            

            //if (in_array($login_fabrica, array(1,104))) {
                $local = "Logradouro: {$array_dados['destinatario_endereco']} Número: {$array_dados['destinatario_numero']} Complemento: {$array_dados['destinatario_complemento']}  Bairro: {$array_dados['destinatario_bairro']} Cidade: {$array_dados['destinatario_cidade']} UF: {$array_dados['destinatario_estado']} CEP: {$array_dados['destinatario_cep']} ";
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
            //}

                $sql = "INSERT INTO tbl_hd_chamado_item(
                    hd_chamado   ,
                    data         ,
                    comentario   ,
                    posto        ,
                    interno
                    ) values (
                    $solicitacao,
                    current_timestamp ,
                    'Postagem feita pelo posto:<br />$status_solicitacao',
                    $posto      ,
                    't'
                    )";
                $res = pg_query($sql);


                $sql_os = "SELECT tbl_os.os,
                    tbl_os.posto,
                    tbl_os.consumidor_email,
                    tbl_os.consumidor_nome,
                    tbl_os.sua_os,
                    tbl_produto.referencia || ' - ' ||tbl_produto.descricao AS produto,
                    tbl_produto.produto AS id_produto,
                    tbl_posto.nome_fantasia,
                    tbl_fabrica.nome AS fabrica
                    FROM tbl_os JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
                    JOIN tbl_produto using (produto)
                    JOIN tbl_fabrica using (fabrica)
                    WHERE hd_chamado = $solicitacao";
                $res_os = pg_query($con, $sql_os);

                if(pg_num_rows($res_os) > 0){
                    $id_os            = pg_fetch_result($res_os, 0, 'os');
                    $sua_os           = pg_fetch_result($res_os, 0, 'sua_os');
                    $id_posto         = pg_fetch_result($res_os, 0, 'posto');
                    $email_consumidor = pg_fetch_result($res_os, 0, 'consumidor_email');
                    $consumidor_nome  = pg_fetch_result($res_os, 0, 'consumidor_nome');
                    $nome_fantasia    = pg_fetch_result($res_os, 0, 'nome_fantasia');
                    $produto          = pg_fetch_result($res_os, 0, 'produto');
                    $fabrica          = pg_fetch_result($res_os, 0, 'fabrica');
                    $id_produto       = pg_fetch_result($res_os, 0, 'id_produto');
                }

                $insert_interacao = "INSERT INTO tbl_os_interacao(
                    os,
                    comentario,
                    posto
                    )VALUES(
                    $id_os,
                    'Postagem feita pelo posto:<br />$status_solicitacao',
                    $id_posto
                    )";
                $res_insert = pg_query($con, $insert_interacao);

            if (trim(strlen($email_consumidor)) > 0) {

                include_once 'class/email/mailer/class.phpmailer.php';
                $mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

                $headers  = "MIME-Version: 1.0 \r\n";
                $headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";

                if ( in_array($login_fabrica, array(11,172))) {
                    $headers .= "From: Suporte <sac@lenoxx.com.br> \r\n";
                } else if ($login_fabrica == 151) {
                    $headers .= "From: Suporte <sac@mondialline.com.br> \r\n";
                } else if ($login_fabrica == 74) {
                    $headers .= "From: Suporte <sac01@atlas.ind.br> \r\n";
                } else if ($login_fabrica == 122) {
                    $headers .= "From: Suporte <wurth.sac@telecontrol.com.br> \r\n";
                } else if ($login_fabrica == 125) {
                    $headers .= "From: Suporte <atendimento@telecontrol.com.br> \r\n";
                } else if ($login_fabrica == 114) {
                    $headers .= "From: Suporte <sac@cobimex.com.br> \r\n";
                } else if ($login_fabrica == 123) {
                    $headers .= "From: Suporte <sac.positec@gmail.com> \r\n";
                } else if ($login_fabrica == 81) {
                    $sql_marca = "SELECT marca
                    FROM tbl_marca
                    JOIN tbl_produto using(marca)
                    WHERE  produto = {$id_produto}
                    AND fabrica = {$login_fabrica};";
                    $res_marca = pg_query($con,$sql_marca);

                    if (pg_num_rows($res_marca) > 0) {
                        $marca_bw = pg_fetch_result($res_marca, 0, marca);

                        if ($marca_bw == 157) {
                            $headers .= "From: Suporte <sac.georgeforeman@gmail.com> \r\n";
                        } else if ($marca_bw == 178) {
                            $headers .= "From: Suporte <sac.rayovac@gmail.com> \r\n";
                        } else if ($marca_bw == 177) {
                            $headers .= "From: Suporte <sac.remington@gmail.com> \r\n";
                        } else {
                            $headers .= "From: Suporte <helpdesk@telecontrol.com.br> \r\n";
                        }
                    }
                }

                $headers .= "From: Suporte <helpdesk@telecontrol.com.br> \r\n";
                $email_envio = $email_consumidor;

                $assunto = "Fábrica: ".$fabrica." - Informações sobre seu produto";
                $mensagem = "Sr./Sra. $consumidor_nome, informamos que o seu produto <b>($produto)</b> foi consertado e será enviado via Correios.<br/><br/>";
                $mensagem .= "Ordem de Serviço: $sua_os<br/>";
                $mensagem .= "Dados da postagem:<br/>$status_solicitacao";

                if (!mail($email_envio, utf8_encode($assunto), utf8_encode($mensagem), $headers)) {
                    $msg_erro = "Erro ao enviar email para $email_envio";
                    //echo $mailer->ErrorInfo;
                }
            }
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
    <?php
            if (!in_array($login_fabrica, array(11,172))) {
    ?>
                    <th>Numero Etiqueta</th>
    <?php
            }
    ?>
                    <th>Status</th>
                    <th>Prazo de Postagem</th>
                    <th>Data Solicitação</th>
                    <th>Horário Solicitação</th>
                    </tr>
                </thead>
                <tbody>
		    <tr>
		    <td><?=trim($status[1])?></td>
		    <td><?=trim($prazo_postagem[1])?></td>
		    <td><?=trim($data_solicitacao[1])?></td>
		    <td><?=trim($horario_solicitacao[1])?></td>
		    </tr>
		</tbody>
		</table>
<?php
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

            } else {
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
            $historico = $result->acompanharPedido->coleta->historico;
            $qtde_his  = count($historico);

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
                    <th colspan="6" >Status da solicitação <?=$result->acompanharPedido->coleta->numero_pedido?></th>
                    </tr>
                    <tr class='titulo_coluna' >
                    <th>Status</th>
                    <th>Descricao do Status</th>
                    <th>Data da atualizacao</th>
                    <th>Horário da atualização</th>
                    <th>Obs:</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
<?php
            $qtde_his = count($historico);
            $objeto   = $result->solicitarPostagemReversa->coleta->objeto;

            if ($qtde_his>1) {
                foreach($historico as $dados) {
                    $dados->data_atualizacao = str_replace("-", "/", $dados->data_atualizacao);

                    echo "<tr>";
                    echo "<td class='tac'>".$dados->status."</td>";
                    echo "<td class='tac'>".trim(utf8_decode($dados->descricao_status))."</td>";
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
                    echo "<td class='tac'>".trim($historico->data_atualizacao)."</td>";
                    echo "<td class='tac'>".trim($historico->hora_atualizacao)."</td>";
                    echo "<td class='tac'>".trim(utf8_decode($historico->observacao))."</td>";
                echo "</tr>";
            }
            if(!empty($objeto->ultimo_status)){
                echo "<tr>";
                echo "<td class='tac'>".$objeto->ultimo_status."</td>";
                echo "<td class='tac'>".trim(utf8_decode($objeto->descricao_status))."</td>";
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
<?php
        } else if ($result->acompanharPedido->cod_erro != "00") {
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
?>
</body>
</html>
