<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro = '';

$title = 'PROCEDIMENTOS '. strtoupper($login_fabrica_nome);

$layout_menu = "tecnica";

include "cabecalho_new.php";

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new anexaS3('ve', (int) $login_fabrica);
}

if($_GET["acao"]) $acao = strtoupper($_GET["acao"]);

if($_GET["comunicado"]){
	$comunicado = $_GET["comunicado"];
} 

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
	font-size: 11 px;
	font-weight: bold;
	color: #000000
	background-color: #D9E2EF
}
.Conteudo {
	font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
	font-size: 11 px;
	font-weight: normal;
}
</style>

<br>

<?
// SELECIONA AS FAMILIAS
$sql = "SELECT familia FROM tbl_posto_linha WHERE posto = $login_posto";
$res = pg_exec ($con,$sql);
$contador_familia = pg_numrows($res);
$familia_posto = '';
for ($i=0; $i<$contador_familia; $i++){
	if (pg_result ($res,$i,0) <> '') {
		$familia_posto .= pg_result ($res,$i,0);
		$familia_posto .= ", ";
	}
}
$familia_posto .= "0";

$sql2 = "SELECT 	tbl_posto_fabrica.pedido_em_garantia     ,
					tbl_posto_fabrica.pedido_faturado        ,
					tbl_posto_fabrica.digita_os              ,
					tbl_posto_fabrica.reembolso_peca_estoque 
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto.posto   = $login_posto ";

$res2 = pg_exec ($con,$sql2);

if (pg_numrows ($res2) > 0) {
	$pedido_em_garantia     = pg_result($res2,0,pedido_em_garantia);
	$pedido_faturado        = pg_result($res2,0,pedido_faturado);
	$digita_os              = pg_result($res2,0,digita_os);
	$reembolso_peca_estoque = pg_result($res2,0,reembolso_peca_estoque);
}

##### COMUNICADO #####

