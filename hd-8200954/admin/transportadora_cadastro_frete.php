<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

unset($msg_erro);
$msg_debug = "";

$array_estados = $array_estados();

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim' && isset($_POST['cnpj'])) {
    $cnpj = $_POST['cnpj'];
    $cnpj = str_replace(".", "", $cnpj);
    $cnpj = str_replace("/", "", $cnpj);
    $cnpj = str_replace("-", "", $cnpj);

    $valida = verificaCpfCnpj($cnpj);

    if (!empty($valida)) {
        exit(json_encode(array('ok' => utf8_encode($valida))));
    }

    $sql = " 
        SELECT  
            tbl_transportadora.cnpj
        FROM tbl_transportadora 
        JOIN tbl_transportadora_fabrica ON tbl_transportadora.transportadora = tbl_transportadora_fabrica.transportadora
        WHERE cnpj = '{$cnpj}'
        AND fabrica = {$_POST['fabrica']};
    ";

    $res = pg_query($con,$sql);
    
    if(pg_num_rows($res) == 0) {
        exit(json_encode(array('nenhum' => utf8_encode('Sem resultado!'))));
    }

    exit(json_encode(array('ok' => utf8_encode('Este CNPJ j· existe!!'))));
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
        $retorno = array("error" => utf8_encode("estado n„o encontrado"));
    }

    exit(json_encode($retorno));
}

if (!empty($_POST["acao"]) and $_POST["acao"] == "alterar_faixa") {

    header("Content-Type: application/json");

    if (empty($_POST["transportadora"]) or empty($_POST["transportadora_valor"])) {
        header("HTTP/1.1 400 400 Bad Request");
        die('{"erro": "Par‚metro requerido faltando"}');
    }

    $transportadora = $_POST["transportadora"];
    $transportadora_valor = $_POST["transportadora_valor"];
    $kg_inicial = '';
    $kg_final = '';
    $valor_kg = '';
    $valor_acima_kg_final = '';
    $seguro = '';
    $gris = '';

    $update = array();

    if (!empty($_POST["kg_inicial"])) {
        $kg_inicial = str_replace(".", "", $_POST["kg_inicial"]);
        $kg_inicial = str_replace(",", ".", $kg_inicial);
        $update[] = "kg_inicial = $kg_inicial";
    }

    if (!empty($_POST["kg_final"])) {
        $kg_final = str_replace(".", "", $_POST["kg_final"]);
        $kg_final = str_replace(",", ".", $kg_final);
        $update[] = "kg_final = $kg_final";
    }

    if (!empty($_POST["valor_kg"])) {
        $valor_kg = str_replace(".", "", $_POST["valor_kg"]);
        $valor_kg = str_replace(",", ".", $valor_kg);
        $update[] = "valor_kg = $valor_kg";
    }

    if (!empty($_POST["valor_acima_kg_final"])) {
        $valor_acima_kg_final = str_replace(".", "", $_POST["valor_acima_kg_final"]);
        $valor_acima_kg_final = str_replace(",", ".", $valor_acima_kg_final);
        $update[] = "valor_acima_kg_final = $valor_acima_kg_final";
    }

    if (!empty($_POST["seguro"])) {
        $seguro = str_replace(".", "", $_POST["seguro"]);
        $seguro = str_replace(",", ".", $seguro);
        $update[] = "seguro = $seguro";
    }

    if (!empty($_POST["gris"])) {
        $gris = str_replace(".", "", $_POST["gris"]);
        $gris = str_replace(",", ".", $gris);
        $update[] = "gris = $gris";
    }

    if (!empty($update)) {
        $sql = 'UPDATE tbl_transportadora_valor SET ';
        $sql .= implode(', ', $update);
        $sql .= ' WHERE transportadora_valor = ' . $transportadora_valor;
        $sql .= ' AND fabrica = ' . $login_fabrica;
        $sql .= ' AND transportadora = ' . $transportadora;

        $qry = pg_query($con, $sql);

        if (pg_last_error()) {
            header("HTTP/1.1 400 400 Bad Request");
            die('{"erro": "Erro ao atualizar registro"}');
        }
    }

    die('{"result": "Registro atualizado com sucesso."}');
}


if (strlen($_POST['btn_acao']) > 0) $btn_acao = strtolower($_POST['btn_acao']);

#-------------------- Descredenciar -----------------
if ($_POST["descredenciar"]) {
    $transportadora_valor = $_POST['valor'];
    $resS = pg_query($con,"BEGIN TRANSACTION");
    $sql = "DELETE FROM tbl_transportadora_valor
            WHERE   transportadora_valor = $transportadora_valor
    ";
    $res = pg_exec ($con,$sql);
    if (strlen (pg_errormessage ($con)) > 0) $msg_erro["msg"][] = pg_errormessage($con);
    if (count($msg_erro["msg"]) == 0) {
        $resS = pg_query($con,"COMMIT TRANSACTION");
        echo "ok";
    }else{
        $resS = pg_query($con,"ROLLBACK TRANSACTION");
        $erro = implode("<br />", $msg_erro["msg"]);
        echo $erro;
    }
    exit;
}

