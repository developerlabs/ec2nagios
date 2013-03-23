<?php

require_once (dirname(__FILE__) . '/aws-sdk-for-php/sdk.class.php');
require_once (dirname(__FILE__) . '/config.inc.php');

$ec2 = new AmazonEC2();
