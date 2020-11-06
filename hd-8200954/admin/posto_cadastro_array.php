<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "cadastros,call_center";
include "autentica_admin.php";
include "funcoes.php";
include "../helpdesk/mlg_funciones.php";//  Para o mapa do Brasil
include "../helpdesk.inc.php";// Funcoes de HelpDesk
require dirname(__FILE__) . '/../class_resize.php';
$debug = 1;
error_reporting(E_ERROR);

$msg_erro = "";
$msg_debug = "";
$msg = $_GET['msg'];

$array_antes = array();
$array_depois = array();

// Autocomplete ajax
if (isset($_GET["q"])){

    //$q = trim(preg_replace("/\W/", ' ', utf8_decode(mb_strtolower($_GET["q"]))));
    $q = utf8_decode(trim($_GET["q"]));

    if ($_GET["busca"] == "cidade"){
        if (strlen($q)>2){
            $sql = "SELECT cod_ibge, cidade as cidade, estado
            FROM tbl_ibge
            WHERE
            /*cidade ~* E'$q'*/
            cidade ilike  '%$q%'
            ORDER BY length(cidade) ASC
             LIMIT 100";
            $res = pg_query($con,$sql);
            $numrows = pg_num_rows($res);

            if (pg_num_rows ($res) > 0) {

                header('Content-type: text/html; charset=utf-8');

                for ($i=0; $i<pg_num_rows ($res); $i++ ){
                    $codigo_ibge    = pg_fetch_result($res,$i,cod_ibge);
                    $cidade_estado  = utf8_encode(pg_fetch_result($res,$i,cidade));
                    $estado         = utf8_encode(pg_fetch_result($res,$i,estado));
                    echo "$cidade_estado - $estado|$codigo_ibge";
                    echo "\n";
                }
            }
        }
    }
    exit;
}

if($_GET['buscaCidade']){
    $uf = $_GET['estado'];

    if($uf == "BR-CO"){
        $estado = "'GO','MS','MT','DF'";
    } else if($uf == "BR-NE"){
        $estado = "'SE','AL','RN','MA','PE','PB','CE','PI','BA'";
    } else if($uf == "BR-N"){
        $estado = "'TO','PA','AP','RR','AM','AC','RO'";
    } else {
        $estado = "'$uf'";
    }
    $sql = "SELECT DISTINCT contato_estado,contato_cidade FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica and contato_estado in($estado) ORDER BY contato_estado,contato_cidade";
    $res = pg_query($con,$sql);
    if(pg_numrows($res) > 0){
        $retorno = "<option value=''>Todos</option>";
        for($i = 0; $i < pg_numrows($res); $i++){
            $cidade = pg_result($res,$i,'contato_cidade');
            $estado = pg_result($res,$i,'contato_estado');

            $nome_cidade = in_array($uf,array('BR-CO','BR-NE','BR-N')) ? "$cidade - $estado" : $cidade;

            $retorno .= "<option value='$cidade'>$nome_cidade</option>";
        }
    } else {
        $retorno .= "<option value=''>Cidade não encontrada</option>";
    }

    echo $retorno;
    exit;
}

if (strlen($_REQUEST['posto'])) {

    $posto = anti_injection($_REQUEST['posto']);

    $sql = "SELECT UPPER(pais) FROM tbl_posto WHERE posto = $posto";

    $res   = pg_query($con, $sql);
    $_pais = pg_fetch_result ($res, 0, 0);

}

//exclusão da foto
if (strlen($_GET['posto']) > 0 and strlen($_GET['excluir_foto']) > 0 and strlen($_GET['foto']) > 0) {
    $posto    = trim($_GET['posto']);
    $excluir_foto = trim($_GET['excluir_foto']);
    $foto         = trim($_GET['foto']);
    $sql = "SELECT * FROM tbl_posto_fabrica_foto WHERE posto_fabrica_foto = $excluir_foto";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $aux_fabrica = pg_fetch_result($res, 0, fabrica);

        //valida a fabrica para o caso de ter sido alterado direto na barra de endereços
        if ($aux_fabrica == $login_fabrica) {
            if ($foto == 'foto_posto') {
                $caminho_foto  = pg_fetch_result($res, 0, 'foto_posto');
                $caminho_thumb = pg_fetch_result($res, 0, 'foto_posto_thumb');

                $sql = "UPDATE tbl_posto_fabrica_foto SET
                            foto_posto           = NULL,
                            foto_posto_thumb     = NULL,
                            foto_posto_descricao = NULL
                        WHERE posto_fabrica_foto = $excluir_foto";
                $res = pg_query($con, $sql);

                system("rm $caminho_foto");
                system("rm $caminho_thumb");
            }

            if ($foto == 'foto_contato1') {
                $caminho_foto  = pg_fetch_result($res, 0, foto_contato1);
                $caminho_thumb = pg_fetch_result($res, 0, foto_contato1_thumb);

                $sql = "UPDATE tbl_posto_fabrica_foto SET
                            foto_contato1           = NULL,
                            foto_contato1_thumb     = NULL,
                            foto_contato1_descricao = NULL
                        WHERE posto_fabrica_foto = $excluir_foto";
                $res = pg_query($con, $sql);

                system("rm $caminho_foto");
                system("rm $caminho_thumb");
            }

            if ($foto == 'foto_contato2') {
                $caminho_foto  = pg_fetch_result($res, 0, foto_contato2);
                $caminho_thumb = pg_fetch_result($res, 0, foto_contato2_thumb);

                $sql = "UPDATE tbl_posto_fabrica_foto SET
                            foto_contato2           = NULL,
                            foto_contato2_thumb     = NULL,
                            foto_contato2_descricao = NULL
                        WHERE posto_fabrica_foto = $excluir_foto";
                $res = pg_query($con, $sql);

                system("rm $caminho_foto");
                system("rm $caminho_thumb");
            }
        }
    }
}

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

#-------------------- Descredenciar -----------------
if ($btn_acao == "descredenciar" and strlen($posto) > 0 ) {
    $sql = "DELETE FROM tbl_posto_fabrica
            WHERE  tbl_posto_fabrica.posto   = $posto
            AND    tbl_posto_fabrica.fabrica = $login_fabrica;";
    $res = pg_query ($con,$sql);

    if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

    if (strlen($msg_erro) == 0) {
        header ("Location: $PHP_SELF");
        exit;
    }
}

