<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';
require_once 'autentica_admin.php';

include 'funcoes.php';
include_once 'helper.php';

if(isset($_POST["get_pecas_sem_preco"])){


	$pag = $_POST["pag"];

	$limit = $pag * 1000;

	$lista_basica = ($login_fabrica == 72) ? " JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca join tbl_produto on tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.ativo " : "";

	$tabela_garantia = 'AND (tabela_garantia IS TRUE or tabela_garantia IS NOT TRUE)';

	if ($login_fabrica == 87) {
		$tabela_garantia = '';
	}

	$sql = "SELECT DISTINCT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
					FROM tbl_peca
					$lista_basica ";
	$sql .= " LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
			  AND tbl_tabela_item.tabela in(SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica $tabela_garantia)";
	if($login_fabrica == 2)
		$sql .= "AND tbl_tabela_item.tabela = 2";
	
	$sql .= "   WHERE tbl_peca.fabrica = $login_fabrica
				AND   tbl_peca.produto_acabado IS NOT TRUE
				AND   tbl_peca.ativo           IS TRUE
				AND   tbl_tabela_item.preco    IS NULL 
				LIMIT $limit OFFSET 1000";

	$res = pg_query($con, $sql);

	$pecas_sem_preco = array();

	if(pg_num_rows($res) > 0){

		for ($i = 0; $i < pg_num_rows($res); $i++) { 
			
			$pecas_sem_preco["pecas"][] = array(
					"peca"       => pg_fetch_result($res, $i, "peca"),
					"referencia" => pg_fetch_result($res, $i, "referencia"),
					"descricao"  => utf8_encode(pg_fetch_result($res, $i, "descricao"))
				);

		}

		$pecas_sem_preco["tem_pecas"] = (pg_num_rows($res) < 1000) ? false : true;

	}

	exit(json_encode($pecas_sem_preco));


}

$helper->login;

$title = "Cadastros do Sistema";
$layout_menu = "cadastro";
include 'cabecalho_new.php';

include 'jquery-ui.html';

?>
<style type="text/css">
table.tabela {margin-bottom: 1em;font-family: Verdana, Arial, helvetica, sans-serif}
table.tabela>caption.titulo_coluna {
	background: #596d9b  url(imagens/icon_collapse_white.png) no-repeat 16px 2px;
	border-radius: 5px 5px 0 0;
	cursor: pointer;
	padding:2px;
	transition: background-color 0.4s;
	-o-transition: background-color 0.4s;
	-ms-transition: background-color 0.4s;
	-moz-transition: background-color 0.4s;
	-webkit-transition: background-color 0.4s;
}
table.tabela tr td{
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
	background-color:#596d9b;
	font-size: 11px;
	font-weight: bold;
	color:#FFFFFF;
	text-align:center;
}

/* muda cor topo peça sem preço */
table.tabela.oculta.ocultar > thead,
table.tabela.oculta.ocultar > tbody {display:none}
table.tabela.oculta caption {
	border-radius: 5px;
	background: red url(imagens/icon_expand_white.png) no-repeat 16px 2px;
	background-color: red;
	color: white;
}
</style>

<script type="text/javascript">
	var pag = 1;
	$().ready(function() {
		$("tr[id^=ln_]").click(function(){
			var peca = $(this).attr("rel");
			if( $("#ln_os_"+peca).is(":visible") ){
				$("#ln_os_"+peca).hide();
			}else{
				$("#ln_os_"+peca).show();
			}
		});
		$(".titulo_coluna").click(function(){
			if($(".box-carregar").is(":visible")){
				$(".box-carregar").hide();
			}else{
				$(".box-carregar").show();
			}
		});

		$(".btn-carregar").click(function(){
			
			$.ajax({
				url : "<?php echo $_SERVER['PHP_SELF'] ?>",
				type : "post",
				data : {
					pag : pag,
					get_pecas_sem_preco : true
				},
				beforeSend: function(){
					$(".before-msg").html("<em>carregando, por favor aguarde...</em>");
				}
			}).always(function(data){

				data = JSON.parse(data);

				$(".before-msg").html("");

				$.each(data.pecas, function(key, value){
					console.log(value);
					$(".lista-peca-sem-preco").append("<tr> \
							<td align='center'><a href='preco_cadastro.php?peca="+value.peca+"' target='_blank'>"+value.referencia+"</td> \
							<td>"+value.descricao+"</td> \
						</tr>");
				});

				if(data.tem_pecas == true){
					pag += 1;
				}else{
					$(".box-carregar").hide();
				}

			});

		});
	});
