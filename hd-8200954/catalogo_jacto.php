<?php
error_reporting(E_ERROR & E_NOTICE);
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';

	$title       = "CATÁLOGO DE PEDIDOS";

	header("Content-Type: text/html; charset=utf-8");

	//Modo = Visualização, usando quando o catalogo é acessado mais não pode gerar pedidos!
	$modulo = @$_REQUEST['modulo'];

	/*
	 * Tradução
	 */

	$trad_jacto = array(
		'pt-br' => array (
			'catalogo_peca'               => 'Catálogo de Peças',
			'menu'                        => 'Menu',
			'voltar'                      => 'Voltar',
			'fechar'                      => 'Fechar',
			'mensagem_rodape'             => 'Para selecionar, posicione o mouse na op&ccedil;&atilde;o desejada e clique para acessar os dados.',
			'btn_gravar_pecas'            => 'Gravar peças selecionadas',
			'lng_referencia'              => 'Referência',
			'lng_descricao'               => 'Descrição',
			'lng_serie'                   => 'Série',
			'lng_produto'                 => 'Produto',
			'lng_desenho'                 => 'Desenho',
			'lng_edicao'                  => 'Edi&ccedil;&atilde;o',
			'lng_conjunto_montagem'       => 'Conjunto de Montagem',
			'lng_quant'                   => 'Quant',
			'lng_nenhuma_peca_encontrada' => 'Nenhuma pe&ccedil;a encontrada!',
			'lng_nenhum_catalogo'		  => 'Nenhum catalogo encontrado!',
		),
		'es'    => array(
			'catalogo_peca'               => 'Catálogo de piezas',
			'menu'                        => 'Menú',
			'voltar'                      => 'Volver',
			'fechar'                      => 'Cerrar',
			'mensagem_rodape'             => 'Para seleccionar, coloque el cursor sobre la opción deseada y haga clic para acceder a los datos.',
			'btn_gravar_pecas'            => 'Guardar piezas seleccionadas',
			'lng_referencia'              => 'Referencia',
			'lng_descricao'               => 'Descripción',
			'lng_serie'                   => 'Serie',
			'lng_produto'                 => 'Producto',
			'lng_desenho'                 => 'Esquema',
			'lng_edicao'                  => 'Edición',
			'lng_conjunto_montagem'       => 'Kit de montaje',
			'lng_quant'                   => 'Cant',
			'lng_nenhuma_peca_encontrada' => 'Pieza(s) no encontrada(s)',
			'lng_nenhum_catalogo'		  => '¡!No se encontró ningún catálogo!',
		),
		'en-US' => array(
			'catalogo_peca'               => 'Parts Catalog',
			'menu'                        => 'Menu',
			'voltar'                      => 'Back',
			'fechar'                      => 'Close',
			'mensagem_rodape'             => 'To select, place your mouse on the desired option and click to access the data.',
			'btn_gravar_pecas'            => 'Save selected parts',
			'lng_referencia'              => 'Reference',
			'lng_descricao'               => 'Description',
			'lng_serie'                   => 'Series',
			'lng_produto'                 => 'Product',
			'lng_desenho'                 => 'Drawing',
			'lng_edicao'                  => 'Edition',
			'lng_conjunto_montagem'       => 'Mounting Kit',
			'lng_quant'                   => 'Qty',
			'lng_nenhuma_peca_encontrada' => 'No item found',
			'lng_nenhum_catalogo'		  => 'No Catalog found',
		)
	);

	$lng = $_COOKIE['idioma_jacto_catalogo'];
	if(strlen($lng) == 0)
		$lng = "Portugues(BRA)";
