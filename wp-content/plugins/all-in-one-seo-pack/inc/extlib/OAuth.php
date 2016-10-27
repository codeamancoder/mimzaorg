<?php
/* OAuth PHP Library
 * http://oauth.googlecode.com/svn/code/php/
 *
 * License: The MIT License
 *
 * Copyright (c) 2007 Andy Smith
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */
// vim: foldmethod=marker

/* Generic exception class
 */
if ( !class_exists( 'OAuthException' ) ) {
	class OAuthException extends Exception {
	  // pass
	}	
}

if ( !class_exists( 'OAuthConsumer' ) ) {
	class OAuthConsumer {
	  public $key;
	  public $secret;

	  function __construct($key, $secret, $callback_url=NULL) {
	    $this->key = $key;
	    $this->secret = $secret;
	    $this->callback_url = $callback_url;
	  }

	  function __toString() {
	    return "OAuthConsumer[key=$this->key,secret=$this->secret]";
	  }
	}
}

if ( !class_exists( 'OAuthToken' ) ) {
	class OAuthToken {
	  // access tokens and request tokens
	  public $key;
	  public $secret;

	  /**
	   * key = the token
	   * secret = the token secret
	   */
	  function __construct($key, $secret) {
	    $this->key = $key;
	    $this->secret = $secret;
	  }

	  /**
	   * generates the basic string serialization of a token that a server
	   * would respond to request_token and access_token calls with
	   */
	  function to_string() {
	    return "oauth_token=" .
	           OAuthUtil::urlencode_rfc3986($this->key) .
	           "&oauth_token_secret=" .
	           OAuthUtil::urlencode_rfc3986($this->secret);
	  }

	  function __toString() {
	    return $this->to_string();
	  }
	}
}

if ( !class_exists('OAuthSignatureMethod') ) {
	/**
	 * A class for implementing a Signature Method
	 * See section 9 ("Signing Requests") in the spec
	 */
	abstract class OAuthSignatureMethod {
	  /**
	   * Needs to return the name of the Signature Method (ie HMAC-SHA1)
	   * @return string
	   */
	  abstract public function get_name();

	  /**
	   * Build up the signature
	   * NOTE: The output of this function MUST NOT be urlencoded.
	   * the encoding is handled in OAuthRequest when the final
	   * request is serialized
	   * @param OAuthRequest $request
	   * @param OAuthConsumer $consumer
	   * @param OAuthToken $token
	   * @return string
	   */
	  abstract public function build_signature($request, $consumer, $token);

	  /**
	   * Verifies that a given signature is correct
	   * @param OAuthRequest $request
	   * @param OAuthConsumer $consumer
	   * @param OAuthToken $token
	   * @param string $signature
	   * @return bool
	   */
	  public function check_signature($request, $consumer, $token, $signature) {
	    $built = $this->build_signature($request, $consumer, $token);

	    // Check for zero length, although unlikely here
	    if (strlen($built) == 0 || strlen($signature) == 0) {
	      return false;
	    }

	    if (strlen($built) != strlen($signature)) {
	      return false;
	    }

	    // Avoid a timing leak with a (hopefully) time insensitive compare
	    $result = 0;
	    for ($i = 0; $i < strlen($signature); $i++) {
	      $result |= ord($built{$i}) ^ ord($signature{$i});
	    }

	    return $result == 0;
	  }
	}
}

if ( !class_exists('OAuthSignatureMethod_HMAC_SHA1') ) {
	/**
	 * The HMAC-SHA1 signature method uses the HMAC-SHA1 signature algorithm as defined in [RFC2104] 
	 * where the Signature Base String is the text and the key is the concatenated values (each first 
	 * encoded per Parameter Encoding) of the Consumer Secret and Token Secret, separated by an '&' 
	 * character (ASCII code 38) even if empty.
	 *   - Chapter 9.2 ("HMAC-SHA1")
	 */
	class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod {
	  function get_name() {
	    return "HMAC-SHA1";
	  }

	  public function build_signature($request, $consumer, $token) {
	    $base_string = $request->get_signature_base_string();
	    $request->base_string = $base_string;

	    $key_parts = array(
	      $consumer->secret,
	      ($token) ? $token->secret : ""
	    );

	    $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
	    $key = implode('&', $key_parts);

	    return base64_encode(hash_hmac('sha1', $base_string, $key, true));
	  }
	}
}

if ( !class_exists('OAuthSignatureMethod_PLAINTEXT') ) {
	/**
	 * The PLAINTEXT method does not provide any security protection and SHOULD only be used 
	 * over a secure channel such as HTTPS. It does not use the Signature Base String.
	 *   - Chapter 9.4 ("PLAINTEXT")
	 */
	class OAuthSignatureMethod_PLAINTEXT extends OAuthSignatureMethod {
	  public function get_name() {
	    return "PLAINTEXT";
	  }

	  /**
	   * oauth_signature is set to the concatenated encoded values of the Consumer Secret and 
	   * Token Secret, separated by a '&' character (ASCII code 38), even if either secret is 
	   * empty. The result MUST be encoded again.
	   *   - Chapter 9.4.1 ("Generating Signatures")
	   *
	   * Please note that the second encoding MUST NOT happen in the SignatureMethod, as
	   * OAuthRequest handles this!
	   */
	  public function build_signature($request, $consumer, $token) {
	    $key_parts = array(
	      $consumer->secret,
	      ($token) ? $token->secret : ""
	    );

	    $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
	    $key = implode('&', $key_parts);
	    $request->base_string = $key;

	    return $key;
	  }
	}
}


