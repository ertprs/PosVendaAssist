<div class="container-fluid">
    <div class="row-fluid">
        <div class="span10">
            <h3>Ajax</h3>
            <div class="row-fluid">
                <div class="span10">
                    <p>Para utilizar o ajax padr�o segue os par�metros:</p>
                    <ul>
                        <li><code>loadind(param)</code> : mostra/esconde o gif de loading, o <code>param</code> pode ser "show"/"hide"</li>
                        <li><code>ajaxAction()</code>: serve para verificar se ja esta ocorrendo um ajax isso evitar 
                            que seja enviado 2 ajax ao mesmo tempo o que pode as vezes resultar em um 
                            loading infinito, esta fun��o retorna true ou false, true fala que n�o 
                            tem nenhum ajax rodando, false fala que tem ajax rodando</li>
                    </ul>
                    <p>Abaixo um exemplo do c�digo fonte:</p>
                        
                    <textarea class="span10" rows="6" cols="12" disabled="disable">
        if (ajaxAction()) {
            $.ajax({
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "POST",
                dataType: "JSON",
                data: { gravar: true },
                beforeSend: function  () {
                    loading("show");
                },
                complete: function () {
                    loading("hide");
                } 
            });
        }
                    </textarea>
                    
                </div>
            </div>
        </div>
    </div>
</div>