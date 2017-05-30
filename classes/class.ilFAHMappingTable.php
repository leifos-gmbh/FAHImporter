<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Table/classes/class.ilTable2GUI.php';

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHMappingTable extends ilTable2GUI
{
	/**
	 * ilFAHImporterPlugin
	 * @var ilFAHImporterPlugin
	 */
	private $plugin = null;
	
	/**
	 *
	 * @var \ilLogger
	 */
	private $logger = null;

	
	/**
	 * constructor
	 * @param type $a_parent_obj
	 * @param type $a_parent_cmd
	 * @param type $a_template_context
	 */
	public function __construct($a_parent_obj, $a_parent_cmd = "", $a_template_context = "")
	{
		$this->plugin = ilFAHImporterPlugin::getInstance();
		$this->logger = $GLOBALS['DIC']->logger()->fahi();
		
		$this->setId('fahi_mapping');
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
	}
	
	public function init()
	{
	 	$this->addColumn("");
		$this->addColumn($this->plugin->txt('map_col_prefix'), 'prefix');
		$this->addColumn($this->plugin->txt('map_col_template'), 'template');
		$this->setFormAction($GLOBALS['DIC']->ctrl()->getFormAction($this->getParentObject(), $this->getParentCmd()));
		$this->setRowTemplate('tpl.map_row.html', $this->plugin->getDirectory());
		
		$this->addMultiCommand('deleteMappings',$this->lng->txt('delete'));
		
		$this->setTitle($this->plugin->txt('map_table_title'));
	}
	
	public function fillRow($set)
	{
		$this->tpl->setVariable('VAL_ID',$set['mapping_id']);
		$this->tpl->setVariable('VAL_PREFIX',$set['prefix']);
		include_once './Services/Link/classes/class.ilLink.php';
		$this->tpl->setVariable('LINK_TEMPLATE',ilLink::_getLink($set['template']));
		$this->tpl->setVariable('TEMPLATE_TITLE',ilObject::_lookupTitle(ilObject::_lookupObjId($set['template'])));
	}
	
	public function parse()
	{
		$mappings = ilFAHMappings::getInstance();
		
		$data = [];
		foreach($mappings->getMappings() as $mapping)
		{
			$row = [];
			$row['mapping_id'] = $mapping->getId();
			$row['prefix'] = $mapping->getPrefix();
			$row['template'] = $mapping->getTemplate();
			
			$data[] = $row;
		}
		
		$this->logger->dump($data);
		
		$this->setData($data);
	}
	
	
}
?>