<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


$excluido = $_GET['excluir'];

if (strlen ($excluido) > 0) {
	$sql = "UPDATE tbl_posto SET
			senha_financeiro = null
			WHERE posto = $excluido";
	$deleta =  pg_exec ($con,$sql);

	if ($deleta) {
		Header("Location: $PHP_SELF");
	}
}
$liberar_acesso = $_GET['liberar_acesso'];

if (strlen ($liberar_acesso) > 0) {
	$sql = "UPDATE tbl_posto_fabrica SET
			login_provisorio = 'f'
			WHERE posto = $liberar_acesso and fabrica=$login_fabrica";
	$deleta =  pg_exec ($con,$sql);

	if ($deleta) {
		Header("Location: $PHP_SELF");
	}
}

$excluido_preco = $_GET['excluir_preco'];

if (strlen ($excluido_preco) > 0) {
	$sql = "UPDATE tbl_posto SET
			senha_tabela_preco = null
			WHERE posto = $excluido_preco";
	$deleta =  pg_exec ($con,$sql);

	if ($deleta) {
		Header("Location: $PHP_SELF");
	}
}
# --
$senha_padrao = $_GET['senha_padrao'];
if (strlen ($senha_padrao) > 0) {
	$posto   = $_GET['posto'];

	if($login_fabrica<>10)$fabrica = $login_fabrica;
	else                  $fabrica = $_GET['fabrica'];

	//CARACTERES DA SENHA
	$digitos    = 2;
	$caracteres = 'abcdefghijklmnopqrstuvwxyz';
	$cp_senha   = ''                          ;// variavel que será guardada a senha
	$y = strlen($caracteres)-1                ;//conta quantos caracteres tem na variavel $caracteres

	for($x=1;$x<=$digitos;$x++){
		$rand = rand(0,$y); //Funçao rand() - vai gerar um valor randômico com qualquer um daqueles caracteres da variavel $caracteres
		$str = substr($caracteres,$rand,1); // substr() - retorna parte de uma string
		$cp_senha .= $str;
	}

	//NUMEROS DA SENHA
	$digitos    = 4                    ;
	$caracteres = '1234567890'         ;
	$cp_senha_n = ''                   ;
	$y          = strlen($caracteres)-1;
	
	for($x=1;$x<=$digitos;$x++){
		$rand = rand(0,$y); //Funçao rand() - vai gerar um valor randômico com qualquer um daqueles caracteres da variavel $caracteres
		$str = substr($caracteres,$rand,1); // substr() - retorna parte de uma string
		$cp_senha_n .= $str;
	}

	$senha = $cp_senha.$cp_senha_n;

//	echo $senha;
//	exit;

	$sql = "UPDATE tbl_posto_fabrica SET
			senha             = '$senha'                       ,
			login_provisorio  = 'f'                            , 
			data_expira_senha = current_date + interval '90day'
		WHERE posto = $posto 
		AND fabrica = $fabrica;";

	$res = pg_exec ($con,$sql);

	$sql = "SELECT  tbl_posto.nome                            ,
			tbl_posto_fabrica.contato_email         as email                  ,
			tbl_fabrica.nome                AS fabrica,
			tbl_posto_fabrica.codigo_posto            
		FROM tbl_posto 
		JOIN tbl_posto_fabrica USING(posto) 
		JOIN tbl_fabrica       USING(fabrica)
		WHERE tbl_posto.posto     = $posto
		and   tbl_fabrica.fabrica = $fabrica;";

	$res = pg_exec ($con,$sql);

	$nome                 = pg_result($res,0,nome);
	$email                = pg_result($res,0,email);
	$nome                 = pg_result($res,0,nome);
	$fabrica              = pg_result($res,0,fabrica);
	$codigo_posto         = pg_result($res,0,codigo_posto);


	$email_origem  = "suporte@telecontrol.com.br";
	$email_destino = $email;
	$assunto       = "Nova senha do Assist: $fabrica";
	$corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR 
			NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
			<P align=justify>Prezado posto <STRONG>$nome</STRONG>
			</P>
			<P align=justify>Foi solicitado a geração de uma nova senha de acesso para o fabricante: <b>$fabrica</b> ao suporte Telecontrol. A partir de agora sua nova senha de acesso é: <font color='#FF0000'><b>$senha</b></font><br><br><b>Login:</b> $codigo_posto<br><b>Senha:</b> $senha<br>
			<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br 
			</P>";
	$body_top = "--Message-Boundary\n";
	$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
	$body_top .= "Content-transfer-encoding: 7BIT\n";
	$body_top .= "Content-description: Mail message body\n\n";


	if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
		$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
		header ("Location: $PHP_SELF?msg=$msg");
	}else{
		$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
	}
	
}




if($_POST['btn_acao']) $btn_acao = trim ($_POST['btn_acao']);

