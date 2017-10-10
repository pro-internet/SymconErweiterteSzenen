<?
class ErweiterteSzenenSteuerung extends IPSModule {

	/////////////
	// new     //
	/////////////

	public $status;
	public $statusPath;
	public $settings;
	public $settingsPath;

	public function __construct($InstanceID) {
		//Never delete this line!
		parent::__construct($InstanceID);
		
		//Path to the Settings/Backup Parent Folder
		$docsPath = $_ENV['PUBLIC'] . '\Documents\Symcon Modules';
		//Path to the Settings/Backup Folder
		$docsFolderPath = $docsPath . '\\' . $this->InstanceID;
		//Path to the Settings File
		$docsSettingsFile = $docsFolderPath . '\\' . 'settings' . '.json';
		//Path to the Status File
		$docsStatusFile = $docsFolderPath . '\\' . 'status' . '.json'; 
		//Create Parent Folder for all the Instances
		if (!file_exists($docsPath)) {
			@mkdir($docsPath, 0777, true);
		}
		//Create Folder for this Instance
		if (!file_exists($docsFolderPath)) {
			@mkdir($docsFolderPath, 0777, true);
		}
		//Create Settings File
		if (!file_exists($docsSettingsFile)) {
			$fh = @fopen($docsSettingsFile, 'w');
			@fclose($fh);
		}
		//Create Status File
		if (!file_exists($docsStatusFile)) {
			$fh = @fopen($docsStatusFile, 'w');
			@fclose($fh);
		}

		$this->status = file_get_contents($docsStatusFile);
		$this->statusPath = $docsStatusFile;
		$this->settings = json_decode($docsSettingsFile, true);
		$this->settingsPath = $docsSettingsFile;
	}

