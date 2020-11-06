<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = traduz("CUSTO POR OS - NACIONAIS x IMPORTADOS");

$btn_finalizar = $_POST["btn_finalizar"];

## MESSAGE OF ERROR
$msg_erro = array();
$msgErrorPattern01 = traduz("Preencha os campos obrigatórios.");
$msgErrorPattern02 = traduz("Por favor, verifique as datas.");
$msgErrorPattern03 = traduz("Nenhum resultado encontrado.");

if (strlen($btn_finalizar)>0)
{
	$data_inicial 	  = $_POST["data_inicial_01"];
	$data_final 	  = $_POST["data_final_01"];
	$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
	$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

	if (strlen($_POST["data_inicial_01"]) == 0)
	{
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}

	if (count($msg_erro["msg"]) == 0)
	{
		$data_inicial   = trim($_POST["data_inicial_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen(pg_errormessage($con)) > 0)
		{
			$erro = pg_errormessage ($con) ;

			if(count($msg_erro["msg"])>0)
			{
				$msg_erro["msg"][]    = $msgErrorPattern01;
				$msg_erro["campos"][] = "data";
			}
		}

		if (count($msg_erro["msg"]) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}


	if (strlen($_POST["data_final_01"]) == 0)
	{
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}

	/** Converte data para comparação **/
	/** Tira a barra */
	$d_ini = explode ("/", $data_inicial);
	$d_fim = explode ("/", $data_final);

	/** separa as datas $d[2] = ano $d[1] = mes etc... **/
	$nova_data_inicial 	= "$d_ini[2]-$d_ini[1]-$d_ini[0]";
	$nova_data_final 	= "$d_fim[2]-$d_fim[1]-$d_fim[0]";

	#Verificacao de Datas
	if($nova_data_inicial > $nova_data_final)
	{
		$msg_erro["msg"][]    = $msgErrorPattern02;
		$msg_erro["campos"][] = "data";
	}

	if (count($msg_erro["msg"]) == 0)
	{
		$data_final = trim($_POST["data_final_01"]);
		$fnc        = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

		if (strlen ( pg_errormessage ($con) ) > 0)
		{
			$erro = pg_errormessage ($con) ;
			if(count($msg_erro["msg"]) >0)
			{
				$msg_erro["msg"][]    = $msgErrorPattern01;
				$msg_erro["campos"][] = "data";
			}
		}

		if (count($msg_erro["msg"]) == 0) $aux_data_final = @pg_result ($fnc,0,0);
	}
}

include 'cabecalho_new.php';
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);
include("plugin_loader.php");
?>
<script type="text/javascript" charset="utf-8">
		$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		function formatItem(row) { return row[0] + " - " + row[1];}

		function formatResult(row) { return row[0]; }
	});

 	var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

    function removerAcentos(string) { 
        return string.replace(/[\W\[\] ]/g,function(a) {
            return map[a]||a}) 
    };

    /** select de provincias/estados */
    $(function() {

        <?php if (strlen($_POST['estado']) == 0) { ?> 
                $("#estado").append("<option value=''><?=traduz('TODOS OS ESTADOS');?></option>");
        <?php } ?>

        var post = "<?= $_POST['posto_estado']; ?>";

        var select = "";

        <?php
        $estadosPais = getListaDeEstadosDoPais($pais);
        foreach ($estadosPais as $descEstado) { ?>
            var estado = '<?= $descEstado['descricao'] ?>';
            var sigla = '<?= $descEstado['sigla'] ?>';
                    
            if (post == sigla) {
                select = "selected";
            }

            var option = "<option value='" + sigla + "'" + select +">" + estado + "</option>";
            $("#estado").append(option);

            select = "";
        <?php } ?>

    });

</script>


