<?
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include_once __DIR__.'/funcoes.php';

$array_estados = $array_estados();
/*$array_estados = array_map(function($e) {
    return utf8_decode($e);
}, $array_estados);*/

include_once "class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("os", $login_fabrica);
$anexaS3 = new anexaS3('ve', (int) $login_fabrica);

if (isset($_POST['ajax_anexo_upload'])) {
    $posicao = $_POST['anexo_posicao'];
    $chave   = $_POST['anexo_chave'];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));
    $extx = $ext;

    if ($ext == 'jpeg') {
        $ext = 'jpg';
    }

    if (strlen($arquivo['tmp_name']) > 0) {
        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx'))) {
            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx'));
        } else {
            $arquivo_nome = "{$chave}_{$posicao}";

            $s3->tempUpload("{$arquivo_nome}", $arquivo);

            if($ext == 'pdf'){
                $link = 'imagens/pdf_icone.png';
            } else if(in_array($ext, array('doc', 'docx'))) {
                $link = 'imagens/docx_icone.png';
            } else {
                $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);
                if(strlen($link) == 0 ) {
                    $link = $s3->getLink("thumb_{$arquivo_nome}.{$extx}", true);
                    $ext = $extx;
                }
            }

            $href = $s3->getLink("{$arquivo_nome}.{$ext}", true);
            if(strlen($href) == 0) {
                $href = $s3->getLink("{$arquivo_nome}.{$extx}", true);
                $ext = $extx;
            }
            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
            } else {
                $retorno = array('link' => $link, 'arquivo_nome' => "{$arquivo_nome}.{$ext}", 'href' => $href, 'ext' => $ext);
            }
        }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
    }

    $retorno['posicao'] = $posicao;

    exit(json_encode($retorno));
}

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
        $sql = "
            SELECT DISTINCT * FROM (
                SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                UNION
                SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
            ) AS cidade
            ORDER BY cidade ASC;
        ";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("estado não encontrado"));
    }

    exit(json_encode($retorno));
}

if (isset($_POST["ajax_busca_defeito_constatado"]) && !empty($_POST["produto"])) {
    $produto = $_POST["produto"];
    $fora_garantia = $_POST["fora_garantia"];
    $grupo_atendimento = $_POST["grupo_atendimento"];
    $tipo_atendimento_descricao = $_POST["tipo_atendimento_descricao"];
    $defeitos_constatados_selecionados = $_POST['defeitos_selecionados'];
    $tipo_atendimento = $_POST['tipo_atendimento'];
    $grupo = $_POST['grupo'];

    if (!empty($defeitos_constatados_selecionados)) {
        $whereSelecionados = "AND tbl_defeito_constatado.defeito_constatado NOT IN ($defeitos_constatados_selecionados)";
    }

    if ($usa_linha_defeito_constatado == 't'){
        $join_linha_familia_produto = " JOIN tbl_linha ON tbl_linha.linha = tbl_diagnostico.linha AND tbl_linha.fabrica = {$login_fabrica}
                   JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = {$login_fabrica} ";
    }else{
        $join_linha_familia_produto = " JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                   JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica} ";
    }


    $join_defeito_grupo   = "";
    $where_defeito_grupo  = "";
    if (strlen($grupo) > 0){
        $join_defeito_grupo   = " JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo AND tbl_defeito_constatado_grupo.fabrica = {$login_fabrica}";
        $where_defeito_grupo  = " AND tbl_defeito_constatado_grupo.defeito_constatado_grupo = {$grupo}";
    }

    $sql = "
        SELECT DISTINCT
            tbl_defeito_constatado.defeito_constatado,
            tbl_defeito_constatado.codigo,
            tbl_defeito_constatado.descricao,
            tbl_defeito_constatado.lancar_peca,
            tbl_defeito_constatado.lista_garantia,
        tbl_defeito_constatado.defeito_constatado_grupo
        FROM tbl_diagnostico
        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
        {$join_linha_familia_produto}
        {$join_defeito_grupo}
        WHERE tbl_diagnostico.fabrica = {$login_fabrica}
        AND tbl_produto.produto = {$produto}
        AND tbl_diagnostico.ativo IS TRUE
        {$whereTipoAtendimento}
        {$whereSelecionados}
        {$where_defeito_grupo}
        ORDER BY tbl_defeito_constatado.descricao ASC";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $defeito_constatado_array = array();

        while ($result = pg_fetch_object($res)) {
            $defeito_constatado_array[] = array(
                "descricao" => utf8_encode($result->descricao),
                "lancar_pecas" => $result->lancar_peca,
                "defeito_constatado" => $result->defeito_constatado,
                "lista_garantia" => $result->lista_garantia,
                "defeito_constatado_grupo" => $result->defeito_constatado_grupo
            );
        }

        if (isset($fabrica_usa_subproduto)) {
            $sql = "SELECT CASE WHEN produto_pai = {$produto} THEN produto_filho ELSE produto_pai END AS subproduto FROM tbl_subproduto WHERE produto_pai = {$produto} OR produto_filho = {$produto}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $subproduto = pg_fetch_result($res, 0, "subproduto");

                $sql = "SELECT distinct tbl_defeito_constatado.defeito_constatado, tbl_defeito_constatado.descricao
                        FROM tbl_diagnostico
                        INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
                        INNER JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                        INNER JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
                        WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                        AND tbl_produto.produto = {$subproduto}
                        AND tbl_diagnostico.ativo IS TRUE
                        ORDER BY tbl_defeito_constatado.descricao ASC";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $subproduto_defeito_constatado_array = array();

                    while ($result = pg_fetch_object($res)) {
                        $subproduto_defeito_constatado_array[$result->defeito_constatado] = utf8_encode($result->descricao);
                    }
                }
            }
        }

        $retorno = array("defeitos_constatados" => $defeito_constatado_array);

        if (isset($subproduto_defeito_constatado_array)) {
            $retorno["subproduto_defeitos_constatados"] = $subproduto_defeito_constatado_array;
        }
    } else {
        $retorno = array("error" => utf8_encode(traduz("nenhum.defeito.constatado.encontrado.para.a.familia.do.produto")));
    }

    exit(json_encode($retorno));
}

if (isset($_POST["ajax_busca_grupo_defeito_constatado"]) && !empty($_POST["produto"])) {   
    
    $produto = $_POST["produto"];

    $sql = "SELECT defeito_constatado_grupo, descricao
              FROM tbl_defeito_constatado_grupo
             WHERE fabrica = {$login_fabrica}
          ORDER BY descricao ASC";

    if (in_array($login_fabrica, [178])) {
        if ($usa_linha_defeito_constatado == 't'){
            $join_linha_familia_produto = " JOIN tbl_linha ON tbl_linha.linha = dg.linha AND tbl_linha.fabrica = {$login_fabrica}
                       JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = {$login_fabrica} ";
        }else{
            $join_linha_familia_produto = " JOIN tbl_familia ON tbl_familia.familia = dg.familia AND tbl_familia.fabrica = {$login_fabrica}
                       JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica} ";
        }

        $sql = "SELECT dcg.defeito_constatado_grupo, dcg.descricao
            FROM tbl_defeito_constatado_grupo AS dcg
                JOIN tbl_defeito_constatado   AS dc ON dc.defeito_constatado_grupo = dcg.defeito_constatado_grupo AND dc.fabrica = {$login_fabrica}
                JOIN tbl_diagnostico          AS dg ON dg.defeito_constatado       = dc.defeito_constatado        AND dg.fabrica = {$login_fabrica}
                {$join_linha_familia_produto}
            WHERE dcg.fabrica           = {$login_fabrica}
                AND dg.fabrica          = {$login_fabrica} 
                AND tbl_produto.produto = {$produto} 
                AND dg.ativo IS TRUE
            GROUP BY dcg.defeito_constatado_grupo
            ORDER BY dcg.descricao ASC";
    }   

    $res = pg_query($con, $sql);
    $option = "<option value=''>Selecione</option>";
    if (pg_num_rows($res) > 0) {
        $grupo_defeito_constatado_array = array();

        while ($result = pg_fetch_object($res)) {
            $option .= "<option value='".$result->defeito_constatado_grupo."'>".$result->descricao."</option>";
        }
    } 

    exit($option);
}

if ($login_fabrica == 175){
    if ($_POST["ajax"] == "sim" AND $_POST["acao"] == "valida_data_venda"){
        $produto        = $_POST["produto"];
        $serie          = $_POST["serie"];
        $data_abertura  = $_POST["data_abertura"];
        $nota_fiscal    = $_POST["nota_fiscal"];
        $data_nf        = $_POST['data_nf'];

        $sql = "
            SELECT  tbl_numero_serie.data_venda,
                    tbl_produto.garantia
            FROM    tbl_numero_serie
            JOIN    tbl_produto USING(produto)
            WHERE   tbl_numero_serie.fabrica = {$login_fabrica}
            AND     tbl_numero_serie.serie = '{$serie}'
            AND     tbl_produto.fabrica_i = $login_fabrica
            AND     tbl_produto.produto = $produto";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0){
            $data_venda = pg_fetch_result($res, 0, 'data_venda');
            $garantia = pg_fetch_result($res, 0, 'garantia');
            

            if ($nota_fiscal != "semNota"){
                $fora_garantia = false;

                if (!empty($data_nf) && !empty($data_venda)) {
                    $fora_garantia = (strtotime(formata_data($data_venda)) < strtotime(formata_data($data_abertura))) ? true : false;
                }

                if (empty($data_venda) || $fora_garantia === true) {
                    $fora_garantia = (strtotime(formata_data($data_nf)." +{$garantia} months") < strtotime(formata_data($data_abertura))) ? true : false;
                }

            } else {
                $fora_garantia = (strtotime(formata_data($data_venda)." +{$garantia} months") < strtotime(formata_data($data_abertura))) ? true : false;
            }

            if ($fora_garantia === true){
                exit(json_encode(array("ok" => "fora_garantia")));
            }else{
                exit(json_encode(array("ok" => "nao_valida")));
            }

            /*if ($nota_fiscal != "semNota"){
                $fora_garantia = (strtotime(formata_data($data_nf)." +{$garantia} months") < strtotime(formata_data($data_abertura))) ? true : false;
                if ($fora_garantia === true){
                    exit(json_encode(array("ok" => "fora_garantia")));
                }else{
                    exit(json_encode(array("ok" => "nao_valida")));
                }
            }else{
                $fora_garantia_venda = (strtotime("$data_venda+{$garantia} months") < strtotime(formata_data($data_abertura))) ? true : false;
                if ($fora_garantia_venda === true){
                    exit(json_encode(array("ok" => "fora_garantia")));
                }else{
                    exit(json_encode(array("ok" => "nao_valida")));
                }
            }*/
        }
        exit(json_encode(array("ok" => "erro_serie")));
    }
}

if ($login_fabrica == 178){

    if (isset($_POST["valida_data_nota_fiscal"])){
        $data_nf_atualizada = $_POST["data_nf_atualizada"];
        $data_abertura      = $_POST["data_abertura"];

        list($dnf, $mnf, $ynf) = explode("/", $data_nf_atualizada);
        list($da, $ma, $ya) = explode("/", $data_abertura);

        if (!checkdate($mnf, $dnf, $ynf) or !checkdate($ma, $da, $ya)) {
            exit(json_encode(array("ok" => "error", "result" => utf8_encode("Data inválida"))));
        } else {
            $aux_data_nf_atualizada = "{$ynf}-{$mnf}-{$dnf}";
            $aux_data_abertura      = "{$ya}-{$ma}-{$da}";

            if (strtotime($aux_data_abertura) < strtotime($aux_data_nf_atualizada)) {
                exit(json_encode(array("error" => "error", "result" => utf8_encode("Data Nota Fiscal não pode ser maior que a Data Abertura"))));
            }else{
                exit(json_encode(array("success" => "success", "result" => utf8_encode("Data atualizada com sucesso."))));
            }
        }
        exit;
    }

    if (isset($_POST["ajax_busca_defeito_reclamado"]) && !empty($_POST["produto"])) {       

        $produto = $_POST["produto"];
       
        $sql = "SELECT
                DISTINCT tbl_defeito_reclamado.defeito_reclamado,
                tbl_defeito_reclamado.codigo,
                tbl_defeito_reclamado.descricao
            FROM tbl_diagnostico
            INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica} AND tbl_defeito_reclamado.ativo IS TRUE
            INNER JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
            INNER JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
            WHERE tbl_diagnostico.fabrica = {$login_fabrica}
            AND tbl_produto.produto = {$produto}
            AND tbl_diagnostico.ativo IS TRUE
            ORDER BY tbl_defeito_reclamado.codigo ASC, tbl_defeito_reclamado.descricao ASC";
        $res = pg_query($con, $sql);
        $defeitos_reclamados = array();

        if(pg_num_rows($res) > 0){
            for($i = 0; $i < pg_num_rows($res); $i++){
                $defeito_reclamado = pg_fetch_result($res, $i, "defeito_reclamado");
                $descricao         = pg_fetch_result($res, $i, "descricao");
                $codigo            = pg_fetch_result($res, $i, "codigo");
                $defeitos_reclamados[] = array("defeito_reclamado" => utf8_encode($defeito_reclamado), "descricao" => utf8_encode($descricao));
            }
        }else{
            $defeitos_reclamados[] = array("defeito_reclamado" => "", "descricao" => "");
        }
        exit(json_encode(array("defeitos_reclamados" => $defeitos_reclamados)));
    }

    if ($_POST["ajax"] == "sim" AND $_POST["acao"] = "marcas_produto"){
        $marcas = $_POST["marcas"];
        $marcas = json_decode($marcas, true);

        $sqlMarcas = "
            SELECT marca, nome 
            FROM tbl_marca 
            WHERE fabrica = $login_fabrica
            AND marca IN (".$marcas['marcas'].")
        ";
        $resMarcas = pg_query($con, $sqlMarcas);

        if (pg_num_rows($resMarcas) > 0){
            $array_marcas = pg_fetch_all($resMarcas);
            exit(json_encode(array("ok" => "success", "result" => $array_marcas)));
        }else{
            exit(json_encode(array("ok" => "error")));
        }
        exit;
    }

    if (isset($_POST['carrega_tecnico']) AND $_POST['carrega_tecnico'] == true AND $areaAdmin == true){
        $posto = $_POST['posto'];

        $sql = "SELECT tecnico, nome FROM tbl_tecnico WHERE posto = $posto AND fabrica = $login_fabrica AND ativo IS TRUE";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0){
            $retorno = pg_fetch_all($res);
            exit(json_encode(array("succes"=>"ok", "dados" => $retorno)));
        }else{
            exit(json_encode(array("error"=>"ok", "dados" => "Nenhum técnico encontrado")));
        }
        exit;
    }
}

if ($login_fabrica == 183){
    if (isset($_POST['ajax_listar_produtos_nf']) AND $_POST['ajax_listar_produtos_nf'] == true){
        $posto       = $_POST["posto"];
        $nota_fiscal = $_POST["nota_fiscal"];
        $data_nf     = $_POST["data_nf"];
        
        list($d, $m, $y) = explode("/", $data_nf);

        if (!checkdate($m, $d, $y)) {
            $error["msg"][]    = "Data Inválida";
        } else {
            $aux_data_nf   = "{$y}-{$m}-{$d}";
        }
        
        $sql = "
            SELECT
                tbl_produto.referencia,
                tbl_produto.descricao,
                tbl_produto.produto,
                tbl_venda.serie,
                tbl_venda.nota_fiscal,
                TO_CHAR(tbl_venda.data_nf, 'DD/MM/YYYY') AS data_nf,
                tbl_venda.qtde
            FROM tbl_venda
            JOIN tbl_produto ON tbl_produto.produto = tbl_venda.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            WHERE fabrica = {$login_fabrica} 
            AND posto = {$posto} 
            AND nota_fiscal = '{$nota_fiscal}' 
            AND data_nf = '{$aux_data_nf}'";
        $res = pg_query($con, $sql);
        
        if (pg_num_rows($res) > 0){
            $dados = pg_fetch_all($res);
            exit(json_encode(array("ok" => "success", "result" => $dados)));
        }else{
            exit(json_encode(array("ok" => "error")));
        }
        exit;
    }

    if (isset($_POST["ajax_busca_defeito_reclamado"]) && !empty($_POST["produto"])) {       

        $produto = $_POST["produto"];
        
        $sql = "SELECT
                DISTINCT tbl_defeito_reclamado.defeito_reclamado,
                tbl_defeito_reclamado.codigo,
                tbl_defeito_reclamado.descricao
            FROM tbl_diagnostico
            INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica} AND tbl_defeito_reclamado.ativo IS TRUE
            INNER JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
            INNER JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
            WHERE tbl_diagnostico.fabrica = {$login_fabrica}
            AND tbl_produto.produto = {$produto}
            AND tbl_diagnostico.ativo IS TRUE
            ORDER BY tbl_defeito_reclamado.codigo ASC, tbl_defeito_reclamado.descricao ASC";
        $res = pg_query($con, $sql);
        $defeitos_reclamados = array();

        if(pg_num_rows($res) > 0){
            for($i = 0; $i < pg_num_rows($res); $i++){
                $defeito_reclamado = pg_fetch_result($res, $i, "defeito_reclamado");
                $descricao         = pg_fetch_result($res, $i, "descricao");
                $codigo            = pg_fetch_result($res, $i, "codigo");
                $defeitos_reclamados[] = array("defeito_reclamado" => utf8_encode($defeito_reclamado), "descricao" => utf8_encode($descricao));
            }
        }else{
            $defeitos_reclamados[] = array("defeito_reclamado" => "", "descricao" => "");
        }
        exit(json_encode(array("defeitos_reclamados" => $defeitos_reclamados)));
    }
}

include "os_cadastro_unico/fabricas/os_revenda.php";

$layout_menu = ($areaAdmin) ? 'callcenter' : 'os';
if ($login_fabrica == 178){
    $title = traduz("CADASTRO DE ORDEM DE SERVIÇO");
}else{
    $title = traduz("CADASTRO DE ORDEM DE SERVIÇO DE REVENDA");
}

if ($areaAdmin === true) {
    include __DIR__.'/admin/cabecalho_new.php';
} else {
    include __DIR__.'/cabecalho_new.php';
}

if (strlen($os_revenda) == 0 && strlen($_REQUEST['os_revenda']) == 0 && $areaAdmin === false) {
    $sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica};";
    $res = @pg_query($con,$sql);
    $digita_os = pg_fetch_result ($res,0,0);

    if ($digita_os == 'f' ) {
        include __DIR__.'/cabecalho_new.php'; ?>
        <br />
        <br />
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Sem Permissão para cadastrar Ordem de Serviço</th>
                </tr>
            </thead>

        </table>
        <br />
        <br />
        <?
        include "rodape.php";
        exit;
    }
}

// 'nome' único de arquivo para trabalhar com anexos
if (!strlen(getValue("anexo_chave"))) {
    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
} else {
    $anexo_chave = getValue("anexo_chave");
}

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet"
);

include __DIR__.'/admin/plugin_loader.php'; ?>

<style type="text/css">
#modelo_produto, #modelo_nota_fiscal, #modelo_sem_nota_fiscal, #div_trocar_posto { display:none; }
#google_maps {
    width: 90%;
    float: left;
    z-index: 1;
}

<?php if ($login_fabrica == 183){ ?>
    .box_defeito_reclamado{
        margin-left: 25px!important;
        margin-top: 0px!important;
        padding-bottom: 0px!important;
    }
    
    .box_defeito_constatado{
        margin-left: 71px!important;
        margin-top: 5px!important;
        padding-bottom: 25px!important;
    }

<?php }else{ ?>
    .box_grupo_defeito_constatado{
        margin-top: 5px!important;
        padding-bottom: 25px!important;
    }

    .box_defeito_reclamado{
        margin-left: 71px!important;
        margin-top: 5px!important;
        padding-bottom: 25px!important;
    }

    .box_defeito_constatado{
        margin-top: 5px!important;
        padding-bottom: 25px!important;
    }
<?php } ?>
.box_fora_linha{
    margin-left: 71px!important;
    padding-bottom: 25px!important;
    font-weight: bold;
    margin-top: -8px;
    color: #ff352f;
}
.box_instalacao_publica{
    margin-left: 18px!important;
    margin-top: 5px!important;
    padding-bottom: 25px!important;
}
.btn_lista_basica{
    margin-top: 24px;
}

.btn_peca_servico{
    margin-top: 24px;
}

<?php if ($login_fabrica == 178){ ?>
    .style_178 {
        margin-left: 72px !important;
        margin-top: 5px !important;
        margin-bottom: 30px !important;
    }
<?php } ?>
</style>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <br />
    <div class="alert alert-error"><h4><?= implode("<br />", $msg_erro["msg"]); ?></h4></div>
    <? if ($erro_carrega_os_revenda) {
        include "rodape.php";
        exit;
    }
} else { ?>
    <br />
<? } ?>

