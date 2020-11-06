<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

if ($login_fabrica <> 1 AND $login_fabrica <> 7 AND $login_fabrica <> 15) {
	exit;
}

if (strlen($_POST["acao"]) > 0) $btn_acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0) $btn_acao = strtoupper($_GET["acao"]);

# ---- excluir ---- #
$os = $_GET['excluir'];

if (strlen ($os) > 0) {

	if($login_fabrica == 1){
		// hd18827
		$sql="SELECT os FROM tbl_os_troca where os= $os";
		$res= pg_exec($con,$sql);
		if(pg_numrows($res) >0){
			$sql="UPDATE tbl_os_troca set status_os = 96 where os = $os; ";
			$res= pg_exec($con, $sql);

			$sql = "INSERT INTO tbl_os_status (
								os                     ,
								status_os              ,
								observacao             ,
								status_os_troca        ,
								data
							) VALUES (
								'$os'                  ,
								'96'                   ,
								'OS excluída por posto',
								't'                    ,
								CURRENT_TIMESTAMP
							);";
			$res = pg_exec ($con,$sql);
			//echo nl2br($sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_os SET excluida = true
							WHERE  tbl_os.os           = $os
							AND    tbl_os.fabrica      = $login_fabrica;";
				//echo nl2br($sql);
				$res = pg_exec($con,$sql);

				$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
			}
		}else{
			$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}else{
		$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

}

$excluir_revenda=$_GET['excluir_revenda'];

if(strlen($excluir_revenda) >0){
	$sql="UPDATE tbl_os_revenda SET excluida='t'
			WHERE os_revenda=$excluir_revenda
			AND   excluida='f' ";
	$res=pg_exec($con,$sql);
	$msg_erro .= pg_errormessage($con);
}

#------ Volta a OS de troca recusada para aprovação -----#
$os_troca_aprovacao = $_GET['troca_aprovacao'];

if (strlen ($os_troca_aprovacao) > 0) {
	if($login_fabrica == 1){
		$sql = "UPDATE tbl_os_troca SET status_os = null WHERE os = $os_troca_aprovacao;";
		$res = @pg_exec ($con,$sql);
	}
}

#---------------- Fim troca aprovação -------------------#

# ---- fechar ---- #
$os = $_GET['fechar'];
if (strlen ($os) > 0) {
//	include "ajax_cabecalho.php";

	$msg_erro = "";
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT status_os FROM tbl_os_status WHERE os = $os AND status_os IN (62,64,65,72,73,87,88) ORDER BY data DESC LIMIT 1";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res)>0){
		$status_os = trim(pg_result($res,0,status_os));
		if ($status_os=="87" || $status_os=="72" || $status_os=="65" || $status_os=="62"){
			$msg_erro .="OS com intervenção. Não pode ser fechada.";
		}
	}

	$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con) ;

	if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con) ;
	}
	if (strlen ($msg_erro) == 0 and $login_fabrica==1) {
		$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
		$res = @pg_exec ($con,$sql);
//echo $sql;
		$msg_erro = pg_errormessage($con);
		$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
		$destinatario = "samuel@telecontrol.com.br";
		$assunto      = "OS estoque VERIFICAR";
		$mensagem     = "OS estoque os = $os";
		$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
//		@mail($destinatario,$assunto,$mensagem,$headers);
	}
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo "ok;XX$os";
	}else{
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";
	}
	flush();
	exit;
}


$msg = "";


if($sistema_lingua == 'ES') $meses = array(1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
else                        $meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");


if (strlen($btn_acao) > 0 ) {
	$os_off              = trim (strtoupper ($_POST['os_off']));
	$codigo_posto_off    = trim(strtoupper($_POST['codigo_posto_off']));
	$posto_nome_off      = trim(strtoupper($_POST['posto_nome_off']));
	$tipo_os             = trim(strtoupper($_POST['tipo_os']));

	$sua_os = trim (strtoupper ($_POST['sua_os']));
	if (strlen($sua_os) == 0)
		$sua_os    = trim (strtoupper ($_GET['sua_os']));


	if(strlen($sua_os)>0 AND strlen($sua_os)<4)$msg="Favor digitar no minimo 4(quatro) caracteres";

	$serie     = trim (strtoupper ($_POST['serie']));
	$nf_compra = trim (strtoupper ($_POST['nf_compra']));
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));

	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	$codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
	$posto_nome         = trim(strtoupper($_POST['posto_nome']));
	$consumidor_nome    = trim($_POST['consumidor_nome']);
	$produto_referencia = trim(strtoupper($_POST['produto_referencia']));
	$os_aberta          = trim(strtoupper($_POST['os_aberta']));
	$revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));

	if ($login_e_distribuidor <> 't') $codigo_posto = $login_codigo_posto ;

/*	if (strlen ($consumidor_nome) > 0 AND strlen ($codigo_posto) == 0 ) {
		$msg = "Especifique o nome";
	}
*/
	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace (" ","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
		$msg = "Tamanho do CPF do consumidor inválido";
	}

	$revenda_cnpj = str_replace (".","",$revenda_cnpj);
	$revenda_cnpj = str_replace (" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("/","",$revenda_cnpj);
	if (strlen ($revenda_cnpj) <> 14 AND strlen ($revenda_cnpj) > 0) {
		$msg = "CNPJ inválido";
	}
	if (strlen ($revenda_cnpj) == 14 AND strlen ($revenda_cnpj) > 0) {
		$xsql = "SELECT revenda from tbl_revenda where cnpj='$revenda_cnpj' limit 1";
		$xres = pg_exec ($con, $xsql);
		$revenda_revenda = pg_result($xres,0,revenda);
		//echo $xsql

	}
	if (strlen ($nf_compra) > 0 ) {
		$nf_compra = "000000" . $nf_compra;
		$nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
	}

/*
	if ( (strlen ($codigo_posto) > 0 OR strlen ($posto_nome) > 0 OR strlen ($consumidor_nome) > 0 OR strlen ($produto_referencia) > 0 ) AND ( strlen ($mes) == 0 OR strlen ($ano) == 0) )  {
		$msg = "Digite o mês e o ano para fazer a pesquisa";
	}
*/

	if ( strlen ($sua_os) == 0 AND strlen ($serie) == 0 AND strlen ($nf_compra) == 0 AND strlen ($consumidor_cpf) == 0 AND  strlen ($mes) == 0 AND strlen ($ano) == 0 )  {
		$msg = "Data Inválida";
	}
/*
	if ( (strlen ($codigo_posto) == 0 AND strlen ($posto_nome) == 0 AND strlen ($consumidor_nome) == 0 AND strlen ($produto_referencia) == 0 AND strlen ($os_aberta) == 0 ) AND ( strlen ($mes) > 0 OR strlen ($ano) > 0) ) {
		$msg = "Especifique mais um campo para a pesquisa";
	}
*/
	if (strlen ($mes) == 0 AND strlen ($ano) > 0) {
		$msg = "Selecione o mês";
	}

	if ( strlen ($posto_nome) > 0 AND strlen ($posto_nome) < 5 ) {
		$msg = "Digite no mínimo 5 letras para o nome do posto";
	}

	if ( strlen ($consumidor_nome) > 0 AND strlen ($consumidor_nome) < 5) {
		$msg = "Digite no mínimo 5 letras para o nome do consumidor";
	}

	if ( strlen ($serie) > 0 AND strlen ($serie) < 5) {
		$msg = "Digite no mínimo 5 letras para o número de série";
	}


	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	if (strlen($msg) == 0 && strlen($opcao2) > 0) {
		if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
		if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);
		if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
		if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);
		if (strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);

		if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto.posto                ,
							tbl_posto.nome                 ,
							tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = trim(pg_result($res,0,posto));
				$posto_codigo = trim(pg_result($res,0,codigo_posto));
				$posto_nome   = trim(pg_result($res,0,nome));
			}else{
				$erro .= " Posto não encontrado. ";
			}
		}
	}
}

