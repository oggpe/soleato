<?php
/**
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @author Rodrigo Rocco <info@jomres-plugins.com>
* @copyright 2010-2017 Rodrigo Rocco - Jomres Plugins & Development - www.jomres-plugins.com
**/

// ################################################################
defined('_JOMRES_INITCHECK') or die('');
// ################################################################

jr_import('dobooking');

class j05000bookingobject
{
    public function __construct($componentArgs)
    {
        // Must be in all minicomponents. Minicomponents with templates that can contain editable text should run $this->template_touch() else just return
        $MiniComponents = jomres_singleton_abstract::getInstance('mcHandler');

        if ($MiniComponents->template_touch) {
            $this->template_touchable = false;

            return;
        }
        $bkg = new booking();
        $this->bookingObject = $bkg;
        $bk = $this->bookingObject;
        if (strlen($bk->error_code) > 0) {
            $this->bookingObject = null;
        } else {
            unset($bk);
        }

        if (!AJAXCALL) {
            $mrConfig = getPropertySpecificSettings();
            $bkg = new booking();
            $bkg->suppress_output = true;
            $this->bookingObject = $bkg;
            $bk = $this->bookingObject;
            if (strlen($bk->error_code) > 0) {
                $this->bookingObject = null;
            } else {
                unset($bk);
            }

            $bkg->remove_third_party_extra('tourist_tax', 0);
            $bkg->resetTotals();

            $bkg->generateBilling();
            $bkg->storeBookingDetails();

            if (!isset($mrConfig['tourist_tax'])) {
                $mrConfig['tourist_tax'] = '0';
            }

            if ((float) $mrConfig['tourist_tax'] > 0) {
                if (using_bootstrap()) {
                    echo '<p class="alert">'.jr_gettext('_JOMRES_TOURIST_TAX_NOTE', '_JOMRES_TOURIST_TAX_NOTE').'</p>';
                } else {
                    echo '<p class="ui-state-highlight">'.jr_gettext('_JOMRES_TOURIST_TAX_NOTE', '_JOMRES_TOURIST_TAX_NOTE').'</p>';
                }
            }
        }
    }

    // This must be included in every Event/Mini-component
    public function getRetVals()
    {
        return $this->bookingObject;
    }
}

