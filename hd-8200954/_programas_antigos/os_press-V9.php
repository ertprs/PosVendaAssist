<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica == 7) {
	header ("Location: os_press_filizola.php?os=$os");
	exit;
}
if(strlen($_GET['os'])>0){
	$os=$_GET['os'];
	$sql = "SELECT motivo_atraso,observacao,os_reincidente FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	$motivo_atraso = pg_result($res,0,motivo_atraso);
	$observacao    = pg_result($res,0,observacao);
	$os_reincidente = pg_result($res,0,os_reincidente);
	if($os_reincidente=='t' AND ( strlen($motivo_atraso )==0 OR strlen($observacao)==0))
		header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
}


$sql = "SELECT  tbl_fabrica.os_item_subconjunto
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result ($res,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}

#------------ Le OS da Base de dados ------------#
$os = $HTTP_GET_VARS['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                    ,
					tbl_os.sua_os_offline                                            ,
					tbl_admin.login                              AS admin            ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao   ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY')   AS data_nf_saida	  ,
					tbl_os.tipo_atendimento                                           ,
					tbl_os.tecnico_nome                                               ,
					tbl_tipo_atendimento.descricao                 AS nome_atendimento,
					tbl_os.consumidor_nome                                            ,
					tbl_os.consumidor_fone                                            ,
					tbl_os.consumidor_endereco                                        ,
					tbl_os.consumidor_numero                                          ,
					tbl_os.consumidor_complemento                                     ,
					tbl_os.consumidor_bairro                                          ,
					tbl_os.consumidor_cep                                             ,
					tbl_os.consumidor_cidade                                          ,
					tbl_os.consumidor_estado                                          ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.nota_fiscal_saida										 ,
					tbl_os.cliente                                                   ,
					tbl_os.revenda                                                   ,
					tbl_os.qtde_produtos as qtde                                             ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf          ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado,
					tbl_os.defeito_reclamado_descricao                               ,
					tbl_defeito_constatado.descricao             AS defeito_constatado,
					tbl_defeito_constatado.codigo                AS defeito_constatado_codigo,
					tbl_causa_defeito.descricao                  AS causa_defeito    ,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.acessorios                                                ,
					tbl_os.consumidor_revenda                                        ,
					tbl_os.obs                                                       ,
					tbl_os.excluida                                                  ,
					tbl_produto.referencia                                           ,
					tbl_produto.descricao                                            ,
					tbl_produto.voltagem                                             ,
					tbl_os.qtde_produtos                                             ,
					tbl_os.serie                                                     ,
					tbl_os.codigo_fabricacao                                         ,
					tbl_posto_fabrica.codigo_posto               AS posto_codigo     ,
					tbl_posto.nome                               AS posto_nome       ,
					tbl_os_extra.os_reincidente,
					tbl_os_extra.orientacao_sac,
					tbl_os.solucao_os
			FROM    tbl_os
			JOIN    tbl_posto                   ON tbl_posto.posto         = tbl_os.posto
			JOIN    tbl_posto_fabrica           ON  tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_os_extra           ON tbl_os.os               = tbl_os_extra.os
			LEFT JOIN    tbl_admin              ON tbl_os.admin  = tbl_admin.admin
			LEFT JOIN    tbl_defeito_reclamado  ON tbl_os.defeito_reclamado  = tbl_defeito_reclamado.defeito_reclamado
			LEFT JOIN    tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN    tbl_causa_defeito      ON tbl_os.causa_defeito      = tbl_causa_defeito.causa_defeito
			LEFT JOIN    tbl_produto            ON tbl_os.produto            = tbl_produto.produto
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			WHERE   tbl_os.os = $os ";

	if ($login_e_distribuidor == "t") {
#		$sql .= "AND (tbl_os_extra.distribuidor = $login_posto OR tbl_os.posto = $login_posto) ";
	}else{
		$sql .= "AND tbl_os.posto = $login_posto ";
	}

	$res = pg_exec ($con,$sql);
#	echo $sql . "<br>- ". pg_numrows ($res);

	if (pg_numrows ($res) > 0) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$admin                       = pg_result ($res,0,admin);
		$data_digitacao              = pg_result ($res,0,data_digitacao);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$data_finalizada             = pg_result ($res,0,finalizada);
		$data_nf_saida				 = pg_result ($res,0,data_nf_saida);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero           = pg_result ($res,0,consumidor_numero);
		$consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$consumidor_cep              = pg_result ($res,0,consumidor_cep);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_cpf             = pg_result ($res,0,consumidor_cpf);
		
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$nota_fiscal_saida           = pg_result ($res,0,nota_fiscal_saida);
		$data_nf                     = pg_result ($res,0,data_nf);
		$cliente                     = pg_result ($res,0,cliente);
		$revenda                     = pg_result ($res,0,revenda);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$produto_referencia          = pg_result ($res,0,referencia);
		$produto_descricao           = pg_result ($res,0,descricao);
		$produto_voltagem            = pg_result ($res,0,voltagem);
		$serie                       = pg_result ($res,0,serie);
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
		$defeito_constatado          = pg_result ($res,0,defeito_constatado);
		$defeito_constatado_codigo   = pg_result ($res,0,defeito_constatado_codigo);
		$causa_defeito_codigo        = pg_result ($res,0,causa_defeito_codigo);
		$causa_defeito               = pg_result ($res,0,causa_defeito);
		$posto_codigo                = pg_result ($res,0,posto_codigo);
		$posto_nome                  = pg_result ($res,0,posto_nome);
		$obs                         = pg_result ($res,0,obs);
		$qtde_produtos               = pg_result ($res,0,qtde_produtos);
		$excluida                    = pg_result ($res,0,excluida);
		$os_reincidente              = trim(pg_result ($res,0,os_reincidente));
		$orientacao_sac              = trim(pg_result ($res,0,orientacao_sac));
		$sua_os_offline              = trim(pg_result ($res,0,sua_os_offline));
		$solucao_os =trim (pg_result($res,0,solucao_os));

		$qtde             = pg_result ($res,0,qtde);

		$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
		$tecnico_nome       = trim(pg_result($res,$i,tecnico_nome));
		$nome_atendimento   = trim(pg_result($res,$i,nome_atendimento));
		
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
			if($aparencia_produto=='NEW')$aparencia_produto= $aparencia_produto.' - Bom Estado';
			if($aparencia_produto=='USL')$aparencia_produto= $aparencia_produto.' - Uso intenso';
			if($aparencia_produto=='USN')$aparencia_produto= $aparencia_produto.' - Uso Normal';
			if($aparencia_produto=='USH')$aparencia_produto= $aparencia_produto.' - Uso Pesado';
			if($aparencia_produto=='ABU')$aparencia_produto= $aparencia_produto.' - Uso Abusivo';
			if($aparencia_produto=='ORI')$aparencia_produto= $aparencia_produto.' - Original, sem uso';
			if($aparencia_produto=='PCK')$aparencia_produto= $aparencia_produto.' - Embalagem';
	}
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = "Confirma��o de Ordem de Servi�o";

