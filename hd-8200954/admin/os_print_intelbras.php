<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

#------------ Le OS da Base de dados ------------#
$os = $HTTP_GET_VARS['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT tbl_os.sua_os                                                  ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_produto.produto                                            ,
					tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_produto.qtd_etiqueta_os                                    ,
					tbl_defeito_reclamado.descricao AS defeito_cliente             ,
					tbl_os.cliente                                                 ,
					tbl_os.revenda                                                 ,
					tbl_os.serie                                                   ,
					tbl_os.codigo_fabricacao                                       ,
					tbl_os.consumidor_cpf                                          ,
					tbl_os.consumidor_nome                                         ,
					tbl_os.consumidor_fone                                         ,
					tbl_os.consumidor_endereco                                     ,
					tbl_os.consumidor_numero                                       ,
					tbl_os.consumidor_complemento                                  ,
					tbl_os.consumidor_bairro                                       ,
					tbl_os.consumidor_cep                                          ,
					tbl_os.consumidor_cidade                                       ,
					tbl_os.consumidor_estado                                       ,
					tbl_os.revenda_cnpj                                            ,
					tbl_os.revenda_nome                                            ,
					tbl_os.nota_fiscal                                             ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
					tbl_os.defeito_reclamado                                       ,
					tbl_os.defeito_reclamado_descricao                             ,
					tbl_os.acessorios                                              ,
					tbl_os.aparencia_produto                                       ,
					tbl_os.obs                                                     ,
					tbl_posto.nome                                                 ,
					tbl_posto.endereco                                             ,
					tbl_posto.numero                                               ,
					tbl_posto.cep                                                  ,
					tbl_posto.cidade                                               ,
					tbl_posto.estado                                               ,
					tbl_posto.fone                                                 ,
					tbl_posto.cnpj                                                 ,
					tbl_posto.ie                                                   ,
					tbl_os.consumidor_revenda                                      ,
					tbl_os.tipo_os,
					tbl_os.tipo_atendimento                                        ,
					tbl_os.tecnico_nome                                            ,
					tbl_tipo_atendimento.descricao              AS nome_atendimento,
					tbl_os.qtde_produtos                                           ,
					tbl_os.excluida                                                ,
					tbl_defeito_constatado.descricao          AS defeito_constatado,
					tbl_servico_realizado.descricao                                AS solucao
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto   USING (posto)
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			LEFT JOIN tbl_defeito_reclamado USING (defeito_reclamado)
			LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_servico_realizado ON tbl_os.solucao_os = tbl_servico_realizado.servico_realizado
			WHERE   tbl_os.os = $os ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$data_fechamento	= pg_result ($res,0,data_fechamento);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero           = pg_result ($res,0,consumidor_numero);
		$consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$consumidor_cep              = pg_result ($res,0,consumidor_cep);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf			= pg_result ($res,0,data_nf);
		$defeito_reclamado	= pg_result ($res,0,defeito_cliente);
		$aparencia_produto	= pg_result ($res,0,aparencia_produto);
		$produto	= pg_result ($res,0,produto);
		$acessorios			= pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$consumidor_revenda = pg_result ($res,0,consumidor_revenda);
		$excluida           = pg_result ($res,0,excluida);
		$referencia         = pg_result ($res,0,referencia);
		$descricao          = pg_result ($res,0,descricao);
		$serie              = pg_result ($res,0,serie);
		$codigo_fabricacao  = pg_result ($res,0,codigo_fabricacao);
		$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
		$tecnico_nome       = trim(pg_result($res,$i,tecnico_nome));
		$nome_atendimento   = trim(pg_result($res,$i,nome_atendimento));
		$tipo_os                        = trim(pg_result($res,0,tipo_os));
		$defeito_constatado             = trim(pg_result($res,0,defeito_constatado));
		$solucao                        = trim(pg_result($res,0,solucao));
	}
}

