<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";

include 'autentica_admin.php';

include "../anexaNF_inc.php";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) ilike UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}

$posto        = trim($_REQUEST["posto"]);
$codigo_posto = trim($_REQUEST["codigo_posto"]);
$status       = trim($_REQUEST["status"]);
$periodo      = trim($_REQUEST["mes"]).'/'.trim($_REQUEST["ano"]);
$admin        = trim($_REQUEST["admin"]);

if($acao == "PESQUISAR"){
	if (strlen($codigo_posto)) {
		$sql = "
		SELECT
		tbl_posto.posto,
		tbl_posto.nome
		
		FROM
		tbl_posto_fabrica
		JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto

		WHERE
		fabrica=$login_fabrica
		AND codigo_posto='$codigo_posto'
		";
		$res = pg_query($sql);
		
		if (pg_num_rows($res)) {
			$acao = "PESQUISAR";
			$posto = pg_result($res, 0, posto);
		}
	}

	if(strlen($periodo)==0){
		$msg_erro = "Informe o Mês para a pesquisa";
	}else{
		$periodo = explode("/", $periodo);
		if (count($periodo) == 2) {
			$periodo_mes = intval($periodo[0]);
			$periodo_ano = intval($periodo[1]);

			if($status != 154){
				if ($periodo_mes < 1 || $periodo_mes > 12) {
					$msg_erro = "Período informado é inválido";
				}
			}
			if($periodo_mes < 10){
				$periodo_mes = '0'.$periodo_mes;
			}
		}
	}
}

$title = 'RELATÓRIO DE OS POR STATUS DA NOTA';
include "cabecalho.php";
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


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{
    padding-left: 140px
}
</style>

<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; ?>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>

<script language="JavaScript">

function status_onchange(){
	var id_status = document.getElementById('status').value;

	if(id_status==154){
		document.getElementById('mes').disabled = true;
	}else{
		document.getElementById('mes').disabled = false;
	}
}

$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});

	status_onchange();
});

</script>

<?
if(strlen($msg_erro)>0){
	echo "<div class='msg_erro'>$msg_erro</div>";
	echo "<br>";
}
?>

