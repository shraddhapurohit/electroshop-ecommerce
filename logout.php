<?php
// logout.php
require_once 'includes/db.php';
session_destroy();
redirect('index.php');
