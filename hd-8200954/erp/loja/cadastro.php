<BODY TOPMARGIN=0>
<?
include 'dbconfig.php';
include 'dbconnect-inc.php';
include 'configuracao.php';


if(strlen($_GET["pessoa"]) > 0) $pessoa = $_GET["pessoa"];
else                            $pessoa = $_POST["pessoa"];

$btn_acao = trim($_GET["btn_acao"]);

if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST["btn_acao"]);
}

if ($btn_acao == "Gravar"){

	$pessoa           = trim($_POST['pessoa']);
	$nome             = trim($_POST['nome']);
	$cnpj             = trim($_POST['cnpj']);
	$ie               = trim($_POST['ie']);
	$data_nascimento  = trim($_POST['data_nascimento']);
	$endereco         = trim($_POST['endereco']);
	$bairro           = trim($_POST['bairro']);
	$cidade           = trim($_POST['cidade']);
	$estado           = trim($_POST['estado']);
	$cep              = trim($_POST['cep']);
	$email            = trim($_POST['email']);
	$senha            = trim($_POST['senha']);

	if(strlen($nome)     == 0 ) $msg_erro .= "Digite o nome<br>";
	if(strlen($cnpj)     == 0 ) $msg_erro .= "Digite o CPF<br>";
	if(strlen($ie)       == 0 ) $msg_erro .= "Digite o RG<br>";
	if(strlen($data_nascimento)  == 0 ) $msg_erro .= "Digite a Data de Nascimento<br>";
	if(strlen($endereco) == 0 ) $msg_erro .= "Digite o Endereço<br>";
	if(strlen($bairro)   == 0 ) $msg_erro .= "Digite o Bairro<br>";
	if(strlen($cidade)   == 0 ) $msg_erro .= "Digite a Cidade<br>";
	if(strlen($estado)   == 0 ) $msg_erro .= "Digite o Estado<br>";
	if(strlen($cep)      == 0 ) $msg_erro .= "Digite o Cep<br>";
	if(strlen($email)    == 0 ) $msg_erro .= "Digite o Email<br>";
	if(strlen($senha)    == 0 ) $msg_erro .= "Digite a Senha<br>";

	//INFORMAÇÕES GERAIS
	if(strlen($nome)                 > 0) $xnome             = "'".$nome."'";
	else                                  $xnome             = "null";
	if(strlen($login_empresa )       > 0) $xlogin_empresa    = "'".$login_empresa."'";
	else                                  $xlogin_empresa    = "null";
	if(strlen($cnpj )                > 0) $xcnpj             = "'".$cnpj."'";
	else                                  $xcnpj             = "null";
	if(strlen($ie )                  > 0) $xie               = "'".$ie."'";
	else                                  $xie               = "null";
	/*if(strlen($data_nascimento )     > 0) $xdata_nascimento  = "'".$data_nascimento."'";
	else                                  $xdata_nascimento  = "null";*/
	if(strlen($endereco)             > 0) $xendereco         = "'".$endereco."'";
	else                                  $xendereco         = "null";
	if(strlen($bairro)               > 0) $xbairro           = "'".$bairro."'";
	else                                  $xbairro           = "null";
	if(strlen($cidade)               > 0) $xcidade           = "'".$cidade."'";
	else                                  $xcidade           = "null";
	if(strlen($estado)               > 0) $xestado           = "'".$estado."'";
	else                                  $xestado           = "null";
	if(strlen($cep)                  > 0) $xcep              = "'".$cep."'";
	else                                  $xcep              = "null";
	if(strlen($email)                > 0) $xemail            = "'".$email."'";
	else                                  $xemail            = "null";
	if(strlen($senha)                > 0) $xsenha            = "'".$senha."'";
	else                                  $xsenha            = "null";

	$xcep = str_replace(".", "",$xcep);
	$xcep = str_replace("-", "",$xcep);
	$xcep = str_replace("/", "",$xcep);

	$xcnpj = str_replace(".", "",$xcnpj);
	$xcnpj = str_replace("-", "",$xcnpj);
	$xcnpj = str_replace("/", "",$xcnpj);

	$xie = str_replace(".", "",$xie);
	$xie = str_replace("-", "",$xie);
	$xie = str_replace("/", "",$xie);

	/*#################################################################################
	################################## CADASTRO #######################################*/
	
		if(strlen($msg_erro)==0){
			
			if (strlen($pessoa)==0){
			$sql = "SELECT * 
					FROM tbl_pessoa 
					WHERE cnpj = $xcnpj 
					AND empresa = $login_empresa";
			$res = pg_exec ($con,$sql);

			if(pg_numrows($res)>0){
				$pessoa = trim(pg_result($res,0,pessoa));
				$pessoa = $pessoa;
				$msg_erro = "CPF já cadastrado.";
			}else{
			$res = pg_exec ($con,"BEGIN TRANSACTION");

			$data_nascimento = str_replace("/","",$data_nascimento);
			$data_nascimento =  "'" . substr($data_nascimento,4,4) . "-" . substr($data_nascimento,2,2) . "-" . substr($data_nascimento,0,2) . "'";

				$sql = "INSERT INTO tbl_pessoa (
							nome             ,
							empresa          ,
							cnpj             ,
							ie               ,
							data_nascimento  ,
							endereco         ,
							bairro           ,
							cidade           ,
							estado           ,
							cep              ,
							email            ,
							senha
						)VALUES (
							$xnome            ,
							$login_empresa    ,
							$xcnpj            ,
							$xie              ,
							$data_nascimento ,
							$xendereco        ,
							$xbairro          ,
							$xcidade          ,
							$xestado          ,
							$xcep             ,
							$xemail           ,
							$xsenha
						)";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$res    = pg_exec ($con,"SELECT CURRVAL ('tbl_pessoa_pessoa_seq')");
				$pessoa = pg_result ($res,0,0);
				
			}
			
			$sql = "SELECT pessoa FROM tbl_pessoa_cliente WHERE pessoa = $pessoa";
			$res = pg_exec ($con,$sql);
			if(pg_numrows($res) == 0){
				$sql  = "INSERT INTO tbl_pessoa_cliente (
						pessoa ,
						empresa,
						ativo
					)VALUES(
						$pessoa    ,
						$login_empresa,
						't'
					)";
				$res = pg_exec ($con,$sql);
				}
			
			}
		}
		if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Pessoa $pessoa gravado com sucesso!";
		//header ("Location: $PHP_SELF?tipo=$tipo&ok=1");
			
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}

	}


	/*############################# FIM CADASTRO ######################################
	###################################################################################*/


