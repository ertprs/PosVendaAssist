<?php
/**
 *
 * importa-peca.php
 *
 * Importação de peças Atlas
 *
 * @author  Ronald Santos
 * @version 2012.04.17
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'teste');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	$fabrica = 74;
	$fabrica_nome = 'atlas';

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	$sql = "SELECT * FROM tmp_atlas_hd_antigo where nada isnull  ";
	$res = pg_query($con,$sql);

	for($i=0;$i<pg_num_rows($res);$i++){
		$data = pg_fetch_result($res,$i,'data');
		$cnpj_cpf = pg_fetch_result($res,$i,'cnpj_cpf');
		$nome = pg_fetch_result($res,$i,'nome');
		$fone1 = pg_fetch_result($res,$i,'fone1');
		$fone2 = pg_fetch_result($res,$i,'fone2');
		$email = pg_fetch_result($res,$i,'email');
		$cidade = pg_fetch_result($res,$i,'cidade');
		$estado = pg_fetch_result($res,$i,'estado');
		$endereco = pg_fetch_result($res,$i,'endereco');
		$complemento = pg_fetch_result($res,$i,'complemento');
		$numero = pg_fetch_result($res,$i,'numero');
		$bairro = pg_fetch_result($res,$i,'bairro');
		$cep = pg_fetch_result($res,$i,'cep');
		$agente = utf8_decode(pg_fetch_result($res,$i,'agente'));
		$ticket = pg_fetch_result($res,$i,'ticket');
		$descricao = utf8_decode(pg_fetch_result($res,$i,'descricao'));
		$modelo_equipamento = utf8_decode(pg_fetch_result($res,$i,'modelo_equipamento'));
		$serie_equipamento = pg_fetch_result($res,$i,'serie_equipamento');
		$data_fabricacao = pg_fetch_result($res,$i,'data_fabricacao');
		$sintomas = pg_fetch_result($res,$i,'sintomas');
		$questionario = utf8_decode(pg_fetch_result($res,$i,'questionario'));
		$cid = pg_fetch_result($res,$i,'cid');

		if(empty($cid)){
			$cid = null;
		}

		$atendente = 3606;
		$resi = pg_query($con,"BEGIN TRANSACTION");
		$sqli = "INSERT INTO tbl_hd_chamado(
					admin,
					data,
					titulo,
					status,
					atendente,
					categoria,
					fabrica_responsavel,
					fabrica
				)values(
					$atendente,
					'$data',
					'Atendimento DBM',
					'Resolvido',
					$atendente,
					'$sintomas',
					$fabrica,
					$fabrica
				) RETURNING hd_chamado";
		$resi = pg_query($con,$sqli);
		$hd_chamado =  pg_fetch_result($resi,0,0);
		$msg_erro = pg_last_error($con);

		$reclamado = str_replace("'","","Atendente: $agente\nTicket: $ticket\nProduto: $modelo_equipamento\nSerie: $serie_equipamento\nData Fabricação: $data_fabricacao\nProblema: $questionario\nDescrição do Atendimento: $descricao");

		$sql = "INSERT INTO tbl_hd_chamado_extra (
					hd_chamado,
					reclamado,
					origem,
					nome,
					endereco,
					numero,
					complemento,
					bairro,
					cep,
					fone,
					fone2,
					email,
					cpf,
					cidade) values(
						$hd_chamado,
						'$reclamado',
						'Telefone',
						'$nome',
						'$endereco',
						'$numero',
						'$complemento',
						'$bairro',
						'$cep',
						'$fone',
						'$fone2',
						'$email',
						'$cnpj_cpf',
						$cid
					)";
		$resi = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);

		if(empty($msg_erro)) {
			$resi = @pg_query ($con,"COMMIT TRANSACTION");
		}else{
			$resi = @pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}

	$phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}

