<style>
	.table td,
	.table th {
		border: 0 !important;
	}

	.table thead {
		background-color: rgba(255, 255, 255, 0.5);
	}

	thead tr,
	tbody tr:nth-last-child(n+2) {
		border-top: 0px !important;
		border-bottom: 1px solid rgb(255, 255, 255, 0.5) !important;
	}

	tbody tr:last-child {
		border-bottom: 0px !important;
	}

	table.highlight>tbody>tr:hover {
		background-color: #d2d2d280;
	}

	#image img {
		max-width: 75%;
	}

	hr {
		width: 80%;
		border: 0;
		height: 1px;
		background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.80), rgba(0, 0, 0, 0));
	}

	#info p {
		font-size: 20px;
	}

	p.descrizione,
	p.descrizione-show,
	p.descrizione-hide,
	p.note {
		max-width: 90%;
	}

	p.descrizione-hide {
		max-height: 225px;
		overflow: hidden;
	}

	@media only screen and (min-width: 768px) {
		.borderLeft {
			border-left: 1.5px solid gray;
		}

		.table thead .th-border-left {
			border-left: 1.5px solid gray !important;
		}

		#goToAdminPage {
			float: right;
		}
	}

	@media only screen and (max-width: 768px) {
		#image {
			padding-bottom: 30px;
		}
	}
</style>

