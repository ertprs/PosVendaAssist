<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";

if (trim($_POST['comunicado']) > 0) $comunicado_supervisor = trim($_POST['comunicado_supervisor']);
if (trim($_GET['comunicado']) > 0)  $comunicado_supervisor = trim($_GET['comunicado_supervisor']);


$btn_acao = trim (strtolower ($_POST['btn_acao']));

if (trim($btn_acao) == "gravar") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$descricao              = trim($_POST['titulo']);
	$mensagem               = trim($_POST['mensagem']);
	$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;


	if (strlen($descricao) == 0)  $aux_titulo = "null";
	else                          $aux_titulo = "'". $descricao ."'";
	
	if (strlen($mensagem) == 0)   $aux_mensagem = "null";
	else                          $aux_mensagem = "'". $mensagem ."'";
	
	if (strlen($msg_erro) == 0) {
		if (strlen($comunicado_supervisor) == 0) {
			$sql = "INSERT INTO tbl_comunicado_supervisor (
						titulo                 ,
						mensagem               ,
						fabrica
					) VALUES (
						$aux_titulo                 ,
						$aux_mensagem               ,
						$login_fabrica
					);";
		}else{
			$sql = "UPDATE tbl_comunicado_supervisor SET
						titulo                 = $aux_titulo     ,
						mensagem               = $aux_mensagem
					WHERE comunicado_supervisor = $comunicado_supervisor
					AND   fabrica    = $login_fabrica";
		}

		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen($msg_erro) == 0) {
		if (strlen($comunicado_supervisor) == 0) {
			$res                   = pg_exec ($con,"SELECT currval ('tbl_comunicado_supervisor_seq')");
			$comunicado_supervisor = pg_result ($res,0,0);
		}
	}

	if (strlen ($msg_erro) == 0) {
		///////////////////////////////////////////////////
		// Rotina que faz o upload do arquivo
		///////////////////////////////////////////////////
		// Tamanho máximo do arquivo (em bytes) 
		$config["tamanho"] = 2048000;

		// Formulário postado... executa as ações 
		if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

			// Verifica o mime-type do arquivo
			if (!preg_match("/\/(pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|zip|x-zip-compressed)$/", $arquivo["type"])){
				$msg_erro = "Arquivo em formato inválido!";
			} else {
				// Verifica tamanho do arquivo 
				if ($arquivo["size"] > $config["tamanho"])
					$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
			}

			if (strlen($msg_erro) == 0) {
				// Pega extensão do arquivo
				preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt|zip){1}$/i", $arquivo["name"], $ext);
				$aux_extensao = "'".$ext[1]."'";

				// Gera um nome único para a imagem
				$nome_anexo = $comunicado_supervisor.".".$ext[1];
//echo $nome_anexo;
				// Caminho de onde a imagem ficará + extensao
				$imagem_dir = "../comunicados_supervisor/".strtolower($nome_anexo);

				// Exclui anteriores, qquer extensao
				//@unlink($imagem_dir);

				// Faz o upload da imagem
				if (strlen($msg_erro) == 0) {
					//move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
					if (copy($arquivo["tmp_name"], $imagem_dir)) {
						$sql =	"UPDATE tbl_comunicado_supervisor SET
									arquivo  = '$nome_anexo'
								WHERE comunicado_supervisor = $comunicado_supervisor
								AND   fabrica    = $login_fabrica";
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}else{
						$msg_erro = "Arquivo não foi enviado!!!";
					}
				}

			}
		}
	}

	///////////////////////////////////////////////////
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}
	
	$comunicado_supervisor    = $_POST["produto_descricao"];
	$titulo                   = $_POST['titulo'];
	$mensagem                 = $_POST['mensagem'];
	$data                     = $_POST['data'];
	$arquivo                  = $_POST['arquivo'];
	
	$res = pg_exec ($con,"ROLLBACK TRANSACTION");
}


