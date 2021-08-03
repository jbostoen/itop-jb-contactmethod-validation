<?php

/**
 * @copyright   Copyright (C) 2019 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2.6.210803
 *
 * PHP Main file
 */

namespace jb_itop_extensions\contact_method;

use \jb_itop_extensions\contact_method\BelgianPhoneNumberValidator;

// iTop internals
use \CMDBChange;
use \DBObject;
use \DBObjectSearch;
use \DBOBjectSet;
use \Dict;
use \iApplicationObjectExtension;
use \MetaModel;

// iTop classes
use \ContactMethod;
use \Person;

/**
 * Class AOE_Validation. Used to actually validate, but not improve the object.
 */
class AOE_Validation implements iApplicationObjectExtension {
	 	  
	/**
	 * Invoked to determine whether an object can be written to the database 
	 *	
	 * The GUI calls this verb and reports any issue.
	 * Anyhow, this API can be called in other contexts such as the CSV import tool.
	 * 
	 * @param DBObject $oObject The target object
	 * @return string[] A list of errors message. An error message is made of one line and it can be displayed to the end-user.
	 * 
	 * @details It's worth noting that you have complete access and even may set properties here; but it's called during DBUpdate() and changes will NOT be saved.
	 */	
	public function OnCheckToWrite($oObject) {
				
		$aErrors = [];
	
		if($oObject instanceof ContactMethod) {
			
			$sContactMethod = $oObject->Get('contact_method');
			$sContactDetail = $oObject->Get('contact_detail');
			
			switch($sContactMethod) {
				
				case 'phone':
				
					$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sContactDetail, true);
					$oObject->Set('contact_detail', $oBelgianPhoneNumberValidator->GetDigits());
					
					switch(true) {
						
						// Belgian land line phone number
						case $oBelgianPhoneNumberValidator->IsValidLandLineNumber() == true:
						
						// International phone number - hopefully land line
						case $oBelgianPhoneNumberValidator->HasValidCountryPrefix() == false:
						
							// No error
							break;
												
						// Unidentified
						default:
						
							$aErrors[] = Dict::S('Errors/ContactMethod/InvalidPhoneNumber');							
						
					}
				
					break;
				
				case 'mobile_phone':
				
					$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sContactDetail, true);
					$oObject->Set('contact_detail', $oBelgianPhoneNumberValidator->GetDigits());
					
					switch(true) {
						
						// Belgian mobile phone number
						case $oBelgianPhoneNumberValidator->IsValidMobileNumber() == true:
						
						// International phone number - hopefully mobile
						case $oBelgianPhoneNumberValidator->HasValidCountryPrefix() == false:
						
							// No error
							break;
						
						// Unidentified
						default: 
							
							// Belgian land line phone number
							$aErrors[] = Dict::S('Errors/ContactMethod/InvalidMobilePhoneNumber');
							
					}
					
					break;
				
				
				case 'email':
								
					if(!filter_var($sContactDetail, FILTER_VALIDATE_EMAIL)) {
					 
						$aErrors[] =  Dict::S('Errors/ContactMethod/InvalidEmail');
						
					}
				
				default:
					break;
					
			}
			
		}
		
		elseif($oObject instanceof Person) {
			
			$sPhoneNumber = $oObject->Get('phone');
			$sMobileNumber = $oObject->Get('mobile_phone');
			
			// Check phone
			// ---
			
			$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sPhoneNumber, true);
			
			switch(true) {
				
				// Empty (OK for Person, NOT for ContactMethod)
				case strlen($sPhoneNumber) == 0:
				
				// Belgian land line number
				case $oBelgianPhoneNumberValidator->IsValidLandLineNumber() == true:
				
				// International phone number - hopefully land line
				case $oBelgianPhoneNumberValidator->HasValidCountryPrefix() == false:
				
				// 'admin' gets +00 000 000 000 by default during iTop installation in one of the 2.7 versions
				case $sPhoneNumber == '+00 000 000 000' && (Int)$oObject->GetKey() < 1:
				
					// No error
					break;
					
				// Unidentified
				default:
				
					$aErrors[] = Dict::S('Errors/ContactMethod/InvalidPhoneNumber');
				
			}
			
			// Check mobile phone
			// ---
			$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sMobileNumber, true);
			
			switch(true) {
				
				// Empty (OK for Person, NOT for ContactMethod)
				case strlen($sMobileNumber) == 0:
				
				// Belgian mobile phone number
				case $oBelgianPhoneNumberValidator->IsValidMobileNumber() == true:
				
				// International phone number - hopefully mobile
				case $oBelgianPhoneNumberValidator->HasValidCountryPrefix() == false:
				
					// No error
					break;
				
				// Unidentified
				default:
				
					$aErrors[] = Dict::S('Errors/ContactMethod/InvalidMobilePhoneNumber');
				
			}
			
			// Check email
			// ---
			$sEmail = $oObject->Get('email');
			
			if($sEmail != '' && !filter_var( $sEmail, FILTER_VALIDATE_EMAIL)) {
				$aErrors[] = Dict::S('Errors/ContactMethod/InvalidEmail');				
			}
		
			
		}
		
		return $aErrors;
				
	}	


	/**
	 * Invoked to determine whether an object has been modified in memory
	 *
	 *	The GUI calls this verb to determine the message that will be displayed to the end-user.
	 *	Anyhow, this API can be called in other contexts such as the CSV import tool.
	 *	
	 * If the extension returns false, then the framework will perform the usual evaluation.
	 * Otherwise, the answer is definitively "yes, the object has changed".	 	 	 
	 *	 
	 * @param DBObject $oObject The target object
	 * @return boolean True if something has changed for the target object
	 */	
	public function OnIsModified($oObject) {
		return false;
	}
	
	/**
	 * Invoked to determine whether an object can be deleted from the database
	 *	
	 * The GUI calls this verb and stops the deletion process if any issue is reported.
	 * 	 
	 * Please note that it is not possible to cascade deletion by this mean: only stopper issues can be handled. 	 
	 * 
	 * @param DBObject $oObject The target object
	 * @return string[] A list of errors message. An error message is made of one line and it can be displayed to the end-user.
	 */	
	public function OnCheckToDelete($oObject) {
		return [];
		
	}

	/**
	 * Invoked when an object is updated into the database
	 *	
	 * The method is called right <b>after</b> the object has been written to the database.
	 * 
	 * @param DBObject $oObject The target object
	 * @param CMDBChange|null $oChange A change context. Since 2.0 it is fine to ignore it, as the framework does maintain this information once for all the changes made within the current page
	 * @return void
	 */	
	public function OnDBUpdate($oObject, $oChange = null) {
		return;
	}

	/**
	 * Invoked when an object is created into the database
	 *	
	 * The method is called right <b>after</b> the object has been written to the database.
	 * 
	 * @param DBObject $oObject The target object
	 * @param CMDBChange|null $oChange A change context. Since 2.0 it is fine to ignore it, as the framework does maintain this information once for all the changes made within the current page
	 * @return void
	 */	
	public function OnDBInsert($oObject, $oChange = null) {
		return;
	}

	/**
	 * Invoked when an object is deleted from the database
	 *	
	 * The method is called right <b>before</b> the object will be deleted from the database.
	 * 
	 * @param DBObject $oObject The target object
	 * @param CMDBChange|null $oChange A change context. Since 2.0 it is fine to ignore it, as the framework does maintain this information once for all the changes made within the current page
	 * @return void
	 */	
	public function OnDBDelete($oObject, $oChange = null) {
		return;
	}
	
	/**
	 * 
	 * Updates related Person object each time a ContactMethod is removed.
	 * It checks if it's one of the default contact details (phone, mobile phone, email) and sets the info to blank or the last other known info.
	 *  	 
	 */
	public function OnContactMethodDeleted($oObject) {
				
		// If a ContactMethod is deleted, the related Person object should be updated to reflect these changes 
		if($oObject instanceof ContactMethod) {
			
			$oContactMethod = $oObject;
			$sContactMethod = $oContactMethod->Get('contact_method');
			$sContactDetail = $oContactMethod->Get('contact_detail');
			
			switch($sContactMethod) {
				
				case 'phone':
				case 'mobile_phone':
				case 'email':
					
					// Retrieve Person
					$sOQL = 'SELECT Person WHERE id = :person_id';
					$oSet_Person = new DBObjectSet(DBObjectSearch::FromOQL($sOQL), [], [
						'person_id' => $oContactMethod->Get('person_id')
					]);
			
					// Only 1 person should be retrieved
					$oPerson = $oSet_Person->Fetch();

					// Set Person's attribute value to empty if the value was the same as the one for the ContactMethod that has been deleted
					if($oPerson->Get($sContactMethod) == $sContactDetail) {
						
						$oPerson->Set($sContactMethod, '');
							
						
						// But what if a fallback is possible, to update the Person object with another most recent ContactMethod of the same contact_method type?
						// Since this query is executed before ContactMethod is really deleted: 
						// Don't include the current (deleted) ContactMethod object in t his query.
						$sOQL = 'SELECT ContactMethod WHERE person_id = :person_id AND contact_method = :contact_method AND id != :id';			
						
						// Return maximum one. Descending by id.
						$oSet_ContactMethod = new DBObjectSet(DBObjectSearch::FromOQL($sOQL), ['id' => false], [
							'person_id' => $oPerson->GetKey(),
							'contact_method' => $sContactMethod,
							'id' => $oContactMethod->GetKey()
						], [], 1);
							
						// But maybe there's another last known ContactMethod.
						// Simply look at 'id' and take the last one, not date of last change (yet)
						while($oExistingContactMethod = $oSet_ContactMethod->Fetch()){
							$oPerson->Set($sContactMethod, $oExistingContactMethod->Get('contact_detail'));	
						}
						
						// Reset person attribute
						$oPerson->DBUpdate();
						
					}
					break;
					
				default:
					break;
		
			}
			
		}
		
		// if Person is deleted, iTop should automatically remove all ContactMethods by default
		
		return;
	}
	
}

