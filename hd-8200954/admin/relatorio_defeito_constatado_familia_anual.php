<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="gerencia";
$layout_menu = 'gerencia';
include "funcoes.php";

if (strlen($_POST["acao"]) > 0) $acao = $_POST["acao"];

if ($_POST["acao"] == 0 && strtoupper($_GET["acao"]) == "PESQUISAR") {
	$acao = $_GET["acao"];
}

$acao = trim(strtoupper($acao));

$title = "RELATÓRIO ANUAL DE OS POR DEFEITOS CONSTATADOS E POR FAMÍLIA";

if ($excel) {
	ob_start();
}
else {
	include "cabecalho.php";
}
?>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
    padding: 2px 0;
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
}
</style>
<script type='text/javascript' src='js/jquery.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script type="text/javascript">
	
	function fnc_pesquisa_posto2 (campo, campo2, tipo) {
		if (tipo == "codigo" ) {
			var xcampo = campo;
		}

		if (tipo == "nome" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.codigo  = campo;
			janela.nome    = campo2;
			janela.focus();
		}

		else{
			alert("Preencha toda ou parte da informação para realizar a pesquisa!");
		}
	}
	$().ready(function(){
		$( "#data_inicial" ).maskedinput("99/99/9999");
		$( "#data_final" ).maskedinput("99/99/9999");
        $('#data_inicial').datePicker({startDate:'01/01/2000'});
	    $('#data_final').datePicker({startDate:'01/01/2000'});
	});

</script>

<?php
	if(strlen($_GET['codigo_posto']) > 0 ) {

		$sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '".$_GET['codigo_posto']."'";
		$res_posto = pg_query($con,$sql_posto);
		if(pg_numrows($res_posto)) {
			$cod_posto = pg_result($res_posto,0,posto);
			$cond_posto  = ' AND posto = ' . $cod_posto . '';
		}
		else
			$msg_erro = 'Posto Não Encontrado';
		
	}
	
	if(strlen($_GET['estado']) > 0) {
		$cond_estado = " AND bi_os.estado = '".$_GET['estado']."'";
	}

    //VALIDA OS CAMPOS 
    if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
        $data_inicial = $_REQUEST['data_inicial'];
	    $data_final   = $_REQUEST['data_final'];

        list($di, $mi, $yi) = explode("/", $data_inicial);
		if(@!checkdate($mi,$di,$yi)) 
			$msg_erro = "Data inicial inválida!";
		

		if(empty($msg_erro)){
            list($df, $mf, $yf) = explode("/", $data_final);
            if(@!checkdate($mf,$df,$yf)) 
                $msg_erro = "Data final inválida!";
		}

		if(empty($msg_erro)){
            $aux_data_inicial = "$yi-$mi-$di";
            $aux_data_final = "$yf-$mf-$df";

            $mes_inicial = $mi;
            $mes_inicial = $mf;
		}

		if(empty($msg_erro)){
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_final) > strtotime('today')){
				$msg_erro = "Data inválida!";
			}
		}

        if(empty($msg_erro)){
            $sql = "SELECT '$aux_data_inicial'::date + interval '2 months' > '$aux_data_final'";
            $res = pg_query($con,$sql);
            $periodo = pg_fetch_result($res,0,0);
            if($periodo == 'f'){
                $msg_erro = "O período não podem ser maior que dois meses";
            }
		}

		$xmarca = $_GET["marca"]; 

    }
?>

<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; ?>
<? if (strlen($msg_erro) > 0) { ?>
	<br>
	<table width="700" border="0" cellspacing="0" cellpadding="0" align="center">
		<tr class="msg_erro">
			<td colspan="4" height='25'><? echo $msg_erro; ?></td>
		</tr>
	</table>
<? } ?>

<? if (strlen($_GET["msg"]) > 0) { ?>
	<br>
	<table width="700" border="0" cellspacing="0" cellpadding="0" align="center">
		<tr class="texto_avulso">
			<td colspan="4" height='25'><? echo $_GET["msg"]; ?></td>
		</tr>
	</table>
<? }

