<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHMapping
{
	/**
	 * @var ilLogger
	 */
	private $logger = null;
	
	private $table_name = '';
	
	private $id = 0;
	
	private $prefix = '';
	
	private $template = '';
	
	/**
	 * ilDBInterface
	 * @var \ilDB
	 */
	private $db = null;

	public function __construct($id = 0)
	{
		$this->logger = $GLOBALS['DIC']->logger()->fahi();
		$this->id = $id;
		
		$this->db = $GLOBALS['DIC']->database();
		
		$this->table_name = ilFAHImporterPlugin::getInstance()->getTablePrefix().'_map';
		
		$this->logger->debug('Table name is: ' .$this->table_name);
		
		$this->read();
	}
	
	public function setTemplate($a_template)
	{
		$this->template = $a_template;
	}
	
	/**
	 * @return int
	 */
	public function getTemplate()
	{
		return $this->template;
	}
	
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}
	
	public function getPrefix()
	{
		return $this->prefix;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Read mapping
	 */
	protected function read()
	{
		if(!$this->id)
		{
			return true;
		}
		
		$query = 'SELECT * from '.$this->table_name.' '.
			'WHERE mapping_id = '.$this->db->quote($this->getId(), 'integer');
		$res = $this->db->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$this->prefix = $row->prefix;
			$this->template = $row->template;
		}
	}
	
	/**
	 * Delete mapping entry
	 */
	public function delete()
	{
		$query = 'DELETE FROM '.$this->table_name.' '.
			'WHERE mapping_id = '.$this->db->quote($this->getId(), 'integer');
			
		$this->db->manipulate($query);
	}
	

	/**
	 * Update
	 */
	public function update()
	{
		$query = 'UPDATE '.$this->table_name.' '.
			'SET prefix = '.$this->db->quote($this->getPrefix(),'text').', '.
			'template = '.$this->db->quote($this->getTemplate(),'integer').' '.
			'WHERE mapping_id = '.$this->db->quote($this->getId(),'integet');
		$this->db->manipulate($query);
	}
	
	public function save()
	{
		$this->id = $this->db->nextId($this->table_name);
		
		$query = 'INSERT INTO '.$this->table_name.' '.
			'(mapping_id,prefix,template) '.
			'VALUES( '.
			$this->db->quote($this->getId(),'integer').', '.
			$this->db->quote($this->getPrefix(),'text').', '.
			$this->db->quote($this->getTemplate(),'integer').')';
		$this->db->manipulate($query);
	}
	
}
?>