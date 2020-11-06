<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';

$makita = 42;
$elgin = 117;
$layout_menu = "tecnica";
$title = "TREINAMENTO";

if ($login_fabrica == 117) {
	include_once('carrega_macro_familia.php');
}

include 'cabecalho_new.php';
$plugins = array(
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect",
	"select2",
	"datetimepickerbs2"
);

include("plugin_loader.php");

$treinamento = $_GET["treinamento"];
if((strlen($treinamento)>0) && ($login_fabrica != $makita and $login_fabrica != 117)){
	if(in_array($login_fabrica, array(148,169,170,193))){
		$campos_adicionais = "parametros_adicionais,
		TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao,
		qtde_participante, ";
	}

	if (in_array($login_fabrica, array(169,170,175,193)))
	{
		if (!in_array($login_fabrica, array(175))){
			$campos_adicionais  .= " tbl_treinamento.vagas_min, ";	
			$campos_adicionais  .= " tbl_treinamento.tipo_posto, ";
			$campo_time = "HH24:MI:SS";
		}

		if (in_array($login_fabrica, array(175))){
			$campos_adicionais  .= " tbl_treinamento_produto.produto AS produtos, ";	
		}
		$campos_adicionais  .= " tbl_treinamento_produto.linha AS linhas,
								(SELECT tbl_promotor_treinamento.nome
                                    FROM tbl_promotor_treinamento
                                    JOIN tbl_treinamento_instrutor ON tbl_treinamento_instrutor.instrutor_treinamento = tbl_promotor_treinamento.promotor_treinamento
                                    AND tbl_treinamento_instrutor.treinamento = tbl_treinamento.treinamento
                                    ORDER BY tbl_treinamento_instrutor.data_input DESC LIMIT 1
                                ) AS palestrante,
								(SELECT tbl_promotor_treinamento.email
                                    FROM tbl_promotor_treinamento
                                    JOIN tbl_treinamento_instrutor ON tbl_treinamento_instrutor.instrutor_treinamento = tbl_promotor_treinamento.promotor_treinamento
                                    AND tbl_treinamento_instrutor.treinamento = tbl_treinamento.treinamento
                                    ORDER BY tbl_treinamento_instrutor.data_input DESC LIMIT 1
                                ) AS email_palestrante,";
		$join_linha          = "LEFT JOIN tbl_treinamento_produto on tbl_treinamento_produto.treinamento = tbl_treinamento.treinamento";
	}

	if (in_array($login_fabrica, array(175))){
		$campos_adicionais  .= " tbl_treinamento.validade_treinamento, tbl_treinamento.categoria, ";
		$campos_adicionais  .= " TO_CHAR(tbl_treinamento.inicio_inscricao,'DD/MM/YYYY') AS inicio_inscricao, ";
		$campos_adicionais  .= " TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao, ";
		$campos_adicionais  .= " tbl_treinamento.data_finalizado, ";

		/*if (in_array($login_fabrica, [193])) {
			$join_treinamento_tipo = " LEFT JOIN tbl_treinamento_tipo ON tbl_treinamento_tipo.treinamento_tipo = tbl_treinamento.treinamento_tipo ";
			$campos_adicionais    .= " tbl_treinamento_tipo.nome AS treinamento_nome, ";
		}*/
	}

	if (in_array($login_fabrica, [1])) {
		$campos_adicionais = "tbl_treinamento.parametros_adicionais ,";
	}

	if (in_array($login_fabrica, array(148,169,170,175,193))){
		$campo_time = "HH24:MI:SS"; 
	}

	$sql = "SELECT tbl_treinamento.treinamento                                        ,
				tbl_treinamento.titulo                                                ,
				tbl_treinamento.descricao                                             ,
				tbl_treinamento.ativo                                                 ,
				tbl_treinamento.vagas                                                 ,
				tbl_treinamento.vaga_posto                                            ,
				tbl_treinamento.linha                                                 ,
				tbl_treinamento.familia                                               ,
				tbl_treinamento.estado 												  ,
				TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY $campo_time') AS data_inicio      ,
				TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY $campo_time')    AS data_fim         ,
				tbl_treinamento.adicional,
				tbl_treinamento.local,
				tbl_treinamento.cidade,				
				tbl_treinamento.treinamento_tipo,
				$campos_adicionais
				tbl_treinamento.visivel_portal
		FROM tbl_treinamento
			{$join_linha}
			{$join_treinamento_tipo}
		WHERE tbl_treinamento.treinamento = $treinamento
		AND tbl_treinamento.fabrica = $login_fabrica";		

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		$rows        = pg_numrows($res);

		for ($x=0; $x < $rows; $x++){
			$treinamento  = trim(pg_result($res,$x,treinamento));
			$titulo       = trim(utf8_encode(pg_result($res,$x,titulo)));
			if ($login_fabrica == 1) {
				$titulo       = trim(pg_result($res,$x,titulo));
			}
			$descricao    = trim(pg_result($res,$x,descricao));
			$ativo        = trim(pg_result($res,$x,ativo))      ;
			$data_inicial = trim(pg_result($res,$x,data_inicio));
			$data_final   = trim(pg_result($res,$x,data_fim))   ;
			$familia      = trim(pg_result($res,$x,familia))    ;
			$qtde         = trim(pg_result($res,$x,vagas))      ;
			$vaga_posto   = trim(pg_result($res,$x,vaga_posto))      ;
			$adicional    = trim(utf8_encode(pg_result($res,$x,adicional)));
			$local    = trim(utf8_encode(pg_result($res,$x,local)));
			$cidade    = trim(pg_result($res,$x,cidade))      ;
			$visivel_portal    = trim(pg_result($res,$x,visivel_portal));
			$palestrante    = trim(pg_result($res,$x,palestrante));
			$email_palestrante    = trim(pg_result($res,$x,email_palestrante));

			if(in_array($login_fabrica, array(169,170,175,193))){
				if (!in_array($login_fabrica, array(175)))
				{	
					$parametros_adicionais = json_decode(trim(pg_result($res,$x,parametros_adicionais)));
					$carga_horaria         = $parametros_adicionais->carga_horaria;
					$tipo_posto            = $parametros_adicionais->tipo_posto;
					// trazer os postos dos treinamentos antigos, pois a regra foi modificada
					if (empty($tipo_posto)){
						$tipo_posto        = trim(pg_result($res,$x,tipo_posto));
					}
					$qtde_participante = pg_result($res,$x,qtde_participante);
					$estado_participante = pg_result($res,$x,estado);
					$qtde_min            = pg_result($res,$x,vagas_min);
				}
				$treinamento_tipo = trim(pg_result($res,$x,treinamento_tipo));
				$linha_vazio = pg_result($res,$x,linhas);
				if (empty($linha_vazio)){
					$linha  = trim(pg_result($res,$x,linha));
					$arr_linha[] = $linha;
				}else{
					$linha  = trim(pg_result($res,$x,linhas));
					$arr_linha[] = $linha;
				}

				if (in_array($login_fabrica, array(175))){
					$finalizado = pg_result($res,$x,data_finalizado); 
					$produtos   = pg_result($res,$x,produtos);
					$arr_produto[] = $produtos;
				}
			}elseif ($login_fabrica == 1){
				$parametros_adicionais = json_decode(trim(pg_result($res,$x,parametros_adicionais)));
				$linha                 = $parametros_adicionais->linha;
				$marca                 = $parametros_adicionais->marca;
				$familia               = $parametros_adicionais->familia;
				$tipo_posto            = $parametros_adicionais->tipo_posto;
				$categoria_posto       = $parametros_adicionais->categoria_posto;
			} else {
				$linha       = trim(pg_result($res,$x,linha))      ;
			}

			if (in_array($login_fabrica, array(148,169,170,193))) {
				$prazo_inscricao = trim(pg_result($res,$x,prazo_inscricao));								
			}

			if (in_array($login_fabrica, array(175))){
				$validade_treinamento = trim(pg_result($res,$x,validade_treinamento));
				$treinamento_por      = trim(pg_result($res,$x,categoria));

				$inicio_inscricao      = trim(pg_result($res,$x,inicio_inscricao));	
				$prazo_inscricao       = trim(pg_result($res,$x,prazo_inscricao));	
/*
				if (in_array($login_fabrica, [193])) {
					$treinamento_tipo      = strtolower(trim(pg_result($res,$x,treinamento_tipo)));
					$treinamento_tipo_nome = strtolower(trim(pg_result($res,$x,treinamento_nome)));
				}*/
			}

			if($cidade != ""){

				$sql = "SELECT tbl_cidade.cidade,tbl_cidade.nome,tbl_cidade.estado, tbl_estado.nome AS estado_nome
									from tbl_cidade
									JOIN tbl_estado ON tbl_estado.estado = tbl_cidade.estado
									where cidade = $cidade;";

				$res_cidade = pg_exec($con,$sql);
				if(pg_num_rows($res_cidade) > 0){
					$cidade = pg_result($res_cidade,0,cidade);
					$nome_cidade = pg_result($res_cidade,0,nome);
					$estado_cidade = pg_result($res_cidade,0,estado);
					$estado_nome = pg_result($res_cidade,0,estado_nome);

					$sqlR = "SELECT estados_regiao from tbl_regiao where fabrica = $login_fabrica and ativo is true";
					$resR = pg_query($con,$sqlR);
					for ($i=0; $i < $resR ; $i++) {
						if(strstr(pg_fetch_result($resR,$i,'estados_regiao'), $estado_cidade)){
							$estados_regiao_combo = pg_fetch_result($resR,$i,estados_regiao);
						}
					}
				}else{
					$cidade = "";
					$nome_cidade = "";
					$estado_cidade = "";
				}
			}


			if(in_array($login_fabrica, array(169,170,193))){
				$sql = "SELECT tbl_promotor_treinamento.promotor_treinamento AS instrutor_treinamento
						FROM tbl_treinamento 
						JOIN tbl_treinamento_instrutor ON tbl_treinamento_instrutor.treinamento = tbl_treinamento.treinamento
						JOIN tbl_promotor_treinamento ON tbl_promotor_treinamento.promotor_treinamento = tbl_treinamento_instrutor.instrutor_treinamento
						WHERE tbl_treinamento.treinamento = {$treinamento}
						ORDER BY tbl_treinamento_instrutor.data_input DESC LIMIT 1";
				//$sql = "SELECT ti.instrutor_treinamento FROM tbl_treinamento_instrutor ti JOIN tbl_promotor_treinamento pt ON ti.instrutor_treinamento = pt.promotor_treinamento WHERE ti.treinamento = $treinamento ORDER BY ti.instrutor_treinamento DESC LIMIT 1";

				$res_instrutor = pg_query($con, $sql);
				$res_instrutor = pg_fetch_all($res_instrutor);
				$res_instrutor = $res_instrutor[0];


				$sql = "SELECT tp.treinamento_promotor, tp.promotor_treinamento, pt.nome, pt.email FROM tbl_treinamento_promotor tp JOIN tbl_promotor_treinamento pt ON tp.promotor_treinamento = pt.promotor_treinamento WHERE tp.treinamento = $treinamento";
				$res_promotores = pg_query($con, $sql);
				$res_promotores = pg_fetch_all($res_promotores);

				$sql = "SELECT tc.treinamento_cidade, c.cidade, c.nome, c.estado FROM tbl_treinamento_cidade tc JOIN tbl_cidade c ON tc.cidade = c.cidade WHERE tc.treinamento = $treinamento";
				$res_cidade = pg_query($con, $sql);
				$res_cidade = pg_fetch_all($res_cidade);

				$sql = "SELECT tc.treinamento_cidade, tc.estado FROM tbl_treinamento_cidade tc WHERE tc.treinamento = $treinamento";
				$res_estados = pg_query($con, $sql);
				$res_estados = pg_fetch_all($res_estados);
			}
		}
	}
}elseif((strlen($treinamento)>0)&&($login_fabrica == $makita or $login_fabrica == 117)){
	$sql = "SELECT tbl_treinamento.treinamento                                    ,
			tbl_treinamento.titulo                                                ,
			tbl_treinamento.descricao                                             ,
			tbl_treinamento.ativo                                                 ,
			tbl_treinamento.vagas                                                 ,
			tbl_treinamento.linha                                                 ,
			tbl_treinamento.local                                                 ,
			TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio      ,
			TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim         ,
			tbl_treinamento.adicional											  ,
			tbl_treinamento.treinamento_tipo 									  ,
			tbl_treinamento.visivel_portal,
			tbl_treinamento.cidade,
			tbl_treinamento.estado,
			tbl_treinamento.palestrante
	FROM tbl_treinamento
	WHERE treinamento = $treinamento
	AND fabrica = $login_fabrica";

	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$treinamento  = trim(pg_result($res,0,'treinamento'));
		$titulo       = trim(utf8_encode(pg_result($res,0,'titulo')))     ;
		$titulo = str_replace('"','',$titulo);
		$descricao    = trim(pg_result($res,0,'descricao'))  ;
		$ativo        = trim(pg_result($res,0,'ativo'))      ;
		$data_inicial = trim(pg_result($res,0,'data_inicio'));
		$data_final   = trim(pg_result($res,0,'data_fim'))   ;
		$linha        = trim(pg_result($res,0,'linha'))      ;
		$qtde         = trim(pg_result($res,0,'vagas'))      ;
		$adicional    = trim(utf8_encode(pg_result($res,0,'adicional')))  ;
		$familia      = trim(pg_result($res,0,'treinamento_tipo' ))    ;
		$local      = trim(utf8_encode(pg_result($res,0,'local' )))    ;
		$palestrante    = trim(pg_result($res,0,'palestrante'));
		$visivel_portal      = trim(pg_result($res,0,'visivel_portal' ))    ;
		$cidade    = trim(pg_result($res,0,'cidade'));

	}

	if ($login_fabrica == 117) {
		$sql = "SELECT distinct
					tbl_macro_linha.macro_linha
				FROM tbl_macro_linha
				JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
				WHERE tbl_macro_linha.ativo IS TRUE
				AND fabrica = {$login_fabrica}
				AND ativo = 't'
				AND tbl_macro_linha_fabrica.linha = {$linha}";
		$res = pg_query($con, $sql);
		$linha_elgin = pg_fetch_result($res, 0, "macro_linha");

	if($cidade != ""){

			$sql = "SELECT tbl_cidade.cidade,tbl_cidade.nome,tbl_cidade.estado, tbl_estado.nome AS estado_nome
								from tbl_cidade
								JOIN tbl_estado ON tbl_estado.estado = tbl_cidade.estado
								where cidade = $cidade;";

			$res = pg_exec($con,$sql);
			if(pg_num_rows($res) > 0){
				$cidade = pg_result($res,0,cidade);
				$nome_cidade = pg_result($res,0,nome);
				$estado_cidade = pg_result($res,0,estado);
				$estado_nome = pg_result($res,0,estado_nome);

				$sqlR = "SELECT estados_regiao from tbl_regiao where fabrica = $login_fabrica and ativo is true";
				$resR = pg_query($con,$sqlR);
				for ($i=0; $i < $resR ; $i++) {
					if(strstr(pg_fetch_result($resR,$i,'estados_regiao'), $estado_cidade)){
						$estados_regiao_combo = pg_fetch_result($resR,$i,estados_regiao);
					}
				}
			}else{
				$cidade = "";
				$nome_cidade = "";
				$estado_cidade = "";
			}
		}
	}
}

