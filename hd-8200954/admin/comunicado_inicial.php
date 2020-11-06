<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";

if (trim($_POST['comunicado']) > 0) $comunicado = trim($_POST['comunicado']);
if (trim($_GET['comunicado']) > 0)  $comunicado = trim($_GET['comunicado']);

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new anexaS3('co', (int) $login_fabrica);
	$S3_online = is_object($s3);
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$ativo = trim($_POST['ativo']);


if (trim($btn_acao) == "gravar") {
	$res = pg_query ($con,"BEGIN TRANSACTION");

	$produto_referencia     = trim($_POST['produto_referencia']);
	$descricao              = trim($_POST['descricao']);
	$extensao               = trim($_POST['extensao']);
	$tipo                   = trim($_POST['tipo']);
	$mensagem               = trim($_POST['mensagem']);
	$mensagem               = str_replace("'","\'",$mensagem);
	$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
	$ativo                  = trim($_POST['ativo']);
	$codigo_posto           = trim($_POST['codigo_posto']);
	$posto_nome             = trim($_POST['posto_nome']);
	$linha                  = trim($_POST['linha']);

	if($login_fabrica == 1){

		$categoria_posto  	= $_POST["categoria_posto"];
		$tipo_posto 		= $_POST["tipo_posto"];
		$estados_posto		= $_POST["estados_posto"];
		$pedido_faturado    = $_POST["pedido_faturado"];
		$pedido_em_garantia = $_POST["pedido_em_garantia"];
		$estoque 			= $_POST["estoque"];
		$digita_os			= $_POST["digita_os"];

		if(strlen($pedido_faturado) == 0 ){
			$pedido_faturado = "'f'";
		}else{
			$pedido_faturado = "'t'";
		}

		if(strlen($pedido_em_garantia) == 0 ){
			$pedido_em_garantia = "'f'";
		}else{
			$pedido_em_garantia = "'t'";
		}

		if(strlen($estoque) == 0 ){
			$estoque = "'f'";
		}else{
			$estoque = "'t'";
		}

		if(strlen($digita_os) == 0 ){
			$digita_os = "'f'";
		}else{
			$digita_os = "'t'";
		}

		if($digita_os == "'f'" AND $estoque == "'f'" and $pedido_em_garantia == "'f'" AND $pedido_faturado == "'f'"){
			$digita_os = 'null';
			$estoque = 'null';
			$pedido_em_garantia = 'null';
			$pedido_faturado = 'null';
		}
	}

if($login_fabrica != 1){
	$digita_os = 'null';
	$estoque = 'null';
	$pedido_em_garantia = 'null';
	$pedido_faturado = 'null';
}


    //$mensagem = pg_escape_string($mensagem);

    if (strlen($descricao) == 0)  $aux_descricao = "null";
	else                          $aux_descricao = "'". $descricao ."'";

	if (strlen($tipo_posto) == 0 )  $aux_tipo_posto = "null";
	else                            $aux_tipo_posto = "'". $tipo_posto ."'";

	if (strlen($extensao) == 0)   $aux_extensao = "null";
	else                          $aux_extensao = "'". $extensao ."'";

	if (strlen($familia) == 0)    $aux_familia = "null";
	else                          $aux_familia = "'". $familia ."'";

	if (strlen($tipo) == 0)       $aux_tipo = "null";
	else                          $aux_tipo = "'". $tipo ."'";

	if (strlen($linha) == 0)    $aux_linha = "null";
	else                          $aux_linha= "'". $linha ."'";

	if(strlen(trim($categoria_posto))== 0 ){
		$categoria_posto = null;
	}

	if(strlen(trim($estados_posto))== 0 ){
		$estados_posto = null;
	}

	if (strlen($mensagem) == 0)   $aux_mensagem = "null";
	else                          $aux_mensagem = "E'". $mensagem ."'";

	if (strlen($obrigatorio_os_produto) == 0) $aux_obrigatorio_os_produto = "'f'";
	else                                      $aux_obrigatorio_os_produto = "'t'";

	if (strlen($obrigatorio_site) == 0)       $aux_obrigatorio_site = "'f'";
	else                                      $aux_obrigatorio_site = "'t'";

	if (trim($ativo) == 'f')                  $aux_ativo = "'f'";
	else                                      $aux_ativo = "'t'";

	$produto = "null";
	$posto = "null";

	//pega o codigo do posto========================================================

	if(strlen($codigo_posto) > 0 and $login_fabrica == 1) {
		$sql = "SELECT  posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica ";
		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) <> 1) {
			$msg_erro = traduz("Código do posto % não encontrado",null,null,[$codigo_posto]);
		}else{
			$posto = pg_fetch_result ($res,0,posto);
		}
	}

	//pega o codigo do posto========================================================

	if($login_fabrica == 20)$aux_pais = "'$login_pais'";
	else                    $aux_pais = "null";


	//pega o codigo do posto========================================================


	if (strlen($msg_erro) == 0) {
		if (strlen($comunicado) == 0) {
			$sql = "INSERT INTO tbl_comunicado (
						produto                ,
						familia                ,
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
						pais                   ,
						remetente_email        ,
						destinatario_especifico,
						estado                 ,
						pedido_faturado        ,
						pedido_em_garantia     ,
						digita_os              ,
						reembolso_peca_estoque ,
						linha
					) VALUES (
						$produto                    ,
						$aux_familia                ,
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
						$aux_pais                   ,
						'$remetente_email'          ,
						'$categoria_posto' 			,
						'$estados_posto' 			,
						$pedido_faturado          ,
						$pedido_em_garantia       ,
						$digita_os                ,
						$estoque   ,
						$aux_linha
					);";
		}else{
			$sql = "UPDATE tbl_comunicado SET
						produto                = $produto                    ,
						familia                = $aux_familia                ,
						extensao               = LOWER($aux_extensao)        ,
						descricao              = $aux_descricao              ,
						mensagem               = $aux_mensagem               ,
						tipo                   = $aux_tipo                   ,
						obrigatorio_os_produto = $aux_obrigatorio_os_produto ,
						obrigatorio_site       = $aux_obrigatorio_site       ,
						posto                  = $posto                      ,
						ativo                  = $aux_ativo                  ,
						tipo_posto             = $aux_tipo_posto             ,
						pais                   = $aux_pais                   ,
						remetente_email        = '$remetente_email'          ,
						destinatario_especifico = '$categoria_posto',
						estado				   = '$estados_posto',
						pedido_faturado        = $pedido_faturado,
						pedido_em_garantia     = $pedido_em_garantia,
						digita_os              = $digita_os,
						reembolso_peca_estoque = $estoque,
						linha                  = $aux_linha
					WHERE comunicado = $comunicado
					AND   fabrica    = $login_fabrica;";
				
					$ativo_comunicado = ($ativo == 't') ? $comunicado : "";
		}
		$res = pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($comunicado) == 0) {
			$res        = pg_query ($con,"SELECT currval ('seq_comunicado')");
			$comunicado = pg_fetch_result ($res,0,0);
			$ativo_comunicado = $comunicado;
		}
	}

	if(!empty($ativo_comunicado)) {
		$sql = "UPDATE tbl_comunicado set ativo = false where fabrica = $login_fabrica and ativo and tipo = $aux_tipo and comunicado <> $ativo_comunicado and posto isnull";
		$res = pg_query ($con,$sql);
	}
	if (strlen ($msg_erro) == 0) {

		// Formulário postado... executa as ações
		if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["name"]){
			if ($S3_online) {
// echo "Entrou";exit;
				$tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
				if ($s3->tipo_anexo != $tipo_s3)
					$s3->set_tipo_anexoS3($tipo_s3);

				if (!$s3->uploadFileS3($comunicado, $arquivo)) {
					$msg_erro = $s3->_erro;
				} else {
					$aux_extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
					//pegar extensão do arquivo hd-3349931 interação 168 - item 2
					$sql =" UPDATE tbl_comunicado
							   SET extensao   = LOWER('$aux_extensao')
							 WHERE comunicado = $comunicado
							   AND fabrica    = $login_fabrica";
					$res      = pg_query($con,$sql);

					$msg_erro = pg_last_error($con);
				}
			} else {

				// Verifica o MIME-TYPE do arquivo
				if (!preg_match("/\/(pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|vnd.ms-powerpoint|zip|x-zip-compressed|doc)$/", $arquivo["type"])){
					$msg_erro = traduz("Arquivo em formato inválido!");
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

					// Faz o upload da imagem
					if (strlen($msg_erro) == 0) {
						//move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
						if (copy($arquivo["tmp_name"], $imagem_dir)) {
							$sql =	"UPDATE tbl_comunicado SET
								extensao  = LOWER($aux_extensao)
								WHERE comunicado = $comunicado
								AND   fabrica    = $login_fabrica";
							$res = pg_query ($con,$sql);

							$msg_erro = pg_errormessage($con);
						}else{
							$msg_erro = traduz("Arquivo não foi enviado!!!");
						}
					}
				}
			}
		}
	}

	///////////////////////////////////////////////////
	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}

	$produto_referencia     = $_POST["produto_referencia"];
	$produto_descricao      = $_POST["produto_descricao"];
	$descricao              = $_POST['descricao'];
	$extensao               = $_POST['extensao'];
	$tipo                   = $_POST['tipo'];
	$mensagem               = $_POST['mensagem'];
	$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
	$obrigatorio_site       = $_POST['obrigatorio_site'];
	$ativo                  = $_POST['ativo'];
	$codigo_posto           = $_POST['codigo_posto'];
	$posto_nome             = $_POST['posto_nome'];
	$linha                  = $_POST['linha'];

	if (!$s3->excluiArquivoS3($s3->attachList[0]))
		$msg_erro .= $s3->_erro;
	$res = pg_query ($con,"ROLLBACK TRANSACTION");
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
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) > 0) {
		$produto_referencia     = trim(pg_fetch_result($res,0,prod_referencia));
		$familia                = trim(pg_fetch_result($res,0,familia));
		$linha                  = trim(pg_fetch_result($res,0,linha));
		$produto_descricao      = trim(pg_fetch_result($res,0,prod_descricao));
		$descricao              = trim(pg_fetch_result($res,0,descricao));
		$extensao               = trim(pg_fetch_result($res,0,extensao));
		$extensao               = strtolower($extensao);
		$tipo                   = trim(pg_fetch_result($res,0,tipo));
		$mensagem               = trim(pg_fetch_result($res,0,mensagem));
		$obrigatorio_os_produto = trim(pg_fetch_result($res,0,obrigatorio_os_produto));
		$obrigatorio_site       = trim(pg_fetch_result($res,0,obrigatorio_site));
		$posto                  = trim(pg_fetch_result($res,0,posto));
		$posto_nome             = trim(pg_fetch_result($res,0,posto_nome));
		$codigo_posto           = trim(pg_fetch_result($res,0,codigo_posto));
		$remetente_email        = trim(pg_fetch_result($res,0,remetente_email));
		$tipo_posto             = trim(pg_fetch_result($res,0,tipo_posto));
		$ativo                  = trim(pg_fetch_result($res,0,ativo));

		if($login_fabrica == 1){
			$categoria_posto 		= trim(pg_fetch_result($res,0,destinatario_especifico));
			$estado          		= trim(pg_fetch_result($res,0,estado));
			$pedido_faturado 		= trim(pg_fetch_result($res,0,pedido_faturado));
			$pedido_em_garantia		= trim(pg_fetch_result($res,0,pedido_em_garantia));
			$digita_os 				= trim(pg_fetch_result($res,0,digita_os));
			$estoque  				= trim(pg_fetch_result($res,0,reembolso_peca_estoque));
		}

		$btn_lista = "ok";
	}
}