//die($lng);
	switch ($lng) {
		case 'ESP':
			extract($trad_jacto['es']);
			$lng = 'ESPAÑOL';
			break;
		
		case 'ENGLISH':
			extract($trad_jacto['en-US']);
			break;
		
		default:
			extract($trad_jacto['pt-br']);
			break;
	}

	$gravar_itens_selecionados = @$_POST['gravar_itens_selecionados'];
	if(strlen($gravar_itens_selecionados) > 5){
		$pecas_codigo = $_POST['peca_codigo'];
		if(strlen($pecas_codigo) == 0){
			echo "<script type='text/javascript'>";
				echo "window.location= catalogo_jacto.php";
			echo "</script>";
		}

		?>
			<script type='text/javascript'>
				var retorno_pecas = new Array();
				var contador = 0;
			</script>
		<?
				foreach ($pecas_codigo as $peca){
			$peca;
				
			$referencia = $_POST["referencia_$peca"];
			$quantidade = $_POST["quantidade_$peca"];

			if($quantidade < 1)
				$quantidade = 1;

			$retorno = $referencia."|".$quantidade;
			//$retorno = "111138|$quantidade";
			?>
			<script type='text/javascript'>
				retorno_pecas[contador] = '<?php echo $retorno;?>';
				contador ++;
			</script>
		<?php
		}
		?>
			<script type='text/javascript'>
				window.opener.importaPecaViaCatalogo(retorno_pecas);
				window.close();
			</script>
		<?
		$gravar_itens_selecionados = "";
	}

	if($_POST['act'] == 'print_catalogo'){
		$catalogo = $_POST['catalogo'];

		if($catalogo == 2){
			$id =$_POST['id'];

			$sql = "SELECT 
						nome
					FROM 
						tbl_jacto_grupo_catalogo 
					WHERE 
						grupo_catalogo = $id
						AND fabrica = $login_fabrica
					LIMIT 1";
			//echo $sql;
			$res = pg_query ($con,$sql);
			$catalogo = (pg_fetch_result($res,0,'nome'));

			echo "<h2>$catalogo_peca</h2>";
			echo "<h1>{$catalogo}</h1>";

			$sql = "SELECT 
					catalogo	,
					nome
				FROM tbl_jacto_catalogo
				WHERE 
					grupo_catalogo = $id
					AND fabrica = $login_fabrica
				ORDER BY nome ASC
				;";
			//echo $sql;
			$res   = pg_query($con,$sql);
			$total = pg_num_rows($res);
			
			if($total > 0){
				echo "<div id='catalogo_2'>";
						for ($i = 0 ; $i < $total; $i++) {
							$catalogo = pg_fetch_result($res, $i, 'catalogo');
							$nome     = pg_fetch_result($res, $i, 'nome');

							echo "<a href='javascript:void(0);' onclick='catalogoJacto(\"catalogo_2\",{$catalogo});' ><input type='button' value ='{$nome}' /></a>";
						}
					echo "<div style='clear: both;'>&nbsp;</div>";
				echo "</div>";
			}else{
				echo "<div class='alerta'>$lng_nenhum_catalogo</div>";
			}
			echo "<div class='msg_rodape'>$mensagem_rodape</div>";
		}

		if($catalogo == 3){
			$sql = "SELECT nome FROM tbl_jacto_catalogo WHERE catalogo = $id LIMIT 1";
			$res = pg_query ($con,$sql);
			$catalogo = (pg_fetch_result($res,0,'nome'));
			echo "<h2>$catalogo_peca</h2>";
			echo "<h1>{$catalogo}</h1>";

			$sql = "	SELECT 	
						catalogo	,
						referencia	,
						descricao
					FROM 
						tbl_jacto_produto
					WHERE 
						catalogo = $id
						AND fabrica = $login_fabrica
						AND desenho = ''
					GROUP BY 
						catalogo, referencia, descricao
					ORDER BY 
						descricao ASC
					;";
			$sql   = ($sql);
			$res   = pg_query($con,$sql);
			$total = pg_num_rows($res);
			
			if($total > 0) {
				
				echo "<table border='0' width='98%' cellpadding='2' cellspacing='0' class='table' align='center' >";
					echo "<tr class='titulo_tabela'>";
						echo "<th width='*' colspan='2'>$lng_descricao</th>";
						echo "<th width='120px'>RG</th>";
					echo "</tr>";

				for ($i = 0 ; $i < $total; $i++) {

					$catalogo   = pg_fetch_result($res,  $i, 'catalogo');
					$referencia = pg_fetch_result($res,  $i, 'referencia');
					$descricao  = (pg_fetch_result($res, $i, 'descricao'));

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						
						$sql_verifica_serie = "SELECT
										serie		, 
										mes		, 
										edicao	,
										produto	,
										referencia
									FROM
										tbl_jacto_produto 
									WHERE 
										catalogo = $id
										AND fabrica = $login_fabrica
										AND referencia = '$referencia'
									ORDER BY edicao DESC, mes DESC, produto DESC
									;";
					//echo $sql_verifica_serie;
					$res_verifica_serie = pg_query($con,$sql_verifica_serie);
					$total_verifica_serie = pg_num_rows($res_verifica_serie);

					if($total_verifica_serie == 1) {
						$sql_produto = "
							SELECT 
								produto 
							FROM 
								tbl_jacto_produto
							WHERE
								catalogo = $id
								AND fabrica = $login_fabrica
								AND referencia = '$referencia'
							LIMIT 1;
						";
						$res_produto = pg_query($con,$sql_produto);
						$produto = pg_fetch_result($res_produto,0,0);
						//echo $sql_produto;

						echo "<tr bgcolor='$cor'>";
							echo "<td colspan='2'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >{$descricao}</a></td>";
							echo "<td align='center'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >".str_pad($referencia, 10,'0',STR_PAD_LEFT)."</a></td>";
						echo "</tr>";
					}else{
						for ($x = 0 ; $x < $total_verifica_serie; $x++) {
							$produto          = pg_fetch_result($res_verifica_serie, $x, 'produto');
							$serie            = pg_fetch_result($res_verifica_serie, $x, 'serie');
							$mes              = pg_fetch_result($res_verifica_serie, $x, 'mes');
							$edicao           = pg_fetch_result($res_verifica_serie, $x, 'edicao');
							$referencia_serie = pg_fetch_result($res_verifica_serie, $x, 'referencia');
							
							$class = $referencia;

							if($x == 0){
								echo "<tr bgcolor='$cor' id='$class' onclick='mostraSeries(\"$class\");'>";
									echo "<td colspan='2'><a href='javascript:void(0);' >{$descricao}</a></td>";
									echo "<td align='center'>&nbsp;</td>";
								echo "</tr>";
							
								echo "<tr class='titulo_tabela $class' style='display: none; background-color: #000'>";
									echo "<th width='*'>$lng_referencia</th>";
									echo "<th width='*'>$lng_serie</th>";
									echo "<th width='120px'>$lng_edicao</th>";
								echo "</tr>";
							}
							echo "<tr style='display: none; background-color: #CCC' class='$class'>";
								echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >".str_pad($referencia_serie, 10,'0',STR_PAD_LEFT)."</a></td>";
								echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >{$serie}</a></td>";
								echo "<td align='center'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >".str_pad($edicao, 4,'0',STR_PAD_LEFT)."/".str_pad($mes, 2,'0',STR_PAD_LEFT)."</a></td>";
							echo "</tr>";
						}

					}
				}
				echo "</table>";
			} else {
				$sql = "SELECT DISTINCT
						catalogo	,
						referencia	,
						descricao	,
						desenho	,
						produto	
					FROM 
						tbl_jacto_produto
					WHERE 
						catalogo = $id
						AND fabrica = $login_fabrica
						AND desenho <> ''
					ORDER BY descricao ASC
					;";
					$sql   = ($sql);
					$res   = pg_query($con, $sql);
					$total = pg_num_rows($res);

				if($total > 0) {
				
					echo "<table border='0' width='98%' cellpadding='2' cellspacing='0' class='table' align='center'>";
					echo "<tr class='titulo_tabela'>";
						echo "<th width='*' colspan='2'>$lng_descricao</th>";
						echo "<th width='120px'>RG</th>";
					echo "</tr>";

					for ($i = 0 ; $i < $total; $i++) {
						$catalogo   = pg_fetch_result($res, $i, 'catalogo');
						$referencia = pg_fetch_result($res, $i, 'referencia');
						$descricao  = pg_fetch_result($res, $i, 'descricao');
						$desenho    = pg_fetch_result($res, $i, 'desenho');
						$produto    = pg_fetch_result($res, $i, 'produto');

						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							
							$sql_verifica_serie = "SELECT
											serie		, 
											mes		, 
											edicao	
										FROM
											tbl_jacto_produto 
										WHERE 
											catalogo = $id
											AND fabrica = $login_fabrica
											AND referencia = '$referencia'
										ORDER BY edicao DESC, mes DESC, produto DESC
										;";
						//echo $sql_verifica_serie;
						$res_verifica_serie   = pg_query($con,$sql_verifica_serie);
						$total_verifica_serie = pg_num_rows($res_verifica_serie);

						if($total_verifica_serie == 1) {

							echo "<tr bgcolor='$cor'>";
								echo "<td colspan='2'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3_alternativa\",{$produto});' >{$descricao}</a></td>";
								echo "<td align='center'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3_alternativa\",{$produto});' >".str_pad($referencia, 10,'0',STR_PAD_LEFT)."</a></td>";
							echo "</tr>";
						}else{
							for ($x = 0 ; $x < $total_verifica_serie; $x++) {
								$produto          = pg_fetch_result($res_verifica_serie, $x, 'produto');
								$serie            = pg_fetch_result($res_verifica_serie, $x, 'serie');
								$mes              = pg_fetch_result($res_verifica_serie, $x, 'mes');
								$edicao           = pg_fetch_result($res_verifica_serie, $x, 'edicao');
								$referencia_serie = pg_fetch_result($res_verifica_serie, $x, 'referencia');
								
								$class = $referencia;

								if($x == 0){
									echo "<tr bgcolor='$cor' id='$class' onclick='mostraSeries(\"$class\");'>";
										echo "<td colspan='2'><a href='javascript:void(0);' >{$descricao}</a></td>";
										echo "<td align='center'>&nbsp;</td>";
									echo "</tr>";
								
									echo "<tr class='titulo_tabela $class' style='display: none; background-color: #000'>";
										echo "<th width='*'>$lng_referencia</th>";
										echo "<th width='*'>$lng_serie</th>";
										echo "<th width='120px'>$lng_edicao</th>";
									echo "</tr>";
								}
								echo "<tr style='display: none; background-color: #CCC' class='$class'>";
									echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >".str_pad($referencia_serie, 10,'0',STR_PAD_LEFT)."</a></td>";
									echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >{$serie}</a></td>";
									echo "<td align='center'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >".str_pad($edicao, 4,'0',STR_PAD_LEFT)."/".str_pad($mes, 2,'0',STR_PAD_LEFT)."</a></td>";
								echo "</tr>";
							}

						}
					}
					echo "</table>";
				}
			}
			
			echo "<div class='msg_rodape'>$mensagem_rodape</div>";
		}

		if($catalogo == 4){
			$sql = "SELECT referencia, descricao, serie, edicao, mes, catalogo FROM tbl_jacto_produto WHERE produto = '{$id}' LIMIT 1";
			$res           = pg_query ($con,$sql);
			$catalogo      = pg_fetch_result($res, 0, 'catalogo');
			$descricao     = pg_fetch_result($res, 0, 'descricao');
			$serie         = pg_fetch_result($res, 0, 'serie');
			$referencia_bd = pg_fetch_result($res, 0, 'referencia');

			$referencia = str_pad(pg_fetch_result($res, 0, 'referencia'), 10, '0', STR_PAD_LEFT);
			$edicao     = str_pad(pg_fetch_result($res, 0, 'edicao'),     4,  '0', STR_PAD_LEFT);
			$mes        = str_pad(pg_fetch_result($res, 0, 'mes'),        2,  '0', STR_PAD_LEFT);

			echo "<h2>$catalogo_peca</h2>";
			echo "<h1>{$descricao}<br>
					<strong>
						RG: {$referencia} - 
						$lng_serie: {$serie} - 
						$lng_edicao: {$edicao}/{$mes}
					</strong>
				</h1>";

			$sql = "
			SELECT
				F.produto     AS produto,
				P.produto     AS produto_pai,
				F.referencia  AS referencia,
				F.serie       AS serie,
				F.mes         AS mes,
				F.edicao      AS edicao,
				F.modificacao AS modificacao,
				F.descricao   AS descricao,
				F.versao      AS versao,
				F.desenho     AS desenho
			FROM 
				tbl_jacto_lista 
				JOIN tbl_jacto_produto P ON tbl_jacto_lista.referencia_pai = P.referencia
										AND tbl_jacto_lista.modificacao_pai = P.modificacao
										AND tbl_jacto_lista.serie_pai = P.serie
										AND tbl_jacto_lista.edicao_pai = P.edicao
										AND tbl_jacto_lista.mes_pai = P.mes
										AND tbl_jacto_lista.versao_pai = P.versao
				JOIN tbl_jacto_produto F ON tbl_jacto_lista.referencia_filho = F.referencia 
										AND tbl_jacto_lista.mes_filho = F.mes 
										AND tbl_jacto_lista.edicao_filho = F.edicao 
										AND tbl_jacto_lista.serie_filho = F.serie 
										AND tbl_jacto_lista.versao_filho = F.versao
										AND tbl_jacto_lista.modificacao_filho = F.modificacao
					AND F.idioma=P.idioma
			WHERE
				P.produto = $id 
				AND F.desenho <> ''
				AND F.descricao <> ''
			ORDER BY
				descricao";

			//echo nl2br($sql);

				$res = pg_query ($con,$sql);
				$total = pg_num_rows($res);
				
				if($total > 0){

					for ($w = 0 ; $w < $total; $w++) {
						$produto_array[] = (pg_fetch_result($res,$w,'produto'));
					}

					//print_r($produto_array);
										
					echo "<table border='0' width='98%' cellpadding='2' cellspacing='0' class='table' align='center' >";
						echo "<tr class='titulo_tabela'>";
							echo "<th width='90px'>$lng_referencia</th>";
							echo "<th width='*'>$lng_produto</th>";
							echo "<th width='55px'>$lng_desenho</th>";
						echo "</tr>";

						for ($i = 0 ; $i < $total; $i++) {
							$descricao   = (pg_fetch_result($res, $i, 'descricao'));
							$referencia  = pg_fetch_result($res , $i, 'referencia');
							$serie       = pg_fetch_result($res , $i, 'serie');
							$mes         = pg_fetch_result($res , $i, 'mes');
							$edicao      = pg_fetch_result($res , $i, 'edicao');
							$modificacao = pg_fetch_result($res , $i, 'modificacao');
							$desenho     = pg_fetch_result($res , $i, 'desenho');
							$produto     = pg_fetch_result($res , $i, 'produto');
							$versao      = pg_fetch_result($res , $i, 'versao');

							//echo $descricao; echo "<br>";
							$sql_verifica_filho = "
								SELECT
									F.produto     AS produto,
									P.produto     AS produto_pai,
									F.referencia  AS referencia,
									F.serie       AS serie,
									F.mes         AS mes,
									F.edicao      AS edicao,
									F.modificacao AS modificacao,
									F.descricao   AS descricao,
									F.versao      AS versao,
									F.desenho     AS desenho
								FROM
									tbl_jacto_lista
									JOIN tbl_jacto_produto P ON tbl_jacto_lista.referencia_pai    = P.referencia
															AND tbl_jacto_lista.modificacao_pai   = P.modificacao
															AND tbl_jacto_lista.serie_pai         = P.serie
															AND tbl_jacto_lista.edicao_pai        = P.edicao
															AND tbl_jacto_lista.mes_pai           = P.mes
															AND tbl_jacto_lista.versao_pai        = P.versao
									JOIN tbl_jacto_produto F ON tbl_jacto_lista.referencia_filho  = F.referencia
															AND tbl_jacto_lista.mes_filho         = F.mes
															AND tbl_jacto_lista.edicao_filho      = F.edicao
															AND tbl_jacto_lista.serie_filho       = F.serie
															AND tbl_jacto_lista.versao_filho      = F.versao
															AND tbl_jacto_lista.modificacao_filho = F.modificacao
															AND F.idioma                          = P.idioma
								WHERE
									P.produto = $produto
									AND P.produto <> F.produto 
									AND P.desenho <> ''
									AND F.desenho <> '' ";
							//echo nl2br($sql_verifica_filho); echo "<br>";

							$res_verifica_filho = pg_query ($con,$sql_verifica_filho);
							$total_filho = pg_num_rows($res_verifica_filho);

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							
							if($total_filho > 0){

								$produto2 = pg_fetch_result($res_verifica_filho,0,produto);
								
								if (!in_array($produto2,$produto_array)) {
									echo "<tr bgcolor='$cor'>";
									echo "<td align='center'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >".
										str_pad($referencia,10,'0',STR_PAD_LEFT).
										"</a></td>";
										echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >+ {$descricao}</a></td>";
										echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\",{$produto});' >{$desenho}</a></td>";
									echo "</tr>";
								} else {
									echo "<tr bgcolor='$cor'>";
									echo "<td align='center'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >".
										str_pad($referencia,10,'0',STR_PAD_LEFT).
										"</a></td>";
										echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >{$descricao}</a></td>";
										echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >{$desenho}</a></td>";
									echo "</tr>";
								}
							}else{
								echo "<tr bgcolor='$cor'>";
								echo "<td align='center'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >".
									str_pad($referencia,10,'0',STR_PAD_LEFT).
									"</a></td>";
									echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >{$descricao}</a></td>";
									echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >{$desenho}</a></td>";
								echo "</tr>";
							}

						}
					echo "</table>";
				}
			echo "<div class='msg_rodape'>$mensagem_rodape</div>";
		}

		if($catalogo == 5){
			$sql = "SELECT referencia, catalogo, descricao, serie, edicao, mes, versao FROM tbl_jacto_produto WHERE referencia = '$id' LIMIT 1;";
			//echo $sql;
			$res        = pg_query ($con,$sql);
			$referencia = pg_fetch_result($res, 0, 'referencia');
			$catalogo   = pg_fetch_result($res, 0, 'catalogo');
			$descricao  = pg_fetch_result($res, 0, 'descricao');
			$serie      = pg_fetch_result($res, 0, 'serie');
			$edicao     = pg_fetch_result($res, 0, 'edicao');
			$mes        = pg_fetch_result($res, 0, 'mes');
			$versao     = pg_fetch_result($res, 0, 'versao');

			echo "<h2>$catalogo_peca</h2>";
			echo "<h1>{$descricao}<br>
							<strong>
								RG: {$referencia} - 
								$lng_serie: {$serie} - 
								$lng_edicao: {$edicao}/{$mes}
							</strong>
						</h1>";

				$sql = "SELECT 
						tbl_jacto_produto.produto AS produto		,
						tbl_jacto_produto.descricao AS descricao		,
						tbl_jacto_produto.referencia AS referencia
					FROM tbl_jacto_lista
					JOIN 
						tbl_jacto_produto AS p ON (tbl_jacto_lista.referencia_filho = tbl_jacto_produto.referencia) 
					WHERE 
						desenho != '' 
						AND tbl_jacto_lista.referencia_pai = '$id' 
						AND tbl_jacto_produto.idioma = '$lng'
						AND tbl_jacto_produto.serie = '$serie'
						AND tbl_jacto_produto.edicao = '$edicao'
						AND tbl_jacto_produto.mes = '$mes'
						AND tbl_jacto_produto.versao = '$versao'
					ORDER BY tbl_jacto_lista.posicao ASC ";
				//echo $sql;
				$res = pg_query($con, $sql);
				$total = pg_num_rows($res);
				
				if($total > 0){
					echo "<table border='0' width='98%' cellpadding='2' cellspacing='0' class='table' align='center' >";
						echo "<tr class='titulo_tabela'>";
							echo "<th width='*' colspan='2'>$lng_conjunto_montagem</th>";
							echo "<th width='120px'>RG</th>";
						echo "</tr>";

						for ($i = 0 ; $i < $total; $i++) {
							$produto    = pg_fetch_result($res, $i, 'produto');
							$descricao  = pg_fetch_result($res, $i, 'descricao');
							$referencia = pg_fetch_result($res, $i, 'referencia');

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

							$sql_verifica_filho = "	SELECT 
												p.produto AS produto				,
												p.descricao AS descricao		,
												p.referencia AS referencia
											FROM tbl_jacto_lista AS l 
												JOIN tbl_jacto_produto AS p ON (l.produto_filho = p.produto) 
											WHERE 
												desenho != '' 
												AND p.produto_pai = $produto
												AND idioma        = '$lng'
												AND p.serie       = '$serie'
												AND p.edicao      = '$edicao'
												AND p.mes         = '$mes'
											ORDER BY l.posicao ASC ";
							$res_verifica_filho = pg_query($con, $sql_verifica_filho);
							$total_filho = pg_num_rows($res_verifica_filho);

							if($total_filho > 1){
								echo "<tr bgcolor='$cor'>";
									echo "<td colspan='2'><a href='#' style='text-decoration: none; border: none;' onclick='javascript: mostraFilho({$produto})' ><img src='imagens/mais.bmp' title='{$descricao}' class='img_{$produto}' style='margin: 0; padding: 0; border: none; margin-top: 3px;' /> {$descricao}</a></td>";
									echo "<td align='center'>".str_pad($referencia, 10,'0',STR_PAD_LEFT)."</td>";
								echo "</tr>";

								$class_filho = $produto;

								for ($x = 0 ; $x < $total_filho; $x++) {
									$produto    = pg_fetch_result($res, $x , 'produto');
									$descricao  = pg_fetch_result($res, $x , 'descricao');
									$referencia = pg_fetch_result($res, $xi, 'referencia');

									$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

									echo "<tr bgcolor='$cor' class='{$class_filho}' style='display: none;'>";
										echo "<td>&nbsp;</td>";
										echo "<td><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >{$descricao}</a></td>";
										echo "<td align='center'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >".str_pad($referencia, 10,'0',STR_PAD_LEFT)."</a></td>";
									echo "</tr>";
								}
							}else{
								echo "<tr bgcolor='$cor'>";
									echo "<td colspan='2'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >{$descricao}</a></td>";
									echo "<td align='center'><a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca_3\",{$produto});' >".str_pad($referencia, 10,'0',STR_PAD_LEFT)."</a></td>";
								echo "</tr>";
							}


						}
					echo "</table>";
				}

			echo "<div class='msg_rodape'>$mensagem_rodape</div>";
		}

		if($catalogo == 6){
			$sql = "SELECT 
					referencia, 
					catalogo, 
					descricao, 
					serie, 
					edicao, 
					mes, 
					desenho,
					modificacao,
					versao
				FROM 
					tbl_jacto_produto 
				WHERE 
					produto = '{$id}'
				LIMIT 1";
			//echo nl2br($sql);
			$res = pg_query ($con,$sql);
			$referencia  = pg_fetch_result($res, 0, 'referencia');
			$catalogo    = pg_fetch_result($res, 0, 'catalogo');
			$descricao   = pg_fetch_result($res, 0, 'descricao');
			$serie       = pg_fetch_result($res, 0, 'serie');
			$edicao      = pg_fetch_result($res, 0, 'edicao');
			$mes         = pg_fetch_result($res, 0, 'mes');
			$desenho     = pg_fetch_result($res, 0, 'desenho');
			$modificacao = pg_fetch_result($res, 0, 'modificacao');
			$versao      = pg_fetch_result($res, 0, 'versao');

			echo "<h2>$catalogo_peca</h2>";
			echo "<h1>{$descricao}<br>
					<strong>
						RG: {$referencia} - 
						$lng_serie: {$serie} - 
						$lng_edicao: {$edicao}/{$mes}
					</strong>
				</h1>";

			$sql = "
				SELECT
					tbl_jacto_lista.lista AS lista ,
					F.descricao AS descricao ,
					tbl_jacto_lista.referencia_filho AS referencia ,
					tbl_jacto_lista.posicao AS posicao ,
					tbl_jacto_lista.quantidade AS quantidade
				FROM
					tbl_jacto_lista
					JOIN tbl_jacto_produto P ON tbl_jacto_lista.referencia_pai = P.referencia 
						AND tbl_jacto_lista.modificacao_pai = P.modificacao
						AND tbl_jacto_lista.serie_pai = P.serie
						AND tbl_jacto_lista.edicao_pai = P.edicao
						AND tbl_jacto_lista.mes_pai = P.mes
						AND tbl_jacto_lista.versao_pai = P.versao
					JOIN tbl_jacto_produto F ON tbl_jacto_lista.referencia_filho = F.referencia
						AND tbl_jacto_lista.modificacao_filho = F.modificacao
						AND tbl_jacto_lista.serie_filho = F.serie
						AND tbl_jacto_lista.edicao_filho = F.edicao
						AND tbl_jacto_lista.mes_filho = F.mes
						AND tbl_jacto_lista.versao_filho = F.versao
						AND F.idioma=P.idioma
				WHERE
					P.produto = $id
					AND tbl_jacto_lista.posicao <> ''
					AND F.descricao <> ''
				ORDER BY
					tbl_jacto_lista.posicao ASC";
				//echo nl2br($sql); //

				$res = pg_query($con,$sql);
				$total = pg_num_rows($res);

				echo "<table border='0' width='900px' cellpadding='2' cellspacing='0' align='center'>";
					echo "<tr>";
						echo "<td width='400px' valign='top' style='min-height: 500px'>";
							?>
							<link href="js/cloud-zoom/cloud-zoom.css" rel="stylesheet" type="text/css" />
							<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
							<script type="text/javascript" src="js/cloud-zoom/cloud-zoom.1.0.2.min.js"></script>
							<?
							echo "<div style='overflow: auto;'>";
								echo "<a href='jacto/imagens/$desenho.JPG' class = 'cloud-zoom' id='zoom1' rel=\"position: 'inside' , showTitle: true, adjustX:-4, adjustY:-4\" target='_blank' style='border: none; z-index: 0;'>";
									echo "<img src='jacto/imagens/$desenho.JPG' alt='$descricao' title='$descricao' width='400px' style='border: none; z-index: 0;' />";
								echo "</a>";
							echo "</div>";
						echo "</td>";
						echo "<td width='*' valign='top'>";
							if($total > 0){
								echo "<form name='frm_pedido_pecas' method='post' action=''>";
								echo "<table border='0' width='400px' cellpadding='2' cellspacing='0' class='table' align='center' >";
									echo "<tr class='titulo_tabela' style='font-size: 10px'>";
										if(strlen($modulo) == 0)
											echo "<th width='10px' colspan='2'>&nbsp;</th>";
										else
											echo "<th width='10px'>&nbsp;</th>";
										echo "<th width='50px' style='font-size: 10px'>RG</th>";
										echo "<th width='*' style='font-size: 10px'>$lng_descricao</th>";
										if(strlen($modulo) == 0)
											echo "<th width='30px' style='font-size: 10px'>$lng_quant.</th>";
									echo "</tr>";

									for ($i = 0 ; $i < $total; $i++) {
										$lista      = pg_fetch_result($res, $i, 'lista');
										$descricao  = pg_fetch_result($res, $i, 'descricao');
										$referencia = pg_fetch_result($res, $i, 'referencia');
										$posicao    = pg_fetch_result($res, $i, 'posicao');
										$quantidade = pg_fetch_result($res, $i, 'quantidade');

										$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
										
										if(strlen($modulo) == 0)
											echo "<tr bgcolor='$cor' style='cursor: url(imagens/icone_carrinho.gif),auto;'>";
										else
											echo "<tr bgcolor='$cor' style='cursor: pointer;'>";
											if(strlen($modulo) == 0)
												echo "<td style='font-size: 10px;' ><input type='checkbox' name='peca_codigo[]' value='$lista' id='check_$lista'/></td>";
											
											echo "<td style='font-size: 10px;' onclick='verificaCheck($lista)'>".str_pad($posicao, 2,'0',STR_PAD_LEFT)."</td>";
											echo "<td style='font-size: 10px;' onclick='verificaCheck($lista)'>".str_pad($referencia, 8,'0',STR_PAD_LEFT)."</td>";
											echo "<td style='font-size: 9px;'  onclick='verificaCheck($lista)'>$descricao</td>";
											if(strlen($modulo) == 0)
												echo "<td style='font-size: 10px; text-align: center' >
													<!-- <a href='javascript:void(0);' onclick='catalogoJacto(\"gravar_itens\",{$lista});' >".str_pad($quantidade, 3,'0',STR_PAD_LEFT)."</a> //-->
													<input type='hidden' name='referencia_$lista' value='$referencia' />
													<input type='text' name='quantidade_$lista' value='$quantidade' style='width: 30px;' maxlength='4' />
													</td>";
										echo "</tr>";
									}
									
									if(strlen($modulo) == 0){
										echo "<tr>";
											echo "<th style='text-align: center' colspan='5'  class='titulo_tabela'>";
												echo "<div style='text-align: center; margin: 10px auto;'><input type='submit' value=' $btn_gravar_pecas ' name='gravar_itens_selecionados' /></div>";
											echo "</th>";
										echo "</tr>";
									}
								echo "</table>";

								

								echo "</form>";
							}else{
								echo $lng_nenhuma_peca_encontrada;
							}
						echo "</td>";
					echo "</tr>";
				echo "</table>";

				echo "<div class='msg_rodape'>$mensagem_rodape</div>";
		}
		exit;
	}
