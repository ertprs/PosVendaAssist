<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include "autentica_admin.php";

$titulo = 'Atendimento interativo';

$res = pg_exec ($con,"BEGIN TRANSACTION");
/*
$sql = "INSERT INTO tbl_hd_chamado (titulo,fabrica, fabrica_responsavel,admin,atendente) values ('Atendimento interativo',$login_fabrica,$login_fabrica,$login_admin, $login_admin)";
$res = pg_exec($con,$sql);
$msg_erro = pg_errormessage($con);
*/
if (strlen($msg_erro)==0) {
	//$res    = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
    $res = pg_exec($con, "select nextval('seq_hd_chamado'::regclass) ");
	$hd_chamado = pg_result ($res,0,0);
	$msg_erro = pg_errormessage($con);
}

/*$sql = "INSERT INTO tbl_hd_chamado_extra (hd_chamado) values ($hd_chamado)";
$res = pg_exec($con,$sql);
$msg_erro = pg_errormessage($con);
*/
if (strlen($msg_erro)==0) {

	$res = pg_exec ($con,"COMMIT TRANSACTION");

	echo 'sim|'.$hd_chamado;

	if($login_fabrica == 129){

		$sql_ramal = "SELECT nome_completo, ramal FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica}";
		$res_ramal = pg_query($con, $sql_ramal);

		$nome_completo = pg_fetch_result($res_ramal, 0, "nome_completo");
		list($nome_admin, $sobrenome) = explode(" ", $nome_completo);
		$nome_admin = ucwords(strtolower($nome_admin));

		$ramal = pg_fetch_result($res_ramal, 0, "ramal");

		if(strlen(trim($ramal)) > 0){

			/* Ex: Cristiane_R683.exp */
            $caminho_arq = "/mnt/ftp-fabricantes/rinnai/sac/";
            // $url_arquivo = "/home/rinnai/sac/{$nome_admin}_R{$ramal}.exp";
			$url_arquivo = "{$caminho_arq}{$nome_admin}_R{$ramal}.exp";

            if(file_exists($url_arquivo)){
    			$dados_ramal = file_get_contents($url_arquivo);

    			if(strlen(trim($dados_ramal)) > 0){

                    list($inicio, $vazio1, $caminho_arquivo, $vazio2, $telefone) = explode("\n", $dados_ramal);
                    
                    /* Seleciona os dados */
                    list($str_inicio, $inicio) = explode(" - ", $inicio);
                    $dados_ramal_inicio = trim($inicio);

                    list($str_caminho_arquivo, $caminho_arquivo) = explode(" - ", $caminho_arquivo);
                    $dados_ramal_caminho_arquivo = trim(str_replace("\\", "\\\\", $caminho_arquivo));

                    list($str_telefone, $telefone) = explode(" - ", $telefone);
                    $dados_ramal_telefone = trim($telefone);

                    /* Fim - Seleciona os dados */

    				$sql_campos_adicionais = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra FROM hd_chamado = {$hd_chamado}";
    				$res_campos_adicionais = pg_query($con, $sql_campos_adicionais);

    				$campos_adicioanais = pg_fetch_result($res_campos_adicionais, 0, "array_campos_adicionais");

    				if(strlen(trim($campos_adicioanais)) > 0){

    					$campos_adicioanais = json_decode($campos_adicioanais, true);

                        $campos_adicioanais["inicio"]           = $dados_ramal_inicio;
                        $campos_adicioanais["caminho_arquivo"]  = $dados_ramal_caminho_arquivo;
    					$campos_adicioanais["telefone"]         = $dados_ramal_telefone;

    					$campos_adicioanais = str_replace("\\", "\\\\", json_encode($campos_adicioanais));

    				}else{

                        $dados = array(
                            "inicio"          => $dados_ramal_inicio,
                            "caminho_arquivo" => $dados_ramal_caminho_arquivo,
                            "telefone"        => $dados_ramal_telefone
                        );

    					$campos_adicioanais = str_replace("\\", "\\\\", json_encode($dados));

    				}

    				/*$sql_dados_ramal = "UPDATE tbl_hd_chamado_extra SET array_campos_adicionais = '{$campos_adicioanais}' WHERE hd_chamado = {$hd_chamado}";
    				$res_dados_ramal = pg_query($con, $sql_dados_ramal);*/

    				if(strlen(pg_last_error($con)) == 0){

    					unlink($url_arquivo);

                        $dados_ramal_caminho_arquivo = "\\\\admin09\\".str_replace(":", "", $dados_ramal_caminho_arquivo);

    					echo "|".$dados_ramal_caminho_arquivo;

    				}
    			}
            }
		}
	}

} else {
	$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	echo 'nao|';
}

?>
