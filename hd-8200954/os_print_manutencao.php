<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['os_manutencao']) > 0) {
	$os_manutencao = trim($_GET['os_manutencao']);
}

if(strlen($os_manutencao) == 0){
	echo "<script Language='JavaScript'>";
	echo "window.close();";
	echo "</script>";
	exit;
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os_manutencao) > 0) {
	$sql = "SELECT  tbl_os_revenda.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
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
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.cliente                                                   ,
					tbl_os.revenda                                                   ,
					tbl_os.obs                                                       ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf          ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado,
					tbl_os.defeito_reclamado_descricao                               ,
					tbl_os.tipo_atendimento                                          ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.acessorios                                                ,
					tbl_os.capacidade                                                ,
					tbl_produto.referencia                                           ,
					tbl_produto.descricao                                            ,
					tbl_os_extra.classificacao_os                                    ,
					tbl_os_extra.taxa_visita                                         ,
					tbl_os_extra.hora_tecnica                                        ,
					tbl_os_extra.anormalidades                                       ,
					tbl_os_extra.causas                                              ,
					tbl_os_extra.medidas_corretivas                                  ,
					tbl_os_extra.recomendacoes                                       ,
					tbl_os_extra.obs                                     AS obs_extra,
					tbl_os_extra.selo                                                ,
					tbl_os_extra.lacre_encontrado                                    ,
					tbl_os_extra.tecnico                                             ,
					tbl_os_extra.lacre                                               ,
					tbl_os_extra.regulagem_peso_padrao                               ,
					tbl_os_extra.certificado_conformidade                            ,
					tbl_os_extra.valor_diaria                                        ,
					tbl_os_extra.qtde_horas                                          ,
					tbl_os_extra.deslocamento_km                                     ,
					tbl_os_extra.visita_por_km                                       ,
					tbl_os_extra.valor_por_km                                        ,
					tbl_os_extra.mao_de_obra                                         ,
					tbl_os_extra.mao_de_obra_por_hora                                ,
					tbl_os_extra.valor_total_diaria                                  ,
					tbl_os_extra.valor_total_hora_tecnica                            ,
					tbl_os_extra.desconto_deslocamento                               ,
					tbl_os_extra.desconto_hora_tecnica                               ,
					tbl_os_extra.desconto_diaria                                     ,
					tbl_os_extra.desconto_regulagem                                  ,
					tbl_os_extra.desconto_certificado                                ,
					tbl_condicao.descricao                      AS condicao_descricao,
					tbl_classificacao_os.descricao      AS classificacao_os_descricao,
					tbl_os.consumidor_revenda                                        ,
					tbl_posto_fabrica.posto_empresa
			FROM    tbl_os
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_defeito_reclamado USING(defeito_reclamado)
			LEFT JOIN    tbl_produto           USING(produto)
			LEFT JOIN    tbl_os_extra          USING(os)
			LEFT JOIN    tbl_condicao          USING(condicao)
			LEFT JOIN    tbl_classificacao_os  USING(classificacao_os)
			WHERE   tbl_os.os      = $os
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_os.posto   = $login_posto";

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
					tbl_os_revenda.valor_por_km,
					tbl_os_revenda.diaria,
					tbl_os_revenda.valor_diaria,
					tbl_os_revenda.desconto_hora_tecnica,
					tbl_os_revenda.desconto_diaria,
					tbl_os_revenda.desconto_deslocamento,
					tbl_os_revenda.obs,
					tbl_os_revenda.contrato,
					tbl_cliente.cliente,
case when length(tbl_os_revenda.consumidor_nome) > 0 then tbl_os_revenda.consumidor_nome else tbl_cliente.nome    end    AS consumidor_nome,
					case when length(tbl_os_revenda.consumidor_cnpj) > 0 then tbl_os_revenda.consumidor_cnpj else tbl_cliente.cpf     end    AS consumidor_cpf,
					case when length(tbl_os_revenda.consumidor_endereco) > 0 then tbl_os_revenda.consumidor_endereco else tbl_cliente.endereco  end  AS consumidor_endereco,
					case when length(tbl_os_revenda.consumidor_numero) > 0 then tbl_os_revenda.consumidor_numero else tbl_cliente.numero   end   AS consumidor_numero,
					case when length(tbl_os_revenda.consumidor_complemento) > 0 then tbl_os_revenda.consumidor_complemento else tbl_cliente.complemento end AS consumidor_complemento,
					case when length(tbl_os_revenda.consumidor_bairro) > 0 then tbl_os_revenda.consumidor_bairro else tbl_cliente.bairro    end  AS consumidor_bairro,
					case when length(tbl_os_revenda.consumidor_fone) > 0 then tbl_os_revenda.consumidor_fone else tbl_cliente.fone    end    AS consumidor_fone,
					case when length(tbl_os_revenda.consumidor_cidade) > 0 then tbl_os_revenda.consumidor_cidade else tbl_cidade.nome   end  AS consumidor_cidade,
					case when length(tbl_os_revenda.consumidor_estado) > 0 then tbl_os_revenda.consumidor_estado else tbl_cidade.estado   end    AS consumidor_estado,
					case when length(tbl_os_revenda.consumidor_cep) > 0 then tbl_os_revenda.consumidor_cep else tbl_cliente.cep   end    AS consumidor_cep,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.posto,
					tbl_posto.nome,
					tbl_posto.cnpj,
					tbl_posto_fabrica.contato_cidade AS posto_cidade,
					tbl_posto_fabrica.contato_estado AS posto_estado,
					tbl_posto_fabrica.posto_empresa
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
		$valor_por_km        = trim(pg_result($res,0,valor_por_km));
		$diaria              = trim(pg_result($res,0,diaria));
		$valor_diaria        = trim(pg_result($res,0,valor_diaria));
		$desconto_hora_tecnica = trim(pg_result($res,0,desconto_hora_tecnica));
		$desconto_diaria       = trim(pg_result($res,0,desconto_diaria));
		$desconto_taxa_visita  = trim(pg_result($res,0,desconto_deslocamento));
		$obs                 = trim(pg_result($res,0,obs));
		$contrato            = trim(pg_result($res,0,contrato));
		$cliente             = trim(pg_result($res,0,cliente));
		$consumidor_nome        = trim(pg_result($res,0,consumidor_nome));
		$consumidor_cnpj        = trim(pg_result($res,0,consumidor_cpf));
		$consumidor_endereco    = trim(pg_result($res,0,consumidor_endereco));
		$consumidor_numero      = trim(pg_result($res,0,consumidor_numero));
		$consumidor_complemento = trim(pg_result($res,0,consumidor_complemento));
		$consumidor_bairro      = trim(pg_result($res,0,consumidor_bairro));
		$consumidor_fone        = trim(pg_result($res,0,consumidor_fone));
		$consumidor_cidade      = trim(pg_result($res,0,consumidor_cidade));
		$consumidor_estado      = trim(pg_result($res,0,consumidor_estado));
		$posto               = trim(pg_result($res,0,posto));
		$posto_codigo        = trim(pg_result($res,0,codigo_posto));
		$posto_nome          = trim(pg_result($res,0,nome));
		$posto_cidade        = trim(pg_result($res,0,posto_cidade));
		$posto_estado        = trim(pg_result($res,0,posto_estado));
		$posto_empresa       = trim(pg_result ($res,0,posto_empresa));
		$consumidor_cep      = trim(pg_result ($res,0,consumidor_cep));
		$consumidor_cep         = substr($consumidor_cep,0,2) .".". substr($consumidor_cep,2,3) ."-". substr($consumidor_cep,5,3);

		if ($desconto_regulagem>0 and $regulagem_peso_padrao>0){
			$regulagem_peso_padrao = $regulagem_peso_padrao - ($regulagem_peso_padrao*$desconto_deslocamento / 100);
		}
		
		if ($desconto_certificado>0 and $certificado_conformidade>0){
			$certificado_conformidade = $certificado_conformidade - ($certificado_conformidade*$desconto_certificado / 100);
		}

		if ($desconto_hora_tecnica>0 and $hora_tecnica>0){
			$hora_tecnica = $hora_tecnica - ($hora_tecnica*$desconto_hora_tecnica / 100);
		}

		if ($desconto_diaria>0 and $valor_diaria>0){
			$valor_diaria = $valor_diaria - ($valor_diaria*$desconto_diaria / 100);
		}

		if ($desconto_diaria>0 AND $valor_diaria>0){
			$valor_diaria = $valor_diaria + ($valor_diaria * $desconto_diaria /100 );
		}
		
		/* Calculo da Mao de Obra */
		$mao_de_obra = $hora_tecnica + $valor_diaria;

		$diaria        = number_format($diaria,2,",",".");
		$taxa_visita   = number_format($taxa_visita,2,",",".");
		$visita_por_km = number_format($visita_por_km,2,",",".");

		if (strlen($cliente) > 0) {
			$sql = "SELECT  tbl_cliente.endereco   ,
							tbl_cliente.numero     ,
							tbl_cliente.complemento,
							tbl_cliente.bairro     ,
							tbl_cliente.cep        ,
							tbl_cliente.cpf        ,
							tbl_cliente.rg
					FROM    tbl_cliente
					WHERE   tbl_cliente.cliente = $cliente;";
			$res1 = pg_exec ($con,$sql);
			
			if (pg_numrows($res1) > 0) {
				$consumidor_cpf         = trim(pg_result ($res1,0,cpf));
				
				if (strlen($consumidor_cpf) == 14){
					$consumidor_cpf = substr($consumidor_cpf,0,2) .".". substr($consumidor_cpf,2,3) .".". substr($consumidor_cpf,5,3) ."/". substr($consumidor_cpf,8,4) ."-". substr($consumidor_cpf,12,2);
				}elseif(strlen($consumidor_cpf) == 11){
					$consumidor_cpf = substr($consumidor_cpf,0,3) .".". substr($consumidor_cpf,3,3) .".". substr($consumidor_cpf,6,3) ."-". substr($consumidor_cpf,9,2);
				}
				$consumidor_rg          = trim(pg_result ($res1,0,rg));
			}
		}
		
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

		if (strlen($revenda_cnpj) == 14){
			$revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		}elseif(strlen($consumidor_cpf) == 11){
			$revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
		}

		#$sql = "UPDATE tbl_os_extra SET impressa = current_timestamp where os=$os;";
		#$res = pg_exec($con,$sql);

	}else{
		echo "<script Language='JavaScript'>";
		echo "alert('OS não encontrada!')";
		echo "</script>";
		exit;
	}
}


