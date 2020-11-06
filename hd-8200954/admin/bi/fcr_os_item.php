<?
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'../');
define('OS_BACK', ($areaAdminCliente == true)?'':'../');

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';

if ($areaAdminCliente == true) {
	include 'autentica_admin.php';
	include_once '../funcoes.php';
} else {
	$admin_privilegios = "gerencia";
	include_once '../includes/funcoes.php';
	include '../autentica_admin.php';
	include '../monitora.php';
}

if($login_fabrica == 134){
    $tema = "Serviço Realizado";
}else{
    $tema = "Defeito Constatado";
}

if(isset($produto))$listar="ok";
$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

$qtde_meses_leadrship = 15;
//include "cabecalho.php";

?>

    <link href="<?=ADMCLI_BACK?>bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="<?=ADMCLI_BACK?>bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="<?=ADMCLI_BACK?>css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="<?=ADMCLI_BACK?>css/tooltips.css" type="text/css" rel="stylesheet" />
    <link href="<?=ADMCLI_BACK?>plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
    <link href="<?=ADMCLI_BACK?>bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

    <!--[if lt IE 10]>
     <link href="<?=ADMCLI_BACK?>plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
    <link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
    <![endif]-->

    <script src="<?=ADMCLI_BACK?>plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="<?=ADMCLI_BACK?>plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="<?=ADMCLI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script src="<?=ADMCLI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script src="<?=ADMCLI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script src="<?=ADMCLI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>

<?

echo "<br /> <div class='container'>";

	if($login_fabrica == 24){
		$matriz_filial = $_GET['matriz_filial'];

		$cond_join_bi_os = "join bi_os on BI.os = bi_os.os AND bi_os.fabrica = $login_fabrica";

		if(strlen($matriz_filial) > 0 AND $matriz_filial != 'null'){
			$cond_matriz_filial = " AND substr(BI.serie,length(BI.serie) - 1, 2) = '$matriz_filial' "; 
			$cond_matriz_filial_bi_os = " AND substr(bi_os.serie,length(bi_os.serie) - 1, 2) = '$matriz_filial' "; 
		}
	}

	if ($_GET['origem_tipo']=='defeito') {
		  
		$dc_descricao = $_GET['dr_descricao'];

		$sql = "SELECT * FROM temp_bi_os_sem_peca2_$login_fabrica where dc_descricao = '$dc_descricao' order by posto_nome";
		$res = pg_exec($con,$sql);

 		 echo "<br><a href='javascript:history.back()'>[Voltar]</a>";
		 echo "<TABLE name='relatorio' id='relatorio' align='center' class='table table-striped table-bordered table-hover'>";
		 echo "<thead>";
		 echo "<tr class='titulo_coluna'>";
		 echo "<th><b>OS</b></th>";
		 echo "<th><b>Cód. Posto</b></th>";
		 echo "<th><b>Posto</b></th>";
		 echo "<th><b>Defeito Reclamado</b></th>";
		 echo "<th height='15'><b>$tema</b></th>";
		 echo "</TR>";
		 echo "</thead>";
		 echo "<tbody>";

		  for ($i=0; $i<pg_numrows($res); $i++){
                                $posto_codigo   = trim(pg_result($res,$i,posto_codigo));
                                $posto_nome     = trim(pg_result($res,$i,posto_nome));
                                $dr_codigo      = trim(pg_result($res,$i,dr_codigo));
                                $dr_descricao   = trim(pg_result($res,$i,dr_descricao));
                                $dc_codigo      = trim(pg_result($res,$i,dc_codigo));
                                $dc_descricao   = trim(pg_result($res,$i,dc_descricao));
                                $os             = trim(pg_result($res,$i,os));
                                $sua_os         = trim(pg_result($res,$i,sua_os));


				echo "<TR>";
				echo "<TD align='left' nowrap><a href='".OS_BACK."os_press.php?os=$os' target='_blanck'>$sua_os</a></td>";
				echo "<TD align='left' nowrap>$posto_codigo</TD>";
				echo "<TD align='left' >$posto_nome</TD>";
				echo "<TD align='left'>$dr_codigo - $dr_descricao</TD>";
				echo "<TD align='left'>$dc_codigo - $dc_descricao</TD>";
				echo "</TR>";
		}
			$total_pecas = number_format($total_pecas,2,",",".");
			echo "</tbody>";
			echo " </TABLE>";
			echo "<a href='javascript:history.back()'>[Voltar]</a>";

	}


