<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];

	$status             = ($telecontrol_distrib || $login_fabrica == 189) ? "Resolvido" : $_POST['status'];

	$atendente          = $_POST['atendente'];
	$linha				= $_POST['linha'];

	if($login_fabrica == 90){	
		$classificacao = $_POST["classificacao"];
		if(!empty($classificacao)){
			$condCl = " AND tbl_hd_chamado.hd_classificacao = $classificacao ";
		}
	}

	if(in_array($login_fabrica, array(101,169,170))){
		$origem = $_POST["origem"];
	}

	if(count($linha) > 0) {
		$condJoinLinha = " IN (";
		for($i = 0; $i < count($linha); $i++){
			if($i == count($linha)-1 ){
				$condJoinLinha .= $linha[$i].")";
			}else {
				$condJoinLinha .= $linha[$i].", ";
			}
		}
		$join_linha = " JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto"; 
		$cond_6 = " AND tbl_produto.linha {$condJoinLinha} ";
	}
	$linhas = implode(',',$linha);
	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	$cond_5 = " 1 = 1 ";

	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro = "Data Inválida";
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){

		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);

	}else{

		$msg_erro = "Data Inválida";

	}

	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");
	}

	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");
	}

	if($xdata_inicial > $xdata_final)
		$msg_erro = traduz("Data Inválida");

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$produto = pg_fetch_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}

	if (strlen($natureza_chamado) > 0) {
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}elseif($login_fabrica == 85 and strlen(trim($natureza_chamado))==0){
		$cond_2 = " tbl_hd_chamado.categoria <> 'garantia_estendida' ";
	}

	if(strlen($status)>0){
		if($login_fabrica == 74 AND $status == "nao_resolvido"){
			$cond_3 = " lower(tbl_hd_chamado.status) <> 'resolvido'  ";
		}else{
			$cond_3 = " tbl_hd_chamado.status = '$status'  ";
		}
	}

	//HD 795654 - Comentado pois nao batia com o outro relatorio
	/*if ($status == 'Resolvido') {
		$cond_status = " AND (SELECT data FROM tbl_hd_chamado_item WHERE status_item = 'Resolvido' AND hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'";
	} else if($status == 'Cancelado') {
		$cond_status = " AND (SELECT data FROM tbl_hd_chamado_item WHERE status_item = 'Cancelado' AND hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'";
	} else if($status == 'Aberto') {
		$cond_status = " AND (SELECT data FROM tbl_hd_chamado_item WHERE status_item = 'Aberto' AND hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
	}*/

	$cond_data = " AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";

	if (in_array($login_fabrica,array(6,24))) {
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if (count($atendente)>0 AND $atendente[array_search("", $atendente)] != "") {

		$atendentes = implode(",",$atendente); // HD 310601
		if (!empty($atendentes)) $cond_5 = " tbl_hd_chamado.atendente IN ( $atendentes ) ";

	}

	if ($login_fabrica == 2) {
		$condicoes = $produto . ";" . $natureza_chamado . ";" . $status . ";" . $posto . ";" . $xdata_inicial . ";" .$xdata_final;
	}

    if($login_fabrica == 101 and strlen(trim($origem))>0){
        $cond_origem = "and tbl_hd_chamado_extra.origem = '$origem' ";
    }else if(in_array($login_fabrica, array(169,170)) AND strlen(trim($origem)) > 0){
    	$cond_origem = "AND tbl_hd_chamado_extra.hd_chamado_origem = $origem ";
    }

}

$layout_menu = "callcenter";
$title       = traduz("RELATÓRIO PERÍODO DE ATENDIMENTO");

include "cabecalho_new.php"; ?>


<script type="text/javascript">

	function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,atendente,origem,linhas, classificacao) {

		if (typeof atendente == 'undefined') {
			atendente = '';
		}

		var url = "callcenter_relatorio_periodo_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&atendente="+atendente+"&origem="+origem+"&linhas="+linhas+"&classificacao="+classificacao;

		if (navigator.userAgent.match(/Chrome/gi)) {
			url = unescape(encodeURIComponent(url));
		}

		janela = window.open(url, "Callcenter",'scrollbars=yes,width=950,height=450,top=315,left=0');

		janela.focus();

	}

	/* POP-UP IMPRIMIR */
	function abrir(URL) {
		var width = 700;
		var height = 600;
		var left = 90;
		var top = 90;

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');

	}

