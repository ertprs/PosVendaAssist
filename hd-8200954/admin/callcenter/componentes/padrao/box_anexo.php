<div class="container_bc">
    <div class="top_painel">
        <div class="row">
            <div class="col-md-12 tac">
                <b><?php echo traduz("Anexo(s)");?></b> 
            </div>
        </div>
    </div>
    <div class="box_campos">
        <div class="box_add_init_callcenter">
             <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <?php
                        $boxUploader = array(
                            "context" => "callcenter",
                            "tamanho_botao" => "btn-lg btn-info",
                            "hidden_title" => true,
                            "unique_id" => $callcenter
                        );
                        include "box_uploader.php";
                    ?>
                    <script>
                        $(function() {
                        BoxUploader.registerCallback('delete', (res, file) => new Promise((resolve, reject) => {
                                    if (res.remove == 'ok') {
                                        $.ajax({
                                            async: true,
                                            timeout: 60000,
                                            type: 'POST',
                                            dataType: 'JSON',
                                            url: 'callcenter_interativo_new.php',
                                            data: {
                                                ajax_interage_upload_arquivo_excluir: true,
                                                tdocsId: file.id(),
                                                callcenter: '<?=$callcenter?>'
                                            },
                                        })
                                        .done(() => resolve());
                                    }
                                    resolve();
                                }
                        ));

                        BoxUploader.registerCallback('upload', (fileInfo) => new Promise((resolve, reject) => 
                            {
                                        $.ajax({
                                            async: true,
                                            timeout: 60000,
                                            type: 'POST',
                                            dataType: 'JSON',
                                            url: 'callcenter_interativo_new.php',
                                            data: {
                                                ajax_interage_upload_arquivo: true,
                                                nome_arquivo: fileInfo.fileId.file_name[0],
                                                callcenter: '<?=$callcenter?>'
                                            }
                                        }).done(() => resolve());
                                        }));
                            });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
       