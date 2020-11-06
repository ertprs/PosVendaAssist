<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';

	$sql = "SELECT mao_de_obra,
					produto,
					referencia,
					descricao,
					linha,
					familia,
					valores_adicionais
			FROM tbl_produto 
			WHERE fabrica_i = $login_fabrica 
			order by descricao";
	$resSubmit =	pg_query($con,$sql);
	$count = pg_num_rows($resSubmit);
	//echo $sql;

if ($_GET["gerar_excel"] =='t'){
	$fp = fopen ("/tmp/tabela-de-mao-de-obra-$login_posto-$login_fabrica.xls","w");
		//fputs ($fp,"");
			 fputs ($fp,"<table> <tr> <th>Referencia </th>  <th> Descricao do Produto </th>  <th> Mao de Obra </th> ");
			 	if (isset($usaCalculoKM)){
			 		fputs ($fp,"<th> Valores Adicionais </th>");
			 	}
			 	fputs ($fp,"</tr>");

				for ($i = 0; $i < $count; $i++) {
					$mao_de_obra           	= pg_fetch_result($resSubmit,$i,'mao_de_obra');
			        $produto           		= pg_fetch_result($resSubmit,$i,'produto');
			        $referencia           	= pg_fetch_result($resSubmit,$i,'referencia');
			        $descricao           	= pg_fetch_result($resSubmit,$i,'descricao');
			        $linha           		= pg_fetch_result($resSubmit,$i,'linha');
			        $familia           		= pg_fetch_result($resSubmit,$i,'familia');
			        $campos_adicionais 		= json_decode(pg_fetch_result($resSubmit, $i, "valores_adicionais"), true);
			        
			     	$sqlexc  ="SELECT  adicional_mao_de_obra,
			         					percentual_mao_de_obra,
			         					mao_de_obra
									FROM tbl_excecao_mobra
									WHERE fabrica = {$login_fabrica} 
									AND ( posto = {$login_posto} OR posto is null )
									AND produto = {$produto}
			         	";
			         	//echo $sqlexc;//exit;
			         	
			     	$resexc =	pg_query($con,$sqlexc);

			     	if (pg_num_rows($resexc)==0){
			     		$sqlli  ="SELECT  		adicional_mao_de_obra,
				             					percentual_mao_de_obra,
				             					mao_de_obra
					 						FROM tbl_excecao_mobra
					 						WHERE fabrica = {$login_fabrica}
					 						AND ( posto = {$login_posto} OR posto is null )
					 						AND linha = {$linha}
					             	";
								//echo $sqlli;//exit;
				     	$resexc =	pg_query($con,$sqlli);

				        	if (pg_num_rows($resexc)==0){
				        		$sqladc  ="SELECT  adicional_mao_de_obra,
					             					percentual_mao_de_obra,
					             					mao_de_obra
					 						FROM tbl_excecao_mobra
					 						WHERE fabrica = {$login_fabrica} 
					 						AND ( posto = {$login_posto} OR posto is null )
					 						AND familia = {$familia}
					             	";
					             //echo $sqladc;//exit;
				     			$resexc =	pg_query($con,$sqladc);
				     		}

			     	}
			     	if (pg_num_rows($resexc)>0) {

			         	$adicional_mao_de_obra           	= pg_fetch_result ($resexc,0,adicional_mao_de_obra);
				        $percentual_mao_de_obra           	= pg_fetch_result ($resexc,0,percentual_mao_de_obra);
				        $assume_valor          				= pg_fetch_result ($resexc,0,mao_de_obra);
				        if ($assume_valor > 0){
				        	$mao_de_obra = $assume_valor;
				        }
				        //var_dump($percentual_mao_de_obra);
				        if (isset($adicional_mao_de_obra)){
				        	$mao_de_obra += $adicional_mao_de_obra;
				        }
				        if (isset($percentual_mao_de_obra)){
				        	$mao_de_obra += (($percentual_mao_de_obra/100) * $mao_de_obra) ;
				        }

			     	}
			     
			     fputs ($fp,"<tr><th>".strtoupper($referencia)."</th><th>".strtoupper($descricao)."</th>");
			     fputs ($fp,"<th>".$mao_de_obra."</th>");
			   	 if (isset($usaCalculoKM)){
				     if (isset($campos_adicionais)) {
						fputs ($fp,"<th>");
						foreach ($campos_adicionais as $key => $value) {
							if (!is_array($value)) {
								$campos_adicionais[$key] = ($value);
								fputs ($fp," {$key} : {$value}");
							}
						}
						fputs ($fp,"</th>");
					}else{
						fputs ($fp,"<th>0</th>");
					}
				}
				fputs ($fp,"</tr>");	

		}
	fputs ($fp,"</table>");	
		
		
	if (isset($usaCalculoKM)){
	fputs ($fp,"<table><tr>");	
	
			fputs ($fp,"<th>Valor por KM </th>");
			fputs ($fp,"<th>Adicionais </th> </tr>");

    	$sql = "SELECT valores_adicionais ,
    				valor_km
    			from tbl_fabrica
    			where fabrica = {$login_fabrica}";
		$resca =	pg_query($con,$sql);
		//echo $sql;
		$valor_km_fabrica 				= pg_fetch_result($resca, 0, "valor_km");
		$campos_adicionais 		= json_decode(pg_fetch_result($resca, 0, "valores_adicionais"), true);
		
		$sql_km = " SELECT valor_km
					FROM tbl_posto_fabrica
					WHERE fabrica = {$login_fabrica}
					AND posto = {$login_posto}
		";
		$res =	pg_query($con,$sql_km);
		$valor_km_posto 				= pg_fetch_result($res, 0, "valor_km");
		$valor_km = ($valor_km_posto > 0 ) ? $valor_km_posto : $valor_km_fabrica ;
		
		fputs ($fp,"<tr><th>".$valor_km."</th> <th>");

		foreach ($campos_adicionais as $key => $value) {
			if (is_array($value)) {
				$campos_adicionais[$key] = ($value);
				fputs ($fp," {$key} = {$value['valor']}  ");
			}

		}
		if ( $login_fabrica == 125 ) {
			fputs ($fp,"	TROCA DO MOTOR : R$ 30,00 
							TROCA DO TRILHO :  R$ 30,00  
							TROCA DE PEÇAS : R$ 15,00 
							VISITA TECNICA : R$ 15,00		
				");
		}
		fputs ($fp,"</th></tr>");

	}
	fputs ($fp,"</table>");
	fclose ($fp);
	flush();
}

$title = traduz('tabela.de.mão.de.obra', $con);
$layout_menu = 'os';
include "cabecalho.php";

?>

<? include "javascript_pesquisas_novo.php"; 
?>
<script type="text/javascript" src="js/jquery-latest.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type="text/javascript">

	var traducao = {
		informar_parte_para_pesquisa: '<?=traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con)?>',
		aguarde_submissao:			  '<?=traduz('aguarde.submissao', $con, $cook_idioma)?>',
	}
</script>

<style>
<?php 
include  "admin/bootstrap/css/bootstrap.css"; 
include  "admin/bootstrap/css/ajuste.css"; 
include  "admin/bootstrap/css/extra.css"; 
?>

</style>


<br><br>
<table id="relatorio_mao_de_obra" class='table table-striped table-bordered table-hover table-large'>
	<thead>
		<tr class='titulo_tabela'>
			<th colspan=100%>Mão de obra por produto</th>
		</tr>
		<tr class="titulo_coluna">
			<th colspan="1">Referencia</th>
			<th colspan="1">Produto</th>
			<th colspan="1">Mão de Obra</th>
		<?php if (isset($usaCalculoKM)){ 
		echo "<th colspan='1'>Serviço por produto</th>";
		} ?>
		</tr>
	</thead>
	<tbody>
		

		<?php 
			for ($i = 0; $i < $count; $i++) {
				
					$mao_de_obra           	= pg_fetch_result($resSubmit,$i,'mao_de_obra');
			        $produto           		= pg_fetch_result($resSubmit,$i,'produto');
			        $referencia           	= pg_fetch_result($resSubmit,$i,'referencia');
			        $descricao           	= pg_fetch_result($resSubmit,$i,'descricao');
			        $linha           		= pg_fetch_result($resSubmit,$i,'linha');
			        $familia           		= pg_fetch_result($resSubmit,$i,'familia');
			        $campos_adicionais 		= json_decode(pg_fetch_result($resSubmit, $i, "valores_adicionais"), true);
			        
			     	$sqlexc  ="SELECT  adicional_mao_de_obra,
			         					percentual_mao_de_obra,
			         					mao_de_obra
									FROM tbl_excecao_mobra
									WHERE fabrica = {$login_fabrica} 
									AND ( posto = {$login_posto} OR posto is null )
									AND produto = {$produto}
			         	";
			         	//echo $sqlexc;//exit;
			         	
			     	$resexc =	pg_query($con,$sqlexc);

			     	if (pg_num_rows($resexc)==0){
			     		$sqlli  ="SELECT  		adicional_mao_de_obra,
				             					percentual_mao_de_obra,
				             					mao_de_obra
					 						FROM tbl_excecao_mobra
					 						WHERE fabrica = {$login_fabrica}
					 						AND ( posto = {$login_posto} OR posto is null )
					 						AND linha = {$linha}
					             	";
								//echo $sqlli;//exit;
				     	$resexc =	pg_query($con,$sqlli);

				        	if (pg_num_rows($resexc)==0){
				        		$sqladc  ="SELECT  adicional_mao_de_obra,
					             					percentual_mao_de_obra,
					             					mao_de_obra
					 						FROM tbl_excecao_mobra
					 						WHERE fabrica = {$login_fabrica} 
					 						AND ( posto = {$login_posto} OR posto is null )
					 						AND familia = {$familia}
					             	";
					             //echo $sqladc;//exit;
				     			$resexc =	pg_query($con,$sqladc);
				     		}

			     	}
			     	if (pg_num_rows($resexc)>0) {

			         	$adicional_mao_de_obra           	= pg_fetch_result ($resexc,0,adicional_mao_de_obra);
				        $percentual_mao_de_obra           	= pg_fetch_result ($resexc,0,percentual_mao_de_obra);
				        $assume_valor          				= pg_fetch_result ($resexc,0,mao_de_obra);
				        if ($assume_valor > 0){
				        	$mao_de_obra = $assume_valor;
				        }
				        //var_dump($percentual_mao_de_obra);
				        if (isset($adicional_mao_de_obra)){
				        	$mao_de_obra += $adicional_mao_de_obra;
				        }
				        if (isset($percentual_mao_de_obra)){
				        	$mao_de_obra += (($percentual_mao_de_obra/100) * $mao_de_obra) ;
				        }

			     	}
			     
			     echo "	<tr>
			     			<td class='tac'>".strtoupper($referencia)."</td>
			     			<td class='tal'>".strtoupper($descricao)."</td>
			     			";
			     echo "		<td class='tac'>  R$ ".number_format($mao_de_obra,2,',','.')."</td>";
			    if (isset($usaCalculoKM)){
				     if (isset($campos_adicionais)) {
						
						echo "<td class='tac'> ";
						foreach ($campos_adicionais as $key => $value) {
							if (!is_array($value)) {
								$campos_adicionais[$key] = ($value);
								echo " {$key} :  R$ {$value} <br />";
							}
						}
						echo "</td>";
					}else{
						echo "<td class='tac'> R$ 0,00</td>";
					}
				}
				echo "</tr>";	
		}
     	?>

		
	</tbody>
</table>
<?php 

	echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo "<tr>";
	if ($login_fabrica <> 128 ){
	echo "<td align='center' ><font  face='Arial, Verdana, Times, Sans' size='4' color='red'><h4>Os extratos sempre serão gerados dia 1 de cada mês.</h4></font> </td>";
	}elseif($login_fabrica == 128){
		echo "<td align='center' ><font  face='Arial, Verdana, Times, Sans' size='4' color='red'><h4>Os extratos sempre serão gerados dia 25 de cada mês.</h4></font> </td>";
	}

	echo "</tr>";
	echo "</table>";



// OBIMEX= 1  		114 | COBIMEX
// ELLO= 1      	136 | Ello
// SAINT-GOBAIN= 1  125 | SAINT-GOBAIN
// WURTH= 1      	122 | Wurth
// UNILEVER= 25     128 | Unilever
// Os extratos sempre serão gerados todo dia X de cada mês.




	if (isset($usaCalculoKM)){
		echo "<br />";
		echo "<br />";
?>


<table id="relatorio_de_km" class='table table-striped table-bordered table-hover table-large'>
	<thead>
		<tr class='titulo_tabela'>
			<th colspan="4">Valor de km e adicionais</th>
		</tr>
		<tr class="titulo_coluna">
			<th colspan="1">Valor por KM</th>
			<th colspan="1">Adicionais</th>
		</tr>
	</thead>
	<tbody>
<?php

    	$sql = "SELECT valores_adicionais ,
    				valor_km
    			from tbl_fabrica
    			where fabrica = {$login_fabrica}";
		$resca =	pg_query($con,$sql);
		//echo $sql;
		$valor_km_fabrica 				= pg_fetch_result($resca, 0, "valor_km");
		$campos_adicionais 		= json_decode(pg_fetch_result($resca, 0, "valores_adicionais"), true);
		
		$sql_km = " SELECT valor_km
					FROM tbl_posto_fabric
					WHERE fabrica = {$login_fabrica}
					AND posto = {$login_posto}
		";
		$res =	pg_query($con,$sql_km);
		$valor_km_posto 				= pg_fetch_result($res, 0, "valor_km");
		$valor_km = ($valor_km_posto > 0 ) ? $valor_km_posto : $valor_km_fabrica ;
		
		echo "<td class='tac'>  R$ ".number_format($valor_km,2,',','.')."</td> 
				
				<td class='tac'>";

		foreach ($campos_adicionais as $key => $value) {
			if (is_array($value)) {
				$campos_adicionais[$key] = ($value);
				echo " {$key} : R$ {$value['valor']}  ";
			}
			echo " <br />";
		}
		if ( $login_fabrica == 125 ) {
			echo "	TROCA DO MOTOR : R$ 35,00 <br />
					TROCA DO TRILHO :  R$ 35,00 <br /> 
					TROCA DE PEÇAS : R$ 25,00 <br />
					VISITA TECNICA : R$ 30,00 <br />	
				";
		}
			
    echo " </td> 
    	</tbody>
	</table>
    ";

}	 



if ($_GET["gerar_excel"] =='t'){
		echo `mv  /tmp/tabela-de-mao-de-obra-$login_posto-$login_fabrica.xls xls/tabela-de-mao-de-obra-$login_posto-$login_fabrica.xls`;
		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/tabela-de-mao-de-obra-$login_posto-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em XLS</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
}

?>		
	<input type="button" name="btn_acao" value="Gerar Excel" onclick="javascript: location.href='relatorio_mao_obra.php?gerar_excel=t';" style='cursor:pointer;'>



<?php 
include "rodape.php"; ?>
