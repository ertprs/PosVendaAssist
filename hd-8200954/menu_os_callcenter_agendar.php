<style type="text/css">
.Titulo {
    text-align: center;
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
}
.Conteudo {
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.subtitulo{

    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
    text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.informacao{
    font: 14px Arial; color:rgb(89, 109, 155);
    background-color: #C7FBB5;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.espaco{
    padding-left:80px;
    width: 220px;
}
</style>

<?php

$sql = "SELECT DISTINCT
                        o.os,
                        TO_CHAR(o.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                        p.referencia||' - '||p.descricao AS produto
            FROM tbl_os o
            JOIN tbl_produto p ON p.produto = o.produto AND p.fabrica_i = {$login_fabrica}
            WHERE o.fabrica = {$login_fabrica}
            AND o.posto = {$login_posto}
            AND o.hd_chamado IS NOT NULL
			AND finalizada isnull
            AND o.os NOT IN (SELECT os FROM tbl_os_visita WHERE os = o.os);";

$res = pg_query($con, $sql);

$contOSCallcenter = pg_num_rows($res);

if ($contOSCallcenter > 0) {
    echo '<br/>';
    echo '<table border="0" cellspacing="0" cellpadding="2" width="700" class="tabela">';
    echo '<thead>';
        echo '<tr class="titulo_tabela">';
            echo '<th colspan="3">OSs abertas pelo Call-Center e aguardando agendamento</th>';
        echo '</tr>';
        echo '<tr class="titulo_coluna">';
            echo '<th>OS</th>';
            echo '<th>Data Abertura</th>';
            echo '<th>Produto</th>';
        echo '</tr>';
    echo '</thead>';
    
    echo '<tbody>';
    while ($fetch = pg_fetch_assoc($res)) {
        $os = $fetch['os'];
        $data_abertura = $fetch['data_abertura'];
        $produto = $fetch['produto'];

        if ($i % 2 == 0) {
            $bgcolor = '#FFFFFF';
        } else {
            $bgcolor = '#EAEAEA';
        }

        echo '<tr class="Conteudo" style="text-align:center;background:'.$bgcolor.';">';
            echo '<td><a target="_blank" href="os_item_new.php?os='.$os.'">'.$os.'</a></td>';
            echo '<td>'.$data_abertura.'</td>';
            echo '<td>'.$produto.'</td>';
        echo '</tr>';
        
        $i++;
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '<br/>';
}
