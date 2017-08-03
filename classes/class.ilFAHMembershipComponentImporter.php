<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHMembershipComponentImporter extends ilFAHComponentImporter
{
	
	/**
	 * Import from file
	 * @param type $a_file
	 * @throws Exception
	 */
	public function import($a_file)
	{
		$this->logger->info('Starting import parsing for membership assignments.');
		
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
		// iterate through all membership assignments
		foreach($this->root->membership as $membership_element)
		{
			$membership_info = [];
			$membership_info['id'] = (string) $membership_element->sourcedid->id;
			
			// ignore direct course membership assignments, since these 
			// membership objects do only contain groups
			if(strcmp(substr($membership_info['id'],-2), '_R') === 0)
			{
				$this->logger->info('Ignoring direct course membership assignments for id: ' . $membership_info['id']);
				continue;
			}
			else
			{
				$this->logger->info('Handling membership assignment: ' . $membership_info['id']);
			}
			
			$membership_info['members'] = [];
			foreach($membership_element->member as $member_element)
			{
				$membership_info['members'][] = (string) $member_element->sourcedid->id;
			}
			$this->refreshMembership($membership_info);
		}
	}
	
	/**
	 * Refresh member assignments
	 * @param array $membership_info
	 */
	protected function refreshMembership($membership_info)
	{
		$this->logger->dump($membership_info);
		$GLOBALS['DIC']->rbac()->review()->clearCaches();
		
		
		// get parent ref_id by group
		$shadow = ilFAHShadowGroup::getInstanceByImportId($membership_info['id']);
		if(!$shadow->exists())
		{
			$this->logger->notice('Cannot find shadow group for id: ' . $membership_info['id']);
			return false;
		}
		$ref_id = $shadow->getParentId();
		$obj_id = ilObject::_lookupObjId($ref_id);
		
		if(!$obj_id)
		{
			$this->logger->notice('No obj_id found for '. $membership_info['id']);
			return false;
		}
		
		try {
			include_once './Services/Membership/classes/class.ilParticipants.php';
			$parent_part = ilParticipants::getInstanceByObjId($obj_id);
			if(!$parent_part instanceof ilCourseParticipants)
			{
				$this->logger->notice('Cannot create partipants object for ref_id: ' . $ref_id);
				return false;
			}
		}
		catch(InvalidArgumentException $e) {
			$this->logger->notice('Cannot create partipants object for ref_id: ' . $ref_id.' -> '. $e->getMessage());
			return false;
		}
		
		// desassign all users with import id that are not mentioned in membership info
		foreach($parent_part->getParticipants() as $user_id)
		{	
			$import_id = ilObject::_lookupImportId($user_id);
			$this->logger->debug('Assigned user import id is: ' . $import_id);
			if(
				$import_id &&
				!in_array($import_id, $membership_info['members'])
			)
			{
				$last_three = substr($membership_info['id'], -3);
				if(strcmp($last_three, 'DOZ') === 0)
				{
					if($parent_part->isAdmin($user_id))
					{
						$this->logger->info('Deassigning user with import id:'.$import_id);
						$parent_part->delete($user_id);
					}
				}
				else
				{
					if(!$parent_part->isAdmin($user_id))
					{
						$this->logger->info('Deassigning user with import id:'.$import_id);
						$parent_part->delete($user_id);
					}
				}
			}
		}
		
		// assign all new members
		foreach((array) $membership_info['members'] as $import_id)
		{
			$usr_id = $this->lookupObjId($import_id, 'usr');
			if(!$usr_id)
			{
				$this->logger->warning('Cannot find user with import id: ' . $import_id);
				continue;
			}
			if($parent_part->isAssigned($usr_id))
			{
				$this->logger->debug('User with import id: ' . $import_id.' is already assigned to course '. $obj_id);
			}
			
			// admin group
			$last_three = substr($membership_info['id'], -3);
			if(strcmp($last_three, 'DOZ') === 0)
			{
				$this->logger->info('Assigning user ' . $usr_id . ' as course admin.');
				$parent_part->add($usr_id,IL_CRS_ADMIN);
			}
			else
			{
				$this->logger->info('Assigning user ' . $usr_id . ' as course member.');
				$parent_part->add($usr_id,IL_CRS_MEMBER);
			}
		}
		if($parent_part instanceof ilParticipants)
		{
			$parent_part->delete(ilObjUser::_lookupId($this->settings->getSoapUser()));
			$parent_part->delete($GLOBALS['DIC']->user()->getId());
		}
	}
}
?>