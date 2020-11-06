<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';





###CARREGA REGISTRO
$peca = $_GET ['peca'];

if (strlen($peca) > 0) {
	$sql = "SELECT tbl_peca.peca                   ,
					tbl_peca.referencia            ,
					tbl_peca.descricao             ,
					tbl_peca.ipi                   ,
					tbl_peca.garantia_diferenciada ,
					tbl_peca.devolucao_obrigatoria ,
					tbl_peca.item_aparencia        ,
					tbl_peca.bloqueada_garantia    ,
					tbl_peca.acessorio             ,
					tbl_peca.aguarda_inspecao      ,
					tbl_peca.peca_critica          ,
					tbl_peca.produto_acabado
			FROM    tbl_peca
			WHERE   tbl_peca.peca = $peca
			AND     fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$peca                     = trim(pg_result($res,0,peca));
		$referencia               = trim(pg_result($res,0,referencia));
		$descricao                = trim(pg_result($res,0,descricao));
		$ipi                      = trim(pg_result($res,0,ipi));
		$garantia_diferenciada    = trim(pg_result($res,0,garantia_diferenciada));
		$devolucao_obrigatoria    = trim(pg_result($res,0,devolucao_obrigatoria));
		$item_aparencia           = trim(pg_result($res,0,item_aparencia));
		$bloqueada_garantia       = trim(pg_result($res,0,bloqueada_garantia));
		$acessorio                = trim(pg_result($res,0,acessorio));
		$aguarda_inspecao         = trim(pg_result($res,0,aguarda_inspecao));
		$peca_critica             = trim(pg_result($res,0,peca_critica));
		$produto_acabado          = trim(pg_result($res,0,produto_acabado));
	}
}


$layout_menu = "cadastro";
$title = "Dados Cadastrais da Peça";
include 'cabecalho.php';

?>

<script language="JavaScript">

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
}
</script>



<script language="JavaScript">

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
}
</script>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}

</style>
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
	color: #485989;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}
</style>

<body>

<div id="wrapper">
<form name="frm_peca" method="post" action="<? $PHP_SELF ?>" enctype='multipart/form-data'>
<input type="hidden" name="peca" value="<? echo $peca ?>">

<!-- formatando as mensagens de erro -->
<?
if (strlen($msg_erro) > 0) {
	if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = "Foi detectado o seguinte erro:<br>";
			$msg_erro = substr($msg_erro, 6);
		}

		// retira CONTEXT:
		if (strpos($msg_erro,"CONTEXT:")) {
			$x = explode('CONTEXT:',$msg_erro);
			$msg_erro = $x[0];
		}

?>

<div class='error'>
	<? echo $msg_erro; ?>
</div>

<? } ?>

<table width='500' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
<tr>
		<td bgcolor='#D9E2EF'><b>Referência</b> (*)</td>
		<td bgcolor='#D9E2EF'><b>Descrição</b> (*)</td>
</tr>
<tr>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
		<td bgcolor='#FfFfFF'><input class='frm' size='50' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
</tr>
<tr>
	<td colspan='2'><INPUT TYPE="submit" name='btn_busca' value='Buscar'></td>
</tr>
</table>
</form>

<?
if (strlen($_POST["btn_busca"]) > 0) {
	$btnacao = trim($_POST["btn_busca"]);
}

