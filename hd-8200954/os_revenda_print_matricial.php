<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica == 1){
	include("os_revenda_print_blackedecker.php");
	exit;
}

include 'funcoes.php';

if (in_array($login_fabrica,array(137,141,144))) {// Verifica se o posto é Interno

    $sql = "SELECT posto
            FROM tbl_posto_fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
            WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
            AND tbl_posto_fabrica.posto = " . $login_posto;
    $res = pg_query($con,$sql);

    if( pg_num_rows($res) > 0) {

        $posto_interno = true;

    }else{

        $posto_interno = false;

    }

}

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);

if(strlen($os_revenda) > 0){
	if($login_fabrica == 15){
		$campos = " tbl_revenda_fabrica.contato_razao_social      AS revenda_nome                              ,
					 tbl_revenda_fabrica.cnpj        				 AS revenda_cnpj                              ,
					 tbl_revenda_fabrica.contato_fone        		 AS revenda_fone                              ,
					 tbl_revenda_fabrica.contato_email       		 AS revenda_email                             ,
					 tbl_revenda_fabrica.contato_endereco    		 AS revenda_endereco                          ,
					 tbl_revenda_fabrica.contato_numero      		 AS revenda_numero                            ,
					 tbl_revenda_fabrica.contato_complemento 		 AS revenda_complemento                       ,
					 tbl_revenda_fabrica.contato_bairro      		 AS revenda_bairro                            , ";
		$join_revenda = " JOIN tbl_revenda_fabrica ON  tbl_os_revenda.revenda = tbl_revenda_fabrica.revenda AND tbl_revenda_fabrica.fabrica = $login_fabrica";
	}else{
		$campos = " tbl_revenda.nome         	AS revenda_nome                              ,
					 tbl_revenda.cnpj        	AS revenda_cnpj                              ,
					 tbl_revenda.fone        	AS revenda_fone                              ,
					 tbl_revenda.email       	AS revenda_email                             ,
					 tbl_revenda.endereco    	AS revenda_endereco                          ,
					 tbl_revenda.numero      	AS revenda_numero                            ,
					 tbl_revenda.complemento 	AS revenda_complemento                       ,
					 tbl_revenda.cidade 		AS revenda_cidade                       	 ,
					 tbl_revenda.bairro      	AS revenda_bairro                            , ";
		$join_revenda = " JOIN tbl_revenda ON  tbl_os_revenda.revenda = tbl_revenda.revenda ";
	}
	// seleciona do banco de dados
	$sql = "SELECT   tbl_os_revenda.sua_os                                                ,
					 tbl_os_revenda.obs                                                   ,
					 to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					 to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					 $campos
					 tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
					 tbl_posto.nome    AS posto_nome                                      ,
					 to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
					 tbl_os_revenda.nota_fiscal                                           ,
					 tbl_os_revenda.valor_adicional_justificativa                         ,
					 tbl_os_revenda.consumidor_nome                                       ,
					 tbl_os_revenda.consumidor_cnpj                                       
			FROM	 tbl_os_revenda
			$join_revenda
			JOIN tbl_fabrica ON tbl_os_revenda.fabrica = tbl_fabrica.fabrica
			LEFT JOIN tbl_posto USING (posto)
			LEFT JOIN tbl_posto_fabrica
			ON		 tbl_posto_fabrica.posto = tbl_posto.posto
			AND		 tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	 tbl_os_revenda.os_revenda = $os_revenda
			AND		 tbl_os_revenda.posto = $login_posto
			AND		 tbl_os_revenda.fabrica = $login_fabrica ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$sua_os          = pg_result($res,0,sua_os);
		$data_abertura   = pg_result($res,0,data_abertura);
		$data_digitacao  = pg_result($res,0,data_digitacao);
		$revenda_nome    = pg_result($res,0,revenda_nome);
		$revenda_cnpj    = pg_result($res,0,revenda_cnpj);
		$revenda_fone    = pg_result($res,0,revenda_fone);
		$revenda_email   = pg_result($res,0,revenda_email);
		$revenda_endereco= pg_result($res,0,revenda_endereco);
		$revenda_numero  = pg_result($res,0,revenda_numero);
		$revenda_complemento = pg_result($res,0,revenda_complemento);
		$revenda_bairro  = pg_result($res,0,revenda_bairro);
		$revenda_cidade  = pg_result($res,0,revenda_cidade);
		$posto_codigo    = pg_result($res,0,posto_codigo);
		$posto_nome      = pg_result($res,0,posto_nome);
		$data_nf         = pg_result($res,0,data_nf);
		$nota_fiscal     = pg_result($res,0,nota_fiscal);
		$consumidor_nome = pg_result($res,0,consumidor_nome);
		$consumidor_cnpj = pg_result($res,0,consumidor_cnpj);
		$obs             = pg_result($res,0,obs);
		$valor_adicional_justificativa = pg_result($res,0,valor_adicional_justificativa);

		if($login_fabrica == 137){
			$dados_adicionais 		= json_decode($dados_adicionais);
			$transportadora 		= $dados_adicionais->transportadora;
			$nota_fiscal_saida 		= $dados_adicionais->nota_fiscal_saida;
			$data_nota_fiscal_saida = $dados_adicionais->data_nota_fiscal_saida;
		}

	}else{
		echo "Erro... OS da Revenda não encontrada.";
		exit;
	}
}