if (trim($btn_acao) == "apagar") {
	$res = pg_query ($con,"BEGIN TRANSACTION");

	$comunicado = $_POST["apagar"];

	$sql = "SELECT extensao, tipo FROM tbl_comunicado WHERE tbl_comunicado.comunicado = $comunicado";
	$res = pg_query ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro) == 0) {
		$extensao = pg_fetch_result($res,0,0);
		$tipo     = pg_fetch_result($res,0,1);
	}
	if ($S3_online) {
		$tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
		if ($s3->tipo_anexo != $tipo_s3){
			$retorno = $s3->set_tipo_anexoS3($tipo_s3);
		}
		$s3->temAnexos($comunicado);

		if (!$s3->excluiArquivoS3($s3->attachList[0]))
			$msg_erro = $s3->_erro;
	} else {
		$imagem_dir = "../comunicados/".$comunicado.".".$extensao;
		if (is_file($imagem_dir)){
			if (!unlink($imagem_dir)){
				$msg_erro = traduz("Não foi possível excluir arquivo");
			}
		} else {
			$msg_erro = traduz('Arquivo não encontrado!');
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "DELETE  FROM tbl_comunicado WHERE tbl_comunicado.comunicado = $comunicado";
		$res = pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0){
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF");
		exit;
	}

	$produto_referencia     = $_POST["produto_referencia"];
	$familia                = $_POST["familia"];
	$produto_descricao      = $_POST["produto_descricao"];
	$descricao              = $_POST['descricao'];
	$extensao               = $_POST['extensao'];
	$tipo                   = $_POST['tipo'];
	$mensagem               = $_POST['mensagem'];
	$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
	$obrigatorio_site       = $_POST['obrigatorio_site'];
	$ativo                  = $_POST['ativo'];

	$res = pg_query ($con,"ROLLBACK TRANSACTION");
}

