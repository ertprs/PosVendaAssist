<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";
$msg_debug = "";

/**
* - Fábricas diferente de JACTO
* para cadastro de Transportadora
*/


if(in_array($login_fabrica,array(88,94,120,201,143,145,157,163,169,170,175,177))){
    header("Location:transportadora_cadastro_frete.php");
}

if (strlen($_POST['btn_acao']) > 0) $btn_acao = strtolower($_POST['btn_acao']);

#-------------------- Descredenciar -----------------
if ($btn_acao == "descredenciar") {
	$transportadora = $_POST['transportadora'];
	$sql = "DELETE FROM tbl_transportadora_fabrica WHERE transportadora = $transportadora AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	header ("Location: $PHP_SELF");
	exit;
}


#-------------------- Credenciar -----------------
if ($btn_acao == "credenciar") {
	$transportadora   = trim($_POST['transportadora']);
	$codigo_interno   = trim($_POST['codigo_interno']);
	$ativo            = trim($_POST['ativo']);
	$capital_interior = trim($_POST['capital_interior']);
	$valor_frete      = trim($_POST['valor_frete']);
	$estado           = trim($_POST['estado']);

	if (strlen($codigo_interno) == 0)
		$msg_erro = "Preencha o código interno da transportadora.";
	else
		$xcodigo_interno = "'".$codigo_interno."'";

	if (strlen($ativo) == 0)
		$msg_erro = "Selecione se a transportadora está ativa ou não";
	else
		$xativo = "'".$ativo."'";

	// dados para transportadora padrao, nao sao
	if (strlen($estado) == 0)
		$xestado = "null";
	else
		$xestado = "'".strtoupper($estado)."'";

	if (strlen($capital_interior) == 0)
		$xcapital_interior = "null";
	else
		$xcapital_interior = "'".$capital_interior."'";

	if (strlen($valor_frete) == 0){
		$xvalor_frete = "null";
	}else{
		$valor_frete = str_replace(".", "", $valor_frete);
		$valor_frete = str_replace(",", ".", $valor_frete);
		$xvalor_frete = "'".$valor_frete."'";
	}

	if (strlen($msg_erro) == 0){

		$sql = "INSERT INTO tbl_transportadora_fabrica (
					transportadora,
					fabrica       ,
					codigo_interno,
					ativo
				) VALUES (
					$transportadora ,
					$login_fabrica  ,
					$xcodigo_interno,
					$xativo
				)";
		$res = @pg_exec ($con,$sql);
		if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0){
			if (strlen($xcapital_interior) > 0 AND strlen($xvalor_frete) > 0){

				// seleciona para ver se está cadastrado em tbl_transportadora_padrao
				$sql = "SELECT	*
						FROM	tbl_transportadora_padrao
						WHERE	transportadora = $transportadora
						AND     fabrica = $login_fabrica";
				$res = @pg_exec ($con,$sql);
				if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

				if (pg_numrows($res) > 0){
					$sql = "UPDATE tbl_transportadora_padrao SET
								capital_interior = $xcapital_interior,
								valor_frete      = $xvalor_frete     ,
								estado           = $xestado
							WHERE transportadora = $transportadora ";
				}else{
					$sql = "INSERT INTO tbl_transportadora_padrao (
								transportadora  ,
								fabrica         ,
								capital_interior,
								valor_frete     ,
								estado
							) VALUES (
								$transportadora   ,
								$login_fabrica    ,
								$xcapital_interior,
								$xvalor_frete     ,
								$xestado
							)";
				}
				$res = @pg_exec ($con,$sql);
				if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
			}
		}

		if(strlen($msg_erro) == 0){
			header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
			exit;
		}
	}
}