if ($listar == "ok") {

	if ($login_fabrica == 50) { // HD 41116
		echo "<div id='logo' class='alert-block'><img src='../imagens_admin/colormaq_.gif' width='160' height='55'></div>";
	}

	if (in_array($login_fabrica, array(11))) {
		$join_lenoxx = "Join bi_os ON bi_os.os = BI.os AND bi_os.cortesia is not true";
		$cond_lenoxx = "AND BI.cortesia is not true";
	}else{
		$cond_join = "JOIN bi_os ON bi_os.os = BI.os ";
	}
	
	if($login_fabrica == 158){
		if ($areaAdminCliente == true ) {
			$join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = BI.os JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento 
				AND tbl_tipo_atendimento.fabrica = {$login_fabrica} 
				AND   tbl_os.cliente_admin = {$login_cliente_admin} ";
		}else{
			$join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = BI.os JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento 
				AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";

		}
	}
	if($login_fabrica == 158 AND strlen($_GET['tipo_atendimento']) > 0){


		if($_GET['tipo_atendimento'] == 'fora_garantia'){
			$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS TRUE ";
		}else{
			$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
		}
	}


	/*
		FABRICA 148 => hd_chamado=3049906
		Marisa vai criar o campo cancelada na tabela bi_os
		assim que criado o campo remover o JOIN
	*/
	if(in_array($login_fabrica, array(74,148))){ //hd_chamado=3049906
		$join_cancelada = " JOIN tbl_os ON tbl_os.os = BI.os";
		$cond_cancelada = " AND tbl_os.cancelada IS NOT TRUE ";
	}


	$sql2 = "SELECT referencia,descricao
			FROM tbl_produto
			JOIN tbl_linha  USING(linha)
			WHERE produto = $produto
			AND   fabrica = $login_fabrica";
	$res2 = pg_exec ($con,$sql2);

	$produto_referencia = pg_result($res2,0,0);
	$produto_descricao  = pg_result($res2,0,1);

	if(strlen($peca)>0){
		$sql2 = "SELECT referencia,descricao
				FROM tbl_peca
				WHERE peca    = $peca
				AND   fabrica = $login_fabrica";
		$res2 = pg_exec ($con,$sql2);
		$peca_referencia = pg_result($res2,0,0);
		$peca_descricao  = pg_result($res2,0,1);
	}

	echo "<div class='alert alert-success'>";

	if (strlen($lista_produtos) > 0) {
		$sql2 = "SELECT referencia,descricao
				FROM tbl_produto
				JOIN tbl_linha  USING(linha)
				WHERE produto in ($lista_produtos)
				AND   fabrica = $login_fabrica
				ORDER BY tbl_produto.referencia";
		$res2 = pg_exec ($con,$sql2);
		echo "<h4>Produto:";
		for($i=0;$i<pg_numrows($res2);$i++){
			$produto_referencia = pg_result($res2,$i,0);
			$produto_descricao  = pg_result($res2,$i,1);
			echo " $produto_referencia - $produto_descricao<br>";
		}
		echo "</h4>";
	}else{
		echo "<h4>Produto: $produto_referencia - $produto_descricao</h4>";
	}
	if(strlen($peca)>0)	echo "<h5>Peça: $peca_referencia - $peca_descricao</h5>";
	else                echo "<br />";
	if(strlen($data_inicial)>0)echo "Resultado de pesquisa entre os dias <b>$data_inicial</b> e <b>$data_final</b>";
	echo "$mostraMsgLinha $mostraMsgEstado $mostraMsgPais";
	echo "</div>";

	if(strlen($codigo_posto)>0){
		$sql = "SELECT  posto
				FROM    tbl_posto_fabrica
				WHERE   fabrica      = $login_fabrica
				AND     codigo_posto = '$codigo_posto';";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) $posto = trim(pg_result($res,0,posto));
	}
    if ($login_fabrica == 117) {
            if (strlen ($linha)    > 0){
                    $join_macro_linha = "JOIN tbl_macro_linha_fabrica ON BI.linha = tbl_macro_linha_fabrica.linha AND tbl_macro_linha_fabrica.fabrica = $login_fabrica";
                    $join_macro_linha_item = "JOIN tbl_macro_linha_fabrica ON BI.linha = tbl_macro_linha_fabrica.linha AND tbl_macro_linha_fabrica.fabrica = $login_fabrica";
                    $cond_1 = "AND tbl_macro_linha_fabrica.macro_linha = $linha";
            }
            if (strlen ($macro_familia)    > 0) $cond_1 .= " AND   BI.linha   = $macro_familia ";
    }else{
            if (strlen ($linha)    > 0) $cond_1 = " AND   BI.linha   = $linha ";
    }

	if (strlen ($familia)    > 0) $cond_1 .= " AND   BI.familia   = $familia ";
	if (strlen ($estado)   > 0) $cond_2 = " AND   BI.estado  = '$estado' ";
	if (strlen ($posto)    > 0) $cond_3 = " AND   BI.posto   = $posto ";
	if (strlen ($posto) > 0 AND !empty($exceto_posto)) {
		$cond_3 = " AND   NOT (BI.posto   = $posto) ";
	}
	if (strlen ($produto)  > 0) $cond_4 = " AND   BI.produto = $produto "; // HD 2003 TAKASHI
	if (strlen ($pais)     > 0) $cond_6 = " AND   BI.pais    = '$pais' ";
	if (strlen ($marca)    > 0) $cond_7 = " AND   BI.marca   = $marca ";
	if (strlen ($origem)   > 0) $cond_8 = " AND   BI.origem  = '$origem' ";
	if (strlen ($lista_produtos)> 0) {
		$cond_10 = " AND   BI.produto in ($lista_produtos) ";
		$cond_4  = "";
	}

	if (strlen($tipo_data) == 0 ) $tipo_data = 'data_fechamento';
	if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0 AND $tipo_data!="data_fabricacao"){
		$cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
	}

	if($login_fabrica == 20 and $pais !='BR'){
		$produto_descricao   ="tbl_produto_idioma.descricao ";
		$join_produto_idioma =" LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' ";
	}else{
		$produto_descricao   ="tbl_produto.descricao ";
		$join_produto_idioma =" ";
	}

	if($login_fabrica == 95 AND $tipo_data=="data_fabricacao"){

		#Condições para quando não tem BI_OS na consulta
		$condFacricadoJoin = "
		JOIN bi_os ON (BI.os = bi_os.os)
		JOIN tbl_produto ON (tbl_produto.produto = bi_os.produto)
		JOIN tbl_numero_serie ON (tbl_produto.produto = tbl_numero_serie.produto AND bi_os.serie = tbl_numero_serie.serie) ";

		$cond_11_bi_os = " AND tbl_numero_serie.data_fabricacao BETWEEN '$aux_data_inicial' AND '$aux_data_final' AND bi_os.data_digitacao <= tbl_numero_serie.data_fabricacao + interval '$qtde_meses_leadrship month' ";

		#Condições para quando tem BI_OS na consulta
		$facricadoJoin = "
		JOIN tbl_produto ON (tbl_produto.produto = BI.produto)
		JOIN tbl_numero_serie ON (tbl_produto.produto = tbl_numero_serie.produto AND BI.serie = tbl_numero_serie.serie) ";

		$cond_11 = " AND tbl_numero_serie.data_fabricacao BETWEEN '$aux_data_inicial' AND '$aux_data_final' AND BI.data_digitacao <= tbl_numero_serie.data_fabricacao + interval '$qtde_meses_leadrship month' ";

 }

	if (strlen($_GET['tipo_posto']) > 0) {
		$joinTipoPosto = " LEFT JOIN tbl_tipo_posto USING(tipo_posto) ";
		$condTipoPosto = " AND tbl_tipo_posto.tipo_posto IN(".str_replace("|", ",", $_GET['tipo_posto']).")";
	}

 	if(strlen($peca)>0){
		if ($_GET['tipo_os']=='ajustada') {
			$cond12 = "AND tbl_servico_realizado.troca_de_peca is not true AND tbl_servico_realizado.troca_produto is not true";

		} else if ($_GET['tipo_os']=='trocada') {
			$cond12 = "AND tbl_servico_realizado.troca_de_peca is true";
		} else if ($login_fabrica <> 131){
			$cond12 = "AND tbl_servico_realizado.troca_produto is true";
		}
		//hd-3675052
		if($login_fabrica == 35){
			$joinOS = " JOIN tbl_os ON tbl_os.os = BI.os and tbl_os.fabrica = $login_fabrica ";
			$camposOS = " tbl_os.serie as numero_serie, to_char(tbl_os.data_nf,'DD/MM/YYYY') as data_compra, ";
		}

		if($login_fabrica == 85){
			$joinOS = " JOIN tbl_os ON tbl_os.os = BI.os and tbl_os.fabrica = $login_fabrica ";
			$camposOS = " tbl_os.serie as serie, ";
		}

		if($login_fabrica == 50) {
			$campo_serie = " , bi_os.serie ";
			$cond_join_bi_os = "join bi_os on BI.os = bi_os.os AND bi_os.fabrica = $login_fabrica";
		}

		$sql = "SELECT DISTINCT  PE.peca                               ,
						PE.ativo                              ,
						PE.referencia                         ,
						PE.descricao                          ,
						PF.codigo_posto        AS posto_codigo,
						PO.nome                AS posto_nome  ,
						BI.os                                 ,
						BI.custo_peca                         ,
						BI.sua_os                             ,
						$camposOS
						DR.codigo              AS dr_codigo   ,
						DR.descricao           AS dr_descricao,
						DC.codigo              AS dc_codigo   ,
						DC.descricao           AS dc_descricao,
						DC.defeito_constatado  AS dc_id
			FROM      bi_os_item             BI
			$cond_join_bi_os
			$join_macro_linha_item
			JOIN      tbl_peca               PE ON PE.peca               = BI.peca
			JOIN      tbl_posto              PO ON PO.posto              = BI.posto
			JOIN      tbl_posto_fabrica      PF ON PF.posto              = BI.posto
			$joinTipoPosto
			$joinOS
			LEFT JOIN tbl_defeito_reclamado  DR ON DR.defeito_reclamado  = BI.defeito_reclamado AND DR.fabrica = $login_fabrica
			LEFT JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = BI.defeito_constatado
			JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado  = BI.servico_realizado $cond12
			$condFacricadoJoin
			$join_lenoxx
			$join_tipo_atendimento
			$join_cancelada
			WHERE BI.fabrica = $login_fabrica
			AND   PF.fabrica = $login_fabrica
			AND   BI.peca    = $peca
			$cond_matriz_filial_bi_os
			$condTipoPosto
			$cond_cancelada
		    $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11_bi_os";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
  			
			$total = 0;
			 echo "<br><a href='javascript:history.back()'>[Voltar]</a>";
			echo "<TABLE name='relatorio' id='relatorio' align='center' class='table table-striped table-bordered table-hover'>";
			echo "<thead>";
			echo "<tr class='titulo_coluna'>";
			echo "<th><b>OS</b></th>";
			echo ($login_fabrica == 85) ? "<TD height='15'><b>Nº Série</b></TD>" : "";
			echo "<th><b>Cód. Posto</b></th>";
			echo "<th><b>Posto</b></th>";

			if($login_fabrica == 42){
				echo "<TD height='15'><b>Nº Série</b></TD>";
			}

			if ($login_fabrica == 50){ #HD 86811 para Colormaq
				echo "<TD height='15'><b>Nº Série</b></TD>";
				echo "<TD height='15'><b>Data Fabricação</b></TD>";
			}
			echo "<th><b>Defeito Reclamado</b></th>";
			
			echo "<th height='15'><b>$tema</b></th>";
			if($login_fabrica == 35){
				echo "<th><b>PO#</b></th>";
				echo "<th><b>Data Compra</b></th>";
			}
			echo "</TR>";
			echo "</thead>";
			echo "<tbody>";

			for ($i=0; $i<pg_numrows($res); $i++){
				$posto_codigo   = trim(pg_result($res,$i,posto_codigo));
				$posto_nome     = trim(pg_result($res,$i,posto_nome));
				$dr_codigo      = trim(pg_result($res,$i,dr_codigo));
				$dr_descricao   = trim(pg_result($res,$i,dr_descricao));
				$dc_id          = trim(pg_result($res,$i,dc_id));
				$dc_codigo      = trim(pg_result($res,$i,dc_codigo));
				$dc_descricao   = trim(pg_result($res,$i,dc_descricao));
				$os             = trim(pg_result($res,$i,os));
				$sua_os         = trim(pg_result($res,$i,sua_os));
				$serie          = trim(pg_result($res,$i,serie));

				if($login_fabrica == 35){
					$po 				= pg_fetch_result($res, $i, numero_serie);
					$data_compra 		= pg_fetch_result($res, $i, data_compra);
				}

				$total_pecas += $custo_peca;
				$custo_peca   = number_format($custo_peca,2,",",".");
				if($login_fabrica == 50 or $login_fabrica == 5){ // HD 37460

					if(strlen($dr_codigo) == 0){
						$sqlx="SELECT defeito_reclamado_descricao, serie from tbl_os where os=$os and fabrica= $login_fabrica";
					} else { # HD 86811 para Colormaq
						$sqlx="SELECT serie FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
					}
					$resx = pg_exec($con,$sqlx);
					if(strlen($dr_codigo) == 0){
						$dr_descricao = pg_result($resx,0,defeito_reclamado_descricao);
					}
					$serie        = pg_result($resx,0,serie);
					$data_fabricacao = "";
					if(strlen($serie) > 0) {
						$sqld = "SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
								FROM tbl_numero_serie
								WHERE serie = '$serie'";
						$resd = pg_exec($con,$sqld);
						if(pg_numrows($resd) > 0) {
							$data_fabricacao=pg_result($resd,0,data_fabricacao);
						}
					}
				}


				echo "<TR>";
				echo "<TD align='left' nowrap><a href='".OS_BACK."os_press.php?os=$os' target='_blanck'>$sua_os</a></td>";
				echo ($login_fabrica ==85) ? "<TD height='15'>$serie</TD>" : "";
				echo "<TD align='left' nowrap>$posto_codigo</TD>";
				echo "<TD align='left' >$posto_nome</TD>";
				if ($login_fabrica == 50){ #HD 86811 para Colormaq
					echo "<TD align='left' nowrap>$serie</TD>";
					echo "<TD align='left' nowrap>$data_fabricacao</TD>";
				}

				if($login_fabrica == 42){
					echo "<TD align='left' nowrap>$serie</TD>";
				}

				if($login_fabrica == 15 or $login_fabrica == 5 || strlen($dr_descricao) == 0){
					$sql_dr       = "SELECT defeito_reclamado_descricao FROM tbl_os WHERE os = $os";
					$res_dr       = pg_exec($con,$sql_dr);
					$decricao_descricao = pg_result($res_dr,0,'defeito_reclamado_descricao');

					if(trim($decricao_descricao)){
						$dr_descricao = $decricao_descricao;
					}
				}
				if($dc_id == '0' and $BiMultiDefeitoOs =='t') {
					$sql_dc = "SELECT codigo, descricao FROM tbl_os_defeito_reclamado_constatado JOIN tbl_defeito_constatado using(defeito_constatado)
						WHERE os = $os order by defeito_constatado_reclamado limit 1 ";
					$res_dc = pg_query($con,$sql_dc);
					if(pg_num_rows($res_dc) > 0) {
						$dc_codigo = pg_fetch_result($res_dc, 0, 'codigo');
						$dc_descricao = pg_fetch_result($res_dc, 0, 'descricao');
					}
				}

				echo "<TD align='left'>$dr_codigo - $dr_descricao</TD>";
				
				echo "<TD align='left'>$dc_codigo - $dc_descricao</TD>";
				if($login_fabrica == 35){
					echo "<TD align='left'>$po</TD>";
					echo "<TD align='left'>$data_compra</TD>";
				}
				echo "</TR>";
			}
			$total_pecas = number_format($total_pecas,2,",",".");
			echo "</tbody>";
			echo " </TABLE>";
			echo "<a href='javascript:history.back()'>[Voltar]</a>";
		}


	}else{
		if($login_fabrica == 85){
			$condicao_gelopar = " and BI.classificacao_os is null ";
		}

		if (strlen($_GET['tipo_posto']) > 0) {
			$joinTipoPosto = " JOIN tbl_posto_fabrica PF ON PF.posto = BI.posto LEFT JOIN tbl_tipo_posto USING(tipo_posto) ";
			$condTipoPosto = " AND tbl_tipo_posto.tipo_posto IN(".str_replace("|", ",", $_GET['tipo_posto']).")";
		}

		$sql = "SELECT DISTINCT BI.os, tbl_servico_realizado.troca_de_peca,troca_produto
					INTO temp tmp_bi_os
						FROM bi_os BI
						$join_macro_linha
						$facricadoJoin
						$join_tipo_atendimento
						$join_cancelada
						$joinTipoPosto
						LEFT JOIN bi_os_item ON BI.os = bi_os_item.os
						LEFT JOIN tbl_servico_realizado ON bi_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
					WHERE BI.fabrica = $login_fabrica
						$cond_matriz_filial
						$condTipoPosto
						$cond_lenoxx
						$cond_cancelada
						$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11_bi_os
						$condicao_gelopar ;

				SELECT DISTINCT(os) INTO temp tmp_bi_os_sem_peca FROM tmp_bi_os WHERE troca_de_peca is NULL AND troca_produto is NULL;
				SELECT count( DISTINCT os) FROM tmp_bi_os_sem_peca ; ";
		$res = pg_exec ($con,$sql);

		$os_sem_peca = pg_result($res,0,0);

		$sql = "SELECT DISTINCT(os) INTO TEMP tmp_bi_os_troca_produto FROM tmp_bi_os WHERE troca_produto is TRUE;

				SELECT count( distinct bi_os_item.os) AS com_peca, SUM(bi_os_item.custo_peca) as total_preco
					FROM tmp_bi_os_troca_produto TBI
						JOIN bi_os_item ON TBI.os = bi_os_item.os
						JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado AND tbl_servico_realizado.troca_produto is true
					WHERE bi_os_item.fabrica = $login_fabrica
					AND bi_os_item.excluida IS NOT TRUE;";
		$res = pg_exec ($con,$sql);

		$os_com_produto = pg_result($res,0,0);
		$total_preco_os_com_produto = number_format(pg_result($res,0,1),2,",",".");
		$total_preco = pg_result($res,0,1);

		$sql = "SELECT DISTINCT(os)
					INTO temp tmp_bi_os_troca_peca
					FROM tmp_bi_os
					WHERE troca_de_peca is TRUE
					AND troca_produto is not TRUE
					AND os not IN (SELECT os FROM tmp_bi_os_troca_produto);

				SELECT count( distinct bi_os_item.os) AS com_peca,SUM(bi_os_item.custo_peca) as total_preco
		 			FROM tmp_bi_os_troca_peca TBI
		 			JOIN bi_os_item ON TBI.os = bi_os_item.os
		 			JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado AND tbl_servico_realizado.troca_de_peca is true
		 			JOIN tbl_peca ON bi_os_item.peca = tbl_peca.peca AND tbl_peca.produto_acabado IS NOT TRUE
		 			WHERE bi_os_item.fabrica = $login_fabrica
				;";

		$res = pg_exec ($con,$sql);

		$os_com_peca = pg_result($res,0,0);
		$total_preco_os_com_peca = number_format(pg_result($res,0,1),2,",",".");
		$total_preco += pg_result($res,0,1);

		$sql = "SELECT DISTINCT(os)
					INTO temp tmp_bi_os_ajuste
					FROM tmp_bi_os
					WHERE troca_de_peca is not true
					AND troca_produto is not true
					AND os not IN (SELECT os FROM tmp_bi_os_troca_produto)
					AND os not IN (SELECT os FROM tmp_bi_os_troca_peca);

				SELECT count(*) FROM tmp_bi_os_ajuste ;";
		$res = pg_exec ($con,$sql);

		$os_com_ajuste = pg_result($res,0,0);

		$total_quebra = $os_sem_peca+$os_com_peca+$os_com_ajuste+$os_com_produto;

		$porcentagem_os_sem_peca   =  number_format((($os_sem_peca * 100) / $total_quebra),2,",",".");
		$porcentagem_os_com_peca   =  number_format((($os_com_peca * 100) / $total_quebra),2,",",".");
		$porcentagem_os_com_ajuste  = number_format((($os_com_ajuste * 100) / $total_quebra),2,",",".");
		$porcentagem_os_com_produto = number_format((($os_com_produto * 100) / $total_quebra),2,",",".");

		$total_preco = number_format($total_preco,2,",",".");

		?>
		<br>
		<table id="resumo" align='center' class='table table-striped table-bordered table-hover table-normal' >
            <thead>
	            <tr class='titulo_tabela' >
	            	<?php
                    if ($areaAdminCliente != true) { ?>
                     	<th colspan="9" >Resumo</th>
                    <?php
                    } else { ?>
                    	<th colspan="8" >Resumo</th>
                    <?php
                    }?>
    	            
        	    </tr>
                <tr class='titulo_coluna' >
                    <th>Status</th>
                    <th>Qtde</th>
                    <th>%</th>
                    <?php
                    if ($areaAdminCliente != true) { ?>
                     	<th>Custo com peça</th>
                    <?php
                    } ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class='tal'><a href='#pecas_trocadas'>OS com PEÇA trocada</a></td>
                    <td class='tac'><?=$os_com_peca;?></td>
                    <td class='tac'><?=$porcentagem_os_com_peca?></td>
                    <?php
                	if ($areaAdminCliente != true) { ?>
                    	<td class='tac'>R$ <?=$total_preco_os_com_peca?></td>
                    <?php
                	} ?>
                </tr>
                <tr>
		    		<td class='tal'><a href='#produtos_trocados'>OS com PRODUTO trocado</a></td>
                    <td class='tac'><?=$os_com_produto;?></td>
                    <td class='tac'><?=$porcentagem_os_com_produto?></td>
                    <?php
                	if ($areaAdminCliente != true) { ?>
                    	<td class='tac'>R$ <?=$total_preco_os_com_produto?></td>
                    <?php
                	} ?>
                </tr>
                    <td class='tal'><a href='#pecas_ajustadas'>OS com PECA ajustada</a></td>
                    <td class='tac'><?=$os_com_ajuste;?></td>
                    <td class='tac'><?=$porcentagem_os_com_ajuste?></td>
                    <?php
                	if ($areaAdminCliente != true) { ?>
                    	<td class='tac'>R$ 0,00</td>
                    <?php
                	} ?>
                </tr>
				<tr>
                    <td class='tal'><a href='#os_sem_peca'>OS sem PECA</a></td>
                    <td class='tac'><?=$os_sem_peca;?></td>
                    <td class='tac'><?=$porcentagem_os_sem_peca?></td>
                    <?php
                	if ($areaAdminCliente != true) { ?>
                    	<td class='tac'>R$ 0,00</td>
                    <?php
                	} ?>
                </tr>
                <?php
                if ($areaAdminCliente != true) { ?>
                 	<tr>
						<td class='tal'><strong>Total</strong></td>
						<td class='tac'><?=$total_quebra?></td>
						<td class='tac'>100%</td>
						<td class='tac'>R$ <?=$total_preco?></td>
					</tr>
				<?php
                } ?>
            </tbody>
   		</table>

	<?php

		flush();

		if($login_fabrica == 96){
			echo "<div class='alert alert-block'>Essa consulta mostra o resultados das OS com <b>Peça</b>.</div>";
		}

		/*  DESATIVADO TULIO 06/04/2008
		$sql = "SELECT  PE.peca                              ,
						PE.ativo                             ,
						PE.referencia                        ,
						PE.descricao                         ,
						FA.descricao           AS f_nome     ,
						LI.nome                AS l_nome     ,
						MA.nome                AS m_nome     ,
						count(BI.os)           AS ocorrencia ,
						SUM(BI.custo_peca)     AS custo_peca ,
						SUM(BI.preco)          AS preco
			FROM      bi_os_item BI
			JOIN      tbl_peca    PE ON PE.peca    = BI.peca
			LEFT JOIN tbl_linha   LI ON LI.linha   = BI.linha
			LEFT JOIN tbl_familia FA ON FA.familia = BI.familia
			LEFT JOIN tbl_marca   MA ON MA.marca   = BI.marca
			WHERE BI.fabrica = $login_fabrica
			 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9
			GROUP BY    PE.peca                              ,
						PE.ativo                             ,
						PE.referencia                        ,
						PE.descricao                         ,
						f_nome                               ,
						l_nome                               ,
						m_nome
			ORDER BY ocorrencia DESC ";

		*/
		$produto_trocado = $_GET["produto_trocado"];
		if($login_fabrica == 24){
			$joinBI_os = " JOIN bi_os ON bi_os.os = BI.os and bi_os.fabrica = $login_fabrica ";
		}

		if($produto_trocado == "false"){

			if($login_fabrica == 134){
				$campo_custo_peca = " bi.preco ";
			}else{
				$campo_custo_peca = " bi.custo_peca ";
			}

			$sql = "SELECT	tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_peca.peca,
							tbl_peca.ativo,
							bi.qtde				AS ocorrencia ,
							bi.custo_peca		AS custo_peca
					FROM   (SELECT bi.peca, SUM (bi.qtde) AS qtde, SUM ($campo_custo_peca) AS custo_peca
							FROM bi_os_item BI
							JOIN tbl_servico_realizado USING(servico_realizado)
							$join_macro_linha_item
							$condFacricadoJoin
							$join_lenoxx
							$join_tipo_atendimento
							$joinBI_os
							$cond_matriz_filial_bi_os 
							$join_cancelada
							$joinTipoPosto
							WHERE bi.fabrica = $login_fabrica
							AND BI.excluida IS NOT TRUE
							AND tbl_servico_realizado.troca_de_peca IS TRUE
							$condTipoPosto
							$cond_cancelada
							$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11_bi_os
							GROUP BY bi.peca
					) bi
					JOIN tbl_peca ON bi.peca = tbl_peca.peca
					WHERE produto_acabado is not true
					ORDER BY ocorrencia DESC";

			$res = pg_query($con,$sql);

			if (pg_numrows($res) > 0) {
				$total = 0;

				echo "<TABLE name='pecas_trocadas' id='pecas_trocadas' class='table table-striped table-bordered table-hover table-normal' align='center'>";
				echo "<thead>";

				echo "<tr  class='titulo_tabela'>";
				echo "<th colspan='5'>Peças Trocadas</th>";
				echo "</tr>";
				echo "<tr  class='titulo_coluna'>";
				echo "<th width='100' height='15'><b>Referência</b></th>";
				echo "<th height='15'><b>Peça</b></th>";
				echo "<th width='120' height='15'><b>Ocorrência</b></th>";
				echo "<th width='50' height='15'><b>%</b></th>";
				if ($areaAdminCliente != true) {
					echo "<th width='50' height='15'><b>Custo</b></th>";
				}
				echo "</TR>";
				echo "</thead>";
				echo "<tbody>";

				for ($x = 0; $x < pg_numrows($res); $x++) {
					$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
				}
				$totais = array ();
				$percentuais = array();
				$categorias = array();
				for ($i=0; $i<pg_numrows($res); $i++){
					$referencia   = trim(pg_result($res,$i,referencia));
					$ativo        = trim(pg_result($res,$i,ativo));
					$descricao    = trim(pg_result($res,$i,descricao));
					if($login_fabrica == 20 and $pais !='BR' and strlen($descricao)==0){
						$descricao    = "<font color = 'red'>Tradução não cadastrada.</font>";
					}
					$peca         = trim(pg_result($res,$i,peca));
					$ocorrencia   = trim(pg_result($res,$i,ocorrencia));
					$custo_peca   = trim(pg_result($res,$i,custo_peca));
	#				$preco        = trim(pg_result($res,$i,preco));

					if($custo_peca==0) $custo_peca = $preco;
					if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

					if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';}

					$total_pecas    += $custo_peca;
					$total       += $ocorrencia ;
					$totais[] = (int) $ocorrencia;
					$porcentagem_total += $porcentagem;
					$percentuais[] = round($porcentagem_total,2);
					$cat = utf8_encode($referencia . ' -  ' . $descricao);
					$categorias[] = $cat;

					$porcentagem = number_format($porcentagem,2,",",".");
					$custo_peca = number_format($custo_peca,2,",",".");

					echo "<TR>";
						echo "<TD align='left' nowrap>";
						if($login_fabrica == 24){
							echo "<a href='$PHP_SELF?origem_listagem=defeito&produto=$produto&peca=$peca&data_inicial=$data_inicial&data_final=$data_final&aux_data_inicial=$aux_data_inicial&aux_data_final=$aux_data_final&tipo_data=$tipo_data&posto=$posto&lista_produtos=$lista_produtos&exceto_posto=$exceto_posto&pais=$pais&estado=$estado&tipo_os=trocada&linha=$linha&familia=$familia&tipo_atendimento=$tipo_atendimento&matriz_filial=$matriz_filial'>";
						}else{
							echo "<a href='$PHP_SELF?origem_listagem=defeito&produto=$produto&peca=$peca&data_inicial=$data_inicial&data_final=$data_final&aux_data_inicial=$aux_data_inicial&aux_data_final=$aux_data_final&tipo_data=$tipo_data&posto=$posto&lista_produtos=$lista_produtos&exceto_posto=$exceto_posto&pais=$pais&estado=$estado&tipo_os=trocada&linha=$linha&familia=$familia&tipo_atendimento=$tipo_atendimento'>";
						}

						echo "$referencia</TD>";
						echo "<TD align='left' nowrap>$descricao</TD>";
						echo "<TD align='center' nowrap>$ocorrencia</TD>";
						echo "<TD align='right' nowrap title=''>$porcentagem</TD>";
						if ($areaAdminCliente != true) {
							echo "<TD align='right' nowrap>$custo_peca</TD>";
						}
						echo "</TR>";
					}
					$total_pecas       = number_format($total_pecas,2,",",".");
					$porcentagem_total = number_format($porcentagem_total,2,",",".");
					echo "</tbody>";
					echo "<tr class='table_line'>";
					echo "<td colspan='2'><font size='2'><b><CENTER>TOTAL</b></td>";
					echo "<td align='center' ><font size='2' color='009900'><b>$total</b></td>";
					echo "<td align='right' ><font size='2' color='009900'><b>$porcentagem_total</b></td>";
					if ($areaAdminCliente != true) {
						echo "<td align='right' ><font size='2' color='009900'><b>$total_pecas</b></td>";
					}
					echO "</tr>";
					echo " </TABLE>";

				if ($login_fabrica == 152) { ?>
				<script type="text/javascript" src="<?=ADMCLI_BACK?>plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
				<script src="<?=ADMCLI_BACK?>js/highcharts_4.1.5.js"></script>
				<script src="<?=ADMCLI_BACK?>js/exporting.js"></script>

				<script>
					$(function () {
						var chart;
						$(document).ready(function() {
							chart = new Highcharts.Chart({
								chart: {
									renderTo: 'chart_peca',
									zoomType: 'xy'
								},
								title: {
									text: 'Peças de quebra'
								},
								subtitle: {
									text: '	<?php echo 'Produto: '.$produto_referencia ;?>'
								},
								credits: {
									enabled: false
								},
								xAxis: [{
									categories: <?php echo json_encode($categorias) ?>
								}],
								yAxis: [{
									title: {
										text: ''
									},
									labels: {
										formatter: function() {
											return this.value +' %';
										},
											style: {
												color: '#A0A0A0'
											}
									}
								}, {
									title: {
										text: ''
									},
									labels: {
										formatter: function() {
											return this.value;
										},
											style: {
												color: '#4572A7'
											}
									},
										opposite: true
								}],
								tooltip: {
									formatter: function() {
										return ''+
											this.x +': '+ this.y +
											(this.series.name == 'Perc' ? ' %' : '');
									}
								},
									legend: {
										enabled: false
									},
									series: [{
										name: 'Qtde',
											color: '#4572A7',
											type: 'column',
											data: <?php echo json_encode($totais) ?>

									}, {
										name: 'Perc',
											color: '#FF0000',
											type: 'spline',
											data: <?php echo json_encode($percentuais) ?>
									}]
							});
						});

					});
				</script>
				<div id="chart_peca" style="min-width: 400px; height: 400px; margin: 0 auto"></div><?
				}
			}else{
				// echo "<div class='alert alert-block alert-error text-center'>Não existe resultado para essa consulta.</div>";
			}
		}

		$sql = "SELECT	tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_peca.peca,
						tbl_peca.ativo,
						bi.qtde				AS ocorrencia ,
						bi.custo_peca		AS custo_peca
				FROM   (SELECT bi.peca, SUM (bi.qtde) AS qtde, SUM (bi.custo_peca) AS custo_peca
						FROM bi_os_item BI
						$join_macro_linha_item
						JOIN tbl_servico_realizado USING(servico_realizado)
						$condFacricadoJoin
						$join_lenoxx
						$join_tipo_atendimento
						$join_cancelada
						WHERE bi.fabrica = $login_fabrica
						AND BI.excluida IS NOT TRUE
						$cond_cancelada
						AND tbl_servico_realizado.troca_produto IS TRUE
						--AND BI.os not in (select os from tmp_os_ajuste5)
						$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11_bi_os
						GROUP BY bi.peca
				) bi
				JOIN tbl_peca ON bi.peca = tbl_peca.peca
				WHERE tbl_peca.produto_acabado
				ORDER BY ocorrencia DESC";

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;
			$total_ocorrencia = 0;
			$total_pecas = 0;
			$porcentagem_total = 0;
			echo "<TABLE name='produtos_trocados' id='produtos_trocados' class='table table-striped table-bordered table-hover table-normal' align='center'>";
			echo "<thead>";

			echo "<tr  class='titulo_tabela'>";
			echo "<th colspan='5'>Produtos Trocados</th>";
			echo "</tr>";
			echo "<tr  class='titulo_coluna'>";
			echo "<th width='100' height='15'><b>Referência</b></th>";
			echo "<th height='15'><b>Peça</b></th>";
			echo "<th width='120' height='15'><b>Ocorrência</b></th>";
			echo "<th width='50' height='15'><b>%</b></th>";
			if ($areaAdminCliente != true) {
				echo "<th width='50' height='15'><b>Custo</b></th>";
			}
			echo "</TR>";
			echo "</thead>";
			echo "<tbody>";

			for ($x = 0; $x < pg_numrows($res); $x++) {
				$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			}
			for ($i=0; $i<pg_numrows($res); $i++){
				$referencia   = trim(pg_result($res,$i,referencia));
				$ativo        = trim(pg_result($res,$i,ativo));
				$descricao    = trim(pg_result($res,$i,descricao));
				if($login_fabrica == 20 and $pais !='BR' and strlen($descricao)==0){
					$descricao    = "<font color = 'red'>Tradução não cadastrada.</font>";
				}
				$peca         = trim(pg_result($res,$i,peca));
				$ocorrencia   = trim(pg_result($res,$i,ocorrencia));
				$custo_peca   = trim(pg_result($res,$i,custo_peca));
#				$preco        = trim(pg_result($res,$i,preco));

				if($custo_peca==0) $custo_peca = $preco;
				if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

				if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';}

				$total_pecas    += $custo_peca;
				$total       += $ocorrencia ;
				$porcentagem_total += $porcentagem;
				$porcentagem = number_format($porcentagem,2,",",".");
				$custo_peca = number_format($custo_peca,2,",",".");

				echo "<TR>";
				echo "<TD align='left' nowrap>";

				if($login_fabrica == 24){
					echo "<a href='$PHP_SELF?origem_listagem=defeito&produto=$produto&peca=$peca&data_inicial=$data_inicial&data_final=$data_final&aux_data_inicial=$aux_data_inicial&aux_data_final=$aux_data_final&tipo_data=$tipo_data&posto=$posto&lista_produtos=$lista_produtos&exceto_posto=$exceto_posto&pais=$pais&estado=$estado&tipo_os=prod_trocado&linha=$linha&familia=$familia&tipo_atendimento=$tipo_atendimento&matriz_filial=$matriz_filial'>";
				}else{
					echo "<a href='$PHP_SELF?origem_listagem=defeito&produto=$produto&peca=$peca&data_inicial=$data_inicial&data_final=$data_final&aux_data_inicial=$aux_data_inicial&aux_data_final=$aux_data_final&tipo_data=$tipo_data&posto=$posto&lista_produtos=$lista_produtos&exceto_posto=$exceto_posto&pais=$pais&estado=$estado&tipo_os=prod_trocado&linha=$linha&familia=$familia&tipo_atendimento=$tipo_atendimento'>";
				}				

				echo "$referencia</TD>";
				echo "<TD align='left' nowrap>$descricao</TD>";
				echo "<TD align='center' nowrap>$ocorrencia</TD>";
				echo "<TD align='right' nowrap title=''>$porcentagem</TD>";
				if ($areaAdminCliente != true) {
					echo "<TD align='right' nowrap>$custo_peca</TD>";
				}
				echo "</TR>";
			}
			$total_pecas       = number_format($total_pecas,2,",",".");
			$porcentagem_total = number_format($porcentagem_total,2,",",".");
			echo "</tbody>";
			echo "<tr class='table_line'>";
			echo "<td colspan='2'><font size='2'><b><CENTER>TOTAL</b></td>";
			echo "<td align='center' ><font size='2' color='009900'><b>$total</b></td>";
			echo "<td align='right' ><font size='2' color='009900'><b>$porcentagem_total</b></td>";
			if ($areaAdminCliente != true) {
				echo "<td align='right' ><font size='2' color='009900'><b>$total_pecas</b></td>";
			}
			echO "</tr>";
			echo " </TABLE>";
		}else{
			// echo "<div class='alert alert-block alert-error text-center'>Não existe resultado para essa consulta.</div>";
		}

		if($telecontrol_distrib){
			$sql_distrib = "SELECT distinct bi_os_item.os AS os_troca
			FROM tmp_bi_os_troca_produto TBI
				JOIN bi_os_item ON TBI.os = bi_os_item.os
				JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado AND tbl_servico_realizado.troca_produto is true
			WHERE bi_os_item.fabrica = $login_fabrica
			AND bi_os_item.excluida IS NOT TRUE;";

			$res_distrib = pg_query($con, $sql_distrib);
			if(pg_num_rows($res_distrib) > 0){
				for($os = 0; $os < pg_num_rows($res_distrib); $os++){
					$oss_trocas[] = pg_fetch_result($res_distrib, $os, os_troca);
				}
			}


			echo "<TABLE name='produtos_trocados' id='produtos_trocados' class='table table-striped table-bordered table-hover table-normal' align='center'>";
			echo "<thead>";

			echo "<tr  class='titulo_tabela'>";
			echo "<th colspan='5'>Peças Originalmente Demandadas Antes da Troca do Produto</th>";
			echo "</tr>";
			echo "</thead>";
			echo "<tr  class='titulo_coluna'>";
			echo "<th width='100' height='15'><b>Referência</b></th>";
			echo "<th height='15'><b>Descrição de Peça</b></th>";
			echo "<th width='120' height='15'><b>Ocorrência</b></th>";
			echo "<th width='50' height='15'><b>%</b></th>";
			echo "<th width='50' height='15'><b>Custo</b></th>";
			echo "</TR>";
			echo "</thead>";
			echo "<tbody>";
			for($t = 0; $t < count($oss_trocas); $t++ ){
				$sql_troca = "SELECT tbl_peca.referencia, tbl_peca.descricao, SUM(qtde) as quantidade,
				SUM(bi_os_item.custo_peca) as custo_peca FROM bi_os_item
				JOIN tbl_peca on tbl_peca.peca = bi_os_item.peca
				JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado AND tbl_servico_realizado.troca_produto is true
				WHERE os = $oss_trocas[$t]
				GROUP BY tbl_peca.referencia, tbl_peca.descricao, tbl_servico_realizado.servico_realizado";

				$res_troca = pg_query($con, $sql_troca);

				if(pg_num_rows($res_troca) > 0){
					for($tr = 0; $tr < pg_num_rows($res_troca); $tr++){
						$total_pecas = 0;
						$total = 0;
						$referencia_peca = pg_fetch_result($res_troca, $tr, 'referencia');
						$descricao_peca  = pg_fetch_result($res_troca, $tr, 'descricao');
						$quantidade      = pg_fetch_result($res_troca, $tr, 'quantidade');
						$custo_peca      = pg_fetch_result($res_troca, $tr, 'custo_peca');

						if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

						$total_pecas    += $custo_peca;
						$total       += $ocorrencia ;
						$porcentagem_total += $porcentagem;
						$porcentagem = number_format($porcentagem,2,",",".");

						echo "<tr>";
						echo "<td align='left' nowrap>$referencia_peca</td>";
						echo "<td align='left' nowrap>$descricao_peca</td>";
						echo "<td align='left' nowrap>$quantidade</td>";
						echo "<td align='left' nowrap>$porcentagem</td>";
						echo "<td align='left' nowrap>$custo_peca</td>";
						echo "</tr>";
					}
				}
			}
			

			echo "</tbody></table>";
		}
			
		// pecas com ajuste

		// $sql = "SELECT	tbl_peca.referencia,
		// 				tbl_peca.descricao,
		// 				tbl_peca.peca,
		// 				tbl_peca.ativo,
		// 				bi.qtde				AS ocorrencia ,
		// 				bi.custo_peca		AS custo_peca
		// 		FROM   (SELECT bi.peca, SUM (bi.qtde) AS qtde, SUM (bi.custo_peca) AS custo_peca
		// 				FROM bi_os_item BI
		// 				JOIN tbl_servico_realizado USING(servico_realizado)
		// 				$condFacricadoJoin
		// 				$join_lenoxx
		// 				WHERE bi.fabrica = $login_fabrica
		// 				--AND BI.os in (select os from tmp_os_ajuste3)
		// 				AND BI.excluida IS NOT TRUE
		// 				AND tbl_servico_realizado.troca_de_peca is not true
		// 				AND tbl_servico_realizado.troca_produto is not true
		// 				$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11_bi_os
		// 				GROUP BY bi.peca
		// 		) bi
		// 		JOIN tbl_peca ON bi.peca = tbl_peca.peca
		// 		ORDER BY ocorrencia DESC";

		if($produto_trocado == "false"){
			$sql = "SELECT	tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_peca.peca,
						tbl_peca.ativo,
						bi.qtde				AS ocorrencia ,
						bi.custo_peca		AS custo_peca
				FROM   (SELECT bi_os_item.peca, SUM (bi_os_item.qtde) AS qtde,SUM(bi_os_item.custo_peca) as custo_peca
							FROM tmp_bi_os_ajuste AS TBI
								JOIN bi_os_item ON TBI.os = bi_os_item.os
								JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado
							WHERE bi_os_item.fabrica = $login_fabrica
								AND bi_os_item.excluida IS NOT TRUE
								AND tbl_servico_realizado.troca_produto is not true
								AND tbl_servico_realizado.troca_produto is not true
							GROUP BY bi_os_item.peca
				) bi
				JOIN tbl_peca ON bi.peca = tbl_peca.peca
				ORDER BY ocorrencia DESC";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				$total = 0;

				echo "<TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center' name='pecas_ajustadas' id='pecas_ajustadas' class='table table-striped table-bordered table-hover table-normal'>";
				echo "<thead>";
				echo "<tr  class='titulo_tabela'>";
	                        echo "<th colspan='5'>Peças Ajustadas</th>";
	                        echo "</tr>";
				echo "<tr  class='titulo_coluna'>";
				echo "<th width='100' height='15'><b>Referência</b></th>";
				echo "<th height='15'><b>Peça</b></th>";
				echo "<th width='120' height='15'><b>Ocorrência</b></th>";
				echo "<th width='50' height='15'><b>%</b></th>";
				if ($areaAdminCliente != true) {
					echo "<th width='50' height='15'><b>Custo</b></th>";
				}
				echo "</TR>";
				echo "</thead>";
				echo "<tbody>";
				$total_ocorrencia = 0;
				$total_pecas = 0;
				$porcentagem_total = 0;
				for ($x = 0; $x < pg_numrows($res); $x++) {
					 $total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
				}

				$porcentagem = 0 ;
				$total_pecas = 0 ;
				$custo_peca  = 0 ;
				$preco = 0;
				for ($i=0; $i<pg_numrows($res); $i++){
					$referencia   = trim(pg_result($res,$i,referencia));
					$ativo        = trim(pg_result($res,$i,ativo));
					$descricao    = trim(pg_result($res,$i,descricao));
					if($login_fabrica == 20 and $pais !='BR' and strlen($descricao)==0){
						$descricao    = "<font color = 'red'>Tradução não cadastrada.</font>";
					}
					$peca         = trim(pg_result($res,$i,peca));
	#				$familia      = trim(pg_result($res,$i,f_nome));
	#				$linha        = trim(pg_result($res,$i,l_nome));
	#				$marca        = trim(pg_result($res,$i,m_nome));
					$ocorrencia   = trim(pg_result($res,$i,ocorrencia));
					$custo_peca   = trim(pg_result($res,$i,custo_peca));
	#				$preco        = trim(pg_result($res,$i,preco));

					if($custo_peca==0) $custo_peca = $preco;
					if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

					if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';}

					$total_pecas    += $custo_peca;
					$total       += $ocorrencia ;
					$porcentagem_total += $porcentagem;
					$porcentagem = number_format($porcentagem,2,",",".");
					$custo_peca = number_format($custo_peca,2,",",".");
					echo "<TR>";
					echo "<TD align='left' nowrap>";

					if($login_fabrica == 24){
						echo "<a href='$PHP_SELF?produto=$produto&peca=$peca&data_inicial=$data_inicial&data_final=$data_final&aux_data_inicial=$aux_data_inicial&aux_data_final=$aux_data_final&tipo_data=$tipo_data&posto=$posto&lista_produtos=$lista_produtos&exceto_posto=$exceto_posto&pais=$pais&estado=$estado&tipo_os=ajustada&linha=$linha&familia=$familia&tipo_atendimento=$tipo_atendimento&matriz_filial=$matriz_filial'>";
					}else{
						echo "<a href='$PHP_SELF?produto=$produto&peca=$peca&data_inicial=$data_inicial&data_final=$data_final&aux_data_inicial=$aux_data_inicial&aux_data_final=$aux_data_final&tipo_data=$tipo_data&posto=$posto&lista_produtos=$lista_produtos&exceto_posto=$exceto_posto&pais=$pais&estado=$estado&tipo_os=ajustada&linha=$linha&familia=$familia&tipo_atendimento=$tipo_atendimento'>";
					}
					echo "$referencia</TD>";
					echo "<TD class='tal' nowrap>$descricao</TD>";
					echo "<TD class='tac' nowrap>$ocorrencia</TD>";
					echo "<TD class='tac' nowrap title=''>$porcentagem</TD>";
					if ($areaAdminCliente != true) {
						echo "<TD class='tac' nowrap>R$ 0,00</TD>";
					}
					echo "</TR>";
				}
				$total_pecas       = number_format($total_pecas,2,",",".");
				$porcentagem_total = number_format($porcentagem_total,2,",",".");
				echo "</tbody>";
				if ($areaAdminCliente != true) {
					echo "<tr class='table_line'>";
					echo "<td colspan='2'><font size='2'><b><CENTER>TOTAL</b></td>";
					echo "<td class='tac' ><font size='2' color='009900'><b>$total</b></td>";
					echo "<td class='tac' ><font size='2' color='009900'><b>$porcentagem_total</b></td>";
					if ($areaAdminCliente != true) {
						echo "<td class='tac' ><font size='2' color='009900'><b>R$ $total_pecas</b></td>";
					}
					echO "</tr>";
				}
				echo " </TABLE>";
			}else{
				// echo "<div class='alert alert-block alert-error text-center'>Não existe resultado para essa consulta.</div>";
			}

			if($login_fabrica == 85){
				$condicao_gelopar = " and bi_os.classificacao_os is null ";
			}

			$sql = "DROP table IF EXISTS temp_bi_os_sem_peca2_$login_fabrica";
			$res = pg_exec ($con,$sql);

			if($login_fabrica == 35){
				$joinOS = " JOIN tbl_os ON tbl_os.os = BI.os and tbl_os.fabrica = $login_fabrica ";
				$joinOS_2 = "join tbl_os on tbl_os.os = bi_os.os and tbl_os.fabrica = $login_fabrica ";
				$camposOS = " numero_serie, to_char(data_nf,'DD/MM/YYYY') as data_compra, ";
				$camposOS_2 = " tbl_os.serie as numero_serie, tbl_os.data_nf, ";
			}

			// OS sem peça, lenoxx pediu para tirar...
		        $sql = "SELECT
					posto_codigo,
					posto_nome ,
					os ,
					sua_os ,
					dr_codigo ,
					dr_descricao,
					dc_codigo ,
					dc_descricao ,
					dc_id,
					$camposOS
					serie
					into temp_bi_os_sem_peca2_$login_fabrica
					FROM
					( SELECT distinct
					PF.codigo_posto AS posto_codigo,
					PO.nome AS posto_nome ,
					BI.os ,
					BI.sua_os ,
					DR.codigo AS dr_codigo ,
					DR.descricao AS dr_descricao,
					DC.codigo AS dc_codigo ,
					DC.descricao AS dc_descricao,
					DC.defeito_constatado AS dc_id,
					$camposOS_2
					BI.serie
					FROM bi_os BI
					JOIN tbl_posto PO ON PO.posto = BI.posto
					JOIN tbl_posto_fabrica PF ON PF.posto = BI.posto
					$join_macro_linha
					$joinOS
					LEFT JOIN tbl_defeito_reclamado DR ON DR.defeito_reclamado = BI.defeito_reclamado AND DR.fabrica = $login_fabrica
					LEFT JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = BI.defeito_constatado
					$join_lenoxx
					$join_tipo_atendimento
					$join_cancelada
					WHERE
					BI.fabrica = $login_fabrica
					AND   PF.fabrica = $login_fabrica
				        $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11_bi_os $cond_cancelada

					EXCEPT
					SELECT DISTINCT
					PF.codigo_posto AS posto_codigo,
					PO.nome AS posto_nome ,
					bi_os.os ,
					bi_os.sua_os ,
					DR.codigo AS dr_codigo ,
					DR.descricao AS dr_descricao,
					DC.codigo AS dc_codigo ,
					DC.descricao AS dc_descricao,
					DC.defeito_constatado AS dc_id,
					$camposOS_2
					bi_os.serie
					FROM bi_os_item BI
					JOIN bi_os on BI.os = bi_os.os
					JOIN tbl_posto PO ON PO.posto = BI.posto
					JOIN tbl_posto_fabrica PF ON PF.posto = BI.posto
					$join_macro_linha_item
					$join_tipo_atendimento
					$join_cancelada
					$joinOS_2
					LEFT JOIN tbl_defeito_reclamado DR ON DR.defeito_reclamado = BI.defeito_reclamado AND DR.fabrica = $login_fabrica
					LEFT JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = BI.defeito_constatado
					LEFT JOIN tbl_servico_realizado SR ON SR.servico_realizado = BI.servico_realizado
					LEFT JOIN tbl_defeito DE ON DE.defeito = BI.defeito
					WHERE BI.fabrica = $login_fabrica
					AND BI.peca IS NOT NULL
					AND BI.excluida IS NOT TRUE
				        $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11_bi_os
				        $condicao_gelopar $cond_cancelada
					)X; select *from temp_bi_os_sem_peca2_$login_fabrica order by 6;";

			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {

				if ($login_fabrica != 11) {
					$total = 0;
					echo "<TABLE name='os_sem_peca' id='os_sem_peca' align='center' class='table table-striped table-bordered table-hover'>";
					echo "<thead>";
					echo "<tr  class='titulo_tabela'>";
		                        echo "<th colspan='8'>OS Sem PEÇA</th>";
		                        echo "</tr>";
					echo "<tr class='titulo_coluna'>";
					echo "<th><b>OS</b></th>";
					echo ($login_fabrica ==85) ? "<TD height='15'><b>Nº Série</b></TD>" : "";
					echo "<th><b>Cód. Posto</b></th>";
					echo "<th><b>Posto</b></th>";

					if($login_fabrica == 42){
						echo "<TD height='15'><b>Nº Série</b></TD>";
					}

					if ($login_fabrica == 50){ #HD 86811 para Colormaq
						echo "<TD height='15'><b>Nº Série</b></TD>";
						echo "<TD height='15'><b>Data Fabricação</b></TD>";
					}
					echo "<th><b>Defeito Reclamado</b></th>";
					echo "<th height='15'><b>$tema</b></th>";
					if($login_fabrica == 35){
						echo "<th><b>PO#</b></th>";
						echo "<th><b>Data da Compra</b></th>";
					}
					if($login_fabrica == 137){
						echo "<th height='15'><b>N. Lote</b></th>";
					}
					echo "</TR>";
					echo "</thead>";
					echo "<tbody>";

					for ($i=0; $i<pg_numrows($res); $i++){
						$posto_codigo   = trim(pg_result($res,$i,posto_codigo));
						$posto_nome     = trim(pg_result($res,$i,posto_nome));
						$dr_codigo      = trim(pg_result($res,$i,dr_codigo));
						$dr_descricao   = trim(pg_result($res,$i,dr_descricao));
						$dc_codigo      = trim(pg_result($res,$i,dc_codigo));
						$dc_descricao   = trim(pg_result($res,$i,dc_descricao));
						$dc_id          = trim(pg_result($res,$i,dc_id));
						$os             = trim(pg_result($res,$i,os));
						$sua_os         = trim(pg_result($res,$i,sua_os));
						$lote         	= trim(pg_result($res,$i,serie));

						if($login_fabrica == 35){
							$numero_serie = pg_fetch_result($res, $i, numero_serie);
							$data_compra = pg_fetch_result($res, $i, data_compra);
						}

						$total_pecas += $custo_peca;
						$custo_peca   = number_format($custo_peca,2,",",".");
						if($login_fabrica == 50 or $login_fabrica == 5){ // HD 37460

							if(strlen($dr_codigo) == 0){
								$sqlx="SELECT defeito_reclamado_descricao, serie from tbl_os where os=$os and fabrica= $login_fabrica";
							} else { # HD 86811 para Colormaq
								$sqlx="SELECT serie FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
							}
							$resx = pg_exec($con,$sqlx);
							if(strlen($dr_codigo) == 0){
								$dr_descricao = pg_result($resx,0,defeito_reclamado_descricao);
							}
							$serie        = pg_result($resx,0,serie);
							$data_fabricacao = "";
							if(strlen($serie) > 0) {
								$sqld = "SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
									FROM tbl_numero_serie
									WHERE serie = '$serie'";
								$resd = pg_exec($con,$sqld);
								if(pg_numrows($resd) > 0) {
									$data_fabricacao=pg_result($resd,0,data_fabricacao);
								}
							}
						}

						if($login_fabrica == 42){
							$sqlx="SELECT serie FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
							$resx = pg_exec($con,$sqlx);
							$serie        = pg_result($resx,0,serie);
						}

						if($dc_id == '0' and $BiMultiDefeitoOs =='t') {
							$sql_dc = "SELECT codigo, descricao FROM tbl_os_defeito_reclamado_constatado JOIN tbl_defeito_constatado using(defeito_constatado)
										WHERE os = $os order by defeito_constatado_reclamado limit 1 ";
							$res_dc = pg_query($con,$sql_dc);
							if(pg_num_rows($res_dc) > 0) {
								$dc_codigo = pg_fetch_result($res_dc, 0, 'codigo');
								$dc_descricao = pg_fetch_result($res_dc, 0, 'descricao');
							}
						}
						echo "<TR>";
						echo "<TD align='left' nowrap><a href='".OS_BACK."os_press.php?os=$os' target='_blanck'>$sua_os</a></td>";
						echo ($login_fabrica ==85) ? "<TD height='15'>$lote</TD>" : "";
						echo "<TD align='left' nowrap>$posto_codigo</TD>";
						echo "<TD align='left' >$posto_nome</TD>";

						if($login_fabrica == 42){
							echo "<TD align='left' nowrap>$serie</TD>";
						}

						if ($login_fabrica == 50){ #HD 86811 para Colormaq
							echo "<TD align='left' nowrap>$serie</TD>";
							echo "<TD align='left' nowrap>$data_fabricacao</TD>";
						}
						if($login_fabrica == 15 or $login_fabrica == 5 or strlen($dr_descricao) == 0){
							$sql_dr       = "SELECT defeito_reclamado_descricao FROM tbl_os WHERE os = $os";
							$res_dr       = pg_exec($con,$sql_dr);
							$decricao_descricao = pg_result($res_dr,0,'defeito_reclamado_descricao');

							if(trim($decricao_descricao)){
								$dr_descricao = $decricao_descricao;
							}
						}
						echo "<TD align='left'>$dr_codigo - $dr_descricao</TD>";
						
						echo "<TD align='left'>$dc_codigo - $dc_descricao</TD>";
						if($login_fabrica == 35){
							echo "<TD align='left'>$numero_serie</TD>";
							echo "<TD align='left'>$data_compra</TD>";
						}
						if($login_fabrica == 137){
							echo "<TD align='left'>{$lote}</TD>";
						}
					echo "</TR>";
					}
					$total_pecas = number_format($total_pecas,2,",",".");
					echo "</tbody>";
					echo " </TABLE>";
				}

			}
		}



		if ($login_fabrica == 11) {
			$sql = "SELECT COUNT(*) as ocorrencia,dc_descricao from temp_bi_os_sem_peca2_$login_fabrica group by dc_descricao order by ocorrencia desc";
			$res = pg_exec ($con,$sql);

			$sqlT = "SELECT COUNT(*) FROM temp_bi_os_sem_peca2_$login_fabrica";
			$resT = pg_exec ($con,$sqlT);


			if (pg_numrows($resT) > 0) {

				$total_os = pg_result($resT,0,0);

			}

			if (pg_numrows($res) > 0) {


				echo "<TABLE name='os_sem_peca' id='os_sem_peca' align='center' class='table table-striped table-bordered table-hover'>";
				echo "<thead>";
				echo "<tr  class='titulo_tabela'>";
        	                echo "<th colspan='8'>Defeitos em OS sem peças</th>";
	                        echo "</tr>";
				echo "<tr class='titulo_coluna'>";
				echo "<th><b>$tema</b></th>";
				echo "<th><b>Ocorrencias</b></th>";
				echo "<th><b>%</b></th>";
				echo "</TR>";
				echo "</thead>";
				echo "<tbody>";

				for ($i=0; $i<pg_numrows($res); $i++){
        	                        $ocorrencia     = trim(pg_result($res,$i,ocorrencia));
                        	        $dc_descricao   = trim(pg_result($res,$i,dc_descricao));


					if ($total_os > 0) $porcentagem_os = (($ocorrencia * 100) / $total_os);

					$porcentagem_os = number_format($porcentagem_os,2,",",".");
					echo "<TR>";
					echo "<TD align='left' nowrap>
					<a href='$PHP_SELF?origem_tipo=defeito&dr_descricao=$dc_descricao'>
					$dc_descricao</a></TD>";
					echo "<TD class='tac' nowrap>$ocorrencia</a></td>";
					echo "<TD class='tac' nowrap></a>$porcentagem_os</td>";


				}
			}
		}
	}
}

echo "</div>";

flush();

?>


