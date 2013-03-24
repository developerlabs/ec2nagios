<?php

require_once (dirname(__FILE__) . '/aws-sdk-for-php/sdk.class.php');
require_once (dirname(__FILE__) . '/config.inc.php');

$ec2 = new AmazonEC2();

# Add EC2Nagios configuration directory to nagios.cfg
$nagios_config = @file_get_contents($nagios_config_path);
$directory_configuration = "cfg_dir={$ec2nagios_objects_directory}";
if (strpos($nagios_config, $directory_configuration) === false) {
	$nagios_config = preg_replace('/(.*)cfg_dir=([^\n]*)/ms', '$0' . "\n\n{$directory_configuration}", $nagios_config);
	if (strpos($nagios_config, $directory_configuration) === false)
		$nagios_config .= "\n{$directory_configuration}\n";
	file_put_contents($nagios_config_path, $nagios_config);
}

# Make EC2Nagios objects directory if not exists
if (!file_exists($ec2nagios_objects_directory))
	mkdir($ec2nagios_objects_directory);

# List EC2 instances and generate configuration
$ec2nagios_config = '';
$hostgroup_members = array();

foreach ($regions as $region) {

	// describe instances in the region.
	$ec2->set_region($region);
	$instances = $ec2->describe_instances();
	if (!$instances->isOK())
		continue;

	// create config
	foreach ($instances->body->reservationSet->children() as $reservationItem) {
		foreach ($reservationItem->instancesSet->children() as $instanceItem) {
			if ($instanceItem->instanceState->name != 'running')
				continue;
			$node_dns = $instanceItem->dnsName;
			$node_ip = $use_public_dns ? $instanceItem->dnsName : $instanceItem->privateIpAddress;
			$node_name = null;
			$hostgroup = null;
			foreach ($instanceItem->tagSet->item as $tag) {
				$tag_key = $tag->key->to_string();
				$tag_value = $tag->value->to_string();
				if (strcasecmp($tag_key, 'Name') === 0) {
					$node_name = $tag_value;
				}
				if (strcasecmp($tag_key, $ec2nagios_tag_key) === 0) {
					$hostgroup = $tag_value;
				}
			}
			if ($hostgroup) {
				$ec2nagios_config .= create_host_config($node_dns, $node_name, $node_ip);
				$hostgroup_members[$hostgroup][] = $node_dns;
			}
		}
	}

}

foreach ($hostgroup_members as $hostgroup => $members) {
	$ec2nagios_config .= create_hostgroup_config($hostgroup, $members);
}

file_put_contents("{$ec2nagios_objects_directory}/{$ec2nagios_config_filename}", $ec2nagios_config);

# Create template hostgroup configuration if not exists
$hostgroups = array_keys($hostgroup_members);
foreach ($hostgroups as $hostgroup) {
	$hostgroup_config_path = "{$ec2nagios_objects_directory}/{$hostgroup}.cfg";
	if (!file_exists($hostgroup_config_path)) {
		$hostgroup_config = create_hostgroup_config_template($hostgroup);
		file_put_contents($hostgroup_config_path, $hostgroup_config);
	}
}

function create_host_config($node_dns, $node_name, $node_ip) {

	$ec2nagios_config = <<<EOT
define host{
	use             linux-server
        host_name       {$node_dns}
        alias           {$node_name}
        address         {$node_ip}
        }

EOT;

	return $ec2nagios_config;

}

function create_hostgroup_config($hostgroup, $members) {

	$members_concat = join($members, ',');

	$ec2nagios_config = <<<EOT
define hostgroup{
        hostgroup_name  {$hostgroup}
        alias           {$hostgroup}
        members         {$members_concat}
        }

EOT;

	return $ec2nagios_config;

}

function create_hostgroup_config_template($hostgroup) {

	$ec2nagios_config = <<<EOT
# You can edit following lines for "{$hostgroup}" hostgroup

define service{
        use                     local-service
        hostgroup_name          {$hostgroup}
        service_description     PING
        check_command           check_ping!100.0,20%!500.0,60%
        }

EOT;

	return $ec2nagios_config;

}
