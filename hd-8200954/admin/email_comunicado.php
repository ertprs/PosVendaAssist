<?php
if ( ! function_exists('gravar_comunicado') ) {
	function limpa_email ($email) {
		return preg_replace('/[<>"\']/', '', $email);
	}
	/**
	 * Grava um comunicado dentro do assist
	 *
	 * @param	string	$tipo 
	 * @param	string	$titulo
	 * @param	string	$mensagem
	 * @param	int		$posto		[optional, default 'null']
	 * @param	boolean	$obrigatorio[optional, default 'true']
	 * @return	boolean
	 * @author	Manuel Lopez <manolo@telecontrol.com.br>
	 */
	function gravar_comunicado($tipo, $titulo, $mensagem, $posto = null, $obrigatorio = true) {
		global $con, $login_fabrica;
		
		if ( ! is_resource($con) ) { return false; }
		if ( empty($login_fabrica) ) { return false; }
		$posto = ( is_null($posto) ) ? 'null' : $posto ;
		
		$mensagem 	= pg_escape_string($mensagem);
		$titulo   	= pg_escape_string($titulo);
		$tipo		= pg_escape_string($tipo);
		$obrigatorio= ($obrigatorio)?'true':'false';
		
        $sql = "INSERT INTO tbl_comunicado (mensagem, tipo, descricao, posto, fabrica, obrigatorio_site, ativo) ".
                "VALUES ('$mensagem', '$tipo', '$titulo', $posto, $login_fabrica, $obrigatorio, true)";
        $res = pg_query($con,$sql);
        if ( is_resource($res) && pg_affected_rows($res) == 1 ) {
        	return true;
        }
//         echo nl2br($sql);
        return false;
	}
	
	/**
	 * Envia um e-mail, pode enviar uma mensagem para suporte caso dê erro na hora do envio
	 *
	 * @param	string	$from
	 * @param	string	$to
	 * @param	string	$titulo
	 * @param	string	$body
	 * @param	string	$sender		[optional, dejault '']
	 * @param	string	$headers	[optional, dafaul '']
	 * @param	boolean	$send_error	[optional, default false]
	 * @return	boolean
	 * @author	Manuel Lopez <manolo@telecontrol.com.br>
	 */
	function enviar_email($from, $to, $titulo, $body, $sender = '', $headers = '', $send_error = false) {
		global $login_fabrica, $externalId;
		
		include_once __DIR__."/../class/communicator.class.php";
		
		$fabrica= $login_fabrica;
		$from	= limpa_email($from);
		$to		= limpa_email($to);
		$sender	= limpa_email($sender);
		$subject= $titulo;
		$rem	= ($sender<>'')?"\"$sender\" <$from>":$from;
		if ($to == '' or $titulo=='') return false;
		
        $mailTc = new TcComm($externalId);
        $mailTc->sendMail(
          $to,
          $subject,
          $body,
          'noreply@telecontrol.com.br'
        );
	}


	function mail_comunicado ($posto, $from, $to, $titulo, $mensagem, $sender = '', $headers = '',
							  $send_error = false, $obrigatorio = true, $ativo = true) {
		if ($to == '' or $posto=='' or $fabrica=='' or is_bool($posto) or is_bool($fabrica) or $titulo=='') return false;
		$com = gravar_comunicado($tipo,$titulo,$mensagem, $posto, $obrigatorio);
		$mail= enviar_email($from, $to, $titulo, $mensagem, $sender, $headers, $send_error);
		$ret = ($com) ?'t':'f';
		$ret.= ($mail)?'t':'f';
		return $ret;
	}
}