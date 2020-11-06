<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3 = new anexaS3('ve', (int) $login_fabrica);
	$S3_online = is_object($s3);
}

$msg_erro = "";

if (trim($_POST['comunicado']) > 0) $comunicado = trim($_POST['comunicado']);
if (trim($_GET['comunicado']) > 0)  $comunicado = trim($_GET['comunicado']);

if ($S3_sdk_OK) {
	include_once S3CLASS;

	if (is_numeric($comunicado)) {
		$tipoObj = (in_array($tipo_comunicado, explode(',', utf8_decode(anexaS3::TIPOS_VE)))) ? 've' : 'co';
		$s3 = new anexaS3($tipoObj, (int) $login_fabrica, $comunicado);
	} else {
		$s3 = new anexaS3('ve', (int) $login_fabrica);
	}
	$S3_online = is_object($s3);
}


$btn_acao = trim (strtolower ($_POST['btn_acao']));

$ativo = trim($_POST['ativo']);

if (trim($btn_acao) == "gravar") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$produto_referencia     = trim($_POST['produto_referencia']);
	$familia                = trim($_POST['familia']);
	$descricao              = trim($_POST['descricao']);
	$extensao               = trim($_POST['extensao']);
	$tipo                   = trim($_POST['tipo']);
	$mensagem               = trim($_POST['mensagem']);
	$obrigatorio_os_produto = trim($_POST['obrigatorio_os_produto']);
	$obrigatorio_site       = trim($_POST['obrigatorio_site']);
	$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
	$codigo_posto           = trim($_POST['codigo_posto']);
	$posto_nome             = trim($_POST['posto_nome']);
	$tipo_posto             = trim($_POST['tipo_posto']);
	$remetente_email        = trim($_POST['remetente_email']);
	$estado                 = trim($_POST['estado']);
	$ativo                  = trim($_POST['ativo']);

	if (strlen($descricao) == 0)  $aux_descricao = "null";
	else                          $aux_descricao = "'". $descricao ."'";

	if (strlen($tipo_posto) == 0 )  $aux_tipo_posto = "null";
	else                            $aux_tipo_posto = "'". $tipo_posto ."'";
	
	if (strlen($extensao) == 0)   $aux_extensao = "null";
	else                          $aux_extensao = "'". $extensao ."'";

	if (strlen($familia) == 0)    $aux_familia = "null";
	else                          $aux_familia = "'". $familia ."'";
//ALTERA��O FEITA POR RAPHAEL GIOVANINI PARA PODER ENVIAR CHAMADOS PARA OS POSTOS DO PARAN�
	if (strlen($estado) == 0)     $aux_estado = "null";
	else                          $aux_estado = "'". $estado ."'";
	
	if (strlen($tipo) == 0)       $aux_tipo = "null";
	else                          $aux_tipo = "'". $tipo ."'";

	$aux_pais = "'". $login_pais ."'";


//Quando selecionando o 'Aviso Posto Unico' faz a validac�o para saber se entrou com os dados do posto
	if (((strlen($posto_nome) == 0) || (strlen($codigo_posto) == 0)) AND (strlen($tipo == 'Com. Unico Posto'))){
		$msg_erro = 'Por favor inserir los datos del servicio';
	}
