<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro,auditoria";
include 'autentica_admin.php';
include "funcoes.php";

$msg_erro = array();

$layout_menu = "financeiro";
$title = traduz("RELATÓRIO DE GASTOS COM CONSERTOS");
include "cabecalho_new.php";
$plugins = array(
	"multiselect",
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

#mostrando datas na tela Marisa/Waldir decidimos tirar
#include "programa_desempenho.php";

$pais = $_POST["pais"];
if(strlen($pais)==0){
	$pais = $_REQUEST["pais"];
}

$tipo = $_POST["tipo"];
if(strlen($tipo)==0){
	$tipo = $_REQUEST["tipo"];
}

if($_POST["btn_acao"]=="Pesquisar"){
	##### Pesquisa entre datas #####
	$x_data_inicial = trim($_REQUEST["data_inicial"]);
	$x_data_final   = trim($_REQUEST["data_final"]);
	if(in_array($login_fabrica, array(15, 169, 170))) {
		if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

			if (strlen($x_data_inicial) > 0) {
				$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
				$x_data_inicial = str_replace("'", "", $x_data_inicial);
				$data_inicial   = $x_data_inicial.' 00:00:00';
				$dia_inicial    = substr($x_data_inicial, 8, 2);
				$mes_inicial    = substr($x_data_inicial, 5, 2);
				$ano_inicial    = substr($x_data_inicial, 0, 4);
		//		$data_inicial = date("01/m/Y H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
			}else{
				$msg_erro["msg"][] = traduz("Preencha os campos obrigatórios");
				$msg_erro["campos"][] = "data";
			}

			if (strlen($x_data_final) > 0) {
				$x_data_final = fnc_formata_data_pg($x_data_final);
				$x_data_final = str_replace("'", "", $x_data_final);
				$data_final   = $x_data_final.' 23:59:59';
				$dia_final    = substr($x_data_final, 8, 2);
				$mes_final    = substr($x_data_final, 5, 2);
				$ano_final    = substr($x_data_final, 0, 4);
		//		$data_final   = date("t/m/Y H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
			}else{
				if(count($msg_erro) == 0 ){
					$msg_erro["msg"][] = traduz("Preencha os campos obrigatórios");
					$msg_erro["campos"][] = "data";
				}

			}
			
			if (in_array($login_fabrica, array(169, 170)) && empty($msg_erro['msg']) && !empty($x_data_final) && !empty($x_data_inicial)) {
				if (strtotime($x_data_inicial.' +6 months') < strtotime($x_data_final)) {
					$msg_erro["msg"][] = traduz("O intervalo entre a data inicial e final não pode ser superior a 6 meses");
					$msg_erro["campos"][] = "data";
				}
			}
		}else{
			$msg_erro["msg"][] = traduz("Data inválida");
			$msg_erro["campos"][] = "data";
		}
	}else{
        if($login_fabrica == 1){
            $x_marca = $_POST['marca'];
        }
		if(empty($_REQUEST['mes']) or empty($_REQUEST['ano']) ) {
			$msg_erro["msg"][] = traduz("Preencha os campos obrigatórios");
			$msg_erro["campos"][] = "data";
		}
	}
}

?>
<script type="text/javascript">

function AbrePosto(ano,mes,estado,linha){
	janela = window.open("gasto_por_posto_estado.php?ano=" + ano + "&mes=" + mes + "&estado=" + estado+ "&linha=" + linha,"Gasto",'width=700,height=300,top=0,left=0, scrollbars=yes' );
	janela.focus();
}

$(function(){
    $.datepickerLoad(Array("data_final", "data_inicial"));
    Shadowbox.init();

    $("#linha").multiselect({
        selectedText: "selecionados # de #"
	});
	
	<?php
	if (in_array($login_fabrica, array(169, 170))) {
	?>
		$(document).on('click', 'span[rel=lupa]', function() {
			$.lupa($(this));
		});
		
		$(document).on('click', 'span[rel=trocar_posto]', function() {
			$('#posto_id, #posto_codigo, #posto_nome').val('');
		
			$('#posto_codigo, #posto_nome')
			.prop({ readonly: false })
			.next('span[rel=trocar_posto]')
			.attr({ rel: 'lupa' })
			.find('i')
			.removeClass('icon-remove')
			.addClass('icon-search')
			.removeAttr('title');
		});
		
		window.retorna_posto = function(retorno) {
			$('#posto_id').val(retorno.posto);
			$('#posto_codigo').val(retorno.codigo);
			$('#posto_nome').val(retorno.nome);
		
			$('#posto_codigo, #posto_nome')
			.prop({ readonly: true })
			.next('span[rel=lupa]')
			.attr({ rel: 'trocar_posto' })
			.find('i')
			.removeClass('icon-search')
			.addClass('icon-remove')
			.attr({ title: 'Trocar Posto' });
		}
	<?php
	}
	?>
});
</script>

<?
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}?>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz("Campos obrigatórios")?> </b>
</div>
<form name='frm_percentual' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
<div class='titulo_tabela '><?=traduz("Parâmetros de Pesquisa")?></div>
		<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>

					<?

					/*--------------------------------------------------------------------------------
					selectMesSimples()
					Cria ComboBox com meses de 1 a 12
					--------------------------------------------------------------------------------*/
					function selectMesSimples($selectedMes){
						for($dtMes=1; $dtMes <= 12; $dtMes++){
							$dtMesTrue = ($dtMes < 10) ? "0".$dtMes : $dtMes;

							echo "<option value=$dtMesTrue ";
							if ($selectedMes == $dtMesTrue) echo "selected";
							echo ">$dtMesTrue</option>\n";
						}
					}
				if(!in_array($login_fabrica, array(15, 169, 170))){ ?>
				<label class='control-label' for='descricao_posto'><?=traduz("Mês")?></label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select name='mes' class="span6">
						<option value=''></option>
						<? selectMesSimples($mes); ?>
					</select>
				</div>
			<? }else{ ?>
				<label class='control-label' for='descricao_posto'><?=traduz("Data Inicial")?></label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_inicial" id="data_inicial" maxlength="10" value="<?=$_POST['data_inicial']?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
				</div>
			<? } ?>
			</div>
		</div>

		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>

				<?
				/*--------------------------------------------------------------------------------
				selectAnoSimples($ant,$pos,$dif,$selectedAno)
				// $ant = qtdade de anos retroceder
				// $pos = qtdade de anos posteriores
				// $dif = ve qdo ano termina
				// $selectedAno = ano já setado
				Cria ComboBox com Anos
				--------------------------------------------------------------------------------*/
				function selectAnoSimples($ant,$pos,$dif=0,$selectedAno)
				{
					$startAno = date("Y"); // ano atual
					for($dtAno = $startAno - $ant; $dtAno <= $startAno + ($pos - $dif); $dtAno++){
						echo "<option value=$dtAno ";
						if ($selectedAno == $dtAno) echo "selected";
						echo ">$dtAno</option>\n";
					}
				}
				if(!in_array($login_fabrica, array(15, 169, 170))){?>
				<label class='control-label' for='descricao_posto'><?=traduz("Ano")?></label>
				<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<select name='ano' class="span6">
							<option value=''></option>
				<? selectAnoSimples(1,0,'',$ano) ?>
						</select>
				</div>
				<?}else{?>
				<label class='control-label' for='descricao_posto'><?=traduz("Data Final")?></label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
						<input type="text" name="data_final" id="data_final" maxlength="10" value="<?=$_POST['data_final']?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" >
				</div>
				<? } ?>

			</div>
		</div>

		<div class='span2'></div>
	</div>
	<div class="row-fluid">
        <!-- margem -->
        <div class="span2"></div>
<?php
        if($login_fabrica == 1){

            $sqlMarca = "
                SELECT  marca,
                        nome
                FROM    tbl_marca
                WHERE   fabrica = $login_fabrica;
            ";
            $resMarca = pg_query($con,$sqlMarca);
            $marcas = pg_fetch_all($resMarca);
?>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='marca'><?=traduz("Marca")?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select name="marca" id="marca">
                                <option value=""><?=traduz("ESCOLHA")?></option>
<?
                            foreach($marcas as $chave => $valor){
?>
                                <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $x_marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
                            }
?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
<?
        }
