<?
//include "../autentica_validade_senha.php";
include "../monitora_cabecalho.php";
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

</script>

<script language="javascript" src="js/assist.js"></script>

<!--
<body onLoad="fnc_preload();">
-->

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
		echo "<img src='imagens_admin/btn_callcenter.gif' usemap='#menu_map'>";
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

	if ($login_fabrica == "10") $prefixo = 'adm_';

/*	$sql  = "SELECT COUNT(*) FROM (
				SELECT tbl_hd_chamado.admin , (SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY hd_chamado_item DESC LIMIT 1) AS admin_item
				FROM tbl_hd_chamado WHERE admin = $login_admin
			) As help WHERE admin <> admin_item ";
*/
	$sql = "SELECT COUNT (*) FROM (
				SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.status ,tbl_hd_chamado.admin ,
					(SELECT tbl_hd_chamado_item.admin
					FROM tbl_hd_chamado_item
					JOIN tbl_hd_chamado using(hd_chamado)
					WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					ORDER BY hd_chamado_item DESC LIMIT 1) AS admin_item
				FROM tbl_hd_chamado
				WHERE admin = $login_admin and upper(status) <> 'RESOLVIDO'
			) As help WHERE admin <> admin_item";
	$sql = "SELECT count(*)
			FROM tbl_hd_chamado
			WHERE admin = $login_admin
			AND (
				(exigir_resposta is TRUE and status<>'Resolvido')
				OR
				(resolvido is null and status='Resolvido')
				)
			AND status<>'Cancelado'
			AND fabrica_responsavel <> $login_fabrica";
			// AND fabrica_responsavel <> $login_fabrica - hd 3469 tectoy estava abrindo callcenter e estava aparecendo como pendente
	$resX = pg_exec ($con,$sql);
	$qtde_help = pg_result ($resX,0,0);
	if ($qtde_help == 0 OR strlen ($qtde_help) == 0) {
		echo "<td width='100' align='center' valign='top'><a href='/assist/helpdesk/".$prefixo."chamado_detalhe.php' target='_blank'><img src='/assist/helpdesk/imagem/help.png' width='30' alt='Sistema de HelpDesk TELECONTROL' border='0'></a></td>";
	}else {
		if ($qtde_help == 1) {
			$msg_help = "Você tem $qtde_help chamado pendente, aguardando sua resposta" ;
		}else{
			$msg_help = "Você tem $qtde_help chamados pendentes, aguardando sua resposta" ;
		}
		//se não for para telecontrol, tem filtro
		if(strlen($prefixo)>0) {
			echo "<td width='100' align='center' valign='top'><a href='/assist/helpdesk/".$prefixo."chamado_lista.php' target='_blank'><img src='/assist/helpdesk/imagem/help-vermelho.gif' width='30' alt='$msg_help' border='0'></a></td>";
		}else{
			echo "<td width='100' align='center' valign='top'><a href='/assist/helpdesk/".$prefixo."chamado_lista.php?status=Análise&exigir_resposta=t' target='_blank'><img src='/assist/helpdesk/imagem/help-vermelho.gif' width='30' alt='$msg_help' border='0'></a></td>";
		}
	}

	echo "</tr>";
	echo "</table>";



	echo "<table border='0' cellpadding='0' cellspacing='0' background='/assist/imagens/submenu_fundo_cinza.gif'  align = 'center'>";
	echo "<tr>";
	echo "<td width='100'><img src='/assist/imagens/pixel.gif' width='30' height='1'></td>";
	echo "<td width='100%' align='center'>";

	switch ($layout_menu) {
	case "gerencia":
		include '../submenu_gerencia.php';
		break;
	case "callcenter":
		include '../submenu_callcenter.php';
		break;
	case "cadastro":
		include '../submenu_cadastro.php';
		break;
	case "tecnica":
		include '../submenu_tecnica.php';
		break;
	case "financeiro":
		include '../submenu_financeiro.php';
		break;
	case "auditoria":
		include '../submenu_auditoria.php';
		break;
	default:
		include '../submenu_gerencia.php';
		break;
	}
	echo "</td>";

	echo "<td width='100'><img src='/assist/imagens/pixel.gif' width='30' height='1'></td>";


	echo "</table>";

	echo "
	<map name='menu_map'>
	<area shape='rect' coords='014,0,090,24' href='../menu_gerencia.php'>
	<area shape='rect' coords='100,0,176,24' href='../menu_callcenter.php'>
	<area shape='rect' coords='190,0,263,24' href='../menu_cadastro.php'>
	<area shape='rect' coords='276,0,353,24' href='../menu_tecnica.php'>
	<area shape='rect' coords='362,0,439,24' href='../menu_financeiro.php'>
	<area shape='rect' coords='450,0,527,24' href='../menu_auditoria.php'>
	<area shape='rect' coords='541,0,622,24' href='http://www.telecontrol.com.br/assist'>
	</map>";

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
			echo "<a href='$login_fabrica_site' target='_new'>";
			if ($login_login == 'suggar') {
			    echo "<img src='/assist/logos/suggar.jpg' alt='$login_fabrica_site' border='0' height='40'>";
			}elseif ($login_login == 'tulio' or $login_login == 'sergio') {
			    echo "<img src='/assist/logos/telecontrol.jpg' alt='$login_fabrica_site' border='0' height='40'>";
			}else{
			    echo "<img src='/assist/logos/$login_fabrica_logo' alt='$login_fabrica_site' border='0' height='40'>";
			}
			echo "</a>";
		?>
	</td>
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
	<td style='font-size: 14px; font-weight: bold; font-family: arial;'>
	<?
		$data = date("Y-m-d");
		echo escreveData($data);
		echo date(" - H:i");
		echo " / Usuário: ".ucfirst($login_login);
	?>
	</td>
	<td>
	<?
	 //----INICIO HELP----//
	 /*
	 $local = $PHP_SELF;

		$sql = "SELECT * from tbl_help";
		$res = pg_exec ($con,$sql);

		if (@pg_numrows($res) >= 0) {
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$programa       = pg_result($res,$i,programa);
				$help           = pg_result($res,$i,help);

				$pos = strpos($local, $programa);
				if ($pos == true) {
//				echo"$programa<BR>";
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

	//----FIM HELP----//
	*/
	 ?>

	</td>







