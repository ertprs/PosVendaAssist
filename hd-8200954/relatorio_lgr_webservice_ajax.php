<?

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
    include __DIR__."/class/tdocs.class.php";
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

include __DIR__."/class/ComunicatorMirror.php";
$comunicatorMirror = new ComunicatorMirror();

$btn_acao = $_REQUEST['btn_acao'];

if ($btn_acao == "atualizar_email" AND !empty($_POST["id_faturamento"]) AND !empty($_POST["novo_email"])){
    $id_faturamento = $_POST["id_faturamento"];
    $novo_email = $_POST["novo_email"];

    $sql = "UPDATE tbl_faturamento_destinatario SET email = '$novo_email' WHERE faturamento = $id_faturamento";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0){
        echo "error";
    }else{
        echo "success";
    }
    exit;
}

if ($btn_acao == "atualizar_ac" AND !empty($_POST["id_faturamento"]) AND !empty($_POST["novo_ac"])){
    $id_faturamento = $_POST["id_faturamento"];
    $novo_ac = $_POST["novo_ac"];

    $sql = "UPDATE tbl_faturamento SET pedido_fabricante = '$novo_ac' WHERE faturamento = $id_faturamento";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0){
        echo "error";
    }else{
        echo "success";
    }
    exit;
}

if (in_array(strtolower($btn_acao), array("aprovar", 'reprovar', 'interagir', 'reenviar'))) {
    $faturamento = $_REQUEST['faturamento'];
    $autorizacao_coleta = $_REQUEST['autorizacao_coleta'];
    $conhecimento = $_REQUEST['conhecimento'];
    $correios = $_REQUEST['correios'];
    $observacoes = $_REQUEST['observacoes'];
    $auxObservacoes = str_replace("'", "''", $_REQUEST['observacoes']);

    if (empty($correios) && empty($autorizacao_coleta) && $btn_acao == "aprovar") {
        $msg_erro["msg"][] = "Para a aprovação é necessário informar uma autorização de coleta";
        $msg_erro['campos'][] = "autorizacao_coleta";
    }

    if (!empty($correios) && (empty($autorizacao_coleta) || empty($conhecimento)) && $btn_acao == "aprovar") {
        $msg_erro["msg"][] = "Para a aprovação é necessário informar uma autorização de coleta e o E-Ticket";
        $msg_erro['campos'][] = "autorizacao_coleta";
        $msg_erro['campos'][] = "conhecimento";
    }

    if (empty($observacoes)) {
        $msg_erro["msg"]['campos_obrigatorios'] = "Preencha os campos obrigatórios";
        $msg_erro['campos'][] = "observacoes";
    }

    if (count($msg_erro['msg']) == 0) {

        try {
            pg_query($con, "BEGIN;");

            if (!in_array($btn_acao, array('interagir', 'reenviar'))) {

                if ($btn_acao == "aprovar") {
                    $aux_conhecimento = "";
                    if (!empty($correios)) {
                        $aux_conhecimento = ", conhecimento = '{$conhecimento}'";
                    }
                    $camposUpd = "devolucao_concluida = 't', pedido_fabricante = '{$autorizacao_coleta}'{$aux_conhecimento}";
                } else {
                    $camposUpd = "cancelada = now()";
                }

                $updFat = "UPDATE tbl_faturamento SET {$camposUpd} WHERE fabrica = {$login_fabrica} AND faturamento = {$faturamento};";
                pg_query($con, $updFat);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Ocorreu um erro gravando as informações da devolução #001");
                }

            }

            if ($areaAdmin === true) {
                $colResp = "admin,";
                $valResp = "{$login_admin},";
            } else {
                $colResp = "posto,";
                $valResp = "{$login_posto},";
            }

            $instInt = "
                INSERT INTO tbl_faturamento_interacao (
                    {$colResp}
                    faturamento,
                    fabrica,
                    interacao
                ) VALUES (
                    {$valResp}
                    {$faturamento},
                    {$login_fabrica},
                    '{$auxObservacoes}'
                );
            ";

            pg_query($con, $instInt);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Ocorreu um erro gravando as informações da devolução #002");
            }

            $sqlMail = "
                SELECT
                    fd.nome AS posto_solicitante,
                    LOWER(TRIM(fd.email)) AS posto_email,
                    fd.fone AS posto_fone1,
                    fd.ie AS posto_fone2,
                    LOWER(TRIM(tf.contato_email)) AS transportadora_email,
                    t.nome As transportadora_nome,
                    pf.codigo_posto AS posto_codigo,
                    p.nome AS posto_nome,
                    f.nota_fiscal,
                    f.pedido_fabricante,
                    TO_CHAR(f.emissao, 'DD/MM/YY') AS emissao
                FROM tbl_faturamento f
                JOIN tbl_faturamento_destinatario fd USING(faturamento)
                JOIN tbl_posto_fabrica pf ON pf.posto = f.distribuidor AND pf.fabrica = {$login_fabrica}
                JOIN tbl_posto p ON p.posto = pf.posto
                JOIN tbl_transportadora_fabrica tf ON tf.transportadora = pf.transportadora AND tf.fabrica = {$login_fabrica}
                JOIN tbl_transportadora t ON t.transportadora = tf.transportadora
                WHERE f.fabrica = {$login_fabrica}
                AND f.faturamento = {$faturamento};
            ";

            $resMail = pg_query($con, $sqlMail);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Ocorreu um erro gravando as informações da devolução #003");
            } else if (pg_num_rows($resMail) == 0) {
                throw new Exception("É necessário informar uma transportadora para o posto autorizado");
            }

            $transportadora_nome = pg_fetch_result($resMail, 0, "transportadora_nome");
            $transportadora_email = pg_fetch_result($resMail, 0, "transportadora_email");
            $posto_email = pg_fetch_result($resMail, 0, "posto_email");
            $posto_codigo = pg_fetch_result($resMail, 0, "posto_codigo");
            $posto_nome = pg_fetch_result($resMail, 0, "posto_nome");
            $posto_solicitante = pg_fetch_result($resMail, 0, "posto_solicitante");
            $posto_fone1 = pg_fetch_result($resMail, 0, "posto_fone1");
            $posto_fone2 = pg_fetch_result($resMail, 0, "posto_fone2");
            $nota_fiscal = pg_fetch_result($resMail, 0, "nota_fiscal");
            $emissao = pg_fetch_result($resMail, 0, "emissao");
            $pedido_fabricante = pg_fetch_result($resMail, 0, "pedido_fabricante");

            unset($amazonTC, $anexos, $types);
            $amazonTC = new TDocs($con, $login_fabrica,"lgr");
            $anexos = array();
            $exibir_anexo = false;

            for ($i = 0; $i < 2; $i++) {
                $anexos["$i"]["nome"] = "nf_devolucao_{$faturamento}_{$login_fabrica}_anexo_nf_{$i}";
                $anexos["$i"]["url"] = $amazonTC->getDocumentsByName($anexos["$i"]["nome"], "lgr", $faturamento)->url;
                if (strlen($anexos["$i"]["url"]) > 0) {
                    $exibir_anexo = true;
                }
            }

            if ($exibir_anexo === true) {
                $links = "";
                foreach ($anexos as $value) {
                    if (strlen($value["url"]) > 0) {
                        $ext = pathinfo($value["url"], PATHINFO_EXTENSION);
                        $desc = "";
                        if ($ext != 'xml') {
                            $desc = "Link NF (PDF/JPEG)";
                        } else {
                            $desc = "Link NF (XML)";
                        }
                        $links .= "<a href='{$value['url']}' target='_blank'>{$desc}</a><br />";
                    }
                }
            }

            if (empty($autorizacao_coleta) && !empty($pedido_fabricante)) {
                $autorizacao_coleta = $pedido_fabricante;
            }

            $text_autorizacao = "";

            if (!empty($autorizacao_coleta)) {
                $text_autorizacao = "Autorização de coleta n° <b>{$autorizacao_coleta}</b>.<br />";
            }

            if (in_array($btn_acao, array('aprovar', 'reenviar'))) {
                $status_solicitacao = "
                    Solicitação de devolução n° {$faturamento} foi <b>APROVADA</b>.<br />
                    Razão Social da Empresa: <b>{$posto_nome}</b><br />
                    <br />
                    {$text_autorizacao}
                    NF Devolução n° <b>{$nota_fiscal}</b><br />
                    Emissão: <b>{$emissao}</b><br />
                    <br />
                ";
                $assunto_solicitacao = "Solicitação de Coleta - APROVADA / CT {$posto_codigo}, Acomp. {$faturamento}, NF {$nota_fiscal}";
            } else if ($btn_acao == "reprovar") {
                $status_solicitacao = "
                    Solicitação de devolução n° {$faturamento} foi <b>REPROVADA</b>.<br />
                    Razão Social da Empresa: <b>{$posto_nome}</b><br />
                    <br />
                    {$text_autorizacao}
                    NF Devolução n° <b>{$nota_fiscal}</b>.<br />
                    Emissão: <b>{$emissao}</b><br />
                    <br />
                ";
                $assunto_solicitacao = "Solicitação de Coleta - REPROVADA / CT {$posto_codigo}, Acomp. {$faturamento}, NF {$nota_fiscal}";
            } else {
                $status_solicitacao = "
                    Solicitação de devolução n° {$faturamento} teve uma interação.<br />
                    Razão Social da Empresa: <b>{$posto_nome}</b><br />
                    <br />
                    {$text_autorizacao}
                    NF Devolução n° <b>{$nota_fiscal}</b>.<br />
                    Emissão: <b>{$emissao}</b><br />
                    <br />
                ";
                $assunto_solicitacao = "Interação na solicitação de coleta -  CT {$posto_codigo}, Acomp. {$faturamento}, NF {$nota_fiscal}";
            }

            $mensagem_email_posto = "
                Olá,<br />
                {$status_solicitacao}
                <b>Links NF</b><br />
                {$links}<br />
                <br />
                <b>As observações da fábrica foram:</b><br />
                <span style='color:red;font-weight:bold;'>{$observacoes}</span><br />
                <br />
                <br />
                Transportadora acionada: <b>{$transportadora_nome}</b><br />
                <br />
                <b>INFORMAÇÕES REDE AUTORIZADA</b><br />
                <b>Solicitante:</b> {$posto_solicitante}<br />
                <b>Fone 1:</b> {$posto_fone1} - <b>Fone 2:</b> {$posto_fone2} - <b>Email:</b> {$posto_email}<br />
                <br />
                <br />
                <br />
                Dúvidas devem ser enviadas através da opção de <b>interação</b> na tela de histórico de Solicitações de Devolução no <b>Telecontrol</b>.
            ";

            $mensagem_email_admin = "
                Olá,<br />
                {$status_solicitacao}
                <b>Links NF</b><br />
                {$links}<br />
                <br />
                As observações do autorizado foram:<br />
                <b>{$observacoes}</b><br />
                <br />
                <b>INFORMAÇÕES REDE AUTORIZADA</b><br />
                <b>Solicitante:</b> {$posto_solicitante}<br />
                <b>Fone 1:</b> {$posto_fone1} - <b>Fone 2:</b> {$posto_fone2} - <b>Email:</b> {$posto_email}<br />
                <br />
            ";

            if ($areaAdmin === true) {
                if (in_array($btn_acao, array('aprovar', 'reenviar'))) {
                    $comunicatorMirror->post($posto_email, utf8_encode($assunto_solicitacao), utf8_encode($mensagem_email_posto), 'noreply@tc', 'noreply@telecontrol.com.br', ["devolucaogarantia@mideacarrier.com", $transportadora_email]);
                } else {
                    $comunicatorMirror->post($posto_email, utf8_encode($assunto_solicitacao), utf8_encode($mensagem_email_posto), 'noreply@tc', 'noreply@telecontrol.com.br', ["devolucaogarantia@mideacarrier.com"]);
                }
            } else {
                $comunicatorMirror->post('devolucaogarantia@mideacarrier.com', utf8_encode($assunto_solicitacao), utf8_encode($mensagem_email_admin), 'noreply@tc', 'noreply@telecontrol.com.br');
            }

            pg_query($con, "COMMIT;");
            $msg_sucesso = "Interação efetuada com sucesso";
            unset($_REQUEST['observacoes'], $_REQUEST['autorizacao_coleta'], $_REQUEST['conhecimento']);
        } catch(Exception $e) {
            $mensagem = $e->getMessage();
            $tipo_erro = "error";
            if (in_array($login_fabrica, array(169,170))){    
                $pos = strpos($mensagem, "blacklist Communicator");
                
                if ($pos !== false){
                    $mensagem = "O e-mail cadastrado encontra-se em blacklist, por favor entre em contato com o responsável para regularização ou altere o e-mail para reenvio";
					$tipo_erro = "warning";
                }
            }
           
            $msg_erro['msg'][] = $mensagem;
            pg_query($con, "ROLLBACK;");
        }
    }
}

