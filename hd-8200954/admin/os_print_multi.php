<?
# HD - 36258
# Foi criado esta tela para imprimir OSs do extrato

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$arrayostmp = $_GET["osimprime"];

$arrayos = substr($arrayostmp, 0, -1);

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
if (strlen ($arrayos) > 0) { // fim no fim
	$sql = "SELECT  tbl_os.os                                                      ,
					tbl_os.sua_os                                                  ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
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
					tbl_os.defeito_reclamado_descricao                             ,
					tbl_os.acessorios                                              ,
						tbl_os.produto,
					tbl_os.aparencia_produto                                       ,
					tbl_defeito_reclamado.descricao AS defeito_reclamado_cliente   ,
					tbl_os.consumidor_revenda                                      ,
					tbl_os.excluida                                                ,
					tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_os.serie                                                   ,
					tbl_os.tipo_atendimento                                        ,
					tbl_os.tecnico_nome                                            ,
					tbl_tipo_atendimento.descricao             AS nome_descricao   ,
					tbl_os.tipo_os                                                 ,
					tbl_os.codigo_fabricacao                                       ,
					tbl_defeito_constatado.descricao          AS defeito_constatado,
					tbl_solucao.descricao                                AS solucao
			FROM    tbl_os
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			JOIN    tbl_produto USING (produto)
			LEFT JOIN tbl_defeito_reclamado USING (defeito_reclamado)
			LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
			WHERE   tbl_os.os IN ($arrayos)";
	$resX = pg_exec ($con,$sql);

	if (pg_numrows ($resX) > 0){
		for ($i=0; $i < pg_numrows ($resX); $i++){
			$os					= pg_result ($resX,$i,os);
			$sua_os				= pg_result ($resX,$i,sua_os);
			$data_abertura		= pg_result ($resX,$i,data_abertura);
			$data_fechamento	= pg_result ($resX,$i,data_fechamento);
			$consumidor_nome             = pg_result ($resX,$i,consumidor_nome);
			$consumidor_endereco         = pg_result ($resX,$i,consumidor_endereco);
			$consumidor_numero           = pg_result ($resX,$i,consumidor_numero);
			$consumidor_complemento      = pg_result ($resX,$i,consumidor_complemento);
			$consumidor_bairro           = pg_result ($resX,$i,consumidor_bairro);
			$consumidor_cidade           = pg_result ($resX,$i,consumidor_cidade);
			$consumidor_estado           = pg_result ($resX,$i,consumidor_estado);
			$consumidor_cep              = pg_result ($resX,$i,consumidor_cep);
			$consumidor_fone             = pg_result ($resX,$i,consumidor_fone);
			$revenda_cnpj		= pg_result ($resX,$i,revenda_cnpj);
			$revenda_nome		= pg_result ($resX,$i,revenda_nome);
			$nota_fiscal		= pg_result ($resX,$i,nota_fiscal);
			$data_nf			= pg_result ($resX,$i,data_nf);
			$defeito_reclamado	= pg_result ($resX,$i,defeito_reclamado_cliente);
			$aparencia_produto	= pg_result ($resX,$i,aparencia_produto);
			$produto	= pg_result ($resX,$i,produto);
			$acessorios			= pg_result ($resX,$i,acessorios);
			$defeito_reclamado_descricao = pg_result ($resX,$i,defeito_reclamado_descricao);
			$consumidor_revenda = pg_result ($resX,$i,consumidor_revenda);
			$excluida           = pg_result ($resX,$i,excluida);
			$referencia         = pg_result ($resX,$i,referencia);
			$descricao          = pg_result ($resX,$i,descricao);
			$serie              = pg_result ($resX,$i,serie);
			$codigo_fabricacao  = pg_result ($resX,$i,codigo_fabricacao);
			$tipo_atendimento   = trim(pg_result($resX,$i,tipo_atendimento));
			$tecnico_nome       = trim(pg_result($resX,$i,tecnico_nome));
			$nome_atendimento   = trim(pg_result($resX,$i,nome_descricao));
			$tipo_os                        = trim(pg_result($resX,$i,tipo_os));
			$defeito_constatado             = trim(pg_result($resX,$i,defeito_constatado));
			$solucao                        = trim(pg_result($resX,$i,solucao));
	// Fim do pg_numrows antigo }

			if (strlen($sua_os) == 0) $sua_os = $os;

			if ($consumidor_revenda == 'C'){
				$consumidor_revenda = 'CONSUMIDOR';
			}else if ($consumidor_revenda == 'R'){
				$consumidor_revenda = 'REVENDA';
			}
			//}// Fim for
			//	} // fim pg_numrows novo
			//}

			if ($cliente_contrato == 'f'){
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
			}else{
					$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
			}
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
				<TD colspan="<? if ($login_fabrica == 1) echo '6'; else echo '5'; ?>" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVIÇO EXCLUÍDA</h1></TD>
			</TR>
			<? } ?>

			<TR>
				<TD class="titulo" colspan="<? if ($login_fabrica == 1) echo '6'; else echo '5'; ?>">Informações sobre a Ordem de Serviço</TD>
			</TR>
			<TR>
				<TD class="titulo">OS FABR.</TD>
				<TD class="titulo">DT ABERT. OS</TD>
				<TD class="titulo">REF.</TD>
				<TD class="titulo">DESCRIÇÃO</TD>
				<TD class="titulo">
					<? 
					if($login_fabrica==35){
						echo "PO#";
					}else{
						echo "SÉRIE";
					}
					?>
				</TD>
				<? if ($login_fabrica == 1) { ?>
				<TD class="titulo">CÓD. FABRICAÇÃO</TD>
				<? } ?>
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
				<? if ($login_fabrica == 1) { ?>
				<TD class="conteudo"><? echo $codigo_fabricacao ?></TD>
				<? } ?>
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

			<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
			<TR>
				<TD class="titulo">ATENDIMENTO</TD>
				<?		if($login_fabrica==19){
				if(strlen($tipo_os)>0){
					$sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
					$ress = pg_exec($con,$sqll);
					$tipo_os_descricao = pg_result($ress,0,0);
				}
				?>
					<TD class="titulo">MOTIVO</TD>
			<?}?>
				<TD class="titulo">NOME DO TÉCNICO</TD>
			</TR>
			<TR>
				<TD class="conteudo"><? echo  $tipo_atendimento . "-" . $nome_atendimento ?></TD>
			<?		if($login_fabrica==19){ ?>
					<TD class="conteudo"><? echo "$tipo_os_descricao";?></TD>
			<?}?>
				<TD class="conteudo"><? echo $tecnico_nome ?></TD>
			</TR>
			</TABLE>

			<?
			if ($login_fabrica == 2 AND strlen($data_fechamento)>0) {
			?>
				<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
				<? echo "<TR>"; 
				 if(strlen($defeito_constatado) > 0) {
						echo "<TD class='titulo'>DEFEITO CONSTATADO</TD>";
						echo "<TD class='titulo'>SOLUÇÃO</TD>";
						echo "<TD class='titulo'>DT FECHA. OS</TD>";
				}
				echo "</TR>";
				echo "<TR>";
				if(strlen($defeito_constatado) > 0) {
						echo "<TD class='conteudo'>$defeito_constatado</TD>";
						echo "<TD class='conteudo'>$solucao</TD>";
						echo "<TD class='conteudo'>$data_fechamento</TD>";
				} ?>
				</TR>
				</TABLE>
			<?
			}
			?>

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
				<TD class="titulo">
					<? 
					if($login_fabrica==35){
						echo "PO#";
					}else{
						echo "SÉRIE";
					}
					?>
				</TD>
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

			<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
			<TR>
				<TD><h2>Em, <? 
					if($login_fabrica==2  AND strlen($data_fechamento)>0){
					$data_hj = date('d/m/Y');
					echo $posto_cidade .", ". $data_hj;
				}else{
					echo $posto_cidade .", ". $data_abertura;
				} ?></h2></TD>
			</TR>
			<TR>
				<TD><h2><? echo $consumidor_nome ?> - Assinatura:</h2></TD>
			</TR>
			</TABLE>

			<?
			//IMG CORTE
			echo "<div id='container'>";
				echo "<IMG SRC='imagens/cabecalho_os_corte.gif' ALT=''>";
			echo "</div>";

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
			<? }
			?>
			<!--</div>-->
		<br style="page-break-before:always">
	<?} // fim for
	} // fim pg_numrows
} // fim do strlen?>
<script language="JavaScript">
	window.print();
</script>


</BODY>

</html>