<center>

	<div class="alert"><h4><?php echo traduz("Este relatório considera a data de aprovação do extrato.");?></h4></div>

	<?php if (count($msg_erro["msg"]) > 0) {	?>
    	<div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
	<?php 	}	?>

	<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
	<div class="row"> <b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios"); ?></b> </div>
	<form name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">

	<div class="titulo_tabela"><?php echo traduz("Parâmetros de Pesquisa"); ?></div>
	<br />
	<div class="container tc_container">

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial")?></label>
					<div class='controls controls-row'>
						<div class='span6'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial_01" id="data_inicial" maxlength="10" class='span12' value="<? if (strlen($data_inicial) > 0) echo $data_inicial;?>"/>
						</div>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?php echo traduz("Data Final");?></label>
					<div class='controls controls-row'>
						<div class='span6'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final_01"   id="data_final"   maxlength="10" class='span12' value="<? if (strlen($data_final) > 0) echo $data_final;?>"/>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='estado'><?php echo traduz("Estado"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select id="estado" name="estado" size="1" class='frm'>

							</select>
						</div>
					</div>
				</div>
			</div>
		</div>

		<br />
		<center>
			<input type="button" class='btn' value="<?php echo traduz("Pesquisar"); ?>" onclick="document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; "  alt='Clique AQUI para pesquisar'>
			<input type='hidden' name='btn_finalizar' value='0'>
		</center>
		<br />

	</div>
</form>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
<?php
flush()	;

