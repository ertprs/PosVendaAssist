<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = trim($_GET["busca"]);
	$tipo_busca = trim($_GET["tipo_busca"]);
	if (strlen($q)>2){
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,";
					if($login_fabrica == 96)
						$sql .= "tbl_produto.referencia_fabrica AS referencia,";
					else
						$sql .= "tbl_produto.referencia,";
						$sql .= "tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				if($login_fabrica != 96){
					$sql .= " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ";
				}else{
					$sql .= " AND UPPER(tbl_produto.referencia_fabrica) like UPPER('%$q%') OR UPPER(tbl_produto.referencia_pesquisa) like UPPER('%$q%')";
				}
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}

	exit;
}

$msg_erro = "";

if (trim($_POST['comunicado']) > 0) $comunicado = trim($_POST['comunicado']);
if (trim($_GET['comunicado']) > 0)  $comunicado = trim($_GET['comunicado']);

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$ativo = trim($_POST['ativo']);


$replicar = $_POST['PickList'];

if (trim($btn_acao) == "gravar") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$produto_referencia     = trim($_POST['produto_referencia']);
	$extensao               = trim($_POST['extensao']);
	$tipo                   = trim($_POST['tipo']);
	$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
	$ativo                  = trim($_POST['ativo']);
	echo $familia                = trim($_POST['familia']);
	echo $linha                  = trim($_POST['linha']);
	$descricao              = trim($_POST['descricao']);
	$obrigatorio_os_produto = trim($_POST['obrigatorio_os_produto']);
	$obrigatorio_site       = trim($_POST['obrigatorio_site']);

	if($login_fabrica==6){
		$mensagem	        = trim($_POST['mensagem']);
	}
	if (strlen($descricao) == 0)  $aux_descricao = "null";
	else                          $aux_descricao = "'". $descricao ."'";

	if (strlen($tipo_posto) == 0) $aux_tipo_posto = "null";
	else                          $aux_tipo_posto = "'". $tipo_posto ."'";

	if (strlen($extensao) == 0)   $aux_extensao = "null";
	else                          $aux_extensao = "'". $extensao ."'";

	if (strlen($familia) == 0)    $aux_familia = "null";
	else                          $aux_familia = $familia ;

	if (strlen($linha) == 0)      $aux_linha = "null";
	else                          $aux_linha = $linha ;

	if (strlen($tipo) == 0)       $aux_tipo = "null";
	else                          $aux_tipo = "'". $tipo ."'";


//Quando selecionando o 'Aviso Posto Unico' faz a validacão para saber se entrou com os dados do posto
	if (((strlen($posto_nome) == 0) || (strlen($codigo_posto) == 0)) AND (strlen($tipo == 'Com. Unico Posto'))){
		$msg_erro = 'Por favor inserir os dados do posto';
	}
