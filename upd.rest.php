<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * REST Add-on
 *
 * @package		ExpressionEngine 2
 * @subpackage	Third Party
 * @category	Modules
 * @author		Phil Sturgeon
 * @link		http://devot-ee.com/add-ons/rest/
 */
class Rest_upd
{
	public $module_name = 'REST';
	public $version		= '1.6.0';

    function __construct( $switch = TRUE )
    {
		$this->EE =& get_instance();
    }

    /**
     * Installer for the REST module
     */
    public function install()
	{
		$this->EE->db->insert('modules', array(
			'module_name' 	 => $this->module_name,
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
		));

		$this->EE->load->dbforge();

		// time to make a table
		$this->EE->dbforge->add_field('id');
		$this->EE->dbforge->add_field(array(
			'name' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => FALSE
			),
			'url' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => FALSE
			),
			'verb' => array(
				'type' => 'VARCHAR',
				'constraint' => 6,
				'null' => FALSE
			),
			'format' => array(
				'type' => 'VARCHAR',
				'constraint' => 50,
				'null' => FALSE
			),
			'record_type' => array(
				'type' => 'CHAR',
				'constraint' => 1,
				'default' => 'm',
				'null' => FALSE
			),
			'params' => array(
				'type' => 'TEXT',
				'null' => FALSE
			),
			'site_id' => array(
				'type'		=> 'INT',
				'constraint' => 11,
				'default'	=> 1,
				'null'		=> FALSE
			)
		));
		
		$this->EE->dbforge->create_table('rest_requests');
		
		// Add an example request
		$this->EE->db->query("
			INSERT INTO `exp_rest_requests` (`id`, `name`, `url`, `verb`, `format`, `params`, `site_id`) VALUES
			(NULL, 'twitter_timeline', 'http://api.twitter.com/1/statuses/user_timeline.json', 'get', 'json', 'screen_name=philsturgeon', 1);");

		return TRUE;
	}


	/**
	 * Uninstall the REST module
	 */
	public function uninstall()
	{
		$this->EE->load->dbforge();

		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->module_name));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', $this->module_name);
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', $this->module_name);
		$this->EE->db->delete('actions');

		$this->EE->db->where('class', $this->module_name.'_mcp');
		$this->EE->db->delete('actions');

		$this->EE->dbforge->drop_table('rest_requests');

		return TRUE;
	}

	/**
	 * Update the module
	 *
	 * @param $current current version number
	 * @return boolean indicating whether or not the module was updated
	 */

	public function update($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$this->EE->load->dbforge();

		if ($current <= '1.5.3')
		{
			$this->EE->dbforge->add_column('rest_requests', array(
				'record_type' => array(
					'type'		=> 'char',
					'constraint' => 11,
					'default'	=> 'm',
					'null'		=> FALSE,
				)
			));

			$this->EE->db->set('site_id', config_item('site_id'));
			$this->EE->db->update('rest_requests');
		}
	}

}

/* End of file upd.rest.php */
/* Location: ./system/expressionengine/third_party/rating/upd.rest.php */
