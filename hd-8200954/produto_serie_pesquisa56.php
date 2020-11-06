<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

if (strlen($_GET["form"]) > 0)	$form = trim($_GET["form"]);

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<script language="JavaScript">
<!--
function retorno(referencia, descricao, serie) {
	f = opener.window.document.<? echo $form; ?>;
	f.produto_referencia.value = referencia;
	f.produto_descricao.value  = descricao;
	f.produto_serie.value      = serie;
	window.close();
}
// -->
</script>

</head>

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<img src="imagens/pesquisa_produto.gif">

<br>

<?

$serie = trim (strtoupper($_GET["campo"]));
$posto = '6359';

echo "<h4>Pesquisando por <b>número de série do produto</b>: <i>$serie</i></h4>";
echo "<p>";

$sql =	"SELECT tbl_os.sua_os                                                    ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura      ,
				tbl_os.consumidor_nome                                           ,
				tbl_os.consumidor_cpf                                            ,
				tbl_os.consumidor_cidade                                         ,
				tbl_os.consumidor_fone                                           ,
				tbl_os.consumidor_celular                                        ,
				tbl_os.consumidor_fone_comercial                                 ,
				tbl_os.consumidor_estado                                         ,
				tbl_os.consumidor_endereco                                       ,
				tbl_os.consumidor_numero                                         ,
				tbl_os.consumidor_complemento                                    ,
				tbl_os.consumidor_bairro                                         ,
				tbl_os.consumidor_cep                                            ,
				tbl_os.consumidor_email                                          ,
				tbl_os.revenda_cnpj                                              ,
				tbl_os.revenda_nome                                              ,
				tbl_revenda.fone                           AS revenda_fone       ,
				tbl_revenda.cep                            AS revenda_cep        ,
				tbl_revenda.endereco                       AS revenda_endereco   ,
				tbl_revenda.numero                         AS revenda_numero     ,
				tbl_revenda.complemento                    AS revenda_complemento,
				tbl_revenda.bairro                         AS revenda_bairro     ,
				tbl_cidade.nome                            AS revenda_cidade     ,
				tbl_cidade.estado                          AS revenda_estado     ,
				tbl_os.revenda                                                   ,
				tbl_os.nota_fiscal                                               ,
				to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf            ,
				tbl_os.consumidor_revenda                                        ,
				tbl_os.defeito_reclamado_descricao                               ,
				tbl_os.aparencia_produto                                         ,
				tbl_os.codigo_fabricacao                                         ,
				tbl_os.type                                                      ,
				tbl_os.satisfacao                                                ,
				tbl_os.laudo_tecnico                                             ,
				tbl_os.tipo_os_cortesia                                          ,
				tbl_os.serie                                                     ,
				tbl_os.qtde_produtos                                             ,
				tbl_os.troca_faturada                                            ,
				tbl_os.acessorios                                                ,
				tbl_os.tipo_os                                                   ,
				tbl_produto.referencia                     AS produto_referencia ,
				tbl_produto.descricao                      AS produto_descricao  ,
				tbl_produto.voltagem                       AS produto_voltagem   ,
				tbl_posto_fabrica.codigo_posto                                   ,
				tbl_os.prateleira_box
		FROM tbl_os
		JOIN      tbl_produto  ON tbl_produto.produto       = tbl_os.produto
		JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $posto
		LEFT JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
		LEFT JOIN tbl_cidade  ON tbl_revenda.cidade = tbl_cidade.cidade
		WHERE    tbl_os.fabrica = $login_fabrica
		AND      tbl_os.serie = '$serie'
		LIMIT 1;";
$res = pg_exec ($con,$sql);
//echo "$sql";
if (@pg_numrows ($res) == 0) {
	echo "<h1>Produto '$descricao' não encontrado</h1>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;
}