	public function Create() {
		//Never delete this line!
		parent::Create();

		//Properties
		if(@$this->RegisterPropertyString("Names") !== false)
		{
			$this->RegisterPropertyString("Names", "");
			$this->RegisterPropertyInteger("Sensor", 0);
			$this->CreateSetValueScript($this->InstanceID);
		}

		if(!IPS_VariableProfileExists("SZS.SceneControl")){
			IPS_CreateVariableProfile("SZS.SceneControl", 1);
			IPS_SetVariableProfileValues("SZS.SceneControl", 1, 2, 0);
			//IPS_SetVariableProfileIcon("SZS.SceneControl", "");
			IPS_SetVariableProfileAssociation("SZS.SceneControl", 1, "Speichern", "", -1);
			IPS_SetVariableProfileAssociation("SZS.SceneControl", 2, "Ausführen", "", -1);
		}

		if(!IPS_VariableProfileExists("Switch")){
			 // Create Switch Profile if its not exists
			 IPS_CreateVariableProfile("Switch",0);
			 IPS_SetVariableProfileValues("Switch",0,1,1);
			 IPS_SetVariableProfileAssociation("Switch",0,"Aus","",-1);
			 IPS_SetVariableProfileAssociation("Switch",1,"An","", 0x8000FF);
			 IPS_SetVariableProfileIcon("Switch","Power");

			 IPS_SetVariableCustomProfile($vid,"Switch");
		}

	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
		
		//Get the content of the Table
		$dataString = $this->ReadPropertyString("Names");
		//check if the Table String is empty
		if($dataString != "")
		{
			$data = json_decode($dataString, true);
			//make sure the Content of the Table is valid
			if(sizeof($data) > 0)
			{
				//check if it's currently reiterating the apply changes process
				if($this->status != 'reiterating')
				{
					IPS_SetPosition($this->InstanceID, -700);

					$this->RemoveExcessiveProfiles("ESZS.Selector");
					$this->RemoveExcessiveProfiles("ESZS.Sets");

					//Create all the other, table independent Objects
					{
						//Get the ID of the SetValue Script
						$svs = IPS_GetObjectIDByIdent("SetValueScript", $this->InstanceID);
						
						//Create Targets Dummy Instance
						if(@IPS_GetObjectIDByIdent("Targets", IPS_GetParent($this->InstanceID)) === false)
						{
							$DummyGUID = $this->GetModuleIDByName();
							$insID = IPS_CreateInstance($DummyGUID);
							IPS_SetName($insID, "Targets");
							IPS_SetParent($insID, IPS_GetParent($this->InstanceID));
							IPS_SetPosition($insID, 9999);
							IPS_SetIdent($insID, "Targets");
						}
						else
						{
							$insID = IPS_GetObjectIDByIdent("Targets", IPS_GetParent($this->InstanceID));
						}

						//Events Category
						if(@IPS_GetObjectIDByIdent("EventsCat", $this->InstanceID) === false)
						{
							$eventsCat = IPS_CreateCategory();
							IPS_SetName($eventsCat, "Events");
							IPS_SetIdent($eventsCat, "EventsCat");
							IPS_SetParent($eventsCat, $this->InstanceID);
							IPS_SetHidden($eventsCat, true);
							IPS_SetPosition($eventsCat, -10000);
						}
						else
						{
							$eventsCat = IPS_GetObjectIDByIdent("EventsCat", $this->InstanceID);
						}

						//Create the Selector Profile
						if(IPS_VariableProfileExists("ESZS.Selector" . $this->InstanceID))
						{
							IPS_DeleteVariableProfile("ESZS.Selector" . $this->InstanceID);
							IPS_CreateVariableProfile("ESZS.Selector" . $this->InstanceID, 1);
							IPS_SetVariableProfileIcon("ESZS.Selector" . $this->InstanceID, "Rocket");
						}
						else
						{
							IPS_CreateVariableProfile("ESZS.Selector" . $this->InstanceID, 1);
							IPS_SetVariableProfileIcon("ESZS.Selector" . $this->InstanceID, "Rocket");
						}

						//Selector Variable
						if(@IPS_GetObjectIDByIdent("Selector", IPS_GetParent($this->InstanceID)) === false)
						{
							$vid = IPS_CreateVariable(1);
							IPS_SetParent($vid, IPS_GetParent($this->InstanceID));
							IPS_SetName($vid, IPS_GetName($this->InstanceID));
							IPS_SetIdent($vid, "Selector");
							IPS_SetPosition($vid, 600);
							IPS_SetVariableCustomProfile($vid, "ESZS.Selector" . $this->InstanceID);
							IPS_SetVariableCustomAction($vid, $svs);
						}
						else
						{
							$vid = IPS_GetObjectIDByIdent("Selector", IPS_GetParent($this->InstanceID));
						}

						//Create Selector event
						if(@IPS_GetObjectIDByIdent("SelectorOnChange", $eventsCat) === false)
						{
							$eid = IPS_CreateEvent(0);
							IPS_SetParent($eid, $eventsCat);
							IPS_SetName($eid, "Selector.OnChange");
							IPS_SetIdent($eid, "SelectorOnChange");
							IPS_SetEventTrigger($eid, 0, $vid);
							IPS_SetEventActive($eid, true);
							IPS_SetEventScript($eid, "ESZS_CallScene(". $this->InstanceID .", GetValue($vid));");
						}
						else
						{
							$eid = IPS_GetObjectIDByIdent("SelectorOnChange", $eventsCat);
						}

						//Get the ID of the Sensor
						$sensorID = $this->ReadPropertyInteger("Sensor");
						//validate the variable ID of the Sensor
						if($sensorID > 9999)
						{
							//Create Sensor event
							if(@IPS_GetObjectIDByIdent("SensorEvent", $eventsCat) === false)
							{
								$eid = IPS_CreateEvent(0);
								IPS_SetEventTrigger($eid, 1, $sensorID);
								IPS_SetEventScript($eid, "ESZS_CallScene(". $this->InstanceID .", ($sensorID *100));");
								IPS_SetEventActive($eid, true);
								IPS_SetParent($eid, $eventsCat);
								IPS_SetName($eid, "Sensor.OnChange");
								IPS_SetIdent($eid, "SensorEvent");
							}
							else
							{
								$eid = IPS_GetObjectIDByIdent("SensorEvent", $eventsCat);
							}	
						}

						//Create Automatik for this instance
						if(@IPS_GetObjectIDByIdent("Automatik", IPS_GetParent($this->InstanceID)) === false)
						{
							$vid = IPS_CreateVariable(0);
							IPS_SetName($vid, "Automatik");
							IPS_SetParent($vid, IPS_GetParent($this->InstanceID));
							IPS_SetPosition($vid, -999);
							IPS_SetIdent($vid, "Automatik");
							IPS_SetVariableCustomAction($vid, $svs);
							IPS_SetVariableCustomProfile($vid, "Switch");
						}
						else
						{
							$vid = IPS_GetObjectIDByIdent("Automatik", IPS_GetParent($this->InstanceID));
						}
						
						//Create Event for Automatik
						if(@IPS_GetObjectIDByIdent("AutomatikEvent", $eventsCat) === false)
						{
							$eid = IPS_CreateEvent(0);
							IPS_SetEventTriggerValue($eid, true);
							IPS_SetEventActive($eid, true);
							IPS_SetParent($eid, $eventsCat);
							IPS_SetName($eid, "Automatik.OnTrue");
							IPS_SetIdent($eid, "AutomatikEvent");
						}
						else
						{
							$eid = IPS_GetObjectIDByIdent("AutomatikEvent", $eventsCat);
						}
						IPS_SetEventTrigger($eid, 4, $vid);
						IPS_SetEventScript($eid, "ESZS_CallScene(". $this->InstanceID .", ($sensorID *100));");

						//Create Sperre for this Instance
						if(@IPS_GetObjectIDByIdent("Sperre", IPS_GetParent($this->InstanceID)) === false)
						{
							$vid = IPS_CreateVariable(0);
							IPS_SetName($vid, "Sperre");
							IPS_SetParent($vid, IPS_GetParent($this->InstanceID));
							IPS_SetPosition($vid, -999);
							IPS_SetIdent($vid, "Sperre");
							IPS_SetVariableCustomAction($vid, $svs);
							IPS_SetVariableCustomProfile($vid, "Switch");
						}
						else
						{
							$vid = IPS_GetObjectIDByIdent("Sperre", IPS_GetParent($this->InstanceID));
						}

						//Create Event for Sperre
						if(@IPS_GetObjectIDByIdent("SperreEvent", $eventsCat) === false)
						{
							$eid = IPS_CreateEvent(0);
							IPS_SetEventTriggerValue($eid, false);
							IPS_SetEventActive($eid, true);
							IPS_SetParent($eid, $eventsCat);
							IPS_SetName($eid, "Sperre.OnFalse");
							IPS_SetIdent($eid, "SperreEvent");
						}
						else
						{
							$eid = IPS_GetObjectIDByIdent("SperreEvent", $eventsCat);
						}
						IPS_SetEventTrigger($eid, 4, $vid);
						IPS_SetEventScript($eid, "ESZS_CallScene(". $this->InstanceID .", ($sensorID *100));");

						//Create all the states (Morgen, Tag...)
						//Validate the Sensor Variable ID
						if($sensorID > 9999)
						{
							//Create Dummy Set Instance
							if(@IPS_GetObjectIDByIdent("Set", IPS_GetParent($this->InstanceID)) === false)
							{
								$DummyGUID = $this->GetModuleIDByName();
								$insID = IPS_CreateInstance($DummyGUID);
								IPS_SetName($insID, "DaySets");
								IPS_SetParent($insID, IPS_GetParent($this->InstanceID));
								IPS_SetPosition($insID, -500);
								IPS_SetIdent($insID, "Set");
							}
							else
							{
								$insID = IPS_GetObjectIDByIdent("Set", IPS_GetParent($this->InstanceID));
							}

							$sets = array("Früh","Morgen","Tag","Dämmerung","Abend","Nacht");
							//Create the Variables for each entry in the sets array
							foreach($sets as $i => $state)
							{
								if(@IPS_GetObjectIDByIdent("set$i", $insID) === false)
								{
									$vid = IPS_CreateVariable(1);
									IPS_SetName($vid, $state);
									IPS_SetParent($vid, $insID);
									IPS_SetPosition($vid, $i);
									IPS_SetIdent($vid, "set$i");
									IPS_SetVariableCustomAction($vid, $svs);
									IPS_SetVariableCustomProfile($vid, "ESZS.Selector" . $this->InstanceID);	
								}
								else
								{
									$vid = IPS_GetObjectIDByIdent("set$i", $insID);
								}
								//Create Events for the States
								if(@IPS_GetObjectIDByIdent("SetEvent$i", $eventsCat) === false)
								{
									$eid = IPS_CreateEvent(0);
									IPS_SetEventTrigger($eid, 1, $vid);
									IPS_SetEventScript($eid, "ESZS_CallScene(" . $this->InstanceID . ", ($sensorID *100));");
									IPS_SetName($eid, "$state".".OnChange");
									IPS_SetParent($eid, $eventsCat);
									IPS_SetIdent($eid, "SetEvent$i");
									IPS_SetEventActive($eid, true);
								}
								else
								{
									$eid = IPS_GetObjectIDByIdent("SetEvent$i", $eventsCat);
								}
							}
						}
					}

					$reiterate = false;
					foreach($data as $i => $entry)
					{
						//check if any positions were set
						if($entry['Position'] == 0 || array_key_exists('Position', $entry) !== true)
						{
							$data[$i]['Position'] = $i;
							$reiterate = true;
							$this->SetStatus('reiterating');
						}

						//check if a Valid ID is set to this entry
						if($entry['ID'] == 0 || $entry['ID'] == null || array_key_exists('ID', $entry) !== true)
						{
							//Set a new ID in case no ID was set
							$data[$i]['ID'] = rand(10000, 99999);
							//tell the rest of the script to reload down the line with the new IDs
							$reiterate = true;
							$this->SetStatus('reiterating');
						}
						
						//check if a Valid Name is set to this entry
						if($entry['name'] == null || array_key_exists('ID', $entry) !== true)
						{
							//Set a new ID in case no ID was set
							$data[$i]['name'] = "Szene$i";
							//tell the rest of the script to reload down the line with the new IDs
							$reiterate = true;
							$this->SetStatus('reiterating');
						}
					}

					//reiterate the Modules apply changes function with the newly added IDs
					if($reiterate)
					{
						$configModule = json_decode(IPS_GetConfiguration($this->InstanceID), true);
						$configModule['Names'] = json_encode($data);
						$configJSON = json_encode($configModule);
						IPS_SetConfiguration($this->InstanceID, $configJSON);
						IPS_ApplyChanges($this->InstanceID);
						return;
					}
				}
				$this->SetStatus('runnung');

				//sort the data array by the Position
				usort($data, function($a, $b) {
					return $a['Position'] - $b['Position'];
				});

				foreach($data as $i => $entry)
				{
					$ID = $entry['ID'];
					//Create Scene Variable
					if(@IPS_GetObjectIDByIdent("Scene".$ID, $this->InstanceID) === false)
					{
						IPS_LogMessage("DaySet Module", "Creating new Scene Variable... " . $entry['name'] . " | " . "Scene".$ID);
						$vid = IPS_CreateVariable(1);
						IPS_SetIdent($vid, "Scene".$ID);
						IPS_SetParent($vid, $this->InstanceID);
						SetValue($vid, 2);
						IPS_SetVariableCustomProfile($vid, "SZS.SceneControl");
						$this->EnableAction("Scene".$ID);
					}
					else
					{
						$vid = IPS_GetObjectIDByIdent("Scene".$ID, $this->InstanceID);
					}
					IPS_SetName($vid, $entry['name']);
					IPS_SetPosition($vid, $entry['Position']);

					//Create SceneData Variable
					if(@IPS_GetObjectIDByIdent("Scene".$ID."Data", $this->InstanceID) === false)
					{
						IPS_LogMessage("DaySet_Scenes", "Creating new SceneData Variable..." . $entry['name'] . "Data I " . "Scene".$ID . "Data");
						$vid = IPS_CreateVariable(3);
						IPS_SetIdent($vid, "Scene".$ID."Data");
						IPS_SetParent($vid, $this->InstanceID);
						IPS_SetHidden($vid, true);
					}
					else
					{
						$vid = IPS_GetObjectIDByIdent("Scene".$ID."Data", $this->InstanceID);
					}
					IPS_SetName($vid, $entry['name']."Data");/*highest position value in the table*/
					IPS_SetPosition($vid, abs($entry['Position']) + $data[sizeof($data) - 1]['Position'] + 1);
				
					//Set Selector profile
					IPS_SetVariableProfileAssociation("ESZS.Selector" . $this->InstanceID, ($i), $entry['name'],"",-1);
				}
				IPS_SetVariableProfileValues("ESZS.Selector" . $this->InstanceID, 0, $i, 0);

				//rearrange data array to have IDs as keys
				$dataByID = array();
				foreach ($data as &$entry) {
					$dataByID[$entry['ID']] = &$entry;
				}

				//Delete Excessive Scenes
				foreach(IPS_GetChildrenIDs($this->InstanceID) as $child)
				{
					$ident = @IPS_GetObject($child)['ObjectIdent'];
					//only allow Scenes to pass through
					if(strpos($ident, "Scene") !== false && strlen($ident) < 11)
					{
						$ID = str_replace("Scene", "", $ident);
						if(array_key_exists($ID, $dataByID) === false)
						{
							//Delete Scene
							IPS_DeleteVariable($child);
							//Get Associated SceneData
							$vid = IPS_GetObjectIDByIdent("Scene" . $ID . "Data", $this->InstanceID);
							IPS_DeleteVariable($vid);
						}
					}
				}
				//Get Sensor ID
				$sensorID = $this->ReadPropertyInteger("Sensor");
				//Delete Excessive Sets/Automation/Sperre
				if($sensorID < 9999) //no sensor set
				{
					$insID = @IPS_GetObjectIDByIdent("Set", IPS_GetParent($this->InstanceID));
					if(isset($insID)) $this->Del($insID);
					$vid = @IPS_GetObjectIDByIdent("Automatik", IPS_GetParent($this->InstanceID));
					if(isset($vid)) IPS_DeleteVariable($vid);
					$vid =  @IPS_GetObjectIDByIdent("Sperre", IPS_GetParent($this->InstanceID));
					if(isset($vid)) IPS_DeleteVariable($vid);
				}
			}
		}
		return;
	}

