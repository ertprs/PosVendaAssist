<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

include_once 'anexaNF_inc.php';

if(isset($_POST["gravar_li_confirmo"])){

	$extrato = $_POST["extrato"];
	$nome    = "Li e Confirmo: ".utf8_decode($_POST["nome"]);

	$sql = "INSERT INTO tbl_extrato_status 
			(
			    extrato,
			    fabrica,
			    obs,
			    data,
			    pendente,
			    pendencia
			)
			VALUES 
			(
			    {$extrato},
			    {$login_fabrica},
			    '{$nome}',
			    current_timestamp,
			    true,
			    true
			)"; 
	$res = pg_query($con, $sql);

	exit(true);

}

$erro = "";
$tipo     = $_GET['tipo'];
$pendencia = $_GET['pendencia'];

//  Exclui a imagem da NF
if ($_POST['ajax'] == 'excluir_nf') {
	$img_nf = anti_injection($_POST['excluir_nf']);
	//$img_nf = basename($img_nf);

	$excluiu = (excluirNF($img_nf));
	$nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $img_nf);

	if ($login_fabrica == 42) {
		if ($excluiu)  $ret = "ok|" . temNFMakita($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
	}else{
		if ($excluiu)  $ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
	}	
	if (!$excluiu) $ret = 'ko|Não foi possível excluir o arquivo solicitado.';

	exit($ret);
}//	FIM	Excluir	imagem

if(strlen($pendencia)>0){
	$sql ="UPDATE tbl_extrato_status set confirmacao_pendente='t'
		WHERE extrato=$pendencia
		and pendente='t'";
	$res = pg_exec($con,$sql);
	$erro = pg_errormessage($con);
	if (strlen($erro) == 0){

		$xsql = "SELECT protocolo from tbl_extrato where extrato=$pendencia";
		$xres = pg_exec($con,$xsql);
		$xprotocolo = pg_result($xres,0,protocolo);

		$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
		$destinatario = "llaterza@blackedecker.com.br";
		$assunto      = "Pendência em extrato resolvido";
		$mensagem     = "A <BR>Blackedecker<BR><BR>
Minha pendência do extrato de número $xprotocolo foi resolvida, favor verificar.
<BR><BR>
PA $login_codigo_posto - $login_nome";
		$headers="Return-Path: <llaterza@blackedecker.com.br>\nFrom:".$remetente."\nBcc:takashi@telecontrol.com.br \nContent-type: text/html\n";

/*	if ( mail($destinatario,$assunto,$mensagem,$headers) ) {

	}else{
		echo "erro";
}*/
	}
	$extrato =$pendencia;
}

