<?php
/*
*
* TELA UTILIZADA NO CALLCENTER E gerencia, QUANDO FOR NECESSÁRIO A ALTERAÇÃO PARA APENAS
* UM GRUPO DE USUÁRIOS USAR A VARIAVEL - $layout_menu
*
*/

$admin_privilegios = $admin_privilegios ? : "gerencia,call_center";
$layout_menu       = $layout_menu ? : "callcenter";
if($titulo_gerencia == "sim"){
	$title = 'SOLICITAÇÃO DE CHEQUE (GERÊNCIA)';
} else {
	$title = 'SOLICITAÇÃO DE CHEQUE';
}

$plugins = array("datepicker", "dataTable", "shadowbox", "maskedinput");

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once '../class/communicator.class.php';

if($login_fabrica == 1){    
    $sql_adm_supervisao_cheque = "SELECT admin, parametros_adicionais 
                                    FROM tbl_admin
                                    WHERE admin = {$login_admin}
                                    AND fabrica = {$login_fabrica}
                                   ";
	// AND parametros_adicionais::JSON->>'supervisao_cheque' = 't'
	//die(nl2br($sql_adm_supervisao_cheque));
    $res_adm_supervisao_cheque      = pg_query($con, $sql_adm_supervisao_cheque);
//    $contador_adm_supervisao_cheque = pg_num_rows($res_adm_supervisao_cheque);


	if(pg_num_rows($res_adm_supervisao_cheque)>0){
		$parametros_adicionais = pg_fetch_result($res_adm_supervisao_cheque, 0 , 'parametros_adicionais');
		$params_permissao = json_decode($parametros_adicionais , true);
        $contador_adm_supervisao_cheque = 0;
		if($params_permissao['supervisao_cheque'] == 't'){
			$contador_adm_supervisao_cheque = 1; 
		}
		if($params_permissao['solicitacao_cheque'] == 't'){
			$permissaoGerencia = true;
		}
	}
}

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    if ($_POST['action'] == 'aprova_solicitacoes' || $_POST['action'] == 'recusa_solicitacoes') {
        $acao_validacao = ($_POST['action'] == 'aprova_solicitacoes') ? 'aprovado' : 'recusado';
        if($layout_menu == 'gerencia'){            
            $acao = $acao_validacao;
            $acao_email = $acao_validacao;
        } else {            
            $acao = 'pendente';
            $acao_email = $acao_validacao;
        }
        
        $solicitacoes = implode(',', $_POST['solicitacoes']);
        $data_atual   = date('Y-m-d');
        $motivo = (isset($_POST['motivo']) && !empty($_POST['motivo'])) ? pg_escape_string($_POST['motivo']) : '';

        if(mb_detect_encoding($motivo, 'utf-8')){
            $motivo = utf8_decode($motivo);
        }

        //if($contador_adm_supervisao_cheque > 0 && $login_fabrica == 1){            
            $sql_solicitacao_cheque = "SELECT campos_adicionais from tbl_solicitacao_cheque WHERE solicitacao_cheque IN {$solicitacoes}";
            $res_solicitacao_cheque = pg_query($con, $sql_solicitacao_cheque);
            $campos_add_sc = json_decode(pg_fetch_result($res_solicitacao_cheque, 0, 'campos_adicionais'),true);

            if($acao_validacao == 'aprovado'){  
                if($layout_menu == 'gerencia'){            
                    $campos_add_sc['gerencia'] = 'aprovado';
                    //$campos_add_sc['supervisao_cheque'] = 'aprovado';
                } else {
                    $campos_add_sc['gerencia'] = 'pendente';
                    $campos_add_sc['supervisao_cheque'] = 'aprovado';
                }
                
                $campos_add_sc['supervisao_admin'] = $login_admin;
                $campos_add_sc = json_encode($campos_add_sc); 
                $solicitacoes_x = explode(",", $solicitacoes );                
                foreach ($solicitacoes_x as $valores) {                               
                    $sql_auditar = "UPDATE tbl_solicitacao_cheque SET campos_adicionais = campos_adicionais::jsonb || '{$campos_add_sc}' WHERE solicitacao_cheque = {$valores};";
                    //die(nl2br($sql_auditar));
                    $res_sql_auditar = pg_query($con, $sql_auditar);
                    if (strlen(pg_last_error()) > 0) {
                       exit(json_encode(array("error" => utf8_encode("Não foi possível aprovar o(s) registro(s) selecionado(s)"))));
                    }
                }
            } else {
                if($layout_menu == 'gerencia'){
                    $campos_add_sc['gerencia'] = 'recusado';
                    //$campos_add_sc['supervisao_cheque'] = 'recusado';                    
                } else {
                    $campos_add_sc['gerencia'] = 'pendente';
                    $campos_add_sc['supervisao_cheque'] = 'recusado';
                }

                $campos_add_sc['supervisao_admin'] = $login_admin;
                $campos_add_sc = json_encode($campos_add_sc);
                $solicitacoes_x = explode(",", $solicitacoes );
                foreach ($solicitacoes_x as $valores) {  
                    $sql_auditar = "UPDATE tbl_solicitacao_cheque SET campos_adicionais = campos_adicionais::jsonb || '{$campos_add_sc}' WHERE solicitacao_cheque = {$valores};";
                    //die(nl2br($sql_auditar));
                    $res_sql_auditar = pg_query($con, $sql_auditar);
                }
                if (strlen(pg_last_error()) > 0) {
                   exit(json_encode(array("error" => utf8_encode("Não foi possível aprovar o(s) registro(s) selecionado(s)"))));
                }
            }
        //}

        if(!empty($acao)){
            $sql = "INSERT INTO tbl_solicitacao_cheque_acao (
                        solicitacao_cheque,
                        admin_acao,
                        tipo_acao,
                        motivo,
                        data_acao
                    )
                    SELECT
                        tbl_solicitacao_cheque.solicitacao_cheque,
                        {$login_admin},
                        '{$acao}',
                        '{$motivo}',
                        '{$data_atual}'
                    FROM tbl_solicitacao_cheque WHERE tbl_solicitacao_cheque.solicitacao_cheque IN($solicitacoes)";

            pg_query($con, $sql);
        }

        if (strlen(pg_last_error()) > 0) {
            exit(json_encode(array("error" => utf8_encode("Não foi possível aprovar o(s) registro(s) selecionado(s)"))));
        }

        $mailTc = new TcComm($externalId);

        /* Pega o login do admin que realizou a ação */
        $sql = "SELECT
                    login
                FROM tbl_admin WHERE fabrica = {$login_fabrica}
                    AND admin = $login_admin";
        $res = pg_query($con, $sql);
        $login = pg_fetch_result($res, 0, 'login');

        /* MANDA EMAIL PARA O ADMIN QUE ABRIU A SOLICITAÇÃO SE O MESMO NÃO ESTIVER HABILITADO NO GRUPO PARA RECEBER AS NOTIFICAÇÕES DE SOLICITAÇÃO DE CHEQUE */
        $sql = "SELECT
                    tbl_admin.email,
                    tbl_solicitacao_cheque.solicitacao_cheque,
                    tbl_solicitacao_cheque.admin,
                    tbl_solicitacao_cheque.componente_solicitante,
                    tbl_solicitacao_cheque.valor_liquido,
                    tbl_solicitacao_cheque.vencimento,
                    tbl_solicitacao_cheque.numero_solicitacao
                FROM tbl_solicitacao_cheque
                JOIN tbl_admin ON tbl_admin.admin = tbl_solicitacao_cheque.admin AND tbl_admin.fabrica = {$login_fabrica} 
                WHERE tbl_solicitacao_cheque.solicitacao_cheque IN($solicitacoes) ORDER BY 1";
        $res = pg_query($con, $sql);

        $msg_email = 'Foi '.$acao_email.' uma solicitação de Cheque / Reembolso<br /><br />';
        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $componente_solicitante = pg_fetch_result($res, $i, 'componente_solicitante');
            $vencimento             = pg_fetch_result($res, $i, 'vencimento');
            $valor_liquido          = pg_fetch_result($res, $i, 'valor_liquido');
            $solicitacao_cheque     = pg_fetch_result($res, $i, 'solicitacao_cheque');
            $numero_solicitacao     = pg_fetch_result($res, $i, 'numero_solicitacao');
            $email_aux              = pg_fetch_result($res, $i, 'email');

            if (!empty($email) && $email !== $email_aux) {
                $mailTc->sendMail(
                    $email,
                    'Solicitação de Cheque - '.ucfirst($acao_email),
                    $msg_email
                );
                $msg_email = 'Foi '.$acao_email.' uma solicitação de Cheque / Reembolso<br /><br />';
            }
            $email = pg_fetch_result($res, $i, 'email');

            $msg_email .= '
                    <strong>Solicitação: '.$numero_solicitacao.'</strong><br />
                    <strong>Componente Solicitante:</strong> '.$componente_solicitante.'<br />
                    <strong>Vencimento:</strong> '.implode('/', array_reverse(explode('-', $vencimento))).'<br />
                    <strong>Valor Líquido:</strong> '.$valor_liquido.'<br />
                    <strong>'.ucfirst($acao_email).' pelo admin:</strong> '.$login.'<br />';

            if( !empty($motivo) ){
                $msg_email .= "<strong> Motivo: </strong> {$motivo} <br/>";
            }
        }
        /* ENVIA PARA O ULTIMO ADMIN PERCORRIDO NO FOR */
        $mailTc->sendMail(
            $email,
            'Solicitação de Cheque - '.ucfirst($acao_email),
            $msg_email
        );

        //ENVIA EMAIL PARA O GRUPO CADASTRADO PARA RECEBER AS NOTIFICAÇÕES DAS SOLICITAÇÕES DE CHEQUE
        $sql = "SELECT
                    email
                FROM tbl_admin WHERE fabrica = {$login_fabrica}
				AND ativo
                    AND JSON_FIELD('solicitacao_cheque', parametros_adicionais) = 't'";
        $res_admin = pg_query($con, $sql);
        if (pg_num_rows($res_admin) > 0) {
            //Pega informações das solicitações aprovadas/reprovadas
            $sql = "SELECT
                        tbl_solicitacao_cheque.componente_solicitante,
                        tbl_solicitacao_cheque.valor_liquido,
                        tbl_solicitacao_cheque.vencimento,
                        tbl_solicitacao_cheque.numero_solicitacao,
                        tbl_solicitacao_cheque.solicitacao_cheque
                    FROM tbl_solicitacao_cheque
                    WHERE tbl_solicitacao_cheque.solicitacao_cheque IN($solicitacoes)";

            $res = pg_query($con, $sql);
            $msg = (pg_num_rows($res) == 1) ? 'uma' : 'algumas';

            $msg_email = "";

            $contar = pg_num_rows($res);

            for ($i = 0; $i < $contar; $i++) {
                $componente_solicitante = pg_fetch_result($res, $i, 'componente_solicitante');
                $vencimento         = pg_fetch_result($res, $i, 'vencimento');
                $valor_liquido      = pg_fetch_result($res, $i, 'valor_liquido');
                $numero_solicitacao = pg_fetch_result($res, $i, 'numero_solicitacao');
                $solicitacao_cheque = pg_fetch_result($res, $i, 'solicitacao_cheque');

                if (empty($numero_solicitacao)) {
                    $numero_solicitacao = $solicitacao_cheque;
                }

                if (!empty($msg_email)) { $msg_email .= '<br /><br />'; }

                $msg_email .= '
                    <strong>Solicitação: '.$numero_solicitacao.'</strong><br />
                    <strong>Componente Solicitante:</strong> '.$componente_solicitante.'<br />
                    <strong>Vencimento:</strong> '.implode('/', array_reverse(explode('-', $vencimento))).'<br />
                    <strong>Valor Líquido:</strong> '.$valor_liquido.'<br />
                    <strong>'.ucfirst($acao_email).' pelo admin:</strong> '.$login;

                if( !empty($motivo) ){
                    $msg_email .= "<strong> Motivo: </strong> {$motivo} <br/>";
                }
            }

            $msg_email = 'Foi '.$acao_email.' '.$msg.' solicitação(ões) de Cheque / Reembolso<br /><br />'.$msg_email;
            for ($i = 0; $i < pg_num_rows($res_admin); $i++) {
                $email = pg_fetch_result($res_admin, $i, 'email');

                /*$mailTc->sendMail(
                    $email,
                    'Solicitação de Cheque - '.ucfirst($acao),
                    $msg_email
				);*/
            }
        }

        exit(json_encode(array("ok" => utf8_encode("Registro(s) aprovado(s) com sucesso"))));
    }elseif ($_POST['action'] == 'deleta_solicitacao') {
        $solicitacao_cheque = $_POST['solicitacao_cheque'];
        $motivo_excluir     = $_POST["motivo_excluir"];

        pg_query($con, 'BEGIN');

        $sql = "INSERT INTO tbl_solicitacao_cheque_acao (
                    solicitacao_cheque,
                    admin_acao,
                    tipo_acao,
                    data_acao,
                    motivo
                ) VALUES(
                    {$solicitacao_cheque},
                    {$login_admin},
                    'desativado',
                    current_date,
                    '$motivo_excluir'
                )";

        pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            pg_query($con, 'ROLLBACK');
            exit(json_encode(array("erro" => utf8_encode("erro"))));
        }
        pg_query($con, 'COMMIT');
        exit(json_encode(array("ok" => utf8_encode("ok"))));

    }elseif ($_POST['action'] == 'salvar_data_contas') {
        include_once dirname(__FILE__) . '/../class/AuditorLog.php';

        $solicitacao_cheque = $_POST['solicitacao_cheque'];
        $data_contas = $_POST['data_contas'];
        $data_contas_formatar = date_create_from_format('d/m/Y', $data_contas);
        $data_contas = date_format($data_contas_formatar, 'Y-m-d');
        
        pg_query($con, 'BEGIN');

        $sql_campos_add = "SELECT campos_adicionais FROM tbl_solicitacao_cheque WHERE solicitacao_cheque = $solicitacao_cheque AND fabrica = $login_fabrica";
        $res_campos_add = pg_query($con, $sql_campos_add);
        $campos_add = [];
        
        $AuditorLog = new AuditorLog;
        $sqlAuditor = " SELECT TO_CHAR(((campos_adicionais)::json->>'data_contas_pagar')::date, 'DD/MM/YYYY') AS data_envio_contas_pagar
                        FROM tbl_solicitacao_cheque
                        WHERE   solicitacao_cheque = $solicitacao_cheque
                        AND     fabrica = $login_fabrica";
        $AuditorLog->RetornaDadosSelect($sqlAuditor);

        if (pg_num_rows($res_campos_add) > 0) {
            $campos_add = json_decode(pg_fetch_result($res_campos_add, 0, 'campos_adicionais'),true);
            $campos_add["data_contas_pagar"] = $data_contas;
            $campos_add = json_encode($campos_add, JSON_UNESCAPED_UNICODE);
        } else{
            $campos_add["data_contas_pagar"] = $data_contas; 
            $campos_add = json_encode($campos_add);
        }

        $sql = "UPDATE tbl_solicitacao_cheque SET campos_adicionais = '$campos_add' WHERE solicitacao_cheque = $solicitacao_cheque AND fabrica = $login_fabrica";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            pg_query($con, 'ROLLBACK');
            exit(json_encode(array("erro" => utf8_encode("erro"))));
        }
        pg_query($con, 'COMMIT');
        $AuditorLog->RetornaDadosSelect()->EnviarLog('UPDATE', 'tbl_solicitacao_cheque',"$login_fabrica*$solicitacao_cheque");
        exit(json_encode(array("ok" => utf8_encode("ok"))));
    }
}

