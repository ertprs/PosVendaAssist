<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$seleciona = $_GET['sel'];

?>

<style type="text/css">

body {
	text-align: center;

		}

.cabecalho {

	color: black;
	border-bottom: 2px dotted WHITE;
	font-size: 12px;
	font-weight: bold;
}

.descricao {
	padding: 5px;
	color: black;
	font-size: 10px;
	font-weight: normal;
	text-align: justify;
}


/*========================== MENU ===================================*/

a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}
.Titulo{
	font:Geneva, Arial, Helvetica, san-serif;
	text-align: center;
	font-size:10px;
	font-weight: bold;
}
.Conteudo{
	font:Geneva, Arial, Helvetica, san-serif;
	font-size:9px;
}

<link type="text/css" rel="stylesheet" href="css/css.css">
<link type="text/css" rel="stylesheet" href="css/tc.css">

A:hover {color:#247BF0; }
img     { border:0px;   }

.links {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: xx-small;
	font-weight: normal;
	color:#596d9b;
	}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 16px "Arial";
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
</style>

<?
	if($seleciona == 3){
		$dias = 3;
	}elseif($seleciona == 5){
		$dias = 5;
	}elseif($seleciona == 15){
		$dias = 15;
	}elseif($seleciona == 23){
		$dias = 23;
	}elseif($seleciona == 25){
		$dias = 25;
	}elseif($seleciona == 30){
		$dias = 30;
	}

	$extraCond = '';
	if ($login_fabrica == 15){
		$extraCond = "AND tbl_os.os not in (select distinct os from tbl_os_status where status_os in (120, 122, 123, 126) and os = tbl_os.os  and (select status_os from tbl_os_status where status_os in (120, 122, 123, 126) and os = tbl_os.os order by data desc limit 1) = 120)";
	}

	$sql_principal = "SELECT tbl_os.os                  ,
								tbl_os.sua_os           ,
								tbl_os.tipo_atendimento ,
								tbl_os.data_abertura    ,
								tbl_os.data_fechamento  ,
								tbl_os.produto
							INTO TEMP TABLE temp_risco_" . $login_fabrica . "_" . $login_posto . "
							FROM tbl_os
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_os.posto   = $login_posto
							AND   tbl_os.excluida IS NOT TRUE $extraCond";

	$sql_continuacao = " AND   tbl_os.data_fechamento IS NULL;
						CREATE INDEX temp_risco_" . $login_fabrica . "_" . $login_posto . "_os ON temp_risco_" . $login_fabrica . "_" . $login_posto . " (os);
						SELECT os                                               ,
								sua_os                                          ,
								tipo_atendimento                                ,
								LPAD(sua_os,10,'0')                 AS os_ordem ,
								TO_CHAR(data_abertura,'DD/MM/YY')   AS abertura ,
								tbl_produto.produto                             ,
								tbl_produto.referencia                          ,
								tbl_produto.descricao                           ,
								tbl_produto.voltagem
							FROM temp_risco_" . $login_fabrica . "_" . $login_posto . "
							JOIN tbl_produto ON tbl_produto.produto = temp_risco_" . $login_fabrica . "_" . $login_posto . ".produto
							LEFT JOIN tbl_os_produto using(os)
							WHERE data_abertura <= CURRENT_DATE - INTERVAL '$dias days'
								AND coalesce(tbl_os_produto.os_produto,null) is null
								ORDER BY os_ordem;";

if(in_array($login_fabrica, array(1,2,11,15,51,81,91))){
	/**
 	 *
	 * HD 749695 - Latinatec não listar OSs em auditoria (há mais de 60 dias) *
	 */

	$sql = "SELECT codigo_posto
				FROM tbl_posto_fabrica
				WHERE posto = $login_posto
				AND fabrica = $login_fabrica;";
	$res = pg_exec($con,$sql);

	$codigo_posto   = trim(pg_result($res,0,codigo_posto));

	if($seleciona == '5'){
		if($login_fabrica == 91) {
			$sql_principal .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '5 days' ";
		}
				
		$sql_principal .= $sql_continuacao;
		$res = pg_exec($con,$sql_principal);
		$contador_res = pg_numrows($res);

		if ($contador_res > 0) {
			echo "<table width='500' align = 'center' class='tabela'>";
			echo "<tr  height='15' height='30' class='titulo_tabela'>";
			echo "<td colspan='3' height='30'>";
			if($login_fabrica <> 11 and $login_fabrica <> 51){
				if($sistema_lingua == "ES") {
					echo "&nbsp;OS SIN FECHA DE CIERRE HACE 5 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
				}else{
					echo "&nbsp;OS SEM DATA DE FECHAMENTO HÁ 5 DIAS OU MAIS DA DATA DE ABERTURA&nbsp;";
				}
			}

			echo "<br><font color='#FFFF00'>";
			if($sistema_lingua == "ES") {
				echo "";
			}else{
				echo "Perigo de PROCON conforme artigo 18 do C.D.C.";
			}
			echo "</font></td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna' height='15'>";
			echo "<td>OS</td>";
			echo "<td>Abertura</td>";
			echo "<td>";
			if($sistema_lingua == "ES") {
				echo "Producto";
			}else{
				echo "Produto";
			}
			echo "</td>";
			echo "</tr>";
			
			for($a = 0; $a < $contador_res; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$tipo_atendimento = trim(pg_result($res,$a,tipo_atendimento));
				$abertura         = trim(pg_result($res,$a,abertura));
				$produto          = trim(pg_result($res,$a,produto));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));


				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = pg_exec($con,$sql_idioma);
				$contar_idioma = pg_numrows($res_idioma);
				if ($contar_idioma > 0) {
					$descricao  = trim(pg_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				$produto_completo = $referencia . " - " . $descricao;

				echo "<tr height='15' bgcolor='$cor'>";
				echo "<td align='center'>";
				echo "<a href='os_item.php?os=$os'>";
								
				if(strlen($sua_os)==0)echo $os;
				else                  echo "$sua_os";
				echo "</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";

				if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

				echo "</tr>";
			}
			echo "<tr>";
				echo "<td class='titulo_coluna' colspan='3' align='center'>Total OS´s: $a</td>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";
		}
	}

	if($seleciona == '15'){

		if($login_fabrica == 11 or $login_fabrica == 51) {
			$sql_principal .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '30 days' ";
		}else{
			$sql_principal .= " AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '15 days'";
		}

		$sql_principal .= $sql_continuacao;
		$res = pg_exec($con,$sql_principal);
		$contador_res = pg_numrows($res);

		if ($contador_res > 0) {
			echo "<table width='500' align = 'center' class='tabela'>";
			echo "<tr  height='15' height='30' class='titulo_tabela'>";
			echo "<td colspan='3' height='30'>";
			if($login_fabrica <> 11 and $login_fabrica <> 51){
				if($sistema_lingua == "ES") {
					echo "&nbsp;OS SIN FECHA DE CIERRE HACE 15 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
				}else{
					echo "&nbsp;OS SEM DATA DE FECHAMENTO HÁ 15 DIAS OU MAIS DA DATA DE ABERTURA&nbsp;";
				}
			}elseif($login_fabrica == 11 or $login_fabrica == 51){ //HD 52453
				echo "&nbsp;OS PENDENTES A MAIS DE 30 DIAS&nbsp;";
			}	
			echo "<br><font color='#FFFF00'>";
			if($sistema_lingua == "ES") {
				echo "";
			}else{
				echo "Perigo de PROCON conforme artigo 18 do C.D.C.";
			}
			echo "</font></td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna' height='15'>";
			echo "<td>OS</td>";
			echo "<td>Abertura</td>";
			echo "<td>";
			if($sistema_lingua == "ES") {
				echo "Producto";
			}else{
				echo "Produto";
			}
			echo "</td>";
			echo "</tr>";
			
			for($a = 0; $a < $contador_res; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$tipo_atendimento = trim(pg_result($res,$a,tipo_atendimento));
				$abertura         = trim(pg_result($res,$a,abertura));
				$produto          = trim(pg_result($res,$a,produto));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));


				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = pg_exec($con,$sql_idioma);
				$contador_idioma = pg_numrows($res_idioma);
				if ($contador_idioma >0) {
					$descricao  = trim(pg_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				$produto_completo = $referencia . " - " . $descricao;

				echo "<tr height='15' bgcolor='$cor'>";
				echo "<td align='center'>";
				if ($login_fabrica == 3) {
					echo "<a href='os_press.php?os=$os' target='_new'>";
				}else{
					if ($login_fabrica == 1 AND ($tipo_atendimento=="17" OR $tipo_atendimento=='18')){
						echo "<a href='os_press.php?os=$os'>";
					}else{
						echo "<a href='os_item.php?os=$os'>";
					}
				}
				if($login_fabrica==1)echo $codigo_posto;
				if(strlen($sua_os)==0)echo $os;
				else                  echo "$sua_os";
				echo "</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";

				if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

				echo "</tr>";
			}
			echo "<tr>";
				echo "<td class='titulo_coluna' colspan='3' align='center'>Total OS´s: $a</td>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";
		}
	}
	if($seleciona == '30'){
		if($login_fabrica == 15) {
			$sql_principal .= " AND tbl_os.consumidor_revenda = 'C' ";
		}

		$sql_principal .= $sql_continuacao;			
		$res = pg_exec($con,$sql_principal);
		$contador_res = pg_numrows($res);

		if ($contador_res > 0) {
			echo "<table width='500' align='center' class='tabela'>";
			echo "<tr class='titulo_tabela' height='15'>";
			echo "<td colspan='3'>";
			if($sistema_lingua == "ES") echo "OS QUE EXCEDERAN EL PLAZO LIMITE DE 30 DÍAS PARA CIERRE";
			else                        echo "OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO";
			echo "<br><font color='#FFFF00'>";
			if($sistema_lingua == "ES") echo "Clique em la OS para informar el motivo";
			else                        echo "Clique na OS para informar o Motivo";
			echo "</font></td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna' height='15' bgcolor='#FF0000' >";
			echo "<td>OS</td>";
			echo "<td>Abertura</td>";
			echo "<td>";
			if($sistema_lingua == "ES") echo "Producto";
			else                        echo "Produto";
			echo "</td>";
			echo "</tr>";
			
			for($a = 0; $a < $contador_res; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$abertura         = trim(pg_result($res,$a,abertura));
				$produto          = trim(pg_result($res,$a,produto));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));
				$voltagem         = trim(pg_result($res,$a,voltagem));
//				$codigo_posto   = trim(pg_result($res,$a,codigo_posto));
				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$descricao  = trim(@pg_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "<tr height='15' bgcolor='$cor'>";
				echo "<td align='center'><a href='os_motivo_atraso.php?os=$os' target='_blank'>";
				if($login_fabrica==1)echo $codigo_posto;
				if(strlen($sua_os)==0)echo $os;
				else                  echo $sua_os;
				"</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";
				if ($sistema_lingua=='ES') echo "<td><acronym title='Referencia: $referencia\nDescripción: $descricao\nVoltaje: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td><acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				echo "</tr>";
			}
			echo "<tr>";
				echo "<td class='titulo_coluna' colspan='3' align='center'>Total OS´s: $a</td>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";
		}
	}
}

