<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * REST Add-on
 *
 * @package     ExpressionEngine 2
 * @subpackage  Third Party
 * @category    Modules
 * @author      Phil Sturgeon
 * @link        http://devot-ee.com/add-ons/rest/
 */
class Rest
{
	public $return_data = '';

	private $supported_formats = array(
		'xml'               => 'application/xml',
		'atom'              => 'application/atom+xml',
		'rss'               => 'application/rss+xml',
		'json'              => 'application/json',
		'serialized'        => 'application/vnd.php.serialized',
		'csv'               => 'text/csv'
	);

	private $auto_detect_formats = array(
		'application/xml'       => 'xml',
		'text/xml'              => 'xml',
		'application/atom+xml'  => 'atom',
		'application/rss+xml'   => 'rss',
		'application/json'      => 'json',
		'text/json'             => 'json',
		'text/csv'              => 'csv',
		'application/csv'       => 'csv',
		'application/vnd.php.serialized' => 'serialize'
	);

	// --------------------------------------------------------------------

	/**
	 * Called by {exp:rest} the construct is the center of all logic for this plugin
	 *
	 * @param   int
	 * @return  bool
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->library('curl');

		// Call request by name
		if (($name = $this->EE->TMPL->fetch_param('name')))
		{
			$response = self::_run_saved_request('name', $name);
		}

		elseif (($id = $this->EE->TMPL->fetch_param('id')))
		{
			$response = self::_run_saved_request('id', $id);
		}

		// Otherwise, it looks like we are handling a manual call
		else
		{
			$url = $this->EE->TMPL->fetch_param('url', NULL);
			$format = $this->EE->TMPL->fetch_param('format', 'xml');
			$verb = $this->EE->TMPL->fetch_param('verb', 'get');
			$record_type = $this->EE->TMPL->fetch_param('record_type', 'm');
			$method = $this->EE->TMPL->fetch_param('method', $verb);

			if ( ! $url)
			{
				return '';
			}

			/*
			$this->http_auth = $this->EE->TMPL->fetch_param('http_auth', '');
			$this->http_username = $this->EE->TMPL->fetch_param('http_username', NULL);
			$this->http_password = $this->EE->TMPL->fetch_param('http_password', NULL);
			*/

