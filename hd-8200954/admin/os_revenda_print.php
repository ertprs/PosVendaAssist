<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);

if ($login_fabrica == 11){
	if ($_GET['e']){
		$extrato = $_GET['e'];
	}
}

if(strlen($os_revenda) > 0){

	$campos     = '';

	if (in_array($login_fabrica, [178])) {
		$campos = "
		tbl_revenda.endereco    	AS revenda_endereco                          ,
		tbl_revenda.numero      	AS revenda_numero                            ,
		tbl_revenda.complemento 	AS revenda_complemento                       ,
		tbl_revenda.cidade 		    AS revenda_cidade                       	 ,
		tbl_revenda.bairro      	AS revenda_bairro                            , ";
	}

	// seleciona do banco de dados
	$sql = "SELECT   tbl_os_revenda.sua_os                                                ,
					 tbl_os_revenda.obs                                                   ,
					 to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					 to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					 to_char(tbl_os_revenda.data_nf,'DD/MM/YYYY') AS data_nf              ,
					 $campos
					 tbl_revenda.nome  AS revenda_nome                                    ,
					 tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					 tbl_revenda.fone  AS revenda_fone                                    ,
					 tbl_revenda.email AS revenda_email                                   ,
					 tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
					 tbl_posto_fabrica.contato_fone_comercial 							  ,
					 tbl_posto.nome    AS posto_nome                                      ,
					 tbl_os_revenda.nota_fiscal                                           ,
					 tbl_os_revenda.consumidor_nome                                       ,
					 tbl_os_revenda.consumidor_cnpj                                       ,
					 tbl_os_revenda.consumidor_revenda 									  ,
					 tbl_tipo_atendimento.descricao AS tipo_atendimento_descricao         ,
					 tbl_os_revenda.obs_causa AS observacao_callcenter                    ,
					 tbl_os_revenda.consumidor_endereco                                   ,
					 tbl_os_revenda.consumidor_numero                                     ,
					 tbl_os_revenda.consumidor_complemento                                ,
					 tbl_os_revenda.consumidor_bairro                                     ,
					 tbl_os_revenda.consumidor_email                                      ,
					 tbl_os_revenda.consumidor_fone 									  ,
					 tbl_os_revenda.consumidor_cidade 									  ,
					 tbl_os_revenda.consumidor_estado                                     ,
					 tbl_os_revenda.consumidor_cpf 										  ,
					 tbl_os_revenda.tipo_atendimento                                      ,
					 tbl_os_revenda.tipo_os                                               ,
					 tbl_os_revenda.os_geo                                                ,
					 tbl_os_revenda.campos_extra
			FROM tbl_os_revenda
			LEFT JOIN tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			JOIN tbl_fabrica USING (fabrica)
			LEFT JOIN tbl_posto USING (posto)
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os_revenda.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
			WHERE tbl_os_revenda.os_revenda = $os_revenda
			AND tbl_os_revenda.fabrica = $login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0){
		$sua_os         = pg_fetch_result($res,0,sua_os);
		$data_abertura  = pg_fetch_result($res,0,data_abertura);
		$data_digitacao = pg_fetch_result($res,0,data_digitacao);
		$data_nf        = pg_fetch_result($res,0,'data_nf');
		$revenda_nome   = pg_fetch_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_fetch_result($res,0,revenda_cnpj);
		$revenda_fone   = pg_fetch_result($res,0,revenda_fone);
		$revenda_email  = pg_fetch_result($res,0,revenda_email);
		$revenda_endereco= pg_result($res,0,revenda_endereco);
		$revenda_numero  = pg_result($res,0,revenda_numero);
		$revenda_complemento = pg_result($res,0,revenda_complemento);
		$revenda_bairro  = pg_result($res,0,revenda_bairro);
		$revenda_cidade  = pg_result($res,0,revenda_cidade);
		$posto_codigo   = pg_fetch_result($res,0,posto_codigo);
		$posto_nome     = pg_fetch_result($res,0,posto_nome);
		$obs            = pg_fetch_result($res,0,obs);
		$nota_fiscal     = pg_fetch_result($res,0,nota_fiscal);
		$consumidor_nome = pg_fetch_result($res,0,consumidor_nome);
		$consumidor_cnpj = pg_fetch_result($res,0,consumidor_cnpj);
		$tipo_atendimento = pg_fetch_result($res,0,tipo_atendimento);
		$motivo          = pg_fetch_result($res,0,tipo_os);
		$os_geo          = pg_fetch_result($res,0,os_geo);
		$consumidor_revenda = pg_fetch_result($res, 0, consumidor_revenda);
		$tipo_atendimento_descricao = pg_fetch_result($res, 0, tipo_atendimento_descricao);
		$consumidor_cpf = pg_fetch_result($res, 0, consumidor_cpf);
		$consumidor_fone = pg_fetch_result($res, 0, consumidor_fone);
		$consumidor_email = pg_fetch_result($res, 0, consumidor_email);
		$consumidor_endereco = pg_fetch_result($res, 0, consumidor_endereco);
		$consumidor_numero = pg_fetch_result($res, 0, consumidor_numero);
		$consumidor_complemento = pg_fetch_result($res, 0, consumidor_complemento);
		$consumidor_bairro = pg_fetch_result($res, 0, consumidor_bairro);
		$consumidor_cidade = pg_fetch_result($res, 0, consumidor_cidade);
		$consumidor_estado = pg_fetch_result($res, 0, consumidor_estado);
		$contato_fone_comercial = pg_fetch_result($res, 0, contato_fone_comercial);
		$observacao_callcenter = pg_fetch_result($res, 0, observacao_callcenter);
		$campos_extra = pg_fetch_result($res, 0, campos_extra);

		$campos_extra = json_decode($campos_extra, true);
		$inscricao_estadual = $campos_extra["inscricao_estadual"];

		if ($login_fabrica == 178 AND !empty($consumidor_revenda)){
			switch ($consumidor_revenda) {
				case 'C':
					$consumidor_revenda_label = " - CONSUMIDOR";
					break;
				case 'R':
					$consumidor_revenda_label = " - REVENDA";
					break;
				case 'S':
					$consumidor_revenda_label = " - CONSTRUTORA";
					break;
				
			}
		}

		if (!empty($revenda_cidade)){
			$sql_cidade_revenda = "SELECT nome, estado FROM tbl_cidade WHERE cidade = $revenda_cidade";
			$res_cidade_revenda = pg_query($con, $sql_cidade_revenda);

			$cidade_revenda = pg_fetch_result($res_cidade_revenda, 0, 'nome');
			$estado_revenda = pg_fetch_result($res_cidade_revenda, 0, 'estado');
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
}

.quadrado {
	width: 18px;
    height: 16px;
    border: 1px solid;
    border-color: #403f3f;
    float: left;
}
.quadrado_im {
	width: 18px;
    height: 16px;
    border: 1px solid;
    border-color: #403f3f;
    margin-left: 70px;
}
.td_border {
	border: solid;
    border-bottom-width: 1px;
    border-top-width: 0px;
    border-left-width: 0px;
    border-right-width: 0px;
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

<TABLE width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD>
		<?php if ($login_fabrica == 178){ ?>
		<IMG SRC="logos/roca_nova_logo.jpg" style="max-height: 70px; max-width: 200px;" ALT="ORDEM DE SERVIÇO"></TD>
		<?php } else if ($login_fabrica == 144) { ?>
			<IMG SRC="logos/logo_hikari.jpg" style="max-height: 70px; max-width: 200px;" ALT="ORDEM DE SERVIÇO"></TD>
		<?php
		} else {?>
		<IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="ORDEM DE SERVIÇO">
		<?php } ?>
	</TD>
</TR>
</TABLE>

<br>

<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="6" style='text-align: center;'>Informações sobre a Ordem de Serviço</TD>
</TR>

<?php if (in_array($login_fabrica, [141])) {?>
	<TR>
		<TD class="titulo">OS FABRICANTE</TD>
		<TD class="titulo">DATA DA ABERTURA</TD>
		<TD class="titulo">DATA DA DIGITAÇÃO</TD>
		<TD class="titulo">DATA NF</TD>
		<TD class="titulo">NOTA FISCAL</TD>
	</TR>
	<TR>
		<TD class="conteudo"><? echo $sua_os ?></TD>
		<TD class="conteudo"><? echo $data_abertura ?></TD>
		<TD class="conteudo"><? echo $data_digitacao ?></TD>
		<TD class="conteudo"><? echo $data_nf ?></TD>
		<TD class="conteudo"><? echo $nota_fiscal ?></TD>
	</TR>
<?php } else { ?>
	<TR>
		<TD class="titulo">OS FABRICANTE</TD>
		<TD class="titulo">DATA DA ABERTURA DA OS</TD>
		<TD class="titulo">DATA DA DIGITAÇÃO DA OS</TD>
		<?php if ($login_fabrica == 178){ ?>
		<TD class="titulo">TIPO ATENDIMENTO</TD>
		<?php } ?>
	</TR>
	<TR>
		<TD class="conteudo"><? echo $sua_os; echo $consumidor_revenda_label; ?></TD>
		<TD class="conteudo"><? echo $data_abertura ?></TD>
		<TD class="conteudo"><? echo $data_digitacao ?></TD>
		<?php if ($login_fabrica == 178){ ?>
		<TD class="conteudo"><?=$tipo_atendimento_descricao?></TD>
		<?php } ?>
	</TR>
<?php }  ?>
</TABLE>

<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME</TD>
	<TD class="titulo">
		<?php 
			if ($login_fabrica == 178){
				echo "CPF/CNPJ";
			}else{
				echo "CNPJ DA REVENDA";
			}
		?>
	</TD>
	<?php if ($login_fabrica == 178 AND strlen(trim($inscricao_estadual)) > 0){ ?>
	<td class="titulo">INSCR. ESTADUAL</td>
	<?php } ?>
	<TD class="titulo">FONE</TD>
	<TD class="titulo">E-MAIL</TD>

</TR>
<TR>
	<TD class="conteudo">
		<?php 
			if ($login_fabrica == 178){
				echo $consumidor_nome;
			}else{
				echo $revenda_nome;
			}
		?>
	</TD>
	<TD class="conteudo">
		<?php 
			if ($login_fabrica == 178){
				echo $consumidor_cpf;
			}else if ($login_fabrica == 15){
				echo substr($revenda_cnpj,0,8);
			}else{
				echo $revenda_cnpj;
			}
		?>		
	</TD>
	<?php if ($login_fabrica == 178 AND strlen(trim($inscricao_estadual)) > 0){ ?>
	<TD class="conteudo">
		<?=$inscricao_estadual?>
	</TD>
	<?php } ?>
	<TD class="conteudo">
		<?php 
			if ($login_fabrica == 178){
				echo $consumidor_fone;
			}else{
				echo $revenda_fone;
			}
		?>		
	</TD>
	<TD class="conteudo">
		<?php
			if ($login_fabrica == 178){
				echo $consumidor_email;
			}else{
				echo $revenda_email;
			}
		?>	
	</TD>
</TR>
</TABLE>

<?php if($login_fabrica == 178 ){ ?>
<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
	<TR>
		<TD class="titulo">ENDEREÇO</TD>
		<TD class="titulo">Nº</TD>
		<TD class="titulo">COMPL.</TD>
		<TD class="titulo">BAIRRO</TD>
		<TD class="titulo">CIDADE</TD>
		<TD class="titulo">ESTADO</TD>
	</TR>
	<TR>
		<TD class="conteudo"><? if(strlen($consumidor_endereco) > 0) echo $consumidor_endereco; else echo "&nbsp;"; ?></TD>
		<TD class="conteudo"><? if(strlen($consumidor_numero) > 0) echo $consumidor_numero; else echo "&nbsp;"; ?></TD>
		<TD class="conteudo"><? if(strlen($consumidor_complemento) > 0) echo $consumidor_complemento; else echo "&nbsp;"; ?></TD>
		<TD class="conteudo"><? if(strlen($consumidor_bairro) > 0) echo $consumidor_bairro; else echo "&nbsp;"; ?></TD>
		<?php if (in_array($consumidor_revenda, array("C", "S", "R"))) { ?>
		<TD class="conteudo"><? if(strlen($consumidor_cidade) > 0) echo $consumidor_cidade; else echo "&nbsp;"; ?></TD>
		<TD class="conteudo"><? if(strlen($consumidor_estado) > 0) echo $consumidor_estado; else echo "&nbsp;"; ?></TD>	
		<?php } else { ?> 
		<TD class="conteudo"><? if(strlen($cidade_revenda) > 0) echo $cidade_revenda; else echo "&nbsp;"; ?></TD>
		<TD class="conteudo"><? if(strlen($estado_revenda) > 0) echo $estado_revenda; else echo "&nbsp;"; ?></TD>
		<?php } ?>
	</TR>
</TABLE>
<?php } ?>

<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">CÓDIGO DO POSTO</TD>
	<TD class="titulo">NOME DO POSTO</TD>
	<TD class="titulo">FONE DO POSTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $posto_codigo ?></TD>
	<TD class="conteudo"><? echo $posto_nome ?></TD>
	<TD class="conteudo"><? echo $contato_fone_comercial ?></TD>
</TR>
</TABLE>
<?
if($login_fabrica==19){
	if(strlen($tipo_atendimento)>0){
		$sqll = "SELECT codigo, descricao from tbl_tipo_atendimento where tipo_atendimento = $tipo_atendimento";
		$ress = pg_query($con,$sqll);
			$xcodigo_atendimento = pg_fetch_result($ress,0,codigo);
			$xdescricao          = pg_fetch_result($ress,0,descricao);
	}
	if(strlen($motivo)>0){
		$sqll = "SELECT descricao from tbl_tipo_os where tipo_os = $motivo";
		$ress = pg_query($con,$sqll);
		$xmotivo          = pg_fetch_result($ress,0,descricao);
	}
?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">Tipo Atendimento</TD>
	<TD class="titulo">Motivo</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo "$xcodigo_atendimento - $xdescricao";?></TD>
	<TD class="conteudo"><? echo $xmotivo ?></TD>
</TR>
</TABLE>

<?}?>

<?php 
if ($login_fabrica == 178){ 
	if (!empty($observacao_callcenter)){
?>
		<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
			<TR>
				<TD class="titulo">OBSERVAÇÕES SAC</TD>
			</TR>
			<TR>
				<TD class="conteudo"><? echo $observacao_callcenter ?></TD>
			</TR>
		</TABLE>
<?php
	}
	if (!empty($obs)){
?>
		<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
			<TR>
				<TD class="titulo">OBSERVAÇÕES DA OS</TD>
			</TR>
			<TR>
				<TD class="conteudo"><? echo $obs ?></TD>
			</TR>
		</TABLE>
<?php
	}
?>
<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
	<TR>
		<TD class="titulo">OBSERVAÇÕES DO TÉCNICO</TD>
	</TR>
	<TR>
		<TD height="165px"; class="conteudo"></TD>
	</TR>
</TABLE>
<?php } else { ?>
	<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
	<TR>
		<TD class="titulo">OBSERVAÇÕES</TD>
	</TR>
	<TR>
		<TD class="conteudo"><? echo $obs ?></TD>
	</TR>
	</TABLE>
<?php } ?>
<TABLE width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
	<TR>
		<TD>&nbsp;</TD>
	</TR>
</TABLE>
<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">PRODUTOS</TD>
</TR>
</TABLE>

<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">REFERÊNCIA PRODUTO</TD>
	<TD class="titulo">DESCRIÇÃO DO PRODUTO</TD>
	<?php 
	if (in_array($login_fabrica, [141])) {
		echo "<TD class='titulo'>PREÇO UNITÁRIO</TD>";
	}else if ($login_fabrica == 178){
		echo "<TD class='titulo'>DEF. RECLAMADO</TD>";
	}else {
		echo "<TD class='titulo'>VOLTAGEM</TD>";
	}
	?>	
	<TD class="titulo">
		<? if($login_fabrica==35){
				echo "PO#";
			}else if ($login_fabrica == 178){
				echo "NF";
			}else{
				echo "NÚMERO DE SÉRIE";
			}
		?>
	</TD>
	<?php if ($login_fabrica == 178) { ?>
		<TD class='titulo'>Procedente  /  Improcedente</TD>
	<?php } ?>
	<?php if($login_fabrica == 137){ ?>
		<TD class="titulo">CFOP</TD>
		<TD class="titulo">QUANTIDADE</TD>
		<TD class="titulo">VALOR UNITÁRIO</TD>
		<TD class="titulo">TOTAL DA NOTA</TD>
	<?php } 

	if ($login_fabrica == 144) { ?>
		<TD class="titulo">QTDE</TD>
		<TD class="titulo">NOTA FISCAL</TD>
	<?php
	}
	?>
</TR>

<?
	// monta o FOR
	$qtde_item = 20;
	if($login_fabrica ==1 and $os_geo =='t'){
		$join= " JOIN tbl_os on tbl_os.os = tbl_os_revenda_item.os_lote ";
		$cond= " AND tbl_os.excluida is not true ";
	}

	if ($login_fabrica == 11 && $extrato) {
		$join= " JOIN tbl_os on tbl_os_revenda_item.os_lote = tbl_os.os 
		JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os and tbl_os_extra.extrato = $extrato";
	}

	if ($login_fabrica == 178){
		$campos_defeito = "tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao,";
		$join = "LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os_revenda_item.defeito_reclamado AND tbl_defeito_reclamado.fabrica = $login_fabrica";
	}
		if ($os_revenda){
			// seleciona do banco de dados
			if($login_fabrica == 178){
				$sql = "SELECT
						tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao,
						tbl_os.produto ,
						tbl_os.serie ,
						tbl_os.nota_fiscal ,
						tbl_os_revenda_item.os_revenda_item ,
						tbl_os_revenda_item.produto ,
						tbl_os_revenda_item.serie ,
						tbl_os_revenda_item.qtde ,
						tbl_os_revenda_item.nota_fiscal ,
						tbl_os_revenda_item.marca AS marca_produto,
						tbl_os_revenda_item.defeito_constatado_descricao,
						tbl_os_revenda_item.rg_produto ,
						tbl_produto.referencia ,
						tbl_produto.parametros_adicionais AS produto_parametros_adicionais,
						tbl_produto.familia AS produto_familia,
						tbl_produto.descricao ,
						tbl_produto.preco ,
						tbl_produto.voltagem
					FROM tbl_os_revenda
					JOIN tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN tbl_os_campo_extra ON tbl_os_revenda.os_revenda = tbl_os_campo_extra.os_revenda
						AND tbl_os_revenda_item.os_revenda_item = tbl_os_campo_extra.os_revenda_item
					JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os.fabrica = {$login_fabrica}
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
					LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
					WHERE tbl_os_revenda.os_revenda = {$os_revenda}";
			}else{
				$sql = "SELECT   
							{$campos_defeito}
							tbl_os_revenda_item.os_revenda_item ,
							tbl_os_revenda_item.produto         ,
							tbl_os_revenda_item.serie           ,
							tbl_os_revenda_item.qtde            ,
							tbl_os_revenda_item.nota_fiscal 	,
							tbl_os_revenda_item.marca AS marca_produto,
							tbl_os_revenda_item.defeito_constatado_descricao,
							tbl_os_revenda_item.rg_produto      ,
							tbl_produto.referencia              ,
							tbl_produto.parametros_adicionais AS produto_parametros_adicionais,
							tbl_produto.familia AS produto_familia,
							tbl_produto.descricao               ,
							tbl_produto.preco                   ,
							tbl_produto.voltagem                
						FROM tbl_os_revenda
						JOIN tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
						JOIN tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
						$join
						WHERE tbl_os_revenda.os_revenda = $os_revenda
						$cond";
			}
			$res = pg_query($con, $sql);
			if (pg_num_rows($res)== 0 && $login_fabrica == 11 && $extrato){
				?>
				<td colspan="4">Sem OS no extrato consultado</td>
				<?
			}else{

				for ($i=0; $i<pg_num_rows($res); $i++)
				{

					$os_revenda_item    = pg_fetch_result($res,$i,os_revenda_item);
					$referencia_produto = pg_fetch_result($res,$i,referencia);
					$produto_descricao  = pg_fetch_result($res,$i,descricao);
					$produto_voltagem   = pg_fetch_result($res,$i,voltagem);
					$produto_serie      = pg_fetch_result($res,$i,serie);
					$produto_preco      = pg_fetch_result($res,$i,'preco');
					$qtde 				= pg_fetch_result($res,$i,qtde);
					$nota_fiscal 		= pg_fetch_result($res, $i, nota_fiscal);

					if($login_fabrica == 137){
						$qtde 	= pg_fetch_result($res,$i,qtde);
						$dados 	= pg_fetch_result($res,$i,rg_produto);

						$dados 			= json_decode($dados);
						$cfop 			= $dados->cfop;
						$valor_unitario = $dados->vu;
						$valor_nota 	= $dados->vt;

					}	

					if ($login_fabrica == 178){
						$produto_id 					= pg_fetch_result($res, $i, produto);
						$defeito_reclamado_descricao    = pg_fetch_result($res,$i,defeito_reclamado_descricao);
						$produto_parametros_adicionais  = pg_fetch_result($res, $i, produto_parametros_adicionais);
						$produto_parametros_adicionais  = json_decode($produto_parametros_adicionais, true);
				        	$fora_linha 			= $produto_parametros_adicionais["fora_linha"];
				        	$qtde 				= pg_fetch_result($res,$i,qtde);
				        	$marca 				= pg_fetch_result($res, $i, marca_produto);
				        	$produto_familia 		= pg_fetch_result($res, $i, produto_familia);
						$preco_base         		= $produto_preco + ($produto_preco/100 *20);
				        	$nota_fiscal 			= pg_fetch_result($res, $i, nota_fiscal);

						if ($fora_linha === true AND strlen($marca) > 0 AND !empty($produto_id) AND !empty($produto_familia) AND !empty($produto_preco)){
							$sqlProd = "
								SELECT produto, referencia, descricao, preco
								FROM tbl_produto
								WHERE fabrica_i = {$login_fabrica}
								AND produto NOT IN ($produto_id)
								AND parametros_adicionais::jsonb->>'marcas' = '$marca'
								AND lista_troca = 't'
								AND familia = $produto_familia
								ORDER BY descricao ASC ";
						    $resProd = pg_query($con, $sqlProd);

						    if (pg_num_rows($resProd) > 0){

							for ($y=0; $y < pg_num_rows($resProd); $y++) { 
							    $preco_produto_troca = pg_fetch_result($resProd, $y, "preco");
							    if (($preco_produto_troca >= $produto_preco AND $preco_produto_troca < $preco_base)){
								$produtos[] = array(
								    "referencia" => pg_fetch_result($resProd, $y, "referencia").' - '.substr(pg_fetch_result($resProd, $y, "descricao"), 0, 30)
								);
								$contador = count($produtos);
								if ($contador == 3){
								    break;
								}
							    }
							}
						    }
						}
					?>
							<TR>
								<TD class="conteudo td_border"><?=$referencia_produto ?></TD>
								<TD class="conteudo td_border">
									<strong><?=$produto_descricao ?></strong>
									<?php if ($fora_linha === true){
										echo "<br/><span style='color:red'>Produto fora de linha, possíveis produtos para troca:</span>";
										foreach ($produtos as $key => $value) {
											echo "<br/>".$value['referencia'];
										}
									} 
									?>
								</TD>
								<TD class='conteudo td_border'><?=$defeito_reclamado_descricao?></TD>
								<TD class='conteudo td_border'><?=$nota_fiscal?></TD>
								<TD class='conteudo td_border' align='center'>
									<div class='quadrado'></div>
									<div class='quadrado_im'></div>
									<p style="padding-bottom: 5px;">Defeito Constatado: ________________________________________________________________</p>
								</TD>
							</TR>
					<?php
					} else {
				?>
						<TR>
							<TD class="conteudo"><? echo $referencia_produto ?></TD>
							<TD class="conteudo"><? echo $produto_descricao ?></TD>
							<?php 
								if (in_array($login_fabrica, [141])) {
									echo "<TD class='conteudo'>" . number_format($produto_preco, 2, ',', '.') . "</TD>";
								} else {
									echo "<TD class='conteudo'>{$produto_voltagem}</TD>";
								}
							?>
							<TD class="conteudo"><? echo $produto_serie ?></TD>
							<?php
							if($login_fabrica == 137){
								?>
								<TD class="conteudo"><? echo $cfop ?></TD>
								<TD class="conteudo"><? echo $qtde ?></TD>
								<TD class="conteudo"><? echo $valor_unitario ?></TD>
								<TD class="conteudo"><? echo $valor_nota ?></TD>
								<?php
							}

							if ($login_fabrica == 144) { ?>
								<TD class="conteudo"><? echo $qtde ?></TD>
								<TD class="conteudo"><? echo $nota_fiscal ?></TD>
							<?php
							}

							?>
						</TR>
				<?php
					}
				}
			}
		}
?>
</TABLE>

<br>

<TABLE width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class='conteudo'><h2>Em: <? echo $data_abertura ?></h2></TD>
</TR>
<TR>
	<TD class='conteudo'><h2>
		<?php 
			if ($login_fabrica == 178){
				echo $consumidor_nome;
			}else{
				echo $revenda_nome;
			}
		?>
		- Assinatura:  _________________________________________</h2>
	</TD>
</TR>
	<?php if ($login_fabrica == 178){ ?>
		<TR>
			<TD class='conteudo'><h2>Responsável pela visita: __________________________________ Assinatura: __________________________________</h2></TD>
			<td valign="top">
				<p style="margin-top: -40px;margin-left:8px;font-size:10px;font-family:'Arial'"><?= traduz("anexar.arquivos.via.mobile") ?></p>
				<p><img src="" class="qr_press" style="display:none;width:100px;"></p>
			</td>
		</TR>
	<?php } ?>
</TABLE>
<?if($login_fabrica==19){?>

<TABLE width="<?=($login_fabrica == 178)? '1000px' : '650px'?>" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE><BR>
<TABLE width="<?=($login_fabrica == 178)? '1000px' : '650px'?>" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
	<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
</TR>
<TR>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
	<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
</TR>
</TABLE>
<?}?>
<br><br>

<?php
if ($login_fabrica == 178){ 
	include_once 'os_print_qrcode.php';

	include 'TdocsMirror.php';
	include 'controllers/ImageuploaderTiposMirror.php';

	$imageUploaderTipos = new ImageuploaderTiposMirror($login_fabrica,$con);

	try{
	    $comboboxContext = $imageUploaderTipos->get();
	}catch(\Exception $e){    
	    $comboboxContext = [];
	}

	$comboboxContextJson = [];
	$comboboxContextOptionsAux = [];
	foreach ($comboboxContext as $context => $options) {
	    foreach ($options as $value) {
	        $comboboxContextOptionsAux[$value['value']] = $value['label'];
	        $comboboxContextJson[$context][] = $value["value"];
	    }
	}


?>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script type="text/javascript">
	    getQrCode();
	    function getQrCode() {
	        $.ajax("controllers/QrCodeImageUploader.php",{
	            async: true,
	            type: "POST",
	            data: {
	                "ajax": "requireQrCode",
	                "options": <?=json_encode($comboboxContextJson["os"])?>,
	                "title": 'Upload de Arquivos',
	                "objectId": <?=$_GET['os_revenda']?>,
	                "contexto": "revenda",
	                "fabrica": <?=$login_fabrica?>,
	                "hashTemp": "false",
	                "print": "true"
	            }
	        }).done(function(response){
	            $(".qr_press").attr("src",response.qrcode)          
	            $(".qr_press").show('fast', function () {
	                window.print();
	            });
	        });
	    }
	</script>
<?php } else { ?>
<script language="JavaScript">
	window.print();
</script>
<?php } ?>

</BODY>
</html>

