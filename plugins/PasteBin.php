<?php

/**
 * cURL module is needed in order for this class to work
 */
if (!function_exists('curl_init')) {
	throw new Exception('PasteBin library needs the CURL PHP extension.');
}

/**
 * PasteBin API wrapper class. Can be used to create new Paste.
 *
 * @author Marius Barbulescu
 * @version 0.1
 */
class PasteBin
{
	/**
	 * Library version 
	 */
	public const float VERSION = 0.1;

	/**
	 * API URL for POST requests
	 */
    public const string API_POST_URL = 'https://pastebin.com/api/api_post.php';

	/**
	 * API params field names
	 */
	public const string API_FIELD_DEV_KEY = 'api_dev_key';
	public const string API_FIELD_USER_KEY = 'api_user_key';
	public const string API_FIELD_OPTION = 'api_option';
	public const string API_FIELD_PASTE_CODE = 'api_paste_code';
	public const string API_FIELD_PASTE_PRIVATE = 'api_paste_private';
	public const string API_FIELD_PASTE_NAME = 'api_paste_name';
	public const string API_FIELD_PASTE_EXPIRE_DATE = 'api_paste_expire_date';
	public const string API_FIELD_PASTE_FORMAT = 'api_paste_format';

	/**
	 * Default API option value when creating a new Paste.
	 */
	public const string API_OPTION_CREATE = 'paste';

	/**
	 * API paste expiration date values
	 */
	public const string PASTE_EXPIRE_NEVER = 'N'; // Never
    public const string PASTE_EXPIRE_10M = '10M'; // 10 Minutes
    public const string PASTE_EXPIRE_1H = '1H'; // 1 Hour
    public const string PASTE_EXPIRE_1D = '1D'; // 1 Day
    public const string PASTE_EXPIRE_1W = '1W'; // 1 Week
    public const string PASTE_EXPIRE_2W = '2W'; // 2 Weeks
    public const string PASTE_EXPIRE_1M = '1M'; // 1 Month

    /**
     * API paste visibility types
     */
    public const string PASTE_PRIVACY_PUBLIC = '0';
    public const string PASTE_PRIVACY_UNLISTED = '1';
    public const string PASTE_PRIVACY_PRIVATE = '2';

    /**
     * API Developer Key needed in order to make API requests
     * @var string
     */
    public string|false $apiDevKey = false;

    /**
     * API error, in case there is one
     * 
     * @var string
     */
    public ?string $apiError = null;

    /**
     * cURL request error, if any
     * 
     * @var string
     */
    public ?string $curlError = null;

    /**
     * API paste visibility type => label array
     * 
     * @var array
     */
    public static array $pastePrivacyTypes = [
    	self::PASTE_PRIVACY_PUBLIC => 'Public',
    	self::PASTE_PRIVACY_UNLISTED => 'Unlisted',
    	self::PASTE_PRIVACY_PRIVATE => 'Private',
    ];

    /**
     * API paste expire date type => label array
     * 
     * @var array
     */
    public static array $pasteExpireTypes = [
    	self::PASTE_EXPIRE_NEVER => 'Never',
	    self::PASTE_EXPIRE_10M => '10 Minutes',
	    self::PASTE_EXPIRE_1H => '1 Hour',
	    self::PASTE_EXPIRE_1D => '1 Day',
	    self::PASTE_EXPIRE_1W => '1 Week',
	    self::PASTE_EXPIRE_2W => '2 Weeks',
	    self::PASTE_EXPIRE_1M => '1 Month',
    ];

    /**
     * Default options to be used when making cURL requests.
     * 
     * @var array
     */
    public static array $curlOptions = [
    	CURLOPT_CONNECTTIMEOUT => 10,
	    CURLOPT_RETURNTRANSFER => true,
	    CURLOPT_TIMEOUT => 60,
	    CURLOPT_VERBOSE => false,
	    CURLOPT_NOBODY => 0,
	    CURLOPT_ENCODING => '',
    ];


    /**
     * Instantiate a new PasteBin object.
     * 
     * @param string $_apiKey
     */
    public function __construct(string|false $_apiKey = false)
    {
    	if ($_apiKey) {
    		$this->setApiKey($_apiKey);
    	}
    }

    /**
     * Set the API key used to make requests.
     * 
     * @param string $_apiKey
     */
    public function setApiKey(string|false $_apiKey): void
    {
    	$this->apiDevKey = $_apiKey;
    }

    /**
     * Get the API key.
     * 
     * @return string
     */
    public function getApiKey(): string|false
    {
    	return $this->apiDevKey;
    }

    /**
     * Return last call error, whether it was a cURL error or API error.
     * If no error exists, false is returned.
     * 
     * @return string|boolean
     */
    public function getError(): string|false
    {
    	if ($this->curlError) {
    		return $this->curlError;
    	} elseif ($this->apiError) {
    		return $this->apiError;
    	}

    	return false;
    }

