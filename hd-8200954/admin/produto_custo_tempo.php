<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "CUSTO TEMPO POR PRODUTO/FAMÍLIA";

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

.espaco td{
	padding:10px 0 10px;
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
</style>


<?
$familia = $_POST["familia"];
	echo "<FORM name='frm' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
	echo "<table width='700' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>";
	
	echo "<tr >";
	echo "<td class='titulo_tabela' >Parâmetros de Pesquisa</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td bgcolor='#DBE5F5'>";
	
		echo "<table width='100%' border='0' cellspacing='1' cellpadding='0' class='Conteudo'>";
	
		echo "<tr width='100%' >";
		echo "<td colspan='2' align='center' height='20' style='padding:15px 0 15px;'>Família&nbsp;";
		##### INÍCIO FAMÍLIA #####
		$sql = "SELECT  *
				FROM    tbl_familia
				WHERE   tbl_familia.fabrica = $login_fabrica
				ORDER BY tbl_familia.descricao;";
		$res = pg_exec ($con,$sql);
	
		if (pg_numrows($res) > 0) {
			echo "<select class='frm' style='width: 280px;' name='familia'>\n";
			echo "<option value=''>ESCOLHA</option>\n";
	
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_familia = trim(pg_result($res,$x,familia));
				$aux_descricao  = trim(pg_result($res,$x,descricao));
	
				echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
			}
			echo "</select><br />\n";
		}
		##### FIM FAMÍLIA #####
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	echo "</td>";
	echo "</tr>";
	
	echo "<tr>
			<td colspan='2' align='center' style='padding-bottom:10px;'>
				<input type='submit' name='btn_gravar' value='Consultar'>
				<input type='hidden' name='acao' value=$acao>
			</td>
		</tr>";	
	echo "</table>";
	echo "</form>";

if(strlen($familia)>0){

	$sql = "SELECT   tbl_produto.produto                                                    ,
			tbl_produto.referencia                        AS produto_referencia    ,
			tbl_produto.descricao                         AS produto_descricao     ,
			tbl_defeito_constatado.defeito_constatado                              ,
			tbl_defeito_constatado.codigo                 AS defeito_codigo        ,
			tbl_defeito_constatado.descricao              AS defeito_descricao     ,
			tbl_produto_defeito_constatado.mao_de_obra                             ,
			tbl_produto_defeito_constatado.unidade_tempo
			FROM tbl_produto_defeito_constatado
			LEFT JOIN tbl_produto            USING(produto)
			LEFT JOIN tbl_defeito_constatado USING(defeito_constatado) 
			WHERE tbl_produto_defeito_constatado.unidade_tempo IS NOT NULL 
			AND   tbl_produto_defeito_constatado.mao_de_obra   IS NOT NULL
			AND   tbl_produto.familia            = $familia
			AND   tbl_defeito_constatado.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao,tbl_produto.produto,tbl_defeito_constatado.descricao ;";
	
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
	
		echo "<br><table border='1' cellpadding='0' cellspacing='1' class='tabela' align='center'>";


		$sql2 = "SELECT descricao,codigo FROM tbl_defeito_constatado WHERE fabrica = 20 AND ativo IS TRUE ORDER BY descricao";
		$res2 = pg_exec ($con,$sql2);
	
		$total_defeito = pg_numrows($res2);

		for($x=0 ; $x < $total_defeito ; $x++){
			if($x == 0){
				$colunas = $total_defeito + 1;
				echo "<tr>";
				echo "<td class='titulo_tabela' colspan='100%'>Total de OS Abertas sem Lançamento de Peças</td>";

				echo "</tr>";
		
				echo "<tr>";
				echo "<td class='titulo_coluna'>Produto</td>";
			}	

			$defeito[$x] = pg_result($res2,$x,descricao);
			
			echo "<td class='titulo_coluna'>$defeito[$x]</td>";
		}

		echo "</tr>";

	$x = 0;
	$y = 0;

	
	$qtde_defeito =  array();

	$total_mes = 0;
	$total_ano = 0;

	$produtos_total = 0;

	for ($i=0; $i<pg_numrows($res); $i++){

		$produto            = trim(pg_result($res,$i,produto))          ;
		$produto_descricao  = trim(pg_result($res,$i,produto_descricao));

		if($produto_anterior<>$produto){
			for($x=0 ; $x < $total_defeito ; $x++){
				$qtde_defeito[$produtos_total][$x]  = 0;
				
			}
			$qtde_defeito[$produtos_total][$total_defeito] = $produto_descricao;
			$produto_anterior = $produto;
			$produtos_total   = $produtos_total + 1;
		}
	}


	for ($i=0; $i<pg_numrows($res); $i++){

		$produto            = trim(pg_result($res,$i,produto))           ;
		$produto_referencia = trim(pg_result($res,$i,produto_referencia));
		$produto_descricao  = trim(pg_result($res,$i,produto_descricao)) ;
		$defeito_descricao  = trim(pg_result($res,$i,defeito_descricao)) ;
		$unidade_tempo      = trim(pg_result($res,$i,unidade_tempo))     ;
		$mao_de_obra        = trim(pg_result($res,$i,mao_de_obra))       ;


		if($produto_anterior<>$produto){

//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO PRODUTO
			if($i<>0 AND $produto_anterior<>$produto ){

				for($a=0;$a<$total_defeito;$a++){
					echo "<td bgcolor='$cor' title='".$mes[$a]."'>";

					if ($qtde_defeito[$y][$a]>0) echo "<font color='#000000'><b>".$qtde_defeito[$y][$a];
					else                         echo "<font color='#999999'> ";

					echo "</td>";
					if($a==($total_defeito-1))echo "</tr>";
				}

				$y=$y+1;// usado para indicação de produto
			}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' width='150'  height = '40'><a href='#' class='Mes'>$produto_referencia - $produto_descricao</a></td>";

			$x=0; //ZERA OS MESES
			$produto_anterior=$produto; 
		}

		/*while($defeito_descricao <> $defeito[$x]){ //repete o lup até que o mes seja igual e anda um mes.
			$x=$x+1;
		};*/ //loop infinito

		if(trim($defeito_descricao) == $defeito[$x]){
			$qtde_defeito[$y][$x] = $unidade_tempo .'<br>R$' .number_format($mao_de_obra,2,',','.') ;
		}

		$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor
		if($i==(pg_numrows($res)-1)){
			for($a=0;$a<$total_defeito;$a++){			//imprime os doze meses
				echo "<td bgcolor='$cor' title='".$defeito[$a]."'>";

				if ($qtde_defeito[$y][$a]>0) echo "<font color='#000000'><b>".$qtde_defeito[$y][$a];
				else                         echo "<font color='#999999'> ";

				echo "</td>";
				if($a==($total_defeito-1))echo "</tr>";
			}
		
		}

	}
	echo "</table>";

	}else{
		echo "<br>Nenhuma Unidade tempo Cadastrada";
	}
}
include 'rodape.php';
?>
