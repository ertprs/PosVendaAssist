<?
include "../../assist/www/dbconfig.php";
include "../../assist/www/includes/dbconnect-inc.php";
include "../../assist/www/funcoes.php";

$os_consulta   = $_POST['os'];

if(strlen($os_consulta)>0){
	$consulta_os = str_replace("-","",$os_consulta);
	$sql = "SELECT os,
			sua_os,
			os_numero,
			revenda_nome,
			TO_CHAR(data_abertura, 'DD/MM/YYYY') AS data_abertura,consumidor_nome,
			os_numero,
			data_conserto,
			descricao,
			finalizada
			from tbl_os
			join tbl_produto using(produto)
			where fabrica=81
			AND (os='$consulta_os' OR sua_os='$os_consulta') limit 1";
	$res = @pg_query($con,$sql);
	if(@pg_num_rows($res) > 0) {
		$os_resultado	= pg_fetch_result($res,0,os);
		$sua_os			= pg_fetch_result($res,0,sua_os);
		$os_numero		= pg_fetch_result($res,0,os_numero);
		$revenda_nome	= pg_fetch_result($res,0,revenda_nome);
		$data_abertura	= pg_fetch_result($res,0,data_abertura);
		$consumidor		= pg_fetch_result($res,0,consumidor_nome);
		$data_conserto	= pg_fetch_result($res,0,data_conserto);
		$descricao		= pg_fetch_result($res,0,descricao);
		$finalizada		= pg_fetch_result($res,0,finalizada);
	}else{
		$msg_erro = "<center><table><tr><td bgcolor='red'><font color=white size=4><b>OS Inexistente.</b></td></tr></table></center>";
	}
	$pelota="";
	if(empty($msg_erro)) {
		$sqlcor="SELECT *
					FROM tbl_os
					WHERE defeito_constatado is null
					AND	  solucao_os is null
					AND	  os=$os_resultado";
		$rescor=@pg_query($con,$sqlcor);
		if(@pg_num_rows($rescor) > 0) {
			$pelota="<img src='../../assist/imagens/status_vermelho' width='10' align='absmiddle'/>";
		} else {
			$sqlcor2 = "SELECT	DISTINCT tbl_os_item.pedido   ,
								tbl_os_item.peca					  ,
								tbl_pedido.distribuidor			 ,
								tbl_os_item.faturamento_item	   ";
			if(strlen($os_item)==0){
				$sqlcor2 .=", tbl_os_item.os_item ";
			}
			$sqlcor2 .=	"FROM	tbl_os_produto
						JOIN	tbl_os_item USING (os_produto)
						JOIN	tbl_produto USING (produto)
						JOIN	tbl_peca	USING (peca)
						LEFT JOIN tbl_defeito USING (defeito)
						LEFT JOIN tbl_servico_realizado USING (servico_realizado)
						LEFT JOIN tbl_os_item_nf	 ON tbl_os_item.os_item	  = tbl_os_item_nf.os_item
						LEFT JOIN tbl_pedido		 ON tbl_os_item.pedido	   = tbl_pedido.pedido
						LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
						LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
						WHERE   tbl_os_produto.os = $os_resultado";

			$rescor2 = @pg_query($con,$sqlcor2);
			if(@pg_num_rows($rescor2) > 0) {
				for ($j = 0 ; $j < pg_num_rows ($rescor2) ; $j++) {
					$pedido				= trim(pg_fetch_result($rescor2,$j,pedido));
					$peca				= trim(pg_fetch_result($rescor2,$j,peca));
					$distribuidor		= trim(pg_fetch_result($rescor2,$j,distribuidor));
					$faturamento_item	= trim(pg_fetch_result($rescor2,$j,faturamento_item));
					if(strlen($os_item) ==0)$os_item			  = trim(pg_fetch_result($rescor2,$j,os_item));

					if (strlen($pedido) > 0 and (($peca <> $peca_anterior and $pedido<>$pedido_anterior) or ($peca <> $peca_anterior and $pedido == $pedido_anterior))) {
							$sqlx = "SELECT nota_fiscal
							FROM (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
							JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
							JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item= tbl_faturamento_item.faturamento_item
							JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							AND tbl_faturamento.fabrica = 2
							WHERE    tbl_faturamento_item.peca = $peca";
							$resx = pg_query ($con,$sqlx);
							if (pg_num_rows ($resx) == 0) {
								$condicao_01 = " 1=1 ";
								if (strlen ($distribuidor) > 0) {
									$condicao_01 = " tbl_faturamento.distribuidor = $distribuidor ";
								}
								$sqlxx  = "SELECT *
										FROM	tbl_faturamento
										JOIN	tbl_faturamento_item USING (faturamento)
										WHERE   tbl_faturamento_item.pedido = $pedido
										AND	 tbl_faturamento_item.peca   = $peca
										AND	 $condicao_01 ";
								$resxx = pg_query ($con,$sqlxx);

								if (pg_num_rows ($resxx) == 0) {
									$pelota="<img src='../../assist/imagens/status_amarelo' width='10' align='absmiddle'/>";
								}else{
									$pelota="<img src='../../assist/imagens/status_rosa' width='10' align='absmiddle'/>";
								}
							}else{
								$pelota="<img src='../../assist/imagens/status_rosa' width='10' align='absmiddle'/>";
							}
						}else{
							$pelota="<img src='../../assist/imagens/status_amarelo' width='10' align='absmiddle'/>";
						}
					$os_anterior	 = $os;
					$peca_anterior   = $peca;
					$pedido_anterior = $pedido;
					$faturamento_anterior = $faturamento;
				}
			}else{
				$pelota="<img src='../../assist/imagens/status_amarelo' width='10' align='absmiddle'/>";
			}

			$sql="SELECT count(os_item) as conta_item
					FROM tbl_os
					JOIN tbl_os_produto USING(os)
					JOIN tbl_os_item using(os_produto)
					WHERE os=$os ";
			$resX = pg_query ($con,$sql);
			if(pg_fetch_result($resX,0,0) == 0){
				$pelota="<img src='../../assist/imagens/status_rosa' width='10' align='absmiddle'/>";
			}
		}

		if(strlen($data_conserto)>0 or strlen($finalizada) > 0){
			$pelota = "<img src='../../assist/imagens/status_azul' width='10' align='absmiddle'/>";
		}
	}
}

?>
<html>
	<head>
		<title>Consulta de OS</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<style type="text/css">
		#trx {
			font-weight: bold;
			color: #fff;
			background-color: #003366;
			padding: 2px 11px;
			text-align: left;
			border-right: 1px solid #fff;
			line-height: 1.2;
		}
		#try{
			color: #000;
			background-color: #fff;
			padding: 2px 11px;
			text-align: left;
			border-bottom: 2px solid #95bce2;
			line-height: 1.2;
		}
		#tdx {
			padding: 1px 11px;
			border-left: 1px solid #95bce2;
			border-top: 1px solid #95bce2;
			border-right: 1px solid #95bce2;
			border-bottom: 1px solid #95bce2;
		}
		BODY {
			PADDING-RIGHT: 0px; PADDING-LEFT: 0px;
			SCROLLBAR-ARROW-COLOR: #000000; PADDING-TOP: 0px; SCROLLBAR-BASE-COLOR:#E1E1E1
		}

		A:link {
		COLOR: #444875
		}
		A:visited {
			COLOR: #444875
		}
		A:hover {
			COLOR: #AABC44
		}
		.menu:link {
			COLOR: #ffffff; TEXT-DECORATION: none
		}
		.menu:visited {
			COLOR: #ffffff; TEXT-DECORATION: none
		}
		.menu:hover {
			COLOR: #ffffff; TEXT-DECORATION: underline
		}
		.baixo:link {
			COLOR: #000000; TEXT-DECORATION: none
		}
		.baixo:visited {
			COLOR: #000000; TEXT-DECORATION: none
		}
		.baixo:hover {
			COLOR: #000000; TEXT-DECORATION: underline
		}


		#todoform input {	
		background:#ffffff;	
		border:1px solid #c0c0c0;	
		}
		#todoform textarea {
		border:1px solid #c0c0c0;
		background:#ffffff;	
		scrollbar-arrow-color:#000000;	
		scrollbar-3dlight-color:#000000;	
		scrollbar-highlight-color:#ffffff;	
		scrollbar-face-color:#34587A;	
		scrollbar-shadow-color:#000000;	
		scrollbar-darkshadow-color:#c0c0c0;	
		scrollbar-track-color:#F6F8FA;	
		}
		#todoform input.botao {	
		background:#B1B8C1;	
		color:#000000;
		font:Tahoma;
		border:1px solid #000000;
		}	

		#todoform1 input {	
			background-color:#536A44;	
			border:1px solid #78B351;	
		}
		#todoform1 select {	
			background:#c0c0c0;	
			border:1px solid #000000;
			font:12px arial, helvetica, sans-serif;
			color:#ffffff;	
		}
		#todoform1 textarea {
			border:1px solid #c0c0c0;
			background:#ffffff;
			font:12px arial, helvetica, sans-serif;
			color:#003399;	
			scrollbar-arrow-color:#000000;	
			scrollbar-3dlight-color:#000000;	
			scrollbar-highlight-color:#ffffff;	
			scrollbar-face-color:#c0c0c0;	
			scrollbar-shadow-color:#000000;	
			scrollbar-darkshadow-color:#c0c0c0;	
			scrollbar-track-color:#F6F8FA;	
		}

		#todoform2 input {	
			background-color:#000000;	
			border:1px solid #000000;	
		}
		#todoform2 select {	
			background:#c0c0c0;	
			border:1px solid #000000;
			font:12px arial, helvetica, sans-serif;
			color:#ffffff;	
		}
		#todoform2 textarea {
			border:1px solid #c0c0c0;
			background:#ffffff;
			font:12px arial, helvetica, sans-serif;
			color:#003399;	
			scrollbar-arrow-color:#000000;	
			scrollbar-3dlight-color:#000000;	
			scrollbar-highlight-color:#ffffff;	
			scrollbar-face-color:#c0c0c0;	
			scrollbar-shadow-color:#000000;	
			scrollbar-darkshadow-color:#c0c0c0;	
			scrollbar-track-color:#F6F8FA;	
		}

	</style>
	<script src="js/AC_RunActiveContent.js" type="text/javascript"></script>
	
	<body background="imagens/plano.png" bgcolor="#000000" topmargin="0" leftmargin="0">
