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

if (!empty($btn_acao)) {

    $ocorrencia     = $_REQUEST['ocorrencia'];
    $inf_etiqueta   = $_REQUEST['inf_etiqueta'];
    $aus_etiqueta   = $_REQUEST['aus_etiqueta'];
    $ref_nf         = $_REQUEST['ref_nf'];
    $ref_fisica     = $_REQUEST['ref_fisica'];
    $imagem         = $_FILES['anexo_ocorrencia'];

    if (count($_FILES) > 0) {
        $tem_anexos = array();
        foreach($_FILES as $key => $value) {
            $type = strtolower(preg_replace("/.+\//", "", $value["type"]));
            if (!empty($value['name'])) {
                $tem_anexos[] = "ok";
            }
        }

        // if (count($tem_anexos) != count($_FILES)) {
        //     $msg_erro["msg"]["anexo"] = "Anexos obrigatórios";
        // }
    }

    if (empty($ocorrencia)) {
        $msg_erro['msg'][] = "É necessário informar o tipo de ocorrência";
    }

    if ($ocorrencia == 't') {
        if (empty($inf_etiqueta) && empty($aus_etiqueta) && empty($ref_fisica) && empty($ref_nf)) {
            $msg_erro['msg'][] = "É necessário informar algum dos problemas para gravar a ocorrência";
        }
    }

    $dados_ocorrencia = array(
        "ocorrencia" => $ocorrencia,
        "inf_etiqueta" => $inf_etiqueta,
        "aus_etiqueta" => $aus_etiqueta,
        "ref_nf" => $ref_nf,
        "ref_fisica" => $ref_fisica
    );

    $dados_ocorrencia_json = json_encode($dados_ocorrencia);

    if (empty($dados_ocorrencia_json)) {
        $msg_erro['msg'][] = "Ocorreu um erro ao processar informações";
    }

    if (count($msg_erro['msg']) == 0) {

        try {

            pg_query($con, "BEGIN;");

            $instOco = "
                INSERT INTO tbl_faturamento_interacao (
                    admin,
                    faturamento,
                    fabrica,
                    ocorrencia
                ) VALUES (
                    {$login_admin},
                    {$faturamento},
                    {$login_fabrica},
                    '{$dados_ocorrencia_json}'
                ) RETURNING faturamento_interacao;
            ";

            $resInstOco = pg_query($con, $instOco);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Ocorreu um erro gravando as informações da devolução #001");
            }

            $faturamento_interacao = pg_fetch_result($resInstOco, 0, 0);

            unset($amazonTC, $image, $types);
            $amazonTC = new TDocs($con, $login_fabrica);
            $types = array("jpg", "jpeg");
            $erro_anexo = "";

            if ((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)) {
                $type = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                if (!in_array($type, $types)) {
                    $erro_anexo .= "Formato inválido, são aceitos os seguintes formatos: jpg ou jpeg<br />";
                } else {
                    $imagem['name'] = "ocorrencia_{$faturamento_interacao}_{$login_fabrica}.$type";
                    $subir_anexo = $amazonTC->uploadFileS3($imagem, $faturamento_interacao, false, "lgr", "ocorrencia");

                    if (!$subir_anexo) {
                        $erro_anexo .= "Erro ao gravar o anexo<br />";
                    }
                }
            }

            if (!empty($erro_anexo)) {
                throw new Exception($erro_anexo);
            }

            pg_query($con, "COMMIT;");
            $msg_sucesso = "Ocorrência gravada com sucesso";
            unset($_REQUEST['ocorrencia'], $_REQUEST['inf_etiqueta'], $_REQUEST['aus_etiqueta'], $_REQUEST['ref_nf'], $_REQUEST['ref_fisica']);
        } catch (Exception $e) {
            $msg_erro['msg'][] = $e->getMessage();
            pg_query($con, "ROLLBACK;");
        }
        
    }
}

