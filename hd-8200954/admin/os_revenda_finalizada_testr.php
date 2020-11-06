<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";

if ($login_fabrica == 6) {
	if (strlen($_GET['os_revenda']) == 0 ) {
		$msg_erro = "Sem número da OS....";	
	}
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

if ($btn_acao == "explodir" AND strlen($msg_erro)==0) {
	// executa funcao de explosao
	//HD 19570
	$sql = "UPDATE tbl_os_revenda SET 
				admin = $login_admin
			WHERE os_revenda = $os_revenda
			AND   fabrica    = $login_fabrica";
	$res = @pg_exec ($con,$sql);
	$sql = "SELECT fn_explode_os_revenda($os_revenda,$login_fabrica)";
	$res = @pg_exec ($con,$sql);
	$msg_erro = substr(pg_errormessage($con),6);
	
	if( strpos($msg_erro,'data_nf_superior_data_abertura') ) {
		$msg_erro="A data de nota fiscal não pode ser maior que a data de abertura. Por favor, clique em botão Alterar para fazer a correção.";
	}
	if( strpos($msg_erro,'fora da garantia') ) {
		$msg_erro="Produto Fora da Garantia";
	}
	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT  sua_os, posto
				FROM	tbl_os_revenda
				WHERE	os_revenda = $os_revenda
				AND		fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);
		$sua_os = @pg_result($res,0,sua_os);
		$posto  = @pg_result($res,0,posto);

		// redireciona para os_revenda_explodida.php
		header("Location: os_revenda_explodida.php?sua_os=$sua_os&posto=$posto");
		exit;
	}
}

if(strlen($os_revenda) > 0){
	if($login_fabrica == 1) $left = " LEFT "; 
	// seleciona do banco de dados
	$sql = "SELECT   tbl_os_revenda.sua_os                                                ,
					 tbl_os_revenda.obs                                                   ,
					 to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					 to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					 tbl_revenda.nome  AS revenda_nome                                    ,
					 tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					 tbl_revenda.fone  AS revenda_fone                                    ,
					 tbl_revenda.email AS revenda_email                                   ,
					 tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
					 tbl_posto.nome    AS posto_nome                                      ,
					 tbl_os_revenda.tipo_os                                               ,
					 tbl_os_revenda.consumidor_email
			FROM tbl_os_revenda
			$left JOIN tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			LEFT JOIN tbl_posto USING (posto)
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os_revenda.os_revenda = $os_revenda
			AND   tbl_os_revenda.fabrica    = $login_fabrica";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$sua_os         = pg_result($res,0,sua_os);
		$data_abertura  = pg_result($res,0,data_abertura);
		$data_digitacao = pg_result($res,0,data_digitacao);
		$revenda_nome   = pg_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_result($res,0,revenda_cnpj);
		$revenda_fone   = pg_result($res,0,revenda_fone);
		$revenda_email  = pg_result($res,0,revenda_email);
		$posto_codigo   = pg_result($res,0,posto_codigo);
		$posto_nome     = pg_result($res,0,posto_nome);
		$obs            = pg_result($res,0,obs);
		$tipo_os        = pg_result($res,0,tipo_os);
		$consumidor_email  = pg_result($res,0,consumidor_email);
	}else{
		header('Location: os_revenda.php');
		exit;
	}
}

$title			= "Cadastro de Ordem de Serviço - Revenda"; 
$layout_menu	= "callcenter";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
	
}

.formulario2{
	background-color:#D9E2EF;
	font:11px Arial;
	
}