#---------------- Cadastra/Altera Transportadora --------------
if ($btn_acao == "gravar"){

	$nome = trim($_POST['nome']);
	$cnpj = trim($_POST['cnpj']);
	$xcnpj = str_replace ("-","",$cnpj);
	$xcnpj = str_replace (".","",$xcnpj);
	$xcnpj = str_replace ("/","",$xcnpj);
	$xcnpj = str_replace (" ","",$xcnpj);


	if (strlen($xcnpj) > 0) {
		// verifica se posto está cadastrado
		$sql = "SELECT transportadora
				FROM   tbl_transportadora
				WHERE  cnpj = '$xcnpj'";
		$res = @pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {
			$transportadora = pg_result ($res,0,0);
			if (strlen($transportadora) > 0){
				$sqlfab = "SELECT transportadora
						FROM   tbl_transportadora_fabrica
						WHERE  transportadora = $transportadora
						AND    fabrica = $login_fabrica";
				$resfab = @pg_exec ($con,$sqlfab);

				if (pg_numrows ($resfab) > 0) {
					$transp_fab = pg_result ($res,0,0);
				}
			}
			#header ("Location: $PHP_SELF?transportadora=" . pg_result ($res,0,0) );
			#exit;
		} /* else{
			$msg_erro = "Transportadora não cadastrada, favor completar os dados do cadastro.";
		} */
	}

	if (strlen($msg_erro) == 0){

		$fantasia         = trim($_POST['fantasia']);
		$codigo_interno   = trim($_POST['codigo_interno']);
		$ativo            = trim($_POST['ativo']);
		$capital_interior = trim($_POST['capital_interior']);
		$valor_frete      = trim($_POST['valor_frete']);
		$estado           = trim($_POST['estado']);

		if (strlen($nome) == 0)
			$msg_erro = "Preencha o campo Nome da transportadora.";
		else
			$xnome = "'".$nome."'";

		if (strlen($xcnpj) == 0){
			$msg_erro = "Preencha o campo CNPJ da transportadora.";
		}else{
			$xcnpj = str_replace(array(".", "/", "-"), "", $cnpj);
			$xcnpj = "'".$xcnpj."'";
		}

		if (strlen($fantasia) == 0)
			$xfantasia = "null";
		else
			$xfantasia = "'".$fantasia."'";

		if (strlen($codigo_interno) == 0)
			$msg_erro = "Preencha o código interno da transportadora.";
		else
			$xcodigo_interno = "'".$codigo_interno."'";

		if (strlen($ativo) == 0)
			$msg_erro = "Selecione se a transportadora está ativa ou não.";
		else
			$xativo = "'".$ativo."'";

		// dados para transportadora padrao, nao sao
		if (strlen($estado) == 0)
			$xestado = "null";
		else
			$xestado = "'".strtoupper($estado)."'";

		if (strlen($capital_interior) == 0)
			$xcapital_interior = "null";
		else
			$xcapital_interior = "'".$capital_interior."'";

		if (strlen($valor_frete) == 0){
			$xvalor_frete = "null";
		}else{
			$valor_frete = str_replace(".", "", $valor_frete);
			$valor_frete = str_replace(",", ".", $valor_frete);
			$xvalor_frete = "'".$valor_frete."'";
		}

		if (strlen($msg_erro) == 0){

			$res = @pg_exec ($con,"BEGIN TRANSACTION");

			if (strlen($transportadora) > 0){
				// ######################## ALTERA ########################
				$sql = "UPDATE tbl_transportadora SET
							nome     = $xnome    ,
							cnpj     = $xcnpj    ,
							fantasia = $xfantasia
						WHERE transportadora = $transportadora";
				$res = @pg_exec ($con,$sql);
				if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
				if (strlen($msg_erro) == 0){
					if (strlen($transp_fab)>0){
						$sql = "UPDATE tbl_transportadora_fabrica SET
								codigo_interno = $xcodigo_interno,
								ativo          = $xativo
							WHERE transportadora = $transportadora
							AND   fabrica      = $login_fabrica ";
						$res = @pg_exec ($con,$sql);
						if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
					}else{
						 $sql = "INSERT INTO tbl_transportadora_fabrica (
                                                                transportadora,
                                                                fabrica       ,
                                                                codigo_interno,
                                                                ativo
                                                        ) VALUES (
                                                                $transportadora ,
                                                                $login_fabrica  ,
                                                                $xcodigo_interno,
                                                                $xativo
                                                        )";
	                                        $res = @pg_exec ($con,$sql);
						if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
					}
				}

			}else{

				// ######################## INSERE ########################
				$sql = "INSERT INTO tbl_transportadora (
							nome     ,
							cnpj     ,
							fantasia
						) VALUES (
							$xnome    ,
							$xcnpj    ,
							$xfantasia
						)";
				$res = @pg_exec ($con,$sql);
				if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

				if (strlen($msg_erro) == 0){
					$res = @pg_exec ($con,"SELECT CURRVAL('seq_transportadora')");

					if (strlen (pg_errormessage ($con)) > 0)
						$msg_erro = pg_errormessage($con);
					else
						$transportadora = pg_result($res,0,0);
				}

				if (strlen($msg_erro) == 0){
					$sql = "INSERT INTO tbl_transportadora_fabrica (
								transportadora,
								fabrica       ,
								codigo_interno,
								ativo
							) VALUES (
								$transportadora ,
								$login_fabrica  ,
								$xcodigo_interno,
								$xativo
							)";
					$res = @pg_exec ($con,$sql);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
				}
			}

			if (strlen($msg_erro) == 0){
				if (strlen($xcapital_interior) > 0 AND strlen($xvalor_frete) > 0 AND strlen($transportadora) > 0){

					// seleciona para ver se está cadastrado em tbl_transportadora_padrao
					$sql = "SELECT	*
							FROM	tbl_transportadora_padrao
							WHERE	transportadora = $transportadora
							AND     fabrica = $login_fabrica";
					$res = @pg_exec ($con,$sql);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

					if (pg_numrows($res) > 0){
						$sql = "UPDATE tbl_transportadora_padrao SET
									capital_interior = $xcapital_interior,
									valor_frete      = $xvalor_frete     ,
									estado           = $xestado
								WHERE transportadora = $transportadora
								AND   fabrica = $login_fabrica";
					}else{
						$sql = "INSERT INTO tbl_transportadora_padrao (
									transportadora  ,
									fabrica         ,
									capital_interior,
									valor_frete     ,
									estado
								) VALUES (
									$transportadora   ,
									$login_fabrica    ,
									$xcapital_interior,
									$xvalor_frete     ,
									$xestado
								)";
					}
					$res = pg_exec ($con,$sql);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
				}
			}

			if(strlen($msg_erro) > 0){
				"ROLLBACK";
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}else{
				"COMMIT";
				$res = @pg_exec ($con,"COMMIT TRANSACTION");
				$msg = "Gravado com Sucesso!";
//				header ("Location: transportadora_cadastro_new.php");
//				exit;
			}

		}
	}

}