if (count($msg_erro["msg"]) > 0) { ?>
    <br />
    <div class="alert alert-error"><h4><?= implode("<br />", $msg_erro["msg"]); ?></h4></div>
<? } else { ?>
    <? if (!empty($msg_sucesso)) { ?>
        <br />
        <div class="alert alert-success"><h4><?= $msg_sucesso; ?></h4></div>
    <? }
} ?>

<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>

<? if ($areaAdmin === true) { ?>

    <form name="frm_ocorrencia_lgr" id="frm_lgr" method="POST" class="form-search form-inline" enctype="multipart/form-data" >
        <div id="div_informacoes" class="tc_formulario">
            <div class="titulo_tabela">Ocorrência</div>
            <br />
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class='control-group <?=(in_array('ocorrencia', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="ocorrencia">Sem ocorrência</label>
                        <div class="controls controls-row">
                            <input type="radio" name="ocorrencia" value="f" />
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class='control-group <?=(in_array('ocorrencia', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="ocorrencia">Com ocorrência</label>
                        <div class="controls controls-row">
                            <input type="radio" name="ocorrencia" value="t" />
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span8">
                    <div class='control-group <?=(in_array('inf_etiqueta', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="inf_etiqueta">Falta Informações na Etiqueta, quais?</label>
                        <div class="controls controls-row">
                            <input type="radio" name="inf_etiqueta" value="Nome Autorizada" /> Nome Autorizada<br />
                            <input type="radio" name="inf_etiqueta" value="NF de devolucao" /> NF de devolução<br />
                            <input type="radio" name="inf_etiqueta" value="AC" /> AC<br />
                            <input type="radio" name="inf_etiqueta" value="OS" /> OS<br />
                            <input type="radio" name="inf_etiqueta" value="Mateiral novo ou com defeito" /> Material novo ou com defeito<br />
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <br />
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span8">
                    <div class='control-group <?=(in_array('aus_etiqueta', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="aus_etiqueta">Ausência de etiqueta?</label>
                        <div class="controls controls-row">
                            <select id="aus_etiqueta" name="aus_etiqueta" class="span12">
                                <option value=""></option>
                                <option value="Fisico">Físico</option>
                                <option value="Material">Material</option>
                                <option value="Fisico e Material">Físico e Material</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div class="tac">
                <b>Divergência físico x NF?</b>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class='control-group <?=(in_array('ref_nf', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="ref_nf">Código material mencionado na NF</label>
                        <div class="controls controls-row">
                            <input type="text" id="ref_nf" name="ref_nf" class="span12" value='<?= $_REQUEST["ref_nf"]; ?>' />
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class='control-group <?=(in_array('ref_fisica', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="ref_fisica">Código material físico</label>
                        <div class="controls controls-row">
                            <input type="text" id="ref_fisica" name="ref_fisica" class="span12" value='<?= $_REQUEST["ref_fisica"]; ?>' />
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class='control-group'>
                        <label for="anexo_ocorrencia">Imagem</label>
                        <div class="controls controls-row">
                            <input type="file" name="anexo_ocorrencia" />
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
                    <button type="button" class="btn btn-default" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('gravar'); $('form[name=frm_ocorrencia_lgr]').submit(); } else { alert('Aguarde! A ocorrência está sendo processada.'); return false; }">Gravar</button>
                </div>
                <div class="span2"></div>
            </div>
        </div>
    </form>

<? }

