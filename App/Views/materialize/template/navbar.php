<?php $root = config("subdir") . "/" ?>
<div class="navbar-fixed" id="navbar">
	<nav class="blue-grey darken-4">
		<div class="nav-wrapper">
			<a href="<?= $root ?>" class="brand-logo center">A</a>
			<a href="#" data-target="mobile-sidenav" class="sidenav-trigger"><i class="material-icons">menu</i></a>
			<ul class="left hide-on-med-and-down">
				<li><a href="<?= $root ?>" class="text-uppercase">Home</a></li>
				<!--<li><a href="#" class="text-uppercase">In Corso</a></li>-->
				<li><a href="<?= $root . "Anime/Archive" ?>" class="text-uppercase">Archivio</a></li>
			</ul>
			<ul class="right hide-on-med-and-down">
				<li><a class="waves-effect waves-teal d-block"><i class="material-icons">search</i></a></li>
				<?php if (Mvc\Helpers\Session::has("user")) : ?>
					<li><a href="#" class="dropdown-trigger text-uppercase" data-target="dropdownUser">User<i class="material-icons right">arrow_drop_down</i></a></li>
				<?php else : ?>
					<li><a href="<?= $root . "User" ?>" class="text-uppercase">Login</a></li>
				<?php endif; ?>
			</ul>
		</div>
	</nav>
</div>
<ul class="sidenav" id="mobile-sidenav">
	<li><a href="<?= $root ?>" class="text-uppercase">Home</a></li>
	<!--<li><a href="#" class="text-uppercase">In Corso</a></li>-->
	<li><a href="<?= $root . "Anime/Archive" ?>" class="text-uppercase">Archivio</a></li>
	<?php if (Mvc\Helpers\Session::has("user")) : ?>
		<li><a href="#" class="dropdown-trigger text-uppercase" data-target="dropdownUser-mobile">User<i class="material-icons right">arrow_drop_down</i></a></li>
	<?php else : ?>
		<li><a href="<?= $root . "user" ?>" class="text-uppercase">Login</a></li>
	<?php endif; ?>
</ul>

<?php if (Mvc\Helpers\Session::has("user")) : ?>
	<ul id="dropdownUser" class="dropdown-content">
		<li><a href="<?= $root . "User" ?>">User</a></li>
		<?php if (Mvc\Models\UserModel::getCurrentUser()->isAdmin()) : ?>
			<li><a href="<?= $root . "Admin" ?>">Admin</a></li>
		<?php endif; ?>
		<li><a href="#" id="logout">Logout</a></li>
		<?php if (config("debug", false)) : ?>
			<li><a href="#" id="refresh">RefreshUser</a></li>
		<?php endif; ?>
	</ul>
	<ul id="dropdownUser-mobile" class="dropdown-content">
		<li><a href="<?= $root . "User" ?>">User</a></li>
		<?php if (Mvc\Models\UserModel::getCurrentUser()->isAdmin()) : ?>
			<li><a href="<?= $root . "Admin" ?>">Admin</a></li>
		<?php endif; ?>
		<li><a href="#" id="logout">Logout</a></li>
		<?php if (config("debug", false)) : ?>
			<li><a href="#" id="refresh">RefreshUser</a></li>
		<?php endif; ?>
	</ul>
<?php endif; ?>

<script>
	$(document).ready(function() {
		$('.sidenav').sidenav({
			draggable: true,
			preventScrolling: true
		});
		$(".dropdown-trigger").dropdown({
			coverTrigger: false,
			hover: false
		});
	});
</script>

<script>
	$(document).ready(function() {
		$("#logout").click(function() {
			$.ajax({
				type: 'POST',
				url: "<?= config("subdir") . "/user/logoutPost" ?>",
				dataType: "json",
				cache: false,
				success: function(r) {
					location.href = "<?= $root ?>"
				},
				error: function(r) {
					console.log(r);
				}
			});
		})

		<?php if (config("debug", false)) : ?>
			// ! Only Dev
			$("#refresh").click(function() {
				$.ajax({
					type: 'POST',
					url: "<?= config("subdir") . "/user/refresh" ?>",
					dataType: "json",
					cache: false,
					complete: function(r, ts) {
						location.reload()
					},
					error: function(response) {
						console.log(response);
					}
				});
			})
		<?php endif; ?>
	});
</script>

<?php config("debug", false) ? \Mvc\Helpers\Debugger::GetInstance()->echoDebug() : ""; ?>
