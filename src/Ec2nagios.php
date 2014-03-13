<?php

require_once (dirname(__FILE__) . '/../aws-sdk-for-php/sdk.class.php');

class Ec2nagios {

	public static function start() {

		self::edit_nagios_config_for_ec2nagios();
		self::make_config_directory();

		$groups = array();
		foreach (Ec2nagiosConfig::get_accounts() as $project => $option)
			$groups = array_merge_recursive($groups, self::get_groups($project, $option));

		foreach ($groups as $group_name => $instances)
			self::make_service_config($group_name);

		self::make_ec2_nagios_configurtion($groups);

	}

	private static function edit_nagios_config_for_ec2nagios() {

		$nagios_config = @file_get_contents(Ec2nagiosConfig::get_nagios_config_path());
		$line = 'cfg_dir=' . Ec2nagiosConfig::get_config_directory();
		if (strpos($nagios_config, $line) !== false)
			return;

		$nagios_config = preg_replace('/(.*)cfg_dir=([^\n]*)/ms', '$0' . "\n\n{$line}", $nagios_config);
		if (strpos($nagios_config, $line) == false)
			$nagios_config .= "\n{$line}\n";

		file_put_contents(Ec2nagiosConfig::get_nagios_config_path(), $nagios_config);

	}

	private static function make_config_directory() {

		if (file_exists(Ec2nagiosConfig::get_config_directory()))
			return;

		mkdir(Ec2nagiosConfig::get_config_directory());

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
					$variables['groupName'] = $group_name;
					$groups[$group_name][] = $variables;
				}
			}

		}

		return $groups;

	}

	private static function make_service_config($group_name) {

		$config_path = Ec2nagiosConfig::get_config_directory() . '/' . $group_name . '.cfg';
		if (file_exists($config_path))
			return;

		$config = self::generate_service_config($group_name);
		file_put_contents($config_path, $config);

	}

	private static function make_ec2_nagios_configurtion($groups) {

		$configs = array();

		foreach ($groups as $group_name => $instances)
			foreach ($instances as $instance)
				$configs[] = self::generate_host_config($instance);

		foreach ($groups as $group_name => $instances)
			$configs[] = self::generate_hostgroup_config($group_name, self::generate_host_names($instances));

		$projects = array();
		foreach ($groups as $group_name => $instances)
			foreach ($instances as $instance)
				$projects[$instance['projectName']][] = $instance;

		foreach ($projects as $project_name => $instances)
			$configs[] = self::generate_hostgroup_config($project_name, self::generate_host_names($instances));

		$config = implode("\n\n", $configs);

		file_put_contents(Ec2nagiosConfig::get_config_directory() . '/' . Ec2nagiosConfig::get_config_file_name(), $config);

	}

	private static function generate_host_names($instances) {
		$host_names = array();
		foreach ($instances as $instance)
			$host_names[] = self::generate_host_name($instance);
		return implode(',', $host_names);
	}

	private static function generate_host_name($instance) {
		return self::render(Ec2nagiosConfig::get_host_name_template(), $instance);
	}

	private static function generate_host_config($instance) {
		return self::render(Ec2nagiosConfig::get_host_template(), array_merge($instance, array('hostName' => self::generate_host_name($instance))));
	}

	private static function generate_hostgroup_config($group_name, $members) {
		return self::render(Ec2nagiosConfig::get_hostgroup_template(), array(
			'groupName' => $group_name,
			'members' => $members
		));
	}

	private static function generate_service_config($groupName) {
		return self::render(Ec2nagiosConfig::get_service_template(), array('groupName' => $groupName));
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

	private static function render($template, $variables) {

		foreach ($variables as $key => $value)
			$template = str_replace('${' . $key . '}', $value, $template);

		return $template;

	}

}
