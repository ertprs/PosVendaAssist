<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'ajax_cabecalho.php';

$title = "CRM - ";

if($tipo_orcamento == "venda"){
	$cond1   = " JOIN tbl_orcamento_venda USING(orcamento)";
	$title  .= "Orçamento de Venda "; 
}
if($tipo_orcamento == "fora_garantia"){
	$cond1  = " JOIN tbl_orcamento_os    USING(orcamento)";
	$title .= "Orçamento de Serviço "; 
}

include "menu.php";
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'CRM') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>
<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs( {fxAutoHeight: true} );
	});
</script>

<script language='javascript' src='../ajax.js'></script>
<script>
function retornaCrm (http , componente ) {
	com = document.getElementById(componente);

	com.innerHTML   ="Carregando<br><img src='../imagens/carregar2.gif'>";
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = results[2];

					mostrar_interacao(results[1],'interacao_'+results[1]);
				}else{
					alert ('Erro ao abrir CRM' );
					alert(results[0]);
				}
			}
		}
	}
}

function pegaCrm (hd_chamado,dados,cor) {
	url = "ajax_crm.php?ajax=sim&acao=detalhes&hd_chamado=" + escape(hd_chamado)+"&cor="+escape(cor) ;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaCrm (http , dados) ; } ;
	http.send(null);
}

function MostraEsconde(dados,hd_chamado,imagem,cor)
{
	if (document.getElementById)
	{
		// this is the way the standards work
		var style2 = document.getElementById(dados);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			style2.innerHTML   ="";
			img.src='../imagens/mais.gif';

			}
		else{
			style2.style.display = "block";
			img.src='../imagens/menos.gif';
			pegaCrm(hd_chamado,dados,cor);
		}

	}
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
			
var http_forn = new Array();