//Quando selecionando o 'Aviso Posto Unico' faz a validacão para saber se entrou com os dados do posto


	if (strlen($mensagem) == 0)   $aux_mensagem = "null";
	else                          $aux_mensagem = "'". $mensagem ."'";

	if (strlen($obrigatorio_os_produto) == 0) $aux_obrigatorio_os_produto = "'f'";
	else                                      $aux_obrigatorio_os_produto = "'t'";

	if (strlen($obrigatorio_site) == 0)       $aux_obrigatorio_site = "'f'";
	else                                      $aux_obrigatorio_site = "'t'";

	if (trim($ativo) != 't')                  $aux_ativo = "'f'";
	else                                      $aux_ativo = "'t'";

	if ((strlen($linha)==0 and strlen($descricao)==0) or
		(in_array($login_fabrica, array(3, 11, 14, 15)) and strlen($descricao)>0)) {//HD 198907
		if (strlen($produto_referencia) > 0){
			echo $sql = "SELECT produto, referencia FROM tbl_produto JOIN tbl_linha ON (tbl_linha.linha = tbl_produto.linha) WHERE (referencia = '$produto_referencia' OR  referencia_fabrica = '$produto_referencia') AND tbl_linha.fabrica = $login_fabrica; ";
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 0) $msg_erro = "Produto $produto_referencia não cadastrado";
			else                        $produto = pg_result ($res,0,0);
		}else{
			//$msg_erro .= "Por favor informe o Produto!" ;
			$produto = "null";
		}
	}else{
		$produto = "null";
	}


	$multiplo = trim($_POST['radio_qtde_produtos']);

	if ($multiplo == 'muitos'){
		$produto = "null";
	}

	$posto = "null";

	if (strlen($msg_erro) == 0) {
		if (strlen($comunicado) == 0) {
			$sql = "INSERT INTO tbl_comunicado (
						produto                ,
						familia                ,
						linha                  ,
						extensao               ,
						descricao              ,
						mensagem               ,
						tipo                   ,
						fabrica                ,
						obrigatorio_os_produto ,
						obrigatorio_site       ,
						posto                  ,
						tipo_posto             ,
						ativo                  ,
						remetente_email
					) VALUES (
						$produto                    ,
						$aux_familia                ,
						$aux_linha                  ,
						LOWER($aux_extensao)        ,
						$aux_descricao              ,
						$aux_mensagem               ,
						$aux_tipo                   ,
						$login_fabrica              ,
						$aux_obrigatorio_os_produto ,
						$aux_obrigatorio_site       ,
						$posto                      ,
						$aux_tipo_posto             ,
						$aux_ativo                  ,
						'$remetente_email'
					);";
		}else{
			$sql = "UPDATE tbl_comunicado SET
						produto                = $produto                    ,
						familia                = $aux_familia                ,
						linha                  = $aux_linha                  ,
						extensao               = LOWER($aux_extensao)        ,
						descricao              = $aux_descricao              ,
						mensagem               = $aux_mensagem               ,
						tipo                   = $aux_tipo                   ,
						obrigatorio_os_produto = $aux_obrigatorio_os_produto ,
						obrigatorio_site       = $aux_obrigatorio_site       ,
						posto                  = $posto                      ,
						ativo                  = $aux_ativo                  ,
						tipo_posto             = $aux_tipo_posto             ,
						remetente_email        = '$remetente_email'
					WHERE comunicado = $comunicado
					AND   fabrica    = $login_fabrica;";
		}
		echo nl2br($sql);
		die;
		//$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($comunicado) == 0) {
			$res        = pg_exec ($con,"SELECT currval ('seq_comunicado')");
			$comunicado = pg_result ($res,0,0);
		}
	}


	$sql = "DELETE FROM tbl_comunicado_produto
			WHERE comunicado = $comunicado";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);

	# Múltiplos Comunicados
	# HD 6392
	$replicar = $_POST['PickList'];
	$numero_multiplos = 0;

	if (count($replicar)>0 AND $multiplo=='muitos'){

		for ($i=0;$i<count($replicar);$i++){
			$p = trim($replicar[$i]);
			if (strlen($p)==0) continue;
			$sql = "SELECT tbl_produto.produto
					FROM tbl_produto
					JOIN tbl_linha USING(linha)
					WHERE tbl_produto.referencia='$p'
					AND tbl_linha.fabrica=$login_fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res)==1){
				$prod = pg_result($res,0,0);
				$numero_multiplos++;
				$sql = "SELECT comunicado
						FROM tbl_comunicado_produto
						WHERE comunicado = $comunicado
						AND   produto    = $prod ";
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res)==0){
					$sql = "INSERT INTO tbl_comunicado_produto (comunicado,produto )
							VALUES ($comunicado,$prod)";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}
	}

	if (strlen($linha)==0 AND $produto=='null' AND $numero_multiplos==0 ) {
		$msg_erro .= "Informe o produto!";
	}


	if ($login_fabrica==11){
		if ($tipo==''){
			$msg_erro .= "Informe o tipo de comunicado";
		}
		if ($aux_descricao=='null'){
			$msg_erro .= "Informe o titulo.";
		}
	}

	if (strlen ($msg_erro) == 0) {
		////////////////////////////
		// Rotina que faz o upload do arquivo
		///////////////////////////////////////////////////
		// Tamanho máximo do arquivo (em bytes)
		if($login_fabrica == 42) {
			$config["tamanho"] = 4*1024*1024;   //  4MB
		}else{
			$config["tamanho"] = 2*1024*1024;   //  2MiB - MLG
		}



		// Formulário postado... executa as ações
		if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

			// Verifica o MIME-TYPE do arquivo
			if (!preg_match("/\/(bin|pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|vnd.ms-powerpoint|zip|x-zip-compressed)$/", $arquivo["type"])){
				$msg_erro .= "Arquivo em formato inválido!";
			} else {
				// Verifica tamanho do arquivo
				if ($arquivo["size"] > $config["tamanho"])
					$msg_erro .= "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
			}

			if (strlen($msg_erro) == 0) {
				// Pega extensão do arquivo
				preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt|ppt|zip){1}$/i", $arquivo["name"], $ext);
				$aux_extensao = "'".$ext[1]."'";

				// Gera um nome único para a imagem
				$nome_anexo = $comunicado.".".$ext[1];

				// Caminho de onde a imagem ficará + extensao
				$imagem_dir = "../comunicados/".strtolower($nome_anexo);

				// Exclui anteriores, qquer extensao
				//@unlink($imagem_dir);
//echo $arquivo["tmp_name"];
				// Faz o upload da imagem
				if (strlen($msg_erro) == 0) {
					//move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
					if (copy($arquivo["tmp_name"], $imagem_dir)) {
						$sql =	"UPDATE tbl_comunicado SET
									extensao  = LOWER($aux_extensao)
								WHERE comunicado = $comunicado
								AND   fabrica    = $login_fabrica";
						$res = pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}else{
						$msg_erro .= "Arquivo não foi enviado!!!";
					}
				}
			}
		}
	}

	///////////////////////////////////////////////////
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		// HD 16279
		echo "<script language='javascript'>";
		if($login_fabrica==3){
			echo "alert('Arquivo cadastrado com Sucesso');";
		}
		echo "window.location='$PHP_SELF?cadastro=ok'";
		echo "</script>";

		///////////////////////
		exit;
	}

	$produto_referencia     = $_POST["produto_referencia"];
	$produto_descricao      = $_POST["produto_descricao"];
	$descricao              = $_POST['descricao'];
	$linha                  = $_POST['linha'];
	$familia                = $_POST['familia'];
	$extensao               = $_POST['extensao'];
	$tipo                   = $_POST['tipo'];
	$ativo                  = $_POST['ativo'];

	$res = pg_exec ($con,"ROLLBACK TRANSACTION");
}


if (strlen($comunicado) > 0) {
	$sql = "SELECT  tbl_produto.referencia AS prod_referencia,
					tbl_produto.descricao  AS prod_descricao ,
					tbl_comunicado.* ,
					tbl_posto.nome AS posto_nome ,
					tbl_posto_fabrica.codigo_posto
			FROM    tbl_comunicado
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_posto   ON tbl_comunicado.posto = tbl_posto.posto
			LEFT JOIN tbl_posto_fabrica ON tbl_comunicado.posto = tbl_posto_fabrica.posto AND tbl_comunicado.fabrica = tbl_posto_fabrica.fabrica
			WHERE   tbl_comunicado.comunicado = $comunicado
			AND     tbl_comunicado.fabrica    = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$produto_referencia     = trim(pg_result($res,0,prod_referencia));
		$produto_descricao      = trim(pg_result($res,0,prod_descricao));
		$descricao              = trim(pg_result($res,0,descricao));
		$extensao               = trim(pg_result($res,0,extensao));
		$extensao               = strtolower($extensao);
		$tipo                   = trim(pg_result($res,0,tipo));
		$ativo                  = trim(pg_result($res,0,ativo));
		$linha                  = trim(pg_result($res,0,linha));
		$familia                = trim(pg_result($res,0,familia));
		$obrigatorio_os_produto = (pg_result($res,0,obrigatorio_os_produto))?'t':'f';
		$obrigatorio_site		= (pg_result($res,0,obrigatorio_site))?'t':'f';

		if($login_fabrica==6)	{
			$mensagem                = trim(pg_result($res,0,mensagem));
		}

		$btn_lista = "ok";
	}

	# Comunicados multiplos PRODUTOS
	# HD 6392
	$sql = "SELECT 	tbl_produto.produto,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM tbl_comunicado_produto
			JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
			WHERE tbl_comunicado_produto.comunicado = $comunicado";
	$resProd = pg_exec ($con,$sql);
	$lista_produtos = array();
	for ($i=0; $i<pg_numrows ($resProd); $i++){
		$mult_produto    = trim(pg_result($resProd,$i,produto));
		$mult_referencia = trim(pg_result($resProd,$i,referencia));
		$mult_descricao  = trim(pg_result($resProd,$i,descricao));
		array_push($lista_produtos,array($mult_produto,$mult_referencia,$mult_descricao));
	}
}