	public function RequestAction($Ident, $Value) {

		switch($Value) {
			case "1":
				$this->SaveValues($Ident);
				break;
			case "2":
				$this->CallValues($Ident);
				break;
			default:
				throw new Exception("Invalid action");
		}
	}

	public function CallScene(int $SceneNumber){
		if($SceneNumber > 99999) //sender = Sensor
		{
			$SceneNumber = floor($SceneNumber/100);
			$sensorWert = GetValue($SceneNumber) - 1;
			$setsIns = IPS_GetObjectIDByIdent("Set", IPS_GetParent($this->InstanceID));
			if($sensorWert > -1 && $sensorWert < sizeof(IPS_GetChildrenIDs($setsIns)))
			{
				$set = IPS_GetObjectIDByIdent("set$sensorWert", $setsIns);
				$ActualSceneNumber = GetValue($set);
				//Get Scene Identity by Association name 
				$assoc = IPS_GetVariableProfile("ESZS.Selector" . $this->InstanceID)['Associations'][$ActualSceneNumber];
				$sceneVar = IPS_GetObjectIDByName($assoc['Name'], $this->InstanceID);
				$actualIdent = IPS_GetObject($sceneVar)['ObjectIdent'];
				$this->CallValues($actualIdent."Sensor");
			}
		}
		else
		{
			if($SceneNumber < 10000) //sender = selector
			{
				$assoc = IPS_GetVariableProfile("ESZS.Selector" . $this->InstanceID)['Associations'][$SceneNumber];
				$sceneVar = IPS_GetObjectIDByName($assoc['Name'], $this->InstanceID);
				$actualIdent = IPS_GetObject($sceneVar)['ObjectIdent'];
				$this->CallValues($actualIdent);
			}
			else
			{
				$this->CallValues("Scene".$SceneNumber);
			}
		}
	}

