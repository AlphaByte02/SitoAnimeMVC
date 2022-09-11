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

	i.material-icons {
		vertical-align: middle;
	}
</style>
<div class="container-fluid grey lighten-3 rounded my-2">
	<div class="row">
		<div class="col-sm-12 center">
			<h1>Visualizza Utenti</h1>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<table id="table" class="table responsive-table centered highlight">
				<thead style="background-color: rgba(255,255,255,0.5);">
					<tr>
						<th>ID</th>
						<th>UserName</th>
						<th>Level</th>
						<th>Last Login</th>
						<th>Registration Date</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($users as $user) : ?>
						<tr>
							<td><a href="#"><i class="tiny material-icons">edit</i> <?= $user->id ?></a></td>
							<td><?= $user->username ?></td>
							<td><?= $trans("codes." . $user->level->description) ?></td>
							<td><?= $user->last_login ?? "-" ?></td>
							<td><?= $user->registration_date ?? "-" ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
