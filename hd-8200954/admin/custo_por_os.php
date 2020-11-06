<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios	= "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu 		= "gerencia";
$title 				= traduz("CUSTO POR OS");

## MESSAGE OF ERROR
$msg_erro = array();
$msgErrorPattern01 =  traduz("Preencha os campos obrigatórios.");
$msgErrorPattern02 =  traduz("Não foram encontrados registros no período indicado.");

if ($login_fabrica == 117) {
    include_once('carrega_macro_familia.php');
}

include "cabecalho_new.php";

$plugins = array( "dataTable" );

include("plugin_loader.php");
?>

<script type="text/javascript" charset="utf-8">
	$(function() {
		$.dataTableLoad({
			table: "#gridRelatorioPosto"
	 	});
	});
</script>

<?php
if(count($_POST)>0){
	$mes = trim($_POST['mes']);
	$ano = trim($_POST['ano']);

            $linha = trim($_POST['linha']);
            $familia = trim($_POST['familia']);
            $marca = trim($_POST['marca']);

	if (strlen($mes)==0 || strlen($ano)==0)
	{
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}
}
?>

<?php if (count($msg_erro["msg"]) > 0) {	?>
	<div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php 	}	?>

<div class="row"> <b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios"); ?> </b> </div>
<form name='frm_custo' class="form-search form-inline tc_formulario" action='<? echo $PHP_SELF ?>' method='post'>

	<div class="titulo_tabela"><?php echo traduz("Parâmetros de Pesquisa");?></div>
	<br />
		<div class='row-fluid'>
			<div class='span2'></div>

			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='Mes'><?php echo traduz("Mês"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<select name='mes' size='1'>
								<option value=''></option>
								<option value='01' <? if ($mes == '01') echo ' selected ' ?> ><?php echo traduz("Janeiro"); ?></option>
								<option value='02' <? if ($mes == '02') echo ' selected ' ?> ><?php echo traduz("Fevereiro"); ?></option>
								<option value='03' <? if ($mes == '03') echo ' selected ' ?> ><?php echo traduz("Março"); ?></option>
								<option value='04' <? if ($mes == '04') echo ' selected ' ?> ><?php echo traduz("Abril"); ?></option>
								<option value='05' <? if ($mes == '05') echo ' selected ' ?> ><?php echo traduz("Maio"); ?></option>
								<option value='06' <? if ($mes == '06') echo ' selected ' ?> ><?php echo traduz("Junho"); ?></option>
								<option value='07' <? if ($mes == '07') echo ' selected ' ?> ><?php echo traduz("Julho"); ?></option>
								<option value='08' <? if ($mes == '08') echo ' selected ' ?> ><?php echo traduz("Agosto"); ?></option>
								<option value='09' <? if ($mes == '09') echo ' selected ' ?> ><?php echo traduz("Setembro"); ?></option>
								<option value='10' <? if ($mes == '10') echo ' selected ' ?> ><?php echo traduz("Outubro"); ?></option>
								<option value='11' <? if ($mes == '11') echo ' selected ' ?> ><?php echo traduz("Novembro"); ?></option>
								<option value='12' <? if ($mes == '12') echo ' selected ' ?> ><?php echo traduz("Dezembro"); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='Ano'><?php echo traduz("Ano"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<?php
							$sql = "SELECT date_part('YEAR', min(data_abertura))as data_min from tbl_os where fabrica = {$login_fabrica}";
							$res = pg_query($con,$sql);
							$data = pg_fetch_result($res, 0, "data_min");
							?>

							<select name='ano' size='1' class='Caixa'>
								<option value=''></option>
								<?php
									for ($i = $data ; $i <= date("Y") ; $i++)
									{
										echo "<option value='$i'";
										if ($ano == $i) echo " selected";
										echo ">$i</option>";
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
        </div>

			<div class='row-fluid'>
				<div class='span2'></div>
                <? if ($login_fabrica == 117) {
                ?>
                <div class="span4">
                    <div class="control-group">
                        <label class='control-label' for='macro_linha'><?php echo traduz("Linha"); ?></label>
                        <div class='controls controls-row'>
                        <?
                            $sql = "SELECT  
                                        DISTINCT tbl_macro_linha.macro_linha, 
                                        tbl_macro_linha.descricao
                                    FROM tbl_macro_linha
                                        JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
                                    WHERE  tbl_macro_linha_fabrica.fabrica = {$login_fabrica}
                                        AND     tbl_macro_linha.ativo = TRUE
                                    ORDER BY tbl_macro_linha.descricao;";
                            $res = pg_query ($con,$sql);

                            if (pg_numrows($res) > 0) {
                                echo "<select class='frm' style='width:200px;' name='macro_linha' id='macro_linha'>\n";
                                echo "<option value=''>ESCOLHA</option>\n";

                                for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                    $aux_linha = trim(pg_fetch_result($res,$x,macro_linha));
                                    $aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

                                    echo "<option value='$aux_linha'"; if ($macro_linha == $aux_linha) echo " SELECTED "; echo ">$aux_descricao</option>\n";
                                }
                                echo "</select>\n";
                            }
                        ?>                        
                        </div>
                    </div>
                </div>
                <?
                }
                ?>
				<div class="span4">
                    <div class="control-group">
                        <label class='control-label' for='linha '><?=($login_fabrica == 117)? traduz("Macro - Família") : traduz("Linha")?></label>
                        <div class='controls controls-row'>
<?
                        if ($login_fabrica == 117) {
                        $sql_linha = "SELECT DISTINCT tbl_linha.linha,
                                               tbl_linha.nome
                                            FROM tbl_linha
                                                JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                                JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                                            WHERE tbl_macro_linha_fabrica.fabrica = $login_fabrica
                                                AND     tbl_linha.ativo = TRUE
                                            ORDER BY tbl_linha.nome;";
                    } else {
                        $sql_linha = "SELECT
                                            linha,
                                            nome
                                    FROM tbl_linha
                                    WHERE tbl_linha.fabrica = $login_fabrica
                                    ORDER BY tbl_linha.nome ";
                    }
                    $res_linha = pg_query($con, $sql_linha);
?>
                            <input type="hidden" name="linha_aux" id="linha_aux" value="<?=$_REQUEST['linha']; ?>">
                            <select name="linha" id="linha" class='span12'>
                                <option value=''><?php echo traduz("ESCOLHA"); ?></option>
<?php

        if (pg_num_rows($res_linha) > 0) {
            for ($j = 0 ; $j < pg_num_rows($res_linha) ; $j++){
                $aux_linha    = trim(pg_fetch_result($res_linha,$j,linha));
                $aux_descricao  = trim(pg_fetch_result($res_linha,$j,nome));
?>
                                <option value = "<?=$aux_linha?>" <?=($linha == $aux_linha) ? " SELECTED " : ""?>><?=$aux_descricao?></option>
<?
            }
?>
                            </select>
<?
        }
?>
                        </div>
                    </div>
                </div>
                <? if ($login_fabrica != 117) {
                ?>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='familia'><?php echo traduz("Família"); ?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<?php
									$sql = "SELECT *
											  FROM tbl_familia
											 WHERE tbl_familia.fabrica = $login_fabrica
										  ORDER BY tbl_familia.descricao;";
									$res = pg_query ($con,$sql);

									if (pg_num_rows($res) > 0)
									{
										echo "
											<select class='Caixa' style='width: 200px;' name='familia'>
												<option value=''>". traduz("ESCOLHA") ."</option>";

										for ($x = 0; $x < pg_num_rows($res); $x++) {
											$aux_familia    = trim(pg_fetch_result($res,$x,familia));
											$aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

											echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
										}
										echo "</select>\n";
									}
								?>
							</div>
						</div>
					</div>
                </div>
                <? } ?>
                <div class='span2'></div>
            </div>
<? if($login_fabrica == 1) { ?>
        <div class="container tc_container">
            <div class='row-fluid'>
                <div class='span2'></div>
<?

                $sqlMarca = "
                    SELECT  marca,
                            nome
                    FROM    tbl_marca
                    WHERE   fabrica = $login_fabrica;
                ";
                $resMarca = pg_query($con,$sqlMarca);
                $marcas = pg_fetch_all($resMarca);
?>
                    <div class='span8'>
                    <div class='control-group'>
                        <label class='control-label' for='marca'><?php echo traduz("Marca"); ?></label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <select name="marca" id="marca">
                                    <option value=""><?php echo traduz("ESCOLHA"); ?></option>
<?
                                foreach($marcas as $chave => $valor){
?>
                                    <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
                                }
?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
			</div>
		</div>

<?
            }
    if(in_array($login_fabrica, [152, 180, 181, 182])) {
?>
		<div class='row-fluid'>
			<div class='span2'></div>

			<div class='span4'>
				<div class='control-group <?=(in_array("tipo_atendimento", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='Mes'><?php echo traduz("Tipo Atendimento"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<select name='tipo_atendimento' size='1'>
								<option value=''></option>
							<?php
							$sql = "SELECT tipo_atendimento,descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica}";
							$res = pg_query($con,$sql);
							while ($resultado = pg_fetch_object($res)){
								echo "<option value='$resultado->tipo_atendimento'>$resultado->descricao</option>";
							}
							?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class='span4'></div>

        </div>
	<?php } ?>
	<br />
		<center>
			<input class="btn" type="submit" alt="Gerar Relatório" value="<?php echo traduz("Gerar Relatório");?>">
		</center>
	<br />
</form>
<br />

<?php

//fica essa parte
// tem a parte da fabrica 152 que mostra o grafico '
if (strlen($mes) > 0 AND strlen($ano) > 0)
{

	if($_POST['tipo_atendimento']){
		$tipo_atendimento = $_POST['tipo_atendimento'];
	}

	$data_inicial = $ano . "-" . $mes . "-01 00:00:00";
	$data_final   = pg_fetch_result (pg_query ($con,"SELECT ('$data_inicial'::date + INTERVAL '1 month' - INTERVAL '1 day')::date "),0,0) . " 23:59:59";



	 if (in_array($login_fabrica, array(138))) {
                        $sql = "SELECT os INTO TEMP tmp_cpo1_$login_admin
                                          FROM tbl_os_extra JOIN tbl_extrato USING(extrato)
                                         WHERE tbl_extrato.fabrica = $login_fabrica
                                           AND tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final';

                                        CREATE INDEX tmp_cpo1_os_$login_admin ON tmp_cpo1_$login_admin(os);

                                        SELECT DISTINCT tbl_os.posto,
                                                AVG (tbl_os.mao_de_obra + tbl_os.pecas) AS media ,
                                                sum(tbl_os.mao_de_obra + tbl_os.pecas) as total, COUNT(*) AS qtde
                                                INTO TEMP tmp_cpo_$login_admin
                                          FROM  tbl_os_produto
                                          JOIN tbl_os USING (os)
                                          JOIN  tbl_posto USING (posto)
                                          JOIN  tmp_cpo1_$login_admin OS ON OS.os = tbl_os.os
                                          JOIN  tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                                         WHERE  tbl_os.fabrica = $login_fabrica";

                                    if(strlen(trim($linha))>1) $sql .= " AND tbl_produto.linha      = $linha";
                                    if(strlen(trim($familia))>1) $sql .= " AND tbl_produto.familia  = $familia";
                                    if(strlen(trim($marca))>1) $sql .= " AND tbl_produto.marca      = $marca";

                                    $sql .=" GROUP BY tbl_os.posto;

                                        CREATE INDEX tmp_cpo_posto_$login_admin ON tmp_cpo_$login_admin(posto);
                                        SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto.fone, tbl_posto.email, med.media , med.total,med.qtde
                                          FROM tbl_posto
                                          JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                          JOIN tmp_cpo_$login_admin med ON tbl_posto.posto = med.posto
                                      ORDER BY media DESC";
            } else {
		$sql = "SELECT os INTO TEMP tmp_cpo1_$login_admin
		      FROM tbl_os_extra JOIN tbl_extrato USING(extrato)
		     WHERE tbl_extrato.fabrica = $login_fabrica
		       AND tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final';

		    CREATE INDEX tmp_cpo1_os_$login_admin ON tmp_cpo1_$login_admin(os);

		    SELECT 	tbl_os.posto,
		    		AVG (tbl_os.mao_de_obra + tbl_os.pecas) AS media ,
		    		sum(tbl_os.mao_de_obra + tbl_os.pecas) as total, COUNT(*) AS qtde
					INTO TEMP tmp_cpo_$login_admin
			  FROM 	tbl_os
		      JOIN  tbl_posto USING (posto)
		      JOIN  tmp_cpo1_$login_admin OS ON OS.os = tbl_os.os
		      JOIN  tbl_produto  USING(produto)
		     WHERE  tbl_os.fabrica = $login_fabrica";

        		if(strlen(trim($linha))>1) $sql .= " AND tbl_produto.linha      = $linha ";
		        if(strlen(trim($familia))>1) $sql .= " AND tbl_produto.familia  = $familia ";
			if(strlen(trim($marca))>1) $sql .= " AND tbl_produto.marca      = $marca ";
			if(strlen(trim($tipo_atendimento))>1) $sql .= " AND tbl_os.tipo_atendimento = $tipo_atendimento ";

		$sql .=" GROUP BY tbl_os.posto;

			CREATE INDEX tmp_cpo_posto_$login_admin ON tmp_cpo_$login_admin(posto);
			SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto.fone, tbl_posto.email, med.media , med.total,med.qtde
		      FROM tbl_posto
		      JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		      JOIN tmp_cpo_$login_admin med ON tbl_posto.posto = med.posto
		  ORDER BY media DESC";
		}
		$res = pg_query($con,$sql);


	if(pg_num_rows($res) > 0)
	{

		if($login_fabrica == 152)
		{
			$sql_graf = "SELECT tbl_tipo_atendimento.descricao as name,
                                                sum(
                                                        CASE WHEN tbl_os.mao_de_obra is null  THEN 0 else tbl_os.mao_de_obra END +
                                                        CASE WHEN tbl_os.qtde_km_calculada is null THEN 0 else tbl_os.qtde_km_calculada END +
                                                        CASE WHEN tbl_os.valores_adicionais is null THEN 0 else tbl_os.valores_adicionais END
                                                        ) as y
                                                FROM tbl_os
                                                INNER JOIN tbl_os_extra USING(os)
                                                INNER JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
                                                INNER JOIN tbl_tipo_atendimento on tbl_tipo_atendimento.fabrica = $login_fabrica
                                                        AND tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                                                WHERE tbl_os.fabrica = $login_fabrica
                                                AND tbl_extrato.fabrica = $login_fabrica
                                        AND tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
                                        GROUP BY tbl_tipo_atendimento.descricao ";
			$res_graf = pg_query($con,$sql_graf);
			$result = pg_fetch_all($res_graf);
			foreach ($result as $key ) {
				$key["x"] = $key["y"] ;
				$valor_total = $key["y"] + $valor_total;
			}
			foreach ($result as $key ) {
				$key["y"] =  ($valor_total - $key["y"])/$valor_total ;
			}


			if(pg_num_rows($res_graf))
			{

				?>
				<script src="js/novo_highcharts.js"></script>
				<script src="js/modules/exporting.js"></script>
				<script type="text/javascript">
				$(function () {
					$('#container-highcharts').highcharts({
						chart: {
							plotBackgroundColor: null,
							plotBorderWidth: null,
							plotShadow: false,
							type: 'pie'
						},
						title: {
							text: '<?php echo traduz("Custo total por tipo de OS"); ?>'
						},
						tooltip: {
							pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
						},
						plotOptions: {
							pie: {
								allowPointSelect: true,
								cursor: 'pointer',
								dataLabels: {
									enabled: true,
									format: '<b>{point.name}</b>: ' + <?php echo $real ?> + '{point.y}',
									style: {
										color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
									}
								}
							}
						},
						series: [{
							name: "Total",
							colorByPoint: true,
							data: [ <?php
								foreach ($result as $key ) {
									if(!empty($key)){
										echo "{ name : '".$key['name']."' ,";
										echo " y : ".number_format($key['y'], 2, '.', '')." } ,";
									}
								}
								?>
								]
							}]
						});
					});
				</script>
				<?

				echo '<div id="container-highcharts" style="width:100%; height:400px;"></div>';
			}
		}
		echo "	<table id='gridRelatorioPosto' class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_coluna'>
						<th>".	traduz("Código") ."	</th>
						<th>".	traduz("Nome") 	."	</th>
						<th>". 	traduz("Fone") 	."	</th>
						<th>". 	traduz("E-mail") ."	</th>
						<th nowrap>". traduz("Qtde Os") ."</th>
						<th nowrap>". traduz("Média " .  $real) ."</th>";

						if($login_fabrica == 40) echo "<th nowrap>". traduz("Total %", null, null [$real]) . "</th>";

		echo "		</tr>
				</thead>
				<tbody>";

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++)
		{
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr bgcolor='$cor'>";

				echo "<td align='left'>".pg_fetch_result ($res,$i,codigo_posto)."</td>";
				echo "<td align='left'>".pg_fetch_result ($res,$i,nome)."</td>";
				echo "<td align='left'>".pg_fetch_result ($res,$i,fone)."</td>";
				echo "<td align='left'>".pg_fetch_result ($res,$i,email)."</td>";
				echo "<td align='center'><center>".pg_fetch_result ($res,$i,qtde)."</center></td>";
				echo "<td align='center'><center>".number_format (pg_fetch_result ($res,$i,media),2,",",".")."</center></td>";

			if($login_fabrica == 40) {
				echo "<td align='right'>".number_format (pg_fetch_result ($res,$i,'total'),2,",",".")."</td>";
			}
			echo "</tr>";

			$total +=pg_fetch_result ($res,$i,'total');
		}
		if($login_fabrica == 40) {
			echo "	</tbody>
					<tfoot>
					<tr>
						<td colspan='100%' align='right' >Total: ".number_format ($total,2,",",".")."</td>
					</tr>
					</tfoot>";
		}
		echo "</table>";
	} else {
		echo "<div class='alert'><h4>".$msgErrorPattern02."</h4></div>";
	}
}
echo "<br /><br />";
include "rodape.php";
?>