//Quando selecionando o 'Aviso Posto Unico' faz a validac�o para saber se entrou com os dados do posto

	
	if (strlen($mensagem) == 0)   $aux_mensagem = "null";
	else                          $aux_mensagem = "'". $mensagem ."'";
	
	if (strlen($obrigatorio_os_produto) == 0) $aux_obrigatorio_os_produto = "'f'";
	else                                      $aux_obrigatorio_os_produto = "'t'";
	
	if (strlen($obrigatorio_site) == 0)       $aux_obrigatorio_site = "'f'";
	else                                      $aux_obrigatorio_site = "'t'";

	if (trim($ativo) == 'f')                  $aux_ativo = "'f'";
	else                                      $aux_ativo = "'t'";

	if (strlen($produto_referencia) > 0){
		$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$produto_referencia'";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 0) $msg_erro = "Producto $produto_referencia no catastrado";
		else                        $produto = pg_result ($res,0,0);
	}else{
		$produto = "null";
	}

	//pega o codigo do posto========================================================

	$posto = "null";

	if(strlen($codigo_posto) > 0) {
		$sql = "SELECT  posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica ";
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) <> 1) {
			$msg_erro = "C�digo del servicio ($codigo_posto) no encuentrado";
		}else{
			$posto = pg_result ($res,0,posto);
		}
	}

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
						estado                 ,
						pais                   ,
						remetente_email
					) VALUES (
						$produto                    ,
						$aux_familia                ,
						$aux_extensao               ,
						$aux_descricao              ,
						$aux_mensagem               ,
						$aux_tipo                   ,
						$login_fabrica              ,
						$aux_obrigatorio_os_produto ,
						$aux_obrigatorio_site       ,
						$posto                      ,
						$aux_tipo_posto             ,
						$aux_ativo                  ,
						$aux_estado                 ,
						$aux_pais                   ,
						'$remetente_email'
					);";
		}else{
			$sql = "UPDATE tbl_comunicado SET
						produto                = $produto                    ,
						familia                = $aux_familia                ,
						extensao               = $aux_extensao               ,
						descricao              = $aux_descricao              ,
						mensagem               = $aux_mensagem               ,
						tipo                   = $aux_tipo                   ,
						obrigatorio_os_produto = $aux_obrigatorio_os_produto ,
						obrigatorio_site       = $aux_obrigatorio_site       ,
						posto                  = $posto                      ,
						ativo                  = $aux_ativo                  ,
						tipo_posto             = $aux_tipo_posto             ,
						estado                 = $aux_estado                 ,
						pais                   = $aux_pais                   ,
						remetente_email        = '$remetente_email'
					WHERE comunicado = $comunicado
					AND   fabrica    = $login_fabrica;";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($comunicado) == 0) {
			$res        = pg_exec ($con,"SELECT currval ('seq_comunicado')");
			$comunicado = pg_result ($res,0,0);
		}
	}

	if (strlen ($msg_erro) == 0) {
		///////////////////////////////////////////////////
		// Rotina que faz o upload do arquivo
		///////////////////////////////////////////////////
		// Tamanho m�ximo do arquivo (em bytes) 
		$config["tamanho"] = 4096000;

		// Formul�rio postado... executa as a��es 
		if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

			// Verifica o MIME-TYPE do arquivo
			if (!preg_match("/\/(pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|vnd.ms-powerpoint|zip|x-zip-compressed)$/", $arquivo["type"])){
				$msg_erro = "Archivo en formato inv�lido!";
			} else {
				// Verifica tamanho do arquivo 
				if ($arquivo["size"] > $config["tamanho"]) 
					$msg_erro = "Archivo en tama�o muy grande! Debe tener el m�ximo 4MB. Envie otro archivo.";
			}

			if (strlen($msg_erro) == 0) {
				if (is_object($s3)) {
					$tipo_s3 = (in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE)))) ? 've' : 'co';
					if ($s3->tipo_anexo != $tipo_s3)
						$s3->set_tipo_anexoS3($tipo_s3);

					if (!$s3->uploadFileS3($comunicado, $arquivo, true)) {
						$msg_erro = $s3->_erro;
					} else {
						//print_r($s3);die;
						$aux_extensao = pathinfo($s3->attachList[0], PATHINFO_EXTENSION);
						$sql =	"UPDATE tbl_comunicado
							SET extensao   = LOWER('$aux_extensao')
							WHERE comunicado = $comunicado
							AND fabrica	= $login_fabrica";
						$res = @pg_query ($con,$sql);
						$msg_erro .= pg_last_error($con);
					}
				} else {
					// Pega extens�o do arquivo
					preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt|ppt|pps|zip){1}$/i", $arquivo["name"], $ext);
					$extensao_anexo = $ext[1];
					$aux_extensao   = "'$extensao_anexo'";

					// Gera um nome �nico para a imagem
					$nome_anexo = "$comunicado.$extensao_anexo";

					// Caminho de onde a imagem ficar� + extensao
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
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_last_error($con);
						}
					}
				}
			}
		}
	}