#-------------------- Pesquisa -----------------
$transportadora = $_POST['transportadora'];

if (strlen($transportadora) == 0 ) $transportadora = $_GET['transportadora'];

if (strlen($transportadora) > 0 and strlen ($msg_erro) == 0 ) {
	$sql = "SELECT	tbl_transportadora.nome   ,
					tbl_transportadora.cnpj                   ,
					tbl_transportadora.fantasia
			FROM	tbl_transportadora
			LEFT JOIN	tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora
			LEFT JOIN	tbl_transportadora_padrao
			ON		tbl_transportadora_padrao.transportadora = tbl_transportadora.transportadora
			WHERE	tbl_transportadora.transportadora = $transportadora";

	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) > 0) {
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
		$fantasia         = trim(pg_result($res,0,fantasia));

		$sql = "SELECT	tbl_transportadora_fabrica.codigo_interno ,
						tbl_transportadora_fabrica.ativo          ,
						tbl_transportadora_padrao.capital_interior,
						tbl_transportadora_padrao.valor_frete     ,
						tbl_transportadora_padrao.estado
				FROM	tbl_transportadora_fabrica
				JOIN	tbl_transportadora_padrao USING(transportadora)
				WHERE	tbl_transportadora_fabrica.transportadora = $transportadora
				AND		tbl_transportadora_fabrica.fabrica = $login_fabrica";

		$res = pg_exec ($con,$sql);

		if (@pg_numrows ($res) > 0) {
			$capital_interior = trim(pg_result($res,0,capital_interior));
			$valor_frete      = trim(pg_result($res,0,valor_frete));
			$estado           = trim(pg_result($res,0,estado));
			$codigo_interno   = trim(pg_result($res,0,codigo_interno));
			$ativo            = trim(pg_result($res,0,ativo));
		}else{
			$codigo_interno   = "";
		}

	}
}-



$visual_black = "manutencao-admin";
if(strlen($msg)==0)
	$msg = $_GET['msg'];


$title     = "Cadastro de Transportadoras";
$cabecalho = "Cadastro de Transportadoras";

$layout_menu = "cadastro";

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"shadowbox",
	"mask",
	"dataTable",
	"price_format"
);

include("plugin_loader.php");

?>
<script>
$(function() {
	$.autocompleteLoad(Array("transportadora"));
	Shadowbox.init();
		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

});

function retorna_transportadora (retorno) {
		$("#codigo_interno").val(retorno.codigo_interno);
		$("#cnpj").val(retorno.cnpj);
		$("#nome").val(retorno.nome);
		$("#fantasia").val(retorno.fantasia);

		if(retorno.ativo == 't'){
			$("#op_sim").prop('checked', true);
		}else{
			$("#op_nao").prop('checked', true);
		}

		$("#estado").val(retorno.estado);
		$("#capital_interior option:contains('" + retorno.capital_interior + "')").prop('selected', true);
		$("#valor_frete").val(retorno.valor_frete);
console.log(retorno);
	}

