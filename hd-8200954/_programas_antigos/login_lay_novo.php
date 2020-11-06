<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//include 'autentica_usuario.php';
include 'cabecalho_login_lay_novo.php';
?>

<style type="text/css">
input {
	BORDER-RIGHT: #000000 1px solid; 
	BORDER-TOP: #000000 1px solid; 
	FONT-WEIGHT: bold; 
	FONT-SIZE: 8pt; 
	BORDER-LEFT: #000000 1px solid; 
	BORDER-BOTTOM: #000000 1px solid; 
	FONT-FAMILY: Verdana; 
	BACKGROUND-COLOR: #FFFFFF
}
a:link {
	color: #000000;

	text-decoration: none;
}

a:visited {
	color: #000000;

	text-decoration: none;
}

a:hover {
	color: #000000;
	text-decoration: none;
}

a:active {
	color: #000000;
	font-weight: bold;
	text-decoration: none;
}
</style>
<?
if (strlen (btn_buscar) > 0) {

if($_POST['tipo_busca'])          { $tipo_busca      = trim ($_POST['tipo_busca']);}
if($_POST['busca'])          { $busca      = trim ($_POST['busca']);}
if($tipo_busca=='os'){

}
if($tipo_busca=='pedido'){}
if($tipo_busca=='nf'){}
if($tipo_busca=='comunicado'){}
if($tipo_busca=='preco'){}

}


?>



<?
echo "<table width='680' border='0' align='center' cellpadding='4' cellspacing='4' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td width='160' align='center'> ";
//--logo fabrica -->
echo "<IMG SRC='admin/imagens_admin/britania.jpg' ALT='Bem Vindo!!!' width='150' height='49'><BR>Bem-vindo posto<BR> <B>######## ### #### ##</b> </td>";
echo "<td width='520'  valign='bottom'>";


//-- ########BUSCA TELECONTROL########## -->
echo "	<FORM METHOD='POST' ACTION='$PHP_SELF'>";
	echo "<table width='500' border='0' cellpadding='0' align='right' cellspacing='1' bgcolor='#666666'>";
	echo "<tr>";
	echo "<td>";
		echo "<table width='510' height='60' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 11px; background-color: #dfdfdf'>";
		echo "<tr bgcolor='#D2D2D2'>";
		echo "<td height='25' colspan='2' background='admin/imagens_admin/cinza.gif' align='right'>";
		
		
		echo "<input type='radio' name='tipo_busca' value='os'>O.S | <input type='radio' name='tipo_busca' value='pedido'>Pedido | <input type='radio' name='tipo_busca' value='nf'>Nota Fiscal | <input type='radio' name='tipo_busca' value='comunicado'>Comunicado | <input type='radio' name='tipo_busca' value='preco'>Preço de Peça&nbsp;&nbsp;&nbsp;";
		
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td width='200' height='35' align='center' valign='middle'> ";
		echo "<img src='admin/imagens_admin/btn_lupa.gif' width='20' height='18'>Busca Telecontrol </td>";
		echo "<td width='300' align='right' valign='middle' > Número: &nbsp;"; 
		echo "<input type='text' size='20' maxlength='20' name='busca' value=''> <input type='submit' name='btn_buscar' value='Buscar'>&nbsp;&nbsp;&nbsp;";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";
//-- ########BUSCA TELECONTROL FIM########## -->

echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td width='160' valign='top'>";
//--######## ESQUERDO ########## -->
/*
 	echo "<table width='160' border='0' align='center' cellpadding='5' cellspacing='1' style='font-family: verdana; font-size: 11px; background-color: #71AFBA'>";
	echo "<tr>";
	echo "<td width='160' height='25' background='admin/imagens_admin/agua.gif'><font size='2' color='#FFFFFF'><B>Produtos</b></font>";
	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td bgcolor='#ffffff' align='center'><B>Televisão 20</b><BR>
	Tv Sony, tela plana, stéreo. R$2000,00<BR>
	<hr width='95%' size='1'><B>Televisão 20</b><BR>
	Tv Sony, tela plana, stéreo. R$2000,00<BR>
	<hr width='95%' size='1'><B>Televisão 20</b><BR>
	Tv Sony, tela plana, stéreo. R$2000,00<BR>
	<hr width='95%' size='1'><B>Televisão 20</b><BR>
	Tv Sony, tela plana, stéreo. R$2000,00<BR><BR>";
	echo "</td>";
	echo "</tr>";
	
	echo "</table>";
 	echo "<BR>";
 */
 
	echo "<table width='160' border='0' align='center' cellpadding='5' cellspacing='1' style='font-family: verdana; font-size: 11px; background-color: #71AFBA'>";
	echo "<tr>";
	echo "<td width='160' height='25' background='admin/imagens_admin/agua.gif'><font size='2' color='#FFFFFF'><B>Telecontrol</b></font>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor='#EBFCFF'>Aqui os Postos Autorizados Britania podem efetuar o lançamento de Ordens de Serviço em garantia, conferir seu extrato financeiro, visualizar e imprimir vistas explodidas, contatar a empresa através do Fale Conosco, ficar a par de lançamentos de produtos e promoções entre outros recursos de grande utilidade para agilizar todo o processo de controle de Ordens de Serviço.<BR><BR>A Telecontrol desenvolve sistemas totalmente destinados à Internet, com isto você tem acesso às informações de sua empresa de qualquer lugar, podendo tomar decisões gerenciais com total segurança.<br>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
echo "<BR>";

	echo "<table width='160' border='0' align='center' cellpadding='5' cellspacing='1' style='font-family: verdana; font-size: 12px; background-color: #CD4444'>";
	echo "<tr>";
	echo "<td width='150' height='25' background='admin/imagens_admin/vermelho.gif'><font color='#FFFFFF'><B>Acesso Rápido</B></font></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td height='160' align='center' valign='top' bgcolor='#F0CECE'><BR>";
		echo "<table width='155' border='0' align='center' cellpadding='0' cellspacing='1' bgcolor='#F0CECE' style='font-family: verdana; font-size: 10px' >";
		echo "<tr width='150'>";
		echo "<td width='75' valign='top' bgcolor='#F0CECE'> ";
		echo "<B>Cadastrar</B><BR>";
		echo "<a href='#'>Ordem Serviço</a><BR>";
		echo "<a href='#'>Pedidos de Peça</a><BR>";
		echo "<a href='#'>O.S. Revenda</a><BR>";
		echo "<a href='#'>Consumidor</a><BR>";
		echo "<a href='#'>Revenda</a><BR>";
		echo "<a href='#'>Status OS</a><BR> ";
		echo "</td>";
		echo "<td width='75' valign='top' bgcolor='#F0CECE'> ";
		echo "<B>Consultar</b><BR>";
		echo "<a href='#'>Ordem Serviço</a><BR>";
		echo "<a href='#'>Pedidos de Peça</a><BR>";
		echo "<a href='#'>Pendência de Peça</a><BR>";
		echo "<a href='#'>O.S. Revenda</a><BR>";
		echo "<a href='#'>Nota Fiscal</a><BR> ";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center' colspan='2'  bgcolor='#F0CECE'> ";
		echo "<B><br><BR><a href='#'>Fechar Ordem Serviço</a></B><BR><BR></td>";
		echo "</tr>";
	echo "</table>";
	echo "</td>";
	echo "</tr>";
	echo "</table><BR>"; 
echo "</td>";
echo "<td width='520' valign='top'>";
//--######## DIREITO ########## -->

	 
	 
echo "<table width='510' border='0' align='center' cellpadding='0' cellspacing='1'  style='font-family: verdana; font-size: 10px; background-color: #C6C7B1'>";
echo "<tr>";
echo "<td width='500' height='51' align='center' background='admin/imagens_admin/novidades.gif'>";
echo "<a href='$PHP_SELF?tipo=Boletim'>Boletim</a> | <a href='$PHP_SELF?tipo=Comunicado'>Comunicado</a> | <a href='$PHP_SELF?tipo=Descritivo técnico'>Descritivo técnico</a> | <a href='$PHP_SELF?tipo=Esquema Elétrico'>Esquema Elétrico</a> | <a href='$PHP_SELF?tipo=Foto'>Foto</a> | <a href='$PHP_SELF?tipo=informativo'>Informativo</a> <BR>  <a href='$PHP_SELF?tipo=Lançamentos'>Lançamentos</a> |  <a href='$PHP_SELF?tipo=Manual'>Manual</a> |  <a href='$PHP_SELF?tipo=Orientação de Serviço'>Orientação</a> |  <a href='$PHP_SELF?tipo=Procedimento'>Procedimento</a> | <a href='$PHP_SELF?tipo=Promocao'>Promoção</a> |  <a href='$PHP_SELF?tipo=Vista Explodida'>Vista Explodida</a>"; 

echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td bgcolor='#F0F0E4'>";
	echo "<table width='510' border='0' align='center' cellpadding='4' cellspacing='4' style='font-family: verdana; font-size: 10px; background-color: #F0F0E4'>";
	echo "<tr>";
	echo "<td colspan='3' align='center'>";
		echo "<font size='3'><B>Novo layout do Sistema Telecontrol</B><BR> Confira as novidades do Sistema</font> ";
		echo "</td>";
	echo "</tr>";
		  
	 
	if($_GET['tipo'])          { $tipo      = trim ($_GET['tipo']);}
	if($tipo=='Boletim'){}
	if($tipo=='Comunicado'){}
	if($tipo=='Descritivo técnico'){}
	if($tipo=='Esquema Elétrico'){}
	if($tipo=='Foto'){}
	if($tipo=='informativo'){}
	if($tipo=='Lançamentos'){}
	if($tipo=='Manual'){}
	if($tipo=='Orientação de Serviço'){}
	if($tipo=='Procedimento'){}
	if($tipo=='Promoção'){}
	if($tipo=='Vista Explodida'){}
	/*
	$sql = "SELECT	tbl_comunicado.comunicado, 
					tbl_comunicado.descricao , 
					tbl_comunicado.mensagem  , 
					tbl_comunicado.tipo      ,
					tbl_produto.produto      , 
					tbl_produto.referencia   , 
					tbl_produto.descricao AS descricao_produto        , 
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data 
			FROM	tbl_comunicado 
			LEFT JOIN tbl_produto USING (produto) 
			LEFT JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha 
			WHERE	tbl_comunicado.fabrica = $login_fabrica 
			AND		tbl_comunicado.tipo = '$tipo' 
			ORDER BY tbl_produto.referencia
			LIMIT 9";

	$res = pg_exec ($con,$sql);
	$comunicado_msg            = trim(pg_result($res,$i,mensagem));
	$comunicado_descricao      = trim(pg_result($res,$i,descricao));
	$comunicado_tipo           = trim(pg_result($res,$i,tipo));
	$comunicado_data           = trim(pg_result($res,$i,data));
	 */
	
	
	
	 /*
	$produto_referencia     = trim($_POST['produto_referencia']);
	$familia                = trim($_POST['familia']);
	$descricao              = trim($_POST['descricao']);
	$extensao               = trim($_POST['extensao']);
	$tipo                   = trim($_POST['tipo']);
	$mensagem               = trim($_POST['mensagem']);
	$obrigatorio_os_produto = trim($_POST['obrigatorio_os_produto']);
	$obrigatorio_site       = trim($_POST['obrigatorio_site']);
	$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
	$codigo_posto           = trim($_POST['codigo_posto']);
	$posto_nome             = trim($_POST['posto_nome']);
	$remetente_email        = trim($_POST['remetente_email']);
	  */

	
	
	
	
	echo "<tr>";
		echo "<td  width='166' valign='top'>
		<font size='1'><B>Boletim</B> 00/00/0000</font><BR>
		Batedeira com problemas<BR>
		BAT PEROLA BRANCA 220V BAU com problemas, por...<BR> 
		<hr width='98%' size='1'>
		<font size='1'><B>Boletim</B> 00/00/0000</font><BR>
		Batedeira com problemas<BR>
		BAT PEROLA BRANCA 220V BAU com problemas, por...<BR>
 		<hr width='98%' size='1'>
		<font size='1'><B>Boletim</B> 00/00/0000</font><BR>
		Batedeira com problemas<BR>
		BAT PEROLA BRANCA 220V BAU com problemas, por...<BR> ";
		echo "</td>";
		
		echo "<td width='166' valign='top'>
 		<font size='1'><B>Lançamento</B>00/00/0000</font><BR>
		Ar condicionado<BR>
		Verificar o lançamento do novo Ar Condicionado..<BR> 
		<hr width='98%' size='1'>
		<font size='1'><B>Lançamento</B> 00/00/0000</font><BR>
		TV tela plana<BR>
		Verificar o lançamento do novo TV..<BR> 
		<hr width='98%' size='1'>
		<font size='1'><B>Lançamento</B> 00/00/0000</font><BR>
		Aquecedor<BR>
		Verificar o lançamento do novo Aquecedor..<BR> ";
		echo "</td>";
		
		echo "<td width='166' valign='top'> 
		<font size='1'><B>Comunicado</B> 00/00/0000</font><BR>
		Ar condicionado<BR>
		Verificar o lançamento do novo Ar Condicionado..<BR> 
		<hr width='98%' size='1'>
		<font size='1'><B>Comunicado</B> 00/00/0000</font><BR>
		TV tela plana<BR>
		Verificar o lançamento do novo TV..<BR> 
		<hr width='98%' size='1'>
		<font size='1'><B>Comunicado</B> 00/00/0000</font><BR>
		Aquecedor<BR>
		Verificar o lançamento do novo Aquecedor..<BR> ";
		echo "</td>";
	echo "</tr>";
	echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "<br>";
