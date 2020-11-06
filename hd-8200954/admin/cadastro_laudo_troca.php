<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "autentica_admin.php";
include_once "../class/email/PHPMailer/class.phpmailer.php";
include_once "../class/email/PHPMailer/PHPMailerAutoload.php";
require_once "../classes/autoload.php";
require_once "../class/aws/anexaS3.class.php";
include_once "../classes/mpdf61/mpdf.php";
// include_once '../class/tdocs.class.php';
include_once '../class/communicator.class.php';
include_once '../funcoes.php';
$mailTc = new TcComm($externalId);//classe
header ('Content-type: text/html; charset=UTF-8');
$login_fabrica = 30;
$sql = "SELECT  aprova_laudo
        FROM    tbl_admin
        WHERE   fabrica = $login_fabrica
        AND     admin = $login_admin
";
$res = pg_query($con,$sql);
$aprova_laudo = pg_fetch_result($res,0,aprova_laudo);

$os = (strlen($_REQUEST["imprimir"] > 0)) ? $_REQUEST["imprimir"] : $_REQUEST["alterar"];

if(isset($_REQUEST['imprimir'])){
    $os = $_REQUEST['imprimir'];
}

if(isset($_REQUEST['alterar'])){
    $os = $_REQUEST['alterar'];
}

if(isset($_REQUEST['liberar'])){
    $os = $_REQUEST['liberar'];
}

if(isset($_REQUEST['os'])){
    $os = $_REQUEST['os'];
}

if(isset($_REQUEST["laudo_tecnico_os"])){
    $laudo_tecnico_os = $_REQUEST["laudo_tecnico_os"];
}

function verifica_correcao($os) {
    global $con, $login_fabrica;

    $sqlCorrecao = "SELECT os_status 
                FROM tbl_os_status 
                WHERE status_os NOT IN (193,194)
                AND observacao ILIKE 'LAUDO DEVOLVIDO%'
                AND os = {$os}
                LIMIT 1
                    ";
    $resCorrecao = pg_query($con, $sqlCorrecao);

    return (pg_num_rows($resCorrecao) > 0) ? true : false;
}

if(strlen($laudo_tecnico_os) > 0){

    $join_laudo_tecnico = " INNER JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os ";
    $cond_laudo_tecnico = " AND tbl_laudo_tecnico_os.laudo_tecnico_os = {$laudo_tecnico_os} ";

}

$laudo  = $_REQUEST['laudo'];

//if(strlen($msg_erro) == 0 OR $laudo != "fats"){
    $sql = "SELECT  tbl_posto.nome AS posto_nome,
                    tbl_posto_fabrica.codigo_posto AS posto_codigo,
                    tbl_posto.fone AS posto_fone,
                    tbl_posto.fax AS posto_fax,
                    tbl_posto.cnpj AS posto_cnpj,
                    tbl_posto.ie AS posto_ie,
                    tbl_posto.email AS posto_email,
                    tbl_os.consumidor_nome,
                    tbl_os.consumidor_endereco,
                    tbl_os.consumidor_numero,
                    tbl_os.consumidor_complemento,
                    tbl_os.consumidor_cidade,
                    tbl_os.consumidor_bairro,
                    tbl_os.consumidor_estado,
                    tbl_os.consumidor_cep,
                    tbl_os.consumidor_fone,
                    tbl_os.consumidor_email,
                    tbl_os.nota_fiscal,
                    tbl_os.data_digitacao::DATE,
                    tbl_os.data_conserto::DATE,
                    tbl_os.data_abertura,
                    tbl_os.data_fechamento,
                    tbl_os.defeito_reclamado_descricao,
                    tbl_os.sua_os,
                    (tbl_os.data_fechamento - tbl_os.data_abertura) AS dias_fechamento,
                    tbl_produto.referencia || ' - ' || tbl_produto.descricao AS equipamento_modelo,
                    tbl_produto.voltagem AS equipamento_tensao,
                    tbl_os.serie AS equipamento_serie,
                    tbl_os.data_nf AS equipamento_data_venda    ,
                    tbl_os_produto.serie AS equipamento_serie   ,
                    tbl_causa_troca.descricao AS causa_troca,
                    tbl_causa_troca.causa_troca as id_causa_troca,
                    tbl_os_troca.observacao
            FROM    tbl_os
            JOIN    tbl_os_troca        ON  tbl_os_troca.os             = tbl_os.os
            JOIN    tbl_causa_troca     ON  tbl_causa_troca.causa_troca = tbl_os_troca.causa_troca
            JOIN    tbl_produto         ON  tbl_produto.produto         = tbl_os.produto 
            {$join_laudo_tecnico}
            JOIN    tbl_posto           USING(posto)
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                        AND tbl_posto_fabrica.fabrica   = $login_fabrica
            LEFT JOIN    tbl_os_produto      ON  tbl_os_produto.os           = tbl_os.os
            WHERE   tbl_os.fabrica  = $login_fabrica
            AND     tbl_os.os       = $os 
            {$cond_laudo_tecnico}
    ";
    $res = pg_query($con,$sql);

    $sua_os                         = pg_fetch_result($res, 0, "sua_os");
    $posto_nome                     = pg_fetch_result($res, 0, "posto_nome");
    $posto_codigo                   = pg_fetch_result($res, 0, "posto_codigo");
    $posto_fone                     = pg_fetch_result($res, 0, "posto_fone");
    $posto_fax                      = pg_fetch_result($res, 0, "posto_fax");
    $posto_cnpj                     = pg_fetch_result($res, 0, "posto_cnpj");
    $posto_ie                       = pg_fetch_result($res, 0, "posto_ie");
    $posto_email                    = pg_fetch_result($res, 0, "posto_email");
    $cliente_nome                   = pg_fetch_result($res, 0, "consumidor_nome");
    $cliente_endereco               = pg_fetch_result($res, 0, "consumidor_endereco");
    $cliente_numero                 = pg_fetch_result($res, 0, "consumidor_numero");
    $cliente_complemento            = pg_fetch_result($res, 0, "consumidor_complemento");
    $cliente_cidade                 = pg_fetch_result($res, 0, "consumidor_cidade");
    $cliente_bairro                 = pg_fetch_result($res, 0, "consumidor_bairro");
    $cliente_estado                 = pg_fetch_result($res, 0, "consumidor_estado");
    $cliente_cep                    = pg_fetch_result($res, 0, "consumidor_cep");
    $cliente_fone                   = pg_fetch_result($res, 0, "consumidor_fone");
    $cliente_email                  = pg_fetch_result($res, 0, "consumidor_email");
    $equipamento_nota_fiscal        = pg_fetch_result($res, 0, "nota_fiscal");
    $equipamento_modelo             = pg_fetch_result($res, 0, "equipamento_modelo");
    $equipamento_tensao             = pg_fetch_result($res, 0, "equipamento_tensao");
    $equipamento_serie              = pg_fetch_result($res, 0, "equipamento_serie");
    $equipamento_data_venda         = pg_fetch_result($res, 0, "equipamento_data_venda");
    $equipamento_serie              = pg_fetch_result($res, 0, "equipamento_serie");
    $data_digitacao                 = pg_fetch_result($res, 0, "data_digitacao");
    $data_conserto                  = pg_fetch_result($res, 0, "data_conserto");
    $data_abertura                  = pg_fetch_result($res, 0, "data_abertura");
    $data_fechamento                = pg_fetch_result($res, 0, "data_fechamento");
    $dias_fechamento                = pg_fetch_result($res, 0, "dias_fechamento");
    $observacao                     = pg_fetch_result($res, 0, "observacao");
    $defeito_reclamado_descricao    = pg_fetch_result($res, 0, "defeito_reclamado_descricao");
    $causa_troca                    = pg_fetch_result($res, 0, "causa_troca");
    $id_causa_troca                 = pg_fetch_result($res, 0, 'id_causa_troca');

    list($a, $m, $d)        = explode("-", $equipamento_data_venda);
    list($dda, $ddm, $ddd)  = explode("-", $data_digitacao);
    list($dca, $dcm, $dcd)  = explode("-", $data_conserto);
    list($daa, $dam, $dad)  = explode("-", $data_abertura);
    list($dfa, $dfm, $dfd)  = explode("-", $data_fechamento);

    if(strlen(trim($data_conserto))>0){
        $data_conserto = $dcd."/".$dcm."/".$dca;
    }

    if(strlen(trim($data_digitacao))>0){
        $data_digitacao = $ddd."/".$ddm."/".$dda;
    }

    if(strlen(trim($data_abertura))>0){
        $data_abertura = $dad."/".$dam."/".$daa;
    }

    if(strlen(trim($data_fechamento))>0){
        $data_fechamento = $dfd."/".$dfm."/".$dfa;
    }

    $sql = "SELECT  DISTINCT
                    tbl_defeito_constatado.descricao
            FROM    tbl_os_defeito_reclamado_constatado
            JOIN    tbl_defeito_constatado USING(defeito_constatado)
            WHERE   os = $os
    ";

    $res = pg_query ($con,$sql);

    $array_integridade = array();

    if(pg_num_rows($res)>0){
        for ($i=0;$i<pg_num_rows($res);$i++){
            $aux_defeito_constatado = pg_fetch_result($res,$i,descricao);
            array_push($array_integridade,$aux_defeito_constatado);
        }

        $lista_defeitos = implode($array_integridade,", ");
    }


    $checklist = array(
        "empresa" => array(
            "nome"      => utf8_encode($posto_nome),
            "codigo"    => $posto_codigo,
            "fone"      => $posto_fone,
            "fax"       => $posto_fax,
            "cnpj"      => $posto_cnpj,
            "ie"        => $posto_ie,
            "email"     => $posto_email,
        ),
        "cliente" => array(
            "nome"      => utf8_encode($cliente_nome),
            "endereco"  => utf8_encode($cliente_endereco." ".$cliente_numero." ".$cliente_com),
            "cidade"    => utf8_encode($cliente_cidade),
            "bairro"    => utf8_encode($cliente_bairro),
            "estado"    => $cliente_estado,
            "cep"       => $cliente_cep,
            "fone"      => $cliente_fone,
            "email"     => $cliente_email,
        ),
        "equipamento" => array(
            "modelo"        => utf8_encode($equipamento_modelo),
            "tensao"        => $equipamento_tensao,
            "serie"         => $equipamento_serie,
            "nota_fiscal"   => $equipamento_nota_fiscal,
            "data_venda"    => "$d/$m/$a",
            "serie"         => $equipamento_serie,
            "causa_troca"   => utf8_encode($causa_troca),
        ),
        "os" => array(
            "os"                            => $sua_os,
            "data_digitacao"                => "$data_digitacao",
            "data_abertura"                 => "$data_abertura",
            "data_conserto"                 => "$data_conserto",
            "data_fechamento"               => "$data_fechamento",
            "defeito_reclamado_descricao"   => utf8_encode($defeito_reclamado_descricao),
            "defeito_constatado"            => utf8_encode($lista_defeitos),
            "dias_fechamento"               => $dias_fechamento,
            "observacao"                    => utf8_encode($observacao)
        ),
    );

    if(strlen($_GET["laudo_tecnico_os"]) == 0){
        $cond_afirmativa = " AND afirmativa = true ";
    }

    $sqlLaudo = "SELECT  laudo_tecnico_os,
                    observacao
            FROM    tbl_laudo_tecnico_os
            WHERE   os = $os 
            {$cond_afirmativa} 
            {$cond_laudo_tecnico}
    ";
    $res = pg_query($con,$sqlLaudo);
    $json = pg_fetch_result($res, 0, observacao);
    $checklistImprime = json_decode($json, true);
	unset($checklistImprime['os']['observacao']);

	if(is_array($checklistImprime)){

		$checklist = array_merge($checklist,$checklistImprime);
        if(empty($checklist['os']['observacao'])) $checklist['os']['observacao'] = utf8_encode($observacao);

	}

	if(empty($checklist['cliente']['endereco'])) {
		$checklist['cliente']['endereco'] = $cliente_endereco." ".$cliente_numero." ".$cliente_com;
	}
	if(empty($checklist['cliente']['bairro'])) {
		$checklist['cliente']['bairro'] = $cliente_bairro;
	}
//}

