<?php

/**
 * @copyright   Copyright (c) 2019-2021 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2.6.210803
 * @experimental
 *
 * Defines classes for phone number validation, which offers some methods to validate phone numbers. Warning: specifically for Belgian use.
 *
 */
  
 namespace jb_itop_extensions\contact_method;
	
	/**
	 * Class which is able to validate phone numbers.
	 */
	class PhoneNumberValidator {
		
		/** @var \String $sDigitsOnly Only digits */
		public $sDigitsOnly;
		
		/** @var \String $sOriginalProvidedInput Original phone number */
		public $sOriginalProvidedInput;
		
		/**
		 * Constructor for phone number validator. Immediately keeps a copy of only the digits.
		 * 
		 * @param \String|null $sPhoneNumber Phone number as specified by the user
		 * @return void
		 * 
		 */
		public function __construct($sPhoneNumber = null) {
			
			if($sPhoneNumber !== null) {
				$this->SetDigits($sPhoneNumber);
			}
			
		}
		
		/**
		 * Gets a number without digits.
		 * 
		 * @param \String Returns only the digits of the specified number.
		 * @return void
		 * 
		 */
		public function GetDigits() {
			return $this->sDigitsOnly;
		}
		
		/**
		 * Checks whether the phone number only contains allowed characters
		 * 
		 * @return \Boolean
		 * 
		 */
		public function ContainsOnlyAllowedCharacters() {
			// Could start with +countrycode
			// Starts with zonal code
			// Might contain spaces
			// Often a slash, but not required
			// Then numbers, sometimes with a space or dot in between
			return preg_match('/^(\+|)[0-9 \.\/]{1,}$/', $this->sOriginalProvidedInput);
		}
		
		/**
		 * Sets phone digits for this validator.
		 * 
		 * @param \String $sPhoneNumber Phone number as specified by the user
		 * @return void
		 * 
		 */
		public function SetDigits($sPhoneNumber) {
			
			$this->sOriginalProvidedInput = $sPhoneNumber;
			$this->sDigitsOnly = preg_replace('/[^\d]/', '', $this->sOriginalProvidedInput);
			
		}
		
		/**
		 * Checks whether the provided phone number is a local phone number.
		 * 
		 * @return \Boolean
		 * 
		 */
		public function IsLocalNumber($sPhoneNumber) {
			
			return (substr($this->GetDigits(), 0, 1) == '0');
			
		}
	}


	/**
	 * Class which is able to validate Belgian phone numbers.
	 */
	class BelgianPhoneNumberValidator extends PhoneNumberValidator {
		
		/**
		 * @var \Integer $iCountryCode Country code
		 */
		public $iCountryCode = 32;
		
		/**
		 * @var \Integer $iDigitsMobile Max number of digits for a land line number, excluding country code or starting 0
		 */
		public $iDigitsLandLine = 8;
		
		
		/**
		 * @var \Integer|null $sDigitsOnlyWithoutCountryPrefix Phone number without country prefix
		 */
		public $sDigitsOnlyWithoutCountryPrefix = null;
		
		/**
		 * @var \Integer $iDigitsMobile Max number of digits for a mobile number, excluding country code or starting 0
		 */
		public $iDigitsMobile = 9;
		
		/**
		 * @var \Boolean $bAcceptLocalZone Whether or not to accept local numbers (0). Defaults to false.
		 */
		public $bAcceptLocalZone = false;
		
		/**
		 * Constructor for phone number validator. Immediately keeps a copy of only the digits.
		 * 
		 * @param \String|null $sPhoneNumber Phone number as specified by the user
		 * @return void
		 * 
		 */
		public function __construct($sPhoneNumber = null, $bAcceptLocalZone = false) {
			
			parent::__construct($sPhoneNumber, $bAcceptLocalZone);
			
			$this->bAcceptLocalZone = true;
			
			
		}
				
		/**
		 * Returns only the local digits. No +countrycode or 0
		 * 
		 * @return \String Local digits
		 * 
		 */
		public function GetLocalDigits() {
		
			// Adapted to Belgian situation
			return $this->sDigitsOnlyWithoutCountryPrefix;
			
		}
		
		/**
		 * Returns whether this is has a valid country code (or 0)
		 *
		 * @return \Boolean
		 */
		public function HasValidCountryPrefix() {
			
			if($this->bAcceptLocalZone == true && preg_match('/^0/', $this->GetDigits())) {
				return true;
			}
			elseif(preg_match('/^'.$this->iCountryCode.'/', $this->GetDigits())) {
				return true;
			}
			
			return false;
		}
		
		/**
		 * Returns whether this has a mobile prefix for a Belgian mobile phone number
		 *
		 * @param \String $sPhone Phone number
		 *
		 * @return \Boolean
		 */
		public function HasValidMobilePrefix() {
			
			// https://www.bipt.be/en/consumers/telephone/numbering/numbering-principles
			// https://en.wikipedia.org/wiki/Telephone_numbers_in_Belgium
			// 044, 045, 046, 047, 048, 049
			// 04 = land line too, Liège and Voeren. Less digits!
			// Hence a check for the first 2 digits and the total number of digits.			
			
			switch(true) {
				case preg_match('/^(4[4-9])/', $this->sDigitsOnlyWithoutCountryPrefix) && $this->HasValidNumberOfDigitsMobileNumber():
					return true;
					break;
					
				default:
					break;
					
			}
		
			return false;

		}
		
		/**
		 * Returns whether this is a valid Belgian phone number.
		 * 
		 * @param \Boolean $bStrict Optional setting to also enforce use of only valid characters. Defaults to false.
		 * 
		 * @return \Boolean
		 * 
		 */
		public function IsValidNumber($bStrict = false) {
			
			if($bStrict == true && $this->ContainsOnlyAllowedCharacters() == false) {
				return false;
			}
			
			return ($this->IsValidLandLineNumber() == true || $this->IsValidMobileNumber() == true);
		}
		
		/**
		 * Returns whether this is a valid Belgian phone number.
		 * 
		 * @return \Boolean
		 * 
		 */
		public function IsValidLandLineNumber() {
			// 1) must have a Belgian country code or 0
			// 2) must have a length of 8 non-country digits
			return ($this->HasValidCountryPrefix() == true && $this->HasValidNumberOfDigitsLandLineNumber() == true);
		}
		
		/**
		 * Returns whether this is a valid Belgian phone number.
		 * 
		 * @return \Boolean
		 * 
		 */
		public function IsValidMobileNumber() {
			// 1) must have a Belgian country code or 0
			// 2) must have a valid Belgian mobile prefix
			// 3) must have a length of 9 non-country digits
			return ($this->HasValidCountryPrefix() == true && $this->HasValidMobilePrefix() == true && $this->HasValidNumberOfDigitsMobileNumber() == true);
		}
		
		/**
		 * Returns whether the number of digits for a land line number is correct
		 * 
		 * @return \Boolean
		 * 
		 */
		public function HasValidNumberOfDigitsLandLineNumber() {
			return (strlen($this->sDigitsOnlyWithoutCountryPrefix) == $this->iDigitsLandLine);
		}
		
		/**
		 * Returns whether the number of digits for a mobile number is correct
		 * 
		 * @return \Boolean
		 * 
		 */
		public function HasValidNumberOfDigitsMobileNumber() {
			return (strlen($this->sDigitsOnlyWithoutCountryPrefix) == $this->iDigitsMobile);
		}
		
		/**
		 * Sets phone digits for this validator.
		 * 
		 * @param \String $sPhoneNumber Phone number as specified by the user
		 * @return void
		 * 
		 */
		public function SetDigits($sPhoneNumber) {
			
			parent::SetDigits($sPhoneNumber);
			$this->sDigitsOnlyWithoutCountryPrefix = preg_replace('/^(0|'.$this->iCountryCode.')/', '', $this->sDigitsOnly);
			
		}
		
	}
	
