<?php

// class used for api for the suitecrm/sugarcrm

/**
 * This class is used to perform basic crud operations for any sugarcrm instance
 * 
 * @author Peterson Umoke <umoke10@hotmail.com>
 * @version 2.60.1 beta
 * @see https://docs.suitecrm.com/developer/api/api-4_1/
 */
class SugarRest
{
    /**
     * store the url for the suitecrm/sugarcrm instance
     *
     * @var string|url
     */
    public $sugar_url_instance;

    /**
     * store the base for the v4_1 endpoint
     *
     * @var string
     */
    private $rest_base;

    /**
     * store the url to the rest
     *
     * @var string|url
     */
    private $rest_url;

    /**
     * store the session id for the currently logged in user
     *
     * @var string|int
     */
    private $user_session_id;

    /**
     * store the username for the application
     *
     * @var string
     */
    private $username;

    /**
     * set the password for the current application
     *
     * @var string
     */
    private $password;

    /**
     * store the name of the module
     *
     * @var string
     */
    public $module_name;

    /**
     * set the parameters
     *
     * @var mixed|string
     */
    private $parameters;

    /**
     * set the method been used at the present moment
     *
     * @var string
     */
    public $method;

    /**
     * store the application name
     *
     * @var string
     */
    public $application_name;

    /**
     * store import information concerning the user current session
     * 
     * @var mixed
     */
    public $is_user_admin;

    public function __construct($sugar_url_instance = "", $username = "", $password = "")
    {
        // set default props for certain things
        if (!empty($sugar_url_instance)) $this->sugar_url_instance = $sugar_url_instance;
        if (!empty($username)) $this->username = $username;
        if (!empty($password)) $this->password = $password;
        $this->rest_base = "/service/v4_1/rest.php";
        $this->application_name = "SugarCRM Application Name";
        $this->sugar_url_instance = rtrim($this->sugar_url_instance, "/"); // remove the trailing slash from the end of the string
        $this->rest_url =  $this->sugar_url_instance . $this->rest_base; // compile the url together
        $this->module_name = "";
        $this->is_user_admin = 0;

        // automatically login to the application upon inititation
        $this->login();
    }

    /**
     * magic method to call uncreated methods in this class
     *
     * @param string $name - the name of the method
     * @param string|array $arguments - the arguments
     * @return void|mixed
     */
    public function __call($name, $arguments)
    {
        // provide a fallback for list of available calls for sending requests using curl
        $available_callables = array('rest_request', 'send_rest_request', 'restRequest');
        if (in_array($name, $available_callables)) {
            $method = $arguments[0];
            $arguments = $arguments[1];
            return $this->send_request($method, $arguments);
        }
    }

    /**
     * send curl requests
     *
     * @param string $method - the method or action to use
     * @param array $arguments - the arguments you want to pass
     * @return string|null|bool
     */
    public function send_request(string $method, array $arguments)
    {
        $this->method = $method;
        $this->parameters = $arguments;
        $curl = curl_init($this->rest_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $post = array(
            "method" => $method,
            "input_type" => "JSON",
            "response_type" => "JSON",
            "rest_data" => json_encode($arguments),
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result, 1);
    }

    /**
     * login to the application
     *
     * @return void
     */
    private function login()
    {
        $userAuth = array(
            'user_name' => $this->username,
            'password' => md5($this->password),
        );
        $appName = $this->application_name;
        $nameValueList = array();

        $args = array(
            'user_auth' => $userAuth,
            'application_name' => $appName,
            'name_value_list' => $nameValueList
        );

        $result = $this->send_request('login', $args);
        $this->user_session_id = $result['id'];
        $this->is_user_admin = $result['name_value_list']['user_is_admin']['value'];
    }

    /**
     * logout of the crm
     *
     * @return void
     */
    public function logout()
    {
        $args = array(
            'session' => $this->user_session_id
        );

        $this->send_request('logout', $args);
    }

    /**
     * retrieve a paginated list of results
     *
     * @param string $module_name
     * @param array $columns
     * @param integer $max_results
     * @param string $query
     * @param string $order_by
     * @param integer $offset
     * @param array $relationships
     * @param integer $deleted
     * @return void
     */
    public function select($module_name, $columns = array(), $max_results = 10, $query = '', $order_by = '', $offset = 0, $relationships = array(), $deleted = 0)
    {
        $this->module_name = $module_name; // store the module name
        $this->method = 'get_entry_list';

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'query' => $query,
            'order_by' => $order_by,
            'offset' => $offset,
            'select_fields' => $columns,
            'max_results' => $max_results,
            'link_name_to_fields_array' => $relationships,
            'deleted' => $deleted,
        );

        $result = $this->send_request($this->method, $entryArgs);
        return $result;
    }

    /**
     * gets all the fields of a particular module
     *
     * @param string $module_name
     * @param array $select_fields
     * @return void
     */
    public function fields($module_name, $select_fields = array())
    {
        $this->module_name = $module_name; // store the module name
        $this->method = 'get_module_fields';

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'fields' => $select_fields,
        );


        $result = $this->send_request($this->method, $entryArgs);
        return $result;
    }

    /**
     * used to get the count of a module
     *
     * @param string $module_name
     * @param string $query
     * @param integer $deleted
     * @return void
     */
    public function count($module_name, $query = '', $deleted = 0)
    {
        $this->module_name = $module_name; // store the module name
        $this->method = 'get_entries_count';

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'query' => $query,
            'deleted' => $deleted,
        );

        $result = $this->send_request($this->method, $entryArgs);
        return $result['result_count'] ?? false;
    }

    /**
     * get a single record from the crm using the id of the record
     *
     * @param string $module_name
     * @param string|array $id
     * @param array $columns
     * @param array $relationships
     * @return array
     */
    public function get($module_name, $id, $columns = array(), $relationships = array())
    {
        $this->module_name = $module_name; // store the module name
        $this->method = 'get_entries';
        $id = is_string($id) ? array($id) : $id; // convert the id to array format if its a string

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'id' => $id,
            'select_fields' => $columns,
            'link_name_to_fields_array' => $relationships,
        );

        $result = $this->send_request($this->method, $entryArgs);
        return $result;
    }

    /**
     * used to get all the relationships in a particular module
     *
     * @param string $module_name
     * @param array $select_fields
     * @return void
     */
    public function relationships($module_name, $id, $select_fields = array())
    {
        $this->module_name = $module_name; // store the module name
        $this->method = 'get_relationships';

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'fields' => $select_fields,
        );


        $result = $this->send_request($this->method, $entryArgs);
        return $result;
    }

    public function delete()
    {
        //
    }


    public function update_records()
    {
        //
    }

    public function update_record()
    {
        // 
    }

    public function update_relationships()
    {
        //
    }

    public function update_relationship()
    {
        //
    }
}