if($_POST['gravar'] || $_POST['btn_liberar']){
    $os                                    = filter_input(INPUT_POST,'os');
    $checklist['laudo']                    = filter_input(INPUT_POST,'laudo');
    $checklist['troca']['cor']             = filter_input(INPUT_POST,'troca_cor');
    $checklist['troca']['motivo']          = filter_input(INPUT_POST,'troca_motivo');
    $checklist['troca']['produto_troca']   = filter_input(INPUT_POST,'produto_troca');
    $checklist['sac']['backoffice']        = filter_input(INPUT_POST,'sac_backoffice');
    $checklist['sac']['protocolo']         = filter_input(INPUT_POST,'sac_protocolo');
    $checklist["pergunta"]["mangueira"]    = filter_input(INPUT_POST,'pergunta_mangueira');
    $checklist["pergunta"]["inmetro"]      = filter_input(INPUT_POST,'pergunta_inmetro');
    $checklist["pergunta"]["prazo"]        = filter_input(INPUT_POST,'pergunta_prazo');
    $checklist["pergunta"]["regulador"]    = filter_input(INPUT_POST,'pergunta_regulador');
    $checklist["pergunta"]["fogao"]        = filter_input(INPUT_POST,'pergunta_fogao');
    $checklist["pergunta"]["abracadeira"]  = filter_input(INPUT_POST,'pergunta_abracadeira');
    $checklist["pergunta"]["plastico"]     = filter_input(INPUT_POST,'pergunta_plastico');
    $checklist["pergunta"]["materiais"]    = filter_input(INPUT_POST,'pergunta_materiais');
    $checklist["pergunta"]["pessoais"]     = filter_input(INPUT_POST,'pergunta_pessoais');
    $checklist["pergunta"]["sinistro"]     = filter_input(INPUT_POST,'pergunta_sinistro');
    $checklist["foto"]["frente"]           = filter_input(INPUT_POST,'foto_frente');
    $checklist["foto"]["lada"]             = filter_input(INPUT_POST,'foto_lada');
    $checklist["foto"]["traseira"]         = filter_input(INPUT_POST,'foto_traseira');
    $checklist["foto"]["laea"]             = filter_input(INPUT_POST,'foto_laea');
    $checklist["foto"]["ladp"]             = filter_input(INPUT_POST,'foto_ladp');
    $checklist["foto"]["teto"]             = filter_input(INPUT_POST,'foto_teto');
    $checklist["foto"]["laep"]             = filter_input(INPUT_POST,'foto_laep');
    $checklist["foto"]["chao"]             = filter_input(INPUT_POST,'foto_chao');
    $checklist["foto"]["visp"]             = filter_input(INPUT_POST,'foto_visp');
    $checklist["foto"]["pessoa"]           = filter_input(INPUT_POST,'foto_pessoa');
    $checklist["foto"]["viip"]             = filter_input(INPUT_POST,'foto_viip');
    $checklist["foto"]["danos"]            = filter_input(INPUT_POST,'foto_danos');
    $checklist["negociacao"]["troca"]      = filter_input(INPUT_POST,'negociacao_troca');
    $checklist['negociacao']['valor']      = filter_input(INPUT_POST,'negociacao_valor');
    $checklist['banco']['titular']         = filter_input(INPUT_POST,'banco_titular');
    $checklist['banco']['teletitular']     = filter_input(INPUT_POST,'banco_teletitular',FILTER_SANITIZE_NUMBER_INT);
    $checklist['banco']['banco']           = filter_input(INPUT_POST,'banco_banco');
    $checklist['banco']['agencia']         = filter_input(INPUT_POST,'banco_agencia');
    $checklist["banco"]["tipo"]            = filter_input(INPUT_POST,'banco_tipo');
    $checklist['banco']['conta']           = filter_input(INPUT_POST,'banco_conta');
    $checklist['banco']['documento']       = filter_input(INPUT_POST,'banco_documento',FILTER_SANITIZE_NUMBER_INT);
    $checklist['banco']['rg']              = filter_input(INPUT_POST,'banco_rg',FILTER_SANITIZE_NUMBER_INT);
    $checklist['banco']['data_nascimento'] = filter_input(INPUT_POST,'banco_data_nascimento');
    
    $checklist["foto"]["etiqueta"]         = filter_input(INPUT_POST,'foto_etiqueta');
    $checklist["foto"]["nf"]               = filter_input(INPUT_POST,'foto_nf');
    
    $checklist["foto"]["aceite1"]          = filter_input(INPUT_POST,'foto_aceite1');
    $checklist["foto"]["aceite2"]          = filter_input(INPUT_POST,'foto_aceite2');
    $checklist["foto"]["rg"]               = filter_input(INPUT_POST,'foto_rg');
    $checklist["foto"]["cpf"]              = filter_input(INPUT_POST,'foto_cpf');
    $checklist["foto"]["residencia"]       = filter_input(INPUT_POST,'foto_residencia');
    
    $checklist["foto"]["produto1"]         = filter_input(INPUT_POST,'foto_produto1');
    $checklist["foto"]["produto2"]         = filter_input(INPUT_POST,'foto_produto2');
    $checklist["foto"]["produto3"]         = filter_input(INPUT_POST,'foto_produto3');

    if (!empty($_POST['causa_troca'])) {

        $sqlDescCausa = "SELECT tbl_causa_troca.descricao
                         FROM tbl_causa_troca
                         WHERE tbl_causa_troca.causa_troca = {$_POST['causa_troca']}
                         AND tbl_causa_troca.fabrica = {$login_fabrica}";
        $resDescCausa = pg_query($con, $sqlDescCausa);

        if (pg_num_rows($resDescCausa) > 0) {
            $checklist['equipamento']['causa_troca'] = utf8_encode(pg_fetch_result($resDescCausa, 0, 'descricao'));
        } else {
            $msg_erro .= "Preencha a causa da troca <br />";
        }

    }

    $checklist['os']['observacao']         = $_POST['observacao_troca'];
    
    $checklist["motivo"][]                 = filter_input(INPUT_POST,'motivo');

    $erro = "";
    $laudo = $checklist['laudo'];

    switch($laudo){
        case "fat":
            $title = "FORMUL&Aacute;RIO DE AN&Aacute;LISE DE TROCA - FAT";
            $amazonTC = new TDocs($con, $login_fabrica,"os");
            $types = array("png", "jpg", "jpeg","pdf");
            foreach ($_FILES as $key => $imagem) {
                if ($key != "anexo_img"){

                    if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
                        $compara_check = explode("_",$key);

                        if ($checklist['foto'][$compara_check[1]] != "") {
                            $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                            if (!in_array($type, $types)) {

                                $msg_erro .= "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg, pdf<br />";
                                break;

                            } else {

                                $imagem['name'] = (strlen($laudo_tecnico_os) > 0) ? $laudo_tecnico_os."_".$key."_".$laudo_tecnico_os.".".$type : "laudo_anexo_".$os."_{$login_fabrica}_{$key}.$type";
                                $amazonTC->uploadFileS3($imagem, $os, false);

                                $checklist['foto'][$key]    = $imagem['name'];

                            }
                        } else {
                            $msg_erro .= "Deve-se marcar o check com a imagem da posi&ccedil;ao a subir<br />";
                            break;
                        }
                    }
                } else {
                    if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
                        $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                        if (!in_array($type, $types)) {
                            $msg_erro .= "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg, pdf<br />";
                            break;
                        } else {

                            $imagem['name'] = (strlen($laudo_tecnico_os) > 0) ? $laudo_tecnico_os."_".$key."_".$laudo_tecnico_os.".".$type : "laudo_anexo_".$os."_{$login_fabrica}_{$key}.$type";
                            $amazonTC->uploadFileS3($imagem, $os);
                            $checklist['anexo_img']     = $imagem['name'];

                        }
                    }
                }
            }

            $sqlCompara = "
                SELECT  DISTINCT
                        tbl_os_produto.produto
                FROM    tbl_os
                JOIN    tbl_os_produto USING(os)
                WHERE   os = $os
            ";
            $resCompara = pg_query($con,$sqlCompara);
            $produto_os = pg_fetch_result($resCompara,0,produto);
            if(($produto_os != $checklist['troca']['produto_troca']) && ($checklist['troca']['motivo'] == "")){
                $msg_erro = "Relate o motivo da troca por um produto diferente";
                break;
            }
        break;

        case "fatrev":
            if ($login_fabrica == 30) {
                if (strlen($_POST["contato_responsavel"]) == 0 || strlen($_POST["fone_revenda"]) == 0 || (strlen($_POST["forma_negociacao_revenda"]) == 0 )) {
                    $msg_erro .= "Favor preencher todos os dados da revenda";
                    break;
                } else {
                    $dados_revenda = array();

                    $aux_sql = "SELECT revenda_nome, revenda_cnpj FROM tbl_os WHERE os = $os";
                    $aux_res = pg_query($con, $aux_sql);

                    $dados_revenda["revenda_nome"] = utf8_encode(pg_fetch_result($aux_res, 0, 'revenda_nome'));
                    $dados_revenda["revenda_cnpj"] = utf8_encode(pg_fetch_result($aux_res, 0, 'revenda_cnpj'));
                    $dados_revenda["contato_responsavel"] = utf8_encode($_POST["contato_responsavel"]);
                    $dados_revenda["fone_revenda"]        = utf8_encode($_POST["fone_revenda"]);

                    $dados_revenda["negociacao_revenda"] = $_POST['forma_negociacao_revenda'];

                    $checklist["dados_revenda"] = $dados_revenda;
                }
            }

            $title = "FORMUL&Aacute;RIO DE AN&Aacute;LISE DE TROCA REVENDA - FATR";
            $amazonTC = new TDocs($con, $login_fabrica,"os");
            $types = array("png", "jpg", "jpeg","pdf");

            foreach ($_FILES as $key => $imagem) {
                if ($key != "anexo_img" || ($key == "anexo_img" && $login_fabrica == 30)){

                    if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
                        $compara_check = explode("_",$key);

                        if ($checklist['foto'][$compara_check[1]] != "" || $key == "anexo_img") {
                            $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                            if (!in_array($type, $types)) {

                                $msg_erro .= "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg, pdf<br />";
                                break;

                            } else {

                                $imagem['name'] = (strlen($laudo_tecnico_os) > 0) ? $laudo_tecnico_os."_".$key."_".$laudo_tecnico_os.".".$type : "laudo_anexo_".$os."_{$login_fabrica}_{$key}.$type";
                                $amazonTC->uploadFileS3($imagem, $os, false);

                                $checklist['foto'][$key]    = $imagem['name'];

                            }
                        } else {
                            $msg_erro .= "Deve-se marcar o check com a imagem da posi&ccedil;ao a subir<br />";
                            break;
                        }
                    }
                } else {
                    if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
                        $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                        if (!in_array($type, $types)) {
                            $msg_erro .= "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg, pdf<br />";
                            break;
                        } else {

                            $imagem['name'] = (strlen($laudo_tecnico_os) > 0) ? $laudo_tecnico_os."_".$key."_".$laudo_tecnico_os.".".$type : "laudo_anexo_".$os."_{$login_fabrica}_{$key}.$type";
                            $amazonTC->uploadFileS3($imagem, $os);
                            $checklist['anexo_img']     = $imagem['name'];

                        }
                    }
                }
            }

            /*$sqlCompara = "
                SELECT  DISTINCT
                        tbl_os_produto.produto
                FROM    tbl_os
                JOIN    tbl_os_produto USING(os)
                WHERE   os = $os
            ";
            $resCompara = pg_query($con,$sqlCompara);
            $produto_os = pg_fetch_result($resCompara,0,produto);
            if(($produto_os != $checklist['troca']['produto_troca']) && ($checklist['troca']['motivo'] == "")){
                $msg_erro = "Relate o motivo da troca por um produto diferente";
                break;
            }*/
        break;

        case "fats":
            $title = "FORMUL&Aacute;RIO DE AN&Aacute;LISE DE TROCA - FAT SINISTRO";
            $amazonTC = new TDocs($con, $login_fabrica,"laudo_tecnico");
            $types = array("png", "jpg", "jpeg");

            foreach ($_FILES as $key => $imagem) {
                if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
                    $compara_check = explode("_",$key);

                    if ($checklist['foto'][$compara_check[1]] != "") {
                        $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));

                        if (!in_array($type, $types)) {

                            $msg_erro .= "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg<br />";
                            break;

                        } else {

                            $imagem['name'] = (strlen($laudo_tecnico_os) > 0) ? $laudo_tecnico_os."_".$key."_".$laudo_tecnico_os.".".$type : "laudo_anexo_".$os."_{$login_fabrica}_{$key}.$type";
                            $amazonTC->uploadFileS3($imagem, $os, false);

                            $checklist['foto'][$key] = $imagem['name'];

                        }
                    } else {
                        $msg_erro .= "Deve-se marcar o check com a imagem da posi&ccedil;ao a subir<br />";
                        break;
                    }
                }
            }

            foreach($checklist['pergunta'] as $pergunta){
                if(strlen($pergunta) == 0){
                    $msg_erro .= "Deve-se responder a todas as perguntas!!<br />";
                    break;
                }
            }

            $validaNegociacao = 0;

            foreach($checklist['negociacao'] as $negociacao){

                if(strlen($negociacao) > 0){
                    $validaNegociacao = 1;
                    break;
                }
            }

            if($validaNegociacao == 0){
                $msg_erro .= "Marcar o tipo da proposta de negocia&ccedil;&atilde;o!!<br />";
            }else{
                if(in_array($checklist["negociacao"]["troca"],array("restituicao","danos")) && $checklist["negociacao"]["valor"] == ""){
                    $msg_erro .= "Colocar o valor do ressarcimento / danos morais dado ao consumidor";
                }
            }
        break;

        case "far":
            $title = "FORMUL&Aacute;RIO DE AN&Aacute;LISE DE RESTITUI&Ccedil;&Atilde;O - FAR";
            $amazonTC = new TDocs($con, $login_fabrica,"os");
            $types = array("png", "jpg", "jpeg","pdf");

            foreach ($_FILES as $key => $imagem) {
                if ($key != "anexo_img"){

                    if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
                        $compara_check = explode("_",$key);
                        if ($checklist['foto'][$compara_check[1]] != "") {
                            $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));

                            if (!in_array($type, $types)) {

                                $msg_erro .= "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg, pdf<br />";
                                break;

                            } else {

                                $imagem['name'] = (strlen($laudo_tecnico_os) > 0) ? $laudo_tecnico_os."_".$key."_".$laudo_tecnico_os.".".$type : "laudo_anexo_".$os."_{$login_fabrica}_{$key}.$type";

                                $amazonTC->uploadFileS3($imagem, $os, false);

                                $checklist['foto'][$key]    = $imagem['name'];

                            }
                        } else {
                            $msg_erro .= "Deve-se marcar o check com a imagem da posi&ccedil;ao a subir<br />";
                            break;
                        }
                    }
                } else {
                    if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
                        $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                        if (!in_array($type, $types)) {
                            $msg_erro .= "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg, pdf<br />";
                            break;
                        } else {

                            $imagem['name'] = (strlen($laudo_tecnico_os) > 0) ? $laudo_tecnico_os."_".$key."_".$laudo_tecnico_os.".".$type : "laudo_anexo_".$os."_{$login_fabrica}_{$key}.$type";
                            $amazonTC->uploadFileS3($imagem, $os);
                            $checklist['anexo_img']     = $imagem['name'];

                        }
                    }
                }
            }

            $validaRestituicao = 0;
            foreach($checklist['banco'] as $banco){
                if(strlen($banco) == 0){
                    $validaRestituicao = 1;
                    break;
                }
            }

            if($validaRestituicao == 1){
                $msg_erro .= "Todos os dados banc&aacute;rios s&atilde;o obrigat&oacute;rios";
            }
        break;
    }
