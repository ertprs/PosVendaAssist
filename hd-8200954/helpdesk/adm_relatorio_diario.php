<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once '../admin/funcoes.php';
if($login_fabrica<>10){
	header ("Location: index.php");
}

$array_fabricas = pg_fetch_pairs(
    $con,
    "SELECT fabrica, nome 
    FROM tbl_fabrica 
    WHERE ativo_fabrica 
    ORDER BY nome"
);

$atendentes = pg_fetch_pairs(
  $con,
  "SELECT admin, nome_completo
  FROM tbl_admin 
  WHERE tbl_admin.fabrica = 10
  AND tbl_admin.grupo_admin in (1, 2, 4, 6)
  AND ativo
  ORDER BY tbl_admin.nome_completo"
);

$TITULO = "ADM - Relatório Diário";
define('BS3', true);
include "menu.php";
?>

<link type="text/css" rel="stylesheet" media="screen" href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link rel="stylesheet" type="text/css" href="adm_relatorio_diario.css">

<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src='../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
<script src="../admin/plugins/jquery.alphanumeric.js"></script>
<script src='../admin/plugins/jquery.mask.js'></script>

<script>
    $(function() {
      $("#data_inicial").datepicker().mask("99/99/9999");
	  $("#data_final").datepicker().mask("99/99/9999");
	    $('input,select,:checkbox').change(function() {$('.btn-primary').removeAttr('disabled');});

	  setTimeout(function() {
		  location.reload();
		}, 180000);

    });
</script>
<style>
	table th {
		text-align: center;
	}
</style>
<?php

function setDate($dateVar) {
	return $dateVar ? is_date($dateVar, 'EUR', 'EUR') : date('d-m-Y');
}

$filtrado = false;
$data_atual = date('d-m-Y');

$data_inicial = setDate($_REQUEST['data_inicial']); 
$data_final = setDate($_REQUEST['data_final']);

if ($data_final < $data_inicial) {
	$data_final = $data_inicial;
}

if ($data_inicial <> $data_atual || $data_final <> $data_atual) {
	$filtrado = true;
}

$checkResolvido = isset( $_POST['resolvido'] ) ? 'checked' : '';
$checkFaturado = isset( $_POST['chamado_faturado'] ) ? 'checked' : '';
$checkAgrupar = isset( $_POST['agrupar'] ) ? 'checked' : '' ;

// ***************************************************************

$idFabrica = $_REQUEST['fabricante'];
if (!empty($idFabrica)) {
    $cond .= " AND tbl_hd_chamado.fabrica = $idFabrica ";
    $filtrado = true;
}

$atendente = $_REQUEST['atendente'];
if(!empty($atendente)){
     $cond .= " AND tbl_admin.admin = $atendente ";
     $filtrado = true;
}

$atendente_responsavel = $_REQUEST['atendente_responsavel'];
if(!empty($atendente_responsavel)){
     $cond .= " AND tbl_hd_chamado.login_admin = $atendente_responsavel ";
     $filtrado = true;
}

$resolvido = $_REQUEST['resolvido'];
if($checkResolvido) {
	$cond .= " AND tbl_hd_chamado.status='Resolvido' ";
    $filtrado = true;
}

$chamado_faturado = $_REQUEST['chamado_faturado'];
if($checkFaturado) {
	$cond .= " AND tbl_hd_chamado.tipo_chamado BETWEEN 1 AND 4 ";
    $filtrado = true;
}

if($checkAgrupar) {
	$filtrado = true;
}

if ( !$filtrado ) {
	echo '<meta http-equiv="refresh" content="180">';
}

?>