#---------------- Cadastra faixas de peso para Frete --------------
if ($btn_acao == "gravar_faixas"){
    $capital_interior   = trim($_POST['capital_interior']);
    $estado             = trim($_POST['estado']);
    $kg_inicial         = trim($_POST['kg_inicial']);
    $kg_final           = trim($_POST['kg_final']);
    $valor_kg           = trim($_POST['valor_kg']);
    $valor_kg_excedente = trim($_POST['valor_kg_excedente']);
    $seguro             = trim($_POST['seguro']);
    $gris               = trim($_POST['gris']);

    if($login_fabrica == 94){

        $kg_inicial = 0.001;
        $kg_final   = 0.001;
        $valor_kg_excedente   = 0.00;
    }

    // dados para transportadora padrao
    if (strlen($estado) == 0){
        $msg_erro['msg'][] = "Preencha o estado da Transportadora";
    }else{
        $xestado = "'".strtoupper($estado)."'";
    }

    if (strlen($capital_interior) == 0){
        $msg_erro['msg'][] = "Selecione a regi„o da transportadora";
    }else{
        $xcapital_interior = "'".$capital_interior."'";
    }

    if (strlen($kg_inicial) == 0){
        $msg_erro['msg'][] = "Selecione o Kg inicial da faixa de frete";
    }else{
        $xkg_inicial = str_replace(".","",$kg_inicial);
        $xkg_inicial = str_replace(",",".",$kg_inicial);
    }
    if (strlen($kg_final) == 0){
        $msg_erro['msg'][] = "Selecione o Kg final da faixa de frete";
    }else{
        $xkg_final = str_replace(".","",$kg_final);
        $xkg_final = str_replace(",",".",$kg_final);
    }
    if (strlen($valor_kg) == 0){
        if($login_fabrica == 94){
            $msg_erro['msg'][] = "Informe o valor do Frete";
        }else{
            $msg_erro['msg'][] = "Selecione o valor do kg da faixa de frete";
        }
    }else{
        $xvalor_kg = str_replace(".","",$valor_kg);
        $xvalor_kg = str_replace(",",".",$valor_kg);
    }
    if (strlen($valor_kg_excedente) == 0){
        $msg_erro['msg'][] = "Selecione o valor do kg excedente da faixa de frete";
    }else{
        $xvalor_kg_excedente = str_replace(".","",$valor_kg_excedente);
        $xvalor_kg_excedente = str_replace(",",".",$valor_kg_excedente);
    }

    if (strlen($seguro) == 0){
        if($login_fabrica == 120 or $login_fabrica == 201){
            $msg_erro['msg'][] = "Selecione a porcentagem do seguro da faixa de frete";
        }else{
            $xseguro = 0.00;
        }
    }else{
        $xseguro  = str_replace(".","",$seguro );
        $xseguro  = str_replace(",",".",$seguro );
    }

    if (strlen($gris) == 0){
        if($login_fabrica == 120 or $login_fabrica == 201){
            $msg_erro['msg'][] = "Selecione a porcentagem do GRIS da faixa de frete";
        }else{
            $xgris = 0.00;
        }
    }else{
        $xgris  = str_replace(".","",$gris);
        $xgris  = str_replace(",",".",$gris);
    }


    if (strlen($xcapital_interior) > 0 AND strlen($transportadora) > 0) {
        $res = @pg_exec ($con,"BEGIN TRANSACTION");

        // seleciona para ver se est· cadastrado em tbl_transportadora_padrao
        $sql = "
            SELECT  tbl_transportadora_padrao.transportadora_padrao
            FROM    tbl_transportadora_padrao
            WHERE   transportadora      = $transportadora
            AND     fabrica             = $login_fabrica
            AND     capital_interior    = $xcapital_interior
            AND     estado              = $xestado
        ";

        $res = @pg_exec ($con,$sql);
        if (strlen (pg_errormessage ($con)) > 0) $msg_erro["msg"][] = pg_errormessage($con);
    }

    if (pg_numrows($res) == 0){
        $sql = "
            INSERT INTO tbl_transportadora_padrao   (
                transportadora,
                fabrica,
                capital_interior,
                estado
            ) VALUES (
                $transportadora,
                $login_fabrica,
                $xcapital_interior,
                $xestado
            )RETURNING transportadora_padrao;
        ";
        $res = pg_exec ($con,$sql);
        if (strlen (pg_errormessage ($con)) > 0) $msg_erro["msg"][] = pg_errormessage($con);
    }

    $transportadora_padrao = pg_fetch_result($res,0,0);

    if(count($msg_erro["msg"]) == 0) {
        if(strlen($xkg_inicial) > 0 && (float)$xkg_inicial > 0){
            /**
            * Antes da gravaÁ„o, ser· feita a validaÁ„o dos Kgs
            * para que n„o haja gravaÁ„o entre faixas existentes
            */
            $sqlValidaKg = "SELECT  generate_series((kg_inicial * 1000)::integer,(kg_final * 1000)::integer) AS intervalo
                            FROM    tbl_transportadora_valor
                            WHERE   transportadora_padrao   = $transportadora_padrao
                            AND     fabrica                 = $login_fabrica
            ";
            $resValidaKg = pg_query($con,$sqlValidaKg);
            $temKg = pg_num_rows($resValidaKg);

            $update = false;

            if($temKg > 0){

                if($login_fabrica != 94){                
                    for($i = 0; $i < $temKg; $i++){
                        $intervalo[] = pg_fetch_result($resValidaKg,$i,intervalo) * 0.001;
                    }

                    $comp_intervalo = range((float)$xkg_inicial,(float)$xkg_final,0.001);
                    $verificaKg = array_intersect($intervalo,$comp_intervalo);

                    if(count($verificaKg) > 0){
                        $msg_erro['msg'][] = "J· existe um intervalo cadastrado entre os Kg's";
                    }
                }else{
                    $update = true;
                }
            }

            if($update){
                $sql = "UPDATE tbl_transportadora_valor SET valor_kg = $xvalor_kg WHERE transportadora = $transportadora AND fabrica = $login_fabrica";
            }else{
                $sql = "INSERT INTO tbl_transportadora_valor (
                        fabrica,
                        transportadora,
                        kg_inicial,
                        kg_final,
                        valor_kg,
                        valor_acima_kg_final,
                        transportadora_padrao,
                        seguro,
                        gris
                    ) VALUES (
                        $login_fabrica,
                        $transportadora,
                        $xkg_inicial,
                        $xkg_final,
                        $xvalor_kg,
                        $xvalor_kg_excedente,
                        $transportadora_padrao,
                        $xseguro,
                        $xgris
                    );
                ";
            }
            $res = pg_query($con,$sql);
            if (strlen (pg_errormessage ($con)) > 0) $msg_erro["msg"][] = pg_errormessage($con);
        }else{
            $msg_erro['msg'][] = "Valor do Kg inicial inv·lido";
        }
    }

    if(count($msg_erro["msg"]) > 0){
        "ROLLBACK";
        $res = @pg_exec ($con,"ROLLBACK TRANSACTION");
    }else{
        "COMMIT";
        $res = @pg_exec ($con,"COMMIT TRANSACTION");
        $msg = "Gravado com Sucesso!";
        $_REQUEST['transportadora_padrao'] = $transportadora_padrao;
    }
}

