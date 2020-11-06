<?php

include "dbconfig.php";
include "dbconnect-inc.php";
include "autentica_admin.php";
	
$busca      = $_GET["busca"];
$tipo_busca = $_GET["tipo_busca"];

if (strlen($q)>2){

	if ($tipo_busca=="posto"){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}

	if ($tipo_busca=="produto"){
		$sql = "SELECT tbl_produto.produto,
						tbl_produto.referencia,
						tbl_produto.descricao
				FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE tbl_linha.fabrica = $login_fabrica ";

		$sql .=  ($busca == "codigo") ? " AND tbl_produto.referencia like '%$q%' " : " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$produto    = trim(pg_fetch_result($res,$i,produto));
				$referencia = trim(pg_fetch_result($res,$i,referencia));
				$descricao  = trim(pg_fetch_result($res,$i,descricao));
				echo "$produto|$descricao|$referencia";
				echo "\n";
			}
		}
	}

	if ($tipo_busca=="cliente_admin"){
		$y = trim (strtoupper ($q));
		$palavras = explode(' ',$y);
		$count = count($palavras);
		$sql_and = "";
		for($i=0 ; $i < $count ; $i++){
			if(strlen(trim($palavras[$i]))>0){
				$cnpj_pesquisa = trim($palavras[$i]);
				$cnpj_pesquisa = str_replace (' ','',$cnpj_pesquisa);
				$cnpj_pesquisa = str_replace ('-','',$cnpj_pesquisa);
				$cnpj_pesquisa = str_replace ('\'','',$cnpj_pesquisa);
				$cnpj_pesquisa = str_replace ('.','',$cnpj_pesquisa);
				$cnpj_pesquisa = str_replace ('/','',$cnpj_pesquisa);
				$cnpj_pesquisa = str_replace ('\\','',$cnpj_pesquisa);
				$sql_and .= " AND (tbl_cliente_admin.nome ILIKE '%".trim($palavras[$i])."%'
								  OR  tbl_cliente_admin.cnpj ILIKE '%$cnpj_pesquisa%' OR tbl_cliente_admin.cidade ILIKE '%".trim($palavras[$i])."%')";
				if (strlen($cidade)>0) {
					$sql_and .= " AND tbl_cliente_admin.cidade ILIKE '%".trim($cidade)."%'";
				}
			}
		}

		$sql = "SELECT      tbl_cliente_admin.cliente_admin,
							tbl_cliente_admin.nome,
							tbl_cliente_admin.codigo,
							tbl_cliente_admin.cnpj,
							tbl_cliente_admin.cidade
				FROM        tbl_cliente_admin
				WHERE       tbl_cliente_admin.fabrica = $login_fabrica
				$sql_and limit 30";

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cliente_admin      = trim(pg_fetch_result($res,$i,cliente_admin));
				$nome               = trim(pg_fetch_result($res,$i,nome));
				$codigo             = trim(pg_fetch_result($res,$i,codigo));
				$cnpj               = trim(pg_fetch_result($res,$i,cnpj));
				$cidade             = trim(pg_fetch_result($res,$i,cidade));

				echo "$cliente_admin|$cnpj|$codigo|$nome|$cidade";
				echo "\n";
			}
		}
	}
}
exit;

?>
