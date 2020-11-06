  <?php 
    $bannerLoja = $objBanner->get(0, true);
    if (!empty($bannerLoja)) {
?>
<div id="myBanners" class="carousel slide">
    <div class="carousel-inner">
        <?php 
            $i = 1;
            foreach ($bannerLoja as $rowsBanner) {
            if (!empty($rowsBanner["imagem"])) {
        ?>
        <div class="<?php echo ($i == 1) ? 'active' : '';?> item">
            <a href="<?php echo APP_URL.$rowsBanner["link"];?>">
            <img style="max-width: 100%;height: 400px;" src="<?php echo $rowsBanner["imagem"];?>" alt="<?php echo $rowsBanner["descricao"];?>"">
            </a>
        </div>
        <?php   $i++;
            }
            }
        ?>
    </div>
    <a class="carousel-control left" href="#myBanners" data-slide="prev">&lsaquo;</a>
    <a class="carousel-control right" href="#myBanners" data-slide="next">&rsaquo;</a>
</div>
<?php }?>