if (trim($btn_acao) == "apagararquivo") {

	$comunicado = $_POST["apagar"];

	$sql = "SELECT extensao, tipo FROM tbl_comunicado WHERE comunicado = $comunicado";
	$res = pg_query($con,$sql);
	extract(pg_fetch_assoc($res,0));

	if ($S3_online) {
		$tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
		if ($s3->tipo_anexo != $tipo_s3)
			$s3->set_tipo_anexoS3($tipo_s3);

		$s3->temAnexos($comunicado);
		if (!$s3->excluiArquivoS3($s3->attachList[0]))
			$msg_erro = $s3->_erro;
	} else {
		$imagem_dir = "../comunicados/".$comunicado.".".pg_fetch_result($res,0,0);
		if (is_file($imagem_dir)){
			if (!unlink($imagem_dir)){
				$msg_erro = traduz("Não foi possível excluir arquivo");
			}
		}
	}

	if (!$msg_erro) {
		$sql = "UPDATE tbl_comunicado SET extensao = NULL WHERE comunicado = $comunicado";
		$res = pg_query ($con,$sql);

		if (is_resource($res) and pg_affected_rows($res)==1) {
			header("Location: $PHP_SELF");
			exit;
		} else {
			$msg_erro = traduz('Erro ao atualizar o banco de dados!');
		}
	}
}

