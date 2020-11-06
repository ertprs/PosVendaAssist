<?php

$admin_privilegios  = "cadastros";
$layout_menu 		= "cadastro";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

if( in_array($login_fabrica,array(134)) ){
    $tema = traduz("Serviço Realizado");
    $temaMaiusculo = traduz("SERVIÇO REALIZADO");
}else{
    $tema = traduz("Defeito Constatado");
    $temaMaiusculo = traduz("DEFEITO CONSTATADO");
}

/* inicio edição de integridade*/

if (isset($_GET['excluir']) || isset($_POST['ajax'])) {
	$diagnostico = (isset($_POST['id'])) ? $_POST['id'] : (int) $_GET['excluir'];
	$sql_Auditor = "SELECT * FROM tbl_diagnostico WHERE tbl_diagnostico.diagnostico = ".$diagnostico;

	unset($AuditorLog);
    $AuditorLog = new AuditorLog;
    $AuditorLog->retornaDadosSelect($sql_Auditor);
}

if(isset($_POST['ajax'])){
    if(in_array($login_fabrica, array(152,180,181,182))) {
        $valor = $_POST['valor'];
        $id = $_POST['id'];
        $valor = intval($valor);
        if($valor == "" or !is_int($valor)){
            $valor = "0";
        }

        $sql = "UPDATE tbl_diagnostico set tempo_estimado = ".$valor." where diagnostico = ".$id;
        $res = pg_query($con,$sql);
        if (pg_errormessage ($con) > 0){
            echo "false";
        }else{
			$AuditorLog->retornaDadosSelect($sql_Auditor)->EnviarLog('update', 'tbl_diagnostico',"$login_fabrica");
            echo "true";
        }

    }elseif( in_array($login_fabrica,array(134,136,158)) ){

        $valor = $_POST['valor'];
        $id = $_POST['id'];
        $valor = str_replace(',','.',$valor);

        $sql = "UPDATE tbl_diagnostico set mao_de_obra = ".$valor." where diagnostico = ".$id;
        $res = pg_query($con,$sql);
        if (pg_errormessage ($con) > 0){
            echo "false";
        }else{
			$AuditorLog->retornaDadosSelect($sql_Auditor)->EnviarLog('update', 'tbl_diagnostico',"$login_fabrica");
            echo "true";
        }
    }
    exit;
}

/* inicio exclusao de integridade */
if (isset($_GET['excluir']) ) {

    $id = (int) $_GET['excluir'];
    if(!empty($id)) {
        $sql = pg_query($con, 'DELETE FROM tbl_diagnostico WHERE diagnostico =' . $id );
        $msg = traduz("Excluído com sucesso");
        $AuditorLog->retornaDadosSelect($sql_Auditor)->EnviarLog('delete', 'tbl_diagnostico',"$login_fabrica");
    }
}
/* fim exclusao */

// ----- Inicio do cadastro ----------