$title = "Ordem de Serviço Revenda - Impresso"; 

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
	font-family: Draft;
}

.titulo {
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	color: #000000;
	background: #FFFFFF
	border-bottom: dotted 0px #a0a0a0;
	border-right: dotted 0px #a0a0a0;
	border-left: dotted 0px #a0a0a0;
	padding: 1px,1px,1px,1px;
	padding-left: 3px;
}

.conteudo {
	font-size: 13px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 0px #a0a0a0;
	border-left: dotted 0px #a0a0a0;
	padding: 1px,1px,1px,1px;
	padding-left: 3px;
}

.borda {
	border: solid 0px #c0c0c0;
}
.etiqueta {
	width: 110px;
	font:Draft;
	font-size: 12px;
	text-align: center
}

h2 {
	color: #000000
}
</style>
<style type='text/css' media='print'>
.noPrint {display:none;}
</style> 

<body>

<div class='noPrint'>
<input type=button name='fbBtPrint' value='Versão Jato de Tinta / Laser'
onclick="window.location='os_revenda_print.php?os_revenda=<? echo $os_revenda; ?>'">
<br>
<hr class='noPrint'>
</div>

<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="logos/logo_<? echo strtolower ($login_fabrica_nome) ?>.jpg" style="max-height: 70px; max-width: 200px;" ALT="ORDEM DE SERVIÇO"></TD>
</TR>
</TABLE>
<?
if ($login_fabrica == 19 ) {
	echo '<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">';
	echo '<TR>';
	echo '<TD class="titulo"ALIGN="CENTER" ><FONT SIZE="2"><B>FAVOR IMPRIMIR EM DUAS VIAS</B></FONT></TD>';
	echo '</TR>';
	echo '</TABLE>';
}
?>
<br>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="4">Informações sobre a Ordem de Serviço - Revenda</TD>
</TR>
<TR>
	<TD class="titulo"><?= (in_array($login_fabrica, array(169,170))) ? 'OS REVENDEDOR' : 'OS FABRICANTE'; ?></TD>
	<TD class="titulo">DATA DA ABERTURA DA OS</TD>
	<TD class="titulo">DATA DA DIGITAÇÃO DA OS</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $sua_os ?></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $data_digitacao ?></TD>
</TR>
</TABLE>
<? if($login_fabrica== 19) {$aux_revenda = "DO ATACADO";} else {$aux_revenda = "DA REVENDA";}?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME <?=$aux_revenda;?></TD>
	<TD class="titulo">CNPJ <?=$aux_revenda;?></TD>
<? if($login_fabrica != 15){ ?>
	<TD class="titulo">FONE</TD>
	<TD class="titulo">E-MAIL</TD>
<? } ?>

</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_nome ?></TD>
	<TD class="conteudo"><? echo ($login_fabrica == 15)? substr($revenda_cnpj,0,8)  : $revenda_cnpj; ?></TD>