#---------------- Cadastra / Altera Transportadora --------------
if ($btn_acao == "gravar"){

    $nome               = trim($_POST['nome']);
    $fantasia           = trim($_POST['fantasia']);
    $codigo_interno     = trim($_POST['codigo_interno']);
    $ativo              = trim($_POST['ativo']);
    $cnpj               = trim($_POST['cnpj']);
    $xcnpj              = str_replace ("-","",$cnpj);
    $xcnpj              = str_replace (".","",$xcnpj);
    $xcnpj              = str_replace ("/","",$xcnpj);
    $xcnpj              = str_replace (" ","",$xcnpj);

    $sqlSe = "SELECT transportadora FROM tbl_transportadora WHERE cnpj = '{$xcnpj}';";
    $resSe = pg_query($con,$sqlSe);

    if(pg_num_rows($resSe) > 0){
        $transportadora = pg_fetch_result($resSe,0,transportadora);
    }
    
    if (strlen($nome) == 0)
        $msg_erro['msg'][] = "Preencha o campo Nome da transportadora.";
    else
        $xnome = "'".$nome."'";

    //HD-6876642
    // if (strlen($xcnpj) == 0){
    //     $msg_erro['msg'][] = "Preencha o campo CNPJ da transportadora.";
    // }else{
    //     $xcnpj = "'".$xcnpj."'";
    // }

    if (in_array($login_fabrica, array(169,170,177))) {
        $email          = trim($_POST['email']);
        $ie             = trim($_POST['ie']);
        $cep            = trim($_POST['cep']);
        $cep            = preg_replace("/[\-]/", "", $cep);
        $estado         = trim($_POST['estado_transportadora']);
        $cidade         = trim($_POST['cidade']);
        $bairro         = trim($_POST['bairro']);
        $endereco       = trim($_POST['endereco']);
        $numero         = trim($_POST['numero']);
        $fone           = trim($_POST['fone']);

        if (strlen($email) == 0) {
            $msg_erro['msg'][] = "Preencha o campo EMAIL da transportadora.";
        } else {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg_erro['msg'][] = "Preencha email EMAIL da transportadora v·lido.";
            } else {
                $xemail = "'".$email."'";
            }
        }

        if (empty($ie)) {
            $msg_erro['msg'][] = "Preencha o campo INSCRI«√O ESTADUAL da transportadora.";
        } else {
            $xie = "'{$ie}'";
        }

        if (empty($cep)) {
            $msg_erro['msg'][] = "Preencha o campo CEP da transportadora.";
        }else {
            $xcep = "'{$cep}'";
        }

        if (empty($estado)) {
            $msg_erro['msg'][] = "Preencha o campo ESTADO da transportadora.";
        } else {
            $xestado = "'{$estado}'";
        }

        if (empty($cidade)) {
            $msg_erro['msg'][] = "Preencha o campo CIDADE da transportadora.";
        } else {
            $xcidade = "'{$cidade}'";
        }

        if (empty($endereco)) {
            $msg_erro['msg'][] = "Preencha o campo ENDERE«O da transportadora.";
        } else {
            $xendereco = "'{$endereco}'";
        }

        if (empty($bairro)) {
            $msg_erro['msg'][] = "Preencha o campo BAIRRO da transportadora.";
        } else {
            $xbairro = "'{$bairro}'";
        }

        if (empty($fone)) {
            $msg_erro['msg'][] = "Preencha o campo TELEFONE da transportadora.";
        } else {
            $xfone = "'{$fone}'";
        }

        $updTransp = ", ie = {$xie}";

        $insTranspC = ", ie";
        $insTranspV = ", {$xie}";

        $updTranspFab = ",
            contato_email    = {$xemail},
            contato_endereco = {$xendereco},
            contato_cidade   = {$xcidade},
            contato_estado   = {$xestado},
            contato_bairro   = {$xbairro},
            contato_cep      = {$xcep},
            fone             = {$xfone}
        ";

        $insTranspFabC = ",
            contato_email,
            contato_endereco,
            contato_cidade,
            contato_estado,
            contato_bairro,
            contato_cep,
            fone
        ";

        $insTranspFabV = ",
            {$xemail},
            {$xendereco},
            {$xcidade},
            {$xestado},
            {$xbairro},
            {$xcep},
            {$xfone}
        ";

    }

    if (strlen($fantasia) == 0)
        $xfantasia = "''";
    else
        $xfantasia = "'".$fantasia."'";

    if (strlen($codigo_interno) == 0)
        $msg_erro['msg'][] = "Preencha o cÛdigo interno da transportadora.";
    else
        $xcodigo_interno = "'".$codigo_interno."'";

    if (strlen($ativo) == 0)
        $msg_erro['msg'][] = "Selecione se a transportadora est· ativa ou n„o.";
    else
        $xativo = "'".$ativo."'";

    if (count($msg_erro["msg"]) == 0){

        $res = pg_query($con,"BEGIN TRANSACTION");

        if (strlen($transportadora) > 0){
            // ######################## ALTERA ########################
            $sql = "
                UPDATE tbl_transportadora SET 
                    nome = {$xnome}, 
                    fantasia = {$xfantasia}
                    {$updTransp}
                WHERE transportadora  = {$transportadora};
            ";
            $res = pg_query($con,$sql);

            if (strlen(pg_last_error()) > 0) {
                $msg_erro['msg'][] = "Ocorreu um erro atualizando dados da Transportadora #001";
            }

            $sql = "SELECT transportadora FROM tbl_transportadora_fabrica WHERE transportadora = {$transportadora} AND fabrica = {$login_fabrica};";
            $res = pg_query($con,$sql);

            if (strlen(pg_last_error()) > 0) {
                $msg_erro['msg'][] = "Ocorreu um erro atualizando dados da Transportadora #002";
            }
            
            if (count($msg_erro["msg"]) == 0){
                if (pg_num_rows($res) > 0){
                    $sql = "
                        UPDATE tbl_transportadora_fabrica SET
                            codigo_interno   = {$xcodigo_interno},
                            ativo            = {$xativo}
                            {$updTranspFab}
                        WHERE transportadora = {$transportadora}
                        AND fabrica = {$login_fabrica};
                    ";
                    
                    $res = pg_query($con,$sql);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro['msg'][] = "Ocorreu um erro atualizando dados da Transportadora #003";
                    }                    

                }else{
                    $sql = "
                        INSERT INTO tbl_transportadora_fabrica (
                            transportadora,
                            fabrica,
                            codigo_interno,
                            ativo
                            {$insTranspFabC}
                        ) VALUES (
                            {$transportadora},
                            {$login_fabrica},
                            {$xcodigo_interno},
                            {$xativo}
                            {$insTranspFabV}
                        );
                    ";
                    $res = pg_query($con,$sql);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro['msg'][] = "Ocorreu um erro atualizando dados da Transportadora #004";
                    }
                }
            }
        }else{

            // ######################## INSERE ########################
            $sql = "
                INSERT INTO tbl_transportadora (
                    nome,
                    cnpj,
                    fantasia
                    {$insTranspC}
                ) VALUES (
                    {$xnome},
                    {$xcnpj},
                    {$xfantasia}
                    {$insTranspV}
                );
            ";

            $res = pg_query($con,$sql);
            
            if (strlen(pg_last_error()) > 0) {
                $msg_erro['msg'][] = "Ocorreu um erro cadastrando uma nova Transportadora #001";
            }

            if (count($msg_erro["msg"]) == 0){
                $res = pg_query($con,"SELECT CURRVAL('seq_transportadora')");

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro['msg'][] = "Ocorreu um erro cadastrando uma nova Transportadora #002";
                } else {
                    $transportadora = pg_result($res,0,0);
                }
            }

            if (count($msg_erro["msg"]) == 0){
                $sql = "
                    INSERT INTO tbl_transportadora_fabrica (
                        transportadora,
                        fabrica,
                        codigo_interno,
                        ativo
                        {$insTranspFabC}
                    ) VALUES (
                        {$transportadora},
                        {$login_fabrica},
                        {$xcodigo_interno},
                        {$xativo}
                        {$insTranspFabV}
                    );
                ";
                
                $res = pg_query($con, $sql);
                
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro['msg'][] = "Ocorreu um erro cadastrando uma nova Transportadora #003";
                }
            }
        }
    }
    if(count($msg_erro["msg"]) > 0){
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }else{
        $res = pg_query($con,"COMMIT TRANSACTION");
        $msg = "Gravado com Sucesso!";
        $_REQUEST['transportadora'] = $transportadora;
    }
}