if (trim($btn_acao) == "apagar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$comunicado = $_POST["apagar"];

	$sql = "SELECT extensao FROM tbl_comunicado WHERE tbl_comunicado.comunicado = $comunicado";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if (strlen($msg_erro) == 0) {
		$extensao = @pg_result($res,0,0);
	}
	//hd 9892
	$sql=" SELECT comunicado
			FROM tbl_comunicado_produto
			WHERE comunicado=$comunicado ";
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		$sql2 = "DELETE FROM tbl_comunicado_produto
				WHERE comunicado = $comunicado";
		$res2 = pg_exec ($con,$sql2);
		$msg_erro .= pg_errormessage($con);
	}
	$sql = "DELETE  FROM tbl_comunicado WHERE tbl_comunicado.comunicado = $comunicado";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if (strlen($msg_erro) == 0) {
		$imagem_dir = "../comunicados/".$comunicado.".".$extensao;
		if (is_file($imagem_dir)){
			if (!unlink($imagem_dir)){
				$msg_erro = "Não foi possível excluir arquivo";
			}
		}
	}

	if (strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF");
		exit;
	}

	$produto_referencia     = $_POST["produto_referencia"];
	$familia                = $_POST["familia"];
	$linha                  = $_POST["linha"];
	$produto_descricao      = $_POST["produto_descricao"];
	$descricao              = $_POST['descricao'];
	$extensao               = $_POST['extensao'];
	$tipo                   = $_POST['tipo'];
	$mensagem               = $_POST['mensagem'];
	$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
	$obrigatorio_site       = $_POST['obrigatorio_site'];
	$ativo                  = $_POST['ativo'];

	$res = pg_exec ($con,"ROLLBACK TRANSACTION");
}



if (trim($btn_acao) == "apagararquivo") {

	$comunicado = $_POST["apagar"];

	$sql = "SELECT extensao FROM tbl_comunicado WHERE comunicado = $comunicado";
	$res = pg_exec ($con,$sql);

	$imagem_dir = "../comunicados/".$comunicado.".".pg_result($res,0,0);

	$sql = "UPDATE tbl_comunicado SET extensao = null WHERE comunicado = $comunicado";
	$res = pg_exec ($con,$sql);

	if (is_file($imagem_dir)){
		if (!unlink($imagem_dir)){
			$msg_erro = "Não foi possível excluir arquivo";
		}else{
			header("Location: $PHP_SELF");
			exit;
		}
	}
}

$layout_menu = "tecnica";
$titulo = "Cadastramento de Comunicados / Vistas Explodidas / Fotos / Boletins";
$title = "CADASTRO DE COMUNICADOS / VISTAS EXPLODIDAS / FOTOS / BOLETINS";

include 'cabecalho.php';
?>


<script language='javascript'>
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function VerificaSuaOS (sua_os){
	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}
}

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

///////////////////////////////////////////////////////////

var singleSelect = true;  // Allows an item to be selected once only
var sortSelect = true;  // Only effective if above flag set to true
var sortPick = true;  // Will order the picklist in sort sequence

// Initialise - invoked on load
function initIt() {
	var pickList = document.getElementById("PickList");
	var pickOptions = pickList.options;
	pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
}

// Adds a selected item into the picklist
function addIt() {

	if ($('#produto_referencia_multi').val()=='')
		return false;

	if ($('#produto_descricao_multi').val()=='')
		return false;


	var pickList = document.getElementById("PickList");
	var pickOptions = pickList.options;
	var pickOLength = pickOptions.length;
	pickOptions[pickOLength] = new Option($('#produto_referencia_multi').val()+" - "+ $('#produto_descricao_multi').val());
	pickOptions[pickOLength].value = $('#produto_referencia_multi').val();

	$('#produto_referencia_multi').val("");
	$('#produto_descricao_multi').val("");

	if (sortPick) {
		var tempText;
		var tempValue;
		// Sort the pick list
		while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
			tempText = pickOptions[pickOLength-1].text;
			tempValue = pickOptions[pickOLength-1].value;
			pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
			pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
			pickOptions[pickOLength].text = tempText;
			pickOptions[pickOLength].value = tempValue;
			pickOLength = pickOLength - 1;
		}
	}

	pickOLength = pickOptions.length;
	$('#produto_referencia_multi').focus();
}

// Deletes an item from the picklist
function delIt() {
	var pickList = document.getElementById("PickList");
	var pickIndex = pickList.selectedIndex;
	var pickOptions = pickList.options;
	while (pickIndex > -1) {
		pickOptions[pickIndex] = null;
		pickIndex = pickList.selectedIndex;
	}
}
// Selection - invoked on submit
function selIt(btn) {
	var pickList = document.getElementById("PickList");
	var pickOptions = pickList.options;
	var pickOLength = pickOptions.length;
/*	if (pickOLength < 1) {
		alert("Nenhuma produto selecionado!");
		return false;
	}*/
	for (var i = 0; i < pickOLength; i++) {
		pickOptions[i].selected = true;
	}
/*	return true;*/
}
</script>


<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="js/jquery.dimensions.tootip.js"></script>
<script type="text/javascript" src="js/chili-1.7.pack.js"></script>
<script type="text/javascript" src="js/jquery.tooltip.js"></script>
<link rel="stylesheet" href="js/jquery.tooltip.css" />
<link rel="stylesheet" href="js/jquery.autocomplete.css" type="text/css" />