if ($acao == "VER" && strlen($comunicado) > 0) {

	$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = $login_fabrica ";

	$sql =	"SELECT *
			FROM tbl_comunicado_posto_blackedecker
			WHERE {$cond_pesquisa_fabrica}
			AND   posto      = $login_posto
			AND   comunicado = $comunicado ";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) == 0 && $login_fabrica == 1) {
		$sql =	"INSERT INTO tbl_comunicado_posto_blackedecker (
					fabrica    ,
					posto      ,
					comunicado
				) VALUES (
					$login_fabrica ,
					$login_posto   ,
					$comunicado
				);";
		$res = pg_exec($con,$sql);
	}
	if($login_fabrica==1){		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia IS null ";
		$sql_cond2=" tbl_comunicado.pedido_faturado IS null ";
		$sql_cond3=" tbl_comunicado.digita_os IS null ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS null ";

		$sql_cond5=" AND (tbl_comunicado.destinatario_especifico = '$categoria' or tbl_comunicado.destinatario_especifico = '') ";
		$sql_cond6=" AND (tbl_comunicado.tipo_posto = '$tipo_posto' or tbl_comunicado.tipo_posto is null) ";

		if ($pedido_em_garantia == "t")     $sql_cond1 ="  tbl_comunicado.pedido_em_garantia IS TRUE ";
		if ($pedido_faturado == "t")        $sql_cond2 ="  tbl_comunicado.pedido_faturado IS TRUE ";
		if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 ="   tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";

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
	}

	$sql =	"SELECT comunicado                                        ,
					remetente_email                                   ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data ,
					descricao                                         ,
					mensagem
			FROM tbl_comunicado
			WHERE {$cond_pesquisa_fabrica}
			AND   comunicado = $comunicado";
	if($login_fabrica ==20) $sql .= " AND pais = '$login_pais'";
	
	//HD 10983
	if($login_fabrica==1){
		$sql.=" $sql_cond_total ";
		$sql.=" $sql_cond5 ";
		$sql.=" $sql_cond6 ";
		$sql.=" $sql_cond_linha ";
	}

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		echo "<table class='table table-bordered table-striped table-fixed'>";

		echo "<thead><tr class='titulo_coluna'>";
		echo "<th colspan='2'>";
		echo strtoupper(traduz("procedimentos",$con,$cook_idioma));
		echo "</th>";
		echo "</tr>";

		echo "<tr class='titulo_coluna'>";
		echo "<th>";
		echo strtoupper(traduz("remetente",$con,$cook_idioma));
		echo "</th>";
		echo "<th>";
		echo strtoupper(traduz("data",$con,$cook_idioma));
		echo "</th>";
		echo "</tr></thead>";
		echo "<tbody><tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td>" . pg_result($res,0,remetente_email) . "</td>";
		echo "<td>" . pg_result($res,0,data) . "</td>";
		echo "</tr></tbody>";

		echo "<thead><tr class='titulo_coluna'>";
		echo "<th colspan='2'>";
		echo strtoupper(traduz("assunto",$con,$cook_idioma));
		echo "</th>";
		echo "</tr></thead>";
		echo "<tbody><tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td colspan='2' align='center'>" . pg_result($res,0,descricao) . "</td>";
		echo "</tr></tbody>";

		echo "<thead><tr class='titulo_coluna'>";
		echo "<th colspan='2'>";
		echo strtoupper(traduz("mensagem",$con,$cook_idioma));
		echo "</th>";
		echo "</tr></thead><tbody>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td colspan='2' align='center'>";
		if (strlen(trim(pg_result($res,0,mensagem))) > 0) 
			echo "<b>";
			fecho("prezado",$con,$cook_idioma)." (a)";
			echo "$posto_nome,</b>";
		echo "<br><br>" . nl2br(pg_result($res,0,mensagem)) . "</td>";
		echo "</tr></tbody>";

		if ($S3_online) {
			$tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
			if ($s3->tipo_anexo != $tipo_s3)
				$s3->set_tipo_anexoS3($tipo_s3);
			$s3->temAnexos($comunicado);

			if ($s3->temAnexo) {
				$arquivo = $s3->url;
			}

		} else {
			$jpg = "/var/www/assist/www/comunicados/$comunicado.jpg";
			$gif = "/var/www/assist/www/comunicados/$comunicado.gif";
			$pdf = "/var/www/assist/www/comunicados/$comunicado.pdf";
			$doc = "/var/www/assist/www/comunicados/$comunicado.doc";
			$xls = "/var/www/assist/www/comunicados/$comunicado.xls";
			$zip = "/var/www/assist/www/comunicados/$comunicado.zip";
			
			if (file_exists($jpg) == true)
				$arquivo = "/assist/comunicados/$comunicado.jpg";
			if (file_exists($gif) == true)
				$arquivo = "/assist/comunicados/$comunicado.gif";
			if (file_exists($pdf) == true)
				$arquivo = "/assist/comunicados/$comunicado.pdf";
			if (file_exists($doc) == true)
				$arquivo = "/assist/comunicados/$comunicado.doc";
			if (file_exists($xls) == true)
				$arquivo = "/assist/comunicados/$comunicado.xls";
			if (file_exists($zip) == true)
				$arquivo = "/assist/comunicados/$comunicado.zip";
		}

		if (strlen($arquivo) > 0) {
			echo "<thead><tr class='titulo_coluna'>";
			echo "<th colspan='2'>".strtoupper(traduz("anexo",$con,$cook_idioma))."</th>";
			echo "</tr></thead><tbody>";
			echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
			echo "<td colspan='2' align='center'><center><a href='$arquivo' target='_blank'>";
			fecho ("clique.aqui",$con,$cook_idioma);
			echo "</center></td>";
			echo "</tr></tbody>";
		}

		echo "</table>";
		echo "<br>";
	}
	?>
	<center>
		<input class="tac btn" id='btn_voltar' name='btn_voltar' type='submit' style="cursor: pointer;" onclick="javascript: history.back(-1);" ALT="<?fecho("voltar",$con,$cook_idioma);?>" border='0' value='Voltar'>
	</center>
	<?
}

