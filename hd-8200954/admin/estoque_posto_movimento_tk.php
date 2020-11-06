<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

$ajax_acerto = $_GET['ajax_acerto'];
if(strlen($ajax_acerto)==0){$ajax_acerto = $_POST['ajax_acerto'];}
if(strlen($ajax_acerto)>0){
	$peca  = $_GET['peca'];
	$posto = $_GET['posto'];
	$btn_acao = $_POST['btn_acao'];
	$hoje = date("d/m/Y");
	if(strlen($peca)>0 and strlen($posto)>0 and strlen($btn_acao)==0){
		$sql = "
			SELECT tbl_peca.referencia as peca_referencia,
				tbl_peca.descricao  as peca_descricao    ,
				tbl_posto.nome as nome_posto             ,
				tbl_posto_fabrica.codigo_posto           ,
				tbl_estoque_posto.qtde
			FROM tbl_estoque_posto
			JOIN tbl_posto on tbl_estoque_posto.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_peca on tbl_estoque_posto.peca = tbl_peca.peca
			WHERE tbl_estoque_posto.fabrica = $login_fabrica
			AND   tbl_estoque_posto.posto = $posto
			AND   tbl_estoque_posto.peca = $peca";
		$res = pg_exec($con,$sql);
		if(pg_num_rows($res)>0){
			$peca_referencia = pg_result($res,0,peca_referencia);
			$peca_descricao  = pg_result($res,0,peca_descricao);
			$nome_posto      = pg_result($res,0,nome_posto);
			$codigo_posto    = pg_result($res,0,codigo_posto);
			$qtde            = pg_result($res,0,qtde);
			if($qtde<0){
			$xqtde = $qtde * -1;
			}else{
			$xqtde = $qtde;
			}

		echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' align='center' style='font-family: verdana; font-size: 10px'>";
			echo "<tr>";
			echo "<td>Posto: <B>$codigo_posto - $nome_posto</B> </td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Peça: <B>$peca_referencia - $peca_descricao</B> </td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Qtde Estoque: <B>$qtde</b></td>";
			echo "</tr>";
		echo "</table>";
		echo "<form name='frm_acerto' method='post' action='$PHP_SELF'>";
		echo "<table border='1' cellpadding='4' cellspacing='1' width='90%' align='center' style='font-family: verdana; font-size: 10px'>";
			echo "<tr>";
			echo "<td colspan='2'>Para acertar o estoque do posto basta inserir uma nova movimentação com os valores abaixo:</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td><B>Peça: </B>$peca_referencia - $peca_descricao </td>";
			echo "<td><B>Data: </B>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='data_acerto' size='10' maxlength='10' value='$hoje' class='frm'></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td><B>Qtde Estoque: </B> <input type='text' name='qtde_acerto' size='4' maxlength='4' value='$xqtde' class='frm'></td>";
			echo "<td><B>Nota Fiscal: </B> <input type='text' name='nf_acerto' size='10' maxlength='20' value='$qtde_acerto' class='frm'></td>";
			echo "</tr>";
			
			echo "<tr>";
			echo "<td colspan='2'><B>Tipo: </B> <input type='radio' name='tipo' value='E'> Entrada <input type='radio' name='tipo' value='S'> Saida</td>";
			echo "</tr>";
			

			echo "<tr>";
			echo "<td colspan='2' align='center'><B>Observação: </B><BR><TEXTAREA NAME='obs_acerto' ROWS='5' COLS='50'  class='frm'></TEXTAREA>";
			echo "<input type='hidden' name='posto' value='$posto'>";
			echo "<input type='hidden' name='peca' value='$peca'>";
			echo "<input type='hidden' name='btn_acao' value=''>";
			echo "<input type='hidden' name='ajax_acerto' value='true'>";
			echo "<BR><img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_acerto.btn_acao.value == '' ) { document.frm_acerto.btn_acao.value='gravar' ; document.frm_acerto.submit() } else { alert ('Aguarde ') }\" ALT=\"Gravar itens da Ordem de Serviço\" border='0' style=\"cursor:pointer;\">";
			echo "</td>";
			echo "</tr>";

		echo "</table>";

	echo "</form>";

		}
	}
	if(strlen($btn_acao)>0){
		$data_acerto = $_POST['data_acerto'];
		$qtde_acerto = $_POST['qtde_acerto'];
		$nf_acerto   = $_POST['nf_acerto'];
		$obs_acerto  = $_POST['obs_acerto'];
		$peca        = $_POST['peca'];
		$posto       = $_POST['posto'];
		$tipo        = $_POST['tipo'];
		
		if(strlen($tipo)==0){
			$tipo = "qtde_entrada";
		}else{
			if($tipo == "E"){$tipo = "qtde_entrada"; $operador = " + ";}
			if($tipo == "S"){$tipo = "qtde_saida"; $operador = " - ";}
		}
		

		$data_acerto = fnc_formata_data_pg($data_acerto);
		if(strlen($obs_acerto)==0){
			$obs_acerto = "null";
		}else{
			$obs_acerto = "'". $obs_acerto . "'";
		}
		
		if(strlen($nf_acerto)==0){
			$nf_acerto = "null";
		}else{
			$nf_acerto = "'". $nf_acerto . "'";
		}

		if(strlen($qtde_acerto)==0) $msg_erro = "Favor informar quantidade";

		if(strlen($msg_erro)==0){
			$sql = "INSERT INTO tbl_estoque_posto_movimento(
								fabrica      , 
								posto        , 
								peca         , 
								$tipo        , 
								data         , 
								obs          ,
								nf           , 
								admin
								)values(
								$login_fabrica,
								$posto        ,
								$peca         ,
								$qtde_acerto  ,
								$data_acerto  ,
								$obs_acerto   ,
								$nf_acerto    ,
								$login_admin
						)";
//				echo $sql;
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if(strlen($msg_erro)==0){
				$sql = "SELECT peca 
						FROM tbl_estoque_posto 
						WHERE peca = $peca 
						AND posto = $posto 
						AND fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					$sql = "UPDATE tbl_estoque_posto set 
							qtde = qtde $operador $qtde_acerto
							WHERE peca  = $peca
							AND posto   = $posto
							AND fabrica = $login_fabrica;";
					$res = pg_exec($con,$sql);
//					echo $sql;
					$msg_erro .= pg_errormessage($con);
				}else{
					$sql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde)
							values($login_fabrica,$posto,$peca,$qtde_acerto)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}

	echo "<center>Atualizado com sucesso!</center>";
	}
