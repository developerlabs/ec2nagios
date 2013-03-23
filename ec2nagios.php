<?php

require_once (dirname(__FILE__) . '/aws-sdk-for-php/sdk.class.php');
require_once (dirname(__FILE__) . '/config.inc.php');

$ec2 = new AmazonEC2();
$ec2nagios_config_path = "{$objects_directory}/{$ec2nagios_config_file}";

# Add ec2nagios.cfg file line to nagios.cfg
$old_config = @file_get_contents($config_path);
$pattern = '/(.*)cfg_file=([^\n]*)/ms';
preg_match($pattern, $old_config, $match);
if ($match) {
	if (strpos($match[2], $ec2nagios_config_file) === false) {
		$replacement = '$0' . "\n\n# EC2Nagios configuration\ncfg_file={$ec2nagios_config_path}";
		$new_config = preg_replace($pattern, $replacement, $old_config);
		file_put_contents($config_path, $new_config);
	}
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

file_put_contents($ec2nagios_config_path, $config);

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
