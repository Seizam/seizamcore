<?php

namespace WidgetsFramework;

class User extends Parameter {

	/**
	 * <ul>
	 * <li>The default value is <i>null</i></li>
	 * <li>The parameter is not required</li>
	 * </ul>  
	 * @param string $name The parameter name, case insensitive
	 * @throws \MWException When $name not set
	 */
	public function __construct($name) {
		parent::__construct($name);

		$this->default_value = null;
	}

	/**
	 * Transforms from string to MediaWiki User object.
	 * Require a value.
	 * 
	 * @param string|boolean $value A string or boolean <i>true</i>
	 * @return \User
	 * @throws UserError When value is not an existing username.
	 */
	public function parse($value) {

		if ($value === true) {
			// parameter specified without value
			Tools::ThrowUserError(wfMessage('wfmk-value-required', $this->getName()));
		}

		$title = \Title::newFromText($value);
		if (is_null($title)) {
			Tools::ThrowUserError(wfMessage('wfmk-user-syntax', $this->getName()));
		}

		$user = \User::newFromName($value, 'usable');
		if (!$user || $user->getId() == 0) {
			Tools::ThrowUserError(wfMessage('wfmk-user-syntax', $this->getName()));
		}

		return $user;
	}

	/**
	 * Returns the username.
	 * @return String
	 */
	public function getOutput() {
		/** @var \User */
		$value = $this->getValue();
		return $value->getName();
	}

}