if ( isset($_POST['gravar'] ) ) {
    if( !empty($_POST['familia']) && !empty($_POST['defeito'])  ) {
        $familias   = array();
        $defeitos   = array();
        $garantias  = array();


        foreach( $_POST['familia'] as $familia )
            $familias[] = ($familia);


        if($login_fabrica == 160 or $replica_einhell){
            foreach( $_POST['garantia'] as $garantia )
                $garantias[] = ($garantia);
        }

        foreach( $_POST['defeito'] as $defeito )
            $defeitos[] = ($defeito);

        foreach( $_POST['mao_de_obra'] as $mao_de_obra )
            $mao_de_obras[] = ($mao_de_obra);

        foreach( $_POST['tempo_estimado'] as $tempo_estimado )
            $tempo_estimados[] = intval($tempo_estimado);

        $array_id_alterado = array();
        for ( $i = 0; $i < count($familias); $i++ ) {
            $sql = pg_query($con, 'SELECT *
                FROM tbl_diagnostico
                WHERE tbl_diagnostico.familia = ' . $familias[$i] .
                ' AND tbl_diagnostico.defeito_constatado = ' . $defeitos[$i] .
                ' AND tbl_diagnostico.defeito_reclamado isnull ' .
                ' AND fabrica = ' . $login_fabrica);
            $sql_Auditor = $sql;
            if(pg_numrows($sql) > 0)
                $array_id_alterado[pg_fetch_result($sql, 0, diagnostico)] = pg_fetch_result($sql, 0, diagnostico);
        }
        if (count($array_id_alterado)) {
            $AuditorLog = new AuditorLog;
            $AuditorLog->retornaDadosSelect('SELECT *
            FROM tbl_diagnostico
            WHERE tbl_diagnostico.diagnostico IN('.implode(',', $array_id_alterado).')');
        }

        $array_id_inserido = array();

        for ( $i = 0; $i < count($familias); $i++ ) {
            unset($diagnostico_id);
            $sql = pg_query($con, 'SELECT *
                FROM tbl_diagnostico
                WHERE tbl_diagnostico.familia = ' . $familias[$i] .
                ' AND tbl_diagnostico.defeito_constatado = ' . $defeitos[$i] .
                ' AND tbl_diagnostico.defeito_reclamado isnull ' .
                ' AND fabrica = ' . $login_fabrica);
            $sql_Auditor = $sql;
            if(pg_numrows($sql) > 0)
                $diagnostico_id = pg_fetch_result($sql, 0, diagnostico);

            if( in_array($login_fabrica,array(134,136,158)) ){
                $campoAdicional = " ,mao_de_obra ";
                if(empty($mao_de_obras[$i])) $mao_de_obras[$i] = 0 ;
                $valueAdicional = " ,".str_replace(',','.',$mao_de_obras[$i]);
            }elseif(in_array($login_fabrica, array(152,180,181,182))) {
                $campoAdicional = " ,tempo_estimado ";
                $valueAdicional = " ,".$tempo_estimados[$i] ;
            }else{
                $campoAdicional = "";
                $valueAdicional = "";
            }

            if($login_fabrica == 160 or $replica_einhell){
                $campoGarantia = " , garantia ";

                if($garantias[$i] == 'garantia'){
                    $resultadoGarantia = 't';
                }else{
                    $resultadoGarantia = 'f';
                }

                $valueGarantia = " , '$resultadoGarantia' ";

            }else{
                $campoGarantia = " ";
                $valueGarantia = " ";
            }

            if (!empty($diagnostico_id)) {
                unset($sql);
                
                if (!empty($campoAdicional) && strlen($campoAdicional) > 1) {
                    $sql = str_replace(",", "", $campoAdicional)." = ".str_replace(",", "", $valueAdicional);
                }
                
                if (!empty($campoGarantia) && strlen($campoGarantia) > 1) {
                    if (!empty($sql))
                        $sql .= " AND ";

                    $sql .= " $campoGarantia = $valueGarantia";
                }
                
                $sql = "UPDATE tbl_diagnostico SET $sql WHERE diagnostico = $diagnostico_id AND fabrica = $login_fabrica";
                $query = pg_query($con,$sql);

                if (strlen (pg_last_error($con)) > 0) {
                    $msg_erro = traduz('Erro ao tentar atualizar');
                    break;
                }

            }else{
                $sql = 'INSERT INTO tbl_diagnostico (
                        fabrica,
                        familia,
                        defeito_constatado,
                        admin '.$campoAdicional.''.$campoGarantia.'
                    ) VALUES (
                        '.$login_fabrica.',
                        '.$familias[$i].',
                        '.$defeitos[$i].',
                        '.$login_admin.' '.$valueAdicional.''.$valueGarantia.'
                    ) RETURNING diagnostico;';
                $query = pg_query($con,$sql);

                if (strlen (pg_last_error($con)) > 0) {
                    $msg_erro = traduz('Erro ao tentar atualizar');
                    break;
                }else{
                    $array_id_inserido[] = pg_fetch_result($query, 0, diagnostico);
                    $iddiagnostico = pg_fetch_result($query, 0, diagnostico);
                }
            }
        }

        if(empty($msg_erro)) {
            $msg = traduz("Gravado com sucesso");
            
            if (count($array_id_alterado)) {
                $AuditorLog->retornaDadosSelect()->EnviarLog('update', 'tbl_diagnostico',"$login_fabrica");
            }

            if (count($array_id_inserido)) {
                unset($AuditorLog);
                $AuditorLog = new AuditorLog('insert');
                $AuditorLog->retornaDadosSelect('SELECT *
                    FROM tbl_diagnostico
                    WHERE tbl_diagnostico.diagnostico IN('.implode(',', $array_id_inserido).')')->EnviarLog('insert', 'tbl_diagnostico',"$login_fabrica");
            }
        }
    } else {
        $msg_erro = traduz('Escolha um defeito e uma família');
    }
}
// fim cadastro

$title = traduz("INTEGRAÇÃO FAMÍLIA - ").$temaMaiusculo;

include 'cabecalho_new.php';
$plugins = array("shadowbox", "alphanumeric", "price_format","dataTable");

include("plugin_loader.php");

if( $login_fabrica == 101 ) { // HD 677430
    $sql= "SELECT descricao
           FROM tbl_defeito_constatado
           WHERE fabrica = $login_fabrica
           AND orientacao IS TRUE";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) ) {
        $defeitos_orientacao = array();
        for($i=0;$i<pg_num_rows($res); $i++ ) {
            $defeitos_orientacao[] = pg_result($res,$i,0);
        }
        $defeitos_orientacao = implode (', ',$defeitos_orientacao);
?>
    <div class="well well-small">
        Para o(s) Defeito(s) Constatado(s) <b><?=$defeitos_orientacao ?></b> será utilizada Mão de Obra Diferenciada, conforme cadastrado no cadastro de Produtos
    </div><br />
<?php
        }
    } //FIM HD 677430