//     echo "<pre>";
//     print_r($checklist);
//     echo "</pre>";
    $checklist = array_map_recursive(function ($r) {
        $result = explode("@", $r);
        if (count($result) > 1) {
            $new = explode("<A0>", utf8_encode($result[0]));
            return $new[0] . "@" . $result[1];
        } else {
            return $r;
        }
    }, $checklist);
    
    $json = json_encode($checklist);
    $json = pg_escape_string($con, $json);

    if(strlen($msg_erro) == 0){
		$res = pg_query($con,"BEGIN TRANSACTION");

        /* DELETE  FROM tbl_laudo_tecnico_os
            WHERE   os = $os; */

        $title = utf8_encode($title);

        if(strlen($laudo_tecnico_os) > 0){

            $sql = "UPDATE tbl_laudo_tecnico_os SET 
                        observacao = '{$json}', 
                        titulo = '{$title}' 
                    WHERE 
                        os = {$os} 
                        {$cond_laudo_tecnico}
                    ";
        
        }else{
			$sql = "SELECT os FROM tbl_laudo_tecnico_os WHERE os = $os ";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) == 0) {
				$sql = "
					INSERT INTO tbl_laudo_tecnico_os (
						os,
						observacao,
						fabrica,
						titulo
					) VALUES (
						$os,
						'$json',
						$login_fabrica,
						'".utf8_encode($title)."'
					);";
			}
        }

		$res = pg_query($con,$sql);

		$msg_erro = pg_last_error($con);


		if($_POST['gravar']){

            $observacao_troca = utf8_decode($_POST['observacao_troca']);
            $id_causa_troca   = $_POST['causa_troca'];

            if (empty($id_causa_troca)) {
                $msg_erro = "Preencha a causa da troca";
            }

			if (strlen($msg_erro) == 0) {
				$sql2 = "
					DELETE  FROM
					tbl_os_status
					WHERE   os = $os
					AND     status_os IN(192,193,194)
                    AND     observacao NOT ILIKE 'LAUDO DEVOLVIDO%';

                    UPDATE tbl_os_status
                    SET observacao = '(Corrigido) ' || observacao
                    WHERE os = {$os}
                    AND observacao ILIKE 'LAUDO DEVOLVIDO%';

				INSERT INTO tbl_os_status(
					os,
					status_os,
					observacao,
					admin,
					status_os_troca,
					fabrica_status
				) VALUES (
					$os,
					192,
					'AUDITORIA DE ".utf8_encode($title)."',
					$login_admin,
					TRUE,
					$login_fabrica
				);
				";

				$res2 = pg_query($con,$sql2);
				$msg_erro = pg_last_error($con);
				//echo nl2br($sql2);

                $sqlAtualizaTroca = "
                    UPDATE tbl_os_troca
                    SET    observacao  = '{$observacao_troca}',
                           causa_troca = {$id_causa_troca}
                    WHERE  os = {$os}
                ";
                $resAtualizaTroca = pg_query($con, $sqlAtualizaTroca);



			}else{
				$msg_erro = pg_last_error($con);
			}
			if (strlen($msg_erro) > 0) {
				$res = pg_query($con,"ROLLBACK TRANSACTION");
				$erro = "Ocorreu um erro ao gravar o checklist! - ";
			} else {

				$mailer = new PHPMailer;

				$sqlMail = "
					SELECT  email,
					nome_completo
					FROM    tbl_admin
					WHERE   aprova_laudo IS TRUE
					AND		ativo
					AND     fabrica     = $login_fabrica
					";
				$resMail = pg_query($con,$sqlMail);
				$conta = pg_numrows($resMail);

				$mailer->isSMTP();
				$mailer->IsHTML();

				$mailer->From = "no-reply@telecontrol.com.br";
				$mailer->FromName = "Posvenda Telecontrol";
				for($i=0;$i<$conta;$i++){
					$usuario = pg_fetch_result($resMail,$i,nome_completo);
					$email   = pg_fetch_result($resMail,$i,email);
					$mailer->addAddress($email,$usuario);
				}

				$mailer->Subject = "OS $os com Formulário de Laudo para APROVAÇÃO";
				$msg = "Prezado(s),";
				$msg .= "<br /><br />Favor, acessar o sistema para Aprovar / Reprovar o laudo cadastrado";
				$msg .= "<br />para a OS $os ";
				$mailer->Body = $msg;
				$mailer->Send();
				$res = pg_query($con,"COMMIT TRANSACTION");
				header("Location: os_press.php?os=$os");
			}
		}else if($_POST['btn_liberar']){
			if($checklist['sac']['backoffice'] == "" || $checklist['sac']['protocolo'] == ""){
				$msg_erro .= "<br>Para liberar o laudo, necessita do cadastro do backoffice e protocolo.";
			}else{
				if(($laudo == 'fat') || ($laudo == 'fats' && $_POST['negociacao_troca'])){
					$sqlUp = "
						UPDATE  tbl_os_troca
						SET     gerar_pedido = TRUE,
						admin_autoriza = $login_admin
						WHERE   os = $os
						";
                    $frase = "Ordem de Servi&ccedil;o liberada para troca";
				}else{
                    $valorIns = $checklist['negociacao']['valor'];
                    
                    if(strlen($valorIns) > 0){
    					$valorIns = str_replace(".","",$valorIns);
    					$valorIns = str_replace(",",".",$valorIns);
    					$cpf = preg_replace('/\D/','',$checklist['banco']['documento']);
                    }else{
                        $valorIns = 0;
                    }

                    $inf_banco = "";
                    $val_banco = "";

                    if ($login_fabrica == 30) {
                        if (strlen($checklist['banco']['banco']) > 0) {
                            $inf_banco = " banco, agencia, conta, ";
                            $val_banco = $checklist['banco']['banco'] .", substr('" . $checklist['banco']['agencia'] . "',1,10), '" .$checklist['banco']['conta'] . "',"; 
                        }
                    } else {
                        $inf_banco = " banco, agencia, conta, ";
                        $val_banco = $checklist['banco']['banco'] .", substr('" . $checklist['banco']['agencia'] . "',1,10), '" .$checklist['banco']['conta'] . "',"; 
                    }

					$sqlUp = "
						UPDATE  tbl_os_troca
						SET     ressarcimento = TRUE,
						admin_autoriza = $login_admin
						WHERE   os = $os;

                        INSERT INTO tbl_ressarcimento (
                            fabrica,
                            os,
                            nome,
                            cpf,
                            $inf_banco
                            valor_original,
                            valor_alterado,
                            admin,
                            tipo_conta
                        ) VALUES (
                            $login_fabrica,
                            $os,
                            substr('".str_replace("'", "", $checklist['banco']['titular'])."',1,50),
                            substr('$cpf', 1, 14),
                            $val_banco
                            $valorIns,
                            0.00,
                            $login_admin,
                            '".$checklist['banco']['tipo']."'
                        );
					";
					$frase = "Ordem de Servi&ccedil;o liberada para ressarcimento";
                }
				// echo nl2br($sqlUp); exit;
				$resUp = pg_query($con,$sqlUp);
				$msg_erro = pg_last_error($con);

                /*if ($login_fabrica == 30) {
                    $sql = "UPDATE tbl_os SET valores_adicionais = 40, status_checkpoint = 31 WHERE os = {$os} AND fabrica = {$login_fabrica}";
                    $res = pg_query($con, $sql);

                    $sql = "SELECT tbl_os_campo_extra.campos_adicionais
                            FROM tbl_os_campo_extra
                            WHERE os = $os
                            AND fabrica = $login_fabrica";
                    $res = pg_query($con,$sql);
                    if (pg_num_rows($res) > 0) {
                      $res_adicionais    = pg_result($res,0,campos_adicionais);
                      $campos_adicionais = json_decode($res_adicionais, true);
                      unset($campos_adicionais['taxa_entrega']);
                      $campos_adicionais['taxa_entrega'] = 40;
                      $campos_adicionais = json_encode($campos_adicionais);

                        $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais'
                                WHERE os = $os
                                AND fabrica = $login_fabrica";
                    }else{
                        $campos_adicionais = array(
                            'taxa_entrega' => 40
                        );
                        $campos_adicionais = json_encode($campos_adicionais);
                        $sql = "INSERT INTO tbl_os_campo_extra (fabrica, os, campos_adicionais) values ($login_fabrica, $os, '$campos_adicionais')";
                    }
                    pg_query($con, $sql);
                }*/

				$sql2 = "
					INSERT INTO tbl_os_status(
						os,
						status_os,
						observacao,
						admin,
						status_os_troca,
						fabrica_status
					) VALUES (
						$os,
						193,
						'$frase',
						$login_admin,
						TRUE,
						$login_fabrica
					);
				";
				$res2 = pg_query($con,$sql2);
				$msg_erro = pg_last_error($con);


				//hd_chamado=3063684
				/*$sql_up = "UPDATE tbl_os set
								data_fechamento = CURRENT_TIMESTAMP,
								data_conserto = CURRENT_TIMESTAMP,
								finalizada = CURRENT_TIMESTAMP,
								defeito_constatado = case when defeito_constatado isnull then 11792 else defeito_constatado end,
								solucao_os = case when solucao_os isnull then 243 else solucao_os end
							WHERE os = $os and finalizada isnull";
				$res_up = pg_query($con, $sql_up);

				$sql_upd = "UPDATE tbl_os_extra SET obs_fechamento = '$login_login' WHERE os = $os ;";
				$res_upd = pg_query($con,$sql_upd);*/
				// fim - hd_chamado=3063684

				if (strlen($msg_erro) > 0) {
					$res = pg_query($con,"ROLLBACK TRANSACTION");
					$erro = "Ocorreu um erro ao gravar o checklist!".pg_last_error($con). " - ".$msg_erro.'<br/>';
				} else {
					$res = pg_query($con,"COMMIT TRANSACTION");
					header("Location: os_press.php?os=$os");
				}
			}
		}
    }
}

if($_POST['btn_aprovar']){
    $res = pg_query($con,"BEGIN TRANSACTION");

    $fraseEmail = "APROVADO(S)";
    $sqlUp = "
    UPDATE  tbl_os_troca
    SET     status_os = 193
    WHERE   os = $os
    ";
    $resUp = pg_query($con,$sqlUp);
    $msg_erro = pg_last_error($con);

    $sql = "UPDATE tbl_laudo_tecnico_os SET afirmativa = true, data = CURRENT_TIMESTAMP WHERE os = {$os} and afirmativa is not false";
    $res = pg_query($con, $sql);

    $sqlIns = "
    INSERT INTO tbl_os_status (
        os        ,
        status_os ,
        observacao,
        admin,
        status_os_troca
    ) VALUES (
        $os,
        193,
        'LAUDO APROVADO.',
        $login_admin,
        't'
    );
    ";
    $resIns = pg_query($con,$sqlIns);

    $sqlLaudo = "UPDATE tbl_laudo_tecnico_os SET AFIRMATIVA = true WHERE os = {$os}";
    $resLaudo = pg_query($con,$sqlLaudo);

    if ($login_fabrica == 30) {
        $sql = "UPDATE tbl_os SET valores_adicionais = '65', status_checkpoint = 31 WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        $sql = "SELECT tbl_os_campo_extra.campos_adicionais
                FROM tbl_os_campo_extra
                WHERE os = $os
                AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
          $res_adicionais    = pg_result($res,0,campos_adicionais);
          $campos_adicionais = json_decode($res_adicionais, true);
		  if(isset($campos_adicionais['taxa_entrega'])) {
			  unset($campos_adicionais['taxa_entrega']);
		  }
          $campos_adicionais['taxa_entrega'] = 40;
          $campos_adicionais['avaliacao'] = 25;
          $campos_adicionais = json_encode($campos_adicionais);

            $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais'
                    WHERE os = $os
                    AND fabrica = $login_fabrica";
        }else{
            $campos_adicionais = array(
                'taxa_entrega' => 40,
                'avaliacao' => 25,
            );
            $campos_adicionais = json_encode($campos_adicionais);
            $sql = "INSERT INTO tbl_os_campo_extra (fabrica, os, campos_adicionais) values ($login_fabrica, $os, '$campos_adicionais')";
        }
        $res = pg_query($con, $sql);
    }

    if(pg_last_error($con)){
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        $erro = "Ocorreu um erro ao aprovar o laudo";
    }else{
        $res = pg_query($con,"COMMIT TRANSACTION");
        header("Location: os_press.php?os=$os");
    }
}
if($_POST['btn_reprovar']){

    $motivo = $_POST['motivo'];

    if($motivo != ""){
        $res = pg_query($con,"BEGIN TRANSACTION");

        if ($_POST['correcao']) {
            $afirmativa = "null";
            $obsStatus  = "LAUDO DEVOLVIDO p/ CORREÇÃO. MOTIVO: ".utf8_decode($motivo);
            $statusOs   = 192;
        } else {
            $afirmativa = "false";
            $obsStatus = "LAUDO RECUSADO. MOTIVO: ".utf8_decode($motivo);
            $statusOs   = 194;
        }

        $sqlUp = "
            UPDATE  tbl_os_troca
            SET     status_os = {$statusOs}
            WHERE   os = {$os}
        ";
        $resUp = pg_query($con,$sqlUp);
        $msg_erro = pg_last_error($con);

        $sqlIns = "
        INSERT INTO tbl_os_status (
            os        ,
            status_os ,
            observacao,
            admin,
            status_os_troca
        ) VALUES (
            {$os},
            {$statusOs},
            '{$obsStatus}',
            {$login_admin},
            't'
        );
        ";
        $resIns = pg_query($con,$sqlIns);

        $sql = "UPDATE tbl_laudo_tecnico_os SET afirmativa = {$afirmativa}, data = CURRENT_TIMESTAMP WHERE os = {$os}";
        $res = pg_query($con, $sql);

        if($_POST["laudo_tecnico_os"]){
            $laudo_tecnico_os = $_POST["laudo_tecnico_os"];
            $cond_laudo_tecnico = " AND laudo_tecnico_os = {$laudo_tecnico_os} ";
        }

        $sql = "SELECT observacao FROM tbl_laudo_tecnico_os WHERE os = {$os} {$cond_laudo_tecnico}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

            $observacao = json_decode(pg_fetch_result($res, 0, "observacao"), true);

            $observacao["motivo"][] = $motivo;

            $observacao = json_encode($observacao);
            $observacao = pg_escape_string($con, $observacao);

            $sql = "UPDATE tbl_laudo_tecnico_os SET observacao = '{$observacao}' WHERE os = {$os} {$cond_laudo_tecnico}";
            $res = pg_query($con, $sql);

        }

        /* $sqlDel = "DELETE  FROM tbl_laudo_tecnico_os
                    WHERE   os = $os";
        $resDel = pg_query($con,$sqlDel); */

        if(pg_last_error($con)){
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            $erro = "Ocorreu um erro ao reprovar o laudo";
        }else{
            $res = pg_query($con,"COMMIT TRANSACTION");

            $mailer = new PHPMailer;

            $sqlMail = "
                SELECT  email,
                        nome_completo
                FROM    tbl_admin
                WHERE   aprova_laudo IS TRUE
				AND		ativo
                AND     fabrica     = $login_fabrica
            ";
            $resMail = pg_query($con,$sqlMail);
            $conta = pg_numrows($resMail);

            $mailer->isSMTP();
            $mailer->IsHTML();

            $mailer->From = "no-reply@telecontrol.com.br";
            $mailer->FromName = "Posvenda Telecontrol";
            for($i=0;$i<$conta;$i++){
                $usuario = pg_fetch_result($resMail,$i,nome_completo);
                $email   = pg_fetch_result($resMail,$i,email);
                $mailer->addAddress($email,$usuario);
            }

            if ($_POST['correcao']) {
                $laudoCorrecao = " (Correção)";
            }

            $mailer->Subject = "OS $os com Formulário de Laudo REPROVADO {$laudoCorrecao}";
            $msg = "Prezado(s),";
            $msg .= "<br /><br />Laudo ".strtoupper($laudo)." da $os foi REPROVADO {$laudoCorrecao}.";
            $msg .= "<br />Motivo: $motivo ";
            $mailer->Body = $msg;

            $mailer->Send();

            header("Location: os_press.php?os=$os");
        }
    }else{
        $erro = "Favor, gravar o MOTIVO para a recusa do Laudo";
    }
}