$title = "Ordem de Serviço - Impressão";

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
	margin: 0px,0px,0px,0px;
}

.titulo {
	font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 7px;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: dotted 1px #000000;
	/*border-right: dotted 1px #a0a0a0;*/
 	border-left: dotted 0px #000000;
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 0px #a0a0a0;
	/*border-left: dotted 1px #a0a0a0;*/
	border-bottom: dotted 1px #000000;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 1px #a0a0a0;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid #a0a0a0;
	color:#000000;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}
</style>

<body>

<TABLE width="650px" border="0" cellspacing="1" cellpadding="2">
<TR class="titulo" style="text-align: center;">
<?
	if($login_fabrica==7 AND strlen($posto_empresa)>0){
		$cond1 =  "AND PO.posto   = $posto_empresa ";
	}else{
		$cond1 =  "AND PO.posto   = $login_posto ";
	}

	$sql = "SELECT  PO.cnpj      ,
			PO.ie        ,
			PO.fone      ,
			PO.nome      ,
			PO.endereco    AS endereco,
			PO.numero      AS numero,
			PO.complemento AS complemento,
			PO.bairro      AS bairro,
			PO.cidade      AS cidade,
			PO.estado      AS estado,
			PO.cep         AS cep,
			PO.email       AS email
		FROM tbl_posto         PO
		JOIN tbl_posto_fabrica PF ON PO.posto = PF.posto 
		WHERE PF.fabrica = $login_fabrica
		$cond1";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) == 1) {
	$posto_nome			= pg_result ($res,0,nome);
	$posto_endereco		= pg_result ($res,0,endereco);
	$posto_numero		= pg_result ($res,0,numero);
	$posto_cep			= pg_result ($res,0,cep);
	$posto_cidade		= pg_result ($res,0,cidade);
	$posto_estado		= pg_result ($res,0,estado);
	$posto_fone			= pg_result ($res,0,fone);
	$posto_cnpj			= pg_result ($res,0,cnpj);
	$posto_ie			= pg_result ($res,0,ie);
}

	if ($cliente_contrato <> 't' AND $tipo_atendimento <> "59") 
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	else
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome)."_contrato.gif";
?>
	<TD rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"></TD>
