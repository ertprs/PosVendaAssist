<?php
// HD 708697
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';
	
	if ( !isset ($_GET['deslocamento']) || $_GET['deslocamento'] != 't' )
		exit;
		
	$descricao = trim($_GET['descricao']);
	$referencia = trim($_GET['referencia']);
	
	if ( empty($descricao) && empty($referencia) )
		exit;
		
	$cond = !empty($descricao) ? " AND tbl_produto.descricao ILIKE '" . $descricao . "%'" : '';
	$cond .= !empty($referencia) ? " AND tbl_produto.referencia LIKE '" . $referencia . "%'" : ''; 
	
	$sql = "SELECT produto
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE 
				tbl_linha.fabrica = $login_fabrica
				$cond
				AND tbl_linha.deslocamento";
				
	$res = pg_query($con,$sql);
	
	if ( pg_num_rows($res) ) {
		echo 't';
		exit;
	}