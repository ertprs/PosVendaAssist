<?
if (!$_voip_teracell){
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';
}
/* TULIO PEDIU PARA DESABILITAR - FABIO - 03-09-2008 */
exit;


$sql = "SELECT posto,tipo_posto 
	FROM tbl_posto_fabrica 
	WHERE posto = $login_posto 
	AND fabrica = 49
	AND credenciamento <> 'DESCREDENCIADO'";
$res = @pg_exec ($con,$sql);
if(@pg_numrows($res)==0) {
	echo "<center><b>Seu status é inativo, não sendo possível utilizar este serviço</b></center>";
	exit;
}else{
	$voip_tipo_posto = pg_result($res,0,1);
	$sql = "SELECT dias,status
		FROM tbl_credenciamento 
		WHERE posto = $login_posto 
		AND fabrica = 49
		ORDER BY credenciamento DESC
		LIMIT 1;";
	$res = @pg_exec ($con,$sql);
	if(@pg_numrows($res)>0) {
		$c_dias   = pg_result($res,0,dias);
		$c_status = pg_result($res,0,status);
		if($c_status == 'CREDENCIADO') $c_dias  = "ILIMITADO";
		else                           $c_dias .= " dia(s)";
	}

}

$sql = "SELECT fone FROM tbl_posto WHERE posto = $login_posto";
$res = pg_exec ($con,$sql);
$fone_origem = pg_result ($res,0,fone);
$ddi_origem = "0055";
$ddd_origem = "";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">


<?
$btn_acao = trim($_POST['ligacao']);
if(strlen($btn_acao) > 0){

	$msg_erro = "";

	if($voip_tipo_posto==178){
		$ddi_destino      ='055';
		$telefone_destino = $fone_origem;
	}else{
		$ddi_destino          = trim($_POST['ddi_destino']);
		$ddd_destino          = trim($_POST['ddd_destino']);
		$telefone_destino     = trim($_POST['telefone_destino']);
	}

	$telefone_destino = str_replace('-','',$telefone_destino);
	$telefone_destino = str_replace('(','',$telefone_destino);
	$telefone_destino = str_replace(')','',$telefone_destino);
	$telefone_destino = str_replace(' ','',$telefone_destino);

	$contato      = trim($_POST['contato']);

	if($voip_tipo_posto==175) $telefone_origem = $fone_origem;
	else{
		$ddi_origem      = trim($_POST['ddi_origem']);
		$ddd_origem      = trim($_POST['ddd_origem']);
		$telefone_origem = trim($_POST['telefone_origem']);
	}

	$telefone_origem = str_replace('-','',$telefone_origem);
	$telefone_origem = str_replace('(','',$telefone_origem);
	$telefone_origem = str_replace(')','',$telefone_origem);
	$telefone_origem = str_replace(' ','',$telefone_origem);
	if (substr ($telefone_origem,0,1) == "0") $telefone_origem = substr ($telefone_origem,1);

	if($voip_tipo_posto <> 177){
		#----------- Valida Celulares -------------
		if (substr ($telefone_destino,0,1) == "7" OR substr ($telefone_destino,0,1) == "8" OR substr ($telefone_destino,0,1) == "9") {
			$msg_erro = "Número de celular não é válido nesta promoção";
		}
	
		if (substr ($telefone_origem,0,1) == "7" OR substr ($telefone_origem,0,1) == "8" OR substr ($telefone_origem,0,1) == "9") {
			$msg_erro = "Número de origem não pode ser celular";
		}
	}

	#----------- Escolha sequencial de VSIM -----------------
#	$vsim = "18005193267";
#	$vsim = "18006193057";
#	$vsim = "18008627468";

	$sql = "SELECT vsim_number FROM tbl_vsim ORDER BY qtde_ligacoes";
	$res = pg_exec ($con,$sql);
	$vsim = pg_result ($res,0,0);
	$sql = "UPDATE tbl_vsim SET qtde_ligacoes = qtde_ligacoes + 1 WHERE vsim_number = '$vsim'";
	$res = pg_exec ($con,$sql);

	$vsim_saida = rand (1,10);

	$conteudo = "Channel: SIP/$ddi_origem$ddd_origem$telefone_origem@$vsim
WaitTime: 45

Context: vsim-$vsim_saida
Extension: $ddi_destino$ddd_destino$telefone_destino
Priority: 1
";

	#Context: vsim-todas
	#Extension: $ddi_destino$ddd_destino$telefone_destino
	#echo "<br>$conteudo";


	if(strlen($msg_erro) == 0){
		$abrir = fopen("/tmp/voip/$login_posto.call", "w+");
		if (!fwrite($abrir, $conteudo)) {
			$msg_erro = "Erro escrevendo no arquivo ($filename)";
		}else{
			//if(strlen($contato) == 0){ $contato = 'null'; }
			$sql = "INSERT INTO tbl_agenda_voip(
						posto  ,
						ddi    ,
						ddd    ,
						fone   ,
						nome
				) VALUES (
						$posto  ,
						$ddi_destino        ,
						$ddd_destino        ,
						$telefone_destino   ,
						'$contato'
				);";
			//$res = pg_exec($con,$sql);
			//echo "$sql";
		}
		fclose($abrir); 

		system ("ncftpput -u voip -p asdasd123123 -S XXX spider.telecontrol.com.br / /tmp/voip/$login_posto.call");
		$discando = 't';

	}
}

