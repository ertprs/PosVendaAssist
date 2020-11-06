<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	
	<title><? echo $title ?></title>
	
	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">
	
	<style type='text/css'>

		body {margin-top  :  0px; margin-left :  0px; margin-right:  0px; padding: 0px,0px,0px,0px;	text-align: center;}

		img {border: 0px;}

		input {font-family: arial; font-size: 11px; border: 1px solid;}

		.frm {background-color   : #FFFFFF;}
		.frm-on {background-color: #FFCC00;}

		.user {padding-top : 10px; padding-left: 05px; font-family: arial; font-weight: bold; font-size: 11px; color: #606060;}
		
		.data {padding-top : 0px; padding-left: 5px; font-family: arial; font-weight: bold;	font-size: 9px; color: A0A0A0; height: 19px; text-align: left;}

		.mensagem {padding-top : 0px; padding-left: 5px; font-family: arial; font-weight: bold; font-size: 11px; color: FFFFFF; text-align: left;}
		
		/*========================== NAVEGAÇÃO ===================================*/
		#nav {padding-left: 46px; padding-top : 3px; color: #E0E0E0;}

		a:link.nav {color: #909090;	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 9px; font-weight: bold; text-decoration: none;}

		a:visited.nav {color: #909090; font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 9px; font-weight: bold; text-decoration: none;}

		a:hover.nav {color: #000000; font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 9px; font-weight: bold; text-decoration: none;}

	</style>

	<script>
		/*********************************************************************************************
				Nome da Função : displayText
				Apresenta em um campo as informações de ajuda de onde o cursor estiver posicionado.
		**********************************************************************************************/
			function displayText( sText ) {
				document.getElementById("displayArea").innerHTML = sText;
			}

	</script>
	<?
		function escreveData($data) { 
			$vardia = substr($data,8,2);
			$varmes = substr($data,5,2);
			$varano = substr($data,0,4);

			$convertedia = date ("w", mktime (0,0,0,$varmes,$vardia,$varano)); 

			$diaSemana = array("Domingo", "Segunda-feira", "Terça-feira", "Quarta-feira", "Quinta-feira", "Sexta-feira", "Sábado"); 

			$mes = array(1=>"janeiro", "fevereiro", "março", "abril", "maio", "junho", "julho", "agosto", "setembro", "outubro", "novembro", "dezembro"); 

			if ($varmes < 10) $varmes = substr($varmes,1,1);

			return $diaSemana[$convertedia] . ", " . $vardia  . " de " . $mes[$varmes] . " de " . $varano; 
		} 
		// Utilizar da seguinte maneira 
		//echo escreveData("2005-12-02"); 
	?> 

	<script language="javascript" src="js/assist.js"></script>


</head>


<body>
	<table width='100%' border='0' cellspacing='0' cellpadding='0'>
	<? if ($sem_menu == false OR strlen ($sem_menu) == 0 ) { ?>
	<tr>
	<?
		echo "<td width='30%' align='center' style='padding-top:7px;' background='a_imagens/fundo_top_azul_esquerdo.gif'>";
			echo "<a href='".$login_fabrica_site."' target='_new'>";
			echo "<IMG SRC='/assist/logos/".$login_fabrica_logo."' ALT='".$login_fabrica_site."' border='0'>";
			echo "</a>";
			echo "</td>";
		echo "<td width='580px'><img src='a_imagens/botoes_".$layout_menu.".gif' usemap='#menu_map'></td>";
		echo "<td background='a_imagens/fundo_top_azul_direito.gif'>&nbsp;</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td class='user' background='a_imagens/fundo_but_azul_esquerdo.gif'>user:&nbsp; ".ucfirst($login_login)."</td>";
		echo "<td id='nav' background='a_imagens/fundo_".$layout_menu.".gif'>";
				switch ($layout_menu) {
				case "os":
					echo "<a class='nav' href='#'>CADASTRO</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>CONSULTA</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>REVENDA</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>SAIR</a>";
					break;
				case "pedido":
					echo "<a class='nav' href='#'>CADASTRO1</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>CONSULTA1</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>REVENDA1</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>SAIR1</a>";
					break;
				case "cadastro":
					echo "<a class='nav' href='#'>CADASTRO2</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>CONSULTA2</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>REVENDA2</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>SAIR2</a>";
					break;
				case "gerencia":
					echo "<a class='nav' href='#'>CADASTRO3</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>CONSULTA3</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>REVENDA3</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>SAIR3</a>";
					break;
				case "financeiro":
					echo "<a class='nav' href='#'>CADASTRO4</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>CONSULTA4</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>REVENDA4</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>SAIR4</a>";
					break;
				case "tecnico":
					echo "<a class='nav' href='#'>CADASTRO5</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>CONSULTA5</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>REVENDA5</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>SAIR5</a>";
					break;
				default:
					echo "<a class='nav' href='#'>CADASTRO6</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>CONSULTA6</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>REVENDA6</a>&nbsp;|&nbsp;";
					echo "<a class='nav' href='#'>SAIR6</a>";
					break;
				};
			} ;
		?>
		</td>
		<td background='a_imagens/fundo_but_azul_direito.gif'><img src='a_imagens/fundo_but_azul_direito.gif'></td>
	</tr>
	</table>

	<table width='100%' border='0' cellspacing='0' cellpadding='0'>
	<tr>
		<td class='data' colspan='2' background='a_imagens/divisa_fundo.gif'><? echo $user_level ?></td>
		<td class='data' colspan='1' background='a_imagens/divisa_fundo.gif'>	
		<? 
			$data = date("Y-m-d");
			echo escreveData($data);
		?>
		</td>
		<td class='data' colspan='2' background='a_imagens/divisa_fundo.gif'>	
			HORA:
		<? 
			echo date("H:i");	
		?>&nbsp;
			h
		</td>
	</tr>
	<tr>
		<td><img src='a_imagens/dpl_curva_esquerda.gif'></td>
		<td><img src='a_imagens/dpl_esquerda.gif'></td>
		<td id="displayArea" class='mensagem' width='100%' background='a_imagens/dpl_miolo_verm.gif'><img src='a_imagens/dpl_miolo_verm.gif'></td>
		<td><img src='a_imagens/dpl_direita.gif'></td>
		<td><img src='a_imagens/dpl_curva_direita.gif'></td>
	</tr>
	<tr>
		<td><img src='a_imagens/dpl_deg_esquerda.gif'></td>
		<td colspan='3'>
			&nbsp; Espaço para inserir barra de navegação
		</td>
		<td><img src='a_imagens/dpl_deg_direita.gif'></td>
	</tr>
	</table>
	<table width='730px' border='0' cellspacing='0' cellpadding='0' height='380px'>
	<tr>
		<td>
			<input class="frm" type="text" name="consumidor_cpf" size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. ex.: 68539487691');">
		</td>
		<td>
			<input class="frm" type="text" name="consumidor_cpf" size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o NOME do consumidor. ');">
		</td>
		<td>
			<input class="frm" type="text" name="consumidor_cpf" size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CÓDIGO do consumidor.');">
		</td>		
	</tr>
	</table>
<map name="menu_map">
	<area shape="rect" coords="040,05,120,60" href="a_menu_os.php">
	<area shape="rect" coords="130,05,210,60" href="a_menu_pedido.php">
	<area shape="rect" coords="220,05,300,60" href="a_menu_cadastro.php">
	<area shape="rect" coords="310,05,390,60" href="a_menu_gerencia.php">
	<area shape="rect" coords="400,05,480,60" href="a_menu_financeiro.php">
	<area shape="rect" coords="490,05,570,60" href="a_menu_tecnico.php">
</map>

	<table width='100%' border='0' cellspacing='0' cellpadding='0'>
	<tr>
		<td width='100%' background='a_imagens/rdp_fundo.gif'><img src='a_imagens/pixel.gif'></td>
		<td width='81px' background='a_imagens/rdp_fundo.gif'><img src='a_imagens/rdp_texto.gif'></td>
	</tr>
	</table>
</body>
</html>