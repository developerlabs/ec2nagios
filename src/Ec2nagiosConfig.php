<?php
class Ec2nagiosConfig {

	private static $nagios_config_path;
	private static $ec2nagios_objects_directory;
	private static $ec2nagios_config_filename;
	private static $tag_key;
	private static $regions;
	private static $accounts;
	private static $host_name_template;
	private static $host_template;
	private static $hostgroup_template;
	private static $service_template;

	public static function get_nagios_config_path() {
		return self::$nagios_config_path;
	}

	public static function set_nagios_config_path($nagios_config_path) {
		self::$nagios_config_path = $nagios_config_path;
	}

	public static function get_ec2nagios_objects_directory() {
		return self::$ec2nagios_objects_directory;
	}

	public static function set_ec2nagios_objects_directory($ec2nagios_objects_directory) {
		self::$ec2nagios_objects_directory = $ec2nagios_objects_directory;
	}

	public static function get_ec2nagios_config_filename() {
		return self::$ec2nagios_config_filename;
	}

	public static function set_ec2nagios_config_filename($ec2nagios_config_filename) {
		self::$ec2nagios_config_filename = $ec2nagios_config_filename;
	}

	public static function get_tag_key() {
		return self::$tag_key;
	}

	public static function set_tag_key($tag_key) {
		self::$tag_key = $tag_key;
	}

	public static function get_regions() {
		return self::$regions;
	}

	public static function set_regions($regions) {
		self::$regions = $regions;
	}

	public static function get_accounts() {
		return self::$accounts;
	}

	public static function set_accounts($accounts) {
		self::$accounts = $accounts;
	}

	public static function get_host_name_template() {
		return self::$host_name_template;
	}

	public static function set_host_name_template($host_name_template) {
		self::$host_name_template = $host_name_template;
	}

	public static function get_host_template() {
		return self::$host_template;
	}

	public static function set_host_template($host_template) {
		self::$host_template = $host_template;
	}

	public static function get_hostgroup_template() {
		return self::$hostgroup_template;
	}

	public static function set_hostgroup_template($hostgroup_template) {
		self::$hostgroup_template = $hostgroup_template;
	}

	public static function get_service_template() {
		return self::$service_template;
	}

	public static function set_service_template($service_template) {
		self::$service_template = $service_template;
	}

}
