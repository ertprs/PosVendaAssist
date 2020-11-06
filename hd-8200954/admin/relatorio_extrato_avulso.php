<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
require_once '../helpdesk/mlg_funciones.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$tDocs = new TDocs($con,$login_fabrica,'avulso');

if(strlen($_POST['lancamento']) > 0){
	$lancamento = $_POST['lancamento'];
	if (isset($novaTelaOs)) {
		$sql = " INSERT INTO tbl_extrato_lancamento_excluido (
					extrato_lancamento,
					posto             ,
					fabrica           ,
					lancamento        ,
					descricao         ,
					debito_credito    ,
					historico         ,
					valor             ,
					competencia_futura,
					data_lancamento   ,
					data_exclusao     ,
					admin
				) SELECT extrato_lancamento,
						 posto             ,
						 fabrica           ,
						 lancamento        ,
						 case when descricao is null  then ' ' when descricao is not null then descricao end,
						 debito_credito    ,
						 historico         ,
						 valor             ,
						 competencia_futura,
						 data_lancamento   ,
						 current_timestamp ,
						 $login_admin
				FROM tbl_extrato_lancamento
				WHERE extrato_lancamento = $lancamento;

				UPDATE tbl_extrato_lancamento set fabrica=0 WHERE extrato_lancamento= $lancamento;";
	} else {
		$sql = " INSERT INTO tbl_extrato_lancamento_excluido (
					extrato_lancamento,
					posto             ,
					fabrica           ,
					lancamento        ,
					descricao         ,
					debito_credito    ,
					historico         ,
					valor             ,
					competencia_futura,
					data_lancamento   ,
					data_exclusao     ,
					admin
				) SELECT extrato_lancamento,
						 posto             ,
						 fabrica           ,
						 lancamento        ,
						 case when descricao is null  then ' ' when descricao is not null then descricao end,
						 debito_credito    ,
						 historico         ,
						 valor             ,
						 competencia_futura,
						 data_lancamento   ,
						 current_timestamp ,
						 $login_admin
				FROM tbl_extrato_lancamento
				WHERE extrato_lancamento = $lancamento;

				UPDATE tbl_extrato_lancamento set fabrica=0 WHERE extrato_lancamento= $lancamento;

				SELECT fn_calcula_extrato($login_fabrica,extrato)
				FROM tbl_extrato_lancamento
				WHERE extrato_lancamento= $lancamento
				AND   extrato IS NOT NULL;
				";
	}
	$res = pg_query($con,$sql);
	echo (strlen(pg_errormessage($con)) == 0) ? "OK" : "Erro";
	exit;
}

