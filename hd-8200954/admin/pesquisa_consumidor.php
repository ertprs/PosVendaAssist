<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Consumidores.. </title>
<meta http-equiv=pragma content=no-cache>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font: bold 11px "Arial";
	border-collapse: collapse;
	border:1px solid #596d9b;
}


</style>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor.gif">

<?

if (strlen($_GET["nome"]) > 0) {
	$nome = strtoupper (trim ($_GET["nome"]));

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do consumidor</b>: <i>$nome</i></font>";
	//echo "<p>";
	if($login_fabrica==7){
		$sql = "SELECT  tbl_posto.nome        AS nome       ,
						tbl_posto.cnpj        AS cpf        ,
						tbl_posto.ie          AS rg         ,
						tbl_posto.endereco    AS endereco   ,
						tbl_posto.numero      AS numero     ,
						tbl_posto.complemento AS complemento,
						tbl_posto.fone        AS fone       ,
						tbl_posto.cep         AS cep        ,
						tbl_posto.bairro      AS bairro     ,
						tbl_posto.cidade      AS nome_cidade,
						tbl_posto.estado      AS estado     ,
						tbl_posto.posto       AS cliente
				FROM  tbl_posto
				JOIN  tbl_posto_consumidor USING(posto)
				WHERE  tbl_posto.nome     ILIKE '%$nome%'
				AND   tbl_posto_consumidor.fabrica = $login_fabrica
				ORDER BY tbl_posto.nome";
	}else{
		$sql = "SELECT      tbl_cliente.*                 ,
							tbl_cidade.nome AS nome_cidade,
							tbl_cidade.estado             ,
							tbl_cliente_contato.fone
				FROM        tbl_cliente
				LEFT JOIN   tbl_cidade USING (cidade)
				LEFT JOIN   tbl_cliente_contato USING (cliente)
				WHERE       tbl_cliente.nome ILIKE '%$nome%'
				ORDER BY    tbl_cliente.nome";

	}
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {

		$sql = "SELECT '' AS cliente,
						consumidor_nome AS nome,
						consumidor_cpf AS cpf,
						'' AS rg,
						consumidor_cidade AS nome_cidade,
						consumidor_estado AS estado,
						consumidor_fone AS fone,
						consumidor_endereco AS endereco,
						consumidor_numero AS numero,
						consumidor_cep AS cep,
						consumidor_complemento AS complemento,
						consumidor_bairro AS bairro
				FROM tbl_os where fabrica = $login_fabrica
				AND consumidor_nome ILIKE '$nome%'
				AND excluida IS NOT TRUE
				AND tbl_os.data_digitacao >= current_date - interval '1 year'
				";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == 0){
			echo "<h1>Consumidor '$nome' não encontrado</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}
}elseif (strlen($_GET["cpf"]) > 0) {
	$cpf = strtoupper (trim ($_GET["cpf"]));
	$cpf = str_replace (".","",$cpf);
	$cpf = str_replace ("-","",$cpf);
	$cpf = str_replace ("/","",$cpf);
	$cpf = str_replace (" ","",$cpf);

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CPF do consumidor</b>: <i>$cpf</i></font>";
	//echo "<p>";
	if($login_fabrica==7){
		$sql = "SELECT  tbl_posto.nome        AS nome       ,
						tbl_posto.cnpj        AS cpf        ,
						tbl_posto.ie          AS rg         ,
						tbl_posto.endereco    AS endereco   ,
						tbl_posto.numero      AS numero     ,
						tbl_posto.complemento AS complemento,
						tbl_posto.fone        AS fone       ,
						tbl_posto.cep         AS cep        ,
						tbl_posto.bairro      AS bairro     ,
						tbl_posto.cidade      AS nome_cidade,
						tbl_posto.estado      AS estado     ,
						tbl_posto.posto       AS cliente
				FROM  tbl_posto
				JOIN  tbl_posto_consumidor USING(posto)
				WHERE  tbl_posto.cnpj      ILIKE '%$cpf%'
				AND   tbl_posto_consumidor.fabrica = $login_fabrica
				ORDER BY tbl_posto.nome";
	}else{
		$sql = "SELECT      tbl_cliente.*                 ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado             ,
						tbl_cliente_contato.fone
			FROM        tbl_cliente
			LEFT JOIN   tbl_cidade USING (cidade)
			LEFT JOIN   tbl_cliente_contato USING (cliente)
			WHERE       tbl_cliente.cpf ILIKE '%$cpf%'
			ORDER BY    tbl_cliente.nome";

	}
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {

		$sql = "SELECT '' AS cliente,
					consumidor_nome AS nome,
					consumidor_cpf AS cpf,
					'' AS rg,
					consumidor_cidade AS nome_cidade,
					consumidor_estado AS estado,
					consumidor_fone AS fone,
					consumidor_endereco AS endereco,
					consumidor_numero AS numero,
					consumidor_cep AS cep,
					consumidor_complemento AS complemento,
					consumidor_bairro AS bairro
				FROM tbl_os where fabrica = $login_fabrica
				AND consumidor_cpf = '$cpf'
				AND excluida IS NOT TRUE
				ORDER BY os DESC LIMIT 1";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == 0){
			echo "<h1>C.P.F. '$cpf' não encontrado</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}
}

if (pg_numrows($res) == 1 and $login_fabrica<>7) {
	echo "<script language='javascript'>";
	echo "cliente.value     ='".pg_result($res,0,cliente)."'; ";
	echo "hidden_consumidor_nome.value     ='".pg_result($res,0,cliente)."'; ";
	echo "nome.value        ='".str_replace("'","",pg_result($res,0,nome))."'; ";
	echo "cpf.value         ='".pg_result($res,0,cpf)."'; ";
	echo "rg.value          ='".pg_result($res,0,rg)."'; ";
	echo "cidade.value      ='".pg_result($res,0,nome_cidade)."'; ";
	echo "fone.value        ='".pg_result($res,0,fone)."'; ";
	echo "endereco.value    ='".str_replace("'","",pg_result($res,0,endereco))."'; ";
	echo "numero.value      ='".pg_result($res,0,numero)."'; ";
	echo "complemento.value ='".pg_result($res,0,complemento)."'; ";
	echo "bairro.value      ='".pg_result($res,0,bairro)."'; ";
	echo "cep.value         ='".pg_result($res,0,cep)."'; ";
	echo "estado.value      ='".pg_result($res,0,estado)."'; ";
	if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
	echo "window.opener.changeInput();";
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

	echo "<table width='100%' border='0' cellspacing='1' class='tabela'>\n";
	if(strlen($nome) > 0)
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>nome do consumidor</b>: $nome</font></td></tr>";
	if(strlen($cpf) > 0)
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'Pesquisando por <b>CPF do consumidor</b>: $cpf</font></td></tr>";

	echo "<tr class='titulo_coluna'><td>CPF</td><td>Nome</td><td>Cidade</td>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$cliente    = trim(pg_result($res,$i,cliente));
		$nome       = trim(pg_result($res,$i,nome));
		$cpf        = trim(pg_result($res,$i,cpf));

		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";

		echo "<tr bgcolor='$cor'>\n";

		echo "<td>\n";
		echo "$cpf";
		echo "</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno+'?cliente=$cliente' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: cliente.value='" . pg_result ($res,$i,cliente) . "' ; hidden_consumidor_nome.value='" . pg_result ($res,$i,cliente) . "'; nome.value='" . str_replace ("'","",pg_result ($res,$i,nome)) . "' ; cpf.value = '" . pg_result ($res,$i,cpf) . "' ; rg.value='" . pg_result ($res,$i,rg) . "'; cidade.value='" . pg_result ($res,$i,nome_cidade) . "' ; fone.value='" . pg_result ($res,$i,fone) . "' ; endereco.value='" . str_replace("\"","",str_replace ("'","",pg_result ($res,$i,endereco))) . "' ; numero.value='" . pg_result ($res,$i,numero) . "' ; complemento.value='" . pg_result ($res,$i,complemento) . "' ; bairro.value='" . pg_result ($res,$i,bairro) . "' ; cep.value='" . pg_result ($res,$i,cep) . "' ; estado.value='" . pg_result ($res,$i,estado) . "' ; ";
			if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
			echo "window.opener.changeInput();";
			echo "this.close(); \">\n";
		}
		echo "$nome";
		echo "</a>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo  pg_result ($res,$i,nome_cidade) . "-" . pg_result ($res,$i,estado);
		echo "</td>\n";

		echo "</tr>\n";
	}
	echo "</table>\n";
}

?>


</body>
</html>
