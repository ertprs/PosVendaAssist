<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

$layout_menu = "os";
$title = "Dados Cadastrais do Produto";

include 'cabecalho.php';

$produto  = $_GET['produto'];
$btn_acao = $_POST['btn_acao'];

$msg_erro = "";

if (strlen($btn_acao) > 0) {

	$referencia = $_POST['referencia'];

	if (strlen($referencia) > 0) {

		$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$referencia'";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$produto = pg_fetch_result($res, 0, 'produto');
		}

	} else {
		$msg_erro = "Informe a referência do produto.";
	}

	if (strlen($produto) > 0) {
		$sql = "SELECT  tbl_produto.produto        ,
						tbl_produto.referencia     ,
						tbl_produto.descricao      ,
						tbl_produto.voltagem       ,
						tbl_produto.garantia       ,
						tbl_produto.linha          ,
						tbl_produto.familia        ,
						tbl_produto.mao_de_obra    ,
						tbl_admin.login            ,
						to_char(tbl_produto.data_atualizacao, 'DD/MM/YYYY HH24:MI') AS data_atualizacao
				FROM    tbl_produto
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_produto.admin AND tbl_admin.fabrica = $login_fabrica
				WHERE   tbl_produto.fabrica_i = $login_fabrica
				AND     tbl_produto.produto = $produto;";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			$produto                  = trim(pg_fetch_result($res,0,produto));
			$referencia               = trim(pg_fetch_result($res,0,referencia));
			$descricao                = trim(pg_fetch_result($res,0,descricao));
			$voltagem                 = trim(pg_fetch_result($res,0,voltagem));
			$garantia     = trim(pg_fetch_result($res,0,garantia));
			$linha        = trim(pg_fetch_result($res,0,linha));
			$familia      = trim(pg_fetch_result($res,0,familia));
			$mao_de_obra  = trim(pg_fetch_result($res,0,mao_de_obra));
			$admin                    = trim(pg_fetch_result($res,0,login));
		}
	}

}

?>

<script language='javascript' src='ajax.js'></script>

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
</script>

<style>
.titulo {
	text-align: left;
	border:0;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color: #000000;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#FFFFFF;
	border:0;
	background-color: #596D9B
}

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

</style>


</head>
<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' bgcolor='#FF0000'>";
	echo "<tr>";
	echo "<td align='center' >";
	echo "	<font face='arial, verdana' color='#FFFFFF' size='2'>";
	echo "<B>".$msg_erro."</B>";
	echo "	</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<form name="frm_info" method="post" action="<? $PHP_SELF ?>">
<table width="400" cellpadding="0" cellspacing="0"  align='center'>
	<tr>
		<td  class="menu_top" colspan="2"><b><font size='3' color='#ffffff'>Dados Cadastrais do Produto</font></b></td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td style="text-align: left;">Referência</td>
		<td style="text-align: left;">Descrição</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td nowrap style="text-align: left;" ><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a REFERÊNCIA DO PRODUTO ou parte dela, depois, clique na lupa a direita para realizar a busca.');"><a href="javascript: fnc_pesquisa_produto (document.frm_info.referencia,document.frm_info.descricao,'referencia',document.frm_info.voltagem)"><IMG SRC="imagens/btn_lupa_novo.gif" ></a></td>
		<td nowrap style="text-align: left;" ><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a DESCRIÇÃO DA PEÇA ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		<a href="javascript: fnc_pesquisa_produto (document.frm_info.referencia,document.frm_info.descricao,'descricao',document.frm_info.voltagem)"><IMG SRC="imagens/btn_lupa_novo.gif" ></a></td>
		<input type=hidden name=voltagem value="<? $voltagem; ?>">
	</tr>

	<tr bgcolor="#D9E2EF"><td></td></tr>

		<tr bgcolor="#D9E2EF">
		<td colspan=2 style="text-align: center;">
		<div id="wrapper" align='center'><input class='frm' type="submit" name="btn_acao" value='Buscar' >
		</div>
		</td>
	</tr>