if ( isset($_POST['enviar']) ) {

	$fabrica = $login_fabrica < 10 ? 0 . $login_fabrica : $login_fabrica;

	$extrato = $_POST['extrato'];

	$sql = "SELECT TO_CHAR(data_geracao, 'YYYY_MM') as data_geracao
		FROM tbl_extrato
		WHERE extrato = $extrato
		AND posto = $login_posto";

	$res = pg_query($con,$sql);

	$data_geracao = pg_result($res,0,0);

	function getFile() {

		global $data_geracao, $extrato, $fabrica;

		$dir = "nf_digitalizada/$fabrica/$data_geracao";

		if ( !is_dir($dir) ) {

			mkdir($dir);
			chmod($dir, 0777);

		}

		return count( glob("$dir/e_$extrato*") );

	}

	$tipo = 'pendencia';
	$files = $_FILES['nf_servico'];

	if(empty($_REQUEST['extrato'])) {
		exit("Nenhum extrato encontrado");
	}

	$allow = array('application/pdf', 'application/msword', 'image/image', 'image/jpeg', 'image/pjpeg', 'image/gif', 'image/png', 'image/bitmap');

	try {

		$vazio = TRUE;

		// Apenas percorre arquivos anexados para verificar se tem algum, e se os que foram enviados sao permitidos
		foreach($files['type'] as $k => $type) {

			if (empty($type)) {
				continue;
			}

			$vazio = FALSE;

			if ( !in_array($type, $allow) ) {
				echo $type;
				throw new Exception('Arquivo ' . $files['name'][$k] . ' não permitido');

			}

		}

		if ($vazio === TRUE) {
			throw new Exception("Escolha pelo menos um arquivo");
		}

		// Percorre um a um para fazer o envio
		foreach($files['tmp_name'] as $k => $item) {

			if (empty($item)) {
				continue;
			}

			$upload = array(
				'name'     => $files['name'][$k],
				'type'     => $files['type'][$k],
				'tmp_name' => $files['tmp_name'][$k],
				'error'    => $files['error'][$k],
				'size'     => $files['size'][$k]
			);

			$anexou = anexaNF('e_' . $extrato, $upload, null, ($k) ? $k+1 : null);

			if ($anexou !== 0) { // 0 se anexou com sucesso...
				$erro_anexo = (is_int($anexou)) ? $msgs_erro[$anexou] : $anexou;
				throw New Exception($erro_anexo);
			}
		}

		$sql = "INSERT INTO tbl_extrato_status(
			extrato  ,
			fabrica  ,
			obs      ,
			data     ,
			pendente ,
			pendencia
		)
		VALUES(
			$extrato,
			$login_fabrica,
			'Aguardando envio para o financeiro',
			current_timestamp,
			'f',
			'f'
		);

		UPDATE tbl_extrato SET data_recebimento_nf = now() WHERE extrato = $extrato;";

		$res = pg_query($con,$sql);
		pg_close($con);
		header("Location:?extrato=$extrato&tipo=pendencia&msg=$msg");

	} catch (Exception $e) {

		$msg_erro = $e->getMessage();

		echo '<p style="color:red; text-align:center;">' . $msg_erro . '</p>';

	}

}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>

<title>Observação do Status do Extrato</title>

<style>
	input {
	border-right: #888888 1px solid;
	border-top: #888888 1px solid;
	font-weight: bold;
	font-size: 8pt;
	border-left: #888888 1px solid;
	border-bottom: #888888 1px solid;
	font-family: verdana;
	background-color: #f0f0f0
	}
	.erro {
	color: white;
	text-align: center;
	font: bold 12px Verdana, Arial, Helvetica, sans-serif;
	background-color: #FF0000;
	}
	.tabela {
	font-family: Verdana, Tahoma, Arial;
	font-size: 10px;
	text-align: center;
	}
	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	#divAnexaNF{
	background: #d9e2ef;
	}
	#adicionaNFServico {
	font-size: 11px;
	}
	#enviarNF {
	font-size:15px;
	text-align:center;
	}
	.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:530px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
	}
	.btn-li-confirmo{
		text-transform: uppercase;
		padding: 10px 30px; 
		background-color: #f9f9f9; 
		border: 1px solid #ccc;
		border-radius: 4px;
	}
	.box-li-confimo{
		margin: 0 auto;
		margin-top: 20px;
		margin-bottom: 20px;
		width: 400px;
		background: #f9f9f9;
		border: 1px solid #cccccc;
	}
	.box-li-confimo input, .box-li-confimo button{
		width: 300px;
	}
	.box-li-confimo button{
		background-color: #dddddd;
	}
	.box-li-confimo button:hover{
		transition: 0.4s;
		background-color: #cccccc;
		cursor: pointer;
	}
</style>
<link rel="stylesheet" href="css/css.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js"></script>
<script type="text/javascript" src="js/anexaNF_excluiAnexo.js"></script>

<script>
	
	function li_confirmo(){

		$(".box-li-confimo").toggle();

	}

	function gravar_li_confirmo(){

		var extrato = $("#extrato_li_confirmo").val();
		var nome    = $("#nome_li_confirmo").val().trim();

		if(extrato == ""){
			return;
		}

		if(nome == ""){
			alert("Por favor, insira o seu nome!");
			$("#nome_li_confirmo").focus();
			return;
		}

		$.ajax({
			url: "extrato_status_aprovado.php",
			type: "post",
			data: {
				gravar_li_confirmo: true,
				extrato: extrato,
				nome: nome
			},
			complete: function(data){

				data = data.responseText;

				console.log(data);

				if(data == true){

					$(".box-li-confimo").hide();
					$(".box-li-confimo-link").hide();

					alert("Li e Confirmo gravado com sucesso!");

					location.reload(); 

				}else{
					alert("Erro ao gravar o Li e Confirmo!");
				}

			}
		});

	}

