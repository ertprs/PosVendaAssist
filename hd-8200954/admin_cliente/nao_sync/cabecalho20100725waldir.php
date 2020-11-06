<?
if($login_fabrica == 3 and $login_login <> 'samuel'){
	include "autentica_validade_senha.php";
//	echo "1";
}
include "monitora_cabecalho.php";
#header("Expires: 0");
#header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
#header("Pragma: no-cache, public");

/*$sql = "SELECT tbl_fabrica.multimarca,
				tbl_fabrica.acrescimo_tabela_base
		FROM   tbl_fabrica
		WHERE  tbl_fabrica.fabrica = $login_fabrica
		AND    tbl_fabrica.multimarca is true
		AND    tbl_fabrica.acrescimo_tabela_base is true;";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0){
	$multimarca            = trim(pg_result($res,0,multimarca));
	$acrescimo_tabela_base = trim(pg_result($res,0,acrescimo_tabela_base));
}*/

function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

function TempoExec($pagina, $sql, $time_start, $time_end){
	if (1 == 1){
		$time = $time_end - $time_start;
		$time = str_replace ('.',',',$time);
		$sql  = str_replace ('\t',' ',$sql);
#		$fp = fopen ("/home/telecontrol/tmp/postgres.log","a");
#		fputs ($fp,$pagina);
#		fputs ($fp,"#");
#		fputs ($fp,$sql);
#		fputs ($fp,"#");
#		fputs ($fp,$time);
#		fputs ($fp,"\n");
#		fclose ($fp);
	}
}

$micro_time_start = getmicrotime();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

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

	<link type="text/css" rel="stylesheet" href="css/css.css">
	<link type="text/css" rel="stylesheet" href="css/tooltips.css">
</head>

<script>
/*****************************************************************
Nome da Função : displayText
		Apresenta em um campo as informações de ajuda de onde
		o cursor estiver posicionado.
******************************************************************/
	function displayText( sText ) {
		document.getElementById("displayArea").innerHTML = sText;
	}

	function atualiza_dado(admin){
		window.open('atualiza_dado.php?admin='+admin, 'ouverture', 'toolbar=no, status=yes, scrollbars=yes, resizable=no, width=400, height=500');
	}
</script>

<script language="javascript" src="../admin/js/assist.js"></script>

<? if (in_array($login_fabrica,array(14,43,66))) { ?>
<script type="text/javascript">
function setCookie(c_name,value,path,expiredays)
{
	var exdate=new Date();
	exdate.setDate(exdate.getDate()+expiredays);
	var expireDate = (expiredays==null) ? "" : ";expires="+exdate.toGMTString();
	var c_path     = (path == null) ? "" : ";path="+path;
	document.cookie=c_name+ "=" +escape(value)+c_path;
	window.location.reload();
}

function trocaFabrica(novoLogin) {
	login = novoLogin.split("|");
	setCookie("cook_admin",login[0],"/assist/");
	setCookie("cook_fabrica",login[1],"/assist/");
}

</script>
<?}?>
<!--
<body onLoad="fnc_preload();">
-->
<style>
#helpdesk{
	color:#0000FF;
	BORDER-RIGHT: #6699CC 2px solid;
	BORDER-TOP: #6699CC 2px solid;
	BORDER-LEFT: #6699CC 2px solid;
	BORDER-BOTTOM: #6699CC 2px solid;
	FONT: 9pt Arial ;
	COLOR:            #FF0000;
	BACKGROUND-COLOR: #F2F7FF;
	position: absolute;
	top: 55px;
	right: 8px;
	width:100px;
	height:20px;
}
</style>
<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' <?=$body_onload;?> >


<?

#echo "<h1>O site sairá do ar em 5 minutos para manutenção</h1>";
#echo "<h1>e retornará dentro de meia hora</h1>";
#echo "<h1>Por favor, finalize seu trabalho.</h1>";
#echo "<p><font size='+3' color='#ff0000'>Site Fora do ar em 1 minuto</font>";



