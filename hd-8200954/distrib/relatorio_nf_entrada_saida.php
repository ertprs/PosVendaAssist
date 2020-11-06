<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


$data_inicial = $_POST['data_inicial'];
$data_final = $_POST['data_final'];
$fabrica = $_POST['fabrica'];

if($_POST['ajax'] == 'faturamento') {
			$tipo = $_POST['tipo'];
			$peca = $_POST['peca'];

			if($tipo == 'g') {
					$cond = " AND (tbl_faturamento.cfop in ('5949','6949') or natureza ~* 'garantia') ";
					$tipo_des = 'GARANTIA';
			}else{
				$cond = " AND (tbl_faturamento.cfop not in ('5949','6949') or natureza !~* 'garantia') ";
				$tipo_des = 'FATURADA';
			}
			$sql = "SELECT	tbl_faturamento.nota_fiscal,
							to_char(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao,
							tbl_faturamento_item.qtde
					FROM tbl_faturamento
					JOIN tbl_faturamento_item USING(faturamento)
					WHERE tbl_faturamento_item.peca = $peca
					AND   tbl_faturamento.fabrica = 10
					AND distribuidor = 4311
					AND tbl_faturamento.cancelada IS NULL
					AND   tbl_faturamento.status_nfe='100'
					$cond";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) >0) {
				$resultado .= "<tr><td colspan='100%'>
						<table align='center' width='600'  border='0' cellspacing='1' cellpadding='1' id='table_$peca".$tipo."'>
						<tr bgcolor='#08088A' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
								<td>Nota Fiscal $tipo_des</td>
								<td>Emissão</td>
								<td>Qtde</td>
								</tr>
								";
				for($x = 0; $x < pg_numrows($res);$x++) {

					$nota_fiscal  = pg_fetch_result($res,$x,'nota_fiscal');
					$emissao 	     = pg_fetch_result($res,$x,'emissao');
					$qtde     	 = pg_fetch_result($res,$x,'qtde');
					$descricao 	 = pg_fetch_result($res,$x,'descricao');
					$resultado .= "<tr><td align='center'>$nota_fiscal</td>";
					$resultado .= "<td align='center'>$emissao</td>";
					$resultado .= "<td align='right'>$qtde</td></tr>";
				}
				$resultado.="</table></td></tr>";
				echo $resultado;
			}
			exit;
	}


$title = 'Relatório de NF de saída';

?>

<html>
<head>
<title><?echo $title;?></title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<?
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script>
$(document).ready(function()
    {
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
});

function mostraFaturamento(tipo,peca){
	if($('#table_'+peca+""+tipo).length >0) {
		$('#table_'+peca+""+tipo).toggle();
	}else{

		$.ajax({
			url: '<?$PHP_SELF?>',
			cache: false,
			type: "POST",
			data:{
				tipo: tipo,
				peca : peca, 
				ajax : 'faturamento'
			},
			complete: function(retorno){
				$('#'+peca).after(retorno.responseText);
			}
		});
	}
}


</script>
<style>
.qtde{
	text-align: center;

}
</style>
<body>

<? include 'menu.php' ;

?>
		<center><h1><?echo $title;?></h1></center>

<p>
<?
		if (strlen($msg_erro) > 0) {
			echo "<div style='border: 1px solid #DD0000; background-color: #FFDDDD; color: #DD0000; font-size: 11pt; margin-bottom: 10px; padding: 5px;'>$msg_erro</div><p>";
		}

?>
	<center>
	<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='POST'>
	<table>

		<tr>
			<td align='right'>Data Inicial</td>
			<td><input type='text' size='11' name='data_inicial' id='data_inicial' class="frm" value="<? echo $_REQUEST["data_inicial"]; ?>"></td>
			<td align='right'>Data Final</td>
			<td><input type='text' size='11' name='data_final'   id='data_final' class="frm"  value="<? echo $_REQUEST["data_final"]; ?>"></td>
					<td align='right'>Fábrica</td>
			<td align='left'>
			<?
			echo "<select style='width:120px;' name='fabrica' id='fabrica' class='frm'>";
				$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN ($telecontrol_distrib) ORDER BY nome";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					for($x = 0; $x < pg_numrows($res);$x++) {
						$aux_fabrica = pg_fetch_result($res,$x,fabrica);
						$aux_nome    = pg_fetch_result($res,$x,nome);
						echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
					}
				}
			echo "</select>";
			?>
			</td>
		</tr>
		<tr>
			<td align='center' colspan='6'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		</tr>
	</table>
	<br/>
