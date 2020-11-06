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

	//recuperando os nomes dos campos. Eles também serão os nomes dos campos da planilha
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

		// substituindo todas as quebras de linha ao final de cada registro, que por padrão seria \r por uma valor em branco, para que a formatação fique legível
		$dados= str_replace("\r","",$dados);

		// Caso não encontre nenhum registro, mostra esta mensagem. 
		if ($dados== "") {
		$dados = "\n Nenhum registro encontrado!\n"; 
		}

	}

	//Último passo - Cabeçalhos e instruções para geração e download do arquivo:
	header('Content-type: application/msexcel');
	// este cabeçalho abaixo, indica que o arquivo deverá ser gerado para download (parâmetro attachment) e o nome dele será o contido dentro do parâmetro filename. 
	header('Content-Disposition: attachment; filename="relatorio_defeitos_cadastrados.xls"');
	// No cache, ou seja, não guarda cache, pois é gerado dinamicamente 
	header("Pragma: no-cache");
	// Não expira 
	header("Expires: 0");
	// E aqui geramos o arquivo com os dados mencionados acima! 
	print "$header\n$dados";

?>