<form name="frm_os_revenda" id="frm_os_revenda" method="POST" class="form-search form-inline" enctype="multipart/form-data" >
    <input type="hidden" id="fabrica_id" name="fabrica_id" value="<?=$login_fabrica?>" />
    <input type="hidden" id="revenda_id" name="revenda" value="<?= getValue('revenda'); ?>" />

    <? if ($areaAdmin === true) {
        if (!empty(getValue('posto_id'))) {
            $sql = "
                SELECT
                    latitude,
                    longitude
                FROM tbl_posto_fabrica
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND tbl_posto_fabrica.posto = ".getValue('posto_id').";
            ";
        }
    } else { 
        $sql = "
            SELECT
                latitude,
                longitude
            FROM tbl_posto_fabrica
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.posto = {$login_posto};
        ";
    }

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $posto_latitude  = pg_fetch_result($res, 0, "latitude");
        $posto_longitude = pg_fetch_result($res, 0, "longitude");
    } ?>

    <input type="hidden" id="posto_latitude" value="<?= $posto_latitude; ?>" disabled="disabled" />
    <input type="hidden" id="posto_longitude" value="<?= $posto_longitude; ?>" disabled="disabled" />
    
    <div class="row">
        <strong class="obrigatorio pull-right">* Campos obrigatórios</strong>
    </div>

    <? if ($areaAdmin === true) {
        if ((count($msg_erro["msg"]) > 0 && strlen(getValue("posto_id")) > 0) && !strlen($os)) {
            $posto_readonly     = "readonly='readonly'";
            $posto_esconde_lupa = "style='display: none;'";
            $posto_mostra_troca = "style='display: block;'";
        }

        if (strlen($os) > 0 && strlen(getValue("posto_id")) > 0) {
            $posto_readonly     = "readonly='readonly'";
            $posto_esconde_lupa = "style='display: none;'";
        } ?>

        <div id="div_informacoes_posto" class="tc_formulario">
            <div class="titulo_tabela">Informações do Posto Autorizado</div>
            <br />
            <input type="hidden" id="posto_id" name="posto_id" value="<?= getValue('posto_id'); ?>" />
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3">
                    <div class='control-group <?=(in_array('posto_id', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="posto_codigo">Código</label>
                        <div class="controls controls-row">
                            <div class="span10 input-append">
                                <h5 class="asteristico">*</h5>
                                <input id="posto_codigo" name="posto_codigo" class="span12" type="text" value="<?=getValue('posto_codigo')?>" <?=$posto_readonly?> />
                                <span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span5">
                    <div class='control-group <?=(in_array('posto_id', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="posto_nome">Nome</label>
                        <div class="controls controls-row">
                            <div class="span11 input-append">
                                <h5 class="asteristico">*</h5>
                                <input id="posto_nome" name="posto_nome" class="span12" type="text" value="<?=getValue('posto_nome')?>" <?=$posto_readonly?> />
                                <span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>

            <div id="div_trocar_posto" class="row-fluid" <?=$posto_mostra_troca?> >
                <div class="span2"></div>
                <div class="span10">
                    <button type="button" id="trocar_posto" class="btn btn-danger" >Alterar Posto Autorizado</button>
                </div>
            </div>
        </div>
        <br />
    <? } else { ?>
        <input type="hidden" id="posto_id" name="posto_id" value="<?= ($login_fabrica == 183 AND $login_tipo_posto_codigo == "Rep") ? getValue('posto_id') : $login_posto; ?>" />
    <? } ?>
    <div id="div_informacoes_os_revenda" class="tc_formulario" style="padding-bottom: 30px;">
        <div class="titulo_tabela">Informações da OS <?=(in_array($login_fabrica, array(178,183))) ? "" : "Revenda" ?></div>
        <br />
        <div class="row-fluid">
            <div class="span1"></div>
            <?php if (!in_array($login_fabrica, array(178,183))){ ?>
            <div class="span2">
                <div class='control-group <?=(in_array('sua_os', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="sua_os"><?= (in_array($login_fabrica, array(169,170))) ? "OS Revendedor" : "OS Fabricante"; ?></label>
                    <div class="controls controls-row">
                        <div class="input-append">
                            <? if ($regras["sua_os"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="sua_os" name="sua_os" class="span12" type="text" maxlength="20" value="<?= getValue('sua_os'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
            <div class="span2">
                <div class='control-group <?=(in_array('data_abertura', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="data_abertura">Data Abertura</label>
                    <div class="controls controls-row">
                        <div class="input-append">
                            <? if ($regras["data_abertura"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="data_abertura" name="data_abertura" class="span12" type="text" value="<?= getValue('data_abertura'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <? if (!in_array($login_fabrica, array(139,169,170,175,178,183))) { ?>
                <div class="span3">
                    <div class='control-group <?=(in_array('tipo_atendimento', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="tipo_atendimento">Tipo Atendimento</label>
                        <div class="controls controls-row">
                            <div class="input-append">
                                <? if ($regras["tipo_atendimento"]["obrigatorio"] == true) { ?>
                                    <h5 class='asteristico'>*</h5>
                                <? } ?>
                                <select name="tipo_atendimento" class="span12 tipo_atendimento">
                                    <option value=''>Selecione...</option>
                                <?
                                if ($login_fabrica == 177){
                                    $whereGrupoAtendimento = " AND tbl_tipo_atendimento.grupo_atendimento IS NULL ";
                                }

                                if ($login_fabrica == 144 && !verifica_tipo_posto("posto_interno", "TRUE", $login_posto)) {
                                    $whereDeslocamento = "AND km_google IS NOT TRUE";
                                }

                                if (in_array($login_fabrica, [178])) {
                                    $produtosAdicionados = getValue('produtos');
                                    unset($produtosAdicionados['__modelo__']);
                                    
                                    foreach ($produtosAdicionados as $p => $linhaProduto) {
                                        
                                        if (!empty($produtosAdicionados[$p]["parametros_adicionais_os_item"])){
                                            $param_adicionais = json_decode($produtosAdicionados[$p]["parametros_adicionais_os_item"], true);
                                            $count            = json_decode($param_adicionais["info_pecas"], true);

                                            foreach ($count as $key => $value) {
                                                $qtdex+=$value["qtde_lancada"];
                                            }
                                        }
                                    } 

                                    $notDisabled = ($qtdex > 0) ? false : true;
                                }

                                $sqlTpAtendimento = "
                                    SELECT
                                        tipo_atendimento,
                                        descricao,
                                        fora_garantia,
                                        km_google,
                                        entrega_tecnica AS visita_tecnica,
                                        grupo_atendimento
                                    FROM tbl_tipo_atendimento
                                    WHERE fabrica = {$login_fabrica}
                                    AND ativo IS TRUE
                                    {$whereGrupoAtendimento}
                                    {$whereDeslocamento}
                                    ORDER BY descricao DESC;
                                ";
                                $resTpAtendimento = pg_query($con, $sqlTpAtendimento);

                                while($result = pg_fetch_object($resTpAtendimento)) {
                                    $selected = ($result->tipo_atendimento == getValue('tipo_atendimento')) ? "selected" : "";
                                    $disabled = ($result->tipo_atendimento != getValue('tipo_atendimento') && !empty(getValue('os_revenda'))) ? "disabled" : "";
                                    $disabled = (in_array($login_fabrica, [178]) AND $notDisabled == true) ? '' : $disabled;                                    

                                        if($areaAdmin == false and $result->fora_garantia == 't'){
                                            continue;
                                        }
                                    ?>
                                    <option value="<?= $result->tipo_atendimento; ?>" km_google="<?= $result->km_google; ?>" fora_garantia="<?= $result->fora_garantia; ?>" visita_tecnica="<?= $result->visita_tecnica; ?>" grupo_atendimento="<?= $result->grupo_atendimento; ?>" <?= $selected." ".$disabled; ?>><?= $result->descricao; ?></option>
                                <? } ?>
                            </select>
                            </div>
                        </div>
                    </div>
                </div>
            <? } ?>

            <?php if (in_array($login_fabrica, array(178,183))){
                $consumidor_revenda = $_REQUEST["consumidor_revenda"];
                $display_consumidor_revenda = "";

                if (empty($consumidor_revenda)){
                    $consumidor_revenda = getValue("consumidor_revenda");
                }

                if ($login_fabrica == 183){
                    if ($login_tipo_posto_codigo == "Rev" OR $login_tipo_posto_codigo == "Rep"){
                        $consumidor_revenda = "R";
                        $display_consumidor_revenda = "style='display: none;'";
                    }
                }
            ?>
                <div class="span3" <?=$display_consumidor_revenda?> >
                    <input type="hidden" id="sua_os" name="sua_os" class="span12" maxlength="50" value="<?=$os_revenda?>" />
                            
                    <div class='control-group <?=(in_array('consumidor_revenda', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_revenda">Tipo OS</label>
                        <div class="controls controls-row">
                            <div class="input-append">
                                <? if ($regras["consumidor_revenda"]["obrigatorio"] == true) { ?>
                                    <h5 class='asteristico'>*</h5>
                                <? } ?>
                                <select name="consumidor_revenda" class="span12 consumidor_revenda">
                                    <?php
                                        if (empty($os_revenda) OR strlen(trim($consumidor_revenda)) == 0){
                                            echo "<option value=''>Selecione...</option>";
                                        }
                                        
                                        if ($login_fabrica == 183){
                                            $array_tipos_os = array("R" => "Revenda");
                                        }else{
                                            $array_tipos_os = array( "S" => "Construtora", "C" => "Consumidor", "R" => "Revenda");
                                        }

                                        foreach ($array_tipos_os as $valor => $descricao) {
                                            $selected_tipos_os = ( isset($consumidor_revenda) and ($consumidor_revenda == $valor) ) ? "SELECTED" : '' ;
                                            $disabled_tipo_os = (strlen(trim($consumidor_revenda) > 0) AND ($consumidor_revenda != $valor AND !empty($os_revenda))) ? "disabled" : '';
                                    ?>
                                        <option <?=$selected_tipos_os?> <?=$disabled_tipo_os?> value="<?=$valor?>"><?=$descricao?></option>
                                    <?php
                                        }
                                    ?>
                                </select>
                                <input type="hidden" name="consumidor_revenda_antes" id="consumidor_revenda_antes" value="<?=getValue('consumidor_revenda');?>">
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php 
                if (in_array($login_fabrica, array(169,170,178)) OR ($login_fabrica == 183 AND $login_tipo_posto_codigo != "Rev" AND $login_tipo_posto_codigo != "Rep")) {
                    if ($login_fabrica == 178 AND (!empty(getValue("visita_por_km")) OR !empty($_REQUEST["solicitar_deslocamento"]))){
                        $checked_deslocamento = "checked";
                    }else if ($login_fabrica == 178 AND !empty(getValue("hd_chamado"))){
                        $checked_deslocamento = "checked";
                    }else{
                        $checked_deslocamento = "";
                    }
            ?>
                    <div class="span3">
                        <div class='control-group <?=(in_array('solicitar_deslocamento', $msg_erro['campos'])) ? "error" : "" ?>' >
                            <label class="control-label" for="solicitar_deslocamento">Solicitar Deslocamento</label>
                            <div class="controls controls-row">
                                <div class="input-append">
                                    <input id="solicitar_deslocamento" <?=$checked_deslocamento?> name="solicitar_deslocamento" type="checkbox" value="t" />
                                </div>
                            </div>
                        </div>
                    </div>
            <?php 
                    if ($login_fabrica == 178 AND $areaAdmin === true){
                        if (getValue("os_cortesia") == "t"){
                            $checked_cortseia = "checked";
                        }else{
                            $checked_cortseia = "";
                        }
            ?>
                        <div class="span3">
                            <div class='control-group <?=(in_array('os_cortesia', $msg_erro['campos'])) ? "error" : "" ?>' >
                                <label class="control-label" for="os_cortesia">Cortesia</label>
                                <div class="controls controls-row">
                                    <div class="input-append">
                                        <input id="os_cortesia" <?=$checked_cortseia?> name="os_cortesia" type="checkbox" value="t" />
                                    </div>
                                </div>
                            </div>
                        </div>
            <?php         
                    }
                } 
            ?>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span5 div_revenda_nome">
                <div class='control-group <?=(in_array('revenda_nome', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_nome">Nome <b>(Revenda)</b></label>
                    <div class="controls controls-row">
                        <div class="span11 input-append">
                            <? if ($regras["revenda_nome"]["obrigatorio"] == true) { ?>
                                <h5 class="asteristico">*</h5>
                            <? } ?>
                            <input id="revenda_nome" name="revenda_nome" class="span12" type="text" maxlength="50" value="<?= getValue('revenda_nome'); ?>" />
                            <?php 
                                if ($login_fabrica == 183){
                                    if ($login_tipo_posto_codigo != "Rev"){
                                        $tipo_lupa      = (($login_tipo_posto_codigo == "Rep")? "posto" : "revenda");
                                        $parametro_lupa = (($login_tipo_posto_codigo == "Rep")? "nome"  : "razao_social");
                            ?>
                                        <span class="add-on" rel="lupa" style='cursor: pointer;'>
                                            <i class="icon-search"></i>
                                        </span>
                                        <input type="hidden" name="lupa_config" tipo="<?=$tipo_lupa?>" parametro="<?=$parametro_lupa?>" />
                            <?php   
                                    } 
                                }else{ 
                            ?>
                                <span class="add-on" rel="lupa" style='cursor: pointer;'>
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3 div_revenda_cnpj">
                <div class='control-group <?=(in_array('revenda_cnpj', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <?php
                    if (in_array($login_fabrica, [186])) { ?>
                        <label class="control-label" for="consumidor_cpf">
                            <?= traduz('cpf') ?>
                            <input type="radio" id="cpf_cnpj_revenda" name="revenda_cpf_cnpj" <?= (getValue('revenda_cpf_cnpj') == "cpf") ? 'checked="checked"': ''; ?> value="cpf" />
                            <?= traduz('cnpj') ?> 
                            <input type="radio" id="cnpj_cpf_revenda" name="revenda_cpf_cnpj" <?= (getValue('revenda_cpf_cnpj') == "cnpj" || empty(getValue('revenda_cpf_cnpj'))) ? 'checked="checked"': ''; ?> value="cnpj" />
                        </label>
                    <?php
                    } else { ?>
                        <label class="control-label" for="revenda_cnpj"><?= traduz('CNPJ');?></label>
                    <?php
                    } ?>
                    <div class="controls controls-row">
                        <div class="span10 input-append">
                            <? if ($regras["revenda_cnpj"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_cnpj" name="revenda_cnpj" class="span12" type="text" value="<?= getValue('revenda_cnpj'); ?>" />
                            <?php 
                                if ($login_fabrica == 183){
                                    if ($login_tipo_posto_codigo != "Rev"){
                                        $tipo_lupa      = (($login_tipo_posto_codigo == "Rep")? "posto" : "revenda");
                            ?>
                                        <span class="add-on" rel="lupa" style='cursor: pointer;'>
                                            <i class="icon-search"></i>
                                        </span>
                                        <input type="hidden" name="lupa_config" tipo="<?=$tipo_lupa?>" parametro="cnpj" />    
                            <?php   }   ?>
                            <?php }else{ ?>
                                <span class="add-on" rel="lupa" style='cursor: pointer;'>
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php 
            if ($login_fabrica == 178){ 
                
                if (strlen($_REQUEST['inscricao_estadual']) > 0 OR in_array($_REQUEST['consumidor_revenda'], array("R", "S")) OR strlen(trim(getValue('inscricao_estadual'))) > 0){
                    $display_inscricao = "style='display: block;'";
                    $display_cep = "style='display: none;'";
                }else{
                    $display_inscricao = "style='display: none;'";
                    $display_cep = "style='display: block;'";
                }
            ?>
            <div class="span2 div_inscricao_estadual" <?=$display_inscricao?> >
                <div class='control-group <?=(in_array('inscricao_estadual', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="inscricao_estadual">Inscr. Estadual</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>
                            <?php
                                $value_inscricao     = getValue('inscricao_estadual');

                                if (in_array($login_fabrica, [178])) {
                                    $value_inscricao = (empty($value_inscricao)) ? getValue('consumidor[inscricao_estadual]') : $value_inscricao;
                                }
                            ?>
                            <input id="inscricao_estadual" name="inscricao_estadual" class="span12" type="text" value="<?= $value_inscricao; ?>" />
                        </div>
                    </div>
                </div>
            </div>
	        <?php } else {?>
            <div class="span2">
                <div class='control-group <?=(in_array('revenda_cep', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_cep">CEP</label>
                    <div class="controls controls-row">
                        <div class="input-append">
                            <? if ($regras["revenda_cep"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_cep" name="revenda_cep" class="span12" type="text" value="<?= getValue('revenda_cep'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <?php if ($login_fabrica == 178){ ?>
            <div class="span2 div_cep_inscricao" >
                <div class='control-group <?=(in_array('revenda_cep', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_cep">CEP</label>
                    <div class="controls controls-row">
                        <div class="input-append">
                            <? if ($regras["revenda_cep"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_cep" name="revenda_cep" class="span12" type="text" value="<?= getValue('revenda_cep'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
            <div class="span2">
                <div class="control-group <?=(in_array('revenda_estado', $msg_erro['campos'])) ? "error" : "" ?>">
                    <label class="control-label" for="revenda_estado">Estado</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <? if ($regras["revenda_estado"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <select id="revenda_estado" name="revenda_estado" class="span12" >
                                <option value="">Selecione</option>
                                <?
                                $rev_uf = getValue('revenda_estado');
                                foreach ($array_estados as $sigla => $nome_estado) {
                                    $selected = ($sigla == $rev_uf) ? "selected" : ""; ?>
                                    <option value="<?= $sigla; ?>" <?= $selected; ?>><?= $nome_estado; ?></option>
                                <? } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group <?=(in_array('revenda_cidade', $msg_erro['campos'])) ? "error" : "" ?>">
                    <label class="control-label" for="revenda_cidade">Cidade</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <? if ($regras["revenda_cidade"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <select id="revenda_cidade" name="revenda_cidade" class="span12" >
                                <option value="" >Selecione</option>
                                <? if (strlen($rev_uf = getValue("revenda_estado")) > 0) {
                                    $sql = "
                                        SELECT * FROM (
                                            SELECT UPPER(TRIM(fn_retira_especiais(cidade))) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('$rev_uf')
                                            UNION
                                            SELECT UPPER(TRIM(fn_retira_especiais(nome))) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('$rev_uf')
                                        ) AS cidade
                                        ORDER BY cidade ASC;
                                    ";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        $rev_cidade = getValue("revenda_cidade");

                                        $sql = "SELECT UPPER(TRIM(fn_retira_especiais('$rev_cidade')))";
                                        $resUpperCidade = pg_query($con,$sql);
                                        $rev_cidade = pg_fetch_result($resUpperCidade,0,0);

                                        while ($result = pg_fetch_object($res)) {
                                            $selected = ($result->cidade == $rev_cidade) ? " selected" : ""; ?>
                                            <option value="<?= $result->cidade; ?>" <?= $selected; ?>><?= $result->cidade; ?></option>
                                        <? }
                                    }
                                } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="<?=(strlen($_REQUEST['inscricao_estadual']) > 0 OR in_array($_REQUEST['consumidor_revenda'], array("R", "S")))? 'span2' : 'span3'?> div_bairro ">
                <div class='control-group <?=(in_array('revenda_bairro', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_bairro">Bairro</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <? if ($regras["revenda_bairro"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_bairro" name="revenda_bairro" class="span12" type="text" value="<?= getValue('revenda_bairro'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span5">
                <div class='control-group <?=(in_array('revenda_endereco', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_endereco">Endereço</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <? if ($regras["revenda_endereco"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_endereco" name="revenda_endereco" class="span12" type="text" value="<?= getValue('revenda_endereco'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1">
                <div class='control-group <?=(in_array('revenda_numero', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_numero">Número</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <? if ($regras["revenda_numero"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_numero" name="revenda_numero" class="span12" type="text" maxlength="15" value="<?= getValue('revenda_numero'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2">
                <div class='control-group <?=(in_array('revenda_complemento', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_complemento">Complemento</label>
                    <div class="controls controls-row">
                        <div class="span12">

                            <? if ($regras["revenda_complemento"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_complemento" name="revenda_complemento" class="span12" type="text" maxlength='20' value="<?= getValue('revenda_complemento'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2">
                <div class='control-group <?=(in_array('revenda_fone', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_fone">Telefone</label>
                    <div class="controls controls-row">
                        <div class="input-append">
                            <? if ($regras["revenda_fone"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_fone" name="revenda_fone" class="span12" type="text" value="<?= getValue('revenda_fone'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <?php if ($login_fabrica == 178){ ?>
                <div class="span2">
                    <div class='control-group <?=(in_array('revenda_celular', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="revenda_celular">Celular</label>
                        <div class="controls controls-row">
                            <div class="span12 input-append">
                                <? if ($regras["revenda_celular"]["obrigatorio"] == true) { ?>
                                    <h5 class='asteristico'>*</h5>
                                <? } ?>
                                <input id="revenda_celular" name="revenda_celular" class="span12" type="text" value="<?= getValue('revenda_celular'); ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="span5">
                <div class='control-group <?=(in_array('revenda_email', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_email">Email</label>
                    <div class="controls controls-row">
                        <div class="span12 input-append">
                            <? if ($regras["revenda_email"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_email" name="revenda_email" class="span12" type="text" value="<?= getValue('revenda_email'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <? if (in_array($login_fabrica, array(169,170))) { ?>
                <div class="span5">
                    <div class='control-group <?=(in_array('revenda_contato', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="revenda_contato">Contato</label>
                        <div class="controls controls-row">
                            <div class="span12 input-append">
                                <? if ($regras["revenda_contato"]["obrigatorio"] == true) { ?>
                                    <h5 class='asteristico'>*</h5>
                                <? } else { ?>
                                    <h5 class='asteristico' hidden>*</h5>
                                <?php
                                } ?>
                                <input id="revenda_contato" name="revenda_contato" class="span12" type="text" value="<?= getValue('revenda_contato'); ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            <? } ?>
            <div class="span1"></div>
        </div>
    </div>
    <br />
    <?php 
        if ($login_fabrica == 177){
            $display_map = "style='display:none';";
        }else{
            $display_map = "";
        }

        if (in_array($login_fabrica, array(175))) {
            if (count($msg_erro["msg"]) > 0) {
                $display_map = 'style="display: none;"';
            } else {
                $display_map = '';
            }
        }
    ?>
    <div id="div_informacoes_deslocamento" <?=$display_map?>>
        <div class="tc_formulario" style="padding-bottom: 30px;">
            <div class="titulo_tabela">Informações do Deslocamento <label class="pull-right"><input type="checkbox" id="ver-mapa" /> Ver Mapa</label></div>
            <br />
            <div id="google_maps" style='width:90%;margin-left:5%;height:400px;'></div>
            <br />
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span10" style="padding-top: 5px;height: auto;">
                    <div class='control-group <?=(in_array('os[qtde_km]', $msg_erro['campos'])) ? "error" : "" ?> tac' >
                        <? if (strlen(getValue("os_revenda")) > 0 AND $login_fabrica != 178) { ?>
                            <div class="controls controls-row">
                                <div class="span12 tac">
                                    <input id="qtde_km_hidden" name="qtde_km_hidden" type="hidden" value="<?=getValue('qtde_km_hidden')?>" />
                                    <input id="qtde_km" name="qtde_km" type="hidden" value="<?=getValue('qtde_km')?>" />
                                    <div class='alert alert-warning' >O deslocamento será pago na primeira OS gerada a partir dessa OS de revenda</div>
                                </div>
                            </div>
                        <? } else { ?>
                            <label class="control-label" id="box_desc_distancia" for="box_desc_distancia">Distância <span style="color: #FF0000;">(a distância já é calculada a ida e a volta)</span></label>
                            <div class="controls controls-row">
                                <div class="span12 tac">
                                    <span id="info_km">
                                        <h5 class="asteristico" style="float:none;display:inline;">*</h5>
                                        <input <?=($login_fabrica == 178 AND !$areaAdmin)? "readonly" : ""?> id="qtde_km" name="qtde_km" class="span2" type="text" value="<?=number_format(getValue('qtde_km'), 2, '.', '')?>" />
                                    </span>
                                    <input id="qtde_km_hidden" name="qtde_km_hidden" type="hidden" value="<?=getValue('qtde_km_hidden')?>" />
                                    <button type="button" id="calcular_km" class="btn btn-primary btn-small" >Calcular KM</button>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="span1"></div>
            </div>
        </div>
    </div>
    
    <?php 
        if ($login_fabrica == 178){
            if (($tipo_os == "C" OR $_REQUEST["consumidor_revenda"] == "C") OR $tipo_os == "S" OR $_REQUEST["consumidor_revenda"] == "S"){
                $display_rc = "";
            }else if (getValue('hd_chamado') AND !empty(getValue('revenda_nome'))){
                $display_rc = "";
            }else{
                $display_rc = "style='display: none;'"; 
            }

            if (!empty($_REQUEST["revenda_nome_consumidor"])){
                $revenda_nome_consumidor = $_REQUEST["revenda_nome_consumidor"];
            }else{
                $revenda_nome_consumidor = getValue('revenda_nome_consumidor');
            }

            if (!empty($_REQUEST["revenda_cnpj_consumidor"])){
                $revenda_cnpj_consumidor = $_REQUEST["revenda_cnpj_consumidor"];
            }else{
                $revenda_cnpj_consumidor = getValue('revenda_cnpj_consumidor');
            }

            if ($areaAdmin === true){
                $login_posto = getValue('posto_id');
            }
    ?>
    <div id="div_informacoes_os_revenda_consumidor" <?=$display_rc?> class="tc_formulario" style="padding-bottom: 30px;">
        <div class="titulo_tabela">Informações da Revenda</div>
        <br/>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span5 div_revenda_nome_consumidor">
                <div class='control-group <?=(in_array('revenda_nome_consumidor', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_nome_consumidor">Nome <b>(Revenda)</b></label>
                    <div class="controls controls-row">
                        <div class="span11 input-append">
                            <? if ($regras["revenda_nome_consumidor"]["obrigatorio"] == true) { ?>
                                <h5 class="asteristico">*</h5>
                            <? } ?>
                            <input id="revenda_nome_consumidor" name="revenda_nome_consumidor" class="span12" type="text" maxlength="50" value="<?=$revenda_nome_consumidor?>" />
                            <span class="add-on" rel="lupa" style='cursor: pointer;'>
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3 div_revenda_cnpj_consumidor">
                <div class='control-group <?=(in_array('revenda_cnpj_consumidor', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="revenda_cnpj_consumidor">CNPJ</label>
                    <div class="controls controls-row">
                        <div class="span10 input-append">
                            <? if ($regras["revenda_cnpj_consumidor"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="revenda_cnpj_consumidor" name="revenda_cnpj_consumidor" class="span12" type="text" value="<?=$revenda_cnpj_consumidor?>" />
                            <span class="add-on" rel="lupa" style='cursor: pointer;'>
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
    </div>
    <?php 
        } 
    ?>

    <?php if ($login_fabrica == 178){ ?>
    <div id="div_informacoes_agendamento">
        <div class="tc_formulario" style="padding-bottom: 30px;">
            <div class="titulo_tabela"><?php echo traduz('informacoes.do.agendamento');?></div>
            <br/>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span2">
                    <div class='control-group <?=(in_array('data_agendamento', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="data_agendamento"><?php echo traduz('data agendamento');?></label>
                        <div class="controls controls-row">
                            <?php
                            if ($regras["data_agendamento"]["obrigatorio"] == true) { echo "<h5 class='asteristico'>*</h5>";}
                            if (!empty($_REQUEST["data_agendamento"])){
                                $data_agendamento = $_REQUEST["data_agendamento"];
                            }else{
                                $data_agendamento = getValue('agendamento[data_agendamento]');
                            }
                            ?>
                            <input id="data_agendamento" name="data_agendamento" class="span12" type="text" autocomplete="off" value="<?=$data_agendamento?>"/>
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <div class='control-group <?=(in_array('tecnico', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="revenda_cnpj"><?php echo traduz('tecnico');?></label>
                        <div class="controls controls-row">
                            <?php
                                if ($regras["tecnico"]["obrigatorio"] == true) {
                                    echo "<h5 class='asteristico'>*</h5>";
                                }
                                
                                if (!empty($_REQUEST["tecnico"])){
                                    $tecnico = $_REQUEST["tecnico"];
                                }else{
                                    $tecnico = getValue('agendamento[tecnico]');
                                }
                            ?>
                            <select class="span12" name="tecnico" id="tecnico">
                                <?php if (strlen(trim($tecnico)) == 0 AND empty(getValue("agendamento[data_agendamento]"))){ ?>
                                    <option value=""></option>
                                <?php } ?>
                                <?php
                                
                                $sql = "SELECT tecnico, nome FROM tbl_tecnico WHERE posto = $login_posto AND fabrica = $login_fabrica AND ativo IS TRUE";
                                $res = pg_query($con,$sql);
                                

                                foreach (pg_fetch_all($res) as $key) {
                                    $disabled = (strlen(trim($tecnico) > 0) AND (!empty($os_revenda) AND $tecnico != $key['tecnico'])) ? "disabled" : "";
                                    $selected_tecnico = ( isset($tecnico) and ($tecnico == $key['tecnico']) ) ? "SELECTED" : '' ;
                                ?>
                                    <option value="<?php echo $key['tecnico']?>" <?php echo $selected_tecnico ?> <?=$disabled?> >
                                        <?php echo $key['nome']?>
                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="span3">
                    <div class='control-group <?=(in_array('os[tecnico]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="revenda_cnpj"><?php echo traduz('periodo.da.visita');?></label>
                        <div class="controls controls-row">
                            <?php
                                if ($regras["tecnico"]["obrigatorio"] == true) {
                                    echo "<h5 class='asteristico'>*</h5>";
                                }
                                if (!empty($_REQUEST["periodo"])){
                                    $periodo = $_REQUEST["periodo"];
                                }else{
                                    $periodo = getValue("agendamento[periodo]");
                                }
                                
                                if ($periodo == "manha"){
                                    $selected_manha = "selected";
                                    $disable_tarde = "disabled";
                                }else if ($periodo == "tarde"){
                                    $selected_tarde = "selected";
                                    $disable_manha = "disabled";
                                }

                            ?>
                            <select class="span12" name="periodo_visita" id="periodo_visita">
                                <option <?=$selected_manha?> <?=$disable_manha?> value="manha">Manhã</option>
                                <option <?=$selected_tarde?> <?=$disable_tarde?> value="tarde">Tarde</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="span2">
                    <div class='control-group <?=(in_array('data_visita_realizada', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="data_visita_realizada"><?php echo traduz('data.visita.realizada');?></label>
                        <div class="controls controls-row">
                            <div class="input-append">
                            <?php
                                if ($regras["data_visita_realizada"]["obrigatorio"] == true) { echo "<h5 class='asteristico'>*</h5>";}
                                if (!empty($_REQUEST["data_visita_realizada"])){
                                    $data_visita = $_REQUEST["data_visita_realizada"];
                                }else{
                                    $data_visita = getValue('data_visita_realizada');
                                }    
                            ?>
                                <input id="data_visita_realizada" name="data_visita_realizada" class="span12" type="text" autocomplete="off" value="<?=$data_visita?>"/>
                                  <span class="add-on"><i id="btnPopover" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Visita realizada" data-content="Preencher o campo somente se a visita tenha sido realizada " class="icon-question-sign"></i>   </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1"></div>
            </div>
        </div>
    </div>
    <br/>
    <?php } ?>
    
    <?php 
        $display_informacoes_data_nf = "";

        if ($login_fabrica == 183 AND !empty(getValue('notas_fiscais_adicionadas'))){
            $display_informacoes_data_nf = "style='display: none;'";
        }
    ?>

    <div class="tc_formulario" <?=$display_informacoes_data_nf?> id="div_informacoes_data_nf" style="padding-bottom: 30px;">
        <input type="hidden" name="notas_fiscais_adicionadas" id="notas_fiscais_adicionadas" value="<?= getValue('notas_fiscais_adicionadas'); ?>" />
        <div class="titulo_tabela">Informações de Nota(s) Fiscal(is)</div>
        <br />
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span2">
                <div class='control-group <?=(in_array('nota_fiscal', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="nota_fiscal"><?=($login_fabrica == 178) ? "Nota Fiscal/Habite-se" : "Nota Fiscal"?></label>
                    <div class="controls controls-row">
                        <div class="input-append">
                            <? if ($regras["nota_fiscal"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="nota_fiscal" name="nota_fiscal" class="span12" maxlength="20" type="text" value="<?= getValue('nota_fiscal'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class='control-group <?=(in_array('data_nf', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="data_nf"><?=($login_fabrica == 178) ? "Data Nota Fiscal/Habite-se" : "Data Nota Fiscal"?></label>
                    <div class="controls controls-row">
                        <div class="input-append">
                            <? if ($regras["data_nf"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="data_nf" name="data_nf" class="span12" type="text" value="<?= getValue('data_nf'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){ ?>
            <div class="span3" id="div_listar_produtos_nf">
                <br />
                <div class='control-group' id="group_listar_produtos" >
                    <div class="controls controls-row tac">
                        <button type='button' class='btn btn-success' title="Adicionar Nota Fiscal" id="listar_produtos_nf" ><i class="icon-white icon-th-list"></i> Listar produtos</button>
                    </div>
                </div>
            </div>
            <?php } else {?>
            <div class="span3">
                <br />
                <div class='control-group' >
                    <div class="controls controls-row tac">
                        <button type='button' class='btn btn-success' title="Adicionar Nota Fiscal" id="adicionar_nota_fiscal" ><i class="icon-white icon-plus"></i>Adicionar NF</button>
                    </div>
                </div>
            </div>
            <div class="span3">
                <br />
                <div class='control-group' >
                    <div class="controls controls-row tac">
                        <button type='button' class='btn btn-info' title="Adicionar Produtos sem Nota Fiscal" id="adicionar_sem_nota_fiscal" ><i class="icon-white icon-plus"></i> Sem NF</button>
                    </div>
                </div>
            </div>
            <?php }?>
            <div class="span1"></div>
        </div>
    </div>
    <?
    $produtosAdicionados = getValue('produtos');
    unset($produtosAdicionados['__modelo__']);

    /**
     * Ordenar o array para mostrar na tela de cadastro
     */
    uasort($produtosAdicionados, function ($a, $b) {
        return $a['nota_fiscal'] - $b['nota_fiscal'];
    });

    $linhasProduto = count($produtosAdicionados);
    $readOnlyDescricao = ($login_fabrica == 178) ? "readonly" : "";

    if ($linhasProduto > 0) {
        $mostraDivProdutos = "block";
    } else {
        $mostraDivProdutos = "none";
    } ?>
    <br />
    <div id="div_informacoes_produto" class="tc_formulario" style="display:<?= $mostraDivProdutos; ?>;">
        <div class="titulo_tabela">Informações do(s) produto(s)</div>
        <br />
        <div id="modelo_nota_fiscal">
            <div style="background-color:#f5f5f5;padding-top:10px;" id="div_nota_fiscal___nota__">
                <div class="row-fluid" name="nota_fiscal___nota__">
                    <div class="span1"></div>
                    <div class="span2">
                        <div class='control-group'>
                            <div class="span12 input-append">
                                <label class="control-label">Nota Fiscal</label>
                                <div class="controls controls-row">
                                    <strong id="label_nota_fiscal___nota__"></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span2">
                        <div class='control-group'>
                            <div class="span12 input-append">
                                <label class="control-label">Data da Nota Fiscal</label>
                                <div class="controls controls-row">
                                    <strong id="label_data_nf___nota__"></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span6">
                        <br />
                        <div class="controls controls-row">
                            <div class="span12 tar" >
                                <button type="button" class="btn btn-danger" name="remove_nf" rel="__nota__" >Remover Todos</button>
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
                <div id="produto___nota__">
                    <div class="row-fluid" name="produto___modelo__">
                        <div class="span1">
                            <div class='control-group'>
                                <br />
                                <div class="controls controls-row">
                                    <div class="span12 tac" >
                                        <input type="hidden" name="produtos[__modelo__][id]" rel="produto_id" value="" />
                                        <input type="hidden" name="produtos[__modelo__][os_revenda_item]" value="" />
                                        <input type="hidden" name="produtos[__modelo__][nota_fiscal]" value="" />
                                        <input type="hidden" name="produtos[__modelo__][data_nf]" value="" />
                                        <input type="hidden" name="produtos[__modelo__][info_pecas]" value='' />

                                        <input type="hidden" name="produtos[__modelo__][produto_fora_linha]" value='' />
                                                
                                        <button type="button" class="btn btn-mini btn-danger" name="remove_produto" rel="__modelo__" style="display: none;" >X</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <? if (in_array($login_fabrica, array(169,170,178,183))) { ?>
                            <div class="span2">
                                <div class='control-group'>
                                    <label class="control-label" for="tipo_atendimento">Tipo Atendimento</label>
                                    <div class="controls controls-row">
                                        <div class="span12">
                                            <? if ($regras["tipo_atendimento"]["obrigatorio"] == true) { ?>
                                                <h5 class='asteristico'>*</h5>
                                            <? } ?>
                                            <select name="produtos[__modelo__][tipo_atendimento]" data-value="" data-posicao="__modelo__" class="span12 tipo_atendimento">
                                                <option value="">Selecione...</option>
                                                <?

                                                if (in_array($login_fabrica, array(169,170))) {
                                                    $whereFabrica = "AND grupo_atendimento IN ('R','G') AND descricao != 'RMA'";
                                                }

                                                $sqlTpAtendimento = "
                                                    SELECT
                                                        tipo_atendimento,
                                                        descricao,
                                                        fora_garantia,
                                                        km_google,
                                                        entrega_tecnica AS visita_tecnica,
                                                        grupo_atendimento
                                                    FROM tbl_tipo_atendimento
                                                    WHERE fabrica = {$login_fabrica}
                                                    AND ativo IS TRUE
                                                    {$whereFabrica}
                                                    ORDER BY descricao DESC;
                                                ";
                                                $resTpAtendimento = pg_query($con, $sqlTpAtendimento);

                                                while($result = pg_fetch_object($resTpAtendimento)) { 
                                                    if ($login_fabrica == 183){
                                                        if (strtolower($result->descricao) != "garantia"){
                                                            continue;
                                                        }
                                                    }
                                                ?>
                                                    <option value="<?= $result->tipo_atendimento; ?>" km_google="<?= $result->km_google; ?>" fora_garantia="<?= $result->fora_garantia; ?>" visita_tecnica="<?= $result->visita_tecnica; ?>" grupo_atendimento="<?= $result->grupo_atendimento; ?>" ><?= $result->descricao; ?></option>
                                                <? } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <? } ?>
                        <?php if (!in_array($login_fabrica, array(178,183,195))){ ?>
                        <div class="span2">
                            <div class='control-group'>
                                <label class="control-label" for="serie">Número de Série</label>
                                <div class="controls controls-row">
                                    <div class="span12 input-append">
                                        <input name="produtos[__modelo__][serie]" class="span10 nserie" posicao_serie='__modelo__' type="text" value="" maxlength="30" />
                                        <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
                                             <i class='icon-search'></i>
                                        </span>
                                        <input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" posicao="__modelo__" parametro="numero_serie" <?=(in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""?> <?=($usaProdutoGenerico) ? "produto-generico='true'" : ""?> />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="span2">
                            <div class='control-group'>
                                <label class="control-label" for="referencia">Referência</label>
                                <div class="controls controls-row">
                                    <div class="span10 input-append">
                                        <input name="produtos[__modelo__][referencia]" class="span12" type="text" value="" />
                                        <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
                                            <i class="icon-search"></i>
                                        </span>
                                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posto="<?=$login_posto?>" posicao="__modelo__" ativo="t" <? if (isset($fabrica_usa_subproduto)) echo "subproduto='true'"; ?> <?= (in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""; ?> <?= ($usaProdutoGenerico) ? "produto-generico='true'" : ""; ?> />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span3" >
                            <div class='control-group'>
                                <label class="control-label" for="descricao">Descrição</label>
                                <div class="controls controls-row">
                                    <div class="span10 input-append">
                    				    <input name="produtos[__modelo__][descricao]" class="span12" type="text" value="" <?=$readOnlyDescricao?>/>
                    					<?php
                    					if($login_fabrica != 178){
                    					?>
                                            <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
                                                <i class="icon-search"></i>
                                            </span>
                    					<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posto="<?=$login_posto?>" posicao="__modelo__" ativo="t" <? if (isset($fabrica_usa_subproduto)) echo "subproduto='true'"; ?> <?= (in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""; ?> <?= ($usaProdutoGenerico) ? "produto-generico='true'" : ""; ?> />
                    					<?php
                    					}
                    					?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if (in_array($login_fabrica, array(195))){ ?>
                        <div class="span2">
                            <div class='control-group'>
                                <label class="control-label" for="data_fabricacao">Data de Fabricação</label>
                                <div class="controls controls-row">
                                    <div class="span12 input-append">
                                        <input name="produtos[__modelo__][data_fabricacao]" class="span12 data_fab" type="text" value="" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="span1">
                            <div class='control-group'>
                                <label class="control-label" for="qtde">Qtde</label>
                                <div class="controls controls-row">
                                    <div class="span12 input-append">
                                        <input name="produtos[__modelo__][qtde]" class="span12 numeric" type="text" value="" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (in_array($login_fabrica, array(178,183))){ ?>
                            <?php if ($login_fabrica == 178){ ?>
                            <div class="span2">
                                <div class='control-group'>
                                    <label class="control-label" for="marca">Marca</label>
                                    <div class="controls controls-row">
                                        <h5 class="asteristico">*</h5>
                                        <select name="produtos[__modelo__][marca]" class="span12 marca" id="marca__modelo__">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <div class="span2 box_defeito_reclamado">
                                <div class='control-group'>
                                    <label class="control-label" for="defeito_reclamado">Defeito Reclamado</label>
                                    <div class="controls controls-row">
                                        <h5 class="asteristico">*</h5>
                                        <select name="produtos[__modelo__][defeito_reclamado]" class="span12 defeito_reclamado" id="defeito_reclamado__modelo__">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php if ($login_fabrica == 178){ ?>
                                <div class="span2 box_grupo_defeito_constatado">
                                    <div class='control-group'>
                                        <label class="control-label" for="defeito_constatado_grupo">Grupo Defeito Const.</label>
                                        <div class="controls controls-row">
                                            <select name="produtos[__modelo__][defeito_constatado_grupo]" rel="__modelo__" class="span12 defeito_constatado_grupo" id="defeito_constatado_grupo__modelo__">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="span2 box_defeito_constatado">
                                <div class='control-group'>
                                    <label class="control-label" for="defeito_constatado">Defeito Constatado</label>
                                    <div class="controls controls-row">
                                        <select name="produtos[__modelo__][defeito_constatado]" class="span12 defeito_constatado__modelo__" id="defeito_constatado__modelo__">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php if ($login_fabrica == 178){ ?>
                            <div class="span2 box_instalacao_publica">
                                <div class='control-group'>
                                    <label class="control-label" for="instalacao_publica">Instalação Publica</label>
                                    <div class="controls controls-row">
                                        <select name="produtos[__modelo__][instalacao_publica]" class="span12 instalacao_publica" id="instalacao_publica__modelo__">
                                            <option value="t">Sim</option>
                                            <option selected value="f">Não</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <div class="span2 div_lista_basica">
                                <div class='control-group'>
                                    <div class="controls controls-row">
                                        <button type="button" name="lista_basica" data-posicao="__modelo__" class="btn btn_lista_basica"><?php echo traduz("lista.basica");?></button>
                                        <span style="<?=($login_fabrica == 183) ? "margin-right: -95px; margin-top: 30px; float:right;" : "margin-right: 312px; margin-top: 7px; float:right;" ?>" class="label label-info informacoes_pecas__modelo__"></span>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($login_fabrica == 177){ ?>
                        <div class="span2">
                            <div class='control-group'>
                                <label class="control-label" for="lote">Lote</label>
                                <div class="controls controls-row">
                                    <div class="span12 input-append">
                                        <h5 style="display:none;" class="asteristico h5_lote__modelo__">*</h5>
                                        <input name="produtos[__modelo__][lote]" class="span10 numeric" posicao_lote='__modelo__' type="text" value="" maxlength="30" />
                                        <input name="produtos[__modelo__][hidden_lote]" id="hidden_lote__modelo__" class="span10 numeric" posicao_lote='__modelo__' type="hidden" value="" maxlength="30" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <? 
                        if (in_array($login_fabrica, array(173)) && verifica_tipo_posto("posto_interno", "TRUE", $login_posto)) { ?>
                            <div class="span2">
                                <div class='control-group'>
                                    <label class="control-label" for="tecnico">Técnico</label>
                                    <div class="controls controls-row">
                                        <div class="span12">
                                            <? if ($regras["tecnico"]["obrigatorio"] == true) { ?>
                                                <h5 class='asteristico'>*</h5>
                                            <? } ?>
                                            <select name="produtos[__modelo__][tecnico]" class="span12 tecnico">
                                                <option value="">Selecione...</option>
                                                <?

                                                $sqlTec = "
                                                    SELECT
                                                        tecnico,
                                                        nome
                                                    FROM tbl_tecnico
                                                    WHERE fabrica = {$login_fabrica}
                                                    AND posto = {$login_posto}
                                                    AND ativo IS TRUE
                                                    ORDER BY nome;
                                                ";
                                                $resTec = pg_query($con, $sqlTec);

                                                while($result = pg_fetch_object($resTec)) { ?>
                                                    <option value="<?= $result->tecnico; ?>" ><?= $result->nome; ?></option>
                                                <? } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <? } ?>
                        <div class="span1"></div>
                    </div>
                </div>
                <br />
                <?php //if ($login_fabrica != 183){ ?>
                <div class="tac">
                    <p>
                        <button type="button" name="adicionar_linha___nota__" rel="" class="btn btn-primary" >Adicionar Novo Produto</button>
                    </p>
                </div>
                <br />
                <?php 
                /*}else{ 
                    if ($login_fabrica == 183 AND !in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
                ?>
                        <div class="tac">
                            <p>
                                <button type="button" name="adicionar_linha___nota__" rel="" class="btn btn-primary" >Adicionar Novo Produto</button>
                            </p>
                        </div>
                        <br />   
               <?php
                    } 
                } */
                ?>
            </div>
            <br />
        </div>
        <div id="modelo_sem_nota_fiscal">
            <div style="background-color:#f5f5f5;padding-top:10px;" id="div_nota_fiscal___nota__">
                <div class="row-fluid" name="nota_fiscal___nota__">
                    <div class="span1"></div>
                    <div class="span4">
                        <br />
                        <div class="controls controls-row">
                            <div class="span12 tar" >
                                <strong>Grupo de produtos sem nota fiscal</strong>
                            </div>
                        </div>
                    </div>
                    <div class="span6">
                        <br />
                        <div class="controls controls-row">
                            <div class="span12 tar" >
                                <button type="button" class="btn btn-danger" name="remove_nf" rel="__nota__" >Remover Todos</button>
                            </div>
                        </div>
                    </div>
                    <div class="span1"></div>
                </div>
                <div id="produto___nota__">
                    <div class="row-fluid" name="produto___modelo__">
                        <div class="span1">
                            <div class='control-group'>
                                <br />
                                <div class="controls controls-row">
                                    <div class="span12 tac" >
                                        <input type="hidden" name="produtos[__modelo__][id]" rel="produto_id" value="" />
                                        <input type="hidden" name="produtos[__modelo__][os_revenda_item]" value="" />
                                        <input type="hidden" name="produtos[__modelo__][nota_fiscal]" value="" />
                                        <input type="hidden" name="produtos[__modelo__][data_nf]" value="" />
                                        <input type="hidden" name="produtos[__modelo__][info_pecas]" value='' />
                                        <input type="hidden" name="produtos[__modelo__][produto_fora_linha]" value='' />
                                        <button type="button" class="btn btn-mini btn-danger" name="remove_produto" rel="__modelo__" style="display: none;" >X</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <? if (in_array($login_fabrica, array(169,170,178,183))) { ?>
                            <div class="span2">
                                <div class='control-group'>
                                    <label class="control-label" for="tipo_atendimento">Tipo Atendimento</label>
                                    <div class="controls controls-row">
                                        <div class="span12">
                                            <? if ($regras["tipo_atendimento"]["obrigatorio"] == true) { ?>
                                                <h5 class='asteristico'>*</h5>
                                            <? } ?>
                                            <select name="produtos[__modelo__][tipo_atendimento]" data-value="" data-posicao="__modelo__" class="span12 tipo_atendimento">
                                                <option value="">Selecione...</option>
                                                <?

                                                if (in_array($login_fabrica, array(169,170))) {
                                                    $whereFabrica = "AND grupo_atendimento IN ('R','G') AND descricao != 'RMA'";
                                                }

                                                $sqlTpAtendimento = "
                                                    SELECT
                                                        tipo_atendimento,
                                                        descricao,
                                                        fora_garantia,
                                                        km_google,
                                                        entrega_tecnica AS visita_tecnica,
                                                        grupo_atendimento
                                                    FROM tbl_tipo_atendimento
                                                    WHERE fabrica = {$login_fabrica}
                                                    AND ativo IS TRUE
                                                    {$whereFabrica}
                                                    ORDER BY descricao DESC;
                                                ";
                                                $resTpAtendimento = pg_query($con, $sqlTpAtendimento);

                                                while($result = pg_fetch_object($resTpAtendimento)) { 
                                                    if ($login_fabrica == 183){
                                                        if (strtolower($result->descricao) != "garantia"){
                                                            continue;
                                                        }
                                                    }
                                                ?>
                                                    <option value="<?= $result->tipo_atendimento; ?>" km_google="<?= $result->km_google; ?>" fora_garantia="<?= $result->fora_garantia; ?>" visita_tecnica="<?= $result->visita_tecnica; ?>" grupo_atendimento="<?= $result->grupo_atendimento; ?>" ><?= $result->descricao; ?></option>
                                                <? } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <? } ?>
                        <?php if (!in_array($login_fabrica, array(178,183,195))){ ?>
                        <div class="span2">
                            <div class='control-group'>
                                <label class="control-label" for="serie">Número de Série</label>
                                <div class="controls controls-row">
                                    <div class="span12 input-append">
                                        <input name="produtos[__modelo__][serie]" class="span10 nserie" posicao_serie='__modelo__' type="text" value="" maxlength="30" />
                                        <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
                                             <i class='icon-search'></i>
                                        </span>
                                        <input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" posicao="__modelo__" parametro="numero_serie" <?=(in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""?> <?=($usaProdutoGenerico) ? "produto-generico='true'" : ""?> />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="span2">
                            <div class='control-group'>
                                <label class="control-label" for="referencia">Referência</label>
                                <div class="controls controls-row">
                                    <div class="span10 input-append">
                                        <input name="produtos[__modelo__][referencia]" class="span12" type="text" value="" />
                                        <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
                                            <i class="icon-search"></i>
                                        </span>
                                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posto="<?=$login_posto?>" posicao="__modelo__" ativo="t" <? if (isset($fabrica_usa_subproduto)) echo "subproduto='true'"; ?> <?= (in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""; ?> <?= ($usaProdutoGenerico) ? "produto-generico='true'" : ""; ?> />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span3" >
                            <div class='control-group'>
                                <label class="control-label" for="descricao">Descrição</label>
                                <div class="controls controls-row">
                                    <div class="span10 input-append">
                    				    <input name="produtos[__modelo__][descricao]" class="span12" type="text" value="" <?=$readOnlyDescricao?> />
                    					<?php
                    					if($login_fabrica != 178){
                    					?>
                                                            <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
                                                                <i class="icon-search"></i>
                                                            </span>
                    					<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posto="<?=$login_posto?>" posicao="__modelo__" ativo="t" <? if (isset($fabrica_usa_subproduto)) echo "subproduto='true'"; ?> <?= (in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""; ?> <?= ($usaProdutoGenerico) ? "produto-generico='true'" : ""; ?> />
                    					<?php
                    					}
                    					?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if (in_array($login_fabrica, array(195))){ ?>
                        <div class="span2">
                            <div class='control-group'>
                                <label class="control-label" for="data_fabricacao">Data de Fabricação</label>
                                <div class="controls controls-row">
                                    <div class="span12 input-append">
                                        <input name="produtos[__modelo__][data_fabricacao]" class="span12 data_fab" type="text" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php }?>
                        <div class="span1">
                            <div class='control-group'>
                                <label class="control-label" for="qtde">Qtde</label>
                                <div class="controls controls-row">
                                    <div class="span12 input-append">
                                        <input name="produtos[__modelo__][qtde]" class="span12 numeric" type="text" value="" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (in_array($login_fabrica, array(178,183))){ ?>
                            <?php if ($login_fabrica == 178){ ?>
                            <div class="span2">
                                <div class='control-group'>
                                    <label class="control-label" for="marca">Marca</label>
                                    <div class="controls controls-row">
                                        <h5 class="asteristico">*</h5>
                                        <select name="produtos[__modelo__][marca]" class="span12 marca" id="marca__modelo__">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <div class="span2 box_defeito_reclamado">
                                <div class='control-group'>
                                    <label class="control-label" for="defeito_reclamado">Defeito Reclamado</label>
                                    <div class="controls controls-row">
                                        <h5 class="asteristico">*</h5>
                                        <select name="produtos[__modelo__][defeito_reclamado]" class="span12 defeito_reclamado" id="defeito_reclamado__modelo__">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php if ($login_fabrica == 178){ ?>
                                <div class="span2 box_grupo_defeito_constatado">
                                    <div class='control-group'>
                                        <label class="control-label" for="defeito_constatado_grupo">Grupo Defeito Const.</label>
                                        <div class="controls controls-row">
                                            <select name="produtos[__modelo__][defeito_constatado_grupo]" rel="__modelo__" class="span12 defeito_constatado_grupo" id="defeito_constatado_grupo__modelo__">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="span2 box_defeito_constatado">
                                <div class='control-group'>
                                    <label class="control-label" for="defeito_constatado">Defeito Constatado</label>
                                    <div class="controls controls-row">
                                        <select name="produtos[__modelo__][defeito_constatado]" class="span12 defeito_constatado" id="defeito_constatado__modelo__">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php if ($login_fabrica == 178){ ?>
                            <div class="span2 box_instalacao_publica">
                                <div class='control-group'>
                                    <label class="control-label" for="instalacao_publica">Instalação Publica</label>
                                    <div class="controls controls-row">
                                        <select name="produtos[__modelo__][instalacao_publica]" class="span12 instalacao_publica" id="instalacao_publica__modelo__">
                                            <option value="t">Sim</option>
                                            <option selected value="f">Não</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <div class="span2 div_lista_basica">
                                <div class='control-group'>
                                    <div class="controls controls-row">
                                        <button type="button" name="lista_basica" data-posicao="__modelo__" class="btn btn_lista_basica"><?php echo traduz("lista.basica");?></button>
                                        <span style="<?=($login_fabrica == 183) ? "margin-right: -95px; margin-top: 30px; float:right;" : "margin-right: 312px; margin-top: 7px; float:right;" ?>" class="label label-info informacoes_pecas__modelo__"></span>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($login_fabrica == 177){ ?>
                        <div class="span2">
                            <div class='control-group'>
                                <label class="control-label" for="lote">Lote</label>
                                <div class="controls controls-row">
                                    <div class="span12 input-append">
                                        <h5 style="display:none;" class="asteristico h5_lote__modelo__">*</h5>
                                        <input name="produtos[__modelo__][lote]" class="span10 numeric" posicao_lote='__modelo__' type="text" value="" maxlength="30" />
                                        <input name="produtos[__modelo__][hidden_lote]" id="hidden_lote__modelo__" class="span10 numeric" posicao_lote='__modelo__' type="hidden" value="" maxlength="30" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <? if (in_array($login_fabrica, array(173)) && verifica_tipo_posto("posto_interno", "TRUE", $login_posto)) { ?>
                            <div class="span2">
                                <div class='control-group'>
                                    <label class="control-label" for="tecnico">Técnico</label>
                                    <div class="controls controls-row">
                                        <div class="span12">
                                            <? if ($regras["tecnico"]["obrigatorio"] == true) { ?>
                                                <h5 class='asteristico'>*</h5>
                                            <? } ?>
                                            <select name="produtos[__modelo__][tecnico]" class="span12 tecnico">
                                                <option value="">Selecione...</option>
                                                <?

                                                $sqlTec = "
                                                    SELECT
                                                        tecnico,
                                                        nome
                                                    FROM tbl_tecnico
                                                    WHERE fabrica = {$login_fabrica}
                                                    AND posto = {$login_posto}
                                                    AND ativo IS TRUE
                                                    ORDER BY nome;
                                                ";
                                                $resTec = pg_query($con, $sqlTec);

                                                while($result = pg_fetch_object($resTec)) { ?>
                                                    <option value="<?= $result->tecnico; ?>" ><?= $result->nome; ?></option>
                                                <? } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <? } ?>
                        <div class="span1"></div>
                    </div>
                </div>
                <br />
                <div class="tac">
                    <p>
                        <button type="button" name="adicionar_linha___nota__" rel="" class="btn btn-primary" >Adicionar Novo Produto</button>
                    </p>
                </div>
                <br />
            </div>
            <br />
        </div>
        <div id="modelo_produto">
            <div class="row-fluid" name="produto___modelo__">
                <div class="span1">
                    <div class='control-group'>
                        <div class="controls controls-row">
                            <div class="span12 tac" >
                                <input type="hidden" name="produtos[__modelo__][id]" rel="produto_id" value="" />
                                <input type="hidden" name="produtos[__modelo__][os_revenda_item]" value="" />
                                <input type="hidden" name="produtos[__modelo__][nota_fiscal]" value="" />
                                <input type="hidden" name="produtos[__modelo__][data_nf]" value="" />
                                <input type="hidden" name="produtos[__modelo__][info_pecas]" value='' />
                                <input type="hidden" name="produtos[__modelo__][produto_fora_linha]" value='' />
                                <button type="button" class="btn btn-mini btn-danger" name="remove_produto" rel="__modelo__" style="display: none;" >X</button>
                            </div>
                        </div>
                    </div>
                </div>
                <? if (in_array($login_fabrica, array(169,170,178,183))) { ?>
                    <div class="span2">
                        <div class='control-group'>
                            <label class="control-label" for="tipo_atendimento">Tipo Atendimento</label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <select name="produtos[__modelo__][tipo_atendimento]" data-value="" data-posicao="__modelo__" class="span12 tipo_atendimento">
                                        <option value="">Selecione...</option>
                                        <?
                                        if (in_array($login_fabrica, array(169,170))) {
                                            $whereFabrica = "AND grupo_atendimento IN ('R','G') AND descricao != 'RMA'";
                                        }
                                        $sqlTpAtendimento = "
                                            SELECT
                                                tipo_atendimento,
                                                descricao,
                                                fora_garantia,
                                                km_google,
                                                entrega_tecnica AS visita_tecnica,
                                                grupo_atendimento
                                            FROM tbl_tipo_atendimento
                                            WHERE fabrica = {$login_fabrica}
                                            AND ativo IS TRUE
                                            {$whereFabrica}
                                            ORDER BY descricao DESC;
                                        ";
                                        $resTpAtendimento = pg_query($con, $sqlTpAtendimento);

                                        while($result = pg_fetch_object($resTpAtendimento)) { 
                                            if ($login_fabrica == 183){
                                                if (strtolower($result->descricao) != "garantia"){
                                                    continue;
                                                }
                                            }
                                        ?>
                                            <option value="<?= $result->tipo_atendimento; ?>" km_google="<?= $result->km_google; ?>" fora_garantia="<?= $result->fora_garantia; ?>" visita_tecnica="<?= $result->visita_tecnica; ?>" grupo_atendimento="<?= $result->grupo_atendimento; ?>" ><?= $result->descricao; ?></option>
                                        <? } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                <? } ?>
                <?php if (!in_array($login_fabrica, array(178,183,195))){ ?>
                <div class="span2">
                    <div class='control-group'>
                        <label class="control-label" for="serie">Número de Série</label>
                        <div class="controls controls-row">
                            <div class="span12 input-append">
                                <input name="produtos[__modelo__][serie]" class="span10 nserie" posicao_serie='__modelo__' type="text" value="" maxlength="30" />
                                <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
                                     <i class='icon-search'></i>
                                </span>
                                <input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" posicao="__modelo__" parametro="numero_serie" <?=(in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""?> <?=($usaProdutoGenerico) ? "produto-generico='true'" : ""?> />
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="span2">
                    <div class='control-group'>
                        <label class="control-label" for="referencia">Referência</label>
                        <div class="controls controls-row">
                            <div class="span10 input-append">
                                <input name="produtos[__modelo__][referencia]" class="span12" type="text" value="" />
                                <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posto="<?=$login_posto?>" posicao="__modelo__" ativo="t" <? if (isset($fabrica_usa_subproduto)) echo "subproduto='true'"; ?> <?= (in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""; ?> <?= ($usaProdutoGenerico) ? "produto-generico='true'" : ""; ?> />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span3" >
                    <div class='control-group'>
                        <label class="control-label" for="descricao">Descrição</label>
                        <div class="controls controls-row">
                            <div class="span10 input-append">
                			    <input name="produtos[__modelo__][descricao]" class="span12" type="text" value="" <?=$readOnlyDescricao?> />
                				<?php
                				if($login_fabrica != 178){
                				?>
                                    <span class="add-on" rel="lupa_produto" style='cursor: pointer;'>
                                        <i class="icon-search"></i>
                                    </span>
                				<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posto="<?=$login_posto?>" posicao="__modelo__" ativo="t" <? if (isset($fabrica_usa_subproduto)) echo "subproduto='true'"; ?> <?= (in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""; ?> <?= ($usaProdutoGenerico) ? "produto-generico='true'" : ""; ?> />
                				<?php
                				}
                				?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (in_array($login_fabrica, array(195))){ ?>
                    <div class="span2">
                        <div class='control-group'>
                            <label class="control-label" for="data_fabricacao">Data de Fabricação</label>
                            <div class="controls controls-row">
                                <div class="span12 input-append">
                                    <input name="produtos[__modelo__][data_fabricacao]" class="span12 data_fab" type="text" value="" />
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="span1">
                    <div class='control-group'>
                        <label class="control-label" for="qtde">Qtde</label>
                        <div class="controls controls-row">
                            <div class="span12 input-append">
                                <input name="produtos[__modelo__][qtde]" class="span12 numeric" type="text" value="" />
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (in_array($login_fabrica, array(178,183))){ ?>
                    <?php if ($login_fabrica == 178){ ?>
                    <div class="span2">
                        <div class='control-group'>
                            <label class="control-label" for="marca">Marca</label>
                            <div class="controls controls-row">
                                <h5 class="asteristico">*</h5>
                                <select name="produtos[__modelo__][marca]" class="span12 marca" id="marca__modelo__">
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="span2 box_defeito_reclamado">
                        <div class='control-group'>
                            <label class="control-label" for="defeito_reclamado">Defeito Reclamado</label>
                            <div class="controls controls-row">
                                <h5 class="asteristico">*</h5>
                                <select name="produtos[__modelo__][defeito_reclamado]" class="span12 defeito_reclamado" id="defeito_reclamado__modelo__">
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php if ($login_fabrica == 178){ ?>
                        <div class="span2 box_grupo_defeito_constatado">
                            <div class='control-group'>
                                <label class="control-label" for="defeito_constatado_grupo">Grupo Defeito Const.</label>
                                <div class="controls controls-row">
                                    <select name="produtos[__modelo__][defeito_constatado_grupo]" rel="__modelo__" class="span12 defeito_constatado_grupo" id="defeito_constatado_grupo__modelo__">
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="span2 box_defeito_constatado">
                        <div class='control-group'>
                            <label class="control-label" for="defeito_constatado">Defeito Constatado</label>
                            <div class="controls controls-row">
                                <select name="produtos[__modelo__][defeito_constatado]" class="span12 defeito_constatado" id="defeito_constatado__modelo__">
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php if ($login_fabrica == 178){ ?>
                    <div class="span2 box_instalacao_publica">
                        <div class='control-group'>
                            <label class="control-label" for="instalacao_publica">Instalação Publica</label>
                            <div class="controls controls-row">
                                <select name="produtos[__modelo__][instalacao_publica]" class="span12 instalacao_publica" id="instalacao_publica__modelo__">
                                    <option value="t">Sim</option>
                                    <option selected value="f">Não</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="span2 div_lista_basica">
                        <div class='control-group'>
                            <div class="controls controls-row">
                                <button type="button" name="lista_basica" data-posicao="__modelo__" class="btn btn_lista_basica"><?php echo traduz("lista.basica");?></button>
                                <span style="<?=($login_fabrica == 183) ? "margin-right: -95px; margin-top: 30px; float:right;" : "margin-right: 312px; margin-top: 7px; float:right;" ?>" class="label label-info informacoes_pecas__modelo__"></span>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($login_fabrica == 177){ ?>
                <div class="span2">
                    <div class='control-group'>
                        <label class="control-label" for="lote">Lote</label>
                        <div class="controls controls-row">
                            <div class="span12 input-append">
                                <h5 style="display:none;" class="asteristico h5_lote__modelo__">*</h5>
                                <input name="produtos[__modelo__][lote]" class="span10 numeric" posicao_lote='__modelo__' type="text" value="" maxlength="30" />
                                <input name="produtos[__modelo__][hidden_lote]" id="hidden_lote__modelo__" class="span10 numeric" posicao_lote='__modelo__' type="hidden" value="" maxlength="30" />
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <? if (in_array($login_fabrica, array(173)) && verifica_tipo_posto("posto_interno", "TRUE", $login_posto)) { ?>
                    <div class="span2">
                        <div class='control-group'>
                            <label class="control-label" for="tecnico">Técnico</label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <select name="produtos[__modelo__][tecnico]" class="span12 tecnico">
                                        <option value="">Selecione...</option>
                                        <?
                                        $sqlTec = "
                                            SELECT
                                                tecnico,
                                                nome
                                            FROM tbl_tecnico
                                            WHERE fabrica = {$login_fabrica}
                                            AND posto = {$login_posto}
                                            AND ativo IS TRUE
                                            ORDER BY nome;
                                        ";
                                        $resTec = pg_query($con, $sqlTec);

                                        while($result = pg_fetch_object($resTec)) { ?>
                                            <option value="<?= $result->tecnico; ?>" ><?= $result->nome; ?></option>
                                        <? } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                <? } ?>
                <div class="span1"></div>
            </div>
        </div>
        <div id="div_produtos" style="padding:10px;">
            <? if ($linhasProduto > 0) {
                $nf_anterior = "";
                $data_nf_anterior = "";
                foreach ($produtosAdicionados as $p => $linhaProduto) {
                    if (in_array($login_fabrica, array(178,183))){
                        unset($qtdex);
                        $displayRemoverProduto   = (!empty($produtosAdicionados[$p]['id']) && empty($produtosAdicionados[$p]['os_revenda_item'])) ? '' : 'style="display: none;"';
                        $displayLupaProduto      = (!empty($produtosAdicionados[$p]['id']) && !empty($produtosAdicionados[$p]['os_revenda_item']) ) ? "display:none" : "";
                        $readOnlyProduto         = (!empty($produtosAdicionados[$p]['id']) && !empty($produtosAdicionados[$p]['os_revenda_item']) ) ? "readonly" : "";
                        $readOnlyMarca           = (!empty($produtosAdicionados[$p]['id']) && !empty($produtosAdicionados[$p]['os_revenda_item']) && !empty($produtosAdicionados[$p]['marca']) ) ? "readonly" : "";
                        /*$readOnlyTipoAtendimento = (!empty($produtosAdicionados[$p]['id']) && !empty($produtosAdicionados[$p]['os_revenda_item']) && !empty($produtosAdicionados[$p]['tipo_atendimento']) ) ? "readonly" : "";*/
                        $readOnlyProdutoNumSerie = (!count($msg_erro['campos'][$p]) > 0) ? "readonly" : "";

                        if ($produtosAdicionados[$p]['qtde'] == 1) {
                            $displayRemoverProduto = $displayLupaProduto = $readOnlyProduto = $readOnlyMarca = $readOnlyProdutoNumSerie = '';
                        }

                        if (!empty($produtosAdicionados[$p]["info_pecas"])){
                            $count = json_decode($produtosAdicionados[$p]["info_pecas"], true);

                            foreach ($count as $key => $value) {
                                $qtdex+=$value["qtde_lancada"];
                            }

                            if ($qtdex > 1){
                                $text_span = "<p style='margin: 0px !important; font-size: 12px;'>$qtdex peças lançadas</p>";
                            }else{
                                $text_span = "<p style='margin: 0px !important; font-size: 12px;'>$qtdex peça lançada</p>";
                            }
                        }else{
                            $text_span = "";
                        }

                        if (!empty($produtosAdicionados[$p]["parametros_adicionais_os_item"])){
                            $param_adicionais = json_decode($produtosAdicionados[$p]["parametros_adicionais_os_item"], true);
                            $produtosAdicionados[$p]["defeito_constatado"] = $param_adicionais["defeito_constatado"];
                            $produtosAdicionados[$p]["info_pecas"] = $param_adicionais["info_pecas"];
                            $info_pecas = $param_adicionais["info_pecas"];
                            $produtosAdicionados[$p]['instalacao_publica'] = $param_adicionais["instalacao_publica"];
                            $produtosAdicionados[$p]['defeito_constatado_grupo'] = $param_adicionais["defeito_constatado_grupo"];
                        }

                        if (!empty($produtosAdicionados[$p]["produto_parametros_adicionais"])){
                            $param_adicionais_produto = json_decode($produtosAdicionados[$p]["produto_parametros_adicionais"], true);
                            $produtosAdicionados[$p]["produto_fora_linha"] = $param_adicionais_produto["fora_linha"];
                        }

                        $readOnlyDefeitoConstatado = (!empty($produtosAdicionados[$p]['id']) && !empty($produtosAdicionados[$p]['os_revenda_item']) && !empty($produtosAdicionados[$p]['defeito_constatado']) ) ? "readonly" : "";
                    }else{
                        $displayRemoverProduto = (!empty($produtosAdicionados[$p]['id']) && empty($produtosAdicionados[$p]['os_revenda_item'])) ? '' : 'style="display: none;"';
                        $displayLupaProduto      = (!empty($produtosAdicionados[$p]['id']) OR !empty($produtosAdicionados[$p]['os_revenda_item']) ) ? "display:none" : "";
                        $readOnlyProduto         = (!empty($produtosAdicionados[$p]['id']) OR !empty($produtosAdicionados[$p]['os_revenda_item']) ) ? "readonly" : "";
                        $readOnlyProdutoNumSerie = (!count($msg_erro['campos'][$p]) > 0) ? "readonly" : "";
                    }
                    
                    if (empty($produtosAdicionados[$p]['nota_fiscal']) AND $login_fabrica == 178){
                        $produtosAdicionados[$p]['nota_fiscal'] = "semNota";
                    }

                    if ($produtosAdicionados[$p]['nota_fiscal'] != $nf_anterior) {
                        if ($nf_anterior != "") { ?>
                            <br />
                            <div class="tac">
                                <p>
                                    <button type="button" name="adicionar_linha_<?= (!empty($nf_anterior)) ? $nf_anterior : $produtosAdicionados[$p]['nota_fiscal']; ?>" rel="<?= $nf_anterior.'_'.$data_nf_anterior; ?>" class="btn btn-primary" >Adicionar Novo Produto</button>
                                </p>
                            </div>
                            <br />
                        </div>
                        <br />
                        <? } ?>
                    <div style="background-color:#f5f5f5;padding-top:10px;" id="div_nota_fiscal_<?= $produtosAdicionados[$p]['nota_fiscal']; ?>">
                        <div class="row-fluid" name="nota_fiscal_linha_<?= $produtosAdicionados[$p]['nota_fiscal']; ?>">
                            <div class="span1"></div>
                            <? if ($produtosAdicionados[$p]['nota_fiscal'] != 'semNota') { ?>
                                <div class="span2">
                                    <div class='control-group'>
                                        <div class="span12 input-append">
                                            <label class="control-label">Nota Fiscal</label>
                                            <div class="controls controls-row">
                                                <strong id="label_nota_fiscal_<?= $produtosAdicionados[$p]['nota_fiscal']; ?>"><?= $produtosAdicionados[$p]['nota_fiscal']; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="span2">
                                    <div class='control-group'>
                                        <div class="span12 input-append">
                                            <label class="control-label">Data da Nota Fiscal</label>
                                            <div class="controls controls-row">
                                                <strong id="label_data_nf_<?= $produtosAdicionados[$p]['nota_fiscal']; ?>"><?= $produtosAdicionados[$p]['data_nf']; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <? } else { ?>
                                <div class="span4">
                                    <br />
                                    <div class="span12 tar">
                                        <strong>Grupo de Produtos sem nota fiscal</strong>
                                    </div>
                                </div>
                            <? }
                             if (empty($produtosAdicionados[$p]['os_revenda_item'])) { ?>
                                <div class="span6">
                                    <br />
                                    <div class="controls controls-row">
                                        <div class="span12 tar" >
                                            <button type="button" class="btn btn-danger" name="remove_nf" rel="<?= $produtosAdicionados[$p]['nota_fiscal']; ?>" <?= $displayRemoverNf; ?>>Remover Todos</button>
                                        </div>
                                    </div>
                                </div>
                            <? } ?>
                            <div class="span1"></div>
                        </div>
                        <? } ?>
                        <div id="produto_<?= str_replace('.','___',$produtosAdicionados[$p]['nota_fiscal']); ?>">
                            <div class="row-fluid" name="produto_<?= $p; ?>">
                                <div class="span1">
                                    <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>'>
                                        <br />
                                        <div class="controls controls-row">
                                            <div class="span12 tac" >
                                                <input type="hidden" name="produtos[<?= $p; ?>][id]" rel="produto_id" value="<?= $produtosAdicionados[$p]['id']; ?>" />
                                                <input type="hidden" name="produtos[<?= $p; ?>][os_revenda_item]" value="<?= $produtosAdicionados[$p]['os_revenda_item']; ?>" />
                                                <input type="hidden" name="produtos[<?= $p; ?>][nota_fiscal]" value="<?= $produtosAdicionados[$p]['nota_fiscal']; ?>" />
                                                <input type="hidden" name="produtos[<?= $p; ?>][data_nf]" value="<?= $produtosAdicionados[$p]['data_nf']; ?>" />
                                                <input type="hidden" name="produtos[<?= $p; ?>][info_pecas]" value='<?= $produtosAdicionados[$p]["info_pecas"]; ?>' />
                                                <input type="hidden" name="produtos[<?= $p; ?>][produto_fora_linha]" value='<?= $produtosAdicionados[$p]["produto_fora_linha"]; ?>' />
                                                
                                                <button type="button" class="btn btn-mini btn-danger" name="remove_produto" rel="<?= $p; ?>" <?= $displayRemoverProduto; ?>>X</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <? if (in_array($login_fabrica, array(169,170,178,183))) { ?>
                                    <?php 
                                        $style_tipo_at = "";
                                        if ($login_fabrica == 178 AND (empty($produtosAdicionados[$p]['nota_fiscal']) OR $produtosAdicionados[$p]['nota_fiscal'] == 'semNota') AND empty($msg_erro["msg"])){ 
                                            $style_tipo_at = "style='margin-left: 72px;'";
                                    ?>
                                    <div class="row-fluid atualizar_nota_<?=$p?>" style="height: 30px !important; margin-left: 71px !important;">
                                        <div class="span3">
                                            <div class='control-group'>
                                                <label class="control-label" for="tipo_atendimento">Nota Fiscal/Habite-se</label>
                                                <div class="controls controls-row">
                                                    <div class="span12">
                                                        <input type="text" name="produtos[<?= $p; ?>][nota_fiscal]" value="<?= $produtosAdicionados[$p]['nota_fiscal']; ?>" class="span8 nota_fiscal_atualizada" data-posicao="<?=$p?>" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="span3">
                                            <div class='control-group'>
                                                <label class="control-label" for="tipo_atendimento">Data Nota Fiscal/Habite-se</label>
                                                <div class="controls controls-row">
                                                    <div class="span12">
                                                        <input type="text" name="produtos[<?= $p; ?>][data_nf]" value="<?= $produtosAdicionados[$p]['data_nf']; ?>" class="span10 data_nf_atualizada" data-posicao="<?=$p?>" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="span2" <?=$style_tipo_at?> >
                                        <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>' >
                                            <label class="control-label" for="tipo_atendimento">Tipo Atendimento</label>
                                            <div class="controls controls-row">
                                                <div class="span12">
                                                        <?php 
                                                            if ($login_fabrica == 178){
                                                                /*$disabled = (!empty($produtosAdicionados[$p]['os_revenda_item']) AND !empty($produtosAdicionados[$p]['tipo_atendimento'])) ? "disabled" : "";*/
                                                                $disabled = "";
                                                        ?>
                                                                <select name="produtos[<?= $p; ?>][tipo_atendimento]" data-value="<?=$produtosAdicionados[$p]['tipo_atendimento']?>" data-posicao="<?=$p?>" class="span12 tipo_atendimento" <?= (!empty($produtosAdicionados[$p]['os_revenda_item'])) ? $readOnlyTipoAtendimento : ""; ?>>
                                                        <?php
                                                            }else{
                                                                $disabled = (!empty($produtosAdicionados[$p]['os_revenda_item'])) ? "disabled" : "";
                                                        ?>
                                                                <select name="produtos[<?= $p; ?>][tipo_atendimento]" class="span12 tipo_atendimento" <?= (!empty($produtosAdicionados[$p]['os_revenda_item'])) ? $readOnlyProduto : ""; ?>>
                                                        <?php    
                                                            }
                                                        ?>
                                                        <option value="" <?= $disabled; ?>>Selecione...</option>
                                                        <?

                                                        if (in_array($login_fabrica, array(169,170))) {
                                                            $whereFabrica = "AND grupo_atendimento IN ('R','G') AND descricao != 'RMA'";
                                                        }

                                                        $sqlTpAtendimento = "
                                                            SELECT
                                                                tipo_atendimento,
                                                                descricao,
                                                                fora_garantia,
                                                                km_google,
                                                                entrega_tecnica AS visita_tecnica,
                                                                grupo_atendimento
                                                            FROM tbl_tipo_atendimento
                                                            WHERE fabrica = {$login_fabrica}
                                                            AND ativo IS TRUE
                                                            {$whereFabrica}
                                                            ORDER BY descricao DESC";
                                                        $resTpAtendimento = pg_query($con, $sqlTpAtendimento);
                                                        
                                                        while($result = pg_fetch_object($resTpAtendimento)) {
                                                            $selected = ($result->tipo_atendimento == $produtosAdicionados[$p]['tipo_atendimento']) ? "selected" : "";
                                                            if ($login_fabrica == 178){
                                                                /*$disabled = ($result->tipo_atendimento != $produtosAdicionados[$p]['tipo_atendimento'] && !empty($produtosAdicionados[$p]['os_revenda_item']) && !empty($produtosAdicionados[$p]['tipo_atendimento'])) ? "disabled" : "";*/
                                                                $disabled = "";
                                                                if (!empty(getValue("hd_chamado"))){
                                                                    $tipo_atendimento_desc = retira_acentos($result->descricao);
                                                                    
                                                                    $tipo_atendimento_desc = strtolower($tipo_atendimento_desc);
                                                                    if ($tipo_atendimento_desc == "atendimento balcao"){
                                                                        continue;
                                                                    }

                                                                    if (empty($produtosAdicionados[$p]['tipo_atendimento']) AND !empty(getValue("hd_chamado"))){
                                                                        $tipo_atendimento_desc = retira_acentos($result->descricao);
                                                                        $tipo_atendimento_desc = strtolower($tipo_atendimento_desc);
                                                                        if ($tipo_atendimento_desc == "garantia domicilio"){
                                                                            $selected = "selected";
                                                                        }
                                                                    }
                                                                }
                                                            }else{
                                                                $disabled = ($result->tipo_atendimento != $produtosAdicionados[$p]['tipo_atendimento'] && !empty($produtosAdicionados[$p]['os_revenda_item'])) ? "disabled" : "";
                                                            }
                                                            if ($login_fabrica == 183){
                                                                if (strtolower($result->descricao) != "garantia"){
                                                                    continue;
                                                                }
                                                            }
                                                            ?>
                                                            <option value="<?= $result->tipo_atendimento; ?>" km_google="<?= $result->km_google; ?>" fora_garantia="<?= $result->fora_garantia; ?>" visita_tecnica="<?= $result->visita_tecnica; ?>" grupo_atendimento="<?= $result->grupo_atendimento; ?>" <?= $selected." ".$disabled; ?> ><?= $result->descricao; ?></option>
                                                        <? } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <? } ?>
                                <?php if (!in_array($login_fabrica, array(178,183,195))){ ?>
                                <div class="span2">
                                    <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="serie">Número de Série</label>
                                        <div class="controls controls-row">
                                            <div class="span12 input-append">
                                                <input name="produtos[<?= $p; ?>][serie]" class="span10" type="text" value="<?= $produtosAdicionados[$p]['serie']; ?>" maxlength="30" <?= (!empty($produtosAdicionados[$p]['os_revenda_item']) || (!empty($produtosAdicionados[$p]["serie"]) && $produtosAdicionados[$p]["serie"] != $login_codigo_posto)) ? $readOnlyProdutoNumSerie : ""; ?> />
                                                <span class="add-on" rel="lupa_produto" style="cursor: pointer;<?= $displayLupaProduto; ?>">
                                                     <i class='icon-search'></i>
                                                </span>
                                                <input type="hidden" name="lupa_config" posto="<?= $login_posto; ?>" tipo="produto" parametro="numero_serie" posicao="<?= $p; ?>" <?=(in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""?> <?=($usaProdutoGenerico) ? "produto-generico='true'" : ""?> />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="span2">
                                    <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="referencia">Referência</label>
                                        <div class="controls controls-row">
                                            <div class="span10 input-append">
                                                <input name="produtos[<?= $p; ?>][referencia]" class="span12" type="text" value="<?= $produtosAdicionados[$p]['referencia']; ?>" <?= $readOnlyProduto; ?> />
                                                <span class="add-on" rel="lupa_produto" style="cursor: pointer;<?= $displayLupaProduto; ?>">
                                                    <i class="icon-search"></i>
                                                </span>
                                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posto="<?=$login_posto?>" ativo="t" posicao="<?= $p; ?>" <? if (isset($fabrica_usa_subproduto)) echo "subproduto='true'"; ?> <?= (in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""; ?> <?= ($usaProdutoGenerico) ? "produto-generico='true'" : ""; ?> />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="span3" >
                                    <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="descricao">Descrição</label>
                                        <div class="controls controls-row">
                                            <div class="span10 input-append">
                        						<input name="produtos[<?= $p; ?>][descricao]" class="span12" type="text" value="<?= $produtosAdicionados[$p]['descricao']; ?>" <?=$readOnlyDescricao; ?> />
                        						<?php
                        						if($login_fabrica != 178){
                        						?>
                                                <span class="add-on" rel="lupa_produto" style="cursor: pointer;<?= $displayLupaProduto; ?>">
                                                    <i class="icon-search"></i>
                                                </span>
                        						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posto="<?=$login_posto?>" ativo="t" posicao="<?= $p; ?>" <? if (isset($fabrica_usa_subproduto)) echo "subproduto='true'"; ?> <?= (in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""; ?> <?= ($usaProdutoGenerico) ? "produto-generico='true'" : ""; ?> />
                        						<?php
                        						}
                        						?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if (in_array($login_fabrica, array(195))){ ?>
                                <div class="span3">
                                    <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="data_fabricacao">Data de Fabricação</label>
                                        <div class="controls controls-row">
                                            <div class="span12 input-append">
                                                <input name="produtos[<?= $p; ?>][data_fabricacao]" class="span12 data_fab" type="text" value="<?= $produtosAdicionados[$p]['data_fabricacao']; ?>" <?= (!empty($produtosAdicionados[$p]['os_revenda_item']) || (!empty($produtosAdicionados[$p]["serie"]) && $produtosAdicionados[$p]["serie"] != $login_codigo_posto)) ? $readOnlyProduto : ""; ?> />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="span1">
                                    <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="qtde">Qtde</label>
                                        <div class="controls controls-row">
                                            <div class="span12 input-append">
                                                <input name="produtos[<?= $p; ?>][qtde]" class="span12 numeric" type="text" value="<?= $produtosAdicionados[$p]['qtde']; ?>" <?= (!empty($produtosAdicionados[$p]['os_revenda_item']) || (!empty($produtosAdicionados[$p]["serie"]) && $produtosAdicionados[$p]["serie"] != $login_codigo_posto)) ? $readOnlyProduto : ""; ?> />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                

                                <?php if (in_array($login_fabrica, array(178,183))){ ?>
                                    <?php if ($login_fabrica == 178){ ?>
                                    <div class="span2">
                                        <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>' >
                                            <label class="control-label" for="marca">Marca</label>
                                            <div class="controls controls-row">
                                                <h5 class="asteristico">*</h5>
                                                <select name="produtos[<?= $p; ?>][marca]" class="span12 marca" id="marca<?=$p?>" <?= (!empty($produtosAdicionados[$p]['os_revenda_item'])) ? $readOnlyMarca : ""; ?>>
                                                    <?php
                                                    $id_produto = $produtosAdicionados[$p]['id'];
                                                    $sql = "
                                                        SELECT 
                                                            JSON_FIELD('marca',parametros_adicionais) AS marcas_produto
                                                        FROM tbl_produto 
                                                        WHERE fabrica_i = $login_fabrica
                                                        AND produto = $id_produto ";
                                                    $res = pg_query($con, $sql);
                                                    if (pg_num_rows($res) > 0){
                                                        $marcas_produto = pg_fetch_result($res, 0, "marcas_produto");
                                                        $marcas_produto = json_decode($marcas_produto, true);
                                                        $marcas_produto = $marcas_produto['marcas'];

                                                        $count = count(explode(",", $marcas_produto));

                                                        $sqlMarca = "
                                                            SELECT
                                                                marca, nome
                                                            FROM tbl_marca
                                                            WHERE fabrica = {$login_fabrica}
                                                            AND marca IN ($marcas_produto)
                                                            AND ativo IS TRUE
                                                            UNION SELECT null AS marca, 'Selecione a Marca' AS nome
                                                            ORDER BY nome DESC;
                                                        ";
                                                        $resMarca = pg_query($con, $sqlMarca);
                                                        
                                                        #if ($count > 1 ){
                                                            #echo "<option value=''>Selecione a Marca</option>";
                                                        #}
                                                        while($result = pg_fetch_object($resMarca)) {
                                                            $selected = ($result->marca == $produtosAdicionados[$p]['marca']) ? "selected" : "";
                                                            $disabled = ($result->marca != $produtosAdicionados[$p]['marca'] && !empty($produtosAdicionados[$p]['os_revenda_item']) && !empty($produtosAdicionados[$p]['marca'])) ? "disabled" : ""; ?>
                                                            <option value="<?= $result->marca; ?>" <?= $selected." ".$disabled; ?> ><?= $result->nome; ?></option>
                                                    <?php
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="span2 box_defeito_reclamado">
                                        <div class="control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>">
                                            <label class="control-label" for="defeito_reclamado"><?php echo traduz("defeito.reclamado");?></label>
                                            <div class="controls controls-row">
                                                <h5 class="asteristico">*</h5>
                                                <div class="span12">
                                                    <select id="defeito_reclamado<?=$p?>" name="produtos[<?= $p; ?>][defeito_reclamado]" class="span12 defeito_reclamado" <?= (!empty($produtosAdicionados[$p]['os_revenda_item'])) ? $readOnlyDefeitoConstatado : ""; ?>>
                                                        <?php
                                                        if (strlen($produtosAdicionados[$p]['id']) > 0) {

                                                            $sql = "SELECT DISTINCT
                                                                    tbl_defeito_reclamado.defeito_reclamado,
                                                                    tbl_defeito_reclamado.codigo,
                                                                    tbl_defeito_reclamado.descricao
                                                                FROM tbl_diagnostico
                                                                INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica} AND tbl_defeito_reclamado.ativo IS TRUE
                                                                INNER JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                                                                INNER JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
                                                                WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                                                                AND tbl_diagnostico.ativo IS TRUE
                                                                AND tbl_produto.produto = ".$produtosAdicionados[$p]['id']."
                                                                ORDER BY tbl_defeito_reclamado.codigo ASC, tbl_defeito_reclamado.descricao ASC";
                                                            $res = pg_query($con, $sql);

                                                            if ($login_fabrica == 183){
                                                                echo "<option value=''>Selecione</option>";
                                                            }else{
                                                                if (empty($produtosAdicionados[$p]["defeito_reclamado"])){
                                                                    echo "<option value=''>Selecione</option>";
                                                                }
                                                            }

                                                            if (pg_num_rows($res) > 0) {
                                                                while ($result = pg_fetch_object($res)) {
                                                                    $selected = ($result->defeito_reclamado == $produtosAdicionados[$p]['defeito_reclamado']) ? "selected" : "";
                                                                    $disabled = ($result->defeito_reclamado != $produtosAdicionados[$p]['defeito_reclamado'] && !empty($produtosAdicionados[$p]['os_revenda_item']) && !empty($produtosAdicionados[$p]['defeito_reclamado'])) ? "disabled" : "";
                                                                ?>
                                                                    <option value='<?=$result->defeito_reclamado?>' <?=$selected?> <?=$disabled?> >
                                                                        <?=$result->descricao; ?>
                                                                    </option>
                                                                <?php 
                                                                }
                                                            }
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($login_fabrica == 178){ ?>
                                        <div class="span2 box_defeito_constatado">
                                            <div class="control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>">
                                                <label class="control-label" for="defeito_constatado_grupo"><?php echo traduz("Grupo Defeito Const.");?></label>
                                                <div class="controls controls-row">
                                                    <div class="span12">
                                                        <select id="defeito_constatado_grupo<?=$p?>" rel="<?=$p?>" name="produtos[<?= $p; ?>][defeito_constatado_grupo]" class="span12 defeito_constatado_grupo" <?= (!empty($produtosAdicionados[$p]['os_revenda_item'])) ? $readOnlyDefeitoConstatado : ""; ?>>
                                                            <option value="">Selecione ...</option>
                                                            <?php
                                                                $sql = "
                                                                    SELECT 
                                                                        tbl_defeito_constatado_grupo.defeito_constatado_grupo,
                                                                        tbl_defeito_constatado_grupo.descricao
                                                                    FROM tbl_defeito_constatado_grupo
                                                                    WHERE tbl_defeito_constatado_grupo.fabrica = $login_fabrica
                                                                    ORDER BY tbl_defeito_constatado_grupo.descricao ASC";
                                                                $res = pg_query($con, $sql);

                                                                if (pg_num_rows($res) > 0) {
                                                                    while ($result = pg_fetch_object($res)) {
                                                                        $selected = ($result->defeito_constatado_grupo == $produtosAdicionados[$p]['defeito_constatado_grupo']) ? "selected" : "";
                                                                        $disabled = ($result->defeito_constatado_grupo != $produtosAdicionados[$p]['defeito_constatado_grupo'] &&  !empty($produtosAdicionados[$p]['defeito_constatado_grupo'])) ? "disabled" : "";
                                                                    ?>
                                                                        <option value='<?=$result->defeito_constatado_grupo?>' <?=$selected?> <?=$disabled?> >
                                                                            <?=$result->descricao; ?>
                                                                        </option>
                                                                    <?php 
                                                                    }
                                                                }
                                                             ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="span2 box_defeito_constatado">
                                        <div class="control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>">
                                            <label class="control-label" for="defeito_constatado"><?php echo traduz("defeito.constatado");?></label>
                                            <div class="controls controls-row">
                                                <div class="span12">
                                                    <select id="defeito_constatado<?=$p?>" name="produtos[<?= $p; ?>][defeito_constatado]" class="span12 defeito_constatado" <?= (!empty($produtosAdicionados[$p]['os_revenda_item'])) ? $readOnlyDefeitoConstatado : ""; ?>>
                                                        <?php
                                                        if (strlen($produtosAdicionados[$p]['id']) > 0) {
                                                            if ($usa_linha_defeito_constatado == 't'){
                                                                $join_linha_familia_produto = " JOIN tbl_linha ON tbl_linha.linha = tbl_diagnostico.linha AND tbl_linha.fabrica = {$login_fabrica}
                                                                           JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = {$login_fabrica} ";
                                                            }else{
                                                                $join_linha_familia_produto = " JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                                                                           JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica} ";
                                                            }

                                                            $sql = "
                                                                SELECT DISTINCT
                                                                    tbl_defeito_constatado.defeito_constatado,
                                                                    tbl_defeito_constatado.descricao,
                                                                    tbl_defeito_constatado.lancar_peca,
                                                                    tbl_defeito_constatado.codigo,
                                                                    tbl_defeito_constatado.lista_garantia,
                                                                tbl_defeito_constatado.defeito_constatado_grupo
                                                                FROM tbl_diagnostico
                                                                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                                                                {$join_linha_familia_produto}
                                                                WHERE tbl_diagnostico.fabrica = $login_fabrica
                                                                AND tbl_produto.produto =".$produtosAdicionados[$p]['id']."
                                                                AND tbl_diagnostico.ativo IS TRUE
                                                                ORDER BY tbl_defeito_constatado.descricao ASC";
                                                            $res = pg_query($con, $sql);

                                                            if ($login_fabrica == 183){
                                                                echo "<option value=''>Selecione</option>";
                                                            }else{
                                                                if (empty($produtosAdicionados[$p]["defeito_constatado"])){
                                                                    echo "<option value=''>Selecione</option>";
                                                                }
                                                            }
                                                            
                                                            if (pg_num_rows($res) > 0) {
                                                                while ($result = pg_fetch_object($res)) {
                                                                    $selected = ($result->defeito_constatado == $produtosAdicionados[$p]['defeito_constatado']) ? "selected" : "";
                                                                    $disabled = ($result->defeito_constatado != $produtosAdicionados[$p]['defeito_constatado'] && !empty($produtosAdicionados[$p]['os_revenda_item']) && !empty($produtosAdicionados[$p]['defeito_constatado'])) ? "disabled" : "";
                                                                ?>
                                                                    <option value='<?=$result->defeito_constatado?>' data-lancar-pecas='<?=$result->lancar_peca?>' data-lista-garantia='<?=$result->lista_garantia?>' data-defeito-grupo='<?=$result->defeito_constatado_grupo?>' <?=$selected?> <?=$disabled?> >
                                                                        <?=$result->descricao; ?>
                                                                    </option>
                                                                <?php 
                                                                }
                                                            }
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($login_fabrica == 178){ ?>
                                    <div class="span2 box_instalacao_publica" >
                                        <div class="control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>">
                                            <label class="control-label" for="instalacao_publica"><?php echo traduz("instalação.publica");?></label>
                                            <div class="controls controls-row">
                                                <div class="span12">
                                                    <?php
                                                        if (!empty($produtosAdicionados[$p]['instalacao_publica'])){
                                                            $insta_readonly = "readonly";
                                                        }else{
                                                            $insta_readonly = "";
                                                        }
                                                    ?>
                                                    <select id="instalacao_publica<?=$p?>" name="produtos[<?= $p; ?>][instalacao_publica]" class="span12 instalacao_publica" <?=$insta_readonly?>>
                                                        <?php
                                                            if (empty($produtosAdicionados[$p]['instalacao_publica'])){
                                                                $produtosAdicionados[$p]['instalacao_publica'] = "f";
                                                            }
                                                            
                                                            $array_tipo_instalacao = array(
                                                                "t" => "Sim",
                                                                "f" => "Não",
                                                            );

                                                            foreach ($array_tipo_instalacao as $valor => $descricao) {
                                                                $selected_tipos_os = ( isset($produtosAdicionados[$p]['instalacao_publica']) and ($produtosAdicionados[$p]['instalacao_publica'] == $valor) ) ? "SELECTED" : '' ;
                                                                $disabled = ($valor != $produtosAdicionados[$p]['instalacao_publica'] && !empty($produtosAdicionados[$p]['os_revenda_item']) && !empty($produtosAdicionados[$p]['instalacao_publica'])) ? "disabled" : "";
                                                        ?>
                                                                <option <?=$selected_tipos_os?> value="<?=$valor?>" <?=$disabled?> ><?=$descricao?></option>
                                                        <?php
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="span2 div_lista_basica">
                                        <div class='control-group'>
                                            <div class="controls controls-row">
                                                <?php if (empty($produtosAdicionados[$p]["info_pecas"]) OR empty($os_revenda)){ ?>
                                                <button type="button" name="lista_basica" data-posicao="<?=$p?>" class="btn btn_lista_basica"><?php echo traduz("lista.basica");?></button>
                                                <span style="<?=($login_fabrica == 183) ? "margin-right: -95px; margin-top: 30px; float:right;" : "margin-right: 312px; margin-top: 7px; float:right;" ?>" class="label label-info informacoes_pecas<?=$p?>"><?=$text_span?></span>
                                                <?php } else if (empty($msg_erro["msg"])){ ?>
                                                <button type="button" data-posicao="<?=$p?>" data-info_pecas='<?=$produtosAdicionados[$p]["info_pecas"]?>' class="btn btn-info btn_peca_servico"><?php echo traduz("Peças/Serviços Realizados lançados");?></button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!empty($produtosAdicionados[$p]["produto_fora_linha"]) OR ($produtosAdicionados[$p]["produto_fora_linha"] === true OR $produtosAdicionados[$p]["produto_fora_linha"] == "true")){ ?>
                                        <div class='span12 box_fora_linha' id="box_fora_linha_<?=$p?>">Produto fora de linha, verificar na impressão da OS possíveis opções para troca</div>
                                    <?php } ?>
                                <?php } ?>
                                <?php 
                                if ($login_fabrica == 177){ 
                                    if (($_REQUEST["produtos"][$p]["hidden_lote"] == 't') OR !empty($produtosAdicionados[$p]['lote'])){
                                        $display_lote_peca = "";
                                    }else{
                                        $display_lote_peca = "style='display:none;'";
                                    }
                                ?>
                                <div class="span2">
                                    <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="lote">Lote</label>
                                        <div class="controls controls-row">
                                            <div class="span12 input-append">
                                                <h5 <?=$display_lote_peca?> class="asteristico h5_lote<?= $p; ?>">*</h5>
                                                <input name="produtos[<?= $p; ?>][lote]" class="span10 numeric" posicao_lote='<?=$p?>' type="text" value="<?=$produtosAdicionados[$p]['lote']?>" maxlength="30" />
                                                <input name="produtos[<?= $p; ?>][hidden_lote]" id="hidden_lote<?= $p; ?>" class="span10 numeric" posicao_lote='<?= $p; ?>' type="hidden" value="<?= $produtosAdicionados[$p]['hidden_lote']; ?>" maxlength="30" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <? if (in_array($login_fabrica, array(173)) && verifica_tipo_posto("posto_interno", "TRUE", $login_posto)) { ?>
                                    <div class="span2">
                                        <div class='control-group <?=(in_array("produto_{$p}", $msg_erro['campos'])) ? "error" : "" ?>' >
                                            <label class="control-label" for="tecnico">Técnico</label>
                                            <div class="controls controls-row">
                                                <div class="span12">
                                                    <select name="produtos[<?= $p; ?>][tecnico]" class="span12 tecnico" <?= (!empty($produtosAdicionados[$p]['os_revenda_item'])) ? $readOnlyProduto : ""; ?>>
                                                        <? $disabled = (!empty($produtosAdicionados[$p]['os_revenda_item'])) ? "disabled" : ""; ?>
                                                        <option value="" <?= $disabled; ?>>Selecione...</option>
                                                        <?

                                                        $sqlTec = "
                                                            SELECT
                                                                tecnico,
                                                                nome
                                                            FROM tbl_tecnico
                                                            WHERE fabrica = {$login_fabrica}
                                                            AND posto = {$login_posto}
                                                            AND ativo IS TRUE
                                                            ORDER BY nome;
                                                        ";
                                                        $resTec = pg_query($con, $sqlTec);

                                                        while($result = pg_fetch_object($resTec)) {
                                                            $selected = ($result->tecnico == $produtosAdicionados[$p]['tecnico']) ? "selected" : "";
                                                            $disabled = ($result->tecnico != $produtosAdicionados[$p]['tecnico'] && !empty($produtosAdicionados[$p]['os_revenda_item'])) ? "disabled" : ""; ?>
                                                            <option value="<?= $result->tecnico; ?>" <?= $selected." ".$disabled; ?> ><?= $result->nome; ?></option>
                                                        <? } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <? } ?>
                                <div class="span1"></div>
                            </div>
                        </div>
                        <? if (!next($produtosAdicionados)) { ?>
                        <br />
                        <div class="tac">
                            <p>
                                <button type="button" name="adicionar_linha_<?= str_replace('.','___',$produtosAdicionados[$p]['nota_fiscal']); ?>" rel="<?= str_replace('.','___',$produtosAdicionados[$p]['nota_fiscal']).'_'.$produtosAdicionados[$p]['data_nf']; ?>" class="btn btn-primary" >Adicionar Novo Produto</button>
                            </p>
                        </div>
                        <br />
                    </div>
                    <br />
                    <? }
                    $nf_anterior = $produtosAdicionados[$p]['nota_fiscal'];
                    $data_nf_anterior = $produtosAdicionados[$p]['data_nf'];
                }
            } ?>
        </div>
    </div>
    <br />
    <?php if ($login_fabrica == 183) {


        ?>
    <div id="div_tipo" class="tc_formulario">
        <div class="titulo_tabela">Selecionar Tipo de Frete</div>
        <br />
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10 tac">
                <div class='control-group <?=(in_array("tipo_frete", $msg_erro['campos'])) ? "error" : "" ?>' >

                    <div class="controls controls-row">
                        <div class="span4"></div>
                        <div class="span4 tac">
                            <h5 class="asteristico">*</h5>
                            <select name="tipo_frete" class="span12" id="tipo_frete">
                                <option value="">Selecione ...</option>
                                <option value="CIF" <?php echo ($tipo_frete == "CIF") ? "selected" : "";?>>CIF</option>
                                <option value="FOB" <?php echo ($tipo_frete == "FOB") ? "selected" : "";?>>FOB</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="span1"></div>
        </div>
    </div><br>
    <?php }?>
    <div id="div_obs" class="tc_formulario">
        <div class="titulo_tabela">Observações</div>
        <br />
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class='control-group <?=(in_array('obs', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <div class="controls controls-row">
                        <? if ($regras["obs"]["obrigatorio"] == true) { ?>
                            <h5 class='asteristico'>*</h5>
                        <? } ?>
                        <div class="span12">
                            <textarea id="obs" name="obs" class="span12" style="height: 50px;" ><?= getValue("obs"); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
    </div>

    <br />
    <?php 
        if ($fabricaFileUploadOS) {
            if ($anexo_notas == 't'){
                if (in_array($login_fabrica, array(175))) {
                    if ($_REQUEST['notas_fiscais_adicionadas'] == '\'semNota\'') {
                        $display_anexo      = "style='display:none;'";
                        $isChecked          = "";
                        $display_combo_nota = "style='display:none;'";
                    } else {
                        $display_anexo      = "";
                        $isChecked          = "checked='true'";
                        $display_combo_nota = "";
                    }
                } else {
                    $display_anexo      = "";
                    $isChecked          = "checked='true'";
                    $display_combo_nota = "";
                }
            }else{
                if (!empty($_REQUEST['notas_fiscais_adicionadas'])){
                    $display_anexo = "";
                    $display_combo_nota = "style='display:none;'";
                }else{
                    $display_anexo = "style='display:none;'";
                    $display_combo_nota = "style='display:none;'";
                    $isChecked = "";
                }
            }

            if ($login_fabrica == 178){
                $display_anexo = "style='display:none;'";
                if (empty($os_revenda)){
                    $anexoNoHash = true;
                    if ($_REQUEST['anexo_chave'] > 0){
                        $tempUniqueId = $_REQUEST['anexo_chave'];
                    }else{
                        $tempUniqueId = $login_posto.date("dmYHisu");
                    }
                }else{
                    $tempUniqueId = $os_revenda;
                }
                
            }else{
                $anexoNoHash = true;
                if ($_REQUEST['anexo_chave'] > 0){
                    $tempUniqueId = $_REQUEST['anexo_chave'];
                }else{
                    $tempUniqueId = $login_posto.date("dmYHisu");
                }
            }
            
            $anexo_prepend = "
                <div class='row-fluid' id='div_anexo_notas' $display_anexo >
                    <div class='span2'></div>
                    <div class='span8 tac'>
                        <label class='checkbox'>
                            <input type='checkbox' $isChecked id='anexo_notas' name='anexo_notas' value='t'>
                            Anexo para a Nota Fiscal
                        </label>
                    </div>
                </div>
                <div class='row-fluid' id='select_notas' $display_combo_nota>
                    <div class='span2'></div>
                    <div class='span8 tac'>
                        <select name='notas_revenda' id='campo_descricao'>
                            <option value=''>Selecione uma nota fiscal</option>";

                            if ($notas_fiscais_adicionadas > 0){
                                $array_notas = explode(',', $notas_fiscais_adicionadas);
                                
                                foreach ($array_notas as $key => $value) {
                                    $selected_nota = ( isset($notas_revenda) and ($notas_revenda == $value) ) ? "SELECTED" : '' ;
                                    $anexo_prepend .= "<option value='Nota Fiscal $value' $selected_nota >$value</option>";

                                                        }
                                                    }
                        $anexo_prepend .="</select>
                                </div>
                            </div>
                                        ";
            $boxUploader = array(
                "div_id" => "div_anexos",
                "prepend" => $anexo_prepend,
                "context" => "revenda",
                "unique_id" => $tempUniqueId,
                "hash_temp" => $anexoNoHash,
                "bootstrap" => true,
                "hidden_button" => false
            );
            include "box_uploader.php";
        } 
    ?>
    <?php if (!$fabricaFileUploadOS){ ?>
    <div id="div_anexos" class="tc_formulario">
        <div class="titulo_tabela">Anexo(s)</div>
        <br />
        <div class="tac">
            <? if ($fabrica_qtde_anexos > 0 AND !$fabricaFileUploadOS) {
                if (strlen($os_revenda) > 0) {
                    list($dia,$mes,$ano) = explode("/", getValue("data_abertura"));
                }
                echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";
                for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                    unset($anexo_link);

                    $anexo_imagem = "imagens/imagem_upload.png";
                    $anexo_s3     = false;
                    $anexo        = "";

                    if (strlen(getValue("anexo[{$i}]")) > 0 && getValue("anexo_s3[{$i}]") != "t") {
                        $anexos = $s3->getObjectList(getValue("anexo[{$i}]"), true);
                        $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
                        if ($ext == "pdf") {
                            $anexo_imagem = "imagens/pdf_icone.png";
                        } else if (in_array($ext, array("doc", "docx"))) {
                            $anexo_imagem = "imagens/docx_icone.png";
                        } else {
                            $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), true);
                        }
                        $anexo_link = $s3->getLink(basename($anexos[0]), true);
                        $anexo = getValue("anexo[$i]");
                    } else if(strlen($os_revenda) > 0) {
                        $anexos = $s3->getObjectList("{$os_revenda}_{$i}.", false, $ano, $mes);
                        if (count($anexos) > 0) {
                            $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
                            if ($ext == "pdf") {
                                $anexo_imagem = "imagens/pdf_icone.png";
                            } else if (in_array($ext, array("doc", "docx"))) {
                                $anexo_imagem = "imagens/docx_icone.png";
                            } else {
                                $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
                            }
                            $anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);
                            $anexo = basename($anexos[0]);
                            $anexo_s3 = true;
                        }
                    } ?>
                    <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
                        <? if ($regras["anexo"]["obrigatorio"] == true) { ?>
                            <h5 class='asteristico' style="margin-left: 40px;">*</h5> <br />
                        <? }
                        if (isset($anexo_link)) { ?>
                            <a href="<?=$anexo_link?>" target="_blank" >
                        <? } ?>
                        <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
                        <? if (isset($anexo_link)) { ?>
                            </a>
                            <script>setupZoom();</script>
                        <? }
                        if ($anexo_s3 === false) { ?>
                            <button type="button" class="btn btn-mini btn-primary btn-block" name="anexar" rel="<?=$i?>" >Anexar</button>
                        <? } ?>
                        <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
                        <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                        <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                    </div>
                <? }
            } ?>
        </div>
        <br />
    </div>
    <?php } ?>
    <br />
    <div class="tac">
        <input type='hidden' name="gravar" />
        <input type="button" class="btn btn-large" value="Gravar" id="os_revenda_form_submit" data-submit="" />
    </div>
</form>

<? if (getValue('notas_fiscais_adicionadas') == '\'semNota\'') {
    $notas_fiscais_adicionadas = getValue('notas_fiscais_adicionadas');
    $notas_fiscais_adicionadas = "\"$notas_fiscais_adicionadas\"";
} else if (strlen(getValue('notas_fiscais_adicionadas')) > 0) {
    $notas_fiscais_adicionadas = getValue('notas_fiscais_adicionadas');
    $notas_fiscais_adicionadas = explode(",", $notas_fiscais_adicionadas);
    foreach ($notas_fiscais_adicionadas as $key => $nota) {
        $nota = str_replace("'", "", $nota);
        if ($nota == 'semNota') {
            $notas_fiscais_adicionadas[$key] = "\"'semNota'\"";
        }else{
            $notas_fiscais_adicionadas[$key] = "'$nota'";
        }
    }
    $notas_fiscais_adicionadas = implode(",", $notas_fiscais_adicionadas);
} ?>

<script type="text/javascript">
$(function() {
    var pLat;
    var pLng;

    var cLat;
    var cLng;

    var geometry;

    if (typeof Map !== "object") {
        Map      = new Map("google_maps");
        Markers  = new Markers(Map);
        Router   = new Router(Map);
        Geocoder = new Geocoder();
    }

    $("#div_informacoes_deslocamento").hide();
    $("#div_informacoes_agendamento").hide();
    $("#google_maps").hide();

    $("#ver-mapa").on("click", function() {
        if ($(this).is(":checked")) {
            $("#google_maps").show();
            if ($("#google_maps").html() == '') {
                mostraMapa();
            }
        } else {
            $("#google_maps").hide();
        }
    });

    <?php if ($fabricaFileUploadOS) { ?>
        $("#anexo_notas").on("click", function() {
            if ($(this).is(":checked")) {
                $("#select_notas").show();
            } else {
                $("#select_notas").hide();
                $("#campo_descricao").val('');
            }
        });
    <?php } ?>
    
    var notas_adicionadas = [<?= $notas_fiscais_adicionadas; ?>];

    /**
     * Inicia o shadowbox, obrigatório para a lupa funcionar
     */
    Shadowbox.init();
    <?php if ($login_fabrica != 178){ ?>
        $("#data_abertura").datepicker({ maxDate: 0, minDate: "-7d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    <?php } ?>    

    <? if (empty(getValue('data_abertura'))) { ?>
        $("#data_abertura").datepicker("setDate", new Date());
    <? } ?>

    $(".data_fab").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" });
    $(".data_fab").mask("99/99/9999");
    $("#data_nf").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    $(".data_nf_atualizada").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

    <?php if (!in_array($login_fabrica, [178,186])){ ?>
    $("#revenda_cnpj").mask("99.999.999/9999-99",{placeholder:""});
    <?php } ?>
    $("#revenda_cep").mask("99999-999",{placeholder:""});
    $("#revenda_fone").mask("(99) 9999-9999");

    $(".numeric").numeric();

    $(document).on("click", ".btn_lista_basica", function(){
        let posicao = $(this).data("posicao");
        let produto = $("input[name='produtos["+posicao+"][id]']").val();
        let tipo_atendimento = $("select[name='produtos["+posicao+"][tipo_atendimento]']").val();
        let defeito_constatado = $("select[name='produtos["+posicao+"][defeito_constatado]']").val();
        let info_pecas = $("input[name='produtos["+posicao+"][info_pecas]']").val();

        <?php if ($areaAdmin === true){ ?>
            let posto_id = $("#posto_id").val();
        <?php } ?>
        
        if (produto == "" || produto == undefined){
            alert("<?php echo traduz('informe.o.defeito.constatado.para.lancar.pecas');?>");     
            return false;
        }

        if (defeito_constatado == "" || defeito_constatado == undefined) {
            alert("<?php echo traduz('informe.o.defeito.constatado.para.lancar.pecas');?>");     
            return false;
        }

        if (tipo_atendimento == "" || tipo_atendimento == undefined) {
            alert("<?php echo traduz('informe.o.tipo.de.atendimento.para.lancar.pecas');?>");     
            return false;
        }

        let url = "lista_basica_lupa_new.php?produto="+produto+"&garantia=true"+"&tipo_atendimento="+tipo_atendimento+"&posicao="+posicao+"&info_pecas="+info_pecas+"&page=cadastro_os_revenda&posto="+posto_id;
        
        if (typeof produto != "undefined" && produto.length > 0) {
            Shadowbox.open({
                content: url,
                player: "iframe",
                height: 600,
                width: 1048,
                options: {
                    onClose: function() {
                        $("select[name^=produto_pecas], select[name^=subproduto_pecas]").css({ visibility: "visible" });
                    }
                }
            });
        } else {
            alert("<?php echo traduz('selecione.um.produto.para.pesquisar.sua.lista.basica');?>");
        }
    });

    $(document).on("click", ".btn_peca_servico", function(){
        let posicao = $(this).data("posicao");
        let info_pecas = $(this).data("info_pecas");
        
        info_pecas = JSON.stringify(info_pecas);
        
        let url = "pecas_servicos_os_revenda.php?info_pecas="+info_pecas;
        
        if (info_pecas != undefined && info_pecas != "") {
            Shadowbox.open({
                content: url,
                player: "iframe",
                height: 600,
                width: 800,
                options: {
                    onClose: function() {
                        $("select[name^=produto_pecas], select[name^=subproduto_pecas]").css({ visibility: "visible" });
                    }
                }
            });
        } else {
            alert("<?php echo traduz('selecione.um.produto.para.pesquisar.sua.lista.basica');?>");
        }
    });

    <?php if ($login_fabrica == 175){ ?>
        $("#nota_fiscal").numeric();
    <?php } ?>
    /**
     * Evento que chama a função de lupa para a lupa clicada
     */
    $(document).on("click", "span[rel=lupa]", function() {
        $.lupa($(this));
    });

    /**
     * Evento que chama a função de change do tipo de atendimento
     */
    <?php if ($login_fabrica != 178){ ?>
        $(document).on("change", "#solicitar_deslocamento", function() {
            if ($(this).is(":checked") && $("#div_informacoes_deslocamento").not(":visible")) {
                $("#div_informacoes_deslocamento").show();
            } else if ($(this).not(":checked")) {
                $("#div_informacoes_deslocamento").hide();
            }
        });
    <?php } ?>

    <?php

    if (in_array($login_fabrica, [186])) { 

        ?>
        if ($("#revenda_cnpj").attr("readonly") == "readonly") {
            $("#cpf_cnpj_revenda").closest("label").html("CPF/CNPJ");
        }

        $("input[name=revenda_cpf_cnpj]").click(function(){
            var tipo = $(this).val();
            
            $("#revenda_cnpj").unmask();
            if(tipo == 'cnpj'){
                $("#revenda_cnpj").mask("99.999.999/9999-99",{placeholder:""});
            }else{
                $("#revenda_cnpj").mask("999.999.999-99",{placeholder:""});
            }
        });

        $("input[name=revenda_cpf_cnpj]:checked").click();

    <?php
    } ?>

    <?php
    if (in_array($login_fabrica, [169,170])) { ?>
        $(document).on("change", ".tipo_atendimento", function() {

            let inp_contato = $("#revenda_contato").prev("h5");

            if ($.trim($(this).find("option:selected").text()) == "Triagem") {
                $(inp_contato).show();
            } else {

                let triagem = false;
                $(".tipo_atendimento").each(function(){

                    if ($.trim($(this).find("option:selected").text()) == "Triagem") {

                        let triagem = true;

                    }

                });

                if (triagem) {
                    $(inp_contato).show();
                } else {
                    $(inp_contato).hide();
                }

            }

        });

        $(".tipo_atendimento").change();

    <?php
    }

    if ($login_fabrica == 178){ ?>

        $("#data_abertura").datepicker({ maxDate: 0, minDate: "0d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    
        $('#btnPopover').popover();
        $("#data_agendamento").datepicker({ minDate: "-7d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");

        <?php if (!empty(getValue("agendamento[data_agendamento]")) AND !empty(getValue("agendamento[tecnico]")) AND empty($msg_erro["msg"])){ ?>
            $("#data_agendamento").datepicker("destroy");
            $("#data_agendamento").prop("readonly", true);
            $("#tecnico").attr("readonly", "readonly");
            $("#periodo_visita").attr("readonly", "readonly");
        <?php } ?>

        $("#data_visita_realizada").datepicker({ minDate: "-7d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
        $("#revenda_celular").numeric({ allow: "()- " });
        $("#revenda_celular").mask("(99) 99999-9999",{placeholder:""});

        $(document).on("change", "#solicitar_deslocamento", function() {
            if ($(this).is(":checked") && $("#div_informacoes_deslocamento").not(":visible")) {
                $("#div_informacoes_deslocamento").show();
                $("#div_informacoes_agendamento").show();

                if ($("#revenda_bairro").val().length > 0) {
                    $("#calcular_km").click();
                }
            } else if ($(this).not(":checked")) {
                $("#div_informacoes_deslocamento").hide();
                $("#div_informacoes_agendamento").hide();
                $("#qtde_km").val("");
                $("#data_agendamento").val("");
                $("#tecnico").val("");
                $("#data_visita_realizada").val("");
            }
        });

        if ($("#solicitar_deslocamento").is(":checked") && $("#div_informacoes_deslocamento").not(":visible")){
            $("#div_informacoes_deslocamento").show();
            $("#div_informacoes_agendamento").show();
        }

        $(document).on("change", ".consumidor_revenda", function() {
            var consumidor_revenda = $(this).val();
            var consumidor_revenda_antes = $("#consumidor_revenda_antes").val();

            if (consumidor_revenda_antes != consumidor_revenda && ($("#revenda_nome").val() != "" || $("#revenda_cnpj").val() != "")){
                if( !confirm('Atenção alguns dados da Informações da OS Revenda podem ser perdidos?')) {
                    $(this).val(consumidor_revenda_antes);
                    return false;
                }
                limpaCampos();
            }
            $("#consumidor_revenda_antes").val(consumidor_revenda);
            if (consumidor_revenda == "C" || consumidor_revenda == "S"){
                //$(".div_revenda_nome").find("#revenda_nome").next(".add-on").hide();
                // Liberar para colocar lupa de consumidor
                $(".div_revenda_nome").find("input[name='lupa_config']").attr("tipo", "consumidor_os");
                $(".div_revenda_nome").find("input[name='lupa_config']").attr("parametro", "nome_consumidor");

                if (consumidor_revenda == "S"){
                    $(".div_cep_inscricao").show();
                    $(".div_revenda_nome").find("label > b").text("(Construtora)");
                    $(".div_revenda_cnpj").find("label").text("CNPJ");
                    $("#revenda_cnpj").mask("99.999.999/9999-99",{placeholder:""});
                    $(".div_inscricao_estadual").show();
                    $(".div_cep_consumidor").hide();
                    $(".div_bairro").removeClass("span4");
                    $(".div_bairro").addClass("span2");
                }else{
                    $(".div_bairro").removeClass("span2");
                    $(".div_bairro").addClass("span4");
                    $(".div_cep_inscricao").show();
                    $(".div_cep_consumidor").show();
                    $(".div_inscricao_estadual").hide();
                    $("#inscricao_estadual").val('');

                    $(".div_revenda_nome").find("label > b").text("(Consumidor)");
                    $(".div_revenda_cnpj").find("label").text("CPF/CNPJ");
                    
                    $("#revenda_cnpj").unmask();
                    
                    $("#revenda_cnpj").focus(function(){
                        $(this).unmask();
                    });
                    
                    $("#revenda_cnpj").blur(function(){
                        $(this).unmask();
                        var el = $(this);
                        var caracteresDigitados = el.val().replace(/[^\d]+/g,'').length;
                        if (caracteresDigitados > 11){
                            el.mask("99.999.999/9999-99", {placeholder:""});
                        }else{
                            el.mask("999.999.999-99", {placeholder:""});
                        }
                    });
                }

                // Liberar para colocar lupa de consumidor
                $(".div_revenda_cnpj").find("input[name='lupa_config']").attr("tipo", "consumidor_os");
                $(".div_revenda_cnpj").find("input[name='lupa_config']").attr("parametro", "cpf_cnpj");

                $("#revenda_cnpj_consumidor").mask("99.999.999/9999-99",{placeholder:""});

                $("#revenda_cep").parent().prepend('<h5 class="asteristico">*</h5>');
                $("#revenda_cidade").parent().prepend('<h5 class="asteristico">*</h5>');
                $("#revenda_estado").parent().prepend('<h5 class="asteristico">*</h5>');
                $("#revenda_bairro").parent().prepend('<h5 class="asteristico">*</h5>');
                $("#revenda_endereco").parent().prepend('<h5 class="asteristico">*</h5>');
                $("#revenda_numero").parent().prepend('<h5 class="asteristico">*</h5>');
                $("#revenda_fone").parent().prepend('<h5 class="asteristico">*</h5>');
                $("#revenda_email").parent().prepend('<h5 class="asteristico">*</h5>');

                $("#div_informacoes_os_revenda_consumidor").show();

            }else{
                //$(".div_revenda_nome").find("#revenda_nome").next(".add-on").show();
                // Liberar para colocar lupa de consumidor
                $(".div_inscricao_estadual").show();
                $(".div_bairro").removeClass("span4");
                $(".div_bairro").addClass("span2");
                $(".div_cep_inscricao").show();
                $(".div_cep_consumidor").hide();
                $(".div_revenda_nome").find("input[name='lupa_config']").attr("tipo", "revenda");
                $(".div_revenda_nome").find("input[name='lupa_config']").attr("parametro", "razao_social");

                $(".div_revenda_nome").find("label > b").text("(Revenda)");

                //$(".div_revenda_cnpj").find("#revenda_cnpj").next(".add-on").show();
                // Liberar para colocar lupa de consumidor
                $(".div_revenda_cnpj").find("input[name='lupa_config']").attr("tipo", "revenda");
                $(".div_revenda_cnpj").find("input[name='lupa_config']").attr("parametro", "cnpj");

                $(".div_revenda_cnpj").find("label").text("CNPJ");
                $("#revenda_cnpj").mask("99.999.999/9999-99",{placeholder:""});
                
                $("#revenda_cep").parent().prepend('<h5 class="asteristico">*</h5>');
                $("#revenda_cidade").parent().find('.asteristico').remove();
                $("#revenda_estado").parent().find('.asteristico').remove();
                $("#revenda_bairro").parent().find('.asteristico').remove();
                $("#revenda_endereco").parent().find('.asteristico').remove();
                $("#revenda_numero").parent().find('.asteristico').remove();
                $("#revenda_fone").parent().find('.asteristico').remove();
                $("#revenda_email").parent().find('.asteristico').remove();

                $("#div_informacoes_os_revenda_consumidor").hide();
            }
        });

        $(document).on("change", ".tipo_atendimento", function(){
            var posicao = $(this).data("posicao");
            var tipo_atendimento = $(this).val();
            var value = $(this).data("value");
            var info_pecas = $("input[name='produtos["+posicao+"][info_pecas]']").val();
            var km_google = $(this).find('option:selected').attr("km_google");

            if (info_pecas != "" && info_pecas != undefined){
                if( !confirm('Ao alterar o Tipo de Atendimento você vai perder as peças/serviços lançados, deseja continuar?')) {
                    $(this).val(value);
                    return;
                }
            }

            if (km_google == "t"){
                if ($("#solicitar_deslocamento").is(":checked") === false && $("#div_informacoes_deslocamento").is(":visible") === false) {
                    alert("Atenção! É necessário marcar a opção selecionar deslocamento e preencher  as Informações do Agendamento");
                    $('html, body').animate({scrollTop : 0},800);
                }
            }

            $(this).data("value", tipo_atendimento);
            $(".informacoes_pecas"+posicao).find("p").remove();
            $("input[name='produtos["+posicao+"][info_pecas]']").val("");
        });

        /**
         *  Evento que valida data  nota fiscal do produto atualizada
         */
        $(document).on("change", ".data_nf_atualizada", function(){
            let posicao = $(this).data("posicao");
            let data_nf_atualizada = $(this).val();
            let data_abertura = $("#data_abertura").val();

            //let data_nf_atualizada = $("input[name='produtos["+posicao+"][data_nf]']").val();
            //let nota_fiscal_atualizada = $("input[name='produtos["+posicao+"][nota_fiscal]']").val();
            
            // if ($.inArray(nota_fiscal, notas_adicionadas) != -1) {
            //     alert("Nota Fiscal já adicionada");
            //     return false;
            // }
            if (data_nf_atualizada == "") {
                alert("Informe o número e a data da nota fiscal");
                return false;
            }

            $.ajax({
                url: "cadastro_os_revenda.php",
                type: "POST",
                dataType:"JSON",
                data: {
                    ajax: true,
                    valida_data_nota_fiscal: true,
                    data_nf_atualizada: data_nf_atualizada,
                    data_abertura: data_abertura
                }
            })
            .done(function(data) {
                if (data.error == "error"){
                    alert(data.result);
                    $("input[name='produtos["+posicao+"][data_nf]']").val("");
                }
            });
        });

        /**
         * Evento que valida se já existe nota fiscal adicionada 
         */
        $(document).on("change", ".nota_fiscal_atualizada", function(){
            let posicao = $(this).data("posicao");
            let nota_fiscal_atualizada = $(this).val();
            
            if ($.inArray(nota_fiscal_atualizada, notas_adicionadas) != -1) {
                alert("Nota Fiscal já adicionada");
                $("input[name='produtos["+posicao+"][nota_fiscal]']").val("");
                return false;
            }
        });

        var consumidor_revenda = $(".consumidor_revenda").val();
        
        if (consumidor_revenda == "C" || consumidor_revenda == "S"){
            //$(".div_revenda_nome").find("#revenda_nome").next(".add-on").hide();
            // Liberar para colocar lupa de consumidor
            $(".div_revenda_nome").find("input[name='lupa_config']").attr("tipo", "consumidor_os");
            $(".div_revenda_nome").find("input[name='lupa_config']").attr("parametro", "nome_consumidor");

            if (consumidor_revenda == "S"){
                $(".div_revenda_nome").find("label > b").text("(Construtora)");
                $(".div_revenda_cnpj").find("label").text("CNPJ");
                $("#revenda_cnpj").mask("99.999.999/9999-99",{placeholder:""});
            }else{
                $(".div_revenda_nome").find("label > b").text("(Consumidor)");
                $(".div_revenda_cnpj").find("label").text("CPF/CNPJ");
                
                $("#revenda_cnpj").unmask();
                    
                $("#revenda_cnpj").focus(function(){
                    $(this).unmask()
                });
                
                $("#revenda_cnpj").blur(function(){
                    $(this).unmask();
                    var el = $(this);

                    var caracteresDigitados = el.val().length;
                    if (caracteresDigitados > 11){
                        el.mask("99.999.999/9999-99");
                    }else{
                        el.mask("999.999.999-99");
                    }
                });
            }

            // Liberar para colocar lupa de consumidor
            $(".div_revenda_cnpj").find("input[name='lupa_config']").attr("tipo", "consumidor_os");
            $(".div_revenda_cnpj").find("input[name='lupa_config']").attr("parametro", "cpf_cnpj");

            $("#revenda_cnpj_consumidor").mask("99.999.999/9999-99",{placeholder:""});
            $("#revenda_cep").parent().prepend('<h5 class="asteristico">*</h5>');
            $("#revenda_cidade").parent().prepend('<h5 class="asteristico">*</h5>');
            $("#revenda_estado").parent().prepend('<h5 class="asteristico">*</h5>');
            $("#revenda_bairro").parent().prepend('<h5 class="asteristico">*</h5>');
            $("#revenda_endereco").parent().prepend('<h5 class="asteristico">*</h5>');
            $("#revenda_numero").parent().prepend('<h5 class="asteristico">*</h5>');
            $("#revenda_fone").parent().prepend('<h5 class="asteristico">*</h5>');
            $("#revenda_email").parent().prepend('<h5 class="asteristico">*</h5>');
        }else{
            //$(".div_revenda_nome").find("#revenda_nome").next(".add-on").show();
            // Liberar para colocar lupa de consumidor
            $(".div_revenda_nome").find("input[name='lupa_config']").attr("tipo", "revenda");
            $(".div_revenda_nome").find("input[name='lupa_config']").attr("parametro", "razao_social");

            $(".div_revenda_nome").find("label > b").text("(Revenda)");

            //$(".div_revenda_cnpj").find("#revenda_cnpj").next(".add-on").show();
            // Liberar para colocar lupa de consumidor
            $(".div_revenda_cnpj").find("input[name='lupa_config']").attr("tipo", "consumidor_os");
            $(".div_revenda_cnpj").find("input[name='lupa_config']").attr("parametro", "cpf_cnpj");

            $(".div_revenda_cnpj").find("label").text("CNPJ");

            $("#revenda_cnpj").mask("99.999.999/9999-99",{placeholder:""});
                
            $("#revenda_cep").parent().find('.asteristico').remove();
            $("#revenda_cidade").parent().find('.asteristico').remove();
            $("#revenda_estado").parent().find('.asteristico').remove();
            $("#revenda_bairro").parent().find('.asteristico').remove();
            $("#revenda_endereco").parent().find('.asteristico').remove();
            $("#revenda_numero").parent().find('.asteristico').remove();
            $("#revenda_fone").parent().find('.asteristico').remove();
            $("#revenda_email").parent().find('.asteristico').remove();
        }

    <?php } ?>

    <?php if ($login_fabrica == 175){ ?>
        $(document).on("change", ".nserie", function() {
            var serie = $(this).val();
            var posicao = $(this).attr('posicao_serie');
            var data_abertura = $("#data_abertura").val();
            var produto = $("input[name='produtos["+posicao+"][id]']").val();
            var nota_fiscal = $("input[name='produtos["+posicao+"][nota_fiscal]']").val();
            var data_nf = $("input[name='produtos["+posicao+"][data_nf]']").val();
            if (data_abertura == '' || data_abertura == undefined){
                alert("Preencha a data de abertura para continuar");
                $("input[name='produtos["+posicao+"][serie]']").val('');
                return false;
            }

            if (produto != '' && produto != undefined && serie != '' && serie != undefined && data_abertura != '' && data_abertura != undefined){
                valida_nserie (produto,serie,data_abertura,posicao,nota_fiscal, data_nf);
            }
        });
    <?php } ?>

    <?php if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep")) ){ ?>

        <?php if ($login_tipo_posto_codigo == "Rev"){ ?>
            $("#div_informacoes_os_revenda").find("input").attr("readOnly", true);
            $("input[name='data_abertura']").attr("readOnly", false);
            $("#div_informacoes_os_revenda").find("select").attr("readOnly", true);
            $('#div_informacoes_os_revenda option:not(:selected)').remove();
        <?php } ?>
        
        $(document).on("click", "#listar_produtos_nf", function (){
            let nota_fiscal = $("input[name='nota_fiscal']").val();
            let posto = $("#posto_id").val();
            let data_nf = $("#data_nf").val();
            
            $.ajax({
                url: "cadastro_os_revenda.php",
                type: "POST",
                dataType:"JSON",
                data: {
                    ajax_listar_produtos_nf: true,
                    posto: posto,
                    nota_fiscal: nota_fiscal,
                    data_nf: data_nf
                },
                beforeSend: function() {
                    $("#listar_produtos_nf").html('<i class="icon-white icon-th-list"></i> Listando produtos ...').prop("disabled", true);
                }
            })
            .done(function(data) {
                $("#listar_produtos_nf").html('<i class="icon-white icon-th-list"></i> Listar produtos').prop("disabled", false);
                if (data.ok == "success"){
                    $("#notas_fiscais_adicionadas").val(nota_fiscal);
                    $("#div_informacoes_data_nf").hide();
                    $.each(data.result, function(idx, elem){
                        if (idx == 0){
                            adiciona_nota(elem.produto, elem.referencia, elem.descricao, elem.serie, elem.qtde);
                        }else{
                            adiciona_linhas(elem.produto, elem.data_nf, elem.nota_fiscal,elem.referencia, elem.descricao, elem.serie, elem.qtde);
                        }
                    });    
                }else{
                    $("#div_listar_produtos_nf").find("br").remove();
                    $("#group_listar_produtos").remove();
                    $("#div_listar_produtos_nf").prepend("\
                        <br/><div class='control-group'>\
                            <div class='controls controls-row tac'>\
                                <button type='button' class='btn btn-success' title='Adicionar Nota Fiscal' id='adicionar_nota_fiscal' ><i class='icon-white icon-plus'></i>Adicionar NF</button>\
                            <div>\
                        </div>\
                    ");
                
                    //se o erro for de não encotrar retornar alert de nf não encontrada.
                    //alert("Nenhuma NF encontrada");
                }
            });
        });
            
    <?php } ?>
    /**
     * Evento que chama a lupa do produto
     */
    $(document).on("click", "span[rel=lupa_produto]", function() {
        var parametros_lupa_produto = ["posto", "ativo", "posicao"];

        <? if ($usaProdutoGenerico) { ?>
            parametros_lupa_produto.push("produto-generico");
        <? }
        if (in_array($login_fabrica, array(169,170))) { ?>
            parametros_lupa_produto.push("mascara");
            $(this).next("input[name=lupa_config]").attr("grupo-atendimento", $("#tipo_atendimento").find("option:selected").attr("grupo_atendimento"));
            parametros_lupa_produto.push("grupo-atendimento");
            $(this).next("input[name=lupa_config]").attr("fora-garantia", $("#tipo_atendimento").find("option:selected").attr("fora_garantia"));
            parametros_lupa_produto.push("fora-garantia");
            $(this).next("input[name=lupa_config]").attr("km-google", $("#tipo_atendimento").find("option:selected").attr("km_google"));
            parametros_lupa_produto.push("km-google");
            parametros_lupa_produto.push("tela_cadastro_os");
        <? } ?>
        $.lupa($(this), parametros_lupa_produto);
    });

    $("#os_revenda_form_submit").on("click", function(e) {
        e.preventDefault();

        var submit = $(this).data("submit");
        if (submit.length == 0) {
            $(this).data({ submit: true });
            $("input[name=gravar]").val('Gravar');
            $(this).parents("form").submit();
        } else {
           alert("Não clique no botão voltar do navegador, utilize somente os botões da tela");
        }
    });

    /**
     * Evento para quando alterar o estado carregar as cidades do estado
     */
    $("select[id$=_estado]").change(function() {
        busca_cidade($(this).val());
    });

    /**
     * Evento para buscar o endereço do cep digitado
     */
    $("input[id$=_cep]").blur(function() {
        if ($(this).attr("readonly") == undefined) {
            busca_cep($(this).val());
        }
    });

    /**
     * Evento que adiciona uma nova linha de produto
     */
    $(document).on("click", "button[name^=adicionar_linha_]", function() {
        var rel = $(this).attr("rel").replace('___','.').split("_");
		console.log(rel);
        var nota_fiscal = rel[0].replace('.','___');
        var nota_fiscal2 = nota_fiscal.replace('___','.');
        var data_nf = rel[1];
        var nova_linha = $("#modelo_produto").clone();
        var posicao = $("div.row-fluid[name^=produto_][name!=produto___modelo__]").length;
        $("#produto_"+nota_fiscal).append($(nova_linha).html().replace(/__modelo__/g, posicao).replace(/disabled\=['"]disabled['"]/g, ""));
        $("input[name='produtos["+posicao+"][nota_fiscal]']").val(nota_fiscal2);
        $("input[name='produtos["+posicao+"][data_nf]']").val(data_nf);
        $("div.row-fluid[name=produto_"+posicao+"]").find(".numeric").numeric();

        $("div.row-fluid[name=produto_"+posicao+"]").find(".data_fab").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" });
        $("div.row-fluid[name=produto_"+posicao+"]").find(".data_fab").mask("99/99/9999");
    });

    /**
     * Evento que adiciona uma nova linha de nota fiscal
     */
    $(document).on("click", "#adicionar_nota_fiscal", function() {
        var nota_fiscal = $("#nota_fiscal").val().replace(/\s/g, '');
        var data_nf = $("#data_nf").val();
        var data_abertura = $("#data_abertura").val();
        var nova_linha = $("#modelo_nota_fiscal").clone();
        var posicao = $("div.row-fluid[name^=produto_][name!=produto___modelo__]").length;

        if ($.inArray(nota_fiscal, notas_adicionadas) != -1) {
            alert("Nota Fiscal já adicionada");
            return false;
        }

        if (data_abertura != "" && data_abertura != undefined && data_nf != "" && data_nf != undefined){
            [nf_dia, nf_mes, nf_ano] = data_nf.split("/");
            [ab_dia, ab_mes, ab_ano] = data_abertura.split("/");
            
            var valida_data_nf = new Date(nf_ano+"/"+nf_mes+"/"+nf_dia);
            var valida_data_ab = new Date(ab_ano+"/"+ab_mes+"/"+ab_dia);
            
            if (valida_data_nf > valida_data_ab){
                alert("Data nota fiscal não pode ser maior que a data de abertura");
                return false;
            }
        }

        if (nota_fiscal == "" || data_nf == "") {
            alert("Informe o número e a data da nota fiscal");
            return false;
        } else {
            $("#nota_fiscal").val("");
            $("#data_nf").val("");
        }

        if ($("#div_informacoes_produto").not(":visible")) {
            $("#div_informacoes_produto").show();
        }

        $("#div_produtos").append($(nova_linha).html().replace(/__nota__/g, nota_fiscal).replace(/__modelo__/g, posicao).replace(/disabled\=['"]disabled['"]/g, ""));
        $("#label_nota_fiscal_"+nota_fiscal).html(nota_fiscal);
        $("#label_data_nf_"+nota_fiscal).html(data_nf);
        $("input[name='produtos["+posicao+"][nota_fiscal]']").val(nota_fiscal);
        $("input[name='produtos["+posicao+"][data_nf]']").val(data_nf);
        $("div.row-fluid[name=produto_"+posicao+"]").find(".numeric").numeric();
        $("button[name=adicionar_linha_"+nota_fiscal+"]").attr("rel", nota_fiscal+"_"+data_nf);

        notas_adicionadas.push(nota_fiscal);
        $("#notas_fiscais_adicionadas").val(notas_adicionadas);

        <?php if ($fabricaFileUploadOS) { ?>
            <?php if ($login_fabrica != 178){ ?>
            $("#div_anexo_notas").show();
            <?php } ?>
            var option = "<option value=''>Selecione uma nota fiscal</option>";
            $.each(notas_adicionadas, function (key, value) {
                option += "<option value='Nota Fiscal "+value+"'>Nota Fiscal: "+value+"</option>";
            });
            $('#select_notas').find('select').html(option);
        <?php } ?>
        
        <?php if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep")) ){ ?>
            $("#div_informacoes_data_nf").hide();
        <?php } ?>

        $("div.row-fluid[name=produto_"+posicao+"]").find(".data_fab").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" });
        $("div.row-fluid[name=produto_"+posicao+"]").find(".data_fab").mask("99/99/9999");
    });

    $(document).on("change", ".defeito_constatado_grupo", function() {
        var grupo = $(this).val();
        var posicao = $(this).attr("rel");
        var produto = $("input[name='produtos["+posicao+"][id]']").val();
        
        $("#defeito_constatado"+posicao).html('');
        $.ajax({
            url: "cadastro_os_revenda.php",
            type: "POST",
            dataType:"JSON",
            data: {
                ajax_busca_defeito_constatado: true,
                produto: produto,
                grupo: grupo
            }
        })
        .done(function(data) {
            if (data.defeitos_constatados) {
                var option = "<option value=''>Selecione</option>";
                $.each(data.defeitos_constatados, function(key, value) {
                    var descricao = value.descricao;
                    option += "<option value='"+value.defeito_constatado+"' data-lancar-pecas='"+value.lancar_pecas+"' data-lista-garantia='"+value.lista_garantia+"' data-defeito-grupo='"+value.defeito_constatado_grupo+"' >"+descricao+"</option>";
                });
                $("#defeito_constatado"+posicao).append(option);
            } else {
                $("#defeito_constatado"+posicao).append("<option value='' >Nenhum defeito encontrado</option>");
            }
            $("#defeito_constatado"+posicao).show().next().remove();
        });
    });

    /**
     * Evento que adiciona uma nova linha sem nota fiscal
     */
    $(document).on("click", "#adicionar_sem_nota_fiscal", function() {
        var nota_fiscal = 'semNota';
        var data_nf = 'semNota';
        var nova_linha = $("#modelo_sem_nota_fiscal").clone();
        var posicao = $("div.row-fluid[name^=produto_][name!=produto___modelo__]").length;

        if ($.inArray("'"+nota_fiscal+"'", notas_adicionadas) != -1 || $.inArray(nota_fiscal, notas_adicionadas) != -1) {
            alert("Produto sem nota fiscal já foi adicionado, clique em adicionar novo produto do grupo de produtos sem nota");
            return false;
        }

        if ($("#div_informacoes_produto").not(":visible")) {
            $("#div_informacoes_produto").show();
        } 
        
        $("#div_produtos").append($(nova_linha).html().replace(/__nota__/g, nota_fiscal).replace(/__modelo__/g, posicao).replace(/disabled\=['"]disabled['"]/g, ""));
        $("input[name='produtos["+posicao+"][nota_fiscal]']").val(nota_fiscal);
        $("div.row-fluid[name=produto_"+posicao+"]").find(".numeric").numeric();
        $("button[name=adicionar_linha_"+nota_fiscal+"]").attr("rel", nota_fiscal+"_"+data_nf);

        notas_adicionadas.push("'"+nota_fiscal+"'");
        $("#notas_fiscais_adicionadas").val(notas_adicionadas);

        $("div.row-fluid[name=produto_"+posicao+"]").find(".data_fab").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" });
        $("div.row-fluid[name=produto_"+posicao+"]").find(".data_fab").mask("99/99/9999");
    });

    /**
     * Remove NF e todos os produtos amarrados enquanto a os não foi gravada
     */
    $(document).on("click", "button[name=remove_nf]", function() {

        var nota_fiscal = $(this).attr("rel");
        var nota_fiscal_aux = "";

    	if (nota_fiscal == 'semNota') {
            var nota_fiscal_aux = "'"+nota_fiscal+"'";
    	}

        if(confirm("Tem certeza que deseja remover a nota fiscal "+nota_fiscal+" e todos os seus produtos?") == true) {
            $("#div_nota_fiscal_"+nota_fiscal).remove();
	        var index = -1;
    	    if (nota_fiscal_aux.length > 0) {
                index = notas_adicionadas.indexOf(nota_fiscal_aux);
    	    } else {
                index = notas_adicionadas.indexOf(nota_fiscal);
    	    }

            if (index > -1) {
                notas_adicionadas.splice(index, 1);
                $("#notas_fiscais_adicionadas").val(notas_adicionadas);
            }
            var linhas = $("div.row-fluid[name^=produto_][name!=produto___modelo__]").length;
            if (linhas == 0) {
                $("#div_informacoes_produto").hide();
            }
        }
        
        <?php if ($fabricaFileUploadOS) { ?>
            var option = "<option value=''>Selecione uma nota fiscal</option>";
            $.each(notas_adicionadas, function (key, value) {
                option += "<option value='Nota Fiscal "+value+"'>Nota Fiscal: "+value+"</option>";
            });
            $('#select_notas').find('select').html(option);
            if ($("#notas_fiscais_adicionadas").val() == '') {
                $("#select_notas").hide();
                $("#div_anexo_notas").hide();
                $('#anexo_notas').prop('checked', false);
            }
        <?php } ?>

        <?php if ($login_fabrica == 183){ ?>
            $("#div_informacoes_data_nf").show();
        <?php } ?>
    });

    /**
     * Evento que limpa uma linha de produto
     */
    $(document).on("click", "button[name=remove_produto]", function() {
        var posicao = $(this).attr("rel");
        var os_revenda_item = $("input[name='produtos["+posicao+"][os_revenda_item]']").val();

        if (os_revenda_item != undefined && os_revenda_item.length > 0) {
            alert('O produto não pode ser removido, ordem de serviço já aberta');
        }

        $("input[name='produtos["+posicao+"][os_revenda_item]']").val("");
        $("input[name='produtos["+posicao+"][id]']").val("");
        $("input[name='produtos["+posicao+"][serie]']").val("").removeAttr("readonly");
        $("input[name='produtos["+posicao+"][referencia]']").val("").removeAttr("readonly");
        $("input[name='produtos["+posicao+"][descricao]']").val("").removeAttr("readonly");
        $("input[name='produtos["+posicao+"][qtde]']").val("").removeAttr("readonly");

        <?php if ($login_fabrica == 177){ ?>  
            $("input[name='produtos["+posicao+"][lote]']").val("").removeAttr("readonly");
            $(".h5_lote"+posicao).hide();
        <?php } ?>
        
        <?php if (in_array($login_fabrica, array(178,183))){ ?>
            $("#marca"+posicao).val("");
            $("select[name='produtos["+posicao+"][tipo_atendimento]']").val("");
            $("select[name='produtos["+posicao+"][defeito_constatado]']").val("");
            $("select[name='produtos["+posicao+"][defeito_reclamado]']").val("");
            $("input[name='produtos["+posicao+"][info_pecas]']").val("");
            $("#box_fora_linha_"+posicao).remove();
            $(".informacoes_pecas"+posicao).find("p").remove();
            $("input[name='produtos["+posicao+"][produto_fora_linha]']").val("");
        <?php } ?>
        
        $("div[name=produto_"+posicao+"]").find("span[rel=lupa_produto]").show();

        $(this).parents("div.row-fluid").css({ "background-color": "transparent" });
        $(this).hide();
    });

    /**
    * Eventos para anexar imagem
    */
    $("form[name=form_anexo]").ajaxForm({
        complete: function(data) {
            data = $.parseJSON(data.responseText);

            if (data.error) {
                alert(data.error);
            } else {
                var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                $(imagem).attr({ src: data.link });

                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                var link = $("<a></a>", {
                    href: data.href,
                    target: "_blank"
                });

                $(link).html(imagem);

                $("#div_anexo_"+data.posicao).prepend(link);

                if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
                    setupZoom();
                }

                $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
            }

            $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
            $("#div_anexo_"+data.posicao).find("button").show();
            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
        }
    });

    $("button[name=anexar]").click(function() {
        var posicao = $(this).attr("rel");
        $("input[name=anexo_upload_"+posicao+"]").click();
    });

    $("input[name^=anexo_upload_]").change(function() {
        var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

        $("#div_anexo_"+i).find("button").hide();
        $("#div_anexo_"+i).find("img.anexo_thumb").hide();
        $("#div_anexo_"+i).find("img.anexo_loading").show();

        $(this).parent("form").submit();
    });

    /**
     * Evento para calcular o KM
     */
    $("#calcular_km").click(function() {
        try {
            calcula_km_revenda();
        } catch(e) {
            alert(e.message);
        }
    });

    /**
     * Evento de click do botão trocar_posto
     * Irá remover o readonly dos campos código e nome e dar um show nas lupas
     */
    $("#trocar_posto").click(function() {
        $("#div_informacoes_posto").find("input").val("");
        $("#div_informacoes_posto").find("input[readonly=readonly]").removeAttr("readonly");
        $("#div_informacoes_posto").find("span[rel=lupa]").show();
        $("#div_trocar_posto").hide();

        <? if ($areaAdmin === true) { ?>
            $("input[name=lupa_config][tipo=produto]").attr({ posto: "" });
        <? } ?>
    });

    <?php if ($login_fabrica == 178){ ?>
        $("#revenda_numero").blur(function() {
            if ($("#div_informacoes_deslocamento").is(":visible")) {
                calcula_km_revenda();
            }
        });
        <?php if (!$areaAdmin){ ?>
        if ($("#revenda_cep").val() != "" && $("#revenda_cep").val() != undefined && $("#revenda_endereco").val() != "" && $("#revenda_endereco").val() != undefined){
            $('#revenda_cep').trigger('blur');
            setTimeout(function(){
                calcula_km_revenda();
            },1000);
        }
        $(".consumidor_revenda").change();
        <?php } ?>
    <?php } ?>
});

function carrega_tecnico(posto){
    $.ajax({
        url: '<?=$PHP_SELF?>',
        type: "post",
        dataType: "JSON",
        data: {"carrega_tecnico": true, "posto": posto}
    }).done(function(res){
        $.each(res.dados, function(idx, elemen){
            let option = $('<option>', {
                value: elemen.tecnico,
                text: elemen.nome
            });
            $("#tecnico").append(option);
        });
    });
}

function adiciona_nota(produto, referencia, descricao, serie, qtde){
    var nota_fiscal = $("#nota_fiscal").val();
    var data_nf = $("#data_nf").val();
    var data_abertura = $("#data_abertura").val();
    var nova_linha = $("#modelo_nota_fiscal").clone();
    var posicao = $("div.row-fluid[name^=produto_][name!=produto___modelo__]").length;

    if (data_abertura != "" && data_abertura != undefined && data_nf != "" && data_nf != undefined){
        [nf_dia, nf_mes, nf_ano] = data_nf.split("/");
        [ab_dia, ab_mes, ab_ano] = data_abertura.split("/");
        
        var valida_data_nf = new Date(nf_ano+"/"+nf_mes+"/"+nf_dia);
        var valida_data_ab = new Date(ab_ano+"/"+ab_mes+"/"+ab_dia);
        
        if (valida_data_nf > valida_data_ab){
            alert("Data nota fiscal não pode ser maior que a data de abertura");
            return false;
        }
    }

    if (nota_fiscal == "" || data_nf == "") {
        alert("Informe o número e a data da nota fiscal");
        return false;
    } else {
        $("#nota_fiscal").val("");
        $("#data_nf").val("");
    }

    if ($("#div_informacoes_produto").not(":visible")) {
        $("#div_informacoes_produto").show();
    }

    $("#div_produtos").append($(nova_linha).html().replace(/__nota__/g, nota_fiscal).replace(/__modelo__/g, posicao).replace(/disabled\=['"]disabled['"]/g, ""));
    $("#label_nota_fiscal_"+nota_fiscal).html(nota_fiscal);
    $("#label_data_nf_"+nota_fiscal).html(data_nf);
    $("input[name='produtos["+posicao+"][nota_fiscal]']").val(nota_fiscal);
    $("input[name='produtos["+posicao+"][data_nf]']").val(data_nf);
    $("div.row-fluid[name=produto_"+posicao+"]").find(".numeric").numeric();
    $("input[name='produtos["+posicao+"][serie]']").val(serie);
    $("input[name='produtos["+posicao+"][referencia]']").val(referencia);
    $("input[name='produtos["+posicao+"][descricao]']").val(descricao);
    $("input[name='produtos["+posicao+"][qtde]']").val(qtde);
    $("input[name='produtos["+posicao+"][id]']").val(produto);

    busca_defeito_constatado(produto, posicao);
    busca_defeito_reclamado(produto, posicao);
    $("div[name=produto_"+posicao+"]").find("button[name=remove_produto]").show();
}

function adiciona_linhas(produto, data_nf, nota_fiscal, referencia, descricao, serie, qtde){
    var nova_linha = $("#modelo_produto").clone();
    var posicao = $("div.row-fluid[name^=produto_][name!=produto___modelo__]").length;
    $("#produto_"+nota_fiscal).append($(nova_linha).html().replace(/__modelo__/g, posicao).replace(/disabled\=['"]disabled['"]/g, ""));
    $("input[name='produtos["+posicao+"][nota_fiscal]']").val(nota_fiscal);
    $("input[name='produtos["+posicao+"][data_nf]']").val(data_nf);
    $("div.row-fluid[name=produto_"+posicao+"]").find(".numeric").numeric();

    $("input[name='produtos["+posicao+"][serie]']").val(serie);
    $("input[name='produtos["+posicao+"][referencia]']").val(referencia);
    $("input[name='produtos["+posicao+"][descricao]']").val(descricao);
    $("input[name='produtos["+posicao+"][qtde]']").val(qtde);
    $("input[name='produtos["+posicao+"][id]']").val(produto);

    $("div[name=produto_"+posicao+"]").find("button[name=remove_produto]").show();

    busca_defeito_constatado(produto, posicao);
    busca_defeito_reclamado(produto, posicao);
}

/**
 * Função para limpar campos da revenda
*/

function limpaCampos(){
    $("#revenda_nome, #revenda_cnpj, #revenda_cep, #revenda_estado").val("");
    $("#revenda_cidade, #revenda_bairro, #revenda_endereco, #revenda_numero, #revenda_id").val("");
    $("#revenda_complemento, #revenda_fone, #revenda_email, #revenda_nome_consumidor, #revenda_cnpj_consumidor").val("");
}

/**
 * Função que busca os defeitos constatados da família do produto
 */
function busca_defeito_constatado(produto, posicao,grupo = '') {
    
    var fora_garantia = $("#tipo_atendimento option:selected").attr("fora_garantia");
    var grupo_atendimento = $("#tipo_atendimento option:selected").attr("grupo_atendimento");
    var tipo_atendimento_descricao = $("#tipo_atendimento option:selected").text();
    var tipo_atendimento = $("#tipo_atendimento option:selected").val()
    var defeitos_selecionados = $("#defeitos_constatados_multiplos").val();
    var tipo_atendimento = "";

    $.ajax({
        url: "cadastro_os_revenda.php",
        type: "POST",
        dataType:"JSON",
        data: {
            ajax: true,
            ajax_busca_defeito_constatado: true,
            produto: produto,
            fora_garantia: fora_garantia,
            defeitos_selecionados: defeitos_selecionados,
            grupo_atendimento: grupo_atendimento,
            tipo_atendimento_descricao: tipo_atendimento_descricao,
            grupo: grupo,
            tipo_atendimento: tipo_atendimento
        },
        beforeSend: function() {
            $("#defeitos_constatado"+posicao).hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
        }
    })
    .done(function(data) {
        if (data.defeitos_constatados) {
            $("#defeito_constatado"+posicao+" > option").first().nextAll().remove();
            defeito_constatado_json = [];
            var option = "<option value=''>Selecione</option>";
            $.each(data.defeitos_constatados, function(key, value) {
                var descricao = value.descricao;
                option += "<option value='"+value.defeito_constatado+"' data-lancar-pecas='"+value.lancar_pecas+"' data-lista-garantia='"+value.lista_garantia+"' data-defeito-grupo='"+value.defeito_constatado_grupo+"' >"+descricao+"</option>";
                // var option .= $("<option></option>");
                // $(option).val(value.defeito_constatado);
                // $(option).text(descricao);
                // $(option).data({ "lancar-pecas": value.lancar_pecas, "lista-garantia": value.lista_garantia, "defeito-grupo": value.defeito_constatado_grupo });
                //$("#defeito_constatado"+posicao).append(option);

                defeito_constatado_json.push({ defeito_constatado: value.defeito_constatado, descricao: descricao , lancar_pecas: value.lancar_pecas, lista_garantia: value.lista_garantia, defeito_constatado_grupo: value.defeito_constatado_grupo });
            });
            $("#defeito_constatado"+posicao).append(option);
        }
        $("#defeito_constatado"+posicao).show().next().remove();
    });
}

function busca_grupo_defeito_constatado(produto, posicao) {

    $.ajax({
        url: "cadastro_os_revenda.php",
        type: "POST",
        data: {
            ajax: true,
            ajax_busca_grupo_defeito_constatado: true,
            produto: produto
        }
    })
    .done(function(data) {
        $("#defeito_constatado_grupo"+posicao).html(data);
    });
}

function busca_defeito_reclamado(produto, posicao) {
    $.ajax({
        url: "cadastro_os_revenda.php",
        type: "POST",
        dataType:"JSON",
        data: {
            ajax: true,
            ajax_busca_defeito_reclamado: true,
            produto: produto
        },
        beforeSend: function() {
            $("#defeitos_constatado"+posicao).hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
        }
    })
    .done(function(data) {
        if (data.defeitos_reclamados) {
            $("#defeito_reclamado"+posicao+" > option").first().nextAll().remove();
            defeito_reclamado_json = [];
            var option = "<option value=''>Selecione</option>";
            $.each(data.defeitos_reclamados, function(key, value) {
                var descricao = value.descricao;
                option += "<option value='"+value.defeito_reclamado+"'>"+descricao+"</option>";
                defeito_reclamado_json.push({ defeito_reclamado: value.defeito_reclamado, descricao: descricao , lancar_pecas: value.lancar_pecas, lista_garantia: value.lista_garantia, defeito_constatado_grupo: value.defeito_constatado_grupo });
            });
            $("#defeito_reclamado"+posicao).append(option);
        }
        $("#defeito_reclamado"+posicao).show().next().remove();
    });
}

/**
 * Função para retirar a acentuação
 */
function retiraAcentos(palavra){
    if (!palavra) {
        return "";
    }

    var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
    var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    for(i = 0; i < palavra.length; i++) {
        if (com_acento.search(palavra.substr(i, 1)) >= 0) {
            newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
        } else {
            newPalavra += palavra.substr(i, 1);
        }
    }

    return newPalavra.toUpperCase();
}

/**
 * Função de valida garantia número de série ibramed
 */
function valida_nserie (produto,serie,data_abertura,posicao,nota_fiscal, data_nf){
    if (produto != '' && produto != undefined && serie != '' && serie != undefined && data_abertura != '' && data_abertura != undefined){
        $.ajax({
            url: window.location.href,
            type: "post",
            data: {
                ajax: "sim",
                acao: "valida_data_venda",
                produto: produto,
                serie: serie,
                data_abertura: data_abertura,
                nota_fiscal: nota_fiscal,
                data_nf: data_nf
            }
        }).fail(function(){
        }).done(function(data){
            data = JSON.parse(data);
            if (data.ok != "" && data.ok != undefined && data.ok == "fora_garantia"){
                alert("Produto fora de garantia");
                $("input[name='produtos["+posicao+"][serie]']").val('');
                $("input[name='produtos["+posicao+"][id]']").val('');
                $("input[name='produtos["+posicao+"][referencia]']").val('');
                $("input[name='produtos["+posicao+"][descricao]']").val('');
                $("input[name='produtos["+posicao+"][qtde]']").val('');
            }else if (data.ok != "" && data.ok != undefined && data.ok == "erro_serie"){
                alert("Número de série não cadastrado para o produto");
                $("input[name='produtos["+posicao+"][serie]']").val('');
            }
        });
    }
}

/**
 * Monta combo de marcas 
 */
function combo_marcas (marcas, posicao){
    if (marcas != '' && marcas != undefined){
        $.ajax({
            url: window.location.href,
            type: "post",
            data: {
                ajax: "sim",
                acao: "marcas_produto",
                marcas: marcas
            }
        }).fail(function(){
        }).done(function(data){
            data = JSON.parse(data);
            
            if (data.ok == "success"){
                //if ($(data.result).length > 1){
                    var option = "<option value=''>Selecione a Marca</option>";
            //}
            let selecionado = "selected";

                $.each(data.result, function (key, value) {
            option += "<option value='"+value.marca+"' "+selecionado+">"+value.nome+"</option>";
            selecionado = "";
                });
            }else{
                var option = "<option value=''>Selecione a Marca</option>";
            }

            $('#marca'+posicao).html(option);
            
        });
    }
}

/**
 * Função de retorno da lupa de consumidor
 */
function retorna_consumidor_os(retorno){
    $("#revenda_nome").val(retorno.nome);
    $("#revenda_cnpj").val(retorno.cpf);

    if (retorno.cep.length > 0) {
        $("#revenda_cep").val(retorno.cep);

        busca_cep(retorno.cep);

        if ($("#revenda_bairro").val().length == 0) {
            $("#revenda_bairro").val(retorno.bairro);
        }

        if ($("#revenda_endereco").val().length == 0) {
            $("#revenda_endereco").val(retorno.endereco);
        }
    } else {
        $("#revenda_estado").val(retorno.estado);

        busca_cidade(retorno.estado);

        $("#revenda_cidade").val(retiraAcentos(retorno.cidade).toUpperCase());
        $("#revenda_bairro").val(retorno.bairro);
        $("#revenda_endereco").val(retorno.endereco);
    }
    
    $("#revenda_numero").val(retorno.numero);
    $("#revenda_complemento").val(retorno.complemento);
    $("#revenda_email").val(retorno.email);
    $("#revenda_fone").val(retorno.fone);
}

<?php if(in_array($login_fabrica, array(183)) AND $login_tipo_posto_codigo == "Rep"){ ?>
    function retorna_posto(retorno) {
        $("#revenda_id").val(retorno.posto);
        $("#posto_id").val(retorno.posto);
        $("#revenda_nome").val(retorno.nome);
        $("#revenda_cnpj").val(retorno.cnpj);
        
        if (retorno.cep.length > 0) {
            $("#revenda_cep").val(retorno.cep);

            busca_cep(retorno.cep);
        } else {
            $("#revenda_estado").val(retorno.estado);

            busca_cidade(retorno.estado);

            $("#revenda_cidade").val(retiraAcentos(retorno.cidade_nome).toUpperCase());
            $("#revenda_bairro").val(retorno.bairro);
            $("#revenda_endereco").val(retorno.endereco);
        }

        $("#revenda_numero").val(retorno.contato_numero);
        $("#revenda_complemento").val(retorno.contato_complemento);
        $("#revenda_fone").val(retorno.contato_fone_comercial);
        $("#revenda_email").val(retorno.contato_email);
    }
<?php } ?>
/**
 * Função de retorno da lupa de revenda
 */
function retorna_revenda(retorno) {

    <?php if ($login_fabrica == 178){ ?>
        if ($(".consumidor_revenda").val() == "C" || $(".consumidor_revenda").val() == "S"){
            $("#revenda_id").val(retorno.revenda_fabrica);
            $("#revenda_nome_consumidor").val(retorno.razao);
            $("#revenda_cnpj_consumidor").val(retorno.cnpj);
        }else{
            $("#revenda_id").val(retorno.revenda_fabrica);
            $("#revenda_nome").val(retorno.razao);
            $("#revenda_cnpj").val(retorno.cnpj);
            
            if (retorno.cep.length > 0) {
                $("#revenda_cep").val(retorno.cep);

                busca_cep(retorno.cep);

                if ($("#revenda_bairro").val().length == 0) {
                    $("#revenda_bairro").val(retorno.bairro);
                }

                if ($("#revenda_endereco").val().length == 0) {
                    $("#revenda_endereco").val(retorno.endereco);
                }
            } else {
                $("#revenda_estado").val(retorno.estado);

                busca_cidade(retorno.estado);

                $("#revenda_cidade").val(retiraAcentos(retorno.cidade_nome).toUpperCase());
                $("#revenda_bairro").val(retorno.bairro);
                $("#revenda_endereco").val(retorno.endereco);
            }

            $("#revenda_numero").val(retorno.numero);
            $("#revenda_complemento").val(retorno.complemento);
            $("#revenda_telefone").val(retorno.fone);
        }
    <?php }else{ ?>
        $("#revenda_id").val(retorno.revenda_fabrica);
        $("#revenda_nome").val(retorno.razao);
        $("#revenda_cnpj").val(retorno.cnpj);
        
        if (retorno.cep.length > 0) {
            $("#revenda_cep").val(retorno.cep);

            busca_cep(retorno.cep);

            if ($("#revenda_bairro").val().length == 0) {
                $("#revenda_bairro").val(retorno.bairro);
            }

            if ($("#revenda_endereco").val().length == 0) {
                $("#revenda_endereco").val(retorno.endereco);
            }
        } else {
            $("#revenda_estado").val(retorno.estado);

            busca_cidade(retorno.estado);

            $("#revenda_cidade").val(retiraAcentos(retorno.cidade_nome).toUpperCase());
            $("#revenda_bairro").val(retorno.bairro);
            $("#revenda_endereco").val(retorno.endereco);
        }

        $("#revenda_numero").val(retorno.numero);
        $("#revenda_complemento").val(retorno.complemento);
        $("#revenda_telefone").val(retorno.fone);
    <?php } ?>
}

/**
 * Função de retorno da lupa de produtos
 */

function retorna_produto(retorno) {
    $("input[name='produtos["+retorno.posicao+"][id]']").val(retorno.produto);
    if (typeof retorno.serie_produto != "undefined") {
        $("input[name='produtos["+retorno.posicao+"][serie]']").attr({ readonly: "readonly" });
        $("input[name='produtos["+retorno.posicao+"][qtde]']").val(1).attr({ readonly: "readonly" });
    }
    $("input[name='produtos["+retorno.posicao+"][referencia]']").val(retorno.referencia).attr({ readonly: "readonly" });
    $("input[name='produtos["+retorno.posicao+"][descricao]']").val(retorno.descricao).attr({ readonly: "readonly" });
    $("div[name=produto_"+retorno.posicao+"]").find("span[rel=lupa_produto]").hide();
    $("div[name=produto_"+retorno.posicao+"]").find("button[name=remove_produto]").show();

    <?php if ($login_fabrica == 175){ ?>
        var data_abertura = $("#data_abertura").val();
        var nota_fiscal = $("input[name='produtos["+retorno.posicao+"][nota_fiscal]']").val();
        var data_nf = $("input[name='produtos["+retorno.posicao+"][data_nf]']").val();
        valida_nserie (retorno.produto,retorno.serie_produto,data_abertura,retorno.posicao, nota_fiscal, data_nf);
    <?php } ?>

    <?php if ($login_fabrica == 178){ ?>
        combo_marcas(retorno.marcas_produto, retorno.posicao);
        //busca_defeito_constatado(retorno.produto, retorno.posicao);
        busca_grupo_defeito_constatado(retorno.produto, retorno.posicao);
        busca_defeito_reclamado(retorno.produto, retorno.posicao);
        if (retorno.fora_linha == true){
            $("input[name='produtos["+retorno.posicao+"][produto_fora_linha]']").val("true");
            $("div[name='produto_"+retorno.posicao+"'] > .div_lista_basica").after("<div class='span12 box_fora_linha' id='box_fora_linha_"+retorno.posicao+"'>Produto fora de linha, verificar na impressão da OS possíveis opções para troca</div>");
        }
    <?php } ?>

    <?php if ($login_fabrica == 183){ ?>
        busca_defeito_constatado(retorno.produto, retorno.posicao);
        busca_defeito_reclamado(retorno.produto, retorno.posicao);
    <?php } ?>


    <?php if ($login_fabrica == 177){ ?>
        if (retorno.lote == 't'){
            $("#hidden_lote"+retorno.posicao).val(retorno.lote);
            $(".h5_lote"+retorno.posicao).show();
        }else{
            $("#hidden_lote"+retorno.posicao).val('');
            $(".h5_lote"+retorno.posicao).hide();
        }
    <?php } ?>
}

/**
 * Função que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, cidade) {
    $("#revenda_cidade").find("option").first().nextAll().remove();

    if (estado.length > 0) {
        $.ajax({
            async: false,
            url: "cadastro_os_revenda.php",
            type: "POST",
            data: { ajax_busca_cidade: true, estado: estado },
            beforeSend: function() {
                if ($("#revenda_cidade").next("img").length == 0) {
                    $("#revenda_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                }
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $.each(data.cidades, function(key, value) {
                        var option = $("<option></option>", { value: value, text: value });
                        $("#revenda_cidade").append(option);
                    });
                }

                $("#revenda_cidade").show().next().remove();
            }
        });
    }

    if(typeof cidade != "undefined" && cidade.length > 0){
        $("#revenda_cidade option[value='"+cidade+"']").attr('selected','selected');
    }

}

/**
 * Função de retorno da lista básica
 */
function retorna_pecas(retorno, posicao) {
    if (retorno.length > 0) {
        var erro_qtde = [];
        var dados_pecas = [];
        var qtde_pecas_retorno = 0;
        $.each(retorno, function(key, peca) {
            dados_pecas.push({id_peca:peca.peca, servico_realizado:peca.servico_realizado, qtde_lancada:peca.qtde_lancada, defeito_peca:peca.defeito_peca});
            qtde_pecas_retorno+= parseInt(peca.qtde_lancada);
        });

        var info_pecas = JSON.stringify(dados_pecas);
        
        $("input[name='produtos["+posicao+"][info_pecas]']").val(info_pecas);

        $(".informacoes_pecas"+posicao).find("p").remove();
        if (qtde_pecas_retorno > 1){
            $(".informacoes_pecas"+posicao).append("<p style='margin: 0px !important; font-size: 12px;'>"+qtde_pecas_retorno+" peças lançadas </p>");
        }else{
            $(".informacoes_pecas"+posicao).append("<p style='margin: 0px !important; font-size: 12px;'>"+qtde_pecas_retorno+" peça lançada </p>");
        }
    }else{
        $("input[name='produtos["+posicao+"][info_pecas]']").val("");
        $(".informacoes_pecas"+posicao).find("p").remove();
    }
}

/**
 * Função que faz um ajax para buscar o cep nos correios
 */
function busca_cep(cep, method) {
    if (cep.length > 0) {
        var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

        if (typeof method == "undefined" || method.length == 0) {
            method = "webservice";
            $.ajaxSetup({
                timeout: 3000
            });
        } else {
            $.ajaxSetup({
                timeout: 5000
            });
        }

        $.ajax({
            async: true,
            url: "ajax_cep.php",
            type: "GET",
            data: { cep: cep, method: method },
            beforeSend: function() {
                $("#revenda_estado").next("img").remove();
                $("#revenda_cidade").next("img").remove();
                $("#revenda_bairro").next("img").remove();
                $("#revenda_endereco").next("img").remove();

                $("#revenda_estado").hide().after(img.clone());
                $("#revenda_cidade").hide().after(img.clone());
                $("#revenda_bairro").hide().after(img.clone());
                $("#revenda_endereco").hide().after(img.clone());
            },
            error: function(xhr, status, error) {
                busca_cep(cep, "database");
            },
            success: function(data) {
                results = data.split(";");

                if (results[0] != "ok") {
                    alert(results[0]);
                    $("#revenda_cidade").show().next().remove();
                } else {
                    $("#revenda_estado").val(results[4]);

                    busca_cidade(results[4]);
                    results[3] = results[3].replace(/[()]/g, '');

                    $("#revenda_cidade").val(retiraAcentos(results[3]).toUpperCase());

                    if (results[2].length > 0) {
                        $("#revenda_bairro").val(results[2]);
                    }

                    if (results[1].length > 0) {
                        $("#revenda_endereco").val(results[1]);
                    }
                }

                $("#revenda_estado").show().next().remove();
                $("#revenda_bairro").show().next().remove();
                $("#revenda_endereco").show().next().remove();

                if ($("#revenda_bairro").val().length == 0) {
                    $("#revenda_bairro").focus();
                } else if ($("#revenda_endereco").val().length == 0) {
                    $("#revenda_endereco").focus();
                } else if ($("#revenda_numero").val().length == 0) {
                    $("#revenda_numero").focus();
                }

                $.ajaxSetup({
                    timeout: 0
                });

                <?php if (in_array($login_fabrica, [178])) { ?> 
                    $("#calcular_km").click();
                <?php } ?>
            }
        });
    }
}

function calcula_km_revenda (km_rota) {

    if (typeof km_rota == "undefined") {
        var km_rota = false;
    }

    try {

        if ($("#posto_id").val().length == 0) {
            throw new Error("Selecione um Posto Autorizado");
        }

        if ($("#posto_latitude").val().length == 0 && $("#posto_longitude").val().length == 0) {
            throw new Error("Posto Autorizado sem latitude e longitude");
        }

        if ($("#revenda_endereco").val() == "" && $("#revenda_cidade").val() == "" && $("#revenda_estado").val() == "") {
            throw new Error("Digite as informações da Revenda para calcular o KM");
        }

        var Pais = "Brasil";

        Geocoder.setEndereco({
            endereco: $("#revenda_endereco").val(),
            numero: $("#revenda_numero").val(),
            bairro: $("#revenda_bairro").val(),
            cidade: $("#revenda_cidade > option:selected").text(),
            estado: $("#revenda_estado").val(),
            cep: $("#revenda_cep").val(),
            pais: Pais
        });

        request = Geocoder.getLatLon();

        request.then(
            function(resposta) {

                pLat = $("#posto_latitude").val();
                pLng = $("#posto_longitude").val();
                var pLatLng = $("#posto_latitude").val()+","+$("#posto_longitude").val();

                cLat  = resposta.latitude;
                cLng  = resposta.longitude;
                var cLatLng = cLat+","+cLng;

                $.ajax({
                    url: "controllers/TcMaps.php",
                    type: "POST",
                    data: {ajax: "route", origem: pLatLng, destino: cLatLng, ida_volta: "sim"},
                    timeout: 60000
                }).done(function(data){
                    data = JSON.parse(data);

                    geometry = data.rota.routes[0].geometry;
                    var kmtotal = parseFloat(data.total_km).toFixed(2);

                    if ($("#ver-mapa").is(":checked")) {
                        mostraMapa();
                    }

                    if (km_rota == false) {
                        $('#qtde_km').val(kmtotal);
                        var qtde_km_hidden = $("#qtde_km_hidden").val();

                        if (typeof qtde_km_hidden == "undefined" || qtde_km_hidden == null || qtde_km_hidden.length == 0) {
                            $("#qtde_km_hidden").val(kmtotal);
                        }
                    }
                    $('#loading-map').hide();
                }).fail(function(){
                    $('#loading-map').hide();
                    alert('Erro ao tentar calcular a rota!');
                });
            },
            function(erro) {
                $('#loading-map').hide();
                alert(erro);
            }
        );
    } catch(e) {
        $('#loading-map').hide();
        alert(e.message);
    }

}

function mostraMapa () {
    if (cLat.length == 0 || cLng.length == 0 || pLat.length == 0 || pLng.length == 0 || geometry.length ==0) {
        throw new Error("Execute o calculo do KM para mostrar o mapa");
    }

    Map.load();

    /* Marcar pontos no mapa */
    Markers.remove();
    Markers.clear();
    Markers.add(cLat, cLng, "blue", "Cliente");
    Markers.add(pLat, pLng, "red", "Posto");
    Markers.render();
    Markers.focus();

    Router.remove();
    Router.clear();
    Router.add(Polyline.decode(geometry));
    Router.render();

}

<? if ($areaAdmin === true) { ?>

    /**
     * Função de retorno da lupa do posto
     */
    function retorna_posto(retorno) {
        /**
         * A função define os campos código e nome como readonly e esconde o botão
         * O posto somente pode ser alterado quando clicar no botão trocar_posto
         * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
         */
        $("#posto_id").val(retorno.posto);
        $("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" });
        $("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" });
        $("#div_trocar_posto").show();
        $("#div_informacoes_posto").find("span[rel=lupa]").hide();

        $("#posto_latitude").val(retorno.latitude);
        $("#posto_longitude").val(retorno.longitude);
        $("input[name=lupa_config][tipo=produto]").attr({ posto: retorno.posto });

        <?php if ($login_fabrica == 178 AND $areaAdmin == true){ ?>
            carrega_tecnico(retorno.posto);
        <?php } ?>
    }
<? } ?>
</script>

<? if ($fabrica_qtde_anexos > 0) {
    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) { ?>
        <form name="form_anexo" method="post" action="cadastro_os.php" enctype="multipart/form-data" style="display: none;" >
            <input type="file" name="anexo_upload_<?=$i?>" value="" />

            <input type="hidden" name="ajax_anexo_upload" value="t" />
            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
        </form>
    <? }
}

include "rodape.php"; ?>
