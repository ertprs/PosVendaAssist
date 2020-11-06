<?
include "/www/assist/www/dbconfig.php";
include "/www/assist/www/includes/dbconnect-inc.php";

$fabrica = 47;

/*
$sql = "
SELECT DISTINCT tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto.cnpj, tbl_posto.cidade, tbl_posto.estado, tbl_posto.endereco, tbl_posto.bairro, tbl_posto.numero, tbl_posto.complemento, tbl_posto.fone, tbl_posto.cnpj, tbl_posto.email, tbl_posto.contato, tbl_posto.cep
	FROM tbl_posto 
	JOIN tbl_posto_fabrica using(posto)
	WHERE tbl_posto_fabrica.fabrica = 1 
	AND tbl_posto_fabrica.posto in(
		SELECT DISTINCT tbl_posto_fabrica.posto
		FROM tbl_posto 
		JOIN tbl_posto_fabrica using(posto)
		WHERE tbl_posto_fabrica.fabrica = 20
	) LIMIT 1;
";
*/

/* CROWN ENVIA CARTA CONVITE ATUAL*/
$sql = "SELECT DISTINCT tbl_posto.posto, cnpj, email, nome
			FROM tbl_posto 
			JOIN tbl_posto_fabrica USING(posto)
			WHERE fabrica in (47)
			; ";





$res = pg_exec($con,$sql);

echo "<table border='1'>";