</script>

<?
$plugins = array(
	"mask",
	"datepicker",
	"select2"
);

include "plugin_loader.php";

?>
<script type="text/javascript" charset="utf-8">
	$(function() {
		$("select").select2();
        $("#data_inicial").datepicker().mask("99/99/9999");
        $("#data_final").datepicker().mask("99/99/9999");
    });
</script>

<script type="text/javascript" src="../ajax.js"></script>

<?
include "javascript_pesquisas.php";
?>
<script type="text/javascript" src="js/highcharts_4.0.3.js"></script>
	<? if(strlen($msg_erro)>0){ ?>
		<div class="alert alert-danger">
			<? echo $msg_erro; ?>
		</div>
	<? } ?>
<FORM name="frm_relatorio" class="form-search form-inline tc_formulario" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
	<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for=''><?=traduz('Data Inicial')?></label>
					<div class='controls controls-row'>
						<input class="span7" type="text" name="data_inicial" id="data_inicial" size="14" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
					</div>
				</div>
			</div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for=''><?=traduz('Data Final')?></label>
					<div class='controls controls-row'>
						<input class="span7" type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class="control-group input-append">
					<label class="control-label" for=''><?=traduz('Ref. Produto')?></label>
						<div class='controls controls-row'>
							<input class="span12" type="text" name="produto_referencia" size="14" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" ><span class='add-on' rel="lupa" src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')"><i class='icon-search' ></i></span>
						</div>
				</div>
			</div>
			<div class="span4">
				<div class="control-group input-append">
					<label class="control-label" for=''><?=traduz('Descrição')?></label>
					<div class='controls controls-row'>
						<input class="span12" type="text" name="produto_descricao" style='width: 180px' class='frm' value="<? echo $produto_descricao ?>" ><span class='add-on' rel="lupa" src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')"><i class='icon-search' ></i></span>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
		<div class="row-fluid">
		<div class="span2"></div>
			<div class="span4">
				<div class="control-group input-append">
					<label class="control-label" for=''><?=traduz('Natureza')?></label>
					<div class='controls controls-row'>
						<select name='natureza_chamado' class='frm' style='width: 180px'>
							<option value=''></option><?php
								//HD39566
								$sqlx = "SELECT nome            ,
												descricao
										FROM tbl_natureza
										WHERE fabrica = $login_fabrica
										AND ativo = 't'
										ORDER BY nome";

								$resx = pg_query($con, $sqlx);

								for ($y = 0; pg_num_rows($resx) > $y; $y++) {

									$nome      = trim(pg_fetch_result($resx, $y, 'nome'));
									$descricao = trim(pg_fetch_result($resx, $y, 'descricao'));

									echo $nome;
									echo "<option value='$nome'";
									if ($natureza_chamado == $nome) echo "selected";
									echo ">$descricao</option>";
								}?>
						</select>
					</div>
				</div>
			</div>
			<?php 
			if (!$telecontrol_distrib || $login_fabrica <> 189) { ?>
			<div class="span4">
				<div class="control-group input-append">
					<label class="control-label" for=''><?=traduz('Status')?></label>
					<div class='controls controls-row'>
						<select name="status" class='controls' style='width: 180px'>
							<option value=""></option><?php
							if($login_fabrica == 74){
								$selected = ($status == "nao_resolvido") ? "selected" : "";
								echo "<option value='nao_resolvido' $selected>".traduz('Não resolvido')."</option>";
							}
								$sql = "select distinct status from tbl_hd_status where fabrica = $login_fabrica order by status";
								$res = pg_query($con,$sql);
								for($x=0;pg_num_rows($res)>$x;$x++){
									$xstatus = pg_fetch_result($res,$x,status);
									echo "<option value='$xstatus'"; if ($xstatus == $status) echo "selected";echo" >$xstatus</option>";

								} ?>
						</select>

						 <acronym class='ac' title="<?=traduz('As datas referentes as pesquisas são: Aberto: Data de Abertura do Chamado. Resolvido: Data em que o Chamado foi modificado para Resolvido. Cancelado: Data em que o Chamado foi modificado para Cancelado.')?>"><img src='imagens/help.png'></acronym>
					</div>
				</div>
			</div>
			<?php
			} ?>
		<div class="span2"></div>
		</div>
		<?php if($login_fabrica == 90){?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<label class="control-label" for=''><?=traduz('Classificação')?></label>
				<div class='controls controls-row'>
				<select name="classificacao" class='controls' style='width: 180px'>
					<option value=""></option>
					<?php 
						$sqlCl = "SELECT hd_classificacao, descricao from tbl_hd_classificacao where fabrica = $login_fabrica AND ativo is true ORDER BY descricao";
						$resCL = pg_query($con, $sqlCl);
						for($a = 0; $a<pg_num_rows($resCL); $a++){
							$hd_classificacao 	= pg_fetch_result($resCL, $a, hd_classificacao);
							$descricao 			= pg_fetch_result($resCL, $a, descricao);

							if($hd_classificacao == $classificacao){
								$selected = " selected ";
							}else{
								$selected = " ";
							}
						
							echo "<option value='$hd_classificacao' $selected >$descricao</option>";

						}

					?>
				</select>
				</div>
			<div class="span2"></div>
			</div>	
		</div>
		<?php } ?>

	<?php if($login_fabrica == 101){?>
		<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group input-append">
				<label class="control-label" for=''><?=traduz('Origem ')?></label>
				<div class='controls controls-row'>
					<select name="origem" id="xorigem" style="width:180px">
		                <option value=''>Escolha</option>
		                <option value='Telefone' <?PHP if ($origem == 'Telefone') { echo "Selected";}?>>Telefone</option>
		                <option value='Email' <?PHP if ($origem == 'Email') { echo "Selected";}?>>E-mail</option>
		                 <?php if( $login_fabrica == 101 ){?>
		                    <option value='ecommerce' <?PHP if ($origem == 'ecommerce') { echo "Selected";}?>>E-Commerce </option>
		                <?php } ?>
		                <option value='whatsapp' <?PHP if ($origem == 'whatsapp'){ echo "Selected";}?>>WhatsApp</option>
		                <option value='facebook' <?PHP if ($origem == 'facebook') { echo "Selected";}?>>Facebook</option>
		                <option value='reclame_aqui' <?PHP if ($origem == 'reclame_aqui') { echo "Selected";}?>>Reclame Aqui </option>
		                <option value='procon' <?PHP if ($origem == 'procon') { echo "Selected";}?>>Procon </option>
		                <option value='jec' <?PHP if ($origem == 'jec') { echo "Selected";}?>>JEC </option>
		            </select>
	            </div>
            </div>
        </div>
        <div class="span2"></div>
        </div>
        <br />
	<?php } ?>
	<?php if($login_fabrica == 11){?>
		<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
				<div class='control-group'>
					<label class='control-label' for='linha'><?=traduz('Linha')?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<?
							$sql_linha = "SELECT
												linha,
												nome
										  FROM tbl_linha
										  WHERE tbl_linha.fabrica = $login_fabrica
										  ORDER BY tbl_linha.nome ";
							$res_linha = pg_query($con, $sql_linha); ?>
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
										} ?>

										<option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >

											<?php echo $key['nome']?>

										</option>
							  <?php } ?>
								</select>

						</div>
					</div>
				</div>
			</div>
			 <div class="span2"></div>
        </div>

	<?php } ?>
	<!-- HD 234177: Acrescentar busca por atendente -->
	<!-- HD 310601: Acrescentar busca multipla por atendente -->
	<?php

			if($login_fabrica == 74){

                $tipo = "producao"; // teste - producao

                $admin_fale_conosco = ($tipo == "producao") ? 6409 : 6437;

                $cond_admin_fale_conosco = " AND tbl_admin.admin NOT IN ($admin_fale_conosco) ";

            }

		     $sql = "SELECT admin, login
						FROM tbl_admin
						WHERE
							fabrica = $login_fabrica
							AND ativo IS TRUE
							AND (privilegios LIKE '%call_center%' OR privilegios like '*')
							$cond_admin_fale_conosco
						ORDER BY login";

				$res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {

                    $total = pg_num_rows($res);
                    $total = round((($total * 20) / 3)+15);

                    if ($total > 120) $total = 120;

                    $style = "style='height: {$total}px'";

                }?>
        <?php if(in_array($login_fabrica, array(169,170))){ ?>
    		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='atendente'><?=traduz('Atendente')?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<select name="atendente[]" id="atendente" multiple>
									<option value=""></option>
									<?php
									$sql = "SELECT admin, nome_completo
											FROM tbl_admin
											WHERE fabrica = $login_fabrica
											AND (callcenter_supervisor IS TRUE OR atendente_callcenter IS TRUE)
											AND ativo";
									$res = pg_query($con,$sql);
									foreach (pg_fetch_all($res) as $key) {
										if(isset($atendente)){
											foreach ($atendente as $id) {
												if ( isset($atendente) && ($id == $key['admin']) ){
													$selected_atendente[] = $id;
												}
											}
										}
									?>
										<option value="<?php echo $key['admin']?>" <?php if( in_array($key['admin'], $selected_atendente)) echo "SELECTED"; ?> >
											<?php echo $key['nome_completo']?>
										</option>
									<?php
									}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("origem", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='origem'><?=traduz('Origem')?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<select name="origem" id="origem">
									<option value=""></option>
									<?php

										$sql = "SELECT hd_chamado_origem, descricao FROM tbl_hd_chamado_origem WHERE fabrica = $login_fabrica and ativo IS TRUE order by descricao";
										$res = pg_query($con,$sql);
										foreach (pg_fetch_all($res) as $key) {
											$selected_origem = ( isset($origem) and ($origem == $key['hd_chamado_origem']) ) ? "SELECTED" : '' ;
										?>
											<option value="<?php echo $key['hd_chamado_origem']?>" <?php echo $selected_origem ?> >
												<?php echo $key['descricao']?>
											</option>
										<?php
										}
									?>
								</select>
							</div>
							<div class='span2'></div>
						</div>
					</div>
				</div>
			</div>
		<?php }else{ ?>
			<div class="row-fluid">
				<br />
	            <div class="panel well well-lg" style="width: 90%; margin: 0 auto;">
	            	<h4 align="center"><?=traduz('Atendentes')?></h4>
					<div class="row-fluid">
						<div class='span12'>
					        <?php
					            if(count($atendente) > 0)
					                $checked = ($atendente[array_search("", $atendente)] == "") ? "checked": "" ;
					            ?>
							            <div class="row-fluid" style="min-height: 40px !important;">
							            	<div class="span2"></div>
						            		<div class="span8" style="text-align: center;">
							            		<label class="checkbox" for=''>
									            <?
									            if (!$telecontrol_distrib) {
									           	 	echo "<input type='checkbox' value='' name='atendente[]' {$checked} />".traduz('Toda a Equipe')."";
									           	}
									           	?>
									           	 </label>
								           	 </div>
									        <div class="span2"></div>
								        </div>

					           	 <?

							    $res = pg_query($con,$sql);
					            if (pg_num_rows($res)>0) {
					            	$i = 0;

					                while($dado = pg_fetch_array($res)){
					                	$i++;
					                    if(count($atendente)>0)
					                        $checked = ($atendente[array_search($dado[0], $atendente)] == $dado[0]) ? "checked": "" ;
					                    if ($i % 3 == 0) { ?>
					                    	<div class="row-fluid" style="min-height: 40px !important;">
					                    <? } ?>

					                    	<div class='span4'>
					                    		<label class="checkbox" for=''>
					                    		<?
					                    		echo "<input type='checkbox' value='{$dado[0]}' name='atendente[]' {$checked} />{$dado[1]}";
					                    		?>
					                    		</label>
					                    	</div>
					                    <?
					                	if ($i % 3 == 0) { ?>
					                    		</div>
					                    <? }
					                }

					            }
					        ?>
					    </div>
					</div>
				</div>
			</div>
		<?php } ?>
		<div class="row">
			<div class="span5"></div>
			<div class="span2">
				<br />
				<input type='submit' class='btn' style="cursor:pointer" name='btn_acao' value="<?=traduz('Pesquisar')?>">
			</div>
			<div class="span5"></div>
		</div>
		<br />
</FORM>
<br /><?php

	if (strlen($btn_acao) > 0 and strlen($msg_erro) == 0) {

		$sql = " SELECT extract( 'days' from data_interacao ::timestamp - data_abertura ::timestamp) as periodo ,
						count(*) as qtde
				FROM (
						SELECT	tbl_hd_chamado.hd_chamado                ,
								tbl_hd_chamado.status                    ,
								tbl_hd_chamado.data as data_abertura     ,
							(	SELECT tbl_hd_chamado_item.data
								FROM tbl_hd_chamado_item
								WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
								and tbl_hd_chamado_item.interno is not true
		        AND tbl_hd_chamado.status is not null
								ORDER BY data desc LIMIT 1
						) AS data_interacao
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				$join_linha
				WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
				AND $cond_1
				AND $cond_2
				AND $cond_3
				AND $cond_4
				AND $cond_5
				$condCl
				$cond_6
				$cond_origem
				) AS X
				where X.data_interacao NOTNULL
				GROUP BY periodo order by periodo; ";

		if($login_fabrica == 35 AND $status == "Ag. Consumidor"){
			$campo = " (SELECT count(*)
						FROM fn_calendario((tbl_hd_chamado.data::date + 1),CURRENT_DATE)
						WHERE nome_dia not in ('Domingo','Sábado')) AS periodo ";

		} elseif($login_fabrica == 90 ){
			$campo = " CASE
						WHEN tbl_hd_chamado.status = 'Resolvido' 
						    
						    THEN (SELECT tbl_hd_chamado_item.data
								FROM tbl_hd_chamado_item
								WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
								and tbl_hd_chamado_item.interno is not true
		        AND tbl_hd_chamado.status is not null
								ORDER BY data desc LIMIT 1)::date - tbl_hd_chamado.data::date

						ELSE current_date - tbl_hd_chamado.data::date
						END AS periodo  ";
			
		}else {
			$campo = "  CASE
							WHEN dias_aberto IS NULL THEN 0
							ELSE dias_aberto
						END AS periodo ";
		}

		if($login_fabrica == 74){
	        $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
	    }

	    //echo nl2br($sql);

		$sql = " SELECT COUNT(tbl_hd_chamado.hd_chamado) AS qtde,
						$campo
						FROM tbl_hd_chamado_extra
						JOIN tbl_hd_chamado using(hd_chamado)
						$join_linha
						WHERE fabrica_responsavel =  $login_fabrica
						$cond_data
						AND $cond_1
						AND $cond_2
						AND $cond_3
						AND $cond_4
						AND $cond_5 
						$cond_6
						$condCl
						AND tbl_hd_chamado.posto is null 
						$cond_origem
						$cond_admin_fale_conosco
		                AND tbl_hd_chamado.status is not null
						GROUP BY periodo
						ORDER BY periodo ";

		$res = pg_query($con,$sql);

		if(pg_num_rows($res)>0){ ?>
			<thead>
			<table class='table table-striped table-bordered table-fixed'>
				<TR class="titulo_coluna">
					<th class='subtitulo' colspan=2><?=traduz('Clique no intervalo de chamado para detalhar os chamados')?></th>
				</TR >
				<TR class='titulo_coluna'>
					<th align='left'><?=traduz('Intervalo de Chamado')?></th>
					<th align='right'><?=traduz('Qtde')?></th>

				</TR >
			</thead>
			<?
			$soma_qtde = 0;
			$soma_qtde2 = 0;
			$total_qtde = 0;
			$total_qtde_at = 0;

			foreach (pg_fetch_all($res) as $key => $value) {
				$total_qtde_at += $value['qtde'];
			}

			for($y=0;pg_num_rows($res)>$y;$y++){
				$periodo = pg_fetch_result($res,$y,periodo);
				$qtde   = pg_fetch_result($res,$y,qtde);
				$xperiodo =$periodo;
			//	if(strlen($periodo)==0){$periodo = "Sem Interação";}
				if($periodo==0){$periodo = "Mesmo dia";}
				if($periodo==1){$periodo = "1 dia";}
				if($periodo>1){$periodo .= " dias";}

// 				$grafico_periodo[] = $periodo;
// 				$grafico_qtde[] = $qtde;
                $grafico_titulo[] = utf8_encode($periodo);
                $coluna_porcentagem = 0;
                $coluna_porcentagem = ($qtde*100)/$total_qtde_at; 
                $coluna_porcentagem_dados[] = (float)number_format($coluna_porcentagem, 2);

				if (in_array($login_fabrica, [35, 90])){

					$soma_qtde += $qtde;
					$soma_qtde2 += ($periodo * $qtde);


				}
				$total_qtde += $qtde;
				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
				echo "<TR bgcolor='$cor'>\n";
					echo "<TD align='left' nowrap><a href=\"javascript: AbreCallcenter('$xdata_inicial','$xdata_final','$produto','$natureza_chamado','$status','$xperiodo','$atendentes','$origem','$linhas', '$classificacao')\">$periodo</a></TD>\n";
					echo "<TD class='tac' nowrap>$qtde</TD>\n";
				echo "</TR >\n";

			}

            $height_grafico         = (count($grafico_titulo) / 3);
            $height_grafico         = (ceil($height_grafico) * 200);
            $highcharts_descricao   = json_encode($grafico_titulo);
            $highcharts_qtde        = json_encode($coluna_porcentagem_dados);  

//             $apresenta_grafico = json_encode($grafico_dados);

				if ($login_fabrica == 35 or ( $login_fabrica ==  90 and $status == 'Resolvido')){
					if($login_fabrica == 35){
						$msgTMA = "Tempo médio de dias para atendimento dos atendimentos";
					}else{
						$msgTMA = "Tempo Médio de Atendimento(dias) ";
					}
				?>
					<tr >
						<td align="left"><?=$msgTMA?></td>
						<?
						$media = ($soma_qtde2) / ($soma_qtde);
						?>
						<td align="center"><center><? echo number_format($media, 2, ',', '.'); ?></center></td>
					</tr>
				<?}?>

					<tr class="titulo_coluna">
						<th align="left">Total de Atendimentos</th>
						<th align="right"><? echo number_format($total_qtde, 0, ',', '.'); ?></th>
					</tr>

			<?

			echo "</table>";
?>
            <br>
<script type="text/javascript">
$(function () {
    $('#grafico').highcharts({
        chart: {
            type: 'bar',
            height: <?=$height_grafico?>,
            width:650
        },
        credits: {
            enabled: false
        },
        title: {
            text: '<?=traduz('Relatório de Atendimentos')?>'
        },
        subtitle:{
             text:'<?=traduz('Período:')?>'' <?=$data_inicial?> - <?=$data_final?>'
        },
        xAxis: {
            categories: <?=$highcharts_descricao?>
        },
        yAxis:[{
            min: 0,
            title: {
                enabled: true,
                text: '<?=traduz('Nº de Atendimentos')?>',
                style: {
                    fontWeight: 'normal'
                }
            },
        },{
            title: {
                enabled: true,
                text: '<?=traduz('Nº de Atendimentos')?>',
                style: {
                    fontWeight: 'normal'
                }
            },
            opposite:true
        }],
        legend: {
            enabled:false
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px;width:150px">{point.key}</span><table style="width:150px;">',
            pointFormat: '<tr><td style="padding:0" nowrap><b>{point.y} %<?=traduz(' Atendimento(s)')?></b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        series: [{
            data: <?=$highcharts_qtde?>
        }],
        plotOptions: {
            series: {
                dataLabels: {
                    enabled: false,
                    formatter: function(){
                        var correto = parseFloat(this.point.x);
                        var formato = Highcharts.numberFormat(correto,2,',','.');
                        return formato+' OS';
                    }
                }
            }
        },
    });
});
</script>
<div class="container">
            <div id="grafico" style="height: <?=$height_grafico?>px; width: 655px; margin: 0 auto;"></div>
</div>
<?
		if($login_fabrica==2){//hd 36906 3/10/2008
			$title = traduz("RELATORIO PERIODO DE ATENDIMENTO");
			echo "<BR><BR>";
			echo "<A HREF=\"javascript:abrir('impressao_callcenter.php?condicoes=$condicoes;$title')\">";
			echo "<IMG SRC=\"imagens/btn_imprimir_azul.gif\" BORDER='0' ALT=''>";
			echo "</A>";
		}
		}

		else{
			echo "<center>".traduz('Nenhum Resultado Encontrado')."</center>";
		}
	}
?>

<p>

<? include "rodape.php" ?>
