<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include "autentica_admin.php";
include 'funcoes.php';


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj         = trim(pg_result($res,$i,cnpj));
				$nome         = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if($select_acao == "94" AND strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo da reprovação OS.";
	}

	if(strlen($observacao) > 0){
		$observacao = "' Observação: $observacao '";
	}else{
		$observacao = " NULL ";
	}

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	for ($x=0;$x<$qtde_os;$x++){

		$xxos = trim($_POST["check_".$x]);

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

			$res_os = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "SELECT contato_email,tbl_os.sua_os, tbl_os.posto, tbl_os.tipo_atendimento
					FROM tbl_posto_fabrica
					JOIN tbl_os            ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.os      = $xxos
					AND   tbl_os.fabrica = $login_fabrica";
			$res_x = pg_exec($con,$sql);
			$posto_email = pg_result($res_x,0,contato_email);
			$sua_os      = pg_result($res_x,0,sua_os);
			$posto       = pg_result($res_x,0,posto);
			$tipo_atendimento= pg_result($res_x,0,tipo_atendimento);

			$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
			$res_x = pg_exec($con,$sql);
			$promotor = pg_result($res_x,0,nome_completo);

			$sqlx = "SELECT motivo                                         ,
						   tbl_causa_defeito.codigo        AS cd_codigo    ,
						   tbl_causa_defeito.descricao     AS cd_descricao ,
						   tbl_servico_realizado.descricao AS s_descricao  ,
						   tbl_os_troca_motivo.observacao
					FROM tbl_os
					JOIN tbl_os_troca_motivo USING (os)
					JOIN tbl_causa_defeito ON tbl_os.causa_defeito = tbl_causa_defeito.causa_defeito
					JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os.solucao_os
					WHERE tbl_os.os      = $xxos
					AND   tbl_os.fabrica = $login_fabrica";
			$res_m = pg_exec($con,$sqlx);
			if(pg_numrows($res_m) > 0) {
				$motivo       = pg_result($res_m,0,motivo);
				$cd_codigo    = pg_result($res_m,0,cd_codigo);
				$cd_descricao = pg_result($res_m,0,cd_descricao);
				$s_descricao  = pg_result($res_m,0,s_descricao);
				$m_observacao = pg_result($res_m,0,observacao);
			}
			$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (92,93,94)
					AND os = $xxos
					ORDER BY data DESC
					LIMIT 1";
			$res_os = pg_exec($con,$sql);
			if (pg_numrows($res_os)>0){
				$status_da_os = trim(pg_result($res_os,0,status_os));
				if ($status_da_os == 92){
					//Aprovada
					if($select_acao == "93"){
						if($tipo_atendimento==13 or $tipo_atendimento==66){
							/*HD: 87459 - GRAVAR UM EXTRATO POR OS DE TROCA NA BOSCH */
							$sql = "SELECT extrato 
									FROM tbl_os_extra
									WHERE os = $xxos
										and extrato is not null";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);

							if(pg_numrows($res )==0){
								//--=== Cria um extrato para o posto ===--\\
								$sql = "INSERT INTO tbl_extrato (posto, fabrica, avulso, total) VALUES ($posto,$login_fabrica, 0, 0)";
								$res = pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);

								$sql = "SELECT CURRVAL ('seq_extrato')";
								$res = pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);
								$extrato  = pg_result ($res,0,0);

								//--=== Insere as OS's no extrato ==--\\				
								$sql = "UPDATE tbl_os_extra SET extrato = $extrato WHERE os = $xxos";
								$res = pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);
								

								//--=== Calcula o extrato do posto ====--\\
								$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
								$res = pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);

							}else{
								//envia email, apresenta erro
								if($sistema_lingua == 'ES') $msg_erro = "No es posible cerrar más de un extracto por día!";
								else                        $msg_erro = "Não é possível fechar mais de um extrato por dia!";
								
								$nome         = "TELECONTROL";
								$email_from   = "helpdesk@telecontrol.com.br";
								$assunto      = "OS DE TROCA";
								$destinatario = "helpdesk@telecontrol.com.br, igor@telecontrol.com.br";
								$boundary = "XYZ-" . date("dmYis") . "-ZYX";

								$mensagem = "Posto $login_posto - fabrica: $login_fabrica está tentando gravar a OS de troca: $xxos mas existe o problema de existir um extrato para esta OS. Programa: os_troca.php <br> 
											Este email é enviado com o objetivo de tratar os problemas de duplicidade de extrato (lote) da Bosch. 
											<br>Estava acontecendo de criar extrato com total em branco, possivelmente pelo posto voltar a página e reprocessar (F5). <br>
											Então colocamos para enviar email. Quem pegar este email deve pesquisar se existe algum problema nos extratos deste posto. <br>Só pode ter um extrato, com todas as OSs e o total tem que bater.";

								$body_top = "--Message-Boundary\n";
								$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
								$body_top .= "Content-transfer-encoding: 7BIT\n";
								$body_top .= "Content-description: Mail message body\n\n";
								@mail($destinatario,$assunto,$mensagem,"From: ".$email_from." \n $body_top ");
							}
						}

						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,93,current_timestamp,$observacao,$login_admin)";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$email_origem  = "pt.garantia@br.bosch.com";
						$email_destino = "$posto_email, helpdesk@telecontrol.com.br";
						$assunto       = "Troca Aprovada";

						$corpo ="<br>A OS n°$sua_os foi aprovada. Imprima uma cópia da OS e envie junto com o produto\n\n";
						$corpo.="<br>Promotor que concedeu a aprovação: $promotor\n\n";
						$corpo.="<br><br>Motivo da troca: $motivo ";
						if(strlen($m_observacao) > 0 ) {
							$corpo.="<br>Observação: $m_observacao";
						}
						$corpo.="<br>Identificação do defeito e Defeito: ";
						$corpo.="<br><br>$s_descricao &nbsp;&nbsp; $cd_codigo - $cd_descricao ";
						$corpo.="<br><br>PROCEDIMENTO PARA EMISSÃO DA NF DE ENVIO DO PRODUTO:<br>
