<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHUserComponentImporter extends ilFAHComponentImporter
{
	
	/**
	 * Import from file
	 * @param type $a_file
	 * @throws Exception
	 */
	public function import($a_file)
	{
		try {
			$this->init($a_file);
			$writer = $this->parseXml();
			$this->updateUser($writer);
			$this->terminate();
		} 
		catch (Exception $ex) {
			throw $ex;
		}
	}
	
	/**
	 * Parse xml
	 */
	protected function parseXml()
	{
		include_once './Services/Xml/classes/class.ilXmlWriter.php';
		$writer = new ilXmlWriter();
		$writer->xmlHeader();
		$writer->xmlStartTag('Users');
		foreach($this->root->person as $user)
		{
			$action = 'Insert';
			
			$user_exists = (bool) count(
				ilObject::lookupObjIdsByImportId(
					(string) $user->sourcedid->id,
					'usr'
				)
			);
			if($user_exists)
			{
				$action = 'Update';
			}
			
			// User
			$writer->xmlStartTag(
				'User',
				array(
					'ImportId' => (string) $user->sourcedid->id,
					'Language' => 'de',
					'Action' => $action
				)
			);
			$writer->xmlElement(
				'Role',
				array(
					'Type' => 'Global',
					'Id' => 4
				),
				'User'
			);
			$writer->xmlElement('Login',null,(string) $user->userid);
			$writer->xmlElement('Firstname',null, (string) $user->name->n->given);
			$writer->xmlElement('Lastname',null, (string) $user->name->n->family);
			$writer->xmlElement('Email',null, (string) $user->email);
			$writer->xmlEndTag('User');
			
			$this->logger->debug('Found: ' . (string) $user->name->n->family);
		}
		$writer->xmlEndTag('Users');
		return $writer;
	}

	/**
	 * @param ilXmlWriter $writer
	 * @throws ilFAHSoapConnectionException | ilFAHImportException
	 */
	protected function updateUser(ilXmlWriter $writer)
	{
		$this->logger->debug('Xml user import: ' . $writer->xmlDumpMem(false));
		
		try {
			$this->logger->info('Starting import of users.');
			$res = $this->soap->call(
					'importUsers',
					array(
						$this->soap_session,
						USER_FOLDER_ID,
						$writer->xmlDumpMem(false),
						2,
						0
					)
				);
			$this->logger->info('Received response: '. $res);

			if(stristr($res, 'Login is not unique'))
			{
				$this->logger->error('One or more usernames are not unique.');
				$this->logger->error('Update aborted.');
				throw new ilFAHImportException(
					'Cannot update user data. Received response: ' . htmlentities($res)
				);
			}

		} 
		catch (Exception $ex) {
			$this->logger->error('User import failed with message: ' . $ex->getMessage());
			throw new ilFAHSoapConnectionException($ex->getMessage());
		}
		
		
	}
}
?>