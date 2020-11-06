<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$btn_acao = $_POST['btn_acao'];

$erro = "";

if(!empty($_REQUEST['ajax'])) {
	$tipo_pedido = $_POST['tipo_pedido'];
	$mes = $_POST['mes'];
	$ano = $_POST['ano'];
	if(strlen($mes) == 1) $mes = "0".$mes;
	$data_inicial = date("$ano-$mes-01");
	$data_final = date('Y-m-t', strtotime($data_inicial)); 

	$sql = "SELECT descricao FROM tbl_tipo_pedido WHERE tipo_pedido = $tipo_pedido";
	$res = pg_query($con,$sql);
	$tp_descricao = pg_fetch_result($res,0,0);

	$sql = "
		SELECT tbl_pedido.pedido
		INTO TEMP tmp_rgf_$login_admin
		FROM tbl_pedido
		WHERE tbl_pedido.fabrica = $login_fabrica 
		AND   tbl_pedido.status_pedido <> 14
		AND   tbl_pedido.tipo_pedido = $tipo_pedido
		AND tbl_pedido.data between '$data_inicial 00:00:00' and '$data_final 23:59:59';


		CREATE INDEX tmp_rgf_pedido_$login_admin ON tmp_rgf_$login_admin(pedido);

		SELECT  		x.descricao, 
					x.referencia,
					x.qtde, 
					x.valor 
			FROM (
				SELECT  tbl_peca.referencia,
					tbl_peca.descricao,
					SUM(tbl_pedido_item.qtde) as qtde, 
					ROUND (SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco)::numeric,2) as valor
			FROM  tbl_pedido_item
			JOIN  tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido 
			JOIN  tbl_peca USING(peca)
			JOIN  tmp_rgf_$login_admin P ON P.pedido = tbl_pedido.pedido
			WHERE tbl_pedido.fabrica = $login_fabrica 
			AND   tbl_pedido.status_pedido <> 14
			AND   tbl_pedido.tipo_pedido = $tipo_pedido
			AND tbl_pedido.data between '$data_inicial 00:00:00' and '$data_final 23:59:59'
			group by tbl_peca.referencia, tbl_peca.descricao
			) x
			ORDER BY  
			x.descricao, 
			x.referencia 
			" ;
	$res = pg_query($con,$sql);

	if (pg_num_rows($res)>0){
		$resultado.= "<table id='resultado' class='table table-striped table-bordered table-hover table-large' style='margin: 0 auto;' >";

		$resultado.= "<thead >"	;
		$resultado.= "<tr class='titulo_coluna'><td colspan='100%'>Tipo pedido: $tp_descricao</td></tr>";
		$resultado.= "<tr class='titulo_coluna' style='text-align:center'>";
		$resultado.= "<td>PEÇA</td>\n";
		$resultado.= "<td>Qtde</td>\n";
		$resultado.= "<td>Valor</td>\n";
		$resultado.= "</tr>";
		$resultado.= "</thead>";

		$resultado.= "<tbody>";
		$total_linhas = pg_numrows($res);
		for ($x = 0; $x < $total_linhas; $x++) {
			$referencia = trim(pg_result($res,$x,'referencia'));
			$descricao = trim(pg_result($res,$x,descricao));
			$qtde      = trim(pg_result($res,$x,qtde));
			$valor     = trim(pg_result($res,$x,valor));

			$resultado.= "<tr'>";
			$resultado.= "<td>$referencia - $descricao</td>";
			$resultado.= "<td style='text-align:center'>$qtde</td>";
			$resultado.= "<td style='text-align:right' >".number_format($valor,2,',','.')."</td>";
			$resultado.= "</tr>";
			$total_qtde += $qtde;
			$total_valor += $valor;

		}//fecha o segundo for
		$resultado.= "</tbody>";
		$resultado.="<tfoot><tr class='titulo_coluna'><td>Total</td>";
		$resultado.="<td>$total_qtde</td>";
		$resultado.="<td style='text-align:right'>".number_format($total_valor,2,',','.')."</td>";
		$resultado.= "</tr></tfoot></table>"	;
		echo "ok|".$resultado;
	}else{
		echo "erro|erro";
	}
	exit;
}