<center>
<table border="0" cellpadding="0" cellspacing="0" width="1000">
    <tr>
        <td align="center"><font color="#000000"><script language="javascript">
	if (AC_FL_RunContent == 0) {
		alert("This page requires AC_RunActiveContent.js.");
	} else {
		AC_FL_RunContent(
			'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0',
			'width', '1000',
			'height', '123',
			'src', 'js/menucima1',
			'quality', 'best',
			'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
			'align', 'middle',
			'play', 'true',
			'loop', 'true',
			'scale', 'showall',
			'wmode', 'transparent',
			'devicefont', 'false',
			'id', 'menucima1',
			'bgcolor', '#000000',
			'name', 'menucima1',
			'menu', 'false',
			'allowFullScreen', 'false',
			'allowScriptAccess','sameDomain',
			'movie', 'js/menucima1',
			'salign', ''
			); //end AC code
	}
</script>
<noscript>
	<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="1000" height="123" id="menucima1" align="middle">
	<param name="allowScriptAccess" value="sameDomain" />
	<param name="allowFullScreen" value="false" />
	<param name="movie" value="js/menucima1.swf" /><param name="menu" value="false" /><param name="quality" value="best" /><param name="wmode" value="transparent" /><param name="bgcolor" value="#000000" />	<embed src="js/menucima1.swf" menu="false" quality="best" wmode="transparent" bgcolor="#000000" width="1000" height="123" name="menucima1" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	</object>
