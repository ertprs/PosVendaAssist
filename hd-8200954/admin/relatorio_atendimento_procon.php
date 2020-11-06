<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'funcoes.php';
include "autentica_admin.php";
$layout_menu = "callcenter";

$title = "RELATÓRIO DE ATENDIMENTOS PROCON";
include "cabecalho.php";

?>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<?php include "../javascript_calendario.php";?>

<script type="text/javascript">
	$().ready(function(){
		//$( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
		$( "#data_inicial" ).maskedinput("99/99/9999");
		//$( "#data_final" ).datePicker({startDate : "01/01/2000"});
		$( "#data_final" ).maskedinput("99/99/9999");
	});
</script>
<style type="text/css">
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial" !important;
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
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

<?

if ($_POST['gerar']){

	$atendimento	= $_POST['atendimento'];
	$cidade			= $_POST['cidade'];
	$estado 		= $_POST['estado'];
	$data_inicial	= $_POST['data_inicial'];
	$data_final		= $_POST['data_final'];
	
	
	if( (empty($data_inicial) OR empty($data_final)) and empty($atendimento) )
		$msg_erro = "Data Inválida";
	
	
	if(strlen($msg_erro)==0 and !empty($data_inicial) and !empty($data_final)){
	
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if(!checkdate($mi,$di,$yi)) 
			$msg_erro = "Data Inválida";
	
	}
	
	
	if(strlen($msg_erro)==0 and !empty($data_inicial) and !empty($data_final)){
	
		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf)) 
			$msg_erro = "Data Inválida";
	
	}
	
	
	if(strlen($msg_erro)==0 and !empty($data_inicial) and !empty($data_final)){
	
		$aux_data_inicial = "$yi-$mi-$di";
		$aux_data_final = "$yf-$mf-$df";
	
	}


	if(strlen($msg_erro)==0 and !empty($data_inicial) and !empty($data_final))
		if(strtotime($aux_data_final) < strtotime($aux_data_inicial))
			$msg_erro = "Data Inválida.";

	if(strlen($msg_erro)==0 and !empty($data_inicial) and !empty($data_final))
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -3 month')) 
			$msg_erro = 'O intervalo entre as datas não pode ser maior que 90 dias.';

}?>

<table class="texto_avulso" width="700px" align="center">
	<tr>
		<td>
		A data de pesquisa não poderá ter um intervalo maior que 90 dias
		</td>
	</tr>
</table>
<br>
<?
if ($msg_erro){?>
	<table class="msg_erro" align="center" width="700px">
		<tr>
			<td> <?echo $msg_erro?></td>
		</tr>
	</table>
<?
}
?>

<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST">

	<table cellspacing="0" cellpadding="1" align="center" class='formulario' width="700px">
		
		<tr>
		
			<td class="titulo_tabela"> Parâmetros de Pesquisa </td>
			
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
		
			<td>
			
				<table width="500px" align="center">
				
					<tr>
						<td colspan="2">Atendimento</td>
					</tr>
					
					<tr>
						<td colspan="2">
							<input type="text" name="atendimento" id="atendimento" class='frm' value="<?=$atendimento?>"/>
						</td>
					</tr>
					
					<tr>
					
						<td>
						
							<label for="data_inicial">Data Inicial</label>
						
						</td>
						
						<td>
						
							<label for="data_final">Data Final</label><br />
						
						</td>
					</tr>
					<tr>
						<td>

							<input type="text" name="data_inicial" id="data_inicial" class="frm" size="12" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
						
						</td>
						
						<td>
							
							<input type="text" name="data_final" id="data_final" class="frm" size="12" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
						
						</td>
					</tr>
					
					<tr>
						
						<td>
							<label for="cidade">Cidade</label><br />
						</td>
						
						<td>
							<label for="estado">Estado</label><br />
						</td>
						
					</tr>
					
					<tr>
						
						<td>
							<input type="hidden" name="cidade" id="cidade" />
							<input type="text" name="cidade_nome" id="cidade_nome" value="<?=$cidade?>" class="frm" />
						</td>
						
						<td>
							
							<select name="estado" id='estado' class='frm' style='font-size:11px'>
							<option value="">Todos</option>
							<? $ArrayEstados = array('','AC','AL','AM','AP',
														'BA','CE','DF','ES',
														'GO','MA','MG','MS',
														'MT','PA','PB','PE',
														'PI','PR','RJ','RN',
														'RO','RR','RS','SC',
														'SE','SP','TO'
													);
								for ($i=0; $i<=27; $i++){
									echo"<option value='".$ArrayEstados[$i]."'";
									if ($estado == $ArrayEstados[$i]) echo " selected='selected' ";
									echo ">".$ArrayEstados[$i]."</option>\n";
								}
							?>
							</select>
							
						</td>
						
					</tr>
					
					<tr>
						<td>&nbsp;</td>
					</tr>
					
					<tr>
					
						<td colspan="3" style="padding-top:5px; text-align:center;">
						
							<input type="submit" name="gerar" value="Pesquisar" />
							
						</td>
						
					</tr>
					
				</table>
				
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
	</table>
	