if($ip=="201.27.212.208"){
//echo "../comunicados/".strtolower($nome_anexo)."   -".$aux_extensao;
}
	///////////////////////////////////////////////////
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}
	
	$s3->excluiArquivoS3($s3->attachList[0]); // Exlui o arquivo, abortada a grava��o do comunicado.

	$produto_referencia     = $_POST["produto_referencia"];
	$produto_descricao      = $_POST["produto_descricao"];
	$descricao              = $_POST['descricao'];
	$extensao               = $_POST['extensao'];
	$tipo                   = $_POST['tipo'];
	$mensagem               = $_POST['mensagem'];
	$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
	$obrigatorio_site       = $_POST['obrigatorio_site'];
	$estado                 = $_POST['estado'];
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
		$familia                = trim(pg_result($res,0,familia));
		$produto_descricao      = trim(pg_result($res,0,prod_descricao));
		$descricao              = trim(pg_result($res,0,descricao));
		$extensao               = trim(pg_result($res,0,extensao));
		$extensao               = strtolower($extensao);
		$tipo                   = trim(pg_result($res,0,tipo));
		$mensagem               = trim(pg_result($res,0,mensagem));
		$obrigatorio_os_produto = trim(pg_result($res,0,obrigatorio_os_produto));
		$obrigatorio_site       = trim(pg_result($res,0,obrigatorio_site));
		$posto                  = trim(pg_result($res,0,posto));
		$posto_nome             = trim(pg_result($res,0,posto_nome));
		$codigo_posto           = trim(pg_result($res,0,codigo_posto));
		$remetente_email        = trim(pg_result($res,0,remetente_email));
		$tipo_posto             = trim(pg_result($res,0,tipo_posto));
		$estado                 = trim(pg_result($res,0,estado));
		$ativo                  = trim(pg_result($res,0,ativo));
		$btn_lista = "ok";
	}
}

