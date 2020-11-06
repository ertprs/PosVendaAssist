<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include '../token_cookie.php';

$token_cookie = $_COOKIE['sess'];

$cookie_login = get_cookie_login($token_cookie);

//include 'autentica_admin.php';

/*
18/3/2009 -	Adicionado AJAX para envio de cartão, altera o botão para informar
			que foi enviado, mas se atualizar a página essa informação se perde
		  - Quando tiver um aniversário no sábado ou no domingo, habilita também
		    o botão para enviar e-mail. Falta definir se deveria enviar uma
		    outra imagem...

??/4/2009 - Salva a informação de para quais usuários já foi enviado e-mail numa 'cookie'
			com validade  de 1 dia, assim cada vez  que carrega a página o botão 'enviar'
			vai ficar inhabilitado e com o texto de "Enviado!" para não ter possibilidade
			de confusão...
*/

//---------------------------------------------------------
//  Envia o e-mail de parabéns (AJAX)
if ($_POST['ajax']=="sim") {
	if ($_POST['btn_enviar']=="") {
//	Prepara o e-mail se é que não foi enviado
		$destino = '"'.$_POST['nome'].'" <'. $_POST['email'] .">";
		$assunto = "Parabéns! - Equipe Telecontrol";
		$mensagem= "<HTML>
		<HEAD>
			<META http-equiv='Content-Type' content='text/html; charset=windows-1250'>
			<TITLE>Parab&eacute;ns!</TITLE>
		</HEAD>
		<BODY>
			<IMG src='http://www.telecontrol.com.br/parabens.jpg'></IMG>
		</BODY>
		</HTML>";
		$meuemail = "MIME-Version: 1.0\r\n";
		$meuemail.= "Content-type: text/html; charset=iso-8859-1\r\n";
		$meuemail.= "From: Equipe Telecontrol <diretoria@telecontrol.com.br>\r\n";
		$meuemail.= "Bcc: \"Manuel Lopez\" <manolo@telecontrol.com.br>\r\n";
		$meuemail.= "X-Mailer: Assist Telecontrol\r\n";

		$enviado = mail($destino, utf8_encode($assunto), utf8_encode($mensagem), $meuemail);
		$msg = iif(($enviado),  "OK|O e-mail para $nome foi enviado com sucesso!",
								"KO|ATENÇÃO: o e-mail para $nome NÃO foi enviado!");
		if ($enviado) {
		    list($dia,$mes,$ano) = explode("-",date("d-m-y"));
			setcookie("email[".$_POST['email']."]","enviado",mktime(23,59,59, $mes, $dia, $ano));
		}
		echo $msg;
		exit;
	}
	echo "KO|Informação não válida";
	exit;
}
//---------------------------------------------------------
$dias	= array(0 => "Domingo",		"Segunda-feira","Terça-feira", "Quarta-feira",
					 "Quinta-feira","Sexta-feira",	"Sábado");
$meses	= array(1 => "Janeiro", "Fevereiro","Mar&ccedil;o",	"Abril",
					 "Maio", 	"Junho",    "Julho",		"Agosto",
					 "Setembro","Outubro",	"Novembro",		"Dezembro");
//---------------------------------------------------------
function iif($condition, $val_true, $val_false = "") {
	if (is_numeric($val_true) and is_null($val_false)) $val_false = 0;
	if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) return null;
	return ($condition) ? $val_true : $val_false;
}

function is_between($valor,$min,$max) {
	return iif(($valor >= $min and $valor <= $max),true,false);
}// Fim is_between

function parse_data($data,$formato='date') {
// $data é no formato "YYMMDD".
//  Formato 'date' é formato interno PHP de data (mktime...)
	if ($formato=="date")
		return mktime(0,0,0, substr($data,2,2), substr($data,-2), substr($data,0,2));
//  Formato 'string' simplesmente passa de YYMMDD para DD/MM/YY
	if ($formato=="string")
	    return substr($data,-2)."/".substr($data,2,2)."/".substr($data,0,2);
}// Fim parse_data

