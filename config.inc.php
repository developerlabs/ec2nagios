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

$nagios_config_path = '/etc/nagios/nagios.cfg';
$ec2nagios_objects_directory = '/etc/nagios/ec2nagios';
$ec2nagios_config_filename = 'ec2nagios.cfg';
$ec2nagios_tag_key = 'EC2Nagios';

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
