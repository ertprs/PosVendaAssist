<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

$codigo_posto = $_POST["codigo_posto"];
if($_POST["codigo_posto"]){
	if(strlen($codigo_posto)==0){
		$msg_erro = traduz("Informe os Parâmetros para a Pesquisa");
	}
}

$layout_menu = "financeiro";
$title = traduz("COMPARATIVO ANUAL DE MÉDIA DE EXTRATO");

include 'cabecalho.php';

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
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
</style>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

<script>

$().ready(function(){
	Shadowbox.init();
});

 function gravaDados(name, valor){
 	try{
        	$("input[name="+name+"]").val(valor);
        } catch(err){
        	return false;
        }
 }

 function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
 	gravaDados('codigo_posto',codigo_posto);
 	gravaDados('posto_nome',nome);
 }

 function pesquisaPosto(campo,tipo){
 	var campo = campo.value;
	
	if (jQuery.trim(campo).length > 2){
        	Shadowbox.open({
                                content:    "posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
                                player: "iframe",
                                title:      "Pesquisa Posto",
                                width:  800,
                                height: 500
                 });
        }else
        	alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
 }
	
</script>

<form name='frm' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
<table width='700' border='0' cellspacing='1' cellpadding='2' class='formulario' align="center">
	<?php if(strlen($msg_erro)>0){ ?>
			<tr class="msg_erro">
				<td colspan="2"><?php echo $msg_erro; ?></td>
			</tr> 
	<?php } ?>
	<tr class="titulo_tabela">
		<td colspan="3"><?=traduz('Parâmetros de Pesquisa')?></td>
	</tr>

	<tr>
		<td width="150">&nbsp;</td>
		<td align='left'>
			<?=traduz('Código Posto')?>
			<br>
			<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="pesquisaPosto (document.frm.codigo_posto,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: pesquisaPosto (document.frm.codigo_posto,'codigo')"></A>
		</td>
		<td align='left'>
			<?=traduz('Razão Social')?>
			<br><input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="pesquisaPosto(document.frm.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: pesquisaPosto (document.frm.posto_nome,'nome')" style="cursor:pointer;"></A>
		</td>
	</tr>
	<?php if (in_array($login_fabrica, array(169,170))) {
			$sqlInspetor = "SELECT admin,
                                  login,
                                  nome_completo
                             FROM tbl_admin
                            WHERE tbl_admin.fabrica = {$login_fabrica}
                              AND tbl_admin.admin_sap IS TRUE
                              AND tbl_admin.ativo IS TRUE
                         ORDER BY tbl_admin.nome_completo ASC;";
            $resInspetor  = pg_query($con, $sqlInspetor);

		    $sqlEstado = "
		        SELECT  estado,
		                nome
		        FROM    tbl_estado
		        WHERE   visivel IS TRUE
		  		ORDER BY      estado
		    ";
    		$resEstado = pg_query($con,$sqlEstado);

	?>
	<tr>
		<td width="150">&nbsp;</td>
		<td>
			Inspetor<br />
			<select name="inspetor">
				<option value=""></option>
				<?php
				for ($i = 0; $i < pg_num_rows($resInspetor); $i++) {
					$admin = pg_fetch_result($resInspetor, $i, 'admin');
					$login = pg_fetch_result($resInspetor, $i, 'login');
					$nome_completo = pg_fetch_result($resInspetor, $i, 'nome_completo');

					$nome_completo = (empty($nome_completo)) ? $login : $nome_completo;
					$selected = ($inspetor == $admin) ? 'selected' : '';

					echo "<option value='$admin' $selected>$nome_completo</option>";
				}
				?>
			</select>
		</td>
		<td>
			Por Região<br />
			<select name="estado" id="estado">
				<option value=""></option>
				<?php
				for ($i = 0; $i < pg_num_rows($resEstado); $i++) {
					$estado_consulta = pg_fetch_result($resEstado, $i, 'estado');
					$nome = pg_fetch_result($resEstado, $i, 'nome');
					$selected = ($estado_consulta == $estado) ? 'selected' : '';

					echo "<option value='$estado_consulta' $selected>$estado_consulta - $nome</option>";
				}
				?>
			</select>
		</td>
	</tr>
	<?php } ?>
	<tr>
		<td colspan="3" align="center">
			<input type='submit' name='btn_gravar' value='Pesquisar' style='cursor:pointer'>
			<input type='hidden' name='acao' value="<?php echo $acao;?>">
		</td>
	</tr>
	<tr>
		<td colspan="3" align="center">&nbsp;</td>
	</tr>
