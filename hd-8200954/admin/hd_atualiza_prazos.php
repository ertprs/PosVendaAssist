<style type="text/css">
<!--
@import url("estilo.css");
-->

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 0px solid;
	background-color: #596D9B;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
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

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

p{
margin-left: 10%;
}

.sucesso {
  color: white;
  text-align: center;
  font: bold 16px Verdana, Arial, Helvetica, sans-serif;
  background-color: green;
}

.subtitulo{

	background-color:#7092BE;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center; 
}

</style>

<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$title = "ATUALIZA PRAZOS HELPDESK";
include 'cabecalho.php';

$sql = "
SELECT
fila,
hd_chamado,
prioridade_supervisor,
data_aprovacao_fila,
previsao_inicio,
previsao_termino,
prazo_dias_uteis

FROM
tbl_hd_chamado

WHERE
tipo_chamado>0
AND fabrica=3
AND prazo_dias_uteis IS NOT NULL

ORDER BY
fila,
prioridade_supervisor,
hd_chamado
";
$res = pg_query($con, $sql);

for($i = 0; $i < pg_num_rows($res); $i++) {
	$dados = pg_fetch_array($res);
	extract($dados);

	if ($fila != $fila_anterior) {
		$sql = "SELECT CURRENT_DATE";
		$res_data = pg_query($con, $sql);
		$prazo_fim_anterior = pg_result($res_data, 0, 0) . " 08:00:00";
	}

	if (strlen($data_aprovacao_fila) > 0) {
		$sql = "SELECT fn_calcula_previsao('$data_aprovacao_fila'::timestamp, $prazo_dias_uteis) AS prazo_fim";
		$res_prazo = pg_query($con, $sql);
		$prazo_fim = pg_result($res_prazo, 0, 'prazo_fim');
		
		$sql = "UPDATE tbl_hd_chamado SET previsao_termino_interna='$prazo_fim' WHERE hd_chamado=$hd_chamado";
		$res_atualiza = pg_query($con, $sql);
	}
	else {
		$sql = "SELECT fn_calcula_previsao('$prazo_fim_anterior'::timestamp, $prazo_dias_uteis) AS prazo_fim";
		$res_prazo = pg_query($con, $sql);
		$prazo_fim = pg_result($res_prazo, 0, 'prazo_fim');
		
		$sql = "UPDATE tbl_hd_chamado SET previsao_termino_interna='$prazo_fim' WHERE hd_chamado=$hd_chamado";
		$res_atualiza = pg_query($con, $sql);
	}

	echo "$hd_chamado|$prazo_fim<br>";
//	echo "$hd_chamado|$prazo_fim|$prazo_fim_anterior|$prazo_dias_uteis|$data_aprovacao_fila<br>";
	$prazo_fim_anterior = $prazo_fim;
	$fila_anterior = $fila;
}

include "rodape.php";