if ($sem_menu == false OR strlen ($sem_menu) == 0 ) {
	echo "<table border='0' cellpadding='0' cellspacing='0' background='/assist/imagens/fundo-cabecalho.png'  align = 'center'>";
	echo "<tr>";
	echo "<td width='100'><img src='/assist/imagens/pixel.gif' width='30' height='1'></td>";
	echo "<td width='100%' align='center' valign='top'>";


	switch ($layout_menu) {
	case "gerencia":
		echo "<img src='imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
		break;
	case "callcenter":
		if($login_fabrica == 30){
			echo "<img src='imagens_admin/btn_callcenter_esmaltec.gif' usemap='#menu_map'>
				<img src='imagens_admin/btn_callcenter_esmaltec_sair.gif' usemap='#menu2_map'>";
		}else{
			echo "<img src='imagens_admin/btn_callcenter.gif' usemap='#menu_map'>";
		}
		break;
	case "cadastro":
		echo "<img src='imagens_admin/btn_cadastro.gif' usemap='#menu_map'>";
		break;
	case "tecnica":
		echo "<img src='imagens_admin/btn_tecnica.gif' usemap='#menu_map'>";
		break;
	case "financeiro":
		echo "<img src='imagens_admin/btn_financeiro.gif' usemap='#menu_map'>";
		break;
	case "auditoria":
		echo "<img src='imagens_admin/btn_auditoria.gif' usemap='#menu_map'>";
		break;
	default:
		echo "<img src='imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
		break;
	}
	echo "</td>";

	echo "</tr>";
	echo "</table>";


	echo "<table border='0' cellpadding='0' cellspacing='0' background='/assist/imagens/submenu_fundo_cinza.gif'  align = 'center'>";
	echo "<tr>";
	echo "<td width='100'><img src='/assist/imagens/pixel.gif' width='30' height='1'></td>";
	echo "<td width='100%' align='center'>";

	switch ($layout_menu) {
		case "gerencia":
			include 'submenu_gerencia.php';
			break;
		case "callcenter":
			include 'submenu_callcenter.php';
			break;
		case "cadastro":
			include 'submenu_cadastro.php';
			break;
		case "tecnica":
			include 'submenu_tecnica.php';
			break;
		case "financeiro":
			include 'submenu_financeiro.php';
			break;
		case "auditoria":
			include 'submenu_auditoria.php';
			break;
		default:
			include 'submenu_gerencia.php';
			break;
	}
	echo "</td>";

	echo "<td width='100'><img src='/assist/imagens/pixel.gif' width='30' height='1'></td>";

	echo "</tr>";

	echo "</table>";

	if($login_fabrica == 30){
		echo "<map name='menu_map'><area shape='rect' coords='0,0,78,24' href='menu_callcenter.php'></map>";
		echo "<map name='menu2_map'><area shape='rect' coords='0,0,78,24' ref='http://www.telecontrol.com.br/assist'></map>";
	}else{
		echo "<map name='menu_map'><area shape='rect' coords='100,0,176,24' href='menu_callcenter.php'>";
		echo "<area shape='rect' coords='541,0,622,24' href='http://www.telecontrol.com.br/assist'>
	</map>";
	}

}

?>

