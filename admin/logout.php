<?php
require __DIR__ . '/../includes/bootstrap.php';

session_destroy();
redirect('login.php');
