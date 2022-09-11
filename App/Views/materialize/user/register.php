<style>
	.card-alert .card-content {
		padding: 10px 20px;
	}

	.card-alert i {
		font-size: 20px;

		position: relative;
		top: 2px;
	}

	.card-alert .alert-circle {
		position: relative;
		top: -5px;
		left: -2px;

		display: inline-block;

		width: 40px;

		vertical-align: bottom;
		white-space: nowrap;

		border-radius: 1000px;
	}

	.card-alert .single-alert {
		line-height: 42px;
	}

	.card-alert button {
		font-size: 20px;

		position: absolute;
		top: 5px;
		right: 10px;

		color: #fff;
		border: none;
		background: none;

		cursor: pointer;
	}

	.card-alert .card .card-content {
		padding: 20px 40px 20px 20px;
	}

	.card-alert .card-action i {
		top: 0;

		margin: 0;
	}
</style>

<div class="container grey lighten-3 rounded py-2">
	<div class="row">
		<div class="col-sm-12 center">
			<h1>Sign Up</h1>
		</div>
	</div>
	<div id="error" class="card-alert card red" style="display: none;">
		<div class="card-content white-text">
			<p><i class="material-icons">error</i></p>
		</div>
		<button type="button" class="close white-text" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true">×</span>
		</button>
	</div>
	<form id="form" method="post">
		<div class="row m-0">
			<div class="input-field col-sm-6 offset-sm-3">
				<i class="material-icons prefix pt-2">person_outline</i>
				<input type="text" id="username" name="username" required>
				<label for="username" class="center-align">Username</label>
			</div>
		</div>
		<div class="row m-0">
			<div class="input-field col-sm-6 offset-sm-3">
				<i class="material-icons prefix pt-2">lock_outline</i>
				<input type="password" id="password" name="password" required>
				<label for="password">Password</label>
			</div>
		</div>
		<div class="row m-0">
			<div class="input-field col-sm-6 offset-sm-3">
				<i class="material-icons prefix pt-2">lock_outline</i>
				<input type="password" id="repeat_password" name="repeat_password" required>
				<label for="repeat_password">Repeat Password</label>
			</div>
		</div>
		<div class="row m-0">
			<div class="col-sm-6 offset-sm-3 center">
				<h6 class="m-0"><a href="<?= config("subdir") . "/user/login" ?>">Sing In</a></h6>
			</div>
		</div>
		<div class="row m-0">
			<div class="input-field col-sm-6 offset-sm-3">
				<button class="btn cyan waves-effect waves-light right" type="submit" id="submit" name="submit">Submit
					<i class="material-icons right">send</i>
				</button>
			</div>
		</div>
	</form>
</div>

<script>
	$(document).ready(function() {
		$(".card-alert .close").click(function() {
			$(this)
				.closest(".card-alert")
				.fadeOut("slow");
		});

		$("#form").submit(function(e) {
			e.preventDefault();

			if ($(this).find("#password").val() != $(this).find("#repeat_password").val()) {
				$("#error").fadeIn("slow");
				$("#error p").html("Password MUST coincide")
				return;
			}

			let serializeData = $(this).serialize();

			let inp = $(this).find(":input")
			inp.prop("disabled", true)

			$.ajax({
				type: 'POST',
				url: "<?= config("subdir") . "/user/registerPost" ?>",
				data: serializeData,
				dataType: "json",
				cache: false,
				complete: function(r, ts) {
					inp.prop("disabled", false)
					console.log(r.responseJSON);

					if (r.responseJSON.success) {
						location.href = "<?= $root ?>"
					} else {
						$("#error").fadeIn("slow");
						$("#error p").html("Error: " + r.responseJSON.error)
					}
				},
				error: function(response) {
					console.log("Error:")
					console.log(response);
				}
			});
		})

	})
</script>