if ($btn_acao == "gravar") {
    $cnpj  = trim($_POST['cnpj']);
    $xcnpj = str_replace (".","",$cnpj);
    $xcnpj = str_replace ("-","",$xcnpj);
    $xcnpj = str_replace ("/","",$xcnpj);
    $xcnpj = str_replace (" ","",$xcnpj);

    $nome  = trim($_POST ['nome']);

    $posto = trim($_POST ['posto']);

    if (strlen($posto) > 0){
        $sqlVcnpj = "SELECT cnpj
                    FROM tbl_posto
                    WHERE posto = $posto";
        $resVcnpj = pg_query ($con,$sqlVcnpj);

        if (pg_num_rows ($resVcnpj) > 0){
            if ($xcnpj <> trim((pg_fetch_result ($resVcnpj,0,0)))){
                if($login_fabrica <> 1 and $login_fabrica <> 20) {
                    $msg_erro = "A alteração de CNPJ só é possível mediante abertura de
                chamados para a Telecontrol";
                }
            }
        }
    }

    if($login_fabrica <> 20){
        if (strlen($nome) == 0 AND strlen($xcnpj) > 0) {
            // verifica se posto está cadastrado
            $sql = "SELECT posto
                    FROM   tbl_posto
                    WHERE  cnpj = '$xcnpj'";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) > 0) {
                $posto = pg_fetch_result ($res,0,0);
                header ("Location: $PHP_SELF?posto=$posto");
                exit;
            }else{
                $posto    = '';
                $msg_erro = "Posto não cadastrado, favor completar os dados do cadastro.";
            }
        }

        if(strlen($xcnpj) <> 14 and !in_array($login_fabrica, array(2,5,7,14,30,35,45,49,50,51,52,86,85,117)))
        {
            $msg_erro = "CNPJ inválido, digitar novamente.";
        }

        if(strlen($xcnpj) <> 14 AND strlen($xcnpj) <> 11 and !in_array($login_fabrica, array(2,5,7,30,35,45,49,50,51,86,85,117))){
            //Cadence   07/04/2008 HD 17261 - A Cadence tem postos que são cadastrados pelo CPF
            //Dynacom   06/03/2008 HD 15279 - A Dynacom tem postos que são cadastrados pelo CPF
            //NKS       16/04/2008 HD 17853 - A NKS tem postos que são cadastrados pelo CPF
            //GAMA      23/07/2008 HD 27662 - A GAMA tem postos que são cadastrados pelo CPF
            //MOndial   16/03/2010 HD 208465    - A Mondial tem postos que são cadastrados pelo CPF
            //FILIZOLA  11/08/2008 HD 27662 - A FILIZOLA tem postos que são cadastrados pelo CPF
            //ESMALTEC      14/05/2009 HD 106125    - A Esmaltec tem postos que são cadastrados pelo CPF
            //FAMASTIL      28/05/2010 Fone     - A Famastil precisou cadastrar 2 postos com CPF (MLG)
            //ELGIN         26/04/2012 HD 1108731   - A Elgin tem postos que são cadastrados pelo CPF
            $msg_erro = "CNPJ/CPF inválido, digitar novamente..";
        }

        if($login_fabrica==2){//HD 34921 29/8/2008
            $validar = checa_cnpj($xcnpj);
            if ($validar==1){
                $msg_erro = "Por favor digite um CNPJ válido.";
            }
        }

        if(strlen($xcnpj) == 0){
            $msg_erro = "Digite o CNPJ do Posto.";
        }else{
            $cnpj = $xcnpj;
        }

    }else{
        if($_pais == "BR"){
            if(strlen($xcnpj) <> 14 AND strlen($xcnpj) <> 11){
                $msg_erro = "CNPJ/CPF inválido, digitar novamente.";
            }
        }
    }

    if (strlen($msg_erro) == 0){
        if (strlen($posto) == 0 AND strlen($nome) > 0 AND strlen($xcnpj) > 0) {
            // verifica se posto está cadastrado
            $sql = "SELECT posto
                    FROM   tbl_posto
                    WHERE  cnpj = '$xcnpj'";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) > 0){
                $posto = pg_fetch_result ($res,0,0);
                header ("Location: $PHP_SELF?posto=$posto");
                exit;
            }
        }
    }
    $codigo  = trim($_POST ['codigo']);
    if(strlen($codigo)==0){
        $msg_erro = "Digite o código do posto! Ele será utilizado para LOGIN do posto. Se você não tiver um, sugerimos o CNPJ como código.";
    }

    if (strlen($msg_erro) == 0){
        $ie                                      = trim($_POST ['ie']);
        $im                                      = trim($_POST ['im']);
        $endereco                                = trim($_POST ['endereco']);
        $numero                                  = trim($_POST ['numero']);
        $complemento                             = trim($_POST ['complemento']);
        $bairro                                  = trim($_POST ['bairro']);
        $cep                                     = trim($_POST ['cep']);
        $cidade                                  = trim($_POST ['cidade']);
        $estado                                  = trim($_POST ['estado']);
        $email                                   = trim($_POST ['email']);
        if ($login_fabrica == 15){

            $email2                              = trim($_POST['email2']);
            $fone2                               = trim($_POST ['fone2']);
            $fone3                               = trim($_POST ['fone3']);

        }
        $fone                                    = trim($_POST ['fone']);
        if ($login_fabrica == 40)
        {
            $fone2                                   = trim($_POST ['fone2']);
        }
        $fax                                     = trim($_POST ['fax']);
        $contato                                 = trim($_POST ['contato']);
        $nome_fantasia                           = trim($_POST ['nome_fantasia']);
        $obs                                     = trim($_POST ['obs']);
        $capital_interior                        = trim($_POST ['capital_interior']);
        $posto_empresa                           = trim($_POST ['posto_empresa']);
        $tipo_posto                              = trim($_POST ['tipo_posto']);
        $divulgar_consumidor                     = trim($_POST ['divulgar_consumidor']);
        $escritorio_regional                     = trim($_POST ['escritorio_regional']);
        $codigo                                  = trim($_POST ['codigo']);
        $senha                                   = trim($_POST ['senha']);
        $desconto                                = trim($_POST ['desconto']);
        $valor_km                                = trim($_POST ['valor_km']);
        $desconto_acessorio                      = trim($_POST ['desconto_acessorio']);
        $custo_administrativo                    = trim($_POST ['custo_administrativo']);
        $imposto_al                              = trim($_POST ['imposto_al']);
        $suframa                                 = trim($_POST ['suframa']);
        $item_aparencia                          = trim($_POST ['item_aparencia']);
        $pedido_em_garantia_finalidades_diversas = trim($_POST ['pedido_em_garantia_finalidades_diversas']);
        $pais                                    = trim($_POST ['pais']);
        $garantia_antecipada                     = trim($_POST ['garantia_antecipada']);
        $imprime_os                              = trim($_POST ['imprime_os']);
        $qtde_os_item                            = trim($_POST ['qtde_os_item']);
        $escolhe_condicao                        = trim($_POST ['escolhe_condicao']); #HD 23738
        $condicao_liberada                       = trim($_POST ['condicao_liberada']); #HD 23738
        $atende_consumidor                       = trim($_POST ['atende_consumidor']);
        $contribuinte_icms                       = trim($_POST ['contribuinte_icms']);
        if(!$contribuinte_icms)                  $contribuinte_icms = 'f';

// MLG  17/7/2009   HD 126810 - Adicionado campo 'atende_consumidor'

        if(strlen($pais)==0) $msg_erro = "Selecione o país";

        $xie                = (strlen($ie) > 0)                 ? "'$ie'"                   : 'null';
        $xim                = (strlen($im) > 0)                 ? "'$im'"                   : 'null';
        $xnumero            = (strlen($numero) > 0)             ? "'$numero'"               : 'null';
        $xcomplemento       = (strlen($complemento) > 0)        ? "'$complemento'"          : 'null';
        $xbairro            = (strlen($bairro) > 0)             ? "'$bairro'"               : 'null';
        $xcidade            = (strlen($cidade) > 0)             ? "'$cidade'"               : 'null';
        $xestado            = (strlen($estado) > 0)             ? "'$estado'"               : 'null';
        $xcontato           = (strlen($contato) > 0)            ? "'$contato'"              : 'null';
        $xemail             = (strlen($email) > 0)              ? "'$email'"                : 'null';

        if ($login_fabrica==15) {

            if (strlen($email2) > 0) {
                $xemail_latina = "'".$email.";".$email2."'";
            }else{
                $xemail_latina = $xemail;
            }

        }

        $xfone              = (strlen($fone) > 0)               ? "'$fone'"                 : 'null';
        if ($login_fabrica <> 15){

            $xfone2             = (strlen($fone2) > 0)              ? "'$fone2'"                    : 'null';
            $xfone3             = (strlen($fone3) > 0)              ? "'$fone3'"                    : 'null';

        }else{

            $xfone = "ARRAY['".$fone."','".$fone2."','".$fone3."']";

        }

        $xfax               = (strlen($fax) > 0)                ? "'$fax'"                  : 'null';
        $xnome_fantasia     = (strlen($nome_fantasia) > 0)      ? "'$nome_fantasia'"        : 'null';
        $xcapital_interior  = (strlen($capital_interior) > 0)   ? "'$capital_interior'"     : 'null';
        $xposto_empresa     = (strlen($posto_empresa) > 0)      ? "'$posto_empresa'"        : 'null';
        $xtipo_posto        = (strlen($tipo_posto) > 0)         ? "'$tipo_posto'"           : 'null';
        $xescritorio_regional=(strlen($escritorio_regional)> 0) ? "'$escritorio_regional'"  : 'null';
        $xcodigo            = (strlen($codigo) > 0)             ? "'$codigo'"               : 'null';
        $xsuframa           = (strlen($suframa) > 0)            ? "'$suframa'"                  : "'f'" ;
        $zgarantia_antecipada=(strlen($garantia_antecipada)> 0) ? "'f'"                     : "'".$garantia_antecipada."'";
        $xescolhe_condicao  = (strlen($escolhe_condicao) > 0)   ? "'t'"                     : "'f'";
        $xatende_consumidor = (strlen($atende_consumidor) > 0)  ? "'t'"                     : "'f'";
        $xendereco          = (strlen($endereco) > 0)           ? "'".$endereco."'"         : 'null';
        $xendereco          = (strlen($endereco) > 0)           ? "'".$endereco."'"         : 'null';
        if (strlen($cep) > 0){
            $xcep = str_replace (".","",$cep);
            $xcep = str_replace ("-","",$xcep);
            $xcep = str_replace (" ","",$xcep);
            $xcep = "'".substr($xcep,0,8)."'";
        }else{
            $xcep = 'null';
        }

        if ($login_fabrica == 11) {
            $permite_envio_produto = $_POST["permite_envio_produto"];
        }

        if (strlen($pedido_em_garantia_finalidades_diversas) == 0)
            $xpedido_em_garantia_finalidades_diversas = "'f'";
        if($pedido_em_garantia_finalidades_diversas=='t')
            $xpedido_em_garantia_finalidades_diversas = "'$pedido_em_garantia_finalidades_diversas'";

        $sql="SELECT posto FROM tbl_posto where cnpj ='$xcnpj'";
        $res=pg_query($con,$sql);
        $msg_erro.=pg_errormessage($con);
        if(pg_num_rows($res) >0){
            $posto=pg_fetch_result($res,0,posto);
        }

        $vCodigo = trim($_POST['codigo']);
        $sqlSenha = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$vCodigo' AND senha = '$senha' and length(senha) > 3 AND fabrica <> $login_fabrica";
        $qrySenha = pg_query($con, $sqlSenha);

        if (pg_num_rows($qrySenha) > 0) {
            $msg_erro = 'Senha inválida, favor escolher outra.';
        }

        /**
         * SE HOUVER ALGUM CAMPO QUE PRECISA DE VALIDAÇÃO DE INT, ADICIONAR AQUI
         */
        $campos_int_verificar = array('"Desconto"' => 'desconto',
                                      '"Desconto Acessório"' => 'desconto_acessorio',
                                      '"Imposto IVA"' => 'imposto_al',
                                      '"Custo Administrativo"' => 'custo_administrativo',
                                      '"Valor KM"' => 'valor_km',
                                      '"Acréscimo Tributário"' => 'acrescimo_tributario',
                                      '"Taxa Administrativa"' => 'taxa_administrativa');
        $error_inteiros = array();

        foreach ($campos_int_verificar as $text => $value) {

            $$value = str_replace(',', '.', $$value);

            if ( isset($$value) and strlen($$value)>0 and !is_numeric($$value) ) {

                $error_inteiros[] = "Campo $text deve conter apenas números";

            }

        }

        if (count($error_inteiros)>0) {
            $msg_erro = implode('<br>', $error_inteiros);
        }

        if (strlen($msg_erro) == 0) {
            $res = pg_query ($con,"BEGIN TRANSACTION");

            #----------------------------- Alteração de Dados ---------------------
            if (strlen ($posto) == 0) {
                #-------------- INSERT ---------------
                $sql = "INSERT INTO tbl_posto (
                            nome            ,
                            cnpj            ,
                            ie              ,";
                if($login_fabrica == 15){
                    $sql  .= "im            ,";
                }
                $sql .= "   endereco        ,
                            numero          ,
                            complemento     ,
                            bairro          ,
                            cep             ,
                            cidade          ,
                            estado          ,
                            email           ,";
                if($login_fabrica == 15){
                    $sql  .= "telefones     ,";
                }else{
                    $sql .= "
                              fone          ,";

                }

                    $sql .="
                            fax             ,
                            nome_fantasia   ,
                            capital_interior,
                            pais            ,
                            suframa
                        ) VALUES (
                            '$nome'                  ,
                            '$xcnpj'                 ,
                            $xie                     ,";
                if($login_fabrica == 15){
                    $sql  .= "$xim            ,";
                }
                $sql  .= "$xendereco                 ,
                            $xnumero                 ,
                            $xcomplemento            ,
                            $xbairro                 ,
                            $xcep                    ,
                            $xcidade                 ,
                            $xestado                 ,
                            $xemail                  ,
                            $xfone                   ,
                            $xfax                    ,
                            $xnome_fantasia          ,
                            upper($xcapital_interior),
                            '$pais'                  ,
                            $xsuframa
                        )";
                $res = pg_query($con,$sql);
                if (!is_resource($res)) {   // Se usar o pg_last_error/pg_errormessage não vai devolver o erro do CNPJ e sim o do 'current transaction aborted'
                    $erro_cnpj = explode('.',pg_last_error($con));
                    $msg_erro   = preg_replace('/ERROR: /','',$erro_cnpj[0]);
                    unset($erro_cnpj);
                }

                if (strlen($msg_erro) == 0){
                    $sql = "SELECT CURRVAL ('seq_posto')";
                    $res = pg_query ($con,$sql);
                    $posto = pg_fetch_result ($res,0,0);
                    $msg_erro = pg_errormessage ($con);

                    $novo_posto = $posto;
                }

                if($login_fabrica == 1 and !empty($xtaxa_administrativa)){
                    $sql = "INSERT INTO tbl_excecao_mobra(posto,fabrica,tx_administrativa) VALUES($posto,$login_fabrica,$xtaxa_administrativa)";
                    $res = pg_query($con,$sql);
                }
            }else{
                $sql = "UPDATE tbl_posto SET
                        suframa = $xsuframa
                    WHERE posto = $posto ";
                $res = pg_query($con,$sql);
            }

            // grava posto_fabrica
            if (strlen($msg_erro) == 0 and strlen($posto) > 0){
                // HD 110541
                if($login_fabrica==11){
                    $atendimento_lenoxx  = trim ($_POST['atendimento_lenoxx']);
                }
                $codigo_posto            = trim ($_POST['codigo']);
                $senha                   = trim ($_POST['senha']);
                $posto_empresa           = trim ($_POST['posto_empresa']);
                $tipo_posto              = trim ($_POST['tipo_posto']);
                $escritorio_regional     = trim ($_POST['escritorio_regional']);
                $obs                     = trim ($_POST['obs']);
                $transportadora          = trim ($_POST['transportadora']);
                //HD-808142
                if($login_fabrica == 52){
                    $tabela_servico          = trim ($_POST['tabela_servico']);
                    if(empty($tabela_servico)){
                        $msg_erro = "Escolha uma tabela de serviço.";
                    }
                }
                $cobranca_endereco       = trim ($_POST['cobranca_endereco']);
                $cobranca_numero         = trim ($_POST['cobranca_numero']);
                $cobranca_complemento    = trim ($_POST['cobranca_complemento']);
                $cobranca_bairro         = trim ($_POST['cobranca_bairro']);
                $cobranca_cep            = trim ($_POST['cobranca_cep']);
                $cobranca_cidade         = trim ($_POST['cobranca_cidade']);
                $cobranca_estado         = trim ($_POST['cobranca_estado']);
                $desconto                = trim ($_POST['desconto']);
                $valor_km                = trim ($_POST['valor_km']);
                $desconto_acessorio      = trim ($_POST['desconto_acessorio']);
                $custo_administrativo    = trim ($_POST['custo_administrativo']);
                $imposto_al              = trim ($_POST['imposto_al']);
                $pedido_em_garantia      = trim($_POST ['pedido_em_garantia']);
                $coleta_peca             = trim($_POST ['coleta_peca']);
                $reembolso_peca_estoque  = trim($_POST ['reembolso_peca_estoque']);
                $pedido_faturado         = trim($_POST ['pedido_faturado']);
                $digita_os               = trim($_POST ['digita_os']);
                $controla_estoque        = trim($_POST ['controla_estoque']);
                if ($login_fabrica == 15){
                    $tipo_controle_estoque = trim($_POST ['tipo_controle_estoque']);
                }
                $prestacao_servico       = trim($_POST ['prestacao_servico']);
                $prestacao_servico_sem_mo= trim($_POST ['prestacao_servico_sem_mo']);
                $pedido_bonificacao      = trim($_POST ['pedido_bonificacao']);
                $banco                   = trim($_POST ['banco']);
                $agencia                 = trim($_POST ['agencia']);
                $conta                   = trim($_POST ['conta']);
                $favorecido_conta        = trim($_POST ['favorecido_conta']);
                $conta_operacao          = trim($_POST ['conta_operacao']);//HD 8190 5/12/2007 Gustavo
                $cpf_conta               = trim($_POST ['cpf_conta']);
                $tipo_conta              = trim($_POST ['tipo_conta']);
                $obs_conta               = trim($_POST ['obs_conta']);
                $pedido_via_distribuidor = trim($_POST ['pedido_via_distribuidor']);
                $pais                    = trim($_POST ['pais']);
                $garantia_antecipada     = trim($_POST ['garantia_antecipada']);
                // HD 12104
                $imprime_os              = trim($_POST ['imprime_os']);
                #HD 407694
                $acrescimo_tributario    = trim($_POST ['acrescimo_tributario']);
                $taxa_administrativa     = trim($_POST ['taxa_administrativa']);
                // HD 17601
                $qtde_os_item            = trim($_POST ['qtde_os_item']);
                $escolhe_condicao        = trim($_POST ['escolhe_condicao']);
                // ! HD 121248 (augusto) - Atendente de callcenter para o posto
                $admin_sap               = (int) $_POST['admin_sap'];
                $admin_sap               = (empty($admin_sap)) ? 'null' : $admin_sap ;

                if ($login_fabrica == 3) {
                    $admin_sap_especifico = $_POST["admin_sap_especifico"];
                    $admin_sap_especifico = (empty($admin_sap_especifico)) ? 'null' : $admin_sap_especifico;
                }

                // HD 126810
                $atende_consumidor       = (strlen(trim($_POST ['atende_consumidor']))>0) ? "'t'" : "'f'";
                // hd 21496 - Francisco - campo Data da Nomeação para Dynacom
            if ($login_fabrica==2) { $data_nomeacao           = trim ($_POST['data_nomeacao']);
                $data_nomeacao = (strlen($data_nomeacao) >0) ? "$data_nomeacao" : "0001-01-01";
            }

            if($login_fabrica == 30){ //HD 356653 Inicio
                $conta_contabil                          = trim($_POST ['conta_contabil']);
                $centro_custo                            = trim($_POST ['centro_custo']);
                $local_entrega                           = trim($_POST ['local_entrega']);
                $fixo_km_valor                           = str_replace(",",".",$_POST ['fixo_km_valor']);

                if(!empty($fixo_km_valor)){
                    $aux_fixo_km = '{"valor_km_fixo":"'.$fixo_km_valor.'"}';
                }else{
                    $aux_fixo_km = '{"valor_km_fixo":""}';
                }
            } //HD 356653 Fim

            if ($login_fabrica == 42){#HD 401553 INICIO
                $posto_filial                            = trim($_POST ['posto_filial']);
                $posto_filial = (empty($posto_filial)) ? 'f' : $posto_filial;
            }#HD 401553 FIM

            //HD 672836 - inicio
            if ($login_fabrica == 1 and empty($msg_erro)){
                if ($admin_sap){
                    $sql = "SELECT admin from tbl_admin_atendente_estado where estado=$xestado and fabrica = $login_fabrica";

                    $res = pg_query($con,$sql);

                    $admin_atendente_estado = (pg_num_rows($res)>0) ? pg_result($res,0,0) : "" ;

                    if ($admin_sap <> $admin_atendente_estado){

                        $admin_sap_especifico = $admin_sap;
                        $admin_sap            = $admin_sap;

                    }else{

                        $admin_sap_especifico = "null";
                        $admin_sap            = $admin_atendente_estado;

                    }

                }

            }

            //HD 672836 - fim

            if(strlen($pais)==0) $msg_erro = "Selecione o país";

                if($login_fabrica==19){
                    $atende_comgas = trim($_POST ['atende_comgas']);
                    $atende_comgas = (strlen($atende_comgas)==0) ? "'f'" : "'t'";
                }
                $xcodigo_posto         = (strlen($codigo_posto) > 0)         ? "'" . strtoupper ($codigo_posto) . "'" : 'null';
                $xsenha                = (strlen($senha) > 0)                ? "'".$senha."'"                         : "'*'";
                $xdesconto             = (strlen($desconto) > 0)             ? "'".$desconto."'"                      : 'null';
                $xdesconto             = str_replace (",",".",$xdesconto);
                $valor_km              = str_replace (",",".",$valor_km);
                $desconto_acessorio    = str_replace (",",".",$desconto_acessorio);
                $imposto_al            = str_replace (",",".",$imposto_al);
                $custo_administrativo  = str_replace (",",".",$custo_administrativo);

                $xvalor_km             = (strlen($valor_km) > 0)             ? $valor_km                              : '0';
                $xdesconto_acessorio   = (strlen($desconto_acessorio) > 0)   ? "'".$desconto_acessorio."'"            : 'null';
                $xcusto_administrativo = (strlen($custo_administrativo) > 0) ? "'".$custo_administrativo."'"          : 0;
                $ximposto_al           = (strlen($imposto_al) > 0)           ? "'".$imposto_al."'"                    : 'null';
                $xposto_empresa        = (strlen($posto_empresa) > 0)        ? "'".$posto_empresa."'"                 : 'null';
                $xtipo_posto           = (strlen($tipo_posto) > 0)           ? "'".$tipo_posto."'"                    : 'null';
                $xescritorio_regional  = (strlen($escritorio_regional) > 0)  ? "'".$escritorio_regional."'"           : 'null';
                $xobs                  = (strlen($obs) > 0)                  ? "'".$obs."'"                           : 'null';
                $xtransportadora       = (strlen($transportadora) > 0)       ? "'".$transportadora."'"                : 'null';
                $xcobranca_endereco    = (strlen($cobranca_endereco) > 0)    ? "'".$cobranca_endereco."'"             : 'null';
                $xcobranca_numero      = (strlen($cobranca_numero) > 0)      ? "'".$cobranca_numero."'"               : 'null';
                $xcobranca_complemento = (strlen($cobranca_complemento) > 0) ? "'".$cobranca_complemento."'"          : 'null';
                $xcobranca_bairro      = (strlen($cobranca_bairro) > 0)      ? "'".$cobranca_bairro."'"               : 'null';
                $xacrescimo_tributario = (strlen($acrescimo_tributario) > 0) ? $acrescimo_tributario                  : '0';
                $xtaxa_administrativa = (strlen($taxa_administrativa) > 0) ? $taxa_administrativa                     : '0';

                if($xacrescimo_tributario > 0 || strpos($xacrescimo_tributario,',') > 0){
                    $xacrescimo_tributario = str_replace('.','',$xacrescimo_tributario);
                    $xacrescimo_tributario = str_replace(',','.',$xacrescimo_tributario);
                }

                if($xtaxa_administrativa > 0 || strpos($xtaxa_administrativa,',') > 0){
                    $xtaxa_administrativa = str_replace('.','',$xtaxa_administrativa);
                    $xtaxa_administrativa = str_replace(',','.',$xtaxa_administrativa);

                    $xtaxa_administrativa = ($xtaxa_administrativa / 100) + 1;
                }

                if (strlen($cobranca_cep) > 0){
                    $xcobranca_cep = str_replace (".","",$cobranca_cep);
                    $xcobranca_cep   = str_replace ("-","",$xcobranca_cep);
                    $xcobranca_cep  = str_replace (" ","",$xcobranca_cep);
                    $xcobranca_cep =   "'".$xcobranca_cep."'";
                }else{
                    $xcobranca_cep = 'null';
                }

                $xcobranca_cidade        = (strlen($cobranca_cidade)        > 0) ? "'".$cobranca_cidade."'"        : 'null';
                $xcobranca_estado        = (strlen($cobranca_estado)        > 0) ? "'".$cobranca_estado."'"        : 'null';
                $xobs                    = (strlen($obs)                    > 0) ? "'".$obs."'"                    : 'null';
                $xpedido_em_garantia     = (strlen($pedido_em_garantia)     > 0) ? "'".$pedido_em_garantia."'"     : "'f'";
                $xcoleta_peca            = (strlen($coleta_peca)            > 0) ? "'".$coleta_peca."'"            : "'f'";
                $xreembolso_peca_estoque = (strlen($reembolso_peca_estoque) > 0) ? "'".$reembolso_peca_estoque."'" : "'f'";
                $xpedido_faturado        = (strlen($pedido_faturado)        > 0) ? "'".$pedido_faturado."'"        : "'f'";
                $xdigita_os              = (strlen($digita_os)              > 0) ? "'".$digita_os."'"              : "'f'";
                $xcontrola_estoque       = (strlen($controla_estoque)       > 0) ? "'".$controla_estoque."'"       : "'f'";

                if ($login_fabrica == 42) {
                    $entrega_tecnica = $_POST["entrega_tecnica"];

                    if ($entrega_tecnica <> "t") {
                        $entrega_tecnica = "f";
                    }
                }

                if ($login_fabrica == 15){ //HD 755863

                    if ($tipo_controle_estoque == 'nenhum'){

                        $xcontrola_estoque = 'false';
                        $xcontrole_estoque_novo = 'false';
                        $xcontrole_estoque_manual = 'false';

                    }elseif ($tipo_controle_estoque == 'estoque_normal'){

                        $xcontrola_estoque = 'true';
                        $xcontrole_estoque_novo = 'false';
                        $xcontrole_estoque_manual = 'false';

                    }elseif($tipo_controle_estoque == 'estoque_novo'){

                        $xcontrola_estoque = 'false';
                        $xcontrole_estoque_novo = 'true';
                        $xcontrole_estoque_manual = 'false';

                    }elseif($tipo_controle_estoque == 'estoque_manual'){

                        $xcontrola_estoque = 'false';
                        $xcontrole_estoque_novo = 'false';
                        $xcontrole_estoque_manual = 'true';

                    }

                    $sql = "SELECT controla_estoque, controle_estoque_novo, controle_estoque_manual
                            FROM  tbl_posto_fabrica
                            WHERE fabrica = $login_fabrica
                            AND   posto = $posto";
                    $res = pg_query($con,$sql);

                    if (pg_num_rows($res)>0){

                        list($controla_estoque_cad,$controle_estoque_novo_cad,$controle_estoque_manual_cad) = pg_fetch_row($res,0);

                        if ( ( ($controla_estoque == 'f' and $controle_estoque_novo_cad == 'f' and $controle_estoque_manual_cad == 'f') or $controla_estoque_cad == 't' ) and $tipo_controle_estoque == 'estoque_novo' ){

                            // SEGUNDO SOLICITAÇÃO DO CLIENTE, NO MOMENTO QUE O ADMIN MUDAR O TIPO DE CONTROLE DE
                            // ESTOQUE DE UM DETERMINADO POSTO, SERÁ ZERADO O ESTOQUE DE TODAS AS PEÇAS EXISTENTES...

                            $sql_update_estoque = "
                                UPDATE tbl_estoque_posto
                                SET qtde = 0
                                WHERE posto= $posto
                                and fabrica = $login_fabrica
                            ";

                            $res_update_estoque = pg_query($con,$sql_update_estoque);

                        }
                    }

                }

                $xprestacao_servico        = (strlen($prestacao_servico) > 0)    ? "'".$prestacao_servico."'"        : "'f'";
                $xprestacao_servico_sem_mo = (!empty($prestacao_servico_sem_mo)) ? "'".$prestacao_servico_sem_mo."'" : "'f'";
                $xgarantia_antecipada      = (strlen($garantia_antecipada) > 0)  ? "'".$garantia_antecipada."'"      : "'f'";
                $xpedido_bonificacao       = (strlen($pedido_bonificacao) > 0)   ? "'".$pedido_bonificacao."'"       : "'f'";

                if (strlen($banco) > 0) {
                    $xbanco = "'".$banco."'";
                    $sqlB = "SELECT nome FROM tbl_banco WHERE codigo = '$banco'";
                    $resB = pg_query($con,$sqlB);
                    if (pg_num_rows($resB) == 1) {
                        $xnomebanco = "'" . trim(pg_fetch_result($resB,0,0)) . "'";
                    }else{
                        $xnomebanco = "null";
                    }
                }else{
                    $xbanco     = "null";
                    $xnomebanco = "null";
                }

                $xagencia          = (strlen($agencia)          > 0) ? "'".$agencia."'"          : 'null';
                $xconta            = (strlen($conta)            > 0) ? "'".$conta."'"            : 'null';
                $xfavorecido_conta = (strlen($favorecido_conta) > 0) ? "'".$favorecido_conta."'" : 'null';
                $xconta_operacao   = (strlen($conta_operacao)   > 0) ? "'".$conta_operacao."'"   : 'null';
                $xtipo_conta       = (strlen($tipo_conta)       > 0) ? "'".$tipo_conta."'"       : 'null';

                $cpf_conta = str_replace (".","",$cpf_conta);
                $cpf_conta = str_replace ("-","",$cpf_conta);
                $cpf_conta = str_replace ("/","",$cpf_conta);
                $cpf_conta = str_replace (" ","",$cpf_conta);

                if (strlen($cpf_conta) <> 14 AND $tipo_conta == 'Conta jurídica'){
                    $msg_erro = "CNPJ da Conta jurídica inválida";
                }

                $xcpf_conta               = (strlen($cpf_conta) > 0) ? "'".$cpf_conta."'" : 'null';
                $xobs_conta               = (strlen($obs_conta) > 0) ? "'".$obs_conta."'" : 'null';
                $xpedido_via_distribuidor = (strlen($pedido_via_distribuidor) > 0) ? "'".$pedido_via_distribuidor."'" : "'f'";

                    // HD 17601
                    if(strlen($qtde_os_item)==0){
                        if($login_fabrica==45){
                            $msg_erro="Por favor, preencher a quantidade de itens na OS que o posto pode lançar";
                        }else{
                            $qtde_os_item="0";
                        }
                    }

                if ($login_fabrica == 3 and (empty($admin_sap) or $admin_sap == 'null')) {
                    $msg_erro.= 'Por favor, selecione o inspetor para esse posto.';
                }

                if (strlen($msg_erro) == 0 AND strlen($posto) > 0) {
                    $sql = "SELECT  tbl_posto_fabrica.*
                            FROM    tbl_posto_fabrica
                            WHERE   tbl_posto_fabrica.posto   = $posto
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica ";
                    $res = pg_query($con,$sql);
                    if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
                }

                //Lenoxx não pode repetir código do posto, pois nº da OS é gerado pelo codigo
                if ( strlen($msg_erro) == 0 AND strlen($posto) > 0 AND strlen($xcodigo_posto) > 0 ) {
                    $sqlx = "SELECT  tbl_posto_fabrica.*
                            FROM    tbl_posto_fabrica
                            WHERE   tbl_posto_fabrica.posto       <> $posto
                            AND     tbl_posto_fabrica.fabrica      = $login_fabrica
                            AND     tbl_posto_fabrica.codigo_posto = $xcodigo_posto";
                    $resx = pg_query($con,$sqlx);
                    if (pg_num_rows($resx) > 0) $msg_erro = "Já existe um posto cadastrado com o código $xcodigo_posto";
                }

                if (strlen($msg_erro) == 0){
                    $total_rows = pg_num_rows($res);
                    //HD 15225
                    if ($login_fabrica == 3) {
                        $xpedido_via_distribuidor = "'t'";
                        $xpedido_em_garantia      = "'f'";
                        $xreembolso_peca_estoque  = "'f'";
                    }

                    //HD 12104

                    if($login_fabrica == 14){
                        $imprime_os = (strlen($imprime_os) > 0) ? 't' : 'f';
                    } else {
                        $imprime_os='f';
                    }

                    if($login_fabrica==7 AND $xposto_empresa<>'null'){
                        $sqlp = "SELECT posto
                                FROM tbl_posto_fabrica
                                WHERE codigo_posto = $xposto_empresa
                                AND   fabrica      = $login_fabrica";
                        $resp = @pg_query ($con,$sqlp);
                        $msg_erro = pg_errormessage($con);
                        if (pg_num_rows ($resp) > 0) {
                            $xposto_empresa = pg_fetch_result($resp, 0, posto);
                        }else{
                            $msg_erro = "Código posto empresa não encontrado. ";
                        }
                    }

                    if ($login_fabrica == 20 and strlen($posto) > 0) {
                        // HD 31884
                        $sql_t="SELECT  tbl_posto_fabrica.* ,
                                nome                ,
                                cnpj                ,
                                ie                  ,
                                capital_interior    ,
                                suframa             ,
                                pais                ,
                                contato
                            INTO  TEMP tmp_posto_$login_admin
                            FROM  tbl_posto_fabrica
                            JOIN  tbl_posto USING (posto)
                            WHERE tbl_posto_fabrica.posto  = $posto
                            AND   tbl_posto_fabrica.fabrica= $login_fabrica;

                        CREATE INDEX tmp_posto_posto_$login_admin ON tmp_posto_$login_admin(posto); ";

			$res_t=pg_query($con,$sql_t);
			#$array_antes = pg_fetch_all($rest_t);
			#$array_antes = json_encode($array_antes);
                    }
                        $upd_valor_km = "valor_km                = $xvalor_km               ,";
                    if ( isset($_POST['controla_estoque']) )
                        $x_controla_estoque = $_POST['controla_estoque'];
                    else
                        $x_controla_estoque = 'f';

                    if ($login_fabrica == 15 && $_POST['posto_vip'] && $_POST['posto_vip']=='vip') {
                        $posto_vip = $_POST['posto_vip'];
                    }else if($login_fabrica == 15 && $_POST['posto_vip'] != 'vip'){
                        $posto_vip = "";
                    }

                    if ($login_fabrica == 1 && $_POST['categoria_posto']) {
                        $categoria_posto = $_POST['categoria_posto'];
                    }else if($login_fabrica == 1){
                        $categoria_posto = "";
                    }

                    // HD 220549 - disponibiliza chamado apenas para esse flag como 'n'
                    if (isset($_POST['tela_os_nova'])) {

                        $sql_atendimento = "atendimento = 'n',"; // @todo colocar no insert e update

                    } else if ($login_fabrica == 20) {

                        $sql_atendimento = " atendimento = null, ";

                    }

                    if (pg_num_rows ($res) > 0) {
                        // ! Atualizar POSTO FABRICA

                        $sql = "UPDATE tbl_posto_fabrica SET
                                    codigo_posto            = $xcodigo_posto           ,";
                                    if($login_fabrica == 52){

                                        $sql .="tabela_mao_obra = $tabela_servico,";
                                    }

                                    if ($login_fabrica == 11) {
                                        $sql .= "permite_envio_produto = '$permite_envio_produto',";
                                    }
                        $sql .= "
                                    senha                   = $xsenha                  ,
                                    posto_empresa           = $xposto_empresa          ,
                                    tipo_posto              = $xtipo_posto             ,
                                    obs                     = $xobs                    ,
                                    contato_nome            = $xcontato                ,
                                    $sql_atendimento
                                    contato_endereco        = $xendereco               ,
                                    contato_numero          = $xnumero                 ,
                                    contato_complemento     = $xcomplemento            ,
                                    contato_bairro          = $xbairro                 ,
                                    contato_cidade          = $xcidade                 ,
                                    contato_cep             = $xcep                    ,
                                    contato_estado          = $xestado                 ,";

                        if ($login_fabrica == 15){
                            $sql .= "contato_telefones = $xfone,";
                        }else{

                            $sql .= "
                                    contato_fone_comercial  = $xfone                   ,
                                    contato_fone_residencial = $xfone2                 ,";

                        }

                        if ($login_fabrica == 30) {
                            $sql .= " contato_cel = $xfone3 ,
                                      parametros_adicionais = '$aux_fixo_km',
                            ";
                        }

                        $sql .= "
                                    contato_fax             = $xfax                    ,
                                    nome_fantasia           = $xnome_fantasia          ,";

                        if ($login_fabrica==15) {
                            $sql .= "contato_email           = $xemail_latina          ,";
                        }else{
                            $sql .= "contato_email           = $xemail                 ,";
                        }

                        $sql .= "

                                    transportadora          = $xtransportadora         ,
                                    cobranca_endereco       = $xcobranca_endereco      ,
                                    cobranca_numero         = $xcobranca_numero        ,
                                    cobranca_complemento    = $xcobranca_complemento   ,
                                    cobranca_bairro         = $xcobranca_bairro        ,
                                    cobranca_cep            = $xcobranca_cep           ,
                                    cobranca_cidade         = $xcobranca_cidade        ,
                                    cobranca_estado         = $xcobranca_estado        ,
                                    desconto                = $xdesconto               ,
                                    $upd_valor_km
                                    desconto_acessorio      = $xdesconto_acessorio     ,
                                    custo_administrativo    = $xcusto_administrativo   ,
                                    imposto_al              = $ximposto_al             ,
                                    pedido_em_garantia      = $xpedido_em_garantia     ,
                                    coleta_peca             = $xcoleta_peca            ,
                                    reembolso_peca_estoque  = $xreembolso_peca_estoque ,
                                    pedido_faturado         = $xpedido_faturado        ,
                                    digita_os               = $xdigita_os              ,
                                    controla_estoque        = $xcontrola_estoque       ,";

                        if ($login_fabrica == 15){
                            $sql .= " controle_estoque_novo  = $xcontrole_estoque_novo,
                                    controle_estoque_manual  = $xcontrole_estoque_manual,
                                    categoria               = '$posto_vip',";
                        }
                        if ($login_fabrica == 1){
                            $sql .= " categoria               = '$categoria_posto',";
                        }

                            $sql .= "
                                    prestacao_servico       = $xprestacao_servico      ,
                                    prestacao_servico_sem_mo=$xprestacao_servico_sem_mo,
                                    pedido_bonificacao      = $xpedido_bonificacao     ,
                                    admin_sap               = $admin_sap               ,";

                            //HD 672836 Gabriel
                            $sql .= ($login_fabrica == 1 or $login_fabrica == 3) ? "admin_sap_especifico  = $admin_sap_especifico ," : " " ;

                            $sql .= "
                                    banco                   = $xbanco                  ,
                                    agencia                 = $xagencia                ,
                                    acrescimo_tributario    = $xacrescimo_tributario   ,
                                    conta                   = $xconta                  ,";
    if($login_fabrica==11){ $sql .= "atendimento            = '$atendimento_lenoxx'    , ";}//HD 110541
                            $sql .= "nomebanco              = $xnomebanco              ,
                                    favorecido_conta        = $xfavorecido_conta       ,
                                    escritorio_regional     = $xescritorio_regional    ,";
    //HD 8190 5/12/2007 Gustavo
    if($login_fabrica==45){ $sql .= "conta_operacao         = $xconta_operacao         , ";}
                            $sql .= "cpf_conta              = $xcpf_conta              ,
                                    tipo_conta              = $xtipo_conta             ,
                                    obs_conta               = $xobs_conta              , ";
    if($login_fabrica==19){ $sql .= " atende_comgas         = $atende_comgas           , ";}
                            $sql .= " pedido_via_distribuidor = $xpedido_via_distribuidor,
                                    item_aparencia          = '$item_aparencia'        ,
                                    data_alteracao          = CURRENT_TIMESTAMP        ,
                                    admin                   = $login_admin             ,
                                    garantia_antecipada     = $xgarantia_antecipada    ,
                                    atende_consumidor       = $xatende_consumidor      ,
                                    imprime_os              = '$imprime_os'            ,
                                    divulgar_consumidor     = '$divulgar_consumidor'   ,
                                    qtde_os_item            = '$qtde_os_item'           ,
                                    contribuinte_icms       = '$contribuinte_icms'     ";
    // hd 21496 - Francisco - campo Data da Nomeação para Dynacom
    if($login_fabrica==2){  $sql .= ",data_nomeacao         = '$data_nomeacao'";}
    //HD 356653
    if($login_fabrica == 30){ $sql .= ", centro_custo        = '$centro_custo',
                                     conta_contabil         = '$conta_contabil',
                                     local_entrega          = '$local_entrega'";}
                            // HD 401553
    if($login_fabrica==42){ $sql .= ", filial               = '$posto_filial',
                                       entrega_tecnica      = '$entrega_tecnica'";}

                            $sql .="WHERE tbl_posto_fabrica.posto   = $posto
                                    AND   tbl_posto_fabrica.fabrica = $login_fabrica ";

                    }else{

                        if (isset($_POST['tela_os_nova']) && $login_fabrica == 20) {

                            $atendimento_lenoxx = 'n';

                        }
                        // ! Inserir POSTO FABRICA
                        //HD-808142
                        $sql = "INSERT INTO tbl_posto_fabrica (
                                    posto                  ,";
                                if($login_fabrica == 52){
                                    $sql .= "tabela_mao_obra        ,";
                                }
                                if ($login_fabrica == 11) {
                                    $sql .= "permite_envio_produto ,";
                                }
                                $sql .= "
                                    fabrica                ,
                                    codigo_posto           ,
                                    senha                  ,
                                    desconto               ,
                                    valor_km               ,
                                    desconto_acessorio     ,
                                    custo_administrativo   ,
                                    imposto_al             ,
                                    posto_empresa          ,
                                    tipo_posto             ,
                                    obs                    ,
                                    contato_nome           ,
                                    contato_endereco       ,
                                    contato_numero         ,
                                    contato_complemento    ,
                                    contato_bairro         ,
                                    contato_cidade         ,
                                    contato_cep            ,
                                    contato_estado         ,";
                        if ($login_fabrica != 15){
                            $sql .= "
                                    contato_fone_comercial ,
                                    contato_fone_residencial , ";
                        }else{
                            $sql .= "contato_telefones,";
                        }

                        if ($login_fabrica == 30) {
                            $sql .= " contato_cel ,
                                      parametros_adicionais,
                            ";
                        }

                        $sql .= "
                                    contato_fax            ,
                                    nome_fantasia          ,
                                    contato_email          ,
                                    transportadora         ,
                                    cobranca_endereco      ,
                                    cobranca_numero        ,
                                    cobranca_complemento   ,
                                    cobranca_bairro        ,
                                    cobranca_cep           ,
                                    cobranca_cidade        ,
                                    cobranca_estado        ,
                                    pedido_em_garantia     ,
                                    reembolso_peca_estoque ,
                                    coleta_peca            ,
                                    pedido_faturado        ,
                                    digita_os              ,
                                    controla_estoque       ,
                                    prestacao_servico      ,
                                    prestacao_servico_sem_mo,
                                    pedido_bonificacao     ,
                                    admin_sap               ,";

                            //HD 755863
                            $sql .= ($login_fabrica == 15) ? "controle_estoque_novo, controle_estoque_manual, categoria, " : " ";

                            //HD 672836 Gabriel
                            $sql .= ($login_fabrica == 1) ? "admin_sap_especifico, categoria, " : " " ;
                            $sql .= ($login_fabrica == 3) ? "admin_sap_especifico, " : " " ;

                            $sql .= "
                                    banco                  ,
                                    agencia                ,
                                    conta                  ,
                                    nomebanco              ,
                                    favorecido_conta       ,
                                    atende_consumidor      ,
                                    cpf_conta              ,
                                    tipo_conta             ,
                                    acrescimo_tributario   ,
                                    obs_conta              ,
                                    pedido_via_distribuidor,
                                    item_aparencia         ,
                                    data_alteracao         ,
                                    admin                  ,
                                    garantia_antecipada    ,
                                    escritorio_regional    ,
                                    imprime_os             ,
                                    qtde_os_item           ,
                                    contribuinte_icms      ";
    if($login_fabrica==2) { $sql .= ",data_nomeacao        "; } // hd 21496
    if($login_fabrica==11 || $login_fabrica == 20){ $sql .= ",atendimento          "; } // HD 110541
    if($login_fabrica==19){ $sql .= ",atende_comgas        ";}
    if($login_fabrica==45){ $sql .= ",conta_operacao       ";} //HD 8190
    if($login_fabrica ==30) { $sql .=",centro_custo,
                                      conta_contabil,
                                      local_entrega"; } //HD 356653
    // HD 401553 -  Gabriel
    if($login_fabrica==42) { $sql .= ", filial, entrega_tecnica"; }
                            $sql .="
                                ) VALUES (
                                    $posto                   ,";
                                    if($login_fabrica == 52){
                                        $sql.=" $tabela_servico          ,";
                                    }
                                    if ($login_fabrica == 11) {
                                        $sql .= "'$permite_envio_produto' ,";
                                    }
                                    $sql .= "
                                    $login_fabrica           ,
                                    $xcodigo                 ,
                                    $xsenha                  ,
                                    $xdesconto               ,
                                    $xvalor_km               ,
                                    $xdesconto_acessorio     ,
                                    $xcusto_administrativo   ,
                                    $ximposto_al             ,
                                    $xposto_empresa          ,
                                    $xtipo_posto             ,
                                    $xobs                    ,
                                    $xcontato                ,
                                    $xendereco               ,
                                    $xnumero                 ,
                                    $xcomplemento            ,
                                    $xbairro                 ,
                                    $xcidade                 ,
                                    $xcep                    ,
                                    $xestado                 ,";
                            if ($login_fabrica != 15) {
                                $sql .= "
                                    $xfone                   ,
                                    $xfone2                  , ";
                            }else{
                                $sql .= "$xfone,";
                            }
                            if ($login_fabrica == 30) {
                                $sql .= " $xfone3       ,
                                        '$aux_fixo_km'    ,
                                ";
                            }

                            $sql .= "
                                    $xfax                    ,
                                    $xnome_fantasia          ,";

                            if ($login_fabrica == 15){
                                $sql .= "$xemail_latina      ,";
                            }else{

                                $sql .= "$xemail             ,";
                            }

                            $sql .= "
                                    $xtransportadora         ,
                                    $xcobranca_endereco      ,
                                    $xcobranca_numero        ,
                                    $xcobranca_complemento   ,
                                    $xcobranca_bairro        ,
                                    $xcobranca_cep           ,
                                    $xcobranca_cidade        ,
                                    $xcobranca_estado        ,
                                    $xpedido_em_garantia     ,
                                    $xreembolso_peca_estoque ,
                                    $xcoleta_peca            ,
                                    $xpedido_faturado        ,
                                    $xdigita_os              ,
                                    $xcontrola_estoque       ,
                                    $xprestacao_servico      ,
                                    $xprestacao_servico_sem_mo,
                                    $xpedido_bonificacao     ,
                                    $admin_sap               ,";

                            //HD 755863
                            $sql .= ($login_fabrica == 15) ? "$xcontrole_estoque_novo, $xcontrole_estoque_manual, '$posto_vip',": " ";

                            //HD 672836 Gabriel
                            $sql .= ($login_fabrica == 1) ? "$admin_sap_especifico, '$categoria_posto', " : " " ;
                            $sql .= ($login_fabrica == 3) ? "$admin_sap_especifico, " : " " ;

                            $sql .= "
                                    $xbanco                  ,
                                    $xagencia                ,
                                    $xconta                  ,
                                    $xnomebanco              ,
                                    $xfavorecido_conta       ,
                                    $xatende_consumidor      ,
                                    $xcpf_conta              ,
                                    $xtipo_conta             ,
                                    $xacrescimo_tributario   ,
                                    $xobs_conta              ,
                                    $xpedido_via_distribuidor,
                                    '$item_aparencia'        ,
                                    current_timestamp        ,
                                    $login_admin             ,
                                    $xgarantia_antecipada    ,
                                    $xescritorio_regional    ,
                                    '$imprime_os'            ,
                                    '$qtde_os_item'          ,
                                    '$contribuinte_icms'     ";
    if($login_fabrica==2) { $sql .=",'$data_nomeacao'        "; } // hd 21496
    if($login_fabrica==11 || $login_fabrica == 20){ $sql .=",'$atendimento_lenoxx'   "; } // HD 110541
    if($login_fabrica==19){ $sql .=",$atende_comgas          "; }
    if($login_fabrica==45){ $sql .=",$xconta_operacao        "; } //HD 8190
    if($login_fabrica==92 || $login_fabrica ==30){ $sql .=", '$centro_custo',
                                      '$conta_contabil',
                                      '$local_entrega'"; } //HD 356653
    //HD 401553
    if($login_fabrica==42) { $sql .= ", '$posto_filial', '$entrega_tecnica' ";}
                            $sql .="
                                )";

                    }
                    $res = pg_query ($con,$sql);

                }

                if($login_fabrica == 1){
                    $sql = "SELECT excecao_mobra FROM tbl_excecao_mobra WHERE posto = $posto AND fabrica = $login_fabrica AND tx_administrativa NOTNULL";
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res) > 0){
                        $excecao_mobra = pg_fetch_result($res, 0, 'excecao_mobra');
                        $sql = "UPDATE tbl_excecao_mobra SET tx_administrativa = $xtaxa_administrativa WHERE excecao_mobra = $excecao_mobra AND posto = $posto AND fabrica = $login_fabrica";
                        $res = pg_query($con,$sql);
                    }else{
                        $sql = "INSERT INTO tbl_excecao_mobra(posto,fabrica,tx_administrativa) VALUES($posto,$login_fabrica,$xtaxa_administrativa)";
                        $res = pg_query($con,$sql);
                    }

                }
                if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
            }

            //HD 15526

            // grava posto_linha
            if (strlen($msg_erro) == 0){
                if (!in_array($login_fabrica,array(14))) {
                    $sql = "SELECT  tbl_linha.linha
                            FROM    tbl_linha
                            WHERE   ativo IS TRUE
                            AND     fabrica = $login_fabrica";
                    $res = pg_query ($con,$sql);
                    #var_dump($_POST);exit;
                    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                        $linha = pg_fetch_result ($res,$i,linha);

                        $atende             = $_POST ['atende_'             . $linha];
                        $tabela             = $_POST ['tabela_'             . $linha];
                        $desconto           = $_POST ['desconto_'           . $linha];
                        $tabela_posto       = $_POST ['tabela_posto_'       . $linha];
                        $tabela_bonificacao = $_POST ['tabela_bonificacao_' . $linha];
                        $distribuidor       = $_POST ['distribuidor_'       . $linha];
                        if ( $login_fabrica == 50 ){
                            $auditar_os     = $_POST ['auditar_os_'         . $linha];
                        }
                        if ($login_fabrica == 24){ #HD 383050 - Adicionando campo novo - SUGGAR
                            $divulga_consumidor_linha = $_POST['divulga_consumidor_linha_'.$linha];
                        }

                        $sql = "SELECT tbl_tabela.tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa IS TRUE";
                        $resX = pg_query($con,$sql);
                        if (pg_num_rows($resX) == 1) {
                            $tabela = pg_fetch_result ($resX,0,tabela);
                        }

                        if (strlen ($atende) == 0) {
                            $sql = "DELETE FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
                            $resX = pg_query ($con,$sql);
                        }else{
                            if (strlen ($tabela) == 0 and !in_array($login_fabrica,array(1,104))) $msg_erro = "Informe a tabela para esta linha";
                            if (strlen ($desconto) == 0) $desconto = "0";
                            if (strlen ($distribuidor) == 0) $distribuidor = "null";
                            if (strlen ($tabela_posto) == 0) $tabela_posto = "null";
                            if (strlen ($tabela_bonificacao) == 0) $tabela_bonificacao = "null";

                            if (strlen ($tabela) == 0){
                                    $tabela = 'null';
                                }

                            if ($login_fabrica == 24){ #HD 383050 - Adicionando campo novo - SUGGAR
                                if ( empty($divulga_consumidor_linha) ) {
                                    $divulga_consumidor_linha = "f";
                                }else{
                                    $divulga_consumidor_linha = "t";
                                }
                            }
                            if ($login_fabrica == 50){
                                if ( empty($auditar_os) ) {
                                    $auditar_os = "f";
                                }else{
                                    $auditar_os = "t";
                                }
                            }

                            if (strlen ($msg_erro) == 0) {
                                $sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
                                $resX = pg_query ($con,$sql);
                                if (pg_num_rows ($resX) > 0) {
                                    $sql = "UPDATE tbl_posto_linha SET
                                                tabela              = $tabela  ,
                                                desconto            = $desconto,
                                                tabela_posto        = $tabela_posto,
                                                tabela_bonificacao  = $tabela_bonificacao,
                                                distribuidor        = $distribuidor";
                                    if ( $login_fabrica == 24 ){ #HD 383050 - Adicionando campo novo - SUGGAR
                                        $sql .= ",divulgar_consumidor = '$divulga_consumidor_linha'";
                                    }
                                    if ( $login_fabrica == 50 ){
                                        $sql .= ",auditar_os = '$auditar_os'";
                                    }

                                    $sql .= "
                                            WHERE tbl_posto_linha.posto = $posto
                                            AND   tbl_posto_linha.linha = $linha";
                                   # echo nl2br($sql)."<br />";
                                    $resX = pg_query ($con,$sql);
                                }else{
                                    $sql = "INSERT INTO tbl_posto_linha (
                                                posto   ,
                                                linha   ,
                                                tabela  ,
                                                desconto,
                                                distribuidor,
                                                tabela_posto,
                                                tabela_bonificacao";
                                    if ( $login_fabrica == 24 ){ #HD 383050 - Adicionando campo novo - SUGGAR
                                        $sql .= "
                                                ,divulgar_consumidor
                                        ";
                                    }
                                    if ( $login_fabrica == 50 ){
                                        $sql .= "
                                                ,auditar_os
                                        ";
                                    }

                                    $sql .="
                                            ) VALUES (
                                                $posto   ,
                                                $linha   ,
                                                $tabela  ,
                                                $desconto,
                                                $distribuidor,
                                                $tabela_posto,
                                                $tabela_bonificacao";
                                    if ( $login_fabrica == 24 ){ #HD 383050 - Adicionando campo novo - SUGGAR
                                        $sql .= "
                                                ,'$divulga_consumidor_linha'
                                        ";
                                    }
                                    if ( $login_fabrica == 50 ){ #HD 383050 - Adicionando campo novo - SUGGAR
                                        $sql .= "
                                                ,'$auditar_os'
                                        ";
                                    }

                                    $sql .="
                                            )";
                                    $resX = pg_query ($con,$sql);
                                }
                            }
                                    // echo nl2br($sql)."<br />";echo $msg_erro;exit;
                        }
                    }
                    #exit;
                }else{
                    $sql = "SELECT * FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY tbl_familia.descricao;";
                    $res = pg_query ($con,$sql);

                    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                        $familia = pg_fetch_result ($res,$i,familia);

                        $atende       = $_POST ['atende_'       . $familia];
                        $tabela       = $_POST ['tabela_'       . $familia];
                        $desconto     = $_POST ['desconto_'     . $familia];
                        $distribuidor = $_POST ['distribuidor_' . $familia];

                        if (strlen ($atende) == 0) {
                            $sql = "DELETE FROM tbl_posto_linha
                                    WHERE  tbl_posto_linha.posto   = $posto
                                    AND    tbl_posto_linha.familia = $familia";
                            $resX = pg_query ($con,$sql);
                        }else{
                            if (strlen ($tabela) == 0)       $msg_erro = "Informa a tabela para esta familia";
                            if (strlen ($desconto) == 0)     $desconto = "0";
                            if (strlen ($distribuidor) == 0) $distribuidor = "null";

                            if (strlen ($msg_erro) == 0) {
                                if($login_fabrica == 20){
                                    $sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
                                            WHERE  tbl_tabela.fabrica = $login_fabrica
                                            AND    tbl_tabela.tabela  = $tabela
                                            AND    tbl_tabela.ativa IS TRUE";
                                    $resX = pg_query($con,$sql);

                                    if (pg_num_rows($resX) == 1) {
                                        $tabela = pg_fetch_result ($resX,0,tabela);
                                    }

                                    $sql = "UPDATE tbl_posto_fabrica SET
                                                tabela       = $tabela
                                            WHERE posto   = $posto
                                            AND   familia = $familia";
                                    $resX = pg_query ($con,$sql);

                                }
                                $sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND familia = $familia";
                                $resX = pg_query ($con,$sql);

                                if (pg_num_rows ($resX) > 0) {
                                    $sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
                                            WHERE  tbl_tabela.fabrica = $login_fabrica
                                            AND    tbl_tabela.tabela  = $tabela
                                            AND    tbl_tabela.ativa IS TRUE";
                                    $resX = pg_query($con,$sql);

                                    if (pg_num_rows($resX) == 1) {
                                        $tabela = pg_fetch_result ($resX,0,tabela);
                                    }

                                    $sql = "UPDATE tbl_posto_linha SET
                                                tabela       = $tabela  ,
                                                desconto     = $desconto,
                                                distribuidor = $distribuidor
                                            WHERE tbl_posto_linha.posto   = $posto
                                            AND   tbl_posto_linha.familia = $familia";
                                    $resX = pg_query ($con,$sql);

                                }else{
                                    $sql = "INSERT INTO tbl_posto_linha (
                                                posto   ,
                                                familia ,
                                                tabela  ,
                                                desconto,
                                                distribuidor
                                            ) VALUES (
                                                $posto   ,
                                                $familia ,
                                                $tabela  ,
                                                $desconto,
                                                $distribuidor
                                            )";
                                    $resX = pg_query ($con,$sql);
                                }
                            }
                        }
                    }
                }
            }
        }

        if($login_fabrica == 20 AND strlen($msg_erro)==0){
            $tabela = $_POST["tabela_unica"];
            if(strlen($tabela) > 0){
                $sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
                        WHERE  tbl_tabela.fabrica = $login_fabrica
                        AND    tbl_tabela.tabela  = $tabela
                        AND    tbl_tabela.ativa IS TRUE";
                $resX = pg_query($con,$sql);
                if (pg_num_rows($resX) == 1) $tabela = pg_fetch_result ($resX,0,tabela);

                $sql = "UPDATE tbl_posto_fabrica SET
                            tabela     = $tabela
                        WHERE posto    = $posto
                        AND   fabrica  = $login_fabrica";
                $resX = pg_query ($con,$sql);
            }

            $sql= " SELECT  CASE WHEN tbl_posto_fabrica.codigo_posto             <> tmp_posto_$login_admin.codigo_posto              THEN tbl_posto_fabrica.codigo_posto            ELSE null END AS codigo_posto_alterado               ,
                            CASE WHEN tbl_posto_fabrica.credenciamento           <> tmp_posto_$login_admin.credenciamento            THEN tbl_posto_fabrica.credenciamento          ELSE null END AS credenciamento_alterado             ,
                            CASE WHEN tbl_posto_fabrica.senha                    <> tmp_posto_$login_admin.senha                     THEN tbl_posto_fabrica.senha                   ELSE null END AS senha_alterado                      ,
                            CASE WHEN tbl_posto_fabrica.desconto                 <> tmp_posto_$login_admin.desconto                  THEN tbl_posto_fabrica.desconto                ELSE null END AS desconto_alterado                   ,
                            CASE WHEN tbl_posto_fabrica.desconto_acessorio       <> tmp_posto_$login_admin.desconto_acessorio        THEN tbl_posto_fabrica.desconto_acessorio      ELSE null END AS desconto_acessorio_alterado         ,
                            CASE WHEN tbl_posto_fabrica.custo_administrativo     <> tmp_posto_$login_admin.custo_administrativo      THEN tbl_posto_fabrica.custo_administrativo    ELSE null END AS custo_administrativo_alterado       ,
                            CASE WHEN tbl_posto_fabrica.imposto_al               <> tmp_posto_$login_admin.imposto_al                THEN tbl_posto_fabrica.imposto_al              ELSE null END AS imposto_al_alterado                 ,
                            CASE WHEN tbl_posto_fabrica.tipo_posto               <> tmp_posto_$login_admin.tipo_posto                THEN tbl_posto_fabrica.tipo_posto              ELSE null END AS tipo_posto_alterado                 ,
                            CASE WHEN tbl_posto_fabrica.obs                      <> tmp_posto_$login_admin.obs                       THEN tbl_posto_fabrica.obs                     ELSE null END AS obs_alterado                        ,
                            CASE WHEN tbl_posto_fabrica.contato_endereco         <> tmp_posto_$login_admin.contato_endereco          THEN tbl_posto_fabrica.contato_endereco        ELSE null END AS contato_endereco_alterado           ,
                            CASE WHEN tbl_posto_fabrica.contato_numero           <> tmp_posto_$login_admin.contato_numero            THEN tbl_posto_fabrica.contato_numero          ELSE null END AS contato_numero_alterado             ,
                            CASE WHEN tbl_posto_fabrica.contato_complemento      <> tmp_posto_$login_admin.contato_complemento       THEN tbl_posto_fabrica.contato_complemento     ELSE null END AS contato_complemento_alterado        ,
                            CASE WHEN tbl_posto_fabrica.contato_bairro           <> tmp_posto_$login_admin.contato_bairro            THEN tbl_posto_fabrica.contato_bairro          ELSE null END AS contato_bairro_alterado             ,
                            CASE WHEN tbl_posto_fabrica.contato_cidade           <> tmp_posto_$login_admin.contato_cidade            THEN tbl_posto_fabrica.contato_cidade          ELSE null END AS contato_cidade_alterado             ,
                            CASE WHEN tbl_posto_fabrica.contato_cep              <> tmp_posto_$login_admin.contato_cep               THEN tbl_posto_fabrica.contato_cep             ELSE null END AS contato_cep_alterado                ,
                            CASE WHEN tbl_posto_fabrica.contato_estado           <> tmp_posto_$login_admin.contato_estado            THEN tbl_posto_fabrica.contato_estado          ELSE null END AS contato_estado_alterado             ,
                            CASE WHEN tbl_posto_fabrica.contato_fone_comercial   <> tmp_posto_$login_admin.contato_fone_comercial    THEN tbl_posto_fabrica.contato_fone_comercial  ELSE null END AS contato_fone_comercial_alterado     ,
                            CASE WHEN tbl_posto_fabrica.contato_fax              <> tmp_posto_$login_admin.contato_fax               THEN tbl_posto_fabrica.contato_fax             ELSE null END AS contato_fax_alterado                ,
                            CASE WHEN tbl_posto_fabrica.nome_fantasia            <> tmp_posto_$login_admin.nome_fantasia             THEN tbl_posto_fabrica.nome_fantasia           ELSE null END AS nome_fantasia_alterado              ,
                            CASE WHEN tbl_posto_fabrica.contato_email            <> tmp_posto_$login_admin.contato_email             THEN tbl_posto_fabrica.contato_email           ELSE null END AS contato_email_alterado              ,
                            CASE WHEN tbl_posto_fabrica.transportadora           <> tmp_posto_$login_admin.transportadora            THEN tbl_posto_fabrica.transportadora          ELSE null END AS transportadora_alterado             ,
                            CASE WHEN tbl_posto_fabrica.cobranca_endereco        <> tmp_posto_$login_admin.cobranca_endereco         THEN tbl_posto_fabrica.cobranca_endereco       ELSE null END AS cobranca_endereco_alterado          ,
                            CASE WHEN tbl_posto_fabrica.cobranca_numero          <> tmp_posto_$login_admin.cobranca_numero           THEN tbl_posto_fabrica.cobranca_numero         ELSE null END AS cobranca_numero_alterado            ,
                            CASE WHEN tbl_posto_fabrica.cobranca_complemento     <> tmp_posto_$login_admin.cobranca_complemento      THEN tbl_posto_fabrica.cobranca_complemento    ELSE null END AS cobranca_complemento_alterado       ,
                            CASE WHEN tbl_posto_fabrica.cobranca_bairro          <> tmp_posto_$login_admin.cobranca_bairro           THEN tbl_posto_fabrica.cobranca_bairro         ELSE null END AS cobranca_bairro_alterado            ,
                            CASE WHEN tbl_posto_fabrica.cobranca_cep             <> tmp_posto_$login_admin.cobranca_cep              THEN tbl_posto_fabrica.cobranca_cep            ELSE null END AS cobranca_cep_alterado               ,
                            CASE WHEN tbl_posto_fabrica.cobranca_cidade          <> tmp_posto_$login_admin.cobranca_cidade           THEN tbl_posto_fabrica.cobranca_cidade         ELSE null END AS cobranca_cidade_alterado            ,
                            CASE WHEN tbl_posto_fabrica.cobranca_estado          <> tmp_posto_$login_admin.cobranca_estado           THEN tbl_posto_fabrica.cobranca_estado         ELSE null END AS cobranca_estado_alterado            ,
                            CASE WHEN tbl_posto_fabrica.pedido_em_garantia       <> tmp_posto_$login_admin.pedido_em_garantia        THEN tbl_posto_fabrica.pedido_em_garantia      ELSE null END AS pedido_em_garantia_alterado         ,
                            CASE WHEN tbl_posto_fabrica.pedido_faturado          <> tmp_posto_$login_admin.pedido_faturado           THEN tbl_posto_fabrica.pedido_faturado         ELSE null END AS pedido_faturado_alterado            ,
                            CASE WHEN tbl_posto_fabrica.digita_os                <> tmp_posto_$login_admin.digita_os                 THEN tbl_posto_fabrica.digita_os               ELSE null END AS digita_os_alterado                  ,
                            CASE WHEN tbl_posto_fabrica.prestacao_servico        <> tmp_posto_$login_admin.prestacao_servico         THEN tbl_posto_fabrica.prestacao_servico       ELSE null END AS prestacao_servico_alterado          ,
                            CASE WHEN tbl_posto_fabrica.banco                    <> tmp_posto_$login_admin.banco                     THEN tbl_posto_fabrica.banco                   ELSE null END AS banco_alterado                      ,
                            CASE WHEN tbl_posto_fabrica.agencia                  <> tmp_posto_$login_admin.agencia                   THEN tbl_posto_fabrica.agencia                 ELSE null END AS agencia_alterado                    ,
                            CASE WHEN tbl_posto_fabrica.conta                    <> tmp_posto_$login_admin.conta                     THEN tbl_posto_fabrica.conta                   ELSE null END AS conta_alterado                      ,
                            CASE WHEN tbl_posto_fabrica.nomebanco                <> tmp_posto_$login_admin.nomebanco                 THEN tbl_posto_fabrica.nomebanco               ELSE null END AS nomebanco_alterado                  ,
                            CASE WHEN tbl_posto_fabrica.favorecido_conta         <> tmp_posto_$login_admin.favorecido_conta          THEN tbl_posto_fabrica.favorecido_conta        ELSE null END AS favorecido_conta_alterado           ,
                            CASE WHEN tbl_posto_fabrica.cpf_conta                <> tmp_posto_$login_admin.cpf_conta                 THEN tbl_posto_fabrica.cpf_conta               ELSE null END AS cpf_conta_alterado                  ,
                            CASE WHEN tbl_posto_fabrica.tipo_conta               <> tmp_posto_$login_admin.tipo_conta                THEN tbl_posto_fabrica.tipo_conta              ELSE null END AS tipo_conta_alterado                 ,
                            CASE WHEN tbl_posto_fabrica.obs_conta                <> tmp_posto_$login_admin.obs_conta                 THEN tbl_posto_fabrica.obs_conta               ELSE null END AS obs_conta_alterado                  ,
                            CASE WHEN tbl_posto_fabrica.pedido_via_distribuidor  <> tmp_posto_$login_admin.pedido_via_distribuidor   THEN tbl_posto_fabrica.pedido_via_distribuidor ELSE null END AS pedido_via_distribuidor_alterado    ,
                            CASE WHEN tbl_posto_fabrica.item_aparencia           <> tmp_posto_$login_admin.item_aparencia            THEN tbl_posto_fabrica.item_aparencia          ELSE null END AS item_aparencia_alterado             ,
                            CASE WHEN tbl_posto_fabrica.garantia_antecipada      <> tmp_posto_$login_admin.garantia_antecipada       THEN tbl_posto_fabrica.garantia_antecipada     ELSE null END AS garantia_antecipada_alterado        ,
                            CASE WHEN tbl_posto_fabrica.escritorio_regional      <> tmp_posto_$login_admin.escritorio_regional       THEN tbl_posto_fabrica.escritorio_regional     ELSE null END AS escritorio_regional_alterado        ,
                            CASE WHEN tbl_posto_fabrica.tabela                   <> tmp_posto_$login_admin.tabela                    THEN tbl_posto_fabrica.tabela                  ELSE null END AS tabela_alterado                     ,
                            CASE WHEN tbl_posto.nome                             <> tmp_posto_$login_admin.nome                      THEN tbl_posto.nome                            ELSE null END AS nome_alterado                       ,
                            CASE WHEN tbl_posto.cnpj                             <> tmp_posto_$login_admin.cnpj                      THEN tbl_posto.cnpj                            ELSE null END AS cnpj_alterado                       ,
                            CASE WHEN tbl_posto.ie                               <> tmp_posto_$login_admin.ie                        THEN tbl_posto.ie                              ELSE null END AS ie_alterado                         ,
                            /* HD 52864 20/11/2008
                            CASE WHEN tbl_posto.fone                             <> tmp_posto_$login_admin.fone                      THEN tbl_posto.fone                            ELSE null END AS fone_alterado                       ,
                            CASE WHEN tbl_posto.fax                              <> tmp_posto_$login_admin.fax                       THEN tbl_posto.fax                             ELSE null END AS fax_alterado                        ,
                            */
                            CASE WHEN tbl_posto.capital_interior                 <> tmp_posto_$login_admin.capital_interior          THEN tbl_posto.capital_interior                ELSE null END AS capital_interior_alterado           ,
                            CASE WHEN tbl_posto.contato                          <> tmp_posto_$login_admin.contato                   THEN tbl_posto.contato                         ELSE null END AS contato_alterado                    ,
                            tmp_posto_$login_admin.*
                    FROM   tbl_posto_fabrica
                    JOIN   tbl_posto USING (posto)
                    JOIN   tmp_posto_$login_admin USING (posto)
                    WHERE  tbl_posto_fabrica.fabrica = $login_fabrica
                    AND    tbl_posto_fabrica.posto   = $posto";
            $res=pg_query($con,$sql);

            $remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
            $destinatario = "Robson.Gastao@br.bosch.com";
            $assunto      = "Alteração no posto $xcodigo_posto";

            if(strlen(pg_fetch_result($res,0,codigo_posto_alterado           ))        >0 ) $mensagem ="Foi alterado o código do posto, de -" .pg_fetch_result($res,0,codigo_posto). "para - ".pg_fetch_result($res,0,codigo_posto_alterado) ."<br>";
            if(strlen(pg_fetch_result($res,0,credenciamento_alterado         ))        >0 ) $mensagem ="Foi alterado o credenciamento, de -" .pg_fetch_result($res,0,credenciamento). "para - ".pg_fetch_result($res,0,credenciamento_alterado) ."<br>";
            if(strlen(pg_fetch_result($res,0,senha_alterado                  ))        >0 ) $mensagem.="Foi alterada a Senha, de - " .pg_fetch_result($res,0,senha). " para - " .pg_fetch_result($res,0,senha_alterado) . "<br>";
            if(strlen(pg_fetch_result($res,0,desconto_alterado               ))        >0 ) $mensagem.="Foi alterado o Desconto, de - " .pg_fetch_result($res,0,desconto               ). " para - " .pg_fetch_result($res,0,desconto_alterado               ) . "<br>";
            if(strlen(pg_fetch_result($res,0,desconto_acessorio_alterado     ))        >0 ) $mensagem.="Foi alterada o Desconto Acessório, de - " .pg_fetch_result($res,0,desconto_acessorio     ). " para - " .pg_fetch_result($res,0,desconto_acessorio_alterado     ) . "<br>";
            if(strlen(pg_fetch_result($res,0,custo_administrativo_alterado   ))        >0 ) $mensagem.="Foi alterada o Custo Administrativo, de - " .pg_fetch_result($res,0,custo_administrativo   ). " para - " .pg_fetch_result($res,0,custo_administrativo_alterado   ) . "<br>";
            if(strlen(pg_fetch_result($res,0,imposto_al_alterado             ))        >0 ) $mensagem.="Foi alterada o Imposto IVA, de - " .pg_fetch_result($res,0,imposto_al             ). " para - " .pg_fetch_result($res,0,imposto_al_alterado             ) . "<br>";
            if(strlen(pg_fetch_result($res,0,tipo_posto_alterado             ))        >0 ) $mensagem.="Foi alterada o Tipo do posto, de - " .pg_fetch_result($res,0,tipo_posto             ). " para - " .pg_fetch_result($res,0,tipo_posto_alterado             ) . "<br>";
            if(strlen(pg_fetch_result($res,0,obs_alterado                    ))        >0 ) $mensagem.="Foi alterada a observação, de - " .pg_fetch_result($res,0,obs                    ). " para - " .pg_fetch_result($res,0,obs_alterado                    ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_endereco_alterado       ))        >0 ) $mensagem.="Foi alterada o endereço, de - " .pg_fetch_result($res,0,contato_endereco       ). " para - " .pg_fetch_result($res,0,contato_endereco_alterado       ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_numero_alterado         ))        >0 ) $mensagem.="Foi alterada o número, de - " .pg_fetch_result($res,0,contato_numero         ). " para - " .pg_fetch_result($res,0,contato_numero_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_complemento_alterado    ))        >0 ) $mensagem.="Foi alterada o complemento, de - " .pg_fetch_result($res,0,contato_complemento    ). " para - " .pg_fetch_result($res,0,contato_complemento_alterado    ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_bairro_alterado         ))        >0 ) $mensagem.="Foi alterada o bairro, de - " .pg_fetch_result($res,0,contato_bairro         ). " para - " .pg_fetch_result($res,0,contato_bairro_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_cidade_alterado         ))        >0 ) $mensagem.="Foi alterada a cidade, de - " .pg_fetch_result($res,0,contato_cidade         ). " para - " .pg_fetch_result($res,0,contato_cidade_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_cep_alterado            ))        >0 ) $mensagem.="Foi alterada o cep, de - " .pg_fetch_result($res,0,contato_cep            ). " para - " .pg_fetch_result($res,0,contato_cep_alterado            ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_estado_alterado         ))        >0 ) $mensagem.="Foi alterada o estado, de - " .pg_fetch_result($res,0,contato_estado         ). " para - " .pg_fetch_result($res,0,contato_estado_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,transportadora_alterado         ))        >0 ) $mensagem.="Foi alterada a transportadora, de - " .pg_fetch_result($res,0,transportadora         ). " para - " .pg_fetch_result($res,0,transportadora_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_endereco_alterado      ))        >0 ) $mensagem.="Foi alterada o endereço da cobrança, de - " .pg_fetch_result($res,0,cobranca_endereco      ). " para - " .pg_fetch_result($res,0,cobranca_endereco_alterado      ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_numero_alterado        ))        >0 ) $mensagem.="Foi alterada o número da cobrança, de - " .pg_fetch_result($res,0,cobranca_numero        ). " para - " .pg_fetch_result($res,0,cobranca_numero_alterado        ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_complemento_alterado   ))        >0 ) $mensagem.="Foi alterada o complemento da cobrança, de - " .pg_fetch_result($res,0,cobranca_complemento   ). " para - " .pg_fetch_result($res,0,cobranca_complemento_alterado   ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_bairro_alterado        ))        >0 ) $mensagem.="Foi alterada o bairro da cobrança, de - " .pg_fetch_result($res,0,cobranca_bairro        ). " para - " .pg_fetch_result($res,0,cobranca_bairro_alterado        ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_cep_alterado           ))        >0 ) $mensagem.="Foi alterada o cep da cobrança, de - " .pg_fetch_result($res,0,cobranca_cep           ). " para - " .pg_fetch_result($res,0,cobranca_cep_alterado           ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_cidade_alterado        ))        >0 ) $mensagem.="Foi alterada a cidade da cobrança, de - " .pg_fetch_result($res,0,cobranca_cidade        ). " para - " .pg_fetch_result($res,0,cobranca_cidade_alterado        ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_estado_alterado        ))        >0 ) $mensagem.="Foi alterada o estado da cobrança, de - " .pg_fetch_result($res,0,cobranca_estado        ). " para - " .pg_fetch_result($res,0,cobranca_estado_alterado        ) . "<br>";
            if(strlen(pg_fetch_result($res,0,agencia_alterado                ))        >0 ) $mensagem.="Foi alterada a agencia, de - " .pg_fetch_result($res,0,agencia                ). " para - " .pg_fetch_result($res,0,agencia_alterado                ) . "<br>";
            if(strlen(pg_fetch_result($res,0,conta_alterado                  ))        >0 ) $mensagem.="Foi alterada a conta, de - " .pg_fetch_result($res,0,conta                  ). " para - " .pg_fetch_result($res,0,conta_alterado                  ) . "<br>";
            if(strlen(pg_fetch_result($res,0,nomebanco_alterado              ))        >0 ) $mensagem.="Foi alterada o banco , de - " .pg_fetch_result($res,0,nomebanco              ). " para - " .pg_fetch_result($res,0,nomebanco_alterado              ) . "<br>";
            if(strlen(pg_fetch_result($res,0,favorecido_conta_alterado       ))        >0 ) $mensagem.="Foi alterada o Nome favorecido, de - " .pg_fetch_result($res,0,favorecido_conta       ). " para - " .pg_fetch_result($res,0,favorecido_conta_alterado       ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cpf_conta_alterado              ))        >0 ) $mensagem.="Foi alterada o CPF do favorecido, de - " .pg_fetch_result($res,0,cpf_conta              ). " para - " .pg_fetch_result($res,0,cpf_conta_alterado              ) . "<br>";
            if(strlen(pg_fetch_result($res,0,tipo_conta_alterado             ))        >0 ) $mensagem.="Foi alterada o tipo de conta, de - " .pg_fetch_result($res,0,tipo_conta             ). " para - " .pg_fetch_result($res,0,tipo_conta_alterado             ) . "<br>";
            if(strlen(pg_fetch_result($res,0,obs_conta_alterado              ))        >0 ) $mensagem.="Foi alterada a observação da conta, de - " .pg_fetch_result($res,0,obs_conta              ). " para - " .pg_fetch_result($res,0,obs_conta_alterado              ) . "<br>";
            if(strlen(pg_fetch_result($res,0,escritorio_regional_alterado    ))        >0 ) $mensagem.="Foi alterada O escritório regional, de - " .pg_fetch_result($res,0,escritorio_regional    ). " para - " .pg_fetch_result($res,0,escritorio_regional_alterado    ) . "<br>";
            if(strlen(pg_fetch_result($res,0,tabela_alterado                 ))        >0 ) $mensagem.="Foi alterada a tabela, de - " .pg_fetch_result($res,0,tabela                 ). " para - " .pg_fetch_result($res,0,tabela_alterado                 ) . "<br>";
            if(strlen(pg_fetch_result($res,0,nome_alterado                   ))        >0 ) $mensagem.="Foi alterada o Nome do posto, de - " .pg_fetch_result($res,0,nome                   ). " para - " .pg_fetch_result($res,0,nome_alterado                   ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cnpj_alterado                   ))        >0 ) $mensagem.="Foi alterada o CNPJ do posto, de - " .pg_fetch_result($res,0,cnpj                   ). " para - " .pg_fetch_result($res,0,cnpj_alterado                   ) . "<br>";
            if(strlen(pg_fetch_result($res,0,ie_alterado                     ))        >0 ) $mensagem.="Foi alterada o Inscrição Estadual, de - " .pg_fetch_result($res,0,ie                     ). " para - " .pg_fetch_result($res,0,ie_alterado                     ) . "<br>";
            if(strlen(pg_fetch_result($res,0,capital_interior_alterado       ))        >0 ) $mensagem.="Foi alterada o Capital/Interior, de - " .pg_fetch_result($res,0,capital_interior       ). " para - " .pg_fetch_result($res,0,capital_interior_alterado       ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_alterado                ))        >0 ) $mensagem.="Foi alterada o contato, de - " .pg_fetch_result($res,0,contato                ). " para - " .pg_fetch_result($res,0,contato_alterado                ) . "<br>";
            if(pg_fetch_result($res,0,pedido_em_garantia_alterado)          =='t') $mensagem.="Este posto não fazia PEDIDO EM GARANTIA (Manual), foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,pedido_em_garantia_alterado)      =='f')                                                                       $mensagem.="Este posto fazia PEDIDO EM GARANTIA (Manual), foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,pedido_faturado_alterado)             =='t') $mensagem.="Este posto não fazia PEDIDO FATURADO (Manual), foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,pedido_faturado_alterado)         =='f')                                                                       $mensagem.="Este posto fazia DIGITA OS, foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,digita_os_alterado)                   =='t') $mensagem.="Este posto não fazia DIGITA OS, foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,digita_os_alterado)               =='f')                                                                      $mensagem.="Este posto fazia PEDIDO FATURADO (Manual), foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,prestacao_servico_alterado)           =='t') $mensagem.="Este posto não fazia PRESTAÇÃO DE SERVIÇO, foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,prestacao_servico_alterado)       =='f')                                                                       $mensagem.="Este posto fazia PRESTAÇÃO DE SERVIÇO, foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,pedido_via_distribuidor_alterado)     =='t') $mensagem.="Este posto não fazia PEDIDO VIA DISTRIBUIDOR, foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,pedido_via_distribuidor_alterado) =='f')                                                                       $mensagem.="Este posto fazia PEDIDO VIA DISTRIBUIDOR, foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,item_aparencia_alterado)              =='t') $mensagem.="Este posto não podia pedir peças com item de aparência, foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,item_aparencia_alterado)          =='f')                                                                       $mensagem.="Este posto podia pedir peças com item de aparência, foi alerado para não poder fazer<br>";

            $headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
            if(strlen(trim($mensagem))>0) {
                $conteudo="Foi alterado os dados do posto $xcodigo_posto:<br>".$mensagem;
                if(mail($destinatario, utf8_encode($assunto), utf8_encode($conteudo), $headers)){
                    #echo "enviado com sucesso"; exit;
                };
            }
        }

        if (strlen($msg_erro) == 0 && $login_fabrica == 1) {
            $sql =  "SELECT DISTINCT tbl_condicao.condicao , tbl_posto_condicao.visivel
                    FROM tbl_condicao
                    JOIN tbl_posto_condicao USING (condicao)
                    JOIN tbl_posto_fabrica USING (posto)
                    WHERE tbl_condicao.fabrica = $login_fabrica
                    AND   tbl_condicao.tabela  = 31
                    AND   tbl_condicao.visivel IS TRUE
                    AND   tbl_posto_fabrica.tipo_posto = $xtipo_posto
                    ORDER BY tbl_condicao.condicao ASC";
            $res1 = pg_query($con,$sql);
            for ($i = 0 ; $i < pg_num_rows($res1) ; $i++) {
                $condicao = pg_fetch_result($res1,$i,condicao);
                $visivel  = pg_fetch_result($res1,$i,visivel);

                $tabela = ($condicao == 62) ? 47 : 31;

                $sql =  "SELECT condicao
                        FROM tbl_posto_condicao
                        WHERE posto  = $posto
                        AND condicao = $condicao;";
                $res2 = pg_query($con,$sql);
                if (pg_num_rows($res2) > 0) {
                    $sql =  "UPDATE tbl_posto_condicao SET
                                tabela  = $tabela,
                                visivel = '$visivel'
                            WHERE tbl_posto_condicao.condicao = $condicao
                            AND   tbl_posto_condicao.posto    = $posto;";
                }else{
                    $sql = "INSERT INTO tbl_posto_condicao (
                                posto    ,
                                condicao ,
                                tabela   ,
                                visivel
                            ) VALUES (
                                $posto    ,
                                $condicao ,
                                $tabela   ,
                                '$visivel'
                            );";
                }
                $res = @pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);
                if (strlen($msg_erro) > 0) {
                    $msg_erro = " Não foi possível cadastrar a condição de pagamento p/ este posto. ";
                    break;
                }
            }

            if($login_fabrica==1){
                $sql = "UPDATE tbl_posto_condicao SET
                                visivel = $xpedido_em_garantia_finalidades_diversas
                        WHERE posto  = $posto
                        AND condicao = 62";
                $res = @pg_query($con,$sql);
            }

            /* HD 23738 */
            if($login_fabrica==1){
                $sql = "SELECT  tbl_posto_fabrica.escolhe_condicao,
                                tbl_posto_fabrica.condicao_escolhida,
                                tbl_posto.nome,
                                tbl_posto_fabrica.contato_email
                        FROM tbl_posto_fabrica
                        JOIN tbl_posto USING(posto)
                        WHERE tbl_posto_fabrica.posto   = $posto
                        AND   tbl_posto_fabrica.fabrica = $login_fabrica";
                $res = @pg_query($con,$sql);
                if (pg_num_rows($res) > 0) {
                    $escolhe_condicao_ant = pg_fetch_result($res,0,escolhe_condicao);
                    $condicao_escolhida   = pg_fetch_result($res,0,condicao_escolhida);
                    $posto_nome           = pg_fetch_result($res,0,nome);
                    $posto_email          = pg_fetch_result($res,0,contato_email);

                    $sql = "UPDATE tbl_posto_fabrica SET
                                    escolhe_condicao = $xescolhe_condicao
                            WHERE posto   = $posto
                            AND   fabrica = $login_fabrica";
                    $res = @pg_query($con,$sql);

                    if ($condicao_escolhida == '' AND $escolhe_condicao_ant <> 't'){
                        if ($escolhe_condicao == 't'){

                            /* Dispara um email para o PA */
                            $assunto = "Definir condição de pagamento: Posto $codigo_posto";
                            $mensagem  = "<b>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM </b>****.<BR><BR><BR>";
                            $mensagem .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                            $mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
                            $mensagem .= "<b>Prezado Cliente,</b> ";
                            $mensagem .= "<br><br>\n";
                            $mensagem .= "Informamos que o seu posto foi nomeado para adquirir peças direto com a fábrica.";
                            $mensagem .= "<br>\n";
                            $mensagem .= "<br>\n";
                            $mensagem .= "Acesse a tela de digitação no site através do caminho <b>PEDIDOS/CADASTRO DE PEDIDOS DE PEÇAS</b> para ler o procedimento sobre definição da condição de pagamento.";
                            $mensagem .= "<br><br>\n";
                            $mensagem .= "Obrigada.";
                            $mensagem .= "<br><br>\n";
                            $mensagem .= "Black & Decker do Brasil.";
                            $mensagem .= "</font>";

                            $cabecalho .= "MIME-Version: 1.0\n";
                            $cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
                            $sqlemail = "SELECT email
                                    FROM tbl_admin
                                    WHERE fabrica = $login_fabrica
                                    and tbl_admin.admin = $login_admin";
                            $resemail = pg_query($con,$sqlemail);
                            $email_admin = pg_fetch_result($resemail,0,email);

                            $cabecalho .= "From: Black & Decker <$email_admin>\n";
                            $cabecalho .= "To: $posto_nome <$posto_email>, Blackedecker <$email_admin>\n";

                            $cabecalho .= "Subject: $assunto\n";
                            $cabecalho .= "Return-Path: Suporte <helpdesk@telecontrol.com.br>\n";
                            $cabecalho .= "X-Priority: 1\n";
                            $cabecalho .= "X-MSMail-Priority: High\n";
                            $cabecalho .= "X-Mailer: PHP/" . phpversion();

                            if ( !mail("", utf8_encode($assunto), utf8_encode($mensagem), $cabecalho) ) {
                                $msg_erro = " Não foi possível enviar o email. Tente novamente. ";
                            }
                        }
                    }

                    if ($escolhe_condicao == 't' AND $condicao_escolhida == 'f' AND strlen($condicao_liberada)>0){
                        $sql = "UPDATE tbl_posto_fabrica SET
                                        condicao_escolhida = 't',
                                        escolhe_condicao   = 'f'
                                WHERE posto   = $posto
                                AND   fabrica = $login_fabrica";
                        $res = @pg_query($con,$sql);

                        /* Dispara um email para o PA */
                        $assunto = "Definir condição de pagamento: Posto $codigo_posto";
                        $mensagem  = "<b>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM </b>****.<BR><BR><BR>";
                        $mensagem .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                        $mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
                        $mensagem .= "<b>Prezado Cliente,</b> ";
                        $mensagem .= "<br><br><br>\n";
                        $mensagem .= "Informamos que tela de digitação de pedido foi liberada com a condição de pagamento que você escolheu.";
                        $mensagem .= "<br><br>\n";
                        $mensagem .= "Acesse a tela de digitação no site através do caminho <b>PEDIDOS/CADASTRO DE PEDIDOS DE PEÇAS</b>.";
                        $mensagem .= "<br><br>\n";
                        $mensagem .= "Obrigada.";
                        $mensagem .= "<br><br><br>\n";
                        $mensagem .= "Black & Decker do Brasil.";
                        $mensagem .= "</font>";

                        $sqlemail = "SELECT email
                                    FROM tbl_admin
                                    WHERE fabrica = $login_fabrica
                                    and tbl_admin.admin = $login_admin";
                        $resemail = pg_query($con,$sqlemail);
                        $email_admin = pg_fetch_result($resemail,0,email);
                        $cabecalho .= "MIME-Version: 1.0\n";
                        $cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
                        $cabecalho .= "From: Black & Decker <$email_admin>\n";
                        $cabecalho .= "To: $posto_nome <$posto_email>, Blackedecker <$email_admin>\n";

                        #$cabecalho .= "To: Rúbia Fernandes <rfernandes@blackedecker.com.br> \n";
                        $cabecalho .= "Subject: $assunto\n";
                        $cabecalho .= "Return-Path: Suporte <helpdesk@telecontrol.com.br>\n";
                        $cabecalho .= "X-Priority: 1\n";
                        $cabecalho .= "X-MSMail-Priority: High\n";
                        $cabecalho .= "X-Mailer: PHP/" . phpversion();

                        if ( !mail("", utf8_encode($assunto), utf8_encode($mensagem), $cabecalho) ) {
                            $msg_erro = " Não foi possível enviar o email. Tente novamente. ";
                        }
                    }
                }
            }
        }

        //hd 49412 - o código abaixo se repete para cada foto, pois o admin pode cadastrar fotos com extensões diferentes.
        if(strlen($msg_erro)==0 and $login_fabrica==50){
            if (isset($_FILES['foto_posto'])) {
                $Destino  = "/www/assist/www/foto_posto/";
                $DestinoT = "/www/assist/www/foto_posto/";

                $Fotos    = $_FILES['foto_posto'];
                $Nome     = $Fotos['name'];
                $Tamanho  = $Fotos['size'];
                $Tipo     = $Fotos['type'];
                $Tmpname  = $Fotos['tmp_name'];
                $Extensao = $Nome;

                if(strlen($Extensao)>0){
                    if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){
                        if(!is_uploaded_file($Tmpname)){
                            $msg_erro .= "Não foi possível efetuar o upload.";
                        }

                        $tmp = explode(".",$Nome);
                        $ext = $tmp[count($tmp)-1];

                        if (strlen($Extensao)==0){
                            $ext = $Extensao;
                        }

                        $ext = strtolower($ext);

                        $sql = "SELECT posto_fabrica_foto
                                FROM tbl_posto_fabrica_foto
                                WHERE posto = $posto
                                AND fabrica = $login_fabrica";
                        $res = pg_query ($con,$sql);

                        if (pg_num_rows($res) == 0) {
                            #insere um registro
                            $sql = "INSERT INTO tbl_posto_fabrica_foto
                                        (posto, fabrica)
                                        VALUES ($posto,$login_fabrica)";

                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                            $sql                = "SELECT CURRVAL ('seq_posto_fabrica_foto')";
                            $res                = pg_query ($con,$sql);
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        } else {
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        }

                        $nome_foto  = "$posto_fabrica_foto"."_posto.$ext";
                        $nome_thumb = "$posto_fabrica_foto"."_posto_thumb.$ext";

                        $Caminho_foto  = "../foto_posto/$nome_foto";
                        $Caminho_thumb = "../foto_posto/$nome_thumb";

                        $descricao_foto_posto = str_replace("\'","",$_POST['descricao_foto_posto']);
                        $descricao_foto_posto = str_replace("\"","",$descricao_foto_posto);

                        #Atualiza o nome do arquivo na tabela
                        if (strlen($posto_fabrica_foto)>0){
                            $sql = "UPDATE tbl_posto_fabrica_foto SET
                                        foto_posto           = '$Caminho_foto',
                                        foto_posto_thumb     = '$Caminho_thumb',
                                        foto_posto_Descricao = '$descricao_foto_posto'
                                    WHERE posto_fabrica_foto = $posto_fabrica_foto";
                            $res = pg_query ($con,$sql);
                        }

                        reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
                        reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
                    }else{
                        $msg_erro .= "O formato da foto $Nome não é permitido!<br>";
                    }
                }
            }

            if (isset($_FILES['foto_contato1'])) {
                $Destino  = "/www/assist/www/foto_posto/";
                $DestinoT = "/www/assist/www/foto_posto/";

                $Fotos    = $_FILES['foto_contato1'];
                $Nome     = $Fotos['name'];
                $Tamanho  = $Fotos['size'];
                $Tipo     = $Fotos['type'];
                $Tmpname  = $Fotos['tmp_name'];
                $Extensao = $Nome;

                if(strlen($Extensao)>0){
                    if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){
                        if(!is_uploaded_file($Tmpname)){
                            $msg_erro .= "Não foi possível efetuar o upload.";
                        }

                        $tmp = explode(".",$Nome);
                        $ext = $tmp[count($tmp)-1];

                        if (strlen($Extensao)==0){
                            $ext = $Extensao;
                        }

                        $ext = strtolower($ext);

                        $sql = "SELECT posto_fabrica_foto
                                FROM tbl_posto_fabrica_foto
                                WHERE posto = $posto
                                AND fabrica = $login_fabrica";
                        $res = pg_query ($con,$sql);

                        if (pg_num_rows($res) == 0) {
                            #insere um registro
                            $sql = "INSERT INTO tbl_posto_fabrica_foto
                                        (posto, fabrica)
                                        VALUES ($posto,$login_fabrica)";

                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                            $sql                = "SELECT CURRVAL ('seq_posto_fabrica_foto')";
                            $res                = pg_query ($con,$sql);
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        } else {
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        }

                        $nome_foto  = "$posto_fabrica_foto"."_contato1.$ext";
                        $nome_thumb = "$posto_fabrica_foto"."_contato1_thumb.$ext";

                        $Caminho_foto  = "../foto_posto/$nome_foto";
                        $Caminho_thumb = "../foto_posto/$nome_thumb";

                        $descricao_foto_contato1 = str_replace("\'","",$_POST['descricao_foto_contato1']);
                        $descricao_foto_contato1 = str_replace("\"","",$descricao_foto_contato1);

                        #Atualiza o nome do arquivo na tabela
                        if (strlen($posto_fabrica_foto)>0){
                            $sql = "UPDATE tbl_posto_fabrica_foto SET
                                        foto_contato1           = '$Caminho_foto',
                                        foto_contato1_thumb     = '$Caminho_thumb',
                                        foto_contato1_descricao = '$descricao_foto_contato1'
                                    WHERE posto_fabrica_foto = $posto_fabrica_foto";
                            $res = pg_query ($con,$sql);
                        }

                        reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
                        reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
                    }else{
                        $msg_erro .= "O formato da foto $Nome não é permitido!<br>";
                    }
                }
            }

            if (isset($_FILES['foto_contato2'])) {
                $Destino  = "/www/assist/www/foto_posto/";
                $DestinoT = "/www/assist/www/foto_posto/";

                $Fotos    = $_FILES['foto_contato2'];
                $Nome     = $Fotos['name'];
                $Tamanho  = $Fotos['size'];
                $Tipo     = $Fotos['type'];
                $Tmpname  = $Fotos['tmp_name'];
                $Extensao = $Nome;

                if(strlen($Extensao)>0){
                    if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){
                        if(!is_uploaded_file($Tmpname)){
                            $msg_erro .= "Não foi possível efetuar o upload.";
                        }

                        $tmp = explode(".",$Nome);
                        $ext = $tmp[count($tmp)-1];

                        if (strlen($Extensao)==0){
                            $ext = $Extensao;
                        }

                        $ext = strtolower($ext);

                        $sql = "SELECT posto_fabrica_foto
                                FROM tbl_posto_fabrica_foto
                                WHERE posto = $posto
                                AND fabrica = $login_fabrica";
                        $res = pg_query ($con,$sql);

                        if (pg_num_rows($res) == 0) {
                            #insere um registro
                            $sql = "INSERT INTO tbl_posto_fabrica_foto
                                        (posto, fabrica)
                                        VALUES ($posto,$login_fabrica)";

                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                            $sql                = "SELECT CURRVAL ('seq_posto_fabrica_foto')";
                            $res                = pg_query ($con,$sql);
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        } else {
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        }

                        $nome_foto  = "$posto_fabrica_foto"."_contato2.$ext";
                        $nome_thumb = "$posto_fabrica_foto"."_contato2_thumb.$ext";

                        $Caminho_foto  = "../foto_posto/$nome_foto";
                        $Caminho_thumb = "../foto_posto/$nome_thumb";

                        $descricao_foto_contato2 = str_replace("\'","",$_POST['descricao_foto_contato2']);
                        $descricao_foto_contato2 = str_replace("\"","",$descricao_foto_contato2);

                        #Atualiza o nome do arquivo na tabela
                        if (strlen($posto_fabrica_foto)>0){
                            $sql = "UPDATE tbl_posto_fabrica_foto SET
                                        foto_contato2       = '$Caminho_foto',
                                        foto_contato2_thumb = '$Caminho_thumb',
                                        foto_contato2_descricao = '$descricao_foto_contato2'
                                    WHERE posto_fabrica_foto = $posto_fabrica_foto";
                            $res = pg_query ($con,$sql);
                        }

                        reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
                        reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
                    }else{
                        $msg_erro .= "O formato da foto $Nome não é permitido!<br>";
                    }
                }
            }
        }


        #HD 401553 INICIO
        if (strlen($msg_erro)==0 and $login_fabrica == 42){

            $posto_edicao = $posto;
            /* DELETA TODOS OS REGISTROS DA TABELA tbl_posto_filial
                RELACIONADOS AO POSTO QUE ESTÁ SENDO EDITADO */
                $sql_del_posto_filial = "
                    DELETE from tbl_posto_filial
                    WHERE tbl_posto_filial.posto = $posto_edicao;
                ";

                $res_del_posto_filial = pg_query($con,$sql_del_posto_filial);
                $msg_erro .= pg_errormessage($con);

            /* FAZ A CONTA DE QUANTOS POSTOS ESTÃO COMO DISTRIBUIDOR */
            $sql_distribuidores = "
                SELECT count(tbl_posto_fabrica.nome_fantasia)
                from tbl_posto_fabrica
                JOIN tbl_tipo_posto using (tipo_posto)
                WHERE tbl_tipo_posto.fabrica=$login_fabrica
                AND tbl_posto_fabrica.filial is true;
            ";

            $res_distribuidores = pg_query($con,$sql_distribuidores);

            for ($x = 0; $x < $res_distribuidores;$x++){

                $posto_filial = $_POST['posto_distrib_'.$x];
                $cad_distribuidor = $_POST['cad_distribuidor_'.$x];

                if (!$msg_erro and $cad_distribuidor == 't'){
                    /* INSERE NA TABELA tbl_posto_filial OS POSTOS QUE FORAM SELECIONADOS
                    NA EDIÇÃO DA TABELA "Filiais" DO FORMULÁRIO */

                    $sql_insere_posto_filial = "
                        INSERT INTO tbl_posto_filial(
                            posto,
                            filial_posto,
                            fabrica
                        )VALUES(
                            $posto_edicao,
                            $posto_filial,
                            $login_fabrica
                        )
                    ";

                    $res_insere_posto_filial = pg_query($con,$sql_insere_posto_filial);
                    $msg_erro .= pg_errormessage($con);

                }

            }

        }
        #HD 401553 FIM

