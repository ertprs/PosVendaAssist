<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['os_sedex']) > 0)  $os_sedex = trim($_GET['os_sedex']);
if (strlen($_POST['os_sedex']) > 0) $os_sedex = trim($_POST['os_sedex']);

#------------ Le OS da Base de dados ------------#
if (strlen ($os_sedex) == 0) {
	echo "<SCRIPT LANGUAGE=\"JavaScript\">\n";
	echo "<!--\n";
	echo "window.close();\n";
	echo "//-->\n";
	echo "</SCRIPT>\n";
}else{
	$sql = "SELECT  tbl_os_sedex.posto_origem                       ,
					tbl_os_sedex.posto_destino                      ,
					tbl_admin.login                                 ,
					to_char(tbl_os_sedex.data, 'DD/MM/YYYY') AS data,
					tbl_os_sedex.despesas                           ,
					tbl_os_sedex.controle                           ,
					tbl_os_sedex.sua_os_destino                     ,
					to_char(tbl_os_sedex.finalizada, 'DD/MM/YYYY HH24:MI') AS finalizada
			FROM    tbl_os_sedex
			JOIN    tbl_admin USING (admin)
			WHERE   tbl_os_sedex.os_sedex     = $os_sedex
			AND     tbl_os_sedex.posto_origem = $login_posto
			AND     tbl_os_sedex.fabrica      = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$posto_origem   = trim (pg_result ($res,0,posto_origem));
		$posto_destino  = trim (pg_result ($res,0,posto_destino));
		$solicitante    = trim (pg_result ($res,0,login));
		$data           = trim (pg_result ($res,0,data));
		$despesas       = trim (pg_result ($res,0,despesas));
		$despesas       = number_format($despesas,2,',','.');
		$controle       = trim (pg_result ($res,0,controle));
		$sua_os_destino = trim (pg_result ($res,0,sua_os_destino));
		$finalizada     = trim (pg_result ($res,0,finalizada));

		// dados do posto origem
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_posto.cnpj,
						tbl_posto.ie,
						tbl_posto.fone,
						tbl_posto.endereco,
						tbl_posto.numero,
						tbl_posto.bairro,
						tbl_posto.cidade,
						tbl_posto.estado,
						tbl_posto.cep
				FROM    tbl_posto_fabrica
				JOIN    tbl_posto USING (posto)
				WHERE   tbl_posto_fabrica.posto   = $login_posto
				AND     tbl_posto_fabrica.fabrica = $login_fabrica";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$codigo_posto_origem = trim(pg_result($res1,0,codigo_posto));
			$nome_posto_origem   = trim(pg_result($res1,0,nome));
			$posto_endereco      = pg_result($res1,0,endereco);
			$posto_numero        = pg_result($res1,0,numero);
			$posto_bairro        = pg_result($res1,0,bairro);
			$posto_cidade        = pg_result($res1,0,cidade);
			$posto_estado        = pg_result($res1,0,estado);
			$posto_cep           = pg_result($res1,0,cep);
			$posto_fone          = pg_result($res1,0,fone);
			$posto_cnpj          = pg_result($res1,0,cnpj);
			$posto_ie            = pg_result($res1,0,ie);
		}

		// dados do posto destino
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_posto.endereco,
						tbl_posto.numero,
						tbl_posto.bairro,
						tbl_posto.cidade,
						tbl_posto.estado,
						tbl_posto.cep
				FROM    tbl_posto_fabrica
				JOIN    tbl_posto USING (posto)
				WHERE   tbl_posto_fabrica.posto   = $posto_destino
				AND     tbl_posto_fabrica.fabrica = $login_fabrica";
		$res2 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res2) > 0) {
			$codigo_posto_destino = trim(pg_result($res2,0,codigo_posto));
			$nome_posto_destino   = trim(pg_result($res2,0,nome));
			$endereco             = pg_result($res2,0,endereco);
			$numero               = pg_result($res2,0,numero);
			$bairro               = pg_result($res2,0,bairro);
			$cidade               = pg_result($res2,0,cidade);
			$estado               = pg_result($res2,0,estado);
			$cep                  = pg_result($res2,0,cep);
		}
	}
}