</table>

<?
if(strlen($codigo_posto)>0){

	# 51985 - Francisco Ambrozio
	#   Alterei para pesquisar a partir do dia 1º do mês e incluir o mês atual
	#$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")+1 , 1, date("Y")-1);
	$data_inicial = strftime ("%Y-%m-%d", $data_serv);

	$xdata_inicial = $data_inicial .' 00:00:00';
	$xdata_final = date("Y-m-d 23:59:59");

	$cond_169_170 = '';
	if (in_array($login_fabrica, array(169,170))) {
		if (strlen($inspetor) > 0) {
			$cond_169_170 .= " AND tbl_extrato.admin = $inspetor";
		}
		if (strlen($estado) > 0) {
			$cond_169_170 .= " AND tbl_posto.estado = '$estado'";
		}
	}

	$sql = "SELECT   SUM(coalesce(pecas,0)+coalesce(mao_de_obra,0)+coalesce(avulso,0))        AS total        ,
			 to_char(data_geracao,'YYYY-MM')      AS data_geracao ,
			 tbl_posto.posto                                      ,
			 tbl_posto.nome                       AS posto_nome   ,
			 tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto_fabrica  ON  tbl_extrato.posto         = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_posto          ON tbl_extrato.posto          = tbl_posto.posto
		WHERE tbl_extrato.fabrica          = $login_fabrica
		AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'
		AND tbl_extrato.aprovado IS NOT NULL
			AND to_char(data_geracao,'YYYY-MM') IN (
			SELECT DISTINCT to_char(data_geracao,'YYYY-MM')
			FROM tbl_extrato
			JOIN tbl_posto_fabrica using(posto)
			WHERE codigo_posto     ='$codigo_posto'
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		)
		AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		$cond_169_170
		GROUP BY to_char(data_geracao,'YYYY-MM'),
			tbl_posto.posto                 ,
			tbl_posto.nome                  ,
			tbl_posto_fabrica.codigo_posto
		ORDER BY to_char(data_geracao,'YYYY-MM');";

	#if ($ip == "200.228.76.11") echo $sql;
//echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$posto           = trim(pg_result($res,0,posto))       ;
		$posto_nome      = trim(pg_result($res,0,posto_nome))  ;
		$codigo_posto    = trim(pg_result($res,0,codigo_posto));

		echo "<br><table border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td width='100' rowspan='2' >".traduz("Valor Pago/Mês")."</td>";
		echo "<td colspan='12'>".traduz("Meses")."</td>";
		echo "<td rowspan='2' class='Mes'>".traduz("Total Ano")."</td>";
		echo "</tr><tr class='titulo_coluna'>";

		# HD 68843
		$mes_atual = date("m");
		$ano_atual = date("Y");
		$ano_atual--;

		for($x=0;$x<12;$x++){

			if ($mes_atual < 12){
				$mes_atual++;
			}else{
				$mes_atual = 01;
				$ano_atual++;
			}

			$mes_atual = sprintf("%02d",$mes_atual);

			$mes[$x] = "$mes_atual/$ano_atual";
			echo "<td class='Mes'>$mes[$x]</td>";
		}

		echo "</tr>";

		$x=0;
		$y=0;
		//zerando todos arrays
		$posto_total=0;
		$qtde_mes =  array();

		$total_mes = 0;
		$total_ano = 0;

		$qtde_mes[$posto_total][0]  = 0;
		$qtde_mes[$posto_total][1]  = 0;
		$qtde_mes[$posto_total][2]  = 0;
		$qtde_mes[$posto_total][3]  = 0;
		$qtde_mes[$posto_total][4]  = 0;
		$qtde_mes[$posto_total][5]  = 0;
		$qtde_mes[$posto_total][6]  = 0;
		$qtde_mes[$posto_total][7]  = 0;
		$qtde_mes[$posto_total][8]  = 0;
		$qtde_mes[$posto_total][9]  = 0;
		$qtde_mes[$posto_total][10] = 0;
		$qtde_mes[$posto_total][11] = 0;
		$qtde_mes[$posto_total][12] = $posto_nome;
		$x=0;

		for ($i=0; $i<pg_numrows($res); $i++){

			$posto           = trim(pg_result($res,$i,posto));
			$data_geracao    = trim(pg_result($res,$i,data_geracao));
			$total           = trim(pg_result($res,$i,total));

			$xdata_geracao = explode('-',$data_geracao);
			$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

			if($posto_anterior<>$posto){

	//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO PRODUTO
				if($i<>0 ){

					for($a=0;$a<12;$a++){			//imprime os doze meses
						echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
						if ($qtde_mes[$y][$a]>0)
							echo "<font color='#000000'><b> ". $real .number_format($qtde_mes[$y][$a],2,',','.');
						else echo "<font color='#999999'> ";

						echo "</td>";
						$total_ano = $total_ano + $qtde_mes[$y][$a];
						if($a==11) {
							$total_ano = number_format($total_ano,2,',','.');
							echo "<td bgcolor='$cor' >$real . $total_ano</td>";
							echo "</tr>";
						}	// se for o ultimo mes quebra a linha
					}

					$y=$y+1;						// usado para indicação de produto
				}

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				echo "<tr align='center'>";
				echo "<td bgcolor='$cor' width='150'  height = '40'><b>$posto_nome</b></td>";

				$total_ano = 0;
				$x=0; //ZERA OS MESES

			}

			while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
				$x=$x+1;
			};

			if($data_geracao == $mes[$x]){
				$qtde_mes[$y][$x] = $total;
			}

			$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor

			if($i==(pg_numrows($res)-1)){
				for($a=0;$a<12;$a++){			//imprime os doze meses
					echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
					if ($qtde_mes[$y][$a]>0)
						echo "<font color='#000000'> ". $real .number_format($qtde_mes[$y][$a],2,',','.');
					else echo "<font color='#999999'> ";

					echo "</td>";
					$total_ano = $total_ano + $qtde_mes[$y][$a];
					if($a==11) {
						$total_ano = number_format($total_ano,2,',','.');
						echo "<td bgcolor='$cor' >$real . $total_ano</td>";
						echo "</tr>";
					}	// se for o ultimo mes quebra a linha
				}

			}
			$posto_anterior=$posto;

		}

	/*	for($i=0; $i<$posto_total ; $i++){
			for($j=0; $j<13 ; $j++)echo $qtde_mes[$i][$j]." - ";
		echo "<br><br>";
		}
	*/
		flush();

		# 51985
		#$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
		$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")+1 , 1, date("Y")-1);
		$data_inicial = strftime ("%Y-%m-%d", $data_serv);

		$xdata_inicial = $data_inicial .' 00:00:00';
		$xdata_final = date("Y-m-d 23:59:59");

		$sql = "SELECT   SUM(coalesce(pecas,0)+coalesce(mao_de_obra,0)+coalesce(avulso,0))                           AS total        ,
			to_char(data_geracao,'YYYY-MM')      AS data_geracao
		FROM tbl_extrato
		WHERE tbl_extrato.fabrica          = $login_fabrica
		AND tbl_extrato.aprovado IS NOT NULL
		AND to_char(data_geracao,'YYYY-MM') IN (
			SELECT DISTINCT to_char(data_geracao,'YYYY-MM')
			FROM tbl_extrato
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		)
		AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		GROUP BY to_char(data_geracao,'YYYY-MM')
		ORDER BY to_char(data_geracao,'YYYY-MM');";

