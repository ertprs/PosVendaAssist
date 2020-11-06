<?php
/**
 * HD 417698. Função que verifica se o posto vai enviar a documentação online ou via Correios. Se for online, retorna true, para obrigar anexar NF na OS.
 * @param $fabrica fabrica do posto
 * @param $posto id do posto logado
 * @return bool true caso a opção do posto seja envio online, e false caso seja via correios, ou nao tenha resposta
 * @version 0.1
 * @author Brayan Rastelli
 */

function EnvioObrigatorioNF ($fabrica, $posto, $os = '', $tipo_atendimento = '') {

	global $con;

	$sql = "SELECT posto
			FROM tbl_tipo_gera_extrato
			WHERE fabrica = $fabrica
			AND posto = $posto
			AND envio_online";

	$res = pg_query($con,$sql);

	$ret = (bool) pg_num_rows($res);

	if($fabrica == 1 AND $tipo_atendimento == 17){ // HD-2208108
		$ret = 1;
	}
	// Para alguns tipos de OS cortesia, e tipos de atendimento, nao sera bloqueado, mesmo para postos com envio online.
	if (!empty($os) ) {

		 if (strpos('_' . TIPOS_ANEXO, $os[0]) > 0) {

            $prefixo_anexo = $os[0] . '_';
            $os = preg_replace('/\D/', '', $os);

        }

		switch($prefixo_anexo){

            case '': $tbl = 'tbl_os'; $campo = 'os'; break;
            case 'r_': $tbl = 'tbl_os_revenda'; $campo = 'os_revenda'; break;
            case 's_': $tbl = 'tbl_os_sedex'; $campo = 'os_sedex'; break;

        }

		$sql = "SELECT tipo_os_cortesia, tbl_tipo_atendimento.descricao
			FROM $tbl
			LEFT JOIN tbl_tipo_atendimento USING(tipo_atendimento)
			WHERE $campo = $os
			AND $tbl.fabrica = $fabrica";

		$res = pg_query($con,$sql);

		$cortesia 	= @pg_result($res,0,0);
		$at 		= @pg_result($res,0,1);

		switch($cortesia) {

			case 'Sem Nota Fiscal':
			case 'Promotor':
			case 'Fora da Garantia':
				$ret = false;
				break;

		}

		switch($at) {

			case 'Troca faturada':
			case 'Troca em cortesia':
				$ret = false;
				break;

		}

	}

    if ($fabrica == 1 && $tipo_atendimento == 334) {
        $ret = false;
    }

	return $ret;

}
