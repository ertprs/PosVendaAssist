<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica == 1){
	include("os_print_blackedecker.php");
	exit;
}

$os   = $_GET['os'];
$modo = $_GET['modo'];

if ($login_fabrica == 7) {
#	header ("Location: os_print_filizola.php?os=$os&modo=$modo");
	header ("Location: os_print_manutencao.php?os=$os&modo=$modo");
	exit;
}

if($sistema_lingua <> 'BR') echo "Brasil";
else echo "Outro lingua";

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql =	"SELECT tbl_os.sua_os                                                  ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_produto.produto                                            ,
					tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
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
					tbl_posto.pais                                                 ,
					tbl_os.consumidor_revenda                                      ,
					tbl_os.tipo_atendimento                                        ,
					tbl_os.tecnico_nome                                            ,
					tbl_tipo_atendimento.descricao              AS nome_atendimento,
					tbl_os.qtde_produtos                                           ,
					tbl_os.excluida                                                ,
					tbl_defeito_constatado.descricao          AS defeito_constatado,
					tbl_solucao.descricao                                AS solucao
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto   USING (posto)
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			LEFT JOIN tbl_defeito_reclamado USING (defeito_reclamado)
			LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
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

		if(strlen($sistema_lingua) == 0) $sistema_lingua = 'BR';

        //--=== Tradução para outras linguas ============================= Raphael HD:1212
        $sql_idioma = " SELECT * FROM tbl_produto_idioma
                        WHERE produto     = $produto
                        AND upper(idioma) = '$sistema_lingua'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        if (@pg_numrows($res_idioma) >0) {
            $descricao  = trim(@pg_result($res_idioma,0,descricao));
        }



        $sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
                        WHERE defeito_reclamado = $defeito_reclamado
                        AND upper(idioma)        = '$sistema_lingua'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        if (@pg_numrows($res_idioma) >0) {
            $defeito_cliente  = trim(@pg_result($res_idioma,0,descricao));
        }

echo "Lingua: $sistema_lingua";

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
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
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

h2 {
	font:60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	color: #000000
}
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
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	text-align: left;
	background: #ffffff;
	border-right: solid 1px #a0a0a0;
	border-left: solid 1px #a0a0a0;
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

h2 {
	font:60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	color: #000000
}
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
<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
<TR class="titulo" style="text-align: center;">
<?
	if ($cliente_contrato == 'f') 
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	else
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";

?>
	<TD rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" HEIGHT='40' ALT="ORDEM DE SERVIÇO"></TD>
	<TD style="font-size: 09px;"><? echo  substr($posto_nome,0,30)?></TD>
	<TD><? if ($sistema_lingua <>'BR') echo "FECHA EMISSIÓN"; else echo "DATA EMISSÃO"?></TD>
	<TD><? if ($sistema_lingua <>'BR') echo "NÚMERO"; else echo "NÚMERO";?></TD>
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
			echo "<center><b> $sua_os <br> $consumidor_revenda  </b></center>";
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
	echo "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR><TD align='left'><font face='arial' size='1px'>via do cliente</font></TD></TR>";
	echo "</TABLE>";
}
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">

<? if ($excluida == "t") { ?>
<TR>
	<TD colspan="<? echo $colspan ?>" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVIÇO EXCLUÍDA</h1></TD>
</TR>
<? } ?>

<TR>
	<TD class="titulo" style='font-size: 8px;' colspan="<? echo $colspan ?>"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la ordem de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
</TR>
<TR >
	<TD class="titulo">OS FABRICANTE</TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA ABERT. OS"; else echo "DT ABERT. OS";?></TD>
	<TD class="titulo">REF.</TD>
	<? if ($login_fabrica == 19) { ?>
	<TD class="titulo">QTDE</TD>
	<? } ?>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "SERIE "; else echo "SÉRIE";?></TD>
	<? if ($login_fabrica == 1) { ?>
	<TD class="titulo">CÓD. FABRICAÇÃO</TD>
	<? } ?>
</TR>
<TR height='5'>
	<TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $referencia ?></TD>
	<? if ($login_fabrica == 19) { ?>
	<TD class="conteudo"><? echo $qtde_produtos ?></TD>
	<? } ?>
	<TD class="conteudo"><? echo $descricao ?></TD>
	<TD class="conteudo"><? echo $serie ?></TD>
	<? if ($login_fabrica == 1) { ?>
	<TD class="conteudo"><? echo $codigo_fabricacao ?></TD>
	<? } ?>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
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

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NUMERO"; else echo "NÚMERO";?></TD>
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

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "IDENTIFICACIÓN USUARIO"; else echo "CPF";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $defeito_reclamado_descricao . " - " . $defeito_cliente ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARIENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIO DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
	<TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>


