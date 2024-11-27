<?php
require_once "db.php";

$onjconn = new Conexion();
$conn = $onjconn->getConexion();
// Función para agregar un nuevo comentario
function addComentario($cedula, $nombre, $apellido, $comentario) {
    global $conn;
	$link = $conn;
	
	if ($link->connect_errno) {
			echo "Falló la conexión a MySQL: (" . $link->connect_errno . ") " . $link->connect_error;
		}else{
			$sql ="INSERT INTO contacto(cedula, nombre, apellido, comentario)"."VALUES('$cedula', '$nombre', '$apellido', '$comentario')";

			$result = $link->query($sql);
			if($result){
                $_SESSION['comentario']='ok';
            }else{
                $_SESSION['comentario']='fail';
            }
        }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener datos del formulario
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $comentario = $_POST['textarea'];

    addComentario($cedula, $nombre, $apellido, $comentario);


}
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto</title>
    <style>
        #caja {
            background-color: hsl(188, 100%, 100%, .5);
            padding: 20px;
            margin-top: 100px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            position: relative;
            z-index: 2; /* Asegura que el contenido esté encima del difuminado negro */
        }
    </style>
</head>
<body>
    <footer class="container-fluid" id="gtco-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-6" id="contact">
                    <form action="" method="POST">
                        <h4> Contactanos </h4>
                        <?php 
                            if (isset($_SESSION["comentario"]) && $_SESSION["comentario"] == "ok") {
                                echo "<h4>Comentario enviado correctamente!</h4>";
                                unset($_SESSION["comentario"]);
                            }
                            if (isset($_SESSION["comentario"]) && $_SESSION["comentario"] == "fail") {
                                echo "<h4>No se pudo enviar el comentario!</h4>";
                                unset($_SESSION["comentario"]);
                            }
                        ?>
                        <input type="text" class="form-control" id="cedula" name="cedula" placeholder="Cedula" required>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" required>
                        <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Apellido">
                        <textarea class="form-control" name="textarea" placeholder="Mensaje"></textarea>
                        <button type="submit"class="submit-button">Enviar<i class="fa fa-angle-right" aria-hidden="true"></i></button>
                    </form>
                </div>
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-6" id="caja">
                            <h4>Información de Contacto</h4>
                                <ul class="nav flex-column services-nav">
                                    <li class="nav-item"><strong>Teléfono:</strong> +123 456 7890</li>
                                    <li class="nav-item"><strong>Email:</strong> soportevirtualbank@virtualbank.com</li>
                                    <li class="nav-item"><strong>Dirección:</strong> Calle 100 #45-32, Barranquilla, Colombia</li>
                                </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>