<?php
include_once dirname(__FILE__) . '/../../dbconfig.php';
include_once dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include_once dirname(__FILE__) . '/../funcoes.php';

ini_set("display_errors", 1);
error_reporting(E_ALL);

$configuracao = array(
    'login_fabrica'     => 1,
    'pasta'             => '/tmp/blackedecker/nao_bkp',
    'tipo_arquivos'     => array('SSP', 'ACE', 'GAR', 'GRP'),
    'ano_pesquisa'      => false, //False = Ano atual
    'mes_pesquisa'      => 'oct', //Padr�o 3 caracteres: ago,set,out,nov,dez,
    'consulta_data'     => '2017-10-05 00:00:00',
    'listagem_arquivos' => array(),
    'log_pedido'        => '/tmp/blackedecker/log-pedidos.txt'
);

extract($configuracao);

/* LISTA OS ARQUIVOS */
$arquivos = `ls -l $pasta`;
$arquivos = explode("\n", $arquivos);

$ano_pesquisa = (is_numeric($ano_pesquisa)) ? "$ano_pesquisa" : $ano_pesquisa;

/* FILTRA O ARRAY PARA OS ARQUIVOS DO ANO E DO M�S INFORMADO */
unset($arquivos[0]);

$arquivos = array_values(array_filter($arquivos, function($v){
    global $tipo_arquivos, $ano_pesquisa, $mes_pesquisa;

    $nome_arquivo = array_values(array_filter(explode(' ', $v)));
    #if (count($nome_arquivo)) {i
    #    if (($ano_pesquisa == false && strpos($nome_arquivo[7], ':') === false) ||
    #            ($ano_pesquisa !== false && $nome_arquivo[7] !== $ano_pesquisa)) {
    #		return true;
    #        return false;
    #    }
    #   if (in_array(strtoupper(substr($nome_arquivo[8], -3)), $tipo_arquivos) && strtolower($nome_arquivo[5]) == strtolower($mes_pesquisa)) {
    #        return true;
    #    }
    #}
    if (!in_array(strtolower(trim($nome_arquivo[5])), array("sep", "oct"))) {
        return false;
    }
    return true;
}));

/* CRIA A ORDENA��O DOS ARQUIVOS COM A ORDEM DECRESCENTE */
foreach ($arquivos as $linha) {
    $dados = array_values(array_filter(explode(' ', $linha)));
    //if ($ano_pesquisa !== false) {
        $listagem_arquivos[$dados[6]][] = $dados[8];
    //}else{
        //$listagem_arquivos[$dados[6]][$dados[7]] = $dados[8];
    //}
    ksort($listagem_arquivos[$dados[6]]);
}

$sql = "SELECT
            pedido,
            seu_pedido
        FROM tbl_pedido
        WHERE fabrica = 1 AND finalizado >= '$consulta_data' AND status_pedido NOT IN(14) AND exportado IS NOT NULL  ORDER BY data;";

$res  = pg_query($con, $sql);
$rows = pg_num_rows($res);
if ($rows > 0) {
	#pg_query($con, "BEGIN");
	#pg_query($con, "UPDATE tbl_fabrica SET altera_pedido_exportado = TRUE WHERE fabrica = 1");
    $fp = fopen($log_pedido, "a");
    #pg_prepare($con, 'atualiza_pedido', "UPDATE tbl_pedido SET arquivo_pedido = $1 WHERE pedido = $2;");
    #pg_prepare($con, 'consulta_ultimo_status', "SELECT status FROM tbl_pedido_status WHERE pedido = $1 ORDER BY data DESC LIMIT 1;");
    for ($i = 0; $i < $rows; $i++) {
        $pedido     = pg_fetch_result($res, $i, 'pedido');
        $seu_pedido = pg_fetch_result($res, $i, 'seu_pedido');

        $achou = 0;
	$status_aprovacao = false;
	$a = array();
        foreach ($listagem_arquivos as $dia => $array_arquivos) {
            foreach ($array_arquivos as $Arquivo) {
                $conteudo = file_get_contents("$pasta/$Arquivo");
                if (strpos($conteudo, $seu_pedido) !== false) {
		#if (preg_match("/$seu_pedido/", $conteudo)) {
		    $sqlUltimoStatus = "SELECT status FROM tbl_pedido_status WHERE pedido = $pedido ORDER BY data DESC LIMIT 1";
		    $resStatus = pg_query($con, $sqlUltimoStatus);
                    #$resStatus = pg_execute($con, 'consulta_ultimo_status', array($pedido));
                    if (pg_num_rows($resStatus) > 0 && pg_fetch_result($resStatus, 0, 'status') == '18') {
			$status_aprovacao = true;
                    }
                    $achou = 1;
		    $a[] = $Arquivo;
                }
            }
        }
        if ($achou !== 1) {
            fwrite($fp, "$pedido;$seu_pedido;;;\n");
        } else {
		$a_time = array();
		foreach($a as $k => $v) {
			$a_time[$v] = filemtime($pasta."/".$v);
		}
		asort($a_time);
		$Arquivo = array_keys($a_time);
		$Arquivo = $Arquivo[0];

		#$sqlArquivo = "SELECT pedido FROM tbl_pedido WHERE pedido = {$pedido} AND LOWER(arquivo_pedido) != LOWER('{$Arquivo}')";
		#$resArquivo = pg_query($con, $sqlArquivo);

		#if (pg_num_rows($resArquivo) > 0) {
			#pg_query($con, "UPDATE tbl_pedido SET arquivo_pedido = E'{$Arquivo}' WHERE pedido = {$pedido}");
		#}

		if ($status_aprovacao) {
                        fwrite($fp, "$pedido;$seu_pedido;$Arquivo;18\n");
                } else {
                        fwrite($fp, "$pedido;$seu_pedido;$Arquivo;\n");
                }
	}
    }
    fclose($fp);
	#pg_query($con, "UPDATE tbl_fabrica SET altera_pedido_exportado = FALSE WHERE fabrica = 1");
	#pg_query($con, "COMMIT");
}
?>

