<?php
/**
 * @author Brayan L. Rastelli
 * @description Manutenção de exceções
 */
 
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	$layout_menu      	= "cadastro";
	$admin_privilegios	= "cadastro";

	include 'autentica_admin.php';
	require_once 'helper.php';

	/**
	 *AJAX para fazer UPDATE ou INSERT na ação de cada campo
	 * 
	 */
	if(isset($_GET['ajax'])){

		$familia 	= (int) $_GET['familia'];
		$valor 		= (strlen($_GET['valor']) == 0) ? 0 : str_replace(',', '.', $_GET['valor']);
		$campo 		= $_GET['campo'];
		$excecao 	= $_GET['excecao'];
		$posto 		= $_GET['posto'];

		switch ($campo) {
			case 'mao_de_obra[]':
				$mo 	= $valor ;
				$ad_mo 	= 'null' ;
				$pc_mo 	= 'null' ;
				$update = " mao_de_obra = $mo ";
				break;
			case 'ad_mao_de_obra[]':
				$ad_mo 	= $valor ;
				$mo 	= 'null' ;
				$pc_mo 	= 'null' ;
				$update = " adicional_mao_de_obra  = $ad_mo ";
				break;
			case 'pc_mao_de_obra[]':
				$pc_mo 	= $valor ;
				$mo 	= 'null' ;
				$ad_mo 	= 'null' ;
				$update = " percentual_mao_de_obra = $pc_mo ";
				break;
		}

		
		if(empty($excecao)){

			$sql = "INSERT INTO tbl_excecao_mobra(
													posto,
													adicional_mao_de_obra,
													percentual_mao_de_obra,
													fabrica,
													mao_de_obra,
													familia)	VALUES(
													$posto,
													$ad_mo,
													$pc_mo,
													$login_fabrica,
													$mo,
													$familia
													) RETURNING excecao_mobra";
			$res = pg_query($con,$sql);
			if(!pg_last_error($con)){
				$excecao = pg_fetch_result($res, 0, 0);
				echo "ok|$excecao";
			}else{
				echo pg_last_error($con);
			}

		}else{

			$sql = "UPDATE 
							tbl_excecao_mobra 
						SET 
							$update
						WHERE excecao_mobra = $excecao";

			$res = pg_query($con, $sql);
			if(!pg_last_error($con)){
				echo "ok!$excecao";
			}else{
				echo pg_last_error($con);
			}
		}

		exit;
	}

	/** 
	 * Request para pesquisar 
	 * Foi colocado com $_REQUEST pois o Ronaldo solicitou continuar listando os resultados, após gravar o formulário.
	 */
	if( isset($_REQUEST['familia']) ) {
	
		try {

			$linha 		= (int) $_REQUEST['linha'];
			$familia 	= (int) $_REQUEST['familia'];

			$cond = array();

			if (!empty($linha)) {
				$cond[] = 'AND tbl_excecao_mobra.linha = ' . $linha;
			}

			if (!empty($familia)) {
				$cond[] = 'AND tbl_excecao_mobra.familia = ' . $familia;
			}

		} catch (Exception $e) {
			
			$msg_erro = $e->getMessage();

		}
	
	}
	/* Fim Request Pesquisar */

		
	$title="MANUTENÇÃO DE EXCEÇÃO DE MÃO-DE-OBRA";

	include 'cabecalho.php';

?>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript">


	$(document).on('blur', 'input.mao_de_obra', function(){
	// $("input.mao_de_obra").blur(function(){

		var valor 	= $(this).val();
		var campo 	= $(this).attr('name');
		var familia = $(this).parent().find(".familia").val();
		var id 		= $(this).parent().find(".excecao_mobra").val();
		var posto 	= $(this).parent().find(".posto").val();
		var link		= "<?php echo $_SERVER['PHP_SELF']; ?>?ajax=1&valor="+valor+"&campo="+campo+"&familia="+familia+"&excecao="+id+"&posto="+posto;
		var valor_ant = "";
				
		if(campo == "mao_de_obra[]"){
			valor_ant = $(this).parent().find(".mao_de_obra_ant").val();
		}else if(campo == "ad_mao_de_obra[]"){
			valor_ant = $(this).parent().find(".ad_mao_de_obra_ant").val();
		}else if(campo == "pc_mao_de_obra[]"){
			valor_ant = $(this).parent().find(".pc_mao_de_obra_ant").val();
		}

		var input_mo = $(this);

		if( /* valor != "" && */ valor != valor_ant){

			$.ajax({
				url:link,
				cache:false,
				success:function(result){

					var data = result.split('|');

					// console.log(data);
				
					if(data[0] == 'ok'){

						if(data[1] != ""){
							input_mo.parent().find(".excecao_mobra").val(data[1]);
						}

						if(campo == "mao_de_obra[]"){
							input_mo.parent().find(".mao_de_obra_ant").val(valor);
						}else if(campo == "ad_mao_de_obra[]"){
							input_mo.parent().find(".ad_mao_de_obra_ant").val(valor);
						}else if(campo == "pc_mao_de_obra[]"){
							input_mo.parent().find(".pc_mao_de_obra_ant").val(valor);
						}

					}else{
						$(".msg_erro").html(data[0]);
					}
				}

			});

		}
	});

