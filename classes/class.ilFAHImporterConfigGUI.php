<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Component/classes/class.ilPluginConfigGUI.php';

/**
 * Description of class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFAHImporterConfigGUI extends ilPluginConfigGUI
{
	/**
	* Handles all commmands, default is "configure"
	*/
	public function performCommand($cmd)
	{
		global $ilCtrl;
		global $ilTabs;
		
		$ilTabs->addTab(
			'settings',
			ilFAHImporterPlugin::getInstance()->txt('tab_settings'),
			$GLOBALS['ilCtrl']->getLinkTarget($this,'configure')
		);
		
		$ilTabs->addTab(
			'import',
			ilFAHImporterPlugin::getInstance()->txt('tab_import'),
			$GLOBALS['ilCtrl']->getLinkTarget($this,'import')
		);
		
		
		switch ($cmd)
		{
			default:
				$this->$cmd();
				break;

		}
	}
	
	/**
	 * Show settings screen
	 * @global type $tpl
	 * @global type $ilTabs 
	 */
	protected function configure(ilPropertyFormGUI $form = null)
	{
		global $tpl, $ilTabs;
		
		$ilTabs->activateTab('settings');
		
		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initConfigurationForm();
			
		}
		
		$tpl->setContent($form->getHTML());
	}
	
	/**
	 * Init configuration form
	 * @global type $ilCtrl 
	 */
	protected function initConfigurationForm()
	{
		global $ilCtrl, $lng;
		
		$settings = ilFAHImporterSettings::getInstance();
		
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->getPluginObject()->txt('tbl_settings'));
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->addCommandButton('save', $lng->txt('save'));
		
		// log level
		$GLOBALS['DIC']->language()->loadLanguageModule('log');
		$level = new ilSelectInputGUI($this->getPluginObject()->txt('form_tab_settings_loglevel'),'log_level');
		$level->setOptions(ilLogLevel::getLevelOptions());
		$level->setValue($settings->getLogLevel());
		$form->addItem($level);

		// import lock
		$lock = new ilCheckboxInputGUI($this->getPluginObject()->txt('tbl_setting_lock'),'lock');
		$lock->setValue(1);
		$lock->setDisabled(!$settings->isLocked());
		$lock->setChecked($settings->isLocked());
		$form->addItem($lock);
		
		$soap_user = new ilTextInputGUI($this->getPluginObject()->txt('tbl_setting_soap_user'),'user');
		$soap_user->setValue($settings->getSoapUser());
		$soap_user->setRequired(true);
		$soap_user->setSize(16);
		$soap_user->setMaxLength(128);
		$form->addItem($soap_user);
		
		$soap_pass = new ilPasswordInputGUI($this->getPluginObject()->txt('tbl_setting_soap_pass'),'pass');
		$soap_pass->setValue($settings->getSoapPass());
		$soap_pass->setSkipSyntaxCheck(TRUE);
		$soap_pass->setRetype(false);
		$soap_pass->setRequired(true);
		$soap_pass->setSize(16);
		$soap_pass->setMaxLength(128);
		$form->addItem($soap_pass);
		
		
		// import directory 
		$imp = new ilTextInputGUI($this->getPluginObject()->txt('form_tab_settings_import_directory'),'import_directory');
		$imp->setValue($settings->getImportDirectory());
		$imp->setSize(32);
		$imp->setMaxLength(512);
		$imp->setRequired(true);
		$form->addItem($imp);
		
		$backup = new ilTextInputGUI($this->getPluginObject()->txt('tbl_settings_backup'),'backup');
		$backup->setRequired(true);
		$backup->setSize(120);
		$backup->setMaxLength(512);
		$backup->setValue($settings->getBackupDir());
		$form->addItem($backup);
		
		// cron intervall
		$cron_i = new ilNumberInputGUI($this->getPluginObject()->txt('cron'),'cron_interval');
		$cron_i->setMinValue(1);
		$cron_i->setSize(2);
		$cron_i->setMaxLength(3);
		$cron_i->setRequired(true);
		$cron_i->setValue($settings->getCronInterval());
		$cron_i->setInfo($this->getPluginObject()->txt('cron_interval'));
		$form->addItem($cron_i);
		
		
		$form->setShowTopButtons(false);
		
		return $form;
	}
	
	/**
	 * Save settings
	 */
	protected function save()
	{
		global $lng, $ilCtrl;
		
		$form = $this->initConfigurationForm();
		$settings = ilFAHImporterSettings::getInstance();
		
		if($form->checkInput())
		{
			$settings->setLogLevel($form->getInput('log_level'));
			$settings->enableLock($form->getInput('lock'));
			$settings->setSoapUser($form->getInput('user'));
			$settings->setSoapPass($form->getInput('pass'));
			$settings->setCronInterval($form->getInput('cron_interval'));
			$settings->setImportDirectory($form->getInput('import_directory'));
			$settings->setBackupDir($form->getInput('backup'));
			
			$settings->update();
				
			ilUtil::sendSuccess($lng->txt('settings_saved'),true);
			$ilCtrl->redirect($this,'configure');
		}
		
		$error = $lng->txt('err_check_input');
		$form->setValuesByPost();
		ilUtil::sendFailure($error);
		$this->configure($form);
	}
	
	/**
	 * Start import
	 */
	protected function import(ilPropertyFormGUI $form = null)
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab('import');

		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initImportForm();
		}
		$tpl->setContent($form->getHTML());
	}
	
	/**
	 * Show import settings
	 * @global type $ilCtrl
	 * @global type $lng
	 * @return \ilPropertyFormGUI
	 */
	protected function initImportForm()
	{
		global $ilCtrl, $lng;
		
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->getPluginObject()->txt('tbl_import'));
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->addCommandButton('doImport', $this->getPluginObject()->txt('btn_import'));
		
		// selection all or single elements
		$imp_type = new ilRadioGroupInputGUI($this->getPluginObject()->txt('import_selection'),'selection');
		$imp_type->setValue(ilFAHImporter::IMPORT_SELECTED);
		$imp_type->setRequired(true);
		$form->addItem($imp_type);
		
		$all = new ilRadioOption($this->getPluginObject()->txt('import_selection_all'),ilFAHImporter::IMPORT_ALL);
		$imp_type->addOption($all);
		
		$sel = new ilRadioOption($this->getPluginObject()->txt('import_selection_selected'), ilFAHImporter::IMPORT_SELECTED);
		$imp_type->addOption($sel);
		
		$usr = new ilCheckboxInputGUI($lng->txt('objs_usr'),'usr');
		$usr->setValue(ilFAHImporter::TYPE_USR);
		$sel->addSubItem($usr);

		$cat = new ilCheckboxInputGUI($lng->txt('objs_cat'),'cat');
		$cat->setValue(ilFAHImporter::TYPE_CAT);
		$sel->addSubItem($cat);

		$crs = new ilCheckboxInputGUI($lng->txt('objs_crs'),'crs');
		$cat->setValue(ilFAHImporter::TYPE_CRS);
		$sel->addSubItem($crs);

		$grp = new ilCheckboxInputGUI($lng->txt('objs_grp'),'grp');
		$cat->setValue(ilFAHImporter::TYPE_GRP);
		$sel->addSubItem($grp);

		$mem = new ilCheckboxInputGUI($this->getPluginObject()->txt('type_membership'),'mem');
		$mem->setValue(ilFAHImporter::TYPE_MEM);
		$sel->addSubItem($mem);

		$form->setShowTopButtons(false);

		return $form;
	}
	
	protected function doImport()
	{
		global $lng, $ilCtrl;
		
		$form = $this->initImportForm();
		$import = ilFAHImporter::getInstance();
		
		if($form->checkInput())
		{
			if($form->getInput('selection') == ilFAHImporter::IMPORT_ALL)
			{
				$import->addType(ilFAHImporter::TYPE_USR);
			}
			else
			{
				if($form->getInput('usr'))
				{
					$import->addType(ilFAHImporter::TYPE_USR);
				}
				if($form->getInput('cat'))
				{
					$import->addType(ilFAHImporter::TYPE_CAT);
				}
				if($form->getInput('crs'))
				{
					$import->addType(ilFAHImporter::TYPE_CRS);
				}
				if($form->getInput('grp'))
				{
					$import->addType(ilFAHImporter::TYPE_GRP);
				}
				if($form->getInput('mem'))
				{
					$import->addType(ilFAHImporter::TYPE_MEM);
				}
			}
			
			// Perform import
			try 
			{
				$import->import();
				ilUtil::sendSuccess($this->getPluginObject()->txt('import_success'),true);
				$ilCtrl->redirect($this,'import');
			}
			catch(ilException $e)
			{
				ilUtil::sendFailure($e->getMessage(),true);
				$ilCtrl->redirect($this,'import');
			}
		}
		ilUtil::sendFailure($lng->txt('err_check_input'));
		$this->import($form);
		
	}
	
	
}
?>