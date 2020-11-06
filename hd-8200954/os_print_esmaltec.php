<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica == 1){
	include("os_print_blackedecker.php");
	exit;
}

if($login_fabrica == 14){
	include("os_print_intelbras.php");
	exit;
}

$os              = $_GET['os'];
$modo            = $_GET['modo'];
$qtde_etiquetas  = $_GET['qtde_etiquetas'];


if ($login_fabrica == 7) {
#	header ("Location: os_print_filizola.php?os=$os&modo=$modo");
	header ("Location: os_print_manutencao.php?os=$os&modo=$modo");
	exit;
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql =	"SELECT tbl_os.sua_os                                                  ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_produto.produto                                            ,
					tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_produto.qtd_etiqueta_os                                    ,
					tbl_produto.familia                                            ,
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
					tbl_os.observacao                                              ,
					tbl_os.os_posto                                                ,
					tbl_posto.nome                                                 ,
					tbl_posto_fabrica.contato_endereco           as endereco       ,
					tbl_posto_fabrica.contato_numero             as numero         ,
					tbl_posto_fabrica.contato_cep                as cep            ,
					tbl_posto_fabrica.contato_cidade             as cidade         ,
					tbl_posto_fabrica.contato_estado             as estado         ,
					tbl_posto_fabrica.contato_fone_comercial     as fone           ,
					tbl_posto.cnpj                                                 ,
					tbl_posto.ie                                                   ,
					tbl_posto_fabrica.contato_pais               as pais           ,
					tbl_os.consumidor_revenda                                      ,
					tbl_os.tipo_os,
					tbl_os.tipo_atendimento                                        ,
					tbl_os.tecnico_nome                                            ,
					tbl_tipo_atendimento.descricao              AS nome_atendimento,
					tbl_os.qtde_produtos                                           ,
					tbl_os.excluida                                                ,
					tbl_defeito_constatado.descricao          AS defeito_constatado,
					tbl_solucao.descricao                                AS solucao,
					tbl_os_extra.orientacao_sac  
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto   USING (posto)
			JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			LEFT JOIN tbl_defeito_reclamado USING (defeito_reclamado)
			LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
			LEFT JOIN  tbl_os_extra           ON tbl_os.os                             = tbl_os_extra.os
			WHERE   tbl_os.os = $os
			AND     tbl_os.posto = $login_posto";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os                         = pg_result ($res,0,sua_os);
		$data_abertura                  = pg_result ($res,0,data_abertura);
		$data_fechamento                = pg_result ($res,0,data_fechamento);
		$referencia                     = pg_result ($res,0,referencia);
		$produto                        = pg_result ($res,0,produto);
		$descricao                      = pg_result ($res,0,descricao);
		$serie                          = pg_result ($res,0,serie);
		$codigo_fabricacao              = pg_result ($res,0,codigo_fabricacao);
		$cliente                        = pg_result ($res,0,cliente);
		$revenda                        = pg_result ($res,0,revenda);
		$consumidor_cpf                 = pg_result ($res,0,consumidor_cpf);
		$consumidor_nome                = pg_result ($res,0,consumidor_nome);
		$consumidor_endereco            = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero              = pg_result ($res,0,consumidor_numero);
		$consumidor_complemento         = pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro              = pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade              = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado              = pg_result ($res,0,consumidor_estado);
		$consumidor_cep                 = pg_result ($res,0,consumidor_cep);
		$consumidor_fone                = pg_result ($res,0,consumidor_fone);
		$revenda_cnpj                   = pg_result ($res,0,revenda_cnpj);
		$revenda_nome                   = pg_result ($res,0,revenda_nome);
		$nota_fiscal                    = pg_result ($res,0,nota_fiscal);
		$data_nf                        = pg_result ($res,0,data_nf);
		$defeito_reclamado              = pg_result ($res,0,defeito_reclamado);
		$aparencia_produto              = pg_result ($res,0,aparencia_produto);
		$acessorios                     = pg_result ($res,0,acessorios);
		$defeito_cliente                = pg_result ($res,0,defeito_cliente);
		$defeito_reclamado_descricao    = pg_result ($res,0,defeito_reclamado_descricao);
		$posto_nome                     = pg_result ($res,0,nome);
		$posto_endereco                 = pg_result ($res,0,endereco);
		$posto_numero                   = pg_result ($res,0,numero);
		$posto_cep                      = pg_result ($res,0,cep);
		$posto_cidade                   = pg_result ($res,0,cidade);
		$posto_estado                   = pg_result ($res,0,estado);
		$posto_fone                     = pg_result ($res,0,fone);
		$posto_cnpj                     = pg_result ($res,0,cnpj);
		$posto_ie                       = pg_result ($res,0,ie);
		$sistema_lingua                 = strtoupper(trim(pg_result ($res,0,pais)));
		$consumidor_revenda             = pg_result ($res,0,consumidor_revenda);
		$obs                            = pg_result ($res,0,obs);
		$qtde_produtos                  = pg_result ($res,0,qtde_produtos);
		$excluida                       = pg_result ($res,0,excluida);
		$tipo_atendimento               = trim(pg_result($res,0,tipo_atendimento));
		$tecnico_nome                   = trim(pg_result($res,0,tecnico_nome));
		$nome_atendimento               = trim(pg_result($res,0,nome_atendimento));
		$defeito_constatado             = trim(pg_result($res,0,defeito_constatado));
		$solucao                        = trim(pg_result($res,0,solucao));
		$qtd_etiqueta_os                = trim(pg_result($res,0,qtd_etiqueta_os));
		$tipo_os                        = trim(pg_result($res,0,tipo_os));
		$os_posto                       = trim(pg_result($res,0,os_posto));
		$familia                        = trim(pg_result($res,0,familia));
		$orientacao_sac                 = trim(pg_result($res,0,orientacao_sac));
		$observacao                 = trim(pg_result($res,0,observacao));

		if(strlen($sistema_lingua) == 0) $sistema_lingua = 'BR';
		if($sistema_lingua <>'BR') $lingua = "ES";


		if (strlen($qtde_etiquetas)>0 AND $qtde_etiquetas>0){
			$qtd_etiqueta_os=$qtde_etiquetas;
		}else{
			if(strlen($qtd_etiqueta_os)==0){ 
				$qtd_etiqueta_os=5;
			}
		}

        //--=== Tradução para outras linguas ============================= Raphael HD:1212

	if ((strlen(trim($produto))>0) and (strlen(trim($lingua))>0)) {
	        $sql_idioma = " SELECT * FROM tbl_produto_idioma
				WHERE produto     = $produto
				AND upper(idioma) = '$lingua'";
	        $res_idioma = @pg_exec($con,$sql_idioma);
        
		if (@pg_numrows($res_idioma) >0) {
			$descricao  = trim(@pg_result($res_idioma,0,descricao));
	        }
	}


	if ((strlen(trim($defeito_reclamado))>0) and (strlen(trim($lingua))>0)) {
	        $sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
					WHERE defeito_reclamado = $defeito_reclamado
					AND upper(idioma)        = '$lingua'";
	        $res_idioma = @pg_exec($con,$sql_idioma);
        
		if (@pg_numrows($res_idioma) >0) {
			$defeito_cliente  = trim(@pg_result($res_idioma,0,descricao));
		}
	}

	if ((strlen(trim($tipo_atendimento))>0) and (strlen(trim($lingua))>0)) {
		$sql_idioma = " SELECT * FROM tbl_tipo_atendimento_idioma
				WHERE tipo_atendimento = '$tipo_atendimento'
				AND upper(idioma)   = '$lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);

		if (@pg_numrows($res_idioma) >0) {
			$nome_atendimento  = trim(@pg_result($res_idioma,0,descricao));
		}
        }



        //--=== Tradução para outras linguas ================================================

		if (strlen($revenda) > 0) {
			$sql = "SELECT  tbl_revenda.endereco   ,
							tbl_revenda.numero     ,
							tbl_revenda.complemento,
							tbl_revenda.bairro     ,
							tbl_revenda.cep
					FROM    tbl_revenda
					WHERE   tbl_revenda.revenda = $revenda;";
			$res1 = pg_exec ($con,$sql);
			
			if (pg_numrows($res1) > 0) {
				$revenda_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
				$revenda_numero      = trim(pg_result ($res1,0,numero));
				$revenda_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
				$revenda_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
				$revenda_cep         = trim(pg_result ($res1,0,cep));
				$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
			}
		}
	}
	
	$sql = "UPDATE tbl_os_extra SET	impressa = current_timestamp WHERE os = $os;";
	$res = pg_exec($con,$sql);
