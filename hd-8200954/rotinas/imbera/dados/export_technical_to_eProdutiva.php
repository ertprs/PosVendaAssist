<?php

include dirname(__FILE__) . '/../../../dbconfig.php';
include dirname(__FILE__) . '/../../../includes/dbconnect-inc.php';

global $fabrica ;
$fabrica = 158;

//Utilizado para a API telecontrol.eprodutiva.com.br/api

//chave teste persys
#$authorizationKey = '4716427000141-dc3442c4774e4edc44dfcc7bf4d90447'; 

#chave producao persys

$authorizationKey = '12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9';
$sql = "
	SELECT t.tecnico, pf.codigo_posto, p.nome, p.nome_fantasia, p.cnpj, p.ie, pf.contato_email, 9 as departamento, 9 as unidade
	FROM tbl_posto_fabrica pf
	INNER JOIN tbl_posto p ON p.posto = pf.posto
	INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = $fabrica AND tp.tecnico_proprio IS TRUE
	INNER JOIN tbl_tecnico t ON t.posto = pf.posto AND t.fabrica = $fabrica
	WHERE pf.fabrica = $fabrica
";
$res = pg_query($con, $sql);

$i=0;
while($row = pg_fetch_assoc($res)){
    $i++;

    $json = array(
	"nomeUsuario" => $row["codigo_posto"],
	"razaoNome" => utf8_encode($row["nome"]),
	"fantasiaSobrenome" => utf8_encode($row["nome_fantasia"]),
	"cnpjCpf" => $row["cnpj"],
	"ieRg" => $row["ie"],
	"email" => $row["contato_email"],
	"departamento" => array(
		"id" => $row["departamento"],
		"unidade" => array(
			"id" => $row["unidade"]
		)
	),
	"externalAuthorization" => true
    );
   
    echo $json = json_encode($json);
    $codigo = postData($json,$authorizationKey);

    if ($codigo != false) {
        $up = "UPDATE tbl_tecnico SET codigo_externo = '$codigo' WHERE tecnico = {$row['tecnico']}";
	$rup = pg_query($con, $up);

	if (pg_affected_rows($rup) == 1) {
            echo 'OK' . $row['codigo_posto'] . " - " . $row["nome"] . "\n";
        } else {
	    echo 'ERRO' . $row['codigo_posto'] . " - " . $row["nome"] . "\n";
        }
    } else {
        echo 'ERRO' . $row['codigo_posto'] . " - " . $row["nome"] . "\n";
    }
}

function postData($json, $authKey){
    $url = 'http://telecontrol.eprodutiva.com.br/api/agente';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorizationv2: ".$authKey,
    ));

    $result = curl_exec($ch);

    if(!$result){
        echo '>>>>>>>>>> CURL ERROR: (' . $result . ' -> ' . $json. ')' . "\n";
    }

    curl_close($ch);

    $result = validateResponseReturningArray($result, $json);

    if (!$result["codigo"]) {
        return false;
    } else {
        return $result["codigo"];
    }
}

function validateResponseReturningArray($curlResult, $requestParams){
    $arrResult = json_decode($curlResult, true);
    if(array_key_exists('error', $arrResult)){
        echo '>>>>>>>>>> Response: (' . $curlResult . ' -> ' . $requestParams . ')' . "\n";

    }
    return $arrResult;
}
echo 'Finish:#' . $i .'Records';