</tr>
<tr>
	<td colspan=3><div class="frm-on-os" id="displayArea">&nbsp;</div></td>
</tr>

<?
	if(strlen($msg_validade_cadastro)>0){
	echo "<tr>";
	echo "<td align='center' colspan=3>$msg_validade_cadastro</td>";
	echo "</tr>";

} ?>
</TABLE>



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

<?/*
	<table width='700'><tr><td align='left'><p>Em virtude dos feriados, estaremos atendendo  pelo Msn através do email <b><i>suporte@telecontrol.com.br</i></b>, email, e telefones Voip, nos numeros: <b>São Paulo:</b> (11) 4063-4230, <b>Curitiba:</b> (41) 4063-9872, <b>Florianópolis:</b> (48) 4052-8762,<b>Belo Horizonte:</b> (31) 4062-7401. E pelos celulares (14) 8141-1021 ou (14) 8122-2536.<p>Atenciosamente<br> Telecontrol Networking
	</td></tr></table>
*/?>

<?
#------------- Programa Restrito ------------------#
$sql = "SELECT * FROM tbl_admin WHERE admin = $login_admin AND privilegios NOT ILIKE '%*%' ";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) > 0) {
	$sql = "SELECT *
			FROM   tbl_programa_restrito
			WHERE  tbl_programa_restrito.programa = '$PHP_SELF'
			AND    tbl_programa_restrito.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$sql = "SELECT *
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



if ($login_fabrica==3 and $login_admin<>398 and 1==2) {
	echo "<center><br><br><br><br><br><font face='arial' size='2' color='#FF0000'><b>O sistema ficará fora do ar temporariamente para realização de uma atualização no método de geração do número da Ordem de Serviço automaticamente.<BR>Previsão de retorno às 09:00h.</b></font></center>";
		exit;
}

//echo "<!-- restricao \n $sql -->";
//include "monitora.php";
?>
