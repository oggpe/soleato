<?php
/**
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @author Aladar Barthi <sales@jomres-extras.com>
* @copyright 2009-2012 Aladar Barthi
**/
// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################

class j06000je_top_destinations
	{
	function __construct()
		{
		$MiniComponents =jomres_getSingleton('mcHandler');
		if ($MiniComponents->template_touch)
			{
			$this->template_touchable=false; return;
			}
		
		$ePointFilepath = get_showtime('ePointFilepath');
		
		$output=array();
		$pageoutput = array();
		$pageoutput = array();
		$rows=array();
		$delay=0;
		$counter = 1;
		
		$width=jomresGetParam( $_REQUEST, 'topdest_imgwidth', '88' );
		$height=jomresGetParam( $_REQUEST, 'topdest_imgheight', '88' );
		$limit=jomresGetParam( $_REQUEST, 'topdest_limit', 10 );
		$all=jomresGetParam( $_REQUEST, 'topdest_all', 0 );
		$arguments = jomresGetParam( $_REQUEST, 'topdest_ptype_ids', '' );

		$property_type_bang = explode (",",$arguments);
		
		$required_property_type_ids = array();
		foreach ($property_type_bang as $ptype)
			{
			if ((int)$ptype!=0)
				$required_property_type_ids[] = (int)$ptype;
			}
		if (!empty($required_property_type_ids))
			{
			$clause="AND `ptype_id` IN (" . jomres_implode($required_property_type_ids) .") ";
			}
		else
			$clause='';
		
		$output['HBROWSEALLDESTINATIONS']=jr_gettext('_JRPORTAL_JE_TOP_DESTINATIONS_BROWSEALLDESTINATIONS','_JRPORTAL_JE_TOP_DESTINATIONS_BROWSEALLDESTINATIONS',FALSE);
		$output['HALLDESTINATIONS']=jr_gettext('_JRPORTAL_JE_TOP_DESTINATIONS_HALLDESTINATIONS','_JRPORTAL_JE_TOP_DESTINATIONS_HALLDESTINATIONS',FALSE);
		$output['URL']=jomresURL(JOMRES_SITEPAGE_URL.'&task=je_top_destinations&topdest_all=1');
		
		//country flags css
		jomres_cmsspecific_addheaddata("css",JOMRES_ROOT_DIRECTORY.'/css/flag-icon-css/css/','flag-icon.min.css');
		
		$jomres_media_centre_images = jomres_singleton_abstract::getInstance('jomres_media_centre_images');
		
		if ($all==0)
			{
			$query="SELECT `property_town`, `property_region`, `property_country`, COUNT(`property_town`) AS `counter` FROM #__jomres_propertys WHERE `published` = '1' $clause GROUP BY `property_town`, `property_region`, `property_country` ORDER BY `counter` DESC LIMIT ".(int)$limit." ";
			$result =doSelectSql($query); 
	
			if (empty($result))
				return;
			
			//get all town images
			$jomres_media_centre_images->get_site_images('towns');

			foreach ($result as $res)
				{
				$r = array();
				
				$town_image = $jomres_media_centre_images->multi_query_images['noimage-medium'];
				
				$town = jomres_cmsspecific_stringURLSafe($res->property_town);
				$town_nicename = jomres_decode($res->property_town);
				if ( isset($jomres_media_centre_images->site_images['towns'][$town][0]['medium']) ) {
					$town_image = $jomres_media_centre_images->site_images['towns'][$town][0]['medium'];
				}
				
				
				if (is_numeric($res->property_region))
					{
					$jomres_regions = jomres_singleton_abstract::getInstance('jomres_regions');
					$region_name = jr_gettext("_JOMRES_CUSTOMTEXT_REGIONS_".$res->property_region,$jomres_regions->get_region_name($res->property_region),false);
					}
				else
					$region_name = jr_gettext('_JOMRES_CUSTOMTEXT_PROPERTY_REGION'.$res->property_region,$res->property_region,false);
				
				$region = jomres_cmsspecific_stringURLSafe($region_name);
				$region_nicename = jomres_decode($region_name);
				
				$search_string ='&town='.$town;
				if (!empty($required_property_type_ids))
					$search_string .= '&ptype='.$required_property_type_ids[0]; //top destinations allows more property type ids to be set, but the search feature only allows searching by one, so we`ll search using the first ptype id passed to top destinations params

				/*OPE*/
				$r['TOWNLINK']=''.jomresURL(JOMRES_SITEPAGE_URL.'&send=Search&calledByModule=mod_jomsearch_m0'.$search_string).'';
				$r['TOWN']='<a href="'.jomresURL(JOMRES_SITEPAGE_URL.'&send=Search&calledByModule=mod_jomsearch_m0'.$search_string).'">'.$town_nicename.'</a>';
				$r['TOWN_NAME'] = $town_nicename;
				$r['COUNTRY'] = getSimpleCountry($res->property_country);
				$r['COUNTER'] = (int)$res->counter." ".jr_gettext('_JRPORTAL_JE_TOP_DESTINATIONS_HPROPERTIES','_JRPORTAL_JE_TOP_DESTINATIONS_HPROPERTIES',FALSE);
				
				if (using_bootstrap())
					{
					$r['TOWN_IMAGE']='<a href="'.jomresURL(JOMRES_SITEPAGE_URL.'&send=Search&calledByModule=mod_jomsearch_m0'.$search_string).'"><img src="'.$town_image.'" alt="'.$town_nicename.'"/></a>';
					}
				else
					{
					$r['TOWN_IMAGE']='<img src="'.$town_image.'" alt="'.$town_nicename.'" width="'.$width.'" height="'.$height.'" style="vertical-align:middle;"/>';
					}
				
				$r['TOWN_IMAGE_PATH'] = $town_image;
				
				$r['COUNTRY_FLAG'] = '<span class="flag-icon flag-icon-'.strtolower($res->property_country).'"></span>';
				
				$r['REGION']='<a href="'.jomresURL(JOMRES_SITEPAGE_URL.'&send=Search&calledByModule=mod_jomsearch_m0&region='.$region).'">'.$region_nicename.'</a>';
				
				if ($counter == 1 || $counter == 7)
					$r['BIG_SPAN']='col-lg-8';
				else
					$r['BIG_SPAN']='col-lg-4';
				
				$counter++;
				
				$delay=$delay+300;
				$r['DELAY']=$delay;
				
				$rows[]=$r;
				}

			$pageoutput[]=$output;
			$tmpl = new patTemplate();
			$tmpl->setRoot( $ePointFilepath.JRDS.'templates'.JRDS.find_plugin_template_directory() );
			$tmpl->readTemplatesFromInput( 'je_top_destinations.html' );
			$tmpl->addRows( 'pageoutput', $pageoutput );
			$tmpl->addRows( 'rows', $rows );
			$tmpl->displayParsedTemplate();
			}
		else
			{
			$rows = array();
			$locations = array();
			
			$query="SELECT DISTINCT `property_town`, `property_country` FROM #__jomres_propertys WHERE `published` = '1' ORDER BY `property_country` ";
			$result = doSelectSql($query);
			
			if (empty($result))
				return;
			
			foreach ($result as $res)
				{
				if ($res->property_country != '')
					$locations[strtoupper($res->property_country)][] = $res->property_town;
				}

			$counter=1;
			foreach ($locations as $code=>$c)
				{
				$r = array();
					
				$r['TOWNS'] = '';
				$r['COUNTRY'] = getSimpleCountry($code);
				$r['COUNTRY_FLAG'] = '<span class="flag-icon flag-icon-'.strtolower($code).'"></span>';
				
				foreach ($c as $t)
					{
					$town = jomres_cmsspecific_stringURLSafe($t);
					$town_nicename = ucfirst(jomres_decode($t));
					
					$search_string ='&town='.$town;
					if (!empty($required_property_type_ids))
						$search_string .= '&ptype='.$required_property_type_ids[0];
				
					if (!using_bootstrap())
						{
						$r['TOWNS'] .= '<a href="'.jomresURL(JOMRES_SITEPAGE_URL.'&send=Search&calledByModule=mod_jomsearch_m0'.$search_string).'">'.$town_nicename.'</a><br />';
						}
					else
						{
						$r['TOWNS'].='<li><a href="'.jomresURL(JOMRES_SITEPAGE_URL.'&send=Search&calledByModule=mod_jomsearch_m0'.$search_string).'">'.$town_nicename.'</a></li>';
						}
					}
				
				if ($counter%4==0)
					$r['STYLE']='clear:both;';
				else
					$r['STYLE']='';
				
				$counter++;
				
				$rows[]=$r;
				}
			
			$pageoutput[]=$output;
			$tmpl = new patTemplate();
			$tmpl->setRoot( $ePointFilepath.JRDS.'templates'.JRDS.find_plugin_template_directory() );
			$tmpl->readTemplatesFromInput( 'je_top_destinations_all.html' );
			$tmpl->addRows( 'pageoutput', $pageoutput );
			$tmpl->addRows( 'rows', $rows );
			$tmpl->displayParsedTemplate();
			}
		}

	// This must be included in every Event/Mini-component
	function getRetVals()
		{
		return null;
		}
	}