for($i = 0; $i < pg_numrows($res); $i++){

	$email_posto  = pg_result($res, $i, email);
	$cnpj         = pg_result($res, $i, cnpj);
//	$cep          = pg_result($res, $i, cep);
	$posto        = pg_result($res, $i, posto);
	$nome_posto   = pg_result($res, $i, nome);
/*	$endereco     = pg_result($res, $i, endereco);
	$bairro       = pg_result($res, $i, bairro);
	$numero       = pg_result($res, $i, numero);
	$complemento  = pg_result($res, $i, complemento);
	$cidade       = pg_result($res, $i, cidade);
	$estado       = pg_result($res, $i, estado);
	$telefone     = pg_result($res, $i, fone);
	$contato      = pg_result($res, $i, contato);
//	$codigo_posto = pg_result($res, $i, codigo_posto);
//	$credenciamento = pg_result($res, $i, credenciamento);
//	$fabrica_nome = pg_result($res, $i, fabrica_nome);
*/

//	$email_posto = 'fernando@telecontrol.com.br'; //<------
	$mensagem = '';
	$id = $posto;
	$key = md5($fabrica);

//	set_time_limit(0);
	$nome       = "CROWN FERRAMENTAS ELÉTRICAS DO BRASIL";
	$email       = "$email_posto";
/*
	$mensagem  .= "<p align='center' style='font-size: 25px'><a href='http://www.crownferramentas.com.br'>WWW.CROWNFERRAMENTAS.COM.BR</a></p>";
	$mensagem  .= "<table align='center' border='0' cellpading='0' cellspacing='0' width='500'><tr><td><p center><img src='http://www.telecontrol.com.br/assist/credenciamento/titulo_crown.jpg'><br>";
	$mensagem  .= "<b><FONT SIZE='3' COLOR=''><p align='justify'>A CROWN FERRAMENTAS ELÉTRICAS</b></FONT> é um grupo empresarial que desenvolve o que há de melhor em ferramentas elétricas, 
					utilizando tecnologia de ponta há mais de 20 anos. Seu parque industrial conta 50 mil metros quadrados e mais de 100 milhões de dólares
					de capital fixo, tendo capacidade para produzir mais de três milhões de máquinas ao ano. Conta com mais de 20 linhas de produção e 10 
					setores de avançada inspeção de qualidade. A empresa tem hoje mais de 1500 funcionários, sendo 150 profissionais técnicos, dos quais 30
					alcançaram os maiores ranques de classificação profissional.
				</p>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "<p align='justify'>A companhia é especializada em toda linha de ferramentas elétricas seja para uso profissional ou casual. São elas: esmerilhadeiras, politrizes, 
					parafusadeiras, furadeiras, furadeiras de impacto, tupias, plainas, serras circulares, serras tico-tico e ampla linha de jardinagem. Os produtos 
					possuem os expressivos selos  de qualidade e segurança: UL, CSA, GS/CE/EMC. Para assegurar a qualidade de seus produtos a CROWN é certificada  ISO 9000. 
					O reconhecimento vem através do crescente número de vendas em países da Europa, EUA, Japão e em outros  50 países pelo mundo.</p>
				";
	$mensagem  .= "<br><br>";
	$mensagem  .= "VISANDO ALCANÇAR O MERCADO BRASILEIRO, A CROWN TRAZ VANTAGENS INÉDITAS A SUA REDE AUTORIZADA de ASSITENCIA TÉCNICA, TAIS  COMO:";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<p align='center'><b>- Taxas de mão de obra acima da média do mercado:</b>";
	$mensagem  .= "<br>até 1.000  W atts R$ <b>15,00</b>";
	$mensagem  .= "<br>acima de 1.000 até 2.000 W atts R$ <b>25,00</b>";
	$mensagem  .= "<br>acima de 2.000 W atts R$ <b>30,00</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Possibilidade de revender as FERRAMENTAS CROWN com amplo desconto.</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Descontos especiais em peças de reposição e produtos superiores aos concorrentes.</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Compatibilidade das principais peças com outros produtos do mercado.</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Excelente qualidade e disponibilidade de peças e agilidade na entrega.</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Apoio Técnico (vistas explodidas, boletins, dicas, informativos, etc).</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Sistema Gratuito de administração Garantia - TELECONTROL</b>";
	$mensagem  .= "<br><br><br>";
	$mensagem  .= "Teste e comprove você mesmo as ferramentas CROWN e veja a quão longe estas máquinas podem chegar.</p>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "<p align='center'><b><FONT SIZE='4'><a href='http://www.telecontrol.com.br/assist/credenciamento/crown/formulario.php?email=$email_posto'>Cadastre-se AQUI</a></b></FONT><br></p>";
	$mensagem  .= "</td></tr><table>";
*/

	$mensagem  .= "<img src='http://www.telecontrol.com.br/assist/credenciamento/crown/contrato_topo.jpg'>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "<table><tr><td><p align='left'>A Rede Autorizada</p><br></td></tr>";
	$mensagem  .= "<tr><td width='500'><p align='justify'>A CROWN FERRAMENTAS ELÉTRICAS DO BRASIL agradece a sua confiança depositada nesta nova marca. Juntos abriremos novas oportunidades e consolidaremos nossos
					negócios. Neste momento é hora de firmar compromisso e mostrar ao mercado as nossas forças. Atendendo aos modernos padrões de
					qualidade e exigências da grande Rede Varejista precisamos formalizar o acordo que garantirá a crescente comercialização dos nossos
					produtos e Serviços.";
	$mensagem  .= "<br><br>";
	$mensagem  .= "A CROWN FERRAMENTAS ELÉTRICAS DO BRASIL convida-o para ler o contrato de prestação de serviço. Durante sua análise, nos colocamos a disposição para sanar dúvidas
					que possam surgir. <u>Deve ser impresso em duas vias , reconhecido firma em cartório e encaminha-las através de carta registrada para o
					endereço abaixo</u>. Enviar cópia do Contrato Social da Empresa (com a última alteração, se houver). Se a pessoa que assinar o contrato não 
					constar no Contrato Social , será necessário a cópia da Procuração Pública. A CROWN FERRAMENTAS ELÉTRICAS DO BRASIL lhe devolverá umas das vias devidamente assinada. </p>";
	$mensagem  .= "<br>";
	$mensagem  .= "A CROWN FERRAMENTAS ELÉTRICAS DO BRASIL<br>
					&nbsp;Rua Nilo Peçanha, 1026/1032 - Bom Retiro<br>
					&nbsp;Curitiba - PR<br>
					&nbsp;CEP 80.520-000";
	$mensagem  .= "<br><br>";
	$mensagem  .= "<b>A CROWN FERRAMENTAS ELÉTRICAS DO BRASIL não poderá enviar peças em garantia e nem realizar pagamentos a sua empresa antes do retorno deste contrato.</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "<a href='http://www.telecontrol.com.br/assist/credenciamento/contrato/contrato_html.php?id=$id&key=$key'><u><b>Clique aqui para acessar o contrato.</b></u></a><br>";
	$mensagem  .= "<br><FONT COLOR='#9B9B9B'><b>Atenção!</b> Se você não conseguir clicar no atalho acima, acesse este endereço: www.telecontrol.com.br/assist/credenciamento/contrato/contrato_html.php?id=$id&key=$key</FONT>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "dúvidas: suporte@crownferramentas.com.br";
	$mensagem  .= "<img src='http://www.telecontrol.com.br/assist/credenciamento/crown/contrato_rodape.jpg'></td></tr></table>";
	$assunto   = "AUTO CADASTRAMENTO - A CROWN FERRAMENTAS ELÉTRICAS DO BRASIL";
	$anexos    = 0;
	$boundary = "";

	$mens = "$mensagem\n";

	$headers  = "MIME-Version: 1.0\n";
	$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
	$headers .= "From: \"A CROWN FERRAMENTAS ELÉTRICAS DO BRASIL\" <suporte@crownferramentas.com.br>\r\n";
	$headers .= "Content-type: text/html; charset=\"ISO-8859-1\"\n\n";

	$assunto   = "AUTO CADASTRAMENTO";
	$anexos    = 0;
	$boundary = "XYZ-" . date("dmYis") . "-ZYX";

//echo "$mensagem";

	//if(mail($email, $assunto, $mens, $headers)){

		echo "<tr>";
//		echo "<td nowrap>" ;
//			if(strlen($codigo_posto) > 0) echo $codigo_posto; else echo "&nbsp;";
//		echo "</td>";
		echo "<td nowrap>"; 
			if(strlen($nome_posto) > 0) echo $nome_posto; else echo "&nbsp;";
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
	//}
}

echo "</table>";
echo "47: ".md5(47);
echo "<br>25: ".md5(25);
?>