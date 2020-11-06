<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["busca"]) > 0) $busca = trim($_POST["busca"]);

if (strlen($_GET["exibir"]) > 0)  $exibir = trim($_GET["exibir"]);
if (strlen($_POST["exibir"]) > 0) $exibir = trim($_POST["exibir"]);

$title = " F O R U M ";
$layout_menu = 'tecnica';

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

input.busca {
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	padding: 0px,0px,0px,0px;
}

</style>

<br>

<table width='700px' border='0' cellpadding='0' cellspacing='3' align='center'>
<tr>
	<td>
		<img src='imagens/forum_logo.gif'>
	</td>
	<td>
		<p class='texto'>
Bien venido! Aquí usted podrá cambiar informaciones con otros servicios autorizados, sanar sus dudas, contactar técnicos que ya trataron inquietudes semejantes a las suyas.

		<br />
Es muy práctico y sencillo utilizar. Basta crear un nuevo tópico o contestar a uno ya existente. Participe!
		</p>
	</td>
</tr>
<tr>
	<td valign='top'>
		<table width='150px' border='0' cellpadding='0' cellspacing='3' valign='top' align='center'>
		<tr>
			<td>
				<img src='imagens/forum_home.gif'>
			</td>
			<td>
				<a href='forum.php' class='menu'>PANTALLA INICIAL</a>
			</td>
		</tr>
		<tr>
			<td colspan='2' class='texto'>
				<FORM NAME='frm_busca' METHOD=POST ACTION="forum.php">
				<input type='hidden' name='exibir' value='todos'>
				<BR> BÚSQUEDA <BR>
					<INPUT TYPE="text" NAME="busca" size='13' class='busca'>&nbsp;<INPUT TYPE="submit" name='ok' value='OK' class='busca'>
				</FORM>
			</td>
		</tr>
		</table>
	</td>
	<td>
		<?
			// seleciona os dados
			// 1º) 5 últimos tópicos cadastrados
			// 2º) todos os tópicos cadastrados
			// 3º) busca
			// 4º) se não encontrar resultados, mostra mensagem de "Nenhum tópico encontrado."
			// paginação
			
			if (strlen($exibir) == 0) {
					$sql = "SELECT      tbl_forum.forum_pai              ,
										tbl_forum.titulo                 ,
										tbl_posto.nome      AS nome_posto,
										tbl_admin.login     AS nome_admin,
										count(*) AS post                 ,
										to_char(tbl_forum.data,'DD/MM/YYYY HH24:MI') AS data
							FROM        tbl_forum
							JOIN        tbl_forum forum_pai  on forum_pai.forum_pai       = tbl_forum.forum
							LEFT JOIN   tbl_admin            on tbl_admin.admin           = tbl_forum.admin
							LEFT JOIN   tbl_posto            on tbl_posto.posto           = tbl_forum.posto
							LEFT JOIN   tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
															and tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE       tbl_forum.liberado is true
							AND         tbl_forum.fabrica = $login_fabrica
							AND ( tbl_posto.pais = '$login_pais' OR tbl_admin.pais = '$login_pais')
							GROUP BY    tbl_forum.forum_pai ,
										tbl_forum.titulo    ,
										tbl_posto.nome      ,
										tbl_admin.login     ,
										tbl_forum.data
							ORDER BY    tbl_forum.data DESC
							LIMIT 10";
				$res = @pg_exec ($con,$sql);
			}else{
				if (strlen($busca) == 0) {
					$sql = "SELECT      tbl_forum.forum_pai              ,
										tbl_forum.titulo                 ,
										tbl_posto.nome      AS nome_posto,
										tbl_admin.login     AS nome_admin,
										count(*) AS post                 ,
										to_char(tbl_forum.data,'DD/MM/YYYY HH24:MI') AS data
							FROM        tbl_forum
							JOIN        tbl_forum forum_pai  on forum_pai.forum_pai       = tbl_forum.forum
							LEFT JOIN   tbl_admin            on tbl_admin.admin           = tbl_forum.admin
							LEFT JOIN   tbl_posto            on tbl_posto.posto           = tbl_forum.posto 
							LEFT JOIN   tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
															and tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE       tbl_forum.liberado is true
							AND         tbl_forum.fabrica = $login_fabrica
							AND         tbl_posto.pais    = '$login_pais'
							GROUP BY    tbl_forum.forum_pai ,
										tbl_forum.titulo    ,
										tbl_posto.nome      ,
										tbl_admin.login     ,
										tbl_forum.data
							ORDER BY    tbl_forum.data DESC";
				}else{
					$sql = "SELECT      tbl_forum.forum_pai              ,
										tbl_forum.titulo                 ,
										tbl_posto.nome      AS nome_posto,
										tbl_admin.login     AS nome_admin,
										count(*) AS post                 ,
										to_char(tbl_forum.data,'DD/MM/YYYY HH24:MI') AS data
							FROM        tbl_forum
							JOIN        tbl_forum forum_pai  on forum_pai.forum_pai       = tbl_forum.forum
							LEFT JOIN   tbl_admin            on tbl_admin.admin           = tbl_forum.admin
							LEFT JOIN   tbl_posto            on tbl_posto.posto           = tbl_forum.posto
							LEFT JOIN   tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
															and tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE       tbl_forum.liberado is true
							AND         tbl_forum.fabrica = $login_fabrica
							AND         tbl_posto.pais    = '$login_pais'
							AND         (tbl_forum.titulo ILIKE '%$busca%' OR tbl_forum.mensagem ILIKE '%$busca%')
							GROUP BY    tbl_forum.forum_pai ,
										tbl_forum.titulo    ,
										tbl_posto.nome      ,
										tbl_admin.login     ,
										tbl_forum.data
							ORDER BY    tbl_forum.data DESC";
				}
				
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
			}
			
			echo "<table width='550px' border='0' cellpadding='0' cellspacing='3' align='center'>";
			if (@pg_numrows($res) > 0) {
				if ($exibir == "todos") {
					echo "<tr>";
					echo "<td class='texto' colspan='4'>";
					
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
				}
				echo "<tr class='forum_cabecalho'>";
				
				echo "<td>TOPICO</td>";
				echo "<td>AUTOR</td>";
				echo "<td>POSTS</td>";
				echo "<td>ÚLTIMO POST</td>";
				
				echo "</tr>";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					$forum  = trim(pg_result($res,$i,forum_pai));
					$titulo = trim(pg_result($res,$i,titulo));
					$autor  = trim(pg_result($res,$i,nome_posto));
					if (strlen($autor) == 0) $autor = trim(pg_result($res,$i,nome_admin));
					$post   = trim(pg_result($res,$i,post));
					$data   = trim(pg_result($res,$i,data));
					
					echo "<tr class='forum_claro'>";
					
					echo "<td align='left' nowrap><a href='forum_mensagens.php?forum=$forum' class='forum'>$titulo</a></td>";
					echo "<td align='left' nowrap><div class='forum'>".strtoupper($autor)."</div></td>";
					echo "<td><div class='forum'>$post</div></td>";
					echo "<td><div class='forum'>$data</div></td>";
					
					echo "</tr>";
				}
			}elseif (@pg_numrows($res) == 0 AND strlen($busca) > 0){
				echo "<tr>";
				
				echo "<td class='texto' colspan='4'>";
				
				echo "<p class='texto'>";
				echo "NO ENCUENTRAMOS NINGUNA OCURRIENCIA CON LA PALAVRA <b><font color='#CC0000'>'" . strtoupper($busca) ."'</font></b>";
				echo "</p>";
				
				echo "</td>";
				
				echo "</tr>";
			}else{
				echo "<tr>";
				
				echo "<td class='texto' colspan='4'>";
				
				echo "<p class='texto'>";
				echo "FÓRUM SIN MENSAJES";
				echo "</p>";
				
				echo "</td>";
				
				echo "</tr>";
			}
		?>
		
		<tr>
			<td bgcolor='#ffcc00' align='center' nowrap>
				<font face='arial' size='2' color='#000000'><a href='<? echo "$PHP_SELF?exibir=todos"; ?>'>Consultar todos los tópicos</a></font>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td bgcolor='#ffcc00' align='center' nowrap>
				<font face='arial' size='2' color='#000000'><a href='forum_post.php'>Registrar nuevo tópico</a></font>
			</td>
		</tr>
	</table>
	</td>
</tr>

</table>

<br>
<br>

<? include "rodape.php"; ?>