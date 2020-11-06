<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_usuario.php';
include 'funcoes.php';

$msg_erro = "";

if (trim($_POST['comunicado']) > 0) $comunicado = trim($_POST['comunicado']);
if (trim($_GET['comunicado']) > 0)  $comunicado = trim($_GET['comunicado']);

$btn_acao = trim (strtolower ($_POST['btn_acao']));

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
	$remetente_email        = trim($_POST['remetente_email']);

	if (strlen($descricao) == 0)  $aux_descricao = "null";
	else                          $aux_descricao = "'". $descricao ."'";
	
	if (strlen($extensao) == 0)   $aux_extensao = "null";
	else                          $aux_extensao = "'". $extensao ."'";

	if (strlen($familia) == 0)    $aux_familia = "null";
	else                          $aux_familia = "'". $familia ."'";
	
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

	if (strlen($produto_referencia) > 0){
		$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$produto_referencia'";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 0) $msg_erro = "Produto $produto_referencia não cadastrado";
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
			$msg_erro = "Código do posto ($codigo_posto) não encontrado";
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
		// Tamanho máximo do arquivo (em bytes) 
		$config["tamanho"] = 4096000;

		// Formulário postado... executa as ações 
		if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

			// Verifica o MIME-TYPE do arquivo
			if (!preg_match("/\/(pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|vnd.ms-powerpoint|zip|x-zip-compressed)$/", $arquivo["type"])){
				$msg_erro = "Arquivo em formato inválido!";
			} else {
				// Verifica tamanho do arquivo 
				if ($arquivo["size"] > $config["tamanho"]) 
					$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 4MB. Envie outro arquivo.";
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
									extensao  = $aux_extensao
								WHERE comunicado = $comunicado
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
	
	$produto_referencia     = $_POST["produto_referencia"];
	$produto_descricao      = $_POST["produto_descricao"];
	$descricao              = $_POST['descricao'];
	$extensao               = $_POST['extensao'];
	$tipo                   = $_POST['tipo'];
	$mensagem               = $_POST['mensagem'];
	$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
	$obrigatorio_site       = $_POST['obrigatorio_site'];
	
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
	$produto_descricao      = $_POST["produto_descricao"];
	$descricao              = $_POST['descricao'];
	$extensao               = $_POST['extensao'];
	$tipo                   = $_POST['tipo'];
	$mensagem               = $_POST['mensagem'];
	$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
	$obrigatorio_site       = $_POST['obrigatorio_site'];
	
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
$title = "Cadastramento de Comunicados / Vistas Explodidas / Fotos / Boletins";

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
	<td colspan=3 align='center' class="menu_top">Campos em NEGRITO são obrigatórios.</td>
</tr>

<tr>
	<td class='table_line'><B>Comunicado tipo</B></td>
	<td class='table_line2'>
		<select class='frm' name='tipo'>
			<option value=''>ESCOLHA</option>
			<option value='Com. Unico Posto'            <? if ($tipo == 'Com. Unico Posto')   echo " SELECTED ";?>>Com. Unico Posto</option>
			<option value='Boletim'                     <? if ($tipo == "Boletim")            echo " SELECTED ";?>>Boletim</option>
			<option value='Comunicado'                  <? if ($tipo == "Comunicado")         echo " SELECTED ";?>>Comunicado</option>
			<option value='Informativo'                 <? if ($tipo == "Informativo")        echo " SELECTED ";?>>Informativo</option>
			<option value='Foto'                        <? if ($tipo == "Foto")               echo " SELECTED ";?>>Foto</option>
			<option value='Vista Explodida'             <? if ($tipo == "Vista Explodida")    echo " SELECTED ";?>>Vista Explodida</option>
			<option value='Esquema Elétrico'            <? if ($tipo == "Esquema Elétrico")   echo " SELECTED ";?>>Esquema Elétrico</option>
			<option value='Descritivo técnico'          <? if ($tipo == "Descritivo técnico") echo " SELECTED ";?>>Descritivo técnico</option>
			<option value='Manual'                      <? if ($tipo == "Manual")             echo " SELECTED ";?>>Manual</option>
			<option value='Orientação de Serviço'       <? if ($tipo == "Orientação Serviço") echo " SELECTED ";?>>Orientação Serviço</option>
			<option value='Lançamentos'                 <? if ($tipo == "Lançamentos")        echo " SELECTED ";?>>Lançamentos</option>
			<option value='Procedimento'                <? if ($tipo == "Procedimento")       echo " SELECTED ";?>>Procedimento</option>
			<option value='Promocao'                    <? if ($tipo == "Promocao")           echo " SELECTED ";?>>Promoção</option>
		</select> 
	</td>
	<td class='table_line2'><FONT COLOR="#6B6B6B">*<B>Com. Unico Posto:</B> Selcionando essa opção será <U>obrigatório</U> a escolha do posto.</FONT></td>
</tr>
<tr>
	<td class='table_line'>Produto</td>
	<td class='table_line2'>Ref:&nbsp;<input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia,document.frm_comunicado.produto_descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
	<td class='table_line2'>Descrição:&nbsp;<input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="45" maxlength="50" class='frm'>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia,document.frm_comunicado.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></td>
</tr>
<tr>

	<td class='table_line'>Família</td>
	<td class='table_line2' colspan='2'>
<?
		##### INÍCIO FAMÍLIA #####
		$sql = "SELECT  *
				FROM    tbl_familia
				WHERE   tbl_familia.fabrica = $login_fabrica
				ORDER BY tbl_familia.descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='frm' style='width: 280px;' name='familia'>\n";
			echo "<option value=''>ESCOLHA</option>\n";
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_familia = trim(pg_result($res,$x,familia));
				$aux_descricao  = trim(pg_result($res,$x,descricao));
				echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
		##### FIM FAMÍLIA #####
?>
	</td>
</tr>
<tr>
	<td class='table_line'><B>Título</B></td>
	<td class='table_line2' colspan='2'><input type='text' name='descricao' value='<?echo $descricao?>' size='85' maxlength='50' class='frm'></td>
</tr>
<tr>
	<td class='table_line'>Mensagem</td>
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
	<td class="table_line">Selecionar o Posto</td>
	<td class="table_line2" colspan='2' nowrap>Cod.:<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'codigo')"></A>
		&nbsp;Razao Social.:<input class="frm" type="text" name="posto_nome" size="47" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_comunicado.codigo_posto,document.frm_comunicado.posto_nome,'nome')" style="cursor:pointer;"><br><br>
		Email Conf. de leitura:&nbsp;<INPUT TYPE="text" size='40' value='<?echo $remetente_email?>' class='frm' NAME="remetente_email"></A></td>
