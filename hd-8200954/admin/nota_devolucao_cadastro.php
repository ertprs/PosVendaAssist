<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';

/* PASSA PARÂMETRO PARA O CABEÇALHO */

/* Aparece no sub-menu e no título do Browser */
$title = "Cadastro de Devolução de Peças Obrigatória - ADMIN";

/* Determina a aba em destaque do MENU */
$layout_menu = 'callcenter';

include "cabecalho.php";

$qtde_item = 50;

$btn_acao = $_POST["btn_acao"];

if ($btn_acao == "gravar") {
	$posto_codigo	= trim($_POST["posto_codigo"]);
	$nota_fiscal	= trim($_POST["nota_fiscal"]);
	$data_nf_pg		= fnc_formata_data_pg (trim($_POST["data_nf"]));
	$data_nf		= str_replace ("'", "", $data_nf_pg);
	$data_nf		= substr($data_nf,8,9)."/".substr($data_nf,5,2)."/".substr($data_nf,0,4);
//	$				= $_POST[""];

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$peca[$i]				= trim($_POST['peca_'.$i]);
		$peca_descricao[$i]		= trim($_POST['peca_descricao_'.$i]);
		$peca_qtde[$i]			= trim($_POST['peca_qtde_'.$i]);
		if (strlen($peca[$i]) > 0 AND strlen($peca_descricao[$i]) > 0 AND strlen($peca_qtde[$i]) > 0) {
			$sql =	"INSERT INTO tbl_ (
						
						) VALUES (
						$posto_codigo,
						$nota_fiscal,
						$data_nf_pg,
						$peca[$i],
						'$peca_descricao[$i]',
						$peca_qtde[$i]);";
//			echo $sql."<br><br>";
		}
	}
}

?>

<? include "javascript_pesquisas.php" ?>

<br>

<?
if (strlen ($msg_erro) > 0) {
?>
<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
	<tr>
		<td height="27" valign="middle" align="center">
			<font face="Arial, Helvetica, sans-serif" color="#FF3333"><b><? echo $msg_erro ?></b></font>
		</td>
	</tr>
</table>
<?
}
?>

<table border="0" width="90%" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td align="left">

			<!-- ------------- Formulário ----------------- -->

			<form name="frm_devolucao" method="post" action="<? echo $PHP_SELF ?>">
			<input type='hidden' name='btn_acao' value=''>
			<table width="100%" border="0" cellspacing="5" cellpadding="0">
				<tr valign="top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do Posto</font>
						<br>
						<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o Código do Posto. Clique na lupa para efetuar a pesquisa.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_devolucao.posto_codigo,document.frm_devolucao.posto_nome,'codigo')">
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Posto</font>
						<br>
						<input class="frm" type="text" name="posto_nome" size="45" value="<? echo $posto_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o Nome do Posto. Clique na lupa para efetuar a pesquisa.');">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_devolucao.posto_codigo,document.frm_devolucao.posto_nome,'nome')" style="cursor:pointer;">
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
						<br>
						<input class="frm" type="text" name="nota_fiscal" size="8" maxlength="8" value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');">
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
						<br>
						<input class="frm" type="text" name="data_nf" size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Compra. Verifique se o produto está dentro do Prazo de Garantia.');">
						<br>
						<font face='arial' size='1'>Ex.: 25/10/2004</font>
					</td>
				</tr>
			</table>

			<hr>

<?
for ($i = 0 ; $i < $qtde_item ; $i++) {
	if ($i % 17 == 0) {
		if ($i <> 0 ) {
			echo "			</table>\n";
			echo "			<br>\n<center><img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_devolucao.btn_acao.value == '' ) { document.frm_devolucao.btn_acao.value='gravar' ; document.frm_devolucao.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'></center>\n<br>\n";
		}
		echo "			<table width='100%' border='0' cellspacing='5' cellpadding='0'>\n";
		echo "				<tr height='20' bgcolor='#666666'>\n";
		echo "					<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'><b>Código</b></font></td>\n";
		echo "					<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'><b>Descrição</b></font></td>\n";
		echo "					<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'><b>Qtde</b></font></td>\n";
		echo "				</tr>\n";
	}

	echo "				<tr>\n";
	echo "					<td><input type='text' class='frm' name='peca_$i' size='15' value='$peca[$i]'>&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_peca (document.frm_devolucao.peca_$i , document.frm_devolucao.peca_descricao_$i , 'referencia')\" alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
	echo "					<td><input type='text' class='frm' name='peca_descricao_$i' size='60' value='$peca_descricao[$i]'>&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_peca (document.frm_devolucao.peca_$i , document.frm_devolucao.peca_descricao_$i , 'descricao')\" alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
	echo "					<td><input type='text' class='frm' name='peca_qtde_$i' size='10' value='$peca_qtde[$i]'></td>\n";
	echo "				</tr>\n";
}
?>
			</table>

			<br>

			<center><img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_devolucao.btn_acao.value=='' ) { document.frm_devolucao.btn_acao.value='gravar'; document.frm_devolucao.submit() } else { alert('Aguarde submissão') }" ALT='Gravar' border='0' style='cursor:pointer;'></center>

			</form>

		</td>
	</tr>
</table>

<br>

<? include 'rodape.php'; ?>