if ($fabricaFileUploadTreinamento) {
    if (!empty($treinamento)) {
        $tempUniqueId = $treinamento;
        $anexoNoHash = null;
    } else if (strlen(getValue("anexo_chave")) > 0) {
        $tempUniqueId = getValue("anexo_chave");
        $anexoNoHash = true;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}

/*HD - 6261912*/
$editar_campos          = false;
$readonly               = "";
$disabled               = "";

if (in_array($login_fabrica, [169,170,193]) && strlen($treinamento) > 0) {
	$aux_sql = "SELECT treinamento FROM tbl_treinamento WHERE fabrica = $login_fabrica AND data_finalizado IS NOT NULL AND treinamento = $treinamento";
	$aux_res = pg_query($con, $aux_sql);
	$aux_val = pg_fetch_result($aux_res, 0, 'treinamento');

	if (strlen($aux_val) > 0) {
		include_once "../plugins/fileuploader/TdocsMirror.php";

		$editar_campos          = true;
		$readonly               = " readonly='readonly' ";
		$disabled               = " disabled='disabled' ";
	}
}
?>

<?php if (in_array($login_fabrica, [169,170,193])){ ?>
<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
<?php } ?>

<style type="text/css">
	#modal-cadastra-tecnico {
       width: 80%;
       margin-left: -40%;
       z-index: 1;
    }
    .modal-backdrop, .modal-backdrop.fade.in{
    	z-index: 0;
    }

    .error-multiple{
	    background-color: white !important;
	    border: 1px solid #B94A48 !important;
	    border-radius: 4px !important;
	    cursor: text !important;
    }

</style>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando' width='150'></div>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<div id="erro" class='alert alert-error' style="display:none;"><h4></h4></div>
<div id="success" class='alert alert-success' style="display:none;"><h4></h4></div>
<FORM name="frm_relatorio" id="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">

<input type='hidden' name='treinamento' id='treinamento' value='<?=$treinamento?>'>
<input type='hidden' name='titulo_antigo' id='titulo_antigo' value='<?=$titulo?>'>
<input type='hidden' name='linha_antiga' id='linha_antiga' value='<?=$linha?>'>
<input type='hidden' name='familia_antiga' id='familia_antiga' value='<?=$familia?>'>
<input type='hidden' name='data_inicial_antiga' id='data_inicial_antiga' value='<?=$data_inicial?>'>
<input type='hidden' name='data_final_antiga' id='data_final_antiga' value='<?=$data_final?>'>

