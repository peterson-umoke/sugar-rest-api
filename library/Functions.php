<?php

// require_once dirname(dirname(__FILE__)) . "/config/configs.php";

class Functions
{
	public $restSession;

	public function __construct()
	{
		global $sugar_configs;
		$this->Sugarlogin($sugar_configs["user_name"], $sugar_configs["password"]);
	}

	function call($method, $parameters, $url = "")
	{
		global $sugar_configs;

		$curl = curl_init($sugar_configs['sugar_url']);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$json = json_encode($parameters);
		$postArgs = array(
			'method' => $method,
			'input_type' => 'JSON',
			'response_type' => 'JSON',
			'rest_data' => $json,
		);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postArgs);

		$response = curl_exec($curl);
		print_r($response);
		// Convert the result from JSON format to a PHP array
		$result = json_decode($response);
		/*if ( !is_object($result) ) {
				print_r($response);
				 die("Error handling result.\n");
			}*/
		if (!isset($result->id)) {
			//die("Error: {$result->name} - {$result->description}\n.");
		}
		//print_r($result);
		return $result;
	}

	function Sugarlogin($username, $password)
	{
		global $sugar_configs;
		$parameters = array(

			'user_auth' => array(

				'user_name' => $username,

				'password' => md5($password),

			),
		);
		// $login_parameters = array(
		// "user_auth" => array(
		// "user_name" => $username,
		// "password" => md5($password),
		// //"version" => "1"
		// ),
		// //"application_name" => "RestTest",
		// //"name_value_list" => array(),
		// );
		$login_result = $this->call("login", $parameters, $sugar_configs['sugar_url']);

		//$session_id = $login_result->id;
		$this->restSession = $login_result->id ?? 0;
	}


	function login($username, $password, $contactName, $contactPassword)
	{
		global $sugar_config;
		$fields_array = array('first_name', 'last_name', 'account_name', 'picture');

		$parameters = array(

			'session' => $this->restSession,                                 //Session ID
			'module_name' => 'Contacts',                             //Module name
			'query' => "contacts_cstm.portal_user_password_c='" . MD5($contactPassword) . "' And contacts_cstm.email_address_c='" . $contactName . "'",   //Where condition without "where" keyword
			'order_by' => "",                 //$order_by
			'offset'  => 0,                                               //offset
			'select_fields' => $fields_array,                      //select_fields
			'link_name_to_fields_array' => array(array()), //optional
			'max_results' => 5,                                        //max results                  
			'deleted' => 'false',                                        //deleted
		);
		$contact_login_result = $this->call("get_entry_list", $parameters, $sugar_config['parent_site_url'] . '/rest.php');
		$this->LoginResult = $contact_login_result;
		return $contact_login_result;
	}

	function register($username, $password, $param)
	{
		global $sugar_config;

		//$this->Sugarlogin($username,$password);
		$password = MD5("password");
		//$array[]=array("name"=>"password","value"=>$password);

		$parameters = array(

			'session' => $this->restSession,                                 //Session ID
			'module_name' => 'Contacts',                             //Module name
			'name_value_list' => $param,                                        //deleted
		);

		$result = $this->call('set_entry', $parameters, $sugar_config['parent_site_url'] . '/rest.php');

		return true;
	}
	function set_entry($module, $param)
	{
		global $sugar_config;

		//$this->Sugarlogin($username,$password);
		$password = MD5("password");
		//$array[]=array("name"=>"password","value"=>$password);

		$parameters = array(

			'session' => $this->restSession,                                 //Session ID
			'module_name' => $module,                             //Module name
			'name_value_list' => $param,                                        //deleted
		);

		$result = $this->call('set_entry', $parameters, $sugar_config['parent_site_url'] . '/rest.php');

		return $result;
	}

	function get_relationships($module, $module_id1, $relate_module, $field)
	{
		global $sugar_config;

		//$this->Sugarlogin($username,$password);
		$password = MD5("password");
		//$array[]=array("name"=>"password","value"=>$password);

		$parameters = array(
			'session' => $this->restSession,
			'module_name' => $module,
			'module_id' => $module_id1,
			'link_field_name' => $relate_module,
			'related_module_query' => '',
			'related_fields' => $field,
			'related_module_link_name_to_fields_array' => array(),
			'deleted' => false,
			'order_by' => 'created_date',
		);
		$result = $this->call('get_relationships', $parameters, $sugar_config['parent_site_url'] . '/rest.php');

		return $result;
	}
	function set_relationship($module1, $module_id1, $relate_module, $relate_module_id, $param)
	{
		global $sugar_config;

		//$this->Sugarlogin($username,$password);
		$password = MD5("password");
		//$array[]=array("name"=>"password","value"=>$password);

		$parameters = array(

			'session' => $this->restSession,                                 //Session ID
			'module_name' => $module1,                             //Module name
			'module_id' => $module_id1,
			'link_field_name' => $relate_module,
			'related_ids' => array(array("$relate_module_id")),
		);
		$result = $this->call('set_relationship', $parameters, $sugar_config['parent_site_url'] . '/rest.php');

		return $result;
	}
	function getEntry($module, $id, $fields)
	{
		global $sugar_config;
		$fields_array = array('first_name', 'last_name', 'account_name', 'picture', 'email1');
		//$this->Sugarlogin("","");
		$parameters = array(

			'session' => $this->restSession,                                 //Session ID
			'module_name' => $module,                             //Module name
			'query' => strtolower($module) . ".id='$id'",   //Where condition without "where" keyword
			'order_by' => "",                 //$order_by
			'offset'  => 0,                                               //offset
			'select_fields' => $fields, //$fields_array,                      //select_fields
			'link_name_to_fields_array' => array(array()), //optional
			'max_results' => 5,                                        //max results                  
			'deleted' => 'false',                                        //deleted
		);
		$contact_login_result = $this->call("get_entry_list", $parameters, $sugar_config['parent_site_url'] . '/rest.php');

		$this->LoginResult = $contact_login_result;
		$modStrings = array();

		//$returnFields = $this->getFields($module, true);
		$data = array();
		//print_r($contact_login_result);exit();
		foreach ($contact_login_result->entry_list as $num => $case) {
			foreach ($case->name_value_list as $fieldNum => $field) {
				$data[$field->name] = $field->value;
			}
		}
		return $data;
	}
}