1. Imprima cópia da OS para enviar com o produto.<br>
2. Natureza da operação: “Remessa de Peças em Garantia” / “Outras Saídas não Especificadas”.<br>
3. “CFOP” 5.949 (dentro do estado SP) 6.949 demais estados. (fora do estado de SP)<br>
4. Informar os 10 dígitos (código comercial) do produto que irá retornar, bem como sua denominação.<br>
5. Destacar somente ICMS, apenas para MICRO EMPRESA “ME” não é necessário destacar o ICMS.<br>
6. Não destacar IPI.<br>
7. Informar no corpo da Nota Fiscal o local de entrega: PT-RLA/ASA1 – Ca370/Laboratório QMM.<br>
8. O frete será pago pela Bosch, desde que solicitado coleta da mercadoria através da DHL, telefone (19) 3848-9466 ou ainda pelo e-mail br.dsc.guarulhos.bosch.coleta@dhl.com, exclusivo para garantia. Mercadorias encaminhadas por outra transportadora/meio com frete a cobrar serão devolvidas e consequentemente os custos serão por conta do solicitante.<br>
9. Utilize embalagem em bom estado.<br>
10. Valor do produto na Nota fiscal: deverá ser colocado com o preço de lista menos 33%, ex: F 012 8003 AD R$ 160,00 (Preço de lista), valor a ser colocado na NF R$ 107,20. Mercadorias que vierem com preço diferente do especificado serão notificadas para providenciarem carta de correção atrasando o retorno.<br>\n\n";
						$corpo.="<br>_______________________________________________\n";
						$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){

						}



					}
					//Recusada
					if($select_acao == "94"){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,94,current_timestamp,$observacao,$login_admin)";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "UPDATE tbl_os SET
									excluida  = 't'
								WHERE os = $xxos
								AND fabrica = $login_fabrica ";
						$res = pg_exec($con,$sql);

						$sql = "UPDATE tbl_os_extra SET
									status_os = 94
								WHERE os = $xxos";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
						$email_origem  = "pt.garantia@br.bosch.com";
						$email_destino = "$posto_email, helpdesk@telecontrol.com.br";
						$assunto       = "Troca Reprovada";

						$corpo ="<br>A OS n°$sua_os foi reprovada.\n\n";
						$corpo.="<br>Promotor que reprovou: $promotor\n\n";
						$corpo.="<br>Motivo: $observacao\n\n";
						$corpo.="<br>_______________________________________________\n";
						$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){

						}
					}
				}
			}

			if (strlen($msg_erro)==0){
				$res = pg_exec($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "callcenter";
$title = "TROCA DE PRODUTO - APROVAÇÃO ORDEM DE SERVIÇO DE CORTESIA";

include "cabecalho.php";

?>

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

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
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


<script language="JavaScript">
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_pesquisa2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}
}

