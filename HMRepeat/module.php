<?php

declare(strict_types=1);
	class HMRepeat extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			//$this->ConnectParent('{6179ED6A-FC31-413C-BB8E-1204150CF376}');
			$this->ConnectParent('{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}');
			
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
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
	}