function gravar_comentario(formulatio) {
//	ref = trim(ref);
	var acao='cadastrar';

	url = "ajax_crm.php?ajax=sim&acao="+acao;
	for( var i = 0 ; i < formulatio.length; i++ ){
			//alert(formulatio.elements[i].name+' = '+formulatio.elements[i].value);
			if(formulatio.elements[i].type=='radio' || formulatio.elements[i].type=='checkbox'){
				
				if(formulatio.elements[i].checked == true){
					//alert(formulatio.elements[i].value);
					url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
				}
			}else{
				url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
			}
	}

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					alert(response[2]);
					formulatio.btn_acao.value='GRAVAR';
					formulatio.comentario.value='';
					mostrar_interacao(response[1],'interacao_'+response[1]);
				}
				if (response[0]=="0"){
					// posto ja cadastrado
					alert(response[1]);
					formulatio.btn_acao.value='GRAVAR';
				}

			}
		}
	}
	http_forn[curDateTime].send(null);
}
function mostrar_interacao(hd_chamado,interacao) {

	var acao='interacao';

	url = "ajax_crm.php?ajax=sim&acao="+acao+"&hd_chamado="+hd_chamado;

	var com = document.getElementById(interacao);
	com.innerHTML   ="Aguarde...";

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com.innerHTML   = response[1];
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
</script>

<style>

.Label{
	font-family: Verdana;
	font-size: 10px;
}
.tabela{
	font-family: Verdana;
	font-size: 12px;
	
}

</style>


<?

$busca = trim($_POST["busca"]);
$tipo  = trim($_POST["tipo"]);

?>
<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0' class='tabela'>
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' align='center' colspan='6'><? echo $title; ?></td>
		</tr>
		<tr height='10'>
			<td  align='center' colspan='6'></td>
		</tr>
		<tr>
			<td class='Label'>
				<div id="container-Principal">
					<ul>
						<li><a href="#tab1Procurar"><span><img src='imagens/lupa.png' align=absmiddle> Busca</span></a></li>
					</ul>
					<div id="tab1Procurar">
					<form method='POST'>
					<table align='center'>
					<tr>
						<td><input name="busca" size="41" maxlength="2048" value="<?=$busca?>" title="Pesquisar" type="text" ><font size="-1"> <input name="btnG" value="Pesquisar" type="submit"></td>
					</tr>
					<tr>
						<td class='Conteudo'>Pesquisar por: <input type='radio' name='tipo' value='d' checked> Data Previsão <input type='radio' name='tipo' value='c' <?if($tipo=='c') echo "CHECKED";?>> Cliente <input type='radio' name='tipo' value='v' <?if($tipo=='v') echo "CHECKED";?>> Vendedor</td>
					</tr>
					</table>
					</form>
					<?

					$btnG=$_POST['btnG'];

					if(strlen($btnG) > 0) {
							//--=== Busca por Data de Previsão ==============================================================
						if($tipo == 'd'){
							if (strlen($busca) == 0) $msg_erro .= "Favor informar a data para pesquisa<br>";  
					
							if (strlen($busca) > 10) $msg_erro .= "Tamanho incorreto para data<br>";

							$fnc = @pg_exec($con,"SELECT fnc_formata_data('$busca')");
							if (strlen ( pg_errormessage ($con) ) > 0) $msg_erro = pg_errormessage ($con) ;	
							
							if(strpos($msg_erro,"invalid input syntax for integer")){
								$msg_erro = "Este não é um formato válido para data"; 
							}
							if (strlen($msg_erro) == 0){
								$aux_busca = @pg_result ($fnc,0,0);
								$cond2 = " AND tbl_orcamento.data_previsao = '$aux_busca' ";
							}
						}
						
						if (strlen($msg_erro) > 0 ) {
							echo "<font color='#FF0000'><center>$msg_erro</center></font><br>";
							exit;
						}
						if (strlen($msg_erro) == 0 ) {

							//--=== Busca por Vendedor ======================================================================
							if($tipo == 'v'){
								$aux_busca = strtoupper($busca);
								$cond2 = "AND tbl_hd_chamado.empregado IN (
										SELECT empregado
										FROM  tbl_empregado
										JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_empregado.pessoa
										WHERE tbl_empregado.empresa = $login_empresa
										AND   upper(nome)  LIKE '%$aux_busca%' 
									)";
							}
							//--=== Busca por Vendedor ======================================================================
							if($tipo == 'c'){
								$aux_busca = strtoupper($busca);
								$cond2 = "AND tbl_hd_chamado.orcamento IN (
										SELECT orcamento 
										FROM  tbl_orcamento
										WHERE tbl_orcamento.empresa = $login_empresa
										AND   upper(consumidor_nome)  LIKE '%$aux_busca%'
									)";
							}

						


						$sql = "SELECT  tbl_hd_chamado.orcamento                                  ,
								tbl_hd_chamado.hd_chamado                                         ,
								tbl_hd_chamado.titulo                                             ,
								tbl_status.descricao as status                                    ,
								tbl_hd_chamado.empregado                                          ,
								tbl_orcamento.aprovado                                            ,
								tbl_orcamento.consumidor_nome                                     ,
								TO_CHAR(tbl_hd_chamado.data        ,'DD/MM/YYYY') AS data         ,
								TO_CHAR(tbl_orcamento.data_previsao,'DD/MM/YYYY') AS data_previsao
							FROM tbl_hd_chamado
							JOIN tbl_orcamento USING (orcamento)
							JOIN tbl_empregado USING (empregado)
							LEFT JOIN tbl_status ON tbl_status.status = tbl_orcamento.status
							$cond1
							WHERE orcamento IS NOT NULL
							AND   fabrica_responsavel = $login_empresa
							$cond2";

							$res = pg_exec ($con,$sql);

							if (@pg_numrows($res) > 0) {
								if (strlen($busca)    > 0 ) echo "<center>Você está buscando por: $busca</center><br>";
								echo  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='750' >";
								echo  "<TR class='Titulo'  height='25' align='center'>";
								echo  "<TD background='../admin/imagens_admin/azul.gif' colspan='2' width='50'><b>Orcamento</b></TD>";
								echo  "<TD background='../admin/imagens_admin/azul.gif'><b>Cliente</b></TD>";
								echo  "<TD background='../admin/imagens_admin/azul.gif' width='70'<b>Data</b></TD>";
								echo  "<TD background='../admin/imagens_admin/azul.gif' width='70'><b>Data Previsão</b></TD>";
								echo  "<TD background='../admin/imagens_admin/azul.gif' width='100' align='center'><b>Status</b></TD>";
								echo  "<TD background='../admin/imagens_admin/azul.gif' width='50'><b>Aprovado</b></TD>";
								echo  "<TD background='../admin/imagens_admin/azul.gif' colspan='2'><b>Ações</b></TD>";
								echo  "</TR>";
							
								for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

									$orcamento            = pg_result($res,$i,orcamento);
									$hd_chamado           = pg_result($res,$i,hd_chamado);
									$empregado            = pg_result($res,$i,empregado);
									$data                 = pg_result($res,$i,data);
									$data_previsao        = pg_result($res,$i,data_previsao);
									$titulo               = pg_result($res,$i,titulo);
									$status               = pg_result($res,$i,status);
									$aprovado             = pg_result($res,$i,aprovado);
									$consumidor_nome      = pg_result($res,$i,consumidor_nome);
							
									if($cor=="#F1F4FA")$cor = '#F7F5F0';
									else               $cor = '#F1EEFA';

									if($aprovado == "t") $aprovado = "<font color='#009900'>Sim";
									else                 $aprovado = "<font color='#990000'>Não";

									echo "<tr bgcolor='$cor' class='Conteudo'>";
									echo "<td align='center' width='20'>";
									echo  "<img src='../imagens/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','$hd_chamado','visualizar_$i','$cor');\" align='absmiddle'>";
									echo "</td>";
									echo "<td  align='center'><a href=\"javascript:MostraEsconde('dados_$i','$hd_chamado','visualizar_$i','$cor');\"><b>$orcamento</b></a></td>";
									echo "<td align='left' >$consumidor_nome</td>";
									echo "<td align='center' >$data</td>";
									echo "<td align='center' >$data_previsao</td>";	
									echo "<td align='center' >$status</td>";
									echo "<td align='center' >$aprovado</td>";
									echo "<td align='center' align='center'><a href='orcamento_cadastro.php?orcamento=$orcamento'>Ver</a></td>";
									echo "<td align='center' align='center'><a href='orcamento_print.php?orcamento=$orcamento' target='_blank'>Imprimir</a></td>";
									echo "</tr>";
							
									echo "<tr heigth='1' class='Conteudo' bgcolor='$cor'><td colspan='9'>";
									echo "<DIV class='exibe' id='dados_$i' value='1' align='center'>";
									echo "</DIV>";
									echo "</td></tr>";
								}
								echo "</table>";
							}
						}
					}
					echo "</div>";
					echo "</td>";
					echo "</tr>";
					echo "</table>";
include "rodape.php";
?>