//echo $sql;

}


if (strlen($sua_os) == 0) $sua_os = $os;

$title = "Ordem de Serviço Balcão - Impressão";
//echo "$qtde_produtos";
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

<? if($login_posto <> '14236'){ ?>

<style type="text/css">
.box_div { 
  border:2px solid;
  width: 100%;
  height: 60px;
}

.box_div2 { 
  border:2px solid;
  width: 150px;
  height: 15px;
}

.box_div3 { 
  border:2px solid;
  width: 180px;
  height: 15px;
}

.box_div4 { 
  border:2px solid;
  width: 15px;
  height: 10px;
}

.box_div5{ 
  border:2px solid;
  width: 160px;
  height: 10px;
}

.box_div6 { 
  border:2px solid;
  width: 100%;
  height: 15px;
}
</style>

<style type="text/css">
body {
	margin: 0px;
}

.titulo {
	font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 7px;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: dotted 1px #000000;
	/*border-right: dotted 1px #a0a0a0;*/
 	border-left: dotted 1px #000000;
	padding: 1px 1px 1px 1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 1px #a0a0a0;
	border-left: dotted 1px #a0a0a0;
	padding: 1px 1px 1px 1px;
}

.etiqueta {
	width: 110px;
	font:50% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	text-align: center
}

h2 {
	font:60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	color: #000000
}

td.fonttitulo {
	font: 11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	text-align: center;
	font-weight: bold;
}
.boxpecas {
	height: 20px;
}
</style>
<style type='text/css' media='print'>
.noPrint {display:none;}
</style> 
<? }else{ ?>

<style type="text/css">
body {
	margin: 0px;
}

.titulo {
	font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: solid 1px #c0c0c0;
	/*border-right: dotted 1px #a0a0a0;*/
 	border-left: solid 1px #c0c0c0;
	padding: 1px 1px 1px 1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	text-align: left;
	background: #ffffff;
	border-right: solid 1px #a0a0a0;
	border-left: solid 1px #a0a0a0;
	padding: 1px 1px 1px 1px;
}

.borda {
	border: solid 1px #c0c0c0;
}

.etiqueta {
	width: 110px;
	font:50% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	text-align: center
}

h2 {
	font:60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	color: #000000
}
</style>
<style type='text/css' media='print'>
.noPrint {display:none;}
</style> 
<? } ?>



