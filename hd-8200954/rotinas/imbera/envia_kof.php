<?php


$ftp_server = "201.33.134.254";
$ftp_user   = "BRAPPIMBER";
$ftp_pass   = "W+58+dkQ";
$conexao_ftp = ftp_connect($ftp_server,22);

    ftp_login($conexao_ftp, $ftp_user, $ftp_pass);
    ftp_pasv($conexao_ftp, true);


$dirlocal="/mnt/kof/saida/";
#$dirlocal="/home/ww2novo/saida/";
$dir = opendir($dirlocal);
$dirremote="/FTP_facelect/410/Inbound/InbIMBERA/";
$array_arquivos = array();

while (($file = readdir($dir)) !== false) {
        if ($file != '..' && $file != '.') {
        
    if (ftp_put($conexao_ftp, $dirremote.$file,$dirlocal.$file, FTP_BINARY)) {			
        $array_arquivos[] = $file;
        echo "Arquivo enviado com sucesso \n";

        $ultima_linha_arquivo = system("tail -n 1 {$dirlocal}{$file}");
        $mensagem = "Retorno KOF $file enviado com sucesso.\n$ultima_linha_arquivo";
        mail("waldir.pimentel@telecontrol.com.br,maicon.luiz@telecontrol.com.br,telecontrol.retornokof@imberacooling.com", "Retorno KOF $file enviado com sucesso", $mensagem);

        if (unlink($dirlocal.$file)) {
            echo "arquivo deletado com sucesso \n";
        } else {
	    echo "erro ao excluir arquivo \n";
        }
    } else {
        echo "Erro ao enviar arquivo para Kof {$dirlocal}{$file}\n";
    }
        }
}

if (empty($array_arquivos)) {
        mail("felipe.vaz@telecontrol.com.br,amanda.dasilva@imberacooling.com, waldir.pimentel@telecontrol.com.br,telecontrol.retornokof@imberacooling.com", "Não enviou retorno KOF", "Não enviou retorno KOF");
}
    
die;