if (count($msg_erro["msg"]) > 0) { ?>
    <br />
	<div class="alert alert-<?=$tipo_erro?>"><h4><?= implode("<br />", $msg_erro["msg"]); ?></h4></div>
<? } else { ?>
    <? if (!empty($msg_sucesso)) { ?>
        <br />
        <div class="alert alert-success"><h4><?= $msg_sucesso; ?></h4></div>
    <? }
}

if ($_REQUEST['faturamento']) {

    $ac_campo = (in_array($login_fabrica, [169,170])) ? 'f.pedido_fabricante,' : "";

    $faturamento = $_REQUEST['faturamento'];

    $sqlDadosFat = "
        SELECT 
            f.faturamento,
            TO_CHAR(f.emissao, 'DD/MM/YY') AS emissao,
            f.nota_fiscal,
            fd.nome,
            fd.fone AS fone1,
            fd.ie AS fone2,
            LOWER(TRIM(fd.email)) AS email,
            f.devolucao_concluida,
            f.conhecimento,
            $ac_campo
            TO_CHAR(f.cancelada, 'DD/MM/YY') AS cancelada,
            t.nome AS transportadora_nome
        FROM tbl_faturamento f
        LEFT JOIN tbl_faturamento_destinatario fd ON fd.faturamento = f.faturamento
        JOIN tbl_transportadora t ON t.transportadora = f.transportadora
        WHERE f.fabrica = {$login_fabrica}
        AND f.faturamento = {$faturamento};
    ";
    $resDadosFat = pg_query($con, $sqlDadosFat);

    $sqlDadosFatItem = "
        SELECT
            p.referencia||' - '||p.descricao AS peca,
	    fi.nota_fiscal_origem,
            fi.qtde,
            CASE WHEN fi.os IS NULL THEN fi.obs_conferencia::INT ELSE fi.os END AS os,
            fi.preco,
            fi.base_icms,
            fi.valor_icms,
            fi.valor_ipi
        FROM tbl_faturamento_item fi
        JOIN tbl_peca p USING (peca)
        WHERE fi.faturamento = {$faturamento}
        AND p.fabrica = {$login_fabrica};
    ";

    $resDadosFatItem = pg_query($con, $sqlDadosFatItem);

    if (pg_num_rows($resDadosFat) > 0) { ?>

        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="bootstrap/js/bootstrap.js"></script>

        <?php 
            if (in_array($login_fabrica, array(169,170))){
                $display_email = "style='display:none;'";

                if (count($msg_erro["msg"]) > 0) {
                    $pos = strpos($msg_erro["msg"][0], "encontra-se na blacklist");
                    
                    if ($pos !== false){
                        $display_email = "";
                    }
                }
        ?>
                <div id="div_atualizar_email" <?=$display_email?> class="tc_formulario">
                    <div class="titulo_tabela">ALTERAR EMAIL</div>
                    <br />
                    <div class="row-fluid">
                        <div class='span12'>
                            <div class='control-group' style='margin-left: 10px;'>
                                <label class='control-label' for='familia'>Novo E-mail</label>
                                <div class='controls controls-row'>
                                    <input type="text" name="novo_email" id="novo_email" class="span6" value="">        
                                    <button type="button" class="btn btn-primary" data-faturamento="<?=$faturamento?>" id="atualizar_email" style="margin-bottom: 10px;">Alterar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br />
                </div>

                <div id="div_atualizar_ac" style="display:none;" class="tc_formulario">
                    <div class="titulo_tabela">ALTERAR AC</div>
                    <br />
                    <div class="row-fluid">
                        <div class='span12'>
                            <div class='control-group' style='margin-left: 10px;'>
                                <label class='control-label' for='familia'>Nova AC</label>
                                <div class='controls controls-row'>
                                    <input type="text" name="novo_ac" id="novo_ac" class="span6" value="">        
                                    <button type="button" class="btn btn-primary" data-faturamento="<?=$faturamento?>" id="atualizar_ac" style="margin-bottom: 10px;">Alterar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br />
                </div>

        <?php } ?>
        <table id="resultado_pesquisa_pecas" class='table table-striped table-bordered table-hover table-large'>
            <thead>
                <tr>
                    <td colspan="7">
                        <table id="resultado_pesquisa_fat" class='table table-striped table-bordered table-hover table-large'>
                            <thead>
                                <tr>
                                    <th class="titulo_coluna">Acompanhamento</th>
                                    <?php
                                        if (in_array($login_fabrica, [169,170]) && !empty(pg_fetch_result($resDadosFat, 0, "pedido_fabricante")) && pg_fetch_result($resDadosFat, 0, "devolucao_concluida") == 't' && $areaAdmin === true) {
                                    ?>
                                            <th class="titulo_coluna">AC</th>        
                                    <?php 
                                        }
                                    ?>
                                    <th class="titulo_coluna">Solicitante</th>
                                    <th class="titulo_coluna">Telefone</th>
                                    <th class="titulo_coluna">Celular</th>
                                    <th class="titulo_coluna">Email</th>
                                    <th class="titulo_coluna">NF Devolução</th>
                                    <th class="titulo_coluna">Emissão</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="tac"><?= pg_fetch_result($resDadosFat, 0, "faturamento"); ?></td>
                                    <?php
                                        if (in_array($login_fabrica, [169,170]) && !empty(pg_fetch_result($resDadosFat, 0, "pedido_fabricante")) && pg_fetch_result($resDadosFat, 0, "devolucao_concluida") == 't' && $areaAdmin === true) {
                                    ?>
                                            <td class="tac ac_cadastro">
                                                <?= pg_fetch_result($resDadosFat, 0, "pedido_fabricante"); ?>
                                                <br />
                                                <button class="btn btn-primary btn-mini altera_ac">Alterar</button>
                                            </td>

                                    <?php 
                                        }
                                    ?>
                                    <td class="tac"><?= pg_fetch_result($resDadosFat, 0, "nome"); ?></td>
                                    <td><?= pg_fetch_result($resDadosFat, 0, "fone1"); ?></td>
                                    <td><?= pg_fetch_result($resDadosFat, 0, "fone2"); ?></td>
                                    <td class='email_cadastro'><?= pg_fetch_result($resDadosFat, 0, "email"); ?></td>
                                    <td><?= pg_fetch_result($resDadosFat, 0, "nota_fiscal"); ?></td>
                                    <td class="tac"><?= pg_fetch_result($resDadosFat, 0, "emissao"); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th class="titulo_coluna">Peça</th>
		    <th class="titulo_coluna">NF Origem</th>
                    <th class="titulo_coluna">Quantidade</th>
                    <th class="titulo_coluna">Valor Unit.</th>
                    <th class="titulo_coluna">Valor ICMS</th>
                    <th class="titulo_coluna">Valor IPI</th>
                    <th class="titulo_coluna">OS</th>
                </tr>
            </thead>
            <tbody>
                <? for($p = 0; $p < pg_num_rows($resDadosFatItem); $p++) {
                    $rPeca = pg_fetch_result($resDadosFatItem, $p, "peca");
		            $rNFOrigem = pg_fetch_result($resDadosFatItem, $p, "nota_fiscal_origem");
                    $rQtde = pg_fetch_result($resDadosFatItem, $p, "qtde");
                    $rOs = pg_fetch_result($resDadosFatItem, $p, "os");
                    $rPreco = pg_fetch_result($resDadosFatItem, $p, "preco");
                    $rBaseIcms = pg_fetch_result($resDadosFatItem, $p, "base_icms");
                    $rValorIcms = pg_fetch_result($resDadosFatItem, $p, "valor_icms");
                    $rValorIpi = pg_fetch_result($resDadosFatItem, $p, "valor_ipi"); ?>
                    <tr>
                        <td class="tac"><?= $rPeca; ?></td>
			<td class="tac"><?= $rNFOrigem; ?></td>
                        <td class="tac"><?= $rQtde; ?></td>
                        <td class="tac">R$ <?= number_format($rPreco, 2, ',', '.'); ?></td>
                        <td class="tac">R$ <?= number_format($rValorIcms, 2, ',', '.'); ?></td>
                        <td class="tac">R$ <?= number_format($rValorIpi, 2, ',', '.'); ?></td>
                        <td class="tac"><?= $rOs; ?></td>
                    </tr>
                <? } ?>
            </tbody>
        </table>

        <?
        unset($amazonTC, $anexos, $types);
        $amazonTC = new TDocs($con, $login_fabrica, "lgr");
        $anexos = array();
        $exibir_anexo = false;

        for ($i = 0; $i < 2; $i++) {
            $anexos["$i"]["nome"] = "nf_devolucao_{$faturamento}_{$login_fabrica}_anexo_nf_{$i}";
            $anexos["$i"]["url"] = $amazonTC->getDocumentsByName($anexos["$i"]["nome"], "lgr")->url;
            if (strlen($anexos["$i"]["url"]) > 0) {
                $exibir_anexo = true;
            }
        }

        if($exibir_anexo == true) { ?>

            <div id="div_informacoes" class="tc_formulario">
                <div class="titulo_tabela">ANEXOS</div>
                <br />
                <div class="row-fluid">
                    <div class="span3"></div>
                    <div class="tac">
                        <? foreach ($anexos as $value) {
                            if (strlen($value["url"]) > 0) {
                                $ext = pathinfo($value["url"], PATHINFO_EXTENSION);
                                $src = "";
                                if (strtolower($ext) == "pdf") {
                                    $src = 'imagens/pdf_icone.png';
                                } else if(in_array($ext, array('doc', 'docx'))) {
                                    $src = 'imagens/docx_icone.png';
                                } else if (strtolower($ext) == "xml") {
                                    $src = 'imagens/xml_icone.png';
                                } ?>
                                <div class="span2">
                                    <a href="<?= $value['url']; ?>" target="_blank">
                                        <img src="<?= $src; ?>" style="max-height: 80px !important; max-width: 80px !important;" border="0"><br />NF (<?= strtoupper($ext); ?>)
                                    </a>
                                </div>
                            <? }
                        } ?>
                    </div>
                    <div class="span3"></div>
                </div>
                <br />
            </div>
            <br />
            
        <? }

        $devolucao_concluida = pg_fetch_result($resDadosFat, 0, "devolucao_concluida");
        $cancelada = pg_fetch_result($resDadosFat, 0, "cancelada");
        $transportadora_nome = pg_fetch_result($resDadosFat, 0, "transportadora_nome");

        $sqlInteracoes = "
            SELECT
                a.nome_completo AS admin_nome,
                p.nome AS posto_nome,
                fi.interacao,
                TO_CHAR(fi.data_input, 'DD/MM/YY HH24:MI') AS data
            FROM tbl_faturamento_interacao fi
            LEFT JOIN tbl_admin a USING(admin,fabrica)
            LEFT JOIN tbl_posto_fabrica pf USING(posto,fabrica)
            LEFT JOIN tbl_posto p USING(posto)
            WHERE fi.fabrica = {$login_fabrica}
            AND fi.faturamento = {$faturamento}
            AND fi.ocorrencia IS NULL
            ORDER BY fi.data_input DESC;
        ";

        $resInteracoes = pg_query($con, $sqlInteracoes);
        $countInteracoes = pg_num_rows($resInteracoes);

        if ($countInteracoes > 0) { ?>
            <table id="resultado_pesquisa_pecas" class='table table-striped table-bordered table-hover table-large'>
                <thead>
                    <tr>
                        <td class="titulo_tabela tac" colspan="3">HISTÓRICO DE INTERAÇÕES</td>
                    </tr>
                    <tr>
                        <th class="titulo_coluna">Responsável</th>
                        <th class="titulo_coluna">Data</th>
                        <th class="titulo_coluna">Interação</th>
                    </tr>
                </thead>
                <tbody>
                    <? for ($t = 0; $t < $countInteracoes; $t++) {
                        $rAdmin = pg_fetch_result($resInteracoes, $t, "admin_nome");
                        $rPosto = pg_fetch_result($resInteracoes, $t, "posto_nome");
                        $rInteracao = pg_fetch_result($resInteracoes, $t, "interacao");
                        $rData = pg_fetch_result($resInteracoes, $t, "data");
                        $responsavel = (empty($rAdmin)) ? $rPosto : $rAdmin; ?>
                        <tr>
                            <td class="tac"><?= $responsavel; ?></td>
                            <td class="tac"><?= $rData; ?></td>
                            <td class="tac"><?= $rInteracao; ?></td>
                        </tr>
                    <? } ?>
                </tbody>
            </table>
        <? }

        if (empty($cancelada)) { ?>
            <form name="frm_interacao_lgr" id="frm_lgr" method="POST" class="form-search form-inline" enctype="multipart/form-data" >
                <div id="div_informacoes" class="tc_formulario">
                    <div class="titulo_tabela">INTERAGIR</div>
                    <br />
                    <? if ($areaAdmin === true && empty($devolucao_concluida)) { ?>
                        <div class="row-fluid">
                            <div class="span2"></div>
                            <div class="span4">
                                <div class='control-group <?=(in_array('autorizacao_coleta', $msg_erro['campos'])) ? "error" : "" ?>' >
                                    <label class="control-label" for="autorizacao_coleta">Autorização de Coleta</label>
                                    <div class="controls controls-row">
                                        <input type="text" id="autorizacao_coleta" name="autorizacao_coleta" class="span12" value='<?= $_REQUEST["autorizacao_coleta"]; ?>' />
                                    </div>
                                </div>
                            </div>
                            <?
                            if ($transportadora_nome == 'CORREIOS') { ?>
                                <input type="hidden" id="correios" name="correios" value="t" />
                                <div class="span4">
                                    <div class='control-group <?=(in_array('conhecimento', $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="conhecimento">E-Ticket</label>
                                        <div class="controls controls-row">
                                            <input type="text" id="conhecimento" name="conhecimento" class="span12" value='<?= $_REQUEST["conhecimento"]; ?>' />
                                        </div>
                                    </div>
                                </div>
                            <? } ?>
                            <div class="span2"></div>
                        </div>
                        <br />
                    <? } ?>
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span8">
                            <div class='control-group <?=(in_array('observacoes', $msg_erro['campos'])) ? "error" : "" ?>' >
                                <label class="control-label" for="observacoes">Observações</label>
                                <div class="controls controls-row">
                                    <textarea id="observacoes" name="observacoes" class="span12" style="height: 50px;" ><?= $_REQUEST["observacoes"]; ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <br />
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span8 tac">
                            <input type="hidden" name="btn_acao" id="btn_acao" value="">
                            <? if (empty($devolucao_concluida) && $areaAdmin === true) { ?>
                                <button type="button" class="btn btn-default" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('aprovar'); $('form[name=frm_interacao_lgr]').submit(); } else { alert('Aguarde! A aprovação está sendo processada.'); return false; }">Aprovar</button>
                                <button type="button" class="btn btn-default" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('reprovar'); $('form[name=frm_interacao_lgr]').submit(); } else { alert('Aguarde! O cancelamento está sendo processado.'); return false; }">Reprovar</button>
                            <? } else if (!empty($devolucao_concluida) && $areaAdmin === true) { ?>
                                <button type="button" class="btn btn-default" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('reenviar'); $('form[name=frm_interacao_lgr]').submit(); } else { alert('Aguarde! O reenvio está sendo processado.'); return false; }">Reenviar</button>
                            <? } ?>
                            <button type="button" class="btn btn-default" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('interagir'); $('form[name=frm_interacao_lgr]').submit(); } else { alert('Aguarde! A interação está sendo processada.'); return false; }">Interagir</button>
                        </div>
                        <div class="span2"></div>
                    </div>
                </div>
            </form>
        <? }
    }
} ?>

