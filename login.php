<?php
session_start();
require_once "db.php";

$onjconn = new Conexion();
$conn = $onjconn->getConexion();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $link = $conn;
    
    if ($link->connect_errno) {
        echo "Falló la conexión a MySQL: (" . $link->connect_errno . ") " . $link->connect_error;
    } else {
        $cedula = $_POST['cedula'];
        $password = $_POST['password'];
        
        // Consulta para obtener nombre, apellido, cedula, password y el número de cuenta
        $sql = "SELECT cliente.nombre, cliente.apellido, cliente.cedula, cliente.password, cuenta.numero
                FROM cliente
                LEFT JOIN cuenta ON cliente.cedula = cuenta.cedula_cliente
                WHERE cliente.cedula = '$cedula'";

        $result = $link->query($sql);
        
        if ($fila = $result->fetch_assoc()) {
            if (password_verify($password, $fila['password'])) {
                // Iniciar sesión y almacenar datos en sesión
                $_SESSION['login'] = 'ok';
                $_SESSION['nombre'] = $fila['nombre'];
                $_SESSION['apellido'] = $fila['apellido'];
                $_SESSION['cedula'] = $fila['cedula'];
                $_SESSION['numero_cuenta'] = $fila['numero']; // Guardamos el número de cuenta en la sesión

                // Redirigir al dashboard
                header('Location: dashboard.php');
            } else {
                $_SESSION['login'] = 'fail';
            }
        }
    }
    mysqli_close($link);
}
?>
<?php 
if(isset($_SESSION['login'])){
  header("Location: dashboard.php");
}else{
?>
<!DOCTYPE html>
<!-- Coding By CodingNepal - youtube.com/codingnepal -->
<html lang="es" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
  </head>
  <body>
    <div class="center">
      <h1>Iniciar sesión</h1>
      <?php

        if (isset($_SESSION["login"]) && $_SESSION["login"] == "fail") {
            echo "<h4>Cedula o contraseña incorrecta</h4>";
            unset($_SESSION["login"]);
        }
        ?>
      <form method="POST">
        <div class="txt_field">
          <input type="text" id="cedula" name="cedula" required>
          <span></span>
          <label>Cedula</label>
        </div>
        <div class="txt_field">
          <input type="password" id="password" name="password" required>
          <span></span>
          <label>Contraseña</label>
        </div>
        <!-- <div class="pass">Olvidaste tu contraseña?</div> -->
        <input type="submit" value="Login">
        <div class="signup_link">
          No eres cliente? <a href="registro.php">Registrarse</a>
        </div>
      </form>
    </div>
<?php
  }
?>
  </body>
</html>
