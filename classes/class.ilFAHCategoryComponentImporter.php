<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHCategoryComponentImporter extends ilFAHComponentImporter
{
	
	/**
	 * Import from file
	 * @param type $a_file
	 * @throws Exception
	 */
	public function import($a_file)
	{
		$this->logger->info('Starting import parsing for categories');
		
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
			if(!$this->isCategory((string) $group_element->sourcedid->id))
			{
				$this->logger->debug('Ignoring ' . (string) $group_element->description->short .' for category import.');
				continue;
			}
			
			
			$category_info = [];
			$category_info['title'] = (string) $group_element->description->short;
			$category_info['parent_id'] = (string) $group_element->relationship->sourcedid->id;
			$category_info['id'] = (string) $group_element->sourcedid->id;
			
			try {
				$this->refreshCategory($category_info);
			}
			catch(Exception $e) {
				$this->logger->error('Category structure update failed with message: ' . $e->getMessage());
				$this->logger->dump($category_info, ilLogLevel::ERROR);
				throw $e;
			}
		}
	}
	
	/**
	 * Check if object is category
	 * @param type $a_title
	 */
	protected function isCategory($a_id)
	{
		$this->logger->debug('Validating ' . $a_id);
		if(preg_match('/^[0-9]{4}_R$/', $a_id))
		{
			$this->logger->debug($a_id . ' matches [0-9]{4}_R');
			return true;
		}
		if(strcmp('Jahresprogramm', $a_id) === 0)
		{
			$this->logger->debug($a_id . ' matches Jahresprogramm.');
			return true;
		}
		if(strcmp('Benutzergruppen', $a_id) === 0)
		{
			$this->logger->debug($a_id . ' matches Benutzergruppen.');
			return true;
		}
		if(strcmp('Zielgruppen', $a_id) === 0)
		{
			$this->logger->debug($a_id . ' matches Zielgruppen.');
			return true;
		}
		$this->logger->info('Ignoring structural element: ' . $a_id);
		return false;
	}
	
	/**
	 * refresh category
	 */
	protected function refreshCategory($cat_info)
	{
		include_once './Services/Xml/classes/class.ilXmlWriter.php';
		$writer = new ilXmlWriter();
		$writer->xmlStartTag('Objects');
		
		$obj_id = $this->lookupObjId($cat_info['id'], 'cat');
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
				'type' => 'cat'
			)
		);
		$writer->xmlElement('Title',[], $cat_info['title']);
		$writer->xmlElement('ImportId',[], $cat_info['id']);
		$writer->xmlEndTag('Object');
		$writer->xmlEndTag('Objects');
		
		if($do_create)
		{
			$this->logger->info('Calling create for: '. $writer->xmlDumpMem(false));
			
			$parent_id = $this->lookupParentId($cat_info['parent_id'], 'cat');
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