?>
        <div class="span4">
            <div class="control-group">
                <div class="controls controls-row tac">
        			<div class='span12'>
                        <div class='control-group '>
                            <?php
                            if ($login_fabrica == 117) {
								$joinElgin = "
									JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
									JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
								";
                            	?>
                                <label class='control-label' for='linha '><?=traduz("Macro - Família")?></label>
                            <?php
                            } else {
							?>
                            	<label class='control-label' for='linha '><?=traduz("Linha")?></label>
                            <?php
                            }
                            ?>
                            <div class='controls controls-row'>
								<?
								$sql_linha = "SELECT DISTINCT
													tbl_linha.linha,
													tbl_linha.nome
											FROM tbl_linha
											$joinElgin
											WHERE tbl_linha.fabrica = $login_fabrica
											ORDER BY tbl_linha.nome ";
								$res_linha = pg_query($con, $sql_linha);
								?>
								
                                <select name="linha[]" id="linha" multiple="multiple" class='span12'>
									<?php
									$selected_linha = array();
									foreach (pg_fetch_all($res_linha) as $key) {
										if(isset($linha)){
											foreach ($linha as $id) {
												if ( isset($linha) && ($id == $key['linha']) ){
													$selected_linha[] = $id;
												}
											}
										}
										?>
                                    	<option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> ><?=$key['nome']?></option>
									<?php
									}
									?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
		</div>
		
		<?php
		if (in_array($login_fabrica, array(169,170))) {
		?>
			<div class='span4' >
				<div class='control-group' >
					<label class='control-label' for='inspetor' ><?=traduz("Inspetor")?></label>
					<div class='controls controls-row' >
						<div class='span12' >
							<select id='inspetor' name='inspetor' class='span12' />
								<option value='' ><?=traduz("Selecione")?></option>
								<?php
								$sql = "
									SELECT admin, nome_completo
									FROM tbl_admin
									WHERE fabrica = {$login_fabrica}
									AND ativo IS TRUE
									AND admin_sap IS TRUE
									ORDER BY login
								";
								$res = pg_query($con, $sql);
								if (pg_num_rows($res) > 0) {
									while ($row = pg_fetch_object($res)) {
										$selected = (getValue('inspetor') == $row->admin) ? 'selected' : '';
										echo "<option value='{$row->admin}' {$selected} >{$row->nome_completo}</option>";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		<?php
		}
		?>

        <!-- margem -->
        <div class="span2"></div>
    </div>
    <? if(($login_fabrica == 24) || ($login_fabrica == 20 && $login_admin == 590)) { ?>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span2'>
			<div class='control-group'>

				<? if($login_fabrica == 24){ ?>
					<label class='control-label' for='descricao_posto'><?=traduz("Tipo")?></label>
					<div class='controls controls-row'>
						<select name='tipo' class="frm">
							<option value='T'><?=traduz("Todas")?></option>
							<option value='C' <?if($tipo == 'C'){echo "selected";}?>><?=traduz("Consumidor")?></option>
							<option value='R' <?if($tipo == 'R'){echo "selected";}?>><?=traduz("Revenda")?></option>
						</select>
					</div>
				<? }

				if($login_fabrica ==20 and $login_admin ==590){ ?>
					<label class='control-label' for='descricao_posto'><?=traduz("País")?></label>
					<div class='controls controls-row'>
					<?
						$sql = "SELECT  *
								FROM    tbl_pais
								ORDER BY tbl_pais.nome;";
						$res = pg_query ($con,$sql);

						if (pg_num_rows($res) > 0) { ?>
							<select name='pais'> <?
							if(strlen($pais) == 0 ) {
								$pais = 'BR';
							}

							for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
								$aux_pais  = trim(pg_fetch_result($res,$x,pais));
								$aux_nome  = trim(pg_fetch_result($res,$x,nome));

								echo "<option value='$aux_pais'";
								if ($pais == $aux_pais){
									echo " SELECTED ";
									$mostraMsgPais = "<br>".traduz("do PAÍS")." $aux_nome";
								}
								echo ">$aux_nome</option>";
							}?>
							</select>
						<? } ?>
					</div>
				<? } ?>
			</div>
		</div>
	</div>
	<? 
	} 
	
	if (in_array($login_fabrica, array(169,170))) {
		$tem_valor_km = true;
		$extrato_sem_peca = "t";
		?>
		<div class='row-fluid' >
			<div class='span2' ></div>
			<?php
			if (strlen(getValue('posto_id')) > 0) {
				$posto_input_readonly     = 'readonly';
				$posto_span_rel           = 'trocar_posto';
				$posto_input_append_icon  = 'remove';
				$posto_input_append_title = 'title="Trocar Posto"';
			} else {
				$posto_input_readonly     = '';
				$posto_span_rel           = 'lupa';
				$posto_input_append_icon  = 'search';
				$posto_input_append_title = '';
			}
			?>
			<div class='span3' >
				<div class='control-group' >
					<label class='control-label' for='posto_codigo' ><?=traduz("Código do Posto")?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input id='posto_codigo' name='posto_codigo' class='span12' type='text' value='<?=getValue("posto_codigo")?>' <?=$posto_input_readonly?> />
							<span class='add-on' rel='<?=$posto_span_rel?>' >
								<i class='icon-<?=$posto_input_append_icon?>' <?=$posto_input_append_title?> ></i>
							</span>
							<input type='hidden' name='lupa_config' tipo='posto' parametro='codigo' />
							<input type='hidden' id='posto_id' name='posto_id' value='<?=getValue("posto_id")?>' />
						</div>
					</div>
				</div>
			</div>
			<div class='span4' >
				<div class='control-group' >
					<label class='control-label' for='posto_nome' ><?=traduz("Nome do Posto")?></label>
					<div class='controls controls-row' >
						<div class='span10 input-append' >
							<input id='posto_nome' name='posto_nome' class='span12' type='text' value='<?=getValue("posto_nome")?>' <?=$posto_input_readonly?> />
							<span class='add-on' rel='<?=$posto_span_rel?>' >
								<i class='icon-<?=$posto_input_append_icon?>' <?=$posto_input_append_title?> ></i>
							</span>
							<input type='hidden' name='lupa_config' tipo='posto' parametro='nome' />
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<div class='row-fluid' >
			<div class='span2' ></div>
			<div class='span4' >
				<div class='control-group' >
					<label class='control-label' for='estado' ><?=traduz("Estado")?></label>
					<div class='controls controls-row' >
						<div class='span12' >
							<select id='estado' name='estado' class='span12' />
								<option value='' ><?=traduz("Selecione")?></option>
								<?php
								$options = $array_estados();
								foreach ($options as $value => $label) {
									$selected = (getValue('estado') == $value) ? 'selected' : '';
									echo "<option value='{$value}' {$selected} >{$label}</option>";
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
	?>
	<input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
	<div class="row-fluid">
        <!-- margem -->
        <div class="span4"></div>

        <div class="span4">
            <div class="control-group">
                <div class="controls controls-row tac">
                    <button type="button" class="btn" value="Gravar" alt="Gravar formulário" onclick="submitForm($(this).parents('form'),'Pesquisar');" > <?=traduz("Pesquisar")?></button>
                </div>
            </div>
        </div>

        <!-- margem -->
        <div class="span4"></div>
    </div>
      <?
# hd 190381 - samuel
if($login_fabrica != 30){?>
	<div class="row-fluid">
        <div class="span12">
            <div class="control-group">
                <div class="controls controls-row tac">
                	<span class="label label-info">(*) <?=traduz("O critério de busca deste relatório referente ao mês é data de aprovação do extrato!")?></span>
                </div>
            </div>
        </div>
      </div>
<? } ?>
</form>
<center>

<?
//HD 3237 - SE LOGIN FOR DIFERENTE DE SAMEL, DEVERÁ SER APENAS PARA O BRASIL
if(strlen($pais) == 0 ) {
	$pais = 'BR';
}
flush();

$join_pais = "  ";
$cond_pais = " 1=1 ";
	// HD 3237 - ADICIONAR FILTRO POR PAIS
	if($login_fabrica ==20){
	$join_pais = " JOIN tbl_posto    ON tbl_posto.posto = tbl_os.posto ";
	$cond_pais = " tbl_posto.pais = '$pais' " ;
}

// condição tipo de OS consumidor ou revenda
$cond_tipo = " AND 1=1 ";
	if($login_fabrica ==24 and $tipo <> 'T'){
		if($tipo == 'C'){
			$cond_tipo = " AND tbl_os.consumidor_revenda = 'C' OR tbl_os.consumidor_revenda IS NULL " ;
		}else{
			$cond_tipo = " AND tbl_os.consumidor_revenda = 'R' " ;
		}
	}

//if(strlen($x_data_final)>0 AND strlen($x_data_inicial)>0){
//	$sql = "select '$x_data_final'::date - '$x_data_inicial'::date ";
//	$res = pg_query($con,$sql);
//	if(pg_fetch_result($res,0,0)>90)$msg_erro = "Período não pode ser maior que 90 dias";
//}

if($login_fabrica <> 15){
	//$msg_erro = 0;
}else{
	if(strlen($x_data_inicial) > 0 OR strlen($x_data_final) > 0){
		// echo "$msg_erro";
	}
}

//if (strlen ($linha) == 0) $linha = 'tbl_linha.linha';

if ((strlen($mes) > 0 AND strlen($ano) > 0) OR (strlen($msg_erro) == 0 AND in_array($login_fabrica, array(15,169,170)))){

	if(!in_array($login_fabrica, array(15,169,170))){
		$data_inicial = $ano . "-" . $mes . "-01 00:00:00";
		$res = pg_query ($con,"SELECT ('$data_inicial'::date + interval '1 month' - interval '1 day')::date");
		$data_final = pg_fetch_result ($res,0,0);
		$data_final = $data_final . " 23:59:59";
	}

	if(strlen($marca) > 0){
        $cond_marca = "AND tbl_produto.marca = $marca";
	}

    if(count($linha) > 0 ) {
        $condJoinLinha = " IN (";
        for($i = 0; $i < count($linha); $i++){
            if($i == count($linha)-1 ){
                $condJoinLinha .= $linha[$i].")";
            }else {
                $condJoinLinha .= $linha[$i].", ";
            }
        }
        $cond_linha .=	" AND tbl_produto.linha {$condJoinLinha} ";
	}
	
	if (!empty($_POST['posto_id'])) {
		$posto_id = $_POST['posto_id'];
		$cond_posto = "AND tbl_extrato.posto = {$posto_id}";
	}
	
	if (!empty($_POST['inspetor'])) {
		$inspetor = $_POST['inspetor'];
		$cond_inspetor = "AND tbl_posto_fabrica.admin_sap = $inspetor";
	}
	
	if (!empty($_POST['estado'])) {
		$estado = $_POST['estado'];
		$cond_estado = "AND tbl_posto_fabrica.contato_estado = '{$estado}'";
	}

	if($login_fabrica == 42 or $login_fabrica ==50){
		$custo_peca = "custo_peca";
		$cond_posto = " AND tbl_extrato.posto <> 59959 ";
	}else{
			$custo_peca = "pecas";
	}

	if($login_fabrica == 42 or $login_fabrica ==50){
		$join_os_produto = "JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto";
	}

	if($login_fabrica == 42) {
		$data_extrato = " tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
	}else {
		$data_extrato = " tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'";
	}

	$sql = "SELECT os
			INTO TEMP tmp_gpp_os_extrato_aprovada_$login_admin
			FROM   tbl_os_extra
			JOIN   tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_os_extra.i_fabrica = tbl_extrato.fabrica
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			WHERE  $data_extrato
			AND    tbl_extrato.fabrica = $login_fabrica
			$cond_posto
			$cond_inspetor
			$cond_estado
			;

			CREATE INDEX tmp_gpp_os_extrato_aprovada_OS_$login_admin ON tmp_gpp_os_extrato_aprovada_$login_admin(os);
	
			SELECT os, mao_de_obra, pecas, consumidor_revenda,produto,posto,fabrica,custo_peca, qtde_km_calculada
			INTO temp tmp_gpp_os_os_$login_admin
			FROM tbl_os
			JOIN tmp_gpp_os_extrato_aprovada_$login_admin USING(os);
			
			CREATE INDEX tmp_gpp_os_os_produto_$login_admin ON tmp_gpp_os_os_$login_admin(produto);
			";

	/*HD: 55221 - PARA A BLACK NÃO É GRAVADO O CAMPO CUSTO_PECA NA OS*/
	/*HD 55221 ALTERADO FORAM SEPARADOS OS SELECTS e ALTERADO O CALCULO DA MEDIA*/
	if($login_fabrica == 1){
		$sql .= "
				ALTER TABLE tmp_gpp_os_extrato_aprovada_$login_admin ADD column custo_peca double precision;

				SELECT tbl_os.os,
				SUM(tbl_os_item.custo_peca * tbl_os_item.qtde) as custo_peca
				INTO TEMP tmp_gpp_os_custo_extrato_aprovada_$login_admin
				FROM tbl_os
				JOIN tmp_gpp_os_extrato_aprovada_$login_admin ON tmp_gpp_os_extrato_aprovada_$login_admin.os = tbl_os.os
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
				LEFT JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE tbl_os.fabrica = $login_fabrica
				GROUP BY tbl_os.os;

				CREATE INDEX tmp_gpp_os_custo_extrato_aprovada_OS_$login_admin ON tmp_gpp_os_custo_extrato_aprovada_$login_admin(os);

				SELECT tmp_gpp_os_custo_extrato_aprovada_$login_admin.os,
				CASE WHEN custo_peca IS NULL THEN 0 ELSE custo_peca END
				INTO TEMP tmp_gpp_os_os_custo_extrato_aprovada_$login_admin
				FROM tmp_gpp_os_custo_extrato_aprovada_$login_admin;

				CREATE INDEX tmp_gpp_os_os_custo_extrato_aprovada_OS_$login_admin ON tmp_gpp_os_os_custo_extrato_aprovada_$login_admin(os);

				UPDATE tmp_gpp_os_extrato_aprovada_$login_admin SET custo_peca= x.custo_peca
				FROM (
					SELECT tmp_gpp_os_os_custo_extrato_aprovada_$login_admin.os,
					tmp_gpp_os_os_custo_extrato_aprovada_$login_admin.custo_peca
					FROM tmp_gpp_os_os_custo_extrato_aprovada_$login_admin
					JOIN tmp_gpp_os_extrato_aprovada_$login_admin USING(os)
				) as x
				WHERE x.os = tmp_gpp_os_extrato_aprovada_$login_admin.os;";
	}
	/*HD: 55221 - PARA A BLACK NÃO É GRAVADO O CAMPO CUSTO_PECA NA OS*/

	if($login_fabrica ==1){
		$sql_custo_peca = " SUM ( CASE WHEN tbl_os.custo_peca  IS NULL THEN 0 ELSE tbl_os.custo_peca  END )     AS pecas             ,";
		$sql_desvio     = " STDDEV (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END ) AS desvio  ,";

	}else{
		if($login_fabrica == 42 or $login_fabrica == 50 ){
			$sql_custo_peca = " SUM ( CASE WHEN tbl_os_item.$custo_peca  IS NULL THEN 0 ELSE tbl_os_item.$custo_peca  END )     AS pecas             ,";
		} else {
			$sql_custo_peca = " SUM ( CASE WHEN tbl_os.$custo_peca  IS NULL THEN 0 ELSE tbl_os.$custo_peca  END )     AS pecas             ,";
		}
		if($tem_valor_km) {
			$sql_desvio     = " STDDEV (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.$custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.$custo_peca + coalesce(tbl_os.qtde_km_calculada,0) END ) AS desvio  ,";
		}else{
			$sql_desvio     = " STDDEV (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.$custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.$custo_peca END ) AS desvio  ,";
		}
	}
   		$sql_km_calculada = "CASE WHEN SUM (tbl_os.qtde_km_calculada) IS NULL THEN 0 ELSE SUM (tbl_os.qtde_km_calculada)  END AS qtde_km_calculada ,";

	$sql .= "
		SELECT
			SUM ( CASE WHEN tbl_os.mao_de_obra IS NULL THEN 0 ELSE tbl_os.mao_de_obra END )   AS mao_de_obra       ,
			$sql_km_calculada
			COUNT (tbl_os.os)                                                                 AS qtde              ,
			$sql_custo_peca
			$sql_desvio
			COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'C' OR tbl_os.consumidor_revenda IS NULL THEN 1 ELSE NULL END)                                    AS qtde_os_consumidor,
			COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'R'                                      THEN 1 ELSE NULL END)                                    AS qtde_os_revenda
		INTO TEMP tmp_gpp_$login_admin
		FROM    tmp_gpp_os_os_$login_admin tbl_os
		LEFT JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
		$cond_marca
		$join_pais
		$join_os_produto
		WHERE   tbl_os.fabrica = $login_fabrica
		AND     $cond_pais $cond_linha $cond_tipo;

		SELECT * FROM tmp_gpp_$login_admin";
	$res = pg_query ($con,$sql);
	
	//$arr = pg_fetch_all($res);
	//echo "<pre>";
	//	print_r($arr);
	//echo "</pre>";
	//echo "<br><bR> erro = ". pg_last_error($con) ."<br><Br>";

	$mao_de_obra = pg_fetch_result ($res,0,mao_de_obra);
	$pecas       = pg_fetch_result ($res,0,pecas);
	$total_geral = $mao_de_obra + $pecas ;

	$qtde_geral         = pg_fetch_result ($res,0,qtde) ;
	$desvio_geral       = pg_fetch_result ($res,0,desvio) ;
	if (strlen($desvio_geral) == 0) $desvio_geral = 0;

	$qtde_os_consumidor = pg_fetch_result ($res,0,qtde_os_consumidor);
	$qtde_km_calculada 	= pg_fetch_result ($res,0,qtde_km_calculada);

	if($tem_valor_km) {
		$total_geral += $qtde_km_calculada;
	}
	$qtde_os_revenda    = pg_fetch_result ($res,0,qtde_os_revenda); ?>

	<table width='700' class='table table-striped table-bordered table-hover table-large' >
	<thead>
		<tr class='titulo_tabela'>
			<td colspan='4'>
				<font style='font-size:14px;'><?=traduz("Valores Totais Pagos")?></font>
			</td>
		</tr>
	</thead>
	<tr bgcolor='#F7F5F0'>
	<td>
		<?=traduz('Mão de Obra')?> - <?= $real . number_format ($mao_de_obra,2,",",".")?>
	</td>
	<? if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") { ?>
    <td>
	   <?=traduz('Peças')?> - <?= $real . number_format ($pecas,2,",",".")?>
	</td>
    <? } ?>
	<td>KM - <?php echo $real . number_format ($qtde_km_calculada,2,",","."); ?></td>
	<td width='34%' <?= (in_array($login_fabrica, array(152,157,180,181,182))) ? "colspan='2' " : "" ; ?>>
	<?=traduz('Total')?> - <?= $real . number_format ($total_geral,2,",",".")?>
	</td>
	</tr>
	<tr bgcolor='#F1F4FA'>
	<td width='33%' colspan="2">
	<?=traduz("Qtde de OS")?> - <?=number_format($qtde_geral,0,",",".")?>
	</td>
	<td width='33%'><?
	echo traduz("Gasto Médio - " . $real);
	if ($total_geral > 0){
		$gasto_medio = $total_geral / $qtde_geral;
	}else {
		$gasto_medio = 0;
	}

	echo number_format ($gasto_medio,2,",",".");
	echo "</td>";
	echo "<td width='34%'>";
	echo traduz("Desvio Padrão")." - " . $real ;
	echo number_format ($desvio_geral,2,",",".") ;
	echo "</td>";
	echo "</tr>";
	echo "<tr bgcolor='#F7F5F0'>";
	echo "<td width='S%' colspan='2'>";
	echo traduz("Qtde OS Consumidor - ") . number_format ($qtde_os_consumidor,0,",",".");
	echo "</td>";
	echo "<td width='50%' colspan='2'>";
	echo traduz("Qtde Os Revenda - ") . number_format ($qtde_os_revenda,0,",",".") ;
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<p>";

	/////////////////////////////////////////////////////////////////
	// exibe os graficos
	/////////////////////////////////////////////////////////////////
/*
	echo "<table width='700'>";
	echo "<tr>";
	echo "<td width='50%'>";
	include ("gasto_por_posto_grafico_1.php"); // custo por OS
	echo "</td>";
	echo "<td width='50%'>";
	include ("gasto_por_posto_grafico_2.php"); // % de OS com defeitos
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td width='50%'>";
	include ("gasto_por_posto_grafico_4.php"); // clientes e revendas
	echo "</td>";
	echo "<td width='50%'>";
	include ("gasto_por_posto_grafico_3.php"); // clientes e revendas PIZZA
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	/////////////////////////////////////////////////////////////////

	echo "<p>";
	*/
	#---------------- 10 Maiores postos em Valores Nominais ------------
	flush();
	if($login_fabrica == 15){
		$colspan='8';
	}elseif($login_fabrica == 145 or $tem_valor_km){
		$colspan='8';
	}else{
		$colspan ='7';
	}
	?>
	<table width='700' class='table table-striped table-bordered table-hover table-large'>
	<thead>
		<?
		if($login_fabrica == 15){ ?>
			<tr class='titulo_tabela'>
				<td colspan='<?=$colspan?>'>
					<font style='font-size:14px;'><?=traduz("100 Maiores Postos em Valores Nominais")?></font>
				</td>
			</tr> <?
		}elseif(in_array($login_fabrica, array(152,180,181,182))){ ?>
			<tr class='titulo_tabela'>
				<td colspan='<?=$colspan?>'>
					<font style='font-size:14px;'><?=traduz("20 Maiores Postos em Valores Nominais")?></font>
				</td>
			</tr> <?
		}else{ ?>
			<tr class='titulo_tabela'>
				<td colspan='<?=$colspan?>'>
					<font style='font-size:14px;'><?=traduz("10 Maiores Postos em Valores Nominais")?></font>
				</td>
			</tr> <?
		} ?>
		<tr class='titulo_coluna'>
			<td><?=traduz("Posto")?></td>
			<td><?=traduz("Nome")?></td>
                                <? if($login_fabrica == 15){ ?>
				<td><?=traduz("Cidade")?></td>
			<? } ?>
			<td><?=traduz("Estado")?></td>
			<td><?=traduz("Qtde")?></td>
			<? if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) { ?>
                                  <td><?=traduz("KM")?></td>
			<? } ?>
			<td><?=traduz("MO")?></td>
            <? if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") { ?>
			<td><?=traduz("Peças")?></td>
                                    <? } ?>
			<td><?=traduz("Total")?></td>
		</tr>
	</thead>
	<?
		if($login_fabrica == 15){
			$limit = '100';
		}elseif(in_array($login_fabrica, array(152,180,181,182))) {
			$limit = '20';
		}else{
			$limit = '10';
		}

	if($login_fabrica ==1){
		$sql_custo_peca = " CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas  ,";
		$join_extrato_aprovado = "JOIN    tmp_gpp_os_extrato_aprovada_$login_admin tbl_os ON tbl_os.os           = tbl_os.os";
	}else{
		if($login_fabrica == 42 or $login_fabrica == 50){
			$sql_custo_peca = " SUM(e_aprovado.pecas) as pecas ,";
			$join_custo_peca = "JOIN (
			SELECT os,CASE WHEN SUM (tbl_os_item.custo_peca) IS NULL THEN 0 ELSE SUM (tbl_os_item.custo_peca) END AS pecas
			FROM tmp_gpp_os_extrato_aprovada_$login_admin
			JOIN tbl_os_produto USING(os)
			JOIN tbl_os_item using(os_produto) WHERE $cond_pais group by os )e_aprovado ON e_aprovado.os = tbl_os.os";
		} else {
			$sql_custo_peca = " CASE WHEN SUM   (tbl_os.$custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.$custo_peca)  END AS pecas      ,";
			$join_extrato_aprovado = "JOIN    tmp_gpp_os_extrato_aprovada_$login_admin e_aprovado ON e_aprovado.os           = tbl_os.os";
		}
		if (in_array($login_fabrica, array(145, 157))) {
			$sql_km_calculada = "CASE WHEN SUM (tbl_os.qtde_km_calculada) IS NULL THEN 0 ELSE SUM (tbl_os.qtde_km_calculada)  END AS qtde_km_calculada ,";
		}
	}
	if (in_array($login_fabrica, array(145, 157))) {
		$campo_fabrica_145 	= " , tbl_os.qtde_km_calculada ";
		$join_fabrica_145 	= " join tbl_os on tbl_os.fabrica = tbl_posto_fabrica.fabrica ";
	}

	$sql = "SELECT  maiores.*                     ,
					tbl_posto.nome                ,
					tbl_posto.cidade              ,
					tbl_posto.estado              ,
					tbl_posto_fabrica.codigo_posto
			FROM (
					SELECT * FROM (
						SELECT  tbl_os.posto                                                                                          ,
								CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
								CASE WHEN SUM   (tbl_os.qtde_km_calculada) IS NULL THEN 0 ELSE SUM  (tbl_os.qtde_km_calculada) END AS qtde_km_calculada,
								$sql_custo_peca
								CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
						FROM    tmp_gpp_os_os_$login_admin tbl_os
						$join_custo_peca
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica
						$cond_marca
						JOIN    tbl_posto_fabrica                                   ON tbl_posto_fabrica.posto = tbl_os.posto
						$join_pais

						WHERE   tbl_os.fabrica            = $login_fabrica
						AND     tbl_posto_fabrica.fabrica = $login_fabrica
						AND     $cond_pais $cond_linha $cond_tipo
						GROUP BY tbl_os.posto ";
	if($login_fabrica == 15){
		$sql .= " ) AS x ORDER BY x.qtde DESC LIMIT $limit ";
	}else{
		$sql .= " ) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC LIMIT $limit ";
	}

	$sql .= "   ) maiores
			JOIN tbl_posto         ON maiores.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON maiores.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica

			WHERE $cond_pais ";
	if($login_fabrica == 15){
		$sql .= " ORDER BY maiores.qtde DESC ";
	}

	$res = pg_query ($con,$sql);

	$total_mao_de_obra = 0;
	$total_pecas = 0 ;
	$total_qtde = 0 ;

	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$cor = "#F7F5F0";
		if ($i % 2 == 0)
		{
			$cor = '#F1F4FA';
		}

		echo "<tr style='background-color: $cor;'>";

		echo "<td align='left'>";
		echo pg_fetch_result ($res,$i,codigo_posto);
		echo "</td>";

		echo "<td align='left'>";
		echo pg_fetch_result ($res,$i,nome);
		echo "</td>";

		if($login_fabrica == 15){
			echo "<td align='left'>";
			echo pg_fetch_result ($res,$i,cidade);
			echo "</td>";
		}

		echo "<td align='center'>";
		echo pg_fetch_result ($res,$i,estado);
		echo "</td>";

		echo "<td align='right'> ";
		$qtde = pg_fetch_result ($res,$i,qtde);
		echo $qtde;
		echo "</td>";

		if ((in_array($login_fabrica, array(145, 157)) && $extrato_sem_peca != "t") or $tem_valor_km) {
			echo "<td align='right'> ";
			$qtde_km_calculada = pg_fetch_result ($res,$i,qtde_km_calculada);
			echo number_format ($qtde_km_calculada,2,",",".");
			echo "</td>";
		}

		echo "<td align='right'>";
		$mao_de_obra = pg_fetch_result ($res,$i,mao_de_obra);
		echo number_format ($mao_de_obra,2,",",".");
		echo "</td>";

        if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") {
    		echo "<td align='right'>";
    		$pecas = pg_fetch_result ($res,$i,pecas);
    		echo number_format ($pecas,2,",",".");
    		echo "</td>";
        }

		echo "<td align='right'>";
		if (in_array($login_fabrica, array(157))) {
			$total = $mao_de_obra + $qtde_km_calculada;
		} else {
			$total = $mao_de_obra + $pecas ;
			if($tem_valor_km) {
				$total += $qtde_km_calculada;
			} 
		}
		echo number_format ($total,2,",",".");
		echo "</td>";

		echo "</tr>";

		$total_mao_de_obra += pg_fetch_result ($res,$i,mao_de_obra) ;
		$total_pecas       += pg_fetch_result ($res,$i,pecas) ;
		$total_qtde        += pg_fetch_result ($res,$i,qtde) ;
		if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
			$total_km_calculada  += pg_fetch_result ($res,$i,qtde_km_calculada) ;
		}
	}

	$total = $total_mao_de_obra + $total_pecas + $total_km_calculada;

	if($login_fabrica == 15){
		$colspan='4';
	}else{
		$colspan='3';
	}
	echo "<tr class='subtitulo'>";
	echo "<td align='rigth' colspan='$colspan'>";
	echo "&nbsp;&nbsp;".traduz("Percentual: ");
	if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
	echo number_format ($perc,0) . "%". traduz("do total");
	echo "</td>";

	echo "<td align='right'>";
	echo $total_qtde;
	echo "</td>";

	if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
		echo "<td align='right'> ";
		echo number_format ($total_km_calculada,2,",",".");;
		echo "</td>";
	}

	echo "<td align='right'>";
	echo number_format ($total_mao_de_obra,2,",",".");
	echo "</td>";

    if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") {
        echo "<td align='right'>";
        echo number_format ($total_pecas,2,",",".");
        echo "</td>";
    }

	echo "<td align='right'>";
	echo number_format ($total,2,",",".");
	echo "</td>";
	echo "</tr>";

	echo "</table>";
	echo "<p>";

	flush();

	#-------------------- Acima da Media + Desvio ------------------------
    $colspan = ($extrato_sem_peca != "t") ? 9 : 10;
	?>
	<table width='700' class='table table-striped table-bordered table-hover table-large'>
	<thead>
		<tr class='titulo_tabela'>
			<td colspan='<?=$colspan?>'>
				<font style='font-size:14px;'><?=traduz("Postos com gastos acima da Média")."(". number_format($gasto_medio,2,",",".") .") + ".traduz("Desvio Padrão")." (". number_format($desvio_geral,2,",",".") .")"?></font>
			</td>
		</tr>
		<tr class='titulo_coluna'>
			<td><?=traduz("Posto")?></td>
			<td><?=traduz("Nome")?></td>
			<td><?=traduz("Estado")?></td>
			<td><?=traduz("Qtde")?></td>
			<? if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) { ?>
				<td><?=traduz("KM")?></td>
			<? } ?>
			<td><?=traduz("MO")?></td>
            <? if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") { ?>
                <td><?=traduz("Peças")?></td>
            <? } ?>
			<td><?=traduz("Total")?></td>
			<td><?=traduz("Média")?></td>
			<td><?=traduz("Acima")?></td>
		</tr>
	</thead>
	<?
	$xgasto_medio  = str_replace(",",".",$gasto_medio);
	$xdesvio_geral = str_replace(",",".",$desvio_geral);