$layout_menu = traduz("tecnica");
$titulo = traduz("CADASTRO DE MENSAGEM PARA A TELA INICIAL");
$title = traduz("CADASTRO DE MENSAGEM PARA TELA INICIAL");

include 'cabecalho.php';
?>
<script type='text/javascript' src='js/jquery-1.3.2.js'></script>
<script type='text/javascript' src='js/fckeditor/fckeditor.js'></script>
<script language='javascript'>
window.onload = function(){
	var oFCKeditor = new FCKeditor( 'mensagem', 640 ) ;
	oFCKeditor.BasePath = "js/fckeditor/" ;
	oFCKeditor.ToolbarSet = 'Chamado' ;
	oFCKeditor.ReplaceTextarea();
}

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
margin: 0 auto;
}
.texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
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
width: 700px;
}
table.comespaco tr td{ padding-left:30px; }
</style>

<body>

<div class="texto_avulso" style="margin:10px auto 10px;"><?=traduz("É importante ressaltar que é sempre a última mensagem cadastrada que vai para a Página Inicial dos Postos.03/09/2010")?>;</div>

<form enctype = "multipart/form-data" name="frm_comunicado" method="post" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="comunicado"    value="<?=$comunicado?>">
<input type='hidden' name='apagar'        value=''>
<input type='hidden' name='btn_acao'      value=''>
<INPUT TYPE="hidden" value='Comunicado Inicial' name='tipo'>
<input type="hidden" name="posto" value="<? echo $posto ?>">


