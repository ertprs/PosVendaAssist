<?php
/**
 *
 * relatorio_tempo_os_aberta.php
 *
 * HD 253580
 *
 * Listará todas as OS que estão abertas (Não finalizada), tendo algumas modificações em colunas:
 *
 *  - Até 03 dias
 *  - Até 07 dias
 *  - Mais que 07 dias
 *  - Total de OS
 *
 */

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

if ($trava_cliente_admin) {
	$admin_privilegios="call_center";
	$layout_menu = "callcenter";
} else {
	$admin_privilegios="gerencia";
	$layout_menu = "gerencia";
}

include_once 'autentica_admin.php';

$title = "RELATÓRIO DE TEMPO DE OS ABERTAS";

$excel = $_GET["excel"];

if ($excel)
	ob_start();
else
	include_once 'cabecalho.php';

$mes = date('m');
$ano = date('Y');
$data_referencia = "current_date";

$meses = array(
				1 => "Janeiro",
					"Fevereiro",
					"Março",
					"Abril",
					"Maio",
					"Junho",
					"Julho",
					"Agosto",
					"Setembro",
					"Outubro",
					"Novembro",
					"Dezembro"
				);
?>

<style type="text/css">

.Relatorio {
	border-collapse: collapse;
	border: 2px solid #95bce2;
	/*width: 650px;*/
	font-size: 1.1em;
	font-family: Verdana;
	font-size: 11px;
}

.Relatorio thead {
	background: #596D9B;
	color: #fff;
	font-weight: bold;
	padding: 1px 11px;
	text-align: left;
	border-right: 1px solid #fff;
	line-height: 1.2;
	font-family: Verdana;
	font-size: 11px;
}
.Relatorio tfoot {
	background: #DDDDDD;

}
.Relatorio td {
	padding: 1px 5px;
	border-bottom: 1px solid #95bce2;
	font-family: Verdana;
	font-size: 11px;
}

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

.tabela { width: 900px; }

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.total { padding-right: 10px; font-weight: bold; color: #596D9B; }

</style>

<?php
if (empty($excel))
	echo '<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />';

include_once "javascript_pesquisas.php";
?>

<script type="text/javascript" src="../js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<SCRIPT LANGUAGE="JavaScript">
function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}else {
		alert("Favor, digitar pelo menos 3 caracteres para a busca");
	}
}

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatCliente(row) {
		return row[2] + " - " + row[3] + " - Cidade: " + row[4];
	}

	$().ready(function() {
		// Busca pelo CÓDIGO DO POSTO
		$("#codigo_posto").autocomplete("relatorio_tempo_conserto_mes_ajax.php?tipo_busca=posto&busca=codigo", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#codigo_posto").result(function(event, data, formatted) {
			$("#posto_nome").val(data[1]) ;
		});

		// Busca pelo NOME DO POSTO
		$("#posto_nome").autocomplete("relatorio_tempo_conserto_mes_ajax.php?tipo_busca=posto&busca=nome", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#codigo_posto").val(data[2]) ;
			//alert(data[2]);
		});

		//Busca pelo NOME DO CLIENTE ADMIN - FRICON
		$("#cliente_nome_admin").autocomplete("relatorio_tempo_conserto_mes_ajax.php?tipo_busca=cliente_admin&busca=nome", {
			minChars: 3,
			delay: 150,
			width: 350,
			max: 30,
			matchContains: true,
			formatItem: formatCliente,
			formatResult: function(row) {
			return row[3];
			}
	});

		$("#cliente_nome_admin").result(function(event, data, formatted) {
			$("#cliente_admin").val(data[0]) ;
			$("#cliente_nome_admin").val(data[3]) ;
		});
	});

</SCRIPT>

<?
if ($trava_cliente_admin) {
	$desabilita_cliente_nome_admin = "disabled";
} else {
	$desabilita_cliente_nome_admin = "";
}

if (in_array($login_fabrica, array(52))) {
	if ($trava_cliente_admin) {
		$cliente_admin = $trava_cliente_admin;
		$sql = "SELECT nome FROM tbl_cliente_admin WHERE cliente_admin=$cliente_admin";
		$res = pg_query($con, $sql);
		$cliente_nome_admin = pg_result($res, 0, nome);
	}
}

