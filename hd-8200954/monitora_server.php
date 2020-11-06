<?php

/**
 * INSTRUCOES DE USO NA LINHA DE COMANDO
 *
 * php /var/www/assist/www/monitora_server.php hd
 * php /var/www/assist/www/monitora_server.php uptime
 * php /var/www/assist/www/monitora_server.php externo 
 *
 * php /var/www/assist/www/monitora_server.php banco
 * php /var/www/assist/www/monitora_server.php venom
 *
 */

// CONFIG's
$conf['disco']   = 90;
$conf['load']    = 40;
$conf['time']    = 10;//em minutos
//$conf['contato'] = array('helpdesk@telecontrol.com.br', 'waldir@telecontrol.com.br', 'boaz@telecontrol.com.br', 'marisa.silvana@telecontrol.com.br');
$conf['contato'] = array('andreus@telecontrol.com.br', 'boaz@telecontrol.com.br');
$conf['site']    = array('http://www.uol.com.br','http://www.terra.com.br','http://www.cnn.com','http://www.google.com','http://www.softlayer.com');

require_once('dbconfig.php');
require_once('includes/dbconnect-inc.php');

function espaco_disco() {

    global $conf;

    ob_start();

    system('df');//pega espaço em disco

    $str = ob_get_contents();
    ob_end_clean();

    $pos  = strpos($str, '/dev/xvda2');
    $str2 = substr($str, $pos, 100);
    $perc = substr($str2, strpos($str2, "%")-3, 3);

    if ($perc >= $conf['disco']) {

        $msg = 'O servidor está cheio, está com ' . $perc . '% de sua capacidade!';

        for ($i = 0; $i < count($conf['contato']); $i++) {

            mail($conf['contato'][$i], 'MONITORAMENTO SERVIDOR - ATENCAO', $msg);

        }

    }

}

function uptime() {

    global $conf;

    ob_start();

    system('uptime');//monitora processamento

    $str = ob_get_contents();
    ob_end_clean();

    $pos  = strpos($str, 'load average:');
    $str2 = substr($str, $pos, strlen($str) - $pos);
    $str2 = trim(str_replace('load average:', '', $str2));

    $vet = explode(',',$str2);//pega ultima posição que contem a media

    if ($vet[2] >= $conf['load']) {

        $msg = 'O servidor está com sua capcidade de processamento em ' . $conf['load'] . '%!';

        for ($i = 0; $i < count($conf['contato']); $i++) {

            mail($conf['contato'][$i], 'MONITORAMENTO SERVIDOR - ATENCAO', $msg);

        }

    }

}

function monitora_venom() {

    global $conf;

    $file = fopen('/var/www/telecontrol/www/sysmon.txt', 'w');
    fwrite($file, date('Y-m-d H:i'));
    fclose($file);
    
    $arq = file_get_contents('http://venom.telecontrol.com.br/sysmon.txt');

    if (strlen($arq) > 0) {
    
        if (strtotime($arq) + ($conf['time'] * 60) < strtotime(date('Y-m-d H:s'))) {
        
            $msg = 'O servidor não está respondendo a mais de ' . $conf['time'] . ' minutos!';

            for ($i = 0; $i < count($conf['contato']); $i++) {

                mail($conf['contato'][$i], 'MONITORAMENTO SERVIDOR - ATENCAO', $msg);

            }
        
        }
    
    } else {
    
        $msg = 'Não foi possível conectar ao servidor não está respondendo a mais de ' . $conf['time'] . ' minutos!';

        for ($i = 0; $i < count($conf['contato']); $i++) {

            mail($conf['contato'][$i], 'MONITORAMENTO SERVIDOR - ATENCAO', $msg);

        }   
    
    }

}

function rede_externa() {
    
    global $conf;

	$vet = array();

    for ($i = 0; $i < count($conf['site']); $i++) {

    	$arq = @file_get_contents($conf['site'][$i]);

    	if (strlen($arq) == 0) {
            $vet[$x++] = $conf['site'][$i];
        }

    }

    if ($x > 0) {

        if ($x == count($conf['site'])) {
			$msg = 'Nao foi possivel acessar nenhum site, verique a conexao com a internet!';
        } else {
	    	$msg = 'Apenas ' . count($vet) . ' dos ' . count($conf['site']) . ' sites foram acessados, os sites não acessados foram: '.implode(', ',$vet);
		}

        for ($i = 0; $i < count($conf['contato']); $i++) {

            mail($conf['contato'][$i], 'MONITORAMENTO SERVIDOR - ATENCAO', $msg);

        }

	}

}

function monitora_banco() {

	global $con;

	$sql = "select now() - query_start as time, procpid as pid, current_query as query from pg_catalog.pg_stat_activity where  now() - query_start > '00:10:00' order by  now()-query_start desc;";
	$res = pg_query($con, $sql);
	$tot = pg_num_rows($res);
	
	for ($i = 0; $i < $tot; $i++) {

		$vet[] = implode(' - ',pg_fetch_assoc($res));

	}

	if (count($vet)) {

		for ($i = 0; $i < count($conf['contato']); $i++) {

			mail($conf['contato'][$i], 'MONITORAMENTO SERVIDOR - ATENCAO', implode("\n", $vet));

		}

	}

}

if ($argv[1] == 'hd') {
    espaco_disco();
}

if ($argv[1] == 'uptime') {
    uptime();
}

if ($argv[1] == 'venom') {
    monitora_venom();
}

if ($argv[1] == 'externo') {
    rede_externa();
}

if ($argv[1] == 'completo') {
    
    global $conf;

    espaco_disco();
    uptime();
    monitora_venom();
	monitora_banco();
    //rede_externa();

}

if ($argv[1] == 'banco') {
	monitora_banco();
}

?>
