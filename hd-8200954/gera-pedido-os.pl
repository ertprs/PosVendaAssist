#!/usr/bin/perl 
#
# Telecontrol Networking
# www.telecontrol.com.br
# Exportacao de pedidos de pecas com base na OS
#
#print "Content-Type: text/html\n\n";

require "/www/cgi-bin/dados_tc.cfg";

$fabrica  = "3" ;
$arquivos = "/tmp";

use Pg;
use File::Copy;

system ("mkdir $arquivos/britania 2> /dev/null ; chmod 777 $arquivos/britania" );
open (ARQ_ERRO , ">" , "$arquivos/britania/gera_pedido_os.err");

$conn = Pg::connectdb("host=$dbhost dbname=$dbnome user=$dbusuario password=$dbsenha");
die $conn->errorMessage unless PGRES_CONNECTION_OK eq $conn->status;

$sql = "SET DateStyle TO 'SQL,EUROPEAN'";
$result = $conn-> exec ($sql);

if (length ($conn->errorMessage) > 0) {
	print ARQ_ERRO $sql;
	print ARQ_ERRO "\n";
	print ARQ_ERRO $conn->errorMessage;
	print ARQ_ERRO "\n\n\n";
}



$sql = "SELECT  DISTINCT
				tbl_posto.posto   ,
				tbl_produto.linha
		FROM    tbl_os_item
		JOIN    tbl_servico_realizado USING (servico_realizado)
		JOIN    tbl_os_produto USING (os_produto)
		JOIN    tbl_os         USING (os)
		JOIN    tbl_posto      USING (posto)
		JOIN    tbl_produto          ON tbl_os.produto            = tbl_produto.produto
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
		LEFT JOIN tbl_os_troca       ON tbl_os.os = tbl_os_troca.os
		WHERE   tbl_os_item.pedido IS NULL
		AND     tbl_os.excluida    IS NOT TRUE
		AND     tbl_os.validada    IS NOT NULL
		AND     (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido)
		AND     tbl_os.fabrica      = $fabrica
		AND     tbl_posto.posto     <> 6359
		AND     tbl_os_troca.os    IS NULL
		AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO' ) 
		AND     (( (SELECT status_os FROM tbl_os_status 
		              WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,72,73,116,117)
		              ORDER BY data DESC LIMIT 1) NOT IN (62,65,72,116) ) 
		            OR      (SELECT status_os FROM tbl_os_status 
		              WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,72,73,116,117)
		              ORDER BY data DESC LIMIT 1) IS NULL)";
$result = $conn-> exec ($sql);

if (length ($conn->errorMessage) > 0) {
	print ARQ_ERRO $sql;
	print ARQ_ERRO "\n";
	print ARQ_ERRO $conn->errorMessage;
	print ARQ_ERRO "\n\n\n";
}