if (trim($btn_acao) == "apagar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$comunicado = $_POST["apagar"];

	$sql = "SELECT extensao FROM tbl_comunicado WHERE tbl_comunicado.comunicado = $comunicado";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro) == 0) {
		$extensao = @pg_result($res,0,0);
	}
	
	$sql = "DELETE  FROM tbl_comunicado WHERE tbl_comunicado.comunicado = $comunicado";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro) == 0) {
		if ($S3_online and $s3->temAnexos($comunicado)) {
			if (!$s3->excluiArquivoS3($s3->attachList[0])) {
				$msg_erro = $s3->_erro;
			}
		} else {
			$imagem_dir = "../comunicados/".$comunicado.".".$extensao;
			if (is_file($imagem_dir)){
				if (!unlink($imagem_dir)){
					$msg_erro = "N�o foi poss�vel excluir arquivo";
				}
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
	$produto_descricao      = $_POST["produto_descricao"];
	$descricao              = $_POST['descricao'];
	$extensao               = $_POST['extensao'];
	$tipo                   = $_POST['tipo'];
	$mensagem               = $_POST['mensagem'];
	$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
	$obrigatorio_site       = $_POST['obrigatorio_site'];
	$estado                 = $_POST['estado'];
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

		if ($S3_online and $s3->temAnexos($comunicado)) {
			if (!$s3->excluiArquivoS3($s3->attachList[0])) {
				$msg_erro = $s3->_erro;
			}
		} else {
			$imagem_dir = "../comunicados/".$comunicado.".".pg_fetch_result($res,0,0);

			if (is_file($imagem_dir)){
				if (!unlink($imagem_dir)){
					$msg_erro = "N�o foi poss�vel excluir arquivo";
				}
			}
		}

}

$layout_menu = "tecnica";
$title = "Publicaci�n de comunicados / fotos/ informativos";

include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 

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
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script src="../js/jquery.blockUI_2.39.js" ></script>

<script>
    $(function () {
        $("a[name=prod_ve]").click(function () {
		    var attr = $(this).attr("rel").split("/");
		    var comunicado = attr[0];
            var tipo = attr[1];

            $.ajaxSetup({
            	async: true
            });

            $.blockUI({ message: "Aguarde..." });

            $.get("../verifica_s3_comunicado.php", { comunicado: comunicado,fabrica:"<?=$login_fabrica?>",tipo: tipo}, function (data) {
    		  	if (data.length > 0) {
	            	var nav = window.navigator.userAgent;
					var newWin = window.open(data, "_blank", "menubar=no, titleblar=no, status=no, location=no, resizable=yes");

					if (nav.match(/Chrome/gi) && nav.match(/Safari/gi)) {
						popupBlockerChecker.check(newWin);
					} else {
						if (!newWin) {
		                    Shadowbox.init();

		                    Shadowbox.open({
		                	    content :   "../popup_bloqueado.php",
		                    	player  :   "iframe",
								title   :   "POPUP BLOQUEADO",
								width   :   800,
								height  :   600
							});
						}
					}
				} else {
                    alert("Archivo no encontrado!");
                }

                $.unblockUI();
            });
        });
    });

	var popupBlockerChecker = {
		check: function(popup_window) {
			var _scope = this;

			if (popup_window) {
				if (/chrome/.test(navigator.userAgent.toLowerCase())) {
					setTimeout(function() {
						_scope._is_popup_blocked(_scope, popup_window);
					}, 500);
				}else{
					popup_window.onload = function() {
						_scope._is_popup_blocked(_scope, popup_window);
					};
				}
			}else{
				_scope._displayMsg();
			}
		},
		_is_popup_blocked: function(scope, popup_window){
			if ((popup_window.screenX > 0) == false) {
				scope._displayMsg();
		    }
		},
		_displayMsg: function() {
			Shadowbox.init();

			Shadowbox.open({
				content :   "../popup_bloqueado.php",
				player  :   "iframe",
				title   :   "POPUP BLOQUEADO",
				width   :   800,
				height  :   600
			});
		}
	};
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
</style>

<body>


<form enctype = "multipart/form-data" name="frm_comunicado" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="MAX_FILE_SIZE" value="4000000">
<input type="hidden" name="comunicado"    value="<? echo $comunicado ?>">
<input type='hidden' name='apagar'        value=''>
<input type='hidden' name='btn_acao'      value=''>
<input type="hidden" name="posto" value="<? echo $posto ?>">


<table width='700' border='0' cellpadding='5' cellspacing='3' align='center'>

<? if (strlen($msg_erro) > 0) { ?>
<tr>
	<td class='error' colspan=3 align='center'><? echo $msg_erro; ?></td>
</tr>
<? } ?>

<tr>
	<td colspan=3 align='center' class="menu_top">Campos en NEGRILLA son obligatorios.</td>
</tr>

<tr>
	<td class='table_line'><B>Tipo comunicado</B></td>
	<td class='table_line2'>
		<select class='frm' name='tipo'>
			<option value=''> ELIJA </option>
			<option value='Com. Unico Posto'            <? if ($tipo == 'Com. Unico Posto')       echo " SELECTED ";?>>Com. �nico Servicio</option>
			<option value='Boletim'                     <? if ($tipo == "Boletim")                echo " SELECTED ";?>>Bolet�n</option>
			<option value='Comunicado'                  <? if ($tipo == "Comunicado")             echo " SELECTED ";?>>Comunicado</option>
			<option value='Informativo'                 <? if ($tipo == "Informativo")            echo " SELECTED ";?>>Informativo</option>
			<option value='Foto'                        <? if ($tipo == "Foto")                   echo " SELECTED ";?>>Foto</option>
			<option value='Descritivo t�cnico'          <? if ($tipo == "Descritivo t�cnico")     echo " SELECTED ";?>>Descriptivo tecnico</option>
			<option value='Informativo tecnico'         <? if ($tipo == "Informativo tecnico")    echo " SELECTED ";?>>Informativo tecnico </option>
			<option value='Manual'                      <? if ($tipo == "Manual")                 echo " SELECTED ";?>>Manual</option>
			<option value='Orienta��o de Servi�o'       <? if ($tipo == "Orienta��o Servi�o")     echo " SELECTED ";?>>Orientaci�n de servicio</option>
			<option value='Lan�amentos'                 <? if ($tipo == "Lan�amentos")            echo " SELECTED ";?>>Lanzamientos</option>
			<option value='Procedimentos'               <? if ($tipo == "Procedimentos")          echo " SELECTED ";?>>Procedimientos</option>
			<option value='Promocao'                    <? if ($tipo == "Promocao")               echo " SELECTED ";?>>Promoci�n</option>
			<option value='Estrutura do Produto'        <? if ($tipo == "Estrutura do Produto")   echo " SELECTED ";?>>Estructura del producto</option>
			<option value='Capacita��o V�deo'           <? if ($tipo == "Capacita��o V�deo")      echo " SELECTED ";?>>Capacitaci�n Video</option>
			<option value='Capacita��o Manual'          <? if ($tipo == "Capacita��o Manual")     echo " SELECTED ";?>>Capacitaci�n Manual</option>
		</select> 
	</td>
	<td class='table_line2'><FONT COLOR="#6B6B6B">*<B>Com. �nico Servicio:</B> utilizando esa opci�n, es necesario definir el servicio.</FONT></td>
</tr>
<tr>
	<td class='table_line'>Producto</td>
	<td class='table_line2'>Ref:&nbsp;<input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia,document.frm_comunicado.produto_descricao,'referencia')" alt='Haga un click aqui bara buscar por referencia de herramienra' style='cursor:pointer;'></td>
	<td class='table_line2'>Descripci�n:&nbsp;<input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="45" maxlength="50" class='frm'>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia,document.frm_comunicado.produto_descricao,'descricao')" alt='Haga un click aqui bara buscar por referencia de herramienra' style='cursor:pointer;'></td>
