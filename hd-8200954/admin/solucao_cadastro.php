<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao	= (trim($_REQUEST["btn_acao"]));
$solucao	= (trim($_REQUEST["solucao"]));

if (strlen($solucao) > 0) {

	if ($login_fabrica == 183){
		$readOnly = "readOnly";
	}

	$sql = "SELECT *
			FROM tbl_solucao
			WHERE fabrica = {$login_fabrica}
			AND solucao = {$solucao};";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$solucao          	= trim(pg_result($res,0,solucao));
		$codigo 			= trim(pg_result($res,0,codigo));
		$descricao        	= trim(pg_result($res,0,descricao));
		$ativo            	= trim(pg_result($res,0,ativo));
		$troca_peca       	= trim(pg_result($res,0,troca_peca));
		$somente_cortesia 	= trim(pg_result($res,0,somente_cortesia));
		$classificacao 		= trim(pg_result($res,0,classificacao));
		
		$parametros_adicionais = trim(pg_fetch_result($res, 0, "parametros_adicionais"));

		if (!empty($parametros_adicionais)){
			$parametros_adicionais = json_decode($parametros_adicionais, true);
			$mao_de_obra = $parametros_adicionais["mao_de_obra"];
			$mao_de_obra = number_format($mao_de_obra,2,",",".");
		}
	}
}else{
	$readOnly = "";
}