#if ($ip == "200.228.76.93") echo "<br><br>".$sql;

		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {

			$x=0;
			$y=0;
			//zerando todos arrays

			$posto_total2 = 0;
			$qtde_mes2   =  array();
			$qtde_posto2 =  array();

			$total_mes2 = 0;
			$total_ano2 = 0;

			$qtde_mes2[$posto_total2][0]  = 0;
			$qtde_mes2[$posto_total2][1]  = 0;
			$qtde_mes2[$posto_total2][2]  = 0;
			$qtde_mes2[$posto_total2][3]  = 0;
			$qtde_mes2[$posto_total2][4]  = 0;
			$qtde_mes2[$posto_total2][5]  = 0;
			$qtde_mes2[$posto_total2][6]  = 0;
			$qtde_mes2[$posto_total2][7]  = 0;
			$qtde_mes2[$posto_total2][8]  = 0;
			$qtde_mes2[$posto_total2][9]  = 0;
			$qtde_mes2[$posto_total2][10] = 0;
			$qtde_mes2[$posto_total2][11] = 0;
			$qtde_mes2[$posto_total2][12] = "Média";

			$qtde_posto2[$posto_total2][0]  = 0;
			$qtde_posto2[$posto_total2][1]  = 0;
			$qtde_posto2[$posto_total2][2]  = 0;
			$qtde_posto2[$posto_total2][3]  = 0;
			$qtde_posto2[$posto_total2][4]  = 0;
			$qtde_posto2[$posto_total2][5]  = 0;
			$qtde_posto2[$posto_total2][6]  = 0;
			$qtde_posto2[$posto_total2][7]  = 0;
			$qtde_posto2[$posto_total2][8]  = 0;
			$qtde_posto2[$posto_total2][9]  = 0;
			$qtde_posto2[$posto_total2][10] = 0;
			$qtde_posto2[$posto_total2][11] = 0;
			$qtde_posto2[$posto_total2][12] = "Média";

			$x = 0;
			for ($i=0; $i<pg_numrows($res); $i++){

				$data_geracao    = trim(pg_result($res,$i,data_geracao));
				$total           = trim(pg_result($res,$i,total));

				$sql2 = "SELECT  count(*) ,
						posto
					FROM tbl_extrato
					WHERE fabrica = $login_fabrica
					AND  to_char(data_geracao,'YYYY-MM') ='$data_geracao'
					GROUP BY posto;";

				$xdata_geracao = explode('-',$data_geracao);
				$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

				$res2 = pg_exec($con,$sql2);
				if (pg_numrows($res2) > 0) {
					$postos_digitaram[$i] = pg_numrows($res2);
					$media_mes[$i] = $total / $postos_digitaram[$i];
					//echo "data: $data_geracao".$postos_digitaram[$i].' = '.$media_mes[$i].'<br>';
				}


				$cor = '#F7F5F0';

				if($i==0){
					echo "<tr align='center'>";
					echo "<td bgcolor='$cor' width='150'  height = '40'><b>".traduz("Média")."</b></td>";
				}

				$total_ano2 = 0;
				$x = 0; //ZERA OS MESES

				while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
					//echo "$data_geracao<>".$mes[$x];
					$x=$x+1;
				};

				if($data_geracao == $mes[$x]){
					$qtde_mes2[$y][$x]   = $media_mes[$i];
					$qtde_posto2[$y][$x] = $postos_digitaram[$i];

				}

				$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor

				if($i==(pg_numrows($res)-1)){
					for($a=0;$a<12;$a++){			//imprime os doze meses
						echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
						if ($qtde_mes2[$y][$a]>0)
							echo "<font color='#000000'> ". $real .number_format($qtde_mes2[$y][$a],2,',','.');
						else echo "<font color='#999999'> ";

						echo "</td>";
						$total_ano2 = $total_ano2 + $qtde_mes2[$y][$a];
						if($a==11) {
							$total_ano2 = number_format($total_ano2,2,',','.');
							echo "<td bgcolor='$cor' >$real . $total_ano2</td>";
							echo "</tr>";

							//TOTAL DE POSTOS
							echo "<tr class='Conteudo'align='center'>";
							for($a=0;$a<12;$a++){
								if($a==0) echo "<td bgcolor='$cor'><b>".traduz("Total de Postos")."</b></td>";
								echo "<td bgcolor='$cor'>";
								if ($qtde_mes2[$y][$a]>0)
									echo "<font color='#000000'>".$qtde_posto2[$y][$a];
								else    echo " ";
								echo "</td>";
							}
							echo "<td bgcolor='$cor'> - </td></tr>";
						}	// se for o ultimo mes quebra a linha
					}
				}
			}
		}
		echo "</table><br>";
		include "posto_extrato_ano_grafico.php";
	}else{
		echo traduz("Nenhum extrato durante este período");
	}
}

include 'rodape.php';
?>