<div class="container grey lighten-3 rounded py-2">
	<?php if ($isAdmin) : ?>
		<div class="row m-0 mt-2">
			<div class="col-sm-12">
				<a href="<?= config("subdir") . "/admin/anime/edit/" . $anime->id ?>"><button id="goToAdminPage" class="btn waves-effect waves-light">Admin Page</button></a>
			</div>
		</div>
	<?php endif; ?>
	<div class="row">
		<div class="col-sm-12 center">
			<h1 class="<?= $isAdmin ? "mt-0" : "" ?>"><?= $anime->name ?></h1>
			<?php if (!empty($anime->name_en) && $anime->name_en != $anime->name) : ?>
				<h3><?= $anime->name_en ?></h3>
			<?php endif; ?>
		</div>
	</div>
	<br>
	<hr />
	<br>
	<div class="row">
		<div id="image" class="col-md-4 col-sm-12 align-self-md-center center">
			<div class="row">
				<div class="col-sm-12">
					<div class="imageLoader is-loading">
						<img class="<?= $img["cssclass"] ?>" src="<?= $img["src"] ?>" alt="<?= $img["alt"] ?>">
					</div>
				</div>
			</div>
			<?php if ($isLogged) : ?>
				<div class="row">
					<div class="col-sm-12">
						<button id="toggleFollow" class="btn waves-effect waves-light"><?= $trans(!$serie ? "global.FOLLOW" : "global.UNFOLLOW") ?></button>
					</div>
				</div>
			<?php endif; ?>
			<?php if ($serie) : ?>
				<div class="row">
					<div class="col-sm-12">
						<button id="toggleHidden" class="btn waves-effect waves-light"><?= $trans(!$serie->isHidden() ? "global.HIDE" : "global.SHOW") ?></button>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<div id="info" class="col-md-8 col-sm-12 center borderLeft">
			<div class="row">
				<div class="col-md-4 col-sm-12">
					<h4>Periodo:</h4>
					<p>
						<?php
						$date = $anime->getDateTime();
						echo $date->format("Y") . "<br/>(" . $date->format("d") . " " . substr($trans("calendar." . $date->format("F")), 0, 3) . ")";
						?>
					</p>
				</div>
				<div class="col-md-4 col-sm-12">
					<h4>Numeri:</h4>
					<p>
						<?php if (!empty($anime->episode)) : ?>
							<?php foreach ($anime->episode as $episode) : ?>
								<?= $episode->number ?> <?= $trans("codes." . $episode->type->description, $episode->number) ?><br>
							<?php endforeach; ?>
						<?php else : ?>
							???
						<?php endif; ?>
					</p>
				</div>
				<div class="col-md-4 col-sm-12">
					<h4>Stato in Italia:</h4>
					<p><?= $trans("codes." . $anime->status->description) ?></p>
				</div>
			</div>
			<?php if (!empty($anime->tags)) : ?>
				<div class="row">
					<div class="col-sm-12">
						<?php foreach ($anime->tags as $tag) : ?>
							<div class="chip"><?= $tag->name ?></div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			<hr />
			<div class="row">
				<div class="col-sm-12">
					<h4>Trama:</h4>
					<p class="descrizione descrizione-hide mx-auto"><?= $anime->description ?></p>
				</div>
			</div>
			<?php if (!empty($anime->note)) : ?>
				<div class="row">
					<div class="col-sm-12">
						<h4>Note:</h4>
						<p class="note mx-auto"><?= $anime->note ?></p>
					</div>
				</div>
			<?php endif; ?>
			<?php if (!empty($related)) : ?>
				<div class="row">
					<div class="col-sm-12">
						<h4>Link Correlati:</h4>
						<div class="center">
							<?php foreach ($related as $reletedanime) : ?>
								<a href="<?= $reletedanime["anime"]->getAnimeUrl() ?>"><?= ($reletedanime["group_position"] < $groupPosition ? "&#8639;" : ($reletedanime["group_position"] == $groupPosition ? "&#8640;" : "&#8643;")) . " " . $reletedanime["anime"]->name ?></a><br>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<br>
	<hr style="width: 95%;" />
	<br>
	<?php if ($serie) : ?>
		<div class="row">
			<div class="col-md-6 col-sm-12 offset-md-3">
				<button class="btn w-100" id="save-btn" disabled>Save</button>
			</div>
		</div>
	<?php endif; ?>
	<div class="row">
		<div class="col-sm-12">
			<table id="table" class="table responsive-table centered highlight">
				<?php foreach ($ep as $type => $group) : ?>
					<thead>
						<tr>
							<?php if ($serie && $type != "EPISODE_NONE") : ?>
								<th>
									<label>
										<input num="-1" type="checkbox" <?= $group["allViewed"] ? "checked" : "" ?> />
										<span></span>
									</label>
								</th>
								<th><?= $trans("codes." . $type, count($group) - 1) ?></th>
							<?php else : ?>
								<th colspan="2"><?= $trans("codes." . $type, count($group) - 1) ?></th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody id="<?= $type ?>">
						<?php foreach ($group["ep"] as $file) : ?>
							<tr>
								<?php if ($serie && $type != "EPISODE_NONE") : ?>
									<td>
										<label>
											<input num="<?= $file["num"] ?>" type="checkbox" <?= $file["isViewed"] ? "checked" : "" ?> /> <!-- class="filled-in" -->
											<span></span>
										</label>
									</td>
									<td><a href="<?= $file["url"] ?>" target="_blank"><?= $file["name"] ?></a></td>
								<?php else : ?>
									<td colspan="2"><a href="<?= $file["url"] ?>" target="_blank"><?= $file["name"] ?></a></td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				<?php endforeach; ?>
			</table>
		</div>
	</div>
</div>
<script src="<?= path("javascriptdir") . "/simple_checkbox_table.min.js" ?>"></script>
<?php if ($isLogged) : ?>
	<script>
		$(document).ready(function() {
			$("#toggleFollow").click(function() {
				$(this).prop("disabled", true)
				$.ajax({
					type: 'POST',
					url: "<?= config("subdir") . "/Anime/toggleFollowPost/" ?>",
					data: {
						animeId: "<?= $anime->id; ?>"
					},
					dataType: "json",
					cache: false,
					complete: function(r, ts) {
						if (r.responseJSON && r.responseJSON.success) {
							M.toast({
								html: "<?= $trans(!$serie ? "global.ADDED" : "global.REMOVED") ?>",
								classes: "rounded-pill"
							});

							setTimeout(function() {
								location.reload()
							}, 1000);
						} else {
							M.toast({
								html: "Failed" + (r.responseJSON ? ": " + r.responseJSON.error : ''),
								classes: "rounded-pill"
							});
							$("#toggleFollow").prop("disabled", false)
						}
					},
					error: function(r) {
						console.error("AjaxError:", r)
						M.toast({
							html: "Request Failed",
							classes: "rounded-pill"
						});
					}
				});
			})
		})
	</script>
