<?php
// Legacy entry point maintained for backward compatibility.
// Route requests through the main index router.
$_SERVER['REQUEST_URI'] = '/api';
require __DIR__ . '/index.php';
