<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_usuario.php";
	include "funcoes.php";

if ($sistema_lingua) $idioma = strtolower($sistema_lingua);

$os_orcamento = $_REQUEST['os_orcamento'];
$btn_acao	     = $_POST['btn_acao'];

if($btn_acao=="gravar"){
	
	$consumidor_nome		= $_REQUEST['consumidor_nome'];
	$consumidor_fone			= $_REQUEST['consumidor_fone'];
	$consumidor_email		= $_REQUEST['consumidor_email'];
	$produto_referencia		= $_REQUEST['produto_referencia'];
	$produto_descricao		= $_REQUEST['produto_descricao'];
	$voltagem				= $_REQUEST['voltagem'];
	$abertura				= $_REQUEST['abertura'];
	$fechamento			= $_REQUEST['fechamento'];
	$orcamento_envio		= $_REQUEST['orcamento_envio'];
	$orcamento_aprovacao		= $_REQUEST['orcamento_aprovacao'];
	$orcamento_reprovado		= $_REQUEST['orcamento_reprovado'];
	$conserto				= $_REQUEST['conserto'];
	$orcamento_aprovado		= $_REQUEST['orcamento_aprovado'];

	//Valida Campos
	if(strlen($consumidor_nome) == 0){
		$msg_erro = traduz("consumidor.nome.invalido", $con, $cook_idioma);
	}else{
        $xconsumidor_nome = "'" . $consumidor_nome . "'";
    }

	if(strlen($consumidor_fone) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = traduz("consumidor.fone.invalido", $con, $cook_idioma);
	}else{
        $xconsumidor_fone = "'" . $consumidor_fone . "'";
    }

	if(strlen($produto_referencia) == 0 AND strlen($msg_erro) == 0){
		$msg_erro = traduz("informe.o.produto", $con, $cook_idioma);
	}elseif(strlen($msg_erro) == 0){
		$sqlP = "	SELECT 
					produto
				FROM 
					tbl_produto
					JOIN tbl_linha USING(linha)
				WHERE 
					referencia = '$produto_referencia'
					AND fabrica = $login_fabrica";
		$resP = pg_exec($con,$sqlP);
		if(pg_numrows($resP)>0) 
			$produto  = pg_result($resP,0,produto);
		else
			$msg_erro = traduz("produto.nao.encontrado", $con, $cook_idioma);
	}

	if(strlen($abertura)==0 AND strlen($msg_erro) == 0){
		$msg_erro = traduz("informe.data.abertura", $con, $cook_idioma);
	}else{
		$xabertura = fnc_formata_data_hora_pg(trim($abertura));
	}

	if($orcamento_reprovado=="t"){
		$xorcamento_reprovado = "t";
		$xorcamento_aprovado = "f";
	}else {
		$xorcamento_reprovado = "f";
		$xorcamento_aprovado = "t";
	}

	/*
	if(strlen($orcamento_envio) > 0 AND $xorcamento_reprovado=="f" AND  strlen($msg_erro) == 0){
		
		if(strlen($orcamento_aprovacao)==0) 
			$msg_erro = traduz("aprovacao.orcamento.invalido", $con, $cook_idioma);

		//if(strlen($conserto)==0 AND strlen($msg_erro) == 0) 
		//	$msg_erro = traduz("termino.conserto.invalido", $con, $cook_idioma);
	}
	*/

	if(strlen($orcamento_envio)==0){
		$xorcamento_envio = "null";
		//$msg_erro = "Envio do Orçamento Inválido";
	}else 
		$xorcamento_envio = fnc_formata_data_hora_pg(trim($orcamento_envio));

	if(strlen($orcamento_aprovacao)==0) 
		$xorcamento_aprovacao = "null";
	else
		$xorcamento_aprovacao = fnc_formata_data_hora_pg(trim($orcamento_aprovacao));
/*
	if($orcamento_aprovado=="t")
		$xorcamento_aprovado = "t";
	else 
		$xorcamento_aprovado = "f";
*/
	if(strlen($fechamento)==0)
		$xfechamento = "null";
	else
		$xfechamento = fnc_formata_data_hora_pg(trim($fechamento));

	if(strlen($conserto)==0)
		$xconserto = "null";
	else
		$xconserto = fnc_formata_data_hora_pg(trim($conserto));

	if(strlen($consumidor_email)==0)
		$xconsumidor_email = "null";
	else
		$xconsumidor_email = "'" . $consumidor_email . "'";

	if(strlen($xfechamento)>0 AND $xfechamento<>"null"){
		$sql = "SELECT $xfechamento > CURRENT_TIMESTAMP AS data_maior";
		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro)==0){
			$data_maior = pg_result($res,0,data_maior);

			if ($data_maior == 't'){
				$msg_erro = traduz("data.fechamento.nao.pode.ser.maior.atual", $con, $cook_idioma);
			}
		}
	}

	if(strlen($msg_erro)==0){
		if($xorcamento_aprovado == 'f'){
			$xconserto				= "null";
			//$xorcamento_envio		= "null";
			$xorcamento_aprovacao	= "null";
		}

		if(strlen($os_orcamento) == 0){
			$sql = "INSERT INTO tbl_os_orcamento(
					data_digitacao ,
					posto               ,
					produto             ,
					abertura            ,
					orcamento_envio     ,
					orcamento_aprovacao ,
					orcamento_aprovado  ,
					conserto            ,
					fechamento          ,
					consumidor_nome     ,
					consumidor_fone     ,
					consumidor_email	,
					fabrica		
				)VALUES(
					CURRENT_TIMESTAMP	,
					$login_posto          ,
					$produto              ,
					$xabertura            ,
					$xorcamento_envio     ,
					$xorcamento_aprovacao ,
					'$xorcamento_aprovado',
					$xconserto            ,
					$xfechamento          ,
					$xconsumidor_nome     ,
					$xconsumidor_fone     ,
					$xconsumidor_email	,
					$login_fabrica
				) RETURNING os_orcamento;";
		}else{
			$sql = "UPDATE tbl_os_orcamento SET 
					produto = $produto						,
					abertura = $xabertura						,
					orcamento_envio = $xorcamento_envio			,
					orcamento_aprovacao = $xorcamento_aprovacao	,
					orcamento_aprovado = '$xorcamento_aprovado'		,
					conserto = $xconserto						,
					fechamento = $xfechamento					,
					consumidor_nome = $xconsumidor_nome			,
					consumidor_fone = $xconsumidor_fone			,
					consumidor_email = $xconsumidor_email
				WHERE 
					os_orcamento = $os_orcamento;";
		}

		$res		= @pg_query ($con,$sql) ;
		$msg_erro_db	= pg_errormessage($con);

		if(strpos($msg_erro_db, "out of range")){
			$msg_erro = traduz("data.hora.esta.incorreta", $con, $cook_idioma);
		}
		if(strpos($msg_erro_db, "data_futura")){
			$msg_erro = traduz("data.entrada.nao.pode.ser.maior.que.data.hora.atual", $con, $cook_idioma);
		}
		if(strpos($msg_erro_db, "data_futura_fechamento")){
			$msg_erro = traduz("data.fechamento.nao.pode.menor.que.data.conserto", $con, $cook_idioma);
		}
		if(strpos($msg_erro_db, "data_futura_orcamento_envio")){
			$msg_erro = traduz("data.envio.nao.pode.ser.menor.que.data.abertura", $con, $cook_idioma);
		}
		if(strpos($msg_erro_db, "data_futura_orcamento_aprovacao")){
			$msg_erro = traduz("data.envio.nao.pode.ser.mario.que.data.aprovacao", $con, $cook_idioma);
		}
		if(strpos($msg_erro_db, "data_futura_conserto")){
			$msg_erro = traduz("data.conserto.nao.pode.ser.menor.que.data.aprovacao", $con, $cook_idioma);
		}
		if(strlen($msg_erro)==0){
			if(strlen($os_orcamento) == 0)
				$os_orcamento = pg_result($res,0,os_orcamento);

			header ("Location: $PHP_SELF");
			exit;
		}
	}
}

