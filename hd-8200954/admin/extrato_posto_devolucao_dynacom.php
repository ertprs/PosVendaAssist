<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];


$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];

$layout_menu = "financeiro";
$title = "Consulta Devoluções de Peças Pendentes";

$agrupar = "true";

if (strlen($_GET["pendentes"]) > 0) {
	$todosPendentes = trim($_GET["pendentes"]);
}else{
	$todosPendentes = "false";
}

if (isset($_POST["Excluir"])){

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	for ($i=0;$i<count($_POST['extratos']);$i++){
		$extrato=$_POST['extratos'][$i];
		$sql = "DELETE from tbl_extrato_devolucao WHERE extrato=$extrato AND serie='FN'";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}			
	else {
		//$res = @pg_exec ($con,"ROLLBACK TRANSACTION"); //teste
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF?btnacao=filtrar&posto_codigo=$posto_codigo&posto_nome=$posto_nome");
		exit();
	}	
}

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

if (isset($_POST["Confirmar"])){

	$extrato_nota=trim($_POST['extrato_nota']);
	$extrato_nf_data_chegada =trim($_POST['data_chegada']);

	$res = @pg_exec($con,"BEGIN TRANSACTION");
	$extrato_nf_data_chegada=@converte_data($extrato_nf_data_chegada);

	if ($extrato_nf_data_chegada){
		for ($i=0;$i<count($_POST['extratos']);$i++){
			$extrato=$_POST['extratos'][$i];
			$sql = "UPDATE tbl_extrato_devolucao SET data_nf_recebida='$extrato_nf_data_chegada'
					WHERE extrato=$extrato AND serie='FN'";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	else{
		$msg_erro = "Data no formato inválido";
	}
	
	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}			
	else {
		//$res = @pg_exec ($con,"ROLLBACK TRANSACTION"); //teste
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF?btnacao=filtrar&posto_codigo=$posto_codigo&posto_nome=$posto_nome");
		exit();
	}	
}



