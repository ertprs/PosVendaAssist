<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";



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
			WHERE fabrica in (25)
			; ";



/*
$sql = "SELECT DISTINCT estado,cidade, nome, endereco, bairro, numero, complemento, fone, cnpj, email, contato
			FROM tbl_posto 
			JOIN tbl_posto_linha USING(posto) 
			JOIN tbl_posto_extra USING(posto)
		WHERE tbl_posto_linha.linha IN(385,372,374,335,4) 
		AND tbl_posto.posto in (select posto from tbl_posto_fabrica where fabrica = 25)";


/*
$sql = "SELECT DISTINCT cnpj, nome, fantasia, endereco, numero, complemento, cidade, estado, email, fone , contato
			FROM tbl_posto
			JOIN tbl_posto_fabrica using(posto)
		WHERE tbl_posto_fabrica.fabrica = '25' 
		AND credenciamento = 'CREDENCIADO' ;";
*/


$res = pg_exec($con,$sql);

echo "<table border='1'>";

for($i = 0; $i < pg_numrows($res); $i++){

	$email_posto  = pg_result($res, $i, email);
	$cnpj         = pg_result($res, $i, cnpj);
//	$cep          = pg_result($res, $i, cep);
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

	$email_posto = 'fernando@telecontrol.com.br'; //<------
	$mensagem = '';

//	set_time_limit(0);
	$nome       = "Ferramentas CROWN";
	$email       = "$email_posto";
	$mensagem  .= "<p align='center' style='font-size: 25px'><a href='http://www.crownferramentas.com.br'>WWW.CROWNFERRAMENTAS.COM.BR</a></p>";
	$mensagem  .= "<table align='center' border='0' cellpading='0' cellspacing='0' width='500'><tr><td><p center><img src='credenciamento/titulo_crown.jpg'><br>";
	$mensagem  .= "<b><FONT SIZE='3' COLOR=''><p align='justify'>A CROWN FERRAMENTAS EL�TRICAS</b></FONT> � um grupo empresarial que desenvolve o que h� de melhor em ferramentas el�tricas, 
					utilizando tecnologia de ponta h� mais de 20 anos. Seu parque industrial conta 50 mil metros quadrados e mais de 100 milh�es de d�lares
					de capital fixo, tendo capacidade para produzir mais de tr�s milh�es de m�quinas ao ano. Conta com mais de 20 linhas de produ��o e 10 
					setores de avan�ada inspe��o de qualidade. A empresa tem hoje mais de 1500 funcion�rios, sendo 150 profissionais t�cnicos, dos quais 30
					alcan�aram os maiores ranques de classifica��o profissional.
				</p>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "<p align='justify'>A companhia � especializada em toda linha de ferramentas el�tricas seja para uso profissional ou casual. S�o elas: esmerilhadeiras, politrizes, 
					parafusadeiras, furadeiras, furadeiras de impacto, tupias, plainas, serras circulares, serras tico-tico e ampla linha de jardinagem. Os produtos 
					possuem os expressivos selos  de qualidade e seguran�a: UL, CSA, GS/CE/EMC. Para assegurar a qualidade de seus produtos a CROWN � certificada  ISO 9000. 
					O reconhecimento vem atrav�s do crescente n�mero de vendas em pa�ses da Europa, EUA, Jap�o e em outros  50 pa�ses pelo mundo.</p>
				";
	$mensagem  .= "<br><br>";
	$mensagem  .= "VISANDO ALCAN�AR O MERCADO BRASILEIRO, A CROWN TRAZ VANTAGENS IN�DITAS A SUA REDE AUTORIZADA de ASSITENCIA T�CNICA, TAIS  COMO:";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<p align='center'><b>- Taxas de m�o de obra acima da m�dia do mercado:</b>";
	$mensagem  .= "<br>at� 1.000  W atts R$ <b>15,00</b>";
	$mensagem  .= "<br>acima de 1.000 at� 2.000 W atts R$ <b>25,00</b>";
	$mensagem  .= "<br>acima de 2.000 W atts R$ <b>30,00</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Possibilidade de revender as FERRAMENTAS CROWN com amplo desconto.</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Descontos especiais em pe�as de reposi��o e produtos superiores aos concorrentes.</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Compatibilidade das principais pe�as com outros produtos do mercado.</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Excelente qualidade e disponibilidade de pe�as e agilidade na entrega.</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Apoio T�cnico (vistas explodidas, boletins, dicas, informativos, etc).</b>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "&nbsp;&nbsp;<b>- Sistema Gratuito de administra��o Garantia - TELECONTROL</b>";
	$mensagem  .= "<br><br><br>";
	$mensagem  .= "Teste e comprove voc� mesmo as ferramentas CROWN e veja a qu�o longe estas m�quinas podem chegar.</p>";
	$mensagem  .= "<br><br>";
	$mensagem  .= "<p align='center'><b><FONT SIZE='4'><a href='http://posvenda.telecontrol.com.br/assist/credenciamento/crown/formulario.php?email=$email_posto'>Cadastre-se AQUI</a></b></FONT><br></p>";
	$mensagem  .= "</td></tr><table>";
	$assunto   = "AUTO CADASTRAMENTO";
	$anexos    = 0;
	$boundary = "XYZ-" . date("dmYis") . "-ZYX";

	$mens  = "--$boundary\n";
	$mens .= "Content-Transfer-Encoding: 8bits\n";
	$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
	$mens .= "$mensagem\n";
	$mens .= "--$boundary\n";

	$headers  = "MIME-Version: 1.0\n";
	$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
	$headers .= "From: \"Crown Ferramentas\" <suporte@crownferramentas.com.br>\r\n";
	$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";

echo "$mensagem";

//	if(mail($email, $assunto, $mens, $headers)){

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

?>