echo "<table width='510' border='0' align='center' cellpadding='0' cellspacing='0'>";
echo "<tr valign='top'>";
echo "<td width='360' valign='top' style='font-family: verdana; font-size: 12px'>";
//----######## DIREITO ESQUERDO########## -->

	echo "<table  width='350' border='0' cellpadding='5' cellspacing='1'  valign='top' style='font-family: verdana; font-size: 12px; background-color: #728D5A' align='left'> ";
	echo "<tr>";
		echo "<td height='25' background='admin/imagens_admin/verde.gif'>";
		echo "<font color='#FFFFFF'><B>Fechamento de Extrato</B></font>";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td height='35' bgcolor='#DDEFC7'>Seu próximo extrato será
		fechado no dia 22/01/2006! Existem 99 OS abertas e 99 Fechadas
		do periodo de 00/01/2006 até 02/07/2006. <a href='#'>Clique
		aqui para fechar as OS abertas.</a>";
		echo "</td>";
	echo "</tr>";
	echo "</table>";



	
echo "</td>";
echo "<td rowspan='3' width='150' valign='top'> ";
//--######## DIREITO DIREITO - AJUDA########## -->
	echo "<table style='font-family: verdana; font-size: 10px; background-color: #EBE062' width='150' border='0' align='center' valign='top' cellpadding='5' cellspacing='1' >";
	echo "<tr>";
	echo "<td background='admin/imagens_admin/amarelo.gif'><font color='#FFFFFF'><font size='2'><B>Ajuda do Sistema</B></font>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center' bgcolor='#ffffff'> ";
		//-- ###MANUAIS#### -->
	echo "<font color='#FF0000'><b>Obtenha mais informações sobre o novo sistema</b></font>
	<BR><a href='pdf/sistema.pdf'>PDF</a> 
	<a href='pdf/sistema.doc'>DOC</a> 
	<a href='pdf/sistema.htm'>HTML</a>
	<BR><BR>
	<font color='#FF0000'><b>Consulte o manual feito especialmente para você!</b></font><BR>
	<a href='pdf/ajuda.pdf'>PDF</a>
	<a href='pdf/ajuda.doc'>DOC</a>
	<a href='pdf/ajuda.htm'>HTML</a>
	<BR><BR>
	<font color='#FF0000'><b>Para valorizar ainda mais o seu serviço, estamos aumentando o valor das taxas de mão-de-obra</b></font>
	<BR><a href='#doisreais'>saiba mais</a>
	<BR><BR>
	<font color='#FF0000'><b>Circular Manual do Sistema</b></font><BR>
	<a href='http://www.telecontrol.com.br/assist/pdf/sistema.pdf'>PDF</a>
	<a href='http://www.telecontrol.com.br/assist/pdf/sistema.doc'>DOC</a>
	<a href='http://www.telecontrol.com.br/assist/pdf/sistema.htm'>HTML</a>
	<BR><BR>
	<font color='#FF0000'><b>Manual Ajuda do Sistema</b><font size='1'></font><BR>
	<a href='http://www.telecontrol.com.br/assist/pdf/ajuda.pdf'>PDF</a> 
	<a href='http://www.telecontrol.com.br/assist/pdf/ajuda.doc'>DOC</a> 
	<a href='http://www.telecontrol.com.br/assist/pdf/ajuda.htm'>HTML</a> <BR>";
	echo "</td>";
	//-- ###MANUAIS#### -->
	echo "</tr>";
	echo "</table>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td  valign='top'>";

	echo "<table width='350' style='font-family: verdana; font-size: 12px; background-color: #DB6510' align='left' cellpadding='5' cellspacing='1' >";
	echo "<tr>";
		echo "<td height='25' background='admin/imagens_admin/laranja.gif'><font color='#FFFFFF'><B>Informações atualizadas</B></font>";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td bgcolor='#E4B998'>Mantenha as informações sobre seu posto atualizadas!! <a href='#'>Clique aqui</a> para adicionar ou alterar alguma informação.";
		echo "</td>";
	echo "</tr>";
	echo "</table>";

echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td  valign='top'>";


/*
echo "<table width='350' border='0' align='left' cellpadding='5' cellspacing='1' style='font-family: verdana; font-size: 12px; background-color: #CD4444'>";
	echo "<tr>";
	echo "<td width='350' height='25' background='admin/imagens_admin/vermelho.gif'><font color='#FFFFFF'><B>Acesso Rápido</B></font></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor='#F0CECE'>";
		echo "<table width='95%' border='0' align='center' cellpadding='5' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
		echo "<tr>";
		echo "<td width='50%' valign='top' bgcolor='#F0CECE'> ";
		echo "<B>Cadastrar</B><BR>";
		echo "<a href='#'>Ordem Serviço</a><BR>";
		echo "<a href='#'>Pedidos de Peça</a><BR>";
		echo "<a href='#'>O.S. Revenda</a><BR>";
		echo "<a href='#'>Consumidor</a><BR>";
		echo "<a href='#'>Revenda</a><BR>";
		echo "<a href='#'>Status OS</a><BR> ";
		echo "</td>";
		echo "<td width='50%' valign='top' bgcolor='#F0CECE'> ";
		echo "<B>Consultar</b><BR>";
		echo "<a href='#'>Ordem Serviço</a><BR>";
		echo "<a href='#'>Pedidos de Peça</a><BR>";
		echo "<a href='#'>Pendência de Peça</a><BR>";
		echo "<a href='#'>O.S. Revenda</a><BR>";
		echo "<a href='#'>Nota Fiscal</a><BR> ";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='2' align='center' colspan='2'  bgcolor='#F0CECE'> ";
		echo "<B><a href='#'>Fechar Ordem Serviço</a></B>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	echo "</td>";
	echo "</tr>";
	echo "</table>"; 
echo "<BR>";

 */



echo "<table width='350' style='font-family: verdana; font-size: 12px; background-color: #485989' border='0' align='left' cellpadding='5' cellspacing='1'>";
	echo "<tr>";
	echo "<td height='25' background='admin/imagens_admin/azul.gif'><font color='#FFFFFF'><B>E-mail atualizado</B></font>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td bgcolor='#DBE5F5'>Por favor confirme seu E-MAIL no campo abaixo.<br>
		Após receber um e-mail da Telecontrol, clique no link e confirme seu e-mail.<br>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;E-mail:&nbsp;&nbsp;
		<input type='text' size='20' maxlength='20' name='email' value=''>
		&nbsp;&nbsp;
		<input type='submit' name='btn_email' value='Confirmar'>";
	echo "</td>";
	echo "</tr>";
echo "</table>";

echo "</td>";
echo "</tr>";


echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";
include "rodape_lay_novo.php";
 

?>