if (!class_exists('booking')) {
    class booking extends dobooking
    {
        private $all_forbidded;
        private $forbidded_room_types;
        private $combination_discount_applied = false;
        // Copy of minimum occupancies plguin
        function getTariffsForRoomUids( $freeRoomsArray )
            {
            $mrConfig = $this->mrConfig;
            $this->build_tariff_to_date_map();
            $roomAndTariffArray          = array ();
            $already_found_tariffs       = array ();
            $this->tariff_types_min_days = array ();
            $dateRangeArray              = explode( ",", $this->dateRangeString );
            $dateRangeArray_count        = count( $dateRangeArray );
            $filtered_out_type_type_ids  = array ();
            $this->setErrorLog( "getTariffsForRoomUids:: tariff map " . serialize( $this->micromanage_tarifftype_to_date_map ) );
            $this->setErrorLog( "--------------------------------------------" );
                
            $minimum_occupancies_enabled = false;
            if (file_exists(JOMRESPATH_BASE.JRDS."core-plugins".JRDS.'minimum_occupancies'.JRDS."plugin_info.php")){
                $minimum_occupancies_enabled = true;
                jr_import('jomres_occupancies');
                $occupancies = new jomres_occupancies();
                $occupancies->property_uid = $this->property_uid;
                $occupancies->get_all_by_property_uid();
            }

            $rooms_available = array();
            
            $guest_type_variants=$this->getVariantsOfType('guesttype');
            $guest_types_selected = array();
            
            foreach ($guest_type_variants as $g)
                {
                $guest_types_selected[$g['id']] = $g['qty']; 
                }

             // $freeRoomsArray = $this->filter_forbidded($freeRoomsArray);   
            
            if ( !empty( $freeRoomsArray ) && is_array( $freeRoomsArray ) )
                {
                $unixArrivalDate   = $this->getMkTime( $this->arrivalDate );
                $unixDepartureDate = $this->getMkTime( $this->departureDate );

                foreach ( $freeRoomsArray as $room_uid )
                    {
                    $rateDeets = $this->getTariffsForRoomUidByClass( $room_uid );
                    foreach ( $rateDeets as $tariff )
                        {
                        $datesValid                = $this->filter_tariffs_on_dates( $tariff, $unixArrivalDate, $unixDepartureDate ); // Does the tariff's from/to dates fall within the booking's dates? There will be some overlap here if we use Advanced or Micromanage mode. That's where the tariff_to_date_map will come into play
                        $stayDaysValid             = $this->filter_tariffs_staydays( $tariff ); // This will also use the map, it'll help to calculate also the minimum interval
                        $roomsAlreadySelectedTests = $this->filter_tariffs_alreadyselectedcheck( $tariff ); // If the tariff can only be selected when N number of rooms have already been selected?
                        $numberPeopleValid         = $this->filter_tariffs_peoplenumbercheck( $tariff ); // If the total number of people in the booking fall within the tariff's min/max people range?
                        $dowCheck                  = $this->filter_tariffs_dowcheck( $tariff ); // Does the tariff allow selections on the arrival date's day of week?

                        $passedOccupancyCheck = true;
                        $room_type = $this->allPropertyRooms[$room_uid]['room_classes_uid'];

                        if ($minimum_occupancies_enabled){                                                     
                            if (array_key_exists($room_type,$occupancies->all_occupancies))
                                {
                                $map=$occupancies->all_occupancies[$room_type]['guest_type_map'];
                                foreach ($map as $key=>$val)
                                    {
                                    if (isset($guest_types_selected[$key])) {
                                        $number = $guest_types_selected[$key];
                                        if ((int)$number < (int)$val)
                                            $passedOccupancyCheck = false;
                                        }
                                    }
                                }
                        }
                        
                        $rates_uid = $tariff->rates_uid;
                        $this->setErrorLog( "getTariffsForRoomUids:: Checking tariff id " . $rates_uid . " " );
                        if ( $datesValid && $stayDaysValid && $numberPeopleValid && $dowCheck && $roomsAlreadySelectedTests && $passedOccupancyCheck)
                            {
                            $tariff_type_id = 0;
                            if (isset($this->all_tariff_id_to_tariff_type_xref[ $rates_uid ][ 0 ])) {
                                $tariff_type_id = $this->all_tariff_id_to_tariff_type_xref[ $rates_uid ][ 0 ];
                            }
                            if ( !isset( $already_found_tariffs[ $tariff_type_id . " " . $room_uid ] ) && !in_array( $tariff_type_id, $filtered_out_type_type_ids ) )
                                {
                                $pass_price_check = true;
                                if ( $mrConfig[ 'tariffmode' ] == "2" ) // If tariffmode = 2, we need to finally scan $this->micromanage_tarifftype_to_date_map, to ensure that all dates have a price set
                                    {
                                    if ( empty( $this->micromanage_tarifftype_to_date_map ) ) $pass_price_check = false;
                                    else
                                        {
                                        //$this->setPopupMessage( str_replace(";", " " ,serialize( $this->micromanage_tarifftype_to_date_map[$tariff_type_id] ) ) );
                                        $map_count = count( $this->micromanage_tarifftype_to_date_map[ $tariff_type_id ] );
                                        foreach ( $this->micromanage_tarifftype_to_date_map[ $tariff_type_id ] as $dates )
                                            {
                                            $this->setErrorLog( "getTariffsForRoomUids:: Count dates " . $map_count . " Count daterange array " . $dateRangeArray_count . " " );
                                            if ( $map_count != $dateRangeArray_count ) // There are more dates in the date range array than there are valid tariffs. This means that during the map building phase we passed the date of the last tariff found
                                                {
                                                $this->setErrorLog( "getTariffsForRoomUids:: tariff map count != dates count " );
                                                $pass_price_check = false;
                                                }
                                            else
                                                {
                                                if ( (float) $dates[ 'price' ] == 0 && $dates[ 'tariff_type_id' ] == $tariff_type_id )
                                                    {
                                                    $pass_price_check = false;
                                                    $this->setErrorLog( "getTariffsForRoomUids:: Removing a tariff as at least one other tariff in the series is set to 0. Tariff type id = " . $tariff_type_id );
                                                    $filtered_out_type_type_ids[ ] = $tariff_type_id;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                if ( $pass_price_check )
                                    {
                                    if ( $mrConfig[ 'tariffmode' ] == "2" ) $already_found_tariffs[ $tariff_type_id . " " . $room_uid ] = 1; // 
                                        if (!$this->is_forbidded($room_uid)) {
                                            $roomAndTariffArray[ ] = array ( $room_uid, $rates_uid );
                                        }
                                    }

                                }
                            }
                        elseif ( $datesValid && !$stayDaysValid && $numberPeopleValid && $dowCheck && $roomsAlreadySelectedTests && $mrConfig[ 'tariffmode' ] == "1" ) // Everything passed except the number of days in the booking
                            {
                            $mindays = $this->simple_tariff_to_date_map[ $rates_uid ][ 'mindays' ];
                            if ( $mindays < $this->mininterval )
                                {
                                $this->mininterval = $mindays;
                                }
                            }
                        }
                    }
                }
            else
            $this->setErrorLog( "getTariffsForRoomUids::count(freeRoomsArray) = 0" );
            $this->setErrorLog( "--------------------------------------------" );



            // $this->all_forbidded=false;
            // if (empty($roomAndTariffArray)){
            //     $this->all_forbidded=true;
            // }


            if ( empty( $roomAndTariffArray ) && $mrConfig[ 'tariffmode' ] == "2" )
                {
                if ( !empty( $this->tariff_types_min_days ) )
                    {
                    $this->mininterval = 1000; // We MUST reset the minimum interval here, as it's going to be recalculated.
                    foreach ( $this->tariff_types_min_days as $mindays )
                        {
                        if ( $mindays < $this->mininterval ) $this->mininterval = $mindays;
                        }
                    }
                }

            return $roomAndTariffArray;
            }    

    /**
     * Called in phase 3 of the ajax queries.
     * Checks the state of the booking. If any of the checks fail the method setMonitoring is passed a message.
     * If, at the end, the monitoringMessages array is empty then we know that it has passed all the tests and the booking is ready for confirmation. At that stage the ok_to_book flag is set to true.
     */
    public function monitorBookingStatus()
    {
        $mrConfig = $this->mrConfig;
        $tmpBookingHandler = jomres_getSingleton('jomres_temp_booking_handler');
        $amend_contract = $tmpBookingHandler->getBookingFieldVal('amend_contract');

        // Let's see if the form is ready to be booked.

        if (!empty($this->requestedRoom) && $this->email != '') {
            $this->email_usage_check($this->email);
            if (!$this->email_address_can_be_used) {
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_EMAIL_ALREADY_IN_USE', '_JOMRES_BOOKINGFORM_MONITORING_EMAIL_ALREADY_IN_USE', false, false)));
            }
        }

        if (get_showtime('include_room_booking_functionality')) {
            if ($this->mininterval == 1000) { // Probably a tariff wasn't found
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_SRP_WEHAVENOVACANCIES', '_JOMRES_SRP_WEHAVENOVACANCIES', false, false)));
                $this->resetPricingOutput = true;
            }

            if ($this->stayDays < $this->mininterval && !$amend_contract && $this->mininterval < 1000 && empty($this->requestedRoom)) {
                $this->resetPricingOutput = true;
                if ($mrConfig[ 'wholeday_booking' ] == '1') {
                    $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_BOOKING_TOO_SHORT1_WHOLEDAY', '_JOMRES_BOOKINGFORM_MONITORING_BOOKING_TOO_SHORT1_WHOLEDAY', false, false)).' '.$this->mininterval.' '.$this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_BOOKING_TOO_SHORT2', '_JOMRES_BOOKINGFORM_MONITORING_BOOKING_TOO_SHORT2', false).' '.($this->stayDays - 1)));
                } else {
                    $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_BOOKING_TOO_SHORT1', '_JOMRES_BOOKINGFORM_MONITORING_BOOKING_TOO_SHORT1', false, false)).' '.$this->mininterval.' '.$this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_BOOKING_TOO_SHORT2', '_JOMRES_BOOKINGFORM_MONITORING_BOOKING_TOO_SHORT2', false).' '.$this->stayDays));
                }

                if ($this->jrConfig[ 'useJomresMessaging' ] == '1') {
                    $this->build_tariff_to_date_map();
                    if ($mrConfig[ 'tariffmode' ] == '2') {
                        foreach ($this->micromanage_tarifftype_to_date_map as $dates) {
                            $prices = array();
                            foreach ($dates as $date) {
                                $prices[ $date[ 'mindays' ] ] = $date[ 'price' ];
                            }
                            foreach ($prices as $key => $val) {
                                if ($val > 0) {
                                    if ($this->cfg_perPersonPerNight == '1') {
                                        $pernight = jr_gettext('_JOMRES_FRONT_TARIFFS_PPPN', '_JOMRES_FRONT_TARIFFS_PPPN', false);
                                    } else {
                                        $pernight = jr_gettext('_JOMRES_FRONT_TARIFFS_PN', _JOMRES_FRONT_TARIFFS_PN, false);
                                    }
                                    //echo ';jomresJquery.jGrowl(\'' . jr_gettext( '_JOMRES_STAYFORAMINIMUMOF', _JOMRES_STAYFORAMINIMUMOF, false ) . " " . $key . " " . jr_gettext( '_JOMRES_NIGHTSFOR', _JOMRES_NIGHTSFOR, false ) . " " . output_price( $val ) . $pernight . '\', { life: 20000 });';
                                }
                            }
                        }
                    }

                    if ($mrConfig[ 'tariffmode' ] == '1') {
                        foreach ($this->simple_tariff_to_date_map as $tariff) {
                            if ($this->cfg_perPersonPerNight == '1') {
                                $pernight = jr_gettext('_JOMRES_FRONT_TARIFFS_PPPN', '_JOMRES_FRONT_TARIFFS_PPPN', false);
                            } else {
                                $pernight = jr_gettext('_JOMRES_FRONT_TARIFFS_PN', '_JOMRES_FRONT_TARIFFS_PN', false);
                            }
                            //echo ';jomresJquery.jGrowl(\'' . jr_gettext( '_JOMRES_STAYFORAMINIMUMOF', _JOMRES_STAYFORAMINIMUMOF, false ) . " " . $tariff[ 'mindays' ] . " " . jr_gettext( '_JOMRES_NIGHTSFOR', _JOMRES_NIGHTSFOR, false ) . " " . output_price( $tariff[ 'price' ] ) . " " . $pernight . '\', { life: 20000 });';
                        }
                    }
                }
            }

            if (empty($this->requestedRoom) && $this->getSingleRoomPropertyStatus()) {
                $this->resetPricingOutput = true;
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_SRP_WEHAVENOVACANCIES', '_JOMRES_SRP_WEHAVENOVACANCIES', false, false)));
            }

            if (isset($this->number_of_free_rooms)) { // $this->number_of_free_rooms might not be set, it depends on the "field" sent
                if ($this->number_of_free_rooms == 0 && ($this->currentField == 'arrivalDate' || $this->currentField == 'departureDate' || $this->currentField == 'guesttype')) {
                    $this->resetPricingOutput = true;
                    $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_SRP_WEHAVENOVACANCIES', '_JOMRES_SRP_WEHAVENOVACANCIES', false, false)));
                }

            }

            if (!$this->checkArrivalDate($this->arrivalDate)) {
                $this->resetPricingOutput = true;
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_ARRIVALDATE_INVALID', '_JOMRES_BOOKINGFORM_MONITORING_ARRIVALDATE_INVALID', false, false)));
            }

            // if (!$this->checkDepartureDate($this->departureDate) )
            // {
            // $this->resetPricingOutput=true;
            // if ($mrConfig['wholeday_booking'] == "1")
            // $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_DEPARTUREDATE_INVALID_WHOLEDAY',_JOMRES_BOOKINGFORM_MONITORING_DEPARTUREDATE_INVALID_WHOLEDAY,false,false)));
            // else
            // $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_DEPARTUREDATE_INVALID',_JOMRES_BOOKINGFORM_MONITORING_DEPARTUREDATE_INVALID,false,false)));
            // }

            $numberOfGuestTypes = $this->getVariantsOfType('guesttype');

            // if ($this->is_forbidded()){
            //     $this->resetPricingOutput = true;
            //     $this->setMonitoring('Not allowed combination of guests');
            // }

            /*if ($this->all_forbidded && !$this->at_least_one_allowed){
                $this->resetPricingOutput = true;
                 $this->setMonitoring($this->sanitiseOutput('Not rooms available for the selected guest types.'));
            }*/

            
            $requestedRoom_count = count($this->requestedRoom);

            foreach ($numberOfGuestTypes as $r) {
                if (!$this->checkGuestVariantIdAndQty($r[ 'id' ], $r[ 'qty' ])) {
                    $this->resetPricingOutput = true;
                    $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_GUEST_TYPE_INCORRECT', '_JOMRES_BOOKINGFORM_MONITORING_GUEST_TYPE_INCORRECT', false, false)));
                }
            }

            if (!$this->jpd_check_if_all_forbidden() )  { 
                $this->resetPricingOutput = true;#
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_JDP_NO_ROOMS_FOR_SELECTED_GUEST_TYPES', '_JOMRES_JDP_NO_ROOMS_FOR_SELECTED_GUEST_TYPES', false, false)));
            }

            if ($this->total_in_party < 1 && !empty($numberOfGuestTypes)) {
                $this->resetPricingOutput = true;
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_SELECT_GUEST_NUMBERS', '_JOMRES_BOOKINGFORM_MONITORING_SELECT_GUEST_NUMBERS', false, false)));
            }
            if (!empty($numberOfGuestTypes) && !$this->tariffsCanHostTotalInParty()) {
                $this->resetPricingOutput = true;
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_TOO_MANY_IN_PARTY_FOR_TARIFFS', '_JOMRES_BOOKINGFORM_MONITORING_TOO_MANY_IN_PARTY_FOR_TARIFFS', false, false)));
            }
            if ($this->total_in_party < $requestedRoom_count && !empty($numberOfGuestTypes)) {
                $this->resetPricingOutput = true;
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_MORE_ROOMS_THAN_GUESTS', '_JOMRES_BOOKINGFORM_MONITORING_MORE_ROOMS_THAN_GUESTS', false, false)));
            }
            //if ($this->total_in_party > $this->beds_available && count($result)>0 && count($this->requestedRoom ) > 0)
	    /*OPE test added to correct wrong message  when there is a black booking, beds_available is set in custom_code/dobooking.class.php in function checkPeopleNumbers, $totalFreeBeds = -1  */	
            if ($this->total_in_party > $this->beds_available && !empty($numberOfGuestTypes) /*OPE start*/ && $this->beds_available != -1 /*OPE end*/) {
                $this->resetPricingOutput = true;
                if ($this->cfg_singleRoomProperty != '1') {
                    $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_TOO_MANY_GUESTS_FOR_BEDS', '_JOMRES_BOOKINGFORM_MONITORING_TOO_MANY_GUESTS_FOR_BEDS', false, false)));
                } else {
                    $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_SRP_WEHAVENOVACANCIES', '_JOMRES_SRP_WEHAVENOVACANCIES', false, false)));
                }
            }
            if (!empty($numberOfGuestTypes) && $requestedRoom_count > 0 && !$this->selectedRoomsCanHostTotalInParty()) {
                $this->resetPricingOutput = true;
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_CHOOSE_MORE_ROOMS', '_JOMRES_BOOKINGFORM_MONITORING_CHOOSE_MORE_ROOMS', false, false)));
            }
		
	    /*OPE added */
            if (empty($this->rooms_list_style_roomstariffs) && empty($this->requestedRoom) ) {
                $this->resetPricingOutput = true;
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_SOLEATO_NO_ROOMS_FOR_SELECTED_TARIFF_OR_DATES', '_JOMRES_SOLEATO_NO_ROOMS_FOR_SELECTED_TARIFF_OR_DATES', false, false)));
            }
		
            if (empty($this->requestedRoom)) {
                $this->resetPricingOutput = true;
                if ($this->cfg_singleRoomProperty != '1') {
                    $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_BOOKINGFORM_MONITORING_SELECT_A_ROOM', '_JOMRES_BOOKINGFORM_MONITORING_SELECT_A_ROOM', false, false)));
                } else {
                    $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_COM_MR_QUICKRES_STEP4_TITLE', '_JOMRES_COM_MR_QUICKRES_STEP4_TITLE', false, false)));
                }
            }
        }

        if (!get_showtime('include_room_booking_functionality')) {
            $quantity = 0;
            if (!empty($this->third_party_extras)) {
                foreach ($this->third_party_extras as $tpe) {
                    if (!empty($tpe)) {
                        $quantity = 1;
                    } // We don't care how many extras there are, so long as at least one has been selected.
                    //$this->setPopupMessage("jintour ".serialize($this->third_party_extras));
                }
            }
            $extrasArray = explode(',', $this->extras);
            foreach ($extrasArray as $extra) {
                if ($extra != '') {
                    $quantity = $quantity + $this->extrasquantities[ $extra ];
                }
                //$this->setPopupMessage($quantity);
            }
            if ($quantity == 0) {
                $this->setMonitoring($this->sanitiseOutput(jr_gettext('_JOMRES_AJAXFORM_EXTRAS_SELECT', '_JOMRES_AJAXFORM_EXTRAS_SELECT', false, false)));
            }
        }

        //$this->setPopupMessage('');

        if ($this->getMonitoringNumberOfMessages() == 0) {
            $this->ok_to_book = true;
        }
    }    

 
    function is_forbidded($room_uid){
        $result = $this->getVariantsOfType('guesttype');
        $room_type = 0;
        if (!empty($result)) {
            if (isset($this->allPropertyRooms[ $room_uid ][ 'room_classes_uid' ])) {
                $room_type = $this->allPropertyRooms[ $room_uid ][ 'room_classes_uid' ];
            } 

       //  if (in_array($this->forbidded_room_types, $room_type)){
         //   return true;
       // }

        //$this->forbidded_room_types[] = $room_type;

            $existing_combinations = jpd_get_existing_combinations($this->property_uid);            
            $whole_combination_exist = selected_combination_exist($existing_combinations,$result);  
   
            if ( $whole_combination_exist){    
                $room_types = $existing_combinations[$whole_combination_exist]['room_types'];
                if ( array_key_exists($room_type,$room_types) && $room_types[$room_type]['forbidded'] == 1 ){
                    return true;
                }
            }
 
        }

        return false;
    }

    public function te_setAverageRate()
    {
        $this->setErrorLog('te_setAverageRate:: Started');
        $mrConfig = $this->mrConfig;

        $tmpBookingHandler = jomres_getSingleton('jomres_temp_booking_handler');
        $disc = array();
        if (!isset($mrConfig[ 'wisepriceactive' ]) || empty($mrConfig[ 'wisepriceactive' ])) {
            $mrConfig[ 'wisepriceactive' ] = '0';
        }
        if (!isset($mrConfig[ 'wisepricethreshold' ]) || empty($mrConfig[ 'wisepricethreshold' ])) {
            $mrConfig[ 'wisepricethreshold' ] = '60';
        }
        $wisepricethreshold = (int) $mrConfig[ 'wisepricethreshold' ];
        $tmpBookingHandler->updateBookingField('wiseprice_discount', $disc);

        $datesTilBooking = $this->findDateRangeForDates($this->today, $this->arrivalDate);
        $dateRangeArray = explode(',', $this->dateRangeString);
        $stayDays = $this->stayDays;
        $this->build_tariff_to_date_map();

        $result = $this->getVariantsOfType('guesttype');           
        $existing_combinations = jpd_get_existing_combinations($this->property_uid,false);            
        $whole_combination_exist = selected_combination_exist($existing_combinations,$result);   

        foreach ($this->requestedRoom as $rt) {
            $calculated_price_per_room_per_day = 0.00;
            $rm = explode('^', $rt);
            $tariff_id = $rm[ 1 ];
            $room_id = $rm[ 0 ];
            $this->room_allocations[ $room_id ]['guest_types']=array();
            $query = 'SELECT tarifftype_id FROM #__jomcomp_tarifftype_rate_xref WHERE tariff_id = '.(int) $tariff_id;
            $tarifftypeid = doSelectSql($query, 1);

            if ($tarifftypeid != false) {
                $dates = $this->micromanage_tarifftype_to_date_map[ $tarifftypeid ];
                $cumulative_price = 0.00;
                foreach ($dateRangeArray as $date) {
                    $cumulative_price += $dates[ $date ][ 'price' ];
                }

                $basic_room_rate = $cumulative_price / $stayDays;
                $roomType = $this->allPropertyTariffs[ $tariff_id ][ 'roomclass_uid' ];
                if (count($datesTilBooking) <= $wisepricethreshold && $mrConfig[ 'wisepriceactive' ] == '1') {
                    $percentageBooked = $this->getPercentageOfRoomsBookedForRoomtype($roomType);

                    $r = $this->getDiscountedRoomrate($basic_room_rate, $percentageBooked);
                    //$this->setPopupMessage("Discount rate ".$r);
                    $old_room_rate = $basic_room_rate;

                    $isDiscounted = false;
                    if ($r < $basic_room_rate) {
                        $isDiscounted = true;
                        $tmpBookingHandler->updateBookingField('booking_discounted', true);
                    }
                    $disc[ ] = array('roomrate' => $old_room_rate, 'discountedRate' => $r, 'roomType' => $roomType, 'isDiscounted' => $isDiscounted);
                    $basic_room_rate = $r;
                    $tmpBookingHandler->updateBookingField('wiseprice_discount', $disc);
                    $tmpBookingHandler->saveBookingData();
                } else {
                    $old_room_rate = $basic_room_rate;
                }

                $this->room_allocations[ $room_id ][ 'price_per_night' ] = $basic_room_rate;
                $this->room_allocations[ $room_id ][ 'price_per_night_nodiscount' ] = $old_room_rate;

                if ($whole_combination_exist != false){
     
                     foreach ($existing_combinations[$whole_combination_exist]['room_types'] as $room_type => $data) {
                         if ($roomType == $room_type && $data['forbidded'] == 0){
                                foreach ($data['discount'] as $id => $discountPercentage) {
                                    if ($discountPercentage > 0){
                                       
                                        $discount = ($basic_room_rate / 100) * $discountPercentage;
                                        $discountedRate = $basic_room_rate - $discount;
                                        $qty = $existing_combinations[$whole_combination_exist]['qty'][$id];
                                        $this->room_allocations[ $room_id ]['guest_types'][$id] = array('qty'=>$qty,'discountedRate'=> $discountedRate);
                                    }
                                }
                         }
                     }
                } 
            }
        }

        $total = 0.00;
        $total_nodiscount = 0.00;
        $total_number_of_guests = 0;
        foreach ($this->room_allocations as $room) {

            if ($this->cfg_perPersonPerNight == '0') {
                $total += $room[ 'price_per_night' ];
                $total_nodiscount += $room[ 'price_per_night_nodiscount' ];
            } else {
                $total_guests_discounted = 0;
                foreach ($room['guest_types'] as $id => $data) {
                    if (isset($data['discountedRate'])){
                        $this->combination_discount_applied = true;
                        $total_guests_discounted+=$data['qty'];
                        $total+=$data['discountedRate']*$data['qty'];
                    }
                }
                $total_number_of_guests += $room[ 'number_allocated' ];
                $total += ($room[ 'price_per_night' ] * ($room[ 'number_allocated' ]-$total_guests_discounted));

                $total_nodiscount += ($room[ 'price_per_night_nodiscount' ] * $room[ 'number_allocated' ]);
            }
        }

        if ($this->cfg_perPersonPerNight == '1') {
            $this->rate_pernight = $total / $total_number_of_guests;
            $this->rate_pernight_nodiscount = $total_nodiscount / $total_number_of_guests;
        } else {
            $this->rate_pernight = $total / count($this->room_allocations);
            $this->rate_pernight_nodiscount = $total_nodiscount / count($this->room_allocations);
        }

        $this->setErrorLog('te_setAverageRate::Setting average rate '.$this->rate_pernight);
        $this->setErrorLog('te_setAverageRate:: Ended');

        return true;
    }


    private function jpd_check_if_all_forbidden(){
        $basic_property_details = jomres_singleton_abstract::getInstance('basic_property_details');         
        $basic_property_details->gather_data($this->property_uid); 

        $result = $this->getVariantsOfType('guesttype');

        $existing_combinations = jpd_get_existing_combinations($this->property_uid,true);            
        $whole_combination_exist = selected_combination_exist($existing_combinations,$result);

        if ( $whole_combination_exist ){
            if(!empty($basic_property_details->room_types)){
               $property_total_room_types = count($basic_property_details->room_types);
            }

            $existing_combination_total_rooom_types = count($existing_combinations[$whole_combination_exist]['room_types']);

            if ($existing_combination_total_rooom_types < $property_total_room_types){
                return true;
            } else {
                foreach ($existing_combinations[$whole_combination_exist]['room_types'] as $rt_id => $data) {
                   if (array_key_exists('discount',$data)){
                      return true;
                   }
                }
            }
        } else {
            return true;
        }

        return false;

    }
    

        /**
     * Calculate how much to charge per person
     * Find the value of the selected guest types, as a variation of the basic room per night of all the selected rooms
     * Eg. you have two rooms, one and $20 and one at $40, the basic value of the rooms is $30 per night.
     * The guest values are calculated as a 'variation' from the basic room value.
     * Whilst this calculation is done every time, the decision as to whether or not to apply this variation is made elsewhere.
     */
    // JPD
    public function setGuestTypeVariantValues()
    {
        $result = $this->getVariantsOfType('guesttype');
        $this->setErrorLog('setGuestTypeVariantValues::Found variants of guesttype: '.count($result));
        $mrConfig = $this->mrConfig;


        if (!empty($result)) {
            // $existing_combinations = jpd_get_existing_combinations($this->property_uid,true);            
            // $whole_combination_exist = selected_combination_exist($existing_combinations,$result);
            $ratePerNight = $this->rate_pernight;
            $ratePerNight_nodiscount = $this->rate_pernight_nodiscount;
             

            $this->setErrorLog('setGuestTypeVariantValues::Setting variant values');
             
            foreach ($result as $r) {
                $id = $r[ 'id' ];
                $qty = $r[ 'qty' ];
                
                $query = "SELECT `is_percentage`,`posneg`,`variance` FROM `#__jomres_customertypes` where id = '$id' ";
                $gt = doSelectSql($query, 2);
                if ($gt) {
                    $variance = $this->combination_discount_applied ? 0.00 : $gt[ 'variance' ];
                    settype($variance, 'float');
                    if ($gt[ 'is_percentage' ] == '0') {
                        $rate = $this->accommodation_tax_rate;
                        if ($mrConfig[ 'prices_inclusive' ] == 1) {
                            $divisor = ($rate / 100) + 1;
                            $variance = $variance / $divisor;
                        }

                        if ($gt[ 'posneg' ] == '1') {
                            $val = $ratePerNight + $variance;
                            $val_nodiscount = $ratePerNight_nodiscount + $variance;
                        } else {
                            $val = $ratePerNight - $variance;
                            $val_nodiscount = $ratePerNight_nodiscount - $variance;
                        }
                    } else {
                        if ($gt[ 'posneg' ] == '1') {
                            $val = (($ratePerNight / 100) * $variance) + $ratePerNight;
                            $val_nodiscount = (($ratePerNight_nodiscount / 100) * $variance) + $ratePerNight_nodiscount;
                        } else {
                            $val = $ratePerNight - (($ratePerNight / 100) * $variance);
                            $val_nodiscount = $ratePerNight_nodiscount - (($ratePerNight_nodiscount / 100) * $variance);
                        }
                    }
                    $this->setErrorLog('setGuestTypeVariantValues::Setting variant value '.$id.' to '.$val);
                    $this->setVariant('guesttype', $id, $qty, $val, $val_nodiscount);                    
                }
            

                 else {
                    return false;
                }
            //}
            }
        } else {
            return true;
        }

        return true;
    }

        /**
         * Creates the javascript date input and returns it as a value.
         */
        public function generateDateInput($fieldName, $dateValue, $myID = false)
        {
            $tmpBookingHandler = jomres_getSingleton('jomres_temp_booking_handler');
            // We need to give the javascript date function a random name because it will be called by both the component and modules
            $uniqueID = '';
            // If this date picker is "arrivalDate" then we need to create a departure date input name too, then set it in showtime. With that we'll be able to tell this set of functionality what the id of the
            // departureDate is so that it can set it's date when this one changes
            if ($fieldName != 'departureDate') {
                list($usec, $sec) = explode(' ', microtime());
                mt_srand($sec * $usec);
                $possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefhijklmnopqrstuvwxyz';
                for ($i = 0; $i < 10; ++$i) {
                    $key = mt_rand(0, strlen($possible) - 1);
                    $uniqueID .= $possible[ $key ];
                }
                set_showtime('departure_date_unique_id', $uniqueID.'_XXX');
            } else {
                $uniqueID = get_showtime('departure_date_unique_id');
            }

            if ($dateValue == '') {
                $dateValue = date('Y/m/d');
            }
            $dateValue = JSCalmakeInputDates($dateValue);

            $dateFormat = $this->cfg_cal_input;
            $dateFormat = strtolower(str_replace('%', '', $dateFormat)); // For the new jquery calendar, we'll strip out the % symbols. This should mean that we don't need to force upgraders to reset their settings.
            $dateFormat = str_replace('y', 'yy', $dateFormat);
            $dateFormat = str_replace('m', 'mm', $dateFormat);
            $dateFormat = str_replace('d', 'dd', $dateFormat);

            if (!defined('_JOMRES_CALENDAR_RTL')) {
                define('_JOMRES_CALENDAR_RTL', 'false');
            }

            $alt_field_string = '';
            $depature_date_doc_ready = '';
            if ($fieldName == 'arrivalDate') {
                $alt_field_string = '
					altField: "#' .get_showtime('departure_date_unique_id').'",

					';
            }

            $onchange = '';
            $onclose = '';
            if ($fieldName == 'arrivalDate') {
                if ($this->cfg_fixedPeriodBookings == '1') {
                    $onchange .= ' getResponse_particulars(\'arrivalDate\',this.value); ';
                } else {
                    $onchange .= ' ajaxADate(this.value,\''.$this->cfg_cal_input.'\'); getResponse_particulars(\'arrivalDate\',this.value,\''.$uniqueID.'\'); ';
                    $onchange .= ' jomresJquery("#'.get_showtime('departure_date_unique_id').'").datepicker(\'option\', {minDate: jomresJquery(this).datepicker(\'getDate\')})';
                    $onclose .= ' jomresJquery("#'.get_showtime('departure_date_unique_id').'").datepicker(\'show\'); ';
                }
            } else {
                $onchange .= ' getResponse_particulars(\'departureDate\',this.value); ';
            }

            $size = ' size="10" ';
            $input_class = '';
            if (using_bootstrap()) {
                $size = '';
                $input_class = ' input-small ';
            }

            $amend_contract = $tmpBookingHandler->getBookingFieldVal('amend_contract');
            $output = '<script type="text/javascript">
			jomresJquery(function() {
				jomresJquery("#' .$uniqueID.'").datepicker( {
					dateFormat: "' .$dateFormat.'",';
            if (!$amend_contract) {
                $output .= 'minDate: 0, ';
            }

            $output .= 'maxDate: "+5Y",';

            if ((using_bootstrap() && jomres_bootstrap_version() == '2') || !using_bootstrap()) {
                $output .= 'buttonImage: \''.get_showtime('live_site').'/'.JOMRES_ROOT_DIRECTORY.'/images/calendar.png\',';
                $bs3_icon = '';
            } else {
                $output .= 'buttonText: "",';
                $bs3_icon = '<span class="input-group-addon" id="dp_trigger_'.$uniqueID.'"><span class="fa fa-calendar"></span></span>';
            }
            $output .= '
					autoSize:true,
					buttonImageOnly: true,
					showOn: "both",
					changeMonth: true,
					changeYear: true,';
            if ($fieldName == 'arrivalDate' && !using_bootstrap()) {
                $output .= 'numberOfMonths: 3,';
            } else {
                $output .= 'numberOfMonths: 1,';
            }

            $output .= 'showOtherMonths: true,
					selectOtherMonths: true,
					showButtonPanel: true,';
            if ($this->jrConfig[ 'calendarstartofweekday' ] == '1') {
                $output .= 'firstDay: 0,';
            } else {
                $output .= 'firstDay: 1,';
            }
            $output .= 'onSelect: function() {
							' .$onchange.'
						}';

            if ($fieldName == 'arrivalDate') {
                $output .= ',beforeShowDay: isAvailable';

                if ($onclose != '') {
                    $output .= ', onClose: function() { '.$onclose.' }';
                }
            }

            $output .= '} );

			});';

            if (using_bootstrap() && jomres_bootstrap_version() == '3') {
                $output .= '
				jomresJquery(function() {jomresJquery("#dp_trigger_'.$uniqueID.'").on("click", function() {jomresJquery("#'.$uniqueID.'").datepicker("show");})});
				';
            }

            $output .= '
			</script>
			<input type="text" readonly="readonly" style="cursor:pointer; background-color: #FFFFFF;" ' .$size.' class="'.$input_class.' form-control input-group" name="'.$fieldName.'" id="'.$uniqueID.'" value="'.$dateValue.'" autocomplete="off" />'.$bs3_icon.'
			';

            return $output;
        }
    }
}