if (strlen ($btn_acao) > 0) {
	if($_POST['fabrica']) {
		$dados_fabrica = explode("|",$_POST['fabrica']);
		$aux_fabrica      = $dados_fabrica[0];
		$aux_nome_fabrica = $dados_fabrica[1];
	}
	if($_POST['codigo_posto'])        { $codigo_posto   = trim ($_POST['codigo_posto']);}
}
$TITULO = "Senha dos Postos Autorizados";
include "menu.php";
?>
<style>
.Linha{
	border-bottom: 1px dotted #666666
	font-family: arial;
	font-size:10px;
}
#senha b{
	display:block;
	width:150px;
	float:left;
	border-bottom:1px dotted #cccccc;
	clear:both;
}
</style>

<?
if(strlen($msg)>0) echo "<center>Senha alterada. $msg</center>";

echo "<form name='frm_agenda' action='$PHP_SELF' method='post' >";
echo "<BR><table width = '600' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
echo "<tr>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>"; //linha esquerda - 2 linhas
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>";
if($sistema_lingua == "ES") echo "Digite o código do posto";
else                        echo "Digite o código do posto e escolha a Fábrica";
echo "</b></td>";//centro
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
	echo "<br><center><font size='2' >";
	if($sistema_lingua == "ES") echo "Código servicio: ";
	else                        echo "Código Posto: ";
	echo "<INPUT TYPE='text' NAME='codigo_posto' class='caixa'>&nbsp;&nbsp;";
	if ($login_fabrica==10) {


	echo "Fábrica: <select class='frm' style='width: 200px;' name='fabrica' class='caixa'></center>\n";

	echo "<option value=''>- FÁBRICA -</option>\n";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$fabrica   = trim(pg_result($res,$x,fabrica));
		$nome      = trim(pg_result($res,$x,nome));
		echo "<option value='$fabrica|$nome'"; if ($fabrica == $aux_fabrica) echo " SELECTED "; echo ">$nome</option>\n";
	}
	echo "</select>\n";
	}
	echo "<br><br>";
}
echo "<input type='submit' name='btn_acao' value='";
if($sistema_lingua == "ES") echo "Exhibir servicio";
else                        echo "Exibir Postos";
echo "'>";


echo "</td>";

echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita

echo "</tr>";

echo "<tr>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";
echo "</table>";
echo "</form>";
if(strlen($msg)>0)exit;

//RESULTADO DA BUSCA//
#if ((strlen ($adm_admin) > 0) && (strlen ($aux_admin) > 0)) {

if($login_fabrica<>10)$aux_fabrica = $login_fabrica;

