<?php 

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','dev');  // producao Alterar para produção ou algo assim

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

$fabrica = 1;
$arquivos = "/tmp";
$nomeArquivo = "$arquivos/blackedecker/pedido-aprovado-demanda.err";
#$nomeArquivo = "/home/gaspar/public_html/PosVendaAssist/teste/pedido-aprovado-demanda.err";
$hoje = date("d-m-Y");
$hoje = date('d-m-Y', strtotime("-1 days",strtotime($hoje))); 

$destino   = "/var/www/assist/www/rotinas/blackedecker/entrada";
#$destino   = "/home/gaspar/public_html/PosVendaAssist/rotinas/blackedecker/tests";
$arquivo   = "$destino/pedido_aprovado_demanda-$hoje.csv";

function replace_unicode_escape_sequence($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}
function unicode_decode($str) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
}

$sql = " SELECT distinct tbl_pedido.pedido, 
        tbl_pedido.seu_pedido, 
                regexp_replace(tbl_pedido_item.valores_adicionais::text,'\\\\u00','\\\\\\\\u00','g')::json->>'observacao' observacao_item ,
                regexp_replace(tbl_pedido_item.valores_adicionais::text,'\\\\u00','\\\\\\\\u00','g')::json->>'auditoria' auditoria ,
        tbl_pedido.aprovacao_obs, 
        tbl_pedido.aprovacao_tipo, 
        to_char(tbl_pedido.finalizado,'DD/MM/YYYY') as finalizado, 
        tbl_peca.referencia as referencia_peca, 
        tbl_peca.parametros_adicionais, 
        tbl_peca.descricao as descricao_peca, 
        tbl_posto_fabrica.posto, 
        tbl_posto_fabrica.codigo_posto, 
        tbl_posto.nome, 
        tbl_posto_fabrica.categoria, 
        tbl_pedido_item.qtde, 
        
        tbl_pedido_item.valores_adicionais::JSON->>'aprovado' as aprovado,
        tbl_pedido_item.valores_adicionais::JSON->>'excluido' as excluido, 
        tbl_pedido_item.valores_adicionais::JSON->>'alterada' as alterado, 
        tbl_pedido_item.valores_adicionais::JSON->>'msg_comunicado' as msg_comunicado,

        tbl_pedido_item.obs  
    from tbl_pedido 
    join tbl_pedido_status on tbl_pedido.pedido = tbl_pedido_status.pedido 
    join tbl_pedido_status status_aprovacao on tbl_pedido.pedido = status_aprovacao.pedido and status_aprovacao.status in (18,33) and status_aprovacao.observacao notnull and status_aprovacao.admin isnull
    join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_pedido.posto and tbl_posto_fabrica.fabrica = 1  
    join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto 
    join tbl_pedido_item on tbl_pedido_item.pedido = tbl_pedido.pedido 
    join tbl_peca on tbl_peca.peca = tbl_pedido_item.peca  
    where tbl_pedido.fabrica = $fabrica   
    and (tbl_pedido.finalizado is not null OR tbl_pedido_status.observacao = 'Aprovado Automaticamente')
    --and (tbl_pedido_status.observacao <> 'Aprovado Automaticamente' OR tbl_pedido_status.observacao is null)
    and tbl_pedido_status.status = 1 and tbl_pedido_status.data::date = current_date - 1 

    and tbl_pedido_item.valores_adicionais::JSON->>'aprovado' = 'true' 
    and tbl_pedido_item.valores_adicionais::JSON->>'demanda' = 'true'
    
    and tbl_pedido.status_pedido not in(18,14)

    order by tbl_pedido.pedido ";	
$res = pg_query($con, $sql);

if(pg_num_rows($res)>0){
	$file = fopen("$arquivo", "w");
	$thead = "Código Posto;Nome posto;Categoria posto;Pedido;Finalização;Código Peça;Nome peça;Qtde solicitada;Qtde demanda; Status; Quantidade Alterada; Auditoria;Justificativa Aprovação;Mensagem Posto \r\n"; 

	fwrite($file, $thead);
	for($i=0; $i<pg_num_rows($res); $i++){
		$pedido = pg_fetch_result($res, $i, 'pedido');
		$seu_pedido = pg_fetch_result($res, $i, 'seu_pedido');
		$codigo_posto = pg_fetch_result($res, $i, 'codigo_posto');
		$nome_posto = pg_fetch_result($res, $i, 'nome');
		$categoria = pg_fetch_result($res, $i, 'categoria');
		$aprovacao_obs_total = pg_fetch_result($res, $i, 'aprovacao_obs');
		$aprovacao_tipo_total = pg_fetch_result($res, $i, 'aprovacao_tipo');
		//$valores_adicionais = pg_fetch_result($res, $i, "valores_adicionais");
		$finalizado = pg_fetch_result($res, $i, 'finalizado');
		$referencia_peca = pg_fetch_result($res, $i, 'referencia_peca');
		$descricao_peca = pg_fetch_result($res, $i, 'descricao_peca');
		$parametros_adicionais = pg_fetch_result($res, $i, 'parametros_adicionais');
		$qtde = pg_fetch_result($res, $i, 'qtde');
		$obs = utf8_decode(pg_fetch_result($res, $i, 'obs'));

		$alterado = pg_fetch_result($res, $i, 'alterado');
		$excluido = pg_fetch_result($res, $i, 'excluido');

		$msg_comunicado = pg_fetch_result($res, $i, "msg_comunicado");
		$valores_adicionais = json_decode($valores_adicionais, true);
		$observacao_item = pg_fetch_result($res, $i, 'observacao_item');		
		$observacao_item = utf8_decode(unicode_decode("$observacao_item"));
		$auditoria =ucfirst(pg_fetch_result($res, $i, 'auditoria'));

		if(strlen(trim($alterado))>0){
			$status = "Alterada";
		}elseif(strlen(trim($excluido))>0){
			$status = "Excluída";
		}else{
			$status = "Aprovada";
		}

		$parametros_adicionais = json_decode($parametros_adicionais, true);
		$demanda = $parametros_adicionais['qtde_demanda'];

		$tbody = "$codigo_posto;$nome_posto;$categoria;$seu_pedido;$finalizado;$referencia_peca;$descricao_peca;$qtde;$demanda; $status ;$msg_comunicado;$auditoria;$obs;$observacao_item\r\n";

		fwrite($file, $tbody);
	}

	fclose($file);

	$mail = new PHPMailer(); // nao retirar 
	$mail->IsHTML();
	$mail->Subject = 'Aprovacao sobrevenda pecas';
	$mail->Body = 'Segue em anexo pedidos aprovados';
	$mail->AddAddress('juliano.pires@sbdinc.com');
	$mail->AddAddress('daniele.barboza@sbdinc.com');
	$mail->AddAddress('bruna.maciel@sbdinc.com');
	$mail->AddAddress('isadora.alves@sbdinc.com');
	$mail->AddAddress('fabiola.oliveira@sbdinc.com');
	$mail->AddAddress('helpdesk@telecontrol.com.br');
	#$mail->AddAddress('gaspar.lucas@telecontrol.com.br');
	#$mail->AddAddress('joao.junior@telecontrol.com.br');
	$mail->AddAttachment($arquivo);
	$mail->SetFrom("noreply@telecontrol.com.br", 'Telecontrol');

	if (!$mail->Send()) echo 'erro ao enviar email';
}
?>
