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
			$group_info['parent_id'] = $this->updateParentId($group_info['parent_id']);
			
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
	 * No "Benutzergruppen" required for ILIAS
	 * @param string $a_parent_id
	 * @return rewritten parent id
	 */
	protected function updateParentId($a_parent_id)
	{
		if(strcmp(substr($a_parent_id, -2),'_S') === 0)
		{
			$this->logger->debug($a_parent_id.': matches _S$ and is replaced by _R');
			$a_parent_id = substr($a_parent_id,0, -1);
			return $a_parent_id.'R';
		}
		if(strcmp($a_parent_id, 'Zielgruppen') === 0)
		{
			// replace 'Zielgruppen' by 'AlleDozenten'
			$this->logger->debug($a_parent_id.': matches Zielgruppen and is replaced by "AlleDozenten"');
			return 'AlleDozenten';
		}
		if(strcmp($a_parent_id, 'Benutzergruppen') === 0)
		{
			// replace 'Benutzergruppen' by 'AlleBenutzer'
			$this->logger->debug($a_parent_id.': matches Benutzergruppen and is replaced by "AlleBenutzer"');
			return 'AlleBenutzer';
		}
		
		$this->logger->debug('Parent id: ' . $a_parent_id .' is not replaced.');
		return $a_parent_id;
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
			$this->logger->info('Ignoring group:' . $a_id);
			return false;
		}
		if(strcmp('AlleDozenten', $a_id) === 0)
		{
			$this->logger->debug($a_id. ' matches "AlleDozenten"');
			return true;
		}
		if(strcmp('MitarbeiterLand_U', $a_id) === 0)
		{
			$this->logger->debug($a_id. ' matches "MitarbeiterLand_U"');
			$this->logger->info('Ignoring group:' . $a_id);
			return false;
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
		
		$this->logger->debug($a_id.': is not handled as group.');
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
			// this is a deprecated group => remove it
			$this->logger->info('Removing deprecated group.');
			$this->soap->call(
				'removeFromSystemByImportId',
				[
					$this->soap_session,
					$grp_info['id']
				]
			);
		}
		
		// create/update shadow entry
		$parent_id = $this->lookupParentId($grp_info['parent_id']);

		$shadow = ilFAHShadowGroup::getInstanceByImportId($grp_info['id']);
		$shadow->setParentId($parent_id);
		$shadow->persist();
		
		$this->logger->info('Added new shadow group for: ' . $grp_info['id']. ' with parent reference: ' . $grp_info['parent_id']);
		return true;
	}
}
?>