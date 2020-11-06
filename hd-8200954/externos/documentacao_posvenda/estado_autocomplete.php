<?php
$array_estados = array("AC" => "Acre", "AL" => "Alagoas", "AM" => "Amazonas", "AP" => "Amapá", "BA" => "Bahia", "CE" => "Ceará", "DF" => "Distrito Federal", "ES" => "Espírito Santo", "GO" => "Goiás", "MA" => "Maranhão", "MG" => "Minas Gerais", "MS" => "Mato Grosso do Sul", "MT" => "Mato Grosso", "PA" => "Pará", "PB" => "Paraíba","PE" => "Pernambuco", "PI" => "Piauí", "PR" => "Paraná","RJ" => "Rio de Janeiro",  "RN" => "Rio Grande do Norte","RO"=>"Rondônia","RR" => "Roraima", "RS" => "Rio Grande do Sul","SC" => "Santa Catarina","SE" => "Sergipe", "SP" => "São Paulo", "TO" => "Tocantins");

$term = trim($_GET["term"]);

$array_pesquisados = array();

foreach ($array_estados as $sigla => $estado) {
	preg_match("/".strtoupper($term)."/", strtoupper($sigla), $s);
	preg_match("/".strtoupper($term)."/", strtoupper($estado), $e);
	
    if (count($s) > 0 || count($e) > 0) {
        $array_pesquisados[] = array("sigla" => $sigla, "estado" => utf8_encode($estado));
    }
}

echo json_encode($array_pesquisados);
exit;
?>