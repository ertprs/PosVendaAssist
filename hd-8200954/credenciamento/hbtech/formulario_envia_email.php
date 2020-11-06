<?
include "/www/assist/www/dbconfig.php";
include "/www/assist/www/includes/dbconnect-inc.php";

$fabrica = 25;


/*
$sql = "SELECT DISTINCT tbl_posto.nome, tbl_posto.cnpj, tbl_posto.cidade, tbl_posto.estado, tbl_posto.endereco, tbl_posto.bairro, tbl_posto.numero, tbl_posto.complemento, tbl_posto.fone, tbl_posto.cnpj, tbl_posto.email, tbl_posto.contato, tbl_posto.cep
	FROM tbl_posto 
	JOIN tbl_posto_fabrica using(posto)
	JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica AND tbl_posto_fabrica.fabrica = 1
	WHERE tbl_posto_fabrica.fabrica = 1
	AND upper(tbl_posto.estado) in('PR','RS','SP','SC','CE');
";
*/

/*
$sql = "SELECT DISTINCT tbl_posto.nome, tbl_posto.cnpj, tbl_posto.cidade, tbl_posto.estado, tbl_posto.endereco, tbl_posto.bairro, tbl_posto.numero, tbl_posto.complemento, tbl_posto.fone, tbl_posto.cnpj, tbl_posto.email, tbl_posto.contato, tbl_posto.cep
			FROM tbl_posto
			where posto in(
			select posto from tbl_posto_linha where linha in(335,374,468)
		);
";
*/

/*
$sql = "SELECT DISTINCT tbl_posto.posto, cnpj, email, nome
			FROM tbl_posto 
			JOIN tbl_posto_fabrica USING(posto)
			WHERE tbl_posto_fabrica.fabrica in (25)
			AND estado <> 'SP'
		; ";
*/

$sql = "select  tbl_posto.posto     ,
				tbl_posto.nome      ,
				tbl_posto.cnpj      ,
				tbl_posto.email     ,
				tbl_posto.cidade    ,
				tbl_posto.estado    ,
				tbl_posto.fone      ,
				tbl_posto.contato   ,
				tbl_tipo_posto.descricao,
				tbl_posto_fabrica.credenciamento
			FROM tbl_posto
			JOIN tbl_posto_fabrica using(posto)
			JOIN tbl_tipo_posto using(tipo_posto)
			WHERE tbl_posto_fabrica.fabrica = 25
			AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OFFSET 100;";

$res = pg_exec($con,$sql);

echo "<table border='1'>";