	public function SaveScene(int $SceneNumber){

		$this->SaveValues("Scene".$SceneNumber);

	}

	private function SaveValues($SceneIdent) {

		$targetIDs = IPS_GetObjectIDByIdent("Targets", IPS_GetParent($this->InstanceID));
		$data = Array();

		IPS_LogMessage("DaySet_Scenes.SaveValues", "Saving new Scene Data Values...");
		IPS_LogMessage("DaySet_Scenes.SaveValues", "Targets from ". IPS_GetName(IPS_GetParent($targetIDs)) ."/". IPS_GetName($targetIDs) ." ($targetIDs) are being used)");
		

		//We want to save all Lamp Values
		foreach(IPS_GetChildrenIDs($targetIDs) as $TargetID) {
			//only allow links
			if(IPS_LinkExists($TargetID)) {
				$linkVariableID = IPS_GetLink($TargetID)['TargetID'];
				if(IPS_VariableExists($linkVariableID)) {
					$data[$linkVariableID] = GetValue($linkVariableID);
				}
			}
		}
		$sceneDataID = IPS_GetObjectIDByIdent($SceneIdent."Data", $this->InstanceID);
		$sceneData = wddx_serialize_value($data);
		SetValue($sceneDataID, $sceneData);

		//write into backup file
		try
		{
			$content = json_decode(@file_get_contents($this->docsFile), true);
			$content[$sceneDataID] = $sceneData;
			@file_put_contents($this->docsFile, json_encode($content));
		} catch (Exception $e) { 
			IPS_LogMessage("DaySet_Scenes.SaveValues", "couldn't access backup file: " . $e->getMessage());
		}
	}