<? if($login_fabrica != 15){ ?>
	<TD class="conteudo"><? echo $revenda_fone ?></TD>
	<TD class="conteudo"><? echo $revenda_email ?></TD>
<? } ?>
</TR>
</TABLE>

<? if($login_fabrica == 24 OR ($login_fabrica == 137 && $posto_interno == true)){//HD5492?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">Nº</TD>
	<TD class="titulo">COMPL.</TD>
	<TD class="titulo">BAIRRO</TD>
</TR>
<TR>
	<TD class="conteudo"><? if(strlen($revenda_endereco) > 0) echo $revenda_endereco; else echo "&nbsp;"; ?></TD>
	<TD class="conteudo"><? if(strlen($revenda_numero) > 0) echo $revenda_numero; else echo "&nbsp;"; ?></TD>
	<TD class="conteudo"><? if(strlen($revenda_complemento) > 0) echo $revenda_complemento; else echo "&nbsp;"; ?></TD>
	<TD class="conteudo"><? if(strlen($revenda_bairro) > 0) echo $revenda_bairro; else echo "&nbsp;"; ?></TD>
</TR>
</TABLE>
<?
}

if($login_fabrica == 137 && $posto_interno == true){

	$sql_cidade_revenda = "SELECT nome, estado FROM tbl_cidade WHERE cidade = $revenda_cidade";
	$res_cidade_revenda = pg_query($con, $sql_cidade_revenda);

	$cidade_revenda = pg_fetch_result($res_cidade_revenda, 0, 'nome');
	$estado_revenda = pg_fetch_result($res_cidade_revenda, 0, 'estado');

	?>

	<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
		<TR>
			<TD class="titulo" width="50%">CIADADE</TD>
			<TD class="titulo" width="50%">ESTADO</TD>
		</TR>
		<TR>
			<TD class="conteudo"><? echo $cidade_revenda; ?></TD>
			<TD class="conteudo"><? echo $estado_revenda; ?></TD>
		</TR>
	</TABLE>

	<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
		<TR>
			<TD class="titulo" width="50%">TRANSPORTADORA</TD>
		</TR>
		<TR>
			<TD class="conteudo"><? echo (empty($transportadora)) ? "N/I" : $transportadora; ?></TD>
		</TR>
	</TABLE>

	<?php

}

?>

<?if($login_fabrica ==19){?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME DA REVENDA</TD>
	<TD class="titulo">CNPJ DA REVENDA</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cnpj ?></TD>
</TR>
</TABLE>
<?}?>

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
	<TD class="titulo">NOTA FISCAL <?php echo ($login_fabrica == 137 && $posto_interno == true) ? "ENTRADA" : ""; ?></TD>
	<TD class="titulo">DATA NOTA <?php echo ($login_fabrica == 137 && $posto_interno == true) ? "ENTRADA" : ""; ?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $nota_fiscal ?></TD>
	<TD class="conteudo"><? echo $data_nf ?></TD>
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
<?	if($login_fabrica == 19){
		echo "<TD class='titulo'>QTDE</TD>";
	}else{
		if (!in_array($login_fabrica, array(169,170))) {
			echo "<TD class='titulo'>NÚMERO DE SÉRIE</TD>";
		}
	}

	if (in_array($login_fabrica, array(94,169,170))) { ?>
		<td class='titulo'>DEFEITO RECLAMADO</td>
	<? }
	
	if($login_fabrica == 137 && $posto_interno == true){ ?>
		<TD class="titulo">CFOP</TD>
		<TD class="titulo">QUANTIDADE</TD>
		<TD class="titulo">VALOR UNITÁRIO</TD>
		<TD class="titulo">TOTAL DA NOTA</TD>
	<?php } ?>

</TR>

