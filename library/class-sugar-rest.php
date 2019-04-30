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
    private $module_name;

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
    private $method;

    /**
     * store the application name
     *
     * @var string
     */
    public $application_name;

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
     * used to send curl requests
     *
     * @param string $method - the method or action to use
     * @param array $arguments - the arguments you want to pass
     * @return string|null|bool
     */
    public function send_request(string $method, array $arguments)
    {
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
     * used login to the application
     *
     * @return int|bool
     */
    public function login()
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

        $this->method = 'login';
        $result = $this->send_request($this->method, $args);

        $this->user_session_id = $result['id'];

        return $this->user_session_id;
    }
}
