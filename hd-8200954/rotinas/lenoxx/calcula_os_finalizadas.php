<?php
error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim
#define('ENV','dev');
try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $fabricas = array(
        11 => "lenoxx",
        172 => "pacific",
    );

    if (array_key_exists(1, $argv)) {
        $fabrica = $argv[1];
    } else {
        $fabrica = 11;
    }

    if (!array_key_exists($fabrica, $fabricas)) {
        die("ERRO: argumento inválido - " . $fabrica . "\n");
    }

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $sql = "SELECT tbl_os.os,
    tbl_produto.mao_de_obra,
    tbl_produto.familia,
    tbl_produto.produto,
    tbl_produto.linha,
    tbl_os.posto
    FROM tbl_os
    JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.extrato IS NULL
    JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
    WHERE tbl_os.fabrica = $fabrica
    AND tbl_os.finalizada >= CURRENT_DATE - INTERVAL '3 days'
    AND tbl_os.excluida IS NOT TRUE
    AND tbl_os.mao_de_obra IS NULL";
    $resOS = pg_query($con,$sql);

    if(pg_num_rows($resOS) > 0){

		for($i = 0; $i < pg_num_rows($resOS); $i++){

			$res = pg_query($con, 'BEGIN TRANSACTION');

			$posto = pg_fetch_result($resOS,$i,'posto');
			$mao_de_obra = pg_fetch_result($resOS,$i,'mao_de_obra');
			$familia = pg_fetch_result($resOS,$i,'familia');
			$linha = pg_fetch_result($resOS,$i,'linha');
			$produto = pg_fetch_result($resOS,$i,'produto');
			$os = pg_fetch_result($resOS,$i,'os');

			$sql = "UPDATE tbl_os SET mao_de_obra = '$mao_de_obra' WHERE fabrica = $fabrica AND os = $os";
			$res = pg_query($con,$sql);

			if ($posto == 14270){

				$sql = "UPDATE tbl_os
					SET mao_de_obra = mao_de_obra / 2
					WHERE tbl_os.os = $os
					AND tbl_os.revenda IN (SELECT revenda FROM tbl_revenda WHERE nome ILIKE '%AULIK%')";
				$res = pg_query($con,$sql);
			}

			$sql = "UPDATE tbl_os SET mao_de_obra = mao_de_obra / 2
				FROM tbl_os_troca
				WHERE tbl_os.os = tbl_os_troca.os
				AND tbl_os.os = $os
				AND tbl_os_troca.ressarcimento IS TRUE
				AND tbl_os_troca.fabric = $fabrica";
			$res = pg_query($con,$sql);

			if (in_array($familia,array(537,540))){

				$sql = "SELECT status_os FROM tbl_os_status WHERE os = $os AND fabrica_status = $fabrica AND status_os = 65";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0){
					$sql = "UPDATE tbl_os SET mao_de_obra = mao_de_obra / 2 WHERE os = $os";
					$res = pg_query($con,$sql);
				}

			}

			$sql = "UPDATE tbl_os SET mao_de_obra = 0
				FROM tbl_os_extra
				WHERE tbl_os.os = $os
				AND tbl_os_extra.os = tbl_os.os
				AND tbl_os.fabrica = $fabrica
				AND tbl_os.data_digitacao > '2006-11-01 00:00:00'
				AND tbl_os.admin IS NOT NULL
				AND tbl_os_extra.admin_paga_mao_de_obra IS NOT TRUE";
			$res = pg_query($con,$sql);

			$sql = "UPDATE tbl_os SET
				mao_de_obra = x.mao_de_obra
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.linha IS NULL
					AND tbl_excecao_mobra.produto IS NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);


			$sql = "UPDATE tbl_os SET
				mao_de_obra = mao_de_obra + x.adicional_mao_de_obra
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.adicional_mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.adicional_mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.linha IS NULL
					AND tbl_excecao_mobra.produto IS NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);



			$sql = "UPDATE tbl_os SET
				mao_de_obra = mao_de_obra * (1 + (x.percentual_mao_de_obra / 100))
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.percentual_mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.percentual_mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.linha IS NULL
					AND tbl_excecao_mobra.produto IS NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);




			$sql = "UPDATE tbl_os SET
				mao_de_obra = x.mao_de_obra
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_excecao_mobra.linha = $linha
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.linha IS NOT NULL
					AND tbl_excecao_mobra.produto IS NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);




			$sql = "UPDATE tbl_os SET
				mao_de_obra = mao_de_obra + x.adicional_mao_de_obra
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.adicional_mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_excecao_mobra.linha = $linha
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.adicional_mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.linha IS NOT NULL
					AND tbl_excecao_mobra.produto IS NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);




			$sql = "UPDATE tbl_os SET
				mao_de_obra = mao_de_obra * (1 + (x.percentual_mao_de_obra / 100))
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.percentual_mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_excecao_mobra.linha = $linha
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.percentual_mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.linha IS NOT NULL
					AND tbl_excecao_mobra.produto IS NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);




			$sql = "UPDATE tbl_os SET
				mao_de_obra = x.mao_de_obra
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_excecao_mobra.familia = $familia
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.familia IS NOT NULL
					AND tbl_excecao_mobra.produto IS NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);




			$sql = "UPDATE tbl_os SET
				mao_de_obra = mao_de_obra + x.adicional_mao_de_obra
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.adicional_mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_excecao_mobra.familia = $familia
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.adicional_mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.familia IS NOT NULL
					AND tbl_excecao_mobra.produto IS NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);


			$sql = "UPDATE tbl_os SET
				mao_de_obra = mao_de_obra * (1 + (x.percentual_mao_de_obra / 100))
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.percentual_mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_excecao_mobra.familia = $familia
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.percentual_mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.familia IS NOT NULL
					AND tbl_excecao_mobra.produto IS NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);



			$sql = "UPDATE tbl_os SET
				mao_de_obra = x.mao_de_obra
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_excecao_mobra.produto = $produto
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.linha IS NOT NULL
					AND tbl_excecao_mobra.produto IS NOT NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);




			$sql = "UPDATE tbl_os SET
				mao_de_obra = mao_de_obra + x.adicional_mao_de_obra
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.adicional_mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_excecao_mobra.produto = $produto
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.adicional_mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.linha IS NOT NULL
					AND tbl_excecao_mobra.produto IS NOT NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);




			$sql = "UPDATE tbl_os SET
				mao_de_obra = mao_de_obra * (1 + (x.percentual_mao_de_obra / 100))
				FROM (
					SELECT tbl_os.os, tbl_excecao_mobra.percentual_mao_de_obra
					FROM tbl_os
					JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_excecao_mobra.produto = $produto
					WHERE tbl_os.fabrica = $fabrica
					AND tbl_os.os = $os
					AND tbl_excecao_mobra.percentual_mao_de_obra IS NOT NULL
					AND tbl_excecao_mobra.linha IS NOT NULL
					AND tbl_excecao_mobra.produto IS NOT NULL
					AND tbl_os.os NOT IN (
						SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
						WHERE tbl_os.fabrica = $fabrica
						AND tbl_os.posto = 14270
						AND tbl_revenda.nome ILIKE '%AULIK%'
					)
				) x
				WHERE tbl_os.os = x.os";
			$res = pg_query($con,$sql);


			if(strlen(trim(pg_last_error($con))) == 0){
				$sql = "COMMIT TRANSACTION";
				$result = pg_query($con, $sql);
			}else{
				$sql = "ROLLBACK TRANSACTION";
				$result = pg_query($con, $sql);
				$msg = pg_last_error();
			}
		}
    }

    $phpCron->termino();

} catch (Exception $e) {

    $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();
    //echo $msg."\r\n";

    $data_log = array('dest'=>'helpdesk@telecontrol.com.br');
    Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao executar Rotina Calcula OS Finalizada - " . ucfirst($fabricas[$fabrica]), $msg);

}


    ?>
