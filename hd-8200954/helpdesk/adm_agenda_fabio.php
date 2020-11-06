<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica <> 10) header ("Location: index.php");

if($_POST['btn_acao']) $btn_acao = trim ($_POST['btn_acao']);




//--=== LOGAR COMO ADMIN =====================================================--\\
if (substr ($btn_acao,0,5) == "Logar" ) {

	$admin = $_POST['admin'];

	$sql = "SELECT login, senha FROM tbl_admin WHERE admin = '$admin'";

	$res = pg_exec ($con,$sql);

	$senha = pg_result ($res,0,senha);
	$login = pg_result ($res,0,login);

	echo "<form name='frm_login' method='post' target='_blank' action='../index.php'>";
	echo "<input type='hidden' name='login'>";
	echo "<input type='hidden' name='senha'>";
	echo "<input type='hidden' name='btnAcao' value='Enviar'>";
	echo "</form>";

	echo "\n";
	echo "<script language='javascript'>\n";
	echo "document.write ('redirecionando') ; \n";
	echo "document.frm_login.login.value = '$login' ; \n";
	echo "document.frm_login.senha.value = '$senha' ; \n";
	echo "document.frm_login.submit() ; \n";
	echo "document.location = '$PHP_SELF' ; \n";
	echo "</script>";
	echo "\n";

	exit ;

}

