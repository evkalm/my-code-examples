<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>{{ $title }}</title>
	<meta name="description" content="@yield('description')">

	<link rel="icon" href="{{ asset('favicon.ico') }}">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
	
	<link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
	<link rel="stylesheet" href="{{ asset('build/main-personal-account.css') }}">

</head>
<body>
	<div class="container">
	
		@include('layouts.header')

		<div id="h1-container">
			<h1>{{ $h1 }}</h1>
		</div>

		<div style="min-height: 45vh;">
			@yield('content')
		</div>

		@include('layouts.footer')

		@yield('scripts')

		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
	</div>
</body>
</html>