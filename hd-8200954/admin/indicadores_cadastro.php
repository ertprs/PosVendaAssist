<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

$nota_corte = $_REQUEST['nota_corte'];
$peso       = $_REQUEST['peso'];
$indicador  = $_REQUEST['indicador'];
$descricao_indicador  = $_REQUEST['descricao_indicador'];
$meta       = $_REQUEST['meta'];

if($_GET['del']==1){
	$sql = "DELETE FROM tbl_indicador WHERE indicador = $indicador";
	$res = pg_query($con,$sql);
}

if($_POST){
	if(empty($nota_corte) or empty($peso) or empty($meta)){
		$msg_erro = 'A Nota de Corte, o Peso e a Meta devem ser Informados';
	}
	if(strlen($msg_erro) == 0){
		if(empty($indicador)){
			$sql = "INSERT INTO tbl_indicador
						 (descricao, nota_corte, peso,fabrica,meta)
						 VALUES('$descricao_indicador',$nota_corte, $peso,$login_fabrica,$meta)";
		}
		else{
			$sql = "UPDATE tbl_indicador SET
			               descricao  = '$descricao_indicador',
						   nota_corte = $nota_corte,
						   peso       = $peso,
						   meta       = $meta
						WHERE indicador = $indicador";
		}
		$res = pg_query($con,$sql);
	}
}

$layout_menu = "cadastro";
$title = "CADASTRO DE INDICADORES PARA MEDIAS DOS POSTOS";

include 'cabecalho.php';

?>
<style type='text/css'>
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}

	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

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
</style>

<form method='post' action=''>
	<input type='hidden' name='indicador' value='<?= $indicador; ?>'>
	<table align='center' width='700' class='formulario'>
		<caption class='titulo_tabela'>Cadastro</caption>
		<tr><td colspan='4'>&nbsp;</td></tr>
		<tr>
			<td width='80'>&nbsp;</td>
			<td width='300'>
				Indicador <br />
				<select name='descricao_indicador' class='frm'>
					<option value='Prazo médio de atendimento (1 mês)' <? if($descricao_indicador == 'Prazo médio de atendimento (1 mês)') echo 'SELECTED';?>>Prazo médio de atendimento (1 mês)</option>
					<option value='Reincidência' <? if($descricao_indicador == 'Reincidência') echo 'SELECTED';?>>Reincidência</option>
					<option value='Reclamações' <? if($descricao_indicador == 'Reclamações') echo 'SELECTED';?>>Reclamações</option>
				</select>
			</td>
			<td width='100'>
				Nota de Corte <br />
				<input type='text' name='nota_corte'class='frm' size='8' value='<?= $nota_corte; ?>'>
			</td>
			<td>
				Peso <br />
				<input type='text' name='peso' class='frm' size='5' value='<?= $peso; ?>'>
			</td>
			<td>
				Meta <br />
				<input type='text' name='meta' class='frm' size='5' value='<?= $meta; ?>'>
			</td>
		</tr>
		<tr><td colspan='4'>&nbsp;</td></tr>
		<tr>
			<td colspan='5' align='center'>
				<input type='submit' value='Gravar'>
				<input type='button' value='Limpar' onclick="window.location='<?= $PHP_SELF; ?>'">
				<!-- <input type='button' value='Apagar' onclick="window.location='<?= $PHP_SELF; ?>?del=1&indicador=<?= $indicador; ?>'"> -->
			</td>
		</tr>
		<tr><td colspan='4'>&nbsp;</td></tr>
	</table>
</form>

<br />

<?
	$sql = "SELECT indicador,descricao, nota_corte, peso,meta FROM tbl_indicador ORDER BY peso";
	$res = pg_query($con,$sql);
	
	if(pg_numrows($res) > 0){ ?>
		<table width='700' align='center' cellspacing='1' class='tabela'>
			<tr class='titulo_coluna'>
				<th>Indicador</th>
				<th>Nota de Corte</th>
				<th>Peso da Nota</th>
				<th>Meta</th>
			</tr>
	<?
		for($i = 0; $i<pg_numrows($res); $i++){
			$indicador  = pg_result($res,$i,indicador);
			$descricao  = pg_result($res,$i,descricao);
			$nota_corte = pg_result($res,$i,nota_corte);
			$peso       = pg_result($res,$i,peso);
			$meta       = pg_result($res,$i,meta);

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		?>
			<tr bgcolor='<? echo $cor; ?>'>
				<td><a href='?indicador=<?= $indicador; ?>&descricao_indicador=<?= $descricao; ?>&nota_corte=<?= $nota_corte; ?>&peso=<?= $peso; ?>&meta=<?= $meta; ?>'><? echo $descricao; ?></a></td>
				<td><? echo $nota_corte; ?></td>
				<td><? echo $peso; ?></td>
				<td><? echo $meta; ?></td>
			</tr>
		<?
		}
		?>
		</table>
		<?
	}
	
	include 'rodape.php';
?>