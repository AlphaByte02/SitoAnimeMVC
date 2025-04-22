<link rel="stylesheet" href="<?= path("styledir") . "/image-loading.css" ?>">
<style>
	img {
		max-height: 300px;
	}

	p {
		font-size: 20px;
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
			<h2>Hello <?= $user->username ?></h2>
		</div>
	</div>
	<div class="row center">
		<div class="col-sm-4 align-self-center">
			<div class="row">
				<div class="col-sm-12">
					<h4>In Visualizzazione</h4>
					<p><?= count($series["inprogress"] ?? []) ?></p>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<h4>Tag Più Visti</h4>
					<p>
						<?php foreach ($mostViewedTags as $tag) : ?>
							<?= $tag["name"] . "<br>" ?>
						<?php endforeach; ?>
					</p>
				</div>
			</div>
		</div>
		<div class="col-sm-4 align-self-center">
			<!--<img style="border-radius: 25%;" src="https://avatars.dicebear.com/4.5/api/human/<?= $user->username ?>.svg?w=256&h=256" alt="user-ico">-->
			<img style="border-radius: 25%;" src="https://api.dicebear.com/9.x/adventurer/svg?seed=<?= $user->username ?>&size=256" alt="user-ico">
		</div>
		<div class="col-sm-4 align-self-center">
			<div class="row">
				<div class="col-sm-12">
					<h4>Anime Conclusi</h4>
					<p><?= count($series["concluded"] ?? []) ?></p>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<h4>Da Vedere</h4>
					<p><?= count($series["added"] ?? []) ?></p>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12 center">
			<h4>Anime</h4>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12 center">
			<ul class="tabs tabs-fixed-width z-depth-1">
				<li class="tab <?= empty($series["inprogress"]) ? "disabled" : "" ?>"><a class="active" href="#inprogress"><?= $trans("codes.SERIES_STATUS_INPROGRESS", 2) ?></a></li>
				<li class="tab <?= empty($series["concluded"]) ? "disabled" : "" ?>"><a href="#concluded"><?= $trans("codes.SERIES_STATUS_CONCLUDED", 2) ?></a></li>
				<li class="tab <?= empty($series["added"]) ? "disabled" : "" ?>"><a href="#added"><?= $trans("codes.SERIES_STATUS_ADDED", 2) ?></a></li>
				<!--<li class="tab disabled"><a href="#all"><?= $trans("codes.SERIES_ALL", 2) ?></a></li>-->
				<li class="tab <?= empty($series["hidden"]) ? "disabled" : "" ?>"><a href="#hidden"><?= $trans("codes.SERIES_STATUS_HIDDEN", 2) ?></a></li>
			</ul>
		</div>
	</div>
	<?php $printTab = function ($animes, $visualNum = 4) { ?>
		<?php $c = 0; ?>

		<?php $printRow = function ($anime, $visualNum) { ?>
			<div class="<?= "col-md-" . (12 / $visualNum) ?> col-sm-12">
				<a href="<?= $anime->getAnimeUrl() ?>">
					<div class="container hoverable center">
						<div class="row mb-1">
							<div class="col-sm-12 mt-2">
								<div class="imageLoader is-loading mx-auto">
									<img loading="lazy" src="<?= $anime->getImgUrl() ?>" alt="AnimeCover" class="responsive-img">
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
			<?php return $anime->name; ?>
		<?php }; ?>

		<?php if (empty($animes)) : ?>
			<div class="row">
				<div class="col-sm-12">
					<h4 class="center">No Anime in this Tab!</h4>
				</div>
			</div>
			<?php return; ?>
		<?php endif; ?>
		<?php foreach ($animes as $anime) : ?>
			<?php if ($c % $visualNum == 0 && $c != 0) : ?>
</div>
<?php endif; ?>
<?php if ($c % $visualNum == 0) : ?>
	<div class="row">
	<?php endif; ?>

	<?php $printRow($anime, $visualNum) ?>

	<?php $c++; ?>
<?php endforeach; ?>
	</div>
<?php }; ?>
<div id="inprogress" class="row tabAnime">
	<div class="container">
		<?php $printTab($series["inprogress"]) ?>
	</div>
</div>
<div id="concluded" class="row tabAnime">
	<div class="container">
		<?php $printTab($series["concluded"]) ?>
	</div>
</div>
<div id="added" class="row tabAnime">
	<div class="container">
		<?php $printTab($series["added"]) ?>
	</div>
</div>
<div id="hidden" class="row tabAnime">
	<div class="container">
		<?php $printTab($series["hidden"]) ?>
	</div>
</div>
</div>

<script>
	$(document).ready(function() {
		$(".tabAnime").imagesLoaded()
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