<table width='700px' border='0' class='formulario comespaco' cellpadding='3' cellspacing='1' align='center' style="text-align:left; tr td{ padding-left:20px; ">

<? if (strlen($msg_erro) > 0) { ?>
<tr>
	<td class='msg_erro' align='center'><? echo $msg_erro; ?></td>
</tr>
<? } ?>

<tr>
	<td class="titulo_tabela" colspan="3"><?=traduz("Comunicado Inicial")?></td>
</tr>


<tr>
	<td>
		Título<br />
		<input type='text' name='descricao' value='<?echo $descricao?>' style="width:638px;" maxlength='50' class='frm'>
	</td>

</tr>
<?
if($login_fabrica == 1){
	echo "<tr>";
	echo "<td>";
		echo "<table> <tr>";
		echo "<td align='left' style='padding-left:0px'> Linha <br>";


	##### INÍCIO LINHA #####
	$sql = "SELECT  *
			FROM    tbl_linha
			WHERE   tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_linha.nome;";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		echo "<select class='frm' name='linha' id='linha'>\n";
		echo "<option value=''>".traduz("ESCOLHA")."</option>\n";
		for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
			$aux_linha = trim(pg_fetch_result($res,$x,linha));
			$aux_nome= trim(pg_fetch_result($res,$x,nome));
			echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
		}
		echo "</select>\n";
	}
	##### FIM LINHA #####

	echo "</td>";

	echo "<td align='left' style='padding-left:0px'>".traduz("Categoria Posto")."<br>";?>

	<select class='frm' name='categoria_posto'>

	    <option value=""><?=traduz("Todos")?></option>
	    <option value="Autorizada"<?php echo ($categoria_posto == "Autorizada")? " selected ": "" ?> ><?=traduz("Autorizada")?></option>
	    <option value="Locadora"><?php echo ($categoria_posto == "Locadora")? " selected ": "" ?>><?=traduz("Locadora")?></option>
	    <option value="Locadora Autorizada"><?php echo ($categoria_posto == "Locadora Autorizada")? " selected ": "" ?>><?=traduz("Locadora Autorizada")?></option>
	    <option value="Pre Cadastro"<?php echo ($categoria_posto == "Pre Cadastro")? " selected ": "" ?>><?=traduz("Pré Cadastro")?></option>
	    <option value="mega projeto"<?php echo ($categoria_posto == "mega projeto")? " selected ": "" ?>><?=traduz("Mega Projeto")?></option>
	</select>

	<?php echo "</td>";
	echo "<td align='left' style='padding-left:0px'>".traduz("Tipo Posto")." <br>";
		?>
			<select name='tipo_posto' id='tipo_posto' class='frm'>
				<?php $sql = "SELECT *
						FROM tbl_tipo_posto
						WHERE tbl_tipo_posto.fabrica = $login_fabrica
						AND tbl_tipo_posto.ativo = 't'
						ORDER BY tbl_tipo_posto.descricao";
					$res = pg_query ($con,$sql);
					echo "<option value=''>".traduz("Todos")."</option>";
					for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
							echo "<option value='" . pg_fetch_result ($res,$i,tipo_posto) . "' ";
								if ($tipo_posto == pg_fetch_result ($res,$i,tipo_posto)) echo " selected ";
							echo ">";
							echo pg_fetch_result ($res,$i,descricao);
						echo "</option>";
					}
				?>
			</select>
		<?php
	echo "</td>";
	echo "<td align='left' style='padding-left:0px'>".traduz("Estados do Posto")."<br>";
	?>
	<select  name="estados_posto" id="estado" size="1" class='frm' tabindex="0">
		<option value=""></option>
		<?php
		$sql = "SELECT * FROM tbl_estado";
		if($sistema_lingua <> 'ES'){
			$sql .= " WHERE pais = 'BR' ";
		}
		$sql .= " ORDER BY estado ";
		$res = pg_query ($con,$sql);

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			echo "<option ";
			if ($estado == pg_fetch_result ($res, $i, "estado"))
				echo " selected " ;
			echo " value='" . pg_fetch_result ($res, $i, "estado") . "'>";
			echo pg_fetch_result ($res, $i, "nome");
			echo "</option>";
		}
		?>
	</select>
	<?php
	echo "</td>";
	echo "</tr> </table>";
	echo "</td>";
	echo "</tr>";
}
?>
<tr>
	<td style="width:300px;"><?=traduz("Mensagem")?><br /><textarea name='mensagem' cols='30' rows='5' class='frm' id='mensagem'><? echo $mensagem?></textarea></td>
