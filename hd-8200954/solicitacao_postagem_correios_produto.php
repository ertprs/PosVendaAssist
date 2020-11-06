<?php
    include_once 'dbconfig.php';
    include_once 'includes/dbconnect-inc.php';
    include_once 'autentica_usuario.php';
    include_once 'funcoes.php';

    if ($_environment == 'development'){
        ini_set('display_errors','on');
    }

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

    $sql_lgr_correios = "
        SELECT hd_chamado_postagem, tbl_hd_chamado_postagem.admin
        FROM tbl_hd_chamado_postagem
        WHERE fabrica = $login_fabrica
        AND hd_chamado = $hd_chamado
        AND admin IS NULL
        ORDER BY hd_chamado_postagem";
    $res_lgr_correios = pg_query($con, $sql_lgr_correios);
    if (pg_num_rows($res_lgr_correios) > 0){
        $disabled = "disabled";
    }else{
        $disabled = "";
    }

    if(isset($_POST['btn_acao']) AND $_POST['btn_acao'] == "submit"){
        $hd_chamado = $_POST['hd_chamado'];
        $tipo       = $_POST['tipo_postagem'];
        $sedex_pac  = $_POST['sedex_pac'];
        $obs        = $_POST['observacao_postagem'];
        $valor_nf   = 0;

        if (strlen(trim($hd_chamado)) == 0){
            $msg_erro["msg"][]    = "Erro ao solicitar postagem";
        }

        if (strlen(trim($tipo)) == 0){
            $msg_erro["msg"][]    = "Selecione o tipo de postagem";
            $msg_erro["campos"][] = "tipo_postagem";
        }

        if (strlen(trim($sedex_pac)) == 0){
            $msg_erro["msg"][]    = "Selecione o modo de envio";
            $msg_erro["campos"][] = "sedex_pac";
        }

        if(!count($msg_erro["msg"])){
            $validado = "true";
        }else{
            $validado   = "false";
        }
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
    <div class='container' style="width: 800px;">
        <?php
        if (count($msg_erro["msg"]) > 0) {
        ?>
            <br/>
            <div class="alert alert-error">
                <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
            </div>
        <?php
        }
        ?>

        <div class="row">
            <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
        </div>
        <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
            <input type="hidden" name="hd_chamado" id='hd_chamado' value="<?=$hd_chamado?>">
            <input type="hidden" name="codigo_posto" id='codigo_posto' value="<?=$codigo_posto?>">
            <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
            <br/>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("tipo_postagem", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='tipo_postagem'>Tipo Postagem</label>
                        <div class='controls controls-row'>
                            <div class='span4'>
                                <h5 class='asteristico'>*</h5>
                                <?php
                                    if($tipo == "C"){
                                        $select_c = "selected";
                                    }else if($tipo == "A"){
                                        $select_a = "selected";
                                    }
                                ?>
                                <select name="tipo_postagem" id="tipo_postagem" <?=$disabled?> >
                                    <option value=""></option>
                                    <option value="C" <?=$select_c?> >Coleta</option>
                                    <option value="A" <?=$select_a?> >Postagem</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("sedex_pac", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='familia'>Modo de envio</label>
                        <div class='controls controls-row'>
                            <div class='span4'>
                                <h5 class='asteristico'>*</h5>
                                <?php
                                    if($sedex_pac == "40517"){
                                        $select_s = "selected";
                                    }else if($sedex_pac == "04677"){
                                        $select_p = "selected";
                                    }
                                ?>
                                <select name="sedex_pac" id="sedex_pac" <?=$disabled?> >
                                    <option value=""></option>
                                    <option value="40517" <?=$select_s?> >SEDEX</option>
                                    <option value="04677" <?=$select_p?> >PAC</option>
                                </select>
                            </div>
                            <div class='span2'></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class='span2'></div>
                <div class="span8">
                    <div class='control-group'>
                        <label>Observação</label>
                        <div class="controls controls-row">
                            <input type="text" name="observacao_postagem" id="observacao_postagem" class='span11' value="<?=$obs?>" <?=$disabled?>>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>

            <p class='tac'><br/>
                <button class='btn' id="btn_acao" type="submit" value="submit" <?=$disabled?> name='btn_acao'>Solicitar Postagem</button>
            </p><br/>
        </form>
    </div>

<?php
if(isset($_POST['btn_acao']) AND $validado = "true" AND !count($msg_erro["msg"])){

    // $hd_chamado = $_POST['hd_chamado'];
    // $tipo       = $_POST['tipo_postagem'];
    // $sedex_pac  = $_POST['sedex_pac'];
    // $obs        = $_POST['observacao_postagem'];
    // $valor_nf   = 0;

    if(in_array($login_fabrica, array(162,164))){
        $xvalor_nf = str_replace('.','',$valor_nf);
        $xvalor_nf = str_replace(',','.',$xvalor_nf);

        $sqlatualiza = "UPDATE tbl_hd_chamado_extra SET valor_nf = '$xvalor_nf' WHERE hd_chamado = $hd_chamado";

        if(in_array($login_fabrica, array(162,164))){

            $checklist = $_POST['checklist']; //HD-3224475
            $numero_documento = $_POST['numero_documento']; //HD-3224475
        }

        $resatualiza = pg_query($sqlatualiza);

    }else{
        $checklist = "";
        $numero_documento = "";
    }

    if (in_array($login_fabrica,array(11,104,151,169,170))) {
        if ($_POST['sedex_pac'] == 40517) {
          $modo_envio = "SEDEX";
          $servico_adicional = '019';
        }else{
          $modo_envio = "PAC";
          $servico_adicional = '064';
        }
    }

    if (in_array($login_fabrica,array(81,114,122,123,125))){
        if ($_POST['sedex_pac'] == 40398) {
          $modo_envio = "SEDEX";
          $servico_adicional = '019';
        }else{
          $modo_envio = "PAC";
          $servico_adicional = '064';
        }
    }

    $sql = "SELECT tbl_hd_chamado_extra.array_campos_adicionais AS array_campos_adicionais,
                   tbl_posto.posto as posto_id,
                   tbl_fabrica.nome as fabrica,
                   tbl_produto.referencia || ' - ' ||tbl_produto.descricao AS produto,
                   tbl_posto.nome as remetente_nome,
                   tbl_posto_fabrica.contato_endereco as remetente_endereco,
                   tbl_posto_fabrica.contato_bairro as remetente_bairro,
                   tbl_posto_fabrica.contato_numero as remetente_numero,
                   tbl_posto_fabrica.contato_cidade as remetente_cidade,
                   tbl_posto_fabrica.contato_estado as remetente_estado,
                   tbl_posto_fabrica.contato_cep    as remetente_cep,
                   tbl_posto.email as remetente_email,
                   tbl_posto_fabrica.contato_complemento as remetente_complemento,
                   tbl_posto_fabrica.contato_fone_comercial as remetente_fone,
                   tbl_hd_chamado_extra.valor_nf,
                   case when tbl_hd_chamado_extra.numero_postagem notnull then tbl_hd_chamado_extra.numero_postagem else tbl_hd_chamado_postagem.numero_postagem end as numero_postagem,
                   tbl_hd_chamado_extra.nome as destinatario_nome,
                   tbl_hd_chamado_extra.endereco as destinatario_endereco,
                   tbl_hd_chamado_extra.numero as destinatario_numero,
                   tbl_hd_chamado_extra.complemento as destinatario_complemento,
                   tbl_hd_chamado_extra.bairro as destinatario_bairro,
                   tbl_hd_chamado_extra.cep as destinatario_cep,
                   tbl_cidade.nome as destinatario_cidade,
                   tbl_cidade.estado as destinatario_estado,
                   tbl_produto.descricao,
                   tbl_hd_chamado_postagem.admin as admin_postagem,
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
        $array_dados = pg_fetch_array($res);
    }

    $cond = "";
    if (in_array($login_fabrica, array(81,164))) {
        if (empty($marca) || $marca == 0 || $marca == null) {
            $cond = " AND marca is null";
        } else {
            $cond = " AND marca={$marca}";
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
    }else{

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

    if(!empty($array_dados['numero_postagem']) AND empty($array_dados['admin_postagem'])) {
        $array_request =  (object) array(
            'codAdministrativo' => $dados_acesso['codAdministrativo'],
            'tipoBusca'         => 'H',
            'numeroPedido'      => $array_dados['numero_postagem'],
            'tipoSolicitacao'   => $tipo
        );
        $function = 'acompanharPedido';
    } else {

        if ($obs == 'undefined') {
            $id_cliente_obs = $hd_chamado;
        }else{
            $id_cliente_obs = $hd_chamado;
        }

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
                    'nome'       => $array_dados['remetente_nome'],
                    'logradouro' => utf8_encode($array_dados['remetente_endereco']),
                    'numero'     => $array_dados['remetente_numero'],
                    'complemento'     => $array_dados['remetente_complemento'],
                    'bairro'     => utf8_encode($array_dados['remetente_bairro']),
                    'cidade'     => utf8_encode($array_dados['remetente_cidade']),
                    'uf'         => $array_dados['remetente_estado'],
                    'cep'        => $array_dados['remetente_cep'],
                ),
                'valor_declarado' => $array_dados['valor_nf'],
                'servico_adicional' => $servico_adicional,//correios obrigou a passar esse parametro
                'obj_col' => (object) array(
                    'item' => 1,
                    'id'   => $hd_chamado.";".utf8_encode($obs),
                    'desc' => utf8_encode($array_dados['descricao'])
                )
            )
        );

        if (in_array($login_fabrica, array(162,164))) {
            $array_request->coletas_solicitadas->remetente->identificacao = $array_dados['cpf'];
        }

        $dias = 30;

        if ($tipo == 'A') {
            $array_request->coletas_solicitadas->ag = $dias;
        }

        if(($login_fabrica == 151 AND $tipo == 'A') or $login_fabrica == 162){
            $array_request->coletas_solicitadas->ag = 15;
        }

        if(($login_fabrica == 164 AND $tipo == 'A')){
            $array_request->coletas_solicitadas->ag = 15;
        }

        $msg = "Postagem";

        if($login_fabrica == 151 AND $tipo == 'C'){
            $array_request->coletas_solicitadas->ag = date('d/m/Y', strtotime(date("Y-m-d"). ' + 15 days'));
            $msg = "Coleta";
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

    if ($function=='SolicitarPostagemReversa1') {
        $array_request =  $array_request;

        $return = array_walk_recursive($array_request,'validaArray');

        if (count($array_erro)>0) {
            foreach($array_erro as $value) {
                echo "<div class='alert alert-danger'>preecher o campo $value</div>";
            }
            die;
        }

    }

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
        }else if($login_fabrica == 104){
            $password = "ovd76635689";
        }else if($login_fabrica == 151){
            $password = "Monitora2016";
        }else if($login_fabrica == 153){
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

        $username = $dados_acesso["id_correio"];
        if(empty($password)) $password = $dados_acesso['password'];
    }

    try {
        try {
            $client = new SoapClient($url_novo_webservice, array("trace" => 1, "exception" => 0,'authorization' => 'Basic', 'login'   => $username, 'password' => $password));
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

                $solicitacao_ok = "true";
                foreach($comentario as  $key => $value) {
                    $string .= "<b>$key</b>: $value <br>";
                }
                /**
                *   Inserindo ou atualizando a postagem
                **/
                $sql = "BEGIN TRANSACTION";
                $res = pg_query($con, $sql);
                $msg_erro = pg_last_error($con);

                $sql_consulta = "SELECT hd_chamado
                                        FROM tbl_hd_chamado_postagem
                                        WHERE fabrica = $login_fabrica
                                        AND hd_chamado = $hd_chamado
                                        AND admin IS NULL";
                $res_consulta = pg_query($con,$sql_consulta);

                $sql_servico_correio =
                    "SELECT servico_correio
                       FROM tbl_servico_correio
                      WHERE tbl_servico_correio.codigo = '$sedex_pac'";
                $res_servico_correio = pg_query($con,$sql_servico_correio);

                if (pg_num_rows($res_servico_correio) > 0 ) {
                    $servico_correio = pg_fetch_result($res_servico_correio, 0, servico_correio);
                }
                if ($obs == 'undefined') {
                    $obs = "";
                }

                if (pg_num_rows($res_servico_correio) > 0) {

                    if (pg_num_rows($res_consulta) > 0) {
                        $sqlatualiza = " UPDATE tbl_hd_chamado_postagem
                                            SET numero_postagem = '$numero_postagem',
                                                tipo_postagem   = '$tipo',
                                                servico_correio = $servico_correio,
                                                obs = '$obs'
                                          WHERE hd_chamado = $hd_chamado
                                            AND fabrica    = $login_fabrica; ";
                    }else{
                        $sqlatualiza = " INSERT INTO tbl_hd_chamado_postagem (
                                                    hd_chamado,
                                                    fabrica,
                                                    numero_postagem,
                                                    tipo_postagem,
                                                    servico_correio,
                                                    obs
                                                ) VALUES(
                                                    $hd_chamado,
                                                    $login_fabrica,
                                                    '$numero_postagem',
                                                    '$tipo',
                                                    $servico_correio,
                                                    '$obs'
                                                ); ";
                    }
                    $resatualiza = pg_query($con, $sqlatualiza);

                    if(in_array($login_fabrica, array(162,164))){ //HD-3224475
                        $array_adicionais = json_decode($array_adicionais, true);
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

                $status_solicitacao = "<b>Solicitação realizada pelo Posto Autorizado. </b><br/>";
                $status_solicitacao .= "<strong>Tipo Solicitação:</strong> ".trim($tipo[1])."<br />";
                $status_solicitacao .= "<strong>Atendimento:</strong> ".trim($atendimento[1])."<br />";
                $status_solicitacao .= "<strong>Número Autorização:</strong> ".trim($numero_autorizacao[1])."<br />";

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

                if($login_fabrica <> 11){
                  $status_solicitacao .= "<strong>Número Etiqueta:</strong> ".trim($numero_etiqueta[1])."<br />";
                }
                $status_solicitacao .= "<strong>Modo de Envio:</strong> ".trim($modo_envio)."<br />";
                $status_solicitacao .= "<strong>Status:</strong> ".trim($status[1])."<br />";
                $status_solicitacao .= "<strong>Prazo de Postagem:</strong> ".trim($prazo_postagem[1])."<br />";
                $status_solicitacao .= "<strong>Data da Solicitação:</strong> ".trim($data_solicitacao[1])."<br />";
                $status_solicitacao .= "<strong>Horário da Solicitação:</strong> ".trim($horario_solicitacao[1])."<br />";

                $sql = "INSERT INTO tbl_hd_chamado_item(
                                hd_chamado   ,
                                data         ,
                                comentario   ,
                                interno
                        ) values (
                                $hd_chamado       ,
                                current_timestamp ,
                                '$status_solicitacao',
                                't'
                        )";
                $res = pg_query($sql);

                if (in_array($login_fabrica,array(104,162))){
                    list($dia,$mes,$ano) = explode("/",$data_solicitacao[1]);
                    $dataSoliticacaoGravar = trim($ano).'-'.trim($mes).'-'.trim($dia);

                    $local  = "Logradouro: ".utf8_encode($array_dados['remetente_endereco']);
                    $local .= " Número: ". $array_dados['remetente_numero'];
                    $local .= " Complemento: ".$array_dados['remetente_complemento'];
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
                    $resCorreios = pg_query($con,$sqlCorreios);
                }

                $sql_consumidor = "
                    SELECT tbl_hd_chamado_extra.celular,
                        tbl_hd_chamado_extra.nome,
                        tbl_hd_chamado_extra.email,
                        tbl_posto.nome AS posto_nome,
                        tbl_os.consumidor_nome,
                        tbl_os.os AS numero_os,
                        tbl_os_extra.obs_adicionais
                    FROM tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                    JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os AND tbl_os.fabrica = {$login_fabrica}
                    JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os.fabrica = {$login_fabrica}
                    JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
                    WHERE tbl_hd_chamado.hd_chamado = {$hd_chamado}
                ";
                $res_consumidor = pg_query($con, $sql_consumidor);

                if(pg_num_rows($res_consumidor) > 0){
                    $consumidor                 = pg_fetch_result($res_consumidor, 0, 'nome');
                    $email_consumidor           = pg_fetch_result($res_consumidor, 0, 'email');
                    $consumidor_celular         = pg_fetch_result($res_consumidor, 0, 'celular');
                    $posto_nome                 = pg_fetch_result($res_consumidor, 0, 'posto_nome');
                    $consumidor_nome            = pg_fetch_result($res_consumidor, 0, 'consumidor_nome');
                    $numero_os                  = pg_fetch_result($res_consumidor, 0, 'numero_os');
                    $array_campos_adicionais    = pg_fetch_result($res_consumidor, 0, 'array_campos_adicionais');

                    $array_campos_adicionais = json_decode($array_campos_adicionais,true);
                    $array_campos_adicionais['solicitacao_postagem_posto'] = trim($numero_autorizacao[1]);
                    $array_campos_adicionais = str_replace("\\", "\\\\", json_encode($array_campos_adicionais));
                    $codigo_rastreio = trim($numero_etiqueta[1]);

                }

                if (strlen($msg_erro) > 0  or strlen(pg_last_error()) > 0 ) {
                    $sql = "ROLLBACK TRANSACTION";
                    $res = pg_query($con, $sql);
                } else {

                    $sql_up = "UPDATE tbl_os_extra SET obs_adicionais = '$array_campos_adicionais', pac = '$codigo_rastreio' WHERE os = $numero_os ";
                    $res_up = pg_query($con, $sql_up);

                    if (strlen(trim($consumidor_celular)) > 0){
                        require_once 'class/sms/sms.class.php';
                        $sms = new SMS();

                        $nome_fab = $sms->nome_fabrica;
                        $texto_sms = "Sr(a). $consumidor_nome o posto autorizado: $posto_nome solicitou uma postagem para seu produto.<br/>
                            Código de rastreio: ".trim($numero_etiqueta[1]);
                        $sms->enviarMensagem($consumidor_celular, $numero_os, '', $texto_sms);
                    }

                    $sql = "COMMIT TRANSACTION";
                    $res = pg_query($con, $sql);
                }
                ?>

                <div class='container' style="width: 800px;">
                    <br/>
                    <div class="alert alert-success" style="width: 748px;">
                        <h4>Solicitação de <?=$msg?> solicitada com Sucesso</h4>
                    </div>

                    <table class='table table-striped table-bordered table-hover table-fixed' style="width: 800px;">
                        <thead>
                            <tr class="titulo_tabela">
                                <?php if($login_fabrica <> 11){ ?>
                                    <th colspan="12" >Status da solicitação</th>
                                <?php }else{ ?>
                                    <th colspan="9" >Status da solicitação</th>
                                <?php } ?>
                            </tr>
                            <tr class='titulo_coluna' >
                                <th>Tipo</th>
                                <th>Atendimento</th>
                                <th>Número Autorização</th>
                                <?php if(in_array($login_fabrica, array(162,164))){ ?>
                                <th>Check List</th>
                                <th>Número Documento</th>
                                <?php } ?>
                                <?php if($login_fabrica <> 11){ ?>
                                <th>Número Etiqueta</th>
                                <?php } ?>
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
                                if($login_fabrica <> 11){
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
                <?php
                if (in_array($login_fabrica,array(11,151))) {
                    if (trim(strlen($email_consumidor)) > 0) {

                        $remetente = ($login_fabrica == 11)
                            ? 'sac@lenoxx.com.br'
                            : (($login_fabrica == 151)
                                ? 'sac@mondialline.com.br'
                                : $externalEmail
                        );

                        $assunto = "Fábrica: ".$fabrica." - Informações sobre sua solicitação";
                        $mensagem = "Sr./Sra. $consumidor informações sobre sua solicitação de postagem.<br/>
                        Protocolo de Atendimento : ".$hd_chamado."<br />
                        Produto: ".$produto."<br />
                        Número da Autorização: ".$numero_autorizacao[1]."<br />
                        Modo de Envio: ".$modo_envio."<br />
                        Data da Solicitação: ".$data_solicitacao[1]."<br />
                        Prazo para Postagem: ".$prazo_postagem[1];

                        include_once '../class/communicator.class.php';
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
                $solicitacao_ok = "false";
            }
        } else {
            if ($result->acompanharPedido->coleta) {
                $historico = $result->acompanharPedido->coleta->historico;
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
                        <h4>Solicitação de Postagem já realizada</h4>
                    </div>
                    <table class='table table-striped table-bordered table-hover table-fixed' style="width: 800px;">
                        <thead>
                            <tr class="titulo_tabela">
                                <th colspan="9" >Status da solicitação</th>
                            </tr>
                            <tr class='titulo_coluna' >
                                <th>Status</th>
                                <th>Descricao do Status</th>
                                <th>Data da atualizacao</th>
                                <th>Horário da atualização</th>
                                <th>Autorização</th>
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
                                echo "<td class='tac'>".$historico->status."</td>";
                                echo "<td class='tac'>".trim(utf8_decode($historico->descricao_status))."</td>";
                                echo "<td class='tac'>".trim($historico->data_atualizacao)."</td>";
                                echo "<td class='tac'>".trim($historico->hora_atualizacao)."</td>";
                                echo "<td class='tac'>".$numero_autorizacao."</td>";
                                if(in_array($login_fabrica, array(162,164))){//HD-3224475
                                    echo "<td class='tac'>".$descricao_check."</td>";
                                    echo "<td class='tac'>".$n_documento."</td>";
                                }
                                echo "<td class='tac'>".$numero_etiqueta."</td>";
                                echo "<td class='tac'>".trim(utf8_decode($historico->observacao))."</td>";
                                echo "</tr>";
                            }
                            ?>
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
    } catch (Exception $e) {
        if ($_environment == 'development')
            var_dump($e);
        echo $msg_erro;
    }
}
?>
<script type="text/javascript">
$(function() {
    var solicitacao_ok = '<?=$solicitacao_ok?>';

    if(solicitacao_ok == 'true'){
        $("#tipo_postagem").prop("disabled", true);
        $("#sedex_pac").prop("disabled", true);
        $("#observacao_postagem").prop("disabled", true);
        $("#btn_acao").prop("disabled",true);
    }

});
</script>
<p class='tac'><br/>
    <button type='button' onclick="window.parent.retornoPostagem('<?=$solicitacao_ok?>','<?=$hd_chamado?>')" class="btn btn-primary">Fechar</button>
</p><br/>

</body>
</html>