</script>

</head>

<body>
<?
// CARREGA DADOS DO EXTRATO
$sql =	"SELECT TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao  ,
			TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS data_aprovado ,
			tbl_posto_fabrica.codigo_posto                 AS posto_codigo  ,
			tbl_posto.nome                                 AS posto_nome    ,
			tbl_extrato.protocolo                                           ,
			tbl_extrato.bloqueado
	FROM tbl_extrato
	JOIN tbl_posto          ON  tbl_posto.posto           = tbl_extrato.posto
	JOIN tbl_posto_fabrica  ON  tbl_extrato.posto         = tbl_posto_fabrica.posto
							AND tbl_posto_fabrica.fabrica = $login_fabrica
	WHERE tbl_extrato.extrato = $extrato;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
$data_geracao   = trim(pg_result($res,0,data_geracao));
$data_aprovado  = trim(pg_result($res,0,data_aprovado));
$posto_codigo   = trim(pg_result($res,0,posto_codigo));
$posto_nome     = trim(pg_result($res,0,posto_nome));
$protocolo      = trim(pg_result($res,0,protocolo));
$bloqueado      = trim(pg_result($res,0,bloqueado));
$posto_completo = $posto_codigo . " - " . $posto_nome;
}

if (strlen($erro) > 0) {
$obs = trim($_POST["obs"]);
echo "<div class='erro'>$erro</div>";
}

$verificaAdmin = "SELECT login, conferido
 				 FROM tbl_extrato_status
				 JOIN tbl_admin ON tbl_extrato_status.admin_conferiu = tbl_admin.admin
				 WHERE extrato = {$extrato} AND conferido is not null
 				AND ((obs = 'Conferido' AND conferido is not null)) ";
$resConferido = pg_query($con, $verificaAdmin);
$numRowsConferido  = pg_num_rows($resConferido);
if($numRowsConferido > 0){
    $conferido = true;
}
?>

<form name="frm_extrato" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="extrato" value="<?echo $extrato?>">
<input type="hidden" name="acao">

<table width='100%' border='0' cellspacing='1' cellpadding='1'  style='font-family: verdana; font-size: 12px'>

<tr>
<? if(in_array($login_fabrica, array(1,42))) { ?>
    <td id="td_conferido" >
<?     if($conferido) { ?>
           <b>Conferido</b>
<?     }
   }?>
	<td ><b>Extrato</b></td>
<td  width='50%' ><b>Data Geração</b></td>
</tr>
<tr>
    <td>&nbsp </td>
<td ><?php if($login_fabrica == 42){echo $extrato;}else{echo $protocolo;} ?></td>
<td width='50%'><?echo $data_geracao?></td>
</tr>
</table><BR>
<? if($bloqueado == "t"){?>

<table width='100%' border='0' cellspacing='1' cellpadding='1' bgcolor='#FF9E5E' style='font-family: verdana; font-size: 12px'>
<tr>
	<td align='center'><b>Extrato Bloqueado</b></td>
</tr>
</table>
<?
}