$layout_menu = "os";

if($sistema_lingua == 'ES') $title = "Seleción de Parámetros para Relación  de Órdenes de Servicio digitadas";
else                        $title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";
include "cabecalho.php";
?>

<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
	background-color: #596D9B;
	color: white;
	font: normal normal bold 11px/normal Arial;
	text-align: center;
}
input[type=button]{
	cursor:pointer;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
.littleFont{
    font:bold 11px Arial;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.espaco{
	padding-left:80px;
	width:220px;
}
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<script language='javascript' src='ajax.js'></script>


<script language='javascript'>
function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	var tamanho;
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
		tamanho = 4;
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
		tamanho = 6;
	}
	if (campo.value != "") {
        if (campo.value.length >= tamanho) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_consulta.revenda_nome;
			janela.cnpj			= document.frm_consulta.revenda_cnpj;
			janela.fone			= document.frm_consulta.revenda_fone;
			janela.cidade		= document.frm_consulta.revenda_cidade;
			janela.estado		= document.frm_consulta.revenda_estado;
			janela.endereco		= document.frm_consulta.revenda_endereco;
			janela.numero		= document.frm_consulta.revenda_numero;
			janela.complemento	= document.frm_consulta.revenda_complemento;
			janela.bairro		= document.frm_consulta.revenda_bairro;
			janela.cep			= document.frm_consulta.revenda_cep;
			janela.email		= document.frm_consulta.revenda_email;
			janela.focus();
		 }else{
            alert("Digite pelo menos "+tamanho+" caracteres para efetuar a pesquisa!");
        }
    }else{
		alert("Digite pelo menos "+tamanho+" caracteres para efetuar a pesquisa!");
	}
}

function fnc_pesquisa_produto(campo, campo2, tipo, voltagem, referencia_pai, descricao_pai, referencia_avo, descricao_avo) {
	
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		if (referencia_pai != "") {
			janela.referencia_pai = referencia_pai;
		}
		if (descricao_pai != "") {
			janela.descricao_pai = descricao_pai;
		}
		if (referencia_avo != "") {
			janela.referencia_avo = referencia_avo;
		}
		if (descricao_avo != "") {
			janela.descricao_avo = descricao_avo;
		}
		janela.focus();
	}else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }

}

function retornaFechamentoOS (http , sinal, excluir, lancar) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					alert ('OS ' + results[0] + ' fechada com sucesso' );
					sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
					sinal.src='/assist/imagens/pixel.gif';
					excluir.src='/assist/imagens/pixel.gif';
					if (lancar){
						lancar.src='/assist/imagens/pixel.gif';
					}
				}else{
					if (http.responseText.indexOf ('de-obra para instala') > 0) {
						alert ('Esta OS não tem mão-de-obra para instalação');
					}else if (http.responseText.indexOf ('Nota Fiscal de Devol') > 0) {
						alert ('Erro no Fechamento da OS. \nPor favor utilizar a tela de Fechamento de OS para informar a Nota Fiscal de Devolução.');
					}else if (http.responseText.indexOf ('o-de-obra para atendimento') > 0) {
						alert ('Esta OS não tem mão-de-obra para este atendimento');
					}else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios') > 0) {
						alert ('Erro no Fechamento da OS. \nPor favor, verifique os dados digitados, aparência e acessórios, na tela de lançamento de itens.');
					}else if (http.responseText.indexOf ('Type informado para o produto não é válido') > 0) {
						alert ('Type informado para o produto não é válido');
					} else {
						alert ('Erro no Fechamento da OS. \nPor favor, verifique os dados digitados, defeito constatado e solução, na tela de lançamento de itens.');
					}
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function fechaOS (os , sinal , excluir , lancar ) {

	var xsinal   = document.getElementById(sinal);
	var xexcluir = document.getElementById(excluir);
	var xlancar = document.getElementById(lancar);

	url = "<?= $PHP_SELF ?>?fechar=" + escape(os) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFechamentoOS (http , xsinal, xexcluir, xlancar) ; } ;
	http.send(null);
}

function verifica_os(){
	var sua_os   = document.getElementById('sua_os');
	if(sua_os.value.length>0){
		for(x=0;x<6;x++){
		var tipo_os  = document.getElementById('tipo_os_'+x);
			tipo_os.disabled = true;
			tipo_os.checked  = false;
		}
	}else{
		for(x=0;x<6;x++){
			var tipo_os  = document.getElementById('tipo_os_'+x);
			tipo_os.disabled = false;
		}
	}

}
</script>
<br>

<table align="center" width="700" cellspacing="1">
	<tr>
		<td align="center" class="texto_avulso">Este Relatório considera a data de digitação das OS</td>
	</tr>
</table>
<br>
<?
if (strlen($msg) > 0) {
	echo "<div align='center'><div class='msg_erro' align='center' style='width:700px'>$msg</div></div>";
}

if (strlen($msg_erro) > 0) {
	echo "<div align='center'><div class='msg_erro' align='center' style='width:700px'>".ucwords($msg_erro)."</div></div>";
}
?>
<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">

<table align="center" width="700" cellspacing="1" class="formulario">
	<tr class="titulo_tabela">
		<td align="center">Parâmetros de Pesquisa</td>
	</tr>
</table>

<table align="center" width="700px" cellspacing="1" border="0" class="formulario">
	<tr align='left'>
		<td width="150px">&nbsp;</td>
		<td >Nº da OS</td>
		<td align="right"><label style="padding: 0 119px 0 0">Nº de Série</label></td>
		
		<td width="150px">&nbsp;</td>

	</tr>
	
	
	<tr>
		
		<td width="150px">&nbsp;</td>
		<td>
			<input type="text" name="sua_os" id="sua_os"  onblur='javascript:verifica_os();'  size="20" value="<?echo $sua_os?>"    class="frm">
		</td>
		
		<td align="right">
			<input type="text" name="serie" style="margin: 0 20px 0 0 "    size="22" value="<?echo $serie?>"     class="frm">
		</td>
		<td width="150px">&nbsp;</td>



	</tr>
	
	<tr align='left'>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	<tr>
		<td width="150px">&nbsp;</td>

		<td >NF. Compra</td>
		<td align="right"><label style="padding: 0 96px 0 0"> CPF Consumidor</td>
		<td width="150px">&nbsp;</td>

	</tr>
	
	
	
	<tr align='left'>
		<td width="150px">&nbsp;</td>
		
		<td ><input type="text" name="nf_compra" size="20" value="<?echo $nf_compra?>" class="frm"></td>
		<td align="right"><input style="margin: 0 20px 0 0" type="text" name="consumidor_cpf" size="22" value="<?echo $consumidor_cpf?>" class="frm"></td>
		
		<td width="150px">&nbsp;</td>
		
	</tr>
	
	<tr align='left'>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan='4' align="center">
			<label>
				<input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> >Apenas OS em aberto
			</label>
		</td>
	</tr>

	<tr>
		<td colspan='4' align='center'><BR>
			<input type="button" value="Pesquisar"  onclick="document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();" />
			<br />
			<br />
		</td>
	</tr>
</table>