/*==============Cidades Atendidas 781457  ================*/

    if (isset($_POST['btn_acao']) && $posto > 0) {

        if ($login_fabrica == 1 and empty($msg_erro) and strlen($posto) > 0 and $posto <> "6359")
        {
            include_once '../class/email/mailer/class.phpmailer.php';

            $mailer = new PHPMailer();

            $sqlRP = "SELECT email FROM tbl_admin WHERE fabrica = $login_fabrica AND responsavel_postos IS TRUE";
            $resRP = pg_query($con,$sqlRP);

            if (pg_num_rows($resRP) > 0)
            {
                $emailRP = Array();
                for ($i = 0; $i < pg_num_rows($resRP); $i++)
                {
                    $emailRP[] = pg_fetch_result($resRP,$i,"email");
                }
            }

            $emailRP[] = "helpdesk@telecontrol.com.br";

            $assunto   = "Alterações no cadastro do Posto";

            $mensagem  = "Houve alteração no cadastro do posto $codigo_posto - $nome";

            $mailer->IsSMTP();
            $mailer->IsHTML();
            foreach ($emailRP as $mail){
                $mailer->AddAddress($mail);
            }

            $mailer->Subject = $assunto;
            $mailer->Body = $mensagem;

            if (!$mailer->Send()){
                $new_email  = implode(",", $emailRP);

                $cabecalho  = "MIME-Version: 1.0 \r";
                $cabecalho .= "Content-type: text/html; charset=iso-8859-1 \r";
                $cabecalho .= "From: helpdesk@telecontrol.com.br";

                mail($new_email, utf8_encode($assunto), utf8_encode($mensagem), $cabecalho);
            }
        }

    }
/*=====================================*/

        if (strlen ($msg_erro) == 0) {
            $array_depois = pg_fetch_all($res);
            $array_depois = json_encode($array_depois);	

            $res = pg_query ($con,"COMMIT TRANSACTION");

            /**
             * Bosch - 20
             * Criação de execução da rotina de criação do arquivo excel de atualização de postos.
             * Após criado, irá ser enviado por email para:
             * Warranty.EWQAS@de.bosch.com ; helpdesk@telecontrol.com.br
             *
             * @author Gabriel Silveira - 19/09/2012
             *
             */
            if ($login_fabrica == 20 and $novo_posto) {

                include_once '../class/email/mailer/class.phpmailer.php';
                /**
                 * instancia a classe PHPMailer no objeto $mailer
                 */
                $mailer = new PHPMailer();

                $cadastro = "novo";
                $status = "C";

                $comando = "php /www/assist/www/rotinas/bosch/atualizacao-posto.php $novo_posto $status $cadastro";

                $link_arquivo = exec($comando);

                if ($error_code === 0) {

                    $link_arquivo = $resposta[0];
                    if ($link_arquivo == 'Sem resultados') {
                        $msg = $link_arquivo;
                        unset($link_arquivo);
                    }

                } else {
                    #$msg_erro = 'Erro ao processar o arquivo de atualização de posto. Tente novamente.';
                }

                $addresList = array('robson.gastao@br.bosch.com', 'helpdesk@telecontrol.com.br' );
                /* Conforme chamado 1056288 - Gastao falou para tirar dessa rotina tb...
                $addresList = array('robson.gastao@br.bosch.com', 'Warranty.EWQAS@de.bosch.com' );  */

                if ($link_arquivo != 'Sem resultados') {

                    $file = "/var/www/assist/www/admin/xls/{$link_arquivo}";

                    $mailer->IsSMTP();
                    $mailer->IsHTML(true);
                    foreach ($addresList as $ToAddres){
                        $mailer->AddAddress($ToAddres);
                    }

                    $mensagem = "Dear Colleague,

A new Service Center was created. Please include it in your system. The warranty claim file will be sent in the next interface

Thanks in advance ";
                    $mailer->Subject = "New LAM Service Center";
                    $mailer->Body    = $mensagem;
                    $mailer->CharSet = 'UTF-8';
                    $mailer->AddAttachment($file, 'arquivonovoposto.xls');

                    if (!$mailer->Send()){

                        $msg_erro = "O email de atualização do posto não foi enviado";

                    }

                }
            }

            //HD 732838 - Salva os relacionamentos de posto x area de atuacao.. originalmente só para a latina

            header ("Location: $PHP_SELF?posto=$posto&msg=Gravado com sucesso");
            exit;
        }else{
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        }
    }//fim if msg_erro
}