<?
if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
else 
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
?>
<body>

<div class='noPrint'>
<input type=button name='fbBtPrint' value='Versão Matricial'
onclick="window.location='os_print_matricial.php?os=<? echo $os; ?>'">
<br>
<hr class='noPrint'>
</div>

<TABLE width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR class="titulo" style="text-align: center;"><?php
		
	if ($cliente_contrato == 'f')
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	else
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	
	if ($familia == 2680 || $familia == 2681) {//HD 246018
		$img_contrato = "logos/cabecalho_print_itatiaia.jpg";
	}?>

	<TD rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" HEIGHT='40' ALT="ORDEM DE SERVIÇO"></TD>
	<TD style="font-size: 09px;"><? echo  substr($posto_nome,0,30)?></TD>
	<TD><? if ($sistema_lingua<>'BR') echo "FECHA EMISSIÓN"; else echo "DATA EMISSÃO"?></TD>
	<TD><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO ORDEM DE SERVIÇO";?></TD>
</TR>
<TR class="titulo">
	<TD style="font-size: 09px; text-align: center; width: 350px; ">
<?
	########## CABECALHO COM DADOS DO POSTOS ########## 
	echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
	if ($sistema_lingua<>'BR') echo "ID1 ";
	else                       echo "CNPJ/CPF ";
	echo $posto_cnpj;
	if ($sistema_lingua<>'BR') echo " - ID2";	
	else                        echo " - IE/RG ";
	echo $posto_ie;
?>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 10px;">
<?	########## DATA DE ABERTURA ########## ?>
		<b><? echo $data_abertura ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 10px;">
<?	########## SUA OS ########## ?>
	<?
		if (strlen($consumidor_revenda) == 0){
			echo "<center><b> $sua_os </b></center>";
		}else{
			echo "<center><b> $sua_os</b></center>";
		}
	?>
	</TD>
</TABLE>

<?
if (($login_fabrica == 1) || ($login_fabrica == 19)) $colspan = 6;
else $colspan = 5;
?>

<?
if ($login_fabrica == 11) {
	echo "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0' align='center'>";
	echo "<TR><TD align='left'><font face='arial' size='1px'>via do cliente</font></TD></TR>";
	echo "</TABLE>";
}
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0" align="center">

