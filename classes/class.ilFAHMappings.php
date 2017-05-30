<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHMappings
{
	private $table_name = '';
	
	private $db = null;
	
	private $maps = [];
	
	/**
	 * @var ilFAHMappings
	 */
	private static $instance = null;

	private function __construct()
	{
		
		$this->db = $GLOBALS['DIC']->database();
		$this->table_name = ilFAHImporterPlugin::getInstance()->getTablePrefix().'_map';
		$this->read();
	}

	/**
	 * Get instance
	 * @return ilFAHMappings
	 */
	public static function getInstance()
	{
		if(self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new self();
	}
	
	/**
	 * Get mappings
	 * @return ilFAHMapping[]
	 */
	public function getMappings()
	{
		return (array) $this->maps;
	}
	
	/**
	 * Get template for title
	 * @param type $a_title
	 */
	public function getTemplateForTitle($a_title)
	{
		foreach($this->getMappings() as $mapping)
		{
			if(preg_match('/^'.$mapping->getPrefix().'/', $a_title))
			{
				return $mapping->getTemplate();
			}
		}
		return 0;
	}
	
	/**
	 * Read
	 */
	public function read()
	{
		$query = 'SELECT * from '.$this->table_name;
		$res = $this->db->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$this->maps[] = new ilFAHMapping($row->mapping_id);
		}
	}
	
}
?>