<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF'  align = 'center'>
<TR>
<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
<TD style='font-size: 14px; font-weight: bold; font-family: arial;'> <? echo "$title" ?> </TD>
<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<TABLE width="700px" border="2" align="center" cellpadding='0' cellspacing='0' bordercolor='#d9e2ef'>
<tr>
	<td>
		<?
			if ($login_login == 'suggar') {
				echo "<a href='$login_fabrica_site' target='_new'>";
			    echo "<img src='/assist/logos/suggar.jpg' alt='$login_fabrica_site' border='0' height='40'>";
			}elseif ($login_login == 'tulio' or ($login_login == 'sergio' and $login_fabrica != 46) or ($login_login == 'samuel' and 1==2) ) {
				echo "<a href='$login_fabrica_site' target='_new'>";
			    echo "<img src='/assist/logos/telecontrol.jpg' alt='$login_fabrica_site' border='0' height='40'>";
			}else{
				if($login_fabrica == 30){
					$img_contrato = "/assist/logos/$login_fabrica_logo";
					$sql_img = "SELECT marca FROM tbl_cliente_admin WHERE cliente_admin = $login_cliente_admin";
					$res_img = pg_exec($con,$sql_img);
					$marca   = pg_result($res_img,0,marca);
					if($marca == 164){
						$img_contrato = "/assist/logos/cabecalho_print_itatiaia.jpg";
						$login_fabrica_site = "http://www.itatiaiamoveis.com.br";
					}
					if($marca == 163){
						$img_contrato = "/assist/logos/logo_ambev.gif";
						$login_fabrica_site = "http://www.ambev.com.br";
					}
					echo "<a href='$login_fabrica_site' target='_new'>";
					echo "<img src='$img_contrato' alt='$login_fabrica_site' border='0' height='40'>";
				}else{
					echo "<a href='$login_fabrica_site' target='_new'>";
					echo "<img src='$img_contrato' alt='$login_fabrica_site' border='0' height='40'>";
				}
			}
			echo "</a>";
		?>
	</td>
<?
$msg_atualiza ="";
$sql = "SELECT fone,email, dia_nascimento, mes_nascimento FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin";
$res = pg_exec($con,$sql);
if(pg_numrows($res) > 0) {
	$fone_admin     = pg_result($res,0,fone);
	$email_admin    = pg_result($res,0,email);
	$dia_nascimento = pg_result($res,0,dia_nascimento);
	$mes_nascimento = pg_result($res,0,mes_nascimento);
	if(strlen($fone_admin) == 0 OR strlen($email_admin) == 0 OR strlen($dia_nascimento) == 0 OR strlen($mes_nascimento) == 0 ) {
		$msg_atualiza .= "O sistema detectou que alguns dados seus estão desatualizados.<br>Clique aqui para atualizar!";
	}
	//if(strlen($fone_admin) == 0 AND strlen($email_admin) == 0) {
	//	$msg_atualiza = "Precisamos da atualização do seu telefone e email no sistema!";
	//}elseif(strlen($fone_admin) == 0) {
	//	$msg_atualiza = "Precisamos da atualização do seu telefone no sistema!";
	//}elseif(strlen($email_admin) == 0) {
	//	$msg_atualiza = "Precisamos da atualização do seu email no sistema!";
	//}elseif(strlen($dia_nascimento) == 0) {
	//	$msg_atualiza = "Precisamos da data do seu aniversário!";
	//}
}
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
	<td style='font-size: 14px; font-weight: bold; font-family: arial;'>
	<?
		$data = date("Y-m-d");
		echo escreveData($data);
		echo date(" - H:i");
		echo " / Usuário: ".ucfirst($login_login);
		if(strlen($msg_atualiza) > 0) {
			echo "<a href='atualiza_dado.php?admin=$login_admin' target='_blank' class='tt' style='color:#FFF'><img src='../imagens/alerta2.gif'><span class='tooltip'><span class='top'></span><span class='middle'>$msg_atualiza</span><span class='bottom'></span></span></a>";
		}
	?>
	</td>
	<td>
	<?
	 //----INICIO HELP----//
		$local = $PHP_SELF;
