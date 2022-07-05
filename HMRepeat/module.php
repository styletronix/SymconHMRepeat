<?php

declare(strict_types=1);
	class HMRepeat extends IPSModule
	{
		public function Create()
			{
				parent::Create();

				$this->RegisterPropertyString("repeatingVariables", "");

				$this->RegisterAttributeString("repeatingStatus", "[]");

				$this->RegisterScript("ActionScript","Externer ActionScript", "<?\n\nSXHMREP_RequestExternalAction(IPS_GetParent(\$_IPS['SELF']), \$_IPS['VARIABLE'], \$_IPS['VALUE']);");
		
				$this->RegisterTimer("UpdateIterval",5,'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "UpdateIterval");');	
			}

		public function Destroy()
			{
				parent::Destroy();
			}

		public function ApplyChanges()
			{
				parent::ApplyChanges();

				$this->UpdateVariables();
			}

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) 
			{
				$this->SendDebug("MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true), 0);
			}

		private function GetListItems($List){
			$arrString = $this->ReadPropertyString($List);
			if ($arrString){
				return json_decode($arrString, true);
			}	
			return null;
		}

		private function GetRepeatingStatus(){
			$arrString = $this->ReadAttributeString("repeatingStatus");
			if ($arrString){
				return json_decode($arrString, true);
			}	
			return array();
		}
		private function SetRepeatingStatus($data){
			$this->SendDebug("SetRepeatingStatus", $data);

			$jsonString = json_encode($data);
			$this->WriteAttributeString("repeatingStatus", $jsonString);
		}


		private function GetRepeatingVariable($objID){
			$arr = $this->GetListItems("repeatingVariables");
			if ($arr){
				foreach($arr as $key1) {
					if ($objID == $key1["InstanceID"]){
						return $key1;
					}				
				}
			}

			return null;
		}
		private function GetRepeatingVariableTreeUp($objID){
			$arr = $this->GetRepeatingVariable($objID);
			
			if ($arr){
				return $arr;
			}else{
				$parent = IPS_GetParent($objID);
				if ($parent > 0){
					return $this->GetRepeatingVariableTreeUp($parent);
				}
			}
							
			return null;
		}

		private function UpdateVariables()
			{
				$arr = $this->GetListItems("repeatingVariables");
				if ($arr){
					foreach($arr as $key1) {
						$objID = $key1["InstanceID"];
						if ($objID > 0){
							$this->UpdateVariablesRecursive([$objID]);
						}
					}
				}	
			}

		private function UpdateVariablesRecursive($ChildrenIDs)
			{
				foreach($ChildrenIDs as $ChildrenID) {
					$obj = IPS_GetObject($ChildrenID);
					switch ($obj["ObjectType"]) {
						case 0: // Kategorie
						case 1: // Instanz
							$this->UpdateVariablesRecursive($obj["ChildrenIDs"]);
							break;
						case 2: // Variable
							$this->UpdateVariable($ChildrenID);
							break;
						default:
								
					}
				}
			}

		private function UpdateVariable($ID){
			$Variable = IPS_GetVariable($ID);
			if ($Variable == null){ return; }
			if ($Variable["VariableAction"] == 0){
				$this->SendDebug("UpdateVariable", "Für Variable " . $ID . " existiert keine Standardaktion. Sie wird daher nicht verwendet.", 0);
				return; 
			}
			if ($Variable["VariableCustomAction"] == 1 or $Variable["VariableCustomAction"] == 2300) {
				$this->SendDebug("UpdateVariable", "Für Variable " . $ID . " wurde die Standardaktion deaktiviert. Sie wird daher nicht verwendet.", 0);
				return; 
			}

			$ActionScriptID = $this->GetIDForIdent("ActionScript");

			if ($Variable["VariableCustomAction"] > 0 and $Variable["VariableCustomAction"] !== $ActionScriptID) { 
				$this->SendDebug("UpdateVariable", "Variable " . $ID . " hat eine benutzerdefinierte Aktion und kann daher nicht verwendet werden.", 0);
				return; 
			}

			$this->RegisterReference($ID);

			if ($Variable["VariableCustomAction"] == $ActionScriptID){ return; }
			
			IPS_SetVariableCustomAction($ID, $ActionScriptID);
			$this->SendDebug("UpdateVariable", "Variable " . $ID . " wurde hinzugefügt.", 0);

			// $this->UnregisterReference(12345);
			// $ReferenceList = $this->GetReferenceList();
		}

		private function TimerCallback($timer){
			switch($timer) {
				case "UpdateIterval":
					$this->UpdateIterval();
					break;

				default:
					throw new Exception("Invalid TimerCallback");
			}
		}

		private function UpdateIterval(){
			$status = $this->GetRepeatingStatus();
			foreach($status as $item) {

			
			}
		}

		private function GetRepeatingStatusItem($id){
			$key = "ID".$id;
			$status = $this->GetRepeatingStatus();

			if (array_key_exists($key,$status)){
				$item = $status[$key];
			}else{
				$item = array(
					"ID" => $id,
					"RepeatCount" => 0,
					"LastTry" => 0,
					"Value" => null
				);
			}

			return $item;
		}

		private function AddOrUpdateFailure($item){
			$this->SendDebug("AddOrUpdateFailure", $item);

			$status = $this->GetRepeatingStatus();
			$status["ID".$item["ID"]] = $item;
			$this->SetRepeatingStatus($status);
		}
		private function RemoveFailure($id){
			$status = $this->GetRepeatingStatus();

			if (array_key_exists("ID".$id,$status)){
				$this->SendDebug("RemoveFailure", $id);

				unset($status["ID".$id]);
				$this->SetRepeatingStatus($status);
			}
		}


		public function RequestAction($Ident, $Value) {
			switch($Ident) {
				case "TestVariable":
					// SetValue($this->GetIDForIdent($Ident), $Value);
					$this->SetValue($Ident, $Value);
					break;

				case "TimerCallback":
					$this->TimerCallback($Value);
					break;

				default:
					throw new Exception("Invalid Ident");
			}
		}

		public function RequestExternalAction($Variable, $Value) {
			$prop = $this->GetRepeatingVariableTreeUp($Variable);
			if ($prop == null){
				$this->SendDebug("RequestExternalAction", "Für Variable " . $ID . " konnte keine Einstellung gefunden werden.", 0);
				$this->LogMessage("Für Variable " . $ID . " konnte keine Einstellung gefunden werden.", KL_WARNING);
				return false;
			}

			$Object = IPS_GetObject($Variable);

			if ($Object['ObjectType'] !== 2){
				$this->SendDebug("RequestExternalAction", "Objekt ID " . $ID . " ist keine Variable", 0);
				$this->LogMessage("Objekt ID " . $ID . " ist keine Variable", KL_WARNING);
				return false;
			}

			$StatusItem = $this->GetRepeatingStatusItem($Variable);

			if ($prop["UpdateOnChangeOnly"] === true){
				if ($Value == GetValue($Variable) or ($Value == $StatusItem["Value"] and $prop["DoNotUpdateWhileRetrying"] ?? true)){
					$this->SendDebug("RequestExternalAction", "Variable " . $ID . " ist unverändert und wird deshalb nicht aktualisiert.", 0);
					$this->LogMessage("Variable " . $ID . " ist unverändert und wird deshalb nicht aktualisiert.", KL_DEBUG);
					return true;
				}
			}

			$VariableObject = IPS_GetVariable($Variable);

			try{
				$result = IPS_RequestAction($VariableObject['VariableAction'], $Object['ObjectIdent'], $Value);
			} catch (Exception $e) {
				$result = false;
				$this->SendDebug("RequestExternalAction", $e);
			}

			if ($result){
				$this->RemoveFailure($Variable);
			}else{
				$StatusItem["Value"] = $Value;
				$StatusItem["RepeatCount"] += 1;
				$StatusItem["LastTry"] = time();
				$this->AddOrUpdateFailure($StatusItem);
			}

			return $result;
		}
	}