<?php

$regras["revenda_cnpj"]["function"] = ["valida_revenda_cnpj_mq"];

function valida_revenda_cnpj_mq() {
    global $con, $campos;

    $cpf = preg_replace("/\D/", "", $campos["revenda_cnpj"]);

    if (strlen($cpf) > 0) {
        $sql = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("CPF/CNPJ $cpf é inválido");
        }
    }
}

function grava_os_revenda_fabrica()
{
    global $campos;

    $revenda_email = (!empty($campos["revenda"]["email"]))    ? $campos["revenda"]["email"]    : $campos["revenda_email"];
    $revenda_fone  = (!empty($campos["revenda"]["telefone"])) ? $campos["revenda"]["telefone"] : $campos["revenda_fone"];

    $campos_extra["revenda_email"] = (!empty($revenda_email)) ? $revenda_email : "null";
    $campos_extra["revenda_fone"]  = (!empty($revenda_fone))  ? $revenda_fone  : "null";

 $campos["revenda_cep"] = preg_replace("/[\-]/", "", $campos["revenda_cep"]);
        $campos["revenda_cnpj"] = preg_replace("/\D/", "", $campos["revenda_cnpj"]);

        $campos["revenda_nome"] = str_replace("'", " ", $campos["revenda_nome"]);
        $campos["revenda_bairro"] = str_replace("'", " ", $campos["revenda_bairro"]);
        $campos["revenda_endereco"] = str_replace("'", " ", $campos["revenda_endereco"]);
        $campos["revenda_complemento"] = str_replace("'", " ", $campos["revenda_complemento"]);
        
        $array_dados = array(
            "consumidor_nome" => "'".$campos["revenda_nome"]."'",
            "consumidor_cpf" => "'".$campos["revenda_cnpj"]."'",
            "consumidor_cep" => "'".$campos["revenda_cep"]."'",
            "consumidor_estado" => "'".$campos["revenda_estado"]."'",
            "consumidor_cidade" => "'".$campos["revenda_cidade"]."'",
            "consumidor_bairro" => "'".$campos["revenda_bairro"]."'",
            "consumidor_endereco" => "'".$campos["revenda_endereco"]."'",
            "consumidor_numero" => "'".$campos["revenda_numero"]."'",
            "consumidor_complemento" => "'".$campos["revenda_complemento"]."'",
            "consumidor_fone" => "'".$campos["revenda_fone"]."'",
            "consumidor_email" => "'".$campos["revenda_email"]."'",
            "consumidor_revenda" => "'".$campos["consumidor_revenda"]."'",
        );


	$json_campos_extra             = json_encode($campos_extra);
    $array_dados["campos_extra"]   = "'".$json_campos_extra."'";

    return $array_dados;
}


function grava_os_fabrica() {
    global $campos;
    
        $campos["revenda_cep"] = preg_replace("/[\-]/", "", $campos["revenda_cep"]);
        
        $dados = array(
            "consumidor_nome" => "'".$campos["revenda_nome"]."'",
            "consumidor_cpf" => "'".$campos["revenda_cnpj"]."'",
            "consumidor_cep" => "'".$campos["revenda_cep"]."'",
            "consumidor_estado" => "'".$campos["revenda_estado"]."'",
            "consumidor_cidade" => "'".$campos["revenda_cidade"]."'",
            "consumidor_bairro" => "'".$campos["revenda_bairro"]."'",
            "consumidor_endereco" => "'".$campos["revenda_endereco"]."'",
            "consumidor_numero" => "'".$campos["revenda_numero"]."'",
            "consumidor_complemento" => "'".$campos["revenda_complemento"]."'",
            "consumidor_fone" => "'".$campos["revenda_fone"]."'",
            "consumidor_email" => "'".$campos["revenda_email"]."'",
            "consumidor_celular" => "'".$campos["revenda_celular"]."'",
        );

    if ($campos["consumidor_revenda"] == "R"){
        $campos["revenda"] = "null";
        unset($campos["revenda_nome"]);
        unset($campos["revenda_cnpj"]);
    }
    return $dados;
}
