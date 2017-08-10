<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHCourseInfo
{
	const DB_TABLE_NAME = 'cron_crnhk_fahi_info';
	
	private $id = 0;
	private $import_id = 0;
	private $keyword = '';
	private $value = '';
	
	private $entry_exists = false;
	
	/**
	 * @var ilDBInterface
	 */
	private $db;
	
	public function __construct($a_id = 0)
	{
		$this->id = $a_id;
		$this->db = $GLOBALS['DIC']->database();
		$this->read();
	}
	
	public static function deleteByImportId($a_id)
	{
		$db = $GLOBALS['DIC']->database();
		
		$query = 'DELETE FROM '.self::DB_TABLE_NAME.' '.
			'WHERE import_id = '.$db->quote($a_id,'integer');
		$db->manipulate($query);

	}
	
	/**
	 * Get info of ref_id 
	 * @param int $a_id
	 * @return []
	 */
	public static function getInfoByImportId($a_id)
	{
		$db = $GLOBALS['DIC']->database();
		
		$query = 'SELECT * FROM '.self::DB_TABLE_NAME.' '.
			'WHERE import_id = '.$db->quote($a_id,'integer');
		$res = $db->query($query);
		
		$info = [];
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			if(strlen(trim($row->value)))
			{
				$info[$row->keyword] = $row->value;
			}
		}
		return $info;
	}


	public function setKeyword($a_key)
	{
		$this->keyword = $a_key;
	}
	
	public function setImportId($a_id)
	{
		$this->import_id = $a_id;
	}
	
	public function setValue($a_val)
	{
		$this->value = $a_val;
	}
	
	public function getKeyword()
	{
		return $this->keyword;
	}
	
	public function getValue()
	{
		return $this->value;
	}
	
	public function getImportId()
	{
		return $this->import_id;
	}
	
	public function create()
	{
		$this->id = $this->db->nextId(self::DB_TABLE_NAME);
		$query = 'INSERT INTO '.self::DB_TABLE_NAME.' '.
			'(info_id,import_id,keyword,value) '.
			'VALUES( '.
			$this->db->quote($this->id, 'integer').', '.
			$this->db->quote($this->import_id, 'integer').', '.
			$this->db->quote($this->keyword, 'text').', '.
			$this->db->quote($this->value, 'text').' '.
			')';
		$this->db->manipulate($query);
	}

	private function read()
	{
		if(!$this->id)
		{
			return false;
		}
		$query = 'SELECT * FROM '.self::DB_TABLE_NAME.' '.
			'WHERE info_id = '.$this->db->quote($this->id, 'integer');
		$res = $this->db->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$this->entry_exists = true;
			$this->keyword = $row->keyword;
			$this->value = $row->value;
			$this->import_id = $row->import_id;
		}
	}
}
?>