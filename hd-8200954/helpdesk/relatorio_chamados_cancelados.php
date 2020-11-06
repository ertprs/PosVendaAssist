<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';

	if($_POST){
		$data_inicial = $_POST['data_inicial'];
		$data_final   = $_POST['data_final'];
		$fabrica      = $_POST['fabrica'];
		$hd_chamado   = $_POST['hd_chamado'];
		$check   = $_POST['check'];
		
		if(empty($check)){
			if(!empty($hd_chamado)){
				$cond = " AND tbl_hd_chamado.hd_chamado = $hd_chamado ";
			}
			
			if(empty($hd_chamado)){
				if(!empty($data_inicial) OR !empty($data_final)){
					
					list($di, $mi, $yi) = explode("/", $data_inicial);
					if(!checkdate($mi,$di,$yi)){ 
						$msg_erro = "Data inicial inválida <br>";
					}

					list($df, $mf, $yf) = explode("/", $data_final);
					if(!checkdate($mf,$df,$yf)){
						$msg_erro .= "Data final inválida <br>";
					}

					if(strlen($msg_erro)==0){
						$aux_data_inicial = "$yi-$mi-$di";
						$aux_data_final = "$yf-$mf-$df";

						if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
							$msg_erro .= "Data inicial maior do que a data final";
						}

						if( strtotime($aux_data_final) > strtotime('today') AND strlen($msg_erro)==0 ){
							$msg_erro .= "Data final não pode ser maior do que a data de hoje";
						}
					}

					if(strlen($msg_erro)==0){
						$cond1 = " AND data BETWEEN '$aux_data_inicial' and '$aux_data_final' ";
					}
				}
			}

			if(!empty($fabrica)){
				$cond2 = " AND tbl_hd_chamado.fabrica = $fabrica";
			}
		}

	}

	
include "menu.php";

?>
<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

</style>
<link type="text/css" rel="stylesheet" href="../plugins/jquery/datepick/telecontrol.datepick.css" /><script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="../js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script language="JavaScript">


	$(document).ready(function() {
		
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

	});
</script>

<form name="frm_consulta" method="post">
	<table width="700" align="center" class="formulario" border = "0">
		<caption class="titulo_tabela">Parâmetros de Pesquisa</caption>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td width="100">&nbsp;</td>
			<td>
				Data ínico (abertura do chamado)<br>
				<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<?php echo $data_inicial; ?>">
			</td>
			<td>
				Data final (abertura do chamado)<br>
				<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<?php echo $data_final; ?>">
			</td>
		</tr>

		<tr><td colspan="3">&nbsp;</td></tr>

		<tr>
			<td width="100">&nbsp;</td>
			<td>
				Fábrica<br>
				<select name="fabrica">
					<option value="">Selecione</option>
					<?php
						$sql1 = "SELECT fabrica,nome FROM tbl_fabrica WHERE ativo_fabrica IS TRUE ORDER BY nome";
						$res1 = pg_query($con,$sql1);

						for($i = 0; $i < pg_num_rows($res1); $i++){
							$fabrica_aux = pg_result($res1,$i,'fabrica');
							$nome    = pg_result($res1,$i,'nome');

							$selected = ($fabrica_aux == $fabrica) ? "SELECTED" : "";
						?>
							<option value="<?php echo $fabrica_aux; ?>" <?php echo $selected; ?>><?php echo $nome; ?></option>
						<?php
						}
					?>
				</select>
			</td>
		
			<td>
				Nº do chamado <br>
				<input type="text" name="hd_chamado" value="<?php echo $hd_chamado;?>">
			</td>
		</tr>

		<tr><td colspan="3">&nbsp;</td></tr>

		<tr>
			<td width="100">&nbsp;</td>
			<td colspan="2" align="left">
				<input type="checkbox" name="check" value="1"  <?php echo (!empty($check)) ? "checked" : ""; ?>>
				Cancelados à mais de 1 mês 
			</td>
		</tr>

		<tr><td colspan="3">&nbsp;</td></tr>

		<tr>
			<td colspan="3" align="center">
				<input type="submit" value="Pesquisar">
			</td>
		</tr>

		<tr><td colspan="3">&nbsp;</td></tr>
	</table>
