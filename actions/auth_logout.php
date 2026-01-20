<?php
session_start();
$base_url = "http://localhost/PinkyStudy";
session_destroy();
header("Location: $base_url/actions/auth_logout.php");