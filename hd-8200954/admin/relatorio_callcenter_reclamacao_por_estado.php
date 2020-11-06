<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$msg_erro = array();
$msgErrorPattern01 = traduz("Preencha os campos obrigatórios.");

if($_GET['ano'])	$ano  = $_GET['ano'];
if($_POST['ano'])	$ano = $_POST['ano'];

if($_GET['ano'] == 'escolha' || $_POST['ano'] == 'escolha')
{
	$msg_erro["msg"][]    = $msgErrorPattern01;
	$msg_erro["campos"][] = "data";
	$showMsg = 1;
}

$layout_menu = "callcenter";
$title = traduz("CALL-CENTER - RELATÓRIO DE RECLAMAÇÃO POR ESTADO");

include 'cabecalho_new.php';
?>
<!--Mensagem de erro-->
<?php if (count($msg_erro["msg"]) > 0) {	?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php 	}	?>

<!--Mensagem de sucesso-->
<?php if (strlen($msg_sucesso) > 0) { ?>
	<p> <div class="alert alert-success"><h4><?php echo $msg_sucesso; ?></h4></div>	</p>
<?php } ?>

<div class="row"> <b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios");?></b></div>
<form name='frm_relatorio' action='<? echo $PHP_SELF ?>' class="form-search form-inline tc_formulario">
<div class="titulo_tabela"><?php echo traduz("Parâmetros de Pesquisa");?></div>
<br />
<div class="container tc_container">
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4">
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class="controls controls-row">
					<div class="span7 input-append">	
						<label class="control-label" for="tabela"><?php echo traduz("Selecione o Ano");?> &nbsp;&nbsp;&nbsp;&nbsp; </label>
						<h5 class='asteristico'>*</h5>
						<select name="ano" size="1" class="frm">
							<option value='escolha'><?php echo traduz("Escolha");?></option>";
							<?php 	
								for ($i = 2003 ; $i <= date("Y") ; $i++) 
								{
									echo "<option value='$i'";
										if ($ano == $i) echo " selected";
									echo ">$i</option>";
								}
							?>
						</select>	
					</div>
				</div>
			</div>	
		</div>
	</div>
</div>	
<center>
	<button type="button" class="btn" onclick='javascript: document.frm_relatorio.submit();'  id="btn_acao" ><?php echo traduz("Pesquisar");?></button>	
	<input type="hidden" name="acao">
</center>
<br />
</form>

<br />

<?php 
	if (strlen($ano) > 0 && $showMsg != 1) 
	{
		echo "	<table class='table table-striped table-bordered table-hover table-large' align='center'width='700'>";
		
		$nomemes = array(1=> "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ");
		$mes 	 = 12; // até dezembro
		$nomeuf  = array(1=>"AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PE","PI","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO");
		$uf 	 = 27; // numero de estados
		
		if ($login_fabrica == 180) {
			// Argentina

			$nomeuf = getProvinciasExterior("AR");

			$uf = count($nomeuf) - 1;
		}

		if ($login_fabrica == 181) {
			//Colombia
			$nomeuf = getProvinciasExterior("CO");

            $uf = count($nomeuf) - 1;

		}

		if ($login_fabrica == 182) {
			//Peru
			$nomeuf = getProvinciasExterior("PE");
			
	        $uf = count($nomeuf) - 1;

		}

		echo "	<thead>
				<tr class='titulo_coluna' >
					<th>UF</th>";
					for($i=1; $i <= 12; $i++){	echo "<th>$nomemes[$i]</th>";	}
		echo "	</tr>
				</thead>";
		
		for($i=1; $i <= $uf; $i++)
		{
			$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";
			
			echo "	
				<tbody>
				<tr class='table_line' bgcolor='$cor'>
					<td>$nomeuf[$i]</td>";
			
					$estado = $nomeuf[$i];
					for ($x=1; $x <= 12; $x++)
					{
						$data_inicial 	= "$ano-$x-1";
						$data_final 	= "$ano-$x-".date("d",mktime(0, 0, 0, $x+1, 1, $ano)-1);
						
						$sql = "Select count (hd_chamado)as TOTAL
								  from tbl_hd_chamado
								  join tbl_hd_chamado_extra using(hd_chamado)
								  join tbl_cidade using (cidade)
								 where data between '$data_inicial 00:00:00' AND '$data_final 23:59:59'
								   and fabrica_responsavel = $login_fabrica
								   and estado = '$estado'
								   and categoria ~* 'reclamacao'
							  group by estado
							  order by estado";

						$res2 				= @pg_exec($con,$sql);
						$total_ocorrencias  = @pg_result($res2,0,TOTAL);

						echo "	<td align='right'>";
									if (strlen($total_ocorrencias ) > 0)
									{
										$data_inicial_pesquisa 		= array_reverse(explode("-", $data_inicial));
										$data_inicial_pesquisa[0] 	= substr("0" . $data_inicial_pesquisa[0], -2);
										$data_inicial_pesquisa[1] 	= substr("0" . $data_inicial_pesquisa[1], -2);
										$data_inicial_pesquisa 		= implode("/", $data_inicial_pesquisa);

										$data_final_pesquisa 	= array_reverse(explode("-", $data_final));
										$data_final_pesquisa[0] = substr("0" . $data_final_pesquisa[0], -2);
										$data_final_pesquisa[1] = substr("0" . $data_final_pesquisa[1], -2);
										$data_final_pesquisa 	= implode("/", $data_final_pesquisa);
										echo "<a href='callcenter_consulta_lite_interativo.php?data_inicial=$data_inicial_pesquisa&data_final=$data_final_pesquisa&consumidor_estado=$estado&chk_opt24=1&chk_opt99=1' target='_blank' title='". traduz("Clique aqui para ver os chamados deste MÊS X ESTADO") . "'>" . round($total_ocorrencias) . "</a>";
									}
						echo "</td>";
					}
			echo "
				</tbody>
				</tr>";
				}
	echo "</table>";
}
include "rodape.php"; 
?>