<script type="text/javascript">
	$(function() {
		$("a[@rel='ajuda'],img[@rel='ajuda'],input[@rel='ajuda']").Tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.85,
			fixPNG: true,
			showBody: " - ",
			extraClass: "balao"
		});
	});
</script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* Busca pelo Código */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_descricao").val(data[1]) ;
	});

	/* Busca por Nome */
	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
	});


	/*  MULTIPLOS PRODUTOS*/

	/* Busca por Produto */
	$("#produto_descricao_multi").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao_multi").result(function(event, data, formatted) {
		$("#produto_referencia_multi").val(data[2]) ;
		$('#adicionar').focus();
	});

	/* Busca pelo Nome */
	$("#produto_referencia_multi").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia_multi").result(function(event, data, formatted) {
		$("#produto_descricao_multi").val(data[1]) ;
		$('#adicionar').focus();
	});

});
</script>

<script language="JavaScript">
function toogleProd(radio){

	var obj = document.getElementsByName('radio_qtde_produtos');
	/*for(var x=0 ; x<obj.length ; x++){*/

	if (obj[0].checked){
		$('#id_um').show("slow");
		$('#id_multi').hide("slow");
	}
	if (obj[1].checked){
		$('#id_um').hide("slow");
		$('#id_multi').show("slow");
	}

}
</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #f5f5f5
}
.table_line2 {
	text-align: left;
	background-color: #fcfcfc
}
.ok {
	text-align: left;
	background-color: #f5f5f5;
	border:1px solid gray;
	font-size:12px;
	font-weight:bold;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
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
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}
</style>

<body>


<form enctype = "multipart/form-data" name="frm_comunicado" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="MAX_FILE_SIZE" value="7096000">
<input type="hidden" name="comunicado"    value="<? echo $comunicado ?>">
<input type='hidden' name='apagar'        value=''>
<input type='hidden' name='btn_acao'      value=''>
<input type="hidden" name="posto" value="<? echo $posto ?>">

<table width="700" align="center">
	<tr bgcolor="#d9e2ef" style="font: 14px Aria; color:#596d9b; border:1px solid #596d9b;">
	<td>*Se <B><U>não</U></B> for selecionado o posto, todos os postos receberam o comunicado<br>
	**Só será enviado o e-mail de confirmação se for selecionado o posto</td>
</tr>
</table>

<table width='700px' border='0' cellpadding='3' cellspacing='1' class='formulario' align='center'>

<? if (strlen($msg_erro) > 0) {?>
<tr>
	<td class='error' colspan=3 align='center'><? echo "$msg_erro"; ?></td>
</tr>
<?}

function opcao_tipo($nome_tipo,$tipo_sel) {
	$sel = ($tipo_sel == $nome_tipo) ? " SELECTED":"";
	return "\t\t\t<option value='$nome_tipo'$sel>$nome_tipo</option>\n";
}

$cad_ok=$_GET['cadastro'];
	if ($cad_ok== 'ok') {;?>
<tr>
	<td class='msg_sucesso' colspan='3' align='center'>Gravado com Sucesso!</td>
</tr>
<?}?>

<tr>
	<td colspan='3' align='center' class="titulo_tabela">Cadastro</td>
</tr>
<tr>
	<td align='right' >Comunicado Tipo *</td>
	<td align='left'>
		<select class='frm' name='tipo'>
			<option value=''>ESCOLHA</option>
			<option value='Vista Explodida'  <? if ($tipo == "Vista Explodida")    echo " SELECTED ";?>>Vista Explodida</option>
			<option value='Esquema Elétrico' <? if ($tipo == "Esquema Elétrico")   echo " SELECTED ";?>>Esquema Elétrico</option><?php
			if ($login_fabrica == 14 || $login_fabrica == 66) {//HD 265319 - MAXCOM
				echo opcao_tipo("Apresentação do Produto", $tipo);
				echo opcao_tipo("Árvore de Falhas",        $tipo);
				echo opcao_tipo("Boletim Técnico",         $tipo);
				echo opcao_tipo("Descritivo Técnico",      $tipo);
				echo opcao_tipo("Estrutura do Produto",    $tipo);
				echo opcao_tipo("Informativo Técnico",     $tipo);
				echo opcao_tipo("Politica de Manutenção",  $tipo);
				echo opcao_tipo("Teste Rede Autorizada",   $tipo);
				echo opcao_tipo("Manual de Trabalho",      $tipo);
			}
			if ($login_fabrica == 66) {//HD 265319 - MAXCOM
				echo opcao_tipo('Manual De Produto', $tipo);
				echo opcao_tipo('Versões Eprom',     $tipo);
			}
			if ($login_fabrica <> 3){ // HD 17700 18182?>
				<option value='Alterações Técnicas'  <? if ($tipo == "Alterações Técnicas")    echo " SELECTED ";?>>Alterações Técnicas</option><?php
				if ($login_fabrica <> 11){ // HD 54608 ?>
					<option value='Manual Técnico'  <? if ($tipo == "Manual Técnico")    echo " SELECTED ";?>>Manual Técnico</option><?php
				} else {?>
					<option value='Manual do Usuário'  <? if ($tipo == "Manual do Usuário")    echo " SELECTED ";?>>Manual do Usuário</option>
					<option value='Apresentação do Produto'  <? if ($tipo == "Apresentação do Produto")    echo " SELECTED ";?>>Apresentação do Produto</option>
					<option value='Informativo técnico'  <? if ($tipo == "Informativo técnico")    echo " SELECTED ";?>>Informativo técnico</option><?php
				}
			}
			if ($login_fabrica == 45) {//HD 231820?>
				<option value='Foto' <? if ($tipo == "Foto")   echo " SELECTED ";?>>Foto</option><?php
			}
			if ($login_fabrica == 15) {?>
				<option value='Diagrama de Serviços' <? if ($tipo == "Diagrama de Serviços")   echo " SELECTED ";?>>Diagrama de Serviços</option><?php
			}
			if ($login_fabrica == 19) {?>
				<option value='Peças de Reposição' <? if ($tipo == "Peças de Reposição")   echo " SELECTED ";?>>Peças de Reposição</option>
				<option value='Produtos'           <? if ($tipo == "Produtos")           echo " SELECTED ";?>>Produtos</option>
				<option value='Lançamentos'        <? if ($tipo == "Lançamentos")        echo " SELECTED ";?>>Lançamentos</option>
				<option value='Informativos'       <? if ($tipo == "Informativos")       echo " SELECTED ";?>>Informativos</option>
				<option value='Promoções'          <? if ($tipo == "Promoções")          echo " SELECTED ";?>>Promoções</option>
				<option value='Peças Alternativas' <? if ($tipo == "Peças Alternativas") echo " SELECTED ";?>>Peças Alternativas</option><?php
			}
			if ($login_fabrica == 3){?>
				<option value='Atualização de Software' <? if ($tipo == "Atualização de Software")   echo " SELECTED ";?>>Atualização de Software</option>
			<?}?>
		</select>
	</td>
