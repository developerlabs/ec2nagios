<?php

require_once (dirname(__FILE__) . '/aws-sdk-for-php/sdk.class.php');
require_once (dirname(__FILE__) . '/config.inc.php');

$ec2 = new AmazonEC2();

# Add ec2nagios.cfg file line to nagios.cfg
$old_config = @file_get_contents($config_path);
$pattern = '/(.*)cfg_file=([^\n]*)/ms';
preg_match($pattern, $old_config, $match);
if ($match) {
	if (strpos($match[2], $ec2nagios_config_file) === false) {
		$replacement = '$0' . "\n\n# EC2Nagios configuration\ncfg_file={$objects_directory}/{$ec2nagios_config_file}";
		$new_config = preg_replace($pattern, $replacement, $old_config);
		file_put_contents($config_path, $new_config);
	}
}
