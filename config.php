<?php

require_once (dirname(__FILE__) . '/src/Ec2nagiosConfig.php');

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

Ec2nagiosConfig::set_host_name_template('${dnsName}');

Ec2nagiosConfig::set_host_template(
<<<EOT
define host{
	use             linux-server
        host_name       \${hostName}
        alias           \${tag.Name}
        address         \${dnsName}
        }

EOT
);

Ec2nagiosConfig::set_hostgroup_template(
<<<EOT
define hostgroup{
        hostgroup_name  \${groupName}
        alias           \${groupName}
        members         \${members}
        }
EOT
);

Ec2nagiosConfig::set_service_template(
<<<EOT
# You can edit following lines for "\${groupName}" hostgroup

define service{
        use                     local-service
        hostgroup_name          \${groupName}
        service_description     PING
        check_command           check_ping!100.0,20%!500.0,60%
        }
EOT
);