<div class="titulo_tabela"><?=traduz('Cadastro de Treinamento')?></div>
<br>
   	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span2">
			<?php if (in_array($login_fabrica, array(148,169,170,175,193))){ ?>
				<div id="data_inicial_picker" class="input-append date control-group">
			    	<label class='control-label'><?=traduz('Data Inicial')?></label>
			    	<h5 class='asteristico'>*</h5><br/>
			    	<input style="width: 130px;" id="data_inicial" data-format="dd/MM/yyyy hh:mm:ss" name="data_inicial" maxlength="10" type="text" value="<?=$data_inicial?>"></input>
				    <span class="add-on">
				      <i data-time-icon="icon-time" data-date-icon="icon-calendar">
				      </i>
				    </span>
			  	</div>
			<?php }else{ ?>
				<div class="control-group" id="data_inicial_campo">
					<label class='control-label'><?=traduz('Data Inicial')?></label>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_inicial" id='data_inicial'  maxlength="10" class='span12' value="<?=$data_inicial?>">
				</div>
			<?php } ?>
		</div>
		<div class='span2'></div>
		<div class="span2">
			<?php if (in_array($login_fabrica, array(148,169,170,175,193))){ ?>
				<div id="data_final_picker" class="input-append date control-group">
			    	<label class='control-label'><?=traduz('Data Final')?></label>
			    	<h5 class='asteristico'>*</h5><br/>
			    	<input style="width: 130px;" id="data_inicial" data-format="dd/MM/yyyy hh:mm:ss" name="data_final" maxlength="10" type="text" value="<?=$data_final?>"></input>
				    <span class="add-on">
				      <i data-time-icon="icon-time" data-date-icon="icon-calendar">
				      </i>
				    </span>
			  	</div>
			<?php }else{ ?>
				<div class="control-group" id="data_final_campo">
					<label class='control-label'><?=traduz('Data Final')?></label>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>">
				</div>
			<?php } ?>


		</div>
		<div class='span2'></div>
	</div>
	<?php
		if(in_array($login_fabrica, array(169,170,193))){
			?>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span4">
					<div class="control-group" id="data_final_campo">
						<label class='control-label'><?=traduz('Prazo Final de Inscrições')?></label>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="prazo_inscricao" id="prazo_inscricao" size="12" maxlength="10" class='span10' value="<?=$prazo_inscricao?>">
					</div>
				</div>
			</div>
			<?php
		}
	?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span8">
			<div class="control-group" id="tema">
				<label class='control-label'><?=traduz('Tema')?></label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="titulo" id='titulo' size="60" maxlength="70" class='span12' value="<?=(strlen($titulo) > 0) ? utf8_decode($titulo) : ""?>">
				</div>
			</div>
		</div>
		<div class="span2"></div>
   	</div>
	<?php
	if ($login_fabrica == 117) {
	?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span4">
			<label class='control-label'><?=traduz('Palestrante')?></label>
			<?php
			$value_palestrante = '';
			if (!empty($palestrante)) {
				$value_palestrante = (mb_check_encoding($palestrante)) ? utf8_decode($palestrante) : $palestrante;
			}
			?>
			<input type="text" name="palestrante" id='palestrante' size="60" maxlength="60" class='span12' value="<?= $value_palestrante ?>">
		</div>
	</div>
	<?php
	}
	?>

	<?php if (!in_array($login_fabrica, [1])) { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span4">
			<?php if ($login_fabrica == 117) { ?>
	                <div class="control-group" id="macro_linha_campo">
		                <label class='control-label'><?=traduz('Linha')?></label>
		                <h5 class='asteristico'>*</h5>
		                <div class="<? echo $controlgrup?>">
		                <?php
							$sql = "SELECT distinct
										tbl_macro_linha.descricao,
										tbl_macro_linha.macro_linha
									FROM tbl_macro_linha
									JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
									WHERE tbl_macro_linha.ativo IS TRUE
									AND fabrica = {$login_fabrica}
									AND ativo = 't'
									ORDER BY tbl_macro_linha.descricao";
							$res = pg_query($con, $sql);
							echo "<select name='macro_linha' id='macro_linha' class='span12'>\n";
		                    echo "<option value=''>ESCOLHA</option>\n";
		                    for ($count=0; $count < pg_num_rows($res); $count++) {
		                    	$macro_linha = pg_fetch_result($res, $count, "macro_linha");
		                    	$selected = ($linha_elgin == $macro_linha) ? 'selected' : '';

		                    	echo "<option value='{$macro_linha}' {$selected}>".pg_fetch_result($res, $count, "descricao")."</option>\n";
		                    }
		                    echo "</select>";
		                ?>
		                </div>
	                </div>
	        	</div>

	            	<div class="span4">
	                    <div class="control-group" id="linha_campo">
	                    <label class='control-label'><?=traduz('Macro - Família')?></label>
	                    <h5 class='asteristico'>*</h5>
	                    <div class="<? echo $controlgrup?>">
	                    <input type="hidden" name="linha_aux" id="linha_aux" value="<?=$linha; ?>">
	                    <select name='linha' id='linha' class='span12'>
	                    	<option value=''><?=traduz('ESCOLHA')?></option>
	                    </select>
	                    </div>
	                </div>

	            <?php
	            } else {
	            	if ($login_fabrica != 175){
	            		if (in_array($login_fabrica, array(169,170,193))){ ?>
	            			<div class="control-group" id="linha_campo">
								<label class='control-label'><?=traduz('Linha')?></label>
								<h5 class='asteristico asteristico_linha'>*</h5>
								<div class="controls controls-row">
										<?

										$sql = "SELECT  DISTINCT
													tbl_linha.linha,
													tbl_linha.nome,
													tbl_linha.fabrica,
													tbl_treinamento_produto.linha
												FROM    tbl_linha
													LEFT JOIN tbl_treinamento_produto ON tbl_treinamento_produto.linha = tbl_linha.linha
												WHERE   tbl_linha.fabrica = {$login_fabrica}
												ORDER BY tbl_linha.nome;";
										$res = pg_exec ($con,$sql);

										if (pg_numrows($res) > 0) {
											echo "<select name='linha[]' id='linha' class='span12' multiple='multiple'>\n";
											//echo "<option value=''>ESCOLHA</option>\n";

											for ($x = 0 ; $x < pg_numrows($res) ; $x++){
												$aux_linha = trim(pg_result($res,$x,linha));
												$aux_nome  = trim(pg_result($res,$x,nome));

												echo "<option value='$aux_linha'";
												if (in_array($aux_linha, $arr_linha)){
													echo " SELECTED ";
													$mostraMsgLinha = "<br> da LINHA $aux_nome";
												}
												echo ">$aux_nome</option>\n";
											}
											echo "</select>\n";
										}
										?>
								</div>
							</div>
	            	<?php }else{ ?>
						<div class="control-group" id="linha_campo">
							<label class='control-label'><?=traduz('Linha')?></label>
							<h5 class='asteristico'>*</h5>
							<div class="<? echo $controlgrup?>">
									<?

									$sql = "SELECT  *
											FROM    tbl_linha
											WHERE   tbl_linha.fabrica = $login_fabrica
											ORDER BY tbl_linha.nome;";
									$res = pg_exec ($con,$sql);

									if (pg_numrows($res) > 0) {
										echo "<select name='linha' id='linha' class='span12'>\n";
										echo "<option value=''>ESCOLHA</option>\n";

										for ($x = 0 ; $x < pg_numrows($res) ; $x++){
											$aux_linha = trim(pg_result($res,$x,linha));
											$aux_nome  = trim(pg_result($res,$x,nome));

											echo "<option value='$aux_linha'";
											if ($linha == $aux_linha){
												echo " SELECTED ";
												$mostraMsgLinha = "<br> da LINHA $aux_nome";
											}
											echo ">$aux_nome</option>\n";
										}
										echo "</select>\n";
									}
									?>
							</div>
						</div>
					<?php }
					}
					if (in_array($login_fabrica, array(175))){ 
					?>
						
							<div class="control-group" >
								<?php
								$sql = "SELECT 
										produto, 
										referencia, 
										descricao 
									FROM tbl_produto 
									WHERE fabrica_i = {$login_fabrica} 
										AND ativo IS TRUE;";
								$res = pg_exec ($con,$sql);

								if (pg_numrows($res) > 0) { ?>
									<label class='control-label'><?=traduz('Produto')?></label>
									<h5 class='asteristico asteristico_produto'>*</h5>
									<div id="div_produto">
										<select name='produto[]' id='produto' class='span12' multiple="multiple">
											<?php
											for ($x = 0 ; $x < pg_numrows($res) ; $x++){
												$id_produto    = trim(pg_result($res,$x,produto));
												$referencia_produto = trim(pg_result($res,$x,referencia));
												$descricao_produto  = trim(pg_result($res,$x,descricao));

												echo "<option value='$id_produto'";
												if (in_array($id_produto, $arr_produto)){
													echo " SELECTED ";
												}
												echo ">$referencia_produto - $descricao_produto</option>\n";
											}?>
										</select>
									</div>
								<?php
								} ?>
							</div>
						
					<?php
					}
	            }
	            ?>
			</div>
			
			<div class="span4">
				<div class="<? echo $controlgrup?>">
					<?php
					if (!in_array($login_fabrica, array(117))) {
						if($login_fabrica != $makita && !in_array($login_fabrica, array(169,170,175,193))){
							$sql = "SELECT  *
									FROM    tbl_familia
									WHERE   tbl_familia.fabrica = $login_fabrica
									ORDER BY tbl_familia.descricao;";

							$res = pg_exec ($con,$sql);

							if (pg_numrows($res) > 0) {
								echo "<label class='control-label'>Família </label>";
								echo "<select name='familia' id='familia' class='span12'>\n";
								echo "<option value=''>".traduz('ESCOLHA')."</option>\n";

								for ($x = 0 ; $x < pg_numrows($res) ; $x++){
									$aux_familia = trim(pg_result($res,$x,familia));
									$aux_nome  = trim(pg_result($res,$x,descricao));

									echo "<option value='$aux_familia'";
									if ($familia == $aux_familia){
										echo " SELECTED ";
										$mostraMsgLinha = "<br> da FAMILIA $aux_nome";
									}
									echo ">$aux_nome</option>\n";
								}
								echo "</select>\n";
							}
						}else{
							if(in_array($login_fabrica, array(169,170,175,193))){
								$sql = "SELECT * FROM tbl_treinamento_tipo WHERE fabrica = {$login_fabrica} ORDER BY nome";
								$res = pg_query($con,$sql);
								if (pg_numrows($res) > 0) {
									?>
									<div class="control-group" id="div_treinamento_tipo">
										<label class="control-label"><?=traduz('Tipo de Treinamento')?></label>
										<?php if (in_array($login_fabrica, array(175))){ ?> 
											<h5 class='asteristico'>*</h5><br/>
										<?php } ?>
										<div class="" style='padding-right: 20px;'>
											<select name="treinamento_tipo" id="treinamento_tipo" class="span12">
											<option value=''><?=traduz('ESCOLHA')?></option>;
												<?php
												while ($tipo = pg_fetch_array($res)){
													if($treinamento_tipo != $tipo['treinamento_tipo']){
														echo "<option value={$tipo['treinamento_tipo']}>{$tipo['nome']}</option>\n";
													}else{
														echo "<option value={$tipo['treinamento_tipo']} selected='selected' >{$tipo['nome']}</option>\n";
													}
												}
												?>
											</select>
										</div>
									</div>
									<?php
								}
							}else{
								$sql = "SELECT * FROM tbl_treinamento_tipo WHERE fabrica = {$login_fabrica} ORDER BY nome";
								$res = pg_query($con,$sql);
								if (pg_numrows($res) > 0) {
									echo "<label class='control-label'>Família</label>";
									echo "<div>";
									echo "<select name='familia' id='familia' class='frm'>\n";
									echo "<option value=''>".traduz('ESCOLHA')."</option>\n";


									while ($linha = pg_fetch_array($res)){
										if($familia != $linha['treinamento_tipo']){
											echo "<option value={$linha['treinamento_tipo']}>{$linha['nome']}</option>\n";
										}else{
											echo "<option value={$linha['treinamento_tipo']} selected='selected' >{$linha['nome']}</option>\n";
										}
									}

									echo "</select>\n";
									echo "</div>\n";
								}
							}


						}
					}
						?>
				</div>
			</div>
			<div class='span2'></div>
		</div>
<?php	} else { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span2">
				<div class="control-group" id="marca_bd">
					<label class='control-label'><?=traduz('Marca')?></label>
					<select name='marca_bd[]' id='marca_bd' class='span12 tipo_posto_bd bd_sel' multiple="multiple">
						<?php
						$sql = " SELECT  *
								 FROM    tbl_marca
								 WHERE   tbl_marca.fabrica = $login_fabrica
								 ORDER BY tbl_marca.nome;";

						$res = pg_query($con,$sql);
						
						if (pg_numrows($res) > 0) {
							while($marca_result = pg_fetch_array($res)){
								?>
								<option <?= in_array($marca_result['marca'], $marca)?"selected": ""?> value="<?=$marca_result['marca']?>" <?=$disable;?> ><?=$marca_result['nome']?></option>
								<?php
							}
						}
						?>
					</select>
				</div>
			</div>
			<div class='span2'></div>
			<div class="span4">
				<div class='span2'></div>
				<div class="control-group" id="linha_bd">
					<label class='control-label'><?=traduz('Linha')?></label>
					<select name='linha_bd[]' id='linha_bd' class='span12 tipo_posto_bd bd_sel' multiple="multiple">
						<?php
						$sql = "SELECT  *
								FROM    tbl_linha
								WHERE   tbl_linha.fabrica = $login_fabrica
								ORDER BY tbl_linha.nome;";
						$res = pg_query($con,$sql);
						if (pg_numrows($res) > 0) {
							while($linha_result = pg_fetch_array($res)){
								?>
								<option <?= in_array($linha_result['linha'], $linha)?"selected": ""?> value="<?=$linha_result['linha']?>" <?=$disable;?> ><?=$linha_result['nome']?></option>
								<?php
							}
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
		<div class='span2'></div>
			<div class="span2">
				<div class="control-group" id="familia_bd">
					<label class='control-label'><?=traduz('Família')?></label>
					<select name='familia_bd[]' id='familia_bd' class='span12 tipo_posto_bd bd_sel' multiple="multiple">
						<?php
						$sql = "SELECT  *
								FROM    tbl_familia
								WHERE   tbl_familia.fabrica = $login_fabrica
								ORDER BY tbl_familia.descricao;";

						$res = pg_query($con,$sql);
						
						if (pg_numrows($res) > 0) {
							while($familia_result = pg_fetch_array($res)){
								?>
								<option <?= in_array($familia_result['familia'], $familia)?"selected": ""?> value="<?=$familia_result['familia']?>" <?=$disable;?> ><?=$familia_result['descricao']?></option>
								<?php
							}
						}
						?>
					</select>
				</div>
			</div>
			<div class='span2'></div>
			<div class="span4">
				<div class='span2'></div>
				<div class="control-group" id="tipo_posto_bd">
					<label class='control-label'><?=traduz('Tipo de Posto')?></label>
					<select name='tipo_posto_bd[]' id='tipo_posto_bd' class='span12 tipo_posto_bd bd_sel' multiple="multiple">
						<?php
						$sql = "SELECT tipo_posto, descricao FROM tbl_tipo_posto WHERE fabrica = ".$login_fabrica;

						$res = pg_query($con,$sql);
						
						if (pg_numrows($res) > 0) {
							while($tipo_posto_result = pg_fetch_array($res)){
								?>
								<option <?= in_array($tipo_posto_result['tipo_posto'], $tipo_posto)?"selected": ""?> value="<?=$tipo_posto_result['tipo_posto']?>" <?=$disable;?> ><?=$tipo_posto_result['descricao']?></option>
								<?php
							}
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span4">
			<div class="control-group" id="categoria_bd">
					<label class='control-label'><?=traduz('Categoria')?></label>
					<select name='categoria_bd[]' id='categoria_bd' class='span12 tipo_posto_bd bd_sel' multiple="multiple">
						<?php
							$array_categoria_bd = ['autorizada','locadora','locadora autorizada', 'Compra Peca', 'mega projeto']; 
					
							foreach ($array_categoria_bd as $key => $categoria_result) {
								?>
								<option <?= in_array($categoria_result, $categoria_posto)?"selected": ""?> value="<?=$categoria_result?>" <?=$disable;?> ><?=$categoria_result?></option>
								<?php
							}
						
						?>
					</select>
				</div>	
			</div>
			<div class='span2'></div>
		</div>
	<?php
	} ?>

	<div class='row-fluid'>
		<div class='span2'></div>
		<?php

		switch ($login_fabrica) {
			case 1:
			case 169:
			case 170:
			case 193:
				$vagasSpanConfig = "span2";
				break;
			case 117:
				$vagasSpanConfig = "span4";
				break;
			default:
				$vagasSpanConfig = "span8";
				break;
        }

		if(in_array($login_fabrica, array(169,170,193))){ ?>
			<div class="span4">
				<div class="control-group" id="tipo_posto">
					<h5 class='asteristico'>*</h5>
					<label class='control-label'><?=traduz('Tipo de Posto')?></label>
					<select name='tipo_posto[]' id='tipo_posto' class='span12 tipo_posto_sel' multiple="multiple">
						<option><?=traduz('Selecione um tipo de posto')?></option>
						<?php
						$sql = "SELECT tipo_posto, descricao FROM tbl_tipo_posto WHERE fabrica = ".$login_fabrica;
						$res = pg_query($con,$sql);
						if (pg_numrows($res) > 0) {
							while($tipo_posto_result = pg_fetch_array($res)){
								/*// bloqueando campo
								if (in_array($tipo_posto_result['tipo_posto'], $tipo_posto))
								{
									$disable = "";
								}else{
								    if (!empty($treinamento) && $treinamento <= 1743) {
								        $disable = "";
								    } else {
								        if (empty($tipo_posto)){
								            $disable = "";
								        }else{  
								            $disable = "disabled=disabled";
								        }
								    }
								}*/
								?>
								<option <?= in_array($tipo_posto_result['tipo_posto'], $tipo_posto)?"selected": ""?> value="<?=$tipo_posto_result['tipo_posto']?>" <?=$disable;?> ><?=$tipo_posto_result['descricao']?></option>
								<?php
							}
						}
						?>
					</select>
				</div>
			</div>
		<?php
		} 

			if (in_array($login_fabrica, array(175))){ ?>
				<div class="span4">
					<div class="control-group" id="vagas">
						<h5 class='asteristico'>*</h5>
						<label class='control-label'><?=traduz('Vagas')?></label>
						<input type="text" id="qtde" name="qtde" size="10" maxlength="3" class='span12' value="<? if (strlen($qtde) > 0) echo $qtde; ?>">
					</div>
				</div>
				<div class="span4">
					<div class="control-group" id="div_validade_treinamento">
						<h5 class='asteristico'>*</h5>
						<label class='control-label'><?=traduz('Vencimento do Treinamento (Meses)')?></label>
						<input type="number" id="validade_treinamento" name="validade_treinamento" size="10" maxlength="3" class='span12' value="<? if (strlen($validade_treinamento) > 0) echo $validade_treinamento; ?>">
					</div>
				</div>
	<?php } else { ?>
				<div class="<?=$vagasSpanConfig?>">
					<div class="control-group" id="vagas">
						<h5 class='asteristico'>*</h5>
						<label class='control-label'><?=traduz('Vagas')?></label>
						<input type="text" id="qtde" name="qtde" size="10" maxlength="3" class='span12' value="<? if (strlen($qtde) > 0) echo $qtde; ?>">
					</div>
				</div>
	<?php }
		if(in_array($login_fabrica, array(169,170,193))){
			?>
			<div class="span2">
				<div class="control-group" id="vagas_posto">
					<h5 class='asteristico'>*</h5>
					<label class='control-label'><?=traduz('Vagas por posto')?></label>
					<input type="text" id="qtde_participante" name="qtde_participante" size="10" maxlength="3" class='span12' value="<? if (strlen($qtde_participante) > 0) echo $qtde_participante; ?>">
				</div>
			</div>
			<?php
		}

		if(in_array($login_fabrica, array(1))){ ?>
			<div class="<?=$vagasSpanConfig?>">
				<div class="control-group" id="vagas">
					<label class='control-label'><?=traduz('Vagas por posto')?></label>
					<input type="text" id="vaga_posto" name="vaga_posto" size="10" maxlength="3" class='span12' value="<? if (strlen($vaga_posto) > 0) echo $vaga_posto; ?>">
				</div>
			</div>
		<?php
		}
		?>

		<?php
		if($login_fabrica == 117){ ?>
			<div class='span4' nowrap>
				<div class='control-label'>
					<br>
					<label class='control-label'>
						<?php
						if(strtolower($visivel_portal) == 't'){
							$checkbox = "checked";
						}else{
							$checkbox = "";
						} ?>
						<input type="checkbox" name="visivel_portal"  value="true" <?php echo $checkbox; ?>>
						<?=traduz('Visualizar no portal')?>
					</label>
				</div>
			</div>
		<?php
		} ?>
		<div class='span2'></div>
	</div>
	<?php
		if (in_array($login_fabrica, array(175))){
	?>
		<div class='row-fluid' id='div_inscricoes' style='display: none;'>
			<div class='span2'></div>
			<!-- inicio inscrições -->
			<div class="span2">
				<div class="control-group" id="div_inicio_inscricao">
					<h5 class='asteristico'>*</h5>
					<label class='control-label'><?=traduz('Início das Inscrições')?></label>
					<input type="text" name="inicio_inscricao" id="inicio_inscricao" size="12" maxlength="10" class='span12' value="<?=$inicio_inscricao?>">
				</div>
			</div>
			<!-- fim inscrições -->
			<div class="span2">
				<div class="control-group" id="div_prazo_inscricao">
					<h5 class='asteristico'>*</h5>
					<label class='control-label'><?=traduz('Prazo das Incrições')?></label>
					<input type="text" name="prazo_inscricao" id="prazo_inscricao" size="12" maxlength="10" class='span12' value="<?=$prazo_inscricao?>">
				</div>
			</div>
		</div>
	<?php			
		}
	?>


	<?php
	if(in_array($login_fabrica, array(169,170,193))){
		?>
		<div class="row-fluid">
		<div class="span2"></div>
			<div class="span2">
				<div class="control-group" id="div_carga_horaria">
					<label class='control-label'><?=traduz('Carga Horária')?></label>
					<h5 class='asteristico'>*</h5>
					<input type="text" id="carga_horaria" name="carga_horaria" size="10" maxlength="3" class='span12' value="<? if (strlen($carga_horaria) > 0) echo $carga_horaria; ?>">
				</div>
			</div>
			<div class="span4">
				<div class="control-group" id="div_min_participantes">
					<label class='control-label'><?=traduz('Qtde Mínima de Participantes')?></label>
					<h5 class='asteristico'>*</h5>
					<input type="text" id="qtde_min" name="qtde_min" size="10" maxlength="3" class='span12' value="<? if (strlen($qtde_min) > 0) echo $qtde_min; ?>">
				</div>
			</div>
			<div class="span2"></div>
		</div>

		<div class="row-fluid">
		<div class="span2"></div>
			<div class="span8">
					<div class="control-group" id="div_instrutor">
						<label class='control-label'><?=traduz('Instrutor')?></label>
						<h5 class="asteristico">*</h5>
						<select id="instrutor" name="instrutor" class="span12 ">
							<option><?=traduz('Selecione o Instrutor')?></option>
							<?php
							$sql = "SELECT promotor_treinamento, nome, email FROM tbl_promotor_treinamento where fabrica = {$login_fabrica} AND (tipo = '2' OR tipo = '3')";
							$res = pg_query($con, $sql);
							while ($promotor = pg_fetch_array($res)){
								?>						
								<option <?=$res_instrutor['instrutor_treinamento'] == $promotor['promotor_treinamento']? "selected": ""?> 
								 value="<?=$promotor['promotor_treinamento']?>"><?=$promotor['nome']." - ".$promotor['email']?></option>
								<?php
							}
								?>
						</select>
					</div>
				</div>
		</div>

		<!-- tecnicos cadastrados -->
		<div class="row-fluid">
		<div class="span2"></div>
			<div class="span8">
				<div class="control-group" id="div_tecnico_convidado">
					<label class='control-label'><?=traduz('Convidados')?></label>

					<select name="tecnico_convidado[]" class="span12 convidados_sel" multiple="multiple">
						<option><?=traduz('Selecione o(s) Convidado(s)')?></option>
						<?php
							$sql = "SELECT
										tecnico,
										dados_complementares,
										nome,
										email,
										tipo_tecnico
									FROM tbl_tecnico
										WHERE fabrica = {$login_fabrica}
									AND tipo_tecnico = 'TF'
									AND ativo IS TRUE
									";
							$res   = pg_query($con, $sql);
							$count = pg_numrows($res);

							for ($i=0; $i<$count; $i++){
								$dados_complementares = json_decode(trim(pg_result($res,$i,dados_complementares)));
								$empresa              = utf8_decode($dados_complementares->empresa);
								$result               = pg_fetch_array($res);

								$select_convidado   = "SELECT
		                                                tecnico,
		                                                treinamento
		                                            FROM tbl_treinamento_posto
		                                            WHERE treinamento    = ".$treinamento."
		                                            AND tecnico          = ".$result['tecnico']."
		                                            ";
		                        $res_convidado      = pg_query($con, $select_convidado);

		                        if (pg_numrows($res_convidado) > 0){
		                        	$selected = "selected=selected";
		                        }else{
		                        	$selected = "";
		                        }
							?>
								<option <?=$selected?> value="<?=$result['tecnico']?>"><?=$empresa." - ".$result['nome']." &lt;".$result['email']."&gt;"?></option>
							<?php
					  		}
						?>
						</select>
				</div>
			</div>
		</div>
		<?php
	}
	?>

	<?php if (!in_array($login_fabrica, array(175))){ ?> 
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span8">
			<label class='control-label'><?=traduz('Informações Adicionais')?></label>
				<input type="text" name="adicional"  maxlength="200" class='frm span12' value="<? echo utf8_decode($adicional); ?>">
				<DIV ID="display_hint" class='span9'><?=traduz('Digite aqui as informações adicionais que o posto deve fornecer ao inscrever um treinando. Ex: Revenda')?></DIV>
			</div>
			<div class='span2'></div>
		</div>
	<?php } ?>
	
	<?php if(in_array($login_fabrica, array(1,138,117,169,170,171,193))){ //HD-3261932 ?>
		</br>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on btn-lupa' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							<input type="hidden" id="tipo_posto_b">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'><?=traduz('Nome Posto')?></label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on btn-lupa' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span8">
				<p>
					<input type="button" class="btn btn-lupa" value="Adicionar" onclick="addPosto()" <?php if(in_array($login_fabrica, [169,170,193]) && $editar_campos == true) echo $disabled; ?> />
				</p>
			</div>
			<div class="span2"></div>
		</div>
		<input type="hidden" id="codigo_adiciona" name="codigo_adiciona" value="">
		<input type="hidden" id="descricao_adicona" name="descricao_adicona" value="">
		<?php
			if (in_array($login_fabrica, array(169,170,193))) {
				$id_treinamento = trim($_GET['treinamento']);
				$sql = "SELECT
							tbl_treinamento.treinamento,
							(
								SELECT COUNT(*)
								FROM tbl_treinamento_posto
								WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
								AND   tbl_treinamento_posto.ativo IS TRUE
							)                                                     AS qtde_postos
						FROM tbl_treinamento
						WHERE tbl_treinamento.fabrica = {$login_fabrica}
						AND tbl_treinamento.treinamento = {$id_treinamento}";
				$res = pg_query($con,$sql);
				if (pg_numrows($res) > 0){
					$qte_inscritos = pg_fetch_result($res,0,qtde_postos);

					if ($qte_inscritos > 0){ ?>
						<div id="erro" class="alert alert-error">
							<h4><?=traduz('Postos que não possuem técnicos cadastrados.')?></h4>
						</div>
					<?php }
				}
			}
		?>

		<table id="integracao" class="table table-bordered" style="width: 580px;" >
			<thead>
				<tr class="titulo_coluna">
					<th><?=traduz('Código do Posto')?></th>
					<th><?=traduz('Nome do Posto')?></th>
					<th><?=traduz('Ações')?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$sqlTreina = "SELECT tbl_posto_fabrica.codigo_posto,
											tbl_posto.nome,
											tbl_posto.posto,
											tbl_treinamento_posto.tecnico
									FROM tbl_posto_fabrica
									JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
									JOIN tbl_treinamento_posto ON tbl_treinamento_posto.posto = tbl_posto_fabrica.posto
									WHERE tbl_treinamento_posto.treinamento = $treinamento
									AND tbl_posto_fabrica.fabrica = {$login_fabrica}
									AND tbl_treinamento_posto.tecnico IS NULL ";
									//echo $sqlTreina;exit;
					$resTreina = pg_query($con, $sqlTreina);
					if(pg_num_rows($resTreina) > 0){
						$count_treina = pg_num_rows($resTreina);

						for ($i=0; $i < pg_num_rows($resTreina); $i++) {
							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							$posto_codigo = pg_fetch_result($resTreina, $i, 'codigo_posto');
							$posto_nome = pg_fetch_result($resTreina, $i, 'nome');
							$posto_id = pg_fetch_result($resTreina, $i, 'posto');
						?>
						<tr id="<?=$i?>" class="tr-<?=$i?>" bgcolor="<?=$cor?>">
							<td><input class="cod_posto" type="hidden" value="<?=$posto_codigo?>" name="xcodigo_posto[<?=$i?>]" /><?=$posto_codigo?></td>
							<td><input type="hidden" value="<?=$posto_nome?>" name="xnome_posto[<?=$i?>]" /><?=$posto_nome?></td>
							<td class="tac"><button type="button" onclick="deletaposto('<?=$i?>','<?=$posto_id?>','<?=$treinamento?>')" class="btn"><?=traduz('Remover')?></button></td>
						</tr>

					<?php
						}
					}else{
						$count_treina = 0;
					}
				?>
			</tbody>
		</table>

	<?php } ?>
	<br>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span8">
			<div class="control-group" id="descricao_campo">
				<h5 class='asteristico'>*</h5>
				<label class='control-label'><?=traduz('Descrição')?></label>
				<?php
				$value_descricao = '';
				if (!empty($descricao)) {
					$value_descricao = (mb_check_encoding($descricao)) ? utf8_decode($descricao) : $descricao;
				}
				?>
				<TEXTAREA NAME='descricao' ROWS='7' id="descricao" COLS='60' class='frm span12'><?= $value_descricao ?></TEXTAREA>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<?php 
		if (in_array($login_fabrica, array($makita,1,117,138,169,170,171,175,193))) { 
			if (in_array($login_fabrica, array(175))){
				$display_regiao_estado    = "id='div_regiao_estado' style='display: none;'";
				$display_cidade           = "id='div_cidade' style='display: none;'";
			}
	?>
	<?php if (!in_array($login_fabrica, array(175))){ ?>
			<div class='row-fluid'>
				<div class='span2'></div>				
				<div class="span8 control-group" id='div_local_treinamento'>
					<label class='control-label'><?=traduz('Local do treinamento')?></label>
					<?php if(in_array($login_fabrica, [169,170,193])){ ?>
                		                <h5 class="asteristico">*</h5>
		                        <?php } ?>
					<input type="text" name="local" id="local" value="<?php echo utf8_decode($local) ?>" class='span12'>
				</div>
				<div class='span2'></div>
			</div>
	<?php } ?>

		<?php if(in_array($login_fabrica, array(117,138,171,175))){ ?>
		<!-- Combo de campos com Região, Estado e Cidade -->
		<div class='row-fluid' <?=$display_regiao_estado;?>>
			<div class='span2'></div>
			<div class="span4">
				<div class="control-group" id="descricao_estado" <?=$display_estado;?>>
					<input type="hidden" id="estado_treinamento" value='<?=$estado_cidade?>'>
					<label class='control-label'><?=traduz('Estado')?></label>
					<h5 class="asteristico">*</h5>
					<select id="listaEstado" name="listaEstado" class="span12 ">
						<?php if(strlen($estado_cidade) > 0){ ?>
							<option value="<?=$estado_cidade?>"><?=$estado_nome?></option>
						<?php }else{?>
							<option value=""><?=traduz('Selecione')?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="span4" <?=$display_cidade;?>>
				<div class="control-group" id="descricao_cidade">
					<input type="hidden" id="cidade_treinamento" value='<?=$nome_cidade?>'>
					<label class='control-label'><?=traduz('Cidade')?></label>
					<h5 class="asteristico">*</h5>
					<select id="cidade" name="cidade" class="span12 ">
						<?php if(strlen($nome_cidade) > 0){ ?>
							<option value="<?=$cidade?>"><?=$nome_cidade?></option>
						<?php }else{?>
							<option value=""><?=traduz('Selecione')?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class='span2'></div>
		</div>

	<?php
		}

		if(in_array($login_fabrica, array(169,170,193))){
			?>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span4">
					<div class="control-group" id="descricao_estado">
						<input type="hidden" id="estado_treinamento" value='<?=$estado_cidade?>'>
						<label class='control-label'><?=traduz('Estado do treinamento')?></label>
						<h5 class="asteristico">*</h5>
						<select id="listaEstado" name="listaEstado" class="span12 ">
							<?php if(strlen($estado_cidade) > 0){ ?>
								<option value="<?=$estado_cidade?>"><?=$estado_nome?></option>
							<?php }else{?>
								<option value=""><?=traduz('Selecione')?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="span4">
					<div class="control-group" id="descricao_cidade">
						<input type="hidden" id="cidade_treinamento" value='<?=$nome_cidade?>'>
						<label class='control-label'><?=traduz('Cidade do treinamento')?></label>
						<h5 class="asteristico">*</h5>
						<select id="cidade" name="cidade" class="span12 ">
							<?php if(strlen($nome_cidade) > 0){ ?>
								<option value="<?=$cidade?>"><?=$nome_cidade?></option>
							<?php }else{?>
								<option value=""><?=traduz('Selecione')?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
			<!-- Combo de campos Estado e Cidade, permitindo adicionar várias cidades -->
			<hr>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span2">
					<div class="control-group" id="descricao_estado">
						<input type="hidden" id="estado_participante_treinamento" value=''>
						<label class='control-label'><?=traduz('Estado')?></label>
						<select id="listaEstadoParticipante" name="listaEstadoParticipante" class="span12 ">
							<option><?=traduz('Estado')?></option>
							<?php
							foreach ($array_estados() as $key => $value) {
								if($estado_participante == $key){
									$estado_participante_text = $value;
								}
								?><option value="<?=$key?>"><?=$value?></option><?php
							}
							?>
						</select>
					</div>
				</div>
				<div class="span4">
					<div class="control-group" id="descricao_cidade_participante">
						<input type="hidden" id="cidade_participante_treinamento" value=''>
						<label class='control-label'><?=traduz('Cidade')?></label>
						<select id="cidade_participante" name="cidade_participante" class="span12 ">
							<option value=""><?=traduz('Selecione')?></option>
						</select>
					</div>
				</div>
				<div class="span2">
					<input type='button' name='btn-add-cidade' id='btn-add-cidade' class='btn btn-primary' value='Adicionar' style='margin-top: 20px;'>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span8">
					<table id='tbl_cidades_participantes' class='table table-striped table-bordered table-hover table-fixed'>
					<thead>
						<tr class='titulo_tabela'><th colspan='9'> <?=traduz('Cidades Participantes')?></th></tr>
						<tr class='titulo_coluna'>
						<th><?=traduz('Cidade')?></th>
						<th><?=traduz('Estado')?></th>
						<th><?=traduz('Ação')?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($res_estados as $value) {
							if (empty($value['estado'])){
								continue;
							}
							?>
							<tr>
								<td class=""><small>(<?=traduz('Todas')?>)</small></td>
								<td class="tac"><?=$value['estado']?></td>
								<td class="tac"><input type="button" name="btn-rm-estado-participante" class="btn btn-danger btn-mini btn-rm-estado-participante" value="Remover"></td>
							</tr>
							<?php
						}
						foreach ($res_cidade as $value) {
							?>
							<tr>
								<td><?=$value['nome']?></td>
								<td class="tac"><?=$value['estado']?></td>
								<td class="tac"><input data-treinamento-cidade='<?=$value['treinamento_cidade']?>' type="button" name="btn-rm-cidade" class="btn btn-danger btn-mini btn-rm-cidade" value="Remover"></td>
							</tr>
							<?php
						}
						?>
					</tbody>
					</table>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span8">
					<small><?=traduz('Selecione um estado participante e/ou escolha as cidades que poderão se inscrever no treinamento')?></small>
				</div>
			</div>
			<?php
		}
	}
	?>

	<?php 
		if (in_array($login_fabrica, array(175))) { 
			$display_localTreinamento = "id='div_local_treinamento' style='display: none;'";
	?>
			<div class='row-fluid' <?=$display_localTreinamento;?>>
				<div class='span2'></div>
				<div class="span6 control-group" id="local_treinamento_erro">
					<h5 class="asteristico">*</h5>
					<label class='control-label'><?=traduz('Local do treinamento')?></label>
					<input type="text" name="local" id="local" value="<?php echo utf8_decode($local) ?>" class='span12'>
				</div>
				<div class='span1' id='div_treinamento_ativo'>
					<label class='control-label'><?=traduz('Ativo')?></label> <br />
					<center> <input type="checkbox" name="ativo" id="ativo" value="t" <?php echo ($ativo == 't') ? 'checked=checked' : ''; ?> /> </center>
				</div>
				<!-- <div class='span1' id='div_treinamento_finalizado'>
					<label class='control-label'>Finalizado</label> <br />
					<center> <input type="checkbox" name="finalizado" id="finalizado" <?php echo (strlen($finalizado) > 0) ? 'checked=checked' : ''; ?> /> </center>
				</div> -->
				<div class='span2'></div>
			</div>

	
	<?php } ?> 

	<br />

	<?php 
	/******************* BOX UPLOADER *******************/
	if (!in_array($login_fabrica, array(175))) {
		$anexo_prepend = "<label class='label label-important' id='anexo_obrig' {$display_msg_anexo} >Anexo(s) obrigatórios</label>";
	}

	if ($fabricaFileUploadTreinamento) {

		echo "<div id='anexos'>";
	        $boxUploader = array(
	            "div_id" => "div_anexos",
	            "prepend" => $anexo_prepend,
	            "context" => "treinamento",
	            "unique_id" => $tempUniqueId,
	            "hash_temp" => $anexoNoHash
	        );
	        include "../box_uploader.php";
        echo "</div>";
    }
?>


	<?php
	if(in_array($login_fabrica, array(169,170,193))){
		?>
		<hr>
		<div class="row-fluid">
			<span class="span2"></span>
			<div class="span6">
				<div class="control-group" id="">
					<label class='control-label'><?=traduz('Promotores')?></label>
					<select id="promotor" name="promotor" class="span12 ">
					<option value=""><?=traduz('Selecione o promotor')?></option>
						<?php
						$sql = "SELECT promotor_treinamento, nome, email FROM tbl_promotor_treinamento where fabrica = {$login_fabrica} AND (tipo = '1' OR tipo = '3')";
						$res = pg_query($con, $sql);
						while ($promotor = pg_fetch_array($res)){
							?>
							<option value="<?=$promotor['promotor_treinamento']?>"><?=$promotor['nome']." - ".$promotor['email']?></option>
							<?php
						}

						?>
					</select>
				</div>
			</div>
			<div class="span2">
				<input type='button' name='btn-add-promotor' id='btn-add-promotor' class='btn btn-primary' value='Adicionar' style='margin-top: 20px;'>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span8">
				<table id='tbl_promotores_participantes' class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_tabela'><th colspan='9'> <?=traduz('Promotores Responsáveis')?></th></tr>
					<tr class='titulo_coluna'>
					<th><?=traduz('Nome')?></th>
					<th><?=traduz('Ação')?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($res_promotores as $value) {
						?>
						<tr>
							<td class="td-promotor" data-promotorId="<?=$value['treinamento_promotor']?>"><?=$value['nome']." - ".$value['email']?></td>
							<td class="tac"><input data-promotor-treinamento='<?=$value['treinamento_promotor']?>' type="button" name="btn-rm-promotor" class="btn btn-danger btn-mini btn-rm-promotor" value="Remover"></td>
						</tr>
						<?php
					}
					?>
				</tbody>
				</table>
			</div>
		</div>
		<?php
	}
	
	if (in_array($login_fabrica, [169,170,193]) && $editar_campos == true) { ?>
		<div class='row-fluid'>
			<div class='span12'>
				<div class="span12 env-tdocs-uploads"></div>
				<center>
		        	<button type="button" class="btn btn-primary" id="btn-call-fileuploader" rel="2"><i class="icon-picture icon-white"></i> <?=traduz('Anexar Arquivos')?></button>
		        </center>
			</div>
		</div>
		<br>
<?php } else {
	echo "<br>";
} ?>

	<div class='row-fluid'>
		<div class='span12'>
			<center>
				<?php
				if($login_fabrica == $makita){
					echo "<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn2' class='btn btn-primary' value='Gravar'\">";
				}elseif (in_array($login_fabrica, array(169,170,193))){
					echo "<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' class='btn btn-primary' value='Gravar'\"> ";
					if (strlen($treinamento) > 0){
						echo " <button TYPE='button' name='bt_canc_forn' id='bt_canc_forn' class='btn btn-danger'>Cancelar</button>";
					}					
				}else{
					echo "<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' class='btn btn-primary' value='Gravar'\">";	
				}
				?>
				<INPUT TYPE='button' name='bt_limpa_form' id='bt_limpa_form' class='btn btn-warning' value='Limpar'>
			</center>
		</div>
	</div>
</FORM>
<br />
</div>

<div id="modal-cadastra-tecnico" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
	<input type="hidden" id="id_treinamento" value="" name="id_treinamento" >
	<input type="hidden" name="id_posto" id="id_posto" value="">

    <div class="modal-header">
        <h3><?=traduz('Cadastrar Técnicos')?></h3>
    </div>
    <div class="modal-body">

    	<div class="alert alert-danger" id="msg_error_tecnico" style="display: none;"></div>
		<div class="alert alert-success" id="msg_success_tecnico" style="display: none;"></div>

        <div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_postox" id="codigo_postox" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" cadastra_tecnico_admin="true" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'><?=traduz('Nome Posto')?></label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="descricao_postox" id="descricao_postox" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" cadastra_tecnico_admin="true" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<label class='control-label' for='tecnico'><?=traduz('Técnicos')?></label>
				<select name="tecnico" id="tecnico">
					<option value=""></option>

				</select>
			</div>
			<div class='span2'></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<br/>
				<button type='button' class="btn btn-primary" id='insert_tecnico'><?=traduz('Cadastrar')?></button>
			</div>
			<div class="span4">
				<br/>
				<button type='button' class="btn" id='limpar_dados_tecnico'><?=traduz('Limpar dados')?></button>
			</div>
		</div>
    </div>
    <div class="modal-footer">
        <button type="button" id="btn-close-modal-cadastra-tecnico" class="btn"><?=traduz('Fechar')?></button>
    </div>
</div>

<div class="container-fluid">
<div id='dados'></div>
</div>
<script type='text/javascript'>
	$(function(){

		<?php  if (in_array($login_fabrica, array(175))) { ?>

				$("#inicio_inscricao").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
				$("#prazo_inscricao").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");	

				$('#data_inicial_picker, #data_final_picker').datetimepicker({
		      		language: 'pt-BR'
	    		});

				$("#linha").select2();
				$("#produto").select2();

				// $("input[name=treinamento_por]:radio").change(function () {
				// 	verificaTreinamento(this.value);
				// });

				$('#treinamento_tipo').change(function() {
					var descricao_tipo_treinamento = $("#treinamento_tipo option:selected").text().toLowerCase();
					$("#data_inicial_picker").find("h5").show();
					$("#data_final_picker").find("h5").show();
				
					if (descricao_tipo_treinamento == "presencial"){
						$("#div_local_treinamento").show();
						$("#local_treinamento_erro").show();
						$("#div_horaro_inicial").show();
						$("#div_horaro_final").show();
						$("#div_inscricoes").show();
						$("#vagas").parent().show();
						$("#div_regiao_estado").show();
						$("#div_cidade").show();
						$("#div_anexos").show();
					}else{
						$("#div_local_treinamento").hide();
						$("#div_horaro_inicial").hide();
						$("#div_horaro_final").hide();
						$("#div_inscricoes").hide();
						$("#vagas").parent().hide();
						$("#div_regiao_estado").hide();
						$("#div_cidade").hide();
						$("#div_anexos").hide();
						if (descricao_tipo_treinamento == "online"){
							$("#div_local_treinamento").show();
							$("#local_treinamento_erro").hide();
							$("#data_inicial_picker").removeClass("error");
							$("#data_final_picker").removeClass("error");
							$("#data_inicial_picker").find("h5").hide();
							$("#data_final_picker").find("h5").hide();
						}
					}
				});

				//verificaTreinamento($("input[name='treinamento_por']:checked").val());
				$('#treinamento_tipo').change();
				
				// on click checkbox
				$("#finalizado").on('click', function(){
					treinamento_tipo_check = $("#treinamento_tipo option:selected").text().toLowerCase();
					if (treinamento_tipo_check == "online"){
						if ($(this).prop('checked')) {
							confirm('Encerrar Inscrições');	
				        }	
					}
				});

				$("#ativo").on('click', function(){
					var ativo = $(this).is(':checked');

					if (ativo == false){
						alert("O Treinamento será cancelado e será enviado uma notificação para todos os inscritos, deseja prosseguir?");
					}else{

					}
				});

		<?php }elseif (in_array($login_fabrica, array(1,169,170,193))){ ?>
				checkItensTabela();
				$("#linha").select2();
				$(".tipo_posto_sel").select2();
                $(".convidados_sel").select2();
                $(".bd_sel").multiselect();

		<?php } ?>

		Shadowbox.init();

		$(document).on('click', 'a.shadow_treinamento', function(){
            var url = $(this).data('url');
            Shadowbox.open({
                content: url,
                player: 'iframe',
                width: 1224,
                height: 600
            });
        });

		$("span[rel=lupa]").click(function () {
			<?php if (in_array($login_fabrica, array(169,170,193))){ ?>
				$.lupa($(this),Array('cadastra_tecnico_admin'));
			<?php }else{?>
				$.lupa($(this));
			<?php } ?>
		});

		<?php if (in_array($login_fabrica, array(148,169,170,193))){ ?>

			$(document).on("click", ".btn_cadastra_tecnico", function() {
		        var modal_reprova_os = $("#modal-cadastra-tecnico");
		        var id_treinamento = $(this).data("id_treinamento");
		        $("#id_treinamento").val(id_treinamento);

		        $(modal_reprova_os).modal("show");
		    });

			$("#btn-close-modal-cadastra-tecnico").click(function() {
	            var modal_reprova_os = $("#modal-cadastra-tecnico");
	            var btn_fechar = $("#btn-close-modal-cadastra-tecnico");
	            $(modal_reprova_os).modal("hide");
	        });

			$('#data_inicial_picker, #data_final_picker').datetimepicker({
	      		language: 'pt-BR'
    		});

			$(document).on("click", "#limpar_dados_tecnico", function() {
				$("#id_posto").val("");
				$("#tecnico").children(":selected").prop("selected", false);
				$("#msg_success_tecnico").hide();
        		$("#msg_success_tecnico").html("");
        		$("#msg_error_tecnico").hide();
        		$("#msg_error_tecnico").html("");
        		$("#codigo_postox").val("");
        		$("#descricao_postox").val("");
			});

			$(document).on("click", "#insert_tecnico", function() {
				var treinamento = $("#id_treinamento").val();
				var posto 		= $("#id_posto").val();
				var tecnico 	= $("#tecnico").children(":selected").val();

				$.ajax({
		            method: "GET",
		            url: "cadastra_tecnico_treinamento.php",
		            data: { ajax: 'sim', acao: 'cadastrar', treinamento: treinamento, posto: posto, tecnico_nome: tecnico, cadastra_tecnico_admin: 'sim'},
		            timeout: 8000
		        }).done(function(data) {
		        	var result = data.split("|");

		        	if (result[0] == "1" || result[0] == "2"){
		        		$("#msg_error_tecnico").show();
		        		$("#msg_error_tecnico").html("<h4>"+result[1]+"</h4>");

		        		$("#msg_success_tecnico").hide();
		        		$("#msg_success_tecnico").html("");

		        		setTimeout(function(){
							$("#msg_success_tecnico").hide();
		        			$("#msg_success_tecnico").html("");
		        		},1500);
		        	}else if (result[0] == "ok"){
	        			$("#msg_success_tecnico").show();
		        		$("#msg_success_tecnico").html("<h4>"+result[1]+"</h4>");

		        		$("#msg_error_tecnico").hide();
		        		$("#msg_error_tecnico").html("");

		        		setTimeout(function(){
							$("#msg_success_tecnico").hide();
		        			$("#msg_success_tecnico").html("");
		        		},1500);
		        	}
	        	});
			});

<?php
		} else {
            if ($login_fabrica == 1) {
?>
                $(document).on("click","button[id^=concluir_]",function(){
                    var aux                 = $(this).attr("id").split("_");
                    var treinamento_posto   = aux[1];

                    if (confirm("Deseja realmente concluir o treinamento?")) {
                        $.ajax({
                            url:"ajax_treinamento.php",
                            type:"POST",
                            dataType:"JSON",
                            data:{
                                ajax:true,
                                tipo:"concluir_treinamento",
                                treinamento_posto:treinamento_posto
                            }
                        })
                        .done(function(data){
                            if (data.ok) {
                                alert(data.msg);
                                location.href = "treinamento_realizados.php";
                            }
                        });

                    }
                });

                $(document).on("click","button[id^=excluir_]",function(){
                    var aux                 = $(this).attr("id").split("_");
                    var treinamento_posto   = aux[1];
                    var urlComunicado = $(this).data('url');

                    $.ajax({
                        url:"ajax_treinamento.php",
                        type:"POST",
                        dataType:"JSON",
                        data:{
                            ajax:true,
                            tipo:"excluir_treinamento",
                            treinamento_posto:treinamento_posto
                        }
                    })
                    .done(function(data){
                        if (data.ok) {
                            if (!data.envia_comunicado) {
                                alert(data.msg);
                                mostrar_treinamento();
                                window.setTimeout('location.reload()', 500);
                            } else {

                                Shadowbox.open({
                                    content: "treinamento_justificativa.php?treinamento="+treinamento_posto,
                                    player: 'iframe',
                                    width: 1024,
                                    height: 600
                                });
                            }
                        }
                    });
                });
<?php
            }

            if (!in_array($login_fabrica, array(175))){ ?>
				$("#data_inicial").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
				$("#data_final").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");	
	<?php }

	} ?>

		<?php
		if(in_array($login_fabrica, array(148,169,170,193))){
			?>
			$("#prazo_inscricao").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
			<?php
		}
		?>
		$("#qtde").mask("999");

		if ($("#tecnico_fone").length) {
			$("#tecnico_fone").maskedinput("(99)9999-9999");
		}

		if ($("#tecnico_celular").length) {
			$("#tecnico_celular").maskedinput("(99)9999-9999");
		}

		$('#img_help').click(function(){
			alert("Cardíaca, hipertensíva, traumatismo, infecto-contagiosa, etc.");
		});

		var cidade_treinamento = $("#cidade_treinamento").val();
		var estado_treinamento = $("#estado_treinamento").val();

		listaEstado();

		mostrar_treinamento();

		<?php if (in_array($login_fabrica, [169,170,193]) && $editar_campos == true) { /*HD - 6261912*/ ?>
			desabilitarCampos();

			$("#btn-call-fileuploader").click(function(){
	        	Shadowbox.open({
	            	content: tdocs_uploader_url,
	            	player: "iframe",
	            	height: 600,
	            	width: 950,
	            	options: {
	            	    onClose: updateEnvTdocs
	            	}
		    	});
		    });
		<?php } ?>
	});

	<?php if (in_array($login_fabrica, [169,170,193]) && $editar_campos == true) { /*HD - 6261912*/ ?>
		function desabilitarCampos() {
			$("#linha").prop("disabled", true);
			$(".tipo_posto_sel").prop("disabled", true);
			$(".convidados_sel").prop("disabled", true);
			$("#listaEstadoParticipante").prop("disabled", true);
			$("#cidade_participante").prop("disabled", true);
			$("#promotor").prop("disabled", true);
			$("#prazo_inscricao").prop("disabled", true);
			$("input[name=adicional]").prop("disabled", true);
			$("#codigo_posto").prop("disabled", true);
			$("#descricao_posto").prop("disabled", true);
			$("#descricao").prop("disabled", true);
			$("#btn-add-cidade").prop("disabled", true);
			$("#btn-add-promotor").prop("disabled", true);
		}

		function habilitarCampos() {
			$("#linha").prop("disabled", false);
			$(".tipo_posto_sel").prop("disabled", false);
			$(".convidados_sel").prop("disabled", false);
			$("#listaEstadoParticipante").prop("disabled", false);
			$("#cidade_participante").prop("disabled", false);
			$("#promotor").prop("disabled", false);
			$("#prazo_inscricao").prop("disabled", false);
			$("input[name=adicional]").prop("disabled", false);
			$("#codigo_posto").prop("disabled", false);
			$("#descricao_posto").prop("disabled", false);
			$("#descricao").prop("disabled", false);
			$("#btn-add-cidade").prop("disabled", false);
			$("#btn-add-promotor").prop("disabled", false);
		}

		var tdocs_uploader_url = "plugins/fileuploader/fileuploader-iframe.php?context=treinamento&reference_id=<?=$treinamento ?>";
		var updateEnvTdocs = function(){
			var tokens = [];
			$.ajax(tdocs_uploader_url+"&ajax=get_tdocs").done(function(response){
				$(response).each(function(idx,elem){
					tokens.push(elem.tdocs_id);
					if($("#"+elem.tdocs_id).length == 0){
						var div = $("<div class='env-img' style='display: none'>");

						$(div).html("Carregando...");
						$(".env-tdocs-uploads").append(div);

						loadImage(elem.tdocs_id,function(responseTdocs){
							$(div).html("");
							$(div).attr("id",elem.tdocs_id);
							var img = $("<img class='img-rounded'>");
							if(responseTdocs.fileType == 'image'){
								$(img).attr("src",responseTdocs.link);
							}else{
								$(img).attr("src","plugins/fileuploader/file-placeholder.png");
								var span = $("<span>"+responseTdocs.file_name+"</span>")
								$(div).append(span);
							}

							$(div).prepend(img);


							$(div).click(function(){
								if(responseTdocs.fileType == 'image'){
									$("#img-tag").attr("src", responseTdocs.link);
								}else{
									$("#img-tag").attr("src", "plugins/fileuploader/file-placeholder.png");
									var span = $("<a href='"+responseTdocs.link+"' target='_BLANK'>"+responseTdocs.file_name+"</a>")
									$("#img-tag").parents("div:first").append(span);
								}

								$("#modal-view-image").modal();
							});

							setTimeout(function(){
								$(div).fadeIn(1500);
							},1000);
						});
					}
				});

				setTimeout(function(){
					$(".env-img").each(function(idx,elem){
						var id = $(elem).attr("id");
						if($.inArray(id,tokens) == -1){
							$("#"+id).fadeOut(1500,function(){
								$("#"+id).remove();
							});
						}
					});
				},3000);
	        });
		}
	<?php } ?>
	
	function verificaTreinamento(valor){
		if (valor == 'linha'){
			$("#linha").attr('disabled', false);
			$("#produto").attr('disabled', true);
			$("#produto").val('').trigger('change');
			$(".asteristico_produto").hide();
			$(".asteristico_linha").show();
		}else if (valor == 'produto'){
			$("#linha").attr('disabled', true);
			$("#produto").attr('disabled', false);
			$("#linha").val('').trigger('change');
			$(".asteristico_linha").hide();
			$(".asteristico_produto").show();
		}else{
			$("#linha").attr('disabled', false);
			$("#produto").attr('disabled', false);
			$("#linha").val('').trigger('change');
			$("#produto").val('').trigger('change');
		}
	}

	function orderTable(tabela){
		jQuery.extend(jQuery.fn.dataTableExt.oSort, {
	        "currency-pre": function (a) {
	            a = (a === "-") ? 0 : a.replace(/[^\d\-\.]/g, "");
	            return parseFloat(a);
	        },
	        "currency-asc": function (a, b) {
	            return a - b;
	        },
	        "currency-desc": function (a, b) {
	            return b - a;
	        }
	    });
	        var tds = $(tabela).find(".titulo_coluna");

	        var colunas = [];

	        $(tds).find("th").each(function(){
	            if ($(this).attr("class") == "date_column") {
	                colunas.push({"sType":"date"});
	            }else {
	                colunas.push(null);
	            }
	        });

		$.dataTableLoad({ table: tabela,aoColumns:colunas });
	}

	function retorna_posto(retorno){

		<?php if (in_array($login_fabrica, array(169,170,193))){ ?>

			if (retorno.cadastra_tecnico_admin == 'true'){
				$("#codigo_postox").val(retorno.codigo);
				$("#descricao_postox").val(retorno.nome);
				$("#id_posto").val(retorno.posto);

				if (retorno.dados_tecnicos.length > 0){
					var option = "<option value=''>Selecione um técnico</option>";
					$.each(retorno.dados_tecnicos, function(key, value){
						option += "<option value='"+value.tecnico+"'>"+value.nome+"</option>";
					});

					$('#tecnico').html(option);
				}

			}else{
				$("#codigo_posto").val(retorno.codigo);
				$("#descricao_posto").val(retorno.nome);
			}

			$("#codigo_adiciona").val(retorno.codigo); //HD-3261932
			$("#descricao_adicona").val(retorno.nome); //HD-3261932

		<?php }else{?>

			$("#tipo_posto_b").val(retorno.tipo_posto);
			$("#codigo_posto").val(retorno.codigo);
			$("#descricao_posto").val(retorno.nome);
			$("#codigo_adiciona").val(retorno.codigo); //HD-3261932
			$("#descricao_adicona").val(retorno.nome); //HD-3261932

		<?php } ?>
	}

<?php if(in_array($login_fabrica, array(1,138,117,169,170,171,193))){ ?>
    function deletaposto(n,posto,treinamento){ //HD-3261932
		var linha_tr = $("#"+n);
		var posto = posto;
		if(treinamento > 0 && posto > 0){
			$.ajax({
	            method: "GET",
	            url: "ajax_treinamento.php",
	            data: { ajax: 'sim', acao: 'deletar_posto', treinamento: treinamento, posto: posto},
	            timeout: 8000
	        }).done(function(data) {
	            data = JSON.parse(data);
	            if (data.success !== undefined) {
	                linha_tr.remove();
	            }else if (data.erro == 'error') {
	                alert("Existem técnicos cadastrado para esse treinamento, o posto não pode ser removido");
	            }

	            checkItensTabela();
        	});
		}else{
			linha_tr.remove();
			checkItensTabela();
		}
		$(this).prop("disabled",false);

		<?php if ($login_fabrica == 1) { ?>
				if ($(".cod_posto").val() == undefined) {
					$(".bd_sel").multiselect('enable');
	    		}
		<?php } ?>
	}

	<?php if (in_array($login_fabrica, array(1,169,170,193))){ ?>
			function checkItensTabela()
			{
				if ($('#integracao tr').length <= 1){
					var select   = $('#tipo_posto').find("#tipo_posto option");
					$(select).each(function(){
			        	$(this).prop("disabled",false);
			        });
				}

			<?php if ($login_fabrica == 1) { ?>
					if ($('#integracao tr').length <= 1){
						var select   = $('#marca_bd').find("#marca_bd option");
						$(select).each(function(){
				        	$(this).prop("disabled",false);
				        });
					}

					if ($('#integracao tr').length <= 1){
						var select   = $('#linha_bd').find("#linha_bd option");
						$(select).each(function(){
				        	$(this).prop("disabled",false);
				        });
					}

					if ($('#integracao tr').length <= 1){
						var select   = $('#familia_bd').find("#familia_bd option");
						$(select).each(function(){
				        	$(this).prop("disabled",false);
				        });
					}

			<?php } ?>
			}
	<?php } ?>


	var count_treina = <?=$count_treina?>;
	i = count_treina;

    function addPosto() { //HD-3261932

    	var continuar = '';
    	<?php
		if (in_array($login_fabrica, array(169,170,193)))
		{
		?>		var array_select_tipo = "";
				$("#tipo_posto").find("option:selected").each(function(idx,elem){
					array_select_tipo += "&select_tipo_posto[]="+$(elem).val();
				});

		  		var codigo_posto   = $("#codigo_posto").val();

		  		var acao         = 'adicionar_posto';
				var treinamento  = $("#treinamento").val();

				var array_linha = "";
				$("#linha").find("option:selected").each(function(idx,elem){
					array_linha += "&linha[]="+$(elem).val();
				});

		  		if (array_select_tipo != "" && codigo_posto != "")
		  		{
		  			$.ajax({
						url: "ajax_treinamento.php",
						async: false,
						data: "codigo_posto="+codigo_posto+"&acao="+acao+"&ajax=sim"+"&treinamento="+treinamento+array_linha+array_select_tipo,
						method: "GET",
					}).done(function(response){
						response = JSON.parse(response);
						if (response.error !== undefined){
							//$('#bt_cad_forn').prop("disabled", false);
							$('input[name=bt_cad_forn]').prop("disabled", false);
							alert(response.error);
							continuar = false;
						}else{
							continuar = true;
						}
					});
		  		}
	<?php }else{ ?> continuar = true; <?php } ?>

		if (continuar == false){
			return;
		}else{

			var options         = $('#tipo_posto').find("#tipo_posto option");
			var optionsSelected = $("#tipo_posto").find("option:selected");

			var values = [];
			$(optionsSelected).each(function(idx,op){
				values.push($(op).val());
			});

			$(options).each(function(idx,op){
				if(values.indexOf($(op).val()) == -1){
					$(op).attr("disabled","disabled");
					$(op).attr("selected",false);
				}else
				{
					$(op).attr("disabled",false);
					$(op).attr("selected","selected");
				}
			});
		}

    	var arr_posto = [];
    	$("#integracao").each(function(){
    		var codigo_posto = $(".cod_posto").val()
    		arr_posto.push(codigo_posto);
    	});

    	var codigo_adiciona = $('#codigo_adiciona').val();
		var descricao_adicona = $("#descricao_adicona").val();
		var count_treina = <?=$count_treina?>;
		var tipo_posto_b  = $("#tipo_posto_b").val();
		var cor = (i % 2) ? "#F7F5F0" : "#F1F4FA";

		var htm_input = '<tr data-tipo="'+tipo_posto_b+'" id="'+i+'" class="tr-'+i+'" bgcolor="'+cor+'"><td><input class="cod_posto" type="hidden" value="'+codigo_adiciona+'" name="xcodigo_posto['+i+']" />'+codigo_adiciona+'</td><td><input type="hidden" value="'+descricao_adicona+'" name="xnome_posto['+i+']" />'+descricao_adicona+'</td><td class="tac"><button type="button" onclick="deletaposto('+i+')" class="btn">Remover</button></td></tr>';

		if (codigo_adiciona  === '') {
			alert('Selecione um novo posto');
			return false;
		}


		if (descricao_adicona  === '') {
			alert('Selecione um novo posto');
			return false;
		}

		if(arr_posto.length > 0){
			if(jQuery.inArray(codigo_adiciona, arr_posto) !== -1){
				alert("Posto já adicionado");
				return;
			}
		}
	
		arr_posto.push(codigo_adiciona);
		i++;
		$("#tabela").css("display","block");
		$(htm_input).appendTo("#integracao");

		$("#tipo_posto_b").val('');
		$('#codigo_adiciona').val('');
		$("#descricao_adicona").val('');
		$("#codigo_posto").val('');
		$("#descricao_posto").val('');
		$(".bd_sel").multiselect('disable');

	}

<?php } ?>

	<?php
	if(in_array($login_fabrica, array(169,170,193))){
		?>
		var removeCidade = function(){
			var treinamentoCidade = $(this).data("treinamento-cidade");
			var linhaCidadeTreinamento = this;
			var treinamento = $("#treinamento").val();

			$.ajax({
	            method: "POST",
	            url: "ajax_treinamento.php",
	            data: { ajax: 'sim', acao: 'deletar_treinamento_cidade', treinamento_cidade: treinamentoCidade, 'treinamento': treinamento},
	            timeout: 8000
	        }).done(function(data) {
	            data = JSON.parse(data);
	            if (data.success !== undefined) {
	                $(linhaCidadeTreinamento).parents("tr").remove();
	            }else if (data.erro == 'error') {
	                alert("Não foi possível remover a cidade");
	            }
        	});
		}


		$("#btn-add-cidade").click(function(){
			var estado = $("#listaEstadoParticipante option:selected").html();
			var estadoVal = $("#listaEstadoParticipante option:selected").val();
			var cidade = $("#cidade_participante option:selected").html();
			var cidadeId = $("#cidade_participante option:selected"). val();

			var erroCidade = false;
			$("#tbl_cidades_participantes").find("tbody > tr").each(function(idx,elem){
				var td = $(elem).find(".td-cidade")[0];

				if($(td).data("cidadeId") == cidadeId){
					alert("Cidade já adicionada");
					erroCidade = true;
				}

				var td_estado = $(elem).find(".td-estado")[0];
				if($(td_estado).data("estado") == estadoVal){
					alert("Estado já adicionado");
					erroCidade = true;
				}
			});

			if(erroCidade){
				return false;
			}

			if(cidadeId != ""){
				var tr = $("<tr>");

				var td = $("<td class='td-cidade'>");
				$(td).data("cidadeId",cidadeId);
				$(td).html(cidade);
				$(tr).append(td);

				var td = $("<td class='tac'>");
				$(td).html(estado);
				$(tr).append(td);


				var btnRemove = $('<input type="button" name="btn-rm-cidade" class="btn btn-danger btn-mini btn-rm-cidade" value="Remover">');
				$(btnRemove).click(function(){
					$($(this).parents("tr")[0]).remove();
				});
				var td = $("<td class='tac'>");
				$(td).append(btnRemove);
				$(tr).append(td);

				$("#tbl_cidades_participantes").find("tbody").append(tr);

			}else{
				if(estado != "" && estado != "Estado"){

					var tr = $("<tr>");

					var td = $("<td class='td-cidade'>");
					$(td).html('<small>(Todas)</small>');
					$(tr).append(td);

					var td = $("<td class='tac td-estado'>");
					$(td).html(estado);
					$(td).data("estado",estadoVal);
					$(tr).append(td);

					var btnRemove = $('<input type="button" name="btn-rm-estado-participante" class="btn btn-danger btn-mini btn-rm-estado-participante" value="Remover">');
					$(btnRemove).click(function(){
						$($(this).parents("tr")[0]).remove();

					});
					var td = $("<td class='tac'>");
					$(td).append(btnRemove);
					$(tr).append(td);

					$("#tbl_cidades_participantes").find("tbody").append(tr);
				}else{
					alert("Selecione a cidade");
				}
			}
		});

		$(".btn-rm-cidade").click(removeCidade);

		$(".btn-rm-estado-participante").click(function(){
			var treinamento = $("#treinamento").val();
			var linhaEstadoTreinamento = this;

			$.ajax({
	            method: "POST",
	            url: "ajax_treinamento.php",
	            data: { ajax: 'sim', acao: 'deletar_treinamento_estado', 'treinamento': treinamento},
	            timeout: 8000
	        }).done(function(data) {
	            data = JSON.parse(data);
	            if (data.success !== undefined) {
	                $(linhaEstadoTreinamento).parents("tr").remove();
	            }else if (data.erro == 'error') {
	                alert("Não foi possível remover a cidade");
	            }
        	});
		});


		var removePromotor = function(){
			var promotorTreinamento = $(this).data("promotor-treinamento");
			var linhaPromotorTreinamento = this;
			var treinamento = $("#treinamento").val();

			$.ajax({
	            method: "POST",
	            url: "ajax_treinamento.php",
	            data: { ajax: 'sim', acao: 'deletar_promotor_treinamento', promotor_treinamento: promotorTreinamento, 'treinamento': treinamento},
	            timeout: 8000
	        }).done(function(data) {
	            data = JSON.parse(data);
	            if (data.success !== undefined) {
	                $(linhaPromotorTreinamento).parents("tr").remove();
	            }else if (data.erro == 'error') {
	                alert("Não foi possível remover o promotor");
	            }
        	});
		}

		$("#btn-add-promotor").click(function(){
			var promotor = $("#promotor option:selected").html();
			var promotorId = $("#promotor option:selected").val();

			if(promotorId == ""){
				alert("Escolha o promotor");
				return false;
			}

			var erroPromotor = false;
			$("#tbl_promotores_participantes").find("tbody > tr").each(function(idx,elem){
				var td = $(elem).find(".td-promotor")[0];

				if($(td).data("promotorid") == promotorId){
					alert("Promotor já adicionado");
					erroPromotor = true;
				}
			});
			if(erroPromotor){

				return false;
			}

			var tr = $("<tr>");

			var td = $("<td class='td-promotor'>");
			$(td).data("promotorid",promotorId);
			$(td).html(promotor);
			$(tr).append(td);


			var btnRemove = $('<input type="button" name="btn-rm-promotor" class="btn btn-danger btn-mini btn-rm-promotor" value="Remover">');
			$(btnRemove).click(function(){
				$($(this).parents("tr")[0]).remove();
			});
			var td = $("<td class='tac'>");
			$(td).append(btnRemove);
			$(tr).append(td);

			$("#tbl_promotores_participantes").find("tbody").append(tr);

		});

		$(".btn-rm-promotor").click(removePromotor);

		<?php
	}
	?>
	setTimeout(function(){ Shadowbox.init(); }, 1000);

	function hint( sMessage ) {
		document.getElementById("display_hint").innerHTML = sMessage;
	}

	$('#bt_limpa_form').on('click', function(){
		if ($('#treinamento').val() !== '') {
			window.location.href = 'treinamento_cadastro.php';
		}else{
			$('#frm_relatorio').each (function(){
				this.reset();
			});
		}
	});

	// $(document).on("click","#listaRegiao", function(){
 //      listaRegiao();
 //  });
	// $(document).on("click","#listaEstado", function(){
 //      monta_estado();
 // //  });
 // $(document).on("click","#cidade", function(){
	// 	lista_cidade();
	// });
  	$(document).on("change","#listaRegiao", function(){
  		var regiao_campo = $("#listaRegiao").val();

  		if (regiao_campo !== "") {
	    	listaEstado(regiao_campo);
	    }else{
        	listaEstado();
          	$('#cidade').html("<option value=''>Selecione</option>");
      	}
  	});


  $(document).on("change","#listaEstado", function(){
  		lista_cidade();
  });
<?php if ( ($login_fabrica == 1) && (!empty($treinamento)) ) { ?>
  $(window).load(function() {

		if ($(".cod_posto").val() == undefined) {
			$(".bd_sel").multiselect('enable');
			$('#codigo_posto').attr("disabled",true);
			$('#descricao_posto').attr("disabled",true);
			$('.btn-lupa').attr("disabled",true);
			$('.btn-lupa').hide();
		} else {
			$(".bd_sel").multiselect('disable');
			$('#codigo_posto').removeAttr("disabled");
			$('#descricao_posto').removeAttr("disabled");
			$('.btn-lupa').removeAttr("disabled");
			$('.btn-lupa').show();
				
		}
	});

  <?php 
	}
  if(in_array($login_fabrica, array(169,170,193))){
  ?>
  	  var valorPrimarioEstado = $("#listaEstado").val()
  	  listaEstado();
  	  setTimeout(function(){
  	  	$("#listaEstado").val(valorPrimarioEstado);
  	  },500);

	  $(document).on("change","#listaEstadoParticipante", function(){
	  		lista_cidade(true);
	  });
  <?php
  }
  ?>

	$('#bt_cad_forn').on('click', function(){
		$('#bt_cad_forn').prop("disabled", true);
		gravar_treinamento();
	});

	// cancelar treinamento 
	<?php if (in_array($login_fabrica, array(169,170,193))){ ?> 
			$('#bt_canc_forn').on('click', function(){
				cancelar_treinamento();
			});
	<?php } ?>

	$('#bt_cad_forn2').on('click', function(){
		$('#bt_cad_forn2').prop("disabled", true);
		gravar_treinamento(1);
	});

    $(document).on('click','button.seleciona-treinamento', function(){
        var btn = $(this);
        var text = $(this).text();
        var treinamento = $(btn).data('treinamento');
        $(btn).prop({disabled: true}).text("Espere...");

        $.ajax({
            method: "GET",
            url: "ajax_treinamento.php",
            data: { ajax: 'sim', acao: 'ativa_desativa', treinamento: treinamento, id: 0}
        }).fail(function(){
			$("#success").hide();
			$("#erro").show().find('h4').html("Não foi possível confirmar/cancelar o treinamento, tempo esgotado!");
			setTimeout(function(){
				$('html, body').animate({
			        scrollTop: $("#erro").offset().top
			    }, 1000);
			},500);
        }).done(function(data) {
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                $(btn).prop({disabled: false}).text(data.ok);
                if (data.ok == 'Cancelado') {
                    $(btn).removeClass('btn-primary');
                    $(btn).addClass('btn-danger');
                    $(btn).parent("td").prev("td").find("img").attr({ src: "imagens_admin/status_vermelho.gif" });
                }else{
                    $(btn).addClass('btn-primary');
                    $(btn).removeClass('btn-danger');
                    $(btn).parent("td").prev("td").find("img").attr({ src: "imagens_admin/status_verde.gif" });
                }
            }else if (response.erro !== undefined) {
                $(btn).prop({disabled: false}).text(text);
				$("#success").hide();
				$("#erro").show().find('h4').html(response.erro);

				setTimeout(function(){
					$('html, body').animate({
				        scrollTop: $("#erro").offset().top
				    }, 1000);
				},500);

            }
        });
    });

    //Notificação
    $(document).on('click','button.envia-notificao', function(){
        var btn = $(this);
        var text = $(this).text();
        var treinamento = $(btn).data('url');

        var url = $(this).data('url');
        Shadowbox.open({
            content: url,
            player: 'iframe',
            width: 1024,
            height: 600
        });
    });


<?php if (in_array($login_fabrica, array(175))){ ?>
		function monta_estado(){
		  	listaEstado();
		    $('#cidade').html("<option value=''>Selecione</option>");
		  }
<?php }else{ ?>
		function monta_estado(){
		  	var regiao_campo = $("#listaRegiao").val();
		    if (regiao_campo !== "") {
		        listaEstado(regiao_campo);
		    }else{
		        listaEstado();
		        $('#cidade').html("<option value=''>Selecione</option>");
		    }
		  }
<?php } ?>
  



  function lista_cidade(participante){
  	if(participante == undefined){
  		var estados_campo = $("#listaEstado").val();
  	}else{
		var estados_campo = $("#listaEstadoParticipante").val();
  	}

    if (estados_campo !== "") {
        $.ajax({
            method: "GET",
            url: "ajax_treinamento.php",
            data: {ajax: "sim", acao: "consulta_cidades", estados: estados_campo, cadastro: "sim"},
            timeout: 8000
        }).fail(function(){
		$("#success").hide();
		$("#erro").show().find('h4').html("Ocorreu um erro ao tentar listar as cidades, tempo esgotado! Recarregue a pagina...");
        }).done(function(data){
            data = JSON.parse(data);
            if (data != null && data.messageError == undefined) {
            	var option = "<option value=''>Selecione uma cidade</option>";

                $.each(data,function(index,obj){
                    option += "<option value='"+obj.codigo+"'>"+obj.cidade+"</option>";
                });
                if(participante == undefined){
					$('#cidade').html(option);
                }else{
                	$('#cidade_participante').html(option);
                }
            }else{
				$("#success").hide();
				$("#erro").show().find('h4').html("Ocorreu um erro ao tentar listar as cidades! Recarregue a pagina...");
            }
        });
    }else{
        $('#cidade').html("<option value=''>Selecione</option>");
    }
  }

	$(document).on("change",".tipo_posto_bd", function(){
		var valor = $(this).val();
		var codigo_posto = $(".cod_posto").val();

		if (valor == "" || valor == null) {
			$('#codigo_posto').removeAttr("disabled");
			$('#descricao_posto').removeAttr("disabled");
			$('.btn-lupa').removeAttr("disabled");
			$('.btn-lupa').show();
			return false;
		}  
			$('#codigo_posto').attr("disabled",true);
			$('#descricao_posto').attr("disabled",true);
			$('.btn-lupa').attr("disabled",true);
			$('.btn-lupa').hide();	
	});

	function gravar_treinamento(maquita = 0) {
		<?php if (in_array($login_fabrica, [169,170,193]) && $editar_campos == true) { /*HD - 6261912*/ ?>
			habilitarCampos();
		<?php } ?>

		var acao = "cadastrar";
		if (maquita == 1) {
			acao = "cadastrar_makita";
		}

		var tema = $("#titulo").val();
		var data_inicial_campo = $("#data_inicial").val();
		var data_final_campo = $("#data_final").val();
		var linha_campo = "";
				$("#linha_bd").find("option:selected").each(function(idx,elem){
					linha_campo += "&select_linha[]="+$(elem).val();
				});
		var descricao_campo = $("#descricao").val();
		var vagas_campo = $("#qtde").val();
		var regiao = $("#listaRegiao").val();
		var estado = $("#listaEstado").val();
		var cidade = $("#cidade").val();
		var macro_linha = $('#macro_linha').val();

		var msg_erro = false;

		<?php if (in_array($login_fabrica, array(169,170,193))){ ?>
				var qtde_min_participantes = $("#qtde_min").val();
				var carga_horaria          = $("#carga_horaria").val();
				var local_treinamento      = $("#local").val();
		<?php } ?>

		<?php if (in_array($login_fabrica, array(175))){ ?>
				var treinamento_tipo     		= $("#treinamento_tipo").val();
				var validade_treinamento 		= $("#validade_treinamento").val();
				var inicio_inscricao     		= $("#inicio_inscricao").val();
				var prazo_inscricao      		= $("#prazo_inscricao").val();
				var horario_inicial      		= $("#horario_inicial").val();
				var horario_final        		= $("#horario_final").val();
				var local_treinamento    		= $("#local").val();
				var produtos 					= $("#produto").val();
				var descricao_tipo_treinamento 	= $("#treinamento_tipo option:selected").text().toLowerCase();
				
				if(treinamento_tipo == ''){
					msg_erro = true;
					$("#treinamento_campo").addClass('error');
					$("#div_treinamento_tipo").addClass('error');

					if (data_inicial_campo == "" || data_inicial_campo == undefined){
						msg_erro = true;
						$("#data_inicial_picker").addClass('error');
					}
					if (data_final_campo == "" || data_final_campo == undefined){
						msg_erro = true;
						$("#data_final_picker").addClass('error');
					}

				}

				if (produtos == '' || produtos == undefined){
					msg_erro = true;
					//$("#div_produto").addClass('error');
					$("#div_produto").find(".select2-selection--multiple").addClass("error-multiple");
				}

				if(validade_treinamento == ''){
					msg_erro = true;
					$("#div_validade_treinamento").addClass('error');
				}	

				if (descricao_tipo_treinamento == "presencial"){

					if(inicio_inscricao == ''){
						msg_erro = true;
						$("#div_inicio_inscricao").addClass('error');
					}

					if(prazo_inscricao == ''){
						msg_erro = true;
						$("#div_prazo_inscricao").addClass('error');
					}

					if(vagas_campo == ''){
						msg_erro = true;
						$("#vagas").addClass('error');
					}

					if (estado == '') {
						msg_erro = true;
						$("#descricao_estado").addClass('error');
					}

					if (cidade == '') {
						msg_erro = true;
						$("#descricao_cidade").addClass('error');
					}

					if (local_treinamento == '') {
						msg_erro = true;
						$("#local_treinamento_erro").addClass('error');
					}

					if(data_inicial_campo == ''){
						msg_erro = true;
						$("#data_inicial_campo").addClass('error');
					}
					if(data_final_campo == ''){
						msg_erro = true;
						$("#data_final_campo").addClass('error');
					}
				}
		<?php } ?>

		if (typeof $('#macro_linha') == 'object' && macro_linha == '') {
			msg_erro = true;
			$("#macro_linha_campo").addClass('error');
		}

		if(tema == ''){
			msg_erro = true;
			$("#tema").addClass('error');
		}

		<?php if ($login_fabrica != 175){ ?>
			if(data_inicial_campo == ''){
				msg_erro = true;
				$("#data_inicial_campo").addClass('error');
			}
			if(data_final_campo == ''){
				msg_erro = true;
				$("#data_final_campo").addClass('error');
			}
		<?php } ?>
		<?php if (!in_array($login_fabrica, [1])){?>
			$("#linha").find("option:selected").each(function(idx,elem){
				linha_campo += "&select_linha[]="+$(elem).val();
			});
		<?php } ?>

		<?php
		if (!in_array($login_fabrica, [1,175])) { ?>
			if(linha_campo == ''){
				msg_erro = true;
				$("#linha_campo").addClass('error');
			}
		<?php
		} ?>

		<?php if (in_array($login_fabrica, array(169,170,193))){ ?>
				if(qtde_min_participantes == ''){
					msg_erro = true;
					$("#div_min_participantes").addClass('error');
				}

				if (carga_horaria == ''){
                                        msg_erro = true;
                                        $("#div_carga_horaria").addClass('error');      
                                }

                                if(local_treinamento == ''){
                                        msg_erro = true;
                                        $("#div_local_treinamento").addClass('error');
                                }

		<?php } 

			if (!in_array($login_fabrica, array(175))){ ?>
				if(vagas_campo == ''){
					msg_erro = true;
					$("#vagas").addClass('error');
				}
	<?php   } ?>
		
		if(descricao_campo == ''){
			msg_erro = true;
			$("#descricao_campo").addClass('error');
		}

		<?php if(!in_array($login_fabrica, array(138,175)) ){ ?>
			if (regiao == '') {
				msg_erro = true;
				$("#descricao_regiao").addClass('error');
			}
		<?php } ?>

		<?php if (!in_array($login_fabrica, array(175))){ ?>
				if (estado == '') {
					msg_erro = true;
					$("#descricao_estado").addClass('error');
				}
		<?php } ?>

		<?php
		if(in_array($login_fabrica, array(169,170,193))){
			?>

			var cidades = [];
			var estado_participante = [];
			$("#tbl_cidades_participantes").find("tbody > tr").each(function(idx,elem){

				if($(elem).find(".btn-rm-cidade"),length > 0 && $(elem).find(".btn-rm-cidade").data('treinamento-cidade') == undefined){
					var td = $(elem).find(".td-cidade")[0];

					if($(td).data("cidadeId") != "" && $(td).data("cidadeId") != null){
						cidades.push($(td).data("cidadeId"));
					}else{
						td = $(elem).find(".td-estado")[0];
						estado_participante.push($(td).data("estado"));
					}
				}
			});

			var promotores = [];
			$("#tbl_promotores_participantes").find("tbody > tr").each(function(idx,elem){
				if($(elem).find(".btn-rm-promotor").data('promotor-treinamento') == undefined){
					var td = $(elem).find(".td-promotor")[0];

					promotores.push($(td).data("promotorid"));
				}
			});


			var inputCidades = $("<input type='hidden' id='cidades_hidden' name='cidades' />");
			$(inputCidades).val(JSON.stringify(cidades));

			var inputEstado = $("<input type='hidden' id='estado_hidden' name='estado_participante' />");
			$(inputEstado).val(estado_participante);

			var inputPromotores = $("<input type='hidden' id='promotores_hidden' name='promotores' />")
			$(inputPromotores).val(JSON.stringify(promotores));

			$('#frm_relatorio').append(inputCidades);
			$('#frm_relatorio').append(inputPromotores);
			$('#frm_relatorio').append(inputEstado);
			<?php
		}else{
				if (!in_array($login_fabrica, array(175))){ ?>
					if (cidade == '') {
						msg_erro = true;
						$("#descricao_cidade").addClass('error');
					}
		<?php
				}
		}
		?>

		if(msg_erro == true){
			//$('#bt_cad_forn').prop("disabled", false);
			$('input[name=bt_cad_forn]').prop("disabled", false);
			$("#erro").show().find('h4').html("Preencha os campos obrigatórios.");
			return;
		}

		$.ajax({
			url: "ajax_treinamento.php",
			data: $('#frm_relatorio').serialize()+"&acao="+acao+"&ajax=sim",
			method: "GET",
		}).fail(function(){
			//$('#bt_cad_forn').prop("disabled", false);
			$('input[name=bt_cad_forn]').prop("disabled", false);
			$("#success").hide();
			$("#erro").show().find('h4').html("Erro ao tentar gravar, tempo esgotado!");
			setTimeout(function(){
				$('html, body').animate({
			        scrollTop: $("#erro").offset().top
			    }, 1000);
			},500);
		}).done(function(response){
			response = JSON.parse(response);
			if (response.ok !== undefined) {
				$("#erro").hide();
				$("#success").show().find('h4').html(response.ok);
				alert(response.ok);
				$('#frm_relatorio').each (function(){
					this.reset();
				});
				mostrar_treinamento();
				window.setTimeout('location.reload()', 500); //HD-3261932
				exit();
			}else if (response.error !== undefined){
				$("#success").hide();
				$("#erro").show().find('h4').html(response.error);
				setTimeout(function(){
					$('html, body').animate({
				        scrollTop: $("#erro").offset().top
				    }, 1000);
				},500);
			}
			//$('#bt_cad_forn').prop("disabled", false);
			$('input[name=bt_cad_forn]').prop("disabled", false);
		});

		$("#cidades_hidden").remove();
		$("#promotores_hidden").remove();
	}

	function cancelar_treinamento() {
		// alert
		let motivoCancelamento = prompt('Deseja cancelar esse treinamento?');
		if (!!motivoCancelamento){
			// desabilitando campos
			$('#bt_cad_forn').prop("disabled", true);
			$('#bt_canc_forn').html('Cancelando <i class="fas fa-circle-notch fa-spin"></i>');
			$('#bt_limpa_form').prop("disabled", true);
			
			var acao = "cancelar";
			$.ajax({
				url: "ajax_treinamento.php",
				data: $('#frm_relatorio').serialize()+"&acao="+acao+"&ajax=sim"+"&motivo="+motivoCancelamento,
				method: "GET",
			}).fail(function(){
				//$('#bt_cad_forn').prop("disabled", false);
				$('input[name=bt_cad_forn]').prop("disabled", false);
				$('#bt_canc_forn').html('Cancelar');
				$('#bt_limpa_form').prop("disabled", false);
				$("#success").hide();
				$("#erro").show().find('h4').html("Erro ao tentar Cancelar o treinamento, tempo esgotado!");
				setTimeout(function(){
					$('html, body').animate({
				        scrollTop: $("#erro").offset().top
				    }, 1000);
				},500);
			}).done(function(response){
				response = JSON.parse(response);
				if (response.ok !== undefined) {
					$("#erro").hide();
					$("#success").show().find('h4').html(response.ok);
					alert(response.ok);
					$('#frm_relatorio').each (function(){
						this.reset();
					});
					mostrar_treinamento();
					window.setTimeout('location.reload()', 500); //HD-3261932
				}else if (response.error !== undefined){
					$("#success").hide();
					$("#erro").show().find('h4').html(response.error);
					setTimeout(function(){
						$('html, body').animate({
					        scrollTop: $("#erro").offset().top
					    }, 1000);
					},500);
				}
				//$('#bt_cad_forn').prop("disabled", false);
				$('input[name=bt_cad_forn]').prop("disabled", false);
				$('#bt_canc_forn').html('Cancelar');
				$('#bt_limpa_form').prop("disabled", false);
			});
		}
	}

	function mostrar_treinamento() {
		$("#dados").html("Carregando<br><img src='imagens/carregar2.gif'>");
		$.ajax({
			url: "ajax_treinamento.php",
			data: {ajax: "sim", acao: "ver"},
			method: "GET",
			timeout: 10000
		}).fail(function(){
			$("#success").hide();
			$("#erro").show().find('h4').html("Erro ao tentar listar os treinamentos, tempo esgotado!");
			$("#dados").html("");
		}).done(function(response){
			response = JSON.parse(response);
			if (response.ok !== undefined) {
				$("#dados").html(response.ok);
				<?php if (in_array($login_fabrica, array(169,170,193))){ ?>
                    orderTable('#tblTreinamento');
                <?php } ?>
			}else{
				$("#dados").html("");
			}
		});
	}

    function listaRegiao(){
        if ($('#listaRegiao').length !== 0) {
            $.ajax({
                method: "GET",
                url: "ajax_treinamento.php",
                data: {ajax: "sim", acao: "consulta_regiao"},
                timeout: 8000
            }).fail(function(){
				$("#success").hide();
				$("#erro").show().find('h4').html("Não foi possível listar as regiões, tempo esgotado! Recarregue a pagina...");
            }).done(function(data){
                data = JSON.parse(data);
                if (data.ok !== undefined) {
                    $('#listaRegiao').html(data.ok);
                }else{
					$("#success").hide();
					$("#erro").show().find('h4').html(data.erro);
                }
            });
        }
    }

    function listaEstado(estado = ""){
		var estado_treinamento = $("#estado_treinamento").val();

		if (estado_treinamento != "" && estado_treinamento != undefined) {
			var treinamento_id = '<?=$_GET['treinamento']?>';
		}

        $.ajax({
            method: "GET",
            url: "ajax_treinamento.php",
            data: {ajax: "sim", acao: "consulta_estados", estados: estado, treinamento_id: treinamento_id},
            timeout: 8000
        }).fail(function(){
			$("#success").hide();
			$("#erro").show().find('h4').html("Não foi possível listar os estados, tempo esgotado! Recarregue a pagina...");
        }).done(function(data){
            data = JSON.parse(data);
            if (data.messageError == undefined) {
                var select = "<select id='estado' name='estado' class='frm'>";
                select += "<option value=''>Selecione um estado</option>";

                $.each(data,function(index,obj){
                	if (obj.cod_estado == "tem_posto") {
                		$("#listaEstado").css('pointer-events', 'none');
                		$("#listaEstado").css('touch-action', 'none');
                	}

                	if (obj.cod_estado == estado_treinamento) {
                		var selected_id = "SELECTED";
                	}
                    	select += "<option "+selected_id+" value='"+obj.cod_estado+"'>"+obj.estado+"</option>";
                		
	            });
	                
	                select += "</select>";
	                $('#listaEstado').html(select);
                		
            }else{
				$("#success").hide();
				$("#erro").show().find('h4').html("Ocorreu um erro ao tentar listar os estados! Recarregue a pagina...");
            }
        });
    }
</script>

<p>
<?php include "rodape.php"; ?>