$layout_menu = 'os';
include "cabecalho.php";

?>
<style type="text/css">



body {
	margin: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}
.Tabela{
	border:1px solid #d2e4fc;
	background-color:#485989;
	}

</style>
<p>

<?
if (strlen($os_reincidente) > 0) {
	$sql = "SELECT  tbl_os.sua_os,
					tbl_os.serie
			FROM    tbl_os
			WHERE   tbl_os.os = $os_reincidente;";
	$res1 = pg_exec ($con,$sql);
	
	$sos   = trim(pg_result($res1,0,sua_os));
	$serie = trim(pg_result($res1,0,serie));
	
	echo "<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
	echo "<tr>";
	echo "<td class='titulo'>ANTEN��O</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='titulo'>ORDEM DE SERVI�O COM N�MERO DE S�RIE: $serie REINCIDENTE. ORDEM DE SERVI�O ANTERIOR: $sos</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";
}

if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
else 
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
?>
<?
 ##############################################
# se � um distribuidor da Britania consultando #
# exibe o posto                                #
 ##############################################

if ((strlen($tipo_atendimento)>0) and $login_fabrica==1) { 
?>
<center>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='tabela'>
<TR>
	<TD class="inicio" height='20' width='100'>&nbsp;&nbsp;Troca de Produto: </TD>
	<TD class="conteudo" height='20'><? echo " &nbsp;&nbsp;$nome_atendimento"; ?></TD>
</TR>
</TABLE>
</center>
<?
}  
if ($excluida == "t") { 
?>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
<TR>
	<TD  bgcolor="#FFE1E1" height='20'><h1>ORDEM DE SERVI�O EXCLU�DA</h1></TD>
</TR>
</TABLE>
<?
} 


