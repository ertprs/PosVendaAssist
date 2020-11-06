<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if ($_POST["btn_acao"] == 1) {

	$data_inicial = $_POST["data_inicial_01"];
	$data_final = $_POST["data_final_01"];
    $data_filtro = $_POST["data_filtro"];

	if(!$data_inicial OR !$data_final){
		$msg_erro["msg"][] = traduz("Data Inválida.");
		$msg_erro["campos"][] = "data";
	}

	if(count($msg_erro)==0){
		//Início Validação de Datas
		if($data_inicial){
			$dat = explode ("/", $data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) {
					$msg_erro["msg"][] = traduz("Data Inválida");
					$msg_erro["campos"][] = "data";
				}
		}
		if($data_final){
			$dat = explode ("/", $data_final );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) {
					$msg_erro["msg"][] = traduz("Data Inválida");
					$msg_erro["campos"][] = "data";
				}
		}
		if(count($msg_erro)==0){
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$aux_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_final);//tira a barra
			$aux_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($aux_data_inicial > $aux_data_final){
				$msg_erro["msg"][] = traduz("Data Inválida.");
				$msg_erro["campos"][] = "data";
			}

			//Fim Validação de Datas
		}
	}

    if(isset($_GET["linha"])){
        $linha = $_GET["linha"];
    }
    if(isset($_POST["linha"])){
        if(count($linha)>0){
            $linha = $_POST["linha"];
        }
    }

	if(isset($_GET["marca"])){
		$marca = $_GET["marca"];
	}
	if(isset($_POST["marca"])){
		if(count($marca)>0){
			$marca = $_POST["marca"];
		}
	}

    if (isset($_POST['status_os'])){
        $status_os = $_POST['status_os'];
    }

	if (count($msg_erro) == 0) $listar = "ok";

	if (count($msg_erro) > 0) {

		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);

	}

    if(in_array($login_fabrica, array(164))){

        if(strlen($_POST["posto_id"]) > 0 && strlen($_POST["codigo_posto"]) > 0 && strlen($_POST["descricao_posto"]) > 0){

            $posto_id        = $_POST["posto_id"];
            $codigo_posto    = $_POST["codigo_posto"];
            $descricao_posto = $_POST["descricao_posto"];

            $cond_posto = " AND tbl_os.posto = {$posto_id} ";
        }

    }

}

$layout_menu = "gerencia";
$title = traduz("RELATÓRIO - FIELD CALL-RATE : RELATÓRIO DE ORDEM DE SERVIÇO SEM PEÇA");

include "cabecalho_new.php";
$plugins = array( 	"multiselect",
					"datepicker",
					"mask",
					"dataTable",
					"shadowbox"
);
include "plugin_loader.php";
?>

