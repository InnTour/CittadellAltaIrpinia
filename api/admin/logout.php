<?php
session_start();
session_destroy();
header('Location: /api/admin/login.php');
exit;