	private function CallValues($SceneIdent) {

		$actualIdent = str_replace("Sensor", "", $SceneIdent);
		$selectValue = str_replace("Scene", "", $actualIdent);
		$sceneDataID = IPS_GetObjectIDByIdent($actualIdent."Data", $this->InstanceID);
		$dataStr = GetValue($sceneDataID);
		$data = wddx_deserialize($dataStr);
		if($data != NULL && strlen($dataStr) > 3) {
			if(strpos($SceneIdent, "Sensor") !== false)
			{
				if(@IPS_GetObjectIDByIdent("Automatik", IPS_GetParent($this->InstanceID)) !== false)
				{
					$automatikID = IPS_GetObjectIDByIdent("Automatik", IPS_GetParent($this->InstanceID));
					$auto = GetValue($automatikID);
				}

				if(@IPS_GetObjectIDByIdent("Sperre", IPS_GetParent($this->InstanceID)) !== false)
				{
					$SperreID = IPS_GetObjectIDByIdent("Sperre", IPS_GetParent($this->InstanceID));
					$sperre = GetValue($SperreID);
				}
			}
			else
			{
				$auto = true;
				$sperre = false;
			}
			if($auto && !$sperre)
			{
				//Set Selector to current Scene
				$selectVar = IPS_GetObjectIDByIdent("Selector", IPS_GetParent($this->InstanceID));
				$eventsCat = IPS_GetObjectIDByIdent("EventsCat", $this->InstanceID);
				$selectEvent = IPS_GetObjectIDByIdent("SelectorOnChange", $eventsCat);
				IPS_SetEventActive($selectEvent, false);
				IPS_Sleep(100);
				$sceneVar = IPS_GetObjectIDByIdent($actualIdent, $this->InstanceID);
				$sceneNum = $this->GetAssociationByName("ESZS.Selector" . $this->InstanceID, IPS_GetObject($sceneVar)['ObjectName']);
				SetValue($selectVar, $sceneNum);
				IPS_Sleep(100);
				IPS_SetEventActive($selectEvent, true);

				IPS_LogMessage("DaySet_Scenes.CallValues", "Calling Values for Scene '".IPS_GetName($sceneVar)."'");
				//Set the actual values for the targets
				foreach($data as $id => $value) {
					if (IPS_VariableExists($id)){

						$o = IPS_GetObject($id);
						$v = IPS_GetVariable($id);

						if($v['VariableCustomAction'] > 0)
							$actionID = $v['VariableCustomAction'];
						else
							$actionID = $v['VariableAction'];

						//Skip this device if we do not have a proper id
						if($actionID < 10000)
						{
							SetValue($id, $value);
							continue;
						}

						if(IPS_InstanceExists($actionID)) {
							IPS_RequestAction($actionID, $o['ObjectIdent'], $value);
						} else if(IPS_ScriptExists($actionID)) {
							echo IPS_RunScriptWaitEx($actionID, Array("VARIABLE" => $id, "VALUE" => $value, "SENDER" => "WebFront"));
						}
					}
				}
			}
		} else
		{ 
			echo $SceneIdent;
			if(strpos($SceneIdent, "Sensor") !== true)
			{
				//Set Selector to current Scene
				$selectVar = IPS_GetObjectIDByIdent("Selector", IPS_GetParent($this->InstanceID));
				$eventsCat = IPS_GetObjectIDByIdent("EventsCat", $this->InstanceID);
				$selectEvent = IPS_GetObjectIDByIdent("SelectorOnChange", $eventsCat);
				IPS_SetEventActive($selectEvent, false);
				IPS_Sleep(100);
				$sceneVar = IPS_GetObjectIDByIdent($actualIdent, $this->InstanceID);
				$sceneNum = $this->GetAssociationByName("ESZS.Selector" . $this->InstanceID, IPS_GetObject($sceneVar)['ObjectName']);
				SetValue($selectVar, $sceneNum);
				IPS_Sleep(100);
				IPS_SetEventActive($selectEvent, true);

				echo "No SceneData for this Scene";
			}
		}
	}