<? if ($excluida == "t") { ?>
<TR>
	<TD colspan="<? echo $colspan ?>" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVIÇO EXCLUÍDA</h1></TD>
</TR>
<? } ?>

</TABLE>


<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class="titulo" colspan="7"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la ordem de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
</TR>

<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FABRICANTE"; else echo "FABRICANTE";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OS FABRICANTE"; else echo "OS FABRICANTE";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA ABERT. OS"; else echo "DT ABERT. OS";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "REF."; else echo "REF.";?></TD>
	<? if ($login_fabrica == 19) { ?>
	<TD class="titulo">QTDE</TD>
	<? } ?>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "SERIE "; else echo "SÉRIE";?></TD>
	<TD class="titulo">OS REVENDEDOR</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo "<b>".$login_fabrica_nome."</b>" ?></TD>
	<TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $referencia ?></TD>
	<? if ($login_fabrica == 19) { ?>
	<TD class="conteudo"><? echo $qtde_produtos ?></TD>
	<? } ?>
	<TD class="conteudo"><? echo $descricao ?></TD>
	<TD class="conteudo"><? echo $serie ?></TD>
	<TD class="conteudo"><? echo $os_posto ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL USUARIO"; else echo "NOME DO CONSUMIDOR";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CIUDAD"; else echo "CIDADE";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PROVINCIA"; else echo "ESTADO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "TELÉFONO"; else echo "FONE";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
	<TD class="conteudo"><? echo $consumidor_estado ?></TD>
	<TD class="conteudo"><? echo $consumidor_fone ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_endereco ?></TD>
	<TD class="conteudo"><? echo $consumidor_numero ?></TD>
	<TD class="conteudo"><? echo $consumidor_complemento ?></TD>
	<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "IDENTIFICACIÓN USUARIO"; else echo "CPF";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
</TR>
</TABLE>



<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class="titulo" colspan="5"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre el distribuidor"; else echo "Informações sobre a Revenda";?></TD>
</TR>
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "Identificación"; else echo "CNPJ";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE"; else echo "NOME";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FACTURA COMERCIAL"; else echo "NF N.";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA NF"; else echo "DATA NF";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_cnpj ?></TD>
	<TD class="conteudo"><? echo $revenda_nome ?></TD>
	<TD class="conteudo"><? echo $nota_fiscal ?></TD>
	<TD class="conteudo"><? echo $data_nf ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NUMERO"; else echo "NÚMERO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_endereco ?></TD>
	<TD class="conteudo"><? echo $revenda_numero ?></TD>
	<TD class="conteudo"><? echo $revenda_complemento ?></TD>
	<TD class="conteudo"><? echo $revenda_bairro ?></TD>
	<TD class="conteudo"><? echo $revenda_cep ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $defeito_reclamado_descricao . " - " . $defeito_cliente ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARIENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIO DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
	<TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>

<?
if ($login_fabrica == 11 or $login_fabrica == 30) {
?>
	<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
	<? echo "<TR>"; 
	 if(strlen($defeito_constatado) > 0) {
			echo "<TD class='titulo'>DEFEITO CONSTATADO</TD>";
			echo "<TD class='titulo'>SOLUÇÃO</TD>";
	} else {
			echo "<TD class='titulo'>DEFEITO CONSTATADO (preencher este campo a mão)</TD>";
			echo "<TD class='titulo'>SOLUÇÃO (preencher este campo a mão)</TD>";
	}
	echo "</TR>";
	echo "<TR>";
	if(strlen($defeito_constatado) > 0) {
			//SELECT DOIDO;
		$sql = "SELECT tbl_defeito_constatado.descricao AS defeito_constatado, 
		               tbl_solucao.descricao AS solucao
				FROM tbl_os_defeito_reclamado_constatado
				JOIN tbl_defeito_constatado 
					ON (tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado)
				JOIN tbl_solucao 
					ON (tbl_solucao.solucao = tbl_os_defeito_reclamado_constatado.solucao)
				WHERE os = $os";

		$res = pg_query ($con, $sql);

		if (pg_num_rows($res) > 0) {  

			for ($i = 0; $i < pg_num_rows($res); $i++) {

				$defeito_multi = pg_fetch_result($res, $i, defeito_constatado);
				$solucao_multi = pg_fetch_result($res, $i, solucao);

				echo "<TR>";
				echo "<TD class='conteudo'>$defeito_multi</TD>";
				echo "<TD class='conteudo'>$solucao_multi</TD>"; 
				echo "</TR>";
			}

		} else {
			
			echo "<TD class='conteudo'>$defeito_constatado</TD>";
			echo "<TD class='conteudo'>$solucao</TD>";
		}

	} else {
			echo "<TD class='conteudo'>&nbsp;</TD>";
			echo "<TD class='conteudo'>&nbsp;</TD>";
	}?>
	</TR>
	</TABLE>
<?
}

