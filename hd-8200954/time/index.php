<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
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

	<title>CONTROLE DE COTAÇÃO</title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

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
	<td nowrap colspan='2' align='center'><b>Controle de Cotações</b></td>
	<td nowrap colspan='1' align='center'><b>Produtos</b></td>
	<td nowrap colspan='1' align='center'><b>Pedidos</b></td>
	<td nowrap colspan='1' align='center'><b>Nota Fiscal</b></td>
	<td nowrap colspan='1' align='center'><b>Fornecedor</b></td>
	<td nowrap colspan='1' align='center'><b>Requisição</b></td>
	<td nowrap colspan='1' align='center'><b>Venda</b></td>
	<td nowrap colspan='1' align='center'><b>Importação</b></td>
  </tr>
  <tr bgcolor='#fcfcfc'>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='cotacao_consultar_lista.php'>Sistema</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='requisicao_lista.php'>Requisições</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='cotacao_consultar_new.php'>Cotações</a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='cotacao_consultar_mapa.php'>Mapa </a>
	</td>
	<td nowrap onMouseOver="this.bgColor='#aaccff'" onMouseOut="this.bgColor='#ffffff'">
	  <li><a href='entrada_produtos.php'>Lançar</a>
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
	  <li><a href='orcamento_lista.php'>Orçamento</a>
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