?>

<!-- AQUI COMEÇA O HTML DO MENU -->
<head>

	<title></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<link type="text/css" rel="stylesheet" href="css/css.css">
</head>


<style>
body{padding:0px;margin:0px;}
.Titulo {
	text-align: center;
	font-family: Verdana,sans;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Verdana,sans;
	font-size: 10px;
	font-weight: none;
}
.ConteudoBranco {
	font-family: Verdana,sans;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Verdana,sans ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Verdana, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Verdana,sans ;
	font-weight: bold;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
</style>

<script type='text/javascript' src='jquery-latest.pack.js'></script>
<script type='text/javascript' src='jquery.autocomplete.js'></script>
<script type='text/javascript' src='jquery.bgiframe.js'></script>
<script type='text/javascript' src='jquery.dimensions.js'></script>
<link rel="stylesheet" type="text/css" href="jquery.autocomplete.css" />

	<script type="text/javascript">

		window.resizeTo(300,480);

		function carga(){
			var carga     = document.getElementById('load');
			carga.style.visibility = "visible";
			carga.innerHTML = "<b>&nbsp;&nbsp;Discando...&nbsp;&nbsp;</b>";
			setTimeout('esconde_carregar()',12000);
		}
		function esconde_carregar() {
			document.getElementById('load').style.visibility = "hidden";
		}

	</script>

</script>
<script language="JavaScript">
/*
$().ready(function() {
	function formatItem(row) {
	//	alert(row);
		return row[3] + " - " + row[4];
	}
	
	function formatResult(row) {
		return row[0];
	}
	

	$("#contato").autocomplete("autocomplete.php?busca=contato", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#contato").result(function(event, data, formatted) {
		$("#ddi"          ).val(data[0]) ;
		$("#ddd"          ).val(data[1]) ;
		$("#fone"         ).val(data[2]) ;
		$("#contato"      ).val(data[3]) ;
		$("#telefone"     ).val(data[2]) ;
		$("#posto"        ).val(data[5]) ;
	});


	$("#telefone").autocomplete("autocomplete.php?busca=telefone", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#telefone").result(function(event, data, formatted) {
		$("#ddi"          ).val(data[0]) ;
		$("#ddd"          ).val(data[1]) ;
		$("#fone"         ).val(data[2]) ;
		$("#contato"      ).val(data[3]) ;
		$("#telefone"     ).val(data[2]) ;
		$("#posto"        ).val(data[5]) ;
	});


});
*/
</script>



<?
/*
if($cor=="#F1F4FA")$cor = '#F7F5F0';
else               $cor = '#F1F4FA';
*/
?>

<body>
<table width='100%' bgcolor='#EFEFEF'>
	<tr>
		<td align='left'><font size=1>Vencimento: <b><?=$c_dias?></b></td>
	</tr>
</table>
<div  id="load" value='1' align='right' style='position: absolute;visibility:hidden;opacity:.90;z-index:1; overflow: auto;background-color: #ffeac0;border: #f29f3e 1px solid;' ></DIV>
<?if($discando=='t'){?>
<script>
carga();
</script>
<?}?>
<br>
<FORM name="frm_relatorio" METHOD="POST" ACTION="<? $PHP_SELF; ?>">

<table width='250' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo'>Tele-VoIP</td>
	</tr>
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'> 
			<INPUT TYPE="hidden" name='posto' id='posto' value='<? echo $posto ?>'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr class="Conteudo">
					<td colspan='3'  align='center' <? if(strlen($msg_erro)>0) echo "class='Erro'";?>><? if(strlen($msg_erro)>0) echo "$msg_erro";?>&nbsp;</td>
				</tr>

				<tr class="Conteudo">
						<?
						if($voip_tipo_posto == 175) echo "<td colspan='3'><b>Origem: $fone_origem</b></td>";
						else{
							echo "<tr class='Conteudo'>";
							echo "<td colspan='3'><b>Origem</b></td>";
							echo "</tr>";
							echo "<td>DDI<br><input type='text' name='ddi_origem' id='ddi_origem' size='5' maxlength='5' class='Caixa' value='";
							if(strlen($ddi_origem) > 0) echo $ddi_origem; else echo "0055";
							echo "' readonly>";
							echo "</td>";
							echo "<td>DDD<br>";
							echo "<input type='text' name='ddd_origem' id='ddd_origem' size='2' maxlength='2' class='Caixa' value='";
							if(strlen($ddd_origem) > 0) echo $ddd_origem; else echo "14";
							echo "' >";
							echo "</td>";
							echo "<td>Telefone<br>";
							echo "<input type='text' name='telefone_origem' id='telefone_origem' size='12' maxlength='20' class='Caixa' value='";
							if(strlen($telefone_origem) > 0) echo $telefone_origem;
							echo "' >";
							echo "</td>";
						}
						?>
				</tr>

				<tr class="Conteudo">
					<td colspan='3'><hr></td>
				</tr>
				<tr class="Conteudo">
					<?
					if($voip_tipo_posto == 178) echo "<td colspan='3'><b>Destino: $fone_origem</b></td>";
					else{
						echo "<tr class='Conteudo'>";
						echo "<td colspan='3'><b>Destino</b></td>";
						echo "</tr>";
						echo "<td>DDI<br>";
						echo "<input type='text' name='ddi_destino' id='ddi_destino' size='5' maxlength='5' class='Caixa' value='";
						if(strlen($ddi_destino) > 0) echo $ddi_destino; else echo "0055";
						echo "' readonly>";
						echo "</td>";
						echo "<td>DDD<br>";
						echo "<input type='text' name='ddd_destino' id='ddd_destino' size='2' maxlength='2' class='Caixa' value='";
						if(strlen($ddd_destino) > 0) echo $ddd_destino; else echo "14";
						echo "' >";
						echo "</td>";
						echo "<td>Telefone<br>";
						echo "<input type='text' name='telefone_destino' id='telefone_destino' size='12' maxlength='20' class='Caixa' value='";
						if(strlen($telefone_destino) > 0) echo $telefone_destino;
						echo "' >";
						echo "</td>";
					}
					?>
				</tr>
			</table><br>
			<center><input TYPE="submit" style="cursor:pointer" name='ligacao' value='Enviar' ></center>
		</td>
	</tr>
</table>
</FORM>
<br>

</body>
</html>