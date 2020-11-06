<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
if($login_fabrica == 2) { // HD 38390
	include "dbconfig_dynacom.php";
	include "includes/dbconnect-inc_dynacom.php";
}

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Revendedores.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_revenda<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">

<?
if($login_fabrica == 2) { // HD 38390
		if (strlen($_GET["nome"]) > 0) {
		$nome = strtoupper (trim ($_GET["nome"]));

		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome da Revenda</b>: <i>$nome</i></font>";
		echo "<p>";

		$sql = "SELECT      tbl_cliente.cliente       as revenda   ,
							tbl_cliente.razao      as nome         ,
							tbl_cliente.cnpj                       ,
							tbl_cliente.cidade                     ,
							tbl_cliente.fone1 as fone              ,
							tbl_cliente.endereco                   ,
							'' as numero                           ,
							'' as complemento                      ,
							tbl_cliente.bairro                     ,
							tbl_cliente.cep                        ,
							tbl_cliente.email                      ,
							tbl_cidade.descricao       AS nome_cidade ,
							tbl_cidade.estado
				FROM        tbl_cliente
				LEFT JOIN   tbl_cidade USING (cidade)
				WHERE       tbl_cliente.razao ILIKE '%$nome%'
				AND         tbl_cliente.ativo IS TRUE
				AND         tbl_cliente.bloqueado IS FALSE
				AND         tbl_cliente.fisica_juridica ='J'
				ORDER BY    tbl_cidade.estado,tbl_cidade.descricao,tbl_cliente.bairro,tbl_cliente.razao";

		$res = pg_exec ($conn,$sql);

		if (pg_numrows ($res) == 0) {
			echo "<h1>vendedor '$nome' não encontrada</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}

	}elseif (strlen($_GET["cnpj"]) > 0) {
		$nome = strtoupper (trim ($_GET["cnpj"]));

		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ da vendedor</b>: <i>$cpf</i></font>";
		echo "<p>";

		$sql = "SELECT     tbl_cliente.cliente       as revenda   ,
							tbl_cliente.razao      as nome         ,
							tbl_cliente.cnpj                       ,
							tbl_cliente.cidade                     ,
							tbl_cliente.fone1 as fone              ,
							tbl_cliente.endereco                   ,
							'' as numero                           ,
							'' as complemento                      ,
							tbl_cliente.bairro                     ,
							tbl_cliente.cep                        ,
							tbl_cliente.email                      ,
							tbl_cidade.descricao       AS nome_cidade ,
							tbl_cidade.estado
				FROM        tbl_cliente
				LEFT JOIN   tbl_cidade USING (cidade)
				WHERE       tbl_cliente.cnpj ILIKE '%$nome%'
				AND         tbl_cliente.ativo IS TRUE
				AND         tbl_cliente.bloqueado IS FALSE
				AND         tbl_cliente.fisica_juridica ='J'
				ORDER BY    tbl_cliente.razao";

		$res = pg_exec ($conn,$sql);

		if (pg_numrows ($res) == 0) {
			echo "<h1>C.N.P.J. '$nome' não encontrado</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}elseif (strlen($_GET["cidade"]) > 0) {
		# HD 31204 - Francisco Ambrozio
		$nome = strtoupper (trim ($_GET["cidade"]));

		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Cidade da vendedor</b>: <i>$cpf</i></font>";
		echo "<p>";

		$sql = "SELECT    tbl_cliente.cliente       as revenda   ,
							tbl_cliente.razao      as nome         ,
							tbl_cliente.cnpj                       ,
							tbl_cliente.cidade                     ,
							tbl_cliente.fone1 as fone              ,
							tbl_cliente.endereco                   ,
							'' as numero                           ,
							'' as complemento                      ,
							tbl_cliente.bairro                     ,
							tbl_cliente.cep                        ,
							tbl_cliente.email                      ,
							tbl_cidade.descricao       AS nome_cidade ,
							tbl_cidade.estado
				FROM        tbl_cliente
				LEFT JOIN   tbl_cidade USING (cidade)
				WHERE       tbl_cidade.descricao ILIKE '%$nome%'
				AND         tbl_cliente.ativo IS TRUE
				AND         tbl_cliente.bloqueado IS FALSE
				AND         tbl_cliente.fisica_juridica ='J'
				ORDER BY    tbl_cliente.razao";

		$res = pg_exec ($conn,$sql);

		if (pg_numrows ($res) == 0) {
			echo "<h1>Cidade '$nome' não encontrada</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}elseif (strlen($_GET["familia"]) > 0){
		$familia = strtoupper (trim ($_GET["familia"]));
		$cidade = strtoupper (trim ($_GET["consumidor_cidade"]));

		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Familia do Produto que o vendedor atende</b>: <i>$cpf</i></font>";
		echo "<p>";

		$sql = "SELECT	   tbl_cliente.cliente       as revenda   ,
							tbl_cliente.razao      as nome         ,
							tbl_cliente.cnpj                       ,
							tbl_cliente.cidade                     ,
							tbl_cliente.fone1 as fone              ,
							tbl_cliente.endereco                   ,
							'' as numero                           ,
							'' as complemento                      ,
							tbl_cliente.bairro                     ,
							tbl_cliente.cep                        ,
							tbl_cliente.email                      ,
							tbl_cidade.descricao       AS nome_cidade ,
							tbl_cidade.estado
				FROM        tbl_cliente
				LEFT JOIN   tbl_cidade USING (cidade)
				WHERE cliente in
					(
					SELECT cliente
					FROM tbl_pedido
					JOIN tbl_pedido_item USING (pedido)
					JOIN tbl_produto USING (produto)
					JOIN tbl_grupo   USING(grupo)
					WHERE current_timestamp - data_digitacao < interval '15 months' AND tbl_grupo.descricao ILIKE '%$familia%'
					)
				AND         tbl_cliente.ativo IS TRUE
				AND         tbl_cliente.bloqueado IS FALSE
				AND         tbl_cliente.fisica_juridica ='J'
				AND         tbl_cidade.descricao ILIKE '%$cidade%'
				ORDER BY    tbl_cliente.razao";
		$res = pg_exec ($conn,$sql);

		if (pg_numrows($res) == 0) {
			echo "<h1>vendedor não encontrada</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}else{
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>vendedor</b>: <i>$cpf</i></font>";
		echo "<p>";

		$sql = "SELECT		tbl_vendedor.vendedor     as revenda               ,
							tbl_vendedor.nome              ,
							tbl_vendedor.cnpj              ,
							tbl_vendedor.cidade            ,
							tbl_vendedor.fone              ,
							tbl_vendedor.endereco          ,
							'' as numero                   ,
							'' as complemento              ,
							tbl_vendedor.bairro            ,
							tbl_vendedor.cep               ,
							tbl_vendedor.email             ,
							tbl_cidade.descricao AS nome_cidade,
							tbl_cidade.estado
				FROM        tbl_vendedor
				LEFT JOIN   tbl_cidade USING (cidade)
				ORDER BY    tbl_vendedor.nome";
		$res = pg_exec ($conn,$sql);

		if (pg_numrows($res) == 0) {
			echo "<h1>vendedor não encontrada</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}
}else{
	$tipo = $_GET['tipo'];
	switch($tipo) {
	case 'nome':
		$nome = strtoupper (trim ($_GET["nome"]));
		$cond = " tbl_revenda.nome ILIKE '%$nome%' ";
		break;
	case 'cnpj':
		$nome = strtoupper (trim ($_GET["cnpj"]));
		$cond = " tbl_revenda.cnpj ILIKE '%$nome%' ";
		break;
	case 'cidade':
		$nome = strtoupper (trim ($_GET["cidade"]));
		$cond = " tbl_cidade.nome ILIKE '%$nome%' ";
		break;
	}

	if($login_fabrica == 161){ //hd_chamado=3120493
		$join_revenda_fabrica = " JOIN tbl_revenda_fabrica ON tbl_revenda_fabrica.revenda = tbl_revenda.revenda ";
		$cond_revenda_fabrica = " AND tbl_revenda_fabrica.fabrica = $login_fabrica ";
	}

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>".strtoupper($tipo)." da Revenda</b>: <i>$nome</i></font>";
	echo "<p>";

	$sql = "SELECT      tbl_revenda.revenda                    ,
		tbl_revenda.nome                       ,
		tbl_revenda.revenda                    ,
		tbl_revenda.cnpj                       ,
		tbl_revenda.cidade                     ,
		tbl_revenda.fone                       ,
		tbl_revenda.endereco                   ,
		tbl_revenda.numero                     ,
		tbl_revenda.complemento                ,
		tbl_revenda.bairro                     ,
		tbl_revenda.cep                        ,
		tbl_revenda.email                      ,
		tbl_cidade.nome         AS nome_cidade ,
		tbl_cidade.estado
		FROM        tbl_revenda
		LEFT JOIN   tbl_cidade USING (cidade)
		$join_revenda_fabrica
		WHERE  $cond
		$cond_revenda_fabrica
		ORDER BY    tbl_cidade.estado,tbl_cidade.nome,tbl_revenda.bairro,tbl_revenda.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Revenda '$nome' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1) {
	echo "<script language='JavaScript'>";
	echo "nome.value        ='".pg_result($res,0,nome)."'; ";
	echo "revenda.value     ='".pg_result($res,0,revenda)."'; ";
	echo "cidade.value      ='".pg_result($res,0,nome_cidade)."'; ";
	echo "fone.value        ='".pg_result($res,0,fone)."'; ";
	echo "endereco.value    ='".pg_result($res,0,endereco)."'; ";
	echo "numero.value      ='".pg_result($res,0,numero)."'; ";
	echo "complemento.value ='".pg_result($res,0,complemento)."'; ";
	echo "bairro.value      ='".pg_result($res,0,bairro)."'; ";
	echo "estado.value      ='".pg_result($res,0,estado)."'; ";
	echo "this.close(); ";
	echo "</script>";
	exit;
}

if (pg_numrows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$nome        = trim(pg_result($res,$i,nome));
		$cnpj        = trim(pg_result($res,$i,cnpj));
		$cidade      = trim(pg_result($res,$i,nome_cidade));
		$fone        = trim(pg_result($res,$i,fone));
		$endereco    = trim(pg_result($res,$i,endereco));
		$numero      = trim(pg_result($res,$i,numero));
		$complemento = trim(pg_result($res,$i,complemento));
		$bairro      = trim(pg_result($res,$i,bairro));
		$cep         = trim(pg_result($res,$i,cep));
		$estado      = trim(pg_result($res,$i,estado));
		$revenda       = trim(pg_result($res,$i,revenda));

		echo "<tr>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$cnpj</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<a href=\"javascript: nome.value='$nome'; cidade.value='$cidade'; fone.value='$fone'; endereco.value='$endereco'; numero.value='$numero'; complemento.value='$complemento'; bairro.value='$bairro'; estado.value='$estado'; revenda.value='$revenda';";
		if ($_GET["proximo"] == "t" ) { echo "proximo.focus(); "; }
		echo "this.close(); \">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$bairro</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$cidade</font>\n";
		echo "</td>\n";




		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>