// AJAX
if($todosPendentes=="true"){
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	$sql = "SELECT	count(tbl_extrato.extrato) AS totalPecas,
				tbl_posto.posto AS posto,
				tbl_posto.nome
					FROM    tbl_os
					JOIN    tbl_os_extra             ON tbl_os.os                                = tbl_os_extra.os
					JOIN    tbl_produto              ON tbl_os.produto                           = tbl_produto.produto
					JOIN    tbl_os_produto           ON tbl_os.os                                = tbl_os_produto.os
					JOIN    tbl_os_item              ON tbl_os_produto.os_produto                = tbl_os_item.os_produto
					JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado  = tbl_os_item.servico_realizado
					JOIN    tbl_peca                 ON tbl_os_item.peca                         = tbl_peca.peca
					JOIN    tbl_extrato              ON tbl_extrato.extrato                      = tbl_os_extra.extrato
					JOIN    tbl_posto_linha          ON tbl_posto_linha.posto = tbl_os.posto AND (tbl_posto_linha.linha = tbl_produto.linha OR tbl_posto_linha.familia = tbl_produto.familia)
					JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
					WHERE
					tbl_extrato.fabrica  = $login_fabrica
					AND     tbl_os_item.liberacao_pedido        IS TRUE
					AND     tbl_peca.devolucao_obrigatoria      IS TRUE
					AND     tbl_servico_realizado.troca_de_peca IS TRUE
					GROUP BY tbl_posto.posto,
							tbl_posto.nome
					HAVING count(tbl_extrato.extrato)>20
					ORDER BY count(tbl_extrato.extrato) DESC
					";
	$sql = "SELECT	DISTINCT	tbl_extrato_devolucao.nota_fiscal AS nf,
						to_char(tbl_extrato_devolucao.data_nf_envio,'DD/MM/YYYY') AS enviado,
						tbl_extrato_devolucao.total_nota AS total_nota,
						tbl_posto.nome AS nome_posto,
						tbl_posto.posto AS posto,
						tbl_posto.cnpj AS cnpj
			FROM tbl_extrato_devolucao
			JOIN tbl_extrato USING(extrato)
			JOIN tbl_posto USING(posto)
			WHERE fabrica=$login_fabrica
			AND nota_fiscal IS NOT NULL
			AND data_nf_recebida IS NULL
			ORDER BY enviado DESC
		";

	$res_extrato = pg_exec ($con,$sql);
	$qtde_postos=pg_numrows($res_extrato);

	$lista="";
	$lista .=  "<center><table border='0' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#485989' width='600px'>";
	$lista .=  "<tr class='Titulo' height='20' background='imagens_admin/azul.gif'>";
	$lista .=  "<td>POSTO</td>";
	$lista .=  "<td>NOTA FISCAL</td>";
	$lista .=  "<td>DATA ENVIO</td>";
	$lista .=  "<td>VALOR NOTA</td></tr>";

	for ($i=0;$i<$qtde_postos;$i++){
		//$extrato		= pg_result($res_extrato,$i,extrato);
		$nf			= pg_result($res_extrato,$i,nf);
		$nf_total_nota	= pg_result($res_extrato,$i,total_nota);
		$data_envio	= pg_result($res_extrato,$i,enviado);
		$nome_posto	= pg_result($res_extrato,$i,nome_posto);
		$cod_posto	= pg_result($res_extrato,$i,posto);
		$cnpj_posto	= pg_result($res_extrato,$i,cnpj);
		//$lista .= "$nf (R$ $nf_total_nota) - $nome_posto($cod_posto)<br>";

		$cor = ($i%2==0) ? '#f8f8f8' : '#ffffff';

		$lista .=  "<tr class='Conteudo' height='20' bgcolor='$cor' align='left'  >";
		$lista .=  "<td nowrap  align='left' title='$nome_posto'><a href='$PHP_SELF?btnacao=filtrar&posto_codigo=$cnpj_posto&posto_nome=$nome_posto''>".substr($nome_posto,0,40)."</a></td>";
		$lista .=  "<td nowrap align='center'>$nf</td>";
		$lista .=  "<td nowrap  align='center' title='Enviado em $data_envio'>$data_envio</td>";
		$lista .=  "<td nowrap  align='right' title='Valor da Nota: R$ $nf_total_nota'>$nf_total_nota</td>";
		$lista .=  "</tr>";

	}
	$lista .=  "</table>";

	if ($qtde_postos==0){
		echo "<center><h2 style='font-size:12px;background-color:#D9E2EF;color:black;width:550px'>Não há nenhum posto com recebimento da NF pendente</h2></center>";
	}
	else {
		echo "<hr><br><b style='font-size:14px'>Postos Que Enviaram Peças Sem Confirmação de Recebimento da Fábrica</b><br>";
		echo $lista;
		echo "<br>";
	}
	exit;
}

include "cabecalho.php";



?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
;
	background-color: #D9E2EF
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	/*background-color: #D9E2EF*/
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.quadro{
	border: 1px solid #596D9B;
	width:450px;
	height:50px;
	padding:10px;
	
}

.botao {
		border-top: 1px solid #333;
	        border-left: 1px solid #333;
	        border-bottom: 1px solid #333;
	        border-right: 1px solid #333;
	        font-size: 13px;
	        margin-bottom: 10px;
	        color: #0E0659;
		font-weight: bolder;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:12px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:12px;
}
</style>
<script language='javascript' src='../ajax.js'></script>

<script language="JavaScript">

/* ============= Função PESQUISA DE POSTOS ====================
Nome da Função : fnc_pesquisa_posto (cnpj,nome)
		Abre janela com resultado da pesquisa de Postos pela
		Código ou CNPJ (cnpj) ou Razão Social (nome).
=================================================================*/

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}
}

var checkflag = "false";
function check(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}