if (isset($_GET['anexos'])) {
    $tdocs = new TDocs($con, $login_fabrica, 'cheque');
    $ret = $tdocs->getDocumentsByRef($_GET['anexos']); 

    
    ?>


    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <title></title>
            <meta http-equiv="X-UA-Compatible" content="IE=8"/>
            <meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
            <meta http-equiv="Expires"       content="0">
            <meta http-equiv="Pragma"        content="no-cache, public">
            <meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
            <meta name      ="Author"        content="Telecontrol Networking Ltda">

            <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
            <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
            <link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
            <link type="text/css" rel="stylesheet" media="screen" href="../css/tooltips.css" />
            <link type="text/css" rel="stylesheet" media="screen" href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
            <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />
        </head>
    <?php
        if (count($ret->attachListInfo)) {
    ?>
        <div class="container-fluid" style="background-color: #ccc">
            <div id="listagem-imagem" class="row-fluid" style="padding-top: 5px;">
                <div class="span1"></div>
        <?php
                foreach ($ret->attachListInfo as $array_file) {
                    $anexo_imagem = $array_file['link'];

                    $ext = strtolower(preg_replace("/.+\./", "", basename($anexo_imagem)));
                    if ($ext == "pdf") {
                        $anexo_imagem = "imagens/pdf_icone.png";
                    }elseif (in_array($ext, array('doc', 'docx'))) {
                        $anexo_imagem = "imagens/docx_icone.png";
                    }elseif(!in_array($ext, array('jpg', 'jpeg', 'png'))  ){ 
			$anexo_imagem = "";
		    }

		    

        ?>
                    <div class="span2" style="background-color: white;">
                        <div class="thumbnail" style="text-align: center !important;">
                            <img style="height: 128px;" data-src='<?=$array_file['link']?>' src="<?=$anexo_imagem?>" alt='<?=$array_file['filename']?>'><br />
                            <a href="#" class="btn btn-primary btn-visualizar">Visualizar</a>
                        </div>
                    </div>
        <?php
                }
                for ($i = count($ret->attachListInfo); $i < 5; $i++) {
        ?>
                    <div class="span2" style="background-color: white;">
                        <div class="thumbnail" style="text-align: center !important;">
                            <div style="height: 178px; text-align: center;">
                                <div style="padding-top: 70px;font-size: 20px;text-align: center;color: #999;font-weight: bold;text-transform: uppercase;line-height: 25px;">Sem Anexo</div>
                            </div>
                        </div>
                    </div>
        <?php
                }
        ?>
            </div>
        </div>
        <div style="border: solid 5px #ccc; min-height: 400px;">
            <div class="container-fluid">
                <div id="visualiza-imagem" class="row-fluid" style="text-align: center;">
                    <iframe style="display: none; width: 100%; height: 500px;" src=""></iframe>
                    <img src="" style="display: none;">
                    <div style="padding-top: 170px;"><div class="alert alert-warning"><h4>Nenhuma imagem selecionada</h4></div></div>
                </div>
            </div>
        </div>
    <?php }else{ ?>
        <div style="align-items: center; display: flex; min-height: 100%; min-height: 100vh;">
            <div class="container-fluid">
                <div class="row">
                    <div class="span12">
                        <div class="alert alert-warning"><h4>Esta solicitação não possui anexos</h4></div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
        <script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
        <script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
        <script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
        <script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
        <script src="../bootstrap/js/bootstrap.js"></script>
        <script type="text/javascript">
            $(function(){
                $('.btn-visualizar').on('click', function(){
                    var img = $(this).parents('div').first().find('img').data('src');
                    $('#visualiza-imagem').show().find('div.alert').hide();

                    if ($(this).parents('div').first().find('img').attr('src') == 'imagens/pdf_icone.png' || $(this).parents('div').first().find('img').attr('src') == 'imagens/docx_icone.png' || $(this).parents('div').first().find('img').attr('src') == '' ) {
                        $('#visualiza-imagem').show().find('img').hide();
                        $('#visualiza-imagem').show().find('iframe').show().attr('src', img);
                    }else{
                        $('#visualiza-imagem').show().find('iframe').hide();
                        $('#visualiza-imagem').show().find('img').show().attr('src', img);
                    }
                });
            });
        </script>
    </html>
<?php
    exit;
}

if (isset($_GET['motivo_recusa'])) { ?>
    <!DOCTYPE html />
    <html>
        <head>
            <meta http-equiv="X-UA-Compatible" content="IE=8"/>
            <meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
            <meta http-equiv="Expires"       content="0">
            <meta http-equiv="Pragma"        content="no-cache, public">
            <meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
            <meta name      ="Author"        content="Telecontrol Networking Ltda">
            <meta http-equiv=pragma content=no-cache>
            <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
            <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
            <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
            <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
            <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

            <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
            <script src="bootstrap/js/bootstrap.js"></script>
            <script src="plugins/dataTable.js"></script>
            <script src="plugins/resize.js"></script>
            <script src="plugins/shadowbox_lupa/lupa.js"></script>

            <script>
                function submit_gravar(argument) {
                    var motivo = $('#interacao_motivo').val();
                    window.parent.pega_selecionados('recusa_solicitacoes',motivo);
                }
            </script>
        </head>

        <body>
            <div id="container_lupa" style="overflow-y:auto;">
                
                <div class="row-fluid">
                    <form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >
                        <div class='titulo_tabela '>Informar o Motivo da Recusa</div>

                        <br />

                        <div class="row-fluid" >
                            <div class="span1"></div>
                            <div class="span10" >
                                <div class="control-group" >
                                    <label class="control-label" for="interacao_motivo" >Motivo da Recusa</label>
                                    <div class="controls controls-row" >
                                        <textarea id="interacao_motivo" name="interacao_motivo" class="span12" ></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="span1"></div>
                        </div>
                        <div class="row-fluid">
                            <div class="span12 tac" >
                                <div class="control-group" >
                                    <label class="control-label" >&nbsp;</label>
                                    <div class="controls controls-row tac" >
                                        <button type="button" id="inter_grava" name="inter_grava" class="btn btn-success" data-loading-text="Gravando..." onclick=" submit_gravar(); window.parent.Shadowbox.close();" >Gravar</button>
                                        <button type="button" id="inter_cancela" name="inter_cancela" class="btn" data-loading-text="Cancelando..." onclick='window.parent.Shadowbox.close();' >Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>                
            </div>
        </body>
    </html>
    <?php
    exit;
}

include_once 'cabecalho_new.php';
include_once 'plugin_loader.php';

$admin_solicitacao_cheque = false;

$sql = "SELECT
            admin
        FROM tbl_admin
        WHERE fabrica = {$login_fabrica}
            AND JSON_FIELD('solicitacao_cheque', parametros_adicionais) = 't'
            AND admin = {$login_admin}";
$res_admin = pg_query($con, $sql);
if (pg_num_rows($res_admin) > 0) {
    $admin_solicitacao_cheque = true;
}

/*if ($admin_solicitacao_cheque !== true && $layout_menu == 'gerencia') {
    echo "<div class='alert alert-warning'><h4>Sem permissão para acessar esta opção</h4></div>";exit;
}*/

if ((!empty($btn_acao) && $btn_acao == 'pesquisar') || (isset($_GET['num_solicitacao']) && $_GET['num_solicitacao'] != '')) {
    $campos_erro = array();
    $where       = '';
    $msg_erro    = '';
    $fornecedor  = $_REQUEST["codigo_fornecedor"];

    // Remover espaços
    $numero_solicitacao = trim($numero_solicitacao);
    $data_inicial       = trim($data_inicial);
    $data_fim           = trim($data_final);
    $aprovacao          = trim($aprovacao);
    $fornecedor         = trim($fornecedor);
    $ano_pesquisa       = trim($ano_pesquisa);
    $admin_solicitacao  = trim($admin_solicitacao);
    $tipo               = trim($tipo);
    $cnpj_cpf           = trim($cnpj_cpf);

    if (isset($_GET['num_solicitacao'])) {
        $numero_solicitacao = $_GET['num_solicitacao'];
        $xsolicitacao_cheque = $_GET['xsolicitacao_cheque'];
        if (!empty($xsolicitacao_cheque)) {
            $btn_acao = 'pesquisar';
            $_POST['btn_acao'] = 'pesquisar';
            $sql_acao = "SELECT tipo_acao 
                         FROM tbl_solicitacao_cheque_acao 
                         WHERE solicitacao_cheque = {$xsolicitacao_cheque} 
                         ORDER BY data_input DESC LIMIT 1";
            //die(nl2br($sql_acao));
            $res_acao = pg_query($con, $sql_acao);
            if (pg_num_rows($res_acao) > 0) {
                if (pg_fetch_result($res_acao, 0, 'tipo_acao') == 'recusado') {
                    $aprovacao = 'r';
                } elseif (pg_fetch_result($res_acao, 0, 'tipo_acao') == 'aprovado') {
                    $aprovacao = 'a';
                } else {
                    $aprovacao = 'p';
                } 
            } else {
                $sql_campos = "  SELECT campos_adicionais 
                                 FROM tbl_solicitacao_cheque 
                                 WHERE solicitacao_cheque = {$xsolicitacao_cheque} 
                                 ORDER BY data_input DESC LIMIT 1";
                        //die(nl2br($sql_acao));
                $res_campos = pg_query($con, $sql_campos);
                if (pg_num_rows($res_campos) > 0) {
                    $camp_add = json_decode(pg_fetch_result($res_campos, 0, 'campos_adicionais'), true);

                    if (($camp_add['supervisao_cheque'] == 'pendente' || $camp_add['supervisao_cheque'] == '')) {
                        $aprovacao = 'ps';
                    } else if ($camp_add['supervisao_cheque'] == 'recusado') {
                        $aprovacao = 'rs';
                    } else if ($camp_add['gerencia'] == 'pendente') {
                        $aprovacao = 'p';
                    } else if ($camp_add['gerencia'] == 'recusado') {
                        $aprovacao = 'r';
                    } else {
                        $aprovacao = 'a';
                    }

                } else {
                    $aprovacao = 'p';
                }
            }
        }        
    }

    if (!empty($numero_solicitacao) && $numero_solicitacao !== '0') {
        $where .= " AND tbl_solicitacao_cheque.numero_solicitacao = $numero_solicitacao";
    }

    if (!empty($data_inicial) && !empty($data_final)) {
        $data_inicial_aux = implode('-', array_reverse(explode('/', $data_inicial)));
        $data_final_aux = implode('-', array_reverse(explode('/', $data_final)));

        if ($data_inicial_aux > $data_final_aux) {
            $campos_erro = array('data_inicial', 'data_final');
            $msg_erro    = 'Período inválida';
        }else{
            $where .= " AND tbl_solicitacao_cheque.data_input::date BETWEEN '$data_inicial_aux' AND '$data_final_aux'";
        }
    }else{
        if (!empty($data_inicial) || !empty($data_final)) {
            $campos_erro = array();
        }

        if (empty($data_inicial)) {
            $campos_erro[] = 'data_inicial';
        }
        if (empty($data_final)) {
            $campos_erro[] = 'data_final';
        }
		
		if(!in_array($aprovacao, array('a','r','p','ps','rs','ex')) and empty($ano_pesquisa) and empty($fornecedor)) {
			$msg_erro = "Informa o ano ou intervalo de data para pesquisa";
		}
        /* NÃO DEU ERRO DE DATA */
        if (count($campos_erro) !== 1 and !empty($ano_pesquisa)) {
            $where .= " AND tbl_solicitacao_cheque.data_input::date BETWEEN '$ano_pesquisa-01-01' AND '$ano_pesquisa-12-31'";
            $campos_erro = array();
        }
    }

    if (empty($where) and !in_array($aprovacao, array('a','r','p','ps','rs','ex')) and empty($fornecedor)) {
        $msg_erro = (!empty($msg_erro)) ? $msg_erro : 'Preencha os campos obrigatórios';
    }else{
        $campos_erro = array();

        if (!empty($fornecedor)) {
            $where .= " AND tbl_solicitacao_cheque.fornecedor = $fornecedor ";
        }

        if (!empty($admin_solicitacao)) {
            $where .= " AND tbl_solicitacao_cheque.admin = $admin_solicitacao";
        }

        if (!empty($cnpj_cpf)) {
            $where .= " AND (tbl_posto.cnpj = '$cnpj_cpf' OR tbl_fornecedor.cnpj = '$cnpj_cpf')";
        }
        if (!empty($tipo)) {
            $tipo   = explode('_', $tipo);
            $where .= " AND tbl_tipo_solicitacao.tipo_solicitacao = {$tipo[0]}";
        }

        $exists = '';
        if ($aprovacao == 'p' OR $aprovacao == 'ps' OR $aprovacao == 'rs') {
            $exists  =  ' NOT EXISTS ';
        }

        if($login_fabrica == 1){
            $data_corte = '2019-12-12 10:00:00';
            $campo_tipo_acao_status = 'tbl_solicitacao_cheque_acao.tipo_acao, ';
            $join_solicitacao_cheque_acao = ' LEFT JOIN lateral( select tipo_acao,  solicitacao_cheque_acao, data_input, motivo from tbl_solicitacao_cheque_acao where tbl_solicitacao_cheque_acao.solicitacao_cheque = tbl_solicitacao_cheque.solicitacao_cheque order by solicitacao_cheque_acao desc limit 1) tbl_solicitacao_cheque_acao on true ';
        }

        if($login_fabrica != 1){
            $where .= " AND {$exists} (SELECT tipo_acao FROM tbl_solicitacao_cheque_acao WHERE tbl_solicitacao_cheque_acao.solicitacao_cheque = tbl_solicitacao_cheque.solicitacao_cheque ORDER BY tbl_solicitacao_cheque_acao.data_input DESC LIMIT 1)";
        }        

        if ($aprovacao == 'ps' AND $login_fabrica == 1) {             
            $where .= " AND (
                                tbl_solicitacao_cheque.campos_adicionais IS NOT NULL 
                                AND (
                                        tbl_solicitacao_cheque.campos_adicionais::JSON->>'supervisao_cheque' = 'pendente' OR tbl_solicitacao_cheque.campos_adicionais::JSON->>'supervisao_cheque' is null)
                                    ) 
                            AND tbl_solicitacao_cheque_acao.tipo_acao IS NULL";
        }

        if ($aprovacao == 'rs' AND $login_fabrica == 1) {              
            $where .= " AND (
                                tbl_solicitacao_cheque.campos_adicionais IS NOT NULL 
                                AND tbl_solicitacao_cheque.campos_adicionais::JSON->>'supervisao_cheque' = 'recusado' 
                                AND tbl_solicitacao_cheque.campos_adicionais::JSON->>'gerencia' != 'recusado') ";
        }

        if ($aprovacao == 'p' AND $login_fabrica != 1) {
            $where .= " OR  (
                                SELECT tipo_acao FROM tbl_solicitacao_cheque_acao WHERE tbl_solicitacao_cheque_acao.solicitacao_cheque = tbl_solicitacao_cheque.solicitacao_cheque ORDER BY tbl_solicitacao_cheque_acao.data_input DESC LIMIT 1
                            ) 
                               IN ('alteracao_pos_aprovado')";
        } elseif ($aprovacao == 'p' AND $login_fabrica == 1) {            
            $where .= " AND (
                                (       
                                        tbl_solicitacao_cheque.campos_adicionais IS NOT NULL
                                        AND tbl_solicitacao_cheque.campos_adicionais::JSON->>'gerencia' = 'pendente'
                                        AND tbl_solicitacao_cheque.campos_adicionais::JSON->>'supervisao_cheque' = 'aprovado'
                                    
                                )
                            OR 
                                (
                                    (
                                        tbl_solicitacao_cheque.campos_adicionais IS NULL
                                        AND tbl_solicitacao_cheque_acao.tipo_acao = 'pendente'
                                        AND tbl_solicitacao_cheque.data_input < '{$data_corte}'
                                    )
                                    OR
                                    (
                                        tbl_solicitacao_cheque.campos_adicionais IS NULL
                                        AND tbl_solicitacao_cheque_acao.tipo_acao IS NULL
                                        AND tbl_solicitacao_cheque.data_input < '{$data_corte}'
                                    )
                                ) 
                            ) ";                        
        }

        if ($aprovacao == 'a' AND $login_fabrica != 1) {
            $where .= " IN('aprovado')";
        }elseif($aprovacao == 'a' AND $login_fabrica == 1) {            
            $where .= " AND (tbl_solicitacao_cheque.campos_adicionais::JSON->>'gerencia' = 'aprovado'
                        OR  (tbl_solicitacao_cheque_acao.tipo_acao = 'aprovado')) ";
        }elseif($aprovacao == 'r' AND $login_fabrica != 1) {
            $motivoField = '(SELECT motivo FROM tbl_solicitacao_cheque_acao WHERE tbl_solicitacao_cheque_acao.solicitacao_cheque = tbl_solicitacao_cheque.solicitacao_cheque ORDER BY tbl_solicitacao_cheque_acao.data_input DESC LIMIT 1 ) as motivo,';
            $where .= " IN('recusado')";
        }elseif($aprovacao == 'r' AND $login_fabrica == 1) {            
            $where .= " AND (tbl_solicitacao_cheque.campos_adicionais::JSON->>'gerencia' = 'recusado' OR        tbl_solicitacao_cheque.campos_adicionais::JSON->>'gerencia' is null )
                and (SELECT tipo_acao FROM tbl_solicitacao_cheque_acao WHERE tbl_solicitacao_cheque_acao.solicitacao_cheque = tbl_solicitacao_cheque.solicitacao_cheque ORDER BY tbl_solicitacao_cheque_acao.data_input DESC LIMIT 1) IN('recusado')";
        }else if ($aprovacao == 'ex') {
            $where .= " AND tbl_solicitacao_cheque_acao.tipo_acao = 'desativado' ";
            $motivoField = " tbl_solicitacao_cheque_acao.motivo AS motivo_exclusao, TO_CHAR(tbl_solicitacao_cheque_acao.data_input, 'DD/MM/YYYY HH24:MI') AS exclusao_data, ";
        }
	}
}