if($seleciona == 15 AND $login_fabrica == 86){
	$dias = 3;
}

$sql_principal_2 = "SELECT tbl_os.os            ,
						tbl_os.sua_os           ,
						tbl_os.tipo_atendimento ,
						tbl_os.data_abertura    ,
						tbl_os.produto
					INTO TEMP TABLE temp_risco2_" . $login_fabrica . "_" . $login_posto . "
					FROM tbl_os
					WHERE tbl_os.fabrica     = $login_fabrica
					AND tbl_os.posto         = $login_posto
					AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '$dias days'
					AND tbl_os.data_fechamento IS NULL
					AND tbl_os.excluida is not true;

					CREATE INDEX temp_risco2_".$login_fabrica . "_" . $login_posto . "_os ON temp_risco2_" . $login_fabrica . "_" . $login_posto . " (os);

					SELECT os                                             ,
							sua_os                                        ,
							tipo_atendimento                              ,
							LPAD(sua_os,10,'0') AS os_ordem               ,
							TO_CHAR(data_abertura,'DD/MM/YY') AS abertura ,
							tbl_produto.produto                           ,
							tbl_produto.referencia                        ,
							tbl_produto.descricao                         ,
							tbl_produto.voltagem
						FROM temp_risco2_" . $login_fabrica . "_" . $login_posto . "
						JOIN tbl_produto ON tbl_produto.produto = temp_risco2_" . $login_fabrica . "_" . $login_posto . ".produto
						LEFT JOIN tbl_os_produto using(os)
						WHERE data_abertura <= CURRENT_DATE - INTERVAL '$dias days'
						AND coalesce(tbl_os_produto.os_produto,null) is null
						ORDER BY os_ordem;";