</script>
<?

//VETORES QUE ADICIONAM FUNCIONALIDADES - HD 383687

$vet_peca_sem_preco                 = array(2,5,7,8,11,35,40,43,52,74,80,86,72,85); //Ao alterar esse ARRAY, também deverá alterar a rotina 'rotinas/rotina-os-peca-sem-preco.php'
$vet_produto_sem_mo                 = array(3,6,86);
$vet_produto_sem_preco              = array(51,86);
$vet_produto_sem_familia            = array(3,6,7,14,43,85,86);
$vet_produto_sem_linha              = array(43,86);

$vet_produto_sem_capacidade_divisao = array(7);

$vet_fabrica_multi_marca            = array(3, 10, 30, 52, 101, 104, 105);

//hd 19043 - Selecionei as fábricas que usam tbl_subproduto e coloquei no array. Ébano
$usam_subproduto = array(43, 8, 3, 14, 46, 17, 66, 4, 10, 2, 5);
$usam_kit_pecas  = array(15,24,91);
$fabrica_pecas_represadas = array_merge($fabricas_contrato_lite, array(6,24,50,86));

// Fábricas que não deixam digitar o defeito reclamado,
// têm que cadastrar e manter a lista de defeitos reclamados
$sql = "SELECT pedir_defeito_reclamado_descricao
		  FROM tbl_fabrica
		 WHERE fabrica = $login_fabrica
		   AND (pedir_defeito_reclamado_descricao IS NULL
			OR pedir_defeito_reclamado_descricao  IS FALSE);";
$res = @pg_exec($con,$sql);


$fabrica_seleciona_defeito_reclamado = (@pg_numrows($res) > 0 or in_array($login_fabrica, array(42, 74, 81, 86,  96, 114,115,116)));

// Fábricas que fazem relacionamento de integridade de defeito constatado com família
$fabrica_integridade_familia_constatado = array(42,74,81,86,94,95,96,98,99,104,105,108,101,111,114,115,116);

// Fábricas que fazem relacionamento de integridade de defeito reclamado com família
$fabrica_integridade_familia_reclamado  = array(52,98,99,104,105,108,101,111);

$fabrica_integridade_reclamado_constatado = array(1, 2, 5, 8, 10, 14, 16, 20);
$fabrica_nao_cadastra_solucao_defeito     = array_merge($fabricas_contrato_lite, array(2,14,19,20,86,94,106));

// Clientes que usam a nova tela de Relacionamento de Integridade

$fabrica_usa_rel_diag_new = in_array($login_fabrica, array(2, 7, 15, 25, 28, 30, 35, 40, 42, 43, 45, 46, 47,96,85, 115, 116)) or($login_fabrica > 49 and !in_array($login_fabrica, array(59, 66)));
$fabrica_integridade_peca = array(5,15,24,50);

// Habilita o Laudo Técnico (cadastro de questionário)
$fabrica_pede_laudo_tecnico = array(1, 19, 43, 46, 56, 57, 58);

// Cadastro de clientes Admin
$fabrica_tem_clientes_admin = array(30, 52, 85, 96);
$fabrica_pedido_loja_virtual = array(3, 10, 35);

/* INFORMAÇÕES DE PROBLEMAS COM O CADASTRO DE PEÇAS E PRODUTOS */
// Configuração padrão das tabelas de dados
$tblAttrs = array(
	// adicionar na tableAttrs a class 'oculta' para iniciar as tabelas colapsadas
	'tableAttrs'   => ' align="center" width="850" cellspacing="1" class="tabela"', 
	'captionAttrs' => ' class="titulo_coluna"',
	'headerAttrs'  => ' class="titulo_coluna"',
	'rowAttrs'     => ' align="left"',
);
#----------------- Produtos sem Linha ---------------
if (in_array($login_fabrica, $vet_produto_sem_linha)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica = $login_fabrica";
	if ($login_fabrica == 43) {
		$sql .= " AND tbl_produto.linha = '466'";
	} else {
		$sql .= " AND tbl_produto.linha IS NULL";
	}
	$sql .= " AND tbl_produto.ativo IS TRUE ";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_linha = pg_fetch_all($res);

		foreach($prod_sem_linha as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psl');

			$tbl_sem_linha[] = array(
				'Referência' => "<a href='produto_cadastro.php?produto=$psl_produto'>$psl_referencia</a>",
				'Descrição'  => $psl_descricao
			);
		}
		$tbl_sem_linha['attrs'] = $tblAttrs; // Copia a config.

		echo array2table($tbl_sem_linha, 'Produtos sem Linha');

	}
}