<?php endif; ?>
<?php if ($serie) : ?>
	<script>
		$(document).ready(function() {
			var initdata = {
				animeId: "<?= $anime->id ?>"
			};
			var postdata = {
				...initdata
			};

			// $("#table > tbody input[type=checkbox]").change()
			$("table").simpleCheckboxTable({
				onCheckedStateChanged: function(checkbox) {
					let type = checkbox.parents("tbody").attr("id")
					if (!postdata.hasOwnProperty(type)) {
						postdata[type] = []
					}

					if (postdata[type].indexOf(checkbox.attr("num")) == -1) {
						postdata[type].push(checkbox.attr("num"))
					} else {
						postdata[type] = arrayRemove(postdata[type], checkbox.attr("num"))
						if (postdata[type].length == 0) {
							delete postdata[type]
						}
					}

					$("#save-btn").attr("disabled", JSON.stringify(postdata) == JSON.stringify(initdata))
				}
			});

			/*
			$(window).on('beforeunload', function(e) {
				if (JSON.stringify(postdata) == JSON.stringify(initdata)) {
					return
				}

				$.ajax({
					type: 'POST',
					url: "<?= config("subdir") . "/Anime/updateSeriePost/" ?>",
					data: postdata,
					dataType: "json",
					cache: false,
					complete: function(r, ts) {
						console.log(r.responseJSON);
					}
				});

				delete e['returnValue'];
			})
			*/


			$("#save-btn").click(function() {
				$.ajax({
					type: 'POST',
					url: "<?= config("subdir") . "/Anime/updateSeriePost/" ?>",
					data: postdata,
					dataType: "json",
					cache: false,
					complete: function(r, ts) {
						//console.log(r.responseJSON);
					},
					success: function(r) {
						// console.log(r)
						if (r && r.success) {
							postdata = {
								...initdata
							}
							$("#save-btn").attr("disabled", true)

							M.toast({
								html: "<?= $trans("global.SAVED") ?>",
								classes: "rounded-pill"
							});
						} else {
							M.toast({
								html: "Failed" + (r && r.error ? ": " + r.error : ''),
								classes: "rounded-pill"
							});
						}
					},
					error: function(r) {
						console.error("AjaxError:", r)
						M.toast({
							html: "Request Failed",
							classes: "rounded-pill"
						});
					}
				});
			})

			$("#toggleHidden").click(function() {
				$(this).prop("disabled", true)
				$.ajax({
					type: 'POST',
					url: "<?= config("subdir") . "/Anime/toggleHideSeriePost/" ?>",
					data: {
						animeId: "<?= $anime->id; ?>"
					},
					dataType: "json",
					cache: false,
					complete: function(r, ts) {
						$("#toggleHidden").prop("disabled", false)
					},
					success: function(r) {
						if (r && r.success) {
							M.toast({
								html: "<?= $trans(!$serie->isHidden() ? "global.HIDDEN" : "global.SHOWED") ?>",
								classes: "rounded-pill"
							});
							//$("#toggleHidden").text("<?= $trans($serie->isHidden() ? "global.HIDE" : "global.SHOW") ?>")

							setTimeout(function() {
								location.reload()
							}, 1000);
						} else {
							M.toast({
								html: "Failed" + (r && r.error ? ": " + r.error : ''),
								classes: "rounded-pill"
							});
						}
					},
					error: function(r) {
						console.error("AjaxError:", r)
						M.toast({
							html: "Request Failed",
							classes: "rounded-pill"
						});
					}
				});
			})

			function isEmpty(obj) {
				for (let key in obj) {
					if (obj.hasOwnProperty(key))
						return false;
				}
				return true;
			}

			function arrayRemove(arr, value) {
				return arr.filter(function(ele) {
					return ele != value;
				});
			}
		})
	</script>
<?php endif; ?>

<script src="<?php echo path("resourcesdir") . "/js/read_more.js" ?>"></script>

<script>
	$(document).ready(function() {
		$("#image").imagesLoaded()
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
