<div class="container-fluid">
    <div class="row-fluid">
        <div class="span10">
            <h3>Excel</h3>
            <p>Em algumas p�ginas do sistema os dados s�o demonstrados em tabelas,
                por�m nem sempre � poss�vel mostrar todos dados cadastrados em nossa
                base de dados, ent�o o usu�rio tem a op��o de gerar um arquivo XLS e
                fazer o download.</p>
            <p>Gerar esse arquivo � simples, basta gravar em um arquivo os dados em forma de tabela, e disponibilizar um link para download.</p>
            <p>Esse � um processo que pode ser seguido:</p>
            <ul>
                <li>Adicione o c�digo do bot�o na tela:</li>
                <ul>
                    <div class="control-group">
                        <textarea class="span10" rows="6" cols="12" disabled="disable">
                            &lt;?php
                                include 'assist/admin/include/funcoes.php'

                                $jsonPOST = excelPostToJson($_POST);
                            ?&gt;

                            &lt;input type="hidden" id="jsonPOST" value='&lt;?=$jsonPOST?&gt;' /&gt;
                            &lt;div id='gerar_excel' class="btn_excel"&gt;
                                &lt;span&gt;&lt;img src='imagens/excel.png' /&gt;&lt;/span&gt;
                                &lt;span class="txt"&gt;Gerar Arquivo Excel&lt;/span&gt;
                            &lt/div&gt;
                        </textarea>
                    </div>
                </ul>
                <li>Fa�a o select para pegar as informa��es:</li>
                <li>Set uma vari�vel com o nome do programa e exten��o xls, e crie o arquivo:</li>
                <ul>
                    <div class="control-group">
                        <textarea class="span10" rows="3" cols="12" disabled="disable">$data = date("d-m-Y-H:i");
                            $fileName = "relatorio_os_atendimento-{$data}.xls";
                            $file = fopen("/tmp/{$fileName}", "w");
                        </textarea>
                    </div>

                </ul>
                <li>Grave o cabe�alho do relat�rio:</li>
                <ul>
                    <div class="control-group">
                        <textarea class="span10" rows="6" cols="12" disabled="disable">fwrite($file, "
                            &lt;table border='1'&gt;
                                &lt;thead&gt;
                                        &lt;tr&gt;
                                                &lt;th colspan='1' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' &gt;
                                                        RELAT�RIO
                                                &lt;/th&gt;
                                        &lt;/tr&gt;
                                        &lt;tr&gt;
                                                &lt;th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'&gt;campo&lt;/th&gt;
                                        &lt;/tr&gt;
                                &lt;/thead&gt;
                                &lt;tbody&gt;
                            ");
                        </textarea>
                    </div>
                </ul>
                <li>Grave as linhas do relat�rio:</li>
                <ul>
                    <textarea class="span10" rows="6" cols="12" disabled="disable">for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
                            $campo = pg_fetch_result($resSubmit,$i,'campo');

                            fwrite($file, "
                                    &lt;tr&gt;
                                            &lt;td nowrap align='center'&gt;{$campo}&lt;/td&gt;
                                    &lt;/tr>"
                            );
                        }
                    </textarea>
                </ul>
                <li>Grave o rodap� com o total de registros.</li>
                <ul>
                    <textarea class="span10" rows="6" cols="12" disabled="disable">for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
                        fwrite($file, "
                                                &lt;tr&gt;
                                                        &lt;th colspan='1' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros&lt;/th&gt;
                                                &lt;/tr&gt;
                                        &lt;/tbody&gt;
                                &lt;/table&gt;
                        ");
                    </textarea>
                </ul>
                <li>Finalize o arquivo e disponibilize o link para download, e encerre o script com <code>"exit;"</code></li>
                <ul>
                    <textarea class="span10" rows="6" cols="12" disabled="disable">fclose($file);
                        if (file_exists("/tmp/{$fileName}")) {
                                system("mv /tmp/{$fileName} xls/{$fileName}");

                                // devolve para o ajax o nome doa rquivo gerado
                                echo "xls/{$fileName}";
                        }
                        exit;
                    </textarea>
                </ul>
            </ul>
        </div>
    </div>
</div>
