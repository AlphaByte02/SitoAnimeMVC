<!DOCTYPE html>
<html lang="it">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="robots" content="noindex">

		<meta name="theme-color" content="#666"/>

		<link rel="icon" type="image/png" href="<?= path("resourcesdir") . "/images/favicon-32x32.png" ?>" sizes="32x32"/>
		<link rel="icon" type="image/png" href="<?= path("resourcesdir") . "/images/favicon-16x16.png" ?>" sizes="16x16"/>

		<title>Alpha Anim | <?= $title ?></title>
		<meta property="og:title" content="Alpha Anim | <?= $title ?>">
		<meta name="description" content="Streaming Anime">
		<meta property="og:site_name" content="Alpha Anim">

		<meta property="og:image" content="<?= $ogimg ?? path("resourcesdir") . "/images/Kani_Nayuta.jpg"; ?>">

		<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
		<link rel="stylesheet" href="<?= path("resourcesdir") . "/styles/bootstrapImport/bootstrap-grid.min.css" ?>">
		<link rel="stylesheet" href="<?= path("resourcesdir") . "/styles/bootstrapImport/bootstrap-utilities.min.css" ?>">
		<!--<link rel="stylesheet" href="<?= path("resourcesdir") . "/styles/bootstrapImport/bootstrap-reboot.min.css" ?>">-->

		<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>

		<style>
			.container {
				margin-top: 10px;
				margin-bottom: 10px;
			}
		</style>
	</head>
	<body>
