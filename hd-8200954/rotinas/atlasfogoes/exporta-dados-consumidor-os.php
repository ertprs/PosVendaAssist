<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

date_default_timezone_set('America/Sao_Paulo');

$fabrica = 74;
$data = new DateTime();
$data->sub(new DateInterval('P01D'));
$ontem = $data->format('Y-m-d');
$hoje = date('Y-m-d');

$outdir = '/tmp/atlas';

$outfile = $outdir . '/dados_consumidor_os-' . $ontem . '.txt';

$sql = "
    SELECT 
		consumidor_cpf,
		campos_adicionais,
		consumidor_nome,
		consumidor_fone,
		consumidor_cep,
		consumidor_endereco,
		consumidor_numero,
		consumidor_complemento,
		consumidor_bairro,
		consumidor_estado,
		consumidor_cidade,
		consumidor_email
    FROM tbl_os
    LEFT JOIN tbl_os_campo_extra USING(os, fabrica)
    WHERE fabrica = $fabrica
	AND	length(consumidor_nome) > 0 
    AND data_digitacao BETWEEN '$ontem 00:00'::timestamp - interval '6 day' AND '$hoje 23:59'";

$qry = pg_query($con, $sql);

if (pg_num_rows($qry)) {
    $res = fopen($outfile, 'w');

    while ($fetch = pg_fetch_assoc($qry)) {
		$campos_adicionais = json_decode($fetch["campos_adicionais"], true);

		$data_nascimento = '';

		if (!array_key_exists("data_nascimento", $campos_adicionais)) {
			continue;
		}

		$dn = explode("/", $campos_adicionais["data_nascimento"]);

		$d = (int) $dn[0];
		$m = (int) $dn[1];
		$y = (int) $dn[2];

		if (!checkdate($m, $d, $y)) {
			continue;
		}

		$data_nascimento = "{$dn[2]}-{$dn[1]}-{$dn[0]}";

		$line = $fetch["consumidor_cpf"] . "\t";
		$line .= $data_nascimento . "\t";
		$line .= trim($fetch["consumidor_nome"]) . "\t";
		$line .= trim($fetch["consumidor_cidade"]) . "\t";
		$line .= trim($fetch["consumidor_cep"]) . "\t";
		$line .= trim($fetch["consumidor_estado"]) . "\t";
		$line .= trim($fetch["consumidor_endereco"]) . "\t";
		$line .= trim($fetch["consumidor_numero"]) . "\t";
		$line .= trim($fetch["consumidor_complemento"]) . "\t";
		$line .= trim($fetch["consumidor_bairro"]) . "\t";
		$line .= trim($fetch["consumidor_fone"]) . "\t";
		$line .= trim($fetch["consumidor_email"]) . "\n";

		fwrite($res, $line);
    }

    fclose($res);

    if (filesize($outfile) > 0) {
        $destino = '/home/atlas/telecontrol-atlas/dados_consumidor_os-' . $ontem . '.txt';
        copy($outfile, $destino);
    }

}

