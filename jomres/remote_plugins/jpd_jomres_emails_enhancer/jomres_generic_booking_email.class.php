<?php
/**
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @author Rodrigo Rocco <info@jomres-plugins.com>
* @copyright 2010-2017 Rodrigo Rocco - Jomres Plugins & Development - www.jomres-plugins.com
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################

class jomres_generic_booking_email
	{
	private static $configInstance;

	public function __construct()
		{
		$this->data = array ();
		$this->parsed_email = array();
		}

	public static function getInstance()
		{
		if ( !self::$configInstance )
			{
			self::$configInstance = new jomres_generic_booking_email();
			}

		return self::$configInstance;
		}
	
	public function gather_data($contract_uid = 0, $property_uid = 0, $print = false)
		{
		if ($contract_uid == 0 )
			{
			throw new Exception( "Error: Contract uid not set.");
			}
		if ($property_uid == 0 )
			{
			throw new Exception( "Error: Property uid not set.");
			}
		
		if ( array_key_exists( $contract_uid, $this->data ) )
			return $this->data[$contract_uid];
			
		$mrConfig            = getPropertySpecificSettings();
		
		$tmpBookingHandler   = jomres_singleton_abstract::getInstance( 'jomres_temp_booking_handler' );
		$thisJRUser			 = jomres_singleton_abstract::getInstance( 'jr_user' );
		$MiniComponents 	 = jomres_singleton_abstract::getInstance( 'mcHandler' );
		
		$current_property_details = jomres_singleton_abstract::getInstance( 'basic_property_details' );
		$current_property_details->gather_data($property_uid);
		
		$current_contract_details = jomres_singleton_abstract::getInstance( 'basic_contract_details' );
		$current_contract_details->gather_data($contract_uid, $property_uid);

		$jpd_jomres_emails_enhancer = new jpd_jomres_emails_enhancer();
		$jpd_jomres_emails_enhancer->get();
		$override=true;
		
		if ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['override']!= '1'){
			$override=false;
			$jpd_jomres_emails_enhancer = new jpd_jomres_emails_enhancer();
			$jpd_jomres_emails_enhancer->get($property_uid);
		}

		//selected rooms/resources and tariff details
		$this->data[$contract_uid]['ROOMS'] = '';
		if (isset($current_contract_details->contract[$contract_uid]['roomdeets']))
			{
			foreach ($current_contract_details->contract[$contract_uid]['roomdeets'] as $rd)
				{
				$this->data[$contract_uid]['ROOMS'] .= $current_property_details->all_room_types[$rd['room_classes_uid']]['room_class_abbv'];
				
				if ($rd[ 'room_name' ] != '')
					$this->data[$contract_uid]['ROOMS'] .= ' - ' . $rd[ 'room_name' ];
				/*OPE commented
				if ($rd[ 'room_number' ] != '')
					$this->data[$contract_uid]['ROOMS'] .= ' - ' . $rd[ 'room_number' ];
				*/
				$this->data[$contract_uid]['ROOMS'] .= '; ';
				
				if (!isset($this->data[$contract_uid]['TARIFFS']))
					$this->data[$contract_uid]['TARIFFS'] = '';
				
				$this->data[$contract_uid]['TARIFFS'] .= $rd[ 'rate_title' ] . '; ';
				}
				/*OPE remove last ;*/
				$this->data[$contract_uid]['ROOMS'] = rtrim($this->data[$contract_uid]['ROOMS'],";");
			}
		
		//guest details
		$this->data[$contract_uid]['FIRSTNAME'] = $current_contract_details->contract[$contract_uid]['guestdeets']['firstname'];
		$this->data[$contract_uid]['SURNAME'] = $current_contract_details->contract[$contract_uid]['guestdeets']['surname'];
		$this->data[$contract_uid]['HOUSE'] = $current_contract_details->contract[$contract_uid]['guestdeets']['house'];
		$this->data[$contract_uid]['STREET'] = $current_contract_details->contract[$contract_uid]['guestdeets']['street'];
		$this->data[$contract_uid]['TOWN'] = $current_contract_details->contract[$contract_uid]['guestdeets']['town'];
		$this->data[$contract_uid]['REGION']=$current_contract_details->contract[$contract_uid]['guestdeets']['county'];
		$this->data[$contract_uid]['COUNTRY'] = $current_contract_details->contract[$contract_uid]['guestdeets']['country'];
		$this->data[$contract_uid]['POSTCODE'] = $current_contract_details->contract[$contract_uid]['guestdeets']['postcode'];
		$this->data[$contract_uid]['LANDLINE'] = $current_contract_details->contract[$contract_uid]['guestdeets']['tel_landline'];
		$this->data[$contract_uid]['MOBILE'] = $current_contract_details->contract[$contract_uid]['guestdeets']['tel_mobile'];
		$this->data[$contract_uid]['EMAIL'] = $current_contract_details->contract[$contract_uid]['guestdeets']['email'];
		
		//extras details
		if (isset($current_contract_details->contract[$contract_uid]['extradeets']))
			{
			foreach ( $current_contract_details->contract[$contract_uid]['extradeets'] as $extra )
				{
				if (!isset($this->data[$contract_uid]['EXTRAS']))
					$this->data[$contract_uid]['EXTRAS'] = '';
				
				$this->data[$contract_uid]['EXTRAS'] .= $extra['name'] . ' x ' . $extra['qty'] . '; ';
				}
			}
		
		//links
		$this->data[$contract_uid]['LINK_TO_PROPERTY'] = "<a href=\"" . JOMRES_SITEPAGE_URL_NOSEF . "&task=viewproperty&property_uid=" . $property_uid . "\">" . jr_gettext( '_JOMRES_COM_MR_VRCT_PROPERTY_HEADER_WEBSITE', '_JOMRES_COM_MR_VRCT_PROPERTY_HEADER_WEBSITE', false, false ) . "</a>";
		$this->data[$contract_uid]['LINK_TO_PROPERTY_HREF'] =  JOMRES_SITEPAGE_URL_NOSEF . "&task=viewproperty&property_uid=" . $property_uid;
		
		$this->data[$contract_uid]['LINK_TO_BOOKING_HREF'] =  JOMRES_SITEPAGE_URL_NOSEF . "&task=muviewbooking&contract_uid=" . $contract_uid;
		if ( !$thisJRUser->userIsManager && $thisJRUser->userIsRegistered )
			{
			$this->data[$contract_uid]['LINK_TO_BOOKING'] = "<a href=\"" . JOMRES_SITEPAGE_URL_NOSEF . "&task=muviewbooking&contract_uid=" . $contract_uid . "\">" . jr_gettext( '_JOMCOMP_MYUSER_VIEWBOOKING', '_JOMCOMP_MYUSER_VIEWBOOKING', false, false ) . "</a>";
			$this->data[$contract_uid]['LINK_TO_BOOKING_HREF'] =  JOMRES_SITEPAGE_URL_NOSEF . "&task=muviewbooking&contract_uid=" . $contract_uid;
			
			}

		$this->data[$contract_uid]['LINK_TO_BOOKING_ADMIN_HREF'] =  JOMRES_SITEPAGE_URL_NOSEF . "&task=editBooking&contract_uid=" . $contract_uid;	
			
		
		//number of guest types
		if (isset($current_contract_details->contract[$contract_uid]['guesttype']))
			{
			$total_guests = 0;	
			foreach ( $current_contract_details->contract[$contract_uid]['guesttype'] as $type )
				{
				if (!isset($this->data[$contract_uid]['NUMBER_OF_GUESTS']))
					$this->data[$contract_uid]['NUMBER_OF_GUESTS'] = '';
				
				$this->data[$contract_uid]['NUMBER_OF_GUESTS'] .= $type[ 'title' ].' x '.$type[ 'qty' ].', ';

				$total_guests+=(int)$type[ 'qty' ];
				}
				$this->data[$contract_uid]['TOTAL_GUESTS']=$total_guests;
			}
		
		//invoice printout
		$invoice_id = 0;
		
		if (isset($MiniComponents->miniComponentData[ '03025' ][ 'insertbooking_invoice' ][ 'invoice_id' ]))
			$invoice_id = (int)$MiniComponents->miniComponentData[ '03025' ][ 'insertbooking_invoice' ][ 'invoice_id' ];
		
		if ((int)$invoice_id == 0)
			{
			$invoice_id = $current_contract_details->contract[$contract_uid]['contractdeets']['invoice_uid'];
			}

		if ( (int) $invoice_id > 0 )
			{
			$invoice_template = $MiniComponents->specificEvent( '06005', 'view_invoice', array ( 'internal_call' => true, 'invoice_id' => $invoice_id ) );
			$this->data[$contract_uid]['INVOICE'] = $invoice_template;
			}
		
		
		
		if ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['qrcodes'] == '1'){
			//qr codes
			$url = JOMRES_SITEPAGE_URL_NOSEF . "&task=editBooking&thisProperty=" . $property_uid . "&contract_uid=" . $contract_uid;
			$this->data[$contract_uid]['QR_CODE_OFFICE'] = jomres_make_qr_code( $url );
			
			$url = make_gmap_url_for_property_uid( $property_uid );
			$this->data[$contract_uid]['QR_CODE_MAP'] = jomres_make_qr_code( $url );
			if ($print)
				{
				$this->data[$contract_uid]['QR_OFFICE'] = '<img src="'.$this->data[$contract_uid]['QR_CODE_OFFICE']['relative_path'].'" width="100" height="100" alt="qrcode"/>';
				$this->data[$contract_uid]['QR_DIRECTIONS'] = '<img src="'.$this->data[$contract_uid]['QR_CODE_MAP']['relative_path'].'" width="100" height="100" alt="qrcode"/>';
				$this->data[$contract_uid]['QR_CODE_OFFICE_SRC']=$this->data[$contract_uid]['QR_CODE_OFFICE']['relative_path'];
				$this->data[$contract_uid]['QR_CODE_DIRECTIONS_SRC']=$this->data[$contract_uid]['QR_CODE_MAP']['relative_path'];
				}
			else
				{
				$this->data[$contract_uid]['QR_OFFICE'] = '<img src="cid:qr_code_office" width="100" height="100" alt="qrcode"/>';
				$this->data[$contract_uid]['QR_DIRECTIONS'] = '<img src="cid:qr_code_map" width="100" height="100" alt="qrcode"/>';
				$this->data[$contract_uid]['QR_CODE_OFFICE_SRC']='cid:qr_code_office';
				$this->data[$contract_uid]['QR_CODE_DIRECTIONS_SRC']='cid:qr_code_map';
				}
		}
		
		//custom fields
		$ptype_id = $current_property_details->ptype_id;
		$custom_field_output = array ();
		
		$jomres_custom_field_handler = jomres_singleton_abstract::getInstance('jomres_custom_field_handler');
		$allCustomFields = $jomres_custom_field_handler->getAllCustomFieldsByPtypeId($ptype_id);
		
		if ( count( $allCustomFields ) > 0 )
			{
			$this->data[$contract_uid]['CUSTOM_FIELDS'] = '';
			foreach ( $allCustomFields as $f )
				{
				$formfieldname          = $f[ 'fieldname' ] . "_" . $f[ 'uid' ];
				if (isset($tmpBookingHandler->tmpbooking[ $formfieldname ]))
					$this->data[$contract_uid]['CUSTOM_FIELDS'] .= jr_gettext( 'JOMRES_CUSTOMTEXT' . $f[ 'uid' ], $f[ 'description' ] ).': '.$tmpBookingHandler->tmpbooking[ $formfieldname ].'; ';
				}
			}
		
		//other output
		$this->data[$contract_uid]['PAYMENT_LINK'] = JOMRES_SITEPAGE_URL_NOSEF."&task=confirmbooking&selectedProperty=".$property_uid."&sk=".$current_contract_details->contract[$contract_uid]['contractdeets']['secret_key']."&nofollowtmpl=nofollowtmpl";
		
		$this->data[$contract_uid]['BOOKING_NUMBER'] = $current_contract_details->contract[$contract_uid]['contractdeets']['tag'];
		$this->data[$contract_uid]['ARRIVAL'] = outputDate( $current_contract_details->contract[$contract_uid]['contractdeets']['arrival'] );
		$this->data[$contract_uid]['DEPARTURE'] = outputDate( $current_contract_details->contract[$contract_uid]['contractdeets']['departure'] );
		$this->data[$contract_uid]['TOTAL'] = output_price( $current_contract_details->contract[$contract_uid]['contractdeets']['contract_total'] );
		$this->data[$contract_uid]['DEPOSIT'] = output_price( $current_contract_details->contract[$contract_uid]['contractdeets']['deposit_required'] );
		/*OPE commented */
		//$this->data[$contract_uid]['BALANCE'] = output_price( $current_contract_details->contract[$contract_uid]['contractdeets']['contract_total'] - $current_contract_details->contract[$contract_uid]['contractdeets']['deposit_required'] );
		$this->data[$contract_uid]['SPECIAL_REQUIREMENTS'] = jomres_decode($current_contract_details->contract[$contract_uid]['contractdeets']['special_reqs']);
		/*OPE added */
					if ($current_contract_details->contract[$contract_uid]['contractdeets']['deposit_paid'] == 1) {
						$this->data[$contract_uid]['BALANCE'] = output_price($current_contract_details->contract[$contract_uid]['contractdeets']['contract_total'] - $current_contract_details->contract[$contract_uid]['contractdeets']['deposit_required']);
					} else {
						$this->data[$contract_uid]['BALANCE'] = output_price($current_contract_details->contract[$contract_uid]['contractdeets']['contract_total']);
					}
		
		$this->data[$contract_uid]['ALLOCATION_NOTE'] = '';
		if (isset($tmpBookingHandler->tmpbooking[ "booking_notes" ][ "suppliment_note" ]))
			$this->data[$contract_uid]['ALLOCATION_NOTE'] = $tmpBookingHandler->tmpbooking[ "booking_notes" ][ "suppliment_note" ];
		$this->data[$contract_uid]['BOOKING_CREATION_DATE'] = outputDate($current_contract_details->contract[$contract_uid]['contractdeets']['timestamp']);
		
		$this->data[$contract_uid]['REMOTE_IP'] = $_SERVER['REMOTE_ADDR'];
		
		//property address and policies
		$this->data[$contract_uid]['PROPERTY_NAME'] = $current_property_details->property_name;
		$this->data[$contract_uid]['PROPERTY_STREET'] = $current_property_details->property_street;
		$this->data[$contract_uid]['PROPERTY_TOWN'] = $current_property_details->property_town;
		$this->data[$contract_uid]['PROPERTY_REGION'] = $current_property_details->property_region;
		$this->data[$contract_uid]['PROPERTY_COUNTRY'] = $current_property_details->property_country;
		$this->data[$contract_uid]['PROPERTY_POSTCODE'] = $current_property_details->property_postcode;
		$this->data[$contract_uid]['PROPERTY_TEL'] = $current_property_details->property_tel;
		$this->data[$contract_uid]['PROPERTY_FAX'] = $current_property_details->property_fax;
		$this->data[$contract_uid]['PROPERTY_EMAIL'] = $current_property_details->property_email;
		$this->data[$contract_uid]['POLICIES_AND_DISCLAIMERS'] = $current_property_details->property_policies_disclaimers;

		$this->data[$contract_uid]['ADDRESS'] =$current_property_details->property_street.', '.$current_property_details->property_town.', '.$current_property_details->property_region.', '.$current_property_details->property_country;

		if ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['map'] == '1'){
			$siteConfig = jomres_singleton_abstract::getInstance( 'jomres_config_site_singleton' );
			$jrConfig   = $siteConfig->get();
			$apikey = '';
			if ( $jrConfig[ 'google_maps_api_key' ] != '' )
				$apikey = '&key='.$jrConfig[ 'google_maps_api_key' ];

			$mapzoom=$jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['mapzoom'];
			$lat_long =$current_property_details->lat.','.$current_property_details->long;
			$map ='https://maps.googleapis.com/maps/api/staticmap?center='.$lat_long.'&zoom='.$mapzoom.'&scale=false&size=600x300&maptype=roadmap&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0x6fa1a8%7Clabel:%7C'.$lat_long.$apikey;
			$this->data[$contract_uid]['MAP']=$map;
		}

		$this->data[$contract_uid]['MAP_LINK']='http://maps.google.com/maps?q='.$current_property_details->lat. ',' .$current_property_details->long;
		$this->data[$contract_uid]['IMAGES_PATH']=get_showtime( 'live_site' ) . '/'.JOMRES_ROOT_DIRECTORY."/remote_plugins/jpd_jomres_emails_enhancer/images/";	

		$jomres_media_centre_images = jomres_singleton_abstract::getInstance( 'jomres_media_centre_images' );
		$jomres_media_centre_images->get_images($property_uid);
		if ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['mainimage'] == 'file'){
				if ($override){
					$resource_type   = 'jpd_emails_enhancer_image';
					$this->data[$contract_uid]['MAINIMAGE']=$this->get_backend_images($resource_type);
				}else{
					$this->data[$contract_uid]['MAINIMAGE']=$jomres_media_centre_images->images['jpd_jomres_emails_enhancer_image'][0][0]['large'];
				}
			}
			elseif ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['mainimage'] == 'random'){
				$index = rand(0,count($jomres_media_centre_images->images['slideshow'][0])-1);
				$this->data[$contract_uid]['MAINIMAGE']=$jomres_media_centre_images->images['slideshow'][0][$index]['large'];
			}else{
				$index = rand(0,count($jomres_media_centre_images->images['property'][0])-1);
				$this->data[$contract_uid]['MAINIMAGE']=$jomres_media_centre_images->images['property'][0][$index]['large'];
			}

		if ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['logo'] == 'file'){
			if ($override){
				$resource_type   = 'jpd_emails_enhancer_logo';
				$this->data[$contract_uid]['LOGO']=$this->get_backend_images($resource_type);
			}else{
				$this->data[$contract_uid]['LOGO']=$jomres_media_centre_images->images['jpd_jomres_emails_enhancer_logo'][0][0]['large'];
			}
		}else{
			$this->data[$contract_uid]['LOGO']= $this->get_leohtian_logo();
		}	

		
		/*OPE START*/
		if (isset($this->data[$contract_uid]['MAINIMAGE'])) {
			$tmp = strpos(get_showtime('live_site'), $this->data[$contract_uid]['MAINIMAGE']);
			if ($tmp == false) {
				if ($this->data[$contract_uid]['MAINIMAGE'][0] == '/') {
					$this->data[$contract_uid]['MAINIMAGE'] = get_showtime('live_site') . $this->data[$contract_uid]['MAINIMAGE'];
				} else {
					$this->data[$contract_uid]['MAINIMAGE'] = get_showtime('live_site') . '/' . $this->data[$contract_uid]['MAINIMAGE'];
				}
			}
		}
			
		if (isset($this->data[$contract_uid]['LOGO'])) {
			$tmp = strpos(get_showtime('live_site'), $this->data[$contract_uid]['LOGO']);
			if ($tmp == false) {
				if ($this->data[$contract_uid]['LOGO'][0] == '/') {
					$this->data[$contract_uid]['LOGO'] = get_showtime('live_site') . $this->data[$contract_uid]['LOGO'];
				} else {
					$this->data[$contract_uid]['LOGO'] = get_showtime('live_site') . '/' . $this->data[$contract_uid]['LOGO'];
				}
			}
		}
		/*OPE END*/
		
		$this->data[$contract_uid]['FACEBOOK_URL']=$jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['facebook'];
		$this->data[$contract_uid]['TWITTER_URL']=$jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['twitter'];	
		$this->data[$contract_uid]['INSTAGRAM_URL']=$jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['instagram'];
		$this->data[$contract_uid]['SUPPORT_EMAIL']=!empty($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['supportemail'])?$jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['supportemail']:$current_property_details->property_email;
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_COPY']=$jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['copyrighttext'];
				
		if ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['dateformat']=='1'){
			$this->data[$contract_uid]['ARRIVAL_DATE'] =$this->data[$contract_uid]['ARRIVAL'];
			$this->data[$contract_uid]['DEPARTURE_DATE'] =$this->data[$contract_uid]['DEPARTURE'];
		}else{
			$this->data[$contract_uid]['ARRIVAL_DATE'] =  $current_contract_details->contract[$contract_uid]['contractdeets']['arrival'];
			$this->data[$contract_uid]['DEPARTURE_DATE'] = $current_contract_details->contract[$contract_uid]['contractdeets']['departure'];	
		}

		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_DEAR']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_DEAR','JPD_JOMRES_EMAILS_ENHANCER_DEAR',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_THANKYOU']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_THANKYOU','JPD_JOMRES_EMAILS_ENHANCER_THANKYOU',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_ORDER']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_ORDER','JPD_JOMRES_EMAILS_ENHANCER_ORDER',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_ARRIVAL']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_ARRIVAL','JPD_JOMRES_EMAILS_ENHANCER_ARRIVAL',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_DEPARTURE']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_DEPARTURE','JPD_JOMRES_EMAILS_ENHANCER_DEPARTURE',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_TOTALP']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_TOTALP','JPD_JOMRES_EMAILS_ENHANCER_TOTALP',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_DEPOSIT']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_DEPOSIT','JPD_JOMRES_EMAILS_ENHANCER_DEPOSIT',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_BALANCE']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_BALANCE','JPD_JOMRES_EMAILS_ENHANCER_BALANCE',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_SPECIAL']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_SPECIAL','JPD_JOMRES_EMAILS_ENHANCER_SPECIAL',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_CONFIRMED']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_CONFIRMED','JPD_JOMRES_EMAILS_ENHANCER_CONFIRMED',false,false);
		
		if ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['viewbookigs'] == '1'){
			$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_VIEWBB']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_VIEWBB','JPD_JOMRES_EMAILS_ENHANCER_VIEWBB',false,false);
		}
		if ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['indicationboxes'] == '1'){
			$this->data[$contract_uid]['PROPERTY_CHECKIN_TIMES'] = $current_property_details->property_checkin_times;
			$this->data[$contract_uid]['PROPERTY_AIRPORTS'] = $current_property_details->property_airports;
			$this->data[$contract_uid]['PROPERTY_DRIVING_DIRECTIONS'] = $current_property_details->property_driving_directions;
			$this->data[$contract_uid]['PROPERTY_OTHERTRANSPORT'] = $current_property_details->property_othertransport;
			$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_CHECKIN']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_CHECKIN','JPD_JOMRES_EMAILS_ENHANCER_CHECKIN',false,false);
			$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_AIRPORTS']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_AIRPORTS','JPD_JOMRES_EMAILS_ENHANCER_AIRPORTS',false,false);
			$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_DRIVING']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_DRIVING','JPD_JOMRES_EMAILS_ENHANCER_DRIVING',false,false);
			$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_OTHER']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_OTHER','JPD_JOMRES_EMAILS_ENHANCER_OTHER',false,false);
		}
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_VIEWP']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_VIEWP','JPD_JOMRES_EMAILS_ENHANCER_VIEWP',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_QRDRIVING']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_QRDRIVING','JPD_JOMRES_EMAILS_ENHANCER_QRDRIVING',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_QROFFICE']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_QROFFICE','JPD_JOMRES_EMAILS_ENHANCER_QROFFICE',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_QUESTIONS']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_QUESTIONS','JPD_JOMRES_EMAILS_ENHANCER_QUESTIONS',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_CONTACT']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_CONTACT','JPD_JOMRES_EMAILS_ENHANCER_CONTACT',false,false);
		
		if ($jpd_jomres_emails_enhancer->jpd_jomres_emails_enhancerConfigOptions['policies'] == '1'){
			$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_POLICIES']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_POLICIES','JPD_JOMRES_EMAILS_ENHANCER_POLICIES',false,false);
		}
	
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_HELLO']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_HELLO','JPD_JOMRES_EMAILS_ENHANCER_HELLO',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_HELLO_CANCEL']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_HELLO_CANCEL','JPD_JOMRES_EMAILS_ENHANCER_HELLO_CANCEL',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_GUEST']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_GUEST','JPD_JOMRES_EMAILS_ENHANCER_GUEST',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_PHONE']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_PHONE','JPD_JOMRES_EMAILS_ENHANCER_PHONE',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_EMAIL']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_EMAIL','JPD_JOMRES_EMAILS_ENHANCER_EMAIL',false,false);
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_ADDRESS']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_ADDRESS','JPD_JOMRES_EMAILS_ENHANCER_ADDRESS',false,false);

		$this->data[$contract_uid]['TOTAL_NOFEE'] = output_price($current_contract_details->contract[$contract_uid]['contractdeets']['contract_total'] - VALUE_TO_ADD);
        $this->data[$contract_uid]['DEPOSIT_NOFEE'] = output_price($current_contract_details->contract[$contract_uid]['contractdeets']['deposit_required'] - VALUE_TO_ADD);
		/*OPE added*/
		$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_ROOMS']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_ROOMS','JPD_JOMRES_EMAILS_ENHANCER_ROOMS',false,false);	

		
        $curr_jintour_properties = get_showtime('jintour_properties');
        if( !in_array( $property_uid,$curr_jintour_properties) ){
        	$this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_TOTALG']=jr_gettext('JPD_JOMRES_EMAILS_ENHANCER_TOTALG','JPD_JOMRES_EMAILS_ENHANCER_TOTALG',false,false);
        }

		return true;
		}

	private function get_backend_images($resource_type){
		$files = scandir_getfiles(JOMRES_IMAGELOCATION_ABSPATH . $resource_type . JRDS);
		if (count($files)>0)
			{
			foreach ($files as $file)
				{
				$image = JOMRES_IMAGELOCATION_RELPATH . $resource_type ."/" . $file;
				break;
				}
			if ($resource_type == 'jpd_emails_enhancer_logo' ){
				return $image;
			} else {
				return $this->rel_replace($image);	
			}
			
		}	
		return false;	
	}

	private function get_email_overrides(){
		return scandir_getfiles(JPD_EMAILS_PATH);
	}

	private function get_leohtian_logo(){
		if (this_cms_is_joomla()){	
			$app      = JFactory::getApplication();
			$template     = $app->getTemplate(true);

			if ($template->template == 'jr_leotian')
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->quoteName('params'));
				$query->from($db->quoteName('#__template_styles'));
				$query->where($db->quoteName('template') . ' LIKE '. $db->quote($template->template));
				$db->setQuery($query);
				$template_parameters = $db->loadResult();
				$template_parameters = json_decode($template_parameters,true);
				return $template_parameters['logoimage'];
		}
	}

	private function rel_replace($url){
		return str_replace(get_showtime( 'live_site' ) . '/', '', $url);
	}	
	
	public function parse_email($email_type = '', $contract_uid = 0)
		{
		$this->parsed_email = array();
		$pageoutput = array();
		
		if ($email_type == '' || $contract_uid == 0)
			return;

		//get custom template
		$MiniComponents = jomres_singleton_abstract::getInstance( 'mcHandler' );
		if (isset($MiniComponents->registeredClasses['03150'][$email_type]))
			$MiniComponents->specificEvent( '03150', $email_type );
		else
			return;
		
		$overrides = $this->get_email_overrides();
		if (!in_array($MiniComponents->miniComponentData['03150'][$email_type]['type'].'.html', $overrides)){

			$email_default_html = file_get_contents( $MiniComponents->miniComponentData['03150'][$email_type]['default_template'] );
			$email_default_html = str_replace("[", "{", $email_default_html);
			$email_default_html = str_replace("]", "}", $email_default_html);
			
			$email_body = jr_gettext('_EMAIL_TEXT_'.$email_type, $email_default_html, false);
			
			//let`s replace the [ ] with { }
			$email_body = str_replace("[", "{", $email_body);
			$email_body = str_replace("]", "}", $email_body);
			$email_subject = jr_gettext('_EMAIL_SUBJECT_'.$email_type, '[PROPERTY_NAME] - [BOOKING_NUMBER]', false);

		}else{
			$property_uid = get_showtime('property_uid');
			set_showtime('property_uid',0);
			$email_subject = jr_gettext('_EMAIL_SUBJECT_'.$email_type, '[PROPERTY_NAME] - [BOOKING_NUMBER]', false);
			set_showtime('property_uid',$property_uid);
		}

		
		$email_subject = str_replace("[", "{", $email_subject);
		$email_subject = str_replace("]", "}", $email_subject);

		//parse emails
		$pageoutput[] = $this->data[$contract_uid];
		
		//parse email subject
		$tmpl = new patTemplate();
		$tmpl->readTemplatesFromInput( '<patTemplate:tmpl name="pageoutput" unusedvars="strip">'.$email_subject.'</patTemplate:tmpl>', 'String' );
		$tmpl->addRows( 'pageoutput', $pageoutput );
		$this->parsed_email['subject'] = $tmpl->getParsedTemplate();

		//parse email body
		$tmpl = new patTemplate();
		$tmpl->addRows( 'pageoutput', $pageoutput );
		if (!empty($this->data[$contract_uid]['SPECIAL_REQUIREMENTS'])){
			$tmpl->addRows( 'specialreq', $pageoutput );
		}
			
		if (!empty($this->data[$contract_uid]['FACEBOOK_URL']))
			$tmpl->addRows( 'facebook', $pageoutput );
		if (!empty($this->data[$contract_uid]['TWITTER_URL']))
			$tmpl->addRows( 'twitter', $pageoutput );
		if (!empty($this->data[$contract_uid]['INSTAGRAM_URL']))
			$tmpl->addRows( 'instagram', $pageoutput );
		if (isset($this->data[$contract_uid]['MAP']))
			$tmpl->addRows( 'map', $pageoutput );
		if (isset($this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_VIEWBB']))
			$tmpl->addRows( 'viewbb', $pageoutput );
		if (isset($this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_CHECKIN']))
			$tmpl->addRows( 'indicationboxes', $pageoutput );
		if (isset($this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_POLICIES']))
			$tmpl->addRows( 'policies', $pageoutput );
		if (isset($this->data[$contract_uid]['QR_CODE_OFFICE']))
			$tmpl->addRows( 'qrcodes', $pageoutput );
		else
			$tmpl->addRows( 'noqrcodes', $pageoutput );

		if (isset($this->data[$contract_uid]['JPD_JOMRES_EMAILS_ENHANCER_TOTALG']))
			$tmpl->addRows( 'totalguests', $pageoutput );
		
		$tmpl->setRoot(JOMRES_TEMPLATEPATH_BACKEND);
		
		if (!in_array($MiniComponents->miniComponentData['03150'][$email_type]['type'].'.html', $overrides))
			$tmpl->readTemplatesFromInput( '<patTemplate:tmpl name="pageoutput" unusedvars="strip">'.$email_body.'</patTemplate:tmpl>', 'String' );
		else	
			$tmpl->readTemplatesFromInput($MiniComponents->miniComponentData['03150'][$email_type]['type'].'.html');
		
		$this->parsed_email['text'] = $tmpl->getParsedTemplate();
		
		if (isset($this->data[$contract_uid]['QR_CODE_OFFICE'])){
			//attachments
			$office_qr_code = array ( "type" => "image", "image_path" =>$this->data[$contract_uid]['QR_CODE_OFFICE'][ 'absolute_path' ], "CID" => "qr_code_office" );
			$this->parsed_email['attachments'][] = $office_qr_code;

			$map_qr_code = array ( "type" => "image", "image_path" => $this->data[$contract_uid]['QR_CODE_MAP'][ 'absolute_path' ], "CID" => "qr_code_map" );
			$this->parsed_email['attachments'][] = $map_qr_code;
		}
		
		return true;
		}
	}