if (strlen($btn_acao) > 0) {
	
	$descricao = trim($_POST["descricao"]);
	$troca_peca = trim($_POST["troca_peca"]);
	$ativo = trim($_POST["ativo"]);
	$classificacao = trim($_POST["classificacao"]);
	$codigo = trim($_POST["codigo"]);
	$mao_de_obra = $_POST["mao_de_obra"];
	$parametros_adicionais = array();

	if (strlen($ativo) == 0) $ativo = 'f';
	if (strlen($troca_peca) == 0) $troca_peca = 'f';
	if (strlen($somente_cortesia) == 0) $somente_cortesia = 'f';

	// Validações
	if ($btn_acao != "deletar") {

		if ($login_fabrica == 183 ){
			if (strlen($codigo) == 0) {
				$msg_erro['msg'][] = "Por favor insira o código da solução";
				$msg_erro['campos'][] = "codigo";
			}
			
			if (strlen($mao_de_obra) == 0) {
				$msg_erro['msg'][] = "Por favor informar o valor da mão de obra";
				$msg_erro['campos'][] = "mao_de_obra";
			}
		}

		if (strlen($descricao) == 0) {
			if($login_fabrica == 191){
				$msg_erro['msg'][] ="Por favor insira a descrição do serviço realizado";
			}else{
				$msg_erro['msg'][] ="Por favor insira a descrição da solução";
			}
			$msg_erro['campos'][] = "descricao";
		}

		if ($login_fabrica == 158) {

			if (strlen($codigo) == 0) {
				$msg_erro['msg'][] = "Por favor insira o código da solução";
				$msg_erro['campos'][] = "codigo";
			}

			if (strlen($classificacao) == 0) {
				$msg_erro['msg'][] ="Por favor, selecione uma classificação";
				$msg_erro['campos'][] = "classificacao";
			}

		}else{
			$classificacao = "null";
		}

	}

	if (($btn_acao == "gravar") AND (strlen($solucao) == 0)) {

		if ($login_fabrica == 183 AND !empty($mao_de_obra)){

			$sqlValida = "SELECT solucao FROM tbl_solucao WHERE fabrica = $login_fabrica AND codigo = '$codigo'";
			$resValida = pg_query($con, $sqlValida);
			
			if (pg_num_rows($resValida) > 0){
				if($login_fabrica == 191){
					$msg_erro['msg'][] = "Já existe um serviço realizado cadastrada com esse código";
				}else{
					$msg_erro['msg'][] = "Já existe uma solução cadastrada com esse código";
				}
			}

			$mao_de_obra = fnc_limpa_moeda($mao_de_obra);
			$campo_parametros_adicionais = ", parametros_adicionais";
			$parametros_adicionais["mao_de_obra"] = $mao_de_obra;
			$parametros_adicionais = ",'".json_encode($parametros_adicionais)."'";
		}else{
			$campo_parametros_adicionais = "";
			$parametros_adicionais = null;
		}

		if (count($msg_erro['msg']) == 0) {
			if ($login_fabrica == 35) {
				
				pg_query($con, "BEGIN;");

				$sql = "INSERT INTO tbl_solucao (
							descricao,
							ativo,
							fabrica,
							troca_peca,
							somente_cortesia
						) VALUES (
							'{$descricao}',
							'{$ativo}',
							{$login_fabrica},
							'{$troca_peca}',
							'{$somente_cortesia}'
						);";

				$res = pg_query($con,$sql);

				if (strlen(pg_last_error()) > 0) {
					pg_query($con, "ROLLBACK;");
					$msg_erro['msg'][] = "Ocorreu um erro durante a gravação dos dados #001";
				}
				
				$sql = "SELECT last_value FROM seq_solucao;";
				$res = pg_query($con,$sql);
				$codigo_solucao = pg_fetch_result($res,0,0);

				if (strlen(pg_last_error()) > 0) {
					pg_query($con, "ROLLBACK;");
					$msg_erro['msg'][] = "Ocorreu um erro durante a gravação dos dados #002";
				}

				if (count($msg_erro['msg']) == 0) {
					$sql = "UPDATE tbl_solucao SET
								codigo = '{$codigo_solucao}'
							WHERE solucao = {$codigo_solucao}
							AND fabrica = {$login_fabrica};";
					$res = pg_query($con,$sql);

					if (strlen(pg_last_error()) > 0) {
						pg_query($con, "ROLLBACK;");
						$msg_erro['msg'][] = "Ocorreu um erro durante a gravação dos dados #003";
					} else {
						pg_query($con, "COMMIT;");
						$msg_sucesso = "Dados Gravados com sucesso";
						unset($btn_acao, $solucao, $codigo, $descricao, $ativo, $troca_peca, $somente_cortesia, $classificacao);
					}

				}
			} else {
				$sql = "INSERT INTO tbl_solucao (
							descricao,
							ativo,
							fabrica,
							troca_peca,
							somente_cortesia,
							classificacao,
							codigo
							$campo_parametros_adicionais
						) VALUES (
							'{$descricao}',
							'{$ativo}',
							{$login_fabrica},
							'{$troca_peca}',
							'{$somente_cortesia}',
							{$classificacao},
							'{$codigo}'
							$parametros_adicionais
						)";
				$res = pg_query($con,$sql);
				if (strlen(pg_last_error()) > 0) {
					$msg_erro['msg'][] = "Ocorreu um erro durante a gravação dos dados #001";
				} else {
					$msg_sucesso = "Dados Gravados com sucesso";
					unset($btn_acao, $solucao, $codigo, $descricao, $ativo, $troca_peca, $somente_cortesia, $classificacao, $mao_de_obra);
					header("LOCATION: solucao_cadastro.php");
				}
			}
		}
	}
	
	if (($btn_acao == "gravar") AND (strlen($solucao) > 0)) {
		
		if (count($msg_erro['msg']) == 0) {
			if ($login_fabrica == 183 AND !empty($mao_de_obra)){
				$sqlParam = "SELECT tbl_solucao.parametros_adicionais FROM tbl_solucao WHERE solucao = $solucao" ;
				$resParam = pg_query($con, $sqlParam);

				if (pg_num_rows($resParam) > 0){
					$dadosParam = pg_fetch_result($resParam, 0, "parametros_adicionais");
					if (!empty($dadosParam)){
						$dadosParam = json_decode($dadosParam, true);
						$dadosParam["mao_de_obra"] = $mao_de_obra;
					}else{
						$dadosParam["mao_de_obra"] = $mao_de_obra;
					}
				}else{
					$dadosParam["mao_de_obra"] = $mao_de_obra;
				}
				$parametros_adicionais = json_encode($dadosParam);
				$campo_parametros_adicionais = ", parametros_adicionais = '$parametros_adicionais'";
			}

			if ($login_fabrica == 35) {
				$sql = "UPDATE tbl_solucao SET
								solucao = '{$solucao}',
								descricao = '{$descricao}',
								ativo = '{$ativo}',
								troca_peca = '{$troca_peca}',
								somente_cortesia = '{$somente_cortesia}',
								codigo = '{$solucao}'
						WHERE solucao = {$solucao}
						AND fabrica = {$login_fabrica};";
			}else{
				$sql = "UPDATE tbl_solucao SET
								solucao = '{$solucao}',
								descricao = '{$descricao}',
								ativo = '{$ativo}',
								troca_peca = '{$troca_peca}',
								somente_cortesia = '{$somente_cortesia}',
								classificacao = {$classificacao},
								codigo = '{$codigo}'
								$campo_parametros_adicionais
						WHERE solucao = {$solucao}
						AND fabrica = {$login_fabrica};";
			}
			$res = pg_query($con,$sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro['msg'][] = "Ocorreu um erro durante a alteração dos dados #004";
			} else {
				$msg_sucesso = "Alterado com sucesso!";
				unset($btn_acao, $solucao, $codigo, $descricao, $ativo, $troca_peca, $somente_cortesia, $classificacao, $mao_de_obra);
				header("LOCATION: solucao_cadastro.php");
			}
		}		
	}

	if ($btn_acao == "deletar" && strlen($solucao) > 0) {

		$sql1 = "SELECT * FROM tbl_solucao WHERE solucao = '$solucao'" ;
		$res = pg_query($con,$sql1);
		$ativ = pg_fetch_result($res,0,ativo);

		if($ativ == 't'){
			$msg_erro['msg'][] = "Solução não pode ser apagada, está sendo usada pelo sistema!";
		} else {
			$sql = "DELETE FROM tbl_solucao
					WHERE solucao = {$solucao}
					AND fabrica = {$login_fabrica};";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				if(strpos(pg_errormessage($con),"violates foreign key")) {
					if($login_fabrica == 191){
						$msg_erro['msg'][] = "Serviço Realizado não pode ser apagado, ele é usado nos outros cadastros do sistema #005";
					}else{
						$msg_erro['msg'][] = "Solução não pode ser apagada, ela é usada nos outros cadastros do sistema #005";
					}
				} else {
					if($login_fabrica == 191){
						$msg_erro['msg'][] = "Ocorreu um erro durante a exclusão do Serviço Realizado #006";
					}else{
						$msg_erro['msg'][] = "Ocorreu um erro durante a exclusão da solução #006";
					}
				}
			} else {
				$msg_sucesso = "Registro deletado com sucesso";
				unset($btn_acao, $solucao);
			}
		}
	} // DELETAR
}