<form name="frm_busca" method="post" action="<?=$PHP_SELF?>">
<input type="hidden" name="acao">
<table width="700px" border='0' cellspacing="1" cellpadding="4" align="center" class='formulario'>
	<tr class="titulo_tabela">
		<td colspan="3" height='25'>Parâmetros de Pesquisa</td>
	</tr>
	<tr >
		<td width='300px'>&nbsp;</td>
		<td width='*'>&nbsp;</td>
		<td width='240px'>&nbsp;</td>
	</tr>

	<tr>
		<td class='espaco'>Posto<br>
            <input type="text" name="codigo_posto" id="codigo_posto" size="15"  value="<? echo $codigo_posto ?>" class="frm"  style='width: 120px;'>
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_busca.codigo_posto, document.frm_busca.posto_nome, 'codigo')">
		</td>
		<td colspan='2'>
		    Nome do Posto<br>
            <input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_busca.codigo_posto, document.frm_busca.posto_nome, 'nome')">
		</td>
	</tr>

	<tr>
		<td class='espaco'>
		    Status<br>
            <select id="status" name="status" class='frm' onchange="status_onchange();" onkeyup="status_onchange();" style='width: 120px;'>
			<?
			$opcoes_status = array();
			$opcoes_status["152"] = "Trocado com nota";
			$opcoes_status["153"] = "Trocado sem nota";
			$opcoes_status["154"] = "Troca pendente";

			foreach($opcoes_status as $valor => $label) {
				if($status == $valor)  $selected = "selected"; else $selected =  "";
			    echo "<option $selected value='$valor'>$label</option>";
			}
			?>
			</select>
		</td>
		<td>
		    Mês<br>
		    <select name="mes" id="mes" size="1" class="frm" style='width: 120px;'>
				<?
				$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
				//if($mes == NULL) $mes = date('m');
				for ($i = 1 ; $i <= count($meses) ; $i++) {
				    if($mes == $i)  $selected = "selected"; else $selected =  "";

					echo "<option value='$i' {$selected} >{$meses[$i]}</option>";
				}
				?>
			</select>
		</td>
		<td>
		    Ano<br>
			<select name="ano" size="1" class="frm"  style='width: 92px;'>
				<?
				if($ano == NULL) $ano = date('Y');
				for ($i = date("Y") ; $i >= 2003; $i--) {
				    if($ano == $i)  $selected = "selected"; else $selected =  "";
				    
					echo "<option value='$i' {$selected} >$i</option>";
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td class='espaco'>
		    Admin<br>
            <?  
			$resAdmin = pg_exec($con,"SELECT admin, login FROM tbl_admin WHERE tbl_admin.fabrica = $login_fabrica AND   tbl_admin.ativo IS TRUE ORDER BY login");
			if(pg_numrows($resAdmin)>0){
				echo "<select name='admin' class='frm'  style='width: 120px;'>";
				    echo "<option value=''></option>";
				    for($x=0; $x<pg_numrows($resAdmin); $x++){
				    	$xadmin = pg_result($resAdmin,$x,admin);
				    	$login = pg_result($resAdmin,$x,login);
				    	echo "<option value='$xadmin'";
				    	if($xadmin==$admin) echo 'selected';
				        	echo ">$login</option>";
				    	}
				    echo "</select>";
			}
			?>
		</td>
		<td colspan='2'>
		    Estado<br>
            <select name="estado" id='estado' style='width:140px; font-size:9px' class="frm">
				<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
				<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
				<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
				<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
				<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
				<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
				<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
				<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
				<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
				<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
				<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
				<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
				<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
				<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
				<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
				<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
				<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
				<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
				<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
				<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
				<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
				<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
				<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
				<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
				<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
				<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
				<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
				<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
			</select>
		</td>
	</tr>
	<tr >
		<td colspan="3" align="center" style='padding:10px;'><input type='button' value='Pesquisar' onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>
</form>

<?
if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {

	if(strlen($posto)>0){
		$cond_posto = " AND tbl_os.posto = $posto ";
	}else{
		$cond_posto = " AND 1=1 ";
	}

	if(strlen($estado)>0){
		$cond_estado = " AND tbl_posto.estado = '$estado' ";
	}else{
		$cond_estado = " AND 1=1 ";
	}

	if(strlen($admin)>0){
		$cond_admin = " AND tbl_os_troca.admin = $admin ";
	}else{
		$cond_admin = " AND 1=1 ";
	}

	if($status!=154){
	//if($status=="152" or $status=="153" or $status=="154"){
		$periodoWhere = " AND tbl_os_troca.data BETWEEN '$periodo_ano-$periodo_mes-01 00:00:00'::timestamp AND '$periodo_ano-$periodo_mes-01 00:00:00'::timestamp + INTERVAL '1 MONTH' - INTERVAL '1 DAY' ";
	}else{
		$periodoWhere = "  AND tbl_os_troca.data BETWEEN '$periodo_ano-01-01 00:00:00'::timestamp AND '$periodo_ano-12-31 00:00:00'::timestamp ";
	}

	if($status=="152" or $status=="153" or $status=="154"){
		$cond_status = " AND tbl_os_status.status_os IN ($status) ";
	}

	//Caso o campo admin tenha sido preenchido, não deve ser filtrado nesta consulta (Ebano)
	if($status==154){
		$sqlP = "SELECT MAX(os_status), tbl_os_status.os, tbl_os_status.admin
				 INTO TEMP tmp_status_pendente_$login_admin
				 FROM tbl_os_status
				 WHERE fabrica_status = $login_fabrica
				 $cond_status
				 GROUP BY tbl_os_status.os, tbl_os_status.admin;
				 
				 CREATE INDEX tmp_status_pendente_os_$login_admin ON tmp_status_pendente_$login_admin(os);";
	}else{
		$sqlP = "SELECT MAX(os_status), tbl_os_status.os, tbl_os_status.admin
				INTO TEMP tmp_status_pendente_$login_admin
				FROM tbl_os_status
				JOIN tbl_os_troca ON tbl_os_status.os=tbl_os_troca.os
				WHERE fabrica_status = $login_fabrica
				$cond_status
				GROUP BY tbl_os_status.os, tbl_os_status.admin;
				
				CREATE INDEX tmp_status_pendente_os_$login_admin ON tmp_status_pendente_$login_admin(os);";
	}
	#echo nl2br($sqlP);
	$resP = pg_exec($con, $sqlP);

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome  AS nome_posto ,
					tbl_posto.estado              ,
					tbl_os.sua_os                 ,
					tbl_os.os					  ,
					(select tbl_produto.referencia ||' - '|| tbl_produto.descricao from tbl_produto where tbl_produto.produto = tbl_os.produto) AS produto,
					tbl_os.nota_fiscal            ,
					to_char(tbl_os.data_nf, 'dd/mm/yyyy') AS data_nf,
					(select nome_completo from tbl_admin where tbl_admin.admin = tmp_status_pendente_$login_admin.admin) AS admin
			FROM tbl_os
			LEFT JOIN tbl_os_troca USING(os)
			JOIN tbl_posto    USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tmp_status_pendente_$login_admin USING(os)
			WHERE tbl_os.fabrica = $login_fabrica $cond_posto $cond_admin $cond_estado $periodoWhere";
	#echo nl2br($sql);
	$res = pg_exec($con, $sql);

	include_once('../anexaNF_inc.php');
	if(pg_numrows($res)>0){
		echo "<br>";
		echo "<table width='88%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
			echo "<td>Posto</td>";
			echo "<td>Estado</td>";
			echo "<td>OS</td>";
			echo "<td>Produto</td>";
			echo "<td>NF</td>";
			echo "<td>Data NF</td>";
			if($login_fabrica == 72)
				echo "<td>NF Anexada</td>";
			echo "<td>Admin Responsável</td>";
		echo "</tr>";
		for($x=0; $x<pg_numrows($res); $x++){
			$codigo_posto = pg_result($res,$x,codigo_posto);
			$nome_posto   = pg_result($res,$x,nome_posto);
			$estado       = pg_result($res,$x,estado);
			$sua_os       = pg_result($res,$x,sua_os);
			$num_os       = pg_result($res,$x,os);
			$produto      = pg_result($res,$x,produto);
			$nota_fiscal  = pg_result($res,$x,nota_fiscal);
			$data_nf      = pg_result($res,$x,data_nf);
			$admin        = pg_result($res,$x,admin);

			$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'>$codigo_posto - $nome_posto</td>";
			echo "<td>$estado</td>";
			echo "<td>$sua_os</td>";
			echo "<td align='left'>$produto</td>";
			echo "<td>$nota_fiscal</td>";
			echo "<td>$data_nf</td>";
			if($login_fabrica == 72){
				echo "<td>";
					echo (temNF($num_os,'bool') == true) ? "SIM" : "NÃO";
				echo "</td>";
			}
			echo "<td>$admin</td>";
			echo "</tr>";
		}
		echo "</table><br><br>";
	}else{
		echo "<p>Nenhum resultado encontrado para a pesquisa</p>";
	}

}
?>

<? include "rodape.php";?>
