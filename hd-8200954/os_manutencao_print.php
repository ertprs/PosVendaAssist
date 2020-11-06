<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if (strlen($_GET['os_manutencao']) > 0)  $os_manutencao = trim($_GET['os_manutencao']);

if(strlen($os_manutencao) == 0){
	echo "<script Language='JavaScript'>";
	echo "window.close();";
	echo "</script>";
	exit;
}

if (strlen($os_manutencao) > 0) {
	$sql = "SELECT  tbl_os_revenda.os_revenda,
					tbl_os_revenda.sua_os,
					TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura,
					tbl_os_revenda.posto,
					tbl_os_revenda.consumidor_nome,
					tbl_os_revenda.qtde_km,
					tbl_os_revenda.quem_abriu_chamado,
					tbl_os_revenda.taxa_visita,
					tbl_os_revenda.hora_tecnica,
					tbl_os_revenda.cobrar_percurso,
					tbl_os_revenda.visita_por_km,
					tbl_os_revenda.diaria,
					tbl_os_revenda.obs,
					tbl_os_revenda.contrato,
					tbl_cliente.cliente,
					tbl_cliente.nome        AS cliente_nome,
					tbl_cliente.cpf         AS cliente_cpf,
					tbl_cidade.nome         AS cliente_cidade,
					tbl_cidade.estado       AS cliente_estado,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.posto,
					tbl_posto.nome,
					tbl_posto.cnpj,
					tbl_posto_fabrica.contato_cidade AS posto_cidade,
					tbl_posto_fabrica.contato_estado AS posto_estado
			FROM	tbl_os_revenda
			JOIN	tbl_posto USING(posto)
			JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_cliente ON tbl_cliente.cliente = tbl_os_revenda.cliente
			LEFT JOIN tbl_cidade  ON tbl_cidade.cidade   = tbl_cliente.cidade
			WHERE	tbl_os_revenda.fabrica    = $login_fabrica
			AND		tbl_os_revenda.posto      = $login_posto
			AND		tbl_os_revenda.os_revenda = $os_manutencao";
	$res = pg_exec($con, $sql);
	if (pg_numrows($res) > 0) {
		$os_manutencao       = trim(pg_result($res,0,os_revenda));
		$sua_os              = trim(pg_result($res,0,sua_os));
		$data_abertura       = trim(pg_result($res,0,data_abertura));
		$posto               = trim(pg_result($res,0,posto));
		$consumidor_nome     = trim(pg_result($res,0,consumidor_nome));
		$qtde_km             = trim(pg_result($res,0,qtde_km));
		$quem_abriu_chamado  = trim(pg_result($res,0,quem_abriu_chamado));
		$taxa_visita         = trim(pg_result($res,0,taxa_visita));
		$hora_tecnica        = trim(pg_result($res,0,hora_tecnica));
		$cobrar_percurso     = trim(pg_result($res,0,cobrar_percurso));
		$visita_por_km       = trim(pg_result($res,0,visita_por_km));
		$diaria              = trim(pg_result($res,0,diaria));
		$obs                 = trim(pg_result($res,0,obs));
		$contrato            = trim(pg_result($res,0,contrato));
		$cliente             = trim(pg_result($res,0,cliente));
		$cliente_nome        = trim(pg_result($res,0,cliente_nome));
		$cliente_cnpj        = trim(pg_result($res,0,cliente_cpf));
		$cliente_cidade      = trim(pg_result($res,0,cliente_cidade));
		$cliente_estado      = trim(pg_result($res,0,cliente_estado));
		$posto               = trim(pg_result($res,0,posto));
		$posto_codigo        = trim(pg_result($res,0,codigo_posto));
		$posto_nome          = trim(pg_result($res,0,nome));
		$posto_cidade        = trim(pg_result($res,0,posto_cidade));
		$posto_estado        = trim(pg_result($res,0,posto_estado));

		$diaria        = number_format($diaria,2,",",".");
		$taxa_visita   = number_format($taxa_visita,2,",",".");
		$visita_por_km = number_format($visita_por_km,2,",",".");

	}else{
		echo "<script Language='JavaScript'>";
		echo "alert('OS não encontrada!')";
		echo "</script>";
		exit;
	}
}


$title = "Ordem de Serviço Manutenção - Impresso"; 

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
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css_press.css">

</head>

<style type="text/css">

body {
	margin: 0px;
}

.titulo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: xx-small;
	text-align: left;
	color: #000000;
	background: #D0D0D0;
	border-bottom: dotted 1px #a0a0a0;
	border-right: dotted 1px #a0a0a0;
	border-left: dotted 1px #a0a0a0;
	padding: 1px,1px,1px,1px;
	padding-left: 3px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: left;
	background: #ffffff;
	border-right: dotted 1px #a0a0a0;
	border-left: dotted 1px #a0a0a0;
	padding: 1px,1px,1px,1px;
	padding-left: 3px;
}

.borda {
	border: solid 1px #c0c0c0;
}
.etiqueta {
	width: 110px;
	font:50% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	text-align: center
}
</style>

<body>