<?
	// monta o FOR
	$qtde_item = 20;

		if ($os_revenda){
			// seleciona do banco de dados
			$sql = "SELECT   tbl_os_revenda_item.os_revenda_item ,
							 tbl_os_revenda_item.produto         ,
							 tbl_os_revenda_item.serie           ,
							 tbl_os_revenda_item.qtde             ,
							 tbl_os_revenda_item.rg_produto      ,
							 tbl_produto.referencia              ,
							 tbl_produto.descricao               
					FROM	 tbl_os_revenda
					JOIN	 tbl_os_revenda_item
					ON		 tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN	 tbl_produto
					ON		 tbl_produto.produto = tbl_os_revenda_item.produto
					WHERE	 tbl_os_revenda.os_revenda = $os_revenda
					AND		 tbl_os_revenda.posto      = $login_posto
					AND		 tbl_os_revenda.fabrica    = $login_fabrica ";

			$res = pg_exec($con, $sql);
			$total_itens = pg_numrows($res);

			for ($i=0; $i<pg_numrows($res); $i++)
			{

				$os_revenda_item    = pg_result($res,$i,os_revenda_item);
				$referencia_produto = pg_result($res,$i,referencia);
				$qtde               = pg_result($res,$i,qtde);
				$produto_descricao  = pg_result($res,$i,descricao);
				$produto_serie      = pg_result($res,$i,serie);

				if($login_fabrica == 137 && $posto_interno == true){
					$qtde 	= pg_result($res,$i,qtde);
					$dados 	= pg_result($res,$i,rg_produto);

					$dados 			= json_decode($dados);
					$cfop 			= $dados->cfop;
					$valor_unitario = $dados->vu;
					$valor_nota 	= $dados->vt;

				}
?>
<TR>
	<TD class="conteudo"><? echo $referencia_produto ?></TD>
	<TD class="conteudo"><? echo $produto_descricao ?></TD>
	<?
	if($login_fabrica == 19){
		echo "<TD class='conteudo'> $qtde </TD>";
	}else{
		if (!in_array($login_fabrica, array(169,170))) {
			echo "<TD class='conteudo'>$produto_serie </TD>";
		}
	}

	if (in_array($login_fabrica, array(94,169,170))) {
		echo "<TD class='conteudo'> $defeito_constatado_descricao </TD>";
	}

	if($login_fabrica == 137 && $posto_interno == true){
		?>
		<TD class="conteudo"><? echo $cfop ?></TD>
		<TD class="conteudo"><? echo $qtde ?></TD>
		<TD class="conteudo"><? echo $valor_unitario ?></TD>
		<TD class="conteudo"><? echo $valor_nota ?></TD>
		<?php
	}

	?>

</TR>
<?
if (in_array($login_fabrica, array(169,170))) { ?>
	<tr>
		<td colspan="3" class="titulo">OBSERVAÇÔES</td>
	</tr>
	<tr>
		<td colspan="3" class="conteudo" style="height:100px;"></td>
	</tr>
<? }
			}
		}
?>
</TABLE>

<br>
<?php

if(in_array($login_fabrica, array(141,144)) && $posto_interno == true){
	?>
	<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
		<TR>
			<TD align="center">
				<?php 
				echo "<span font-size:16px; font-weight:bold;>OS: $sua_os-1 a $sua_os-$total_itens</span>";
				?></TD>
			</TR>
		</TABLE>
		<?php
	}else{
?>
<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class='conteudo'><h2>Em: <? echo $data_abertura ?></h2></TD>
</TR>
<TR>
	<TD class='conteudo'><h2><? echo $revenda_nome ?> - Assinatura:  _________________________________________</h2></TD>
</TR>
</TABLE>
	<?php
	}
	?>
<?if($login_fabrica==19){?>
	<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
	<TR>
		<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
	</TR>
	</TABLE><BR>
	<TABLE width="650px" border="1" cellspacing="0" cellpadding="0">
	<TR>
		<TD class="etiqueta">
			<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
		<TD class="etiqueta">
		<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
		<TD class="etiqueta">
			<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
		<TD class="etiqueta">
			<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
		<TD class="etiqueta">
			<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
	</TR>
	<TR>
		<TD class="etiqueta">
			<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
		<TD class="etiqueta">
		<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
		<TD class="etiqueta">
			<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
		<TD class="etiqueta">
			<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
		<TD class="etiqueta">
			<? echo "<b>OS $sua_os</b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
		</TD>
	</TR>
	</TABLE>
<?}?>
<br><br>

<script language="JavaScript">
	window.print();
</script>

</BODY>
</html>