function setCheck(theCheckbox,mudarcor,cor){
	if (document.getElementById(theCheckbox)) {
//		document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}

</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">

$().ready(function() {
	$("#os").numeric();

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>

<? include "javascript_pesquisas.php";

if($btn_acao == 'Pesquisar'){

	$data_inicial       = trim($_POST['data_inicial']);
	$data_final         = trim($_POST['data_final']);
	$aprova             = trim($_POST['aprova']);
	$os                 = trim($_POST['os']);
	$produto_referencia = trim($_POST['produto_referencia']);

	# HD 77122 - Não estava pesquisando por posto
	$posto_codigo = trim($_POST['posto_codigo']);

	if (strlen($os)>0){
		$Xos = " AND os = $os ";
	}

	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		$aprovacao = "92";
	}elseif($aprova=="aprovacao"){
		$aprovacao = "92";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "93";
	}elseif($aprova=="reprovadas"){
		$aprovacao = "94";
	}

	if (strlen($data_inicial) > 0) {

		list($d, $m, $y) = explode("/", $data_inicial);
        if(!checkdate($m,$d,$y)) 
            $msg_erro = "Data Inválida";

		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {

		list($d, $m, $y) = explode("/", $data_final);
        if(!checkdate($m,$d,$y)) 
            $msg_erro = "Data Inválida";

		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}

	if( ( !empty($data_final) && !empty($data_inicial) ) && $data_inicial > $data_final )
		$msg_erro = "Data Inválida";
}

if(strlen($msg_erro) > 0){
	echo '<div class="msg_erro" style="width:700px;margin:auto;">' . $msg_erro . '</div>';
}

//LEGENDAS hd 14631
/*
echo "<p>";
echo "<div align='center' style='position: relative; left: 10'>";
echo "<table border='0' cellspacing='0' cellpadding='0'>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#FDEBD0;color:#FDEBD0;border:1px solid #F8B652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  OS com origem da Intervenção</b></font></td><BR>";
echo "</tr>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#99FF66;color:#00FF00;border:1px solid #F8B652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  OS com Observação</b></font></td><BR>";
echo "</tr>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#CCFFFF; color:#D7FFE1;border:1px solid #F8B652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td><BR>";
echo "</tr>";
echo "</table>";
echo "</div>";
echo "</p>";
*/
//----------------------

?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='formulario espaco'>

<TBODY>
<tr class="titulo_tabela" height="20px"><td colspan="3">Parâmetros de Pesquisa</td></tr>
<tr><td>&nbsp;</td></tr>
<TR>
	<td width="20%">&nbsp;</td>
	<TD>
		Número da OS<br>
		<input type="text" name="os" id="os" value="<? echo $os ?>" class="frm" size="15" />
	</TD>
	<TD></TD>
</TR>
<TR>
	<td width="10%">&nbsp;</td>
	<TD>
		Data Inicial<br>
		<input type="text" name="data_inicial" id="data_inicial" size="15" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
	</TD>
	<TD>
		Data Final<br>
		<input type="text" name="data_final" id="data_final" size="15" maxlength="10" value="<? echo $data_final ?>" class="frm">
	</TD>
</TR>
<TR>
	<td width="10%">&nbsp;</td>
	<TD>
		Cod Posto<br>
		<input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm">
		<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'codigo')">
	</TD>
	<TD>
		Nome do Posto<br>
		<input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm">
		<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'nome')">
	</TD>
</TR>
<?if ($login_fabrica==20){?>
<TR>
	<td width="10%">&nbsp;</td>
		<td>
			Ref. Produto<br />
			<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
			&nbsp;
			<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia')">
		</td>
		<td>
			Descrição Produto<br />
			<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="40" value="<? echo $produto_descricao ?>" >
			&nbsp;
			<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao')">
		</td>
</TR>
<?}?>
<TR>
	<td width="10%">&nbsp;</td>
	<TD colspan='2'>Para aprovação do Promotor:<br>
		<?
		echo "<select name='promotor_treinamento' size='1' class='frm'>";
		echo "<option></option>";
		$sql = "SELECT tbl_admin.admin AS promotor_treinamento,
						tbl_promotor_treinamento.nome,
						tbl_promotor_treinamento.email,
						tbl_promotor_treinamento.ativo,
						tbl_escritorio_regional.descricao
			FROM tbl_promotor_treinamento
			JOIN tbl_escritorio_regional USING(escritorio_regional)
			JOIN tbl_admin               USING(admin)
			WHERE tbl_promotor_treinamento.fabrica = $login_fabrica
			AND   tbl_promotor_treinamento.ativo ='t'
			AND   tbl_promotor_treinamento.pais = 'BR'
			ORDER BY tbl_promotor_treinamento.nome";
		$res = pg_exec ($con,$sql) ;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$x_promotor_treinamento = pg_result ($res,$i,promotor_treinamento);
			$x_nome                 = pg_result ($res,$i,nome);

			echo "<option ";
			if ($promotor_treinamento == $x_promotor_treinamento ) echo " selected ";
			echo " value='$x_promotor_treinamento' >" ;
			echo $x_nome;
			echo "</option>\n";
		}
		echo "</select>";
		?>
	</TD>
</TR>
<tr><td>&nbsp;</td></tr>
<tr>
	<td width="10%">&nbsp;</td>
	<td colspan='2'>
		<fieldset style="width:300px;">
		<legend>Mostrar as OS:</legend>

			<INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>Em aprovação &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas  &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas &nbsp;&nbsp;&nbsp;
		</fieldset>
	</td>
</tr>
</tbody>
	<TD colspan="3" align="center">
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" onclick="javascript: if ( document.frm_consulta.btn_acao.value == '' ) { document.frm_consulta.btn_acao.value='Pesquisar'; document.frm_consulta.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer;" value='Pesquisar' />
	</TD>
</TR>
<tr><td>&nbsp;</td></tr>
</form>

</table>



<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {
	if(strlen($promotor_treinamento)>0) $sql_add = " AND tbl_promotor_treinamento.admin = $promotor_treinamento ";
	else                                $sql_add = " ";

	# HD 77122
	if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0){
		$sqlPosto = "SELECT posto FROM tbl_posto_fabrica 
					WHERE codigo_posto = '$posto_codigo' AND fabrica = $login_fabrica";
		
		$resPosto = pg_exec($con,$sqlPosto);
		if (pg_numrows($resPosto) == 1){
			$sqlCondPosto = "AND tbl_os.posto = ".pg_result($resPosto, 0, posto);
		}
		else
			$sqlCondPosto = ' AND 1=2';
	}

	$sql =  "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE status_os IN (92,93,94) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (92,93,94) ) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos
			;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			/* select os from  tmp_interv_$login_admin; */

			SELECT	tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.nota_fiscal_saida                                    ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_estado                            ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (92,93,94) ORDER BY data DESC LIMIT 1) AS status_descricao
				FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_promotor_treinamento ON tbl_promotor_treinamento.promotor_treinamento = tbl_os.promotor_treinamento
						$sql_add
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_posto.pais = 'BR'
				$sqlCondPosto
				";
	if($produto_referencia<>''){
		$sql .= " AND tbl_produto.referencia='$produto_referencia'";
	}
	if($login_fabrica==20) $sql .= " AND tipo_atendimento in (13,66) ";
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
	}
