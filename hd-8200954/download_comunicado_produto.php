<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$titulo = "Comunicados / Vistas Explodidas / Fotos / Boletins";
$title  = "Comunicados / Vistas Explodidas / Fotos / Boletins";

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

<body>
	<DIV ID='wrapper'>

		<form name="frm_comunicado" method="post" action="<? $PHP_SELF ?>">

			<font face='arial' size='-1' color='#6699FF'><b>Para pesquisar um produto, informe parte da referência ou descrição do produto.</b></font>

<?

$msg_erro = "";

$btn_acao = trim (strtolower ($_POST['btn_acao']));

if (trim($btn_acao) == "buscar informações do produto") {
	$sql = "SELECT  tbl_comunicado.comunicado                        ,
					tbl_produto.referencia AS prod_referencia        ,
					tbl_produto.descricao  AS prod_descricao         ,
					tbl_comunicado.descricao                         ,
					tbl_comunicado.extensao                          ,
					tbl_comunicado.tipo                              ,
					tbl_comunicado.mensagem                          ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
			FROM    tbl_comunicado
			JOIN    tbl_produto USING (produto) 
			JOIN    tbl_linha   USING (linha)
			WHERE   tbl_linha.fabrica         = $login_fabrica ";

	// BUSCA POR REFERENCIA DIGITADA DO PRODUTO
	if (strlen($_POST['referencia']) > 0) {

		$referencia = $_POST['referencia'];

		$sql .= "AND	tbl_produto.referencia = '$referencia'
				ORDER BY tbl_comunicado.data DESC";
		$res = pg_exec ($con,$sql);
	
		if (pg_numrows ($res) == 0) {
			$msg_erro = "Produto $referencia não cadastrado";
		}else{
			$descricao = pg_result ($res,0,0);
		}
	}

	// BUSCA POR DESCRICAO DIGITADA DO PRODUTO
	elseif (strlen($_POST['descricao']) > 0) {

		$descricao = $_POST['descricao'];

		$sql .= "AND	tbl_produto.descricao = '$descricao'
				ORDER BY tbl_comunicado.data DESC";
		$res = pg_exec ($con,$sql);
	
		if (pg_numrows ($res) == 0) {
			$msg_erro = "Produto $descricao não cadastrado";
		}else{
			$descricao = pg_result ($res,0,0);
		}
	}
	else{
		$msg_erro = "Produto não encontrado";
	}
	if (strlen($msg_erro) > 0) { 
?>
	<div id="wrapper">
		<center><b><? echo $msg_erro; ?></b></center>
	</div>
<?
	}

}

?>

	<table width='400' align='center' border='0'>
		<tr>
			<td align='center'>
				<b>Referência</b>
			</td>
			<td align='center'>
				<b>Descrição</b>
			</td>
		</tr>
		<tr>
			<td align='center'>
				<input type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20">&nbsp;<a href='#'><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.referencia,document.frm_comunicado.descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto'></a>
			</td>
			<td align='center'>
				<input type="text" name="descricao" value="<? echo $descricao ?>" size="50" maxlength="50">&nbsp;<a href='#'><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.referencia,document.frm_comunicado.descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto'></a>
			</td>
		</tr>
	</table>

	<p align='center'>
		<input type='submit' name='btn_acao' value='Buscar informações do produto'>
	<br><br>

<?

if ((strlen($_POST['referencia']) > 0) || (strlen($_POST['descricao']) > 0)){	

	if (pg_numrows ($res) > 0) {

		// HTML ONDE LISTA OS PRODUTOS DE ACORDO DOM O QUE FOI DIGITADO
		echo "<table width='500' align='center' border='0'>";
		echo "	<tr bgcolor='#cccccc'>";
		echo "		<td width='80' align='center'><b>Data</b></td>";
		echo "		<td width='340'><b>Título</b></td>";
		echo "		<td width='80' align='center'><b>Download</b></td>";
		echo "	</tr>";
	
		if ($S3_sdk_OK) {
			include_once S3CLASS;
			$s3 = new anexaS3('ve', (int) $login_fabrica);
		}

		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$comunicado           = trim(pg_result($res,$i,comunicado));
			$comunicado_descricao = trim(pg_result($res,$i,descricao));
			$comunicado_extensao  = trim(pg_result($res,$i,extensao));
			$comunicado_tipo      = trim(pg_result($res,$i,tipo));
			$comunicado_mensagem  = trim(pg_result($res,$i,mensagem));
			$comunicado_data      = trim(pg_result($res,$i,data));

			if ($S3_online) {
				$tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
				if ($s3->tipo_anexo != $tipo_s3)
					$s3->set_tipo_anexoS3($tipo_s3);
				$s3->temAnexos($comunicado);
			}
		
			if ($S3_online and $s3->temAnexo) {
				$img = $s3->url;
			} else {
				if ($comunicado_extensao == "gif")
					$img = "comunicados/$comunicado.gif";
				if ($comunicado_extensao == "jpg")
					$img = "comunicados/$comunicado.jpg";
				if ($comunicado_extensao == "pdf")
					$img = "comunicados/$comunicado.pdf";

				if (!file_exists($img))
					$img = '#';
			}

			echo "	<tr bgcolor='#cccccc'>\n";
			echo "		<td align='center'>$comunicado_data</td>";
			echo "		<td>$comunicado_descricao</td>";
			echo "		<td align='center'><a href=\"$img\" target='_blank'><img src=\"imagens/download.gif\" width=80 height=20></a></td>";
			echo "	</tr>";
		}
		echo "</table>";
	}
}
?>

</form>
</div>

<? include "rodape.php"; ?>