</tr>
<!-- fim pesquisa de postos ========================================================================-->



<tr>
	<td class='table_line'>Arquivo</td>
	<td class='table_line2' colspan='2'><input type='file' name='arquivo' size='73' class='frm'></td>
</tr>
<? if (strlen($comunicado) > 0 AND strlen($extensao) > 0) { ?>
<tr>
	<td class='table_line'>Arquivo anterior</td>
	<td class='table_line2' colspan='2'>
		&nbsp;&nbsp;&nbsp;
		<a href='../comunicados/<? echo $comunicado.".".$extensao; ?>' target='_blank'>Abrir arquivo</a>
		<img src='imagens/btn_apagararquivo.gif' alt='Clique aqui para apagar somente o arquivo anexado.' onclick='document.frm_comunicado.btn_acao.value="apagararquivo" ; document.frm_comunicado.apagar.value="<? echo $comunicado; ?>" ; document.frm_comunicado.submit()' style='cursor:pointer;'>
	</td>
</tr>
<tr>
	<td class='table_line2' colspan=3 align='center'>
		<font face='arial' size='-1' color='#6699FF'><b>
		A ação de alteração de um comunicado acarretará na exclusão do arquivo anteriormente enviado.<br>
		Para que isso não ocorra, lance um novo comunicado para este produto.
		</b></font>
	</td>
</tr>
<? } ?>
<tr>
	<td class='table_line'>Obrigatório na OS</td>
	<td class='table_line2' colspan='2'><input type='checkbox' name='obrigatorio_os_produto' value='t' <? if ($obrigatorio_os_produto == "t") echo "checked" ?> class='frm'></td>
</tr>
<tr>
	<td class='table_line'>Exibir na tela de entrada do site</td>
	<td class='table_line2' colspan='2'><input type='checkbox' name='obrigatorio_site' value='t' <? if ($obrigatorio_site == "t") echo "checked" ?> class='frm'></td>
</tr>
<tr>
	<td class='table_line2' colspan='3' align='center'>
		<img src='imagens_admin/btn_gravar.gif' onclick='document.frm_comunicado.btn_acao.value="gravar"; document.frm_comunicado.submit()' style='cursor: hand;'>