#----------------- Produtos sem Capacidade Divisão---------------
if (in_array($login_fabrica, $vet_produto_sem_capacidade_divisao)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica = $login_fabrica
			 AND tbl_produto.ativo IS TRUE
			 AND (tbl_produto.divisao isnull or tbl_produto.capacidade isnull) ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_linha = pg_fetch_all($res);

		foreach($prod_sem_linha as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'pscd');

			$tbl_sem_linha[] = array(
				'Referência' => "<a href='produto_cadastro.php?produto=$pscd_produto'>$pscd_referencia</a>",
				'Descrição'  => $pscd_descricao
			);
		}
		$tbl_sem_linha['attrs'] = $tblAttrs; // Copia a config.

		echo array2table($tbl_sem_linha, 'Produtos sem Capacidade ou Divisão');

	}
}

#----------------- Produtos sem Familia ---------------
if (in_array($login_fabrica, $vet_produto_sem_familia)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica   = $login_fabrica
			   AND tbl_produto.familia IS NULL
			   AND tbl_produto.ativo   IS TRUE ";

	if ($login_fabrica == 14) {
		$sql .= " AND tbl_produto.abre_os IS TRUE AND SUBSTR(tbl_produto.referencia,1,1) = '4'";
	}

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_familia = pg_fetch_all($res);

		foreach($prod_sem_familia as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psf');

			$tbl_sem_familia[] = array(
				'Referência' => "<a href='produto_cadastro.php?produto=$psf_produto'>$psf_referencia</a>",
				'Descrição'  => $psf_descricao
			);
		}
		$tbl_sem_familia['attrs'] = $tblAttrs; // Copia a config.
		echo array2table($tbl_sem_familia, 'Produtos sem Família');
	}
}

#----------------- Produtos sem Mão-de-Obra ---------------
if (in_array($login_fabrica, $vet_produto_sem_mo)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica = $login_fabrica
			   AND (tbl_produto.mao_de_obra IS NULL OR tbl_produto.mao_de_obra = 0)
			   AND tbl_produto.ativo IS NOT FALSE  ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_mo = pg_fetch_all($res);

		foreach($prod_sem_mo as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psm');

			$tbl_sem_mo[] = array(
				'Referência' => "<a href='produto_cadastro.php?produto=$psm_produto'>$psm_referencia</a>",
				'Descrição'  => $psm_descricao
			);
		}
		$tbl_sem_mo['attrs'] = $tblAttrs; // Copia a config.
		echo array2table($tbl_sem_mo, 'Produtos sem Mão-de-Obra');
	}
}

#------------ Produtos sem preço ------------
if (in_array($login_fabrica, $vet_produto_sem_preco)) {

	$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
				FROM tbl_peca
				LEFT JOIN tbl_tabela_item USING(peca)
				WHERE tbl_peca.fabrica = $login_fabrica
				AND   tbl_peca.referencia in(SELECT referencia FROM tbl_produto JOIN tbl_linha USING(linha) WHERE fabrica = $login_fabrica)
				AND   tbl_peca.produto_acabado = 'f'
				AND   tbl_tabela_item.preco IS NULL";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$prod_sem_preco = pg_fetch_all($res);

		foreach($prod_sem_preco as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'psp');

			$tbl_sem_preco[] = array(
				'Referência' => "<a href='preco_cadastro.php?peca=$psp_produto'>$psp_referencia</a>",
				'Descrição'  => $psp_descricao
			);
		}
		$tbl_sem_preco['attrs'] = $tblAttrs; // Copia a config.
		echo array2table($tbl_sem_preco, 'Produtos sem Preço');
	}
}