?>
<html>
	<head>
		<title><?php echo $catalogo_peca;?> - JACTO</title>
		<style type="text/css" media='all'>
			* {
				font-family: Verdana, sans-serif;
			}

			body{
				margin: 0px;
				padding: 0px;
				text-align: center;
			}

			#geral{
				width: 100%;
				height: 600px ;
				text-align: left;
				position: relative;
			}

			#geral ul, #geral ul li{
				margin: 0px;
				padding: 0px;
				list-style: none;
				float: left;
			}

			.msg_rodape{
				background: #596d9b;
				position: fixed;
				bottom: 0;
				left: 0;
				padding: 10px;
				width: 100%;
				text-align: center;
				color: #FFF;
				border-top: 2px solid #000;
				font-size: 14px;
				z-index: 100;
			}

			#menu{
				height: 35px;
				text-align: right;
				position: relative;
				background: #596d9b;
				margin-bottom: 10px;
			}

			#menu ul{
				text-align: right;
				position: absolute;
				top: 0;
				right: 0;
			}
			
			#menu ul li{
				padding: 5px 20px;
			}

			#menu div.img_paises{
				position: absolute;
				top: 6px;
				left: 10px;
				cursor: pointer;
			}

			#menu div#btn_menu{
				position: absolute;
				top: 10px;
				right: 120px;
				cursor: pointer;
			}

			#menu div#btn_voltar_ajax{
				position: absolute;
				top: 10px;
				right: 68px;
				cursor: pointer;
			}

			#menu div#btn_sair{
				position: absolute;
				top: 10px;
				right: 10px;
				cursor: pointer;
			}

			#menu a{
				font-size: 12px;
				color: #FFF;
			}
	
			#conteudo{
				padding: 10px;
				z-index: 0;
			}

			#conteudo ul.ul_catalogo_1 li{
				width: 127px;
				text-align: center;
				position: relative;
				height: 150px;
				margin: 0 0 10px 7px;
			}
			#conteudo ul li div{
				position: absolute;
				top: 0;
				left: 0;
			}

			#conteudo ul li div.titulo{
				z-index: 10;
				color: #333;
				padding: 5px;
			}

			#conteudo ul li div.titulo p{
				display: table-cell;
				vertical-align: bottom;
				height: 30px;
				width: 110px;
				text-align: center;
				font-size: 10px;
				text-transform: UPPERCASE;
				margin: 0 auto;
			}

			#conteudo ul li .imagem{
				z-index: 0;
				width: 123px;
				padding: 0 auto;
				top: 10px;
				min-height: 500px;
			}

			#conteudo ul li img{
				border: none;
				width: 120px;
			}

			a{
				border: none;
				text-decoration: none;
				cursor: pointer;
			}

			h1{
				font-size: 16px;
				text-align: center;
				padding: 5px;
				margin: 0 0 20px 0;
				font-weight: bold;
				color: #596d9b;
				text-align: right;
				border-bottom: 2px solid #596d9b;
			}

			h1 strong{
				font-size: 12px;
				font-weight: normal;
			}
			
			h2{
				font-size: 14px;
				text-align: left;
				margin: 0 10px;;
				font-weight: normal;
				color: #596d9b;
			}

			#catalogo_2 input{
				margin: 0 15px 20px 0;
				padding: 5px 10px;
			}

			.table{
				border: 1px solid #596d9b;
				margin: 0 auto;
				margin-bottom: 150px;
			}

			.titulo_tabela{
				background-color:#596d9b;
				font: bold 12px "Arial";
				color:#FFFFFF;
				text-align:center;
			}

			.titulo_coluna{
				background-color:#596d9b;
				font: bold 11px "Arial";
				color:#FFFFFF;
				text-align:center;
			}

			.table td{
				font-family: verdana;
				font-size: 11px;
				border-right: 1px solid #596d9b;
				border-top: 1px solid #596d9b;
			}

			.table tr td:last-child{
				border-right: none;
			}

			.table tr:hover td, .table tr:hover td a{
				background: #999;
				color: #FFF;
			}

			.table td a{
				color: #333;
			}

			img{
				z-index: 0;
			}

			.carregador{
				width: 100%;
				height: 600px;
				text-align: center; 
				padding: 10px; 
				z-index: 1000; 
				background: url(imagens/bg_transparente_cinza_50.png) repeat; 
				position: absolute; 
				text-align: center; 
				display: none;
			}


		</style>
		<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
		<script type="text/javascript" src="admin/js/ajax.js"></script>
		<script type="text/javascript">
			$(window).load(function() {
				$("html").css("background","#FFF");
			});
			function verificaCheck(campo){
				var checked = '#check_'+campo;

				if(campo.length == 0){
					return false;
				}else{
					if ($((checked)).is(':checked'))
						$(checked).attr('checked',false);
					else
						$(checked).attr('checked',true);
				}
			}

			function Cookie(name,value,days) {
				if (days) {
					var date = new Date();
					date.setTime(date.getTime()+(days*24*60*60*1000));
					var expires = "; expires="+date.toGMTString();
				}
				else var expires = "";
				document.cookie = name+"="+value+expires+"; path=/";
			}

			var retorno = {

				grupo_catalogo:  '',
				catalogo:        '',
				produto:         '',
				peca:            '',
				lista:           ''

			};

			var cnt = 0;

			function catalogoJacto(pagina,id){
				var id = id;
				var pagina = pagina;
				
				//$("#dados").html("PAG: "+pagina +" ID: "+ id);

				if(pagina == 'catalogo_1'){
					$('.carregador').css('display','block');
					$.ajax({
						url: '<?php echo "catalogo_jacto.php?modulo=$modulo";?>',
						type: "POST",
						data: "catalogo=2&id="+id+"&act=print_catalogo",
						success: function(resposta){
							retorno.grupo_catalogo = id;
							$('#btn_voltar_ajax').html("<a href=\"<?php echo 'catalogo_jacto.php?modulo='.$modulo;?>\" ><?php echo $voltar;?></a>");
							$('#conteudo').html(resposta);
							$('.carregador').css('display','none');
						}
					});
				}
				
				if(pagina == 'catalogo_2'){
					$('.carregador').css('display','block');
					$.ajax({
						url: '<?php echo "catalogo_jacto.php?modulo=$modulo";?>',
						type: "POST",
						data: "catalogo=3&id="+id+"&act=print_catalogo",
						success: function(resposta){
							retorno.catalogo = id;
							$('#btn_voltar_ajax').html("<a href='javascript:void(0);' onclick='catalogoJacto(\"catalogo_1\","+retorno.grupo_catalogo+");'><?php echo $voltar;?></a>");
							$('#conteudo').html(resposta);
							$('.carregador').css('display','none');
						}
					});
				}
				
				if(pagina == 'catalogo_3'){
					$('.carregador').css('display','block');
					$.ajax({
						url: '<?php echo "catalogo_jacto.php?modulo=$modulo";?>',
						type: "POST",
						data: "catalogo=3&id="+id+"&act=print_catalogo",
						success: function(resposta){
							retorno.produto = id;
							$('#btn_voltar_ajax').html("<a href='javascript:void(0);' onclick='catalogoJacto(\"catalogo_2\","+retorno.catalogo+");'><?php echo $voltar;?></a>");
							$('#conteudo').html(resposta);
							$('.carregador').css('display','none');
						}
					});
				}

				if(pagina == 'ver_peca'){
					$('.carregador').css('display','block');
					$.ajax({
						url: '<?php echo "catalogo_jacto.php?modulo=$modulo";?>',
						type: "POST",
						data: "catalogo=4&id="+id+"&act=print_catalogo",
						success: function(resposta){
							retorno.peca = id;
							$('#btn_voltar_ajax').html("<a href='javascript:void(0);' onclick='catalogoJacto(\"catalogo_2\","+retorno.catalogo+");'><?php echo $voltar;?></a>");
							$('.carregador').css('display','none');
							$('#conteudo').html(resposta);
						}
					});
				}

				if(pagina == 'ver_peca_2'){
					$('.carregador').css('display','block');
					$.ajax({
						url: '<?php echo "catalogo_jacto.php?modulo=$modulo";?>',
						type: "POST",
						data: "catalogo=5&id="+id+"&act=print_catalogo",
						success: function(resposta){
							retorno.peca = id;
							$('#btn_voltar_ajax').html("<a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\","+retorno.peca+");'><?php echo $voltar;?></a>");
							$('.carregador').css('display','none');
							$('#conteudo').html(resposta);
						}
					});
				}
				
				if(pagina == 'ver_peca_3'){
					$('.carregador').css('display','block');
					$.ajax({
						url: '<?php echo "catalogo_jacto.php?modulo=$modulo";?>',
						type: "POST",
						data: "catalogo=6&id="+id+"&act=print_catalogo",
						success: function(resposta){
						$('#btn_voltar_ajax').html("<a href='javascript:void(0);' onclick='catalogoJacto(\"ver_peca\","+retorno.peca+");'><?php echo $voltar;?></a>");
							$('.carregador').css('display','none');
							$('#conteudo').html(resposta);
						}
					});
				}

				if(pagina == 'ver_peca_3_alternativa'){
					$('.carregador').css('display','block');
					$.ajax({
						url: '<?php echo "catalogo_jacto.php?modulo=$modulo";?>',
						type: "POST",
						data: "catalogo=6&id="+id+"&act=print_catalogo",
						success: function(resposta){
						$('#btn_voltar_ajax').html("<a href='javascript:void(0);' onclick='catalogoJacto(\"catalogo_2\","+retorno.catalogo+");'><?php echo $voltar;?></a>");
							$('.carregador').css('display','none');
							$('#conteudo').html(resposta);
						}
					});
				}

				if(pagina == 'gravar_itens'){
					$('.carregador').css('display','block');
					$.ajax({
						url: '<?php echo "catalogo_jacto.php?modulo=$modulo";?>',
						type: "POST",
						data: "catalogo=gravar_itens&id="+id+"&act=print_catalogo",
						success: function(resposta){
							$('#conteudo').html(resposta);
							$('.carregador').css('display','none');
						}
					});
				}

			};
			
			function mostraFilho(id){
				//alert(id);
				//$('.'+id).css('display','block');
				if($(".img_"+id).attr( "src" )== "imagens/mais.bmp"){
					$(".img_"+id).attr("src", "imagens/menos.bmp");
					$('.'+id).css('display','block');
				}else{
					$(".img_"+id).attr("src", "imagens/mais.bmp");
					$('.'+id).css('display','none');
				}
			}

			function idioma_catalogo(idioma){
				Cookie('idioma_jacto_catalogo',idioma,14);
				window.location = '<?php echo "catalogo_jacto.php?modulo=$modulo";?>';
			}

			function mostraSeries(class_value){
				$("."+class_value).fadeToggle();
			}
		</script>
	</head>

	<body>
		<div class='carregador'><img src='imagens/ajax-loader_2.gif' title='Carregador' style='margin: 35% auto;' /></div>
		<div id='geral'>
			<div id='menu'>
				<div class='img_paises'>
					<img src='imagens/bandeira_paises/Brazil-Flag-24.png'		 alt='Portugues Brasil' onclick="idioma_catalogo('Portugues(BRA)');" />
					<img src='imagens/bandeira_paises/United-States-Flag-24.png' alt='English'			onclick="idioma_catalogo('ENGLISH');" />
					<img src='imagens/bandeira_paises/Spain-Flag-24.png'		 alt='Español'			onclick="idioma_catalogo('ESP');" />
				</div>
				<div id='btn_menu'><a href='<?php echo "catalogo_jacto.php?modulo=$modulo";?>' ><?php echo $menu?></a></div>
				<div id='btn_voltar_ajax'>&nbsp;</div>
				
				<div id='btn_sair'>
					<?php
						if(strlen($modulo) == 0)
							echo "<a href='javascript: void(0);' onclick='window.close();'>";
						else
							echo "<a href='javascript: void(0);' onclick='window.parent.Shadowbox.close();'>";
						echo $fechar?></a>
					</div>

			</div>

			<div id='conteudo'>
				<h2><?php echo $$catalogo_peca?></h2><br>
				<?php
					if(strlen($catalogo) == 0){
				?>
					<div>
						<ul class='ul_catalogo_1'>
							<?php
							$sql = "SELECT 
										grupo_catalogo	,
										codigo_grupo	,
										nome			,
										desenho			
									FROM 
										tbl_jacto_grupo_catalogo 
									WHERE 
										idioma = '$lng'
										AND fabrica = $login_fabrica
									ORDER BY nome ASC
									";
								//echo utf8_encode($sql);
								$res = pg_query ($con, $sql);
								
								if(pg_num_rows( $res ) > 0 ){

									for($i = 0; $i < pg_num_rows( $res );){
										$grupo_catalogo = pg_fetch_result($res, $i, grupo_catalogo);
										$codigo_grupo   = pg_fetch_result($res, $i, codigo_grupo);
										$nome           = pg_fetch_result($res, $i, nome);
										$desenho        = pg_fetch_result($res, $i, desenho);
									
										echo "<li>";
											echo "<a href='javascript:void(0);' onclick='catalogoJacto(\"catalogo_1\",$grupo_catalogo);'>";
												echo "<div class='titulo'><p>$nome</p></div>";
												echo "<div class='imagem'><img src='jacto/imagens/$desenho.JPG' /></div>";
											echo "</a>";
										echo "</li>";
										$i++;
									}
								}
							?>
						</ul>
						<div style='clear: both'>&nbsp;</div>
					</div>
				<?php
					}
				?>
					<!-- <?=$sql?> -->
				<div class='msg_rodape'><?php echo $mensagem_rodape;?></div>
			</div>
		</div>
	</body>
</html>
<?php @pg_close($con);?>
