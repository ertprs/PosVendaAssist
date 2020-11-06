<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$msg_erro = "";

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["mes"])) > 0)  $mes = trim($_POST["mes"]);
if (strlen(trim($_GET["mes"])) > 0)  $mes = trim($_GET["mes"]);


if (strlen(trim($_POST["ano"])) > 0)  $ano = trim($_POST["ano"]);
if (strlen(trim($_GET["ano"])) > 0)  $ano = trim($_GET["ano"]);

if (strlen(trim($_POST["linha"])) > 0)  $linha = trim($_POST["linha"]);
if (strlen(trim($_GET["linha"])) > 0)  $linha = trim($_GET["linha"]);

if (strlen(trim($_POST["codigo_posto"])) > 0)  $codigo_posto = trim($_POST["codigo_posto"]);
if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto = trim($_GET["codigo_posto"]);

if (strlen(trim($_POST["estado"])) > 0)  $estado = trim($_POST["estado"]);
if (strlen(trim($_GET["estado"])) > 0)  $estado = trim($_GET["estado"]);

if(!empty($btn_acao)) {
	if(empty($mes) or empty($ano)) {
		$msg_erro = "Informe o ano e mês para fazer a pesquisa";
	}

	if(strlen($mes) == 1) {
		$mes = "0".$mes;
	}
	$cond_posto = " AND 1 = 1";
	$cond_uf    = " AND 1 = 1";
	$cond_linha = " AND 1 = 1";
	if(!empty($codigo_posto)) {
		$sql = " SELECT posto
				FROM tbl_posto_fabrica
				WHERE fabrica = $login_fabrica
				AND   codigo_posto='$codigo_posto'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$posto = pg_fetch_result($res,0,posto);
			$cond_posto = " AND os.posto = $posto ";
		}
	}

	if(!empty($linha)) {
		$cond_linha = " AND ose.linha = $linha " ;
	}else{
	
	}

	if(!empty($estado)) {
		$cond_uf = " AND pf.contato_estado = '$estado' " ;
	}
}


$layout_menu = "financeiro";
$title = "CONSULTA DE EXTRATOS DETALHADO";

include "cabecalho.php";

?>
<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else
		alert('Preencha toda ou parte da informação para realizar a pesquisa!');
}

</script>

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

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{
	
}
</style>

<? 

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="msg_erro">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>

<? } ?>

<center>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="btn_acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="0" class='formulario'>
	<tr class="titulo_tabela" height="15">
		<td colspan="4">Parâmetros de Pesquisa</td>
	</tr>
	<tr >
		<td colspan="4">&nbsp;</td>
	</tr>
		<tr>
			<td width="10%">&nbsp;</td>
			<td nowrap>
					Mês *<br />
					<select name="mes" size="1" class="frm">
							<option value=""></option>
							<?
								for ($i = 1 ; $i <= count($meses) ; $i++) {
									echo "<option value='$i'";
									if ($mes == $i) echo " selected";
									echo ">" . $meses[$i] . "</option>";
								}
							?>
				</select>
			</td>
			<td nowrap>
					Ano *<br />
					<select name="ano" size="1" class="frm">
						<option value=""></option>
						<?
							for ($i = 2006 ; $i <= date("Y") ; $i++) {
								echo "<option value='$i'";
								if ($ano == $i) echo " selected";
								echo ">$i</option>";
							}
						?>
						</select>
			</td>
			<td>&nbsp;</td>
		</tr>

		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr>
			<td width="25%">&nbsp;</td>
			<td nowrap>
					Cod. Posto <br />
					<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>"><img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')">
			</td>
			<td nowrap>
					Nome do Posto<br />
					<input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;">
			</td>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
			<td >&nbsp;</td>
			<td nowrap>
					Estado <br />
					<select name="estado" size="1" class='frm'>
							<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>></option>
							<?
								 $ArrayEstados = array('AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO');
								for ($i=0; $i<=26; $i++){
									echo"<option value='".$ArrayEstados[$i]."'";
									if ($estado == $ArrayEstados[$i]) echo " selected";
									echo ">".$ArrayEstados[$i]."</option>\n";
								}
							?>
					</select>
			</td>
			<td nowrap>
					Linha <br />
					<select name="linha" size="1" class='frm'>
						<?
							$sql = " SELECT linha,nome
									FROM tbl_linha
									WHERE fabrica=$login_fabrica";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) > 0){
								echo "<option value></option>";
								for($i =0;$i<pg_num_rows($res);$i++) {
									$xlinha = pg_fetch_result($res,$i,linha);
									$nome  = pg_fetch_result($res,$i,nome);
									echo "<option value='$xlinha'";
									echo ($linha == $xlinha) ? " SELECTED " :"";
									echo ">$nome</option>";
								}
							}
						?>
					</select>
			</td>
			<td>&nbsp;</td>
		</tr>

		<tr >
			<td colspan="4" align='center' style='padding:20px 0 20px 0;'>
			<input type='button' value='Pesquisar' onclick="javascript: document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: pointer;" alt="Clique AQUI para pesquisar"></td>
		</tr>
</table>

</form>

