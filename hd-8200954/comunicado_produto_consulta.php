<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";
if ($login_fabrica==3) { // HD 56709
	include 'comunicado_mostra.php';
	exit;
}

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new anexaS3('co', (int) $login_fabrica);
}

$btn_ajuda = $_POST['btn_ajuda'];
if (strlen($btn_ajuda)>0 and isFabrica(3)) {
	$produto     = $_POST['produto'];
	$ajuda_texto = $_POST['ajuda_texto'];
	$sua_os      = $_POST['sua_os'];
	$serie       = $_POST['serie'];
	$defeito_constatado = $_POST['defeito_constatado'];
	$xajuda_texto = "OS: $sua_os\n Série: $serie\n Defeito Constatado: $defeito_constatado \n \nPontos verificados pelo técnico: $ajuda_texto";
	if (strlen($ajuda_texto)>0 and strlen($produto)>0 and strlen($sua_os)>0) {
		$sql = "INSERT into tbl_comunicado(
					descricao  ,
					mensagem   ,
					data       ,
					tipo       ,
					fabrica    ,
					ativo      ,
					pais       ,
					posto      ,
					produto
				)values(
					'Ajuda Suporte Tecnico',
					'$xajuda_texto'        ,
					current_timestamp      ,
					'Ajuda Suporte Tecnico',
					$login_fabrica         ,
					't'                    ,
					'BR'                   ,
					$login_posto           ,
					$produto
				)";
		//echo $sql;
		$res = pg_exec($con,$sql);
		$msg_erro = "Solicitação enviada com sucesso!";
	}else{
		$msg_erro = "Por favor insira o problema, e digite a ordem de serviço.";
	}

}

$btn_acao = trim(strtoupper($_REQUEST["btn_acao"]));

if (strlen($btn_acao) > 0 && strtoupper($btn_acao) == "CONSULTAR") {
	$opcao = $_REQUEST["opcao"];

	if (strlen($opcao) == 0){
		if ($sistema_lingua == 'ES')
			$msg_erro .= " Elija una opción deseada. ";
		else
			$msg_erro .= " Selecione uma opção desejada. ";
	}
	if ($opcao == "1" && strlen($msg_erro) == 0) {
		$produto_referencia = trim($_REQUEST["produto_referencia"]);
		if (strlen($produto_referencia) == 0) {
			if($sistema_lingua == "ES") $msg_erro = " Informe la referencia del producto deseado. ";
			else                        $msg_erro .= " Informe a Referência do Produto desejado. ";
		}
		$produto_descricao = trim($_REQUEST["produto_descricao"]);
		if (strlen($produto_descricao) == 0) {
			if($sistema_lingua == "ES") $msg_erro = " Informe la descripción del producto deseado";
			else                        $msg_erro .= " Informe a Descrição do Produto desejado. ";
		}
		$produto_voltagem = trim($_POST["produto_voltagem"]);
	}
}

$title = "Comunicados $login_fabrica_nome";
$layout_menu = "tecnica";

include 'cabecalho.php';

?>


<script language="JavaScript">
function fnc_pesquisa_produto (campo1, campo2, tipo, campo3) {
	if (tipo == "referencia") {
		var xcampo = campo1;
	}
	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}
	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo1;
		janela.descricao    = campo2;
		if (campo3 != "") {
			janela.voltagem = campo3;
		}
		janela.focus();
	}
}

