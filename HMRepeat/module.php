<?php

declare(strict_types=1);
	class HMRepeat extends IPSModule
	{
		public function Create()
			{
				parent::Create();

				$this->RegisterPropertyString("repeatingVariables", "");
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

		private function GetListItems($List)
			{
				$arrString = $this->ReadPropertyString($List);
				if ($arrString){
					$arr = json_decode($arrString, true);
				
					return $arr;
				}	
				return null;
			}

		private function UpdateVariables()
			{
				$arr = $this->GetListItems("repeatingVariables");
				if ($arr){
					foreach($arr as $key1) {
						$key1["InstanceID"]
					}
				}	
			}
	}