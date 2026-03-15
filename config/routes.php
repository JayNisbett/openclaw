<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['api/openclaw/webhook/(:num)'] = 'webhook/inbound/$1';
$route['api/openclaw/schema'] = 'webhook/schema';
$route['api/openclaw'] = 'api_openclaw/data';
$route['api/openclaw/(:any)'] = 'api_openclaw/data/$1';
$route['api/openclaw/(:any)/(:any)'] = 'api_openclaw/data/$1/$2';
$route['api/openclaw/(:any)/(:any)/(:any)'] = 'api_openclaw/data/$1/$2/$3';
$route['api/openclaw/(:any)/(:any)/(:any)/(:any)'] = 'api_openclaw/data/$1/$2/$3/$4';