if (strlen ($aux_fabrica) > 0 AND strlen($_POST["btn_acao"])>0) {
	$sql = "SELECT  tbl_posto.posto                ,
					tbl_posto.nome                 ,
					tbl_posto_fabrica.senha        ,
					tbl_posto_fabrica.contato_email      as email           ,
					tbl_posto.senha_financeiro     ,
					tbl_posto.senha_tabela_preco   ,
					tbl_posto_fabrica.codigo_posto 
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE  tbl_posto_fabrica.fabrica = $aux_fabrica
			AND    tbl_posto_fabrica.codigo_posto ILIKE '%$codigo_posto%'";
if($sistema_lingua == "ES") $sql .= "AND tbl_posto.pais = '$login_pais'";
//echo $sql;
	$res = pg_exec ($con,$sql);
	$n_resu = pg_numrows($res);
			
	if ($n_resu > 0) {
		echo "<BR>";
		echo "<table width = '900' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2' ><img src='/assist/imagens/pixel.gif' width='9' ></td>"; //linha esquerda - 2 linhas
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100' style='font-family: arial ; color:#666666' colspan='5'>Resultado da busca por: <B>$aux_fabrica - $aux_nome_fabrica</B></td>";//centro
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";//linha direita - 2 linhas
		echo "</tr>";
		
		echo "<tr>";
		echo "<td bgcolor='#D9E8FF' align = 'center' style='font-family: arial ; color:#666666'><B>ID:</B></td>";
		echo "<td bgcolor='#D9E8FF' align = 'center' style='font-family: arial ; color:#666666'><B>";
		if($sistema_lingua == "ES") echo "Nombre";
		else                        echo "Nome";
		echo "</B></td>";
		echo "<td bgcolor='#D9E8FF' align = 'center' style='font-family: arial ; color:#666666'><B>Email</B></td>";
		echo "<td bgcolor='#D9E8FF' align = 'center' style='font-family: arial ; color:#666666'><B>";
		if($sistema_lingua == "ES") echo "";
		else                        echo "Senhas";
		echo "</B></td>";
		echo "<td bgcolor='#D9E8FF' align = 'center' style='font-family: arial ; color:#666666'><B>";
		if($sistema_lingua == "ES") echo "";
		else                        echo "Ações:";
		echo "</B></td>";

		echo "</tr>";


		//-----------CABECA RESULTADO BUSCA-----------//

		for ($i=0; $i<$n_resu; $i++){
			$posto              = pg_result($res,$i, posto);
			$nome               = pg_result($res,$i, nome);
			$codigo_posto       = pg_result($res,$i, codigo_posto);
			$senha_financeiro   = trim(pg_result($res,$i, senha_financeiro));
			$senha_tabela_preco = trim(pg_result($res,$i, senha_tabela_preco));
			$senha              = trim(pg_result($res,$i, senha));
			$email              = pg_result($res,$i, email);


			$senha                                                 ="******";
			if(strlen($senha_financeiro)==0)   $senha_financeiro   ="--";
			else                               $senha_financeiro   ="******";
			if(strlen($senha_tabela_preco)==0) $senha_tabela_preco ="--";
			else                               $senha_tabela_preco ="******";


			$cor = ($i % 2 == 0) ? '#FFFFFF' : '#F2F7FF';

			echo "<tr style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
			//----------------RESULTADO BUSCA----------------// 

			echo "<td><font size='-2'>$posto&nbsp;</font></td>";
			echo "<td width='150'><font size='1'>$codigo_posto - $nome&nbsp;</font></td>";
			echo "<td width='100'><font size='-2'>$email&nbsp;</font></td>";

			echo "<td width='200' title='SENHAS'><div id='senha'>";
			echo "<b>";
			if($sistema_lingua == "ES") echo "Acceso:";
			else                        echo "Acesso:";
			echo "</b>$senha<br>";
			echo "<b>";
			if($sistema_lingua == "ES") echo "Financieiro";
			else                        echo "Financeiro:";
			echo "</b>$senha_financeiro<br>";
			echo "<b>";
			if($sistema_lingua == "ES") echo "Tabla de precios";
			else                        echo "Tabela de Preço:";
			echo "</b>$senha_tabela_preco<br>";
			echo "<b>";
			if($sistema_lingua == "ES") echo "Confirmación de emails:";
			else                        echo "Confirmação de e-mail:";
			echo "</b>******<br>";
			echo "</div></td>";

			echo "<td>";

			echo "<a href=\"javascript: if (confirm('";
			if($sistema_lingua == "ES") echo "Desea Crear clave de acceso al servicio ";
			else                        echo "Deseja realmente criar uma senha e enviar ao posto ";
			echo "$codigo_posto - $nome ?') == true) { window.location='$PHP_SELF?senha_padrao=OK&posto=$posto&fabrica=$aux_fabrica'; }\"><img src='imagem/btn_ok.gif'border='0' align='absmiddle'> ";
			if($sistema_lingua == "ES") echo "Crear clave de acceso";
			else                        echo "Criar Senha Acesso";
			echo "&nbsp;</a> ";

			echo "<br><a href=\"javascript: if (confirm('";
			if($sistema_lingua == "ES") echo "Desea excluir clave financieiro al servicio ";
			else                        echo "Deseja realmente excluir a senha do fincanceiro do posto ";
			echo " $codigo_posto - $nome ?') == true) { window.location='$PHP_SELF?excluir=$posto'; }\"><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'> ";
			if($sistema_lingua == "ES") echo "Excluir clave financieiro";
			else                        echo "Excluir Senha Financeiro";
			echo "&nbsp;</a>";

			echo "<br><a href=\"javascript: if (confirm('";
			if($sistema_lingua == "ES") echo "Desea borrar  clave de la tabla de precios al servicio ";
			else                        echo "Deseja realmente excluir a senha da Tabela de preço do posto ";
			echo "$codigo_posto - $nome ?') == true) { window.location='$PHP_SELF?excluir_preco=$posto'; }\"><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'> ";
			if($sistema_lingua == "ES") echo "Borrar  clave de la tabla de precios";
			else                        echo "Excluir Senha da Tabela de Preço";
			echo "</a><br>";

			echo "<a href=\"javascript: if (confirm('";
			if($sistema_lingua == "ES") echo "Desea liberar acceso al servicio ";
			else                        echo "Deseja realmente liberar o acesso do posto ";
			echo " $codigo_posto - $nome ?') == true) { window.location='$PHP_SELF?liberar_acesso=$posto'; }\"><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>";
			if($sistema_lingua == "ES") echo "Liberar acceso al servicio";
			else                        echo "Liberar acesso do POSTO";
			echo "</a><br>";
			echo "</td>";

			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
			echo "</tr>";
		} //----------------FIM RESULTADO BUSCA IMPRIMI----------------// 

		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%' colspan='5'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		
		echo "</table>";

	}

}


?>