<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $obs ?></TD>
</TR>
</TABLE>

<?
//if($login_fabrica==19){
//Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
if ($login_fabrica <> 11) {
?>
	<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
	<TR>
		<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD>
		<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL TÉCNICO"; else echo "NOME DO TÉCNICO";?></TD>
	</TR>
	<TR>
		<TD class="conteudo"><? echo $tipo_atendimento." - ".$nome_atendimento ?></TD>
		<TD class="conteudo"><? echo $tecnico_nome ?></TD>
	</TR>
	</TABLE>
<?
}
//}
?>


<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<?
//WELLINGTON 05/02/2007
if ($login_fabrica == 11) {
	echo "<CENTER>";
	echo "<TABLE width='650px' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='titulo'>";
	echo "<TD style='font-size: 09px; text-align: center; width: 100%;'>";

	########## CABECALHO COM DADOS DO POSTOS ########## 
	echo $posto_nome."<BR>";
	echo "CNPJ/CPF ".$posto_cnpj ." - IE/RG ".$posto_ie;
	echo "</TD></TR></TABLE></CENTER>";
}
?>

<?
if ($login_fabrica == 11) {
	echo "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR><TD align='left'><font face='arial' size='1px'>via do fabricante - assinada pelo cliente</font></TD></TR>";
	echo "</TABLE>";
}
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo" colspan="5"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la ordem de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
</TR>

<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OS FABRICANTE"; else echo "OS FABRICANTE";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA ABERT. OS"; else echo "DT ABERT. OS";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "REF."; else echo "REF.";?></TD>
	<? if ($login_fabrica == 19) { ?>
	<TD class="titulo">QTDE</TD>
	<? } ?>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "SERIE "; else echo "SÉRIE";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $referencia ?></TD>
	<? if ($login_fabrica == 19) { ?>
	<TD class="conteudo"><? echo $qtde_produtos ?></TD>
	<? } ?>
	<TD class="conteudo"><? echo $descricao ?></TD>
	<TD class="conteudo"><? echo $serie ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
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

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
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

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "IDENTIFICACIÓN USUARIO"; else echo "CPF";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
</TR>
</TABLE>



<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
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

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
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

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $defeito_reclamado_descricao . " - " . $defeito_cliente ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
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
if ($login_fabrica == 11) {
?>
	<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
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
			echo "<TD class='conteudo'>$defeito_constatado</TD>";
			echo "<TD class='conteudo'>$solucao</TD>";
	} else {
			echo "<TD class='conteudo'>&nbsp;</TD>";
			echo "<TD class='conteudo'>&nbsp;</TD>";
	}?>
	</TR>
	</TABLE>
<?
}
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
</TR>
<TR>
	<TD><? echo $obs ?></TD>
</TR>
</TABLE>

<? 
//if($login_fabrica==19){
//Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
if ($login_fabrica <> 11) {
?>
	<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
	<TR>
		<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD>
		<TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL TÉCNICO"; else echo "NOME DO TÉCNICO";?></TD>
	</TR>
	<TR>
		<TD class="conteudo"><? echo $tipo_atendimento." - ".$nome_atendimento ?></TD>
		<TD class="conteudo"><? echo $tecnico_nome ?></TD>
	</TR>
</TABLE>
<?
}
//}
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class='titulo'><? if ($sistema_lingua<>'BR') echo "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:"; else echo "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";?></td>
</TR>
<TR>
	<TD>&nbsp;</td>
</TR>
<TR>
	<TD>&nbsp;</td>
</TR>
<? if($login_posto <> '14236'){  //chamado = 1460 ?>
<TR>
	<TD>&nbsp;</td>
</TR>
<? } ?>
</TABLE>

<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD style='font-size: 10px'><? echo $posto_cidade .", ". $data_abertura ?></TD>
</TR>
<TR>
	<TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: "; ?></TD>
</TR>
</TABLE>
<? if($login_posto <> '14236'){ //chamado = 1460 ?>
<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<TABLE width="650px" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR>Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR>Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR>Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR>Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR>Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>

</TR>
</TABLE>
<? } ?>


<script language="JavaScript">
	window.print();
</script>


</BODY>
</html>