if($tipo=="pendencia"){
$xsql = "SELECT 	tbl_extrato_status.obs,to_char(tbl_extrato_status.data,'DD/MM/YYYY') as data ,
			tbl_extrato_status.pendente,
			tbl_extrato_status.confirmacao_pendente,
			tbl_extrato_status.extrato,
			tbl_extrato_status.arquivo
	FROM tbl_extrato_status
	WHERE extrato = $extrato and pendente notnull
	ORDER BY tbl_extrato_status.data DESC ";
$xres = pg_exec($con,$xsql);
//echo "$xsql";
if(pg_numrows($xres)>0){
?>
<table width='100%' border='0' cellspacing='1' cellpadding='5' style='font-family: verdana; font-size: 11px'  bgcolor='#596D9B'>
<tr>
<td width='80px' align='center'><font color='#FFFFFF'><b>Situação</b></FONT></td>
<td><font color='#FFFFFF'><b>Pendência</b></FONT></td>
</tr>
<?	for($x=0;pg_num_rows($xres)>$x;$x++){

	$xobs                  = pg_result($xres,$x,obs);
	$xobs = (mb_check_encoding($xobs, "UTF-8")) ? utf8_decode($xobs) : $xobs;
	$xdata                 = pg_result($xres,$x,data);
	$xpendente             = pg_result($xres,$x,pendente);
	$xconfirmacao_pendente = pg_result($xres,$x,confirmacao_pendente);
	$xextrato              = pg_result($xres,$x,extrato);
	$xarquivo              = pg_result($xres,$x,arquivo);

	if($xpendente=="t" and strlen($xconfirmacao_pendente)==0){$situacao = "Aguardando posto";}
	if($xpendente=="f" and $xconfirmacao_pendente=="f"){$situacao = "Resolvido";}
	if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
	if(strlen($xpendente)==0 and strlen($xconfirmacao_pendente)==0){$situacao = "Observação";}
	//if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
	$cor = "#d0e0f6";
	if ($x % 2 == 0) $cor = '#efeeea';

	echo "<tr bgcolor='$cor'>";
	echo "<td align='center'><font size='1'>$xdata<BR><B>";
		echo "$situacao";

	echo "</b></font></td>";
	echo "<td ><font size='2'>".nl2br($xobs)."</font></td>";
	echo "</tr>";
}

?>

</table>
<table width='100%' border='0' cellspacing='1' cellpadding='5' style='font-family: verdana; font-size: 11px'>
<tr  bgcolor='#596D9B'>
<td><font color='#FFFFFF'><b>Arquivo</b></FONT></td>
</tr>
<tr><?PHP
$dir = "admin/documentos/";

	?>
</tr>
</table>

<?  }
}

if($login_fabrica == 1){

	$sql_lc = "SELECT tbl_extrato_status.advertencia         
		FROM tbl_extrato_status
		WHERE tbl_extrato_status.extrato = $extrato
		AND fabrica = $login_fabrica
		AND (confirmacao_pendente IS NOT NULL OR pendente IS NOT NULL)
		ORDER BY data DESC
		LIMIT 1";
	$res_lc = pg_query($con, $sql_lc);

	if(pg_num_rows($res_lc) > 0){


		$advertencia = pg_fetch_result($res_lc, 0, 'advertencia');

		if ($advertencia == 't') {
		?>

			<br /> 

			<div class="box-li-confimo-link">
				<a href="javascript: li_confirmo();" class="btn-li-confirmo"> Li e Confirmo </a>
			</div>

			<div class="box-li-confimo" style="display: none;">
				<input type="hidden" name="extrato_li_confirmo" id="extrato_li_confirmo" value="<?php echo $extrato; ?>">
				<p>
					<strong>Informe seu nome para confirmação de leitura:</strong>
				</p>
				<p>
					<input type="text" class="frm" name="nome_li_confirmo" id="nome_li_confirmo" />
				</p>
				<p>
					<button type="button" class="frm" onclick="gravar_li_confirmo();"> Gravar </button>
				</p>
			</div>

			<br /> 

		<?php
		}

	}

}