#-------------------- Pesquisa -----------------
$transportadora = $_REQUEST['transportadora'];

if (strlen($transportadora) > 0 && count($msg_erro["msg"]) == 0) {
    $sql = "
        SELECT
            tbl_transportadora.nome,
            tbl_transportadora.cnpj,
            tbl_transportadora.fantasia,
            tbl_transportadora.ie,
            tbl_transportadora_fabrica.codigo_interno,
            tbl_transportadora_fabrica.ativo,
            tbl_transportadora_fabrica.contato_email,
            tbl_transportadora_fabrica.contato_endereco,
            tbl_transportadora_fabrica.contato_cidade,
            tbl_transportadora_fabrica.contato_estado,
            tbl_transportadora_fabrica.contato_bairro,
            tbl_transportadora_fabrica.contato_cep,
            tbl_transportadora_fabrica.fone
        FROM tbl_transportadora
        JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora
        WHERE tbl_transportadora.transportadora = {$transportadora}
        AND tbl_transportadora_fabrica.fabrica = {$login_fabrica};
    ";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $nome             = trim(pg_result($res,0,nome));
        $cnpj             = trim(pg_result($res,0,cnpj));
        $fantasia         = trim(pg_result($res,0,fantasia));
        $ie               = trim(pg_result($res,0,ie));
        $codigo_interno   = trim(pg_result($res,0,codigo_interno));
        $ativo            = trim(pg_result($res,0,ativo));
        $email            = trim(pg_result($res,0,contato_email));
        $endereco         = trim(pg_result($res,0,contato_endereco));
        $cidade           = trim(pg_result($res,0,contato_cidade));
        $estado           = trim(pg_result($res,0,contato_estado));
        $bairro           = trim(pg_result($res,0,contato_bairro));
        $cep              = trim(pg_result($res,0,contato_cep));
        $fone             = trim(pg_result($res,0,fone));
    }
}

$visual_black = "manutencao-admin";

if(strlen($msg)==0)
    $msg = $_GET['msg'];

$title     = "Cadastro de Transportadoras";
$cabecalho = "Cadastro de Transportadoras";

$layout_menu = "cadastro";

include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "shadowbox",
    "mask",
    "dataTable",
    "price_format"
);

include("plugin_loader.php");

if ($login_fabrica == 120 or $login_fabrica == 201) {
    echo '<script src="js/transportadora_alterar_faixas_frete.js"></script>';
}

