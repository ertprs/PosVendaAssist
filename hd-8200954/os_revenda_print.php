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

if ($login_fabrica == 11){
	if ($_GET['e']){
		$extrato = $_GET['e'];
	}
}

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
		$join_revenda = " LEFT JOIN tbl_revenda ON  tbl_os_revenda.revenda = tbl_revenda.revenda ";
	}

	$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os_revenda.fabrica IN (11,172) " : " tbl_os_revenda.fabrica = $login_fabrica ";

	// seleciona do banco de dados
	$sql = "SELECT   tbl_os_revenda.sua_os                               ,
	tbl_os_revenda.obs                                                   ,
	tbl_os_revenda.obs_causa AS observacao_callcenter                    ,
	to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
	to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
	$campos
	tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
	tbl_posto_fabrica.contato_fone_comercial 							 ,
	tbl_posto.nome    AS posto_nome                                      ,
	to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
	tbl_os_revenda.nota_fiscal                                           ,
	tbl_os_revenda.consumidor_nome                                       ,
	tbl_os_revenda.consumidor_revenda                                    ,
	tbl_os_revenda.consumidor_email 									 ,
	tbl_os_revenda.consumidor_endereco    								 ,
	tbl_os_revenda.consumidor_numero      								 ,
	tbl_os_revenda.consumidor_complemento 								 , 
	tbl_os_revenda.consumidor_bairro      								 ,
	tbl_os_revenda.consumidor_fone 										 ,
	tbl_os_revenda.consumidor_cidade 									 ,
	tbl_os_revenda.consumidor_estado                                     ,
	tbl_os_revenda.valor_adicional_justificativa                         ,
	tbl_os_revenda.consumidor_cpf 										 ,
	tbl_tipo_atendimento.descricao AS tipo_atendimento_descricao         ,
	tbl_os_revenda.consumidor_cnpj                                       ,
	tbl_os_revenda.campos_extra                                     
	FROM tbl_os_revenda
	$join_revenda
	JOIN tbl_fabrica ON tbl_os_revenda.fabrica = tbl_fabrica.fabrica
	LEFT JOIN tbl_posto USING (posto)
	LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
	LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os_revenda.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
	WHERE tbl_os_revenda.os_revenda = $os_revenda
	AND tbl_os_revenda.posto = $login_posto
	AND {$cond_pesquisa_fabrica} ";
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
		$contato_fone_comercial = pg_fetch_result($res, 0, contato_fone_comercial);
		$posto_nome      = pg_result($res,0,posto_nome);
		$data_nf         = pg_result($res,0,data_nf);
		$nota_fiscal     = pg_result($res,0,nota_fiscal);
		$consumidor_nome = pg_result($res,0,consumidor_nome);
		$consumidor_cnpj = pg_result($res,0,consumidor_cnpj);
		$consumidor_cpf  = pg_fetch_result($res, 0, consumidor_cpf);
		$consumidor_email = pg_fetch_result($res, 0, consumidor_email);

		$consumidor_endereco = pg_fetch_result($res, 0, consumidor_endereco);
		$consumidor_numero = pg_fetch_result($res, 0, consumidor_numero);
		$consumidor_complemento = pg_fetch_result($res, 0, consumidor_complemento);
		$consumidor_bairro = pg_fetch_result($res, 0, consumidor_bairro);
		$consumidor_cidade = pg_fetch_result($res, 0, consumidor_cidade);
		$consumidor_estado = pg_fetch_result($res, 0, consumidor_estado);

		$consumidor_fone = pg_fetch_result($res, 0, consumidor_fone);
		$obs             = pg_result($res,0,obs);
		$observacao_callcenter = pg_fetch_result($res, 0, observacao_callcenter);
		$valor_adicional_justificativa = pg_result($res,0,valor_adicional_justificativa);
		$consumidor_revenda = pg_fetch_result($res, 0, consumidor_revenda);
		$tipo_atendimento_descricao = pg_fetch_result($res, 0, tipo_atendimento_descricao);

		if($login_fabrica == 137){
			$dados_adicionais 		= json_decode($dados_adicionais);
			$transportadora 		= $dados_adicionais->transportadora;
			$nota_fiscal_saida 		= $dados_adicionais->nota_fiscal_saida;
			$data_nota_fiscal_saida = $dados_adicionais->data_nota_fiscal_saida;
		}

		$campos_extra = pg_fetch_result($res, 0, campos_extra);
		$campos_extra = json_decode($campos_extra, true);
		$inscricao_estadual = $campos_extra["inscricao_estadual"];

		if ($login_fabrica == 178){
			if (!empty($consumidor_revenda)){
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

			$sql_agendamento = "
				SELECT TO_CHAR(data_agendamento, 'DD/MM/YYYY') AS data_ultimo_agendamento
				FROM tbl_tecnico_agenda WHERE os_revenda = $os_revenda 
				AND fabrica = $login_fabrica AND data_cancelado IS NULL 
				ORDER BY tecnico_agenda DESC LIMIT 1";
			$res_agendamento = pg_query($con, $sql_agendamento);
			
			if (pg_num_rows($res_agendamento) > 0){
				$data_ultimo_agendamento = pg_fetch_result($res_agendamento, 0, 'data_ultimo_agendamento');
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

$login_fabrica_nome = strtolower(str_replace(" ", "", $login_fabrica_nome));
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
<style type='text/css' media='print'>
.noPrint {display:none;}
</style> 

<body>
	<?php if ($login_fabrica <> 178){ ?>
	<div class='noPrint'>
		<input type=button name='fbBtPrint' value='Versão Matricial'
		onclick="window.location='os_revenda_print_matricial.php?os_revenda=<? echo $os_revenda; ?>'">
		<br>
		<hr class='noPrint'>
	</div>
	<?php } ?>
	<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
		<TR>
			<TD>
				<?php if ($login_fabrica == 178){ ?>
					<IMG SRC="logos/roca_nova_logo.jpg" style="max-height: 70px; max-width: 200px;" ALT="ORDEM DE SERVIÇO"></TD>
				<?php }else{?>
					<IMG SRC="logos/logo_<? echo strtolower ($login_fabrica_nome) ?>.jpg" style="max-height: 70px; max-width: 200px;" ALT="ORDEM DE SERVIÇO">
				<?php } ?>
			</TD>
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

	<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
		<TR>
			<TD class="titulo" colspan="4" style='text-align: center;'>Informações sobre a Ordem de Serviço</TD>
		</TR>
		<TR>
			<TD class="titulo"><?= (in_array($login_fabrica, array(169,170))) ? 'OS REVENDEDOR' : 'OS FABRICANTE'; ?></TD>
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
	</TABLE>
	<?php 
		if($login_fabrica== 19){
			$aux_revenda = "DO ATACADO";
		} else {
			if ($login_fabrica != 178){
				$aux_revenda = "DA REVENDA";
			}
		}
	?>
	<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
		<TR>
			<TD class="titulo">NOME <?=$aux_revenda;?></TD>
			<TD class="titulo">
				<?php 
					if ($login_fabrica == 178){
						echo "CPF/CNPJ";
					}else{
						echo "CNPJ ".$aux_revenda;
					}
				?>
			</TD>

			<?php if ($login_fabrica == 178 AND !empty($inscricao_estadual)){ ?>
			<TD class='titulo'>Inscr. Estatudal</TD>
			<?php } ?>
			<? if($login_fabrica != 15){ ?>
			<TD class="titulo">FONE</TD>
			<TD class="titulo">E-MAIL</TD>
			<? } ?>
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

			<?php if ($login_fabrica == 178 AND !empty($inscricao_estadual)){ ?>
				<TD class='conteudo'><?=$inscricao_estadual?></TD>
			<?php } ?>

			<? if($login_fabrica != 15){ ?>
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
			<? } ?>
		</TR>
	</TABLE>
	<?php if(in_array($login_fabrica, array(24,178)) OR ($login_fabrica == 137 && $posto_interno == true)){//HD5492?>
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
			<?php if ($login_fabrica == 178 AND in_array($consumidor_revenda, array("C", "S", "R"))){ ?>
			<TD class="conteudo"><? if(strlen($consumidor_endereco) > 0) echo $consumidor_endereco; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($consumidor_numero) > 0) echo $consumidor_numero; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($consumidor_complemento) > 0) echo $consumidor_complemento; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($consumidor_bairro) > 0) echo $consumidor_bairro; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($consumidor_cidade) > 0) echo $consumidor_cidade; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($consumidor_estado) > 0) echo $consumidor_estado; else echo "&nbsp;"; ?></TD>
			<?php } else {?>
			<TD class="conteudo"><? if(strlen($revenda_endereco) > 0) echo $revenda_endereco; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($revenda_numero) > 0) echo $revenda_numero; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($revenda_complemento) > 0) echo $revenda_complemento; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($revenda_bairro) > 0) echo $revenda_bairro; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($cidade_revenda) > 0) echo $cidade_revenda; else echo "&nbsp;"; ?></TD>
			<TD class="conteudo"><? if(strlen($estado_revenda) > 0) echo $estado_revenda; else echo "&nbsp;"; ?></TD>
			
			<?php } ?>
		</TR>
	</TABLE>

	<?

}

