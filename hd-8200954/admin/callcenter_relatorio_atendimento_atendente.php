<?
//CHAMADO:		134895
//PROGRAMADOR:	EBANO LOPES
//SOLICITANTE:	11 - LENOXX

/**
 * Corrigido problema na query, que filtrava chamados do Help-desk e do callcenter sem nenhuma diferença.
 * Não contar interações da abertura de chamado.
 * Não contar interações de mudança de status.
 * HD 155210 - Augusto Pascutti (2009-09-23)
 */

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include '../helpdesk.inc.php';
#$login_fabrica = 151;
$layout_menu = "callcenter";
if(in_array($login_fabrica, array(169,170))){
	$title = traduz("Relatório DE ATENDIMENTO X INTERAÇÕES");
}else{
	$title = traduz("RELATÓRIO DE ATENDIMENTOS POR ATENDENTE");
}




if($login_fabrica == 85){
	/*VERIFICA O ADMIN SUPERVISOR DO CALLCENTER*/
	$sql = "SELECT callcenter_supervisor, privilegios from tbl_admin where fabrica = $login_fabrica and admin = $login_admin";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$callcenter_supervisor = pg_result($res,0,0);
		$privilegios = pg_result($res,0,1);
	}
	if ($callcenter_supervisor != "t" AND $privilegios != "*") {
		$supervisor=" AND tbl_hd_chamado.atendente = $login_admin AND tbl_hd_chamado.admin = $login_admin ";
	}
}

$btn_acao = $_POST['btn_acao'];

if ($_POST["btn_acao"] == "submit") {
	$origem = $_POST['origem'];
	$xdata_inicial	= implode("-", array_reverse(explode("/", $_POST["data_inicial"]))) . " 00:00:00";
	$xdata_final	= implode("-", array_reverse(explode("/", $_POST["data_final"]))) . " 23:59:59";

	$tipo_protocolo = $_POST['tipo_protocolo'];
    //VALIDANDO AS DATASe

    $sql = "SELECT '$xdata_inicial'::timestamp, '$xdata_final'::timestamp";
    @$res = pg_query($sql);
    if (!$res)
    {
		$msg_erro = traduz("Preencha os campos obrigatórios");
		$btn_acao = "";
	}
	if($xdata_inicial > $xdata_final)
		$msg_erro = traduz("Preencha os campos obrigatórios");


	if ($_POST["gerar_excel"]) {
		if(strlen(trim($origem)) > 0){
			$cond_origem = "AND hce.hd_chamado_origem = {$origem}";
		}else{
			$cond_origem = "";
		}

		if(strlen(trim($tipo_protocolo)) > 0){
			$cond_tipo_protocolo = " AND hc.hd_tipo_chamado = {$tipo_protocolo} ";
		}else{
			$cond_tipo_protocolo = "";
		}

		$sql_csv = "SELECT
						ahc.nome_completo AS admin_abriu_chamado,
						hc.hd_chamado,
						TO_CHAR(hc.data, 'DD/MM/YYYY HH24:MI') AS data_abertura_chamado,
						hco.descricao AS origem,
						hml.descricao AS providencia,
						ahci.nome_completo AS admin_interacao,
						hci.status_item AS status_interacao,
						TO_CHAR(hci.data, 'DD/MM/YYYY HH24:MI') AS data_interacao
					FROM tbl_hd_chamado hc
					JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado
					JOIN tbl_hd_chamado_item hci ON hci.hd_chamado = hc.hd_chamado
					JOIN tbl_admin ahci ON ahci.admin = hci.admin AND ahci.fabrica = {$login_fabrica}
					JOIN tbl_admin ahc ON ahc.admin = hc.admin AND ahc.fabrica = {$login_fabrica}
					JOIN tbl_hd_status hs ON hs.status = hci.status_item AND hs.fabrica = {$login_fabrica}
					JOIN tbl_hd_motivo_ligacao hml ON hml.hd_motivo_ligacao = hci.hd_motivo_ligacao
						AND hml.fabrica = {$login_fabrica}
					JOIN tbl_hd_chamado_origem hco ON hco.hd_chamado_origem = hce.hd_chamado_origem AND hco.fabrica = {$login_fabrica}
					WHERE hc.fabrica_responsavel = {$login_fabrica}
					AND hci.data BETWEEN '$xdata_inicial' AND '$xdata_final'
					AND hci.admin IS NOT NULL
					AND hci.status_item IS NOT NULL
					$cond_origem
					$cond_tipo_protocolo
					ORDER BY ahci.nome_completo, hci.data";
		$res_csv = pg_query($con, $sql_csv);

		$result = pg_fetch_all($res_csv);

		$data = date("d-m-Y-H:i");
		$fileName = "relatorio_atendimento_interacoes-{$data}.csv";
		$file = fopen("/tmp/{$fileName}", "w");

		$titulo = array('Nome atendente','Número chamado','Data abertura',
            'Origem','Providência','Atendente interação','Status interação','Data interação'
        );

		fwrite($file, $titulo);
    	$linhas = implode(";", $titulo)."\r\n";

    	foreach ($result as $key => $value) {
            $linhas .= implode(";", $value)."\r\n";
        }

        fwrite($file, $linhas);
    	fclose($file);

    	if (file_exists("/tmp/{$fileName}")) {
			system("mv /tmp/{$fileName} xls/{$fileName}");

			echo "xls/{$fileName}";
		}
		exit;
	}
}