<?
	$sql = "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
	$resP = pg_exec($con,$sql);
?>
	<TD style="font-size: 09px;"><? echo $posto_nome; ?></TD>
	<TD>DATA EMISSÃO</TD>
	<TD>NÚMERO</TD>
	<TD>PÁGINA</TD>
</TR>

<TR class="titulo">
	<TD style="font-size: 09px; text-align: center; width: 350px; ">
<?
switch (strlen (trim ($posto_cnpj))) {
	case 0:
		$posto_cnpj = "&nbsp";
		break;
	case 11:
		$posto_cnpj = substr ($posto_cnpj,0,3) . "." . substr ($posto_cnpj,3,3) . "." . substr ($posto_cnpj,6,3) . "-" . substr ($posto_cnpj,9,2);
		break;
	case 14:
		$posto_cnpj = substr ($posto_cnpj,0,2) . "." . substr ($posto_cnpj,2,3) . "." . substr ($posto_cnpj,5,3) . "/" . substr ($posto_cnpj,8,4) . "-" . substr ($posto_cnpj,12,2);
		break;
}

	########## CABECALHO COM DADOS DO POSTOS ########## 
	echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
	echo "CNPJ ".$posto_cnpj ." <br> IE ".$posto_ie;
?>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
		<b><? echo $data_abertura ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;"align='center'>
		<b><? echo $sua_os ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;" align='center'>
		<b>1/1</b>
	</TD>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">