if($_POST['ajax'] == 'excluir'){
	$imagem = $_POST['imagem'];
	if(file_exists($imagem)) {
		unlink($imagem);
	}
	exit('Imagem excluída');
}
if ($_POST['gravarimagem']) {
	$posto = $_POST['posto_imagem'];
    if(is_array($_POST['exclui'])){
        foreach($_POST['exclui'] as $apagar){
            unlink($apagar);
        }
    }
    $caminho_imagem = '../autocredenciamento/fotos/';
    $caminho_path   = '../autocredenciamento/fotos';
    //$debug = ($_COOKIE['debug'][0] == 't');
    $msg_erro = array();

    $nome_foto__cnpj = preg_replace('/\D/','',utf8_decode($cnpj_imagem));

    $config["tamanho"] = 2*1024*1024;

    if(count($msg_erro) == 0){
        for($i = 1; $i < 4; $i++){



            if ($_FILES["arquivo$i"]['name']=='') continue; //  Próxima iteração se não há arquivo definido

            $arquivo    = $_FILES["arquivo$i"];


            // if ($debug) {echo "<p>Imagem para o posto $posto, Erros: ".count($msg_erro)."<br><pre>".var_dump($arquivo)."</pre></p>";}

            // Formulário postado... executa as ações
            if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

                // Verifica o MIME-TYPE do arquivo
                if (!preg_match("/\/(pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
                    $msg_erro[] = "Arquivo em formato inválido!";
                }

                // Verifica tamanho do arquivo
                if ($arquivo["size"] > $config["tamanho"])
                    $msg_erro[] = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";

                if (count($msg_erro) == 0) {

                    // Pega extensão do arquivo
                    preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
                    $aux_extensao = "." . $ext[1];
                    $aux_extensao = strtolower($aux_extensao);

                    // Gera um nome único para a imagem
                    $nome_anexo = $nome_foto__cnpj . "_" .$i . $aux_extensao;

//                     if ($debug) echo "<p>Imagem $i gravada como $nome_anexo...</p>";

                    // Exclui anteriores, qualque extensao
                    #@unlink($nome_foto__cnpj . "_" .$i);
                    array_map('unlink',glob($caminho_imagem.$nome_foto__cnpj . "_" .$i.".*"));

                    // Faz o upload da imagem
                    if (count($msg_erro) == 0) {
                        $thumbail = new resize( "arquivo$i", 600, 400 );
                        $thumbail -> saveTo($nome_anexo,$caminho_imagem);
                    }

                }
            }
        }
    }
    if(empty($msg_erro)) {
	    header ("Location: $PHP_SELF?posto=$posto&msg=Gravado com sucesso");
	    exit;
    }
}

if($_POST['anexa_contrato']){

    $contrato = $_FILES['contrato'];
    $posto_contrato = $_POST['posto_contrato'];
    $ext = explode('/',$contrato['type']);

    if($ext[1] != "pdf" AND $ext[1] != "PDF"){
        $msg_erro = "Tipo de arquivo inválido. Por favor enviar arquivo PDF";
    }else{
        if ($arquivo["size"] > 2048000){
            $msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
        }else{

            $nome_anexo = "anexos/contrato_$posto_contrato.pdf";

            if(!move_uploaded_file($contrato['tmp_name'], $nome_anexo)){
                $msg_erro = "Falha ao anexar arquivo";
            }

        }
    }

}

#-------------------- Pesquisa Posto -----------------
if (strlen($_GET['posto']) > 0)  $posto = trim($_GET['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);

if (strlen($posto) > 0 and strlen ($msg_erro) == 0 ) {
    $sql = "SELECT  tbl_posto_fabrica.posto       ,
            tbl_posto_fabrica.credenciamento      ,
            tbl_posto_fabrica.codigo_posto        ,
            tbl_posto_fabrica.posto_empresa       ,
            tbl_posto_fabrica.tipo_posto          ,
            tbl_posto_fabrica.transportadora_nome ,
            tbl_posto_fabrica.transportadora      ,
            tbl_posto_fabrica.cobranca_endereco   ,
            tbl_posto_fabrica.cobranca_numero     ,
            tbl_posto_fabrica.cobranca_complemento,
            tbl_posto_fabrica.cobranca_bairro     ,
            tbl_posto_fabrica.cobranca_cep        ,
            tbl_posto_fabrica.cobranca_cidade     ,
            tbl_posto_fabrica.cobranca_estado     ,
            tbl_posto_fabrica.obs                 ,
            tbl_posto_fabrica.banco               ,
            tbl_posto_fabrica.agencia             ,
            tbl_posto_fabrica.conta               ,
            tbl_posto_fabrica.filial              ,
            tbl_posto_fabrica.nomebanco           ,
            tbl_posto_fabrica.favorecido_conta    ,
            tbl_posto_fabrica.conta_operacao      ,
            tbl_posto_fabrica.cpf_conta           ,
            tbl_posto_fabrica.atendimento         ,
            tbl_posto_fabrica.atendimento         ,
            tbl_posto_fabrica.tipo_conta          ,
            tbl_posto_fabrica.obs_conta           ,
            tbl_posto_fabrica.acrescimo_tributario,
            tbl_posto.nome                        ,
            tbl_posto.cnpj                        ,
            tbl_posto.ie                          ,
            tbl_posto.im                          ,
            tbl_posto_fabrica.contato_endereco       AS endereco,
            tbl_posto_fabrica.contato_numero         AS numero,
            tbl_posto_fabrica.contato_complemento    AS complemento,
            tbl_posto_fabrica.contato_bairro         AS bairro,
            tbl_posto_fabrica.contato_cep            AS cep,
            tbl_posto_fabrica.contato_cidade         AS cidade,
            tbl_posto_fabrica.contato_estado         AS estado,
            tbl_posto_fabrica.contato_email          AS email,
            tbl_posto_fabrica.contato_fone_comercial AS fone,
            tbl_posto_fabrica.contato_fone_residencial AS fone2,
            tbl_posto_fabrica.contato_cel AS fone3,
            tbl_posto_fabrica.contato_fax            AS fax,
            tbl_posto_fabrica.contato_nome           AS contato_nome,
            /* HD 52864 19/11/2008
            tbl_posto.fone                        ,
            tbl_posto.fax                         ,*/
            tbl_posto.suframa                     ,
            tbl_posto.contato                     ,
            tbl_posto.capital_interior            ,
            tbl_posto_fabrica.nome_fantasia       ,
            tbl_posto.pais                        ,
            tbl_posto_fabrica.item_aparencia      ,
            tbl_posto_fabrica.senha               ,
            tbl_posto_fabrica.desconto            ,
            CASE WHEN tbl_posto_fabrica.valor_km = 0 THEN tbl_fabrica.valor_km
            ELSE tbl_posto_fabrica.valor_km END as valor_km  ,
            tbl_posto_fabrica.desconto_acessorio  ,
            tbl_posto_fabrica.custo_administrativo,
            tbl_posto_fabrica.imposto_al          ,
            tbl_posto_fabrica.pedido_em_garantia  ,
            tbl_posto_fabrica.reembolso_peca_estoque,
            tbl_posto_fabrica.coleta_peca         ,
            tbl_posto_fabrica.pedido_faturado     ,
            tbl_posto_fabrica.digita_os           ,
            tbl_posto_fabrica.controla_estoque    ,
            tbl_posto_fabrica.prestacao_servico   ,
            tbl_posto_fabrica.prestacao_servico_sem_mo,
            tbl_posto_fabrica.atende_comgas       ,
            tbl_posto_fabrica.pedido_bonificacao  ,
            tbl_posto_fabrica.senha_financeiro            ,
            tbl_posto.senha_tabela_preco          ,
            tbl_posto_fabrica.admin               ,
            to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao,
            tbl_posto_fabrica.pedido_via_distribuidor,
            tbl_posto_fabrica.garantia_antecipada,
            tbl_posto_fabrica.escritorio_regional,
            tbl_posto_fabrica.imprime_os         ,
            to_char(tbl_posto_fabrica.data_nomeacao,'DD/MM/YYYY') AS data_nomeacao,
            tbl_posto_fabrica.qtde_os_item,
            tbl_posto_fabrica.escolhe_condicao,
            tbl_posto_fabrica.condicao_escolhida,
            tbl_posto_fabrica.atende_consumidor,
            tbl_posto_fabrica.admin_sap,
            tbl_posto_fabrica.divulgar_consumidor,
            tbl_posto_fabrica.centro_custo,
            tbl_posto_fabrica.conta_contabil,
            tbl_posto_fabrica.local_entrega,
            tbl_posto_fabrica.contribuinte_icms,
            tbl_posto_fabrica.controle_estoque_novo,
            tbl_posto_fabrica.controle_estoque_manual,
            tbl_posto_fabrica.contato_telefones,
            tbl_posto_fabrica.entrega_tecnica,
            tbl_posto_fabrica.categoria,
            tbl_excecao_mobra.tx_administrativa,
            tbl_posto_fabrica.permite_envio_produto
            " . (($login_fabrica == 3) ? ", tbl_posto_fabrica.admin_sap_especifico" : "") . "
        FROM      tbl_posto
        LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
        JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica AND tbl_fabrica.fabrica = $login_fabrica
        LEFT JOIN tbl_excecao_mobra ON tbl_posto.posto = tbl_excecao_mobra.posto AND tbl_excecao_mobra.fabrica = $login_fabrica AND tbl_excecao_mobra.tx_administrativa notnull
        WHERE     tbl_posto_fabrica.fabrica = $login_fabrica
        AND       tbl_posto_fabrica.posto   = $posto ";

    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) > 0) {
 	$array_antes = pg_fetch_all($res);
        $array_antes = json_encode($array_antes);
        $posto            = trim(pg_fetch_result($res,0,posto));
        $credenciamento   = trim(pg_fetch_result($res,0,credenciamento));
        $codigo           = trim(pg_fetch_result($res,0,codigo_posto));
        $nome             = trim(pg_fetch_result($res,0,nome));
        $cnpj             = trim(pg_fetch_result($res,0,cnpj));
        $ie               = trim(pg_fetch_result($res,0,ie));
        $im               = trim(pg_fetch_result($res,0,im));
        if (strlen($cnpj) == 14) $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
        if (strlen($cnpj) == 11) $cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
        $endereco         = trim(pg_fetch_result($res,0,endereco));
        $endereco         = str_replace("\"","",$endereco);
        $numero           = trim(pg_fetch_result($res,0,numero));
        $complemento      = trim(pg_fetch_result($res,0,complemento));
        $bairro           = trim(pg_fetch_result($res,0,bairro));
        $cep              = trim(pg_fetch_result($res,0,cep));
        $cidade           = trim(pg_fetch_result($res,0,cidade));
        $estado           = trim(pg_fetch_result($res,0,estado));
        $email            = trim(pg_fetch_result($res,0,email));
        if ($login_fabrica == 15) {

            $email_latina = array();
            $email_latina = split(';', $email);

            $email  = $email_latina[0];
            $email2 = $email_latina[1];

        }
        $fone             = trim(pg_fetch_result($res,0,fone));
        $fone2             = trim(pg_fetch_result($res,0,fone2));
        $fone3             = trim(pg_fetch_result($res,0,fone3));
        if ($login_fabrica == 15){

            $chars_replace = array('{','}','"');
            $contato_telefones = str_replace($chars_replace, "", trim(pg_fetch_result($res,0,'contato_telefones')));

            $fones_latina = array();
            $fones_latina = split(',', $contato_telefones);

            if(strlen($fone)==0 and strlen($fones_latina[0])>0 ){
                $fone  = $fones_latina[0];
            }
            $fone2 = $fones_latina[1];
            $fone3 = $fones_latina[2];

        }

        $fax                      = trim(pg_fetch_result($res,0, 'fax'));
        $contato                  = trim(pg_fetch_result($res,0, 'contato'));
        $suframa                  = trim(pg_fetch_result($res,0, 'suframa'));
        $item_aparencia           = trim(pg_fetch_result($res,0, 'item_aparencia'));
        $obs                      = trim(pg_fetch_result($res,0, 'obs'));
        $capital_interior         = trim(pg_fetch_result($res,0, 'capital_interior'));
        $posto_empresa            = trim(pg_fetch_result($res,0, 'posto_empresa'));
        $tipo_posto               = trim(pg_fetch_result($res,0, 'tipo_posto'));
        $senha                    = trim(pg_fetch_result($res,0, 'senha'));
        $pais                     = trim(pg_fetch_result($res,0, 'pais'));
        $desconto                 = trim(pg_fetch_result($res,0, 'desconto'));
        $valor_km                 = trim(pg_fetch_result($res,0, 'valor_km'));
        $desconto_acessorio       = trim(pg_fetch_result($res,0, 'desconto_acessorio'));
        $custo_administrativo     = trim(pg_fetch_result($res,0, 'custo_administrativo'));
        $imposto_al               = trim(pg_fetch_result($res,0, 'imposto_al'));
        $nome_fantasia            = trim(pg_fetch_result($res,0, 'nome_fantasia'));
        $transportadora           = trim(pg_fetch_result($res,0, 'transportadora'));
        $escritorio_regional      = trim(pg_fetch_result($res,0, 'escritorio_regional'));
        $posto_filial             = trim(pg_fetch_result($res,0, 'filial')); #HD 401553
        $acrescimo_tributario     = trim(pg_fetch_result($res,0, 'acrescimo_tributario'));

        $cobranca_endereco        = trim(pg_fetch_result($res,0, 'cobranca_endereco'));
        $cobranca_numero          = trim(pg_fetch_result($res,0, 'cobranca_numero'));
        $cobranca_complemento     = trim(pg_fetch_result($res,0, 'cobranca_complemento'));
        $cobranca_bairro          = trim(pg_fetch_result($res,0, 'cobranca_bairro'));
        $cobranca_cep             = trim(pg_fetch_result($res,0, 'cobranca_cep'));
        $cobranca_cidade          = trim(pg_fetch_result($res,0, 'cobranca_cidade'));
        $cobranca_estado          = trim(pg_fetch_result($res,0, 'cobranca_estado'));
        $pedido_em_garantia       = trim(pg_fetch_result($res,0, 'pedido_em_garantia'));
        $reembolso_peca_estoque   = trim(pg_fetch_result($res,0, 'reembolso_peca_estoque'));
        $coleta_peca              = trim(pg_fetch_result($res,0, 'coleta_peca'));
        $pedido_faturado          = trim(pg_fetch_result($res,0, 'pedido_faturado'));
        $digita_os                = trim(pg_fetch_result($res,0, 'digita_os'));
        $controla_estoque         = trim(pg_fetch_result($res,0, 'controla_estoque'));
        $controle_estoque_novo    = trim(pg_fetch_result($res,0, 'controle_estoque_novo'));
        $controle_estoque_manual  = trim(pg_fetch_result($res,0, 'controle_estoque_manual'));
        $prestacao_servico        = trim(pg_fetch_result($res,0, 'prestacao_servico'));
        $prestacao_servico_sem_mo = trim(pg_fetch_result($res,0, 'prestacao_servico_sem_mo'));
        $pedido_bonificacao       = trim(pg_fetch_result($res,0,'pedido_bonificacao'));
        $banco                    = trim(pg_fetch_result($res,0, 'banco'));
        $agencia                  = trim(pg_fetch_result($res,0, 'agencia'));
        $conta                    = trim(pg_fetch_result($res,0, 'conta'));
        $nomebanco                = trim(pg_fetch_result($res,0, 'nomebanco'));
        $favorecido_conta         = trim(pg_fetch_result($res,0, 'favorecido_conta'));
        $conta_operacao           = trim(pg_fetch_result($res,0, 'conta_operacao'));//HD 8190 5/12/2007 Gustavo
        $cpf_conta                = trim(pg_fetch_result($res,0, 'cpf_conta'));
        $tipo_conta               = trim(pg_fetch_result($res,0, 'tipo_conta'));
        $obs_conta                = trim(pg_fetch_result($res,0, 'obs_conta'));
        $senha_financeiro         = trim(pg_fetch_result($res,0, 'senha_financeiro'));
        $senha_tabela_preco       = trim(pg_fetch_result($res,0, 'senha_tabela_preco'));
        $pedido_via_distribuidor  = trim(pg_fetch_result($res,0, 'pedido_via_distribuidor'));
        $atende_comgas            = trim(pg_fetch_result($res,0, 'atende_comgas'));
        $atendimento_lenoxx       = trim(pg_fetch_result($res,0, 'atendimento'));//HD 110541
        $divulgar_consumidor      = pg_fetch_result($res,0, 'divulgar_consumidor');
        $atendimento              = $atendimento_lenoxx;
        $contato_nome             = trim(pg_fetch_result($res,0, 'contato_nome'));//HD 110541

        $admin                    = trim(pg_fetch_result($res,0, 'admin'));
        $data_alteracao           = trim(pg_fetch_result($res,0, 'data_alteracao'));
        $garantia_antecipada      = trim(pg_fetch_result($res,0, 'garantia_antecipada'));
        $imprime_os               = pg_fetch_result($res,0, 'imprime_os'); // HD12104
        $qtde_os_item             = pg_fetch_result($res,0, 'qtde_os_item'); // HD 17601
        $escolhe_condicao         = pg_fetch_result($res,0, 'escolhe_condicao');
        $condicao_escolhida       = pg_fetch_result($res,0, 'condicao_escolhida');
        $atende_consumidor        = pg_fetch_result($res,0, 'atende_consumidor'); // HD 126810 -    Adicionado campo 'atende_consumidor'
        $data_nomeacao            = pg_fetch_result($res,0, 'data_nomeacao'); // hd 21496 - Francisco - campo Data da Nomeação para Dynacom
        $admin_sap                = pg_fetch_result($res,0,'admin_sap'); // ! HD 121248 (augusto) - Buscar atendente de posto cadastrado para este posto

        if ($login_fabrica == 3) {
            $admin_sap_especifico = pg_fetch_result($res, 0, "admin_sap_especifico");
        }

        # HD 110541

        $centro_custo             = trim(pg_fetch_result($res,0, 'centro_custo'));//HD 356653
        $conta_contabil           = trim(pg_fetch_result($res,0, 'conta_contabil'));//HD 356653
        $local_entrega            = trim(pg_fetch_result($res,0, 'local_entrega'));//HD 356653
        $contribuinte_icms        = pg_fetch_result($res,0, 'contribuinte_icms');

        if ($login_fabrica == 42) {
            $entrega_tecnica = pg_fetch_result($res, 0, "entrega_tecnica");
        }

        if ($login_fabrica == 1) {
            $categoria_posto =pg_fetch_result($res,0,categoria);
            $taxa_administrativa =pg_fetch_result($res,0,tx_administrativa);
        }

        if($login_fabrica==11){
            $permite_envio_produto = pg_fetch_result($res, 0, "permite_envio_produto");

            $sql_X = "SELECT TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS dataa
                        FROM tbl_credenciamento
                       WHERE fabrica = 11
                         AND posto   = $posto
                    ORDER BY data DESC
                       LIMIT 1";
                $res_X = pg_query ($con,$sql_X);
                if (pg_num_rows ($res_X) > 0) {
                        $data_credenciamento   = trim(pg_fetch_result($res_X,0,'dataa'));
                }
        }
    }else{
        $sql = "SELECT  tbl_posto_fabrica.posto               ,
                tbl_posto_fabrica.credenciamento      ,
                tbl_posto_fabrica.codigo_posto        ,
                tbl_posto_fabrica.posto_empresa       ,
                tbl_posto_fabrica.tipo_posto          ,
                tbl_posto_fabrica.transportadora_nome ,
                tbl_posto_fabrica.transportadora      ,
                tbl_posto_fabrica.cobranca_endereco   ,
                tbl_posto_fabrica.cobranca_numero     ,
                tbl_posto_fabrica.cobranca_complemento,
                tbl_posto_fabrica.cobranca_bairro     ,
                tbl_posto_fabrica.cobranca_cep        ,
                tbl_posto_fabrica.cobranca_cidade     ,
                tbl_posto_fabrica.cobranca_estado     ,
                tbl_posto_fabrica.obs                 ,
                tbl_posto_fabrica.digita_os           ,
                tbl_posto_fabrica.controla_estoque    ,
                tbl_posto_fabrica.prestacao_servico   ,
                tbl_posto_fabrica.prestacao_servico_sem_mo,
                tbl_posto_fabrica.pedido_bonificacao  ,
                tbl_posto_fabrica.banco               ,
                tbl_posto_fabrica.agencia             ,
                tbl_posto_fabrica.conta               ,
                tbl_posto_fabrica.nomebanco           ,
                tbl_posto_fabrica.favorecido_conta    ,
                tbl_posto_fabrica.conta_operacao      ,
                tbl_posto_fabrica.cpf_conta           ,
                tbl_posto_fabrica.tipo_conta          ,
                tbl_posto_fabrica.obs_conta           ,
                tbl_posto_fabrica.atende_comgas       ,
                tbl_posto_fabrica.acrescimo_tributario,
                tbl_posto.nome                        ,
                tbl_posto.cnpj                        ,
                tbl_posto.ie                          ,
                tbl_posto.im                          ,
                tbl_posto_fabrica.contato_endereco    AS endereco,
                tbl_posto_fabrica.contato_numero      AS numero,
                tbl_posto_fabrica.contato_complemento AS complemento,
                tbl_posto_fabrica.contato_bairro      AS bairro,
                tbl_posto_fabrica.contato_cep         AS cep,
                tbl_posto_fabrica.contato_cidade      AS cidade,
                tbl_posto_fabrica.contato_estado      AS estado,
                tbl_posto_fabrica.contato_email       AS email,
                tbl_posto_fabrica.contato_fone_comercial AS fone,
                tbl_posto_fabrica.contato_fone_residencial AS fone2,
                tbl_posto_fabrica.contato_cel AS fone3,
                tbl_posto_fabrica.contato_fax            AS fax,
                tbl_posto_fabrica.contato_nome           AS contato_nome,
                /* HD 52864 19/11/2008
                tbl_posto.fone                        ,
                tbl_posto.fax                         ,*/
                tbl_posto.contato                     ,
                tbl_posto.suframa                     ,
                tbl_posto.pais                        ,
                tbl_posto.capital_interior            ,
                tbl_posto.nome_fantasia               ,
                tbl_posto_fabrica.item_aparencia      ,
                tbl_posto_fabrica.senha               ,
                tbl_posto_fabrica.desconto            ,
                tbl_posto_fabrica.valor_km            ,
                tbl_posto_fabrica.desconto_acessorio  ,
                tbl_posto_fabrica.custo_administrativo,
                tbl_posto_fabrica.imposto_al          ,
                tbl_posto_fabrica.pedido_em_garantia  ,
                tbl_posto_fabrica.reembolso_peca_estoque,
                tbl_posto_fabrica.coleta_peca        ,
                tbl_posto_fabrica.pedido_faturado     ,
                tbl_posto_fabrica.digita_os           ,
                tbl_posto_fabrica.controle_estoque_novo    ,
                tbl_posto_fabrica.prestacao_servico   ,
                tbl_posto_fabrica.prestacao_servico_sem_mo,
                tbl_posto_fabrica.pedido_bonificacao  ,
                tbl_posto.senha_financeiro            ,
                tbl_posto.senha_tabela_preco          ,
                tbl_posto_fabrica.admin               ,
                to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao,
                tbl_posto_fabrica.pedido_via_distribuidor,
                tbl_posto_fabrica.garantia_antecipada,
                tbl_posto_fabrica.escritorio_regional,
                tbl_posto_fabrica.imprime_os         ,
                to_char(tbl_posto_fabrica.data_nomeacao,'DD/MM/YYYY') AS data_nomeacao,
                tbl_posto_fabrica.qtde_os_item,
                tbl_posto_fabrica.escolhe_condicao,
                tbl_posto_fabrica.atende_consumidor,
                tbl_posto_fabrica.condicao_escolhida,
                tbl_posto_fabrica.divulgar_consumidor,
                tbl_posto_fabrica.centro_custo,
                tbl_posto_fabrica.conta_contabil,
                tbl_posto_fabrica.local_entrega,
                tbl_posto_fabrica.contribuinte_icms,
                tbl_posto_fabrica.entrega_tecnica,
                tbl_posto_fabrica.categoria,
                tbl_posto_fabrica.permite_envio_produto

            FROM    tbl_posto
            LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
            WHERE   tbl_posto_fabrica.posto   = $posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) > 0) {
            $posto                    = trim(pg_fetch_result($res,0, 'posto'));
            //$codigo                   = trim(pg_fetch_result($res,0, 'codigo_posto'));
            $credenciamento           = trim(pg_fetch_result($res,0, 'credenciamento'));
            $nome                     = trim(pg_fetch_result($res,0, 'nome'));
            $cnpj                     = trim(pg_fetch_result($res,0, 'cnpj'));
            $ie                       = trim(pg_fetch_result($res,0, 'ie'));
            $im                       = trim(pg_fetch_result($res,0, 'im'));
            if (strlen($cnpj) == 14) $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
            if (strlen($cnpj) == 11) $cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);

            $endereco                 = trim(pg_fetch_result($res,0, 'endereco'));
            $endereco                 = str_replace("\"","",$endereco);
            $numero                   = trim(pg_fetch_result($res,0, 'numero'));
            $complemento              = trim(pg_fetch_result($res,0, 'complemento'));
            $bairro                   = trim(pg_fetch_result($res,0, 'bairro'));
            $cep                      = trim(pg_fetch_result($res,0, 'cep'));
            $cidade                   = trim(pg_fetch_result($res,0, 'cidade'));
            $estado                   = trim(pg_fetch_result($res,0, 'estado'));
            $email                    = trim(pg_fetch_result($res,0, 'email'));
            $fone                     = trim(pg_fetch_result($res,0, 'fone'));
            $fone2                    = trim(pg_fetch_result($res,0, 'fone2'));
            $fone3                    = trim(pg_fetch_result($res,0, 'fone3'));
            $fax                      = trim(pg_fetch_result($res,0, 'fax'));
            $contato                  = trim(pg_fetch_result($res,0, 'contato'));
            $suframa                  = trim(pg_fetch_result($res,0, 'suframa'));
            $item_aparencia           = trim(pg_fetch_result($res,0, 'item_aparencia'));
            $obs                      = trim(pg_fetch_result($res,0, 'obs'));
            $capital_interior         = trim(pg_fetch_result($res,0, 'capital_interior'));
            $posto_empresa            = trim(pg_fetch_result($res,0, 'posto_empresa'));
            $tipo_posto               = trim(pg_fetch_result($res,0, 'tipo_posto'));
            //$senha                    = trim(pg_fetch_result($res,0, 'senha'));
            $desconto                 = trim(pg_fetch_result($res,0, 'desconto'));
            $valor_km                 = trim(pg_fetch_result($res,0, 'valor_km'));
            $desconto_acessorio       = trim(pg_fetch_result($res,0, 'desconto_acessorio'));
            $custo_administrativo     = trim(pg_fetch_result($res,0, 'custo_administrativo'));
            $imposto_al               = trim(pg_fetch_result($res,0, 'imposto_al'));
            $nome_fantasia            = trim(pg_fetch_result($res,0, 'nome_fantasia'));
            $transportadora           = trim(pg_fetch_result($res,0, 'transportadora'));
            $pais                     = trim(pg_fetch_result($res,0, 'pais'));

            $cobranca_endereco        = trim(pg_fetch_result($res,0, 'cobranca_endereco'));
            $cobranca_numero          = trim(pg_fetch_result($res,0, 'cobranca_numero'));
            $cobranca_complemento     = trim(pg_fetch_result($res,0, 'cobranca_complemento'));
            $cobranca_bairro          = trim(pg_fetch_result($res,0, 'cobranca_bairro'));
            $cobranca_cep             = trim(pg_fetch_result($res,0, 'cobranca_cep'));
            $cobranca_cidade          = trim(pg_fetch_result($res,0, 'cobranca_cidade'));
            $cobranca_estado          = trim(pg_fetch_result($res,0, 'cobranca_estado'));
            $pedido_em_garantia       = trim(pg_fetch_result($res,0, 'pedido_em_garantia'));
            $reembolso_peca_estoque   = trim(pg_fetch_result($res,0, 'reembolso_peca_estoque'));
            $coleta_peca              = trim(pg_fetch_result($res,0, 'coleta_peca'));
            $pedido_faturado          = trim(pg_fetch_result($res,0, 'pedido_faturado'));
            $digita_os                = trim(pg_fetch_result($res,0, 'digita_os'));
            $controla_estoque         = trim(pg_fetch_result($res,0, 'controla_estoque'));
            $controle_estoque_novo    = trim(pg_fetch_result($res,0, 'controle_estoque_novo'));
            $controle_estoque_manual  = trim(pg_fetch_result($res,0, 'controle_estoque_manual'));
            $prestacao_servico        = trim(pg_fetch_result($res,0, 'prestacao_servico'));
            $prestacao_servico_sem_mo = trim(pg_fetch_result($res,0, 'prestacao_servico_sem_mo'));
            $pedido_bonificacao       = pg_fetch_result($res,0, 'pedido_bonificacao');
            $banco                    = trim(pg_fetch_result($res,0, 'banco'));
            $agencia                  = trim(pg_fetch_result($res,0, 'agencia'));
            $conta                    = trim(pg_fetch_result($res,0, 'conta'));
            $nomebanco                = trim(pg_fetch_result($res,0, 'nomebanco'));
            $favorecido_conta         = trim(pg_fetch_result($res,0, 'favorecido_conta'));
            $conta_operacao           = trim(pg_fetch_result($res,0, 'conta_operacao'));//HD 8190 5/12/2007 Gustavo
            $cpf_conta                = trim(pg_fetch_result($res,0, 'cpf_conta'));
            $tipo_conta               = trim(pg_fetch_result($res,0, 'tipo_conta'));
            $obs_conta                = trim(pg_fetch_result($res,0, 'obs_conta'));
            $senha_financeiro         = trim(pg_fetch_result($res,0, 'senha_financeiro'));
            $senha_tabela_preco       = trim(pg_fetch_result($res,0, 'senha_tabela_preco'));
            $pedido_via_distribuidor  = trim(pg_fetch_result($res,0, 'pedido_via_distribuidor'));
            $atende_comgas            = trim(pg_fetch_result($res,0, 'atende_comgas'));
            $acrescimo_tributario     = trim(pg_fetch_result($res,0,'acrescimo_tributario'));
            $escritorio_regional      = trim(pg_fetch_result($res,0, 'escritorio_regional'));

            $contato_nome             = trim(pg_fetch_result($res,0, 'contato_nome'));

            $admin                    = trim(pg_fetch_result($res,0, 'admin'));
            $data_alteracao           = trim(pg_fetch_result($res,0, 'data_alteracao'));
            $garantia_antecipada      = trim(pg_fetch_result($res,0, 'garantia_antecipada'));
            $imprime_os               = trim(pg_fetch_result($res,0, 'imprime_os'));
            $qtde_os_item             = pg_fetch_result($res,0, 'qtde_os_item'); // HD 17601
            $escolhe_condicao         = pg_fetch_result($res,0, 'escolhe_condicao');
            $condicao_escolhida       = pg_fetch_result($res,0, 'condicao_escolhida');
            $atende_consumidor        = pg_fetch_result($res,0, 'atende_consumidor'); // HD 126810 -    Adicionado campo 'atende_consumidor'
            $data_nomeacao            = pg_fetch_result($res,0, 'data_nomeacao'); // hd 21496 - Francisco - campo Data da Nomeação para Dynacom
            $divulgar_consumidor      = pg_fetch_result($res,0, 'divulgar_consumidor');

            $centro_custo             = trim(pg_fetch_result($res,0, 'centro_custo'));//HD 356653
            $conta_contabil           = trim(pg_fetch_result($res,0, 'conta_contabil'));//HD 356653
            $local_entrega            = trim(pg_fetch_result($res,0, 'local_entrega'));//HD 356653
            $contribuinte_icms        = pg_fetch_result($res,0, 'contribuinte_icms');

            if ($login_fabrica == 11) {
                $permite_envio_produto = pg_fetch_result($res, 0, "permite_envio_produto");
            }

            if ($login_fabrica == 42) {
                $entrega_tecnica = pg_fetch_result($res, 0, "entrega_tecnica");
            }

            if ($login_fabrica == 1) {
                $categoria_posto =pg_fetch_result($res,0,categoria);
            }

        }else{
            $sql = "SELECT  tbl_posto.nome                        ,
                            tbl_posto.cnpj                        ,
                            tbl_posto.ie                          ,
                            tbl_posto.im                          ,
                            tbl_posto.endereco                    ,
                            tbl_posto.numero                      ,
                            tbl_posto.complemento                 ,
                            tbl_posto.bairro                      ,
                            tbl_posto.cep                         ,
                            tbl_posto.cidade                      ,
                            tbl_posto.estado                      ,
                            tbl_posto.email                       ,
                            tbl_posto.fone                        ,
                            tbl_posto.fax                         ,
                            tbl_posto.contato                     ,
                            tbl_posto.suframa                     ,
                            tbl_posto.capital_interior            ,
                            tbl_posto.senha_financeiro            ,
                            tbl_posto.senha_tabela_preco          ,
                            tbl_posto.pais                        ,
                            tbl_posto.nome_fantasia
                    FROM    tbl_posto
                    WHERE   tbl_posto.posto   = $posto ";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) > 0) {
                $nome               = trim(pg_fetch_result($res,0, 'nome'));
                $cnpj               = trim(pg_fetch_result($res,0, 'cnpj'));
                if (strlen($cnpj) == 14) $cnpj = substr($cnpj,0, 2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
                if (strlen($cnpj) == 11) $cnpj = substr($cnpj,0, 3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
                $ie                 = trim(pg_fetch_result($res,0, 'ie'));
                $im                 = trim(pg_fetch_result($res,0, 'im'));
                $endereco           = trim(pg_fetch_result($res,0, 'endereco'));
                $endereco           = str_replace("\"","",$endereco);
                $numero             = trim(pg_fetch_result($res,0, 'numero'));
                $complemento        = trim(pg_fetch_result($res,0, 'complemento'));
                $bairro             = trim(pg_fetch_result($res,0, 'bairro'));
                $cep                = trim(pg_fetch_result($res,0, 'cep'));
                $cidade             = trim(pg_fetch_result($res,0, 'cidade'));
                $estado             = trim(pg_fetch_result($res,0, 'estado'));
                $email              = trim(pg_fetch_result($res,0, 'email'));
                $fone               = trim(pg_fetch_result($res,0, 'fone'));
                $fax                = trim(pg_fetch_result($res,0, 'fax'));
                $contato            = trim(pg_fetch_result($res,0, 'contato'));
                $suframa            = trim(pg_fetch_result($res,0, 'suframa'));
                $capital_interior   = trim(pg_fetch_result($res,0, 'capital_interior'));
                $senha_financeiro   = trim(pg_fetch_result($res,0, 'senha_financeiro'));
                $senha_tabela_preco = trim(pg_fetch_result($res,0, 'senha_tabela_preco'));
                $nome_fantasia      = trim(pg_fetch_result($res,0, 'nome_fantasia'));
                $pais               = trim(pg_fetch_result($res,0, 'pais'));
            }
        }
    }
}

$visual_black = "manutencao-admin";

$title       = "CADASTRO  DE POSTOS AUTORIZADOS";
$cabecalho   = "CADASTRO DE POSTOS AUTORIZADOS";
$layout_menu = "cadastro";
include 'cabecalho.php';

$fone = preg_replace('/^\(?0?x*(\d{2})\)?/', '($1)', $fone)

?>


<?php
    include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script type='text/javascript'>
    function showModal() {

        Shadowbox.open({
            content:"../verifica_forma_extrato.php?posto=<?=$posto?>",
            player: "iframe",
            title:  "Geração de Extrato",
            width:  800,
            <?=$botao_fechar_modal?>
            height: 600
        });

    }

    function showImage(caminho) {

        Shadowbox.open({
            content:caminho,
            player: "iframe",
            title:  "Geração de Extrato",
            width:  700,
            <?=$botao_fechar_modal?>
            height: 500
        });

    }

    function deleteImage(caminho,imagem) {

	if (confirm("Excluir a imagem do posto?\nATENÇÃO: Se você fez alguma alteração no cadastro do posto, não será salva. Se for o caso, grave primeiro e exclua a imagem após gravar.")) {
		$.post(
			window.location.pathname,
			{
				ajax:     'excluir',
				'imagem': caminho
			},
			function(data) {
				alert('Imagem Excluída');
				$('#'+imagem+"> img").hide();
			}
		)
	}
    }

    window.onload = function(){

                Shadowbox.init( {
                        modal: true,
                } );


        };

    $(document).ready(function()
    {
        $("#mostra_opt_extrato").click(function(e) {
                        showModal();
                        e.preventDefault();
        });

        $('#cobranca_cidade').alpha();
        $(".msk_valor").numeric({allow: ',' });

        $('#tipo_controle_estoque').change(function()
        {
            var value = $(this).val();

            if (value == 'estoque_novo'){
                alert("Alterando para 'Estoque Novo', se existir, será zerado todo e qualquer saldo de peças do estoque deste posto");
            }
        });
    });

    //função p/ digitar só numero
    function checarNumero(campo){
        var num = campo.value.replace(",",".");
        campo.value = parseInt(num);
        if (campo.value=='NaN') {
            campo.value='';
        }
    }

    function fnc_pesquisa_codigo_posto (codigo, nome) {
        var url = "";
        if (codigo != "" && nome == "") {
            url = "pesquisa_posto.php?codigo=" + codigo;
            janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
            janela.focus();
        }
        else{
            alert("Informe toda ou parte da informação para realizar a pesquisa");
        }
    }

    function fnc_pesquisa_nome_posto (codigo, nome) {
        var url = "";
        if (codigo == "" && nome != "") {
            url = "pesquisa_posto.php?nome=" + nome;
            janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
            janela.focus();
        }
        else{
            alert("Informe toda ou parte da informação para realizar a pesquisa");
        }
    }

    function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {
        if (tipo == "nome" ) {
            var xcampo = campo;
        }

        if (tipo == "cnpj" ) {
            var xcampo = campo2;
        }

        if (tipo == "codigo" ) {
            var xcampo = campo3;
        }

        if (xcampo.value != "") {
            var url = "";
            url = "posto_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");
            janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
            janela.nome = campo;
            janela.cnpj = campo2;
            janela.focus();
        }
        else{
            alert("Informe toda ou parte da informação para realizar a pesquisa");
        }
    }

    //HD 5595 Listar posto revenda para Tectoy
    function posto_revenda(fabrica){
        janela = window.open("posto_revenda.php?fabrica=" + fabrica ,"fabrica",'resizable=1,scrollbars=yes,width=650,height=450,top=0,left=0');
        janela.focus();
    }

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
        Formata o Campo de CNPJ a medida que ocorre a digitação
        Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(campo){
    var cnpj = campo.value.length;
    if (cnpj == 2 || cnpj == 6) campo.value += '.';
    if (cnpj == 10) campo.value += '/';
    if (cnpj == 15) campo.value += '-';
}
</script>