/*
		$sql = "SELECT *
				FROM tbl_help
				WHERE fabrica  = $login_fabrica
				AND   programa = '$local'";
		$res = @pg_exec ($con,$sql);
		if(@pg_numrows($res)==0){
			$sql = "SELECT * from tbl_help
					WHERE programa = '$local'
					AND   fabrica IS NULL";
			$res = @pg_exec ($con,$sql);
		}

		if (@pg_numrows($res) >= 0) {

			$programa       = @pg_result($res,0,programa);
			$help           = @pg_result($res,0,help);
			if($programa==$local){

			echo"
			<SCRIPT LANGUAGE='JavaScript'>
			function ajuda()
			{
			window.open('help.php?programa=$programa', 'ouverture', 'toolbar=no, status=yes, scrollbars=yes, resizable=no, width=400, height=500');
			}
			</SCRIPT>";
			echo"<A HREF='#' ONCLICK='ajuda()'>
			<img src='imagens/help.jpg' alt='Clique aqui para obter ajuda'>";

			echo "</A>";
			}

		}
*/
/* Ebano esta criando nova rotina 
		$sql = "SELECT programa, help FROM tbl_help WHERE fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);

		if (@pg_numrows($res) >= 0) {

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$programa       = pg_result($res,$i,programa);
				$help           = pg_result($res,$i,help);

				$pos = strpos($local, $programa);
				if ($pos == true) {
					echo"
					<SCRIPT LANGUAGE='JavaScript'>
					function ajuda()
					{
					window.open('help.php?programa=$programa', 'ouverture', 'toolbar=no, status=yes, scrollbars=yes, resizable=no, width=400, height=500');
					}
					//-->
					</SCRIPT>";
					echo"<A HREF='#' ONCLICK='ajuda()'>
					<img src='imagens/help.jpg' alt='Clique aqui para obter ajuda'>";
					echo "</A>";
				}
			}
		}else{
			$sql = "SELECT programa, help FROM tbl_help WHERE fabrica IS NULL";
			$res = pg_exec ($con,$sql);
			if (@pg_numrows($res) >= 0) {
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					$programa       = pg_result($res,$i,programa);
					$help           = pg_result($res,$i,help);

					$pos = strpos($local, $programa);
					if ($pos == true) {
						echo"
						<SCRIPT LANGUAGE='JavaScript'>
						function ajuda()
						{
						window.open('help.php?programa=$programa', 'ouverture', 'toolbar=no, status=yes, scrollbars=yes, resizable=no, width=400, height=500');
						}
						//-->
						</SCRIPT>";
						echo"<A HREF='#' ONCLICK='ajuda()'>
						<img src='imagens/help.jpg' alt='Clique aqui para obter ajuda'>";
						echo "</A>";
					}

				}
			}
		}
	*/

	//----FIM HELP----//
	 ?>

	</td>

</tr>

<?php 

if ($login_fabrica == 11 and ($title=='Cadastros do Sistema' or $title=='MENU GERÊNCIA')) {

		$sql = "SELECT log_integracao from tbl_log_integracao where fabrica = $login_fabrica and confirmar_leitura = 'f'";

		$res = pg_exec($con,$sql);

		if (pg_num_rows($res)>0) {

			$cor = 'red';
			$texto = "<a href='log_erro_integracao.php'><font color='white' size=+1><b>Existem Erros de Integracao - Clique aqui para Visualizar</FONT></b></a>";

		}
	}

if ($login_fabrica == 43 or $login_fabrica == 66 or $login_fabrica == 14)