if(strlen($os_orcamento) > 0 && strlen($btn_acao) == 0){
	$sql = "	SELECT 
				tbl_os_orcamento.consumidor_fone	,
				tbl_os_orcamento.consumidor_nome	,
				tbl_os_orcamento.consumidor_email		,
				tbl_produto.referencia				,
				tbl_produto.descricao				,
				tbl_produto.voltagem				,
				tbl_os_orcamento.abertura			,
				tbl_os_orcamento.orcamento_envio		,
				tbl_os_orcamento.orcamento_aprovacao	,
				tbl_os_orcamento.orcamento_aprovado	,
				tbl_os_orcamento.conserto
			FROM 
				tbl_os_orcamento 
				JOIN tbl_produto ON (tbl_produto.produto = tbl_os_orcamento.produto)
			WHERE 
				posto = $login_posto
				AND fabrica = $login_fabrica 
				AND os_orcamento = $os_orcamento";
	$res = pg_exec($con, $sql);

	if(pg_numrows($res) == 1){
		$consumidor_nome	= pg_fetch_result($res,0,consumidor_nome);
		$consumidor_fone		= pg_fetch_result($res,0,consumidor_fone);
		$consumidor_email	= pg_fetch_result($res,0,consumidor_email);
		$produto_referencia	= pg_fetch_result($res,0,referencia);
		$produto_descricao	= pg_fetch_result($res,0,descricao);
		$voltagem			= pg_fetch_result($res,0,voltagem);
		$abertura			= mostra_data_hora(pg_fetch_result($res,0,abertura));
		$orcamento_envio	= mostra_data_hora(pg_fetch_result($res,0,orcamento_envio));
		$orcamento_aprovacao	= mostra_data_hora(pg_fetch_result($res,0,orcamento_aprovacao));
		$orcamento_aprovado	= pg_fetch_result($res,0,orcamento_aprovado);
		$conserto			= mostra_data_hora(pg_fetch_result($res,0,conserto));

		$xorcamento_reprovado = ($orcamento_aprovado == 'f') ? 't' : 'f';
	}else{
		$msg_erro = traduz("nenhum.orcamento.encontrado", $con, $cook_idioma);
	}
}

