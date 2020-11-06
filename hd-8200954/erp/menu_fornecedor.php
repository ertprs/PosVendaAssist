<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");                // Data no passado
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");   // Sempre modificado
header("Cache-Control: no-store, no-cache, must-revalidate");    // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");   // HTTP/1.0

########################################################################
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

$micro_time_start = getmicrotime();

########################################################################


//--=========== AQUI COMEÇA O NOVO MENU - RAPHAEL GIOVANINI V ===========--\\
?>
<html>
<head>
<title><?=$title;?></title>
<link type="text/css" rel="stylesheet" href="css/estilo.css">
<link type="text/css" rel="stylesheet" href="css/css.css">
<link type="text/css" rel="stylesheet" href="SpryMenuBarHorizontal.css">

<script type="text/javascript" src="jquery/jquery-latest.pack.js"></script>
<script type="text/javascript" src="jquery/thickbox.js"></script>
<link rel="stylesheet" href="jquery/thickbox.css" type="text/css" media="screen" />
<script src="jquery/jquery.history_remote.pack.js" type="text/javascript"></script>
<script src="jquery/jquery.tabs.pack.js" type="text/javascript"></script>

<link rel="stylesheet" href="jquery/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="jquery/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->

<script src="jquery/jquery.form.js" type="text/javascript" language="javascript"></script>

<script src="jquery/jquery.corner.js" type="text/javascript" language="javascript"></script>

<script src="jquery/jquery.shadow.js" type="text/javascript" language="javascript"></script>

<script src="jquery/jquery.autocomplete.js" type="text/javascript" language="javascript"></script>
<link rel="stylesheet" type="text/css" href="jquery/jquery.autocomplete.css" />

<script src="jquery/jquery.MultiFile.js" type="text/javascript" language="javascript"></script>        
<script src="jquery/jquery.MetaData.js" type="text/javascript" language="javascript"></script>
<!-- <script src="jquery/jtip.js" type="text/javascript"></script> -->
<!--<script type="text/javascript" src="jquery/jquery-tooltipdemo.js"></script>-->
<!--<script type="text/javascript" src="jquery/jquery.atalho.js"></script>-->
<script src="jquery/jquery.maskedinput.js" type="text/javascript"></script>

<!-- retirei Fabio
<script src="jquery/jquery.dimensions.js" type="text/javascript"></script>
<script src="jquery/jquery.cluetip.js" type="text/javascript"></script>
-->

<script language='javascript' src='SpryMenuBar.js'></script>

<script type="text/javascript" src="jquery/jquery.focusfields.pack.js"></script>
<script type="text/javascript" src="jquery/parsesamples.js"></script>
<script type="text/javascript">
$(
	function()
	{
		parseSamples();
		$("input.Caixa, textarea.Caixa, input.CaixaValor,").focusFields();
	}
)
</script>
<!--
<script language='javascript'>

$(document).shortkeys({
	/*'F2':       function ()  { document.getElementById('LinkAjuda').click(); },*/
/*	'F12+C':       function () { window.location="cadastro.php?tipo=cliente#tab2Cadastrar";             },
	'F12+O':       function () { window.location="orcamento_cadastro.php?tipo_orcamento=orca_venda";    },
	'F12+V':       function () { window.location="orcamento_cadastro.php?tipo_orcamento=venda";         },
	'F11':       function () { window.location="orcamento_cadastro.php?tipo_orcamento=fora_garantia"; },
	'F12+I':            function () { window.location="menu_inicial.php"; }
*/
/*  'M':       function () { $('#try_me').append('M<br />'); },
  'Space':   function () { $('#try_me').append('Space<br />'); },
  'Space+V': function () { $('#try_me').append('Space+V<br />'); },
  'V':       function () { $('#try_me').append('V<br />'); },
  't+y':     function () { $('#try_me').append('T+Y<br />'); },
  't+u':     function () { $('#try_me').append('T+U<br />'); }*/
});
</script>
-->
</head>
<body>

<table width='100%' border='0' cellspacing='0' align='center' cellpadding='0'>
	<tr height='25' >
		<td bgcolor='#B90000' width='160' align='center'><font color='#FFFFFF' size='1'><? echo "<b>$nome_empresa</b> ";?></font></td>
		<td  bgcolor='#4F94CD' align='left' ><font color='#FFFFFF' size='1'>&nbsp;<?echo "Fornecedor: <b>$login_fornecedor_nome</b> | Email: <b>$login_fornecedor_email</b> ";?> | <a href='http://www.telecontrol.com.br' style='color:#FFFFFF'>Sair</font></a>
		</td>
	</tr>
</table>

<br><h1 style='color:#0000FF'>Sistema de Cotação On-Line</h1><br>


<?

echo "<center>";

?>