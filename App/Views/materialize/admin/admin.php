<style>
	.card-image img {
		max-width: 200px;
	}

	.card:hover {
		background-color: #475d67 !important;
	}
</style>
<div class="container grey lighten-3 rounded py-2">
	<div class="row">
		<div class="col-sm-12 center">
			<h1>Area Admin</h1>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12 col-md-6 center">
			<a href="<?= config("subdir") . "/admin/anime/create" ?>">
				<div class="card blue-grey darken-1">
					<div class="card-image">
						<img class="mx-auto py-3 responsive-img" src="<?= path("resourcesdir") . "/images/admin/plus.png" ?>">
						<span class="card-title">Aggiungi</span>
					</div>
					<div class="card-content white-text">
						<p>Aggiungi Anime</p>
					</div>
				</div>
			</a>
		</div>
		<div class="col-sm-12 col-md-6 center">
			<a href="<?= config("subdir") . "/admin/anime/view" ?>">
				<div class="card blue-grey darken-1">
					<div class="card-image">
						<img class="mx-auto py-3 responsive-img" src="<?= path("resourcesdir") . "/images/admin/table.png" ?>">
						<span class="card-title">Visualizza</span>
					</div>
					<div class="card-content white-text">
						<p>Visualizza Anime</p>
					</div>
				</div>
			</a>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12 col-md-6 center">
			<a href="<?= config("subdir") . "/admin/user/create" ?>">
				<div class="card blue-grey darken-1">
					<div class="card-image">
						<img class="mx-auto py-3 responsive-img" src="<?= path("resourcesdir") . "/images/admin/user_add.png" ?>">
						<span class="card-title">Aggiungi</span>
					</div>
					<div class="card-content white-text">
						<p>Aggiungi Utenti</p>
					</div>
				</div>
			</a>
		</div>
		<div class="col-sm-12 col-md-6 center">
			<a href="<?= config("subdir") . "/admin/user/view" ?>">
				<div class="card blue-grey darken-1">
					<div class="card-image">
						<img class="mx-auto py-3 responsive-img" src="<?= path("resourcesdir") . "/images/admin/user_management.png" ?>">
						<span class="card-title">Visualizza</span>
					</div>
					<div class="card-content white-text">
						<p>Visualizza Utenti</p>
					</div>
				</div>
			</a>
		</div>
	</div>
</div>
