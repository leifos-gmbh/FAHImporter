<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
abstract class ilFAHComponentImporter
{
	const DEFAULT_SOAP_RESPONSE_TIMEOUT = 1800;
	
	const DEFAULT_ROOT_ID = 'INST_ROOT';
	
	/**
	 * @var ilLogger
	 */
	protected $logger = null;
	
	/**
	 * Simple xml root element
	 * @var SimpleXMLElement
	 */
	protected $root = null;
	
	
	/**
	 * @var ilFAHImporterSettings
	 */
	protected $settings = null;
	
	/**
	 * Soap client
	 * @var ilSoapClient
	 */
	protected $soap = null;
	
	/**
	 * @var string
	 */
	protected $soap_session = '';
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->logger = $GLOBALS['DIC']->logger()->fahi();
		$this->settings = ilFAHImporterSettings::getInstance();
	}
	
	/**
	 * Init input stream from file
	 * @param type $a_input_file
	 * @return void
	 * @throws ilFAHImportException
	 */
	public function initInputStream($a_input_file)
	{
		$this->logger->debug((string) $a_input_file);
		libxml_use_internal_errors(true);
		$root = simplexml_load_file($a_input_file);
		if(!$root instanceof SimpleXMLElement)
		{
			foreach(libxml_get_errors() as $error)
			{
				$this->logger->error($error->message);
			}
			$this->logger->error('Cannot read xml from input file: ' . $a_input_file);
			throw new ilFAHImportException('Cannot read xml from input: ' . $a_input_file);
		}
		$this->root = $root;
	}
	
	/**
	 * Start import
	 * @param type $a_file
	 * @return boolean
	 * @throws ilFAHImportException
	 * @throws ilFAHSoapConnectionException
	 */
	protected function init($a_file)
	{
		$this->initInputStream($a_file);
		$this->initSoapConnection();
		return true;
	}
	
	/**
	 * @throws ilFAHSoapConnectionException
	 */
	protected function terminate()
	{
		$this->logoutSoap();
	}
	
	
	
	/**
	 * Init soap connection
	 * @throws ilFAHSoapConnectionException
	 */
	public function initSoapConnection()
	{
		include_once './Services/WebServices/SOAP/classes/class.ilSoapClient.php';
		
		$this->soap = new ilSoapClient();
		$this->soap->setErrorHandlingForClientCalls(ilSoapClient::ERROR_HANDLING_FOR_CLIENT_CALLS_EXCEPTION);
		$this->soap->setResponseTimeout(self::DEFAULT_SOAP_RESPONSE_TIMEOUT);
		$this->soap->enableWSDL(true);
		if(!$this->soap->init())
		{
			throw new ilFAHSoapConnectionException('Error calling soap server.');
		}
		
		// login soap
		$this->loginSoap();
	}

	/**
	 * Login soap
	 * @throws ilFAHSoapConnectionException
	 */
	protected function loginSoap()
	{
		try {
			$res = $this->soap->call(
					'login', 
					array(
						CLIENT_ID,
						$this->settings->getSoapUser(),
						$this->settings->getSoapPass()
					)
			);
			$this->soap_session = $res;
			$this->logger->debug('Login soap: ' . $this->soap_session);
			return true;
		} 
		catch (Exception $ex) {
			$this->logger->error('Soap login failed with message: ' . $ex->getMessage());
			throw new ilFAHSoapConnectionException($ex->getMessage());
		}
	}
	
	/**
	 * Logout soap
	 * @throws ilFAHSoapConnectionException
	 */
	protected function logoutSoap()
	{
		if(!$this->soap instanceof SoapClient)
		{
			return false;
		}
		
		try {
			$this->soap->call(
				'logout',
				array(
					$this->soap_session
				)
			);
			$this->logger->debug('Logged out from soap webservice.');
		} 
		catch (Exception $ex) {
			$this->logger->error('Soap logout failed with message: ' . $ex->getMessage());
			throw new ilFAHSoapConnectionException($ex->getMessage());
		}
	}
	
	/**
	 * Lookup obj id
	 * @param type $a_id
	 * @param type $a_type
	 * @return int
	 */
	protected function lookupObjId($a_id, $a_type = '')
	{
		global $DIC;

		$ilDB = $DIC->database();
		
		$query = 'SELECT obj_id FROM object_data '.
				'WHERE import_id = '.$ilDB->quote($a_id,'text').' ';
		
		if($a_type)
		{
			$query  .= 'AND type = '.$ilDB->quote($a_type,'text');
		}
		
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			return $row->obj_id;
		}
		return 0;
	}
	
	/**
	 * Return ref_id
	 * @param type $a_id
	 * @return int
	 */
	protected function lookupParentId($a_id,$a_type = '')
	{
		$this->logger->debug('Lookup parent id for: '. $a_id);
		if($a_id == self::DEFAULT_ROOT_ID)
		{
			return ROOT_FOLDER_ID;
		}
		$obj_id = $this->lookupObjId($a_id,$a_type);
		$this->logger->debug('Found obj_id = ' . $obj_id.' for id ' . $a_id);
		$refs = ilObject::_getAllReferences($obj_id);
		$ref_id = end($refs);
		$this->logger->debug('Using ref_id: ' . $ref_id);
		return $ref_id;
	}
	
	/**
	 * Lookup reference id for object
	 * @param type $a_obj_id
	 * @return type
	 */
	protected function lookupReferenceId($a_obj_id)
	{
		$refs = ilObject::_getAllReferences($a_obj_id);
		return end($refs);
	}
	
}
?>