<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


$meses = array(01 => "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO");
$mostra_mes = array('01'=>"JAN",'02'=>"FEV",'03'=>"MAR",'04'=>"ABR",'05'=>"MAI",'06'=>"JUN",'07'=>"JUL",'08'=>"AGO",'09'=>"SET",'10'=>"OUT",'11'=>"NOV",'12'=>"DEZ");

$msg_erro = '';

function ultimodiames($soma_inicial=""){
	if (!$soma_inicial){
		$ano = date("Y");
		$mes = date("m");
		$dia = date("d");
	}else{
		$ano = date("Y",$soma_inicial);
		$mes = date("m",$soma_inicial);
		$dia = date("d",$soma_inicial);
	}
$soma_inicial = mktime(0, 0, 0, $ano, $mes, 1);
//$soma_inicial = mktime(0, 0, 0, $mes, 1, $ano);
return date(0,$soma_inicial-1);
}

// Pega os valores das variaveis passadas por parametros de pesquisa e as coloca em um cookie
$cookget = @explode("?", $REQUEST_URI);
// Expira quando fecha o browser 
setcookie("cookget", $cookget[1]);


	
	//   ***** CAPTURA DE DADOS *****   \\
	// armazena os dados selecionados nas veriáveis
	if($_GET['marca'])
		$marca = trim($_GET['marca']);

	if($_GET['marca2'])
		$mes_inicial = str_pad(trim($_GET['marca2']), 2, '0', STR_PAD_LEFT);
	if($_GET['marca3'])
		$ano_inicial = trim($_GET['marca3']);

	if($_GET['marca4'])
		$mes_final   = str_pad(trim($_GET['marca4']), 2, '0', STR_PAD_LEFT);
	if($_GET['marca5'])
		$ano_final   = trim($_GET['marca5']);

include "cabecalho.php";?>
<p>
<style type="text/css">
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
</style>
<?
//*************************************PRODUTO*************************************************\\

// Monta a data inicial 
$data_inicial = $ano_inicial.'-'.$mes_inicial.'-'.'01';
$dta_inicial  = $ano_inicial.'-'.$mes_inicial.'-'.'01';
// Monta a data final (em duas variáveis)
$data_final   = $ano_final.'-' .$mes_final.'-'.'01';
$dta_final    = $ano_final.'-' .$mes_final.'-'.'01';

// Processo que determina a data inicial selecionada pelo usuário (YYYY-MM-DD HH:MM:SS)
$dta_inicial     = substr($dta_inicial, 0,10 )." 00:00:00";
// Processo que determina a data final selecionada mais um mês
$sql_data_final  = "Select '$data_final'::date + interval '1 month' as data_lista";
$res_data_final  = pg_exec($con,$sql_data_final);
$vet_data_final  = pg_result($res_data_final,0,data_lista);
// Processo que determina o último dia do mês selecionado
$sql_data_final  = "Select '$vet_data_final'::date - interval '1 day' as data_lista";
$res_data_final  = pg_exec($con,$sql_data_final);
$vet_data_final  = pg_result($res_data_final,0,data_lista);
$dta_final       = substr($vet_data_final, 0,10 )." 23:59:59";


// Processo para localização no banco dos PRODUTOS
$sql_produto = "
		SELECT tbl_produto.descricao
		FROM tbl_hd_chamado 
		JOIN tbl_hd_chamado_extra USING(hd_chamado)
		JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
		JOIN tbl_marca   ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = $login_fabrica
		WHERE data >= '$dta_inicial' AND data <= '$dta_final'
		AND tbl_marca.nome = '$marca'
		AND tbl_hd_chamado.fabrica = $login_fabrica
		AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		GROUP BY descricao
		ORDER BY descricao";
$res_produto  = @pg_exec($con,$sql_produto);

// Processo para Formatação do LABEL de acordo com o BANCO DE DADOS.
for ($i = 0; $i < pg_num_rows($res_produto); $i++) {
	$produto[$i] = @pg_result($res_produto,$i,descricao);
}
$cont_produto = $i;