if($_GET['ajax']=='sim') {
	if($_GET['fabrica']) {
		$dados_fabrica = explode("|",$_GET['fabrica']);
		$aux_fabrica      = $dados_fabrica[0];
		$aux_nome_fabrica = $dados_fabrica[1];
	}
	
	if (strlen ($aux_fabrica) > 0) {
		$sql = "SELECT admin, login, nome_completo, email, fone, senha 
				FROM   tbl_admin 
				WHERE  fabrica = $aux_fabrica
				ORDER BY nome_completo";
		$res = pg_exec ($con,$sql);
		$n_resu = pg_numrows($res);
				
		if ($n_resu > 0) {
			$resposta .= "<BR>";
			$resposta .= "<table width = '755' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
			$resposta .= "<tr>";
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2' ><img src='/assist/imagens/pixel.gif' width='9' ></td>"; //linha esquerda - 2 linhas
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666' colspan='7'><b>Resultado da busca por:</b></td>";//centro
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";//linha direita - 2 linhas
			$resposta .= "</tr>";
			$resposta .= "<tr>";
			$resposta .= "<td bgcolor='#D9E8FF' align = 'center' width='100%' style='font-family: arial ; color:#666666' colspan='7'>$aux_fabrica - <B>$aux_nome_fabrica</B></td>";
			$resposta .= "</tr>";
			$resposta .= "<tr bgcolor='#DDDDDD' style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25'>";
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
			$resposta .= "<td rowspan='2' width='40'><B>Adm:</B></td>";
			$resposta .= "<td rowspan='2'><B>Nome completo:</B></td>";
			$resposta .= "<td rowspan='2'><B>Login/Senha:</B></td>";
	
			$resposta .= "<td rowspan='2'><B>Contato:</B></td>";
			$resposta .= "<td colspan='2' align='center'><B>Chamados</B></td>";
			$resposta .= "<td rowspan='2' align='center'><B>Logar como Admin</B></td>";
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
			$resposta .= "</tr>";
			$resposta .= "<tr bgcolor='#D9D9D9' style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25'>";
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
			$resposta .= "<td align='center'><B>Pendente</B></td>";
			$resposta .= "<td align='center'><B>Resolvido</B></td>";
			
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
			$resposta .= "</tr>";
	
			//-----------CABECA RESULTADO BUSCA-----------//
	
			for ($i=0; $i<$n_resu; $i++){
				$resu_admin           = pg_result($res,$i, admin);
				$resu_login           = pg_result($res,$i, login);
				$resu_nome_completo   = pg_result($res,$i, nome_completo);
				$resu_email           = pg_result($res,$i, email);
				$resu_fone            = pg_result($res,$i, fone);
				$resu_senha           = pg_result($res,$i, senha);
	
				//-----------FIM CABECA RESULTADO BUSCA-----------//
				//----------------RESULTADO BUSCA IMPRIMI----------------// 
				$cor = ($i % 2 == 0) ? '#FFFFFF' : '#F2F7FF';
	
				$resposta .= "<tr style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";
				$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
				//----------------RESULTADO BUSCA----------------// 
	
				$resposta .= "<td>$resu_admin&nbsp;</td>";
				$resposta .= "<td>$resu_nome_completo&nbsp;</td>";
				
				$resposta .= "<td><br>";
				$resposta .= "<div id='senha1'>";
				$resposta .= "<b>Login:</b>$resu_login<br>";
				$resposta .= "<b>Senha:</b>$resu_senha<br>&nbsp;";
				$resposta .= "</div>";
				$resposta .= "</td>";
	
	
				$resposta .= "<td><br>";
				$resposta .= "<div id='senha1'>";
				$resposta .= "<b>Telefone:</b>$resu_fone<br>";
				$resposta .= "<b>Email:</b>$resu_email<br>&nbsp;";
				$resposta .= "</div>";
				$resposta .= "</td>";
	
				//--------CHAMADOS--------//
				$sql1 = "SELECT count (*) AS total_novo
						FROM       tbl_hd_chamado
						WHERE      admin=$resu_admin
						AND       (tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido' AND tbl_hd_chamado.status <> 'Aprovação')";
				$res1 = @pg_exec ($con,$sql1);
				$total_pendente= pg_result($res1,0,total_novo);
					
					
				$sql2 = "SELECT	 COUNT (*) AS total_resolvido
						FROM       tbl_hd_chamado
						WHERE      admin=$resu_admin
						AND       status = 'Resolvido'";
				$res2 = @pg_exec ($con,$sql2);
				$total_resolvido           = pg_result($res2,0,total_resolvido);
				
	
				$resposta .= "<td aling='center' title='Chamados Pendentes'><center>$total_pendente</center></td>";
				$resposta .= "<td aling='center' title='Chamados Resolvidos'><center>$total_resolvido</center></td>";
				
				$resposta .= "<td aling='center'>";
				$resposta .= "<form name='frm_admin' method='post' action='$PHP_SELF '><center><input type='submit' name='btn_acao' value='Logar' class='botao2'><input type='hidden' value='$resu_admin'name='admin'></center></form>";
				$resposta .= "</td>";
	
				$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
				$resposta .= "</tr>";
			} //----------------FIM RESULTADO BUSCA IMPRIMI----------------// 
	
			$resposta .= "<tr>";
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%' colspan='7'></td>";
			$resposta .= "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
			$resposta .= "</tr>";
			
			$resposta .= "</table>";

			echo $resposta;
			exit;
	
		}else {echo "erro";exit;}
	
	}
}


?>
<!--<script language='javascript' src='../ajax.js'></script>-->
<script language='javascript'>

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
			

var http = new Array();

function Exibir(fabrica,dados){
	
	var temp = document.getElementById(dados);
	var curDateTime = new Date();
	http[curDateTime] = createRequestObject();
	url = "<?=$PHP_SELF?>?fabrica="+fabrica+"&ajax=sim" ;

	http[curDateTime].open('get',url);
	http[curDateTime].onreadystatechange = function(){
		if (http[curDateTime].readyState == 1) {
			temp.innerHTML   = "<font size='1'>Carregando...<br><img src='../imagens/carregar_os.gif'>";
		}
		if (http[curDateTime].readyState == 4) 
		{
			if (http[curDateTime].status == 200 || http[curDateTime].status == 304) 
			{
				var response = http[curDateTime].responseText;
				if (response!="erro"){
					temp.innerHTML   = response;
				}
				else {
					temp.innerHTML   ="<h4>Selecione a Fábrica</h4>";
				}
			}
		}
	}
	http[curDateTime].send(null);
}



</script>
<?
include "menu.php";

echo "<form name='frm_agenda' action='$PHP_SELF' method='post' >";
echo "<BR><table width = '600' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
echo "<tr>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>"; //linha esquerda - 2 linhas
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Escolha a Fábrica</b></td>";//centro
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//linha direita - 2 linhas
echo "</tr>";
echo "<tr>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
echo "<td>";

//COMBO FABRICA//
$sql = "SELECT   * 
		FROM     tbl_fabrica 
		ORDER BY nome";
$res = pg_exec ($con,$sql);
$n_fabricas = pg_numrows($res);

if ($n_fabricas > 0) {
	echo "<BR><center><select class='frm' style='width: 200px;' name='fabrica'></center>\n";
	echo "<option value=''>- FÁBRICA -</option>\n";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$fabrica   = trim(pg_result($res,$x,fabrica));
		$nome      = trim(pg_result($res,$x,nome));
		echo "<option value='$fabrica|$nome'"; if ($fabrica == $aux_fabrica) echo " SELECTED "; echo ">$nome</option>\n";
	}
	echo "</select>\n";

}


echo "<input type='button' name='btn_acao' value='Listas Usuários' class='botao2' onclick=\"Exibir(document.frm_agenda.fabrica.value,'dados')\">";
echo "<BR><BR>";


echo "</td>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
echo "</tr>";

echo "<tr>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
echo  "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";
echo "</table>";

echo "<DIV class='exibe' id='dados' value='1' align='center'></DIV>";

echo "</form>";
//RESULTADO DA BUSCA//
#if ((strlen ($adm_admin) > 0) && (strlen ($aux_admin) > 0)) {

include 'rodape.php';
?>