$title = "Ordem de Serviço Sedex - Impressão";
?>

<html>

<head>

	<title><? echo $title ?></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">

	<link type="text/css" rel="stylesheet" href="css/css_press.css">

</head>

<style type="text/css">

body {
	margin: 0px;
}

.titulo {
	font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 8px;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: dotted 1px #000000;
	/*border-right: dotted 1px #a0a0a0;*/
 	border-left: dotted 1px #000000;
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 1px #a0a0a0;
	border-left: dotted 1px #a0a0a0;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 1px #c0c0c0;
}

h2 {
	font:60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	color: #000000
}

</style>
<body>
<TABLE width="650px" border="0" cellspacing="1" cellpadding="0">
<TR class="titulo" style="text-align: center;">
<?
	if ($cliente_contrato == 'f') 
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	else
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";

?>
	<TD class="conteudo" rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"><br></TD>
	<TD style="font-size: 09px;"><? echo $nome_posto_origem ?></TD>
	<TD>DATA</TD>
	<TD>NÚMERO</TD>
</TR>

<TR class="titulo">
	<TD style="font-size: 09px; text-align: center; width: 350px; ">
<?
	########## CABECALHO COM DADOS DO POSTOS ########## 
	echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
	echo "CNPJ/CPF ".$posto_cnpj ." - IE/RG ".$posto_ie;
?>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
		<b><? echo $data; ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
		<? echo "<center><b> $os_sedex </b></center>"; ?>
	</TD>
</tr>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">

<TR>
	<TD class="titulo" colspan="<? echo $colspan ?>">Informações sobre a Ordem de Serviço</TD>
</TR>
</table>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<tr>
	<td class="titulo">Controle de objeto</td>
	<td class="titulo">Despesas</td>
	<td class="titulo">Solicitado por</td>
	<td class="titulo">Data</td>
<?
	if (strlen($finalizada) > 0) echo "<td class=\"titulo\">Finalizada em</td>";
?>
</tr>
<tr>
	<td class="conteudo"><? echo "$controle"; ?></td>
	<td class="conteudo"> R$ <? echo "$despesas"; ?></td>
	<td class="conteudo"><? echo strtoupper($solicitante); ?></td>
	<td class="conteudo"><? echo $data; ?></td>
<?
	if (strlen($finalizada) > 0) echo "<td class=\"conteudo\">$finalizada</td>";
?>
</tr>
</table>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<tr>
	<td colspan="2" class="titulo">Posto Origem da Mercadoria</td>
</tr>
<tr>
	<td width="25%" class="titulo">Código</td>
	<td width="75%" class="titulo">Nome</td>
</tr>
<tr>
	<td class="conteudo"><? echo $codigo_posto_origem ?></td>
	<td class="conteudo"><? echo $nome_posto_origem ?></td>
</tr>
</table>

<br>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<tr>
	<td colspan="3" width="100%" class="titulo">Posto Destino da Mercadoria</td>
</tr>
<tr>
	<td class="titulo">Código</td>
	<td class="titulo">Nome</td>
	<td class="titulo">Atender a OS</td>
</tr>
<tr>
	<td class="conteudo"><? echo $codigo_posto_destino ?></td>
	<td class="conteudo"><? echo $nome_posto_destino ?></td>
	<td class="conteudo"><? echo $sua_os_destino ?></td>
</tr>
<tr>
	<td colspan="2" class="titulo">Endereço</td>
	<td class="titulo">CEP</td>
</tr>
<tr>
	<td colspan="2" class="conteudo"><? echo $endereco.", ".$numero; ?></td>
	<td class="conteudo"><? echo $cep ?></td>
</tr>
<tr>
	<td class="titulo">Bairro</td>
	<td class="titulo">Cidade</td>
	<td class="titulo">UF</td>
</tr>
<tr>
	<td class="conteudo"><? echo $bairro; ?></td>
	<td class="conteudo"><? echo $cidade; ?></td>
	<td class="conteudo"><? echo $estado; ?></td>