?>
<script>
$(function() {
    $.autocompleteLoad(Array("transportadora"));
    Shadowbox.init();
    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $("#cnpj").mask("99.999.999/9999-99");
    $('#cnpj').focusout(function(){
        if ($('#cnpj').val() !== "" && $('#estado').length == 0) {
            var fabrica = <?=$login_fabrica; ?>;
            $.ajax({
                method: "POST",
                url: window.location,
                timeout: 5000,
                data: {ajax: 'sim',cnpj: $('#cnpj').val(), fabrica: fabrica}
            }).fail(function(){
            }).done(function(data){
                data = JSON.parse(data);
                if (data['ok'] !== undefined) {
                    alert(data['ok']);
                    $('#cnpj').val('').focus();
                }
            });
        }
    });

    $("input[id*='kg']").each(function(){
        $(this).css("text-align","right");
        $(this).priceFormat({
            prefix: '',
            centsSeparator: ',',
            thousandsSeparator: '.'
        });
    });

<?
if($login_fabrica == 120 or $login_fabrica == 201){
?>
    $("#seguro").css("text-align","right");
    $("#seguro").priceFormat({
        prefix: '',
        centsSeparator: ',',
        thousandsSeparator: '.'
    });

    $("#gris").css("text-align","right");
    $("#gris").priceFormat({
        prefix: '',
        centsSeparator: ',',
        thousandsSeparator: '.'
    });

    altera_faixas_frete();
<?
}
?>

    $("input[id^='kg_']").each(function(){
        $(this).priceFormat({
            prefix: '',
            centsSeparator: ',',
            thousandsSeparator: '.',
            centsLimit:3
        })
    });

    $("input[id^='ipt_']").each(function(){
        $(this).priceFormat({
            prefix: '',
            centsSeparator: ',',
            thousandsSeparator: '.'
        })
    });

    $("input[id^='ipt_kg_']").each(function(){
        $(this).priceFormat({
            prefix: '',
            centsSeparator: ',',
            thousandsSeparator: '.',
            centsLimit:3
        })
    });


    $("button[id^=remove_linha_]").click(function(){
        var linha = $(this).parents("tr");
        var valor = $(linha).find("input[name^=valor_]").val();
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            data:{
                descredenciar:"sim",
                valor:valor
            }
        })
        .done(function(data){
            $(linha).html("<td colspan='100%' class'tac'><div class='alert-success'>Item excluÌdo com sucesso</div></td>");
            setTimeout(function(){
                $(linha).remove();
            },1000);
        })
        .fail(function(data){
            alert(data.responseText);
        });
    });

    /**
     * Evento para quando alterar o estado carregar as cidades do estado
     */
    $("#estado").change(function() {
        busca_cidade($(this).val());
    });

    /**
     * Evento para buscar o endereÁo do cep digitado
     */
    $("#cep").blur(function() {
        if ($(this).attr("readonly") == undefined) {
            busca_cep($(this).val());
        }
    });
});

function retorna_transportadora (retorno) {
    $("#codigo_interno").val(retorno.codigo_interno);
    $("#cnpj").val(retorno.cnpj);
    $("#nome").val(retorno.nome);
    $("#fantasia").val(retorno.fantasia);

    if(retorno.ativo == 't'){
        $("#op_sim").prop('checked', true);
    }else{
        $("#op_nao").prop('checked', true);
    }

    $("#estado").val(retorno.estado);
    $("#capital_interior option:contains('" + retorno.capital_interior + "')").prop('selected', true);
    $("#valor_frete").val(retorno.valor_frete);

    <?php if (in_array($login_fabrica, [169,170,177])) { ?>

	var cidade_aux = retiraAcentos(retorno.cidade);

	$("#ie").val(retorno.ie);
	$("#email").val(retorno.email);
	$("#cep").val(retorno.cep);
	$("#estado option[value='"+retorno.uf+"']").attr('selected','selected');
	busca_cidade(retorno.uf, cidade_aux, true);
	$("#bairro").val(retorno.bairro);
	$("#endereco").val(retorno.endereco);
	$("#fone").val(retorno.fone);
    <?php } ?>

}

/**
 * FunÁ„o que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, cidade, valor) {
    $("#cidade").find("option").first().nextAll().remove();

    if (estado.length > 0) {
        $.ajax({
            async: false,
            url:"<?= $PHP_SELF; ?>",
            type: "POST",
            data: { ajax_busca_cidade: true, estado: estado },
            beforeSend: function() {
                if ($("#cidade").next("img").length == 0) {
                    $("#cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                }
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $.each(data.cidades, function(key, value) {
                        var option = $("<option></option>", { value: value, text: value });
                        $("#cidade").append(option);
                    });
                }

                $("#cidade").show().next().remove();
            }
        });
    }

    if(typeof cidade != "undefined" && cidade.length > 0) {
	if (typeof valor != "undefined" && valor.length > 0) {
	    $("#cidade option:contains("+cidade+")").attr('selected','selected');
	} else {
            $("#cidade option[value='"+cidade+"']").attr('selected','selected');
	}
    }

}

/**
 * FunÁ„o que faz um ajax para buscar o cep nos correios
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
                $("#estado").next("img").remove();
                $("#cidade").next("img").remove();
                $("#bairro").next("img").remove();
                $("#endereco").next("img").remove();

                $("#estado").hide().after(img.clone());
                $("#cidade").hide().after(img.clone());
                $("#bairro").hide().after(img.clone());
                $("#endereco").hide().after(img.clone());
            },
            error: function(xhr, status, error) {
                busca_cep(cep, "database");
            },
            success: function(data) {
                results = data.split(";");

                if (results[0] != "ok") {
                    alert(results[0]);
                    $("#cidade").show().next().remove();
                } else {
                    $("#estado").val(results[4]);

                    busca_cidade(results[4]);
                    results[3] = results[3].replace(/[()]/g, '');

                    $("#cidade").val(retiraAcentos(results[3]).toUpperCase());

                    if (results[2].length > 0) {
                        $("#bairro").val(results[2]);
                    }

                    if (results[1].length > 0) {
                        $("#endereco").val(results[1]);
                    }
                }

                $("#estado").show().next().remove();
                $("#bairro").show().next().remove();
                $("#endereco").show().next().remove();

                if ($("#bairro").val().length == 0) {
                    $("#bairro").focus();
                } else if ($("#endereco").val().length == 0) {
                    $("#endereco").focus();
                }

                $.ajaxSetup({
                    timeout: 0
                });
            }
        });
    }
}

/**
 * FunÁ„o para retirar a acentuaÁ„o
 */
