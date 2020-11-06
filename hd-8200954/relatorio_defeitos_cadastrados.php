<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


	$sql = "SELECT
				tbl_defeito_reclamado.descricao         , 
				tbl_defeito_reclamado.defeito_reclamado ,
				tbl_defeito_reclamado.familia           ,
				tbl_defeito_reclamado.ativo             ,
				tbl_defeito_reclamado.linha             ,
				tbl_defeito_reclamado.duvida_reclamacao
			FROM tbl_defeito_reclamado
			LEFT JOIN tbl_familia USING (familia)
			WHERE tbl_familia.fabrica = 1
			ORDER BY tbl_defeito_reclamado.familia";
	$res = pg_exec ($con,$sql) ;

	$fields = pg_num_fields($res);

	//recuperando os nomes dos campos. Eles tamb�m ser�o os nomes dos campos da planilha
	for ($i = 0; $i < $fields; $i++) {
		$header .= pg_field_name($res, $i) . "\t";
	}

	for($i=0; $i < pg_numrows($res); $i++){
		$descricao         = trim(pg_result($res, $i, descricao));
		$defeito_reclamado = trim(pg_result($res, $i, defeito_reclamado));
		$familia           = trim(pg_result($res, $i, familia));
		$ativo             = trim(pg_result($res, $i, ativo));
		$linha             = trim(pg_result($res, $i, linha));
		$duvida_reclamacao = trim(pg_result($res, $i, duvida_reclamacao));

 		$row = array($descricao, $defeito_reclamado, $familia, $ativo, $linha, $duvida_reclamacao);
		$line = '';
		
		foreach($row as $value) { 
		if ((!isset($value)) OR ($value == "")) {
		$value = "\t";
		} else {
		$value = str_replace('"', '""', $value);
		$value = '"' . $value . '"' . "\t";
		}
		$line .= $value;
		}
		$dados .= trim($line)."\n";

		// substituindo todas as quebras de linha ao final de cada registro, que por padr�o seria \r por uma valor em branco, para que a formata��o fique leg�vel
		$dados= str_replace("\r","",$dados);

		// Caso n�o encontre nenhum registro, mostra esta mensagem. 
		if ($dados== "") {
		$dados = "\n Nenhum registro encontrado!\n"; 
		}

	}

	//�ltimo passo - Cabe�alhos e instru��es para gera��o e download do arquivo:
	header('Content-type: application/msexcel');
	// este cabe�alho abaixo, indica que o arquivo dever� ser gerado para download (par�metro attachment) e o nome dele ser� o contido dentro do par�metro filename. 
	header('Content-Disposition: attachment; filename="relatorio_defeitos_cadastrados.xls"');
	// No cache, ou seja, n�o guarda cache, pois � gerado dinamicamente 
	header("Pragma: no-cache");
	// N�o expira 
	header("Expires: 0");
	// E aqui geramos o arquivo com os dados mencionados acima! 
	print "$header\n$dados";

?>