</noscript></font></td>
    </tr>
</table>
	<div align="center"><center>

	<table border="0" cellpadding="0" cellspacing="0" width="770">
		<tr>
			<td>
				<br>
				<center>
				<?echo (strlen($msg_erro) > 0) ?$msg_erro :  "";?>
				<form name='busca_os' method='POST' action='<?=$PHP_SELF?>'>
					<p><font color="#444875" style="font-size: 16px; font-family: arial,verdana,arial,helvetica;"><b>Número da OS</p></b></font>
					<input name='os' type='text' value='<?=$os?>'>
					<input type=submit name='enviar' value='Consultar'>
				</form>
				</center>
			</td>
		</tr>
	</table>

	</center></div>

	<? if(strlen($os_resultado) > 0) { ?>
		<div style='position: relative; center: 10'>
		<center>
		<table border='0' cellspacing='0' cellpadding='0'>
			<tr height='18'>
				<td align='left'>
					<img src='../../assist/imagens/status_vermelho' width='10' align='absmiddle'/>
					<font size='3'> Aguardando Análise</font>
				</td>
			</tr>
			<tr height='18'>
				<td align='left'>
					<img src='../../assist/imagens/status_amarelo' width='10' align='absmiddle'/>
					<font size='3'> Aguardando Peça</font>
				</td>
			</tr>
			<tr height='18'>
				<td align='left'>
					<img src='../../assist/imagens/status_rosa' width='10' align='absmiddle'/>
					<font size='3'> Aguardando Conserto</font>
				</td>
			</tr>
			<tr height='18'>
				<td align='left'>
					<img src='../../assist/imagens/status_azul' width='10' align='absmiddle'/>
					<font size='3'> Consertado</font>
				</td>
			</tr>
		</table>
		<br><br>
		<table width='700'>
			<tr id='trx'>
				<td align='center'>OS</td>
				<td align='center'>Data Abertura</td>
				<td align='center'>Produto</td>
				<td align='center'>Consumidor/Revenda</td>
			</tr>
			<tr id='try'>
				<td id='tdx' align='center' nowrap><? echo $pelota." ".$os;?></td>
				<td id='tdx' align='center'><?=$data_abertura?></td>
				<td id='tdx' align='center'><?=$descricao?></td>
				<td id='tdx' align='center'>
				<?
					if(strlen($consumidor)>0) echo $consumidor;
					elseif(strlen($revenda_nome)>0) echo $revenda_nome;
					elseif(strlen($revenda_nome)>0 AND strlen($consumidor)>0) echo $consumidor."/".$revenda_nome;
					else echo "";
				?>
				</td>
			</tr>
		</table>
		<p style='style-align:left'>CASO NÃO CONSIGA ESCLARECER SUAS DÚVIDAS, LIGUE PARA O NOSSO SAC<br>
			3366 9166 (CAPITAL E GRANDE SÃO PAULO)<br>
			0800 770 1699 (DEMAIS LOCALIDADES OU MANDE UM E-MAIL <a href="mailto:sac@dynacom.com.br">SAC@DYNACOM.COM.BR</a>)<br>
			Atendimento de seg. a sex. das 9:00 às 16:00 horas</p>
		</center>
		</div>
		<? } ?>

	<table border="0" cellpadding="0" cellspacing="0" width="1000">
    <tr>
        <td align="center"><font color="#FFFFFF"><script language="javascript">
	if (AC_FL_RunContent == 0) {
		alert("This page requires AC_RunActiveContent.js.");
	} else {
		AC_FL_RunContent(
			'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0',
			'width', '1000',
			'height', '44',
			'src', 'js/menucima3',
			'quality', 'best',
			'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
			'align', 'middle',
			'play', 'true',
			'loop', 'true',
			'scale', 'showall',
			'wmode', 'transparent',
			'devicefont', 'false',
			'id', 'menucima3',
			'bgcolor', '#FFFFFF',
			'name', 'menucima3',
			'menu', 'true',
			'allowFullScreen', 'false',
			'allowScriptAccess','sameDomain',
			'movie', 'js/menucima3',
			'salign', ''
			); //end AC code
	}
