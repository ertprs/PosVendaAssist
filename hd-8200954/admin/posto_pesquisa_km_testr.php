<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_produtos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>

<style type='text/css'>
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

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

10:21 30/07/2010
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}


</style>

<title> Pesquisa Postos... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<br>
<table class='tabela' width='100%'>
<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "nome") {
	$nome = trim (strtoupper($_GET["campo"]));
	
	/*echo "<tr class='titulo_tabela'>
					<td colspan='4'>
						Pesquisando por nome do posto: <i>$nome</i>
					</td>
		  </tr>";*/
	
	$sql = "SELECT   tbl_posto.*, 
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.contato_endereco, 
					tbl_posto_fabrica.contato_numero,
					tbl_posto_fabrica.contato_bairro,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.contato_cep
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    (tbl_posto.nome ILIKE '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<td>Posto '$nome' não encontrado</td>>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "codigo") {
	$codigo_posto = trim (strtoupper($_GET["campo"]));
	$codigo_posto = str_replace (".","",$codigo_posto);
	$codigo_posto = str_replace (",","",$codigo_posto);
	$codigo_posto = str_replace ("-","",$codigo_posto);
	$codigo_posto = str_replace ("/","",$codigo_posto);

	/*echo "<tr class='titulo_tabela'>
			<td width='100%'>
				Pesquisando por código do posto $codigo_posto
			</td>
		</tr>";	*/
	$sql = "SELECT    tbl_posto.*, 
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.contato_endereco, 
					tbl_posto_fabrica.contato_numero,
					tbl_posto_fabrica.contato_bairro,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.contato_cep
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto_fabrica.codigo_posto ilike '%$codigo_posto%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";

	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Posto '$codigo_posto' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1) {
	echo "<script language='javascript'>";
	echo "nome.value   = '".str_replace ('"','',trim(pg_result($res,0,nome)))."';";
	echo "codigo.value = '".trim(pg_result($res,0,codigo_posto))."';";
	$contato_endereco= trim(pg_result($res,$i,contato_endereco));
	$contato_numero  = trim(pg_result($res,$i,contato_numero));
	$contato_bairro  = trim(pg_result($res,$i,contato_bairro));
	$contato_cidade  = trim(pg_result($res,$i,contato_cidade));
	$contato_estado  = trim(pg_result($res,$i,contato_estado));
	$contato_cep     = trim(pg_result($res,$i,contato_cep));

	echo "contato_endereco.value = '$contato_endereco';contato_numero.value = '$contato_numero';contato_bairro.value = '$contato_bairro';contato_cidade.value = '$contato_cidade';contato_estado.value = '$contato_estado';if (window.contato_cep) { contato_cep.value = '$contato_cep'; }";

	if ($_GET["proximo"] == "t") echo "proximo.focus();";
	echo "this.close();";
	echo "</script>";
	exit;
}

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' class='tabela' border='0' cellspancing='1'>\n";
	if($tipo == "nome"){
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='4'>";
		echo "Pesquisando por nome do posto: $nome";
		echo "</tr>";
		echo "</tr>";
	}
	elseif($tipo=="codigo"){
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='4'>";
		echo "Pesquisando por código do posto $codigo_posto";
		echo "</tr>";
		echo "</tr>";
	}
	echo "<tr class='titulo_coluna' align='left'>
				<td>CPF</td>
				<td>Nome</td>
				<td>Cidade</td>
				<td>Estado</td>
			 </tr>";
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$codigo_posto=trim(pg_result($res,$i,codigo_posto));
		$posto      = trim(pg_result($res,$i,posto));
		$nome       = trim(pg_result($res,$i,nome));
		$cnpj       = trim(pg_result($res,$i,cnpj));
		$cidade     = trim(pg_result($res,$i,cidade));
		$estado     = trim(pg_result($res,$i,estado));
		
		$nome = str_replace ('"','',$nome);
		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);

		$contato_endereco= trim(pg_result($res,$i,contato_endereco));
		$contato_numero  = trim(pg_result($res,$i,contato_numero));
		$contato_bairro  = trim(pg_result($res,$i,contato_bairro));
		$contato_cidade  = trim(pg_result($res,$i,contato_cidade));
		$contato_estado  = trim(pg_result($res,$i,contato_estado));
		$contato_cep     = trim(pg_result($res,$i,contato_cep));
		
		$cor = ( $i%2 == 0 ) ? '#F7F5F0' : '#F1F4FA';

		

		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>\n";
		echo "<b>$cnpj</b>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: nome.value = '$nome'; codigo.value = '$codigo_posto';contato_endereco.value = '$contato_endereco';contato_numero.value = '$contato_numero';contato_bairro.value = '$contato_bairro';contato_cidade.value = '$contato_cidade';contato_estado.value = '$contato_estado'; if (window.contato_cep) { contato_cep.value = '$contato_cep'; }";

//		echo "<a href=\"javascript: nome.value = '$nome'; codigo.value = '$codigo_posto';contato_endereco.value = '$contato_endereco';contato_numero.value = '$contato_numero';contato_bairro.value = '$contato_bairro';contato_cidade.value = $contato_cidade';contato_estado.value = '$contato_estado';";
		if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
		echo "this.close() ; \" >";
		echo "$nome\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "$cidade\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "$estado\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>