abstract class CME_Validation implements iContactMethodExtension {
	
	/** @var \Float Rank. Order of execution of these validations. */
	public $fRank = 1;
	 	  
	/**
	 * Performs validation. May move a 'phone' value to a 'mobile phone' value.
	 * 
	 * @param \Person|\ContactMethod iTop object
	 * 
	 * @return void
	 */	
	public function BeforeSaveObject($oObject) {
		
		if($oObject instanceof ContactMethod) {
			
			$sContactMethod = $oObject->Get('contact_method');
			$sContactDetail = $oObject->Get('contact_detail');
			
			switch($sContactMethod) {
				
				case 'phone':
				
					$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sContactDetail, true);
					$oObject->Set('contact_detail', $oBelgianPhoneNumberValidator->GetDigits());
					
					// Phone number is actually a mobile phone number?
					if($oBelgianPhoneNumberValidator->IsValidMobileNumber() == true) {
						$oObject->Set('contact_method', 'mobile_phone');
					}
					break;
				
				case 'mobile_phone':
				
					$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sContactDetail, true);
					$oObject->Set('contact_detail', $oBelgianPhoneNumberValidator->GetDigits());
					break;
					
				default:
					break;
					
			}
			
		}
		
		elseif($oObject instanceof Person) {
			
			$aErrors = [];
			$sPhoneNumber = $oObject->Get('phone');
			$sMobileNumber = $oObject->Get('mobile_phone');
			
			// Check phone
			// ---
			
			$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sPhoneNumber, true);
			
			// Phone number is actually a mobile phone number?
			if($oBelgianPhoneNumberValidator->IsValidMobileNumber() == true && $sMobileNumber == '') {
				
				$oObject->Set('mobile_phone', $oBelgianPhoneNumberValidator->GetDigits());
				$oObject->Set('phone', '');
				
			}
			
		}
				
	}	
	
}