for($i = 0; $i < pg_numrows($res); $i++){

	$email_posto  = pg_result($res, $i, email);
	$cnpj         = pg_result($res, $i, cnpj);
	$posto        = pg_result($res, $i, posto);
//	$cep          = pg_result($res, $i, cep);
	$nome_posto   = pg_result($res, $i, nome);
//	$endereco     = pg_result($res, $i, endereco);
//	$bairro       = pg_result($res, $i, bairro);
//	$numero       = pg_result($res, $i, numero);
//	$complemento  = pg_result($res, $i, complemento);
	$cidade       = pg_result($res, $i, cidade);
	$estado       = pg_result($res, $i, estado);
	$telefone     = pg_result($res, $i, fone);
	$contato      = pg_result($res, $i, contato);
	$tipo_posto   = pg_result($res, $i, descricao);
	$credenciamento  = pg_result($res, $i, credenciamento);
//	$codigo_posto = pg_result($res, $i, codigo_posto);
//	$credenciamento = pg_result($res, $i, credenciamento);
//	$fabrica_nome = pg_result($res, $i, fabrica_nome);

//	$posto       = "6359";
//	$email_posto = 'fernando@telecontrol.com.br'; //<------
	$mensagem    = '';
	$id = $posto;
	$key = md5($fabrica);



//	set_time_limit(0);
	$nome       = "HBFLEX";
	$email       = "$email_posto";
/*
//	$mensagem  .= "<img src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/superior.jpg'><br>";
	$mensagem  .= "<table border='0' style='background-repeat: no-repeat;' background='http://www.telecontrol.com.br/assist/credenciamento/hbtech/superior.jpg'>";
	$mensagem  .= "<tr><td width='650' style='background-repeat: no-repeat; background-position: 140px 300px;' background='http://www.telecontrol.com.br/assist/credenciamento/hbtech/marca.jpg'><p align='justify' style='font-family: arial; font-size: 16px;'>
					<br><br><br><br><br><br><br><br>
					<b>Prezado Assistente Técnico 
					<br><br><br>
					Seja bem vindo!
					<br><br><br>
					Nós da <b>HBFLEX</b> temos o orgulho de contarmos com a parceria e competência de sua empresa para acolher nossos clientes. Os primeiros lotes de produtos estão chegando ao mercado. Agora precisamos ampara-los prestando bons serviços de Assistência Técnica.
					<br><br>
					Neste momento encaminhamos um breve manual para o usuário do Sistema <b>ASSIST TELECONTROL</b>. Nele você poderá fazer o 'primeiro acesso' cadastrando <b>Código</b> e <b>Senha</b> bem como abrir Ordens de Serviço, realizar pedidos de peças em garantia, comprar peças entre outros recursos.
					<br><br>
					Para melhor atender a Rede Autorizada e os nossos clientes tomamos a iniciativa de TROCAR os primeiros produtos. Com isto conseguiremos maior credibilidade e confiança  neste início de operação. As taxas de mão de obra serão pagas integralmente neste período. Os procedimentos estão explicados nesta primeira remessa de informações.
					<br><br>
					Agradecemos mais uma vez sua parceria e asseguraremos que não pouparemos esforços para que nossos trabalhos transcorram de forma profissional, transparente e rentável para sua operação.
					<br><br><br>
					à Direção HBFLEX</b>";
	$mensagem  .= "</td>";
	$mensagem  .= "<td align='center' valign='center'>&nbsp;<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><img src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/fotos.jpg'></td></tr>";
	$mensagem  .= "<br>";
	$mensagem  .= "<tr><td colspan='2' style='background-position: bottom ; background-repeat: no-repeat;' background='http://www.telecontrol.com.br/assist/credenciamento/hbtech/inferior.jpg'>";
	$mensagem  .= "<p align='justify' style='font-family: arial; font-size: 18px;'>
					<br><br>
					<center><b><FONT SIZE='2' COLOR='#FF0000'>Primeiro acesso, clique <a href='http://www.telecontrol.com.br/assist/credenciamento/hbtech/Primeiro_Acesso.pdf'><b>aqui.</FONT></b></b></a></center>
					<br><center><b><FONT SIZE='2' COLOR='#FF0000'>Manual do Telecontrol, clique <a href='http://www.telecontrol.com.br/assist/credenciamento/hbtech/Manual_Telecontrol.pdf'><b>aqui.</b></FONT></a></p></center>
					<br><br><br><br><br><br><br><br><br>";
	$mensagem  .= "</td></tr></table>";
*/


/*
	$mensagem  .= "<table border='0' style='background-repeat: no-repeat;' background='http://www.telecontrol.com.br/assist/credenciamento/hbtech/superior.jpg'>";
	$mensagem  .= "<tr><td width='650'><p align='justify'><br><br><br><br><br><br><br><br><br><br><br><br><br>Prezado Assistente Técnico<br>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "Esta mensagem deve ser  lida pelo proprietário ou pelo responsável da empresa";
	$mensagem  .= "<br>";
	$mensagem  .= "<p align='justify'>A HBFLEX está ampliando suas linhas de produtos. Além dos MP3 e MP4 aproximam-se novos lançamentos:
					Coifas, Adegas, Ar Condicionado, Aparelhos de Áudio e Vídeo, Lavadora / Secadora de Roupas. Estamos
					ampliando nossa Rede Autorizada bem como reafirmando nossos compromissos.</p>";
	$mensagem  .= "<br>";
	$mensagem  .= "Através do link abaixo gostaríamos de confirmar seus dados cadastrais e as especialidades da sua empresa:<br>";
	$mensagem  .= "<br>";
	$mensagem  .= "<center><b><a href='http://www.telecontrol.com.br/assist/credenciamento/hbtech/index.php?id=$id&key=$key' target='BLANK_'>Clique aqui para acessar o formulário</a></b><br>";
	$mensagem  .= "<FONT COLOR='#9B9B9B'><b>Atenção!</b> Se você não conseguir clicar no atalho acima, acesse este endereço: www.telecontrol.com.br/assist/credenciamento/hbtech/index.php?id=$id&key=$key</FONT></center><br>";
	$mensagem  .= "<p align='justify'>Para formalizar nossa parceria precisamos que leia o contrato em anexo e assine em duas vias. 
					Deverá ser carimbado com o CNPJ e enviado para Avenida Marques de São Vicente,121 Bloco B - Salas 401 - CEP 01.139.001 São Paulo - SP.</p>";
	$mensagem  .= "<br>";
	$mensagem  .= "<center><a href='http://www.telecontrol.com.br/assist/credenciamento/contrato/contrato_html.php?id=$id&key=$key'><u><b>Clique aqui para acessar o contrato.</b></u></a>";
	$mensagem  .= "<br><FONT COLOR='#9B9B9B'><b>Atenção!</b> Se você não conseguir clicar no atalho acima, acesse este endereço: www.telecontrol.com.br/assist/credenciamento/contrato/contrato_html.php?id=$id&key=$key</FONT></center>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "<b>É de suma importância que avise seus funcionários sobre a chegada dos Produtos HBFLEX.</b>";
	$mensagem  .= "<br>";
	$mensagem  .= "<p align='justify'>Periodicamente enviaremos notícias, informativos, comunicados para que sejam divulgados entre os colaboradores e técnicos da sua empresa.</p>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "<center><b><u><a href='http://www.hbflex.com' target='BLANK_'>EQUIPE HBFLEX</a></u></b></center>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "dúvidas: suporte@hbflex.com";
	$mensagem  .= "<br>";
	$mensagem  .= "<tr><td colspan='2' style='background-position: bottom ; background-repeat: no-repeat;' background='http://www.telecontrol.com.br/assist/credenciamento/hbtech/inferior.jpg'>";
	$mensagem  .= "<p align='justify' style='font-family: arial; font-size: 18px;'>
					<br><br><br><br><br><br><br>";
	$mensagem  .= "</td></tr></table>";
*/


#2008-07-30

	$mensagem  .= "<table border='0' width='500'>";
	$mensagem  .= "<tr><td><img src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/cabecalho_hbtech.gif'></td></tr>";
	$mensagem  .= "<tr><td>";
	$mensagem  .= "<br>";
	$mensagem  .= "<p align='center' style='font-size: 25px; color: #000099;'>Prezado Assistente Técnico<br><br>
					Estamos na fase final de cadastramento nas linhas que trabalhamos. Para reconhecer seu esforço nesta parceria vamos sortear vários brindes para os 100 primeiros que enviarem o TERMO DE ADESÃO.<br>Participe !!!</p>";
//	$mensagem  .= "<br>";
	$mensagem  .= "</td></tr>";

	$mensagem  .= "<tr><td align='center'><br><br><img src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/produtos.gif'></td></tr>";
	$mensagem  .= "<tr><td><p align='justify'>";
	$mensagem  .= "<br><center><a href='http://www.telecontrol.com.br/assist/credenciamento/contrato/download_contrato2.php?id=$id&key=$key'><FONT SIZE='6'>click aqui p/ download</FONT></a></center><br>";
	$mensagem  .= "<p align='center' style='font-size: 25px'>Preencha o Termo de Adesão / Taxas de Mão de Obra e envie para:<br><br>
					Telecontrol Networking Ltda.<br>
					Avenida Carlos Artêncio, 420 B<br>
					Cep 17.519-255<br>
					Marília-SP</p>";
	$mensagem  .= "<br>";
	$mensagem  .= "<tr><td colspan='2'><img src='http://www.telecontrol.com.br/assist/credenciamento/hbtech/rodape_hbtech.gif'>";
	$mensagem  .= "</td></tr></table>";


	$assunto   = "AUTO CADASTRAMENTO - HBFLEX";
	$anexos    = 0;
	$boundary = "";

	$mens = "$mensagem\n";

	$headers  = "MIME-Version: 1.0\n";
	$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
	$headers .= "From: \"HBFLEX\" <suporte@hbflex.com>\r\n";
	$headers .= "Content-type: text/html; charset=\"ISO-8859-1\"\n\n";

//echo "$mensagem";

	//if(mail($email, $assunto, $mens, $headers)){

		echo "<tr>";
//		echo "<td nowrap>" ;
//			if(strlen($codigo_posto) > 0) echo $codigo_posto; else echo "&nbsp;";
//		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($credenciamento) > 0) echo $credenciamento; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($cnpj) > 0) echo $cnpj; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($nome_posto) > 0) echo $nome_posto; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($tipo_posto) > 0) echo $tipo_posto; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($endereco) > 0) echo $endereco; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($numero) > 0) echo $bairro; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($numero) > 0) echo $numero; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($complemento) > 0) echo $complemento; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($cidade) > 0) echo $cidade; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($estado) > 0) echo $estado; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>";
			if(strlen($cep) > 0) echo $cep; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>";
			if(strlen($telefone) > 0) echo $telefone; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>";
			if(strlen($email_posto) > 0) echo $email_posto; else echo "&nbsp;";
		echo "</td>";
		echo "<td nowrap>";
			if(strlen($contato) > 0) echo $contato; else echo "&nbsp;";
		echo "</td>";
		echo "</tr>";
	flush();
	sleep(3);
	//}
	flush();
}

echo "</table>";

?>