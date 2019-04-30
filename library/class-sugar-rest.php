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

    /**
     * store the results from the server
     *
     * @var mixed
     */
    private $results;

    /**
     * option to make results json or not
     *
     * @var bool
     */
    private $is_json_response;

    public function __construct($sugar_url_instance = "", $username = "", $password = "", $jsonResponse = true)
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
        $this->is_json_response = $jsonResponse;

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
        $this->user_session_id = $result['id'] ?? 0;
        $this->is_user_admin = $result['name_value_list']['user_is_admin']['value'] ?? 0;
        $this->results = $result;

        if (!empty($result['name']) && $result['name'] === 'Invalid Login' && $result['number'] === 10) {
            $this->results = $result;
            throw new Exception("Login Failed, Please Check Username and Password: " . json_encode($result), 1);
            return false;
        }

        return true;
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
        $this->results = true;
    }

    /**
     * get the currently logged in user
     *
     * @return string|void
     */
    public function current_user_id()
    {
        $args = array(
            'session' => $this->user_session_id
        );

        $data = $this->send_request('get_user_id', $args);
        $this->results = $data;
        return $data['id'];
    }

    /**
     * check if user is session is still active
     *
     * @return boolean
     */
    public function is_logged_in()
    {
        return $this->results = !empty($this->user_session_id) ? true : false;
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

        // print_r($relationships);
        // die();

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'query' => $query,
            'order_by' => $order_by,
            'offset' => $offset,
            'select_fields' => $columns,
            'link_name_to_fields_array' => $relationships,
            'max_results' => $max_results,
            'deleted' => $deleted,
        );

        $result = $this->send_request($this->method, $entryArgs);
        $this->results = $result;
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
        $this->results = $result;
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
        $this->results = $result;
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
        $this->results = $result;
        return $result;
    }

    /**
     * get the relationships of mdoules
     *
     * @param string $module_name
     * @param string $id
     * @param string $link_field_name
     * @param string $related_module_query
     * @param array $related_fields
     * @param array $related_module_link_name_to_fields_array
     * @param integer $deleted
     * @param string $order_by
     * @param integer $offset
     * @param integer $limit
     * @return void
     */
    public function relationships($module_name, $id, $link_field_name = '', $related_module_query = '', $related_fields = array(), $related_module_link_name_to_fields_array = array(), $deleted = 0, $order_by = '', $offset = 0, $limit = 100)
    {
        $this->module_name = $module_name; // store the module name
        $this->method = 'get_relationships';

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'module_id' => $id,
            'link_field_name' => $link_field_name,
            'related_module_query' => $related_module_query,
            'related_fields' => $related_fields,
            'related_module_link_name_to_fields_array' => $related_module_link_name_to_fields_array,
            'deleted' => $deleted,
            'order_by' => $order_by,
            'offset' => $offset,
            'limit' => $offset
        );


        $result = $this->send_request($this->method, $entryArgs);
        $this->results = $result;
        return $result;
    }

    /**
     * update or create records
     *
     * @param string $module_name
     * @param array $parameters - name_value pair array
     * @return void
     */
    public function sets($module_name, $parameters)
    {
        $this->module_name = $module_name; // store the module name
        $this->method = 'set_entries';

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'name_value_lists' => $parameters,
        );

        $result = $this->send_request($this->method, $entryArgs);
        $this->results = $result;
        return $result;
    }

    /**
     * update or create a single record
     *
     * @param string $module_name
     * @param array $parameters - - name_value pair array
     * @return void
     */
    public function set($module_name, $parameters)
    {
        $this->module_name = $module_name; // store the module name
        $this->method = 'set_entry';

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'name_value_list' => $parameters,
        );

        $result = $this->send_request($this->method, $entryArgs);
        $this->results = $result;
        return $result;
    }

    /**
     * update or create relationship
     *
     * @param string $module_name
     * @param string $id
     * @param string $link_field_name
     * @param array $related_ids
     * @param array $parameters
     * @param integer $delete
     * @return void
     */
    public function update_relationship($module_name, $id, $link_field_name = '', $related_ids = array(), $parameters = array(), $delete = 0)
    {
        $this->module_name = $module_name; // store the module name
        $this->method = 'set_relationships';

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_name' => $this->module_name,
            'module_id' => $id,
            'link_field_name' => $link_field_name,
            'related_ids' => $related_ids,
            'name_value_lists' => $parameters,
            'delete' => $delete
        );

        $result = $this->send_request($this->method, $entryArgs);
        $this->results = $result;
        return $result;
    }

    /**
     * update arrays of realtionships
     *
     * @param array $module_names
     * @param array $module_ids
     * @param array $link_field_names
     * @param array $related_ids
     * @param array $parameters
     * @param array $delete_array
     * @return mixed
     */
    public function update_relationships($module_names, $module_ids, $link_field_names, $related_ids, $parameters, $delete_array)
    {
        $this->module_name = $module_names; // store the module name
        $this->method = 'set_relationships';

        // set the args
        $entryArgs = array(
            'session' => $this->user_session_id,
            'module_names' => $this->module_name,
            'module_ids' => $module_ids,
            'link_field_names' => $link_field_names,
            'related_ids' => $related_ids,
            'name_value_lists' => $parameters,
            'delete_array' => $delete_array
        );

        $result = $this->send_request($this->method, $entryArgs);
        $this->results = $result;
        return $result;
    }

    /**
     * delete a record(s) using it id
     *
     * @param string|mixed $id
     * @return bool
     */
    public function delete($module_name, $id)
    {
        $this->module_name = $module_name; // store the module name
        $fetch_data = $this->get($module_name, $id, ['id', 'name', 'deleted']);

        if ($fetch_data['entry_list']) {
            foreach ($fetch_data['entry_list'] as $i => $main_data) {
                $this->set(
                    $module_name,
                    array(
                        array(
                            'name' => 'id',
                            'value' => $main_data['id']
                        ),
                        array(
                            'name' => 'deleted',
                            'value' => 1
                        )
                    )
                );
            }

            $this->results = true;
            return true;
        }

        $this->results = false;
        return false;
    }

    /**
     * use this to automatically convert the response to json
     *
     * @return string|void|bool|mixed
     */
    private function toJSON()
    {
        if ($this->is_json_response) {
            header('Content-Type: application/json');
            echo json_encode($this->results);
        }
    }

    /**
     * example demo request
     *
     * @return void
     */
    public function example()
    {
        $entryArgs = array(
            //Session id - retrieved from login call
            'session' => $this->user_session_id,
            //Module to get_entry_list for
            'module_name' => 'Accounts',
            //Filter query - Added to the SQL where clause,
            // 'query' => "accounts.billing_address_city = 'Ohio'",
            // 'query' => "accounts.name LIKE '%simply%'",
            //Order by - unused
            'query' => "",
            'order_by' => '',
            //Start with the first record
            //Return the id and name fields
            'offset' => 0,
            'select_fields' => array('id', 'name',),
            //Link to the "contacts" relationship and retrieve the
            //First and last names.
            'link_name_to_fields_array' => array(
                // array(
                //     'name' => 'contacts',
                //     'value' => array(
                //         'first_name',
                //         'last_name',
                //     ),
                // ),
            ),
            // show a large result
            'max_results' => 10000000000000000000000000000000,
            //Do not show deleted
            'deleted' => 0,
        );
        $result = $this->send_request('get_entry_list', $entryArgs);
        $this->results = $result;

        return $result;
    }

    /**
     * once the class is been destroyed
     */
    public function __destruct()
    {
        $this->toJSON();
    }
}
