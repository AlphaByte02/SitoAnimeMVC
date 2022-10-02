<script>
	$(document).ready(function() {
		var searchArea = $("#searchArea")
		if (searchArea.val() != "")
			search(searchArea.val().toLowerCase())

		searchArea.on("keyup", function() {
			search($(this).val().toLowerCase())
		});

		searchArea.on("change", function() {
			search($(this).val().toLowerCase())
		});

		function search(value) {
			value = $.trim(value);
			$("#indici").toggle(value == "");

			$("#animelist > div a").filter(function() {
				let hide = $(this).text().toLowerCase().indexOf(value) != -1;

				$(this).toggle(hide)
			});

			$("#animelist > div").filter(function() {
				let isAllVisible = $(this).children("div [style*='display: none']").length != $(this).children().length - 1;
				$(this).toggle(isAllVisible);
			});
		}
	});
</script>

<link rel="stylesheet" href="<?= path("styledir") . "/image-loading.css" ?>">
<style>
	@media only screen and (max-width: 600px) {
		.anime-title {
			margin-top: .5rem !important;
		}
	}

	#searchFields {
		transition: all .2s;
	}

	#searchFields:has(label.active) {
		margin-top: 2rem;
	}
</style>

<div class="container grey lighten-3 rounded">
	<div class="row">
		<div id="searchFields" class="input-field col-sm-11 mx-auto">
			<label for="searchArea">Search Anime</label>
			<input id="searchArea" type="text" class="autocomplete" autocomplete="off">
			<span class="helper-text">Nel database sono contenuti <?= $totalAnimeNum ?> anime</span>
		</div>
	</div>
	<div class="row" id="indici">
		<div class="col-sm-12 center">
			<ul class="pagination">
				<?php foreach (array_keys($animes) as $letter) : ?>
					<li class="waves-effect"><a href="#<?= $letter ?>"><?= $letter ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<div id="animelist" class="pb-1">
		<?php $allAnime = []; ?>
		<?php $printRow = function ($anime, $group = "") { ?>
			<a href="<?= $anime->getAnimeUrl() ?>">
				<div class="row hoverable pb-1 pt-2 mx-2 center-on-small-only">
					<div class="col-sm-12 col-md-2">
						<div class="imageLoader is-loading mx-auto">
							<img loading="lazy" src="<?= $anime->getImgUrl() ?>" alt="AnimeCover" class="responsive-img">
						</div>
					</div>
					<div class="anime-title col-sm-12 col-md-10 valign-wrapper center-on-small-only">
						<span class="black-text flow-text img-text w-100">
							<?= !empty($group) && $group != $anime->name ? $group . "<br>" : "" ?> <?= $anime->name ?>
						</span>
						<span class="d-none"><?= $anime->name_en ?: $anime->name ?></span>
					</div>
				</div>
			</a>
			<?php return $anime->name; ?>
		<?php }; ?>
		<?php foreach ($animes as $letter => $value) : ?>
			<div id="<?= $letter ?>" class="container">
				<div class="row">
					<div class="col-sm-12">
						<h2 class="center"><?= $letter ?></h2>
					</div>
				</div>
				<?php foreach ($value as $mixed) : ?>
					<?php if ($mixed instanceof \Mvc\Models\GroupAnimeModel) : ?>
						<?php foreach ($mixed->animes as $anime) : ?>
							<?php $allAnime[] = $printRow($anime["anime"], $mixed->group_name) ?>
						<?php endforeach; ?>
					<?php else : ?>
						<?php $allAnime[] = $printRow($mixed) ?>
					<?php endif; ?>
				<?php endforeach; ?>

			</div>
		<?php endforeach; ?>
	</div>
</div>
<script>
	$(document).ready(function() {
		let d = {};
		<?php foreach ($allAnime as $anime) : ?>
			d["<?= $anime ?>"] = null,
			<?php endforeach; ?>
			$('input.autocomplete').autocomplete({
				data: d,
				limit: 25
			});
			$("#animelist").imagesLoaded()
				.progress(function(imgLoad, image) {
					let $item = $(image.img).parent();
					$item.removeClass('is-loading');
					if (!image.isLoaded) {
						$item.addClass('is-broken');
						console.log(`Image broken: ${$item.parent().next().children().first().text().trim()}`)
					}
				})
	})
</script>
<script src="https://unpkg.com/imagesloaded@4/imagesloaded.pkgd.min.js"></script>