#------------ Peças sem preço ------------
if (in_array($login_fabrica, $vet_peca_sem_preco) or $login_fabrica >= 86) {
	if ($login_fabrica == 120) {
		$sql_n = "SELECT tabela, descricao 
					FROM tbl_tabela 
					WHERE fabrica = $login_fabrica 
					AND (tabela_garantia IS TRUE OR tabela_garantia IS NOT TRUE)";
		$res_s = pg_query($con, $sql_n);
		$legenda = true;

		if (pg_num_rows($res_s)> 0) {
			for ($i=0; $i < pg_num_rows($res_s) ; $i++) { 
				$tabela_s 	 = pg_fetch_result($res_s, $i, tabela);
				$tabela_desc = pg_fetch_result($res_s, $i, descricao);

				$sql_n_t = "SELECT DISTINCT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
								FROM tbl_peca
								LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
								AND tbl_tabela_item.tabela in($tabela_s) WHERE tbl_peca.fabrica = $login_fabrica 
								AND tbl_peca.produto_acabado IS NOT TRUE
								AND tbl_peca.ativo IS TRUE
								AND tbl_tabela_item.preco IS NULL";
				$res_n_t = pg_query($con,$sql_n_t);
				if (pg_num_rows($res_n_t)> 0) {
					$peca_sem_preco = pg_fetch_all($res_n_t);
					if ($legenda == true) {
						echo "<table width='100%' cellspacing='1' align='center'>";
						echo "<tr>
								<td width='15px'><div style='padding: 6px; border: 1px solid #eed3d7; background: #f2dede; border-radius: 2px; '></div></td>
								<td align='left' style='font-size:12px;'> &nbsp; Peças sem preço e que foram lançadas em alguma Ordem de Serviço</td>
							  </tr>";
						echo "</table>";
						$legenda = false;						
					}
					

					echo "<table class='tabela oculta ocultar' width='100%' cellspacing='0' cellpadding='5' align='center'>";
					echo "<caption class='titulo_coluna' style='font-size: 14px; padding-top: 5px;'>Peças sem Preço na Tabela: $tabela_desc </caption>";
					echo "<thead style='display:none;'>
							<tr class='titulo_coluna' style='border: 1px solid #596d9b;'>
								<th>Referência</th>
								<th>Descrição</th>
							</tr>
						  </thead><tbody class='ocultar' style='display:none'>";

					foreach($peca_sem_preco as $rec) {
						extract($rec, EXTR_PREFIX_ALL, 'psp');
						
						$sql2 = "SELECT os,sua_os 
								 FROM tmp_os_peca_sem_preco 
								 WHERE fabrica = $login_fabrica 
								 AND peca = $psp_peca";
						$res2 = pg_query($con,$sql2);
						$total_os_peca_sem_preco = pg_num_rows($res2);
						$bgcolor = ($total_os_peca_sem_preco > 0) ? "#f2dede" : "";
						$id = ($total_os_peca_sem_preco > 0) ? "id='ln_{$psp_peca}' rel='{$psp_peca}' style='cursor:pointer;'" : "";

						echo "<tr bgcolor='{$bgcolor}' $id>
								<td align='center'><a href='preco_cadastro.php?peca={$psp_peca}' target='_blank'>{$psp_referencia}</a></td>
								<td align='left'>{$psp_descricao}</td>
							  </tr>";

						if($total_os_peca_sem_preco > 0){
							echo "<tr style='display:none' id='ln_os_{$psp_peca}'>";
									echo "<td colspan='2'>
											<table width='100%'>";
								for($x = 0; $x < $total_os_peca_sem_preco; $x++){
									$os 	= pg_fetch_result($res2, $x, 'os');
									$sua_os = pg_fetch_result($res2, $x, 'sua_os');

											echo"<tr><td align='left'>
													<a href='os_press.php?os={$os}' target='_blank'>{$sua_os}</a>
												</td></tr>";
								}
							echo "</table></td></tr>";
						}
					}
					echo "</tbody></table>";					
				}
			}
		}
		
	}else{

		$lista_basica = ($login_fabrica == 72) ? " JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca join tbl_produto on tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.ativo " : "";

		$tabela_garantia = 'AND (tabela_garantia IS TRUE or tabela_garantia IS NOT TRUE)';

		if ($login_fabrica == 87) {
			$tabela_garantia = '';
		}

		$btn_hide = true;

		$sql_count = "SELECT DISTINCT COUNT(tbl_peca.peca) AS qtde_pecas  
					FROM tbl_peca
					$lista_basica ";
		$sql_count .= " LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
				  AND tbl_tabela_item.tabela not in(SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica $tabela_garantia)";
		if($login_fabrica == 2)
			$sql_count .= "AND tbl_tabela_item.tabela = 2";
		
		$sql_count .= "   WHERE tbl_peca.fabrica = $login_fabrica
					AND   tbl_peca.produto_acabado IS NOT TRUE
					AND   tbl_peca.ativo           IS TRUE
					/* AND   tbl_tabela_item.preco    IS NULL */";

		$res_count = pg_query($con, $sql_count);
		if(pg_num_rows($res_count) > 0){
			$qtde_pecas = pg_fetch_result($res_count, 0, "qtde_pecas");
			if($qtde_pecas > 1000){
				$btn_hide = false;
			}
		}

		$sql = "SELECT DISTINCT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
					FROM tbl_peca
					$lista_basica ";
		$sql .= " LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
				  AND tbl_tabela_item.tabela not in(SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica $tabela_garantia)";
		if($login_fabrica == 2)
			$sql .= "AND tbl_tabela_item.tabela = 2";
		
		$sql .= "   WHERE tbl_peca.fabrica = $login_fabrica
					AND   tbl_peca.produto_acabado IS NOT TRUE
					AND   tbl_peca.ativo           IS TRUE
					/* AND   tbl_tabela_item.preco    IS NULL */ 
					LIMIT 1000";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$peca_sem_preco = pg_fetch_all($res);
			echo "<table width='100%' cellspacing='1' align='center'>";
			echo "<tr>
					<td width='15px'><div style='padding: 6px; border: 1px solid #eed3d7; background: #f2dede; border-radius: 2px; '></div></td>
					<td align='left' style='font-size:12px;'> &nbsp; Peças sem preço e que foram lançadas em alguma Ordem de Serviço</td>
				  </tr>";
			echo "</table>";

			echo "<table class='tabela oculta ocultar' width='100%' cellspacing='0' cellpadding='5' align='center'>";
			echo "<caption class='titulo_coluna' style='font-size: 14px; padding-top: 5px;'>Peças sem Preço</caption>";
			echo "<thead style='display:none;'>
					<tr class='titulo_coluna' style='border: 1px solid #596d9b;'>
						<th width='10%'>Referência</th>
						<th width='30%'>Descrição</th>
						<th width='30%'>OSs em Garantia com Peças sem Preço</th>
						<th width='30%'>Tabela onde a Peça está sem Preço</th>
					</tr>
				</thead>
				  	<tbody class='ocultar lista-peca-sem-preco' style='display:none'>";
				  	$box = 0;
			foreach($peca_sem_preco as $rec) {
				extract($rec, EXTR_PREFIX_ALL, 'psp');

				/**
				 * @description HD 754908 Jacto - Não mostrar peças que tem de para, e o para contenha preço.
				 * @author Brayan
				 **/
				if ( $login_fabrica == 87 ) {

					$sql2 = "SELECT tbl_peca.peca
							 FROM tbl_depara
							 JOIN tbl_peca ON tbl_depara.peca_para = tbl_peca.peca AND tbl_peca.fabrica = tbl_depara.fabrica
							 WHERE tbl_peca.fabrica 		= $login_fabrica
							 AND   tbl_depara.peca_de 		= $psp_peca
							 AND   tbl_peca.produto_acabado IS NOT TRUE
							 AND   tbl_peca.ativo           IS TRUE";

					$res2 = pg_query($con,$sql2);

					if ( pg_num_rows($res2) > 0 ) {
						continue;
					}

				}

				$links_os = array();

				$sql2 = "SELECT os,sua_os 
						 FROM tmp_os_peca_sem_preco 
						 WHERE fabrica = $login_fabrica 
						 AND peca = $psp_peca";
				$res2 = pg_query($con,$sql2);
				$total_os_peca_sem_preco = pg_num_rows($res2);
				$bgcolor = ($total_os_peca_sem_preco > 0) ? "#f2dede" : "";
				$id = ($total_os_peca_sem_preco > 0) ? "id='ln_{$psp_peca}' rel='{$psp_peca}' style='cursor:pointer;'" : "";

				$desc_tabelas = array();

			  	$sql_tabela = "SELECT 
				  				sigla_tabela, descricao 
				  			FROM tbl_tabela 
				  			WHERE fabrica = {$login_fabrica} 
				  			AND tabela NOT IN (SELECT tabela FROM tbl_tabela_item WHERE peca = {$psp_peca})";
			  	$res_tabela = pg_query($con, $sql_tabela);

			  	if(pg_num_rows($res_tabela) > 0){

					echo "<tr bgcolor='{$bgcolor}' $id>
						<td align='center'><a href='preco_cadastro.php?peca={$psp_peca}' target='_blank'>{$psp_referencia}</a></td>
						<td align='left'>{$psp_descricao}</td>
						<td class='tac'>";

						if($total_os_peca_sem_preco > 0){
							for($x = 0; $x < $total_os_peca_sem_preco; $x++){
								$os 	= pg_fetch_result($res2, $x, 'os');
								$sua_os = pg_fetch_result($res2, $x, 'sua_os');

								$links_os[] = "<a href='os_press.php?os={$os}' target='_blank'>{$sua_os}</a>";
							}
							echo implode(", ", $links_os);
						}else{
							echo "Nenhuma OS localizada";
						}

					  echo "</td>";

					  echo "<td>";


				  		for($t = 0; $t < pg_num_rows($res_tabela); $t++){

				  			$desc_tabelas[] = pg_fetch_result($res_tabela, $t, "sigla_tabela")." - ".pg_fetch_result($res_tabela, $t, "descricao");

				  		}

				  		$desc_tabelas = implode("<br />", $desc_tabelas);

				  		$box++;

				  		?>
				  		<div class="tac">
				  			<button type="button" class="btn btn-success" id="exibir_<?=$box?>" style="width: 80px;" onclick="toggle_box('<?php echo $box; ?>')"><strong>Exibir</strong></button>
				  			<button type="button" class="btn btn-danger" id="ocultar_<?=$box?>" style="width: 80px; display: none;" onclick="toggle_box('<?php echo $box; ?>')"><strong>Ocultar</strong></button>
				  		</div>
				  		<div id="desc_tabelas_<?=$box?>" class="tac" style="display: none;">
				  			<br />
				  			<?php echo $desc_tabelas; ?>
				  		</div>
					  	<?php

					  echo "</td>";

					echo "</tr>";

				}

			}
			echo "</tbody></table>";

			if($btn_hide == false){

				echo "
				<div class='tac box-carregar' style='display: none;'>
					<button type='button' class='btn btn-warning btn-carregar'>Carregar mais Peças sem Preço</button>
					<div class='before-msg tac'></div>
				</div> <br />";

			}

		}
			
			if($login_fabrica == 151 ) {

			
			echo "<table class='tabela oculta ocultar' width='100%' cellspacing='0' cellpadding='5' align='center'>";
			echo "<caption class='titulo_coluna' style='font-size: 14px; padding-top: 5px;'>Produtos Novos</caption>";
			echo "<thead style='display:none;'>
					<tr class='titulo_coluna' style='border: 1px solid #596d9b;'>
						<th width='10%'>Referência</th>
						<th width='30%'>Descrição</th>
					</tr>
				</thead>
				  	<tbody class='ocultar lista-produto-novo' style='display:none'>";
				
			$sql = "SELECT 	produto,
					descricao,
					referencia
					FROM tbl_produto 
					WHERE fabrica_i = $login_fabrica AND tbl_produto.admin is null";

			$res = pg_query($sql);


			for ($i=0;$i<pg_num_rows($res);$i++) {

				$produto    = pg_result($res,$i,produto);
				$referencia = pg_result($res,$i,referencia);
				$descricao  = pg_result($res,$i,descricao);

				echo "<tr>";
				echo "<td align='center'><a href='produto_cadastro.php?produto={$produto}' target='_blank'>{$referencia}</a></td>";
				echo "<td>$descricao</td>";
				echo "</tr>";

			}

			echo "

			</tbody></table>";


			echo "<table class='tabela oculta ocultar' width='100%' cellspacing='0' cellpadding='5' align='center'>";
			echo "<caption class='titulo_coluna' style='font-size: 14px; padding-top: 5px;'>Peças Novas</caption>";
			echo "<thead style='display:none;'>
					<tr class='titulo_coluna' style='border: 1px solid #596d9b;'>
						<th width='10%'>Referência</th>
						<th width='30%'>Descrição</th>
					</tr>
				</thead>
				  	<tbody class='ocultar lista-produto-novo' style='display:none'>";
				
			$sql = "SELECT 	peca,
					descricao,
					referencia
					FROM tbl_peca 
					WHERE fabrica = $login_fabrica AND tbl_peca.admin is null";

			$res = pg_query($sql);


			for ($i=0;$i<pg_num_rows($res);$i++) {

				$peca    = pg_result($res,$i,peca);
				$referencia = pg_result($res,$i,referencia);
				$descricao  = pg_result($res,$i,descricao);

				echo "<tr>";
				echo "<td align='center'><a href='peca_cadastro.php?peca={$peca}' target='_blank'>{$referencia}</a></td>";
				echo "<td>$descricao</td>";
				echo "</tr>";

			}

			echo "

			</tbody></table>";

			}

		?>

		<script type="text/javascript">

		function toggle_box(box){
			if($("#desc_tabelas_"+box).is(":visible")){
				$("#exibir_"+box).show();
				$("#ocultar_"+box).hide();
				$("#desc_tabelas_"+box).hide(700);
			}else{
				$("#exibir_"+box).hide();
				$("#ocultar_"+box).show();
				$("#desc_tabelas_"+box).show(700);
			}
		}

		</script>

		<?php

	}
}