if (isset($_POST['btn_acao']) or !empty($excel)) {
	$mes                = $_POST["mes"];
	$ano                = $_POST["ano"];
	$pais               = $_POST["pais"];
	$estado             = $_POST["estado"];
	$familia            = $_POST["familia"];
	$linha              = $_POST["linha"];
	$produto_referencia = $_POST["produto_referencia"];
	$produto_descricao  = $_POST["produto_descricao"];
	$codigo_posto       = $_POST["codigo_posto"];
	$posto_nome         = $_POST["posto_nome"];
	$cliente_admin      = $_POST["cliente_admin"];
	$cliente_nome_admin = $_POST["cliente_nome_admin"];

	if (empty($mes))                $mes                = $_GET['mes'];
	if (empty($ano))                $ano                = $_GET['ano'];
	if (empty($pais))               $pais               = $_GET['pais'];
	if (empty($estado))             $estado             = $_GET['estado'];
	if (empty($familia))            $familia            = $_GET['familia'];
	if (empty($linha))              $linha              = $_GET['linha'];
	if (empty($produto_referencia)) $produto_referencia = $_GET['produto_referencia'];
	if (empty($marca_logo)) 		$marca_logo 		= $_GET['marca_logo'];
	if (empty($produto_descricao))  $produto_descricao  = $_GET['produto_descricao'];
	if (empty($codigo_posto))       $codigo_posto       = $_GET['codigo_posto'];
	if (empty($posto_nome))         $posto_nome         = $_GET['posto_nome'];
	if (empty($cliente_admin))      $cliente_admin      = $_GET['cliente_admin'];
	if (empty($cliente_nome_admin)) $cliente_nome_admin = $_GET['cliente_nome_admin'];

	if ($trava_cliente_admin) {
		$cliente_admin = $trava_cliente_admin;
		$sql = "SELECT nome FROM tbl_cliente_admin WHERE cliente_admin=$cliente_admin";
		$res = pg_query($con, $sql);
		$cliente_nome_admin = pg_result($res, 0, nome);
	}

	$codigo_posto_pesquisa = $codigo_posto;

	if (!empty($familia)) {
		$familia = intval($familia);
		$sql = "SELECT familia FROM tbl_familia WHERE fabrica = $login_fabrica";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro = "Família escolhida não existe";
		}
	}

	if (!empty($linha)) {
		$linha = intval($linha);
		$sql = "SELECT linha FROM tbl_linha WHERE fabrica=$login_fabrica";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro = "Linha escolhida não existe";
		}
	}

	if (!empty($produto_referencia)) {
		$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha WHERE tbl_linha.fabrica=$login_fabrica AND tbl_produto.referencia ilike '$produto_referencia'";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro = 'Produto ' . $produto_referencia . ' inexistente';
		}
	}

	if (!empty($codigo_posto)) {
		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica=$login_fabrica AND codigo_posto='$codigo_posto'";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro = "Posto ' . $codigo_posto inexistente";
		}
		else {
			$posto_id = pg_result($res, 0, 0);
		}
	}

	if ($data_referencia == "") {
		$data_referencia = "data_conserto";
	}

	$data_referencia = "current_date";

	if (!empty($cliente_admin)) {
		$sql = "SELECT cliente_admin FROM tbl_cliente_admin WHERE fabrica=$login_fabrica AND cliente_admin=$cliente_admin";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro = "Cliente ADM selecionado inexistente";
		}
	}

	if (empty($cliente_admin) and !empty($cliente_nome_admin)){
		$msg_erro = "Para efetuar uma busca por Cliente ADM, digite o nome desejado no campo e SELECIONE UMA OPÇÃO DA LISTA";
		$cliente_nome_admin = "";
	}
	elseif (!empty($cliente_admin) && empty($cliente_nome_admin)) {
		$cliente_admin = "";
	}

	if (!empty($mes)) {
		$mes = intval($mes);
		if ($mes < 1 || $mes > 12) {
			$msg_erro = "O mês deve ser um número entre 1 e 12";
			$mes = "";
		}
	}

	if (strlen($ano) != 4) {
		$msg_erro = "O ano deve conter 4 dígitos";
		$ano = "";
	}

	if ($mes == "" || $ano == "") {
		$msg_erro = "Selecione o mês e o ano para a pesquisa";
	}

}


