<?
class ErweiterteSzenenSteuerung extends IPSModule {
	/////////////
	// Modular  //
	/////////////
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
	}
	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}
	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
		
		$this->RemoveExcessiveProfiles("ESZS.Selector");
		$this->RemoveExcessiveProfiles("ESZS.Sets");
		$data = json_decode($this->ReadPropertyString("Names"),true);


		// UPDATE ÜBERWACHUNG //






		$this->setAllOnChangeEventsForHits();









		// ------------------------------------------------------------------------------------------------

		
		if($data != "")
		{
			$archivGUID = $this->GetModuleIDByName("Archive Control");
			$archivIDs = (array) IPS_GetInstanceListByModuleID($archivGUID);
			
			IPS_SetPosition($this->InstanceID, 50);
			
			//Selector profile
			if(IPS_VariableProfileExists("ESZS.Selector" . $this->InstanceID))
			{
				IPS_DeleteVariableProfile("ESZS.Selector" . $this->InstanceID);
				IPS_CreateVariableProfile("ESZS.Selector" . $this->InstanceID, 1);
			}
			else
			{
				IPS_CreateVariableProfile("ESZS.Selector" . $this->InstanceID, 1);
			}
			
			//Targets Dummy
			if(@IPS_GetObjectIDByIdent("Targets", IPS_GetParent($this->InstanceID)) === false)
			{
				$dummyGUID = $this->GetModuleIDByName();
				$insID = IPS_CreateInstance($dummyGUID);
				IPS_SetParent($insID, IPS_GetParent($this->InstanceID));
				IPS_SetIdent($insID, "Targets");
			}
			else
			{
				$insID = IPS_GetObjectIDByIdent("Targets", IPS_GetParent($this->InstanceID));
			}
			IPS_SetPosition($insID, 40);
			IPS_SetName($insID, "Targets");

			if(@IPS_GetObjectIDByIdent("Targets", $this->InstanceID) !== false)
			{
				$oldTargetsCategory = IPS_GetObjectIDByIdent("Targets", $this->InstanceID);
				if(sizeof(IPS_GetChildrenIDs($insID)) < 1)
				{
					$newTargetsDummy = $insID;
					foreach(IPS_GetChildrenIDs($oldTargetsCategory) as $child)
					{
						if(IPS_LinkExists($child))
						{
							$o = IPS_GetObject($child);
							$o['ParentID'] = $newTargetsDummy;
							$l = IPS_GetLink($child);
							$content = array_merge($o, $l);
							IPS_DeleteLink($child);
							$this->CreateLink($content);
						}
					}
				}
				foreach(IPS_GetChildrenIDs($oldTargetsCategory) as $child)
				{
					IPS_DeleteLink($child);
				}
				IPS_DeleteCategory($oldTargetsCategory);
			}

			//Events Category
			if(@IPS_GetObjectIDByIdent("EventsCat", $this->InstanceID) === false)
			{
				$eventsCat = IPS_CreateCategory();
				IPS_SetName($eventsCat, "Events");
				IPS_SetIdent($eventsCat, "EventsCat");
				IPS_SetParent($eventsCat, $this->InstanceID);
				IPS_SetHidden($eventsCat, true);
			}
			else
			{
				$eventsCat = IPS_GetObjectIDByIdent("EventsCat", $this->InstanceID);
			}
				
			//sort data by position ///// Temporarily disabled due to not working properly //////
			// $d = array();
			// foreach ($data as $key => $row)
			// {
				// $d[$key] = $row['Position'];
			// }			
			// array_multisort($data, SORT_ASC, $d);
			
			for($i = 1; $i <= sizeof($data); $i++) {
				if(array_key_exists('name', $data[$i - 1]) !== true)
				{
					$data[$i-1]['name'] = "Scene$i";
				}
				if(@IPS_GetObjectIDByIdent("Scene".$i, $this->InstanceID) === false){
					//Scene
					$vid = IPS_CreateVariable(1 /* Scene */);
					SetValue($vid, 2);
					$scenesDeleted = false;
				} else
				{
					$vid = IPS_GetObjectIDByIdent("Scene".$i, $this->InstanceID);
					$scenesDeleted = true;
				}
				IPS_SetParent($vid, $this->InstanceID);
				if(IPS_GetName($vid) != $data[$i - 1]['name'] && strpos(IPS_GetName($vid),"Unnamed") === false)
				{	//Namechange detected
					if($this->IPS_GetObjectIDByName($data[$i - 1]['name'], $this->InstanceID) !== false)
					{ //not a new scene
						if($scenesDeleted) {
							$oldSceneID = $this->IPS_GetObjectIDByName($data[$i - 1]['name'], $this->InstanceID);
							$oldSceneIdent = IPS_GetObject($oldSceneID)['ObjectIdent'];
							$oldSceneDataID = IPS_GetObjectIDByIdent($oldSceneIdent . "Data", $this->InstanceID);
							$newSceneDataID = IPS_GetObjectIDByIdent("Scene".$i."Data", $this->InstanceID);
							$oldSceneData = GetValue($oldSceneDataID);
							SetValue($newSceneDataID, $oldSceneData);
						}
					}
				}
				IPS_SetName($vid, $data[$i - 1]['name']);
				IPS_SetIdent($vid, "Scene".$i);
				IPS_SetPosition($vid, $i);
				IPS_SetVariableCustomProfile($vid, "SZS.SceneControl");
				$this->EnableAction("Scene".$i);
				
				if(@IPS_GetObjectIDByIdent("Scene".$i."Data", $this->InstanceID) === false)
				{
					//SceneData
					$vid = IPS_CreateVariable(3 /* SceneData */);
				}
				else
				{
					$vid = IPS_GetObjectIDByIdent("Scene".$i."Data", $this->InstanceID);
				}
				IPS_SetParent($vid, $this->InstanceID);
				IPS_SetName($vid, $data[$i - 1]['name']."Data");
				IPS_SetIdent($vid, "Scene".$i."Data");
				IPS_SetPosition($vid, $i + sizeof($data));
				IPS_SetHidden($vid, true);
				
				//Set Selector profile
				IPS_SetVariableProfileAssociation("ESZS.Selector" . $this->InstanceID, ($i-1), $data[$i - 1]['name'],"",-1);
			}
			//Selector Variable
			if(@IPS_GetObjectIDByIdent("Selector", IPS_GetParent($this->InstanceID)) === false)
			{
				$vid = IPS_CreateVariable(1);
			}
			else
			{
				$vid = IPS_GetObjectIDByIdent("Selector", IPS_GetParent($this->InstanceID));
			}
			$svs = IPS_GetObjectIDByIdent("SetValueScript", $this->InstanceID);
			IPS_SetParent($vid, IPS_GetParent($this->InstanceID));
			IPS_SetIcon($vid, "Rocket");
			IPS_SetName($vid, IPS_GetName($this->InstanceID));
			IPS_SetIdent($vid, "Selector");
			IPS_SetPosition($vid, 10);
			IPS_SetVariableCustomProfile($vid, "ESZS.Selector" . $this->InstanceID);
			IPS_SetVariableCustomAction($vid, $svs);
			AC_SetLoggingStatus($archivIDs[0], $vid, true);
			
			//Create Selector event
			if(@IPS_GetObjectIDByIdent("SelectorOnChange", $eventsCat) === false)
			{
				$eid = IPS_CreateEvent(0);
			}
			else
			{
				$eid = IPS_GetObjectIDByIdent("SelectorOnChange", $eventsCat);
			}
			IPS_SetParent($eid, $eventsCat);
			IPS_SetName($eid, "Selector.OnChange");
			IPS_SetIdent($eid, "SelectorOnChange");
			IPS_SetEventTrigger($eid, 0, $vid);
			IPS_SetEventActive($eid, true);
			IPS_SetEventScript($eid, "ESZS_CallScene(". $this->InstanceID .", GetValue($vid) + 1);");
			
			//Create Sensor event
			$sensorID = $this->ReadPropertyInteger("Sensor");
			if($sensorID > 9999)
				$sensorExists = true;
			else
				$sensorExists = false;
			if($sensorExists)
			{
				if(@IPS_GetObjectIDByIdent("SensorEvent", $eventsCat) === false)
				{
					$eid = IPS_CreateEvent(0);
				}
				else
				{
					$eid = IPS_GetObjectIDByIdent("SensorEvent", $eventsCat);
				}
				IPS_SetEventTrigger($eid, 1, $sensorID);
				IPS_SetEventScript($eid, "ESZS_CallScene(". $this->InstanceID .", ($sensorID*100));");
				IPS_SetEventActive($eid, true);
				IPS_SetParent($eid, $eventsCat);
				IPS_SetName($eid, "Sensor.OnChange");
				IPS_SetIdent($eid, "SensorEvent");
			}
			
			//Create Automatik for this instance
			if(@IPS_GetObjectIDByIdent("Automatik", IPS_GetParent($this->InstanceID)) === false)
				$vid = IPS_CreateVariable(0);
			else
				$vid = IPS_GetObjectIDByIdent("Automatik", IPS_GetParent($this->InstanceID));
			IPS_SetName($vid, "Automatik");
			IPS_SetParent($vid, IPS_GetParent($this->InstanceID));
			IPS_SetPosition($vid, 20);
			IPS_SetIdent($vid, "Automatik");
			IPS_SetVariableCustomAction($vid, $svs);
			IPS_SetVariableCustomProfile($vid, "Switch");
			AC_SetLoggingStatus($archivIDs[0], $vid, true);
			
			//Create Event for Automatik
			if(@IPS_GetObjectIDByIdent("AutomatikEvent", $eventsCat) === false)
				$eid = IPS_CreateEvent(0);
			else
				$eid = IPS_GetObjectIDByIdent("AutomatikEvent", $eventsCat);
			IPS_SetEventTrigger($eid, 4, $vid);
			IPS_SetEventTriggerValue($eid, true);
			IPS_SetEventScript($eid, "ESZS_CallScene(". $this->InstanceID .", ($sensorID*100));");
			IPS_SetEventActive($eid, true);
			IPS_SetParent($eid, $eventsCat);
			IPS_SetName($eid, "Automatik.OnTrue");
			IPS_SetIdent($eid, "AutomatikEvent");
			
			//Create Sperre for this instance
			if(@IPS_GetObjectIDByIdent("Sperre", IPS_GetParent($this->InstanceID)) === false)
				$vid = IPS_CreateVariable(0);
			else
				$vid = IPS_GetObjectIDByIdent("Sperre", IPS_GetParent($this->InstanceID));
			IPS_SetName($vid, "Sperre");
			IPS_SetParent($vid, IPS_GetParent($this->InstanceID));
			IPS_SetPosition($vid, 30);
			IPS_SetIdent($vid, "Sperre");
			IPS_SetVariableCustomAction($vid, $svs);
			IPS_SetVariableCustomProfile($vid, "Switch");
			AC_SetLoggingStatus($archivIDs[0], $vid, true);

			//Create Event for Sperre
			if(@IPS_GetObjectIDByIdent("SperreEvent", $eventsCat) === false)
				$eid = IPS_CreateEvent(0);
			else
				$eid = IPS_GetObjectIDByIdent("SperreEvent", $eventsCat);
			IPS_SetEventTrigger($eid, 4, $vid);
			IPS_SetEventTriggerValue($eid, false);
			IPS_SetEventScript($eid, "ESZS_CallScene(". $this->InstanceID .", ($sensorID *100));");
			IPS_SetEventActive($eid, true);
			IPS_SetParent($eid, $eventsCat);
			IPS_SetName($eid, "Sperre.OnFalse");
			IPS_SetIdent($eid, "SperreEvent");
			
			//Create Sensor Selection
			//by its profile
			// $sensorID = this->ReadPropertyInteger("Sensor");
			// if($sensorID > 9999)
			// {
				// $profileName = IPS_GetVariable($sensorID)['VariableProfile'];
				// if($profileName == "")
					// $profileName = IPS_GetVariable($sensorID)['VariableCustomProfile'];
				// $profile = IPS_GetVariableProfile($profileName);
				
			// }
			
			//Create all the states (Morgen, Tag...)
			$sensorID = $this->ReadPropertyInteger("Sensor");
			if($sensorID > 9999)
			{
				if(@IPS_GetObjectIDByIdent("Set", IPS_GetParent($this->InstanceID)) === false)
				{
					$DummyGUID = $this->GetModuleIDByName();
					$insID = IPS_CreateInstance($DummyGUID);
				}
				else
				{
					$insID = IPS_GetObjectIDByIdent("Set", IPS_GetParent($this->InstanceID));
				}
				IPS_SetName($insID, "Sensor");
				IPS_SetParent($insID, IPS_GetParent($this->InstanceID));
				IPS_SetPosition($insID, 60);
				IPS_SetIdent($insID, "Set");
				
				$sets = array("Früh","Morgen","Tag","Dämmerung","Abend","Nacht");
				//Create the profile
				if(IPS_VariableProfileExists("ESZS.Sets" . $this->InstanceID))
				{
					IPS_DeleteVariableProfile("ESZS.Sets" . $this->InstanceID);
					IPS_CreateVariableProfile("ESZS.Sets" . $this->InstanceID, 1);
				}
				else
				{
					IPS_CreateVariableProfile("ESZS.Sets" . $this->InstanceID, 1);
				}
				foreach($sets as $i => $state)
				{
					IPS_SetVariableProfileAssociation("ESZS.Sets" . $this->InstanceID, $i, $state, "", -1);
				}
				//Create the variables
				foreach($sets as $i => $state)
				{
					if(@IPS_GetObjectIDByIdent("set$i", $insID) === false)
						$vid = IPS_CreateVariable(1);
					else
						$vid = IPS_GetObjectIDByIdent("set$i", $insID);
					IPS_SetName($vid, $state);
					IPS_SetParent($vid, $insID);
					IPS_SetPosition($vid, $i);
					IPS_SetIdent($vid, "set$i");
					IPS_SetVariableCustomAction($vid, $svs);
					IPS_SetVariableCustomProfile($vid, "ESZS.Selector" . $this->InstanceID);
					
					//Create Events for the States
					if(@IPS_GetObjectIDByIdent("SetEvent$i", $eventsCat) === false)
						$eid = IPS_CreateEvent(0);
					else
						$eid = IPS_GetObjectIDByIdent("SetEvent$i", $eventsCat);
					IPS_SetEventTrigger($eid, 1, $vid);
					IPS_SetEventScript($eid, "ESZS_CallScene(" . $this->InstanceID . ", ($sensorID*100));");
					IPS_SetName($eid, "$state".".OnChange");
					IPS_SetParent($eid, $eventsCat);
					IPS_SetIdent($eid, "SetEvent$i");
					IPS_SetEventActive($eid, true);
				}
			}
			
			//Delete excessive Scences 
			$ChildrenIDs = IPS_GetChildrenIDs($this->InstanceID);
			$ChildrenIDsCount = 0;
			foreach($ChildrenIDs as $child)
			{
				$ident = IPS_GetObject($child)['ObjectIdent'];
				if(strpos($ident, "Scene") !== false)
					$ChildrenIDsCount++;
			}
			$ChildrenIDsCount = $ChildrenIDsCount/2;
			if($ChildrenIDsCount > sizeof($data)) {
				for($j = sizeof($data)+1; $j <= $ChildrenIDsCount; $j++) {
					IPS_DeleteVariable(IPS_GetObjectIDByIdent("Scene".$j, $this->InstanceID));
					IPS_DeleteVariable(IPS_GetObjectIDByIdent("Scene".$j."Data", $this->InstanceID));
				}
			}
			
			//Delete Excessive Automation
			$sensor = $this->ReadPropertyInteger("Sensor");
			if($sensor < 9999)
			{
				if(@IPS_GetObjectIDByIdent("Sperre", IPS_GetParent($this->InstanceID)) !== false)
				{
					$sperreVar = IPS_GetObjectIDByIdent("Sperre", IPS_GetParent($this->InstanceID));
					IPS_DeleteVariable($sperreVar);
				}
				
				if(@IPS_GetObjectIDByIdent("Set", IPS_GetParent($this->InstanceID)) !== false)
				{
					$setIns = IPS_GetObjectIDByIdent("Set", IPS_GetParent($this->InstanceID));
					$this->Del($setIns);
				}
				
				if(@IPS_GetObjectIDByIdent("Automatik", IPS_GetParent($this->InstanceID)) !== false)
				{
					$autoVar = IPS_GetObjectIDByIdent("Automatik", IPS_GetParent($this->InstanceID));
					IPS_DeleteVariable($autoVar);
				}
			}
		}
	}



	// Funktionen

	// Prüft ob Variable bereits existiert und erstellt diese wenn nicht
	public function checkVar ($var, $type = 1, $profile = false , $position = "", $index = 0, $defaultValue = null) {
		if ($this->searchObjectByName($var) == 0) {
			$nVar = $this->easyCreateVariable($type, $var ,$position, $index, $defaultValue);
			if ($type == 0 && $profile == true) {
				$this->addSwitch($nVar);
			}
			if ($type == 1 && $profile == true) {
				$this->addTime($nVar);
			}
			if ($position != "") {
				IPS_SetParent($nVar, $position);
			}
			if ($index != 0) {
				IPS_SetPosition($nVar, $index);
			}
			return $nVar;
		} else {
			return $this->searchObjectByName($var);
		}
	}


	// Sucht nach Objekt im angegebenem Ordner (wenn kein Ordner angegeben wird, wird das Modul selbst verwendet)
	public function searchObjectByName ($name, $searchIn = null, $objectType = null) {

		if ($searchIn == null) {
			$searchIn = $this->InstanceID;
		}

		$childs = IPS_GetChildrenIDs($searchIn);
		$returnId = 0;
		foreach ($childs as $child) {
			
			$childObject = IPS_GetObject($child);
			
			if ($childObject['ObjectName'] == $name) {
			
				$returnId = $childObject['ObjectID'];
		
			}
			if ($objectType == null) {
				
				if ($childObject['ObjectName'] == $name) {
				
					$returnId = $childObject['ObjectID'];

				}

		} else {

			if ($childObject['ObjectName'] == $name && $childObject['ObjectType'] == $objectType) {
				
				$returnId = $childObject['ObjectID'];

			}

		}
		}

	return $returnId;
	}

	public function getAllElementsContainsName ($name, $si = null) {

		if ($si == null) {

			$si = $this->InstanceID;

		}

		$var = IPS_GetObject($si);

		$hits = null;

		foreach ($var['ChildrenIDs'] as $child) {

			$chld = IPS_GetObject($child);

			if (strpos($chld['ObjectName'], $name) !== false) {

				$hits[] = $child;

			}

		}

		return $hits;


	}


	protected function setAllOnChangeEventsForHits () {

		$allScenes = $this->getAllElementsContainsName("Data");

		if (count($allScenes) > 0) {

			foreach ($allScenes as $scene) {

				$sceneObj = IPS_GetObject($scene);

				$scene = GetValue($scene);

				$ary = json_decode($scene);

				while ($element = current($ary)) {

					$currentElement = key($ary);

					$cElementObj = IPS_GetObject($currentElement);

					if (!$this->doesExist($this->searchObjectByName($cElementObj['ObjectName'] . " Event"))) {

						//$this->easyCreateFunctionEvent($cElementObj['ObjectName'] . " Event", );

						$this->easyCreateFunctionEvent($currentElement, "<?php ESZS_onTargetChanged(" . $this->InstanceID . ");" . " ?>", $this->InstanceID, $cElementObj['ObjectName'] . " Event");

					}

					next($ary);

				}

			}

		}

	}

	public function onTargetChanged () {

		$allScenes = $this->getAllElementsContainsName("Data");

		$prnt = IPS_GetParent($this->InstanceID);

		$targetsFolder = IPS_GetObjectIDByIdent("Targets", $prnt);
		
		$targetsFolder = IPS_GetObject($targetsFolder);

		if (count($allScenes) > 0) {

			$currentScene = null;

			$isValidScene = false;

			foreach ($targetsFolder['ChildrenIDs'] as $tgChild) {

				$tg = IPS_GetObject($tgChild);

				if ($tg['ObjectType'] == 6) {

					$lnk = IPS_GetLink($tg['ObjectID']);

					$elem = IPS_GetObject($lnk['TargetID']);

					$elemID = $elem['ObjectID'];

					$currentScene[$elemID] = GetValue($elem['ObjectID']);

				}

			}

			print_r($allScenes);

			foreach ($allScenes as $scene) {

				$scI = GetValue($scene);
				$scI = json_decode($scene);

				sort($scI);
				sort($currentScene);

				if ($scI == $currentScene) {

					$isValidScene = true;

				}

			}

			if ($isValidScene) {

				echo "IsValidScene!!!";

			} else {

				echo "Is not a valid scene!!!!!!!";

			}

		}

	}



	public function easyCreateFunctionEvent ($target, $func, $parent = "", $name = "Unnamed Event") {
		if ($parent == "") {
			$parent = $this->InstanceID;
		}
		$eid = IPS_CreateEvent(0);
		IPS_setEventTrigger($eid, 0, $target);
		IPS_SetParent($eid, $parent);
		IPS_SetEventScript($eid, $func);
		IPS_SetName($eid, $name);
		IPS_SetEventActive($eid, true); 
		return $eid;
	} 


	public function doesExist ($id) {
		if (IPS_ObjectExists($id) && $id != 0) {
			
			return true;
		} else {
			return false;
		
		}
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
		if($SceneNumber > 9999) //sender = Sensor
		{
			$SceneNumber = floor($SceneNumber/100);
			$sensorWert = GetValue($SceneNumber) - 1;
			$setsIns = IPS_GetObjectIDByIdent("Set", IPS_GetParent($this->InstanceID));
			$set = IPS_GetObjectIDByIdent("set$sensorWert", $setsIns);
			$ActualSceneNumber = GetValue($set);
			$this->CallValues("Scene".$ActualSceneNumber."Sensor");
		}
		else
		{
			$this->CallValues("Scene".$SceneNumber);
		}
	}
	public function SaveScene(int $SceneNumber){
		
		$this->SaveValues("Scene".$SceneNumber);
	}
	private function SaveValues($SceneIdent) {
		
		$targetIDs = IPS_GetObjectIDByIdent("Targets", IPS_GetParent($this->InstanceID));
		$data = Array();
		
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
		SetValue(IPS_GetObjectIDByIdent($SceneIdent."Data", $this->InstanceID), json_encode($data));
	}
	private function CallValues($SceneIdent) {
		
		$actualIdent = str_replace("Sensor", "", $SceneIdent);
		$actualIdent = str_replace("Scene", "", $actualIdent);
		if(strpos($SceneIdent, "Sensor") !== false) //sender = sensor
			$actualIdent++;
		$actualIdent = "Scene". $actualIdent;
		$data = json_decode(GetValue(IPS_GetObjectIDByIdent($actualIdent."Data", $this->InstanceID)));
		if($data != NULL) {
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
				$sceneNum = str_replace("Scene","",$actualIdent);
				SetValue($selectVar, $sceneNum - 1);
				IPS_Sleep(100);
				IPS_SetEventActive($selectEvent, true);
				
				//Set the actual values for the targets
				foreach($data as $id => $value) {
					if (IPS_VariableExists($id)){
						//dont set the value if the target already has the desired value
						if(GetValue($id) !== $value)
						{
						
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
			}
		} else {
			echo "No SceneData for this Scene";
		}
	}
	private function CreateCategoryByIdent($id, $ident, $name) {
		 $cid = @IPS_GetObjectIDByIdent($ident, $id);
		 if($cid === false) {
			 $cid = IPS_CreateCategory();
			 IPS_SetParent($cid, $parent);
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
			IPS_SetPosition($sid, 9999);			
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
					IPS_SetVariableCustomProfile($vid, "~Switch");
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
			if(@IPS_GetObjectIDByIdent($content["ObjectIdent"], $content["ParentID"]) !== false)
				$id = IPS_GetObjectIDByIdent($content["ObjectIdent"], $content["ParentID"]);
		}

		return $id;
	}
	
	private function IPS_GetObjectIDByName($name, $parent)
	{
		$children = IPS_GetChildrenIDs($parent);
		foreach($children as $child)
		{
			$childName = IPS_GetName($child);
			if($childName === $name)
			{
				return $child;
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
}
?>