</tr>
<tr>

	<td class='table_line'>Fam�lia</td>
	<td class='table_line2' colspan='2'>
<?
		##### IN�CIO FAM�LIA #####
		$sql = "SELECT  tbl_familia_idioma.familia   ,
						tbl_familia_idioma.descricao
				FROM    tbl_familia
				JOIN    tbl_familia_idioma using(familia)
				WHERE   tbl_familia.fabrica = $login_fabrica
				ORDER BY tbl_familia.descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='frm' style='width: 280px;' name='familia'>\n";
			echo "<option value=''>ELIJA</option>\n";
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_familia = trim(pg_result($res,$x,familia));
				$aux_descricao  = trim(pg_result($res,$x,descricao));
				echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
		##### FIM FAM�LIA #####
?>
	</td>
</tr>
<tr>
	<td class='table_line'><B>T�tulo</B></td>
	<td class='table_line2' colspan='2'><input type='text' name='descricao' value='<?echo $descricao?>' size='85' maxlength='50' class='frm'></td>
</tr>
<tr>
	<td class='table_line'>Mensaje</td>
	<td class='table_line2' colspan='2'><textarea name='mensagem' cols='85' rows='5' class='frm'><? echo $mensagem?></textarea></td>
</tr>

<?

//================================pega email do admin logado====================================
if (strlen ($remetente_email) == 0 AND strlen ($comunicado) == 0 ) {
	$sql =	"SELECT email FROM tbl_admin WHERE admin = $login_admin";
	$res = pg_exec ($con,$sql);
	$remetente_email = pg_result($res,0,email);
}
//================================pega email do admin logado====================================
?>



<!-- Pesquisa de postos============================================================================ -->
<tr>
	<td class="table_line">Selecionar Servicio</td>
	<td class="table_line2" colspan='2' nowrap>Cod.:<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'codigo')"></A>
		&nbsp;Nombre oficial del Servicio:<input class="frm" type="text" name="posto_nome" size="47" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'nome')" style="cursor:pointer;"><br><br>
		Email confirmaci�n lectura:&nbsp;<INPUT TYPE="text" size='40' value='<?echo $remetente_email?>' class='frm' NAME="remetente_email"></A></td>
</tr>
<!-- fim pesquisa de postos ========================================================================-->


<!-- tipo de posto== ========================================================================-->
<tr>
	<td class="table_line">Tipo de Servicio</td>
	<td class='table_line2'><select name='tipo_posto' class='frm' size='1'>
		<?	$sql = "SELECT *
							FROM   tbl_tipo_posto
							WHERE  tbl_tipo_posto.fabrica = $login_fabrica and tipo_posto =92
							ORDER BY tbl_tipo_posto.descricao";
					$res = pg_exec ($con,$sql);
							echo "<option value=''>TODOS";
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
							echo "<option value='" . pg_result ($res,$i,tipo_posto) . "' ";
								if ($tipo_posto == pg_result ($res,$i,tipo_posto)) echo " selected ";
							echo ">";
							echo pg_result ($res,$i,descricao);
							echo "</option>";
						}
			
		?></select> </td>
	<td class='table_line2'><FONT COLOR="#6B6B6B">* Solo los postos del tipo elejidos receber�n el comunicado.</FONT></td>
</tr>