<!-- JavaScript Mapa da Rede-->
<script type="text/javascript">
$(document).ready(function()
{
    <? if($login_fabrica <> 14){ ?>
/*      $("input[@name=fone]").maskedinput("(99) 9999-9999");
        $("input[@name=fone2]").maskedinput("(99) 9999-9999");
        $("input[@name=fone3]").maskedinput("(99) 9999-9999");
        $("input[@name=contato_cel]").maskedinput("(99) 9999-9999");*/
    <? } ?>
    /*$("input[@name=fax]").maskedinput("(99) 9999-9999");*/
    $("#cep").mask("99.999-999");
    $("#cobranca_cep").mask("99.999-999");
    $("#cnpj").mask('00.000.000/0000-00');
    $("input[name=cidade]").alpha({allow:" "});
    $("input[name=im]").numeric({allow:" "});
    <?php
        if($login_fabrica == 20){
            if($_pais ==  "BR"){
                echo '$("input[name=cnpj]").numeric();';
            }
        }else{
            echo '$("input[name=cnpj]").numeric();';
        }
    ?>
    $("input[name=desconto]").numeric({allow:".,"});
<?
        if($login_fabrica == 30){
?>
    $("#fixo_km_valor").numeric({allow:".,"});
    $("#fixo_km_valor").css("text-align","right");
<?
        }
?>
//  Adiciona um evento onClick para cada 'area' que vai alterar o valor do SELECT 'estado'
    $('#mapabr map area').click(function() {
        $('#mapa_estado').val($(this).attr('name'));
        $('#mapa_estado').change();
    });
    $('#sel_cidade').hide('fast');
    $('#abre_mapa_br').click(function() {
        $("#mapa_pesquisa").slideToggle("slow",function() {
            if ($("#mapa_pesquisa").is(":hidden")) {
                $("#abre_mapa_br").html('<b>Consulte o Mapa da Rede</b> <br /> <img src="imagens/icon_mapa_e.gif" id="img_ab_e" style="width: 28px; height: 28px; margin: 0 auto;" /> <img src="imagens/icon_mapa.gif" id="img_ab" style="width: 28px; height: 28px; margin: 0 auto; display: none;" />');
            } else {
                $("#abre_mapa_br").html('<b>Esconder o Mapa da Rede</b> <br /> <img src="imagens/icon_mapa_e.gif" id="img_ab_e" style="width: 28px; height: 28px; margin: 0 auto;" /> <img src="imagens/icon_mapa.gif" id="img_ab" style="width: 28px; height: 28px; margin: 0 auto; display: none;" />');
            }
        });
    });

    $("#abre_mapa_br").mouseover(function () {
        $("#abre_mapa_br > #img_ab_e").hide();
        $("#abre_mapa_br > #img_ab").show();
        $("#abre_mapa_br").removeClass("abre_mapa_br_e");
        $("#abre_mapa_br").addClass("abre_mapa_br");
    });

    $("#abre_mapa_br").mouseout(function () {
        $("#abre_mapa_br > #img_ab").hide();
        $("#abre_mapa_br > #img_ab_e").show();
        $("#abre_mapa_br").removeClass("abre_mapa_br");
        $("#abre_mapa_br").addClass("abre_mapa_br_e");
    });

//  Quando muda o valor do select 'estado' requisita as cidades onde tem postos autorizados e os
//  insere no select 'cidades'
    $('#mapa_estado').change(function() {
        var estado = $('#mapa_estado').val();
        if (estado == '') {
            $('#sel_cidade').hide();
            return;
        }

        $('#sel_cidade').show();
        $.get("cidade_mapa_rede.php", {'action': 'cidades','estado': estado,'fabrica':<?=$login_fabrica?>},
          function(data){
            $('#sel_cidade').show(500);
            if (data.indexOf('Sem resultados') < 0) {
                $('#mapa_cidades').html(data).val('').removeAttr('disabled');
                if ($('#mapa_cidades option').length == 2) {
                    $('#mapa_cidades option:last').attr('selected','selected');
                    $('#mapa_cidades').change();
                }
            } else {
                $('#mapa_cidades').html(data).val('Sem resultados').attr('disabled','disabled');
            }
          });
    });

    $('#mapa_cidades').change(function() {
        $('select[name=cidade]').val($('#mapa_cidades').val());
        $('[name=btn_mapa]').click();
    });
//  });
}); // FIM do jQuery

function montaComboCidade(estado){

    $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estado="+estado,
            cache: false,
            success: function(data) {
                $('#cidade').html(data);
            }

        });

}

function montaComboCidade2(){

    var estado = $('#mapa_estado').val();

    $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estado="+estado,
        cache: false,
        success: function(data) {
            $('#mapa_cidades').html(data);
        }

    });

}

function entrega_tecnica_check(valor) {
    if (valor == "t") {
        //$("#entrega_tecnica").attr("checked", "checked").attr("readonly", "readonly");
        $("#entrega_tecnica").attr("checked", "checked");
    } else {
        $("#entrega_tecnica").removeAttr("readonly");
    }
}
</script>

<style type="text/css">

.text_curto {
    text-align: center;
    font-weight: bold;
    color: #000;
    background-color: #FF6666;
}

.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    border: 1px solid;
    color:#596d9b;
    background-color: #d9e2ef
}

.border {
    border: 1px solid #ced7e7;
}

.table_line {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    border: 0px solid;
    background-color: #ffffff
}

input {
    font-size: 10px;
}

.top_list {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color:#596d9b;
    background-color: #d9e2ef
}

.line_list {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: normal;
    color:#596d9b;
    background-color: #ffffff
}

.Titulo {
    text-align: center;
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
}

.Conteudo {
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}
.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial" !important;
color:#FFFFFF;
text-align:center;
}

.titulo_coluna{
background-color:#596d9b;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center;
}

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border:1px solid #596d9b;
}

</style>

<!-- CSS Mapa BR -->
<style type="text/css">
<!--
div#mapa_pesquisa {
    font-family: sans-serif, Verdana, Geneva, Arial, Helvetica;
    font-size: 11px;
    line-height: 1.2em;
    color:#88A;
    background: white;
    top: 0;
    left: 0;
    padding: 30px 10px 15px 10px;
    display: none;
}

#frmdiv {
    margin: 10px;
    text-align: left;
    width: 552px;
}

#mapabr {height:340px;position:relative;float: left}
    #mapabr label, #mapabr select {margin-left: 1em;z-index:10}
    #mapabr span {
        padding: 2px 4px;
        color: white;
        background-color: #A10F15;
        text-shadow: 0 0 0 transparent;
        font: inherit
    }
    #mapabr h2 {margin-top: 1.5em}
    #mapabr area {cursor: pointer}
    #mapabr fieldset {
        border-radius: 5px;
        -moz-border-radius: 5px;
        -webkit-border-radius: 5px;
        height: 365px;
        width: 500px;
}

.cinza {#667}
.bold {
    font-weight: bold;
}

#abre_mapa_br
{
    display: block;
    cursor: pointer;
    font-size: 12px;
    height: 30px;
    width: 155px;
}
.abre_mapa_br
{
    color: #0183C9;
}
.abre_mapa_br_e
{
    color: #365093;
}

button, input[type=submit], input[type=button]
{
    cursor: pointer;
}
//-->
</style>

<?
function reduz_imagem($img, $max_x, $max_y, $nome_foto) {
    //pega o tamanho da imagem ($original_x, $original_y)
    list($width, $height) = getimagesize($img);
    $original_x = $width;
    $original_y = $height;
    // se a largura for maior que altura
    if($original_x > $original_y) {
       $porcentagem = (100 * $max_x) / $original_x;
    }
    else {
       $porcentagem = (100 * $max_y) / $original_y;
    }

    $tamanho_x = $original_x * ($porcentagem / 100);
    $tamanho_y = $original_y * ($porcentagem / 100);

    $image_p = imagecreatetruecolor($tamanho_x, $tamanho_y);
    $image   = imagecreatefromjpeg($img);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $width, $height);
    imagejpeg($image_p, $nome_foto, 65);
}

if ($login_login == "fabricio" && $login_fabrica == 3) {
    $sql =  "SELECT DISTINCT
                    tbl_posto.posto                                               ,
                    tbl_posto_fabrica.codigo_posto                AS posto_codigo ,
                    tbl_posto.nome                                AS posto_nome   ,
                    TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data         ,
                    tbl_credenciamento.dias                                       ,
                    TO_CHAR((tbl_credenciamento.data::date + tbl_credenciamento.dias),'DD/MM/YYYY') AS data_prevista
            FROM tbl_credenciamento
            JOIN tbl_posto          ON  tbl_posto.posto           = tbl_credenciamento.posto
            JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
                                    AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_credenciamento.fabrica = $login_fabrica
            AND   UPPER(tbl_credenciamento.status) = 'EM DESCREDENCIAMENTO'
            AND   UPPER(tbl_posto_fabrica.credenciamento) = 'EM DESCREDENCIAMENTO'
            AND   (tbl_credenciamento.data::date + tbl_credenciamento.dias) < current_date
            ORDER BY tbl_posto_fabrica.codigo_posto;";
    $resC = pg_query($con,$sql);
    if (pg_num_rows($resC) > 0) {
        echo "<br>";
        echo "<div id='mainCol'>";
        echo "<div class='contentBlockLeft' style='background-color: #FFCC00; width: 500;'>";
        echo "<br>";
        echo "<b>Postos com Status \"Em Descredenciamento\"</b>";
        echo "<br><br>";
        echo "<table class='formulario'>";
            echo "<tr class='titulo_coluna'>";
            echo "<td>Posto</td>";
            echo "<td>Data</td>";
            echo "<td>Dias</td>";
            echo "<td>Data Prevista</td>";
            echo "</tr>";
        for ($k = 0 ; $k < pg_num_rows($resC) ; $k++) {
            $cor = ($k % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

            echo "<tr class='Conteudo' bgcolor='$cor'>";
            echo "<td align='left'><a href='$PHP_SELF?posto=" . trim(pg_fetch_result($resC,$k,posto)) . "'>" . trim(pg_fetch_result($resC,$k,posto_codigo)) . " - " . trim(pg_fetch_result($resC,$k,posto_nome)) . "</a></td>";
            echo "<td>" . trim(pg_fetch_result($resC,$k,data)) . "</td>";
            echo "<td>" . trim(pg_fetch_result($resC,$k,dias)) . "</td>";
            echo "<td>" . trim(pg_fetch_result($resC,$k,data_prevista)) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<br>";
        echo "</div>";
        echo "</div>";
        echo "<br>";
    }
}
?>

<?
if(strlen($msg_erro) > 0 or !empty($msg)){
	$classe = (!empty($msg_erro)) ? "error":"msg_sucesso";
	$mensagem = (!empty($msg_erro)) ? $msg_erro:$msg;
        if (strpos($msg_erro, "tbl_posto_cnpj") ) $msg_erro = "CNPJ do posto já cadastrado.";
?>
<table style="margin: 0 auto; width: 800px; border: 0;" class='formulario' cellspacing="1" cellpadding="0">
<tr align='center'>
<td class='<? echo $classe;?>'>
        <? echo $mensagem; ?>
    </td>
</tr>
</table>
<? } ?>
<p>

<form name='frm_mapa' method='post' action='mapa_rede_new.php' target='_blank'>
<table style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3" class='formulario'>
    <tr class="titulo_tabela">
        <td>
            Mapa da Rede
        </td>
    </tr>
    <tr>
        <td align='center' style='color: #596D9B; font: Arial'>
            Para incluir um novo posto, preencha somente seu CNPJ e clique em gravar.
            <br>
            Faremos uma pesquisa para verificar se o posto já está cadastrado em nosso banco de dados.
        </td>
    </tr>
    <tr>
        <td align='center'>
        <span id='abre_mapa_br' class="abre_mapa_br_e">
                <b>Consulte o Mapa da Rede</b>
                <br />
                <img src="imagens/icon_mapa_e.gif" id="img_ab_e" style="width: 28px; height: 28px; margin: 0 auto;" />
                <img src="imagens/icon_mapa.gif" id="img_ab" style="width: 28px; height: 28px; margin: 0 auto; display: none;" />
        </span>
            <br />
        <div id='mapa_pesquisa'>
            <div id='frmdiv'>
                <fieldset for="frm_mapa_rede_gama" style="width: 550px;">
                    <legend>Pesquisa de Postos Autorizados</legend>
                    <div id='mapabr'>
                        <map name="Map2">
                            <area shape="poly" name="RS" coords="122,238,142,221,164,232,148,262">
                            <area shape="poly" name="SC" coords="143,214,172,215,169,235,143,219">
                            <area shape="poly" name="PR" coords="138,202,148,191,166,192,175,207,171,214,139,213">
                            <area shape="poly" name="SP" coords="152,187,162,173,182,174,186,187,188,194,197,190,197,198,177,206,168,190">

                            <area shape="poly" name="MS" coords="136,195,156,171,138,159,124,159,117,182">
                            <area shape="poly" name="MT" coords="117,151,143,151,160,127,160,106,120,105,111,101,98,102,107,117,100,131,102,142">
                            <area shape="poly" name="RO" coords="93,126,98,118,94,113,86,105,86,100,80,93,73,102,67,108,67,116,77,121">
                            <area shape="poly" name="AC" coords="50,106,10,91,13,101,23,104,29,104,30,112,44,113">
                            <area shape="poly" name="AM" coords="11,87,53,101,74,88,105,91,117,55,103,43,89,50,76,43,77,30,62,37,43,30,40,38,33,75,21,75,13,82">
                            <area shape="poly" name="RR" coords="74,13,74,18,82,25,84,41,93,40,102,31,96,21,97,9,90,11">
                            <area shape="poly" name="PA" coords="112,33,114,40,127,50,117,82,121,95,162,99,174,77,173,68,193,48,172,54,158,55,145,45,133,25">
                            <area shape="poly" name="AP" coords="145,25,153,23,157,13,164,29,153,41">
                            <area shape="poly" name="MA" coords="196,50,185,72,194,90,212,82,215,59">

                            <area shape="poly" name="TO" coords="179,83,165,120,189,128,185,101">
                            <area shape="poly" name="GO" coords="159,166,148,157,165,131,188,136,170,151">
                            <area shape="poly" name="PI" coords="201,92,216,86,223,64,228,85,219,98,207,99,206,107,199,107">
                            <area shape="poly" name="RJ" coords="206,201,202,190,214,189,218,181,226,187">
                            <area shape="poly" name="MG" coords="171,164,190,162,192,145,205,140,217,146,224,154,217,169,212,183,193,183,185,170">
                            <area shape="poly" name="ES" coords="236,167,228,162,221,177,226,183">
                            <area shape="poly" name="BA" coords="198,113,196,134,213,133,230,139,235,146,231,157,235,160,240,142,241,127,249,124,243,113,243,105,234,106,225,107,215,107,207,115">
                            <area shape="poly" name="CE" coords="230,59,235,86,241,86,252,70,239,61">
                            <area shape="poly" name="SE" coords="250,108,248,113,251,118,257,113,252,109">

                            <area shape="poly" name="AL" coords="266,102,258,104,251,102,260,110,266,104">
                            <area shape="poly" name="PE" coords="269,94,269,99,262,99,256,101,251,98,246,98,239,96,234,100,231,95,234,92,243,93,251,94,255,96">
                            <area shape="poly" name="PB" coords="269,85,262,85,257,88,253,85,248,87,257,90,263,91,268,89">
                            <area shape="poly" name="RN" coords="256,73,249,81,256,80,257,83,270,82,265,76">
                            <area shape="poly" name="DF" coords="168,162,171,153,183,149,182,161">
                        </map>
                        <p style='textalign: right; font-weight: bold;'>Selecione o Estado:</p>
                        <img src="../externos/mapa_rede/imagens/mapa_azul.gif" usemap="#Map2" border="0">
                    </div>
                    <label for='estado'>Selecione o Estado</label><br>
                    <select title='Selecione o Estado' name='mapa_estado' id='mapa_estado' onchange="montaComboCidade2();">
                        <option></option>
    <?              foreach ($estados as $sigla=>$estado_nome) {// a variavel $estado está em ../helpdesk/mlg_funciones.php
                        echo "\t\t\t\t<option value='$sigla'>$estado_nome</option>\n";
                    }
    ?>              </select>
                    <div id='sel_cidade'>
                        <label for='mapa_cidades'>Selecione uma cidade</label><br>
                        <select title='Selecione uma cidade' name='mapa_cidades' id='mapa_cidades'>
                            <option></option>
                        </select>
                    </div>
                </fieldset>
            </div>
        </div>

        <? if($login_fabrica == 59) { ?>
            País
            <select class='frm' name='pais'>
            <?  $sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_pais) AS contato_pais
                FROM tbl_posto_fabrica
                WHERE /* tbl_posto_fabrica.credenciamento = 'CREDENCIADO'AND  */
                tbl_posto_fabrica.fabrica = $login_fabrica
                ORDER BY contato_pais";
                $res = pg_query($con, $sql);
                if(pg_num_rows($res)>0){
                    echo "<option value='' selected>Todos</option>";
                    for($x=0; $x<pg_num_rows($res); $x++){
                        $nome_pais = pg_fetch_result($res, $x, contato_pais);
                        echo "<option value='$nome_pais'>";
                        echo $nome_pais;
                        echo "</option>";
                    }
                }
            ?>
            </select>
        <? }else{ ?>
            País
            <select class='frm' name='pais'>
                <option value='BR' selected>Brasil</option>
            </select>
        <? } ?>

        <? if($login_fabrica == 59) { ?>
            Estado
            <select class='frm' name='estado'>
            <?  $sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_estado) AS contato_estado
                FROM tbl_posto_fabrica
                WHERE /* tbl_posto_fabrica.credenciamento = 'CREDENCIADO' AND */
                tbl_posto_fabrica.fabrica = $login_fabrica
                ORDER BY contato_estado";
                $res = pg_query($con, $sql);
                if(pg_num_rows($res)>0){
                    echo "<option value='' selected>Todos</option>";
                    for($x=0; $x<pg_num_rows($res); $x++){
                        $nome_estado = pg_fetch_result($res, $x, contato_estado);
                        echo "<option value='$nome_estado'>$nome_estado</option>";
                    }
                }
            ?>
            </select>
        <? }else{ ?>
                Estado
                <select class='frm' name='estado' onchange="montaComboCidade(this.value)">
                    <option value='00' selected>Todos</option>
                    <option value='SP'         >São Paulo</option>
                    <option value='RJ'         >Rio de Janeiro</option>
                    <option value='PR'         >Paraná</option>
                    <option value='SC'         >Santa Catarina</option>
                    <option value='RS'         >Rio Grande do Sul</option>
                    <option value='MG'         >Minas Gerais</option>
                    <option value='ES'         >Espírito Santo</option>
                    <option value='BR-CO'      >Centro-Oeste</option>
                    <option value='BR-NE'      >Nordeste</option>
                    <option value='BR-N'       >Norte</option>
                </select>
        <? } ?>
                Cidade
                <select class='frm' name='cidade' id='cidade'>
                <?  $sql = "SELECT DISTINCT UPPER(trim(tbl_posto_fabrica.contato_cidade)) AS contato_cidade
                    FROM tbl_posto_fabrica
                    WHERE /* tbl_posto_fabrica.credenciamento = 'CREDENCIADO' AND */
                    tbl_posto_fabrica.fabrica = $login_fabrica
                    ORDER BY contato_cidade";
                    $res = pg_query($con, $sql);
                    if(pg_num_rows($res)>0){
                        echo "<option value='' selected>Todos</option>";
                        for($x=0; $x<pg_num_rows($res); $x++){
                            $nome_cidade = pg_fetch_result($res, $x, contato_cidade);
                            echo "<option value='$nome_cidade'>";
                            echo $nome_cidade;
                            echo "</option>";
                        }
                    }
                ?>
                </select>

                <input class='frm' type='submit' name='btn_mapa' id='btn_mapa' value='mapa'>
                </font>
            </td>
        </tr>
    </table>
</form>

<br/ >

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>" <?if ($login_fabrica == 50) {?> enctype='multipart/form-data' <?}?>>

<?php
if (!empty($msg_erro) and !empty($novo_posto)) {
    unset($posto);
}
?>
<input type="hidden" name="posto" value="<? echo $posto ?>">

<?
    echo "<TABLE class='formulario' style='margin: 0 auto; width: 700px; border: 0;' >";
    echo "<TR>";
    echo "<TD align='left'><font size='2' face='verdana' ";
    if ($credenciamento == 'CREDENCIADO')
        $colors = "color:#3300CC";
    else if ($credenciamento == 'DESCREDENCIADO')
        $colors = "color:#F3274B";
    else if ($credenciamento == 'EM DESCREDENCIAMENTO')
        $colors = "color:#FF9900";
    else if ($credenciamento == 'EM CREDENCIAMENTO')
        $colors = "color:#006633";
    # HD 110541
    if($login_fabrica==11 AND strlen($data_credenciamento)>0){
        if ($credenciamento == 'CREDENCIADO')
            $show_date_credenciamento = "EM: $data_credenciamento";
        else if ($credenciamento == 'DESCREDENCIADO'){
            $sql_X2 = "select TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data from tbl_credenciamento where fabrica=11 and posto=$posto and status='CREDENCIADO'";
            $res_X2 = pg_query ($con,$sql_X2);
            if (pg_num_rows ($res_X2) > 0) {
                    $data_credenciamento_2   = trim(pg_fetch_result($res_X2,0,data));
                    $show_date_credenciamento .= "CREDENCIADO EM: $data_credenciamento_2 E DESCREDENCIADO EM $data_credenciamento";
            }else{
                $show_date_credenciamento .= "DESCREDENCIADO EM $data_credenciamento";;
            }
        }
        else if ($credenciamento == 'EM DESCREDENCIAMENTO')
            $show_date_credenciamento = "DESDE: $data_credenciamento";
        else if ($credenciamento == 'EM CREDENCIAMENTO')
            $show_date_credenciamento = "DESDE: $data_credenciamento";
        else if ($credenciamento == 'REPROVADO') {
            $show_date_credenciamento = "EM: $data_credenciamento";
        }
    }
    echo "><B>  ";
    echo "<a href='credenciamento.php?codigo=$codigo&posto=$posto&listar=3' style='$colors'>";
    # HD 110541
    if($login_fabrica==11 AND $credenciamento == 'DESCREDENCIADO'){
        echo $show_date_credenciamento;
    }else{
        echo $credenciamento."  ".$show_date_credenciamento;
    }
    echo "</B></font></TD>";

    echo "<td align='right' nowrap>";
//  if (strlen ($posto) > 0 and $login_fabrica <> 3) {
//  HD 148558 pediu para colocar também para Britânia

    if (strlen ($posto) > 0 ){
        $resX = pg_query ("SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto FROM tbl_posto_fabrica JOIN tbl_posto ON tbl_posto_fabrica.distribuidor = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.posto = $posto");

        if (pg_num_rows ($resX) > 0) {
            echo "Distribuidor: " . pg_fetch_result ($resX,0,codigo_posto) . " - " . pg_fetch_result ($resX,0,nome) ;
        }else{
            echo "Atendimento direto";
        }
    }
    echo "</td>";

    echo "</TR>";
    echo "</TABLE>";
?>
<? if($login_fabrica == 91){
        $sql = "SELECT valor_km FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
        $res = pg_query($con,$sql);
        $valor_padrao = pg_result($res,0,0);
        if(!empty($valor_padrao) AND $valor_padrao > 0){
?>
            <div class='texto_avulso'>
                Caso não seja preenchido o campo "Valor/KM" o sistema assumirá o valor padrão R$ <? echo number_format($valor_padrao,2,',','.'); ?>. <br>Para alterar o valor  padrão, entre em contato com a Telecontrol.
            </div>
            <br />
    <? } ?>
<? } ?>
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <td colspan="5" class='titulo_tabela'>
            Informações Cadastrais
        </td>
    </tr>

    <?
    //HD 11308 11/1/2008
    if($login_fabrica == 15){?>
    <tr align='left'>
        <td>CNPJ</td>
        <td>I.E.</td>
        <td>I.M.</td>
    </tr>
    <tr align='left'>
        <td><input class='frm' type="text" name="cnpj" id="cnpj" style="float: left; width: 143px;" maxlength="18" value="<? echo $cnpj ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a></td>
        <td><input class='frm' type="text" name="ie" style="float: left; width: 143px;" maxlength="20" value="<? echo $ie ?>" ></td>
        <td><input class='frm' type='text' name='im' style="float: left; width: 173px;" maxlength='40' value="<? echo $im ?>"></td>
    </tr>

    <tr align='left'>
        <td>Telefone</td>
        <td align="left">Telefone 2</td>
        <td align="left">Telefone 3</td>
        <td align="left">Celular</td>

    </tr>

    <tr align='left'>
        <td align="left"><input class='frm telefone' type="text" name="fone" style="float: left; width: 106px;" maxlength="20" value="<? echo $fone ?>"></td>
        <td align="left">
            <input type="text" class='frm telefone' name="fone2" id="fone2" style="float: left; width: 106px;" maxlength="20" value="<?echo $fone2?>" />
        </td>
        <td align="left">
            <input type="text" class='frm telefone' name="fone3" id="fone3" style="float: left; width: 106px;" maxlength="20" value="<?echo $fone3?>" />
        </td>
        <td align="left">
            <input type="text" class='frm telefone' name="contato_cel" id="contato_cel" style="float: left; width: 106px;" maxlength="20" value="<?echo $contato_cel?>" />
        </td>
    </tr>

    <tr>

        <td align="left">Fax</td>
        <td style="text-align: left;">Contato</td>
    </tr>

    <tr>

        <td align="left"><input class='frm telefone' type="text" name="fax" style="float: left; width: 106px;" maxlength="20" value="<? echo $fax ?>"></td>
        <td align="left"><input class='frm' type="text" name="contato"  style="float: left; width: 143px;" maxlength="30" value="<? echo $contato_nome ?>"></td>
    </tr>

    <?}else{?>
    <tr align='left'>
        <?php if($login_fabrica == 52 || $login_fabrica == 85){?>
            <td>CNPJ / CPF</td>
        <?php }else{?>
            <td>CNPJ</td>
        <?php }?>
        <td>I.E.</td>
    <?php
    if(in_array($login_fabrica, array(81, 114))){
        ?>
        <td>Fone</td>
        <td>Fone 2</td>
    </tr>
    <?php
    }
    else if ($login_fabrica == 40)
    {
    ?>
        <td>Fone</td>
        <td>Fone 2</td>
    <?
    }
    else{
        ?>
        <td>Fone</td>
        <td>Fax</td>
    <?php
    }
    ?>
    </tr>
    <?php
        if(in_array($login_fabrica, array(81, 114))){
        ?>
    <tr align='left'>
        <td nowrap><input class='frm' type="text" name="cnpj" id="cnpj" style="float: left; width: 143px" maxlength="18" value="<? echo $cnpj ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a></td>
        <td><input class='frm' type="text" name="ie" style="float: left; width: 143px" maxlength="15" value="<? echo $ie ?>" ></td>
        <td><input class='frm telefone' type="text" name="fone" style="float: left; width: 106px;" maxlength="12" value="<? echo $fone ?>"></td>
        <td><input class='frm telefone' type="text" name="fone2" style="float: left; width: 106px;" maxlength="12" value="<? echo $fone2 ?>"></td>
    </tr>
    <tr align='left'>
        <td>Fax</td>
        <td>Contato</td>
    </tr>
    <tr align='left'>
        <td><input class='frm telefone' type="text" name="fax" style="float: left; width: 106px;" maxlength="15" value="<? echo $fax ?>"></td>
        <td><input  class='frm'type="text" name="contato" size="15" maxlength="15" value="<? echo $contato_nome ?>" style="width:100px"></td>
    </tr>
        <?php
        }
        else if ($login_fabrica == 40)
        {
        ?>
            <td nowrap><input class='frm' type="text" name="cnpj" id="cnpj" style="float: left; width: 143px;" maxlength="18" value="<? echo $cnpj ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a></td>
            <td><input class='frm' type="text" name="ie" style="float: left; width: 143px;" maxlength="20" value="<? echo $ie ?>" ></td>
            <td><input class='frm telefone' type="text" name="fone" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone ?>"></td>
            <td><input class='frm telefone' type='text' name='fone2' style="float: left; width: 106px;" maxlength='15' value='<?=$fone2?>'></td>
        </tr>
        <tr align='left'>
            <td>Fax</td>
            <td>Contato</td>
        </tr>
        <tr align='left'>
            <td><input  class='frm'type="text" name="contato" style="float: left; width: 106px;" maxlength="15" value="<? echo $contato_nome ?>" style="width:100px"></td>
            <td><input class='frm telefone' type="text" name="fax" size="15" maxlength="15" value="<? echo $fax ?>"></td>
        </tr>
        <?
        }
        else{
        ?>
        <tr>
            <td nowrap>
                <input class='frm' type="text" name="cnpj" id="cnpj" style="float: left; width: 143px;" maxlength="18" value="<? echo $cnpj ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a>
            </td>
            <td><input class='frm' type="text" name="ie" style="float: left; width: 143px;" maxlength="20" value="<? echo $ie ?>" ></td>
            <td><input class='frm telefone' type="text" name="fone" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone ?>"></td>
            <td><input class='frm telefone' type="text" name="fax" style="float: left; width: 106px;" maxlength="15" value="<? echo $fax ?>"></td>
        </tr>

        <?php
        if(!empty($fone) AND strlen(preg_replace('/(\D)/i', '', $fone)) < 10){?>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td colspan='2' style='color: #F00; text-align: left; font-size: 11px;'>
                <b>Telefone Inválido!</b> <br />O formato do telefone deve ser: (14) 3402 6588
            </td>
            <td>&nbsp;</td>
        </tr>
        <?php }?>
        <?php
        }
    }
    $colspan = ($login_fabrica == 15) ? 1 : 3;

    ?>
    <?php if ($login_fabrica == 30): ?>
    <tr>
        <td align="left">Fone Celular 1</td>
        <td align="left" colspan="3">Fone Celular 2</td>
    </tr>
    <tr>
        <td align="left">
            <input class='frm telefone' type="text" name="fone2" id="fone2" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone2 ?>">
        </td>
        <td align="left" colspan="3">
            <input class='frm telefone' type="text" name="fone3" id="fone3" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone3 ?>">
        </td>
    </tr>
    <?php endif ?>

    <tr style="text-align: left;">
        <?
        if (!in_array($login_fabrica, Array(15, 40, 81, 114)))
        {
        ?>
            <td>Contato</td>
        <?
        }
        ?>
        <td>Código</td>
        <td colspan="2">Razão Social</td>
    </tr>
    <tr>
        <?
        if (!in_array($login_fabrica, Array(15, 40, 81, 114)))
        {
        ?>
            <td>
            <input  class='frm'type="text" name="contato" size="15" style="float: left;" maxlength="15" value="<? echo $contato_nome ?>" style="width:100px">
        </td>
        <?
        }
        ?>
        <td align='left'>
            <input class='frm' type="text" name="codigo" size="14" style="float: left;" maxlength="14" value="<? echo $codigo ?>" style="width:150px"<?if(strlen($posto) > 0 and $login_fabrica == 45 AND strlen(trim($codigo)) > 0)  echo " readonly='readonly' ";?>><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'codigo')"></a>
        </td>
        <td colspan="2" align='left'>
            <input class='frm' type="text" name="nome" style="float: left; width:300px;" maxlength="60" value="<? if ($login_fabrica == 50) { echo strtoupper($nome); } else { echo $nome; } ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'nome')"></a>
        </td>
    </tr>
    <tr><td>&nbsp;</td></tr>
    <tr>
        <td colspan="5">
            <button type="button" onclick="location.href='<? echo $PHP_SELF ?>?listar=todos#postos'">Listar Todos os Postos Cadastrados</button>
        </td>
    </tr>
    <?php if ($posto and $login_fabrica == 1): ?>
        <tr>
            <td colspan="5" align="center"><button id="mostra_opt_extrato">Alterar geração de extrato do posto</button></td>
        </tr>
    <?php endif; ?>
</table>
<?

//17/7/2009 MLG
    $colspan = 3;   // Calcula o 'colspan' da tD do "país"
    if ($login_fabrica==2) $colspan--;    //  Um a menos, porque tem 'data nomeação'
    if ($login_fabrica==2) $colspan--;    //  Um a menos, porque tem 'atende consumidor'
?>

<?php $array_fabrica_inspetores = array(3, 30); ?>

<?php if ( hdPermitePostoAbrirChamado() or in_array($login_fabrica, $array_fabrica_inspetores) ): ?>
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>

        <td class='titulo_coluna'>
            <? echo ($login_fabrica == 1) ? "Atendente de Callcenter Para este Posto" : "Inspetor para esse posto";?>
        </td>
    </tr>
    <tr>
        <td align="center"> <em>
        <? echo ($login_fabrica == 1) ? "Selecione o atendente para quem serão gerados os chamados abertos por este posto de atendimento" : "Selecione o inspetor para esse posto";?></em> </td>
    </tr>
    <tr>
        <td>
            <?php
                if ($login_fabrica == 3) {
                    $sqlInspetor = "SELECT admin, login, nome_completo FROM tbl_admin WHERE admin_sap = 't' AND ativo = 't' AND fabrica = $login_fabrica ORDER BY nome_completo";
                    $qryInspetor = pg_query($con, $sqlInspetor);

                    if (is_resource($qryInspetor)) {
                        $aAtendentes = array();
                        while ($fetch = pg_fetch_assoc($qryInspetor)) {
                            $aAtendentes[] = $fetch;
                        }
                    }
                } else {
                    // ! Buscar atendentes  de posto
                    // HD 121248 (augusto)
                    $aAtendentes = hdBuscarAtendentes();
                }
            ?>
            <select class='frm' name="admin_sap" id="admin_sap">
                <option value=""></option>
                <?php foreach($aAtendentes as $aAtendente): ?>
                    <?php if($login_fabrica == 42){ ?>
                        <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo strtoupper($aAtendente['login']); ?></option>
                    <?php }else{ ?>
                        <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo empty($aAtendente['nome_completo']) ? $aAtendente['login'] : $aAtendente['nome_completo'] ; ?></option>
                    <?php } ?>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <? if ($login_fabrica == 3) { ?>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td align="center">
            <em>
                Selecione o atendente para quem serão gerados os chamados abertos por este posto de atendimento
            </em>
        </td>
    </tr>
    <tr>
        <td>
            <?php
                $bAtendentes = hdBuscarAtendentes();
            ?>
            <select class='frm' name="admin_sap_especifico" id="admin_sap_especifico">
                <option value=""></option>
                <?php foreach($bAtendentes as $bAtendente): ?>
                        <option value="<?php echo $bAtendente['admin']; ?>" <?php echo ($bAtendente['admin'] == $admin_sap_especifico) ? 'selected="selected"' : '' ; ?>><?php echo empty($bAtendente['nome_completo']) ? $bAtendente['login'] : $bAtendente['nome_completo'] ; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <? } ?>
    <tr>
        <td>&nbsp;</td>
    </tr>
</table>
<?php endif; ?>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <? if($login_fabrica==20) { ?>
        <td colspan="1">Desconto Acessório</td>
        <td colspan="1">Imposto IVA</td>
        <td colspan="1">Custo Administrativo</td>
        <? } ?>
        <td colspan="<?=$colspan?>">País</td>
        <? if ($login_fabrica==2) {
        /*  hd 21496 - Francisco - campo Data da Nomeação para Dynacom
            HD 167192- MLG - A Dynacom pode fazer com que o posto não apareça na pesquisa de postos,
                             tanto no Call-Center quanto na web (telecontrol / mapa_rede ...)
            PAra as fábricas que querem controlar se aparecem os postos na pesquisa
       */?>
        <td>Data Nomeação</td>
        <td>Atende Consumidor</td>
        <? } ?>
    </tr>

    <tr>
        <?if($login_fabrica==20){ ?>
        <td><input class='frm' type="text" name="desconto_acessorio" size="5" maxlength="5" value="<? echo $desconto_acessorio ?>" >%</td>
        <td><input class='frm' type="text" name="imposto_al" size="5" maxlength="5" value="<? echo $imposto_al ?>" >%</td>
        <td><input class='frm' type="text" name="custo_administrativo" size="5" maxlength="5" value="<? echo $custo_administrativo ?>" >%</td>

        <? } ?>

        <td colspan="<?=$colspan?>">
        <?php if (!empty($_GET['posto'])) {
            $sql = "SELECT pais, nome
                    FROM tbl_pais
                    WHERE pais = '$pais'
                    ORDER BY nome";

            $res = pg_query($con, $sql);

            $nome_pais= pg_fetch_result($res, 0, 'nome');
            $pais= pg_fetch_result($res, 0, 'pais');
            ?>
            <input type="hidden" name="pais" value="<?=$pais?>">
            <?
            echo "<b>$nome_pais</b>";
        } else {

         ?>
        <select name='pais' class='frm'>
        <?  $sql = "SELECT pais, nome
                    FROM tbl_pais
                    ORDER BY nome";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res)>0){
                echo "<option value=''></option>";
                for($x=0; $x<pg_num_rows($res); $x++){
                    $aux_pais = pg_fetch_result($res, $x, pais);
                    $nome_pais= pg_fetch_result($res, $x, nome);

                    $selected_pais = " ";
                    if ($pais == $aux_pais) $selected_pais = " selected ";

                    echo "<option value='$aux_pais' $selected_pais>";
                    echo $nome_pais;
                    echo "</option>";
                }
            }
        ?>
        </select>
        <?}?>
        </td>
        <?if ($login_fabrica==2){ ?>
        <td>
        <!-- hd 21496 - Francisco - campo Data da Nomeação para Dynacom -->
        <? include "javascript_calendario.php"; ?>
        <script type="text/javascript" charset="utf-8">
            $(function()
            {
                $("input[rel='data_mask']").mask("99/99/9999");
                $("input[name=data_nomeacao]").datePicker({startDate : "01/01/2000"});

            });
        </script>
        <? if($data_nomeacao=='01/01/0001' OR $data_nomeacao=='0001-01-01') {
            $data_nomeacao ="";
        }
        $atende_consumidor_checked = ($atende_consumidor<>'f')? "CHECKED" :"";
        ?>
            <input class='frm' type="text" name="data_nomeacao" rel='data_mask' size="12" maxlength="16"
              value="<? echo $data_nomeacao ?>" ></td>
            <td><input class='frm' type="checkbox" name="atende_consumidor" <?=$atende_consumidor_checked?>
                      title='Se desmarcar, o posto não irá a aparecer na pesquisa da rede de postos autorizados'>
        </td>
        <?}?>
    </tr>