if($tipo=="obs"){
	$xsql = "SELECT 	tbl_extrato_status.obs,
										tbl_extrato_status.pendente,
										tbl_extrato_status.confirmacao_pendente,
										tbl_extrato_status.extrato,
										tbl_extrato_status.arquivo
							FROM tbl_extrato_status
							WHERE extrato = $extrato and pendente is null and confirmacao_pendente is null";
	$xres = pg_exec($con,$xsql);
	//echo "$xsql";
	if(pg_numrows($xres)>0){
		?>
		<table width='100%' border='0' cellspacing='1' cellpadding='1' style='font-family: verdana; font-size: 12px'  bgcolor='#596D9B'>
			<tr>
				<td><font color='#FFFFFF'><b>Tipo</b></FONT></td>
				<td><font color='#FFFFFF'><b>Observação</b></FONT></td>
			</tr>
		<?
		for($x=0;pg_numrows($xres)>$x;$x++){
			$xobs                  = pg_result($xres,$x,obs);
			$xpendente             = pg_result($xres,$x,pendente);
			$xconfirmacao_pendente = pg_result($xres,$x,confirmacao_pendente);
			$xextrato              = pg_result($xres,$x,extrato);
			$xarquivo              = pg_result($xres,$x,arquivo);

			if($xpendente=="t" and strlen($xconfirmacao_pendente)==0){$situacao = "Aguardando posto";}
			if($xpendente=="f" and $xconfirmacao_pendente=="f"){$situacao = "Resolvido";}
			if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
			if(strlen($xpendente)==0 and strlen($xconfirmacao_pendente)==0){$situacao = "Observação";}
			//if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
			echo "<tr>";
			echo "<td bgcolor='#FFFFFF'><font size='2'>OBSERVAÇÃO</font></td>";
			echo "<td bgcolor='#FFFFFF'><font size='2'>$xobs</font></td>";
			echo "</tr>";
		}
		echo "</table>";
		//echo "<BR><a href=\"javascript: if (confirm('Confirmo que recebi do PA a pendência faltante') == true) { window.location='$PHP_SELF?pendencia=$extrato'; }\"><font size='1'>Baixar pendência</font></a>";
	}
}
?>
</center>
</form>
<?php
if (in_array($login_fabrica, array(1,42)) and !empty($extrato) ) {
	$sql = "SELECT posto
							FROM tbl_tipo_gera_extrato
							WHERE fabrica = $login_fabrica
								AND posto = $login_posto
								AND envio_online
								AND tipo_envio_nf = 'online_possui_nfe'";
	$res = pg_query($con,$sql);
	//echo $sql."<br>";

	$enviaNFServicos = (bool) pg_num_rows($res);
	
	$sql = "SELECT pendencia, pendente, advertencia
							FROM tbl_extrato_status
							WHERE extrato = $extrato
								AND fabrica = $login_fabrica
							ORDER BY data DESC
							LIMIT 1";
	//echo $sql;
	$res = pg_query($con,$sql);

	$li_confirmo = (bool) pg_num_rows($res);

	$pendencia = @pg_result($res, 0, 0);
	$pendente  = @pg_result($res, 0, 1);

	$advertencia = pg_fetch_result($res, 0, 'advertencia');

	$extratoPendente = ($pendencia == 't' && $pendente == 't') ? TRUE : FALSE;
}
?>
<div id="DIVanexos">
	<?
	if ($login_fabrica == 42) {
		if (temNFMakita("e_$extrato", 'bool')) {
			echo temNFMakita("e_$extrato", 'linkEx');
			echo $include_imgZoom;
		}
	}else{
		if (temNF("e_$extrato", 'bool')) {
			echo temNF("e_$extrato", 'linkEx');
			echo $include_imgZoom;
		}
	}	
	?>
</div>