if (strlen($comunicado_supervisor) > 0) {
	$sql = "SELECT  comunicado_supervisor      ,
					titulo                     ,
					mensagem                   ,
					arquivo                    ,
					TO_CHAR(data_envio,'DD/MM/YYYY') AS data_envio
			FROM    tbl_comunicado_supervisor
			WHERE   comunicado_supervisor = $comunicado_supervisor
			AND     fabrica = $login_fabrica";
			
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) > 0) {
		$comunicado_supervisor  = trim(pg_result($res,0,comunicado_supervisor));
		$data                   = trim(pg_result($res,0,data_envio));
		$descricao              = trim(pg_result($res,0,titulo));
		$mensagem               = trim(pg_result($res,0,mensagem));
		$arquivo                = trim(pg_result($res,0,arquivo));
		$btn_lista = "ok";
	}
}

if (trim($btn_acao) == "apagar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$comunicado_supervisor = $_POST["apagar"];

	$sql = "SELECT arquivo FROM tbl_comunicado_supervisor WHERE tbl_comunicado_supervisor.comunicado_supervisor = $comunicado_supervisor";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	//if (strlen($msg_erro) == 0) {
	//	$extensao = @pg_result($res,0,0);
//	}
	
	$sql = "DELETE  FROM tbl_comunicado_supervisor WHERE tbl_comunicado_supervisor.comunicado_supervisor = $comunicado_supervisor";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro) == 0) {
		$imagem_dir = "../comunicados_supervisor/".$arquivo;
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
	
	$comunicado_supervisor    = $_POST["produto_descricao"];
	$titulo                   = $_POST['titulo'];
	$mensagem                 = $_POST['mensagem'];
	$data                     = $_POST['data'];
	$arquivo                  = $_POST['arquivo'];
	
	$res = pg_exec ($con,"ROLLBACK TRANSACTION");
}