</tr>
<?if (in_array($login_fabrica, array(3, 11, 14, 15))) {//HD 198907?>
<tr>
	<td align='right'>Título</td>
	<td align='left'><input type='text' name='descricao' value='<? echo $descricao ?>' size='50' maxlength='50' class='frm'></td>
</tr>
<?}?>
<tr>
	<td colspan='2'>
	<table border="0" width="100%" class="formulario">
		<tr>
			
			<td>
				<table border="0">
					<tr>
						<?php 
							if (in_array($login_fabrica, array(3, 11, 14, 15))) 
								$tam="95";
							else
								$tam="135";
						?>
						<td width="<?php echo $tam; ?>">&nbsp;</td>
						<td>
							<?
							if (count($lista_produtos)>0){
								$display_um_produto    = "display:none";
								$display_multi_produto = "";
								$display_um            = "";
								$display_multi         = " CHECKED ";
							}else{
								$display_um_produto    = "";
								$display_multi_produto = "display:none";
								$display_um            = " CHECKED ";
								$display_multi         = "";
							}
							?>

							
							<img src='imagens/help.png' title='Para selecionar vários produtos, clique em Vários Produtos e adicione os produtos a lista. Todos os produtos da lista serão referenciados ao comunicado. Para remover algum produto, selecione-o na lista e clique no botão Remover'>
							Para *&nbsp;&nbsp;
						</td>
						<td>
							Um produto
							<input type="radio" name="radio_qtde_produtos" value='um'  <?=$display_um?>  onClick='javascript:toogleProd(this)'>
							&nbsp;&nbsp;&nbsp;&nbsp;
							Vários Produtos
							<input type="radio" name="radio_qtde_produtos" value='muitos' <?=$display_multi?> onClick='javascript:toogleProd(this)'>
						</td>
					</tr>
				</table>

			</td>
		
		</tr>
		
		<tr>
			<td>
				<div id='id_um' style='<?echo $display_um_produto;?>'>
				<table border="0" width="100%" class="formulario">
					<tr>
						<?php 
							if (in_array($login_fabrica, array(3, 11, 14, 15))) 
								$tam="50";
							else
								$tam="90";
						?>
						<td width="<?php echo $tam; ?>">&nbsp;</td>
						<td width="100" align="right">Ref Produto&nbsp;</td>
						<td align="left"><input type="text" name="produto_referencia" id="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia,document.frm_comunicado.produto_descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
					</tr>
					<tr>
						<td width="40">&nbsp;</td>
						<td width="90" align="right">Descrição&nbsp;</td>
						<td align="left"><input type="text" name="produto_descricao" id="produto_descricao" value="<? echo $produto_descricao ?>" size="35" maxlength="50" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia,document.frm_comunicado.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></td>
					</tr>
					</table>
				</div>
			</td>
		</tr>

		<tr>
			<td>
				<div id='id_multi' style='<?echo $display_multi_produto;?>'>
					<table border="0" width="100%" class="formulario">
						<tr>
							<?php 
							if (in_array($login_fabrica, array(3, 11, 14, 15))) 
								$tam="50";
							else
								$tam="95";
							?>
							<td width="<?php echo $tam; ?>">&nbsp;</td>
							<td align="right">Ref Produto&nbsp;</td>
							<td align="left"><input type="text" name="produto_referencia_multi" id="produto_referencia_multi" value="" size="10" maxlength="20" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_duplicar.produto_referencia,document.frm_duplicar.produto_descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
						</tr>

						<tr>
							<td width="30">&nbsp;</td>
							<td align="right">Descrição&nbsp;</td>
							<td align="left"><input type="text" name="produto_descricao_multi" id="produto_descricao_multi" value="" size="30" maxlength="50" class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_duplicar.produto_referencia,document.frm_duplicar.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'>&nbsp;&nbsp;&nbsp;&nbsp;
							<input type='button' name='adicionar' id='adicionar' value='Adiconar' class='frm' onclick='addIt();'></td>
						</tr>
						<tr>
							<td colspan="3" align="center">
								<b style='font-weight:normal;color:gray;font-size:10px'>(Selecione o produto e clique em 'Adicionar')</b>
							</td>
						</tr>
					</table>
					
					<br>
						<SELECT MULTIPLE SIZE='6' style="width:80%" ID="PickList" NAME="PickList[]" class='frm'>

						<?
						if (count($lista_produtos)>0){
							for ($i=0; $i<count($lista_produtos); $i++){
								$linha_prod = $lista_produtos[$i];
								echo "<option value='".$linha_prod[1]."'>".$linha_prod[1]." - ".$linha_prod[2]."</option>";
							}
						}
						?>

						</SELECT> <br>
						<center><input TYPE="BUTTON" VALUE="Remover" ONCLICK="delIt();" class='frm'></input></center>
				</div>
			</td>
		</tr>
	</table>
	
	</td>
</tr>



<?
if ($login_fabrica == 19) {
?>

<tr>
	<td colspan=3 align='center' class="menu_top">Preencha os campos abaixo para documentos de toda uma linha.</td>
</tr>

<tr>
	<td align="right">Linha</td>
	<td colspan='2' align="left">
		<?
		$sql = "SELECT  *
				FROM    tbl_linha
				WHERE   tbl_linha.fabrica = $login_fabrica
				ORDER BY tbl_linha.nome;";
		$resX = pg_exec ($con,$sql);

		if (pg_numrows($resX) > 0) {
			echo "<select class='frm' style='width: 280px;' name='linha'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($resX) ; $x++){
				$aux_linha = trim(pg_result($resX,$x,linha));
				$aux_nome  = trim(pg_result($resX,$x,nome));

				echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
			}
			echo "</select>\n";
		}
		?>
	</td>

