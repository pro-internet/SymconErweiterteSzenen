<?
class ErweiterteSzenenSteuerung extends IPSModule {

	/////////////
	// Modular //
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
		
		$this->RemoveExcessiveProfiles();
		$this->CreateCategoryByIdent($this->InstanceID, "Targets", "Targets");
		$data = json_decode($this->ReadPropertyString("Names"),true);
		
		if($data != "")
		{
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
				
			//sort data by position
			$d = array();
			foreach ($data as $key => $row)
			{
				$d[$key] = $row['Position'];
			}			
			array_multisort($data, SORT_ASC, $d);
			
			for($i = 1; $i <= sizeof($data); $i++) {
				if(@IPS_GetObjectIDByIdent("Scene".$i, $this->InstanceID) === false){
					//Scene
					$vid = IPS_CreateVariable(1 /* Scene */);
					SetValue($vid, 2);
				} else
				{
					$vid = IPS_GetObjectIDByIdent("Scene".$i, $this->InstanceID);
				}
				IPS_SetParent($vid, $this->InstanceID);
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
					$vid = @IPS_GetObjectIDByIdent("Scene".$i."Data", $this->InstanceID);
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
			IPS_SetName($vid, IPS_GetName($this->InstanceID));
			IPS_SetIdent($vid, "Selector");
			IPS_SetPosition($vid, 1);
			IPS_SetVariableCustomProfile($vid, "ESZS.Selector" . $this->InstanceID);
			IPS_SetVariableCustomAction($vid, $svs);
			
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
				IPS_SetEventScript($eid, "ESZS_CallScene(". $this->InstanceID .", $sensorID);");
				IPS_SetEventActive($eid, true);
				IPS_SetParent($eid, $eventsCat);
				IPS_SetName($eid, "Sensor.OnChange");
				IPS_SetIdent($eid, "SensorEvent");
			}
			
			//Create Automatik for targets
			$cid = IPS_GetObjectIDByIdent("Targets", $this->InstanceID);
			$this->CreateAutomatikSwitch($cid);
			
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
			if($sensor > 9999)
			{
				
			}
			else
			{
				if(@IPS_GetObjectIDByIdent("AutomatikIns" ,IPS_GetParent($this->InstanceID)) !== false)
				{
					$id = IPS_GetObjectIDByIdent("AutomatikIns" ,IPS_GetParent($this->InstanceID));
					foreach(IPS_GetChildrenIDs($id) as $child)
					{
						IPS_DeleteVariable($child);
					}
					IPS_DeleteInstance($id);
				}
				
				//Delete Targets for Automation
				$targetFolder = IPS_GetObjectIDByIdent("Targets", $this->InstanceID);
				foreach(IPS_GetChildrenIDs($targetFolder) as $child)
				{
					$ident = IPS_GetObject($child)['ObjectIdent'];
					if(strpos($ident, "AutomatikLink") !== false)
					{
						IPS_DeleteLink($child);
					}
					IPS_DeleteInstance($id);
				}
			}
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
			$data = json_decode($this->ReadPropertyString("Names"),true);
			$sensorWert = GetValue($SceneNumber);
			foreach($data as $i => $scene)
			{
				if($scene["SensorWert"] == $sensorWert)
				{
					$t = $i + 1;
					$this->CallValues("Scene". $t . "Sensor");
				}
			}
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
		
		$targetIDs = IPS_GetObjectIDByIdent("Targets", $this->InstanceID);
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
		SetValue(IPS_GetObjectIDByIdent($SceneIdent."Data", $this->InstanceID), wddx_serialize_value($data));
	}

	private function CallValues($SceneIdent) {
		
		$actualIdent = str_replace("Sensor", "", $SceneIdent);
		$data = wddx_deserialize(GetValue(IPS_GetObjectIDByIdent($actualIdent."Data", $this->InstanceID)));
		if(@IPS_GetObjectIDByIdent("AutomatikIns", IPS_GetParent($this->InstanceID)) === false)
			$autoIns = $this->CreateAutomatikSwitch(IPS_GetObjectIDByIdent("Targets", $this->InstanceID));
		else
			$autoIns = IPS_GetObjectIDByIdent("AutomatikIns", IPS_GetParent($this->InstanceID));
		
		if($data != NULL) {
			foreach($data as $id => $value) {
				if(strpos($SceneIdent, "Sensor") !== false)
				{
					if(@IPS_GetObjectIDByIdent($id . "Automatik", $autoIns) !== false)
					{
						$automatikID = IPS_GetObjectIDByIdent($id . "Automatik", $autoIns);
						$auto = $data[$automatikID];
					}
				}
				else
				{
					$auto = true;
				}
				if (IPS_VariableExists($id) && $auto){
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
						echo IPS_RunScriptWaitEx($actionID, Array("VARIABLE" => $id, "VALUE" => $value));
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
	
	private function IPS_GetObjectIDByName($name, $parent)
	{
		$children = IPS_GetChildrenIDs($parent);
		foreach($children as $child)
		{
			$childName = IPS_GetName($child);
			if($childName == $name)
				return $child;
		}
	}
	
	private function RemoveExcessiveProfiles()
	{
		$profiles = IPS_GetVariableProfileListByType(1);
		foreach($profiles as $key => $profile)
		{
			if(strpos($profile, "ESZS.Selector") !== false)
			{
				$id = (int) str_replace("ESZS.Selector", "", $profile);
				if(!IPS_InstanceExists($id))
				{
					IPS_DeleteVariableProfile($profile);
				}
			}
		}
	}
}
?>