	private function CreateCategoryByIdent($id, $ident, $name) {
		 $cid = @IPS_GetObjectIDByIdent($ident, $id);
		 if($cid === false) {
			 $cid = IPS_CreateCategory();
			 IPS_SetParent($cid, $id);
			 IPS_SetName($cid, $name);
			 IPS_SetIdent($cid, $ident);
		 }
		 return $cid;
	}

	private function CreateSetValueScript($parentID)
	{
		if(@IPS_GetObjectIDByIdent("SetValueScript", $parentID) === false)
		{
			$sid = IPS_CreateScript(0 /* PHP Script */);
		}
		else
		{
			$sid = IPS_GetObjectIDByIdent("SetValueScript", $parentID);
		}
		IPS_SetParent($sid, $parentID);
			IPS_SetName($sid, "SetValue");
			IPS_SetIdent($sid, "SetValueScript");
			IPS_SetHidden($sid, true);
			IPS_SetPosition($sid, 99999);
			IPS_SetScriptContent($sid, "<?
SetValue(\$_IPS['VARIABLE'], \$_IPS['VALUE']);
?>");
		return $sid;
	}

	protected function GetModuleIDByName($name = "Dummy Module")
	{
		$moduleList = IPS_GetModuleList();
		$GUID = ""; //init
		foreach($moduleList as $l)
		{
			if(IPS_GetModule($l)['ModuleName'] == $name)
			{
				$GUID = $l;
				break;
			}
		}
		return $GUID;
	}

	private function CreateAutomatikSwitch($targetFolder)
	{
		if($this->ReadPropertyInteger("Sensor") > 9999)
		{
			if(@IPS_GetObjectIDByIdent("AutomatikIns", IPS_GetParent($this->InstanceID)) === false)
			{
				$dummyGUID = $this->GetModuleIDByName();
				$insID = IPS_CreateInstance($dummyGUID);
			}
			else
			{
				$insID = IPS_GetObjectIDByIdent("AutomatikIns", IPS_GetParent($this->InstanceID));
			}
			IPS_SetName($insID, "Automatik");
			IPS_SetParent($insID, IPS_GetParent($this->InstanceID));
			IPS_SetIdent($insID, "AutomatikIns");
			$svs = IPS_GetObjectIDByIdent("SetValueScript", $this->InstanceID);

			$targets = IPS_GetChildrenIDs($targetFolder);
			foreach($targets as $targetLink)
			{
				$target = IPS_GetLink($targetLink)['TargetID'];
				$ident = IPS_GetObject($target)['ObjectIdent'];
				if(strpos($ident, "Automatik") === false)
				{
					//Create the Variables
					if(@IPS_GetObjectIDByIdent("$target"."Automatik", $insID) === false)
					{
						$vid = IPS_CreateVariable(0);
					}
					else
					{
						$vid = IPS_GetObjectIDByIdent("$target"."Automatik", $insID);
					}
					IPS_SetName($vid, IPS_GetName($target));
					IPS_SetParent($vid, $insID);
					IPS_SetIdent($vid, "$target"."Automatik");
					IPS_SetVariableCustomProfile($vid, "Switch");
					IPS_SetVariableCustomAction($vid, $svs);

					//Create the Target Links
					if(@IPS_GetObjectIDByIdent("$target"."AutomatikLink", $targetFolder) === false)
					{
						$lid = IPS_CreateLink();
					}
					else
					{
						$lid = IPS_GetObjectIDByIdent("$target"."AutomatikLink", $targetFolder);
					}
					IPS_SetName($lid, IPS_GetName($target) . ".Automatik");
					IPS_SetParent($lid, $targetFolder);
					IPS_SetIdent($lid, "$target"."AutomatikLink");
					IPS_SetLinkTargetID($lid, $vid);
				}
			}
			return $insID;
		}
	}
	
	private function GetAssociationByName($profile, $name)
	{
		$associations = IPS_GetVariableProfile($profile)['Associations'];
		foreach($associations as $i => $assoc)
		{
			if($assoc['Name'] == $name)
			{
				return $i;
			}
		}
		return false;
	}

	private function RemoveExcessiveProfiles($profileName)
	{
		$profiles = IPS_GetVariableProfileListByType(1);
		foreach($profiles as $key => $profile)
		{
			if(strpos($profile, "$profileName") !== false)
			{
				$id = (int) str_replace("$profileName", "", $profile);
				if(!IPS_InstanceExists($id))
				{
					IPS_DeleteVariableProfile($profile);
				}
			}
		}
	}
	
	private function maxByKey($arr, $k)
	{
		$d = array();
		foreach ($arr as $key => $row)
		{
			$d[$key] = $row[$k];
		}
		array_multisort($arr, SORT_DESC, $d);
		return $arr[0]['Position'];
	}
	
	private function sortByKey($arr, $k)
	{
		$d = array();
		foreach ($arr as $key => $row)
		{
			$d[$key] = $row[$k];
			
		}
		array_multisort($arr, SORT_ASC, $d);
		return $arr;
	}

    protected function CreateLink($content)
	{
		/**
		 * 
		 * 
		 * @param <array> $content 
		 * 
		 * @return <integer> $LinkID
		 
		$content = array("ObjectName" => "LinkName",
						 "ParentID" => ParentID,
						 "ObjectIdent" => "Identity",
						 "TargetID" => TargetID,
						 "ObjectInfo" => "Info", //optional
						 "ObjectIsHidden" => Boolean, //optional
						 "ObjectPosition" => position, //optional
						 "ObjectIcon" => "Icon" //optional
						)
		 */
		if(@IPS_GetObjectIDByIdent($content["ObjectIdent"], $content["parentID"]) === false)
		{
			$id = IPS_CreateLink();
			IPS_SetName($id, $content['ObjectName']);
			IPS_SetParent($id, $content['ParentID']);
			IPS_SetIdent($id, $content['ObjectIdent']);
			if(array_key_exists("ObjectInfo", $content))
				IPS_SetInfo($id, $content["ObjectInfo"]);
			if(array_key_exists("ObjectIsHidden", $content))
				IPS_SetHidden($id, $content["ObjectIsHidden"]);
			if(array_key_exists("ObjectPosition", $content))
				IPS_SetPosition($id, $content["ObjectPosition"]);
			if(array_key_exists("ObjectIcon", $content))
				IPS_SetIcon($id, $content["ObjectIcon"]);
			IPS_SetLinkTargetID($id, $content["TargetID"]);
		}
		else
		{
			$id = IPS_GetObjectIDByIdent($content["ObjectIdent"], $content["ParentID"]);
		}
		return $id;
	}


    protected function Del($id, $bool = false /*Delete associated files along with the objects ?*/)
	{
		if(IPS_HasChildren($id))
		{
			$childIDs = IPS_GetChildrenIDs($id);
			foreach($childIDs as $child)
			{
				$this->Del($child);
			}
			$this->Del($id);
		}
		else
		{
			$type = IPS_GetObject($id)['ObjectType'];
			switch($type)
			{
				case(0):
					IPS_DeleteCategory($id);
					break;
				case(1):
					IPS_DeleteInstance($id);
					break;
				case(2):
					IPS_DeleteVariable($id);
					break;
				case(3):
					IPS_DeleteScript($id);
					break;
				case(4):
					IPS_DeleteEvent($id);
					break;
				case(5):
					IPS_DeleteMedia($id, $bool /*dont delete media file along with it*/);
					break;
				case(6):
					IPS_DeleteLink($id);
			}
		}
	}

	protected function SetStatus($status)
	{
		file_put_contents($this->statusPath, $status);
		$this->statusPath = $status;
	}
}
?>
