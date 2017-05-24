<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHGroupComponentImporter extends ilFAHComponentImporter
{
	
	/**
	 * Import from file
	 * @param type $a_file
	 * @throws Exception
	 */
	public function import($a_file)
	{
		$this->logger->info('Starting import parsing for groups');
		
		try {
			$this->init($a_file);
			$this->parseXml();
			$this->terminate();
		} 
		catch (Exception $ex) {
			$this->terminate();
			throw $ex;
		}
	}
	
	/**
	 * Parse xml and update/create categories
	 */
	protected function parseXml()
	{
		foreach($this->root->group as $group_element)
		{
			$this->logger->debug('Handling group: ' . (string) $group_element->description->short);
			if(!$this->isGroup((string) $group_element->sourcedid->id))
			{
				$this->logger->debug('Ignoring ' . (string) $group_element->description->short .' for group import.');
				continue;
			}
			
			
			$group_info = [];
			$group_info['title'] = (string) $group_element->description->short;
			$group_info['parent_id'] = (string) $group_element->relationship->sourcedid->id;
			$group_info['id'] = (string) $group_element->sourcedid->id;
			
			try {
				$this->refreshGroup($group_info);
			}
			catch(Exception $e) {
				$this->logger->error('Group structure update failed with message: ' . $e->getMessage());
				$this->logger->dump($group_info, ilLogLevel::ERROR);
				throw $e;
			}
		}
	}
	
	/**
	 * Check if object is category
	 * @param type $a_title
	 */
	protected function isGroup($a_id)
	{
		if(strcmp('AlleBenutzer', $a_id) === 0)
		{
			$this->logger->debug($a_id. ' matches "AlleBenutzer"');
			return true;
		}
		if(strcmp('AlleTeilnehmer', $a_id) === 0)
		{
			$this->logger->debug($a_id. ' matches "AlleTeilnehmer"');
			return true;
		}
		if(strcmp('AlleDozenten', $a_id) === 0)
		{
			$this->logger->debug($a_id. ' matches "AlleDozenten"');
			return true;
		}
		if(strcmp('MitarbeiterLand_U', $a_id) === 0)
		{
			$this->logger->debug($a_id. ' matches "MitarbeiterLand_U"');
			return true;
		}
		
		if(preg_match('/_TN$/', $a_id))
		{
			$this->logger->debug($a_id .' matches group pattern.');
			return true;
		}
		if(preg_match('/_DOZ$/', $a_id))
		{
			$this->logger->debug($a_id .' matches group pattern.');
			return true;
		}
		return false;
	}
	
	/**
	 * refresh category
	 */
	protected function refreshGroup($grp_info)
	{
		include_once './Services/Xml/classes/class.ilXmlWriter.php';
		$writer = new ilXmlWriter();
		$writer->xmlStartTag('Objects');
		
		$obj_id = $this->lookupObjId($grp_info['id'], 'grp');
		if($obj_id)
		{
			$do_create = false;
		}
		else
		{
			$do_create = true;
		}
		$writer->xmlStartTag(
			'Object', 
			array(
				'obj_id' => $obj_id,
				'type' => 'grp'
			)
		);
		$writer->xmlElement('Title',[], $grp_info['title']);
		$writer->xmlElement('ImportId',[], $grp_info['id']);
		$writer->xmlEndTag('Object');
		$writer->xmlEndTag('Objects');
		
		if($do_create)
		{
			$this->logger->info('Calling create for: '. $writer->xmlDumpMem(false));
			
			$parent_id = $this->lookupParentId($grp_info['parent_id']);
			$this->soap->call(
					'addObject',
					array(
						$this->soap_session,
						$parent_id,
						$writer->xmlDumpMem(false)
					)
			);
			
		}
		else
		{
			$this->logger->info('Calling update for: '. $writer->xmlDumpMem(false));

			$this->soap->call(
					'updateObjects',
					array(
						$this->soap_session,
						$writer->xmlDumpMem(false)
					)
			);
			
		}
	}
}
?>