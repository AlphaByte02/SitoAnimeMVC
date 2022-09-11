<style>
	.table td,
	.table th {
		border: 0 !important;
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

	.trunc {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		max-width: 175px;
	}

	.left-align-important {
		text-align: left !important;
	}

	i.material-icons {
		vertical-align: middle;
	}
</style>

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

			$("#table tbody tr").filter(function() {
				let text = $(this).find("td:not(:nth-child(4)):not(:nth-child(n+7):nth-last-child(n+2))").text().toLowerCase()
				$(this).toggle(text.indexOf(value) != -1)
			});
		}
	});
</script>

<div class="container-fluid grey lighten-3 rounded my-2">
	<div class="row">
		<div class="col-sm-12 center">
			<h1>Visualizza Anime</h1>
		</div>
	</div>
	<div class="row">
		<div class="input-field col-sm-12">
			<label for="searchArea">Search Anime</label>
			<input id="searchArea" type="text" class="autocomplete" autocomplete="off">
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<table id="table" class="table responsive-table centered highlight">
				<thead style="background-color: rgba(255,255,255,0.5);">
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Name EN</th>
						<th>ImageUrl</th>
						<th>Status</th>
						<th>Release Date</th>
						<th>Descrizione</th>
						<th>Note</th>
						<th>Path</th>
						<th>Group Name</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($animes as $anime) : ?>
						<tr>
							<td><a href="<?= config("subdir") . "/admin/anime/edit/" . $anime["anime"]->id ?>"><i class="tiny material-icons">edit</i> <?= $anime["anime"]->id ?></a></td>
							<td class="trunc"><?= $anime["anime"]->name ?></td>
							<td class="trunc"><?= $anime["anime"]->name_en ?? "-" ?></td>
							<td class="trunc"><?= $anime["anime"]->imageurl ?? "-" ?></td>
							<td><?= $trans("codes." . $anime["anime"]->status->description) ?></td>
							<td><?= $anime["anime"]->release_date ?></td>
							<td class="left-align-important trunc"><?= substr($anime["anime"]->description, 0, strpos($anime["anime"]->description, "<br>") ?: strlen($anime["anime"]->description)) ?></td>
							<td class="<?= !empty($anime["anime"]->note) ? "left-align-important" : "" ?> trunc"><?= !empty($anime["anime"]->note) ? substr($anime["anime"]->note, 0, strpos($anime["anime"]->note, "<br>") ?: strlen($anime["anime"]->note)) : "-" ?></td>
							<td class="trunc"><?= $anime["anime"]->path ?></td>
							<td class="trunc"><?= $anime["group"]["name"] ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
