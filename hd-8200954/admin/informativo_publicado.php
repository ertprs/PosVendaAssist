<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

$title = "INFORMATIVOS PUBLICADOS";

//Definir aki o numero de registros por pagina para paginaÃ§Ã£o
$registros_por_pagina = 20;

if(!isset($_GET["pagina"])){
	$pagina_atual = 1;
	
	}
else{
	$pagina_atual = intval($_GET["pagina"]);
}

include_once "cabecalho.php";
require_once("../telecontrol_oo.class.php");

echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";
echo "<link href='informativo_publicado.css' rel='stylesheet' type='text/css'>";

	
// Lista informativos 
$grupo_listar_informativos = new grupo ("Informativos", $campos_telecontrol[$login_fabrica], "Consulta");


	$base_sql = "
	SELECT
	[campos]
	
	FROM
	tbl_informativo
	
	WHERE
	publicar IS NOT NULL
	AND fabrica={$login_fabrica}
	";
	
	// conta qunatidade de registro para paginação
	$sql_cont = str_replace("[campos]", "COUNT(*)", $base_sql);
	$res = pg_query($con, $sql_cont);
	$total_registros = pg_fetch_result($res, 0, 0);
	
	if ($total_registros > 0) {
		$total_paginas = ceil($total_registros / $registros_por_pagina);
		if ($pagina_atual <= 0) $pagina_atual = 1;
		if ($pagina_atual > $total_paginas) $pagina_atual = $total_paginas;
		$offset = $registros_por_pagina * ($pagina_atual-1);
		
		//busca os registros
		$sql = str_replace("[campos]", "informativo, titulo, TO_CHAR(publicar, 'DD/MM/YYYY HH24:MI') AS publicar", $base_sql);
		$sql .= "ORDER BY publicar LIMIT {$registros_por_pagina} OFFSET {$offset}";
		@$res = pg_query($con, $sql);
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao listar informativo.<erro msg='".pg_last_error($con)."'>");
		

		$tabela .= "
			<table align='center' width='690' cellspacing='1' class='tabela'>
			<tr align='center'>
			<th align='left'>Título do Informativo</th>
			<th align='left'>Data Publicação</th>
			</tr>";
			
		while ($linha = pg_fetch_array($res) ){ 
		
			if($cont%2==0)
			$cor = "#F7F5F0";
			else
			$cor= "#F1F4FA";		
				
			$tabela .= "
			<tr align='left' bgcolor='{$cor}'>
			<td align='left' class='td_titulo'><a href='informativo_email.php?informativo={$linha['informativo']}'>{$linha['titulo']}</a></td>
			<td align='left' class='td_publicado'>{$linha['publicar']}</td>
			</tr>";
			$cont++;
		} 
			$tabela .= "</table>";

		$grupo_listar_informativos->set_html_before($tabela);
		
		
	echo "<center>";


	$grupo_listar_informativos->draw();


	//exibe a paginação se o resultado for > 20
	if($total_registros > $registros_por_pagina){
		if($pagina_atual<=1){
			echo "Anterior ";
		}else{
			$anterior = $pagina_atual-1;
			echo "<a href='informativo_publicado.php?pagina={$anterior}{$condicoes_paginacao}'>Anterior </a> ";
			}
		if ($total_paginas > 1){
			for ($i=1;$i<=$total_paginas;$i++){ 
			if ($pagina_atual == $i){
				echo $pagina_atual . " "; 
			}else{ 
				echo "<a href='informativo_publicado.php?pagina={$i}{$condicoes_paginacao}'>" . $i . "</a> "; 
				}	
			}
		}
		if($pagina_atual==$total_paginas){
			echo " Próxima";
		}else{
			$proxima = $pagina_atual+1;
			echo "<a href='informativo_publicado.php?pagina={$proxima}{$condicoes_paginacao}'> Próxima</a> ";
			}
	}else{
			echo "";
			}
	}
	else {
		$msg_erro = "Não existem informativos publicados no momento";
		echo "<center><div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
	}
		
include_once "rodape.php";

?>