$layout_menu = "cadastro";
if($login_fabrica == 191){
	$title = "CADASTRO DE SERVIÇO REALIZADO";
}else{
	$title = "CADASTRO DE SOLUÇÃO";
}
include 'cabecalho_new.php';

$plugins = array(
	"alphanumeric"
);

include("plugin_loader.php");
?>
<style type="text/css">
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>
<script type="text/javascript">
	$(function(){
		$("#mao_de_obra").numeric({allow: ','});
	});
</script>
<? if (count($msg_erro['msg']) > 0) { ?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro['msg']); ?></h4>
    </div>
<? } else if (strlen($msg_sucesso) > 0) { ?>
	<div class="alert alert-success">
		<h4><?= $msg_sucesso; ?></h4>
	</div>
<? } ?>

<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name='frm_solucao' method='post' action='<?= $PHP_SELF; ?>' class='form-inline tc_formulario'>
	<input type='hidden' name='solucao' value='<?= $solucao; ?>'>
	<div class="titulo_tabela">Cadastro de <?=($login_fabrica == 191) ? 'Serviço Realizado' : 'Solução'?></div>
	<br />
	<div class='row-fluid'>
	    <div class='span2'></div>
	    <?php if ($login_fabrica == 183){ ?>
	    	<div class='span2'>
		    	<div class="control-group <?= (in_array("codigo", $msg_erro["campos"])) ? "error" : ""; ?>">
					<label class="control-label" for="codigo">Código:</label>
					<div class="controls controls-row">
			            <div class="span12">
			                <h5 class="asteristico">*</h5>
			                <input type="text" <?=$readOnly?> name="codigo" value="<?= $codigo; ?>" class="span12" maxlength="50" />
			            </div>
			        </div>
		        </div>
		    </div>
	    <?php } ?>
	    <div class='span4'>
	    	<div class='control-group <?= (in_array("descricao", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class="control-label" for="descricao">Descrição</label>
				<div class="controls controls-row">
		            <div class="span12">
		                <h5 class="asteristico">*</h5>
		                <input type="text" <?=$readOnly?> name="descricao" value="<?= $descricao; ?>" class='span12' maxlength='50' />
		            </div>
		        </div>
	        </div>
	    </div>
	    <?php if($login_fabrica == 183){ ?>
	    <div class='span2'>
	    	<div class="control-group <?= (in_array("mao_de_obra", $msg_erro["campos"])) ? "error" : ""; ?>">
				<label class="control-label" for="mao_de_obra">Mão de obra:</label>
				<div class="controls controls-row">
		            <div class="span12">
		                <h5 class="asteristico">*</h5>
		                <input type="text" <?=$readOnly?> id="mao_de_obra" name="mao_de_obra" value="<?= $mao_de_obra; ?>" class="span12" maxlength="50" />
		            </div>
		        </div>
	        </div>
	    </div>
	    <?php } ?>
	    <div class='span2'>
	        <div class="span12">
	        	<label class="control-label" for="ativo">Ativo</label>
	        	<div class="controls controls-row">
		            <div class="span12">
	            		<input type='checkbox' name='ativo' value='t' <?= ($ativo == 't') ? "checked" : ""; ?> />
            		</div>
        		</div>
	        </div>
	    </div>
	    <? if (!in_array($login_fabrica, array(148,183)) ) { ?>
	    	<div class='span3'>
		        <div class="span12">
		        	<?php
		        	if (!isset($novaTelaOs)) {
		        	?>
		        		<label class="control-label" for="troca_peca">Troca de Peça</label>
		        	<?php
		        	} else {
		        	?>
		        		<label class="control-label" for="troca_peca">Permitir Lançamento de Peças?</label>
		        	<?php
		        	}
		        	?>
		        	<div class="controls controls-row">
			            <div class="span12">
		            		<input type='checkbox' name='troca_peca' value='t' <?= ($troca_peca == 't') ? "checked" : ""; ?> />
	            		</div>
	        		</div>
		        </div>
		    </div>
	    <? }
	 	if ($login_fabrica == 1) { ?>
	 		<div class='span2'>
		        <div class="span12">
		        	<label class="control-label" for="somente_cortesia">Somente Cortesia</label>
		        	<div class="controls controls-row">
			            <div class="span12">
		            		<input type='checkbox' name='somente_cortesia' value='t' <?= ($somente_cortesia == 't') ? "checked" : ""; ?> />
	            		</div>
	        		</div>
		        </div>
		    </div>
		<? } ?>
	    <div class='span2'></div>
	</div>
	<? if ($login_fabrica == 158) { ?>
		<div class='row-fluid'>
		    <div class='span2'></div>
		    <div class='span2'>
		    	<div class="control-group <?= (in_array("codigo", $msg_erro["campos"])) ? "error" : ""; ?>">
					<label class="control-label" for="codigo">Código:</label>
					<div class="controls controls-row">
			            <div class="span12">
			                <h5 class="asteristico">*</h5>
			                <input type="text" name="codigo" value="<?= $codigo; ?>" class="span12" maxlength="50" />
			            </div>
			        </div>
		        </div>
		    </div>
		    <div class='span3'>
		    	<div class='control-group <?= (in_array("classificacao", $msg_erro["campos"])) ? "error" : ""; ?>'>
					<label class="control-label" for="classificacao">Classificação:</label>
					<div class="controls controls-row">
			            <div class="span12">
			                <h5 class="asteristico">*</h5>
			                <select name='classificacao' class='span12'>
			                	<option value="">SELECIONE</option>
			                	<?
			                	$sqlClassif = "SELECT * FROM tbl_classificacao WHERE fabrica = {$login_fabrica};";
			                	$resClassif = pg_query($con, $sqlClassif);

			                	$classificacoes = pg_fetch_all($resClassif);
			                	foreach ($classificacoes as $xclassificacao) { 
			                		$idClassificacao = $xclassificacao['classificacao'];
			                		$descClassificacao = $xclassificacao['descricao'];
			                		$selectedClassif = ($classificacao == $idClassificacao) ? "selected" : "";?>
			                		<option value="<?= $idClassificacao; ?>" <?= $selectedClassif; ?>><?= $descClassificacao; ?></option>
			                	<? } ?>
			                </select>
			            </div>
			        </div>
		        </div>
		    </div>
		    <div class='span2'></div>
		</div>
	<? } ?>
	<br/>
	<div class='row-fluid form-horizontal'>
		<div class='span12 tac'>
			<p>
				<input type='hidden' name='btn_acao' id='btn_acao' value='' />
				<input type="button" style="cursor:pointer;" value="Gravar" class='btn' onclick="if (document.getElementById('btn_acao').value == '' ) { document.getElementById('btn_acao').value='gravar' ; document.frm_solucao.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário"  />
				<? if ($_REQUEST["solucao"] AND $login_fabrica != 183) { ?>
					<a href="<?= $PHP_SELF; ?>?btn_acao=deletar&solucao=<?=$solucao?>">
						<input type="button" style="cursor:pointer;" value="Apagar" class='btn btn-danger' alt="Apagar Linha" />
					</a>
				<? } ?>
				<input type="button" style="cursor:pointer;" value="Limpar" class='btn btn-primary' onclick="javascript: if (document.frm_solucao.btn_acao.value == '' ) { document.frm_solucao.btn_acao.value='reset' ; window.location='<?= $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos">
			</p>
		</div>
	</div>
