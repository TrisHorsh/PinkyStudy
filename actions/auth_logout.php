<?php
session_start();
$base_url = "http://localhost/PinkyStudy";
session_destroy();
session_unset();
header("Location: $base_url/actions/auth_logout.php");