if ($excel) {
}
else {
?>

	<form name="frm_busca" method="GET" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="acao">
	<table width="700" border="0" cellspacing="1" cellpadding="4" align="center" class="formulario">
		<tr class="titulo_tabela">
			<td colspan="3">Parâmetros de Pesquisa</td>
		</tr>
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
		<tr>
			<td width="27%">&nbsp;</td>
			<td colspan="2"> 
				Família<br />
				<select name="familia" id="familia" class="frm">
				<option value=""></option>
				<?php
					$sql ="SELECT familia, descricao from tbl_familia where fabrica=$login_fabrica AND ativo = 't' order by descricao;";
					$res = pg_exec ($con,$sql);
					for ($y = 0 ; $y < pg_numrows($res) ; $y++){
						$familia			= trim(pg_result($res,$y,familia));
						$descricao			= trim(pg_result($res,$y,descricao));
						echo "<option value='$familia'"; 
						if ($familia == $_GET['familia']) echo " SELECTED ";
						echo ">$descricao</option>";
					}
				?>
				</select> 
			</td>
		</tr>
        <!--
		<tr>
			<td>&nbsp;</td>
			<td width="140px">
				Mês<br />
				<?php
				$mes_extenso = array(''=> "", '01' => "janeiro", '02' => "fevereiro", '03' => "março", 
				'04' => "abril", '05' => "maio", '06' => "junho", '07' => "julho", 
				'08' => "agosto", '09' => "setembro", '10' => "outubro", '11' => "novembro", 
				'12' => "dezembro");
				?>
				<select name="mes" class="frm" id="mes"><?php
					foreach ($mes_extenso as $k => $v) {
						echo '<option value="'.$k.'"'.($mes == $k ? ' selected="selected"' : '').'>
						'.ucwords($v)."</option>\n";
					}?>
				</select>
			</td>
			<td>
				Ano<br />
				<select name="ano" id="ano" class="frm">
					<?
						$ano_atual = intval(date("Y"));
						for($i = $ano_atual; $i >= 2000; $i--) 
							echo "<option value='$i'>$i</option>";
					?>
				</select>
			</td>
		</tr>
        //-->
        <tr>
            <td>&nbsp;</td>
            <td>
                Data Inicial<br />
                <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<?php echo $data_inicial; ?>" class="frm" />
            </td>
            <td>
                Data Final<br />
                <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<?php echo $data_final; ?>" class="frm" />
            </td>
        </tr>
		<tr>
			<td>&nbsp;</td>
			<td>
				Cod Posto&nbsp; <br />
				<input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="10" value="<? echo $codigo_posto ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_busca.codigo_posto,document.frm_busca.posto_nome,'codigo')">
			</td>	
		
			<td>
				Nome do Posto&nbsp;<br />
				<input class="frm" type="text" name="posto_nome" id="posto_nome" size="30" value="<? echo $posto_nome ?>">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_busca.codigo_posto,document.frm_busca.posto_nome,'nome')">
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
			<?
			if ($login_fabrica == 52) {?>
			<td>
				Marca&nbsp; <br />
				<select name='marca' class='input frm' style='font-size:12px;width:131px;' class='frm' >
					<option value=''></option>	
					<?
					$sql_fricon = "SELECT marca, nome
									FROM tbl_marca
									WHERE tbl_marca.fabrica = $login_fabrica
									ORDER BY tbl_marca.nome ";
					
					$res_fricon = pg_query($con, $sql_fricon);				
					for ($i=0; $i<pg_num_rows($res_fricon); $i++){

						$codigo_marca = pg_fetch_result($res_fricon, $i, "marca");
						$nome_marca = pg_fetch_result($res_fricon, $i, "nome");
						$selected = "";

						if($codigo_marca == $xmarca){
							$selected = "SELECTED";
						}

						echo"<option value='".pg_fetch_result($res_fricon,$i,0)."' $selected>".pg_fetch_result($res_fricon,$i,1)."</option>\n";
					}?>
				</select>
			</td>
			<?}?>
			<td colspan="2">
				Estado<br />
				<select name="estado" class="frm">
					<option value=""></option>
					<?php
						$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
						"AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
						"ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
						"MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
						"PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
						"RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
						"RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
						"SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
						foreach ($array_estado as $k => $v) {
							echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
						}
					?>
				</select>
			</td>
		</tr>

		<tr>
			<td colspan="3" align="center" style="padding:15px 0; ">
				<input type="submit" onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " value="Pesquisar" name="gerar"/>
			</td>
		</tr>
	</table>
	</form>