$sqlV = "SELECT  to_char(data,'DD/MM/YYYY') AS data_agendamento 
                FROM tbl_os_visita WHERE os = {$os} 
            ORDER BY data";
$resV = pg_query($con,$sqlV);
if (pg_num_rows($resV) > 0) {?>
	<TABLE class='borda' width='649px' border='0' cellpadding='0' cellspancing='0' align='center'>
	<?php
    for($j = 0; $j < pg_num_rows($resV); $j++){
        $data_agendamento = pg_fetch_result($resV, $j, 'data_agendamento');
        $ln = $j + 1;
    	?>
        <tr>
            <td class="titulo" height='15' nowrap colspan="3">DATA AGENDAMENTO <?=$ln?></td>
        </tr>
        <tr>
            <td class="conteudo" height='15' colspan="3"><?=$data_agendamento?></td>
        </tr>
    <?php } ?>
    </TABLE>
<?php }


// chamado 2032245

if (strlen($orientacao_sac) > 0 and $orientacao_sac != "null" and $login_fabrica != 11){
?>
<TABLE class='borda' width='649px' border='0' cellpadding='0' cellspancing='0' align='center'>
    <TR>
    	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECTRICES SAC AUTORIZADA PARA PUBLICAR"; else echo "ORIENTAÇÕES DO SAC AO POSTO AUTORIZADO";?></TD>
    </TR>
    <TR>
    	<TD>
    <?
		echo "Obs: ".nl2br(trim(str_replace("|","<br/>",str_replace("<p>","<br/>",str_replace("</p>","<br/>",str_replace("</p><p>","<br/>",str_replace("null","<br />",$orientacao_sac)))))));    	
	?>
		</TD>
    </TR>
</TABLE>
<?
}
if (strlen($observacao) > 0 and $observacao != "null"){
?>
<TABLE class='borda' width='649px' border='0' cellpadding='0' cellspancing='0' align='center'>
    <TR>
    	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES SAC AUTORIZADA PARA PUBLICAR"; else echo "OBSERVAÇÃO DO SAC AO POSTO AUTORIZADO";?></TD>
    </TR>
    <TR>
    	<TD>
    <?
		echo nl2br(trim(str_replace("|","<br/>",str_replace("<p>","<br/>",str_replace("</p>","<br/>",str_replace("</p><p>","<br/>",str_replace("null","<br />",$observacao)))))));    	
	?>
		</TD>
    </TR>
</TABLE>
<?
}
if (strlen($obs) > 0 and $obs != "null"){
?>

<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
</TR>
<TR>
	<TD><? echo $obs ?></TD>
</TR>
</TABLE>
<?php
}
?>

<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
	<TR>
		<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD>
		<?		if($login_fabrica==19){ ?>
		<TD class="titulo">MOTIVO</TD>
<?}?>
		<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL TÉCNICO"; else echo "NOME DO TÉCNICO";?></TD>
	</TR>
	<TR>
		<TD class="conteudo"><? echo $tipo_atendimento." - ".$nome_atendimento ?></TD>
				<?		if($login_fabrica==19){ ?>
		<TD class="conteudo"><? echo "$tipo_os_descricao";?></TD>
<?}?>
		<TD class="conteudo"><? echo $tecnico_nome ?></TD>
	</TR>
</TABLE>

<TABLE class="borda" width="649px" border="0" cellspacing="0" cellpadding="0" align="center">
<TR>
	<TD class='titulo'><? if ($sistema_lingua<>'BR') echo "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:"; else echo "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";?></td>
</TR>
<? if($login_fabrica <> 30){?>
<TR>
	<TD>&nbsp;</TD>
</TR>
<TR>
	<TD>&nbsp;</td>
</TR>
<?}?>
<? if($login_posto <> '14236'){  //chamado = 1460 ?>
<TR>
	<TD>&nbsp;</TD>
</TR> 
<? } ?>
</TABLE>