// Mostra n�mero do Extrato que esta OS's est� - A pedido da Edina
// Fabio
// 29/12/2006
if ($login_fabrica==2){
	if (strlen(trim($data_finalizada))>0){
		$query = "SELECT extrato,
					to_char(data_pagamento,'DD/MM/YYYY')  AS data_pagamento,
						data_vencimento
				FROM tbl_os
				JOIN tbl_os_extra using(os)
				JOIN tbl_extrato using(extrato)
				LEFT JOIN tbl_extrato_pagamento using(extrato)
				WHERE tbl_os.os = $os
				AND tbl_os.fabrica = 2;";
		$result = pg_exec ($con,$query);
		if (pg_numrows ($result) > 0) {
			$extrato = pg_result ($result,0,extrato);
			$data_pg = pg_result ($result,0,data_pagamento);
			$data_vcto = pg_result ($result,0,data_vencimento);
			?>
			<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
					<TR >
						<TD class='inicio' style='text-align:center;'  colspan='4'>
							EXTRATO
						</td>
					</tr>
					<tr>
						<TD class='titulo' style='padding:0px 5px;' width='120' >
							N� EXTRATO
						</td>
						<td	class='conteudo' style='padding:0px 5px;' width='226' >
							<a href='http://www.telecontrol.com.br/assist/os_extrato_detalhe.php?extrato=<? echo $extrato; ?>&posto=<? echo $login_posto; ?>' ><? echo $extrato; ?></a>
						</td>
						<td class='titulo' style='padding:0px 5px;' width='120' >
							DATA DO PAGAMENTO
						</td>
						<td class='conteudo' style='padding:0px 5px;' width='226' >
							&nbsp;<b><? echo $data_pg; ?></b>
						</TD>
					</TR>

			</TABLE><br>

			<?
			
		}
			
		
	}
	
}// fim mostra n�mero do Extrato

if ($login_fabrica == 3 AND $login_e_distribuidor == "t"){
?>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
		<TR>
			<TD class="titulo" colspan="4">POSTO&nbsp;</TD>
		</TR>
		<TR>
			<TD class="conteudo" colspan="4"><? echo "$posto_codigo - $posto_nome"; ?></TD>
		</TR>
</TABLE>
<?
}
?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<tr >
		<td rowspan='4' class='conteudo' width='300' ><center>OS FABRICANTE<br>&nbsp;<b>
			<?
			if ($login_fabrica == 1) echo "<FONT SIZE='6' COLOR='#C67700'>".$posto_codigo;
			if (strlen($consumidor_revenda) > 0) echo $sua_os ."</FONT> - ". $consumidor_revenda;
			else echo $sua_os;
			?>
			<?
			if(strlen($sua_os_offline)>0){ 
			echo "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
			echo "<tr >";
			echo "<td class='conteudo' width='300' height='25' align='center'><BR><center>OS Off Line - $sua_os_offline</center></td>";
			echo "</tr>";
			echo "</table>";
}
			?>
			</b></center>
		</td>
		<td class='inicio' height='15' colspan='4'>&nbsp;<?if($sistema_lingua=='ES')echo "Fecha del OS";else echo "DATAS DA OS";?></td>
	</TR>
	<TR>
		<td class='titulo' width='100' height='15'><?if($sistema_lingua=='ES')echo "ABERTURA";else echo "ABERTURA";?>&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td>
		<td class='titulo' width='100' height='15'><?if($sistema_lingua=='ES')echo "DIGITACI�N";else echo "DIGITA��O";?>&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_digitacao ?></td>
	</tr>
	<tr>
		<td class='titulo' width='100' height='15'><?if($sistema_lingua=='ES')echo "CERRAMIENTO";else echo "FECHAMENTO";?>&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_fechamento ?></td>
		<td class='titulo' width='100' height='15'>FINALIZADA&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_finalizada ?></td>

	</tr>
	<tr>
		<TD class="titulo"  height='15'><?if($sistema_lingua=='ES')echo "FECHA COMPRA";else echo "DATA DA NF";?>&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
		<td class='titulo' width='100' height='15'>FECHADO EM &nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;
		<? 
		if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
						$sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
			$resD = pg_exec ($con,$sql_data);
			if (pg_numrows ($resD) > 0) {
				$total_de_dias_do_conserto = pg_result ($resD,0,final);
			}
			if($total_de_dias_do_conserto==0) echo 'no mesmo dia' ;
			else echo $total_de_dias_do_conserto;
			if($total_de_dias_do_conserto==1) echo ' dia' ;
			if($total_de_dias_do_conserto>1)  echo ' dias' ;
		}else{
			echo "N�O FINALIZADO";
		}
		?>
		</td>
	</tr>
