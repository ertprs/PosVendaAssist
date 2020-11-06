<?php $pagetitle = "Limpeza de Cache" ?>

<?php include('site_estatico/header.php') ?>
    <script>$('body').addClass('pg log-page')</script>

    <section class="table h-img">
        <?php include('site_estatico/menu-pgi.php'); ?>
        <div class="cell">
            <div class="title"><h2>Limpeza de Cache</h2></div>
        </div>
    </section>

    <section class="pad-1 cache-sec">
        <div class="main2">
            <div class="desc no-m">
                <p class="text-center">Durante a navegação os navegadores acumulam arquivos com objetivo de otmizar o
                    carregamento de conteúdos, este armazenamento é chamado de "cache".
                    <br>Torne seu navegador mais rápido e seguro para utilização dos Sistema Telecontrol realizando a
                    limpeza do "cache".
                    <br>Escolha abaixo o navegador utilizado e acesse a página oficial com o passo a passo para realizar a
                    limpeza.</p>
            </div>

            <div class="browsers m-top4">
                <ul>
                    <li>
                        <div class="b-chrome">
                            <img src="images/browsers/chrome.svg" alt="Google Chrome">
                            <h3>Google Chrome</h3>
                        </div>
                        <div>
                            <div class="btn btn-small">
                                <a href="https://support.google.com/accounts/answer/32050?hl=pt-BR"
                                   target="_blank"><span>Saiba mais</span></a>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="b-firefox">
                            <img src="images/browsers/firefox.svg" alt="Mozilla Firefox">
                            <h3>Mozilla Firefox</h3>
                        </div>
                        <div>
                            <div class="btn btn-small">
                                <a href="https://support.microsoft.com/pt-br/help/10607/windows-10-view-delete-browser-history-microsoft-edge"
                                   class="btn"
                                   target="_blank"><span>Saiba mais</span></a>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="b-edge">
                            <img src="images/browsers/edge.svg" alt="Microsoft Edge">
                            <h3>Microsoft Edge</h3>
                        </div>
                        <div>
                            <div class="btn btn-small">
                                <a href="https://support.microsoft.com/pt-br/help/10607/windows-10-view-delete-browser-history-microsoft-edge"
                                   class="btn"
                                   target="_blank"><span>Saiba mais</span></a>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="b-ie">
                            <img src="images/browsers/ie.svg" alt="Internet Explorer">
                            <h3>Internet Explorer</h3>
                        </div>
                        <div>
                            <div class="btn btn-small">
                                <a href="https://support.microsoft.com/pt-br/help/17438/windows-internet-explorer-view-delete-browsing-history"
                                   class="btn"
                                   target="_blank"><span>Saiba mais</span></a>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="b-safari">
                            <img src="images/browsers/safari.svg" alt="Safari">
                            <h3>Safari</h3>
                        </div>
                        <div>
                            <div class="btn btn-small">
                                <a href="https://support.apple.com/kb/PH21412?locale=pt_BR"
                                   class="btn"
                                   target="_blank"><span>Saiba mais</span></a>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="b-opera">
                            <img src="images/browsers/opera.svg" alt="Opera">
                            <h3>Opera</h3>
                        </div>
                        <div>
                            <div class="btn btn-small">
                                <a href="http://help.opera.com/Windows/10.20/pt/history.html"
                                   class="btn"
                                   target="_blank"><span>Saiba mais</span></a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

        </div>
    </section>

<?php include('site_estatico/footer.php') ?>