if ($_POST['btn_nao_liberar']) {
    $res = pg_query($con,"BEGIN TRANSACTION");
    $sqlOs = "
        UPDATE  tbl_os
        SET     troca_garantia = FALSE, data_fechamento = null, finalizada = null 
        WHERE   os = $os
    ";
    $resOs = pg_query($con,$sqlOs);
    $msg_erro = pg_last_error($con);
    if(strlen($msg_erro) == 0){
        $sqlTroca = "
            DELETE  FROM tbl_os_troca
            WHERE   os = $os;

			DELETE  FROM bi_os_item
            WHERE   os_item IN (
                SELECT  tbl_os_item.os_item
                FROM    tbl_peca
                JOIN    tbl_os_item USING (peca)
                JOIN    tbl_os_produto USING (os_produto)
                JOIN    tbl_os USING (os)
                WHERE   tbl_os.os = $os
                AND     tbl_peca.produto_acabado IS TRUE
            );

            DELETE  FROM tbl_os_item
            WHERE   tbl_os_item.os_item IN (
                SELECT  tbl_os_item.os_item
                FROM    tbl_peca
                JOIN    tbl_os_item USING (peca)
                JOIN    tbl_os_produto USING (os_produto)
                JOIN    tbl_os USING (os)
                WHERE   tbl_os.os = $os
                AND     tbl_peca.produto_acabado IS TRUE
            );
        ";
        $resTroca = pg_query($con,$sqlTroca);
        $msg_erro = pg_last_error($con);

        $sql = "INSERT INTO tbl_os_status(
                                os,
                                status_os,
                                observacao,
                                fabrica_status,
                                admin
                            )VALUES(
                                {$os},
                                194,
                                'A troca ou ressarcimento n&atilde;o foi liberado pelo Backoffice',
                                {$login_fabrica},
                                {$login_admin}
                            )";
        $res = pg_query($con,$sql);

        /* $sqlDel = "DELETE  FROM tbl_laudo_tecnico_os
                    WHERE   os = $os";
        $resDel = pg_query($con,$sqlDel); */

        $sql = "UPDATE tbl_laudo_tecnico_os SET afirmativa = false, data = CURRENT_TIMESTAMP WHERE os = {$os} ; ";
        $sql .= "UPDATE tbl_os set data_fechamento = now() , finalizada = CURRENT_TIMESTAMP WHERE os = {$os}";
        $res = pg_query($con, $sql);

        $msg_erro = pg_last_error($con);

    }

    if(strlen($msg_erro) == 0){

        $sqlMail = "
            SELECT  email,
                    nome_completo
            FROM    tbl_admin
            WHERE   aprova_laudo IS TRUE
			AND		ativo
            AND     fabrica     = $login_fabrica
        ";
        $resMail = pg_query($con,$sqlMail);
        $conta = pg_numrows($resMail);

		$email_admin = array();
		for($i=0;$i<$conta;$i++){
			$usuario = pg_fetch_result($resMail,$i,nome_completo);
			$email   = pg_fetch_result($resMail,$i,email);
			$email_admin[] = $email;
		}

        $msg = "Prezado(s),";
        $msg .= "<br /><br />A OS $os n&atilde;o foi liberada para ser efetuda a TROCA ou RESSARCIMENTO do produto.";
// print_r($email_admin);
		$mailTc->setEmailSubject("OS $os não liberada para TROCA / RESSARCIMENTO");
		$mailTc->addToEmailBody($msg);
		$mailTc->setEmailFrom($externalEmail);
		$mailTc->addEmailDest($email_admin);
		$resultado = $mailTc->sendMail();

		if(!$resultado){
            $msg_erro = "Ocorreu um erro ao enviar o email ao admin respons&aacute;vel pelo laudo!";
        }

    }

    if(strlen($msg_erro) == 0){
        $res = pg_query($con,"COMMIT TRANSACTION");
        header("Location: os_press.php?os=$os");
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        $erro = "Ocorreu um erro na n&atilde;o libera&ccedil;&atilde;o da OS: ".$msg_erro;
    }
}

if ($_REQUEST["imprimir"] || $_REQUEST['alterar'] || $_REQUEST['liberar']) {
    $os = (strlen($_REQUEST["imprimir"] > 0)) ? $_REQUEST["imprimir"] : $_REQUEST["alterar"];
    if(strlen($os) == 0){
        $os = $_REQUEST['liberar'];
    }

    $sql = "SELECT  laudo_tecnico_os,
                    observacao
            FROM    tbl_laudo_tecnico_os
    ";
    if($_REQUEST['liberar']){
        $sql .= "
            JOIN    (
                        SELECT  tbl_os_status.os,
                                tbl_os_status.status_os
                        FROM    tbl_os_status
                        WHERE   tbl_os_status.os = $os
                        AND     status_os IN (192,193,194)
                  ORDER BY      os_status DESC
                        LIMIT   1
                    ) status    ON  status.os = tbl_laudo_tecnico_os.os
                                AND status.status_os = 193
        ";
    }
    $sql .= "
            WHERE   tbl_laudo_tecnico_os.fabrica = $login_fabrica
            AND     tbl_laudo_tecnico_os.os = $os 
            {$cond_laudo_tecnico}
    ";
    // echo(nl2br($sql));
    $res = pg_query($con, $sql);

    if (pg_num_rows($res)) {
        $laudo_tecnico_os = pg_fetch_result($res, 0, laudo_tecnico_os);
        $json = pg_fetch_result($res, 0, observacao);
        $checklistImprime = json_decode($json, true);

        $laudo = $checklistImprime['laudo'];
        $amazonTC = new TDocs($con,$login_fabrica,"os");
    } else {
        echo "<script>alert('Nenhum check list encontrado para a os $os');window.close();</script>";
    }

    if(is_array($checklistImprime)){
		unset($checklistImprime['os']['observacao']);
        $checklist = array_merge($checklist,$checklistImprime);
         if(empty($checklist['os']['observacao'])) $checklist['os']['observacao'] = utf8_encode($observacao);
	}

	if(empty($checklist['cliente']['endereco'])) {
		$checklist['cliente']['endereco'] = $cliente_endereco." ".$cliente_numero." ".$cliente_com;
	}
	if(empty($checklist['cliente']['bairro'])) {
		$checklist['cliente']['bairro'] = $cliente_bairro;
	}
}

switch($laudo){
    case "fat":
        $title = "FORMUL&Aacute;RIO DE AN&Aacute;LISE DE TROCA - FAT";
        $enctype = " enctype='multipart/form-data' ";
    break;

    case "fats":
        $title = "FORMUL&Aacute;RIO DE AN&Aacute;LISE DE TROCA - FAT SINISTRO";
        $enctype = " enctype='multipart/form-data' ";
    break;

    case "far":
        $title = "FORMUL&Aacute;RIO DE AN&Aacute;LISE DE RESTITUI&Ccedil;&Atilde;O - FAR";
        $enctype = " enctype='multipart/form-data' ";
    break;

    case "fatrev":
        $title = "FORMUL&Aacute;RIO DE AN&Aacute;LISE DE TROCA REVENDA - FAT";
        $enctype = " enctype='multipart/form-data' ";
    break;
}

?>

<html>
<head>
<title><?=$title?></title>

<link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />


<style>
    .check_ajuste{
        height: 2px;
    }

    .div_fieldset {
        padding-left: 0.75em;
        padding-right: 0.75em;
        border: 1px solid #2876fc;
        border-radius: 6px;
        margin-bottom: 10px;
    }

    .foto{
        height:auto;
        padding-bottom: 20px;
    }

    .erro{
        background-color:#F00;
        color:#FFF;
        text-align:center;
        font-weight:bold;
        font-size:14px;
    }
</style>

<!--[if lt IE 10]>
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
<link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
<![endif]-->

<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../bootstrap/js/bootstrap.js"></script>
<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js" type="text/javascript"></script>
<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js" type="text/javascript"></script>
<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js" type="text/javascript"></script>
<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js" type="text/javascript"></script>
<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js" type="text/javascript"></script>
<script src="../plugins/price_format/jquery.price_format.1.7.min.js" type="text/javascript"></script>
<script src='../plugins/jquery.mask.js'></script>
<script src="../plugins/price_format/config.js" type="text/javascript"></script>
<script src="../plugins/price_format/accounting.js" type="text/javascript"></script>
<script src="plugins/bootstrap/filestyle/bootstrap-filestyle.min.js" type="text/javascript"></script>