function e_hoje($data) {
// Devolve 'true' se $data é hoje, 'false' se não é ou $data não é uma data
	$e_hoje	= iif((date("d/m/y") == parse_data($data,"string")),true,false);

//  Para esta tela, se a data cai em sábado ou domingo e 'hoje' for quinta-feira,
// 	considerar que é hoje também
	$diasemd= date("w",parse_data($data));
	$semdata= date("W",parse_data($data));   // Semana do ano da data
	$semhoje= date("W");                            // Semana atual
	if (!$e_hoje and $semdata==$semhoje and
		(!is_between($diasemd,1,5) and date("w")>=5)) $e_hoje=true;
	return $e_hoje;
}

//---------------------------------------------------------
//  Atualiza as cookies de hoje se o valor for diferente:
$sql = "SELECT count(dia_nascimento) FROM tbl_login_unico WHERE ativo IS TRUE";
$res = pg_query($con,$sql);
$lus = pg_fetch_result($res,0,count);

$sql	= "SELECT count(dia_nascimento) FROM tbl_admin WHERE ativo IS TRUE";
$res	= pg_query($con,$sql);
$admins	= pg_fetch_result($res,0,count);
$hoje	= date("ymd");

$cook_adm_hoje = "relatorio_admin_".$hoje;
$cook_lu_hoje  = "relatorio_lu_".$hoje;

if (strlen($cookie_login[$cook_adm_hoje])==0 or intval($cookie_login[$cook_adm_hoje])<$admins) {
		setcookie($cook_adm_hoje,$admins,mktime(23,59,59, 12, 31, 2014));
		$reset=true;
}

if (strlen($cookie_login[$cook_lu_hoje])==0 or intval($cookie_login[$cook_lu_hoje])<$lus) {
		setcookie($cook_lu_hoje,$lus,mktime(23,59,59, 12, 31, 2014));
		$reset=true;
}

// if ($reset) header("Location: $PHP_SELF");  // Recarrega a página se foram alteradas as cookies

//---------------------------------------------------------
// 	Conta os postos que já informaram do tipo de acesso a internet
$sql	= "SELECT count(DISTINCT tbl_posto.posto) FROM tbl_posto JOIN tbl_posto_fabrica USING (posto) ".
		  "WHERE tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'";
$total	= @pg_fetch_result(@pg_query($con,$sql),0,count);

$sql	= "SELECT count(posto) FROM tbl_posto WHERE velocidade_internet IS NOT NULL";
$velo	= @pg_fetch_result(@pg_query($con,$sql),0,count);

$sql	= "SELECT count(posto) FROM tbl_posto WHERE velocidade_internet LIKE 'Discad_'";
$velo_d = @pg_fetch_result(@pg_query($con,$sql),0,count);

$sql	= "SELECT count(posto) FROM tbl_posto WHERE velocidade_internet='Banda Larga'";
$velo_bl= @pg_fetch_result(@pg_query($con,$sql),0,count);