exit;
}

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){

	$peca = $_GET['peca'];
	$posto = $_GET['posto'];
	$data_inicial = $_GET['data_inicial'];
	$data_final = $_GET['data_final'];

/*	$data_inicial = date("Y-m-d", mktime(0, 0, 0, date("n"), 1,  date("Y")));
	$data_final   = date("Y-m-t", mktime(0, 0, 0, date("n"), 1,  date("Y")));
	*/
	if(strlen($peca)>0){
		$sql = "SELECT 	tbl_estoque_posto_movimento.peca                              , 
						tbl_peca.referencia                                           ,
						tbl_peca.descricao as peca_descricao                          ,
						tbl_os.sua_os                                                 ,
						tbl_estoque_posto_movimento.os                                , 
						to_char(tbl_estoque_posto_movimento.data,'DD/MM/YYYY') as data,
						tbl_estoque_posto_movimento.qtde_entrada                      , 
						tbl_estoque_posto_movimento.qtde_saida                        , 
						tbl_estoque_posto_movimento.admin                             ,
						tbl_estoque_posto_movimento.pedido                            , 
						tbl_estoque_posto_movimento.obs
				FROM  tbl_estoque_posto_movimento 
				JOIN  tbl_peca on tbl_peca.peca =  tbl_estoque_posto_movimento.peca
				AND   tbl_peca.fabrica = $login_fabrica
				LEFT  JOIN tbl_os on tbl_estoque_posto_movimento.os = tbl_os.os 
				AND   tbl_os.fabrica = $login_fabrica
				WHERE tbl_estoque_posto_movimento.posto   = $posto 
				AND   tbl_estoque_posto_movimento.peca    = $peca
				AND   tbl_estoque_posto_movimento.fabrica = $login_fabrica 
				ORDER BY tbl_peca.descricao,
				tbl_estoque_posto_movimento.data,
				tbl_estoque_posto_movimento.qtde_saida,
				tbl_estoque_posto_movimento.os";
		$res = pg_exec($con,$sql);
		//	AND   tbl_estoque_posto_movimento.data between '$data_inicial' and '$data_final' 
		if(pg_numrows($res)>0){
		echo "<table border='0' width='100%' cellpadding='4' cellspacing='1' align='rigth' style='font-family: verdana; font-size: 9px'><tr><td width='95%' align='center'><font size='1' face='verdana'>". pg_result ($res,0,referencia) . " - " . pg_result ($res,0,peca_descricao) . "</font></td><td align='right' bgcolor='#FFFFFF'><a href='javascript:fechar(". pg_result ($res,0,peca) .");'><B>Fechar</b></a></td></tr></table>";
		echo "<center><center>";
			echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 9px'>";
			echo "<tr>";
			echo "<td><font color='#FFFFFF'><B>Movimentação</B></FONT></td>";
			echo "<td><font color='#FFFFFF'><B>Data</B></FONT></td>";
			echo "<td><font color='#FFFFFF'><B>Entrada</B></FONT></td>";
			echo "<td><font color='#FFFFFF'><B>Saida</B></FONT></td>";
			echo "<td><font color='#FFFFFF'><B>Pedido</B></FONT></td>";
			echo "<td><font color='#FFFFFF'><B>OS</B></FONT></td>";
			echo "<td><font color='#FFFFFF'><B>Observação</B></FONT></td>";
			echo "</tr>";
			
			for($i=0; pg_numrows($res)>$i;$i++){
			
				$os             = pg_result ($res,$i,os);
				$sua_os         = pg_result ($res,$i,sua_os);
				$referencia     = pg_result ($res,$i,referencia);
				$peca_descricao = pg_result ($res,$i,peca_descricao);
				$data           = pg_result ($res,$i,data);
				$qtde_entrada   = pg_result ($res,$i,qtde_entrada);
				$qtde_saida     = pg_result ($res,$i,qtde_saida);
				$admin          = pg_result ($res,$i,admin);
				$obs            = pg_result ($res,$i,obs);
				$pedido         = pg_result ($res,$i,pedido);
				
				$saida_total  = $saida_total + $qtde_saida;
				$entrada_total = $entrada_total + $qtde_entrada;
				
			

				if($qtde_entrada>0){
					$movimentacao = "<font color='#35532f'>Entrada</font>";
				}else{
					$movimentacao = "<font color='#f31f1f'>Saida</font>";
				}
				
				$cor = "#efeeea"; 
				if ($i % 2 == 0) $cor = '#d2d7e1';

				echo "<tr bgcolor='$cor'>";
				echo "<td align='center'>$movimentacao</td>";
				echo "<td align='center'>$data</td>";
				echo "<td align='center'>$qtde_entrada</td>";
				echo "<td align='center'>$qtde_saida</td>";
				echo "<td align='center'><a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>$pedido</a></td>";
				echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
				echo "<td align='left'>$obs</td>";
				echo "</td>";
				echo "</tr>";
				
			}
		$total = $entrada_total - $saida_total;
			echo "<tr bgcolor='#FFFFFF'>";
			echo "<td colspan='2' align='center'><font color='#2f67cd'><B>SALDO</B></FONT></td>";
			echo "<td colspan='2' align='center'><font color='#2f67cd'><B>"; echo $total; echo "</B></FONT></td>";
			echo "<td  colspan='3' >&nbsp;</td>";

			echo "</tr>";
			echo "</table><BR>";
		
		}else{
			echo "<BR><center>Nenhum resultado encontrado</center><BR>";
		}	
	}

exit;

}