<script type="text/javascript">
    $(function(){

        $("#correcao").click(function(){
            if ($(this).is(":checked")) {
                $("#aprovar, #gravar").hide();
            } else {
                $("#aprovar, #gravar").show();
            }
        });

        $("input[id*=_data_]").each(function(){
            $(this).datepicker({
                format:'dd/mm/yyyy',
                startDate:'2000-01-01'
            });
            $(this).mask("99/99/9999");
        });

        $('#negociacao_valor').priceFormat({
            prefix: ' ',
            centsSeparator: ',',
            thousandsSeparator: '.'
        });

        $("#fone_revenda").mask("(99) 99999-9999");


    });


    function carregaPreviewUpload(input, posicao, check = "") {
        if (input.files && input.files[0]) {

            if(check != ""){

                $("input[name='"+check+"']").prop("checked", true);

            }

            var reader = new FileReader();
            reader.onload = function (e) {
                $('#imagem_preview_'+posicao).html('<img id="imagem_'+posicao+'" src="#" />');
                $('#imagem_cadastrada_'+posicao).hide('slow');

                $('#imagem_'+posicao)
                    .attr('src', e.target.result)
                    .width(150)
                    .height(180);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</head>
<body>
<div class="container">
<?
if(strlen($erro) > 0 || strlen($msg_erro) > 0){
?>
    <!-- <div class="erro"></div> -->
    <div class='alert alert-error'>
        <?=(empty($msg_erro) ? $erro : $msg_erro)?>
    </div>
<?
}
if ($_REQUEST["imprimir"]) {
    $completo = "formulario_".$laudo."_".date('Ymd_mis').".pdf";

    ob_clean();
    ob_start();

}
?>

    <form method="post" class="" <?=$enctype?>>
        <input type="hidden" name="laudo"   value="<?=$laudo?>" />
        <input type="hidden" name="os"      value="<?=$os?>" />
        <input type="hidden" name="laudo_tecnico_os" value="<?=$laudo_tecnico_os?>" />

    <div id="areaImpressa">
        <div class="row-fluid">
            <div class="span12">
                <span class="pull-left">
                    <img class="logo" src="logos/esmaltec_admin1.jpg" />
                </span>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span12">
                <span>
                    <h2><?=$title?></h2>
                </span>
            </div>
        </div>
        <br />

        <!-- DADOS CLIENTE -->
        <div class="div_fieldset">
        <div class="row-fluid">
            <h4>DADOS CADASTRAIS DO CLIENTE</h4>
        </div>
        <div class="row-fluid">
            <div class="span6">
                <div class="control-group">
                    <label for='cliente_nome'>
                        <strong>Nome:</strong>
                        <br />
                        <span><?=$checklist['cliente']['nome']?></span>
                    </label>
                </div>
            </div>
            <div class="span6">
                <div class="control-group">
                    <label for='cliente_fone'>
                        <strong>Fone:</strong>
                        <br />
                        <span><?=$checklist['cliente']['fone']?></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span12">
                <div class="control-group">
                    <label for='cliente_endereco'>
                        <strong>Endere&ccedil;o:</strong>
                        <br />
                        <span><?=($checklist['cliente']['endereco'])?></span>
                    </label>
                </div>
            </div>
            <!-- <div class="span6"></div> -->
        </div>
        <div class="row-fluid">
            <div class="span4">
                <div class="control-group">
                    <label for='cliente_bairro'>
                        <strong>Bairro:</strong>
                        <br />
                        <span><?=$checklist['cliente']['bairro']?></span>
                    </label>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label for='cliente_cidade'>
                        <strong>Cidade:</strong>
                        <br />
                        <span><?=$checklist['cliente']['cidade']?></span>
                    </label>
                </div>
            </div>
            <div class="span2">
                <div class="control-group">
                    <label for='cliente_estado'>
                        <strong>Estado:</strong>
                        <br />
                        <span><?=$checklist['cliente']['estado']?></span>
                    </label>
                </div>
            </div>
            <div class="span2">
                <div class="control-group">
                    <label for='cliente_estado'>
                        <strong>CEP:</strong>
                        <br />
                        <span><?=$checklist['cliente']['cep']?></span>
                    </label>
                </div>
            </div>
        </div>
        </div>
        <!-- FIM DADOS CLIENTE -->

        <!-- DADOS DO POSTO -->
        <div class="div_fieldset">
        <div class="row-fluid">
            <h4>DADOS CADASTRAIS DO POSTO AUTORIZADO</h4>
        </div>

        <div class="row-fluid">
            <div class="span3">
                <div class="control-group">
                    <label for='empresa_codigo'>
                        <strong>C&oacute;digo do Posto:</strong>
                        <br />
                        <span><?=$checklist['empresa']['codigo']?></span>
                    </label>
                </div>
            </div>
            <div class="span6">
                <div class="control-group">
                    <label for='empresa_nome'>
                        <strong>Raz&atilde;o Social:</strong>
                        <br />
                        <span><?=$checklist['empresa']['nome']?></span>
                    </label>
                </div>
            </div>
            <div class="span3">
                <div class="control-group">
                    <label for='empresa_fone'>
                        <strong>Fone:</strong>
                        <br />
                        <span><?=$checklist['empresa']['fone']?></span>
                    </label>
                </div>
            </div>
        </div>
        </div>
        <!-- FIM DADOS DO POSTO -->

        <!-- DADOS DO PRODUTO RECLAMADO -->
        <div class="div_fieldset">
        <div class="row-fluid">
            <h4>DADOS DO PRODUTO RECLAMADO</h4>
        </div>
        <div class="row-fluid">
            <div class="span12">
                <div class="control-group">
                    <label for='equipamento_modelo'>
                        <strong>Produto:</strong>
                        <br />
                        <span><?=$checklist['equipamento']['modelo']?></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="row-fluid">
            <div class="span4">
                <div class="control-group">
                    <label for='equipamento_serie'>
                        <strong>N&uacute;mero de S&eacute;rie:</strong>
                        <br />
                        <span><?=$checklist['equipamento']['serie']?></span>
                    </label>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label for='equipamento_nota_fiscal'>
                        <strong>N&uacute;mero de NF:</strong>
                        <br />
                        <span><?=$checklist['equipamento']['nota_fiscal']?></span>
                    </label>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label for='equipamento_data_venda'>
                        <strong>Data Compra:</strong>
                        <br />
                        <span><?=$checklist['equipamento']['data_venda']?></span>
                    </label>
                </div>
            </div>
        </div>
        </div>
        <!-- FIM DADOS DO PRODUTO RECLAMADO -->

        <!-- HD - 6047953 -->
        <?php if ($login_fabrica == 30 && (strlen($_GET["laudo"]) == 0 || $_GET["laudo"] == "fatrev") || $laudo =='fatrev') {
            $aux_sql = "SELECT observacao FROM tbl_laudo_tecnico_os WHERE os = $os";
            $aux_res = pg_query($con, $aux_sql);
            $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'observacao'), true);

            $aux_sql = "SELECT revenda_nome, revenda_cnpj FROM tbl_os WHERE os = $os";
            $aux_res = pg_query($con, $aux_sql);

            $revenda_nome = pg_fetch_result($aux_res, 0, 'revenda_nome');
            $revenda_cnpj = pg_fetch_result($aux_res, 0, 'revenda_cnpj');

            if (strlen($imprimir) > 0 || strlen($alterar) > 0 || strlen($liberar) > 0 || ((isset($aux_arr["dados_revenda"]) && !empty($aux_arr["dados_revenda"]))) ) {
                $dados_revenda["contato_responsavel"] = $aux_arr["dados_revenda"]["contato_responsavel"];
                $dados_revenda["fone_revenda"]        = $aux_arr["dados_revenda"]["fone_revenda"];

                $selected_devolucao = "";
                $selected_estoque   = "";

                if ($aux_arr["dados_revenda"]["negociacao_revenda"] == "devolucao") {
                    $selected_devolucao = "CHECKED='checked'";
                    $dados_revenda["negociacao_revenda"] = '&nbsp;Devolu&ccedil;&atilde;o de Compra com NF de devolu&ccedil;&atilde;o';
                } else {
                    $selected_estoque = "CHECKED='checked'";
                    $dados_revenda["negociacao_revenda"] = 'Reposi&ccedil;&atilde;o de Estoque';
                }
            } ?>
            <div class="div_fieldset">
            <div class="row-fluid">
                <h4>DADOS DA REVENDA</h4>
            </div>
            <div class="row-fluid">
                <div class="span6">
                    <div class="control-group">
                        <label for='os_os'>
                            <strong>Nome da Revenda</strong>
                            <br />
                            <span><?=$revenda_nome;?></span>
                        </label>
                    </div>
                </div>
                <div class="span6">
                    <div class="control-group">
                        <label for='os_data_digitacao'>
                            <strong>CNPJ da Revenda</strong>
                            <br />
                            <span><?=$revenda_cnpj;?></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span6">
                    <div class="control-group">
                        <label for='os_os'>
                            <strong>Contato ou Respons&aacute;vel</strong>
                            <br />
                            <span>
                                <?php if (strlen($imprimir) == 0) { ?>
                                    <input type="text" name="contato_responsavel" id="contato_responsavel" value="<?=utf8_decode($dados_revenda["contato_responsavel"]);?>">
                                <?php } else {
                                    echo utf8_decode($dados_revenda["contato_responsavel"]);
                                } ?>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="span6">
                    <div class="control-group">
                        <label for='os_data_digitacao'>
                            <strong>Fone</strong>
                            <br />
                            <span>
                                <?php if (strlen($imprimir) == 0) { ?>
                                    <input type="text" name="fone_revenda" id="fone_revenda" value="<?=$dados_revenda["fone_revenda"];?>">
                                <?php } else {
                                    echo $dados_revenda["fone_revenda"];
                                } ?>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span8">
                    <div class="control-group">
                        <label for='os_os'>
                            <strong>Forma de negocia&ccedil;&atilde;o com a Revenda</strong>
                            <br />
                            <span>
                                <?php if (strlen($imprimir) == 0) { ?>
                                    <label for="forma_negociacao_revenda_devolucao" class="checkbox span8">
                                        <input type="radio" name="forma_negociacao_revenda" id="forma_negociacao_revenda_devolucao" <?=$selected_devolucao;?> value="devolucao">&nbsp;Devolu&ccedil;&atilde;o de Compra com NF de devolu&ccedil;&atilde;o
                                    </label>
                                    <label for="forma_negociacao_revenda_reposicao" class="checkbox">
                                        <input type="radio" name="forma_negociacao_revenda" id="forma_negociacao_revenda_reposicao" <?=$selected_estoque;?> value="reposicao">Reposi&ccedil;&atilde;o de Estoque
                                    </label>
                                <?php } else {
                                    echo $dados_revenda["negociacao_revenda"];
                                } ?>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            </div>
        <?php } ?>

        <!-- HISTORICO DO ATENDIMENTO -->
        <div class="div_fieldset">
        <div class="row-fluid">
            <h4>HIST&Oacute;RICO DO ATENDIMENTO</h4>
        </div>
        <div class="row-fluid">
            <div class="span6">
                <div class="control-group">
                    <label for='os_os'>
                        <strong>Ordem de Servi&ccedil;o:</strong>
                        <br />
                        <span><?=$checklist['os']['os']?></span>
                    </label>
                </div>
            </div>
            <div class="span6">
                <div class="control-group">
                    <label for='os_data_digitacao'>
                        <strong>Data de Digita&ccedil;ao OS:</strong>
                        <br />
                        <span><?=$checklist['os']['data_digitacao']?></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span6">
                <div class="control-group">
                    <label for='os_data_abertura'>
                        <strong>Data de Abertura OS:</strong>
                        <br />
                        <span><?=$checklist['os']['data_abertura']?></span>
                    </label>
                </div>
            </div>
            <div class="span6">
                <div class="control-group">
                    <label for='os_data_conserto'>
                        <strong>Data de Conserto OS:</strong>
                        <br />
                        <span><?=$checklist['os']['data_conserto']?></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span6">
                <div class="control-group">
                    <label for='os_data_fechamento'>
                        <strong>Data de Fechamento OS:</strong>
                        <br />
                        <span><?=$checklist['os']['data_fechamento']?></span>
                    </label>
                </div>
            </div>
            <div class="span6">
                <div class="control-group">
                    <label for='os_dias_fechamento'>
                        <strong>Qtde de dias em aberto:</strong>
                        <br />
                        <span><?=$checklist['os']['dias_fechamento']?></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span6">
                <div class="control-group">
                    <label for='os_defeito_reclamado_descricao'>
                        <strong>Relato do Ocorrido:</strong>
                        <br />
                        <span><?=$checklist['os']['defeito_reclamado_descricao']?></span>
                    </label>
                </div>
            </div>
            <div class="span6">
                <div class="control-group">
                    <label for='os_defeito_constatado'>
                        <strong>Constata&ccedil;ao:</strong>
                        <br />
                        <span><?=$checklist['os']['defeito_constatado']?></span>
                    </label>
                </div>
            </div>
        </div>
        </div>
        <!-- FIM HISTORICO DO ATENDIMENTO -->

        <!-- INFORMA&Ccedil;&Otilde;ES ADICIONAIS / JUSTIFICATIVA-->
        <div class="div_fieldset">
            <div class="row-fluid">
                <h4>INFORMA&Ccedil;&Otilde;ES ADICIONAIS / JUSTIFICATIVA</h4>
            </div>
            <div class="row-fluid">
                <div class="span12">
                    <div class="control-group">
                        <label for='os_os'>
                            <strong>Observa&ccedil;&atilde;o:</strong>
                            <br />
                            <?php
                            if(strlen($imprimir) == 0){ ?>
                                <textarea style="width: 828px;" id="observacao_troca" name="observacao_troca" cols="100" rows="4" ><?=$checklist['os']['observacao']?></textarea>
                            <?php
                            } else { ?>
                                <span><?=$checklist['os']['observacao']?></span>
                            <?php
                            } ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <!-- FIM INFORMA&Ccedil;&Otilde;ES ADICIONAIS / JUSTIFICATIVA-->

        <!-- CAUSA DA TROCA DO PRODUTO -->
        <div class="div_fieldset">
        <div class="row-fluid">
            <h4>CAUSA DA <?=($laudo == 'far') ? "RESTITUI&Ccedil;&Atilde;O" : "TROCA";?><?=($laudo == 'fats') ? "/RESTITUI&Ccedil;&Atilde;O" : "";?> DE PRODUTO</h4>
        </div>
        <div class="row-fluid">
            <div class="span12">
                <div class="control-group">
                    <label for='equipamento_causa_troca'>
                        <strong>Causa:</strong>
                        <br />
                        <?php
                            if(strlen($imprimir) == 0){ ?>
                            <?php
                                $sqlCausa = "SELECT  tbl_causa_troca.causa_troca,
                                tbl_causa_troca.codigo     ,
                                tbl_causa_troca.descricao
                                FROM tbl_causa_troca
                                WHERE tbl_causa_troca.fabrica = {$login_fabrica}
                                AND tbl_causa_troca.ativo     IS TRUE
                                ORDER BY tbl_causa_troca.codigo,tbl_causa_troca.descricao";
                                $resCausa = pg_query ($con,$sqlCausa);
                            ?>
                                <select name='causa_troca' id='causa_troca' size='1' class='frm' style='width:60%'>
                                    <option value='' ></option>
                                    <?php
                                    while ($dadosCausa = pg_fetch_object($resCausa)) {

                                        if (!empty($_POST['causa_troca'])) {
                                            $selected = ($_POST['causa_troca'] == $dadosCausa->causa_troca) ? "selected" : "";
                                        } else {
                                            $selected = ($id_causa_troca == $dadosCausa->causa_troca) ? "selected" : "";
                                        }
                                        
                                        ?>
                                        <option value="<?= $dadosCausa->causa_troca ?>" <?= $selected ?>><?= $dadosCausa->codigo ?> - <?= utf8_encode($dadosCausa->descricao) ?></option>
                                    <?php
                                    } ?>
                                </select>
                        <?php
                        } else { ?>
                            <span><?=$checklist['equipamento']['causa_troca'];?></span>
                        <?php
                        }
                        ?>
                        
                    </label>
                </div>
            </div>
        </div>
        </div>
        <!-- FIM CAUSA DA TROCA DO PRODUTO -->
<?php
    /**
     * - Verificação de anexos previamente
     * cadastrados em Help-desk de posto
     * autorizado
     *
     * @chamado hd-3053295
     * @author William Ap. Brandino
     */

    $sqlAnexo = "
        SELECT  hd_chamado
        FROM    tbl_hd_chamado
        JOIN    tbl_hd_chamado_extra USING(hd_chamado)
        WHERE   os = $os
		AND		fabrica_responsavel = $login_fabrica
        AND     titulo = 'Help-Desk Posto'
        AND     status <> 'Cancelado'
    ";
    $resAnexo = pg_query($con,$sqlAnexo);

    $hd_chamado = pg_fetch_result($resAnexo,0,hd_chamado);

    if (!empty($hd_chamado)) {
        $amazonTCHD = new TDocs($con,$login_fabrica,"helpdesk_pa");
        $anexos = $amazonTCHD->getDocumentsByRef($hd_chamado,'hdposto')->attachListInfo;

        $labels = array(
            0 => 'nota_fiscal',
            1 => 'etiqueta',
            2 => 'produto_1',
            3 => 'produto_2',
            4 => 'produto_3'
        );

        foreach ($anexos as $anexo) {
            $auxNomeArq             = $anexo["filename"];
            $auxNomeArq             = explode(".",$auxNomeArq);

            $posicao = explode("_",$auxNomeArq[0]);
            $posComp = $posicao[1];

            $chaveTrans                 = $labels[$posComp];
            $transAnexo[$chaveTrans]    = $anexo["link"];
        }
    }

    
    switch($laudo){
        case 'fat':
        case 'fatrev':
            $sqlProdTroca = "
                SELECT  tbl_produto.produto AS produto_troca,
                        tbl_produto.referencia,
                        tbl_produto.descricao,
                        tbl_produto.voltagem
                FROM    tbl_produto
                JOIN    tbl_peca     ON tbl_peca.referencia     = tbl_produto.referencia
                                    AND tbl_produto.fabrica_i   = $login_fabrica
                JOIN    tbl_os_troca ON tbl_os_troca.peca       = tbl_peca.peca
                WHERE   tbl_os_troca.os = ".$checklist['os']['os']."
            ";
            $resProdTroca = pg_query($con,$sqlProdTroca);

            $checklist['troca']['produto_troca']    = pg_fetch_result($resProdTroca,0,produto_troca);
            $checklist['troca']['referencia']       = pg_fetch_result($resProdTroca,0,referencia);
            $checklist['troca']['descricao']        = pg_fetch_result($resProdTroca,0,descricao);
            $checklist['troca']['voltagem']         = pg_fetch_result($resProdTroca,0,voltagem);
?>
            <!-- PRODUTO DE TROCA -->
            <div class="div_fieldset">
            <div class="row-fluid">
                <h4>PRODUTO DE TROCA</h4>
            </div>
            <div class="row-fluid">
                <div class="span12">
                    <div class="control-group">
                        <label for='troca_referencia'>
                            <strong>C&oacute;digo:</strong>
                            <br />
                            <span><?=$checklist['troca']['referencia']?></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span12">
                    <div class="control-group">
                        <label for='troca_descricao'>
                            <strong>Modelo:</strong>
                            <br />
                            <span><?=utf8_encode($checklist['troca']['descricao'])?></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row-fluid">
                    <div class="span12">
                        <div class="control-group">
                            <label for='troca_motivo'>
                                <strong>Se "produto para troca" for diferente/superior do "produto reclamado", relate o motivo:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="hidden" id="produto_troca" name="produto_troca" value="<?=$checklist['troca']['produto_troca']?>" />
                                <textarea style="width: 828px;" id="troca_motivo" name="troca_motivo" cols="100" rows="4"  ><?=$checklist['troca']['motivo']?></textarea>
<?
    }else{
?>
                                <br />
                                <span><?=$checklist['troca']['motivo']?></span>
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <!-- FIM PRODUTO DE TROCA -->

            <!-- REGISTRO FOTOGR&Aacute;FICO -->
            <div class="div_fieldset foto">
                <div class="row-fluid">
                    <h4>ANEXOS</h4>
                </div>

                <div class="row-fluid check_ajuste">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?

    if (strlen($imprimir) == 0 && empty($transAnexo['etiqueta'])) {
?>
                                <input type="checkbox" name="foto_etiqueta" value="sim" <?=(($checklist["foto"]["etiqueta"] == "sim") ? "checked" : "")?> />
                                <strong>Etiqueta do Produto</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,1,'foto_etiqueta');" name="foto_etiqueta_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_1"></div>
<?
    }
    
    if (!empty($checklist["foto"]["foto_etiqueta_img"]) || !empty($transAnexo['etiqueta'])) {
        if (empty($transAnexo['etiqueta'])) {
            $anexosEtiqueta = $amazonTC->getDocumentsByName($checklist["foto"]["foto_etiqueta_img"],null,$os)->url;

            if (filter_var($anexosEtiqueta,FILTER_VALIDATE_URL)) {
                $foto_etiqueta_img      = $anexosEtiqueta;
            }
        } else {

            $foto_etiqueta_img = $transAnexo['etiqueta'];
?>
                                <strong>Etiqueta do Produto</strong>
<?php

        }
        if(strlen($imprimir) > 0 && empty($transAnexo['etiqueta'])){
?>
                                <strong>Etiqueta do Produto: </strong><?=$checklist["foto"]["etiqueta"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_1" src="<?=$foto_etiqueta_img?>" width="200px" height="200px" border="0" />
<?
    }
?>

                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if (strlen($imprimir) == 0 && empty($transAnexo['nota_fiscal'])) {
?>
                                <input type="checkbox" name="foto_nf" value="sim" <?=(($checklist["foto"]["nf"] == "sim") ? "checked" : "")?> />
                                <strong>NF</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,2,'foto_nf');" name="foto_nf_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_2"></div>
<?
    }
    
    if (!empty($checklist["foto"]["foto_nf_img"]) || !empty($transAnexo['nota_fiscal'])) {

        if (empty($transAnexo['nota_fiscal'])) {
            $anexosNf = $amazonTC->getDocumentsByName($checklist["foto"]["foto_nf_img"],null,$os)->url;

            if (filter_var($anexosNf,FILTER_VALIDATE_URL)) {
                $foto_nf_img      = $anexosNf;
            }
        } else {
            $foto_nf_img    = $transAnexo['nota_fiscal'];
?>
                                <strong>NF</strong>
<?php
        }
        if (strlen($imprimir) > 0 && empty($transAnexo['nota_fiscal'])) {
?>
                                <strong>NF: </strong><?=$checklist["foto"]["foto_nf"]?>
<?
        }
    ?>
                                <br />
                                <img  id="imagem_cadastrada_2" src="<?=$foto_nf_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>

                </div>

                <div class="row-fluid check_ajuste">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if (strlen($imprimir) == 0 && empty($transAnexo['produto_1'])) {
?>
                                <input type="checkbox" name="foto_produto1" value="sim" <?=(($checklist["foto"]["produto1"] == "sim") ? "checked" : "")?> />
                                <strong>Produto 1</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,3,'foto_produto1');" name="foto_produto1_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_3"></div>
<?
    }
    if (!empty($checklist["foto"]["foto_produto1_img"]) || !empty($transAnexo['produto_1'])) {
        if (empty($transAnexo['produto_1'])) {
            $anexosProd01 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_produto1_img"],null,$os)->url;

            if (filter_var($anexosProd01,FILTER_VALIDATE_URL)) {
                $foto_produto1_img      = $anexosProd01;
            }
        } else {
            $foto_produto1_img  = $transAnexo['produto_1'];
?>
                                <strong>Produto 1</strong>
<?php
        }
        if (strlen($imprimir) > 0 && empty($transAnexo['produto_1'])) {
?>
                                <strong>Produto 1: </strong><?=$checklist["foto"]["produto1"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_3" src="<?=$foto_produto1_img?>" width="200px" height="200px" border="0" />
<?
    }
?>

                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if (strlen($imprimir) == 0 && empty($transAnexo['produto_2'])) {
?>
                                <input type="checkbox" name="foto_produto2" value="sim" <?=(($checklist["foto"]["produto2"] == "sim") ? "checked" : "")?> />
                                <strong>Produto 2</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,4,'foto_produto2');" name="foto_produto2_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_4"></div>
<?
    }
    if (!empty($checklist["foto"]["foto_produto2_img"]) || !empty($transAnexo['produto_2'])) {
        if (empty($transAnexo['produto_2'])) {
            $anexosProd02 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_produto2_img"],null,$os)->url;

            if (filter_var($anexosProd02,FILTER_VALIDATE_URL)) {
                $foto_produto2_img      = $anexosProd02;
            }
        } else {
            $foto_produto2_img  = $transAnexo['produto_2'];
?>
                                <strong>Produto 2</strong>
<?php
        }
        if(strlen($imprimir) > 0 && empty($transAnexo['produto_2'])){
?>
                                <strong>Produto 2: </strong><?=$checklist["foto"]["produto2"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_4" src="<?=$foto_produto2_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row-fluid check_ajuste">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if (strlen($imprimir) == 0 && empty($transAnexo['produto_3'])) {
?>
                                <input type="checkbox" name="foto_produto3" value="sim" <?=(($checklist["foto"]["produto3"] == "sim") ? "checked" : "")?> />
                                <strong>Produto 3</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,5,'foto_produto3');" name="foto_produto3_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_5"></div>
<?
    }
    if (!empty($checklist["foto"]["foto_produto3_img"]) || !empty($transAnexo['produto_3'])) {
        if (empty($transAnexo['produto_3'])) {
            $anexosProd03 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_produto3_img"],null,$os)->url;

            if (filter_var($anexosProd03,FILTER_VALIDATE_URL)) {
                $foto_produto3_img      = $anexosProd03;
            }
        } else {
            $foto_produto3_img  = $transAnexo['produto_3'];
?>
                                <strong>Produto 3</strong>
<?php
        }
        if(strlen($imprimir) > 0 && empty($transAnexo['produto_3'])){
?>
                                <strong>Produto 3: </strong><?=$checklist["foto"]["produto3"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_5" src="<?=$foto_produto3_img?>" width="200px" height="200px" border="0" />
<?
    }
?>

                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_aceite1" value="sim" <?=(($checklist["foto"]["aceite1"] == "sim") ? "checked" : "")?> />
                                <strong>Termo de Aceite 1</strong>
                                <input type="file" name="foto_aceite1_img" onchange="carregaPreviewUpload(this,6,'foto_aceite1');" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_6"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_aceite1_img"])){
        $anexosAceite01 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_aceite1_img"],null,$os)->url;

        if (filter_var($anexosAceite01,FILTER_VALIDATE_URL)) {
            $foto_aceite1_img      = $anexosAceite01;
        }
        if(strlen($imprimir) > 0){
?>
                                <strong>Termo de Aceite 1: </strong><?=$checklist["foto"]["aceite1"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_6" src="<?=$foto_aceite1_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row-fluid check_ajuste">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_aceite2" value="sim" <?=(($checklist["foto"]["aceite2"] == "sim") ? "checked" : "")?> />
                                <strong>Termo de Aceite 2</strong>
                                <input type="file" name="foto_aceite2_img" onchange="carregaPreviewUpload(this,7,'foto_aceite2');" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_7"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_aceite2_img"])){

        $termo_aceite_2 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_aceite2_img"],null,$os)->url;

        if (filter_var($termo_aceite_2,FILTER_VALIDATE_URL)) {
            $foto_aceite2_img      = $termo_aceite_2;
        }
        if(strlen($imprimir) > 0){
?>
                                <strong>Termo de Aceite 2: </strong><?=$checklist["foto"]["aceite2"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_7" src="<?=$foto_aceite2_img?>" width="200px" height="200px" border="0" />
<?
    }
?>

                            </label>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
            <!-- FIM REGISTRO FOTOGR&Aacute;FICO -->



<?
        break;
        case 'fats':
?>
            <!-- RESPONDA AS PERGUNTAS ABAIXO -->
            <div class="div_fieldset">
            <div class="row-fluid">
                <h4>RESPONDA AS PERGUNTAS ABAIXO</h4>
            </div>

            <div class="row-fluid check_ajuste">
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_mangueira'>
                            <strong>A Mangueira instalada no produto &eacute; met&aacute;lica ou pl&aacute;stica?</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_mangueira" value="metalica" <?=(($checklist["pergunta"]["mangueira"] == "metalica") ? "checked" : "")?> /> Met&aacute;lica
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                        <input type="radio" name="pergunta_mangueira" value="plastico" <?=(($checklist["pergunta"]["mangueira"] == "plastico") ? "checked" : "")?> /> Pl&aacute;stico                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["mangueira"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>

            <div class="row-fluid check_ajuste">
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_inmetro'>
                            <strong>A Mangueira instalada no produto &eacute; certificada pelo INMETRO?</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_inmetro" value="sim" <?=(($checklist["pergunta"]["inmetro"] == "sim") ? "checked" : "")?> /> Sim
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_inmetro" value="nao" <?=(($checklist["pergunta"]["inmetro"] == "nao") ? "checked" : "")?> /> N&atilde;o
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["inmetro"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>

            <div class="row-fluid check_ajuste">
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_prazo'>
                            <strong>A Mangueira instalada no produto est&aacute; dentro do prazo de validade de CINCO anos?</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_prazo" value="sim" <?=(($checklist["pergunta"]["prazo"] == "sim") ? "checked" : "")?> /> Sim
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_prazo" value="nao" <?=(($checklist["pergunta"]["prazo"] == "nao") ? "checked" : "")?> /> N&atilde;o
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["prazo"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>

            <div class="row-fluid check_ajuste">
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_regulador'>
                            <strong>O Regulador instalado no produto est&aacute; dentro do prazo de validade de CINCO anos?</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_regulador" value="sim" <?=(($checklist["pergunta"]["regulador"] == "sim") ? "checked" : "")?> /> Sim
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_regulador" value="nao" <?=(($checklist["pergunta"]["regulador"] == "nao") ? "checked" : "")?> /> N&atilde;o
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["regulador"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>

            <div class="row-fluid check_ajuste">
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_fogao'>
                            <strong>A Mangueira estava passando por tr&aacute;s do Fog&atilde;o?</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_fogao" value="sim" <?=(($checklist["pergunta"]["fogao"] == "sim") ? "checked" : "")?> /> Sim
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_fogao" value="nao" <?=(($checklist["pergunta"]["fogao"] == "nao") ? "checked" : "")?> /> N&atilde;o
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["fogao"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>

            <div class="row-fluid">
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_abracadeira'>
                            <strong>A Mangueira estava com Abra&ccedil;adeira Met&aacute;lica, conforme indicado no Manual de Instru&ccedil;&atilde;o?</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_abracadeira" value="sim" <?=(($checklist["pergunta"]["abracadeira"] == "sim") ? "checked" : "")?> /> Sim
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_abracadeira" value="nao" <?=(($checklist["pergunta"]["abracadeira"] == "nao") ? "checked" : "")?> /> N&atilde;o
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["abracadeira"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>

            <div class="row-fluid" >
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_plastico'>
                            <strong>Havia algum objeto pl&aacute;stico no forno?</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_plastico" value="sim" <?=(($checklist["pergunta"]["plastico"] == "sim") ? "checked" : "")?> /> Sim
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_plastico" value="nao" <?=(($checklist["pergunta"]["plastico"] == "nao") ? "checked" : "")?> /> N&atilde;o
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["plastico"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>

            <div class="row-fluid">
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_materiais'>
                            <strong>Houveram Danos Materiais?</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_materiais" value="sim" <?=(($checklist["pergunta"]["materiais"] == "sim") ? "checked" : "")?> /> Sim
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_materiais" value="nao" <?=(($checklist["pergunta"]["materiais"] == "nao") ? "checked" : "")?> /> N&atilde;o
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["materiais"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>

            <div class="row-fluid">
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_pessoais'>
                            <strong>Houveram Danos Pessoais?</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_pessoais" value="sim" <?=(($checklist["pergunta"]["pessoais"] == "sim") ? "checked" : "")?> /> Sim
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_pessoais" value="nao" <?=(($checklist["pergunta"]["pessoais"] == "nao") ? "checked" : "")?> /> N&atilde;o
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["pessoais"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>

            <div class="row-fluid">
                <div class="span8">
                    <div class="control-group">
                        <label for='pergunta_sinistro'>
                            <strong>Em que momento ocorreu o sinistro? (O forno era utilizado no momento?)</strong>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_sinistro" value="sim" <?=(($checklist["pergunta"]["sinistro"] == "sim") ? "checked" : "")?> /> Sim
                        </label>
                    </div>
                </div>
                <div class="span2">
                    <div class="control-group">
                        <label class="radio">
                            <input type="radio" name="pergunta_sinistro" value="nao" <?=(($checklist["pergunta"]["sinistro"] == "nao") ? "checked" : "")?> /> N&atilde;o
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span4">
                    <div class="control-group">
                        <span><?=$checklist["pergunta"]["sinistro"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>
            </div>
            <!-- FIM RESPONDA AS PERGUNTAS ABAIXO -->

            <!-- REGISTRO FOTOGR&Aacute;FICO -->
            <div class="div_fieldset foto">
                <div class="row-fluid">
                    <h4>REGISTRO FOTOGR&Aacute;FICO</h4>
                </div>

                <div class="row-fluid check_ajuste">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_frente" value="sim" <?=(($checklist["foto"]["frente"] == "sim") ? "checked" : "")?> />
                                <strong>Frente do Produto</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,8,'foto_frente');" name="foto_frente_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_8"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_frente_img"])){
        $anexosFrente = $amazonTC->getDocumentsByName($checklist["foto"]["foto_frente_img"],null,$os)->url;

        if (filter_var($anexosFrente,FILTER_VALIDATE_URL)) {
            $foto_frente_img      = $anexosFrente;
        }

        if (strlen($imprimir) > 0) {
?>
                                <strong>Frente do Produto: </strong><?=$checklist["foto"]["frente"]?>
<?php
        }
?>
                                <br />
                                <img id="imagem_cadastrada_8" src="<?=$foto_frente_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_lada" value="sim" <?=(($checklist["foto"]["lada"] == "sim") ? "checked" : "")?> />
                                <strong>Lateral Direita do Ambiente</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,9,'foto_lada');" name="foto_lada_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_9"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_lada_img"])){
        $anexosLada = $amazonTC->getDocumentsByName($checklist["foto"]["foto_lada_img"],null,$os)->url;

        if (filter_var($anexosLada,FILTER_VALIDATE_URL)) {
            $foto_lada_img      = $anexosLada;
        }

        if (strlen($imprimir) > 0) {
?>
                                <strong>Lateral Direita do Ambiente: </strong><?=$checklist["foto"]["lada"]?>
<?php
        }
?>
                                <br />
                                <img id="imagem_cadastrada_9" src="<?=$foto_lada_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row-fluid check_ajuste">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_traseira" value="sim" <?=(($checklist["foto"]["traseira"] == "sim") ? "checked" : "")?> />
                                <strong>Traseira do Produto</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,10,'foto_traseira');" name="foto_traseira_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_10"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_traseira_img"])){
        $anexosTraseira = $amazonTC->getDocumentsByName($checklist["foto"]["foto_traseira_img"],null,$os)->url;

        if (filter_var($anexosTraseira,FILTER_VALIDATE_URL)) {
            $foto_traseira_img      = $anexosTraseira;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Traseira do Produto: </strong><?=$checklist["foto"]["traseira"]?>
<?php
        }
?>
                                <br />
                                <img id="imagem_cadastrada_10" src="<?=$foto_traseira_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_laea" value="sim" <?=(($checklist["foto"]["laea"] == "sim") ? "checked" : "")?> />
                                <strong>Lateral Esquerda do Ambiente</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,11,'foto_laea');" name="foto_laea_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_11"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_laea_img"])){
        $anexosLaea = $amazonTC->getDocumentsByName($checklist["foto"]["foto_laea_img"],null,$os)->url;

        if (filter_var($anexosLaea,FILTER_VALIDATE_URL)) {
            $foto_laea_img      = $anexosLaea;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Lateral Esquerda do Ambiente: </strong><?=$checklist["foto"]["laea"]?>
<?php
        }
?>
                                <br />
                                <img id="imagem_cadastrada_11" src="<?=$foto_laea_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row-fluid check_ajuste">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_ladp" value="sim" <?=(($checklist["foto"]["ladp"] == "sim") ? "checked" : "")?> />
                                <strong>Lateral Direita do Produto</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,12,'foto_laep');" name="foto_ladp_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_12"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_ladp_img"])){
        $anexosLadp = $amazonTC->getDocumentsByName($checklist["foto"]["foto_ladp_img"],null,$os)->url;

        if (filter_var($anexosLadp,FILTER_VALIDATE_URL)) {
            $foto_ladp_img      = $anexosLadp;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Lateral Direita do Produto: </strong><?=$checklist["foto"]["ladp"]?>
<?php
        }
?>
                                <br />
                                <img id="imagem_cadastrada_12" src="<?=$foto_ladp_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_teto" value="sim" <?=(($checklist["foto"]["teto"] == "sim") ? "checked" : "")?> />
                                <strong>Teto do Ambiente</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,13,'foto_teto');" name="foto_teto_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_13"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_teto_img"])){
        $anexosTeto = $amazonTC->getDocumentsByName($checklist["foto"]["foto_teto_img"],null,$os)->url;

        if (filter_var($anexosTeto,FILTER_VALIDATE_URL)) {
            $foto_teto_img      = $anexosTeto;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Teto do Ambiente: </strong><?=$checklist["foto"]["teto"]?>
<?php
        }
?>
                                <br />
                                <img id="imagem_cadastrada_13" src="<?=$foto_teto_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row-fluid">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_laep" value="sim" <?=(($checklist["foto"]["laep"] == "sim") ? "checked" : "")?> />
                                <strong>Lateral Esquerda do Produto</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,14,'foto_laep');" name="foto_laep_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_14"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_laep_img"])){
        $anexosLaep = $amazonTC->getDocumentsByName($checklist["foto"]["foto_laep_img"],null,$os)->url;

        if (filter_var($anexosLaep,FILTER_VALIDATE_URL)) {
            $foto_laep_img      = $anexosLaep;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Lateral Esquerda do Produto: </strong><?=$checklist["foto"]["laep"]?>
<?php
        }
?>
                                <br />
                                <img id="imagem_cadastrada_14" src="<?=$foto_laep_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_chao" value="sim" <?=(($checklist["foto"]["chao"] == "sim") ? "checked" : "")?> />
                                <strong>Ch&atilde;o do Ambiente</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,15,'foto_chao');" name="foto_chao_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_15"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_chao_img"])){
        $anexosChao = $amazonTC->getDocumentsByName($checklist["foto"]["foto_chao_img"],null,$os)->url;

        if (filter_var($anexosChao,FILTER_VALIDATE_URL)) {
            $foto_chao_img      = $anexosChao;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Ch&atilde;o do Ambiente: </strong><?=$checklist["foto"]["chao"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_15" src="<?=$foto_chao_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row-fluid">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_visp" value="sim" <?=(($checklist["foto"]["visp"] == "sim") ? "checked" : "")?> />
                                <strong>Vis&atilde;o Superior do Produto</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,16,'foto_visp');" name="foto_visp_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_16"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_visp_img"])){
        $anexosVisp = $amazonTC->getDocumentsByName($checklist["foto"]["foto_visp_img"],null,$os)->url;

        if (filter_var($anexosVisp,FILTER_VALIDATE_URL)) {
            $foto_visp_img      = $anexosVisp;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Vis&atilde;o Superior do Produto: </strong><?=$checklist["foto"]["visp"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_16" src="<?=$foto_visp_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_pessoa" value="sim" <?=(($checklist["foto"]["pessoa"] == "sim") ? "checked" : "")?> />
                                <strong>Pessoas Envolvidas</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,18,'foto_pessoa');" name="foto_pessoa_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_18"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_pessoa_img"])){
        $anexosPessoa = $amazonTC->getDocumentsByName($checklist["foto"]["foto_pessoa_img"],null,$os)->url;

        if (filter_var($anexosPessoa,FILTER_VALIDATE_URL)) {
            $foto_pessoa_img      = $anexosPessoa;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Pessoas Envolvidas: </strong><?=$checklist["foto"]["pessoa"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_18" src="<?=$foto_pessoa_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row-fluid">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_viip" value="sim" <?=(($checklist["foto"]["viip"] == "sim") ? "checked" : "")?> />
                                <strong>Vis&atilde;o Interna do Produto</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,19,'foto_viip');" name="foto_viip_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_19"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_viip_img"])){
        $anexosViip = $amazonTC->getDocumentsByName($checklist["foto"]["foto_viip_img"],null,$os)->url;

        if (filter_var($anexosViip,FILTER_VALIDATE_URL)) {
            $foto_viip_img      = $anexosViip;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Vis&atilde;o Interna do Produto: </strong><?=$checklist["foto"]["viip"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_19" src="<?=$foto_viip_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_danos" value="sim" <?=(($checklist["foto"]["danos"] == "sim") ? "checked" : "")?> />
                                <strong>Danos Materiais</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,20,'foto_danos');" name="foto_danos_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_20"></div>
<?
    }
    if(!empty($checklist["foto"]["foto_danos_img"])){
        $anexosDanos = $amazonTC->getDocumentsByName($checklist["foto"]["foto_danos_img"],null,$os)->url;

        if (filter_var($anexosDanos,FILTER_VALIDATE_URL)) {
            $foto_danos_img      = $anexosDanos;
        }
        if (strlen($imprimir) > 0) {
?>
                                <strong>Danos Materiais: </strong><?=$checklist["foto"]["danos"]?>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_20" src="<?=$foto_danos_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <!-- FIM REGISTRO FOTOGR&Aacute;FICO -->

            <div class="div_fieldset">
            <div class="row-fluid">
                <h4>PROPOSTA DE NEGOCIA&Ccedil;&Atilde;O</h4>
            </div>

            <div class="row-fluid">
                <div class="span4">
                    <div class="control-group">
                        <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="radio" name="negociacao_troca" value="troca" <?=(($checklist["negociacao"]["troca"] == "troca") ? "checked" : "")?> />
                            <strong>Troca do Produto</strong>
<?
    }else{
?>
                            <strong>Troca do Produto: </strong><?=($checklist["negociacao"]["troca"] == "troca") ? "Sim" : ""?>
<?
    }
?>
                        </label>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="radio" name="negociacao_troca" value="restituicao" <?=(($checklist["negociacao"]["troca"] == "restituicao") ? "checked" : "")?> />
                            <strong>Restitui&ccedil;&atilde;o</strong>
<?
    }else{
?>
                            <strong>Restitui&ccedil;&atilde;o: </strong><?=($checklist["negociacao"]["troca"] == "restituicao") ? "Sim" : ""?>
<?
    }
?>
                        </label>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="radio" name="negociacao_troca" value="danos" <?=(($checklist["negociacao"]["troca"] == "danos") ? "checked" : "")?> />
                            <strong>Danos Morais</strong>
<?
    }else{
?>
                            <strong>Danos Morais: </strong><?=($checklist["negociacao"]["troca"] == "danos") ? "Sim" : ""?>
<?
    }