table.formulario td{
	border:1px solid #596d9b;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #000;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	empty-cells:show;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

</style>

<!--[if lt IE 8]>
<style>
table.tabela2{
	empty-cells:show;
    border-collapse:collapse;
	border-spacing: 2px;
}
</style>
<![endif]-->

<br>
<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class="formulario2">
	<? if (strlen ($msg_erro) > 0) { ?>
		<tr class="msg_erro">
		<td colspan='3'>
			<? echo $msg_erro ?>
		</td>
	</tr>
	<? } ?>
	<tr class="titulo_tabela"><td colspan="3">OS Revenda Finalizada</td></tr>
	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">
			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr class="subtitulo">
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Digitação</font>
					</td>
				</tr>
				<tr>
					<td nowrap align='left' >
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if ($login_fabrica == 1) echo $posto_codigo; echo $sua_os; ?></font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $data_abertura ?></font>
					</td>
					<td nowrap >
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $data_digitacao ?></font>
					</td>
				</tr>
				
			</table>
			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr class="subtitulo">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">E-mail Revenda</font>
					</td>
				</tr>
				<tr>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_nome ?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_cnpj ?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_fone ?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_email ?></font>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr class="subtitulo">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do Posto</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Posto</font>
					</td>
				</tr>
				<tr>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $posto_codigo ?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $posto_nome ?></font>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr class="subtitulo">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>
				</tr>
				<tr>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $obs ?></font>
					</td>
				</tr>
				<? if($login_fabrica == 1) { ?>
								<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">E-mail de Contato</font>
					</td>
				</tr>
				<tr>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $consumidor_email ?>
					</td>
				</tr>
				<? } ?> 
			</table>

		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="3" align="center" class="formulario2">
	<tr>
		<td>
		<table width="690" border="0" cellpadding="2" cellspacing="1" align="center" class="formulario" style="TABLE-LAYOUT: fixed;">
			
			<tr class="subtitulo">
				<? if ($login_fabrica == 1) { ?>
				<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cód. Fabric.</font></td>
				<? } ?>
				<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font></td>
				<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição do Produto</font></td>
				<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número de Série</font></td>
				<? if ($login_fabrica == 1) { ?>
				<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Type</font></td>
				<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Embalagem Original</font></td>
				<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Sinal de Uso</font></td>
				<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número da NF</font></td>
				<? } ?>
			</tr>
		<?
			// monta o FOR
			$qtde_item = 20;

				if ($os_revenda){
					// seleciona do banco de dados
					$sql =	"SELECT tbl_os_revenda_item.os_revenda_item    ,
									tbl_os_revenda.explodida               ,
									tbl_os_revenda_item.produto            ,
									tbl_os_revenda_item.serie              ,
									tbl_os_revenda_item.type               ,
									tbl_os_revenda_item.embalagem_original ,
									tbl_os_revenda_item.sinal_de_uso       ,
									tbl_os_revenda_item.codigo_fabricacao  ,
									tbl_os_revenda_item.nota_fiscal        ,
									tbl_produto.referencia                 ,
									tbl_produto.descricao                  ,
									tbl_produto.voltagem                   
							FROM	tbl_os_revenda
							JOIN	tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
							JOIN	tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
							WHERE	tbl_os_revenda.os_revenda = $os_revenda";
					$res = pg_exec($con, $sql);

					for ($i=0; $i<pg_numrows($res); $i++)
					{
						$referencia_produto = pg_result($res,$i,referencia);
						$produto_descricao  = pg_result($res,$i,descricao);
						$produto_voltagem   = pg_result($res,$i,voltagem);
						$produto_serie      = pg_result($res,$i,serie);
						$type               = pg_result($res,$i,type);
						$embalagem_original = pg_result($res,$i,embalagem_original);
						$sinal_de_uso       = pg_result($res,$i,sinal_de_uso);
						$codigo_fabricacao  = pg_result($res,$i,codigo_fabricacao);
						$nota_fiscal        = pg_result($res,$i,nota_fiscal);
						$explodida          = pg_result($res,$i,explodida);
		?>
			<tr>
				<? if ($login_fabrica == 1) { ?>
				<td align="left">
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $codigo_fabricacao ?></font>
				</td>
				<? } ?>
				<td align="left">
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $referencia_produto ?></font>
				</td>
				<td align="left">
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<?
					echo $produto_descricao;
					if (strlen($produto_voltagem) > 0) echo " - ".$produto_voltagem;
					?>
					</font>
				</td>
				<td align="left">
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_serie ?></font>
				</td>
				<? if ($login_fabrica == 1) { ?>
				<td >
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $type ?></font>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if ($embalagem_original == 't') echo "Sim"; else echo "Não"; ?></font>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if ($sinal_de_uso == 't') echo "Sim"; else echo "Não"; ?></font>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $nota_fiscal ?></font>
				</td>
				<? } ?>
			</tr>
		<?
					}
				}
		?>
		</table>
	</td>
  </tr>
 </table>
<? //HD 21501
if ($login_fabrica==1) { 
	if(strlen($os_revenda) > 0 ){
		$sql="SELECT tipo_atendimento
				from tbl_os_revenda
				where os_revenda=$os_revenda";
		$res=pg_exec($con,$sql);
		$tipo_atendimento=pg_result($res,0,tipo_atendimento);
		if (strlen($tipo_atendimento) > 0) {
		
		echo "<table width='650' border='0' cellpadding='0' cellspacing='0' align='center'  class='formulario'>";
		
		echo "</table>";
		}
	}
}
?>

<input type='hidden' name='btn_acao' value=''>

<table width="700" class="formulario" align="center">
	
	<tr>
		<td align="center" style="border:0px;">
			<?php if(strlen( $explodida ) == 0) { ?>
				<input type="button" style="background:url(imagens/btn_alterarcinza.gif); width:77px; cursor:pointer;" value="&nbsp;"  onclick="javascript: document.location='os_revenda.php?os_revenda=<? echo $os_revenda; ?>'" ALT="Alterar" border='0'>
				<input type="button" style="background:url(imagens/btn_explodir<?if($login_fabrica==19){echo "_2";}?>); width:80px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='explodir' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Explodir" border='0' >
			<?php } ?>
			<input type="button" style="background:url(imagens/btn_imprimir.gif); width:77px; cursor:pointer;" value="&nbsp;" onclick="javascript: window.open('os_revenda_print.php?os_revenda=<? echo $os_revenda; ?>','osrevenda');" ALT="Imprimir" border='0'>
		</td>
	</tr>
	
</table>

<br>

<center>
	<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='red'>Para OS de Troca de Revenda ser encaminhada para aprovação e fechamento, é necessário explodir a OS</b></font>
</center>
<br>
<center><a href="os_revenda_consulta.php?<?echo $_COOKIE['cookget']; ?>"><input type="button" style="background:url(imagens/btn_voltarparaconsulta.gif); width:152px; cursor:pointer;" value="&nbsp;"></a></center>

</form>
<br>

<? include 'rodape.php'; ?>