$layout_menu = "gerencia";
$titulo = "Movimentação de peças do posto";
$title = "Movimentação de peças do posto";

include 'cabecalho.php';
include "javascript_pesquisas.php"; 
?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">
function fechar(peca){
	if (document.getElementById('dados_'+ peca)){
		var style2 = document.getElementById('dados_'+ peca); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}
function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
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
	
function mostraMovimentacao(peca,posto,data_inicial,data_final){
	if (document.getElementById('dados_' + peca)){
		var style2 = document.getElementById('dados_' + peca); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
			retornaMovimentacao(peca,posto,data_inicial,data_final);
		}
	}
}
var http3 = new Array();
function retornaMovimentacao(peca,posto,data_inicial,data_final){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "<?echo $PHP_SELF;?>?ajax=true&peca="+ peca +"&posto=" + posto + "&data_inicial=" + data_inicial + "&data_final="+ data_final;
	http3[curDateTime].open('get',url);
	
	var campo = document.getElementById('dados_'+peca);

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);

}

function acertaEstoque(peca,posto){
	var div = document.getElementById('div_acertaEstoque');
	div.style.display = (div.style.display=="") ? "none" : "";
	acertaEstoque_pop(peca,posto);
}
var http4 = new Array();
function acertaEstoque_pop(peca,posto){

	var curDateTime = new Date();
	http4[curDateTime] = createRequestObject();

	url = "estoque_posto_movimento.php?ajax_acerto=true";
	http4[curDateTime].open('get',url);
	var campo = document.getElementById('div_acertaEstoque');
	Page.getPageCenterX();
	campo.style.top = (Page.top + Page.height/2)-160;
	campo.style.left = Page.width/2-220;

	http4[curDateTime].onreadystatechange = function(){
		if(http4[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http4[curDateTime].readyState == 4){
			if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){

				var results = http4[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http4[curDateTime].send(null);

}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('div_acertaEstoque').innerHTML ='';	
}
Page.getPageCenterX = function (){
	var fWidth;
	var fHeight;		
	//For old IE browsers 
	if(document.all) { 
		fWidth = document.body.clientWidth; 
		fHeight = document.body.clientHeight; 
	} 
	//For DOM1 browsers 
	else if(document.getElementById &&!document.all){ 
			fWidth = innerWidth; 
			fHeight = innerHeight; 
		} 
		else if(document.getElementById) { 
				fWidth = innerWidth; 
				fHeight = innerHeight; 		
			} 
			//For Opera 
			else if (is.op) { 
					fWidth = innerWidth; 
					fHeight = innerHeight; 		
				} 
				//For old Netscape 
				else if (document.layers) { 
						fWidth = window.innerWidth; 
						fHeight = window.innerHeight; 		
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}



</script>
<style type="text/css">
.menu_top {
	
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>
<?

	echo "<div id='div_acertaEstoque' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:450px;'></div>";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

echo "<form name='frm_consulta' method='post' action='$PHP_SELF'>";
echo "<BR><BR><BR><table border='0' cellspacing='0' cellpadding='8' align='center' bgcolor='#596D9B' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td colspan='2'><font color='#FFFFFF'><B>Movimentação do estoque do posto autorizado</B></FONT>";
echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td>";
echo "Código Posto: <input type='text' name='codigo_posto' size='8' value='$codigo_posto' class='frm'>";
?>
<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
<?
echo "</td>";
echo "<td>";
echo "Nome Posto: <input type='text' name='posto_nome' size='20' value='$posto_nome' class='frm'>";
?>
<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
<?
echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td>Código Peça: <input class='frm' type='text' name='referencia' value='$referencia' size='8' maxlength='20'><a href=\"javascript: fnc_pesquisa_peca (document.frm_consulta.referencia,document.frm_consulta.descricao,'referencia')\"><IMG SRC='imagens_admin/btn_buscar5.gif' ></a></td>";

echo "<td>&nbsp;Descrição: &nbsp;&nbsp;<input class='frm' type='text' name='descricao' value='$descricao' size='20' maxlength='50'>
<a href=\"javascript: fnc_pesquisa_peca(document.frm_consulta.referencia,document.frm_consulta.descricao,'descricao')\">
<IMG SRC='imagens_admin/btn_buscar5.gif'></a></td>";
echo "</tr>";

/*
echo "<tr bgcolor='#D9E2EF'>";
echo "<td align='right'>Mês: ";
echo "<select name='mes' size='1' class='frm'>";

        for ($i = 1 ; $i <= count($meses) ; $i++) {
        echo "<option value='$i'";
        if ($mes == $i) echo " selected";
        echo ">" . $meses[$i] . "</option>";
        }
echo "</select>&nbsp;&nbsp;";
echo "</td>";
echo "<td align='left'>&nbsp;Ano:&nbsp;&nbsp; ";
echo "<select name='ano' size='1' class='frm'>";

for ($i = 2006 ; $i <= date("Y") ; $i++) {
echo "<option value='$i'"; if ($ano == $i) echo " selected"; echo ">$i</option>";
}
echo "</select>";

echo "</td>";
echo "</tr>";
*/

echo "<tr bgcolor='#D9E2EF'>";
echo "<td colspan='2'><input type='submit' name='btn_acao' value='Exibir'>";
echo "</td>";
echo "</tr>";
echo "</table>";


$btn_acao= $_POST['btn_acao'];
if (strlen($btn_acao)>0){
	/*
	$mes= $_POST['mes'];
	$ano= $_POST['ano'];
	
	if (strlen($mes)==0){	$msg_erro = "Escolha o mês"; }
	if (strlen($ano)==0){	$msg_erro = "Escolha o ano"; }
	//tratamento de datas
	$data_inicial = date("Y-m-d", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
	*/
	
	$codigo_posto = $_POST['codigo_posto'];
	$posto_nome   = $_POST['posto_nome'];
	
	if (strlen($codigo_posto)==0 or strlen($posto_nome)==0){	
		$msg_erro = "Escolha o posto"; 
	}else{
		$sql = "SELECT posto 
				FROM tbl_posto_fabrica
				WHERE codigo_posto = '$codigo_posto'
				AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
		}else{
			$msg_erro = "Posto não encontrado";
		}
	
	}
	
	$referencia  = $_POST['referencia'];
	$descricao   = $_POST['descricao'];

	if (strlen($referencia)>0 and strlen($msg_erro)==0){	
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE tbl_peca.fabrica= $login_fabrica
				and tbl_peca.referencia='$referencia'
				AND tbl_peca.ativo = 't'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$peca = pg_result($res,0,peca);
		}else{
			$msg_erro = "Peça não encontrada";
		}

	}

	if (strlen($msg_erro)==0){	
		$sql = "SELECT 	DISTINCT 
					tbl_peca.referencia,tbl_peca.peca                   ,
					tbl_peca.descricao                                  ,
					tbl_estoque_posto.qtde                              
				FROM tbl_estoque_posto
				JOIN tbl_peca on tbl_estoque_posto.peca = tbl_peca.peca 
				WHERE  tbl_estoque_posto.posto = $posto  ";
if(strlen($peca)>0){$sql .= " AND tbl_estoque_posto.peca = $peca ";}
				$sql .= " 
				AND tbl_estoque_posto.fabrica = $login_fabrica
				ORDER BY tbl_peca.descricao";
				//tbl_estoque_posto.qtde > 0
/*
,
					( SELECT tbl_faturamento.emissao - current_date
					  FROM tbl_faturamento 
					  JOIN tbl_estoque_posto_movimento 
					  on tbl_estoque_posto_movimento.faturamento = tbl_faturamento.faturamento
					  WHERE tbl_estoque_posto.peca = tbl_estoque_posto_movimento.peca
					  AND tbl_estoque_posto_movimento.faturamento notnull
					  ORDER BY emissao DESC LIMIT 1
					) as emissao_dias                                    ,
					(SELECT to_char(tbl_faturamento.emissao,'DD/MM/YYYY')
					  FROM tbl_faturamento 
					  JOIN tbl_estoque_posto_movimento on tbl_estoque_posto_movimento.faturamento = tbl_faturamento.faturamento
					  WHERE tbl_estoque_posto.peca = tbl_estoque_posto_movimento.peca
					  AND tbl_estoque_posto_movimento.faturamento notnull
					  ORDER BY emissao DESC LIMIT 1
					 ) as emissao

*/

		$res = pg_exec ($con,$sql);
	//	echo $sql;
if(pg_numrows($res)>0){
?><BR><BR>
<table width="600" border="0" cellspacing="0" cellpadding="8" align='center' bgcolor='#596D9B'>
<tr>
<td class='menu_top'>Peca</td>
<td class='menu_top'>Saldo</td>
<td class='menu_top'>Opção</td>
</tr>
<?
for($x=0;pg_numrows($res)>$x;$x++){
	$peca            = pg_result($res,$x,peca);
	$peca_referencia = pg_result($res,$x,referencia);
	$peca_descricao  = pg_result($res,$x,descricao);
	$qtde            = pg_result($res,$x,qtde);
//	$emissao         = pg_result($res,$x,emissao);
//	$emissao_dias    = pg_result($res,$x,emissao_dias);
	
	if($cor == "#efeeea")$cor = "#d2d7e1";
	else $cor = "#efeeea";
	
	$devolve = false;
	
	//if(strlen($emissao_dias) > 0 AND $emissao_dias < -59) {$cor="#efc7ac"; $devolve = true;}
?>
	<tr>
		<td align='left' class='table_line1' bgcolor='<? echo $cor;?>'>
		
		<?echo "<a href=\"javascript:mostraMovimentacao($peca,$posto,'$data_inicial','$data_final');\">$peca_referencia - $peca_descricao</a>";?><BR>
		<div id='dados_<? echo $peca; ?>' style='position:absolute; display:none; border: 1px solid #949494;background-color: #b8b7af;width:593px;'></div>
		
		<input type='hidden' id='peca_<? echo $x; ?>' name='peca_<? echo $x; ?>' value='<? echo $peca; ?>'>
		</td>
		<td align='center' class='table_line1' bgcolor='<? echo $cor;?>'>
			<?echo $qtde;?>
			<input type='hidden' id='qtde_pendente_<? echo $x; ?>' name='qtde_pendente_<? echo $x; ?>' value='<? echo $qtde; ?>'>
		</td>
		<td align='center' class='table_line1' bgcolor='<? echo $cor;?>'>
	<a href="<? echo "$PHP_SELF?ajax_acerto=true&peca=$peca&posto=$posto"; ?>&keepThis=trueTB_iframe=true&height=400&width=500" title="Acerto de estoque do posto autorizado" class="thickbox">Acertar Estoque</a>	
		</td>
	</tr>
<? 
	}
	} 
}

}

include "rodape.php";


?>