</table>
<? 
// CAMPOS ADICIONAIS SOMENTE PARA LORENZETTI
if($login_fabrica==19){
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
	<TD class="titulo"  height='15' width='90'>ATENDIMENTO&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tipo_atendimento.' - '.$nome_atendimento ?></TD>
	<TD class="titulo" height='15' width='90'>NOME DO T�CNICO&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></TD>
</TR>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA LORENZETTI
?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<?	
	#######CONTEUDO ADICIONAL LENOXX - S� PARA O POSTO: 14254 - JUNDSERVICE	###############
	if(($login_posto==14254)and($login_fabrica==11)){?>
		<tr >
			<TD class="titulo" colspan='2' height='15' >NOTA FISCAL SAIDA&nbsp;</TD>
			<TD class="conteudo" colspan='1' height='15' >&nbsp;<? echo $nota_fiscal_saida; ?></TD>
			<TD class="titulo" height='15' >DATA NF SAIDA&nbsp;</TD>
			<TD class="conteudo" colspan='2' height='15' >&nbsp;<? echo $data_nf_saida; ?></TD>
		</tr>
	<?}
	################  FIM CONTEUDO LENOXX ##################
	?>

	<tr>
		<td class='inicio' height='15' colspan='4'>&nbsp;<?if($sistema_lingua=='ES')echo "INFORMACI�N DEL PRODUCTO";else echo "INFORMA��ES DO PRODUTO";?>&nbsp;</td>
	</tr>
	<tr >
		<TD class="titulo" height='15' width='90'><?if($sistema_lingua=='ES')echo "REFERENCIA";else echo "REFER�NCIA";?>&nbsp;</TD>
		<TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?></TD>
		<TD class="titulo" height='15' width='90'><?if($sistema_lingua=='ES')echo "DESCRICI�N";else echo "DESCRI��O";?>&nbsp;</TD>
		<TD class="conteudo" height='15' >&nbsp;<? echo $produto_descricao ?></TD>
		<TD class="titulo" height='15' width='90'><?if($sistema_lingua=='ES')echo "N. DE SERIE";else echo "N. DE S�RIE";?>&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $serie ?>&nbsp;</TD>
	<?if($login_fabrica==19){?>
		<TD class="titulo" height='15' width='90'><?if($sistema_lingua=='ES')echo "CTD";else echo "QTDE";?>QTDE&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $qtde ?>&nbsp;</TD>
	<?}?>
	</tr>
	<? if ($login_fabrica == 1) { ?>
	<tr>
		<TD class="titulo" height='15' width='90'><?if($sistema_lingua=='ES')echo "VOLTAJE";else echo "VOLTAGEM";?>&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $produto_voltagem ?></TD>
		<TD class="titulo" height='15' width='110'>C�DIGO FABRICA��O&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $codigo_fabricacao ?></TD>
		<TD class="conteudo" height='15' colspan='2'></TD>

	</tr>
	<? } ?>
</table>
<? if (strlen($aparencia_produto) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
	<td class='titulo' height='15' width='300'>APARENCIA GERAL DO APARELHO/PRODUTO</td>
	<td class="conteudo">&nbsp;<? echo $aparencia_produto ?></td>
</TR>
</TABLE>
<? } ?>
<? if (strlen($acessorios) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
	<TD class='titulo' height='15' width='300'>ACESS�RIOS DEIXADOS JUNTO COM O APARELHO</TD>
	<TD class="conteudo">&nbsp;<? echo $acessorios; ?></TD>
</TR>
</TABLE>
<? } ?>
<? if (strlen($defeito_reclamado) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
	<TR>
		<TD class='titulo' height='15'width='300'>&nbsp;<?if($sistema_lingua=='ES')echo "INFORMACIONES SOBRE LAS FALLA";else echo "INFORMA��ES SOBRE O DEFEITO";?></TD>
		<TD class="conteudo" >&nbsp;
			<?
			if (strlen($defeito_reclamado) > 0) {
				$sql = "SELECT tbl_defeito_reclamado.descricao
						FROM   tbl_defeito_reclamado
						WHERE  tbl_defeito_reclamado.descricao = '$defeito_reclamado'";
						//WHERE  tbl_defeito_reclamado.defeito_reclamado = '$defeito_reclamado'";

				$res = pg_exec ($con,$sql);

				if (pg_numrows($res) > 0) {
					$descricao_defeito = trim(pg_result($res,0,descricao));
					echo $descricao_defeito ." - ".$defeito_reclamado_descricao;
				}
			}
			?>
		</TD>
	</TR>
</TABLE>
<? } ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<TR>
		<TD  height='15' class='inicio' colspan='4'>&nbsp;<?if($sistema_lingua=='ES')echo "FALLAS";else echo "DEFEITOS";?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15' width='90'>RECLAMADO</TD>
		<TD class="conteudo" height='15' width='150'> &nbsp;<? echo $descricao_defeito ; if($defeito_reclamado_descricao)echo " - ".$defeito_reclamado_descricao; ?></TD>
		<TD class="titulo" height='15' width='90'><? if($login_fabrica==20){echo "REPARO";}else echo "CONSTATADO";?> &nbsp;</td>
		<td class="conteudo" height='15'>&nbsp;
			<? 
			if($login_fabrica==20)echo $defeito_constatado_codigo.' - ';
			echo $defeito_constatado;
			?>
		</TD>
	</TR>

	<TR>
		<TD class="titulo" height='15' width='90'>
		<?
		if($login_fabrica==6 or $login_fabrica==24)      echo "SOLU��O";
		elseif($login_fabrica==20) echo "DEFEITO";
		else                       echo "CAUSA"  ;
		?>
		&nbsp;</td>
		<td class="conteudo"colspan='3' height='15'>&nbsp;
		<? 
		if($login_fabrica==24 and strlen($solucao_os)>0){//takashi 30-11
			$sql="select descricao from tbl_solucao where solucao=$solucao_os and fabrica=$login_fabrica limit 1";
			$xres = pg_exec($con, $sql);
			$xsolucao = trim(pg_result($xres,0,descricao));
			echo "$xsolucao";
		}

		if($login_fabrica==6 ){
		if (strlen($solucao_os)>0){
			if ($data_digitacao>'05/11/2006'){
				$sql="select descricao from tbl_solucao where solucao=$solucao_os and fabrica=$login_fabrica limit 1";
				$xres = pg_exec($con, $sql);
				if (pg_numrows($xres)>0){
					$xsolucao = trim(pg_result($xres,0,descricao));
					echo "$xsolucao";	
				}else{
					$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
					$xres = pg_exec($con, $xsql);
					$xsolucao = trim(pg_result($xres,0,descricao));
					echo "$xsolucao";
				}

			}else{
				$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
				$xres = pg_exec($con, $xsql);
				$xsolucao = trim(pg_result($xres,0,descricao));
				echo "$xsolucao";
				}
			}
		}else{
			if($login_fabrica==20)echo $causa_defeito_codigo.' - ' ;
			echo $causa_defeito;
			}
 		?>
		</TD>
	</TR>
	<?
	if($login_fabrica==20){
		if($solucao_os){
			$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
			$xres = pg_exec($con, $xsql);
			
			$xsolucao = trim(pg_result($xres,0,descricao));
			echo "<tr>";
			echo "<td class='titulo' height='15' width='90'>IDENTIFICA��O&nbsp;</td>";
			echo "<td class='conteudo'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
			echo "</tr>";
		}
	}
	?>
</TABLE>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>&nbsp;<?if($sistema_lingua=='ES')echo "INFORMACIONES SOBRE EL CONSUMIDOR";else echo "INFORMA��ES SOBRE O CONSUMIDOR";?>&nbsp;</td>
	</tr>
	<TR>
		<TD class="titulo" width='90' height='15'><?if($sistema_lingua=='ES')echo "NOMBRE";else echo "NOME";?>&nbsp;</TD>
		<TD class="conteudo" height='15' width='300'>&nbsp;<? echo $consumidor_nome ?></TD>
		<TD class="titulo" width='80'><?if($sistema_lingua=='ES')echo "TEL�FONO";else echo "FONE";?>&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_fone ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'><?if($sistema_lingua=='ES')echo "ID CONSUMIDOR";else echo "CPF";?>&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cpf ?></TD>
		<TD class="titulo" height='15'><?if($sistema_lingua=='ES')echo "APARTADO POSTAL";else echo "CEP";?>&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cep ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'><?if($sistema_lingua=='ES')echo "DIRECCI�N";else echo "ENDERE�O";?>&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_endereco ?></TD>
		<TD class="titulo" height='15'>N�MERO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_numero ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>COMPLEMENTO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_complemento ?></TD>
		<TD class="titulo" height='15'><?if($sistema_lingua=='ES')echo "BARRIO";else echo "BAIRRO";?>&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_bairro ?></TD>
	</TR>
	<TR>
		<TD class="titulo"><?if($sistema_lingua=='ES')echo "CIUDAD";else echo "CIDADE";?>&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $consumidor_cidade ?></TD>
		<TD class="titulo">ESTADO&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $consumidor_estado ?></TD>
	</TR>
</TABLE>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>&nbsp;<?if($sistema_lingua=='ES')echo "INFORMACIONES SOBRE EL DISTRIBUIDOR";else echo "INFORMA��ES DA REVENDA";?></td>
	</tr>
	<TR>
		<TD class="titulo"  height='15' width='90'><?if($sistema_lingua=='ES')echo "NOMBRE";else echo "NOME";?>&nbsp;</TD>
		<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome ?></TD>
		<TD class="titulo"  height='15' width='80'><?if($sistema_lingua=='ES')echo "ID REVENDA";else echo "CNPJ";?>&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj ?></TD>
	</TR>
	<TR>
		<TD class="titulo"  height='15'><?if($sistema_lingua=='ES')echo "FACTURA COMERCIAL";else echo "NF N�MERO";?>&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<FONT COLOR="#FF0000"><? echo $nota_fiscal ?></FONT></TD>
		<TD class="titulo"  height='15'><?if($sistema_lingua=='ES')echo "FECHA COMPRA";else echo "DATA DA NF";?>&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
	</TR>
</TABLE>
<p></p>
<? //if (strlen($defeito_reclamado) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
	<TD colspan="<? if ($login_fabrica == 1) { echo "8"; }else{ echo "7"; } ?>" class='inicio'>&nbsp;DIAGN�STICOS - COMPONENTES - MANUTEN��ES EXECUTADAS</TD>
</TR>
<TR>
<!-- 	<TD class="titulo">EQUIPAMENTO</TD> -->
	<?
	if($os_item_subconjunto == 't') {
		echo"<TD class=\"titulo2\">SUBCONJUNTO</TD>";
		echo"<TD class=\"titulo2\">POSI��O</TD>";
	}
	?>
	<TD class="titulo2">COMPONENTE</TD>
	<TD class="titulo2">QTDE</TD>
	<? if ($login_fabrica == 1 and 1==2) echo "<TD class='titulo'>PRE�O</TD>"; ?>
	<TD class="titulo2">DIGIT.</TD>
	<TD class="titulo2"><? if($login_fabrica == 20) echo "PRE�O BRUTO"; else echo "DEFEITO";?></TD>
	<TD class="titulo2"><? if($login_fabrica == 20) echo "PRE�O L�QUIDO"; else echo "SERVI�O";?></TD>
	<TD class="titulo2">PEDIDO</TD>
	<TD class="titulo2">NOTA FISCAL</TD>
</TR>
<?
	$sql = "SELECT  tbl_produto.referencia                                        ,
					tbl_produto.descricao                                         ,
					tbl_os_produto.serie                                          ,
					tbl_os_produto.versao                                         ,
					tbl_os_item.serigrafia                                        ,
					tbl_os_item.pedido    AS pedido_item                          ,
					tbl_os_item.peca                                              ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
					tbl_defeito.descricao AS defeito                              ,
					tbl_peca.referencia   AS referencia_peca                      ,
					tbl_os_item_nf.nota_fiscal                                    ,
					tbl_peca.descricao    AS descricao_peca                       ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao,
					tbl_status_pedido.descricao     AS status_pedido              ,
					tbl_produto.referencia          AS subproduto_referencia      ,
					tbl_produto.descricao           AS subproduto_descricao       ,
					tbl_lista_basica.posicao        
			FROM	tbl_os_produto
			JOIN	tbl_os_item USING (os_produto)
			JOIN	tbl_produto USING (produto)
			JOIN	tbl_peca    USING (peca)
			JOIN	tbl_lista_basica       ON  tbl_lista_basica.produto = tbl_os_produto.produto
									       AND tbl_lista_basica.peca    = tbl_peca.peca
			LEFT JOIN    tbl_defeito USING (defeito)
			LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN    tbl_os_item_nf    ON  tbl_os_item.os_item      = tbl_os_item_nf.os_item
			LEFT JOIN    tbl_pedido        ON  tbl_os_item.pedido       = tbl_pedido.pedido
			LEFT JOIN    tbl_status_pedido ON  tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE   tbl_os_produto.os = $os
			ORDER BY tbl_peca.descricao";

	$sql = "SELECT  tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_os_produto.serie                                           ,
					tbl_os_produto.versao                                          ,
					tbl_os_item.os_item                                            ,
					tbl_os_item.serigrafia                                         ,
					tbl_os_item.pedido              AS pedido_item                 ,
					tbl_os_item.peca                                               ,
					tbl_os_item.posicao                                            ,
					tbl_os_item.obs                                                ,
					tbl_os_item.custo_peca                                         ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
					tbl_pedido.pedido_blackedecker  AS pedido_blackedecker         ,
					tbl_pedido.distribuidor                                        ,
					tbl_defeito.descricao           AS defeito                     ,
					tbl_peca.referencia             AS referencia_peca             ,
					tbl_os_item_nf.nota_fiscal                                     ,
					tbl_peca.descricao              AS descricao_peca              ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao ,
					tbl_status_pedido.descricao     AS status_pedido               ,
					tbl_produto.referencia          AS subproduto_referencia       ,
					tbl_produto.descricao           AS subproduto_descricao        ,
					tbl_os_item.preco                                              ,
					tbl_os_item.qtde                                               
			FROM	tbl_os_produto
			JOIN	tbl_os_item USING (os_produto)
			JOIN	tbl_produto USING (produto)
			JOIN	tbl_peca    USING (peca)
			LEFT JOIN tbl_defeito USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
			LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
			LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE   tbl_os_produto.os = $os
			ORDER BY tbl_peca.descricao";

	$res = pg_exec($con,$sql);
	$total = pg_numrows($res);

	for ($i = 0 ; $i < $total ; $i++) {
		$pedido        = trim(pg_result($res,$i,pedido_item));
		$pedido_blackedecker = trim(pg_result($res,$i,pedido_blackedecker));
		
		$os_item       = trim(pg_result($res,$i,os_item));
		$peca          = trim(pg_result($res,$i,peca));
		$nota_fiscal   = trim(pg_result($res,$i,nota_fiscal));
		$status_pedido = trim(pg_result($res,$i,status_pedido));
		$obs           = trim(pg_result($res,$i,obs));
		$distribuidor  = trim(pg_result($res,$i,distribuidor));
		$digitacao     = trim(pg_result($res,$i,digitacao_item));
		$preco         = trim(pg_result($res,$i,preco));

		$preco         = number_format($preco,2,',','.'); 

		if ($login_fabrica == 3 AND 1==2 ) {
			$nf = $status_pedido;
		}else{
			if (strlen ($nota_fiscal) == 0) {
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca;";
					$resx = pg_exec ($con,$sql);
					
					if (pg_numrows ($resx) > 0) {
						$nf = trim(pg_result($resx,0,nota_fiscal));
						$link = 1;
					}else{
						$condicao_01 = " 1=1 ";
						if (strlen ($distribuidor) > 0) {
							$condicao_01 = " tbl_faturamento.distribuidor = $distribuidor ";
						}
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido = $pedido
								AND     tbl_faturamento_item.peca = $peca
								AND     $condicao_01 ";
						$resx = pg_exec ($con,$sql);
						
						if (pg_numrows ($resx) > 0) {
							$nf = trim(pg_result($resx,0,nota_fiscal));
							$link = 1;
						}else{
							$nf = "Pendente";
							$link = 1;
						}
					}
				}else{
					$nf = "";
					$link = 0;
				}
			}else{
				$nf = $nota_fiscal;
			}
		}



?>
<TR>
<!-- 	<TD class="conteudo" style="text-align:left;"><? echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao); ?></TD> -->
	<?
	if($os_item_subconjunto == 't') {
		echo"<TD class=\"conteudo\" style=\"text-align:left;\">".pg_result($res,$i,subproduto_referencia) . " - " . pg_result($res,$i,subproduto_descricao)."</TD>";
		echo "<TD class=\"conteudo\" style=\"text-align:center;\">".pg_result($res,$i,posicao)."</TD>";
	}
	?>
	<TD class="conteudo" style="text-align:left;"><? echo pg_result($res,$i,referencia_peca) . " - " . pg_result($res,$i,descricao_peca) ?></TD>
	<TD class="conteudo" style="text-align:center;"><? echo pg_result($res,$i,qtde) ?></TD>
	<?
	if ($login_fabrica == 1 and 1==2) {
		echo "<TD class='conteudo' style='text-align:center;'>";
		echo number_format (pg_result($res,$i,custo_peca),2,",",".");
		echo "</TD>";
	}
	?>
	<TD class="conteudo" style="text-align:center;"><? echo pg_result($res,$i,digitacao_item) ?></TD>
	<TD class="conteudo" style="text-align:right;"><?   if($login_fabrica == 20)echo "R$ ".$preco_bruto; else echo pg_result($res,$i,defeito); ?></TD>
	<TD class="conteudo" style="text-align:right;"><?   if($login_fabrica == 20)echo "R$ ".$preco; else echo pg_result($res,$i,servico_realizado_descricao) ?></TD>
	<TD class="conteudo" style="text-align:CENTER;"><a href='pedido_finalizado.php?pedido=<? echo $pedido ?>' target='_blank'><? if ($login_fabrica == 1) echo $pedido_blackedecker; else echo $pedido; ?></a>&nbsp;</TD>
	<TD class="conteudo" style="text-align:CENTER;" nowrap>
	<?
	if (strtolower($nf) <> 'pendente'){
		if ($link == 1) {
			echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
		}else{
			echo "$nf ";
			//echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
		}
	}else{
		$sql  = "SELECT tbl_embarque.embarque, TO_CHAR (tbl_embarque.faturar,'DD/MM/YYYY') AS faturar FROM tbl_embarque JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque_item.os_item = $os_item AND tbl_embarque.faturar IS NOT NULL";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX) > 0) {
			echo "Embarque " . pg_result ($resX,0,embarque) . " - " . pg_result ($resX,0,faturar) ;
		}else{
			echo "$nf &nbsp;";
		}
	}