<?	if (strlen($comunicado) > 0) { ?>
		&nbsp;&nbsp;&nbsp;
		<img border='0' src='imagens_admin/btn_apagar.gif' alt='Clique aqui para apagar.' onclick='document.frm_comunicado.btn_acao.value="apagar"; document.frm_comunicado.apagar.value="<?echo $comunicado?>"; document.frm_comunicado.submit()' style='cursor: hand;'>
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
				echo "<td align='center'><a href='$PHP_SELF?comunicado=$comunicado'><img src='imagens_admin/btn_listar.gif' alt='Clique aqui para alterar.'></a>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
?>


<table border='0' align='center'>
<tr>
	<td style='color: B1B1B1; font-size: 10px; '>*Se <B><U>não</U></B> for selecionado o posto, todos os postos receberam o comunicado<br>
	**Só será enviado o e-mail de confirmação se for selecionado o posto<br><td>
</tr>
</table>


<hr width='700'>

<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'>
<form name='frm_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<input type='hidden' name='btn_acao' value=''>
<tr>
	<td colspan='2' align='center' class='menu_top'>LOCALIZAR COMUNICADOS JÁ CADASTRADOS</td>
</tr>
<tr>
	<td align='center' class='menu_top'>Tipo</td>
	<td align='center' class='menu_top'>Descrição/Título</td>
</tr>
<tr>
	<td align='center'>
		<select class='frm' name='psq_tipo'>
			<option value=''></option>
			<option value='Boletim'                            <? if ($psq_tipo == "Boletim")                           echo "SELECTED ";?>>Boletim</option>
			<option value='Comunicado'                         <? if ($psq_tipo == "Comunicado")                        echo "SELECTED ";?>>Comunicado</option>
			<option value='Com. Unico Posto'                   <? if ($psq_tipo == "Com. Unico Posto")                  echo "SELECTED ";?>>Comunicado para Determinado Posto</option>
			<option value='Informativo'                        <? if ($psq_tipo == "Informativo")                       echo "SELECTED ";?>>Informativo</option>
			<option value='Foto'                               <? if ($psq_tipo == "Foto")                              echo "SELECTED ";?>>Foto</option>
			<option value='Vista Explodida'                    <? if ($psq_tipo == "Vista Explodida")                   echo "SELECTED ";?>>Vista Explodida</option>
			<option value='Esquema Elétrico'                   <? if ($psq_tipo == "Esquema Elétrico")                  echo "SELECTED ";?>>Esquema Elétrico</option>
			<option value='Descritivo técnico'                 <? if ($psq_tipo == "Descritivo técnico")                echo "SELECTED ";?>>Descritivo técnico</option>
			<option value='Manual'                             <? if ($psq_tipo == "Manual")                            echo "SELECTED ";?>>Manual</option>
			<option value='Orientação de Serviço'              <? if ($psq_tipo == "Orientação de Serviço")             echo "SELECTED ";?>>Orientação de Serviço</option>
			<option value='Lançamentos'                        <? if ($psq_tipo == "Lançamentos")                       echo "SELECTED ";?>>Lançamentos</option>
			<option value='Procedimento'                       <? if ($psq_tipo == "Procedimento")                      echo "SELECTED ";?>>Procedimento</option>
			<option value='Promocao'                           <? if ($tipo == "Promocao")                              echo "SELECTED ";?>>Promoção</option>
		</select>
	</td>
	<td align='center'><input type='text' name='psq_descricao' size='45' value='<? echo $psq_descricao; ?>' class='frm'></td>
</tr>
<tr>
	<td align='center' class='menu_top'>Produto - Referência</td>
	<td align='center' class='menu_top'>Produto - Descrição</td>
</tr>
<tr>
	<td align='center'><input type='text' name='psq_produto_referencia' size='20' class='frm'>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.psq_produto_referencia,document.frm_pesquisa.psq_produto_nome,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
	<td align='center'><input type='text' name='psq_produto_nome'       size='41' class='frm'>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.psq_produto_referencia,document.frm_pesquisa.psq_produto_nome,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></td>
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

	if (pg_numrows($res) > 0){
		echo "<table width='700' align='center' border='0'>";
		echo "<tr>";
		echo "<td align='center' class='menu_top'>Tipo</td>";
		echo "<td align='center' class='menu_top'>Descrição</td>";
		echo "<td align='center' class='menu_top'>Produto</td>";
		echo "<td align='center' class='menu_top'>Data</td>";
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
