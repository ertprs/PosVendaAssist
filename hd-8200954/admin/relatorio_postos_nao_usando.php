<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
if ($_GET['gerar_excel']) {
	if ($_GET['status'] &&  $_GET['status'] != 'undefined') {
		$status = $_GET['status'];
		if ($status != '') {
			$cond .= " AND tbl_posto_fabrica.credenciamento = '$status'";
		}
	}
	if ($_GET['tipo_posto'] &&  $_GET['tipo_posto'] != 'undefined') {
		$tipo_posto = $_GET['tipo_posto'];
		if ($tipo_posto != '') {
			$cond .= " AND tbl_posto_fabrica.tipo_posto = $tipo_posto";
		}
	}

	if( $utilizacao = $_GET['utilizacao'] ){
		if( strlen($utilizacao) > 0 ){
		 	if( $utilizacao == 'os' ){
				$cond .= " AND tbl_posto_fabrica.digita_os IS TRUE";
			}elseif( $utilizacao == 'pedidos_faturados' ){
				$cond .= " AND tbl_posto_fabrica.pedido_faturado IS TRUE";
			}
	 	} 
	}

	if ($_GET['familia'] &&  $_GET['familia'] != 'undefined') {
		$familia = $_GET['familia'];
		if ($familia != '') {
			$condSub .= " AND tbl_produto.familia = $familia";
		}
	}
	if ($_GET['linha'] &&  $_GET['linha'] != 'undefined') {
		$familia = $_GET['linha'];
		if ($familia != '') {
			$condSub .= " AND tbl_produto.linha = $linha";
		}
	}

	$sql ="SELECT
			tbl_posto_fabrica.codigo_posto            ,
			tbl_posto.nome                            ,
			tbl_posto.cnpj                            ,
			tbl_posto_fabrica.contato_cidade as cidade                          ,
			tbl_posto_fabrica.contato_estado     as estado                          ,
			tbl_posto.posto,
			tbl_posto_fabrica.contato_email,
			tbl_posto_fabrica.contato_fone_residencial,
   			tbl_posto_fabrica.contato_cel,
			tbl_posto_fabrica.data_input,
			(SELECT data_abertura FROM tbl_os WHERE fabrica = $login_fabrica and posto = tbl_posto.posto order by data_abertura desc limit 1) as ultima_os
			FROM tbl_posto
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_credenciamento on tbl_credenciamento.posto = tbl_posto.posto
			AND tbl_credenciamento.fabrica = $login_fabrica
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica
			$cond
			$join_nks ";

	if( !($telecontrol_distrib == 't') ){
		if (!in_array($login_fabrica,array(50,51,86))) {
			$sql .= " AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";
      	}
	}

	$sql .= "AND tbl_posto.posto NOT IN ( 
			SELECT posto 
			FROM tbl_os 
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			$condSub
			WHERE posto=tbl_posto.posto AND fabrica = $login_fabrica";
	if($login_fabrica == 19){
		$sql .= " and os_fechada='t' ";
	}
	if ($_GET['inicial'] && $_GET['final']) {
		$data_inicial = date_format(date_create_from_format( 'd/m/Y', $_GET['inicial']), 'Y-m-d');
		$data_final   = date_format(date_create_from_format( 'd/m/Y', $_GET['final']), 'Y-m-d');

		if ($data_inicial != '' && $data_final != '') {
			if ($login_fabrica == 117){
				$datas_between = "AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'";
			}else{
				$sql .= " and data_abertura between '$data_inicial' and '$data_final' " ;
			}
		}
	}

	$sql .= "
			LIMIT 1
		) GROUP BY
			$group_nks
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			tbl_posto.cnpj                            ,
			tbl_posto_fabrica.contato_cidade                          ,
			tbl_posto_fabrica.contato_estado                          ,
			tbl_posto.posto,
			tbl_posto_fabrica.contato_email,
			tbl_posto_fabrica.contato_fone_residencial,
			tbl_posto_fabrica.contato_cel,
			tbl_posto_fabrica.data_input
			ORDER BY
			tbl_posto.nome";
	#echo nl2br($sql); die;
	
	if ($login_fabrica == 117){
		$drops = "DROP TABLE IF EXISTS p;
	              DROP TABLE IF EXISTS o;";
	    $qry = pg_query($con, $drops);

		$sql_p = "SELECT tbl_posto_fabrica.posto,
				       tbl_posto_fabrica.codigo_posto,
				       tbl_posto_fabrica.contato_cidade as cidade,
				       tbl_posto_fabrica.contato_estado as estado,
				       tbl_posto_fabrica.contato_email, 
				       tbl_posto_fabrica.data_input,
				       tbl_posto.nome,
				       tbl_posto.cnpj
				into temp p 
				FROM tbl_posto_fabrica 
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
				$cond";
		$res_p = pg_query($con, $sql_p);

		$sql_o = "SELECT DISTINCT ON (posto) 
					     tbl_os.posto,
					     tbl_os.data_abertura 
				  into temp o 
				  FROM tbl_os
				  JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				  $condSub
				  WHERE tbl_os.fabrica = $login_fabrica 
				  $datas_between";
		$res_o = pg_query($con, $sql_o);

		$sql = "SELECT p.*, 
	                  ( SELECT data_abertura 
	                    FROM tbl_os  
	                    JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
	                    $condSub
	                    WHERE fabrica = $login_fabrica 
	                    AND posto = p.posto 
	                    ORDER BY data_abertura DESC LIMIT 1) AS ultima_os
	            FROM p 
	            WHERE posto NOT IN (SELECT posto FROM o)";
	}			

	$res = pg_exec ($con,$sql);
	if ($_GET['tipo'] == 'xls') {
		$table = "<table border='1' id='tabela_excel' >";
		$table .= "<tr>";
		$table .= "	<td><B>CNPJ</B></td>";
		$table .= "	<td><B>Código</B></td>";
		$table .= "	<td><B>Nome</B></td>";
		$table .= "	<td><B>Cidade</B></td>";
		$table .= "	<td><B>Estado</B></td>";
		$table .= "	<td><B>Credenciamento</B></td>";
		$table .= "	<td><B>última OS aberta</B></td>";
		$table .= "</tr>";
		
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$cnpj           = trim(pg_result($res,$i,cnpj));
			$cidade         = trim(pg_result($res,$i,cidade));
			$estado         = trim(pg_result($res,$i,estado));
			$nome           = trim(pg_result($res,$i,nome));
			$email          = trim(pg_result($res,$i,contato_email));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$posto          = trim(pg_result($res,$i,posto));
			$data_input     = trim(pg_result($res,$i,data_input));
			$data           = trim(pg_result($res,$i,data));
			$ultima_os		= trim(pg_result($res,$i,ultima_os));
			/*$posto          = trim(pg_result($res,$i,posto));*/
			$credenciamento = date_create(($data_input) ? $data_input : $data);
		
			$table .= "<tr>";
			$table .= "	<td>{$cnpj}</td>";
			$table .= "	<td>{$codigo_posto}</td>";
			$table .= "	<td>{$nome}</td>";
			$table .= "	<td>{$cidade}</td>";
			$table .= "	<td>{$estado}</td>";
			$table .= "	<td>";
			$table .= date_format($credenciamento, 'd/m/Y');
			$table .= "	</td>";
			if ($login_fabrica == 117){
			$ultima_os = empty($ultima_os) ? "" : date_format(date_create(($ultima_os)), 'd/m/Y');
			$table .= "<td>";
			$table .= $ultima_os;
			$table .= "</td>";	
		}else{
			$table .= "<td>";
			$table .= ($ultima_os) ? date_format(date_create(($ultima_os)), 'd/m/Y') : "Nenhuma OS aberta";
			$table .= "</td>";
		}
			$table .= "</tr>";
		}
		$table .= "</table>";
	}else{
		$table = "CNPJ;";
		$table .= "Código;";
		$table .= "Nome;";

		if( $telecontrol_distrib == 't' ){
			$table .= "Email;";
			$table .= "Telefone;";
			$table .= "Telefone 2;";
		}

		$table .= "Cidade;";
		$table .= "Estado;";
		$table .= "Credenciamento;";
		$table .= "última OS aberta;";
		$table .= "\n";
		
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$cnpj           = trim(pg_result($res,$i,cnpj));
			$cidade         = trim(pg_result($res,$i,cidade));
			$estado         = trim(pg_result($res,$i,estado));
			$nome           = trim(pg_result($res,$i,nome));
			$email          = trim(pg_result($res,$i,contato_email));
			$telefone 		= trim(pg_result($res,$i,contato_fone_residencial));
			$telefone2   	= trim(pg_result($res,$i,contato_cel));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$posto          = trim(pg_result($res,$i,posto));
			$data_input     = trim(pg_result($res,$i,data_input));
			$data           = trim(pg_result($res,$i,data));
			$ultima_os		= trim(pg_result($res,$i,ultima_os));
			/*$posto          = trim(pg_result($res,$i,posto));*/
			$credenciamento = date_create(($data_input) ? $data_input : $data);

			$table .= "{$cnpj};";
			$table .= "{$codigo_posto};";
			$table .= "{$nome};";

			if( $telecontrol_distrib == 't' ){
				$table .= "{$email};";
				$table .= "{$telefone};";
				$table .= "{$telefone2};";
			}

			$table .= "{$cidade};";
			$table .= "{$estado};";
			$table .= date_format($credenciamento, 'd/m/Y') . ";";
			if ($login_fabrica == 117){
				$ultima_os = empty($ultima_os) ? "" : date_format(date_create(($ultima_os)), 'd/m/Y');
				$table .= $ultima_os;
				$table .= ";";	
			}else{
				$table .= ($ultima_os) ? date_format(date_create(($ultima_os)), 'd/m/Y') : "Nenhuma OS aberta";
				$table .= ";";
			}
			$table .= "\n";
		}
	}
header("Content-type: application/vnd.ms-excel");
header("Content-type: application/force-download"); 
header("Content-Disposition: attachment; filename=planilha.csv"); 
header("Pragma: no-cache");

echo utf8_decode($table);
exit;
}