<?php

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dirección incompleta</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f2f5;
            font-family: Arial, sans-serif;
        }
        .denied-box {
            text-align: center;
            padding: 40px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            max-width: 500px;
        }
        .denied-box img {
            width: 180px;
            margin-bottom: 20px;
        }
        .denied-box h1 {
            font-size: 32px;
            color: #d9534f;
            margin-bottom: 15px;
        }
        .denied-box p {
            font-size: 18px;
            color: #555;
            margin-bottom: 25px;
        }
        .btn-volver {
            display: inline-block;
            padding: 12px 25px;
            background: #0275d8;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
            transition: background 0.3s ease;
        }
        .btn-volver:hover {
            background: #025aa5;
        }
    </style>
</head>
<body>

<div class="denied-box">
    <img src="../img/zona-prohibida.png" alt="Advertencia"> 
    <h1>Dirección incompleta</h1>
    <p>Debes completar una dirección antes de continuar.</p>
    <a href="../detalle_compra.php" class="btn-volver">Volver</a>
</div>

</body>
</html>
