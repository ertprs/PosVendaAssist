<html>
<head>
<title><?=$title;?></title>
<style>
body {
	text-align: center;
	font-family:Arial;
	margin: 0px,0px,0px,0px;
	padding:  0px,0px,0px,0px;
}
A:link, A:visited { 
	TEXT-DECORATION: none;  color: #727272;
}

A:hover{
	color:#247BF0;
}
img{
border:0px;
}
</style>
</head>
<body>
<?
echo "<table height='2'><tr><td></td></tr></table>";
echo "<TABLE width='750px' border='0' cellspacing='0' cellpadding='0' 		align='center'>";
echo "<tr>";

echo "<td><a href='menu_os.php'><img src='../imagens/aba/os";if ($layout_menu == "os"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
echo "<td><a href='$PHP_SELF?menu=produtos'><img src='../imagens/aba/pedidos";if ($layout_menu == "pedido"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
echo "<td><a href='menu_tecnica'><img src='../imagens/aba/info_tecnico";if ($layout_menu == "tecnica"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
echo "<td><a href='cadastro'><img src='../imagens/aba/cadastro";if ($layout_menu == "lancamentos"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
echo "<td><a href='menu_tecnica'><img src='../imagens/aba/tabela_preco";if ($layout_menu == "preco"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
echo "<td><a href='menu_tecnica'><img src='../imagens/aba/promocoes";if ($layout_menu == "promocoes"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
echo "<td><a href='menu_tecnica'><img src='../imagens/aba/outros";if ($layout_menu == "outros"){ echo "_ativo";}echo ".gif' border='0'></a></td>";
echo "<td><img src='../imagens/aba/sair.gif' border='0'></td>";

echo "</tr>";
echo "</table>";
echo "<TABLE width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr>";
echo "<td background='http://www.telecontrol.com.br/assist/imagens/submenu_fundo_cinza.gif' colspan='8'>";

switch ($layout_menu) {
	case "os":
		include '../submenu_os.php';
		break;
	case "pedido":
		include '../submenu_pedido.php';
		break;
	case "cadastro":
		include '../submenu_cadastro.php';
		break;
	case "tecnica":
		include '../submenu_tecnica.php';
		break;
	case "preco":
		include '../submenu_preco.php';
		break;
	default:
		include '../submenu_os.php';
		break;
	}

echo"</td>";
echo "</tr>";
echo "</table>";
echo "<table height='2'><tr><td></td></tr></table>";

?>

<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align="center">
	<TR> 
		<TD width='10'><IMG src="../imagens/corner_se_laranja.gif"></TD>
		<TD style='font-size: 14px; font-weight: bold; font-family: arial;'> <? echo "$title" ?> </TD>
		<TD width='10'><IMG src="../imagens/corner_sd_laranja.gif"></TD>
	</TR>
</TABLE>
<TABLE width="700px" border="2"  cellpadding='0' cellspacing='0' bordercolor='#d9e2ef' align="center">
	<tr>
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
		<td style='padding: 5px; font-size: 12px; font-weight: normal; font-family: arial; text-align: center;'>
			<? 
				$data = date("Y-m-d");
				echo escreveData($data);
				echo date(" - H:i");
				echo " / Posto: " . $login_codigo_posto . "-" . ucfirst($login_nome);
				
				if($login_fabrica == 3 and $login_bloqueio_pedido == 't'){
					echo "<p>";
					
					echo "<font face='verdana' size='2' color='FF0000'><b>Existem títulos pendentes de seu posto autorizado junto ao Distribuidor.
					<br>
					Não será possível efetuar novo pedido faturado das linhas de eletro e branca.
					<br><br>
					Para regularizar a situação solicitamos um contato urgente com a TELECONTROL:
					<br>
					(14) 3413-6588 / (14) 3413-6589 / distribuidor@telecontrol.com.br
					<br>
					Entrar em contato com o departamento de cobranças ou <br>
					efetue o depósito em conta corrente no <br><BR>
					Banco Bradesco<BR>
					Agência 2155-5<br>
					C/C 17427-0<br><br>
					e encaminhe um fax (14 3413-6588) com o comprovante.</b>
					<br><br>
					<b>Para visualizar os títulos <a href='posicao_financeira_telecontrol.php'>clique aqui</a></b>
					</font>";
					
					echo "<p>";
				}
				
			?>
			</td>
		</tr>

<?
if ($login_fabrica == 3 and date("Y-m-d") < '2005-10-01') {
	echo "<tr bgcolor='#BED2D8'><td align='center'><b>Informativo de leitura obrigatória.</b><br><font size='-1'>Novo procedimento para envio de Ordens de Serviço e Nota fiscal de Mão-de-Obra</font><br><a href='pdf/britania_informativo_001.pdf'>Ler Informativo</a></td></tr>";
}

if (1==2) {
	$sqlX = "SELECT COUNT(*) FROM tbl_opiniao_posto WHERE tbl_opiniao_posto.fabrica = $login_fabrica AND tbl_opiniao_posto.ativo IS TRUE ";
	$res = @pg_exec ($con,$sqlX);
	$tem_pesquisa = @pg_result ($res,0,0) ;

	$sqlX = "SELECT COUNT(*) FROM tbl_opiniao_posto JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto) JOIN tbl_opiniao_posto_resposta USING (opiniao_posto_pergunta) WHERE tbl_opiniao_posto.fabrica = $login_fabrica AND tbl_opiniao_posto.ativo IS TRUE AND tbl_opiniao_posto_resposta.posto = $login_posto";
	$res = @pg_exec ($con,$sqlX);

	if (@pg_result ($res,0,0) == 0 AND $tem_pesquisa) {
		echo "<tr>";
		echo "<td bgcolor='#FF6633' style='padding: 5px; font-size: 12px; font-weight: normal; font-family: arial,verdana; text-align: center;'>";
		echo "<b>Atencão !</b> Você foi convidado a participar de uma pesquisa. <br>Antes de prosseguir utilizando o site, você deve completar a pesquisa. <br> <a href='opiniao_posto.php'>Clique aqui</a> para preencher o formulário";
		echo "</td>";
		echo "</tr>";

		if (strpos ($PHP_SELF,'opiniao_posto.php') === false) exit;
	}
}

?>
		<tr>
			<td><div class="frm-on-os" id="displayArea">&nbsp;</div></td>
		</tr>
</TABLE>