if ($_REQUEST['faturamento']) {

    $faturamento = $_REQUEST['faturamento'];

    $sqlDadosOco = "
        SELECT 
            fi.faturamento_interacao,
            a.admin,
            a.nome_completo AS admin_nome,
            TO_CHAR(fi.data_input, 'DD/MM/YY HH24:MI') AS data,
            fi.ocorrencia AS dados_ocorrencia
        FROM tbl_faturamento_interacao fi
        JOIN tbl_admin a ON a.admin = fi.admin AND a.fabrica = {$login_fabrica}
        WHERE fi.fabrica = {$login_fabrica}
        AND fi.faturamento = {$faturamento}
        AND fi.interacao IS NULL
        ORDER BY fi.data_input, a.admin DESC;
    ";
    $resDadosOco = pg_query($con, $sqlDadosOco);
    $countOcorrencias = pg_num_rows($resDadosOco);

    if ($countOcorrencias > 0) { ?>
        <table id="resultado_ocorrencias" class='table table-bordered table-large'>
            <thead>
                <tr>
                    <th class="titulo_tabela tac" colspan="7">HISTÓRICO DE OCORRÊNCIAS</th>
                </tr>
                <tr>
                    <th class="titulo_coluna">Responsável</th>
                    <th class="titulo_coluna">Data</th>
                    <th class="titulo_coluna">Ocorrência</th>
                    <th class="titulo_coluna">Falta inf. Etiqueta?</th>
                    <th class="titulo_coluna">Ausência Etiqueta?</th>
                    <th class="titulo_coluna">Diverg. física x NF?</th>
                    <th class="titulo_coluna">Imagem</th>
                </tr>
            </thead>
            <tbody>
                <?
                for ($t = 0; $t < $countOcorrencias; $t++) {
                    $oFatInteracao = pg_fetch_result($resDadosOco, $t, "faturamento_interacao");
                    $oAdminId = pg_fetch_result($resDadosOco, $t, "admin");
                    $oAdmin = pg_fetch_result($resDadosOco, $t, "admin_nome");
                    $oData = pg_fetch_result($resDadosOco, $t, "data");
                    $oDadosOcorrencia = pg_fetch_result($resDadosOco, $t, "dados_ocorrencia");
                    $oDadosOcorrencia = json_decode($oDadosOcorrencia, true);
                    $oOcorrencia = $oDadosOcorrencia['ocorrencia'];
                    $oInfEtiqueta = $oDadosOcorrencia['inf_etiqueta'];
                    $oAusEtiqueta = $oDadosOcorrencia['aus_etiqueta'];
                    $oRefNf = $oDadosOcorrencia['ref_nf'];
                    $oRefFisica = $oDadosOcorrencia['ref_fisica'];

                    unset($amazonTC, $anexos, $types);
                    $amazonTC = new TDocs($con, $login_fabrica, "lgr");
                    $anexo = array();
                    $exibir_anexo = false;

                    $anexo["nome"] = "ocorrencia_{$oFatInteracao}_{$login_fabrica}";
                    $anexo["url"] = $amazonTC->getDocumentsByName($anexo["nome"], "ocorrencia")->url;

                    if (strlen($anexo["url"]) > 0) {
                        $exibir_anexo = true;
                    }

                    if ($oOcorrencia == 't') {
                        $auxOcorrencia = "Sim";
                    } else {
                        $auxOcorrencia = "Não";
                    }

                    if (!empty($oRefNf) && !empty($oRefFisica)) {
                        $auxRef = "Ref. NF: {$oRefNf} / Ref. Física: {$oRefFisica}";
                    } else {
                        $auxRef = "";
                    } ?>
                    <tr>
                        <td class="tac"><?= $oAdmin; ?></td>
                        <td class="tac"><?= $oData; ?></td>
                        <td class="tac"><?= $auxOcorrencia; ?></td>
                        <td class="tac"><?= $oInfEtiqueta; ?></td>
                        <td class="tac"><?= $oAusEtiqueta; ?></td>
                        <td class="tac"><?= $auxRef; ?></td>
                        <td>
                            <? if ($exibir_anexo === true) { ?>
                                <a href="<?= $anexo['url']; ?>" target="_blank">
                                    <img src="<?= $anexo['url']; ?>" style="max-height: 80px !important; max-width: 80px !important;" border="0" />
                                </a>
                            <? } ?>
                        </td>
                    </tr>
                <? } ?>
            </tbody>
        </table>
    <? }
} ?>

<script type="text/javascript">
    $(function() {
        <? if (!empty($msg_sucesso)) { ?>
                window.parent.atualiza_status(<?= $faturamento; ?>, '<?= $btn_acao; ?>');
        <? } ?>
    });
</script>