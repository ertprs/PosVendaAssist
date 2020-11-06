<?php

	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	$admin_privilegios = "call_center";
	include "autentica_admin.php";

    $sql_ramal = "SELECT ramal FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica}";
    $res_ramal = pg_query($con, $sql_ramal);

    $ramal = pg_fetch_result($res_ramal, 0, "ramal");

    if(strlen($ramal) > 0){

        $sql_dados = "SELECT 
                        nome, 
                        fone,
                        array_campos_adicionais 
                    FROM tbl_hd_chamado_extra 
                    WHERE hd_chamado = {$hd_chamado}";
        $res_dados = pg_query($con, $sql_dados);

        $nome = pg_fetch_result($res_dados, 0, "nome");
        $fone = pg_fetch_result($res_dados, 0, "fone");
        $campos_adicionais = pg_fetch_result($res_dados, 0, "array_campos_adicionais");

        $campos_adicionais = json_decode($campos_adicionais, true);

        $telefone     = (strlen(trim($campos_adicionais["telefone"])) == 0) ? $fone : $campos_adicionais["telefone"];
        $hora_inicial = $campos_adicionais["inicio"];
        $hora_final   = date("d/m/Y H:i:s");

        $nome_caminho = strtolower(str_replace(" ", "-", $nome));

        $caminho_arq = "/mnt/ftp-fabricantes/rinnai/sac/";

        // $arquivos_excluir = glob("{$caminho_arq}{$hd_chamado}-*.txt");
        $arquivos_excluir = glob("{$caminho_arq}{$ramal}-*.imp");

        if(count($arquivos_excluir) > 0){
            foreach ($arquivos_excluir as $key => $value) {
                unlink($value);
            }
        }

        /* cliente, telefone, hora inicial, hora final, número do protocolo */
        // $fp = fopen("{$caminho_arq}{$hd_chamado}-{$nome_caminho}.txt", "a");
        $fp = fopen("{$caminho_arq}{$ramal}.imp", "a");
        fwrite($fp, "\n {$nome} \n {$telefone} \n {$hora_inicial} \n {$hora_final} \n {$hd_chamado} \n");
        fclose($fp);

    }

?>