?>

<?php if (empty($excel)){ ?>
	<form name='frm_percentual' action='<? echo $PHP_SELF ?>' method='post' >
	<table align='center' border='0' cellspacing='2' cellpadding='2' id='Formulario' class="formulario" width="700">
		<?php
		if (!empty($msg_erro)) {
			echo '<tr class="msg_erro"><td colspan="4">' , $msg_erro , '</td></tr>';
		}
		?>
		<tr class="titulo_tabela"><td colspan="4">Parâmetros de Pesquisa</td></tr>
		<TR>
			<td>Mês *</td>
			<TD>
				<select name="mes" size="1" class="frm">
					<option value=''></option>
					<?php
					foreach ($meses as $key => $value) {
						echo '<option value="' , $key , '"';
						if ($mes == $key) {
							echo ' selected ';
						}
						echo '>' , $value , '</option>';
					}
					?>
				</select>
			</TD>
			<td>Ano *</td>
			<TD>
				<select name="ano" size="1" class="frm">
					<option value=''></option>
					<?
					for ($i = date('Y'); $i >= 2003; $i--) {
						echo "<option value='$i'";
						if ($ano == $i) echo " selected";
						echo ">$i</option>";
					}
					?>
				</select> Data abertura
			</TD>
		</TR>
		<TR>
			<td>País</td>
			<TD>
			<?
				$sql = "SELECT  *
						FROM    tbl_pais
						ORDER BY tbl_pais.nome;";
				$res = pg_exec ($con,$sql);

				if (pg_numrows($res) > 0) {
					echo "<select name='pais' class='frm'>\n";
					if(strlen($pais) == 0 ) $pais = 'BR';

					for ($x = 0 ; $x < pg_numrows($res) ; $x++){
						$aux_pais  = trim(pg_result($res,$x,pais));
						$aux_nome  = trim(pg_result($res,$x,nome));

						echo "<option value='$aux_pais'";
						if ($pais == $aux_pais){
							echo " SELECTED ";
							$mostraMsgPais = "<br> do PAÍS $aux_nome";
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n";
				}
				?>
			</TD>
			<td>Por Região</td>
			<td>
				<select name="estado" class='frm'>
					<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
					<option value="centro-oeste" <? if ($estado == "centro-oeste") echo " selected "; ?>>Região Centro-Oeste (GO,MT,MS,DF)</option>
					<option value="nordeste"     <? if ($estado == "nordeste")     echo " selected "; ?>>Região Nordeste (MA,PI,CE,RN,PB,PE,AL,SE,BA)</option>
					<option value="norte"        <? if ($estado == "norte")        echo " selected "; ?>>Região Norte (AC,AM,RR,RO,PA,AP,TO)</option>
					<option value="sudeste"      <? if ($estado == "sudeste")      echo " selected "; ?>>Região Sudeste (MG,ES,RJ,SP)</option>
					<option value="sul"          <? if ($estado == "sul")          echo " selected "; ?>>Região Sul (PR,SC,RS)</option>
					<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
					<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
					<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
					<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
					<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
					<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
					<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
					<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
					<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
					<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
					<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
					<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
					<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
					<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
					<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
					<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
					<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
					<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
					<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
					<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
					<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
					<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
					<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
					<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
					<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
					<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
					<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
				</select>
			</TD>
		</tr>
		<tr>
			<td>Família</font></td>
			<td>
				<?
				$sqlf = "SELECT  *
						FROM    tbl_familia
						WHERE   tbl_familia.fabrica = $login_fabrica
						ORDER BY tbl_familia.descricao;";
				$resf = pg_exec ($con,$sqlf);

				if (pg_numrows($resf) > 0) {
					echo "<select class='frm' style='width:200px;' name='familia'>\n";
					echo "<option value=''>ESCOLHA</option>\n";

					for ($x = 0 ; $x < pg_numrows($resf) ; $x++){
						$aux_familia = trim(pg_result($resf,$x,familia));
						$aux_descricao  = trim(pg_result($resf,$x,descricao));

						echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
					}
					echo "</select>\n";
				}
				?>
			</td>
			<!-- HD 216470: Busca por linha -->
			<td>Linha</font></td>
			<td>
				<?php
				$sql_linha = "
				SELECT
				linha,
				nome

				FROM
				tbl_linha

				WHERE
				tbl_linha.fabrica = $login_fabrica

				ORDER BY
				tbl_linha.nome
				";
				$res_linha = pg_query($con, $sql_linha);

				echo "<select class='frm' style='width:auto;' name='linha'>\n";

				if (pg_numrows($res_linha) > 0) {
					echo "<option value=''>ESCOLHA</option>\n";

					for ($x = 0 ; $x < pg_numrows($res_linha) ; $x++){
						$aux_linha = trim(pg_result($res_linha, $x, linha));
						$aux_nome = trim(pg_result($res_linha, $x, nome));
						if ($linha == $aux_linha) {
							$selected = "SELECTED";
						}
						else {
							$selected = "";
						}

						echo "<option value='$aux_linha' $selected>$aux_nome</option>\n";
					}
				}
				else {
					echo "<option value=''>Não existem linhas cadastradas</option>\n";
				}

				echo "</select>\n";
				?>
			</td>
		</tr>
		<tr>
			<td>Referência</td>
			<td nowrap>
			<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_percentual.produto_referencia,document.frm_percentual.produto_descricao,'referencia')">
			</td>

			<td>Descrição</td>
			<td nowrap colspan="2">
			<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>">&nbsp;<img
			src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript:
			fnc_pesquisa_produto2 (document.frm_percentual.produto_referencia,document.frm_percentual.produto_descricao,'descricao')"></A>
			</td>
		</tr>
		<tr>
			<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
				<td>Cod Posto</td>
				<td>
					<input type="text" name="codigo_posto" id="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_percentual.codigo_posto, document.frm_percentual.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
					<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_percentual.codigo_posto, document.frm_percentual.posto_nome, 'codigo')">
				</td>
				<td>Posto</td>
				<td>
					<input type="text" name="posto_nome" id="posto_nome" size="30" value="<?echo $posto_nome ?>" class="frm">
					<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_percentual.codigo_posto, document.frm_percentual.posto_nome, 'nome')">
				</td>
			</tr>
		</tr>
		<tr>
			<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
				<td>Cliente ADM</td>
				<td>
					<input type='hidden' name='cliente_admin' id='cliente_admin'  value="<? echo $cliente_admin; ?>">
					<input name="cliente_nome_admin" id="cliente_nome_admin" class="frm" <? echo $desabilita_cliente_nome_admin; ?> value='<?echo $cliente_nome_admin ;?>' class='input_req' type="text" size="30" maxlength="50">
				</td>
				<?
				if ($login_fabrica == 52) {
				?>
				<td>Marca</td>
				<td>
				<select name='marca_logo' class='frm' >
					<option value=''></option>
					<?
					$sql_fricon = "SELECT marca, nome
									FROM tbl_marca
									WHERE tbl_marca.fabrica = $login_fabrica
									ORDER BY tbl_marca.nome ";

					$res_fricon = pg_query($con, $sql_fricon);
					for ($i=0; $i<pg_num_rows($res_fricon); $i++){
					echo"<option value='".pg_fetch_result($res_fricon,$i,0)."'";
					echo ">".pg_fetch_result($res_fricon,$i,1)."</option>\n";
					}?>
				</select>
				</td>
				<?
				}else{
				?>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				<?
				}
				?>
			</tr>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<input type='hidden' name='btn_acao' value=''>
			<td colspan="4" align="center"><br><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" border=0 onclick='javascript: if(frm_percentual.btn_acao.value == ""){ frm_percentual.btn_acao.value = "ok" ;frm_percentual.submit(); } else { alert("Aguarde Submissão");} '></td>
		</tr>
		</tfoot>
	</table>
	</form>

	<br>

	<?php
	flush();
}