<table align="center" width="700" cellspacing="1" border="0" class="formulario">
	
	<tr class="subtitulo" align='left'>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	<tr align='left'>
		<td colspan="4">&nbsp;</td>
	</tr>

	<tr>
		<td width="150px">&nbsp;</td>
		
		<td>Mês *</td>
		
		<td align="right"><label style="padding:0 51px 0 0">Ano *</label></td>
		
		<td width="150px">&nbsp;</td>
	</tr>
	

	
	<tr align='left'>
		<td width="150px">&nbsp;</td>
		
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		
		<td align="right">
			<select style="margin:0 20px 0 0 " name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			//for ($i = 2003 ; $i <= date("Y") ; $i++) {
			for($i = date("Y"); $i > 2003; $i--){
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
			
		</td>
		
		<td width="150px">&nbsp;</td>
	</tr>

	<tr align='left'>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	<tr align='left'>
		<td width="150px">&nbsp;</td>
		<td colspan='2'>Nome do Consumidor</td>
		<td width="150px">&nbsp;</td>
	</tr>

	<tr>
		<td width="150px">&nbsp;</td>
		<!-- HD 216395: Mudar todas as buscas de nome para LIKE com % apenas no final. A funcao function mostrarMensagemBuscaNomes() está definida no cabecalho.php -->
		<td colspan='2'><input type="text" name="consumidor_nome" size="58" value="<?echo $consumidor_nome?>" class="frm"> <img src='imagens/help.png' title='Clique aqui para ajuda na busca deste campo' onclick='mostrarMensagemBuscaNomes()'></td>
		<td width="150px">&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>

	<tr>
		<td width="150px">&nbsp;</td>
		<td>Ref. Produto</td>
		<td align="right"><label style="padding:0 86px 0 0"> Descrição Produto </label> </td>
		<td width="150px">&nbsp;</td>
	</tr>
	<tr align='left'>
		<td width="150px">&nbsp;</td>
		<td>
			<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
			<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia', document.frm_consulta.produto_voltagem)" style='cursor: pointer' />
		</td>
		<td align="right">
			<input class="frm" type="text" name="produto_descricao" size="22" value="<? echo $produto_descricao ?>" >
			<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao', document.frm_consulta.produto_voltagem)" style='cursor: pointer' />
			<input type='hidden' name = 'produto_voltagem'>
		</td>
		<td width="150px">&nbsp;</td>
	</tr>
	
	<tr align='left'>
		<td colspan="4">&nbsp;</td>
	</tr>

	<tr align='left'>
		<td width="150px">&nbsp;</td>
		<td>CNPJ Revenda</td>
		<td align="right"><label style="padding:0 102px 0 0">Nome Revenda</td>
		<td width="150px">&nbsp;</td>
	</tr>

	<tr>
		<td width="150px">&nbsp;</td>
		
		<td>
			<input type="text" class="frm"  name="revenda_cnpj" size="15" value="<?echo $revenda_cnpj?>">
			<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="fnc_pesquisa_revenda (document.frm_consulta.revenda_cnpj, 'cnpj')" style='cursor: pointer' />
		</td>
		
		<td align="right">
			<input type="text" class="frm"  name="revenda_nome" size="22" value="<?echo $revenda_nome?>">
			<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="fnc_pesquisa_revenda (document.frm_consulta.revenda_nome, 'nome');" style='cursor: pointer' />
		</td>
	
		<td width="150px">&nbsp;</td>
	</tr>
	
	<tr align='left'>
		<td colspan="4">&nbsp;</td>
	</tr>
	
	<tr align='left'>
		<td width="150px">&nbsp;</td>
		
		<td colspan='2'>
			<fieldset style="width:344px;">
				<legend>Escolha o tipo da OS</legend>
		
				<label>
					<input type='radio' name='tipo_os' id='tipo_os_0' value='' <?php if(strlen($btn_acao) <= 0) echo "checked";?> onClick='javascript:verifica_os();' > Todas
				</label>

				<label>
					<input type='radio' name='tipo_os' id='tipo_os_1' value='C' onClick='javascript:verifica_os();' > Consumidor
				</label>

				<label>
					<input type='radio' name='tipo_os' id='tipo_os_2' value='R' onClick='javascript:verifica_os();' > Revenda
				</label>
				<? if($login_fabrica == 1) { ?>
					<label>
						<input type='radio' name='tipo_os' id='tipo_os_3' value='X' onClick='javascript:verifica_os();' > Cortesia
					</label>
					<label>
						<input type='radio' name='tipo_os' id='tipo_os_4' value='T' onClick='javascript:verifica_os();' > Troca
					</label>
				<? } ?>
			</fieldset>
			<br />
		</td>
		
		<td width="150px">&nbsp;</td>
	</tr>
	
</table>

<input type='hidden' name = 'revenda_fone'>
<input type='hidden' name = 'revenda_cidade'>
<input type='hidden' name = 'revenda_estado'>
<input type='hidden' name = 'revenda_endereco'>
<input type='hidden' name = 'revenda_numero'>
<input type='hidden' name = 'revenda_complemento'>
<input type='hidden' name = 'revenda_bairro'>
<input type='hidden' name = 'revenda_cep'>
<input type='hidden' name = 'revenda_email'>


<table align="center" width="700" cellspacing="1" class="formulario">
	<tr bgcolor="#D9E2EF">
		<td colspan='2' align='center'>
			<br>
			<input type="button" value="Pesquisar" onclick="document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();"/>
			<br />
			<br />
		</td>
	</tr>
</table>


</form>


<?php

if (strlen($btn_acao) > 0 AND strlen($msg) == 0) {

		if ($login_e_distribuidor <> 't') {
			$posto = $login_posto ;
		}

		$join_especifico = "";
		$especifica_mais_1 = "1=1";
		$especifica_mais_2 = "1=1";

		if (strlen ($data_inicial) > 0) {
			if (strlen ($produto_referencia) > 0) {
				$sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
				$resX = pg_exec ($con,$sqlX);
				$produto = pg_result ($resX,0,0);
				$especifica_mais_1 = "tbl_os.produto = $produto";
			}

			if (strlen($os_aberta) > 0) {
				$especifica_mais_2 = "tbl_os.os_fechada IS FALSE";
			}

			$join_especifico = "JOIN (  SELECT os
										FROM tbl_os
										JOIN tbl_os_extra USING (os)
										JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
										LEFT JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha AND tbl_posto_linha.posto = tbl_os.posto
										WHERE fabrica = $login_fabrica
										AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
										AND   (tbl_os.posto   = $login_posto OR (tbl_posto_linha.distribuidor = $login_posto AND tbl_posto_linha.distribuidor IS NOT NULL AND $login_fabrica=3))
										AND   $especifica_mais_1
										AND   $especifica_mais_2
								) oss ON tbl_os.os = oss.os ";
		}

		// OS não excluída
		$sql = "SELECT
					A.os                  ,
					A.sua_os              ,
					A.sua_os_offline      ,
					A.ordem               ,
					A.digitacao           ,
					A.abertura            ,
					A.fechamento          ,
					A.finalizada          ,
					A.explodida           ,
					A.codigo_fabricacao   ,
					A.serie               ,
					A.excluida            ,
					A.motivo_atraso       ,
					A.cortesia            ,
					A.tipo_os_cortesia    ,
					A.consumidor_revenda  ,
					A.consumidor_nome     ,
					A.revenda_nome        ,
					A.tipo_atendimento    ,
					A.valores_adicionais  ,
					A.tecnico_nome        ,
					A.admin               ,
					A.reincidencia        ,
					A.id_tipo_os          ,
					A.descricao           ,
					A.codigo_posto        ,
					A.posto_nome          ,
					A.impressa            ,
					A.extrato             ,
					A.os_reincidente      ,
					A.id_tipo_os          ,
					A.produto_referencia  ,
					A.produto_descricao   ,
					A.produto_voltagem    ,
					A.codigo_distrib      ,
					A.status_os           ,
					A.xrevenda_revenda    ,
					A.os_geo
					FROM ( ( ";
		$sql .=  "SELECT distinct tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						sua_os_offline                                                    ,
						LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
						TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
						null                                         as explodida         ,
						tbl_os.codigo_fabricacao                                          ,
						tbl_os.serie                                                      ,
						tbl_os.excluida                                                   ,
						tbl_os.motivo_atraso                                              ,
						tbl_os.cortesia                                                   ,
						tbl_os.tipo_os_cortesia                                           ,
						tbl_os.consumidor_revenda                                         ,
						tbl_os.consumidor_nome                                            ,
						tbl_os.revenda_nome                                               ,
						tbl_os.tipo_atendimento                                           ,
						tbl_os.valores_adicionais                                         ,
						tbl_os.tecnico_nome                                               ,
						tbl_os.admin                                                      ,
						tbl_os.os_reincidente                      AS reincidencia        ,
						tbl_os.tipo_os                             AS id_tipo_os          ,
						tbl_tipo_atendimento.descricao                                    ,
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						distrib.codigo_posto                        AS codigo_distrib     ,
						(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os ORDER BY data DESC LIMIT 1) AS status_os,
						0 as xrevenda_revenda,
						false::boolean as os_geo
				FROM      tbl_os
				$join_especifico
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os";
				//colocado takashi
		if (strlen($os_situacao) > 0) {
			$sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
			if ($os_situacao == "PAGA")
				$sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
		}
//TULIO
		$sql .=	"
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_tipo_atendimento      ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				WHERE tbl_os.fabrica = $login_fabrica
				AND   (tbl_os.posto  = $login_posto)
				AND   tbl_os.excluida IS NOT TRUE
				AND   ( tbl_os.tipo_atendimento not in (64,65,69) OR tbl_os.tipo_atendimento IS NULL )
				AND  ((status_os NOT IN (13,15) OR status_os IS NULL) OR ( ( select tbl_os_status.status_os_troca FROM tbl_os_status where tbl_os_status.os= tbl_os.os order by data desc limit 1) IS TRUE) ) ";

#				AND   (tbl_os.posto   = $login_posto OR tbl_os_extra.distribuidor = $login_posto)

		if (strlen($mes) > 0) {
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}

		if (strlen($produto_referencia) > 0) {
			$sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
		}
		if (strlen($tipo_os) > 0) {
			if($tipo_os == "C" OR $tipo_os == "R"){
				$sql .= " AND tbl_os.consumidor_revenda = '$tipo_os' and tbl_os.cortesia is FALSE  and tbl_os.tipo_atendimento is null";
			}elseif($tipo_os == "T"){ //troca
				$sql .= " AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) ";
			}elseif($tipo_os == "X"){//cortesia
				$sql .= " AND tbl_os.cortesia is TRUE ";
			}

		}

/*		if (strlen($admin) > 0) {
			$sql .= " AND tbl_os.admin = '$admin' ";
		}
*/
		if (strlen($sua_os) > 0) {
			$sua_os2 = $sua_os;
			$sua_os = "000000" . trim ($sua_os);
			if(strlen ($sua_os) > 12 AND $login_fabrica == 1) {
				$sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
			}elseif(strlen ($sua_os) > 11 AND $login_fabrica == 1){#46900
				$sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
			}else{
				$sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
			}
			$sua_os = strtoupper ($sua_os);

			$sql .= "   AND (
						tbl_os.sua_os = '$sua_os' OR
						tbl_os.sua_os = '0$sua_os' OR
						tbl_os.sua_os = '00$sua_os' OR
						tbl_os.sua_os = '000$sua_os' OR
						tbl_os.sua_os = '0000$sua_os' OR
						tbl_os.sua_os = '00000$sua_os' OR
						tbl_os.sua_os = '000000$sua_os' OR
						tbl_os.sua_os = '0000000$sua_os' OR
						tbl_os.sua_os = '00000000$sua_os' OR
						tbl_os.sua_os = substr('$sua_os2',6,length('$sua_os2')) OR
						tbl_os.sua_os = substr('$sua_os2',7,length('$sua_os2')) 	";

			/* hd 4111 */
			for ($i=1;$i<=40;$i++) {
				$sql .= "OR tbl_os.sua_os = '$sua_os-$i' ";
			}
			$sql .= " OR 1=2) ";
		}
		if (strlen($os_off) > 0) {
			$sql .= " AND (tbl_os.sua_os_offline LIKE '$os_off%' OR tbl_os.sua_os_offline LIKE '0$os_off%' OR tbl_os.sua_os_offline LIKE '00$os_off%') ";
		}



		if (strlen($serie) > 0) {
			$sql .= " AND tbl_os.serie = '$serie'";
		}

		if (strlen($nf_compra) > 0) {
			$sql .= " AND tbl_os.nota_fiscal = '$nf_compra'";
		}

		if (strlen($consumidor_nome) > 0) {
			$sql .= " AND tbl_os.consumidor_nome LIKE '$consumidor_nome%'";
		}

		if (strlen($consumidor_cpf) > 0) {
			$sql .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
		}

		if (strlen($os_aberta) > 0) {
			$sql .= " AND tbl_os.os_fechada IS FALSE ";
		}

		if (strlen($revenda_revenda) > 0) {
			$sql .= " AND tbl_os.revenda = $revenda_revenda ";
		}

		$sql .= " ) UNION ( ";

		$sql .= "
			SELECT distinct tbl_os_revenda.os_revenda as os                         ,
					tbl_os_revenda.sua_os                                           ,
					NULL AS sua_os_offline                                          ,
					LPAD(tbl_os_revenda.sua_os,20,'0') AS ordem                     ,
					TO_CHAR(tbl_os_revenda.digitacao,'DD/MM/YYYY') AS digitacao     ,
					TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura  ,
					NULL AS fechamento                                              ,
					NULL AS finalizada                                              ,
					TO_CHAR(tbl_os_revenda.explodida,'DD/MM/YYYY')     as explodida ,
					NULL AS codigo_fabricacao                                       ,
					NULL AS serie                                                   ,
					false  AS excluida                                              ,
					NULL AS motivo_atraso                                           ,
					false  AS cortesia                                              ,
					NULL AS tipo_os_cortesia                                        ,
					'R'  AS consumidor_revenda                                      ,
					NULL AS consumidor_nome                                         ,
					tbl_revenda.nome as revenda_nome                                ,
					0 AS tipo_atendimento                                           ,
					0 AS valores_adicionais                                         ,
					NULL AS tecnico_nome                                            ,
					0 AS admin                                                      ,
					tbl_os_revenda.os_reincidente AS reincidencia                   ,
					NULL::numeric as id_tipo_os                                     ,
					NULL AS descricao                                               ,
					tbl_posto_fabrica.codigo_posto                                  ,
					tbl_posto.nome AS posto_nome                                    ,
					current_timestamp AS impressa                                   ,
					0 AS extrato                                                    ,
					0 AS os_reincidente                                             ,
					NULL AS produto_referencia                                      ,
					NULL AS produto_descricao                                       ,
					NULL AS produto_voltagem                                        ,
					NULL AS codigo_distrib                                          ,
					0 AS status_os                                                  ,
					1 as revenda_revenda                                            ,
					tbl_os_revenda.os_geo
			FROM tbl_os_revenda
			JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os_revenda.revenda
			JOIN tbl_posto on tbl_os_revenda.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica  on tbl_posto_fabrica.posto = tbl_posto.posto
			and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os_revenda.fabrica  = $login_fabrica
			AND   tbl_os_revenda.posto    = $login_posto
			AND   tbl_os_revenda.excluida IS NOT TRUE
			AND   ( tbl_os_revenda.tipo_atendimento not in (64,65,69) OR tbl_os_revenda.tipo_atendimento IS NULL )
			and   ( 1 = 2 ";
			if (strlen($tipo_os) == 0 or $tipo_os == "R") {
				$sql .= " or ( 1 = 1 ";
				if (strlen($mes) > 0) {
					$sql .= " AND tbl_os_revenda.digitacao BETWEEN '$data_inicial' AND '$data_final'";
				}
				if (strlen($nf_compra) > 0) {
					$sql .= " AND tbl_os_revenda.nota_fiscal = '$nf_compra'";
				}
				if (strlen($sua_os) > 0) {
					$sua_os = "000000" . trim ($sua_os);
					$sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);

					$sua_os = strtoupper ($sua_os);

					$sql .= " AND (
						tbl_os_revenda.sua_os = '$sua_os' OR
						tbl_os_revenda.sua_os = '0$sua_os' OR
						tbl_os_revenda.sua_os = '00$sua_os' OR
						tbl_os_revenda.sua_os = '000$sua_os' OR
						tbl_os_revenda.sua_os = '0000$sua_os' OR
						tbl_os_revenda.sua_os = '00000$sua_os' OR
						tbl_os_revenda.sua_os = '000000$sua_os' OR
						tbl_os_revenda.sua_os = '0000000$sua_os' OR
						tbl_os_revenda.sua_os = '00000000$sua_os')";
				}
				if (strlen($revenda_revenda) > 0) {
					$sql .= " AND tbl_os_revenda.revenda = $revenda_revenda ";
				}
				$sql .= " ) ";
			}
	if ($login_fabrica==7){
		$sql .= " ) ) ) as A ORDER BY A.sua_os ASC";
	}else{
		$sql .= " ) ) ) as A ORDER BY SUBSTRING(A.sua_os,1,5) ASC";
	}

	$sqlT = str_replace ("\n"," ",$sql) ;
	$sqlT = str_replace ("\t"," ",$sqlT) ;
	$resT = @pg_exec ($con,"/* QUERY -> $sqlT  */");

	$res = pg_exec($con,$sql);

	$resultados = pg_numrows($res);

	if (pg_numrows($res) > 0) {
?>
		<form name="frm_os" method="post" action="<?echo $PHP_SELF?>">
<?
		##### LEGENDAS - INÍCIO #####
		echo "<div align='left' style='position: relative;'>";
		echo '<table align="center" width="700" cellspacing="1">';
		if ($excluida == "t") {
			echo "<tr height='18' class='titulo_coluna'>";
			echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Excluídas do sistema</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica != 1) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'>&nbsp; ";
			if($sistema_lingua=='ES')echo "Reincidencia";else echo "Reincidências.";
			echo "</td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";

			if($login_fabrica <> 14){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				echo "OS aberta a mais de 25 dias.";
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}
		}else{
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS aberta a mais de 25 dias.";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			if($sistema_lingua=='ES')echo "Reincidencia";else echo "Reincidências.";
			echo "</b></font></td>";
			echo "</tr>";

			if($login_fabrica==1 AND $login_posto==6359){
				echo "<tr height='3'><td colspan='2'></td></tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFCCCC'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				echo "OS com Intervenção da Fábrica. Aguardando Liberação";
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";

				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#CCFFFF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				echo "OS Liberada Pela Fábrica";
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}

			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		echo "</table>";
		echo "</div>";
		##### LEGENDAS - FIM #####

		echo "<br>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i % 50 == 0) {
				echo "</table>";
				flush();
				echo '<table align="center" width="100%" cellspacing="1" class="tabela">';
			}

			if ($i % 50 == 0) {
				echo "<tr class='titulo_coluna' height='25'>";
				echo "<td width='100' nowrap>OS</td>";
				if($login_fabrica==19 OR $login_fabrica==10){
					echo "<td>OS OFF LINE</td>";
				}
				echo "<td width='150'>Série</td>";
				echo "<td><acronym title='Código de Fabricação' style='cursor: help;'>C.Fabr.</td>";
				echo "<td>Abertura</td>";
				echo "<td><acronym title='Data de fechamento registrada pelo sistema' style='cursor:help;'>Fechamento</a></td>";
				echo "<td>Cliente</td>";
				echo "<td>";
				if($sistema_lingua=='ES')echo "Producto";else echo "Produto";
				echo "</td>";
				if($login_fabrica==19){
					echo "<td>Atendimento</td>";
					echo "<td nowrap>Técnico</td>";
					}
				echo "<td><img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'></td>";
				if($login_fabrica == 1 ){
					echo "<td><img border='0' width='20' heigth='20' src='imagens/envelope.png' alt='Carta Registrada' title='Carta Registrada'></td>";
				}
				echo "<td>Item</td>";
				if ($login_fabrica == 1) {
					echo "<td>Tipo</td>";
					$colspan = "8";
				}else{
					$colspan = "6";
				}
				
				if ($login_fabrica == 7) {
				    $colspan +=  1;
				}
				
				echo "<td colspan='$colspan'>Ações</td>";
			}

			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$fechamento         = trim(pg_result($res,$i,fechamento));
			$finalizada         = trim(pg_result($res,$i,finalizada));
			$serie              = trim(pg_result($res,$i,serie));
			$excluida           = trim(pg_result($res,$i,excluida));
			$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
			$os_cortesia        = trim(pg_result($res,$i,cortesia));
			$tipo_os_cortesia   = trim(pg_result($res,$i,tipo_os_cortesia));
			$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
			$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
			$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$impressa           = trim(pg_result($res,$i,impressa));
			$extrato            = trim(pg_result($res,$i,extrato));
			$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
			$reincidencia       = trim(pg_result($res,$i,reincidencia));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));
			$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
			$valores_adicionais = trim(pg_result($res,$i,valores_adicionais));
			$tecnico_nome       = trim(pg_result($res,$i,tecnico_nome));
			$nome_atendimento   = trim(pg_result($res,$i,descricao));
			$admin              = trim(pg_result($res,$i,admin));
			$sua_os_offline     = trim(pg_result($res,$i,sua_os_offline));
			$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
			$status_os          = trim(pg_result($res,$i,status_os));
			$codigo_fabricacao  = trim(pg_result($res,$i,codigo_fabricacao));
			$explodida          = trim(pg_result($res,$i,explodida));
			$xrevenda_revenda    = trim(pg_result($res,$i,xrevenda_revenda));
			$id_tipo_os          = trim(pg_result($res,$i,id_tipo_os));
			$os_metal            = trim(pg_result($res,$i,os_geo));

			$sql2 = "SELECT status_os FROM tbl_os_status WHERE os = $os AND status_os IN (62,64,65,72,73,87,88) ORDER BY data DESC LIMIT 1";
			$res3 = pg_exec ($con,$sql2);
			if (pg_numrows($res3)>0){
				$status_os_intervencao = trim(pg_result($res3,0,status_os));
			}

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			$excluir_revenda="";

			// hd 23296
			$sql2="SELECT count(tbl_os.os) AS qtde_os_revenda_excluida
					FROM  tbl_os_revenda
					JOIN  tbl_os ON tbl_os.os_numero::numeric=tbl_os_revenda.sua_os::numeric AND tbl_os_revenda.posto = tbl_os.posto
					WHERE tbl_os_revenda.sua_os='$sua_os'
					AND   tbl_os_revenda.posto=$login_posto
					AND   tbl_os_revenda.fabrica=$login_fabrica
					AND   tbl_os_revenda.excluida IS FALSE
					AND   tbl_os.consumidor_revenda ='R'
					AND   (tbl_os.excluida='t' OR tbl_os.fabrica=0)";
			$res2=@pg_exec($con,$sql2);

			$qtde_os_revenda_excluida=@pg_result($res2,0,qtde_os_revenda_excluida);

			if($qtde_os_revenda_excluida >0) {
				$sql3="SELECT count(tbl_os.os) as qtde_os_revenda
						FROM  tbl_os_revenda
						JOIN  tbl_os ON tbl_os.os_numero::numeric=tbl_os_revenda.sua_os::numeric AND tbl_os_revenda.posto = tbl_os.posto
						WHERE tbl_os_revenda.sua_os='$sua_os'
						AND   tbl_os_revenda.posto=$login_posto
						AND   tbl_os_revenda.fabrica=$login_fabrica
						AND   tbl_os.consumidor_revenda ='R'";
				$res3=@pg_exec($con,$sql3);
				$qtde_os_revenda=@pg_result($res3,0,qtde_os_revenda);
				if($qtde_os_revenda_excluida == $qtde_os_revenda){
					$excluir_revenda='t';
				}
			}

			if ($status_os_intervencao=="62") $cor="#FFCCCC";
			if ($status_os_intervencao=="72") $cor="#FFCCCC";
			if ($status_os_intervencao=="87") $cor="#FFCCCC";

			if ($status_os_intervencao=="73" && strlen($fechamento)==0) $cor="#CCFFFF";
			if ($status_os_intervencao=="64" && strlen($fechamento)==0) $cor="#CCFFFF";


			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####
			if ($excluida == "t")		$cor = "#FFE1E1";
			if ($reincidencia =='t')	$cor = "#D7FFE1";

			// OSs abertas há mais de 25 dias sem data de fechamento
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";
			}

			if ($login_fabrica==3){
				if ($status_os=="62") $cor="#E6E6FA";
				if ($status_os=="62") $cor="#FFCCCC";
				if ($status_os=="72") $cor="#FFCCCC";

				if ($status_os=="64" && strlen($fechamento)==0) $cor="#CCFFFF";
				if ($status_os=="65") $cor="#FFFF99";
			}

			if($login_fabrica == 1){
				//if($tipo_atendimento == 17 OR $tipo_atendimento == 18) { $cor = "#FFCC66"; }
			}

			// CONDIÇÕES PARA INTELBRÁS - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
			}
			// CONDIÇÕES PARA INTELBRÁS - FIM

			// CONDIÇÕES PARA BLACK & DECKER - INÍCIO
			// Verifica se não possui itens com 5 dias de lançamento
			if ($login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$data_hj_mais_5 = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
						FROM tbl_os_item
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
						WHERE tbl_os.os = $os
						AND   tbl_os.data_abertura::date >= '$aux_consulta'";
				$resItem = pg_exec($con,$sql);

				$itens = pg_result($resItem,0,total_item);

				if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

				$mostra_motivo = 2;

				//Troca Faturada. HD2365
				$sqlT = "SELECT status_os FROM tbl_os_troca WHERE os = $os;";
				$resT = pg_exec($con, $sqlT);
				if(pg_numrows($resT) > 0){
					$garantia_faturada = pg_result($resT,0,0);
				}
			}

			// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
			if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R") {
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#91C8FF";
					}
				}
			}

			// Se estiver acima dos 30 dias, não exibirá os botões
			if (strlen($fechamento) == 0 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '30 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R"){
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#FF0000";
					}
				}
			}
			// CONDIÇÕES PARA BLACK & DECKER - FIM

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

			if (strlen($sua_os) == 0) $sua_os = $os;
			if ($login_fabrica == 1) $xsua_os =  $codigo_posto.$sua_os ;

			echo "<tr height='15' bgcolor='$cor' align='left'>";
			echo "<td nowrap width='100'>&nbsp;" ;
			if ($login_fabrica == 1){echo $xsua_os; }else{ echo $sua_os;}
			echo "</td>";
			if($login_fabrica==19 OR $login_fabrica==10){
				echo "<td nowrap>&nbsp;" . $sua_os_offline . "</td>";
			}
			echo "<td width='55' nowrap>&nbsp;".$serie."</td>";
			echo "<td width='55' nowrap>&nbsp;".$codigo_fabricacao."</td>";
			echo "<td nowrap >&nbsp;<acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "/".substr($abertura,8,2)."</acronym></td>";

			if ($login_fabrica == 1) $aux_fechamento = $finalizada;
			else                     $aux_fechamento = $fechamento;
			echo "<td nowrap>&nbsp;<acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>";
			//parei
			if(strlen($aux_fechamento)>0){ echo substr($aux_fechamento,0,5) ; echo "/"; echo substr($aux_fechamento,8,2);}
			echo "</acronym></td>";
			echo "<td width='120' nowrap>";
			echo "<acronym title='";
			if ($consumidor_revenda == "R") { 
				echo ($id_tipo_os == 13) ? $consumidor_nome : $revenda_nome;
			}else{
				echo $consumidor_nome ; 
			}
			echo "' style='cursor: help;'>";
			if ($consumidor_revenda == "R"){
				echo ($id_tipo_os == 13) ? substr($consumidor_nome,0,15) : substr($revenda_nome,0,15);
			}else{
				echo  substr($consumidor_nome,0,15) ;
			}

			echo "</acronym></td>";
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td width='70' nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,8) . "</acronym></td>";
			if($login_fabrica==19){
				echo"<td nowrap>$tipo_atendimento - $nome_atendimento </td>";
				echo"<td width='90' nowrap><acronym title='Nome do técnico: $tecnico_nome' style='cursor: help;'>" . substr($tecnico_nome,0,11) . "</acronym></td>";
				}


			##### VERIFICAÇÃO SE A OS FOI IMPRESSA #####
			echo "<td width='30' align='center'>";
			if (strlen($admin) > 0 and $login_fabrica == 19) echo "<img border='0' src='imagens/img_sac_lorenzetti.gif' alt='OS lançada pelo SAC Lorenzetti' title='OS lançada pelo SAC Lorenzetti'>";
			else if (strlen($impressa) > 0)                  echo "<img border='0' src='imagens/img_ok.gif' alt='OS já foi impressa' title='OS já foi impressa'>";
			else                                             echo "<img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS' title='Imprimir OS'>";
			echo "</td>";

			##### VERIFICAÇÃO SE A OS FOI ENVIADA CARTA REGISTRADA #####
			if($login_fabrica == 1 and $consumidor_revenda == 'C' ){
				echo "<td width='30' align='center'>";
				if(strlen($fechamento) == 0){
					$sql_sedex = "SELECT SUM(current_date - data_abertura) AS final FROM tbl_os WHERE os=$os ;";
					$res_sedex = pg_exec($con,$sql_sedex);
					$sedex_dias = pg_result($res_sedex,0,'final');
					if($sedex_dias > 15){
						$sql_sedex = "SELECT sua_os_origem FROM tbl_os_sedex WHERE sua_os_origem = '$os' AND fabrica = $login_fabrica";
						$res_sedex = pg_exec($con,$sql_sedex);
						if(pg_numrows($res_sedex) == 0){
							echo "<a href='carta_registrada.php?os=$os'><img border='0' width='20' heigth='20' src='imagens/envelope.png' alt='Inserir informações da Carta Registrada' title='Inserir informações da Carta Registrada'></a>";
						}else{
							echo "<a href='carta_registrada.php?os=$os'><img border='0' width='20' heigth='20' src='imagens/img_ok.gif' alt='Visualizar as informações da Carta Registrada' title='Visualizar as informações da Carta Registrada'></a>";
						}
					}
					echo "&nbsp;";
				}else{
					echo "&nbsp;";
				}
				echo "</td>";
			}else{
				echo "<td width='30' align='center'>&nbsp;";
				echo "</td>";
			}
				echo "<td width='30' align='center'>";
				if ($qtde_item > 0) echo "<img border='0' src='imagens/img_ok.gif' alt='OS com item'>";
				else                echo "&nbsp;";
				echo "</td>";

			##### VERIFICAÇÃO SE TEM ITEM NA OS PARA A FÁBRICA 1 #####
			if ($login_fabrica == 1) {
				$status_troca = "";
				if($tipo_atendimento == 17 OR $tipo_atendimento == 18){
					$sql_troca = "SELECT tbl_os_troca.status_os, tbl_status_os.descricao
									FROM tbl_os_troca JOIN tbl_status_os USING(status_os)
									WHERE tbl_os_troca.os = $os ";
					$res_troca = pg_exec($con,$sql_troca);
					if(pg_numrows($res_troca) > 0){
						$status_troca = pg_result($res_troca,0,0);
					}
				}
				echo "<td width='30' align='center' nowrap>";
				if($os_cortesia=="t"){
					echo "Cortesia";
				}else{
					if($consumidor_revenda == "R"){
						if(strlen($tipo_atendimento)>0 AND ($tipo_atendimento == 17 or $tipo_atendimento == 18)){
							if($status_troca == '13'){
								echo "Troca<br>(</b>Recusada</b>)";
							}elseif($status_troca == '19'){
								echo "Troca<br>(aprovada)";
							}elseif($status_troca == '15'){
								echo "Troca<br>(Excluída)";
							}else{
								echo "Troca<br>(Em&nbsp;aprovação)";
							}
						}else{
							if($login_fabrica ==1 and ($os_metal =='t' OR $id_tipo_os ==13)){
								echo "Metais Sanitários";
							}else{
								echo "Revenda";
							}

						}
					}elseif($consumidor_revenda == "C"){
						if(strlen($tipo_atendimento)>0 AND ($tipo_atendimento == 17 or $tipo_atendimento == 18)){
							if($status_troca == '13'){
								echo "Troca<br>(</b>Recusada</b>)";
							}elseif($status_troca == '19'){
								echo "Troca<br>(aprovada)";
							}elseif($status_troca == '15'){
								echo "Troca<br>(Excluída)";
							}else{
								echo "Troca<br>(Em&nbsp;aprovação)";
							}
						}else{
							echo "Consumidor";
						}
					}
				}
				echo "</td>";
			}

			//Gustavo 28/3/2008 16334
			if($login_fabrica == 1){
				echo "<td>\n";
					if ($excluida == "f" || strlen($excluida) == 0 and strlen($fechamento) == 0) {
						if($status_troca == 13){
							echo "
							<a href=\"javascript: if (confirm('Deseja realmente voltar a OS $sua_os para aprovação. ?') == true) { window.location='$PHP_SELF?troca_aprovacao=$os'; }\">							
							<input type='button' value='Voltar OS Aprovação'>
							</a>";
						}else
							echo "&nbsp;";
					}else
						echo "&nbsp;";
				echo "</td>\n";
			}

			echo "<td width='60' align='center'>";
			if ($excluida == "f" || strlen($excluida) == 0){
				if($xrevenda_revenda <> 1){
					//echo "<a href='os_press.php?os=$os' target='_blank'><input type=\"button\" value=\"Consulta\"></a>";
					echo "<form action='os_press.php?os=$os' target='_blank' ><input type=\"submit\" value=\"Consulta\"></form>";
				}else{
					echo "&nbsp;";
				}
				if($xrevenda_revenda == 1 and strlen($explodida)==0){
					if($os_metal =='t'){
						echo "
						<input type='button' value='Explodir' onclick=\"javascript: if (document.frm_os.os_explodir_$i.value == '' ) { document.frm_os.os_explodir_$i.value='continuar' ; window.location='os_metal_finalizada.php?os_metal=$os&btn_acao=explodir' } else { alert ('Aguarde submissão') }\">";
						echo "<input type='hidden' name='os_explodir_$i' value=''>";
					}else{
						echo "
						<input type='button' value='Explodir' onclick=\"javascript: if (document.frm_os.os_explodir_$i.value == '' ) { document.frm_os.os_explodir_$i.value='continuar' ; window.location='os_revenda_finalizada.php?os_revenda=$os&btn_acao=explodir' } else { alert ('Aguarde submissão') }\">";
						echo "<input type='hidden' name='os_explodir_$i' value=''>";
					}
				}

			}

			echo "</a></td>\n";

			echo "<td width='60' align='center'>";

			if ($excluida == "f" || strlen($excluida) == 0) {
				if ($login_fabrica == 1 && $tipo_os_cortesia == "Compressor") {
					if($login_posto=="6359"){
						//echo "<a href='os_item.php?os=$os' target='_blank'>";
							echo "<a href='os_print.php?os=$os' target='_blank'>";
					}else{
						echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
					//takashi alterou 03/11
					}
				}else{
					if($xrevenda_revenda ==0){
						echo "<a href='os_print.php?os=$os' target='_blank'>";
					}else{
						if ($login_fabrica==7){
							echo "<a href='os_print_manutencao.php?os_manutencao=$os' target='_blank'>";
						}else{
							echo "<a href='os_revenda_print.php?os_revenda=$os' target='_blank'>";
						}
					}
				}
				echo "<input type='button' value='Imprimir'></a>";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (($excluida == "f" || strlen($excluida) == 0) && strlen($fechamento) == 0) {
					if(strlen($tipo_atendimento) == 0){
					 	if($os_cortesia=='f'){
								echo "<a href='os_cadastro.php?os=$os'><input type=\"button\" value=\"Alterar\"></a>";
						}else{
							echo "&nbsp;";
						}
						/*else{//hd 14659
							if($tipo_os_cortesia=="Promotor"){
							echo "11<a href='os_cortesia_cadastro.php?os=$os' ><img src='imagens/btn_lanca.gif'></a>";
							}
						}*/
					}else{
						if($xrevenda_revenda == 0){
							if($tipo_atendimento == 17 OR $tipo_atendimento == 18){
								if($status_troca <> 19 AND $status_troca <> 15){
									echo "<a href='os_cadastro_troca.php?os=$os'>
									<input type='button' value='Alterar'>
									</a>";
								}else{
									echo "&nbsp;";
								}
							}else{
								if(($os_metal =='t' or $id_tipo_os==13) and $os_cortesia =='f'){
									echo "<a href='os_item.php?os=$os'>
									<input type='button' value='Alterar'>
									</a>";
								}elseif($os_cortesia=='t'){
									echo "&nbsp;";
								}else{
									echo "<a href='os_cadastro_troca.php?os=$os'>
									<input type='button' value='Alterar'>
									</a>";
								}

							}
						}else{
							if(strlen($explodida)==0){
								if($os_metal =='t' or $id_tipo_os==13 ) {
									echo "<a href='os_cadastro_metais_sanitarios_new.php?os_metal=$os'><input type='button' value='Alterar'></a>";
								}else{
									if($login_fabrica==1){//HD 56662
										if(strlen($os)>0){
											$sqlTA = "SELECT tipo_atendimento from tbl_os_revenda where os_revenda = $os";
											$resTA = pg_exec($con, $sqlTA);
											if(pg_numrows($resTA)>0) $tipo_at_revenda = pg_result($resTA,0,tipo_atendimento);
											if($tipo_at_revenda==17){
												echo "<a href='os_revenda_troca.php?os_revenda=$os'><input type='button' value='Alterar'></a>";
											}else{
												echo "<a href='os_revenda.php?os_revenda=$os'><input type='button' value='Alterar'></a>";
											}
										}
									}else{
										echo "<a href='os_revenda.php?os_revenda=$os'><input type='button' value='Alterar'></a>";
									}
								}
							}else
								echo "&nbsp;";
						} 
					}
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}
			echo "<td width='60' align='center' nowrap>";
			if($xrevenda_revenda == 1 and $login_fabrica <> 7 and $login_fabrica<>15) {
				echo "<a href='os_revenda_blackedecker_total_print.php?os_revenda=$os' target='_target'><input type='button' value='Imprime'></a>\n";
			}
			if ($troca_garantia == "t"  OR  ($status_os=="62" || $status_os=="65" || $status_os=="72")) {
			}elseif (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($fechamento) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href='os_item.php?os=$os' target='_blank'>
					<input id='lancar_$i' type='button' value='Lançar Itens'></a>";
				}
			}elseif ($login_fabrica == 1 && strlen ($fechamento) == 0 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if($os_cortesia=='f'){
						if((strlen($tipo_atendimento)==0 AND ($tipo_atendimento <> 17 or $tipo_atendimento <> 18)) or $id_tipo_os == 13){
							if($xrevenda_revenda == 0){
								echo "<a href='os_item.php?os=$os' target='_blank'>";
								echo "
									<input id='lancar_$i' type='button' value='Lançar Itens'></a>";
							}else{
								echo "<a href='os_revenda_blackedecker_total_print.php?os_revenda=$os_revenda' target='_target'>
								<input id='lancar_$i' type='button' value='Imprimir'></a></td>\n";
							}
						}
					}else{//2 cortesia
						if($tipo_os_cortesia=="Promotor"){
								echo "<a href='os_cortesia_cadastro.php?os=$os' >
									<input id='lancar_$i' type='button' value='Lançar Itens'></a>";
						}
					}

				}
			}elseif ($login_fabrica == 7 && strlen ($fechamento) == 0 and $xrevenda_revenda == 0) {
				echo "<a href='os_filizola_valores.php?os=$os' target='_blank'>
					<input id='lancar_$i' type='button' value='Lançar Itens'></a>";
			}elseif (strlen($fechamento) == 0 ) {
				if ($excluida == "f" OR strlen($excluida) == 0) {
					if ($login_fabrica == 1) {
						if($os_cortesia=='f'){
							echo "<a href='os_item.php?os=$os' target='_blank'>";
						}else{
							if($tipo_os_cortesia=="Promotor"){
							echo "<a href='os_cortesia_cadastro.php?os=$os' >
								<input type='button' value='Lançar Itens'></a>";
							}
						}

						if(strlen($tipo_atendimento) == 0){
							echo "<a href='os_item.php?os=$os' target='_blank'>";
						}
					}else{
							echo "<a href='os_item.php?os=$os' target='_blank'>";
					}
					if(($login_fabrica <> 7 and $login_fabrica <> 15) OR $xrevenda_revenda == 0){
						echo "<input id='lancar_$i' type='button' value='Lançar Itens'></a>";
					}
				}
			}elseif (strlen($fechamento) > 0 && strlen($extrato) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if (strlen ($importacao_fabrica) == 0) {
						if($login_fabrica == 20){
							if($status_os<>'13')
								echo "<a href='os_cadastro.php?os=$os&reabrir=ok'>
								<input type='button' value='Reabrir OS'></a>";
						}

						else if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18))
							echo "&nbsp;";
						else 
							echo "<a href='os_item.php?os=$os&reabrir=ok'><input type='button' value='Reabrir OS'></a>";
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if ((strlen($admin) == 0 or ((strlen($tipo_os_cortesia)>0 or strlen($tipo_atendimento) > 0) and strlen($admin) > 0)) AND strlen ($fechamento) == 0 AND ($excluida == "f" OR strlen($excluida) == 0) AND $mostra_motivo == 1) {
					if($xrevenda_revenda == 0){
					echo "<a href='os_motivo_atraso.php?os=$os' target='_blank'><input type='button' value='Motivo'></a>";
					}
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0 && strlen($pedido) == 0 && $login_fabrica != 7  && $status_os!="62" && $status_os!="65" && $status_os!="72") {
				if($excluir_revenda=='t'){
					echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS de Revenda $sua_os ?') == true) { window.location='$PHP_SELF?excluir_revenda=$os'; }\"><input id='excluir_$i' type='button' value='Excluir'></a>";
				}elseif ($excluida == "f" || strlen($excluida) == 0) {
					if (strlen ($admin) == 0) {
						if($xrevenda_revenda == 0){
							if(($tipo_atendimento == 17 OR $tipo_atendimento == 18) AND ($status_troca == 19 OR $status_troca == 15) ){
								echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><input id='excluir_$i' type='button' value='Excluir'></a>";
							}else{
								echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><input id='excluir_$i' type='button' value='Excluir'></a>";
							}
						}
					}else{
						if(($tipo_atendimento == 17 OR $tipo_atendimento == 18) AND ($status_troca <> 19 AND $status_troca <> 15)) {
							echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><input id='excluir_$i' type='button' value='Excluir'></a>";
						}else{
							echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><input id='excluir_$i' type='button' value='Excluir'></a>";
						}
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0 AND $status_os!="62" && $status_os!="65" && $status_os!="72") {
				//hd 4699
				if ($login_fabrica <> 1) {
					if ($excluida == "f" || strlen($excluida) == 0) {

						//takashi 12-12
						$sql_data = "SELECT current_date as data";
						$res_data = pg_exec($con,$sql_data);
						$hoje     =  trim(pg_result($res_data,0,data));
						if(($login_fabrica==1) and 1==2 and ($hoje > "2006-12-17" and $hoje < "2006-12-24")){
								echo "<a href=\"javascript: alert('"._("Informamos que de 18 a 24 de dezembro será realizada uma manutenção no sistema operacional da B&D. Dessa forma, ficará suspenso o fechamento das ordens de serviço neste período.")."');\"><input id='sinal_$i' type='button' value='Fechar'></a>";

						}else{
							if($xrevenda_revenda ==0){
							echo "<a href=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor não seja HOJE, utilize a opção de Fechamento de OS para informar a data correta! Confirma o fechamento da OS $sua_os com a data de HOJE?') == true) { fechaOS ($os,'sinal_$i','excluir_$i','lancar_$i') ; }\"><input id='sinal_$i' type='button' value='Fechar'></a>";
							}else
								echo "&nbsp;";
						}
						//takashi 12-12
					}else
						echo "&nbsp;";
				}else
					echo "&nbsp;";
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			if ($login_fabrica == 7) {
				echo "<td width='60' align='center'>";
				echo "<a href='os_matricial.php?os=$os' target='_blank'>Matricial</a>";
				echo "</td>\n";
			}

			echo "</tr>";
		}
		echo "</table>";
	}

	echo "<br><h1>Resultado: $resultados registro(s).</h1>";
}
?>
</form>

<?
$sua_os             = trim (strtoupper ($_POST['sua_os']));
$serie              = trim (strtoupper ($_POST['serie']));
$nf_compra          = trim (strtoupper ($_POST['nf_compra']));
$consumidor_cpf     = trim (strtoupper ($_POST['consumidor_cpf']));
$produto_referencia = trim (strtoupper ($_POST['produto_referencia']));
$produto_descricao  = trim (strtoupper ($_POST['produto_descricao']));

$mes = trim (strtoupper ($_POST['mes']));
$ano = trim (strtoupper ($_POST['ano']));

$codigo_posto    = trim (strtoupper ($_POST['codigo_posto']));
$posto_nome      = trim (strtoupper ($_POST['posto_nome']));
$consumidor_nome = trim ($_POST['consumidor_nome']);
$os_situacao     = trim (strtoupper ($_POST['os_situacao']));
$revenda_nome    = trim (strtoupper ($_POST['revenda_nome']));

 ?>
<div><?php echo include "rodape.php";?></div>