<?
if(!empty($btn_acao) and empty($msg_erro)) {
	$sql = "SELECT extrato
			INTO  TEMP tmp_det_extrato_$login_admin
			FROM tbl_extrato ex
			WHERE fabrica = $login_fabrica
			AND   to_char(data_geracao,'MM') = '$mes'
			AND   to_char(data_geracao,'YYYY') = '$ano'
			;

			CREATE INDEX tmp_det_extrato_extrato_$login_admin ON tmp_det_extrato_$login_admin(extrato);
			
			SELECT os,mao_de_obra_desconto,mao_de_obra
			INTO TEMP tmp_os_extrato_$login_admin
			FROM tbl_os_extra ose
			JOIN    tmp_det_extrato_$login_admin ex ON ose.extrato = ex.extrato
			WHERE 1 = 1
			$cond_linha;
			
			CREATE INDEX tmp_os_extrato_os_$login_admin ON tmp_os_extrato_$login_admin(os);

			SELECT  os.os              ,
					os.sua_os          ,
					os.produto         ,
					os.consumidor_nome ,
					os.revenda_nome    ,
					os.serie           ,
					os.nota_fiscal     ,
					to_char (os.data_abertura,'DD/MM/YYYY') AS abertura ,
					to_char (os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
					to_char (os.data_nf,'DD/MM/YYYY') AS data_nf ,
					to_char (os.data_fechamento,'DD/MM/YYYY') AS fechamento,
					p.referencia AS produto_referencia,
					p.descricao  AS produto_descricao ,
					ose.mao_de_obra_desconto          ,
					ose.mao_de_obra                   ,
					pf.codigo_posto
			FROM    tbl_os os
			JOIN    tmp_os_extrato_$login_admin ose ON os.os = ose.os
			JOIN    tbl_produto p ON os.produto = p.produto
			JOIN    tbl_posto_fabrica pf ON os.posto = pf.posto AND pf.fabrica = os.fabrica
			WHERE   os.fabrica = $login_fabrica
			$cond_posto
			$cond_uf
			ORDER BY pf.codigo_posto,os.sua_os,ose.mao_de_obra;";
	$res = pg_query ($con,$sql);

	if(pg_num_rows($res) > 0){
		$conteudo = "<table width='700' aling='center' border='0' cellspacing='1' class='tabela'>";
		$conteudo .= "<tr class='titulo_coluna' >";
		$conteudo .= "<td align='center' nowrap >Posto</td>";
		$conteudo .= "<td align='center' nowrap >OS</td>";
		$conteudo .= "<td align='center' nowrap >Série</td>";
		$conteudo .= "<td align='center' nowrap >NF.Compra</td>";
		$conteudo .= "<td align='center' nowrap >Digitação</td>";
		$conteudo .= "<td align='center' nowrap >Abertura</td>";
		$conteudo .= "<td align='center' nowrap >Fechamento</td>";
		$conteudo .= "<td align='center' nowrap >Consumidor</td>";
		$conteudo .= "<td align='center' nowrap >Referência</td>";
		$conteudo .= "<td align='center' nowrap >Produto</td>";
		$conteudo .= "<td align='center' nowrap >MO</td>";
		$conteudo .= "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$chk = $_POST['chk_'.$i];
			$cor = "#F1F4FA";
			if ($i % 2 == 0) $cor = "#F7F5F0";

			$conteudo .= "<tr bgcolor='$cor' >";
			$conteudo .= "<td>";
			$conteudo .= pg_fetch_result($res,$i,codigo_posto);
			$conteudo .= "</td>";

			$conteudo .= "<td nowrap ><a href='os_press.php?os=" . pg_fetch_result ($res,$i,os) . "'>" . pg_fetch_result ($res,$i,sua_os) . "</a></td>";
			$conteudo .= "<td nowrap >" . pg_fetch_result ($res,$i,serie) . "</td>";
			$conteudo .= "<td nowrap align='center'>" . pg_fetch_result ($res,$i,nota_fiscal) . "</td>";
			$conteudo .= "<td nowrap >" . pg_fetch_result ($res,$i,digitacao) . "</td>";
			$conteudo .= "<td nowrap >" . pg_fetch_result ($res,$i,abertura) . "</td>";
			$conteudo .= "<td nowrap >" . pg_fetch_result ($res,$i,fechamento) . "</td>";
			if (strlen(pg_fetch_result ($res,$i,consumidor_nome)) > 0) {
				$conteudo .= "<td nowrap >" . pg_fetch_result ($res,$i,consumidor_nome) . "</td>";
			}else{
				$conteudo .= "<td nowrap >" . pg_fetch_result ($res,$i,revenda_nome) . "</td>";
			}
			$conteudo .= "<td nowrap >" . pg_fetch_result ($res,$i,produto_referencia) . "</td>";
			$conteudo .= "<td nowrap >" . pg_fetch_result ($res,$i,produto_descricao) . "</td>";
			if (pg_fetch_result ($res,$i,mao_de_obra_desconto) > 0) {
				$xmounit = 0;
			}else{
				$xmounit = pg_fetch_result($res,$i,mao_de_obra);
			}
			$conteudo .= "<td nowrap >".number_format($xmounit,2,',','.')."</td>";
			$conteudo .= "</tr>";
		}
		$conteudo .= "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
		$conteudo .= "<td align='center' nowrap colspan='9'>Total - $i OS</td>";
		$conteudo .= "</tr>";
		$conteudo .= "</table>";

		$arquivo_nome     = "relatorio-extrato-$login_fabrica.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;
		
		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo_tmp.zip `;
		echo `rm $arquivo_completo.zip `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE EXTRATO");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		fputs ($fp,$conteudo);
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;

		$resposta .= "<br>";
		$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		$resposta .="<tr>";
		$resposta .= "<td align='center'><input type='button' onclick=\"window.location='xls/$arquivo_nome.zip'\" value='Download do Arquivo'></td>";
		$resposta .= "</tr>";
		$resposta .= "</table>";

		if(pg_num_rows($res) > 5000){
			echo $resposta;
		}else{
			echo $resposta;
			echo "<br/>";
			echo $conteudo;
		}

	}
}

?>

<p><p>

<? include "rodape.php"; ?>