</form>
<br><br>

	<?php
		
		$cond3 = " AND tbl_hd_chamado.fabrica <> 0 AND tbl_hd_chamado.data::date >= '2012-04-09'";

		if(!empty($check)){
			$cond3 = " AND tbl_hd_chamado.fabrica = 0 AND tbl_hd_chamado.data::date >= '2012-04-09'";
		}

		$sql = "SELECT	tbl_hd_chamado.hd_chamado,
					TO_CHAR(tbl_hd_chamado.data::date,'DD/MM/YYYY') AS data,
					tbl_hd_chamado.titulo,
					tbl_fabrica.nome AS fabrica,
					tbl_admin.login AS admin,
					AT.login AS atendente,
					tbl_hd_chamado.hora_desenvolvimento,
					tbl_tipo_chamado.descricao AS tipo_chamado
				FROM tbl_hd_chamado
				JOIN tbl_tipo_chamado ON tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
				JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
				JOIN tbl_admin AT ON tbl_hd_chamado.atendente = AT.admin
				JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
				WHERE status = 'Cancelado'
				AND   tbl_hd_chamado.data::date >= '2012-04-09'
				$cond
				$cond1
				$cond2
				$cond3
				AND (
					(SELECT data::date + interval '1 month' 
					FROM tbl_hd_chamado_item 
					WHERE hd_chamado = tbl_hd_chamado.hd_chamado 
					ORDER BY data DESC limit 1) >= CURRENT_DATE
					)
				ORDER BY tbl_fabrica.nome,tbl_hd_chamado.data";
	
	$res = pg_query($con,$sql);
	#echo nl2br($sql);

		if(pg_num_rows($res) > 0){
	?>
			<table align="center" class="tabela">
				<caption class="titulo_tabela">Relatório de Chamados Cancelados</caption>
				<tr class="titulo_coluna">
					<th>Chamado</th>
					<th>Título</th>
					<th>Tipo</th>
					<th>Data Abertura</th>
					<th>Fábrica</th>
					<th>Autor</th>
					<th>Atendente</th>
					<th>Horas cobradas</th>
				</tr>
	<?php
			for($i = 0; $i < pg_num_rows($res); $i++){

				$chamado              = pg_result($res,$i,'hd_chamado');
				$data                 = pg_result($res,$i,'data');
				$titulo               = pg_result($res,$i,'titulo');
				$fabrica              = pg_result($res,$i,'fabrica');
				$admin                = pg_result($res,$i,'admin');
				$atendente            = pg_result($res,$i,'atendente');
				$hora_desenvolvimento = pg_result($res,$i,'hora_desenvolvimento');
				$tipo_chamado         = pg_result($res,$i,'tipo_chamado');

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	?>
				<tr bgcolor="<?php echo $cor;?>">
					<?php if(empty($check)){ ?>
						<td><a href="adm_chamado_detalhe.php?hd_chamado=<?php echo $chamado; ?>" target="_blank"><?php echo $chamado; ?></a></td>
					<?php }else{ ?>
						<td><?php echo $chamado; ?></td>
					<?php } ?>
					<td><?php echo $titulo; ?></td>
					<td><?php echo $tipo_chamado; ?></td>
					<td><?php echo $data; ?></td>
					<td><?php echo $fabrica; ?></td>
					<td><?php echo $admin; ?></td>
					<td><?php echo $atendente; ?></td>
					<td align="center"><?php echo $hora_desenvolvimento; ?></td>
				</tr>
	<?php
			}
		} 
	?>

</table>
<br>
<center>Total de registros : <?php echo pg_num_rows($res);?></center>

<?php include "rodape.php"; ?>