//  INÍCIO DA PRIMEIRA LINHA TÍTULO  \\

//    **** Processo para montagem do quadro ****    \\
echo "<table align='center' border='0' cellspacing='1' cellpadding='5'>";
	echo "<tr class='menu_top'>\n";
		// Primeira coluna (DESCRIÇÃO DOS PRODUTOS)
		echo "<td width='105'>Produtos</td>";
		// Coluna por períodos (MESES SELECIONADO)
		$cont_data = 0;
		for(;;){
			// Verifica se a data inicial é menor que a data final
			if ($data_inicial <= $data_final){
				// Seleciona o ANO (dois digitos)
				$ano = substr($data_inicial, 2,2 );
				// Seleciona o MES (dois digitos)
				$mes = substr($data_inicial, 5,2 );
				// Troca o mês de digitos para iniciais (ex. 01 -> JAN)
				$mostra_mes[$mes];
				// Monta a STRING para listar na tela
				$mostra_periodo = $mostra_mes[$mes].'/'.$ano;
				// Lista na tela
				echo "<td width='55'>$mostra_periodo</td>";
				// Adiciona mais um mês
				$sql_mes = "Select '$data_inicial'::date + interval '1 month' as data_lista";
				$res_mes = pg_exec($con,$sql_mes);
				$vet_mes = pg_result($res_mes,0,data_lista);
				// Pega somente a data sem a hora
				$data_inicial = substr($vet_mes, 0,10 );
				$cont_data = $cont_data + 1;
			}else{
				break;
			}
		}
		// Penúltima coluna (QTD ACUMULADO)
		echo "<td width='80'>Acumulado</td>";
		// Última coluna (Porcentagem)
		echo "<td width='30'>%</td>";
	echo "</tr>";
//  FIM DA PRIMEIRA LINHA TÍTULO  \\

	// Processo para localização no banco das QTD de PRODUTOS
	$sql_geral = "
		SELECT count (tbl_produto.produto) as TOTAL
		FROM tbl_hd_chamado 
		JOIN tbl_hd_chamado_extra USING(hd_chamado)
		JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
		JOIN tbl_marca   ON tbl_marca.marca = tbl_produto.marca AND tbl_marca = $login_fabrica
		WHERE data >= '$dta_inicial' AND data <= '$dta_final'
		AND tbl_marca.nome = '$marca'
		AND tbl_hd_chamado.fabrica = $login_fabrica
		AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica";
	$res_geral   = @pg_exec($con,$sql_geral);
	$total_geral = @pg_result($res_geral,0,TOTAL);

	//  INÍCIO DO PREENCHIMENTO DA TABELA COM DADOS  \\
	//  Laço principal feito pelo número de PRODUTOS
	for ($i = 0; $i < $cont_produto; $i++) {
		// Determina a cor do GRID
		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F9F9F9";
		// determino a linha da tabela
		echo "<tr class='table_line' bgcolor='$cor'>";
		// Mostra o motivo
		echo "<td nowrap>$produto[$i]</td>";

		// Variável para conta de datas (INICIAL E FINAL)
		$dta_inicio_mes = substr($dta_inicial, 0,10 )." 00:00:00";

		$porcentagem = 0;
		$acumulado   = 0;
		// Laço para somar a qtd mês a mês
		for($x=1; $x <= $cont_data; $x++){

			// Verifica se a data inicial é menor que a data final
			$sql_monta     = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista ";
			$res_monta     = pg_exec($con, $sql_monta);
			$vet_monta     = pg_result($res_monta,0,data_lista);
			$sql_monta_fim = "Select '$vet_monta'::date - interval '1 day' as data_lista";
			$res_monta_fim = pg_exec($con,$sql_monta_fim);
			$vet_monta_fim = pg_result($res_monta_fim,0,data_lista);
			$dta_final_mes = substr($vet_monta_fim, 0,10)." 23:59:59";

			// Query para localização do TOTAL POR PRODUTO
			$sql_total = "
						SELECT count (tbl_produto.descricao) as TOTAL
						FROM tbl_hd_chamado 
							JOIN tbl_hd_chamado_extra USING(hd_chamado)
							JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
							JOIN tbl_marca   ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = $login_fabrica
						WHERE data >= '$dta_inicio_mes' AND data <= '$dta_final_mes'
							AND tbl_hd_chamado.fabrica = $login_fabrica
							AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							AND tbl_produto.descricao = '{$produto[$i]}'
							AND tbl_marca.nome = '$marca'
							GROUP BY tbl_produto.descricao
							ORDER BY tbl_produto.descricao";
			$res_total = @pg_exec($con,$sql_total);
			$total = @pg_result($res_total,0,TOTAL);
			echo "<td align='right'>";
				if (strlen($total ) > 0){
					$tot_total += $total;
					echo $total;
				}
			echo "&nbsp;</td>\n";
			// Soma os totais
			$acumulado = $acumulado + $total;
			// Adiciona mais um mês
			$sql_mes = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista";
			$res_mes = pg_exec($con,$sql_mes);
			$vet_mes = pg_result($res_mes,0,data_lista);
			// Pega somente a data sem a hora
			$dta_inicio_mes = substr($vet_mes, 0,10 )." 00:00:00";
		}
		// Mostra o total acumulado e a porcentagem
		echo "<td align='right'>$acumulado</td>";
		// Faz a conta da Porcentagem
		if ($total_geral > 0) {
			$porcentagem = $acumulado/$total_geral*100;
			$porcentagem = number_format($porcentagem,2);
		}else{
			$porcentagem = 0;
		}
		echo "<td align='right'>$porcentagem</td>\n";
	}
