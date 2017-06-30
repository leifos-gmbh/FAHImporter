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
			
			// ignore direct course membership assignments, since these only 
			// assign groups
			if(strcmp(substr($membership_info['id'],-2), '_R') === 0)
			{
				$this->logger->info('Ignoring direct course membership assignments.');
				continue;
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
		
		include_once './Services/Membership/classes/class.ilParticipants.php';
		$obj_id = $this->lookupObjId($membership_info['id']);
		if(!$obj_id)
		{
			$this->logger->warning('Cannot find course/group for import id: ' . $membership_info['id']);
			return false;
		}
		$part = ilParticipants::getInstanceByObjId($obj_id);
		$type = ilObject::_lookupType($obj_id);
		
		$ref_id = $this->lookupReferenceId($obj_id);
		$parent_part = null;
		if($ref_id)
		{
			$parent_ref_id = $GLOBALS['tree']->getParentId($ref_id);
			$parent_type = ilObject::_lookupType($parent_ref_id, true);
			if($parent_type == 'crs')
			{
				$parent_part = ilParticipants::getInstanceByObjId(ilObject::_lookupObjId($parent_ref_id));
			}
		}
		
		// desassign all users with import id that are not mentioned in membership info
		foreach($part->getParticipants() as $user_id)
		{
			$import_id = ilObject::_lookupImportId($user_id);
			$this->logger->debug('Assigned user import id is: ' . $import_id);
			if(
				$import_id &&
				!in_array($import_id, $membership_info['members'])
			)
			{
				// deassign
				$this->logger->info('Deassigning user with import id:'.$import_id);
				$part->delete($user_id);
				if($parent_part instanceof ilCourseParticipants)
				{
					$this->logger->info('Deassning user from parent course.');
					$parent_part->delete($user_id);
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
			if($part->isAssigned($usr_id))
			{
				$this->logger->debug('User with import id: ' . $import_id.' is already assigned to course/group '. $obj_id);
				if($parent_part instanceof ilCourseParticipants)
				{
					$last_three = substr($membership_info['id'], -3);
					if(strcmp($last_three, 'DOZ') === 0)
					{
						if(!$parent_part->isAdmin($usr_id))
						{
							$this->logger->info('Assigned user as admin in parent course.');
							$parent_part->add($usr_id, IL_CRS_ADMIN);
						}
					}
					elseif(!$parent_part->isAssigned($usr_id))
					{
						$this->logger->info('Assigned user as member in parent course.');
						$parent_part->add($usr_id, IL_CRS_MEMBER);
					}
				}
				continue;
			}
			
			
			switch($type)
			{
				case 'crs':
					$this->logger->info('Assigning user ' . $usr_id . ' to crs.');
					$part->add($usr_id, IL_CRS_MEMBER);
					break;
				
				case 'grp':
					$this->logger->info('Assigning user ' . $usr_id . ' to grp.');
					$part->add($usr_id, IL_GRP_MEMBER);
					break;
			}
			
			if($parent_part instanceof ilCourseParticipants)
			{
				$last_three = substr($membership_info['id'], -3);
				if(strcmp($last_three, 'DOZ') === 0)
				{
					$this->logger->debug('Assign to parent course...');
					if(!$parent_part->isAdmin($usr_id))
					{
						$this->logger->info('Assigned user as admin in parent course.');
						$parent_part->add($usr_id, IL_CRS_ADMIN);
					}
					elseif(!$parent_part->isAssigned($usr_id))
					{
						$this->logger->info('Assigned user as member in parent course.');
						$parent_part->add($usr_id, IL_CRS_MEMBER);
					}
				}
			}
			else
			{
				$this->logger->debug('Parent participants not found');
			}
		}
	}
}
?>