</tr>
<?php if($login_fabrica == 1){ ?>
	<!-- Pesquisa de postos============================================================================ -->
<tr id='selecionarPosto'>
	<td class="formulario" colspan='2' nowrap align="left"><?=traduz("Cod. Posto")?> <input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="10" value="<? echo $codigo_posto ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'codigo')"></A>
		&nbsp;<?=traduz("Razão Social")?> <input class="frm" type="text" name="posto_nome" id="posto_nome" size="47" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'nome')" style="cursor:pointer;"></td>
</tr>
<!-- fim pesquisa de postos ========================================================================-->
<?php } ?>
<tr>
	<td>
		<fieldset style="padding-left:30px; width:150px;">
			<legend><?=traduz("Status")?></legend>
			<INPUT TYPE="radio" NAME="ativo" value='t' <? if (($ativo == 't') OR ($ativo == '')){ echo "checked"; } ?> > <?=traduz("Ativo")?>
			<INPUT TYPE="radio" NAME="ativo" value='f' <? if ($ativo == 'f'){ echo "checked"; } ?> >
			<?=traduz("Inativo")?>
		</fieldset>
	</td>
</tr>

<?php if($login_fabrica == 1){ ?>
	<tr>
		<td colspan='3'>
			<fieldset  style="padding-left:30px;">
				<legend>O <?=traduz("Posto Pode:")?></legend>
				<table>
					<tr>
						<td><input type='checkbox' name='pedido_faturado' <?php echo ($pedido_faturado == 't')? " checked ": "" ?>  value='t'><?=traduz("Pedido Faturado")?></td>
						<td><input type='checkbox' name='pedido_em_garantia' value='t'  <?php echo ($pedido_em_garantia == 't')? " checked ": "" ?> ><?=traduz("Pedido em Garantia")?></td>
						<td><input type='checkbox' name='estoque' value='t'  <?php echo ($estoque == 't')? " checked ": "" ?> ><?=traduz("Reembolso de Peça do Estoque")?></td>
						<td><input type='checkbox' name='digita_os' value='t' <?php echo ($digita_os == 't')? " checked ": "" ?> ><?=traduz("Digita OS")?></td>
					</tr>
				</table>
			</fieldset>
		</td>
	</tr>
<?php } ?>
<tr>
	<td ><?=traduz("Arquivo")?> <br /><input style="float:left;" size='80%' type='file' name='arquivo' class='frm'></td>
