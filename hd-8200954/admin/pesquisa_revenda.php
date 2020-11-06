<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Revendedores.. </title>
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
<img src="imagens/pesquisa_revenda<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">

<?

if (strlen($_GET["nome"]) > 0) {
	$nome = strtoupper (trim ($_GET["nome"]));

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome da Revenda</b>: <i>$nome</i></font>";
	//echo "<p>";

	$sql = "SELECT DISTINCT lpad(tbl_revenda.cnpj, 14, '0') AS cnpj ,
						tbl_revenda.nome                       ,
						tbl_revenda.revenda                    ,
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
			WHERE       tbl_revenda.nome ILIKE '%$nome%' AND tbl_revenda.cnpj_validado IS TRUE";
			if ($login_fabrica == 1)  $sql .= " AND   tbl_revenda.cnpj IS NOT NULL AND ativo IS NOT FALSE ";
			$sql .= " ORDER BY    tbl_cidade.estado,tbl_cidade.nome,tbl_revenda.bairro,tbl_revenda.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Revenda '$nome' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

}elseif (strlen($_GET["cnpj"]) > 0) {
	$nome = strtoupper (trim ($_GET["cnpj"]));
	$nome = str_replace ("-","",$nome);
	$nome = str_replace (".","",$nome);
	$nome = str_replace ("/","",$nome);
	$nome = str_replace (" ","",$nome);

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ da Revenda</b>: <i>$cpf</i></font>";
	//echo "<p>";

	$sql = "SELECT DISTINCT lpad(tbl_revenda.cnpj, 14, '0') AS cnpj ,
						tbl_revenda.nome              ,
						tbl_revenda.cidade            ,
						tbl_revenda.fone              ,
						tbl_revenda.endereco          ,
						tbl_revenda.numero            ,
						tbl_revenda.complemento       ,
						tbl_revenda.bairro            ,
						tbl_revenda.cep               ,
						tbl_revenda.email             ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado
			FROM        tbl_revenda
			LEFT JOIN   tbl_cidade USING (cidade)
			WHERE       tbl_revenda.cnpj ILIKE '%$nome%' AND tbl_revenda.cnpj_validado IS TRUE";
			if ($login_fabrica == 1)  $sql .= " AND   tbl_revenda.cnpj IS NOT NULL AND ativo IS NOT FALSE";
			$sql .= " ORDER BY    tbl_revenda.nome";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>C.N.P.J. '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}else{
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Revenda</b>: <i>$cpf</i></font>";
	//echo "<p>";

	$sql = "SELECT DISTINCT lpad(tbl_revenda.cnpj, 14, '0') AS cnpj ,
						tbl_revenda.nome              ,
						tbl_revenda.cnpj              ,
						tbl_revenda.cidade            ,
						tbl_revenda.fone              ,
						tbl_revenda.endereco          ,
						tbl_revenda.numero            ,
						tbl_revenda.complemento       ,
						tbl_revenda.bairro            ,
						tbl_revenda.cep               ,
						tbl_revenda.email             ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado
			FROM        tbl_revenda
			LEFT JOIN   tbl_cidade USING (cidade)
			WHERE		tbl_revenda.cnpj_validado IS TRUE
			ORDER BY    tbl_revenda.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) == 0) {
		echo "<h1>Revenda não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1) {
	echo "<script language='JavaScript'>";
	echo "nome.value        ='".pg_result($res,0,nome)."'; ";
	echo "cnpj.value        ='".pg_result($res,0,cnpj)."'; ";
    if (empty($_GET['retorna_nome_cnpj'])) {
        echo "cidade.value      ='".pg_result($res,0,cidade)."'; ";
        echo "fone.value        ='".pg_result($res,0,fone)."'; ";
        echo "endereco.value    ='".pg_result($res,0,endereco)."'; ";
        echo "numero.value      ='".pg_result($res,0,numero)."'; ";
        echo "complemento.value ='".pg_result($res,0,complemento)."'; ";
        echo "bairro.value      ='".pg_result($res,0,bairro)."'; ";
        echo "cep.value         ='".pg_result($res,0,cep)."'; ";
        echo "estado.value      ='".pg_result($res,0,estado)."'; ";
        echo "email.value       ='".pg_result($res,0,email)."'; ";
    }
	if ($_GET["proximo"] == "t" ) echo "proximo.focus(); ";
	echo "this.close(); ";
	echo "</script>";
	exit;
}

if (pg_numrows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "var nome;
	var cnpj;
	var cnpj_raiz;
	var cidade;
	var fone;
	var endereco;
	var numero;
	var complemento;
	var bairro;
	var cep;
	var estado;
	var email;
	";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0' cellspacing='1' class='tabela'>\n";
	if(strlen($_GET["nome"]))
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>nome da Revenda</b>: $nome</font></td></tr>";
	elseif(strlen($_GET["cnpj"]))
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>CNPJ da Revenda</b>: $cpf</font></td></tr>";
	else
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>esquisando por <b>Revenda</b>: $cpf</font></td></tr>";

	echo "<tr class='titulo_coluna'><td>Cnpj</td><td>Nome</td><td>Bairro</td><td>Cidade</td>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$nome        = trim(pg_result($res,$i,nome));
		$cnpj        = trim(pg_result($res,$i,cnpj));
		$cnpj_raiz   = trim(substr($cnpj,0,8));
		$cidade      = trim(pg_result($res,$i,nome_cidade));
		$fone        = trim(pg_result($res,$i,fone));
		$endereco    = trim(pg_result($res,$i,endereco));
		$numero      = trim(pg_result($res,$i,numero));
		$complemento = trim(pg_result($res,$i,complemento));
		$bairro      = trim(pg_result($res,$i,bairro));
		$cep         = trim(pg_result($res,$i,cep));
		$estado      = trim(pg_result($res,$i,estado));
		$email       = trim(pg_result($res,$i,email));

		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";

		echo "<tr bgcolor='$cor'>\n";

		echo "<td>\n";
			echo (strlen($cnpj) > 0) ? $cnpj : "&nbsp;";
		echo "</td>\n";

		echo "<td>\n";
        if (empty($_GET['retorna_nome_cnpj'])) {
        	echo "<a href=\"javascript: nome.value='$nome'; cnpj.value='$cnpj'; if(cnpj_raiz) { cnpj_raiz.value='$cnpj_raiz'; } cidade.value='$cidade'; fone.value='$fone'; endereco.value='$endereco'; numero.value='$numero'; complemento.value='$complemento'; bairro.value='$bairro'; cep.value='$cep'; estado.value='$estado'; email.value='$email'; ";
        } else {
            echo "<a href=\"javascript: nome.value='$nome'; cnpj.value='$cnpj'; ";
        }
		if ($_GET["proximo"] == "t" ) { echo " proximo.focus(); "; }
		echo "window.close(); \">\n";
		echo "$nome";
		echo "</a>\n";
		echo "</td>\n";

		echo "<td>\n";
			echo (strlen($bairro) > 0) ? $bairro : "&nbsp;";
		echo "</td>\n";

		echo "<td>\n";
			echo (strlen($cidade) > 0) ? $cidade : "&nbsp;";
		echo "</td>\n";

		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>
