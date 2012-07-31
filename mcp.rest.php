<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * REST Add-on
 *
 * @package		ExpressionEngine 2
 * @subpackage	Third Party
 * @category	Modules
 * @author		Phil Sturgeon
 * @link		http://getsparkplugs.com/rest
 */
class Rest_mcp
{
	public $module_name;

	// --------------------------------------------------------------------

	/**
	 * __construct()
	 *
	 * Set's form validation and properties to be used on each page
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		$this->module_name = strtolower(str_replace('_mcp', '', get_class($this)));

		define('REST_PARAMS', 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name);
		define('REST_URL', BASE.AMP.REST_PARAMS);

		$this->data->base = REST_URL;

		$this->EE->load->library('form_validation');
		$this->EE->form_validation->set_rules(array(
			array(
				'field'   => 'name',
				'label'   => lang('rest_name'),
				'rules'   => 'required|alpha_dash',
			),
			array(
				'field'   => 'url',
				'label'   => lang('rest_url'),
				'rules'   => 'required|prep_url',
			),
			array(
				'field'   => 'verb',
				'label'   => lang('rest_verb'),
				'rules'   => 'required|alpha',
			),
			array(
				'field'   => 'format',
				'label'   => lang('rest_format'),
				'rules'   => 'required'
			),
			array(
				'field'   => 'params',
				'label'   => lang('rest_params'),
				'rules'   => '',
			),
			array(
				'field'   => 'record_type',
				'label'   => lang('rest_record_type'),
				'rules'   => 'required|max_length[1]',
			),
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Index
	 *
	 * See a list of all saved REST requests with links to add/edit/delete
	 *
	 * @access	public
	 * @return	string
	 */
	public function index()
	{
		$this->EE->cp->set_right_nav(array(
			'add_request' => REST_URL.AMP.'method=add'
		));

		// Show the current page to be REST
		$this->EE->cp->set_variable('cp_page_title', lang('rest_module_name'));

		// Assign list of saved REST Requests to the view
		$this->data->rest_requests = $this->EE->db->get_where('rest_requests', array(
			'site_id' => config_item('site_id'))
		)->result();

		return $this->EE->load->view('index', $this->data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Add
	 *
	 * Form to add a new saved REST request
	 *
	 * @access	public
	 * @return	string
	 */
	public function add()
	{
		$this->EE->cp->set_breadcrumb(REST_URL, lang('rest_module_name'));
		$this->EE->cp->set_variable('cp_page_title', lang('add_request'));

		if ($this->EE->form_validation->run() )
		{
			if (self::_insert_request())
			{
				$this->EE->session->set_flashdata('message_success', sprintf(lang('request_saved_message'), $this->EE->input->post('name')));
				$this->EE->functions->redirect(REST_URL);
			}

			else
			{
				$this->data->error = lang('request_save_failed_message');
			}
		}

		// Validation failedd, report error and re-populate param array
		else
		{
			$this->data->error = validation_errors();
			
			parse_str(self::_format_params(), $request['params']);
		}

		// Set the form action
		$this->data->form_action = REST_PARAMS.AMP.'method=add';
		$this->data->request =& $request;

		return $this->EE->load->view('form', $this->data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit
	 *
	 * Form to edit a saved REST request
	 *
	 * @access	public
	 * @return	string
	 */
	public function edit()
	{
		$this->EE->cp->set_breadcrumb(REST_URL, lang('rest_module_name'));
		$this->EE->cp->set_variable('cp_page_title', lang('edit_request'));

		$id = $this->EE->input->get_post('request_id');

		// Get the request from DB
		$request = $this->EE->db
			->where('id', $id)
			->where('site_id', config_item('site_id'))
			->get('rest_requests')
			->row_array();

		// Not here, must be an old link
		$request or $this->EE->functions->redirect(REST_URL);

		// Turn a query string into an array
		parse_str($request['params'], $request['params']);

		// If the form matches validation
		if($_POST)
		{
			if ($this->EE->form_validation->run() )
			{
				if (self::_update_request($id))
				{
					$this->EE->session->set_flashdata('message_success', sprintf(lang('request_saved_message'), $this->EE->input->post('name')));
					$this->EE->functions->redirect(REST_URL);
				}

				else
				{
					$this->data->error = lang('request_save_failed_message');
				}
			}

			else
			{
				$this->data->error = validation_errors();

				parse_str(self::_format_params(), $request['params']);
			}
		}

		// Set the form action
		$this->data->form_action = REST_PARAMS.AMP.'method=edit';
		$this->data->request =& $request;
		
		return $this->EE->load->view('form', $this->data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete
	 *
	 * Remove a saved REST request
	 *
	 * @access	public
	 * @return	string
	 */
	public function delete()
	{
		$this->EE->cp->set_breadcrumb(REST_URL, lang('rest_module_name'));
		$this->EE->cp->set_variable('cp_page_title', lang('delete_request'));

		$id = $this->EE->input->get_post('request_id');

		// Get the request from DB
		$this->data->request = $request = $this->EE->db
			->where('id', $id)
			->get('rest_requests')
			->row_array();

		// Not here, must be an old link
		$request or $this->EE->functions->redirect(REST_URL);

		// They hit cancel: Back to base!
		$this->EE->input->post('cancel') and $this->EE->functions->redirect(REST_URL);

		// If the form matches validation
		if ( $this->EE->input->post('confirm') )
		{
			if (self::_delete_request($id))
			{
				$this->EE->session->set_flashdata('success', sprintf(lang('request_deleted_message'), $this->EE->input->post('name')));
				$this->EE->functions->redirect(REST_URL);
			}

			else
			{
				$this->data->error = lang('request_delete_failed_message');
			}
		}

		// Set the form action
		$this->data->form_action = REST_PARAMS.AMP.'method=delete';

		return $this->EE->load->view('confirm_delete', $this->data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Format Params
	 *
	 * Looks at the POST data and formats all REST params into a HTTP query string
	 *
	 * @access	private
	 * @return	string
	 */
	private function _format_params()
	{
		$param_names = $this->EE->input->post('param_names');
		$param_values = $this->EE->input->post('param_values');

		// Convert useless HTML array into useful PHP array
		$params = array();
		for($i = 0; $i < count($param_names); $i++)
		{
			if(!empty($param_names[$i]))
			{
				$params[$param_names[$i]] = $param_values[$i];
			}
		}

		return self::_build_query($params);
	}

	// --------------------------------------------------------------------

	/**
	 * Build query
	 *
	 * Converts array to query string, maintaining encoding
	 *
	 * @access	private
	 * @param array
	 * @return	string
	 */
	private function _build_query($params = array())
	{
		$pairs = array();

		foreach($params as $key => $val)
		{
			$pairs[] = $key . '=' . $val;
		}

		return implode('&', $pairs);
	}


	// --------------------------------------------------------------------

	/**
	 * Insert Request
	 *
	 * DB code to add a new REST request
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _insert_request()
	{
		return $this->EE->db->insert('rest_requests', array(
			'name' => $this->EE->input->post('name'),
			'url' => $this->EE->input->post('url'),
			'verb' => $this->EE->input->post('verb'),
			'format' => $this->EE->input->post('format'),
			'params' => self::_format_params(),
			'record_type' => $this->EE->input->post('record_type'),
			'site_id' => config_item('site_id')
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Update Request
	 *
	 * DB code to update an edited REST request.
	 *
	 * @access	private
	 * @param	int
	 * @return	bool
	 */
	private function _update_request($id)
	{
		return $this->EE->db->update('rest_requests', array(
			'name' => $this->EE->input->post('name'),
			'url' => $this->EE->input->post('url'),
			'verb' => $this->EE->input->post('verb'),
			'format' => $this->EE->input->post('format'),
			'params' => self::_format_params(),
			'record_type' => $this->EE->input->post('record_type'),
		), array(
			'id' => $id,
			'site_id' => config_item('site_id')
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Request
	 *
	 * DB code to remove a REST request from the database
	 *
	 * @access	private
	 * @param	int
	 * @return	bool
	 */
	private function _delete_request($id)
	{
		return $this->EE->db->delete('rest_requests', array(
			'id' => $id,
			'site_id' => config_item('site_id')
		));
	}

}

/* End of file mcp.rest.php */
/* Location: ./system/expressionengine/third_party/rest/mcp.rest.php */