</tr>
<?
if (strlen($comunicado) > 0 AND strlen($extensao) > 0) {
	if ($S3_online) {
		$tipo_s3 = in_array($comunicado_tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co';
		if ($s3->tipo_anexo != $tipo_s3)
			$s3->set_tipo_anexoS3($tipo_s3);
		$s3->temAnexos($comunicado);
		if ($s3->temAnexo)
			$fileLink = $s3->url;
	} else {
		$filename = $comunicado.".".$extensao;
		if (file_exists($filename))
			$fileLink = $filename;
		unset($filename);
	}
?>
<tr>
	<td ><?=traduz("Arquivo anterior")?>
		&nbsp;&nbsp;&nbsp;
		<a href='<? echo $fileLink; ?>' target='_blank'><?=traduz("Abrir arquivo")?></a>
		<img src='imagens/btn_apagararquivo.gif' alt='<?=traduz("Clique aqui para apagar somente o arquivo anexado.'")?> onclick='document.frm_comunicado.btn_acao.value="apagararquivo" ; document.frm_comunicado.apagar.value="<? echo $comunicado; ?>" ; document.frm_comunicado.submit()' style='cursor:pointer;'>
	</td>
</tr>
<tr>
	<td class='texto_avulso' align='center'>
		<?=traduz("A ação de alteração de um comunicado acarretará na exclusão do arquivo anteriormente enviado.")?><br>
		<?=traduz("Para que isso não ocorra, lance um novo comunicado para este produto.")?>
	</td>
</tr>
<? } ?>

<tr>
	<td  colspan='3' align='center'><br />
		<input type="submit" onclick='document.frm_comunicado.btn_acao.value="gravar"; document.frm_comunicado.submit()' style="cursor: pointer; background:url('imagens_admin/btn_gravar.gif'); width:75px; height:20px;" value="" />
<?	if (strlen($comunicado) > 0) { ?>
		&nbsp;&nbsp;&nbsp;
		<input type="submit" onclick='document.frm_comunicado.btn_acao.value="apagar"; document.frm_comunicado.apagar.value="<?echo $comunicado?>"; document.frm_comunicado.submit()' style="cursor: pointer; background:url('imagens_admin/btn_apagar.gif'); width:75px; height:20px;" value=" " />
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
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) > 0) {
			echo "<br /><table width='700px' class='formulario' cellspacing='1' cellpadding='3' align='center' border='0'>";
			for ($i = 0; $i < pg_num_rows ($res); $i++){
				$comunicado             = trim(pg_fetch_result($res,$i,comunicado));
				$familia                = trim(pg_fetch_result($res,$i,familia));
				$produto_referencia     = trim(pg_fetch_result($res,$i,prod_referencia));
				$produto_descricao      = trim(pg_fetch_result($res,$i,prod_descricao));
				$descricao              = trim(pg_fetch_result($res,$i,descricao));
				$extensao               = trim(pg_fetch_result($res,$i,extensao));
				$tipo                   = trim(pg_fetch_result($res,$i,tipo));
				$mensagem               = trim(pg_fetch_result($res,$i,mensagem));
				$data                   = trim(pg_fetch_result($res,$i,data));
				$obrigatorio_os_produto = trim(pg_fetch_result($res,$i,obrigatorio_os_produto));
				$obrigatorio_site       = trim(pg_fetch_result($res,$i,obrigatorio_site));
				$cor = ($i%2) ?  '#F1F4FA' : '#F7F5F0';
				echo "<tr bgcolor='$cor'>";
				echo "<td align='center' >$descricao</td>";
				echo "<td align='center' >$data</td>";
				echo "<td align='center'><a href='$PHP_SELF?comunicado=$comunicado'><img src='imagens_admin/btn_listar.gif' alt='Clique aqui para alterar.'></a>";
				echo "</tr>";
			}
			echo "</table><br />";
		}
	}