flush();

	if($login_fabrica ==1){
		$sql_custo_peca = " CASE WHEN SUM (tbl_os.custo_peca) IS NULL THEN 0 ELSE SUM (tbl_os.custo_peca)  END AS pecas,";
		$sql_media_mobra_peca = " AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END) AS media_mobra_peca ";

		$order_by_custo_peca = " HAVING AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE
								tbl_os.mao_de_obra + tbl_os.custo_peca END) > ($xgasto_medio + $xdesvio_geral)
								ORDER BY AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END) DESC ";
	}else{
		if($login_fabrica == 42 or $login_fabrica ==50){
			$sql_custo_peca = " CASE WHEN SUM (tbl_os_item.$custo_peca) IS NULL THEN 0 ELSE SUM (tbl_os_item.$custo_peca)  END AS pecas ,";
		} else {
			$sql_custo_peca = " CASE WHEN SUM (tbl_os.$custo_peca) IS NULL THEN 0 ELSE SUM (tbl_os.$custo_peca)  END AS pecas ,";
		}

		if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
			$sql_km_calculada = "CASE WHEN SUM (tbl_os.qtde_km_calculada) IS NULL THEN 0 ELSE SUM (tbl_os.qtde_km_calculada)  END AS qtde_km_calculada ,";
		}

		$sql_media_mobra_peca = " AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.$custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.$custo_peca END) AS media_mobra_peca ";

		$order_by_custo_peca = " HAVING   AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.$custo_peca IS NULL THEN 0 ELSE
						tbl_os.mao_de_obra + tbl_os.$custo_peca END) > ($xgasto_medio + $xdesvio_geral)
						ORDER BY AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.$custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.$custo_peca END) DESC ";
	}

	/*if($login_fabrica == 145){
		$campo_fabrica_145 	= ", tbl_os.qtde_km_calculada ";
		$join_fabrica_145	= " join tbl_os on tbl_os.fabrica = tbl_posto_fabrica.fabrica and tbl_os.fabrica = $login_fabrica ";
	}*/

	$sql = "SELECT  maiores.*       ,
					tbl_posto.nome  ,
					tbl_posto.estado,
					tbl_posto_fabrica.codigo_posto
			FROM (
					SELECT * FROM (
						SELECT  tbl_os.posto                                                                                          ,
							CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
							$sql_custo_peca
							$sql_km_calculada
							CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde       ,
							$sql_media_mobra_peca
						FROM    tmp_gpp_os_os_$login_admin tbl_os
						$join_pais
						JOIN    tbl_produto    ON tbl_produto.produto=tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
						$cond_marca
						$join_os_produto
						WHERE   tbl_os.fabrica  = $login_fabrica
						AND     $cond_pais $cond_linha $cond_tipo
						GROUP BY tbl_os.posto
						$order_by_custo_peca
					) AS x
					ORDER BY (x.media_mobra_peca) DESC
			) maiores
			JOIN tbl_posto         ON maiores.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON maiores.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
			WHERE $cond_pais ;";
	$res = pg_query ($con,$sql);
	//echo nl2br($sql);
	//$arr = pg_fetch_all($res);
	//echo "<Br><br><Br> erro <br><Br> ". pg_last_error($con);
	//echo "<pre>";
	//	print_r($arr);
	//echo "</pre>";

	$total_mao_de_obra = 0 ;
	$total_pecas = 0 ;
	$total_qtde = 0 ;
	$total_perc_acima   = 0;
	$total_qtde_km_calculada = 0 ;;

	$res_gastomedio_desviogeral = ($gasto_medio + $desvio_geral);

	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$codigo_posto      = pg_fetch_result ($res,$i,codigo_posto);
		$nome              = pg_fetch_result ($res,$i,nome);
		$estado            = pg_fetch_result ($res,$i,estado);
		$qtde              = pg_fetch_result ($res,$i,qtde);
		$mao_de_obra       = pg_fetch_result ($res,$i,mao_de_obra);
		$pecas             = pg_fetch_result ($res,$i,pecas);
		$media_mobra_peca  = pg_fetch_result ($res,$i,media_mobra_peca);
		$total             = $mao_de_obra + $pecas ;

		if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
			$qtde_km_calculada = pg_fetch_result ($res,$i,qtde_km_calculada);
		}

		$res_mo_qtde    = ($total / $qtde);

		if($media_mobra_peca > 0) {
			//$perc_acima     = ($res_mo_qtde / $res_gastomedio_desviogeral * 100) - 100;
			$perc_acima     = 100 - ($res_gastomedio_desviogeral / $media_mobra_peca * 100);
			$perc_acima     = number_format ($perc_acima,1,",",".");
		}else{
			$perc_acima     = number_format (0,1,",",".");
		}

		$cor = "#F7F5F0";
		if ($i % 2 == 0)
		{
			$cor = '#F1F4FA';
		}

		echo "<tr style='background-color: $cor;'>";

		echo "<td align='left'>";
		echo $codigo_posto;
		echo "</td>";

		echo "<td align='left'>";
		echo $nome;
		echo "</td>";

		echo "<td align='center'>";
		echo $estado;
		echo "</td>";

		echo "<td align='right'>";
		echo $qtde;
		echo "</td>";

		if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
			echo "<td align='right'>";
			echo number_format ($qtde_km_calculada,2,",",".");
			echo "</td>";
		}
		echo "<td align='right'>";
		echo number_format ($mao_de_obra,2,",",".");
		echo "</td>";

        if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") {
            echo "<td align='right'>";
            echo number_format ($pecas,2,",",".");
            echo "</td>";
        }

		echo "<td align='right'>";
		echo number_format ($total,2,",",".");
		echo "</td>";

		echo "<td align='right'>";
		echo number_format($media_mobra_peca,2,",",".");
		echo "</td>";

		echo "<td align='right'>";
		echo $perc_acima ."%";
		echo "</td>";

		echo "</tr>";

		$total_mao_de_obra += pg_fetch_result ($res,$i,mao_de_obra) ;
		$total_pecas       += pg_fetch_result ($res,$i,pecas) ;
		$total_qtde        += pg_fetch_result ($res,$i,qtde) ;

		if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
			$total_qtde_km_calculada += pg_fetch_result ($res,$i,qtde_km_calculada);
		}
	}

	$total = $total_mao_de_obra + $total_pecas + $total_qtde_km_calculada;

	echo "<tr class='subtitulo'>";
	echo "<td align='rigth' colspan='3'>";
	echo "&nbsp;&nbsp;".traduz("Percentual:")."";
	if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
	echo number_format ($perc,0) . "%".traduz("do total")."";
	echo "</td>";

	echo "<td align='right'>";
	echo $total_qtde;
	echo "</td>";

	if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
		echo "<td align='right'>";
		echo number_format ($total_qtde_km_calculada,2,",",".");
		echo "</td>";
	}

	echo "<td align='right'>";
	echo number_format ($total_mao_de_obra,2,",",".");
	echo "</td>";

    if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") {
        echo "<td align='right'>";
        echo number_format ($total_pecas,2,",",".");
        echo "</td>";
    }

	echo "<td align='right'>";
	echo number_format ($total,2,",",".");
	echo "</td>";

	echo "<td align='right'>&nbsp;</td>";

	echo "<td align='right'>&nbsp;</td>";

	echo "</tr>";

	echo "</table>";
	echo "<p>";

	flush();

	#---------------- 10 Maiores produtos em Valores Nominais ------------
	?>
	<table width='700' class='table table-striped table-bordered table-hover table-large'>
		<thead>
			<tr class='titulo_tabela'>
				<td colspan='6'>
					<? if(in_array($login_fabrica, array(152,180,181,182))) {
						echo "<font style='font-size:14px;'>".traduz("20 Maiores Produtos em Valores Nominais")."</font>" ;
					}else{
						echo "<font style='font-size:14px;'>".traduz("10 Maiores Produtos em Valores Nominais")."</font>" ;
					}?>
				</td>
			</tr>
			<tr class='titulo_coluna'>
				<td><?=traduz("Produto")?></td>
				<td><?=traduz("Qtde")?></td>
				<?if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) { ?>
				<td><?=traduz("KM")?></td>
				<? } ?>
				<td><?=traduz("MO")?></td>
                <? if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") { ?>
				<td><?=traduz("Peças")?></td>
                <? } ?>
				<td><?=traduz("Total")?></td>
			</tr>
			</thead> <?

	if($login_fabrica ==1){
		$sql_custo_peca = " CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas  ,";
	}else{
		if($login_fabrica == 42 or $login_fabrica ==50){
				$sql_custo_peca = " CASE WHEN SUM   (tbl_os_item.$custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os_item.$custo_peca)  END AS pecas      ,";
		} else {
				$sql_custo_peca = " CASE WHEN SUM   (tbl_os.$custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.$custo_peca)  END AS pecas      ,";
		}
		if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
			$sql_km_calculada = "CASE WHEN SUM (tbl_os.qtde_km_calculada) IS NULL THEN 0 ELSE SUM (tbl_os.qtde_km_calculada)  END AS qtde_km_calculada ,";
		}else{
			$sql_km_calculada = " 0 as qtde_km_calculada , ";
		}
	}

	$sql = "SELECT  maiores.*             ,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM (
					SELECT * FROM (
						SELECT  tbl_os.produto                                                                                        ,
								CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
								$sql_custo_peca
								$sql_km_calculada
								CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
						FROM    tmp_gpp_os_os_$login_admin tbl_os
						$join_pais
						JOIN    tbl_produto    ON tbl_produto.produto=tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
						$cond_marca
						$join_os_produto
						WHERE   tbl_os.fabrica  = $login_fabrica
						AND     $cond_pais $cond_linha $cond_tipo
						GROUP BY tbl_os.produto
					) AS x ORDER BY (x.mao_de_obra + x.pecas + x.qtde_km_calculada) DESC LIMIT $limit
				) maiores
			JOIN    tbl_produto ON maiores.produto = tbl_produto.produto
			$cond_marca
			JOIN    tbl_linha   ON tbl_linha.linha = tbl_produto.linha
			WHERE   tbl_linha.fabrica = $login_fabrica;";
	$res = pg_query ($con,$sql);

	//echo nl2br($sql);
	//echo "<br><Br>". pg_last_error($con);
	//$arr = pg_fetch_all($res);
	//echo "<pre>";
		//print_r($arr);
	//echo "</pre>";

	$total_mao_de_obra = 0 ;
	$total_pecas = 0 ;
	$total_qtde = 0 ;
	$total_qtde_km_calculada = 0 ;

	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$cor = "#F7F5F0";
		if ($i % 2 == 0)
		{
			$cor = '#F1F4FA';
		}

		echo "<tr style='background-color: $cor;'>";

		echo "<td align='left'>";
		echo pg_fetch_result ($res,$i,referencia);
		echo " - ";
		echo pg_fetch_result ($res,$i,descricao);
		echo "</td>";

		echo "<td align='right'>";
		$qtde = pg_fetch_result ($res,$i,qtde);
		echo $qtde;
		echo "</td>";

		if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
			echo "<td align='right'>";
			$km = pg_fetch_result ($res,$i,qtde_km_calculada);
			echo number_format ($km,2,",",".");
			echo "</td>";
		}

		echo "<td align='right'>";
		$mao_de_obra = pg_fetch_result ($res,$i,mao_de_obra);
		echo number_format ($mao_de_obra,2,",",".");
		echo "</td>";

		if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") {
			echo "<td align='right'>";
			$pecas = pg_fetch_result ($res,$i,pecas);
			echo number_format ($pecas,2,",",".");
			echo "</td>";
		}

		echo "<td align='right'>";
		if (in_array($login_fabrica, array(157))) {
			$total = $mao_de_obra + $qtde_km_calculada;
		} else {
			$total = $mao_de_obra + $pecas ;
			if($tem_valor_km) {
				$total += $qtde_km_calculada;
			}
		}
		echo number_format ($total,2,",",".");
		echo "</td>";

		echo "</tr>";

		$total_mao_de_obra 			+= pg_fetch_result ($res,$i,mao_de_obra) ;
		$total_pecas       			+= pg_fetch_result ($res,$i,pecas) ;
		$total_qtde        			+= pg_fetch_result ($res,$i,qtde) ;
		$total_qtde_km_calculada    += pg_fetch_result ($res,$i,qtde_km_calculada) ;
	}

    if (in_array($login_fabrica, array(157))) {
        $total = $total_mao_de_obra + $total_qtde_km_calculada ;
    } else {
		$total = $total_mao_de_obra + $total_pecas ;
		if($tem_valor_km) {
			$total += $total_qtde_km_calculada;
		}
    }

	echo "<tr class='subtitulo'>";
	echo "<td align='rigth' colspan='1'>";
	echo "&nbsp;&nbsp;".traduz("Percentual: ");
	if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
	echo number_format ($perc,0) . "%". traduz("do total");
	echo "</td>";

	echo "<td align='right'>";
	echo $total_qtde;
	echo "</td>";

	if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
		echo "<td align='right'>";
		echo number_format ($total_qtde_km_calculada,2,",",".");
		echo "</td>";
	}
	echo "<td align='right'>";
	echo number_format ($total_mao_de_obra,2,",",".");
	echo "</td>";

    if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") {
        echo "<td align='right'>";
        echo number_format ($total_pecas,2,",",".");
        echo "</td>";
    }

	echo "<td align='right'>";
	echo number_format ($total,2,",",".");
	echo "</td>";
	echo "</tr>";

	echo "</table>";
	echo "<p>";

	flush();

	if($login_fabrica == 40) { ?>

		<table width='700' class='table table-striped table-bordered table-hover table-large'>
			<thead>
				<tr class='titulo_tabela'>
					<td colspan='100%'>
						<font style='font-size:14px;'><?=traduz("10 Maiores Familias em Valores Nominais")?></font>
					</td>
				</tr>
				<tr class='titulo_coluna'>
					<td><?=traduz("Família")?></td>
					<td><?=traduz("Qtde")?></td>
					<td><?=traduz("MO")?></td>
					<? 	if ((in_array($login_fabrica, array(145,157)) && $extrato_sem_peca != "t") or $tem_valor_km) {  ?>
					   <td><?=traduz("Peças")?></td>
					<? } ?>
					<td><?=traduz("Total")?></td>
				</tr>
			</thead> <?

		$sql_custo_peca = " CASE WHEN SUM   (tbl_os.$custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.$custo_peca)  END AS pecas      ,";

		$sql = "SELECT  maiores.*                     ,
						tbl_familia.descricao
				FROM (
						SELECT * FROM (
							SELECT  tbl_produto.familia                                                                                          ,
								CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
								$sql_custo_peca
								CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
							FROM    tmp_gpp_os_os_$login_admin tbl_os
							JOIN    tbl_produto ON tbl_produto.produto=tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
							JOIN    tbl_posto_fabrica  ON tbl_posto_fabrica.posto = tbl_os.posto
							$join_pais
							WHERE   tbl_os.fabrica            = $login_fabrica
							AND     tbl_posto_fabrica.fabrica = $login_fabrica
							AND     $cond_pais $cond_linha $cond_tipo
							GROUP BY tbl_produto.familia ";
			$sql .= " ) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC LIMIT $limit ";

		$sql .= "   ) maiores
				JOIN tbl_familia         ON maiores.familia = tbl_familia.familia
				WHERE $cond_pais ";
		$res = pg_query ($con,$sql);
		$total_mao_de_obra = 0 ;
		$total_pecas = 0 ;
		$total_qtde = 0 ;

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$cor = "#F7F5F0";
			if ($i % 2 == 0)
			{
				$cor = '#F1F4FA';
			}

			echo "<tr style='background-color: $cor;'>";

			echo "<td align='left'>";
			echo pg_fetch_result ($res,$i,'descricao');
			echo "</td>";

			echo "<td align='right'> ";
			$qtde = pg_fetch_result ($res,$i,qtde);
			echo $qtde;
			echo "</td>";

			echo "<td align='right'>";
			$mao_de_obra = pg_fetch_result ($res,$i,mao_de_obra);
			echo number_format ($mao_de_obra,2,",",".");
			echo "</td>";

            if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") {
                echo "<td align='right'>";
                $pecas = pg_fetch_result ($res,$i,pecas);
                echo number_format ($pecas,2,",",".");
                echo "</td>";
            }

			echo "<td align='right'>";
			$total = $mao_de_obra + $pecas ;
			echo number_format ($total,2,",",".");
			echo "</td>";

			echo "</tr>";

			$total_mao_de_obra += pg_fetch_result ($res,$i,mao_de_obra) ;
			$total_pecas       += pg_fetch_result ($res,$i,pecas) ;
			$total_qtde        += pg_fetch_result ($res,$i,qtde) ;
		}

		$total = $total_mao_de_obra + $total_pecas ;

		echo "<tr class='subtitulo'>";
		echo "<td align='rigth' colspan='1'>";
		echo "&nbsp;&nbsp;".traduz("Percentual: ");

		if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
		echo number_format ($perc,0) . "%". traduz("do total");	
		echo "</td>";

		echo "<td align='right'>";
		echo $total_qtde;
		echo "</td>";

		echo "<td align='right'>";
		echo number_format ($total_mao_de_obra,2,",",".");
		echo "</td>";

        if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") {
            echo "<td align='right'>";
            echo number_format ($total_pecas,2,",",".");
            echo "</td>";
        }

		echo "<td align='right'>";
		echo number_format ($total,2,",",".");
		echo "</td>";
		echo "</tr>";

		echo "</table>";
		echo "<p>";

		flush();

	}

	#---------------- 20 Maiores peças em Valores Nominais ------------

            if (!in_array($login_fabrica, array(157))) {

                if (in_array($login_fabrica, array(145)) or $tem_valor_km) {
                    $colspan = 6;
                }else{
                    $colspan = 5;
				}
				
				if ($extrato_sem_peca == 't') {
					$colspan -= 1;
				}

                ?>
                <table width='700' class='table table-striped table-bordered table-hover table-large'>
                <thead>
                    <tr class='titulo_tabela'>
                        <td colspan='<?= $colspan ?>'>
                            <font style='font-size:14px;'><?=traduz("20 Maiores Peças em Valores Nominais")?></font>
                        </td>
                    </tr>
                    <tr class='titulo_coluna'>
                        <td><?=traduz("Peça")?></td>
                        <? if (in_array($login_fabrica, array(145)) or $tem_valor_km) { ?>
                            <th><?=traduz("KM")?></th>
                        <? } ?>
                        <td><?=traduz("Qtde")?></td>
                        <td><?=traduz("MO")?></td>
                        <?php
                        if (!in_array($login_fabrica, array(152,180,181,182)) && $extrato_sem_peca != 't') {?>
                            <td><?=traduz("Peças")?></td>
                        <?php
                        }
                        ?>                        
                        <td><?=traduz("Total")?></td>
                    </tr>
                </thead>
                <? if($login_fabrica ==1){
                    $sql_custo_peca = " CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas  ,";
                }else{
                    if($login_fabrica == 42 or $login_fabrica ==50){
                        $sql_custo_peca = " CASE WHEN SUM   (tbl_os_item.$custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os_item.$custo_peca)  END AS pecas      ,";
                    } else {
                        $sql_custo_peca = " CASE WHEN SUM   (tbl_os.$custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.$custo_peca)  END AS pecas      ,";
                    }
                    if (in_array($login_fabrica, array(145)) or $tem_valor_km) {
                        $sql_km_calculada = "CASE WHEN SUM (tbl_os.qtde_km_calculada) IS NULL THEN 0 ELSE SUM (tbl_os.qtde_km_calculada)  END AS qtde_km_calculada ,";
                    }
                }
                $sql = "SELECT  maiores.*          ,
                                        tbl_peca.referencia,
                                        tbl_peca.descricao
                                        FROM (
                                        SELECT * FROM (
                                        SELECT  tbl_os_item.peca                        ,
                                        CASE WHEN  SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
                                        $sql_custo_peca
                                        $sql_km_calculada
                                        CASE WHEN  COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
                            FROM    tmp_gpp_os_os_$login_admin tbl_os
                            JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
                            JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
                            $cond_marca
                            $join_pais
                            WHERE   tbl_os.fabrica = $login_fabrica
                            AND     $cond_pais $cond_linha $cond_tipo
                            GROUP BY tbl_os_item.peca
                            ) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC LIMIT 20
                            ) maiores
                            JOIN tbl_peca ON maiores.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica;";

                $res = pg_query ($con,$sql);

                $total_mao_de_obra = 0 ;
                $total_pecas = 0 ;
                $total_qtde = 0 ;

                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                    $cor = "#F7F5F0";
                    if ($i % 2 == 0) {
                        $cor = '#F1F4FA';
                    }

                    echo "<tr  style='background-color: $cor;'>";

                    echo "<td align='left'>";
                    echo pg_fetch_result ($res,$i,referencia);
                    echo " - ";
                    echo pg_fetch_result ($res,$i,descricao);
                    echo "</td>";

                    if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
                        echo "<td align='right'>";
                        $qtde_km_calculada = pg_fetch_result ($res,$i,qtde_km_calculada);
                        echo number_format ($qtde_km_calculada,2,",",".");
                        echo "</td>";
                    }

                    echo "<td align='right'>";
                    $qtde = pg_fetch_result ($res,$i,qtde);
                    echo $qtde;
                    echo "</td>";

                    echo "<td align='right'>";
                    $mao_de_obra = pg_fetch_result ($res,$i,mao_de_obra);
                    echo number_format ($mao_de_obra,2,",",".");
                    echo "</td>";

                    if (!in_array($login_fabrica, array(152,180,181,182)) && $extrato_sem_peca != 't') {
                        echo "<td align='right'>";
                        $pecas = pg_fetch_result ($res,$i,pecas);
                        echo number_format ($pecas,2,",",".");
                        echo "</td>";
                    }

                    echo "<td align='right'>";
                    $total = $mao_de_obra + $pecas ;
                    echo number_format ($total,2,",",".");
                    echo "</td>";

                    echo "</tr>";

                    $total_mao_de_obra 		+= pg_fetch_result ($res,$i,mao_de_obra) ;
                    $total_pecas      	 	+= pg_fetch_result ($res,$i,pecas) ;
                    $total_qtde        		+= pg_fetch_result ($res,$i,qtde) ;
                    $total_km_calculada     += pg_fetch_result ($res,$i,qtde_km_calculada) ;
                }

				$total = $total_mao_de_obra + $total_pecas ;
				
				if ($tem_valor_km) {
					$total += $total_km_calculada;
				}

                echo "<tr class='subtitulo'>";
                echo "<td align='rigth' colspan='1'>";
                echo "&nbsp;&nbsp;".traduz("Percentual: ");
                if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
                echo number_format ($perc,0) . "%". traduz("do total");
                echo "</td>";

                if (in_array($login_fabrica, array(145)) or $tem_valor_km) {
                    echo "<td align='right'>";
                    echo number_format($total_km_calculada, 2, ',', '.');
                    echo "</td>";
                }

                echo "<td align='right'>";
                echo $total_qtde;
                echo "</td>";

                echo "<td align='right'>";
                echo number_format ($total_mao_de_obra,2,",",".");
                echo "</td>";

                if ( !in_array($login_fabrica, array(152,180,181,182))  && $extrato_sem_peca != 't') {
                    echo "<td align='right'>";
                    echo number_format ($total_pecas,2,",",".");
                    echo "</td>";
                }
                

                echo "<td align='right'>";
                echo number_format ($total,2,",",".");
                echo "</td>";
                echo "</tr>";

                echo "</table>";
                echo "<p>";

                flush();
            }

