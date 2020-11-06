<?
//include "autentica_validade_senha.php";
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
// Data no passado
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
// Sempre modificado
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
// HTTP/1.0
header("Pragma: no-cache");
//////////////////////////////////////////////////////////
function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

function TempoExec($pagina, $sql, $time_start, $time_end){
	$time = $time_end - $time_start;
	$time = str_replace ('.',',',$time);
	$sql  = str_replace ('\t',' ',$sql);
	$fp = fopen ("/home/telecontrol/tmp/postgres.log","a");
	fputs ($fp,$pagina);
	fputs ($fp,"#");
	fputs ($fp,$sql);
	fputs ($fp,"#");
	fputs ($fp,$time);
	fputs ($fp,"\n");
	fclose ($fp);
}
//////////////////////////////////////////////////////////

$micro_time_start = getmicrotime();






//--=========== AQUI COMEÇA O NOVO MENU - RAPHAEL GIOVANINI V ===========--\\

if (1==1) {

?>
<html>
<head>
<title><?=$title;?></title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<link type="text/css" rel="stylesheet" href="SpryMenuBarHorizontal.css">
<script language='javascript' src='SpryMenuBar.js'></script>
<style>

A:hover {color:#247BF0; }
img     { border:0px;   }
.links {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: xx-small;
	font-weight: normal;
	color:#596d9b;
	}
</style>

<script>
/*****************************************************************
Nome da Função : displayText
		Apresenta em um campo as informações de ajuda de onde 
		o cursor estiver posicionado.
******************************************************************/
	function displayText( sText ) {
		document.getElementById("displayArea").innerHTML = sText;
	}

</script>

</head>
<body>
<ul id="MenuBar1" class="MenuBarHorizontal">
      <li>
      	<div align="center"><a class="MenuBarItemSubmenu" href="#">Vendas</a></div>
        <ul>
        <li>
			<div align="left"><a class="MenuBarItemSubmenu" href="#">Ordem de Serviço</a></div>
			<ul>
				<li><a href="admin/os_cadastro_raphael_ajax.php">Garantia</a></li>
				<li><a href="orcamento_cadastro_fabio.php?tipo_orcamento=fora_garantia">Fora da Garantia</a></li>
			</ul>			
		</li>
		<li><a href="orcamento_cadastro_fabio.php?tipo_orcamento=orca_venda">Orçamento de Venda</a></li>
		<li><a href="orcamento_cadastro_fabio.php?tipo_orcamento=venda">Venda</a></li>
	    <li><a href="#">Locação</a></li>
          </ul>
      </li>
   <li><div align="center"><a class="MenuBarItemSubmenu" href="#">CRM</a></div>
          <ul>
            <li><a href="#">Orçamento de Serviço</a></li>
            <li><a href="#">Orçamento de Venda</a></li>
			<li><a href="#">Satisfação</a></li>
          </ul>
  </li>
      <li><div align="center"><a class="MenuBarItemSubmenu" href="#">Compra</a></div>
          <ul>
            <li>
					<div align="left"><a class="MenuBarItemSubmenu" href="#">Requisição</a></div>
					  <ul>
						<li><b>USUÁRIO</b></li>
						<li><a href="requisicao.php?nova=nova">Nova Requisição</a></li>
						<li><a href="requisicao_item.php">Alterar Itens</a></li>
						<li><a href="requisicao_acompanhar.php">Acompanhar Requisição</a></li>
						<li><b>ADMIN</b></li>
						<li><a href="requisicao_mostra_listas.php">Todas Listas</a></li>
						<li><a href="requisicao_lista_gerada.php">Todas Requisições</a></li>
						<li><a href="requisicao_lista.php">Gerar Cotações de Requisição</a></li>
					  </ul>			
			</li>
            <li><a href="cotacao_consultar_mapa.php">Cotação</a></li>
            <li><a href="cotacao_consultar_pedido.php">Pedidos</a></li>
            <li>
					<div align="left"><a class="MenuBarItemSubmenu" href="#">Fornecedor</a></div>
					  <ul>
						<li><a href="fornecedor_login.php">Acesso como Fornecedor</a></li>
						<li><a href="fornecedor_lista_cotacao.php">Cotação</a></li>
						<li><a href="fornecedor_inf_compra.php">Informações de Compra</a></li>
					  </ul>			
			</li>
			<li><a href="nf_entrada.php">Faturamento</a></li>
			<li><a href="#">Recebimento</a></li>
            <li><a href="#">Estoque</a></li>
          </ul>
   </li>

  </li>
      <li><div align="center"><a class="MenuBarItemSubmenu" href="#">Financeiro</a></div>
          <ul>
            <li><a href="#">Contas a Receber</a></li>
            <li><a href="../contas_pagar-fabio.php">Contas a Pagar</a></li>
          </ul>
   </li>

  </li>
      <li><div align="center"><a class="MenuBarItemSubmenu" href="#">Cadastros</a></div>
          <ul>
            <li><a href="#">Clientes</a></li>
            <li><a href="#">Fornecedores</a></li>
            <li><a href="#">Vendedores</a></li>
            <li><a href="#">Técnicos</a></li>
            <li><a href="#">Plano de contas</a></li>
            <li><a href="#">Centro de Custos</a></li>
            <li><a href="#">Caixa/Banco</a></li>
            <li><a href="#">Linha</a></li>
            <li><a href="#">Familia</a></li>
            <li><a href="#">Marca</a></li>
            <li><a href="#">Modelo</a></li>
            <li><a href="#">Condição de Pagamento</a></li>
            <li><a href="#">Usúarios</a></li>
          </ul>
   </li>

  </li>
      <li><div align="center"><a class="MenuBarItemSubmenu" href="#">Relatórios</a></div>
          <ul>
            <li><a href="#">1</a></li>
            <li><a href="#">2</a></li>
            <li><a href="#">3</a></li>
          </ul>
   </li>
  </li>
      <li><div align="center"><a class="MenuBarItemSubmenu" href="#">Gerencial</a></div>
          <ul>
            <li><a href="#">Fluxo de Caixa</a></li>
            <li><a href="#">Orçamento</a></li>
            <li><a href="#">CRM</a></li>
          </ul>
   </li>
  </li>
      <li><div align="center"><a class="MenuBarItemSubmenu" href="#">Ajuda</a></div>
          <ul>
            <li><a href="#">Ajuda F1</a></li>
            <li><a href="#">Help Desk</a></li>
            <li><a href="#">Sobre</a></li>
          </ul>
   </li>
</ul>
<br>
<script type="text/javascript">
var MenuBar1 = new Spry.Widget.MenuBar("MenuBar1",{imgDown:"imagens/SpryMenuBarDownHover.gif", imgRight:"..imagens/SpryMenuBarRightHover.gif"});
 </script>
<br>
<? } ?>