?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row-fluid">
                <div class="span12">
                    <div class="control-group">
                        <strong>R$</strong>
<?
    if(strlen($imprimir) == 0){
?>
                        <input type="text" id="negociacao_valor" name="negociacao_valor" value="<?=$checklist['negociacao']['valor']?>" class="span3" />
<?
    }else{
?>
                            <span><?=$checklist["negociacao"]["valor"]?></span>
<?
    }
?>
                    </div>
                </div>
            </div>
            </div>

<?
        break;
        case 'far':
?>

                       <!-- REGISTRO FOTOGR&Aacute;FICO -->
            <div class="div_fieldset foto">
                <div class="row-fluid">
                    <h4>ANEXOS</h4>
                </div>

                <div class="row-fluid check_ajuste">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0 && empty($transAnexo['etiqueta'])){
?>
                                <input type="checkbox" name="foto_etiqueta" value="sim" <?=(($checklist["foto"]["etiqueta"] == "sim") ? "checked" : "")?> />
                                <strong>Etiqueta do Produto</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,21,'foto_etiqueta');" name="foto_etiqueta_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_21"></div>
<?
            }
            if(!empty($checklist["foto"]["foto_etiqueta_img"]) || !empty($transAnexo['etiqueta'])){
                if (empty($transAnexo['etiqueta'])) {
                    $anexosEtiquetaSerie = $amazonTC->getDocumentsByName($checklist["foto"]["foto_etiqueta_img"],null,$os)->url;

                    if (filter_var($anexosEtiquetaSerie,FILTER_VALIDATE_URL)) {
                        $foto_etiqueta_img      = $anexosEtiquetaSerie;
                    }
                } else {
                    $foto_etiqueta_img = $transAnexo['etiqueta'];
?>
                                <strong>Etiqueta do Produto</strong>
<?php

                }
                if(strlen($imprimir) > 0 && empty($transAnexo['etiqueta'])){

?>
                                <strong>Etiqueta do Produto: </strong><?=$checklist["foto"]["etiqueta"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_21" src="<?=$foto_etiqueta_img?>" width="200px" height="200px" border="0" />
<?
            }
?>

                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0 && empty($transAnexo['nota_fiscal'])){
?>
                                <input type="checkbox" name="foto_nf" value="sim" <?=(($checklist["foto"]["foto_nf"] == "sim") ? "checked" : "")?> />
                                <strong>NF</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,22,'foto_nf');" name="foto_nf_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_22"></div>
<?
            }
            if(!empty($checklist["foto"]["foto_nf_img"]) || !empty($transAnexo['nota_fiscal'])){
                if (empty($transAnexo['nota_fiscal'])) {
                    $anexosNfImg = $amazonTC->getDocumentsByName($checklist["foto"]["foto_nf_img"],null,$os)->url;

                    if (filter_var($anexosNfImg,FILTER_VALIDATE_URL)) {
                        $foto_nf_img      = $anexosNfImg;
                    }
                } else {

                    $foto_nf_img = $transAnexo['nota_fiscal'];
?>
                                <strong>NF</strong>
<?php

                }
                if(strlen($imprimir) > 0 && empty($transAnexo['nota_fiscal'])){
?>
                                <strong>NF: </strong><?=$checklist["foto"]["foto_nf"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_22" src="<?=$foto_nf_img?>" width="200px" height="200px" border="0" />
<?
            }
?>
                            </label>
                        </div>
                    </div>

                </div>
                <div class="row-fluid check_ajuste">
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_rg" value="sim" <?=(($checklist["foto"]["rg"] == "sim") ? "checked" : "")?> />
                                <strong>RG</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,23,'foto_rg');" name="foto_rg_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_23"></div>
<?
            }
            if(!empty($checklist["foto"]["foto_rg_img"])){

                $anexosRg = $amazonTC->getDocumentsByName($checklist["foto"]["foto_rg_img"],null,$os)->url;

                if (filter_var($anexosRg,FILTER_VALIDATE_URL)) {
                    $foto_rg_img      = $anexosRg;
                }
                if(strlen($imprimir) > 0){
?>
                                <strong>RG: </strong><?=$checklist["foto"]["rg"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_23" src="<?=$foto_rg_img?>" width="200px" height="200px" border="0" />
<?
            }
?>

                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_cpf" value="sim" <?=(($checklist["foto"]["cpf"] == "sim") ? "checked" : "")?> />
                                <strong>CPF</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,24,'foto_cpf');" name="foto_cpf_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_24"></div>
<?
            }
            if(!empty($checklist["foto"]["foto_cpf_img"])){
                $anexosCPF = $amazonTC->getDocumentsByName($checklist["foto"]["foto_cpf_img"],null,$os)->url;

                if (filter_var($anexosCPF,FILTER_VALIDATE_URL)) {
                    $foto_cpf_img      = $anexosCPF;
                }
                if(strlen($imprimir) > 0){
?>
                                <strong>CPF: </strong><?=$checklist["foto"]["cpf"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_24" src="<?=$foto_cpf_img?>" width="200px" height="200px" border="0" />
<?
            }
?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row-fluid check_ajuste">

                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_residencia" value="sim" <?=(($checklist["foto"]["residencia"] == "sim") ? "checked" : "")?> />
                                <strong>Comprovante Resid&ecirc;ncia</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,25,'foto_residencia');" name="foto_residencia_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_25"></div>
<?
            }
            if(!empty($checklist["foto"]["foto_residencia_img"])){
                $anexosResidencia = $amazonTC->getDocumentsByName($checklist["foto"]["foto_residencia_img"],null,$os)->url;

                if (filter_var($anexosResidencia,FILTER_VALIDATE_URL)) {
                    $foto_residencia_img      = $anexosResidencia;
                }
                if(strlen($imprimir) > 0){
?>
                                <strong>Comprovante Resid&ecirc;ncia: </strong><?=$checklist["foto"]["residencia"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_25" src="<?=$foto_residencia_img?>" width="200px" height="200px" border="0" />
<?
            }
?>
                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0 && empty($transAnexo['produto_1'])){
?>
                                <input type="checkbox" name="foto_produto1" value="sim" <?=(($checklist["foto"]["produto1"] == "sim") ? "checked" : "")?> />
                                <strong>Produto 1</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,26,'foto_produto1');" name="foto_produto1_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_26"></div>
<?
            }

            if(strlen($checklist["foto"]["foto_produto1_img"]) > 0 || strlen($transAnexo['produto_1']) > 0){

                if (strlen($checklist["foto"]["foto_produto1_img"]) > 0) {
                    $anexosProduto01 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_produto1_img"],null,$os)->url;

                    if (filter_var($anexosProduto01,FILTER_VALIDATE_URL)) {
                        $foto_produto1_img      = $anexosProduto01;
                    }
                } else {
                    $foto_produto1_img = $transAnexo['produto_1'];
?>
                    <strong>Produto 1</strong>
<?php
                }
                if(strlen($imprimir) > 0 && empty($transAnexo['produto_1'])){

?>
                                <strong>Produto 1: </strong><?=$checklist["foto"]["produto1"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_26" src="<?=$foto_produto1_img?>" width="200px" height="200px" border="0" />
<?
            }
?>

                            </label>
                        </div>
                    </div>
                </div>

                <div class="row-fluid check_ajuste">

                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0 && empty($transAnexo['produto_2'])){
?>
                                <input type="checkbox" name="foto_produto2" value="sim" <?=(($checklist["foto"]["produto2"] == "sim") ? "checked" : "")?> />
                                <strong>Produto 2</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,27,'foto_produto2');" name="foto_produto2_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_27"></div>
<?
            }
            if(!empty($checklist["foto"]["foto_produto2_img"]) || !empty($transAnexo['produto_2'])){
                if (empty($transAnexo['produto_2'])) {
                    $anexosProduto02 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_produto2_img"],null,$os)->url;

                    if (filter_var($anexosProduto01,FILTER_VALIDATE_URL)) {
                        $foto_produto2_img      = $anexosProduto02;
                    }
                } else {
                    $foto_produto2_img = $transAnexo['produto_2'];
?>
                                <strong>Produto 2</strong>
<?php
                }
                if(strlen($imprimir) > 0 && empty($transAnexo['produto_2'])){
?>
                                <strong>Produto 2: </strong><?=$checklist["foto"]["produto2"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_27" src="<?=$foto_produto2_img?>" width="200px" height="200px" border="0" />
<?
            }
?>
                            </label>
                        </div>

                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0 && empty($transAnexo['produto_3'])){
?>
                                <input type="checkbox" name="foto_produto3" value="sim" <?=(($checklist["foto"]["produto3"] == "sim") ? "checked" : "")?> />
                                <strong>Produto 3</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,28,'foto_produto3');" name="foto_produto3_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_28"></div>
<?
            }
            if(!empty($checklist["foto"]["foto_produto3_img"]) || !empty($transAnexo['produto_3'])){
                if (empty($transAnexo['produto_3'])) {
                    $anexosProduto03 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_produto3_img"],null,$os)->url;

                    if (filter_var($anexosProduto01,FILTER_VALIDATE_URL)) {
                        $foto_produto3_img      = $anexosProduto03;
                    }
                } else {
                    $foto_produto3_img  = $transAnexo['produto_3'];
?>
                                <strong>Produto 3</strong>
<?php
                }
                if(strlen($imprimir) > 0 && empty($transAnexo['produto_3'])){

?>
                                <strong>Produto 3: </strong><?=$checklist["foto"]["produto3"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_28" src="<?=$foto_produto3_img?>" width="200px" height="200px" border="0" />
<?
            }
?>

                            </label>
                        </div>
                    </div>
                </div>

                <div class="row-fluid check_ajuste">

                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_aceite1" value="sim" <?=(($checklist["foto"]["aceite1"] == "sim") ? "checked" : "")?> />
                                <strong>Termo de Aceite 1</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,29,'foto_aceite1');" name="foto_aceite1_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_29"></div>
<?
            }
            if(!empty($checklist["foto"]["foto_aceite1_img"])){
                $anexosProduto04 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_aceite1_img"],null,$os)->url;

                if (filter_var($anexosProduto04,FILTER_VALIDATE_URL)) {
                    $foto_aceite1_img      = $anexosProduto04;
                }
                if(strlen($imprimir) > 0){
?>
                                <strong>Termo de Aceite 1: </strong><?=$checklist["foto"]["aceite1"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_29" src="<?=$foto_aceite1_img?>" width="200px" height="200px" border="0" />
<?
            }
?>
                            </label>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="checkbox">
<?
            if(strlen($imprimir) == 0){
?>
                                <input type="checkbox" name="foto_aceite2" value="sim" <?=(($checklist["foto"]["aceite2"] == "sim") ? "checked" : "")?> />
                                <strong>Termo de Aceite 2</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,30,'foto_aceite2');" name="foto_aceite2_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_30"></div>
<?
            }
            if(!empty($checklist["foto"]["foto_aceite2_img"])){
                $anexosTermo01 = $amazonTC->getDocumentsByName($checklist["foto"]["foto_aceite2_img"],null,$os)->url;

                if (filter_var($anexosTermo01,FILTER_VALIDATE_URL)) {
                    $foto_aceite2_img      = $anexosTermo01;
                }
                if(strlen($imprimir) > 0){
?>
                                <strong>Termo de Aceite 2: </strong><?=$checklist["foto"]["aceite2"]?>
<?
                }
?>
                                <br />
                                <img id="imagem_cadastrada_30" src="<?=$foto_aceite2_img?>" width="200px" height="200px" border="0" />
<?
    }
?>

                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- FIM REGISTRO FOTOGR&Aacute;FICO -->

            <!-- DADOS BANC&Aacute;RIOS -->
            <div class="div_fieldset">
            <div class="row-fluid">
                <h4>DADOS BANC&Aacute;RIOS A SER SOLICITADO AO TITULAR QUE RECEBER&Aacute; A RESTITUI&Ccedil;&Atilde;O</h4>
            </div>
            <div class="row-fluid">
                <div class="span6">
                    <div class="control-group">
                        <label for='banco_titular'>
                            <strong>Nome:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="banco_titular" name="banco_titular" value="<?=$checklist['banco']['titular']?>" class="span12" />
<?
    }else{
?>
                            <br />
                            <span><?=$checklist["banco"]["titular"]?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
                <div class="span3">
                    <div class="control-group">
                        <label for='banco_data_nascimento'>
                            <strong>Data Nascimento:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="banco_data_nascimento" name="banco_data_nascimento" value="<?=$checklist['banco']['data_nascimento']?>" class="span12" />
<?
    }else{
?>
                            <br />
                            <span><?=$checklist['banco']['data_nascimento']?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
                <div class="span3">
                    <div class="control-group">
                        <label for='banco_teletitular'>
                            <strong>Telefone:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="banco_teletitular" name="banco_teletitular" value="<?=$checklist['banco']['teletitular']?>" class="span12" />
<?
    }else{
?>
                            <br />
                            <span><?=$checklist["banco"]["teletitular"]?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span4">
                    <div class="control-group">
                        <label for='banco_documento'>
                            <strong>CPF / CNPJ:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="banco_documento" name="banco_documento" value="<?=$checklist['banco']['documento']?>" class="span12" />
<?
    }else{
?>
                            <br />
                            <span><?=$checklist["banco"]["documento"]?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label for='banco_documento'>
                            <strong>RG:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="banco_rg" name="banco_rg" value="<?=$checklist['banco']['rg']?>" class="span12" />
<?
    }else{
?>
                            <br />
                            <span><?=$checklist["banco"]["rg"]?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
<?
    if(strlen($imprimir) == 0){
?>
                <div class="span4">
                    <div class="control-group">
                        <label class="checkbox">
                            <input type="radio" name="banco_tipo" value="corrente" <?=(($checklist["banco"]["tipo"] == "corrente") ? "checked" : "")?> />
                            <strong>Conta Corrente</strong>
                        </label>
                    <!--</div>
                </div>
                <div class="span4">
                    <div class="control-group">-->
                    <br />
                        <label class="checkbox">
                            <input type="radio" name="banco_tipo" value="poupanca" <?=(($checklist["banco"]["tipo"] == "poupanca") ? "checked" : "")?> />
                            <strong>Conta Poupan&ccedil;a</strong>
                        </label>
                    </div>
                </div>
<?
    }else{
?>
                <div class="span8">
                    <div class="control-group">
                        <strong>Conta: </strong>
                        <br />
                        <span><?=$checklist["banco"]["tipo"]?></span>
                    </div>
                </div>
<?
    }
?>
            </div>
            <div class="row-fluid">
                <div class="span4">
                    <div class="control-group">
                        <label for='banco_banco'>
                            <strong>Banco:</strong>
<?
    if(strlen($imprimir) == 0){
        $sqlBanco = "
            SELECT  tbl_banco.codigo || ' - ' || tbl_banco.nome AS nome_banco,
                    tbl_banco.banco
            FROM    tbl_banco
      ORDER BY      nome_banco
        ";
        $resBanco = pg_query($con,$sqlBanco);
        $dadosBanco = pg_fetch_all($resBanco);
?>
                            <!--<input type="text" id="banco_banco" name="banco_banco" value="" class="span12" />-->
                            <select id="banco_banco" name="banco_banco">
                                <option value="">SELECIONE</option>
<?
        foreach($dadosBanco as $codigo=>$banco){
                $selected = ($banco["banco"] == $checklistImprime["banco"]["banco"]) ? "SELECTED" : "";
?>
                                <option value="<?=$banco['banco']?>" <?php echo $selected; ?>><?=utf8_encode($banco['nome_banco'])?></option>
<?
        }
?>
                            </select>
<?
    }else{
        $sqlBanco = "
            SELECT  tbl_banco.codigo || ' - ' || tbl_banco.nome AS nome_banco
            FROM    tbl_banco
            WHERE   banco = ".$checklist['banco']['banco']
        ;
        $resBanco = pg_query($con,$sqlBanco);
        $checklist['banco']['nome_banco'] = pg_fetch_result($resBanco,0,nome_banco);
?>
                            <br />
                            <span><?=$checklist["banco"]["nome_banco"]?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label for='banco_agencia'>
                            <strong>Ag&ecirc;ncia:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="banco_agencia" name="banco_agencia" value="<?=$checklist['banco']['agencia']?>" class="span12" />
<?
    }else{
?>
                            <br />
                            <span><?=$checklist["banco"]["agencia"]?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label for='banco_conta'>
                            <strong>Conta:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="banco_conta" name="banco_conta" value="<?=$checklist['banco']['conta']?>" class="span12" />
<?
    }else{
?>
                            <br />
                            <span><?=$checklist["banco"]["conta"]?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span3">
                    <div class="control-group">
                        <label for='negociacao_valor'>
                            <strong>R$</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="negociacao_valor" name="negociacao_valor" value="<?=$checklist['negociacao']['valor']?>" class="span12" />
<?
    }else{
?>
                            <span><?=$checklist["negociacao"]["valor"]?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
            </div>
            </div>


            <!-- FIM DADOS BANC&Aacute;RIOS -->



<?
        break;
    }
if(in_array($laudo,array("fat","far","fatrev"))){
?>
            <!-- ANEXOS DO LAUDO-->
            <div class="div_fieldset">
                <div class="row-fluid">
                    <h4>ANEXO DO LAUDO</h4>
                </div>
                <div class="row-fluid">
                    <div class="span12">
                        <div class="control-group">
                            <label class="checkbox">
<?
    if(strlen($imprimir) == 0){
?>
                                <strong>Coloque um anexo do comprovante do produto</strong>
                                <input type="file" onchange="carregaPreviewUpload(this,31);" name="anexo_img" class="filestyle" data-size="sm" data-input="false" data-buttonText="Anexar Arquivo" data-buttonName="btn-primary"/>
                                <div id="imagem_preview_31"></div>
                                <br /> <br />
<?
    }

    if(!empty($checklist["anexo_img"]) || ($login_fabrica == 30 && $laudo == "fatrev" && !empty($checklist["foto"]["anexo_img"]))){
        if ($login_fabrica == 30 && $laudo == "fatrev" && !empty($checklist["foto"]["anexo_img"])) {
            $anexos = $amazonTC->getDocumentsByName($checklist["foto"]["anexo_img"],null,$os)->url;            
        } else {
            $anexos = $amazonTC->getDocumentsByName($checklist["anexo_img"],null,$os)->url;
        }

        if (filter_var($anexos,FILTER_VALIDATE_URL)) {
            $anexo_img      = $anexos;
        }

        if(strlen($imprimir) > 0){
?>
                                <strong>Coloque um anexo do comprovante do produto: </strong>
<?
        }
?>
                                <br />
                                <img id="imagem_cadastrada_31" src="<?=$anexo_img?>" width="200px" height="200px" border="0" />
<?
    }
?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <!-- FIM ANEXOS DO LAUDO-->
<?php
    }
?>
            <!-- ESPA&Ccedil;&Otilde; RESERVADO AO SAC -->
            <div class="div_fieldset">
            <div class="row-fluid">
                <h4>ESPA&Ccedil;O RESERVADO AO SAC</h4>
            </div>
            <div class="row-fluid">
                <div class="span6">
                    <div class="control-group">
                        <label for='sac_backoffice'>
                            <strong>Backoffice Respons&aacute;vel:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="sac_backoffice" name="sac_backoffice" value="<?=$checklist['sac']['backoffice']?>" class="span12" />
<?
    }else{
?>
                            <br />
                            <span><?=$checklist['sac']['backoffice']?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
                <div class="span6">
                    <div class="control-group">
                        <label for='sac_protocolo'>
                            <strong>Protocolo:</strong>
<?
    if(strlen($imprimir) == 0){
?>
                            <input type="text" id="sac_protocolo" name="sac_protocolo" value="<?=$checklist['sac']['protocolo']?>" class="span12" />
<?
    }else{
?>
                            <br />
                            <span><?=$checklist['sac']['protocolo']?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
            </div>
            </div>
            <!-- FIM ESPA&Ccedil;&Otilde; RESERVADO AO SAC -->
        <br />
<?php

    if((strlen($_REQUEST['alterar']) > 0 && strlen($_GET['liberar']) == 0 && $aprova_laudo == 't' && !verifica_correcao($os)) || isset($_GET["imprimir"]) ){
?>
        <div class="div_fieldset">
            <div class="row-fluid">
                <h4>CASO ESSE LAUDO SEJA REJEITADO, COLOQUE O MOTIVO</h4>
            </div>
            <div class="row-fluid">
                <div class="span6">
                    <div class="control-group">
                        <label for='motivo'>
                            <strong>Motivo:</strong>
    <?php
    if(strlen($imprimir) == 0){
    ?>
        <input type="text" id="motivo" name="motivo" value="<?=$motivo?>" class="span12" />

        <label>
            <input type="checkbox" id="correcao" name="correcao" value="t" />
            <strong>Solicitar Corre&ccedil;&atilde;o</strong>
        </label>
        <br />
    <?php
    }else{

        $motivo = "";
        $cont = 1;

        if(count($checklist["motivo"]) > 0){
            foreach ($checklist["motivo"] as $m) {
                if(strlen(trim($m)) > 0){
                    $motivo .= "Intera&ccedil;&atilde;o {$cont} : ".$m."<br />";
                    $cont++;
                }
            }
        }else{
            $motivo = "Nenhum motivo informado.";
        }

?>
                            <br />
                            <span><?=$motivo?></span>
<?
    }
?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
<?
    }
?>
    </div>
        <div class="btn-toolbar tac" style="margin-bottom: 0px;">
        <input type="hidden" name="liberar" value="<?=$_GET['liberar']?>" />
        <input type="hidden" name="alterar" value="<?=$_GET['alterar']?>" />
<?php

    if (!$_GET["imprimir"]) {

        $sql_status = "select status_os from tbl_os_status where os = $os and status_os in(193, 194)";
        //echo nl2br($sql_status);
        $res_status = pg_query($con, $sql_status);
        if(pg_num_rows($res_status)==0){
            if($login_fabrica == 30){
              echo "<a href='os_cadastro.php?os=$os&osacao=trocar' class='btn'>Alterar</a>";
            }
         }

        if(strlen($_GET['liberar']) == 0){

            $txtBtn = (!verifica_correcao($os)) ? 'Gravar' : 'Corrigir';
?>
            <input class='btn btn-primary' type="submit"  name="gravar" value="<?= $txtBtn ?>" id="gravar" />
<?
        }

            if(strlen($_REQUEST['alterar']) > 0 && strlen($_GET['liberar']) == 0 && $aprova_laudo == 't'){


                if (!verifica_correcao($os)) { ?>
                    <input class='btn btn-success' type="submit"  name="btn_aprovar" value="Aprovar" id="aprovar" />
                    <input class='btn btn-danger'  type="submit"  name="btn_reprovar" value="Reprovar" />
                <?php
                } else { ?>
                    Aguardando correção
                <?php
                }

            }else if(strlen($_GET['liberar']) > 0 && $aprova_laudo){
    ?>
                <input class='btn btn-info'    type="submit"  name="btn_liberar"     value="Liberar" />
                <input class='btn btn-warning' type="submit"  name="btn_nao_liberar" value="N&atilde;o Liberar" />
    <?
            }

    ?>



        </div>
<?
    } else {

        $html = stripslashes(ob_get_contents());
        $caminho = "xls/".$completo;

        $titleR = str_replace('&Aacute;','A',$title);

		$bootstrap = $bootstrap ? : '';

        //$pdf = new \Mpdf\Mpdf;
        $pdf = new mPDF;
        //$pdf->charset_in = 'windows-1252';
        //Comentei a linha pois esta apresentando vários erro de codificação. 
        $pdf->allow_html_optional_endtags = true;
        $pdf->SetTitle($titleR);
        $pdf->SetDisplayMode('fullpage');
        $pdf->WriteHTML($bootstrap,1);

        $pdf->WriteHTML($html);
        $pdf->Output($caminho,'F');

        ob_end_flush();
        echo "<script>window.open('$caminho','_self');</script>";
    }

?>
        <br />
    </form>
</div>
</body>
</html>