<body>
<form name='adm_relatorio_diario' method='POST' ACTION='adm_relatorio_diario.php'>

    <div class="container">
    	<div class="panel panel-default">
        	<div class="panel-heading">
        		<h3 class="panel-title">Relatório Diário</h3>
        	</div>

        	<div class="panel-body">
            	<div class="row">
              		<div class="col-md-3 col-sm-4 col-xs-6">

              			<div class="form-group">
                  			<div class="input-group date col-md-9">
                    			<input id="data_inicial" type="text" class="form-control" 
			                      name="data_inicial" 
			                      placeholder="<?=traduz("Data Inicial")?>"
			                      value="<?=$data_inicial?>">

                    			<span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                  			</div>
                		</div>
              		</div>

              		<div class="col-md-3 col-sm-4 col-xs-6">
                		<div class="form-group">
                  			<div class="input-group date col-md-9">
			                    <input id="data_final" type="text" class="form-control" 
			                           name="data_final" 
			                           placeholder="<?=traduz("Data final")?>"
			                           value="<?=$data_final?>">
			                    <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
            		      	</div>
                		</div>
              		</div>

					<div class="col-md-3 col-sm-4 col-xs-6">
						<div class="form-group">
							<div class="input-group">

					    		<select name="atendente" class="form-control">
                                <option value="" selected><?=traduz('Atendente')?></option>
								<?php
									foreach( $atendentes as $k => $v ) {
								  		$selected = ($k == $_POST['atendente'] ) ? 'selected' : '';
								  		echo "<option value=\"$k\" $selected>$v</option>" ;
									}
								?>
					    		</select>

					  		</div>
						</div>
					</div>

					<div class="col-md-3 col-sm-4 col-xs-6">
						<div class="form-group">
							<div class="input-group">
						    	<select name="atendente_responsavel" class="form-control">
                                    <option value="" selected><?=traduz('At. Responsável')?></option>
									<?php
										foreach( $atendentes as $k => $v ) {
											$selected = ($k == $_POST['atendente_responsavel'] ) ? 'selected' : '';
									  		echo "<option value=\"$k\" $selected>$v</option>" ;
										}
									?>
						    	</select>
						  	</div>
						</div>
					</div>

				</div>

				<div class="row">
              		<div class="col-md-3 col-sm-6 col-xs-6">
                		<?php echo array2select('fabricante', 'sel-fabricante', $array_fabricas, $_POST['fabricante'], ' class="form-control"', 'Selecione a Fábrica', true); ?>
              		</div>

              		<div class="col-md-1">&nbsp;</div>

			    	<div class="col-md-2">
					    <input id="checkFaturado" type="checkbox" name="chamado_faturado" <?=$checkFaturado ?> >
                        <label class="form-check-label" for="checkFaturado"><?traduz('Faturado')?></label>
					</div>

		        	<div class="col-md-2 form-group form-inline-group">
			            <input id="checkResolvido" type="checkbox" name="resolvido" <?=$checkResolvido ?> >
                        <label class="form-check-label" for="checkResolvido"><?traduz('Resolvido')?></label>
			        </div>

					<div class="col-md-2">
						<input id="checkAgrupar" type="checkbox" name="agrupar" <?=$checkAgrupar ?> >
                        <label class="form-check-label" for="checkAgrupar"><?traduz('Agrupar')?></label>
		            </div>

		            <div class="col-md-2 col-sm-3 col-xs-3">
                		<button type="submit" id="clear-form" name="submit" class="btn btn-default pull-right"><?=traduz('Pesquisar')?>
                		</button>
                	</div>

		        </div>
		    </div>
    	</div>
    </div>
</form>