</tr>


<tr>
	<td align="right">Título</td>
	<td align="left" colspan='2'>
		<input type="text" name="descricao" value="<? echo $descricao ?>" size="50" maxlength="50" class='frm'>
	</td>

</tr>


<?
}
?>


<!-- fim Tipo do posto ========================================================================-->

<tr>
	<td align="right" width="195">Ativo/Inativo *</td>
	<td align='left'>
	Ativo<INPUT TYPE="radio" NAME="ativo" value='t' <? if ($ativo == 't'){ echo "checked"; } ?> >
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	Inativo<INPUT TYPE="radio" NAME="ativo" value='f' <? if ($ativo == 'f'){ echo "checked"; } ?> ></td>
</tr>

<?if($login_fabrica==14 OR $login_fabrica==6){?>
<tr>
	<td>Mensagem</td>
	<td colspan='2'><textarea name='mensagem' cols='85' rows='5' class='frm'><? echo $mensagem?></textarea></td>
</tr>
<?}?>
<tr>
	<td align="right"><img src='imagens/help.png' title='Para anexar uma VISTA EXPLODIDA você deve anexar apenas a imagem do produto em formato gif, pois os sistema já informa para o posto as peças baseando-se na lista básica do produto.' />Tamanho Máximo 2MB *</td>
	<td colspan='2' align="left"><input type='file' name='arquivo' size='65' class='frm'></td>
</tr>
<? if (strlen($comunicado) > 0 AND strlen($extensao) > 0) { ?>
<tr>
	<td align="right">Arquivo anterior</td>
	<td colspan='2' align="left">
		
		<input type="hidden" name="extensao" value="<? echo $extensao; ?>">
		<input type="button" value="Abrir Arquivo" onclick="window.location='../comunicados/<? echo $comunicado.".".$extensao; ?>'">
		
		<input type='button' value='Apagar Arquivo' alt='Clique aqui para apagar somente o arquivo anexado.' onclick='document.frm_comunicado.btn_acao.value="apagararquivo" ; document.frm_comunicado.apagar.value="<? echo $comunicado; ?>" ; document.frm_comunicado.submit()' style='cursor:pointer;'>
	</td>
</tr>
<tr class="subtitulo">
	<td colspan="3">
		
		A ação de alteração de um comunicado acarretará na exclusão do arquivo anteriormente enviado.<br>
		Para que isso não ocorra, lance um novo comunicado para este produto.
		
	</td>
</tr>
<? }
	if ($login_fabrica == 14) {?>
<tr>
	<td>Obrigatório na OS</td>
	<td colspan='2' align='left'><input type='checkbox' name='obrigatorio_os_produto' value='t' <? if ($obrigatorio_os_produto == "t") echo "checked" ?> class='frm'></td>
</tr>
<tr>
	<td align="right">Exibir na Tela de Entrada do Site</td>
	<td colspan='2' align='left'><input type='checkbox' name='obrigatorio_site' value='t' <? if ($obrigatorio_site == "t") echo "checked" ?> class='frm'></td>
</tr>
<?}?>
<tr>
	<td colspan='3' align='center'>
		<input type="button" value="Gravar" onclick='selIt();document.frm_comunicado.btn_acao.value="gravar"; document.frm_comunicado.submit()' >
<?	if (strlen($comunicado) > 0) { ?>
		&nbsp;&nbsp;&nbsp;
		<input type="button" value="Apagar" alt='Clique aqui para apagar.' onclick='document.frm_comunicado.btn_acao.value="apagar"; document.frm_comunicado.apagar.value="<?echo $comunicado?>"; document.frm_comunicado.submit()' >
<?	} ?>
	</td>
</tr>
</table>
</FORM>

<?
	if (strlen ($produto_referencia) > 0) {
		$sql = "SELECT  tbl_comunicado.comunicado                                    ,
						tbl_comunicado.familia                                       ,
						tbl_produto.referencia                    AS prod_referencia ,
						tbl_produto.descricao                     AS prod_descricao  ,
						tbl_comunicado.descricao                                     ,
						tbl_comunicado.extensao                                      ,
						tbl_comunicado.tipo                                          ,
						tbl_comunicado.mensagem                                      ,
						to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data            ,
						tbl_comunicado.obrigatorio_os_produto                        ,
						tbl_comunicado.obrigatorio_site
				FROM    tbl_comunicado
				JOIN    tbl_produto USING (produto)
				WHERE   tbl_produto.referencia = '$produto_referencia'
				ORDER BY tbl_comunicado.data DESC";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {
			echo "<br><table width='700' align='center' border='0' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
				echo "<td>Descrição</td>";
				echo "<td>Tipo Comunicado</td>";
				echo "<td>Data</td>";
				echo "<td>Ação</td>";
				echo "</tr>";
			for ($i = 0; $i < pg_numrows ($res); $i++){
				$comunicado_prod        = trim(pg_result($res,$i,comunicado));
				$familia                = trim(pg_result($res,$i,familia));
				$produto_referencia_prod= trim(pg_result($res,$i,prod_referencia));
				$produto_descricao      = trim(pg_result($res,$i,prod_descricao));
				$descricao              = trim(pg_result($res,$i,descricao));
				$extensao               = trim(pg_result($res,$i,extensao));
				$tipo                   = trim(pg_result($res,$i,tipo));
				$mensagem               = trim(pg_result($res,$i,mensagem));
				$data                   = trim(pg_result($res,$i,data));
				$obrigatorio_os_produto = pg_result($res,$i,obrigatorio_os_produto);
				$obrigatorio_site       = pg_result($res,$i,obrigatorio_site);
				
				$cor = ($i % 2 == 0) ? "#F7F5F0": "#F1F4FA";

				echo "<tr bgcolor='$cor'>";
				echo "<td align='center'>$descricao</td>";
				echo "<td align='center' >$tipo</td>";
				echo "<td align='center'>$data</td>";
				?>

				<td align='center'><input type='button' style='background:url(imagens_admin/btn_listar.gif); width:75px; cursor:pointer;' value='&nbsp;' alt='Clique aqui para alterar.' onclick="window.location='<?php echo $PHP_SELF."?comunicado=".$comunicado_prod;?>'"></td>
				<?
				echo "</tr>";
			}
			echo "</table><br>";
		}
	}

