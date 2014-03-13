<?php

require_once (dirname(__FILE__) . '/Ec2nagiosConfig.php');

Ec2nagiosConfig::set_nagios_config_path('/etc/nagios/nagios.cfg');
Ec2nagiosConfig::set_ec2nagios_objects_directory('/etc/nagios/ec2nagios');
Ec2nagiosConfig::set_ec2nagios_config_filename('ec2nagios.cfg');
Ec2nagiosConfig::set_tag_key('EC2Nagios');

Ec2nagiosConfig::set_regions(array(
	AmazonEC2::REGION_US_E1,
	AmazonEC2::REGION_US_W1,
	AmazonEC2::REGION_US_W2,
	AmazonEC2::REGION_EU_W1,
	AmazonEC2::REGION_APAC_SE1,
	AmazonEC2::REGION_APAC_NE1,
	AmazonEC2::REGION_US_GOV1,
	AmazonEC2::REGION_SA_E1,
));

Ec2nagiosConfig::set_accounts(array('project' => array(
		'key' => 'key',
		'secret' => 'secret-key',
	), ));
