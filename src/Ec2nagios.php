<?php

require_once (dirname(__FILE__) . '/../aws-sdk-for-php/sdk.class.php');

class Ec2nagios {

	public static function start() {

		self::add_ec2nagios_objects_directory_to_nagios_config();
		self::make_ec2nagios_objects_directory();

		$groups = array();
		foreach (Ec2nagiosConfig::get_accounts() as $project => $option)
			$groups = array_merge_recursive($groups, self::get_groups($project, $option));

		foreach ($groups as $group_name => $instances)
			self::create_initial_service_configuration($group_name);

		$ec2nagios_config = self::create_ec2_nagios_configurtion($groups);
		
		file_put_contents(Ec2nagiosConfig::get_ec2nagios_objects_directory() . '/' . Ec2nagiosConfig::get_ec2nagios_config_filename(), $ec2nagios_config);

	}

	private static function add_ec2nagios_objects_directory_to_nagios_config() {

		$nagios_config = @file_get_contents(Ec2nagiosConfig::get_nagios_config_path());
		$line = 'cfg_dir=' . Ec2nagiosConfig::get_ec2nagios_objects_directory();
		if (strpos($nagios_config, $line) !== false)
			return;

		$nagios_config = preg_replace('/(.*)cfg_dir=([^\n]*)/ms', '$0' . "\n\n{$line}", $nagios_config);
		if (strpos($nagios_config, $line) == false)
			$nagios_config .= "\n{$line}\n";

		file_put_contents(Ec2nagiosConfig::get_nagios_config_path(), $nagios_config);

	}

	private static function make_ec2nagios_objects_directory() {

		if (file_exists(Ec2nagiosConfig::get_ec2nagios_objects_directory()))
			return;

		mkdir(Ec2nagiosConfig::get_ec2nagios_objects_directory());

	}

	private static function get_groups($project, $option) {

		$ec2 = new AmazonEC2($option);

		$groups = array();
		foreach (Ec2nagiosConfig::get_regions() as $region) {

			$ec2->set_region($region);
			$instances = $ec2->describe_instances();
			if (!$instances->isOK())
				continue;

			foreach ($instances->body->reservationSet->children() as $reservationItem) {
				foreach ($reservationItem->instancesSet->children() as $instance) {
					if ($instance->instanceState->name->to_string() != 'running')
						continue;
					$group_name = self::search_ec2nagios_tag_value($instance);
					if (!$group_name)
						continue;
					$variables = self::extract_variables($instance);
					$variables['projectName'] = $project;
					$groups[$group_name][] = $variables;
				}
			}

		}

		return $groups;

	}

	private static function search_ec2nagios_tag_value($instance) {

		foreach ($instance->tagSet->item as $tag) {
			$tag_key = $tag->key->to_string();
			$tag_value = $tag->value->to_string();
			if (strcasecmp($tag_key, Ec2nagiosConfig::get_tag_key()) === 0) {
				return $tag_value;
			}
		}

	}

	private static function create_ec2_nagios_configurtion() {

		$ec2nagios_config = '';

		foreach ($groups as $group_name => $instances) {
			foreach ($instances as $instance)
				$ec2nagios_config .= self::create_host_config($instance);
		}

		foreach ($groups as $group_name => $instances) {
			$host_names = array();
			foreach ($instances as $instance)
				$host_names[] = $instance['dnsName'];
			$ec2nagios_config .= self::create_hostgroup_config($group_name, implode(',', $host_names));
		}

		return $ec2nagios_config;

	}

	private static function create_ec2nagios_config($instances) {

		foreach ($instances as $instance) {

		}

	}

	private static function create_initial_service_configuration($group_name) {

		$config_path = Ec2nagiosConfig::get_ec2nagios_objects_directory() . '/' . $group_name . '.cfg';
		if (file_exists($config_path))
			return;

		$config = self::create_hostgroup_config_template($group_name);
		file_put_contents($config_path, $config);

	}

	private static function create_host_config($instance) {

		$ec2nagios_config = <<<EOT
define host{
	use             linux-server
        host_name       {$instance['dnsName']}
        alias           {$instance['tag.Name']}
        address         {$instance['dnsName']}
        }

EOT;

		return $ec2nagios_config;

	}

	private static function create_hostgroup_config($group_name, $members) {

		$ec2nagios_config = <<<EOT
define hostgroup{
        hostgroup_name  {$group_name}
        alias           {$group_name}
        members         {$members}
        }

EOT;

		return $ec2nagios_config;

	}

	private static function create_hostgroup_config_template($hostgroup) {

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

	private static function extract_variables($instance) {

		$variables = array(
			'instanceId' => $instance->instanceId->to_string(),
			'imageId' => $instance->imageId->to_string(),
			'instanceState' => $instance->instanceState->name->to_string(),
			'privateDnsName' => $instance->privateDnsName->to_string(),
			'dnsName' => $instance->dnsName->to_string(),
			'keyName' => $instance->keyName->to_string(),
			'instanceType' => $instance->instanceType->to_string(),
			'launchTime' => $instance->launchTime->to_string(),
			'availabilityZone' => $instance->placement->availabilityZone->to_string(),
			'kernelId' => $instance->kernelId->to_string(),
			'subnetId' => $instance->subnetId->to_string(),
			'vpcId' => $instance->vpcId->to_string(),
			'privateIpAddress' => $instance->privateIpAddress->to_string(),
			'ipAddress' => $instance->ipAddress->to_string(),
		);

		foreach ($instance->tagSet->item as $tag)
			$variables['tag.' . $tag->key->to_string()] = $tag->value->to_string();

		return $variables;

	}

}