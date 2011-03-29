<?php

class WP_JSON_RPC_API_Control 
{

	public $id;
	public $view;
	
	public function __construct()
	{
		$this->view = new WP_JSON_RPC_API_View; 
		add_action('init', array(&$this, 'event_init'));	
		add_action('init', array(&$this->view, 'enqueue_helper_js'));	

		do_action( 'wp_json_rpc_api_instantiated' );
	}

	public function event_init()
	{
		// listen for POST requests with an argument of 'json-rpc'
		if ( ! empty( $_POST['json-rpc-request'] ) ) {
			$request = $_POST['json-rpc-request'];	
			if ( get_magic_quotes_gpc() ) {
				$request = stripslashes( $request );
			}
			$decoded = json_decode( $request );	

			// in some configurations the request is slashed even when get_magic_quotes_gpc returns 0--not sure why
			if ( empty( $decoded ) || empty( $decoded->jsonrpc ) ) {
				$decoded = json_decode( stripslashes( $request ) );
			}

			if ( ! empty( $decoded ) && ! empty( $decoded->jsonrpc ) ) {
				$this->handle_json_request( $decoded );
			}
		}
	}

	public function handle_json_request( $request = null )
	{
		if ( empty( $request->method ) ) {
			return;	
		}

		$id = isset( $request->id ) ? (string) $request->id : null;
		$this->id = $id;

		$server_class = apply_filters('json_server_classname', 'WP_JSON_RPC_Server', $request->method ); 
		$json_server = new $server_class;
		$result = $json_server->serve_request( $request );
		
		if ( is_a( $result, 'IXR_Error' ) ) {
			echo $this->view->get_json_error( 
				new WP_Error( $result->code, $result->message ),
				$id
			);
			exit;
		} elseif ( is_wp_error( $result ) ) {
			echo $this->view->get_json_error( 
				$result,
				$id
			);
			exit;
		} else {
			echo $this->view->get_json_result(
				$result,
				$id
			);
			exit;
		}
	}
}

class WP_JSON_RPC_API_View
{

	public $client_dir_url;
	
	public function __construct()
	{
		$this->client_dir_url = plugin_dir_url( dirname( __FILE__ ) ) . 'client-files/';
	}

	public function enqueue_helper_js()
	{
		wp_enqueue_script(
			'json-rpc-api-helper', 
			$this->client_dir_url . 'js/helper.js',
			null,
			'1.0'
		);
	}

	public function get_json_error( WP_Error $error, $id = null, $data = null )
	{
		$code = (int) $error->get_error_code();
		$message = $error->get_error_message();
		$data = null === $data ? $error->get_error_data() : $data;

		$error = array(
			'code' => $code,
			'message' => $message,
		);

		if ( ! empty( $data ) ) {
			$error['data'] = $data;
		}

		return json_encode( array(
			'jsonrpc' => '2.0',
			'error' => $error,
			'id' => $id,
		) );
	}

	public function get_json_result( $result = null, $id = null )
	{
		return json_encode( array(
			'jsonrpc' => '2.0',
			'result' => $result,
			'id' => $id,
		) );
	}
}

class WP_JSON_RPC_Server extends IXR_Server
{
	public $methods;

	public function __construct()
	{
		$this->methods['demo.sayHello'] = 'this:sayHello';
		$this->methods['demo.addTwoNumbers'] = 'this:addTwoNumbers';
	}
	
	function hasMethod( $method ) {
		return in_array( $method, array_keys( $this->callbacks ) );
	}

	public function serve_request( $data = null ) {
		$this->setCapabilities();
		$this->callbacks = apply_filters( 'jsonrpc_methods', $this->methods );
		$this->setCallbacks();
		$this->message = new WP_JSON_RPC_Message( $data );
		$this->message->parse();
		$result = $this->call( $this->message->methodName, $this->message->params );

		return $result;
	}


	/**
	 * Callable methods
	 */


	public function sayHello()
	{
		return 'Hello!';
	}

	public function addTwoNumbers( $args ) {
		$number1 = isset( $args[0] ) ? $args[0] : 0;
		$number2 = isset( $args[1] ) ? $args[1] : 0;
		return $number1 + $number2;
	}

}

class WP_JSON_RPC_Message extends IXR_Message 
{
	
	public function parse()
	{
		$this->methodName = $this->message->method;
		$this->messageType = 'methodCall';
		$this->params = is_array( $this->message->params ) ? $this->message->params : array( $this->message->params );
	}
}
