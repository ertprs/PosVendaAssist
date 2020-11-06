<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';
$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
$layout_menu = "tecnica";
$title = "TREINAMENTOS REALIZADOS";

include "cabecalho_new.php";
include "../plugins/fileuploader/TdocsMirror.php";

$plugins = array(
    "shadowbox"
);

include "plugin_loader.php";
include "javascript_pesquisas.php";

$treinamento = $_GET['treinamento'];

$sql = "SELECT tbl_treinamento.treinamento                                        ,
			tbl_treinamento.titulo                                                ,
			tbl_treinamento.descricao                                             ,
			tbl_treinamento.ativo                                                 ,
			tbl_treinamento.vagas                                                 ,
			tbl_treinamento.linha                                                 ,
			tbl_treinamento.familia                                               ,
			TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio      ,
			TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim         ,
			tbl_treinamento.data_fim::date  - tbl_treinamento.data_inicio::date  AS dias,
			tbl_treinamento.adicional,
			tbl_treinamento.local,
			tbl_treinamento.cidade,
			tbl_treinamento.palestrante,
			tbl_treinamento.treinamento_tipo,
			tbl_treinamento.data_finalizado,
			tipo_posto,
			TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY')    AS prazo_inscricao,
			tbl_treinamento.visivel_portal
	FROM tbl_treinamento
	WHERE tbl_treinamento.treinamento = $treinamento
	AND tbl_treinamento.fabrica = $login_fabrica";
	