include "topo.php";
echo "<table width='750' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td width='182' valign='top'>";
include "menu.php";
echo "<BR>";
echo "</td>";
echo "<td width='568' align='right' valign='top'>";
echo "<BR>";
	//Mensagem
	if (strlen ($msg_erro) > 0){
		echo  "<p align=center>"."<FONT SIZE='3' COLOR='#FF0000'>".$msg_erro."</FONT>"."</p>";
	}
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<table width='550' border='0' align='center' cellpadding='4' cellspacing='2' style='font-family: verdana; font-size: 12px'>";
	echo "<tr>";
	echo "<td colspan='2'>";
	echo "<B>Cadastro de Cliente</b><BR><BR>
		Bem-vindo ao Sistema Tecnoplus, para realizar sua compra on-line é necessário realizar um simples cadastro em nossa loja.<BR><BR>
		Por favor preencha as informações para que nossa empresa possa realizar um atendimento adequado a você. <BR><BR>
		Boas compras!!!<BR><BR>";
	echo "</td>";	
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='2'>";
	echo "<B>Dados do Cliente</b>";
		
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left' width='100'>";
		echo "&nbsp;&nbsp;Nome:";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='text' size='40' maxlength='40' name='nome' value='$nome' >";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "&nbsp;&nbsp;CPF:";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='text' size='14' maxlength='14' name='cnpj' value='$cnpj'> 000.000.000-00";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";	
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "&nbsp;&nbsp;RG:";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='text' size='14' maxlength='13' name='ie' value='$ie'> 000.000.000-0";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "&nbsp;&nbsp;Data Nascimento:";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='text' size='10' maxlength='10' name='data_nascimento' value='$data_nascimento'> dd/mm/aaaa";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";	
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "&nbsp;&nbsp;Endereço:";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='text' size='40' maxlength='40' name='endereco' value='$endereco'> ";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";	
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "&nbsp;&nbsp;Bairro:";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='text' size='40' maxlength='40' name='bairro' value='$bairro'> ";
		echo "</td>";
	echo "</tr>";
	
	echo "<tr>";	
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "&nbsp;&nbsp;Cidade:";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='text' size='24' maxlength='40' name='cidade' value='$cidade'> ";
		echo "&nbsp;&nbsp; Estado:&nbsp;&nbsp;<input type='text' size='3' maxlength='2' name='estado' value='$estado'>";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";	
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "&nbsp;&nbsp;CEP:";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='text' size='12' maxlength='9' name='cep' value='$cep'> 99999-999";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";	
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "&nbsp;&nbsp;E-mail";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='text' size='40' maxlength='40' name='email' value='$email'> ";
		echo "</td>";
	echo "</tr>";
		echo "<tr>";	
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "&nbsp;&nbsp;Senha:";
		echo "</td>";
		echo "<td style='font-family: verdana; font-size: 10px' align='left'>";
		echo "<input type='password' size='40' maxlength='10' name='senha' value='$senha'> ";
		echo "</td>";
	echo "</tr>";
	
	echo "</tr>";
		echo "<tr>";	
		echo "<td style='font-family: verdana; font-size: 10px'  colspan='2'>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='reset' name='btn_limpar' value='Limpar' class='botao'>&nbsp;&nbsp;&nbsp; <input type='submit' name='btn_acao'  value='Gravar' class='botao'>";
		echo "</td>";
		
	echo "</tr>";
	echo "</table><BR>";
	echo "</form>";
	echo "</td>";
echo "</tr>";
	echo "<tr>";
		echo "<td colspan='2' height='60' bgcolor='#f3f2f1' align='center'>
		&nbsp;<a href='index.php'>Home</a>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='empresa.php'>Quem Somos</a>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='cadatro.php'>Cadastro</A>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='promocao.php'>Destaque</A>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='#'>Fale Conosco</a><BR>
		Tecnoplus 2007 -  Todos os direitos Reservados<BR>
		Sistema Telecontrol";
		echo "</td>";
	echo "</tr>";
echo "</table>";

?>