<?
} //if ($excel)

if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	$ano_atual = intval(date("Y"));
    
	if(strlen($mi) > 0 ) {
		$mes_inicial = $mi;
		$mes_final   = $mf;

		$cond_mes = "AND mes = '{$mi}' ";
	}
	else{
		$mes_inicial = 01;
		$mes_final = 12;
	}

	$data_ini	 = date("$ano-$mes-01");
	$d			 = date('t', strtotime($data_ini)); 

	if(strlen($_GET['familia']) > 0 )
		$cond_familia  = ' AND bi_os.familia = ' . $_GET['familia'];

	if($login_fabrica == 52){
		$campos_marca = "tbl_marca.nome,";
		$join_os_marca = "JOIN tbl_os ON tbl_os.os = bi_os.os AND tbl_os.fabrica = $login_fabrica
                		  LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_os.marca";
		
		if(strlen($_GET["marca"]) > 0){
			$cond_marca = " AND tbl_os.marca = ". $_GET["marca"];
		}
	}

	$sql = "
            SELECT
            	{$campos_marca}
                bi_os.familia,
                tbl_defeito_constatado.codigo AS defeito_constatado_codigo,
                bi_os.defeito_constatado,
                COUNT(bi_os.os) AS count_os,
                TO_CHAR(bi_os.data_finalizada, 'MM') AS mes
                INTO TEMP TABLE tmp_os_familia
            FROM bi_os
                JOIN tbl_familia ON bi_os.familia=tbl_familia.familia
                JOIN tbl_defeito_constatado ON bi_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
		JOIN tbl_os ON tbl_os.os = bi_os.os AND tbl_os.fabrica = $login_fabrica
		LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_os.marca
            WHERE
                bi_os.fabrica=$login_fabrica
                AND bi_os.data_finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                AND bi_os.excluida IS NOT TRUE 
                $cond_marca 
                $cond_familia
                $cond_posto
                $cond_estado
            GROUP BY
            	{$campos_marca}
                bi_os.familia,
                tbl_defeito_constatado.codigo,
                bi_os.defeito_constatado,
                tbl_familia.descricao,
                tbl_defeito_constatado.defeito_constatado_grupo,
                TO_CHAR(bi_os.data_finalizada, 'MM')
            ORDER BY
                tbl_familia.descricao,
                tbl_defeito_constatado.codigo,
                tbl_defeito_constatado.defeito_constatado_grupo,
                TO_CHAR(bi_os.data_finalizada, 'MM');

            SELECT * FROM tmp_os_familia;
	";
