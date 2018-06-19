<?php
/**
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @author Rodrigo Rocco <info@jomres-plugins.com>
* @copyright 2012-2016 Rodrigo Rocco - Jomres Plugins & Development - www.jomres-plugins.com
**/
// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################



jr_define('JPD_JOMRES_EMAILS_ENHANCER_TITLE','JPD Emails Enhancer settings');

jr_define('JPD_JOMRES_EMAILS_ENHANCER_DEAR','Bună ziua');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_THANKYOU','vă mulțumim pentru rezervarea la ');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_ORDER','Numarul de rezervare');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_ARRIVAL','Data sosirii');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_DEPARTURE','Data plecării');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_TOTALG','Numărul total al invitaților');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_TOTALP','Prețul total');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_DEPOSIT','Cauțiunea necesară');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_BALANCE','Rest de plata');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_SPECIAL','Special Requirements');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_VIEWBB','Detalii rezervare');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_CHECKIN','Check in times');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_AIRPORTS','Airports');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_DRIVING','Driving directions');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_OTHER','Other transport');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_VIEWP','Informații despre hotel');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_QRDRIVING','QR code for driving directions');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_QROFFICE','QR code for office use only');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_QUESTIONS','Dacă aveți întrebări despre această rezervare, vă rugăm să nu ezitați să ne contactați');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_CONTACT','E-mail nostru este ');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_POLICIES','Termeni şi condiţii');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_COPY','Copyright © Jomres Plugins & Development');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HELLO','Hello, you received a new booking at ');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_GUEST','Oaspete');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_PHONE','Telefon');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_EMAIL','E-mail');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_ADDRESS','Adresă');

jr_define('JPD_JOMRES_EMAILS_ENHANCER_SETTING','Setting');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_VALUE','Value');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_EXPLANATION','Explanation');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HOVERRIDE','Override Mode');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_OVERRIDE','Setting this parameter to Yes will make the below\'s setting enabled for all the properties in the system and front end settings page will be disabled. Otherwise the below settings will be used as default settings.');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HLOGO','Logo');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_LOGODESC1','Logo image for email\'s header.<br> 1) Upload the desired logo file using the ');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_LOGODESC2','2) Intended ONLY for Leohtian template.');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HMEDIA','Media Centre');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HMAIN','Main Image');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_MAIN','Main image for email\'s.<br> 1) Random image of front end Media Centre  "Property main image". 2) Random image of front end Media Centre "Slideshow images" 3) Upload the desired main file using the ');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HVIEWB','"View Bookings" button ?');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_VIEWB','Yes to add the button to the email, it has a link to the booking description page.');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HINDICATION','Indications boxes?');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_INDICATION','Yes to include the descriptions of "Check in times", "Airports","Driving directions", "Other transport". (Applicable to new guest bookings and confirmation letter emails only)');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HMAP','Map');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_MAP','Yes to attach the map to the email. (Applicable to new guest bookings and confirmation letter emails only)');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HZOOM','Map Zoom');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_ZOOM','Zoom of the aboves map that it is an image taken from Google Static Map API, and it could be a value from 0 to 21. The Google API key previously entered into Jomres setting will be used, remember to include "Google Static Map API" at your\'s google API\'s console https://console.developers.google.com');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HQR','QR codes ?');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_QR','Yes to attach the driving directions and admin qr codes. (Applicable to new guest bookings and confirmation letter emails only)');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HFACE','Facebook link');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_FACE','If you enter your facebook page url an icon with the link will be placed at email\'s footer.');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HTWI','Twitter');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_TWI','If you enter your twitter page url an icon with the link will be placed at email\'s footer.');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HINSTA','Instagram');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_INSTA','If you enter your instagram page url an icon with the link will be placed at email\'s footer.');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HSEMAIL','Support email');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_SEMAIL','Email address that will be added at the end of the guest\'s email. If you leave this field blank then the propety\'s front en setting will be used instead.');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HPOL','Pollicies & Disclaimers');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_POL','Yes to include the property policies with in the guest\'s email. (Applicable to new guest bookings and confirmation letter emails only)');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_HCOPY','Copyright text');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_COPY','Text that will be a added on the left part of the email\'s footer.');

jr_define('JPD_JOMRES_EMAILS_ENHANCER_UPLOAD','1) Upload file');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_MAINLOGO','2) Main website logo');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_MAINIMAGE','2) Random main property image');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_SLIDESHOWIMAGE','3) Random slideshow image');

jr_define('JPD_JOMRES_EMAILS_ENHANCER_HELLO_CANCEL','rezervarea dvs. este anulată : ');

jr_define('JPD_JOMRES_EMAILS_ENHANCER_AVAILABLE_VARIABLES','Available variables');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_GUEST_DETAILS','Guest details');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_CONTRACT_DETAILS','Contract details');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_PROPERTY_DETAILS','Property Details');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_EMAIL_SUBJECT','Email subject');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_EDIT','Edit');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_EMAIL_SUBJECT_DESC','Edit email\'s subject output.' );
jr_define('JPD_JOMRES_EMAILS_ENHANCER_MODAL_TITLE','Edit Emails Subject');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_MEDIA_LOGO', 'JPD Emails Logo');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_MEDIA_MAINIMAGE','JPD Emails Main Image');

jr_define('JPD_JOMRES_EMAILS_ENHANCER_CONFIRMED','<strong>Rezervarea dvs. a fost confirmată</strong>. Data sosirii');

jr_define('JPD_JOMRES_EMAILS_ENHANCER_HDATEFORMAT','Use "Date Format"');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_DATEFORMAT','If Yes arrival and departure dates within email will be formated with Jomres settings, if set to No then will be used contract\'s date format yyyy/mm/dd');

jr_define('JPD_JOMRES_EMAILS_ENHANCER_','');
jr_define('JPD_JOMRES_EMAILS_ENHANCER_ROOMS','Cameră');