if($login_fabrica == 137 && $posto_interno == true){
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
<?php if ($login_fabrica <> 178){ ?>
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
<?php } ?>
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
<?php } else {?>
<TABLE class="borda" width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
	<TR>
		<TD class="titulo">OBSERVAÇÕES</TD>
	</TR>
	<TR>
		<TD height="230px"; class="conteudo"><? echo $obs ?></TD>
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
		<?php
		if (in_array($login_fabrica, [144])) { ?>
			<td class="titulo">OS</td>
			<td class="titulo">TIPO DE ATENDIMENTO</td>
		<?php
		}
		?>
		<TD class="titulo">REFERÊNCIA PRODUTO</TD>
		<TD class="titulo">DESCRIÇÃO DO PRODUTO</TD>
		<?php
		if (in_array($login_fabrica, [144])) { ?>
			<td class="titulo">NOTA FISCAL</td>
			<td class="titulo">DATA NF</td>
		<?php
		}
		?>
		<?	if(in_array($login_fabrica, array(19, 186))) {
			echo "<TD class='titulo'>QTDE</TD>";
		}else{
			if (!in_array($login_fabrica, array(169,170))) {
				echo "<TD class='titulo'>";
				if($login_fabrica==35){
					echo "PO#";
				}else if ($login_fabrica == 178){
					echo "DEF. RECLAMADO";
				}else{
					echo "NÚMERO DE SÉRIE";
				}
				echo "</TD>";

				if ($login_fabrica == 178){
					echo "<TD class='titulo'>NF</TD>";
				}
			}
		}
		if ($login_fabrica == 178){
			echo "<td class='titulo'>Procedente  /  Improcedente</td>";
		}
		if (in_array($login_fabrica, array(94,169,170))) { ?>
			<td class='titulo'>DEFEITO RECLAMADO</td>
		<? }

		if ($login_fabrica == 137 && $posto_interno == true) { ?>
			<TD class="titulo">CFOP</TD>
			<TD class="titulo">QUANTIDADE</TD>
			<TD class="titulo">VALOR UNITÁRIO</TD>
			<TD class="titulo">TOTAL DA NOTA</TD>
		<? } ?>

	</TR>

	<?
	// monta o FOR
	$qtde_item = 20;

	if ($os_revenda){

		if ($login_fabrica == 11 && $extrato) {
			$join = " JOIN tbl_os on tbl_os_revenda_item.os_lote = tbl_os.os 
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os and tbl_os_extra.extrato = $extrato";
		}

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
			$res = pg_exec($con, $sql);
		}else{
			if (in_array($login_fabrica, [144])) {
				$join = "JOIN tbl_os o ON o.sua_os LIKE '{$os_revenda}'||'-%' AND o.fabrica = {$login_fabrica}
						 JOIN tbl_os_produto ON tbl_os_produto.os = o.os
						 AND tbl_os_produto.produto = o.produto
						 LEFT JOIN tbl_tipo_atendimento ON o.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento";
				$campoOs = ", o.sua_os, tbl_tipo_atendimento.descricao as descricao_atendimento";
				$distinct = "DISTINCT ON (o.os)";
			}

			$sql = "
				SELECT {$distinct}
					{$campos_defeito}
					tbl_os_revenda_item.os_revenda_item ,
					tbl_os_revenda_item.produto         ,
					tbl_os_revenda_item.serie           ,
					tbl_os_revenda_item.qtde            ,
					tbl_os_revenda_item.nota_fiscal 	,
					TO_CHAR(tbl_os_revenda_item.data_nf, 'dd/mm/yyyy') as data_nf,
					tbl_os_revenda_item.marca AS marca_produto,
					tbl_os_revenda_item.rg_produto      ,
					tbl_os_revenda_item.defeito_constatado_descricao,
					tbl_produto.referencia              ,
					tbl_produto.preco AS produto_preco,
					tbl_produto.parametros_adicionais AS produto_parametros_adicionais,
					tbl_produto.familia AS produto_familia,
					tbl_produto.descricao               
					{$campoOs}
			FROM tbl_os_revenda
			JOIN tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
			JOIN tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
			$join
			WHERE tbl_os_revenda.os_revenda = $os_revenda
			AND tbl_os_revenda.posto = $login_posto
			AND {$cond_pesquisa_fabrica} ";
			$res = pg_exec($con, $sql);
		}

		if (pg_numrows($res)== 0 && $login_fabrica == 11 && $extrato){
			?>
			<td colspan="4">Sem OS no extrato consultado</td>
			<?
		}else{
			$total_itens = pg_numrows($res);
			for ($i=0; $i<pg_numrows($res); $i++) {

				$os_revenda_item    = pg_result($res,$i,os_revenda_item);
				$referencia_produto = pg_result($res,$i,referencia);
				$qtde               = pg_result($res,$i,qtde);
				$produto_descricao  = pg_result($res,$i,descricao);
				$defeito_constatado_descricao  = pg_result($res,$i,defeito_constatado_descricao);
				$produto_serie      = pg_result($res,$i,serie);
				$nota_fiscal 		= pg_fetch_result($res, $i, nota_fiscal);
				$sua_os             = pg_fetch_result($res, $i, sua_os);
				$data_nf            = pg_fetch_result($res, $i, data_nf);
				$descricao_atendimento = pg_fetch_result($res, $i, descricao_atendimento);

				if($login_fabrica == 137 && $posto_interno == true){
					$qtde 	= pg_result($res,$i,qtde);
					$dados 	= pg_result($res,$i,rg_produto);

					$dados 			= json_decode($dados);
					$cfop 			= $dados->cfop;
					$valor_unitario = $dados->vu;
					$valor_nota 	= $dados->vt;
				}	
					if ($login_fabrica == 178){
						$produto_id 			= pg_fetch_result($res, $i, produto);
						$defeito_reclamado_descricao    = pg_fetch_result($res,$i,defeito_reclamado_descricao);
						$produto_preco 			= pg_fetch_result($res, $i, produto_preco);
						$produto_parametros_adicionais  = pg_fetch_result($res, $i, produto_parametros_adicionais);
						$produto_parametros_adicionais  = json_decode($produto_parametros_adicionais, true);
						$fora_linha 			= $produto_parametros_adicionais["fora_linha"];
						#$marcas 			= $produto_parametros_adicionais["marcas"];
						$marca 				= pg_fetch_result($res, $i, marca_produto);
						$produto_familia 		= pg_fetch_result($res, $i, produto_familia);
						$preco_base         		= $produto_preco + ($produto_preco/100 *20);
				        
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
					}else{
				?>
						<TR>
							<?php
							if (in_array($login_fabrica, [144])) { ?>
								<td class="conteudo"><?= $sua_os ?></td>
								<td class="conteudo"><?= $descricao_atendimento ?></td>
							<?php
							}
							?>
							<TD class="conteudo"><? echo $referencia_produto ?></TD>
							<TD class="conteudo"><? echo $produto_descricao ?></TD>
							<?
							if (in_array($login_fabrica, [144])) { ?>
								<td class="conteudo"><?= $nota_fiscal ?></td>
								<td class="conteudo"><?= $data_nf ?></td>
							<?php
							}

							if (in_array($login_fabrica, array(19, 186))) {
								echo "<TD class='conteudo'> $qtde </TD>";
							} else {
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
				<?php
					}
				if (in_array($login_fabrica, array(169,170))) { ?>
					<tr>
						<td colspan="3" class="titulo">OBSERVAÇÕES</td>
					</tr>
					<tr>
						<td colspan="3" class="conteudo" height="100" style="border:dotted 1px #a0a0a0;"></td>
					</tr>
				<? }
			}
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
		<TABLE width="<?=($login_fabrica == 178)? '1000px' : '600px'?>" border="0" cellspacing="2" cellpadding="0">
			<TR>
				<TD 	rowspan='1' class='conteudo'><h2>Em: 
					<?php
						if ($login_fabrica == 178 AND !empty($data_ultimo_agendamento)){
							echo $data_ultimo_agendamento;
						}else{
							echo $data_abertura;
						}
					?>
					</h2>
				</TD>
			</TR>
			<TR>
				<TD  rowspan='1' class='conteudo'><h2>
				<?php 
					if ($login_fabrica == 178){
						echo $consumidor_nome;
					}else{
						echo $revenda_nome;
					}
				?> 
				- Assinatura:  _________________________________________</h2></TD>
			</TR>
			<?php if ($login_fabrica == 178){ ?>
			<TR>
				<TD class='conteudo'><h2>Responsável pela visita: __________________________________ Assinatura: __________________________________</h2></TD>
				<td valign="top">
					<p style="margin-top: -40px;margin-left:8px;font-size:10px;font-family:'Arial'"><?= traduz("anexar.arquivos.via.mobile") ?></p>
					<p><img src="" class="qr_press" style="display:none;width:100px;"></p>
				</td>
			</tr>
			<?php } ?>
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