<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="ORDEM DE SERVIÇO"></TD>
</TR>
</TABLE>

<br>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="4">Informações sobre a Ordem de Serviço - Manutenção</TD>
</TR>
<TR>
	<TD class="titulo">OS FABRICANTE</TD>
	<TD class="titulo">DATA DA ABERTURA DA OS</TD>
	<TD class="titulo">DATA DA DIGITAÇÃO DA OS</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $sua_os ?></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $data_digitacao ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME DO CLIENTE</TD>
	<TD class="titulo">CPF/CNPJ DO CLIENTE</TD>

</TR>
<TR>
	<TD class="conteudo"><? echo $cliente_nome ?></TD>
	<TD class="conteudo"><? echo $cliente_cnpj ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">CÓDIGO DO POSTO</TD>
	<TD class="titulo">NOME DO POSTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $posto_codigo ?></TD>
	<TD class="conteudo"><? echo $posto_nome ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">CHAMADO ABERTO POR</TD>
	<TD class="titulo">TAXA VISITA</TD>
	<TD class="titulo">HORA TÉCNICA</TD>
	<TD class="titulo">COBRAR PERCURSO?</TD>
	<TD class="titulo">VALOR/KM</TD>
	<TD class="titulo">VALOR DIÁRIA</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $quem_abriu_chamado ?></TD>
	<TD class="conteudo"><? echo $taxa_visita ?></TD>
	<TD class="conteudo"><? echo $hora_tecnica ?></TD>
	<TD class="conteudo"><? echo $cobrar_percurso ?></TD>
	<TD class="conteudo"><? echo $visita_por_km ?></TD>
	<TD class="conteudo"><? echo $diaria ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">OBSERVAÇÕES</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $obs ?></TD>
</TR>
</TABLE>

<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD>&nbsp;</TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">PRODUTOS</TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">REFERÊNCIA PRODUTO</TD>
	<TD class="titulo">DESCRIÇÃO DO PRODUTO</TD>
	<TD class="titulo">NÚMERO DE SÉRIE</TD>
	<TD class="titulo">CAP.</TD>
	<TD class="titulo">PESO PADRÃO</TD>
	<TD class="titulo">CERT. CONF</TD>
	<TD class="titulo">DEFEITO RECLAMADO</TD>
</TR>

<?

if (strlen($os_manutencao) > 0) {
	$sql = "SELECT  tbl_os_revenda_item.os_revenda_item          ,
					tbl_os_revenda_item.produto                  ,
					tbl_os_revenda_item.serie                    ,
					tbl_os_revenda_item.capacidade               ,
					tbl_os_revenda_item.regulagem_peso_padrao    ,
					tbl_os_revenda_item.certificado_conformidade ,
					tbl_os_revenda_item.defeito_reclamado        ,
					tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao,
					tbl_produto.referencia                       ,
					tbl_produto.descricao
			FROM	tbl_os_revenda
			JOIN	tbl_os_revenda_item USING(os_revenda)
			JOIN	tbl_produto         USING (produto)
			JOIN	tbl_defeito_reclamado USING(defeito_reclamado)
			WHERE	tbl_os_revenda.fabrica         = $login_fabrica
			AND		tbl_os_revenda_item.os_revenda = $os_manutencao 
			ORDER BY tbl_os_revenda_item.os_revenda_item ASC ";
	$res = pg_exec($con, $sql);
	for ($i=0; $i<pg_numrows($res   ); $i++){
		$os_revenda_item          = pg_result($res,$i,os_revenda_item);
		$produto_referencia          = pg_result($res,$i,referencia);
		$produto_descricao           = pg_result($res,$i,descricao);
		$produto_serie               = pg_result($res,$i,serie);
		$produto_capacidade          = pg_result($res,$i,capacidade);
		$regulagem_peso_padrao       = pg_result($res,$i,regulagem_peso_padrao);
		$certificado_conformidade    = pg_result($res,$i,certificado_conformidade);
		$defeito_reclamado           = pg_result($res,$i,defeito_reclamado);
		$defeito_reclamado_descricao = pg_result($res,$i,defeito_reclamado_descricao);
?>
<TR>
	<TD class="conteudo"><? echo $produto_referencia ?></TD>
	<TD class="conteudo"><? echo $produto_descricao ?></TD>
	<TD class="conteudo"><? echo $produto_serie ?></TD>
	<TD class="conteudo"><? echo $produto_capacidade ?></TD>
	<TD class="conteudo"><? echo $regulagem_peso_padrao ?></TD>
	<TD class="conteudo"><? echo $certificado_conformidade ?></TD>
	<TD class="conteudo"><? echo $defeito_reclamado_descricao ?></TD>
</TR>
<?
			}
		}
?>
</TABLE>

<br>

<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class='conteudo'><h2>Em: <? echo $data_abertura ?></h2></TD>
</TR>
<TR>
	<TD class='conteudo'><h2><? echo $revenda_nome ?> - Assinatura:  _________________________________________</h2></TD>
</TR>
</TABLE>
<br><br>

<script language="JavaScript">
	window.print();
</script>

</BODY>
</html>