</script>

<style type="text/css">

	.titulo_tabela {
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_coluna {
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro {
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario {
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	.formulario label{
		display: block;
	}
	.formulario select {
		min-width: 150px;
	}
	table.tabela tr td {
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	table.tabela tr td table tr td {
		border:none;
	}

	#tabela {display:none;}
	.sucesso {
	    background-color:#008000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}
	#relatorio tr td { cursor:pointer; }

</style>

<div class="msg_erro" style="width:700px;margin:auto; text-align:center; display:none;"></div>

<?php if ( isset($msg) ) { ?>

	<div class="sucesso" style="width:700px;margin:auto; text-align:center;"><?=$msg?></div>

<?php } ?>

<div class="formulario" style="width:700px; margin:auto;">
	
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<form action="<?=$PHP_SELF?>" method="POST">
		<div style="padding:10px;">
			<table style="width:320px;margin:auto; text-align:left; border:none;">
				<tr>
					<td>
						<label for="familia">Família</label>
						<select name="familia" id="familia">
							<option value="">Todas</option>
							<?php $params = array("ativo"=>"'t'");
								foreach($helper->crud->getFamilias($params ) as $familias) : ?>

								<option value="<?=$familias['familia']?>" <?=$familias['familia'] == $_REQUEST['familia'] ? 'selected' : '';?>>
									<?=$familias['descricao']?>
								</option>

							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="3" align="center">
						<input type="submit" name="pesquisar" value="Pesquisar"  />
					</td>
				</tr>
			</table>
		</div>
	</form>

</div>

<?php
function montaTd($familia,$resultados){
	foreach($resultados as $key => $valor) {
		if($familia == $valor['familia']){
			$id     = $valor['excecao_mobra'];
			$mo 	= $valor['mao_de_obra'];
			$ad_mo 	= $valor['adicional_mao_de_obra'];
			$pc_mo 	= $valor['percentual_mao_de_obra'];
			unset($familias[$key]);
			return	$valor['familia'].'||||
			<td>								
				<input type="hidden" name="excecao_mobra[]" value="'.$id.'" class="excecao_mobra" />
				<input type="hidden" name="familia[]" value="'.$valor['familia'].'" class="familia" />
				<input type="hidden" name="posto[]" value="'.$valor['posto'].'" class="posto" />

				<input type="hidden" name="mao_de_obra_ant" class="mao_de_obra_ant" value="'.$mo.'" size="5" />
				<input type="hidden" name="ad_mao_de_obra_ant" class="ad_mao_de_obra_ant" value="'.$ad_mo.'" size="5" />
				<input type="hidden" name="pc_mao_de_obra_ant" class="pc_mao_de_obra_ant" value="'.$pc_mo.'" size="5" />

				<input type="text" name="mao_de_obra[]" class="frm mao_de_obra" value="'.$mo.'" size="5" />
				<input type="text" name="ad_mao_de_obra[]" class="frm mao_de_obra" value="'.$ad_mo.'" size="5" />
				<input type="text" name="pc_mao_de_obra[]" class="frm mao_de_obra" value="'.$pc_mo.'" size="5" /></td>';
			exit;
		}
	}
}
	if ( ( isset($_POST['pesquisar']) ||  !empty($_REQUEST['familia']) ) ) :

		$sql = "SELECT 
					excecao_mobra,
				   	tbl_posto.nome AS descricao_posto, tbl_posto_fabrica.codigo_posto,
					tbl_posto.posto,
					tbl_excecao_mobra.mao_de_obra,
				   	tbl_excecao_mobra.adicional_mao_de_obra,
					tbl_excecao_mobra.percentual_mao_de_obra,
					tbl_familia.familia, 
					tbl_familia.descricao 	AS descricao_familia
				INTO TEMP excecao_$login_fabrica
				FROM 
					tbl_excecao_mobra
					JOIN tbl_posto 			USING(posto)
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_excecao_mobra.fabrica
					JOIN tbl_familia 	ON tbl_excecao_mobra.familia = tbl_familia.familia
				WHERE 
					tbl_excecao_mobra.fabrica = $login_fabrica
					AND tbl_excecao_mobra.posto <> 6359 " . implode(' ', $cond) . '
				ORDER BY descricao_posto,tbl_familia.descricao ';
		$res = pg_query($con, $sql);
		
		$sql = "SELECT distinct posto, descricao_posto, codigo_posto
			FROM excecao_$login_fabrica order by descricao_posto ";
		try {

			$res = pg_query($con, $sql);
			$resultados = pg_fetch_all($res);
			if (pg_errormessage($con) || pg_num_rows($res) == 0) {

				throw new Exception("Nenhum resultado encontrado " . pg_errormessage($con));				

			}
			
			echo "<br><table align='center' class='formulario' style='background-color:#FFF'>
					<tr><td>1º Campo: Valor de Mão-de-Obra</td></tr>
					<tr><td>2º Campo: Valor de Mão-de-Obra Adicional</td></tr>
					<tr><td>3º Campo: Percentual de Mão-de-Obra</td></tr>
				  </table> ";

			echo '<form action="'.$PHP_SELF.'" method="POST">
					<input type="hidden" name="familia" value="'.$_REQUEST['familia'].'">
				  	<table class="tabela" style="width:960px; margin:10px auto;" cellpadding="0" cellspacing="1">
						<thead>';
			$topo_repete = "			<tr class='titulo_coluna'>
							<th>Posto</th>";
			
							$params = (!empty($familia)) ? array("ativo"=>"'t'","familia"=>"$familia"):array("ativo"=>"'t'");

							foreach($helper->crud->getFamilias($params ) as $familias) {

								$topo_repete .= '<th>'.$familias['descricao'].'</td>';
							}
							
            $topo_repete .= "    </tr>";
            echo $topo_repete;
            $trocar = array("<th>","</th>");
            $por    = array("<td>","</td>");
            $topo_repete = str_replace($trocar,$por,$topo_repete);
			echo '			</thead>
						<tbody>';

			$params = (!empty($familia)) ? array("ativo"=>"'t'","familia"=>"$familia"):array("ativo"=>"'t'");
			$familias = $helper->crud->getFamilias($params ) ;
            $conta_topo = 0;
			for ($i=0; $i < pg_num_rows($res); $i++) {
                if($conta_topo == 10){
                    echo $topo_repete;
                    $conta_topo = 0;
                }
                $conta_topo ++;
				$posto  = pg_fetch_result($res,$i,'posto');
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				echo '<tr bgcolor="'.$cor.'">
					<td align="left">&nbsp;
	' . pg_result($res, $i, 'codigo_posto') . ' - ' . pg_result($res, $i, 'descricao_posto') . ' &nbsp;
							</td>
						  ';
				
				$sqlm = "SELECT 
					excecao_mobra,
					mao_de_obra,
				   	adicional_mao_de_obra,
					percentual_mao_de_obra,
					familia, 
					descricao_familia,
					posto
					FROM excecao_$login_fabrica
					WHERE posto = $posto
					ORDER BY descricao_familia";
				$resm = pg_query($con,$sqlm);
				$resultados = pg_fetch_all($resm);
				$familia_anterior = "";
				foreach($familias as $familia){
					$td = montaTd($familia['familia'],$resultados);
					list($id_familia,$td) = explode('||||',$td);
					if($familia['familia'] == $id_familia) echo $td;
					else
						echo	'<td>			
						<input type="hidden" name="excecao_mobra[]" value="'.$id.'" class="excecao_mobra" />
						<input type="hidden" name="familia[]" value="'.$familia['familia'].'" class="familia" />
						<input type="hidden" name="posto[]" value="'.$posto.'" class="posto" />	
						<input type="text" name="mao_de_obra[]" class="frm mao_de_obra" value="" size="5" />

						<input type="hidden" name="mao_de_obra_ant" class="mao_de_obra_ant" value="'.$mo.'" size="5" />
						<input type="hidden" name="ad_mao_de_obra_ant" class="ad_mao_de_obra_ant" value="'.$ad_mo.'" size="5" />
						<input type="hidden" name="pc_mao_de_obra_ant" class="pc_mao_de_obra_ant" value="'.$pc_mo.'" size="5" />

						<input type="text" name="ad_mao_de_obra[]" class="frm mao_de_obra" value="'.$ad_mo.'" size="5" />
						<input type="text" name="pc_mao_de_obra[]" class="frm mao_de_obra" value="'.$pc_mo.'" size="5" /></td>';
						
				}
				echo '</tr>';

			}

			echo '			
						</tbody>
				  	</table>
				  </form>';

		} catch( Exception $e) {

			echo $e->getMessage();

		}

?>

		<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
		<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
		<script type="text/javascript">

			$(function() {
			
				$(".mao_de_obra").numeric( {'allow' : ',.'});

			}); 

		</script>

<?php 

	endif;

	include 'rodape.php'; 

?>