if(in_array($login_fabrica, array(2,35,86,91))){
	if($seleciona == '15'){

		$res = pg_exec($con,$sql_principal_2);
		$contador_res = pg_numrows($res);

		if ($contador_res > 0) {
			echo "<table width='500' align = 'center' class='tabela'>";
			echo "<tr height='30' class='titulo_coluna'>";
			echo "<td colspan='3'>";

			if($sistema_lingua == "ES") {
				echo "&nbsp;OS SIN FECHA DE CIERRE HACE $dias DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
			}else{
				echo "&nbsp;O.S's ABERTAS A MAIS DE $dias DIAS SEM LANÇAMENTO DE PEÇAS&nbsp;";
			}
			echo "<br><font color='#FFFF00'>";
			if($sistema_lingua == "ES") {
				echo "";
			}else{
				echo "Perigo de PROCON conforme artigo 18 do C.D.C.";
			}
			echo "</font></td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna' height='15' bgcolor='#FF0000'>";
			echo "<td>OS</td>";
			echo "<td>Abertura</td>";
			echo "<td>";
			if($sistema_lingua == "ES") {
				echo "Producto";
			}else{
				echo "Produto";
			}
			echo "</td>";
			echo "</tr>";
			
			for($a = 0; $a < $contador_res; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$tipo_atendimento = trim(pg_result($res,$a,tipo_atendimento));
				$abertura         = trim(pg_result($res,$a,abertura));
				$produto          = trim(pg_result($res,$a,produto));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));


				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$descricao  = trim(@pg_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				$produto_completo = $referencia . " - " . $descricao;

				echo "<tr height='15' bgcolor='$cor'>";
				echo "<td align='center'>";
				echo "<a href='os_item.php?os=$os' target='blank_'>";

				if(strlen($sua_os)==0) echo $os;
				else                  echo "$sua_os";
				echo "</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";

				if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

				echo "</tr>";
			}
			if($login_fabrica == 86){
			echo "<tr class='titulo_coluna'>
				<td colspan='3'>
					Obs.: Caso a OS não necessite de troca de peças, por favor informe a peça em que foi feito algum reparo ou manutenção e selecione o tipo de ajuste realizado
				</td>
			  </tr>";
		}
			echo "<tr>";
				echo "<td class='titulo_coluna' colspan='3' align='center'>Total OS´s: $a</td>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";			
		}
	}

	if($seleciona == '23'){
		$res = pg_exec($con,$sql_principal_2);
		$contador_res = pg_numrows($res);

		if ($contador_res > 0) {
			echo "<table width='500' align = 'center' class='tabela'>";
			echo "<tr height='30' class='titulo_tabela'>";
			echo "<td colspan='3'>";
			if($sistema_lingua == "ES") {
				echo "&nbsp;OS SIN FECHA DE CIERRE HACE 15 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
			}else{
				echo "&nbsp;O.S's ABERTAS A MAIS DE 23 DIAS SEM DATA DE FECHAMENTO, INDEPENDENTE DO LANÇAMENTO DE PEÇAS&nbsp;";
			}
			echo "<br><font color='#FFFF00'>";
			if($sistema_lingua == "ES") {
				echo "";
			}else{
				echo "Perigo de PROCON conforme artigo 18 do C.D.C.";
			}
			echo "</font></td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna' height='15'>";
			echo "<td>OS</td>";
			echo "<td>Abertura</td>";
			echo "<td>";
			if($sistema_lingua == "ES") {
				echo "Producto";
			}else{
				echo "Produto";
			}
			echo "</td>";
			echo "</tr>";
			
			for($a = 0; $a < $contador_res; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$tipo_atendimento = trim(pg_result($res,$a,tipo_atendimento));
				$abertura         = trim(pg_result($res,$a,abertura));
				$produto          = trim(pg_result($res,$a,produto));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));

				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$descricao  = trim(@pg_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				$produto_completo = $referencia . " - " . $descricao;

				echo "<tr height='15' bgcolor='$cor'>";
				echo "<td align='center'>";
				echo "<a href='os_item.php?os=$os' target='blank_'>";

				if(strlen($sua_os)==0) echo $os;
				else                  echo "$sua_os";
				echo "</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";

				if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

				echo "</tr>";
			}
			echo "<tr>";
				echo "<td class='titulo_coluna' colspan='3' align='center'>Total OS´s: $a</td>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";
		}
	}
	
	if($seleciona == '25'){
		$res = pg_exec($con,$sql);
		$contador_res = pg_numrows($res);

		if ($contador_res > 0) {
			echo "<table width='500' align = 'center' class='tabela'>";
			echo "<tr height='30' class='titulo_tabela'>";
			echo "<td colspan='3'>";
			if($sistema_lingua == "ES") {
				echo "&nbsp;OS SIN FECHA DE CIERRE HACE 15 DÍAS O MÁS DE LA FECHA DE ABERTURA&nbsp;";
			}else{
				echo "&nbsp;O.S's ABERTAS A MAIS DE 23 DIAS SEM DATA DE FECHAMENTO, INDEPENDENTE DO LANÇAMENTO DE PEÇAS&nbsp;";
			}
			echo "<br><font color='#FFFF00'>";
			if($sistema_lingua == "ES") {
				echo "";
			}else{
				echo "Perigo de PROCON conforme artigo 18 do C.D.C.";
			}
			echo "</font></td>";
			echo "</tr>";
			echo "<tr class='titulo_coluna' height='15'>";
			echo "<td>OS</td>";
			echo "<td>Abertura</td>";
			echo "<td>";
			if($sistema_lingua == "ES") {
				echo "Producto";
			}else{
				echo "Produto";
			}
			echo "</td>";
			echo "</tr>";
			
			for($a = 0; $a < $contador_res; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$tipo_atendimento = trim(pg_result($res,$a,tipo_atendimento));
				$abertura         = trim(pg_result($res,$a,abertura));
				$produto          = trim(pg_result($res,$a,produto));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));

				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$descricao  = trim(@pg_result($res_idioma,0,descricao));
				}
				//--=== Tradução para outras linguas ================================================

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				$produto_completo = $referencia . " - " . $descricao;

				echo "<tr height='15' bgcolor='$cor'>";
				echo "<td align='center'>";
				echo "<a href='os_item.php?os=$os' target='blank_'>";

				if(strlen($sua_os)==0) echo $os;
				else                  echo "$sua_os";
				echo "</a></td>";
				echo "<td align='center'>" . $abertura . "</td>";

				if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

				echo "</tr>";
			}
			echo "<tr>";
				echo "<td class='titulo_coluna' colspan='3' align='center'>Total OS´s: $a</td>";
			echo "</tr>";
			echo "</table>";
			echo "<br>";
		}
	}
}
?>
