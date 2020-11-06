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

$title = "RELATÓRIO DE TEMPO DE CHAMADOS EM ABERTO";


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

<?php
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
	if (empty($produto_descricao))  $produto_descricao  = $_GET['produto_descricao'];
	if (empty($codigo_posto))       $codigo_posto       = $_GET['codigo_posto'];
	if (empty($posto_nome))         $posto_nome         = $_GET['posto_nome'];
	if (empty($cliente_admin))      $cliente_admin      = $_GET['cliente_admin'];
	if (empty($cliente_nome_admin)) $cliente_nome_admin = $_GET['cliente_nome_admin'];

	$codigo_posto_pesquisa = $codigo_posto;

	if ($trava_cliente_admin) {
		$cliente_admin = $trava_cliente_admin;
		$sql = "SELECT nome FROM tbl_cliente_admin WHERE cliente_admin=$cliente_admin";
		$res = pg_query($con, $sql);
		$cliente_nome_admin = pg_result($res, 0, nome);

		$desabilita_cliente_nome_admin = "disabled";
	} else {
		$desabilita_cliente_nome_admin = "";
	}

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
		$cliente_admin = intval($cliente_admin);
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
				<td>&nbsp;</td>
				<td>&nbsp;</td>
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
		$add_1 = " AND tbl_posto.pais = '$pais'";
	}

	if(!empty($estado)) {
		$add_2 = "AND tbl_posto.estado ";
		if($estado == "centro-oeste") $add_2 .= " in ('GO','MT','MS','DF') ";
		if($estado == "nordeste")     $add_2 .= " in ('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";
		if($estado == "norte")        $add_2 .= " in ('AC','AM','RR','RO','PA','AP','TO') ";
		if($estado == "sudeste")      $add_2 .= " in ('MG','ES','RJ','SP') ";
		if($estado == "sul")          $add_2 .= " in ('PR','SC','RS') ";
		if(strlen($estado) == 2)      $add_2 .= " = '$estado'";
		if ($estado == "SP-capital") {
			$add_2 .= " tbl_posto.estado = 'SP'
                                 AND tbl_cidade.cidade ~* 's.o paulo|s.o bernardo do campo|S.o Caetano do Sul|Guarulhos|Santo Andr.'";
		}
		if ($estado == "SP-interior") {
			$add_2 .= " tbl_posto.estado = 'SP'
                         AND tbl_posto.cidade !~* 's.o paulo|s.o bernardo do campo|S.o Caetano do Sul|Guarulhos|Santo Andr.'";
		}

	}






	// ========== INICIO CONDIÇÃO DOS FILTROS ==========

	if(strlen($familia) > 0){
		$and_familia = "JOIN tbl_familia 
						ON  tbl_produto.familia = tbl_familia.familia
						AND tbl_familia.fabrica    = $login_fabrica
						AND tbl_familia.familia    = $familia";
	}

	if(strlen($linha) > 0){
		$and_linha = "JOIN tbl_linha  
					  ON  tbl_produto.linha = tbl_linha.linha 
					  AND tbl_linha.fabrica = $login_fabrica
					  AND tbl_linha.linha   = $linha";
	}


	if(strlen($produto_referencia) > 0){
		$and_referencia_pd = " AND tbl_produto.referencia = '$produto_referencia'";
	}

	
	if(strlen($posto_id) > 0){
		$cond_posto = " AND tbl_posto.posto = $posto_id";
	}

	if(strlen($cliente_admin) > 0){
		$cond_cliente_admin = " JOIN tbl_cliente_admin on tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin $cond_cliente_admin AND tbl_cliente_admin.cliente_admin = $cliente_admin";
	}else {
		$cond_cliente_admin = " LEFT JOIN tbl_cliente_admin on tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin ";
	}



	// ========== FIM CONDIÇÃO DOS FILTROS ==========


	 	$sql_hd = "SELECT 
					tbl_hd_chamado.hd_chamado,
					tbl_posto.posto AS codigo_posto,
					tbl_posto.nome AS posto_nome,
					tbl_posto.cidade,
					tbl_posto.estado, 
					tbl_hd_chamado.status 
				    INTO TEMP temp_rtc_hd
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado  
					LEFT JOIN tbl_hd_chamado_item on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado and tbl_hd_chamado_item.produto is not null
					LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_item.produto $and_referencia_pd 

					$and_familia 
					$and_linha  

					LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao
					LEFT JOIN tbl_cidade on tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
					LEFT JOIN tbl_posto_fabrica on tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = 52
					LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto $cond_posto  $add_2	$add_1


					$cond_cliente_admin 
					LEFT JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os  


					WHERE tbl_hd_chamado.fabrica_responsavel = 52
					AND tbl_hd_chamado.data BETWEEN '$data_inicial' AND '$data_final' 
					ORDER BY tbl_hd_chamado.hd_chamado DESC";
		$res_hd = pg_exec($con,$sql_hd);
		//echo nl2br($sql_hd);






   $sql_bus_posto ="SELECT  
					DISTINCT(temp_rtc_hd.codigo_posto) AS codigo_posto,
					temp_rtc_hd.posto_nome AS posto_nome,
					temp_rtc_hd.cidade,
					temp_rtc_hd.estado 
					FROM temp_rtc_hd
					WHERE temp_rtc_hd.posto_nome <> '' 
					GROUP BY temp_rtc_hd.codigo_posto,temp_rtc_hd.posto_nome,temp_rtc_hd.cidade,temp_rtc_hd.estado   
					ORDER BY temp_rtc_hd.posto_nome DESC";
   	//echo nl2br($sql_bus_posto);
	$res_bus_posto = pg_exec($con,$sql_bus_posto);
	if(pg_numrows($res_bus_posto)>0){

		$arquivo_nome     = "relatorio_tempo_os_aberta_".$login_fabrica.".".$login_admin.".xls";
		$caminho_arquivo  = "xls/".$arquivo_nome;
		fopen($caminho_arquivo, "w+");	
		$fp = fopen($caminho_arquivo, "a");


		echo "<center><font size='1'></font></center>";
		echo "<table align='center' border='0' cellspacing='1' cellpadding='2' class='tabela' width='700'>";
		fputs ($fp,"<table align='center' border='1' bordercolor='#596D9B'>");
		echo "<body>";
		echo "<tr class='titulo_coluna'>\n";
		fputs ($fp,"<tr>");

		echo "<td>Posto</td>";
		fputs ($fp,"<td bgcolor='#596D9B'><font color='#FFFFFF'>Posto</font></td>");

		echo '<td>Abertos</td>
			  <td>Aguardando peça</td>
			  <td>Resolvidos</td>
			  <td>Total de OS</td>';
		fputs ($fp,"<td bgcolor='#596D9B' color='#FFFFFF'><font color='#FFFFFF'>Abertos</font></td>
				  <td bgcolor='#596D9B' color='#FFFFFF'><font color='#FFFFFF'>Aguardando peça</font></td>
				  <td bgcolor='#596D9B' color='#FFFFFF'><font color='#FFFFFF'>Resolvidos</font></td>
				  <td bgcolor='#596D9B' color='#FFFFFF'><font color='#FFFFFF'>Total de OS</font></td>");

		echo "</tr>";
		fputs ($fp,"</tr>");

		for($b=0;$b < pg_numrows($res_bus_posto);$b++) {

			$cod_codigo_posto   = "";
			$cod_posto_nome		= "";
			$cod_cidade			= "";
			$cod_estado			= "";

			$cod_codigo_posto   = pg_result($res_bus_posto,$b,codigo_posto);
			$cod_posto_nome		= pg_result($res_bus_posto,$b,posto_nome);
 			$cod_cidade			= pg_result($res_bus_posto,$b,cidade);
			$cod_estado			= pg_result($res_bus_posto,$b,estado);

									 				
				$sqla = "SELECT count(hd_chamado) AS total  
						FROM temp_rtc_hd 
						WHERE status = 'Aberto'
						AND codigo_posto = '$cod_codigo_posto'";
				$resa = pg_exec($con,$sqla);
				$total_1 = @pg_result($resa,0,0);


				$sqlb = "SELECT count(hd_chamado) AS total  
						FROM temp_rtc_hd 
						WHERE status ='Aguardando Peça'
						AND codigo_posto = '$cod_codigo_posto'";
				$resb = pg_exec($con,$sqlb);
				$total_2 = @pg_result($resb,0,0);

			
				$sqlc = "SELECT count(hd_chamado) AS total  
						FROM temp_rtc_hd 
						WHERE status ='Resolvido'
						AND codigo_posto = '$cod_codigo_posto'";
				$resc = pg_exec($con,$sqlc);
				$total_3 = @pg_result($resc,0,0);



				if ($i % 2 == 0) {
					$cor = "#F1F4FA";
				}else{
					$cor = "#F7F5F0";
				}

				if ($excel){
					$link_abre = "";
					$link_fecha = "";
				} else {
					$link_abre = "<a href='relatorio_tempo_chamado_aberto_hd.php?mes=$mes&ano=$ano&estado=$estado&pais=$pais&familia=$familia&linha=$linha&produto_referencia=$produto_referencia&posto=$cod_codigo_posto&cliente_admin=$cliente_admin&periodo=mes_atual' title='Clique para ver os atendimentos' target='_blank'>";
					$link_fecha = "</a>";
				}

				echo "<tr bgcolor='$cor'>";
				fputs ($fp,"<tr>");
				echo "<td  bgcolor='$cor' align='left' nowrap>$link_abre$cod_codigo_posto - $cod_posto_nome ($cod_cidade-$cod_estado)$link_fecha</td>";
				fputs ($fp,"<td bgcolor='$cor' align='left' nowrap><font color='#63798D'>$cod_codigo_posto - $cod_posto_nome ($cod_cidade-$cod_estado)</font></td>");
				echo "<td align='center' title='$total_1d'>$total_1</td>\n";
				fputs ($fp,"<td bgcolor='$cor' align='center' title='$total_1d'>$total_1</td>");
				echo "<td align='center' title='$total_2d'>$total_2</td>\n";
				fputs ($fp,"<td bgcolor='$cor' align='center' title='$total_2d'>$total_2</td>");
				echo "<td align='center' title='$total_3d'>$total_3</td>\n";
				fputs ($fp,"<td bgcolor='$cor' align='center' title='$total_3d'>$total_3</td>");
				echo "<td align='center'>" . (intval($total_1) + intval($total_2) + intval($total_3)) . "</td>\n";
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . (intval($total_1) + intval($total_2) + intval($total_3)) . "</td>");
				echo "</tr>\n";
				fputs ($fp,"</tr>");

		   

		}
	



		$sqla = "
			SELECT count(hd_chamado) AS total  
				FROM temp_rtc_hd 
				WHERE status ='Aberto'
				AND posto_nome <> ''";
		$resa = pg_exec($con,$sqla);
		$total_1 = pg_result($resa,0,0);


		$sqlb = "
			SELECT count(hd_chamado) AS total  
				FROM temp_rtc_hd 
				WHERE status ='Aguardando Peça'
				AND posto_nome <> ''";
		$resb = pg_exec($con,$sqlb);
		$total_2 = pg_result($resb,0,0);

	
		$sqlc = "
			SELECT count(hd_chamado) AS total  
				FROM temp_rtc_hd 
				WHERE status ='Resolvido'
				AND posto_nome <> ''";
		$resc = pg_exec($con,$sqlc);
		$total_3 = pg_result($resc,0,0);



		$total =  (intval($total_1) + intval($total_2) + intval($total_3));

		echo "<tr bgcolor='#F7F5F0'>\n";
		fputs ($fp,"<tr>");
		echo "<td align='right'><span class='total'>TOTAL</span></td>";
		fputs ($fp,"<td bgcolor='#F7F5F0' align='right'><span class='total'><font color='#596D9B'>TOTAL</font></span></td>");
		echo "<td align='center' title='$total_1d'>$total_1</td>\n";
		fputs ($fp,"<td bgcolor='#F7F5F0' align='center' title='$total_1d'>$total_1</td>");
		echo "<td align='center' title='$total_2d'>$total_2</td>\n";
		fputs ($fp,"<td bgcolor='#F7F5F0' align='center' title='$total_2d'>$total_2</td>");
		echo "<td align='center' title='$total_3d'>$total_3</td>\n";
		fputs ($fp,"<td bgcolor='#F7F5F0' align='center' title='$total_3d'>$total_3</td>");
		echo "<td align='center'>" . $total . "</td>\n";
		fputs ($fp,"<td bgcolor='#F7F5F0' align='center'>" . $total . "</td>");
		echo "</tr>\n";
		fputs ($fp,"</tr>");

	
		echo "</body>";
		echo "</table>";
		fputs ($fp,"</table>");


		
		if(file_exists($caminho_arquivo)) {
			echo "<br>";
			echo "<table width='700px' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr>";
			echo "<td align='center'><button type='button' onclick=\"window.location='$caminho_arquivo'\">Download em Excel</button></td>";
			echo "</tr>";
			echo "</table>";
		}

   }else{
		echo "Nenhum resultado encontrado";
   }

}

?>