$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0) {
	$treinamento  = trim(pg_result($res,0,treinamento));
	$titulo       = trim(utf8_encode(pg_result($res,0,titulo)));
	$descricao    = trim(utf8_encode(pg_result($res,0,descricao)));
	$ativo        = trim(pg_result($res,0,ativo))      ;
	$data_inicial = trim(pg_result($res,0,data_inicio));
	$data_final   = trim(pg_result($res,0,data_fim))   ;
	$linha        = trim(pg_result($res,0,linha))      ;
	$familia      = trim(pg_result($res,0,familia))    ;
	$qtde         = trim(pg_result($res,0,vagas))      ;
	$adicional    = trim(utf8_encode(pg_result($res,0,adicional)));
	$local    = trim(utf8_encode(pg_result($res,0,local)));
	$cidade    = trim(pg_result($res,0,cidade))      ;
	$visivel_portal    = trim(pg_result($res,0,visivel_portal));
	$palestrante    = trim(pg_result($res,0,palestrante));
	$data_finalizado    = trim(pg_result($res,0,data_finalizado));
	if(in_array($login_fabrica, array(169,170,193))){
		$tipo_posto = trim(pg_result($res,0,tipo_posto));
		$prazo_inscricao = trim(pg_result($res,0,prazo_inscricao));
		$treinamento_tipo = trim(pg_result($res,0,treinamento_tipo));

		if ($data_inicial == $data_final){
			$dias = 1;
		}else{
			$dias = trim(pg_result($res,0,dias))+1;
		}
	}else{
		$dias = trim(pg_result($res,0,dias))+1;
	}

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

	$sql = "SELECT ti.instrutor_treinamento FROM tbl_treinamento_instrutor ti JOIN tbl_promotor_treinamento pt ON ti.instrutor_treinamento = pt.promotor_treinamento WHERE ti.treinamento = $treinamento";
	$res_instrutor = pg_query($con, $sql);
	$res_instrutor = pg_fetch_all($res_instrutor);
	$res_instrutor = $res_instrutor[0];


	$sql = "SELECT tp.treinamento_promotor, tp.promotor_treinamento, pt.nome, pt.email FROM tbl_treinamento_promotor tp JOIN tbl_promotor_treinamento pt ON tp.promotor_treinamento = pt.promotor_treinamento WHERE tp.treinamento = $treinamento";
	$res_promotores = pg_query($con, $sql);
	$res_promotores = pg_fetch_all($res_promotores);


	$sql = "SELECT tc.treinamento_cidade, c.cidade, c.nome, c.estado FROM tbl_treinamento_cidade tc JOIN tbl_cidade c ON tc.cidade = c.cidade WHERE tc.treinamento = $treinamento";
	$res_cidade = pg_query($con, $sql);
	$res_cidade = pg_fetch_all($res_cidade);


	if (in_array($login_fabrica, array(169,170,193)))
	{
		$select_dia_participou  = " tbl_treinamento_posto.dia_participou, ";
		$where_tecnico          = "AND tbl_tecnico.tecnico IS NOT NULL";
		$tipo_tecnico           = " , tbl_tecnico.tipo_tecnico, tbl_tecnico.tecnico";
		$select_data_finalizado = " , tbl_treinamento.data_finalizado";
		$join_treinamento       = " INNER JOIN tbl_treinamento ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento ";
		$campo_aplicado         = ", tbl_treinamento_posto.aplicado";
	}

	$sql = "SELECT  tbl_treinamento_posto.treinamento_posto,
					tbl_tecnico.nome     AS tecnico_nome,
					tbl_tecnico.rg       AS tecnico_rg,
					tbl_tecnico.cpf      AS tecnico_cpf,
					tbl_tecnico.email    AS tecnico_email,
					tbl_tecnico.telefone AS tecnico_fone,
					tbl_treinamento_posto.ativo,
					tbl_treinamento_posto.hotel,
					tbl_treinamento_posto.participou,
					tbl_treinamento_posto.confirma_inscricao,
					tbl_treinamento_posto.promotor,
					tbl_treinamento_posto.motivo_cancelamento AS motivo,
					{$select_dia_participou}
					TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS data_inscricao,
					TO_CHAR(tbl_treinamento_posto.data_inscricao,'HH24:MI:SS') AS hora_inscricao,
					tbl_posto.nome                                             AS posto_nome,
					tbl_posto.estado,
					tbl_posto_fabrica.codigo_posto,
					tbl_promotor_treinamento.nome,
					tbl_treinamento_posto.observacao    AS observacao_antigo,
					tbl_treinamento_posto.tecnico_nome  AS tecnico_nome_antigo,
					tbl_treinamento_posto.tecnico_rg    AS tecnico_rg_antigo,
					tbl_treinamento_posto.tecnico_cpf   AS tecnico_cpf_antigo,
					tbl_treinamento_posto.tecnico_email AS tecnico_email_antigo,
					tbl_treinamento_posto.tecnico_fone  AS tecnico_fone_antigo,
					tbl_treinamento_posto.nota_tecnico	AS nota_tecnico,
					tbl_treinamento_posto.aprovado
					{$campo_aplicado}
					{$tipo_tecnico}
					{$select_data_finalizado}
			   FROM tbl_treinamento_posto
		  LEFT JOIN tbl_promotor_treinamento USING(promotor_treinamento)
		  LEFT JOIN tbl_posto USING(posto)
		  LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto       = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		  LEFT JOIN tbl_admin         ON tbl_treinamento_posto.admin   = tbl_admin.admin
		  LEFT JOIN tbl_tecnico       ON tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
		  {$join_treinamento}
			  WHERE tbl_treinamento_posto.treinamento = $treinamento
				AND tbl_treinamento_posto.ativo IS TRUE
				{$where_tecnico}
		   ORDER BY tbl_posto.nome" ;
	$res_tecnicos = pg_query($con,$sql);

}
?>
<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
<FORM name="frm_relatorio" id="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">

	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>
	<div id="erro" class='alert alert-error' style="display:none;"><h4></h4></div>
	<div class="titulo_tabela">Finaliza Treinamento</div>

	<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span2">
				<div class="control-group" id="data_inicial_campo">
					<label class='control-label'>Data Inicial</label>
					<input type="text" name="data_inicial" id='data_inicial'  maxlength="10" class='span12' value="<?=$data_inicial?>" readonly>
				</div>
			</div>
			<div class="span2">
				<div class="control-group" id="data_final_campo">
					<label class='control-label'>Data Final</label>
					<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" readonly>
				</div>
			</div>
			<div class="span4">
				<div class="control-group" id="data_final_campo">
					<label class='control-label'>Prazo Final de Inscrições</label>
					<input type="text" name="prazo_inscricao" id="prazo_inscricao" size="12" maxlength="10" class='span12' value="<?=$prazo_inscricao?>" readonly>
				</div>
			</div>

	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span8">
			<div class="control-group" id="tema">
				<label class='control-label'>Tema</label>
				<div class='controls controls-row'>
					<input type="text" name="titulo" id='titulo' size="60" maxlength="70" class='span12' value="<? if (strlen($titulo) > 0) echo utf8_decode($titulo); ?>" readonly>
				</div>
			</div>
		</div>
		<div class="span2"></div>
   	</div>

   	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span4">
			<div class="control-group" id="linha_campo">
				<label class='control-label'>Linha</label>
				<h5 class='asteristico'>*</h5>
				<div class="<? echo $controlgrup?>">
						<?

						$sql = "SELECT  *
								FROM    tbl_linha
								WHERE   tbl_linha.fabrica = $login_fabrica
								ORDER BY tbl_linha.nome;";
						$res = pg_exec ($con,$sql);

						if (pg_numrows($res) > 0) {
							echo "<select name='linha' id='linha' class='span12' readonly>\n";
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
		</div>
		<div class="span4">
			<div class="<? echo $controlgrup?>">
				<?
				$sql = "SELECT * FROM tbl_treinamento_tipo WHERE fabrica = {$login_fabrica} ORDER BY nome";
				$res = pg_query($con,$sql);
				if (pg_numrows($res) > 0) {
					?>
					<div class="control-group" id="linha_campo">
						<label class="control-label">Tipo de Treinamento</label>
						<div class="">
							<select name="treinamento_tipo" id="treinamento_tipo" class="span12" readonly>
								<?php
								while ($tipo = pg_fetch_array($res)){
									if($treinamento_tipo != $tipo['treinamento_tipo']){
										echo "<option value={$tipo['treinamento_tipo']}>{$tipo['nome']}</option>\n";
									}else{
										echo "<option value={$tipo['treinamento_tipo']} selected='selected' >{$tipo['nome']}</option>\n";
										$treinamentoTipoNome = $tipo['nome'];
									}
								}
								?>
							</select>
						</div>
					</div>
				<?php
				}?>
			</div>
		</div>
		<div class='span2'></div>
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>

			<div class="span4">
				<div class="control-group" id="tipo_posto">
					<h5 class='asteristico'>*</h5>
					<label class='control-label'>Tipo de Posto</label>
					<select name='tipo_posto' id='tipo_posto' class='span12' readonly>
						<option>Selecione um tipo de posto</option>
						<?php
						$sql = "SELECT tipo_posto, descricao FROM tbl_tipo_posto WHERE fabrica = ".$login_fabrica;
						$res = pg_query($con,$sql);
						if (pg_numrows($res) > 0) {
							while($tipo_posto_result = pg_fetch_array($res)){
								?>
								<option <?=$tipo_posto == $tipo_posto_result['tipo_posto']?"selected": ""?> value="<?=$tipo_posto_result['tipo_posto']?>" ><?=$tipo_posto_result['descricao']?></option>
								<?php
							}
						}
						?>
					</select>
				</div>
			</div>


		<div class="span4">
			<div class="control-group" id="vagas">
				<h5 class='asteristico'>*</h5>
				<label class='control-label'>Vagas</label>
				<input type="text" id="qtde" name="qtde" size="10" maxlength="3" class='span12' value="<? if (strlen($qtde) > 0) echo $qtde; ?>" readonly>
			</div>
		</div>
	</div>


		<div class="row-fluid">
		<div class="span2"></div>
			<div class="span8">
					<div class="control-group" id="descricao_estado">
						<input type="hidden" id="estado_treinamento" value='<?=$estado_cidade?>'>
						<label class='control-label'>Instrutor</label>
						<h5 class="asteristico">*</h5>
						<select id="instrutor" name="instrutor" class="span12 " readonly>
							<option>Selecione o Instrutor</option>
							<?php
							// adicionar where tipo = INSTRUTOR!
							$sql = "SELECT promotor_treinamento, nome, email FROM tbl_promotor_treinamento where fabrica = ".$login_fabrica." AND tipo = '2'";

							$res = pg_query($con, $sql);
							while ($promotor = pg_fetch_array($res)){
								?>
								<option <?=$res_instrutor['instrutor_treinamento'] == $promotor['promotor_treinamento']? "selected": ""?> value="<?=$promotor['promotor_treinamento']?>"><?=$promotor['nome']." - ".$promotor['email']?></option>
								<?php
							}
								?>
						</select>
					</div>
				</div>
		</div>


	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span8">
		<label class='control-label'>Informações Adicionais</label>
			<input type="text" name="adicional"  maxlength="200" class='frm span12' value="<? echo utf8_decode($adicional); ?>" readonly>
			<DIV ID="display_hint" class='span9'>Digite aqui as informações adicionais que o posto deve fornecer ao inscrever um treinando. Ex: Revenda</DIV>
		</div>
		<div class='span2'></div>
	</div>
	<br>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span8">
			<div class="control-group" id="descricao_campo">
				<h5 class='asteristico'>*</h5>
				<label class='control-label'>Descrição</label>
				<TEXTAREA NAME='descricao' ROWS='7' id="descricao" COLS='60' class='frm span12' readonly><?if (strlen($descricao) > 0) echo utf8_decode($descricao); ?></TEXTAREA>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span8">
			<label class='control-label'>Local</label>
			<input type="text" name="local" id="local" value="<?php echo utf8_decode($local) ?>" class='span12' readonly>
		</div>
		<div class='span2'></div>
	</div>



	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group" id="descricao_estado">
				<input type="hidden" id="estado_treinamento" value='<?=$estado_cidade?>'>
				<label class='control-label'>Estado</label>
				<h5 class="asteristico">*</h5>
				<select id="listaEstado" name="listaEstado" class="span12 " readonly>
					<?php if(strlen($estado_cidade) > 0){ ?>
						<option value="<?=$estado_cidade?>"><?=$estado_nome?></option>
					<?php }else{?>
						<option value="">Selecione</option>
					<?php } ?>
				</select>
			</div>
		</div>
		<div class="span4">
			<div class="control-group" id="descricao_cidade">
				<input type="hidden" id="cidade_treinamento" value='<?=$nome_cidade?>'>
				<label class='control-label'>Cidade</label>
				<h5 class="asteristico">*</h5>
				<select id="cidade" name="cidade" class="span12 " readonly>
					<?php if(strlen($nome_cidade) > 0){ ?>
						<option value="<?=$cidade?>"><?=$nome_cidade?></option>
					<?php }else{?>
						<option value="">Selecione</option>
					<?php } ?>
				</select>
			</div>
		</div>
	</div>
			<!-- Combo de campos Estado e Cidade, permitindo adicionar várias cidades -->
	<hr>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<table id='tbl_cidades_participantes' class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class='titulo_tabela'><th colspan='9'> Cidades Participantes</th></tr>
				<tr class='titulo_coluna'>
				<th>Cidade</th>
				<th>Estado</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($res_cidade as $value) {
					?>
					<tr>
						<td><?=$value['nome']?></td>
						<td class="tac"><?=$value['estado']?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
			</table>
		</div>
	</div>

	<hr>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<table id='tbl_promotores_participantes' class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class='titulo_tabela'><th colspan='9'> Promotores Responsáveis</th></tr>
				<tr class='titulo_coluna'>
				<th>Nome</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($res_promotores as $value) {
					?>
					<tr>
						<td class="td-promotor tac" data-promotorId="<?=$value['treinamento_promotor']?>"><?=$value['nome']." - ".$value['email']?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
			</table>
		</div>
	</div>
	<?php
	if($treinamentoTipoNome == 'Palestra'){
		?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class="control-group" id="vagas">
					<h5 class='asteristico'>*</h5>
					<label class='control-label'>Quantidade de pessoas participantes</label>
					<input type="text" id="qtde_participantes" name="qtde_participantes" size="10" maxlength="3" class='span12 tac' value="<? if (strlen($qtde_participantes) > 0) echo $qtde_participantes; ?>">
				</div>
			</div>
		</div>
		<?php
	}
	?>
</FORM>
</div>

<div class="container-fluid">
<div class="row-fluid">
	<?php
	if($treinamentoTipoNome != 'Palestra'){
	?>
	<table id='tecnicos_participantes' border='0' cellpadding='0' cellspacing='0' class='table table-striped table-fixed'  align='center' width='700px'>
		<thead>
			<tr class="titulo_tabela">
				<th colspan="11">Técnicos Participantes e Avaliações</th>
			</tr>
			<tr class='titulo_coluna'  height='25'>
				<th>Posto</th>
				<th width='25'>UF</th>
				<th>Informações do T&eacute;cnico</th>
				<th >Data</th>
				<th width='60' >Inscri&ccedil;&atilde;o</th>
				<th width='60' >Presente</th>
				<?php if(in_array($login_fabrica, array(169,170,193))){ ?>
				<th width='60' >Dias Participados</th>	
				<?php } ?>
				<?php if (in_array($login_fabrica, array(169,170,193))){ ?>
					<th>Realizou Prova <input type="checkbox" id="marca_todos_prova" name="marca_todos_prova" /> </th>
				<?php }?>
				<th width='60' colspan>Nota</th>
				<th width='60' colspan>Aprovado</th>
				<?php if (in_array($login_fabrica, array(169,170,193))){ ?>
					<th>Gerar Certificados</th>
				<?php }else{ ?>
					<th>Anexar Certificados</th>
				<?php } ?>				
			</tr>
		</thead>
		<tbody>
			<?php
			while ($value = pg_fetch_array($res_tecnicos)) {
			?>
				<tr data-treinamento-posto='<?=$value['treinamento_posto']?>' class="Conteudo" id="inscricao_<?=$value['treinamento_posto']?>" bgcolor="#F1F4FA">
					<td align="left"><?=$value['codigo_posto']." - ".$value['posto_nome']?></td>
					<td align="center" nowrap=""><?=$value['estado']?></td>
					<td align="left" nowrap="">
					<b>Nome: </b><?=$value['tecnico_nome']?> <br>
					<b>CPF:</b> <?=$value['tecnico_cpf']?><br>
					<b>Fone:</b> <?=$value['tecnico_fone']?><br>
					</td>
					<td align="center" class="tac">
						<?=$value['data_inscricao']?> <br> <?=$value['hora_inscricao']?>
					</td>
					<td align="center" class="tac">
						<?=$value['ativo']? '<img src="imagens_admin/status_verde.gif"> Sim': '<img src="imagens_admin/status_vermelho.gif"> Não'?>
					</td>
					<td align="center">
						<?php 
							if (in_array($login_fabrica, array(169,170,193))){ ?>
								<div id='presente_div' class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
									<h5 class="asteristico">*</h5>
									<select name="presente" class="span12 select-presente" >
										<?php 
											if (empty($value['data_finalizado'])){ ?>
												<option <?=$value['participou'] == 'f'? "SELECTED":"" ?>></option>
												<option <?=$value['participou'] == 't'? "SELECTED":""?> value="SIM">Sim</option>
												<option value="NAO">Não</option>
									<?php   }else{ ?>
												<option></option>
												<option <?=$value['participou'] == 't'? "SELECTED":""?> value="SIM">Sim</option>
												<option <?=$value['participou'] == 'f'? "SELECTED":""?> value="NAO">Não</option>
									<?php   } ?>
									</select>
								</div>
						<?php }else { ?>
							<div id='presente_div' class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
								<h5 class="asteristico">*</h5>
								<select name="presente" class="span12 select-presente" >
									<option></option>
									<option <?=$value['participou'] == 't'? "SELECTED":""?> value="SIM">Sim</option>
									<option <?=$value['participou'] == 'f'? "SELECTED":""?> value="NAO">Não</option>
								</select>
							</div>
						<?php } ?>
					</td>
					<?php if (in_array($login_fabrica, array(169,170,193))) { ?>
					<td  align="center">
						<label id='dias_treinamento'><b><?=$dias;?></b> dia(s) de Treinamento</label>
						<div id='dia_participou_div' class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
							<h5 class="asteristico">*</h5>
							<input style="width: 50px" type="text" name="dia_participou" class="input-dias" value="<?=$value['dia_participou']; ?>">
						</div>
					</td>
					<?php } ?>
					<?php if (in_array($login_fabrica, array(169,170,193))){ ?>
					<td align="center">
						<div id='realizou_prova_div' class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
							<input style="width: 50px" type="checkbox" name="realizou_prova" class="realizou_prova" value="t" <?=$value['aplicado'] == 't'? "CHECKED":"" ?>>
						</div>
					</td>
					<?php }?>
					<td align="center">
						<div id='nota_div' class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
							<h5 class="asteristico">*</h5>
							<input style="width: 50px" type="text" name="nota" data-treinamento-posto="7964" class="input-nota" value="<?=$value['nota_tecnico']?>">
						</div>
					</td>
					<td align="center">
						<div id='aprovado_div' class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<h5 class="asteristico">*</h5>
							<select name="aprovado" class="span12 select-aprovado" >
								<option></option>
								<option <?=$value['aprovado'] == 't'? "SELECTED":""?> value="SIM">Sim</option>
								<option <?=$value['aprovado'] == 'f'? "SELECTED":""?> value="NAO">Nao</option>
							</select>
							<input type="hidden" name="aprovado" class='select-aprovado-read' value='' />
						</div>
					</td>
					<td class="tac">
						<?php 
							if (in_array($login_fabrica, array(169,170,193))){ 
								if (($value['tipo_tecnico'] == 'TF' && in_array($login_fabrica, [169,170])) || in_array($login_fabrica, [193])){ 
										// obtendo certificado do técnico
										$tecnico      = $value['tecnico'];
										$sql_tdocs    = "SELECT tdocs_id
													FROM tbl_tdocs 
														WHERE fabrica = {$login_fabrica}
													AND contexto      = 'gera_certificado'
													AND referencia    = 'gera_certificado'
													AND referencia_id = {$tecnico}
													AND json_field('treinamento',obs) = '{$treinamento}'";

										$res_tdocs    = pg_query($con,$sql_tdocs);

										if (pg_numrows($res_tdocs) > 0){
											$unique_id = pg_fetch_result($res_tdocs, 0, tdocs_id);
											
											if(strlen($unique_id) > 0) {
												$tdocsMirror      = new TdocsMirror();
												$resposta         = $tdocsMirror->get($unique_id);
												$link_certificado = $resposta["link"];
		                   		?>		                   			
												<a target="_blank" href='<?=$link_certificado?>' style='cursor: pointer; text-align: center;'>Acessar Certificado</a>
											<?php }else { ?>
												<a class='gera_certificado_convidado' data-treinamento='<?=$treinamento?>' data-treinamento-posto='<?=$value['treinamento_posto']?>' style='cursor: pointer; text-align: center;'>Emitir Certificado</a>
											<?php }
									  }else{ ?>
		                   					<a class='gera_certificado_convidado' data-treinamento='<?=$treinamento?>' data-treinamento-posto='<?=$value['treinamento_posto']?>' style='cursor: pointer; text-align: center;'>Emitir Certificado</a>
								<?php	}									
								}
							}else {  ?>
								<button type="button" class="btn btn-small btn-primary btn-attach-cert" data-treinamento-posto='<?=$value['treinamento_posto']?>'  rel="2"><i class="icon-picture icon-white"></i></button>
					<?php } ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>

		</table>
		
		<?php
		}
		?>

		<div class="row-fluid" style="background: #d9e2ef">
			<div class="titulo_tabela">Imagens e Arquivos do Treinamento</div>
			<div class="span12 env-tdocs-uploads">
			</div>
		</div>
		<?php
		if($data_finalizado == NULL){
		?>
		<div class="row-fluid" style="background: #d9e2ef">
			<div class="span2"></div>
			<div class="span8 tac">
                <button type="button" class="btn btn-primary" id="btn-call-fileuploader" rel="2"><i class="icon-picture icon-white"></i> Anexar Arquivos</button>
                <button type="button" class="btn btn-primary" id="btn-end-training" rel="2"><i class="icon-ok icon-white"></i> Finalizar Treinamento</button>
			</div>
		</div>
		<?php
		}
		?>

		<hr>
		<div class='row-fluid'>
			<div class='span12' style='text-align: center;'>
				<a style="margin-bottom: 10px; text-align: center;" href="treinamento_cadastro.php" class="btn btn-default">Voltar para listagem</a>
			</div>
		</div>
</div>
</div>
<div id="modal-view-image" class="modal hide fade">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Visualizar Imagem</h3>
  </div>
  <div class="modal-body tac">
    <p>
    	<img id="img-tag" src="" class="img-rounded">
    </p>
  </div>
  <div class="modal-footer">
    <a href="#" class="btn">Fechar</a>
  </div>
</div>

<script type="text/javascript">
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

	function loadImage(uniqueId, callback){
        $.ajax("plugins/fileuploader/fileuploader-iframe.php?loadTDocs="+uniqueId).done(callback);
    }

	$(function(){
		Shadowbox.init();

		$(".btn-attach-cert").click(function(){
			var treinamento_posto = $(this).data("treinamento-posto");

			var tdocs_uploader_cert = "plugins/fileuploader/fileuploader-iframe.php?context=treinamento_posto&reference_id="+treinamento_posto+"&no_hash=true";
			Shadowbox.open({
             content: tdocs_uploader_cert,
             player: "iframe",
             height: 600,
             width: 950,
             options: {
                 onClose: updateEnvTdocs
             }
	        });
		});

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

		$("#btn-end-training").click(function(){
			if(confirm("Deseja finalizar esse treinamento?")){
				var acao = 'finalizar_treinamento';
				var qtde_participantes= "";

				if($("#qtde_participantes").length == 1){
					qtde_participantes = $("#qtde_participantes").val();

					if(qtde_participantes ==""){
						alert("Informe a quantidade de participantes");

						$("#qtde_participantes").focus();

						return false;
					}
				}
				
				<?php if (in_array($login_fabrica, array(169,170,193))){ ?>
					msg_erro = false;

					$('#tecnicos_participantes tbody tr').each(function() {
						var a = $(this).find(".realizou_prova");
						if (a.is(':checked')){
							$(this).find(".input-nota").attr('readonly', false);
							$(this).find(".select-aprovado").attr('disabled', false);
						}else{
							$(this).find(".input-nota").attr('readonly', true);
							$(this).find(".select-aprovado").attr('disabled', true);		
						}

						if($(this).find(".select-presente").val() == undefined || $(this).find(".select-presente").val() == ''){
							msg_erro = true;
							$(this).find("#presente_div").addClass('error');
						}

						if($(this).find(".input-nota").val() == undefined || $(this).find(".input-nota").val() == '' && $(this).find(".select-presente").val() == 'SIM'){
							if ($(this).find(".realizou_prova").is(':checked')) {
								$(this).find("#nota_div").addClass('error');
								msg_erro = true;
							}
						}

						if($(this).find(".input-dias").val() == undefined || $(this).find(".input-dias").val() == '' && $(this).find(".select-presente").val() == 'SIM'){
							msg_erro = true;
							$(this).find("#dia_participou_div").addClass('error');
						}

						if($(this).find(".select-aprovado").val() == undefined || $(this).find(".select-aprovado").val() == '' && $(this).find(".select-presente").val() == 'SIM'){
							if ($(this).find(".realizou_prova").is(':checked')) {
								$(this).find("#aprovado_div").addClass('error');
								msg_erro = true;
							}
							
						}
					});							

					if(msg_erro == true){
						$("#erro").show().find('h4').html("Preencha os campos obrigatórios.");
						return;
					}else
					{
						$("#erro").hide();
					}
				<?php } ?>

				var url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento=<?=$treinamento?>&qtde_participantes="+qtde_participantes;
				$.ajax(url).done(function(response){
					response = JSON.parse(response)							;
					if(response.error != undefined){
						alert(response.error);
					}else{
						alert("Finalizado");
						$("#btn-end-training").fadeOut(1000);
						$("#btn-call-fileuploader").fadeOut(1000);
					}
				});
			}
		});

		<?php if (in_array($login_fabrica, [169,170,193])){ ?>
		
			$(".realizou_prova").click(function(){
				var tr                = $(this).parents("tr")[0];
				var treinamento_posto = $(tr).data("treinamento-posto");
				var acao              = 'atualiza_realizou_prova';
				var input             = this;
				var realizou          = '';

				if ($(this).is(':checked')) {
					var realizou = 't';
				}else{
					var realizou = 'f';
				}

				var url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento_posto+"&realizou_prova="+realizou;
				$.ajax(url).done(function(response){
					$(input).addClass("input-border");
					setTimeout(function(){
						$(input).removeClass("input-border");
					},1500);
				});
				checkProva();
			});

			$("#marca_todos_prova").click(function(){
				if ($(this).is(':checked')) {
					$('.prova_texto').html('Deselecionar Todos');
				}else{
					$('.prova_texto').html('Selecionar Todos');
				}
				$('.realizou_prova').not(this).prop('checked', this.checked);
				checkProva();
			});

			checkProva();
		<?php } ?>
	});

	function checkProva(){
		$('tr').each(function() {
			var a = $(this).find(".realizou_prova");
			if (a.is(':checked')){
				$(this).find(".input-nota").attr('readonly', false);
				$(this).find(".select-aprovado").attr('disabled', false);	
			}else{
				$(this).find(".input-nota").attr('readonly', true);
				$(this).find(".select-aprovado").attr('disabled', true);		
			}
		});		
	}

	/* Função para gerar certificado */
	$(document).on('click', '.gera_certificado_convidado', function() {
		$(this).html('<span style="text-align: center;">Gerando <i class="fas fa-circle-notch fa-spin"></i></span>');
  		var treinamento       = $(this).data("treinamento");
  		var treinamento_posto = $(this).data("treinamento-posto");
  		var td                = $(this).parents("td")[0];

     	$.ajax("../gera_certificado.php",{
          method: "POST",
          data: {
            treinamento: treinamento,
            treinamento_posto: treinamento_posto,
            isConvidado: true,
            returnLinkText: true
          }
        }).done(function(response){
        	response = JSON.parse(response);
        	if (response.ok !== undefined) {
        		alert('Certificado enviado para o e-mail cadastrado..');
        		$(td).html("<a target='_blank' href='"+response.ok+"' style='text-align: center;'>Acessar Certificado</a>");
        	}else{
        		alert(response.error);
        		$(td).html("<a class='gera_certificado_convidado' data-treinamento='"+treinamento+"' data-treinamento-posto='"+treinamento_posto+"' style='cursor: pointer; text-align: center;'>Emitir Certificado</a>");
        	}
        });
  	});

	$(".input-nota").change(function(){
		var tr = $(this).parents("tr")[0];
		var treinamento_posto = $(tr).data("treinamento-posto");
		var nota = $(this).val();
		var acao = 'atualiza_nota_tecnico';
		var input = this;

		var url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento_posto+"&nota="+nota;
		$.ajax(url).done(function(response){
			$(input).addClass("input-border");
			setTimeout(function(){
				$(input).removeClass("input-border");
			},1500);
		});
	});

	$(".input-dias").change(function(){
		var tr = $(this).parents("tr")[0];
		var treinamento_posto = $(tr).data("treinamento-posto");
		var dias = $(this).val();
		var acao = 'atualiza_dia_participou';
		var input = this;

		var url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento_posto+"&dia="+dias;
		$.ajax(url).done(function(response){
			$(input).addClass("input-border");
			setTimeout(function(){
				$(input).removeClass("input-border");
			},1500);
		});
	});

	$(".select-presente").change(function(){
		var tr = $(this).parents("tr")[0];
		var treinamento_posto = $(tr).data("treinamento-posto");
		var presente = $(this).val();
		var acao = 'ativa_desativa_participou';
		var select = this;

		/* Desativando e definindo valor padrão, para o resto dos campos caso o técnico/convidado não participou do treinamento */
		<?php if (in_array($login_fabrica, array(169,170,193))){ ?>
			if (presente == 'NAO'){
				$(tr).find(".input-dias").val("0");
				$(tr).find(".input-dias").attr('readonly', true);

				$(tr).find(".input-nota").attr('readonly', true);
				
				$(tr).find(".select-aprovado").val("NAO");
				$(tr).find(".select-aprovado-read").val("NAO");
				$(tr).find(".select-aprovado").attr('disabled', true);
			}else if (presente == 'SIM'){
				$(tr).find(".input-dias").val("");
				$(tr).find(".input-dias").attr('readonly', false);

				var a = $(tr).find(".realizou_prova");
				if (a.is(':checked')){
					$(tr).find(".input-nota").val("");
					$(tr).find(".input-nota").attr('readonly', false);
					
					$(tr).find(".select-aprovado").val("");
					$(tr).find(".select-aprovado-read").val("NAO");
					$(tr).find(".select-aprovado").attr('disabled', false);	
				}else{
					$(tr).find(".input-nota").attr('readonly', true);
					
					$(tr).find(".select-aprovado").val("NAO");
					$(tr).find(".select-aprovado-read").val("NAO");
					$(tr).find(".select-aprovado").attr('disabled', true);	
				}
			}
		<?php 	} ?>	

		var url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento_posto+"&participou="+presente;
		$.ajax(url).done(function(response){
			$(select).addClass("input-border");
			setTimeout(function(){
				$(select).removeClass("input-border");
			},1500);
		});
	});

	$(".select-aprovado").change(function(){
		var tr = $(this).parents("tr")[0];
		var treinamento_posto = $(tr).data("treinamento-posto");
		var aprovado = $(this).val();
		var acao = 'atualiza_aprova_reprova';
		var select = this;

		var url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento_posto+"&aprovado="+aprovado;
		$.ajax(url).done(function(response){
			$(select).addClass("input-border");
			setTimeout(function(){
				$(select).removeClass("input-border");
			},1500);
		});
		$(tr).find(".select-aprovado-read").val($(this).val());
	});

	setTimeout(function(){
		updateEnvTdocs();
	},1000)

</script>

<style type="text/css">
	.input-border{
		border: 2px solid #31ea11 !important;
	}

	.env-tdocs-uploads > p{
		text-align: center;
    	font-size: 29px;
	}

	.env-tdocs-uploads{
		padding-top: 10px;
	}

	.env-img {
	    float: left;
	    width: 25%;
	    margin-bottom: 17px;
	    min-height: 132px;
	    max-height: 132px;
    	overflow: hidden;
    	text-align: center;
    	cursor: pointer;
    	transition: all ease-in-out .3s;
	}

	.env-img:hover{
		transform: scale(1.2);
		transition: all ease-in-out .3s;
	}

	.env-img > .img-rounded{
		width: 60%;
	}

	.env-img > span {
		float: left;
	}
</style>

<?php include "rodape.php"; ?>
