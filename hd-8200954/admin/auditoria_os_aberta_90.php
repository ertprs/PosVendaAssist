<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';

if ($login_fabrica == 3) {
	header ("Location: auditoria_os_aberta_90_britania.php");
exit;
}


$layout_menu = "auditoria";
$title = "Auditoria de OSs abertas a mais de 90 dias";

include "cabecalho.php";

?>

<style type="text/css">

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
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

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>



<script type="text/javascript" src="js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type='text/javascript' src="js/bibliotecaAJAX.js"></script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>




<script language='javascript'>

function conta_os(posto,linha,contador) {

var posto = posto;
var linha = linha;
var div_os = document.getElementById('qtde_os_'+linha);
var div_sem = document.getElementById('qtde_sem_'+linha);
var div_com = document.getElementById('qtde_com_'+linha);

	var url = 'conta_os_auditoria_ajax.php?posto=' + posto + '&cache_bypass=<?= $cache_bypass ?>';

				$.ajax({
				type: "GET",
				url: "conta_os_auditoria_ajax.php?posto=",
				data: 'posto=' + posto + '&cache_bypass=<?= $cache_bypass ?>',
				cache: false,
				beforeSend: function() {
					// enquanto a função esta sendo processada, você
					// pode exibir na tela uma
					// msg de carregando
					$(div_os).html("Espere...");
					$(div_sem).html("Espere...");
					$(div_com).html("Espere...");
				},
				success: function(txt) {
					// pego o id da div que envolve o select com
					// name="id_modelo" e a substituiu
					// com o texto enviado pelo php, que é um novo
					//select com dados da marca x
					array_div = txt.split('|');
					$(div_os).html(array_div[0]);
					$(div_sem).html(array_div[1]);
					$(div_com).html(array_div[2]);
				},
				error: function(txt) {
					alert(txt);
				}
			});

	//	$(div).html(qtde);


}


</script>


<?
$sql_tipo = " 120, 122, 123, 126 ";
$aprovacao = " 120, 122 ";


$sql =  "
	SELECT DISTINCT os
	INTO TEMP tmp_interv_90_$login_admin_2
	FROM tbl_os_status
	WHERE status_os IN ($sql_tipo);
	
	SELECT
		interv.os
		INTO TEMP tmp_interv_90_$login_admin
	FROM (
		SELECT
		ultima.os,
		(
			SELECT status_os
			FROM tbl_os_status
			WHERE status_os IN ($sql_tipo)
				AND tbl_os_status.os = ultima.os
			ORDER BY data
			DESC LIMIT 1
		) AS ultimo_status
		FROM (
				SELECT os FROM tmp_interv_90_$login_admin_2
		) ultima
	) interv
	WHERE interv.ultimo_status IN ($aprovacao)
	$Xos
	;

	CREATE INDEX tmp_interv_OS_90_$login_admin ON tmp_interv_90_$login_admin(os);

	SELECT
		tbl_posto.posto,
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_posto.estado,
		count(tbl_os.os) as qtde_os
	FROM tmp_interv_90_$login_admin X
	JOIN tbl_os ON tbl_os.os = X.os
	JOIN tbl_posto using(posto)
	JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
	WHERE tbl_os.fabrica = $login_fabrica
	AND data_abertura < (current_date - interval '90 days')
	AND excluida IS NOT TRUE
	AND finalizada IS NULL
	GROUP BY
		tbl_posto.posto,
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_posto.estado
	ORDER BY tbl_posto.nome ";
//if($ip == '201.76.71.206') echo nl2br($sql);
$res = pg_exec($con,$sql);

if(pg_numrows($res)>0){

	echo "<br><table width='700' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
	echo "<tr>";
	echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
	echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Nome Posto</b></font></td>";
	echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Estado</B></font></td>";
	echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS sem Justificativa</B></font></td>";
	echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS com Justificativa</B></font></td>";
	echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Qtde Total</B></font></td>";
	echo "</tr>";

	$cores = '';
	$qtde_intervencao = 0;

	for ($x=0; $x<pg_numrows($res);$x++){

		$posto             = pg_result($res, $x, posto);
		$codigo_posto      = pg_result($res, $x, codigo_posto);
		$nome              = pg_result($res, $x, nome);
		$estado            = pg_result($res, $x, estado);
		$qtde_os           = pg_result($res, $x, qtde_os);

		$cores++;
		$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
		if(strlen($sua_os)==o)$sua_os=$os;
		echo "<tr bgcolor='$cor' >";
		echo "<td style='font-size: 9px; font-family: verdana' nowrap ><a href='auditoria_os_aberta_90_aprova.php?posto=$posto'  target='_blank'>$codigo_posto</a></td>";
		
		echo "<td style='font-size: 9px; font-family: verdana' align='left'>".$nome. "</td>";
		echo "<td align='center' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia' style='cursor: help'>". $estado."</acronym></td>";

		echo "<td align='center' style='font-size: 9px; font-family: verdana' nowrap><acronym  style='cursor: help'><div id='qtde_sem_$x'><a href=\"javascript:conta_os($posto,'$x','".($i+1)."');\">VER</a></div></acronym></td>";
		
		echo "<td align='center' style='font-size: 9px; font-family: verdana' nowrap><acronym  style='cursor: help'><div id='qtde_com_$x'><a href=\"javascript:conta_os($posto,'$x','".($i+1)."');\">VER</a></div></td>";
		
		echo "<td align='center' title='Clique aqui para ver a quantidade de OS'>
		<div id='qtde_os_$x'><a href=\"javascript:conta_os($posto,'$x','".($i+1)."');\">VER</a></div></td>";
		echo "</tr>";
	}
	echo "</table>";

	echo "<table class='table_line'>
		<tr>
			<td style='font-size: 9px; font-family: verdana' nowrap >
			<a href='auditoria_os_aberta_90_download.php' target='_blank'>Clique aqui para download de todas as OS's em Auditoria.</a>
			</td>
		</tr>
		</table>";


}else{
	echo "<br><center>Nenhum OS encontrada.</center>";
}

include "rodape.php" ?>