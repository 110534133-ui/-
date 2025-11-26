<?php
   $servername = "127.0.0.1";
   $username = "root";
   $password = "";
   $dbname = "lamian";
   
   $conn = new mysqli($servername, $username, $password, $dbname);
   $conn->set_charset("utf8mb4");
   
   if ($conn->connect_error) {
       die("連線失敗: " . $conn->connect_error);
   }
   ?>