function AbrirJanelaObs (extrato) {
	var largura  = 400;
	var tamanho  = 250;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
function gerarExportacao(but){
	 if (but.value == 'Exportar Extratos' ) {
		if (confirm('Deseja realmente prosseguir com a exportação?\n\nSerá exportado somente os extratos aprovados e liberados.')){
			but.value='Exportando...';
			exportar();
		}
	} else {
		 alert ('Aguarde submissão');
	}

}

function retornaExporta(http) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					alert(results[1]);
				}else{
					alert (results[1]);
				}
			}else{
				alert ("Não existe extratos a serem exportados.");
			}
		}
	}
}

function exportar() {
	url = "<?= $PHP_SELF ?>?exportar=sim";
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaExporta(http) ; } ;
	http.send(null);
}


function retornaPostos(http,componente) {
	var com = document.getElementById(componente);
	if (http.readyState == 1) {
		com.innerHTML   = "<font size='1'>Aguarde...Processando...<br><img src='../imagens/carregar_os.gif'>";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			com.innerHTML   = " "+http.responseText;;
		}
	}
}

function postosPendentes(componente) {
	url = "<?= $PHP_SELF ?>?pendentes=true";
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPostos(http,componente) ; } ;
	http.send(null);
}

</script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<?

if (strlen($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' class='error'>\n";
	echo "<tr>";
	echo "<td>$msg_erro</td>";
	echo "</tr>";
	echo "</table>\n";
}



echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<TABLE width='600' align='center' border='0' cellspacing='3' cellpadding='2'>\n";
echo "<input type='hidden' name='btnacao' value=''>";
/*
echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "		Consultar Postos Com Devolução De Peças Pendentes";
echo "	</TD>";
echo "<TR>\n";

echo "<TR>\n";
echo "	<TD ALIGN='center'>";
echo "	Data Inicial ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' value='$data_inicial' class='frm'>&nbsp;<IMG src=\"imagens_admin/btn_lupa.gif\" align='absmiddle' onclick=\"javascript:showCal('dataPesquisaInicial_Extrato')\" style='cursor:pointer' alt='Clique aqui para abrir o calendário'>\n";
echo "	</TD>\n";

echo "	<TD ALIGN='center'>";
echo "	Data Final ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' value='$data_final' class='frm'>&nbsp;<IMG src=\"imagens_admin/btn_lupa.gif\" align='absmiddle' onclick=\"javascript:showCal('dataPesquisaFinal_Extrato')\" style='cursor:pointer' alt='Clique aqui para abrir o calendário'>\n";
echo "</TD>\n";
echo "</TR>\n";*/

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "		Selecione o Posto para Consulta";
echo "	</TD>";
echo "<TR>\n";

echo "<TR >\n";
echo "	<TD COLSPAN='2' ALIGN='center' nowrap>";
echo "CNPJ";
echo "		<input type='text' name='posto_codigo' size='18' value='$posto_codigo' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'cnpj')\">";

echo "&nbsp;&nbsp;Razão Social ";
echo "		<input type='text' name='posto_nome' size='45' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "<TR>\n";

echo "</TABLE>\n";

echo "<img src=\"imagens_admin/btn_filtrar.gif\" onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' style=\"cursor:pointer;\">\n";

echo "</form>";
echo "<br>";

echo "<br><a href=\"javascript:document.frm_extrato.posto_codigo.value='';document.frm_extrato.posto_nome.value='';postosPendentes('div_postos');\" ALT='Mostrar todos postos com devolução de peças pendentes' style='font-size:12px'>>> Mostrar Todos as Nota Enviadas sem Confirmação de Recebimento <<</a><br><br>";

echo "<div id='div_postos'>";

if (strlen ($posto_codigo) > 0 ) {

	echo "<hr>";
	$posto_codigo = str_replace (" " , "" , $posto_codigo);
	$posto_codigo = str_replace ("-" , "" , $posto_codigo);
	$posto_codigo = str_replace ("/" , "" , $posto_codigo);
	$posto_codigo = str_replace ("." , "" , $posto_codigo);


	$query_posto="SELECT
					tbl_posto.posto,
					tbl_posto.nome
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
					WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
					AND         tbl_posto.cnpj   = '$posto_codigo'";
	$res_posto = pg_exec ($con,$query_posto);
	$res_qtde=pg_numrows($res_posto);
	if ($res_qtde>0){
		$posto = pg_result ($res_posto,0,posto);
		$nome = pg_result ($res_posto,0,nome);
	}

	echo "<br><center><h2 style='font-size:15px;background-color:#D9E2EF;color:black;width:550px'>$nome</h2></center>";

	$query_extratos="SELECT
					tbl_extrato_devolucao.nota_fiscal AS nota_fiscal_devolucao,
					to_char (tbl_extrato_devolucao.data_nf_envio,'dd/mm/yyyy') as data_envio,
					to_char (tbl_extrato_devolucao.data_nf_recebida,'dd/mm/yyyy') as data_recebimento,	
					tbl_extrato_devolucao.total_nota AS total_nota
					FROM        tbl_extrato
					JOIN        tbl_extrato_devolucao    ON tbl_extrato.extrato         = tbl_extrato_devolucao.extrato
					WHERE       tbl_extrato.fabrica = $login_fabrica
					AND         tbl_extrato.posto   = $posto
					AND tbl_extrato_devolucao.data_nf_recebida IS NULL
					AND tbl_extrato_devolucao.nota_fiscal IS NOT NULL
					GROUP BY nota_fiscal_devolucao,data_envio,data_recebimento,total_nota";

	$res_extr = pg_exec ($con,$query_extratos);
	$res_extr_qtde=pg_numrows($res_extr);

	$nota_devolucao="";
	if ($res_extr_qtde>0){
		for ($i=0;$i<$res_extr_qtde;$i++){
			$nota_devolucao .= "<form method='post' name='alterar_nota' action='$PHP_SELF?btnacao=filtrar&posto_codigo=$posto_codigo&posto_nome=$posto_nome'>";
			$nota_devolucao .= "<table border='0' align='center' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#485989' width='550'>\n";
			$nota_devolucao .= "<tr class='Titulo' height='15' >\n";
			$nota_devolucao .= "<td align='center' background='imagens_admin/azul.gif'>";
			$nota_devolucao .= "Extrato";
			$nota_devolucao .= "</td>";
			$nota_devolucao .= "<td align='center' background='imagens_admin/azul.gif'>";
			$nota_devolucao .= "Nota Fiscal de Devolução";
			$nota_devolucao .= "</td>";
			$nota_devolucao .= "<td align='center' background='imagens_admin/azul.gif'>";
			$nota_devolucao .= "Excluir?";
			$nota_devolucao .= "</td>";
			$nota_devolucao .= "<td align='center' background='imagens_admin/azul.gif'>";
			$nota_devolucao .= "Data do Recebimento";
			$nota_devolucao .= "</td>";
			$nota_devolucao .= "</tr>\n";
			$nf_devolucao = pg_result ($res_extr,$i,nota_fiscal_devolucao);
			$nf_data_envio =  pg_result ($res_extr,$i,data_envio);
			$nf_data_recebimento =  pg_result ($res_extr,$i,data_recebimento);
			$total_nota =  pg_result ($res_extr,$i,total_nota);

			$mostra_extratos="SELECT DISTINCT
							tbl_extrato_devolucao.extrato AS extrato
							FROM        tbl_extrato_devolucao
							JOIN        tbl_extrato  ON tbl_extrato.extrato = tbl_extrato_devolucao.extrato
							WHERE       tbl_extrato.fabrica = $login_fabrica
							AND         tbl_extrato.posto   = $posto
							AND tbl_extrato_devolucao.nota_fiscal = '$nf_devolucao'
							";
			$res_extratos_da_nota = pg_exec ($con,$mostra_extratos);
			$res_extratos_da_nota_qtde=pg_numrows($res_extratos_da_nota);
			$extratos="";
			for($j=0;$j<$res_extratos_da_nota_qtde;$j++){
				$extrato_consulta = pg_result ($res_extratos_da_nota,$j,extrato);
				$extratos.="<input type='hidden' name='extratos[]' value='$extrato_consulta'>$extrato_consulta<br>";
			}

			$nota_devolucao .= "<tr><td>$extratos</td>"; 
			$nota_devolucao .= "<td><input type='hidden' name='extrato_nota' value='$nf_devolucao'>$nf_devolucao</td>";
			$nota_devolucao .= "<td><input type='submit' value='Excluir' name='Excluir'' onclick='javascript:if (confirm(\"Deseja apagar o número desta nota fiscal? O posto deverá preencher novamente!\")) return true; else return false;'></td>";
			if (strlen($nf_data_recebimento)>0){
				$nota_devolucao .= "<td>$nf_data_recebimento</td>";
			}
			else{
				$nota_devolucao .= "<td><input type='text' class='inpu' name='data_chegada' value='' size='10' maxlength='10'>";
				$nota_devolucao .= "<input type='submit' value='Confirmar' name='Confirmar'' onclick='javascript: if (this.form.data_chegada.value==\"\"){alert(\"Digite a data do recebimento!\");return false;}if (confirm(\"Confirmar recebimento desta nota fiscal de devolução? Os extratos deste posto serão liberados!\")) return true; else return false;'></td>";				
			}
			$nota_devolucao .= "</tr>";
			$nota_devolucao .= "</table>";
			$nota_devolucao .= "</form>";
		}


	}
	if (strlen($nota_devolucao)>0){
		echo "<br><b style='font-size:12px'>Relação das notas fiscais de devolução<br>
			
				<b style='font-size:12px;font-weight:normal'>$nota_devolucao</b>";
	}
## fim Notas Fiscais de Devolução


	if (strlen($nota_devolucao)>0){
		$query_extratos="SELECT DISTINCT
								tbl_extrato_devolucao.extrato AS extrato
								FROM        tbl_extrato_devolucao
								JOIN        tbl_extrato  ON tbl_extrato.extrato = tbl_extrato_devolucao.extrato
								WHERE       tbl_extrato.fabrica = $login_fabrica
								AND         tbl_extrato.posto   = $posto
								AND tbl_extrato_devolucao.nota_fiscal = '$nf_devolucao'";
	}
	else{
		$query_extratos="SELECT
						tbl_extrato.extrato AS extrato
						FROM        tbl_extrato
						LEFT JOIN   tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
						LEFT JOIN   tbl_os               ON tbl_os.os                 = tbl_os_extra.os
						WHERE       tbl_extrato.fabrica = $login_fabrica
						AND         tbl_extrato.posto   = $posto
						AND         tbl_os.os NOT IN (SELECT tbl_os_status.os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND tbl_os_status.status_os IN (13,15) AND tbl_os_status.extrato=tbl_extrato.extrato) 
						AND tbl_extrato.extrato NOT IN (SELECT extrato FROM tbl_extrato_devolucao WHERE nota_fiscal IS  NOT NULL )
						GROUP BY tbl_extrato.extrato
						";
	}
	$res_extratos = pg_exec ($con,$query_extratos);
	$res_extratos_qtde=pg_numrows($res_extratos);



	$ext="";
	$ext_unitario=array();
	if ($res_extratos_qtde>0){
		for ($i=0;$i<$res_extratos_qtde;$i++){
			$ext .= pg_result ($res_extratos,$i,extrato).",";
			array_push($ext_unitario,pg_result ($res_extratos,$i,extrato));
		}
		$ext = substr($ext, 0, (strlen($ext)-1));
	}


	$sql = "SELECT	
				tbl_peca.referencia        AS peca_referencia                      ,
				tbl_peca.descricao         AS peca_nome                            ,
				(SELECT preco FROM tbl_tabela_item WHERE peca = tbl_os_item.peca AND tabela = tbl_posto_linha.tabela) AS precoX ,
				sum(tbl_os_item.qtde)      AS qtde,
				tbl_peca.devolucao_obrigatoria AS devolucao
		FROM    tbl_os
		JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
		JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
		JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
		JOIN    tbl_os_item              ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
		JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
		JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
		JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
		JOIN    tbl_posto_linha          ON tbl_posto_linha.posto = tbl_os.posto AND (tbl_posto_linha.linha = tbl_produto.linha OR tbl_posto_linha.familia = tbl_produto.familia)
		LEFT JOIN tbl_extrato_devolucao ON tbl_extrato_devolucao.extrato = tbl_extrato.extrato
		WHERE   tbl_os_extra.extrato IN ($ext)
		AND     tbl_extrato.fabrica  = $login_fabrica
		AND tbl_extrato_devolucao.data_nf_recebida IS NULL
		AND     tbl_os_item.liberacao_pedido    IS TRUE";
	//if($login_fabrica<>14){ $sql .=" AND     tbl_peca.devolucao_obrigatoria      IS TRUE";}
	$sql .=" AND     tbl_servico_realizado.troca_de_peca IS TRUE
		GROUP BY  tbl_peca.devolucao_obrigatoria,
					tbl_peca.referencia   ,
					precoX ,
					tbl_peca.descricao
		ORDER BY   devolucao
		";
	
	if ($res_extratos_qtde>0){
		$res = pg_exec ($con,$sql);
		$totalRegistros = pg_numrows($res);
	}

	if ($totalRegistros > 0){
		if (strlen($nota_devolucao)>0){
			echo "<br><b style='font-size:14px'>Peças da Devolução</b>";
		}
		else{
			echo "<br><b style='font-size:14px'>Relação De Peças Com Devolução Pendente</b>";
		}
		//echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
		echo "<table border='0' align='center' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#485989' width='550'>\n";
		$tmp_total_qtde=0;

		if ($agrupar == "false") $colspan = "5";
		if ($agrupar == "true")  $colspan = "3";
	
		//echo "<TR class='menu_top'>\n";
		echo "<tr class='Titulo' height='15' >\n";

		
		echo "<TD align='center' background='imagens_admin/azul.gif'>PEÇA</TD>\n";
		echo "<TD align='center' background='imagens_admin/azul.gif'>QTDE</TD>\n";
		if ($login_fabrica <>14) {	echo "<TD align='center' background='imagens_admin/azul.gif'>VALOR</TD>\n";}
		
		echo "</TR>\n";
		
		$imprimi_devolucao_fisicamente=0;
		$imprimi_devolucao_nao_fisicamente=0;
		$soma_preco = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){

			if ( trim(pg_result ($res,$i,devolucao))=='t' AND $imprimi_devolucao_fisicamente==0){
				$imprimi_devolucao_fisicamente=1;
				echo "<TR class='table_line' bgcolor='#FFFFCC' >\n";
				echo "<TD align='left' nowrap colspan='3'><b>Peças que precisam ser devolvidas fisicamente</b></TD>\n";
				echo "</TR>\n";
			}
			if ( trim(pg_result ($res,$i,devolucao))=='f' AND $imprimi_devolucao_nao_fisicamente==0){
				$imprimi_devolucao_nao_fisicamente=1;
				echo "<TR class='table_line' bgcolor='#FFFFCC' >\n";
				echo "<TD align='left' nowrap colspan='3'><b>Peças que não precisam ser devolvidas fisicamente</b></TD>\n";
				echo "</TR>\n";
			}

			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$peca_nome			= trim(pg_result ($res,$i,peca_nome));
			$preco				= trim(pg_result ($res,$i,precoX));
			$qtde				= trim(pg_result ($res,$i,qtde));
			if($qtde>1){$preco = $preco*$qtde;}
			$soma_preco			= $soma_preco + $preco;
			$consumidor			= strtoupper($consumidor);
			$preco				= number_format($preco,2,",",".");

			$tmp_total_qtde+=$qtde;
			
			$cor = "#FCF9DA";
			$cor = "#d9e2ef";
			$cor = "#FFF";

			$btn = 'amarelo';
			
			if ($i % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}
			
			if (strstr($matriz, ";" . $i . ";")) {
				$cor = '#E49494';
			}
			

			if (strlen ($sua_os) == 0) $sua_os = $os;
			
			echo "<TR class='table_line' style='background-color: $cor;'>\n";
			

			echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
			echo "<TD align='center' nowrap>$qtde</TD>\n";
			if ($login_fabrica <>14) {	echo "<TD align='right' nowrap style='padding-right:3px'>$preco</TD>\n";}
			
			echo "</TR>\n";
		}
		echo "<TR class='Conteudo' bgcolor='#FFFFAA' style='padding:10px'>\n";
		
		if ($agrupar == "false") $colspan = '3';
		if ($agrupar == "true")  $colspan = '1';
		
		echo "<TD align='center' nowrap colspan='$colspan'><b>TOTAL</b></TD>\n";
		echo "<TD align='center' nowrap><b>$tmp_total_qtde</b></TD>\n";
		echo "<TD align='right' nowrap style='padding-right:3px'>". number_format($soma_preco,2,",",".") ."</TD>\n";
		
		echo "</TR>\n";
		echo "</TABLE>\n";
	}
	else {
		echo "<b style='font-size:12px'>Não consta peças com devolução obrigatória para este posto</b>";
	}
	

}