</form>

<div class='alert alert-warning tac'>
Para efetuar alterações, clique na descrição/código <?=($login_fabrica == 191) ? 'do serviço realizado' : 'da solução'?>.
</div>

<div class='container'>
	<table id='tbl_tipo_posto' class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
			<tr class="titulo_coluna">
			<th colspan="100%">Relação de <?=($login_fabrica == 191) ? 'Serviços Realizados Cadastrados' : 'Soluções Cadastradas'?></th>
			</tr>
			<tr class="titulo_coluna">
				<th>Código</th>
                                <th>Descrição</th>
				<? if ($login_fabrica == 158) { ?>
                                        <th nowrap>Família</th>
                                <? } ?>
				<th>Ativo</th>
				<? if (!in_array($login_fabrica, [148,183])) { ?>
					<th nowrap>Troca Peça</th>
				<? }
				if ($login_fabrica == 183){ ?>
					<th nowrap>Mão de Obra</th>				
				<?php }
				if ($login_fabrica == 1) { ?>
					<th nowrap>Somente Cortesia</th>
				<? }
				if ($login_fabrica == 158) { ?>
					<th nowrap>Classificação</th>
				<? } ?>
			</tr>
		</thead>
		<tbody>
			<?
			if ($login_fabrica == 158) {
				$familias = "
					, ARRAY_TO_STRING(ARRAY(
						SELECT DISTINCT f.descricao
						FROM tbl_diagnostico d
						INNER JOIN tbl_familia f ON f.familia = d.familia AND f.fabrica = $login_fabrica
						WHERE d.fabrica = $login_fabrica
						AND d.solucao = s.solucao
					), ', ') AS familias
				";
			}

			$sql = "SELECT
						s.*,
						c.descricao AS classificacao_desc
						$familias
					FROM tbl_solucao s
					LEFT JOIN tbl_classificacao c USING(classificacao, fabrica)
					WHERE s.fabrica = {$login_fabrica}
					ORDER BY s.descricao;";
			$res = pg_query($con,$sql);
			
			if (pg_num_rows($res) > 0) {
				for($y = 0; $y < pg_num_rows($res); $y++) {
					$solucao 	= trim(pg_result($res,$y,solucao));
					$codigo 	= trim(pg_result($res,$y,codigo));
					$descricao     = trim(pg_result($res,$y,descricao));
					$ativo                = trim(pg_result($res,$y,ativo));
					$troca_peca           = trim(pg_result($res,$y,troca_peca));
					$somente_cortesia     = trim(pg_result($res,$y,somente_cortesia));
					$classificacaoDesc     = trim(pg_result($res,$y,classificacao_desc));
					$parametros_adicionais = trim(pg_fetch_result($res, $y, "parametros_adicionais"));

					if (!empty($parametros_adicionais)){
						$parametros_adicionais = json_decode($parametros_adicionais, true);
						$mao_de_obra = $parametros_adicionais["mao_de_obra"];
						$mao_de_obra = number_format($mao_de_obra,2,",",".");
					}

					if($ativo=='t')            $ativo="Sim";            else $ativo="<font color='#660000'>Não</font>";
					if($troca_peca=='t')       $troca_peca="Sim";       else $troca_peca="<font color='#660000'>Não</font>";
					if($somente_cortesia=='t') $somente_cortesia="Sim"; else $somente_cortesia="<font color='#660000'>Não</font>"; ?>
					<tr>
						<td class='tac'><a href='<?= $PHP_SELF; ?>?solucao=<?= $solucao; ?>'><?= (!in_array($login_fabrica, array(158,183))) ? $solucao : $codigo; ?></a></td>
                    	<td class='tal'><a href='<?= $PHP_SELF; ?>?solucao=<?= $solucao; ?>'><?= $descricao; ?></a></td>
					<?php
					if ($login_fabrica == 158) {
						$familias = pg_fetch_result($res, $y, familias);
						?>
						<td><?=$familias?></td>
					<?php
					}
					?>
					<td class='tac'><?= $ativo; ?></td>
					<? if (!in_array($login_fabrica, [148,183])) { ?>
						<td class='tac'><?= $troca_peca; ?></td>
					<? }
					if ($login_fabrica == 183){ ?>
						<td class="tac"><?=$mao_de_obra?></td>
					<? }
					if ($login_fabrica == 1) { ?>
						<td class='tac'><?= $somente_cortesia; ?></td>
					<? }
					if ($login_fabrica == 158) { ?>
						<td class='tac'><?= $classificacaoDesc; ?></td>
					<? } ?>
					</tr>
				<? }
			} else { ?>
				<tr>
					<td class="tac">Nenhum registro encontrado</td>
				</tr>
			<? } ?>
		</tbody>
	</table>
</div>
<? include "rodape.php"; ?>
