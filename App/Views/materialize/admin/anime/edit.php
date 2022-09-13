<style>
	.required {
		color: red !important;
	}
</style>

<div class="container grey lighten-3 rounded my-2">
	<div class="row">
		<div class="col-sm-12 center">
			<h1>Edit Anime</h1>
		</div>
	</div>
	<div class="row">
		<div class="col-sm center">
			<h5><a href="<?= $anime->getAnimeUrl() ?>">Vai alla Pagina</a></h5>
		</div>
		<div class="col-sm center">
			<h5><a href="<?= config("subdir") . "/admin" ?>">Vai ad Admin</a></h5>
		</div>
		<div class="col-sm center">
			<h5><a href="<?= config("subdir") . "/admin/anime/createfrom/" . ($anime->id ?: "") ?>">Duplica Anime</a></h5>
		</div>
		<div class="col-sm center">
			<h5><a href="<?= config("subdir") . "/admin/anime/create" ?>">Aggiungi Anime</a></h5>
		</div>
		<div class="col-sm center">
			<h5><a href="<?= config("subdir") . "/admin/anime/view" ?>">Vai alla View</a></h5>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<form id="form" method="post">
				<div class="row m-0">
					<div class="input-field col-sm-2 offset-sm-5">
						<input class="center" type="text" id="anime_id" name="anime_id" required readonly value="<?= $anime->id ?: "" ?>">
						<label for="anime_id" class="center-align">Anime ID</label>
					</div>
				</div>
				<div class="row m-0">
					<div class="input-field col-sm-6 offset-sm-3">
						<input type="text" id="anime_name" name="anime_name" required value="<?= $anime->name ?: "" ?>">
						<label for="anime_name" class="center-align">Anime Name JP <span class="required">*</span></label>
					</div>
				</div>
				<div class="row m-0">
					<div class="input-field col-sm-6 offset-sm-3">
						<input type="text" id="anime_name_en" name="anime_name_en" value="<?= $anime->name_en ?: "" ?>">
						<label for="anime_name_en" class="center-align">Anime Name EN</label>
					</div>
				</div>
				<div class="row m-0">
					<div class="input-field col-sm-6 offset-sm-3">
						<input type="url" id="anime_image_url" name="anime_image_url" value="<?= $anime->imageurl ?: "" ?>">
						<label for="anime_image_url" class="center-align">Anime External Image Url</label>
					</div>
				</div>
				<div class="row m-0">
					<div class="input-field col-sm-6 offset-sm-3">
						<input type="text" class="datepicker" id="anime_release_date" name="anime_release_date" required value="<?= $anime->release_date ?: "" ?>">
						<label for="anime_release_date" class="center-align">Release Date <span class="required">*</span></label>
					</div>
				</div>
				<div class="row m-0">
					<div class="input-field col-sm-6 offset-sm-3">
						<select id="anime_state" name="anime_state">
							<option value="201" <?= $anime->status->code == "201" ? "selected" : "" ?>><?= $trans("codes.ANIME_STATUS_INPROGRESS") ?></option>
							<option value="202" <?= $anime->status->code == "202" ? "selected" : "" ?>><?= $trans("codes.ANIME_STATUS_CONCLUDED") ?></option>
							<option value="203" <?= $anime->status->code == "203" ? "selected" : "" ?>><?= $trans("codes.ANIME_STATUS_ANNOUNCED") ?></option>
							<option value="204" <?= $anime->status->code == "204" ? "selected" : "" ?>><?= $trans("codes.ANIME_STATUS_INEDITED") ?></option>
						</select>
						<label>Anime State <span class="required">*</span></label>
					</div>
				</div>
				<div class="row m-0">
					<div class="input-field col-sm-12 col-md-3">
						<input type="number" id="anime_number_ep" name="anime_number_ep" min="0" required value="<?= $anime->episode[51]->number ?: "0" ?>">
						<label for="anime_number_ep" class="center-align">Episode Number <span class="required">*</span></label>
					</div>
					<div class="input-field col-sm-12 col-md-3">
						<input type="number" id="anime_number_oav" name="anime_number_oav" min="0" required value="<?= $anime->episode[52]->number ?: "0" ?>">
						<label for="anime_number_oav" class="center-align">OAV Number <span class="required">*</span></label>
					</div>
					<div class="input-field col-sm-12 col-md-3">
						<input type="number" id="anime_number_special" name="anime_number_special" min="0" required value="<?= $anime->episode[53]->number ?: "0" ?>">
						<label for="anime_number_special" class="center-align">Special Number <span class="required">*</span></label>
					</div>
					<div class="input-field col-sm-12 col-md-3">
						<input type="number" id="anime_number_movie" name="anime_number_movie" min="0" required value="<?= $anime->episode[54]->number ?: "0" ?>">
						<label for="anime_number_movie" class="center-align">Movie Number <span class="required">*</span></label>
					</div>
				</div>
				<div class="row m-0">
					<div class="input-field col-sm-6 offset-sm-3">
						<textarea id="anime_description" name="anime_description" class="materialize-textarea" required><?= $anime->description ?: "" ?></textarea>
						<label for="anime_description">Anime Description <span class="required">*</span></label>
					</div>
				</div>
				<div class="row m-0">
					<div class="input-field col-sm-6 offset-sm-3">
						<textarea id="anime_note" name="anime_note" class="materialize-textarea"><?= $anime->note ?: "" ?></textarea>
						<label for="anime_note">Anime Note</label>
					</div>
				</div>
				<div class="row m-0">
					<div class="chips chips-autocomplete input-field col-sm-8 offset-sm-2"></div>
				</div>
				<div class="row m-0">
					<div class="input-field col-sm-6 offset-sm-3">
						<input type="text" id="anime_path" name="anime_path" required value="<?= $anime->path ?: "" ?>">
						<label for="anime_path" class="center-align">Anime Path <span class="required">*</span></label>
					</div>
				</div>
				<div class="row m-0">
					<div class="col-sm-6 offset-sm-3">
						<fieldset>
							<legend>Groups Section</legend>
							<div class="row m-0">
								<div class="input-field col-sm-12 col-md-8">
									<input type="text" id="anime_group_name" name="anime_group_name" class="autocomplete" autocomplete="off" value="<?= $groupname ?>">
									<label for="anime_group_name" class="center-align">Anime Group Name</label>
								</div>
								<div class="input-field col-sm-12 col-md-4">
									<input type="number" id="anime_group_position" name="anime_group_position" min="1" value="<?= $groupposition ?>">
									<label for="anime_group_position" class="center-align">Position</label>
								</div>
							</div>
						</fieldset>
					</div>
				</div>
				<div class="clearfix my-3 w-50 center-block">
					<button class="btn cyan waves-effect waves-light left" type="reset">Reset<i class="material-icons right">redo</i></button>
					<button class="btn cyan waves-effect waves-light right" type="submit" id="submit" name="submit">EDIT<i class="material-icons right">edit</i></button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	$(document).ready(function() {
		let groupsName = {};
		<?php foreach ($groupsname as $groupName) : ?>
			groupsName["<?= $groupName ?>"] = null;
		<?php endforeach; ?>

		let tagsName = {};
		<?php foreach ($tags as $tag) : ?>
			tagsName["<?= $tag->name ?>"] = null;
		<?php endforeach; ?>

		let animeTags = [];
		<?php foreach ($anime->tags as $tag) : ?>
			animeTags.push({
				"tag": "<?= $tag->name ?>"
			});
		<?php endforeach; ?>

		$('input.autocomplete').autocomplete({
			data: groupsName,
			limit: 10
		});

		$('.chips-autocomplete').chips({
			placeholder: "Tags",
			autocompleteOptions: {
				data: tagsName,
				limit: Infinity,
				minLength: 1
			},
			data: animeTags
		});

		$('.datepicker').datepicker({
			autoClose: true,
			firstDay: 1,
			format: "yyyy-mm-dd", //'dd/mm/yyyy',
			i18n: {
				months: [
					"<?= $trans("calendar.January") ?>",
					"<?= $trans("calendar.February") ?>",
					"<?= $trans("calendar.March") ?>",
					"<?= $trans("calendar.April") ?>",
					"<?= $trans("calendar.May") ?>",
					"<?= $trans("calendar.June") ?>",
					"<?= $trans("calendar.July") ?>",
					"<?= $trans("calendar.August") ?>",
					"<?= $trans("calendar.September") ?>",
					"<?= $trans("calendar.October") ?>",
					"<?= $trans("calendar.November") ?>",
					"<?= $trans("calendar.December") ?>",
				],
				monthsShort: [
					"<?= substr($trans("calendar.January"), 0, 3) ?>",
					"<?= substr($trans("calendar.February"), 0, 3) ?>",
					"<?= substr($trans("calendar.March"), 0, 3) ?>",
					"<?= substr($trans("calendar.April"), 0, 3) ?>",
					"<?= substr($trans("calendar.May"), 0, 3) ?>",
					"<?= substr($trans("calendar.June"), 0, 3) ?>",
					"<?= substr($trans("calendar.July"), 0, 3) ?>",
					"<?= substr($trans("calendar.August"), 0, 3) ?>",
					"<?= substr($trans("calendar.September"), 0, 3) ?>",
					"<?= substr($trans("calendar.October"), 0, 3) ?>",
					"<?= substr($trans("calendar.November"), 0, 3) ?>",
					"<?= substr($trans("calendar.December"), 0, 3) ?>",
				],
				weekdays: [
					"<?= $trans("calendar.Sunday") ?>",
					"<?= $trans("calendar.Monday") ?>",
					"<?= $trans("calendar.Tuesday") ?>",
					"<?= $trans("calendar.Wednesday") ?>",
					"<?= $trans("calendar.Thursday") ?>",
					"<?= $trans("calendar.Friday") ?>",
					"<?= $trans("calendar.Saturday") ?>",
				],
				weekdaysShort: [
					"<?= substr($trans("calendar.Sunday"), 0, 3) ?>",
					"<?= substr($trans("calendar.Monday"), 0, 3) ?>",
					"<?= substr($trans("calendar.Tuesday"), 0, 3) ?>",
					"<?= substr($trans("calendar.Wednesday"), 0, 3) ?>",
					"<?= substr($trans("calendar.Thursday"), 0, 3) ?>",
					"<?= substr($trans("calendar.Friday"), 0, 3) ?>",
					"<?= substr($trans("calendar.Saturday"), 0, 3) ?>",
				],
				weekdaysAbbrev: [
					"<?= substr($trans("calendar.Sunday"), 0, 1) ?>",
					"<?= substr($trans("calendar.Monday"), 0, 1) ?>",
					"<?= substr($trans("calendar.Tuesday"), 0, 1) ?>",
					"<?= substr($trans("calendar.Wednesday"), 0, 1) ?>",
					"<?= substr($trans("calendar.Thursday"), 0, 1) ?>",
					"<?= substr($trans("calendar.Friday"), 0, 1) ?>",
					"<?= substr($trans("calendar.Saturday"), 0, 1) ?>",
				]
			}
		});
	});