if($todosPendentes=="true2222222222222"){
	echo "<br><br> <b style='font-size:14px'>Postos Que Ainda Não Enviaram Peças Mas Já Atingiram O Limite</b><br>";
	$query_postos = "SELECT tbl_posto.posto AS posto, tbl_posto.nome AS nome, tbl_posto.cnpj AS cnpj FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE tbl_posto_fabrica.fabrica=$login_fabrica";
	$res = pg_exec ($con,$query_postos);
	$qtde_postos=pg_numrows($res);
	for ($i=0;$i<$qtde_postos;$i++){
		$posto = pg_result ($res,$i,posto);
		$posto_nome = pg_result ($res,$i,nome);
		$posto_cnpj = pg_result ($res,$i,cnpj);
		$sql = "SELECT	count(*) AS total
			FROM    tbl_os
			JOIN    tbl_os_extra             ON tbl_os.os                                = tbl_os_extra.os
			JOIN    tbl_produto              ON tbl_os.produto                           = tbl_produto.produto
			JOIN    tbl_os_produto           ON tbl_os.os                                = tbl_os_produto.os
			JOIN    tbl_os_item              ON tbl_os_produto.os_produto                = tbl_os_item.os_produto
			JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado  = tbl_os_item.servico_realizado
			JOIN    tbl_peca                 ON tbl_os_item.peca                         = tbl_peca.peca
			JOIN    tbl_extrato              ON tbl_extrato.extrato                      = tbl_os_extra.extrato
			JOIN    tbl_posto_linha          ON tbl_posto_linha.posto = tbl_os.posto AND (tbl_posto_linha.linha = tbl_produto.linha OR tbl_posto_linha.familia = tbl_produto.familia)
			JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
			WHERE tbl_posto.posto=$posto
			AND     tbl_extrato.fabrica  = $login_fabrica
			AND     tbl_os_item.liberacao_pedido    IS TRUE
			AND     tbl_peca.devolucao_obrigatoria      IS TRUE
			AND     tbl_servico_realizado.troca_de_peca IS TRUE";

		$res_extrato = pg_exec ($con,$sql);
		$totalRegistros = pg_result($res_extrato,0,total);
		if ($totalRegistros>20){
			echo "Posto: <b>$posto_nome</b> - CNPJ: $posto_cnpj<br>";
		}
	}
}

echo "<br>";

?>
</div>

<br>

<? include "rodape.php"; ?>
