<? 
		$liq1=$_POST['liquidificador1'];
		$liq1=$_POST['liquidificador1'];
		$liq2=$_POST['liquidificador2'];
		$liq3=$_POST['liquidificador3'];
		$caf1=$_POST['cafeteira1'];
		$caf2=$_POST['cafeteira2'];
		$caf3=$_POST['cafeteira3'];
		$nome=$_POST['nome'];
		$endereco=$_POST['endereco'];
		$bairro=$_POST['bairro'];
		$telefone=$_POST['telefone'];
		$cidade=$_POST['cidade'];
		$cep=$_POST['cep'];
		$email=$_POST['email'];
		$obs=$_POST['obs'];


//-=============================FUNÇÃO VALIDA EMAIL==============================-//
function validatemail($email=""){ 
    if (preg_match("/^[a-z]+([\._\-]?[a-z0-9]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email)) { 
//validacao anterior [a-z0-9\._-]
		$valida = "1"; 
    } 
    else { 
        $valida = "0"; 
    } 
    return $valida; 
} 


$email = $_POST['email'];
if(strlen($email)>0){

	if (validatemail($email)) { 


		//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

		$email_origem  = "$email";
		$email_destino = "fernando@telecontrol.com.br";
		$assunto       = "Compra de Produtos";
		$corpo        .="<br>Nome: $nome \n";
		$corpo        .="<br>Endereco: $endereco \n";
		$corpo        .="<br>Bairro: $bairro \n";
		$corpo        .="<br>Cidade: $cidade \n";
		$corpo        .="<br>CEP: $cep \n";
		$corpo        .="<br>Email: $email \n\n";
		$corpo        .="<br>Telefone: $telefone \n";
		$corpo        .="<br>LIQUIDIFICADORES:<br> Liquidificador 1= $liq1<br> Liquidificador 2= $liq2<br> Liquidificador 3= $liq3 \n";
		$corpo        .="<br>CAFETEIRAS:<br> Cafeteira 1= $caf1<br> Cafeteira 2= $caf2<br>  Cafeteira 3= $caf3 \n";
		$corpo        .="<br>OBS.:<br> $obs \n";
		$corpo        .="<br>_______________________________________________\n";


		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

//$corpo = $body_top.$corpo;

		if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
			$msg = "<br>Foi enviado um email para: ".$email.", e nele há um link para confirmar a validade do email.<br>Logo após a confirmação o sistema estará liberado!<br>";
		}else{
			$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";

		}

	echo "<CENTER>Pedido enviado com sucesso</CENTER>";
	
	}else{
		echo "<table width='650'>";
		echo "<tr>";
		echo "<td bgcolor='#3399FF'><h3>Este endereço de Email não é válido: $email</h3>";
		echo "</tr>";
		echo "</table>";
		exit;
	}
}
?>