$title = traduz("telecontrol.assistencia.tecnica.os.fora.garantia", $con, $cook_idioma); //ttext($a_trad_orc, "page_title", $idioma);

$layout_menu = 'os';
include "cabecalho.php";
?>

<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
<!-- JQuery -->
<script type="text/javascript" src="js/jquery.js"></script>

<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>

<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<!-- Ajax TULIO -->
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<? include "javascript_pesquisas.php"; ?>
<script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
		Shadowbox.init();
    });

	function pesquisaProduto(campo, tipo){
		var campo	= jQuery.trim(campo.value);

		if (campo.length > 2){   
			Shadowbox.open({
				content	:	"pesquisa_produto_nv.php?"+tipo+"="+campo,
				player	:	"iframe",
				title	:	"<?php fecho('pesquisa.de.produto', $con, $cook_idioma);?>",
				width	:	800,
				height	:	500
			});
		}else
			alert("<?php fecho('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
	}

	function retorna_produto(produto,referencia,descricao, posicao, voltagem){
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_descricao",descricao);
        gravaDados("voltagem",voltagem);
	}

	function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

function gravaEnvio (orcamento_envio, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?orcamento_envio=' + escape(orcamento_envio) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function gravaAprovacao (orcamento_aprovacao, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?orcamento_aprovacao=' + escape(orcamento_aprovacao) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function gravaConserto (conserto, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?conserto=' + escape(conserto) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function gravaFechamento (fechamento, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?fechamento=' + escape(fechamento) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function gravaAprovado (orcamento_aprovado, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?orcamento_aprovado=' + escape(orcamento_aprovado) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function retornaCampos (campos) {
	if (campos.indexOf ('<erro>') >= 0) {
		campos = campos.substring (campos.indexOf('<erro>')+6,campos.length);
		campos = campos.substring (0,campos.indexOf('</erro>'));
		campos_array = campos.split("|");

		msg_erro = campos_array[0];
		if (msg_erro.indexOf('data_futura_orcamento_aprovacao') > 0) {
		    msg_erro = "<?=traduz('data.envio.nao.pode.ser.mario.que.data.aprovacao', $con, $cook_idioma)?>";
		}
		if (msg_erro.indexOf('data_futura_orcamento_envio') > 0) {
		    msg_erro = "<?=traduz('data.envio.nao.pode.ser.menor.que.data.abertura', $con, $cook_idioma)?>";
		}
		if (msg_erro.indexOf('data_futura_conserto') > 0) {
		    msg_erro = "<?=traduz('data.conserto.nao.pode.ser.menor.que.data.aprovacao', $con, $cook_idioma)?>";
		}
		if (msg_erro.indexOf('data_futura_fechamento') > 0) {
		    msg_erro = "<?=traduz('data.fechamento.nao.pode.menor.que.data.conserto', $con, $cook_idioma)?>";
		}
		if (msg_erro.indexOf('out of range') > 0) {
			msg_erro = "<?=traduz('data.hora.esta.incorreta', $con, $cook_idioma)?>";
		}
		if (msg_erro.indexOf('data_futura') > 0) {
			msg_erro = "<?=traduz('data.entrada.nao.pode.ser.maior.que.data.hora.atual', $con, $cook_idioma)?>";
		}
		alert (msg_erro) ;
		linha = campos_array[1] ;
		campo = campos_array[2] ;

		document.getElementById(campo + "_" + linha).val();
		document.getElementById(campo + "_" + linha).focus() ;
	}
}
</script>

<? //include "javascript_calendario.php"; ?>

<!-- Formatar DATA -->
<script type="text/javascript" charset="utf-8">
	$(function(){
		$("input[@rel='data']").maskedinput("99/99/9999 99:99");
		$("input[@rel='fone']").maskedinput("(99)9999-9999");

		verficaCampoData();
	});

	function verficaCampoData(){
		var abertura			 = $("input[name='abertura']").val().replace('/','').replace(':','').replace('_','').replace(' ','');
		var orcamento_envio		 = $("input[name='orcamento_envio']").val().replace('/','').replace(':','').replace('_','').replace(' ','');
		var orcamento_aprovacao	 = $("input[name='orcamento_aprovacao']").val().replace('/','').replace(':','').replace('_','').replace(' ','');
		var conserto			 = $("input[name='conserto']").val().replace('/','').replace(':','').replace('_','').replace(' ','');
		var orcamento_reprovado	 = $("input[name='orcamento_reprovado']").attr('checked');;

		if(orcamento_reprovado){
			//$("input[name='orcamento_envio']").val("");
			$("input[name='orcamento_aprovacao']").val("");
			$("input[name='conserto']").val("");

			//$("input[name='orcamento_envio']").attr("disabled", true);
			$("input[name='orcamento_aprovacao']").attr("disabled", true);
			$("input[name='conserto']").attr("disabled", true);
		}

		if(abertura.length == 0){
			$("input[name='orcamento_envio']").val("");
			$("input[name='orcamento_aprovacao']").val("");
			$("input[name='conserto']").val("");

			$("input[name='orcamento_envio']").attr("disabled", true);
			$("input[name='orcamento_aprovacao']").attr("disabled", true);
			$("input[name='conserto']").attr("disabled", true);

			return false;
		}else{
			$("input[name='orcamento_envio']").attr("disabled", false);
			//$("input[name='orcamento_aprovacao']").attr("disabled", true);
			//$("input[name='conserto']").attr("disabled", true);
		}

		if(orcamento_envio.length == 0){
			$("input[name='orcamento_aprovacao']").val("");
			//$("input[name='conserto']").val("");

			$("input[name='orcamento_aprovacao']").attr("disabled", true);
			//$("input[name='conserto']").attr("disabled", true);

			return false;
		}else{
			$("input[name='orcamento_aprovacao']").attr("disabled", false);
			//$("input[name='conserto']").attr("disabled", true);
		}

		if(orcamento_aprovacao.length == 0){
			$("input[name='conserto']").val("");

			$("input[name='conserto']").attr("disabled", true);

			return false;
		}else{
			$("input[name='conserto']").attr("disabled", false);
		}


		setTimeout("verficaCampoData()",1000);
	}

</script>

<style>
	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
	}
	.Conteudo {
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
	}
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}


	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}


	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
		width: 700px;
		padding: 3px 0;
		margin: 0 auto;
	}


	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
		border: 1px solid #596d9b;
	}

	.subtitulo{

		background-color: #7092BE;
		font:bold 14px Arial;
		color: #FFFFFF;
		text-align:center;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.texto_avulso{
		font: 14px Arial; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.informacao{
		font: 14px Arial; color:rgb(89, 109, 155);
		background-color: #C7FBB5;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.espaco{
		padding-left:80px; 
		width: 220px;
	}
</style>
<br>
<?
//include "javascript_pesquisas.php";

if(strlen($erro)>0) $msg_erro = $erro;

if(strlen($msg_erro)>0){
	if(strpos($msg_erro, "out of range")){
		$msg_erro = traduz("data.hora.esta.incorreta", $con, $cook_idioma);
	}
	if(strpos($msg_erro, "data_futura")){
		$msg_erro = traduz("data.entrada.nao.pode.ser.maior.que.data.hora.atual", $con, $cook_idioma);
	}
	if(strpos($msg_erro, "data_futura_fechamento")){
		$msg_erro = traduz("data.fechamento.nao.pode.menor.que.data.conserto", $con, $cook_idioma);
	}
	if(strpos($msg_erro, "data_futura_orcamento_envio")){
		$msg_erro = traduz("data.envio.nao.pode.ser.menor.que.data.abertura", $con, $cook_idioma);
	}
	if(strpos($msg_erro, "data_futura_orcamento_aprovacao")){
		$msg_erro = traduz("data.envio.nao.pode.ser.mario.que.data.aprovacao", $con, $cook_idioma);
	}
	if(strpos($msg_erro, "data_futura_conserto")){
		$msg_erro = traduz("data.conserto.nao.pode.ser.menor.que.data.aprovacao", $con, $cook_idioma);
	}
	echo "<div class='msg_erro'>" . $msg_erro . "</div>";
}
?>


<form method="POST" name="frm_os" id="frm_os" action="<?=$PHP_SELF?>">
	<table align="center" class="formulario" width="700" border="0" cellspacing='1' cellpadding='1'>
		<tr>
			<td class="titulo_tabela" align="center" colspan='7'><?php fecho("cadastro.os.fora.garantia", $con, $cook_idioma);?></td>
		</tr>
		<tr>
			<td width='20'>&nbsp;</td>
			<td width='120'>&nbsp;</td>
			<td width='80'>&nbsp;</td>
			<td width='140'>&nbsp;</td>
			<td width='120'>&nbsp;</td>
			<td width='120'>&nbsp;</td>
			<td width='20'>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td colspan='2'>
				<?php fecho("nome.do.consumidor", $con, $cook_idioma);?><br>
				<input name="consumidor_nome" size="22" maxlength="50" value="<?=$consumidor_nome?>" type="text" tabindex="0" />
			</td>
			<td>
				<?php fecho("consumidor.fone", $con, $cook_idioma);?><br>
				<input name="consumidor_fone" size="16" maxlength="14" value="<?=$consumidor_fone?>" type="text" rel="fone" tabindex="0">
			</td>
			<td colspan='2' style='padding-left: 30px;'>
				<?php fecho("consumidor.email", $con, $cook_idioma);?><br>
				<input name="consumidor_email" size="28" maxlength="50" value="<?=$consumidor_email?>" type="text" tabindex="0">
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td colspan='2'>
				<?php fecho("referencia.do.produto", $con, $cook_idioma);?><br>
				<input type="text" name="produto_referencia" id="produto_referencia" size="22" maxlength="20" value="<?=$produto_referencia?>">&nbsp;
				<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: pesquisaProduto(document.frm_os.produto_referencia, 'referencia')" />
			</td>
			<td  colspan='2'>
				<?php fecho("descricao.do.produto", $con, $cook_idioma);?><br>
				<input type="text" name="produto_descricao" id="produto_descricao" size="30" value="<?=$produto_descricao?>">
				&nbsp;
				<img src='imagens/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_os.produto_descricao, 'descricao')" />
			</td>
			<td>
				<?php fecho("voltagem", $con, $cook_idioma);?><br>
				<input  type="text" name="voltagem" id="voltagem" size="5" value="<?=$voltagem?>" />
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width='30'>&nbsp;</td>
			<td colspan='5'>
				<table  align="center" width="100%" border="0" cellspacing='0' cellpadding='0'>
					<tr>
						<td>
							<?php fecho("data.de.entrada", $con, $cook_idioma);?><br>
							<input name="abertura" rel='data' size='18' maxlength='18' value="<?=$abertura?>" type="text" rel="data" onkeyup = "verficaCampoData();" onblur=" verficaCampoData();" tabindex="0">
						</td>
						<td>
							<?php fecho("envio.do.orcamento", $con, $cook_idioma);?><br>
							<input name="orcamento_envio" rel='data' size='18' maxlength='18' value="<?=$orcamento_envio; ?>" type="text" rel="data" onkeyup = "verficaCampoData();" onblur="verficaCampoData();" tabindex="0">
						</td>
                        <td align='center'>&nbsp;<br>
                            <input type="checkbox" name="orcamento_reprovado" value="t" <? if($xorcamento_reprovado=="t") echo "checked"; ?> onclick='verficaCampoData();' /><br />
                            <?php fecho("orcamento.reprovado", $con, $cook_idioma);?>
                        </td>
						<td>
							<?php fecho("aprovacao.do.orcamento", $con, $cook_idioma);?><br>
							<input name="orcamento_aprovacao" rel='data' size='18' maxlength='18' value="<?=$orcamento_aprovacao; ?>" type="text" rel="data" onkeyup = "verficaCampoData();" onblur="verficaCampoData();" tabindex="0">
						</td>
						<td>
							<?php fecho("termino.conserto", $con, $cook_idioma);?><br>
							<input name="conserto" rel='data' size='18' maxlength='18' value="<?=$conserto; ?>" type="text" rel="data" onkeyup = "verficaCampoData();" onblur="verficaCampoData(); " tabindex="0">
						</td>
					</tr>
				</table>
			</td>
			<td width='30'>&nbsp;</td>
		</tr>
		<tr>
			<td align="center" colspan='7'><br>
				<input type='hidden' name='btn_acao' value='' />
				<input type='hidden' name='os_orcamento' value='<?php echo $os_orcamento;?>' />
				<input type='button' name='acao' rel='sem_submit' class='verifica_servidor' value='<?=fecho('gravar', $con, $cook_idioma);?>' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde ') }" /><br><br>
			</td>
		</tr>
	</table>
</form>
<?php include "rodape.php"; ?>