/*if ($ip == "200.230.81.54"){
	echo nl2br($sql);
	exit;
}*/
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";

		echo "<table width='800' border='0' align='center' cellpadding='0' cellspacing='1' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: pointer;' align='center' /></td>";
		echo "<td>OS</td>";
		echo "<td>Data <br>Digitação</B></td>";
		echo "<td>Data <br>Abertura</td>";
		echo "<td>Posto</td>";
		echo "<td>Produto</td>";
		echo "<td>Descrição</td>";
		echo "<td>Status</td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x=0; $x<pg_numrows($res);$x++){

			$os						= pg_result($res, $x, os);
			$sua_os					= pg_result($res, $x, sua_os);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$posto_nome				= pg_result($res, $x, posto_nome);
			$consumidor_nome		= pg_result($res, $x, consumidor_nome);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_result($res, $x, voltagem);
			$data_digitacao			= pg_result($res, $x, data_digitacao);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$status_os				= pg_result($res, $x, status_os);
			$status_observacao		= pg_result($res, $x, status_observacao);
			$status_descricao		= pg_result($res, $x, status_descricao);

			$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
				if($status_os==92){
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
					if (strlen($msg_erro)>0){
						if (strlen($_POST["check_".$x])>0){
							echo " CHECKED ";
						}
					}
					echo ">";
				}
			echo "</td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap ><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a></td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_abertura. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação do Promotor: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
			#echo "<td style='font-size: 9px; font-family: verdana'><a href=\"javascript: abreObs('$os','$codigo_posto','$sua_os')\">VER</a></td>";
			echo "</tr>";

		}
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr>";
		if(trim($aprova) == 'aprovacao'){
			echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
			echo "<select name='select_acao' size='1' class='frm' >";
			echo "<option value=''></option>";
			if ($login_fabrica == 20) {
				echo "<option value='93'";  if ($_POST["select_acao"] == "93")  echo " selected"; echo ">APROVADO OS</option>";
			} else {
				echo "<option value='93'";  if ($_POST["select_acao"] == "93")  echo " selected"; echo ">APROVADO PARA PAGAMENTO</option>";
			}
			echo "<option value='94'";  if ($_POST["select_acao"] == "94")  echo " selected"; echo ">GARANTIA RECUSADA</option>";
			echo "</select>";
			echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value=''  "; if ($_POST["select_acao"] == "19") echo " DISABLED "; echo ">";
			echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
		}
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "<input type='hidden' name='promotor_treinamento' value='$promotor_treinamento'>";
		echo "<input type='hidden' name='posto_codigo' value='$posto_codigo'>";
		echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
		echo "<input type='hidden' name='data_final' value='$data_final'>";
		echo "</table>";
		echo "</form>";
	}else{
		echo "<center>Nenhuma OS encontrada.</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>