if (trim($btn_acao) == "apagararquivo") {

	$comunicado_supervisor = $_POST["apagar"];

	$sql = "SELECT arquivo FROM tbl_comunicado_supervisor WHERE comunicado_supervisor = $comunicado_supervisor";
	$res = pg_exec ($con,$sql);

	$imagem_dir = "../comunicados_supervisor/".pg_result($res,0,0);

	$sql = "UPDATE tbl_comunicado_supervisor SET arquivo = null WHERE comunicado_supervisor = $comunicado_supervisor";
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

$layout_menu = "auditoria";
$titulo = "Cadastramento de Comunicados de Supervisores";
$title = "Cadastramento de Comunicados de Supervisores";

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
.ERRO{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#ff0000;
}
</style>

<body>

<form enctype = "multipart/form-data" name="frm_comunicado" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="MAX_FILE_SIZE" value="2000000">
<input type="hidden" name="comunicado_supervisor"    value="<? echo $comunicado_supervisor ?>">
<input type='hidden' name='apagar'        value=''>
<input type='hidden' name='btn_acao'      value=''>

<table width='700' border='0' cellpadding='5' cellspacing='3' align='center'>

<? if (strlen($msg_erro) > 0) { ?>
<tr>
	<td class='error' colspan=3 align='center'><? echo $msg_erro; ?></td>
</tr>
<? } ?>

<tr>
	<td colspan=3 align='center' class="menu_top">Campos em NEGRITO são obrigatórios.</td>
</tr>

<tr>
	<td class='table_line'><B>Título</B></td>
	<td class='table_line2' colspan='2'><input type='text' name='titulo' value='<?echo $descricao?>' size='85' maxlength='50' class='frm'></td>
</tr>
<tr>
	<td class='table_line'>Mensagem</td>
	<td class='table_line2' colspan='2'><textarea name='mensagem' cols='85' rows='5' class='frm'><? echo $mensagem?></textarea></td>
</tr>
<tr>
	<td class='table_line'>Arquivo</td>
	<td class='table_line2' colspan='2'><input type='file' name='arquivo' size='73' class='frm'></td>
</tr>
<? if (strlen($comunicado_supervisor) > 0 AND strlen($arquivo) > 0) { ?>
<tr>
	<td class='table_line'>Arquivo anterior</td>
	<td class='table_line2' colspan='2'>
		&nbsp;&nbsp;&nbsp;
		<a href='../comunicados_supervisor/<? echo $arquivo; ?>' target='_blank'>Abrir arquivo</a>
		<img src='imagens/btn_apagararquivo.gif' alt='Clique aqui para apagar somente o arquivo anexado.' onclick='document.frm_comunicado.btn_acao.value="apagararquivo" ; document.frm_comunicado.apagar.value="<? echo $comunicado_supervisor; ?>" ; document.frm_comunicado.submit()' style='cursor:pointer;'>
	</td>
</tr>
<tr>
	<td class='table_line2' colspan=3 align='center'>
		<font face='arial' size='-1' color='#6699FF'><b>
		A ação de alteração de um comunicado acarretará na exclusão do arquivo anteriormente enviado.<br>
		Para que isso não ocorra, lance um novo Comunicado para o Supervisor.
		</b></font>
	</td>
</tr>
<? } ?>

<tr>
	<td class='table_line2' colspan='3' align='center'>
		<img src='imagens_admin/btn_gravar.gif' onclick='document.frm_comunicado.btn_acao.value="gravar"; document.frm_comunicado.submit()' style='cursor: hand;'>
<?	if (strlen($comunicado_supervisor) > 0) { ?>
		&nbsp;&nbsp;&nbsp;
		<img border='0' src='imagens_admin/btn_apagar.gif' alt='Clique aqui para apagar.' onclick='document.frm_comunicado.btn_acao.value="apagar"; document.frm_comunicado.apagar.value="<?echo $comunicado_supervisor?>"; document.frm_comunicado.submit()' style='cursor: hand;'>
<?	} ?>
	</td>
</tr>
</table>
</FORM>
	


<hr width='700'>

<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'>
<form name='frm_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<input type='hidden' name='btn_acao' value=''>
<tr>
	<td colspan='2' align='center' class='menu_top'>LOCALIZAR COMUNICADOS JÁ CADASTRADOS</td>
</tr>
<tr>
	<td align='center' colspan='2'class='menu_top'>Descrição/Título</td>
</tr>
<tr>
	<td align='center' colspan='2'><input type='text' name='psq_descricao' size='45' value='<? echo $psq_descricao; ?>' class='frm'></td>
</tr>
<tr>
	<td align='center' colspan='2'><img src='imagens/btn_continuar.gif' onclick='document.frm_pesquisa.btn_acao.value="pesquisar"; document.frm_pesquisa.submit()' style='cursor:pointer;'></td>
</tr>
</form>
</table>

<br>

<?
if (trim($btn_acao) == "pesquisar") {
	$titulo          = $_POST['titulo'];
	#--------------------------------------------------------
	#  Mostra todos os informativos cadastrados
	#--------------------------------------------------------
	
	$sql = "SELECT	comunicado_supervisor      ,
					titulo                     ,
					mensagem                   ,
					arquivo                    ,
					TO_CHAR(data_envio,'DD/MM/YYYY') AS data_envio
			FROM	tbl_comunicado_supervisor
		 	WHERE   fabrica=$login_fabrica";
		if (strlen($psq_descricao) > 0){
			$sql .= " AND tbl_comunicado_supervisor.titulo ILIKE '%$psq_descricao%'";
		}
	$sql .= " ORDER BY comunicado_supervisor DESC";


	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0){
		echo "<table width='700' align='center' border='0'>";
		echo "<tr>";
		echo "<td align='center' class='menu_top'>Titulo</td>";
		echo "<td align='center' class='menu_top'>Mensagem</td>";
		echo "<td align='center' class='menu_top'>Data</td>";
		echo "<td align='center' class='menu_top' width='85'>&nbsp;</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			echo "<tr>";

  			echo "<td align='left' class='table_line'>";
			echo "<a href='$PHP_SELF?comunicado_supervisor=" . pg_result ($res,$i,comunicado_supervisor) . "'>";
			echo pg_result ($res,$i,titulo);
			echo "</a>";
			echo "</td>";

			echo "<td align='left' class='table_line'width='300'>";
			echo pg_result ($res,$i,mensagem);
			echo "</td>";
			
			echo "<td align='left' class='table_line'width='50'>";
			echo pg_result ($res,$i,data_envio);
			echo "</td>";

			echo "<td align='left'>";
			echo "<a href='$PHP_SELF?comunicado_supervisor=" . pg_result ($res,$i,comunicado_supervisor) . "'>";
			echo "<img src='imagens/btn_alterar_azul.gif'>";
			echo "</a>";
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}//else echo '<span class="ERRO">NENHUM DOCUMENTO CADASTRADO</span>';
}

include "rodape.php";
?>
