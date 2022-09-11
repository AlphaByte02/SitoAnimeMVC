<style>
	#buttons {
		margin-bottom: 10px;
		height: 40px;
		text-align: center;
		width: 85%;
		margin: 0 auto;
	}

	#buttons button {
		width: 60px;
	}

	#buttons button.disabled {
		cursor: not-allowed;
	}
</style>
<div class="container center">
	<?php if (!empty($anime) && !empty($anime["src"])) : ?>
		<div class="row">
			<div class="col-sm-12">
				<h1 id="title"><a href="<?= $anime["pageurl"] ?: "#" ?>"><?= $anime["name"] ?></a> <?= " - " . $trans("codes." . $anime["type"]->description) . " " . $anime["num"] ?></h1>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<div id="buttons" class="my-2">
					<button value="-1" class="btn waves-effect waves-light left <?= !$btn["prev"] ? "disabled" : "" ?>">&lt;</button>
					<button value="1" class="btn waves-effect waves-light right <?= !$btn["post"] ? "disabled" : "" ?>">&gt;</button>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<div id="player">
					<div id="video">
						<video controls="controls" name="media" class="responsive-video">
							<source src="<?= $anime["src"] ?>" type="<?= $anime["mime"] ?>">
						</video>
					</div>
				</div>
			</div>
		</div>
	<?php else : ?>
		<div class="row">
			<div class="col-sm-12">
				<h1>L'anime:</h1>
				<h2 id="title"><?= $anime["name"] . " - " . $trans("codes." . $anime["type"]->description) . " " . $anime["num"] ?></h2>
				<h3>o non esiste o si sono riscontrati problemi con il file</h3>
			</div>
		</div>
	<?php endif; ?>
</div>

<script>
	$(document).ready(function() {
		function changeEp(nEps) {
			let path = window.location.pathname.replace(/\/+$/, '');
			var ar = path.split("/");
			var n = ar[ar.length - 1];
			var nep = parseInt(n) + parseInt(nEps);
			if (nep >= 0) {
				ar[ar.length - 1] = nep.toString().padStart(2, '0');
				window.location.pathname = ar.join("/");
			}
		}

		$("button:not(.disabled)").click(function(e) {
			changeEp(e.target.value)
		});

		$("video").on("ended", function() {
			if ($("#buttons button:last-child").is(":not(.disabled)")) {
				changeEp(1)
			}
		})
	});
</script>