    /**
     * Create a new Paste. If everything is OK the return value will be the 
     * URL of the newly created Paste. If there was an error when the request
     * was sent, the return value will be false. Check the error message if
     * this is the case.
     * 
     * @param  string $_code
     * @param  string $_visibility
     * @param  string $_name
     * @param  string $_expire
     * @param  string $_format
     * @return string|boolean The URL for the newly created Paste
     */
    public function createPaste(
        string $_code,
        string|false $_visibility = false,
        string|false $_name = false,
        string|false $_expire = false,
        string|false $_format = false
    ): string|false
    {
        $params = [
            self::API_FIELD_OPTION => self::API_OPTION_CREATE,
            self::API_FIELD_DEV_KEY => $this->getApiKey(),
            self::API_FIELD_PASTE_CODE => $_code,
            self::API_FIELD_PASTE_PRIVATE => $this->_preparePrivateValue($_visibility),
            self::API_FIELD_PASTE_NAME => (string) $_name,
            self::API_FIELD_PASTE_EXPIRE_DATE => $this->_prepareExpireValue($_expire),
            self::API_FIELD_PASTE_FORMAT => $this->_prepareFormatValue($_format),
        ];

    	return $this->_makePostRequest(self::API_POST_URL, $params);
    }

    /**
     * Make a cURL request using the POST method.
     * 
     * @param  string $_url
     * @param  array $_params
     * @return string|boolean
     */
    protected function _makePostRequest(string $_url, array $_params): string|false
    {
    	return $this->_makeRequest($_url, $_params, 'post');
    }

    /**
     * Make a cURL request by specifying the url, params and the method. 
     *  
     * @param  string $_url
     * @param  array $_params
     * @param  string $_type
     * @return string|boolean
     */
    protected function _makeRequest(string $_url, array $_params, string $_type = 'get'): string|false
    {
    	$ch = $this->_prepareRequest($_url, $_type, $_params);
    	
		$result = curl_exec($ch);
		if ($result === false) {
    		$this->curlError = curl_error($ch);
			curl_close($ch);
    		return false;
    	}

    	curl_close($ch);

    	return $this->_parseResponse($result);
	}

	/**
	 * Prepare the cURL request based on the http method type.
	 * 
	 * @param  string $_type
	 * @return cURL handle
	 */
	protected function _prepareRequest(string $_url, string $_type, array $_params = []): CurlHandle
	{
		$ch = $this->_initRequest();
		if (!curl_setopt($ch, CURLOPT_URL, $_url)) {
			curl_close($ch);
			throw new RuntimeException('Unable to configure the PasteBin request.');
		}

			switch (strtolower($_type)) {
				case 'post':
					curl_setopt($ch, CURLOPT_POST, true);

					$postfields = $this->_encodeParams($_params);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
					break;

				case 'get':
				default:
					// this is set as default, but just to be sure
					curl_setopt($ch, CURLOPT_HTTPGET, true);
			}

		return $ch;
	}

	/**
	 * Prepare request params to be sent through cURL.
	 * 
	 * @param  array $_params
	 * @return string
	 */
	protected function _encodeParams(array $_params): string
	{
		return http_build_query($_params, '', '&', PHP_QUERY_RFC1738);
	}

	/**
	 * Test if the privacy value is among valid ones, 
	 * else return the default value.
	 * 
	 * @param  string $_value
	 * @return string
	 */
	protected function _preparePrivateValue(string|false $_value): string
	{
		if (in_array($_value, [
			self::PASTE_PRIVACY_PUBLIC,
			self::PASTE_PRIVACY_UNLISTED,
			self::PASTE_PRIVACY_PRIVATE,
		], true)) {
			return $_value;
		}

		// default unlisted
		return self::PASTE_PRIVACY_UNLISTED; 
	}

	/**
	 * Test if the expire date value is among the valid ones,
	 * else return the default value.
	 * 
	 * @param  string $_value
	 * @return string
	 */
	protected function _prepareExpireValue(string|false $_value): string
	{
		if (in_array($_value, array_keys(self::$pasteExpireTypes), true)) {
			return $_value;
		}

		// default 10 minutes
		return self::PASTE_EXPIRE_10M; 
	}	

	/**
	 * Set proper format of the code.
	 * 
	 * @param  string $_value
	 * @return string
	 */
	protected function _prepareFormatValue(string|false $_value): string
	{
		return $_value === false || $_value === '' ? 'text' : $_value;
	}

	/**
	 * Initiates the cURL request used by other methods to send cURL requests.
	 * 
	 * @return cURL handle|false
	 */
	protected function _initRequest(): CurlHandle
	{
		$ch = curl_init();
		if ($ch === false || !curl_setopt_array($ch, self::$curlOptions)) {
			if ($ch !== false) {
				curl_close($ch);
			}
			throw new RuntimeException('Unable to initialize the PasteBin request.');
		}

		return $ch;
	}

	/**
	 * Interpret the response received from the API. 
	 * Check if there is an error returned or not. 
	 * 
	 * @param  string $_response
	 * @return boolean|string
	 */
	protected function _parseResponse(string $_response): string|false
	{
		if (preg_match('/Bad API request, (.*)/', $_response, $match)) {
			$this->apiError = ucfirst($match[1]);
			return false;
		}

		return $_response;
	}
}
