<?
  // Klassendefinition
  class LichtsteuerungRGBW extends IPSModule {

    // Constructor
    public function __construct($InstanceID) {
      // Don't delete this Row!
      parent::__construct($InstanceID);
    }
    // Create Instance
    public function Create() {
      // Don't delete this Row!
      parent::Create();

      if(@$this->RegisterPropertyString("Lichter") !== false){
        $this->RegisterPropertyString("Lichter","");
      }


      $parent = $this->InstanceID;

      // Create Instance Profies
      // CreateProfile($profile, $type, $min, $max, $steps, $digits = 0, $prefix = "DMX", $suffix = "", $icon = "")
      if(!IPS_VariableProfileExists("DMX.Dim")){
        $this->CreateProfile("DMX.Dim", 1, 0, 255, 1, 1, "", "%");
      }
      if(!IPS_VariableProfileExists("DMX.Channel")){
        $this->CreateProfile("DMX.Channel", 1, 0, 100000, 1, 1, "", "");
      }
      if(!IPS_VariableProfileExists("DMX.Fade")){
        $this->CreateProfile("DMX.Fade", 2, 0, 10, 0.5, 1, "", "");
      }
      if(!IPS_VariableProfileExists("~Switch")){
        $this->CreateProfile("~Switch", 0, 0, 1, 1, 1, "", "");
      }


      // Create SetValue Script
      if(@IPS_GetObjectIDByIdent("SetValueScript", $this->InstanceID) === false){
      $sid = IPS_CreateScript(0 /* PHP Script */);
      IPS_SetParent($sid, $this->InstanceID);
      IPS_SetName($sid, "SetValue");
      IPS_SetIdent($sid, "SetValueScript");
      IPS_SetHidden($sid, true);
      IPS_SetScriptContent($sid, "<?
      if (\$IPS_SENDER == \"WebFront\"){
        SetValue(\$_IPS['VARIABLE'], \$_IPS['VALUE']);
      }
      ?>");
    }

    $svs = IPS_GetObjectIDByIdent("SetValueScript", $this->InstanceID);

    // Create Instance Vars (RGBW & FadeWert)
    // CreateVariable($type, $name, $ident, $parent, $position, $initVal, $profile, $action, $hide)
    $vid = $this->CreateVariable(1,"Global R","VarID_RWert", $parent, 1, 0, "DMX.Dim", $svs, false);
    $vid = $this->CreateVariable(1,"Global G","VarID_GWert", $parent, 2, 0, "DMX.Dim", $svs, false);
    $vid = $this->CreateVariable(1,"Global B","VarID_BWert", $parent, 3, 0, "DMX.Dim", $svs, false);
    $vid = $this->CreateVariable(1,"Global W","VarID_WWert", $parent, 4, 0, "DMX.Dim", $svs, false);
    $vid = $this->CreateVariable(2, "Global Fade","VarID_FadeWert", $parent, 5, 1, "DMX.Fade", $svs, false);




    // Set Trigger Events on Vars

  }

  // Überschreibt die intere IPS_ApplyChanges($id) Funktion
  public function ApplyChanges() {
    // Don't delete this Row!
    parent::ApplyChanges();

    $parent = $this->InstanceID;
    $hauptInstanz = $parent;

    $svs = IPS_GetObjectIDByIdent("SetValueScript", $this->InstanceID);

    $moduleList = IPS_GetModuleList();
    $dummyGUID = ""; //init
    foreach($moduleList as $l){
      if(IPS_GetModule($l)['ModuleName'] == "Dummy Module"){
        $dummyGUID = $l;
        break;
      }
    }

    // On Apply read Device List
    $deviceList = json_decode($this->ReadPropertyString("Lichter"));
    //print_r($deviceList);

    if (is_array($deviceList) || is_object($deviceList)){
      foreach($deviceList as $i => $list){

        if(@IPS_GetObjectIDByIdent("device$i", IPS_GetParent($this->InstanceID)) === false){
          $insID = IPS_CreateInstance($dummyGUID);
          IPS_SetParent($insID, IPS_GetParent($this->InstanceID));
        }
        else{
          $insID = IPS_GetObjectIDByIdent("device$i", IPS_GetParent($this->InstanceID));
        }

        IPS_SetName($insID, $list->Name);
        //IPS_SetName(" ");
        IPS_SetPosition($insID, $i + 1);
        IPS_SetIdent($insID, "device$i");

        $array = json_decode(json_encode($list),true);
        //print_r($array);

        $R = $array['RChannel'];
        $G = $array['GChannel'];
        $B = $array['BChannel'];
        $W = $array['WChannel'];
        $S = $array['Name'];
        $P = $array['Sort'];

        /*$isEmpty = @IPS_GetObjectIDByIdent("R", $insID);
        if(!empty($isEmpty)){
          $RV = IPS_GetVariableIDByName("R",      $insID);
          $GV = IPS_GetVariableIDByName("G",      $insID);
          $BV = IPS_GetVariableIDByName("B",      $insID);
          $WV = IPS_GetVariableIDByName("W",      $insID);
          $SV = IPS_GetObjectIDByIdent("Switch", $insID);
          $EV = IPS_GetEventIDByName("TriggerOnChange",  $insID);
          IPS_DeleteVariable($RV);
          IPS_DeleteVariable($GV);
          IPS_DeleteVariable($BV);
          IPS_DeleteVariable($WV);
          IPS_DeleteVariable($SV);
          IPS_DeleteEvent($EV);
        } */

        // Generate Values

        if(@IPS_GetObjectIDByIdent("R",$insID) === false){
          $vid = $this->CreateVariable(1,"R", "R", $insID, 1, $R, "DMX.Channel", $svs, TRUE);
          $vid = $this->CreateVariable(1,"G", "G", $insID, 2, $G, "DMX.Channel", $svs, TRUE);
          $vid = $this->CreateVariable(1,"B", "B", $insID, 3, $B, "DMX.Channel", $svs, TRUE);
          $vid = $this->CreateVariable(1,"W", "W", $insID, 4, $W, "DMX.Channel", $svs, TRUE);

          // Set Position
          $vid = IPS_SetPosition($insID, $P);

          // Generate Switch
          $vid = $this->CreateVariable(0, "Status", "Switch", $insID, 0, 0, "~Switch", $svs, FALSE);

          // Get Switch ID
          $triggerID = IPS_GetVariableIDByName("Status", $insID);
          $vid = $this->CreateEventOn($insID, $triggerID, $hauptInstanz);
        }
        //lösche überschüssige räume
    			while($i < count(IPS_GetChildrenIDs(IPS_GetParent($this->InstanceID))))
    			{
    				$i++;
    				if(@IPS_GetObjectIDByIdent("device$i", IPS_GetParent($this->InstanceID)) !== false)
    				{
    					$id = IPS_GetObjectIDByIdent("device$i", IPS_GetParent($this->InstanceID));
    					$this->DeleteObject($id);
    				}
    			}



      }
    }

    // Global Event Trigger
    $Test = IPS_GetVariableIDByName("Global R", $parent);
    $isEmpty = @IPS_GetObjectIDByIdent("TriggerOnChange".$Test, $parent);
    if(empty($isEmpty)){
      $triggerR = IPS_GetVariableIDByName("Global R", $parent);
      $triggerG = IPS_GetVariableIDByName("Global G", $parent);
      $triggerB = IPS_GetVariableIDByName("Global B", $parent);
      $triggerW = IPS_GetVariableIDByName("Global W", $parent);

      $vid = $this->CreateEventTrigger($triggerR);
      $vid = $this->CreateEventTrigger($triggerG);
      $vid = $this->CreateEventTrigger($triggerB);
      $vid = $this->CreateEventTrigger($triggerW);
    }

  }



  private function sortRooms(){

  }


  public function eventTriggerOnChange($InstanceID){


    $parent = IPS_GetParent($InstanceID);
    $fatherParent = IPS_GetParent($parent);

    $object = IPS_GetObject($parent);

    $getList =  IPS_GetProperty($parent, "Lichter");
    $deviceList = json_decode($getList);

    // Get Global ID`s
    $getGlobalR = IPS_GetVariableIDByName("Global R", $parent);
    $getGlobalG = IPS_GetVariableIDByName("Global G", $parent);
    $getGlobalB = IPS_GetVariableIDByName("Global B", $parent);
    $getGlobalW = IPS_GetVariableIDByName("Global W", $parent);
    $getGlobalF = IPS_GetVariableIDByName("Global Fade", $parent);

    // Get Global Values
    $getValueGlobalR = GetValue($getGlobalR);
    $getValueGlobalG = GetValue($getGlobalG);
    $getValueGlobalB = GetValue($getGlobalB);
    $getValueGlobalW = GetValue($getGlobalW);
    $getValueGlobalF = GetValue($getGlobalF);


    if (is_array($deviceList) || is_object($deviceList)){
      foreach($deviceList as $i => $device){
        // Set Value for each Device and if the Switch set on

        $channelName = $device->Name;
        //$ObjectID = @IPS_GetObjectIDByName($channelName, $parent);
        //$Object = IPS_GetObject($ObjectID);
        $ObjektID = @IPS_GetObjectIDByName($device->Name, $fatherParent);

        $deviceProp = IPS_GetObject($ObjektID);

        $getSwitch = $deviceProp['ChildrenIDs'][4];
        $Switch = GetValue($getSwitch);

        $getDevice = IPS_GetParent($device->RChannel);

        $getValueChannelR = GetValue($device->RChannel);
        $getValueChannelG = GetValue($device->GChannel);
        $getValueChannelB = GetValue($device->BChannel);
        $getValueChannelW = GetValue($device->WChannel);

        // Get Ident
        $channelObjectR = IPS_GetObject($device->RChannel);
        $channelObjectG = IPS_GetObject($device->GChannel);
        $channelObjectB = IPS_GetObject($device->BChannel);
        $channelObjectW = IPS_GetObject($device->WChannel);

        // Get Channel Number
        $channelStringR = $channelObjectR['ObjectIdent'];
        $channelStringG = $channelObjectG['ObjectIdent'];
        $channelStringB = $channelObjectB['ObjectIdent'];
        $channelStringW = $channelObjectW['ObjectIdent'];


        $channelNumberR = $this->getIntFromString($channelStringR);
        $channelNumberG = $this->getIntFromString($channelStringG);
        $channelNumberB = $this->getIntFromString($channelStringB);
        $channelNumberW = $this->getIntFromString($channelStringW);

        if($Switch == TRUE){
          DMX_FadeChannel($getDevice, $channelNumberR, $getValueGlobalR, $getValueGlobalF);
          DMX_FadeChannel($getDevice, $channelNumberG, $getValueGlobalG, $getValueGlobalF);
          DMX_FadeChannel($getDevice, $channelNumberB, $getValueGlobalB, $getValueGlobalF);
          DMX_FadeChannel($getDevice, $channelNumberW, $getValueGlobalW, $getValueGlobalF);
        }
        if($Switch == FALSE){

        }

      }
    }

    echo "\n Event Trigger started";
  }

  public function Destroy() {
    parent::Destroy();

    echo "LOOOOL loeschen Button gedrueckt";
  }

  protected function DeleteObject($id){
    if(IPS_HasChildren($id))
    {
      $childrenIDs = IPS_GetChildrenIDs($id);
      foreach($childrenIDs as $chid)
      {
        $this->DeleteObject($chid);
      }
      $this->DeleteObject($id);
    }
    else
    {
      $type = IPS_GetObject($id)['ObjectType'];
      switch($type)
      {
        case(0 /*kategorie*/):
        IPS_DeleteCategory($id);
        break;
        case(1 /*Instanz*/):
        IPS_DeleteInstance($id);
        break;
        case(2 /*Variable*/):
        IPS_DeleteVariable($id);
        break;
        case(3 /*Skript*/):
      IPS_DeleteScript($id,false /*move file to "Deleted" folder*/);
      break;
      case(4 /*Ereignis*/):
      IPS_DeleteEvent($id);
      break;
      case(5 /*Media*/):
      IPS_DeleteMedia($id);
      break;
      case(6 /*Link*/):
      IPS_DeleteLink($id);
      break;
    }
  }
  }


  protected function CreateVariable($type, $name, $ident, $parent, $position, $initVal, $profile, $action, $hide){
    $vid = IPS_CreateVariable($type);

    IPS_SetName($vid,$name);                            // set Name
    IPS_SetParent($vid,$parent);                        // Parent
    IPS_SetIdent($vid,$ident);                          // ident halt :D
    IPS_SetPosition($vid,$position);                    // List Position
    SetValue($vid,$initVal);                            // init value
    IPS_SetHidden($vid, $hide);                         // Objekt verstecken

    if(!empty($profile)){
      IPS_SetVariableCustomProfile($vid,$profile);    // Set custom profile on Variable
    }
    if(!empty($action)){
      IPS_SetVariableCustomAction($vid,$action);      // Set custom action on Variable
    }

    return $vid;                                        // Return Variable
  }

  protected function CreateProfile($profile, $type, $min, $max, $steps, $digits = 0, $prefix = "DMX", $suffix = "", $icon = ""){
    IPS_CreateVariableProfile($profile, $type);
    IPS_SetVariableProfileValues($profile, $min, $max, $steps);
    IPS_SetVariableProfileText($profile, $prefix, $suffix);
    IPS_SetVariableProfileDigits($profile, $digits);
    IPS_SetVariableProfileIcon($profile, $icon);
  }

  protected function CreateEventOn($insID, $triggerID, $hauptInstanz){
    // 0 = ausgelöstes; 1 = zyklisches; 2 = Wochenplan;
    $eid = IPS_CreateEvent(0);
    // Set Parent
    IPS_SetParent($eid, $insID);
    // Set Name
    IPS_SetName($eid, "TriggerOnChange");
    // Set Script
    IPS_SetEventScript($eid, "DMXDYN_refresh(". $hauptInstanz .", ". $hauptInstanz .", ". $insID .", ". $triggerID .");");
    // OnUpdate für Variable 12345
    IPS_SetEventTrigger($eid, 0, $triggerID);
    IPS_SetEventActive($eid, true);
    return $eid;
  }

  protected function CreateEventTrigger($triggerID){
    $Instance = $this->InstanceID;

    // 0 = ausgelöstes; 1 = zyklisches; 2 = Wochenplan;
    $eid = IPS_CreateEvent(0);
    // Set Parent
    IPS_SetParent($eid, $Instance);
    // Set Name
    IPS_SetName($eid, "TriggerOnChange".$triggerID);
    IPS_SetIdent($eid, "TriggerOnChange".$triggerID);
    // Set Script
    IPS_SetEventScript($eid, "DMXDYN_eventTriggerOnChange(". $Instance .", ". $triggerID .");");
    // OnUpdate für Variable 12345
    IPS_SetEventTrigger($eid, 0, $triggerID);
    IPS_SetEventActive($eid, true);

    return $eid;
  }

  // Own Function
  public function refresh($InstanceID, $insID, $triggerID) {
    // Anhand der TriggerID muss ich erkennen welcher der Parent ist und kann dann die Werte neu setzen

    $FadeSpeed = 1.5;

    // Get Channel ID`s
    $getChannelR = IPS_GetVariableIDByName("R", $insID);
    $getChannelG = IPS_GetVariableIDByName("G", $insID);
    $getChannelB = IPS_GetVariableIDByName("B", $insID);
    $getChannelW = IPS_GetVariableIDByName("W", $insID);


    // Get Channel Values
    $getValueChannelR = GetValue($getChannelR);
    $getValueChannelG = GetValue($getChannelG);
    $getValueChannelB = GetValue($getChannelB);
    $getValueChannelW = GetValue($getChannelW);
    $getDevice        = IPS_GetParent($getValueChannelR);

    // Get Ident
    $channelObjectR = IPS_GetObject($getValueChannelR);
    $channelObjectG = IPS_GetObject($getValueChannelG);
    $channelObjectB = IPS_GetObject($getValueChannelB);
    $channelObjectW = IPS_GetObject($getValueChannelW);

    // Get Channel Number
    $channelStringR = $channelObjectR['ObjectIdent'];
    $channelStringG = $channelObjectG['ObjectIdent'];
    $channelStringB = $channelObjectB['ObjectIdent'];
    $channelStringW = $channelObjectW['ObjectIdent'];

    $channelNumberR = $this->getIntFromString($channelStringR);
    $channelNumberG = $this->getIntFromString($channelStringG);
    $channelNumberB = $this->getIntFromString($channelStringB);
    $channelNumberW = $this->getIntFromString($channelStringW);


    // Get Global ID`s
    $getGlobalR = IPS_GetVariableIDByName("Global R", $InstanceID);
    $getGlobalG = IPS_GetVariableIDByName("Global G", $InstanceID);
    $getGlobalB = IPS_GetVariableIDByName("Global B", $InstanceID);
    $getGlobalW = IPS_GetVariableIDByName("Global W", $InstanceID);
    $getGlobalF = IPS_GetVariableIDByName("Global Fade", $InstanceID);

    // Get Global Values
    $getValueGlobalR = GetValue($getGlobalR);
    $getValueGlobalG = GetValue($getGlobalG);
    $getValueGlobalB = GetValue($getGlobalB);
    $getValueGlobalW = GetValue($getGlobalW);
    $getValueGlobalF = GetValue($getGlobalF);

    // IF True Set Values
    $Switch = GetValue($triggerID);

    if($Switch == TRUE){
      // Set Channel Values
      DMX_FadeChannel($getDevice, $channelNumberR, $getValueGlobalR, $getValueGlobalF);
      DMX_FadeChannel($getDevice, $channelNumberG, $getValueGlobalG, $getValueGlobalF);
      DMX_FadeChannel($getDevice, $channelNumberB, $getValueGlobalB, $getValueGlobalF);
      DMX_FadeChannel($getDevice, $channelNumberW, $getValueGlobalW, $getValueGlobalF);
    }
    // IF False Set 0
    if($Switch == FALSE){
      // Set Channel Values
      DMX_FadeChannel($getDevice, $channelNumberR, 0, $getValueGlobalF);
      DMX_FadeChannel($getDevice, $channelNumberG, 0, $getValueGlobalF);
      DMX_FadeChannel($getDevice, $channelNumberB, 0, $getValueGlobalF);
      DMX_FadeChannel($getDevice, $channelNumberW, 0, $getValueGlobalF);
    }
  }

  protected function getIntFromString($string){
    $matches = preg_replace('/\D/', '', $string);
    print_r($matches);
    return $matches;
  }
}
?>