<? ########## DADOS DO CLIENTE ########## ?>

<?
if (strlen (trim ($cliente_ie)) == 0) $cliente_ie = "&nbsp";


switch (strlen (trim ($cliente_cpf))) {
case 0:
	$cliente_cpf = "&nbsp";
	break;
case 11:
	$cliente_cpf = substr ($cliente_cpf,0,3) . "." . substr ($cliente_cpf,3,3) . "." . substr ($cliente_cpf,6,3) . "-" . substr ($cliente_cpf,9,2);
	break;
case 14:
	$cliente_cpf = substr ($cliente_cpf,0,2) . "." . substr ($cliente_cpf,2,3) . "." . substr ($cliente_cpf,5,3) . "/" . substr ($cliente_cpf,8,4) . "-" . substr ($cliente_cpf,12,2);
	break;
}

?>

<TR>
	<TD class="titulo">Raz.Soc.</TD>
	<TD class="conteudo" colspan='3'><? echo $consumidor_nome ?>&nbsp</TD>
	<TD class="titulo">CNPJ/CPF</TD>
	<TD class="conteudo"><? echo $consumidor_cpf ?>&nbsp</TD>
	<TD class="titulo">IE/RG</TD>
	<TD class="conteudo"><? echo $consumidor_rg ?>&nbsp</TD>
</TR>

<!-- ====== ENDEREÇO E TELEFONE ================ -->
<TR>
	<TD class="titulo">Endereço</TD>
	<TD class="conteudo" colspan='3'><? 	
	if (strlen ($os_manutencao) > 0) echo $consumidor_endereco . ", " . $consumidor_numero . " " . $consumidor_complemento ?>
	&nbsp</TD>
	<TD class="titulo">CEP</TD>
	<TD class="conteudo"><? echo $consumidor_cep ?>&nbsp</TD>
	<TD class="titulo">Telefone</TD>
	<TD class="conteudo"><? echo $consumidor_fone ?>&nbsp</TD>
</TR>

<!-- ====== Cep Municipio UF ================ -->
<TR>
	<TD class="titulo">Bairro</TD>
	<TD class="conteudo" colspan=3><? echo $consumidor_bairro ?>&nbsp</TD>
	<TD class="titulo">Municipio</TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?>&nbsp</TD>
	<TD class="titulo">Estado</TD>
	<TD class="conteudo"><? echo $consumidor_estado ?>&nbsp</TD>
