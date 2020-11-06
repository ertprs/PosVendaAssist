<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Nº Série.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_revenda<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">

<?
//produto_serie
if (strlen($_GET["produto_serie"]) > 5) {
	$produto_serie = strtoupper (trim ($_GET["produto_serie"]));
	
	echo "<br><font face='Arial, Verdana, Times, Sans' size='2'>";
	echo "Pesquisando por <b>Serie do Produto</b>: ";
	echo "<i>$produto_serie</i></font>";
	echo "<p>";

	
	$sql = "SELECT 
				cnpj,
				referencia_produto,
				to_char(data_venda, 'dd/mm/yyyy') as data_venda,
				to_char(data_fabricacao, 'dd/mm/yyyy') as data_fabricacao
			FROM tbl_numero_serie  
			WHERE fabrica = $login_fabrica
				AND serie = trim('$produto_serie')";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Nº Série '$produto_serie' não encontrado. Favor preencher as informações de produto e de revenda manualmente. </h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

}else{
	echo "<h1>Digite ao menos 6 digitos para o número de série.</h1>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;
}


if (pg_numrows ($res) > 0 ) {

	$cnpj_revenda       = trim(pg_result($res,0,cnpj));
	$referencia_produto = trim(pg_result($res,0,referencia_produto));
	$data_venda         = trim(pg_result($res,0,data_venda));
	$data_fabricacao    = trim(pg_result($res,0,data_fabricacao));

	$referencia_produto = str_replace (".","",$referencia_produto);
	$referencia_produto = str_replace (",","",$referencia_produto);
	$referencia_produto = str_replace ("-","",$referencia_produto);
	$referencia_produto = str_replace ("/","",$referencia_produto);

	$sql = "
			SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = $login_fabrica
			JOIN     tbl_familia ON tbl_familia.familia = tbl_produto.familia and tbl_familia.fabrica = $login_fabrica
			WHERE    tbl_produto.referencia_pesquisa = '$referencia_produto'
			AND      tbl_linha.ativo IS TRUE
			AND      tbl_familia.ativo IS TRUE
			AND      tbl_produto.ativo IS TRUE
			AND      tbl_produto.produto_principal ";

	$res_produto = pg_exec ($con,$sql);

	if (pg_numrows ($res_produto) == 0) {
		echo "<h1>A série foi encontrada, mas o produto '$referencia' não está cadastrado na Telecontrol, entrar em contato com a Fábrica.</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
		
	$produto    = trim(pg_result($res_produto,0,produto));
	$descricao  = trim(pg_result($res_produto,0,descricao));
	$voltagem   = trim(pg_result($res_produto,0,voltagem));
	$referencia = trim(pg_result($res_produto,0,referencia));
	$descricao = str_replace ('"','',$descricao);
	$descricao = str_replace ("'","",$descricao);
	/*echo "<script language='JavaScript'>\n";
	echo "referencia.value = '$referencia' ;";
	echo "descricao.value = '$descricao' ;";
	echo "voltagem.value = '$voltagem';";
	echo "descricao.focus();";
	//echo "this.close();";
	echo "</script>\n";*/




	$sql = "SELECT      tbl_revenda.nome              ,
						tbl_revenda.revenda           ,
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
			LEFT JOIN   tbl_estado using(estado)
			WHERE       tbl_revenda.cnpj ='$cnpj_revenda' ";

	$res_revenda = pg_exec ($con,$sql);

	if (pg_numrows ($res_revenda) == 0) {
		echo "<h1>Revenda não encontrada para a série: '$produto_serie'.</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}

	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";	

	$revenda    = trim(pg_result($res_revenda,0,revenda));
	$nome       = trim(pg_result($res_revenda,0,nome));
	$cnpj       = trim(pg_result($res_revenda,0,cnpj));
	$bairro     = trim(pg_result($res_revenda,0,bairro));
	$cidade     = trim(pg_result($res_revenda,0,nome_cidade));

	echo "<tr>\n";	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$cnpj</font>\n";
	echo "</td>\n";
	
	echo "<td>\n";
	if ($_GET['forma'] == 'reload') {
		echo "<a href=\"javascript: opener.document.location = retorno + '?revenda=$revenda' ; this.close() ;\" > " ;
	}else{
		echo "<a href=\"javascript: nome.value='" . pg_result ($res_revenda,0,nome) . "' ; cnpj.value = '" . pg_result ($res_revenda,0,cnpj) . "' ; cidade.value='" . $cidade . "' ; fone.value='" . pg_result ($res_revenda,0,fone) . "' ; endereco.value='" . pg_result ($res_revenda,0,endereco) . "' ; numero.value='" . pg_result ($res_revenda,0,numero) . "' ; complemento.value='" . pg_result ($res_revenda,0,complemento) . "' ; bairro.value='" . pg_result ($res_revenda,0,bairro) . "' ; cep.value='" . pg_result ($res_revenda,0,cep) . "' ; estado.value='" . pg_result ($res_revenda,0,estado) . "'; email.value='" . pg_result ($res_revenda,0,email) . "' ; 
		txt_nome.value='" . pg_result ($res_revenda,0,nome) . "' ;
		txt_cnpj.value = '" . pg_result ($res_revenda,0,cnpj) . "' ; txt_cidade.value='" . $cidade . "' ; txt_fone.value='" . pg_result ($res_revenda,0,fone) . "' ; txt_endereco.value='" . pg_result ($res_revenda,0,endereco) . "' ; txt_numero.value='" . pg_result ($res_revenda,0,numero) . "' ; txt_complemento.value='" . pg_result ($res_revenda,0,complemento) . "' ; txt_bairro.value='" . pg_result ($res_revenda,0,bairro) . "' ; txt_cep.value='" . pg_result ($res_revenda,0,cep) . "' ; txt_estado.value='" . pg_result ($res_revenda,0,estado) . "'; txt_data_venda.value='" . $data_venda . "'; 
		produto_referencia.value='" . pg_result ($res_produto,0,referencia) . "'; produto_descricao.value='" . pg_result ($res_produto,0,descricao) . "' ; produto_voltagem.value='" . pg_result ($res_produto,0,voltagem) . "' ; data_fabricacao.value='".$data_fabricacao."' ; ";
		if ($_GET['revenda_fixo']){
			echo " if (revenda_fixo) { revenda_fixo.style.display='block'; } ";
		}
		echo "this.close(); \">\n";
	}

	echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$nome</font>\n";
	echo "</a>\n";
	echo "</td>\n";	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$bairro</font>\n";
	echo "</td>\n";
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$cidade</font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}
?>
</body>
</html>
