<?php

	/**
	* This is the flat rate shipping module for Unreal Shopping Cart. To enable
	* flat rate shipping in Unreal Shopping Cart login to the control panel and click the
	* Settings -> Shipping Settings tab in the menu.
	*/
	class SHIPPING_FLATRATE extends ISC_SHIPPING
	{
		private $_shippingcost = 0;

		/*
			Shipping class constructor
		*/
		public function __construct()
		{

			// Setup the required variables for the flat rate shipping module
			parent::__construct();
			$this->_name = GetLang('FlatRateName');
			$this->_image = "";
			$this->_description = GetLang('FlatRateDesc');
			$this->_help = GetLang('FlatRateHelp');
			$this->_height = 0;
			$this->_showtestlink = false;
			$this->_flatrate = true;

			// Flat rate is available worldwide
			$this->_countries = array("all");
		}

		/**
		* Custom variables for the shipping module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{

			if (GetConfig('CurrencyLocation') == 'right') {
				$prefix = '';
				$suffix = GetConfig('CurrencyToken');
			} else {
				$prefix = GetConfig('CurrencyToken');
				$suffix = '';
			}

			$this->_variables['shippingcost'] = array(
				"name" => "Shipping Cost",
				"type" => "textbox",
				"help" => GetLang('FlatRateShippingCostHelp'),
				"default" => "",
				"required" => true,
				"size" => 7,
				'prefix' => $prefix,
				'suffix' => $suffix,
				'format' => 'price',
			);
		}

		public function GetQuote()
		{

			// The following array will be returned to the calling function.
			// It will contain at one ISC_SHIPPING_QUOTE object

			$fr_quote = array();

			// Create a quote object
			$this->_shippingcost = $this->GetValue("shippingcost");
			$fr_quote = new ISC_SHIPPING_QUOTE($this->GetId(), $this->getDisplayName(), $this->_shippingcost);

			return $fr_quote;
		}
	}