//  FINAL DO PREENCHIMENTO DA TABELA COM DADOS  \\
	echo "<tr class='menu_top'>\n";
		//  *** PROCESSO DE TOTALIZAÇÃO DE VALORES ***  \\
		echo "<td width='105'>Total geral</td>";
		$dta_inicio_mes = substr($dta_inicial, 0,10 )." 00:00:00";
		$qtd_mes = 0;
		$total_mes = 0;
		for($x=1; $x <= $cont_data; $x++){
			// Verifica se a data inicial é menor que a data final
			$sql_monta     = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista ";
			$res_monta     = pg_exec($con, $sql_monta);
			$vet_monta     = pg_result($res_monta,0,data_lista);
			$sql_monta_fim = "Select '$vet_monta'::date - interval '1 day' as data_lista";
			$res_monta_fim = pg_exec($con,$sql_monta_fim);
			$vet_monta_fim = pg_result($res_monta_fim,0,data_lista);
			$dta_final_mes = substr($vet_monta_fim, 0,10)." 23:59:59";
			// Query para localização do TOTAL POR MOTIVO
			$sql_total_mes = "
						SELECT count(tbl_produto.descricao) as TOTAL
						FROM tbl_hd_chamado 
							JOIN tbl_hd_chamado_extra USING(hd_chamado)
 							JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
                                                        JOIN tbl_marca   ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = $login_fabrica
						WHERE data >= '$dta_inicio_mes' AND data <= '$dta_final_mes'
							AND tbl_marca.nome = '$marca'
							AND tbl_hd_chamado.fabrica = $login_fabrica
							AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica";
			$res_total_mes = @pg_exec($con,$sql_total_mes);
			$total_mes = @pg_result($res_total_mes,0,TOTAL);
			// Adiciona mais um mês
			$sql_mes = "Select '$dta_inicio_mes'::date + interval '1 month' as data_lista";
			$res_mes = pg_exec($con,$sql_mes);
			$vet_mes = pg_result($res_mes,0,data_lista);
			// Pega somente a data sem a hora
			$dta_inicio_mes = substr($vet_mes, 0,10 )." 00:00:00";
			// Variável para acumular o total geral
			echo "<td width='55'>$total_mes</td>";
			$qtd_mes = $qtd_mes + $total_mes;
		}
		echo "<td width='80'>$qtd_mes</td>";
		echo "<td width='30'>100%</td>";
	echo "</tr>";
echo"</table>";
?>
<br>
<? include "rodape.php"; ?>
