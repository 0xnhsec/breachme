<?php
session_start();
session_destroy();
header('Location: /public/login.php');
exit;
