<?php


class Conexion {
    private $host = "localhost";
    private $usuario = "root";
    private $password = "max123";
    private $baseDatos = "virtualbank";
    private $conexion;


    public function __construct() {
        $this->conexion = null;
    }

    public function conectar() {
        $this->conexion = new mysqli($this->host, $this->usuario, $this->password, $this->baseDatos);

        if ($this->conexion->connect_error) {
            die('Error de conexión (' . $this->conexion->connect_errno . '): ' . $this->conexion->connect_error);
        }
    }

    public function getConexion() {
        if ($this->conexion === null) {
            $this->conectar();
        }
        return $this->conexion;
    }

    public function cerrarConexion() {
        if ($this->conexion !== null) {
            $this->conexion->close();
            $this->conexion = null;
        }
    }
}