<div class="corpo" align="center">
<?
if($checkAgrupar) {
	$sql = "SELECT sum(to_char(case when data_termino isnull then now() else data_termino end,'HH24:MI:SS')::time - to_char(data_inicio,'HH24:MI:SS')::time) as dia,
		TO_CHAR(previsao_termino,'dd/mm/yyyy hh24:mi') AS previsao_termino,
		tbl_hd_chamado.hd_chamado                                 ,
		substr(tbl_hd_chamado.titulo,1,25) as titulo              ,
		tbl_hd_chamado.status                                     ,
		tbl_tipo_chamado.descricao as tipo                        ,
		tbl_hd_chamado.hora_desenvolvimento                       ,
		tbl_hd_chamado.campos_adicionais::json->>'pre_hora_desenvolvimento' as pre_hora_desenvolvimento                          ,
		case when data_resolvido > previsao_termino then 'sim' else 'nao' end as passou_previsao                       ,
		tbl_admin.admin                                           ,
		tbl_admin.nome_completo                                   ,
		tbl_fabrica.nome,
		(select hora_desenvolvimento*valor_hora_franqueada - valor_desconto from tbl_hd_franquia where tbl_hd_franquia.fabrica = tbl_hd_chamado.fabrica and periodo_fim isnull limit 1) as valor_chamado
		FROM tbl_hd_chamado_atendente
		JOIN tbl_admin using(admin)
		JOIN tbl_hd_chamado using(hd_chamado)
		JOIN tbl_tipo_chamado using(tipo_chamado)
		JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
		WHERE data_inicio::DATE BETWEEN '$data_inicial' AND '$data_final'
		AND tbl_hd_chamado.titulo <> 'Atendimento interativo'
		AND tbl_admin.grupo_admin notnull 
		AND	tbl_admin.fabrica = $login_fabrica
		$cond
		group by 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12
		ORDER BY nome_completo";

	$res = pg_query($con,$sql);
	$num = 0;

	if (pg_num_rows($res) > 0) {
		?>
		<div class="container-fluid">
			<table class="table table-bordered legendas">
		<?php
		for ($i=0; $i<pg_num_rows($res); $i++){

			$hd_chamado       = trim(pg_fetch_result($res,$i,'hd_chamado'))   ;
			$titulo           = trim(pg_fetch_result($res,$i,'titulo'))       ;
			$status           = trim(pg_fetch_result($res,$i,'status'))       ;
			$admin            = trim(pg_fetch_result($res,$i,'admin'))        ;
			$nome_completo    = trim(pg_fetch_result($res,$i,'nome_completo'));
			$hora_desenvolvimento    = trim(pg_fetch_result($res,$i,'hora_desenvolvimento'));
			$pre_hora_desenvolvimento    = trim(pg_fetch_result($res,$i,'pre_hora_desenvolvimento'));
			$dia               = trim(pg_fetch_result($res,$i,'dia')) ;
			$passou_previsao  = trim(pg_fetch_result($res,$i,'passou_previsao')) ;
			$previsao_termino = trim(pg_fetch_result($res,$i,'previsao_termino')) ;
			$valor_chamado    = trim(pg_fetch_result($res,$i,'valor_chamado')) ;
			$fabrica_nome     = trim(pg_fetch_result($res,$i,'nome')) ;
			$tipo             = trim(pg_fetch_result($res,$i,'tipo')) ;

			if($admin<>$admin_anterior){
			
				 $sql2 = "select x.dia
	                        from( select sum(to_char(data_termino,'HH24:MI:SS')::time - to_char(data_inicio,'HH24:MI:SS')::time) AS dia,
	                        	tbl_hd_chamado_atendente.admin
	                            from tbl_hd_chamado_atendente
	                            JOIN tbl_hd_chamado using(hd_chamado)
	                            JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_atendente.admin
	                            WHERE tbl_hd_chamado_atendente.admin = $admin
	                            AND data_inicio::DATE between '$data_inicial' AND '$data_final'
	                            $cond
	                            GROUP BY tbl_hd_chamado_atendente.admin
	                        ) AS x";


				$res2 = pg_exec ($con,$sql2);
				$horas_trabalho  = trim(@pg_result($res2,0,0)) ;

				echo "<thead><tr class=' header tr-{$num} titulo_coluna'>";
				echo "<th width='70'>Decorrido</th>";
				echo "<th width='300'>Chamado <span class='nome_total_hora'>- $nome_completo $horas_trabalho</span></th>";
				echo "<th width='50'>Status</th>";
				echo "<th >Tipo</th>";
				echo "<th width='80'>Fábrica</th>";
				echo "<th width='80'>Horas Desenvolvimento</th>";
				echo "<th width='80'>Horas Orcamento</th>";
				echo "<th width='80'>Valor</th>";
				echo "<th>Previsão Término</th>";
				echo "</tr></thead>";
			}

			$intClass = '';
			$intervalo = calcula_hora($hora_inicio, $hora_termino);

			if ($intervalo[0] == '-') {
				// $intervalo = date('H:i', strtotime("$hora_inicio + 15 minutes"));
				$intervalo = '00:15';
				$intClass = "style='color:maroon'";
			}

			$corTD = "";
			if($status == 'Resolvido' and $passou_previsao == 'sim' and $fabrica_nome <> 'Telecontrol') {
				$corTD = "red";
			}

			echo "<tr class='Conteudo' align='left' bgcolor='$corTD'>";
			echo "<td align='center' $intClass>$dia</td>";
			echo "<td nowrap><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$hd_chamado</a> - $titulo</td>";
			echo "<td>$status</td>";
			echo "<td nowrap>$tipo</td>";
			echo "<td nowrap>$fabrica_nome </td>";
			echo "<td>$pre_hora_desenvolvimento </td>";
			echo "<td>$hora_desenvolvimento </td>";
			echo "<td>$valor_chamado </td>";
			echo "<td align='left' width='80'>$previsao_termino</td>";

			$admin_anterior = $admin;
			$num = 1;
		}

		echo "</table></div>";
	}

} else {

	$sqlInicioTrabalho = "SELECT DISTINCT ON (tbl_admin.admin)
								 tbl_admin.nome_completo, 
								 (
								 	SELECT TO_CHAR(tbl_hd_chamado_atendente.data_termino, 'mm/dd/yyyy HH24:mi')
								 	FROM tbl_hd_chamado_atendente
								 	WHERE tbl_hd_chamado_atendente.admin = tbl_admin.admin
								 	AND tbl_hd_chamado_atendente.data_termino IS NOT NULL
								 	ORDER BY tbl_hd_chamado_atendente.data_termino DESC
								 	LIMIT 1
								 ) ultima_data_termino,
								 current_timestamp - (
								 	SELECT tbl_hd_chamado_atendente.data_termino
								 	FROM tbl_hd_chamado_atendente
								 	WHERE tbl_hd_chamado_atendente.admin = tbl_admin.admin
								 	AND tbl_hd_chamado_atendente.data_termino IS NOT NULL
								 	ORDER BY tbl_hd_chamado_atendente.data_termino DESC
								 	LIMIT 1
								 ) as tempo_sem_dar_inicio
						  FROM tbl_admin
						  WHERE tbl_admin.fabrica = {$login_fabrica}
						  AND tbl_admin.grupo_admin IN (1,3,6,7,4,2,6,11,9)
						  AND tbl_admin.admin NOT IN (2466,435,1553,1097)
						  AND (
							    SELECT TO_CHAR(tbl_hd_chamado_atendente.data_inicio, 'mm/dd/yyyy HH24:mi')
							 	FROM tbl_hd_chamado_atendente
							 	WHERE tbl_hd_chamado_atendente.admin = tbl_admin.admin
							 	AND tbl_hd_chamado_atendente.data_termino IS NULL
							 	ORDER BY tbl_hd_chamado_atendente.data_termino DESC
							 	LIMIT 1
						  ) IS NULL
						  AND tbl_admin.ativo IS TRUE";
	$resInicioTrabalho = pg_query($con, $sqlInicioTrabalho);

	if (pg_num_rows($resInicioTrabalho) > 0) { ?>
		<table class='table table-bordered' style="width: 60%;">
			<tr class="titulo_coluna">
				<th colspan="100%">Não deram início de trabalho</th>
			</tr>
			<tr class="titulo_coluna">
				<th>Admin</th>
				<th>Último fim de trabalho</th>
				<th>Tempo sem dar início</th>
			</tr>
			<?php
			while ($dadosInicio = pg_fetch_assoc($resInicioTrabalho)) { ?>
				<tr>
					<td><?= $dadosInicio['nome_completo'] ?></td>
					<td><?= $dadosInicio['ultima_data_termino'] ?></td>
					<td><?= $dadosInicio['tempo_sem_dar_inicio'] ?></td>
				</tr>
			<?php
			} ?>
		</table>
	<?php
	}

	$sql = "SELECT DISTINCT 
		TO_CHAR(data_inicio,'dd/mm/yyyy') AS data_ini ,
		TO_CHAR(data_inicio,' hh24:mi') AS hora_inicio ,
		TO_CHAR(data_termino,'dd/mm/yyyy')  AS data_termino,
		TO_CHAR(data_termino,' hh24:mi') AS hora_termino,
		TO_CHAR(previsao_termino,'dd/mm/yyyy hh24:mi') AS previsao_termino,
		tbl_hd_chamado.hd_chamado,
		substr(tbl_hd_chamado.titulo,1,25) as titulo,
		tbl_hd_chamado.status,
		tbl_tipo_chamado.descricao as tipo,
		tbl_hd_chamado.hora_desenvolvimento,
		tbl_hd_chamado.campos_adicionais::json->>'pre_hora_desenvolvimento' as pre_hora_desenvolvimento                          ,
		CASE WHEN data_resolvido > previsao_termino THEN 'sim' ELSE 'nao' END AS passou_previsao,
		tbl_admin.admin,
		tbl_admin.nome_completo,
		tbl_fabrica.nome,
		data_inicio, 
		(SELECT hora_desenvolvimento * valor_hora_franqueada - valor_desconto 
			FROM tbl_hd_franquia 
			WHERE tbl_hd_franquia.fabrica = tbl_hd_chamado.fabrica 
			AND periodo_fim isnull limit 1) AS valor_chamado,
		tbl_grupo_admin.descricao as descricao_grupo
		FROM tbl_hd_chamado_atendente
		JOIN tbl_admin using(admin)
		JOIN tbl_grupo_admin ON tbl_admin.grupo_admin = tbl_grupo_admin.grupo_admin
		JOIN tbl_hd_chamado using(hd_chamado)
		JOIN tbl_tipo_chamado using(tipo_chamado)
		JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
		WHERE data_inicio::DATE BETWEEN '$data_inicial' AND '$data_final'
		AND tbl_hd_chamado.titulo <> 'Atendimento interativo'
		AND tbl_admin.grupo_admin notnull 
		AND	tbl_admin.fabrica = $login_fabrica
		$cond
		ORDER BY descricao_grupo, nome_completo, data_inicio, hora_inicio";

	$res = pg_query($con,$sql);
	$num = 0;

	if (pg_num_rows($res) > 0) {
		?>
		<div class="container-fluid">
			<table class="table table-bordered legendas">
		<?php
		for ($i=0; $i<pg_num_rows($res); $i++){

			$descricao_grupo  = trim(pg_fetch_result($res, $i, 'descricao_grupo'));
			$hora_inicio      = trim(pg_fetch_result($res,$i,'hora_inicio'))  ;
			$hora_termino     = trim(pg_fetch_result($res,$i,'hora_termino')) ;
			$hd_chamado       = trim(pg_fetch_result($res,$i,'hd_chamado'))   ;
			$titulo           = trim(pg_fetch_result($res,$i,'titulo'))       ;
			$status           = trim(pg_fetch_result($res,$i,'status'))       ;
			$admin            = trim(pg_fetch_result($res,$i,'admin'))        ;
			$nome_completo    = trim(pg_fetch_result($res,$i,'nome_completo'));
			$hora_orcamento    = trim(pg_fetch_result($res,$i,'hora_desenvolvimento'));
			$data_inicio      = trim(pg_fetch_result($res,$i,'data_ini'))  ;
			$data_termino     = trim(pg_fetch_result($res,$i,'data_termino')) ;
			$passou_previsao  = trim(pg_fetch_result($res,$i,'passou_previsao')) ;
			$previsao_termino = trim(pg_fetch_result($res,$i,'previsao_termino')) ;
			$valor_chamado    = trim(pg_fetch_result($res,$i,'valor_chamado')) ;
			$fabrica_nome     = trim(pg_fetch_result($res,$i,'nome')) ;
			$tipo             = trim(pg_fetch_result($res,$i,'tipo')) ;
			$pre_hora_desenvolvimento   = trim(pg_fetch_result($res,$i,pre_hora_desenvolvimento)) ;

			if ($descricao_grupo_anterior != $descricao_grupo) { ?>
				<thead>
					<tr>
						<th colspan="100%"></th>
					</tr>
					<tr class='titulo_coluna' style="background-color: darkred;">
						<th colspan='100%' style="text-align: center;font-size: 15px;">
							<?= $descricao_grupo ?>
						</th>
					</tr>
					<tr>
						<th colspan="100%"></th>
					</tr>
				</thead>
			<?php
			}

			if($admin<>$admin_anterior){
				$sql2 = "SELECT x.dia
					FROM (
						SELECT SUM(to_char(data_termino,'HH24:MI:SS')::time - to_char(data_inicio,'HH24:MI:SS')::time) as dia,tbl_hd_chamado_atendente.admin
						FROM tbl_hd_chamado_atendente
						JOIN tbl_hd_chamado using(hd_chamado)
						JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_atendente.admin
						WHERE tbl_hd_chamado_atendente.admin=$admin
						AND data_inicio::DATE between '$data_inicial' AND '$data_final'
						$cond
						GROUP BY tbl_hd_chamado_atendente.admin
					) x ;";

				$res2 = pg_exec ($con,$sql2);
				$horas_trabalho  = trim(@pg_result($res2,0,0)) ;

				echo "<thead><tr class=' header tr-{$num} titulo_coluna'>";
				echo "<th class='data oculta_th'>Data</th>";
				echo "<th width='50'>Início</th>";
				echo "<th width='50'>Término</th>";
				echo "<th width='70'>Decorrido</th>";
				echo "<th width='300'>Chamado <span class='nome_total_hora'>- $nome_completo $horas_trabalho</span></th>";
				echo "<th width='50'>Status</th>";
				echo "<th >Tipo</th>";
				echo "<th width='80'>Fábrica</th>";
				echo "<th width='80'>Horas Orcamento</th>";
				echo "<th width='80'>Horas Desenvolvimento</th>";
				echo "<th width='80'>Valor</th>";
				echo "<th>Previsão Término</th>";
				echo "</tr></thead>";
			}

			$intClass = '';
			$intervalo = calcula_hora($hora_inicio, $hora_termino);

			if ($intervalo[0] == '-') {
				// $intervalo = date('H:i', strtotime("$hora_inicio + 15 minutes"));
				$intervalo = '00:15';
				$intClass = "style='color:maroon'";
			}

			$corTD = "";
			if($status == 'Resolvido' and $passou_previsao =='sim' and $fabrica_nome <> 'Telecontrol') {
				$corTD = "red";
			}

			echo "<tr class='Conteudo' align='left' bgcolor='$corTD'>";
			echo "<td align='center' class='data oculta_td'>$data_inicio</td>";
			echo "<td align='center'>$hora_inicio</td>";
			echo "<td align='center'>$hora_termino</td>";
			echo "<td align='center' $intClass>$intervalo</td>";
			echo "<td nowrap><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$hd_chamado</a> - $titulo</td>";
			echo "<td>$status</td>";
			echo "<td nowrap>$tipo</td>";
			echo "<td nowrap>$fabrica_nome </td>";
			echo "<td>$hora_orcamento </td>";
			echo "<td>$pre_hora_desenvolvimento </td>";
			echo "<td>$valor_chamado </td>";
			echo "<td align='left' width='80'>$previsao_termino</td>";
			$descricao_grupo_anterior = $descricao_grupo;
			$admin_anterior = $admin;
			$num = 1;
		}

		echo "</table></div>";
	}
}
include 'rodape.php';


?>
</body>
