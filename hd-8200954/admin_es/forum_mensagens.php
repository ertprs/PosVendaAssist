<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = " F O R U M ";
$layout_menu = 'tecnica';

if (strlen($_POST["forum"]) > 0) $forum = trim($_POST["forum"]);
if (strlen($_GET["forum"]) > 0)  $forum = trim($_GET["forum"]);

include "cabecalho.php";
?>

<style type='text/css'>

.forum_cabecalho {
	padding: 5px;
	background-color: #FFCC00;
	font-family: arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	text-align: center;
	}

.texto {
	padding: 5px;
	font-family: arial;
	font-size: 12px;
	font-weight: bold;
	color: #596D9B;
	text-align: justify;
	}

.corpo {
	padding: 5px;
	font-family: arial;
	font-size: 12px;
	color: #596D9B;
	text-align: justify;
	}

.forum_claro {
	padding: 3px;
	background-color: #CED7E7;
	color: #596D9B;
	text-align: center;
	}


.forum_escuro {
	padding: 3px;
	background-color: #D9E2EF;
	color: #596D9B;
	text-align: center;
	}

a:link.menu {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
}

a:visited.menu {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.menu {
	color: #FFCC00;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
}

a:link.forum {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:visited.forum {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.forum {
	color: #0000FF;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:link.botao {
	padding: 20px,20px,20px,20px;
	background-color: #ffcc00;
	color: #000000;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:visited.botao {
	padding: 20px,20px,20px,20px;
	background-color: #ffcc00;
	color: #000000;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.botao {
	padding: 20px,20px,20px,20px;
	background-color: #596d9b;
	color: #ffffff;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

</style>
<br>
	<table width='700px' border='0' cellpadding='0' cellspacing='3'>
		<tr>
			<td valign='top'>
				<table width='150px' border='0' cellpadding='0' cellspacing='3' valign='top'>
					<tr>
						<td><img src='imagens/forum_home.gif'></td>
						<td><a href='forum.php' class='menu'>PANTALLA INICIAL</a></td>
					</tr>
					<tr>
						<td colspan='2' class='texto'>
						<FORM NAME='frm_busca' METHOD=POST ACTION="forum.php">
						<input type='hidden' name='exibir' value='todos'>
						
						<BR> BÚSQUEDA  <BR>
						<INPUT TYPE="text" NAME="busca" size='13' class='busca'>&nbsp;<INPUT TYPE="submit" name='ok' value='OK' class='busca'>
						</FORM>
						</td>
					</tr>
				</table>
			</td>
			<td>
				<table width='550px' border='0' cellpadding='0' cellspacing='3'>
					<tr>
						<input type='hidden' name='forum' value='$forum'>
						<td>
						<?
						$sql = "SELECT      tbl_forum.titulo                                    ,
											to_char(tbl_forum.data,'DD/MM/YYYY HH24:MI') AS data,
											tbl_forum.titulo                                    ,
											tbl_forum.mensagem                                  ,
											tbl_posto.nome  AS nome_posto                       ,
											tbl_admin.login AS nome_admin                       
								FROM        tbl_forum
								LEFT JOIN   tbl_admin            on tbl_admin.admin           = tbl_forum.admin
								LEFT JOIN   tbl_posto            on tbl_posto.posto           = tbl_forum.posto 
								LEFT JOIN   tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
																and tbl_posto_fabrica.fabrica = $login_fabrica
								WHERE       tbl_forum.liberado is true
								AND         tbl_forum.fabrica   = $login_fabrica
								AND         tbl_forum.forum_pai = $forum
								AND ( tbl_posto.pais = '$login_pais' OR tbl_admin.pais = '$login_pais')";
						
						$sqlCount  = "SELECT count(*) FROM (";
						$sqlCount .= $sql;
						$sqlCount .= ") AS count";
						
						// ##### PAGINACAO ##### //
						require "_class_paginacao.php";
						
						// definicoes de variaveis
						$max_links = 11;                    // máximo de links à serem exibidos
						$max_res   = 20;                    // máximo de resultados à serem exibidos por tela ou pagina
						$mult_pag  = new Mult_Pag();        // cria um novo objeto navbar
						$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página
						
						$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
						
						// ##### PAGINACAO ##### //
						
						if (pg_numrows($res) > 0) {
							echo "<tr>";
							echo "<td class='texto'>";
							
							// ##### PAGINACAO ##### //
							
							if($pagina < $max_links) { 
								$paginacao = pagina + 1;
							}else{
								$paginacao = pagina;
							}
							
							// paginacao com restricao de links da paginacao
							
							// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
							$todos_links = $mult_pag->Construir_Links("strings", "sim");
							
							// função que limita a quantidade de links no rodape
							$links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);
							
							for ($n = 0; $n < count($links_limitados); $n++) {
								echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
							}
							
							$resultado_inicial = ($pagina * $max_res) + 1;
							$resultado_final   = $max_res + ( $pagina * $max_res);
							$registros         = $mult_pag->Retorna_Resultado();
							
							$valor_pagina   = $pagina + 1;
							$numero_paginas = intval(($registros / $max_res) + 1);
							
							if ($valor_pagina == $numero_paginas) $resultado_final = $registros;
							
							if ($registros > 0){
								echo "<br>";
								echo "<div>";
								echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
								echo "<font color='#cccccc' size='1'>";
								echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
								echo "</font>";
								echo "</div>";
							}
							
							// ##### PAGINACAO ##### //
							echo "</td>";
							
							echo "</tr>";
							
							$titulo =  trim(pg_result($res,0,titulo));
							
							echo "<table width='550px' border='0' cellpadding='0' cellspacing='3'>";
							
							echo "<tr class='forum_cabecalho'>";
							echo "	<td>$titulo</td>";
							echo "</tr>";
							
							for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
								$nome_posto = trim(pg_result($res,$i,nome_posto));
								if (strlen($nome_posto) == 0) $nome_posto = trim(pg_result($res,$i,nome_admin));
								$titulo     = trim(pg_result($res,$i,titulo));
								$data       = trim(pg_result($res,$i,data));
								$mensagem   = trim(pg_result($res,$i,mensagem));
								
								echo "<tr class='forum_escuro'>";
								
								echo "<td style='text-align: left;'>";
								
								echo "<table width='100%' border='0' cellpadding='0' cellspacing='3'>";
								echo "<tr class='texto'>";
								
								echo "<td align='left'> ".strtoupper($nome_posto)." </td>";
								echo "<td align='right'> $data </td>";
								
								echo "</tr>";
								echo "<tr class='texto'>";
								
								echo "<td>$titulo</td>";
								
								echo "</tr>";
								echo "<tr class='corpo'>";
								
								echo "<td colspan='3'>$mensagem</td>";
								
								echo "</tr>";
								
								echo "</table>";
								echo "</td>";
								
								echo "</tr>";
							}
							echo "<tr>";
							
							echo "<td align='right'><a href='forum_post.php?forum_pai=$forum' class='botao'>Responder el Tópico</a></td>";
							
							echo "</tr>";
							
							echo "</table>";
						}else{
							echo "<center><h1>Tópico no encuentrado!!!</h1></center>";
						}
							?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

<? include "rodape.php"; ?>