if ( !class_exists('OAuthSignatureMethod_RSA_SHA1') ) {
	/**
	 * The RSA-SHA1 signature method uses the RSASSA-PKCS1-v1_5 signature algorithm as defined in 
	 * [RFC3447] section 8.2 (more simply known as PKCS#1), using SHA-1 as the hash function for 
	 * EMSA-PKCS1-v1_5. It is assumed that the Consumer has provided its RSA public key in a 
	 * verified way to the Service Provider, in a manner which is beyond the scope of this 
	 * specification.
	 *   - Chapter 9.3 ("RSA-SHA1")
	 */
	abstract class OAuthSignatureMethod_RSA_SHA1 extends OAuthSignatureMethod {
	  public function get_name() {
	    return "RSA-SHA1";
	  }

	  // Up to the SP to implement this lookup of keys. Possible ideas are:
	  // (1) do a lookup in a table of trusted certs keyed off of consumer
	  // (2) fetch via http using a url provided by the requester
	  // (3) some sort of specific discovery code based on request
	  //
	  // Either way should return a string representation of the certificate
	  protected abstract function fetch_public_cert(&$request);

	  // Up to the SP to implement this lookup of keys. Possible ideas are:
	  // (1) do a lookup in a table of trusted certs keyed off of consumer
	  //
	  // Either way should return a string representation of the certificate
	  protected abstract function fetch_private_cert(&$request);

	  public function build_signature($request, $consumer, $token) {
	    $base_string = $request->get_signature_base_string();
	    $request->base_string = $base_string;

	    // Fetch the private key cert based on the request
	    $cert = $this->fetch_private_cert($request);

	    // Pull the private key ID from the certificate
	    $privatekeyid = openssl_get_privatekey($cert);

	    // Sign using the key
	    $ok = openssl_sign($base_string, $signature, $privatekeyid);

	    // Release the key resource
	    openssl_free_key($privatekeyid);

	    return base64_encode($signature);
	  }

	  public function check_signature($request, $consumer, $token, $signature) {
	    $decoded_sig = base64_decode($signature);

	    $base_string = $request->get_signature_base_string();

	    // Fetch the public key cert based on the request
	    $cert = $this->fetch_public_cert($request);

	    // Pull the public key ID from the certificate
	    $publickeyid = openssl_get_publickey($cert);

	    // Check the computed signature against the one passed in the query
	    $ok = openssl_verify($base_string, $decoded_sig, $publickeyid);

	    // Release the key resource
	    openssl_free_key($publickeyid);

	    return $ok == 1;
	  }
	}
}

if ( !class_exists('OAuthRequest') ) {
	class OAuthRequest {
	  protected $parameters;
	  protected $http_method;
	  protected $http_url;
	  // for debug purposes
	  public $base_string;
	  public static $version = '1.0';
	  public static $POST_INPUT = 'php://input';

	  function __construct($http_method, $http_url, $parameters=NULL) {
	    $parameters = ($parameters) ? $parameters : array();
	    $parameters = array_merge( OAuthUtil::parse_parameters(parse_url($http_url, PHP_URL_QUERY)), $parameters);
	    $this->parameters = $parameters;
	    $this->http_method = $http_method;
	    $this->http_url = $http_url;
	  }


	  /**
	   * attempt to build up a request from what was passed to the server
	   */
	  public static function from_request($http_method=NULL, $http_url=NULL, $parameters=NULL) {
	    $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
	              ? 'http'
	              : 'https';
	    $http_url = ($http_url) ? $http_url : $scheme .
	                              '://' . $_SERVER['SERVER_NAME'] .
	                              ':' .
	                              $_SERVER['SERVER_PORT'] .
	                              $_SERVER['REQUEST_URI'];
	    $http_method = ($http_method) ? $http_method : $_SERVER['REQUEST_METHOD'];

	    // We weren't handed any parameters, so let's find the ones relevant to
	    // this request.
	    // If you run XML-RPC or similar you should use this to provide your own
	    // parsed parameter-list
	    if (!$parameters) {
	      // Find request headers
	      $request_headers = OAuthUtil::get_headers();

	      // Parse the query-string to find GET parameters
	      $parameters = OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);

	      // It's a POST request of the proper content-type, so parse POST
	      // parameters and add those overriding any duplicates from GET
	      if ($http_method == "POST"
	          &&  isset($request_headers['Content-Type'])
	          && strstr($request_headers['Content-Type'],
	                     'application/x-www-form-urlencoded')
	          ) {
	        $post_data = OAuthUtil::parse_parameters(
	          file_get_contents(self::$POST_INPUT)
	        );
	        $parameters = array_merge($parameters, $post_data);
	      }

	      // We have a Authorization-header with OAuth data. Parse the header
	      // and add those overriding any duplicates from GET or POST
	      if (isset($request_headers['Authorization']) && substr($request_headers['Authorization'], 0, 6) == 'OAuth ') {
	        $header_parameters = OAuthUtil::split_header(
	          $request_headers['Authorization']
	        );
	        $parameters = array_merge($parameters, $header_parameters);
	      }

	    }

	    return new OAuthRequest($http_method, $http_url, $parameters);
	  }

	  /**
	   * pretty much a helper function to set up the request
	   */
	  public static function from_consumer_and_token($consumer, $token, $http_method, $http_url, $parameters=NULL) {
	    $parameters = ($parameters) ?  $parameters : array();
	    $defaults = array("oauth_version" => OAuthRequest::$version,
	                      "oauth_nonce" => OAuthRequest::generate_nonce(),
	                      "oauth_timestamp" => OAuthRequest::generate_timestamp(),
	                      "oauth_consumer_key" => $consumer->key);
	    if ($token)
	      $defaults['oauth_token'] = $token->key;

	    $parameters = array_merge($defaults, $parameters);

	    return new OAuthRequest($http_method, $http_url, $p