function retiraAcentos(palavra){
    if (!palavra) {
        return "";
    }

    var com_acento = '·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«';
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

</script>

<? if (count($msg_erro['msg']) > 0) { ?>
    <br />
    <div class="alert alert-error"><h4><?= implode("<br />", $msg_erro["msg"]); ?></h4></div>
<? } 
if (strlen($msg) > 0) { ?>
    <div class="alert alert-success">
        <h4><?= $msg; ?></h4>
    </div>
<? } ?>

<div class="row-fluid">
    <div class="alert">
    Para incluir uma nova transportadora, preencha somente seu CNPJ e clique em gravar.<br />
    Faremos uma pesquisa para verificar se a transportadora j· est· cadastrada em nosso banco de dados.
    </div>
</div>

<form name='frm_transportadora' METHOD='post' ACTION='<? echo $PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
<input type="hidden" name="transportadora" value="<? echo $transportadora ?>">
<input type='hidden' name='btn_acao' value=''>

    <div class='titulo_tabela '>Cadastro de Transportadora</div>
    <br />
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='cnpj'>CNPJ</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <input type="text" id="cnpj" name="cnpj" class='span12' maxlength="18" value="<? echo $cnpj ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="transportadora" parametro="cnpj" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='nome'>Nome da Transportadora</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <input type="text" id="nome" name="nome" class='span12 ' value="<? echo $nome ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="transportadora" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='codigo_interno'>CÛdigo</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" id="codigo_interno" name="codigo_interno" class='span12' maxlength="20" value="<? echo $codigo_interno ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span5'>
                <div class='control-group'>
                    <label class='control-label' for='fantasia'>Fantasia</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" id="fantasia" name="fantasia" class='span12' value="<? echo $fantasia ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <? if (in_array($login_fabrica, array(169,170,177))) { ?>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class="span2">
                    <div class='control-group'>
                        <label class="control-label" for="ie">IE</label>
                        <div class="controls controls-row">
                            <div class="input-append">
                                <input id="ie" name="ie" class="span12" type="text" value="<?= $ie; ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='email'>Email</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <input type="text" id="email" name="email" class='span12' value="<? echo $email ?>" >
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class='control-group'>
                        <label class="control-label" for="cep">CEP</label>
                        <div class="controls controls-row">
                            <div class="input-append">
                                <input id="cep" name="cep" class="span12" type="text" value="<?= $cep; ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>

            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span2">
                    <div class="control-group">
                        <label class="control-label" for="estado">Estado</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <select id="estado" name="estado_transportadora" class="span12" >
                                    <option value="">Selecione</option>
                                    <?
                                    foreach ($array_estados as $sigla => $nome_estado) {
                                        $selected = ($sigla == $estado) ? "selected" : ""; ?>
                                        <option value="<?= $sigla; ?>" <?= $selected; ?>><?= $nome_estado; ?></option>
                                    <? } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span3">
                    <div class="control-group">
                        <label class="control-label" for="cidade">Cidade</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <select id="cidade" name="cidade" class="span12" >
                                    <option value="">Selecione</option>
                                    <? if (!empty($estado)) {
                                        $sql = "
                                            SELECT * FROM (
                                                SELECT UPPER(TRIM(fn_retira_especiais(cidade))) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                                                UNION
                                                SELECT UPPER(TRIM(fn_retira_especiais(nome))) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                                            ) AS cidade
                                            ORDER BY cidade ASC;
                                        ";
                                        $res = pg_query($con, $sql);

                                        if (pg_num_rows($res) > 0) {
                                            
                                            $sql = "SELECT UPPER(TRIM(fn_retira_especiais('{$cidade}')))";
                                            $resUpperCidade = pg_query($con,$sql);
                                            $cidade = pg_fetch_result($resUpperCidade,0,0);

                                            while ($result = pg_fetch_object($res)) {
                                                $selected = ($result->cidade == $cidade) ? " selected" : ""; ?>
                                                <option value="<?= $result->cidade; ?>" <?= $selected; ?>><?= $result->cidade; ?></option>
                                            <? }
                                        }
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span3">
                    <div class='control-group'>
                        <label class="control-label" for="bairro">Bairro</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="bairro" name="bairro" class="span12" type="text" value="<?= $bairro; ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span6">
                    <div class='control-group'>
                        <label class="control-label" for="endereco">EndereÁo, n∞</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="endereco" name="endereco" class="span12" type="text" value="<?= $endereco; ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class='control-group'>
                        <label class="control-label" for="fone">Telefone</label>
                        <div class="controls controls-row">
                            <div class="input-append">
                                <input id="fone" name="fone" class="span12" type="text" value="<?= $fone; ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
        <? } ?>

        <div id="ativo" class='row-fluid'>
            <div class='span2'></div>

            <div class='span4'>
                <div class="control-group" >
                    <label class="control-label" >Ativo</label>
                    <div class="controls controls-row" >
                        <label class="radio inline" for="op_sim">
                            <input type="radio" name="ativo" value="t" <? if ($ativo == 't') echo"checked"; ?> id='op_sim'>
                            Sim
                        </label>
                        <label class="radio inline" for="op_nao">
                            <input type="radio" name="ativo" value="f" <? if ($ativo == 'f') echo"checked"; ?> id='op_nao'>
                            N„o
                        </label>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <p><br/>
            <input class="btn" type="button" value="Gravar" onclick="javascript: if (document.frm_transportadora.btn_acao.value == '' ) { document.frm_transportadora.btn_acao.value='gravar' ; document.frm_transportadora.submit() } else { alert ('Aguarde submiss„o') }" ALT="Gravar formul·rio">
            <input  class="btn" type="button" value="LISTAR TODAS AS TRANSPORTADORAS" onclick="javascript: window.location='<? echo $PHP_SELF ?>?listar=todos#transportadoras';">
        </p><br/>

    <? if($transportadora != "" && !in_array($login_fabrica, array(143,145,157,163,169,170,175,177))){ ?>
        <div class="row-fluid">
            <div class="alert">
                Os dados abaixo s„o de preenchimento obrigatÛrio no caso de Transportadora Padr„o.
            </div>
        </div>

        <div class='titulo_tabela '>InformaÁıes Cadastrais</div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='estado'>Estado</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
<?
    $array_estado = array(  "AC"=>"AC - Acre",
                            "AL"=>"AL - Alagoas",
                            "AM"=>"AM - Amazonas",
                            "AP"=>"AP - Amap·",
                            "BA"=>"BA - Bahia",
                            "CE"=>"CE - Cear·",
                            "DF"=>"DF - Distrito Federal",
                            "ES"=>"ES - EspÌrito Santo",
                            "GO"=>"GO - Goi·s",
                            "MA"=>"MA - Maranh„o",
                            "MG"=>"MG - Minas Gerais",
                            "MS"=>"MS - Mato Grosso do Sul",
                            "MT"=>"MT - Mato Grosso",
                            "PA"=>"PA - Par·",
                            "PB"=>"PB - ParaÌba",
                            "PE"=>"PE - Pernambuco",
                            "PI"=>"PI - PiauÌ",
                            "PR"=>"PR - Paran·",
                            "RJ"=>"RJ - Rio de Janeiro",
                            "RN"=>"RN - Rio Grande do Norte",
                            "RO"=>"RO - RondÙnia",
                            "RR"=>"RR - Roraima",
                            "RS"=>"RS - Rio Grande do Sul",
                            "SC"=>"SC - Santa Catarina",
                            "SE"=>"SE - Sergipe",
                            "SP"=>"SP - S„o Paulo",
                            "TO"=>"TO - Tocantins"
                        );
?>
                            <select id="estado" name="estado" class='span12'>
                                <option value="">&nbsp;</option>
<?
    foreach ($array_estado as $k => $v) {
?>
                                <option value="<?=$k?>" <?=$estado == $k ? "selected" : ""?> ><?=$v?></option>
<?
    }
?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='capital_interior'>Local</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select id="capital_interior" name="capital_interior">
                                <option selected></option>
                                <option value="CAPITAL" <? if ($capital_interior == "CAPITAL") echo " selected"; ?>>CAPITAL</option>
                                <option value="INTERIOR" <? if ($capital_interior == "INTERIOR") echo " selected"; ?>>INTERIOR</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <?php
        if($login_fabrica != 94){
        ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='kg_inicial'>Kg Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <input type="text" id="kg_inicial" name="kg_inicial" class='span12' maxlength="20" value="" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='kg_final'>Kg Final</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <input type="text" id="kg_final" name="kg_final" class='span12' maxlength="20" value="" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <?php
        }
        ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='valor_kg'><?php echo ($login_fabrica != 94) ? "Valor Kg" : "Valor"; ?></label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <input type="text" id="valor_kg" name="valor_kg" class='span12' maxlength="20" value="" />
                        </div>
                    </div>
                </div>
            </div>
             <?php
            if($login_fabrica != 94){
            ?>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='valor_kg_excedente'>Valor Kg Excedente</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <input type="text" id="valor_kg_excedente" name="valor_kg_excedente" class='span12' maxlength="20" value="" >
                        </div>
                    </div>
                </div>
            </div>
             <?php
            }
            ?>
            <div class='span2'></div>
        </div>
<?
    if($login_fabrica == 120 or $login_fabrica == 201){
?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='seguro'>Seguro %</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <input type="text" id="seguro" name="seguro" class='span12' maxlength="20" value="" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='gris'>GRIS %</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <input type="text" id="gris" name="gris" class='span12' maxlength="20" value="" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
<?
    }
?>
        <p><br/>
            <?php
                $btn_name = ($login_fabrica != 94) ? "Gravar Faixas" : "Gravar";
            ?>
            <input class="btn" type="button" value="<?=$btn_name?>" onclick="javascript: if (document.frm_transportadora.btn_acao.value == '' ) { document.frm_transportadora.btn_acao.value='gravar_faixas' ; document.frm_transportadora.submit() } else { alert ('Aguarde submiss„o') }" ALT="Gravar formul·rio">

            &nbsp; &nbsp;&nbsp;
            <input type="button" class="btn" value="Limpar" onclick="javascript: if (document.frm_transportadora.btn_acao.value == '' ) { document.frm_transportadora.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submiss„o') }" ALT="Limpar campos">

        </p><br/>
<?
}
?>
</form>

<?
if ($_GET['listar'] == 'todos') {


    $sql = "SELECT  tbl_transportadora.transportadora        ,
                    tbl_transportadora.cnpj                  ,
                    tbl_transportadora.nome                  ,
                    tbl_transportadora_fabrica.codigo_interno
            FROM    tbl_transportadora
            JOIN    tbl_transportadora_fabrica USING (transportadora)
            WHERE   tbl_transportadora_fabrica.fabrica = $login_fabrica
      ORDER BY      tbl_transportadora.nome";
    $res = pg_exec ($con,$sql);
    for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

        if ($i % 20 == 0) {
            if ($i > 0) echo "</table>";
            $colspan = $login_fabrica == 88 ? 5 : 3;
            flush();
            echo "<table id='resultado_os_atendimento' class='table table-striped table-bordered table-hover table-full'> ";
            echo "<thead>";

            echo "<tr class='titulo_tabela'>";
            echo "<th colspan='$colspan'>LISTA COM TODAS TRANSPORTADORAS</th>";
            echo "</tr>";

            echo "<tr class='titulo_coluna'>";

            echo "<th>";
            echo "<b>CÛdigo</b>";
            echo "</th>";

            echo "<th>";
            echo "<b>CNPJ</b>";
            echo "</th>";

            echo "<th>";
            echo "<b>Nome</b>";
            echo "</th>";

            echo "</tr>";
            echo "</thead>";
        }

        echo "<tbody>";
        echo "<tr>";

        echo "<td class='tal'>";
        echo pg_result ($res,$i,codigo_interno);
        echo "</td>";

        echo "<td class='tal'>";
        echo pg_result ($res,$i,cnpj);
        echo "</td>";
        echo "<td class='tal'>";
        echo "<a href='$PHP_SELF?transportadora=" . pg_result ($res,$i,transportadora) . "'>";
        echo pg_result ($res,$i,nome);
        echo "</a>";
        echo "</td>";

        echo "</tr>";
        echo "</tbody>";
    }

     echo "</table>";
}

if(!empty($transportadora)) {
	$sql = "SELECT  capital_interior        ,
		estado                  ,
		transportadora_padrao
		FROM    tbl_transportadora_padrao
		WHERE   fabrica         = $login_fabrica
		AND     transportadora  = $transportadora
		GROUP BY      transportadora_padrao   ,
		capital_interior        ,
		estado 	";
	$res = pg_query($con,$sql);
	flush();
	if(pg_num_rows($res) > 0){
		$step = 0;
		for($t = 0;$t < pg_num_rows($res);$t++){

			$transportadora_padrao = pg_fetch_result($res,$t,transportadora_padrao);
			$transportadora_estado = pg_fetch_result($res,$t,estado);
			$transportadora_regiao = pg_fetch_result($res,$t,capital_interior);

			$colspan = in_array($login_fabrica,array(120,201)) ? 7 : 5;

			$sqlT = "SELECT  *
				FROM    tbl_transportadora_valor
				WHERE   transportadora_padrao = $transportadora_padrao
				ORDER BY      kg_inicial
				";
			$resT = pg_query($con,$sqlT);
			if (!pg_num_rows($resT)) {
				continue;
			}
?>
	<table id='resultado_os_atendimento' class='table table-striped table-bordered table-hover table-full'>
		<thead>
<?php
			$titulo = ($login_fabrica != 94) ? "LISTA COM AS FAIXAS DE Kg DA REGI√O" : "LISTA COM VALORES DE FRETE DA REGI√O"
?>
			<tr class='titulo_tabela'>
				<th colspan='<?=$colspan?>'><?=$titulo." ".$transportadora_regiao." ".$transportadora_estado?></th>
			</tr>
			<tr class='titulo_coluna'>

<?
				if($login_fabrica != 94){
?>
				<th>
					<b>Kg Inicial</b>
				</th>
				<th>
					<b>Kg Final</b>
				</th>
				<th>
					<b>Valor Kg</b>
				</th>
				<th>
					<b>Valor excedente Kg</b>
				</th>
<?
				}
			if($login_fabrica == 120 or $login_fabrica == 201){
?>
				<th>
					<b>Seguro (%)</b>
				</th>
				<th>
					<b>GRIS (%)</b>
				</th>
<?
			}

			if($login_fabrica == 94){
?>
				<th>
					<b>Valor Frete</b>
				</th>
<?
			}
?>
				<th>
					&nbsp;
				</th>
			</tr>
		</thead>
		<tbody>
<?
			for($c=0;$c < pg_num_rows($resT); $c++){
				$kg_inicial = number_format(pg_fetch_result($resT,$c,'kg_inicial'),3,',','');
				$kg_final = number_format(pg_fetch_result($resT,$c,'kg_final'),3,',',''); 
				$valor_kg = number_format(pg_fetch_result($resT,$c,'valor_kg'),2,',','');
				$valor_acima_kg_final = number_format(pg_fetch_result($resT,$c,'valor_acima_kg_final'),2,',','');
				$seguro = number_format(pg_fetch_result($resT,$c,'seguro'),2,',','');
				$gris = number_format(pg_fetch_result($resT,$c,'gris'),2,',','');
?>
			<tr>
<?
				if($login_fabrica != 94){
?>
				<input type="hidden" class="transportadora_valor_<?php echo $step ?>" value="<?=pg_fetch_result($resT,$c,transportadora_valor)?>" id="valor_<?=$c?>" name="valor_<?=$c?>"/>
				<td class='tar'>
					<?php if ($login_fabrica == 120 or $login_fabrica == 201): ?>
					<input type="hidden" id="ipt_kg_inicial_<?php echo $step ?>" value="<?php echo $kg_inicial ?>" />
					<?php endif ?>
					<span id="kg_inicial_<?php echo $step ?>"><?=$kg_inicial?></span>
				</td>
				<td class='tar'>
					<?php if ($login_fabrica == 120 or $login_fabrica == 201): ?>
					<input type="hidden" id="ipt_kg_final_<?php echo $step ?>" value="<?php echo $kg_final ?>" />
					<?php endif ?>
					<span id="kg_final_<?php echo $step ?>"><?=$kg_final?>
				</td>
				<td class='tar'>
					<?php if ($login_fabrica == 120 or $login_fabrica == 201): ?>
					<input type="hidden" id="ipt_valor_kg_<?php echo $step ?>" value="<?php echo $valor_kg ?>" />
					<?php endif ?>
					<span id="valor_kg_<?php echo $step ?>"><?=$valor_kg?></span>
				</td>
				<td class='tar'>
					<?php if ($login_fabrica == 120 or $login_fabrica == 201): ?>
					<input type="hidden" id="ipt_valor_acima_kg_final_<?php echo $step ?>" value="<?php echo $valor_acima_kg_final ?>" />
					<?php endif ?>
					<span id="valor_acima_kg_final_<?php echo $step ?>"><?=$valor_acima_kg_final?></span>
				</td>
<?
				}
				if($login_fabrica == 120 or $login_fabrica == 201){
?>
				<td class='tar'>
					<input type="hidden" id="ipt_seguro_<?php echo $step ?>" value="<?php echo $seguro ?>" />
					<span id="seguro_<?php echo $step ?>"><?=$seguro?></span>
				</td>
				<td class='tar'>
					<input type="hidden" id="ipt_gris_<?php echo $step ?>" value="<?php echo $gris ?>" />
					<span id="gris_<?php echo $step ?>"><?=$gris?></span>
				</td>
<?
				}

				if($login_fabrica == 94){
?>
				<td class='tar'>
					<span id="valor_kg_<?php echo $step ?>"><?=$valor_kg?></span>
				</td>
<?
				}
?>
				<td>
					<button class='btn btn-danger btn-small excluir_<?php echo $step ?>' id="remove_linha_<?=$c?>" rel="<?=pg_fetch_result($resT,$c,transportadora_valor)?>" type="button" >Excluir</button>
					<?php if ($login_fabrica == 120 or $login_fabrica == 201): ?>
					<button class='btn btn-warning btn-small' id="alterar_<?php echo $step ?>" type="button">Alterar</button>
					<button class='btn btn-primary btn-small' id="salvar_<?php echo $step ?>" type="button" style="display: none;">Salvar</button>
					<button class='btn btn-danger btn-small' id="cancelar_<?php echo $step ?>" type="button" style="display: none;">Cancelar</button>
					<?php endif ?>
				</td>
			</tr>
<?
				$step++;
			}
?>
		</tbody>
	</table>
<?
		}
	}
}
?>


<? include "rodape.php"; ?>