#----------------------- OS de Consumidor x OS Loja --------------------------

#----------------------- OS sem Telefone --------------------------
	if ($login_fabrica != 150 && !$novaTelaOs and 1==2) {
	?>
		<table width='700' class='table table-striped table-bordered table-hover table-large'>
			<thead>
				<tr class='titulo_tabela'>
					<td colspan='5'>
						<font style='font-size:14px;'><?=traduz("20 Postos que não colocam Telefone do Consumidor na OS")?></font>
					</td>
				</tr>
				<tr class='titulo_coluna'>
					<td width='10%'><?=traduz("Posto")?></td>
					<td width='50%'><?=traduz("Nome")?></td>
					<td width='10%'><?=traduz("Estado")?></td>
					<td width='15%'><?=traduz("Qtde OS")?></td>
					<td width='15%'><?=traduz("Qtde sem Fone")?></td>
				</tr>
			</thead> <?

		$sql = "SELECT  tbl_posto.nome                                                                                 ,
						tbl_posto.estado                                                                               ,
						tbl_posto_fabrica.codigo_posto                                                                 ,
						COUNT(CASE WHEN length (trim (consumidor_fone)) > 0 THEN 1 ELSE NULL      END) AS qtde_com_fone,
						COUNT(CASE WHEN tbl_os.os IS NULL                   THEN 0 ELSE tbl_os.os END) AS qtde_os
				FROM    tbl_posto
				JOIN    tmp_gpp_os_os_$login_admin tbl_os            ON tbl_os.posto        = tbl_posto.posto
				JOIN    tbl_posto_fabrica ON tbl_posto.posto     = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
				WHERE   tbl_os.fabrica            = $login_fabrica
				AND     tbl_posto_fabrica.fabrica = $login_fabrica
				AND     $cond_pais $cond_tipo
				GROUP BY tbl_posto.nome, tbl_posto.estado, tbl_posto_fabrica.codigo_posto
				ORDER BY    COUNT(CASE WHEN tbl_os.os IS NULL THEN 0 ELSE tbl_os.os END) - COUNT(CASE WHEN length (trim (consumidor_fone)) > 0 THEN 1 ELSE NULL END ) DESC,
							COUNT(CASE WHEN tbl_os.os IS NULL THEN 0 ELSE tbl_os.os END) DESC,
							tbl_posto.nome LIMIT 20;";
		$res = pg_query ($con,$sql);

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$cor = "#F7F5F0";
			if ($i % 2 == 0)
			{
				$cor = '#F1F4FA';
			}

			echo "<tr style='background-color: $cor;'>";

			echo "<td align='left'>";
			echo pg_fetch_result($res,$i,codigo_posto);
			echo "</td>";

			echo "<td align='left'>";
			echo pg_fetch_result ($res,$i,nome);
			echo "</td>";

			echo "<td>";
			echo pg_fetch_result ($res,$i,estado);
			echo "</td>";

			echo "<td align='right'>";
			echo pg_fetch_result ($res,$i,qtde_os);
			echo "</td>";

			echo "<td align='right'>";
			echo pg_fetch_result ($res,$i,qtde_os) - pg_fetch_result ($res,$i,qtde_com_fone);
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
		flush();

		#echo "<table width='700' >";
		#echo "<tr><td>";
		//////////////////////////////////////////////////
		// grafico de postos que não colocam Telefone
		//////////////////////////////////////////////////
		#include ("gasto_por_posto_grafico_5.php"); // postos que não colocam Telefone
		//////////////////////////////////////////////////
		#echo "</td></tr>";
		#echo "</table>";

		echo "<p>";
	}

	#---------------- Gasto por Estado ------------

	if ((in_array($login_fabrica, array(145, 157)) && $extrato_sem_peca != "t") or $tem_valor_km) {
		$colspan = 6;
	}else{
		$colspan = 5;
	}

	?>
	<table width='700' class='table table-striped table-bordered table-hover table-large'>
		<thead>
			<tr class='titulo_tabela'>
				<td colspan='<?php echo $colspan ?>'>
					<font style='font-size:14px;'><?=traduz("Gasto por Estado")?></font>
				</td>
			</tr>
			<tr class='titulo_coluna'>
				<td><?=traduz("Estado")?></td>
				<? if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) { ?>
					<th><?=traduz("KM")?></th>
				<? } ?>
				<td><?=traduz("Qtde")?></td>
				<td><?=traduz("MO")?></td>
                <?php
                if (!in_array($login_fabrica, array(152,180,181,182)) && $extrato_sem_peca != "t") {?>
                    <td><?=traduz("Peças")?></td>
                <?php                    
                }?>
				<td><?=traduz("Total")?></td>
			</tr>
		</thead> <?

	if($login_fabrica ==1){
		$sql_custo_peca = " CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas  ,";
	}else{
		$sql_custo_peca = " CASE WHEN SUM   (tbl_os.$custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.$custo_peca)  END AS pecas      ,";
	}

	if (in_array($login_fabrica, array(145, 157)) or $tem_valor_km) {
		$sql_km_calculada = "CASE WHEN SUM (tbl_os.qtde_km_calculada) IS NULL THEN 0 ELSE SUM (tbl_os.qtde_km_calculada)  END AS qtde_km_calculada ,";
	}

	$sql = "SELECT * FROM (
				SELECT  tbl_posto_fabrica.contato_estado as estado                                                                                 ,
						CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM (tbl_os.mao_de_obra)  END AS mao_de_obra,
						$sql_custo_peca
						$sql_km_calculada
						CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)         END AS qtde
				FROM    tmp_gpp_os_os_$login_admin tbl_os
				JOIN    tbl_produto          ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
				$cond_marca
				JOIN    tbl_posto            ON tbl_os.posto              = tbl_posto.posto
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE   tbl_os.fabrica            = $login_fabrica
				AND     tbl_posto_fabrica.fabrica = $login_fabrica
				AND     $cond_pais $cond_linha $cond_tipo
				GROUP BY tbl_posto_fabrica.contato_estado
			) AS x
			ORDER BY (x.mao_de_obra + x.pecas) DESC;";
	$res = pg_query ($con,$sql);

	$total_mao_de_obra = 0 ;
	$total_pecas = 0 ;
	$total_qtde = 0 ;
    if(count($linha) > 0 ) {
        for($i = 0; $i < count($linha); $i++){
            if($i == count($linha)-1 ){
                $Linha .= $linha[$i];
            }else {
                $Linha .= $linha[$i].",";
            }
        }
        $linha =$Linha ;
    }

	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$cor = "#F7F5F0";
		if ($i % 2 == 0)
		{
			$cor = '#F1F4FA';
		}

		echo "<tr  style='background-color: $cor;'>";

		echo "<td align='center' style='cursor: hand;text-decoration: underline ' onclick='javascript:AbrePosto(\"$ano\",\"$mes\",\"". pg_fetch_result ($res,$i,estado)."\",\"$linha\")'>" ;
		echo pg_fetch_result ($res,$i,estado) ;
		echo "</td>";

        if ((in_array($login_fabrica, array(145, 157)) && $extrato_sem_peca != "t") or $tem_valor_km) {
            echo "<td align='right'>";
            $qtde_km_calculada = pg_fetch_result ($res,$i,qtde_km_calculada);
            echo number_format ($qtde_km_calculada,2,",",".");
            echo "</td>";
        }

		echo "<td align='right'>";
		$qtde = pg_fetch_result ($res,$i,qtde);
		echo $qtde;
		echo "</td>";

		echo "<td align='right'>";
		$mao_de_obra = pg_fetch_result ($res,$i,mao_de_obra);
		echo number_format ($mao_de_obra,2,",",".");
		echo "</td>";

        if (!in_array($login_fabrica, array(152,157,180,181,182)) && $extrato_sem_peca != "t") {
            echo "<td align='right'>";
            $pecas = pg_fetch_result ($res,$i,pecas);
            echo number_format ($pecas,2,",",".");
            echo "</td>";
        }

		echo "<td align='right'>";
        $total = $mao_de_obra + $pecas ;
		
		if ($login_fabrica == 157 || $tem_valor_km) {
			$total += $qtde_km_calculada ;
		}
		echo number_format ($total,2,",",".");
		echo "</td>";

		echo "</tr>";

		$total_mao_de_obra 	+= pg_fetch_result ($res,$i,mao_de_obra) ;
		$total_pecas       	+= pg_fetch_result ($res,$i,pecas) ;
		$total_qtde        	+= pg_fetch_result ($res,$i,qtde) ;
		$total_km_calculada += pg_fetch_result ($res,$i,qtde_km_calculada) ;
	}
    if (in_array($login_fabrica, array(157)) && $extrato_sem_peca != "t") {
        $total = $total_mao_de_obra + $total_km_calculada ;
    } else {
	   $total = $total_mao_de_obra + $total_pecas ;
    }

	echo "</table>";
	echo "<p>";

	flush();

	#echo "<table width='700' cellpadding=2 cellspacing=0 border=0>";
	#echo "<tr class='pesquisa'><td colspan='5'>Serviços Realizados</td></tr>";
	#echo "</table>";
	//////////////////////////////////////////////////
	// grafico de serviços realizados
	//////////////////////////////////////////////////
	#include ("servico_realizado_grafico.php"); // postos que não colocam Telefone
	//////////////////////////////////////////////////
		#---------------- Gasto por Estado ------------

	if(in_array($login_fabrica, array(152,180,181,182))) {
	?>
	<table width='700' class='table table-striped table-bordered table-hover table-large'>
		<thead>
			<tr class='titulo_tabela'>
				<td colspan='5'>
					<font style='font-size:14px;'><?=traduz("20 postos com maior Quantidade de OS")?></font>
				</td>
			</tr>
			<tr class='titulo_coluna'>
				<td><?=traduz("Nome")?></td>
				<td><?=traduz("Estado")?></td>
				<td><?=traduz("OS: Entrega Técnica")?></td>
				<td><?=traduz("OS: Reparo")?></td>
				<td><?=traduz("Total")?></td>
			</tr>
		</thead> <?

		$sql = "SELECT  tbl_posto.nome                                                                                 ,
						tbl_posto.estado                                                                               ,
						tbl_posto_fabrica.codigo_posto                                                                 ,
						sum(CASE WHEN tbl_tipo_atendimento.entrega_tecnica is false THEN 1 else 0 END) AS qtde_os ,
						sum(CASE WHEN tbl_tipo_atendimento.entrega_tecnica is true THEN 1 else 0 END) AS qtde_os_entrega
				FROM    tbl_posto
				JOIN    tbl_os            ON tbl_os.posto        = tbl_posto.posto
				JOIN    tbl_posto_fabrica ON tbl_posto.posto     = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
				JOIN    tbl_tipo_atendimento  ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				WHERE   tbl_os.fabrica            = $login_fabrica
				AND     tbl_posto_fabrica.fabrica = $login_fabrica
				AND     $cond_pais $cond_tipo
				GROUP BY tbl_posto.nome, tbl_posto.estado, tbl_posto_fabrica.codigo_posto
				ORDER BY    qtde_os_entrega desc ,qtde_os desc,
							tbl_posto.nome LIMIT 20;";
		$res = pg_query ($con,$sql);
		//var_dump(pg_last_error());

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$cor = "#F7F5F0";
			if ($i % 2 == 0)
			{
				$cor = '#F1F4FA';
			}

			echo "<tr  style='background-color: $cor;'>";

			echo "<td align='center'>" ;
			echo pg_fetch_result ($res,$i,nome) ;
			echo "</td>";

			echo "<td align='right'>";
			$estado = pg_fetch_result ($res,$i,estado);
			echo $estado;
			echo "</td>";

			echo "<td align='right'>";
			$qtde_os_entrega = pg_fetch_result ($res,$i,qtde_os_entrega);
			echo $qtde_os_entrega;
			echo "</td>";

			echo "<td align='right'>";
			$qtde_os = pg_fetch_result ($res,$i,qtde_os);
			echo $qtde_os;
			echo "</td>";

			echo "<td align='right'>";
			$total = $qtde_os_entrega + $qtde_os ;
			echo $total;
			echo "</td>";

			echo "</tr>";

		}

		echo "</table>";
		echo "<p>";

		flush();
	}

}

echo "<br><br>";

include "rodape.php";

?>