while (($posto,$linha) = $result->fetchrow) {
	$erro = " ";
	$sql = "BEGIN TRANSACTION";
	$resultX = $conn-> exec ($sql);

	$sql = "SELECT  tbl_os_item.peca,
					tbl_servico_realizado.troca_produto,
					SUM (tbl_os_item.qtde) AS qtde
			FROM      tbl_os_item
			JOIN      tbl_servico_realizado USING (servico_realizado)
			JOIN      tbl_os_produto USING (os_produto)
			JOIN      tbl_os         USING (os)
			JOIN      tbl_posto      USING (posto)
			JOIN      tbl_produto          ON tbl_os.produto            = tbl_produto.produto
			JOIN      tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			LEFT JOIN tbl_os_troca       ON tbl_os.os = tbl_os_troca.os
			WHERE   tbl_os_item.pedido IS NULL
			AND     tbl_os.validada    IS NOT NULL
			AND     tbl_os.excluida    IS NOT TRUE
			AND     tbl_os.fabrica    = $fabrica
			AND     tbl_os.posto      = $posto
			AND     tbl_produto.linha = $linha
			AND     tbl_os_troca.os    IS NULL
			AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO' ) 
			AND     (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido)
			AND     (( (SELECT status_os FROM tbl_os_status 
				    WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,72,73,116,117)
				    ORDER BY data DESC LIMIT 1) NOT IN (62,65,72,116) ) 
				OR (SELECT status_os FROM tbl_os_status
				    WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,72,73,116,117)
				    ORDER BY data DESC LIMIT 1) IS NULL)
			GROUP BY    tbl_os.posto      ,
				    tbl_produto.linha ,
				    tbl_os_item.peca  ,
				    tbl_servico_realizado.troca_produto";
	$result2 = $conn-> exec ($sql);
	
	if (length ($conn->errorMessage) > 0) {
		print ARQ_ERRO $sql;
		print ARQ_ERRO "\n";
		print ARQ_ERRO $conn->errorMessage;
		print ARQ_ERRO "\n\n\n";
		print ARQ_ERRO "\n Sql 1\n";
		$erro = "*";
	}

	$sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE fabrica = $fabrica AND posto = $posto";
	$resultX = $conn-> exec ($sql) ;
	($tipo_posto) = $resultX->fetchrow;
	$tipo_posto = $tipo_posto;
	
	$condicao = "7";
	
	$sql = "INSERT INTO tbl_pedido (
				posto     ,
				fabrica   ,
				linha     ,
				condicao  ,
				tipo_pedido
			) VALUES (
				$posto    ,
				$fabrica  ,
				$linha    ,
				$condicao ,
				'3'
			);";
	$resultX = $conn-> exec ($sql);

	if (length ($conn->errorMessage) > 0) {
		print ARQ_ERRO $sql;
		print ARQ_ERRO "\n";
		print ARQ_ERRO $conn->errorMessage;
		print ARQ_ERRO "\n\n\n";
		print ARQ_ERRO "\n Insert 1\n";
		$erro = "*";
	}

	$sql = "SELECT currval ('seq_pedido')";
	$resultX = $conn-> exec ($sql);

	if (length ($conn->errorMessage) > 0) {
		print ARQ_ERRO $sql;
		print ARQ_ERRO "\n";
		print ARQ_ERRO $conn->errorMessage;
		print ARQ_ERRO "\n\n\n";
		print ARQ_ERRO "\n Sql 2\n";
		$erro = "*";
	}
	
	($pedido) = $resultX->fetchrow;

	while (($peca,$troca_produto, $qtde) = $result2->fetchrow) {
		$sql = "INSERT INTO tbl_pedido_item (
					pedido,
					peca  ,
					qtde  ,
					qtde_faturada,
					qtde_cancelada,
					troca_produto
				) VALUES (
					$pedido,
					$peca  ,
					$qtde  ,
					0      ,
					0      ,
					'$troca_produto')";
		$resultX = $conn-> exec ($sql);

		if (length ($conn->errorMessage) > 0) {
			print ARQ_ERRO $sql;
			print ARQ_ERRO "\n";
			print ARQ_ERRO $conn->errorMessage;
			print ARQ_ERRO "\n\n\n";
			print ARQ_ERRO "\n Insert 2\n";
			$erro = "*";
		}
	}

	$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
	$resultX = $conn-> exec ($sql);
	
	if (length ($conn->errorMessage) > 0) {
		print ARQ_ERRO $sql;
		print ARQ_ERRO "\n";
		print ARQ_ERRO $conn->errorMessage;
		print ARQ_ERRO "\n\n\n";
		print ARQ_ERRO "\n Função 1\n$sql\n";
		$erro = "*";
	}

	$sql = "UPDATE tbl_os_item SET pedido = $pedido
			FROM tbl_os
			JOIN tbl_os_produto USING (os)
			LEFT JOIN tbl_os_troca USING (os)
			WHERE tbl_os_item.os_produto        = tbl_os_produto.os_produto
			AND   tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			AND   tbl_os_produto.os             = tbl_os.os
			AND   tbl_os.produto                = tbl_produto.produto
			AND   tbl_produto.linha = $linha
			AND   tbl_os.posto      = $posto
			AND   tbl_os.fabrica    = $fabrica
			AND   (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido)
			AND   tbl_os.validada IS NOT NULL
			AND   tbl_os.excluida    IS NOT TRUE
			AND   tbl_os_item.pedido IS NULL
			AND     (( (SELECT status_os FROM tbl_os_status 
			WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,72,73,116,117)
			ORDER BY data DESC LIMIT 1) NOT IN (62,65,72,116) ) 
			OR (SELECT status_os FROM tbl_os_status 
			WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,72,73,116,117)
			ORDER BY data DESC LIMIT 1) IS NULL)";

	$sql = "SELECT fn_atualiza_os_item_pedido(tbl_os_item.os_item, $pedido, $fabrica)
			FROM tbl_os_item 
			JOIN tbl_os_produto USING (os_produto)
			JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
			JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			JOIN tbl_produto ON tbl_os.produto                = tbl_produto.produto
			LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
			WHERE tbl_produto.linha = $linha
			AND   tbl_os.posto      = $posto
			AND   tbl_os.fabrica    = $fabrica
			AND   (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido)
			AND   tbl_os.validada IS NOT NULL
			AND   tbl_os.excluida    IS NOT TRUE
			AND   tbl_os_item.pedido IS NULL
			AND     (( (SELECT status_os FROM tbl_os_status 
			WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,72,73,116,117)
			ORDER BY data DESC LIMIT 1) NOT IN (62,65,72,116) ) 
			OR (SELECT status_os FROM tbl_os_status 
			WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,72,73,116,117)
			ORDER BY data DESC LIMIT 1) IS NULL)";
	$resultX = $conn-> exec ($sql);

	
	if (length ($conn->errorMessage) > 0) {
		print ARQ_ERRO $sql;
		print ARQ_ERRO "\n";
		print ARQ_ERRO $conn->errorMessage;
		print ARQ_ERRO "\n\n\n";
		print ARQ_ERRO "\n Update 1\n";
		$erro = "*";
	}

	if ($erro eq "*") {
	    $sql = "ROLLBACK TRANSACTION";
	    $resultX = $conn-> exec ($sql);
	}else{
	    $sql = "COMMIT TRANSACTION";
		$resultX = $conn-> exec ($sql);
	}
}