if (pg_numrows ($res) == 1 ) {
	$data_abertura              = pg_result ($res,0,data_abertura);
	$consumidor_nome            = pg_result ($res,0,consumidor_nome);
	$consumidor_cpf             = pg_result ($res,0,consumidor_cpf);
	$consumidor_cidade          = pg_result ($res,0,consumidor_cidade);
	$consumidor_fone            = pg_result ($res,0,consumidor_fone);
	$consumidor_celular         = pg_result ($res,0,consumidor_celular);
	$consumidor_fone_comercial  = pg_result ($res,0,consumidor_fone_comercial);
	$consumidor_estado          = pg_result ($res,0,consumidor_estado);
	$consumidor_endereco        = pg_result ($res,0,consumidor_endereco);
	$consumidor_numero          = pg_result ($res,0,consumidor_numero);
	$consumidor_complemento     = pg_result ($res,0,consumidor_complemento);
	$consumidor_bairro          = pg_result ($res,0,consumidor_bairro);
	$consumidor_cep             = pg_result ($res,0,consumidor_cep);
	$consumidor_email           = pg_result ($res,0,consumidor_email);
	$revenda_cnpj               = pg_result ($res,0,revenda_cnpj);
	$revenda_nome               = pg_result ($res,0,revenda_nome);
	$revenda_fone               = pg_result ($res,0,revenda_fone);
	$revenda_cep                = pg_result ($res,0,revenda_cep);
	$revenda_endereco           = pg_result ($res,0,revenda_endereco);
	$revenda_numero             = pg_result ($res,0,revenda_numero);
	$revenda_complemento        = pg_result ($res,0,revenda_complemento);
	$revenda_bairro             = pg_result ($res,0,revenda_bairro);
	$revenda_cidade             = pg_result ($res,0,revenda_cidade);
	$revenda_estado             = pg_result ($res,0,revenda_estado);
	$nota_fiscal                = pg_result ($res,0,nota_fiscal);
	$data_nf                    = pg_result ($res,0,data_nf);
	$consumidor_revenda         = pg_result ($res,0,consumidor_revenda);
	$defeito_reclamado_descricao= pg_result ($res,0,defeito_reclamado_descricao);
	$aparencia_produto          = pg_result ($res,0,aparencia_produto);
	$acessorios                 = pg_result ($res,0,acessorios);
	$codigo_fabricacao          = pg_result ($res,0,codigo_fabricacao);
	$type                       = pg_result ($res,0,type);
	$satisfacao                 = pg_result ($res,0,satisfacao);
	$laudo_tecnico              = pg_result ($res,0,laudo_tecnico);
	$tipo_os_cortesia           = pg_result ($res,0,tipo_os_cortesia);
	$produto_serie              = pg_result ($res,0,serie);
	$qtde_produtos              = pg_result ($res,0,qtde_produtos);
	$produto_referencia         = pg_result ($res,0,produto_referencia);
	$produto_descricao          = pg_result ($res,0,produto_descricao);
	$produto_voltagem           = pg_result ($res,0,produto_voltagem);
	$troca_faturada             = pg_result ($res,0,troca_faturada);
	$codigo_posto               = pg_result ($res,0,codigo_posto);
	$tipo_os                    = pg_result ($res,0,tipo_os);
	$xxxrevenda                 = pg_result ($res,0, revenda);

	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "opener.window.document.$form.produto_referencia.value     = '$produto_referencia'; \n";
	echo "opener.window.document.$form.produto_descricao.value      = '$produto_descricao';  \n";
	echo "opener.window.document.$form.produto_serie.value          = '$produto_serie';     \n";

	echo "opener.window.document.$form.produto_voltagem.value       = '$produto_voltagem'; \n";
	echo "opener.window.document.$form.data_abertura.value          = '$data_abertura';  \n";
	echo "opener.window.document.$form.nota_fiscal.value            = '$nota_fiscal';     \n";
	echo "opener.window.document.$form.data_nf.value                = '$data_nf'; \n";
	echo "opener.window.document.$form.defeito_reclamado_descricao.value  = '$defeito_reclamado_descricao';  \n";
	echo "opener.window.document.$form.consumidor_nome.value        = '$consumidor_nome';     \n";
	echo "opener.window.document.$form.consumidor_cpf.value         = '$consumidor_cpf'; \n";
	echo "opener.window.document.$form.consumidor_fone.value        = '$consumidor_fone';  \n";
	echo "opener.window.document.$form.consumidor_cep.value         = '$consumidor_cep';     \n";

	echo "opener.window.document.$form.consumidor_endereco.value    = '$consumidor_endereco';     \n";
	echo "opener.window.document.$form.consumidor_numero.value      = '$consumidor_numero'; \n";
	echo "opener.window.document.$form.consumidor_complemento.value = '$consumidor_complemento';  \n";
	echo "opener.window.document.$form.consumidor_bairro.value      = '$consumidor_bairro';     \n";
	echo "opener.window.document.$form.consumidor_cidade.value      = '$consumidor_cidade';     \n";
	echo "opener.window.document.$form.consumidor_estado.value      = '$consumidor_estado'; \n";
	echo "opener.window.document.$form.consumidor_email.value       = '$consumidor_email'; \n";
	echo "opener.window.document.$form.revenda_nome.value           = '$revenda_nome';  \n";
	echo "opener.window.document.$form.revenda_cnpj.value           = '$revenda_cnpj';     \n";

	echo "opener.window.document.$form.revenda_fone.value           = '$revenda_fone';     \n";
	echo "opener.window.document.$form.revenda_cep.value            = '$revenda_cep';     \n";
	echo "opener.window.document.$form.revenda_endereco.value       = '$revenda_endereco'; \n";
	echo "opener.window.document.$form.revenda_numero.value         = '$revenda_numero';  \n";
	echo "opener.window.document.$form.revenda_complemento.value    = '$revenda_complemento';     \n";
	echo "opener.window.document.$form.revenda_bairro.value         = '$revenda_bairro';     \n";
	echo "opener.window.document.$form.revenda_cidade.value         = '$revenda_cidade';     \n";
	echo "opener.window.document.$form.revenda_estado.value         = '$revenda_estado'; \n";
	echo "opener.window.document.$form.aparencia_produto.value      = '$aparencia_produto';  \n";
	echo "opener.window.document.$form.acessorios.value             = '$acessorios';     \n";

	echo "window.close();\n";
	echo "// -->\n";
	echo "</script>\n";
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "window.moveTo (100,100);";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$descricao  = str_replace ('"','',$descricao);
		$serie      = trim(pg_result($res,$i,radical_serie));

		echo "<tr>";
		
		echo "<td>";
		echo "<a href=\"javascript: retorno('$referencia', '$descricao', '$serie')\">";
		echo "<font size='-1'>$referencia</font>";
		echo "</a>";
		echo "</td>";
		
		echo "<td>";
		echo "<font size='-1'>$descricao</font>";
		echo "</td>";

		echo "<td>";
		echo "<font size='-1'>$serie</font>";
		echo "</td>";
		
		echo "</tr>";
	}

	echo "</table>";
}

?>

</body>
</html>