?>

<style type="text/css">
    .modal {
        width: 360px !important;
        margin-left: -160px !important;
    }
</style>

<div id="alertas_tela">
    <?php if (!empty($msg_erro)) { ?>
    <div class="alert alert-danger"><h4><?=$msg_erro;?></h4></div>
    <?php } ?>
    <?php if (!empty($msg_ok)) { ?>
    <div class="alert alert-success"><h4><?=$msg_ok;?></h4></div>
    <?php } ?>
</div>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios</b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_familia" method="post" action="<?=$_SERVER['PHP_SELF']?>">
    <div class="titulo_tabela"><?='Parâmetros de Pesquisa' ?></div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("fornecedor", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_fornecedor'>Código Fornecedor</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="codigo_fornecedor" name="codigo_fornecedor" class='span12' maxlength="20" value="<? echo $codigo_fornecedor ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="fornecedor" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("fornecedor", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='nome_fornecedor'>Nome Fornecedor</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="nome_fornecedor" name="nome_fornecedor" class='span12' value="<? echo $nome_fornecedor ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="fornecedor" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='numero_solicitacao'>Ano</label>
                <div class='controls controls-row'>
                    <select name="ano_pesquisa">
						<option></option>
                        <?php
                        $date = date('Y');
                        for ($i = 0; $i < 12; $i++) {
                            if ($date == $ano_pesquisa) {
                                $selected = 'selected';
                            } else {
                                $selected = '';
                            }
                            echo "<option value='$date' {$selected} >$date</option>";
                            $date -= 1;
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('numero_solicitacao', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='numero_solicitacao'>Número Solicitação</label>
                <div class='controls controls-row'>
                    <input type="number" name="numero_solicitacao" value="<?=$numero_solicitacao?>">
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('data_inicial', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <input type="text" name="data_inicial" id="data_inicial" autocomplete="off" value="<?=$data_inicial?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array('data_final', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <input type="text" name="data_final" id="data_final" autocomplete="off" value="<?=$data_final?>">
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='admin_solicitacao'>Usuário</label>
                <div class='controls controls-row'>
                    <select name="admin_solicitacao" id="admin_solicitacao">
                        <option value=""></option>
                        <?php
                        $sql = "SELECT
                                    admin,
                                    login
                                FROM tbl_admin WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY 2";

                        $res = pg_query($con, $sql);
                        for ($i = 0; $i < pg_num_rows($res); $i++) { 
                            $admin = pg_fetch_result($res, $i, 'admin');
                            $login = pg_fetch_result($res, $i, 'login');
                            $selected = ($admin == $admin_solicitacao) ? 'selected' : '';

                            echo "<option value='$admin' {$selected}>$login</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <?php if ($layout_menu == 'callcenter') { ?>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='cnpj_cpf'>CNPJ/CPF</label>
                <div class='controls controls-row'>
                    <input type="text" name="cnpj_cpf" id="cnpj_cpf" value="<?=$cnpj_cpf?>">
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='tipo'>Tipo de Solicitação</label>
                <div class='controls controls-row'>
                    <select name="tipo">
                        <option value=""></option>
                        <?php
                        $sql = "SELECT
                                    tipo_solicitacao,
                                    descricao,
                                    informacoes_adicionais
                                FROM tbl_tipo_solicitacao WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY 2;";
                        $res = pg_query($con, $sql);
                        for ($i = 0; $i < pg_num_rows($res); $i++) {
                            $tipo_solicitacao       = pg_fetch_result($res, $i, 'tipo_solicitacao');
                            $informacoes_adicionais = pg_fetch_result($res, $i, 'informacoes_adicionais');
                            $descricao              = pg_fetch_result($res, $i, 'descricao');
                            $selected               = ($tipo[0] == $tipo_solicitacao) ? 'selected' : '';

                            echo "<option value='{$tipo_solicitacao}_{$informacoes_adicionais}' {$selected}>{$descricao}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <?php
    if (empty($aprovacao)) {
        $aprovacao = 'a';
        if ($layout_menu == 'gerencia') {
            $aprovacao = 'p';
        }
    }

    ?>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span6">
            <div class="control-group">
                <label class="control-label" for='aprovacao'>Aprovação</label>
                <div class='controls controls-row'>
                    <? if($login_fabrica == 1 AND $aprovacao_gerencia != "nao") {
                     ?>                        
                        <input type="radio" name="aprovacao" id="ps" value="ps" <?=($aprovacao == 'ps' OR empty($btn_acao)) ? 'checked' : ''?>> Pendente Supervisão
                        <input type="radio" name="aprovacao" value="rs" <?=($aprovacao == 'rs') ? 'checked' : ''?>> Recusado Supervisão
                        <br>
                    <? } ?>
                    <input type="radio" name="aprovacao" value="p" <?=($aprovacao == 'p') ? 'checked' : ''?>> <? echo ($login_fabrica == 1) ? 'Pendente Gerência' : 'Pendentes' ?>
                    <input type="radio" name="aprovacao" value="r" <?=($aprovacao == 'r') ? 'checked' : ''?>> <? echo ($login_fabrica == 1) ? 'Recusado Gerência' : 'Recusadas' ?>
                    <input type="radio" name="aprovacao" value="a" <?=($aprovacao == 'a' AND !empty($btn_acao)) ? 'checked' : ''?>> <? echo ($login_fabrica == 1) ? 'Aprovado Gerência' : 'Aprovadas' ?><br />
                    <input type="radio" name="aprovacao" value="ex" <?=($aprovacao == 'ex') ? 'checked' : ''?>> Excluídos
                </div>
            </div>
        </div>
        <div class="span4"></div>
    </div><br>
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4 tac">
            <button class="btn" name="btn_acao" value="pesquisar">Pesquisar</button>
			<? if($layout_menu != 'gerencia') { ?>
				<button class="btn btn-success" type="button" id="btn_novo">Nova Solicitação</button>
			<? } ?>
        </div>
    </div>
</form>

<div class="modal hide fade" id="modal_data" style="padding: 10px; border-radius: 22px;">
    <div class="modal-header tac" style="background-color: #596d9b; color: #FFFFFF; border-radius: 7px;">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h5 class="modal-title">Data de Envio de Contas a Pagar</h5>
    </div>  
    <div class="modal-header tac msg_erro_modal hide" style="background-color: #FF0000; color: #FFFFFF; border-radius: 7px; margin-top: 10px;">
        <h5 class="modal-title">Informar uma Data Válida</h5>
    </div>
    <div class="modal-header tac msg_erro_modal_salvar hide" style="background-color: #FF0000; color: #FFFFFF; border-radius: 7px; margin-top: 10px;">
        <h5 class="modal-title">Erro na Inserção da Data</h5>
    </div>
    <div class="modal-header tac msg_sucess_modal hide" style="background-color: #5bb75b; color: #FFFFFF; border-radius: 7px; margin-top: 10px;">
        <h5 class="modal-title">Data Salva com Sucesso</h5>
    </div>
    <div class="modal-body tac">
        <input type="text" name="data_contas_pagar_modal" id="data_contas_pagar_modal" autocomplete="off" value="" style="width: 40%;">
        <input type="hidden" name="solicitacao_modal" id="solicitacao_modal"  value="" />
    </div>
    <div class="modal-footer tac">
        <a href="#" class="btn fechar_modal">Fechar</a>
        <a href="#" class="btn btn-success salvar_modal">Salvar</a>
    </div>
</div>

<div class="modal hide fade" id="modal_excluir" style="padding: 10px; border-radius: 22px;">
    <div class="modal-header tac" style="background-color: #596d9b; color: #FFFFFF; border-radius: 7px;">
        <h5 class="modal-title">Motivo Exclusão</h5>
    </div>  
    <div class="modal-header tac msg_erro_modal_excluir hide" style="background-color: #FF0000; color: #FFFFFF; border-radius: 7px; margin-top: 10px;">
        <h5 class="modal-title">Preencha o Campo Motivo</h5>
    </div>
    <div class="modal-header tac msg_erro_modal_salvar_excluir hide" style="background-color: #FF0000; color: #FFFFFF; border-radius: 7px; margin-top: 10px;">
        <h5 class="modal-title">Erro na Inserção do Motivo</h5>
    </div>
    <div class="modal-header tac msg_sucess_modal_excluir hide" style="background-color: #5bb75b; color: #FFFFFF; border-radius: 7px; margin-top: 10px;">
        <h5 class="modal-title">Motivo Salvo com Sucesso</h5>
    </div>
    <div class="modal-body tac">
        <input type="text" name="motivo_excluir" id="motivo_excluir" autocomplete="off" value="" style="width: 80%;">
        <input type="hidden" name="solicitacao_modal_excluir" id="solicitacao_modal_excluir"  value="" />
    </div>
    <div class="modal-footer tac">
        <a href="#" class="btn fechar_modal_excluir">Fechar</a>
        <a href="#" class="btn btn-success salvar_modal_excluir">Salvar</a>
    </div>
</div>

<?php

if (empty($where)) {
    $where = " AND (SELECT tipo_acao FROM tbl_solicitacao_cheque_acao WHERE tbl_solicitacao_cheque_acao.solicitacao_cheque = tbl_solicitacao_cheque.solicitacao_cheque ORDER BY tbl_solicitacao_cheque_acao.data_input DESC LIMIT 1) IN('aprovado') AND tbl_solicitacao_cheque.admin = $login_admin";
    $primeira_pesquisa = true;
}

if ($layout_menu == 'gerencia' && !isset($_POST['btn_acao'])) {
    $where = '';
}

if (!empty($where) && !count($campos_erro) && isset($_POST['btn_acao'])) {
    pg_prepare($con, 'consulta_reincidentes', "SELECT tbl_solicitacao_cheque.numero_solicitacao,solicitacao_cheque FROM tbl_solicitacao_cheque WHERE  (fornecedor = $1 OR posto = $2) AND solicitacao_cheque <> $3 AND (SELECT tipo_acao FROM tbl_solicitacao_cheque_acao WHERE tbl_solicitacao_cheque_acao.solicitacao_cheque = tbl_solicitacao_cheque.solicitacao_cheque ORDER BY tbl_solicitacao_cheque_acao.data_input DESC LIMIT 1) NOT IN('desativado') and fabrica = $4");
    $sql = "SELECT DISTINCT ON (tbl_solicitacao_cheque.solicitacao_cheque)
                tbl_solicitacao_cheque.solicitacao_cheque,
                tbl_solicitacao_cheque.componente_solicitante,
                tbl_admin.login,
                tbl_tipo_solicitacao.descricao,
                tbl_solicitacao_cheque.valor_liquido,
                tbl_solicitacao_cheque.data_input,
                tbl_solicitacao_cheque.vencimento,
                tbl_solicitacao_cheque.fornecedor,
                tbl_solicitacao_cheque.historico,
                tbl_solicitacao_cheque.motivo_devolucao,
                tbl_solicitacao_cheque.campos_adicionais, 
                {$motivoField}
                tbl_solicitacao_cheque.posto,
                {$campo_tipo_acao_status}
                tbl_solicitacao_cheque.numero_solicitacao AS numero,
                ( 
                    SELECT tipo_acao
                    FROM  tbl_solicitacao_cheque_acao
                    WHERE 
                    tbl_solicitacao_cheque_acao.solicitacao_cheque = tbl_solicitacao_cheque.solicitacao_cheque
                    AND tbl_solicitacao_cheque_acao.tipo_acao = 'alteracao_pos_aprovado'
                    ORDER BY tbl_solicitacao_cheque_acao.solicitacao_cheque 
                    DESC
                    LIMIT 1
                ) AS alterado_pos_aprovado,
                ( 
                    SELECT motivo
                    FROM  tbl_solicitacao_cheque_acao
                    WHERE 
                    tbl_solicitacao_cheque_acao.solicitacao_cheque = tbl_solicitacao_cheque.solicitacao_cheque
                    ORDER BY tbl_solicitacao_cheque_acao.solicitacao_cheque 
                    DESC
                    LIMIT 1
                ) AS motivo_alteracao
            FROM tbl_solicitacao_cheque
                JOIN tbl_admin ON(tbl_admin.admin = tbl_solicitacao_cheque.admin AND tbl_admin.fabrica = {$login_fabrica})
                JOIN tbl_tipo_solicitacao ON(tbl_tipo_solicitacao.tipo_solicitacao = tbl_solicitacao_cheque.tipo_solicitacao AND tbl_tipo_solicitacao.fabrica = {$login_fabrica})
                LEFT JOIN tbl_posto USING(posto)
                LEFT JOIN tbl_fornecedor USING(fornecedor)
                {$join_solicitacao_cheque_acao}
            WHERE tbl_solicitacao_cheque.fabrica = {$login_fabrica} {$where} ORDER BY solicitacao_cheque DESC";

    //echo nl2br($sql); die;

    $res = pg_query($con, $sql);
    //echo pg_last_error($con); 
    $num_rows = pg_num_rows($res);
    if ($num_rows > 0) {

    ?>
        </div>
        <div style="padding: 3px !important;">
        <table>
            <tr>
                <td style="width: 30px;border: 1px solid black;">
                    <div class="status_checkpoint" style="background-color: #FF8282;">&nbsp;</div>
                </td>
                <td align='left'>
                    <b>
                        &nbsp; Reincidente
                    </b>
                </td>
            </tr>
            <tr><td></td></tr>
            <tr>
                <td style="width: 30px;border: 1px solid black;">
                    <div class="status_checkpoint" style="background-color: #FFD700;">&nbsp;</div>
                </td>
                <td align='left'>
                    <b>
                        &nbsp; Alterado após aprovação
                    </b>
                </td>
            </tr>
        </table>
        <br />        
        <table id="resultado_tipo_solicitacao" class='table table-bordered table-hover table-large table-fixed'>
            <thead>
                <tr class='titulo_coluna'>
                    <?php  if ($aprovacao == 'a') {
                                $label_data_envio = "Data Envio Contas a Pagar";
                                $data_envio_coluna = "<th>Data Envio Contas a Pagar</th>";
                            }    

                            if($login_fabrica == 1){
                                if($layout_menu == 'gerencia' AND ($aprovacao == 'p' OR $aprovacao == 'a' OR $aprovacao == 'r')){
                                    echo "<th><input type='checkbox' name='check_all'></th>";
                                    echo '<th>Status do Cheque</th>';
                                    $status_cheque_cabecalho = "Status do Cheque";
                                }
                                if($contador_adm_supervisao_cheque > 0 AND ($aprovacao == 'ps' OR $aprovacao == 'rs') AND $layout_menu != 'gerencia'){
                                    echo "<th><input type='checkbox' name='check_all'></th>";
                                }
                                if(($contador_adm_supervisao_cheque > 0 AND $layout_menu != 'gerencia') || ($layout_menu == 'gerencia' && $aprovacao == 'ex')){
                                    echo "<th>Status do Cheque</th>";
                                    $status_cheque_cabecalho = "Status do Cheque";
                                }
                            }

                            $data_exclusao_coluna   = '';
                            $motivo_exclusao_coluna = '';
                            $data_exclusao_label    = '';
                            $motivo_exclusao_label  = '';
                            if ($aprovacao == 'ex') {
                                $data_exclusao_coluna   = '<th>Data Exclusão</th>';
                                $motivo_exclusao_coluna = '<th>Motivo Exclusão</th>';
                                $data_exclusao_label    = 'Data Exclusão ';
                                $motivo_exclusao_label  = 'Motivo Exclusão ';
                            }
                    ?>
                    <th>Solicitação de Cheque</th>
                    <th>Componente Solicitante</th>
                    <th>Emissão</th>
                    <th>Vencimento</th>
                    <?=$data_exclusao_coluna?>
                    <?=$motivo_exclusao_coluna?>
                    <th>Código Fornecedor</th>
                    <th>Nome Fornecedor</th>
                    <th>C.P.F/C.G.C</th>
                    <th>Endereço</th>
                    <th>Número</th>
                    <th>Bairro</th>
                    <th>Cidade</th>
                    <th>Estado</th>
                    <th>Valor Líquido</th>
                    <th>Número Documento 1</th>
                    <th>Valor Bruto 1</th>
                    <th>Número Documento 2</th>
                    <th>Valor Bruto 2</th>
                    <th>Observação</th>
                    <th>Histórico</th>
                    <th>Admin</th>
                    <th>Tipo da Solicitação</th>
                    <th>Reincidente</th>
                    <th>Justificativa</th>
                    <th>Motivo Alteração</th>
                    <?=$data_envio_coluna?>
                    <th>Data Aprovação</th>
                    <th>Ações</th>
                    <th>Anexo</th>
                </tr>
            </thead>
            <tbody>
    <?php
        $file_csv = 'xls/solicitacao_cheque_'.date('Ymd').'.csv';
        $fp = fopen($file_csv,"w+");

        if ($aprovacao == 'ex') {
            $head = array(
                $status_cheque_cabecalho,
                "Solicitação de Cheque",
                "Componente Solicitante",
                "Emissão",
                "Vencimento",
                $data_exclusao_label,
                $motivo_exclusao_label,
                "Código Fornecedor",
                "Nome Fornecedor",
                "C.P.F/C.G.C",
                "Endereço",
                "Número",
                "Bairro",
                "Cidade",
                "Estado",
                "Valor Líquido",
                "Número Documento 1",
                "Valor Bruto 1",
                "Número Documento 2",
                "Valor Bruto 2",
                "Observação",
                "Histórico",
                "Admin",
                "Tipo da Solicitação",
                "Reincidente",
                "Justificativa",
                "Motivo Alteração",
                $label_data_envio,
                "Data Aprovação",
                "Motivo Devolução",
                "Ref. Produto",
                "Desc. Produto",
                "Peças",
                "Descrição Peças",
                "Status Peças",
                "Protocolo",
                "Número B.O",
                "Número Processo",
                "Justificativa",
                "Número Chamado",
                "Produto 30 Dias",
                "Mídia",
                "ID Reclame Aqui",
                "OS 1",
                "OS 2\n"
            );
        } else {
            $head = array(
                $status_cheque_cabecalho,
                "Solicitação de Cheque",
                "Componente Solicitante",
                "Emissão",
                "Vencimento",
                "Código Fornecedor",
                "Nome Fornecedor",
                "C.P.F/C.G.C",
                "Endereço",
                "Número",
                "Bairro",
                "Cidade",
                "Estado",
                "Valor Líquido",
                "Número Documento 1",
                "Valor Bruto 1",
                "Número Documento 2",
                "Valor Bruto 2",
                "Observação",
                "Histórico",
                "Admin",
                "Tipo da Solicitação",
                "Reincidente",
                "Justificativa",
                "Motivo Alteração",
                $label_data_envio,
                "Data Aprovação",
                "Motivo Devolução",
                "Ref. Produto",
                "Desc. Produto",
                "Peças",
                "Descrição Peças",
                "Status Peças",
                "Protocolo",
                "Número B.O",
                "Número Processo",
                "Justificativa",
                "Número Chamado",
                "Produto 30 Dias",
                "Mídia",
                "ID Reclame Aqui",
                "OS 1",
                "OS 2\n"
            );
        }
    
        fwrite($fp, (strtoupper(implode(";", $head))));

        $quebra_linha = array("\n","\r",'"',"'");

        for ($i = 0; $i < $num_rows; $i++) {
            unset($pecas_explode);
            unset($array_pecas);
            unset($peca_explode);
            unset($peca_csv);

            $peca_explode = '';
            $admin                = pg_fetch_result($res, $i, 'login');
            $descricao            = pg_fetch_result($res, $i, 'descricao');
            $valor_liquido        = priceFormat(pg_fetch_result($res, $i, 'valor_liquido'));
            $data_input           = pg_fetch_result($res, $i, 'data_input');
            $data_input_raiz      = $data_input;

            $xdata_exclusao       = '';
            $xmotivo_exclusao     = '';
            $xdata_exclusao_c     = '';
            $xmotivo_exclusao_c   = '';
            if ($aprovacao == 'ex') {
                $xdata_exclusao     = pg_fetch_result($res, $i, 'exclusao_data');
                $xmotivo_exclusao   = utf8_decode(pg_fetch_result($res, $i, 'motivo_exclusao'));
                $xdata_exclusao_c   = "<td class='tac'>$xdata_exclusao</td>";
                $xmotivo_exclusao_c = "<td class='tac'>$xmotivo_exclusao</td>";
            }

            $solicitacao_cheque   = pg_fetch_result($res, $i, 'solicitacao_cheque');
            $fornecedor           = pg_fetch_result($res, $i, 'fornecedor');
            $posto                = pg_fetch_result($res, $i, 'posto');
            $numero               = pg_fetch_result($res, $i, 'numero');
            $n_solicitacao        = pg_fetch_result($res, $i, 'numero');
            if($login_fabrica == 1){
                $tipo_acao_status     = pg_fetch_result($res, $i, 'tipo_acao');
            }            
            $tipo_acao            = pg_fetch_result($res, $i, 'alterado_pos_aprovado');
            $motivo_devolucao     = pg_fetch_result($res, $i, 'motivo_devolucao');

            /*HD - 4412371*/
            $componente_solicitante = pg_fetch_result($res, $i, 'componente_solicitante');
            $vencimento             = pg_fetch_result($res, $i, 'vencimento');
            $historico              = pg_fetch_result($res, $i, 'historico');

            $campos_adicionais      = json_decode(pg_fetch_result($res, $i, 'campos_adicionais'), true);

            $data_contas_pagar = $campos_adicionais["data_contas_pagar"];

            if($login_fabrica == 1){
            	$supervisao_cheque_gerencia = $campos_adicionais["gerencia"];
                $supervisao_cheque_pendente = $campos_adicionais["supervisao_cheque"];
            }

            if (!empty($data_contas_pagar)) {
                $data_contas_pagar_formatar = new DateTime($data_contas_pagar);
                $data_contas_pagar = $data_contas_pagar_formatar->format('d/m/Y');
            }

            $array_pecas = preg_split('/\n|\r\n?/', $campos_adicionais['pecas']);
            $array_pecas = array_filter($array_pecas); 

            foreach ($array_pecas as $p => $pc) {
                $peca_explode = explode('|', $pc);
                $peca_csv[$p][] = $peca_explode['0']; 
                $peca_csv[$p][] = $peca_explode['1'];  
                $peca_csv[$p][] = $peca_explode['2'];  
            }

            $count_for = count($peca_csv);

            for ($p=0; $p < $count_for; $p++) { 
                for ($r=0; $r <= 2; $r++) {
                    if ($r == 0) {
                        $pecas_explode['referencia'] .= $peca_csv[$p][0]."\n";
                    }
                    
                    if ($r == 1) {
                       $pecas_explode['descricao'] .= $peca_csv[$p][1]."\n";   
                    }

                    if ($r == 2) {
                       $pecas_explode['status'] .= $peca_csv[$p][2]."\n";   
                    } 
                }
            }            

            $adicionais_campos = "";
            $xproduto_ref  = "";
            $xproduto_desc = "";

            foreach($campos_adicionais as $campo => $valor){

                if(empty($valor)){
                    continue;
                }

                if($campo == 'produto' and !empty($valor)){
                    $sqlProduto = "SELECT * from tbl_produto where produto = $valor and fabrica_i = $login_fabrica ";
                    $resProduto  = pg_query($con, $sqlProduto);
                    if(pg_num_rows($resProduto)>0){
                        $referencia_produto = pg_fetch_result($resProduto, 0, referencia);
                        $descricao_produto  = pg_fetch_result($resProduto, 0, descricao);
                        $adicionais_campos .=  ucwords($campo).": $referencia_produto - $descricao_produto \n";
                        $xproduto_ref   = $referencia_produto;
                        $xproduto_desc  = $descricao_produto;
                    }
                    continue;
                }

                $adicionais_campos .=  ucwords($campo).": $valor \n";
            }

            if (isset($campos_adicionais['midia'])) {
                
                    unset($xcampo_midia);
                
                    switch ($campos_adicionais['midia']) {
                        case 'facebook':
                            $xcampo_midia = 'Facebook';
                        break;

                        case 'fale_conosco':
                            $xcampo_midia = 'Fale Conosco';
                        break;

                        case 'reclame_aqui':
                            $xcampo_midia = 'Reclame Aqui';
                        break;

                        case 'youtube':
                            $xcampo_midia = 'Youtube';
                        break;

                        default:
                            $xcampo_midia = "";
                        break;
                    }
                } else {
                    $xcampo_midia = "";
                }

                if (isset($campos_adicionais['produto_30_dias'])) {

                    unset($xcampo_produto);

                    switch ($campos_adicionais['produto_30_dias']) {
                        case 'atraso_no_embarque':
                            $xcampo_produto = 'Atraso No Embarque';
                        break;
                        
                        case 'atraso_na_transportadora':
                            $xcampo_produto = 'Atraso Na Transportadora';
                        break;
                        
                        case 'item_com_divergência':
                            $xcampo_produto = 'Item Com Divergência';
                        break;
                        
                        default:
                            $xcampo_produto = "";
                        break;
                    }
                } else {
                    $xcampo_produto = "";
                }

            $descricao_motivo = "";
            if(!empty($motivo_devolucao)){
                $sqlMotivo = "SELECT descricao from tbl_motivo_devolucao where motivo_devolucao = $motivo_devolucao and fabrica = $login_fabrica";
                $resMotivo = pg_query($con, $sqlMotivo);
                if(pg_num_rows($resMotivo)>0){
                    $descricao_motivo = pg_fetch_result($resMotivo, 0, descricao);
                }
            }

            if (!empty($fornecedor) || !empty($posto)) {
                if (!empty($fornecedor)) {
                    $aux_sql = "SELECT nome, cnpj, endereco, numero, bairro, cep FROM tbl_fornecedor WHERE fornecedor = $fornecedor";
                    $aux_res = pg_query($con, $aux_sql);
                }else {
                    $aux_sql = "SELECT tbl_posto.nome, tbl_posto.cnpj, tbl_posto.endereco, tbl_posto.numero, tbl_posto.bairro, tbl_posto.cidade, tbl_posto.estado, tbl_posto.cep, tbl_posto_fabrica.codigo_posto 
                                FROM tbl_posto 
                                JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                WHERE tbl_posto.posto = $posto";

                    $aux_res = pg_query($con, $aux_sql);
                    $aux_cidade = pg_fetch_result($aux_res, 0, 'cidade');
                    $aux_estado = pg_fetch_result($aux_res, 0, 'estado');
                    $aux_posto = pg_fetch_result($aux_res, 0, 'codigo_posto'); 
                }

                $nome_fornecedor     = pg_fetch_result($aux_res, 0, 'nome');
                $cpf_fornecedor      = pg_fetch_result($aux_res, 0, 'cnpj');
                $endereco_fornecedor = pg_fetch_result($aux_res, 0, 'endereco');
                $numero_fornecedor   = pg_fetch_result($aux_res, 0, 'numero');
                $bairro_fornecedor   = pg_fetch_result($aux_res, 0, 'bairro');
                $aux_cep             = pg_fetch_result($aux_res, 0, 'cep');

                $aux_sql = "SELECT cidade, estado FROM tbl_cep WHERE cep = '$aux_cep'";
                $aux_res = pg_query($con, $aux_sql);

                $cidade_fornecedor = pg_fetch_result($aux_res, 0, 'cidade');
                $estado_fornecedor = pg_fetch_result($aux_res, 0, 'estado');

                if (empty($cidade_fornecedor) && !empty($aux_cidade)) {
                    $cidade_fornecedor = $aux_cidade;
                }

                if (empty($estado_fornecedor) && !empty($aux_estado)) {
                    $estado_fornecedor = $aux_estado;
                }

                $aux_sql = "SELECT numero, valor_bruto, observacao FROM tbl_solicitacao_cheque_item WHERE solicitacao_cheque = $solicitacao_cheque order by solicitacao_cheque_item";
                $aux_res = pg_query($con, $aux_sql);

                if (pg_num_rows($aux_res) > 0) {
                    $vttl = 0; $valor_bruto1 = 0; $valor_bruto2 = 0;
                    for ($v=0; $v < pg_num_rows($aux_res); $v++) { 
                        $vttl = pg_fetch_result($aux_res, $v, 'valor_bruto');
                        if (!empty($vttl)){
                            if (empty($valor_bruto1)) {
                                $valor_bruto1 =  priceFormat($vttl);
                            } else {
                                $valor_bruto2 = priceFormat($vttl);
                            }
                        }
                    }
                }

                $obs_doc      = pg_fetch_result($aux_res, 0, 'observacao');
                $numero_doc1  = pg_fetch_result($aux_res, 0, 'numero');
                $numero_doc2  = pg_fetch_result($aux_res, 1, 'numero');

                $aux_sql = "SELECT data_acao FROM tbl_solicitacao_cheque_acao WHERE solicitacao_cheque = $solicitacao_cheque AND tipo_acao = 'aprovado' ORDER BY data_input DESC LIMIT 1";
                $aux_res = pg_query($con, $aux_sql);

                $data_aprovacao = pg_fetch_result($aux_res, 0, 'data_acao');
            }

            if ($tipo_acao == "alteracao_pos_aprovado") {
                $cor = "#FFD700";
            } else {
                $cor = 'white';
            }

            if (empty($numero)) {
                $numero = $solicitacao_cheque;
            }

            if ($aprovacao == 'r') {
                $motivo                = pg_fetch_result($res, $i, 'motivo');
            } else {
                $motivo                = (mb_detect_encoding(pg_fetch_result($res, $i, 'motivo_alteracao'), "UTF-8")) ? utf8_decode(pg_fetch_result($res, $i, 'motivo_alteracao')) : pg_fetch_result($res, $i, 'motivo_alteracao');
            }
            
            $consulta_reincidente = (!empty($fornecedor) && $fornecedor !== 0) ? $fornecedor : $posto;
            $fornecedor_posto = (!empty($fornecedor) && $fornecedor !== 0) ? $fornecedor : $aux_posto;

            $data_input = explode(' ', $data_input);
            $data_input = implode('/', array_reverse(explode('-', $data_input[0])));

            $vencimento = explode(' ', $vencimento);
            $vencimento = implode('/', array_reverse(explode('-', $vencimento[0])));

            $data_aprovacao = explode(' ', $data_aprovacao);
            $data_aprovacao = implode('/', array_reverse(explode('-', $data_aprovacao[0])));
            
            if ($aprovacao == 'a') {
                $valor_data_aprovacao = "<td>".$data_aprovacao."</td>";
            }

            $valor_total_liquido += pg_fetch_result($res, $i, 'valor_liquido');

            $res_reincidente = pg_execute($con, 'consulta_reincidentes', array($consulta_reincidente, $consulta_reincidente, $solicitacao_cheque, $login_fabrica));
            $reincidente_array = pg_fetch_all($res_reincidente);

            $reincidente_excel = array_map(function($val){
                $numero = (!empty($val['numero_solicitacao'])) ? $val['numero_solicitacao'] : $val['solicitacao_cheque'];
                return str_pad($numero, 6, '0', STR_PAD_LEFT);
            }, $reincidente_array);

            $reincidente = array_map(function($val){
                $numero = (!empty($val['numero_solicitacao'])) ? $val['numero_solicitacao'] : $val['solicitacao_cheque'];
                return "<a href='cad_solicitacao_cheque.php?solicitacao_cheque=".$val['solicitacao_cheque']."&visualizar=true' target='_blank'>".str_pad($numero, 6, '0', STR_PAD_LEFT)."</a>";
            }, $reincidente_array);

            $texto_justificativa = '';
            if (isset($campos_adicionais['reincidente']) && $campos_adicionais['reincidente'] == true) {
                unset($reincidente);
                $sql_reincidencia = " SELECT numero_solicitacao 
                                      FROM tbl_solicitacao_cheque 
                                      WHERE fornecedor = (
                                                            SELECT fornecedor 
                                                            FROM tbl_solicitacao_cheque 
                                                            WHERE solicitacao_cheque = {$solicitacao_cheque}
                                                         ) 
                                      AND fabrica = {$login_fabrica}
                                      and tbl_solicitacao_cheque.solicitacao_cheque < {$solicitacao_cheque}
                                      and tbl_solicitacao_cheque.solicitacao_cheque not in 
                                        (SELECT tbl_solicitacao_cheque_acao.solicitacao_cheque
                                        FROM tbl_solicitacao_cheque_acao 
                                        WHERE tbl_solicitacao_cheque_acao.tipo_acao = 'desativado')";
                $res_reincidencia = pg_query($con, $sql_reincidencia);
                if (pg_num_rows($res_reincidencia) > 0) {
                    for ($r=0; $r < pg_num_rows($res_reincidencia); $r++) { 
                        $reincidente[] = pg_fetch_result($res_reincidencia, $r, 'numero_solicitacao'); 
                    }
                    if (in_array($n_solicitacao, $reincidente)) {
                        $posicao = array_search($n_solicitacao, $reincidente);
                        if($posicao !== false){
                            unset($reincidente[$posicao]);
                        }
                    }
                }
                $texto_justificativa = utf8_decode($campos_adicionais['justificativa']);
                $cor = '#FF8282';
            }

            $button_acoes = "<td class='tac' width='20%'><a href='visualiza_solicitacao_cheque.php?codigo={$solicitacao_cheque}' class='btn btn-warning btn-small' name='imprimir' target='_blank' >Imprimir</a>";

            if ($login_fabrica == 1) {
                $button_acoes = "<td class='tac' width='20%'><a href='visualiza_solicitacao_cheque.php?codigo={$solicitacao_cheque}&solicitacao_cheque=sim' class='btn btn-warning btn-small' name='imprimir' target='_blank' >Solicitação Cheque</a>";

                $button_acoes .= "<br><br><a href='visualiza_solicitacao_cheque.php?codigo={$solicitacao_cheque}&ficha_cadastral=sim' class='btn btn-warning btn-small' name='imprimir_ficha_cadastral' target='_blank' >Ficha Cadastral</a>";
            }


            if ($aprovacao == 'r' || $aprovacao == 'a' || $aprovacao == 'rs') {
                if ($layout_menu == 'callcenter') {
                    $button_acoes .= "<br><br><a href='cad_solicitacao_cheque.php?solicitacao_cheque=$solicitacao_cheque&$aprovacao' class='btn btn-primary btn-small' aprovacao='$aprovacao' name='alterar' value='{$solicitacao_cheque}' target='_blank'>Alterar</a>";
                }
            }

            if ($aprovacao == 'r' || $aprovacao == 'rs' || $aprovacao == 'ps') {
                $sql_login = "  SELECT admin
                                FROM tbl_solicitacao_cheque 
                                WHERE admin = $login_admin 
                                AND fabrica = $login_fabrica
                                AND solicitacao_cheque = $solicitacao_cheque";
                $res_login = pg_query($con, $sql_login);

                if (pg_num_rows($res_login) > 0 || $contador_adm_supervisao_cheque > 0) {
                    $button_acoes .= "<br><br><button class='btn btn-danger btn-small' name='abre_excluir' value='{$solicitacao_cheque}'>Excluir</button>";
                }
            }

            $button_acoes .= "</td>";
            
            if($login_fabrica == 1){
                if($layout_menu == 'gerencia' AND ($aprovacao == 'p' OR $aprovacao == 'a' OR $aprovacao == 'r')){
                    $checkbox_aprova_recusa = "<td class='tac'><input type='checkbox' value='{$solicitacao_cheque}' name='auditar_supervisao_cheque[]' id='auditar_supervisao_cheque'></td>"; 
                }
                if($contador_adm_supervisao_cheque > 0 AND ($aprovacao == 'ps' OR $aprovacao == 'rs') AND $layout_menu != 'gerencia'){
                    $checkbox_aprova_recusa = "<td class='tac'><input type='checkbox' value='{$solicitacao_cheque}' name='auditar_supervisao_cheque[]' id='auditar_supervisao_cheque'></td>"; 
                }
            }

            if ($aprovacao == 'a') {
                if (empty($data_contas_pagar)) {
                    $valor_contas_pagar = "<button class='btn btn-primary btn-small data_contas_pagar' name='data_contas_pagar' value='{$solicitacao_cheque}'>Informar</button>";
                    $valor_contas_pagar_xls = "";
                } else {
                    $sql_login = "  SELECT admin 
                                    FROM tbl_solicitacao_cheque 
                                    WHERE admin = $login_admin 
                                    AND fabrica = $login_fabrica
                                    AND solicitacao_cheque = $solicitacao_cheque";
                    $res_login = pg_query($con, $sql_login);
                    if (pg_num_rows($res_login) > 0 || $contador_adm_supervisao_cheque > 0) {
                        $valor_contas_pagar = $data_contas_pagar."<br /><br /><input type='hidden' name='data_contas_pagar_old_$solicitacao_cheque' value='$data_contas_pagar' /><button class='btn btn-info btn-small data_contas_pagar' name='data_contas_pagar' value='{$solicitacao_cheque}'>Alterar</button><br /><br /><a class='btn btn-light' rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_solicitacao_cheque&id=$solicitacao_cheque' name='btnAuditorLog'>Log</a>";
                    } else {
                        $valor_contas_pagar = $data_contas_pagar;
                    }
                    
                    $valor_contas_pagar_xls = $data_contas_pagar;
                }
            }
//AND $layout_menu != 'gerencia'
            if(( $login_fabrica == 1  and $permissaoGerencia == true) OR ( $login_fabrica == 1 and $contador_adm_supervisao_cheque > 0 )){
                if($supervisao_cheque_pendente == 'recusado' AND $supervisao_cheque_gerencia != 'recusado'){
                    $status_cheque = "<td class='tac'>Recusado Supervisão</td>";
                    $status_chq_body    = '"Recusado Supervisão"';
                } else if($supervisao_cheque_gerencia == 'recusado' OR (empty($supervisao_cheque_pendente) AND empty($supervisao_cheque_gerencia) AND $tipo_acao_status == 'recusado')){
                    $status_cheque = "<td class='tac'>Recusado Gerência</td>";
                    $status_chq_body    = '"Recusado Gerência"';  
                }

                if(($supervisao_cheque_gerencia == 'pendente' && strtotime($data_input_raiz) < strtotime($data_corte) && (empty($tipo_acao_status) || $tipo_acao_status != 'aprovado')) || ($supervisao_cheque_pendente == 'aprovado' && $supervisao_cheque_gerencia == 'pendente' && strtotime($data_input_raiz) >= strtotime($data_corte))){                    
                    $status_cheque      = "<td class='tac'>Pendente Gerência</td>";
                    $status_chq_body    = '"Pendente Gerência"';
                }

                // if($supervisao_cheque_gerencia == 'aprovado' OR (empty($supervisao_cheque_pendente) AND empty($supervisao_cheque_gerencia) AND $tipo_acao_status == 'aprovado')){
                if ($supervisao_cheque_gerencia == 'aprovado' || (strtotime($data_input_raiz) < strtotime($data_corte) && $tipo_acao_status == 'aprovado')) {
                    $status_cheque      = "<td class='tac'>Aprovado Gerência</td>";
                    $status_chq_body    = '"Aprovado Gerência"';                    
                }

                if(($supervisao_cheque_pendente == 'pendente' && $supervisao_cheque_gerencia == 'pendente') || (empty($supervisao_cheque_pendente) && empty($supervisao_cheque_gerencia) && empty($tipo_acao_status))){
                    $status_cheque = "<td class='tac'>Pendente Supervisão</td>";
                    $status_chq_body = '"Pendente Supervisão"';
                }

                if($aprovacao == 'ex'){
                    $status_cheque = "<td class='tac'>Excluído</td>";
                    $status_chq_body = '"Excluído"';
                }
            }

            echo "
                <tr style='background-color: $cor;'>
                    $checkbox_aprova_recusa
                    $status_cheque
                    <td class='tac'>".str_pad($numero, 6, '0', STR_PAD_LEFT)."</td>
                    <td class='tac'>".str_pad($componente_solicitante, 6, '0', STR_PAD_LEFT)."</td>
                    <td class='tac'>$data_input</td>
                    <td class='tac'>$vencimento</td>
                    $xdata_exclusao_c
                    $xmotivo_exclusao_c
                    <td class='tal'>$fornecedor_posto</td>
                    <td class='tal'>$nome_fornecedor</td>
                    <td class='tal'>$cpf_fornecedor</td>
                    <td class='tal'>$endereco_fornecedor</td>
                    <td class='tal'>$numero_fornecedor</td>
                    <td class='tal'>$bairro_fornecedor</td>
                    <td class='tal'>$cidade_fornecedor</td>
                    <td class='tac'>$estado_fornecedor</td>
                    <td class='tal'>R$ $valor_liquido</td>
                    <td class='tal'>$numero_doc1</td>
                    <td class='tal'>R$ $valor_bruto1</td>
                    <td class='tal'>$numero_doc2</td>
                    <td class='tal'>R$ $valor_bruto2</td>
                    <td class='tal'>$obs_doc</td>
                    <td class='tal' style='cursor: pointer;' title='$historico' onclick=\"alert('$historico');\">".substr($historico, 0, 50)."...</td>
                    <td>$admin</td>
                    <td>$descricao</td>
                    <td>".implode(', ', $reincidente)."</td>
                    <td>$texto_justificativa</td>
                    <td style='cursor: pointer;' title='$motivo' onclick=\"alert('$motivo');\">".substr($motivo, 0, 50)."</td>
                    <td>$valor_contas_pagar</td>
                    $valor_data_aprovacao
                    $button_acoes
                    <td class='tac'><button class='btn btn-small' name='anexo' value='{$solicitacao_cheque}'>Visualizar</td>
                </tr>
            ";

            if ($aprovacao == 'ex') {
                $body = array(
                    $status_chq_body,
                    '"' . str_pad($numero, 6, '0', STR_PAD_LEFT)     . '"',
                    '"' . str_pad($componente_solicitante, 6, '0', STR_PAD_LEFT) . '"',
                    '"' . $data_input                                            . '"',
                    '"' . $vencimento                                            . '"',
                    '"' . $xdata_exclusao                                        . '"',
                    '"' . $xmotivo_exclusao                                      . '"',
                    '"' . $fornecedor_posto                                      . '"',
                    '"' . $nome_fornecedor                                       . '"',
                    '"' . $cpf_fornecedor                                        . '"',
                    '"' . str_replace($quebra_linha, "", $endereco_fornecedor)   . '"',
                    '"' . $numero_fornecedor                                     . '"',
                    '"' . $bairro_fornecedor                                     . '"',
                    '"' . $cidade_fornecedor                                     . '"',
                    '"' . $estado_fornecedor                                     . '"',
                    '"' . "R$ ".$valor_liquido                                   . '"',
                    '"' . $numero_doc1                                           . '"',
                    '"' . "R$ ". $valor_bruto1                                   . '"',
                    '"' . $numero_doc2                                           . '"',
                    '"' . "R$ ". $valor_bruto2                                   . '"',
                    '"' . str_replace($quebra_linha, "", $obs_doc)               . '"',
                    '"' . str_replace($quebra_linha, "", $historico)             . '"',
                    '"' . $admin                                                 . '"',
                    '"' . $descricao                                             . '"',
                    '"' . implode(', ', $reincidente_excel)                      . '"',
                    '"' . $texto_justificativa                                   . '"',
                    '"' . str_replace($quebra_linha, "", $motivo)                . '"',
                    '"' . $valor_contas_pagar_xls                                . '"',
                    '"' . $data_aprovacao                                        . '"',
                    '"' . $descricao_motivo                                      . '"',
                    '"' . $xproduto_ref                                          . '"',
                    '"' . $xproduto_desc                                         . '"',
                    '"' . $pecas_explode['referencia']                           . '"',
                    '"' . $pecas_explode['descricao']                            . '"',
                    '"' . $pecas_explode['status']                               . '"',
                    '"' . $campos_adicionais['protocolo']                        . '"',
                    '"' . $campos_adicionais['numero_bo']                        . '"',
                    '"' . $campos_adicionais['numero_processo']                  . '"',
                    '"' . $campos_adicionais['justificativa']                    . '"',
                    '"' . $campos_adicionais['hd_chamado']                       . '"',
                    '"' . $xcampo_produto                                        . '"',
                    '"' . $xcampo_midia                                          . '"',
                    '"' . $campos_adicionais['id_midia']                         . '"',
                    '"' . $campos_adicionais['os1']                              . '"',
                    '"' . $campos_adicionais['os2']                              . '"'. "\n",
                );
            } else {
                $body = array(
                    $status_chq_body,
                    '"' . str_pad($numero, 6, '0', STR_PAD_LEFT)     . '"',
                    '"' . str_pad($componente_solicitante, 6, '0', STR_PAD_LEFT) . '"',
                    '"' . $data_input                                            . '"',
                    '"' . $vencimento                                            . '"',
                    '"' . $fornecedor_posto                                      . '"',
                    '"' . $nome_fornecedor                                       . '"',
                    '"' . $cpf_fornecedor                                        . '"',
                    '"' . str_replace($quebra_linha, "", $endereco_fornecedor)   . '"',
                    '"' . $numero_fornecedor                                     . '"',
                    '"' . $bairro_fornecedor                                     . '"',
                    '"' . $cidade_fornecedor                                     . '"',
                    '"' . $estado_fornecedor                                     . '"',
                    '"' . "R$ ".$valor_liquido                                   . '"',
                    '"' . $numero_doc1                                           . '"',
                    '"' . "R$ ". $valor_bruto1                                   . '"',
                    '"' . $numero_doc2                                           . '"',
                    '"' . "R$ ". $valor_bruto2                                   . '"',
                    '"' . str_replace($quebra_linha, "", $obs_doc)               . '"',
                    '"' . str_replace($quebra_linha, "", $historico)             . '"',
                    '"' . $admin                                                 . '"',
                    '"' . $descricao                                             . '"',
                    '"' . implode(', ', $reincidente_excel)                      . '"',
                    '"' . $texto_justificativa                                   . '"',
                    '"' . str_replace($quebra_linha, "", $motivo)                . '"',
                    '"' . $valor_contas_pagar_xls                                . '"',
                    '"' . $data_aprovacao                                        . '"',
                    '"' . $descricao_motivo                                      . '"',
                    '"' . $xproduto_ref                                          . '"',
                    '"' . $xproduto_desc                                         . '"',
                    '"' . $pecas_explode['referencia']                           . '"',
                    '"' . $pecas_explode['descricao']                            . '"',
                    '"' . $pecas_explode['status']                               . '"',
                    '"' . $campos_adicionais['protocolo']                        . '"',
                    '"' . $campos_adicionais['numero_bo']                        . '"',
                    '"' . $campos_adicionais['numero_processo']                  . '"',
                    '"' . $campos_adicionais['justificativa']                    . '"',
                    '"' . $campos_adicionais['hd_chamado']                       . '"',
                    '"' . $xcampo_produto                                        . '"',
                    '"' . $xcampo_midia                                          . '"',
                    '"' . $campos_adicionais['id_midia']                         . '"',
                    '"' . $campos_adicionais['os1']                              . '"',
                    '"' . $campos_adicionais['os2']                              . '"'. "\n",
                );
            }
            
            fwrite($fp, (implode(";", $body)));
        }
        fclose($fp);
    ?>
            </tbody>
        </table>
        </div>
        <br />
        <div class="alert alert-warning"><h4>Total: <?=priceFormat($valor_total_liquido)?></h4></div>
        <?php
        if($login_fabrica == 1){
            if($contador_adm_supervisao_cheque > 0 AND ($aprovacao == 'ps' OR $aprovacao == 'rs')) {
            ?>
            <div class="row-fluid">
                <div class="span12" style="background-color: #596d9b">
                    <img src="imagens/seta_checkbox.gif" align="absmiddle" style="margin-left: 10px;" /> <b style="color: #FFFFFF;" >COM MARCADOS:</b>
                    <button class='btn btn-success btn-small' name='aprovar' style="margin-top: 2px;" <?=($aprovacao == 'a') ? 'disabled' : ''?>>Aprovar</button>
                    <button class='btn btn-danger btn-small' name='recusar' style="margin-top: 2px;" <?=($aprovacao == 'r') ? 'disabled' : ''?>>Recusar</button>
                </div>
            </div>  
        <?php 
            }   
        }
        if ($layout_menu == 'gerencia') {
        ?>
        <div class="row-fluid">
            <div class="span12" style="background-color: #596d9b">
                <img src="imagens/seta_checkbox.gif" align="absmiddle" style="margin-left: 10px;" /> <b style="color: #FFFFFF;" >COM MARCADOS:</b>
                <button class='btn btn-success btn-small' name='aprovar' style="margin-top: 2px;" <?=($aprovacao == 'a') ? 'disabled' : ''?>>Aprovar</button>
                <button class='btn btn-danger btn-small' name='recusar' style="margin-top: 2px;" <?=($aprovacao == 'r') ? 'disabled' : ''?>>Recusar</button>
            </div>
        </div>
        <?php } ?>

        <div class="row-fluid">
            <div class="span12">
                <div class="btn_excel" onclick="javascript: window.location='<?=$file_csv?>';">         
                    <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
                    <span><img style="width:40px ; height:40px;" src="imagens/icon_csv.png"></span>
                </div>
            </div>
        </div>
    <?php
    }else{
        if (!isset($primeira_pesquisa)) {
            echo '
            <div class="row-fluid">
                <div class="span12 alert alert-warning">
                <h4>Nenhum registro encontrado</h4
                </div>
            </div>';
        }
    }
}
?>
<br />
<script type="text/javascript">
    $(function(){
        $('#data_inicial, #data_final, #data_contas_pagar_modal').datepicker().mask("99/99/9999");
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        jQuery.extend(jQuery.fn.dataTableExt.oSort, {
            "currency-pre": function (a) {
                a = (a === "-") ? 0 : a.replace(/[^\d\-\.]/g, "");
                return parseFloat(a);
            },
            "currency-asc": function (a, b) {
                return a - b;
            },
            "currency-desc": function (a, b) {
                return b - a;
            }
        });

        <?php if ($layout_menu == 'gerencia') { ?>
            var colunas = [
                null,
                { "sType":"numeric" },
                { "sType":"date" },
                null,
                { "sType":"numeric" },
                null,
                null,
                null,
                null,
                null
            ];
        <?php }else{ ?>
            var colunas = [
                { "sType":"numeric" },
                { "sType":"date" },
                null,
                { "sType":"numeric" },
                null,
                null,
                null,
                null,
                null
            ];
        <?php } ?>

        var table = new Object();
        table['table'] = '#resultado_tipo_solicitacao';
        table['type'] = 'full';
        $.dataTableLoad(table);
        

        $('#btn_novo').on('click', function(){
            document.location.href = 'cad_solicitacao_cheque.php';
        });

        $(document).on('click', ".data_contas_pagar", function(){
            let solicitacao_informar = $(this).val();
            $('#solicitacao_modal').val(solicitacao_informar);
            $('#modal_data').modal('show');
            if ($("input[name=data_contas_pagar_old_"+solicitacao_informar+"]").val() != '' && $("input[name=data_contas_pagar_old_"+solicitacao_informar+"]").val() != undefined) {
                $("#data_contas_pagar_modal").val($("input[name=data_contas_pagar_old_"+solicitacao_informar+"]").val());
            }
        });

        $('.fechar_modal').on('click', function(){
            $('#modal_data').modal('hide');
            $(".msg_erro_modal").hide();
            $('.msg_erro_modal_salvar').hide();
            $('.msg_sucess_modal').hide();
            $("#data_contas_pagar_modal").val("");
        });

        $('.close').on('click', function(){
            $('#modal_data').modal('hide');
            $(".msg_erro_modal").hide();
            $('.msg_erro_modal_salvar').hide();
            $('.msg_sucess_modal').hide();
            $("#data_contas_pagar_modal").val("");
        });

        $("#data_contas_pagar_modal").focus(function(){
            $(".msg_erro_modal").hide("slow");
            $('.msg_erro_modal_salvar').hide("slow");
        });

        $('.salvar_modal').on('click', function(){
            $(".msg_erro_modal").hide("slow");
            $('.msg_erro_modal_salvar').hide("slow");

            var solicitacao_cheque = $('#solicitacao_modal').val();
            var data_contas = "";
            data_contas = $('#data_contas_pagar_modal').val();

            if (data_contas == "") {
                $(".msg_erro_modal").show("slow");
            } else {
                $.ajax({
                    url: window.open.href,
                    method: 'POST',
                    data: { ajax: 'sim', action: 'salvar_data_contas', solicitacao_cheque: solicitacao_cheque, data_contas: data_contas },
                    timeout: 8000
                }).fail(function(){
                    $('.msg_erro_modal_salvar').show("slow");
                }).done(function(data){
                    data = JSON.parse(data);
                    if (data.ok == undefined) {
                        $('.msg_erro_modal_salvar').show("slow");
                    }else{
                        $("#data_contas_pagar_modal").val("");
                        $(".salvar_modal").hide();
                        $("#data_contas_pagar_modal").hide();
                        $('.msg_sucess_modal').show("slow");
                        setTimeout(function(){
                            $('#modal_data').modal('hide');
                            $('.msg_sucess_modal').hide();
                            window.parent.location.reload(); 
                        }, 3000);
                    }
                });
            }
        });

        $('button[name=abre_excluir]').on('click', function(){
            let solicitacao_informar = $(this).val();
            $('#solicitacao_modal_excluir').val(solicitacao_informar);
            $('#modal_excluir').modal('show');
        });

         $('.fechar_modal_excluir').on('click', function(){
            $('#modal_excluir').modal('hide');
            $(".msg_erro_modal_excluir").hide();
            $('.msg_erro_modal_salvar_excluir').hide();
            $('.msg_sucess_modal_excluir').hide();
            $("#motivo_excluir").val("");
        });

        $("#motivo_excluir").focus(function(){
            $(".msg_erro_modal_excluir").hide("slow");
            $('.msg_erro_modal_salvar_excluir').hide("slow");
        });

        $('.salvar_modal_excluir').on('click', function(){
            $(".msg_erro_modal_excluir").hide("slow");
            $('.msg_erro_modal_salvar_excluir').hide("slow");

            var solicitacao_cheque = $('#solicitacao_modal_excluir').val();
            var motivo_excluir = "";
            motivo_excluir = $('#motivo_excluir').val();

            if (motivo_excluir == "") {
                $(".msg_erro_modal_excluir").show("slow");
            } else {
                $.ajax({
                    url: window.open.href,
                    method: 'POST',
                    data: { ajax: 'sim', action: 'deleta_solicitacao', solicitacao_cheque: solicitacao_cheque, motivo_excluir: motivo_excluir },
                    timeout: 8000
                }).fail(function(){
                    $('.msg_erro_modal_salvar_excluir').show("slow");
                }).done(function(data){
                    data = JSON.parse(data);
                    if (data.ok == undefined) {
                        $('.msg_erro_modal_salvar_excluir').show("slow");
                    }else{
                        $("#motivo_excluir").val("");
                        $(".salvar_modal_excluir").hide();
                        $("#motivo_excluir").hide();
                        $('.msg_sucess_modal_excluir').show("slow");
                        setTimeout(function(){
                            $('#modal_data').modal('hide');
                            $('.msg_sucess_modal_excluir').hide();
                            window.parent.location.reload(); 
                        }, 3000);
                    }
                });
            }
        });

        $(document).on('click', 'button[name=anexo]', function(){
            var value = $(this).val();
            Shadowbox.init();
            Shadowbox.open({
                content: window.location.href+"?anexos="+value,
                player: "iframe",
                title:  "Solicitação de Cheque - Anexos",
                width:  1024,
                height: 600
            });
        });

        $('button[name=recusar]').on('click', function(){
            if ($('input[type=checkbox]').is(':checked')) {
                var value = $(this).val();
                Shadowbox.init();
                Shadowbox.open({
                    content: window.location.href+"?motivo_recusa=recusa_solicitacoes",
                    player: "iframe",
                    width:  400,
                    height: 220
                });
            } else {
                alert('Selecione alguma solicitação de cheque');
            }
        });

        //$('button[name=aprovar], button[name=recusar]').on('click', function(){
        $('button[name=aprovar]').on('click', function(){            
            pega_selecionados('aprova_solicitacoes');
        });

        $('input[name=check_all]').change(function(){
            $('tbody input[type=checkbox]').prop('checked', $('input[name=check_all]').is(':checked'));
        });
    });

    function pega_selecionados(action,motivo){

        if ($('input[type=checkbox]').is(':checked')) {                
            var solicitacoes = []; 
            console.log(solicitacoes);

            $('input[type=checkbox]').each(function(){
                if ($(this).is(':checked') && $(this).attr('name') !== 'check_all') {
                    solicitacoes.push($(this).val());
                }
            });
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: { ajax: 'sim', action: action, solicitacoes: solicitacoes, motivo: motivo  },
                beforeSend: function() {
                    loading('show');
                    $('button[name=aprovar]').attr('disabled', true);
                    $('button[name=recusar]').attr('disabled', true);
                },
                complete: function(data){
                    data = $.parseJSON(data.responseText);
                    if (data.ok) {
                        if (data.ok !== undefined) {
                            $('button[name=btn_acao]').trigger('click');
                        }else{
                            alert(data.error);
                        }
                        loading('hide');
                    } else {
                        $('button[name=aprovar]').attr('disabled', false);
                        $('button[name=recusar]').attr('disabled', false);
                        loading('hide');
                    }
                }
            });
        }else{
            alert('Selecione alguma solicitação de cheque');
        }
    }

    function retorna_fornecedor (retorno) {
        console.log(retorno);
        $("#codigo_fornecedor").val(retorno.fornecedor);
        $("#nome_fornecedor").val(retorno.nome);
    }
</script>
<?php include_once "rodape.php"; ?>

