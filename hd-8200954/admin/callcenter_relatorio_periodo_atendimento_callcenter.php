<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':'');

$layout_menu = "callcenter";
$title = "RELATÓRIO PERÍODO DE ATENDIMENTO";
?>
<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/ajuste.css" />



<?
	$data_inicial       = $_GET['data_inicial'];
	$data_final         = $_GET['data_final'];
	$produto            = $_GET['produto'];
	$natureza_chamado   = $_GET['natureza'];
	$status             = $_GET['status'];
	$tipo               = $_GET['tipo'];
	$linhas             = $_GET['linhas']; 

	if($login_fabrica == 90){
		$classificacao = $_GET["classificacao"];
		if(!empty($classificacao)){
			$condCl = " AND tbl_hd_chamado.hd_classificacao = $classificacao ";
		}
	}

	if(in_array($login_fabrica, array(101,169,170))){
		$origem = $_GET["origem"];
	}

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	$cond_5 = " 1 = 1 ";
	$cond_6 = " 1 = 1 ";

	if(strlen($produto)>0){
		$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}elseif($login_fabrica == 85 and strlen(trim($natureza_chamado))==0){
		$cond_2 = " tbl_hd_chamado.categoria <> 'garantia_estendida' ";
	}

	if(strlen($status)>0){
		if($status == "nao_resolvido"){
			$cond_3 = " lower(tbl_hd_chamado.status) <> 'resolvido'  ";
		}else{
			$cond_3 = " tbl_hd_chamado.status = '".utf8_decode($status)."'  ";
		}
	}

	#HD 382552
	if($status == 'Resolvido' and $login_fabrica <> 11){
		$cond_status = "(SELECT data FROM tbl_hd_chamado_item WHERE upper(status_item) = 'RESOLVIDO' AND hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1)";
	}elseif($status == 'Cancelado'){
		$cond_status = "(SELECT data FROM tbl_hd_chamado_item WHERE upper(status_item) = 'CANCELADO' AND hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1)";
	}elseif($status == 'Aberto'){
		$cond_status = "tbl_hd_chamado.data ";
	}else{
		$cond_status = "tbl_hd_chamado.data ";
	}

	if($login_fabrica==6){
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if ($tipo == 0 AND $login_fabrica != 90) {
		$cond_5 = "(tbl_hd_chamado_extra.dias_aberto = $tipo OR tbl_hd_chamado_extra.dias_aberto IS NULL)";
	}
	else {
		if($login_fabrica == 90 ){
			$cond_5 = " CASE
						WHEN tbl_hd_chamado.status = 'Resolvido' 
						THEN (SELECT tbl_hd_chamado_item.data
						FROM tbl_hd_chamado_item
						WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
						and tbl_hd_chamado_item.interno is not true
						AND tbl_hd_chamado.status is not null
						ORDER BY data desc LIMIT 1)::date - tbl_hd_chamado.data::date

						ELSE current_date - tbl_hd_chamado.data::date
						END = $tipo ";
		}elseif($login_fabrica == 35 AND $status == "Ag. Consumidor"){
			$cond_5 = "(SELECT count(*)
						FROM fn_calendario((tbl_hd_chamado.data::date + 1),CURRENT_DATE)
						WHERE nome_dia not in ('Domingo','Sábado')) = $tipo";
		} else {
			$cond_5 = "tbl_hd_chamado_extra.dias_aberto = $tipo";
		}
	}

	if($atendente) {
		$cond_6 = " tbl_hd_chamado.atendente IN ( $atendente )  ";
	}

	if($login_fabrica == 101 and strlen(trim($origem))>0){
        $cond_origem = "and tbl_hd_chamado_extra.origem = '$origem' ";
    }else if(in_array($login_fabrica, array(169,170)) AND strlen(trim($origem)) > 0){
    	$cond_origem = "AND tbl_hd_chamado_extra.hd_chamado_origem = $origem ";
    }

	if(!empty($linhas)) {
		$join_linha = " JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto ";
		$cond_linha = " AND tbl_produto.linha  in ( $linhas ) "; 
	}
	if(strlen($msg_erro)==0){

		if(in_array($login_fabrica, array(169,170))){
			$sql_campos = ", tbl_hd_chamado_origem.descricao AS origem ,
							tbl_hd_classificacao.descricao AS classificacao ,
							tbl_hd_motivo_ligacao.descricao AS providencia,
							tbl_motivo_contato.descricao as motivo_contato_descricao,
							tbl_hd_providencia.descricao as providencia_descricao
			";

			$sql_joins .= "	JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_chamado_extra.hd_chamado_origem
								AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
							JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao
								AND tbl_hd_classificacao.fabrica = {$login_fabrica}
							JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao
								AND tbl_hd_motivo_ligacao.fabrica = {$login_fabrica}
							";
		}

		if($login_fabrica == 74){
	        $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
	    }

		$sql = "
				SELECT Y.hd_chamado AS callcenter  ,
						Y.titulo                   ,
						to_char(Y.data_abertura,'DD/MM/YYYY') as abertura            ,
						to_char(Y.data_interacao,'DD/MM/YYYY') AS ultima_interacao   ,
						tbl_admin.login
				FROM(
					SELECT  extract( 'days' from data_interacao::timestamp - data_abertura ::timestamp) as periodo,*
					FROM (
						SELECT tbl_hd_chamado.hd_chamado , tbl_hd_chamado.titulo, tbl_hd_chamado.atendente,
						tbl_hd_chamado.data as data_abertura ,
						( SELECT tbl_hd_chamado_item.data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS data_interacao
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND $cond_status BETWEEN '$data_inicial 00:00:00'
						AND '$data_final 23:59:59'
						AND $cond_1
						AND $cond_2
						AND $cond_3
						AND $cond_6
						$cond_origem
					) AS X
				) as Y
				JOIN tbl_admin on tbl_admin.admin = Y.atendente
				WHERE Y.periodo = $tipo
					ORDER BY abertura
		";

		if($login_fabrica == 90){
			$campoIBBL = " CASE
							WHEN tbl_hd_chamado.status = 'Resolvido' 

							THEN (SELECT tbl_hd_chamado_item.data
							FROM tbl_hd_chamado_item
							WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
							and tbl_hd_chamado_item.interno is not true
							AND tbl_hd_chamado.status is not null
							ORDER BY data desc LIMIT 1)::date - tbl_hd_chamado.data::date

							ELSE current_date - tbl_hd_chamado.data::date
							END AS periodo , tbl_produto.referencia, tbl_produto.descricao,  tbl_defeito_reclamado.descricao as defeito_reclamado_combo, tbl_hd_chamado_extra.serie, tbl_hd_chamado_extra.defeito_reclamado_descricao,  ";

			$sql_join_produto = "LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto and tbl_produto.fabrica_i = $login_fabrica ";
			$sql_join_defeito_reclamado = " LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado and tbl_defeito_reclamado.fabrica = $login_fabrica "; 


		}
		$sql = "
			SELECT tbl_hd_chamado.hd_chamado as callcenter, $campoIBBL 
				tbl_hd_chamado.titulo,
				tbl_hd_chamado.hd_chamado_anterior,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as abertura,
				( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY')  FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS ultima_interacao, tbl_hd_chamado.status,tbl_admin.login
				$sql_campos
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra  on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			JOIN tbl_admin on tbl_admin.admin = tbl_hd_chamado.atendente
			LEFT JOIN tbl_hd_providencia ON ( tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia)
			AND tbl_hd_providencia.fabrica = {$login_fabrica}
			LEFT JOIN tbl_motivo_contato ON ( tbl_motivo_contato.motivo_contato = tbl_hd_chamado_extra.motivo_contato)
			AND tbl_motivo_contato.fabrica = {$login_fabrica}
			$sql_join_produto
			$sql_join_defeito_reclamado
			$sql_joins
			$join_linha
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND $cond_status BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
			AND tbl_hd_chamado.posto is null
			AND $cond_1
			AND $cond_2
			AND $cond_3
			AND	$cond_4
			AND $cond_5
			AND $cond_6
			$condCl
			$cond_origem
			$cond_linha
			$cond_admin_fale_conosco";

		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
			//echo "<div class='container tc_container'>";
			echo "<table class=\"table table-striped table-bordered table-hover table-large\" style='margin: 0 auto;' >
					<thead>";
			echo "<TR class='titulo_coluna' >\n";
			echo "<th >Chamado</th>\n";
			if($login_fabrica == 115){//hd_chamado=2710901
				echo "<th>Chamado Relacionado</th>\n";
			}
			if(!in_array($login_fabrica, array(169,170))){
				echo "<th >Assunto</th>\n";
			}
			echo "<th >Abertura</th>\n";
			echo "<th >Última Interação</th>\n";
			if($login_fabrica == 90){
				echo "<th >Produto</th>\n";
				echo "<th >Nº Série</th>\n";
				echo "<th >Defeito <br> Reclamado</th>\n";
			}
			echo "<th >Status</th>\n";
			echo "<th >Atendente</th>\n";
			if(in_array($login_fabrica, array(169,170))){
				echo "<th >Classificação</th>\n";
				echo "<th >Origem</th>\n";
				echo "<th >Providência</th>\n";
				echo "<th >Providência nv. 3</th>\n";
				echo "<th >Motivo Contato</th>\n";
			}
			echo "</TR ></thehad><tbody>\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$callcenter       	 = pg_result($res,$y,callcenter);
				$titulo           	 = pg_result($res,$y,titulo);
				$abertura         	 = pg_result($res,$y,abertura);
				$ultima_interacao 	 = pg_result($res,$y,ultima_interacao);
				$login            	 = pg_result($res,$y,login);
				$status           	 = pg_result($res,$y,status);

				$origem           	 = pg_result($res,$y,origem);
				$classificacao       = pg_result($res,$y,classificacao);
				$providencia         = pg_result($res,$y,providencia);
				$providencia_descricao = pg_fetch_result($res, $y, providencia_descricao);
				$motivo_contato        = pg_fetch_result($res, $y, motivo_contato_descricao);

				if($login_fabrica == 90){
					$referencia_produto = pg_fetch_result($res, $y, referencia);
					$descricao_produto  = pg_fetch_result($res, $y, descricao);
					$defeito_reclamado_combo = pg_fetch_result($res, $y, defeito_reclamado_combo);
					$numero_serie_produto = pg_fetch_result($res, $y, serie);
					$defeito_reclamado_descricao = pg_fetch_result($res, $y, defeito_reclamado_descricao);

					if(strlen(trim($defeito_reclamado_combo))==0){
						$defeito_reclamado_combo = $defeito_reclamado_descricao;
					}
				}

				if($login_fabrica == 115){ //hd_chamado=2710901
					$hd_chamado_anterior = pg_fetch_result($res, $y, 'hd_chamado_anterior');
				}

				echo "<TR>\n";
				if($login_fabrica == 6){
					echo "<TD align='center' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' target='blank'>$callcenter</a></TD>\n";
				}else{
					echo "<TD align='center' nowrap><a href='callcenter_interativo.php?callcenter=$callcenter' target='_blank'>$callcenter</a></TD>\n";
				}

				if($login_fabrica == 115){ //hd_chamado=2710901
					echo "<TD align='center' nowrap><a href='callcenter_interativo.php?callcenter=$hd_chamado_anterior' target='_blank'>$hd_chamado_anterior</a></TD>\n";
				}
				if(!in_array($login_fabrica, array(169,170))){
					echo "<TD align='left' nowrap>$titulo</TD>\n";
				}
				echo "<TD align='center' nowrap>$abertura</TD>\n";
				echo "<TD align='center' nowrap>$ultima_interacao</TD>\n";
				if($login_fabrica == 90){
					echo "<TD align='center' nowrap>$referencia_produto - $descricao_produto</TD>\n";
					echo "<TD align='center' nowrap>$numero_serie_produto</TD>\n";
					echo "<TD align='center' nowrap>$defeito_reclamado_combo</TD>\n";	
				}
				echo "<TD align='center' nowrap>$status</TD>\n";
				echo "<TD align='left' nowrap>$login</TD>\n";
				if(in_array($login_fabrica, array(169,170))){
					echo "<TD align='center' nowrap>$classificacao</TD>\n";
					echo "<TD align='center' nowrap>$origem</TD>\n";
					echo "<TD align='left' nowrap>$providencia</TD>\n";
					echo "<TD align='left' nowrap>$providencia_descricao</TD>\n";
					echo "<TD align='left' nowrap>$motivo_contato</TD>\n";
				}
				echo "</TR >\n";
			}
			echo "</tbody></table>";
			echo "<center><strong>Quantidade de registros: ";
			echo pg_numrows($res);
			echo "</strong></center></div>";

		}else{
			echo "<h3> Nenhum resultado encontrado </h3>";
		}


	}

?>
