<!doctype html>
<!--
	Solution by GetTemplates.co
	URL: https://gettemplates.co
-->
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- awesone fonts css-->
    <link href="css/font-awesome.css" rel="stylesheet" type="text/css">
    <!-- owl carousel css-->
    <link rel="stylesheet" href="owl-carousel/assets/owl.carousel.min.css" type="text/css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <title>VirtualBank</title>
    <style>

    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light bg-transparent" id="gtco-main-nav">
    <div class="container"><a class="navbar-brand" href="index.php">VirtualBank</a>
        <button class="navbar-toggler" data-target="#my-nav" onclick="myFunction(this)" data-toggle="collapse"><span
                class="bar1"></span> <span class="bar2"></span> <span class="bar3"></span></button>
        <div id="my-nav" class="collapse navbar-collapse">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=inicio">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=servicios">Servicios</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=contacto">Contacto</a></li>
            </ul>
            <form class="form-inline my-2 my-lg-0">
                <a href="login.php" class="btn btn-outline-dark my-2 my-sm-0 mr-3 text-uppercase" target="_blank">Iniciar sesión</a> 
                <a href="registro.php" class="btn btn-info my-2 my-sm-0 text-uppercase" target="_blank">Registrarse</a>
            </form>
        </div>
    </div>
</nav>
<main>
    <?php
    // Verificar la página seleccionada
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
        switch ($page) {
            case 'inicio':
                include('inicio.php');
                break;
            case 'servicios':
                include('servicios.php');
                break;
            case 'contacto':
                include('contacto.php'); // Incluir la página de contacto
                break;
            default:
                echo "<h1>Página no encontrada</h1>";
        }
    }else{
        echo "<div class='container-fluid gtco-banner-area'>
                <div class='container'>
                    <div class='row'>
                        <div class='col-md-6'>
                            <h1> Tu dinero, a un clic. </h1>
                            <p> Olvídate de trámites engorrosos. Abre tu cuenta de ahorro en línea en <span>pocos minutos</span> y solicita un préstamo de manera <span>rápida y fácil</span>. Con nuestra plataforma intuitiva, administrar tu dinero nunca había sido tan <span>sencillo</span>.</p>
                            </div>
                            <div class='col-md-6'>
                                <div class='card'><img class='card-img-top img-fluid' src='images/banner-img.png' alt=''></div>
                            </div>
                        </div>
                    </div>
                </div>";
    }
    ?>
</main>

<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="js/jquery-3.3.1.slim.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<!-- owl carousel js-->
<script src="owl-carousel/owl.carousel.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