</TR>

<!-- ====== CONTATO E CHAMADO ================ -->
<TR>
	<TD class="titulo">Contato</TD>
	<TD class="conteudo"><? echo $quem_abriu_chamado ?>&nbsp</TD>
	<TD class="titulo">Distância</TD>
	<TD class="conteudo"><? if (strlen($qtde_km)>0) echo $qtde_km." km"; ?> &nbsp</TD>
</TR>
<TR>
</TR>

<!-- ====== MOTIVO ================ -->
<TR>
	<TD class="titulo">Taxa de visita:</TD>
	<TD class="conteudo">
	<?
	if (strlen ($os_manutencao) > 0 AND $taxa_visita>0) {
		echo "R$ ". number_format ($taxa_visita,2,",",".") ; 
	}
	?>&nbsp;</TD>
	<TD class="titulo">Hora técnica:</TD>
	<TD class="conteudo">
	<?
		if (strlen ($os_manutencao) > 0 AND $hora_tecnica>0) {
			echo "R$ ". number_format ($hora_tecnica,2,",",".");
		}
	?>&nbsp;</TD>
	<TD class="titulo">Valor/km:</TD>
	<TD class="conteudo">
	<?
	if (strlen ($os_manutencao) > 0 AND $valor_por_km > 0) {
		echo "R$ ". number_format ($valor_por_km,2,",",".")."/Km"; 
	}
	?>&nbsp;</TD>
	<TD class="titulo">Valor diária:</TD>
	<TD class="conteudo">
	<?
	if (strlen ($os_manutencao) > 0 AND $valor_diaria > 0) 
		echo "R$ ". number_format ($valor_diaria,2,",","."); 
	?>&nbsp;</TD>
</TR>

<TR>
	<TD class="titulo">Obs.:</TD>
	<TD class="conteudo" colspan="7"><? echo $obs ?>&nbsp;</TD>
</TR>
</TABLE>

<!-- ====== MODELO DO APARELHO ================ -->

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<?
	$sql = "SELECT	DISTINCT
					tbl_produto.referencia                                  ,
					tbl_produto.descricao                                   ,
					tbl_os_revenda_item.defeito_reclamado_descricao                      ,
					tbl_os_revenda_item.serie                                            ,
					tbl_os_revenda_item.capacidade                                       ,
					tbl_os_revenda_item.certificado_conformidade                   
			FROM	tbl_os_revenda
			JOIN	tbl_os_revenda_item    USING(os_revenda)
			JOIN	tbl_produto            USING(produto)
			WHERE	tbl_os_revenda.os_revenda = $os_manutencao
			AND		tbl_os_revenda.posto      = $login_posto 
			AND		tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_exec ($con,$sql);

	$total_produtos = 0;

	for($i=0; $i<@pg_numrows($res); $i++){

		$produto_referencia  = trim(pg_result($res,$i,referencia));

		//$produto_regulagem   = trim(pg_result($res,$i,regulagem));
		$produto_certificado = trim(pg_result($res,$i,certificado_conformidade));
?>
<TR>
	<TD rowspan='3'><b><? echo $i + 1; ?></b></TD>
	<TD class="titulo">REFERÊNCIA</TD>
	<TD class="conteudo"><? echo $produto_referencia ?> &nbsp;</TD>
	<TD class="titulo">MODELO</TD>
	<TD class="conteudo" colspan=2><? echo pg_result($res,$i,descricao); ?>&nbsp;</TD>
	<TD class="titulo">CAPACIDADE</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,capacidade); ?> &nbsp;</TD>
	<TD class="titulo">SÉRIE</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,serie); ?> &nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">DEFEITO RECLAMADO</TD>
	<TD class="conteudo" colspan='4'><? echo pg_result($res,$i,defeito_reclamado_descricao); ?> &nbsp;</TD>
	<TD class="titulo">REGULAGEM</TD>
	<TD class="conteudo"><? if ($produto_certificado>0) echo $produto_regulagem;  ?>  &nbsp;</TD>
	<TD class="titulo">CERTIF. CONF.</TD>
	<TD class="conteudo"><? if ($produto_certificado>0) echo $produto_certificado;  ?>  &nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">DEFEITO CONSTATADO</TD>
	<TD class="conteudo" colspan='4'>&nbsp;</TD>
	<TD class="titulo">SELO</TD>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="titulo">LACRE ENCONTRADO</TD>
	<TD class="conteudo"> &nbsp;</TD>
