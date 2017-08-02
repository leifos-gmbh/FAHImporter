<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHShadowGroup
{
	const DB_TABLE_NAME = 'cron_crnhk_fahi_grp';
	
	private $id = 0;
	private $import_id = '';
	private $parent_id = 0;
	
	/**
	 * @var ilDBInterface
	 */
	private $db = null;
	
	private $entry_exists = false;
	
	/**
	 * Constructor
	 * @param int $a_id
	 */
	public function __construct($a_id = 0)
	{
		$this->db = $GLOBALS['DIC']->database();
		
		$this->id = $a_id;
		$this->read();
	}
	
	/**
	 * Get instance by import id
	 * @param int $a_import_id
	 * @return \ilFAHShadowGroup
	 */
	public static function getInstanceByImportId($a_import_id)
	{
		$db = $GLOBALS['DIC']->database();
		
		$query = 'SELECT * FROM  '.self::DB_TABLE_NAME.' '.
			'WHERE import_id = '. $db->quote($a_import_id, 'text');
		$res = $db->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			return new self($row->id);
		}
		$instance = new self();
		$instance->setImportId($a_import_id);
		return $instance;
	}
	
	/**
	 * Check if entry exists
	 * @return bool
	 */
	public function exists()
	{
		return $this->entry_exists;
	}


	public function getImportId()
	{
		return $this->import_id;
	}
	
	public function setImportId($a_import_id)
	{
		$this->import_id = $a_import_id;
	}
	
	public function getParentId()
	{
		return $this->parent_id;
	}
	
	public function setParentId($a_parent_id)
	{
		$this->parent_id = $a_parent_id;
	}


	public function persist()
	{
		if($this->entry_exists)
		{
			$this->update();
		}
		else
		{
			$this->create();
		}
	}
	
	/**
	 * update db entry
	 * @return boolean
	 */
	public function update()
	{
		$query = 'UPDATE '.self::DB_TABLE_NAME.' '.
			'set import_id = '.$this->db->quote($this->import_id, 'text').', '.
			'parent_id = '.$this->db->quote($this->parent_id,'integer').' '.
			'where id = '.$this->id;
		$this->db->manipulate($query);
		return true;
	}
	
	/**
	 * create new entry
	 */
	public function create()
	{
		$this->id = $this->db->nextId(self::DB_TABLE_NAME);
		$query = 'INSERT INTO '.self::DB_TABLE_NAME.' '.
			'(id,import_id,parent_id) '.
			'VALUES( '.
			$this->db->quote($this->id, 'integer').', '.
			$this->db->quote($this->import_id, 'text').', '.
			$this->db->quote($this->parent_id,'integer').' '.
			')';
		$this->db->manipulate($query);
		$this->entry_exists = true;
	}
	
	/**
	 * Read from db
	 * @return type
	 */
	protected function read()
	{
		if(!$this->id)
		{
			return;
		}
		
		$query = 'SELECT * FROM '.self::DB_TABLE_NAME.' '.
			'WHERE id = '. $this->db->quote($this->id, 'integer');
		$res = $this->db->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$this->entry_exists = true;
			$this->import_id = $row->import_id;
			$this->parent_id = $row->parent_id;
		}
		return true;
	}
	
	
	
}
?>