if (strlen($btn_finalizar)>0)
{
	if (count($msg_erro["msg"]) == 0)
	{
		$estado 	= $_POST['estado'];
		$condicao_1 = "1=1";

		if (strlen ($estado) > 0) $condicao_1 = "tbl_posto.estado = '$estado'";

		######## Produto Composto - Fujitsu [138] HD 2541097 (01/10/2015)#########
		if (in_array($login_fabrica, array(138))) {
			$sql = "SELECT tbl_linha.nome AS linha, x.origem, x.pecas, x.mao_de_obra, x.qtde
				FROM tbl_linha
				JOIN (SELECT tbl_produto.linha, tbl_produto.origem, SUM(tbl_os.mao_de_obra) AS mao_de_obra, SUM(tbl_os_extra.custo_pecas) AS pecas, COUNT(DISTINCT tbl_os_produto.os) AS qtde
					FROM tbl_os_produto
					JOIN tbl_os USING (os)
					JOIN tbl_os_extra USING (os)
					JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					WHERE  tbl_os.fabrica = $login_fabrica
						AND  tbl_extrato.aprovado BETWEEN '$aux_data_inicial' AND '$aux_data_final'
						AND  $condicao_1
					GROUP BY tbl_produto.linha, tbl_produto.origem) x on x.linha = tbl_linha.linha
					ORDER BY tbl_linha.nome, x.origem";
		} else {

			if($login_fabrica == 163){
				$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
				$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
			}

			$sql = "SELECT tbl_linha.nome AS linha, x.origem, x.pecas, x.mao_de_obra, x.qtde
			          FROM tbl_linha
				      JOIN (	SELECT 	tbl_produto.linha,
				          				tbl_produto.origem,
				             			SUM (tbl_os.mao_de_obra) 		AS mao_de_obra,
										SUM (tbl_os_extra.custo_pecas) 	AS pecas,
										COUNT(tbl_os.os)               	AS qtde
								  FROM  tbl_os
								  JOIN  tbl_os_extra USING (os)
								  JOIN  tbl_produto  USING (produto)
								  JOIN  tbl_extrato  USING (extrato)
								  JOIN  tbl_posto    ON    tbl_os.posto = tbl_posto.posto
								  $join_163
				                 WHERE  tbl_os.fabrica = $login_fabrica
				                   AND  tbl_extrato.aprovado BETWEEN '$aux_data_inicial' AND '$aux_data_final'
				                   AND  $condicao_1
				                   $cond_163
				              GROUP BY  tbl_produto.linha, tbl_produto.origem
			                ) x on x.linha = tbl_linha.linha
			      ORDER BY tbl_linha.nome, x.origem";
		}

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0)
		{
			echo "<br />
			<table class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class='titulo_coluna'>
					<th>". traduz("Linha") ."</th>
					<th>". traduz(" origem") ." 	</th>";
			if(!in_array($login_fabrica, [152, 180, 181, 182])) {
				echo "<th>". traduz(" Peças") ."</th>";
			}

			echo "<th>". traduz("Mão-de-Obra") ." </th>
				  <th>". traduz("Total") ."</th>
			   	  <th>". traduz("Qtde OS") ."</th>
				  <th>". traduz("R$/OS") ."</th>
				 </tr>
			</thead>
			<tbody>";

			$tot_pecas 		 = 0 ;
			$tot_mao_de_obra = 0 ;
			$tot_qtde 		 = 0 ;

			for($i = 0 ; $i < pg_numrows($res) ; $i++)
			{
				$linha		= pg_result ($res,$i,linha);
				$origem		= pg_result ($res,$i,origem);
				$pecas		= pg_result ($res,$i,pecas);
				$mao_de_obra= pg_result ($res,$i,mao_de_obra);
				$qtde		= pg_result ($res,$i,qtde);

				$cor = "#F7F5F0";
				if ($i % 2 == 0) $cor = '#F1F4FA';

				$pecas = round ($pecas,2);
				$mao_de_obra = round ($mao_de_obra,2);

				echo "	<tr>
							<td  bgcolor='$cor' align='left'>	$linha 	</td>
							<td  bgcolor='$cor' align='center'>	$origem </td>";

					if(!in_array($login_fabrica, [152, 180, 181, 182])) {
						echo "	<td  bgcolor='$cor' align='right'>" . number_format ($pecas,2,",",".") . "				</td>";
					}
				echo "
							<td  bgcolor='$cor' align='right'>" . number_format ($mao_de_obra,2,",",".") . " 		</td>
							<td  bgcolor='$cor' align='right'>" . number_format ($pecas + $mao_de_obra,2,",",".") ."</td>
							<td  bgcolor='$cor' align='right'>" . number_format ($qtde,0,",",".") . "				</td>";

							if ($qtde > 0)
							{
								echo "<td  bgcolor='$cor' align='right'>" . number_format (($pecas + $mao_de_obra) / $qtde,2,",",".") . "</td>";
							}
							else
							{
								echo "<td  bgcolor='$cor' align='center'>-</td>";
							}
				echo "</tr>";

				$tot_pecas       += $pecas ;
				$tot_mao_de_obra += $mao_de_obra ;
				$tot_qtde        += $qtde ;
			}


			echo "	</tbody>
					<tfoot>
						<tr>
							<td height='15' colspan='2'>  <b>" . traduz("Totais") . "</b></td>";
					if(!in_array($login_fabrica, [152, 180, 181, 182])) {
							echo "<td height='15' align='right'><b>" . number_format ($tot_pecas,2,",",".") . "					</b></td>";
					}

					echo "<td height='15' align='right'><b>" . number_format ($tot_mao_de_obra,2,",",".") . "				</b></td>
							<td height='15' align='right'><b>" . number_format ($tot_pecas + $tot_mao_de_obra,2,",",".") . "</b></td>
							<td height='15' align='right'><b>" . number_format ($tot_qtde,0,",",".") . "					</b></td>";

							if ($tot_qtde > 0)
							{
								echo "<td align='right'><b>" . number_format (($tot_pecas + $tot_mao_de_obra) / $tot_qtde,2,",",".") . "</b></td>";
							}
							else
							{
								echo "<td align='center'>-</td>";
							}
			echo "		</tr>
					</tfoot>";
		}
		else
		{
			echo "	<tr>
						<td><div class='alert'><h4>".$msgErrorPattern03."</h4></div></td>
					</tr>
					";
		}
		echo "</table>";
	}
}
include "rodape.php"
?>