</TR>
<TR>
	<TD colspan='10' height='1' bgcolor="#000000"></TD>
</TR>
<?
		$total_produtos ++;
	}
?>
</TABLE>

<!-- ======= CONTROLE DE HORAS ========= -->
<TABLE width="650" border="0" cellspacing="0" cellpadding="0">
<TR class="menu_top" bgcolor="#d0d0d0">
	<TD class="menu_top" bgcolor="#d0d0d0">&nbsp;</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan='2'>SAÍDA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan='2'>CHEGADA/INÍCIO</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan='2'>SAÍDA/FIM</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan='2'>CHEGADA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan='2'>SAÍDA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan='2'>CHEGADA</TD>
</TR>
<TR>
	<TD class="menu_top" style='text-align: center;'>LOCAL</TD>
	<TD class="menu_top" style='text-align: center;' colspan='2'>SEDE</TD>
	<TD class="menu_top" style='text-align: center;' colspan='2'>CLIENTE</TD>
	<TD class="menu_top" style='text-align: center;' colspan='2'>CLIENTE</TD>
	<TD class="menu_top" style='text-align: center;' colspan='2'>SEDE</TD>
	<TD class="menu_top" style='text-align: center;' colspan='2'>ALMOÇO</TD>
	<TD class="menu_top" style='text-align: center;' colspan='2'>ALMOÇO</TD>
</TR>
<TR class="table_line">
	<TD class="table_line">DATA</TD>
	<TD class="table_line">HORA</TD>
	<TD class="table_line">KM</TD>
	<TD class="table_line">HORA</TD>
	<TD class="table_line">KM</TD>
	<TD class="table_line">HORA</TD>
	<TD class="table_line">KM</TD>
	<TD class="table_line">HORA</TD>
	<TD class="table_line">KM</TD>
	<TD class="table_line">HORA</TD>
	<TD class="table_line">KM</TD>
	<TD class="table_line">HORA</TD>
	<TD class="table_line">KM</TD>
</TR>
<?
if (strlen($os_manutencao) > 0) {
	// seleciona os visita
	$sql = "SELECT os_visita FROM tbl_os_visita WHERE os_revenda = $os_manutencao ORDER BY os_visita";
	$vis = pg_exec ($con,$sql);
}