if(isset($_POST['consultar'])) {

	$data_inicial      = $_POST["data_inicial"];
    $data_final        = $_POST["data_final"];
    $tipo 		       = $_POST['tipo'];
    $codigo_posto      = $_POST["codigo_posto"];
    $tipo_lancamento   = $_POST["tipo_lancamento"];
    $debito_credito    = $_POST["debito_credito"];
    $tipo              = $_POST['tipo'];
    $marca             = $_POST['marca'];

    $data_inicial_pg = is_date($data_inicial);
    $data_final_pg   = is_date($data_final);

    if (empty($data_inicial_pg) || empty($data_final_pg)) {
    	$msg_erro["msg"][]    = "Preencha a data corretamente";
		$msg_erro["campos"][] = "data";
    }

    $cond = "";

	if(!empty($tipo)){
		$cond .= " AND tbl_extrato_lancamento.lancamento = '$tipo' ";
	}

	if(!empty($codigo_posto)){
		$cond .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	if($tipo_lancamento == 'lancamento_excluido') {
		$excluido = "_excluido" ;
		$sql_join = "";
		$sql_valor = ",TO_CHAR(tbl_extrato_lancamento_excluido.data_exclusao,'DD/MM/YY') AS data_exclusao ";
	}else{
		$conta_garantia = "tbl_extrato_lancamento$excluido.conta_garantia,";
		$sql_join = " LEFT JOIN tbl_extrato USING (extrato) LEFT JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato ";
		$sql_valor = ",tbl_extrato.extrato, TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,					tbl_extrato.protocolo ";
	}

	$cond .= ($debito_credito == 'D') ? " AND tbl_lancamento.debito_credito ='D' " : (($debito_credito == 'C') ? " AND tbl_lancamento.debito_credito ='C'" : "");

	if($login_fabrica == 1) {
		$sql_valor = " ,(select obs from tbl_os_sedex where tbl_os_sedex.os_sedex = tbl_extrato_lancamento.os_sedex limit 1) as obs ";
	}

	if(!empty($tipo)) {
		$cond .= " AND tbl_extrato_lancamento$excluido.lancamento = '$tipo' ";

		if($tipo == '000') {
			$sql_valor = " , x.obs ";
			$join = " JOIN tbl_os_sedex x ON tbl_extrato_lancamento.os_sedex = x.os_sedex ";
			$cond .= " AND tbl_extrato_lancamento.lancamento = 42 AND x.obs like 'Débito gerado por troca de produto na OS%' ";
		}
	}

	$cond .= ($tipo_lancamento == 'sem_extrato') ? " AND tbl_extrato_lancamento.extrato IS NULL " : (($tipo_lancamento == 'com_extrato')? " AND NOT (tbl_extrato_lancamento.extrato IS NULL) ":"");

	$tipo_lancamento = (!in_array($tipo_lancamento,array('sem_extrato','lancamento_excluido'))) ? "" : $tipo_lancamento;

	 if(!empty($_POST['marca'])){

	 	$cond .= " AND tbl_extrato_lancamento.marca = '$marca' " ;

	 }

	 if($login_fabrica == 104){
	 	$left_marca = " LEFT JOIN tbl_marca ON tbl_extrato_lancamento.marca = tbl_marca.marca AND tbl_marca.fabrica = $login_fabrica ";
	 	$campo_marca = ", tbl_marca.nome AS marca_nome ";
	 }

	 if ($login_fabrica == 142 && !empty($_POST["estado"])) {
	 	$estado = strtoupper($_POST["estado"]);

	 	$whereEstado = "AND UPPER(tbl_posto_fabrica.contato_estado) = '{$estado}'";
	 }

	if (count($msg_erro) == 0) {

		//$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;

		$tipo_data = " tbl_extrato_lancamento$excluido.data_lancamento ";
		

		$cond_join = " LEFT JOIN tbl_admin         ON tbl_admin.admin = tbl_extrato_lancamento$excluido.admin ";
		if($login_fabrica == 3) {

			if ($_POST['status_lancamento'] == 'aprovados') {

				$cond_status = "AND tbl_extrato_lancamento.campos_adicionais->>'data_aprovacao' IS NOT NULL";

			} else if ($_POST['status_lancamento'] == "pendentes") {

				$cond_status = "AND tbl_extrato_lancamento.campos_adicionais->>'aprovacao'::text IS NOT NULL
								AND tbl_extrato_lancamento.campos_adicionais->>'data_aprovacao' IS NULL";

			}

			$cond_join = " JOIN tbl_admin         ON tbl_admin.admin = tbl_extrato_lancamento$excluido.admin ";
			$cond_admin	   = " AND NOT (tbl_extrato_lancamento$excluido.admin is null ) ";

		}

		if($login_fabrica == 20){
			$campos_bosch = ", tbl_admin.nome_completo";
			$join_bosch = "left JOIN tbl_admin         ON tbl_admin.admin = tbl_extrato_lancamento$excluido.admin ";
		}
		if($login_fabrica == 1 and $tipo_lancamento <> 'sem_extrato'){
			$tipo_data = " tbl_extrato_financeiro.data_envio ";

			$cond .= " AND ($tipo_data BETWEEN '$data_inicial_pg 00:00:00' AND '$data_final_pg 23:59:59' )";

			$sql = "SELECT tbl_posto_fabrica.codigo_posto                                         ,
					tbl_posto.nome                                                                ,
					tbl_extrato.extrato                                                           ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao                ,
					tbl_extrato.protocolo                                                         ,
					tbl_extrato_lancamento.valor                                                  ,
					tbl_extrato_lancamento.descricao                                              ,
					tbl_extrato_lancamento.extrato_lancamento                                     ,
					tbl_lancamento.debito_credito                                         ,
					tbl_extrato_lancamento.os_sedex                                               ,
					TO_CHAR(tbl_extrato_lancamento.data_lancamento,'DD/MM/YY') AS data_lancamento ,
					tbl_extrato_financeiro.data_envio
					$sql_valor
				FROM tbl_extrato_lancamento
				LEFT JOIN tbl_extrato_financeiro USING (extrato)
				LEFT JOIN tbl_extrato USING (extrato)
				JOIN tbl_lancamento USING (lancamento)
				JOIN tbl_posto ON tbl_extrato_lancamento.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_extrato_lancamento.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
				$join
				WHERE tbl_extrato_lancamento.fabrica = $login_fabrica
				$cond
				ORDER BY tbl_posto_fabrica.codigo_posto";
		}else{

			$sql = "SELECT tbl_posto_fabrica.codigo_posto                           ,
					tbl_posto.nome                                                  ,
					tbl_admin.login                                                 ,
					tbl_extrato_lancamento$excluido.valor                           ,
					tbl_extrato_lancamento$excluido.descricao                       ,
					tbl_extrato_lancamento$excluido.historico                       ,
					tbl_extrato_lancamento$excluido.extrato_lancamento              ,
					$conta_garantia
					tbl_lancamento.debito_credito                  					,
					tbl_lancamento.descricao as descricao_lancamento				,
					tbl_extrato_lancamento.campos_adicionais 						,
					TO_CHAR(tbl_extrato_lancamento$excluido.data_lancamento,'DD/MM/YY') AS data_lancamento,
					TO_CHAR(tbl_extrato_lancamento$excluido.competencia_futura,'MM/YY') AS competencia_futura
					$sql_valor
					$campo_marca
					$campos_bosch
				FROM tbl_extrato_lancamento$excluido
				$sql_join
				JOIN tbl_lancamento USING (lancamento)
				JOIN tbl_posto         ON tbl_extrato_lancamento$excluido.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_extrato_lancamento$excluido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				$cond_join
				$left_marca
				WHERE tbl_extrato_lancamento$excluido.fabrica = $login_fabrica
				AND $tipo_data BETWEEN '$data_inicial_pg 00:00:00' AND '$data_final_pg 23:59:59'
				$cond
				$cond_admin
				{$whereEstado}
				{$cond_status}
				ORDER BY tbl_posto_fabrica.codigo_posto";
		}

		$res_consulta = pg_query ($con,$sql);

		if ($_POST['gerar_excel']) {

			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_extrato_avulso-{$login_fabrica}-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");

			$thead = "<table border='1'>
						<thead>
							<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Extrato</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF 
								!important;'>Dt. Extrato</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor</th>";

			if (in_array($login_fabrica, [3])) {

				$thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo de Lançamento</th>";

			}


				 $thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição</th>";

			if (in_array($login_fabrica, [3])) {

				$thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Competência Futura</th>
						   <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Admin</th>
						   <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status do Lançamento</th>
						   <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Aprovador</th>";

			}

				 $thead	.= "
							</tr>
						</thead>
						<tbody>";

			fwrite($file, $thead);
			
			$totalDeExtratos = 0;
			
			while ($extratoAvulso = pg_fetch_object($res_consulta)) {
				
				$totalDeExtratos++;

				$posto       = $extratoAvulso->codigo_posto . " - " . $extratoAvulso->nome; 

				$campos_adicionais = json_decode($extratoAvulso->campos_adicionais, true);

				$admin_aprovacao = $campos_adicionais['admin'];
				$data_aprovacao  = $campos_adicionais['data_aprovacao'];
				$aprovacao       = $campos_adicionais['aprovacao'];
				$descricao 		 = (!empty($extratoAvulso->descricao)) ? $extratoAvulso->descricao : $extratoAvulso->historico;

				if (isset($campos_adicionais['aprovacao'])) {
					
					$status_aprovacao = (!empty($data_aprovacao)) ? "Aprovado" : "Pendente";

				} else {

					$status_aprovacao = "";

				}

				$sqlNomeAdm = "SELECT nome_completo
							   FROM tbl_admin
							   WHERE admin = {$admin_aprovacao}";
				$resNomeAdm = pg_query($con, $sqlNomeAdm);

				$nome_admin_aprova = substr(pg_fetch_result($resNomeAdm, 0, 'nome_completo'), 0, 21);

				$tbody .= "<tr>
							<td>{$extratoAvulso->data_lancamento}</td>
					    	<td>{$posto}</td>
							<td>{$extratoAvulso->extrato}</td>
							<td>{$extratoAvulso->data_geracao}</td>
							<td>".number_format($extratoAvulso->valor,2,",",".")."</td>";

				if (in_array($login_fabrica, [3])) {

					$tbody .= "<td>{$extratoAvulso->descricao_lancamento}</td>";

				}

				$tbody .= "<td>{$descricao}</td>";

				if (in_array($login_fabrica, [3])) {

					$tbody .= "<td>{$extratoAvulso->competencia_futura}</td>
							   <td>{$extratoAvulso->login}</td>
							   <td>{$status_aprovacao}</td>
							   <td>{$nome_admin_aprova}</td>";

				}

				$tbody .= "
						   </tr>";

			}

			fwrite($file, $tbody);

			$colspan_t = 6;

			fwrite($file, "
						<tr>
							<th colspan='".$colspan_t."' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de {$totalDeExtratos} registros</th>
						</tr>
					</tbody>
				</table>
			");
			
			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}

				
			
		exit;
	}




	}	


	flush();
}


	

$layout_menu = "financeiro";
$title = traduz("RELATÓRIO DE EXTRATOS AVULSOS LANÇADOS");

include "cabecalho_new.php";

$plugins = array( 
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include "plugin_loader.php";

?>

<script language='javascript'>

function excluirLancamento(lancamento,id){
    var extrato_lancamento = $("#extrato_"+lancamento);
    if (confirm('<?=traduz("Deseja realmente excluir este lançamento?")?>', '<?=traduz("Excluindo Lançamento")?>') == true) {
        $.ajax({
            type: "POST",
            url: "<?=$PHP_SELF?>",
            data: {lancamento: lancamento},
            beforeSend:function(){
                $(extrato_lancamento).html("<img src='../imagens/carregar_os.gif' width='8' border='0'>");
            },
            complete: function(){

            	$("#linha_"+lancamento).html("<td colspan='100%' class='alert alert-danger tac'><h5><?=traduz("Exclusão realizada com sucesso")?></h5></td>");
            	$("#linha_historico_"+lancamento).remove();

            }
        });
    }else{
        return false;
    }
}
</script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		 $("#data_inicial").datepicker().mask("99/99/9999");
		 $("#data_final").datepicker().mask("99/99/9999");

		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>

<div class="alert alert-warning"><?=traduz('Serão mostrados somente os extratos que foram enviados para o financeiro.')?></div>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div id="dados" class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>			
<form name="frm_relatorio" METHOD="POST" ACTION="<?= $PHP_SELF ?>" class='form-search form-inline'>
	<div class="tc_formulario">
		<div class='titulo_tabela'><?=traduz('Parâmetros de Pesquisa')?></div>
		<br />	
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
							<div class='controls controls-row'>
								<div class='span4'>
									<h5 class='asteristico'>*</h5>
									<input class='span12' type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<?= $data_inicial ?>" >
								</div>
							</div>	
					</div>	
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<input class='span12' type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<?= $data_final ?>">
							</div>	
						</div>
					</div>	
				</div>		
			<div class='span2'></div>			
		</div>	
		<div class='row-fluid'>	
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group'>	
						<div class='controls controls-row'>
							<label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
							<div class='controls controls-row'>
								<div class="span7 input-append">
									<input class='span12' type="text" name="codigo_posto" id="codigo_posto" value="<?= $codigo_posto ?>" class="Caixa">
									<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
								</div>
							</div>		
						</div>	
					</div>
				</div>
				<div class='span4'>
					<div class='control-group'>
						<div class='controls controls-row'>
							<label class='control-label' for='nome_posto'><?=traduz('Nome do Posto')?></label>
							<div class='controls controls-row'>
								<div class='span12 input-append'>
									<input  class='span12' type="text" name="posto_nome" id="descricao_posto"  value="<?= $posto_nome?>" class="Caixa">
									<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
								</div>
							</div>	
						</div>
					</div>	
				</div>		
			<div class='span2'></div>			
		</div>
		<div class='row-fluid'>				
			<div class='span2'></div>
				<div class='span4'>
					<div class='span6 input-append'>
						<label class='control-label' for='Tipo'><?=traduz('Tipo')?></label>
						<div class="row-fluid">
							<select name='tipo'>
							<option></option>
							<?php
							$sql = "SELECT DISTINCT lancamento ,descricao
									FROM tbl_lancamento
									WHERE fabrica = $login_fabrica
									AND   ativo";
							$res = pg_query($con,$sql);

							if(pg_num_rows($res)>0){
								for($i=0;pg_num_rows($res)>$i;$i++){
									$extrato_lancamento = pg_fetch_result($res,$i,lancamento);
									$descricao = pg_fetch_result($res,$i,descricao);

									?>

									<option value='<?= $extrato_lancamento ?>' <?= ($_POST['tipo'] == $extrato_lancamento) ? "selected" : "" ?>><?= $descricao ?></option>
								<?php
								}

								if($login_fabrica == 1) { ?>

									<option  <?= ($_POST['tipo'] == "000") ? "selected" : "" ?> value='000'><?=traduz('Débito gerado por troca de produto na OS')?></option>

								<?php	
								}
							}
								?>
							</select>
						</div>	
					</div>
				</div>	
				<div class='span4'>
				<?php
				if ($login_fabrica == 142) {
				?>
								<label class='control-label' for='Estado'>Estado:</label>
								<div class='row-fluid'>
									<select class='controls span8' name='estado'>
										<option></option>
										<?php
										$arrayEstados = array("AC" => "Acre",			"AL" => "Alagoas",			"AM" => "Amazonas",
																 "AP" => "Amapá",			"BA" => "Bahia",			"CE" => "Ceará",
																 "DF" => "Distrito Federal","ES" => "Espírito Santo",	"GO" => "Goiás",
																 "MA" => "Maranhão",		"MG" => "Minas Gerais",		"MS" => "Mato Grosso do Sul",
																 "MT" => "Mato Grosso",		"PA" => "Pará",				"PB" => "Paraíba",
																 "PE" => "Pernambuco",		"PI" => "Piauí",			"PR" => "Paraná",
																 "RJ" => "Rio de Janeiro",	"RN" => "Rio Grande do Norte","RO"=>"Rondônia",
																 "RR" => "Roraima",			"RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
																 "SE" => "Sergipe",			"SP" => "São Paulo",		"TO" => "Tocantins");

										foreach ($arrayEstados as $sigla => $nome) {
											$selected = ($sigla == $_POST["estado"]) ? "selected" : "";
										?>

											<option value='<?= $sigla ?>' <?= $selected ?>><?= $nome ?></option>
										<?php	
										}
										?>
									</select>
								</div>			
				<?php
				}
				?>
				<?php if($login_fabrica == 104){ ?>
					<?php
						$sqlM = "SELECT tbl_marca.marca,tbl_marca.nome FROM tbl_marca WHERE tbl_marca.fabrica = $login_fabrica AND tbl_marca.nome in('DWT','OVD') ORDER BY tbl_marca.nome";
						$resM = pg_query($con,$sqlM);

						if(pg_num_rows($resM) > 0){
						?>
							<label for='com_extrato'>Grupo</label>
								<div class='controls controls-row'>
									<select name='marca' class='controls'>
										<option value=''>Todos Grupos</option>
										<?php
											for($i = 0; $i < pg_num_rows($resM); $i++){
												$marca = pg_result($resM,$i,'marca');
												$nome_marca = pg_result($resM,$i,'nome');
												$selected = ($marca == $marca_aux) ? "SELECTED" : "";

										?>
												<option value='<?= $marca ?>' <?= $selected ?>><?= $nome_marca ?></option>
										<?php		
											}
										?>	
									</select>
								</div>

						<?php } ?>

				<?php } ?>
			</div>	
			<div class='span2'></div>		
		</div>
	</div>
	<div class="tc_formulario">
		<?php if($login_fabrica == 3) { ?>
	          	<div class="titulo_tabela">Lançamento</div>
	          	<br />
	          	<div class='row-fluid'>
	              		<div class='span1'></div>
	              		<div class='span3'>
							<label for='com_extrato' class="radio">
						        <input class='frm' type='radio' name='tipo_lancamento' value='com_extrato' id='com_extrato' <?= ($_POST["tipo_lancamento"] == "com_extrato") ? "checked" : "" ?> >Lançamento em extrato
						    </label>
						</div> 
						<div class='span4'>   
						    <label for='sem_extrato' class="radio"'>
						        <input class='frm' type='radio' name='tipo_lancamento' value='sem_extrato' id='sem_extrato' <?= ($_POST["tipo_lancamento"] == "sem_extrato") ? "checked" : "" ?>  >Lançamento em nenhum extrato
						    </label>
					    </div>
					    <div class='span3'>
						    <label for='lancamento_excluido' class="radio">
						        <input type='radio' name='tipo_lancamento' value='lancamento_excluido' <?= ($_POST["tipo_lancamento"] == "lancamento_excluido") ? "checked" : "" ?> id='lancamento_excluido'>Lançamento excluído
						    </label>
						</div>    
				    <div class='span2'></div>
				</div>
				<div class="titulo_tabela">Status do Lançamento</div>
	          	<br />
				<div class='row-fluid'>
	              		<div class='span2'></div>
	              		<div class='span3'>
							<label for='todos' class="radio">
						        <input class='frm' type='radio' name='status_lancamento' value='todos' id='status_lancamento' <?= ($_POST["status_lancamento"] == "todos" || !isset($_POST['status_lancamento'])) ? "checked" : "" ?> >Todos
						    </label>
						</div>
						<div class='span3'>
						    <label for='aprovados' class="radio">
						        <input class='frm' type='radio' name='status_lancamento' value='aprovados' id='status_lancamento' <?= ($_POST["status_lancamento"] == "aprovados") ? "checked" : "" ?> >Aprovados
						    </label>
					    </div>
					    <div class='span3'>
						    <label for='pendentes' class="radio">
						        <input class='frm' type='radio' name='status_lancamento' value='pendentes' id='pendentes' <?= ($_POST["status_lancamento"] == "pendentes") ? "checked" : "" ?> >Pendentes
						    </label>
						</div>    
				    <div class='span2'></div>
				</div>
		<?php } else { ?>
				<div class="titulo_tabela"><?=traduz('Extrato')?></div>
				<br />
				<div class='row-fluid'>
					<div class='span2'></div>
						<div class='span2'>   
						    <label for='todos' class="radio">
						        <input type='radio' name='tipo_lancamento' value='todos' <?= ($_POST["tipo_lancamento"] == "com_extrato" || $_POST["tipo_lancamento"] == "com_extrato") ? '' : 'checked' ?> id='todos'><?=traduz('Todos')?>
						    </label>
						</div> 
						<div class='span3'>
	                        <label for='com_extrato' class="radio">
						        <input class='frm' type='radio' name='tipo_lancamento' value='com_extrato' id='com_extrato' <?= ($_POST["tipo_lancamento"] == "com_extrato") ? 'checked' : '' ?>><?=traduz('Avulsos em extrato')?>
						    </label>
						</div>    
					    <div class='span3'>
	                        <label for='sem_extrato' class="radio">
						        <input type='radio' name='tipo_lancamento' value='sem_extrato' id='sem_extrato' <?= ($_POST["tipo_lancamento"] == "sem_extrato") ? 'checked' : '' ?>><?=traduz('Avulsos sem extrato')?>
						    </label>
						</div>   
	                <div class='span3'></div>
	            </div>    
		<?php } ?>
	</div>
	<div class="tc_formulario">
		<div class="titulo_tabela"><?=traduz('Débito ou Crédito')?></div>
			<br />
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span3'>    
				    <label for='qualquer' class="radio">
				        <input type='radio' name='debito_credito' value='todos' <?= ($_POST["debito_credito"] == "C" || $_POST["debito_credito"] == "C") ? '' : 'checked' ?> id='qualquer'><?=traduz('Todos')?>
				    </label>
				</div>  						
				<div class='span3'>
				    <label for='credito' class="radio">
				        <input class='frm' type='radio' name='debito_credito' value='C' id='credito' <?= ($_POST["debito_credito"] == "C") ? 'checked' : '' ?>><?=traduz('Crédito')?>
				    </label>
				</div>
				<div class='span3'>    
				    <label for='debito' class="radio">
				        <input type='radio' name='debito_credito' value='D' id='debito' <?= ($_POST["debito_credito"] == "D") ? 'checked' : '' ?>><?=traduz('Débito')?>
				    </label>
				</div>  
                <div class='span2'></div>
            </div>    
			<p>
				<input type="submit" class="btn" name="consultar" value='<?=traduz("Pesquisa")?>' id='consultar'>
			</p>
			&nbsp;&nbsp;
		</div>			
</form>
<?php 
	if (pg_num_rows($res_consulta) > 0) {
			$total = 0;
?>
			<br />
			<?= ($tipo_lancamento=='lancamento_excluido') ? "Lançamento Excluído" : (($tipo_lancamento=='sem_extrato') ? "Lançamento em nenhum extrato" : ""); ?>

			</b>
			<table  cellpadding='2' cellspacing='0' align='center' width='700px' >
				<tr>
					<td><BR></td>
				</tr>
				<tr>
					<td bgcolor='#ffcccc' width='20' align='left'>&nbsp;</td>
					<td style='font-size: 10px; text-align: left;' align='left'><?=traduz('Débito')?></td>
				</tr>
			</table>
			<br><br>
		</div>
			<table class='table table-bordered table-large' id="tabela_extrato">
				<thead>
					<TR class='titulo_coluna' height='25'>
						<?= ($login_fabrica ==3 || isset($novaTelaOs)) ? "<th>".traduz("Ação")."</th>" : "" ?>
						<th class="date_column"><?=traduz('Data')?></th>
						<th><?=traduz('Posto')?></th>
						<?= (strlen($tipo_lancamento) == 0) ? "<th>".traduz("Extrato")."</th>" : "" ?>
						<?= (strlen($tipo_lancamento) == 0) ? "<th class='date_column'>".traduz("Dt. Extrato")."</th>" : "" ?>
						<?php
						if($login_fabrica == 104){
						?>	
							<th><?=traduz('Marca')?></th>
						<?php
						}
						?>
							<th class="money_column"><?=traduz('Valor')?></th>
						<?php

						if (in_array($login_fabrica, [3])) { ?>
							<th><?=traduz('Tipo do Lançamento')?></th>
						<?php
						}

						if ($login_fabrica == 158) {
						?>	
							<th><?=traduz('Tipo do Extrato')?></th>
						<?php	
						}
						?>
						<?= ($tipo_lancamento =='lancamento_excluido') ? "<th>Data Exclusão</th>" : "" ?>
							<th><?=traduz('Descrição')?></th>
						<?php
						if ($login_fabrica == 3) { ?>
							<th><?=traduz('Competência')?><br><?=traduz('Futura')?></th>
							<th><?=traduz('Admin')?></th>
							<th><?=traduz('Status do')?> <br /><?=traduz('Lançamento')?></th>
							<th><?=traduz('Aprovador')?></th>
						<?php
						}
						if($login_fabrica == 20){?>
							<th><?=traduz('Data Lançamento')?></th>
							<th><?=traduz('Administrador')?></th>
						<?php }

						if (in_array($login_fabrica, [20])) { ?>
							<th><?=traduz('Anexos')?></th>
						<?php
						}
						?>
						</TR>
					</thead>	
					<?php
						for ($i=0; $i<pg_num_rows($res_consulta); $i++){ 

								$codigo_posto    		= trim(pg_fetch_result($res_consulta,$i,'codigo_posto'))   ;
								$nome            		= trim(pg_fetch_result($res_consulta,$i,'nome'))           ;
								$descricao       		= trim(pg_fetch_result($res_consulta,$i,'descricao'))      ;
								$valor           		= trim(pg_fetch_result($res_consulta,$i,'valor'))          ;
								$debito_credito  		= trim(pg_fetch_result($res_consulta,$i,'debito_credito')) ;
								$descricao_lancamento 	= trim(pg_fetch_result($res_consulta,$i,'descricao_lancamento')) ;
								$campos_adicionais      = json_decode(trim(pg_fetch_result($res_consulta,$i, 'campos_adicionais')), true);

								if($tipo_lancamento <> 'lancamento_excluido') {
									$extrato         = trim(pg_fetch_result($res_consulta,$i,'extrato'))        ;
									$data_geracao    = trim(pg_fetch_result($res_consulta,$i,'data_geracao'))   ;
									$protocolo       = trim(pg_fetch_result($res_consulta,$i,'protocolo'))      ;
								}else{
									$data_exclusao   = trim(pg_fetch_result($res_consulta,$i,'data_exclusao'))  ;
								}
								if($login_fabrica == 20){
									$nome_completo    = trim(pg_fetch_result($res_consulta,$i,'nome_completo'));
								}
									$data_lancamento    = trim(pg_fetch_result($res_consulta,$i,'data_lancamento'));
									$extrato_lancamento = trim(pg_fetch_result($res_consulta,$i,'extrato_lancamento'));
								if($login_fabrica == 3){
									$admin_login        = trim(pg_fetch_result($res_consulta,$i,'login'))      ;
									$historico          = trim(pg_fetch_result($res_consulta,$i,'historico'))  ;
									$competencia_futura = trim(pg_fetch_result($res_consulta,$i,'competencia_futura'));

									$admin_aprovacao = $campos_adicionais['admin'];
									$data_aprovacao  = $campos_adicionais['data_aprovacao'];


									if (isset($campos_adicionais['aprovacao'])) {
										
										$status_aprovacao = (!empty($data_aprovacao)) ? "Aprovado" : "Pendente";

									} else {

										$status_aprovacao = "";

									}

									$sqlNomeAdm = "SELECT nome_completo
												   FROM tbl_admin
												   WHERE admin = {$admin_aprovacao}";
									$resNomeAdm = pg_query($con, $sqlNomeAdm);

									$nome_admin_aprova = pg_fetch_result($resNomeAdm, 0, 'nome_completo');

								}

								if ($login_fabrica == 158) {
									$conta_garantia = pg_fetch_result($res_consulta, $i, "conta_garantia");
									$conta_garantia = ($conta_garantia == "t") ? "Garantia" : "Fora de Garantia";
								}

								$descricao = (!empty($descricao )) ? $descricao : trim(pg_fetch_result($res_consulta,$i,'historico'));

								if ($login_fabrica == 1) {
									$obs = pg_fetch_result($res_consulta,$i,'obs');
								}

								if (strpos($obs,'Débito gerado por troca de produto na OS') !==false) {
									if($tipo !='000') {
										continue;
									}
								}

								if ($login_fabrica == 104){
									$marca_nome = trim(pg_fetch_result($res_consulta,$i,'marca_nome'));
								}

								if ($login_fabrica == 1) { 
									$extrato = $protocolo;
								}	

								if ($debito_credito =='D') {
									$style = " style='background-color:#ffcccc;' ";
								}else{
									$style = "";
								}
						?>

						<TR id="linha_<?= $extrato_lancamento?>" class='Conteudo' <?= $style ?>>

							<?php
							if($login_fabrica == 3) {
							?>
							<?=  ($tipo_lancamento <>'lancamento_excluido') ? "<TD id='extrato_$extrato_lancamento'>
								<button class='btn btn-danger' onclick=\" excluirLancamento('$extrato_lancamento','extrato_$extrato_lancamento')\" >".traduz("Excluir")."</button>
								</TD>" : "<td></td>" ?>
							<?php
							}

							if (isset($novaTelaOs)) {
								if (empty($extrato)) {
									?>
									<?= ($tipo_lancamento <>'lancamento_excluido') ? "<TD id='extrato_$extrato_lancamento'><button class='btn btn-danger' onclick=\"excluirLancamento('$extrato_lancamento','extrato_$extrato_lancamento')\" >".traduz("Excluir")."</button></TD>" : "<td>&nbsp;</td>" ?>

								<?php
								} else {
								?>		
									<td>&nbsp;</td>
							<?php		
								}
							}
							?>

							<TD align='center' class="tac"><?= $data_lancamento ?></TD>
							<TD align='left'><?= $codigo_posto ?> - <?= $nome ?></TD>
							<?php
							if(strlen($tipo_lancamento) == 0) {
							?>	
								<TD align='center' class="tac">
									<?= (strlen($extrato) > 0) ? "$extrato" : "-" ?>
								</TD>
								<TD align='center' class="tac">
									<?= (strlen($data_geracao) > 0) ? "$data_geracao": "-" ?>
								</TD>
							<?php
							}

							if($login_fabrica == 104){ ?>
								<TD align='center'>
									<?= $marca_nome ?>
								</TD>
							<?php	
							}
							?>
							<TD>
								<?= number_format($valor,2,",",".") ?>
						    </TD>
						    <?php
						    if (in_array($login_fabrica, [3])) { ?>
								<td><?= $descricao_lancamento ?></td>
							<?php
							}
							if ($login_fabrica == 158) {
							?>	
								<TD>
									<?= $conta_garantia ?>
								</TD>
							<?php	
							}
							?>
							<?= ($tipo_lancamento =='lancamento_excluido') ? "<TD align='center'>$data_exclusao</TD>" : "" ?>
							<?= ($tipo == '000') ? "<TD align='right'>$obs</TD>":"<TD align='right'>$descricao</TD>" ?>
							<?php if($login_fabrica == 20){ echo "<td> $data_lancamento</td><td> $nome_completo</td>";}?>
							
							<?php
							if($login_fabrica == 3){
							?>	
								<TD align='center'><?= $competencia_futura ?></TD>
								<TD align='right'><?= $admin_login ?></TD>
								<td class="tac"><?= $status_aprovacao ?></td>
								<td><?= substr($nome_admin_aprova, 0, 22) ?></td>
							<?php	
							}

							if (in_array($login_fabrica, [20])) { ?>
								<td class="tac">
									<?php
						                $tDocs->setContext('avulso');
					                    $info = $tDocs->getDocumentsByRef($extrato_lancamento)->attachListInfo;

					                     if (count($info) > 0) {

					                        foreach ($info as $k => $vAnexo) {
					                            $info[$k]["posicao"] = $pos++;
					                        }

					                    }

					                    $imagemAnexo = "imagens/imagem_upload.png";
					                    $linkAnexo   = "#";
					                    $tdocs_id   = "";


					                    if (!empty($extrato_lancamento)) {
					                        if (count($info) > 0) {

					                            foreach ($info as $k => $vAnexo) {

					                                $linkAnexo   = $vAnexo["link"];
					                                $tdocs_id = $vAnexo["tdocs_id"]; ?>
					                                
					                                <a href="<?= $linkAnexo ?>" target="_blank" class="btn btn-primary">
					                                	Anexo
					                                </a>

					                                <?php
					                            }
					                        } 
					                    }
							            ?>
							            <br />
								</td>
							<?php
								$qtdeAnexos++;

							} ?>
						</TR>
						<?php
						if($login_fabrica == 3){ ?>
							<TR id="linha_historico_<?= $extrato_lancamento ?>"  bgcolor='<?= $cor ?>'>
								<td colspan='100%' style='text-align:left;padding-left:10px;padding-right:10px' align='top'>
									<I>Histórico: <?= $historico ?></I>
								</TD>
							</TR>
						<?php	
						}

						$total = $valor + $total;

					}

			if($login_fabrica == 74 or $login_fabrica == 1){
                if(strlen($tipo_lancamento) == 0) {
                    $colspan = "4";
                }else{
                    $colspan = "2";
                }
			}else{
                if(strlen($tipo_lancamento) == 0) {
                    $colspan = "5";
                }else{
                    $colspan = "3";
                }
            }
			?>
				<tfoot>
					<tr>
						<td colspan='<?= $colspan ?>' align='center'>
							<strong><?=traduz('VALOR TOTAL DE AVULSO')?></strong>
						</td>
						<td align='right'>
							<b><?= $real . number_format($total,2,",",".") ?></b>
						</td><td>
						</td>
					</tr>
				</tfoot>
			</TABLE>
			<?

			$data_inicial = trim($_POST["data_inicial"]);
			$data_final   = trim($_POST["data_final"]);
			$linha        = trim($_POST["linha"]);
			$estado       = trim($_POST["estado"]);
			$criterio     = trim($_POST["criterio"]);

	} else if (isset($_POST["consultar"])) {
	?>
		<div class="alert alert-warning">
			<h4><?=traduz('Nenhum resultado encontrado entre')?> <?= $data_inicial ?> e <?= $data_final ?>
			<?= ($tipo_lancamento=='lancamento_excluido') ? "Lançamento Excluído" : (($tipo_lancamento=='sem_extrato') ? "Lançamento em nenhum extrato" : "") ?>
			</h4>
		</div>	
	<?php }

	if ((in_array($login_fabrica, [3, 160]) or $replica_einhell) && isset($_POST)) {

	
		$jsonPOST = excelPostToJson($_REQUEST);
		$jsonPOST = utf8_decode($jsonPOST);
		
		?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>

	<?php } 

	if ($login_fabrica != 3) { ?>

			<script>

	        var tds = $('#tabela_extrato').find(".titulo_coluna");

	        var colunas = [];

	        $(tds).find("th").each(function(){
	            if ($(this).attr("class") == "date_column") {
	                colunas.push({"sType":"date"});
	            } else {
	                colunas.push(null);
	            }
	        });
	        
			$.dataTableLoad({ 
				table: "#tabela_extrato",
				aoColumns:colunas 
			});
		</script>

	<?php 
	}

include "rodape.php" ?>
