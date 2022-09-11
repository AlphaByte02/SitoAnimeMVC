<link rel="stylesheet" href="<?= path("styledir") . "/image-loading.css" ?>">
<style>
	img {
		max-height: 300px;
	}

	.is-loading,
	.is-broken {
		height: 300px !important;
	}

	@media only screen and (max-width: 600px) {
		.anime-title {
			margin-top: .5rem !important;
		}
	}
</style>
<div class="container grey lighten-3 rounded py-2">
	<div class="row">
		<div class="col-sm-12 center">
			<h1>Alpha Anim</h1>
		</div>
	</div>
	<?php if (!empty($lastAnimeViewed)) : ?>
		<div class="row">
			<div class="col-sm-12">
				<h5><b>Gli ultimi anime che hai visto</b></h5>
			</div>
		</div>
		<div id="last-anime-container" class="row">
			<?php foreach ($lastAnimeViewed as $anime) : ?>
				<div class="<?= "col-md-" . (12 / $numLastViewed) ?> col-sm-12">
					<a href="<?= $anime->getAnimeUrl() ?>">
						<div class="container hoverable center">
							<div class="row mb-1">
								<div class="col-sm-12 mt-2">
									<div class="imageLoader is-loading mx-auto">
										<img src="<?= $anime->getImgUrl() ?>" alt="AnimeCover" class="responsive-img">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="anime-title col-sm-12 mb-1">
									<span class="black-text flow-text img-text w-100">
										<?= $anime->name ?>
									</span>
								</div>
							</div>
						</div>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<div class="row">
		<div class="col-sm-12">
			<h5><b>Alcuni di nostri anime</b></h5>
		</div>
	</div>
	<div id="anime-random-container" class="row">
		<?php foreach ($animeRandom as $anime) : ?>
			<div class="<?= "col-md-" . (12 / $numAnimeRandom) ?> col-sm-12">
				<a href="<?= $anime->getAnimeUrl() ?>">
					<div class="container hoverable center">
						<div class="row mb-1">
							<div class="col-sm-12 mt-2">
								<div class="imageLoader is-loading mx-auto">
									<img src="<?= $anime->getImgUrl() ?>" alt="AnimeCover" class="responsive-img">
								</div>
							</div>
						</div>
						<div class="row">
							<div class="anime-title col-sm-12 mb-1">
								<span class="black-text flow-text img-text w-100">
									<?= $anime->name ?>
								</span>
							</div>
						</div>
					</div>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<script>
	$(document).ready(function() {
		$("#last-anime-container,#anime-random-container").imagesLoaded()
			.progress(function(imgLoad, image) {
				let $item = $(image.img).parent();
				$item.removeClass('is-loading');
				if (!image.isLoaded) {
					$item.addClass('is-broken');
				}
			})
	})
</script>
<script src="https://unpkg.com/imagesloaded@4/imagesloaded.pkgd.min.js"></script>
