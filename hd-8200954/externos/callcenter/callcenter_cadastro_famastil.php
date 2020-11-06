	<?	
		include '/var/www/assist/www/dbconfig.php';
		include '/var/www/assist/www/includes/dbconnect-inc.php';		
		// require( '/www/assist/www/class_resize.php' );

		if ($_POST["buscaCidade"] == true) {
			$estado = strtoupper($_POST["estado"]);

			if (strlen($estado) > 0) {
				$sql = "SELECT DISTINCT * FROM (
					SELECT UPPER(TO_ASCII(nome, 'LATIN9')) AS cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(nome, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
					UNION (
						SELECT UPPER(TO_ASCII(cidade, 'LATIN9')) AS cidade FROM tbl_ibge WHERE UPPER(TO_ASCII(cidade, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
					)
				) AS cidade ORDER BY cidade ASC";
				$res  = pg_query($con, $sql);
				$rows = pg_num_rows($res);

				if ($rows > 0) {
					$cidades = array();

					for ($i = 0; $i < $rows; $i++) { 
						$cidades[$i] = array(
							"cidade"          => utf8_encode(pg_fetch_result($res, $i, "cidade")),
							"cidade_pesquisa" => utf8_encode(strtoupper(pg_fetch_result($res, $i, "cidade"))),
						);
					}

					$retorno = array("cidades" => $cidades);
				} else {
					$retorno = array("erro" => "Nenhuma cidade encontrada para o estado {$estado}");
				}
			} else {
				$retorno = array("erro" => "Nenhum estado selecionado");
			}

			exit(json_encode($retorno));
		}

		$buscaProduto = @$_REQUEST['buscaProduto'];
		if($buscaProduto == "buscaProduto"){
			$familia = $_REQUEST['familia'];
			if(is_numeric($familia)) {
				$sql     = "SELECT produto, descricao FROM tbl_produto WHERE familia = $familia ORDER BY descricao ASC;";
				$res     = pg_exec($con,$sql);
				if (pg_numrows ($res) > 0) {
					for ($i=0; $i<pg_numrows ($res); $i++ ){
						$codigo    = pg_result($res,$i,'produto');
						$descricao = pg_result($res,$i,'descricao');
						echo "<option value='$codigo'> $descricao</option>";
					}
				}else{
					echo "<option value=''> Nenhum produto encontrada para esta família.</option>";
				}
			}
			exit;
		}

		$buscaCep = @$_POST['buscaCep'];
		if($buscaCep == 'buscaCep'){
			$cep = trim($_POST ['cep']);
			$cep = str_replace (".","",$cep);
			$cep = str_replace ("-","",$cep);
			$cep = str_replace (" ","",$cep);
			if (strlen ($cep) == 8) {
				$sql = "SELECT * FROM tbl_cep WHERE cep = '$cep'";
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) > 0) {
					$logradouro = trim (pg_fetch_result($res,0,logradouro));
					$bairro     = trim (pg_fetch_result($res,0,bairro));
					$cidade     = trim (pg_fetch_result($res,0,cidade));
					$estado     = trim (pg_fetch_result($res,0,estado));
					echo "ok;". $logradouro . ";" . $bairro . ";" . $cidade . ";" . $estado ;
				}
			}
			exit;
		}

		$fabrica       = 86;
		$login_fabrica = 86;
		$msg           = $_GET['msg'];
		if($_POST['acao'] == 'email'){
			$aux_nome        = ucwords(trim($_POST['nome']));
			$aux_fone        = trim($_POST['fone']);
			$aux_cep         = trim($_POST['cep']);
			$aux_cep         = str_replace (".","",$aux_cep);
			$aux_cep         = str_replace ("-","",$aux_cep);
			$aux_cep         = str_replace (" ","",$aux_cep);
			$aux_endereco    = trim($_POST['endereco']);
			$aux_numero      = trim($_POST['numero']);
			$aux_complemento = trim($_POST['complemento']);
			$aux_bairro      = trim($_POST['bairro']);
			$aux_cidade      = trim($_POST['cidade']);
			$aux_estado      = trim($_POST['estado']);
			$aux_email       = trim($_POST['email']);
			$aux_assunto     = trim($_POST['assunto']);
			$aux_familia     = $_POST['familia'];
			$aux_produto     = $_POST['produto'];
			$aux_mensagem    = ucfirst(trim($_POST['mensagem']));
			if(strlen($aux_nome) == 0){
				$msg_erro = "Nome.<br>";
			}
			if(strlen($aux_fone) == 0){
				$msg_erro .= "Fone.<br>";
			}
			if(strlen($aux_cep) == 0){
				$msg_erro .= "Cep.<br>";
			}
			if(strlen($aux_endereco) == 0){
				$msg_erro .= "Endereço.<br>";
			}
			if(strlen($aux_numero) == 0){
				$msg_erro .= "Número.<br>";
			}
			if(strlen($aux_bairro) == 0){
				$msg_erro .= "Bairro.<br>";
			}
			if(strlen($aux_cidade) == 0){
				$msg_erro .= "Cidade.<br>";
			}
			if(strlen($aux_estado) == 0){
				$msg_erro .= "Estado.<br>";
			}
			if(strlen($aux_assunto) == 0){
				$msg_erro .= "Assunto.<br>";
			}
			if(strlen($aux_familia) == 0){
				$msg_erro .= "Familia.<br>";
			}
			if(strlen($aux_produto) == 0){
				$msg_erro .= "Produto.<br>";
			}
			if(strlen($aux_mensagem) == 0){
				$msg_erro .= "Mensagem.<br>";
			}
			if(strlen($msg_erro)==0){
				$sql         = "SELECT descricao FROM tbl_produto WHERE familia = '$aux_familia' AND produto = '$aux_produto';";
				$res         = pg_exec($con, $sql);
				$aux_produto = trim(pg_result ($res,0,descricao));
				$sql         = "SELECT descricao FROM tbl_familia WHERE familia = '$aux_familia';";
				$res         = pg_exec($con, $sql);
				$aux_familia = trim(pg_result ($res,0,descricao));
				$titulo      = 'Atendimento interativo - Contato Site';
				$atendentes  = 'marketing@famastil.com.br';
				$email = "<table border='0' bgcolor='#CCCC66'>
							<tr>
								<td><font color='blue'>Nome:</font></td>
								<td>".$aux_nome."</td>
							</tr>
							<tr>
								<td><font color='blue'>Telefone:</font></td>
								<td>".$aux_fone."</td>
							</tr>
							<tr>
								<td><font color='blue'>CEP:</font></td>
								<td>".$aux_cep."</td>
							</tr>
							<tr>
								<td><font color='blue'>Endereço:</font></td>
								<td>".$aux_endereco."</td>
							</tr>
							<tr>
								<td><font color='blue'>Número:</font></td>
								<td>".$aux_numero."</td>
							</tr>
							<tr>
								<td><font color='blue'>Complemento:</font></td>
								<td>".$aux_complemento."</td>
							</tr>
							<tr>
								<td><font color='blue'>Bairro:</font></td>
								<td>".$aux_bairro."</td>
							</tr>
							<tr>
								<td><font color='blue'>Cidade:</font></td>
								<td>".$aux_cidade."</td>
							</tr>
							<tr>
								<td><font color='blue'>Estado:</font></td>
								<td>".$aux_estado."</td>
							</tr>
							<tr>
								<td><font color='blue'>E-Mail:</font></td>
								<td>".$aux_email."</td>
							</tr>
							<tr>
								<td><font color='blue'>Assunto:</font></td>
								<td>".$aux_assunto."</td>
							</tr>
							<tr>
								<td><font color='blue'>Familia:</font></td>
								<td>".$aux_familia."</td>
							</tr>
							<tr>
								<td><font color='blue'>Produto:</font></td>
								<td>".$aux_produto."</td>
							</tr>
							<tr>
								<td><font color='blue'>Mensagem:</font></td>
								<td>".$aux_mensagem."</td>
							</tr>
							<tr>
								<td colspan='2'>&nbsp;</td>
							</tr>
							<tr>
								<td colspan='2'><font color='red'><br>EMAIL AUTOMÁTICO FAVOR NÃO RESPONDER !</b></font></td>
							</tr>
						</table>"
						;
				$headers  = 'From: Fale Conosco <faleconosco@famastil.com.br>' . "\r\n";
				$headers .= 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			    // mail($atendentes,$titulo,$email,$headers); 
				$username = 'tc.sac.famastil@gmail.com';
				$senha = 'tcfamastil';
			    try{
					sendEmail(
						utf8_encode($titulo),
						utf8_encode($email),
						array($atendentes),
						array(
							'user' => $username,
							'pass' => $senha
						)
					);															
					echo "<script>alert('Mensagem enviada com sucesso !')</script>";
			    }catch(Exception $e){
			    	echo "<script>alert('Erro ao enviar mensagem !')</script>";
				}
				$aux_nome        = "";
				$aux_fone        = "";
				$aux_cep         = "";
				$aux_endereco    = "";
				$aux_numero      = "";
				$aux_complemento = "";
				$aux_bairro      = "";
				$aux_cidade      = "";
				$aux_estado      = "";
				$aux_email       = "";
				$aux_assunto     = "";
				$aux_familia     = "";
				$aux_produto     = "";
				$aux_mensagem    = "";
			} else {
				$msg_erro2 = "Os seguintes campos não foram preenchidos.<br> Para prosseguir preencha:<br><br>";
			}
		}

		if($_POST['acao'] == 'submit'){
			$aux_nome        = trim($_POST['nome']);
			$aux_fone        = trim($_POST['fone']);
			$aux_fone2  = preg_replace("/\D/","",$aux_fone);
			$aux_cep         = trim($_POST['cep']);
			$aux_cep         = str_replace (".","",$aux_cep);
			$aux_cep         = str_replace ("-","",$aux_cep);
			$aux_cep         = str_replace (" ","",$aux_cep);
			$aux_endereco    = trim($_POST['endereco']);
			$aux_numero      = trim($_POST['numero']);
			$aux_complemento = trim($_POST['complemento']);
			$aux_bairro      = trim($_POST['bairro']);
			$aux_cidade      = trim($_POST['cidade']);
			$aux_estado      = trim($_POST['estado']);
			$aux_email       = trim($_POST['email']);
			$aux_assunto     = trim($_POST['assunto']);
			$aux_familia     = $_POST['familia'];
			$aux_produto     = $_POST['produto'];
			$aux_mensagem    = trim($_POST['mensagem']);
			if(strlen($aux_nome) == 0){
				$msg_erro = "Nome.<br>";
			}
			if(strlen($aux_fone) == 0){
				$msg_erro .= "Fone.<br>";
			}
			if(strlen($aux_cep) == 0){
				$msg_erro .= "Cep.<br>";
			}
			if(strlen($aux_endereco) == 0){
				$msg_erro .= "Endereço.<br>";
			}
			if(strlen($aux_numero) == 0){
				$msg_erro .= "Número.<br>";
			}
			if(strlen($aux_bairro) == 0){
				$msg_erro .= "Bairro.<br>";
			}
			if(strlen($aux_cidade) == 0){
				$msg_erro .= "Cidade.<br>";
			}
			if(strlen($aux_estado) == 0){
				$msg_erro .= "Estado.<br>";
			}
			if(strlen($aux_assunto) == 0){
				$msg_erro .= "Assunto.<br>";
			}
			if(strlen($aux_familia) == 0){
				$msg_erro .= "Familia.<br>";
			}
			if(strlen($aux_produto) == 0){
				$msg_erro .= "Produto.<br>";
			}
			if(strlen($aux_mensagem) == 0){
				$msg_erro .= "Mensagem.<br>";
			}
			if(strlen($msg_erro)==0){
				$res = pg_exec ($con,"BEGIN TRANSACTION");
				if( strlen($aux_estado)>0 and strlen($aux_cidade)>0){
					/* $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(nome, 'LATIN9')) = UPPER(TO_ASCII('{$aux_cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$aux_estado}')";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						$aux_cidade = pg_result($res,0,0);
					}else{
						$sql        = "INSERT INTO tbl_cidade(nome, estado)values(upper('$aux_cidade'),'$aux_estado')";
						$res        = pg_exec($con,$sql);
						$msg_erro  .= pg_errormessage($con);
						$res        = pg_exec ($con,"SELECT CURRVAL ('seq_cidade')");
						$aux_cidade = pg_result ($res,0,0);
					} */

					/* Verifica Cidade */

					$cidade = $aux_cidade;
					$estado = $aux_estado;

					$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
					$res = pg_query($con, $sql);

					if(pg_num_rows($res) == 0){

						$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
						$res = pg_query($con, $sql);

						if(pg_num_rows($res) > 0){

							$aux_cidade = pg_fetch_result($res, 0, 'cidade');
							$estado = pg_fetch_result($res, 0, 'estado');

							$sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$cidade', '$estado')";
							$res = pg_query($con, $sql);

						}else{
							$aux_cidade = 'null';
						}

					}else{
						$aux_cidade = pg_fetch_result($res, 0, 'cidade');
					}

					/* Fim - Verifica Cidade */

				}
			}
			if(strlen($msg_erro) == 0) {
				$res                = pg_exec ($con,"BEGIN TRANSACTION");
				$aux_nome           = mb_convert_encoding( "$aux_nome"                  , 'ISO-8859-1', 'UTF-8' );
				$aux_cep            = mb_convert_encoding( "$aux_cep"                   , 'ISO-8859-1', 'UTF-8' );
				$aux_endereco       = mb_convert_encoding( "$aux_endereco"              , 'ISO-8859-1', 'UTF-8' );
				$aux_numero         = mb_convert_encoding( "$aux_numero"                , 'ISO-8859-1', 'UTF-8' );
				$aux_complemento    = mb_convert_encoding( "$aux_complemento"           , 'ISO-8859-1', 'UTF-8' );
				$aux_bairro         = mb_convert_encoding( "$aux_bairro"                , 'ISO-8859-1', 'UTF-8' );
				$aux_cidade         = mb_convert_encoding( "$aux_cidade"                , 'ISO-8859-1', 'UTF-8' );
				$aux_estado         = mb_convert_encoding( "$aux_estado"                , 'ISO-8859-1', 'UTF-8' );
				$aux_email          = mb_convert_encoding( "$aux_email"                 , 'ISO-8859-1', 'UTF-8' );
				$aux_msg            = mb_convert_encoding( "$aux_msg"                   , 'ISO-8859-1', 'UTF-8' );
				$titulo             = 'Atendimento interativo';
				$xstatus_interacao  = "'Aberto'";
				$sql_admins = "SELECT
									admin
							   FROM
									tbl_admin
							   WHERE 
									fabrica = $login_fabrica
							   AND fale_conosco IS TRUE
							   AND ativo IS TRUE
							   ORDER BY admin ";
				$res_admins = @pg_query($con, $sql_admins);
				if(pg_num_rows($res_admins) > 0){
					if (is_resource($res_admins)) {
						$admins = pg_fetch_all($res_admins);
						foreach ($admins as $a_admin) {
							$at[]= $a_admin['admin'];
						}
						$atendentes = implode(',',$at);
						$sql_last = "SELECT
											atendente
									 FROM
											tbl_hd_chamado
									 WHERE
											fabrica_responsavel = $login_fabrica
									 AND atendente IN($atendentes)
									 ORDER BY data DESC
									 LIMIT 1";
						$res_last = @pg_query($con, $sql_last);
						unset($at,$atendentes);
							if (pg_num_rows($res_last) == 1) {
								$login_admin = pg_fetch_result($res_last, 0, 0);
								foreach($admins as $idx => $atendente) {
									$admin = $atendente['admin'];
									//echo "<pre>Último: $login_admin, conferindo lista, atual (" . ++$idx ." de " . count($admins) ."): $admin</pre>";
									++$idx;
									if ($admin == $login_admin) break;
								}
								if ($idx == (count($admins))) {
									reset($admins);
								}
								$atendente = current($admins);
								$login_admin	= $atendente['admin'];
								}
					}
					if(strlen($aux_email) == 0 AND strlen($msg_erro) == 0){
						$aux_email = "null";
					}
					$sql = "INSERT INTO tbl_hd_chamado (
								admin              ,
								data               ,
								status             ,
								atendente          ,
								fabrica_responsavel,
								titulo             ,
								categoria          ,
								fabrica
							) VALUES (
								$login_admin      ,
								current_timestamp ,
								$xstatus_interacao,
								$login_admin      ,
								$login_fabrica    ,
								'$titulo'         ,
								'$aux_assunto'    ,
								$login_fabrica
							)";
					$res        = pg_exec($con,$sql);
					$msg_erro  .= pg_errormessage($con);
					$res        = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
					$hd_chamado = pg_result ($res,0,0);
					$sql = "INSERT INTO tbl_hd_chamado_extra (
								hd_chamado ,
								nome       ,
								fone       ,
								cep        ,
								endereco   ,
								numero     ,
								complemento,
								bairro     ,
								cidade     ,
								email      ,
								familia    ,
								produto    ,
								reclamado
							) VALUES (
								$hd_chamado       ,
								'$aux_nome'       ,
								'$aux_fone2'       ,
								'$aux_cep'        ,
								'$aux_endereco'   ,
								'$aux_numero'     ,
								'$aux_complemento',
								'$aux_bairro'     ,
								$aux_cidade       ,
								'$aux_email'      ,
								'$aux_familia'    ,
								'$aux_produto'    ,
								'$aux_mensagem'
							) ";
					$res       = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				} else {
					$msg_erro2 = "Não há usuarios selecionados para receber mensagens de CallCenter.";
				}
			} else {
				$msg_erro2 = "Os seguintes campos não foram preenchidos.<br> Para prosseguir preencha:<br><br>";
			}
			if (strlen($msg_erro) == 0 and strlen($msg_erro2) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				$address = getEmail($login_fabrica);
				$emailSubject = 'Atendimento interativo - Contato Site';
				$emailBody = makeEmail(array(
					'Nome:'=>$aux_nome,
					'Telefone:'=>$aux_fone,
					'CEP:'=>$aux_cep,
					'Endereço:'=>$aux_endereco,
					'Número:'=>$aux_numero,
					'Complemento:'=>$aux_complemento,
					'Bairro:'=>$aux_bairro,
					'Cidade:'=>$aux_cidade,
					'Estado:'=>$aux_estado,
					'E-Mail:'=>$aux_email,
					'Assunto:'=>$aux_assunto,
					'Familia:'=>$aux_familia,
					'Produto:'=>$aux_produto,
					'Mensagem:'=>$aux_mensagem,
					'Número do Atendimento:' => $hd_chamado
				));
				$auth = array(
					'user'=>'tc.sac.famastil@gmail.com',
					'pass'=>'tcfamastil'
				);

				try{
					sendEmail($emailSubject,$emailBody,$address,$auth);	
					echo "<script>alert('Mensagem enviada com sucesso !')</script>";
				}
				catch(Exception $ex){

				}

				
				$aux_nome        = "";
				$aux_fone        = "";
				$aux_cep         = "";
				$aux_endereco    = "";
				$aux_numero      = "";
				$aux_complemento = "";
				$aux_bairro      = "";
				$aux_cidade      = "";
				$aux_estado      = "";
				$aux_email       = "";
				$aux_assunto     = "";
				$aux_familia     = "";
				$aux_produto     = "";
				$aux_mensagem    = "";
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}

		$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
							  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
							  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
							  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
							  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
							  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
							  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
							  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins"
							 );
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
	<head>
		<style type="text/css" media="all">
			html {background-color: #3D4B2A;}
			body , html{ height:100%; width:100%; }
			body { font-family: Arial; font-size: 11px; color: #000;}
			.ctn-form {
				background-image: url(imagens_famastil/bg_famastil.jpg);
				background-repeat: repeat-x;
				width: 380px;
				height: 940px;
				float: left;
				clear: both;
			}

			.ctn-form form label {
				float: left;
				clear: both;
				font-size: 11px;
				font-weight: bold;
				color: #fff;
				margin-top: 10px;
				margin-left: 10px;
			}

			.ctn-form .campo input {
				background-color: transparent;
				border: none;
				width: 320px;
				float: left;
				padding: 0 10px 0 10px;
				outline: none;
				margin-top: 5px;
			}


			.ctn-form .campo select {
				background-color: transparent;
				border: none;
				width: 340px;
				float: left;
				padding: 0 10px 0 10px;
				outline: none;
				margin-top: 5px;
			}

			.ctn-form .campo {
				background-image: url(imagens_famastil/campo_famastil.jpg);
				background-repeat: no-repeat;
				width: 340px;
				height: 25px;
				float: left;
				clear: both;
				margin-top: 5px;
				margin-left: 10px;
			}

			.ctn-form .campo.fone {
				background-image: url(imagens_famastil/campo-fone_famastil.jpg);
				background-repeat: no-repeat;
				width: 115px;
				height: 25px;
				float: left;
				clear: both;
				margin-top: 5px;
			}

			.ctn-form .campo.fone input {
				width: 115px;
			}

			.ctn-form .campo.cep {
				background-image: url(imagens_famastil/campo-menor_famastil.jpg);
				background-repeat: no-repeat;
				width: 87px;
				height: 25px;
				float: left;
				clear: both;
				margin-top: 5px;
			}

			.ctn-form .campo.cep input {
				width: 87px;
			}

			.ctn-form .campo.numero {
				background-image: url(imagens_famastil/campo-menor_famastil.jpg);
				background-repeat: no-repeat;
				width: 87px;
				height: 25px;
				float: left;
				clear: both;
				margin-top: 5px;
			}

			.ctn-form .campo.numero input {
				width: 87px;
			}

			.ctn-form .campo.complemento {
				background-image: url(imagens_famastil/campo-menor_famastil.jpg);
				background-repeat: no-repeat;
				width: 87px;
				height: 25px;
				float: left;
				clear: both;
				margin-top: 5px;
			}

			.ctn-form .campo.complemento input {
				width: 87px;
			}

			.ctn-form .textarea {
				background-image: url(imagens_famastil/textarea_famastil.jpg);
				background-repeat: no-repeat;
				width: 350px;
				height: 150px;
				float: left;
				clear: both;
				margin-top: 5px;
				position: relative;
				margin-left: 10px;
			}

			.ctn-form .textarea textarea {
				background-color: transparent;
				border: none;
				width: 320px;
				height: 105px;
				float: left;
				padding: 0 10px 0 10px;
				outline: none;
				margin-top: 5px;
				outline: none;
				font-family: Arial;
				resize:none;
			}

			.ctn-form .textarea input.botao {
				position: absolute;
				bottom: 10px;
				right: 20px;
			}

			#erro{color: #FFFFFF;
				  text-align:center;
				  text-shadow: 0.4em 0.4em 0.3em #000;
				  width:380px;
				  margin: 0 auto;
				  padding: 15px 0px 15px 0px;
				  border-radius: 15px;
				  -moz-border-radius: 15px;
				  background: linear-gradient(top, #990101, #FF1313); /* W3C */
				  background: -o-linear-gradient(top, #990101, #FF1313); /* OPERA */
				  background: -ms-linear-gradient(top,  #990101, #FF1313); /* IE 10 */
				  background: -moz-linear-gradient(top, #990101, #FF1313); /* FIREFOX */
				  background: -webkit-gradient(linear, left top, left bottom, from(#990101), to(#FF1313)); /* OLD CHROME AND OLD SAFARI */
				  background: -webkit-linear-gradient(top, #990101, #FF1313); /* NEW CHROME AND NEW SAFARI */
				  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#990101', endColorstr='#FF1313', GradientType=0); /* IE 9 */
				  border-style: solid;
				  border: 1px;
				  border-color: #000;
				  display: none;
			}
		</style>
		<script src="../../js/jquery-1.5.2.min.js" type="text/javascript"></script>
		<script src="../../js/jquery.maskedinput.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#fone").maskedinput("(99)9999-9999");
				$("#cep").maskedinput("99999-999");

				$("#estado").change(function () {
					if ($(this).val().length > 0) {
						buscaCidade($(this).val());
					} else {
						$("#cidade > option[rel!=default]").remove();
					}
				});
			});

			function buscaCEP(cep) {
				$.ajax({
					type: "POST",
					url:  "callcenter_cadastro_famastil.php",
					data: "cep="+escape(cep)+"&buscaCep=buscaCep",
					cache: false,
					complete: function(resposta){
						results = resposta.responseText.split(";");
						if (typeof (results[1]) != 'undefined') $('#endereco').val(results[1]);
						if (typeof (results[2]) != 'undefined') $('#bairro').val(results[2]);
						if (typeof (results[4]) != 'undefined') $('#estado').val(results[4]);

						buscaCidade(results[4], results[3]);
					}
				});
			}

			function buscaCidade (estado, cidade) {
				$.ajax({
					async: false,
					url: "callcenter_cadastro_famastil.php",
					type: "POST",
					data: { buscaCidade: true, estado: estado },
					cache: false,
					complete: function (data) {
						data = $.parseJSON(data.responseText);

						if (data.cidades) {
							$("#cidade > option[rel!=default]").remove();

							var cidades = data.cidades;

							$.each(cidades, function (key, value) {
								var option = $("<option></option>");
								$(option).attr({ value: value.cidade_pesquisa });
								$(option).text(value.cidade);

								if (cidade != undefined && value.cidade.toUpperCase() == cidade.toUpperCase()) {
								 	$(option).attr({ selected: "selected" });
								}

								$("#cidade").append(option);
							});
						} else {
							$("#cidade > option[rel!=default]").remove();
						}
					}
				});
			}

			function buscaProduto(familia) {
					if(familia != 0){
						$.ajax({
							type: "POST",
							url:  "callcenter_cadastro_famastil.php",
							data: "familia="+familia+"&buscaProduto=buscaProduto",
							success: function(resposta){
								$("#produto").html(resposta);
							}
						});
					}
				}
			buscaProduto(<?php echo $aux_familia?>);

			function validaForm(){
				var msg = "";
				var msg2 = ""
				if($("#nome").val() == ""){
					msg = "Nome.\n";
				}
				if($("#fone").val() == ""){
					msg += "Telefone.\n";
				}
				if($("#cep").val() == ""){
					msg += "Cep.\n";
				}
				if($("#endereco").val() == ""){
					msg += "Endereço.\n";
				}
				if($("#numero").val() == ""){
					msg += "Número.\n";
				}
				if($("#bairro").val() == ""){
					msg += "Bairro.\n";
				}
				if($("#cidade").val() == ""){
					msg += "Cidade.\n";
				}
				if($("#estado").val() == ""){
					msg += "Estado.\n";
				}
				if($("#assunto").val() == ""){
					msg += "Assunto.\n";
				}
				if($("#mensagem").val() == ""){
					msg += "Mensagem.\n";
				}
				if($("#familia").val() == ""){
					msg += "Familia.\n";
				}
				if($("#produto").val() == ""){
					msg += "Produto.";
				}
				if(msg != ""){
					msg2 = "Os seguintes campos não foram preenchidos.\n <Para prosseguir preencha:\n\n" + msg;
					alert(msg2);
				} else {
					if($("#assunto").val() == "Comercial"){
						document.getElementById('acao').value = 'email';
						frm_contato.submit();
					} else {
						document.getElementById('acao').value = 'submit';
						frm_contato.submit();
					}
				}
			}
		</script>
	</head>
	<body>
	<div class="ctn-form">
		<div id='erro' <? if(strlen($msg_erro)>0 or strlen($msg_erro2)>0){echo "style=display:block";} ?>>
			<?
				echo $msg_erro2;
				echo $msg_erro;
			?>
		</div>
		<form action="" method="POST" id="frm_contato" name="frm_contato">
			<input type="hidden" name="acao" id="acao">
			<label>Nome</label>
			<div class="campo nome">
				<input type="text" name="nome" id="nome" value="<?=$aux_nome?>" maxlength="50"/>
			</div>
			<label>Fone</label>
			<div class="campo fone">
				<input type="text" name="fone" id="fone" maxlength="14" value="<? echo $aux_fone ?>" maxlength="13"/>
			</div>
			<label>CEP</label>
			<div class="campo cep">
				<input type="text" name="cep" id="cep" maxlength="9" value="<? echo $aux_cep ?>" onblur="buscaCEP(this.value )"/>
			</div>
			<label>Endereço</label>
			<div class="campo endereco">
				<input type="text" name="endereco" id="endereco" value="<?=$aux_endereco?>" maxlength="60"/>
			</div>
			<label>Número</label>
			<div class="campo numero">
				<input type="text" name="numero" id="numero" value="<?=$aux_numero?>" maxlength="20"/>
			</div>
			<label>Complemento</label>
			<div class="campo complemento">
				<input type="text" name="complemento" id="complemento" maxlength="30" value="<?=$aux_complemento?>"/>
			</div>
			<label>Bairro</label>
			<div class="campo bairro">
				<input type="text" name="bairro" id="bairro" value="<?=$aux_bairro?>" maxlength="30"/>
			</div>
			<label>Estado</label>
			<div class="campo estado">
				<select name="estado" id="estado" >
					<option value='' selected> - selecione -</option>
					<?php
						foreach ($array_estado as $k => $v) {
							echo '<option value="'.$k.'"'.($aux_estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
						}
					?> 
				</select>
			</div>
			<label>Cidade</label>
			<div class="campo cidade">
				<select name="cidade" id="cidade" title="Selecione um estado para escolher uma cidade" >
					<option value='' selected rel='default' > - selecione -</option>
				</select>
			</div>
			<label>E-mail</label>
			<div class="campo email">
				<input type="text" name="email" id="email" value="<?=$aux_email?>"/>
			</div>
			<label>Assunto</label>
			<div class="campo assunto">
				<select name="assunto" id="assunto">
					<option value='' selected> - selecione -</option>
					<option value='sugestao' <?php if($aux_assunto == 'sugestao') echo " selected ";?>>Sugestão</option>
					<option value='reclamacao_at' <?php if($aux_assunto == 'reclamacao_at') echo " selected ";?>>Reclamação da Assistência Técnica</option>
					<option value='reclamacao_empresa' <?php if($aux_assunto == 'reclamacao_empresa') echo " selected ";?>>Reclamação da Empresa</option>
					<option value='reclamacao_produto' <?php if($aux_assunto == 'reclamacao_produto') echo " selected ";?>>Reclamação de Produto/Defeito</option>
					<option value='Comercial' <?php if($aux_assunto == 'comercial') echo " selected ";?>>Comercial(Venda/Compra)</option>
				</select>
			</div>
			<label>Se a dúvida for sobre produto, preencha também as opções abaixo.</label>
			<label>Familia</label>
			<div class="campo familia">
				<select name="familia" id="familia" onchange="buscaProduto(this.value )">
					<?php
						$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao ASC;";
						$res = pg_exec($con,$sql);
						if(pg_numrows($res) == 0){
							echo "<option selected> Nenhuma família encontrada</option>";
						}else{
							echo "<option value='' selected> - selecione - </option>";
							for ($i=0; $i<pg_numrows ($res); $i++ ){
								$codigo    = pg_result($res,$i,'familia');
								$descricao = pg_result($res,$i,'descricao');
								echo "<option value='$codigo' ".($aux_familia == $codigo ? ' selected="selected"' : '').">$descricao</option>";
							}
						}
					?>
				</select>
			</div>
			<label>Produto</label>
			<div class="campo produto">
				<select name="produto" id="produto">
					<option value=""></option>
				</select>
			</div>
			<label>Mensagem</label>
			<div class="textarea">
				<textarea name="mensagem" id="mensagem"><?echo $aux_mensagem?></textarea>
				<input type="image" src="http://www.famastilfpower.com.br/assets/site/img/btn-enviar.jpg" class="botao" name="enviar" id="enviar" onclick="validaForm()"/>
			</div>
		</form>
	</div>
	</body>
</html>
<?php

function makeEmail($content){
	$lines = array();
	foreach($content as $key => $value){
		if(is_array($value))
			$line = array('<font color="blue">'.$value[0].'</font>',$value[1]);
		else
			$line = array('<font color="blue">'.$key.'</font>',$value);
		$lines[] = '<td>'.implode('</td> <td>',$line).'</td>';
	}
	$table = '<tr>'.implode('</tr> <tr>',$lines).'</tr>';
	$table .='<tr><td colspan="2"><font color="red"><b>EMAIL AUTOMÁTICO FAVOR NÃO RESPONDER !</b></font></td></tr>';
	return '<table border="0" bgcolor="#CCCC66">'.$table.'</table>';
}

function getEmail($fabrica){
	global $con;
	$sql = 'SELECT email FROM tbl_admin WHERE fale_conosco AND ativo AND fabrica = $1;';
	$params = array($fabrica);
	$result = pg_query_params($con,$sql,$params);
	$fetch = pg_fetch_all($result);
	return array_map(function($element){
		return $element['email'];
	},$fetch);
}

function sendEmail($subject,$body,$address,$auth){
	$headers = array();
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'Content-Type: text/html; charset=iso=8859-1';
	$headers[] = 'From: '.$auth['user'];
	if(!is_array($address))
		$address = array($address);
	if(!mail(implode(',',$address),$subject,$body,implode("\r\n",$headers))){
		throw new Exception();
	}
	return true;
}
 
