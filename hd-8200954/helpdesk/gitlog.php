<?php
if (!count($_GET))
	die();
$dir = dirname(__DIR__) . DIRECTORY_SEPARATOR;
chdir($dir);

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';

if (file_exists($_GET['page'])) {
	include_once('mlg_funciones.php');

	echo "<h3></h3>";
	exec("git log --date=short --pretty=format:'%cd  %<|(30,trunc)%cn: %s' --no-merges {$_GET['page']}", $log);

	$tableAttrs = array(
		'tableAttrs' => 'data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "'
	);

	$table = array2table(parseGitLog($log));
	$ret = <<<RESULT
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">GIT - LOG <code>{$_GET['page']}</code></h4>
      </div>
      <div class="modal-body">
		$table
      </div>
RESULT;
	die(utf8_decode($ret));
}

function parseGitLog(array $log) {
	$re = "/^(?P<Ano>\d{4})-(?P<Mes>\d\d)-(?P<Dia>\d\d) (?P<Autor>[^:]+): (?P<Mensagem.*)$/";
	$re = "/^(?P<Data>\d{4}-\d\d-\d\d) (?P<Autor>[^:]+): (?P<Mensagem>.*)$/";

	foreach ($log as $line) {
		if (!preg_match($re, $line, $rec))
			continue;

		extract($rec, EXTR_OVERWRITE);
		$Data = is_date($Data, 'iso', 'EUR');


		// Link para o chamado, se tiver essa informação no comentário
		if (preg_match('/(?P<str>hd[ _-]?(?P<hd>\d{7}))/i', $Mensagem, $hdInfo)) {
			$link = createHTMLLink('./adm_chamado_detalhe.php?hd_chamado='.$hdInfo['hd'].'&consultar=sim', $hdInfo['str'], 'target="_blank"');
			$Mensagem = str_replace($hdInfo['str'], $link, $Mensagem);
		}
		$table[] = compact('Data', 'Autor', 'Mensagem');
	}
	return $table;
}

echo "Not found $dir".$_GET['page'];