<script type="text/javascript">
    $(function() {
        <? if (!empty($msg_sucesso)) { ?>
                window.parent.atualiza_status(<?= $faturamento; ?>, '<?= $btn_acao; ?>', '<?= $autorizacao_coleta; ?>', '<?= $conhecimento; ?>');
        <? } ?>
    });

    <?php if (in_array($login_fabrica, array(169,170))){ ?>

        function isEmail(email) {
            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        }

        $(".altera_ac").click(function() {
            $("#div_atualizar_ac").show();
        })

        $(document).on('click','#atualizar_email', function(){
            if (confirm('O endereço de email está correto ?')) {
                var btn = $(this);
                var text = $(this).text();
                var id_faturamento = $(btn).data('faturamento');
                var novo_email = $("#novo_email").val();

                var validaEmail = isEmail(novo_email);

                if (novo_email == "" || novo_email == undefined){
                    alert("Favor informar o E-mail");
                    return false;
                }
                
                if (!validaEmail){
                    alert("O email informado é inválido.");
                    return false;
                }

                $(btn).prop({disabled: true}).text("Alterando...");
                $.ajax({
                    method: "POST",
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    data: { btn_acao: 'atualizar_email', id_faturamento: id_faturamento, novo_email: novo_email},
                    timeout: 8000
                }).fail(function(){
                    alert("Não foi possível atualizar o E-mail, tempo limite esgotado!");
                }).done(function(data) {
                    if (data == "success") {
                        $(btn).text("Alterado");
                        $(".email_cadastro").text(novo_email);
                        setTimeout(function(){
                            $("#div_atualizar_email").hide();
                        }, 2000);
                    }else{
                        $(btn).prop({disabled: false}).text("Alterar");
                        alert("Erro ao atualizar E-mail");
                    }
                });
            }else{
                return false;
            }
        });

        $(document).on('click','#atualizar_ac', function(){
            if (confirm('A nova AC está correta ?')) {
                var btn = $(this);
                var text = $(this).text();
                var id_faturamento = $(btn).data('faturamento');
                var novo_ac = $("#novo_ac").val();

                if (novo_ac == "" || novo_ac == undefined){
                    alert("Favor informar o AC");
                    return false;
                }
                
                $(btn).prop({disabled: true}).text("Alterando...");
                $.ajax({
                    method: "POST",
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    data: { btn_acao: 'atualizar_ac', id_faturamento: id_faturamento, novo_ac: novo_ac},
                    timeout: 8000
                }).fail(function(){
                    alert("Não foi possível atualizar o AC, tempo limite esgotado!");
                }).done(function(data) {
                    if (data == "success") {
                        $(btn).text("Alterado");
                        $(".ac_cadastro").text(novo_ac);
                        setTimeout(function(){
                            $("#div_atualizar_ac").hide();
                        }, 2000);
                    }else{
                        $(btn).prop({disabled: false}).text("Alterar");
                        alert("Erro ao atualizar AC");
                    }
                });
            }else{
                return false;
            }
        });

    <?php } ?>
</script>