<?php 
if (($enviaNFServicos && $tipo == 'pendencia' && $extratoPendente)|| ($login_fabrica == 42 && $tipo == 'pendencia') || ($login_fabrica == 1 && $advertencia != 't' && $enviaNFServicos)) { 
		$sql = "SELECT mao_de_obra,sum(valor), total
			FROM tbl_extrato
			LEFT JOIN tbl_extrato_lancamento On tbl_extrato.extrato = tbl_extrato_lancamento.extrato and lancamento = 47
			WHERE tbl_extrato.extrato = $extrato
			AND tbl_extrato.fabrica = $login_fabrica
			GROUP BY mao_de_obra, total";
		$res = pg_query($con, $sql);
		$avulso =pg_fetch_result($res,0,1) ;
		$avulso = (!empty($avulso))?$avulso:0;
		$sql = "SELECT sum(tbl_os.pecas * (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float -1))) 
					from tbl_os
					join tbl_os_extra using(os)
					join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and campos_adicionais ~'TxAdm'
					where tbl_os_extra.extrato = $extrato
					and tbl_os.pecas > 0
					and (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float)) > 0
		 ";
		$resX = pg_query($con, $sql);
		if(pg_num_rows($resX) > 0) {
			$totalTx = pg_fetch_result($resX,0, 0); 
		}else{
			$totalTx = 0 ; 
		}
		$total = number_format (pg_result($res, 0,0)+$avulso+$totalTx, 2, ',', '.');

		$total_extrato = pg_fetch_result($res, 0, 'total');

		//if(in_array($login_fabrica, array(1,42))){
		if(in_array($login_fabrica, array(1))){
			$sqlPostoReembolso = "SELECT reembolso_peca_estoque
											FROM tbl_posto_fabrica
											WHERE fabrica = $login_fabrica
											AND posto = $login_posto";
			$resPostoReembolso = pg_query($con, $sqlPostoReembolso);

			$posto_reembolso = pg_fetch_result($resPostoReembolso, '0', 'reembolso_peca_estoque');




			$msg_info = "(taxa administrativa + subtotal de mão-de-obra os).";

		}
	?>

	<form action="<?=$PHP_SELF?>" method="POST" enctype='multipart/form-data'>

	<?php
	if ($login_fabrica != 42) {?>
		<div class="texto_avulso">
			Confira se a sua NF foi emitida com o valor correto, que nesse caso é R$ <?=$total?> <?=$msg_info?>
		</div>
	<?php
	}
	?>
		<div id="divAnexaNF" class="tabela">

			<input type="hidden" name="extrato" value="<?echo $extrato?>">

			<p class="titulo_coluna">
				Anexar NF de Serviços
			</p>
			<?php
            if ($login_fabrica == 42) {?>
            	<table>
					<tr>
					    <td align="right">Nota Fiscal de Serviço:</td>
					    <td align="right">
					        <p id="addNF">
					            <input type="file" name="nf_servico[]" id="">
					        </p>
					    </td>
					</tr>
					<tr>
					    <td align="right">XML da Nota Fiscal de Serviço:</td>
					    <td align="right">
					        <p>
					            <input type="file" name="nf_servico[]" id="">
					        </p>
					    </td>
					</tr>
					<tr>
					    <td align="right">Nota Fiscal de Peças (DANFE):</td>
					    <td align="right">
					        <p>
					            <input type="file" name="nf_servico[]" id="">
					        </p>
					    </td>
					</tr>
					<tr>
					    <td align="right">XML Not Fiscal de Peças:</td>
					    <td align="right">
					        <p>
					            <input type="file" name="nf_servico[]" id="">
					        </p>
					    </td>
					</tr>
				</table>
            <?php
        	}else{?>
        		<p id="addNF">
					<input type="file" name="nf_servico[]" id="">
				</p>
				<p>
					<input type="file" name="nf_servico[]" id="">
				</p>
				<p>
					<input type="file" name="nf_servico[]" id="">
				</p>
				<p id="pAdicionaNFServico">
					<button id="adicionaNFServico">Adicionar mais arquivos</button>
				</p>
        	<?php
        	}
            ?>
			<div>
				<input type="submit" value="Enviar" name="enviar" id="enviarNF" />
				<center>
					<img id="loadingConf" src="imagens/grid/loading.gif" style="display: none; width: 22px; vertical-align: middle;" >
				</center>
			</div>

		</div>


	</form>

	<script type="text/javascript">
		$(function() {

			click = false;

			$("#adicionaNFServico").click(function(e) {
				e.preventDefault();
				$("#addNF").clone().prependTo("#pAdicionaNFServico");
			});

			$("#enviarNF").click(function(e) {

				$("#enviarNF").css("display","none");
				$("#loadingConf").css("display","block");

				if ( click === true ) {

					alert('Aguarde Submissão');
					e.preventDefault();
					return false;

				}

				click = true;

				return true;

			});

		});
	</script>
<?php } ?>
</body>

</html>
