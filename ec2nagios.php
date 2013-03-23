<?php

require_once (dirname(__FILE__) . '/aws-sdk-for-php/sdk.class.php');
require_once (dirname(__FILE__) . '/config.inc.php');

$ec2 = new AmazonEC2();

# Add EC2Nagios placeholder to nagios.cfg
$ec2nagios_config_pattern = "/{$config_begin_seperater}(.*){$config_end_seperater}/s";
$old_config = @file_get_contents($config_path);
if (!preg_match($ec2nagios_config_pattern, $old_config)) {
	$old_config = preg_replace('/(.*)cfg_file=([^\n]*)/ms', '$0' . "\n\n{$config_begin_seperater}\n{$config_end_seperater}", $old_config);
}

# List EC2 instances and generate configuration
$config = '';
$host_group_members = array();

foreach ($regions as $region) {

	// describe instances in the region.
	$ec2->set_region($region);
	$instances = $ec2->describe_instances();
	if (!$instances->isOK())
		continue;

	// create config
	foreach ($instances->body->reservationSet->children() as $reservationItem) {
		foreach ($reservationItem->instancesSet->children() as $instanceItem) {
			$group_name = $region;
			$node_dns = $instanceItem->dnsName;
			$node_ip = $use_public_dns ? $instanceItem->dnsName : $instanceItem->privateIpAddress;
			$node_name = null;
			$host_group = null;
			foreach ($instanceItem->tagSet->item as $tag) {
				$tag_key = $tag->key->to_string();
				$tag_value = $tag->value->to_string();
				if (strcasecmp($tag_key, 'Name') === 0) {
					$node_name = $tag_value;
				}
				if (strcasecmp($tag_key, $ec2nagios_tag_key) === 0) {
					$host_group = $tag_value;
				}
			}
			if ($host_group) {
				$config .= create_host_config($node_dns, $node_name, $node_ip);
				$host_group_members[$host_group][] = $node_dns;
			}
		}
	}

}

foreach ($host_group_members as $host_group => $members) {
	$config .= create_host_group_config($host_group, $members);
}

file_put_contents("{$objects_directory}/{$ec2nagios_config_file}", $config);

# Add EC2Nagios config files path to nagios.cfg
$host_groups = array_keys($host_group_members);
$nagios_config = create_nagios_config($objects_directory, $ec2nagios_config_file, $host_groups);
$new_config = preg_replace($ec2nagios_config_pattern, "{$config_begin_seperater}\n{$nagios_config}{$config_end_seperater}", $old_config);

file_put_contents($config_path, $new_config);

# Create template host_group configuration if not exists
foreach ($host_groups as $host_group) {
	$host_group_config_path = "{$objects_directory}/{$host_group}.cfg";
	if (!file_exists($host_group_config_path)) {
		$host_group_config = create_host_group_config_template($host_group);
		file_put_contents($host_group_config_path, $host_group_config);
	}
}

function create_host_config($node_dns, $node_name, $node_ip) {

	$config = <<<EOT
define host{
	use             linux-server
        host_name       {$node_dns}
        alias           {$node_name}
        address         {$node_ip}
        }

EOT;

	return $config;

}

function create_host_group_config($host_group, $members) {

	$members_concat = join($members, ',');

	$config = <<<EOT
define hostgroup{
        hostgroup_name  {$host_group}
        alias           {$host_group}
        members         {$members_concat}
        }

EOT;

	return $config;

}

function create_nagios_config($objects_directory, $ec2nagios_config_file, $host_groups) {

	$config = "cfg_file={$objects_directory}/{$ec2nagios_config_file}\n";
	foreach ($host_groups as $host_group) {
		$config .= "cfg_file={$objects_directory}/{$host_group}.cfg\n";
	}

	return $config;

}

function create_host_group_config_template($host_group) {

	$config = <<<EOT
# You can edit following lines for "{$host_group}" hostgroup

define service{
        use                     local-service
        hostgroup_name          {$host_group}
        service_description     PING
        check_command           check_ping!100.0,20%!500.0,60%
        }

EOT;

	return $config;

}
