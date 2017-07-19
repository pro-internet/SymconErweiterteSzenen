<?
class ErweiterteSzenenSteuerung extends IPSModule {

	public function Create() {
		//Never delete this line!
		parent::Create();

		//Properties
		if(@$this->RegisterPropertyString("Names") !== false)
		{
			$this->RegisterPropertyString("Names", "");
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
		
		$this->CreateCategoryByIdent($this->InstanceID, "Targets", "Targets");
		$data = json_decode($this->ReadPropertyString("Names"),true);
		
		if($data != "")
		{
			//Selector profile
			if(IPS_VariableProfileExists("ESZS.Selector"))
			{
				IPS_DeleteVariableProfile("ESZS.Selector");
				IPS_CreateVariableProfile("ESZS.Selector", 1);
			}
			else
			{
				IPS_CreateVariableProfile("ESZS.Selector");
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
				IPS_SetVariableProfileAssociation("ESZS.Selector", ($i-1), $data[$i - 1]['name'],"",-1);
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
			IPS_SetName($vid, "Select");
			IPS_SetIdent($vid, "Selector");
			IPS_SetPosition($vid, 1);
			IPS_SetVariableCustomProfile($vid, "ESZS.Selector");
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
			
			//Delete excessive Scences 
			$ChildrenIDsCount = sizeof(IPS_GetChildrenIDs($this->InstanceID))/2 - 2;
			if($ChildrenIDsCount > sizeof($data)) {
				for($j = sizeof($data)+1; $j <= $ChildrenIDsCount; $j++) {
					IPS_DeleteVariable(IPS_GetObjectIDByIdent("Scene".$j, $this->InstanceID));
					IPS_DeleteVariable(IPS_GetObjectIDByIdent("Scene".$j."Data", $this->InstanceID));
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
		
		$this->CallValues("Scene".$SceneNumber);

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
		
		$data = wddx_deserialize(GetValue(IPS_GetObjectIDByIdent($SceneIdent."Data", $this->InstanceID)));
		
		if($data != NULL) {
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

}
?>