</form>

<br /><br />
<!-- resultado da requisição -->
<?php 

if ( isset($_POST['gerar']) and empty($msg_erro) ) {

	$cond_data 			= (!empty($data_inicial) and !empty($data_final)) ? " AND tbl_hd_chamado.data between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' " : ' ' ;
	$cond_estado		= (!empty($estado)) ? " AND tbl_cidade.estado = '$estado' " : ' ' ;
	$cond_atendimento	= (!empty($atendimento)) ? " AND tbl_hd_chamado.hd_chamado = $atendimento " : ' ' ;
	
	$order_estado = (empty($estado)) ? ' , tbl_cidade.estado ' : ' ';
	
	$sqlA = "
		
		SELECT  tbl_hd_chamado.hd_chamado,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
				tbl_cidade.nome as cidade,
				tbl_cidade.estado,
				tbl_hd_chamado_extra.nome
		
		FROM tbl_hd_chamado
		
		JOIN tbl_hd_chamado_extra on (tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado) 
		JOIN tbl_cidade on (tbl_hd_chamado_extra.cidade = tbl_cidade.cidade) 
		
		WHERE tbl_hd_chamado.fabrica=$login_fabrica 
		AND tbl_hd_chamado.categoria = 'procon' 
		$cond_data 
		$cond_estado 
		$cond_atendimento 
		
		
		ORDER BY  tbl_hd_chamado.data $order_estado
	";
	$resA = pg_query($con,$sqlA);
	
	if (empty($msg_erro) and pg_num_rows($resA)>0){
	?>
		<table class='tabela' width="700px" cellpadding='2' cellspacing='1' align="center">
			<tr>
				<td colspan="5" class='titulo_tabela'>Resultado da pesquisa</td>
			</tr>
			<tr class='titulo_coluna'>
				<td>Atendimento</td>
				<td>Data</td>
				<td>Consumidor</td>
				<td>Cidade</td>
				<td>Estado</td>
			</tr>
			
		<?php
		for ($i = 0; $i < pg_num_rows($resA); $i++)
		{
			
			$xhd_chamado = pg_result($resA,$i,'hd_chamado');
			$xdata		 = pg_result($resA,$i,'data');
			$xcidade	 = pg_result($resA,$i,'cidade');
			$xestado	 = pg_result($resA,$i,'estado');
			$xnome 		 = pg_result($resA,$i,'nome');
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		?>	
			<tr bgcolor="<?=$cor?>">
				<td>
					<a target="_blank" href="callcenter_interativo_new.php?callcenter=<?=$xhd_chamado?>">
						<?php echo $xhd_chamado;?>
					</a>
				</td>
				<td><?=$xdata?></td>
				<td><?=$xnome?></td>
				<td><?=$xcidade?></td>
				<td><?=$xestado?></td>
			</tr>
		<?
		}
			
		?>
			
		</table>
	
	<?
	}else if (pg_num_rows($resA)==0){
	?>
		<center>Nenhum resultado encontrado</center>
	<?
	}
	

}

include 'rodape.php';

?>