</script>

<? if (strlen ($msg_erro) > 0) { ?>
	<div class='alert alert-error' >
		<h4><? echo $msg_erro;?></h4>
	</div>
<? } ?>
<? if (strlen ($msg) > 0) { ?>
	<div class="alert alert-success">
		<h4><? echo $msg;?></h4>
	</div>
<? } ?>


<div class="row-fluid">
	<div class="alert">
	Para incluir uma nova transportadora, preencha somente seu CNPJ e clique em gravar.<br />
	Faremos uma pesquisa para verificar se a transportadora já está cadastrada em nosso banco de dados.
	</div>
</div>

<form name='frm_transportadora' METHOD='post' ACTION='<? echo $PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
<input type="hidden" name="transportadora" value="<? echo $transportadora ?>">

	<div class='titulo_tabela '>Cadastro de Transportadora</div>
	<br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='cnpj'>CNPJ</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="cnpj" name="cnpj" class='span12' maxlength="18" value="<? echo $cnpj ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="transportadora" parametro="cnpj" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='nome'>Nome da Transportadora</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="nome" name="nome" class='span12 ' value="<? echo $nome ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="transportadora" parametro="nome" />
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
					<label class='control-label' for='codigo_interno'>Código</label>
					<div class='controls controls-row'>
						<div class='span7'>
							<input type="text" id="codigo_interno" name="codigo_interno" class='span12' maxlength="20" value="<? echo $codigo_interno ?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='fantasia'>Fantasia</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" id="fantasia" name="fantasia" class='span12' value="<? echo $fantasia ?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span8"><label>Ativo</label></div>
			<div class="span2"></div>
		</div>

		<div id="ativo" class='row-fluid'>
			<div class='span2'></div>

			<div class='span4'>
				 <label class="radio" for="op_sim">
 					<input type="radio" name="ativo" value="t" <? if ($ativo == 't') echo"checked"; ?> id='op_sim'>
					Sim
			    </label>
			</div>
			<div class='span4'>
			    <label class="radio" for="op_nao">
			    	<input type="radio" name="ativo" value="f" <? if ($ativo == 'f') echo"checked"; ?> id='op_nao'>
					Não
			    </label>
			</div>
			<div class='span2'></div>
		</div>

		<p><br/>
			<input  class="btn" type="button" value="LISTAR TODAS AS TRANSPORTADORAS" onclick="javascript: window.location='<? echo $PHP_SELF ?>?listar=todos#transportadoras';">
		</p><br/>

		<div class="row-fluid">
			<div class="alert">
				Os dados abaixo são de preenchimento obrigatório no caso de Transportadora Padrão.
			</div>
		</div>

		<div class='titulo_tabela '>Informações Cadastrais</div>

		<br 0>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='estado'>Estado</label>
					<div class='controls controls-row'>
						<div class='span7'>
							<select id="estado" name="estado" class='span12'>
							<?php

							$estados_arr = array(
								'AC' => 'Acre',
								'AL' => 'Alagoas',
								'AP' => 'Amapá',
								'AM' => 'Amazonas',
								'BA' => 'Bahia',
								'CE' => 'Ceará',
								'DF' => 'Distrito Federal',
								'ES' => 'Espírito Santo',
								'GO' => 'Goiás',
								'MA' => 'Maranhão',
								'MT' => 'Mato Grosso',
								'MS' => 'Mato Grosso do Sul',
								'MG' => 'Minas Gerais',
								'PA' => 'Pará',
								'PB' => 'Paraíba',
								'PR' => 'Paraná',
								'PE' => 'Pernambuco',
								'PI' => 'Piauí',
								'RJ' => 'Rio de Janeiro',
								'RN' => 'Rio Grande do Norte',
								'RS' => 'Rio Grande do Sul',
								'RO' => 'Rondônia',
								'RR' => 'Roraima',
								'SC' => 'Santa Catarina',
								'SP' => 'São Paulo',
								'SE' => 'Sergipe',
								'TO' => 'Tocantins'
							);

							foreach ($estados_arr as $uf => $nome_estado) {
								
								$selected = ($uf == $estado) ? "selected" : "";

								echo "<option value='{$uf}' {$selected} > {$uf} </option>";

							}

							?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='capital_interior'>Local</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<select id="capital_interior" name="capital_interior">
								<option selected></option>
								<option value="CAPITAL" <? if ($capital_interior == "CAPITAL") echo " selected"; ?>>CAPITAL</option>
								<option value="INTERIOR" <? if ($capital_interior == "INTERIOR") echo " selected"; ?>>INTERIOR</option>
							</select>
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
					<label class='control-label' for='valor_frete'>Valor do Frete</label>
					<div class='controls controls-row'>
						<div class='span7'>
							<input type="text" id="valor_frete" name="valor_frete" class='span12' maxlength="20" value="<? echo $valor_frete ?>" price="true" >
						</div>
					</div>
				</div>
			</div>
		</div>

		<p><br/>

			<?
				// credenciar
				if(strlen($transportadora) > 0 AND strlen($codigo_interno) == 0){
			?>
					<input class="btn" type="button"value="Credenciar" onclick="javascript: if (document.frm_transportadora.btn_acao.value == '' ) { document.frm_transportadora.btn_acao.value='credenciar' ; document.frm_transportadora.submit() } else { alert ('Aguarde submissão') }" ALT="Credenciar transportadora">
			<?	}else{ ?>
					<input class="btn" type="button" value="Gravar" onclick="javascript: if (document.frm_transportadora.btn_acao.value == '' ) { document.frm_transportadora.btn_acao.value='gravar' ; document.frm_transportadora.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário">
					<!-- <img src='imagens_admin/btn_apagar.gif' style="cursor: pointer;" onclick="javascript: if (document.frm_transportadora.btn_acao.value == '' ) { if(confirm('Deseja realmente DESCREDENCIAR esta Transportadora ?') == true) { document.frm_transportadora.btn_acao.value='descredenciar'; document.frm_transportadora.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }" ALT="Apagar a Transportadora" border='0'> -->
			<?	} ?>
			&nbsp; &nbsp;&nbsp;
			<input type="button" class="btn" value="Limpar" onclick="javascript: if (document.frm_transportadora.btn_acao.value == '' ) { document.frm_transportadora.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos">
			<input type='hidden' name='btn_acao' value=''>
		</p><br/>

