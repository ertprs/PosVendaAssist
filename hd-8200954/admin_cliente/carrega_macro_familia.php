<?php
if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    if ($_POST['action'] == 'carrega_macro_familia') {
        $macro_linha = $_POST['macro_linha'];

        $sql_linha = "SELECT DISTINCT
                      tbl_linha.linha,
                      tbl_linha.nome
                    FROM tbl_linha
                    JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                          JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                    WHERE tbl_linha.fabrica = {$login_fabrica} AND tbl_macro_linha_fabrica.macro_linha = {$macro_linha} AND tbl_linha.ativo IS TRUE
                    ORDER BY tbl_linha.nome ";

        $res_linha = pg_query($con, $sql_linha);
        if ($_POST['multiselect'] == 'false') {
            $linha_select = "<option value=''>ESCOLHA</option>";
        }
        if (pg_num_rows($res_linha) > 0) {
            for ($x = 0 ; $x < pg_num_rows($res_linha) ; $x++){
                $aux_linha = trim(pg_fetch_result($res_linha, $x, linha));
                $aux_nome = trim(pg_fetch_result($res_linha, $x, nome));

                $linha_select .= "<option value='$aux_linha'>$aux_nome</option>";
            }
        }
        exit(json_encode(array('ok' => utf8_encode($linha_select))));
    }elseif ($_POST['action'] == 'carrega_familia') {
        $linha = (is_array($_POST['linha'])) ? ' linha in('.implode(',', $_POST['linha']).')' : 'linha = '.$_POST['linha'];

        $sql_familia = "SELECT
                            familia,
                            descricao
                        FROM tbl_familia
                        WHERE fabrica = {$login_fabrica}
                            AND {$linha}";
        $res_familia = pg_query($con, $sql_familia);
        $linha_select = "<option value=''>Selecione</option>";
        for ($x = 0 ; $x < pg_num_rows($res_familia) ; $x++){
            $aux_familia   = pg_fetch_result($res_familia, $x, "familia");
            $aux_descricao = pg_fetch_result($res_familia, $x, "descricao");

            $linha_select .= "<option value='$aux_familia'>$aux_descricao</option>";
        }
        exit(json_encode(array('ok' => utf8_encode($linha_select))));
    }
}

?>
