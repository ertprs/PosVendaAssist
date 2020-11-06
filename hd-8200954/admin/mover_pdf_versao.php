#!/usr/bin/php -q
<?php
/**
 * Changelog
 * ---------
 * 2016-02-10	* short params not working, always using long ones, which were blank
 **/
error_reporting(E_WARNING | E_ERROR);
//Inicializa o banco TC, include AmazonSDK.
require_once __DIR__ . '/phpini_S3.php';
require_once 'anexaS3.class.php';
require_once './lib/fn_sql_cmd.php';

// config padrão
$testmode   = false;
$testlimit  = 5;
$ver_sep    = '-';
$usa_versao = true;
$file_exts  = 'pdf,PDF,Pdf,pDf,pfD';

$help = <<<HELP

Uso:
  {$argv[0]} [opções] id_fábrica

Ex.:

	{$argv[0]} --ext=jpg,gif,pdf,doc,docx 50

	processa essas extensões para a fábrica 50.

Opções:

	-v  | --ver         Mondial (v2): extrai a versão do nome do arquivo,
						não processa se não têm versão.
	-t  | --testmode    Ativa o modo teste: não grava comunicado (porém dá
						o INSERT + ROLLBACK) e não sobe arquivos.
						se passado o valor 'sql' (-tsql --testmode=sql)
						NÃO executa os INSERT, e mostra em tela.
	-ln | --testlimit=n Limita a quantidade de arquivos a serem processados
	-x  | --ext			Lista das extensões dos arquivos a serem processados,
						sem ponto, separados por vírgula, como no exemplo.
	-h  | --help		Esta ajuda.
HELP;

// Parse command-line parameters
if (defined('isCLI') and isCLI) {
	if (!$login_fabrica) {
		if (is_numeric($argv[$argc-1])) {
			$login_fabrica = (int)$argv[$argc-1];
			array_pop($argv); $argc--;
		}
	}

	$args = getopt(
		"ht::l:s:vx:",
		array('help','testmode::','testlimit:','ver_sep:','ver', 'ext:')
	);

	$usa_versao = array_key_exists('ver', $args) ||
		array_key_exists('v', $args);

	if (isset($args['help']) or isset($args['h'])) {
		die($help);
	}

	if (isset($args['testmode']) or isset($args['t'])) {
		$testmode = true;
		$testmode = ($args['testmode']) ? :
			(($args['t']) ? : $testmode);

		if (is_numeric($testmode)) {
			$testlimit = $testmode;
			$testmode = true;
		}
	}

	if (isset($args['ver_sep']) or isset($args['s'])) {
		if (empty($args['ver_sep']) and empty($args['s']))
			die("Separator not defined.\n");

		$ver_sep = ($args['ver_sep']) ? :
			(($args['s']) ? : $ver_sep);
	}

	if (isset($args['ext']) or isset($args['x'])) {
		if (empty($args['ext']) and empty($args['x']))
			die("Deve informar quais tipos de arquivo aceitar\n");

		if (is_numeric($args['ext']))
			$file_exts = $args['ext'];
		if (is_numeric($args['x']))
			$file_exts = $args['x'];
	}

	if (isset($args['testlimit']) or isset($args['l'])) {
		if (empty($args['testlimit']) and empty($args['l']))
			die("Limit not set.\n");

		if (is_numeric($args['testlimit']))
			$testlimit = (int)$args['testlimit'];
		if (is_numeric($args['l']))
			$testlimit = (int)$args['l'];
	}
}

if (!is_numeric($login_fabrica))
	die("No está logado!\n");

$s3 = new anexaS3('ve', $login_fabrica);

$fabrica_nome = pg_fetch_result(
	pg_query(
		$con,
		sql_cmd(
			'tbl_fabrica',
			'LOWER(nome)',
			$login_fabrica
		)
	),
	0, 0
);

if (empty($fabrica_nome))
	die("Fabricante não encontrado!\n");

$dir = "/var/www/$fabrica_nome/vistas_explodidas_$fabrica_nome/";
// echo $dir = "/home/manuel/vistas_$fabrica_nome/";

$count     = 0;
$nao       = 0;
$inseridos = 0;

$where_produto = function($ref, $ver=null) use ($login_fabrica) {
	return array(
		'codigo' => array(
			'tbl_produto.referencia' => "$ref",
			'@tbl_produto.referencia_pesquisa' => "$ref"
		),
		'fabrica_i' => $login_fabrica
	);
};

/*** COMEÇA O PROGRAMA ***/

$arquivos = glob($dir."*.{$file_exts}", GLOB_BRACE);

