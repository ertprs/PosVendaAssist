<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if ($_GET["term"]) {
	$term   = $_GET["term"];
	$search = $_GET["search"];

	if (!strlen($term) || !strlen($search)) {
		exit;
	}

	$limit = "LIMIT 21";


	$usa_rev_fabrica = in_array($login_fabrica, array(3,24));

	if($usa_rev_fabrica){
		switch ($search) {
			case "cod":
				$ilike = "tbl_revenda_fabrica.cnpj ILIKE '%{$term}%'";
				break;

			case "desc":
				$ilike = "TO_ASCII(tbl_revenda_fabrica.contato_razao_social, 'LATIN-9') ILIKE TO_ASCII('%{$term}%', 'LATIN-9')";
				break;
		}

		$sql = "SELECT 	tbl_revenda_fabrica.revenda,
						tbl_revenda_fabrica.contato_razao_social AS nome,
						tbl_revenda_fabrica.cnpj                ,
		               tbl_revenda_fabrica.contato_endereco AS endereco    ,
					   tbl_revenda_fabrica.contato_numero  AS numero     ,
					   tbl_revenda_fabrica.contato_complemento AS complemento ,
					   tbl_revenda_fabrica.contato_bairro AS bairro      ,
					   tbl_revenda_fabrica.contato_cep  AS cep        ,
					   tbl_cidade.estado				        ,
					   tbl_cidade.nome AS cidade_nome           ,
					   tbl_revenda_fabrica.contato_fone AS fone        ,
					   tbl_revenda_fabrica.ie                   ,
					   tbl_revenda_fabrica.contato_nome  AS contato       ,
					   tbl_revenda_fabrica.contato_email AS email       ,
					   tbl_revenda_fabrica.contato_fax  AS fax          ,
					   tbl_revenda_fabrica.contato_nome_fantasia,
					   tbl_revenda_fabrica.contato_cidade
				FROM tbl_revenda_fabrica
				LEFT JOIN tbl_cidade ON tbl_revenda_fabrica.cidade = tbl_cidade.cidade
				WHERE tbl_revenda_fabrica.fabrica = {$login_fabrica}
				AND {$ilike}
				{$limit}";
		}else{
			switch ($search) {
				case "cod":
					$ilike = "tbl_revenda.cnpj ILIKE '%{$term}%'";
					break;

				case "desc":
					$ilike = "TO_ASCII(tbl_revenda.nome, 'LATIN-9') ILIKE TO_ASCII('%{$term}%', 'LATIN-9')";
					break;
			}

			$sql = "SELECT 	tbl_revenda.revenda,
							tbl_revenda.nome,
							tbl_revenda.cnpj                ,
			                tbl_revenda.endereco     ,
						    tbl_revenda.numero       ,
						    tbl_revenda.complemento  ,
						    tbl_revenda.bairro       ,
						    tbl_revenda.cep          ,
						    tbl_cidade.estado				        ,
						    tbl_cidade.nome AS cidade_nome           ,
						    tbl_revenda.fone         ,
						    tbl_revenda.ie                   ,
						    tbl_revenda.contato         ,
						    tbl_revenda.email        ,
						    tbl_revenda.fax          ,
						    null AS contato_nome_fantasia,
						    null AS contato_cidade
					FROM tbl_revenda
					LEFT JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
					WHERE  1 = 1
					AND {$ilike}
					{$limit}";
		}

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		for ($i = 0; $i < pg_num_rows($res); $i++ ){
			$resultado[$i]["revenda_fabrica"] 	= utf8_encode(pg_fetch_result($res, $i, 'revenda'));
			$resultado[$i]["razao"]         	= utf8_encode(pg_fetch_result($res, $i, 'nome'));
			$resultado[$i]["desc"] 				= $resultado[$i]["razao"];
			$resultado[$i]["cnpj"]          	= utf8_encode(pg_fetch_result($res, $i, 'cnpj'));
			$resultado[$i]["cod"] 				= $resultado[$i]["cnpj"];
			$resultado[$i]["edereco"]       	= utf8_encode(pg_fetch_result($res, $i, 'endereco'));
			$resultado[$i]["numero"]        	= utf8_encode(pg_fetch_result($res, $i, 'numero'));
			$resultado[$i]["complemento"]   	= utf8_encode(pg_fetch_result($res, $i, 'complemento'));
			$resultado[$i]["bairro"]        	= utf8_encode(pg_fetch_result($res, $i, 'bairro'));
			$resultado[$i]["cep"]           	= utf8_encode(pg_fetch_result($res, $i, 'cep'));
			$resultado[$i]["estado"]        	= utf8_encode(pg_fetch_result($res, $i, 'estado'));
			$resultado[$i]["cidade"]        	= utf8_encode(pg_fetch_result($res, $i, 'cidade_nome'));
			$resultado[$i]["fone"]          	= utf8_encode(pg_fetch_result($res, $i, 'fone'));
			$resultado[$i]["ie"]            	= utf8_encode(pg_fetch_result($res, $i, 'ie'));
			$resultado[$i]["contato"]       	= utf8_encode(pg_fetch_result($res, $i, 'nome'));
			$resultado[$i]["email"]         	= utf8_encode(pg_fetch_result($res, $i, 'email'));
			$resultado[$i]["fax"]           	= utf8_encode(pg_fetch_result($res, $i, 'fax'));
			$resultado[$i]["nome_fantasia"] 	= utf8_encode(pg_fetch_result($res, $i, 'contato_nome_fantasia'));

		}
	}

	echo json_encode($resultado);
}

exit;
?>
