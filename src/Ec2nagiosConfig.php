<?php
class Ec2nagiosConfig {

	private static $nagios_config_path;
	private static $config_directory;
	private static $config_file_name;
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

	public static function get_config_directory() {
		return self::$config_directory;
	}

	public static function set_config_directory($config_directory) {
		self::$config_directory = $config_directory;
	}

	public static function get_config_file_name() {
		return self::$config_file_name;
	}

	public static function set_config_file_name($config_file_name) {
		self::$config_file_name = $config_file_name;
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