<?
//esmaltec 17222 7/4/2008 ---------------
if ($login_fabrica == 30) {
		echo "<TABLE width='649px' border='0' cellpadding='3' cellspancing='3' align='center'>";
		echo "<TR>";
			echo "<TD colspan='4' class='titulo'>";
				echo "DEFEITO CONSTATADO";
				echo "<div class='box_div'>";
				if(strlen($defeito_constatato) > 0 and 1==2) {
					echo $defeito_constatato;
				}else{
					echo "&nbsp;";
				}
				echo "</div>";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD colspan='4' class='titulo'>";
			echo "CÓDIGO DO DEFEITO";
			echo "<div class='box_div6'>&nbsp;</div>";
			echo "</P></TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='titulo' width='30%'>";
				echo "DATA DE CONCLUSÃO";
				echo "<div class='box_div2'>&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo' colspan='2'>";
				echo "ASSINATURA DO CLIENTE";
				echo "<div class='box_div6' >&nbsp;</div>";
			echo "</TD>";
		echo "</TR>";
		echo "</TR>";
		echo "</TABLE>";
		echo "<TABLE width='649px' border='0' cellpadding='5' cellspancing='5' align='center'>";
		echo "<TR>";
			echo "<TD colspan='6' class='titulo'>";
				echo "PERFIL DO CLIENTE";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='titulo'>";
				echo "Fogão";
				echo "<div class='box_div4' >&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "Marca";
				echo "<div class='box_div5' >&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "Refrigerador";
				echo "<div class='box_div4' >&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "Marca";
				echo "<div class='box_div5' >&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "Bebedouro";
				echo "<div class='box_div4' >&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "Marca";
				echo "<div class='box_div5' >&nbsp;</div>";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='titulo'>";
				echo "Microondas";
				echo "<div class='box_div4' >&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "Marca";
				echo "<div class='box_div5' >&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "Lavadoura";
				echo "<div class='box_div4' >&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "Marca";
				echo "<div class='box_div5' >&nbsp;</div>";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='titulo'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
	echo "</TABLE>";
	echo "<BR/>";
	echo "<TABLE width='649px' border='1' cellpadding='3' cellspancing='3' align='center' bordercolor='#000000'>";
		echo "<TR>";
			echo "<TD class='fonttitulo' colspan='3'>";
				echo "PEÇAS UTILIZADAS EM REPOSIÇÃO";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='fonttitulo'>";
				echo "ITEM";
			echo "</TD>";
			echo "<TD class='fonttitulo'>";
				echo "DESCRIÇÃO";
			echo "</TD>";
			echo "<TD class='fonttitulo'>";
				echo "QUANTIDADE";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='40%'>";
				echo "&nbsp;";
			echo "</TD>";
			echo "<TD class='boxpecas' width='20%'>";
				echo "&nbsp;";
			echo "</TD>";
		echo "</TR>";
	echo "</TABLE>";
}
//------------------------------------------------
?>

<? //-------  HD 15903 ------------(JEAN)	
  if ($login_fabrica == 3){
?>
	<TABLE width="600px" border="0" cellspacing="0" cellpadding="0" class="borda">
	<TR> 
		<TD class="conteudo"><B>Retiro o produto acima descrito isento do defeito reclamado e nas mesmas condições de apresentação de sua entrada neste Posto de Serviços, comprovado através de teste efetuado na entrega do aparelho.</B>
		</TD>
	 </TD>
</TABLE>
</BR>
<? } // -------------- fim HD 15903 ------------- (JEAN)?>

<? if ($login_fabrica <> 30){ ?>
<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD style='font-size: 10px'><? echo $posto_cidade .", ". $data_abertura ?></TD>
</TR>
<TR>
	<TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: "; ?></TD>
</TR>
</TABLE>
<? }?>

<? if($login_posto <> '14236' and $login_fabrica <> 30){ //chamado = 1460 ?>
<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<TABLE width="650px" border="1" cellspacing="0" cellpadding="0">
<TR>
	<? for( $i=0 ; $i < $qtd_etiqueta_os ; $i++) { ?>
		<?if ($i%5==0) { echo "</TR><TR> " ;}?>
	<TD class="etiqueta">
		
		<? echo  "<b>OS <font size='2px'>$sua_os</font></b><BR>Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone" ?>
	</TD> 
	<? } ?>
</TR>
</TABLE>
<? } ?>
<?php
// HD 3741276 - QRCode
include_once 'os_print_qrcode.php';
?>

<script language="JavaScript">
	if (navigator.userAgent.match(/Chrome/gi)) {
		alert("Por favor para fechar a tela utilize o botão cancelar");
	}
	
	window.print();
</script>

</BODY>
</html>