if (strlen($sua_os) == 0) $sua_os = $os;

if ($consumidor_revenda == 'C'){
	$consumidor_revenda = 'CONSUMIDOR';
}else if ($consumidor_revenda == 'R'){
	$consumidor_revenda = 'REVENDA';
}

$title = "Ordem de Serviço Balcão - Impresso";
?>

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
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: left;
	background: #ffffff;
	border-right: dotted 1px #a0a0a0;
	border-left: dotted 1px #a0a0a0;
	padding: 1px,1px,1px,1px;
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
<?
		if ($cliente_contrato == 'f') 
			$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
		else
			$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";

?>
<body>
<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="<? echo ($img_contrato); ?>" ALT="ORDEM DE SERVIÇO"></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">

<? if ($excluida == "t") { ?>
<TR>
	<TD colspan="5" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVIÇO EXCLUÍDA</h1></TD>
</TR>
<? } ?>

<TR>
	<TD class="titulo" colspan="5">Informações sobre a Ordem de Serviço</TD>
</TR>
<TR>
	<TD class="titulo">OS FABR.</TD>
	<TD class="titulo">DT ABERT. OS</TD>
	<TD class="titulo">REF.</TD>
	<TD class="titulo">DESCRIÇÃO</TD>
	<TD class="titulo">SÉRIE</TD>
</TR>
<TR>
	<TD class="conteudo">
	<?
		if (strlen($consumidor_revenda) > 0){
			echo $sua_os ." - ". $consumidor_revenda;
		}else if (strlen($consumidor_revenda) == 0){
				echo $sua_os;
		}
	?>
	</TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $referencia ?></TD>
	<TD class="conteudo"><? echo $descricao ?></TD>
	<TD class="conteudo"><? echo $serie ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME DO CONSUMIDOR</TD>
	<TD class="titulo">CIDADE</TD>
	<TD class="titulo">ESTADO</TD>
	<TD class="titulo">FONE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
	<TD class="conteudo"><? echo $consumidor_estado ?></TD>
	<TD class="conteudo"><? echo $consumidor_fone ?></TD>
</TR>
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">BAIRRO</TD>
	<TD class="titulo">CEP</TD>
	<TD class="titulo"></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_endereco . " " . $consumidor_numero . " " . $consumidor_complemento ?></TD>
	<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">DEFEITO APRESENTADO PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $defeito_reclamado_descricao . "<br>" . strtoupper($defeito_reclamado) ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">APARÊNCIA GERAL DO PRODUTO</TD>
	<TD class="titulo">ACESSÓRIOS DEIXADOS PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
	<TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<?
echo "<TR>"; 
echo "<TD class='titulo'>DEFEITO CONSTATADO</TD>";
echo "<TD class='titulo'>SOLUÇÃO</TD>";
echo "</TR>";
echo "<TR>";
echo "<TD class='conteudo'>$defeito_constatado</TD>";
echo "<TD class='conteudo'>$solucao</TD>";
?>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ATENDIMENTO</TD>
	<TD class="titulo">NOME DO TÉCNICO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo  $tipo_atendimento . "-" . $nome_atendimento ?></TD>
	<TD class="conteudo"><? echo $tecnico_nome ?></TD>
</TR>
</TABLE>


<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="5">Informações sobre a Ordem de Serviço</TD>
</TR>
<TR>
	<TD class="titulo">OS FABR.</TD>
	<TD class="titulo">DT ABERT. OS</TD>
	<TD class="titulo">REF.</TD>
	<TD class="titulo">DESCRIÇÃO</TD>
	<TD class="titulo">SÉRIE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $sua_os ?></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $referencia ?></TD>
	<TD class="conteudo"><? echo $descricao ?></TD>
	<TD class="conteudo"><? echo $serie ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME DO CONSUMIDOR</TD>
	<TD class="titulo">CIDADE</TD>
	<TD class="titulo">ESTADO</TD>
	<TD class="titulo">FONE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
	<TD class="conteudo"><? echo $consumidor_estado ?></TD>
	<TD class="conteudo"><? echo $consumidor_fone ?></TD>