			// This is the least we need
			$response = $this->_call($verb, $url, $format);
		}

		$return = null;

		// Right, we have a response, lets get some output
		if ( ! empty($response))
		{
			$this->EE->TMPL->_match_date_vars($this->EE->TMPL->tagdata);

			/* -------------------------------------------
			/* 'rest_result' hook.
			/*  - Modify result array
			*/
			if ($this->EE->extensions->active_hook('rest_result') === TRUE)
			{
				$response = $this->EE->extensions->call('rest_result', $response, $this->total_results);
			}
			// -------------------------------------------

			// Only bother trying to parse if we get valid response
			$return = $this->EE->TMPL->parse_variables(
				$this->EE->TMPL->tagdata,
				self::_force_array($response)
			);
		}

		$debug = $this->EE->TMPL->fetch_param('debug');

		if (in_array($debug, array('yes', 'true', 'on')))
		{
			return $this->return_data = $this->EE->curl->debug();
		}

		/* -------------------------------------------
		/* 'rest_tagdata_end' hook.
		/*  - Modify final tagdata to be returned
		*/
		if ($this->EE->extensions->active_hook('rest_tagdata_end') === TRUE)
		{
			$return = $this->EE->extensions->call('rest_tagdata_end', $return);
		}

		// -------------------------------------------

		// Only return if there is something worth returning
		if ( ! empty($return))
		{
			return $this->return_data = $return;
		}

		return;
	}

	// --------------------------------------------------------------------

	/**
	 * Run saved request
	 *
	 * Load a saved request and run it
	 *
	 * @access  private
	 * @param   string
	 * @return  string
	 */
	private function _run_saved_request($field, $name)
	{
		$this->EE->db->cache_off();

		$request = $this->EE->db
			->where($field, $name)
			->where('site_id', config_item('site_id'))
			->get('rest_requests')
			->row_array();

		$this->EE->db->cache_on();

		return $request
			? $this->_call($request['verb'], $request['url'], $request['format'], $request['params'], $request['record_type'])
			: FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Run saved request
	 *
	 * Load a saved request and run it
	 *
	 * @access  private
	 * @param   string
	 * @return  string
	 */
	private function _call($method, $url, $format = NULL, $params = array(), $record_type = 'm')
	{
		// Check to see if format is in the supported list
		$mime_type = array_key_exists($format, $this->supported_formats)

			// It is a supported format, grab the MIME for them
			? $this->supported_formats[$format]

			// Otherwise, assume they selected "Other" and entered a MIME-type
			: $format;

		// Take out the expected values from the params array
		foreach ($this->EE->TMPL->tagparams as $key => $var)
		{
			if (strpos($key, 'param:') !== FALSE)
			{
				$params=$params.'&'.str_replace('param:', '', $key). '='. $this->EE->TMPL->parse_globals($var);
			}
		}

		// If it is GET then shove the params into the URL
		if ($method == 'get' && ! empty($params))
		{
			$url .= strpos($url, '?') ? '&' : '?';
			$url .= is_array($params) ? http_build_query($params) : $params;
		}

		// Initialize cURL session
		$this->EE->curl->create($url);

		$this->EE->curl->http_header('User-Agent: ExpressionEngine '.config_item('app_version'));

		// Tell that server what we want. Hmm yeah... thats it.
		$this->EE->curl->http_header('Accept: '.$mime_type);

		// If authentication is enabled use it
		if ( ! empty($this->http_auth) && ! empty($this->http_user))
		{
			$this->EE->curl->http_login($this->http_user, $this->http_pass, $this->http_auth);
		}

		// We still want the response even if there is an error code over 400
		$this->EE->curl->option('failonerror', FALSE);
		
		// Stop it trying to verify SSL, potentially insecure...
		$this->EE->curl->option('ssl_verifypeer', FALSE);

		if ( ! ini_get('safe_mode') AND ! ini_get('open_basedir'))
		{
			// Follow the rabbit wherever it takes you
			$this->EE->curl->option('followlocation', TRUE);
		}
		
		// Follow the rabbit wherever it takes you
		$this->EE->curl->option('connecttimeout', 5);

		// Call the correct method with parameters
		if ($method !== 'get')
		{
			$this->EE->curl->{$method}($params);
		}

		// Execute and return the response from the REST server
		$response = $this->EE->curl->execute();

		// Format and return
		$data = $this->_format_response($format, $response);

		// M or Multiple means its an array (default)
		// S or Single means it's only one item, so make it an array
		// Otherwise the looping will fail!
		if (in_array($record_type, array('s', 'single')))
		{
			$data = array($data);
		}

		// Use a root element to specifiy which element is base
		if ($base = $this->EE->TMPL->fetch_param('base', NULL))
		{
			foreach ((array) explode(',', $base) as $node)
			{
				$data = is_array($data) ? @$data[$node] : @$data->{$node};
			}
		}

		// Are they using limit and offset?
		$limit = $this->EE->TMPL->fetch_param('limit', NULL);
		$offset = $this->EE->TMPL->fetch_param('offset', NULL);

		// Save total results for pagination
		$this->total_results = count($data);
		
		if (is_array($data) AND ($limit OR $offset))
		{
			return array_slice($data, $offset, $limit);
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Format response
	 *
	 * Set a format to send, (preset or MIME type supported
	 *
	 * @access  private
	 * @param   string
	 * @return mixed
	 */
	private function _format_response($suggested_format, $response)
	{
		// It is a supported format, so just run its formatting method
		if (array_key_exists($suggested_format, $this->supported_formats))
		{
			return $this->{"_".$suggested_format}($response);
		}

		// Find out what format the data was returned in
		$returned_mime = @$this->EE->curl->info['content_type'];

		// If they sent through more than just mime, stip it off
		if (strpos($returned_mime, ';'))
		{
			list($returned_mime)=explode(';', $returned_mime);
		}

		$returned_mime = trim($returned_mime);

		if (array_key_exists($returned_mime, $this->auto_detect_formats))
		{
			return $this->{'_'.$this->auto_detect_formats[$returned_mime]}($response);
		}

		return $response;
	}

	// --------------------------------------------------------------------

	/**
	 * Force Array
	 *
	 * Take a totally mixed item and parse it into an array compatible with EE's Template library
	 *
	 * @access  private
	 * @param   mixed
	 * @return  string
	 */
	private function _force_array($var, $level = 1)
	{
		if (is_object($var))
		{
			$var = (array) $var;
		}

		if ($level == 1 && ! isset($var[0]))
		{
			$var = array($var);
		}

		if (is_array($var))
		{
			// Make sure everything else is array or single value
			foreach($var as $index => &$child)
			{
				$child = self::_force_array($child, $level + 1);

				if (is_object($child))
				{
					$child = (array) $child;
				}

				// Format dates to unix timestamps
				elseif (isset($this->EE->TMPL->date_vars[$index]) and ! is_numeric($child))
				{
					$child = strtotime($child);
				}

				// Format for EE syntax looping
				if (is_array($child) && ! is_int($index) && ! isset($child[0]))
				{
					$child = array($child);
				}
			}
		}

		return $var;
	}


	/* Unserializes an XML string, returning a multi-dimensional associative array, optionally runs a callback on all non-array data
	 * Returns false on all failure
	 * Notes:
		* Root XML tags are stripped
		* Due to its recursive nature, unserialize_xml() will also support SimpleXMLElement objects and arrays as input
		* Uses simplexml_load_string() for XML parsing, see SimpleXML documentation for more info
	 */
	private function _xml($input, $recurse = false)
	{
		// Get input, loading an xml string with simplexml if its the top level of recursion
		$data = ((!$recurse) && is_string($input)) ? simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA): $input;

		// Convert SimpleXMLElements to array
		if ($data instanceof SimpleXMLElement)
		{
			$data = (array) $data;

			// This node has attributes
			if (isset($data['@attributes']))
			{
				// -- Option 1: Put them in a new array
				// $data['attributes'] = $data['@attributes'];

				// -- Option 2: Merge them into main array
				$data = array_merge($data['@attributes'], $data);

				unset($data['@attributes']);
			}
		}

		// Recurse into arrays
		if (is_array($data))
		{
			// We'll use this to see if we have duplicates
			$keys = array();

			$numericize = FALSE;

			foreach ($data as $key => &$item)
			{
				// Either set to 1 or incriment
				isset($keys[$key]) ? ++$keys[$key] : $keys[$key] = 1;

				// Lots with the same name? Lets numericificaterize them
				if ($keys[$key] > 1)
				{
					$numericize = TRUE;
				}

				$item = self::_xml($item, true);
			}

			if ($numericize === TRUE)
			{
				$data = array_values($data);
			}
		}

		return $data;
	}

	private function _atom($input)
	{
		$data = self::_xml($input);

		return isset($data['entry']) ? $data['entry'] : array();
	}

	private function _rss($input)
	{
		$data = self::_xml($input);

		return isset($data['channel']['item']) ? $data['channel']['item'] : array();
	}

	// Format HTML for output
	// This function is DODGY! Not perfect CSV support but works with my REST_Controller
	private function _csv($string)
	{
		$data = array();

		// Splits
		$rows = explode("\n", trim($string));
		$headings = explode(',', array_shift($rows));
		foreach( $rows as $row )
		{
			// The substr removes " from start and end
			$data_fields = explode('","', trim(substr($row, 1, -1)));

			if (count($data_fields) == count($headings))
			{
				$data[] = array_combine($headings, $data_fields);
			}

		}

		return $data;
	}

	// Encode as JSON
	private function _json($string)
	{
		return json_decode(trim($string));
	}

	// Encode as Serialized array
	private function _serialize($string)
	{
		return unserialize(trim($string));
	}

}

/* Location: ./application/libraries/REST.php */
