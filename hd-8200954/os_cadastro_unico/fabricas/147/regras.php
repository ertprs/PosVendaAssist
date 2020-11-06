<?php
// complemento, cpf, telefone liberar dois campos (apenas um obrigat�rio), 
// endere�o consumidor, email, endere�o da revenda tamb�m n�o ser� obrigat�rio, apar�ncia e acess�rios. Liberar 4 anexos n�o obrigat�rios.

$regras["consumidor|cep"]["obrigatorio"] = false;
$regras["consumidor|bairro"]["obrigatorio"] = false;
$regras["consumidor|estado"]["obrigatorio"] = false;
$regras["consumidor|cidade"]["obrigatorio"] = false;
$regras["consumidor|endereco"]["obrigatorio"] = false;
$regras["consumidor|numero"]["obrigatorio"] = false;
$regras["revenda|cep"]["obrigatorio"] = false;
$regras["revenda|bairro"]["obrigatorio"] = false;
$regras["revenda|endereco"]["obrigatorio"] = false;
$regras["revenda|numero"]["obrigatorio"] = false;
$regras["os|data_abertura"]["function"] = array("valida_data_30");

$valida_anexo= "";

/**
 * Fun��o para valida��o de data de abertura 
 */
function valida_data_30() {
    global $campos, $os;

    $data_abertura = $campos["os"]["data_abertura"];

    if (!empty($data_abertura) && empty($os)) {
        list($dia, $mes, $ano) = explode("/", $data_abertura);

        if (!checkdate($mes, $dia, $ano)) {
            throw new Exception("Data de abertura inv�lida");
        } else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 30 days")) {
            throw new Exception("Data de abertura n�o pode ser anterior a 30 dias");
        }
    }
}

function auditoria_os_fabricante_hitachi(){
    global $con, $login_fabrica, $os, $campos;

    $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $sqlStatus = "INSERT INTO tbl_os_status (os, status_os, observacao) VALUES ({$os}, 20, 'OS em an�lise pelo fabricante')";
        $resStatus = pg_query($con, $sqlStatus);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao gravar auditoria");
        } 
    }
}

$auditorias = array(
    "auditoria_os_fabricante_hitachi",
    "auditoria_os_reincidente",
    "auditoria_peca_critica",
    "auditoria_troca_obrigatoria",
    "auditoria_pecas_excedentes"
);

?>