<?
if(!empty($btn_acao) and empty($msg_erro) ) {
		if(!empty($data_inicial) and !empty($data_final)){
			$cond = " AND tbl_faturamento.emissao between '$data_inicial' and '$data_final' ";  
		}

		$sql = "SELECT distinct peca,referencia, descricao
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				JOIN tbl_peca using(peca)
				WHERE tbl_faturamento.fabrica = 10 
				AND tbl_faturamento.status_nfe='100'
				AND tbl_peca.fabrica = $fabrica
				and tbl_peca.produto_acabado is not true
				$cond ";
		$res = pg_exec ($con,$sql);
		if(pg_numrows($res) > 0 ) {
			$dados_xls ="<table align='center' border='0' cellspacing='1' cellpadding='1'>";
			$dados_xls .="<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			$dados_xls .="<td>Peça</td>";
			$dados_xls .="<td>Qtde Total Saída </td>";
			$dados_xls .="<td>Média</td>";
			$dados_xls .="<td>Qtde Estoque</td>";
			$dados_xls .="</tr>";


			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					$peca = pg_fetch_result($res,$i,0);
					$referencia = pg_fetch_result($res,$i,1);
					$descricao = pg_fetch_result($res,$i,2);
					$sql = "drop table if exists tmp_peca ; 

							SELECT  tbl_faturamento_item.qtde,tbl_faturamento_item.preco, emissao,tbl_posto_estoque.qtde as qtde_estoque
							INTO TEMP tmp_peca
							FROM tbl_faturamento
								JOIN tbl_faturamento_item using (faturamento)
								JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
								JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
								join tbl_posto_estoque on tbl_peca.peca = tbl_posto_estoque.peca 
								WHERE tbl_faturamento.distribuidor in(4311,376542)
								AND tbl_faturamento.cancelada IS NULL
								AND tbl_faturamento.fabrica <> 0
								AND tbl_faturamento.status_nfe='100'
								AND tbl_fabrica.fabrica = $fabrica
								AND tbl_faturamento_item.peca = $peca ;

								SELECT sum(qtde), round(avg(qtde)::numeric,2),qtde_estoque FROM tmp_peca group by qtde_estoque ";
								$ress = pg_exec ($con,$sql);

						$cor = "cccccc";
						if ($i % 2 == 0) $cor = '#eeeeee';
						$qtde = pg_fetch_result($ress,0,0);
						$media = pg_fetch_result($ress,0,1);
						$estoque = pg_fetch_result($ress,0,2);

						$dados_xls .="<tr bgcolor='$cor' style='font-size:11px' id='$peca'>";

						$dados_xls .="<td>";
						$dados_xls .="$referencia - $descricao";
						$dados_xls .="</td>";

						$dados_xls .="<td class='qtde' id='$peca-g'>";
						$dados_xls .=$qtde ;
						$dados_xls .="</td>";


						$dados_xls .="<td>";
						$dados_xls .= $media;
						$dados_xls .="</td>";

						$dados_xls .="<td class='qtde' id='$peca-f'>";
						$dados_xls .=$estoque;
						$dados_xls .="</td>";


						$dados_xls .="</tr>";
			}
			$dados_xls .= "</table>";
			echo $dados_xls;
			$link_xls = "xls/relatorio_saida_" . date("d-m-y") . '.xls';
				if (file_exists($link_xls))
					exec("rm -f $link_xls");
				if ( is_writable("xls/") ) 
					$file = fopen($link_xls, 'a+');
				else
					echo 'Sem Permissão de escrita';

				fwrite($file,$dados_xls);
				fclose($file);
				if ( isset ($file) && !empty($dados_xls) ) {
						echo "<br/><button class='download' onclick=\"window.open('$link_xls') \">Download XLS</button>";
				}
		}
}
 include "rodape.php"; ?>

</body>
</html>