</table>
</form><?php

if (strlen($btn_acao) > 0 and strlen($produto) > 0) {

	if ($S3_sdk_OK) {
		include_once S3CLASS;
		$s3 = new anexaS3('ve', (int) $login_fabrica);
	}

	if ($login_fabrica == 3) {//HD 110109

		$sql = "SELECT DISTINCT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.garantia, tbl_produto.mao_de_obra, tbl_produto.produto
				FROM tbl_produto
				JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha
				JOIN tbl_tabela USING (tabela)
				WHERE tbl_produto.produto = $produto
				AND tbl_posto_linha.posto = $login_posto
				AND tbl_produto.fabrica_i = $login_fabrica
				AND tbl_tabela.ativa = 't'";

	} else {

		$sql = "SELECT	distinct tbl_produto.referencia, tbl_produto.descricao, tbl_produto.garantia, tbl_produto.mao_de_obra, tbl_produto.produto
				FROM	tbl_lista_basica
				JOIN    tbl_produto     USING (produto)
				JOIN    tbl_posto_linha USING (linha)
				JOIN tbl_tabela         USING (tabela)
				WHERE	tbl_lista_basica.produto = $produto
					AND tbl_posto_linha.posto    = $login_posto
					AND tbl_lista_basica.fabrica = $login_fabrica
					AND tbl_produto.fabrica_i = $login_fabrica
					AND tbl_tabela.ativa   = 't'";

	}

	$res1 = pg_query($con, $sql);

	if (pg_num_rows($res1) == 0) {
		echo "<b>O posto não trabalha com essa linha de produto.<b><BR><BR>";
	} else {

		if(pg_num_rows($res1) > 0) {?>

			<form name='frm_res' method='post' action='<? $PHP_SELF ?>'>
			<table width='700' align='center' border='0' cellpadding='2' cellspacing='1' bgcolor='#F0F7FF'>
				<tr bgcolor='##9999CC' style='font-size:10px'>
					<td align='center' height='25' style='font-size: 14px;' colspan='4'><B><font color=#FFFFFF>Informações sobre o produto: <? echo "$referencia - $descricao"; ?></font></B></td>
				</tr>
				<tr bgcolor='#D9E2EF' style='font-size:10px'>
					<td align='center' class='conteudo' colspan='1'><b>Garantia (meses)</b></td>
					<td align='center' colspan='1' class='conteudo'><b>Mão-de-obra</b></td>
					<td align='center' class='conteudo'><b>Lista Básica</b></td>
				</tr>
				<tr bgcolor='#FFFFFF' >
					<td align='center' class='conteudo' colspan='1'>
						<? echo $garantia ?>
					</td>
					<td align='center' class='conteudo' colspan='1'>
						R$ <? echo $mao_de_obra; ?>
					</td>

					<td align='center' class='conteudo'>
						 <a href='peca_consulta_por_produto.php?produto=<? echo $produto; ?>' target="blank">Lista</a></tr>
					</td>
				</tr>
			<table>

			<table  width='700' align='center' border='0' cellpadding='2' cellspacing='1' bgcolor='#F0F7FF'>
			<tr bgcolor='#D9E2EF' style='font-size:10px'>
				<td align='center' class='conteudo'><b>Vista Explodida</b> </td>
				<td align='center' class='conteudo'><b>Manual</b></td>
				<td align='center' class='conteudo'><b>Comunicado</b></td>
			</tr><?php

			$sql2 = "SELECT tbl_posto_fabrica.codigo_posto        ,
							tbl_posto_fabrica.tipo_posto
					FROM	tbl_posto
					LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
					AND     tbl_posto.posto   = $login_posto ";

			$res2 = pg_query ($con,$sql2);

			if (pg_num_rows($res2) > 0) {
				$tipo_posto = trim(pg_fetch_result($res2, 0, 'tipo_posto'));
			}

			//Pegar Arquivo de VISTA EXPLODIDA
			$sql = "SELECT tbl_comunicado.comunicado,
					tbl_comunicado.descricao ,
					tbl_comunicado.mensagem ,
					tbl_comunicado.tipo,
					tbl_produto.produto ,
					tbl_produto.referencia ,
					tbl_produto.descricao AS descricao_produto ,
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data
					FROM tbl_comunicado
					LEFT JOIN tbl_produto on tbl_produto.produto = tbl_comunicado.produto AND tbl_produto.fabrica_i=$login_fabrica
					LEFT JOIN tbl_comunicado_produto on tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
					WHERE tbl_comunicado.fabrica = $login_fabrica
					AND (tbl_comunicado.tipo_posto = $tipo_posto OR tbl_comunicado.tipo_posto IS NULL)
					AND ((tbl_comunicado.posto = $login_posto) OR (tbl_comunicado.posto IS NULL))
					AND tbl_comunicado.ativo IS TRUE
					AND tbl_comunicado.tipo = 'Vista Explodida'
					AND (tbl_produto.produto = $produto or tbl_comunicado_produto.produto = $produto)
					ORDER BY tbl_produto.descricao DESC,
					tbl_comunicado.descricao,
					tbl_produto.referencia";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {

				$Xcomunicado           = trim(pg_fetch_result($res, $i, 'comunicado'));
				$descricao             = trim(pg_fetch_result($res, $i, 'descricao_produto'));
				$comunicado_descricao  = trim(pg_fetch_result($res, $i, 'descricao'));
				$tipo                  = trim(pg_fetch_result($res, $i, 'tipo'));

				echo "<td nowrap align='center' class='conteudo'>";

				if ($S3_online) {
					$tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
					if ($s3->tipo_anexo != $tipo_s3)
						$s3->set_tipo_anexoS3($tipo_s3);
					$s3->temAnexos($Xcomunicado);

					if ($s3->temAnexo) {
						$com_file = $s3->url;
						echo "<a href='$com_file' target='_blank'>";
					}

				} else {
					$gif = "comunicados/$Xcomunicado.gif";
					$jpg = "comunicados/$Xcomunicado.jpg";
					$pdf = "comunicados/$Xcomunicado.pdf";
					$doc = "comunicados/$Xcomunicado.doc";
					$rtf = "comunicados/$Xcomunicado.rtf";
					$xls = "comunicados/$Xcomunicado.xls";
					$ppt = "comunicados/$Xcomunicado.ppt";
					$zip = "comunicados/$Xcomunicado.zip";

					if (file_exists($gif) == true) echo "<a href='comunicados/$Xcomunicado.gif' target='_blank'>";
					if (file_exists($jpg) == true) echo "<a href='comunicados/$Xcomunicado.jpg' target='_blank'>";
					if (file_exists($cod) == true) echo "<a href='comunicados/$Xcomunicado.cod' target='_blank'>";
					if (file_exists($xls) == true) echo "<a href='comunicados/$Xcomunicado.xls' target='_blank'>";
					if (file_exists($rtf) == true) echo "<a href='comunicados/$Xcomunicado.rtf' target='_blank'>";
					if (file_exists($pdf) == true) echo "<a href='comunicados/$Xcomunicado.pdf' target='_blank'>";
					if (file_exists($ppt) == true) echo "<a href='comunicados/$Xcomunicado.ppt' target='_blank'>";
					if (file_exists($zip) == true) echo "<a href='comunicados/$Xcomunicado.zip' target='_blank'>";
				}
				echo "Arquivo";
				echo "</a>";
				echo "</td>\n";

			} else {
				echo "<td class='conteudo' align='center'>Não consta nenhuma Vista Explodida";
				echo "</td>\n";
			}

			//Pegar Manual do Produto - HD 800919
			$sql = " SELECT tbl_comunicado.comunicado,
							tbl_comunicado.descricao ,
							tbl_comunicado.mensagem ,
							tbl_produto.produto ,
							tbl_produto.referencia ,
							tbl_produto.descricao AS descricao_produto ,
							to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data
						FROM tbl_comunicado
						LEFT JOIN tbl_produto            on tbl_produto.produto               = tbl_comunicado.produto AND tbl_produto.fabrica_i=$login_fabrica
						LEFT JOIN tbl_comunicado_produto on tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
						WHERE tbl_comunicado.fabrica = $login_fabrica
							AND (tbl_comunicado.tipo_posto = $tipo_posto  OR tbl_comunicado.tipo_posto IS NULL)
							AND (tbl_comunicado.posto      = $login_posto OR tbl_comunicado.posto      IS NULL)
							AND (tbl_produto.produto       = $produto     OR tbl_comunicado.produto    IS NULL)
							AND tbl_comunicado.ativo IS TRUE
							AND (upper(tbl_comunicado.tipo) = 'MANUAL' OR tbl_comunicado.tipo = 'Manual de Serviço')
							AND (CASE WHEN tbl_comunicado.produto IS NULL THEN
								tbl_comunicado_produto.produto = $produto
								ELSE
								tbl_comunicado.produto = $produto
							END)
						ORDER BY tbl_produto.descricao DESC,
						tbl_comunicado.descricao,
						tbl_produto.referencia;";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {

				$Xcomunicado           = trim(pg_fetch_result($res, $i, 'comunicado'));
				$descricao             = trim(pg_fetch_result($res, $i, 'descricao_produto'));
				$comunicado_descricao  = trim(pg_fetch_result($res, $i, 'descricao'));

				echo "<td nowrap align='center' class='conteudo'>";
				$gif = "comunicados/$Xcomunicado.gif";
				$jpg = "comunicados/$Xcomunicado.jpg";
				$pdf = "comunicados/$Xcomunicado.pdf";
				$doc = "comunicados/$Xcomunicado.doc";
				$rtf = "comunicados/$Xcomunicado.rtf";
				$xls = "comunicados/$Xcomunicado.xls";
				$ppt = "comunicados/$Xcomunicado.ppt";
				$zip = "comunicados/$Xcomunicado.zip";


				if (file_exists($gif) == true) echo "<a href='comunicados/$Xcomunicado.gif' target='_blank'>";
				if (file_exists($jpg) == true) echo "<a href='comunicados/$Xcomunicado.jpg' target='_blank'>";
				if (file_exists($cod) == true) echo "<a href='comunicados/$Xcomunicado.cod' target='_blank'>";
				if (file_exists($xls) == true) echo "<a href='comunicados/$Xcomunicado.xls' target='_blank'>";
				if (file_exists($rtf) == true) echo "<a href='comunicados/$Xcomunicado.rtf' target='_blank'>";
				if (file_exists($pdf) == true) echo "<a href='comunicados/$Xcomunicado.pdf' target='_blank'>";
				if (file_exists($ppt) == true) echo "<a href='comunicados/$Xcomunicado.ppt' target='_blank'>";
				if (file_exists($zip) == true) echo "<a href='comunicados/$Xcomunicado.zip' target='_blank'>";
				echo "Manual do Produto";
				echo "</a>";
				echo "</td>\n";

			} else {
				echo "<td class='conteudo' align='center'>Não consta nenhum Manual";
				echo "</td>\n";
			}

			//Verificar se tiver comunicado para este produto

			$sql ="SELECT tbl_comunicado.tipo
			FROM  tbl_comunicado
			LEFT JOIN tbl_comunicado_produto USING(comunicado)
			LEFT JOIN tbl_produto ON tbl_produto.referencia='$referencia' and (tbl_produto.produto = tbl_comunicado.produto or tbl_produto.produto = tbl_comunicado_produto.produto)
			WHERE tbl_comunicado.fabrica = $login_fabrica
			AND tbl_produto.fabrica_i = $login_fabrica
			AND tbl_comunicado.tipo='Comunicado'
			AND tbl_comunicado.ativo IS TRUE
			ORDER BY tbl_comunicado.data DESC";
			$res = pg_query($con,$sql);

			if (pg_num_rows ($res) == 0) {
				echo "<td class='conteudo' align='center'>Não consta nenhum Informativo</td>";
			} else {
				$tipo=pg_fetch_result($res,0,tipo);
				#echo "<td class='conteudo' align='center'><a href='pesquisa_comunicado.php?produto=$referencia&descricao=$descricao&comunicado=$tipo' target=blank>Comunicados do produto</a></td>";
				echo "<td class='conteudo' align='center'><a href='comunicado_mostra_pesquisa.php?acao=PESQUISAR&produto_referencia=$referencia&produto_descricao=$descricao&tipo=Comunicado' >Comunicados do produto</a></td>";
			}
			#http://www.telecontrol.com.br/assist/comunicado_mostra_pesquisa.php?acao=PESQUISAR&data_inicial=&data_final=&tipo=&descricao=&produto_referencia=057003009ATA&produto_descricao=SV+VCD+PH148+RIPPING+VERSAO+A&produto_voltagem=Bivolt&x=150&y=9

		echo "</table>";

		//Mostrar as tabelas de preço do produto.

			$sql="	SELECT		tbl_tabela.tabela,
								tbl_tabela.descricao
					FROM		tbl_tabela
					JOIN		tbl_posto_linha USING (tabela)
					JOIN		tbl_linha    ON tbl_linha.linha   = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
					WHERE		tbl_tabela.fabrica    = $login_fabrica
						AND		tbl_posto_linha.posto = $login_posto
						AND		tbl_tabela.ativa   = 't'
					GROUP BY	tbl_tabela.tabela      ,
								tbl_tabela.sigla_tabela,
								tbl_tabela.descricao
					ORDER BY tbl_tabela.sigla_tabela";

			$res_tabela=pg_query($con,$sql);

			if(pg_num_rows($res_tabela) > 0) {

				for ($j = 0; $j < pg_num_rows($res_tabela); $j++) {

					$descricao = trim(pg_fetch_result($res_tabela, $j, 'descricao'));
					$tabela    = trim(pg_fetch_result($res_tabela, $j, 'tabela'));

					$sql="SELECT	 tbl_tabela_item.preco                 ,
									 tbl_peca.referencia as peca_referencia,
									 tbl_peca.descricao  as peca_descricao ,
									 tbl_produto.produto                   ,
									 tbl_lista_basica.peca                 ,
									 tbl_lista_basica.posicao              ,
									 tbl_peca.unidade                      ,
									 tbl_peca.ipi
							FROM     tbl_tabela_item
							JOIN     tbl_peca         USING (peca)
							JOIN     tbl_lista_basica ON tbl_lista_basica.peca = tbl_tabela_item.peca
							JOIN     tbl_produto      ON tbl_produto.produto   = tbl_lista_basica.produto AND 
							tbl_produto.fabrica_i=$login_fabrica
							WHERE   tbl_lista_basica.produto = $produto
								AND  tbl_lista_basica.fabrica = $login_fabrica 
								AND  tbl_peca.item_aparencia <> 't'
								AND  tbl_produto.ativo is TRUE
								AND  tbl_peca.ativo is TRUE
								AND  tbl_tabela_item.tabela=$tabela
							GROUP BY tbl_peca.referencia     ,
									 tbl_peca.descricao      ,
									 tbl_tabela_item.preco   ,
									 tbl_peca.preco_sugerido ,
									 tbl_produto.produto     ,
									 tbl_lista_basica.peca   ,
									 tbl_lista_basica.posicao,
									 tbl_peca.unidade        ,
									 tbl_peca.ipi
							ORDER BY tbl_peca.descricao asc";

					$res = pg_query($con,$sql);

					if (pg_num_rows($res) > 0) {

						echo "<br>";
						echo "<table width='700' align='center' cellspacing='3' border='0'>";
						echo "<tr>";
						echo "<td bgcolor='#007711' align='center' colspan='7'><font face='arial' color='#ffffff'><b>Tabela de Preços-$descricao</b></font></td>";

						$cor = '#ffffff';
						if ($i % 2 == 0) $cor = '#f8f8f8';

						echo "</tr>";
						echo "<tr>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Peça</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Descrição</b></font></td>";
							if($login_fabrica == 3){
							  echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Localização</b></font></td>";
							}
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Unidade</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço Sugerido P/ Venda</b></font></td>";
							if (1 == 2) {
								echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço c/ IPI</b></font></td>";
							}

						echo "</tr>";

						for ($i = 0; $i < pg_num_rows($res); $i++) {

							$peca_referencia = trim(pg_fetch_result($res, $i, 'peca_referencia'));
							$peca_descricao  = trim(pg_fetch_result($res, $i, 'peca_descricao'));
							$unidade         = trim(pg_fetch_result($res, $i, 'unidade'));
							$preco           = trim(pg_fetch_result($res, $i, 'preco'));
							$ipi             = trim(pg_fetch_result($res, $i, 'ipi'));
							$preco_com_ipi   = $preco * (1 + $ipi / 100);
							$posicao	 = trim(pg_fetch_result($res, $i, 'posicao'));

							$sql = "SELECT porcentagem_fator
								   FROM tbl_fator_multiplicacao
								   WHERE  $preco >= valor_inicio
									  AND $preco <= valor_fim";

							$res_fat = pg_query($con, $sql);

							if (pg_num_rows($res_fat) > 0) {
								$porcentagem_fator    = trim(pg_fetch_result($res_fat, 0, 'porcentagem_fator'));
								$preco_sugerido_venda = $preco_com_ipi * $porcentagem_fator;
							}

							echo "<tr bgcolor='$cor'>";

							echo "<td>";
								echo "<font face='arial' size='-2'>";
									echo "$peca_referencia";
								echo "</font>";
							echo "</td>";

							echo "<td>";
								echo "<font face='arial' size='-2'>";
									echo "$peca_descricao";
								echo "</font>";
							echo "</td>";
							
							if($login_fabrica == 3){
							  echo "<td>";
								echo "<font face='arial' size='-2'>";
									echo "$posicao";
								echo "</font>";
							echo "</td>";
							}
							
							echo "<td>";
								echo "<font face='arial' size='-2'>";
									echo $unidade;
								echo "</font>";
							echo "</td>";

							echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
									echo number_format($preco,2,",",".");
								echo "</font>";
							echo "</td>";

							echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
									echo $ipi;
								echo "</font>";
							echo "</td>";

							echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
									echo number_format($preco_sugerido_venda,2,",",".");
								echo "</font>";
							echo "</td>";

							if (1 == 2) {
								echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
										echo number_format ($preco_com_ipi,2,",",".");
									echo "</font>";
								echo "</td>";
							}

							echo "</tr>";
						}
							echo "</table>";
							echo "<BR>";
							echo "</form>";
					}
				}
			}
		}
	}
}


include "rodape.php";


/*	$sql="SELECT *
				FROM tbl_tabela_item
				JOIN tbl_tabela       USING (tabela)
				JOIN tbl_posto_linha  USING (tabela)
				JOIN tbl_peca         USING (peca)
				JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_tabela_item.peca
				JOIN tbl_produto      ON tbl_produto.produto   = tbl_lista_basica.produto
				JOIN tbl_linha        ON tbl_linha.linha       = tbl_produto.linha AND
				tbl_linha.fabrica = $login_fabrica
				WHERE tbl_lista_basica.produto = $produto
				AND   tbl_tabela.ativa         = 't'
				AND   tbl_peca.item_aparencia <> 't'
				AND   tbl_tabela_item.tabela=15
				AND   tbl_posto_linha.posto    = $login_posto
				AND   tbl_peca.ativo is TRUE";
*/
?>