if (in_array($login_admin,array(270,1164,1216,1806,1838))) {

/*
Gera dois arrays:
	$admins		=> contém os ids admin que o usuário tem acesso
	$fabricas	=> contém os nomes das fábricas acessadas por cada admin
			   para acessar o nome de uma fabrica, use o admin dela
			   como índice. Ex: $fabrica[40]

*/

$sql = "SELECT * FROM tbl_admin_igual WHERE admin=$login_admin OR admin_igual=$login_admin LIMIT 1";
$res = pg_query($con, $sql);

$admin_principal = pg_fetch_result($res, 0, admin);

$sql = "SELECT * FROM tbl_admin_igual WHERE admin=$admin_principal";
$res = pg_query($con, $sql);

$admins = array();
$admins[] = $admin_principal;
for($i = 0; $i < pg_num_rows($res); $i++)
{
	$admins[] = pg_fetch_result($res, $i, admin_igual);
}
$admins = implode(",", $admins);

$sql = "SELECT
		tbl_admin.admin,
		tbl_admin.fabrica,
		tbl_fabrica.nome

		FROM tbl_admin
		JOIN tbl_fabrica USING(fabrica)

		WHERE
		tbl_admin.admin IN ($admins)
		AND tbl_admin.admin <> $login_admin
		";
$res = pg_query($con, $sql);

// $fabricas = array();


?>
<tr>
	<td colspan='3'>Logar Como...<br>
	<select name='logar_como' onChange='trocaFabrica(this.value);'>
	<option>selecione</option>
	<?php
		for($i = 0; $i < pg_num_rows($res); $i++)
		{
			list($m_admin,$m_fabrica,$nome_fabrica) = pg_fetch_row($res, $i);
			echo "<option value='$m_admin|$m_fabrica'>$nome_fabrica</option>";
		}
	?>
	</select>
	</td>
</tr>
<?
}
?>
<tr>
	<td colspan=3 bgcolor='<?echo $cor;?>'><div class="frm-on-os" id="displayArea"><?echo $texto;?>&nbsp;</div></td>
</tr>

<?
	if(strlen(trim($msg_validade_cadastro))>0){
		echo "<tr>";
		echo "<td align='center' colspan=3 bgcolor='red'>$msg_validade_cadastro</td>";
		echo "</tr>";

} ?>
</TABLE>



<? /* comentado por Paulo, Tulio pediu para tirar por ocupar espaço 07/05/2008
<table width='500' align='center' border='0'>
<tr>
	<td valign='middle'>
		if(strlen($prefixo)>0) {
			<a href='/assist/helpdesk/<?=$prefixo?>chamado_lista.php' target='_blank'>
		}else{
			<a href='/assist/helpdesk/<?=$prefixo?>chamado_lista.php?status=Análise&exigir_resposta=t' target='_blank'>
		}


		if ($qtde_help == 0 OR strlen ($qtde_help) == 0) {
			echo "<td width='100' align='center' valign='top'>";
			if($login_fabrica == 10){
				echo "<a href='/assist/helpdesk/adm_chamado_lista_novo.php' target='_blank'>";
			}else{
				echo "<a href='/assist/helpdesk/".$prefixo."chamado_detalhe.php' target='_blank'>";
			}
			echo "<img src='/assist/helpdesk/imagem/help.png' alt='HELP-DESK - Clique aqui para abrir um chamado no Suporte Telecontrol.' border='0'></a></td>";
		}else {
			if ($qtde_help == 1) {
				$msg_help = "Você tem $qtde_help chamado pendente, aguardando sua resposta" ;
			}else{
				$msg_help = "Você tem $qtde_help chamados pendentes, aguardando sua resposta" ;
			}
			//se não for para telecontrol, tem filtro
			if(strlen($prefixo)>0) {
				echo "<td width='100' align='center' valign='top'><a href='/assist/helpdesk/".$prefixo."chamado_lista.php' target='_blank'><img src='/assist/helpdesk/imagem/help-vermelho.gif' alt='$msg_help' border='0'></a></td>";
			}else{
				echo "<td width='100' align='center' valign='top'><a href='/assist/helpdesk/".$prefixo."chamado_lista.php?status=Análise&exigir_resposta=t' target='_blank'><img src='/assist/helpdesk/imagem/help-vermelho.gif' alt='HELP-DESK - Clique aqui para abrir um chamado no Suporte Telecontrol.' border='0'></a></td>";
			}
		}
// 			<img src='/assist/helpdesk/imagem/help.png' border='0' valign='center' alt='HELP-DESK - Clique aqui para abrir um chamado no Suporte Telecontrol.'>

		</a>
	</td>

	<td valign='middle'>
		<font face='arial' color='#666666'>
		 if ($qtde_help == 0 OR strlen ($qtde_help) == 0) {
				echo "<a href='/assist/helpdesk/chamado_detalhe.php' target='_blank'>HELP-DESK - Clique aqui para abrir um chamado no Suporte Telecontrol.</a>";
			}else{
				if(strlen($prefixo)>0) {
				echo "<a href='/assist/helpdesk/".$prefixo."chamado_lista.php' target='_blank'>HELP-DESK - Clique aqui para abrir um chamado no Suporte Telecontrol.</a>";
				}else{
					echo "<a href='/assist/helpdesk/".$prefixo."chamado_lista.php?status=Análise&exigir_resposta=t' target='_blank'>HELP-DESK - Clique aqui para abrir um chamado no Suporte Telecontrol.</a>";
				}
			}
			echo "</font><br>";
	if ($qtde_help == 1) {
					echo  "<FONT SIZE='1' COLOR='#FF0000'>Você tem $qtde_help chamado pendente, aguardando sua resposta</FONT>" ;
				}elseIF($qtde_help > 0){
					echo "<FONT SIZE='1' COLOR='#FF0000'>Você tem $qtde_help chamados pendentes, aguardando sua resposta</FONT>" ;
				}


	</td>
	</tr>

</table>*/
?>