</script>
<noscript>
	<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="1000" height="44" id="menucima3" align="middle">
	<param name="allowScriptAccess" value="sameDomain" />
	<param name="allowFullScreen" value="false" />
	<param name="movie" value="js/menucima3.swf" /><param name="quality" value="best" /><param name="wmode" value="transparent" /><param name="bgcolor" value="#FFFFFF" />	<embed src="js/menucima3.swf" quality="best" wmode="transparent" bgcolor="#FFFFFF" width="1000" height="44" name="menucima3" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	</object>
</noscript></font></td>
    </tr>
</table>
<table border="0" cellpadding="0" cellspacing="0"  width="1000" height="30">
    <tr align="center"><td width="300" background="imagens/fd2.png"><font color="#FFFFFF"></font></td>
        <td width="100" bgcolor="#FFFFFF" ><font color="#FFFFFF">.........</font></td>
        <td width="300" background="imagens/fd2.png"><font color="#FFFFFF"></font></td>
        <td align="right" width="300" bgcolor="#FFFFFF" ><font color="#FFFFFF"><FONT style="FONT-SIZE: 10px; FONT-FAMILY: arial,tahoma,verdana,helvetica" color="#c0c0c0">

Copyright © Marca Dynacom - 2009<br>
E-mail: mkt@dynacom.com.br<br>
Site melhor visualizado em resolução de 1024 x 768 pixels

<br>

</font>
</font></td>
    </tr>
</table>

	</body>
</html>