$percent= number_format($velo/$total*100,2)  ." %";
$perc_d = number_format($velo_d/$velo*100,2) ." %";
$perc_bl= number_format($velo_bl/$velo*100,2)." %";
//---------------------------------------------------------
?>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=windows-1252">
	<meta name="generator" content="PSPad editor, www.pspad.com">
	<title>Relat&oacute;rio de atualiza&ccedil;&atilde;o de data de anivers&aacute;rio</title>
	<!--[if lt IE 8]>
		<script src="http://ie7-js.googlecode.com/svn/trunk/lib/IE8.js" type="text/javascript"></script>
	<![endif]-->
    <STYLE type="text/css">
    <!--
	body {
		color: black;
		font-family: Verdana, Arial, Sans Serif;
		font-size: 9pt;
		padding: 0 2px;
		margin:3px;
	}

	h1 {
		background-color: #404B64;
		/*	Experimental, webkit-only */
		background-image: -webkit-gradient(linear, left top, left bottom, from(#242946), to(#CACFD8));
		background-image: -moz-linear-gradient(top, #242946, #CACFD8);
		-pie-background:  linear-gradient(top, #242946, #CACFD8);
		/*	filter: progid:DXImageTransform.Microsoft.Gradient(GradientType="0",StartColorStr=#242946,EndColorStr=#CACFD8);*/
		border: 0px solid transparent;
		/* Bordes redondeados, *SOLO CSS3* */
	    border-radius: 8px;
	    	-moz-border-radius: 8px;
		color: #DADFE8;
		font-size: 1.4em;
		font-weight: bold;
		width: 70%;
		line-height: 2em;
		text-align: center;
		vertical-align: middle;
	}

	table {
	    width: 70%;
		padding: 0;
		margin-top: 0;
		border: 2px solid #9ac0ff;
        border-collapse: separate;
        background-color: white;
		/* Bordes redondeados, *SOLO CSS3* */
	    border-radius: 5px;
	    	-moz-border-radius: 5px;
		font-size: 9pt;
	}
	tr {
		border-width: 0 1px;
		height: 1.5em;
	}
	td {
		text-align: right;
		padding-right: 1em;
		white-space: nowrap;
		overflow: hidden;
	}
	thead th, tr.subhead td, td.subhead,td.data { /* Cabeçalho da tabela e primeira coluna */
		background-color: #9ac0ff;
		/*  Fundo degradê para Chrome/Safari (Webkit) e MSIE 5+ */
		background-image: -webkit-gradient(linear, left top, left bottom, from(#9ac0ff), to(#DEF));
		background-image: -moz-linear-gradient(top, #9ac0ff, #ddeeff);
		-pie-background:  linear-gradient(top, #9ac0ff, #ddeeff);
		/*	filter: progid:DXImageTransform.Microsoft.Gradient(GradientType="0",StartColorStr=#9ac0ff,EndColorStr=#ddeeff);*/
		font-weight: bold;
		text-align: center;
		padding: 0;
	}
	tr:nth-child(even) {background-color:#F5F5FF}
	tr.aniv {
		background-color: yellow;
		color: green;
		font-weight: bold;
	}
	tr.emailok {
		background-color: lightgreen;
		color: #090;
		font-weight: bold;
	}
	tr.w_aniv {
		background-color: #FFE0B0;
		color: #FF9000;
		font-weight: bold;
	}
	tr.passou {
		/*background-color: white;*/
		color: #999;
		font-style: italic;
		font-weight: normal;
	}
	.botao {
		cursor: pointer;
		background-color: #EEE;
		color: #333!important;
		padding: 0 0.7ex;
		margin:0;
		/* Bordes redondeados, *SOLO CSS3* */
	    border-radius: 4px;
	    	-moz-border-radius: 4px;
		border: 2px outset #AAA;
	}
	.botao:hover {	/* Para navegadores no-IE	*/
		border: 2px inset #666;
	}

	form {margin:0;padding:0;border:0}

	div.footer {
		position:fixed;
		bottom:0;
		left:20px;
		width:95%;
		height: 15px;
		background-color: ivory;
		color:#AAA;
		border-top:1px dashed #999;
		border-bottom:1px dotted #CCC;
		text-align:center;
		z-index: 10;
	}

	a,a:link,a:visited,a:active {text-decoration:none}
-->
	</style>
	<script type="text/javascript">
	function SetAjax() {
		var xmlhttp;
		if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
			try {
				xmlhttp = new XMLHttpRequest();
			} catch (e) {
				xmlhttp = false;
			}
		}
		return xmlhttp;
	}

	function enviar(frm) {
	var ajax = new SetAjax(); // Cria um novo objeto HTTPRequest
		if (ajax == null) {
			alert ("Seu navegador não aceita AJAX!");
			return;
		}
		if (frm.btn_enviar.value.substr(0,7) != "Enviado") {
			var post;
			var rnd = Math.random();
			    rnd = rnd.toPrecision(8);
			    rnd = rnd.toString();
			    rnd = rnd.substr(2,8);
			frm.btn_enviar.innerHTML = "Enviando...";
			post = "ajax=sim";
			post = post + "&btn_enviar="; // Tive q tirar porque o ·$%&·!/* do IE não sabe a diferença entre o 'value' do BUTTON e o innerHTML... +frm.btn_enviar.value;
			post = post + "&nome=" + frm.nome.value;
			post = post + "&email="+ frm.email.value;
			post = post + "&rand=" + rnd;
			var url = '<?=$PHP_SELF?>';
			ajax.open("POST",url,true);
			ajax.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			ajax.send(post);
			ajax.onreadystatechange=function() {
				if (ajax.readyState == 4 && ajax.status == 200) {
					var res = Array(1);
					var r = ajax.responseText;
					res = r.split("|",2);
					var estado = res[0];
					var msg = res[1];
					if (estado=="OK") {
						frm.btn_enviar.value = "Enviado";
						frm.btn_enviar.innerHTML = "Enviado!";
					}
					if (estado=="KO") {
						frm.btn_enviar.value = "";
						frm.btn_enviar.innerHTML = "Enviar cart&atilde;o";
					}
				}
			}
		} else {
			alert ('O e-mail para '+frm.nome.value+ " já foi enviado!");
		}
	}
	</script>
</head>
<body>
<center>
<h1>Usu&aacute;rios do Sistema que j&aacute; cadastraram seu anivers&aacute;rio</h1>
<br>
<table style='margin-bottom: 1em;' id='totales'>
	<thead>
	<tr class='SubHead'>
	    <td style='background-color:white'></th>
	    <td colspan='2' width='40%'>Usu&aacute;rios no dia</th>
	    <td colspan='2' width='40%'>Total Usu&aacute;rios</th>
	</tr>
	<tr class='SubHead'>
		<th>DATA</th>
		<th>ADMIN</th>
		<th>Login &Uacute;nico</th>
		<th>ADMIN</th>
		<th>Login &Uacute;nico</th>
	</tr>
	</thead>
<?
/************************************************************
 * Formato das cookies:                     				*
 * nome: relatorio_admin_YYMMDD								*
 *       Corresponde à quantidade de ADMIN com aniversário  *
 *       atualizado até o dia YYMMDD						*
 * nome: relatorio_lu_YYMMDD mesma coisa, mas para os users	*
 *       do Login Único                     				*
 * valor:quantidade de usuários até "hoje".                 *
 * 		 Este valor vai se atualizando dentro do mesmo dia  *
 * 		 (mesma cookie)										*
 *															*
 * Depois o programa calcula a diferença, para saber        *
 * quantos se cadastraram no dia 							*
 ***********************************************************/
$a = 0;
$l = 0;
foreach ($cookie_login as $key=>$value) {
	if (substr_count($key, "relatorio_admin")>0) {
//echo $key." = ".$value."<br>";
		$a_anivs_admin[$a++]= substr($key, -6).",".$value;
	}
	if (substr_count($key, "relatorio_lu")>0) {
//echo $key." = ".$value."<br>";
		$a_anivs_lu[$l++]	= substr($key, -6).",".$value;
	}
}
$admin_anterior = 0;
$lu_anterior    = 0;
for ($i=0;$i<$a;$i++) {
	list ($data_admin,$valor_admin) = explode(",",$a_anivs_admin[$i]);
	list ($data_lu	 ,$valor_lu)	= explode(",",$a_anivs_lu[$i]);
	echo "\t<TR>";
	echo "\t\t<TD class='data'>".parse_data($data_admin,"string")."</TD>\n";
	echo "\t\t<TD>".($valor_admin - $admin_anterior)."</TD>\n";
	echo "\t\t<TD>".($valor_lu - $lu_anterior)."</TD>\n";
	echo "\t\t<TD>".$valor_admin."</TD>\n";
	echo "\t\t<TD>".$valor_lu."</TD>\n";
	echo "\t</TR>";
	$admin_anterior	= $valor_admin;
	$lu_anterior    = $valor_lu;
}
//---------------------------------------------------------
//	Total de usuários não usando
//---------------------------------------------------------
$totadm = @pg_fetch_result(@pg_query($con,"SELECT count(admin)       FROM tbl_admin       WHERE ativo IS TRUE"),0,count);
$totlu	= @pg_fetch_result(@pg_query($con,"SELECT count(login_unico) FROM tbl_login_unico WHERE ativo IS TRUE"),0,count);
?>	<tr>
		<td class='data' colspan='3' style='text-align:right;padding-right:1em'
			title='Este valor não está contando os usuários inativos'>
			Totais de usu&aacute;rios (ativos) ADMIN e LOGIN &Uacute;NICO</td>
		<td style='color: navy;font-weight:bold'><?=$totadm?></td>
		<td style='color: navy;font-weight:bold'><?=$totlu ?></td>
	</tr>

	<tr>
		<td class='data' colspan='3' style='text-align:right;padding-right: 1em'
			title='Este valor não está contando os usuários inativos'>
			Usu&aacute;rios que ainda n&atilde;o preencheram a data de anivers&aacute;rio
		</td>
		<td style='color: #900;font-weight:bold'
			title='Percentual: <?=str_pad((number_format((($totadm-$admins)/$totadm)*100,2)),2," ",STR_PAD_BOTH) ?>%'>
			<?=($totadm - $admins)?>
		</td>
		<td style='color: #900;font-weight:bold'
			title='Percentual: <?=str_pad((number_format((($totlu-$lus)/$totlu)*100,2)),2," ",STR_PAD_BOTH) ?>%'>
			<?=($totlu  - $lus)?>
		</td>
	</tr>
</table>
</center>
<p align='center'>Aperte a tecla <span class='botao' title='ou clique aqui...' onClick='window.location="<?=$PHP_SELF?>";'>F5</span> para atualizar.</p>
<br>
<?
//---------------------------------------------------------
//  Usuários que já informaram do tipo de acesso
//---------------------------------------------------------?>
<table align='center' id='velocidade'>
	<tr>
		<td class='SubHead' style='text-align:right;padding-right: 1em'>
			Usu&aacute;rios que j&aacute; informaram do tipo de acesso à internet</td>
		<td style='color: #009;font-weight:bold'><?=$velo?></td>
		<td style='color: #009;font-weight:bold'
			title='De um total de <?=$total?> postos.<?="\n"?> (Total de postos que estão credenciados com algum fabricante...)'><?=$percent?></td>
	</tr>
	<tr>
		<td class='SubHead' style='text-align:right;padding-right: 1em'>
			Usu&aacute;rios que acessam usando internet discada</td>
		<td style='color: #900;font-weight:bold'><?=$velo_d?></td>
		<td style='color: #900;font-weight:bold'><?=$perc_d?></td>
	</tr>
	<tr>
		<td class='SubHead' style='text-align:right;padding-right: 1em'>
			Usu&aacute;rios que acessam usando internet banda larga</td>
		<td style='color: #060;font-weight:bold'><?=$velo_bl?></td>
		<td style='color: #060;font-weight:bold'><?=$perc_bl?></td>
	</tr>
</table>
<br>
<?
//---------------------------------------------------------
//  Tabela com os aniversarianes do mês escolhido
//---------------------------------------------------------
$mes_atual	= iif((!isset($_GET['mes_atual'])), intval(date("m")), $_GET['mes_atual']);
$mes_ant	= iif(($mes_atual>1),$mes_atual-1,12);   // Se for janeiro, passa para dezembro
$mes_sig	= iif(($mes_atual<12),$mes_atual+1,1);   // Se for dezembro, passa para janeiro

$data_amanha    = parse_data(date("ymd",date('Y-m-d') + strtotime('+1 day')));
$data_next_week	= parse_data(date("ymd",date('Y-m-d') + strtotime('+1 week')));
$data_hoje      = parse_data(date("ymd"));
?>
<table align='center' id='anivs'>
	<caption style='font-weight:bold;margin-bottom: 5px'>
		<br>Aniversariantes do m&ecirc;s de
		<a id='ant' class='botao' title='Consultar <?=$meses[$mes_ant]?>'
		 href='<?echo "$PHP_SELF?mes_atual=$mes_ant";?>'>&laquo;</a>
		&nbsp;<?=$meses[$mes_atual]?>&nbsp;
		<a id='sig' class='botao' title='Consultar <?=$meses[$mes_sig]?>'
		 href='<?echo "$PHP_SELF?mes_atual=$mes_sig";?>'>&raquo;</a>
	</caption>
<?
//---------------------------------------------------------
//  Usuários ADMIN
//---------------------------------------------------------
$sql	= "SELECT (nome_completo||' (da <B>'||initcap(lower(tbl_fabrica.nome))||'</B>)') AS nome,
					email,LPAD(dia_nascimento::text,2,'0') AS dia,LPAD(mes_nascimento::text,2,'0') AS mes
						FROM tbl_admin JOIN tbl_fabrica USING (fabrica)
					WHERE mes_nascimento = $mes_atual AND ativo IS TRUE
					ORDER BY tbl_fabrica.nome,dia_nascimento, nome_completo";
$res	= pg_query($con,$sql);
$anivs	= pg_fetch_all($res);   //  Salva tudo de uma vez num array
$total_admin_mes = pg_num_rows($res);
?>
	<thead>
	<tr>
		<th colspan='3'>Administradores (<?=$total_admin_mes?>)</th>
	</tr>
	<tr>
		<th width='65%'>Nome do usu&aacute;rio</th>
		<th width='25%'>Data de anivers&aacute;rio</th>
		<th width='10%'>A&ccedil;&atilde;o</th>
	</tr>
	</thead>
<?
foreach ($anivs as $key=>$value) {
	$tipo_aniv      = "";
	unset ($btn_enviar,$btn_enviar_texto);
	$data_aniv		= $anivs[$key]['dia']." de ".$meses[intval($anivs[$key]['mes'])];
	$data_aniv_ano  = date("y").$anivs[$key]['mes'].$anivs[$key]['dia'];
	$data_aniv_dia  = date("w",parse_data($data_aniv_ano));
	$data_aniv_dia  = "Cai ".iif((is_between($data_aniv_dia,1,5)),"na ","no ").$dias[$data_aniv_dia];

	if (is_between(parse_data($data_aniv_ano),$data_amanha,$data_next_week)) $tipo_aniv = "w_aniv";
	if (e_hoje($data_aniv_ano)) $tipo_aniv = "aniv";
	if (parse_data($data_aniv_ano)<$data_hoje) $tipo_aniv = "passou";

	if ($tipo_aniv == "aniv") {
	    $email = $anivs[$key]['email'];
	    $btn_enviar = "";
	    $btn_enviar_texto = "Enviar cart&atilde;o";
		if (isset($cookie_login['email'])) {
			if (strtolower($cookie_login['email'][$email])=="enviado") {
				$btn_enviar = "Enviado";
				$btn_enviar_texto = "Enviado!";
				$tipo_aniv = "emailok";
			}
		}
	}
	if ($tipo_aniv != "") $tipo_aniv = " class='$tipo_aniv'";
?>
	<tr<?=$tipo_aniv?>>
		<td title='<? echo $anivs[$key]['email'] . "'>" . $anivs[$key]['nome']; ?></TD>
		<TD title='<?=$data_aniv_dia?>'>
<?		if (!is_between(date("w",parse_data($data_aniv_ano)),1,5)) {
			echo $dias[date("w",parse_data($data_aniv_ano))].", ";
		}
		echo $anivs[$key]['dia']." de ".$meses[intval($anivs[$key]['mes'])]?></td>
		<td style='text-align: center;padding: 4px 1ex'>
		<?
		if (strlen($btn_enviar_texto) > 8) { // Gera o botão se precisar
			$nome_admin = substr($anivs[$key]['nome'],0,strpos($anivs[$key]['nome'], "(")-1);?>
		<form action="<?=$PHP_SELF?>" name="adm_mail_<?=$key?>" method="POST">
		    <input type='hidden' name='email'	  	value='<?=$anivs[$key]['email']?>'>
		    <input type='hidden' name='nome'	  	value='<?=$nome_admin?>'>
			<button type='button' name='btn_enviar' value='<?=$btn_enviar?>' class='botao'
			    onClick="javascript: enviar(document.adm_mail_<?=$key?>,this);">
				<?=$btn_enviar_texto?></button>
		</form>
		<?} else if ($btn_enviar_texto == "Enviado!") {
		echo $btn_enviar_texto;
		}?>
		</td>
	</tr>
<?}
//---------------------------------------------------------
//  Usuários do Login Único
//---------------------------------------------------------
$sql	= "SELECT nome,email,LPAD(dia_nascimento::text,2,'0') AS dia,
                             LPAD(mes_nascimento::text,2,'0') AS mes
                    FROM tbl_login_unico
               WHERE mes_nascimento = $mes_atual AND ativo IS TRUE
               ORDER BY dia_nascimento";
$res	= pg_query($con,$sql);
$anivs	= pg_fetch_all($res);
$total_lu_mes = pg_num_rows($res);
?>
	<tr class='SubHead'>
		<td colspan='3'>Usu&aacute;rios do login &uacute;nico (<?=$total_lu_mes?>)</td>
	</tr>
		<tr class='SubHead'>
		<td width='65%'>Nome do usu&aacute;rio</td>
		<td width='25%'>Data de anivers&aacute;rio</td>
		<td width='10%'>A&ccedil;&atilde;o</td>
	</tr>
<?
foreach ($anivs as $key=>$value) {
	$data_aniv		= $anivs[$key]['dia']." de ".$meses[intval($anivs[$key]['mes'])];
	$data_aniv_ano  = date("y").$anivs[$key]['mes'].$anivs[$key]['dia'];
	$data_aniv_dia  = date("w",parse_data($data_aniv_ano));
	$data_aniv_dia  = "Cai ".iif((is_between($data_aniv_dia,1,5)),"na ","no ").$dias[$data_aniv_dia];

	$tipo_aniv = "";
	if (is_between(parse_data($data_aniv_ano),$data_amanha,$data_next_week)) $tipo_aniv = " class='w_aniv'";
	if (parse_data($data_aniv_ano)<$data_hoje) $tipo_aniv = " class='passou'";
	if (e_hoje($data_aniv_ano)) $tipo_aniv = " class='aniv'";
?>
	<tr<?=$tipo_aniv?>>
		<td title='<?=$anivs[$key]['email']?>'><?=$anivs[$key]['nome']?></td>
		<td title='<?=$data_aniv_dia?>'>
		<?if (!is_between(date("w",parse_data($data_aniv_ano)),1,5)) {
			echo $dias[date("w",parse_data($data_aniv_ano))].", ";
		  }
			echo $data_aniv?></td>
		<td style='text-align: center;padding: 4px 1ex'>
		<? if (e_hoje($data_aniv_ano)) {
		    $email = $anivs[$key]['email'];
		    $btn_enviar		= "";
		    $btn_enviar_texto= "Enviar cart&atilde;o";
			if (isset($cookie_login['email'])) {
				if (strtolower($cookie_login['email'][$email])=="enviado") {
					$btn_enviar = "Enviado";
					$btn_enviar_texto = "Enviado!";
				}
			}
			 ?>
		<form action="<?=$PHP_SELF?>" name="lu_mail_<?=$key?>" method="POST">
		    <input  type='hidden' name='email'		value='<?=$anivs[$key]['email']?>'>
		    <input  type='hidden' name='nome'		value='<?=$anivs[$key]['nome']?>'>
			<button type='button' name='btn_enviar' value='<?=$btn_enviar?>' class='botao'
			     onClick="javascript: enviar(document.lu_mail_<?=$key?>,this);">
				<?=$btn_enviar_texto?></button>
		</form>
		<?}?>
		</td>
	</tr>
<?}?>
</table>
<br><br>
<div class='footer'>
    <b>ATEN&Ccedil;&Atilde;O:</b>
    Este relat&oacute;rio tem que ser executado pelo menos uma vez por dia, no mesmo computador,
    para obter resultados di&aacute;rios.
</div>
</body>
</html>