</tr>
</table>

<br>

<?
if (strlen($os_sedex) > 0 AND strlen($erro) == 0) {

	##### P E Ç A S #####

	$sql =	"SELECT os_sedex_item
			FROM    tbl_os_sedex_item
			WHERE   os_sedex = $os_sedex";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<TABLE class=\"borda\" width=\"650px\" border=\"0\" cellspacing=\"2\" cellpadding=\"0\">\n";
		echo "<tr>\n";
		echo "<td colspan='5' class='titulo'>Peça(s) selecionada(s)</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='titulo'>Referência</td>\n";
		echo "<td class='titulo'>Descrição</td>\n";
		echo "<td class='titulo'>Qtde</td>\n";
		echo "<td class='titulo'>Preço</td>\n";
		echo "<td class='titulo'>Total</td>\n";
		echo "</tr>\n";

		$sql =	"SELECT tbl_os_sedex_item.qtde  ,
						tbl_os_sedex_item.preco ,
						tbl_peca.referencia     ,
						tbl_peca.descricao      
				FROM    tbl_os_sedex_item
				JOIN    tbl_peca USING (peca)
				WHERE   tbl_os_sedex_item.os_sedex = $os_sedex";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
				$referencia  = pg_result($res,$i,referencia);
				$descricao   = pg_result($res,$i,descricao);
				$qtde        = pg_result($res,$i,qtde);
				$preco       = pg_result($res,$i,preco);
				$total       = $qtde * $preco;
				$total_geral = $total_geral + $total;

				echo "<tr>\n";
				echo "<td class='conteudo'>$referencia</td>\n";
				echo "<td class='conteudo'>$descricao</td>\n";
				echo "<td class='conteudo'>$qtde</td>\n";
				echo "<td class='conteudo'> R$ ".number_format($preco,2,",",".")."</td>\n";
				echo "<td class='conteudo'> R$ ".number_format($total,2,",",".")."</td>\n";
				echo "</tr>\n";
			}
			echo "<tr>\n";
			echo "<td colspan='4' class='titulo'><b>Total de Peças</b></td>\n";
			echo "<td class='conteudo'><B> R$ ".number_format($total_geral,2,",",".")."</B></td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td colspan='4' class='titulo'><b>Total de Peças + Despesas</b></td>\n";
			echo "<td class='conteudo'><b> R$ ".number_format($total_geral + $despesas,2,",",".")."</b></td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
	}

	##### P R O D U T O S #####

	$sql =	"SELECT os_sedex_item_produto
			FROM    tbl_os_sedex_item_produto
			WHERE   os_sedex = $os_sedex";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<TABLE class=\"borda\" width=\"650px\" border=\"0\" cellspacing=\"2\" cellpadding=\"0\">\n";
		echo "<tr>\n";
		echo "<td colspan='5' class='titulo'>Produto(s) selecionado(s)</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='titulo'>Referência</td>\n";
		echo "<td class='titulo'>Descrição</td>\n";
		echo "<td class='titulo'>Qtde</td>\n";
		echo "</tr>\n";

		$sql =	"SELECT tbl_os_sedex_item_produto.qtde  ,
						tbl_produto.referencia          ,
						tbl_produto.descricao           
				FROM    tbl_os_sedex_item_produto
				JOIN    tbl_produto USING (produto)
				WHERE   tbl_os_sedex_item_produto.os_sedex = $os_sedex";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
				$referencia  = pg_result($res,$i,referencia);
				$descricao   = pg_result($res,$i,descricao);
				$qtde        = pg_result($res,$i,qtde);

				echo "<tr>\n";
				echo "<td class='conteudo'>$referencia</td>\n";
				echo "<td class='conteudo'>$descricao</td>\n";
				echo "<td class='conteudo'>$qtde</td>\n";
				echo "</tr>\n";
			}
		}
		echo "</table>\n";
	}
}

?>
<script language="JavaScript">
	window.print();
</script>


</BODY>
</html>