if( $btnacao == 'Buscar'){ ?>
	<br>
	<? $sql = "SELECT peca FROM tbl_peca_fora_linha
				WHERE	peca = '$peca'
				AND		fabrica = $login_fabrica"; 
	$res = pg_exec($con,$sql);
		
	if (pg_numrows($res) > 0){
	
	?>
	<table width='500' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#FF0000'>
	<tr  style='font-size:14px'>
		<td><B><FONT COLOR="#FFFFFF">Peça fora de linha</FONT></B></td>
	</tr>
	</table><br>
	<? } ?>
	
	<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
	<tr bgcolor='#D9E2EF' style='font-size:10px'>
		<td align='center' height='25' style='font-size: 14px;' colspan='8'><B>Informações sobre a peça</B></td>
	</tr>
	</table>
	
	<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
	<tr  bgcolor='#D9E2EF' style='font-size:10px'>
		<td align='center' colspan='2'><b>Garantia Diferenciada (meses)</b></td>
		<td align='center'><b>Devolução Obrigatória</b></td>
		<td align='center'><b>Item de Aparência</b></td>
		<td align='center'><b>Bloqueada para Garantia</b></td>
	</tr>
	<tr  bgcolor='#FFFFFF'>
		<td align='center' colspan='2'>
			<? echo $garantia_diferenciada ?>
		</td>

		<td align='center'>
			<? if ($devolucao_obrigatoria == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
		</td>

		<td align='center'>
			<? if ($item_aparencia == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
		</td>

		<td align='center'>
			<? if ($bloqueada_garantia == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
		</td>
	</tr>
	<tr bgcolor='#D9E2EF' style='font-size:10px'>
		<td align='center'><b>IPI</b> (*)</td>
		<td align='center'><b>Acessório</b></td>
		<td align='center'><b>Aguarda Inspeção</b></td>
		<td align='center'><b>Peça Crítica</b></td>
		<td align='center'><b>Produto Acabado</b></td>
	</tr>
	<tr  bgcolor='#FFFFFF'>
	
		<td align='center'>
			<? echo "$ipi"; ?>
		</td>
		
		<td align='center'>
			<? if ($acessorio == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
		</td>

		<td align='center'>
			<? if ($aguarda_inspecao == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
		</td>

		<td align='center'>
			<? if ($peca_critica == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
		</td>

		<td align='center'>
			<? if ($produto_acabado == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
		</td>
	</tr>
	</table>
	<?

	$peca = $_POST ['peca'];

	If($peca > 0){

		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao FROM tbl_peca
				WHERE tbl_peca.fabrica = $login_fabrica
				AND tbl_peca.peca = '$peca' ";
	
		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$referencia_de   = trim(pg_result($res,0,referencia));
			$descricao_de    = trim(pg_result($res,0,descricao));
		}
	
		$sql = "SELECT  tbl_depara.depara,
						tbl_depara.de    ,
						tbl_depara.para  
			FROM    tbl_depara
			WHERE   tbl_depara.fabrica = $login_fabrica 
			AND     tbl_depara.de = '$referencia_de'
			ORDER BY tbl_depara.de ";
		
		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) > 0){
			$referencia_para = trim(pg_result($res,0,para));
			
			$sql2 = "SELECT tbl_peca.descricao FROM tbl_peca
				WHERE tbl_peca.fabrica = $login_fabrica
				AND tbl_peca.referencia = '$referencia_para' ";
			$res2 = pg_exec($con,$sql2);
		
			if (pg_numrows($res2) > 0) {
				$descricao_para    = trim(pg_result($res2,0,descricao));
			}
		}

		if (pg_numrows($res) > 0){
			echo "<br><br>";
			echo "<TABLE width='500' align='center' border='0' cellspacing='1' cellpadding='2'>\n";
			echo "<TR class='menu_top' bgcolor='#D9E2EF' style='font-size: 12px'>\n";
			echo "	<TD COLSPAN='2'>Peça Substituida por</TD>\n";
			echo "</TR>\n";

			echo "<TR class='menu_top'>\n";
			echo "<TD>Ref.</TD>\n";
			echo "<TD>Descrição</TD>\n";
			echo "</TR>\n";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
				$referencia_de   = trim(pg_result($res,$i,de));
				$referencia_para = trim(pg_result($res,$i,para));

				$cor = "#F7F5F0"; 
				if ($i % 2 == 0) 
					$cor = '#F1F4FA';

				echo "<TR class='table_line' style='background-color: $cor;'>\n";
				echo "<TD align='center' nowrap>$referencia_para</TD>\n";
				echo "<TD align='center'>$descricao_para</TD>\n";
				echo "</TR>\n";
			}echo "</TABLE>";
		}

		
		$sql = "SELECT  tbl_tabela_item.tabela_item,
						tbl_tabela.tabela          ,
						tbl_tabela.sigla_tabela    ,
						tbl_tabela_item.preco      ,
						tbl_peca.referencia        ,
						tbl_peca.descricao
				FROM    tbl_tabela
				JOIN    tbl_tabela_item USING (tabela)
				JOIN    tbl_peca        ON tbl_peca.peca = tbl_tabela_item.peca
				WHERE   tbl_tabela_item.peca = $peca
				AND     tbl_tabela.fabrica   = $login_fabrica;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<br><br><TABLE width='500' align='center' border='0' cellspacing='1' cellpadding='2'>\n";
			echo "<TR class='menu_top' bgcolor='#D9E2EF'>\n";
			echo "	<TD style='font-size: 12px' colspan='4' height='20'> Tabela(s) e preço(s) da peça </TD>\n";
			echo "</TR>";
			echo "<TR class='menu_top' bgcolor='#D9E2EF'>\n";
			echo "	<TD> Tabela</TD>";
			echo "	<TD> Preço </TD>";
			echo "	<TD> Tabela</TD>";
			echo "	<TD> Preço </TD>";
			echo "</TR>";



			for ($y = 0 ; $y < pg_numrows($res) ; $y++){
//				$tabela_item     = trim(pg_result($res,$y,tabela_item));
//				$tabela          = trim(pg_result($res,$y,tabela));
				$sigla           = trim(pg_result($res,$y,sigla_tabela));
				$preco           = trim(pg_result($res,$y,preco));
//				$peca_referencia = trim(pg_result($res,$y,referencia));
//				$peca_descricao  = trim(pg_result($res,$y,descricao));
				
				if ($y % 2 == 0) 
				{
					echo "<TR class='table_line'>";
					echo "	<TD align='center'><B>$sigla</B></TD>";
					echo "	<TD align='center'><B>R$ ". number_format($preco,2,",",".");
					echo "	</B>";
				}else{	
					$sigla           = trim(pg_result($res,$y,sigla_tabela));
					$preco           = trim(pg_result($res,$y,preco));
					echo "	<TD align='center'><B>$sigla</B></TD>";
					echo "	<TD align='center'><B>R$ ". number_format($preco,2,",",".");
					echo "	</B>";
				}
				echo "</TR>";
			}echo "</TABLE>";
		}

		$sql = "SELECT tbl_lista_basica.produto, tbl_produto.descricao
						FROM tbl_lista_basica
						JOIN tbl_produto USING (produto)
						WHERE tbl_lista_basica.fabrica = $login_fabrica
						AND   tbl_lista_basica.peca = '$peca' LIMIT 2;";

		$res = pg_exec ($con,$sql);
		
		if(pg_numrows($res) > 0)
		{
			echo "<br><br><TABLE width='500' align='center' border='0' cellspacing='1' cellpadding='2'>";
			echo "<tr class='menu_top' bgcolor='#D9E2EF'>";
			echo "	<td colspan='2' style='font-size: 12px'>Produto(s) que contém a peca</td>";
			echo "</tr>";
			echo "<tr class='menu_top' bgcolor='#D9E2EF'>";
			echo "	<td>Referência</td>";
			echo "	<td>Descrição</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++){
				$produto           = trim(pg_result($res,$i,produto));
				$descricao         = trim(pg_result($res,$i,descricao));
				echo "<tr>";
				echo "	<td>$produto<br>";
				echo "	<td>$descricao<br>";
				echo "</tr>";
			}echo "</table>";
		}
	}
}

echo "<BR><BR>";

include "rodape.php"; 

?>