include "cabecalho_new.php";
$plugins = array(
	"datepicker",
	"mask"
);

include("plugin_loader.php");
?>

<!-- ******************************** JAVASCRIPT ******************************** -->

<script type="text/javascript" charset="utf-8">
	$(function(){
		$("#data_inicial").datepicker().mask("99/99/9999");
		$("#data_final").datepicker().mask("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>


<!-- ******************************** FIM JAVASCRIPT ******************************** -->

	<? if(strlen($msg_erro)>0){ ?>
		<div class='alert alert-danger'><h4><? echo $msg_erro; ?></h4></div>
	<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>
<FORM class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
	<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
	<br />
		<div class="row-fluid">
			<div class="span3"></div>
				<div class='span4'>
					<div class='control-group <?= (strlen($msg_erro) > 0) ? 'error' : '' ?>'>
						<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
							<div class='controls controls-row'>
								<div class='span4'>
									<h5 class='asteristico'>*</h5>
									<input class="span12" type="text" id="data_inicial" name="data_inicial" size="12" maxlength="10" value="<?=$data_inicial?>">
								</div>
							</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?= (strlen($msg_erro) > 0) ? 'error' : '' ?>'>
						<label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
							<div class='controls controls-row'>
								<div class='span4'>
									<h5 class='asteristico'>*</h5>
									<input class="span12" type="text" id="data_final" name="data_final" size="12" maxlength="10" value="<?=$data_final?>">
								</div>
							</div>
					</div>
				</div>
			<div class="span1"></div>
		</div>
		<?php if(in_array($login_fabrica, array(169,170))){ ?>
		<div class='row-fluid'>
			<div class="span2"></div>
		    <div class='span4'>
		        <div class='control-group '>
		            <label class='control-label' for='xorigin'><?=traduz('Origem')?></label>
		            <div class='controls controls-row'>
		                <div class='span4'>
		                    <select name="origem">
		                        <option value=""></option>
		                        <?php
		                            $sql = "SELECT hd_chamado_origem,descricao
		                                        FROM tbl_hd_chamado_origem
		                                        WHERE fabrica = $login_fabrica
		                                        ORDER BY descricao";
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
		            </div>
		        </div>
		    </div>
		    <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='tipo_protocolo'>Tipo Protocolo</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="tipo_protocolo" id="tipo_protocolo">
                            <option value=""></option>
                            <?php

                                $sql = "SELECT hd_tipo_chamado, descricao FROM tbl_hd_tipo_chamado WHERE fabrica = {$login_fabrica} AND ativo ORDER BY descricao";
                                $res = pg_exec($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {

                                    $key['hd_tipo_chamado'] = ($key['hd_tipo_chamado']);
                                    $key['descricao'] = ($key['descricao']);

                                    $selected_tipo = ( isset($tipo_protocolo) and ($tipo_protocolo== $key['hd_tipo_chamado']) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['hd_tipo_chamado']?>" <?php echo $selected_tipo ?> >
                                        <?php echo $key['descricao']; ?>
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
		    <div class='span2'></div>
		</div>
		<?php }?>
		<br />
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
		<br /><br />
</FORM>
</div>
<?

if ($_POST["btn_acao"] == "submit" and strlen($msg_erro)==0) {

	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0){

		if($login_fabrica == 74){
			$tipo = "teste"; // teste - producao
			$admin_fale_conosco = ($tipo == "producao") ? 6409 : 6437;
			$cond_admin_fale_conosco = " AND tbl_admin.admin NOT IN ($admin_fale_conosco) ";
		}

		if($novaTelaOs){
			$sql = "
				SELECT
					a.admin,
					a.login AS nome_usuario,
					a.nome_completo,
					COUNT(*) AS interacoes
					FROM tbl_hd_chamado hc
					INNER JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado
					INNER JOIN tbl_hd_chamado_item hci ON hci.hd_chamado = hc.hd_chamado
					INNER JOIN tbl_admin a ON a.admin = hci.admin AND a.fabrica = $login_fabrica
					INNER JOIN tbl_hd_status hs ON hs.status = hci.status_item AND hs.fabrica = $login_fabrica
					WHERE hc.fabrica_responsavel = $login_fabrica
					AND hci.data BETWEEN '$xdata_inicial' AND '$xdata_final'
					AND hci.admin IS NOT NULL
					AND hci.status_item IS NOT NULL
					GROUP BY a.admin, a.login, a.nome_completo
					ORDER BY a.nome_completo";
		}else{
			$sql = "
				SELECT
					tbl_admin.admin,
					tbl_admin.login AS nome_usuario,
					tbl_admin.nome_completo,
					COUNT(hd_chamado_item) AS interacoes

				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra USING (hd_chamado)
				LEFT JOIN tbl_hd_chamado_item USING (hd_chamado)
				JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin AND tbl_admin.fabrica = $login_fabrica

				WHERE	1=1
				$supervisor
				AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica 
				AND tbl_hd_chamado.titulo <> 'Help-Desk Posto' 
				AND tbl_hd_chamado.fabrica = $login_fabrica
				AND (tbl_hd_chamado.data BETWEEN '$xdata_inicial' AND '$xdata_final' OR tbl_hd_chamado_item.data  BETWEEN '$xdata_inicial' AND '$xdata_final' )
				$cond_admin_fale_conosco
				GROUP BY tbl_admin.admin, tbl_admin.login, tbl_admin.nome_completo
				ORDER BY tbl_admin.nome_completo
				";
		}
		#echo nl2br($sql);
		$res = pg_exec($con, $sql);

		//HD 409490
		$sqlS = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica ";
		$resS = pg_query($con,$sqlS);

		$aStatusInteracoes = array();

		for ($i = 0; $i < pg_num_rows($resS); $i++){
			$aStatusInteracoes[] = pg_result($resS,$i,0);
		}

		if(pg_num_rows($res) > 0){
?>
			<br><div class='container-fluid'>
			<table class='table table-striped table-bordered table-fixed'>
			<tr class="titulo_coluna">
				<th width="80px" rowspan="2"><?=traduz('Login')?></th>
				<th width="175px" rowspan="2"><?=traduz('Nome Completo')?></th>
				<th width="90px" rowspan="2" title="<?=traduz('Total de chamados abertos neste período')?>"><?=traduz('Chamados*')?></th>
				<th width="90px" title="<?=traduz('Total de interações feitas pelo atendente neste período. Independente da data de abertua do chamado')?>" colspan="<?php echo count($aStatusInteracoes)+1; ?>"><?=traduz('Interações*')?></th>
			</tr>
			<tr class="titulo_coluna">
				<?php foreach($aStatusInteracoes as $sStatus): ?>
					<th> <?php echo $sStatus; ?> </th>
				<?php endforeach; ?>
				<th><?=traduz('Total')?></th>
			</tr>
<?php
			for($i = 0; $i < pg_num_rows($res); $i++){
				$total_geral = "";
				$xadmin			= pg_result($res, $i, 'admin');
				$nome_usuario	= pg_result($res, $i, 'nome_usuario');
				$nome_completo	= pg_result($res, $i, 'nome_completo');
				$interacoes		= pg_result($res, $i, 'interacoes');

				if(in_array($login_fabrica, array(169,170))){

					$campos = ",tbl_hd_motivo_ligacao.descricao";
					$join_hd_motivo_ligacao = "JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_item.hd_motivo_ligacao
												AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica";
					$join_hd_chamado_extra = "JOIN tbl_hd_chamado_extra USING (hd_chamado)";
					$group = "GROUP BY tbl_hd_motivo_ligacao.descricao";

					if(strlen(trim($origem)) > 0){
						$cond_origem = "AND tbl_hd_chamado_extra.hd_chamado_origem = {$origem}";
					}else{
						$cond_origem = "";
					}

					if(strlen(trim($tipo_protocolo)) > 0){
						$cond_tipo_protocolo = " AND tbl_hd_chamado.hd_tipo_chamado = {$tipo_protocolo} ";
					}else{
						$cond_tipo_protocolo = "";
					}

				}else{
					$campos = "";
					$join_hd_motivo_ligacao = "";
					$join_hd_chamado_extra = "";
					$group = "";
					$cond_origem = "";
					$cond_tipo_protocolo = "";
				}

				$sql = "SELECT COUNT(tbl_hd_chamado.hd_chamado) AS chamados
						FROM tbl_hd_chamado
						$join_hd_chamado_extra
						WHERE 1=1
						AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND tbl_hd_chamado.data between '$xdata_inicial' and '$xdata_final'
						AND tbl_hd_chamado.admin = $xadmin 
						$cond_origem
						$cond_tipo_protocolo";
				$res_chamados 	= pg_query($con, $sql);
				$chamados	 	= pg_result($res_chamados, 0, 0);
				$aInteracoes    = array();

				foreach ($aStatusInteracoes as $xstatus) {
					$sql = "SELECT COUNT(tbl_hd_chamado_item.hd_chamado_item) as interacoes $campos
							FROM tbl_hd_chamado
							JOIN tbl_hd_chamado_extra USING (hd_chamado)
							JOIN tbl_hd_chamado_item USING (hd_chamado)
							$join_hd_motivo_ligacao
							WHERE 1=1 
							AND tbl_hd_chamado.titulo <> 'Help-Desk Posto' 	
							AND tbl_hd_chamado_item.data between '$xdata_inicial' and '$xdata_final'
							AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
							AND fn_retira_especiais(tbl_hd_chamado_item.status_item) = fn_retira_especiais('$xstatus')
							AND tbl_hd_chamado_item.admin = {$xadmin} $cond_origem $group";

					$res2= pg_query($con,$sql);

					if(in_array($login_fabrica, array(169,170))){
						$aInteracoesx[$xstatus] = pg_fetch_all($res2);
					}else{
						$aInteracoes[$xstatus] = 0;
						if ( is_resource($res2) && pg_num_rows($res2) > 0 ) {
							$aInteracoes[$xstatus] = pg_result($res2,0,0);
						}
					}
				}

				$linha_css 		   = "linha" . $i % 2;
				// Lenoxx HD 155210 - Augusto
				$interacoes_total = $interacoes;
				if ( $login_fabrica == 11 or $login_fabrica == 172) {
					// Nao exibir a interacao de abertura do chamado como interacao valida
					$interacoes_total = $interacoes_aberto;
				}
				$xtotal = 0;
?>
				<tr class="<?php echo $linha_css; ?>">
					<td><?php echo $nome_usuario; ?></td>
					<td ><?php echo $nome_completo; ?></td>
					<td class="tac"><?php echo $chamados; ?></td>

					<?php if(in_array($login_fabrica, array(169,170))){
						$padding = "style='padding-top: 2%'";
						$contador_abertox = 0;

						$contador_resolvidox = 0;

						$contador_cancelado = 0;
						$contador_canceladox = 0;

						foreach ($aInteracoesx as $key_interacoes => $value_interacoes) {
							echo "<td>";
							###ABERTO###
							if($key_interacoes == "Aberto"){
								unset($count);
								unset($xtotal);
								if(is_array($value_interacoes)){
									$count = count($value_interacoes);
								}else{
									$count = 0;
								}
								if($count > 0){
									echo "<table class'table-bordered' align='center' width='100%'>";
											while($contador_abertox < $count){
												$inte = $value_interacoes[$contador_abertox]['interacoes'];
												$inte = intval($inte);
												$xtotal += $inte;
												echo "<tr>";
												echo"	<td class='titulo_coluna' style='background-color:#596D9B' >".$value_interacoes[$contador_abertox]['descricao']."</td>";
												echo"	<td class='tac' style='background-color:#ffffff'>".$value_interacoes[$contador_abertox]['interacoes']."</td>";
												echo "</tr>";
												$contador_abertox++;
											}
											$total_geral +=$xtotal;
											echo "<tr>
													<td class='titulo_coluna tac' style='background-color:#596D9B' >Total</td>
													<td class='tac' style='background-color:#ffffff'>$xtotal</td>
												</tr>";
									echo "</table>";
								}
							}
							###CANCELADO###
							if($key_interacoes == "Cancelado"){
								unset($count);
								unset($xtotal);
								if(is_array($value_interacoes)){
									$count = count($value_interacoes);
								}else{
									$count = 0;
								}
								if($count > 0){
									echo "<table class'table-bordered' align='center' width='100%'>";
										while($contador_canceladox < $count){
											$inte = $value_interacoes[$contador_canceladox]['interacoes'];
											$inte = intval($inte);
											$xtotal += $inte;
											echo "<tr>";
											echo"	<td class='titulo_coluna' style='background-color:#596D9B' >".$value_interacoes[$contador_canceladox]['descricao']."</td>";
											echo"	<td class='tac' style='background-color:#ffffff'>".$value_interacoes[$contador_canceladox]['interacoes']."</td>";
											echo "</tr>";
											$contador_canceladox++;
										}
										$total_geral +=$xtotal;
										echo "<tr>
												<td class='titulo_coluna tac' style='background-color:#596D9B' >Total</td>
												<td class='tac' style='background-color:#ffffff'>$xtotal</td>
											</tr>";
									echo "</table>";
								}
							}

							###RESOLVIDO###
							if($key_interacoes == "Resolvido"){
								unset($count);
								unset($xtotal);
								if(is_array($value_interacoes)){
									$count = count($value_interacoes);
								}else{
									$count = 0;
								}
								if($count > 0){
									echo "<table class'table-bordered' align='center' width='100%'>";
										while($contador_resolvidox < $count){
											$inte = $value_interacoes[$contador_resolvidox]['interacoes'];
											$inte = intval($inte);
											$xtotal += $inte;
											echo "<tr>";
											echo"	<td class='titulo_coluna' style='background-color:#596D9B' >".$value_interacoes[$contador_resolvidox]['descricao']."</td>";
											echo"	<td class='tac' style='background-color:#ffffff'>".$value_interacoes[$contador_resolvidox]['interacoes']."</td>";
											echo "</tr>";
											$contador_resolvidox++;
										}
										$total_geral +=$xtotal;
										echo "<tr>
												<td class='titulo_coluna tac' style='background-color:#596D9B' >Total</td>
												<td class='tac' style='background-color:#ffffff'>$xtotal</td>
											</tr>";
									echo "</table>";
								}
							}
							echo "</td>";
						}
					?>

					<?php }else{?>
						<?php foreach ($aInteracoes as $xinteracao): ?>
							<?php $xtotal += $xinteracao; ?>
							<td class="tac"><?php echo ($xinteracao>0)?$xinteracao:'&nbsp;'; ?></td>
						<?php endforeach; ?>
					<?php } ?>
					<td class="tac" <?=$padding?>>
						<?php
							if(in_array($login_fabrica, array(169,170))){
								echo $total_geral;
							}else{
								echo $xtotal;
							}
						?>
					</td>
				</tr>

<?php
			}
		echo "</table>";

		if(in_array($login_fabrica, array(169,170))){
			$jsonPOST = excelPostToJson($_POST);
			echo "<div id='gerar_excel' class='btn_excel'>
				<input type='hidden' id='jsonPOST' value='$jsonPOST' />
				<span><img src='imagens/excel.png' /></span>
				<span class='txt'>Gerar Arquivo Excel</span>
			</div>";
		}

		}else{
			echo "<div class='alert alert-warning container'><h4>".traduz('Não foram encontrados resultados para esta pesquisa!')."</h4></div>";
		}
	}
}

?>
<? include "rodape.php" ?>