</form>

<?
if ($_GET['listar'] == 'todos') {
	if($login_fabrica == 87){ //HD 373202 - Ronald
		$cond = 'WHERE tbl_transportadora_fabrica.fabrica = '.$login_fabrica;
	}
	$sql = "SELECT	tbl_transportadora.transportadora        ,
					tbl_transportadora.cnpj                  ,
					tbl_transportadora.nome                  ,
					tbl_transportadora_fabrica.codigo_interno
					FROM	tbl_transportadora
					JOIN	tbl_transportadora_fabrica USING (transportadora)
					$cond
					ORDER BY tbl_transportadora.nome";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	 	if ($i % 20 == 0) {
	 		if ($i > 0) echo "</table>";
	 		flush();
			echo "<table id='resultado_os_atendimento' class='table table-striped table-bordered table-hover table-full' style='min-width: 850px;' > ";
	 		echo "<thead>";

	 		echo "<tr class='titulo_tabela'>";
	 		echo "<th colspan='3'>LISTA COM TODAS TRANSPORTADORAS</th>";
	 		echo "</tr>";

	 		echo "<tr class='titulo_coluna'>";

	 		echo "<th>";
	 		echo "<b>Código</b>";
	 		echo "</th>";

	 		echo "<th>";
	 		echo "<b>CNPJ</b>";
	 		echo "</th>";

	 		echo "<th>";
	 		echo "<b>Nome</b>";
	 		echo "</th>";

	 		echo "</tr>";
	 		echo "</thead>";
	 	}

	 	echo "<tbody>";
	 	echo "<tr>";

	 	echo "<td class='tal'>";
	 	echo pg_result ($res,$i,codigo_interno);
	 	echo "</td>";

	 	echo "<td class='tal'>";
	 	echo pg_result ($res,$i,cnpj);
	 	echo "</td>";

	 	echo "<td class='tal'>";
	 	echo "<a href='$PHP_SELF?transportadora=" . pg_result ($res,$i,transportadora) . "'>";
	 	echo pg_result ($res,$i,nome);
	 	echo "</a>";
	 	echo "</td>";

	 	echo "</tr>";
	 	echo "</tbody>";
	}

	 echo "</table>";
}

?>


<? include "rodape.php"; ?>
