<?
include '/var/www/assist/www/dbconfig.php';
include '/var/www/assist/www/includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';



//$admin_privilegios = "call_center";
//include '/assist/www/admin/autentica_admin.php';


//include 'funcoes.php';
//include "/cabecalho.php";

//session_start();
/*

$nome_fornecedor=array("KNOPP & CIA. LTDA.","TECNOPLUS", "CASAS BAHIA");
$senha_fornecedor=array("123456","222222", "555555");
$nome_produto=array("IMPRESSORA LASER HP","PROCESSADOR 3.0-SEMPROM", "MINI MOUSE MULTILASER OPTICO-USB");

$cotacao=array("243","244","245");
$cotacao_abertura=array("10/11/2006","26/10/2006","19/10/2006");
$cotacao_fechamento=array("16/11/2006","30/10/2006","23/10/2006");
$cotacao_status=array("aberta","fechada","fechada");

$item_cotacao=array("1001","1002","1003");
$item_produto=array("0","1","2");
$qtd_produto=array("8","36","70");

*/
$nome_fornecedor=array("KNOPP & CIA. LTDA.","TECNOPLUS", "CASAS BAHIA");
$senha_fornecedor=array("123456","222222", "555555");
$nome_produto=array("IMPRESSORA LASER HP","PROCESSADOR 3.0-SEMPROM", "MINI MOUSE MULTILASER OPTICO-USB","DVD's Virgem", "Monitor 17 LCD - SANSUNG");

$cotacao=array("245","244","243","242");
$cotacao_abertura=array("18/11/2006","10/11/2006","26/10/2006","19/10/2006");
$cotacao_fechamento=array("23/11/2006","16/11/2006","30/10/2006","23/10/2006");
$cotacao_status=array("aberta","aberta","fechada","fechada");

//$item_cotacao=array("1001","1002","1003");
$item_produto=array("0","1","2");
$qtd_produto=array("8","36","70");



$item_cotacao = array ( 
					array('100','0','246','15'),
					array('99','0','245','8'),
                    array('98','1','245','12'),
                    array('97','2','245','36'),
                    array('96','3','245','90'),
                    array('95','4','245','4'),
					array('94','0','244','12'),
                    array('93','1','244','39'),
					array('92','2','244','45'));

?>

<!-- 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">



<head>

	<title>CONTROLE DE COTA��O</title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na m�o...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assist�ncia T�cnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assist�ncia T�cnica, Postos, Manuten��o, Internet, Webdesign, Or�amento, Comercial, J�ias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css.css">
    <script language="JavaScript" type="text/javascript" src="javascript/funcoes.js"></script>   
</head>

<body  bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' >

<center>
<style type="text/css">


.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}

input {
	font-family: Arial;
	font-size: 11px;
	text-indent: 2px;	
	font-size:9px; 
	font-weight: normal;
	color: #000000;
	font-weight: bold;
}
.radio{
	font-family: Tahoma, Arial, Verdana;
	font-size: 8px;
	text-indent: 1px;	
}
.checkbox {
	font-family: Tahoma, Arial, Verdana;
	font-size: 11px;
	text-indent: 2px;
	border: 0px; 	
}

.input_cotacao {
	font-family: arial;
	border: none;
	text-align:right ; 
	font-size:9px; 
	height:9px;	
	font-weight: normal;
	color: #000000;
	border: 0px;
	font-weight: bold;
}


</style>
<table class='table_line' width='700' border='0' cellspacing='1' cellpadding='2'>
  <tr class='titulo'>
	<td nowrap colspan='2' align='center'><b>List Prod. Cotar</b></td>
	<td nowrap colspan='2' align='center'><b>Controle de Cota��es</b></td>
	<td nowrap colspan='1' align='center'><b>Produtos</b></td>
	<td nowrap colspan='1' align='center'><b>Pedidos</b></td>
	<td nowrap colspan='1' align='center'><b>Nota Fiscal</b></td>
	<td nowrap colspan='1' align='center'><b>Fornecedor</b></td>
	<td nowrap colspan='1' align='center'><b>Requisi��o</b></td>
	<td nowrap colspan='1' align='center'><b>Venda</b></td>
	<td nowrap colspan='1' align='center'><b>Importa��o</b></td>
  </tr>
  <tr bgcolor='#fcfcfc'>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='cotacao_consultar_lista.php'>Sistema</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='requisicao_lista.php'>Requisi��es</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='cotacao_consultar_new.php'>Cota��es</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='cotacao_consultar_mapa.php'>Mapa </a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='entrada_produtos.php'>Lan�ar</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='cotacao_consultar_pedido.php'>Pedidos</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='nf_entrada.php'>NF Entrada</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='fornecedor_login.php'>Login</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='requisicao_login.php'>Login</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='orcamento_lista.php'>Or�amento</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='index_importacao.php'>Importar</a>
	</td>
  </tr>

  <tr>
    <td colspan='11'>
	<hr>
	</td>
  </tr>
</table>
</body>
</html>
<center>

-->

<table width='700' class='Menu'>
	<caption>Seja bem vindo ao sistema TIME!</caption>
	<tr>
		<td colspan='4'>&nbsp;</td>
	</tr>
	<tr align='center' bgcolor='#FFFFFF'>
		<td><a href='orcamento_cadastro.php?tipo_orcamento=venda''><img src='imagens/icone_comprar.jpg'></a></td>
		<td><a href='orcamento_cadastro.php?tipo_orcamento=fora_garantia'><img src='imagens/icone_fora_garantia.jpg'></a></td>
		<td><a href='orcamento_consulta.php'><img src='imagens/icone_pedido.jpg'></a></td>
		<td><a href='orcamento_tecnica.php'><img src='imagens/icone_fechamento.jpg'></a></td>

	</tr>

	<tr align='center' bgcolor='#EEEEEE'>

		<td><a href='orcamento_cadastro.php?tipo_orcamento=venda'>Venda</a></td>
		<td><a href='orcamento_cadastro.php?tipo_orcamento=fora_garantia'>Fora de Garantia</a></td>
		<td><a href='orcamento_consulta.php'>Consulta de Or�amento</a></td>
		<td><a href='orcamento_tecnica.php'>Fechamento de OS de Or�amento</a></td>

	</tr>
</table>

<table width='700' class='Menu'>
	<caption>Financeiro</caption>
	<tr>
		<td colspan='4'>&nbsp;</td>
	</tr>
	<tr align='center' >
		<td><a href='cadastro.php?tipo=cliente'><img src='imagens/icone_cliente.jpg'></a></td>
		<td><a href='crm_orcamento.php?tipo_orcamento=venda'><img src='imagens/icone_fluxo_caixa.jpg'></a></td>
		<td><a href='crm_orcamento.php?tipo_orcamento=fora_garantia'><img src='imagens/icone_pesquisa.png'></a></td>
		<td><a href='financeiro_caixa_banco.php'><img src='imagens/icone_caixa.jpg'></a></td>
	</tr>

	<tr align='center' bgcolor='#EEEEEE'>
		<td><a href='cadastro.php?tipo=cliente'>Cliente</a></td>
		<td><a href='crm_orcamento.php?tipo_orcamento=venda'>CRM de venda</a></td>
		<td><a href='crm_orcamento.php?tipo_orcamento=fora_garantia'>CRM or�amento</a></td>
		<td><a href='financeiro_caixa_banco.php'>Caixa</a></td>
	</tr>
</table>

<br><br><br><br>