//HD 216470: Acrescentada a validação de dados ($msg_erro)
if (strlen($mes) > 0 AND strlen($ano) > 0 && strlen($msg_erro) == 0){

	$sql = "SELECT fn_dias_mes('$ano-$mes-01',0)";
	$res3 = pg_exec($con,$sql);
	$data_inicial = pg_result($res3,0,0);

	$sql = "SELECT fn_dias_mes('$ano-$mes-01',1)";
	$res3 = pg_exec($con,$sql);
	$data_final = pg_result($res3,0,0);

	if(!empty($pais)){
		$add_1 = "AND tbl_os.posto IN (SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE tbl_posto.pais = '$pais' and fabrica=$login_fabrica)";
	}

	if(!empty($estado)) {
		$add_2 = "AND tbl_os.posto IN (SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE ";
		if($estado == "centro-oeste") $add_2 .= " tbl_posto.estado in ('GO','MT','MS','DF') ";
		if($estado == "nordeste")     $add_2 .= " tbl_posto.estado in ('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";
		if($estado == "norte")        $add_2 .= " tbl_posto.estado in ('AC','AM','RR','RO','PA','AP','TO') ";
		if($estado == "sudeste")      $add_2 .= " tbl_posto.estado in ('MG','ES','RJ','SP') ";
		if($estado == "sul")          $add_2 .= " tbl_posto.estado in ('PR','SC','RS') ";
		if(strlen($estado) == 2)      $add_2 .= " tbl_posto.estado = '$estado' ";
		if ($estado == "SP-capital") {
			$add_2 .= " tbl_posto.estado = 'SP'
                                 AND tbl_posto.cidade ~* 's.o paulo|s.o bernardo do campo|S.o Caetano do Sul|Guarulhos|Santo Andr.'";
		}
		if ($estado == "SP-interior") {
			$add_2 .= " tbl_posto.estado = 'SP'
                                 AND tbl_posto.cidade !~* 's.o paulo|s.o bernardo do campo|S.o Caetano do Sul|Guarulhos|Santo Andr.'";
		}
		$add_2 .= "  and fabrica=$login_fabrica)";
	}

	if(strlen($familia) > 0){
		$sql = "SELECT produto
				INTO TEMP temp_rtc_familia
				FROM tbl_produto PR
				JOIN tbl_familia FA USING(familia)
				WHERE FA.fabrica    = $login_fabrica
				AND   FA.familia    = $familia;
				CREATE INDEX temp_rtc_familia_produto ON temp_rtc_familia(produto);";
		$res = pg_exec($con,$sql);

		$join_1  =" JOIN temp_rtc_familia FF ON FF.produto = tbl_os.produto";
	}

	//HD 216470: Acrescentada busca por Linha
	if ($linha) {
		$sql = "
		SELECT
		produto
		INTO TEMP temp_rtc_linha

		FROM
		tbl_produto
		JOIN tbl_linha USING(linha)

		WHERE
		tbl_linha.fabrica = $login_fabrica
		AND	tbl_linha.linha = $linha;

		CREATE INDEX temp_rtc_linha_produto ON temp_rtc_linha(produto);
		";
		$res = pg_exec($con,$sql);

		$join_linha = " JOIN temp_rtc_linha LI ON LI.produto = tbl_os.produto";
	}


	if(strlen($produto_referencia) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE fabrica    = $login_fabrica
				AND   referencia = '$produto_referencia' ;";
		$res = pg_exec($con,$sql);
		$produto = @pg_result($res,0,0);
		$add_3 = "AND tbl_os.produto = $produto";
	}

	if (!empty($posto_id)) {
		$cond_posto = "AND tbl_os.posto = $posto_id";
	}
	if (!empty($marca_logo)) {
		$cond_marca = "AND tbl_os.marca = $marca_logo";
	}

	//HD 216470: Acrescentada busca por Cliente ADM
	if ($cliente_admin) {
		$cont_cliente_admin = "AND tbl_os.cliente_admin = $cliente_admin";
	}
	if ($login_fabrica == 52) {
			// fricon estava reclamanado que nao estava batendo os valores. na tela de pesquisa por posto tem esta condicao que nao ocorria aqui.
			$condicao_cliente_admin = "JOIN tbl_cliente_admin ON tbl_os.cliente_admin=tbl_cliente_admin.cliente_admin";
	}

	$sql = "SELECT os,data_abertura,$data_referencia::date,posto
			INTO TEMP temp_rtc_$mes
			FROM    tbl_os
			$condicao_cliente_admin
			$join_1
			$join_linha
			WHERE   tbl_os.fabrica = $login_fabrica
			AND     tbl_os.data_abertura  > '$data_inicial'
			AND     tbl_os.excluida IS NOT TRUE
			AND     tbl_os.finalizada IS NULL
			$cond_posto
			$cond_marca
			$cont_cliente_admin
			$add_1 $add_2 $add_3";
	//echo nl2br($sql);
	$res2 = pg_exec($con,$sql);

	echo "<center><font size='1'></font></center>";

	echo "<table align='center' border='0' cellspacing='1' cellpadding='2' class='tabela' width='700'>";
	echo "<tr class='titulo_coluna'>\n";
	echo "<td>Posto</td>";

	echo '<td>Até 03 dias</td>
		  <td>Até 07 dias</td>
		  <td>Mais que 07 dias</td>
		  <td>Total de OS</td>';

	echo "</tr>";

	$sql = "
			SELECT DISTINCT  XX.posto,codigo_posto,nome,cidade,estado
			FROM temp_rtc_$mes     XX
			JOIN tbl_posto         PO ON XX.posto = PO.posto
			JOIN tbl_posto_fabrica PF ON XX.posto = PF.posto AND PF.fabrica = $login_fabrica
			$whereSql
			ORDER BY nome;
			";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		for( $i=0;$i<pg_numrows($res);$i++ ) {
			$posto        = @pg_result($res,$i,posto);
			$codigo_posto = @pg_result($res,$i,codigo_posto);
			$nome_posto   = @pg_result($res,$i,nome);
			$cidade_posto = @pg_result($res,$i,cidade);
			$estado_posto = @pg_result($res,$i,estado);

			$sqld = "
				SELECT count(os) AS total,
					SUM(($data_referencia - data_abertura)) AS data_diferenca
					FROM temp_rtc_$mes
					WHERE $data_referencia::date-data_abertura <= 3
					AND   posto = $posto;
			";
			$resd = pg_exec($con,$sqld);
			$total_1 = @pg_result($resd,0,0);
			$total_1d = @pg_result($resd,0,data_diferenca);


			$sqld = "
				SELECT count(os) AS total,
					SUM(($data_referencia - data_abertura)) AS data_diferenca
					FROM temp_rtc_$mes
					WHERE $data_referencia::date-data_abertura <= 7
					AND   $data_referencia::date-data_abertura >  3
					AND   posto = $posto;";
			$resd = pg_exec($con,$sqld);
			$total_2 = @pg_result($resd,0,0);
			$total_2d = @pg_result($resd,0,data_diferenca);


			$sqld = "
				SELECT count(os) AS total,
					SUM(($data_referencia - data_abertura)) AS data_diferenca
					FROM temp_rtc_$mes
					WHERE $data_referencia::date-data_abertura > 7
					AND   posto = $posto;";
			$resd = pg_exec($con,$sqld);
			$total_3 = @pg_result($resd,0,0);
			$total_3d = @pg_result($resd,0,data_diferenca);


			$sqld = "
					SELECT count(os) AS total,
					SUM(($data_referencia - data_abertura)) AS data_diferenca
					FROM temp_rtc_$mes
					WHERE $data_referencia::date IS NULL
					AND   posto = $posto;
					";

			flush();

			if ($i % 2 == 0) {
				$cor = "#F1F4FA";
			}else{
				$cor = "#F7F5F0";
			}

			if ($excel){
				$link_abre = "";
				$link_fecha = "";
			} else {
				$link_abre = "<a href='relatorio_tempo_os_aberta_os.php?mes=$mes&ano=$ano&estado=$estado&pais=$pais&familia=$familia&linha=$linha&produto_referencia=$produto_referencia&posto=$posto&cliente_admin=$cliente_admin&periodo=mes_atual&tipo_os=todas&marca_logo=$marca_logo' title='Clique para ver as OSs' target='_blank'>";
				$link_fecha = "</a>";
			}

			echo "<tr bgcolor='$cor'>";
			echo "<td align='left' nowrap>$link_abre$codigo_posto - $nome_posto ($cidade_posto-$estado_posto)$link_fecha</td>";
			echo "<td align='center' title='$total_1d'>$total_1</td>\n";
			echo "<td align='center' title='$total_2d'>$total_2</td>\n";
			echo "<td align='center' title='$total_3d'>$total_3</td>\n";
			echo "<td align='center'>" . (intval($total_1) + intval($total_5) + intval($total_2) + intval($total_3) + intval($total_4)) . "</td>\n";
			echo "</tr>\n";
		}
	}
	if (empty($posto_id)) {
		$sqld = "
			SELECT count(os) AS total,
				SUM(($data_referencia - data_abertura)) AS data_diferenca
				FROM temp_rtc_$mes
				WHERE $data_referencia::date-data_abertura <= 3;";
		$resd = pg_exec($con,$sqld);
		$total_1 = @pg_result($resd,0,0);
		$total_1d = @pg_result($resd,0,data_diferenca);


		$sqld = "
			SELECT count(os) AS total,
				SUM(($data_referencia - data_abertura)) AS data_diferenca
				FROM temp_rtc_$mes
				WHERE $data_referencia::date-data_abertura <= 7
				AND   $data_referencia::date-data_abertura >  3;
		";
		$resd = pg_exec($con,$sqld);
		$total_2 = @pg_result($resd,0,0);
		$total_2d = @pg_result($resd,0,data_diferenca);

		$sqld = "
			SELECT count(os) AS total,
				SUM(($data_referencia - data_abertura)) AS data_diferenca
				FROM temp_rtc_$mes
				WHERE $data_referencia::date-data_abertura > 7;
		";
		$resd = pg_exec($con,$sqld);
		$total_3 = @pg_result($resd,0,0);
		$total_3d = @pg_result($resd,0,data_diferenca);

		$total =  (intval($total_1) + intval($total_2) + intval($total_5) + intval($total_3));

		echo "<tr bgcolor='#F7F5F0'>\n";
		echo "<td align='right'><span class='total'>TOTAL</span></td>";
		echo "<td align='center' title='$total_1d'>$total_1</td>\n";
		echo "<td align='center' title='$total_2d'>$total_2</td>\n";
		echo "<td align='center' title='$total_3d'>$total_3</td>\n";
		echo "<td align='center'>" . $total . "</td>\n";
		echo "</tr>\n";

		flush();
	} else {
		$total = 1;
	}
	echo "</body>";
	echo "</table>";

}

if ($excel) {
	$conteudo_excel = ob_get_clean();
	$arquivo = fopen("xls/relatorio_tempo_os_aberta_$login_fabrica$login_admin.xls", "w+");
	fwrite($arquivo, $conteudo_excel);
	fclose($arquivo);
	echo '<meta http-equiv="Refresh" content=" 0 url=xls/relatorio_tempo_os_aberta_' . $login_fabrica . $login_admin . '.xls">';
} else {
	echo "<br><br>";

	if ($total > 0) {
		$params = "mes=$mes&ano=$ano&estado=$estado&pais=$pais&familia=$familia&linha=$linha&produto_referencia=$produto_referencia&produto_descricao=$produto_descricao&codigo_posto=$codigo_posto_pesquisa&posto_nome=$posto_nome&cliente_admin=$cliente_admin";
		echo "<a href='" . $PHP_SELF . "?" . $params . "&excel=1' style='font-size: 10pt;' target='_blank'><img src='imagens/excell.gif'> Clique aqui para download do relatório em Excel</a>";
		echo "<br><br>";
	}

	include_once "rodape.php";
}

?>