<? # solicitado por Samuel . Colocado por Fabio em 03/08/2007
/*
	echo "
<br>
<table width='700' align='center'>
<tr>
<td align='left'>
<h3 style='font-size:12px;border:1px solid #FECC65;background-color:#FFEDC4;color:black;text-align:center'>Os relatórios são melhores visualizados com o navegador <b style='color:blue'>Firefox</b>. <a href='http://ftp-mozilla.netscape.com/pub/mozilla.org/firefox/releases/2.0.0.6/win32/pt-PT/Firefox%20Setup%202.0.0.6.exe'>Clique aqui para baixar</a></h3>.
</td>
</tr>
</table>
";
*/
?>

<?
//if($ip=='200.228.78.95'){
if(1==2){
?>
	<table width='700' align="center" cellpadding='0' cellspacing='0'>
		<tr>
			<td align='center'>
			<p><b>A T E N Ç Ã O</b>
			<br>
			Neste domingo (22/03/2009) estaremos fazendo uma manutenção preventiva em nossos <br>
			servidores das 10:00h as 15:00h.
			<br>
			Não estamos prevendo parada do sistema, mas eventualmente, poderá ficar off-line!
			<br>
			<p>Atenciosamente
			<br> 
			Telecontrol Networking
			</td>
		</tr>
	</table>
<?
}
#------------- Programa Restrito ------------------#
$sql = "SELECT admin FROM tbl_admin WHERE admin = $login_admin AND privilegios NOT ILIKE '%*%' ";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) > 0) {
	$sql = "SELECT programa
			FROM   tbl_programa_restrito
			WHERE  tbl_programa_restrito.programa = '$PHP_SELF'
			AND    tbl_programa_restrito.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$programa = pg_result($res,0,programa); //HD 72857

		if($login_fabrica <> 3 OR ($login_fabrica == 3 AND $programa <> '/assist/admin/os_cadastro.php')){
			$sql = "SELECT programa
					FROM   tbl_programa_restrito
					JOIN   tbl_admin USING (admin)
					WHERE  tbl_programa_restrito.programa = '$PHP_SELF'
					AND    tbl_programa_restrito.admin    = $login_admin
					AND    tbl_programa_restrito.fabrica  = $login_fabrica ";
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 0) {
				echo "<p><hr><center><h1>Sem permissão para acessar este programa</h1></center><p><hr>";
				exit;
			}
		}
	}
}

//echo "<!-- restricao \n $sql -->";
//include "monitora.php";
?>