?>
<table border='0' align='center'>

</table>

<table width='700px' border='0' align='center' class='formulario' cellpadding='2' cellspacing='1'>
<form name='frm_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<input type='hidden' name='btn_acao' value=''>
<tr>
	<td colspan='4' align='center' class='titulo_tabela'>Pesquisar Comunicados Cadastrados</td>
</tr>
<tr>
	<td align='right'>Tipo</td>
	<td align='left'>
		<select class='frm' name='psq_tipo'>
			<option value=''></option>
			<option value='Vista Explodida'                    <? if ($psq_tipo == "Vista Explodida")                   echo "SELECTED ";?>>Vista Explodida</option>
			<option value='Esquema Elétrico'                   <? if ($psq_tipo == "Esquema Elétrico")                  echo "SELECTED ";?>>Esquema Elétrico</option><?php
			if ($login_fabrica == 14 || $login_fabrica == 66) {//HD 265319 - MAXCOM
			    echo opcao_tipo("Apresentação do Produto",	$tipo);
			    echo opcao_tipo("Árvore de Falhas",			$tipo);
			    echo opcao_tipo("Boletim Técnico",			$tipo);
				echo opcao_tipo("Descritivo Técnico",		$tipo);
				echo opcao_tipo("Estrutura do Produto",		$tipo);
				echo opcao_tipo("Informativo Técnico",		$tipo);
			    echo opcao_tipo("Politica de Manutenção",	$tipo);
			    echo opcao_tipo("Teste Rede Autorizada",	$tipo);
			    echo opcao_tipo("Manual de Trabalho",		$tipo);
			}
			if ($login_fabrica == 66) {//HD 265319 - MAXCOM
				echo opcao_tipo('Manual De Produto', $tipo);
				echo opcao_tipo('Versões Eprom',     $tipo);
			}
			if ($login_fabrica <> 3){ // HD 17700 18182?>
			<option value='Alterações Técnicas'  <? if ($tipo == "Alterações Técnicas")    echo " SELECTED ";?>>Alterações Técnicas</option>
			<?if ($login_fabrica <> 11){ // HD 54608 ?>
					<option value='Manual Técnico'  <? if ($tipo == "Manual Técnico")    echo " SELECTED ";?>>Manual Técnico</option>
				<?}else{ ?>
					<option value='Manual do Usuário'  <? if ($tipo == "Manual do Usuário")    echo " SELECTED ";?>>Manual do Usuário</option>
					<option value='Apresentação do Produto'  <? if ($tipo == "Apresentação do Produto")    echo " SELECTED ";?>>Apresentação do Produto</option>
					<option value='Informativo técnico'  <? if ($tipo == "Informativo técnico")    echo " SELECTED ";?>>Informativo técnico</option>
				<?}
			}
			if ($login_fabrica == 15){?>
				<option value='Diagrama de Serviços' <? if ($tipo == "Diagrama de Serviços")   echo " SELECTED ";?>>Diagrama de Serviços</option>
			<?}?>
			<?if ($login_fabrica == 19){?>
				<option value='Peças de Reposição' <? if ($tipo == "Peças de Reposição")   echo " SELECTED ";?>>Peças de Reposição</option>
				<option value='Produtos' <? if ($tipo == "Produtos")   echo " SELECTED ";?>>Produtos</option>
				<option value='Lançamentos' <? if ($tipo == "Lançamentos")   echo " SELECTED ";?>>Lançamentos</option>
				<option value='Informativos' <? if ($tipo == "Informativos")   echo " SELECTED ";?>>Informativos</option>
				<option value='Informativos' <? if ($tipo == "Informativos")   echo " SELECTED ";?>>Promoções</option>
				<option value='Peças Alternativas' <? if ($tipo == "Peças Alternativas")   echo " SELECTED ";?>>Peças Alternativas</option>
			<?}?>
			<?if ($login_fabrica == 3){?>
				<option value='Atualização de Software' <? if ($tipo == "Atualização de Software")   echo " SELECTED ";?>>Atualização de Software</option>
			<?}?>
		</select>
	</td>
	<td align='center'>Descrição/Título</td>
	<td align='left'><input type='text' name='psq_descricao' size='41' value='<? echo $psq_descricao; ?>' class='frm'></td>
</tr>
<tr>
	<td align='right' nowrap>&nbsp;&nbsp;&nbsp;Ref. Produto</td>
	<td align='left'><input type='text' name='psq_produto_referencia' size='20' class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.psq_produto_referencia,document.frm_pesquisa.psq_produto_nome,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
	<td align='right'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Descrição</td>
	<td align='left'><input type='text' name='psq_produto_nome'       size='41' class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.psq_produto_referencia,document.frm_pesquisa.psq_produto_nome,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></td>
	</tr>
	<tr>
	<td colspan='4' align='center' colspan='2'><br /><input type="button"  value="Continuar" onclick='document.frm_pesquisa.btn_acao.value="pesquisar"; document.frm_pesquisa.submit()' ></td>
</tr>
</form>
</table>
<br>