<!--
<tr>
<td>
<center>

<input type='hidden' name='btn_acao' value=''>
<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>
</center>
</td></tr> -->
</table>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
    <tr align='left'>
        <td>CEP</td>
        <td>Endereço</td>
        <td>Número</td>
        <td>Complemento</td>
    </tr>
    <tr align='left'>
        <td><input class='frm' type="text" name="cep" id="cep" size="10" maxlength="10" value="<? echo $cep ?>" onblur=" buscaCEP(this.value, this.form.endereco, this.form.bairro, this.form.cidade, this.form.estado);"></td>
        <td><input class='frm' type="text" name="endereco" size="30" maxlength="50" value="<? echo $endereco ?>"></td>
        <td><input class='frm' type="text" name="numero" size="10" maxlength="10" value="<? echo $numero ?>"></td>
        <td><input class='frm' type="text" name="complemento" size="5" maxlength="20" value="<? echo $complemento ?>"></td>
    </tr>
    <tr align='left'>
        <td>Bairro</td>
        <td>Cidade</td>
        <td>Estado</td>
    </tr>
    <tr align='left'>
        <td><input class='frm' type="text" name="bairro" size="20" maxlength="20" value="<? echo $bairro ?>"></td>
        <td><input  class='frm'type="text" name="cidade" size="20" maxlength="30" value="<? echo $cidade ?>"></td>
        <td>
            <select name="estado" id="estado" style="font-size:9px" class="frm">
                <option value=""   <?php if (strlen($estado) == 0)    echo " selected ";?> >TODOS OS ESTADOS</option>
                <option value="AC" <?php if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
                <option value="AL" <?php if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
                <option value="AM" <?php if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
                <option value="AP" <?php if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
                <option value="BA" <?php if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
                <option value="CE" <?php if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
                <option value="DF" <?php if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
                <option value="ES" <?php if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
                <option value="GO" <?php if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
                <option value="MA" <?php if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
                <option value="MG" <?php if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
                <option value="MS" <?php if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
                <option value="MT" <?php if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
                <option value="PA" <?php if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
                <option value="PB" <?php if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
                <option value="PE" <?php if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
                <option value="PI" <?php if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
                <option value="PR" <?php if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
                <option value="RJ" <?php if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
                <option value="RN" <?php if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
                <option value="RO" <?php if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
                <option value="RR" <?php if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
                <option value="RS" <?php if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
                <option value="SC" <?php if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
                <option value="SE" <?php if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
                <option value="SP" <?php if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
                <option value="TO" <?php if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
            </select>
        </td>
    </tr>
</table>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr align='left'>
        <td style="width: 232px;">E-mail</td>
        <td>Capital/Interior</td>
        <?if($login_fabrica == 7){?><td>Posto Empresa</td><?}?>
        <td><?echo "Tipo do Posto";?></td>
        <? if($login_fabrica == 1){ ?>
        <td>Categoria Posto</td>
        <? } ?>
        <!-- <td>PEDIDO EM GARANTIA</td> -->
        <?if($login_fabrica == 20){?><td>ER</td><?}
        if (!in_array($login_fabrica,array(86,94,122,81,114,124,123,124,125,128,136))) {//HD 387824?>
            <td>Desconto</td><?php
        }
        if ($login_fabrica == 11) {// HD 110541?>
            <td width = '34%'>Atendimento</td>
        <? } ?>
        <?if(in_array($login_fabrica,array(35,50,52,72, 24,91, 94, 74,15,120,131))){?><td>Valor/km</td><?}?>
        <? // HD 12104
        if($login_fabrica == 14){ ?>
        <td>Liberar 10%</td>
        <? } ?>
        <? // HD 17601
        if($login_fabrica == 45){ ?>
        <td>Qtde Itens</td>
        <? } ?>
        <? if($login_fabrica == 74){ // HD 384120?>
        <td>Contribuinte de ICMS</td>
        <? } ?>

    </tr>
    <tr align='left'>
        <td>

            <input class='frm' type="text" name="email" style="width: 200px;" maxlength="50" value="<? echo $email ?>">

        </td>
        <td>
<?      if ($posto) { ?>
            <span style='text-transform: capitalize;padding: 2px 1ex' class='frm'
                  title='Se você precisa alterar esta informação do POSTO, por favor contate com nosso Suporte.'><?=strtolower($capital_interior)?></span>
<?      } else { ?>
            <select class='frm' name='capital_interior' size='1'>
                <option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> >Capital</option>
                <option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> >Interior</option>
            </select>
<?      } ?>
        </td>
        <? if($login_fabrica==7){
            if(strlen($posto_empresa)>0){
            $sqlx = "SELECT codigo_posto
                    FROM tbl_posto_fabrica
                    WHERE posto   = $posto_empresa
                    AND   fabrica = $login_fabrica";
            $resx = pg_query($con, $sqlx);
            if(pg_num_rows($resx)>0){
                $posto_empresa = pg_fetch_result($resx, 0, codigo_posto);
            }
           }
            ?>
            <td><input class='frm' type="text" name="posto_empresa" size="10" maxlength="15" value="<? echo $posto_empresa ?>"></td>
        <?}?>
        <td>
            <select class='frm' name='tipo_posto' size='1' <?if($login_fabrica == 42){?>onchange="entrega_tecnica_check($(this).find('option:selected').attr('rel'));"<?}?> >
                <?
                    if ($login_fabrica == 94)
                        $order = " DESC";
                     echo $sql = "SELECT *
                            FROM   tbl_tipo_posto
                            WHERE  tbl_tipo_posto.fabrica = $login_fabrica
                            AND tbl_tipo_posto.ativo = 't'
                            ORDER BY tbl_tipo_posto.descricao $order";
                    $res = pg_query ($con,$sql);
                        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                            echo "<option rel='".pg_fetch_result($res, $i, tipo_revenda)."'' value='" . pg_fetch_result ($res,$i,tipo_posto) . "' ";
                                if ($tipo_posto == pg_fetch_result ($res,$i,tipo_posto)) echo " selected ";
                            echo ">";
                            echo pg_fetch_result ($res,$i,descricao);
                    echo "</option>";
                    }
                ?>
            </select>
        </td>
        <?php if ($login_fabrica == 1){



                $checkedA = (strtolower($categoria_posto) == 'autorizada') ? "SELECTED" : "";
                $checkedL = (strtolower($categoria_posto) == 'locadora') ? "SELECTED" : "";
                $checkedAL = (strtolower($categoria_posto) == 'locadora autorizada') ? "SELECTED" : "";
                $checkedPC = (strtolower($categoria_posto) == "pré cadastro") ? "SELECTED" : "";


        ?>

                <td>
                    <select name="categoria_posto" class="frm">
                        <option value=""></option>
                        <option value="Autorizada" <?=$checkedA?>>Autorizada</option>
                        <option value="Locadora" <?=$checkedL?>>Locadora</option>
                        <option value="Locadora Autorizada" <?=$checkedAL?>>Locadora Autorizada</option>
                        <option value="Pr&eacute; Cadastro" <?=$checkedPC?>>Pré Cadastro</option>
                    </select>
                </td>
        <?php }?>

        <?if($login_fabrica == 20){?>
        <td>
            <select name='escritorio_regional' size='1'>
                <?
                    $sql = "SELECT *
                            FROM   tbl_escritorio_regional
                            WHERE  fabrica = $login_fabrica
                            ORDER BY descricao";
                    $res = pg_query ($con,$sql);
                        echo "<option value=''></option>";
                        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                            echo "<option value='" . pg_fetch_result ($res,$i,escritorio_regional) . "' ";
                                if ($escritorio_regional == pg_fetch_result ($res,$i,escritorio_regional)) echo " selected ";
                            echo ">";
                            echo pg_fetch_result ($res,$i,descricao);
                    echo "</option>";
                    }
                ?>
            </select>
        </td>
        <?}?>
<!--
        <td>
            <select name='pedido_em_garantia' size='1'>
                <option value=''></option>
                <option value='t' <? if ($pedido_em_garantia == "t") echo " selected "; ?> >Sim</option>
                <option value='f' <? if ($pedido_em_garantia == "f") echo " selected "; ?> >Não</option>
            </select>
        </td>
--><?php
        if (!in_array($login_fabrica,array(86,94,122,81,114,124,123,124,125,128,136))) {//HD 387824?>
            <td nowrap><input class='frm' type="text" name="desconto" size="5" maxlength="5" value="<?=$desconto?>">%</td><?php
        }
        // HD 110541
        if($login_fabrica==11){?>
        <td width = '33%'>
            <select name='atendimento_lenoxx'
            <?php
                if (isset($readonly) and strlen($atendimento_lenoxx)>0){
                    echo " DISABLED";
                } ?>>
                <option selected></option>
                <option value='b'   <? if ($atendimento_lenoxx == 'b')   echo "selected"; ?>>Balcão</option>
                <option value='r'   <? if ($atendimento_lenoxx == 'r')   echo "selected"; ?>>Revenda</option>
                <option value='t'   <? if ($atendimento_lenoxx == 't')   echo "selected"; ?>>Balcão/Revenda</option>
            </select>
        </td>
        <? } ?>
        <?if(in_array($login_fabrica,array(35,50,52,72, 24,91, 94, 74,15,120,131))){?>
            <td><input class='frm' type="text" name="valor_km" size="5" maxlength="5" value="<? echo $valor_km?>" ></td>
        <?}?>
        <? // HD 12104
        if($login_fabrica == 14){?>
        <td align='center' nowrap>
        <input type='checkbox' class='frm' name='imprime_os' value='imprime_os' <? if($imprime_os == 't') echo " checked "; ?> >
        </td>
        <? } ?>
        <? // HD 17601
        if($login_fabrica == 45){?>
        <td>
        <input type='text' class='frm' name='qtde_os_item' size='2' maxlength='3' value='<? echo "$qtde_os_item";?>'>
        </td>
        <? } ?>
        <? if($login_fabrica == 74){ # HD 384120?>
        <td>
        <input type='radio' class='frm' name='contribuinte_icms'  value='t' <?if ($contribuinte_icms == 't') echo "checked";?>>Sim
        <input type='radio' class='frm' name='contribuinte_icms'  value='f' <?if ($contribuinte_icms == 'f') echo "checked";?>>Não
        </td>
        <? } ?>
    </tr>
    <?php
    if ($login_fabrica == 15) {
    ?>
        <tr>
            <td align="left">
                Email adicional
            </td>
        </tr>
        <tr>
            <td align="left">
                <input type="text" name="email2" id="email2" size="30" maxlength="50" class="frm" value="<?php echo $email2 ?>" >
            </td>
        </tr
    <?php
    }
    /* HD 407694 */
    if($login_fabrica == 1){
        ?>
        <tr>
            <td align="left">Acréscimo Tributário</td>
            <td colspan="3" align="left">Taxa Administrativa</td>
        </tr>
        <tr>
            <td align="left">
                <input type="text" maxlength="8" size="12" value="<?php echo number_format($acrescimo_tributario,3,',','');?>" name="acrescimo_tributario" class="frm msk_valor">
            </td>
            <td colspan="3" align="left">
		<?php
	    	   if($taxa_administrativa > 0){
			   $taxa_administrativa = ($taxa_administrativa - 1) * 100;
		   }
                ?>
                <input type="text" maxlength="8" size="12" value="<?php echo number_format($taxa_administrativa,2,',','');?>" name="taxa_administrativa" class="frm msk_valor">%
            </td>
        </tr>
    <?php }?>
</table>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
    <tr align='left'>
        <td style="width: 232px;">Nome Fantasia</td>
        <td>Senha</td><?php
        if ($login_fabrica <> 86 and $login_fabrica <> 94) {//HD 387824?>
            <td>Transportadora</td>

            <?php
        }?>
        </tr>
        <tr align='left'>
        <td>
            <input class='frm' type="text" name="nome_fantasia" size="30" maxlength="50" value="<? echo $nome_fantasia ?>" >
        </td>
        <td style="width: 100px;" align="left"><input class='frm' type="text" name="senha" size="10" maxlength="10" value="<? echo $senha ?>"></td><?php
        if ($login_fabrica <> 86 and $login_fabrica <> 94) {//HD 387824?>
            <td align='left'>
                <select class='frm' name="transportadora" style="width: 200px;">
                    <option selected></option><?php
                    if($login_fabrica == 11){
                        $sql = "SELECT  tbl_transportadora.transportadora        ,
                                        tbl_transportadora.nome                  ,
                                        tbl_transportadora.cnpj
                                FROM    tbl_transportadora
                                JOIN    tbl_transportadora_fabrica USING(transportadora)
                                WHERE   tbl_transportadora_fabrica.fabrica = $login_fabrica
                                AND     tbl_transportadora_fabrica.ativo  = 't' ";
                        $res = pg_query ($con,$sql);
                        if (pg_num_rows ($res) > 0) {
                            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                                echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
                                if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
                                echo ">";
                                echo pg_fetch_result($res,$i,cnpj) ." - ".substr (pg_fetch_result($res,$i,nome),0,25);
                                echo "</option>\n";
                            }
                        }
                    }else{
                        $sql = "SELECT  tbl_transportadora.transportadora        ,
                                        tbl_transportadora.nome                  ,
                                        tbl_transportadora_fabrica.codigo_interno
                                FROM    tbl_transportadora
                                JOIN    tbl_transportadora_padrao USING(transportadora)
                                JOIN    tbl_transportadora_fabrica USING(transportadora)
                                WHERE   tbl_transportadora_padrao.fabrica = $login_fabrica
                                AND     tbl_transportadora_fabrica.ativo  = 't'
                                ORDER BY tbl_transportadora.nome";
                        $res = pg_query ($con,$sql);
                        if (pg_num_rows ($res) > 0) {
                            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                                echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
                                if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
                                echo ">";
                                echo pg_fetch_result($res,$i,codigo_interno) ." - ".pg_fetch_result($res,$i,nome);
                                echo "</option>\n";
                            }
                        }
                        else
                        {
                            $sql = "SELECT  tbl_transportadora.transportadora        ,
                                        tbl_transportadora.nome                  ,
                                        tbl_transportadora_fabrica.codigo_interno
                                FROM    tbl_transportadora
                                JOIN    tbl_transportadora_fabrica USING(transportadora)
                                WHERE   tbl_transportadora_fabrica.fabrica = $login_fabrica
                                AND     tbl_transportadora_fabrica.ativo  = 't'
                                ORDER BY tbl_transportadora.nome";
                            $res = pg_query ($con,$sql);
                            if (pg_num_rows ($res) > 0) {
                                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                                    echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
                                    if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
                                    echo ">";
                                    echo pg_fetch_result($res,$i,codigo_interno) ." - ".pg_fetch_result($res,$i,nome);
                                    echo "</option>\n";
                                }
                            }
                        }
                    }?>
                </select>
            </td>
            <?php
        }?>
        </tr>

        <tr align='left'>
        <td>Região Suframa</td>
        <td>Item Aparência</td>
<?
        if($login_fabrica == 30){
?>
        <td>Valor Fixo KM</td>
<?
        }
?>
    </tr>
    <tr align='left'>
        <td>
            Sim<INPUT TYPE="radio" NAME="suframa" VALUE = 't' <?if ($suframa == 't') echo "checked";?>>
            Não<INPUT TYPE="radio" NAME="suframa" VALUE = 'f' <?if ($suframa == 'f' or strlen($suframa) == 0) echo "checked";?>>
        </td>
        <td><acronym title='Esta informação trabalha em conjunto com a informação item de aparência no cadastro de peças. Deixando setado como SIM, este posto vai conseguir lançar peças de item de aparência nas Ordens de Serviço de Revenda.'>
            SIM<INPUT TYPE="radio" NAME="item_aparencia" VALUE = 't' <?if ($item_aparencia == 't') echo "checked";?>>
            NÃO<INPUT TYPE="radio" NAME="item_aparencia" VALUE = 'f' <?if ($item_aparencia <> 't') echo "checked";?>>
            </acronym>
    <?  //5595 link para mostrar os postos que atendem revenda para Tectoy
        if($login_fabrica==6) {
            echo "<BR><a href=\"javascript: posto_revenda('$login_fabrica')\" rel='ajuda' title='Clique aqui para ver os postos de revenda'><font size=1>Listar postos</font></a>";
        }
    ?>
        </td>
<?
        if($login_fabrica == 30){
            $sqlKm = "  SELECT  parametros_adicionais
                        FROM    tbl_posto_fabrica
                        WHERE   fabrica = $login_fabrica
                        AND     posto = $posto
            ";
            $resKm = pg_query($con,$sqlKm);
            $parametros_adicionais = pg_fetch_result($resKm,0,parametros_adicionais);
            if(!empty($parametros_adicionais)){
                $adicionais = json_decode($parametros_adicionais,true);
                $fixo_km_valor = $adicionais['valor_km_fixo'];
                $fixo_km_valor = number_format($fixo_km_valor,2,',','.');
            }
?>
        <td>
            <input type="text" id="fixo_km_valor" name="fixo_km_valor" value="<?=$fixo_km_valor?>" class="frm" maxlength="10" size="10" />
        </td>
<?
        }
?>
    </tr>

    <tr align='left'>
        <td>Senha do Financeiro <br />
        <?  echo "<span class='frm' style='margin-top: 8px;padding: 2px 1ex;display: inline-block;";
        echo ($senha_financeiro <> null) ? "'>$senha_financeiro": 'color:red\'>Não Cadastrada';
        echo "</span>";
        ?>
         </td>

        <td colspan='2'>Senha da Tabela de Preço <br />
        <?  echo "<span class='frm' style='margin-top: 8px;padding: 2px 1ex;display: inline-block;";
        echo ($senha_tabela_preco <> null) ? "'>$senha_tabela_preco" : 'color:red\'>Não Cadastrada';
        echo "</span>";
        ?>
           </td>
    </tr>

    <tr align='left'>
        <td>Divulgar posto para o consumidor?</td>
        <?php
        #HD 171607
        if(((in_array($login_fabrica,array(3))) && $login_privilegios == '*') or $login_fabrica == 50 or $login_fabrica == 74 or $login_fabrica == 30 or $login_fabrica == 134){
            ?>
            <td align='left' nowrap>Posto Controla Estoque?</td>
            <?php
        }?>
    </tr>
    <tr align='left'>
        <td>

            <? if (($divulgar_consumidor != 't') && ($divulgar_consumidor != 'f')) $divulgar_consumidor = 't'; ?>
            SIM<INPUT TYPE="radio" NAME="divulgar_consumidor" VALUE = 't' <?if ($divulgar_consumidor == 't') echo "CHECKED";?>>
            NÃO<INPUT TYPE="radio" NAME="divulgar_consumidor" VALUE = 'f' <?if ($divulgar_consumidor == 'f') echo "CHECKED";?>>
        </td>
        <?php
        #HD 171607
        if(((in_array($login_fabrica,array(3))) && $login_privilegios == '*') or $login_fabrica == 50 or $login_fabrica == 74 or $login_fabrica == 30 or $login_fabrica == 134){
            ?>
            <td>
                <label>SIM <INPUT TYPE="radio" NAME="controla_estoque" ID="controla_estoque_t" VALUE = 't' <?if ($controla_estoque == 't') echo "CHECKED";?>></label>
                <label>NÃO <INPUT TYPE="radio" NAME="controla_estoque" ID="controla_estoque_f" VALUE = 'f' <?if ($controla_estoque == 'f') echo "CHECKED";?>></label>
            </td>
            <?php
        }
        ?>
    </tr>
    <?php if ($login_fabrica == 15){ //755863 estoque novo latinatec
        if (strlen($posto) > 0){
            $sql = "select categoria from tbl_posto_fabrica where fabrica=$login_fabrica and posto=$posto";
            $res = pg_query($con,$sql);
            $categoria = strtolower(pg_fetch_result($res, 0, 'categoria'));
            $CHECKED =  (pg_num_rows($res)>0 and  $categoria == 'vip' ) ? 'CHECKED' : '';
            $CHECKED_NO =  (pg_num_rows($res)==0 or $categoria == '') ? 'CHECKED' : '';
        }
    ?>
        <tr>
            <td align="left">Posto VIP?</td>
        </tr>
        <tr>
            <td align="left">
                SIM <input type="radio" name="posto_vip" id="posto_vip" value="vip" <?php echo $CHECKED ?> >
                &nbsp;
                NÃO<input type="radio" name="posto_vip" id="posto_vip2" value='false' <?php echo $CHECKED_NO ?> >
            </td>
        </tr>
        <tr align="left">
            <td>Tipo de Controle de Estoque</td>
            <td>&nbsp;</td>
        </tr>
        <tr align="left">
            <td>
                <select name="tipo_controle_estoque" id="tipo_controle_estoque" class='frm'>
                    <?php
                    if ($controla_estoque == 'f' and $controle_estoque_novo == 'f' and $controle_estoque_manual == 'f'){
                        $select_nenhum = "SELECTED";
                    }elseif ($controla_estoque == 't' and $controle_estoque_novo == 'f' and $controle_estoque_manual == 'f') {
                        $select_normal = "SELECTED";
                    }elseif ($controla_estoque == 'f' and $controle_estoque_novo == 't' and $controle_estoque_manual == 'f') {
                        $select_novo = "SELECTED";
                    }elseif ($controla_estoque == 'f' and $controle_estoque_novo == 'f' and $controle_estoque_manual == 't') {
                        $select_manual = "SELECTED";
                    } ?>
                    <option value="nenhum" <?=$select_nenhum?> > Nenhum </option>
                    <option value="estoque_normal" <?=$select_normal?> > Estoque Normal </option>
                    <option value="estoque_novo" <?=$select_novo?> > Estoque Novo </option>
                    <option value="estoque_manual" <?=$select_manual?> > Estoque Manual </option>
                </select>
            </td>
            <td>&nbsp;</td>
        </tr>
    <?php }?>

    <?if ($login_fabrica == 42){  #HD 401553?>
         <tr align='left'>
            <td>
                <?$posto_filial_check = ($posto_filial ==  't') ? "CHECKED" : null;?>
                <input type="checkbox" name='posto_filial' id='posto_filial' value='t' <?echo $posto_filial_check?>/>
                Posto é Filial
            </td>
        </tr>
        <tr style='text-align: left;' >
            <td>
                <?
                $checked  = ($entrega_tecnica == 't') ? "checked" : "" ;
                if (strlen($tipo_posto) > 0){
                    $sql = "select tipo_revenda from tbl_tipo_posto where fabrica = $login_fabrica and tipo_posto = $tipo_posto";
                    $res = pg_query($con, $sql);

                    $tipo_revenda = pg_fetch_result($res, 0, tipo_revenda);
                    $readonly = ($tipo_revenda == "t") ? "readonly='readonly'" : "";
                }
                ?>
                <input type='checkbox' name='entrega_tecnica' id='entrega_tecnica' value='t' <?=$checked?> <?=$readonly?> />
                Realiza Entrega Técnica
            </td>
        </tr>

    <?php
    }

    if ($login_fabrica == 11) {
    ?>
        <tr align='left'>
            <td>
                Permitir abrir OS para produto que estão marcados como Não abrir OS no cadastro ?
            </td>
        </tr>
        <tr align='left'>
            <td>
                Sim <input type='radio' name='permite_envio_produto' <?=($permite_envio_produto == 't') ? 'CHECKED' : ''?> value='t' />
                Não <input type='radio' name='permite_envio_produto' <?=($permite_envio_produto == 'f' || !strlen($permite_envio_produto)) ? 'CHECKED' : ''?> value='f' />
            </td>
        </tr>
    <?php
    }
    ?>

    <tr >
        <td colspan='5'>Observações</td>
    </tr>
    <tr>
        <td colspan='5'>
            <textarea class='frm' name="obs" cols="75" rows="2"><? echo $obs ?></textarea>
        </td>
    </tr>
</table>

<br />