?>

<hr width='700'>

<table width='700' class='formulario' align='center' border='0' cellpadding='2' cellspacing='1'>
<form name='frm_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<input type='hidden' name='btn_acao' value=''>
<tr>
	<td colspan='2' align='center' class='subtitulo'><?=traduz("Localizar Comunicados já Cadastrados")?></td>
</tr>
<tr class='titulo_coluna'>
	<td align='center'><?=traduz("Tipo")?></td>
	<td align='center'><?=traduz("Descrição")?>/Título</td>
</tr>
<tr>
	<td align='center'>
		<select class='frm' name='psq_tipo'>
			<option value=''></option>
			<option value='Comunicado Inicial'                  <? if ($psq_tipo == "Comunicado Inicial")              echo "SELECTED ";?>><?=traduz("Comunicado Inicial")?></option>
		</select>
	</td>
	<td align='center'><input type='text' name='psq_descricao' size='45' value='<? echo $psq_descricao; ?>' class='frm'></td>
</tr>
<tr>
	<td>&nbsp;</td>
</tr>
<tr>
	<td align='center' colspan='2'><img src='imagens/btn_continuar.gif' onclick='document.frm_pesquisa.btn_acao.value="pesquisar"; document.frm_pesquisa.submit()' style='cursor:pointer;'></td>
</tr>
</form>
</table>
<br>


<?
if (trim($btn_acao) == "pesquisar") {
	$tipo               = $_POST['psq_tipo'];
	$descricao          = $_POST['psq_descricao'];

	#--------------------------------------------------------
	#  Mostra todos os informativos cadastrados
	#--------------------------------------------------------
	$sql = "SELECT	tbl_comunicado.comunicado                            ,
					tbl_comunicado.descricao                             ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data    ,
					tbl_comunicado.tipo                                  ,
					tbl_produto.descricao AS produto_descricao
			FROM	tbl_comunicado
			LEFT JOIN tbl_produto USING(produto)
			LEFT JOIN tbl_linha   on tbl_linha.linha = tbl_produto.linha
			WHERE	tbl_comunicado.fabrica = $login_fabrica ";

	if (strlen($tipo) > 0)      $sql .= " AND tbl_comunicado.tipo      = '$tipo' ";
	if (strlen($descricao) > 0) $sql .= " AND tbl_comunicado.descricao ILIKE '%$descricao%' ";

	$sql .= "ORDER BY tbl_comunicado.data DESC";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0){
		echo "<table width='700px' class='tabela' cellspacing='3' cellpadding='3' align='center' border='0'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center' >".traduz("Tipo")."</td>";
		echo "<td align='center' >".traduz("Descrição")."</td>";
		echo "<td align='center' >".traduz("Data")."</td>";
		echo "<td align='center'  width='85'>".traduz("Ação")."</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

			$cor = ($i%2) ?  '#F7F5F0' : '#F1F4FA';
			echo "<tr bgcolor='$cor'>";

			echo "<td align='left' >";
			echo pg_fetch_result ($res,$i,tipo);
			echo "</td>";

			echo "<td align='left' >";
			echo "<a href='$PHP_SELF?comunicado=" . pg_fetch_result ($res,$i,comunicado) . "'>";
			echo pg_fetch_result ($res,$i,descricao);
			echo "</a>";
			echo "</td>";

			echo "<td align='left' >";
			echo pg_fetch_result ($res,$i,data);
			echo "</td>";

			echo "<td align='left'>";
			echo "<a href='$PHP_SELF?comunicado=" . pg_fetch_result ($res,$i,comunicado) . "'>";
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