if ($acao != "VER") {

	$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = $login_fabrica ";

	$sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE posto = $login_posto AND {$cond_pesquisa_fabrica}";
	$res = pg_query($con,$sql);
	$tipo_posto = pg_fetch_result($res,0,0);

	##### PROCEDIMENTOS #####
	if($login_fabrica==1){		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia IS null ";
		$sql_cond2=" tbl_comunicado.pedido_faturado IS null ";
		$sql_cond3=" tbl_comunicado.digita_os IS null ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS null ";

		if ($pedido_em_garantia == "t")     $sql_cond1 =" tbl_comunicado.pedido_em_garantia IS TRUE ";
		if ($pedido_faturado == "t")        $sql_cond2 =" tbl_comunicado.pedido_faturado IS TRUE ";
		if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 =" tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total=" AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";

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
	}

	$where_tipo = "UPPER(tbl_comunicado.tipo) = 'PROCEDIMENTOS'";
	if(in_array($login_fabrica, array(42))){
		$where_tipo = "lower(tbl_comunicado.tipo) = 'procedimento de manutenção'";
	}

	$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_comunicado.fabrica IN (11,172) " : " tbl_comunicado.fabrica = $login_fabrica ";

	$sql =	"SELECT tbl_comunicado.comunicado                        ,
					tbl_comunicado.descricao                         ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data 
			FROM tbl_comunicado
			LEFT JOIN tbl_produto USING(produto) LEFT JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE {$cond_pesquisa_fabrica}
			AND   $where_tipo
			AND ((tbl_comunicado.posto = $login_posto) OR (tbl_comunicado.posto IS NULL))
			AND (tbl_comunicado.tipo_posto = $tipo_posto OR tbl_comunicado.tipo_posto IS NULL)";
	if($login_fabrica ==20) $sql .= " AND pais = '$login_pais'";
	if ($login_fabrica==14){
			$sql .=" AND (tbl_comunicado.familia IN ($familia_posto) OR tbl_comunicado.familia IS NULL) ";
	}
	if ($login_fabrica==14 or $login_fabrica == 66){    //29/03/2010 MLG - HD 220853
			$sql .=" AND 	CASE WHEN tbl_comunicado.tipo_posto IS NULL THEN TRUE
			                    ELSE
									CASE WHEN tbl_posto_fabrica.tipo_posto = tbl_comunicado.tipo_posto
									THEN TRUE
									ELSE FALSE
									END
							END ";
	}
	//HD 10983
	if($login_fabrica==1){
		$sql.=" $sql_cond_total ";
		$sql.=" $sql_cond_linha ";
	}
	$sql.=" ORDER BY tbl_comunicado.data DESC";

	//coloquei o LEFT JOIN tbl_produto USING(produto) LEFT JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha 
	//para pegar independente se tem produto ligado ou nao takashi
	
	##### PAGINAÇÃO - INÍCIO #####
	$sqlCount  = "SELECT count(*) FROM (" . $sql . ") AS count";

	require "_class_paginacao.php";

	// Definições de variáveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	##### PAGINAÇÃO - FIM #####

	$contador_res = pg_numrows($res);

	if ($contador_res > 0) {
		echo "<table class='table table-bordered table-striped table-fixed'>";
		echo "<thead><tr class='titulo_coluna'>";
		echo "<th colspan='2' align='center'>";
		echo strtoupper(traduz("procedimentos",$con,$cook_idioma));
		echo "</th>";
		echo "</tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<th align='center'>";
		echo "DESCRIÇÃO";
		echo "</th>";
		echo "<th align='center'>";
		echo "DATA";
		echo "</th>";
		echo "</tr></thead><tbody>";		
		for ($j = 0 ; $j < $contador_res; $j++) {
			$cor = ($j % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td><a href='$PHP_SELF?acao=VER&comunicado=" . pg_result($res,$j,comunicado) . "'>" . pg_result($res,$j,descricao) . "</a></td>";
			echo "<td align='center'>" . pg_result($res,$j,data) . "</td>";
			echo "</tr>";
		}
		echo "</tbody></table>";
		echo "<br>";
	}else{
		echo "<div class='alerts'>
				<div class='alert danger margin-top'>Nenhum procedimento encontrado</div>
			  </div>";	
	}
	
	##### PAGINAÇÃO - INÍCIO #####
	
	// Links da paginação
	echo "<br>";
	echo "<div><center>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	echo "</center></div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<div><center>";
		fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array($resultado_inicial,$resultado_final,$registros));
		echo " <font color='#CCCCCC' size='1'>(".traduz("pagina.%.de.%",$con,$cook_idioma,array($valor_pagina,$numero_paginas))."</font>";

		echo "</div></center>";
	}
	##### PAGINAÇÃO - FIM #####
}

include "rodape.php"; 
?>