<!-- fim Tipo do posto ========================================================================-->
<?/*
<tr>
	<td class="table_line">Estado dos Posto</td>
	<td class='table_line2'>
		<select  name="estado" size="1" class="frm" tabindex="0" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Escolha Unidade Federal (Estado).');">
			<option selected> </option>
			<?
			$sql = "SELECT * FROM tbl_estado ORDER BY estado";
			$res = pg_exec ($con,$sql);
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option ";
				if ($estado == pg_result ($res,$i,estado) ) 
					echo " selected " ;
				echo " value='" . pg_result ($res,$i,estado) . "'>";
				echo pg_result ($res,$i,estado);
				echo "</option>";
			}
			
		</select>
 </td>
	<td class='table_line2'><FONT COLOR="#6B6B6B">*Apenas os postos do estado selecionado receber�o o comunicado.</FONT></td>
</tr>
*/?>

<tr>
	<td class="table_line">Activo/Inactivo</td>
	<td class="table_line2" nowrap>Activo<INPUT TYPE="radio" NAME="ativo" value='t' <? if (($ativo == 't') OR ($ativo == '')){ echo "checked"; } ?> ></td>
	<td class="table_line2" nowrap>Inactivo<INPUT TYPE="radio" NAME="ativo" value='f' <? if ($ativo == 'f'){ echo "checked"; } ?> ></td>
</tr>


<tr>
	<td class='table_line'>Archivo</td>
	<td class='table_line2' colspan='2'><input type='file' name='arquivo' size='73' class='frm'>
	<?php	
    	if (!empty($hd_chamado_item)) {
	        $tempUniqueId = $hd_chamado_item;
	        $anexoNoHash = null;
	    } else if (strlen($_POST["anexo_chave"]) > 0) {
	        $tempUniqueId = $_POST["anexo_chave"];
	        $anexoNoHash = true;
	    } else {
	        if ($areaAdmin === true) {
	            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
	        } else {
	            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
	        }

	        $anexoNoHash = true;
	    }
        $boxUploader = array(
            "div_id" => "div_anexos",
            "prepend" => $anexo_prepend,
            "titulo_tabela" => 'Archivo',
            "label_botao" => 'adjuntar archivos',
            "context" => "help desk",
            "unique_id" => $tempUniqueId,
            "hash_temp" => $anexoNoHash
        );

        include "../box_uploader.php";
        ?>
	</td>
</tr>
<? if (strlen($comunicado) > 0 AND strlen($extensao) > 0) { ?>
<tr>
	<td class='table_line'>Archivo anterior</td>
	<td class='table_line2' colspan='2'>
		&nbsp;&nbsp;&nbsp;
		<?
		if (is_object($s3)) {
			echo "<a href='JavaScript:void(0);' name='prod_ve' rel='$comunicado/$tipo'>";
			echo "Click aqu� para abrir el archivo";
			echo "</a>";
		} else {
			foreach ($file_types as $type) {
				if (file_exists("comunicados/$comunicado.$type")) {
					echo "<a href='comunicados/$comunicado.$type' target='_blank'>";
					echo "Click aqu� para abrir el archivo";
					echo "</a>";
				}
			}
		}
    ?>
	</td>
</tr>
<tr>
	<td class='table_line2' colspan=3 align='center'>
		<font face='arial' size='-1' color='#6699FF'><b>
				La acci�n de cambio de un comunicado borrara el archivo enviado anteriormente.<br>
		Para que eso no ocurra, Catastre un nuevo comunicado para este producto.
		</b></font>
	</td>
</tr>
<? } ?>
<tr>
	<td class='table_line'>Obligatorio en OS</td>
	<td class='table_line2' colspan='2'><input type='checkbox' name='obrigatorio_os_produto' value='t' <? if ($obrigatorio_os_produto == "t") echo "checked" ?> class='frm'></td>
</tr>
<tr>
	<td class='table_line'>Exhibir en la pantalla inicial del site</td>
	<td class='table_line2' colspan='2'><input type='checkbox' name='obrigatorio_site' value='t' <? if ($obrigatorio_site == "t") echo "checked" ?> class='frm'></td>
</tr>
<tr>
	<td class='table_line2' colspan='3' align='center'>
		<img src='imagens_admin/btn_gravar.gif' onclick='document.frm_comunicado.btn_acao.value="gravar"; document.frm_comunicado.submit()' style='cursor: hand;'>
