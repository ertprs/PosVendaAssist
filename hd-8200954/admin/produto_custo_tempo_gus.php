<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "Custo Tempo por Produto";

include 'cabecalho.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
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


<?
$familia = $_POST["familia"];
	echo "<FORM name='frm' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
	echo "<table width='350' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>";

	echo "<tr >";
	echo "<td class='Titulo' background='imagens_admin/azul.gif'>Custo Tempo Cadastrados</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td bgcolor='#DBE5F5'>";

		echo "<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>";

		echo "<tr>";
		echo "<td colspan='4'>Selecione a família dos produtos que deseja visualizar os seus respectivos custo-tempo<br><br></td>";
		echo "</tr>";

		echo "<tr width='100%' >";
		echo "<td colspan='2'  align='right' height='20'>Família:&nbsp;</td>";
		echo "<td colspan='2'>";
		##### INÍCIO FAMÍLIA #####
		$sql = "SELECT  *
				FROM    tbl_familia
				WHERE   tbl_familia.fabrica = $login_fabrica
				ORDER BY tbl_familia.descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select CLASS='Caixa' style='width: 280px;' name='familia'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_familia = trim(pg_result($res,$x,familia));
				$aux_descricao  = trim(pg_result($res,$x,descricao));

				echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
		##### FIM FAMÍLIA #####
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	echo "</td>";
	echo "</tr>";


	echo "</table>";
	echo "<center><br><input type='submit' name='btn_gravar' value='Consultar'><input type='hidden' name='acao' value=$acao></center>";
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

		echo "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>";


		$sql2 = "SELECT descricao,codigo FROM tbl_defeito_constatado WHERE fabrica = 20 AND ativo IS TRUE ORDER BY descricao";
		$res2 = pg_exec ($con,$sql2);

		$total_defeito = pg_numrows($res2);
		for($x=0 ; $x < $total_defeito ; $x++){
			if($x == 0){
				$colunas = $total_defeito + 1;
				echo "<tr class='Titulo'>";
				echo "<td colspan='$colunas'background='imagens_admin/azul.gif' height='20'><font size='2'>TOTAL DE OS'S ABERTAS SEM LANÇAMENTO DE PEÇAS</font></td>";
				echo "</tr>";

				echo "<tr class='Titulo'>";
				echo "<td >PRODUTO</td>";
			}

			$defeito[$x] = pg_result($res2,$x,descricao);

			echo "<td class='Mes'>$defeito[$x]</td>";
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

		while($defeito_descricao <> $defeito[$x]){ //repete o lup até que o mes seja igual e anda um mes.
			$x=$x+1;
		};

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
		echo "<br><table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='90%'><tr><td class='Exibe'>";
		echo "<b>Nenhuma Unidade tempo Cadastrada";
		echo "</td></tr></table>";
	}
}
include 'rodape.php';
?>
