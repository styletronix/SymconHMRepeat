<?php

declare(strict_types=1);
	class HMRepeat extends IPSModule
	{
		private int $ActionScriptID;

		public function Create()
			{
				parent::Create();

				$this->RegisterPropertyString("repeatingVariables", "");

				$this->ActionScriptID = $this->RegisterScript("ActionScript","Externer ActionScript", "<?\n\nSXHMREP_RequestExternalAction(" . $this->InstanceID . ", \$_IPS['VARIABLE'], \$_IPS['VALUE']);");
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
				IPS_LogMessage("MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true));
			}

		public function ForwardData($JSONString)
			{
				$data = json_decode($JSONString);
				IPS_LogMessage('Splitter FRWD', utf8_decode($data->Buffer));

				$this->SendDataToParent(json_encode(['DataID' => '{75B6B237-A7B0-46B9-BBCE-8DF0CFE6FA52}', 'Buffer' => $data->Buffer]));

				return 'String data for device instance!';
			}

		public function ReceiveData($JSONString)
			{
				$data = json_decode($JSONString);
				IPS_LogMessage('Splitter RECV', utf8_decode($data->Buffer));

				$this->SendDataToChildren(json_encode(['DataID' => '{98FEC99D-6AD9-4598-8F50-2976DA0A32C8}', 'Buffer' => $data->Buffer]));
			}

		private function GetListItems($List){
				$arrString = $this->ReadPropertyString($List);
				if ($arrString){
					$arr = json_decode($arrString, true);
				
					return $arr;
				}	
				return null;
		}
		private function GetRepeatingVariable($objID){
			$arr = GetListItems("repeatingVariables");
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
			$arr = GetRepeatingVariable($objID);
			
			if ($arr){
				return $arr;
			}else{
				$parent = IPS_GetParent($objID);
				if ($parent > 0){
					return GetRepeatingVariableTreeUp($parent);
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
						$this->UpdateVariablesRecursive([$objID]);
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
			if ($Variable["VariableCustomAction"] == 1 or $Variable["VariableCustomAction"] == 2300) {
				$this->LogMessage("F�r Variable {$_IPS['VARIABLE']} wurde die Standardaktion deaktiviert. Sie wird daher nicht verwendet.", KL_WARNING);
				return; 
			}
			if ($Variable["VariableCustomAction"] > 0 and $Variable["VariableCustomAction"] !== $this->ActionScriptID) { 
				$this->LogMessage("Variable {$_IPS['VARIABLE']} hat eine benutzerdefinierte Aktion und kann daher nicht verwendet werden.", KL_WARNING);
				return; 
			}

			$this->RegisterReference($ID);

			if ($Variable["VariableCustomAction"] == $this->ActionScriptID){ return; }
			
			IPS_SetVariableCustomAction($ID, $this->ActionScriptID);
			$this->LogMessage("Variable {$_IPS['VARIABLE']} wurde hinzugef�gt.", KL_MESSAGE);

			// $this->UnregisterReference(12345);
			// $ReferenceList = $this->GetReferenceList();
		}

		public function RequestAction($Ident, $Value) {
			switch($Ident) {
				case "TestVariable":
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;

				default:
					throw new Exception("Invalid Ident");
			}
		}

		public function RequestExternalAction($Variable, $Value) {
			$prop = GetRepeatingVariableTreeUp($Variable);
			if ($prop == null){
				$this->LogMessage("F�r Variable {$_IPS['VARIABLE']} konnte keine Einstellung gefunden werden.", KL_WARNING);
				return false;
			}

			$Object = IPS_GetObject($Variable);

			if ($Object['ObjectType'] !== 2){
				$this->LogMessage("Objekt ID {$_IPS['VARIABLE']} ist keine Variable", KL_WARNING);
				return false;
			}

			if ($prop["UpdateOnChangeOnly"] === true){
				if ($val == GetValue($Variable)){
					$this->LogMessage("Variable {$_IPS['VARIABLE']} ist unver�ndert und wird deshalb nicht aktualisiert.", KL_DEBUG);
					return true;
				}
			}

			$VariableObject = IPS_GetVariable($Variable);
			return IPS_RequestAction($VariableObject['VariableAction'], $Object['ObjectIdent'], $Value);
		}
	}