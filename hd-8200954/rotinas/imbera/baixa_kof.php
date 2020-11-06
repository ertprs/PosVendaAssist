<?php


$ftp_server = "201.33.134.254";
$ftp_user   = "BRAPPIMBER";
$ftp_pass   = "W+58+dkQ";


$dirlocal="/mnt/kof/entrada/";
#$dirlocal="/home/imbera/imbera-telecontrol/";
#$dirlocal="/mnt/webuploads/imbera/processado/";
$dirremote="/FTP_facelect/410/Outbound/OutIMBERA/";
system("mv /home/imbera/imbera-telecontrol/KOF* $dirlocal");
	$conexao_ftp = ftp_connect($ftp_server,22);

        ftp_login($conexao_ftp, $ftp_user, $ftp_pass);
        ftp_pasv($conexao_ftp, true);
//        ftp_chdir($conexao_ftp, $dirremote);



	$arquivos = ftp_nlist($conexao_ftp,$dirremote);


	print_r($arquivos);


	foreach($arquivos as $arquivo) {
		$nome_arquivo = basename($arquivo);
		if($nome_arquivo != 'bkp') {
			if(ftp_get($conexao_ftp, $dirlocal.$nome_arquivo, $dirremote.$nome_arquivo, FTP_BINARY)) {
			        echo "arquivo $nome_arquivo baixado com sucesso \n";
				if(ftp_put($conexao_ftp, $dirremote.'bkp/'.$nome_arquivo,$dirlocal.$nome_arquivo, FTP_BINARY)) {			
					echo "Arquivo $nome_arquivo copiado para bkp \n";
					if (ftp_delete($conexao_ftp,$dirremote.$nome_arquivo)) {
						echo "arquivo $nome_arquivo deletado com sucesso \n";
					}else {
						echo "erro ao excluir $nome_arquivo \n";
					}
				} else {
					echo "Erro ao mover para bkp \n";
				}
			} else {
        			echo "erro ao baixar $nome_arquivo \n";
			}
		}

	}




die;


if(ftp_get($conexao_ftp, $dirlocal, $dirremote.'/*.txt', FTP_BINARY)) {
        echo "arquivos baixados";
} else {
	echo "erro ao baixar";
}