<?
if (trim($btn_acao) == "pesquisar") {
	$tipo               = $_POST['psq_tipo'];
	$descricao          = $_POST['psq_descricao'];
	$produto_referencia = $_POST['psq_produto_referencia'];
	$produto_descricao  = $_POST['psq_produto_nome'];

	#--------------------------------------------------------
	#  Mostra todos os informativos cadastrados
	#--------------------------------------------------------
	$sql = "SELECT	tbl_comunicado.comunicado                            ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data    ,
					tbl_comunicado.tipo                                  ,
					tbl_comunicado.descricao                             ,
					tbl_comunicado.linha           AS c_linha            ,
					tbl_produto.referencia AS produto_referencia         ,
					tbl_produto.descricao AS produto_descricao           ,
					tbl_comunicado.ativo
			FROM	tbl_comunicado
			LEFT JOIN tbl_produto USING(produto)
			LEFT JOIN tbl_linha   on tbl_linha.linha = tbl_produto.linha
			WHERE	tbl_comunicado.fabrica = $login_fabrica ";

	if (strlen($tipo) > 0)      $sql .= " AND tbl_comunicado.tipo      = '$tipo' ";
	if (strlen($descricao) > 0) $sql .= " AND tbl_comunicado.descricao ILIKE '%$descricao%' ";
	if (strlen($produto_referencia) > 0){
		//HD 9919 PAULO
		$produto_referencia = str_replace("-", "", $produto_referencia);
		$produto_referencia = str_replace("/", "", $produto_referencia);
		$produto_referencia = str_replace("'", "", $produto_referencia);
		$produto_referencia = str_replace(".", "", $produto_referencia);
//	MLG - Erro na pesquisa com espaço no meio na Lenoxx
		if ($login_fabrica==11) $produto_referencia = str_replace(" ", "", $produto_referencia);

		$sqlx = "SELECT   tbl_produto.produto
				FROM     tbl_produto
				JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
				WHERE    tbl_produto.referencia_pesquisa = '$produto_referencia'
				AND      tbl_linha.fabrica = $login_fabrica";
		$resx = pg_exec ($con,$sqlx);
		if (pg_numrows($resx) > 0){
			$produto = pg_result ($resx,0,0);
			$sql .= " AND tbl_comunicado.produto = $produto ";
		}
	}

	$sql.= " UNION ";

	$sql.= " SELECT	tbl_comunicado.comunicado                            ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data    ,
					tbl_comunicado.tipo                                  ,
					tbl_comunicado.descricao                             ,
					tbl_comunicado.linha           AS c_linha            ,
					tbl_produto.referencia AS produto_referencia         ,
					tbl_produto.descricao AS produto_descricao,
					tbl_comunicado.ativo
			FROM	tbl_comunicado
			LEFT JOIN tbl_produto USING(produto)
			LEFT JOIN tbl_linha   on tbl_linha.linha = tbl_produto.linha
			JOIN tbl_comunicado_produto using(comunicado)
			WHERE	tbl_comunicado.fabrica = $login_fabrica ";

	if (strlen($tipo) > 0)      $sql .= " AND tbl_comunicado.tipo      = '$tipo' ";
	if (strlen($descricao) > 0) $sql .= " AND tbl_comunicado.descricao ILIKE '%$descricao%' ";
	if (strlen($produto_referencia) > 0){

		$sqlx = "SELECT   tbl_produto.produto
				FROM     tbl_produto
				JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
				WHERE    tbl_produto.referencia_pesquisa = '$produto_referencia'
				AND      tbl_linha.fabrica = $login_fabrica";
		$resx = pg_exec ($con,$sqlx);
		if (pg_numrows($resx) > 0){
			$produto = pg_result ($resx,0,0);
			$sql .= " AND tbl_comunicado_produto.produto = $produto ";
		}
	}
	//Hd 9922 paulo
	if($login_fabrica== 5 ){
		$sql .=" ORDER BY produto_referencia ";
	}
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0){
		echo "<table width='700' align='center' border='0' class='tabela' cellspacing='1'>";
		echo "<caption class='table_line' style='text-align:left;'>(+) = Vários produtos</caption>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center' class='menu_top' nowrap>Tipo</td>";
		if ($login_fabrica==11 or $login_fabrica==15){//HD 198907
			echo "<td align='center' class='menu_top' nowrap>Descrição/Titulo</td>";
		}
		echo "<td nowrap>Refêrencia</td>";
		echo "<td nowrap>Produto</td>";
		echo "<td nowrap>Data</td>";
		echo "<td nowrap>Ativo</td>";
		echo "<td nowrap width='85'>Ação</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$descricao         = trim(pg_result ($res,$i,descricao));
			$referencia        = trim(pg_result ($res,$i,produto_referencia));
			$comunicado        = trim(pg_result ($res,$i,comunicado));
			$produto_descricao = trim (pg_result ($res,$i,produto_descricao));
			$data              = trim (pg_result ($res,$i,data));
			$ativo             = trim (pg_result ($res,$i,ativo));

			if (strlen($descricao)>0){
				$descricao = " <br>$descricao";
			}

			$sql2 = "SELECT tbl_comunicado_produto.comunicado,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_comunicado_produto
					JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
					WHERE comunicado = $comunicado";
			$res2 = pg_exec ($con,$sql2);
			if (pg_numrows($res2)>0){
				$referencia        = trim(pg_result ($res2,0,referencia));
				$produto_descricao = trim(pg_result ($res2,0,descricao));
				$referencia        = $referencia."...";
				$produto_descricao = $produto_descricao."... (+)";
			}

			if($i%2==0) $cor="#F7F5F0"; else $cor="#F1F4FA";

			echo "<tr bgcolor='$cor'>";

			echo "<td align='left'>";
			echo pg_result ($res,$i,tipo);
			echo "</td>";

			if ($login_fabrica==11 or $login_fabrica==15){//HD 198907
				echo "<td align='left'>";
				echo $descricao;
				echo "</td>";
			}

			echo "<td align='left'>";
			echo "<a href='$PHP_SELF?comunicado=" . $comunicado . "'>$referencia</a>";
			echo "</td>";

			echo "<td align='left'>";
			echo "<a href='$PHP_SELF?comunicado=" .$comunicado . "'>$produto_descricao</a>";
			echo "</td>";


			echo "<td align='center'>";
			echo  $data;
			echo "</td>";

			echo "<td align='center'>";
			if ($ativo != 't') {
				echo  "Inativo";
			} else {
				echo "<b>Ativo</b>";
			}

			echo "</td>";

			echo "<td align='left'>";
			echo "<a href='$PHP_SELF?comunicado=" . $comunicado . "'>";
			echo "<img src='imagens/btn_alterar_azul.gif'>";
			echo "</a>";
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}
}

include "rodape.php";
?>