</TR>
</TABLE>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="5">Informações sobre a Revenda</TD>
</TR>
<TR>
	<TD class="titulo">CNPJ</TD>
	<TD class="titulo">NOME</TD>
	<TD class="titulo">NF N.</TD>
	<TD class="titulo">DATA NF</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_cnpj ?></TD>
	<TD class="conteudo"><? echo $revenda_nome ?></TD>
	<TD class="conteudo"><? echo $nota_fiscal ?></TD>
	<TD class="conteudo"><? echo $data_nf ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">DEFEITO APRESENTADO PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $defeito_reclamado_descricao . "<br>" . strtoupper($defeito_reclamado) ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">APARÊNCIA GERAL DO PRODUTO</TD>
	<TD class="titulo">ACESSÓRIOS DEIXADOS PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
	<TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<?
echo "<TR>"; 
echo "<TD class='titulo'>DEFEITO CONSTATADO</TD>";
echo "<TD class='titulo'>SOLUÇÃO</TD>";
echo "</TR>";
echo "<TR>";
echo "<TD class='conteudo'>$defeito_constatado</TD>";
echo "<TD class='conteudo'>$solucao</TD>";
?>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ATENDIMENTO</TD>
	<TD class="titulo">NOME DO TÉCNICO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo  $tipo_atendimento.$nome_atendimento ?></TD>
	<TD class="conteudo"><? echo $tecnico_nome ?></TD>
</TR>
</TABLE>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<div id="container">
	<div id="page">
		<h2>Diagnóstico, Peças usadas e Resolução do Problema:
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; height: 100px; ">
				<p>Técnico:</p>
				<p><!-- Aqui vai o texto do técnico a mão --></p>
			</div>
		</div>
		</h2>
	</div>
</div>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo" colspan="100%" align="center">PEÇAS TROCADAS</TD>
</TR>
<TR>
	<TD class="titulo">REFERÊNCIA</TD>
	<TD class="titulo">DESCRIÇÃO</TD>
	<TD class="titulo">QTDE</TD>
</TR>

<?
	$sql="SELECT tbl_peca.referencia,
				 tbl_peca.descricao,
				 tbl_os_item.qtde
			FROM tbl_os_produto
			join tbl_os_item using(os_produto)
			left join tbl_peca ON tbl_os_item.peca=tbl_peca.peca
			where tbl_os_produto.os=$os";
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		for($i=0; $i < pg_numrows($res); $i++){
			$qtde				= pg_result ($res,$i,qtde);
			$referencia			= pg_result ($res,$i,referencia);
			$descricao			= pg_result ($res,$i,descricao);
			echo "<tr>";
			echo "<td class='conteudo'>$referencia</td>";
			echo "<td class='conteudo'>$descricao</td>";
			echo "<td class='conteudo' align='center'>$qtde</td></tr>";
		}
	}
?>
</TABLE>
<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><h2>Em, <? echo $posto_cidade .", ". $data_abertura ?></h2></TD>
</TR>
<TR>
	<TD><h2><? echo $consumidor_nome ?> - Assinatura:</h2></TD>
</TR>
</TABLE>

<div id="container">
	<IMG SRC="imagens/cabecalho_os_corte.gif" ALT="">
</div>


<?
$sql = "SELECT  distinct
				tbl_produto.referencia,
				tbl_produto.descricao
		FROM    tbl_os_produto
		JOIN    tbl_produto USING (produto)
		WHERE   tbl_os_produto.os = $os
		ORDER BY tbl_produto.referencia;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

?>


<div id="container">
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
</div>
<? } ?>
</div>

<script language="JavaScript">
	window.print();
</script>


</BODY>

</html>