for($i=0; $i<5; $i++){
	$class = 'table_line';

	if (strlen($os) > 0 AND strlen($msg_erro) == 0) {
		if (@pg_numrows($vis) > 0) {
			$os_visita = trim(@pg_result($vis,$i,os_visita));
		}

		if (strlen($os_visita) > 0) {
			$sql  = "SELECT tbl_os_visita.os_visita                                                       ,
							to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data                ,
							to_char(tbl_os_visita.hora_saida_sede, 'HH24:MI')      AS hora_saida_sede     ,
							tbl_os_visita.km_saida_sede                                                   ,
							to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente,
							tbl_os_visita.km_chegada_cliente                                              ,
							to_char(tbl_os_visita.hora_saida_almoco, 'HH24:MI')    AS hora_saida_almoco   ,
							tbl_os_visita.km_saida_almoco                                                 ,
							to_char(tbl_os_visita.hora_chegada_almoco, 'HH24:MI')  AS hora_chegada_almoco ,
							tbl_os_visita.km_chegada_almoco                                               ,
							to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente  ,
							tbl_os_visita.km_saida_cliente                                                ,
							to_char(tbl_os_visita.hora_chegada_sede, 'HH24:MI')    AS hora_chegada_sede   ,
							tbl_os_visita.km_chegada_sede
					FROM    tbl_os_visita
					WHERE   tbl_os_visita.os        = $os
					AND     tbl_os_visita.os_visita = $os_visita
					AND     tbl_os.posto            = $login_posto
					ORDER BY tbl_os_visita.os_visita";
			$res = pg_exec($con,$sql);

			if (@pg_numrows($res) > 0){
				$data					= trim(pg_result($res,$i,data));
				$os_visita				= trim(pg_result($res,$i,os_visita));
				$data					= trim(pg_result($res,$i,data));
				$hora_saida_sede		= trim(pg_result($res,$i,hora_saida_sede));
				$km_saida_sede			= trim(pg_result($res,$i,km_saida_sede));
				$hora_chegada_cliente	= trim(pg_result($res,$i,hora_chegada_cliente));
				$km_chegada_cliente		= trim(pg_result($res,$i,km_chegada_cliente));
				$hora_saida_almoco		= trim(pg_result($res,$i,hora_saida_almoco));
				$km_saida_almoco		= trim(pg_result($res,$i,km_saida_almoco));
				$hora_chegada_almoco	= trim(pg_result($res,$i,hora_chegada_almoco));
				$km_chegada_almoco		= trim(pg_result($res,$i,km_chegada_almoco));
				$hora_saida_cliente		= trim(pg_result($res,$i,hora_saida_cliente));
				$km_saida_cliente		= trim(pg_result($res,$i,km_saida_cliente));
				$hora_chegada_sede		= trim(pg_result($res,$i,hora_chegada_sede));
				$km_chegada_sede		= trim(pg_result($res,$i,km_chegada_sede));

				$class = 'table_line';
			}
		}
	}

	echo "<TR>\n";
	echo "	<TD class='$class'>".$data."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_saida_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_saida_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_chegada_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_chegada_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_saida_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_saida_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_chegada_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_chegada_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_saida_almoco."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_saida_almoco."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_chegada_almoco."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_chegada_almoco."&nbsp;</TD>\n";
	echo "</TR>\n";

	$data					= " ";
	$hora_saida_sede		= " ";
	$hora_chegada_cliente	= " ";
	$hora_saida_almoco		= " ";
	$hora_chegada_almoco	= " ";
	$hora_saida_cliente		= " ";
	$hora_chegada_sede		= " ";

	$km_saida_sede			= " ";
	$km_chegada_cliente		= " ";
	$km_saida_almoco		= " ";
	$km_chegada_almoco		= " ";
	$km_saida_cliente		= " ";
	$km_chegada_sede		= " ";

}
?>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" width="100">OBSERVAÇÕES</TD>
<?
if (strlen($obs) > 0){
?>
	<TD class="conteudo"><? echo $obs; ?>&nbsp;</TD>
</TR>
<?
}else{
?>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}
?>
</TABLE>


<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">COND. PAGTO</TD>
	<TD class="conteudo"><? echo $condicao_descricao; ?> &nbsp;</TD>
</TR>
<TR>
	<TD class="titulo" rowspan="3" colspan="3">Carimbo e Assinatura</TD>
	<TD class="conteudo" rowspan="3">&nbsp;<br><br><br></TD>
	<TD class="titulo"></TD>
	<TD class="conteudo"></TD>
</TR>
<TR>
	<TD class="titulo"></TD>
	<TD class="conteudo"></TD>
</TR>
<TR>
	<TD class="titulo"></TD>
	<TD class="conteudo"></TD>
</TR>
<TR>
	<TD class="titulo" colspan="6">A ASSINATURA DO CLIENTE CONFIRMA A EXECUÇÃO DO SERVIÇO E EVENTUAL TROCA DE PEÇAS, BEM COMO APROVA OS PREÇOS COBRADOS</TD>
</TR>
</TABLE>
<BR><BR>

<!-- ====== MODELO DO APARELHO ================ -->
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0" height=40>
<TR>
	<TD class="titulo">EM </TD>
	<TD class="conteudo" width='22%'> &nbsp;____&nbsp;/&nbsp;____&nbsp;/&nbsp;________</TD>
	<TD class="titulo">VISTO</TD>
	<TD class="conteudo" width='25%'> _______________________________</TD>
	<TD class="titulo">TÉCNICO</TD>
	<TD class="conteudo" width='23%'><? if (strlen($tecnico) > 0) echo $tecnico; else "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; ?>&nbsp;</TD>
</TR>
</TABLE>

<BR><BR>
<script language="JavaScript">
	window.print();
</script>

</BODY>
</html>
