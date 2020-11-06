<?
//conforme chamado 474 (fabricio -  britania) na hr em que eram buscada as informacoes da OS, estava buscando na forma antiga, ou seja, estava buscando informacoes do cliente na tbl_cliente, com o novo metodo as info do consumidor sao gravados direto na tbl_os, com isso hr que estava buscando info do cliente estava buscando no local errado -  Takashi 31/09/2006
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';

include 'funcoes.php';



if (strlen($_POST['os']) > 0){
	$os = trim($_POST['os']);
}

if (strlen($_GET['os']) > 0){
	$os = trim($_GET['os']);
}

if (strlen($_POST['os_off']) > 0){
	$os_off = trim($_POST['os_off']);
}

if (strlen($_GET['os_off']) > 0){
	$os_off = trim($_GET['os_off']);
}

if (strlen($_POST['nota_fiscal']) > 0){
	$nota_fiscal = trim($_POST['nota_fiscal']);
}

if (strlen($_GET['nota_fiscal']) > 0){
	$nota_fiscal = trim($_GET['nota_fiscal']);
}




/*======= <PHP> FUN?OES DOS BOT?ES DE A??O =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "continuar") {
	$msg_erro = "";

	if (strlen (trim ($nota_fiscal)) == 0) {
		$msg_erro .= " Digite o número da nota fiscal.";
	}


	if (strlen (trim ($os_off)) == 0) {
		$msg_erro .= " Digite o número da OS off-line.";
	}
	

	$res = pg_exec ($con,"BEGIN TRANSACTION");


	if (strlen ($msg_erro) == 0 ) {

	/*================ ALTERA OS =========================*/

	$sql = "UPDATE tbl_os SET
				sua_os_offline = $os_off,
				nota_fiscal = $nota_fiscal
			WHERE tbl_os.os = $os
			AND   tbl_os.fabrica = $login_fabrica ";

			$res = @pg_exec ($con,$sql);

						


	}
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

/*================ LE OS DA BASE DE DADOS =========================*/

if (strlen ($os) > 0) {

	$sql = "SELECT  tbl_os.os                                           ,
					tbl_os.posto                                                ,
					tbl_posto.nome                             AS posto_nome    ,
					tbl_os.sua_os                                               ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
					tbl_os.hora_abertura                                        ,
					tbl_os.produto                                              ,
					tbl_produto.referencia                                      ,
					tbl_produto.descricao                  AS produto_descricao ,
					tbl_os.serie                                                ,
					tbl_os.sua_os_offline                                           ,
					tbl_os.revenda                                              ,
					tbl_os.revenda_cnpj                                         ,
					tbl_os.revenda_nome                                         ,
					tbl_os.nota_fiscal                                          ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
					tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
					tbl_os.os_posto                                             ,
					tbl_os.nota_fiscal_saida                                    ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') as data_nf_saida
			FROM	tbl_os
			LEFT JOIN	tbl_produto          ON tbl_produto.produto       = tbl_os.produto
			JOIN	tbl_posto            ON tbl_posto.posto           = tbl_os.posto
			JOIN	tbl_fabrica            ON tbl_fabrica.fabrica           = tbl_os.fabrica
			JOIN	tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
										AND tbl_fabrica.fabrica       = $login_fabrica
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$os                              = pg_result ($res,0,os);
		$posto                           = pg_result ($res,0,posto);
		$posto_nome                      = pg_result ($res,0,posto_nome);
		$sua_os                          = pg_result ($res,0,sua_os);
		$data_abertura                   = pg_result ($res,0,data_abertura);
		$hora_abertura                   = pg_result ($res,0,hora_abertura);
		$produto_referencia              = pg_result ($res,0,referencia);
		$produto_descricao               = pg_result ($res,0,produto_descricao);
		$produto_serie                   = pg_result ($res,0,serie);
		$revenda						 = pg_result ($res,0,revenda);
		$revenda_cnpj					 = pg_result ($res,0,revenda_cnpj);
		$revenda_nome					 = pg_result ($res,0,revenda_nome);
		$nota_fiscal					 = pg_result ($res,0,nota_fiscal);
		$data_nf						 = pg_result ($res,0,data_nf);
		$posto_codigo				     = pg_result ($res,0,posto_codigo);
		$os_posto						 = pg_result ($res,0,os_posto);
		$nota_fiscal_saida				 = pg_result ($res,0,nota_fiscal_saida);
		$data_nf_saida				  	 = pg_result ($res,0,data_nf_saida);
		$os_off				  			 = pg_result ($res,0,sua_os_offline);

	}
}




$body_onload = "javascript: document.frm_os.sua_os.focus()";
$title = "Alteração de Ordem de Serviço - ADMIN";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'callcenter';
include "cabecalho.php";
?>

<!-- ============= <HTML> COME?A FORMATA??O ===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	//if ($ip=="201.43.201.204") echo "teste";

	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6, strlen($msg_erro)-6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;
?>
	</td>
</tr>
</table>

<? 
echo $msg_debug ;
?>

<?
$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res = pg_exec ($con,$sql);
$hoje = pg_result ($res,0,0);
?>
<style>
.Conteudo{
	font-family: Verdana;
	font-size: 10px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

fieldset.valores , fieldset.valores div{
	padding: 0.2em;
	font-size:10px;
	width:225px;
}

fieldset.valores label {
	float:left;
	width:43%;
	margin-right:0.2em;
	padding-top:0.2em;
	text-align:right;
}

fieldset.valores span {
	font-size:11px;
	font-weight:bold;
}

table.bordasimples {border-collapse: collapse;}

table.bordasimples tr td {border:1px solid #000000;}
</style>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<tr><td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="left">


		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="os" value="<? echo $os ?>">
		<p>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do Posto
				<br>
				<b><? echo $posto_codigo ?></b>&nbsp;</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Posto
				<br>
				<b><? echo $posto_nome ?></b></font>
			</td>
		</tr>
		</table>

		<hr>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign="top">
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
				<br>
				<b><? echo $sua_os ?></b></font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Off-Line</font>
				<br>
				<input class="frm" type="text" name="os_off" id="os_off" size="20"  maxlength="20" value="<? echo $os_off ?>">
				</font><br>
			</td>
		</tr>
		</table>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign="top">
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
				<br>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $data_abertura ?></b></font><br>
			</td>
			<td nowrap>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>
				<br>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_referencia ?></b>
				</font>
			</td>
			<td nowrap>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>
				<br>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'><b><? echo $produto_descricao ?></b></font>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					N. Série.
				</font>
				<br>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
			</td>
		</tr>
		</table>
		<p>

		<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
		<tr class="top">
			<td  class="top">Informações sobre a Revenda</td>
		</tr>
		</table>

		<hr>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign="top">
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda
				<br>
				<b><? echo $revenda_nome ?></b></font>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda
				<br>
				<b><? echo $revenda_cnpj ?></b></font>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
				<br>
				<input class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="15" maxlength="20" value="<? echo $nota_fiscal ?>"></font>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
				<font face='arial' size='1'><br><b><? echo $data_nf ?></b><br></font>
			</td>
		</tr>
		
		</table>

		<p>
			
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
<?

if (strlen ($os) > 0) {

		echo "<img src='imagens/btn_alterarcinza.gif' style='cursor:pointer' ";
		/* HD: 47695 */
		echo " onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ;  document.frm_os.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }\" ";
		
		echo " ALT='Alterar os itens da Ordem de Serviço' ";

		echo "border='0'>";

}
?>
	</td>
</tr>
</table>


</form>


<? include "rodape.php";?>