<?	if (strlen($comunicado) > 0) { ?>
		&nbsp;&nbsp;&nbsp;
		<img border='0' src='imagens_admin/btn_limpar.gif' alt='Haga un click aqu� para borrar.' onclick='document.frm_comunicado.btn_acao.value="apagar"; document.frm_comunicado.apagar.value="<?echo $comunicado?>"; document.frm_comunicado.submit()' style='cursor: hand;'>
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
			echo "<table width='500' align='center' border='0'>";
			for ($i = 0; $i < pg_numrows ($res); $i++){
				$comunicado             = trim(pg_result($res,$i,comunicado));
				$familia                = trim(pg_result($res,$i,familia));
				$produto_referencia     = trim(pg_result($res,$i,prod_referencia));
				$produto_descricao      = trim(pg_result($res,$i,prod_descricao));
				$descricao              = trim(pg_result($res,$i,descricao));
				$extensao               = trim(pg_result($res,$i,extensao));
				$tipo                   = trim(pg_result($res,$i,tipo));
				$mensagem               = trim(pg_result($res,$i,mensagem));
				$data                   = trim(pg_result($res,$i,data));
				$obrigatorio_os_produto = trim(pg_result($res,$i,obrigatorio_os_produto));
				$obrigatorio_site       = trim(pg_result($res,$i,obrigatorio_site));

				echo "<tr>";
				echo "<td align='center' class='table_line2'><b>$descricao</b></td>";
				echo "<td align='center' class='table_line2'><b>$data</b></td>";
				echo "<td align='center'><a href='$PHP_SELF?comunicado=$comunicado'><img src='imagens_admin/btn_listar.gif' alt='Haga un click aqu� para borrar.'></a>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
?>


<table border='0' align='center'>
<tr>
	<td style='color: B1B1B1; font-size: 10px; '>*Caso no seleccione el servicio, todos los servicios recibir�n el comunicado.<br>
	**Solamente ser� enviado el mail de confirmaci�n, caso el servicio sea seleccionado<br><td>
</tr>
</table>


<hr width='700'>

<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'>
<form name='frm_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<input type='hidden' name='btn_acao' value=''>
<tr>
	<td colspan='2' align='center' class='menu_top'>LOCALIZAR COMUNICADOS YA REGISTRADOS</td>
</tr>
<tr>
	<td align='center' class='menu_top'>fecha Inicial</td>
	<td align='center' class='menu_top'>fecha Final</td>
</tr>
<tr>
	<td align='center'><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Haga um click aqu� para abrir el calendario"></center></td>
	<td align='center'><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Haga um click aqu� para abrir el calendario"></center></td>
</tr>
<tr>
	<td align='center' class='menu_top'>Tipo</td>
	<td align='center' class='menu_top'>Descripci�n/T�tulo</td>
</tr>
<tr>
	<td align='center'>
		<select class='frm' name='psq_tipo'>
			<option value=''></option>
			<option value='Boletim'                            <? if ($psq_tipo == "Boletim")                           echo "SELECTED ";?>>Bolet�n</option>
			<option value='Comunicado'                         <? if ($psq_tipo == "Comunicado")                        echo "SELECTED ";?>>Comunicado</option>
			<option value='Com. Unico Posto'                   <? if ($psq_tipo == "Com. Unico Posto")                  echo "SELECTED ";?>>Com. �nico Servicio</option>
			<option value='Informativo'                        <? if ($psq_tipo == "Informativo")                       echo "SELECTED ";?>>Informativo</option>
			<option value='Foto'                               <? if ($psq_tipo == "Foto")                              echo "SELECTED ";?>>Foto</option>
			<option value='Vista Explodida'                    <? if ($psq_tipo == "Vista Explodida")                   echo "SELECTED ";?>>Vista Explodida</option>
			<option value='Esquema El�trico'                   <? if ($psq_tipo == "Esquema El�trico")                  echo "SELECTED ";?>>Esquema El�trico</option>
			<option value='Descritivo t�cnico'                 <? if ($psq_tipo == "Descritivo t�cnico")                echo "SELECTED ";?>>Descriptivo t�cnico</option>
			<option value='Informativo tecnico'                <? if ($tipo == "Informativo tecnico")                   echo "SELECTED ";?>>Informativo tecnico</option> 
			<option value='Manual'                             <? if ($psq_tipo == "Manual")                            echo "SELECTED ";?>>Manual</option>
			<option value='Orienta��o de Servi�o'              <? if ($psq_tipo == "Orienta��o de Servi�o")             echo "SELECTED ";?>>Orientaci�n de servicio</option>
			<option value='Lan�amentos'                        <? if ($psq_tipo == "Lan�amentos")                       echo "SELECTED ";?>>Lanzamientos</option>
			<option value='Procedimentos'                       <? if ($psq_tipo == "Procedimentos")                    echo "SELECTED ";?>>Procedimientos</option>
			<option value='Promocao'                           <? if ($tipo == "Promocao")                              echo "SELECTED ";?>>Promoci�n</option>
			<option value='Estrutura do Produto'               <? if ($tipo == "Estrutura do Produto")                  echo "SELECTED ";?>>Estructura del producto</option> 
		</select>
	</td>
	<td align='center'><input type='text' name='psq_descricao' size='45' value='<? echo $psq_descricao; ?>' class='frm'></td>
</tr>
<tr>
	<td align='center' class='menu_top'>Producto - Referencia</td>
	<td align='center' class='menu_top'>Producto - Descripci�n</td>
</tr>
<tr>
	<td align='center'><input type='text' name='psq_produto_referencia' size='20' class='frm'>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.psq_produto_referencia,document.frm_pesquisa.psq_produto_nome,'referencia')" alt='Haga un click aqui bara buscar por referencia de herramienra' style='cursor:pointer;'></td>
	<td align='center'><input type='text' name='psq_produto_nome'       size='41' class='frm'>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.psq_produto_referencia,document.frm_pesquisa.psq_produto_nome,'descricao')" alt='Haga un click aqui bara buscar por referencia de herramienra' style='cursor:pointer;'></td>
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
	$produto_referencia = $_POST['psq_produto_referencia'];
	$produto_descricao  = $_POST['psq_produto_descricao'];
	$data_inicial 		= $_POST['data_inicial_01'];
	$data_final         = $_POST['data_final_01'];

	if (!strlen($data_inicial) or !strlen($data_final)) {
			$msg_erro["msg"][]    = "Preencha os campos obrigat�rios";
			$msg_erro["campos"][] = "data";
		} else {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);

			if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
				$msg_erro["msg"][]    = "Data Inv�lida";
				$msg_erro["campos"][] = "data";
			} else {
				$xdata_inicial = "{$yi}-{$mi}-{$di}";
				$xdata_final   = "{$yf}-{$mf}-{$df}";

				if (strtotime($xdata_final) < strtotime($xdata_inicial)) {
					$msg_erro["msg"][]    = "Data Final n�o pode ser menor que a Data Inicial";
					$msg_erro["campos"][] = "data";
				}
			}
		}
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
			WHERE	tbl_comunicado.fabrica = $login_fabrica 
			AND      tbl_comunicado.pais = '$login_pais'
			AND tbl_comunicado.data BETWEEN '{$xdata_inicial}'' and '{$xdata_final}'";

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
			$sql .= " AND tbl_comunicado.produto = $produto ";
		}
	}

	$sql .= "ORDER BY tbl_comunicado.data DESC";
	$res = pg_exec ($con,$sql);