<!--   Cobranca  -->
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <td colspan="4" class='titulo_tabela'>
            Informações para cobrança
        </td>
    </tr>
    <!-- Sem a linha abaixo, aparece errado no IE.. ??? -->
    <tr  align='left'>
        <td>CEP</td>
        <td>Endereço</td>
        <td>Número</td>
        <td>Complemento</td>
    </tr>
    <tr align='left'>
        <td>
            <input class='frm' type="text" name="cobranca_cep" id="cobranca_cep" size="10" maxlength="10" value="<? echo $cobranca_cep ?>" onblur=" buscaCEP(this.value, this.form.cobranca_endereco, this.form.cobranca_bairro, this.form.cobranca_cidade, this.form.cobranca_estado);">
        </td>
        <td>
            <input class='frm' type="text" name="cobranca_endereco" size="30" maxlength="50" value="<? echo $cobranca_endereco ?>">
        </td>
        <td>
            <input class='frm' type="text" name="cobranca_numero" size="10" maxlength="10" value="<? echo $cobranca_numero ?>">
        </td>
        <td>
            <input class='frm' type="text" name="cobranca_complemento" size="10" maxlength="20" value="<? echo $cobranca_complemento ?>">
        </td>
    </tr>
    <tr  align='left'>
        <td>Bairro</td>
        <td>Cidade</td>
        <td colspan="2">Estado</td>
    </tr>
    <tr align='left'>
        <td><input class='frm' type="text" name="cobranca_bairro" size="20" maxlength="20" value="<? echo $cobranca_bairro ?>"></td>
        <td><input class='frm' type="text" name="cobranca_cidade"  id="cobranca_cidade" size="20" maxlength="30" value="<? echo $cobranca_cidade ?>"></td>
        <td colspan="2">
                <select name="cobranca_estado" id="cobranca_estado" style="font-size:9px" class="frm">
                    <option value=""   <?php if (strlen($cobranca_estado) == 0)    echo " selected ";?> >TODOS OS ESTADOS</option>
                    <option value="AC" <?php if ($cobranca_estado == "AC") echo " selected "; ?>>AC - Acre</option>
                    <option value="AL" <?php if ($cobranca_estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
                    <option value="AM" <?php if ($cobranca_estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
                    <option value="AP" <?php if ($cobranca_estado == "AP") echo " selected "; ?>>AP - Amapá</option>
                    <option value="BA" <?php if ($cobranca_estado == "BA") echo " selected "; ?>>BA - Bahia</option>
                    <option value="CE" <?php if ($cobranca_estado == "CE") echo " selected "; ?>>CE - Ceará</option>
                    <option value="DF" <?php if ($cobranca_estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
                    <option value="ES" <?php if ($cobranca_estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
                    <option value="GO" <?php if ($cobranca_estado == "GO") echo " selected "; ?>>GO - Goiás</option>
                    <option value="MA" <?php if ($cobranca_estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
                    <option value="MG" <?php if ($cobranca_estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
                    <option value="MS" <?php if ($cobranca_estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
                    <option value="MT" <?php if ($cobranca_estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
                    <option value="PA" <?php if ($cobranca_estado == "PA") echo " selected "; ?>>PA - Pará</option>
                    <option value="PB" <?php if ($cobranca_estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
                    <option value="PE" <?php if ($cobranca_estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
                    <option value="PI" <?php if ($cobranca_estado == "PI") echo " selected "; ?>>PI - Piauí</option>
                    <option value="PR" <?php if ($cobranca_estado == "PR") echo " selected "; ?>>PR - Paraná</option>
                    <option value="RJ" <?php if ($cobranca_estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
                    <option value="RN" <?php if ($cobranca_estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
                    <option value="RO" <?php if ($cobranca_estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
                    <option value="RR" <?php if ($cobranca_estado == "RR") echo " selected "; ?>>RR - Roraima</option>
                    <option value="RS" <?php if ($cobranca_estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
                    <option value="SC" <?php if ($cobranca_estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
                    <option value="SE" <?php if ($cobranca_estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
                    <option value="SP" <?php if ($cobranca_estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
                    <option value="TO" <?php if ($cobranca_estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
                </select>
        </td>
    </tr>
</table>

<?

# HD 55187
if ($login_fabrica == 45){
    $sqlPriv = "SELECT admin FROM tbl_admin WHERE admin = $login_admin
                AND (privilegios LIKE '%financeiro%' OR privilegios LIKE '*')";
    $resPriv = pg_query($con,$sqlPriv);
    if (pg_num_rows($resPriv) == 0){
        $readonly = " READONLY";
    }
}
?>
<br />

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
    <tr><td colspan='4' class='titulo_tabela'>Informações Bancárias</td></tr>
    <tr  align='left'>
        <td width = '33%'>CPF/CNPJ Favorecido</td>
        <td colspan=3>Nome Favorecido</td>
    </tr>
    <tr align='left'>
        <td width = '33%'>
        <input class='frm' type="text" name="cpf_conta" size="14" maxlength="19" value="<? echo $cpf_conta ?>"
        <?php
        if (strlen($cpf_conta)>0){
            echo $readonly;
        }
        ?>></td>
        <td colspan=3>
        <input class='frm' type="text" name="favorecido_conta" size="50" maxlength="50" value="<? echo $favorecido_conta ?>"
        <?php
        if (strlen($favorecido_conta)>0){
            echo $readonly;
        }
        ?>></td>
    </tr>
    <tr  align='left'>
        <td colspan='4' width = '100%'>Banco</td>
    </tr>
    <tr align='left'>
        <td colspan='4'>
            <?
            $sqlB = "SELECT codigo, nome
                    FROM tbl_banco
                    ORDER BY codigo";
            $resB = pg_query($con,$sqlB);
            if (pg_num_rows($resB) > 0) {
                echo "<select class='frm' name='banco' size='1'";
                if (isset($readonly) and strlen($banco)>0){ // HD 85519
                    echo " onfocus='defaultValue=this.value' onchange='this.value=defaultValue' ";
                }
                echo ">";
                echo "<option value=''></option>";
                for ($x = 0 ; $x < pg_num_rows($resB) ; $x++) {
                    $aux_banco     = pg_fetch_result($resB,$x,codigo);
                    $aux_banconome = pg_fetch_result($resB,$x,nome);
                    echo "<option value='" . $aux_banco . "'";
                    if ($banco == $aux_banco) echo " selected";
                    echo ">" . $aux_banco . " - " . $aux_banconome . "</option>";
                }
                echo "</select>";
            }
            ?>
        </td>
    </tr>
    <tr  align='left'>
        <td width = '33%'>Tipo de Conta</td>
        <td width = '33%'>Agência</td>
        <td width = '34%'>Conta</td>
        <? if($login_fabrica == 45 ){?>
        <td width = '34%'>Operação</td>
        <?}?>
    </tr>
    <tr align='left'>
        <td width = '33%'>
            <select class='frm' name='tipo_conta'
            <?php
                if (isset($readonly) and strlen($tipo_conta)>0){
                    echo " DISABLED";
                } ?>>
                <option selected></option>
                <option value='Conta conjunta'   <? if ($tipo_conta == 'Conta conjunta')   echo "selected"; ?>>Conta conjunta</option>
                <option value='Conta corrente'   <? if ($tipo_conta == 'Conta corrente')   echo "selected"; ?>>Conta corrente</option>
                <option value='Conta individual' <? if ($tipo_conta == 'Conta individual') echo "selected"; ?>>Conta individual</option>
                <option value='Conta jurídica'   <? if ($tipo_conta == 'Conta jurídica')   echo "selected"; ?>>Conta jurídica</option>
                <option value='Conta poupança'   <? if ($tipo_conta == 'Conta poupança')   echo "selected"; ?>>Conta poupança</option>
            </select>
        </td>
        <td width = '33%'>
        <input  class='frm' type="text" name="agencia" size="10" maxlength="10" value="<? echo $agencia ?>"
        <?php
        if (strlen($agencia)>0){
            echo $readonly;
        }
        ?>></td>
        <td width = '34%'>
        <input class='frm' type="text" name="conta" size="15" maxlength="15" value="<? echo $conta ?>"
        <?php
        if (strlen($conta)>0){
            echo $readonly;
        }
        ?>></td>
        <? if($login_fabrica == 45 ){?>
        <td width = '34%'>
        <input class='frm' type="text" name="conta_operacao" size="5" maxlength="3" value="<? echo $conta_operacao ?>"
        <?php
        if (strlen($conta_operacao)>0){
            echo $readonly;
        }
        ?>></td>
        <?}?>
    </tr>
    <tr >
        <td colspan="4">Observações</td>
    </tr>
    <tr>
        <td colspan="4">
            <textarea class='frm' name="obs_conta" cols="75" rows="2"
            <?php
            if (strlen($obs_conta)>0){
                echo $readonly;
            }?>><? echo $obs_conta; ?></textarea>
        </td>
    </tr>
</table>

<br />
    <? if($login_fabrica == 30){ //HD 356653 Início?>
        <table class='formulario' style="margin: 0 auto; width: 700px;">
            <tr class='titulo_tabela'><td colspan='3'>Dados Contábeis</td></tr>
            <tr>
                <td align='left'>
                    Conta Contábil <br />
                    <input type='text' name='conta_contabil' id='conta_contabil' size='17' maxlength='25' value='<? echo $conta_contabil; ?>' class='frm'>
                </td>
                <td align='left'>
                    Centro Custo <br />
                    <input type='text' name='centro_custo' id='centro_custo' size='17' maxlength='25' value='<? echo $centro_custo; ?>' class='frm'>
                </td>
                <td align='left'>
                    Local Entrega <br />
                    <input type='text' name='local_entrega' id='local_entrega' size='40' maxlength='50' value='<? echo $local_entrega; ?>' class='frm'>
                </td>
            </tr>
        </table>

        <br />
    <? } //HD 356653 Fim ?>

<!--   linhas, tabelas Distribuidores  -->
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
<tr>

<td class='titulo_tabela'>
<!-- criar imagem com texto referente a linha e tabela -->
Linhas e Tabelas
</td>

</tr>
<tr>

<td>

<?
if($login_fabrica == 20 AND strlen($posto)>0) {
    $sql = "SELECT tabela FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
    $resX = @pg_query ($con,$sql);
    $tabela =  @pg_fetch_result($resX,0,tabela);

    $sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
    $resX = pg_query ($con,$sql);

    echo "<select class='frm' name='tabela_unica'>\n";
    echo "<option selected></option>\n";

    for($x=0; $x < pg_num_rows($resX); $x++){
        $check = "";
        if ($tabela == pg_fetch_result($resX,$x,tabela)) $check = " selected ";
        echo "<option value='".pg_fetch_result($resX,$x,tabela)."' $check>".pg_fetch_result($resX,$x,sigla_tabela)." - ". pg_fetch_result($resX,$x,descricao)."</option>";
    }

    echo "</select>\n";
}
if (strlen ($posto) > 0 AND $login_fabrica <> 20) {?>
    <TABLE  class='formulario' style="margin: 0 auto; width: 100%; border: 0;" cellpadding='1' cellspacing='3'>
        <tr align='left'>

            <?php
            if (!in_array($login_fabrica,array(14))) {
            ?>
                <TD>Linha</TD>
            <?php
            } else {
            ?>
                <TD>Família</TD>
            <?php
            }
            ?>

            <td>Atende</td>
<?php
        if($login_fabrica == 50){
?>
            <TD>Auditar OS 24hrs</TD>
<?php
        }
?>
<?php
        if(!in_array($login_fabrica,array(30,40,74,98,101,104,105,115,116,122,123,124,125,128,129,131,136))) {

?>
            <TD>Tabela</TD>
<?php
        } elseif(in_array($login_fabrica,array(30,98))) {
?>
            <TD>Tabela Faturada</TD>
            <TD>Tabela Garantia </TD>
<?php
        } elseif($login_fabrica == 74 or $login_fabrica == 104 or $login_fabrica == 105 ) {
?>
            <TD>Tabela Venda</TD>
<?php
        if($login_fabrica == 74){
?>
            <TD>Tabela Recompra</TD>
<?php
        }
?>
            <TD>Tabela Garantia</TD>
<?php
        } else {
?>
            <TD>Tabela Garantia</TD>
            <TD>Tabela Faturada</TD>
<?php
        }

        if (!in_array($login_fabrica, array(94,98,101,117,122,81,114)) AND $login_fabrica < 120) {//HD 677353
?>
            <TD>Desconto</TD>
<?php
        }

        if (!in_array($login_fabrica, array(74,86,94,101,104,115,116,117,81)) AND $login_fabrica < 120)  {//HD 387824, 677353
?>

            <TD>Distribuidor</TD>
<?php
        }

        if ($login_fabrica == 24){
?>
            <td>Divulgar linha ao consumidor</td>
<?
        }
?>
    </tr>
<?php
    if (!in_array($login_fabrica,array(14))) {
        $sql = "SELECT  tbl_linha.linha,
                        tbl_linha.nome
                FROM    tbl_linha
                WHERE   ativo IS TRUE
                AND     tbl_linha.fabrica = $login_fabrica ";
        $res = pg_query ($con,$sql);

        for ($i = 0; $i < pg_num_rows($res); $i++) {

            $linha        = pg_fetch_result($res, $i, 'linha');
            $check        = "";
            $auditar_os   = "";
            $tabela       = "" ;
            $desconto     = "";
            $distribuidor = "";

            $sql = "SELECT  tbl_posto_linha.tabela              ,
                            tbl_posto_linha.desconto            ,
                            tbl_posto_linha.distribuidor        ,
                            tbl_posto_linha.tabela_posto        ,
                            tbl_posto_linha.tabela_bonificacao  ,
                            tbl_posto_linha.auditar_os          ,
                            tbl_posto_linha.divulgar_consumidor
                    FROM    tbl_posto_linha
                    WHERE   posto = $posto
                    AND     linha = $linha
            ";
            if ($login_fabrica == 2) {
                $sql = "SELECT  DISTINCT
                                tbl_posto_linha.tabela              ,
                                tbl_posto_linha.desconto            ,
                                tbl_posto_linha.distribuidor        ,
                                tbl_posto_linha.tabela_posto        ,
                                tbl_posto_linha.tabela_bonificacao  ,
                                tbl_posto_linha.divulgar_consumidor
                        FROM    tbl_posto_linha
                        WHERE   posto = $posto
                        AND     linha = $linha
                ";
            }

            $resX = pg_query ($con,$sql);

            if (pg_num_rows($resX) == 1) {
                $check                      = " CHECKED ";
                $tabela                     = pg_fetch_result($resX, 0, 'tabela');
                $desconto                   = pg_fetch_result($resX, 0, 'desconto');
                $distribuidor               = pg_fetch_result($resX, 0, 'distribuidor');
                $tabela_posto               = pg_fetch_result($resX, 0, 'tabela_posto');
                $tabela_bonificacao         = pg_fetch_result($resX, 0, 'tabela_bonificacao');
                $auditar_os                 = pg_fetch_result($resX, 0, 'auditar_os');
                $divulga_consumidor_linha   = pg_fetch_result($resX, 0, 'divulgar_consumidor');
        }else{
        $tabela_posto = "";
        }

            if (pg_num_rows ($resX) > 1) {
?>
                <h1> ERRO NAS LINHAS, AVISE TELECONTROL </h1>
<?
                exit;
            }

            if (strlen ($msg_erro) > 0) {
                $atende             = $_POST ['atende_'             . $linha];
                $auditar_os         = $_POST ['auditar_os_'         . $linha];
                $tabela             = $_POST ['tabela_'             . $linha];
                $tabela_posto       = $_POST ['tabela_posto_'       . $linha];
                $tabela_bonificacao = $_POST ['tabela_bonificacao'  . $linha];
                $desconto           = $_POST ['desconto_'           . $linha];
                $distribuidor       = $_POST ['distribuidor_'       . $linha];
                if ($login_fabrica == 24 ){
                    $divulga_consumidor_linha = $_POST [ 'divulga_consumidor_' . $linha ];
                }
                if ( strlen ($atende) > 0 ) $check = " CHECKED ";

                if($login_fabrica == 50){
                    if ( strlen ($auditar_os) > 0 ) {
                        $check_os = " CHECKED ";
                    }
                }
            }

            if ($login_fabrica == 24 ){
                $check_divulga = ($divulga_consumidor_linha == 't') ? "CHECKED" : null; #HD 383050
            }
            if ($login_fabrica == 50 ){
                $check_os = ($auditar_os == 't') ? "CHECKED" : null;
            }
?>
            <tr align='left'>

                <td nowrap><? echo pg_fetch_result ($res,$i,nome); ?></td>
                <td align='center'><input type='checkbox' name='atende_<?=$linha?>' value='<?=$linha?>' <?=$check?>></td>
<?
            if($login_fabrica == 50){
?>
                <td align='center'><input type='checkbox' name='auditar_os_<?=$linha?>' value='<?=$linha?>' <?=$check_os?>></td>
<?
            }
?>
                <td align='left'>
<?
            if ($login_fabrica == 6) {

                $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica and ativa ORDER BY sigla_tabela";
                $resX = pg_query($con,$sql);

                echo "<select class='frm' name='tabela_$linha'>\n";
                echo "<option selected></option>\n";

                for ($x = 0; $x < pg_num_rows($resX); $x++) {
                    $check = "";
                    if ($tabela == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                    echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";
                }

            }
            if ($login_fabrica == 104) {
                //HD 845757 - item 1 análise
                //seleciona a tabela que o posto hoje está cadastrado para mostrar na frente. mesmo que a tabela esteja inativa... deverá mostrar

                $sql = "SELECT tbl_tabela.tabela, tbl_tabela.descricao, tbl_tabela.sigla_tabela
                          FROM tbl_posto_linha
                          JOIN tbl_tabela USING (tabela)
                         WHERE tbl_tabela.fabrica = $login_fabrica
                           AND tbl_posto_linha.posto=$posto
                           AND tbl_posto_linha.linha=$linha
                UNION
                      SELECT tbl_tabela.tabela, tbl_tabela.descricao, tbl_tabela.sigla_tabela
                        FROM tbl_tabela
                       WHERE fabrica = $login_fabrica
                         AND ativa
                         /*AND tabela_garantia*/
                         AND SUBSTR(sigla_tabela, 1, 2) = '$estado'
                    ORDER BY sigla_tabela";

                $res_tabela_1 = pg_query($con,$sql);

                if ($debug AND pg_last_error($con))
                    pre_echo(nl2br($sql) . "\n<br />" . pg_last_error($con), "Tabela selecionada: $tabela");

                if (pg_num_rows($res_tabela_1)>0) {

                    unset($tabelas_linha, $combo_tabelas);
                    $tabelas_linha = pg_fetch_all($res_tabela_1);

                    if ($debug == 2)
                        echo array2table($tabelas_linha, count($tabelas_linha) . " Tabelas para a linha $linha");

                    foreach ($tabelas_linha as $item_tabelas_linha) {
                        $combo_tabelas[$item_tabelas_linha['tabela']] = $item_tabelas_linha['sigla_tabela'] . ' - ' .
                                                                        strtoupper(trim($item_tabelas_linha['descricao']));
                    }

                    echo array2select("tabela_$linha", null, $combo_tabelas, $tabela, " class='frm'", ' ', true);

                }

            } else {

                $sql  = "   SELECT  tbl_tabela.tabela       ,
                                    tbl_tabela.descricao    ,
                                    tbl_tabela.sigla_tabela
                            FROM    tbl_tabela
                            WHERE   fabrica = $login_fabrica
                            AND     ativa IS TRUE
                      ORDER BY      sigla_tabela";
                $resX = pg_query($con,$sql);
?>
                    <select class='frm' name='tabela_<?=$linha?>' style='width: 170px'>
                        <option selected></option>
<?
                for ($x = 0; $x < pg_num_rows($resX); $x++) {

                    $q_tabela = pg_fetch_result($resX, $x, 'tabela');
                    $q_descricao = pg_fetch_result($resX, $x, 'descricao');
                    $q_sigla = pg_fetch_result($resX, $x, 'sigla_tabela');

                    $check = "";
                    if ($tabela == $q_tabela) $check = " selected ";

                    //HD 677353 - Para a Delonghi aparecer apenas estas tabelas quando for garantia
                    $delonghi = ($login_fabrica == 101 && in_array($q_tabela, array(575)));

                    if ($delonghi || $login_fabrica != 101) {
?>
                        <option value='<?=$q_tabela?>' <?=$check?>><?=$q_sigla." - ".$q_descricao?></option>
<?
                    }

                }

            }
?>
                    </select>
                </td>
<?
            if (in_array($login_fabrica, array(30,40,74,98,101,105,115,116,122,123,124,125,128,129,131,136))){

                echo "<td align='left'>";
                $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
                $resX = pg_query ($con,$sql);

                echo "<select class='frm' name='tabela_posto_$linha' style='width: 170px;'>\n";
                echo "<option selected></option>\n";

                for ($x = 0; $x < pg_num_rows($resX); $x++) {

                    //HD 677353 - Para a Delonghi aparecer apenas estas tabelas quando for faturado
                    $delonghi = ($login_fabrica == 101 && in_array(pg_fetch_result($resX, $x, 'tabela'), array(576,577)));

                    if ($delonghi || in_array($login_fabrica, array(30,40,74,98,104,105,115,116,122,123,124,125,128,129,131,136))) {

                        $check = "";
                        if ($tabela_posto == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                        echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";

                    }

                }

                echo "</select>\n";
                echo "</td>";

            }else if ($login_fabrica == 104) {
                //HD 845757?>
                <td align="left">
                <?php
                //seleciona a tabela que o posto hoje está cadastrado para mostrar na frente. mesmo que a tabela esteja inativa... deverá mostrar
                $sql_tabela_posto1 = "SELECT tbl_tabela.*
                                        FROM tbl_tabela
                                        JOIN tbl_posto_linha ON tbl_tabela.tabela     = tbl_posto_linha.tabela_posto
                                                            AND tbl_posto_linha.posto = $posto
                                                            AND tbl_posto_linha.linha = $linha
                                       WHERE fabrica = $login_fabrica
                                UNION
                                      SELECT *
                                        FROM tbl_tabela
                                       WHERE fabrica = $login_fabrica
                                         AND ativa
                                         /*AND tabela_garantia*/
                                         AND SUBSTR(sigla_tabela, 1, 2) = '$estado'
                                    ORDER BY sigla_tabela";

                $res_tabela_posto_1 = pg_query($con,$sql_tabela_posto1);

                if (pg_num_rows($res_tabela_posto_1)>0) {

                    $tabelas_posto = pg_fetch_all($res_tabela_posto_1);

                    if ($debug == 2)
                        echo array2table($tabelas_posto, count($tabelas_posto) . " Tabelas para a linha $linha, Tabela selecionada: $tabela_posto");

                    foreach ($tabelas_posto as $item_tabelas_posto) {
                        $combo_tabelas[$item_tabelas_posto['tabela']] = $item_tabelas_posto['sigla_tabela'] . ' - ' .
                                                                        strtoupper(trim($item_tabelas_posto['descricao']));
                    }

                    echo array2select("tabela_posto_$linha", null, $combo_tabelas, $tabela_posto, " class='frm'", ' ', true);
                    unset($tabela_posto, $tabelas_posto, $combo_tabelas);

                }

                echo "</td>";

            }
            if($login_fabrica == 74){
                echo "<td align='left'>";
                $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
                $resX = pg_query ($con,$sql);
                echo "<select class='frm' name='tabela_bonificacao_$linha' style='width: 170px;'>\n";
                echo "<option selected></option>\n";

                for ($x = 0; $x < pg_num_rows($resX); $x++) {
                    $check = "";
                    if ($tabela_bonificacao == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                    echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";
                }
                echo "</select>\n";
                echo "</td>";
            }

            if (!in_array($login_fabrica, array(94,98,101,117))  AND $login_fabrica < 120) {//HD 677353
                echo "<td align='center'><input class='frm' type='text' size='3' maxlength='2' name='desconto_$linha' value='$desconto'>%</td>";
            }

            if (!in_array($login_fabrica, array(74,86,94,101,104,115,116,117))  AND $login_fabrica < 120) {//HD 677353


                echo "<td align='left'>";

                $sql = "SELECT  tbl_posto.posto   ,
                                tbl_posto.nome_fantasia,
                                tbl_posto.nome
                        FROM    tbl_posto
                        JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                        JOIN    tbl_tipo_posto       ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                        WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
                        AND     tbl_tipo_posto.distribuidor is true
                        ORDER BY tbl_posto.nome_fantasia";

                $resX = pg_query ($con,$sql);

                echo "<select class='frm' name='distribuidor_$linha' style='width: 170px;'>\n";
                echo "<option ></option>\n";

                for ($x = 0; $x < pg_num_rows($resX); $x++) {
                    $check = "";
                    if ($distribuidor == pg_fetch_result($resX,$x,posto)) $check = " selected ";
                    $fantasia = pg_fetch_result ($resX,$x,nome_fantasia) ;
                    if (strlen (trim ($fantasia)) == 0) $fantasia = pg_fetch_result ($resX,$x,nome) ;
                    echo "<option value='".pg_fetch_result($resX,$x,posto)."' $check>$fantasia</option>";
                }

                echo "</select>\n";
                echo "</td>";

                if ($login_fabrica == 24){ #HD 383050
                    echo "<td>";
                        echo "<input type='checkbox' $check_divulga value='t' name='divulga_consumidor_linha_$linha' id='divulga_consumidor_linha'  >";
                    echo "</td>";
                }

            }
            echo "</tr>";
        }

    } else {

        $sql = "SELECT  tbl_familia.familia,
                        tbl_familia.descricao
                FROM    tbl_familia
                WHERE   tbl_familia.fabrica = $login_fabrica
                ORDER BY tbl_familia.descricao;";

        $res = pg_query ($con,$sql);

        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $familia       = pg_fetch_result ($res,$i,familia);
            $check         = "";
            $tabela        = "" ;
            $desconto      = "";
            $distribuidor  = "";

            $sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND familia = $familia";
            $resX = pg_query ($con,$sql);

            if (pg_num_rows ($resX) == 1) {
                $check        = " CHECKED ";
                $tabela       = pg_fetch_result ($resX,0,'tabela');
                $desconto     = pg_fetch_result ($resX,0,'desconto');
                $distribuidor = pg_fetch_result ($resX,0,'distribuidor');
            }

            if (pg_num_rows ($resX) > 1) {
                echo "<h1> ERRO NAS FAMÍLIAS, AVISE TELECONTROL </h1>";
                exit;
            }

            if (strlen ($msg_erro) > 0) {
                $atende       = $_POST ['atende_'       . $familia] ;
                $tabela       = $_POST ['tabela_'       . $familia] ;
                $desconto     = $_POST ['desconto_'     . $familia] ;
                $distribuidor = $_POST ['distribuidor_' . $familia] ;
                if (strlen ($atende) > 0 ) $check = " CHECKED ";
            }

            echo "<tr>";

            echo "<td nowrap>" . pg_fetch_result ($res, $i, 'descricao') . "</td>";
            echo "<td align='center'><input type='checkbox' name='atende_$familia' value='$familia' $check></td>";

            echo "<td align='left'>";

            $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
            $resX = pg_query ($con,$sql);

            echo "<select class='frm' name='tabela_$familia'>\n";
            echo "<option selected></option>\n";

            for ($x = 0; $x < pg_num_rows($resX); $x++) {

                $check = "";
                if ($tabela == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";

            }

            echo "</select>\n";
            echo "</td>";
            echo "<td align='center'><input class='frm' type='text' size='3' maxlength='2' name='desconto_$linha' value='$desconto'>%</td>";
            echo "<td align='left'>";

            $sql = "SELECT  tbl_posto.posto   ,
                            tbl_posto.nome_fantasia,
                            tbl_posto.nome
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
                                                AND tbl_posto_fabrica.fabrica = $login_fabrica
                    JOIN    tbl_tipo_posto       ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                    WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
                    AND     tbl_posto_fabrica.posto    <> 7214
                    AND     tbl_tipo_posto.distribuidor is true
                    ORDER BY tbl_posto.nome_fantasia";

            $resX = pg_query ($con,$sql);

            echo "<select class='frm' name='distribuidor_$familia' disabled>";
            echo "<option > </option>\n";

            for ($x = 0; $x < pg_num_rows($resX); $x++) {

                $check = "";
                if ($distribuidor == pg_fetch_result($resX, $x, 'posto')) $check = " selected ";

                $fantasia = pg_fetch_result ($resX, $x, 'nome_fantasia') ;

                if (strlen(trim($fantasia)) == 0) $fantasia = pg_fetch_result ($resX, $x, 'nome');

                echo "<option value='".pg_fetch_result($resX,$x,posto)."' $check>$fantasia</option>";

            }

            echo "</select>\n";
            echo "</td>";

            echo "</tr>";
        }

    }

}?>
    </TD>
    </TR>
    </TABLE>
</td>
</tr>
</table>

<br />
<?php //HD-808142
if($login_fabrica == 52){
    if (strlen($posto) > 0){
        $sqlTabelaServico = "select tabela_mao_obra from tbl_posto_fabrica where posto = ".$posto." and fabrica = ".$login_fabrica.";";
        $res = pg_query($con,$sqlTabelaServico);
        $servicoMo = trim(pg_fetch_result($res,0,tabela_mao_obra));
    }
?>
<!--   Tabela de serviços  -->
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <td colspan="4" class='titulo_tabela'>
            Tabela de serviços
        </td>
    </tr>
    <tr>
        <td style="height:70px;">
                <select class="frm" name="tabela_servico" style="width:350px;">
                    <?php

                    //HD-808142

                    $sql = "SELECT
                                tabela_mao_obra,
                                sigla_tabela,
                                descricao
                            FROM tbl_tabela_mao_obra
                            WHERE tbl_tabela_mao_obra.fabrica = ".$login_fabrica." AND tbl_tabela_mao_obra.ativo is TRUE
                            ORDER BY tbl_tabela_mao_obra.sigla_tabela;";

                    $res = pg_query($con,$sql);

                    if (pg_num_rows($res) > 0) {

                            echo "<option value=''  selected='selected' >Selecione</option>";

                            for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                $tabela_mao_obra = trim(pg_fetch_result($res,$x,tabela_mao_obra));
                                $sigla_tabela  = trim(pg_fetch_result($res,$x,sigla_tabela));
                                $descricao  = trim(pg_fetch_result($res,$x,descricao));
                                $selected = ($tabela_mao_obra == $servicoMo) ? " selected='selected' " : "" ;

                                echo  "<option value='".$tabela_mao_obra."' ".$selected.">".$sigla_tabela." - ".$descricao."</option>";
                            }

                    }

                    ?>
                </select>
            </td>
    </tr>
</table>
<?php } ?>
<!-- Esmaltec Cidades  atendidas +=====================-->

<?php

if(in_array($login_fabrica,array(15,30,52,120, 85,74)) && strlen($_GET["posto"]) > 0) {
    $ArrayEstados = array('AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO');
?>
    <script src="js/jquery.alphanumeric.js"></script>
    <script>
        $(function () {
            $(".km").numeric();

            var login_fabrica = "<?=$login_fabrica?>";

            $("#estado_cadastra").change(function () {
                if ($(this).val().length > 0) {
                    $("#cidade_cadastra").removeAttr("readonly");
                } else {
                    $("#cidade_cadastra").attr({ readonly: "readonly" });
                }
            });

            var extraParamEstado = {
                estado: function () {
                    return $("#estado_cadastra").val()
                }
            };

            $("#cidade_cadastra").autocomplete("autocomplete_cidade_new.php", {
                minChars: 3,
                delay: 150,
                width: 350,
                matchContains: true,
                extraParams: extraParamEstado,
                formatItem: function (row) { return row[0]; },
                formatResult: function (row) { return row[0]; }
            });

            $("#consumidor_cidade").result(function(event, data, formatted) {
                $("#consumidor_cidade").val(data[0].toUpperCase());
            });

            $("#adicionar_cidade").click(function () {
                var estado = $("#estado_cadastra").val();
                var cidade = $.trim($("#cidade_cadastra").val());

                if (login_fabrica != 74) {
                    var tipo = $("#tipo_cadastra").val();
                }

                if (login_fabrica == 52) {
                    var km = $("#km_cadastra").val();
                }

                var campo_erro = [];

                if (estado.length == 0) {
                    campo_erro.push("estado");
                }

                if (cidade.length == 0) {
                    campo_erro.push("cidade");
                }

                if (login_fabrica != 74 && tipo.length == 0) {
                    campo_erro.push("tipo");
                }

                if (campo_erro.length == 0) {
                    if (login_fabrica == 52) {
                        var data_ajax = { adicionar_cidade: true, posto: "<?=$posto?>", estado: estado, cidade: cidade, tipo: tipo, km: km };
                    } else if (login_fabrica == 74) {
                        var data_ajax = { adicionar_cidade: true, posto: "<?=$posto?>", estado: estado, cidade: cidade };
                    } else {
                        var data_ajax = { adicionar_cidade: true, posto: "<?=$posto?>", estado: estado, cidade: cidade, tipo: tipo };
                    }

                    $.ajax({
                        url: "cadastra_cidade_atendida.php",
                        type: "POST",
                        data: data_ajax,
                        beforeSend: function () {
                            $("#adicionar_cidade").hide();
                            $("#loading_adicionar_cidade").show();
                        },
                        complete: function (data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                if (login_fabrica == 52) {
                                    var tr_cidade = $("<tr></tr>");

                                    var button_cidade_excluir = $("<button></button>", {
                                        type: "button",
                                        name: "excluir_cidade",
                                        rel: data.id,
                                        text: "Excluir Cidade"
                                    });

                                    var button_cidade_salvar = $("<button></button>", {
                                        type: "button",
                                        name: "salvar_cidade",
                                        rel: data.id,
                                        text: "Salvar Alteração"
                                    });

                                    var cidade_nome = data.cidade + " - " + data.estado;

                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: cidade_nome })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: data.tipo })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", css: { width: "50px" }, class: "km", value: data.km })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).append(button_cidade_salvar).append(button_cidade_excluir));

                                    $("#cidades_atendidas_cadastradas").append(tr_cidade);

                                    $(".km").numeric();
                                } else if (login_fabrica == 74) {
                                    var tr_cidade = $("<tr></tr>");

                                    var button_cidade_excluir = $("<button></button>", {
                                        type: "button",
                                        name: "excluir_cidade",
                                        rel: data.id,
                                        text: "Excluir Cidade"
                                    });

                                    var span = $("<span></span>");

                                    var input_bairro = $("<input />", {
                                        name: "bairro_cadastra",
                                        type: "text"
                                    });

                                    var button_bairro_adicionar = $("<button></button>", {
                                        type: "button",
                                        name: "adicionar_bairro",
                                        rel: data.id,
                                        text: "Adicionar Bairro"
                                    });

                                    $(span).text("Bairros");
                                    $(span).append(input_bairro);
                                    $(span).append(button_bairro_adicionar);

                                    var cidade_nome = data.cidade + " - " + data.estado;

                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: cidade_nome })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" }, name: "bairros" }).html(span));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html(button_cidade_excluir));

                                    $("#cidades_atendidas_cadastradas").append(tr_cidade);
                                } else {
                                    var tr_cidade = $("<tr></tr>");

                                    var button_cidade_excluir = $("<button></button>", {
                                        type: "button",
                                        name: "excluir_cidade",
                                        rel: data.id,
                                        text: "Excluir Cidade"
                                    });

                                    var cidade_nome = data.cidade + " - " + data.estado;

                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: cidade_nome })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: data.tipo })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html(button_cidade_excluir));

                                    $("#cidades_atendidas_cadastradas").append(tr_cidade);
                                }
                            }

                            $("#adicionar_cidade").show();
                            $("#loading_adicionar_cidade").hide();
                        }
                    });
                } else {
                    alert("Os seguintes campos são obrigatórios para adicionar uma nova cidade atendida: "+campo_erro.join(", "));
                }
            });

            $(document).on("click", "button[name=adicionar_bairro]", function () {
                var id     = $(this).attr("rel");
                var bairro = $.trim($(this).prev().val());
                var td_bairro = $(this).parents("td");

                if (bairro.length > 0) {
                    $.ajax({
                        url: "cadastra_cidade_atendida.php",
                        type: "POST",
                        data: { adicionar_bairro: true, posto: "<?=$posto?>", id: id, bairro: bairro },
                        complete: function (data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                var div = $("<div></div>");

                                var bairro_nome = $("<input />", {
                                    type: "text",
                                    readonly: "readonly",
                                    value: bairro
                                });

                                var button_bairro_excluir = $("<button></button>", {
                                    rel: id,
                                    type: "button",
                                    name: "excluir_bairro",
                                    text: "Excluir Bairro"
                                });

                                $(div).append(bairro_nome);
                                $(div).append(button_bairro_excluir);

                                $(td_bairro).append(div);
                            }
                        }
                    });
                } else {
                    alert("Digite um bairro");
                }
            });

            $(document).on("click", "button[name=excluir_cidade]", function () {
                if (confirm("Deseja realmente excluir a cidade ?")) {
                    var id = $(this).attr("rel");
                    var tr = $(this).parents("tr");

                    $.ajax({
                        url: "cadastra_cidade_atendida.php",
                        type: "POST",
                        data: { excluir_cidade: true, posto: "<?=$posto?>", id: id },
                        complete: function (data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                alert("Cidade Excluida");

                                $(tr).remove();
                            }
                        }
                    });
                }
            });

            $(document).on("click", "button[name=excluir_bairro]", function () {
                if (confirm("Deseja realmente excluir o bairro ?")) {
                    var id     = $(this).attr("rel");
                    var bairro = $(this).prev().val();
                    var div    = $(this).parent("div");

                    $.ajax({
                        url: "cadastra_cidade_atendida.php",
                        type: "POST",
                        data: { excluir_bairro: true, posto: "<?=$posto?>", id: id, bairro: bairro },
                        complete: function (data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                alert("Bairro Excluido");

                                $(div).remove();
                            }
                        }
                    });
                }
            });

            $(document).on("click", "button[name=salvar_cidade]", function () {
                var id = $(this).attr("rel");
                var km = $(this).parents("tr").find("input.km").val();

                if (km == undefined || km.length == 0) {
                    km = 0;
                }

                $.ajax({
                    url: "cadastra_cidade_atendida.php",
                    type: "POST",
                    data: { salvar_cidade: true, posto: "<?=$posto?>", id: id, km: km },
                    complete: function (data) {
                        data = $.parseJSON(data.responseText);

                        if (data.erro) {
                            alert(data.erro);
                        } else {
                            alert("Alteração de KM gravada com sucesso");
                        }
                    }
                });
            });
        });
    </script>

    <table class='formulario linha_cidades' style='margin: 0 auto; width: 700px; border: 0;' cellpadding='1' cellspacing='3' >
        <tr>
            <td colspan="<?=($login_fabrica == 52) ? 5 : 4?>" class='titulo_tabela'>Cidades Atendidas</td>
        </tr>
        <tr>
            <td>
                Estado<br />
                <select id="estado_cadastra" >
                    <option value=""></option>
                    <?php
                    foreach ($ArrayEstados as $estado) {
                        echo "<option value='{$estado}' >{$estado}</option>";
                    }
                    ?>
                </select>
            </td>
            <td>
                Cidade<br />
                <input type="text" id="cidade_cadastra" readonly="readonly" title="Selecione um estado para digitar a cidade" />
            </td>
            <?php
            if ($login_fabrica != 74) {
                $sql = "SELECT posto_fabrica_ibge_tipo, nome
                        FROM tbl_posto_fabrica_ibge_tipo
                        WHERE fabrica = {$login_fabrica}";
                $res = pg_query($con, $sql);
                $rows = pg_num_rows($res);
            ?>
                <td>
                    Tipo<br />
                    <select id="tipo_cadastra" >
                        <option value="" ></option>
                        <?php
                            if ($rows > 0) {
                                for ($i = 0; $i < $rows; $i++) {
                                    $posto_fabrica_ibge_tipo = pg_fetch_result($res, $i, "posto_fabrica_ibge_tipo");
                                    $nome = pg_fetch_result($res, $i, "nome");

                                    echo "<option value='{$posto_fabrica_ibge_tipo}' >{$nome}</option>";
                                }
                            }
                        ?>
                    </select>
                </td>
            <?php
            }

            if ($login_fabrica == 52) {
            ?>
                <td>
                    KM distância<br />
                    <input type="text" class="km" id="km_cadastra" style="width: 50px;" />
                </td>
            <?php
            }
            ?>
            <td>
                <button type="button" id="adicionar_cidade" >Adicionar</button>
                <img src="imagens/loading_img.gif" style="display: none; height: 20px; width: 20px;" id="loading_adicionar_cidade" />
            </td>
        </tr>
    </table>

    <table class='formulario linha_cidades' style='margin: 0 auto; width: 700px; border: 0; border-collapse: collapse;' cellpadding='1' cellspacing='3' >
        <tbody id="cidades_atendidas_cadastradas" >
            <tr>
                <?php
                $colspan = 3;

                if ($login_fabrica == 52) {
                    $colspan = 4;
                }
                ?>
                <td colspan="<?=$colspan?>" class='titulo_tabela'>Cidades cadastradas</td>
            </tr>
            <?php
            $sql = "SELECT
                        tbl_posto_fabrica_ibge.posto_fabrica_ibge,
                        tbl_cidade.nome AS cidade,
                        tbl_cidade.estado,
                        tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo,
                        tbl_posto_fabrica_ibge_tipo.nome AS tipo_nome,
                        tbl_posto_fabrica_ibge.km,
                        tbl_posto_fabrica_ibge.bairro
                    FROM tbl_posto_fabrica_ibge
                    INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_posto_fabrica_ibge.cidade
                    INNER JOIN tbl_posto_fabrica_ibge_tipo ON tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo = tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo AND tbl_posto_fabrica_ibge_tipo.fabrica = {$login_fabrica}
                    WHERE tbl_posto_fabrica_ibge.fabrica = {$login_fabrica}
                    AND tbl_posto_fabrica_ibge.posto = {$posto}";
            $res = pg_query($con, $sql);
            $rows = pg_num_rows($res);

            if ($rows > 0) {
                for ($i = 0; $i < $rows; $i++) {
                    $posto_fabrica_ibge      = pg_fetch_result($res, $i, "posto_fabrica_ibge");
                    $cidade                  = pg_fetch_result($res, $i, "cidade");
                    $estado                  = pg_fetch_result($res, $i, "estado");
                    $posto_fabrica_ibge_tipo = pg_fetch_result($res, $i, "posto_fabrica_ibge_tipo");
                    $tipo_nome               = pg_fetch_result($res, $i, "tipo_nome");
                    $km                      = pg_fetch_result($res, $i, "km");
                    $bairros                 = json_decode(pg_fetch_result($res, $i, "bairro"), true);
                    ?>

                    <tr>
                        <td style='border-bottom: 1px solid #000;' valign='top' >
                            <input type='text' readonly='readonly' value='<?=$cidade?> - <?=$estado?>' />
                        </td>
                        <?php
                        if ($login_fabrica == 74) {
                        ?>
                            <td name='bairros' style='border-bottom: 1px solid #000;' valign='top' >
                                <span>
                                    Bairros
                                    <input type='text' name='bairro_cadastra' />
                                    <button type='button' name='adicionar_bairro' rel='<?=$posto_fabrica_ibge?>' >Adicionar Bairro</button>
                                </span>

                                <?php
                                if (count($bairros) > 0) {
                                    foreach ($bairros as $bairro) {
                                        if (!strlen($bairro)) {
                                            continue;
                                        }

                                        $bairro = strtoupper(retira_acentos(utf8_decode($bairro)));
                                        echo "<div><input type='text' readonly='readonly' value='{$bairro}' /> <button type='button' name='excluir_bairro' rel='{$posto_fabrica_ibge}' >Excluir Bairro</button></div>";
                                    }
                                }
                                ?>
                            </td>
                        <?php
                        }

                        if ($login_fabrica != 74) {
                        ?>
                            <td style='border-bottom: 1px solid #000;' valign='top' >
                                <input type='text' readonly='readonly' value='<?=$tipo_nome?>' />
                            </td>
                        <?php
                        }

                        if ($login_fabrica == 52) {
                        ?>
                            <td style='border-bottom: 1px solid #000;' valign='top' >
                                <input type='text' class="km" style="width: 50px;" value='<?=$km?>' />
                            </td>
                        <?php
                        }
                        ?>
                        <td style='border-bottom: 1px solid #000;' valign='top' >
                            <?php
                            if ($login_fabrica == 52) {
                            ?>
                                <button type='button' name='salvar_cidade' rel='<?=$posto_fabrica_ibge?>' >Salvar Alteração</button>
                            <?php
                            }
                            ?>
                            <button type='button' name='excluir_cidade' rel='<?=$posto_fabrica_ibge?>' >Excluir Cidade</button>
                        </td>
                    </tr>
                <?php
                }
            }
            ?>
        </tbody>
    </table>

<?php
}
?>

<script language='javascript'>

$(document).ready(function(){
    var currentTime = new Date().getTime();
    function formatItem(row) {
        return row[0];
    }

     $('.km_distancia').keypress(function(event) {
    var tecla = (window.event) ? event.keyCode : event.which;
        if ((tecla > 47 && tecla < 58)) return true;
        else {
        if (tecla != 8) return false;
            else return true;
        }
    });
});
</script>

<br />

<script>
    $(function(){
        $("#entrega_tecnica").change(function() {
            var tipo_posto = $("select[name=tipo_posto]").find("option:selected").attr("rel");

            if (tipo_posto == "t") {
                if (!$("#entrega_tecnica").is(":checked")) {
                    $("#entrega_tecnica").attr("checked", "checked");
                }
            }
        });
    });
</script>

<table class="formulario" style="margin: 0 auto; width: 700px;" cellpadding="3" cellspacing="2">
<TR>
    <TD colspan='2' class='titulo_tabela' >Posto pode Digitar:</TD>
</TR>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="pedido_faturado" VALUE='t' <? if ($pedido_faturado == 't') echo ' checked ' ?>></TD>
    <TD align='left'>Pedido Faturado (Manual)</TD>
</TR>
<?php if ($login_fabrica == 20) : ?>

    <tr>
        <td align="center">
            <input type="checkbox" name="tela_os_nova" id="tela_os_nova" value="t" <?=($atendimento == 'n') ? 'checked' : ''?>>
        </td>
        <td align='left'>
            <label for="tela_os_nova">Usa tela nova de OS e novo Upload de OS</label>
        </td>
    </tr>

<?php endif; ?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia" VALUE='t' <? if ($pedido_em_garantia == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
    <TD align='left'>Pedido em Garantia (Manual)</TD>
</TR>

<? if ($login_fabrica == 1) {
    if($posto){
        $sql = "SELECT visivel FROM tbl_posto_condicao WHERE condicao = 62 AND posto = $posto";
//echo $sql;
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0){
            $pedido_em_garantia_finalidades_diversas = pg_fetch_result ($res,0,visivel);
        }
    }
?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia_finalidades_diversas" VALUE='t' <? if ($pedido_em_garantia_finalidades_diversas == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
    <TD align='left'>Pedido de Garantia ( Finalidades Diversas )</TD>
</TR>

<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="coleta_peca" VALUE='t' <? if ($coleta_peca == 't') echo 'checked' ?>></TD>
    <TD align='left'>Coleta de Peças</TD>
</TR>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="reembolso_peca_estoque" VALUE='t' <? if ($reembolso_peca_estoque == 't') echo 'checked' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
    <TD align='left'>Reembolso de Peça do Estoque ( Garantia Automática )</TD>
</TR>
<? } ?>
<? if (in_array($login_fabrica, array(6, 24, 81, 114))){ ?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="garantia_antecipada" VALUE='t' <? if ($garantia_antecipada == 't'){ echo ' checked ';} ?> ></TD>
    <TD align='left'>Pedido em Garantia Antecipada</TD>
</TR>
<? } ?>
<? if (in_array($login_fabrica, array(6))){ ?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="coleta_peca" VALUE='t' <? if ($coleta_peca == 't') echo 'checked' ?>></TD>
    <TD align='left'>Coleta de Peças</TD>
</TR>
<? } ?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="digita_os" VALUE='t' <? if ($digita_os == 't') echo ' checked ' ?> ></TD>
    <TD align='left'>Digita OS
    <?
    if($login_fabrica==11 and strlen($posto)>0){
        if($digita_os<>"t"){
            echo "<font color='red'><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Posto Bloqueado Para digitar OS.</b></font>";
        }
    }
    ?>
    </TD>
</TR><?php

if ($login_fabrica <> 86) {//HD 387824?>
    <TR>
        <TD align='center'><INPUT TYPE="checkbox" NAME="prestacao_servico" VALUE='t' <? if ($prestacao_servico == 't') echo ' checked ' ?>  <? if ($login_fabrica == 3) echo " disabled " ?>  ></TD>
        <TD align='left'>Prestação de Serviço<br><font size='-2'>&nbsp;Posto só recebe mão-de-obra. Peças são enviadas sem custo.</font></TD>
    </TR>
    <TR>
        <TD align='center'>
            <INPUT TYPE="checkbox" disabled NAME="pedido_via_distribuidor" VALUE='t'<?php
            if (strlen($posto) > 0) {
                if ($pedido_via_distribuidor == 't') echo ' checked '; else echo '';
                $sql = "SELECT      tbl_tipo_posto.distribuidor
                        FROM        tbl_tipo_posto
                        LEFT JOIN   tbl_posto_fabrica USING (tipo_posto)
                        WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
                        AND         tbl_posto_fabrica.posto = $posto;";
                $res = pg_query ($con,$sql);

                if (@pg_fetch_result($res,0,0) == 't') echo ''; else echo 'disabled';
            }
            if ($login_fabrica == 3) echo " disabled " ?> />
        </TD>
        <TD align='left'>PEDIDO VIA DISTRIBUIDOR</TD>
    </TR><?php
}
if ($login_fabrica == 1){ ?>
<TR>
    <TD align='center'>
    <?
    #HD 23738
    if ($condicao_escolhida == 'f'){
        $msg_bloqueio_condicao =" onClick='this.checked = !this.checked; alert(\"Posto já selecionou a condição de pagamento.\")' ";
    }
    if ($condicao_escolhida == 't'){
        $msg_bloqueio_condicao      = " disabled  ";
        $msg_bloqueio_condicao_desc = " <br><font size='-2'>Posto já escolheu a Condição de Pagamento</font>  ";
    }

    ?>
    <INPUT TYPE="checkbox" NAME="escolhe_condicao" VALUE='t' <? if ($escolhe_condicao == 't'){ echo ' checked ';} ?> <?=$msg_bloqueio_condicao?>>
    </TD>
    <TD align='left'>ESCOLHE CONDIÇÃO DE PAGAMENTO
        <?
        echo $msg_bloqueio_condicao_desc;

        if ($escolhe_condicao == 't'){
            if ($condicao_escolhida == ''){
                echo "<br><font size='-2'>Posto não escolheu a condição de pagamento</b></font>";
            }else{
                $sql = "SELECT tbl_black_posto_condicao.condicao
                        FROM tbl_black_posto_condicao
                        JOIN tbl_condicao ON tbl_condicao.condicao  = tbl_black_posto_condicao.id_condicao
                        WHERE tbl_black_posto_condicao.posto = $posto
                        AND   tbl_condicao.fabrica           = $login_fabrica
                        AND   tbl_condicao.promocao          IS NOT TRUE ";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res)>0){
                    $nome_condicao_escolhida = pg_fetch_result($res,0,0);
                    if ($condicao_escolhida == 'f'){
                        echo "<br><font size='-2'>Condição de Pagamento escolhida: <b>$nome_condicao_escolhida</b></font>";
                        echo "&nbsp;&nbsp;&nbsp;Liberar ";
                        echo "<INPUT TYPE='checkbox' NAME='condicao_liberada' VALUE='t'>";
                    }else{
                        echo "<br><font size='-2'>Condição de Pagamento escolhida: <b>$nome_condicao_escolhida</b></font>";
                    }
                }
            }
        }
        ?>
    </TD>
</TR>
<? } ?>

<? if ($login_fabrica == 19){ ?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="atende_comgas" VALUE='t' <? if ($atende_comgas == 't'){ echo ' checked ';} ?> ></TD>
    <TD align='left'>Atend.Comgás<br><font size='-2'>&nbsp;Posto pode digitar OS Comgás.</font></TD>
</TR>
<? } ?>
<? if ($login_fabrica == 20){ # HD 85632?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="prestacao_servico_sem_mo" VALUE='t' <? if ($prestacao_servico_sem_mo == 't'){ echo ' checked ';} ?> ></TD>
    <TD align='left'>PRESTAÇÃO DE SERVIÇO ISENTA DE MO<br><font size='-2'>&nbsp;Posto só recebe valor das peças. Mão-de-obra não será cobrada.</font></TD>
</TR>
<? } ?>
<? if ($login_fabrica == 74){ # HD 384558?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="pedido_bonificacao" VALUE='t' <? if ($pedido_bonificacao == 't'){ echo ' checked ';} ?> ></TD>
    <TD align='left'>Pedido Bonificação</TD>
</TR>
<? } ?>

<?
if ($login_fabrica == 50 and strlen($posto) > 0) {
    $sql = "SELECT * FROM tbl_posto_fabrica_foto WHERE posto = $posto and fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $posto_fabrica_foto     = pg_fetch_result($res,0,posto_fabrica_foto);

        $caminho_foto_posto     = pg_fetch_result($res,0,foto_posto);
        $caminho_thumb_posto    = pg_fetch_result($res,0,foto_posto_thumb);
        $descricao_foto_posto   = pg_fetch_result($res,0,foto_posto_descricao);

        $caminho_foto_contato1   = pg_fetch_result($res,0,foto_contato1);
        $caminho_thumb_contato1  = pg_fetch_result($res,0,foto_contato1_thumb);
        $descricao_foto_contato1 = pg_fetch_result($res,0,foto_contato1_descricao);

        $caminho_foto_contato2   = pg_fetch_result($res,0,foto_contato2);
        $caminho_thumb_contato2  = pg_fetch_result($res,0,foto_contato2_thumb);
        $descricao_foto_contato2 = pg_fetch_result($res,0,foto_contato2_descricao);
    }

    echo "<table class='border' style='margin: 0 auto; width: 700px; border: 0;' cellpadding='1' cellspacing='3'>";
        echo "<tr>";
            echo "<td colspan='5'><img src='imagens/cab_fotosposto.gif'></td>";
        echo "</tr>";

        echo "<tr>";
            echo "<td width='216'>Posto</td>";
            echo "<td width='216'>Contato 1</td>";
            echo "<td width='216'>Contato 2</td>";
        echo "</tr>";

        echo "<tr>";
            echo "<td>";
                if (strlen($caminho_foto_posto) > 0) {
                    $image = $caminho_foto_posto;
                    $size = getimagesize("$image");
                    $height = $size[1];
                    $width  = $size[0];
                    echo "<IMG SRC='$caminho_thumb_posto' WIDTH='100' HEIGHT='100' onclick=\"javascript:window.open('$caminho_foto_posto','Posto','status=no,scrollbars=no,width=$width,height=$height');\">";
                    echo "<BR>$descricao_foto_posto";
                    echo "<BR><a href=\"javascript: if(confirm('Deseja excluir esta foto?')) window.location = '$PHP_SELF?posto=$posto&excluir_foto=$posto_fabrica_foto&foto=foto_posto'\">";
                    echo "<img src='imagens/btn_x.gif' WIDTH='10' HEIGTH='10'><font size='1'>Excluir</font></a>";
                } else {
                    echo "<B>Selecione a imagem (jpg,gif,png):</B><BR><input class='frm' type='file' value='Procurar foto' name='foto_posto' class='multi {accept:\'jpg|gif|png\', max:1, STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}' />";
                    echo "<BR><INPUT class='frm' TYPE='text' size='30' value='$descricao_foto_posto' maxlength='100' name='descricao_foto_posto'>";
                }
            echo "</td>";
            echo "<td>";
                if (strlen($caminho_foto_contato1) > 0) {
                    $image = $caminho_foto_contato1;
                    $size = getimagesize("$image");
                    $height = $size[1];
                    $width  = $size[0];
                    echo "<IMG SRC='$caminho_thumb_contato1' WIDTH='100' HEIGHT='100' onclick=\"javascript:window.open('$caminho_foto_contato1','Contato','status=yes,scrollbars=no,width=$width,height=$height');\">";
                    echo "<BR>$descricao_foto_contato1";
                    echo "<BR><a href=\"javascript: if(confirm('Deseja excluir esta foto?')) window.location = '$PHP_SELF?posto=$posto&excluir_foto=$posto_fabrica_foto&foto=foto_contato1'\">";
                    echo "<img src='imagens/btn_x.gif' WIDTH='10' HEIGTH='10'><font size='1'>Excluir</font></a>";
                } else {
                    echo "<B>Selecione a imagem (jpg,gif,png):</B><BR><input  class='frm' type='file' value='Procurar foto' name='foto_contato1' class='multi {accept:\'jpg|gif|png\', max:1, STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}' />";
                    echo "<BR><INPUT class='frm' TYPE='text' size='30' value='$descricao_foto_contato1' maxlength='100' name='descricao_foto_contato1'>";
                }
            echo "</td>";
            echo "<td>";
                if (strlen($caminho_foto_contato2) > 0) {
                    $image = $caminho_foto_contato2;
                    $size = getimagesize("$image");
                    $height = $size[1];
                    $width  = $size[0];
                    echo "<IMG SRC='$caminho_thumb_contato2' WIDTH='100' HEIGHT='100' onclick=\"javascript:window.open('$caminho_foto_contato2','Contato','status=yes,scrollbars=no,width=$width,height=$height');\">";
                    echo "<BR>$descricao_foto_contato2";
                    echo "<BR><a href=\"javascript: if(confirm('Deseja excluir esta foto?')) window.location = '$PHP_SELF?posto=$posto&excluir_foto=$posto_fabrica_foto&foto=foto_contato2'\">";
                    echo "<img src='imagens/btn_x.gif' WIDTH='10' HEIGTH='10'><font size='1'>Excluir</font></a>";
                } else {
                    echo "<B>Selecione a imagem (jpg,gif,png):</B><BR><input class='frm' type='file' value='Procurar foto' name='foto_contato2' class='multi {accept:\'jpg|gif|png\', max:1, STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}' />";
                    echo "<BR><INPUT class='frm' TYPE='text' size='30' value='$descricao_foto_contato2' maxlength='100' name='descricao_foto_contato2'>";
                }
            echo "</td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td colspan='3'><FONT color='#B1B1B1' size='1'>Clique sobre a imagem para ampliar</font></td>";
        echo "</tr>";
    echo "</table>";
}
?>

<?
if (strlen($data_alteracao) > 0 AND strlen($admin) > 0){
?>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0; font-weight: bold;" cellpadding="3" cellspacing="2">
<tr>
    <td>&nbsp;</td>
</tr>
<tr>
    <td >Última alteração:</td>
    <td>Em: <? echo $data_alteracao; ?></td>
    <td>Usuário:  <?
    $sql = "SELECT login,fabrica FROM tbl_admin WHERE (fabrica = $login_fabrica OR fabrica=10) AND admin = $admin";
    $res = pg_query($con,$sql);

    echo pg_fetch_result($res,0,login);
    if(pg_fetch_result($res,0,fabrica)==10)echo " <font size='1'>(Telecontrol)</font>";
    ?></td>
</tr>
</table>

<?
}
?>
<?if ($login_fabrica == 42){ #HD 401553 INICIO?>
<tr>
    <td colspan='2' >
        <table class='formulario' cellpadding='0' cellspacing='0' style="margin: 0 auto; width: 700px; border: 0;">

            <tr>
                <td class='titulo_coluna'>Filiais</td>
            </tr>

            <tr>

                <td align='center'>

                    <div style='width:700px;'>

                        <style type="text/css">

                            .lista_filial {
                                list-style:none;
                            }

                            .lista_filial li{
                                display:block;
                                float:left;
                                border: 1px solid #fff;
                                margin: 2px;
                                padding: 2px 15px 2px 15px;
                            }

                        </style>

                        <ul class='lista_filial'>
                            <?
                            $sql_distribuidores = "
                                SELECT
                                    tbl_posto_fabrica.nome_fantasia,
                                    tbl_posto_fabrica.posto
                                FROM tbl_posto_fabrica
                                WHERE tbl_posto_fabrica.fabrica=$login_fabrica
                                AND tbl_posto_fabrica.filial is true
                                AND tbl_posto_fabrica.posto <> 6359
                                order by posto

                            ";

                            $res_distribuidores = pg_query($con,$sql_distribuidores);

                            for ($x = 0; $x < pg_num_rows($res_distribuidores);$x++){

                                $nome_fantasia_distrib = pg_result($res_distribuidores, $x, 'nome_fantasia');
                                $posto_distrib = pg_result($res_distribuidores, $x, posto);

                                if ($_GET['posto']){
                                    $posto_edicao = $_GET['posto'];

                                    $sql_posto_filial = "
                                        SELECT
                                            posto,
                                            filial_posto
                                        from tbl_posto_filial
                                        where posto=$posto_edicao
                                        and filial_posto = $posto_distrib
                                    ";

                                    $res_posto_filial = pg_query($con,$sql_posto_filial);

                                    $checked_distrib = (pg_num_rows($res_posto_filial)>0) ? "CHECKED" : null;
                                }else{
                                    $checked_distrib = null;
                                }

                            ?>
                                <li>
                                    <input type="hidden" name="posto_distrib_<?=$x?>" value='<?=$posto_distrib?>' />
                                    <input type="checkbox" name="cad_distribuidor_<?=$x?>" id="cad_distribuidor_<?=$x?>" value='t' style='margin-top: 2px;' <?echo $checked_distrib?> />
                                    <?echo $nome_fantasia_distrib?>
                                </li>
                            <?
                            }
                            ?>
                        </ul>
                        <div style='clear:both'></div>
                    </div>

                </td>
            </tr>

            <tr><td>&nbsp;</td></tr>

        </table>
    </td>
</tr>
<?}  #HD 401553 FIM?>
<tr><td>&nbsp;</td></tr>
<tr>
    <td colspan='4'>
<a name="postos">
<br>
<center>
<input type="hidden" name="btn_acao">
<input type="button" value="Gravar" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<!-- img src='imagens_admin/btn_apagar.gif' style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { if(confirm('Deseja realmente DESCREDENCIAR este POSTO?') == true) { document.frm_posto.btn_acao.value='descredenciar'; document.frm_posto.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }" ALT="Apagar a Ordem de Serviço" border='0' -->
<input type="button"  value="Limpar" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>
</center>
</a>
<br>
</td></tr>
</TABLE>
<!-- ============================ Botoes de Acao ========================= -->
</form>

<?
# HD - Monteiro
if(in_array($login_fabrica,array(81,114,122,123,125,128,136))){
    //Se for enviado
?>

    <form method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
        <p>Obs* Gravar fotos no formato JPEG|JPG|PNG</p>
        <table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
            <tr>
                <td colspan="4" class="titulo_tabela">
                    Imagens do Posto
                </td>
            </tr>
            <tr>
                <td id='excluir_1'>
                    <?
                    $caminho_imagem = '../autocredenciamento/fotos/';
                    $caminho_path   = '../autocredenciamento/fotos/';
                    $cnpj_img = preg_replace('/\D/','',utf8_decode($cnpj));
                    $img_path = $caminho_path.$cnpj_img;
                    $img_caminho = $caminho_imagem.$cnpj_img;

                    if (is_numeric($posto))
                    if (file_exists($img_caminho."_1.jpg")) $img_ext = "jpg";
                    if (file_exists($img_caminho."_1.png")) $img_ext = "png";
                    if (file_exists($img_caminho."_1.gif")) $img_ext = "gif";
                    if ($img_ext) {
                        $img_src = $img_path."_1.$img_ext";
                    ?>
			<img src="<?php echo $img_src;?>" onclick="showImage('<?php echo $img_src; ?>')" style="width:125px; height:125px;" />
                        <br />
			<img src='../imagens/excluir_loja.gif' class='excluir' onclick="deleteImage('<?php echo $img_src; ?>','excluir_1')"  style='cursor:pointer' alt='Excluir Imagem' title='Excluir imagem' />
                    <?}
                    unset($img_ext);
                    ?>

                </td>
                <td id='excluir_2'>
                    <?
                    if (is_numeric($posto))
                    if (file_exists($img_caminho."_2.jpg")) $img_ext = "jpg";
                    if (file_exists($img_caminho."_2.png")) $img_ext = "png";
                    if (file_exists($img_caminho."_2.gif")) $img_ext = "gif";
                    if ($img_ext) {
                        $img_src = $img_path."_2.$img_ext";
                    ?>
                            <img src="<?php echo $img_src;?>" style="width:125px; height:125px;" onclick="showImage('<?php echo $img_src; ?>')"/>
                        <br />
			<img src='../imagens/excluir_loja.gif' class='excluir' onclick="deleteImage('<?php echo $img_src; ?>','excluir_2')"  style='cursor:pointer' alt='Excluir Imagem' title='Excluir imagem' />

                    <?}
                    unset($img_ext);
                    ?>

                </td>
                <td id='excluir_3'>
                    <?
                    if (is_numeric($posto))
                    if (file_exists($img_caminho."_3.jpg")) $img_ext = "jpg";
                    if (file_exists($img_caminho."_3.png")) $img_ext = "png";
                    if (file_exists($img_caminho."_3.gif")) $img_ext = "gif";
                    if ($img_ext) {
                        $img_src = $img_path."_3.$img_ext";
                    ?>
                            <img src="<?php echo $img_src;?>" style="width:125px; height:125px;" onclick="showImage('<?php echo $img_src; ?>')"/>
                        <br />
			<img src='../imagens/excluir_loja.gif' class='excluir' onclick="deleteImage('<?php echo $img_src; ?>','excluir_3')"  style='cursor:pointer' alt='Excluir Imagem' title='Excluir imagem' />
                    <?}
                    unset($img_ext);
                    ?>

                </td>
            </tr>
            <tr>
                <td>Fachada</td>
                <td>Recepção</td>
                <td>Bancada</td>
            </tr>
            <tr>
                <td>
                    <input type='file' name='arquivo1' id='arquivo1' class="arquivo1" accept="jpeg|jpg" size='1' style="width:125px;" />
                </td>
                <td>
                    <input type='file' name='arquivo2' id='arquivo2' class="arquivo2" accept="jpeg|jpg" size='1' style="width:125px;" />
                </td>
                <td>
                    <input type='file' name='arquivo3' id='arquivo3' class="arquivo3" accept="jpeg|jpg" size='1' style="width:125px;" />
                </td>
            </tr>
            <tr align="center" colspan="3">
                <td align="center" colspan="3">
                    <p>
                    <input type="hidden" name="cnpj_imagem" value="<? echo $cnpj ?>">
                    <input type="hidden" name="posto_imagem" value="<? echo $posto ?>">
                    <input type="submit" name="gravarimagem" id="gravarimagem" value=" Gravar Imagem ">
                    </p>
                </td>
            </tr>

        </table>
    </form>

<?
}
?>
<br />

<table style="margin: 0 auto; width: 700px;">
<? if (strlen($posto) > 0) {
    ?>
    <tr>
        <td>
            <button type="button" onclick='javascript: document.frm_login.login.value = document.frm_posto.codigo.value; alert("Atenção, irá abrir uma nova janela para que se trabalhe como se fosse este posto ! " + document.frm_posto.codigo.value); document.frm_login.senha.value = document.frm_posto.senha.value ; document.frm_login.submit() ;/* window.location = "<? echo $PHP_SELF ?>"*/;'>Clique Aqui para acessar como se fosse este Posto</button>
        </td>
    </tr>
    <? if (in_array($login_fabrica,array(81,114,122,123,125,128,136))) {?>
    <tr><td>&nbsp;</td></tr>
    <tr>
        <td>
            <input type="button" value="Gerar contrato" onclick="javascript: window.open('../credenciamento/gera_contrato.php?fabrica=<?=$login_fabrica?>&cnpj=<?=$cnpj?>&tipo_arquivo=pdf&btn_acao=1')">

            <? if(is_file("anexos/contrato_$posto.pdf")){?>

                <input type='button' value='Abrir contrato' onclick="window.open('anexos/contrato_<?=$posto?>.pdf')">

            <? } ?>
        </td>
    </tr>
    <tr><td>&nbsp;</td></tr>
    <tr>
        <td>
            <form name='frm_contrato' method='post' enctype='multipart/form-data'>
                <input type='file' name='contrato'>
                <input type='hidden' name='posto_contrato' value='<?=$posto?>'>
                <input type='submit' value='Anexar Contrato' name='anexa_contrato'> <br><br>
                <span style='font-size:10px;color:#000'>
                    Tipos de arquivos suportados (PDF,DOC,ZIP e RAR).<br>Tamanho máximo do arquivo de 2MB
                </span>
            </form>
        </td>
    </tr>

<? } ?>
</div>
<? } ?>
</table>

<? // <form name="frm_login" method="post" target="_blank" action="../index.php"> ?>
<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&acao=validar&redir=sim'>
<input type="hidden" name="login">
<input type="hidden" name="senha">
<input type="hidden" name="btnAcao" value="Enviar">
</form>
<p><?php

if ($_GET ['listar'] == 'todos') {

    // gera nome xls
    if ($login_fabrica == 3 OR $login_fabrica == 1) {

        $data = date ("d-m-Y-H-i");

        $arquivo_nome = "relatorio_todos_postos-$data.xls";
        $path         = "/var/www/assist/www/admin/xls/";
        #$path         = "/home/ronald/public_html/posvenda/admin/xls/";
        $path_tmp     = "/tmp/assist/";

        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

        echo `rm $arquivo_completo_tmp `;
        echo `rm $arquivo_completo_tmp.zip `;
        echo `rm $arquivo_completo.zip `;
        echo `rm $arquivo_completo `;

        $fp = fopen ($arquivo_completo_tmp,"w");
        if($login_fabrica == 3){
            fputs ($fp, "NOME \t CÓDIGO \t TIPO \t CREDENCIAMENTO \t PEDIDO FATURADO \t PEDIDO EM GARANTIA \t DIGITA OS \t PRESTAÇÃO DE SERVIÇO \t PEDIDO VIA DISTRIBUIDOR \t CNPJ \t I.E. \t FONE \t FAX \t CONTATO \t ENDEREÇO \t NÚMERO \t COMPLEMENTO \t BAIRRO \t CEP \t CIDADE \t  ESTADO \t E-MAIL \r\n");
        }else{
            fputs ($fp, "NOME \t CÓDIGO \t CNPJ \t FONE \t FAX \t CONTATO \t ENDEREÇO \t NÚMERO \t COMPLEMENTO \t BAIRRO \t CEP \t CIDADE \t ESTADO \t E-MAIL \t CATEGORIA POSTO \t TIPO \t CREDENCIAMENTO \t PEDIDO FATURADO \t PEDIDO EM GARANTIA \t COLETA DE PEÇAS \t REEMBOLSO DE PEÇAS DO ESTOQUE \t DIGITA OS \t PRESTAÇÃO DE SERVIÇO \t PEDIDO VIA DISTRIBUIDOR \t OPÇÃO DE EXTRATO \t DATA AUTORIZAÇÃO \t RESPONSAVEL \t TIPO DE ENVIO DE NF\r\n");
        }

    }
    // fim gera nome xls

    $sql = "SELECT  tbl_posto.posto                           ,
                    tbl_posto.cnpj                            ,
                    tbl_posto.contato                         ,
                    tbl_posto.ie                              ,
                    tbl_posto_fabrica.contato_cidade  as cidade        ,
                    tbl_posto_fabrica.contato_estado  as estado       ,
                    tbl_posto_fabrica.contato_endereco       AS endereco,
                    tbl_posto_fabrica.contato_numero         AS numero,
                    tbl_posto_fabrica.contato_complemento    AS complemento,
                    tbl_posto_fabrica.contato_bairro         AS bairro,
                    tbl_posto_fabrica.contato_cep            AS cep,
                    tbl_posto_fabrica.contato_email          AS email,
                    tbl_posto_fabrica.contato_fone_comercial AS fone,
                    tbl_posto_fabrica.contato_fone_residencial AS fone2,
                    tbl_posto_fabrica.contato_fax            AS fax,
                    tbl_posto.nome                            ,
                    tbl_posto.pais                            ,
                    tbl_posto_fabrica.codigo_posto            ,
                    tbl_tipo_posto.descricao                  ,
                    tbl_posto_fabrica.pedido_faturado         ,
                    tbl_posto_fabrica.pedido_em_garantia      ,
                    tbl_posto_fabrica.coleta_peca             ,
                    tbl_posto_fabrica.reembolso_peca_estoque  ,
                    tbl_posto_fabrica.digita_os               ,
                    tbl_posto_fabrica.controla_estoque        ,
                    tbl_posto_fabrica.prestacao_servico       ,
                    tbl_posto_fabrica.prestacao_servico_sem_mo,
                    tbl_posto_fabrica.pedido_via_distribuidor ,
                    tbl_posto_fabrica.pedido_bonificacao      ,
                    tbl_posto_fabrica.credenciamento          ,
                    tbl_posto_fabrica.categoria          ,
                    to_char(tbl_posto_fabrica.contrato,'DD/MM/YYYY HH24:MI')    as contrato,
                    to_char(tbl_posto_fabrica.atualizacao,'DD/MM/YYYY HH24:MI') as atualizacao,
                    tbl_tipo_gera_extrato.responsavel,
                    tbl_tipo_gera_extrato.tipo_envio_nf,
                    tbl_intervalo_extrato.descricao as intervalo_extrato,
                    TO_CHAR (tbl_tipo_gera_extrato.data_atualizacao, 'dd/mm/YYYY hh24:ii:ss') AS data_atualizacao,
                    tbl_tipo_gera_extrato.intervalo_extrato
            FROM    tbl_posto
            JOIN    tbl_posto_fabrica USING (posto)
            LEFT JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
            LEFT JOIN tbl_tipo_gera_extrato ON tbl_posto_fabrica.fabrica = tbl_tipo_gera_extrato.fabrica AND tbl_posto_fabrica.posto = tbl_tipo_gera_extrato.posto
            LEFT JOIN tbl_intervalo_extrato USING(intervalo_extrato)
            LEFT JOIN tbl_empresa_cliente ON tbl_posto.posto = tbl_empresa_cliente.posto AND tbl_empresa_cliente.fabrica = tbl_posto_fabrica.fabrica
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica";

    if ($login_fabrica == 20) {
        if ($login_admin == (590) OR $login_admin == (364) OR $login_admin == (588)) $sql .= " AND 1 = 1 ";
        else $sql .= "AND tbl_posto.pais = 'BR'";
        $sql .=" ORDER BY tbl_posto.pais,tbl_posto_fabrica.credenciamento, tbl_posto.nome";
    } else {
        $sql .=" ORDER BY tbl_posto_fabrica.credenciamento, tbl_posto.nome";
    }

    $res = pg_query ($con,$sql);
    // echo nl2br($sql);
    if (pg_num_rows($res) > 0) {

        echo "<table style='border: 0;' cellpadding='3' cellspacing='0' class='formulario'>";
                echo "<tr class='titulo_coluna'>";
                if ($login_fabrica == 20) {
                    echo "<td nowrap rowspan='2'>País</td>";
                }
                echo "<td nowrap rowspan='2'>Cidade</td>";
                echo "<td nowrap rowspan='2'>Estado</td>";
                echo "<td nowrap rowspan='2'>Nome</td>";
                echo "<td nowrap rowspan='2'>Código</td>";

                if ($login_fabrica == 1) {
                    echo "<td nowrap rowspan='2'>Email</td>";
                    echo "<td nowrap rowspan='2'>Categoria Posto</td>";
                }

                if ($login_fabrica == 5) {
                    echo "<td nowrap rowspan='2'>Telefone</td>";
                    echo "<td nowrap rowspan='2'>Email</td>";
                    echo "<td nowrap rowspan='2'>Endereço</td>";
                    echo "<td nowrap rowspan='2'>Bairro</td>";
                }
                if ($login_fabrica == 15) {
                    echo "<td nowrap rowspan='2'>I.E.</td>";
                }
                echo "<td nowrap rowspan='2'>Tipo</td>";
                echo "<td nowrap rowspan='2'>Credenciamento</td>";
                if ($login_fabrica == 15) {
                    echo "<td nowrap rowspan='2'>Data Atualização</td>";
                }
                if (in_array($login_fabrica, array(25, 47, 81, 114, 123, 124,125,128,136))) {
                    echo "<td nowrap rowspan='2'>Data Contrato</td>";
                }
                echo "<td nowrap colspan='7'>Posto pode Digitar</td>";
                if ($login_fabrica == 1) {

                    echo '<td colspan="4">Opções de Extrato</td>';

                }
                echo "</tr>";
                echo "<tr class='Titulo'>";
                echo "<td>Pedido Faturado</td>";
                echo "<td>Pedido em Garantia</td>";
                if ($login_fabrica == 1) {
                    echo "<td>Coleta de Peças</td>";
                    echo "<td>Reembolso de Peça do Estoque</td>";
                }
                echo "<td>Digita OS</td>";
                echo "<td>Prestação de Serviço</td>";
                if($login_fabrica == 20) echo "<td>Prestação de Serviço Isenta de MO</td>";
                echo "<td>Pedido via Distribuidor</td>";
                if ($login_fabrica == 1) {
                    echo '<td>Opção de Extrato</td>';
                    echo '<td>Data Atualização</td>';
                    echo '<td>Responsável</td>';
                    echo '<td>Tipo de envio de NF</td>';
                }
                if($login_fabrica == 74) echo "<td>Pedido Bonificação</td>";
                echo "</tr>";
        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

            $posto = pg_fetch_result($res,$i,posto);

            // conteudo excel
            if ($login_fabrica == 3 OR $login_fabrica == 1) {

                $pedido_faturado         = (pg_fetch_result($res, $i, 'pedido_faturado') =='t')         ? "Sim" : "Não";
                $pedido_em_garantia      = (pg_fetch_result($res, $i, 'pedido_em_garantia') =='t')      ? "Sim" : "Não";
                $digita_os               = (pg_fetch_result($res, $i, 'digita_os') =='t')               ? "Sim" : "Não";
                $controla_estoque        = (pg_fetch_result($res, $i, 'controla_estoque') =='t')        ? "Sim" : "Não";
                $prestacao_servico       = (pg_fetch_result($res, $i, 'prestacao_servico') =='t')       ? "Sim" : "Não";
                $pedido_via_distribuidor = (pg_fetch_result($res, $i, 'pedido_via_distribuidor') =='t') ? "Sim" : "Não";
                $pedido_bonificacao = (pg_fetch_result($res, $i, 'pedido_bonificacao') =='t') ? "Sim" : "Não";
                $reembolso_peca_estoque = (pg_fetch_result($res, $i, 'reembolso_peca_estoque') =='t') ? "Sim" : "Não";
                $coleta_peca = (pg_fetch_result($res, $i, 'coleta_peca') =='t') ? "Sim" : "Não";
                $intervalo_extrato = (pg_fetch_result($res,$i, 'intervalo_extrato'));

                if($login_fabrica == 3){
                    fputs($fp,pg_fetch_result($res,$i,'nome')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'codigo_posto')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'descricao')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'credenciamento')."\t");
                    fputs($fp,$pedido_faturado."\t");
                    fputs($fp,$pedido_em_garantia."\t");
                    fputs($fp,$digita_os."\t");
                    fputs($fp,$prestacao_servico."\t");
                    fputs($fp,$pedido_via_distribuidor."\t");
                    fputs($fp,pg_fetch_result($res,$i,'cnpj')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'ie')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'fone')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'fax')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'contato')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'endereco')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'numero')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'complemento')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'bairro')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'cep')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'cidade')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'estado')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'email')."\t");
                    fputs($fp,"\r\n");
                }else{
                    fputs($fp,pg_fetch_result($res,$i,'nome')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'codigo_posto')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'cnpj')."\0\t");
                    fputs($fp,pg_fetch_result($res,$i,'fone')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'fax')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'contato')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'endereco')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'numero')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'complemento')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'bairro')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'cep')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'cidade')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'estado')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'email')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'categoria')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'descricao')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'credenciamento')."\t");
                    fputs($fp,$pedido_faturado."\t");
                    fputs($fp,$pedido_em_garantia."\t");
                    fputs($fp,$coleta_peca."\t");
                    fputs($fp,$reembolso_peca_estoque."\t");
                    fputs($fp,$digita_os."\t");
                    fputs($fp,$prestacao_servico."\t");
                    fputs($fp,$pedido_via_distribuidor."\t");
                }

            }
            // fim  conteudo excel

            /*Retira todos usuários do TIME*/
            $sql = "SELECT *
                    FROM  tbl_empresa_cliente
                    WHERE posto   = $posto
                    AND   fabrica = $login_fabrica";
            $res2 = pg_query ($con,$sql);
            if (pg_num_rows($res2) > 0) continue;
            $sql = "SELECT *
                    FROM  tbl_empresa_fornecedor
                    WHERE posto   = $posto
                    AND   fabrica = $login_fabrica";
            $res2 = pg_query ($con,$sql);
            if (pg_num_rows($res2) > 0) continue;

            $sql = "SELECT *
                    FROM  tbl_erp_login
                    WHERE posto   = $posto
                    AND   fabrica = $login_fabrica";
            $res2 = pg_query ($con,$sql);
            if (pg_num_rows($res2) > 0) continue;

            $x = ($login_fabrica==3) ? $i : $i % 20;