foreach($arquivos as $i=>$file) {

	$ext = pathinfo($file, PATHINFO_EXTENSION);
	$ref = pathinfo($file, PATHINFO_FILENAME);
	$ver = null;

	if ($count == $testlimit and $testmode)
		break; // limite para testes

	if ($usa_versao and strpos($ref, $ver_sep)) {
		preg_match("/^([A-Z0-9]+-[0-9]{2})$ver_sep(\w{3})$/", $ref, $regexres);
		print_r($regexres);
		list(,$ref, $ver) = $regexres;
	}

	if ($testmode)
		pecho(basename($file) . " | Produto $ref, versão $ver, arquivo $ext");

	if ($ref == '' or ($usa_versao and $ver == '')) {
		continue;
	}

	if ($testmode)
		pecho("Arquivo $count: '$file'.\n  Ref. produto: '$ref' versão: '$ver' Ext.: $ext");

	//verifica se o produto com a referencia do PDF existe
	$sql = sql_cmd('tbl_produto', 'produto', $where_produto($ref));

	if ($testmode === 'sql')
		pre_echo($sql, "REF.: $ref");

	$res1 = pg_query($con, $sql);
	$numrows = pg_num_rows($res1);

	if ($numrows > 0) {
		$produto = pg_fetch_result($res1, 0, 0);
		if ($testmode)
			$count += $numrows;
		if ($testmode)
			pecho("\nProduto ID: $produto");

		// Renomeia de referencia-versao.pdf para id_produto.pdf
		if (is_readable($file)) {
			$para = "$dir$produto.pdf";
			if (!$testmode)
				rename($file, $para);
		} else {
			continue;
		}

		pg_query($con,'BEGIN TRANSACTION');

		if ($numrows > 1) { //se tiver mais de um produto com a referência, que não deveria

			for ($count=0, $comunicado=null; $count < $numrows; $count++) {

				// Cria um novo comunicado para o arquivo
				if (is_null($comunicado)) {
					$sql = sql_cmd(
						'tbl_comunicado',
						array(
							'tipo'     => 'Vista Explodida',
							'fabrica'  => $login_fabrica,
							'versao'   => pg_quote(substr($ver, 0, 3), true),
							'extensao' => 'pdf',
							'ativo'    => true,
							'obrigatorio_os_produto' => false,
						)
					) . "\nRETURNING comunicado";

					if ($testmode === 'sql') {
						$comunicado = 1000000;
						pre_echo($sql, 'INSERE COMUNICADO');
					} else {
						$comunicado = pg_fetch_result(pg_query($con, $sql), 0, 0);
					}
				}

				// Atrela o produto ao comunicado
				$sql_cp = sql_cmd(
					'tbl_comunicado_produto',
					compact('comunicado', 'produto')
				);

				if ($testmode === 'sql'):
					pre_echo($sql_cp, 'ATRELA COMUNICADO <-> PRODUTO');
				else:
					$produto = pg_fetch_result($res1, $count, 0);
					pg_query($con, $sql_cp);
				endif;
			}

		} else { //apenas um produto, insere só em uma tabela

			$sql = sql_cmd(
				'tbl_comunicado',
				array(
					'tipo'     => 'Vista Explodida',
					'fabrica'  => $login_fabrica,
					'produto'  => $produto,
					'versao'   => pg_quote(substr($ver, 0, 3), true),
					'extensao' => 'pdf',
					'ativo'    => true,
					'obrigatorio_os_produto' => false,
				)
			) . "\nRETURNING comunicado";

			if ($testmode === 'sql'):
				pre_echo($sql, "\nINSERE COMUNICADO");
				$comunicado = 1001;
			else:
				$comunicado = pg_fetch_result(pg_query($con, $sql),0,0);
				if ($testmode)
					pecho('Comunicado: ' . $comunicado);
			endif;
		}

		if (strlen(pg_last_error($con)) > 0) {
			echo "$ref - $comunciado - OPS...\n\n";
			pg_query($con,"ROLLBACK TRANSACTION");
			continue;
		} else {
			if ($testmode) {
				$sqlLink = sql_cmd(
					'tbl_comunicado',
					array('link_externo' => "s3://$comunicado.pdf"),
					$comunicado
				);
				pecho ($sqlLink, 'Aqui sobe para o S3...');

				$inseridos++;
				continue;
			}
			if (is_readable($para)) {
				if ($s3->uploadFileS3($comunicado, $para, true)) {
					unlink($para);
					$inseridos++;
				}
			} else {
				echo '<br /><font color="red">PDF nao Encontrado</font>';
			}
			pg_query($con,"COMMIT TRANSACTION");
		}

	} else {
		echo "\n<p style='color:red'>Produto $ref $ver nao existe</p>\n";
		$nao++;
	}
}

pecho('Total: ' . $i);
pecho('Movidos: ' . $inseridos);
pecho('Nao existem ou nao foram movidos: ' . $nao);

