<?php

CFCredentials::set(array(
	'development' => array(
		'key' => 'key',
		'secret' => 'secret-key',
		'default_cache_config' => '',
		'certificate_authority' => false
	),
	'@default' => 'development'
));

$config_path = '/etc/nagios/nagios.cfg';
$objects_directory = '/etc/nagios/objects';

$regions = array(
	AmazonEC2::REGION_US_E1,
	AmazonEC2::REGION_US_W1,
	AmazonEC2::REGION_US_W2,
	AmazonEC2::REGION_EU_W1,
	AmazonEC2::REGION_APAC_SE1,
	AmazonEC2::REGION_APAC_NE1,
	AmazonEC2::REGION_US_GOV1,
	AmazonEC2::REGION_SA_E1,
);

$use_public_dns = false;