/*
            Estava repetindo os campos aleatoriamente, por isso resolvi tirar de dentro do loop. HD 268395
            if ($x == 0) {
                flush();
                echo "<tr class='titulo_coluna'>";
                if ($login_fabrica == 20) {
                    echo "<td nowrap rowspan='2'>País</td>";
                }
                echo "<td nowrap rowspan='2'>Cidade</td>";
                echo "<td nowrap rowspan='2'>Estado</td>";
                echo "<td nowrap rowspan='2'>Nome</td>";
                echo "<td nowrap rowspan='2'>Código</td>";
                if ($login_fabrica == 15) {
                    echo "<td nowrap rowspan='2'>I.E.</td>";
                }
                echo "<td nowrap rowspan='2'>Tipo</td>";
                echo "<td nowrap rowspan='2'>Credenciamento</td>";
                if ($login_fabrica == 15) {
                    echo "<td nowrap rowspan='2'>Data Atualização</td>";
                }
                if (in_array($login_fabrica, array(25, 47, 81, 114))) {
                    echo "<td nowrap rowspan='2'>Data Contrato</td>";
                }
                echo "<td nowrap colspan='7'>Posto pode Digitar</td>";
                echo "</tr>";
                echo "<tr class='Titulo'>";
                echo "<td>Pedido Faturado</td>";
                echo "<td>Pedido em Garantia</td>";
                if ($login_fabrica == 1) {
                    echo "<td>Coleta de Peças</td>";
                    echo "<td>Reembolso de Peça do Estoque</td>";
                }
                echo "<td>Digita OS</td>";
                echo "<td>Prestação de Serviço</td>";
                if($login_fabrica == 20) echo "<td>Prestação de Serviço Isenta de MO</td>";
                echo "<td>Pedido via Distribuidor</td>";
                echo "</tr>";
            }
*/

            $cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

            echo "<tr class='Conteudo' bgcolor='$cor'>";

            if ($login_fabrica == 20) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'pais') . "</td>";
            }
            echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'cidade') . "</td>";
            echo "<td nowrap>" . pg_fetch_result($res,$i,'estado') . "</td>";
            echo "<td nowrap align='left'><a href='$PHP_SELF?posto=" . pg_fetch_result($res,$i,'posto') . "'>" . pg_fetch_result($res,$i,'nome') . "</a></td>";
            echo "<td nowrap>" . pg_fetch_result($res,$i,'codigo_posto') . "</td>";

            if ($login_fabrica == 1) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'email') . "</td>";
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'categoria') . "</td>";
            }

            if ($login_fabrica == 5) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'fone') . "</td>";
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'email') . "</td>";
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'endereco') . "</td>";
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'bairro') . "</td>";
            }
            if ($login_fabrica == 15) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'ie') . "</td>";
            }
            echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'descricao') . "</td>";
            echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'credenciamento') . "</td>";
            if ($login_fabrica == 15) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'atualizacao') . "</td>";
            }
                if (in_array($login_fabrica, array(25, 47, 81, 114, 123,124,125,136))) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'contrato') . "</td>";
            }
            echo "<td>";
            if (pg_fetch_result($res,$i,'pedido_faturado') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
            echo "</td>";
            echo "<td>";
            if (pg_fetch_result($res,$i,'pedido_em_garantia') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
            echo "</td>";
            if ($login_fabrica == 1) {
                echo "<td>";
                if (pg_fetch_result($res,$i,'coleta_peca') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
                echo "<td>";
                if (pg_fetch_result($res,$i,'reembolso_peca_estoque') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
            }
            echo "<td>";
            if (pg_fetch_result($res,$i,'digita_os') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
            echo "</td>";
            echo "<td>";
            if (pg_fetch_result($res,$i,'prestacao_servico') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
            echo "</td>";
            if($login_fabrica == 20) { #HD 85632
                echo "<td>";
                if (pg_fetch_result($res,$i,'prestacao_servico_sem_mo') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
            }
            echo "<td>";
            if (pg_fetch_result($res,$i,'pedido_via_distribuidor') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
            echo "</td>";

            if ($login_fabrica == 1) {

                echo '<td>&nbsp;'.$intervalo_extrato.'</td>';
                echo '<td>&nbsp;'.pg_result($res,$i,'data_atualizacao').'</td>';
                echo '<td>&nbsp;'.pg_result($res,$i,'responsavel').'</td>';
                switch(pg_result($res,$i,'tipo_envio_nf')) {

                    case 'correios' : $tipo_envio_nfe = 'Correios'; break;
                    case 'online_possui_nfe' : $tipo_envio_nfe = 'Online/Possui NF-e'; break;
                    case 'online_nao_possui_nfe': $tipo_envio_nfe = 'Online/Não Possui NF-e'; break;

                    default: $tipo_envio_nfe = ''; break;

                }

                echo '<td>&nbsp;'.$tipo_envio_nfe.'</td>';

                fputs($fp,$intervalo_extrato."\t");
                fputs($fp,pg_result($res,$i,'data_atualizacao')."\t");
                fputs($fp,pg_result($res,$i,'responsavel')."\t");
                fputs($fp,$tipo_envio_nfe."\t");
                fputs($fp,"\r\n");

            }

            if($login_fabrica == 74) { #HD 384458
                echo "<td>";
                if (pg_fetch_result($res,$i,'pedido_bonificacao') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    //final gera relatorio excel
    if ($login_fabrica==3 OR $login_fabrica == 1){
        fclose ($fp);
        flush();

        echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;
        echo "<br><p id='id_download2'><a href='xls/$arquivo_nome.zip'><img src='../imagens/excel.gif'><br><font color='#3300CC'>Fazer download do relatório de todos os postos</font></a></p><br>";
    }
    //fim final gera relatorio excel

}

include "rodape.php"; ?>