function fnc_pesquisa_os (campo1) {
	if (campo1.value != "") {

		var url = "";
		url = "produto_pesquisa_os.php?sua_os=" + campo1.value;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.sua_os   = campo1;
		janela.serie    = document.frm_ajuda.serie;
		janela.defeito_constatado = document.frm_ajuda.defeito_constatado;
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
	background-color: #D9E2EF
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.textarea {border: 1px solid #3b4274;}
</style>

<? if (strlen($msg_erro) > 0) { ?>
<div class="alerts">
    <div class="alert danger  margin-top"><?=$msg_erro;?></div>
</div>
<? } ?>
<br />
<form name="frm_comunicado" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="btn_acao">

<table width="500" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="menu_top">
		<td colspan="6"><? if ($sistema_lingua == "ES")  echo "CONSULTA DE COMUNICADOS";else echo "CONSULTA DE COMUNICADOS";?></td>
	</tr>
	<tr class="table_line">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td width="10">&nbsp;</td>
		<td ><input type="radio" name="opcao" value="1" class="frm" <? if  ($opcao == "1")  echo "checked"; ?>><? if($sistema_lingua =="ES") echo "Por producto";else echo " Por Produto";?></td>
		<td ><? if ($sistema_lingua=="ES")  echo "Referencia";else echo "Referência";?></td>
		<td ><? if ($sistema_lingua=="ES")  echo "Descripción";else echo "Descrição";?></td>
		<td ><? if ($sistema_lingua=="ES")  echo "Voltaje";else echo "Voltagem";?></td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><input type="text" name="produto_referencia" size="8" <? if  (isFabrica(5))  { ?> onblur="fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'referencia', document.frm_comunicado.produto_voltagem)" <? } ?> class="frm" value="<?echo $produto_referencia?>"> <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: pointer;" align="absmiddle" alt="<? if($sistema_lingua=="ES") echo "Llene las opciones e click aquí para buscar"; else echo "Clique aqui para pesquisar postos pelo código";?>" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'referencia', document.frm_comunicado.produto_voltagem)"></td>
		<td><input type="text" name="produto_descricao" size="18" <? if  (isFabrica(5))  { ?> onblur="fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'descricao', document.frm_comunicado.produto_voltagem)" <? } ?> class="frm" value="<?echo $produto_descricao?>"> <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: pointer;" align="absmiddle" alt="<? if($sistema_lingua=="ES") echo "Llene las opciones e click aquí para buscar";else echo "Clique aqui para pesquisas pela referência do aparelho.";?>" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'descricao', document.frm_comunicado.produto_voltagem)"></td>
		<td><input type="text" name="produto_voltagem" size="7" class="frm" value="<?echo $produto_voltagem?>"></td>
		<td>&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="table_line">
		<td width="10">&nbsp;</td>
		<td ><input type="radio" name="opcao" value="2" class="frm" <? if ($opcao == "2") echo "checked"; ?>><?=ucfirst(traduz('administrativos', $con))?></td>
		<td colspan='3'>&nbsp;</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td colspan="6">&nbsp;</td>
	</tr>
<? if (isFabrica(3)) { ?>
	<tr class="table_line">
		<td width="10">&nbsp;</td>
		<td ><input type="radio" name="opcao" value="3" class="frm" <? if ($opcao == "3") echo "checked"; ?>> Tipo</td>
		<td colspan='3'>&nbsp;</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td width="10" colspan="2">&nbsp;</td>
		<td colspan="4">
<select class='frm' name='psq_tipo'>
	<option value=''></option>
<?
	$sql = "SELECT DISTINCT tipo
			FROM tbl_comunicado
			WHERE fabrica = $login_fabrica
			AND ativo='t' and tipo <> 'Ajuda Suporte Tecnico'
			ORDER BY tipo;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res)>0) {
		for ($x=0;pg_numrows($res)>$x;$x++) {
			$tipo_comunicado = pg_result($res,$x,tipo);
			echo "<option value='$tipo_comunicado'>";
			if (isFabrica(3) ) {
				if ($tipo_comunicado =='Comunicado')  {
					echo "Comunicado Técnico";
				}elseif ($tipo_comunicado =='Informativo') {
					echo "Comunicado Administrativo";
				}else {
					echo "$tipo_comunicado";
				}
			}else {
				echo "$tipo_comunicado";
			}
			echo "</option>";
		}
	}
?>
</select>
</td>
	</tr>
<? } ?>
	<tr class="table_line">
		<td colspan="6"><center><img border="0" src="<?if ($sistema_lingua=='ES')  echo "imagens/btn_pesquisar_comunicado_es.gif"; else echo "imagens_admin/btn_pesquisar_400.gif";?>" onclick="document.frm_comunicado.btn_acao.value='CONSULTAR'; document.frm_comunicado.submit();" style="cursor: pointer;" alt="<? if($sistema_lingua=="ES") echo "Llene las opciones e click aquí para buscar";else "Preencha as opções e clique aqui para pesquisar";?>"></center></td>
	</tr>

</table>

</form>

<?

//Pega o tipo do posto=================================================================
	$sql2 = "SELECT tbl_posto_fabrica.codigo_posto           ,
					tbl_posto_fabrica.tipo_posto             ,
					tbl_posto_fabrica.pedido_em_garantia     ,
					tbl_posto_fabrica.pedido_faturado        ,
					tbl_posto_fabrica.digita_os              ,
					tbl_posto_fabrica.reembolso_peca_estoque
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto.posto   = $login_posto ";

	$res2 = pg_exec ($con,$sql2);

	if (pg_numrows ($res2) > 0) {
		$tipo_posto             = trim(pg_result($res2,0,tipo_posto));
		$pedido_em_garantia     = trim(pg_result($res2,0,pedido_em_garantia));
		$pedido_faturado        = trim(pg_result($res2,0,pedido_faturado));
		$digita_os              = trim(pg_result($res2,0,digita_os));
		$reembolso_peca_estoque = trim(pg_result($res2,0,reembolso_peca_estoque));
	}
//fim - pega tipo posto=================================================================



//Por produto === por produto cadastrado============================================
if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
	if ($opcao == "1") {
		$produto_referencia = str_replace("-", "", $produto_referencia);
		$produto_referencia = str_replace("/", "", $produto_referencia);
		$produto_referencia = str_replace("'", "", $produto_referencia);
		$produto_referencia = str_replace(".", "", $produto_referencia);

		// HD 16189
		if (strlen($produto_referencia) > 0){
			$sqlx = "SELECT   tbl_produto.produto, tbl_produto.familia
					FROM     tbl_produto
					JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
					WHERE    tbl_produto.referencia_pesquisa = '$produto_referencia'
					AND      tbl_linha.fabrica = $login_fabrica";
			if (strlen($produto_voltagem) > 0){
				$sqlx .=	" AND tbl_produto.voltagem = '$produto_voltagem' ";
			}
			$resx = pg_exec ($con,$sqlx);
			if (pg_numrows($resx) > 0){
				$produto = pg_result ($resx,0,produto);
				$sql_produto .= " AND ( (tbl_comunicado.produto = $produto or tbl_comunicado_produto.produto=$produto) ";

				//hd 53987
				if (isFabrica(3)) {
					$familia = pg_result ($resx,0,familia);
					if (strlen($familia) > 0) {
						$sql_produto .= "or (tbl_comunicado.familia = $familia and tbl_comunicado.produto is null) ";
					}
				}

				$sql_produto .= ") ";


			}
		}
		if (isFabrica(1)) {		//HD 10983
			$sql_cond1=" tbl_comunicado.pedido_em_garantia     IS null ";
			$sql_cond2=" tbl_comunicado.pedido_faturado        IS null ";
			$sql_cond3=" tbl_comunicado.digita_os              IS null ";
			$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS null ";

			$sql_cond5=" AND (tbl_comunicado.destinatario_especifico = '$categoria' or tbl_comunicado.destinatario_especifico = '') ";
			$sql_cond6=" AND (tbl_comunicado.tipo_posto = '$tipo_posto' or tbl_comunicado.tipo_posto is null) ";

			/*HD 7869*/


			if ($pedido_em_garantia == "t")     $sql_cond1 =" tbl_comunicado.pedido_em_garantia     IS NOT FALSE ";
			if ($pedido_faturado == "t")        $sql_cond2 =" tbl_comunicado.pedido_faturado        IS NOT FALSE ";
			if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os              IS TRUE ";
			if ($reembolso_peca_estoque == "t") $sql_cond4 =" tbl_comunicado.reembolso_peca_estoque IS TRUE ";
			$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
		}
		if (isFabrica(1)) {	// HD 31530
			$sql_cond_linha = "
							AND (tbl_comunicado.linha IN
									(
										SELECT tbl_linha.linha
										FROM tbl_posto_linha
										JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
										WHERE fabrica =$login_fabrica
											AND posto = $login_posto
									)
									OR tbl_comunicado.linha IS NULL
								)";
		}else{
			$sqlPostoLinha = "
							AND (tbl_comunicado.linha IN
									(
										SELECT tbl_posto_linha.linha
										FROM tbl_posto_linha
										JOIN tbl_linha USING (linha)
										WHERE fabrica =$login_fabrica
											AND posto = $login_posto
									)
									OR (
											tbl_comunicado.comunicado IN (
												SELECT tbl_comunicado_produto.comunicado
												FROM tbl_comunicado_produto
												JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
												JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
												WHERE fabrica_i =$login_fabrica AND
													  tbl_posto_linha.posto = $login_posto

											)
										AND tbl_comunicado.produto IS NULL
									)
									OR tbl_comunicado.produto in
									(
										SELECT tbl_produto.produto
										FROM tbl_produto
										JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
										WHERE fabrica_i = $login_fabrica AND
										posto = $login_posto
									)
									OR (tbl_comunicado.linha IS NULL AND tbl_comunicado.produto IS NULL)
								)";

			if (isFabrica(1,15,42))  $sqlPostoLinha = "";
		}

		$sql =	"SELECT tbl_comunicado.comunicado                                       ,
						tbl_comunicado.descricao                                        ,
						tbl_comunicado.extensao                                         ,
						tbl_comunicado.video                                            ,
						tbl_comunicado.tipo                                             ,
						tbl_comunicado.mensagem                                         ,
						TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data               ,
						tbl_produto.referencia                    AS produto_referencia ,
						tbl_produto.descricao                     AS produto_descricao  ,
						tbl_produto.produto
				FROM   tbl_comunicado
				LEFT JOIN tbl_produto              USING (produto)
				LEFT JOIN tbl_comunicado_produto   ON tbl_comunicado.comunicado = tbl_comunicado_produto.comunicado
				WHERE  ((tbl_comunicado.tipo_posto = $tipo_posto)  OR (tbl_comunicado.tipo_posto IS NULL))
				AND    ((tbl_comunicado.posto      = $login_posto) OR (tbl_comunicado.posto      IS NULL))
				AND    tbl_comunicado.ativo       IS TRUE
				$sql_produto ";

		if (isFabrica(20))  $sql .= " AND tbl_comunicado.pais = '$login_pais' ";

		//HD 10983
		if (isFabrica(1)) {
			$sql.=" $sql_cond_total ";
			$sql.=" $sql_cond5 ";
			$sql.=" $sql_cond6 ";

		}

		if (isFabrica(1, 3)) { // HD 31530
			$sql.=" $sql_cond_linha ";
		}else{
			// $sql.=" $sqlPostoLinha ";
		}

	}


//Adiminstrativos --- não tem produto cadastrado============================================
	if ($opcao == "2") {
		$sql =	"SELECT tbl_comunicado.comunicado                         ,
						tbl_comunicado.descricao                          ,
						tbl_comunicado.extensao                           ,
						tbl_comunicado.video                              ,
						tbl_comunicado.tipo                               ,
						tbl_comunicado.mensagem                           ,
						TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data
				FROM    tbl_comunicado
				WHERE   tbl_comunicado.produto      IS NULL
				AND     ((tbl_comunicado.tipo_posto =  $tipo_posto)  OR (tbl_comunicado.tipo_posto IS NULL))
				AND     ((tbl_comunicado.posto      =  $login_posto) OR (tbl_comunicado.posto      IS NULL))
				AND     tbl_comunicado.ativo        IS TRUE ";
		if (isFabrica(1)) {		//HD 10983 - Acrescentado no HD 423291
			$sql_cond1=" tbl_comunicado.pedido_em_garantia IS null ";
			$sql_cond2=" tbl_comunicado.pedido_faturado IS null ";
			$sql_cond3=" tbl_comunicado.digita_os IS null ";
			$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS null ";

			$sql_cond5=" AND (tbl_comunicado.destinatario_especifico = '$categoria' or tbl_comunicado.destinatario_especifico = '') ";
			$sql_cond6=" AND (tbl_comunicado.tipo_posto = '$tipo_posto' or tbl_comunicado.tipo_posto is null) ";
			/*HD 7869*/


			if ($pedido_em_garantia == "t")     $sql_cond1 =" tbl_comunicado.pedido_em_garantia     IS NOT FALSE ";
			if ($pedido_faturado == "t")        $sql_cond2 =" tbl_comunicado.pedido_faturado        IS NOT FALSE ";
			if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os              IS TRUE ";
			if ($reembolso_peca_estoque == "t") $sql_cond4 =" tbl_comunicado.reembolso_peca_estoque IS TRUE ";
			$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
		}
		/*HD 7869*/
		$sql_cond_linha = "
				AND (tbl_comunicado.linha IN
						(
							SELECT tbl_linha.linha
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
							WHERE fabrica =$login_fabrica
								AND posto = $login_posto
						)
						OR tbl_comunicado.linha IS NULL
					)";

		$sqlPostoLinha = "
							AND (tbl_comunicado.linha IN
									(
										SELECT tbl_posto_linha.linha
										FROM tbl_posto_linha
										JOIN tbl_linha USING (linha)
										WHERE fabrica =$login_fabrica
											AND posto = $login_posto
									)
									OR (
											tbl_comunicado.comunicado IN (
												SELECT tbl_comunicado_produto.comunicado
												FROM tbl_comunicado_produto
												JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
												JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
												WHERE fabrica_i =$login_fabrica AND
													  tbl_posto_linha.posto = $login_posto

											)
										AND tbl_comunicado.produto IS NULL
									)
									OR tbl_comunicado.produto in
									(
										SELECT tbl_produto.produto
										FROM tbl_produto
										JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
										WHERE fabrica_i = $login_fabrica AND
										posto = $login_posto
									)
									OR (tbl_comunicado.linha IS NULL AND tbl_comunicado.produto IS NULL)
								)";

		if (isFabrica(1,15,42))  $sqlPostoLinha = "";

		if (isFabrica(20))  $sql .= " AND tbl_comunicado.pais = '$login_pais' ";
			//HD 10983
		if (isFabrica(1)) {
			$sql.=" $sql_cond_total ";
			$sql.=" $sql_cond5 ";
			$sql.=" $sql_cond6 ";
		}

		if (isFabrica(1, 3)) { // HD 31530
			$sql.=" $sql_cond_linha ";
		}else{
			$sql.=" $sqlPostoLinha ";
		}

	}
	if ($opcao == "3" and isFabrica(3)) {
		$sql_cond_linha = "
				AND (tbl_comunicado.linha IN
						(
							SELECT tbl_linha.linha
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
							WHERE fabrica =$login_fabrica
								AND posto = $login_posto
						)
						OR tbl_comunicado.linha IS NULL
					)";

		$psq_tipo = $_POST['psq_tipo'];

		if ($psq_tipo =='Comunicado')  {
			echo "Comunicado Técnico";
		}elseif ($psq_tipo =='Informativo') {
			echo "Comunicado Administrativo";
		}else {
			echo "$psq_tipo";
		}

		$sql =	"SELECT tbl_comunicado.comunicado                         ,
						tbl_comunicado.descricao                          ,
						tbl_comunicado.extensao                           ,
						tbl_comunicado.tipo                               ,
						tbl_comunicado.mensagem                           ,
						TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data ,
						tbl_comunicado.video
				FROM    tbl_comunicado
				WHERE   tbl_comunicado.produto IS NULL
				AND     ((tbl_comunicado.tipo_posto		= $tipo_posto)  OR (tbl_comunicado.tipo_posto IS NULL))
				AND     ((tbl_comunicado.posto			= $login_posto) OR (tbl_comunicado.posto      IS NULL))
				AND     tbl_comunicado.ativo IS TRUE
				AND     tbl_comunicado.tipo = '$psq_tipo'";

			if (isFabrica(3)) { // HD 31530
				$sql.=" $sql_cond_linha ";
			}
	}


	$sql .=	" AND tbl_comunicado.fabrica = $login_fabrica
			ORDER BY tbl_comunicado.tipo, tbl_comunicado.data DESC ";
// echo $sql;die;
	$res = pg_exec($con,$sql);

// if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql);

	if (pg_numrows($res) > 0) {
		$produto_referencia = @pg_result($res,0,produto_referencia);
		$produto_descricao  = @pg_result($res,0,produto_descricao);
		$produto            = @pg_result($res,0,produto);
// HD 17334
if (isFabrica(3)) {
		echo "<br><center><font size=3 color='red'>A partir de hoje(22/04/2008), para enviar sua dúvida terá o novo processo, entre na tela de Confirmação de Ordem de Serviço(consulta de OS) e terá opção de ENVIAR DÚVIDA AO SUPORTE TÉCNICO logo abaixo.</font></center><br>";
if (strlen($produto)>0 and 1==2) {
echo "<form name='frm_ajuda' method='post' action='$PHP_SELF'>";

echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; font-size:10px;' bordercolor='#000000' align='center'>";
echo "<tr>";
echo "<td>";
	echo "<table width='500' border='0' cellpadding='2' cellspacing='0' style='border-collapse: collapse; font-size:10px;' bordercolor='#000000' align='center'>";
	echo "<tr class='Titulo'>";
	echo "<td colspan='4'>Solicitar Ajuda ao Fabricante</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Conteudo' valign='top'><b>Atenção:</b></td>";
	echo "<td colspan='3'>Caso não tenha encontrado informação para solucionar o problema do aparelho. Por favor informe ao fabricante no campo abaixo.</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Conteudo'><b>Produto:</b></td>";
	echo "<td colspan='3'><b>$produto_referencia -  $produto_descricao</b></td>";
	echo "</tr>";
	//***
	echo "<tr>";
	echo "<td>&nbsp;</td>";
	echo "<td><b>OS:</b><input type='text' name='sua_os' size='12' class='frm' value='$sua_os'><img border='0' src='imagens_admin/btn_lupa.gif' style='cursor: pointer;' align='absmiddle' onclick=\"javascript: fnc_pesquisa_os (document.frm_ajuda.sua_os)\" ></td>";
	echo "<td><b>Série:</b><input type='text' name='serie' size='12' class='frm' value='$serie' readonly></td>";
	echo "<td><b>Defeito:</b><input type='text' name='defeito_constatado' size='15' class='frm' value='$defeito_constatado' readonly></td>";
	echo "</tr>";
	echo "<tr>";
	//***
	echo "<td class='Conteudo' valign='top'><b>Problema:</b></td>";
	echo "<td colspan='3'><TEXTAREA NAME='ajuda_texto' ROWS='3' COLS='50' class='textarea'>$ajuda_texto</TEXTAREA></td>";
	echo "<tr class='Conteudo'>";
	echo "<td colspan='4' align='center'><img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_ajuda.btn_ajuda.value == '' ) { document.frm_ajuda.btn_ajuda.value='gravar' ; document.frm_ajuda.submit() } else { alert ('Aguarde submissão') }\" ALT=\"Gravar ajuda\" border='0' style='cursor: pointer'>
</td>";
	echo "</tr>";
	echo "</tr>";
	echo "</table>";
echo "<input type='hidden' name='btn_ajuda' value=''>";
echo "<input type='hidden' name='produto' value='$produto'>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</form>";

echo "<BR><BR>";
}
}
	echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$comunicado         = pg_result($res,$i,comunicado);
			$descricao          = pg_result($res,$i,descricao);
			$extensao           = pg_result($res,$i,extensao);
			$tipo               = pg_result($res,$i,tipo);
			$mensagem           = pg_result($res,$i,mensagem);
			$data               = pg_result($res,$i,data);
			if (isFabrica(50)) {
                $video			= trim(pg_result($res,$i,video)); // HD 65474
			}

			if (isFabrica(3)) {
				if ($tipo =='Comunicado')  {
					$tipo="Comunicado Técnico";
				}elseif ($tipo =='Informativo') {
					$tipo="Comunicado Administrativo";
				}
			}
			if (trim($extensao) == 'm') {$extensao = 'bmp';}//modificar as extensoes de alguns arquivos esta como m onde deveria estar bmp.

			if ($video<>'' and isFabrica(50))  { // HD 65474
			// Converte a URL direta para assistir o vídeo para a URL do objeto
				$evideo = str_replace("/watch?v=", "/v/", $video);
			}

			$extensao=strtolower($extensao);

			if ($tipo_anterior != $tipo) {
				if ($i != 0) {
					echo "<tr>";
					echo "<td colspan='5'>&nbsp;</td>";
					echo "</tr>";
				}
				echo "<tr class='Titulo'>";
				echo "<td colspan='5'>";
				echo traduz('tipo.do.comunicado', $con);
				echo ": $tipo</td>";
				echo "</tr>";
				echo "<tr class='Titulo'>";
				echo "<td>";
				echo traduz('data', $con);
				echo "</td>";
				echo "<td>";
				echo traduz('descricao', $con);
				echo "</td>";
				if ($opcao == "1"){
					echo "<td width='10'>" . traduz('produto', $con) . "</td>";
				}
				echo "<td>";
				echo traduz('arquivo', $con);
				echo "</td>";
				echo "</tr>";
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td><B>$data</B></td>";
			echo "<td align='left'>";
			if (isFabrica(50) and $video<>'') {
				echo "\n\t<SPAN style='position:relative;clear:left'>";}
			echo "<B>$descricao</B>";
			if (isFabrica(50) and $video<>'') {    ?>
	<DIV width='445' height='364' align='center' style='float:right;top:10px;right:15'>
	    Vídeo: <A href='<?=$video?>' target='_blank'><?=$video?></A>
		<OBJECT width="445" height="364">
			<PARAM name="movie"
				  value="<?=$evideo?>&hl=pt-br&fs=1&rel=0&color1=0x2b405b&color2=0x6b8ab6&border=1"></PARAM>
			<PARAM name="allowFullScreen"   value="true"></param>
			<PARAM name="allowscriptaccess" value="always"></param>
			<EMBED  src="<?=$evideo?>&hl=pt-br&fs=1&rel=0&color1=0x2b405b&color2=0x6b8ab6&border=1"
				   type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true"
				   width="445" height="364">
			</EMBED>
		</OBJECT>
	</DIV>
	</SPAN>
<?			}
			echo "</td>";
			if ($opcao == "1")
				echo "<td  align='left'><acronym title='Referência: $produto_referencia | Descrição: $produto_descricao'>$produto_descricao</acronym></td>";
			echo "<td align='center'>";
			if (strlen($comunicado) > 0 AND strlen($extensao) > 0) {
				unset($fileLink);
				if ($S3_online) {
					$s3->set_tipo_anexoS3($tipo_comunicado);
					if ($s3->temAnexos($comunicado))
						$fileLink = $s3->url;
				} else {
					$fileLink = "comunicados/$comunicado.$extensao";
					if (!file_exists($fileLink))
						unset($fileLink);
				}
				if (isset($fileLink))echo "<a href='$fileLink' target='_blank'>" . traduz('visualizar.arquivo', $con) . "</a>";
			} else {
				echo "&nbsp;";
			}
			echo "</td>";
			echo "</tr>";
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			if ($mensagem =='') {
				if ($sistema_lingua == 'ES')
					echo "<td colspan='5' align='center' style='color:#B7B7B7;'><br>Mensaje no catastrada.<br>&nbsp;</td>";
				else
					echo "<td colspan='5' align='center' style='color:#B7B7B7;'><br>Mensagem não cadastrada.<br>&nbsp;</td>";
			}else {
				echo "<td style='color:#383838;' colspan='5'><br>";
				echo nl2br($mensagem);
				echo "<br>&nbsp;</td>";
			}
			echo "</tr>";
			$tipo_anterior = $tipo;
		}
		echo "</table>";
	}else{
	echo "<table width='700' border='0' cellpadding='2' cellspacing='0' align='center'>";
	echo "<tr>";
	echo "<td align='center'><font size='1' face='verdana'>";
	if ($sistema_lingua == "ES")  echo "No se han encuentrado comunicados para el producto";
	else                        echo "Não foram encontrados Comunicados para o produto:";
	echo "<BR> $produto_referencia - $produto_descricao</font></td>";
	echo "</tr>";
	echo "</table>";
	}
}

include "rodape.php";