<script>

    function date_onkeydown() {
      if (window.event.srcElement.readOnly) return;
      var key_code = window.event.keyCode;
      var oElement = window.event.srcElement;
      if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
            var d = new Date();
            oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                             String(d.getDate()).padL(2, "0") + "/" +
                             d.getFullYear();
            window.event.returnValue = 0;
        }
        if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
            if ((key_code > 47 && key_code < 58) ||
              (key_code > 95 && key_code < 106)) {
                if (key_code > 95) key_code -= (95-47);
                oElement.value =
                    oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
            }
            if (key_code == 8) {
                if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                    oElement.value = "dd/mm/aaaa";
                oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                    function ($0, $1, $2) {
                        var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                        if (idx >= 5) {
                            return $1 + "a" + $2;
                        } else if (idx >= 2) {
                            return $1 + "m" + $2;
                        } else {
                            return $1 + "d" + $2;
                        }
                    } );
                window.event.returnValue = 0;
            }
        }
        if (key_code != 9) {
            event.returnValue = false;
        }
    }

    function AbrePeca(produto,data_inicial,data_final,linha,estado){
    	janela = window.open("relatorio_field_call_rate_pecas.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado,"produto",'scrollbars=yes,width=750,height=280,top=0,left=0');
    	janela.focus();
    }

    function listaOS(parametros){
    	Shadowbox.open({ content: parametros, player: "iframe", width: 900, height: 600  });
    }

</script>

<script>
    $(function()
    {
    	$.datepickerLoad(Array("data_final_01", "data_inicial_01"));

    	Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

    	$("#linha").multiselect({
        	selectedText: "selecionados # de #"
        });

        $("#status_os").multiselect({
            selectedText: "selecionados # de #"
        });

    	$.dataTableLoad({
    		table: "#resultado_pesquisa"
     	});

    });

    function retorna_posto(retorno){
        $("#posto_id").val(retorno.posto);
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

</script>

<?
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<form name='frm_pesquisa' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '><?=traduz("Parâmetros de Pesquisas")?></div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial"); ?></label>
					<div class='controls controls-row'>
						<div class='span6'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial_01" id="data_inicial_01" maxlength="10" class='span12' value= "<?=$data_inicial_01?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'><?php echo traduz("Data Final"); ?></label>
				<div class='controls controls-row'>
					<div class='span6'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final_01" id="data_final_01" maxlength="10" class='span12' value="<?=$data_final_01?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='linha'><?=($login_fabrica == 117)?  traduz("Macro - Família") :  traduz("Linha") ?></label>
				<div class='controls controls-row'>
					<?php
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
						<option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >
							<?php echo $key['nome']?>
						</option>
					  <?php } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='tipo'><?php echo traduz("Tipo"); ?></label>
				<div class='controls controls-row'>
					<select name='consumidor_revenda' id='consumidor_revenda' class='frm'>
						<option value=''>Todas</option>
						<option value='C' <? if ($consumidor_revenda == 'C') echo 'selected';?>><?php echo traduz("Consumidor"); ?></option>
						<option value='R' <? if ($consumidor_revenda == 'R') echo 'selected';?>><?php echo traduz("Revenda"); ?></option>
					</select>
				</div>
			</div>
		</div>
	</div>
    <?php if (in_array($login_fabrica, array(169,170))){ ?>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <label class="control-label"><?php echo traduz("Status"); ?></label>
            <div class='controls controls-row'>
                <select name="status_os[]" multiple="multiple" id='status_os' class="frm" >
                    <?php
                        $array_status = array(
                            '0' => 'Aberta Call-Center',
                            '14' => 'Aguardando Auditoria',
                            '1' => 'Aguardando Analise',
                            '3' => 'Aguardando Conserto',
                            '30' => 'Aguardando Fechamento',
                            '2' => 'Aguardando Peças',
                            '8' => 'Aguardando Produto',
                            '4' => 'Aguardando Retirada',
                            '9' => 'Finalizada',
                            '28' => 'OS Cancelada'
                        );

                        foreach ($array_status as $key => $value) {
                            $selected_status = ( isset($status_os) and ($status_os == $key) ) ? "SELECTED" : '' ;

                            if(isset($status_os)){
                                foreach ($status_os as $id) {
                                    if ( isset($status_os) && ($id == $key) ){
                                        $selected_status[] = $id;
                                    }
                                }
                            }
                    ?>
                            <option value="<?=$key?>" <?php if( in_array($key, $selected_status)) echo "SELECTED"; ?> > <?echo $value?> </option>
                    <?
                        }
                    ?>
                </select>
            </div>
        </div>
        <div class='span6'></div>
    </div>
    <?php } ?>
	<? if($login_fabrica == 3){?>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='marca'><?php echo traduz("Marca"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<?php
								$sql = "SELECT  *
										FROM    tbl_marca
										WHERE   tbl_marca.fabrica = $login_fabrica
										ORDER BY tbl_marca.nome;";
								$res = pg_exec ($con,$sql);

								if (pg_numrows($res) > 0) {
									echo "<select name='marca' class='frm'>\n";
									echo "<option value=''>" . traduz("ESCOLHA") ."</option>\n";
									for ($x = 0 ; $x < pg_numrows($res) ; $x++){
										$aux_marca = trim(pg_result($res,$x,marca));
										$aux_nome  = trim(pg_result($res,$x,nome));

										echo "<option value='$aux_marca'";
										if ($marca == $aux_marca){
											echo " selected ";
										}
										echo ">$aux_nome</option>\n";
									}
									echo "</select>\n&nbsp;";
								}else{echo traduz("vazio");}
								?>
						</div>
					</div>
				</div>
			</div>
		</div>
    </form>
	<?php
    }

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
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='marca'><?php echo traduz("Marca"); ?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select name="marca" id="marca">
                                <option value="">&nbsp;</option>
                                <?php
                                foreach($marcas as $chave => $valor){
                                ?>
                                <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?
    }
    ?>

    <?php if ($login_fabrica == 15){ ?>
		<div class='row-fluid'>
			<div class='span2'></div>

				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='tipo'><?php echo traduz("Pedido de Peça"); ?></label>
						<div class='controls controls-row'>
							<select name='os_peca'>
								<option value='s'><?php echo traduz("OS sem peça"); ?>s</option>
								<option value='c'><?php echo traduz("OS com peça"); ?></option>
								<option value='t'><?php echo traduz("Todas OS"); ?></option>
							</select>
						</div>
					</div>
				</div>
			<div class='span2'></div>
		</div>

    <?php } ?>

    <?php if($login_fabrica == 164){ ?>

        <div class="row-fluid">

            <div class='span2'></div>
            <div class='span3'>
                <input type="hidden" name="posto_id" id="posto_id" value="">
                <div class='control-group'>
                    <label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto"); ?></label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?=$codigo_posto?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='descricao_posto'><?php echo traduz("Nome Posto"); ?></label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?=$descricao_posto?>" >&nbsp;
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>

        </div>

    <?php } ?>

    <br />

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span10'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>

                <label class="radio">
				   <input type="radio" name="data_filtro" value="finalizadas" <? if($data_filtro == 'finalizadas' OR $data_filtro == ''){ ?> checked <?}?> ><?php echo traduz("Apenas OS finalizadas"); ?>
                </label>

                &nbsp;

                <label class="radio">
				   <input type="radio" name="data_filtro" value="nao_finalizada" <? if ($data_filtro == 'nao_finalizada'){?> checked <?}?> ><?php echo traduz("Apenas OS abertas"); ?>
                </label>

				<?php
				//Ao incluir uma fábrica para estes filtros, verificar se utiliza vários defeito constatado e solução ou um só. Dependendo do caso, a montagem da SQL deve ser enquadrada corretamente após submeter o formulário
				if ($login_fabrica == 81){?>

                    &nbsp;

                    <label class="radio">
					    <input type="radio" name="data_filtro" value="analisadas" <? if ($data_filtro == 'analisadas'){?> checked <?}?> ><?php echo traduz("Apenas OS analisadas (COM defeito e solução)"); ?>
                    </label>

                    &nbsp;

                    <label class="radio">
					    <input type="radio" name="data_filtro" value="nao_analisadas" <? if ($data_filtro == 'nao_analisadas'){?> checked <?}?> > <?php echo traduz("Apenas OS não analisadas (SEM defeito e solução)"); ?>
                    </label>

				<?}?>

			</div>
		</div>
		<div class='span2'></div>
	</div>

	<input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
	<div class="row-fluid">
        <!-- margem -->
        <div class="span4"></div>

        <div class="span4">
            <div class="control-group">
                <div class="controls controls-row tac">
                    <button type="button" class="btn" value="Gravar" alt="Gravar formulário" onclick="submitForm($(this).parents('form'),1);" > <?php echo traduz("Pesquisar"); ?></button>
                </div>
            </div>
        </div>

        <!-- margem -->
        <div class="span4"></div>
    </div>

</form>

</div>

<!-- =========== AQUI TERMINA O FORMULRIO FRM_PESQUISA =========== -->
<?
flush();

if ($listar=="ok"){
	if (count($linha)) {
		$cond_1 = " AND tbl_produto.linha IN (";
		for($i = 0; $i < count($linha); $i++){
			if($i == count($linha)-1 ){
				$cond_1 .= $linha[$i].")";
			}else {
				$cond_1 .= $linha[$i].", ";
			}
		}
	}

    if (count($status_os)) {
        $xstatus_os = implode(',', $status_os);
        $cond_status_os = " AND tbl_os.status_checkpoint IN ($xstatus_os) ";
    }

	if (strlen($marca)>0) $cond_1 = " AND tbl_produto.marca=$marca ";

	switch($consumidor_revenda) {
		case "C":
			$cond_consumidor_revenda = " AND tbl_os.consumidor_revenda='C' ";
		break;

		case "R":
			$cond_consumidor_revenda = " AND tbl_os.consumidor_revenda='R' ";
		break;

		default:
			$cond_consumidor_revenda = "  ";
	}

	switch($data_filtro) {
		case "finalizadas":
			$cond_x = " AND tbl_os.data_fechamento IS NOT NULL ";
		break;

		case "nao_finalizada":
			$cond_x = " AND tbl_os.data_fechamento IS NULL AND tbl_os.finalizada IS NULL ";
		break;

		case "analisadas":
			$cond_x = " AND tbl_os.defeito_constatado IS NOT NULL AND tbl_os.solucao_os IS NOT NULL ";
		break;

		case "nao_analisadas":
			$cond_x = " AND tbl_os.defeito_constatado IS NULL AND tbl_os.solucao_os IS NULL ";
		break;
	}

	if($login_fabrica==14){
		$xsolucao     = " tbl_servico_realizado.descricao AS solucao";
		$join_solucao = " LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado=tbl_os.solucao_os ";
	}else{
		$xsolucao     = " tbl_solucao.descricao AS solucao";
		$join_solucao = " LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os.solucao_os ";

		if($login_fabrica == 163){
			$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
			$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
		}
	}

	$sql = "SELECT DISTINCT(tbl_os.os)
		INTO TEMP tmp_fcr_ossempeca_$login_admin
		FROM tbl_os
		JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
		JOIN tbl_os_produto ON tbl_os.os              = tbl_os_produto.os
		JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
		$join_163
		WHERE tbl_os.fabrica=$login_fabrica
		AND (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
		$cond_1 $cond_2 $cond_x $cond_posto $cond_163;

		SELECT 	tbl_os.os,
			tbl_os.sua_os,
			tbl_os_produto.servico,
			tbl_posto.nome AS posto_nome,
			tbl_os.defeito_reclamado,
			CASE WHEN tbl_os.fabrica in (15,122,81,114,124,123)
				THEN tbl_os.defeito_reclamado_descricao
			ELSE
				tbl_defeito_reclamado.descricao
			END as defeito_reclamado_descricao,
			tbl_os.defeito_constatado,
			tbl_defeito_constatado.descricao as defeito_constatado_descricao,
			tbl_os.solucao_os,
			tbl_defeito_constatado_grupo.descricao as defeito_constatado_grupo,
			$xsolucao ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		,
			to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento	,
			tbl_os.fabrica,
			tbl_produto.descricao as produto_descricao,
			tbl_produto.referencia_fabrica as produto_referencia_fabrica,
			tbl_os.consumidor_revenda
		INTO TEMP tmp_fcr_ossempeca2_$login_admin
		FROM tbl_os
		JOIN tbl_produto on tbl_produto.produto=tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado=tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica=$login_fabrica
		LEFT JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado=tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica=$login_fabrica
		$join_solucao
		$join_163
		LEFT JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_os.defeito_constatado_grupo

		LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
		WHERE (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
		AND tbl_os.fabrica = $login_fabrica
		$cond_1 $cond_2
		$cond_x
		$cond_consumidor_revenda
        $cond_posto
        $cond_163
        $cond_status_os
		AND tbl_os.os NOT IN( select os from tmp_fcr_ossempeca_$login_admin);

		SELECT * FROM  tmp_fcr_ossempeca2_$login_admin X
		ORDER BY X.defeito_reclamado_descricao, X.defeito_constatado_descricao, X.defeito_constatado_descricao";

    if (in_array($login_fabrica, array(138))) {
                $sql = "SELECT DISTINCT tbl_os_produto.os
                            INTO TEMP tmp_fcr_ossempeca_$login_admin
                            FROM tbl_os_produto
                            JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i=$login_fabrica
                            JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
                            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
                            WHERE tbl_os.fabrica = $login_fabrica
                            AND (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
                            $cond_1 $cond_2 $cond_x;

                            SELECT DISTINCT tbl_os.os,
                                        tbl_os.sua_os,
                                        tbl_posto.nome AS posto_nome,
                                        op.defeito_reclamado,
                                        tbl_os.defeito_reclamado_descricao,
                                        COALESCE(array_to_string(array(
                                                    SELECT tbl_defeito_constatado.descricao
                                                    FROM tbl_os_produto
                                                    JOIN tbl_os USING(os)
                                                    LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado
                                                    WHERE (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
                                                    AND tbl_os.fabrica = $login_fabrica
                                                    $cond_1 $cond_2
                                                    $cond_x
                                                    $cond_consumidor_revenda
                                                    AND tbl_os_produto.os NOT IN (SELECT os FROM tmp_fcr_ossempeca_$login_admin)
                                                    AND tbl_os_produto.os = op.os
                                                    ), '<br />')) AS defeito_constatado_descricao,
                                        tbl_os.solucao_os,
                                        tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo,
                                        $xsolucao,
                                        to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura,
                                        to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
                                        tbl_os.fabrica,
                                        COALESCE(array_to_string(array(
                                                    SELECT tbl_produto.descricao
                                                    FROM tbl_produto
                                                    JOIN tbl_os_produto USING(produto)
                                                    JOIN tbl_os USING(os)
                                                    WHERE (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
                                                    AND tbl_os.fabrica = $login_fabrica
                                                    $cond_1 $cond_2
                                                    $cond_x
                                                    $cond_consumidor_revenda
                                                    AND tbl_os_produto.os NOT IN (SELECT os FROM tmp_fcr_ossempeca_$login_admin)
                                                    AND tbl_os_produto.os = op.os
                                                    ), '<br />')) AS produto_descricao,
                                        tbl_os.consumidor_revenda
                            INTO TEMP tmp_fcr_ossempeca2_$login_admin
                            FROM tbl_os_produto op
                            JOIN tbl_os USING(os)
                            JOIN tbl_produto ON tbl_produto.produto = op.produto AND tbl_produto.fabrica_i=$login_fabrica
                            LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = op.defeito_reclamado
                            LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = op.defeito_constatado
                            $join_solucao
                            LEFT JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                            LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_os.defeito_constatado_grupo
                            WHERE (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
                            AND tbl_os.fabrica = $login_fabrica
                            $cond_1 $cond_2
                            $cond_x
                            $cond_consumidor_revenda
                            AND op.os NOT IN (SELECT os FROM tmp_fcr_ossempeca_$login_admin);

                            SELECT * FROM  tmp_fcr_ossempeca2_$login_admin X
                            ORDER BY X.defeito_reclamado_descricao, X.defeito_constatado_descricao, X.defeito_constatado_descricao";
    }

	if ($login_fabrica==15){
		$os_peca        = trim($_POST["os_peca"]);
		if ($os_peca=='t'){
			$cond_3=" AND ( tbl_os.os NOT IN( select os from tmp_fcr_ossempeca_$login_admin)
					  OR tbl_os.os IN( select os from tmp_fcr_ossempeca_$login_admin) );";
		}
		if ($os_peca=='s'){
			$cond_3=" AND tbl_os.os NOT IN( select os from tmp_fcr_ossempeca_$login_admin);";
		}
		if ($os_peca=='c'){
			$cond_3=" AND tbl_os.os IN( select os from tmp_fcr_ossempeca_$login_admin);";
		}

		$sql = "SELECT DISTINCT(tbl_os.os)
			INTO TEMP tmp_fcr_ossempeca_$login_admin
			FROM tbl_os
			JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
			JOIN tbl_os_produto ON tbl_os.os              = tbl_os_produto.os
			JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
			WHERE tbl_os.fabrica=$login_fabrica
			AND (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
			$cond_1 $cond_2;

			SELECT 	tbl_os.os,
				tbl_os.sua_os,
				tbl_posto.nome AS posto_nome,
				tbl_os.defeito_reclamado,
				CASE WHEN tbl_os.fabrica = 15
					THEN tbl_os.defeito_reclamado_descricao
				ELSE
					tbl_defeito_reclamado.descricao
				END as defeito_reclamado_descricao,
				tbl_os.defeito_constatado,
				tbl_defeito_constatado.descricao as defeito_constatado_descricao,
				tbl_os.solucao_os,
				$xsolucao ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento	,
				tbl_os.fabrica,
				tbl_produto.descricao as produto_descricao,
				tbl_produto.referencia_fabrica as produto_referencia_fabrica
			INTO TEMP tmp_fcr_ossempeca2_$login_admin
			FROM tbl_os
			JOIN tbl_produto on tbl_produto.produto=tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
			LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado=tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica=$login_fabrica
			LEFT JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado=tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica=$login_fabrica
			$join_solucao
			LEFT JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
			WHERE (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
			AND tbl_os.fabrica = $login_fabrica
			$cond_1 $cond_2
			$cond_x
			$cond_consumidor_revenda
			$cond_3

			SELECT * FROM  tmp_fcr_ossempeca2_$login_admin X
			ORDER BY X.defeito_reclamado_descricao, X.defeito_constatado_descricao, X.defeito_constatado_descricao";

	}
            //echo nl2br($sql);exit;
	if($login_fabrica==24){
            $sql = "SELECT tbl_os.os	,
				tbl_os.sua_os,
				tbl_posto.nome	as posto_nome,
				tbl_os.defeito_reclamado,
				tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
				tbl_os.defeito_constatado,
				tbl_defeito_constatado.descricao as defeito_constatado_descricao,
				tbl_os.solucao_os,
				tbl_solucao.descricao as solucao,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
				tbl_os.fabrica,
				tbl_produto.descricao as produto_descricao,
				tbl_produto.referencia_fabrica as produto_referencia_fabrica,
				tbl_os.consumidor_revenda
			FROM tbl_os
			JOIN tbl_produto on tbl_produto.produto=tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
			left JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica = $login_fabrica
			left JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
			left JOIN tbl_solucao on tbl_solucao.solucao=tbl_os.solucao_os
			left JOIN tbl_os_produto on tbl_os_produto.os = tbl_os.os
			left JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
			left JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.troca_de_peca='f'
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
			WHERE (tSELECT * FROM tmp_fcr_ossempeca2_5044 X ORDER BY X.defeito_reclamado_descricao, X.defeito_constatado_descricao, X.defeito_constatado_descricaobl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
			AND tbl_os.fabrica=$login_fabrica ";
			if (strlen($linha)>0) $sql .=" AND tbl_produto.linha=$linha ";

		    $sql .=" AND tbl_os.data_fechamento notnull
				AND tbl_os.os NOT IN( SELECT DISTINCT(tbl_os.os)
							FROM tbl_os
							JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
							JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os
							JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto AND  tbl_os_item.fabrica_i=$login_fabrica
							JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.troca_de_peca='t'
							WHERE tbl_os.fabrica=$login_fabrica
							AND (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final') ";
							if (strlen($linha)>0) $sql .=" AND tbl_produto.linha=$linha ";

                        $sql .=" ) order by tbl_defeito_reclamado.descricao, tbl_defeito_constatado.descricao, tbl_servico_realizado.descricao";

	}

	if($login_fabrica == 35){
		$sql = "SELECT COUNT(tbl_os.os) AS total_os, tbl_posto.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_os
				JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
				LEFT JOIN tbl_os_produto ON tbl_os.os              = tbl_os_produto.os
				LEFT JOIN tbl_os_item USING(os_produto)
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.fabrica=$login_fabrica
				AND (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
				$cond_1 $cond_2 $cond_x $cond_consumidor_revenda
				AND tbl_os_item.os_item isnull
				GROUP BY tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto.posto
				ORDER BY total_os DESC,tbl_posto.nome";

		$linhas = implode(",",$linha);
		$parametros = "field_call_rate_lista_os_sem_peca_posto.php?data_ini=$aux_data_inicial&data_fim=$aux_data_final&cr=$consumidor_revenda&linhas=$linhas&data_filtro=$data_filtro";
	}

	$res = pg_query($con, $sql);
	$qtde = pg_num_rows($res);

	if(pg_num_rows($res)>0){

		if($login_fabrica==15){
			if ($os_peca=='s'){
				echo "<BR><BR><center><font style='font:16px Arial;'>". traduz('Foram encontradas ')."$qtde ".traduz('OS sem peça.') . "</font></center><BR>";
			}
			elseif ($os_peca=='c'){
				echo "<BR><BR><center><font style='font:16px Arial;>". traduz('Foram encontradas ')."$qtde ".traduz('OS com peça.') . "</font></center><BR>";
			}
			elseif ($os_peca=='t'){
				echo "<BR><BR><center><font style='font:16px Arial;'>". traduz('Foram encontradas ')."$qtde ".traduz('OS com e sem peça.') . "</font></center><BR>";
			}
		}else{
			if($login_fabrica == 35){
				echo "<BR><BR><center><font style='font:16px Arial;'>". traduz('Foram encontrados ')."$qtde ".traduz('Postos com OS sem peça.') . "</font></center><BR>";
			}else{
				echo "<BR><BR><center><font style='font:16px Arial;'>". traduz('Foram encontradas ')."$qtde ".traduz('OS sem peça.') . "</font></center><BR>";
			}

		}?>
		<table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large' style="min-width: 850px;">
			<thead>
				<?php
					if($login_fabrica == 35){
				?>
						<tr class='titulo_tabela'>
							<td><?php echo traduz("Posto"); ?></td>
							<td><?php echo traduz("Total OS"); ?>s</td>
						</tr>
				<?php
					}else{
				?>

					<tr class='titulo_tabela'>
						<td><?php echo traduz("OS"); ?></td>
						<td><?php echo traduz("C/R"); ?></td>
                        <?php if($login_fabrica == 164){ ?>
                            <td><?php echo traduz("Posto"); ?></td>
                        <?php } ?>
                        <?php if($login_fabrica == 171){ ?>
                            <td><?php echo traduz("Referência Fábrica"); ?></td>
                        <?php } ?>
						<td><?php echo traduz("Produto"); ?></td>
						<td><?php echo traduz("Abertura"); ?></td>
						<td><?php echo traduz("Fechamento"); ?></td>
						<td><?php echo traduz("Defeito Reclamado"); ?></td>
                        <?php if($login_fabrica == 52){ ?>
							<td><?php echo traduz("Grupo Defeito"); ?></td>
					    <?php } ?>
						<td><?php echo traduz("Defeito Constatado"); ?></td>
						<?php if ($login_fabrica == 72) { ?>
						    <td><?php echo traduz("Falha em Potencial"); ?></td>
						<?php
						}

						if (!isset($novaTelaOs)) {
						?>
							<td><?php echo traduz("Solução"); ?></td>
						<?php
						}
						?>
					</tr>
				<?php
					}
				?>

			</thead><tbody> <?


		for ($i=0; $i<pg_numrows($res); $i++){

			if($login_fabrica == 35){
				$posto			= trim(pg_result($res,$i,posto));
				$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
				$nome_posto 	= trim(pg_result($res,$i,nome));
				$total_os 		= trim(pg_result($res,$i,total_os));
			}else{
				$os								= trim(pg_result($res,$i,os));
				$sua_os							= trim(pg_result($res,$i,sua_os));
				$defeito_reclamado_descricao 	= trim(pg_result($res,$i,defeito_reclamado_descricao));
				$defeito_constatado_descricao 	= trim(pg_result($res,$i,defeito_constatado_descricao));

				if ($login_fabrica == 52) {
					$defeito_constatado_grupo       = trim(pg_result($res,$i,defeito_constatado_grupo));
				}
				$nome_falha = "";
				if ($login_fabrica == 72) {
					$defeito_constatado 	= trim(pg_result($res,$i,defeito_constatado));
					$servico 	= trim(pg_result($res,$i,servico));
					$sqlFalha = "SELECT
	                            tbl_servico.descricao AS nome_falha,
	                            tbl_defeito_constatado.descricao AS nome_defeito,
	                            tbl_diagnostico.diagnostico,
	                            tbl_diagnostico.defeito_constatado,
	                            tbl_diagnostico.servico
	                    FROM    tbl_diagnostico
	                    JOIN tbl_servico ON tbl_servico.servico=tbl_diagnostico.servico
	                    JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado=tbl_diagnostico.defeito_constatado
	                    WHERE tbl_diagnostico.fabrica = $login_fabrica
	                    and  tbl_diagnostico.servico = $servico
	                    and  tbl_diagnostico.defeito_constatado = $defeito_constatado
	                    ORDER BY tbl_diagnostico.diagnostico DESC;";
	                $resFalha = pg_query($con, $sqlFalha);
	                if (pg_num_rows($resFalha) > 0) {
	                    $nome_falha = pg_fetch_result($resFalha, 0, 'nome_falha');
	                }
                }

				$solucao 						= trim(pg_result($res,$i,solucao));
				$abertura 						= trim(pg_result($res,$i,abertura));
				$fechamento 					= trim(pg_result($res,$i,fechamento));
				$posto_nome 					= trim(pg_result($res,$i,posto_nome));
				$produto_descricao				= trim(pg_result($res,$i,produto_descricao));
				$referencia_fabrica				= trim(pg_result($res,$i,produto_referencia_fabrica));
				$consumidor_revenda_banco		= pg_result($res, $i, consumidor_revenda);
			}?>

			<?php
				if($login_fabrica == 35){
			?>
				<tr bgcolor='<?=$cor?>'>
					<td align='left' nowrap>
						<a href="javascript:void(0);" onclick="listaOS('<?=$parametros?>&posto=<?=$posto?>');">
							<font size='1'><?=$codigo_posto?> - <?=$nome_posto?></font>
						</a>
					</td>
					<td align='center' nowrap><font size='1'><?=$total_os?></font></td>
				</tr>
			<?php
				}else{
			?>
					<tr bgcolor='<?=$cor?>'>
						<td align='left'><a href='<?="os_press.php?os=$os"?>' target='blank'><font size='1'><?=$sua_os?></font></a></td>
						<td><font size='1'><?=$consumidor_revenda_banco?></font></td>
					    <? if(in_array($login_fabrica, array(164))){ echo "<td align='left'>$posto_nome</td>"; } ?>
					    <?php if(in_array($login_fabrica, array(171))){ echo "<td align='left'>$referencia_fabrica</td>"; } ?>
						<td align='left' nowrap><font size='1'><?=$produto_descricao?></font></td>
						<td><font size='1'><?=$abertura?></font></td>
						<td><font size='1'><?=$fechamento?></font></td>
						<td align='left' nowrap><font size='1'><?=$defeito_reclamado_descricao?></font></td><?
						if($login_fabrica==52){?>
							<td align='left' nowrap><font size='1'><?=$defeito_constatado_grupo?></font></td>
						<?}?>
						<td align='left' nowrap><font size='1'><?=$defeito_constatado_descricao?></font></td>
						<?php if ($login_fabrica == 72) {?>
							<td align='left' nowrap><font size='1'><?php echo $nome_falha;?></font></td>
						<?php
						}

						if (!isset($novaTelaOs)) {
						?>
							<td align='left' nowrap><font size='1'><?=$solucao?></font></td>
						<?php
						}
?>
					</tr>
					<?php
					}
					?>
		<? } ?>
		<tbody></table>
	<? }else{ ?>
		<br><center><?php echo traduz("Não foram Encontrados Resultados para esta Pesquisa."); ?></center>
	<? }
}

include "rodape.php";
?>