if(isset($btn_acao)) {
	$mes = $_POST['mes'];
	$ano = $_POST['ano'];
	if(empty($mes) or empty($ano)) {
		$erro = "Favor informar os dados para pesquisa";
	}
}
$meses= array();
for($i=1;$i<13;$i++){
	$meses[$i]=$i;
}
$anos = array();
$ano_atual =  date('Y');
for($i =$ano_atual;$i>=$ano_atual-5;$i--) {
	$anos[$i]=$i;
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PEÇAS FATURADAS E GARANTIA ";

include "cabecalho_new.php";
$form = array(
	"mes" => array(
		"span"      => 4,
		"label"     => "Mês",
		"type"      => "select",
		"width"     => 5,
		"required"  => true,
		"options"    => $meses),
	"ano" => array(
		"span"      => 4,
		"label"     => "Ano",
		"type"      => "select",
		"width"     => 5,
		"required"  => true,
		"options"    => $anos)

	);

?>
<script>
function mostraPeca(tipo_pedido,ano,mes,i){
	$("#detalhe").html("Aguarde...");
	$.ajax({
                    type    : 'POST',
                    url     : "<?php echo $_SERVER['PHP_SELF']; ?>",
                    data    : "ajax=ajax&tipo_pedido="+tipo_pedido+"&ano="+ano+"&mes="+mes,
		    success : function(data){
			resposta = data.split("|");
                        if(resposta[0] == "ok"){
				$("#detalhe").html(resposta[1]);
                        }else
                            alert("Nenhum resultado encontrado");
                    }
                });

}

</script>

<? if (strlen($erro) > 0) { ?>
<br>
    <div class="alert alert-error">
		<h4><?=$erro?></h4>
    </div>
<? } ?>

<br>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<? echo montaForm($form,null);?>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>


<?
if(isset($btn_acao)) {
	$mes = $_POST['mes'];
	$ano = $_POST['ano'];
	if(strlen($mes) == 1) $mes = "0".$mes;
	$data_inicial = date("$ano-$mes-01");
	$data_final = date('Y-m-t', strtotime($data_inicial)); 

	$sql = "
		SELECT tbl_pedido.pedido
		INTO TEMP tmp_rgf_$login_admin
		FROM tbl_pedido
		WHERE tbl_pedido.fabrica = $login_fabrica 
		AND   tbl_pedido.status_pedido <> 14
		AND tbl_pedido.data between '$data_inicial 00:00:00' and '$data_final 23:59:59';


		CREATE INDEX tmp_rgf_pedido_$login_admin ON tmp_rgf_$login_admin(pedido);

		SELECT  x.mes,
					x.ano, 
					tbl_tipo_pedido.descricao, 
					x.tipo_pedido,
					x.qtde, 
					x.valor 
			FROM (
			SELECT  TO_CHAR (tbl_pedido.data,'MM') AS mes, 
					TO_CHAR (tbl_pedido.data,'YYYY') AS ano, 
					tbl_pedido.tipo_pedido,
					SUM(tbl_pedido_item.qtde) as qtde, 
					ROUND (SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco)::numeric,2) as valor
			FROM  tbl_pedido_item
			JOIN  tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
			JOIN  tmp_rgf_$login_admin P ON P.pedido = tbl_pedido.pedido
			WHERE tbl_pedido.fabrica = $login_fabrica 
			AND   tbl_pedido.status_pedido <> 14
			AND tbl_pedido.data between '$data_inicial 00:00:00' and '$data_final 23:59:59'
			GROUP BY TO_CHAR (tbl_pedido.data,'MM'), 
			TO_CHAR (tbl_pedido.data,'YYYY'), 
					tbl_pedido.tipo_pedido
			) x
			JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = x.tipo_pedido
			ORDER BY  
			tbl_tipo_pedido.descricao, 
			x.tipo_pedido,
			x.ano, 
			x.mes" ;
	$res = pg_query($con,$sql);

	if (pg_num_rows($res)>0){
	?>
	<br>
	<table id='resultado' class="table table-striped table-bordered table-hover table-large" style='margin: 0 auto;' >
	<?

		echo "<thead >";
		echo "<tr class='titulo_coluna' style='text-align:center'>";
		echo "<td >TIPO</td>\n";
		echo "<td >Qtde</td>\n";
		echo "<td >Valor</td>\n";
		echo "</tr>";
		echo "</thead>";

		echo "<tbody>";
		$total_linhas = pg_numrows($res);
		for ($x = 0; $x < $total_linhas; $x++) {
			$mes       = trim(pg_result($res,$x,mes));
			$ano       = trim(pg_result($res,$x,ano));
			$tipo_pedido = trim(pg_result($res,$x,'tipo_pedido'));
			$descricao = trim(pg_result($res,$x,descricao));
			$qtde      = trim(pg_result($res,$x,qtde));
			$valor     = trim(pg_result($res,$x,valor));

			echo "<tr id='$tipo_pedido'>";
			echo "<td><a href='javascript:mostraPeca($tipo_pedido, $ano,$mes,$x)'>$descricao</a></td>";
			echo "<td style='text-align:center'>$qtde</td>";
			echo "<td style='text-align:right' >".number_format($valor,2,',','.')."</td>";	

			echo "</tr>";

		}//fecha o segundo for
		echo "</tbody>";
	?>

	</table><br><br>
	<div id='detalhe'></div>
	<?

	}//fecha o if q verifica se há registros
}
include "rodape.php";
?>