#------------------Posto EM DESCREDENCIAMENTO-----------------
if($login_fabrica == 3){		
		
	$sqlDesc = "SELECT  tbl_posto.posto,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto
					FROM tbl_posto_fabrica 
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					JOIN tbl_credenciamento ON tbl_posto_fabrica.posto = tbl_credenciamento.posto 
					AND tbl_credenciamento.fabrica = $login_fabrica 
					AND tbl_credenciamento.dias notnull
					AND tbl_credenciamento.dias <= 5
					AND tbl_credenciamento.credenciamento = (SELECT MAX(credenciamento) FROM tbl_credenciamento C WHERE C.posto = tbl_posto.posto AND C.fabrica = $login_fabrica)
					WHERE tbl_posto_fabrica.fabrica  = $login_fabrica  
					AND tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO'";
	$resDesc = pg_query($con,$sqlDesc);
	if(pg_num_rows($resDesc) > 0){
		$posto_em_descredenciamento = pg_fetch_all($resDesc);

		foreach($posto_em_descredenciamento as $rec) {
			extract($rec, EXTR_PREFIX_ALL, 'ped');

			$tbl_em_descredenciamento[] = array(
				'Código Posto' => "<a href='credenciamento.php?codigo=$ped_codigo_posto&posto=$ped_posto&listar=3'>$ped_codigo_posto</a>",
				'Descrição'  => $ped_nome
			);
		}
		$tbl_em_descredenciamento['attrs'] = $tblAttrs; // Copia a config.
		echo array2table($tbl_em_descredenciamento, 'Posto Em Descredenciamento');
	}			
}

menuTCAdmin($menuP = include('menus/menu_cadastro_produto.php'));

if ($login_fabrica == 10 OR $login_fabrica == 35)
	echo "<center><a href='loja_virtual_adm.php'> Administrador da Loja Virtual</a></center>\n<br />\n";

//include_once 'helper.php';

menuTCAdmin($menuC = include('menus/menu_cadastro.php'));

// Monta o menu CADASTRO (Parte II)
if ($_GET['debug'] == 'array') {
	$menu = array_merge($menuP, $menuC);
	foreach($menu as $secao) {
		$total += count($secao) - 2; //'secao' e 'linha_de_separação'
	}
	echo "Total das " . count($menu) . " seções: <b>$total</b> ítens.<br />";
	if (isCLI)
		pre_echo(var_export($menu, true), 'Menu Cadastro completo');
}

include 'rodape.php';
include '../google_analytics.php';