close (ARQ_ERRO);

#-------------- Envia arquivo de erros para BRITANIA -------------

if ( -s "$arquivos/britania/gera_pedido_os.err" ) {
	open (EMAIL,">","$arquivos/britania/email_erro_gera_os.txt");
	print EMAIL "MIME-Version: 1.0\n";
	print EMAIL "Content-type: text/html; charset=iso-8859-1\n";
	print EMAIL "From: Telecontrol <telecontrol\@telecontrol.com.br>\n";
	print EMAIL "To: suporte\@telecontrol.com.br , sistemas\@britania.com.br , fabricio.bortoleto\@britania.com.br , ricardo.cividanes\@britania.com.br, edilaine.siqueira\@britania.com.br \n";
	print EMAIL "Subject: Telecontrol - Britania -> Erros ao criar Pedidos com base nas OS\n";
	print EMAIL "<font face='arial' color='#000000' size='2'>\n";
	print EMAIL "Alguns pedidos não foram criados a partir de suas OS, e serão gerados automaticamente assim que os problemas fores solucionados.\n";
	print EMAIL "<br><br>\n";
	print EMAIL "<b>Verifique tabelas de preços, cadastro de peças, etc.</b>\n";
	print EMAIL "<br>\n";
	print EMAIL "</font>\n";
	close (EMAIL);
	system ("cat $arquivos/britania/gera_pedido_os.err | grep \"ERRO\" >> /tmp/britania/email_erro_gera_os.txt ; cat /tmp/britania/email_erro_gera_os.txt | qmail-inject");


	open (EMAIL,">","$arquivos/britania/email_erro_gera_os.txt");
	print EMAIL "MIME-Version: 1.0\n";
	print EMAIL "Content-type: text/html; charset=iso-8859-1\n";
	print EMAIL "From: Telecontrol <telecontrol\@telecontrol.com.br>\n";
	print EMAIL "To: suporte\@telecontrol.com.br \n";
	print EMAIL "Subject: Telecontrol - Britania -> Erros ao criar Pedidos com base nas OS\n";
	print EMAIL "<font face='arial' color='#000000' size='2'>\n";
	print EMAIL "Alguns pedidos não foram criados a partir de suas OS, e serão gerados automaticamente assim que os problemas fores solucionados.\n";
	print EMAIL "<br><br>\n";
	print EMAIL "<b>Verifique tabelas de preços, cadastro de peças, etc.</b>\n";
	print EMAIL "<br>\n";
	print EMAIL "</font>\n";
	close (EMAIL);
	system ("cat $arquivos/britania/gera_pedido_os.err >> /tmp/britania/email_erro_gera_os.txt ; cat /tmp/britania/email_erro_gera_os.txt | qmail-inject");
}

exit 0;