if (strlen($msg) > 0) {
?>
<div class="alert alert-success">
	<h4><?=$msg;?></h4>
</div>
<script type="text/javascript">
    $(".alert-success").fadeIn();
    setTimeout(function(){
        $('.alert-success').fadeOut();
    }, 3000);
</script>
<?php
}
?>

	<script type="text/javascript">

    $(function() {
    	var table = new Object();
        table['table'] = '#tabela_defeitos';
        table['type'] = 'full';
        $.dataTableLoad(table);                                                                                                                                                               
    });
</script>

<div id="msg_alerta" class="alert alert-error" style="display: none;">
	<h4></h4>
</div>
<div class="row">
		<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
	</div>
<div class="form-search form-inline tc_formulario">
	<div class='titulo_tabela '><?=traduz('Cadastro')?></div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div id="cg_familia" class='control-group'>
				<label class='control-label' for='familia'><?=traduz('Família')?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
					<h5 class='asteristico'>*</h5>
		            <select name="familia" id="familia" class="frm">
		                <option value=""></option>
		                <?php
		                    $sql ="SELECT familia, descricao, codigo_familia from tbl_familia where fabrica=$login_fabrica AND ativo = 't' order by descricao;";
		                    $res = pg_query ($con,$sql);
		                    for ($y = 0 ; $y < pg_numrows($res) ; $y++){
		                        $familia            = trim(pg_result($res,$y,familia));
		                        $descricao          = trim(pg_result($res,$y,descricao));
                                $codigo_familia     = trim(pg_result($res,$y,codigo_familia));
		                        echo "<option value='$familia'";
		                        if ($familia == $aux_familia) echo " SELECTED ";
                                if($login_fabrica == 19){
                                    echo ">$codigo_familia - $descricao</option>";
                                }else{
                                    echo ">$descricao</option>";
                                }
		                    }
		                ?>
		            </select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div id="cg_defeito" class='control-group'>
				<label class='control-label' for='defeito'><?=$tema?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<select name="defeito" id="defeito" class="frm">
			                <option value=""></option>
			                <?php
			                    $sql ="SELECT defeito_constatado, descricao, codigo from tbl_defeito_constatado where fabrica=$login_fabrica and ativo='t' order by descricao;";
			                    $res = pg_query ($con,$sql);
			                    for ($y = 0 ; $y < pg_numrows($res) ; $y++){
			                        $defeito_constatado   = trim(pg_result($res,$y,defeito_constatado));
			                        $descricao = trim(pg_result($res,$y,descricao));
			                        $codigo = trim(pg_result($res,$y,codigo));
			                        echo '<option value="'.$defeito_constatado.'"';

			                        if (in_array($login_fabrica, array(19,30)) ) {
			                            echo ">$codigo - $descricao</option>";
			                        } else {
			                            echo ">$descricao</option>";
			                        }
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
		<?php if($login_fabrica == 160 or $replica_einhell){?>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='garantia'><?=traduz('Garantia')?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="checkbox" name="garantia" id="garantia" value="garantia">
					</div>
				</div>
			</div>
		</div>
		<?php }
		if(in_array($login_fabrica,array(134,136,158))){
		?>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='mao_de_obra'><?=traduz('Mão de Obra')?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="mao_de_obra" id='mao_de_obra' class='span12' value='0'/>
					</div>
				</div>
			</div>
		</div>
		<?php }
		if(in_array($login_fabrica, array(152,180,181,182))) {
		?>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='tempo_estimado'><?=traduz('Tempo Estimado (em minutos)')?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name='tempo_estimado' id='tempo_estimado' value=''/>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>
	<br />
	<div class='row-fluid'>
		<p align="center">
			<button type="button" class="btn" onclick="addDefeito()"><?=traduz('Adicionar')?></button>
		</p>
	</div>
</div>
<form class="form-search form-inline" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
	<div id="tabela" style="display: none;">
		<br />
		<table id="integracao" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class="titulo_coluna">
	                <th><?=traduz('Família')?></th>
	                <th><?=$tema?></th>
	                <?php
	                if( in_array($login_fabrica,array(134,136,158)) ){
	                    echo "<th>".traduz("Mão de Obra")."</th>";
	                }
	                if(in_array($login_fabrica, array(152,180,181,182))) {
	                    echo "<th>".traduz("Tempo Estimado (em minutos)")."</th>";
	                }

					if($login_fabrica == 160 or $replica_einhell){
	                    echo "<th>".traduz("Garantia")."</th>";
	                }?>
	                <th><?=traduz('Ações')?></th>
				</tr>
			</thead>
		</table>
		<p align="center">
			<input class="btn" type="submit" value='<?=traduz("Gravar")?>' name="gravar" />
		</p>
	</div>
</form>
<br />
<?php
    if($login_fabrica == 19){
        // colocar uma condição admin/familia_integridade_constatado.php  na tela, so trazer na tbl_diagnostico que
        // tem defeito_constatado e familia, caso tem solução ou defeito_reclamado, não traze
        $cond_19 = "    AND tbl_diagnostico.defeito_constatado IS NOT NULL
                        AND tbl_diagnostico.familia IS NOT NULL
                        AND tbl_diagnostico.solucao IS NULL
                        AND tbl_diagnostico.defeito_reclamado IS NULL";
    }
    $int_cadastrados = "SELECT
                        tbl_diagnostico.diagnostico,
                        tbl_diagnostico.mao_de_obra,
                        tbl_diagnostico.tempo_estimado,
                        tbl_diagnostico.garantia,
                        tbl_defeito_constatado.descricao as defeito_descricao,
                        tbl_defeito_constatado.codigo as defeito_codigo,
                        tbl_familia.descricao as familia_descricao,
			tbl_familia.codigo_familia as codigo_familia,
			tbl_diagnostico.ativo AS status
                        FROM tbl_diagnostico
                        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
                        JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia and tbl_familia.fabrica = $login_fabrica
                        WHERE tbl_diagnostico.fabrica = $login_fabrica
                        $cond_19
                        ORDER BY tbl_familia.descricao, tbl_defeito_constatado.descricao;";
    $query 	  = pg_query($con,$int_cadastrados);
    $num_rows = pg_num_rows($query);
    if ($num_rows > 0) {
?>
	<div id="cadastrados">
		<table class='table table-striped table-bordered table-hover table-fixed'id='tabela_defeitos'>
			<thead>
				<tr class='titulo_tabela'>
                    <?php
                    $colspan = 3;

                    if(in_array($login_fabrica,array(134,136,152,158,160,169,170,180,181,182)) or $replica_einhell)
                        $colspan = 4;
                    ?>
					<th colspan="<?php echo $colspan ?>"><?=traduz('Defeitos Cadastrados')?></th>
				</tr>
				<tr class='titulo_coluna'>
					<th><?=traduz('Família')?></th>
					<th><?=$tema?></th>
                    <?php
                    if (in_array($login_fabrica,array(134,136,158))){
                        echo "<th>".traduz("Mão de Obra")."</th>";
                    } elseif(in_array($login_fabrica, array(152,180,181,182))) {
                        echo "<th>".traduz("Tempo Estimado (em minutos)")."</th>";

                    } elseif($login_fabrica == 160 or $replica_einhell){

                        echo "<th>".traduz("Garantia")."</th>";
	                } elseif(in_array($login_fabrica,array(169,170))){
			             echo "<th>Status</th>";
	                }

                    ?>
					<th><?=traduz('Ações')?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					for ($i=0; $i < $num_rows; $i++) {
                        $familia = trim(pg_fetch_result($query,$i,"familia_descricao"));
                        $mao_de_obra = trim(pg_fetch_result($query,$i,"mao_de_obra"));
                        $tempo_estimado = trim(pg_fetch_result($query,$i,"tempo_estimado"));
                        $defeito = trim(pg_fetch_result($query,$i,"defeito_descricao"));

            			$mao_de_obra = (strlen($mao_de_obra) > 0) ? number_format($mao_de_obra,2,",",".") : $mao_de_obra;
                        $id = trim(pg_fetch_result($query,$i,"diagnostico"));
                        $codigo_familia = trim(pg_fetch_result($query,$i,"codigo_familia"));
                        $defeito_codigo = trim(pg_fetch_result($query,$i,"defeito_codigo"));

                        if($login_fabrica == 160 or $replica_einhell){
                            $garantia =  (trim(pg_fetch_result($query,$i,"garantia")) == 't') ? "Sim" : "Não" ;
			}

			if(in_array($login_fabrica,array(169,170))){
				$status =  (pg_fetch_result($query,$i,"status") == 't') ? "Ativo" : "Inativo";
			}
                        ?>
                        <tr>
                        <?php
                        if($login_fabrica == 131){
                        ?>
							<td><?=$familia;?></td>
							<td style="text-align: left;"><?=$defeito;?></td>
							<td align="center">
								<button class="btn btn-danger" id="BTRmo<?=$id;?>" onclick="deletaintegridade(<?=$id;?>)"><?=traduz('Remover')?></button>
	                        </td>
                        <?php
                        }else{
                            if($login_fabrica == 19){
                                echo '<td>'.$codigo_familia.' - '.$familia.'</td>';
                                echo '<td style="text-align: left;">'.$defeito_codigo.' - '.$defeito.'</td>';
                            }else{
                                echo '<td>'.$familia.'</td>';
                                echo '<td style="text-align: left;">'.$defeito.'</td>';
                            }

                            if($login_fabrica == 160 or $replica_einhell){
								echo '<td style="text-align: left;">'.$garantia.'</td>';
                            }
                            if( in_array($login_fabrica,array(134,136,158)) ){

                                echo '<td id="mo'.$id.'" style="text-align: right;">'.$mao_de_obra.'</td>';
                            }
                            if(in_array($login_fabrica, array(152,180,181,182))) {
                                echo '<td id="mo'.$id.'" style="text-align: right;">'.$tempo_estimado.'</td>';
			    }

			    if( in_array($login_fabrica,array(169,170)) ){
				echo '<td style="text-align: left;">'.$status.'</td>';
			    }

                            echo '<td style="text-align: center;">';
							if( in_array($login_fabrica,array(134,136,158)) ){
								echo '<button class="btn btn-info" id="BTEmo'.$id.'" onclick="editaMaoObra('.$id.',\''.trim($mao_de_obra).'\')">'.traduz("Editar").'</button> ';
                            }
                            if(in_array($login_fabrica, array(152,180,181,182))) {
                                echo '<button class="btn btn-info" id="BTEmo'.$id.'" onclick="editaTempoEstimado('.$id.',\''.trim($tempo_estimado).'\')">'.traduz("Editar").'</button> ';
                            }
                            echo '<button class="btn btn-danger" id="BTRmo'.$id.'" onclick="deletaintegridade('.$id.')">'.traduz("Remover").'</button> ';
                            echo '<button class="btn" id="BTGmo'.$id.'" onclick="gravaMaoObra('.$id.')" style="display:none">'.traduz("Gravar").'</button> ';

                            if( in_array($login_fabrica,array(134,136,158)) ){
                                echo '<button class="btn btn-danger" id="BTCmo'.$id.'" onclick="cancelaEdição('.$id.',\''.trim($mao_de_obra).'\')" style="display:none">'.traduz("Canc.").'</button> ';
                            }
                            if(in_array($login_fabrica, array(152,180,181,182))) {
                                echo '<button class="btn btn-danger" id="BTCmo'.$id.'" onclick="cancelaTempoEstimado('.$id.',\''.trim($tempo_estimado).'\')" style="display:none">'.traduz("Canc.").'</button> ';
                            }
                            echo '</td>';
                        }
                        ?>
                        </tr>
                        <?php
					}
				?>
			</tbody>
		</table>
	</div>
<?php } ?>
<script type="text/javascript">
    $(function(){
        $("#mao_de_obra").priceFormat({
            prefix: '',
            centsSeparator: ',',
            thousandsSeparator: '.'
        });
        $('#tempo_estimado').numeric({allow:","});
        Shadowbox.init();
    });
    i = 0;
	function addDefeito() {
        var defeito = $('#defeito').val();
        if ($('#garantia').is(":checked")) {
            var garantia = $('#garantia').val();
        }else{
            var garantia = "";
        }
        var familia = $("#familia").val();
        var mao_de_obra = $("#mao_de_obra").val();

        var tempo_estimado = $("#tempo_estimado").val();
        var txt_defeito = $('#defeito').find('option').filter(':selected').text();
        var txt_familia = $('#familia').find('option').filter(':selected').text();
        if(garantia.length > 0){
            var txt_garantia = "Sim";
        }else{
            var txt_garantia = "Não";
        }

        if(mao_de_obra != undefined){
            mao_de_obra = '<td style="text-align: right;"><input type="hidden" value="' + mao_de_obra + '" name="mao_de_obra['+i+']"  />' + mao_de_obra+'</td>';
        }

        <?php if($login_fabrica == 160 or $replica_einhell){?>
            var conteudo_garantia = '<td><input type="hidden" value="' + garantia + '" name="garantia['+i+']"  />' + txt_garantia + '</td>';
        <?php }else{ ?>
            var conteudo_garantia = '';
        <?}?>

        var htm_input = '<tr id="'+i+'"><td><input type="hidden" value="' + familia + '" name="familia['+i+']"  />' + txt_familia + '</td><td><input type="hidden" value="' + defeito + '" name="defeito['+i+']"  />' + txt_defeito+'</td>'+mao_de_obra+' '+conteudo_garantia+' <td> <button class="btn btn-danger" onclick="deletaitem('+i+')">Remover</button></td></tr>';

        <?php if(in_array($login_fabrica, array(152,180,181,182))) { ?>
            if(tempo_estimado != undefined){
                tempo_estimado = '<td style="text-align: right;"><input type="hidden" value="' + tempo_estimado + '" name="tempo_estimado['+i+']"  />' + tempo_estimado+'</td>';
            }
            var htm_input = '<tr id="'+i+'" ><td><input type="hidden" value="' + familia + '" name="familia['+i+']"  />' + txt_familia + '</td><td><input type="hidden" value="' + defeito + '" name="defeito['+i+']"  />' + txt_defeito+'</td>'+tempo_estimado+'<td> <button class="btn btn-danger" onclick="deletaitem('+i+')">Remover</button></td></tr>';
        <?php } ?>

        if (familia  === '' || defeito  === '') {
        	$('#msg_alerta').find('h4').text('<?=traduz("Preencha os campos obrigatórios")?>');
            $("#msg_alerta").fadeIn();
            setTimeout(function(){
                $('#msg_alerta').fadeOut();
            }, 3000);

        	if (familia  === ''){
        		$('#cg_familia').addClass('error');
        	}
        	if (defeito  === ''){
        		$('#cg_defeito').addClass('error');
        	}
            return false;
        }else {
            i++;
            $("#tabela").css("display","block");
            $(htm_input).appendTo("#integracao");
        }
    }
    function editaMaoObra(id,valor){
        $('#mo'+id).html('<input type="text" id="inp'+id+'" name="mo'+id+'" class="frm" style="width:63px" value="'+valor+'"/>');
        $('#inp'+id).focus();
        $('#inp'+id).priceFormat({
            prefix: '',
            centsSeparator: ',',
            thousandsSeparator: '.'
        });
        //$('#inp'+id).numeric({allow:","});

        $('#BTRmo'+id).fadeOut('500');
        $('#BTEmo'+id).fadeOut('500',function(){
            $('#BTGmo'+id).fadeIn('500');
            $('#BTCmo'+id).fadeIn('500');
        });
    }
    function editaTempoEstimado(id,valor){
        $('#mo'+id).html('<input type="text" id="inp'+id+'" name="mo'+id+'" class="frm" style="width:63px" value="'+valor+'"/>');
        $('#inp'+id).focus();
        $('#inp'+id).numeric();

        $('#BTRmo'+id).fadeOut('500');
        $('#BTEmo'+id).fadeOut('500',function(){
            $('#BTGmo'+id).fadeIn('500');
            $('#BTCmo'+id).fadeIn('500');
        });
    }
    function gravaMaoObra(id){
        valor = $('#inp'+id).val();
		$.ajax({
			url: "<?php echo $_SELF; ?>",
			data: { valor: valor, id: id, ajax: "true"},
			type: "POST",
			success: function(e){
				if(e == "true"){
					$('#mo'+id).html(valor);
					$('#BTGmo'+id).fadeOut('500');
					$('#BTCmo'+id).fadeOut('500',function(){
						$('#BTRmo'+id).fadeIn('500');
						$('#BTEmo'+id).fadeIn('500');
					});
				}else{
                    $('#msg_alerta').find('h4').text("Ocorreu um erro ao atualizar a mão de obra, tente novamente");
                    $("#msg_alerta").fadeIn();
                    setTimeout(function(){
                        $('#msg_alerta').fadeOut();
                    }, 3000);
				}
			}
		});
    }
    function cancelaEdição (id,valor) {
        $('#mo'+id).html(valor);

        $('#BTGmo'+id).fadeOut('500');
        $('#BTCmo'+id).fadeOut('500',function(){
            $('#BTRmo'+id).fadeIn('500');
            $('#BTEmo'+id).fadeIn('500');
        });
    }
    function cancelaTempoEstimado (id,valor) {
        $('#mo'+id).html(valor);

        $('#BTGmo'+id).fadeOut('500');
        $('#BTCmo'+id).fadeOut('500',function(){
            $('#BTRmo'+id).fadeIn('500');
            $('#BTEmo'+id).fadeIn('500');
        });
    }
    function deletaintegridade(id){
        if (confirm('<?=traduz("Deseja mesmo excluir essa integridade?")?>'))
            window.location='?excluir=' + id;
        else
            return false;
    }
    function limpaCampos(){
    	$('#familia').val('');
    	$('#defeito').val('');
    	$('#garantia').val('');
    	$('#mao_de_obra').val('0,00');
    	$('#tempo_estimado').val('');
    }
    function deletaitem(id) {
        $("#"+id).remove();

		var table = $('#integracao');

		var contador = 0;
		table.find('tr').each(function(indice){
			contador += 1;
		});
		if (contador <= 1) { $('#tabela').hide(); }
    }
</script>
<br />
<p align="center">
<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_diagnostico&id=<?php echo $login_fabrica; ?>' name="btnAuditorLog"><?=traduz('Visualizar Log Auditor')?></a>
</p>
<br />
<?php include 'rodape.php'; ?>
