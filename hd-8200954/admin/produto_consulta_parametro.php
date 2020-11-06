<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$layout_menu = "cadastro";
$title = "Relação de Produtos";

include 'cabecalho_new.php';

$mens = '';

if ($_POST["acao"] == true) {
	$tipo   = $_POST['tipo'];
	$origem = $_POST['origem'];

	if (strlen($tipo) == 0 && strlen($origem) == 0) {
    	$msg_erro["campos"][] = "tipo";
		$msg_erro["campos"][] = "origem";
		$msg_erro["msg"][] = "Selecione a Origem ou Tipo";
	} 

	if (count($msg_erro["msg"]) == 0) {

		$sql = "SELECT  produto               ,
									referencia           ,
									descricao            ,
									origem                ,
									peso                  
					FROM	tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE	fabrica               = $login_fabrica ";

		switch ($origem){
			case 'Nac':		$sql .= "AND UPPER(origem) = UPPER('nac') ";	$mens .= " FABRICAÇÃO - ";	break;
			case 'Imp':		$sql .= "AND UPPER(origem) = UPPER('imp')";	$mens .= " IMPORTADO - ";	break;
			case 'USA':		$sql .= "AND UPPER(origem) = UPPER('usa')";	$mens .= " IMPORTADO USA - ";	break;
			case 'Asi':		$sql .= "AND UPPER(origem) = UPPER('asi')";	$mens .= " IMPORTADO Asia - ";	break;
			default:	break;
		}

		switch ($tipo){
			case 1:		$sql .= "AND lista_troca IS true ";					$mens .= " LISTA DE TROCA ";		break;
			case 2:		$sql .= "AND intervencao_tecnica IS true ";			$mens .= " INTERVENÇÃO TÉCNICA ";			break;
			case 3:		$sql .= "AND numero_serie_obrigatorio IS true ";	$mens .= " NÚMERO DE SÉRIE OBRIGATÓRIO ";	break;
			case 4:		$sql .= "AND produto_principal IS true ";			$mens .= " PRODUTO PRINCIPAL ";	break;
			case 5:		$sql .= "AND troca_obrigatoria IS true ";			$mens .= " TROCA OBRIGATÓRIA ";	break;
			case 6:		$sql .= "AND parametros_adicionais::jsonb->>'ativacao_automatica' = 't' ";      $mens .= " ATIVAÇÃO AUTOMÁTICA ";	break;
			default:	break;
		}

		$sql .= "ORDER BY descricao ASC, referencia ASC";
		
		$res_sql = pg_query($con,$sql);
	}
}
?>
<style type="text/css">

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

</style>
	<?php
		if (count($msg_erro["msg"]) > 0) {
			echo '<div class="alert alert-danger">'.implode("<br>", $msg_erro["msg"]).'</div>';
		} 
	?>
	<form class="form-search form-inline tc_formulario" name='frm_consulta' method='post' action='<? echo $PHP_SELF; ?>'>
		<input type="hidden" name="acao" value="true">
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class='control-group <?=(in_array("origem", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for="origem">Origem</label>
					<div class='controls controls-row'>
				 		<div class="span7 input-append">
				 			<select name='origem' id='origem'>
								<option value=''>Selecione...</option>
								<option value="Nac"  <? if ($origem == "Nac")  echo " SELECTED "; ?>>Nacional</option>
								<option value="Imp"  <? if ($origem == "Imp")  echo " SELECTED "; ?>>Importado</option>
								<option value="USA" <? if ($origem == "USA") echo " SELECTED "; ?>>Importado USA</option>
								<option value="Asi"   <? if ($origem == "Asi")   echo " SELECTED "; ?>>Importado Asia</option>
							</select>
				 		</div>
				 	</div>
				</div>
			</div>
			<div class="span4">
				<div class='control-group <?=(in_array("tipo", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for="tipo">Tipo</label>
					<div class='controls controls-row'>
						<select name='tipo' id='tipo'>
							<option value=''>Selecione...</option>
							<option value='1' <? if ($tipo == 1) echo "selected";?>> Lista de Troca </option>
							<option value='2' <? if ($tipo == 2) echo "selected";?>> Intervenção Técnica </option>
							<option value='3' <? if ($tipo == 3) echo "selected";?>> Número de Série Obrigatório </option>
							<option value='4' <? if ($tipo == 4) echo "selected";?>> Produto Principal </option>
							<option value='5' <? if ($tipo == 5) echo "selected";?>> Troca Obrigatória </option>
							<option value='6' <? if ($tipo == 6) echo "selected";?>> Ativação Automática </option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<br />
		<div class="row-fluid">
			<div class="tac">
				<button class="btn btn-primary" type="button" onclick='javascript: document.frm_consulta.submit();' style='cursor:pointer'>Confirmar</button>
			</div>	
		</div>	
	</form>
<br>
<?php
if (count($msg_erro["msg"]) == 0) {
	if (pg_num_rows($res_sql) > 0){
		
		echo "<div class='container-fluid'>";
		echo "<table width='700' align='center' border='0' class='table table-striped table-bordered table-hover table-large' cellpadding='0' cellspacing='1'>";
		echo "<thead>";
		echo "<tr class='titulo_coluna' height='20'>";
		echo "<td colspan='5' style='text-align : center !IMPORTANT;'>$mens</td>";
		echo "</tr>";

		echo "<tr class='titulo_coluna' height='20'>";
		echo "<td style='text-align:center;'>Referência</td>";
		echo "<td style='text-align:center;'>Descrição</td>";
		echo "<td style='text-align:center;'>Origem</td>";
		echo "</tr>";
		echo "</thead>
			  <tbody>";

		for ($i = 0 ; $i < pg_num_rows ($res_sql) ; $i++) {
				$bg = ($i%2 == 0) ? '#fbfbfb' : '#FFFFFF';
			echo "<tr class='table_line' height='18'>";
			echo "<td style='text-align:center;' bgcolor='$bg'>".pg_fetch_result ($res_sql,$i,'referencia')."</td>";
			echo "<td style='text-align:center;' bgcolor='$bg'>".pg_fetch_result ($res_sql,$i,'descricao')."</td>";
			echo "<td style='text-align:center;' bgcolor='$bg'>".pg_fetch_result ($res_sql,$i,'origem')."</td>";
			echo "</tr>";
		}

		echo "</tbody>";
		echo "</table>";
		echo "</div>";
	} else {
		echo "<div class='container alert alert-warning' class='msg_erro' ><h4>Nenhum resultado encontrado</h4></div>";
	}
}
?>

<? include "rodape.php"; ?>
