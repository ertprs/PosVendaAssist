<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


echo "<html>";
echo "<head>";
echo "<title>Compra Manual Para Gama</title>";
echo '<link type="text/css" rel="stylesheet" href="css/css.css">';
?>
<style type='text/css' screen='all'>
.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
	padding: 3px 0;
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
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
		padding: 2px;
}

.espaco{padding:0 0 0 80px; }
</style>
<?
echo "<script src='js/jquery.js'></script>";
echo "</head>";
echo "<body>";

include 'menu.php' ;
    function comparaSelect($valor1, $valor2){
        $valor1 == $valor2 ? $select = " selected style='color: #f00' " : $select = "";	
	
	    return $select;
    }
    
    $url = array_reverse(explode("/",$_SERVER['PHP_SELF']));
    $res_fabrica = pg_exec($con,"SELECT fabrica, nome FROM tbl_fabrica WHERE fabrica = 51 OR fabrica = 81");
    
        echo "<br><br><table align='center' class='formulario' width='700px' border='0'>";
            echo"<tr><td class='titulo_tabela'>Filtro de Fábrica</td></tr>";
			echo "<tr>";
			    echo "<td style='padding: 20px; text-align: center; font-weight: normal;'>";
                    $fabrica = $_POST['fabrica'];
                    if(pg_numrows($res_fabrica)){
                        echo "<form action='{$url[0]}' method='post'>";
                        echo "Fábrica &nbsp;";
                        echo "<select name='fabrica' class='frm' style='width: 350px;'>";
                            echo "<option value='0' ".comparaSelect(0,$fabrica).">Todos</option>";
                            while($dado_fabrica = pg_fetch_array($res_fabrica))
                                echo "<option value='{$dado_fabrica[0]}' ".comparaSelect($dado_fabrica[0],$fabrica).">{$dado_fabrica[1]}</option>";
                            echo "</select>";
                        echo "<br><br><input type='submit' value=' Filtrar Dados ' />";
                        echo "</form>";
                    } 
                echo "</td>";
			echo "</tr>";
		echo "</table>";

	echo "<br>";
	// Fim Ederson

if($fabrica == 0)
    $sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido.*  FROM x_pedido JOIN tbl_peca USING (peca) ORDER BY x_pedido.dias30 DESC";
else
    $sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido.*  FROM x_pedido JOIN tbl_peca USING (peca) WHERE x_pedido.fabrica = {$fabrica} ORDER BY x_pedido.dias30 DESC";

$res  = pg_exec ($con,$sql);

if(pg_numrows ($res)==0){
	echo "<br><center><b>Não existem peças para compra manual</center></b>";
}else{

	echo "<script>
            function ocultar_mostrar(){ 
                $('.ocultar').toggle();
            } 
		</script>";

	echo "<table id='tabela' align='center' border='0' cellspacing='1' cellpaddin='1' class='tabela'>";
    echo "<tr><td colspan='16' style='text-align: right; padding: 2px; border: none;'><a href='#' class='mostrar' id='campos_mostrar' onclick='ocultar_mostrar();' >MOSTRAR / OCULTAR CAMPOS DE CÁLCULOS</a></td></tr>";
	echo "<tr class='titulo_tabela'>";
	    echo "<td colspan='16'>Peças para Compra Manual</td>";
	echo "</tr>";

	echo "<tr class='titulo_coluna'>";
	    echo "<td rowspan='2' valign='middle'>Referência</td>";
	    echo "<td rowspan='2' valign='middle'>Descrição</td>";
	    echo "<td rowspan='2' valign='middle'>Comprar</td>";
	    echo "<td class='ocultar' colspan='8'>Cálculos</td>";
	    echo "<td rowspan='2' valign='middle'>Mínimo</td>";
	    echo "<td rowspan='2' valign='middle'>Pedido</td>";
	    echo "<td rowspan='2' valign='middle'>Estoque</td>";
	    echo "<td rowspan='2' valign='middle'>Fábrica</td>";
	    echo "<td rowspan='2' valign='middle'>Transp.</td>";
	echo "</tr>";
	
	echo "<tr class='titulo_coluna'>";
	    echo "<td class='ocultar'>90d</td>";
	    echo "<td class='ocultar'>60d</td>";
	    echo "<td class='ocultar'>30d</td>";
	    echo "<td class='ocultar'>maior</td>";
	    echo "<td class='ocultar'>qtdeX</td>";
	    echo "<td class='ocultar'>media</td>";
	    echo "<td class='ocultar'>desvio</td>";
	    echo "<td class='ocultar'>media OK</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		echo "<tr bgcolor='$cor'>";

		    echo "<td title='Referência'>";				
		        echo pg_result ($res,$i,referencia);
		    echo "</td>";

		    echo "<td title='Descrição'>";
		        echo pg_result ($res,$i,descricao);
		    echo "</td>";

		$estoque_minimo = round (pg_result ($res,$i,media_ok) * pg_result ($res,$i,qtdeX) / 2 , 0);
		$qtde_comprometida = $estoque_minimo + pg_result ($res,$i,qtde);
		$qtde_vindo = pg_result ($res,$i,estoque) + pg_result ($res,$i,fabrica) + pg_result ($res,$i,transp) ;
		$comprar = $qtde_comprometida - $qtde_vindo ;

		if ($comprar > 0) $cor = "#FF9999";

		echo "<td align='right' bgcolor='$cor' title='comprar'>";
		echo $comprar ;
		echo "</td>";




		echo "<td align='right' title='90d' bgcolor='#0099FF' class='ocultar'>";
		echo pg_result ($res,$i,dias90);
		echo "</td>";

		echo "<td align='right' title='60d' bgcolor='#0099FF' class='ocultar'>";
		echo pg_result ($res,$i,dias60);
		echo "</td>";

		echo "<td align='right' title='30d' bgcolor='#0099FF' class='ocultar'>";
		echo pg_result ($res,$i,dias30);
		echo "</td>";

		echo "<td align='right' title='maior' class='ocultar'>";
		echo pg_result ($res,$i,maior);
		echo "</td>";

		echo "<td align='right' title='qtdex' class='ocultar'>";
		echo pg_result ($res,$i,qtdex);
		echo "</td>";

		echo "<td align='right' title='media' class='ocultar'>";
		echo pg_result ($res,$i,media);
		echo "</td>";

		echo "<td align='right' title='desvio' class='ocultar'>";
		echo pg_result ($res,$i,desvio);
		echo "</td>";

		echo "<td align='right' title='media OK' class='ocultar'>";
		echo pg_result ($res,$i,media_ok);
		echo "</td>";

		echo "<td align='right' title='Estoque Mínimo'>";
		echo $estoque_minimo;
		echo "</td>";

		echo "<td align='right' title='Pedido' bgcolor='#33CC00'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td align='right' title='Estoque'>";
		echo pg_result ($res,$i,estoque);
		echo "</td>";

		echo "<td align='right' title='Fábrica'>";
		echo pg_result ($res,$i,fabrica);
		echo "</td>";

		echo "<td align='right' title='Transp.'>";
		echo pg_result ($res,$i,transp);
		echo "</td>";


		echo "</tr>";
	}

	echo "</table>";
	flush();
}


?>



<? include "rodape.php"; ?>

</body>
</html>