// 	if (strlen($obs) > 0) { 
// 		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
// 		echo "<TR>";
// 		echo "<TD class='conteudo'><b>OBS:</b>&nbsp;$obs</TD>";
// 		echo "</TR>";
// 		echo "</TABLE>";
// 	}
	?>
	</TD>
</tr>
<?
	}
?>
</TABLE>












<?
//incluido por Welligton 29/09/2006 - Fabricio chamado 472
echo "<BR>";
if (strlen($orientacao_sac) > 0){
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
	echo "<TR>";
	echo "<TD colspan=7 class='inicio'>&nbsp;Orinta��es do SAC ao Posto Autorizado</TD>";
	echo "</TR>";
	echo "<TR>";
	echo "<TD class='conteudo'>$orientacao_sac</TD>";
	echo "</TR>";
	echo "</TABLE>";
}
?>








<BR>

<? 

if (strlen($obs) > 0) { 
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
	echo "<TR>";
	echo "<TD class='conteudo'><b>OBS:</b>&nbsp;$obs</TD>";
	echo "</TR>";
	echo "</TABLE>";
} 
?>





<!--            Valores da OS           -->
<?
if ($login_fabrica == "20") {
	$sql = "SELECT mao_de_obra FROM tbl_produto_defeito_constatado WHERE produto = (SELECT produto FROM tbl_os WHERE os = $os) AND defeito_constatado = (SELECT defeito_constatado FROM tbl_os WHERE os = $os)";
	$res = pg_exec ($con,$sql);
	$mao_de_obra = 0 ;
	if (pg_numrows ($res) == 1) {
		$mao_de_obra = pg_result ($res,0,0);
	}


	$sql = "SELECT tabela , desconto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$tabela = 0 ;
	$desconto = 0;
	if (pg_numrows ($res) == 1) {
		$tabela = pg_result ($res,0,tabela);
		$desconto = pg_result ($res,0,desconto);
		//echo $desconto;
	}
	if (strlen ($desconto) == 0) $desconto = "0";

	if (strlen ($tabela) > 0) {

		$sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total 
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os";
		$res = pg_exec ($con,$sql);
		$pecas = 0 ;
		if (pg_numrows ($res) == 1) {
			$pecas = pg_result ($res,0,0);
		}
		$pecas = number_format ($pecas,2,",",".");
	}else{
		$pecas = "0";
	}

	echo "<table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";
	echo "<td align='center' bgcolor='#E1EAF1'><b>";
	if($sistema_lingua=='ES')echo "Valor de Piezas";else echo "Valor das Pe�as";
	echo "</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>";
	if($sistema_lingua=='ES')echo "Mano de Obra";else echo "M�o-de-Obra";
	echo "</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Total</b></td>";
	echo "</tr>";

	echo "<tr style='font-size: 12px ; color:#000000 '>";
	echo "<td align='right'>" ;

	$valor_liquido = 0;

	if ($desconto > 0 and $pecas > 0) {
		$pecas = str_replace (".","",$pecas);
		$pecas = str_replace (",",".",$pecas);

		$sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$produto = pg_result ($res,0,0);
		}
		//echo 'peca'.$pecas;
		if( $produto == '20567' ){
			$desconto = '0.2238';
			$valor_desconto = round ( (round ($pecas,2) * $desconto ) ,2);
			//echo $valor_desconto;
		}else{
			$valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
		}

		$valor_liquido = $pecas - $valor_desconto ;


		
	}

	$total = $valor_liquido + $mao_de_obra;

	$total          = number_format ($total,2,",",".")         ;
	$mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
	$valor_desconto = number_format ($valor_desconto,2,",",".");
	$valor_liquido  = number_format ($valor_liquido ,2,",",".");
	echo "<font color='#333377'><b>$valor_liquido</b>" ;
	echo "</td>";
	echo "<td align='center'>$mao_de_obra</td>";

	echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
	echo "</tr>";

	echo "</table>";

}
?>






<BR><BR>
<!-- =========== FINALIZA TELA NOVA============== -->
<table cellpadding='10' cellspacing='0' border='0' align='center'>
<tr>
<td><a href="os_cadastro.php"><img src="imagens/btn_lancanovaos.gif"></a></td>
<td><a href="os_print.php?os=<? echo $os ?>" target="_blank"><img src="imagens/btn_imprimir.gif"></a></td>
</tr>
</table>

<!--
<div id='container'>
	<div id="contentleft2" style="width: 150px;">
		&nbsp;
	</div>

	<div id="contentleft2" style="width: 150px;">
		<a href="os_cadastro.php"><img src="imagens/btn_lancanovaos.gif"></a>
	</div>
	<div id="contentleft2" style="width: 150px;">
		<a href="os_print.php?os=<? echo $os ?>" target="_blank"><img src="imagens/btn_imprimir.gif"></a>
	</div>
</div>

<div id='container'>
	&nbsp;
</div>
-->
<? include "rodape.php"; ?>