</script>
<script>
	$(document).ready(function() {
		$("#form button[type='reset']").on('click', function(e) {
			e.preventDefault();
			this.form.reset(); // forcing reset event
			M.updateTextFields(); //updating labels from inputs
		});

		$("#form").submit(function(e) {
			e.preventDefault();
			let serializeData = $(this).serialize();

			let inp = $(this).find(":input")
			inp.prop("disabled", true)

			let tags = M.Chips.getInstance($(".chips.chips-autocomplete")).getData();
			for (let tag of tags) {
				serializeData += "&anime_tags[]=" + tag.tag;
			}

			$.ajax({
				type: 'POST',
				url: "<?= config("subdir") . "/admin/editAnimePost/" ?>",
				data: serializeData,
				dataType: "json",
				cache: false,
				complete: function(r, ts) {
					inp.prop("disabled", false)

					if (r.responseJSON && r.responseJSON.success) {
						M.toast({
							html: "<?= $trans("global.EDITED") ?>",
							classes: "rounded-pill"
						});

						/*setTimeout(function() {
							location.reload()
						}, 1000);*/
					} else {
						M.toast({
							html: "Failed" + (r.responseJSON && r.responseJSON.error ? ": " + r.responseJSON.error : ''),
							classes: "rounded-pill"
						});
					}
				},
				error: function(r) {
					console.error("AjaxError:", r)
				}
			});
		})

	})
</script>
