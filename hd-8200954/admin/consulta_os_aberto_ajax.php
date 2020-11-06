<?php
header("Expires: {$gmtDate} GMT");
header("Cache-Control: no-store, no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

extract($_GET);

$anterior		= ($dias == 5) ? 0 : $dias - 9;
$cond_posto		= ($posto != "") ? "AND posto = $posto " : "";
$cond_produto	= ($produto!="") ? "AND produto = $produto " : "";

if ($dias < 35) {
	$cond_datas	= " BETWEEN current_date - INTERVAL '$dias days' AND current_date - INTERVAL '$anterior days'";
} else {
	$cond_datas	= " < current_date-INTERVAL '25 days'";
}

	$sql = "SELECT DISTINCT os,
					CASE WHEN tbl_os.sua_os IS NULL
						THEN os::varchar
						ELSE tbl_os.sua_os
					END AS sua_os,
					TO_CHAR(data_abertura,'DD-MM-YYYY') AS data_abertura,
					(SELECT count(os_produto) AS qtde_itens
						FROM tbl_os_produto
						WHERE os = tbl_os.os) AS qtde_pecas,
					tbl_posto_fabrica.codigo_posto AS codigo_posto,
					tbl_posto.nome AS razao_social,
					CASE WHEN tbl_os.consumidor_estado IS NOT NULL
						THEN tbl_os.consumidor_estado
						ELSE tbl_posto.estado
					END AS estado
				FROM tbl_os
				JOIN tbl_posto USING (posto)
				JOIN tbl_posto_fabrica USING (posto,fabrica)
				  WHERE fabrica	= $login_fabrica
				    $cond_posto
				    $cond_produto
				    AND data_fechamento IS NULL
				    AND tbl_os.excluida IS NOT TRUE
				    AND data_abertura::date $cond_datas
				  ORDER BY data_abertura,os
	";

if ($ajax == "consulta") {
	$res = pg_query($con, $sql);
	if (!is_resource($res)) {echo "ko";exit;}
	$numrows = pg_num_rows($res);
	if ($numrows === false) {echo "ko";exit;}

	$os_sem = Array();
	$os_com = Array();
    for ($i; $i < $numrows; $i++):
    	list ($os, $sua_os, $x, $qtde_pecas) = pg_fetch_row($res,$i);
    	if ($mostra == 'true') echo "$os<br>";
    	$os = "<a target='_blank' href='os_press.php?os=$os'>$sua_os</a>";
    	if ($mostra == 'true') echo "$os<br>";
    	if ($qtde_pecas):
    	    $os_com[] = $os;
		else:
		    $os_sem[] = $os;
		endif;
	endfor;
	if (!count($os_sem) and !count($os_com)) {echo "NO RESULTS";exit;}
	if (count($os_sem)) {
		echo "<p>OS em aberto <b>SEM</b> peças ";
		echo ($dias <35)?"entre $anterior e $dias dias:<br>\n":"há mais de 25 dias:<br>";
		echo implode(", ", $os_sem).
	         "</p>";
	}
	if (count($os_com)) {
		echo "<p>OS em aberto <b>COM</b> peças:<br>\n".
	         implode(", ", $os_com).
	         "</p>";
	}
	exit;
}

if ($ajax == "lista" and ($tipo_lista != "")) {
	$res = @pg_query($con, $sql);
	if (!is_resource($res)) {echo "ko";exit;}

	$numrows = pg_num_rows($res);
	if ($numrows === false) {echo "ko";exit;}

	$os_sem = Array();
	$os_com = Array();
//	Pega os nomes dos campos para o cabeçalho
	$numfields = @pg_num_fields($my_res);
	for ($c=0; $c < $numfields; $c++) {
 		$camposCSV	.= str_replace("_"," ",@pg_field_name($res, $c)).";";
	}
	$CSV = substr($camposCSV,0,-1)."\n";   //  Tira a última tabulação e acrescenta a quebra de linha

		$tabela = "<table>
	<thead>
	<tr>
		<th>OS</th>
		<th>Data OS</th>
		<th>Código Posto</th>
		<th>Razão Social</th>
		<th>Estado</th>
		<th>Peças</th>
	</tr>
	</thead>
	<tbody>
		";

    for ($i = 0; $i < $numrows; $i++):
        $valores = pg_fetch_row($res,$i);
    	list ($os,$sua_os,$data_abertura,$qtde_pecas,$codigo_posto,$razao_social,$estado) = $valores;
//    	if ($mostrar=='true') {echo "<pre>";print_r ($valores);echo "</pre>\n";}
		$num_os = "<a target='_blank' href='os_press.php?os=$os'>$sua_os</a>";
		$qtde_pecas = ($qtde_pecas) ? "			<td>$qtde_pecas</td>":"";
		$linha = "		<tr>".
				 "			<td>$num_os</td>".
				 "			<td>$data_abertura</td>".
				 "			<td>$codigo_posto</td>".
				 "			<td>$razao_social</td>".
				 "			<td>$estado</td>".
				 $qtde_pecas.
				 "		</tr>\n";

		$CSV.= implode(";",array_slice($valores,1))."\n";   // Tira o campo 'os' do array e junta
    	if ($qtde_pecas):
    	    $os_com[] = $linha;
		else:
		    $os_sem[] = $linha;
		endif;
	endfor;
	$nome_arquivo = "os_em_aberto";
	$nome_arquivo.= ($posto)?"_cp-$codigo_posto":"";
	$nome_arquivo.= ($produto)?"_produto-$produto":"";
	$nome_arquivo.= date('_Y_m_d');

	$tabela_sem	= str_replace("<th>Peças</th>", "", $tabela).
				  "		<caption>Listado de OS SEM peças</caption>\n";
	$tabela_sem.= implode("",$os_sem);
	$tabela_sem.= "	</tbody>\n</table>\n";

	$tabela_com	= $tabela . "		<caption>Listado de OS COM peças</caption>\n";
	$tabela_com.= implode("",$os_com);
	$tabela_com.= "	</tbody>\n</table>\n";

//  Gera arquivo XLS / HTML
		$file_res_xls = @fopen("./xls/$nome_arquivo.xls","wb");
		if (is_resource($file_res_xls)):
		    system ("chmod 664 ./xls/$nome_arquivo.xls");
			fwrite($file_res_xls, $tabela_sem.$tabela_com);
		else:
      		echo "ko";
      		exit;
		endif;
		if (is_resource($file_res_xls)) fclose($file_res_xls);
//  Gera arquico CSV
		$file_res_csv = @fopen("./xls/$nome_arquivo.csv","wb");
		if (is_resource($file_res_csv)):
		    system ("chmod 664 ./xls/$nome_arquivo.csv");
			fwrite($file_res_csv, $CSV);
		else:
      		echo "ko";
      		exit;
		endif;
		if (is_resource($file_res_csv)) fclose($file_res_csv);

		echo $tabela_sem;
		echo $tabela_com;
		echo "<p>Pode baixar a lista de OS como arquivo <a href='/assist/admin/xls/$nome_arquivo.xls'>XLS</a> ou ".
			 "<a href='/assist/admin/xls/$nome_arquivo.txt'>TXT</a>. Clique com o botão direito e selecione 'Salvar como...'</p>";
		exit;
}
?>
