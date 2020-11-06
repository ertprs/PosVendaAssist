<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../autentica_usuario.php';
$cor ="#485989";
$cor2="#9BC4FF";

$link_os          = "../menu_os.php"                                 ;
$link_pedido      = "../menu_pedido.php"                            ;
$link_extrato     = "../os_extrato.php"                             ;
$link_cadastro    = "../menu_cadastro.php"                          ;
$link_preco       = "../tabela_precos.php"                          ;
$link_vista       = "vistas.php"                                 ;
$link_informativo = "../comunicado_mostra.php?tipo=Informativo"     ;
$link_comunicado  = "../comunicado_mostra.php?tipo=Comunicado"      ;
$link_forum       = "../forum.php"                                  ;
$link_pesquisa    = "../menu_os.php"                                ;
$link_requisitos  = "javascript:;' onclick=\"window.open('http://www.telecontrol.com.br/assist/configuracao.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')\""                                ;
$link_sair        = "http://www.telecontrol.com.br/assist/"      ;


//$logo = 'lorenzetti.gif';
if($login_fabrica==3)  $logo = 'britania.gif';
if($login_fabrica==19) $logo = 'lorenzetti.gif';

?>
<script language="JavaScript1.2">
function high(which2){
theobject=which2
highlighting=setInterval("highlightit(theobject)",50)
}
function low(which2){
clearInterval(highlighting)
which2.filters.alpha.opacity=40
}
function highlightit(cur2){
if (cur2.filters.alpha.opacity<100)
cur2.filters.alpha.opacity+=5
else if (window.highlighting)
clearInterval(highlighting)
}
</script>
<style>
body {
	text-align: center;
	font-family:Arial;
	margin: 0px,0px,0px,0px;
	padding:  0px,0px,0px,0px;
}

a.conteudo{
	color: #FFFFFF;
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}
a.conteudo:visited {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

a.conteudo:hover {
	color: #FFFFCC;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

a.conteudo:active {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
}

.Tabela{
	border:1px solid #d2e4fc;
/*	background-color:<?=$cor;?>;*/
}
.rodape{
	color: #FFFFFF;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 9px;
	background-color: #FF9900;
	font-weight: bold;
}
img{
border:0px;
}
.mensagem {
	color: #0099FF;
	font-size: 13px;
	font-weight: bold;
}
.fundo {
background-position: bottom left ;
background-image: url('../logos/telecontrol2.jpg') ;
background-repeat: no-repeat ;
width: 152px ;
height: 80px ;}


</style>

<body><center>
<table width='100%' height='100%' border='0' cellpadding='0' cellspacing='0'>
	<tr>
		<td align='center' valign='top'>
<?		
echo "<TABLE width='750px' border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td><a href='$PHP_SELF?menu=os'><img src='../imagens/aba/os";if ($layout_menu == "os"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
		echo "<td><a href='$PHP_SELF?menu=produtos'><img src='../imagens/aba/produtos";if ($layout_menu == "produtos"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
		echo "<td><a href='$PHP_SELF?menu=lancamentos'><img src='../imagens/aba/info_tecnico";if ($layout_menu == "lancamentos"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
		echo "<td><a href='$PHP_SELF?menu=lancamentos'><img src='../imagens/aba/lancamentos";if ($layout_menu == "lancamentos"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
		echo "<td><a href='$PHP_SELF?menu=lancamentos'><img src='../imagens/aba/lancamentos";if ($layout_menu == "lancamentos"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
		echo "<td><a href='$PHP_SELF?menu=promocoes'><img src='../imagens/aba/promocoes";if ($layout_menu == "promocoes"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
		echo "<td><a href='$PHP_SELF?menu=outros'><img src='../imagens/aba/outros";if ($layout_menu == "outros"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
		echo "<td><img src='../imagens/aba/sair.gif' border='0'></td>";
		echo "</tr>";
echo "</table>";
echo "<TABLE width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr>";
echo "<td background='http://www.telecontrol.com.br/assist/imagens/submenu_fundo_cinza.gif' colspan='8'>&nbsp;</td>";
echo "</tr>";
echo "</table>";

?>

<table width="745" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
	<tr><td colspan='4'>
		<table width="745" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
			<tr align='center'>
					<td  class='mensagem' width='180' align='center'><div id="container"><h2><IMG SRC="../logos/<?=$logo?>"; ALT="Bem-Vindo!!!"></h2></div></td>
					<td class='mensagem'>Mensagem que você deseja para o posto</td>
					<td width='180' align='center'><IMG SRC="../logos/telecontrol2.jpg" ALT="Bem-Vindo!!!"></td>
			</tr>

			</table>
	</td></tr>
	<tr>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_os;?>' class='conteudo'><img src="imagem/os.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_os;?>' class='conteudo'>ORDEM DE SERVIÇO</a></td>
				</tr>
			</table>
		</td>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_pedido;?>' class='conteudo'><img src="imagem/pedido.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_pedido;?>' class='conteudo'>PEDIDO</a></td>
				</tr>
			</table>
		</td>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_extrato;?>' class='conteudo'><img src="imagem/extrato.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_extrato;?>' class='conteudo'>EXTRATO</a></td>
				</tr>
			</table>
		</td>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_cadastro;?>' class='conteudo'><img src="imagem/cadastro.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_cadastro;?>' class='conteudo'>CADASTRO</a></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_preco;?>' class='conteudo'><img src="imagem/preco.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_preco;?>' class='conteudo'>TABELA DE PREÇO</a></td>
				</tr>
			</table>
		</td>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_vista;?>' class='conteudo'><img src="imagem/vista.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_vista;?>' class='conteudo'>VISTA EXPLODIDA</a></td>
				</tr>
			</table>
		</td>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_informativo;?>' class='conteudo'><img src="imagem/informativo.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_informativo;?>' class='conteudo'>INFORMATIVO TÉCNICO</a></td>
				</tr>
			</table>
		</td>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_comunicado;?>' class='conteudo'><img src="imagem/comunicado.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_comunicado;?>' class='conteudo'>COMUNICADO ADMINISTRATIVO</a></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_forum;?>' class='conteudo'><img src="imagem/forum.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_forum;?>' class='conteudo'>FORUM</a></td>
				</tr>
			</table>
		</td>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_pesquisa;?>' class='conteudo'><img src="imagem/pesquisa.gif" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_pesquisa;?>' class='conteudo'>PESQUISA DE SATISFAÇÃO</a></td>
				</tr>
			</table>
		</td>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_requisitos;?>' class='conteudo'><img src="imagem/requisitos.jpg" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_requisitos;?>' class='conteudo'>REQUISITO DO SISTEMA</a></td>
				</tr>
			</table>
		</td>
		<td width='185'><br>
			<table width="185" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
				<tr align='center'>
					<td width='185'><a href='<?=$link_sair;?>' class='conteudo'><img src="imagem/sair.gif" style="filter:alpha(opacity=40)" onMouseover="high(this)" onMouseout="low(this)"></a></td>
				</tr>
				<tr bgcolor='<?=$cor;?>' align='center' onmouseover="this.bgColor='<?=$cor2;?>'" onmouseout="this.bgColor='<?=$cor?>'" >
					<td width='185'><a href='<?=$link_sair;?>' class='conteudo'>SAIR</a></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td colspan='4'><br></td></tr>
</table>
		</td>
	</tr>
	<tr>
		<td height='5'class='rodape'><b>&nbsp;&nbsp;Telecontrol Networking Ltda - <? echo date("Y"); ?> - www.telecontrol.com.br - Deus é o Provedor</b></td>
	</tr>
</table>
</body>
</html>
