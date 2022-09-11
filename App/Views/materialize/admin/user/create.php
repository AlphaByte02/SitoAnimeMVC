<style>
	.required {
		color: red !important;
	}
</style>

<div class="container grey lighten-3 rounded my-2">
	<div class="row">
		<div class="col-sm-12 center">
			<h1>Add User</h1>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<form id="form" method="post">
				<div class="row m-0">
					<div class="input-field col-sm-6 offset-sm-3">
						<button class="btn cyan waves-effect waves-light right" type="submit" id="submit" name="submit">Create
							<i class="material-icons right">send</i>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	$(document).ready(function() {
		$("#form").submit(function(e) {
			e.preventDefault();
			let serializeData = $(this).serialize();

			let inp = $(this).find(":input")
			inp.prop("disabled", true)

			$.ajax({
				type: 'POST',
				url: "<?= config("subdir") . "/admin/createUserPost/" ?>",
				data: serializeData,
				dataType: "json",
				cache: false,
				complete: function(r, ts) {
					inp.prop("disabled", false)

					if (r.responseJSON && r.responseJSON.success) {
						M.toast({
							html: "<?= $trans("global.ADDED") ?>",
							classes: "rounded-pill"
						});

						setTimeout(function() {
							location.href = "<?= config("subdir") . "/admin/edit/" ?>" + r.responseJSON.userId
						}, 1000);
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