//if($ip=="201.27.212.208"){ echo "$sql";}
	if (pg_numrows($res) > 0){
		echo "<table width='700' align='center' border='0'>";
		echo "<tr>";
		echo "<td align='center' class='menu_top'>Tipo</td>";
		echo "<td align='center' class='menu_top'>Descripci�n</td>";
		echo "<td align='center' class='menu_top'>Producto</td>";
		echo "<td align='center' class='menu_top'>Fecha</td>";
		echo "<td align='center' class='menu_top' width='85'>&nbsp;</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			echo "<tr>";

			echo "<td align='left' class='table_line'>";
			echo pg_result ($res,$i,tipo);
			echo "</td>";
			
			echo "<td align='left' class='table_line'>";
			echo "<a href='$PHP_SELF?comunicado=" . pg_result ($res,$i,comunicado) . "'>";
			echo pg_result ($res,$i,descricao);
			echo "</a>";
			echo "</td>";

			echo "<td align='left' class='table_line'>";
			echo pg_result ($res,$i,produto_descricao);
			echo "</td>";

			echo "<td align='left' class='table_line'>";
			echo pg_result ($res,$i,data);
			echo "</td>";

			echo "<td align='left'>";
			echo "<a href='$PHP_SELF?comunicado=" . pg_result ($res,$i,comunicado) . "'>";
			echo "<img src='imagens_admin/btn_alterar_azul_es.gif'>";
			echo "</a>";
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}
}

include "rodape.php";
?>