//die(nl2br($sql));
	$res = pg_query($con, $sql);

	if (pg_numrows($res) > 0) {
		$defeitos = array();
		$meses = array("Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
		$ultimo_mes = $mf;
		$familia_anterior = floatval(pg_result($res, 0, familia));
		$sql = "SELECT descricao FROM tbl_familia WHERE familia=" . $familia_anterior;
		$res_familia = pg_query($con, $sql);
		$familia_ant_descricao = strtoupper(pg_result($res_familia, 0, descricao));

		if(strlen($_GET['familia']) > 0 )
			$cond_familia  = ' AND familia = ' . $_GET['familia'];

		$sql = "
		SELECT SUM(count_os) as total
		FROM tmp_os_familia
		WHERE 1 = 1 
		$cond_familia
		";
		$res_total	= pg_query($con, $sql);
		$todas_os	= pg_result($res_total,0,total);
		//die($sql);

		for($i = 0; $i < pg_num_rows($res); $i++) {
			$os 						= pg_result($res, $i, os);
			$defeito_constatado			= intval(pg_result($res, $i, defeito_constatado));
			$count_os					= intval(pg_result($res, $i, count_os));
			$mes						= intval(pg_result($res, $i, mes));
			$familia					= floatval(pg_result($res, $i, familia));
			$defeito_constatado_codigo	= pg_result($res, $i, defeito_constatado_codigo);
			$marca				= pg_result($res,$i,nome);
			
			$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo . "|" . $marca][$mes]["os"] = $count_os;
			$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo . "|" . $marca][$mes]["tempo"] = $tempo_atendimento;
			$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo . "|" . $marca][$mes]["valor"] = $sum_mao_de_obra;
			
			if($login_fabrica == 52){
				$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo . "|" . $marca][$mes]["marca"] = $marca;
			}

			if ($mes > $ultimo_mes) 
				$ultimo_mes = $mes;
			
		}

		/* exibe as colunas */
		if(strlen($mf)>0)
			$col_meses = 1;
		else
			$col_meses = $ultimo_mes;

		$col_total = ($col_meses * 2) +5;

		if($login_fabrica == 52){
			$th_marca = "<th>Marca</th>";
		}

		echo "<br />
		<table class='tabela' cellspacing='1' cellpadding='0' align='center'>
		<thead>
			<tr class='titulo_coluna'><th align='left' colspan='100%'> $familia_ant_descricao</th></tr>
			<tr class='titulo_coluna'>
				<th>Código Defeito</th>
				<th>Grupo Defeito</th>
				<th>Defeito</th>
				$th_marca";

		for($i = ($mes_inicial-1); $i < $ultimo_mes; $i++) {
			echo "
				<th width=70> OS " . $meses[$i] . "</th>
				<th> % Mês " . $meses[$i] . "</th>";
		}

		echo "
				<th width=50>Total OS</th>
				<th>%</th>
			</tr>
		</thead>
		<tbody>";

		/* fim do cabecalho */

		$total_geral_os_mensal = array();
		$total_geral_tempo_mensal = array();
		$total_geral_os = 0;
		$count =0;

		foreach($defeitos as $defeito_constatado_familia => $mes_array) {
			$count++;
			$partes = explode("|", $defeito_constatado_familia);
			$defeito_constatado = intval($partes[0]);
			$familia = intval($partes[1]);
			$defeito_constatado_codigo = $partes[2];
			
			if($login_fabrica == 52){
				$marca = $partes[3];
			}
			
			//dados primeira familia, quando tiver filtro por familia
			if($count == 1 && strlen($_GET['familia']==0)) { 
				$sql_tf = "
				SELECT SUM(count_os) as total_familia 
				FROM tmp_os_familia 
				WHERE familia = $familia
				$cond_mes;";
				
				$res_tf = pg_query($con,$sql_tf);
				$total_os_familia = pg_result($res_tf,0,total_familia);
			}
		
			/* dados do defeito constatado */ 
			$sql = "
			SELECT
			tbl_defeito_constatado.descricao,
			tbl_defeito_constatado_grupo.defeito_constatado_grupo,
			tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo_descricao
			
			FROM
			tbl_defeito_constatado
			JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
			
			WHERE
			defeito_constatado=$defeito_constatado
			";
			$res = pg_query($con, $sql);
			$defeito_constatado_descricao = pg_result($res, 0, descricao);
			$defeito_constatado_grupo = pg_result($res, 0, defeito_constatado_grupo);
			$defeito_constatado_grupo_descricao = pg_result($res, 0, defeito_constatado_grupo_descricao);

			/* dados da familia */
			$sql = "SELECT descricao FROM tbl_familia WHERE familia=" . $familia;
			$res_familia = pg_query($con, $sql);
			$familia_descricao = strtoupper(pg_result($res_familia, 0, descricao));

			/* totalizando por familia, e novo cabecalho pra cada familia (sem filtro) */
			if($familia != $familia_anterior) {

				$colspan = ($login_fabrica == 52) ? 4 : 3;
				
				echo "
				<tr style='font-weight: bold;'>
				<td colspan=$colspan>TOTAL GERAL</td>
				";

				$total_geral_os_familia = 0;
				for ($m = intval($mes_inicial); $m <= $ultimo_mes; $m++) {
                    $mes = str_pad($m, 2, "0", STR_PAD_LEFT);

					$cond_mes = "AND mes = '{$mes}' ";

					if($mes != $m)
						continue;

					//sql pra pegar o total de os por familia
					$sql_tf = "
					SELECT SUM(count_os) as total_familia
					FROM tmp_os_familia 
					WHERE familia = $familia_anterior
					$cond_mes;";
					//echo $sql_tf."<br>";
					$res_tf = pg_query($con,$sql_tf);
					$total_os_familia = pg_result($res_tf,0,total_familia);

					if(is_null($total_os_familia))
						$total_os_familia = 0;

					$total_geral_os_familia += $total_os_familia; //total, somando todos os meses.

					$percentual = $total_os_familia > 0 ? '100%' : '0%';
					
					echo "
						<td>" . $total_os_familia . "</td>
						<td>".$percentual."</td>";

				}

				echo "
						<td>$total_geral_os_familia</td>
						<td>100%</td>
						
					</tr>";
				// fim do total

				/* cria novo cabecalho */
				echo "
						<tr>
							<td style='border:none;'>&nbsp;</td>
						</tr>
						<tr class='titulo_coluna'>
							<td align='left' colspan='100%'> $familia_descricao</td>
						</tr>
						<tr class='titulo_coluna'>
							<th>Código Defeito</th>
							<th width='120'>Grupo Defeito</th>
							<th>Defeito</th>
							$th_marca";
					
					for($i = ($mes_inicial -1); $i < $ultimo_mes; $i++)
						echo "
							<th width='80'> OS " . $meses[$i] . "</th>
							<th width='80'> % Mês " . $meses[$i] . "</th>";

					echo "
							<th width=50>Total OS</th>
							<th>%</th>
						</tr>
					";
			}

			$cor = ($count % 2) ? "#F7F5F0" : "#F1F4FA";
			echo "
			<tr bgcolor='$cor'>
				<td>$defeito_constatado_codigo</td>
				<td>$defeito_constatado_grupo-$defeito_constatado_grupo_descricao</td>
				<td>$defeito_constatado_descricao</td>";

				if($login_fabrica == 52){
					echo "<td> {$marca} </td>";
				}			
			
			$total_os		= 0;
			$count_os		= 0;
			$total_servico	= 0;

			for ($m = intval($mes_inicial); $m <= $ultimo_mes; $m++) {

				if(strlen($_GET['mes'])==0){
					if($m < 10)
						$mes = 0 . $m;
					else
						$mes =$m;

					$cond_mes = "AND mes = '".$mes."' ";
				}

				if($mes != $m)
					continue;

				if(strlen($_GET['familia']) > 0 )
					$cond_familia  = ' AND familia = ' . $_GET['familia'];
				else
					$cond_familia  = ' AND familia = ' . $familia;

				$sql_pc = "SELECT SUM(count_os) as total
							FROM tmp_os_familia
							WHERE 1 = 1
							$cond_familia
							$cond_mes
							";
				//die($sql_pc);
				$res_pc = pg_query($con,$sql_pc);
				$total_geral_os_mensal[$m] = pg_result($res_pc,0,total); // pega o total de OS

				if(empty($total_geral_os_mensal[$m]))
					$total_geral_os_mensal[$m] = 0;

				if ($count_os = $mes_array[$m]["os"]) {
				}
				else {
					$count_os = 0;
				}
				
				if($total_geral_os_mensal[$m] != 0) // calculo de os por mes
					$porc_atendimento[$m] = ($count_os / $total_geral_os_mensal[$m]) * 100;
				else
					$porc_atendimento[$m] = 0;

				// calculo de os por mes/familia

				$total_os						+= $count_os;				
				$total_geral_pct_mensal[$m]		+= $porc_atendimento[$m];
				$total_geral_os					+= $count_os;

				$total_os_por_familia			= $total_geral_os_mensal[$m];

				if(strlen($_GET['familia']) == 0 && $total_os_por_familia > 0)
					$porc_atendimento[$m] = ($count_os / $total_os_por_familia) * 100;

				echo '
				<td>'.$count_os.'</td>
				<td>'.number_format($porc_atendimento[$m],2,',','').'%</td>';
			}

			if($todas_os >0)
				$media_servico = ($total_os / $todas_os) * 100;

			if(strlen($_GET['familia'])==0 && $total_os_por_familia > 0) {
				$media_servico = ($total_os / $total_os_por_familia) * 100;
			}

			if(strlen($_GET['familia']) == 0 && strlen($_GET['mes'])==0) {
				$sql_tf = "
				SELECT SUM(count_os) as total_familia 
				FROM tmp_os_familia 
				WHERE familia = $familia
				";
			
				$res_tf = pg_query($con,$sql_tf);
				$total_os_familia_total = pg_result($res_tf,0,total_familia);
				$media_servico = ($total_os / $total_os_familia_total) * 100;
			}
			$media_total += $media_servico;

			echo "
				<td>$total_os</td>
				<td>".number_format($media_servico,2,',','')."%</td>
			</tr>";

			$familia_anterior = $familia;

		}

		if(strlen($_GET['familia'] == 0)) { //totaliza a ultima familia
			$colspan = ($login_fabrica == 52) ? 4 : 3;
			echo "
					</tbody>
					<tfoot style='font-weight: bold;'>";

			echo "	<tr>
					<td colspan=$colspan>TOTAL GERAL</td>";
			$total_ultima_familia= 0;
			for ($m = intval($mes_inicial); $m <= $ultimo_mes; $m++) {
				if(strlen($_GET['mes'])>0) {
					if($m < 10)
						$mes = 0 . $m;
					else
						$mes =$m;
					if($mes != $_GET['mes'])
						continue;
				}
				echo "
					<td>" . $total_geral_os_mensal[$m] . "</td>
					<td>100%</td>";
				$total_ultima_familia += $total_geral_os_mensal[$m];
			}

			echo "
					<td>$total_ultima_familia</td>
					<td>100%</td>
					
				</tr>
			</tfoot>
			</table>";
		}

		if(strlen($_GET['familia'] != 0)) { // totaliza quando filtra por familia
			echo "
					</tbody>
					<tfoot style='font-weight: bold;'>";

			$colspan = ($login_fabrica == 52) ? 4 : 3;

			echo "	<tr>
					<td colspan=$colspan>TOTAL GERAL</td>";

			for ($m = intval($mes_inicial); $m <= $ultimo_mes; $m++) {
				if(strlen($_GET['mes'])>0) {
					if($m < 10)
						$mes = 0 . $m;
					else
						$mes =$m;
					if($mes != $_GET['mes'])
						continue;
				}
				echo "
					<td>" . $total_geral_os_mensal[$m] . "</td>
					<td>" . number_format($total_geral_pct_mensal[$m], 2, ",", "") . "%</td>";
			}

			echo "
					<td>$total_geral_os</td>
					<td>".number_format($media_total,2,',','')."%</td>
					
				</tr>
			</tfoot>
			</table>";
		}
	}
	else {
		echo "<br>Não foram encontrados resultados para esta pesquisa";
	}
}
echo "<br>";


if ($excel) {
	$conteudo_excel = ob_get_clean();
	$arquivo = fopen("xls/relatorio_defeito_constatado_os_anual_$login_fabrica$login_admin.xls", "w+");
	fwrite($arquivo, $conteudo_excel);
	fclose($arquivo);
	header("location:xls/relatorio_defeito_constatado_os_anual_$login_fabrica$login_admin.xls");
}
else {
	if ($acao == "PESQUISAR") {
		echo "<button onclick=\"window.location='" . $PHP_SELF . "?" . $_SERVER["QUERY_STRING"] . "&excel=1'\"> Download em Excel</button>";
		echo "<br><br>";
	}

	include "rodape.php";
}
?>
