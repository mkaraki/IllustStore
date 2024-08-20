<?php
require_once(__DIR__ . '/vendor/autoload.php');

DB::$host = 'db';
DB::$user = 'illustStore';
DB::$password = 'illustStore';
DB::$dbName = 'illustStore';
DB::$encoding = 'utf8';

const IMG_SERVER_BASE = "http://localhost:7092";
