<?php

/**
 * @copyright   Copyright (C) 2019 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2020-12-02 13:56:48
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


class ApplicationObjectExtension_ContactMethodValidation implements iApplicationObjectExtension {
	 	  
	/**
	 * Invoked to determine whether an object can be written to the database 
	 *	
	 * The GUI calls this verb and reports any issue.
	 * Anyhow, this API can be called in other contexts such as the CSV import tool.
	 * 
	 * @param DBObject $oObject The target object
	 * @return string[] A list of errors message. An error message is made of one line and it can be displayed to the end-user.
	 */	
	public function OnCheckToWrite($oObject) {
				
		// Note: you can set properties here on the object!
		// Abusing this method to only keep digits of valid phone numbers.
		// Only blocks invalid input
				
		if($oObject instanceof ContactMethod) {
			
			$sContactDetail = $oObject->Get('contact_detail');
			$sContactMethod = $oObject->Get('contact_method');
			
			switch($sContactMethod) {
				
				case 'phone':
				
					$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sContactDetail);
					$oObject->Set('contact_detail', $oBelgianPhoneNumberValidator->GetDigits());
					
					switch(true) {
						
						// Belgian land line phone number
						case $oBelgianPhoneNumberValidator->IsValidBelgianLandLineNumber() == true:
						
						// International phone number - hopefully land line
						case $oBelgianPhoneNumberValidator->HasValidBelgianCountryPrefix() == false:
						
							// No error
							break;
												
						// Unidentified
						default:
						
							return [ 
								Dict::S('Errors/ContactMethod/InvalidPhoneNumber')
							];
							
						
					}
				
					break;
				
				case 'mobile_phone':
				
					$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sContactDetail);
					$oObject->Set('contact_detail', $oBelgianPhoneNumberValidator->GetDigits());
					
					switch(true) {
						
						// Belgian mobile phone number
						case $oBelgianPhoneNumberValidator->IsValidBelgianMobileNumber() == true:
						
						// International phone number - hopefully mobile
						case $oBelgianPhoneNumberValidator->HasValidBelgianCountryPrefix() == false:
						
							// No error
							break;
						
						// Unidentified
						default: 
							
							// Belgian land line phone number
							return [ 
								Dict::S('Errors/ContactMethod/InvalidMobilePhoneNumber')
							];
							
					}
					
					break;
				
				
				case 'email':
								
					if(!filter_var($sContactDetail, FILTER_VALIDATE_EMAIL)) {
					 
						return [
							Dict::S('Errors/ContactMethod/InvalidEmail')
						];
						
					}
				
				default:
					break;
					
			}
			
		}
		
		elseif($oObject instanceof Person) {
			
			$aErrors = [];
			
			// Check phone
			// ---
			$sPhoneNumber = $oObject->Get('phone');
			$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sPhoneNumber);
			
			switch(true) {
				
				// Empty (OK for Person, NOT for ContactMethod)
				case strlen($sPhoneNumber) == 0:
				
				// Belgian land line number
				case $oBelgianPhoneNumberValidator->IsValidBelgianLandLineNumber() == true:
				
				// International phone number - hopefully land line
				case $oBelgianPhoneNumberValidator->HasValidBelgianCountryPrefix() == false:
				
				// 'admin' gets +00 000 000 000 by default during iTop installation in one of the 2.7 versions
				case $sPhoneNumber == '+00 000 000 000' && (Int)$oObject->GetKey() < 1:
				
					// No error
					break;
					
				// Unidentified
				default:
				
					$aErrors[] = Dict::S('Errors/ContactMethod/InvalidPhoneNumber').' - Person ID '.$oObject->GetKey().' - Number: '.$sPhoneNumber;
				
			}
			
			// Check mobile phone
			// ---
			$sMobileNumber = $oObject->Get('mobile_phone');
			$oBelgianPhoneNumberValidator = new BelgianPhoneNumberValidator($sMobileNumber);
			
			switch(true) {
				
				// Empty (OK for Person, NOT for ContactMethod)
				case strlen($sMobileNumber) == 0:
				
				// Belgian mobile phone number
				case $oBelgianPhoneNumberValidator->IsValidBelgianMobileNumber() == true:
				
				// International phone number - hopefully mobile
				case $oBelgianPhoneNumberValidator->HasValidBelgianCountryPrefix() == false:
				
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
		
			return $aErrors;
			
		}
		
		// No errors		
		return [];
				
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
	
	
}
